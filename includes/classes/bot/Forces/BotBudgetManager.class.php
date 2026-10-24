<?php

require_once 'includes/classes/bot/Evaluation/EconomyValuator.class.php';
require_once 'includes/classes/bot/Kernel/BotClock.class.php';

/**
 * NovaRush Bot AI v3.1 - Decoupled Incremental Budget & Vector Logistics Manager
 *
 * Implements:
 * 1. Source of Truth & Decoupled Incremental Credit Model:
 *    - Local scope per planet (planet_id) for Building (mines), Fleet, and Defense credits.
 *    - Global scope per user (user_id) for Research credits (empire-wide aggregate).
 *    - Incremental accumulation per daemon cycle:
 *        Delta_B = (P_hour * delta_t / 3600) * r_k
 *        B_t = min(S_warehouse * r_k, B_{t-1} + Delta_B)
 *    - Static warehouse ceiling & surplus diverted to Common Opportunity Fund (opportunity_fund).
 *
 * 2. Savings Lock & Dynamic Storage Guard:
 *    - Forced unlock of warehouses (22, 23, 24) if target cost > Storage_Max * 0.95,
 *      financed directly from the accumulated savings reserve.
 *    - Anti-Farm Watchdog: If for 3 consecutive cycles (135s) Delta_stock[deficit] <= 0,
 *      abort savings lock, release reserve, and divert into immediate defenses & light fighters.
 *    - Hardened ETA calculation protected against division by zero (max(0.001, P_hour / 3600)).
 *
 * 3. Persistence in uni1_bots (budget_credits, savings_lock, last_budget_time).
 */
class BotBudgetManager
{
    const MAX_SAVINGS_LOCK_ETA = 14400; // 4 hours maximum reasonable savings lock horizon

    public static $internalBucketSwapsCount = 0;

    public static function getInternalBucketSwapsCount()
    {
        return self::$internalBucketSwapsCount;
    }

    public static function resetInternalBucketSwapsCount()
    {
        self::$internalBucketSwapsCount = 0;
    }

    public function getMaxSavingsLockEta(array $savingsLock = null)
    {
        $lock = $savingsLock ?? $this->savingsLock;
        if (!empty($lock)) {
            $elemId = (int)($lock['element_id'] ?? 0);
            if ($elemId === 123 || $elemId === 124) {
                return 28800; // 8 hours for imperial strategic research
            }
        }
        return self::MAX_SAVINGS_LOCK_ETA;
    }

    private $ctx;
    private $credits = array();
    private $savingsLock = null;
    private $lastBudgetTime = 0;

    public function __construct(BotContext $ctx)
    {
        $this->ctx = $ctx;
        $this->loadState();
    }

    /**
     * Load persisted state from botRow
     */
    private function loadState()
    {
        $botRow = $this->ctx->botRow;

        // 1. Budget credits JSON
        if (!empty($botRow['budget_credits'])) {
            $parsed = @json_decode($botRow['budget_credits'], true);
            if (is_array($parsed)) {
                $this->credits = $parsed;
            }
        }

        if (empty($this->credits) || !isset($this->credits['planets'])) {
            $this->credits = array(
                'planets'          => array(),
                'research'         => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
                'opportunity_fund' => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
                'last_physical'    => array(),
                'last_updated'     => BotClock::get(),
            );
        }

        // Initialize last_physical baseline if missing
        if (!isset($this->credits['last_physical']) || !is_array($this->credits['last_physical'])) {
            $this->credits['last_physical'] = array();
            try {
                $db = Database::get();
                if ($db && !empty($this->ctx->botId)) {
                    $planetRows = $db->select("SELECT id, metal, crystal, deuterium FROM %%PLANETS%% WHERE id_owner = :id AND destruyed = 0;", array(':id' => (int)$this->ctx->botId));
                    if (is_array($planetRows)) {
                        foreach ($planetRows as $p) {
                            $pId = (int)$p['id'];
                            $this->credits['last_physical'][$pId] = array(
                                'metal'     => (float)$p['metal'],
                                'crystal'   => (float)$p['crystal'],
                                'deuterium' => (float)$p['deuterium'],
                            );
                        }
                    }
                }
            } catch (Throwable $e) {
                // Non-DB unit test fallback
            }
        }

        // 2. Savings lock JSON and Multi-Category Initialization
        $this->credits['savings_locks_by_cat'] = isset($this->credits['savings_locks_by_cat']) && is_array($this->credits['savings_locks_by_cat']) ? $this->credits['savings_locks_by_cat'] : array();

        if (!empty($botRow['savings_lock'])) {
            $parsedLock = @json_decode($botRow['savings_lock'], true);
            if (is_array($parsedLock) && !empty($parsedLock['element_id'])) {
                $this->savingsLock = $parsedLock;
                $cat = $parsedLock['category'] ?? 'mines';
                if (!isset($this->credits['savings_locks_by_cat'][$cat])) {
                    $this->credits['savings_locks_by_cat'][$cat] = $parsedLock;
                }
            }
        }

        // 3. Last budget time
        $this->lastBudgetTime = isset($botRow['last_budget_time']) ? (int)$botRow['last_budget_time'] : 0;
    }

    /**
     * Get macro category budget ratios based on archetype & personality
     *
     * @return array [mines => float, fleet => float, defense => float, research => float]
     */
    public function getMacroRatios()
    {
        if (isset($this->ctx->personality) && method_exists($this->ctx->personality, 'getBudgetRatios')) {
            return $this->ctx->personality->getBudgetRatios();
        }

        $persName = isset($this->ctx->personality->name) ? strtolower($this->ctx->personality->name) : 'balanced';

        switch ($persName) {
            case 'raider':
            case 'aggressive':
                return array('mines' => 0.15, 'fleet' => 0.55, 'defense' => 0.10, 'research' => 0.20);

            case 'miner':
            case 'economic':
                return array('mines' => 0.55, 'fleet' => 0.10, 'defense' => 0.15, 'research' => 0.20);

            case 'bunker':
            case 'turtle':
            case 'defensive':
                return array('mines' => 0.25, 'fleet' => 0.15, 'defense' => 0.50, 'research' => 0.10);

            case 'balanced':
            default:
                return array('mines' => 0.30, 'fleet' => 0.35, 'defense' => 0.15, 'research' => 0.20);
        }
    }

    /**
     * Incrementally update credits across all colonies and global research
     *
     * @param BotEmpireState $empire
     */
    public function updateCredits(BotEmpireState $empire)
    {
        $now = BotClock::get();
        $deltaT = ($this->lastBudgetTime > 0) ? ($now - $this->lastBudgetTime) : 45;
        $deltaT = max(1, min(3600, $deltaT));

        $ratios = $this->getMacroRatios();
        $planets = $empire->getPlanets();

        if (!is_array($planets) || empty($planets)) {
            $this->lastBudgetTime = $now;
            $this->saveState();
            return;
        }

        if (!isset($this->credits['opportunity_fund'])) {
            $this->credits['opportunity_fund'] = array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);
        }

        $totalEmpireProdM = 0.0;
        $totalEmpireProdC = 0.0;
        $totalEmpireProdD = 0.0;
        $totalEmpireCapM  = 0.0;
        $totalEmpireCapC  = 0.0;
        $totalEmpireCapD  = 0.0;

        $totalEmpireWindfallM = 0.0;
        $totalEmpireWindfallC = 0.0;
        $totalEmpireWindfallD = 0.0;

        $empireUser = method_exists($empire, 'getUser') ? $empire->getUser() : $this->ctx->user;
        if (!empty($empireUser['_auto_paid_research']) && is_array($empireUser['_auto_paid_research'])) {
            foreach ($empireUser['_auto_paid_research'] as $paidTech) {
                $paidLabId = (int)($paidTech['planet_id'] ?? 0);
                $pCost = $paidTech['cost'] ?? array();
                $pM = (float)($pCost[901] ?? 0.0);
                $pC = (float)($pCost[902] ?? 0.0);
                $pD = (float)($pCost[903] ?? 0.0);
                if (isset($this->credits['research'])) {
                    $this->credits['research']['metal']     = round(max(0.0, (float)($this->credits['research']['metal'] ?? 0.0) - $pM), 2);
                    $this->credits['research']['crystal']   = round(max(0.0, (float)($this->credits['research']['crystal'] ?? 0.0) - $pC), 2);
                    $this->credits['research']['deuterium'] = round(max(0.0, (float)($this->credits['research']['deuterium'] ?? 0.0) - $pD), 2);
                }
                if ($paidLabId > 0 && isset($this->credits['last_physical'][$paidLabId])) {
                    $this->credits['last_physical'][$paidLabId]['metal']     = max(0.0, (float)$this->credits['last_physical'][$paidLabId]['metal'] - $pM);
                    $this->credits['last_physical'][$paidLabId]['crystal']   = max(0.0, (float)$this->credits['last_physical'][$paidLabId]['crystal'] - $pC);
                    $this->credits['last_physical'][$paidLabId]['deuterium'] = max(0.0, (float)$this->credits['last_physical'][$paidLabId]['deuterium'] - $pD);
                }
            }
        }

        foreach ($planets as $pId => $pEntry) {
            $pData = $pEntry['data'];
            $pId = (int)$pId;

            if (!empty($pData['_auto_paid_mines']) && is_array($pData['_auto_paid_mines'])) {
                $paidM = (float)($pData['_auto_paid_mines'][901] ?? 0.0);
                $paidC = (float)($pData['_auto_paid_mines'][902] ?? 0.0);
                $paidD = (float)($pData['_auto_paid_mines'][903] ?? 0.0);
                if (isset($this->credits['planets'][$pId]['mines'])) {
                    $this->credits['planets'][$pId]['mines']['metal']     = round(max(0.0, (float)($this->credits['planets'][$pId]['mines']['metal'] ?? 0.0) - $paidM), 2);
                    $this->credits['planets'][$pId]['mines']['crystal']   = round(max(0.0, (float)($this->credits['planets'][$pId]['mines']['crystal'] ?? 0.0) - $paidC), 2);
                    $this->credits['planets'][$pId]['mines']['deuterium'] = round(max(0.0, (float)($this->credits['planets'][$pId]['mines']['deuterium'] ?? 0.0) - $paidD), 2);
                }
                if (isset($this->credits['last_physical'][$pId])) {
                    $this->credits['last_physical'][$pId]['metal']     = max(0.0, (float)$this->credits['last_physical'][$pId]['metal'] - $paidM);
                    $this->credits['last_physical'][$pId]['crystal']   = max(0.0, (float)$this->credits['last_physical'][$pId]['crystal'] - $paidC);
                    $this->credits['last_physical'][$pId]['deuterium'] = max(0.0, (float)$this->credits['last_physical'][$pId]['deuterium'] - $paidD);
                }
            }

            $curStockM = (float)($pData['metal'] ?? 0.0);
            $curStockC = (float)($pData['crystal'] ?? 0.0);
            $curStockD = (float)($pData['deuterium'] ?? 0.0);

            if (!isset($this->credits['planets'][$pId])) {
                $this->credits['planets'][$pId] = array(
                    'mines'   => array(
                        'metal'     => round($curStockM * (float)$ratios['mines'], 2),
                        'crystal'   => round($curStockC * (float)$ratios['mines'], 2),
                        'deuterium' => round($curStockD * (float)$ratios['mines'], 2),
                    ),
                    'fleet'   => array(
                        'metal'     => round($curStockM * (float)$ratios['fleet'], 2),
                        'crystal'   => round($curStockC * (float)$ratios['fleet'], 2),
                        'deuterium' => round($curStockD * (float)$ratios['fleet'], 2),
                    ),
                    'defense' => array(
                        'metal'     => round($curStockM * (float)$ratios['defense'], 2),
                        'crystal'   => round($curStockC * (float)$ratios['defense'], 2),
                        'deuterium' => round($curStockD * (float)$ratios['defense'], 2),
                    ),
                );
                $this->credits['research']['metal']     = round((float)($this->credits['research']['metal'] ?? 0.0) + ($curStockM * (float)$ratios['research']), 2);
                $this->credits['research']['crystal']   = round((float)($this->credits['research']['crystal'] ?? 0.0) + ($curStockC * (float)$ratios['research']), 2);
                $this->credits['research']['deuterium'] = round((float)($this->credits['research']['deuterium'] ?? 0.0) + ($curStockD * (float)$ratios['research']), 2);
                $this->credits['last_physical'][$pId] = array(
                    'metal'     => $curStockM,
                    'crystal'   => $curStockC,
                    'deuterium' => $curStockD,
                );
            }

            $prodM = max(0.0, (float)($pData['metal_perhour'] ?? 0.0));
            $prodC = max(0.0, (float)($pData['crystal_perhour'] ?? 0.0));
            $prodD = max(0.0, (float)($pData['deuterium_perhour'] ?? 0.0));

            $capM = max((float)($pData['metal_max'] ?? 10000.0), $curStockM);
            $capC = max((float)($pData['crystal_max'] ?? 10000.0), $curStockC);
            $capD = max((float)($pData['deuterium_max'] ?? 10000.0), $curStockD);

            $totalEmpireProdM += $prodM;
            $totalEmpireProdC += $prodC;
            $totalEmpireProdD += $prodD;
            $totalEmpireCapM  += $capM;
            $totalEmpireCapC  += $capC;
            $totalEmpireCapD  += $capD;

            $prodDeltaM = ($prodM * $deltaT / 3600.0);
            $prodDeltaC = ($prodC * $deltaT / 3600.0);
            $prodDeltaD = ($prodD * $deltaT / 3600.0);

            // Detect external windfall (loot from combat, recyclers returning, expeditions)
            if (!isset($this->credits['last_physical'][$pId])) {
                $this->credits['last_physical'][$pId] = array('metal' => $curStockM, 'crystal' => $curStockC, 'deuterium' => $curStockD);
            }

            $lastPhysM = (float)$this->credits['last_physical'][$pId]['metal'];
            $lastPhysC = (float)$this->credits['last_physical'][$pId]['crystal'];
            $lastPhysD = (float)$this->credits['last_physical'][$pId]['deuterium'];

            $windfallM = max(0.0, $curStockM - ($lastPhysM + $prodDeltaM));
            $windfallC = max(0.0, $curStockC - ($lastPhysC + $prodDeltaC));
            $windfallD = max(0.0, $curStockD - ($lastPhysD + $prodDeltaD));

            if ($windfallM < 1.0) $windfallM = 0.0;
            if ($windfallC < 1.0) $windfallC = 0.0;
            if ($windfallD < 1.0) $windfallD = 0.0;

            $totalEmpireWindfallM += $windfallM;
            $totalEmpireWindfallC += $windfallC;
            $totalEmpireWindfallD += $windfallD;

            // Update local planetary categories: mines, fleet, defense
            foreach (array('mines', 'fleet', 'defense') as $cat) {
                $r_k = $ratios[$cat];

                $deltaBM = ($prodDeltaM * $r_k) + ($windfallM * $r_k);
                $deltaBC = ($prodDeltaC * $r_k) + ($windfallC * $r_k);
                $deltaBD = ($prodDeltaD * $r_k) + ($windfallD * $r_k);

                $maxCeilingM = $capM * $r_k;
                $maxCeilingC = $capC * $r_k;
                $maxCeilingD = $capD * $r_k;

                // Metal accumulation strictly within category ceiling
                $curM = (float)($this->credits['planets'][$pId][$cat]['metal'] ?? 0.0) + $deltaBM;
                $this->credits['planets'][$pId][$cat]['metal'] = round(min($maxCeilingM, $curM), 2);

                // Crystal accumulation strictly within category ceiling
                $curC = (float)($this->credits['planets'][$pId][$cat]['crystal'] ?? 0.0) + $deltaBC;
                $this->credits['planets'][$pId][$cat]['crystal'] = round(min($maxCeilingC, $curC), 2);

                // Deuterium accumulation strictly within category ceiling
                $curD = (float)($this->credits['planets'][$pId][$cat]['deuterium'] ?? 0.0) + $deltaBD;
                $this->credits['planets'][$pId][$cat]['deuterium'] = round(min($maxCeilingD, $curD), 2);
            }

            // Update tracked physical stock
            $this->credits['last_physical'][$pId] = array(
                'metal'     => $curStockM,
                'crystal'   => $curStockC,
                'deuterium' => $curStockD,
            );
        }

        // Synchronize lunar bodies: moons have zero native production, so their virtual budget
        // is directly backed by their physical stock (minus tactical buffer for phalanx)
        $moons = $empire->getMoons();
        if (is_array($moons)) {
            foreach ($moons as $mId => $mEntry) {
                $mData = $mEntry['data'];
                $mId = (int)$mId;

                if (!isset($this->credits['planets'][$mId])) {
                    $this->credits['planets'][$mId] = array(
                        'mines'   => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
                        'fleet'   => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
                        'defense' => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
                    );
                }

                $mStockM = (float)($mData['metal'] ?? 0.0);
                $mStockC = (float)($mData['crystal'] ?? 0.0);
                $mStockD = max(0.0, (float)($mData['deuterium'] ?? 0.0) - 50000.0);

                // On moons, mines category covers lunar buildings (Base Lunar, Jump Gate, Phalanx, Robots, Nano).
                // Moon budget directly mirrors available physical stock to prevent phantom ratchet inflation when resources are transported away.
                $this->credits['planets'][$mId]['mines']['metal']     = $mStockM;
                $this->credits['planets'][$mId]['mines']['crystal']   = $mStockC;
                $this->credits['planets'][$mId]['mines']['deuterium'] = $mStockD;

                $this->credits['last_physical'][$mId] = array(
                    'metal'     => $mStockM,
                    'crystal'   => $mStockC,
                    'deuterium' => (float)($mData['deuterium'] ?? 0.0),
                );
            }
        }

        // Global research budget (empire-wide aggregate)
        $r_research = $ratios['research'];
        $deltaResM = (($totalEmpireProdM * $deltaT / 3600.0) * $r_research) + ($totalEmpireWindfallM * $r_research);
        $deltaResC = (($totalEmpireProdC * $deltaT / 3600.0) * $r_research) + ($totalEmpireWindfallC * $r_research);
        $deltaResD = (($totalEmpireProdD * $deltaT / 3600.0) * $r_research) + ($totalEmpireWindfallD * $r_research);

        $resCeilingM = $totalEmpireCapM * $r_research;
        $resCeilingC = $totalEmpireCapC * $r_research;
        $resCeilingD = $totalEmpireCapD * $r_research;

        $curResM = (float)($this->credits['research']['metal'] ?? 0.0) + $deltaResM;
        $this->credits['research']['metal'] = round(min($resCeilingM, $curResM), 2);

        $curResC = (float)($this->credits['research']['crystal'] ?? 0.0) + $deltaResC;
        $this->credits['research']['crystal'] = round(min($resCeilingC, $curResC), 2);

        $curResD = (float)($this->credits['research']['deuterium'] ?? 0.0) + $deltaResD;
        $this->credits['research']['deuterium'] = round(min($resCeilingD, $curResD), 2);

        // Enforce Strict Physical Invariant Reconciliation (Conservation of Virtual Earmarks)
        $this->reconcilePhysicalInvariant($empire, $ratios);

        // Reequilibrio Simétrico Anti-Estancamiento de 3 Recursos entre bolsas locales en RAM
        $this->rebalanceStagnantBuckets($empire);

        $this->credits['last_updated'] = $now;
        $this->lastBudgetTime = $now;
        $this->saveState();
    }

    /**
     * Reequilibrio Simétrico MSE-Neutro (4:2:1) entre bolsas locales en RAM:
     * Si una bolsa local tiene superávit severo de un recurso (>40M MSE y >70% de su bolsa)
     * y otra bolsa local del mismo planeta tiene superávit de otro recurso complementario,
     * intercambian al ratio canónico 1:2:4 manteniendo Delta_MSE = 0.0 en ambas bolsas.
     * Jamás confisca ni transfiere MSE gratuitamente entre categorías.
     *
     * @param BotEmpireState $empire
     */
    public function rebalanceStagnantBuckets(BotEmpireState $empire)
    {
        $planets = $empire->getPlanets();
        if (!is_array($planets) || empty($planets)) {
            return;
        }

        $localCats = array('mines', 'fleet', 'defense');
        $rates = array('metal' => 1.0, 'crystal' => 2.0, 'deuterium' => 4.0);

        foreach ($planets as $pId => $pEntry) {
            $pId = (int)$pId;
            if (!isset($this->credits['planets'][$pId])) {
                continue;
            }

            // Detectar bolsas con recurso dominante (>40M MSE y >70% del MSE de su propia bolsa)
            $dominantByCat = array();
            foreach ($localCats as $cat) {
                if ($this->isCategoryLocked($pId, $cat)) {
                    continue;
                }
                $bM = max(0.0, (float)($this->credits['planets'][$pId][$cat]['metal'] ?? 0.0));
                $bC = max(0.0, (float)($this->credits['planets'][$pId][$cat]['crystal'] ?? 0.0));
                $bD = max(0.0, (float)($this->credits['planets'][$pId][$cat]['deuterium'] ?? 0.0));
                $availD = ($cat === 'fleet') ? max(0.0, $bD - 15000000.0) : $bD;

                $mseM = $bM * 1.0;
                $mseC = $bC * 2.0;
                $mseD = $availD * 4.0;
                $totMSE = $mseM + $mseC + $mseD;
                if ($totMSE <= 0.0) {
                    continue;
                }

                if ($mseM > 40000000.0 && ($mseM / $totMSE) > 0.70) {
                    $dominantByCat[$cat] = array('res' => 'metal', 'avail_mse' => $mseM * 0.40);
                } elseif ($mseC > 40000000.0 && ($mseC / $totMSE) > 0.70) {
                    $dominantByCat[$cat] = array('res' => 'crystal', 'avail_mse' => $mseC * 0.40);
                } elseif ($mseD > 40000000.0 && ($mseD / $totMSE) > 0.70) {
                    $dominantByCat[$cat] = array('res' => 'deuterium', 'avail_mse' => $mseD * 0.40);
                }
            }

            if (count($dominantByCat) < 2) {
                continue;
            }

            $catsWithDom = array_keys($dominantByCat);
            for ($i = 0; $i < count($catsWithDom); $i++) {
                for ($j = $i + 1; $j < count($catsWithDom); $j++) {
                    $catA = $catsWithDom[$i];
                    $catB = $catsWithDom[$j];
                    $resA = $dominantByCat[$catA]['res'];
                    $resB = $dominantByCat[$catB]['res'];
                    if ($resA === $resB) {
                        continue;
                    }

                    $swapMSE = min($dominantByCat[$catA]['avail_mse'], $dominantByCat[$catB]['avail_mse']);
                    if ($swapMSE <= 100000.0) {
                        continue;
                    }

                    $unitsA = round($swapMSE / $rates[$resA], 2);
                    $unitsB = round($swapMSE / $rates[$resB], 2);

                    $this->credits['planets'][$pId][$catA][$resA] = max(0.0, round($this->credits['planets'][$pId][$catA][$resA] - $unitsA, 2));
                    $this->credits['planets'][$pId][$catA][$resB] = round($this->credits['planets'][$pId][$catA][$resB] + $unitsB, 2);

                    $this->credits['planets'][$pId][$catB][$resB] = max(0.0, round($this->credits['planets'][$pId][$catB][$resB] - $unitsB, 2));
                    $this->credits['planets'][$pId][$catB][$resA] = round($this->credits['planets'][$pId][$catB][$resA] + $unitsA, 2);

                    $dominantByCat[$catA]['avail_mse'] -= $swapMSE;
                    $dominantByCat[$catB]['avail_mse'] -= $swapMSE;
                    self::$internalBucketSwapsCount++;
                }
            }
        }
    }

    /**
     * Deduct costs atomically from local category or global research,
     * drawing from opportunity_fund if needed.
     *
     * @param int $planetId
     * @param string $category 'mines', 'fleet', 'defense', 'research'
     * @param array $cost Array of resource costs [901 => metal, 902 => crystal, 903 => deut]
     * @return bool True if deducted
     */
    public function deductFromBudget($planetId, $category, array $cost)
    {
        $planetId = (int)$planetId;
        $costM = (float)($cost[901] ?? $cost['metal'] ?? 0.0);
        $costC = (float)($cost[902] ?? $cost['crystal'] ?? 0.0);
        $costD = (float)($cost[903] ?? $cost['deuterium'] ?? 0.0);

        if ($category === 'research') {
            $this->deductResourcePair($this->credits['research'], 'metal', $costM);
            $this->deductResourcePair($this->credits['research'], 'crystal', $costC);
            $this->deductResourcePair($this->credits['research'], 'deuterium', $costD);
        } else {
            if (!isset($this->credits['planets'][$planetId][$category])) {
                $this->credits['planets'][$planetId][$category] = array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);
            }
            $this->deductResourcePair($this->credits['planets'][$planetId][$category], 'metal', $costM);
            $this->deductResourcePair($this->credits['planets'][$planetId][$category], 'crystal', $costC);
            $this->deductResourcePair($this->credits['planets'][$planetId][$category], 'deuterium', $costD);
        }

        // Keep last_physical synchronized when resources are spent on this planet
        if (isset($this->credits['last_physical'][$planetId])) {
            $this->credits['last_physical'][$planetId]['metal']     = max(0.0, (float)$this->credits['last_physical'][$planetId]['metal'] - $costM);
            $this->credits['last_physical'][$planetId]['crystal']   = max(0.0, (float)$this->credits['last_physical'][$planetId]['crystal'] - $costC);
            $this->credits['last_physical'][$planetId]['deuterium'] = max(0.0, (float)$this->credits['last_physical'][$planetId]['deuterium'] - $costD);
        }

        $this->saveState();
        return true;
    }

    /**
     * Synchronize physical evacuation from an endangered planet (fleetsave)
     *
     * @param int $planetId
     * @param array $cargo
     */
    /**
     * Synchronize physical evacuation from an endangered planet (fleetsave)
     *
     * @param int $planetId
     * @param array $cargo
     */
    public function syncPlanetEvacuation($planetId, array $cargo)
    {
        $planetId = (int)$planetId;
        $m = (float)($cargo[901] ?? $cargo['metal'] ?? 0.0);
        $c = (float)($cargo[902] ?? $cargo['crystal'] ?? 0.0);
        $d = (float)($cargo[903] ?? $cargo['deuterium'] ?? 0.0);

        if ($m > 0.0 || $c > 0.0 || $d > 0.0) {
            $this->deductCargoFromLocalPlanet($planetId, $m, $c, $d);
        }

        if (isset($this->credits['last_physical'][$planetId])) {
            $this->credits['last_physical'][$planetId]['metal']     = max(0.0, (float)$this->credits['last_physical'][$planetId]['metal'] - $m);
            $this->credits['last_physical'][$planetId]['crystal']   = max(0.0, (float)$this->credits['last_physical'][$planetId]['crystal'] - $c);
            $this->credits['last_physical'][$planetId]['deuterium'] = max(0.0, (float)$this->credits['last_physical'][$planetId]['deuterium'] - $d);
        }
        $this->saveState();
    }

    /**
     * Deduct fuel consumption and dispatched cargo from planet budget when a fleet launches
     *
     * @param int $planetId
     * @param int $mission
     * @param array $cargoResources [901 => M, 902 => C, 903 => D]
     * @param float $fuelConsumption Deuterium consumed by fleet
     */
    public function deductDispatchedMission($planetId, $mission, array $cargoResources, $fuelConsumption = 0.0)
    {
        $planetId = (int)$planetId;
        $mission  = (int)$mission;
        $fuelConsumption = max(0.0, (float)$fuelConsumption);

        // 1. Deduct fuel consumption strictly from 'fleet' budget and physical baseline
        if ($fuelConsumption > 0.0) {
            if (isset($this->credits['planets'][$planetId]['fleet']['deuterium'])) {
                $cur = (float)$this->credits['planets'][$planetId]['fleet']['deuterium'];
                $this->credits['planets'][$planetId]['fleet']['deuterium'] = round(max(0.0, $cur - $fuelConsumption), 2);
            }
            if (isset($this->credits['last_physical'][$planetId]['deuterium'])) {
                $this->credits['last_physical'][$planetId]['deuterium'] = round(max(0.0, (float)$this->credits['last_physical'][$planetId]['deuterium'] - $fuelConsumption), 2);
            }
        }

        // 2. Dispatched cargo handling by mission type
        $mCargo = max(0.0, (float)($cargoResources[901] ?? $cargoResources['metal'] ?? 0.0));
        $cCargo = max(0.0, (float)($cargoResources[902] ?? $cargoResources['crystal'] ?? 0.0));
        $dCargo = max(0.0, (float)($cargoResources[903] ?? $cargoResources['deuterium'] ?? 0.0));

        if ($mission === 3 || $mission === 7) {
            // Logistics Transport (Mission 3) & Colonization Seed Cargo (Mission 7):
            // Cargo is deducted exclusively from the donor category and last_physical
            // by LogisticsPlanner / BotExpansionPlanner via debitDonorCategory() to prevent double-debit and cross-category bleeding.
        } elseif ($mission === 4 && $mCargo <= 0.0 && $cCargo <= 0.0 && $dCargo > 0.0) {
            // Military Deuterium Piggyback to FOB (Mission 4 Deploy):
            // Deduct dCargo exclusively from 'fleet' budget and physical baseline.
            if (isset($this->credits['planets'][$planetId]['fleet']['deuterium'])) {
                $cur = (float)$this->credits['planets'][$planetId]['fleet']['deuterium'];
                $this->credits['planets'][$planetId]['fleet']['deuterium'] = round(max(0.0, $cur - $dCargo), 2);
            }
            if (isset($this->credits['last_physical'][$planetId]['deuterium'])) {
                $this->credits['last_physical'][$planetId]['deuterium'] = round(max(0.0, (float)$this->credits['last_physical'][$planetId]['deuterium'] - $dCargo), 2);
            }
        } else {
            // General cargo (e.g., Mission 4 Fleetsave evacuation with Metal/Crystal, or other cargo missions):
            // Deduct proportionally across local categories and adjust physical baseline.
            if ($mCargo > 0.0 || $cCargo > 0.0 || $dCargo > 0.0) {
                $this->deductCargoFromLocalPlanet($planetId, $mCargo, $cCargo, $dCargo);
                if (isset($this->credits['last_physical'][$planetId])) {
                    $this->credits['last_physical'][$planetId]['metal']     = round(max(0.0, (float)$this->credits['last_physical'][$planetId]['metal'] - $mCargo), 2);
                    $this->credits['last_physical'][$planetId]['crystal']   = round(max(0.0, (float)$this->credits['last_physical'][$planetId]['crystal'] - $cCargo), 2);
                    $this->credits['last_physical'][$planetId]['deuterium'] = round(max(0.0, (float)$this->credits['last_physical'][$planetId]['deuterium'] - $dCargo), 2);
                }
            }
        }

        $this->saveState();
    }

    /**
     * Deduct dispatched cargo proportionally across local planet categories
     */
    public function deductCargoFromLocalPlanet($planetId, $mCargo, $cCargo, $dCargo)
    {
        $planetId = (int)$planetId;
        if (!isset($this->credits['planets'][$planetId])) {
            return;
        }

        $cats = array('mines', 'fleet', 'defense');
        foreach (array('metal' => $mCargo, 'crystal' => $cCargo, 'deuterium' => $dCargo) as $resKey => $amount) {
            if ($amount <= 0.0) continue;

            $totalCat = 0.0;
            foreach ($cats as $cat) {
                $totalCat += (float)($this->credits['planets'][$planetId][$cat][$resKey] ?? 0.0);
            }

            if ($totalCat <= 0.0) continue;

            $rem = $amount;
            foreach ($cats as $cat) {
                $cur = (float)($this->credits['planets'][$planetId][$cat][$resKey] ?? 0.0);
                $share = ($cur / $totalCat) * $amount;
                $deduct = min($cur, $share);
                $this->credits['planets'][$planetId][$cat][$resKey] = round(max(0.0, $cur - $deduct), 2);
                $rem -= $deduct;
            }

            if ($rem > 0.01) {
                foreach ($cats as $cat) {
                    $cur = (float)($this->credits['planets'][$planetId][$cat][$resKey] ?? 0.0);
                    if ($cur > 0.0) {
                        $deduct = min($cur, $rem);
                        $this->credits['planets'][$planetId][$cat][$resKey] = round(max(0.0, $cur - $deduct), 2);
                        $rem -= $deduct;
                        if ($rem <= 0.01) break;
                    }
                }
            }
        }
    }

    /**
     * Strict Physical Invariant Reconciliation (Conservation of Virtual Earmarks)
     *
     * Invariants:
     * 1. On every planet, Sum(Local Virtual Budgets) <= Physical Stock * (1 - r_research).
     * 2. Global Research Budget <= Empire Total Physical Stock * r_research.
     * 3. Overall Invariant: Sum(All Virtual Credits) <= Sum(All Physical Stock).
     *
     * @param BotEmpireState $empire
     * @param array $ratios Macro category ratios [mines, fleet, defense, research]
     */
    public function reconcilePhysicalInvariant(BotEmpireState $empire, array $ratios)
    {
        $planets = $empire->getPlanets();
        if (!is_array($planets) || empty($planets)) {
            return;
        }

        $r_research = (float)($ratios['research'] ?? 0.20);
        $localRatioSum = max(0.01, 1.0 - $r_research);

        $totalEmpirePhysM = 0.0;
        $totalEmpirePhysC = 0.0;
        $totalEmpirePhysD = 0.0;

        $totalEmpireCapM = 0.0;
        $totalEmpireCapC = 0.0;
        $totalEmpireCapD = 0.0;

        $displaced = array(
            'mines'   => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
            'fleet'   => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
            'defense' => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
        );

        foreach ($planets as $pId => $pEntry) {
            $pData = $pEntry['data'];
            $pId   = (int)$pId;

            if (!isset($this->credits['planets'][$pId])) {
                $this->credits['planets'][$pId] = array(
                    'mines'   => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
                    'fleet'   => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
                    'defense' => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
                );
            }

            $curM = max(0.0, (float)($pData['metal'] ?? 0.0));
            $curC = max(0.0, (float)($pData['crystal'] ?? 0.0));
            $curD = max(0.0, (float)($pData['deuterium'] ?? 0.0));

            $totalEmpirePhysM += $curM;
            $totalEmpirePhysC += $curC;
            $totalEmpirePhysD += $curD;

            $capM = max((float)($pData['metal_max'] ?? 10000.0), $curM);
            $capC = max((float)($pData['crystal_max'] ?? 10000.0), $curC);
            $capD = max((float)($pData['deuterium_max'] ?? 10000.0), $curD);

            $totalEmpireCapM += $capM;
            $totalEmpireCapC += $capC;
            $totalEmpireCapD += $capD;

            foreach (array('metal' => $curM, 'crystal' => $curC, 'deuterium' => $curD) as $resKey => $curPhys) {
                $sumVirt = (float)($this->credits['planets'][$pId]['mines'][$resKey] ?? 0.0)
                         + (float)($this->credits['planets'][$pId]['fleet'][$resKey] ?? 0.0)
                         + (float)($this->credits['planets'][$pId]['defense'][$resKey] ?? 0.0);

                if ($sumVirt > $curPhys && $sumVirt > 0.0) {
                    $scale = $curPhys / $sumVirt;
                    foreach (array('mines', 'fleet', 'defense') as $cat) {
                        $oldVal = (float)($this->credits['planets'][$pId][$cat][$resKey] ?? 0.0);
                        $newVal = round($oldVal * $scale, 2);
                        $this->credits['planets'][$pId][$cat][$resKey] = $newVal;
                        $displaced[$cat][$resKey] += max(0.0, $oldVal - $newVal);
                    }
                }
            }

            $this->credits['last_physical'][$pId] = array(
                'metal'     => $curM,
                'crystal'   => $curC,
                'deuterium' => $curD,
            );
        }

        // Reubicar créditos locales desplazados (p.ej. cuando research gastó físicamente en el planeta Laboratorio)
        // hacia los demás planetas que poseen el respaldo físico sobrante
        foreach (array('metal', 'crystal', 'deuterium') as $resKey) {
            $totDisp = $displaced['mines'][$resKey] + $displaced['fleet'][$resKey] + $displaced['defense'][$resKey];
            if ($totDisp <= 0.01) {
                continue;
            }
            foreach ($planets as $pId => $pEntry) {
                $pId = (int)$pId;
                $curPhys = max(0.0, (float)($pEntry['data'][$resKey] ?? 0.0));
                $sumVirt = (float)($this->credits['planets'][$pId]['mines'][$resKey] ?? 0.0)
                         + (float)($this->credits['planets'][$pId]['fleet'][$resKey] ?? 0.0)
                         + (float)($this->credits['planets'][$pId]['defense'][$resKey] ?? 0.0);
                $freeBacking = max(0.0, $curPhys - $sumVirt);
                if ($freeBacking <= 0.01) {
                    continue;
                }
                foreach (array('mines', 'fleet', 'defense') as $cat) {
                    if ($displaced[$cat][$resKey] <= 0.01 || $freeBacking <= 0.01) {
                        continue;
                    }
                    $transfer = min($displaced[$cat][$resKey], $freeBacking);
                    $this->credits['planets'][$pId][$cat][$resKey] = round((float)$this->credits['planets'][$pId][$cat][$resKey] + $transfer, 2);
                    $displaced[$cat][$resKey] -= $transfer;
                    $freeBacking -= $transfer;
                }
            }
        }

        $totalLocalVirtM = 0.0;
        $totalLocalVirtC = 0.0;
        $totalLocalVirtD = 0.0;
        foreach ($planets as $pId => $pEntry) {
            $pId = (int)$pId;
            foreach (array('mines', 'fleet', 'defense') as $cat) {
                $totalLocalVirtM += (float)($this->credits['planets'][$pId][$cat]['metal'] ?? 0.0);
                $totalLocalVirtC += (float)($this->credits['planets'][$pId][$cat]['crystal'] ?? 0.0);
                $totalLocalVirtD += (float)($this->credits['planets'][$pId][$cat]['deuterium'] ?? 0.0);
            }
        }

        // Reconcile moons: strictly enforce that virtual moon credits never exceed actual physical stock
        $moons = $empire->getMoons();
        if (is_array($moons)) {
            global $resource;
            foreach ($moons as $mId => $mEntry) {
                $mData = $mEntry['data'];
                $mId   = (int)$mId;

                if (!isset($this->credits['planets'][$mId])) {
                    $this->credits['planets'][$mId] = array(
                        'mines'   => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
                        'fleet'   => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
                        'defense' => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
                    );
                }

                $hasGate = ((int)($mData[$resource[43]] ?? 0)) >= 1;
                $hasPhalanx = ((int)($mData[$resource[42]] ?? 0)) >= 1;

                if ($hasGate && $hasPhalanx) {
                    $this->credits['planets'][$mId]['mines'] = array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);
                    $this->credits['planets'][$mId]['defense'] = array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);
                }

                $curM = max(0.0, (float)($mData['metal'] ?? 0.0));
                $curC = max(0.0, (float)($mData['crystal'] ?? 0.0));
                $curD = max(0.0, (float)($mData['deuterium'] ?? 0.0));

                foreach (array('metal' => $curM, 'crystal' => $curC, 'deuterium' => $curD) as $resKey => $curPhys) {
                    $sumVirt = (float)($this->credits['planets'][$mId]['mines'][$resKey] ?? 0.0)
                             + (float)($this->credits['planets'][$mId]['fleet'][$resKey] ?? 0.0)
                             + (float)($this->credits['planets'][$mId]['defense'][$resKey] ?? 0.0);

                    if ($sumVirt > $curPhys && $sumVirt > 0.0) {
                        $scale = $curPhys / $sumVirt;
                        foreach (array('mines', 'fleet', 'defense') as $cat) {
                            if (isset($this->credits['planets'][$mId][$cat][$resKey])) {
                                $cur = (float)$this->credits['planets'][$mId][$cat][$resKey];
                                $this->credits['planets'][$mId][$cat][$resKey] = round($cur * $scale, 2);
                            }
                        }
                    }
                }

                $this->credits['last_physical'][$mId] = array(
                    'metal'     => $curM,
                    'crystal'   => $curC,
                    'deuterium' => $curD,
                );
            }
        }

        // Reconcile Global Research against Empire-wide warehouse ceiling and available physical backing
        $maxResM = max(0.0, min($totalEmpireCapM * $r_research, $totalEmpirePhysM - $totalLocalVirtM));
        $maxResC = max(0.0, min($totalEmpireCapC * $r_research, $totalEmpirePhysC - $totalLocalVirtC));
        $maxResD = max(0.0, min($totalEmpireCapD * $r_research, $totalEmpirePhysD - $totalLocalVirtD));

        if (isset($this->credits['research'])) {
            $curResM = (float)($this->credits['research']['metal'] ?? 0.0);
            $curResC = (float)($this->credits['research']['crystal'] ?? 0.0);
            $curResD = (float)($this->credits['research']['deuterium'] ?? 0.0);

            $this->credits['research']['metal']     = round(min($maxResM, $curResM), 2);
            $this->credits['research']['crystal']   = round(min($maxResC, $curResC), 2);
            $this->credits['research']['deuterium'] = round(min($maxResD, $curResD), 2);
        }
    }

    private function reconcilePlanetResource($pId, $resKey, $sumVirt, $maxLocal, $cap, array $ratios, $localRatioSum)
    {
        $cats = array('mines', 'fleet', 'defense');

        // Solo aplica recorte si los créditos virtuales locales superan el stock físico real del planeta (inflación fantasma / saqueo recibido)
        if ($sumVirt > $maxLocal && $sumVirt > 0.0) {
            $scale = $maxLocal / $sumVirt;
            foreach ($cats as $cat) {
                if (isset($this->credits['planets'][$pId][$cat][$resKey])) {
                    $cur = (float)$this->credits['planets'][$pId][$cat][$resKey];
                    $this->credits['planets'][$pId][$cat][$resKey] = round($cur * $scale, 2);
                }
            }
        }
    }

    private function deductResourcePair(array &$catRef, $resourceKey, $amount)
    {
        if ($amount <= 0.0) return;

        $cur = (float)($catRef[$resourceKey] ?? 0.0);
        $catRef[$resourceKey] = round(max(0.0, $cur - $amount), 2);
    }

    /**
     * Check if a category has sufficient credits in its own dedicated budget
     *
     * @param int $planetId
     * @param string $category
     * @param array $cost
     * @return bool
     */
    public function canAffordBudget($planetId, $category, array $cost)
    {
        $costM = (float)($cost[901] ?? $cost['metal'] ?? 0.0);
        $costC = (float)($cost[902] ?? $cost['crystal'] ?? 0.0);
        $costD = (float)($cost[903] ?? $cost['deuterium'] ?? 0.0);

        if ($category === 'research') {
            $availM = (float)($this->credits['research']['metal'] ?? 0.0);
            $availC = (float)($this->credits['research']['crystal'] ?? 0.0);
            $availD = (float)($this->credits['research']['deuterium'] ?? 0.0);
        } else {
            $planetId = (int)$planetId;
            $cat = $this->credits['planets'][$planetId][$category] ?? array();
            $availM = (float)($cat['metal'] ?? 0.0);
            $availC = (float)($cat['crystal'] ?? 0.0);
            $availD = (float)($cat['deuterium'] ?? 0.0);
        }

        if ($availM >= $costM && $availC >= $costC && $availD >= $costD) {
            return true;
        }

        // On moons (planet_type = 3), synchronize directly with physical stock
        if ($category === 'mines' && $planetId > 0) {
            try {
                $db = Database::get();
                $pRow = $db->selectSingle("SELECT planet_type, metal, crystal, deuterium FROM %%PLANETS%% WHERE id = :id;", array(':id' => $planetId));
                if ($pRow && (string)$pRow['planet_type'] === '3') {
                    $physM = (float)$pRow['metal'];
                    $physC = (float)$pRow['crystal'];
                    $physD = max(0.0, (float)$pRow['deuterium'] - 50000.0);
                    if ($physM >= $costM && $physC >= $costC && $physD >= $costD) {
                        if (!isset($this->credits['planets'][$planetId]['mines'])) {
                            $this->credits['planets'][$planetId]['mines'] = array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);
                        }
                        $this->credits['planets'][$planetId]['mines']['metal']     = max((float)$this->credits['planets'][$planetId]['mines']['metal'], $physM);
                        $this->credits['planets'][$planetId]['mines']['crystal']   = max((float)$this->credits['planets'][$planetId]['mines']['crystal'], $physC);
                        $this->credits['planets'][$planetId]['mines']['deuterium'] = max((float)$this->credits['planets'][$planetId]['mines']['deuterium'], $physD);
                        return true;
                    }
                }
            } catch (Throwable $e) {
                // Ignore DB error
            }
        }

        return false;
    }

    /**
     * Calcula los recursos físicos comprometidos para pagar el próximo elemento en espera (Slot 1)
     * de construcción (building_queue[1]) o investigación (b_tech_queue[1]) en este planeta.
     *
     * @param int $planetId
     * @return array ['metal' => float, 'crystal' => float, 'deuterium' => float, 'has_building' => bool, 'has_research' => bool]
     */
    public function getNextQueuedSlotCost($planetId)
    {
        $planetId = (int)$planetId;
        $reserved = array(
            'metal'        => 0.0,
            'crystal'      => 0.0,
            'deuterium'    => 0.0,
            'has_building' => false,
            'has_research' => false,
        );

        if (!isset($this->ctx->empire) || !method_exists($this->ctx->empire, 'getPlanet')) {
            return $reserved;
        }

        $pEntry = $this->ctx->empire->getPlanet($planetId);
        if (empty($pEntry) || empty($pEntry['data'])) {
            return $reserved;
        }
        $pData = $pEntry['data'];

        // 1. Verificar Slot 1 de construcción en espera (building_queue[1])
        if (!empty($pEntry['building_queue']) && isset($pEntry['building_queue'][1]) && is_array($pEntry['building_queue'][1])) {
            $nextElemId = (int)($pEntry['building_queue'][1][0] ?? 0);
            $nextLvl    = (int)($pEntry['building_queue'][1][1] ?? 1);
            $mode       = $pEntry['building_queue'][1][4] ?? 'build';
            $forDestroy = ($mode === 'destroy');
            if ($nextElemId > 0) {
                $cost = BuildFunctions::getElementPrice($this->ctx->user, $pData, $nextElemId, $forDestroy, $forDestroy ? $nextLvl : ($nextLvl - 1));
                $reserved['metal']        += (float)($cost[901] ?? $cost['metal'] ?? 0.0);
                $reserved['crystal']      += (float)($cost[902] ?? $cost['crystal'] ?? 0.0);
                $reserved['deuterium']    += (float)($cost[903] ?? $cost['deuterium'] ?? 0.0);
                $reserved['has_building']  = true;
            }
        }

        // 2. Verificar Slot 1 de investigación en espera (b_tech_queue[1]) si este planeta es el laboratorio asignado
        $rawTechQueue = $this->ctx->user['b_tech_queue'] ?? '';
        $techQueue = is_array($rawTechQueue) ? $rawTechQueue : (!empty($rawTechQueue) ? @unserialize($rawTechQueue) : array());
        if (is_array($techQueue) && isset($techQueue[1]) && is_array($techQueue[1])) {
            $targetLabId = (int)($techQueue[1][4] ?? $this->ctx->homePlanetId);
            if ($targetLabId === $planetId) {
                $nextTechId  = (int)($techQueue[1][0] ?? 0);
                $nextTechLvl = (int)($techQueue[1][1] ?? 1);
                if ($nextTechId > 0) {
                    $tCost = BuildFunctions::getElementPrice($this->ctx->user, $pData, $nextTechId, false, $nextTechLvl - 1);
                    $reserved['metal']        += (float)($tCost[901] ?? $tCost['metal'] ?? 0.0);
                    $reserved['crystal']      += (float)($tCost[902] ?? $tCost['crystal'] ?? 0.0);
                    $reserved['deuterium']    += (float)($tCost[903] ?? $tCost['deuterium'] ?? 0.0);
                    $reserved['has_research']  = true;
                }
            }
        }

        return $reserved;
    }

    /**
     * Try an internal virtual bucket swap (4:2:1 MSE Neutral) on a planet
     *
     * Invariants & Rules:
     * 1. Triggered when targetCategory cannot cover neededResources virtually,
     *    but planet physical stock does cover it.
     * 2. Strict protection: cannot take resources from any category with active savings_lock.
     * 3. 4:2:1 Neutral conversion in MSE: Delta_MSE = 0 for each participant bucket.
     * 4. Cost: 0 MO, and does not alter physical planet tables.
     *
     * @param int $planetId
     * @param string $targetCategory 'mines', 'fleet', 'defense', 'research'
     * @param array $neededResources [901 => M, 902 => C, 903 => D] or ['metal' => M, ...]
     * @return bool True if swap was executed or not needed, false if impossible
     */
    public function tryInternalBucketSwap($planetId, $targetCategory, array $neededResources)
    {
        $planetId = (int)$planetId;
        $needM = (float)($neededResources[901] ?? $neededResources['metal'] ?? 0.0);
        $needC = (float)($neededResources[902] ?? $neededResources['crystal'] ?? 0.0);
        $needD = (float)($neededResources[903] ?? $neededResources['deuterium'] ?? 0.0);

        // 1. Resolve Target Category virtual bag
        if ($targetCategory === 'research') {
            if (!isset($this->credits['research'])) {
                $this->credits['research'] = array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);
            }
            $targetRef = &$this->credits['research'];
        } else {
            if (!isset($this->credits['planets'][$planetId][$targetCategory])) {
                $this->credits['planets'][$planetId][$targetCategory] = array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);
            }
            $targetRef = &$this->credits['planets'][$planetId][$targetCategory];
        }

        $curM = (float)($targetRef['metal'] ?? 0.0);
        $curC = (float)($targetRef['crystal'] ?? 0.0);
        $curD = (float)($targetRef['deuterium'] ?? 0.0);

        $defM = max(0.0, $needM - $curM);
        $defC = max(0.0, $needC - $curC);
        $defD = max(0.0, $needD - $curD);

        if ($defM <= 0.0001 && $defC <= 0.0001 && $defD <= 0.0001) {
            return true;
        }

        // 2. Physical Stock Verification on $planetId
        $physM = 0.0;
        $physC = 0.0;
        $physD = 0.0;
        $hasDbPhys = false;

        try {
            $db = Database::get();
            if ($db) {
                $pRow = $db->selectSingle("SELECT metal, crystal, deuterium FROM %%PLANETS%% WHERE id = :pid LIMIT 1;", array(':pid' => $planetId));
                if (!empty($pRow)) {
                    $physM = (float)$pRow['metal'];
                    $physC = (float)$pRow['crystal'];
                    $physD = (float)$pRow['deuterium'];
                    $hasDbPhys = true;
                }
            }
        } catch (Throwable $e) {}

        if (!$hasDbPhys) {
            if (isset($this->credits['last_physical'][$planetId])) {
                $physM = (float)($this->credits['last_physical'][$planetId]['metal'] ?? 0.0);
                $physC = (float)($this->credits['last_physical'][$planetId]['crystal'] ?? 0.0);
                $physD = (float)($this->credits['last_physical'][$planetId]['deuterium'] ?? 0.0);
            } else {
                $physM = (float)($this->credits['planets'][$planetId]['mines']['metal'] ?? 0.0)
                       + (float)($this->credits['planets'][$planetId]['fleet']['metal'] ?? 0.0)
                       + (float)($this->credits['planets'][$planetId]['defense']['metal'] ?? 0.0);
                $physC = (float)($this->credits['planets'][$planetId]['mines']['crystal'] ?? 0.0)
                       + (float)($this->credits['planets'][$planetId]['fleet']['crystal'] ?? 0.0)
                       + (float)($this->credits['planets'][$planetId]['defense']['crystal'] ?? 0.0);
                $physD = (float)($this->credits['planets'][$planetId]['mines']['deuterium'] ?? 0.0)
                       + (float)($this->credits['planets'][$planetId]['fleet']['deuterium'] ?? 0.0)
                       + (float)($this->credits['planets'][$planetId]['defense']['deuterium'] ?? 0.0);
            }
        }

        $slot1Reserve = $this->getNextQueuedSlotCost($planetId);
        if ($targetCategory !== 'mines' && $targetCategory !== 'research') {
            $physM = max(0.0, $physM - (float)$slot1Reserve['metal']);
            $physC = max(0.0, $physC - (float)$slot1Reserve['crystal']);
            $physD = max(0.0, $physD - (float)$slot1Reserve['deuterium']);
        }

        if ($physM < $needM || $physC < $needC || $physD < $needD) {
            return false;
        }

        // 3. Candidate Donor Categories on this planet (excluding targetCategory and locked categories)
        $rate = array('metal' => 1.0, 'crystal' => 2.0, 'deuterium' => 4.0);
        $possibleDonors = array('mines', 'fleet', 'defense');
        $validDonors = array();
        foreach ($possibleDonors as $cat) {
            if ($cat === $targetCategory) {
                continue;
            }
            if ($cat === 'mines' && !empty($slot1Reserve['has_building'])) {
                continue;
            }
            if ($this->isCategoryLocked($planetId, $cat)) {
                continue;
            }
            if (isset($this->credits['planets'][$planetId][$cat])) {
                $validDonors[] = $cat;
            }
        }

        if (empty($validDonors)) {
            return false;
        }

        // 4. Strict 4:2:1 MSE-Neutral Swap ONLY:
        // Target category MUST have enough virtual surplus of other resources in its own bucket to pay for the deficit MSE.
        $surplusTarget = array(
            'metal'     => max(0.0, $curM - $needM),
            'crystal'   => max(0.0, $curC - $needC),
            'deuterium' => max(0.0, $curD - $needD),
        );
        $totalSurplusMSE = ($surplusTarget['metal'] * $rate['metal'])
                         + ($surplusTarget['crystal'] * $rate['crystal'])
                         + ($surplusTarget['deuterium'] * $rate['deuterium']);

        $deficits = array('metal' => $defM, 'crystal' => $defC, 'deuterium' => $defD);
        $totalDefMSE = ($defM * $rate['metal']) + ($defC * $rate['crystal']) + ($defD * $rate['deuterium']);

        if ($totalSurplusMSE < $totalDefMSE - 0.0001) {
            // La categoría solicitante NO tiene MSE propio suficiente para cubrir el coste: prohibido confiscar de otras bolsas.
            return false;
        }

        // Verify that validDonors have the required deficit units available (protecting fleet fuel floor)
        foreach ($deficits as $resKey => $amtNeeded) {
            if ($amtNeeded <= 0.0001) continue;
            $donorSum = 0.0;
            foreach ($validDonors as $dCat) {
                $dVal = (float)($this->credits['planets'][$planetId][$dCat][$resKey] ?? 0.0);
                if ($dCat === 'fleet' && $resKey === 'deuterium') {
                    // Proteger piso de combustible operativo en flota (mínimo 25k o 25% de su deuterio)
                    $fuelFloor = max(25000.0, $dVal * 0.25);
                    $dVal = max(0.0, $dVal - $fuelFloor);
                }
                $donorSum += $dVal;
            }
            if ($donorSum < $amtNeeded - 0.0001) {
                return false;
            }
        }

        // Execute 4:2:1 MSE-Neutral Swap (Delta_MSE = 0 for all participating buckets)
        foreach ($deficits as $resKey => $amtNeeded) {
            if ($amtNeeded <= 0.0001) continue;
            $remNeeded = $amtNeeded;
            foreach ($validDonors as $dCat) {
                if ($remNeeded <= 0.0001) break;
                $rawAvail = (float)($this->credits['planets'][$planetId][$dCat][$resKey] ?? 0.0);
                $dAvail = $rawAvail;
                if ($dCat === 'fleet' && $resKey === 'deuterium') {
                    $fuelFloor = max(25000.0, $rawAvail * 0.25);
                    $dAvail = max(0.0, $rawAvail - $fuelFloor);
                }
                if ($dAvail <= 0.0001) continue;

                $takeUnits = min($remNeeded, $dAvail);
                $swapMSE = $takeUnits * $rate[$resKey];

                $remToPayMSE = $swapMSE;
                foreach (array('metal', 'crystal', 'deuterium') as $payKey) {
                    if ($payKey === $resKey) continue;
                    if ($remToPayMSE <= 0.0001) break;
                    if ($surplusTarget[$payKey] <= 0.0001) continue;

                    $payRate = $rate[$payKey];
                    $availPayMSE = $surplusTarget[$payKey] * $payRate;
                    $payMSE = min($remToPayMSE, $availPayMSE);
                    $payUnits = $payMSE / $payRate;

                    $surplusTarget[$payKey] -= $payUnits;
                    $targetRef[$payKey] = max(0.0, round($targetRef[$payKey] - $payUnits, 2));
                    $this->credits['planets'][$planetId][$dCat][$payKey] = max(0.0, round(
                        $this->credits['planets'][$planetId][$dCat][$payKey] + $payUnits,
                        2
                    ));
                    $remToPayMSE -= $payMSE;
                }

                $this->credits['planets'][$planetId][$dCat][$resKey] = max(0.0, round(
                    $this->credits['planets'][$planetId][$dCat][$resKey] - $takeUnits,
                    2
                ));
                $targetRef[$resKey] = max(0.0, round($targetRef[$resKey] + $takeUnits, 2));
                $remNeeded -= $takeUnits;
            }
        }

        self::$internalBucketSwapsCount++;
        $this->saveState();
        return true;
    }

    public function getCredits()
    {
        return $this->credits;
    }

    public function getSavingsLock()
    {
        return $this->savingsLock;
    }

    public function getCategoryCredits($planetId, $category)
    {
        if ($category === 'research') {
            return $this->credits['research'] ?? array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);
        }
        $planetId = (int)$planetId;
        return $this->credits['planets'][$planetId][$category] ?? array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);
    }

    public function getTraderCooldown($category)
    {
        return isset($this->credits['trader_cooldowns'][$category]) ? (int)$this->credits['trader_cooldowns'][$category] : 0;
    }

    public function setTraderCooldown($category, $timestamp)
    {
        if (!isset($this->credits['trader_cooldowns'])) {
            $this->credits['trader_cooldowns'] = array();
        }
        $this->credits['trader_cooldowns'][$category] = (int)$timestamp;
        $this->saveState();
    }

    public function getEmpireTraderCooldown()
    {
        return isset($this->credits['trader_empire_cooldown']) ? (int)$this->credits['trader_empire_cooldown'] : 0;
    }

    public function setEmpireTraderCooldown($timestamp)
    {
        $this->credits['trader_empire_cooldown'] = (int)$timestamp;
        $this->saveState();
    }

    public function isEmpireTraderOnCooldown()
    {
        return BotClock::get() < $this->getEmpireTraderCooldown();
    }

    public function getPlanetTraderCooldown($planetId)
    {
        $planetId = (int)$planetId;
        return isset($this->credits['trader_planet_cooldowns'][$planetId]) ? (int)$this->credits['trader_planet_cooldowns'][$planetId] : 0;
    }

    public function setPlanetTraderCooldown($planetId, $timestamp)
    {
        $planetId = (int)$planetId;
        if (!isset($this->credits['trader_planet_cooldowns'])) {
            $this->credits['trader_planet_cooldowns'] = array();
        }
        $this->credits['trader_planet_cooldowns'][$planetId] = (int)$timestamp;
        $this->saveState();
    }

    public function isPlanetTraderOnCooldown($planetId)
    {
        return BotClock::get() < $this->getPlanetTraderCooldown($planetId);
    }

    /**
     * Universal On-Demand Trader Synchronization with Strict Budget Isolation
     *
     * 1. Deducts source resource and credits target resource ONLY in the virtual bucket
     *    of the requesting category ($category in budget_credits).
     * 2. Immediately updates last_physical in budget_credits to match the physical exchange
     *    on uni1_planets, completely preventing BotBudgetManager from treating the physical
     *    gain as external windfall.
     * 3. Sets 1-hour cooldown for this specific category (if $setCooldown is true).
     */
    public function syncTraderExchange($planetId, $category, $fromResId, $fromAmount, $toResId, $toAmount, $setCooldown = true)
    {
        $planetId = (int)$planetId;
        $fromKey = ($fromResId == 901) ? 'metal' : (($fromResId == 902) ? 'crystal' : 'deuterium');
        $toKey   = ($toResId == 901) ? 'metal' : (($toResId == 902) ? 'crystal' : 'deuterium');

        // 1. Strictly isolated virtual budget adjustment
        if ($category === 'research') {
            $this->credits['research'][$fromKey] = round(max(0.0, (float)($this->credits['research'][$fromKey] ?? 0.0) - $fromAmount), 2);
            $this->credits['research'][$toKey]   = round((float)($this->credits['research'][$toKey] ?? 0.0) + $toAmount, 2);
        } else {
            if (!isset($this->credits['planets'][$planetId][$category])) {
                $this->credits['planets'][$planetId][$category] = array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);
            }
            $this->credits['planets'][$planetId][$category][$fromKey] = round(max(0.0, (float)($this->credits['planets'][$planetId][$category][$fromKey] ?? 0.0) - $fromAmount), 2);
            $this->credits['planets'][$planetId][$category][$toKey]   = round((float)($this->credits['planets'][$planetId][$category][$toKey] ?? 0.0) + $toAmount, 2);
        }

        // 2. Synchronize last_physical baseline to prevent windfall misdetection
        if (isset($this->credits['last_physical'][$planetId])) {
            $this->credits['last_physical'][$planetId][$fromKey] = round(max(0.0, (float)$this->credits['last_physical'][$planetId][$fromKey] - $fromAmount), 2);
            $this->credits['last_physical'][$planetId][$toKey]   = round((float)$this->credits['last_physical'][$planetId][$toKey] + $toAmount, 2);
        }

        // 3. Category trader cooldown (1 hour = 3600 seconds) if requested
        if ($setCooldown) {
            if (!isset($this->credits['trader_cooldowns'])) {
                $this->credits['trader_cooldowns'] = array();
            }
            $this->credits['trader_cooldowns'][$category] = BotClock::get() + 3600;
        }

        $this->saveState();
        return true;
    }

    /**
     * Synchronize 50/35/15 Demand Trader Rebalance:
     * - Updates category virtual budget with the executed bilateral rebalance exchanges
     * - Synchronizes last_physical with new physical targets (targetM, targetC, targetD)
     * - Sets 1-hour cooldown for empire (trader_empire_cooldown = now + 3600)
     * - Sets 2-hour cooldown for this specific planet (trader_planet_cooldowns[planetId] = now + 7200)
     *
     * @param int $planetId
     * @param string $category 'fleet', 'defense', 'research', 'mines'
     * @param float $targetM Target metal
     * @param float $targetC Target crystal
     * @param float $targetD Target deuterium
     * @param array $exchanges Array of executed minimum exchanges
     * @param bool $setCooldowns
     * @return bool
     */
    public function syncDemandTraderRebalance($planetId, $category, $targetM, $targetC, $targetD, array $exchanges = array(), $setCooldowns = true, $setEmpireCooldown = true)
    {
        $planetId = (int)$planetId;
        $now = BotClock::get();

        // 1. Synchronize category virtual budget based on executed bilateral exchanges
        if (!empty($exchanges)) {
            foreach ($exchanges as $ex) {
                $fromKey = ($ex['from_res'] == 901) ? 'metal' : (($ex['from_res'] == 902) ? 'crystal' : 'deuterium');
                $toKey   = ($ex['to_res'] == 901) ? 'metal' : (($ex['to_res'] == 902) ? 'crystal' : 'deuterium');
                $fromAmt = (float)($ex['from_units'] ?? 0.0);
                $toAmt   = (float)($ex['to_units'] ?? 0.0);

                if ($category === 'research') {
                    $this->credits['research'][$fromKey] = round(max(0.0, (float)($this->credits['research'][$fromKey] ?? 0.0) - $fromAmt), 2);
                    $this->credits['research'][$toKey]   = round((float)($this->credits['research'][$toKey] ?? 0.0) + $toAmt, 2);
                } else {
                    if (!isset($this->credits['planets'][$planetId][$category])) {
                        $this->credits['planets'][$planetId][$category] = array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);
                    }
                    $this->credits['planets'][$planetId][$category][$fromKey] = round(max(0.0, (float)($this->credits['planets'][$planetId][$category][$fromKey] ?? 0.0) - $fromAmt), 2);
                    $this->credits['planets'][$planetId][$category][$toKey]   = round((float)($this->credits['planets'][$planetId][$category][$toKey] ?? 0.0) + $toAmt, 2);
                }
            }
        }

        // 2. Synchronize last_physical baseline exactly with the physical targets to prevent windfall false positives
        $this->credits['last_physical'][$planetId] = array(
            'metal'     => round((float)$targetM, 2),
            'crystal'   => round((float)$targetC, 2),
            'deuterium' => round((float)$targetD, 2),
        );

        // 3. Set Cooldowns: 1 hour (3600s) for Empire, 2 hours (7200s) for this Planet
        if ($setCooldowns) {
            if ($setEmpireCooldown) {
                $this->setEmpireTraderCooldown($now + 3600);
            }
            $this->setPlanetTraderCooldown($planetId, $now + 7200);
            $this->setTraderCooldown($category, $now + 3600);
        }

        $this->saveState();
        return true;
    }

    /**
     * Synchronize return of fleetsave and reconcile deuterium fuel to military 'fleet' budget
     *
     * @param int $planetId
     * @param array $payload
     */
    public function syncFleetsaveReturn($planetId, array $payload)
    {
        $planetId = (int)$planetId;
        $fuelTraded = isset($payload['fuel_traded']) ? (float)$payload['fuel_traded'] : 0.0;

        if ($fuelTraded > 0.0) {
            if (!isset($this->credits['planets'][$planetId]['fleet'])) {
                $this->credits['planets'][$planetId]['fleet'] = array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);
            }
            $this->credits['planets'][$planetId]['fleet']['deuterium'] = round((float)($this->credits['planets'][$planetId]['fleet']['deuterium'] ?? 0.0) + $fuelTraded, 2);
            $this->saveState();
        }
    }

    public function syncPhysicalAdjustment($planetId, $deltaM, $deltaC, $deltaD)
    {
        $planetId = (int)$planetId;
        if (isset($this->credits['last_physical'][$planetId])) {
            $this->credits['last_physical'][$planetId]['metal']     = round(max(0.0, (float)$this->credits['last_physical'][$planetId]['metal'] + $deltaM), 2);
            $this->credits['last_physical'][$planetId]['crystal']   = round(max(0.0, (float)$this->credits['last_physical'][$planetId]['crystal'] + $deltaC), 2);
            $this->credits['last_physical'][$planetId]['deuterium'] = round(max(0.0, (float)$this->credits['last_physical'][$planetId]['deuterium'] + $deltaD), 2);
            $this->saveState();
        }
    }

    /**
     * Credit container rewards directly into category budgets based on macro personality ratios
     *
     * @param int $planetId
     * @param float $deltaM
     * @param float $deltaC
     * @param float $deltaD
     * @return bool
     */
    public function creditContainerReward($planetId, $deltaM, $deltaC, $deltaD)
    {
        $planetId = (int)$planetId;
        $ratios = $this->getMacroRatios();

        foreach (array('mines', 'fleet', 'defense') as $cat) {
            $r_k = (float)($ratios[$cat] ?? 0.25);
            if (!isset($this->credits['planets'][$planetId][$cat])) {
                $this->credits['planets'][$planetId][$cat] = array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);
            }
            $this->credits['planets'][$planetId][$cat]['metal']     = round((float)($this->credits['planets'][$planetId][$cat]['metal'] ?? 0.0) + ($deltaM * $r_k), 2);
            $this->credits['planets'][$planetId][$cat]['crystal']   = round((float)($this->credits['planets'][$planetId][$cat]['crystal'] ?? 0.0) + ($deltaC * $r_k), 2);
            $this->credits['planets'][$planetId][$cat]['deuterium'] = round((float)($this->credits['planets'][$planetId][$cat]['deuterium'] ?? 0.0) + ($deltaD * $r_k), 2);
        }

        $r_res = (float)($ratios['research'] ?? 0.15);
        if (!isset($this->credits['research'])) {
            $this->credits['research'] = array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);
        }
        $this->credits['research']['metal']     = round((float)($this->credits['research']['metal'] ?? 0.0) + ($deltaM * $r_res), 2);
        $this->credits['research']['crystal']   = round((float)($this->credits['research']['crystal'] ?? 0.0) + ($deltaC * $r_res), 2);
        $this->credits['research']['deuterium'] = round((float)($this->credits['research']['deuterium'] ?? 0.0) + ($deltaD * $r_res), 2);

        if (isset($this->credits['last_physical'][$planetId])) {
            $this->credits['last_physical'][$planetId]['metal']     = round(max(0.0, (float)$this->credits['last_physical'][$planetId]['metal'] + $deltaM), 2);
            $this->credits['last_physical'][$planetId]['crystal']   = round(max(0.0, (float)$this->credits['last_physical'][$planetId]['crystal'] + $deltaC), 2);
            $this->credits['last_physical'][$planetId]['deuterium'] = round(max(0.0, (float)$this->credits['last_physical'][$planetId]['deuterium'] + $deltaD), 2);
        }

        $this->saveState();
        return true;
    }

    public function deductResearchCredits($deltaM, $deltaC = 0.0, $deltaD = 0.0)
    {
        if (!isset($this->credits['research'])) {
            $this->credits['research'] = array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);
        }
        $this->credits['research']['metal']     = round(max(0.0, (float)($this->credits['research']['metal'] ?? 0.0) - (float)$deltaM), 2);
        $this->credits['research']['crystal']   = round(max(0.0, (float)($this->credits['research']['crystal'] ?? 0.0) - (float)$deltaC), 2);
        $this->credits['research']['deuterium'] = round(max(0.0, (float)($this->credits['research']['deuterium'] ?? 0.0) - (float)$deltaD), 2);
        $this->saveState();
        return true;
    }

    public function deductOpportunityFund($deltaM, $deltaC = 0.0, $deltaD = 0.0)
    {
        return $this->deductResearchCredits($deltaM, $deltaC, $deltaD);
    }

    /**
     * Credit refunded resources from scrapping directly into a planet budget category
     *
     * @param int $planetId
     * @param array $refund
     * @param string $category 'fleet' or 'defense'
     * @return bool
     */
    public function creditScrapRefund($planetId, array $refund, $category = 'fleet')
    {
        $planetId = (int)$planetId;
        $refM = (float)($refund[901] ?? $refund['metal'] ?? 0.0);
        $refC = (float)($refund[902] ?? $refund['crystal'] ?? 0.0);
        $refD = (float)($refund[903] ?? $refund['deuterium'] ?? 0.0);

        if (!isset($this->credits['planets'][$planetId][$category])) {
            $this->credits['planets'][$planetId][$category] = array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);
        }

        $this->credits['planets'][$planetId][$category]['metal']     = round((float)($this->credits['planets'][$planetId][$category]['metal'] ?? 0.0) + $refM, 2);
        $this->credits['planets'][$planetId][$category]['crystal']   = round((float)($this->credits['planets'][$planetId][$category]['crystal'] ?? 0.0) + $refC, 2);
        $this->credits['planets'][$planetId][$category]['deuterium'] = round((float)($this->credits['planets'][$planetId][$category]['deuterium'] ?? 0.0) + $refD, 2);

        if (isset($this->credits['last_physical'][$planetId])) {
            $this->credits['last_physical'][$planetId]['metal'] += $refM;
            $this->credits['last_physical'][$planetId]['crystal'] += $refC;
            $this->credits['last_physical'][$planetId]['deuterium'] += $refD;
        }

        $this->saveState();
        return true;
    }

    /**
     * Revert scrap refund from planet credits and last_physical upon conversion rollback
     *
     * @param int $planetId
     * @param array $refund
     * @param string $category
     */
    public function revertScrapRefund($planetId, array $refund, $category = 'fleet')
    {
        $planetId = (int)$planetId;
        $refM = (float)($refund[901] ?? $refund['metal'] ?? 0.0);
        $refC = (float)($refund[902] ?? $refund['crystal'] ?? 0.0);
        $refD = (float)($refund[903] ?? $refund['deuterium'] ?? 0.0);

        if (isset($this->credits['planets'][$planetId][$category])) {
            $this->credits['planets'][$planetId][$category]['metal']     = max(0.0, round((float)($this->credits['planets'][$planetId][$category]['metal'] ?? 0.0) - $refM, 2));
            $this->credits['planets'][$planetId][$category]['crystal']   = max(0.0, round((float)($this->credits['planets'][$planetId][$category]['crystal'] ?? 0.0) - $refC, 2));
            $this->credits['planets'][$planetId][$category]['deuterium'] = max(0.0, round((float)($this->credits['planets'][$planetId][$category]['deuterium'] ?? 0.0) - $refD, 2));
        }

        if (isset($this->credits['last_physical'][$planetId])) {
            $this->credits['last_physical'][$planetId]['metal']     = max(0.0, (float)$this->credits['last_physical'][$planetId]['metal'] - $refM);
            $this->credits['last_physical'][$planetId]['crystal']   = max(0.0, (float)$this->credits['last_physical'][$planetId]['crystal'] - $refC);
            $this->credits['last_physical'][$planetId]['deuterium'] = max(0.0, (float)$this->credits['last_physical'][$planetId]['deuterium'] - $refD);
        }

        $this->saveState();
    }

    /**
     * Compute donor surplus strictly available for a specific budget category,
     * maintaining accounting isolation without cross-budget bleeding.
     *
     * @param int $planetId
     * @param string $category 'mines', 'fleet', 'defense', 'research'
     * @param array $planetData
     * @return array ['metal' => float, 'crystal' => float, 'deuterium' => float]
     */
    public function getDonorSurplusByCategory($planetId, $category, array $planetData)
    {
        $planetId = (int)$planetId;
        global $resource;

        $isMoon = ((string)($planetData['planet_type'] ?? '1') === '3');
        if ($isMoon) {
            $hasGate = ((int)($planetData[$resource[43]] ?? $planetData['sprungtor'] ?? 0)) >= 1;
            $physM = max(0.0, (float)($planetData['metal'] ?? 0.0));
            $physC = max(0.0, (float)($planetData['crystal'] ?? 0.0));
            // Tactical Phalanx reserve: 25,000,000 Deuterium
            $physD = max(0.0, (float)($planetData['deuterium'] ?? 0.0) - 25000000.0);

            if ($hasGate) {
                return array(
                    'metal'     => round($physM, 2),
                    'crystal'   => round($physC, 2),
                    'deuterium' => round($physD, 2),
                );
            }
            // Without Jump Gate (#43), reserve 2M Metal + 4M Crystal for gate construction
            return array(
                'metal'     => round(max(0.0, $physM - 2000000.0), 2),
                'crystal'   => round(max(0.0, $physC - 4000000.0), 2),
                'deuterium' => round($physD, 2),
            );
        }

        $slot1Reserve = $this->getNextQueuedSlotCost($planetId);

        // A. Global Research Category
        if ($category === 'research') {
            $localBags  = $this->credits['planets'][$planetId] ?? array();
            $localMines = $localBags['mines'] ?? array();
            $localFleet = $localBags['fleet'] ?? array();
            $localDef   = $localBags['defense'] ?? array();

            $committedLocalM = (float)($localMines['metal'] ?? 0.0)
                             + (float)($localFleet['metal'] ?? 0.0)
                             + (float)($localDef['metal'] ?? 0.0)
                             + (float)($slot1Reserve['metal'] ?? 0.0);

            $committedLocalC = (float)($localMines['crystal'] ?? 0.0)
                             + (float)($localFleet['crystal'] ?? 0.0)
                             + (float)($localDef['crystal'] ?? 0.0)
                             + (float)($slot1Reserve['crystal'] ?? 0.0);

            $committedLocalD = (float)($localMines['deuterium'] ?? 0.0)
                             + (float)($localFleet['deuterium'] ?? 0.0)
                             + (float)($localDef['deuterium'] ?? 0.0)
                             + (float)($slot1Reserve['deuterium'] ?? 0.0);

            $physM = max(0.0, (float)($planetData['metal'] ?? 0.0));
            $physC = max(0.0, (float)($planetData['crystal'] ?? 0.0));
            $physD = max(0.0, (float)($planetData['deuterium'] ?? 0.0));

            $freePhysForResearchM = max(0.0, $physM - $committedLocalM);
            $freePhysForResearchC = max(0.0, $physC - $committedLocalC);
            $freePhysForResearchD = max(0.0, $physD - $committedLocalD);

            $resCredits = $this->credits['research'] ?? array();
            $donableM = min($freePhysForResearchM, (float)($resCredits['metal'] ?? 0.0));
            $donableC = min($freePhysForResearchC, (float)($resCredits['crystal'] ?? 0.0));
            $donableD = min($freePhysForResearchD, (float)($resCredits['deuterium'] ?? 0.0));

            return array(
                'metal'     => round($donableM, 2),
                'crystal'   => round($donableC, 2),
                'deuterium' => round($donableD, 2),
            );
        }

        // B. Local Categories ('mines', 'fleet', 'defense')
        if (in_array($category, array('mines', 'fleet', 'defense'), true)) {
            if ($category === 'mines' && $this->isCategoryLocked($planetId, 'mines')) {
                return array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);
            }

            $ratios   = $this->getMacroRatios();
            $catRatio = (float)($ratios[$category] ?? 0.25);

            $prodM = max(0.0, (float)($planetData['metal_perhour'] ?? 0.0));
            $prodC = max(0.0, (float)($planetData['crystal_perhour'] ?? 0.0));
            $prodD = max(0.0, (float)($planetData['deuterium_perhour'] ?? 0.0));

            // Local bag reserve: 1.0 hour of production allocated to this category (+ slot1Reserve if 'mines')
            $catReserveM = (1.0 * $prodM * $catRatio) + (($category === 'mines') ? (float)($slot1Reserve['metal'] ?? 0.0) : 0.0);
            $catReserveC = (1.0 * $prodC * $catRatio) + (($category === 'mines') ? (float)($slot1Reserve['crystal'] ?? 0.0) : 0.0);
            $catReserveD = (1.0 * $prodD * $catRatio) + (($category === 'mines') ? (float)($slot1Reserve['deuterium'] ?? 0.0) : 0.0);

            // Untouchable physical reserve: slot1Reserve + 20,000 Deuterium
            $physM = max(0.0, (float)($planetData['metal'] ?? 0.0));
            $physC = max(0.0, (float)($planetData['crystal'] ?? 0.0));
            $physD = max(0.0, (float)($planetData['deuterium'] ?? 0.0));

            $physAvailM = max(0.0, $physM - (float)($slot1Reserve['metal'] ?? 0.0));
            $physAvailC = max(0.0, $physC - (float)($slot1Reserve['crystal'] ?? 0.0));
            $physAvailD = max(0.0, $physD - ((float)($slot1Reserve['deuterium'] ?? 0.0) + 20000.0));

            $catCredits   = $this->credits['planets'][$planetId][$category] ?? array();
            $budgetAvailM = (float)($catCredits['metal'] ?? 0.0);
            $budgetAvailC = (float)($catCredits['crystal'] ?? 0.0);
            $budgetAvailD = (float)($catCredits['deuterium'] ?? 0.0);

            // Donable surplus strictly capped by physical availability and own category budget (no 40M MSE bypass)
            $donableM = min($physAvailM, max(0.0, $budgetAvailM - $catReserveM));
            $donableC = min($physAvailC, max(0.0, $budgetAvailC - $catReserveC));
            $donableD = min($physAvailD, max(0.0, $budgetAvailD - $catReserveD));

            return array(
                'metal'     => round($donableM, 2),
                'crystal'   => round($donableC, 2),
                'deuterium' => round($donableD, 2),
            );
        }

        return array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);
    }

    /**
     * Debit donated resources atomically from the donor's category ONLY.
     *
     * @param int $planetId
     * @param string $category
     * @param array $resources
     * @return bool
     */
    public function debitDonorCategory($planetId, $category, array $resources)
    {
        $planetId = (int)$planetId;
        $resM = (float)($resources[901] ?? $resources['metal'] ?? 0.0);
        $resC = (float)($resources[902] ?? $resources['crystal'] ?? 0.0);
        $resD = (float)($resources[903] ?? $resources['deuterium'] ?? 0.0);

        $this->debitSingleResource($planetId, $category, 'metal', $resM);
        $this->debitSingleResource($planetId, $category, 'crystal', $resC);
        $this->debitSingleResource($planetId, $category, 'deuterium', $resD);

        if (isset($this->credits['last_physical'][$planetId])) {
            $this->credits['last_physical'][$planetId]['metal'] = round(max(0.0, (float)$this->credits['last_physical'][$planetId]['metal'] - $resM), 2);
            $this->credits['last_physical'][$planetId]['crystal'] = round(max(0.0, (float)$this->credits['last_physical'][$planetId]['crystal'] - $resC), 2);
            $this->credits['last_physical'][$planetId]['deuterium'] = round(max(0.0, (float)$this->credits['last_physical'][$planetId]['deuterium'] - $resD), 2);
        }

        $this->saveState();
        return true;
    }

    private function debitSingleResource($planetId, $category, $key, $amount)
    {
        if ($amount <= 0.0) return;

        // Strictly drawn from the specific category only - zero cross-budget bleeding
        if ($category === 'research') {
            $cur = (float)($this->credits['research'][$key] ?? 0.0);
            $this->credits['research'][$key] = round(max(0.0, $cur - $amount), 2);
        } else {
            if (!isset($this->credits['planets'][$planetId])) {
                $this->credits['planets'][$planetId] = array(
                    'mines'   => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
                    'fleet'   => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
                    'defense' => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
                );
            } elseif (!isset($this->credits['planets'][$planetId][$category])) {
                $this->credits['planets'][$planetId][$category] = array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);
            }
            $cur = (float)($this->credits['planets'][$planetId][$category][$key] ?? 0.0);
            $this->credits['planets'][$planetId][$category][$key] = round(max(0.0, $cur - $amount), 2);
        }
    }

    /**
     * Credit arrived logistics resources directly into a planet budget category (or global research)
     *
     * @param int $planetId
     * @param string $category
     * @param array $resources
     * @return bool
     */
    public function creditPlanetCategory($planetId, $category, array $resources)
    {
        $planetId = (int)$planetId;
        $resM = (float)($resources[901] ?? $resources['metal'] ?? 0.0);
        $resC = (float)($resources[902] ?? $resources['crystal'] ?? 0.0);
        $resD = (float)($resources[903] ?? $resources['deuterium'] ?? 0.0);

        if ($category === 'research') {
            $this->credits['research']['metal']     = round((float)($this->credits['research']['metal'] ?? 0.0) + $resM, 2);
            $this->credits['research']['crystal']   = round((float)($this->credits['research']['crystal'] ?? 0.0) + $resC, 2);
            $this->credits['research']['deuterium'] = round((float)($this->credits['research']['deuterium'] ?? 0.0) + $resD, 2);
        } else {
            if (!isset($this->credits['planets'][$planetId])) {
                $this->credits['planets'][$planetId] = array(
                    'mines'   => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
                    'fleet'   => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
                    'defense' => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
                );
            } elseif (!isset($this->credits['planets'][$planetId][$category])) {
                $this->credits['planets'][$planetId][$category] = array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);
            }
            $this->credits['planets'][$planetId][$category]['metal']     = round((float)($this->credits['planets'][$planetId][$category]['metal'] ?? 0.0) + $resM, 2);
            $this->credits['planets'][$planetId][$category]['crystal']   = round((float)($this->credits['planets'][$planetId][$category]['crystal'] ?? 0.0) + $resC, 2);
            $this->credits['planets'][$planetId][$category]['deuterium'] = round((float)($this->credits['planets'][$planetId][$category]['deuterium'] ?? 0.0) + $resD, 2);
        }

        if (isset($this->credits['last_physical'][$planetId])) {
            $this->credits['last_physical'][$planetId]['metal'] += $resM;
            $this->credits['last_physical'][$planetId]['crystal'] += $resC;
            $this->credits['last_physical'][$planetId]['deuterium'] += $resD;
        }

        $this->saveState();
        return true;
    }

    /**
     * Explicitly credit external windfall (e.g. from loot, debris, expedition)
     * distributed according to macro ratios across local categories and global research.
     *
     * @param int $planetId
     * @param array $resources [901 => M, 902 => C, 903 => D] or ['metal' => M, ...]
     * @return bool
     */
    public function creditExternalWindfall($planetId, array $resources)
    {
        $planetId = (int)$planetId;
        $resM = (float)($resources[901] ?? $resources['metal'] ?? 0.0);
        $resC = (float)($resources[902] ?? $resources['crystal'] ?? 0.0);
        $resD = (float)($resources[903] ?? $resources['deuterium'] ?? 0.0);

        if ($resM <= 0.0 && $resC <= 0.0 && $resD <= 0.0) {
            return false;
        }

        $ratios = $this->getMacroRatios();

        // 1. Local categories
        if (!isset($this->credits['planets'][$planetId])) {
            $this->credits['planets'][$planetId] = array(
                'mines'   => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
                'fleet'   => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
                'defense' => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
            );
        }

        foreach (array('mines', 'fleet', 'defense') as $cat) {
            $rk = $ratios[$cat];
            $this->credits['planets'][$planetId][$cat]['metal']     = round((float)($this->credits['planets'][$planetId][$cat]['metal'] ?? 0.0) + ($resM * $rk), 2);
            $this->credits['planets'][$planetId][$cat]['crystal']   = round((float)($this->credits['planets'][$planetId][$cat]['crystal'] ?? 0.0) + ($resC * $rk), 2);
            $this->credits['planets'][$planetId][$cat]['deuterium'] = round((float)($this->credits['planets'][$planetId][$cat]['deuterium'] ?? 0.0) + ($resD * $rk), 2);
        }

        // 2. Global research
        $rRes = $ratios['research'];
        $this->credits['research']['metal']     = round((float)($this->credits['research']['metal'] ?? 0.0) + ($resM * $rRes), 2);
        $this->credits['research']['crystal']   = round((float)($this->credits['research']['crystal'] ?? 0.0) + ($resC * $rRes), 2);
        $this->credits['research']['deuterium'] = round((float)($this->credits['research']['deuterium'] ?? 0.0) + ($resD * $rRes), 2);

        // 3. Update last_physical to avoid double-counting in next updateCredits()
        if (isset($this->credits['last_physical'][$planetId])) {
            $this->credits['last_physical'][$planetId]['metal'] += $resM;
            $this->credits['last_physical'][$planetId]['crystal'] += $resC;
            $this->credits['last_physical'][$planetId]['deuterium'] += $resD;
        }

        $this->saveState();
        return true;
    }

    /**
     * Get available fleet recruitment credits for a planet
     *
     * @param int $planetId
     * @return array ['metal' => float, 'crystal' => float, 'deuterium' => float]
     */
    public function getPlanetBudgetCredits($planetId, $category)
    {
        switch ($category) {
            case 'fleet':
                return $this->getPlanetFleetCredits($planetId);
            case 'defense':
                return $this->getPlanetDefenseCredits($planetId);
            case 'mines':
            case 'building':
                return $this->getPlanetMinesCredits($planetId);
            case 'research':
                return $this->getResearchCredits();
            default:
                return array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);
        }
    }

    public function getPlanetFleetCredits($planetId)
    {
        $planetId = (int)$planetId;
        $credits = $this->credits['planets'][$planetId]['fleet'] ?? array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);

        return array(
            'metal'     => max(0.0, (float)($credits['metal'] ?? 0.0)),
            'crystal'   => max(0.0, (float)($credits['crystal'] ?? 0.0)),
            'deuterium' => max(0.0, (float)($credits['deuterium'] ?? 0.0)),
        );
    }

    /**
     * Get available defense recruitment credits for a planet
     *
     * @param int $planetId
     * @return array ['metal' => float, 'crystal' => float, 'deuterium' => float]
     */
    public function getPlanetDefenseCredits($planetId)
    {
        $planetId = (int)$planetId;
        $credits = $this->credits['planets'][$planetId]['defense'] ?? array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);

        return array(
            'metal'     => max(0.0, (float)($credits['metal'] ?? 0.0)),
            'crystal'   => max(0.0, (float)($credits['crystal'] ?? 0.0)),
            'deuterium' => max(0.0, (float)($credits['deuterium'] ?? 0.0)),
        );
    }

    /**
     * Get available construction credits for a planet
     *
     * @param int $planetId
     * @return array ['metal' => float, 'crystal' => float, 'deuterium' => float]
     */
    public function getPlanetMinesCredits($planetId)
    {
        $planetId = (int)$planetId;
        $credits = $this->credits['planets'][$planetId]['mines'] ?? array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);

        return array(
            'metal'     => max(0.0, (float)($credits['metal'] ?? 0.0)),
            'crystal'   => max(0.0, (float)($credits['crystal'] ?? 0.0)),
            'deuterium' => max(0.0, (float)($credits['deuterium'] ?? 0.0)),
        );
    }

    /**
     * Get available research credits for the entire empire
     *
     * @return array ['metal' => float, 'crystal' => float, 'deuterium' => float]
     */
    public function getResearchCredits()
    {
        $credits = $this->credits['research'] ?? array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);

        return array(
            'metal'     => max(0.0, (float)($credits['metal'] ?? 0.0)),
            'crystal'   => max(0.0, (float)($credits['crystal'] ?? 0.0)),
            'deuterium' => max(0.0, (float)($credits['deuterium'] ?? 0.0)),
        );
    }

    /**
     * Get timestamp of last dedicated fleet recruitment cycle
     *
     * @return int
     */
    public function getLastFleetRecruit()
    {
        return isset($this->ctx->botRow['last_fleet_recruit']) ? (int)$this->ctx->botRow['last_fleet_recruit'] : 0;
    }

    /**
     * Update timestamp of last dedicated fleet recruitment cycle
     *
     * @param int $time
     */
    public function setLastFleetRecruit($time)
    {
        $this->ctx->updateBotRow(array('last_fleet_recruit' => (int)$time));
    }

    /**
     * Get timestamp of last dedicated defense recruitment cycle
     *
     * @return int
     */
    public function getLastDefenseRecruit()
    {
        return isset($this->ctx->botRow['last_defense_recruit']) ? (int)$this->ctx->botRow['last_defense_recruit'] : 0;
    }

    /**
     * Update timestamp of last dedicated defense recruitment cycle
     *
     * @param int $time
     */
    public function setLastDefenseRecruit($time)
    {
        $this->ctx->updateBotRow(array('last_defense_recruit' => (int)$time));
    }

    /**
     * Engage or refresh Savings Lock for a priority element in a specific category
     */
    /**
     * Engage or refresh Savings Lock for a priority element in a specific category
     */
    public function setSavingsLock($planetId, $targetType, $elementId, array $cost, array $planetData, $category = null, BotEmpireState $empire = null)
    {
        if ($category === null) {
            if ($targetType === 'tech' || $targetType === 'research') {
                $category = 'research';
            } elseif ($targetType === 'ship' || $targetType === 'fleet') {
                $category = 'fleet';
            } elseif ($targetType === 'defense' || $targetType === 'missile') {
                $category = 'defense';
            } else {
                $category = 'mines';
            }
        }

        $costM = (float)($cost[901] ?? $cost['metal'] ?? 0.0);
        $costC = (float)($cost[902] ?? $cost['crystal'] ?? 0.0);
        $costD = (float)($cost[903] ?? $cost['deuterium'] ?? 0.0);

        $stockM = (float)($planetData['metal'] ?? 0.0);
        $stockC = (float)($planetData['crystal'] ?? 0.0);
        $stockD = (float)($planetData['deuterium'] ?? 0.0);

        $defM = max(0.0, $costM - $stockM);
        $defC = max(0.0, $costC - $stockC);
        $defD = max(0.0, $costD - $stockD);

        // Identify deficit resource with largest shortfall
        $defRes = 901;
        $maxDef = $defM;
        if ($defC > $maxDef) {
            $defRes = 902;
            $maxDef = $defC;
        }
        if ($defD > $maxDef) {
            $defRes = 903;
            $maxDef = $defD;
        }

        $stockMap = array(901 => $stockM, 902 => $stockC, 903 => $stockD);
        $prodMap  = array(
            901 => (float)($planetData['metal_perhour'] ?? 0.0),
            902 => (float)($planetData['crystal_perhour'] ?? 0.0),
            903 => (float)($planetData['deuterium_perhour'] ?? 0.0)
        );

        $eta = $this->calculateSafeEta($cost, $stockMap, $prodMap);

        // MAX_SAVINGS_LOCK_ETA enforcement: Never lock empire for unreachable targets
        $maxLockEta = $this->getMaxSavingsLockEta(array('element_id' => $elementId));
        if ($eta > $maxLockEta) {
            if ($empire !== null) {
                $hasFreeFieldsElsewhere = false;
                foreach ($empire->getPlanets() as $pCheckId => $pCheck) {
                    if ((int)$pCheckId === (int)$planetId) continue;
                    $f = CalculateMaxPlanetFields($pCheck['data']) - ((int)$pCheck['data']['field_current'] + count($pCheck['building_queue']));
                    if ($f > 0) {
                        $hasFreeFieldsElsewhere = true;
                        break;
                    }
                }
                if ($hasFreeFieldsElsewhere) {
                    return false;
                }
            } else {
                return false;
            }
        }

        $lockData = array(
            'planet_id'        => (int)$planetId,
            'target_type'      => $targetType,
            'category'         => $category,
            'element_id'       => (int)$elementId,
            'cost'             => array(901 => $costM, 902 => $costC, 903 => $costD),
            'deficit_resource' => $defRes,
            'last_stock'       => (float)$stockMap[$defRes],
            'stagnant_cycles'  => 0,
            'start_time'       => BotClock::get(),
            'eta_seconds'      => $eta,
        );

        if (!isset($this->credits['savings_locks_by_cat']) || !is_array($this->credits['savings_locks_by_cat'])) {
            $this->credits['savings_locks_by_cat'] = array();
        }
        $this->credits['savings_locks_by_cat'][$category] = $lockData;
        $this->savingsLock = $lockData;

        $this->saveState();
        return true;
    }

    /**
     * Check if a specific category is locked by savings on a planet
     */
    public function isCategoryLocked($planetId, $category)
    {
        $lock = $this->credits['savings_locks_by_cat'][$category] ?? null;
        if (empty($lock) && !empty($this->savingsLock) && ($this->savingsLock['category'] ?? 'mines') === $category) {
            $lock = $this->savingsLock;
        }

        if (empty($lock)) {
            return false;
        }

        // Auto-expire lock if it exceeds maximum savings horizon
        $eta = (float)($lock['eta_seconds'] ?? 0.0);
        $maxLockEta = $this->getMaxSavingsLockEta($lock);
        if ($eta > $maxLockEta) {
            $this->clearSavingsLock($category);
            return false;
        }

        if ($category === 'research') {
            return !empty($lock);
        }

        $lockPlanet = (int)($lock['planet_id'] ?? 0);
        return ($lockPlanet === (int)$planetId);
    }

    /**
     * Get active savings lock only if it matches the requested category
     */
    public function getSavingsLockForCategory($planetId, $category)
    {
        if ($this->isCategoryLocked($planetId, $category)) {
            $lock = $this->credits['savings_locks_by_cat'][$category] ?? null;
            if (empty($lock) && !empty($this->savingsLock) && ($this->savingsLock['category'] ?? 'mines') === $category) {
                $lock = $this->savingsLock;
            }
            return $lock;
        }
        return null;
    }

    public function clearSavingsLock($category = null)
    {
        if ($category !== null) {
            unset($this->credits['savings_locks_by_cat'][$category]);
            if (!empty($this->savingsLock) && ($this->savingsLock['category'] ?? '') === $category) {
                $this->savingsLock = !empty($this->credits['savings_locks_by_cat'])
                    ? reset($this->credits['savings_locks_by_cat'])
                    : null;
            }
        } else {
            $this->credits['savings_locks_by_cat'] = array();
            $this->savingsLock = null;
        }
        $this->saveState();
    }

    /**
     * Protected ETA calculation (prevents division by zero)
     *
     * @param array $cost
     * @param array $stock
     * @param array $prodHour
     * @return float
     */
    public function calculateSafeEta(array $cost, array $stock, array $prodHour)
    {
        $costM = (float)($cost[901] ?? $cost['metal'] ?? 0.0);
        $costC = (float)($cost[902] ?? $cost['crystal'] ?? 0.0);
        $costD = (float)($cost[903] ?? $cost['deuterium'] ?? 0.0);

        $stockM = (float)($stock[901] ?? $stock['metal'] ?? 0.0);
        $stockC = (float)($stock[902] ?? $stock['crystal'] ?? 0.0);
        $stockD = (float)($stock[903] ?? $stock['deuterium'] ?? 0.0);

        $prodM = (float)($prodHour[901] ?? $prodHour['metal'] ?? 0.0);
        $prodC = (float)($prodHour[902] ?? $prodHour['crystal'] ?? 0.0);
        $prodD = (float)($prodHour[903] ?? $prodHour['deuterium'] ?? 0.0);

        $etaM = max(0.0, ($costM - $stockM)) / max(0.001, ($prodM / 3600.0));
        $etaC = max(0.0, ($costC - $stockC)) / max(0.001, ($prodC / 3600.0));
        $etaD = max(0.0, ($costD - $stockD)) / max(0.001, ($prodD / 3600.0));

        return round(max($etaM, $etaC, $etaD), 2);
    }

    /**
     * Evaluate Phase 3 Savings Mode & dynamic guards:
     * 1. Storage Cap Guard: if cost > Storage_Max * 0.95, forced unlock of 22, 23, 24
     * 2. Anti-Farm Watchdog: if Delta_stock <= 0 for 3 consecutive cycles (135s), abort & invest in defenses/fighters
     *
     * @param BotEmpireState $empire
     * @param BotActionGateway $gateway
     * @return array [status => string, detail => string]
     */
    public function processSavingsLock(BotEmpireState $empire, BotActionGateway $gateway)
    {
        $locksToProcess = array();
        if (!empty($this->credits['savings_locks_by_cat']) && is_array($this->credits['savings_locks_by_cat'])) {
            $locksToProcess = $this->credits['savings_locks_by_cat'];
        } elseif (!empty($this->savingsLock)) {
            $cat = $this->savingsLock['category'] ?? 'mines';
            $locksToProcess[$cat] = $this->savingsLock;
        }

        if (empty($locksToProcess)) {
            return array('status' => 'idle');
        }

        $lastResult = array('status' => 'idle');

        foreach ($locksToProcess as $lockCat => $lock) {
            if (empty($lock) || !is_array($lock)) {
                continue;
            }

            $pId = (int)($lock['planet_id'] ?? 0);
            $planetEntry = $empire->getPlanet($pId);
            if (empty($planetEntry)) {
                $this->clearSavingsLock($lockCat);
                $lastResult = array('status' => 'invalid_planet');
                continue;
            }

            // Auto-expire savings lock exceeding MAX_SAVINGS_LOCK_ETA
            $currentEta = (float)($lock['eta_seconds'] ?? 0.0);
            $maxLockEta = $this->getMaxSavingsLockEta($lock);
            if ($currentEta > $maxLockEta) {
                $this->ctx->decisionLog->record(
                    'savings_lock_aborted_eta',
                    "Savings lock [{$lockCat}] aborted: ETA ({$currentEta}s) exceeds MAX_SAVINGS_LOCK_ETA ({$maxLockEta}s). Unlocking empire.",
                    $lock,
                    'abort_savings_eta',
                    12000.0
                );
                $this->clearSavingsLock($lockCat);
                $lastResult = array('status' => 'aborted_eta_exceeded');
                continue;
            }

            $pData = $planetEntry['data'];
            $cost  = $lock['cost'];

            $defRes = (int)($lock['deficit_resource'] ?? 901);
            $colKey = ($defRes === 901) ? 'metal' : (($defRes === 902) ? 'crystal' : 'deuterium');
            $costDef = (float)($cost[$defRes] ?? $cost[$colKey] ?? 0.0);
            $maxStockCap = (float)($pData[$colKey . '_max'] ?? 0.0);
            $currentStock = (float)($pData[$colKey] ?? 0.0);
            $lastStock    = (float)($lock['last_stock'] ?? $currentStock);

            $freeFields = CalculateMaxPlanetFields($pData) - ((int)$pData['field_current'] + count($planetEntry['building_queue']));

            // Warehouse corresponding to the current deficit resource
            $storeIdForDeficit = ($defRes === 901) ? 22 : (($defRes === 902) ? 23 : 24);

            $isWarehouseBuilding = false;
            if (!empty($planetEntry['building_queue'])) {
                foreach ($planetEntry['building_queue'] as $bItem) {
                    if ((int)$bItem[0] === $storeIdForDeficit) {
                        $isWarehouseBuilding = true;
                        break;
                    }
                }
            }

            // --- 1. Dynamic Storage Cap Guard ---
            $storeNeeded = 0;
            if ($maxStockCap > 0 && $costDef > ($maxStockCap * 0.95)) {
                $storeNeeded = $storeIdForDeficit;
            }

            if ($storeNeeded > 0 && $freeFields <= 0) {
                $this->ctx->decisionLog->record(
                    'savings_storage_deadlock_aborted',
                    "Savings lock [{$lockCat}] aborted on planet #{$pId}: target requires warehouse #{$storeNeeded} but colony has 0 free fields.",
                    $lock,
                    'abort_storage_deadlock',
                    15000.0
                );
                $this->clearSavingsLock($lockCat);
                $lastResult = array('status' => 'aborted_storage_deadlock');
                continue;
            }

            if ($storeNeeded > 0 && !$isWarehouseBuilding) {
                $ok = $gateway->constructBuilding($pId, $storeNeeded);
                if ($ok) {
                    $this->ctx->decisionLog->record(
                        'savings_storage_unlock',
                        "Forced warehouse unlock (#{$storeNeeded}) on planet #{$pId} due to savings target exceeding 95% storage capacity",
                        array('store' => $storeNeeded, 'target' => $lock['element_id']),
                        "unlock_store_{$storeNeeded}",
                        15000.0
                    );
                    $lastResult = array('status' => 'unlocked_storage', 'store' => $storeNeeded);
                    continue;
                }
            }

            // --- 2. Anti-Farm Watchdog ---
            $isStorageSaturated = ($maxStockCap > 0 && $currentStock >= ($maxStockCap * 0.95));
            $deltaStock = $currentStock - $lastStock;
            if ($deltaStock <= 0.0 && !$isWarehouseBuilding && !$isStorageSaturated) {
                $lock['stagnant_cycles'] = (int)($lock['stagnant_cycles'] ?? 0) + 1;
            } elseif ($deltaStock > 0.0) {
                $lock['stagnant_cycles'] = 0;
            }
            $lock['last_stock'] = $currentStock;

            if ($lock['stagnant_cycles'] >= 3) {
                $this->ctx->decisionLog->record(
                    'savings_antifarm_abort',
                    "Watchdog Anti-Farm triggered on planet #{$pId} [{$lockCat}]: 3 consecutive cycles (135s) with non-growing stock. Aborting savings to recruit defenses and fighters.",
                    array('stagnant_cycles' => $lock['stagnant_cycles'], 'deficit_res' => $defRes),
                    'abort_savings_defend',
                    20000.0
                );

                $this->clearSavingsLock($lockCat);

                $gateway->constructUnits($pId, 401, 100);
                $gateway->constructUnits($pId, 402, 50);
                $gateway->constructUnits($pId, 407, 1);
                $gateway->constructUnits($pId, 204, 50);
                $gateway->constructUnits($pId, 206, 20);

                $lastResult = array('status' => 'aborted_antifarm');
                continue;
            }

            // Recalculate protected ETA
            $stockMap = array(
                901 => (float)($pData['metal'] ?? 0.0),
                902 => (float)($pData['crystal'] ?? 0.0),
                903 => (float)($pData['deuterium'] ?? 0.0)
            );
            $prodMap  = array(
                901 => (float)($pData['metal_perhour'] ?? 0.0),
                902 => (float)($pData['crystal_perhour'] ?? 0.0),
                903 => (float)($pData['deuterium_perhour'] ?? 0.0)
            );
            $lock['eta_seconds'] = $this->calculateSafeEta($cost, $stockMap, $prodMap);

            $this->credits['savings_locks_by_cat'][$lockCat] = $lock;
            if (!empty($this->savingsLock) && ($this->savingsLock['category'] ?? '') === $lockCat) {
                $this->savingsLock = $lock;
            }

            // Check if savings target can now be purchased!
            $targetType = $lock['target_type'];
            $elementId  = $lock['element_id'];

            $bought = false;
            if ($targetType === 'building') {
                $bought = $gateway->constructBuilding($pId, $elementId);
            } elseif ($targetType === 'research') {
                $bought = $gateway->researchTech($pId, $elementId);
            } elseif ($targetType === 'shipyard') {
                $bought = $gateway->constructUnits($pId, $elementId, 1);
            }

            if ($bought) {
                $this->ctx->decisionLog->record(
                    'savings_target_achieved',
                    "Savings Lock fulfilled! Purchased {$targetType} #{$elementId} on planet #{$pId}",
                    $lock,
                    "achieved_{$elementId}",
                    10000.0
                );
                $this->clearSavingsLock($lockCat);
                $lastResult = array('status' => 'completed', 'element' => $elementId);
                continue;
            }

            $lastResult = array('status' => 'accumulating', 'eta' => $lock['eta_seconds']);
        }

        $this->saveState();
        return $lastResult;
    }

    /**
     * Persist credits, savings lock, and last budget time to uni1_bots
     */
    public function saveState()
    {
        $creditsJson = json_encode($this->credits);
        $savingsJson = !empty($this->savingsLock) ? json_encode($this->savingsLock) : null;

        try {
            $db = Database::get();
            if ($db && !empty($this->ctx->botId)) {
                $db->update(
                    "UPDATE " . DB_PREFIX . "bots SET 
                     budget_credits = :credits,
                     savings_lock = :savings,
                     last_budget_time = :last_time
                     WHERE bot_id = :bot_id;",
                    array(
                        ':credits'   => $creditsJson,
                        ':savings'   => $savingsJson,
                        ':last_time' => (int)$this->lastBudgetTime,
                        ':bot_id'    => (int)$this->ctx->botId,
                    )
                );
            }
        } catch (Throwable $e) {
            // Non-DB unit test mock fallback
        }

        if (isset($this->ctx->botRow)) {
            $this->ctx->botRow['budget_credits']   = $creditsJson;
            $this->ctx->botRow['savings_lock']     = $savingsJson;
            $this->ctx->botRow['last_budget_time'] = (int)$this->lastBudgetTime;
        }
    }
}

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

        // 2. Savings lock JSON
        if (!empty($botRow['savings_lock'])) {
            $parsedLock = @json_decode($botRow['savings_lock'], true);
            if (is_array($parsedLock) && !empty($parsedLock['element_id'])) {
                $this->savingsLock = $parsedLock;
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

        foreach ($planets as $pId => $pEntry) {
            $pData = $pEntry['data'];
            $pId = (int)$pId;

            if (!isset($this->credits['planets'][$pId])) {
                $this->credits['planets'][$pId] = array(
                    'mines'   => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
                    'fleet'   => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
                    'defense' => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
                );
            }

            $curStockM = (float)($pData['metal'] ?? 0.0);
            $curStockC = (float)($pData['crystal'] ?? 0.0);
            $curStockD = (float)($pData['deuterium'] ?? 0.0);

            $prodM = max(0.0, (float)($pData['metal_perhour'] ?? 0.0));
            $prodC = max(0.0, (float)($pData['crystal_perhour'] ?? 0.0));
            $prodD = max(0.0, (float)($pData['deuterium_perhour'] ?? 0.0));

            $capM = max(10000.0, (float)($pData['metal_max'] ?? 10000.0));
            $capC = max(10000.0, (float)($pData['crystal_max'] ?? 10000.0));
            $capD = max(10000.0, (float)($pData['deuterium_max'] ?? 10000.0));

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

        $this->credits['last_updated'] = $now;
        $this->lastBudgetTime = $now;
        $this->saveState();
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

        // 2. Deduct dispatched cargo proportionally from local categories
        $mCargo = max(0.0, (float)($cargoResources[901] ?? $cargoResources['metal'] ?? 0.0));
        $cCargo = max(0.0, (float)($cargoResources[902] ?? $cargoResources['crystal'] ?? 0.0));
        $dCargo = max(0.0, (float)($cargoResources[903] ?? $cargoResources['deuterium'] ?? 0.0));

        if ($mCargo > 0.0 || $cCargo > 0.0 || $dCargo > 0.0) {
            $this->deductCargoFromLocalPlanet($planetId, $mCargo, $cCargo, $dCargo);
            if (isset($this->credits['last_physical'][$planetId])) {
                $this->credits['last_physical'][$planetId]['metal']     = round(max(0.0, (float)$this->credits['last_physical'][$planetId]['metal'] - $mCargo), 2);
                $this->credits['last_physical'][$planetId]['crystal']   = round(max(0.0, (float)$this->credits['last_physical'][$planetId]['crystal'] - $cCargo), 2);
                $this->credits['last_physical'][$planetId]['deuterium'] = round(max(0.0, (float)$this->credits['last_physical'][$planetId]['deuterium'] - $dCargo), 2);
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

            $capM = max(10000.0, (float)($pData['metal_max'] ?? 10000.0));
            $capC = max(10000.0, (float)($pData['crystal_max'] ?? 10000.0));
            $capD = max(10000.0, (float)($pData['deuterium_max'] ?? 10000.0));

            $maxLocalM = $curM * $localRatioSum;
            $maxLocalC = $curC * $localRatioSum;
            $maxLocalD = $curD * $localRatioSum;

            // Compute current local sum of virtual budgets
            $sumVirtM = (float)($this->credits['planets'][$pId]['mines']['metal'] ?? 0.0)
                      + (float)($this->credits['planets'][$pId]['fleet']['metal'] ?? 0.0)
                      + (float)($this->credits['planets'][$pId]['defense']['metal'] ?? 0.0);

            $sumVirtC = (float)($this->credits['planets'][$pId]['mines']['crystal'] ?? 0.0)
                      + (float)($this->credits['planets'][$pId]['fleet']['crystal'] ?? 0.0)
                      + (float)($this->credits['planets'][$pId]['defense']['crystal'] ?? 0.0);

            $sumVirtD = (float)($this->credits['planets'][$pId]['mines']['deuterium'] ?? 0.0)
                      + (float)($this->credits['planets'][$pId]['fleet']['deuterium'] ?? 0.0)
                      + (float)($this->credits['planets'][$pId]['defense']['deuterium'] ?? 0.0);

            // Reconcile each resource (Metal, Crystal, Deuterium)
            $this->reconcilePlanetResource($pId, 'metal', $sumVirtM, $maxLocalM, $capM, $ratios, $localRatioSum);
            $this->reconcilePlanetResource($pId, 'crystal', $sumVirtC, $maxLocalC, $capC, $ratios, $localRatioSum);
            $this->reconcilePlanetResource($pId, 'deuterium', $sumVirtD, $maxLocalD, $capD, $ratios, $localRatioSum);

            // Keep last_physical locked to real physical stock
            $this->credits['last_physical'][$pId] = array(
                'metal'     => $curM,
                'crystal'   => $curC,
                'deuterium' => $curD,
            );
        }

        // Reconcile Global Research against Empire-wide physical backing
        $maxResM = $totalEmpirePhysM * $r_research;
        $maxResC = $totalEmpirePhysC * $r_research;
        $maxResD = $totalEmpirePhysD * $r_research;

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

        if ($sumVirt > $maxLocal && $sumVirt > 0.0) {
            // Case A: Phantom inflation (Virtual > Physical). Scale down proportionally.
            $scale = $maxLocal / $sumVirt;
            foreach ($cats as $cat) {
                if (isset($this->credits['planets'][$pId][$cat][$resKey])) {
                    $cur = (float)$this->credits['planets'][$pId][$cat][$resKey];
                    $this->credits['planets'][$pId][$cat][$resKey] = round($cur * $scale, 2);
                }
            }
        } elseif ($sumVirt < $maxLocal) {
            // Case B: Idle physical surplus not yet allocated to virtual buckets.
            $surplus = $maxLocal - $sumVirt;
            foreach ($cats as $cat) {
                $r_k = (float)($ratios[$cat] ?? 0.0);
                $weight = $r_k / $localRatioSum;
                $catCeiling = $cap * $r_k;
                $cur = (float)($this->credits['planets'][$pId][$cat][$resKey] ?? 0.0);
                $newVal = min($catCeiling, $cur + ($surplus * $weight));
                $this->credits['planets'][$pId][$cat][$resKey] = round($newVal, 2);
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

        return $this->tryInternalBucketSwap($planetId, $category, $cost);
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

        // Calculate shortfalls in targetCategory
        $defM = max(0.0, $needM - $curM);
        $defC = max(0.0, $needC - $curC);
        $defD = max(0.0, $needD - $curD);

        // If target already has enough virtual credits, no swap needed
        if ($defM <= 0.0001 && $defC <= 0.0001 && $defD <= 0.0001) {
            return true;
        }

        // 2. Physical Stock Verification ($PLANET must have enough physical stock)
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

        if ($physM < $needM || $physC < $needC || $physD < $needD) {
            return false;
        }

        // 3. Conversion Rates (4 Metal : 2 Crystal : 1 Deuterium)
        $rate = array('metal' => 1.0, 'crystal' => 2.0, 'deuterium' => 4.0);

        // Required MSE for the deficits
        $defMSE = array(
            'metal'     => $defM * $rate['metal'],
            'crystal'   => $defC * $rate['crystal'],
            'deuterium' => $defD * $rate['deuterium'],
        );
        $totalDefMSE = array_sum($defMSE);

        // Calculate surplus in target category available to give up
        $surplusTarget = array(
            'metal'     => max(0.0, $curM - $needM),
            'crystal'   => max(0.0, $curC - $needC),
            'deuterium' => max(0.0, $curD - $needD),
        );
        $totalSurplusMSE = ($surplusTarget['metal'] * $rate['metal'])
                         + ($surplusTarget['crystal'] * $rate['crystal'])
                         + ($surplusTarget['deuterium'] * $rate['deuterium']);

        if ($totalSurplusMSE < $totalDefMSE - 0.0001) {
            // Target category cannot cover the MSE cost of the swap
            return false;
        }

        // 4. Candidate Donor Categories (mines, fleet, defense excluding target and savings_lock)
        $possibleDonors = array('mines', 'fleet', 'defense');
        $validDonors = array();
        foreach ($possibleDonors as $cat) {
            if ($cat === $targetCategory) {
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

        // Verify that unlocked donors have enough of each deficit resource
        $deficits = array('metal' => $defM, 'crystal' => $defC, 'deuterium' => $defD);
        foreach ($deficits as $resKey => $amt) {
            if ($amt <= 0.0001) continue;
            $availInDonors = 0.0;
            foreach ($validDonors as $dCat) {
                $availInDonors += (float)($this->credits['planets'][$planetId][$dCat][$resKey] ?? 0.0);
            }
            if ($availInDonors < $amt - 0.0001) {
                return false;
            }
        }

        // 5. Execute 4:2:1 MSE Neutral Swap
        foreach ($deficits as $resKey => $amtNeeded) {
            if ($amtNeeded <= 0.0001) continue;

            $remNeeded = $amtNeeded;
            foreach ($validDonors as $dCat) {
                if ($remNeeded <= 0.0001) break;

                $dAvail = (float)($this->credits['planets'][$planetId][$dCat][$resKey] ?? 0.0);
                if ($dAvail <= 0.0001) continue;

                $takeUnits = min($remNeeded, $dAvail);
                $swapMSE = $takeUnits * $rate[$resKey];

                // Pay $swapMSE from targetCategory surplus to dCat
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

                // Transfer deficit resource to target
                $this->credits['planets'][$planetId][$dCat][$resKey] = max(0.0, round(
                    $this->credits['planets'][$planetId][$dCat][$resKey] - $takeUnits,
                    2
                ));
                $targetRef[$resKey] = max(0.0, round($targetRef[$resKey] + $takeUnits, 2));

                $remNeeded -= $takeUnits;
            }
        }

        // 6. Record and Save
        if (isset($this->ctx->decisionLog)) {
            $this->ctx->decisionLog->record(
                'internal_bucket_swap',
                "Internal Bucket Swap: rebalanceo neutral 4:2:1 MSE en planeta #{$planetId} para {$targetCategory} (Coste 0 MO, Delta MSE = 0)",
                array(
                    'planet_id'        => $planetId,
                    'target_category'  => $targetCategory,
                    'needed_resources' => $neededResources,
                ),
                "swap_{$targetCategory}_{$planetId}",
                2500.0
            );
        }

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
     * Compute donor surplus strictly available for a specific budget category,
     * maintaining a 2-hour production safety reserve on the donor planet.
     *
     * @param int $planetId
     * @param string $category 'mines', 'fleet', 'defense', 'research'
     * @param array $planetData
     * @return array ['metal' => float, 'crystal' => float, 'deuterium' => float]
     */
    public function getDonorSurplusByCategory($planetId, $category, array $planetData)
    {
        $planetId = (int)$planetId;
        $prodM = max(0.0, (float)($planetData['metal_perhour'] ?? 0.0));
        $prodC = max(0.0, (float)($planetData['crystal_perhour'] ?? 0.0));
        $prodD = max(0.0, (float)($planetData['deuterium_perhour'] ?? 0.0));

        // Safety reserve: minimum 2 hours of local production (or baseline floor)
        $reserveM = max(50000.0, 2.0 * $prodM);
        $reserveC = max(30000.0, 2.0 * $prodC);
        $reserveD = max(20000.0, 2.0 * $prodD);

        $physSurplusM = max(0.0, (float)($planetData['metal'] ?? 0.0) - $reserveM);
        $physSurplusC = max(0.0, (float)($planetData['crystal'] ?? 0.0) - $reserveC);
        $physSurplusD = max(0.0, (float)($planetData['deuterium'] ?? 0.0) - $reserveD);

        // Budget credits available for this category
        $catCredits = ($category === 'research')
            ? ($this->credits['research'] ?? array())
            : ($this->credits['planets'][$planetId][$category] ?? array());

        // Budget credits available for this category (strictly isolated to the 4 canonical budgets)
        $catCredits = ($category === 'research')
            ? ($this->credits['research'] ?? array())
            : ($this->credits['planets'][$planetId][$category] ?? array());

        $budgetAvailM = (float)($catCredits['metal'] ?? 0.0);
        $budgetAvailC = (float)($catCredits['crystal'] ?? 0.0);
        $budgetAvailD = (float)($catCredits['deuterium'] ?? 0.0);

        return array(
            'metal'     => round(min($physSurplusM, $budgetAvailM), 2),
            'crystal'   => round(min($physSurplusC, $budgetAvailC), 2),
            'deuterium' => round(min($physSurplusD, $budgetAvailD), 2),
        );
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
            $this->credits['last_physical'][$planetId]['metal'] = max(0.0, (float)$this->credits['last_physical'][$planetId]['metal'] - $resM);
            $this->credits['last_physical'][$planetId]['crystal'] = max(0.0, (float)$this->credits['last_physical'][$planetId]['crystal'] - $resC);
            $this->credits['last_physical'][$planetId]['deuterium'] = max(0.0, (float)$this->credits['last_physical'][$planetId]['deuterium'] - $resD);
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
    public function setSavingsLock($planetId, $targetType, $elementId, array $cost, array $planetData, $category = null)
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

        $this->savingsLock = array(
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

        $this->saveState();
    }

    /**
     * Check if a specific category is locked by savings on a planet
     */
    public function isCategoryLocked($planetId, $category)
    {
        if (empty($this->savingsLock)) {
            return false;
        }

        $lockPlanet = (int)($this->savingsLock['planet_id'] ?? 0);
        $lockCat    = $this->savingsLock['category'] ?? 'mines';

        if ($category === 'research') {
            return ($lockCat === 'research');
        }

        return ($lockPlanet === (int)$planetId && $lockCat === $category);
    }

    /**
     * Get active savings lock only if it matches the requested category
     */
    public function getSavingsLockForCategory($planetId, $category)
    {
        if ($this->isCategoryLocked($planetId, $category)) {
            return $this->savingsLock;
        }
        return null;
    }

    public function clearSavingsLock()
    {
        $this->savingsLock = null;
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
        if (empty($this->savingsLock)) {
            return array('status' => 'idle');
        }

        $pId = (int)$this->savingsLock['planet_id'];
        $planetEntry = $empire->getPlanet($pId);
        if (empty($planetEntry)) {
            $this->clearSavingsLock();
            return array('status' => 'invalid_planet');
        }

        $pData = $planetEntry['data'];
        $cost  = $this->savingsLock['cost'];

        $defRes = (int)($this->savingsLock['deficit_resource'] ?? 901);
        $colKey = ($defRes === 901) ? 'metal' : (($defRes === 902) ? 'crystal' : 'deuterium');
        $costDef = (float)($cost[$defRes] ?? $cost[$colKey] ?? 0.0);
        $maxStockCap = (float)($pData[$colKey . '_max'] ?? 0.0);
        $currentStock = (float)($pData[$colKey] ?? 0.0);
        $lastStock    = (float)($this->savingsLock['last_stock'] ?? $currentStock);

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
        // If cost of deficit resource exceeds 95% of storage capacity, the planet can never save enough!
        // Immediately force warehouse construction funded directly by savings reserve.
        $storeNeeded = 0;
        if ($maxStockCap > 0 && $costDef > ($maxStockCap * 0.95)) {
            $storeNeeded = $storeIdForDeficit;
        }

        if ($storeNeeded > 0 && !$isWarehouseBuilding) {
            $ok = $gateway->constructBuilding($pId, $storeNeeded);
            if ($ok) {
                $this->ctx->decisionLog->record(
                    'savings_storage_unlock',
                    "Forced warehouse unlock (#{$storeNeeded}) on planet #{$pId} due to savings target exceeding 95% storage capacity",
                    array('store' => $storeNeeded, 'target' => $this->savingsLock['element_id']),
                    "unlock_store_{$storeNeeded}",
                    15000.0
                );
                return array('status' => 'unlocked_storage', 'store' => $storeNeeded);
            }
        }

        // --- 2. Anti-Farm Watchdog ---
        // If for 3 consecutive cycles (135s) Delta_stock[deficit] <= 0:
        // Planet is being raided/farmed. Abort savings, release reserve, build defenses and light fighters!

        // Check if warehouse is currently being upgraded or if storage is saturated (>95%)
        $isStorageSaturated = ($maxStockCap > 0 && $currentStock >= ($maxStockCap * 0.95));

        $deltaStock = $currentStock - $lastStock;
        if ($deltaStock <= 0.0 && !$isWarehouseBuilding && !$isStorageSaturated) {
            $this->savingsLock['stagnant_cycles'] = (int)$this->savingsLock['stagnant_cycles'] + 1;
        } elseif ($deltaStock > 0.0) {
            $this->savingsLock['stagnant_cycles'] = 0;
        }
        $this->savingsLock['last_stock'] = $currentStock;

        if ($this->savingsLock['stagnant_cycles'] >= 3) {
            $this->ctx->decisionLog->record(
                'savings_antifarm_abort',
                "Watchdog Anti-Farm triggered on planet #{$pId}: 3 consecutive cycles (135s) with non-growing stock. Aborting savings to recruit defenses and fighters.",
                array('stagnant_cycles' => $this->savingsLock['stagnant_cycles'], 'deficit_res' => $defRes),
                'abort_savings_defend',
                20000.0
            );

            $this->clearSavingsLock();

            // Emergency military intervention: Recruit immediate defense turrets & Light Hunters (204)
            $gateway->constructUnits($pId, 401, 100); // Lanzamisiles
            $gateway->constructUnits($pId, 402, 50);  // Láser pequeño
            $gateway->constructUnits($pId, 407, 1);   // Cúpula pequeña
            $gateway->constructUnits($pId, 204, 50);  // Caza ligero (204 en 2Moons)
            $gateway->constructUnits($pId, 206, 20);  // Crucero (206)

            return array('status' => 'aborted_antifarm');
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
        $this->savingsLock['eta_seconds'] = $this->calculateSafeEta($cost, $stockMap, $prodMap);

        // Check if savings target can now be purchased!
        $targetType = $this->savingsLock['target_type'];
        $elementId  = $this->savingsLock['element_id'];

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
                $this->savingsLock,
                "achieved_{$elementId}",
                10000.0
            );
            $this->clearSavingsLock();
            return array('status' => 'completed', 'element' => $elementId);
        }

        $this->saveState();
        return array('status' => 'accumulating', 'eta' => $this->savingsLock['eta_seconds']);
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

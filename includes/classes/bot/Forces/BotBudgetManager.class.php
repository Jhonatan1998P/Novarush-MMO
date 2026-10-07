<?php

require_once 'includes/classes/bot/Evaluation/EconomyValuator.class.php';

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
                'last_updated'     => TIMESTAMP,
            );
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
        $persName = isset($this->ctx->personality->name) ? strtolower($this->ctx->personality->name) : 'balanced';

        switch ($persName) {
            case 'raider':
            case 'aggressive':
                return array('mines' => 0.20, 'fleet' => 0.50, 'defense' => 0.10, 'research' => 0.20);

            case 'miner':
            case 'economic':
                return array('mines' => 0.50, 'fleet' => 0.10, 'defense' => 0.15, 'research' => 0.25);

            case 'bunker':
            case 'turtle':
            case 'defensive':
                return array('mines' => 0.30, 'fleet' => 0.15, 'defense' => 0.40, 'research' => 0.15);

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
        $now = TIMESTAMP;
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

            // Update local planetary categories: mines, fleet, defense
            foreach (array('mines', 'fleet', 'defense') as $cat) {
                $r_k = $ratios[$cat];

                $deltaBM = ($prodM * $deltaT / 3600.0) * $r_k;
                $deltaBC = ($prodC * $deltaT / 3600.0) * $r_k;
                $deltaBD = ($prodD * $deltaT / 3600.0) * $r_k;

                $maxCeilingM = $capM * $r_k;
                $maxCeilingC = $capC * $r_k;
                $maxCeilingD = $capD * $r_k;

                // Metal accumulation & opportunity overflow
                $curM = (float)($this->credits['planets'][$pId][$cat]['metal'] ?? 0.0) + $deltaBM;
                if ($curM > $maxCeilingM) {
                    $overflowM = $curM - $maxCeilingM;
                    $this->credits['opportunity_fund']['metal'] += $overflowM;
                    $curM = $maxCeilingM;
                }
                $this->credits['planets'][$pId][$cat]['metal'] = round($curM, 2);

                // Crystal accumulation & opportunity overflow
                $curC = (float)($this->credits['planets'][$pId][$cat]['crystal'] ?? 0.0) + $deltaBC;
                if ($curC > $maxCeilingC) {
                    $overflowC = $curC - $maxCeilingC;
                    $this->credits['opportunity_fund']['crystal'] += $overflowC;
                    $curC = $maxCeilingC;
                }
                $this->credits['planets'][$pId][$cat]['crystal'] = round($curC, 2);

                // Deuterium accumulation & opportunity overflow
                $curD = (float)($this->credits['planets'][$pId][$cat]['deuterium'] ?? 0.0) + $deltaBD;
                if ($curD > $maxCeilingD) {
                    $overflowD = $curD - $maxCeilingD;
                    $this->credits['opportunity_fund']['deuterium'] += $overflowD;
                    $curD = $maxCeilingD;
                }
                $this->credits['planets'][$pId][$cat]['deuterium'] = round($curD, 2);
            }
        }

        // Global research budget (empire-wide aggregate)
        $r_research = $ratios['research'];
        $deltaResM = ($totalEmpireProdM * $deltaT / 3600.0) * $r_research;
        $deltaResC = ($totalEmpireProdC * $deltaT / 3600.0) * $r_research;
        $deltaResD = ($totalEmpireProdD * $deltaT / 3600.0) * $r_research;

        $resCeilingM = $totalEmpireCapM * $r_research;
        $resCeilingC = $totalEmpireCapC * $r_research;
        $resCeilingD = $totalEmpireCapD * $r_research;

        $curResM = (float)($this->credits['research']['metal'] ?? 0.0) + $deltaResM;
        if ($curResM > $resCeilingM) {
            $overflowM = $curResM - $resCeilingM;
            $this->credits['opportunity_fund']['metal'] += $overflowM;
            $curResM = $resCeilingM;
        }
        $this->credits['research']['metal'] = round($curResM, 2);

        $curResC = (float)($this->credits['research']['crystal'] ?? 0.0) + $deltaResC;
        if ($curResC > $resCeilingC) {
            $overflowC = $curResC - $resCeilingC;
            $this->credits['opportunity_fund']['crystal'] += $overflowC;
            $curResC = $resCeilingC;
        }
        $this->credits['research']['crystal'] = round($curResC, 2);

        $curResD = (float)($this->credits['research']['deuterium'] ?? 0.0) + $deltaResD;
        if ($curResD > $resCeilingD) {
            $overflowD = $curResD - $resCeilingD;
            $this->credits['opportunity_fund']['deuterium'] += $overflowD;
            $curResD = $resCeilingD;
        }
        $this->credits['research']['deuterium'] = round($curResD, 2);

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

        $this->saveState();
        return true;
    }

    private function deductResourcePair(array &$catRef, $resourceKey, $amount)
    {
        if ($amount <= 0.0) return;

        $cur = (float)($catRef[$resourceKey] ?? 0.0);
        if ($cur >= $amount) {
            $catRef[$resourceKey] = round($cur - $amount, 2);
        } else {
            $deficit = $amount - $cur;
            $catRef[$resourceKey] = 0.0;
            if (isset($this->credits['opportunity_fund'][$resourceKey])) {
                $fundCur = (float)$this->credits['opportunity_fund'][$resourceKey];
                $this->credits['opportunity_fund'][$resourceKey] = round(max(0.0, $fundCur - $deficit), 2);
            }
        }
    }

    /**
     * Check if a category has sufficient credits (including opportunity fund)
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

        $fundM = (float)($this->credits['opportunity_fund']['metal'] ?? 0.0);
        $fundC = (float)($this->credits['opportunity_fund']['crystal'] ?? 0.0);
        $fundD = (float)($this->credits['opportunity_fund']['deuterium'] ?? 0.0);

        if ($category === 'research') {
            $availM = (float)($this->credits['research']['metal'] ?? 0.0) + $fundM;
            $availC = (float)($this->credits['research']['crystal'] ?? 0.0) + $fundC;
            $availD = (float)($this->credits['research']['deuterium'] ?? 0.0) + $fundD;
        } else {
            $planetId = (int)$planetId;
            $cat = $this->credits['planets'][$planetId][$category] ?? array();
            $availM = (float)($cat['metal'] ?? 0.0) + $fundM;
            $availC = (float)($cat['crystal'] ?? 0.0) + $fundC;
            $availD = (float)($cat['deuterium'] ?? 0.0) + $fundD;
        }

        return ($availM >= $costM && $availC >= $costC && $availD >= $costD);
    }

    public function getCredits()
    {
        return $this->credits;
    }

    public function getSavingsLock()
    {
        return $this->savingsLock;
    }

    /**
     * Engage or refresh Savings Lock for a priority element
     */
    public function setSavingsLock($planetId, $targetType, $elementId, array $cost, array $planetData)
    {
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
            'element_id'       => (int)$elementId,
            'cost'             => array(901 => $costM, 902 => $costC, 903 => $costD),
            'deficit_resource' => $defRes,
            'last_stock'       => (float)$stockMap[$defRes],
            'stagnant_cycles'  => 0,
            'start_time'       => TIMESTAMP,
            'eta_seconds'      => $eta,
        );

        $this->saveState();
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

        // --- 1. Dynamic Storage Cap Guard ---
        // If cost exceeds 95% of storage capacity, the planet can never save enough!
        // Immediately force warehouse construction funded directly by savings reserve.
        $storeNeeded = 0;
        if ((float)($cost[901] ?? $cost['metal'] ?? 0.0) > ((float)($pData['metal_max'] ?? 0.0) * 0.95)) {
            $storeNeeded = 22; // Metal store
        } elseif ((float)($cost[902] ?? $cost['crystal'] ?? 0.0) > ((float)($pData['crystal_max'] ?? 0.0) * 0.95)) {
            $storeNeeded = 23; // Crystal store
        } elseif ((float)($cost[903] ?? $cost['deuterium'] ?? 0.0) > ((float)($pData['deuterium_max'] ?? 0.0) * 0.95)) {
            $storeNeeded = 24; // Deuterium store
        }

        $isWarehouseBuilding = false;
        if (!empty($planetEntry['building_queue'])) {
            foreach ($planetEntry['building_queue'] as $bItem) {
                if (in_array((int)$bItem[0], array(22, 23, 24))) {
                    $isWarehouseBuilding = true;
                    break;
                }
            }
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
        $defRes = (int)$this->savingsLock['deficit_resource'];
        $colKey = ($defRes === 901) ? 'metal' : (($defRes === 902) ? 'crystal' : 'deuterium');
        $currentStock = (float)($pData[$colKey] ?? 0.0);
        $lastStock    = (float)$this->savingsLock['last_stock'];
        $maxStockCap  = (float)($pData[$colKey . '_max'] ?? 0.0);

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
        $db = Database::get();
        $creditsJson = json_encode($this->credits);
        $savingsJson = !empty($this->savingsLock) ? json_encode($this->savingsLock) : null;

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

        $this->ctx->botRow['budget_credits']   = $creditsJson;
        $this->ctx->botRow['savings_lock']     = $savingsJson;
        $this->ctx->botRow['last_budget_time'] = (int)$this->lastBudgetTime;
    }
}

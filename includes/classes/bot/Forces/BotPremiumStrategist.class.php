<?php

require_once 'includes/classes/PremiumEconomy.class.php';
require_once 'includes/classes/bot/Kernel/BotClock.class.php';
require_once 'includes/classes/bot/Evaluation/EconomyValuator.class.php';

/**
 * NovaRush Bot AI - Premium Economy, Trader & Officers Strategist (FASE 2)
 *
 * Responsibilities:
 * 1. Dark Matter Mining at Resource Fair (ShowFairPage, IDs 2301, 2302, 2303):
 *    - Up to 3 conversions daily per resource element.
 *    - Earns 1.000 MO per conversion.
 *    - Strict solvency validation: planet_stock >= cost + 1.5 * next_mine_cost.
 * 2. Universal On-Demand Trader with Strict Budget Isolation:
 *    - 4 categories: mines, research, fleet, defense.
 *    - 1-hour independent cooldown per category.
 *    - Strict isolation: Only trades using virtual credits of the requesting category.
 *    - Synchronizes last_physical in budget_credits to prevent false windfall inflation.
 *    - Cost: 50 MO, maintaining >= 1.000 MO cushion.
 * 3. Officer Investment by ROI:
 *    - Geólogo (601) > Almirante (602) > Ingeniero (603) > Tecnócrata (604).
 *    - Cost formula: ceil(3000 * 1.25^lvl).
 *    - Maintains >= 2.000 MO cushion.
 * 4. Containers (ShowConteinerPage, ID 924):
 *    - Opened on industrial maturity (>= 10M MSE/h) or critical infrastructure deficit.
 */
class BotPremiumStrategist
{
    private $ctx;
    private $gateway;

    // Officer priority order by ROI
    const OFFICER_ORDER = array(601, 602, 603, 604);

    // Charge conversion rates (standard OGame / 2Moons trader)
    const CHARGE_RATES = array(
        901 => array(901 => 1.0,  902 => 2.0, 903 => 4.0),
        902 => array(901 => 0.5,  902 => 1.0, 903 => 2.0),
        903 => array(901 => 0.25, 902 => 0.5, 903 => 1.0),
    );

    public function __construct(BotContext $ctx, BotActionGateway $gateway)
    {
        $this->ctx = $ctx;
        $this->gateway = $gateway;
    }

    /**
     * Coordinate premium economy operations during turn
     *
     * @param BotWorldModel $world
     */
    public function managePremiumEconomy(BotWorldModel $world)
    {
        // 1. Minado de MO en Feria de Recursos (2301, 2302, 2303)
        $this->runFairExchanges($world->empire);

        // 2. Transmutación obligatoria de Stardust a Antimateria legítima (2305)
        $this->transmuteStardust();

        // 3. Arbitraje inteligente de la cadena de suministro (Feria 2306, 2307, 2308)
        $this->arbitrateSupplyChain($world);

        // 4. Inversión en Oficiales por ROI
        $this->investInOfficers();

        // 5. Apertura Táctica de Contenedores por necesidad, lote o madurez
        $this->processContainers($world->empire);
    }

    /**
     * Run Dark Matter mining in Resource Fair (2301, 2302, 2303)
     *
     * @param BotEmpireState $empire
     * @return int Number of conversions executed
     */
    public function runFairExchanges(BotEmpireState $empire)
    {
        global $resource, $pricelist;

        $now = BotClock::get();
        $db = Database::get();
        $botId = (int)$this->ctx->botId;

        $fairElements = array(
            2301 => 901, // Metal
            2302 => 902, // Cristal
            2303 => 903, // Deuterio
        );

        $conversionsDone = 0;
        $planets = $empire->getPlanets();
        if (empty($planets)) {
            return 0;
        }

        $dailyLimit = (int)PremiumEconomy::get('fair_daily_limit', 3);
        $H = PremiumEconomy::get('fair_hours', 6);
        $P = PremiumEconomy::indexedHourlyMSE($botId);
        $growth = PremiumEconomy::get('fair_growth', 1.25);

        foreach ($fairElements as $elId => $resId) {
            $colName = $resource[$elId];
            $userCooldown = isset($this->ctx->user[$colName]) ? (int)$this->ctx->user[$colName] : 0;
            if ($now <= $userCooldown) {
                continue;
            }

            $dailyKey = 'fair_res_' . $elId;
            $n = PremiumEconomy::dailyCount($botId, $dailyKey);
            if ($n >= $dailyLimit) {
                continue;
            }

            // Calculate cost for conversion
            $mult = pow($growth, $n);
            if ($elId == 2301) {
                $cost = (float)ceil($H * $P * $mult);
                $resCol = 'metal';
                $mineElement = 1; // Metal Mine
            } elseif ($elId == 2302) {
                $cost = (float)ceil($H * $P * $mult / 2.0);
                $resCol = 'crystal';
                $mineElement = 2; // Crystal Mine
            } else {
                $cost = (float)ceil($H * $P * $mult / 4.0);
                $resCol = 'deuterium';
                $mineElement = 3; // Deuterium Synthesizer
            }

            // Find planet with highest stock of this resource that satisfies solvency
            $bestPlanetId = 0;
            $bestStock = 0.0;
            $requiredThreshold = 0.0;

            foreach ($planets as $pId => $pEntry) {
                $pData = $pEntry['data'];
                $stock = (float)($pData[$resCol] ?? 0.0);

                // Calculate next mine cost on this planet
                $nextMineLevel = (int)($pData[$resource[$mineElement]] ?? 0) + 1;
                $nextMineCost = $this->calculateMineResourceCost($mineElement, $nextMineLevel, $resId);

                // Solvency formula: stock >= cost + 1.5 * next_mine_cost
                $solvencyFloor = $cost + (1.5 * $nextMineCost);

                if ($stock >= $solvencyFloor && $stock > $bestStock) {
                    $bestPlanetId = (int)$pId;
                    $bestStock = $stock;
                    $requiredThreshold = $solvencyFloor;
                }
            }

            if ($bestPlanetId > 0) {
                // Execute conversion atomically
                $db->update("UPDATE %%PLANETS%% SET {$resCol} = {$resCol} - :cost WHERE id = :pid AND {$resCol} >= :cost;", array(
                    ':cost' => $cost,
                    ':pid'  => $bestPlanetId,
                ));

                if ($db->rowCount() > 0) {
                    // Credit 1.000 MO
                    PremiumEconomy::credit($this->ctx->user, 921, 1000, 'fair_res', $elId, "n={$n};amt=1");

                    // Increment daily counter
                    PremiumEconomy::dailyIncrement($botId, $dailyKey);

                    // Update cooldown
                    $cooldownDuration = isset($pricelist[$elId]['time']) ? (int)$pricelist[$elId]['time'] : 3600;
                    $newCooldown = $now + $cooldownDuration;
                    $this->ctx->user[$colName] = $newCooldown;
                    $db->update("UPDATE %%USERS%% SET {$colName} = :t WHERE id = :uid;", array(
                        ':t'   => $newCooldown,
                        ':uid' => $botId,
                    ));

                    // Synchronize physical baseline in BotBudgetManager to avoid phantom windfall
                    if (isset($this->ctx->budgetManager)) {
                        $deltaM = ($resId == 901) ? -$cost : 0.0;
                        $deltaC = ($resId == 902) ? -$cost : 0.0;
                        $deltaD = ($resId == 903) ? -$cost : 0.0;
                        $this->ctx->budgetManager->syncPhysicalAdjustment($bestPlanetId, $deltaM, $deltaC, $deltaD);
                    }

                    $this->ctx->decisionLog->record(
                        'fair_mining',
                        "Feria de Recursos: Minado exitoso de 1.000 MO con {$resCol} (Coste: " . number_format($cost, 0) . ", Stock restante: " . number_format($bestStock - $cost, 0) . ") en planeta #{$bestPlanetId}",
                        array(
                            'element_id' => $elId,
                            'cost'       => $cost,
                            'planet_id'  => $bestPlanetId,
                            'reward_mo'  => 1000,
                            'daily_n'    => $n + 1,
                        ),
                        "fair_{$elId}_{$bestPlanetId}",
                        2500.0
                    );

                    $conversionsDone++;
                }
            }
        }

        return $conversionsDone;
    }

    /**
     * Rebalance Planet Resources into 50% Metal, 35% Crystal, 15% Deuterium (MSE)
     * using the minimum number of bilateral exchanges (at most 2).
     *
     * @param int $planetId
     * @param string $category 'fleet', 'defense', 'research', 'mines'
     * @return bool True if trade executed
     */
    public function rebalancePlanet50_35_15($planetId, $category, $isBatchMode = false)
    {
        $planetId = (int)$planetId;
        if (!in_array($category, array('fleet', 'defense', 'research', 'mines'))) {
            $category = 'mines';
        }

        $now = BotClock::get();
        $db = Database::get();

        // 1. Check Empire and Planet cooldowns
        if (isset($this->ctx->budgetManager)) {
            if (!$isBatchMode && $this->ctx->budgetManager->isEmpireTraderOnCooldown()) {
                return false;
            }
            if ($this->ctx->budgetManager->isPlanetTraderOnCooldown($planetId)) {
                return false;
            }
        }

        // 2. Check Dark Matter fee (cost_trader, default 50 MO)
        $costMO = (float)Config::get()->cost_trader;
        if ($costMO <= 0.0) $costMO = 50.0;

        $userMO = (float)($this->ctx->user['darkmatter'] ?? 0.0);
        if ($userMO < $costMO) {
            return false;
        }

        // 3. Fetch current physical resources
        $planetRow = $db->selectSingle("SELECT metal, crystal, deuterium FROM %%PLANETS%% WHERE id = :pid LIMIT 1;", array(':pid' => $planetId));
        if (empty($planetRow)) {
            return false;
        }

        $curM = (float)$planetRow['metal'];
        $curC = (float)$planetRow['crystal'];
        $curD = (float)$planetRow['deuterium'];

        $totalMSE = $curM + (2.0 * $curC) + (4.0 * $curD);
        if ($totalMSE <= 0.0) {
            return false;
        }

        // 4. Calculate target 50% Metal, 35% Crystal, 15% Deuterium (in MSE)
        $targetM = round(0.50 * $totalMSE);
        $targetC = round((0.35 * $totalMSE) / 2.0);
        $targetD = round((0.15 * $totalMSE) / 4.0);

        // Exact MSE conservation adjustment (residual diff placed in metal)
        $actualTargetMSE = $targetM + (2.0 * $targetC) + (4.0 * $targetD);
        $residual = round($totalMSE - $actualTargetMSE);
        $targetM += $residual;

        // 5. Calculate MSE differences for each pool
        $deltaM = $curM - $targetM;          // > 0 surplus, < 0 deficit
        $deltaC = ($curC - $targetC) * 2.0;  // > 0 surplus, < 0 deficit
        $deltaD = ($curD - $targetD) * 4.0;  // > 0 surplus, < 0 deficit

        $surpluses = array();
        $deficits  = array();

        if ($deltaM > 0.01) $surpluses[901] = $deltaM;
        elseif ($deltaM < -0.01) $deficits[901] = -$deltaM;

        if ($deltaC > 0.01) $surpluses[902] = $deltaC;
        elseif ($deltaC < -0.01) $deficits[902] = -$deltaC;

        if ($deltaD > 0.01) $surpluses[903] = $deltaD;
        elseif ($deltaD < -0.01) $deficits[903] = -$deltaD;

        // 6. Compute minimum bilateral exchanges (at most 2 exchanges)
        $exchanges = array();
        foreach ($surpluses as $surplusRes => &$surplusAmount) {
            foreach ($deficits as $deficitRes => &$deficitAmount) {
                if ($surplusAmount <= 0.01 || $deficitAmount <= 0.01) {
                    continue;
                }

                $transferMSE = min($surplusAmount, $deficitAmount);
                $surplusAmount -= $transferMSE;
                $deficitAmount -= $transferMSE;

                $fromRate = ($surplusRes == 901) ? 1.0 : (($surplusRes == 902) ? 2.0 : 4.0);
                $toRate   = ($deficitRes == 901) ? 1.0 : (($deficitRes == 902) ? 2.0 : 4.0);

                $fromUnits = $transferMSE / $fromRate;
                $toUnits   = $transferMSE / $toRate;

                $exchanges[] = array(
                    'from_res'   => $surplusRes,
                    'to_res'     => $deficitRes,
                    'mse'        => $transferMSE,
                    'from_units' => $fromUnits,
                    'to_units'   => $toUnits,
                );
            }
        }
        unset($surplusAmount, $deficitAmount);

        // 7. Debit Dark Matter fee (50 MO)
        if (!PremiumEconomy::debit($this->ctx->user, 921, $costMO, 'trader_call', 0, "demand_rebalance;pid={$planetId};cat={$category}")) {
            return false;
        }

        // 8. Update physical resources in database
        $db->update("UPDATE %%PLANETS%% SET 
            metal = :tm, 
            crystal = :tc, 
            deuterium = :td 
            WHERE id = :pid;", array(
            ':tm'  => $targetM,
            ':tc'  => $targetC,
            ':td'  => $targetD,
            ':pid' => $planetId,
        ));

        // 9. Synchronize virtual budgets and apply cooldowns (1h empire, 2h planet)
        if (isset($this->ctx->budgetManager)) {
            $this->ctx->budgetManager->syncDemandTraderRebalance($planetId, $category, $targetM, $targetC, $targetD, $exchanges, true, !$isBatchMode);
        }

        // 10. Record telemetry in decisionLog
        $this->ctx->decisionLog->record(
            'trader_demand_rebalance',
            "Mercader Bajo Demanda ({$category}): Rebalanceo 50/35/15 MSE en planeta #{$planetId} (Total MSE: " . number_format($totalMSE, 0) . ", " . count($exchanges) . " intercambios mínimos)",
            array(
                'planet_id'   => $planetId,
                'category'    => $category,
                'total_mse'   => $totalMSE,
                'target_m'    => $targetM,
                'target_c'    => $targetC,
                'target_d'    => $targetD,
                'exchanges'   => $exchanges,
                'fee_mo'      => $costMO,
            ),
            "demand_trader_{$category}_{$planetId}",
            3500.0
        );

        if (isset($this->ctx->telemetry)) {
            $pInfo = $db->selectSingle("SELECT name, galaxy, system, planet FROM %%PLANETS%% WHERE id = :pid LIMIT 1;", array(':pid' => $planetId));
            $pName = $pInfo['name'] ?? "Planeta #{$planetId}";
            $coords = isset($pInfo['galaxy']) ? "[{$pInfo['galaxy']}:{$pInfo['system']}:{$pInfo['planet']}]" : '';
            $traderReason = "Demanda prioritaria de recursos para " . strtoupper($category) . " (déficit balanceado a 50/35/15 MSE)";
            $this->ctx->telemetry->logTrader(
                $pName,
                $coords,
                $category,
                $traderReason,
                array('metal' => $curM, 'crystal' => $curC, 'deuterium' => $curD, 'mse' => $totalMSE),
                array('metal' => $targetM, 'crystal' => $targetC, 'deuterium' => $targetD, 'mse' => $actualTargetMSE),
                $exchanges,
                $costMO
            );
        }

        return true;
    }

    /**
     * Universal On-Demand Trader with Strict Budget Isolation (Delegates to 50/35/15 rebalance)
     *
     * @param int $planetId
     * @param string $category 'mines', 'research', 'fleet', 'defense'
     * @param int $targetResId
     * @param float $amountNeeded
     * @return bool True if trade executed
     */
    public function executeUniversalTrade($planetId, $category, $targetResId = 0, $amountNeeded = 0.0)
    {
        return $this->rebalancePlanet50_35_15($planetId, $category);
    }

    /**
     * Emergency Trader for Fleetsave: Bypasses standard category cooldown,
     * requires only the 50 MO fee (without 1000 MO cushion), and converts available
     * Crystal (2:1) and Metal (4:1) from the military 'fleet' budget to Deuterium.
     *
     * @param int $planetId
     * @param float $amountNeededDeut
     * @return float Amount of Deuterium successfully purchased (0.0 if failed)
     */
    public function executeEmergencyFleetsaveTrade($planetId, $amountNeededDeut, $allowAllCategories = true)
    {
        $amountNeededDeut = (float)ceil($amountNeededDeut);
        if ($amountNeededDeut <= 0.0) {
            return 0.0;
        }

        $costMO = (float)Config::get()->cost_trader;
        if ($costMO <= 0.0) $costMO = 50.0;

        $userMO = (float)($this->ctx->user['darkmatter'] ?? 0.0);
        if ($userMO < $costMO) {
            return 0.0; // Insufficient Dark Matter for merchant fee
        }

        if (!isset($this->ctx->budgetManager)) {
            return 0.0;
        }

        $db = Database::get();
        $planetRow = $db->selectSingle("SELECT metal, crystal, deuterium FROM %%PLANETS%% WHERE id = :pid LIMIT 1;", array(':pid' => (int)$planetId));
        if (empty($planetRow)) {
            return 0.0;
        }

        $categories = $allowAllCategories ? array('fleet', 'defense', 'mines') : array('fleet');
        $remNeeded = $amountNeededDeut;
        $totalDeutGained = 0.0;
        $totalMetCost = 0.0;
        $totalCryCost = 0.0;
        $categoryDebits = array();

        $physMetal = (float)$planetRow['metal'];
        $physCrystal = (float)$planetRow['crystal'];

        foreach ($categories as $cat) {
            if ($remNeeded <= 0.0) break;

            $catCredits = $this->ctx->budgetManager->getCategoryCredits($planetId, $cat);
            $catMetal   = max(0.0, min((float)($catCredits['metal'] ?? 0.0), $physMetal));
            $catCrystal = max(0.0, min((float)($catCredits['crystal'] ?? 0.0), $physCrystal));

            // Crystal rate: 2.0 crystal per 1 deut
            $maxDeutFromCry = floor($catCrystal / 2.0);
            $deutFromCry    = min($remNeeded, $maxDeutFromCry);
            $cryCost        = $deutFromCry * 2.0;

            $catRemNeeded   = $remNeeded - $deutFromCry;
            // Metal rate: 4.0 metal per 1 deut
            $maxDeutFromMet = floor($catMetal / 4.0);
            $deutFromMet    = min($catRemNeeded, $maxDeutFromMet);
            $metCost        = $deutFromMet * 4.0;

            $gained = $deutFromCry + $deutFromMet;
            if ($gained > 0.0) {
                $categoryDebits[$cat] = array(
                    'cry_cost' => $cryCost,
                    'met_cost' => $metCost,
                    'deut_cry' => $deutFromCry,
                    'deut_met' => $deutFromMet,
                );
                $totalDeutGained += $gained;
                $totalCryCost    += $cryCost;
                $totalMetCost    += $metCost;
                $physCrystal     -= $cryCost;
                $physMetal       -= $metCost;
                $remNeeded       -= $gained;
            }
        }

        // If strict virtual budget didn't cover it, but physical resources are physically present on planet
        if ($remNeeded > 0.0 && $allowAllCategories && ($physCrystal >= 2.0 || $physMetal >= 4.0)) {
            $maxDeutFromCry = floor($physCrystal / 2.0);
            $deutFromCry    = min($remNeeded, $maxDeutFromCry);
            $cryCost        = $deutFromCry * 2.0;

            $catRemNeeded   = $remNeeded - $deutFromCry;
            $maxDeutFromMet = floor($physMetal / 4.0);
            $deutFromMet    = min($catRemNeeded, $maxDeutFromMet);
            $metCost        = $deutFromMet * 4.0;

            $gained = $deutFromCry + $deutFromMet;
            if ($gained > 0.0) {
                $categoryDebits['fleet'] = array(
                    'cry_cost' => ($categoryDebits['fleet']['cry_cost'] ?? 0.0) + $cryCost,
                    'met_cost' => ($categoryDebits['fleet']['met_cost'] ?? 0.0) + $metCost,
                    'deut_cry' => ($categoryDebits['fleet']['deut_cry'] ?? 0.0) + $deutFromCry,
                    'deut_met' => ($categoryDebits['fleet']['deut_met'] ?? 0.0) + $deutFromMet,
                );
                $totalDeutGained += $gained;
                $totalCryCost    += $cryCost;
                $totalMetCost    += $metCost;
            }
        }

        if ($totalDeutGained <= 0.0) {
            return 0.0; // No resources available to trade
        }

        // Debit MO fee
        if (!PremiumEconomy::debit($this->ctx->user, 921, $costMO, 'emergency_fleetsave_trader', 903, "pid={$planetId};sum={$totalDeutGained}")) {
            return 0.0;
        }

        // Update physical resources
        $db->update("UPDATE %%PLANETS%% SET 
            metal = GREATEST(0, metal - :mCost), 
            crystal = GREATEST(0, crystal - :cCost), 
            deuterium = deuterium + :dGain 
            WHERE id = :pid;", array(
            ':mCost' => $totalMetCost,
            ':cCost' => $totalCryCost,
            ':dGain' => $totalDeutGained,
            ':pid'   => (int)$planetId,
        ));

        // Synchronize virtual category budgets
        foreach ($categoryDebits as $cat => $deb) {
            if ($deb['cry_cost'] > 0) {
                $this->ctx->budgetManager->syncTraderExchange($planetId, $cat, 902, $deb['cry_cost'], 903, $deb['deut_cry'], false);
            }
            if ($deb['met_cost'] > 0) {
                $this->ctx->budgetManager->syncTraderExchange($planetId, $cat, 901, $deb['met_cost'], 903, $deb['deut_met'], false);
            }
        }

        $this->ctx->decisionLog->record(
            'trader_exchange',
            "Emergency Fleetsave Trader: Intercambio de " . number_format($totalCryCost) . " cristal y " . number_format($totalMetCost) . " metal por " . number_format($totalDeutGained) . " deuterio en planeta #{$planetId} (50 MO fee)",
            array(
                'planet_id'    => $planetId,
                'categories'   => array_keys($categoryDebits),
                'donor_cry'    => $totalCryCost,
                'donor_met'    => $totalMetCost,
                'target_deut'  => $totalDeutGained,
                'fee_mo'       => $costMO,
            ),
            "emergency_trader_{$planetId}",
            5000.0
        );

        if (isset($this->ctx->telemetry)) {
            $pInfo = $db->selectSingle("SELECT name, galaxy, system, planet FROM %%PLANETS%% WHERE id = :pid LIMIT 1;", array(':pid' => $planetId));
            $pName = $pInfo['name'] ?? "Planeta #{$planetId}";
            $coords = isset($pInfo['galaxy']) ? "[{$pInfo['galaxy']}:{$pInfo['system']}:{$pInfo['planet']}]" : '';
            $this->ctx->telemetry->writeLog(
                'MERCADER',
                trim("{$pName} {$coords}"),
                "Mercader de Emergencia (Fleetsave): Compra de Deuterio para Evacuación",
                "Entregados: " . BotTelemetryLogger::formatNumber($totalMetCost) . " Metal, " . BotTelemetryLogger::formatNumber($totalCryCost) . " Cristal -> Recibidos: +" . BotTelemetryLogger::formatNumber($totalDeutGained) . " Deuterio",
                "Tasa: " . BotTelemetryLogger::formatNumber($costMO) . " MO",
                "Combustible de emergencia para fleetsave de supervivencia ante amenaza militar"
            );
        }

        return $totalDeutGained;
    }

    /**
     * Tactical fuel merchant trade for military offensive strikes.
     * Converts available Crystal/Metal on the FOB to Deuterium when fuel is deficient.
     *
     * @param int $planetId
     * @param float $amountNeededDeut
     * @return float Amount of Deuterium successfully purchased
     */
    public function executeTacticalFuelTrade($planetId, $amountNeededDeut)
    {
        return $this->executeEmergencyFleetsaveTrade($planetId, $amountNeededDeut, true);
    }

    /**
     * Check if 50/35/15 on-demand trader rebalance can resolve category deficit
     *
     * @param BotContext $ctx
     * @param int $planetId
     * @param string $category
     * @param array $cost
     * @return array|false
     */
    public static function canTraderResolveDeficit(BotContext $ctx, $planetId, $category, array $cost)
    {
        $now = BotClock::get();
        if (isset($ctx->budgetManager)) {
            // Check empire cooldown (1h)
            if ($ctx->budgetManager->isEmpireTraderOnCooldown()) {
                return false;
            }
            // Check planet cooldown (2h)
            if ($ctx->budgetManager->isPlanetTraderOnCooldown($planetId)) {
                return false;
            }
        }

        $costMO = (float)Config::get()->cost_trader;
        if ($costMO <= 0.0) $costMO = 50.0;
        $userMO = (float)($ctx->user['darkmatter'] ?? 0.0);
        if ($userMO < $costMO) {
            return false;
        }

        $db = Database::get();
        $planetRow = $db->selectSingle("SELECT metal, crystal, deuterium FROM %%PLANETS%% WHERE id = :pid LIMIT 1;", array(':pid' => (int)$planetId));
        if (empty($planetRow)) {
            return false;
        }

        $curM = (float)$planetRow['metal'];
        $curC = (float)$planetRow['crystal'];
        $curD = (float)$planetRow['deuterium'];
        $totalMSE = $curM + (2.0 * $curC) + (4.0 * $curD);

        $costM = (float)($cost[901] ?? $cost['metal'] ?? 0.0);
        $costC = (float)($cost[902] ?? $cost['crystal'] ?? 0.0);
        $costD = (float)($cost[903] ?? $cost['deuterium'] ?? 0.0);

        // Calculate potential 50/35/15 target resources
        $targetM = 0.50 * $totalMSE;
        $targetC = 0.175 * $totalMSE;
        $targetD = 0.0375 * $totalMSE;

        // If the rebalance produces enough resources for the cost, trade is viable
        if ($targetM >= $costM && $targetC >= $costC && $targetD >= $costD) {
            return array(
                'rebalance_50_35_15' => true,
                'total_mse'          => $totalMSE,
                'target_m'           => $targetM,
                'target_c'           => $targetC,
                'target_d'           => $targetD,
            );
        }

        return false;
    }

    /**
     * Helper to automatically evaluate and execute 50/35/15 trade for category deficit
     */
    public static function tryTraderForCategory(BotContext $ctx, $planetId, $category, array $cost)
    {
        $canResolve = self::canTraderResolveDeficit($ctx, $planetId, $category, $cost);
        if (!$canResolve) {
            return false;
        }

        $strategist = new self($ctx, new BotActionGateway($ctx));
        return $strategist->rebalancePlanet50_35_15($planetId, $category);
    }

    /**
     * Orchestrate On-Demand Trader Demands across the Bot's Empire:
     * - Collect demands across all planets
     * - Order demands by category priority: Flota (1) > Defensa (2) > Investigación (3) > Construcción (4)
     * - Max 1 demand per planet
     * - Max 5 demands executed across the whole empire per batch
     * - Once executed: Empire enters 1-hour cooldown (3,600s), Executed Planets enter 2-hour cooldown (7,200s)
     *
     * @param BotWorldModel $world
     * @return int Number of planets rebalanced
     */
    public function orchestrateDemandTraderBatch(BotWorldModel $world)
    {
        if (!isset($this->ctx->budgetManager)) {
            return 0;
        }

        // 1. Check Empire Cooldown (1 hour)
        if ($this->ctx->budgetManager->isEmpireTraderOnCooldown()) {
            return 0;
        }

        // 2. Check Dark Matter fee availability (at least 50 MO)
        $costMO = (float)Config::get()->cost_trader;
        if ($costMO <= 0.0) $costMO = 50.0;
        $userMO = (float)($this->ctx->user['darkmatter'] ?? 0.0);
        if ($userMO < $costMO) {
            return 0;
        }

        $planets = $world->empire->getPlanets();
        if (empty($planets)) {
            return 0;
        }

        // Category priority mapping: Flota (1) > Defensa (2) > Investigación (3) > Construcción (4)
        $priorityWeights = array(
            'fleet'    => 1,
            'defense'  => 2,
            'research' => 3,
            'mines'    => 4,
        );

        $planetDemands = array();

        foreach ($planets as $pEntry) {
            $pData = $pEntry['data'];
            $pId = (int)$pData['id'];

            // Skip planet if it is currently in its 2-hour cooldown
            if ($this->ctx->budgetManager->isPlanetTraderOnCooldown($pId)) {
                continue;
            }

            // Detect at most 1 highest-priority demand for this planet
            $demandCategory = $this->detectPlanetTraderDemand($pData, $world);
            if ($demandCategory !== null) {
                $totalMSE = (float)$pData['metal'] + (2.0 * (float)$pData['crystal']) + (4.0 * (float)$pData['deuterium']);
                $planetDemands[] = array(
                    'planet_id' => $pId,
                    'category'  => $demandCategory,
                    'priority'  => $priorityWeights[$demandCategory] ?? 99,
                    'total_mse' => $totalMSE,
                );
            }
        }

        if (empty($planetDemands)) {
            return 0;
        }

        // Sort demands by category priority: Flota (1) > Defensa (2) > Investigación (3) > Construcción (4)
        // Secondary sort: highest Total MSE
        usort($planetDemands, function($a, $b) {
            if ($a['priority'] !== $b['priority']) {
                return $a['priority'] <=> $b['priority'];
            }
            return $b['total_mse'] <=> $a['total_mse'];
        });

        // Max 5 demands per empire batch
        $batchToExecute = array_slice($planetDemands, 0, 5);
        $executed = 0;

        foreach ($batchToExecute as $demand) {
            $currentMO = (float)($this->ctx->user['darkmatter'] ?? 0.0);
            if ($currentMO < $costMO) {
                break;
            }

            $ok = $this->rebalancePlanet50_35_15($demand['planet_id'], $demand['category'], true);
            if ($ok) {
                $executed++;
            }
        }

        if ($executed > 0) {
            // Apply Empire Cooldown (1 hour = 3600s) for the batch
            $this->ctx->budgetManager->setEmpireTraderCooldown(BotClock::get() + 3600);

            $this->ctx->decisionLog->record(
                'trader_batch_executed',
                "Orquestación Mercader: Ejecutadas {$executed} demandas (Flota > Defensa > Inv > Const). Imperio en cooldown por 1h, planetas en cooldown por 2h.",
                array(
                    'executed_count' => $executed,
                    'demands'        => $batchToExecute,
                ),
                'trader_batch',
                4000.0
            );
        }

        return $executed;
    }

    /**
     * Detect highest priority trader demand for a planet in order:
     * 1. Flota (fleet)
     * 2. Defensa (defense)
     * 3. Investigación (research)
     * 4. Construcción (mines)
     *
     * @param array $pData
     * @param BotWorldModel $world
     * @return string|null
     */
    public function detectPlanetTraderDemand(array $pData, BotWorldModel $world)
    {
        $pId = (int)$pData['id'];

        // 1. Explicit demand override (runtime / test cases)
        if (!empty($pData['trader_demand_category']) && in_array($pData['trader_demand_category'], array('fleet', 'defense', 'research', 'mines'))) {
            return $pData['trader_demand_category'];
        }

        // 2. Savings lock check on this planet
        if (isset($this->ctx->budgetManager)) {
            $lock = $this->ctx->budgetManager->getSavingsLock();
            if (!empty($lock) && isset($lock['planet_id']) && (int)$lock['planet_id'] === $pId) {
                $lockCat = $lock['category'] ?? null;
                if (in_array($lockCat, array('fleet', 'defense', 'research', 'mines'))) {
                    return $lockCat;
                }
            }
        }

        $totalMSE = (float)$pData['metal'] + (2.0 * (float)$pData['crystal']) + (4.0 * (float)$pData['deuterium']);
        if ($totalMSE < 10000.0) {
            return null; // Not enough resources to justify calling merchant
        }

        $pctM = (float)$pData['metal'] / $totalMSE;
        $pctC = (2.0 * (float)$pData['crystal']) / $totalMSE;
        $pctD = (4.0 * (float)$pData['deuterium']) / $totalMSE;

        // Priority 1: Flota (fleet)
        $shipyardLvl = (int)($pData['hangar'] ?? 0);
        if ($shipyardLvl >= 1 && ($pctD < 0.10 && ($pctM > 0.55 || $pctC > 0.40))) {
            return 'fleet';
        }

        // Priority 2: Defensa (defense)
        $siloLvl = (int)($pData['silo'] ?? 0);
        if ($siloLvl >= 1 && ($pctC < 0.20 && $pctM > 0.65)) {
            return 'defense';
        }

        // Priority 3: Investigación (research)
        $labLvl = (int)($pData['laboratory'] ?? 0);
        if ($labLvl >= 1 && ($pctC < 0.20 || $pctD < 0.08) && $totalMSE >= 50000.0) {
            return 'research';
        }

        // Priority 4: Construcción (mines)
        if ($pctM < 0.35 || $pctM > 0.75 || $pctC < 0.20 || $pctD < 0.05) {
            return 'mines';
        }

        return null;
    }

    /**
     * Invest in officers by ROI maintaining >= 2.000 MO cushion
     * Priority: Geólogo (601) > Almirante (602) > Ingeniero (603) > Tecnócrata (604)
     *
     * @return int Number of officer levels purchased
     */
    public function investInOfficers()
    {
        global $resource, $pricelist;

        $db = Database::get();
        $botId = (int)$this->ctx->botId;
        $purchased = 0;

        $base = PremiumEconomy::get('officer_base', 3000);
        $growth = PremiumEconomy::get('officer_growth', 1.25);

        foreach (self::OFFICER_ORDER as $offId) {
            $colName = $resource[$offId];
            $curLevel = (int)($this->ctx->user[$colName] ?? 0);
            $maxLevel = (int)($pricelist[$offId]['max'] ?? 20);

            if ($curLevel >= $maxLevel) {
                continue;
            }

            // Cost formula: ceil(3000 * 1.25^lvl)
            $costMO = (float)ceil($base * pow($growth, $curLevel));

            // Must preserve at least 2.000 MO cushion
            $currentMO = (float)($this->ctx->user['darkmatter'] ?? 0.0);
            if (($currentMO - $costMO) < 2000.0) {
                continue;
            }

            // Debit MO
            if (PremiumEconomy::debit($this->ctx->user, 921, $costMO, 'officier_buy', $offId, "lvl={$curLevel};amt=1")) {
                $this->ctx->user[$colName] = $curLevel + 1;
                $db->update("UPDATE %%USERS%% SET {$colName} = {$colName} + 1 WHERE id = :u;", array(
                    ':u' => $botId,
                ));

                $this->ctx->decisionLog->record(
                    'officer_investment',
                    "Inversión Oficial: Ascendido #{$offId} a nivel " . ($curLevel + 1) . " (Coste: " . number_format($costMO, 0) . " MO, Saldo restante: " . number_format($this->ctx->user['darkmatter'], 0) . " MO)",
                    array(
                        'officer_id' => $offId,
                        'new_level'  => $curLevel + 1,
                        'cost_mo'    => $costMO,
                    ),
                    "officer_{$offId}",
                    4000.0
                );

                if (isset($this->ctx->telemetry)) {
                    $offName = BotTelemetryLogger::getElementName($offId);
                    $this->ctx->telemetry->logPremiumSpend(
                        'Imperio',
                        "Contratación / Ascenso: {$offName} a Nivel " . ($curLevel + 1),
                        921,
                        $costMO,
                        (float)$this->ctx->user['darkmatter'],
                        "Optimización de retorno por inversión (ROI) para bono permanente"
                    );
                }

                $purchased++;
                break; // Buy up to 1 officer level per cycle to smooth investment
            }
        }

        return $purchased;
    }

    /**
     * Transmute Stardust (923) into Antimatter (922) via Fair element 2305 (Incinerate Strangers)
     * 1 Stardust = 900 AM. Daily limit / batch size: up to 5 per conversion.
     *
     * @return int Antimatter credited
     */
    public function transmuteStardust()
    {
        $stardust = (int)($this->ctx->user['stardust'] ?? 0);
        if ($stardust <= 0) {
            return 0;
        }

        $now = BotClock::get();
        $cooldown = (int)($this->ctx->user['fair_5'] ?? 0);
        if ($now <= $cooldown) {
            return 0;
        }

        $botId = (int)$this->ctx->botId;
        $amount = min($stardust, 5);

        // Debit Stardust
        if (!PremiumEconomy::debit($this->ctx->user, 923, $amount, 'fair_trade', 2305, "stardust_transmute;amt={$amount}")) {
            return 0;
        }

        // Credit 900 AM per Stardust
        $rewardAM = 900 * $amount;
        PremiumEconomy::credit($this->ctx->user, 922, $rewardAM, 'fair_trade', 2305, "stardust_transmute;amt={$amount}");

        // Update cooldown: 24h cooldown
        $newCooldown = $now + 86400;
        $this->ctx->user['fair_5'] = $newCooldown;

        $db = Database::get();
        $db->update("UPDATE %%USERS%% SET fair_5 = :t WHERE id = :uid;", array(
            ':t'   => $newCooldown,
            ':uid' => $botId,
        ));

        $this->ctx->decisionLog->record(
            'stardust_transmute',
            "Transmutación de Polvo de Estrellas (2305): Convertidas {$amount} unidades en " . number_format($rewardAM, 0) . " Antimateria legítima",
            array(
                'stardust_consumed'  => $amount,
                'antimatter_gained'  => $rewardAM,
                'stardust_remaining' => (int)$this->ctx->user['stardust'],
                'antimatter_total'   => (float)$this->ctx->user['antimatter'],
            ),
            "fair_2305_{$botId}",
            3500.0
        );

        if (isset($this->ctx->telemetry)) {
            $this->ctx->telemetry->logPremiumExchange(
                "Transmutación de Polvo Estelar (Feria #2305)",
                "{$amount} Stardust",
                "+" . number_format($rewardAM) . " AM (Saldo: " . BotTelemetryLogger::formatNumber($this->ctx->user['antimatter']) . " AM)",
                "Abastecimiento soberano de Antimateria para Fiestas e Ideologías"
            );
        }

        return $rewardAM;
    }

    /**
     * Arbitrate Closed-Loop Premium Supply Chain:
     * 1. AM -> Bulk Containers (2308): 5,000 AM -> 25 Containers on macro-deficit or excess AM.
     * 2. Containers -> AM (2307): 25 Containers -> 2,500 AM on emergency AM deficit.
     * 3. MO -> AM (2306): 9,375 MO -> 750 AM on massive MO stockpile.
     *
     * @param BotWorldModel $world
     */
    public function arbitrateSupplyChain(BotWorldModel $world)
    {
        $botId = (int)$this->ctx->botId;
        $now = BotClock::get();
        $db = Database::get();

        $userAM   = (float)($this->ctx->user['antimatter'] ?? 0.0);
        $userMO   = (float)($this->ctx->user['darkmatter'] ?? 0.0);
        $userCont = (int)($this->ctx->user['container'] ?? 0);

        // --- 1. AM -> Bulk Containers (2308): Inyección Mayorista ---
        $dailyKey2308   = 'fair_trade_2308';
        $dailyLimit2308 = 3;
        $n2308          = PremiumEconomy::dailyCount($botId, $dailyKey2308);

        if ($n2308 < $dailyLimit2308) {
            $cost2308 = (float)ceil(5000.0 * pow(1.25, $n2308));

            // Check if bot can afford it keeping at least 200 AM cushion
            if ($userAM >= ($cost2308 + 200.0)) {
                $hasCriticalDeficit = false;
                if (isset($this->ctx->budgetManager)) {
                    $lock = $this->ctx->budgetManager->getSavingsLock();
                    if (!empty($lock)) {
                        $hasCriticalDeficit = true;
                    }
                }

                // Check if bot has an active party need (siege, threat, or heavy science >= 18h)
                $hasPartyNeed = false;
                $hasActiveParty = false;
                global $reslist, $resource;
                if (!empty($reslist['party'])) {
                    foreach ($reslist['party'] as $pEl) {
                        $pCol = $resource[$pEl];
                        if (isset($this->ctx->user[$pCol]) && (int)$this->ctx->user[$pCol] > $now) {
                            $hasActiveParty = true;
                            break;
                        }
                    }
                }

                if (!$hasActiveParty) {
                    if (!empty($this->ctx->botRow['siege_cycles']) && (int)$this->ctx->botRow['siege_cycles'] > 0) {
                        $hasPartyNeed = true;
                    }
                    if (isset($world->threats) && method_exists($world->threats, 'getActiveThreats') && !empty($world->threats->getActiveThreats())) {
                        $hasPartyNeed = true;
                    }
                    $techEndTime = (int)($this->ctx->user['b_tech'] ?? 0);
                    if ($techEndTime > $now && ($techEndTime - $now) >= 64800) {
                        $hasPartyNeed = true;
                    }
                }

                // Trigger A: Critical savings lock deficit
                // Trigger B: Excess AM (>= 6000 AM, or >= cost + 7,200 AM if party needed)
                $excessThreshold = $hasPartyNeed ? ($cost2308 + 7200.0) : 6000.0;
                $hasExcessAM = ($userAM >= $excessThreshold);

                if ($hasCriticalDeficit || $hasExcessAM) {
                    if (PremiumEconomy::debit($this->ctx->user, 922, $cost2308, 'fair_trade', 2308, "bulk_containers;n={$n2308}")) {
                        PremiumEconomy::credit($this->ctx->user, 924, 25, 'fair_trade', 2308, "bulk_containers;amt=25");
                        PremiumEconomy::dailyIncrement($botId, $dailyKey2308);

                        $this->ctx->decisionLog->record(
                            'supply_chain_container_buy',
                            "Inyección Mayorista (2308): Canjeados " . number_format($cost2308, 0) . " AM por 25 Contenedores Precursores (12.5h PIB)",
                            array(
                                'cost_am'           => $cost2308,
                                'containers_gained' => 25,
                                'reason'            => $hasCriticalDeficit ? 'critical_deficit' : 'excess_am',
                                'remaining_am'      => (float)$this->ctx->user['antimatter'],
                            ),
                            "fair_2308_{$botId}",
                            4200.0
                        );

                        if (isset($this->ctx->telemetry)) {
                            $this->ctx->telemetry->logPremiumExchange(
                                "Compra Mayorista de Contenedores (Feria #2308)",
                                number_format($cost2308) . " AM",
                                "+25 Contenedores Precursores (Stock: " . ((int)$this->ctx->user['container'] + 25) . ")",
                                $hasCriticalDeficit ? "Déficit en macro-proyecto (savings lock)" : "Exceso de Antimateria soberana (>= 6,000 AM)"
                            );
                        }

                        $userAM   = (float)$this->ctx->user['antimatter'];
                        $userCont = (int)$this->ctx->user['container'];
                    }
                }
            }
        }

        // --- 2. Contenedores -> AM (2307): Flujo Inverso de Emergencia ---
        $dailyKey2307   = 'fair_trade_2307';
        $dailyLimit2307 = 3;
        $n2307          = PremiumEconomy::dailyCount($botId, $dailyKey2307);

        if ($n2307 < $dailyLimit2307 && $userCont >= 40 && $userAM < 500.0) {
            $cost2307 = (int)ceil(25.0 * pow(1.25, $n2307));
            if ($userCont >= $cost2307) {
                if (PremiumEconomy::debit($this->ctx->user, 924, $cost2307, 'fair_trade', 2307, "reverse_liquidity;n={$n2307}")) {
                    PremiumEconomy::credit($this->ctx->user, 922, 2500, 'fair_trade', 2307, "reverse_liquidity;amt=2500");
                    PremiumEconomy::dailyIncrement($botId, $dailyKey2307);

                    $this->ctx->decisionLog->record(
                        'supply_chain_container_sell',
                        "Flujo Inverso de Emergencia (2307): Convertidos {$cost2307} Contenedores excedentes en 2,500 AM para liquidez soberana",
                        array(
                            'containers_spent' => $cost2307,
                            'am_gained'        => 2500,
                            'remaining_conts'  => (int)$this->ctx->user['container'],
                            'current_am'       => (float)$this->ctx->user['antimatter'],
                        ),
                        "fair_2307_{$botId}",
                        4000.0
                    );

                    if (isset($this->ctx->telemetry)) {
                        $this->ctx->telemetry->logPremiumExchange(
                            "Flujo Inverso de Emergencia (Feria #2307)",
                            "{$cost2307} Contenedores Precursores",
                            "+2,500 AM (Saldo: " . BotTelemetryLogger::formatNumber($this->ctx->user['antimatter']) . " AM)",
                            "Superávit de contenedores (>= 40) y déficit crítico de AM (< 500 AM)"
                        );
                    }

                    $userAM = (float)$this->ctx->user['antimatter'];
                }
            }
        }

        // --- 3. MO -> AM (2306 - Pirate Emitter): Si sobreacumula MO (> 12,000 MO) ---
        $cooldown2306 = (int)($this->ctx->user['fair_6'] ?? 0);
        if ($now > $cooldown2306 && $userMO >= 12000.0 && $userAM < 2000.0) {
            $costMO = 9375.0;
            if (PremiumEconomy::debit($this->ctx->user, 921, $costMO, 'fair_trade', 2306, "mo_to_am")) {
                PremiumEconomy::credit($this->ctx->user, 922, 750, 'fair_trade', 2306, "mo_to_am");
                $newCooldown = $now + 86400;
                $this->ctx->user['fair_6'] = $newCooldown;
                $db->update("UPDATE %%USERS%% SET fair_6 = :t WHERE id = :uid;", array(
                    ':t'   => $newCooldown,
                    ':uid' => $botId,
                ));

                $this->ctx->decisionLog->record(
                    'supply_chain_mo_to_am',
                    "Arbitraje MO a AM (2306): Transmutadas 9,375 MO excedentes por 750 AM soberana",
                    array(
                        'mo_cost'      => $costMO,
                        'am_gained'    => 750,
                        'remaining_mo' => (float)$this->ctx->user['darkmatter'],
                        'current_am'   => (float)$this->ctx->user['antimatter'],
                    ),
                    "fair_2306_{$botId}",
                    3800.0
                );

                if (isset($this->ctx->telemetry)) {
                    $this->ctx->telemetry->logPremiumExchange(
                        "Transmutación MO a AM (Feria #2306)",
                        number_format($costMO) . " MO",
                        "+750 AM (Saldo: " . BotTelemetryLogger::formatNumber($this->ctx->user['antimatter']) . " AM)",
                        "Sobreacumulación de Materia Oscura (>= 12,000 MO)"
                    );
                }
            }
        }
    }

    /**
     * Tactical Opening of Containers (924)
     * - Injects 0.5h of total empire MSE production + 50 screening ships (25 LF, 15 HF, 10 CR) per container.
     * - Batches up to 5 containers on high inventory (>= 25) or critical deficits.
     * - Prefers FOB planet for military/raiders, or Home/Hub planet for miners/bunkers.
     *
     * @param BotEmpireState $empire
     * @return int Containers opened
     */
    public function processContainers(BotEmpireState $empire)
    {
        global $resource;

        $userContainers = (int)($this->ctx->user['container'] ?? 0);
        if ($userContainers <= 0) {
            return 0;
        }

        $botId = (int)$this->ctx->botId;

        // Condition A: Industrial maturity (>= 10M MSE/h total empire production)
        $hourlyProd = PremiumEconomy::playerHourlyProduction($botId);
        $hourlyMSE  = $hourlyProd['metal'] + (2.0 * $hourlyProd['crystal']) + (4.0 * $hourlyProd['deuterium']);
        $isMature   = ($hourlyMSE >= 10000000.0);

        // Condition B: Critical infrastructure deficit (savings lock active)
        $hasCriticalDeficit = false;
        if (isset($this->ctx->budgetManager)) {
            $lock = $this->ctx->budgetManager->getSavingsLock();
            if (!empty($lock)) {
                $hasCriticalDeficit = true;
            }
        }

        // Condition C: High inventory liquidation
        $hasExcessContainers = ($userContainers >= 25);

        if (!$isMature && !$hasCriticalDeficit && !$hasExcessContainers) {
            return 0;
        }

        // Determine batch size: 5 on excess or critical deficit, 2 on maturity, else 1
        $batchToOpen = 1;
        if ($hasCriticalDeficit || $hasExcessContainers) {
            $batchToOpen = min($userContainers, 5);
        } elseif ($isMature && $userContainers >= 2) {
            $batchToOpen = 2;
        }

        if (!PremiumEconomy::debit($this->ctx->user, 924, $batchToOpen, 'container_open', 0, "batch={$batchToOpen}")) {
            return 0;
        }

        // Best recipient planet: For raiders/aggressive, prefer FOB where light fleet and fuel are used
        $planets = $empire->getPlanets();
        $targetPlanetId = 0;
        $pers = strtolower($this->ctx->personality->name ?? 'balanced');

        if (in_array($pers, array('raider', 'aggressive', 'military', 'militar')) && !empty($this->ctx->botRow['fob_planet_id'])) {
            $fobCandidate = (int)$this->ctx->botRow['fob_planet_id'];
            if (isset($planets[$fobCandidate])) {
                $targetPlanetId = $fobCandidate;
            }
        }

        if ($targetPlanetId <= 0) {
            $targetPlanetId = (int)$this->ctx->user['id_planet'];
            if (!isset($planets[$targetPlanetId]) && !empty($planets)) {
                $targetPlanetId = (int)key($planets);
            }
        }

        // Calculate reward indexed to 30 min production per container
        $h = PremiumEconomy::get('ct_open_hours', 0.5);
        $totalMse = $hourlyProd['metal'] + (2.0 * $hourlyProd['crystal']) + (4.0 * $hourlyProd['deuterium']);
        if ($totalMse > 0) {
            $pctM = $hourlyProd['metal'] / $totalMse;
            $pctC = (2.0 * $hourlyProd['crystal']) / $totalMse;
            $pctD = (4.0 * $hourlyProd['deuterium']) / $totalMse;
        } else {
            $pctM = 0.5; $pctC = 0.3; $pctD = 0.2;
        }

        $effectiveMse = max($totalMse, (float)PremiumEconomy::baseIncomeMSE());
        $V = $h * $effectiveMse;

        $mPerCont = (float)floor($V * $pctM);
        $cPerCont = (float)floor(($V * $pctC) * 0.5);
        $dPerCont = (float)floor(($V * $pctD) * 0.25);

        $mBatch = $mPerCont * $batchToOpen;
        $cBatch = $cPerCont * $batchToOpen;
        $dBatch = $dPerCont * $batchToOpen;

        $db = Database::get();
        $db->update("UPDATE %%PLANETS%% SET 
            metal = metal + :m, 
            crystal = crystal + :c, 
            deuterium = deuterium + :d,
            {$resource[204]} = {$resource[204]} + :s204,
            {$resource[205]} = {$resource[205]} + :s205,
            {$resource[206]} = {$resource[206]} + :s206
            WHERE id = :pid;", array(
            ':m'    => $mBatch,
            ':c'    => $cBatch,
            ':d'    => $dBatch,
            ':s204' => 25 * $batchToOpen,
            ':s205' => 15 * $batchToOpen,
            ':s206' => 10 * $batchToOpen,
            ':pid'  => $targetPlanetId,
        ));

        // Credit container rewards directly into budget categories according to personality weights
        if (isset($this->ctx->budgetManager)) {
            $this->ctx->budgetManager->creditContainerReward($targetPlanetId, $mBatch, $cBatch, $dBatch);
        }

        $this->ctx->decisionLog->record(
            'container_open',
            "Contenedores Abiertos (#924): Lote de {$batchToOpen} contenedores acreditados (" . number_format($mBatch, 0) . " M, " . number_format($cBatch, 0) . " C, " . number_format($dBatch, 0) . " D + " . (50 * $batchToOpen) . " naves screening) en planeta #{$targetPlanetId}",
            array(
                'planet_id'        => $targetPlanetId,
                'batch_count'      => $batchToOpen,
                'metal'            => $mBatch,
                'crystal'          => $cBatch,
                'deuterium'        => $dBatch,
                'ships'            => array(204 => 25 * $batchToOpen, 205 => 15 * $batchToOpen, 206 => 10 * $batchToOpen),
                'containers_left'  => (int)$this->ctx->user['container'],
            ),
            "container_{$targetPlanetId}",
            3500.0
        );

        if (isset($this->ctx->telemetry)) {
            $tInfo = $db->selectSingle("SELECT name, galaxy, system, planet FROM %%PLANETS%% WHERE id = :pid LIMIT 1;", array(':pid' => $targetPlanetId));
            $tName = $tInfo['name'] ?? "Planeta #{$targetPlanetId}";
            $coords = isset($tInfo['galaxy']) ? "[{$tInfo['galaxy']}:{$tInfo['system']}:{$tInfo['planet']}]" : '';
            $reason = $hasCriticalDeficit ? "Déficit en macro-proyecto (inyección de recursos)" : ($hasExcessContainers ? "Liquidación de inventario alto de contenedores" : "Madurez industrial alcanzada");
            $this->ctx->telemetry->logContainerOpen(
                $tName,
                $coords,
                $batchToOpen,
                array('metal' => $mBatch, 'crystal' => $cBatch, 'deuterium' => $dBatch),
                array(204 => 25 * $batchToOpen, 205 => 15 * $batchToOpen, 206 => 10 * $batchToOpen),
                (int)$this->ctx->user['container'],
                $reason
            );
        }

        return $batchToOpen;
    }

    private function calculateMineResourceCost($elementId, $level, $targetResId)
    {
        global $pricelist;
        $base = (float)($pricelist[$elementId]['cost'][$targetResId] ?? 0.0);
        $factor = (float)($pricelist[$elementId]['factor'] ?? 1.5);
        return (float)floor($base * pow($factor, max(0, $level - 1)));
    }
}

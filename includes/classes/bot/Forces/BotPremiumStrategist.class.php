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

    // In-memory planet cache per turn to eliminate redundant DB queries
    private static $planetCache = array();

    // Officer priority order by ROI
    const OFFICER_ORDER = array(601, 602, 603, 604);

    // Charge conversion rates (standard OGame / 2Moons trader)
    const CHARGE_RATES = array(
        901 => array(901 => 1.0,  902 => 2.0, 903 => 4.0),
        902 => array(901 => 0.5,  902 => 1.0, 903 => 2.0),
        903 => array(901 => 0.25, 902 => 0.5, 903 => 1.0),
    );

    /**
     * Seed planet cache from BotEmpireState at the beginning of turn
     *
     * @param BotEmpireState $empire
     */
    public static function seedPlanetCache(BotEmpireState $empire): void
    {
        self::$planetCache = array();
        $planets = $empire->getPlanets();
        foreach ($planets as $p) {
            $pid = (int)($p['id'] ?? ($p['data']['id'] ?? 0));
            if ($pid > 0) {
                $pData = $p['data'] ?? $p;
                self::$planetCache[$pid] = array(
                    'metal'         => (float)($p['metal'] ?? ($pData['metal'] ?? 0.0)),
                    'crystal'       => (float)($p['crystal'] ?? ($pData['crystal'] ?? 0.0)),
                    'deuterium'     => (float)($p['deuterium'] ?? ($pData['deuterium'] ?? 0.0)),
                    'metal_max'     => (float)($pData['metal_max'] ?? 10000.0),
                    'crystal_max'   => (float)($pData['crystal_max'] ?? 10000.0),
                    'deuterium_max' => (float)($pData['deuterium_max'] ?? 10000.0),
                    'name'          => (string)($p['name'] ?? ($pData['name'] ?? '')),
                    'galaxy'        => (int)($p['galaxy'] ?? ($pData['galaxy'] ?? 0)),
                    'system'        => (int)($p['system'] ?? ($pData['system'] ?? 0)),
                    'planet'        => (int)($p['planet'] ?? ($pData['planet'] ?? 0)),
                );
            }
        }

        if (method_exists($empire, 'getMoons')) {
            $moons = $empire->getMoons();
            foreach ($moons as $m) {
                $mid = (int)($m['id'] ?? ($m['data']['id'] ?? 0));
                if ($mid > 0) {
                    $mData = $m['data'] ?? $m;
                    self::$planetCache[$mid] = array(
                        'metal'         => (float)($m['metal'] ?? ($mData['metal'] ?? 0.0)),
                        'crystal'       => (float)($m['crystal'] ?? ($mData['crystal'] ?? 0.0)),
                        'deuterium'     => (float)($m['deuterium'] ?? ($mData['deuterium'] ?? 0.0)),
                        'metal_max'     => (float)($mData['metal_max'] ?? 10000.0),
                        'crystal_max'   => (float)($mData['crystal_max'] ?? 10000.0),
                        'deuterium_max' => (float)($mData['deuterium_max'] ?? 10000.0),
                        'name'          => (string)($m['name'] ?? ($mData['name'] ?? '')),
                        'galaxy'        => (int)($m['galaxy'] ?? ($mData['galaxy'] ?? 0)),
                        'system'        => (int)($m['system'] ?? ($mData['system'] ?? 0)),
                        'planet'        => (int)($m['planet'] ?? ($mData['planet'] ?? 0)),
                    );
                }
            }
        }
    }

    /**
     * Retrieve cached planet row or fallback query to database
     *
     * @param int $planetId
     * @return array|null
     */
    public static function getCachedPlanetRow($planetId): ?array
    {
        $pid = (int)$planetId;
        if (isset(self::$planetCache[$pid])) {
            return self::$planetCache[$pid];
        }

        try {
            $db = Database::get();
            $row = $db->selectSingle(
                "SELECT metal, crystal, deuterium, metal_max, crystal_max, deuterium_max, name, galaxy, system, planet FROM %%PLANETS%% WHERE id = :pid LIMIT 1;",
                array(':pid' => $pid)
            );
            if (!empty($row)) {
                self::$planetCache[$pid] = array(
                    'metal'         => (float)$row['metal'],
                    'crystal'       => (float)$row['crystal'],
                    'deuterium'     => (float)$row['deuterium'],
                    'metal_max'     => (float)($row['metal_max'] ?? 10000.0),
                    'crystal_max'   => (float)($row['crystal_max'] ?? 10000.0),
                    'deuterium_max' => (float)($row['deuterium_max'] ?? 10000.0),
                    'name'          => (string)($row['name'] ?? ''),
                    'galaxy'        => (int)($row['galaxy'] ?? 0),
                    'system'        => (int)($row['system'] ?? 0),
                    'planet'        => (int)($row['planet'] ?? 0),
                );
                return self::$planetCache[$pid];
            }
        } catch (Throwable $e) {
            // DB fallback failed
        }

        return null;
    }

    /**
     * Update cached planet resources in RAM
     *
     * @param int $planetId
     * @param float $metal
     * @param float $crystal
     * @param float $deuterium
     */
    public static function updateCachedPlanetResources($planetId, $metal, $crystal, $deuterium): void
    {
        $pid = (int)$planetId;
        if (isset(self::$planetCache[$pid])) {
            self::$planetCache[$pid]['metal']     = (float)$metal;
            self::$planetCache[$pid]['crystal']   = (float)$crystal;
            self::$planetCache[$pid]['deuterium'] = (float)$deuterium;
        } else {
            $row = self::getCachedPlanetRow($pid);
            if ($row !== null) {
                self::$planetCache[$pid]['metal']     = (float)$metal;
                self::$planetCache[$pid]['crystal']   = (float)$crystal;
                self::$planetCache[$pid]['deuterium'] = (float)$deuterium;
            }
        }
    }

    /**
     * Invalidate planet cache (all or single planet)
     *
     * @param int|null $planetId
     */
    public static function invalidatePlanetCache($planetId = null): void
    {
        if ($planetId === null) {
            self::$planetCache = array();
        } else {
            unset(self::$planetCache[(int)$planetId]);
        }
    }

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

        $dailyLimit = 1;

        foreach ($fairElements as $elId => $resId) {
            $dailyKey = 'fair_res_' . $elId;
            $n = PremiumEconomy::dailyCount($botId, $dailyKey);
            if ($n >= $dailyLimit) {
                continue;
            }

            // Calculate cost for conversion based on 6h server active empires average
            $cost = PremiumEconomy::getFairResourceCost($elId);
            if ($elId == 2301) {
                $resCol = 'metal';
                $mineElement = 1; // Metal Mine
            } elseif ($elId == 2302) {
                $resCol = 'crystal';
                $mineElement = 2; // Crystal Mine
            } else {
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
                    self::invalidatePlanetCache($bestPlanetId);
                    if (isset($empire) && method_exists($empire, 'updateBodyData')) {
                        $empire->updateBodyData($bestPlanetId, array($resCol => $bestStock - $cost));
                    }
                    // Credit 1.000 MO
                    PremiumEconomy::credit($this->ctx->user, 921, 1000, 'fair_res', $elId, "n={$n};amt=1");

                    // Increment daily counter
                    PremiumEconomy::dailyIncrement($botId, $dailyKey);

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
     * Exact Delta Merchant Trade (BotSupplyChain v3.2)
     *
     * Displaces rigid 50/35/15 ratio with exact missing delta calculation (Delta_M, Delta_C, Delta_D)
     * for targetCost or Warehouse Compression (Case D).
     *
     * Trigger Conditions (only spends 50 MO if one holds):
     * 1. Tactical Emergency: fuel shortage for FS / Strike, or Anti-Farm Watchdog liquidation.
     * 2. High Value Unlock: physical MSE covers 100% of target MSE, level 1/2 cannot solve, >= 2h imperial prod.
     * 3. Warehouse Compression (Case D): warehouse >= 98% full, sells 50% saturated resource into C/D (Delta_MSE = 0).
     *
     * Single Bilateral Transaction: Uses highest surplus resource as currency ($payResId)
     * and acquires exact delta of deficit resources via $Charge matrix (Trader.php),
     * debiting 50 MO once via PremiumEconomy::debit().
     *
     * @param int $planetId
     * @param array $targetCost [901 => M, 902 => C, 903 => D] or ['metal' => M, ...]
     * @param string $contextReason 'tactical_emergency', 'high_value_unlock', 'warehouse_compression', etc.
     * @param bool $isBatchMode
     * @return bool True if trade executed
     */
    public function executeExactDeltaTrade($planetId, array $targetCost = array(), string $contextReason = 'tactical_emergency', $isBatchMode = false)
    {
        $planetId = (int)$planetId;
        $db = Database::get();

        // 1. Fee and Dark Matter verification
        $costMO = (float)Config::get()->cost_trader;
        if ($costMO <= 0.0) $costMO = 50.0;

        $userMO = (float)($this->ctx->user['darkmatter'] ?? 0.0);
        if ($userMO < $costMO) {
            return false;
        }

        $isEmergency = ($contextReason === 'tactical_emergency' || 
                        $contextReason === 'fleetsave' || 
                        strpos($contextReason, 'emergency') !== false || 
                        strpos($contextReason, 'watchdog') !== false || 
                        strpos($contextReason, 'strike') !== false);

        // Check cooldowns for non-emergencies
        if (isset($this->ctx->budgetManager) && !$isEmergency && $contextReason !== 'warehouse_compression' && $contextReason !== 'high_value_unlock') {
            if (!$isBatchMode && $this->ctx->budgetManager->isEmpireTraderOnCooldown()) {
                return false;
            }
            if ($this->ctx->budgetManager->isPlanetTraderOnCooldown($planetId)) {
                return false;
            }
        }

        // 2. Fetch planet physical state (from in-memory cache)
        $planetRow = self::getCachedPlanetRow($planetId);
        if (empty($planetRow)) {
            return false;
        }

        $curM = (float)$planetRow['metal'];
        $curC = (float)$planetRow['crystal'];
        $curD = (float)$planetRow['deuterium'];
        $maxM = max(10000.0, (float)($planetRow['metal_max'] ?? 10000.0));
        $maxC = max(10000.0, (float)($planetRow['crystal_max'] ?? 10000.0));
        $maxD = max(10000.0, (float)($planetRow['deuterium_max'] ?? 10000.0));

        // Rate matrix from Trader.php / self::CHARGE_RATES
        global $Charge;
        $chargeRates = !empty($Charge) ? $Charge : self::CHARGE_RATES;

        // -------------------------------------------------------------
        // TRIGGER 3: Warehouse Compression (Case D / TEST 6)
        // -------------------------------------------------------------
        $isCompression = ($contextReason === 'warehouse_compression') ||
                         ($curM >= 0.98 * $maxM) ||
                         ($curC >= 0.98 * $maxC) ||
                         ($curD >= 0.98 * $maxD);

        if ($isCompression && (empty($targetCost) || $contextReason === 'warehouse_compression')) {
            // Identify saturated resource
            $satRes = 901;
            $satRatio = $curM / $maxM;

            if (($curC / $maxC) > $satRatio) {
                $satRes = 902;
                $satRatio = $curC / $maxC;
            }
            if (($curD / $maxD) > $satRatio) {
                $satRes = 903;
                $satRatio = $curD / $maxD;
            }

            $newM = $curM;
            $newC = $curC;
            $newD = $curD;
            $exchanges = array();

            if ($satRes === 901) {
                // Sell 50% of Metal into Crystal and Deuterium
                $sellAmount = floor($curM * 0.50);
                if ($sellAmount <= 0) return false;

                $halfSell = $sellAmount / 2.0;
                $gainC = $halfSell / $chargeRates[901][902];
                $gainD = $halfSell / $chargeRates[901][903];

                $newM = $curM - $sellAmount;
                $newC = $curC + $gainC;
                $newD = $curD + $gainD;

                $exchanges[] = array('from_res' => 901, 'to_res' => 902, 'from_units' => $halfSell, 'to_units' => $gainC);
                $exchanges[] = array('from_res' => 901, 'to_res' => 903, 'from_units' => $halfSell, 'to_units' => $gainD);
            } elseif ($satRes === 902) {
                // Sell 50% of Crystal into Metal and Deuterium
                $sellAmount = floor($curC * 0.50);
                if ($sellAmount <= 0) return false;

                $halfSell = $sellAmount / 2.0;
                $gainM = $halfSell / $chargeRates[902][901];
                $gainD = $halfSell / $chargeRates[902][903];

                $newM = $curM + $gainM;
                $newC = $curC - $sellAmount;
                $newD = $curD + $gainD;

                $exchanges[] = array('from_res' => 902, 'to_res' => 901, 'from_units' => $halfSell, 'to_units' => $gainM);
                $exchanges[] = array('from_res' => 902, 'to_res' => 903, 'from_units' => $halfSell, 'to_units' => $gainD);
            } else {
                // Sell 50% of Deuterium into Metal and Crystal
                $sellAmount = floor($curD * 0.50);
                if ($sellAmount <= 0) return false;

                $halfSell = $sellAmount / 2.0;
                $gainM = $halfSell / $chargeRates[903][901];
                $gainC = $halfSell / $chargeRates[903][902];

                $newM = $curM + $gainM;
                $newC = $curC + $gainC;
                $newD = $curD - $sellAmount;

                $exchanges[] = array('from_res' => 903, 'to_res' => 901, 'from_units' => $halfSell, 'to_units' => $gainM);
                $exchanges[] = array('from_res' => 903, 'to_res' => 902, 'from_units' => $halfSell, 'to_units' => $gainC);
            }

            // Debit MO
            if (!PremiumEconomy::debit($this->ctx->user, 921, $costMO, 'trader_call', 0, "warehouse_compression;pid={$planetId}")) {
                return false;
            }

            // Update physical DB and in-memory cache
            $db->update("UPDATE %%PLANETS%% SET metal = :m, crystal = :c, deuterium = :d WHERE id = :pid;", array(
                ':m'   => $newM,
                ':c'   => $newC,
                ':d'   => $newD,
                ':pid' => $planetId,
            ));
            self::updateCachedPlanetResources($planetId, $newM, $newC, $newD);

            if (isset($this->ctx->empire) && method_exists($this->ctx->empire, 'updateBodyData')) {
                $this->ctx->empire->updateBodyData($planetId, array(
                    'metal'     => (float)$newM,
                    'crystal'   => (float)$newC,
                    'deuterium' => (float)$newD,
                ));
            }

            if (isset($this->ctx->budgetManager)) {
                $this->ctx->budgetManager->syncDemandTraderRebalance($planetId, 'mines', $newM, $newC, $newD, $exchanges, true, true);
            }

            $this->ctx->decisionLog->record(
                'warehouse_compression_trade',
                "Compresión de Almacén al >=98%: Venta de 50% de recurso #{$satRes} convertida a Deuterio/Cristal sin pérdida MSE (Coste: 50 MO)",
                array('planet_id' => $planetId, 'saturated_res' => $satRes, 'sold' => $sellAmount, 'exchanges' => $exchanges),
                "compress_{$planetId}",
                4500.0
            );

            return true;
        }

        // -------------------------------------------------------------
        // TRIGGERS 1 & 2: Exact Delta Trade for Target Cost (TEST 5)
        // -------------------------------------------------------------
        $costM = (float)($targetCost[901] ?? $targetCost['metal'] ?? 0.0);
        $costC = (float)($targetCost[902] ?? $targetCost['crystal'] ?? 0.0);
        $costD = (float)($targetCost[903] ?? $targetCost['deuterium'] ?? 0.0);

        $defM = max(0.0, $costM - $curM);
        $defC = max(0.0, $costC - $curC);
        $defD = max(0.0, $costD - $curD);

        if ($defM <= 0.0001 && $defC <= 0.0001 && $defD <= 0.0001) {
            return true; // Already covers target cost
        }

        $planetMSE = $curM + (2.0 * $curC) + (4.0 * $curD);
        $costMSE   = $costM + (2.0 * $costC) + (4.0 * $costD);

        if ($planetMSE < $costMSE - 0.0001) {
            return false; // Total MSE physically insufficient
        }

        $fuelReserve = ($contextReason === 'fleet' || strpos($contextReason, 'fleet') !== false) ? 15000000.0 : 25000.0;
        // Calculate surplus above target cost for each resource
        $surplusM = max(0.0, $curM - $costM);
        $surplusC = max(0.0, $curC - $costC);
        $surplusD = max(0.0, ($curD - $fuelReserve) - $costD);

        $surplusMSE = array(
            901 => $surplusM * 1.0,
            902 => $surplusC * 2.0,
            903 => $surplusD * 4.0,
        );

        // Find payment resource ($payResId) with highest surplus
        $payResId = 901;
        $maxSurplus = $surplusMSE[901];
        if ($surplusMSE[902] > $maxSurplus) {
            $payResId = 902;
            $maxSurplus = $surplusMSE[902];
        }
        if ($surplusMSE[903] > $maxSurplus) {
            $payResId = 903;
            $maxSurplus = $surplusMSE[903];
        }

        // Calculate required payment amount using $chargeRates
        $totalPayNeeded = 0.0;
        $exchanges = array();

        if ($payResId !== 901 && $defM > 0.0) {
            $costInPay = $defM * $chargeRates[$payResId][901];
            $totalPayNeeded += $costInPay;
            $exchanges[] = array('from_res' => $payResId, 'to_res' => 901, 'from_units' => $costInPay, 'to_units' => $defM);
        }
        if ($payResId !== 902 && $defC > 0.0) {
            $costInPay = $defC * $chargeRates[$payResId][902];
            $totalPayNeeded += $costInPay;
            $exchanges[] = array('from_res' => $payResId, 'to_res' => 902, 'from_units' => $costInPay, 'to_units' => $defC);
        }
        if ($payResId !== 903 && $defD > 0.0) {
            $costInPay = $defD * $chargeRates[$payResId][903];
            $totalPayNeeded += $costInPay;
            $exchanges[] = array('from_res' => $payResId, 'to_res' => 903, 'from_units' => $costInPay, 'to_units' => $defD);
        }

        $availPay = ($payResId === 901 ? $surplusM : ($payResId === 902 ? $surplusC : $surplusD));
        if ($availPay < $totalPayNeeded - 0.0001) {
            return false; // Insufficient surplus in selected currency
        }

        // Mass threshold check (>= 60M MSE or strategic tech / high value unlock)
        $tradedMSE = $totalPayNeeded * ($payResId === 901 ? 1.0 : ($payResId === 902 ? 2.0 : 4.0));
        $isStrategic = ($isEmergency || 
                        strpos($contextReason, '123') !== false || 
                        strpos($contextReason, '124') !== false || 
                        strpos($contextReason, '199') !== false || 
                        strpos($contextReason, 'strategic') !== false || 
                        $contextReason === 'high_value_unlock');
        if (!$isStrategic && $tradedMSE < 60000000.0 && $costMSE < 60000000.0) {
            return false;
        }

        // Debit 50 MO once
        if (!PremiumEconomy::debit($this->ctx->user, 921, $costMO, 'trader_call', 0, "exact_delta_trade;pid={$planetId};reason={$contextReason}")) {
            return false;
        }

        // Update physical resources
        $newM = $curM;
        $newC = $curC;
        $newD = $curD;

        if ($payResId === 901) {
            $newM -= $totalPayNeeded;
        } elseif ($payResId === 902) {
            $newC -= $totalPayNeeded;
        } else {
            $newD -= $totalPayNeeded;
        }

        if ($payResId !== 901 && $defM > 0.0) $newM += $defM;
        if ($payResId !== 902 && $defC > 0.0) $newC += $defC;
        if ($payResId !== 903 && $defD > 0.0) $newD += $defD;

        $db->update("UPDATE %%PLANETS%% SET metal = :m, crystal = :c, deuterium = :d WHERE id = :pid;", array(
            ':m'   => $newM,
            ':c'   => $newC,
            ':d'   => $newD,
            ':pid' => $planetId,
        ));
        self::updateCachedPlanetResources($planetId, $newM, $newC, $newD);

        if (isset($this->ctx->empire) && method_exists($this->ctx->empire, 'updateBodyData')) {
            $this->ctx->empire->updateBodyData($planetId, array(
                'metal'     => (float)$newM,
                'crystal'   => (float)$newC,
                'deuterium' => (float)$newD,
            ));
        }

        // Synchronize virtual budgets in budgetManager
        if (isset($this->ctx->budgetManager)) {
            $cat = 'mines';
            if (strpos($contextReason, 'fleet') !== false || strpos($contextReason, 'strike') !== false || strpos($contextReason, 'emergency') !== false) {
                $cat = 'fleet';
            } elseif (strpos($contextReason, 'defense') !== false) {
                $cat = 'defense';
            } elseif (strpos($contextReason, 'research') !== false) {
                $cat = 'research';
            }

            $this->ctx->budgetManager->syncDemandTraderRebalance($planetId, $cat, $newM, $newC, $newD, $exchanges, true, !$isBatchMode);
        }

        $this->ctx->decisionLog->record(
            'exact_delta_trade',
            "Mercader por Delta Exacto en planeta #{$planetId} ({$contextReason}): Pagados " . number_format($totalPayNeeded, 0) . " de res #{$payResId} para adquirir faltantes exactos (Coste: 50 MO)",
            array(
                'planet_id'      => $planetId,
                'target_cost'    => $targetCost,
                'pay_res'        => $payResId,
                'pay_spent'      => $totalPayNeeded,
                'exchanges'      => $exchanges,
                'resulting_m'    => $newM,
                'resulting_c'    => $newC,
                'resulting_d'    => $newD,
            ),
            "delta_trader_{$planetId}",
            4000.0
        );

        return true;
    }

    /**
     * Rebalance Planet Resources into 50% Metal, 35% Crystal, 15% Deuterium (MSE)
     * (Backward-compatible adapter that delegates to executeExactDeltaTrade)
     *
     * @param int $planetId
     * @param string $category 'fleet', 'defense', 'research', 'mines'
     * @param bool $isBatchMode
     * @return bool True if trade executed
     */
    public function rebalancePlanet50_35_15($planetId, $category, $isBatchMode = false)
    {
        $planetId = (int)$planetId;
        $pRow = self::getCachedPlanetRow($planetId);
        if (empty($pRow)) return false;

        $totMSE = (float)$pRow['metal'] + (2.0 * (float)$pRow['crystal']) + (4.0 * (float)$pRow['deuterium']);
        $tgtM = round(0.50 * $totMSE);
        $tgtC = round((0.35 * $totMSE) / 2.0);
        $tgtD = round((0.15 * $totMSE) / 4.0);
        $targetCost = array(901 => $tgtM, 902 => $tgtC, 903 => $tgtD);

        return $this->executeExactDeltaTrade($planetId, $targetCost, "demand_rebalance_{$category}", $isBatchMode);
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
        $db = Database::get();
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

        $planetRow = self::getCachedPlanetRow((int)$planetId);
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
        self::invalidatePlanetCache($planetId);

        if (isset($this->ctx->empire) && method_exists($this->ctx->empire, 'updateBodyData')) {
            $this->ctx->empire->updateBodyData($planetId, array(
                'metal'     => max(0.0, $physMetal - $totalMetCost),
                'crystal'   => max(0.0, $physCrystal - $totalCryCost),
                'deuterium' => (float)$planetRow['deuterium'] + $totalDeutGained,
            ));
        }

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
            $pInfo = self::getCachedPlanetRow($planetId);
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

        $planetRow = self::getCachedPlanetRow((int)$planetId);
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

        $costMSE = $costM + (2.0 * $costC) + (4.0 * $costD);
        if ($totalMSE < $costMSE - 0.0001) {
            return false;
        }

        // Calculate exact missing deltas
        $defM = max(0.0, $costM - $curM);
        $defC = max(0.0, $costC - $curC);
        $defD = max(0.0, $costD - $curD);

        // If already covered without trade
        if ($defM <= 0.0001 && $defC <= 0.0001 && $defD <= 0.0001) {
            return array(
                'exact_delta_trade'  => true,
                'rebalance_50_35_15' => true,
                'total_mse'          => $totalMSE,
                'cost_mse'           => $costMSE,
            );
        }

        // Rate matrix from Trader.php / self::CHARGE_RATES
        global $Charge;
        $chargeRates = !empty($Charge) ? $Charge : self::CHARGE_RATES;

        // Surplus above cost (reserving 15M fuel if category is fleet, 25k baseline)
        $fuelReserve = ($category === 'fleet') ? 15000000.0 : 25000.0;
        $surplusM = max(0.0, $curM - $costM);
        $surplusC = max(0.0, $curC - $costC);
        $surplusD = max(0.0, ($curD - $fuelReserve) - $costD);

        $surplusMSE = array(
            901 => $surplusM * 1.0,
            902 => $surplusC * 2.0,
            903 => $surplusD * 4.0,
        );

        $payResId = 901;
        $maxSurplus = $surplusMSE[901];
        if ($surplusMSE[902] > $maxSurplus) {
            $payResId = 902;
            $maxSurplus = $surplusMSE[902];
        }
        if ($surplusMSE[903] > $maxSurplus) {
            $payResId = 903;
            $maxSurplus = $surplusMSE[903];
        }

        $totalPayNeeded = 0.0;
        if ($payResId !== 901 && $defM > 0.0) $totalPayNeeded += $defM * $chargeRates[$payResId][901];
        if ($payResId !== 902 && $defC > 0.0) $totalPayNeeded += $defC * $chargeRates[$payResId][902];
        if ($payResId !== 903 && $defD > 0.0) $totalPayNeeded += $defD * $chargeRates[$payResId][903];

        $availPay = ($payResId === 901 ? $surplusM : ($payResId === 902 ? $surplusC : $surplusD));
        if ($availPay >= $totalPayNeeded - 0.0001) {
            return array(
                'exact_delta_trade'  => true,
                'rebalance_50_35_15' => true,
                'total_mse'          => $totalMSE,
                'cost_mse'           => $costMSE,
                'pay_res'            => $payResId,
                'pay_needed'         => $totalPayNeeded,
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
        return $strategist->executeExactDeltaTrade($planetId, $cost, "demand_unlock_{$category}");
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

            $lock = null;
            if (isset($this->ctx->budgetManager)) {
                $lock = $this->ctx->budgetManager->getSavingsLockForCategory($demand['planet_id'], $demand['category']);
                if (empty($lock) && $demand['category'] === 'research') {
                    $resLock = $this->ctx->budgetManager->getSavingsLockForCategory(0, 'research');
                    if (!empty($resLock) && (int)($resLock['planet_id'] ?? 0) === (int)$demand['planet_id']) {
                        $lock = $resLock;
                    }
                }
                if (empty($lock)) {
                    $genLock = $this->ctx->budgetManager->getSavingsLock();
                    if (!empty($genLock) && (int)($genLock['planet_id'] ?? 0) === (int)$demand['planet_id'] && ($genLock['category'] ?? '') === $demand['category']) {
                        $lock = $genLock;
                    }
                }
            }

            if (!empty($lock) && !empty($lock['cost']) && is_array($lock['cost'])) {
                $ok = $this->executeExactDeltaTrade($demand['planet_id'], $lock['cost'], "demand_unlock_" . $demand['category'], true);
            } else {
                $ok = $this->rebalancePlanet50_35_15($demand['planet_id'], $demand['category'], true);
            }

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
            $minesLock = $this->ctx->budgetManager->getSavingsLockForCategory($pId, 'mines');
            if (!empty($minesLock)) {
                return 'mines';
            }
            $resLock = $this->ctx->budgetManager->getSavingsLockForCategory(0, 'research');
            if (!empty($resLock) && (int)($resLock['planet_id'] ?? 0) === $pId) {
                return 'research';
            }
            $lock = $this->ctx->budgetManager->getSavingsLock();
            if (!empty($lock) && isset($lock['planet_id']) && (int)$lock['planet_id'] === $pId) {
                $lockCat = $lock['category'] ?? null;
                if (in_array($lockCat, array('fleet', 'defense', 'research', 'mines'))) {
                    return $lockCat;
                }
            }
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

        $botId = (int)$this->ctx->botId;
        $dailyKey = 'fair_trade_2305';
        $dailyLimit = 3;
        $n = PremiumEconomy::dailyCount($botId, $dailyKey);
        if ($n >= $dailyLimit) {
            return 0;
        }

        $amount = min($stardust, $dailyLimit - $n);

        // Debit Stardust
        if (!PremiumEconomy::debit($this->ctx->user, 923, $amount, 'fair_trade', 2305, "stardust_transmute;amt={$amount}")) {
            return 0;
        }

        // Credit 900 AM per Stardust
        $rewardAM = 900 * $amount;
        PremiumEconomy::credit($this->ctx->user, 922, $rewardAM, 'fair_trade', 2305, "stardust_transmute;amt={$amount}");

        // Increment daily counter
        for ($k = 0; $k < $amount; $k++) {
            PremiumEconomy::dailyIncrement($botId, $dailyKey);
        }

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
        $dailyKey2306 = 'fair_trade_2306';
        $dailyLimit2306 = 3;
        $n2306 = PremiumEconomy::dailyCount($botId, $dailyKey2306);
        if ($n2306 < $dailyLimit2306 && $userMO >= 12000.0 && $userAM < 2000.0) {
            $costMO = 9375.0;
            if (PremiumEconomy::debit($this->ctx->user, 921, $costMO, 'fair_trade', 2306, "mo_to_am")) {
                PremiumEconomy::credit($this->ctx->user, 922, 750, 'fair_trade', 2306, "mo_to_am");
                PremiumEconomy::dailyIncrement($botId, $dailyKey2306);

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

        $tPlanetEntry = $empire->getPlanet($targetPlanetId);
        $curData = !empty($tPlanetEntry) ? $tPlanetEntry['data'] : array();
        if (isset($empire) && method_exists($empire, 'updateBodyData')) {
            $empire->updateBodyData($targetPlanetId, array(
                'metal'           => (float)($curData['metal'] ?? 0.0) + $mBatch,
                'crystal'         => (float)($curData['crystal'] ?? 0.0) + $cBatch,
                'deuterium'       => (float)($curData['deuterium'] ?? 0.0) + $dBatch,
                $resource[204]    => (int)($curData[$resource[204]] ?? 0) + (25 * $batchToOpen),
                $resource[205]    => (int)($curData[$resource[205]] ?? 0) + (15 * $batchToOpen),
                $resource[206]    => (int)($curData[$resource[206]] ?? 0) + (10 * $batchToOpen),
            ));
        }

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
            $tInfo = self::getCachedPlanetRow($targetPlanetId);
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

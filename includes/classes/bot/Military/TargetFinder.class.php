<?php

require_once 'includes/classes/bot/Perception/PublicDataReader.class.php';
require_once 'includes/classes/bot/Evaluation/EconomyValuator.class.php';
require_once 'includes/classes/bot/Evaluation/CombatOracle.class.php';
require_once 'includes/classes/bot/Evaluation/SurrogateCombatModel.class.php';
require_once 'includes/classes/bot/Military/TargetBlacklist.class.php';

/**
 * NovaRush Bot AI v2 - Target Finder (Advanced EV/Hour & Anti-Trap Targeting)
 *
 * Implements:
 * 1. Net EV per Hour Core Principle:
 *    EV(t) = P(win) * (loot(t) + debris(t) + valEstrategico(t)) - (1 - P(win)) * valorFlotaArriesgada - combustible(t) - MSE_misiles(t)
 *    Valor(t) = EV(t) / horasIdaVuelta(t)
 * 2. Projected Loot at Impact:
 *    lootEstimado = min(storageCap, spied + produccion * (tImpacto - spyTime)) * lootFraction
 *    Heavily penalizes targets with recent activity ('*') to avoid ninjas and baits.
 * 3. Anti-Traps and Anti-Baits:
 *    Filters out baits (active players, active enemy moons with phalanx) and unprofitable turtles (EV <= 0).
 * 4. Parallel Target Classification (Relative Thresholds):
 *    - fleet_crash: Enemy fleet on ground with 2-stage Monte Carlo ROI >= 30%
 *    - farming: Loot >= 5% empire hourly production with dynamic risk scaling
 *    - siege: Empire possesses in-range MIPs to destroy >= 50% defense structure
 *    - recon: Unexplored targets
 */
class BotTargetFinder
{
    private $ctx;

    public function __construct(BotContext $ctx)
    {
        $this->ctx = $ctx;
    }

    /**
     * Find and classify prospective targets across the entire empire
     *
     * @param BotEmpireState|array $empireOrBase
     * @param int $maxTargets
     * @param BotWorldModel|null $world
     * @return array List of scored targets
     */
    public function findTargets($empireOrBase, $maxTargets = 16, $world = null)
    {
        global $resource, $reslist, $pricelist, $CombatCaps;

        // Total bot empire hourly production in MSE for relative calculations
        $empireHourlyProdMSE = 50000.0;
        if ($empireOrBase instanceof BotEmpireState) {
            $empireHourlyProdMSE = $empireOrBase->getHourlyProductionMSE();
        } elseif ($world !== null && isset($world->empire)) {
            $empireHourlyProdMSE = $world->empire->getHourlyProductionMSE();
        }

        // 1. Gather all colonies
        $allColonies = array();
        if ($empireOrBase instanceof BotEmpireState) {
            $allColonies = $empireOrBase->getPlanets();
        } elseif (is_array($empireOrBase)) {
            if (isset($empireOrBase['galaxy'])) {
                $allColonies = array($empireOrBase['id'] => $empireOrBase);
            } else {
                $allColonies = $empireOrBase;
            }
        }

        if (empty($allColonies)) {
            return array();
        }

        $radius = (int)$this->ctx->difficulty->targetScanRadius;

        // Check if intergalactic scanning is permitted by difficulty & tech
        $hyperDrive = isset($this->ctx->user[$resource[118]]) ? (int)$this->ctx->user[$resource[118]] : 0;
        $allowIntergalactic = ($this->ctx->difficulty->intergalacticScan && $hyperDrive >= 6);

        // 2. Aggregate system scan intervals per galaxy
        $intervalsPerGalaxy = array();
        foreach ($allColonies as $colony) {
            $cG = (int)$colony['galaxy'];
            $cS = (int)$colony['system'];

            $startSys = max(1, $cS - $radius);
            $endSys   = min(500, $cS + $radius);
            $intervalsPerGalaxy[$cG][] = array('start' => $startSys, 'end' => $endSys);

            // Intergalactic coverage for adjacent galaxies
            if ($allowIntergalactic) {
                $adjRadius = (int)floor($radius * 0.5);
                foreach (array($cG - 1, $cG + 1) as $adjG) {
                    if ($adjG >= 1 && $adjG <= 9) {
                        $intervalsPerGalaxy[$adjG][] = array(
                            'start' => max(1, $cS - $adjRadius),
                            'end'   => min(500, $cS + $adjRadius)
                        );
                    }
                }
            }
        }

        // 3. Merge overlapping / adjacent intervals per galaxy
        $mergedRanges = array();
        foreach ($intervalsPerGalaxy as $g => $intervals) {
            usort($intervals, function($a, $b) {
                return $a['start'] <=> $b['start'];
            });

            $currentStart = $intervals[0]['start'];
            $currentEnd   = $intervals[0]['end'];

            for ($i = 1; $i < count($intervals); $i++) {
                if ($intervals[$i]['start'] <= ($currentEnd + 1)) {
                    $currentEnd = max($currentEnd, $intervals[$i]['end']);
                } else {
                    $mergedRanges[] = array('galaxy' => $g, 'start' => $currentStart, 'end' => $currentEnd);
                    $currentStart = $intervals[$i]['start'];
                    $currentEnd   = $intervals[$i]['end'];
                }
            }
            $mergedRanges[] = array('galaxy' => $g, 'start' => $currentStart, 'end' => $currentEnd);
        }

        // 4. Batch query celestial bodies across all covered sectors
        $planets = BotPublicDataReader::getGalaxySystemsBatch($mergedRanges, Universe::current());
        if (empty($planets)) {
            return array();
        }

        $candidates = array();
        $seenCoords = array();

        $gameSpeed = FleetFunctions::GetGameSpeedFactor();

        $db = Database::get();
        $botRows = $db->select("SELECT bot_id FROM " . DB_PREFIX . "bots;");
        $allBotIds = array();
        if (is_array($botRows)) {
            foreach ($botRows as $bRow) {
                $allBotIds[(int)$bRow['bot_id']] = true;
            }
        }

        foreach ($planets as $p) {
            // Deduplicate coordinates
            $coordKey = "{$p['galaxy']}:{$p['system']}:{$p['planet']}";
            if (isset($seenCoords[$coordKey])) {
                continue;
            }
            $seenCoords[$coordKey] = true;

            // Do not target own planets, other bots, vacant slots, or administrator accounts
            if ($p['owner_id'] == $this->ctx->botId || isset($allBotIds[(int)$p['owner_id']]) || $p['owner_id'] <= 0 || (int)$p['owner_id'] === 1 || (!empty($p['authlevel']) && (int)$p['authlevel'] > 0) || strtolower((string)$p['username']) === 'admin') {
                continue;
            }

            // NovaRush Anti-Bashing & Target Rotation: Skip blacklisted players/planets (max 3 attacks per player in 24h)
            $pId = isset($p['planet_id']) ? (int)$p['planet_id'] : (int)($p['id'] ?? 0);
            $ownerId = (int)$p['owner_id'];
            if (BotTargetBlacklist::isBlacklisted($this->ctx->botId, $ownerId, $pId, (int)$p['galaxy'], (int)$p['system'], (int)$p['planet'])) {
                continue;
            }

            // Skip vacation mode
            if (!empty($p['is_vacation'])) {
                continue;
            }

            // Find closest bot colony to target (candidate FOB)
            $minDistance = PHP_INT_MAX;
            $nearestColonyId = 0;
            $nearestColony = null;
            foreach ($allColonies as $cId => $colony) {
                $cG = (int)$colony['galaxy'];
                $cS = (int)$colony['system'];

                if ($cG == $p['galaxy']) {
                    $dist = abs($cS - $p['system']);
                } else {
                    $dist = 1000 + (abs($cG - $p['galaxy']) * 500) + abs($cS - $p['system']);
                }

                if ($dist < $minDistance) {
                    $minDistance = $dist;
                    $nearestColonyId = (int)$cId;
                    $nearestColony = $colony;
                }
            }

            // Calculate estimated flight duration & roundtrip hours
            $startCoords = array($nearestColony['galaxy'], $nearestColony['system'], $nearestColony['planet']);
            $targetCoords = array($p['galaxy'], $p['system'], $p['planet']);
            $realDist = FleetFunctions::GetTargetDistance($startCoords, $targetCoords);
            // Standard cruise speed: ~15,000
            $flightSecs = FleetFunctions::GetMissionDuration(10, 15000, $realDist, $gameSpeed, $this->ctx->user);
            $roundtripHours = max(0.05, (2.0 * $flightSecs) / 3600.0);

            // Fetch intelligence if world model is provided
            $intel = null;
            if ($world !== null && isset($world->intel)) {
                $intel = $world->intel->getIntel($p['galaxy'], $p['system'], (int)$p['planet'], 1, 4.0);
            }

            $category = 'recon';
            $score    = 0.0;
            $debrisValMSE = 0.0;
            $lootMSE      = 0.0;
            $defenseMSE   = 0.0;
            $netEV        = 0.0;
            $valorHora    = 0.0;

            // Anti-Trap & Anti-Bait Guard:
            // Check recent activity marker '*' (active in last 15 mins)
            $isRecentlyActive = ($p['activity_marker'] === '*' || (is_numeric($p['activity_marker']) && (int)$p['activity_marker'] < 15));

            if ($intel !== null && $intel['confidence'] >= 0.4) {
                // 1. Calculate Fleet Value on Ground
                $fleetMSE = 0.0;
                if (!empty($intel['fleet']) && is_array($intel['fleet'])) {
                    foreach ($intel['fleet'] as $sId => $cnt) {
                        if ($sId == 212 || $cnt <= 0) continue; // Skip satellites
                        $fleetMSE += BotEconomyValuator::getElementPriceMSE($sId, $cnt);
                    }
                }
                $config = Config::get();
                $fleetCdrFactor = ((float)$config->Fleet_Cdr) / 100.0;
                $defsCdrFactor  = ((float)$config->Defs_Cdr) / 100.0;

                $debrisValMSE = $fleetMSE * $fleetCdrFactor;
                if ($defsCdrFactor > 0 && !empty($intel['defense']) && is_array($intel['defense'])) {
                    $defenseMSE = 0.0;
                    foreach ($intel['defense'] as $dId => $dCnt) {
                        if ($dId == 502 || $dCnt <= 0) continue;
                        $defenseMSE += BotEconomyValuator::getElementPriceMSE($dId, $dCnt);
                    }
                    $debrisValMSE += $defenseMSE * $defsCdrFactor;
                }

                // 2. Projected Loot at Impact (Section 2.2)
                $scannedAt = !empty($intel['scanned_at']) ? (int)$intel['scanned_at'] : TIMESTAMP;
                $deltaHours = max(0.0, (TIMESTAMP + $flightSecs - $scannedAt) / 3600.0);

                // Estimate hourly production from buildings if scouted, else defaults
                $prodM = 1000.0;
                $prodC = 500.0;
                $prodD = 200.0;
                if (!empty($intel['buildings']) && is_array($intel['buildings'])) {
                    $mLevel = (int)($intel['buildings'][1] ?? 0);
                    $cLevel = (int)($intel['buildings'][2] ?? 0);
                    $dLevel = (int)($intel['buildings'][3] ?? 0);
                    if ($mLevel > 0) $prodM = 30.0 * $mLevel * pow(1.1, $mLevel);
                    if ($cLevel > 0) $prodC = 20.0 * $cLevel * pow(1.1, $cLevel);
                    if ($dLevel > 0) $prodD = 10.0 * $dLevel * pow(1.1, $dLevel);
                }

                $storageCap = 500000000.0;
                $projM = min($storageCap, (float)($intel['metal'] ?? 0) + ($prodM * $deltaHours));
                $projC = min($storageCap, (float)($intel['crystal'] ?? 0) + ($prodC * $deltaHours));
                $projD = min($storageCap, (float)($intel['deuterium'] ?? 0) + ($prodD * $deltaHours));

                $lootMetal = $projM * 0.50;
                $lootCry   = $projC * 0.50;
                $lootDeut  = $projD * 0.50;
                $lootMSE   = BotEconomyValuator::toMSE($lootMetal, $lootCry, $lootDeut);

                // 3. Calculate Defense Threat
                if (!empty($intel['defense']) && is_array($intel['defense'])) {
                    foreach ($intel['defense'] as $dId => $cnt) {
                        if ($cnt <= 0) continue;
                        $defenseMSE += BotEconomyValuator::getElementPriceMSE($dId, $cnt);
                    }
                }

                // Penalize heavily if active recently (avoid ninjas / baits)
                if ($isRecentlyActive) {
                    $lootMSE *= 0.15; // 85% penalty due to risk of ninja/evacuation
                }

                // --- RELATIVE CLASSIFICATION ARCHITECTURE ---
                $defenseScouted = !empty($intel['defense_scouted']);

                // 1. EVALUATION: fleet_crash (Enemy fleet on ground with 2-stage Monte Carlo ROI >= 30%)
                $isFleetCrashViable = false;
                $fcWinProb = 0.90;
                $fcNetProfit = 0.0;
                $fcRoi = 0.0;

                if ($fleetMSE > 0 && !empty($intel['fleet'])) {
                    $combatFleet = array();
                    if (!empty($nearestColony['data'])) {
                        foreach ($reslist['fleet'] as $sId) {
                            if ($sId == 210 || $sId == 212 || in_array($sId, array(208, 209, 219))) continue;
                            $cnt = isset($nearestColony['data'][$resource[$sId]]) ? (int)$nearestColony['data'][$resource[$sId]] : 0;
                            if ($cnt > 0) $combatFleet[$sId] = $cnt;
                        }
                    }
                    if (empty($combatFleet) && $world !== null && isset($world->empire)) {
                        foreach ($world->empire->getAllBodies() as $b) {
                            if (!empty($b['data'])) {
                                foreach ($reslist['fleet'] as $sId) {
                                    if ($sId == 210 || $sId == 212 || in_array($sId, array(208, 209, 219))) continue;
                                    $cnt = isset($b['data'][$resource[$sId]]) ? (int)$b['data'][$resource[$sId]] : 0;
                                    if ($cnt > 0) $combatFleet[$sId] = ($combatFleet[$sId] ?? 0) + $cnt;
                                }
                            }
                        }
                    }

                    if (!empty($combatFleet)) {
                        $targetDefenders = (isset($intel['defense']) && is_array($intel['defense']) ? $intel['defense'] : array())
                                         + (isset($intel['fleet']) && is_array($intel['fleet']) ? $intel['fleet'] : array());

                        $targetFactors = BotCombatOracle::resolveDefenderFactors((int)$p['owner_id'], is_array($intel) ? $intel : array());

                        // Stage 1: Fast Surrogate Filter (Analytic)
                        $surrogate = BotSurrogateCombatModel::estimate(
                            $combatFleet,
                            $targetDefenders,
                            $this->ctx->user['factor'] ?? array(),
                            $targetFactors
                        );

                        if ($surrogate['win_prob'] >= 0.50) {
                            // Stage 2: Fast Monte Carlo (2 iterations) for high-efficiency verification
                            $sim = BotCombatOracle::evaluate(
                                $combatFleet,
                                $targetDefenders,
                                array('id' => $this->ctx->botId, 'factor' => $this->ctx->user['factor'] ?? array()),
                                array('id' => (int)$p['owner_id'], 'factor' => $targetFactors),
                                2
                            );

                            $fcWinProb = isset($sim['win_probability']) ? (float)$sim['win_probability'] : 0.0;
                            if ($fcWinProb >= 0.50) {
                                $deployedMSE = 0.0;
                                foreach ($combatFleet as $sId => $cnt) {
                                    $deployedMSE += BotEconomyValuator::getElementPriceMSE($sId, $cnt);
                                }
                                $attLossPct = isset($sim['att_loss_pct']) ? (float)$sim['att_loss_pct'] : 0.20;
                                $attLossMSE = isset($sim['att_loss_mse']) ? (float)$sim['att_loss_mse'] : ($deployedMSE * $attLossPct);
                                $defLossPct = isset($sim['def_loss_pct']) ? (float)$sim['def_loss_pct'] : 0.90;

                                // Recycler capacity check on nearest colony
                                global $resource;
                                $recs219 = isset($nearestColony['data'][$resource[219]]) ? (int)$nearestColony['data'][$resource[219]] : 0;
                                $recs209 = isset($nearestColony['data'][$resource[209]]) ? (int)$nearestColony['data'][$resource[209]] : 0;
                                $availableRecCap = ($recs219 * 500000.0) + ($recs209 * 20000.0);

                                $projectedDebris = ($fleetMSE * $defLossPct * $fleetCdrFactor) + ($defenseMSE * $defLossPct * $defsCdrFactor);
                                $realizableDebris = ($availableRecCap > 0) ? min($projectedDebris, $availableRecCap) : 0.0;

                                $fuelMSE = ($realDist * 15.0);
                                $netReplacementCost = ($attLossMSE * (1.0 - $fleetCdrFactor)) + $fuelMSE;
                                $grossRevenue = $lootMSE + $realizableDebris;
                                $fcNetProfit = $grossRevenue - $netReplacementCost;
                                $fcRoi = $fcNetProfit / max(1.0, ($attLossMSE + $fuelMSE));

                                if ($fcNetProfit > 0 && $fcRoi >= 0.30) {
                                    $isFleetCrashViable = true;
                                }
                            }
                        }
                    }
                }

                // 2. EVALUATION: siege (MIPs across empire sufficient to destroy >= 50% defense structure)
                $canDemolish50Pct = false;
                $totalMipsInRange = 0;

                if ($defenseScouted && $defenseMSE > 0 && $world !== null && isset($world->empire)) {
                    $impulse = isset($this->ctx->user[$resource[117]]) ? (int)$this->ctx->user[$resource[117]] : 0;
                    $range   = FleetFunctions::getMissileRange($impulse);

                    foreach ($world->empire->getAllBodies() as $bBody) {
                        $bData = $bBody['data'];
                        if ((int)$bData['galaxy'] !== (int)$p['galaxy']) continue;
                        if (abs((int)$bData['system'] - (int)$p['system']) > $range) continue;

                        $silo = isset($bData[$resource[44]]) ? (int)$bData[$resource[44]] : 0;
                        $mips = isset($bData[$resource[503]]) ? (int)$bData[$resource[503]] : 0;
                        if ($silo >= 4 && $mips > 0) {
                            $totalMipsInRange += $mips;
                        }
                    }

                    $abmCount = isset($intel['defense'][502]) ? (int)$intel['defense'][502] : 0;
                    $effectiveMips = max(0, $totalMipsInRange - $abmCount);

                    $defTech = array();
                    if (isset($intel['techs']) && is_array($intel['techs'])) {
                        $defTech = $intel['techs'];
                    } elseif (isset($intel['tech']) && is_array($intel['tech'])) {
                        $defTech = $intel['tech'];
                    }
                    $targetDefTech = max((int)($defTech[110] ?? 0), (int)($defTech[111] ?? 0));

                    $totalDefStructurePoints = 0.0;
                    if (!empty($intel['defense']) && is_array($intel['defense'])) {
                        foreach ($intel['defense'] as $uId => $cnt) {
                            if ($uId == 502 || $cnt <= 0) continue;
                            $costM = isset($pricelist[$uId]['cost'][901]) ? (float)$pricelist[$uId]['cost'][901] : 0.0;
                            $costC = isset($pricelist[$uId]['cost'][902]) ? (float)$pricelist[$uId]['cost'][902] : 0.0;
                            $unitStructure = ($costM + $costC) * (1.0 + $targetDefTech * 0.1) / 10.0;
                            $totalDefStructurePoints += ($unitStructure * $cnt);
                        }
                    }

                    $militaryTechAtk = isset($this->ctx->user['military_tech']) ? (int)$this->ctx->user['military_tech'] : 0;
                    $ipmDamagePool = $effectiveMips * 12000.0 * (1.0 + $militaryTechAtk * 0.1);

                    if ($totalDefStructurePoints > 0 && $ipmDamagePool >= (0.50 * $totalDefStructurePoints)) {
                        $canDemolish50Pct = true;
                    }
                }

                // 3. EVALUATION: farming (Dynamic risk scaling: min 5% empire hourly prod, scales when def > 50% loot)
                $defRatio = ($lootMSE > 0) ? ($defenseMSE / $lootMSE) : 999.0;
                if ($defRatio <= 0.50) {
                    $minLootFactor = 0.05;
                } else {
                    // Formula acordada: 0.05 + (1 + 2 * (defensa / botin - 0.40))
                    $minLootFactor = 0.05 + (1.0 + 2.0 * ($defRatio - 0.40));
                }
                $requiredMinLootMSE = $empireHourlyProdMSE * $minLootFactor;
                $isFarmingViable = ($defenseScouted && $lootMSE >= $requiredMinLootMSE);

                // --- CLASSIFICATION ASSIGNMENT ---
                if ($fleetMSE > 0 && $isFleetCrashViable) {
                    if ($defenseMSE > 50000000 && $defenseMSE > ($fleetMSE * 0.5) && $canDemolish50Pct) {
                        $category = 'siege';
                    } else {
                        $category = 'fleet_crash';
                    }
                } elseif ($canDemolish50Pct && $defenseScouted) {
                    $category = 'siege';
                } elseif ($isFarmingViable) {
                    $category = 'farming';
                } elseif ($lootMSE >= ($empireHourlyProdMSE * 0.05) && !$defenseScouted) {
                    $category = 'recon_heavy';
                } else {
                    $category = 'low_value';
                }

                // Anti-Bait Guard: Discard active player with moon having phalanx
                if ($isRecentlyActive && !empty($p['id_luna']) && $category !== 'low_value') {
                    // Juicy planet with active player + moon is a textbook bait!
                    continue;
                }

                // --- SECTION 2.1: EV NETO POR HORA FORMULA ---
                // EV(t) = P(win) * (loot(t) + debris(t) + valEstrategico(t)) - (1 - P(win)) * valorFlotaArriesgada - combustible(t) - MSE_misiles(t)
                $pWin = 0.90;
                $strategicVal = 0.0;
                $riskFleetMSE = ($defenseMSE + $fleetMSE) * 1.3;
                $fuelMSE = ($realDist * 15.0);
                $missilesMSE = 0.0;

                if ($category === 'fleet_crash') {
                    $pWin = $isRecentlyActive ? max(0.50, $fcWinProb * 0.8) : $fcWinProb;
                    $strategicVal = $debrisValMSE * 0.25;
                    $riskFleetMSE = ($defenseMSE + $fleetMSE) * 1.3;
                    $fuelMSE = ($realDist * 15.0);
                    $missilesMSE = 0.0;
                } elseif ($category === 'farming') {
                    $pWin = $isRecentlyActive ? 0.80 : 0.99;
                    $strategicVal = 0.0;
                    $riskFleetMSE = max(50000.0, $defenseMSE * 0.5);
                    $fuelMSE = ($realDist * 5.0);
                    $missilesMSE = 0.0;
                } elseif ($category === 'siege') {
                    $pWin = 0.85;
                    $strategicVal = 500000.0;
                    $riskFleetMSE = ($defenseMSE + $fleetMSE) * 1.0;
                    $fuelMSE = ($realDist * 15.0);
                    $missilesMSE = $totalMipsInRange * BotEconomyValuator::getElementPriceMSE(503);
                }

                $prodMseHourly = BotEconomyValuator::toMSE($prodM, $prodC, $prodD);

                // Compute Net EV
                $netEV = ($pWin * ($lootMSE + $debrisValMSE + $strategicVal)) - ((1.0 - $pWin) * $riskFleetMSE) - $fuelMSE - $missilesMSE;

                // Anti-Turtle Guard: Discard unprofitable turtles (EV <= 0)
                if ($netEV <= 0 && in_array($category, array('siege', 'farming', 'fleet_crash'))) {
                    continue;
                }

                // Valor(t) = EV(t) / horasIdaVuelta(t) (Sección 0 & 2.1 Principio Rector)
                $valorHora = $netEV / $roundtripHours;

                // Score strictly based on Valor = EV_neto / horas_ida_vuelta
                if (in_array($category, array('fleet_crash', 'siege', 'farming'))) {
                    $score = max(50.0, $valorHora);
                } elseif ($category === 'recon_heavy') {
                    $score = max(50.0, ($lootMSE / 1000.0) - ($minDistance * 0.5));
                } else {
                    $score = max(10.0, ($lootMSE / 5000.0) - ($minDistance * 1.5));
                }
            } else {
                $prodMseHourly = 5000.0;
                // Unexplored target: Score for Reconnaissance based on public galaxy activity markers
                $category = 'recon';
                $score = 1000.0;
                if ($p['activity_marker'] === 'none') {
                    $score += 600.0; // Inactive player
                } elseif (is_numeric($p['activity_marker']) && $p['activity_marker'] >= 30) {
                    $score += 300.0;
                }

                if ($p['has_debris']) {
                    $pubDebris = $p['debris_metal'] + ($p['debris_crystal'] * 2);
                    $score += min(600.0, $pubDebris / 100000.0);
                }
                $score -= ($minDistance * 1.0);
            }

            if ($score > 50.0) {
                $candidates[] = array(
                    'target'            => $p,
                    'category'          => $category,
                    'score'             => round($score, 2),
                    'distance'          => $minDistance,
                    'nearest_colony_id' => $nearestColonyId,
                    'loot_mse'          => $lootMSE,
                    'debris_mse'        => $debrisValMSE,
                    'defense_mse'       => $defenseMSE,
                    'net_ev'            => round($netEV, 2),
                    'valor_hora'        => round($valorHora, 2),
                    'roundtrip_hours'   => round($roundtripHours, 2),
                    'prod_mse_hourly'   => round($prodMseHourly, 2),
                );
            }
        }

        // Sort descending by score
        usort($candidates, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return array_slice($candidates, 0, $maxTargets);
    }
}

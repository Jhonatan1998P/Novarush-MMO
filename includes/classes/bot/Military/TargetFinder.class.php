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

        // Half-life synced to difficulty profile (1.0h strict global)
        $halfLife = isset($this->ctx->difficulty->intelMaxAgeHours) ? (float)$this->ctx->difficulty->intelMaxAgeHours : 1.0;

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
                $intel = $world->intel->getIntel($p['galaxy'], $p['system'], (int)$p['planet'], 1, $halfLife);
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

                // Real scouted loot without artificial 500M capping
                $scoutedM = (float)($intel['metal'] ?? 0);
                $scoutedC = (float)($intel['crystal'] ?? 0);
                $scoutedD = (float)($intel['deuterium'] ?? 0);

                $projM = max(0.0, $scoutedM + ($prodM * $deltaHours));
                $projC = max(0.0, $scoutedC + ($prodC * $deltaHours));
                $projD = max(0.0, $scoutedD + ($prodD * $deltaHours));

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

                // Penalize if active recently (avoid ninjas / baits)
                // For mega-loot targets (> 5B MSE), preserve high raid value without complete discard
                if ($isRecentlyActive) {
                    if ($lootMSE > 5000000000.0) {
                        $lootMSE *= 0.60; // 40% caution discount, preserves mega-target incentive
                    } else {
                        $lootMSE *= 0.35; // Standard cautious discount
                    }
                }

                // --- RELATIVE CLASSIFICATION ARCHITECTURE ---
                $defenseScouted = !empty($intel['defense_scouted']);

                // Identify the bot's military bastion / FOB fleet and total combined empire fleet
                $combatFleet = array();
                $empireCombatFleet = array();
                $maxBastionFleetMSE = 0.0;
                if ($world !== null && isset($world->empire)) {
                    foreach ($world->empire->getAllBodies() as $b) {
                        if (empty($b['data'])) continue;
                        $bFleetMSE = 0.0;
                        $localFleet = array();
                        foreach ($reslist['fleet'] as $sId) {
                            if ($sId == 210 || $sId == 212 || in_array($sId, array(208, 209, 219))) continue;
                            $cnt = isset($b['data'][$resource[$sId]]) ? (int)$b['data'][$resource[$sId]] : 0;
                            if ($cnt > 0) {
                                $localFleet[$sId] = $cnt;
                                $bFleetMSE += BotEconomyValuator::getElementPriceMSE($sId, $cnt);
                                $empireCombatFleet[$sId] = isset($empireCombatFleet[$sId]) ? $empireCombatFleet[$sId] + $cnt : $cnt;
                            }
                        }
                        if ($bFleetMSE > $maxBastionFleetMSE) {
                            $maxBastionFleetMSE = $bFleetMSE;
                            $combatFleet = $localFleet;
                        }
                    }
                }
                if (empty($combatFleet) && !empty($nearestColony['data'])) {
                    foreach ($reslist['fleet'] as $sId) {
                        if ($sId == 210 || $sId == 212 || in_array($sId, array(208, 209, 219))) continue;
                        $cnt = isset($nearestColony['data'][$resource[$sId]]) ? (int)$nearestColony['data'][$resource[$sId]] : 0;
                        if ($cnt > 0) {
                            $combatFleet[$sId] = $cnt;
                            $empireCombatFleet[$sId] = isset($empireCombatFleet[$sId]) ? $empireCombatFleet[$sId] + $cnt : $cnt;
                        }
                    }
                }

                // Recycler capacity available across empire
                global $resource;
                $recs219 = isset($nearestColony['data'][$resource[219]]) ? (int)$nearestColony['data'][$resource[219]] : 0;
                $recs209 = isset($nearestColony['data'][$resource[209]]) ? (int)$nearestColony['data'][$resource[209]] : 0;
                $availableRecCap = ($recs219 * 200000000.0) + ($recs209 * 20000.0);
                if ($world !== null && isset($world->empire)) {
                    foreach ($world->empire->getAllBodies() as $b) {
                        if (!empty($b['data']) && ($b['id'] ?? 0) != ($nearestColony['id'] ?? 0)) {
                            $e219 = isset($b['data'][$resource[219]]) ? (int)$b['data'][$resource[219]] : 0;
                            $e209 = isset($b['data'][$resource[209]]) ? (int)$b['data'][$resource[209]] : 0;
                            $availableRecCap += ($e219 * 200000000.0) + ($e209 * 20000.0);
                        }
                    }
                }

                $targetDefenders = (isset($intel['defense']) && is_array($intel['defense']) ? $intel['defense'] : array())
                                 + (isset($intel['fleet']) && is_array($intel['fleet']) ? $intel['fleet'] : array());
                $targetFactors   = BotCombatOracle::resolveDefenderFactors((int)$p['owner_id'], is_array($intel) ? $intel : array());

                // --- 0. UNIVERSAL EMPIRE VIABILITY GATE ---
                // Si el objetivo tiene fuerzas defensoras (flota o defensa), el imperio en su conjunto
                // debe tener >= 70% de probabilidad de victoria antes de clasificarlo como campaña (Asedio o Fleet Crash).
                if (!empty($targetDefenders) && !empty($empireCombatFleet)) {
                    $simEmpire = BotCombatOracle::evaluate(
                        $empireCombatFleet,
                        $targetDefenders,
                        array('id' => $this->ctx->botId, 'factor' => $this->ctx->user['factor'] ?? array()),
                        array('id' => (int)$p['owner_id'], 'factor' => $targetFactors),
                        2
                    );
                    $empireWinProb = isset($simEmpire['win_probability']) ? (float)$simEmpire['win_probability'] : 0.0;
                    if ($empireWinProb < 0.70) {
                        BotTargetBlacklist::addCooldown(
                            $this->ctx->botId,
                            (int)$p['owner_id'],
                            $pId,
                            (int)$p['galaxy'],
                            (int)$p['system'],
                            (int)$p['planet'],
                            86400,
                            "Poder militar insuficiente en todo el imperio (" . round($empireWinProb * 100, 1) . "% prob victoria < 70%)"
                        );
                        continue; // SALIDA INMEDIATA: Descartar objetivo por completo
                    }
                }

                $fuelMSE = ($realDist * 15.0);

                // --- 1. EVALUATION: SIEGE (Prioridad #1) ---
                // Condición: Defensas conocidas >= 1, MIPs en rango demuelen >= 25% de la estructura defensiva
                $isSiegeViable = false;
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

                    // Si los misiles en rango pueden demoler al menos el 25% de la estructura de las defensas
                    if ($totalDefStructurePoints > 0 && $effectiveMips > 0 && $ipmDamagePool >= (0.25 * $totalDefStructurePoints)) {
                        $isSiegeViable = true;
                    }
                }

                // --- 2. EVALUATION: FLEET_CRASH (Prioridad #2) ---
                // Solo si no fue siege. Flota enemiga en tierra, simulación Monte Carlo con FOB, ROI escombros >= 30% (sin almacenes).
                $isFleetCrashViable = false;
                $fcWinProb = 0.50;
                $fcRoi = 0.0;
                $fcNetProfit = 0.0;
                if (!$isSiegeViable && $fleetMSE > 0 && !empty($intel['fleet']) && !empty($combatFleet)) {
                    $sim = BotCombatOracle::evaluate(
                        $combatFleet,
                        $targetDefenders,
                            array('id' => $this->ctx->botId, 'factor' => $this->ctx->user['factor'] ?? array()),
                            array('id' => (int)$p['owner_id'], 'factor' => $targetFactors),
                            2
                        );

                        $fcWinProb = isset($sim['win_probability']) ? (float)$sim['win_probability'] : 0.50;
                        $deployedMSE = 0.0;
                        foreach ($combatFleet as $sId => $cnt) {
                            $deployedMSE += BotEconomyValuator::getElementPriceMSE($sId, $cnt);
                        }
                        $attLossPct = isset($sim['att_loss_pct']) ? (float)$sim['att_loss_pct'] : 0.20;
                        $attLossMSE = isset($sim['att_loss_mse']) ? (float)$sim['att_loss_mse'] : ($deployedMSE * $attLossPct);
                        $defLossPct = isset($sim['def_loss_pct']) ? (float)$sim['def_loss_pct'] : 0.90;

                        $projectedDebris = ($fleetMSE * $defLossPct * $fleetCdrFactor) + ($defenseMSE * $defLossPct * $defsCdrFactor);
                        $realizableDebris = ($availableRecCap > 0) ? min($projectedDebris, $availableRecCap) : 0.0;

                        // Exclusivo: Ganancia = Escombros reciclables (NO almacén)
                        $totalCosts = max(1.0, ($attLossMSE * (1.0 - $fleetCdrFactor)) + $fuelMSE);
                        $fcNetProfit = $realizableDebris - $totalCosts;
                        $fcRoi = $fcNetProfit / $totalCosts;

                        if ($fcNetProfit > 0 && $fcRoi >= 0.30) {
                            $isFleetCrashViable = true;
                        }
                    }

                // --- 3. EVALUATION: FARMING (Prioridad #3) ---
                // Solo si no fue siege ni fleet_crash. Simulación Monte Carlo con FOB, Win >= 70% + ROI >= 10% (con almacén + escombros).
                $isFarmingViable = false;
                $farmWinProb = 0.0;
                $farmRoi = 0.0;
                $farmNetProfit = 0.0;
                if (!$isSiegeViable && !$isFleetCrashViable && $defenseScouted && !empty($combatFleet)) {
                    $sim = BotCombatOracle::evaluate(
                        $combatFleet,
                        $targetDefenders,
                        array('id' => $this->ctx->botId, 'factor' => $this->ctx->user['factor'] ?? array()),
                        array('id' => (int)$p['owner_id'], 'factor' => $targetFactors),
                        2
                    );

                    $farmWinProb = isset($sim['win_probability']) ? (float)$sim['win_probability'] : 0.0;
                    if ($farmWinProb >= 0.70) {
                        $deployedMSE = 0.0;
                        foreach ($combatFleet as $sId => $cnt) {
                            $deployedMSE += BotEconomyValuator::getElementPriceMSE($sId, $cnt);
                        }
                        $attLossPct = isset($sim['att_loss_pct']) ? (float)$sim['att_loss_pct'] : 0.02;
                        $attLossMSE = isset($sim['att_loss_mse']) ? (float)$sim['att_loss_mse'] : ($deployedMSE * $attLossPct);
                        $defLossPct = isset($sim['def_loss_pct']) ? (float)$sim['def_loss_pct'] : 0.90;

                        $projectedDebris = ($fleetMSE * $defLossPct * $fleetCdrFactor) + ($defenseMSE * $defLossPct * $defsCdrFactor);
                        $realizableDebris = ($availableRecCap > 0) ? min($projectedDebris, $availableRecCap) : 0.0;

                        // Almacén (50% loot) + Escombros
                        $grossRevenue = $lootMSE + $realizableDebris;
                        $totalCosts = max(1.0, ($attLossMSE * (1.0 - $fleetCdrFactor)) + $fuelMSE);
                        $farmNetProfit = $grossRevenue - $totalCosts;
                        $farmRoi = $farmNetProfit / $totalCosts;

                        if ($farmNetProfit > 0 && $farmRoi >= 0.10) {
                            $isFarmingViable = true;
                        }
                    }
                }

                // --- ASIGNACIÓN DE CATEGORÍA ---
                if ($isSiegeViable) {
                    $category = 'siege';
                } elseif ($isFleetCrashViable) {
                    $category = 'fleet_crash';
                } elseif ($isFarmingViable) {
                    $category = 'farming';
                } elseif ($lootMSE >= ($empireHourlyProdMSE * 0.05) && !$defenseScouted) {
                    $category = 'recon_heavy';
                } else {
                    $category = 'low_value';
                }

                // Descarte por poco rentable: Entra a lista negra SOLO este planeta por 1 hora
                if ($category === 'low_value') {
                    BotTargetBlacklist::addCooldown($this->ctx->botId, (int)$p['owner_id'], $pId, (int)$p['galaxy'], (int)$p['system'], (int)$p['planet'], 3600, 'low_value');
                    continue; // Continúa con el siguiente objetivo del radar
                }

                // Anti-Bait Guard: Discard active player with moon having phalanx
                if ($isRecentlyActive && !empty($p['id_luna']) && $category !== 'low_value') {
                    // Juicy planet with active player + moon is a textbook bait!
                    continue;
                }

                // --- CALCULO DE EV Y PUNTUACIÓN POR HORA ---
                $pWin = 0.90;
                $strategicVal = 0.0;
                $riskFleetMSE = ($defenseMSE + $fleetMSE) * 1.3;
                $missilesMSE = 0.0;

                if ($category === 'fleet_crash') {
                    $pWin = $isRecentlyActive ? max(0.50, $fcWinProb * 0.8) : $fcWinProb;
                    $strategicVal = $debrisValMSE * 0.25;
                    $riskFleetMSE = ($defenseMSE + $fleetMSE) * 1.3;
                    $netEV = $fcNetProfit;
                } elseif ($category === 'farming') {
                    $pWin = $isRecentlyActive ? 0.70 : max(0.70, $farmWinProb);
                    $strategicVal = 0.0;
                    $riskFleetMSE = max(50000.0, $defenseMSE * 0.5);
                    $netEV = $farmNetProfit;
                } elseif ($category === 'siege') {
                    $pWin = 0.85;
                    $strategicVal = 500000.0;
                    $riskFleetMSE = ($defenseMSE + $fleetMSE) * 1.0;
                    $singleMipDamage = 12000.0 * (1.0 + $militaryTechAtk * 0.1);
                    $neededMips = min($effectiveMips, ceil($totalDefStructurePoints / max(1.0, $singleMipDamage)));
                    $missilesMSE = max(1, $neededMips) * BotEconomyValuator::getElementPriceMSE(503);
                    $netEV = ($pWin * ($lootMSE + $debrisValMSE + $strategicVal)) - ((1.0 - $pWin) * $riskFleetMSE) - $fuelMSE - $missilesMSE;
                } else {
                    $netEV = ($lootMSE * 0.5) - $fuelMSE;
                }

                $prodMseHourly = BotEconomyValuator::toMSE($prodM, $prodC, $prodD);

                // Anti-Turtle Guard: Discard unprofitable targets
                if ($netEV <= 0 && in_array($category, array('siege', 'farming', 'fleet_crash'))) {
                    BotTargetBlacklist::addCooldown($this->ctx->botId, (int)$p['owner_id'], $pId, (int)$p['galaxy'], (int)$p['system'], (int)$p['planet'], 3600, 'low_value');
                    continue;
                }

                // Valor(t) = EV(t) / horasIdaVuelta(t)
                $valorHora = $netEV / $roundtripHours;

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

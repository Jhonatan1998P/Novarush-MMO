<?php

require_once 'includes/classes/bot/Evaluation/CombatOracle.class.php';

/**
 * NovaRush Bot AI v2 - Staging Planner
 *
 * Identifies the safest bastion or Forward Operating Base (FOB) closest to active targets
 * and coordinates fleet unification (Mission 4: Deploy) when local FOB firepower is under 90% win rate.
 */
class BotStagingPlanner
{
    private $ctx;
    private $gateway;

    public function __construct(BotContext $ctx, BotActionGateway $gateway)
    {
        $this->ctx = $ctx;
        $this->gateway = $gateway;
    }

    /**
     * Determine best staging base or Forward Operating Base (FOB) closest to target
     *
     * @param BotEmpireState $empire
     * @param array|null $targetCoord Optional ['galaxy' => int, 'system' => int]
     * @return int Target planet/moon ID
     */
    public function getBestStagingBase(BotEmpireState $empire, array $targetCoord = null)
    {
        $allPlanets = $empire->getPlanets();
        if (empty($allPlanets)) {
            return 0;
        }

        // Prioritize locked target FOB designated by StrikePlanner
        if (!empty($this->ctx->botRow['fob_planet_id']) && (int)$this->ctx->botRow['fob_planet_id'] > 0) {
            $fobId = (int)$this->ctx->botRow['fob_planet_id'];
            if ($empire->getPlanet($fobId) !== null) {
                return $fobId;
            }
        }

        // If target coordinates are provided, find closest colony/moon (FOB)
        if ($targetCoord !== null && isset($targetCoord['galaxy'], $targetCoord['system'])) {
            $tG = (int)$targetCoord['galaxy'];
            $tS = (int)$targetCoord['system'];

            $bestId = 0;
            $minDist = PHP_INT_MAX;

            foreach ($allPlanets as $pId => $pData) {
                $pGalaxy = (int)$pData['galaxy'];
                $pSystem = (int)$pData['system'];

                if ($pGalaxy == $tG) {
                    $dist = abs($pSystem - $tS);
                } else {
                    $dist = 10000 + (abs($pGalaxy - $tG) * 1000) + abs($pSystem - $tS);
                }

                if ($dist < $minDist) {
                    $minDist = $dist;
                    $bestId = $pId;
                }
            }

            if ($bestId > 0) {
                return $bestId;
            }
        }

        // 1. Prefer moon if available
        $moons = $empire->getMoons();
        if (!empty($moons)) {
            $moonIds = array_keys($moons);
            return $moonIds[0];
        }

        // 2. Otherwise return home planet or planet with highest defense
        if ($this->ctx->homePlanetId > 0 && $empire->getPlanet($this->ctx->homePlanetId) !== null) {
            return $this->ctx->homePlanetId;
        }

        $pIds = array_keys($allPlanets);
        return $pIds[0];
    }

    /**
     * Rally idle combat ships to staging base / FOB if local forces do not have >90% win rate
     *
     * @param BotEmpireState $empire
     * @param int $stagingBaseId
     * @param array $targetDefenders Known enemy ships and defenses
     * @return int Number of fleets deployed
     */
    public function rallyFleets(BotEmpireState $empire, $stagingBaseId, array $targetDefenders = array(), $isUrgent = false)
    {
        global $resource, $reslist, $pricelist;
        if (empty($pricelist)) {
            require 'includes/vars/General.php';
        }

        $target = $empire->getPlanet($stagingBaseId);
        if (empty($target)) {
            return 0;
        }

        $fleetList = (isset($reslist['fleet']) && is_array($reslist['fleet'])) ? $reslist['fleet'] : array();

        $fobData = $target['data'];
        $fobCombatFleet = array();
        foreach ($fleetList as $shipId) {
            if (in_array($shipId, array(202, 203, 208, 209, 210, 212, 217, 219, 233))) continue;
            $cnt = isset($fobData[$resource[$shipId]]) ? (int)$fobData[$resource[$shipId]] : 0;
            if ($cnt > 0) {
                $fobCombatFleet[$shipId] = $cnt;
            }
        }

        $db = Database::get();

        // Query active in-flight deployments to FOB to count effective firepower
        $activeInbound = $db->select("SELECT fleet_array FROM %%FLEETS%% WHERE fleet_owner = :botId AND fleet_end_id = :fobId AND fleet_mission = 4 AND fleet_mess = 0;", array(
            ':botId' => $this->ctx->botId,
            ':fobId' => $stagingBaseId
        ));
        $effectiveFobFleet = $fobCombatFleet;
        if (!empty($activeInbound)) {
            foreach ($activeInbound as $aib) {
                $fArr = FleetFunctions::unserialize($aib['fleet_array']);
                if (is_array($fArr)) {
                    foreach ($fArr as $sId => $cnt) {
                        $sId = (int)$sId;
                        if (in_array($sId, array(202, 203, 208, 209, 210, 212, 217, 219, 233))) continue;
                        $effectiveFobFleet[$sId] = ($effectiveFobFleet[$sId] ?? 0) + (int)$cnt;
                    }
                }
            }
        }

        // Check if the fleet already stationed or inbound to FOB is sufficient (>90% win rate or massive garrison)
        if (!empty($targetDefenders)) {
            if (!empty($effectiveFobFleet)) {
                $sim = BotCombatOracle::evaluate(
                    $effectiveFobFleet,
                    $targetDefenders,
                    array('id' => $this->ctx->botId, 'factor' => $this->ctx->user['factor']),
                    array('id' => 0, 'factor' => array()),
                    1
                );
                if (isset($sim['win_probability']) && $sim['win_probability'] >= 0.90) {
                    // Local FOB firepower + inbound deployments already exceeds 90% win rate; keep remaining fleets stationed
                    return 0;
                }
            }
        } else {
            // Si el objetivo está indefenso o la inteligencia aún no reporta defensores, pero la FOB ya cuenta con guarnición respetable (>= 5,000 naves de combate), no saturar en despliegues
            if (!empty($effectiveFobFleet) && array_sum($effectiveFobFleet) >= 5000) {
                return 0;
            }
        }

        // Reserve 1 slot for the campaign assault strike, strictly respecting shielded military slots
        $maxSlots       = FleetFunctions::GetMaxFleetSlots($this->ctx->user);
        $actualFleets   = FleetFunctions::GetCurrentFleets($this->ctx->botId);
        $availableMil   = BotActionGateway::getAvailableMilitarySlots($this->ctx->botId, $this->ctx->user);
        if ($actualFleets >= ($maxSlots - 1) || $availableMil <= 1) {
            return 0;
        }

        // Query active rallies from colonies to avoid stacking duplicate rallies
        $activeRallies = $db->select("SELECT fleet_start_id FROM %%FLEETS%% WHERE fleet_owner = :botId AND fleet_mission = 4;", array(
            ':botId' => $this->ctx->botId
        ));
        $rallyingOrigins = array();
        foreach ($activeRallies as $ar) {
            $rallyingOrigins[(int)$ar['fleet_start_id']] = true;
        }

        $rallied = 0;
        $allBodies = $empire->getAllBodies();
        if (!is_array($allBodies) || empty($allBodies)) {
            return 0;
        }

        foreach ($allBodies as $pId => $body) {
            if ($pId == $stagingBaseId) continue;
            if (isset($rallyingOrigins[$pId])) continue; // Already rallying from this colony

            // Enforce slot reservation dynamically (leaving 1 slot for the assault)
            if ($actualFleets >= ($maxSlots - 1)) {
                break;
            }

            $pData = $body['data'];
            $combatShips = array();

            // Calculate 2 hours of physical resource production on this colony (Metal + Crystal + Deuterium, NOT MSE)
            $prod2h = 2.0 * (
                (float)($pData['metal_perhour'] ?? 0.0) +
                (float)($pData['crystal_perhour'] ?? 0.0) +
                (float)($pData['deuterium_perhour'] ?? 0.0)
            );
            if ($prod2h <= 0.0) {
                $prod2h = 100000.0;
            }

            // Real physical stock on the colony (Metal + Crystal + Deuterium)
            $stockFisicoTotalActual = (float)($pData['metal'] ?? 0.0) +
                                      (float)($pData['crystal'] ?? 0.0) +
                                      (float)($pData['deuterium'] ?? 0.0);

            // 1. Calculate minimum transporters to retain to move >= max(2h production, real physical stock)
            // guaranteeing FleetsaveCalculator can evacuate 100% of real stock
            $keepTrans = array(217 => 0, 203 => 0, 202 => 0);
            $remTransCap = max($prod2h, $stockFisicoTotalActual);
            foreach (array(217, 203, 202) as $tId) {
                if ($remTransCap <= 0) break;
                $avail = isset($pData[$resource[$tId]]) ? (int)$pData[$resource[$tId]] : 0;
                if ($avail <= 0) continue;
                $cap = max(1.0, (float)($pricelist[$tId]['capacity'] ?? 5000));
                if (class_exists('FleetFunctions')) {
                    $calcCap = FleetFunctions::GetFleetRoom(array($tId => 1), $this->ctx->user);
                    if ($calcCap > 0) $cap = $calcCap;
                }
                $needed = (int)ceil($remTransCap / $cap);
                $take = min($avail, $needed);
                $keepTrans[$tId] = $take;
                $remTransCap -= ($take * $cap);
            }
            if (array_sum($keepTrans) === 0) {
                foreach (array(217, 203, 202) as $tId) {
                    $avail = isset($pData[$resource[$tId]]) ? (int)$pData[$resource[$tId]] : 0;
                    if ($avail > 0) {
                        $keepTrans[$tId] = min($avail, 5);
                        break;
                    }
                }
            }

            // 2. Calculate minimum recyclers to retain to harvest >= 2h production
            $keepRec = array(219 => 0, 209 => 0);
            $remRecCap = $prod2h;
            foreach (array(219, 209) as $rId) {
                if ($remRecCap <= 0) break;
                $avail = isset($pData[$resource[$rId]]) ? (int)$pData[$resource[$rId]] : 0;
                if ($avail <= 0) continue;
                $cap = max(1.0, (float)($pricelist[$rId]['capacity'] ?? 20000));
                $needed = (int)ceil($remRecCap / $cap);
                $take = min($avail, $needed);
                $keepRec[$rId] = $take;
                $remRecCap -= ($take * $cap);
            }
            if (array_sum($keepRec) === 0) {
                foreach (array(219, 209) as $rId) {
                    $avail = isset($pData[$resource[$rId]]) ? (int)$pData[$resource[$rId]] : 0;
                    if ($avail > 0) {
                        $keepRec[$rId] = min($avail, 5);
                        break;
                    }
                }
            }

            // Check combat & campaign logistics ships
            $candidateShips = array();
            foreach ($fleetList as $shipId) {
                // Non-tactical civil units that should never rally (colonizers, probes, satellites, terraformers)
                if (in_array($shipId, array(208, 210, 212, 233))) {
                    continue;
                }

                $cnt = isset($pData[$resource[$shipId]]) ? (int)$pData[$resource[$shipId]] : 0;
                if ($cnt <= 0) continue;

                // Transporters: retain units to cover at least 2h physical production, rally the rest
                if (in_array($shipId, array(202, 203, 217))) {
                    $retained = $keepTrans[$shipId] ?? 0;
                    $rallyQty = max(0, $cnt - $retained);
                    if ($rallyQty > 0) {
                        $candidateShips[$shipId] = $rallyQty;
                    }
                }
                // Recyclers: retain units to cover at least 2h physical production, rally the rest
                elseif (in_array($shipId, array(209, 219))) {
                    $retained = $keepRec[$shipId] ?? 0;
                    $rallyQty = max(0, $cnt - $retained);
                    if ($rallyQty > 0) {
                        $candidateShips[$shipId] = $rallyQty;
                    }
                }
                // Combat warships:
                else {
                    $candidateShips[$shipId] = $cnt;
                }
            }

            // Minimum squadron size of 25 combat ships to avoid slot exhaustion
            if (empty($candidateShips) || array_sum($candidateShips) < 25) {
                continue;
            }

            // 3. FMA Sizing & Fuel-Aware Budgeting
            $stockDeut = (float)($pData['deuterium'] ?? 0.0);
            $fuelBudget = max(0.0, $stockDeut * 0.90); // Reserve 10% for local operations
            if ($fuelBudget < 500.0) {
                continue; // Insufficient local fuel to launch any deploy
            }

            $origCoords   = array((int)$pData['galaxy'], (int)$pData['system'], (int)$pData['planet']);
            $targetCoords = array((int)$target['galaxy'], (int)$target['system'], (int)$target['planet']);
            $dist         = FleetFunctions::GetTargetDistance($origCoords, $targetCoords);
            $gameSpeed    = FleetFunctions::GetGameSpeedFactor();

            // Sizing: prioritize capital and heavy warships
            $selectedShips = array();
            if (!empty($targetDefenders)) {
                $targetMSE = 0.0;
                foreach ($targetDefenders as $dId => $dCnt) {
                    $targetMSE += BotEconomyValuator::getElementPriceMSE((int)$dId, (float)$dCnt);
                }
                $targetCapMSE = $targetMSE * 2.2;
                $currentMSE = 0.0;
                foreach ($effectiveFobFleet as $sId => $cnt) {
                    $currentMSE += BotEconomyValuator::getElementPriceMSE((int)$sId, (float)$cnt);
                }
                $remNeededMSE = max(50000000.0, ($targetCapMSE * 1.15) - $currentMSE);

                $priorityOrder = array(213, 225, 215, 211, 206, 207, 205, 204, 217, 203, 219, 209, 202);
                $accumMSE = 0.0;
                foreach ($priorityOrder as $sId) {
                    if (empty($candidateShips[$sId])) continue;
                    $priceMSE = BotEconomyValuator::getElementPriceMSE($sId, 1);
                    if ($priceMSE <= 0) $priceMSE = 1000.0;
                    $avail = $candidateShips[$sId];
                    if ($accumMSE < $remNeededMSE) {
                        $take = min($avail, (int)ceil(($remNeededMSE - $accumMSE) / $priceMSE));
                        if ($take > 0) {
                            $selectedShips[$sId] = $take;
                            $accumMSE += ($take * $priceMSE);
                        }
                    }
                }
                // Include utility ships (transporters/recyclers) if present
                foreach (array(217, 203, 219, 209) as $sId) {
                    if (!empty($candidateShips[$sId]) && empty($selectedShips[$sId])) {
                        $selectedShips[$sId] = $candidateShips[$sId];
                    }
                }
            }

            if (empty($selectedShips) || array_sum($selectedShips) < 25) {
                $selectedShips = $candidateShips;
            }

            // Check tactical urgency: if FOB has target lock / siege or incoming threat
            $hasUrgency = $isUrgent ||
                          (!empty($this->ctx->botRow['offensive_urgency'])) ||
                          (!empty($this->ctx->botRow['target_lock_time']) && (int)$this->ctx->botRow['target_lock_time'] > 0 && !empty($this->ctx->botRow['siege_cycles']) && (int)$this->ctx->botRow['siege_cycles'] > 0) ||
                          (!empty($this->ctx->botRow['target_galaxy']) && !empty($this->ctx->botRow['target_system']));

            // Economic speed selector: if no offensive urgency, test speed 6 down to 1 (50%-60%); if urgent, test 10 down to 1
            $maxSpdCandidate = $hasUrgency ? 10 : 6;

            $fleetSpeed = FleetFunctions::GetFleetMaxSpeed($selectedShips, $this->ctx->user);
            $chosenSpeed = 0;
            $chosenConsumption = 0;

            for ($spd = $maxSpdCandidate; $spd >= 1; $spd--) {
                $dur = FleetFunctions::GetMissionDuration($spd, $fleetSpeed, $dist, $gameSpeed, $this->ctx->user);
                $cons = FleetFunctions::GetFleetConsumption($selectedShips, $dur, $dist, $this->ctx->user, $gameSpeed);
                if ($cons <= $fuelBudget) {
                    $chosenSpeed = $spd;
                    $chosenConsumption = $cons;
                    break;
                }
            }

            // If even at 10% speed consumption exceeds fuelBudget, scale down fleet to fit budget safely
            if ($chosenSpeed === 0) {
                $dur1 = FleetFunctions::GetMissionDuration(1, $fleetSpeed, $dist, $gameSpeed, $this->ctx->user);
                $cons1 = FleetFunctions::GetFleetConsumption($selectedShips, $dur1, $dist, $this->ctx->user, $gameSpeed);
                if ($cons1 > 0) {
                    $scale = min(0.95, ($fuelBudget / $cons1) * 0.90);
                    $scaled = array();
                    foreach ($selectedShips as $sId => $cnt) {
                        $nCnt = (int)floor($cnt * $scale);
                        if ($nCnt > 0) {
                            $scaled[$sId] = $nCnt;
                        }
                    }
                    if (!empty($scaled) && array_sum($scaled) >= 25) {
                        $selectedShips = $scaled;
                        $fleetSpeed = FleetFunctions::GetFleetMaxSpeed($selectedShips, $this->ctx->user);
                        for ($spd = $maxSpdCandidate; $spd >= 1; $spd--) {
                            $dur = FleetFunctions::GetMissionDuration($spd, $fleetSpeed, $dist, $gameSpeed, $this->ctx->user);
                            $cons = FleetFunctions::GetFleetConsumption($selectedShips, $dur, $dist, $this->ctx->user, $gameSpeed);
                            if ($cons <= $fuelBudget) {
                                $chosenSpeed = $spd;
                                $chosenConsumption = $cons;
                                break;
                            }
                        }
                    }
                }
            }

            if ($chosenSpeed <= 0 || empty($selectedShips) || array_sum($selectedShips) < 25) {
                continue;
            }

            // Piggybacking of excess fuel to FOB in Mission 4 (Deploy)
            $totalFleetRoom = FleetFunctions::GetFleetRoom($selectedShips, $this->ctx->user);
            $netCargo = max(0.0, (float)$totalFleetRoom - (float)$chosenConsumption);

            // Calculate untouchable local fleetsave reserve for remaining ships at 10% speed
            $remainingShips = array();
            foreach ($fleetList as $sId) {
                $avail = isset($pData[$resource[$sId]]) ? (int)$pData[$resource[$sId]] : 0;
                $taken = isset($selectedShips[$sId]) ? (int)$selectedShips[$sId] : 0;
                $left  = max(0, $avail - $taken);
                if ($left > 0) {
                    $remainingShips[$sId] = $left;
                }
            }

            $localFsReserve = 0.0;
            if (!empty($remainingShips)) {
                $remSpeed = FleetFunctions::GetFleetMaxSpeed($remainingShips, $this->ctx->user);
                $durFs = FleetFunctions::GetMissionDuration(1, $remSpeed, $dist, $gameSpeed, $this->ctx->user);
                $localFsReserve = FleetFunctions::GetFleetConsumption($remainingShips, $durFs, $dist, $this->ctx->user, $gameSpeed);
            }
            $localFsReserve = max(5000.0, $localFsReserve);

            // Calculate committed deuterium in local queues or active savings lock on this colony
            $localCommittedDeut = 0.0;
            if (isset($this->ctx->budgetManager)) {
                $lock = $this->ctx->budgetManager->getSavingsLock();
                if (!empty($lock) && isset($lock['planet_id']) && (int)$lock['planet_id'] === (int)$pId) {
                    $localCommittedDeut += max(0.0, (float)($lock['cost'][903] ?? $lock['cost']['deuterium'] ?? 0.0));
                }
            }

            // Colony deuterium after flight fuel deduction, fleetsave reserve, and local committed queues
            $colonyDeut = (float)($pData['deuterium'] ?? 0.0);
            $excessDeut = max(0.0, $colonyDeut - (float)$chosenConsumption - $localFsReserve - $localCommittedDeut);
            $cargoDeut = floor(min($netCargo, $excessDeut));

            $cargoResources = array();
            if ($cargoDeut > 0.0) {
                $cargoResources[903] = (float)$cargoDeut;
            }

            // Launch Deploy (Mission 4)
            $ok = $this->gateway->launchFleet(
                $selectedShips,
                4, // Mission 4: Stay / Deploy
                $pId,
                $target['id'],
                $this->ctx->botId,
                $target['galaxy'],
                $target['system'],
                $target['planet'],
                $target['planet_type'],
                $cargoResources,
                $chosenSpeed
            );

            if ($ok) {
                $rallied++;
                $actualFleets++;
                foreach ($selectedShips as $sId => $sCnt) {
                    $effectiveFobFleet[$sId] = ($effectiveFobFleet[$sId] ?? 0) + $sCnt;
                }
                $this->ctx->decisionLog->record(
                    'fleet_staging',
                    "Rallied combat fleet from colony #{$pId} to FOB #{$stagingBaseId} to consolidate firepower (Speed: {$chosenSpeed}0%, Ships: " . number_format(array_sum($selectedShips)) . ", Fuel: " . number_format($chosenConsumption) . " Deut, Piggyback Deut: " . number_format($cargoDeut) . ")",
                    array('from' => $pId, 'to' => $stagingBaseId, 'ships' => $selectedShips, 'speed' => $chosenSpeed, 'fuel' => $chosenConsumption, 'cargo_deut' => $cargoDeut),
                    "rally_{$pId}_to_{$stagingBaseId}",
                    array_sum($selectedShips)
                );
            }
        }

        return $rallied;
    }
}

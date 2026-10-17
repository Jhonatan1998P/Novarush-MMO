<?php

require_once 'includes/classes/bot/Forces/BotPremiumStrategist.class.php';
require_once 'includes/classes/bot/Evaluation/EconomyValuator.class.php';

/**
 * NovaRush Bot AI v2 - Fleetsave Calculator (Professional, Armor-Plated & Phalanx-Aware)
 *
 * Implements:
 * 1. Phalanx-Aware Route Selection: Prioritizes movements from/to moons (Moon-to-Moon Deploy
 *    is 100% undetectable by enemy Sensor Phalanx; Moon-to-Planet or Planet-to-Moon as strong second).
 * 2. 100% Resource Evacuation: Sweeps all metal, crystal, and remaining deuterium into cargo.
 * 3. Accurate Fuel Calculation: Takes into account the FuelConsum tech reduction factor.
 * 4. Emergency MO Trader: If deuterium is insufficient, automatically trades Metal/Crystal
 *    from the military 'fleet' budget for Deuterium via the Trader (50 MO fee).
 * 5. Partial Fleetsave Fallback: If fuel is still insufficient, evacuates the maximum possible
 *    ships (prioritizing highest MSE value per fuel: Heavies > Mediums > Cargo > Fodder)
 *    to the closest own colony.
 * 6. Post-Impact Recall & Budget Sync: Automatically schedules recall past threat arrival
 *    and reconciles deuterium back to the military budget.
 */
class BotFleetsaveCalculator
{
    private $ctx;
    private $gateway;

    public function __construct(BotContext $ctx, BotActionGateway $gateway)
    {
        $this->ctx = $ctx;
        $this->gateway = $gateway;
    }

    /**
     * Execute a fleetsave from an endangered planet/moon
     *
     * @param BotEmpireState $empire
     * @param int $endangeredPlanetId
     * @param int $threatArrivalTime
     * @return bool True if fleetsave successfully launched
     */
    /**
     * Execute a fleetsave from an endangered planet/moon
     *
     * @param BotEmpireState $empire
     * @param int $endangeredPlanetId
     * @param int $threatArrivalTime
     * @param array|null $customShips Optional subset of ships to evacuate
     * @param bool $forceImmediate If true, bypasses the 1-3min delayed window and executes immediately
     * @return bool True if fleetsave successfully launched or scheduled
     */
    public function executeFleetsave(BotEmpireState $empire, $endangeredPlanetId, $threatArrivalTime, array $customShips = null, $forceImmediate = false)
    {
        global $resource, $reslist;

        $endangered = $empire->getPlanet($endangeredPlanetId);
        if (empty($endangered)) {
            return false;
        }

        $pData = $endangered['data'];
        $originType = isset($endangered['planet_type']) ? (int)$endangered['planet_type'] : 1;

        // 1. Gather ships to evacuate
        $ships = array();
        if ($customShips !== null) {
            $ships = $customShips;
        } else {
            $fleetList = (isset($reslist['fleet']) && is_array($reslist['fleet'])) ? $reslist['fleet'] : array();
            foreach ($fleetList as $shipId) {
                if ($shipId == 212) continue; // Solar satellites cannot fly
                $qty = isset($pData[$resource[$shipId]]) ? (int)$pData[$resource[$shipId]] : 0;
                if ($qty > 0) {
                    $ships[$shipId] = $qty;
                }
            }
        }

        if (empty($ships) || array_sum($ships) <= 0) {
            return false;
        }

        // Timing window: Fleetsave must launch randomly 1-3 minutes (60 to 180 seconds) before impact
        $now = class_exists('BotClock') ? BotClock::now() : TIMESTAMP;
        $timeUntilThreat = (int)$threatArrivalTime - $now;
        if ($timeUntilThreat <= 0) {
            return false;
        }

        $fsWindow = mt_rand(60, 180);
        if (!$forceImmediate && $timeUntilThreat > $fsWindow) {
            $scheduledLaunchTime = (int)$threatArrivalTime - $fsWindow;

            $db = Database::get();
            $pending = $db->selectSingle(
                "SELECT task_id FROM %%BOT_TASKS%% 
                 WHERE bot_id = :bid AND task_type = 'staggered_fleetsave' AND status = 'pending' 
                   AND payload LIKE :patt LIMIT 1;",
                array(':bid' => (int)$this->ctx->botId, ':patt' => '%"endangered_planet_id":' . (int)$endangeredPlanetId . '%')
            );

            if (!empty($pending)) {
                return true; // Already scheduled
            }

            BotScheduler::scheduleTask(
                $this->ctx->botId,
                'staggered_fleetsave',
                array(
                    'endangered_planet_id' => (int)$endangeredPlanetId,
                    'threat_arrival_time'  => (int)$threatArrivalTime,
                    'custom_ships'         => $customShips,
                    'fs_window'            => $fsWindow,
                ),
                $scheduledLaunchTime
            );

            $this->ctx->decisionLog->record(
                'fleetsave_scheduled',
                "Scheduled tactical fleetsave from planet #{$endangeredPlanetId} for T+" . ($scheduledLaunchTime - $now) . "s ({$fsWindow}s before impact at " . date('H:i:s', $threatArrivalTime) . ")",
                array('planet_id' => $endangeredPlanetId, 'threat_arrival' => $threatArrivalTime, 'launch_time' => $scheduledLaunchTime, 'window' => $fsWindow),
                "sched_fleetsave_{$endangeredPlanetId}",
                3500.0
            );

            return true;
        }

        // Ensure user factor is loaded so FuelConsum tech factor is 100% active
        if (!isset($this->ctx->user['factor']) || !isset($this->ctx->user['factor']['FuelConsum'])) {
            $this->ctx->user['factor'] = getFactors($this->ctx->user, 'basic', $now);
        }

        $startCoords = array($endangered['galaxy'], $endangered['system'], $endangered['planet']);
        $fleetSpeed  = FleetFunctions::GetFleetMaxSpeed($ships, $this->ctx->user);
        $gameSpeed   = FleetFunctions::GetGameSpeedFactor();

        // 2. Safe Haven Hierarchy:
        // Priority 1: Nearest own Moon free of hostiles
        // Priority 2: Nearest own Planet free of hostiles
        // Priority 3: Most temporally separated destination if entire empire attacked
        $db = Database::get();
        $attackedRows = $db->select("
            SELECT DISTINCT fleet_end_id 
            FROM %%FLEETS%% 
            WHERE fleet_owner != :botId 
              AND fleet_mission IN (1, 2, 9) 
              AND fleet_mess = 0;
        ", array(':botId' => $this->ctx->botId));

        $threatenedBodyIds = array();
        if (!empty($attackedRows) && is_array($attackedRows)) {
            foreach ($attackedRows as $ar) {
                $threatenedBodyIds[(int)$ar['fleet_end_id']] = true;
            }
        }

        $allBodies = $empire->getAllBodies();
        $bestMoonCandidate = null;
        $minDistMoon = PHP_INT_MAX;

        $bestPlanetCandidate = null;
        $minDistPlanet = PHP_INT_MAX;

        $farthestOrMaxTimeCandidate = null;
        $maxDist = -1;

        if (is_array($allBodies)) {
            foreach ($allBodies as $candidateId => $candidate) {
                if ($candidateId == $endangeredPlanetId) continue;
                $candCoords = array($candidate['galaxy'], $candidate['system'], $candidate['planet']);
                $dist = FleetFunctions::GetTargetDistance($startCoords, $candCoords);
                $isThreatened = isset($threatenedBodyIds[(int)$candidateId]);
                $isMoon = ((int)($candidate['planet_type'] ?? 1) === 3);

                // Priority 1: Nearest own Moon free of hostiles
                if ($isMoon && !$isThreatened) {
                    if ($dist < $minDistMoon) {
                        $minDistMoon = $dist;
                        $bestMoonCandidate = $candidate;
                    }
                }

                // Priority 2: Nearest own Planet free of hostiles
                if (!$isMoon && !$isThreatened) {
                    if ($dist < $minDistPlanet) {
                        $minDistPlanet = $dist;
                        $bestPlanetCandidate = $candidate;
                    }
                }

                // Priority 3: Most temporally separated destination if entire empire attacked (farthest distance)
                if ($dist > $maxDist) {
                    $maxDist = $dist;
                    $farthestOrMaxTimeCandidate = $candidate;
                }
            }
        }

        if ($bestMoonCandidate !== null) {
            $destination = $bestMoonCandidate;
        } elseif ($bestPlanetCandidate !== null) {
            $destination = $bestPlanetCandidate;
        } elseif ($farthestOrMaxTimeCandidate !== null) {
            $destination = $farthestOrMaxTimeCandidate;
        } else {
            return $this->emergencyExpeditionSave($endangered, $ships);
        }

        // Fetch fresh physical resources
        $db = Database::get();
        $pRow = $db->selectSingle("SELECT metal, crystal, deuterium FROM %%PLANETS%% WHERE id = :pid LIMIT 1;", array(':pid' => (int)$endangeredPlanetId));
        if (!empty($pRow)) {
            $pData['metal']     = $pRow['metal'];
            $pData['crystal']   = $pRow['crystal'];
            $pData['deuterium'] = $pRow['deuterium'];
        }
        $availDeut = max(0.0, (float)$pData['deuterium']);

        // 3. Send at 10% speed (speed = 1) for drastic deuterium reduction (>90% savings)
        $chosenSpeed  = 1;
        $targetCoords = array($destination['galaxy'], $destination['system'], $destination['planet']);
        $distance     = FleetFunctions::GetTargetDistance($startCoords, $targetCoords);
        $duration     = FleetFunctions::GetMissionDuration($chosenSpeed, $fleetSpeed, $distance, $gameSpeed, $this->ctx->user);
        $consumption  = FleetFunctions::GetFleetConsumption($ships, $duration, $distance, $this->ctx->user, $gameSpeed);

        $fuelTraded = 0.0;

        // 4. Emergency Trader for Deuterium if fuel deficit exists (multi-category donor)
        if ($availDeut < $consumption) {
            $fuelDeficit = max(0.0, $consumption - $availDeut);
            $trader = new BotPremiumStrategist($this->ctx, $this->gateway);
            $traded = $trader->executeEmergencyFleetsaveTrade($endangeredPlanetId, $fuelDeficit, true);
            if ($traded > 0.0) {
                $fuelTraded += $traded;
                $availDeut += $traded;
                $pData['deuterium'] = (float)$pData['deuterium'] + $traded;
            }
        }

        // 5. If destination is affordable for 100% of fleet at speed 1, launch full fleetsave
        if ($consumption <= $availDeut) {
            return $this->launchFullFleetsave(
                $endangeredPlanetId,
                $originType,
                $destination,
                $ships,
                $pData,
                $chosenSpeed,
                $consumption,
                $threatArrivalTime,
                ($originType === 3 || (int)$destination['planet_type'] === 3),
                $fuelTraded
            );
        }

        // 6. Partial Fleetsave Fallback to Closest Colony if fuel still deficient
        return $this->launchPartialFleetsaveToClosest(
            $endangeredPlanetId,
            $originType,
            $destination,
            $ships,
            $pData,
            $availDeut,
            $fleetSpeed,
            $gameSpeed,
            $startCoords,
            $threatArrivalTime,
            $fuelTraded
        );
    }

    private function launchFullFleetsave(
        $endangeredPlanetId,
        $originType,
        array $destination,
        array $ships,
        array $pData,
        $chosenSpeed,
        $chosenConsumption,
        $threatArrivalTime,
        $isMoonFlight,
        $fuelTraded
    ) {
        $cargoRes = array(
            901 => max(0.0, (float)$pData['metal']),
            902 => max(0.0, (float)$pData['crystal']),
            903 => max(0.0, (float)$pData['deuterium'] - (float)$chosenConsumption),
        );

        $fleetId = $this->gateway->launchFleet(
            $ships,
            4, // Mission 4: Stay / Deploy
            $endangeredPlanetId,
            $destination['id'],
            $this->ctx->botId,
            $destination['galaxy'],
            $destination['system'],
            $destination['planet'],
            $destination['planet_type'],
            $cargoRes,
            $chosenSpeed
        );

        if ($fleetId) {
            if (isset($this->ctx->budgetManager)) {
                $this->ctx->budgetManager->syncPlanetEvacuation($endangeredPlanetId, $cargoRes);
            }

            // Recall scheduled using difficulty-calibrated delay range post-impact
            $delayRange  = method_exists($this->ctx->difficulty, 'getFleetsaveRecallDelayRange')
                ? $this->ctx->difficulty->getFleetsaveRecallDelayRange()
                : array(60, 150);
            $recallDelay = mt_rand($delayRange[0], $delayRange[1]);
            $recallTime  = (int)$threatArrivalTime + $recallDelay;
            BotScheduler::scheduleTask(
                $this->ctx->botId,
                'fleetsave_recall',
                array(
                    'fleet_id'    => $fleetId,
                    'origin_id'   => $endangeredPlanetId,
                    'fuel_used'   => $chosenConsumption,
                    'fuel_traded' => $fuelTraded,
                ),
                $recallTime
            );

            $this->ctx->decisionLog->record(
                'fleetsave_executed',
                "Optimal fleetsave: evacuated " . number_format(array_sum($ships)) . " ships to closest colony #{$destination['id']} at 10% speed (Fuel: " . number_format($chosenConsumption) . " deut, traded: " . number_format($fuelTraded) . " deut, recall in +{$recallDelay}s post-impact at " . date('H:i:s', $recallTime) . ")",
                array(
                    'origin'         => $endangeredPlanetId,
                    'target'         => $destination['id'],
                    'ships'          => $ships,
                    'res'            => $cargoRes,
                    'speed'          => $chosenSpeed,
                    'fuel'           => $chosenConsumption,
                    'fuel_traded'    => $fuelTraded,
                    'fleet_id'       => $fleetId,
                    'recall_delay'   => $recallDelay,
                    'phalanx_masked' => $isMoonFlight
                ),
                "fleetsave_{$endangeredPlanetId}_to_{$destination['id']}",
                array_sum($ships)
            );

            return true;
        }

        return false;
    }

    private function launchPartialFleetsaveToClosest(
        $endangeredPlanetId,
        $originType,
        array $closestCandidate,
        array $ships,
        array $pData,
        $availDeut,
        $fleetSpeed,
        $gameSpeed,
        array $startCoords,
        $threatArrivalTime,
        $fuelTraded
    ) {
        $closeCoords = array($closestCandidate['galaxy'], $closestCandidate['system'], $closestCandidate['planet']);
        $distance    = FleetFunctions::GetTargetDistance($startCoords, $closeCoords);
        $duration    = FleetFunctions::GetMissionDuration(1, $fleetSpeed, $distance, $gameSpeed, $this->ctx->user);

        // Sort ships by value-to-fuel ratio (MSE per unit fuel)
        $shipPriority = array();
        foreach ($ships as $shipId => $qty) {
            if ($qty <= 0) continue;
            $unitCost = FleetFunctions::GetFleetConsumption(array($shipId => 1), $duration, $distance, $this->ctx->user, $gameSpeed);
            $unitMSE  = BotEconomyValuator::getElementPriceMSE($shipId, 1);
            $ratio = $unitMSE / max(1, $unitCost);
            $shipPriority[$shipId] = $ratio;
        }
        arsort($shipPriority);

        // Greedily fit maximum possible ships within $availDeut
        $partialShips = array();
        $fuelBudget = $availDeut;

        foreach ($shipPriority as $shipId => $ratio) {
            $qtyAvail = $ships[$shipId];
            if ($qtyAvail <= 0 || $fuelBudget <= 0) continue;

            $testShips = $partialShips;
            $testShips[$shipId] = $qtyAvail;
            $testConsum = FleetFunctions::GetFleetConsumption($testShips, $duration, $distance, $this->ctx->user, $gameSpeed);

            if ($testConsum <= $fuelBudget) {
                $partialShips[$shipId] = $qtyAvail;
            } else {
                // Binary search for maximum affordable count of this ship type
                $low = 0;
                $high = $qtyAvail;
                $bestFit = 0;

                while ($low <= $high) {
                    $mid = (int)floor(($low + $high) / 2);
                    if ($mid <= 0) {
                        $low = $mid + 1;
                        continue;
                    }
                    $candidateFleet = $partialShips;
                    $candidateFleet[$shipId] = $mid;
                    $c = FleetFunctions::GetFleetConsumption($candidateFleet, $duration, $distance, $this->ctx->user, $gameSpeed);

                    if ($c <= $fuelBudget) {
                        $bestFit = $mid;
                        $low = $mid + 1;
                    } else {
                        $high = $mid - 1;
                    }
                }

                if ($bestFit > 0) {
                    $partialShips[$shipId] = $bestFit;
                }
                break; // fuel capacity exhausted
            }
        }

        if (empty($partialShips) || array_sum($partialShips) <= 0) {
            // Cannot fly even 1 ship with available fuel
            return false;
        }

        $actualConsum = FleetFunctions::GetFleetConsumption($partialShips, $duration, $distance, $this->ctx->user, $gameSpeed);
        $cargoCapacity = FleetFunctions::GetFleetRoom($partialShips, $this->ctx->user);
        $remDeut = max(0.0, $availDeut - $actualConsum);

        // Load resources into partial fleet cargo up to room
        $loadM = min((float)$pData['metal'], max(0.0, $cargoCapacity));
        $cargoCapacity -= $loadM;
        $loadC = min((float)$pData['crystal'], max(0.0, $cargoCapacity));
        $cargoCapacity -= $loadC;
        $loadD = min($remDeut, max(0.0, $cargoCapacity));

        $cargoRes = array(901 => $loadM, 902 => $loadC, 903 => $loadD);

        $fleetId = $this->gateway->launchFleet(
            $partialShips,
            4, // Mission 4: Stay / Deploy
            $endangeredPlanetId,
            $closestCandidate['id'],
            $this->ctx->botId,
            $closestCandidate['galaxy'],
            $closestCandidate['system'],
            $closestCandidate['planet'],
            $closestCandidate['planet_type'],
            $cargoRes,
            1 // lowest speed factor for maximum fuel efficiency
        );

        if ($fleetId) {
            if (isset($this->ctx->budgetManager)) {
                $this->ctx->budgetManager->syncPlanetEvacuation($endangeredPlanetId, $cargoRes);
            }

            // Recall scheduled using difficulty-calibrated delay range post-impact
            $delayRange  = method_exists($this->ctx->difficulty, 'getFleetsaveRecallDelayRange')
                ? $this->ctx->difficulty->getFleetsaveRecallDelayRange()
                : array(60, 150);
            $recallDelay = mt_rand($delayRange[0], $delayRange[1]);
            $recallTime  = (int)$threatArrivalTime + $recallDelay;
            BotScheduler::scheduleTask(
                $this->ctx->botId,
                'fleetsave_recall',
                array(
                    'fleet_id'    => $fleetId,
                    'origin_id'   => $endangeredPlanetId,
                    'fuel_used'   => $actualConsum,
                    'fuel_traded' => $fuelTraded,
                    'is_partial'  => true,
                ),
                $recallTime
            );

            $this->ctx->decisionLog->record(
                'fleetsave_executed',
                "Emergency Partial Fleetsave: evacuated " . number_format(array_sum($partialShips)) . " / " . number_format(array_sum($ships)) . " high-value ships to closest colony #{$closestCandidate['id']} at 10% speed (Fuel: " . number_format($actualConsum) . " / " . number_format($availDeut) . " deut, recall in +{$recallDelay}s post-impact at " . date('H:i:s', $recallTime) . ")",
                array(
                    'origin'         => $endangeredPlanetId,
                    'target'         => $closestCandidate['id'],
                    'ships'          => $partialShips,
                    'all_ships'      => $ships,
                    'res'            => $cargoRes,
                    'speed'          => 1,
                    'fuel'           => $actualConsum,
                    'fuel_traded'    => $fuelTraded,
                    'fleet_id'       => $fleetId,
                    'is_partial'     => true,
                ),
                "partial_fleetsave_{$endangeredPlanetId}_to_{$closestCandidate['id']}",
                array_sum($partialShips)
            );

            return true;
        }

        return false;
    }

    private function emergencyExpeditionSave(array $endangered, array $ships)
    {
        // Mission 15 to position 16 in same system
        $fleetId = $this->gateway->launchFleet(
            $ships,
            15, // Mission 15: Expedition
            $endangered['id'],
            0,
            0,
            $endangered['galaxy'],
            $endangered['system'],
            16,
            1,
            array(),
            10
        );

        return !empty($fleetId);
    }
}

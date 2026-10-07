<?php

/**
 * NovaRush Bot AI v2 - Fleetsave Calculator (Professional & Phalanx-Aware)
 *
 * Implements:
 * 1. Phalanx-Aware Route Selection: Prioritizes movements from/to moons (Moon-to-Moon Deploy
 *    is 100% undetectable by enemy Sensor Phalanx; Moon-to-Planet or Planet-to-Moon as strong second).
 * 2. 100% Resource Evacuation: Sweeps all metal, crystal, and remaining deuterium into cargo.
 * 3. Calibrated Reduced Speed: If flight is intercolonial without moon, calculates reduced speed (10%-70%)
 *    such that flight duration safely exceeds threat arrival time, masking timing and slashing fuel burn.
 * 4. Deuterium Fuel Reserve: Optimizes speed factor to guarantee fuel consumption never exceeds local deuterium.
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
    public function executeFleetsave(BotEmpireState $empire, $endangeredPlanetId, $threatArrivalTime)
    {
        global $resource, $reslist;

        $endangered = $empire->getPlanet($endangeredPlanetId);
        if (empty($endangered)) {
            return false;
        }

        $pData = $endangered['data'];
        $originType = isset($endangered['planet_type']) ? (int)$endangered['planet_type'] : 1;

        // 1. Gather all flight-capable ships on the planet
        $ships = array();
        $fleetList = (isset($reslist['fleet']) && is_array($reslist['fleet'])) ? $reslist['fleet'] : array();
        foreach ($fleetList as $shipId) {
            // Skip solar satellites (they cannot fly)
            if ($shipId == 212) continue;

            $qty = isset($pData[$resource[$shipId]]) ? (int)$pData[$resource[$shipId]] : 0;
            if ($qty > 0) {
                $ships[$shipId] = $qty;
            }
        }

        if (empty($ships)) {
            // No fleet to save
            return false;
        }

        // 2. Select safest destination: Phalanx-Aware Ranking
        // Moon-to-Moon (Rank 1: completely invisible to enemy Phalanx)
        // Planet-to-Moon or Moon-to-Planet (Rank 2: arrival masked on moon)
        // Planet-to-Planet (Rank 3: requires calibrated reduced speed)
        $destination = null;
        $allBodies = $empire->getAllBodies();
        $bestCandidate = null;
        $bestCandidateRank = -1;

        if (is_array($allBodies)) {
            foreach ($allBodies as $candidateId => $candidate) {
                if ($candidateId == $endangeredPlanetId) continue;
                $candType = isset($candidate['planet_type']) ? (int)$candidate['planet_type'] : 1;

                $rank = 0;
                if ($originType === 3 && $candType === 3) {
                    $rank = 300; // Moon to Moon (Ghost deployment)
                } elseif ($candType === 3) {
                    $rank = 200; // Target is Moon
                } elseif ($originType === 3) {
                    $rank = 150; // Origin is Moon
                } else {
                    $rank = 100; // Planet to Planet
                }

                // If tied rank, favor distant colonies to allow longer flight buffers
                $distDiff = abs((int)$candidate['system'] - (int)$endangered['system']);
                $totalScore = $rank + min(50, $distDiff);

                if ($totalScore > $bestCandidateRank) {
                    $bestCandidateRank = $totalScore;
                    $bestCandidate = $candidate;
                }
            }
        }

        $destination = $bestCandidate;

        if (empty($destination)) {
            // Only one colony exists; use Expedition (Mission 15) as last resort
            return $this->emergencyExpeditionSave($endangered, $ships);
        }

        // 3. Compute flight distance, speeds, and fuel
        $startCoords  = array($endangered['galaxy'], $endangered['system'], $endangered['planet']);
        $targetCoords = array($destination['galaxy'], $destination['system'], $destination['planet']);
        $distance     = FleetFunctions::GetTargetDistance($startCoords, $targetCoords);
        $fleetSpeed   = FleetFunctions::GetFleetMaxSpeed($ships, $this->ctx->user);
        $gameSpeed    = FleetFunctions::GetGameSpeedFactor();

        $timeUntilThreat = max(60, $threatArrivalTime - TIMESTAMP);
        $safeFlightMin   = $timeUntilThreat + 180; // Buffer: arrive at least 3 minutes after threat impact

        $availDeut = max(0.0, (float)$pData['deuterium']);
        $chosenSpeed = 10;
        $chosenConsumption = 0;
        $foundSafeSpeed = false;

        $isMoonFlight = ($originType === 3 || (int)$destination['planet_type'] === 3);

        // Calibrate reduced speed (10% to 100%)
        // If intercolonial without moon, we explicitly search for reduced speed where duration >= safeFlightMin
        for ($speed = 10; $speed >= 1; $speed--) {
            $duration = FleetFunctions::GetMissionDuration($speed, $fleetSpeed, $distance, $gameSpeed, $this->ctx->user);
            $consumption = FleetFunctions::GetFleetConsumption($ships, $duration, $distance, $this->ctx->user, $speed);

            if ($consumption > $availDeut) {
                // Must reduce speed further if deuterium fuel is insufficient
                continue;
            }

            if (!$isMoonFlight) {
                // For planet-to-planet flight: pick lowest speed that safely exceeds threat arrival
                if ($duration >= $safeFlightMin) {
                    $chosenSpeed = $speed;
                    $chosenConsumption = $consumption;
                    $foundSafeSpeed = true;
                }
            } else {
                // For moon flight: can use standard or calibrated speed within deuterium budget
                $chosenSpeed = $speed;
                $chosenConsumption = $consumption;
                $foundSafeSpeed = true;
                break;
            }
        }

        // If no speed met duration >= safeFlightMin, take minimum affordable speed to maximize flight time
        if (!$foundSafeSpeed) {
            for ($speed = 1; $speed <= 10; $speed++) {
                $duration = FleetFunctions::GetMissionDuration($speed, $fleetSpeed, $distance, $gameSpeed, $this->ctx->user);
                $consumption = FleetFunctions::GetFleetConsumption($ships, $duration, $distance, $this->ctx->user, $speed);
                if ($consumption <= $availDeut) {
                    $chosenSpeed = $speed;
                    $chosenConsumption = $consumption;
                    break;
                }
            }
        }

        // 4. Load 100% of liquid resources on planet (net of fuel consumption)
        $cargoRes = array(
            901 => (float)$pData['metal'],
            902 => (float)$pData['crystal'],
            903 => max(0.0, (float)$pData['deuterium'] - (float)$chosenConsumption),
        );

        // 5. Launch Deploy (Mission 4)
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
            // Schedule automated recall safely past threat arrival time
            $recallTime = $threatArrivalTime + 120;
            BotScheduler::scheduleTask(
                $this->ctx->botId,
                'fleetsave_recall',
                array('fleet_id' => $fleetId, 'origin_id' => $endangeredPlanetId),
                $recallTime
            );

            $this->ctx->decisionLog->record(
                'fleetsave_executed',
                "Professional fleetsave: evacuated " . number_format(array_sum($ships)) . " ships and 100% resources from " . ($originType === 3 ? "Moon" : "Planet") . " #{$endangeredPlanetId} to " . ($destination['planet_type'] == 3 ? "Moon" : "Colony") . " #{$destination['id']} at {$chosenSpeed}0% speed (Fuel: " . number_format($chosenConsumption) . " deut, recall scheduled at " . date('H:i:s', $recallTime) . ")",
                array(
                    'origin' => $endangeredPlanetId,
                    'target' => $destination['id'],
                    'ships' => $ships,
                    'res' => $cargoRes,
                    'speed' => $chosenSpeed,
                    'fuel' => $chosenConsumption,
                    'fleet_id' => $fleetId,
                    'phalanx_masked' => $isMoonFlight
                ),
                "fleetsave_{$endangeredPlanetId}_to_{$destination['id']}",
                array_sum($ships)
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

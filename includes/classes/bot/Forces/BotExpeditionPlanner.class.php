<?php

require_once 'includes/classes/class.FleetFunctions.php';
require_once 'includes/classes/bot/Execution/ActionGateway.class.php';
require_once 'includes/classes/bot/Kernel/BotClock.class.php';
require_once 'includes/classes/bot/Evaluation/EconomyValuator.class.php';

/**
 * NovaRush Bot AI - Autonomous and Safe Expedition Planner (FASE 1)
 *
 * Responsibilities:
 * 1. Dedicated Expedition Slots: S_exp = max(1, floor(sqrt(Tech 124))) (if Tech 124 > 0, else 0).
 * 2. Slot Shielding: Expeditions only use dedicated slots; military fleets cannot consume them.
 * 3. Guaranteed High-Yield Composition: >= 500.000 MSE and >= 400.000 Cargo Capacity.
 *    (Under 500.000 MSE, MissionCaseExpedition forces drops to 'nothing').
 * 4. Dispatches to Sector :16 in local solar system with 1 hour (3600s) hold time.
 * 5. Full telemetry and atomic deduction of ships and fuel.
 */
class BotExpeditionPlanner
{
    private $ctx;
    private $gateway;

    const MIN_EXPEDITION_MSE        = 500000.0;
    const TARGET_MAX_EXPEDITION_MSE = 38500000.0;
    const MIN_EXPEDITION_CAPACITY   = 400000.0;
    const EXPEDITION_STAY_SECONDS   = 3600;

    public function __construct(BotContext $ctx, BotActionGateway $gateway)
    {
        $this->ctx = $ctx;
        $this->gateway = $gateway;
    }

    /**
     * Plan and dispatch autonomous expeditions across the empire
     *
     * @param BotWorldModel $world
     * @return int Number of dispatched expeditions
     */
    public function planExpeditions(BotWorldModel $world)
    {
        $availableSlots = BotActionGateway::getAvailableExpeditionSlots($this->ctx->botId, $this->ctx->user);
        if ($availableSlots <= 0) {
            return 0;
        }

        $empirePlanets = $world->empire->getPlanets();
        if (!is_array($empirePlanets) || empty($empirePlanets)) {
            return 0;
        }

        // Sort planets: prioritize designated FOB or planets with military armada & cargueros
        global $resource;
        $candidates = array();
        $fobId = (int)($this->ctx->botRow['fob_planet_id'] ?? 0);

        foreach ($empirePlanets as $pId => $pEntry) {
            $pData = $pEntry['data'];
            $cargos       = (int)($pData[$resource[203]] ?? 0);
            $smallCargos  = (int)($pData[$resource[202]] ?? 0);
            $cruisers     = (int)($pData[$resource[206]] ?? 0);
            $lightHunters = (int)($pData[$resource[204]] ?? 0);
            $heavyHunters = (int)($pData[$resource[205]] ?? 0);
            $deut         = (float)($pData['deuterium'] ?? 0.0);

            $hasShips = ($cargos > 0 || $smallCargos > 0 || $cruisers > 0 || $lightHunters > 0);
            if ($hasShips && $deut >= 500.0) {
                $isFob = ($fobId > 0 && (int)$pId === $fobId);
                // Heavy priority to FOB and colonies with major combat fleets
                $score = ($isFob ? 1000000000.0 : 0.0)
                       + ($cruisers * 50000.0)
                       + ($lightHunters * 5000.0)
                       + ($heavyHunters * 10000.0)
                       + ($cargos * 25000.0)
                       + ($smallCargos * 5000.0);

                $candidates[] = array(
                    'id'          => (int)$pId,
                    'data'        => $pData,
                    'isFob'       => $isFob,
                    'score'       => $score,
                );
            }
        }

        if (empty($candidates)) {
            return 0;
        }

        usort($candidates, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        $dispatched = 0;

        foreach ($candidates as $cand) {
            if ($dispatched >= $availableSlots) {
                break;
            }

            $pId = $cand['id'];
            $pData = $cand['data'];

            while ($dispatched < $availableSlots) {
                // Attempt to build guaranteed composition
                $fleetComposition = $this->buildExpeditionFleet($pData);
                if (empty($fleetComposition)) {
                    break;
                }

                $fleetStats = $this->calculateFleetStats($fleetComposition);
                if ($fleetStats['mse'] < self::MIN_EXPEDITION_MSE || $fleetStats['capacity'] < self::MIN_EXPEDITION_CAPACITY) {
                    break;
                }

                // Check deuterium consumption
                $startCoords = array((int)$pData['galaxy'], (int)$pData['system'], (int)$pData['planet']);
                $targetCoords = array((int)$pData['galaxy'], (int)$pData['system'], 16);
                $distance = FleetFunctions::GetTargetDistance($startCoords, $targetCoords);

                $fleetSpeed = FleetFunctions::GetFleetMaxSpeed($fleetComposition, $this->ctx->user);
                $gameSpeed = FleetFunctions::GetGameSpeedFactor();
                $duration = FleetFunctions::GetMissionDuration(10, $fleetSpeed, $distance, $gameSpeed, $this->ctx->user);
                $consumption = FleetFunctions::GetFleetConsumption($fleetComposition, $duration, $distance, $this->ctx->user, 10);

                if ((float)$pData['deuterium'] < $consumption) {
                    break;
                }

                // Dispatch canonical expedition (Mission 15 to local system sector :16 for 1 hour)
                $startTime = 0;
                $ok = $this->gateway->launchFleet(
                    $fleetComposition,
                    15, // Mission 15: Expedition
                    $pId,
                    0,  // Target planet 0 for expedition
                    0,  // Target owner 0 for expedition
                    (int)$pData['galaxy'],
                    (int)$pData['system'],
                    16, // Sector :16
                    1,  // Type: Planet
                    array(),
                    10,
                    $startTime,
                    self::EXPEDITION_STAY_SECONDS
                );

                if ($ok) {
                    $dispatched++;

                    $this->ctx->decisionLog->record(
                        'expedition_dispatch',
                        "Dispatched autonomous expedition to [{$pData['galaxy']}:{$pData['system']}:16] from colony #{$pId} (Fleet MSE: " . number_format($fleetStats['mse'], 0) . ", Capacity: " . number_format($fleetStats['capacity'], 0) . ")",
                        array(
                            'planet_id' => $pId,
                            'fleet'     => $fleetComposition,
                            'mse'       => $fleetStats['mse'],
                            'capacity'  => $fleetStats['capacity'],
                            'duration'  => $duration,
                        ),
                        "expedition_{$pId}_16",
                        5000.0
                    );

                    // Update local planet data in candidate for potential second fleet if slots remain
                    foreach ($fleetComposition as $sId => $cnt) {
                        $pData[$resource[$sId]] = max(0, (int)($pData[$resource[$sId]] ?? 0) - $cnt);
                    }
                    $pData['deuterium'] = max(0.0, (float)$pData['deuterium'] - $consumption);
                } else {
                    break;
                }
            }
        }

        return $dispatched;
    }

    /**
     * Build an expedition fleet meeting >= 500k MSE and >= 400k capacity
     *
     * @param array $planetData
     * @return array [shipId => count]
     */
    public function buildExpeditionFleet(array $planetData)
    {
        global $resource, $pricelist;

        $fleet = array();
        $currMse = 0.0;
        $currCap = 0.0;

        $cruisers     = (int)($planetData[$resource[206]] ?? 0);
        $lightHunters = (int)($planetData[$resource[204]] ?? 0);
        $largeCargos  = (int)($planetData[$resource[203]] ?? 0);
        $smallCargos  = (int)($planetData[$resource[202]] ?? 0);

        // Path A: High-Yield FOB / Military Fleet Expedition (Target up to 38.5M MSE for max Dark Matter yield)
        if ($cruisers >= 50 || $lightHunters >= 200) {
            // Allocate Cruisers (206) - Max 800 or 15% of available
            $cruiserLimit = (int)min(floor($cruisers * 0.15), 800);
            if ($cruiserLimit > 0) {
                $fleet[206] = $cruiserLimit;
                $stats = $this->calculateFleetStats($fleet);
                $currMse = $stats['mse'];
                $currCap = $stats['capacity'];
            }

            // Allocate Light Hunters (204) - Fill remaining MSE up to TARGET_MAX_EXPEDITION_MSE
            $mseDeficit = max(0.0, self::TARGET_MAX_EXPEDITION_MSE - $currMse);
            if ($mseDeficit > 0 && $lightHunters > 0) {
                $shipMse = $this->getShipMSE(204);
                $neededLight = (int)ceil($mseDeficit / max(1.0, $shipMse));
                $lightLimit = (int)min(floor($lightHunters * 0.15), $neededLight);
                if ($lightLimit > 0) {
                    $fleet[204] = $lightLimit;
                    $stats = $this->calculateFleetStats($fleet);
                    $currMse = $stats['mse'];
                    $currCap = $stats['capacity'];
                }
            }

            // Ensure Minimum Cargo Capacity (400,000)
            if ($currCap < self::MIN_EXPEDITION_CAPACITY) {
                $capDeficit = self::MIN_EXPEDITION_CAPACITY - $currCap;
                if ($largeCargos > 0) {
                    $neededLarge = (int)ceil($capDeficit / 25000.0);
                    $takeLarge = min($largeCargos, $neededLarge);
                    if ($takeLarge > 0) {
                        $fleet[203] = $takeLarge;
                        $capDeficit = max(0.0, $capDeficit - ($takeLarge * 25000.0));
                    }
                }
                if ($capDeficit > 0 && $smallCargos > 0) {
                    $neededSmall = (int)ceil($capDeficit / 5000.0);
                    $takeSmall = min($smallCargos, $neededSmall);
                    if ($takeSmall > 0) {
                        $fleet[202] = $takeSmall;
                    }
                }
            }

            // Add 1 Spy Probe (210) if available
            $availProbes = (int)($planetData[$resource[210]] ?? 0);
            if ($availProbes > 0) {
                $fleet[210] = 1;
            }

            $finalStats = $this->calculateFleetStats($fleet);
            if ($finalStats['mse'] >= self::MIN_EXPEDITION_MSE && $finalStats['capacity'] >= self::MIN_EXPEDITION_CAPACITY) {
                return $fleet;
            }
        }

        // Path B: Standard Cargo-First Expedition (for civilian/mining colonies)
        $fleet = array();
        if ($largeCargos >= 30) {
            $fleet[203] = 30;
            $stats = $this->calculateFleetStats($fleet);
            $currMse = $stats['mse'];
            $currCap = $stats['capacity'];
        } else {
            if ($largeCargos > 0) {
                $fleet[203] = $largeCargos;
            }
            $capDeficit = max(0.0, self::MIN_EXPEDITION_CAPACITY - ($largeCargos * 25000.0));
            if ($capDeficit > 0 && $smallCargos > 0) {
                $neededSmall = (int)ceil($capDeficit / 5000.0);
                $takeSmall = min($smallCargos, $neededSmall);
                if ($takeSmall > 0) {
                    $fleet[202] = $takeSmall;
                }
            }

            $stats = $this->calculateFleetStats($fleet);
            $currMse = $stats['mse'];
            $currCap = $stats['capacity'];

            if ($currMse < self::MIN_EXPEDITION_MSE) {
                $combatPriorities = array(206, 207, 205, 204, 215, 213);
                foreach ($combatPriorities as $wId) {
                    $avail = (int)($planetData[$resource[$wId]] ?? 0);
                    if ($avail > 0) {
                        $shipMse = $this->getShipMSE($wId);
                        $needed = (int)ceil((self::MIN_EXPEDITION_MSE - $currMse) / max(1.0, $shipMse));
                        $take = min($avail, $needed);
                        if ($take > 0) {
                            $fleet[$wId] = ($fleet[$wId] ?? 0) + $take;
                            $currMse += $take * $shipMse;
                            if ($currMse >= self::MIN_EXPEDITION_MSE) {
                                break;
                            }
                        }
                    }
                }

                if ($currMse < self::MIN_EXPEDITION_MSE) {
                    $availSmall = (int)($planetData[$resource[202]] ?? 0) - ($fleet[202] ?? 0);
                    if ($availSmall > 0) {
                        $shipMse = $this->getShipMSE(202);
                        $needed = (int)ceil((self::MIN_EXPEDITION_MSE - $currMse) / max(1.0, $shipMse));
                        $take = min($availSmall, $needed);
                        if ($take > 0) {
                            $fleet[202] = ($fleet[202] ?? 0) + $take;
                            $currMse += $take * $shipMse;
                        }
                    }
                }
            }
        }

        $combatIds = array(206, 205, 204, 207, 215);
        $hasCombat = false;
        foreach ($combatIds as $cId) {
            if (!empty($fleet[$cId])) {
                $hasCombat = true;
                break;
            }
        }
        if (!$hasCombat) {
            foreach ($combatIds as $cId) {
                $avail = (int)($planetData[$resource[$cId]] ?? 0);
                if ($avail > 0) {
                    $fleet[$cId] = 1;
                    break;
                }
            }
        }

        $availProbes = (int)($planetData[$resource[210]] ?? 0);
        if ($availProbes > 0 && empty($fleet[210])) {
            $fleet[210] = 1;
        }

        $finalStats = $this->calculateFleetStats($fleet);
        if ($finalStats['mse'] >= self::MIN_EXPEDITION_MSE && $finalStats['capacity'] >= self::MIN_EXPEDITION_CAPACITY) {
            return $fleet;
        }

        return array();
    }

    /**
     * Calculate fleet MSE and total cargo room
     *
     * @param array $fleetArray
     * @return array [mse => float, capacity => float]
     */
    public function calculateFleetStats(array $fleetArray)
    {
        $totalMse = 0.0;
        $totalCap = 0.0;

        foreach ($fleetArray as $shipId => $qty) {
            $qty = (int)$qty;
            if ($qty <= 0) continue;

            $totalMse += $this->getShipMSE($shipId) * $qty;
            $totalCap += $this->getShipCapacity($shipId) * $qty;
        }

        return array(
            'mse'      => $totalMse,
            'capacity' => $totalCap,
        );
    }

    /**
     * Canonical MSE value for a ship: Metal + 2*Crystal + 4*Deuterium
     */
    public function getShipMSE($shipId)
    {
        global $pricelist;
        $m = (float)($pricelist[$shipId]['cost'][901] ?? 0.0);
        $c = (float)($pricelist[$shipId]['cost'][902] ?? 0.0);
        $d = (float)($pricelist[$shipId]['cost'][903] ?? 0.0);
        return $m + (2.0 * $c) + (4.0 * $d);
    }

    /**
     * Capacity of a single ship
     */
    public function getShipCapacity($shipId)
    {
        global $pricelist;
        return (float)($pricelist[$shipId]['capacity'] ?? 0.0);
    }
}

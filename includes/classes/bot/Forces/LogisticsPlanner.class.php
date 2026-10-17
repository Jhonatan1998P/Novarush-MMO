<?php

require_once 'includes/classes/bot/Evaluation/EconomyValuator.class.php';

/**
 * NovaRush Bot AI v2 - Demand-Driven Logistics Planner (Pull / Just-in-Time System)
 *
 * Implements:
 * 1. Pure Pull Model: Resources are dispatched strictly by demand, never blindly pushed.
 * 2. Strict Priority Hierarchy:
 *    - Priority 1: Military fuel (FOB assault armada + recyclers + 25% margin) & Silo MIPs in siege.
 *    - Priority 2: Force recruitment (Hangar & Defense queues).
 *    - Priority 3: Infrastructure (next best ROI building) & Research (idle lab tech).
 * 3. 10-Minute Self-Sufficiency Filter:
 *    - If the deficit can be produced by the target planet itself in <= 10 minutes (hourly_prod / 6),
 *      no convoys are sent (the planet waits).
 * 4. In-Flight Transport Pipeline:
 *    - Resources already in transit (Mission 3) to the destination are deducted to avoid duplicate deliveries.
 * 5. Concurrency & Slot Protection:
 *    - Maximum 2 target destinations can be serviced concurrently.
 *    - At least 3 fleet slots are strictly reserved for combat and espionage.
 * 6. Dual-Donor Selection:
 *    - Up to 2 closest colonies with surplus (above 1 hr production + active queues) and cargo ships.
 *    - Optimal cargo ship packing: 217 (Advanced) -> 203 (Large) -> 202 (Small).
 */
class BotLogisticsPlanner
{
    private $ctx;
    private $gateway;

    const MAX_CONCURRENT_TARGETS = 2;
    const RESERVED_FLEET_SLOTS   = 3;

    public function __construct(BotContext $ctx, BotActionGateway $gateway)
    {
        $this->ctx = $ctx;
        $this->gateway = $gateway;
    }

    /**
     * Map logistics demand to a specific BotBudgetManager category
     *
     * @param array $demand
     * @return string 'mines', 'fleet', 'defense', 'research'
     */
    public static function getDemandCategory(array $demand)
    {
        $type = $demand['type'] ?? '';
        if ($type === 'building') {
            return 'mines';
        }
        if ($type === 'research') {
            return 'research';
        }
        if ($type === 'military_fuel') {
            return 'fleet';
        }
        if ($type === 'shipyard') {
            $elementId = (int)($demand['element_id'] ?? 0);
            return ($elementId >= 400) ? 'defense' : 'fleet';
        }
        return 'mines';
    }

    /**
     * Coordinate and dispatch demand-driven supply convoys across the empire
     *
     * @param BotEmpireState $empire
     * @param int $hubPlanetId Optional FOB / Staging base ID
     * @param BotWorldModel|null $world
     * @return int Number of supply convoys dispatched
     */
    public function dispatchConvoys(BotEmpireState $empire, $hubPlanetId = 0, $world = null)
    {
        $db = Database::get();

        // 0. Active in-flight transports in %%FLEETS%% (Mission 3)
        $activeTransRow = $db->selectSingle(
            "SELECT COUNT(*) as cnt FROM %%FLEETS%% WHERE fleet_owner = :botId AND fleet_mission = 3;",
            array(':botId' => $this->ctx->botId)
        );
        $activeTransports = !empty($activeTransRow) ? (int)$activeTransRow['cnt'] : 0;

        // Check slot availability early, strictly respecting shielded military slots
        // and reserving a dedicated margin (3 slots) for offensive / combat / recycler / spy operations
        $availableMil   = BotActionGateway::getAvailableMilitarySlots($this->ctx->botId, $this->ctx->user);
        $sMilitary      = BotActionGateway::getMilitarySlots($this->ctx->user);
        
        // Strict logistics slot cap: maximum 35% of military slots minus in-flight transports
        $maxLogisticsCap = max(1, min(self::MAX_CONCURRENT_TARGETS, (int)floor($sMilitary * 0.35)));
        $logisticsSlotsRemaining = max(0, $maxLogisticsCap - $activeTransports);
        $combatMargin    = min(3, max(1, $sMilitary - 1));
        
        $availableSlots = max(0, min($logisticsSlotsRemaining, $availableMil - $combatMargin));

        if ($availableSlots <= 0) {
            return 0;
        }

        // 1. Gather all active in-flight destination targets & categorised resources
        $db = Database::get();

        // 1a. Pending logistics tasks in uni1_bot_tasks with category and resources tags
        $pendingLogisticsTasks = $db->select(
            "SELECT payload FROM %%BOT_TASKS%% 
             WHERE bot_id = :botId AND task_type = 'logistics_arrival_spend' AND status = 'pending';",
            array(':botId' => $this->ctx->botId)
        );

        $inFlightByCategory = array();
        $activeTargetDestinations = array();

        if (!empty($pendingLogisticsTasks)) {
            foreach ($pendingLogisticsTasks as $plt) {
                $taskPayload = @json_decode($plt['payload'], true);
                if (!is_array($taskPayload) || empty($taskPayload['planet_id'])) {
                    continue;
                }
                $destPId = (int)$taskPayload['planet_id'];
                $cat = !empty($taskPayload['category']) ? $taskPayload['category'] : 'mines';
                $activeTargetDestinations[$destPId] = true;

                if (!isset($inFlightByCategory[$destPId][$cat])) {
                    $inFlightByCategory[$destPId][$cat] = array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);
                }
                if (!empty($taskPayload['resources'])) {
                    $inFlightByCategory[$destPId][$cat]['metal']     += (float)($taskPayload['resources']['metal'] ?? $taskPayload['resources'][901] ?? 0.0);
                    $inFlightByCategory[$destPId][$cat]['crystal']   += (float)($taskPayload['resources']['crystal'] ?? $taskPayload['resources'][902] ?? 0.0);
                    $inFlightByCategory[$destPId][$cat]['deuterium'] += (float)($taskPayload['resources']['deuterium'] ?? $taskPayload['resources'][903] ?? 0.0);
                }
            }
        }

        // 1b. Active fleet movements in uni1_fleets (Missions 3 & 4) for slot & destination concurrency
        $inFlightTransports = $db->select(
            "SELECT fleet_id, fleet_start_id, fleet_end_id, fleet_mess, fleet_mission, fleet_resource_metal, fleet_resource_crystal, fleet_resource_deuterium 
             FROM %%FLEETS%% 
             WHERE fleet_owner = :botId AND fleet_mission IN (3, 4);",
            array(':botId' => $this->ctx->botId)
        );

        if (!empty($inFlightTransports)) {
            foreach ($inFlightTransports as $ift) {
                // If fleet is returning (fleet_mess == 1), cargo is heading to fleet_start_id; if outbound (0), to fleet_end_id
                $pEnd = ((int)$ift['fleet_mess'] === 1) ? (int)$ift['fleet_start_id'] : (int)$ift['fleet_end_id'];
                $activeTargetDestinations[$pEnd] = true;
            }
        }

        $convoysDispatched = 0;
        $servicedDistinctTargets = array_keys($activeTargetDestinations);

        // 1c. Moon Loot Evacuation Guard:
        // Evacuate massive loot accumulated on moons (planet_type = 3) to their parent planets
        $moons = $empire->getMoons();
        if (!empty($moons) && $availableSlots > 0) {
            global $resource, $pricelist;
            $cap217 = isset($pricelist[217]['capacity']) ? (float)$pricelist[217]['capacity'] : 400000000;
            $cap203 = isset($pricelist[203]['capacity']) ? (float)$pricelist[203]['capacity'] : 25000;
            $cap202 = isset($pricelist[202]['capacity']) ? (float)$pricelist[202]['capacity'] : 5000;

            foreach ($moons as $mId => $mEntry) {
                if ($availableSlots <= 0) {
                    break;
                }
                $mData = $mEntry['data'];
                $physM = (float)($mData['metal'] ?? 0.0);
                $physC = (float)($mData['crystal'] ?? 0.0);
                $physD = (float)($mData['deuterium'] ?? 0.0);
                $mseMC = BotEconomyValuator::toMSE($physM, $physC, 0.0);

                if ($physM > 5000000.0 || $physC > 5000000.0 || $physD > 25000000.0) {
                    // Find parent planet
                    $parentPlanet = null;
                    $parentPlanetId = 0;
                    foreach ($empire->getPlanets() as $pId => $pEntry) {
                        if ((int)$pEntry['data']['galaxy'] === (int)$mData['galaxy'] &&
                            (int)$pEntry['data']['system'] === (int)$mData['system'] &&
                            (int)$pEntry['data']['planet'] === (int)$mData['planet']) {
                            $parentPlanet = $pEntry['data'];
                            $parentPlanetId = (int)$pId;
                            break;
                        }
                    }

                    if ($parentPlanetId <= 0 || empty($parentPlanet)) {
                        continue;
                    }

                    // Check if moon or parent already has an active outgoing transport or inbound deploy
                    if (isset($activeTargetDestinations[$parentPlanetId]) || isset($activeTargetDestinations[$mId])) {
                        continue;
                    }

                    $cargos217 = (int)($mData[$resource[217]] ?? 0);
                    $cargos203 = (int)($mData[$resource[203]] ?? 0);
                    $cargos202 = (int)($mData[$resource[202]] ?? 0);
                    $totalMoonCargoCap = ($cargos217 * $cap217) + ($cargos203 * $cap203) + ($cargos202 * $cap202);

                    $evacM = max(0.0, $physM - 100000.0);
                    $evacC = max(0.0, $physC - 100000.0);
                    $evacD = max(0.0, $physD - 25000000.0); // Retener 25M Deu para phalanx / reserva segura
                    $totalToEvac = $evacM + $evacC + $evacD;

                    if ($totalMoonCargoCap > 0 && $totalToEvac > 0) {
                        // Dispatch Mission 3 (Transport) from moon to parent planet
                        $takeRatio = min(1.0, $totalMoonCargoCap / $totalToEvac);
                        $sendM = floor($evacM * $takeRatio);
                        $sendC = floor($evacC * $takeRatio);
                        $sendD = floor($evacD * $takeRatio);
                        $cargoSum = $sendM + $sendC + $sendD;

                        $shipsToSend = array();
                        $needCap = $cargoSum;
                        if ($cargos217 > 0 && $needCap > 0) {
                            $u217 = min($cargos217, (int)ceil($needCap / $cap217));
                            $shipsToSend[217] = $u217;
                            $needCap -= ($u217 * $cap217);
                        }
                        if ($cargos203 > 0 && $needCap > 0) {
                            $u203 = min($cargos203, (int)ceil($needCap / $cap203));
                            $shipsToSend[203] = $u203;
                            $needCap -= ($u203 * $cap203);
                        }
                        if ($cargos202 > 0 && $needCap > 0) {
                            $u202 = min($cargos202, (int)ceil($needCap / $cap202));
                            $shipsToSend[202] = $u202;
                            $needCap -= ($u202 * $cap202);
                        }

                        if (!empty($shipsToSend)) {
                            $cargoResources = array(901 => $sendM, 902 => $sendC, 903 => $sendD);
                            $startTime = 0;
                            $ok = $this->gateway->launchFleet(
                                $shipsToSend,
                                3, // Mission 3: Transport to parent planet
                                $mId,
                                $parentPlanetId,
                                $this->ctx->botId,
                                (int)$parentPlanet['galaxy'],
                                (int)$parentPlanet['system'],
                                (int)$parentPlanet['planet'],
                                1, // Parent planet_type = 1
                                $cargoResources,
                                10,
                                $startTime
                            );

                            if ($ok) {
                                $availableSlots--;
                                $convoysDispatched++;
                                $activeTargetDestinations[$parentPlanetId] = true;
                                $this->ctx->decisionLog->record(
                                    'moon_loot_evacuation',
                                    "Evacuación de botín lunar desde luna #{$mId} hacia planeta padre #{$parentPlanetId}: " . number_format($cargoSum) . " recursos (" . number_format($sendM) . "M, " . number_format($sendC) . "C, " . number_format($sendD) . "D)",
                                    array('moon' => $mId, 'parent' => $parentPlanetId, 'res' => $cargoResources, 'ships' => $shipsToSend),
                                    "moon_evac_{$mId}_to_{$parentPlanetId}",
                                    $cargoSum
                                );
                                continue;
                            }
                        }
                    } elseif ($totalToEvac > 5000000.0) {
                        // Moon has no cargos or insufficient, but accumulates > 5M loot to evacuate!
                        // Dispatch empty cargo ships (Mission 4 Deploy) from parent planet to the moon
                        $pCargos217 = (int)($parentPlanet[$resource[217]] ?? 0);
                        $pCargos203 = (int)($parentPlanet[$resource[203]] ?? 0);
                        $pCargos202 = (int)($parentPlanet[$resource[202]] ?? 0);
                        $parentCargoCap = ($pCargos217 * $cap217) + ($pCargos203 * $cap203) + ($pCargos202 * $cap202);

                        if ($parentCargoCap > 0) {
                            $neededCap = $totalToEvac;
                            $deployShips = array();
                            if ($pCargos217 > 0 && $neededCap > 0) {
                                $u217 = min($pCargos217, (int)ceil($neededCap / $cap217));
                                $deployShips[217] = $u217;
                                $neededCap -= ($u217 * $cap217);
                            }
                            if ($pCargos203 > 0 && $neededCap > 0) {
                                $u203 = min($pCargos203, (int)ceil($neededCap / $cap203));
                                $deployShips[203] = $u203;
                                $neededCap -= ($u203 * $cap203);
                            }
                            if ($pCargos202 > 0 && $neededCap > 0) {
                                $u202 = min($pCargos202, (int)ceil($neededCap / $cap202));
                                $deployShips[202] = $u202;
                                $neededCap -= ($u202 * $cap202);
                            }

                            if (!empty($deployShips)) {
                                $startTime = 0;
                                $ok = $this->gateway->launchFleet(
                                    $deployShips,
                                    4, // Mission 4: Deploy to moon
                                    $parentPlanetId,
                                    $mId,
                                    $this->ctx->botId,
                                    (int)$mData['galaxy'],
                                    (int)$mData['system'],
                                    (int)$mData['planet'],
                                    3, // Moon planet_type = 3
                                    array(),
                                    10,
                                    $startTime
                                );
                                if ($ok) {
                                    $availableSlots--;
                                    $convoysDispatched++;
                                    $activeTargetDestinations[$mId] = true;
                                    $this->ctx->decisionLog->record(
                                        'cargos_deployed_to_moon',
                                        "Despliegue de cargueros vacíos desde planeta padre #{$parentPlanetId} hacia luna #{$mId} para evacuar botín masivo (" . number_format($mseMC) . " MSE)",
                                        array('parent' => $parentPlanetId, 'moon' => $mId, 'ships' => $deployShips),
                                        "deploy_cargos_to_moon_{$mId}",
                                        $mseMC
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }

        // 2. Collect and rank empire-wide demands by strict priority
        $demands = $this->collectEmpireDemands($empire, $world, $hubPlanetId);
        if (empty($demands)) {
            return $convoysDispatched;
        }

        // Sort demands: Priority 1 (Military) -> Priority 2 (Recruitment) -> Priority 3 (Economy/Tech)
        usort($demands, function($a, $b) {
            if ($a['priority'] !== $b['priority']) {
                return $a['priority'] <=> $b['priority'];
            }
            return (float)($b['urgency_score'] ?? 0) <=> (float)($a['urgency_score'] ?? 0);
        });

        $servicedDistinctTargets = array_unique(array_merge($servicedDistinctTargets, array_keys($activeTargetDestinations)));

        // 3. Process demands sequentially
        foreach ($demands as $demand) {
            if ($availableSlots <= 0) {
                break;
            }

            $targetPlanetId = (int)$demand['target_planet_id'];
            $targetBody = $empire->getPlanet($targetPlanetId);
            if (empty($targetBody)) {
                continue;
            }
            $tData = $targetBody['data'];

            // Monopolio por Destino: No despachar múltiples convoyes simultáneos a la misma colonia
            if (isset($activeTargetDestinations[$targetPlanetId])) {
                continue;
            }

            // Concurrency rule: Maximum 2 distinct target destinations can receive convoys
            if (count($servicedDistinctTargets) >= self::MAX_CONCURRENT_TARGETS && !in_array($targetPlanetId, $servicedDistinctTargets)) {
                continue;
            }

            $demandCat = self::getDemandCategory($demand);

            // In-flight deduction for this destination & specific category
            $inFlight = isset($inFlightByCategory[$targetPlanetId][$demandCat])
                ? $inFlightByCategory[$targetPlanetId][$demandCat]
                : array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);

            // Compute Net Deficit after applying the 10-minute self-sufficiency rule and budget category check
            $netDeficit = $this->calculateNetDeficit($tData, $demand['required'], $inFlight, $demandCat, $targetPlanetId);
            $totalDeficit = $netDeficit['metal'] + $netDeficit['crystal'] + $netDeficit['deuterium'];

            if ($totalDeficit <= 0) {
                // Demand already covered by warehouse, in-flight convoys, or local 10-minute production
                continue;
            }

            // Anti-Drip Filter (Filtro Anti-Goteo):
            // Prohibir micro-convoyes por déficits insignificantes (< 5M MSE o < 30 mins producción local),
            // a menos que sea Prioridad 1 (combustible militar), Luna (planet_type = 3) o Colonia en arranque inicial (minas < 10/8).
            $isUnderdevelopedColony = ((string)($tData['planet_type'] ?? '1') === '1' && ((int)($tData['metal_mine'] ?? 0) < 10 || (int)($tData['crystal_mine'] ?? 0) < 8));
            if ((int)($demand['priority'] ?? 3) !== 1 && (string)($tData['planet_type'] ?? '1') !== '3' && !$isUnderdevelopedColony) {
                $deficitMSE = BotEconomyValuator::toMSE((float)$netDeficit['metal'], (float)$netDeficit['crystal'], (float)$netDeficit['deuterium']);
                $halfHourProdMSE = 0.5 * BotEconomyValuator::toMSE(
                    (float)($tData['metal_perhour'] ?? 0.0),
                    (float)($tData['crystal_perhour'] ?? 0.0),
                    (float)($tData['deuterium_perhour'] ?? 0.0)
                );
                if ($deficitMSE < 5000000.0 && $deficitMSE < $halfHourProdMSE) {
                    continue;
                }
            }

            // Target coordinates
            $targetCoords = array(
                (int)$tData['galaxy'],
                (int)$tData['system'],
                (int)$tData['planet'],
                (int)$tData['planet_type']
            );

            // 4. Find closest candidate donors with surplus in this specific category and cargo ships
            $candidateDonors = $this->findBestDonorsForDemand($empire, $targetPlanetId, $targetCoords, $demandCat);
            if (empty($candidateDonors)) {
                continue;
            }

            // 5. Pool de Donantes: Hasta el 33% de colonias más cercanas con excedente donable positivo
            $maxDonors = max(1, (int)ceil(count($empire->getPlanets()) * 0.33));
            $remainingM = $netDeficit['metal'];
            $remainingC = $netDeficit['crystal'];
            $remainingD = $netDeficit['deuterium'];

            $donorsUsedCount = 0;
            foreach ($candidateDonors as $donor) {
                if ($availableSlots <= 0 || $donorsUsedCount >= $maxDonors) {
                    break;
                }
                if ($remainingM <= 0 && $remainingC <= 0 && $remainingD <= 0) {
                    break;
                }

                $dispatched = $this->dispatchSingleDonorConvoy(
                    $donor,
                    $targetPlanetId,
                    $targetCoords,
                    $remainingM,
                    $remainingC,
                    $remainingD,
                    $demand,
                    $demandCat
                );

                if ($dispatched > 0) {
                    $convoysDispatched++;
                    $availableSlots--;
                    $donorsUsedCount++;

                    if (!in_array($targetPlanetId, $servicedDistinctTargets)) {
                        $servicedDistinctTargets[] = $targetPlanetId;
                    }
                    $activeTargetDestinations[$targetPlanetId] = true;
                    // Destination Monopoly: Only 1 donor dispatch per target planet per cycle
                    break;
                }
            }
        }

        return $convoysDispatched;
    }

    /**
     * Compute Net Deficit applying:
     * 1. Category budget available stock + In-flight category transport subtraction
     * 2. The 10-minute self-sufficiency rule:
     *    If missing amount <= 10 min of local production for that category (hourly / 6 * r_k), deficit is 0.
     */
    public function calculateNetDeficit(array $pData, array $required, array $inFlight, $category = 'mines', $planetId = 0)
    {
        $metalPerHour = isset($pData['metal_perhour']) ? (float)$pData['metal_perhour'] : 0.0;
        $cryPerHour   = isset($pData['crystal_perhour']) ? (float)$pData['crystal_perhour'] : 0.0;
        $deutPerHour  = isset($pData['deuterium_perhour']) ? (float)$pData['deuterium_perhour'] : 0.0;

        $ratio = 1.0;
        if (isset($this->ctx->budgetManager)) {
            $ratios = $this->ctx->budgetManager->getMacroRatios();
            $ratio = (float)($ratios[$category] ?? 1.0);
        }

        // 10 minutes production threshold = (hourly / 6) * category ratio
        $prod10MinM = ($metalPerHour / 6.0) * $ratio;
        $prod10MinC = ($cryPerHour / 6.0) * $ratio;
        $prod10MinD = ($deutPerHour / 6.0) * $ratio;

        $availM = (float)($pData['metal'] ?? 0);
        $availC = (float)($pData['crystal'] ?? 0);
        $availD = (float)($pData['deuterium'] ?? 0);

        if (isset($this->ctx->budgetManager) && $planetId > 0) {
            $credits = $this->ctx->budgetManager->getCredits();
            if ($category === 'research') {
                $catCredits = $credits['research'] ?? array();
            } else {
                $catCredits = $credits['planets'][$planetId][$category] ?? array();
            }
            $budgetM = (float)($catCredits['metal'] ?? 0.0);
            $budgetC = (float)($catCredits['crystal'] ?? 0.0);
            $budgetD = (float)($catCredits['deuterium'] ?? 0.0);

            $availM = min($availM, $budgetM);
            $availC = min($availC, $budgetC);
            $availD = min($availD, $budgetD);
        }

        $curM = $availM + (float)($inFlight['metal'] ?? 0);
        $curC = $availC + (float)($inFlight['crystal'] ?? 0);
        $curD = $availD + (float)($inFlight['deuterium'] ?? 0);

        $reqM = (float)($required['metal'] ?? $required[901] ?? 0);
        $reqC = (float)($required['crystal'] ?? $required[902] ?? 0);
        $reqD = (float)($required['deuterium'] ?? $required[903] ?? 0);

        $defM = max(0.0, $reqM - $curM);
        $defC = max(0.0, $reqC - $curC);
        $defD = max(0.0, $reqD - $curD);

        // 10-minute filter: If deficit can be produced locally in <= 10 mins, do NOT send supplies
        if ($defM <= $prod10MinM) {
            $defM = 0.0;
        }
        if ($defC <= $prod10MinC) {
            $defC = 0.0;
        }
        if ($defD <= $prod10MinD) {
            $defD = 0.0;
        }

        return array('metal' => $defM, 'crystal' => $defC, 'deuterium' => $defD);
    }

    /**
     * Collect and rank empire demands across Priority 1, 2, and 3
     */
    public function collectEmpireDemands(BotEmpireState $empire, $world, $hubPlanetId)
    {
        $demands = array();

        // --- Priority 1: Military Fuel at FOB & Missile Silos in Siege ---
        $milFuel = $this->estimateMilitaryFuelDemand($empire, $world, $hubPlanetId);
        if (!empty($milFuel)) {
            $demands[] = $milFuel;
        }

        // --- Priority 1b: Staging Colonies Military Fuel Demand ---
        $stagingFuelDemands = $this->estimateStagingFuelDemands($empire, $hubPlanetId);
        if (!empty($stagingFuelDemands)) {
            foreach ($stagingFuelDemands as $sfd) {
                $demands[] = $sfd;
            }
        }

        $siloDemands = $this->estimateSiloSiegeDemand($empire, $world);
        if (!empty($siloDemands)) {
            foreach ($siloDemands as $sd) {
                $demands[] = $sd;
            }
        }

        // --- Priority 2: Force Recruitment (Hangar & Defense queues) ---
        $recDemands = $this->estimateRecruitmentDemands($empire, $world);
        if (!empty($recDemands)) {
            foreach ($recDemands as $rd) {
                $demands[] = $rd;
            }
        }

        // --- Priority 3: Infrastructure (Mines/Nanite) & Idle Lab Research ---
        $infraDemands = $this->estimateInfrastructureDemands($empire, $world);
        if (!empty($infraDemands)) {
            foreach ($infraDemands as $ind) {
                $demands[] = $ind;
            }
        }

        // --- Priority 3b: Lunar Infrastructure Demands & Phalanx Fuel ---
        $lunarDemands = $this->estimateLunarDemands($empire);
        if (!empty($lunarDemands)) {
            foreach ($lunarDemands as $ld) {
                $demands[] = $ld;
            }
        }

        // --- Priority 3c: Constrained Colony Supply Demands ($freeFields <= 5) ---
        $constrainedDemands = $this->estimateConstrainedColonyDemands($empire);
        if (!empty($constrainedDemands)) {
            foreach ($constrainedDemands as $cd) {
                $demands[] = $cd;
            }
        }

        // --- Priority 4: Surplus Balancing Demands (> 50M idle surplus) ---
        $surplusDemands = $this->estimateSurplusBalancingDemands($empire);
        if (!empty($surplusDemands)) {
            foreach ($surplusDemands as $sd) {
                $demands[] = $sd;
            }
        }

        return $demands;
    }

    /**
     * Priority 4: Surplus Balancing Demands
     * Redistributes massive idle surplus (> 50M MSE) to colonies or FOBs with deficits
     */
    public function estimateSurplusBalancingDemands(BotEmpireState $empire)
    {
        $demands = array();
        $allBodies = $empire->getAllBodies();
        if (count($allBodies) < 2) {
            return $demands;
        }

        $hasMajorSurplus = false;
        foreach ($allBodies as $b) {
            $pData = $b['data'];
            $stockMSE = BotEconomyValuator::toMSE(
                (float)($pData['metal'] ?? 0),
                (float)($pData['crystal'] ?? 0),
                (float)($pData['deuterium'] ?? 0)
            );
            if ($stockMSE > 50000000.0) {
                $hasMajorSurplus = true;
                break;
            }
        }

        if (!$hasMajorSurplus) {
            return $demands;
        }

        $planets = $empire->getPlanets();
        foreach ($planets as $pId => $p) {
            $pData = $p['data'];
            $stockMSE = BotEconomyValuator::toMSE(
                (float)($pData['metal'] ?? 0),
                (float)($pData['crystal'] ?? 0),
                (float)($pData['deuterium'] ?? 0)
            );
            if ($stockMSE < 15000000.0) {
                $defM = max(0.0, 10000000.0 - (float)($pData['metal'] ?? 0));
                $defC = max(0.0, 6000000.0 - (float)($pData['crystal'] ?? 0));
                $defD = max(0.0, 2000000.0 - (float)($pData['deuterium'] ?? 0));
                if (($defM + $defC + $defD) > 0) {
                    $demands[] = array(
                        'target_planet_id' => $pId,
                        'type'             => 'surplus_balancing',
                        'priority'         => 4,
                        'urgency_score'    => 1000.0,
                        'required'         => array('metal' => $defM, 'crystal' => $defC, 'deuterium' => $defD),
                        'reason'           => "Rebalanceo de excedentes imperiales (>50M MSE) hacia colonia #{$pId}",
                    );
                }
            }
        }

        return $demands;
    }

    /**
     * Priority 1: Estimate military fuel for active target (Armada + Recyclers + 25% margin)
     */
    public function estimateMilitaryFuelDemand(BotEmpireState $empire, $world, $fobId)
    {
        if ($fobId <= 0) {
            return null;
        }

        $lockedGalaxy = isset($this->ctx->botRow['target_galaxy']) ? (int)$this->ctx->botRow['target_galaxy'] : 0;
        $lockedSystem = isset($this->ctx->botRow['target_system']) ? (int)$this->ctx->botRow['target_system'] : 0;
        $lockedPlanet = isset($this->ctx->botRow['target_planet']) ? (int)$this->ctx->botRow['target_planet'] : 0;

        if ($lockedGalaxy <= 0 || $lockedSystem <= 0 || $lockedPlanet <= 0) {
            return null;
        }

        $fobBody = $empire->getPlanet($fobId);
        if (empty($fobBody)) {
            return null;
        }
        $fobData = $fobBody['data'];

        // Histéresis Anti-Ping-Pong: Demanda de military_fuel solo si deuterio físico en destino < 15M
        $curFobDeut = (float)($fobData['deuterium'] ?? 0.0);
        if ($curFobDeut >= 15000000.0) {
            return null;
        }

        $intel = null;
        if ($world !== null && isset($world->intel)) {
            $intelHalfLife = isset($this->ctx->difficulty->intelMaxAgeHours) ? (float)$this->ctx->difficulty->intelMaxAgeHours : 16.0;
            $intel = $world->intel->getIntel($lockedGalaxy, $lockedSystem, $lockedPlanet, 1, $intelHalfLife);
        }

        $targetDefenders = array();
        $lootNeeded = array();
        if (!empty($intel)) {
            $targetDefenders = (isset($intel['defense']) ? $intel['defense'] : array()) + (isset($intel['fleet']) ? $intel['fleet'] : array());
            $lootNeeded = array(
                'metal'     => isset($intel['metal']) ? (float)$intel['metal'] * 0.50 : 0,
                'crystal'   => isset($intel['crystal']) ? (float)$intel['crystal'] * 0.50 : 0,
                'deuterium' => isset($intel['deuterium']) ? (float)$intel['deuterium'] * 0.50 : 0,
            );
        }

        $strikePlanner = new BotStrikePlanner($this->ctx, $this->gateway);
        $strikeFleet = $strikePlanner->buildTacticalStrikeFleet($fobData, $targetDefenders, $lootNeeded, 'siege');
        if (empty($strikeFleet)) {
            return null;
        }

        $fSpeed = FleetFunctions::GetFleetMaxSpeed($strikeFleet, $this->ctx->user);
        $fCoords = array($fobData['galaxy'], $fobData['system'], $fobData['planet']);
        $tCoords = array($lockedGalaxy, $lockedSystem, $lockedPlanet);
        $fDist  = FleetFunctions::GetTargetDistance($fCoords, $tCoords);
        $gSpeed = FleetFunctions::GetGameSpeedFactor();
        $fDuration = FleetFunctions::GetMissionDuration(10, $fSpeed, $fDist, $gSpeed, $this->ctx->user);
        $fuelCombat = FleetFunctions::GetFleetConsumption($strikeFleet, $fDuration, $fDist, $this->ctx->user, 10);

        // Recyclers fuel estimate
        $fuelRecyclers = 0.0;
        $expectedDebris = 0.0;
        if (!empty($intel['fleet'])) {
            foreach ($intel['fleet'] as $sId => $cnt) {
                if ($sId == 212 || $cnt <= 0) continue;
                $fCdr = Config::get()->Fleet_Cdr / 100.0;
                $expectedDebris += BotEconomyValuator::getElementPriceMSE($sId, $cnt) * $fCdr;
            }
        }
        if ($expectedDebris > 0) {
            $neededRecyclers = min(
                (int)($fobData['recycler'] ?? 0),
                (int)ceil($expectedDebris / 20000.0)
            );
            if ($neededRecyclers > 0) {
                $recSpeed = FleetFunctions::GetFleetMaxSpeed(array(209 => $neededRecyclers), $this->ctx->user);
                $recDuration = FleetFunctions::GetMissionDuration(10, $recSpeed, $fDist, $gSpeed, $this->ctx->user);
                $fuelRecyclers = FleetFunctions::GetFleetConsumption(array(209 => $neededRecyclers), $recDuration, $fDist, $this->ctx->user, 10);
            }
        }

        // Exact formula: Fuel = (Fleet + Recyclers) * 1.25 (+25% margin)
        $totalFuelNeeded = ($fuelCombat + $fuelRecyclers) * 1.25;

        return array(
            'target_planet_id' => $fobId,
            'type'             => 'military_fuel',
            'priority'         => 1,
            'urgency_score'    => 10000.0,
            'required'         => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => $totalFuelNeeded),
            'reason'           => "Combustible para armada de asalto y recicladores con 25% de margen hacia [{$lockedGalaxy}:{$lockedSystem}:{$lockedPlanet}]",
        );
    }

    /**
     * Priority 1: Estimate military fuel demand for colonies with large garrisons needing to deploy to FOB
     */
    public function estimateStagingFuelDemands(BotEmpireState $empire, $hubPlanetId)
    {
        if ($hubPlanetId <= 0) {
            return array();
        }
        global $reslist, $resource;
        $demands = array();
        $fleetList = (isset($reslist['fleet']) && is_array($reslist['fleet'])) ? $reslist['fleet'] : array();

        foreach ($empire->getPlanets() as $pId => $pBody) {
            if ($pId == $hubPlanetId) continue;
            $pData = $pBody['data'];

            $combatShipsCount = 0;
            foreach ($fleetList as $sId) {
                if (in_array($sId, array(202, 203, 208, 209, 210, 212, 217, 219, 233))) continue;
                $combatShipsCount += (int)($pData[$resource[$sId]] ?? 0);
            }

            $deutAvail = (float)($pData['deuterium'] ?? 0.0);
            if ($combatShipsCount >= 5000 && $deutAvail < 15000000.0) {
                $demands[] = array(
                    'target_planet_id' => $pId,
                    'type'             => 'military_fuel',
                    'priority'         => 1,
                    'urgency_score'    => 8500.0,
                    'required'         => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 150000000.0),
                    'reason'           => "Combustible de despliegue para armada de refuerzo en colonia #{$pId} (" . number_format($combatShipsCount) . " naves)",
                );
            }
        }
        return $demands;
    }

    /**
     * Priority 1: Silo MIP demand during siege
     */
    public function estimateSiloSiegeDemand(BotEmpireState $empire, $world)
    {
        $demands = array();
        $isSiegeActive = (!empty($this->ctx->botRow['target_type']) && $this->ctx->botRow['target_type'] === 'siege');
        if (!$isSiegeActive) {
            return $demands;
        }

        $lockedGalaxy = isset($this->ctx->botRow['target_galaxy']) ? (int)$this->ctx->botRow['target_galaxy'] : 0;
        $lockedSystem = isset($this->ctx->botRow['target_system']) ? (int)$this->ctx->botRow['target_system'] : 0;
        if ($lockedGalaxy <= 0 || $lockedSystem <= 0) {
            return $demands;
        }

        global $resource;
        $impulse = isset($this->ctx->user[$resource[117]]) ? (int)$this->ctx->user[$resource[117]] : 0;
        $range = FleetFunctions::getMissileRange($impulse);

        $allBodies = $empire->getAllBodies();
        foreach ($allBodies as $bId => $body) {
            $pData = $body['data'];
            if ((string)($pData['planet_type'] ?? '1') === '3') continue;
            $siloLevel = isset($pData[$resource[44]]) ? (int)$pData[$resource[44]] : 0;
            if ($siloLevel < 4) continue;

            $pG = (int)$pData['galaxy'];
            $pS = (int)$pData['system'];
            if ($pG != $lockedGalaxy) continue;

            $sDist = abs($pS - $lockedSystem);
            if ($sDist > $range) continue;

            $rocketsCap = BuildFunctions::getMaxConstructibleRockets($this->ctx->user, $pData);
            $canFitMIPs = isset($rocketsCap[503]) ? (int)$rocketsCap[503] : 0;

            if ($canFitMIPs > 0) {
                $batchMIPs = min(15, $canFitMIPs);
                $neededMetal = $batchMIPs * 12500.0;
                $neededDeut  = $batchMIPs * 2500.0;

                $demands[] = array(
                    'target_planet_id' => $bId,
                    'type'             => 'shipyard',
                    'element_id'       => 503,
                    'count'            => $batchMIPs,
                    'priority'         => 1,
                    'urgency_score'    => 5000.0 + ($batchMIPs * 50.0),
                    'required'         => array('metal' => $neededMetal, 'crystal' => 0.0, 'deuterium' => $neededDeut),
                    'reason'           => "Recursos para producir {$batchMIPs}x MIPs de asedio en silo de colonia #{$bId}",
                );
            }
        }
        return $demands;
    }

    /**
     * Priority 2: Force Recruitment (Hangar & Defense queues)
     */
    public function estimateRecruitmentDemands(BotEmpireState $empire, $world)
    {
        $demands = array();
        $allBodies = $empire->getAllBodies();
        global $resource, $pricelist;

        foreach ($allBodies as $bId => $body) {
            if ((string)($body['data']['planet_type'] ?? '1') === '3') continue;
            $queuedJobs = !empty($body['hangar_queue']) ? count($body['hangar_queue']) : 0;
            if ($queuedJobs >= 5) continue;

            $pData = $body['data'];
            $shipyardLvl = isset($pData[$resource[21]]) ? (int)$pData[$resource[21]] : 0;
            if ($shipyardLvl < 1) continue;

            $heavyId = isset($this->ctx->botRow['doctrine_heavy']) ? (int)$this->ctx->botRow['doctrine_heavy'] : 215;
            if (!BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, $heavyId, array())) {
                $heavyId = 207;
                if (!BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 207, array())) {
                    $heavyId = 206;
                }
            }

            if (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, $heavyId, array())) {
                $costM = isset($pricelist[$heavyId]['cost'][901]) ? (float)$pricelist[$heavyId]['cost'][901] : 0.0;
                $costC = isset($pricelist[$heavyId]['cost'][902]) ? (float)$pricelist[$heavyId]['cost'][902] : 0.0;
                $costD = isset($pricelist[$heavyId]['cost'][903]) ? (float)$pricelist[$heavyId]['cost'][903] : 0.0;

                $batch = 10;
                $demands[] = array(
                    'target_planet_id' => $bId,
                    'type'             => 'shipyard',
                    'element_id'       => $heavyId,
                    'count'            => $batch,
                    'priority'         => 2,
                    'urgency_score'    => 2000.0,
                    'required'         => array('metal' => $costM * $batch, 'crystal' => $costC * $batch, 'deuterium' => $costD * $batch),
                    'reason'           => "Recursos para reclutamiento de {$batch}x unidad #{$heavyId} en hangar de #{$bId}",
                );
            }
        }
        return $demands;
    }

    /**
     * Priority 3: Infrastructure & Idle Lab Research
     */
    public function estimateInfrastructureDemands(BotEmpireState $empire, $world)
    {
        $demands = array();
        global $resource;

        $planets = $empire->getPlanets();
        $targetBuildings = array(15, 4, 1, 2, 3, 14, 21, 31, 44);

        foreach ($planets as $pId => $pEntry) {
            if (!empty($pEntry['building_queue']) && count($pEntry['building_queue']) >= 2) continue;
            $pData = $pEntry['data'];

            $isYoungColony = ((int)($pData['metal_mine'] ?? 0) < 10 || (int)($pData['crystal_mine'] ?? 0) < 8);

            foreach ($targetBuildings as $bId) {
                if (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, $bId, array())) {
                    $cost = BuildFunctions::getElementPrice($this->ctx->user, $pData, $bId);
                    $reqM = isset($cost[901]) ? (float)$cost[901] : 0.0;
                    $reqC = isset($cost[902]) ? (float)$cost[902] : 0.0;
                    $reqD = isset($cost[903]) ? (float)$cost[903] : 0.0;

                    if ($pData['metal'] < $reqM || $pData['crystal'] < $reqC || $pData['deuterium'] < $reqD) {
                        $demands[] = array(
                            'target_planet_id' => $pId,
                            'type'             => 'building',
                            'element_id'       => $bId,
                            'count'            => 1,
                            'priority'         => 3,
                            'urgency_score'    => $isYoungColony ? 3500.0 : 1000.0,
                            'required'         => array('metal' => $reqM, 'crystal' => $reqC, 'deuterium' => $reqD),
                            'reason'           => "Construcción de {$resource[$bId]} en planeta #{$pId}",
                        );
                        break;
                    }
                }
            }
        }

        // Research demand for idle lab
        if (empty($this->ctx->user['b_tech_planet']) && empty($this->ctx->user['b_tech'])) {
            $labId = $this->ctx->homePlanetId > 0 ? $this->ctx->homePlanetId : 0;
            if ($labId > 0) {
                $labBody = $empire->getPlanet($labId);
                if (!empty($labBody)) {
                    $labData = $labBody['data'];
                    $techs = array(115, 117, 118, 108, 109, 110, 111, 120, 121, 122);
                    foreach ($techs as $tId) {
                        if (BuildFunctions::isTechnologieAccessible($this->ctx->user, $labData, $tId, array())) {
                            $cost = BuildFunctions::getElementPrice($this->ctx->user, $labData, $tId);
                            $reqM = isset($cost[901]) ? (float)$cost[901] : 0.0;
                            $reqC = isset($cost[902]) ? (float)$cost[902] : 0.0;
                            $reqD = isset($cost[903]) ? (float)$cost[903] : 0.0;
                            if ($labData['metal'] < $reqM || $labData['crystal'] < $reqC || $labData['deuterium'] < $reqD) {
                                $demands[] = array(
                                    'target_planet_id' => $labId,
                                    'type'             => 'research',
                                    'element_id'       => $tId,
                                    'count'            => 1,
                                    'priority'         => 3,
                                    'urgency_score'    => 800.0,
                                    'required'         => array('metal' => $reqM, 'crystal' => $reqC, 'deuterium' => $reqD),
                                    'reason'           => "Investigación de tecnología #{$tId} en laboratorio de #{$labId}",
                                );
                                break;
                            }
                        }
                    }
                }
            }
        }

        return $demands;
    }

    /**
     * Priority 3b: Estimate Lunar Infrastructure and Phalanx Fuel Demand
     */
    public function estimateLunarDemands(BotEmpireState $empire)
    {
        global $resource;
        $demands = array();
        $moons = $empire->getMoons();

        if (!is_array($moons) || empty($moons)) {
            return $demands;
        }

        foreach ($moons as $mId => $moonEntry) {
            $mId = (int)$mId;
            if (!empty($moonEntry['building_queue']) && count($moonEntry['building_queue']) >= 2) {
                continue;
            }

            $mData = $moonEntry['data'];
            $freeMoonFields = CalculateMaxPlanetFields($mData) - ((int)$mData['field_current'] + count($moonEntry['building_queue']));

            $curJumpGate = (int)($mData[$resource[43]] ?? 0);
            $curRobots   = (int)($mData[$resource[14]] ?? 0);
            $curNano     = (int)($mData[$resource[15]] ?? 0);
            $curPhalanx  = (int)($mData[$resource[42]] ?? 0);
            $curMondbasis= (int)($mData[$resource[41]] ?? 0);
            $curHangar   = (int)($mData[$resource[21]] ?? 0);

            // 1. Phalanx Deuterium Buffer (500,000 D for scans)
            $curDeut = (float)($mData['deuterium'] ?? 0.0);
            if ($curDeut < 500000.0) {
                $needD = 500000.0 - $curDeut;
                $demands[] = array(
                    'target_planet_id' => $mId,
                    'type'             => 'military_fuel',
                    'priority'         => 1, // High priority military fuel for Phalanx
                    'urgency_score'    => 4000.0,
                    'required'         => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => $needD),
                    'reason'           => "Buffer operativo de Deuterio para escaneos de Sensor Phalanx en luna #{$mId}",
                );
            }

            // Si la luna ya tiene Salto Cuántico (sprungtor >= 1), Base Lunar (mondbasis >= 6) y Phalanx (phalanx >= 1),
            // se considera 100% OPERATIVA y NO debe generar más demandas de edificios ni absorción de recursos.
            $isOperative = ($curJumpGate >= 1 && $curMondbasis >= 6 && $curPhalanx >= 1);
            if ($isOperative) {
                continue;
            }

            // 2. Determinar edificio objetivo según doctrina lunar
            $targetElemId = null;
            $urgency = 1500.0;
            $priority = 3;

            // Prioridad máxima a Salto Cuántico (10/10 Jump Gates): si curJumpGate == 0
            if ($curJumpGate == 0) {
                $priority = 2;
                $urgency = 9500.0;
                if ($curMondbasis < 6) {
                    $targetElemId = 41; // mondbasis
                } else {
                    $targetElemId = 43; // sprungtor
                }
            } else {
                if ($freeMoonFields <= 1) {
                    $targetElemId = 41;
                    $urgency = 3200.0;
                } else {
                    if ($curRobots < 5) {
                        $targetElemId = 14;
                        $urgency = 2500.0;
                    } elseif ($curNano < 2 && BuildFunctions::isTechnologieAccessible($this->ctx->user, $mData, 15, array())) {
                        $targetElemId = 15;
                        $urgency = 2600.0;
                    } elseif ($curPhalanx < 6 && BuildFunctions::isTechnologieAccessible($this->ctx->user, $mData, 42, array())) {
                        $targetElemId = 42;
                        $urgency = 2200.0;
                    } elseif ($curMondbasis < 6) {
                        $targetElemId = 41;
                        $urgency = 2000.0;
                    }
                }
            }

            if ($targetElemId !== null && BuildFunctions::isTechnologieAccessible($this->ctx->user, $mData, $targetElemId, array())) {
                $cost = BuildFunctions::getElementPrice($this->ctx->user, $mData, $targetElemId);
                $reqM = (float)($cost[901] ?? $cost['metal'] ?? 0.0);
                $reqC = (float)($cost[902] ?? $cost['crystal'] ?? 0.0);
                $reqD = (float)($cost[903] ?? $cost['deuterium'] ?? 0.0);

                // Multiply by 2 for cheap early levels to avoid repetitive trips (unless priority 2 Jump Gate)
                if ($priority !== 2) {
                    $costMSE = BotEconomyValuator::toMSE($reqM, $reqC, $reqD);
                    if ($costMSE < 10000000.0) {
                        $reqM *= 2.0;
                        $reqC *= 2.0;
                        $reqD *= 2.0;
                    }
                }

                $curStockM = (float)($mData['metal'] ?? 0.0);
                $curStockC = (float)($mData['crystal'] ?? 0.0);
                $curStockD = (float)($mData['deuterium'] ?? 0.0);

                if ($curStockM < $reqM || $curStockC < $reqC || $curStockD < $reqD) {
                    $demands[] = array(
                        'target_planet_id' => $mId,
                        'type'             => 'building',
                        'element_id'       => $targetElemId,
                        'count'            => 1,
                        'priority'         => $priority,
                        'urgency_score'    => $urgency,
                        'required'         => array('metal' => $reqM, 'crystal' => $reqC, 'deuterium' => $reqD),
                        'reason'           => "Abastecimiento lunar prioritario para {$resource[$targetElemId]} en luna #{$mId}",
                    );
                }
            }
        }

        return $demands;
    }

    /**
     * Priority 3c: Estimate supply demands for colonies with <= 5 free fields needing Terraformer or Stores
     */
    public function estimateConstrainedColonyDemands(BotEmpireState $empire)
    {
        global $resource;
        $demands = array();
        $planets = $empire->getPlanets();

        if (!is_array($planets) || empty($planets)) {
            return $demands;
        }

        foreach ($planets as $pId => $pEntry) {
            $pId = (int)$pId;
            if (!empty($pEntry['building_queue']) && count($pEntry['building_queue']) >= 2) {
                continue;
            }

            $pData = $pEntry['data'];
            $freeFields = CalculateMaxPlanetFields($pData) - ((int)$pData['field_current'] + count($pEntry['building_queue']));

            if ($freeFields <= 5) {
                $curTerra = (int)($pData[$resource[33]] ?? 0);
                $nextTerraCost = BuildFunctions::getElementPrice($this->ctx->user, $pData, 33, false, $curTerra + 1);
                $costC = (float)($nextTerraCost[902] ?? $nextTerraCost['crystal'] ?? 0.0);
                $costD = (float)($nextTerraCost[903] ?? $nextTerraCost['deuterium'] ?? 0.0);
                $maxC  = (float)($pData['crystal_max'] ?? 0.0);
                $maxD  = (float)($pData['deuterium_max'] ?? 0.0);

                $targetElement = 33;
                $targetCost = $nextTerraCost;

                if ($costC > 0.85 * $maxC && BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 23, array())) {
                    $targetElement = 23;
                    $targetCost = BuildFunctions::getElementPrice($this->ctx->user, $pData, 23);
                } elseif ($costD > 0.85 * $maxD && BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 24, array())) {
                    $targetElement = 24;
                    $targetCost = BuildFunctions::getElementPrice($this->ctx->user, $pData, 24);
                }

                $reqM = (float)($targetCost[901] ?? $targetCost['metal'] ?? 0.0);
                $reqC = (float)($targetCost[902] ?? $targetCost['crystal'] ?? 0.0);
                $reqD = (float)($targetCost[903] ?? $targetCost['deuterium'] ?? 0.0);

                $curM = (float)($pData['metal'] ?? 0.0);
                $curC = (float)($pData['crystal'] ?? 0.0);
                $curD = (float)($pData['deuterium'] ?? 0.0);

                if ($curM < $reqM || $curC < $reqC || $curD < $reqD) {
                    $demands[] = array(
                        'target_planet_id' => $pId,
                        'type'             => 'building',
                        'element_id'       => $targetElement,
                        'count'            => 1,
                        'priority'         => 3,
                        'urgency_score'    => 2800.0,
                        'required'         => array('metal' => $reqM, 'crystal' => $reqC, 'deuterium' => $reqD),
                        'reason'           => "Asistencia logística proactiva para {$resource[$targetElement]} en colonia saturada #{$pId} ({$freeFields} campos libres)",
                    );
                }
            }
        }

        return $demands;
    }

    /**
     * Find candidate donor colonies sorted by ascending distance to destination
     */
    public function findBestDonorsForDemand(BotEmpireState $empire, $targetPlanetId, array $targetCoords, $category = 'mines')
    {
        global $resource, $pricelist;
        $allBodies = $empire->getAllBodies();
        $candidateDonors = array();

        foreach ($allBodies as $bId => $body) {
            if ($bId == $targetPlanetId) continue;
            $dData = $body['data'];

            // Donor surplus checked against category and safety reserve
            if (isset($this->ctx->budgetManager)) {
                $surplus = $this->ctx->budgetManager->getDonorSurplusByCategory($bId, $category, $dData);
                $surplusM = $surplus['metal'];
                $surplusC = $surplus['crystal'];
                $surplusD = $surplus['deuterium'];
            } else {
                // Fallback: 2 hours of local production reserve
                $reserveM = max(50000.0, 2.0 * (float)($dData['metal_perhour'] ?? 0));
                $reserveC = max(30000.0, 2.0 * (float)($dData['crystal_perhour'] ?? 0));
                $reserveD = max(20000.0, 2.0 * (float)($dData['deuterium_perhour'] ?? 0));

                $surplusM = max(0.0, (float)$dData['metal'] - $reserveM);
                $surplusC = max(0.0, (float)$dData['crystal'] - $reserveC);
                $surplusD = max(0.0, (float)$dData['deuterium'] - $reserveD);
            }

            // Histéresis de Ping-Pong de Deuterio:
            // Donante solo dona deuterio si su propio deuterio físico es >= 65M (65,000,000).
            // Y siempre retiene al menos 45M (45,000,000) de deuterio físico tras el despacho.
            if ($category === 'fleet') {
                $physD = (float)($dData['deuterium'] ?? 0.0);
                if ($physD < 65000000.0) {
                    $surplusD = 0.0;
                } else {
                    $maxDonateD = max(0.0, $physD - 45000000.0);
                    $surplusD = min($surplusD, $maxDonateD);
                }
            }

            if ($surplusM <= 0 && $surplusC <= 0 && $surplusD <= 0) {
                continue;
            }

            // Check cargo ships available on donor
            $cargos217 = isset($dData[$resource[217]]) ? (int)$dData[$resource[217]] : 0;
            $cargos203 = isset($dData[$resource[203]]) ? (int)$dData[$resource[203]] : 0;
            $cargos202 = isset($dData[$resource[202]]) ? (int)$dData[$resource[202]] : 0;

            $cap217 = isset($pricelist[217]['capacity']) ? (float)$pricelist[217]['capacity'] : 400000000;
            $cap203 = isset($pricelist[203]['capacity']) ? (float)$pricelist[203]['capacity'] : 25000;
            $cap202 = isset($pricelist[202]['capacity']) ? (float)$pricelist[202]['capacity'] : 5000;

            $totalCargoCap = ($cargos217 * $cap217) + ($cargos203 * $cap203) + ($cargos202 * $cap202);
            if ($totalCargoCap <= 0) {
                continue;
            }

            $dCoords = array($dData['galaxy'], $dData['system'], $dData['planet']);
            $dist = FleetFunctions::GetTargetDistance($dCoords, array($targetCoords[0], $targetCoords[1], $targetCoords[2]));

            $candidateDonors[] = array(
                'planet_id' => $bId,
                'data'      => $dData,
                'distance'  => $dist,
                'surplus'   => array('metal' => $surplusM, 'crystal' => $surplusC, 'deuterium' => $surplusD),
                'cargos'    => array(217 => $cargos217, 203 => $cargos203, 202 => $cargos202),
                'capacity'  => $totalCargoCap,
            );
        }

        $targetBody = $empire->getPlanet($targetPlanetId);
        $targetIsMoon = ($targetBody && (string)($targetBody['data']['planet_type'] ?? '1') === '3');

        if ($targetIsMoon) {
            foreach ($candidateDonors as &$cd) {
                // Parent planet has identical coords [galaxy, system, planet] and planet_type = 1
                if ((string)($cd['data']['planet_type'] ?? '1') === '1'
                    && (int)$cd['data']['galaxy'] === (int)$targetCoords[0]
                    && (int)$cd['data']['system'] === (int)$targetCoords[1]
                    && (int)$cd['data']['planet'] === (int)$targetCoords[2]) {
                    $cd['distance'] = -1; // Top priority parent planet!
                }
            }
            unset($cd);
        }

        usort($candidateDonors, function($a, $b) {
            return $a['distance'] <=> $b['distance'];
        });

        return $candidateDonors;
    }

    /**
     * Dispatch convoy from a single donor to cover part or all of the remaining deficit
     */
    private function dispatchSingleDonorConvoy(
        array $donor,
        $targetPlanetId,
        array $targetCoords,
        &$remainingM,
        &$remainingC,
        &$remainingD,
        array $demand,
        $category = 'mines'
    ) {
        global $pricelist;

        $giveM = min($remainingM, $donor['surplus']['metal']);
        $giveC = min($remainingC, $donor['surplus']['crystal']);
        $giveD = min($remainingD, $donor['surplus']['deuterium']);

        // Histéresis de Ping-Pong de Deuterio:
        if ($category === 'fleet' || ($demand['type'] ?? '') === 'military_fuel') {
            $donorPhysD = (float)($donor['data']['deuterium'] ?? 0.0);
            if ($donorPhysD < 65000000.0) {
                $giveD = 0.0;
            } else {
                $maxDonateD = max(0.0, $donorPhysD - 45000000.0);
                $giveD = min($giveD, $maxDonateD);
            }
        }

        $totalToGive = $giveM + $giveC + $giveD;

        if ($totalToGive <= 0) {
            return 0;
        }

        // Limit strictly by donor cargo capacity
        $cargoCap = $donor['capacity'];
        if ($cargoCap < $totalToGive) {
            $ratio = $cargoCap / $totalToGive;
            $giveM = floor($giveM * $ratio);
            $giveC = floor($giveC * $ratio);
            $giveD = floor($giveD * $ratio);
            $totalToGive = $giveM + $giveC + $giveD;
        }

        if ($totalToGive <= 0) {
            return 0;
        }

        $cap217 = isset($pricelist[217]['capacity']) ? (float)$pricelist[217]['capacity'] : 400000000;
        $cap203 = isset($pricelist[203]['capacity']) ? (float)$pricelist[203]['capacity'] : 25000;
        $cap202 = isset($pricelist[202]['capacity']) ? (float)$pricelist[202]['capacity'] : 5000;

        $shipsToSend = array();
        $needCap = $totalToGive;

        // Optimal ship packing: 217 -> 203 -> 202
        if ($donor['cargos'][217] > 0 && $needCap > 0) {
            $u217 = min($donor['cargos'][217], (int)ceil($needCap / $cap217));
            $shipsToSend[217] = $u217;
            $needCap -= ($u217 * $cap217);
        }
        if ($donor['cargos'][203] > 0 && $needCap > 0) {
            $u203 = min($donor['cargos'][203], (int)ceil($needCap / $cap203));
            $shipsToSend[203] = $u203;
            $needCap -= ($u203 * $cap203);
        }
        if ($donor['cargos'][202] > 0 && $needCap > 0) {
            $u202 = min($donor['cargos'][202], (int)ceil($needCap / $cap202));
            $shipsToSend[202] = $u202;
            $needCap -= ($u202 * $cap202);
        }

        if (empty($shipsToSend)) {
            return 0;
        }

        $cargoResources = array(
            901 => $giveM,
            902 => $giveC,
            903 => $giveD,
        );

        $startTime = 0;
        $ok = $this->gateway->launchFleet(
            $shipsToSend,
            3, // Mission 3: Transport
            $donor['planet_id'],
            $targetPlanetId,
            $this->ctx->botId,
            $targetCoords[0],
            $targetCoords[1],
            $targetCoords[2],
            $targetCoords[3],
            $cargoResources,
            10,
            $startTime
        );

        if ($ok) {
            $remainingM -= $giveM;
            $remainingC -= $giveC;
            $remainingD -= $giveD;

            // Deduct immediately from donor's budget category
            if (isset($this->ctx->budgetManager)) {
                $this->ctx->budgetManager->debitDonorCategory($donor['planet_id'], $category, $cargoResources);
            }

            // Tarea programada en uni1_bot_tasks (logistics_arrival_spend) para acreditar a la llegada y consumir
            $currentSimTime = class_exists('BotClock') ? BotClock::get() : TIMESTAMP;
            $arrival = ($startTime > 0) ? $startTime : ($currentSimTime + 120);
            BotScheduler::scheduleTask(
                $this->ctx->botId,
                'logistics_arrival_spend',
                array(
                    'planet_id'  => (int)$targetPlanetId,
                    'element_id' => (int)($demand['element_id'] ?? 0),
                    'type'       => $demand['type'] ?? 'building',
                    'category'   => $category,
                    'resources'  => array(
                        'metal'     => (float)$giveM,
                        'crystal'   => (float)$giveC,
                        'deuterium' => (float)$giveD,
                    ),
                    'count'      => (int)($demand['count'] ?? 1),
                ),
                $arrival + 2
            );

            $this->ctx->decisionLog->record(
                'logistics_supply_dispatched',
                "Suministro a demanda despachado desde colonia #{$donor['planet_id']} hacia #{$targetPlanetId} (" . ucfirst($category) . " - " . ucfirst($demand['type']) . "): " . number_format($totalToGive) . " recursos (" . number_format($giveM) . "M, " . number_format($giveC) . "C, " . number_format($giveD) . "D)",
                array('from' => $donor['planet_id'], 'to' => $targetPlanetId, 'category' => $category, 'demand' => $demand['type'], 'res' => $cargoResources, 'ships' => $shipsToSend),
                "supply_{$donor['planet_id']}_to_{$targetPlanetId}_{$category}",
                $totalToGive
            );

            return 1;
        }

        return 0;
    }
}

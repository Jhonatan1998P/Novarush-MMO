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
     * Coordinate and dispatch demand-driven supply convoys across the empire
     *
     * @param BotEmpireState $empire
     * @param int $hubPlanetId Optional FOB / Staging base ID
     * @param BotWorldModel|null $world
     * @return int Number of supply convoys dispatched
     */
    public function dispatchConvoys(BotEmpireState $empire, $hubPlanetId = 0, $world = null)
    {
        // Check slot availability early
        $maxSlots = FleetFunctions::GetMaxFleetSlots($this->ctx->user);
        $actualFleets = FleetFunctions::GetCurrentFleets($this->ctx->botId);
        $availableSlots = max(0, ($maxSlots - self::RESERVED_FLEET_SLOTS) - $actualFleets);

        if ($availableSlots <= 0) {
            return 0;
        }

        // 1. Gather all active in-flight destination targets (Missions 3 & 4: Transport and Deploy)
        $db = Database::get();
        $inFlightTransports = $db->select(
            "SELECT fleet_id, fleet_start_id, fleet_end_id, fleet_mess, fleet_mission, fleet_resource_metal, fleet_resource_crystal, fleet_resource_deuterium 
             FROM %%FLEETS%% 
             WHERE fleet_owner = :botId AND fleet_mission IN (3, 4);",
            array(':botId' => $this->ctx->botId)
        );

        $activeTargetDestinations = array();
        $inFlightByPlanet = array();
        if (!empty($inFlightTransports)) {
            foreach ($inFlightTransports as $ift) {
                // If fleet is returning (fleet_mess == 1), cargo is heading to fleet_start_id; if outbound (0), to fleet_end_id
                $pEnd = ((int)$ift['fleet_mess'] === 1) ? (int)$ift['fleet_start_id'] : (int)$ift['fleet_end_id'];
                $activeTargetDestinations[$pEnd] = true;
                if (!isset($inFlightByPlanet[$pEnd])) {
                    $inFlightByPlanet[$pEnd] = array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);
                }
                $inFlightByPlanet[$pEnd]['metal']     += (float)$ift['fleet_resource_metal'];
                $inFlightByPlanet[$pEnd]['crystal']   += (float)$ift['fleet_resource_crystal'];
                $inFlightByPlanet[$pEnd]['deuterium'] += (float)$ift['fleet_resource_deuterium'];
            }
        }

        // 2. Collect and rank empire-wide demands by strict priority
        $demands = $this->collectEmpireDemands($empire, $world, $hubPlanetId);
        if (empty($demands)) {
            return 0;
        }

        // Sort demands: Priority 1 (Military) -> Priority 2 (Recruitment) -> Priority 3 (Economy/Tech)
        usort($demands, function($a, $b) {
            if ($a['priority'] !== $b['priority']) {
                return $a['priority'] <=> $b['priority'];
            }
            return (float)($b['urgency_score'] ?? 0) <=> (float)($a['urgency_score'] ?? 0);
        });

        $convoysDispatched = 0;
        $servicedDistinctTargets = array_keys($activeTargetDestinations);

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

            // Concurrency rule: Maximum 2 distinct target destinations can receive convoys
            if (count($servicedDistinctTargets) >= self::MAX_CONCURRENT_TARGETS && !in_array($targetPlanetId, $servicedDistinctTargets)) {
                continue;
            }

            // In-flight deduction for this destination
            $inFlight = isset($inFlightByPlanet[$targetPlanetId])
                ? $inFlightByPlanet[$targetPlanetId]
                : array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);

            // Compute Net Deficit after applying the 10-minute self-sufficiency rule
            $netDeficit = $this->calculateNetDeficit($tData, $demand['required'], $inFlight);
            $totalDeficit = $netDeficit['metal'] + $netDeficit['crystal'] + $netDeficit['deuterium'];

            if ($totalDeficit <= 0) {
                // Demand already covered by warehouse, in-flight convoys, or local 10-minute production
                continue;
            }

            // Target coordinates
            $targetCoords = array(
                (int)$tData['galaxy'],
                (int)$tData['system'],
                (int)$tData['planet'],
                (int)$tData['planet_type']
            );

            // 4. Find closest candidate donors with surplus and cargo ships
            $candidateDonors = $this->findBestDonorsForDemand($empire, $targetPlanetId, $targetCoords);
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
                    $demand
                );

                if ($dispatched > 0) {
                    $convoysDispatched++;
                    $availableSlots--;
                    $donorsUsedCount++;

                    if (!in_array($targetPlanetId, $servicedDistinctTargets)) {
                        $servicedDistinctTargets[] = $targetPlanetId;
                    }
                }
            }
        }

        return $convoysDispatched;
    }

    /**
     * Compute Net Deficit applying:
     * 1. Warehouses + In-flight transport subtraction
     * 2. The 10-minute self-sufficiency rule:
     *    If missing amount <= 10 min of local production (hourly / 6), deficit is 0.
     */
    public function calculateNetDeficit(array $pData, array $required, array $inFlight)
    {
        $metalPerHour = isset($pData['metal_perhour']) ? (float)$pData['metal_perhour'] : 0.0;
        $cryPerHour   = isset($pData['crystal_perhour']) ? (float)$pData['crystal_perhour'] : 0.0;
        $deutPerHour  = isset($pData['deuterium_perhour']) ? (float)$pData['deuterium_perhour'] : 0.0;

        // 10 minutes production threshold = hourly / 6
        $prod10MinM = $metalPerHour / 6.0;
        $prod10MinC = $cryPerHour / 6.0;
        $prod10MinD = $deutPerHour / 6.0;

        $curM = (float)($pData['metal'] ?? 0) + (float)($inFlight['metal'] ?? 0);
        $curC = (float)($pData['crystal'] ?? 0) + (float)($inFlight['crystal'] ?? 0);
        $curD = (float)($pData['deuterium'] ?? 0) + (float)($inFlight['deuterium'] ?? 0);

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

        $intel = null;
        if ($world !== null && isset($world->intel)) {
            $intel = $world->intel->getIntel($lockedGalaxy, $lockedSystem, $lockedPlanet, 1, 4.0);
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
            $siloLevel = isset($pData[$resource[44]]) ? (int)$pData[$resource[44]] : 0;
            if ($siloLevel < 4) continue;

            $pG = (int)$pData['galaxy'];
            $pS = (int)$pData['system'];
            if ($pG != $lockedGalaxy) continue;

            $sDist = abs($pS - $lockedSystem);
            if ($sDist > $range) continue;

            $maxSpaces = $siloLevel * 10;
            $cur502 = isset($pData[$resource[502]]) ? (int)$pData[$resource[502]] : 0;
            $cur503 = isset($pData[$resource[503]]) ? (int)$pData[$resource[503]] : 0;
            $freeSpaces = max(0, $maxSpaces - (($cur502 * 1) + ($cur503 * 2)));
            $canFitMIPs = (int)floor($freeSpaces / 2);

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
        $targetBuildings = array(15, 1, 2, 3, 14, 21, 31, 44);

        foreach ($planets as $pId => $pEntry) {
            if (!empty($pEntry['building_queue']) && count($pEntry['building_queue']) >= 2) continue;
            $pData = $pEntry['data'];

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
                            'urgency_score'    => 1000.0,
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
     * Find candidate donor colonies sorted by ascending distance to destination
     */
    public function findBestDonorsForDemand(BotEmpireState $empire, $targetPlanetId, array $targetCoords)
    {
        global $resource, $pricelist;
        $allBodies = $empire->getAllBodies();
        $candidateDonors = array();

        foreach ($allBodies as $bId => $body) {
            if ($bId == $targetPlanetId) continue;
            $dData = $body['data'];

            // Donor safety reserve: 1 hour of local production
            $reserveM = max(50000.0, (float)($dData['metal_perhour'] ?? 0));
            $reserveC = max(30000.0, (float)($dData['crystal_perhour'] ?? 0));
            $reserveD = max(20000.0, (float)($dData['deuterium_perhour'] ?? 0));

            $surplusM = max(0.0, (float)$dData['metal'] - $reserveM);
            $surplusC = max(0.0, (float)$dData['crystal'] - $reserveC);
            $surplusD = max(0.0, (float)$dData['deuterium'] - $reserveD);

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
        array $demand
    ) {
        global $pricelist;

        $giveM = min($remainingM, $donor['surplus']['metal']);
        $giveC = min($remainingC, $donor['surplus']['crystal']);
        $giveD = min($remainingD, $donor['surplus']['deuterium']);
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

            // Tarea programada en uni1_bot_tasks (logistics_arrival_spend) para consumir inmediatamente a la llegada (+2s) con 0 recursos expuestos
            if (!empty($demand['element_id'])) {
                $arrival = ($startTime > 0) ? $startTime : (TIMESTAMP + 120);
                BotScheduler::scheduleTask(
                    $this->ctx->botId,
                    'logistics_arrival_spend',
                    array(
                        'planet_id'  => (int)$targetPlanetId,
                        'element_id' => (int)$demand['element_id'],
                        'type'       => $demand['type'] ?? 'building',
                        'count'      => (int)($demand['count'] ?? 1),
                    ),
                    $arrival + 2
                );
            }

            $this->ctx->decisionLog->record(
                'logistics_supply_dispatched',
                "Suministro a demanda despachado desde colonia #{$donor['planet_id']} hacia #{$targetPlanetId} (" . ucfirst($demand['type']) . "): " . number_format($totalToGive) . " recursos (" . number_format($giveM) . "M, " . number_format($giveC) . "C, " . number_format($giveD) . "D)",
                array('from' => $donor['planet_id'], 'to' => $targetPlanetId, 'demand' => $demand['type'], 'res' => $cargoResources, 'ships' => $shipsToSend),
                "supply_{$donor['planet_id']}_to_{$targetPlanetId}",
                $totalToGive
            );

            return 1;
        }

        return 0;
    }
}

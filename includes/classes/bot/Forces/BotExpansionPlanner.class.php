<?php

require_once 'includes/classes/bot/Kernel/BotClock.class.php';
require_once 'includes/classes/bot/Kernel/BotContext.class.php';
require_once 'includes/classes/bot/Execution/ActionGateway.class.php';
require_once 'includes/classes/bot/Perception/WorldModel.class.php';
require_once 'includes/classes/bot/BotNameHelper.class.php';
require_once 'includes/classes/PlayerUtil.class.php';
require_once 'includes/classes/class.FleetFunctions.php';
require_once 'includes/classes/class.BuildFunctions.php';

/**
 * NovaRush Bot AI - Subsystem for Imperial Expansion & Lunar Operations
 *
 * Responsibilities:
 * A: Autonomous production of Colony Ships (#208).
 * B: Optimal coordinate selection & dispatch of Mission 7 (Colonisation) with Seed Cargo Kit.
 * C: Moonshot Doctrine & Organic Moon Creation (20% Moon Chance via 715 Light Fighters / 2.002M debris).
 */
class BotExpansionPlanner
{
    private $ctx;
    private $gateway;

    public function __construct(BotContext $ctx, BotActionGateway $gateway)
    {
        $this->ctx = $ctx;
        $this->gateway = $gateway;
    }

    /**
     * Main lifecycle orchestrator for imperial expansion
     *
     * @param BotWorldModel $world
     * @return array
     */
    public function planExpansion(BotWorldModel $world)
    {
        $stats = array(
            'colonizers_recruited' => 0,
            'colonies_dispatched'  => 0,
            'moonshot_attempts'    => 0,
            'moons_created'        => 0,
        );

        // 1. Autonomous production of Colony Ships (#208)
        $stats['colonizers_recruited'] = $this->recruitColonizers($world);

        // 2. Selection and dispatch of Colonization Fleets (Mission 7)
        $stats['colonies_dispatched'] = $this->dispatchColonizationMissions($world);

        // 3. Moonshot Doctrine & Lunar Creation (20% chance)
        $moonResults = $this->executeMoonshots($world);
        $stats['moonshot_attempts'] = $moonResults['attempts'];
        $stats['moons_created']     = $moonResults['moons_created'];

        return $stats;
    }

    /**
     * Responsabilidad A: Producción Autónoma de Colonizadores (ID 208)
     *
     * @param BotWorldModel $world
     * @return int Number of colonizers queued
     */
    public function recruitColonizers(BotWorldModel $world)
    {
        global $resource;

        $maxPlanets = (int)PlayerUtil::maxPlanetCount($this->ctx->user);
        $currentPlanets = count($world->empire->getPlanets());

        $db = Database::get();
        $inFlightRow = $db->selectSingle(
            "SELECT COUNT(*) as cnt FROM %%FLEETS%% WHERE fleet_owner = :bid AND fleet_mission = 7 AND fleet_mess = 0;",
            array(':bid' => $this->ctx->botId)
        );
        $inFlightColonyShips = (int)($inFlightRow['cnt'] ?? 0);
        $openSlots = $maxPlanets - ($currentPlanets + $inFlightColonyShips);

        if ($openSlots <= 0) {
            return 0;
        }

        // Count stationed + queued colonizers
        $stationedColonizers = 0;
        $queuedColonizers = 0;
        $planets = $world->empire->getPlanets();

        foreach ($planets as $pEntry) {
            $pData = $pEntry['data'];
            $stationedColonizers += (int)($pData[$resource[208]] ?? 0);

            $hQueue = !empty($pEntry['hangar_queue']) ? $pEntry['hangar_queue'] : array();
            foreach ($hQueue as $hq) {
                if (isset($hq[0]) && (int)$hq[0] === 208) {
                    $queuedColonizers += (int)$hq[1];
                }
            }
        }

        $totalAvailable = $stationedColonizers + $queuedColonizers;
        $needed = $openSlots - $totalAvailable;
        if ($needed <= 0) {
            return 0;
        }

        $maxConcurrent = isset($this->ctx->difficulty) ? $this->ctx->difficulty->getMaxConcurrentColonizations() : 1;
        $toRecruit = min($maxConcurrent, $needed);
        $recruited = 0;

        // Sort candidate planets prioritizing home planet, FOB, and planets with higher resources and shipyard
        $homeId = (int)($this->ctx->botRow['home_planet_id'] ?? 0);
        $fobId  = (int)($this->ctx->botRow['fob_planet_id'] ?? 0);
        uasort($planets, function($a, $b) use ($homeId, $fobId) {
            $aId = (int)$a['id'];
            $bId = (int)$b['id'];
            if ($aId === $homeId) return -1;
            if ($bId === $homeId) return 1;
            if ($aId === $fobId) return -1;
            if ($bId === $fobId) return 1;
            $aRes = (float)($a['data']['metal'] ?? 0) + (float)($a['data']['crystal'] ?? 0);
            $bRes = (float)($b['data']['metal'] ?? 0) + (float)($b['data']['crystal'] ?? 0);
            return $bRes <=> $aRes;
        });

        foreach ($planets as $pId => $pEntry) {
            if ($recruited >= $toRecruit) {
                break;
            }

            $pData = $pEntry['data'];
            if (!BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 208, array())) {
                continue;
            }

            $cost = BuildFunctions::getElementPrice($this->ctx->user, $pData, 208, false, 1);
            $costM = (float)($cost[901] ?? $cost['metal'] ?? 10000.0);
            $costC = (float)($cost[902] ?? $cost['crystal'] ?? 20000.0);
            $costD = (float)($cost[903] ?? $cost['deuterium'] ?? 10000.0);

            $availM = (float)$pData['metal'];
            $availC = (float)$pData['crystal'];
            $availD = (float)$pData['deuterium'];

            // Exigir reserva fisica de combustible: $availD >= $costD + 15000.0
            if ($availM < $costM || $availC < $costC || $availD < ($costD + 15000.0)) {
                continue;
            }

            $canAffordBudget = (!isset($this->ctx->budgetManager)) || $this->ctx->budgetManager->canAffordBudget($pId, 'fleet', $cost);
            if (!$canAffordBudget && isset($this->ctx->budgetManager)) {
                $canAffordBudget = $this->ctx->budgetManager->tryInternalBucketSwap($pId, 'fleet', $cost);
            }

            if ($canAffordBudget) {
                $ok = $this->gateway->constructUnits($pId, 208, 1, 'Reclutamiento autónomo de Colonizador para Expansión Imperial', 'fleet');
                if ($ok) {
                    $recruited++;
                    if (isset($this->ctx->decisionLog)) {
                        $this->ctx->decisionLog->record(
                            'colonizer_recruit',
                            "Queued 1 Colony Ship (#208) on planet #{$pId} (Open slots: {$openSlots}, In-Flight: {$inFlightColonyShips})",
                            array('planet_id' => $pId, 'open_slots' => $openSlots, 'needed' => $needed),
                            "recruit_colonizer_{$pId}",
                            10000.0
                        );
                    }
                }
            }
        }

        return $recruited;
    }

    /**
     * Responsabilidad B: Selección Óptima de Coordenadas y Despacho de Misión 7 con Kit de Fundación Colonial
     *
     * @param BotWorldModel $world
     * @return int Number of colonization fleets dispatched
     */
    public function dispatchColonizationMissions(BotWorldModel $world)
    {
        global $resource;

        $maxPlanets = (int)PlayerUtil::maxPlanetCount($this->ctx->user);
        $planets = $world->empire->getPlanets();
        $currentPlanets = count($planets);

        $db = Database::get();
        $inFlightRow = $db->selectSingle(
            "SELECT COUNT(*) as cnt FROM %%FLEETS%% WHERE fleet_owner = :bid AND fleet_mission = 7 AND fleet_mess = 0;",
            array(':bid' => $this->ctx->botId)
        );
        $inFlightColonyShips = (int)($inFlightRow['cnt'] ?? 0);

        $maxConcurrentColo = isset($this->ctx->difficulty) ? $this->ctx->difficulty->getMaxConcurrentColonizations() : 1;

        if (($currentPlanets + $inFlightColonyShips) >= $maxPlanets || $inFlightColonyShips >= $maxConcurrentColo) {
            return 0;
        }

        $dispatched = 0;
        $universe = Universe::current();

        foreach ($planets as $pId => $pEntry) {
            if (($currentPlanets + $inFlightColonyShips) >= $maxPlanets || $inFlightColonyShips >= $maxConcurrentColo) {
                break;
            }

            $availMilSlots = BotActionGateway::getAvailableMilitarySlots($this->ctx->botId, $this->ctx->user);
            if ($availMilSlots <= 0) {
                break;
            }

            $pData = $pEntry['data'];
            $stationedColonizers = (int)($pData[$resource[208]] ?? 0);
            if ($stationedColonizers <= 0) {
                continue;
            }

            // Find optimal coordinates [G:S:P]
            $originG = (int)$pData['galaxy'];
            $originS = (int)$pData['system'];

            $targetCoord = $this->findOptimalColonizationTarget($universe, $originG, $originS);
            if ($targetCoord === null) {
                continue;
            }

            list($tG, $tS, $tP) = $targetCoord;

            // Calculate flight distance and consumption for #208
            $startCoords = array($originG, $originS, (int)$pData['planet']);
            $targetCoords = array($tG, $tS, $tP);
            $distance = FleetFunctions::GetTargetDistance($startCoords, $targetCoords);
            $fleetSpeed = FleetFunctions::GetFleetMaxSpeed(array(208 => 1), $this->ctx->user);
            $speedFactor = 10;
            $gameSpeed = FleetFunctions::GetGameSpeedFactor();
            $flightDuration = FleetFunctions::GetMissionDuration($speedFactor, $fleetSpeed, $distance, $gameSpeed, $this->ctx->user);
            $consumption = FleetFunctions::GetFleetConsumption(array(208 => 1), $flightDuration, $distance, $this->ctx->user, $gameSpeed);

            if ((float)$pData['deuterium'] < $consumption) {
                continue;
            }

            // Calculate net cargo room of #208: GetFleetRoom(array(208 => 1), $this->ctx->user) - $consumption
            $fleetRoom = FleetFunctions::GetFleetRoom(array(208 => 1), $this->ctx->user);
            $netCargoRoom = max(0.0, (float)$fleetRoom - (float)$consumption);

            $seedRatio = isset($this->ctx->difficulty) ? $this->ctx->difficulty->getColonizationSeedCargoRatio() : 0.50;
            $targetSeedCapacity = $netCargoRoom * $seedRatio;

            // Kit de Fundación Colonial (50% M / 35% C / 15% D) tomado exclusivamente de getDonorSurplusByCategory($pId, 'mines', $pData)
            $cargoResources = array();
            $seedCargoDebit = array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);

            if ($targetSeedCapacity > 0.0 && isset($this->ctx->budgetManager)) {
                $surplus = $this->ctx->budgetManager->getDonorSurplusByCategory($pId, 'mines', $pData);
                $desiredM = $targetSeedCapacity * 0.50;
                $desiredC = $targetSeedCapacity * 0.35;
                $desiredD = $targetSeedCapacity * 0.15;

                $availSurplusM = max(0.0, (float)($surplus['metal'] ?? 0.0));
                $availSurplusC = max(0.0, (float)($surplus['crystal'] ?? 0.0));
                $availSurplusD = max(0.0, (float)($surplus['deuterium'] ?? 0.0));

                $physM = max(0.0, (float)($pData['metal'] ?? 0.0));
                $physC = max(0.0, (float)($pData['crystal'] ?? 0.0));
                $physD = max(0.0, (float)($pData['deuterium'] ?? 0.0) - (float)$consumption);

                $actualM = min($desiredM, $availSurplusM, $physM);
                $actualC = min($desiredC, $availSurplusC, $physC);
                $actualD = min($desiredD, $availSurplusD, $physD);

                $sumCargo = $actualM + $actualC + $actualD;
                if ($sumCargo > $targetSeedCapacity && $sumCargo > 0) {
                    $scale = $targetSeedCapacity / $sumCargo;
                    $actualM *= $scale;
                    $actualC *= $scale;
                    $actualD *= $scale;
                }

                if ($actualM > 0 || $actualC > 0 || $actualD > 0) {
                    $cargoResources = array(
                        901 => floor($actualM),
                        902 => floor($actualC),
                        903 => floor($actualD),
                    );
                    $seedCargoDebit = array(
                        'metal'     => $cargoResources[901],
                        'crystal'   => $cargoResources[902],
                        'deuterium' => $cargoResources[903],
                    );
                }
            }

            $outStartTime = null;
            $fleetId = $this->gateway->dispatchColonization($pId, $tG, $tS, $tP, $cargoResources, $speedFactor, $outStartTime);
            if ($fleetId) {
                // Debit Seed Cargo from donor 'mines' category
                if (isset($this->ctx->budgetManager) && ($seedCargoDebit['metal'] > 0 || $seedCargoDebit['crystal'] > 0 || $seedCargoDebit['deuterium'] > 0)) {
                    $this->ctx->budgetManager->debitDonorCategory($pId, 'mines', $seedCargoDebit);
                }

                $dispatched++;
                $inFlightColonyShips++;
                $pData[$resource[208]] = max(0, $stationedColonizers - 1);
                $pEntry['data'][$resource[208]] = $pData[$resource[208]];

                if (isset($this->ctx->decisionLog)) {
                    $this->ctx->decisionLog->record(
                        'colonization_dispatch',
                        "Dispatched Colony Ship (#208) from Planet #{$pId} to optimal coordinates [{$tG}:{$tS}:{$tP}] (Mission 7, Seed Cargo: M:{$seedCargoDebit['metal']} C:{$seedCargoDebit['crystal']} D:{$seedCargoDebit['deuterium']})",
                        array('origin_planet' => $pId, 'target_coords' => array('galaxy' => $tG, 'system' => $tS, 'planet' => $tP), 'fleet_id' => $fleetId, 'seed_cargo' => $seedCargoDebit),
                        "colonize_{$tG}_{$tS}_{$tP}",
                        20000.0
                    );
                }

                if (isset($this->ctx->telemetry) && method_exists($this->ctx->telemetry, 'logFleetMission')) {
                    $this->ctx->telemetry->logFleetMission(
                        $pData['name'] ?? "Planeta #{$pId}",
                        "[{$originG}:{$originS}:{$pData['planet']}]",
                        "Sector Libre",
                        "[{$tG}:{$tS}:{$tP}]",
                        7,
                        array(208 => 1),
                        $cargoResources,
                        0,
                        "Despacho de Misión 7 para fundación de nueva colonia imperial con carga semilla"
                    );
                }
            }
        }

        return $dispatched;
    }

    /**
     * Búsqueda algorítmica de coordenadas óptimas libres [G:S:P]
     * Modulada por getAllowedColonizationPositions() y getColonizationSpreadPolicy()
     *
     * @param int $universe
     * @param int $originG
     * @param int $originS
     * @return array|null [galaxy, system, planet]
     */
    public function findOptimalColonizationTarget($universe, $originG, $originS)
    {
        $config = Config::get();
        $maxGalaxy = (int)($config->max_galaxy ?? 1);
        $maxSystem = (int)($config->max_system ?? 400);
        $db = Database::get();

        // 1. Get spread policy and allowed positions
        $policy = isset($this->ctx->difficulty) ? $this->ctx->difficulty->getColonizationSpreadPolicy() : array(
            'max_per_cluster' => 2, 'cluster_radius' => 2, 'node_min_dist' => 20, 'node_max_dist' => 45
        );
        $allowedPositions = isset($this->ctx->difficulty) ? $this->ctx->difficulty->getAllowedColonizationPositions() : array(8, 7, 9, 6, 10);
        $fallbackPositions = array(4, 12, 3, 13, 2, 14, 1, 15);

        // 2. Count bot colonies in local cluster
        $botPlanets = $db->select("SELECT galaxy, system, planet FROM %%PLANETS%% WHERE id_owner = :bid AND planet_type = 1;", array(':bid' => $this->ctx->botId));
        $clusterCount = 0;
        if (is_array($botPlanets)) {
            foreach ($botPlanets as $bp) {
                if ((int)$bp['galaxy'] === (int)$originG && abs((int)$bp['system'] - (int)$originS) <= (int)$policy['cluster_radius']) {
                    $clusterCount++;
                }
            }
        }

        // 3. Determine system offsets based on cluster capacity
        $systemOffsets = array();
        $clusterFull = ($clusterCount >= (int)$policy['max_per_cluster']);

        if (!$clusterFull) {
            // Local exploration first: within cluster radius
            for ($r = 1; $r <= (int)$policy['cluster_radius']; $r++) {
                $systemOffsets[] = $r;
                $systemOffsets[] = -$r;
            }
        }

        // Add distant node offsets
        $minDist = (int)$policy['node_min_dist'];
        $maxDist = (int)$policy['node_max_dist'];
        for ($d = $minDist; $d <= $maxDist; $d += 5) {
            $systemOffsets[] = $d;
            $systemOffsets[] = -$d;
        }
        for ($d = $minDist + 1; $d <= $maxDist; $d += 5) {
            $systemOffsets[] = $d;
            $systemOffsets[] = -$d;
        }

        // 4. Try allowed positions first, fallback positions second
        $positionPasses = array($allowedPositions, $fallbackPositions);

        foreach ($positionPasses as $positionsToTry) {
            foreach ($systemOffsets as $sOff) {
                $candidateS = $originS + $sOff;
                if ($candidateS < 1 || $candidateS > $maxSystem) {
                    continue;
                }

                foreach ($positionsToTry as $candidateP) {
                    // 1. Position format valid
                    if (!PlayerUtil::checkPosition($universe, $originG, $candidateS, $candidateP)) {
                        continue;
                    }

                    // 2. Position is free of any existing planet
                    if (!PlayerUtil::isPositionFree($universe, $originG, $candidateS, $candidateP)) {
                        continue;
                    }

                    // 3. User tech allows this position (Astro limits position 1-3 and 13-15)
                    if (!PlayerUtil::allowPlanetPosition($candidateP, $this->ctx->user)) {
                        continue;
                    }

                    // 4. Anti-Collision Lock: check no fleet with mission 7 is already inbound to this coordinate
                    $inboundColo = $db->selectSingle(
                        "SELECT COUNT(*) as cnt FROM %%FLEETS%% 
                         WHERE fleet_mission = 7 
                         AND fleet_end_galaxy = :g 
                         AND fleet_end_system = :s 
                         AND fleet_end_planet = :p 
                         AND fleet_mess = 0;",
                        array(':g' => $originG, ':s' => $candidateS, ':p' => $candidateP)
                    );

                    if ((int)($inboundColo['cnt'] ?? 0) > 0) {
                        continue;
                    }

                    return array($originG, $candidateS, $candidateP);
                }
            }
        }

        return null;
    }

    /**
     * Responsabilidad C: Doctrina de Moonshots y Creación Orgánica de Lunas (20% Chance)
     * Vuelo real en %%FLEETS%% con 715 Cazas Ligeros (#204), resolución en BotKernel y Zero-Cheats.
     *
     * @param BotWorldModel $world
     * @return array ['attempts' => int, 'moons_created' => int]
     */
    public function executeMoonshots(BotWorldModel $world)
    {
        global $resource;

        $planets = $world->empire->getPlanets();
        $minPlanets = isset($this->ctx->difficulty) ? $this->ctx->difficulty->getMinPlanetsForMoonshots() : 5;
        if (count($planets) < $minPlanets) {
            return array('attempts' => 0, 'moons_created' => 0);
        }

        // Guardia de Madurez Económica para Moonshots:
        // Un ensayo consume 715 Cazas Ligeros (2.145M Metal + 715k Cristal = 2.86M M+C).
        // El bot NO debe quemar recursos en moonshots hasta tener >= minPlanets y que 2.86M
        // represente <= 2.5 horas de producción imperial (producción M+C >= 1.144M/h).
        $hourlyMC = 0.0;
        foreach ($planets as $pEntry) {
            $hourlyMC += (float)($pEntry['data']['metal_perhour'] ?? 0.0) + (float)($pEntry['data']['crystal_perhour'] ?? 0.0);
        }
        if ($hourlyMC <= 0.0 || (2860000.0 / $hourlyMC) > 2.5) {
            return array('attempts' => 0, 'moons_created' => 0);
        }

        // Proteger fondos de colonización si el imperio aún tiene cupos coloniales abiertos
        $maxPlanets = class_exists('PlayerUtil') ? (int)PlayerUtil::maxPlanetCount($this->ctx->user) : 9;
        if (count($planets) < min(9, $maxPlanets) && $hourlyMC < 1800000.0) {
            return array('attempts' => 0, 'moons_created' => 0);
        }

        // Identificar planetas sin luna
        $moonlessPlanets = array();
        foreach ($planets as $pId => $pEntry) {
            $pData = $pEntry['data'];
            if ((int)($pData['id_luna'] ?? 0) === 0) {
                $moonlessPlanets[$pId] = $pData;
            }
        }

        if (empty($moonlessPlanets)) {
            return array('attempts' => 0, 'moons_created' => 0);
        }

        // Cobertura objetivo de lunas
        $targetRatio = isset($this->ctx->difficulty) ? $this->ctx->difficulty->getTargetMoonCoverageRatio() : 0.50;
        $targetMoonCount = (int)ceil(count($planets) * $targetRatio);
        $currentMoons = count($world->empire->getMoons());
        if ($currentMoons >= $targetMoonCount || $targetMoonCount <= 0) {
            return array('attempts' => 0, 'moons_created' => 0);
        }

        // Prioridad de Moonshots:
        // 1. Luna FOB
        // 2. Planeta Principal
        // 3. Colonias con mayor producción física
        $fobId = (int)($this->ctx->botRow['fob_planet_id'] ?? 0);
        $homeId = (int)($this->ctx->botRow['home_planet_id'] ?? 0);

        uasort($moonlessPlanets, function($a, $b) use ($fobId, $homeId) {
            $aId = (int)$a['id'];
            $bId = (int)$b['id'];
            if ($aId === $fobId) return -1;
            if ($bId === $fobId) return 1;
            if ($aId === $homeId) return -1;
            if ($bId === $homeId) return 1;
            $aProd = (float)($a['metal_perhour'] ?? 0) + (float)($a['crystal_perhour'] ?? 0);
            $bProd = (float)($b['metal_perhour'] ?? 0) + (float)($b['crystal_perhour'] ?? 0);
            return $bProd <=> $aProd;
        });

        $db = Database::get();
        $attempts = 0;
        $cooldown = isset($this->ctx->difficulty) ? $this->ctx->difficulty->getMoonshotCooldownSeconds() : 1800;
        $isSimulation = (defined('SIMULATION_MODE') && SIMULATION_MODE);

        foreach ($moonlessPlanets as $targetPlanetId => $targetPlanet) {
            if ($attempts >= 1) {
                break;
            }

            // Verificar si ya existe una tarea moonshot_orbital_resolution pendiente para este planeta
            $pendingMoonshots = $db->select(
                "SELECT task_id, payload FROM " . DB_PREFIX . "bot_tasks 
                 WHERE bot_id = :bid AND task_type = 'moonshot_orbital_resolution' AND status = 'pending';",
                array(':bid' => $this->ctx->botId)
            );
            if (!empty($pendingMoonshots)) {
                $alreadyPendingForTarget = false;
                foreach ($pendingMoonshots as $pt) {
                    $pPayload = json_decode($pt['payload'], true);
                    if (!empty($pPayload['target_planet_id']) && (int)$pPayload['target_planet_id'] === (int)$targetPlanetId) {
                        $alreadyPendingForTarget = true;
                        break;
                    }
                }
                if ($alreadyPendingForTarget) {
                    continue;
                }
            }

            // Cooldown entre ensayos orbitales (excepto en simulación)
            if (!$isSimulation) {
                $lastAttempt = $db->selectSingle(
                    "SELECT id FROM %%BOT_DECISIONS%% 
                     WHERE bot_id = :bid AND (chosen_action = :act OR chosen_action = :act2) 
                     AND created_at >= DATE_SUB(NOW(), INTERVAL {$cooldown} SECOND) 
                     LIMIT 1;",
                    array(':bid' => $this->ctx->botId, ':act' => "moonshot_dispatch_{$targetPlanetId}", ':act2' => "moon_created_{$targetPlanetId}")
                );
                if (!empty($lastAttempt)) {
                    continue;
                }
            }

            // Respetar candados de ahorro en el planeta objetivo
            if (isset($this->ctx->budgetManager) && method_exists($this->ctx->budgetManager, 'isPlanetLocked')) {
                if ($this->ctx->budgetManager->isPlanetLocked($targetPlanetId)) {
                    continue;
                }
            }

            // Anti-Collision: Verify no inbound attack is currently targeting this planet
            $incoming = $db->selectSingle(
                "SELECT COUNT(*) as cnt FROM %%FLEETS%% WHERE fleet_end_id = :pid AND fleet_mission = 1 AND fleet_mess = 0;",
                array(':pid' => $targetPlanetId)
            );
            if ((int)($incoming['cnt'] ?? 0) > 0) {
                continue;
            }

            // Buscar colonia donante ($candId !== $targetPlanetId) con >= 715 Cazas Ligeros (#204)
            $donorColony = null;
            $bestRecruitColony = null;
            $maxShipyardLvl = -1;

            foreach ($planets as $candId => $candEntry) {
                if ((int)$candId === (int)$targetPlanetId) {
                    continue;
                }
                $cData = $candEntry['data'];
                $cLF = (int)($cData[$resource[204]] ?? 0);
                if ($cLF >= 715 && $donorColony === null) {
                    $donorColony = $cData;
                }

                $syLvl = (int)($cData[$resource[21]] ?? 0);
                if ($syLvl > $maxShipyardLvl) {
                    $maxShipyardLvl = $syLvl;
                    $bestRecruitColony = $cData;
                }
            }

            // Si ninguna colonia tiene >= 715 #204 estacionados:
            if ($donorColony === null) {
                if ($this->ctx->difficulty->canRecruitMoonshotSquadrons() && $bestRecruitColony !== null) {
                    $bId = (int)$bestRecruitColony['id'];
                    $curLF = (int)($bestRecruitColony[$resource[204]] ?? 0);

                    // Contar LF en cola de hangar
                    $queuedLF = 0;
                    $hQueue = !empty($planets[$bId]['hangar_queue']) ? $planets[$bId]['hangar_queue'] : array();
                    foreach ($hQueue as $hq) {
                        if (isset($hq[0]) && (int)$hq[0] === 204) {
                            $queuedLF += (int)$hq[1];
                        }
                    }

                    $neededLF = 715 - ($curLF + $queuedLF);
                    if ($neededLF > 0) {
                        $batchLF = min(250, $neededLF);
                        $cost = BuildFunctions::getElementPrice($this->ctx->user, $bestRecruitColony, 204, false, $batchLF);
                        $canAfford = (!isset($this->ctx->budgetManager)) || $this->ctx->budgetManager->canAffordBudget($bId, 'fleet', $cost);
                        if (!$canAfford && isset($this->ctx->budgetManager)) {
                            $canAfford = $this->ctx->budgetManager->tryInternalBucketSwap($bId, 'fleet', $cost);
                        }

                        if ($canAfford) {
                            $ok = $this->gateway->constructUnits($bId, 204, $batchLF, 'Reclutamiento de escuadrón para Moonshot orbital (715x #204)', 'fleet');
                            if ($ok && isset($this->ctx->decisionLog)) {
                                $this->ctx->decisionLog->record(
                                    'moonshot_squadron_recruit',
                                    "Enqueued batch of {$batchLF}x Light Fighters (#204) on Colony #{$bId} for Moonshot campaign (Current: {$curLF}, Queued: {$queuedLF}, Target: 715)",
                                    array('planet_id' => $bId, 'batch' => $batchLF, 'needed' => $neededLF),
                                    "recruit_moonshot_{$bId}",
                                    15000.0
                                );
                            }
                        }
                    }
                }
                continue;
            }

            // La colonia donante TIENE >= 715 #204. Despachar vuelo real en %%FLEETS%% (Misión 4)
            $donorPlanetId = (int)$donorColony['id'];
            $startTime = null;
            $fleetId = $this->gateway->launchFleet(
                array(204 => 715),
                4, // Mission 4: Deploy / Stay
                $donorPlanetId,
                $targetPlanetId,
                $this->ctx->botId,
                (int)$targetPlanet['galaxy'],
                (int)$targetPlanet['system'],
                (int)$targetPlanet['planet'],
                1, // Planet
                array(),
                10, // Speed 100%
                $startTime
            );

            if ($fleetId) {
                $attempts++;

                // Programar moonshot_orbital_resolution en BotScheduler::scheduleTask para $startTime
                $payload = array(
                    'fleet_id'         => (int)$fleetId,
                    'donor_planet_id'  => (int)$donorPlanetId,
                    'target_planet_id' => (int)$targetPlanetId,
                    'galaxy'           => (int)$targetPlanet['galaxy'],
                    'system'           => (int)$targetPlanet['system'],
                    'planet'           => (int)$targetPlanet['planet'],
                    'ships'            => 715,
                );

                BotScheduler::scheduleTask($this->ctx->botId, 'moonshot_orbital_resolution', $payload, $startTime);

                if (isset($this->ctx->decisionLog)) {
                    $this->ctx->decisionLog->record(
                        'moonshot_dispatch',
                        "Launched 715 Light Fighters (#204) from Colony #{$donorPlanetId} to Planet #{$targetPlanetId} [{$targetPlanet['galaxy']}:{$targetPlanet['system']}:{$targetPlanet['planet']}] for 20% Moonshot orbital exercise (Fleet #{$fleetId}, ETA: " . date('Y-m-d H:i:s', $startTime) . ")",
                        array('donor_planet' => $donorPlanetId, 'target_planet' => $targetPlanetId, 'fleet_id' => $fleetId, 'eta' => $startTime),
                        "moonshot_dispatch_{$targetPlanetId}",
                        30000.0
                    );
                }

                if (isset($this->ctx->telemetry) && method_exists($this->ctx->telemetry, 'logFleetMission')) {
                    $this->ctx->telemetry->logFleetMission(
                        $donorColony['name'] ?? "Colonia #{$donorPlanetId}",
                        "[{$donorColony['galaxy']}:{$donorColony['system']}:{$donorColony['planet']}]",
                        $targetPlanet['name'] ?? "Planeta #{$targetPlanetId}",
                        "[{$targetPlanet['galaxy']}:{$targetPlanet['system']}:{$targetPlanet['planet']}]",
                        4,
                        array(204 => 715),
                        array(901 => 0, 902 => 0, 903 => 0),
                        0,
                        "Despacho de escuadrón de 715 Cazas Ligeros para ejercicio orbital Moonshot (20%)"
                    );
                }
            }
        }

        return array('attempts' => $attempts, 'moons_created' => 0);
    }
}

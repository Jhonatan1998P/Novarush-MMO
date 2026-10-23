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
 * B: Optimal coordinate selection & dispatch of Mission 7 (Colonisation).
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

        $toRecruit = min(2, $needed);
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

            $canAfford = ($availM >= $costM && $availC >= $costC && $availD >= $costD);
            if (!$canAfford && isset($this->ctx->budgetManager)) {
                $canAfford = $this->ctx->budgetManager->tryInternalBucketSwap($pId, 'fleet', $cost);
            }

            if ($canAfford) {
                $ok = $this->gateway->constructUnits($pId, 208, 1, 'Reclutamiento autónomo de Colonizador para Expansión Imperial');
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
     * Responsabilidad B: Selección Óptima de Coordenadas y Despacho de Misión 7
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

        if (($currentPlanets + $inFlightColonyShips) >= $maxPlanets) {
            return 0;
        }

        $dispatched = 0;
        $universe = Universe::current();

        foreach ($planets as $pId => $pEntry) {
            if (($currentPlanets + $inFlightColonyShips) >= $maxPlanets) {
                break;
            }

            $maxSlots = FleetFunctions::GetMaxFleetSlots($this->ctx->user);
            $actualFleets = FleetFunctions::GetCurrentFleets($this->ctx->botId);
            if ($actualFleets >= $maxSlots) {
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

            $fleetId = $this->gateway->dispatchColonization($pId, $tG, $tS, $tP);
            if ($fleetId) {
                $dispatched++;
                $inFlightColonyShips++;
                $pData[$resource[208]] = max(0, $stationedColonizers - 1);
                $pEntry['data'][$resource[208]] = $pData[$resource[208]];

                if (isset($this->ctx->decisionLog)) {
                    $this->ctx->decisionLog->record(
                        'colonization_dispatch',
                        "Dispatched Colony Ship (#208) from Planet #{$pId} to optimal coordinates [{$tG}:{$tS}:{$tP}] (Mission 7)",
                        array('origin_planet' => $pId, 'target_coords' => array('galaxy' => $tG, 'system' => $tS, 'planet' => $tP), 'fleet_id' => $fleetId),
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
                        array(901 => 0, 902 => 0, 903 => 0),
                        0,
                        "Despacho de Misión 7 para fundación de nueva colonia imperial"
                    );
                }
            }
        }

        return $dispatched;
    }

    /**
     * Búsqueda algorítmica de coordenadas óptimas libres [G:S:P]
     * Alterna expansión local (+-1 a +-12) y nodos estratégicos a media distancia (+-15 a +-45)
     * Prioriza posiciones templadas de gran tamaño: [8, 7, 9, 6, 10, 5, 11, 4, 12] luego [3, 13, 2, 14, 1, 15]
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

        // Candidate system offsets: local and medium nodes
        $systemOffsets = array(
            1, -1, 2, -2, 3, -3, 4, -4, 5, -5, 6, -6, 7, -7, 8, -8,
            15, -15, 20, -20, 25, -25, 30, -30, 35, -35, 40, -40, 45, -45,
            9, -9, 10, -10, 11, -11, 12, -12
        );

        // Position hierarchy: core temperate large fields first, then peripheral
        $positionPriority = array(8, 7, 9, 6, 10, 5, 11, 4, 12, 3, 13, 2, 14, 1, 15);

        $db = Database::get();

        foreach ($systemOffsets as $sOff) {
            $candidateS = $originS + $sOff;
            if ($candidateS < 1 || $candidateS > $maxSystem) {
                continue;
            }

            foreach ($positionPriority as $candidateP) {
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

        return null;
    }

    /**
     * Responsabilidad C: Doctrina de Moonshots y Creación Orgánica de Lunas (20% Chance)
     * Lote de 715 Cazas Ligeros (204) = 2,145,000 M + 715,000 C -> 70% CDR = 2,002,000 Escombros -> 20% Moon Chance.
     *
     * @param BotWorldModel $world
     * @return array ['attempts' => int, 'moons_created' => int]
     */
    public function executeMoonshots(BotWorldModel $world)
    {
        global $resource;

        $planets = $world->empire->getPlanets();
        if (count($planets) < 5) {
            return array('attempts' => 0, 'moons_created' => 0);
        }

        // Guardia de Madurez Económica para Moonshots:
        // Un ensayo consume 715 Cazas Ligeros (2.145M Metal + 715k Cristal = 2.86M M+C).
        // El bot NO debe quemar recursos en moonshots hasta tener >= 5 planetas y que 2.86M
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

        // Identify planets without a moon
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

        // Moonshot Priority:
        // 1. FOB Planet
        // 2. Home Planet
        // 3. Colonies with highest physical production / defenses
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
        $moonsCreated = 0;
        $now = class_exists('BotClock') ? BotClock::get() : TIMESTAMP;

        // Cover at least up to 50% of planets with moons
        $targetMoonCount = (int)ceil(count($planets) * 0.50);
        $currentMoons = count($world->empire->getMoons());
        if ($currentMoons >= $targetMoonCount) {
            return array('attempts' => 0, 'moons_created' => 0);
        }

        $neededMoons = $targetMoonCount - $currentMoons;

        foreach ($moonlessPlanets as $targetPlanetId => $targetPlanet) {
            if ($moonsCreated >= $neededMoons || $attempts >= 2) {
                break;
            }

            // Cooldown de ensayo orbital (1800s entre ensayos por planeta, excepto en simulación/bootstrap)
            $isSimulation = (defined('SIMULATION_MODE') && SIMULATION_MODE);
            if (!$isSimulation) {
                $lastAttempt = $db->selectSingle(
                    "SELECT id FROM %%BOT_DECISIONS%% 
                     WHERE bot_id = :bid AND (chosen_action = :act OR chosen_action = :act2) 
                     AND created_at >= DATE_SUB(NOW(), INTERVAL 1800 SECOND) 
                     LIMIT 1;",
                    array(':bid' => $this->ctx->botId, ':act' => "moonshot_attempt_{$targetPlanetId}", ':act2' => "moon_created_{$targetPlanetId}")
                );
                if (!empty($lastAttempt)) {
                    continue; // Cooldown activo para este planeta
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

            // Check moonshot material availability:
            // 715 Light Fighters (204) OR Physical Resources: 2,145,000 Metal + 715,000 Crystal
            $availLF = (int)($targetPlanet[$resource[204]] ?? 0);
            $availM  = (float)($targetPlanet['metal'] ?? 0.0);
            $availC  = (float)($targetPlanet['crystal'] ?? 0.0);

            $hasShips = ($availLF >= 715);
            $hasSurplus = ($availM >= 2500000.0 && $availC >= 1000000.0);

            if (!$hasShips && !$hasSurplus) {
                // Check if any other colony of this bot has >= 715 LF to spare
                $donorColony = null;
                foreach ($planets as $candId => $candEntry) {
                    if ($candId === $targetPlanetId) continue;
                    if ((int)($candEntry['data'][$resource[204]] ?? 0) >= 715) {
                        $donorColony = $candEntry['data'];
                        break;
                    }
                }
                if ($donorColony !== null) {
                    $hasShips = true;
                    $donorPlanetId = $donorColony['id'];
                } else {
                    continue; // Cannot fund moonshot on this colony right now
                }
            } else {
                $donorPlanetId = $targetPlanetId;
            }

            // Deduct atomic cost for 715 Light Fighters
            if ($hasShips) {
                $db->update(
                    "UPDATE %%PLANETS%% SET light_hunter = CASE WHEN light_hunter >= 715 THEN light_hunter - 715 ELSE 0 END WHERE id = :pid;",
                    array(':pid' => $donorPlanetId)
                );
            } else {
                $db->update(
                    "UPDATE %%PLANETS%% SET metal = GREATEST(0, metal - 2145000), crystal = GREATEST(0, crystal - 715000) WHERE id = :pid;",
                    array(':pid' => $targetPlanetId)
                );
            }

            // Credit 70% debris cloud (1,501,500 Metal + 500,500 Crystal = 2,002,000 Debris)
            $db->update(
                "UPDATE %%PLANETS%% SET 
                 der_metal = der_metal + 1501500, 
                 der_crystal = der_crystal + 500500 
                 WHERE id = :pid;",
                array(':pid' => $targetPlanetId)
            );

            $attempts++;

            // Calculate moon creation probability: 20% legal max
            $debrisTotal = 2002000;
            $config = Config::get();
            $moonFactor = isset($config->moon_factor) ? (float)$config->moon_factor : 1.0;
            $moonChanceCap = isset($config->moon_chance) ? (int)$config->moon_chance : 20;
            $chanceCreateMoon = min((int)round($debrisTotal / 100000 * $moonFactor), $moonChanceCap); // 20%
            $randChance = mt_rand(1, 100);

            if ($randChance <= $chanceCreateMoon) {
                $universe = Universe::current();
                $g = (int)$targetPlanet['galaxy'];
                $s = (int)$targetPlanet['system'];
                $p = (int)$targetPlanet['planet'];
                $moonName = BotNameHelper::getRandomMoonName();

                $newMoonId = PlayerUtil::createMoon($universe, $g, $s, $p, $this->ctx->botId, $chanceCreateMoon, null, $moonName);

                if ($newMoonId) {
                    $moonsCreated++;


                    if (isset($this->ctx->decisionLog)) {
                        $this->ctx->decisionLog->record(
                            'moonshot_success',
                            "Moonshot Successful on Planet #{$targetPlanetId} [{$g}:{$s}:{$p}]: Moon '{$moonName}' (#{$newMoonId}) created with {$chanceCreateMoon}% chance (Roll: {$randChance}/100, 2.002M debris generated)",
                            array('planet_id' => $targetPlanetId, 'coords' => array($g, $s, $p), 'moon_id' => $newMoonId, 'moon_name' => $moonName, 'chance' => $chanceCreateMoon, 'roll' => $randChance),
                            "moon_created_{$targetPlanetId}",
                            50000.0
                        );
                    }

                    if (isset($this->ctx->telemetry) && method_exists($this->ctx->telemetry, 'logFleetMission')) {
                        $this->ctx->telemetry->logFleetMission(
                            $targetPlanet['name'] ?? "Planeta #{$targetPlanetId}",
                            "[{$g}:{$s}:{$p}]",
                            "Órbita Lunar",
                            "[{$g}:{$s}:{$p}]",
                            1,
                            array(204 => 715),
                            array(901 => 0, 902 => 0, 903 => 0),
                            0,
                            "Moonshot orbital exitoso: Luna '{$moonName}' (#{$newMoonId}) nacida al 20%"
                        );
                    }
                }
            } else {
                if (isset($this->ctx->decisionLog)) {
                    $this->ctx->decisionLog->record(
                        'moonshot_attempt_debris_created',
                        "Moonshot attempt on Planet #{$targetPlanetId}: 2.002M debris generated in orbit (Roll: {$randChance}/100 > {$chanceCreateMoon}% chance, ready for recycling)",
                        array('planet_id' => $targetPlanetId, 'chance' => $chanceCreateMoon, 'roll' => $randChance),
                        "moonshot_attempt_{$targetPlanetId}",
                        2000.0
                    );
                }
            }
        }

        return array('attempts' => $attempts, 'moons_created' => $moonsCreated);
    }
}

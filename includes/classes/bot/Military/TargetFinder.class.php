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
    public static $phalanxScansCount = 0;
    private static $scannedFleetCache = array();

    // Global queries cache across bots within the turn/tick
    private static $cachedBotIds = null;
    private static $cachedBotIdsTime = 0;
    private static $cachedSieges = null;
    private static $cachedSiegesTime = 0;
    private static $cachedInFlight = null;
    private static $cachedInFlightTime = 0;

    private $ctx;

    public function __construct(BotContext $ctx)
    {
        $this->ctx = $ctx;
    }

    public static function getAllBotIdsMap(): array
    {
        $now = class_exists('BotClock') ? BotClock::now() : time();
        if (self::$cachedBotIds === null || ($now - self::$cachedBotIdsTime) > 30) {
            $db = Database::get();
            $botRows = $db->select("SELECT bot_id FROM " . DB_PREFIX . "bots;");
            $map = array();
            if (is_array($botRows)) {
                foreach ($botRows as $bRow) {
                    $map[(int)$bRow['bot_id']] = true;
                }
            }
            self::$cachedBotIds = $map;
            self::$cachedBotIdsTime = $now;
        }
        return self::$cachedBotIds;
    }

    public static function getActiveSiegesList($now): array
    {
        if (self::$cachedSieges === null || ($now - self::$cachedSiegesTime) > 15) {
            $db = Database::get();
            $lockExpire = $now - 21600;
            $rows = $db->select("SELECT bot_id, target_galaxy, target_system, target_planet, target_type, target_lock_time 
                FROM " . DB_PREFIX . "bots 
                WHERE is_active = 1 
                  AND target_type = 'siege' 
                  AND target_lock_time > :lockExpire;", array(
                ':lockExpire' => $lockExpire
            ));
            self::$cachedSieges = is_array($rows) ? $rows : array();
            self::$cachedSiegesTime = $now;
        }
        return self::$cachedSieges;
    }

    public static function getInFlightAttacksMap($now): array
    {
        if (self::$cachedInFlight === null || ($now - self::$cachedInFlightTime) > 15) {
            $db = Database::get();
            $rows = $db->select("SELECT fleet_end_galaxy, fleet_end_system, fleet_end_planet, fleet_end_type, COUNT(*) as cnt 
                FROM %%FLEETS%% 
                WHERE fleet_mission = 1 AND fleet_mess = 0 
                GROUP BY fleet_end_galaxy, fleet_end_system, fleet_end_planet, fleet_end_type;");
            $map = array();
            if (is_array($rows)) {
                foreach ($rows as $ifr) {
                    $key = (int)$ifr['fleet_end_galaxy'] . ':' . (int)$ifr['fleet_end_system'] . ':' . (int)$ifr['fleet_end_planet'] . ':' . (int)$ifr['fleet_end_type'];
                    $map[$key] = (int)$ifr['cnt'];
                }
            }
            self::$cachedInFlight = $map;
            self::$cachedInFlightTime = $now;
        }
        return self::$cachedInFlight;
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
        $db = Database::get();
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

        $now = class_exists('BotClock') ? BotClock::now() : TIMESTAMP;
        $allBotIds = self::getAllBotIdsMap();

        // Deconflicción: Excluye objetivos con target_type = 'siege' bloqueados por otro bot activo en las últimas 6 horas
        $allActiveSieges = self::getActiveSiegesList($now);
        $siegeLockedMap = array();
        foreach ($allActiveSieges as $obs) {
            if ((int)$obs['bot_id'] !== (int)$this->ctx->botId) {
                $siegeLockedMap[(int)$obs['target_galaxy'] . ':' . (int)$obs['target_system'] . ':' . (int)$obs['target_planet']] = true;
            }
        }

        // Monitoreo de flotas atacantes en vuelo (para descuento de botín pow(0.5, inFlightAttacks))
        $inFlightMap = self::getInFlightAttacksMap($now);

        // Uso Ofensivo de Sensor Phalanx (ID 42)
        $phalanxPriorityTargets = array();
        if (method_exists($this->ctx->difficulty, 'canUseOffensivePhalanx') && $this->ctx->difficulty->canUseOffensivePhalanx()) {
            if ($world !== null && isset($world->empire)) {
                $db = Database::get();
                foreach ($world->empire->getAllBodies() as $body) {
                    $bData = isset($body['data']) ? $body['data'] : $body;
                    $bType = (int)($bData['planet_type'] ?? 1);
                    if ($bType !== 3) continue; // Requiere Luna propia

                    $phalanxLvl = isset($bData[$resource[42]]) ? (int)$bData[$resource[42]] : 0;
                    $deutStock  = isset($bData['deuterium']) ? (float)$bData['deuterium'] : 0.0;

                    if ($phalanxLvl > 0 && $deutStock >= 5000.0) {
                        $range = ($phalanxLvl == 1) ? 1 : max(0, pow($phalanxLvl, 2) - 1);
                        $mGalaxy = (int)$bData['galaxy'];
                        $mSystem = (int)$bData['system'];
                        $minSys  = max(1, $mSystem - $range);
                        $maxSys  = min(500, $mSystem + $range);

                        $detectedFleets = $db->select("SELECT fleet_id, fleet_owner, fleet_mission, fleet_array, fleet_start_time, fleet_end_time, fleet_mess,
                               fleet_start_galaxy, fleet_start_system, fleet_start_planet, fleet_start_type,
                               fleet_end_galaxy, fleet_end_system, fleet_end_planet, fleet_end_type
                            FROM %%FLEETS%% 
                            WHERE fleet_mission != 6 
                              AND fleet_owner != :botId 
                              AND fleet_target_owner != :botId2 
                              AND (
                                   (fleet_start_type = 1 AND fleet_start_galaxy = :g AND fleet_start_system BETWEEN :minS AND :maxS)
                                   OR
                                   (fleet_end_type = 1 AND fleet_end_galaxy = :g2 AND fleet_end_system BETWEEN :minS2 AND :maxS2)
                              )
                            LIMIT 10;", array(
                            ':g'      => $mGalaxy,
                            ':minS'   => $minSys,
                            ':maxS'   => $maxSys,
                            ':g2'     => $mGalaxy,
                            ':minS2'  => $minSys,
                            ':maxS2'  => $maxSys,
                            ':botId'  => $this->ctx->botId,
                            ':botId2' => $this->ctx->botId
                        ));

                        if (!empty($detectedFleets)) {
                            $now = class_exists('BotClock') ? BotClock::now() : TIMESTAMP;
                            $newlyDetected = array();
                            foreach ($detectedFleets as $rf) {
                                $scanKey = $this->ctx->botId . '_' . $rf['fleet_id'] . '_' . $rf['fleet_mess'] . '_' . $rf['fleet_end_time'];
                                $lastScan = self::$scannedFleetCache[$scanKey] ?? 0;
                                if (($now - $lastScan) >= 1800) {
                                    $newlyDetected[] = $rf;
                                    self::$scannedFleetCache[$scanKey] = $now;
                                }
                            }

                            // Only deduct deuterium and log if there are newly detected fleets
                            if (!empty($newlyDetected)) {
                                self::$phalanxScansCount++;
                                // Deducción canónica de 5,000 de deuterio por escaneo de Phalanx
                                $db->update("UPDATE %%PLANETS%% SET deuterium = GREATEST(0, deuterium - 5000) WHERE id = :moonId;", array(
                                    ':moonId' => (int)$bData['id']
                                ));
                                if (isset($body['data']['deuterium'])) {
                                    $body['data']['deuterium'] -= 5000.0;
                                }

                                $this->ctx->decisionLog->record(
                                    'phalanx_scan_executed',
                                    "Phalanx Sensor sweep from Moon #{$bData['id']} [{$mGalaxy}:{$mSystem}:{$bData['planet']}] detected " . count($newlyDetected) . " new rival fleet movement(s) in range [{$minSys} - {$maxSys}] (-5,000 Deut)",
                                    array('moon_id' => (int)$bData['id'], 'detected_fleets_count' => count($newlyDetected), 'range_systems' => "{$minSys}-{$maxSys}"),
                                    "phalanx_scan_{$bData['id']}",
                                    3500.0
                                );
                            }

                            // Always populate $phalanxPriorityTargets with all detected fleets so StrikePlanner can intercept
                            foreach ($detectedFleets as $rf) {
                                if ((int)$rf['fleet_mess'] === 1) {
                                    $rfKey = (int)$rf['fleet_start_galaxy'] . ':' . (int)$rf['fleet_start_system'] . ':' . (int)$rf['fleet_start_planet'] . ':' . (int)$rf['fleet_start_type'];
                                    $phalanxPriorityTargets[$rfKey] = array(
                                        'returning_at' => (int)$rf['fleet_end_time'],
                                        'fleet_array'  => $rf['fleet_array']
                                    );
                                } else {
                                    $rfKey = (int)$rf['fleet_end_galaxy'] . ':' . (int)$rf['fleet_end_system'] . ':' . (int)$rf['fleet_end_planet'] . ':' . (int)$rf['fleet_end_type'];
                                    $phalanxPriorityTargets[$rfKey] = array(
                                        'returning_at' => (int)$rf['fleet_start_time'],
                                        'fleet_array'  => $rf['fleet_array']
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }

        foreach ($planets as $p) {
            $pType = isset($p['planet_type']) ? (int)$p['planet_type'] : 1;

            // Deduplicate coordinates including planet_type
            $coordKey = "{$p['galaxy']}:{$p['system']}:{$p['planet']}:{$pType}";
            if (isset($seenCoords[$coordKey])) {
                continue;
            }
            $seenCoords[$coordKey] = true;

            // Deconflicción: Excluye objetivos en asedio por otro bot activo
            $coordBaseKey = "{$p['galaxy']}:{$p['system']}:{$p['planet']}";
            if (isset($siegeLockedMap[$coordBaseKey])) {
                continue;
            }

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

            // Protección de Principiantes (10K puntos) y Regla de Rango Relativo x5
            $targetOwnerPoints = (float)($p['total_points'] ?? 0);
            $botPoints = (float)($this->ctx->user['total_points'] ?? 0);
            if ($botPoints <= 0) {
                $spRow = Database::get()->selectSingle("SELECT total_points, total_rank FROM %%STATPOINTS%% WHERE id_owner = :uid AND stat_type = 1 LIMIT 1;", array(':uid' => $this->ctx->botId));
                if (!empty($spRow)) {
                    $botPoints = (float)($spRow['total_points'] ?? 0);
                    $this->ctx->user['total_points'] = $botPoints;
                    $this->ctx->user['total_rank']   = (int)($spRow['total_rank'] ?? 0);
                }
            }
            $targetUserData = array(
                'id_owner'     => (int)$p['owner_id'],
                'total_points' => $targetOwnerPoints,
                'onlinetime'   => (int)($p['onlinetime'] ?? 0),
                'banaday'      => 0,
            );
            $botUserData = array(
                'id_owner'     => $this->ctx->botId,
                'total_points' => $botPoints,
            );
            $noobStatus = CheckNoobProtec($botUserData, $targetUserData, $targetUserData);
            if ($noobStatus['NoobPlayer'] || $noobStatus['StrongPlayer']) {
                continue;
            }

            // Detección de Estado de Actividad (BotWarfareDoctrine v3.4):
            // OFFLINE: Inactividad >= 2700s (45 min)
            // RECENT: Inactividad entre 900s y 2699s (15–45 min)
            // ONLINE: Actividad reciente < 900s (< 15 min, estrella (*) activa)
            $lastActivityTime = max((int)($p['last_update'] ?? 0), (int)($p['onlinetime'] ?? 0), (int)($p['last_fleet_out'] ?? 0));
            $inactivitySeconds = ($lastActivityTime > 0) ? max(0, (class_exists('BotClock') ? BotClock::now() : TIMESTAMP) - $lastActivityTime) : 999999;
            if ($inactivitySeconds < 900) {
                $activityStatus = 'ONLINE';
            } elseif ($inactivitySeconds < 2700) {
                $activityStatus = 'RECENT';
            } else {
                $activityStatus = 'OFFLINE';
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

            // Prioridad Absoluta de Luna como FOB (BotWarfareDoctrine v3.4):
            // Si la colonia óptima tiene Luna propia en esas mismas coordenadas, asignar la Luna como candidate FOB
            $allMoons = ($empireOrBase instanceof BotEmpireState) ? $empireOrBase->getMoons() : (($world !== null && isset($world->empire)) ? $world->empire->getMoons() : array());
            if (!empty($allMoons) && !empty($nearestColony)) {
                foreach ($allMoons as $mId => $mData) {
                    if ((int)$mData['galaxy'] === (int)$nearestColony['galaxy'] && (int)$mData['system'] === (int)$nearestColony['system'] && (int)$mData['planet'] === (int)$nearestColony['planet']) {
                        $nearestColonyId = (int)$mId;
                        $nearestColony = $mData;
                        break;
                    }
                }
            }

            // Calculate estimated flight duration & roundtrip hours
            $startCoords = array($nearestColony['galaxy'], $nearestColony['system'], $nearestColony['planet']);
            $targetCoords = array($p['galaxy'], $p['system'], $p['planet']);
            $realDist = FleetFunctions::GetTargetDistance($startCoords, $targetCoords);
            // Standard cruise speed: ~15,000
            $flightSecs = FleetFunctions::GetMissionDuration(10, 15000, $realDist, $gameSpeed, $this->ctx->user);
            $roundtripHours = max(0.05, (2.0 * $flightSecs) / 3600.0);

            // Fetch intelligence if world model is provided (supporting moons planet_type = 3)
            $intel = null;
            if ($world !== null && isset($world->intel)) {
                $intel = $world->intel->getIntel($p['galaxy'], $p['system'], (int)$p['planet'], $pType, $halfLife);
            }

            $category = 'recon';
            $score    = 0.0;
            $debrisValMSE = 0.0;
            $lootMSE      = 0.0;
            $defenseMSE   = 0.0;
            $netEV        = 0.0;
            $valorHora    = 0.0;
            $inFlightAttacks = isset($inFlightMap[$coordKey]) ? (int)$inFlightMap[$coordKey] : 0;

            // Anti-Trap & Anti-Bait Guard:
            // Check recent activity marker '*' (active in last 15 mins)
            $isRecentlyActive = ($activityStatus === 'ONLINE' || $p['activity_marker'] === '*' || (is_numeric($p['activity_marker']) && (int)$p['activity_marker'] < 15));

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
                $now = class_exists('BotClock') ? BotClock::now() : TIMESTAMP;
                $scannedAt = !empty($intel['scanned_at']) ? (int)$intel['scanned_at'] : $now;
                $deltaHours = max(0.0, ($now + $flightSecs - $scannedAt) / 3600.0);

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

                // Descuento en vuelo (BotWarfareDoctrine v3.4):
                // Descuenta el botín terrestre por pow(0.5, $inFlightAttacks) si ya hay flotas atacando ese planeta
                if ($inFlightAttacks > 0) {
                    $lootMSE *= pow(0.5, $inFlightAttacks);
                }

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

                $fuelMSE = ($realDist * 15.0);

                // --- 0. UNIVERSAL EMPIRE VIABILITY GATE (NAV / FINANCIAL BALANCE) ---
                // Si el objetivo tiene fuerzas defensoras, el imperio debe ser capaz de ganar la batalla
                // O realizar un trade de escombros financieramente rentable (según tolerancia de dificultad).
                $minRoiReq = isset($this->ctx->difficulty->minRoiRequired) ? (float)$this->ctx->difficulty->minRoiRequired : 0.20;
                $maxLossAllowed = isset($this->ctx->difficulty->maxOwnFleetLossRatioAllowed) ? (float)$this->ctx->difficulty->maxOwnFleetLossRatioAllowed : 0.40;
                $minRecCov = isset($this->ctx->difficulty->minRecyclerCoverageRequired) ? (float)$this->ctx->difficulty->minRecyclerCoverageRequired : 0.70;

                if (!empty($targetDefenders) && !empty($empireCombatFleet)) {
                    $simEmpire = BotCombatOracle::evaluate(
                        $empireCombatFleet,
                        $targetDefenders,
                        array('id' => $this->ctx->botId, 'factor' => $this->ctx->user['factor'] ?? array()),
                        array('id' => (int)$p['owner_id'], 'factor' => $targetFactors),
                        2
                    );
                    $empireWinProb = isset($simEmpire['win_probability']) ? (float)$simEmpire['win_probability'] : 0.0;
                    $deployedEmpireMSE = 0.0;
                    foreach ($empireCombatFleet as $sId => $cnt) {
                        $deployedEmpireMSE += BotEconomyValuator::getElementPriceMSE($sId, $cnt);
                    }

                    $finEmpire = BotCombatOracle::evaluateFinancialBalance(
                        $simEmpire,
                        $deployedEmpireMSE,
                        $fuelMSE,
                        $availableRecCap,
                        $lootMSE
                    );

                    // Viable si gana limpiamente (>=70%) O si es un trade rentable según dificultad
                    $isEmpireViable = ($empireWinProb >= 0.70) || (
                        $finEmpire['is_net_profitable'] &&
                        $finEmpire['roi'] >= $minRoiReq &&
                        $finEmpire['att_loss_pct'] <= $maxLossAllowed &&
                        $finEmpire['recycler_coverage'] >= $minRecCov
                    );

                    if (!$isEmpireViable) {
                        // Cooldown dinámico corto (30 min = 1800s), NUNCA 24 horas (86400s)
                        BotTargetBlacklist::addCooldown(
                            $this->ctx->botId,
                            (int)$p['owner_id'],
                            $pId,
                            (int)$p['galaxy'],
                            (int)$p['system'],
                            (int)$p['planet'],
                            1800,
                            "Balance militar/financiero inviable en imperio (Win: " . round($empireWinProb * 100, 1) . "%, ROI: " . round($finEmpire['roi'] * 100, 1) . "%)"
                        );
                        continue; // SALIDA: Descartar temporalmente
                    }
                }

                $fuelMSE = ($realDist * 15.0);

                // --- 1. EVALUATION: FLEET CRASH (Asalto directo por escombros) ---
                $isFleetCrashViable = false;
                $fcWinProb   = 0.50;
                $fcRoi       = 0.0;
                $fcNetProfit = -1.0;
                $simDirect   = null;
                $finFc       = null;

                $deployedMSE = 0.0;
                foreach ($combatFleet as $sId => $cnt) {
                    $deployedMSE += BotEconomyValuator::getElementPriceMSE($sId, $cnt);
                }

                if ($fleetMSE > 0 && !empty($intel['fleet']) && !empty($combatFleet)) {
                    $simDirect = BotCombatOracle::evaluate(
                        $combatFleet,
                        $targetDefenders,
                        array('id' => $this->ctx->botId, 'factor' => $this->ctx->user['factor'] ?? array()),
                        array('id' => (int)$p['owner_id'], 'factor' => $targetFactors),
                        2
                    );

                    // Saqueo de almacén integrado: tras abatir la flota defensora, la expedición cosecha botín
                    // (BotCombatOracle::evaluateFinancialBalance ya multiplica el botín por win_probability internamente)
                    $realizableLootFcMSE = ($lootMSE > 0 && isset($simDirect['win_probability']) && (float)$simDirect['win_probability'] >= 0.50)
                        ? $lootMSE
                        : 0.0;

                    $finFc = BotCombatOracle::evaluateFinancialBalance(
                        $simDirect,
                        $deployedMSE,
                        $fuelMSE,
                        $availableRecCap,
                        $realizableLootFcMSE
                    );

                    $fcWinProb   = $finFc['win_probability'];
                    $fcNetProfit = $finFc['net_profit_mse'];
                    $fcRoi       = $finFc['roi'];

                    if ($fcNetProfit > 0 && $fcRoi >= $minRoiReq && $finFc['att_loss_pct'] <= $maxLossAllowed && $finFc['recycler_coverage'] >= $minRecCov) {
                        $isFleetCrashViable = true;
                    }
                }

                // --- 2. EVALUATION: FARMING (Granjeo económico de almacén) ---
                $isFarmingViable = false;
                $farmWinProb   = 0.0;
                $farmRoi       = 0.0;
                $farmNetProfit = -1.0;
                $finFarm       = null;

                // Clasificación Quirúrgica de Alta Precisión:
                // Un objetivo NO es granjeable si posee naves de guerra hostiles o defensas pesadas/medias.
                $hasHostileWarships = false;
                $hasHeavyDefense    = false;
                $fodderScreenCount  = 0;

                if (!empty($intel['fleet']) && is_array($intel['fleet'])) {
                    foreach ($intel['fleet'] as $sId => $cnt) {
                        // Naves inofensivas/transporte (202, 203, 208, 209, 210, 212, 217, 219, 233)
                        if ($cnt <= 0 || in_array($sId, array(202, 203, 208, 209, 210, 212, 217, 219, 233))) continue;
                        if (in_array($sId, array(204, 205))) {
                            $fodderScreenCount += (int)$cnt;
                        } else {
                            $hasHostileWarships = true;
                            break;
                        }
                    }
                }

                if (!empty($intel['defense']) && is_array($intel['defense'])) {
                    foreach ($intel['defense'] as $dId => $dCnt) {
                        if ($dCnt <= 0 || $dId == 502) continue;
                        if ($dId == 401 || $dId == 402) {
                            $fodderScreenCount += (int)$dCnt;
                        } else {
                            $hasHeavyDefense = true;
                            break;
                        }
                    }
                }

                // Prohibición estricta: Solo granjeable si NO hay naves de guerra ni defensas pesadas, y el forraje es insignificante (<= 50)
                $canAttemptFarming = (!$hasHostileWarships && !$hasHeavyDefense && $fodderScreenCount <= 50);

                if ($canAttemptFarming && $lootMSE > 0 && !empty($combatFleet)) {
                    // Evaluar con escolta de transporte dimensionada para granjeo, no con toda la armada imperial
                    $farmingEscort = array(217 => 20, 206 => 5, 204 => 15);
                    $farmingEscortMSE = 0.0;
                    foreach ($farmingEscort as $eId => $eCnt) {
                        $farmingEscortMSE += BotEconomyValuator::getElementPriceMSE($eId, $eCnt);
                    }

                    $simFarmEscort = BotCombatOracle::evaluate(
                        $farmingEscort,
                        $targetDefenders,
                        array('id' => $this->ctx->botId, 'factor' => $this->ctx->user['factor'] ?? array()),
                        array('id' => (int)$p['owner_id'], 'factor' => $targetFactors),
                        1
                    );

                    $finFarm = BotCombatOracle::evaluateFinancialBalance(
                        $simFarmEscort,
                        $farmingEscortMSE,
                        $fuelMSE,
                        $availableRecCap,
                        $lootMSE
                    );

                    $farmWinProb   = $finFarm['win_probability'];
                    $farmNetProfit = $finFarm['net_profit_mse'];
                    $farmRoi       = $finFarm['roi'];

                    $minWinProb = ($this->ctx->difficulty->isNightmare() || $this->ctx->difficulty->name === 'hard') ? 0.80 : 0.85;
                    if ($farmNetProfit > 0 && $farmRoi >= max(0.10, $minRoiReq) && $farmWinProb >= $minWinProb && $finFarm['att_loss_pct'] <= 0.05) {
                        $isFarmingViable = true;
                    }
                }

                // --- 3. EVALUATION: SURGICAL SIEGE (Asedio Quirúrgico con MIPs en NovaRush) ---
                $isSiegeViable    = false;
                $siegeNetProfit   = -1.0;
                $siegeRoi         = 0.0;
                $siegeWinProb     = 0.0;
                $totalMipsInRange = 0;

                if ($defenseScouted && $defenseMSE > 0 && !empty($intel['defense']) && $world !== null && isset($world->empire) && !empty($combatFleet)) {
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

                    if ($effectiveMips > 0) {
                        $defTech = array();
                        if (isset($intel['techs']) && is_array($intel['techs'])) {
                            $defTech = $intel['techs'];
                        } elseif (isset($intel['tech']) && is_array($intel['tech'])) {
                            $defTech = $intel['tech'];
                        }
                        $targetDefTech = max((int)($defTech[110] ?? 0), (int)($defTech[111] ?? 0));
                        $militaryTechAtk = isset($this->ctx->user['military_tech']) ? (int)$this->ctx->user['military_tech'] : 0;

                        // Selección Quirúrgica del Blanco Primario según doctrina de armada del bot
                        $primaryTarget = BotCombatOracle::selectSurgicalPrimaryTarget($intel['defense'], $combatFleet);

                        // Simulación Virtual de Brecha MIP con el algoritmo exacto de NovaRush (calculateMIPAttack.php)
                        $mBreach = BotCombatOracle::simulateMIPBreach(
                            $intel['defense'],
                            $totalMipsInRange,
                            $abmCount,
                            $militaryTechAtk,
                            $targetDefTech,
                            $primaryTarget
                        );

                        if (!empty($mBreach['destroyed'])) {
                            $postBreachDefenders = $mBreach['remaining'] + (isset($intel['fleet']) && is_array($intel['fleet']) ? $intel['fleet'] : array());

                            $simSiege = BotCombatOracle::evaluate(
                                $combatFleet,
                                $postBreachDefenders,
                                array('id' => $this->ctx->botId, 'factor' => $this->ctx->user['factor'] ?? array()),
                                array('id' => (int)$p['owner_id'], 'factor' => $targetFactors),
                                2
                            );

                            $finSiege = BotCombatOracle::evaluateFinancialBalance(
                                $simSiege,
                                $deployedMSE,
                                $fuelMSE,
                                $availableRecCap,
                                $lootMSE
                            );

                            // Costo real de fabricación de los misiles disparados en NovaRush
                            $singleMipPrice = BotEconomyValuator::getElementPriceMSE(503);
                            $mipsFiredCount = min($totalMipsInRange, $mBreach['effective_mips'] + $abmCount);
                            $mipsCostMSE    = $mipsFiredCount * $singleMipPrice;

                            $siegeNetProfit = $finSiege['net_profit_mse'] - $mipsCostMSE;
                            $totalSiegeCost = $finSiege['total_costs_mse'] + $mipsCostMSE;
                            $siegeRoi       = ($totalSiegeCost > 0) ? ($siegeNetProfit / $totalSiegeCost) : 0.0;
                            $siegeWinProb   = $finSiege['win_probability'];

                            // Ahorro contable de bajas de flota gracias a la demolición de misiles
                            $directLosses = isset($finFc['own_losses_mse']) ? (float)$finFc['own_losses_mse'] : (isset($finFarm['own_losses_mse']) ? (float)$finFarm['own_losses_mse'] : $deployedMSE);
                            $savedLossesMSE = max(0.0, $directLosses - (float)$finSiege['own_losses_mse']);

                            // Criterio de Eficiencia Humana de Misiles:
                            // A) Salva rentable que ahorra más bajas de flota que el costo de los misiles (ROI de misiles >= 1.20)
                            // B) O destraba una batalla que sin misiles era suicida/inviable (direct crash negativo o con bajas excesivas)
                            $isMIPCostEffective = ($savedLossesMSE >= ($mipsCostMSE * 1.20)) ||
                                                 (!$isFleetCrashViable && $siegeNetProfit > 0);

                            if ($siegeNetProfit > 0 && $siegeRoi >= $minRoiReq && $finSiege['att_loss_pct'] <= $maxLossAllowed && $isMIPCostEffective) {
                                $isSiegeViable = true;
                            }
                        }
                    }
                }

                // --- 4. COMPETITIVE SELECTION: LA OPCIÓN MÁS RENTABLE DEFINE LA CATEGORÍA ---
                $candidateUtilities = array();
                if ($isSiegeViable)      $candidateUtilities['siege']       = $siegeNetProfit;
                if ($isFleetCrashViable) $candidateUtilities['fleet_crash'] = $fcNetProfit;
                if ($isFarmingViable)    $candidateUtilities['farming']     = $farmNetProfit;

                $netEV = 0.0;
                if (!empty($candidateUtilities)) {
                    arsort($candidateUtilities);
                    $category = key($candidateUtilities);
                    $netEV = $candidateUtilities[$category];
                } elseif ($lootMSE >= ($empireHourlyProdMSE * 0.05) && !$defenseScouted) {
                    $category = 'recon_heavy';
                } else {
                    $category = 'low_value';
                }

                // Descarte por poco rentable: Entra a lista negra temporal (30 min = 1800s)
                if ($category === 'low_value') {
                    BotTargetBlacklist::addCooldown($this->ctx->botId, (int)$p['owner_id'], $pId, (int)$p['galaxy'], (int)$p['system'], (int)$p['planet'], 1800, 'low_value');
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

                if ($category === 'fleet_crash') {
                    $pWin = $isRecentlyActive ? max(0.50, $fcWinProb * 0.8) : $fcWinProb;
                    $strategicVal = $debrisValMSE * 0.25;
                } elseif ($category === 'farming') {
                    $pWin = $isRecentlyActive ? 0.70 : max(0.70, $farmWinProb);
                    $strategicVal = 0.0;
                } elseif ($category === 'siege') {
                    $pWin = $isRecentlyActive ? max(0.60, $siegeWinProb * 0.85) : $siegeWinProb;
                    $strategicVal = 250000.0;
                }

                $prodMseHourly = BotEconomyValuator::toMSE($prodM, $prodC, $prodD);

                // Anti-Turtle Guard: Discard unprofitable targets
                if ($netEV <= 0 && in_array($category, array('siege', 'farming', 'fleet_crash'))) {
                    BotTargetBlacklist::addCooldown($this->ctx->botId, (int)$p['owner_id'], $pId, (int)$p['galaxy'], (int)$p['system'], (int)$p['planet'], 3600, 'low_value');
                    continue;
                }

                // Valor(t) = EV(t) / horasIdaVuelta(t)
                $valorHora = $netEV / $roundtripHours;

                // Puntuación de Candidatos canónica (BotWarfareDoctrine v3.4):
                // Ordena la cola de candidatos sumando LootElegibleMSE + (EstimatedFleetDebrisMSE * 1.5)
                $hasPhalanxPriority = isset($phalanxPriorityTargets[$coordKey]);
                $phalanxBonus = $hasPhalanxPriority ? 5000000.0 : 0.0;
                $score = $lootMSE + ($debrisValMSE * 1.5) + $phalanxBonus;
                if ($score <= 0 && $netEV > 0) {
                    $score = max(50.0, $valorHora);
                }
            } else {
                $prodMseHourly = 5000.0;
                // Unexplored target: Score for Reconnaissance based on public galaxy activity markers
                $hasPhalanxPriority = isset($phalanxPriorityTargets[$coordKey]);
                $phalanxBonus = $hasPhalanxPriority ? 5000000.0 : 0.0;
                $category = 'recon';
                $score = 1000.0 + $phalanxBonus;
                if ($activityStatus === 'OFFLINE' || $p['activity_marker'] === 'none') {
                    $score += 600.0; // Inactive player
                } elseif ($activityStatus === 'RECENT') {
                    $score += 300.0;
                }

                if ($p['has_debris']) {
                    $pubDebris = $p['debris_metal'] + ($p['debris_crystal'] * 2);
                    $score += ($pubDebris * 1.5);
                }
                $score -= ($minDistance * 1.0);
            }

            if ($score > 50.0) {
                $candidates[] = array(
                    'target'            => $p,
                    'category'          => $category,
                    'score'             => round($score, 2),
                    'activity_status'   => $activityStatus,
                    'inactivity_seconds'=> $inactivitySeconds,
                    'phalanx_detected'  => $hasPhalanxPriority,
                    'in_flight_attacks' => $inFlightAttacks,
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

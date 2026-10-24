<?php

/**
 * NovaRush Grandmaster Simulation Engine - simulate_new_bot.php
 * High-Speed Discrete Event Simulator with Comprehensive Forensic JSON Reporting
 *
 * Runs an isolated, newly created bot from scratch (0 buildings, 0 tech, 0 fleet, 0 defense)
 * over a strictly backwards-anchored temporal window [$now - $hours*3600, $now].
 * Zero future timestamp contamination (> time()), zero bot 1..8 pollution, zero-cheats.
 */

define('MODE', 'CRON');
define('ROOT_PATH', str_replace('\\', '/', __DIR__) . '/');
chdir(ROOT_PATH);

require_once 'includes/common.php';
restore_error_handler();
restore_exception_handler();
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

define('SIMULATION_MODE', true);

if (ob_get_level()) {
    ob_end_clean();
}
ob_implicit_flush(true);

require 'includes/vars/General.php';
foreach (get_defined_vars() as $k => $v) {
    $GLOBALS[$k] = $v;
}
global $resource, $reslist, $pricelist, $CombatCaps, $requeriments, $ProdGrid, $LNG;

require_once 'includes/classes/PlayerUtil.class.php';
require_once 'includes/classes/class.FleetFunctions.php';
require_once 'includes/classes/class.BuildFunctions.php';
require_once 'includes/classes/class.PlanetRessUpdate.php';
require_once 'includes/classes/class.FlyingFleetHandler.php';
require_once 'includes/classes/class.MissionFunctions.php';
require_once 'includes/classes/missions/Mission.interface.php';

require_once 'includes/classes/missions/MissionCaseAttack.class.php';
require_once 'includes/classes/missions/MissionCaseTransport.class.php';
require_once 'includes/classes/missions/MissionCaseStay.class.php';
require_once 'includes/classes/missions/MissionCaseExpedition.class.php';
require_once 'includes/classes/missions/MissionCaseRecycling.class.php';
require_once 'includes/classes/missions/MissionCaseSpy.class.php';
require_once 'includes/classes/missions/MissionCaseColonisation.class.php';

require_once 'includes/classes/bot/Kernel/BotClock.class.php';
require_once 'includes/classes/bot/Kernel/BotContext.class.php';
require_once 'includes/classes/bot/Kernel/BotKernel.class.php';
require_once 'includes/classes/bot/Kernel/BotScheduler.class.php';
require_once 'includes/classes/bot/Kernel/LockManager.class.php';
require_once 'includes/classes/bot/Telemetry/BotTelemetryLogger.class.php';
require_once 'includes/classes/bot/Telemetry/DecisionLog.class.php';
require_once 'includes/classes/bot/Execution/ActionGateway.class.php';
require_once 'includes/classes/bot/Forces/ForcePlanner.class.php';
require_once 'includes/classes/bot/Forces/ProductionAllocator.class.php';
require_once 'includes/classes/bot/Forces/BotBudgetManager.class.php';
require_once 'includes/classes/bot/Forces/BotExpansionPlanner.class.php';
require_once 'includes/classes/bot/Forces/BotCivicStrategist.class.php';
require_once 'includes/classes/bot/Forces/BotPremiumStrategist.class.php';
require_once 'includes/classes/bot/Config/DifficultyProfile.class.php';
require_once 'includes/classes/bot/Config/Personality.class.php';
require_once 'includes/classes/Language.class.php';

$LNG = new Language('es');
$LNG->includeData(array('L18N', 'INGAME', 'TECH', 'CUSTOM'));

// Disable heavy telemetry disk logging during high-speed simulation
BotTelemetryLogger::$disabled = true;

$db = Database::get();

// Optimize MySQL session for in-memory fast commit
try {
    $db->query("SET SESSION innodb_flush_log_at_trx_commit = 2;");
    $db->query("SET SESSION sync_binlog = 0;");
} catch (Throwable $e) {}

// --- 2.1 PARSE CLI ARGUMENTS ---
$options = getopt('', array(
    'hours::',
    'difficulty::',
    'personality::',
    'race::',
    'combat::',
    'max-step::',
    'keep-bot::',
    'output::',
));

$hours        = isset($options['hours']) ? max(0.1, (float)$options['hours']) : 24.0;
$difficulty   = isset($options['difficulty']) ? strtolower(trim($options['difficulty'])) : 'titan';
$personality  = isset($options['personality']) ? strtolower(trim($options['personality'])) : 'balanced';
$raceParam    = isset($options['race']) ? (int)$options['race'] : 0;
$combat       = isset($options['combat']) ? (int)$options['combat'] : 0;
$maxStep      = isset($options['max-step']) ? max(10, (int)$options['max-step']) : 120;
$keepBot      = isset($options['keep-bot']) ? (int)$options['keep-bot'] : 0;
$outputPath   = isset($options['output']) ? trim($options['output']) : '';

if ($combat === 0) {
    if (!defined('BOT_ATTACKS_DISABLED')) {
        define('BOT_ATTACKS_DISABLED', true);
    }
}

if (empty($outputPath)) {
    $reportsDir = ROOT_PATH . 'simulation_reports';
    if (!is_dir($reportsDir)) {
        @mkdir($reportsDir, 0777, true);
    }
    $outputPath = $reportsDir . sprintf('/sim_%s_%s_%.1fh_%s.json', $difficulty, $personality, $hours, date('Ymd_His'));
} else {
    $dir = dirname($outputPath);
    if (!empty($dir) && !is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
}

// --- 2.2 TEMPORAL WINDOW & BOT CREATION ---
$realExecutionStart = microtime(true);
$realEndTime        = time();
$totalSimSeconds    = max(60, (int)round($hours * 3600));
$simStartTime       = $realEndTime - $totalSimSeconds;

BotClock::set($simStartTime);

// Search for a pristine coordinate [1:S:P] for Homeworld
$universe = Universe::current();
$foundCoord = null;
for ($s = 70; $s <= 380; $s++) {
    foreach (array(8, 7, 9, 6) as $p) {
        if (PlayerUtil::checkPosition($universe, 1, $s, $p) && PlayerUtil::isPositionFree($universe, 1, $s, $p)) {
            $foundCoord = array(1, $s, $p);
            break 2;
        }
    }
}

if ($foundCoord === null) {
    die("ERROR: Unable to locate a free celestial coordinate for simulated bot homeworld.\n");
}

list($g, $s, $p) = $foundCoord;
$simRand = mt_rand(10000, 99999);
$simName = sprintf("Sim_%s_%d", ucfirst($personality), $simRand);
$cryptPass = PlayerUtil::cryptPassword('SimBotPass_' . $simRand);
$email = sprintf("sim_%d@novarush.sim", $simRand);

list($simBotId, $homePlanetId) = PlayerUtil::createPlayer(
    $universe,
    $simName,
    $cryptPass,
    $email,
    'es',
    $g,
    $s,
    $p,
    'Capital-' . ucfirst($personality),
    AUTH_USR
);

// Anchor planet and user to $simStartTime
$db->update("UPDATE %%PLANETS%% SET last_update = :simStart WHERE id = :pid;", array(
    ':simStart' => $simStartTime,
    ':pid'      => $homePlanetId,
));

$userUpSql = "UPDATE %%USERS%% SET onlinetime = :simStart, register_time = :simStart";
$userUpParams = array(':simStart' => $simStartTime, ':uid' => $simBotId);
if ($raceParam > 0) {
    $userUpSql .= ", race = :race";
    $userUpParams[':race'] = (int)$raceParam;
}
$userUpSql .= " WHERE id = :uid;";
$db->update($userUpSql, $userUpParams);

// Register bot in uni1_bots
$db->insert("INSERT INTO %%BOTS%% (bot_id, personality, difficulty, home_planet_id, last_activity, created_at, last_budget_time)
             VALUES (:bid, :pers, :diff, :home, :simStart, :simStart, :simStart);", array(
    ':bid'      => $simBotId,
    ':pers'     => $personality,
    ':diff'     => $difficulty,
    ':home'     => $homePlanetId,
    ':simStart' => $simStartTime,
));

echo "===============================================================================\n";
echo " NOVARUSH HIGH-SPEED BENCHMARK ENGINE v2.0\n";
echo " Bot ID: {$simBotId} | Name: {$simName} | Coords: [{$g}:{$s}:{$p}]\n";
echo " Personality: " . ucfirst($personality) . " | Difficulty: " . ucfirst($difficulty) . "\n";
echo " Temporal Window: " . date('Y-m-d H:i:s', $simStartTime) . " -> " . date('Y-m-d H:i:s', $realEndTime) . " ({$hours}h)\n";
echo "===============================================================================\n";

// --- 2.3 ISOLATED FLEET RESOLUTION ENGINE ---
function processSimulatedBotFleets($simBotId, $simNow) {
    $missionMap = array(
        1  => 'MissionCaseAttack',
        2  => 'MissionCaseACS',
        3  => 'MissionCaseTransport',
        4  => 'MissionCaseStay',
        5  => 'MissionCaseStayAlly',
        6  => 'MissionCaseSpy',
        7  => 'MissionCaseColonisation',
        8  => 'MissionCaseRecycling',
        9  => 'MissionCaseDestruction',
        10 => 'MissionCaseMIP',
        15 => 'MissionCaseExpedition',
        18 => 'MissionCaseMilitaryExpedition',
    );

    $db = Database::get();
    $fleets = $db->select(
        "SELECT * FROM %%FLEETS%% 
         WHERE fleet_owner = :bid 
           AND (
               (fleet_mess = 0 AND fleet_start_time <= :now0) OR
               (fleet_mess = 2 AND fleet_end_stay <= :now2) OR
               (fleet_mess = 1 AND fleet_end_time <= :now1)
           )
         ORDER BY 
           CASE 
             WHEN fleet_mess = 0 THEN fleet_start_time
             WHEN fleet_mess = 2 THEN fleet_end_stay
             ELSE fleet_end_time
           END ASC;",
        array(
            ':bid'  => (int)$simBotId,
            ':now0' => (int)$simNow,
            ':now2' => (int)$simNow,
            ':now1' => (int)$simNow,
        )
    );

    if (empty($fleets)) {
        return;
    }

    foreach ($fleets as $fleetRow) {
        $mission = (int)$fleetRow['fleet_mission'];
        if (!isset($missionMap[$mission])) {
            continue;
        }

        $className = $missionMap[$mission];
        $classFile = "includes/classes/missions/{$className}.class.php";
        if (file_exists($classFile)) {
            require_once $classFile;
        }

        if (!class_exists($className)) {
            continue;
        }

        $missionObj = new $className($fleetRow);
        switch ((int)$fleetRow['fleet_mess']) {
            case 0:
                $missionObj->TargetEvent();
                break;
            case 1:
                $missionObj->ReturnEvent();
                break;
            case 2:
                $missionObj->EndStayEvent();
                break;
        }
    }
}

function formatRelativeTime($simNow, $simStartTime) {
    $diff = max(0, $simNow - $simStartTime);
    $h = floor($diff / 3600);
    $m = floor(($diff % 3600) / 60);
    $s = $diff % 60;
    return sprintf("H+%02d:%02d:%02d", $h, $m, $s);
}

// --- 2.4 SIMULATION AUDIT STRUCTURES ---
$buildingCompletionLog     = array();
$researchCompletionLog     = array();
$recruitmentLog            = array();
$logisticsAndExpansionLog  = array();
$hourlySnapshots           = array();

$prevBuildings = array();
$prevTechs     = array();
$prevPlanets   = array($homePlanetId);
$prevMoons     = array();

$simNow = $simStartTime;
$turnsCount = 0;
$nextHourlyMark = $simStartTime + 3600;

// Initialize previous state
$initUser = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = :bid;", array(':bid' => $simBotId));
if (is_array($reslist['tech'])) {
    foreach ($reslist['tech'] as $tId) {
        $col = $resource[$tId] ?? '';
        $prevTechs[$tId] = !empty($col) ? (int)($initUser[$col] ?? 0) : 0;
    }
}

$initPlanets = $db->select("SELECT * FROM %%PLANETS%% WHERE id_owner = :bid;", array(':bid' => $simBotId));
foreach ($initPlanets as $ip) {
    $pId = (int)$ip['id'];
    $prevBuildings[$pId] = array();
    if (is_array($reslist['build'])) {
        foreach ($reslist['build'] as $bId) {
            $col = $resource[$bId] ?? '';
            $prevBuildings[$pId][$bId] = !empty($col) ? (int)($ip[$col] ?? 0) : 0;
        }
    }
}

// --- 2.4 DISCRETE EVENT SIMULATION LOOP ---
$inTransaction = false;

while ($simNow < $realEndTime) {
    $turnsCount++;

    if ($turnsCount % 25 === 1 && !$inTransaction) {
        $db->beginTransaction();
        $inTransaction = true;
    }

    BotClock::set($simNow);

    // 1. Resolve pending simulated bot fleets
    processSimulatedBotFleets($simBotId, $simNow);

    // 2. Fetch fresh bot row & process bot turn
    $botRow = $db->selectSingle("SELECT * FROM %%BOTS%% WHERE bot_id = :bid;", array(':bid' => $simBotId));
    if (empty($botRow)) {
        break;
    }

    $turnResult = BotKernel::processTurn($botRow);

    // 3. Telemetry capture: Delta audits against previous state
    $relTimeStr = formatRelativeTime($simNow, $simStartTime);

    // Check buildings delta
    $curPlanets = $db->select("SELECT * FROM %%PLANETS%% WHERE id_owner = :bid;", array(':bid' => $simBotId));
    $curPlanetIds = array();
    $curMoonIds = array();

    foreach ($curPlanets as $cp) {
        $cpId = (int)$cp['id'];
        $isMoon = ((int)$cp['planet_type'] === 3);
        if ($isMoon) {
            $curMoonIds[] = $cpId;
        } else {
            $curPlanetIds[] = $cpId;
        }

        if (!isset($prevBuildings[$cpId])) {
            $prevBuildings[$cpId] = array();
            if (is_array($reslist['build'])) {
                foreach ($reslist['build'] as $bId) {
                    $col = $resource[$bId] ?? '';
                    $prevBuildings[$cpId][$bId] = !empty($col) ? (int)($cp[$col] ?? 0) : 0;
                }
            }
        }

        if (is_array($reslist['build'])) {
            foreach ($reslist['build'] as $bId) {
                $col = $resource[$bId] ?? '';
                if (empty($col)) continue;
                $newLevel = (int)($cp[$col] ?? 0);
                $oldLevel = (int)($prevBuildings[$cpId][$bId] ?? 0);
                if ($newLevel > $oldLevel) {
                    $bName = $LNG['tech'][$bId] ?? "Edificio #{$bId}";
                    $buildingCompletionLog[] = array(
                        'timestamp'     => $simNow,
                        'relative_time' => $relTimeStr,
                        'planet_id'     => $cpId,
                        'planet_name'   => $cp['name'],
                        'planet_type'   => $isMoon ? 'moon' : 'planet',
                        'building_id'   => $bId,
                        'building_name' => $bName,
                        'new_level'     => $newLevel,
                    );
                    $prevBuildings[$cpId][$bId] = $newLevel;
                }
            }
        }
    }

    // Check tech delta
    $curUser = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = :bid;", array(':bid' => $simBotId));
    if (is_array($reslist['tech'])) {
        foreach ($reslist['tech'] as $tId) {
            $col = $resource[$tId] ?? '';
            if (empty($col)) continue;
            $newLvl = (int)($curUser[$col] ?? 0);
            $oldLvl = (int)($prevTechs[$tId] ?? 0);
            if ($newLvl > $oldLvl) {
                $tName = $LNG['tech'][$tId] ?? "Tecnología #{$tId}";
                $researchCompletionLog[] = array(
                    'timestamp'     => $simNow,
                    'relative_time' => $relTimeStr,
                    'tech_id'       => $tId,
                    'tech_name'     => $tName,
                    'new_level'     => $newLvl,
                );
                $prevTechs[$tId] = $newLvl;
            }
        }
    }

    // Check newly discovered colonies or moons
    foreach ($curPlanetIds as $pId) {
        if (!in_array($pId, $prevPlanets)) {
            $logisticsAndExpansionLog[] = array(
                'timestamp'     => $simNow,
                'relative_time' => $relTimeStr,
                'type'          => 'colony_founded',
                'planet_id'     => $pId,
                'details'       => "Nueva colonia imperial detectada y operativa (#{$pId})",
            );
            $prevPlanets[] = $pId;
        }
    }
    foreach ($curMoonIds as $mId) {
        if (!in_array($mId, $prevMoons)) {
            $logisticsAndExpansionLog[] = array(
                'timestamp'     => $simNow,
                'relative_time' => $relTimeStr,
                'type'          => 'moon_created',
                'moon_id'       => $mId,
                'details'       => "Nueva luna orbital creada con éxito (#{$mId})",
            );
            $prevMoons[] = $mId;
        }
    }

    // Log decisions from turnResult
    if (!empty($turnResult['decisions']) && is_array($turnResult['decisions'])) {
        foreach ($turnResult['decisions'] as $d) {
            $action = $d['action'] ?? '';
            if (in_array($action, array('colonization_dispatch', 'logistics_dispatch', 'fob_deployment', 'moonshot_dispatch', 'moonshot_success', 'jump_gate_stage'))) {
                $logisticsAndExpansionLog[] = array(
                    'timestamp'     => $simNow,
                    'relative_time' => $relTimeStr,
                    'type'          => $action,
                    'action'        => $d['action'] ?? '',
                    'reason'        => $d['reason'] ?? '',
                    'metadata'      => $d['metadata'] ?? array(),
                );
            }
        }
    }

    // Log recruited units
    if (!empty($turnResult['recruited']) && is_array($turnResult['recruited'])) {
        foreach ($turnResult['recruited'] as $uId => $cnt) {
            $uName = $LNG['tech'][$uId] ?? "Unidad #{$uId}";
            $recruitmentLog[] = array(
                'timestamp'     => $simNow,
                'relative_time' => $relTimeStr,
                'unit_id'       => (int)$uId,
                'unit_name'     => $uName,
                'count'         => (int)$cnt,
                'category'      => ((int)$uId >= 400) ? 'defense' : 'fleet',
            );
        }
    }

    // 4. Hourly Snapshot Capture
    if ($simNow >= $nextHourlyMark) {
        $simHour = (int)round(($nextHourlyMark - $simStartTime) / 3600);

        $totM = 0.0; $totC = 0.0; $totD = 0.0; $maxMine = 0;
        $totFleetMse = 0.0; $totDefMse = 0.0;

        foreach ($curPlanets as $cp) {
            $totM += (float)($cp['metal_perhour'] ?? 0);
            $totC += (float)($cp['crystal_perhour'] ?? 0);
            $totD += (float)($cp['deuterium_perhour'] ?? 0);
            $maxMine = max($maxMine, (int)($cp[$resource[1]] ?? 0));

            // Fleet MSE
            if (is_array($reslist['fleet'])) {
                foreach ($reslist['fleet'] as $fId) {
                    $qty = (int)($cp[$resource[$fId]] ?? 0);
                    if ($qty > 0 && isset($pricelist[$fId])) {
                        $costM = (float)($pricelist[$fId]['cost'][901] ?? 0);
                        $costC = (float)($pricelist[$fId]['cost'][902] ?? 0);
                        $costD = (float)($pricelist[$fId]['cost'][903] ?? 0);
                        $totFleetMse += ($costM + ($costC * 2.0) + ($costD * 4.0)) * $qty;
                    }
                }
            }

            // Defense MSE
            if (is_array($reslist['defense'])) {
                foreach ($reslist['defense'] as $dfId) {
                    $qty = (int)($cp[$resource[$dfId]] ?? 0);
                    if ($qty > 0 && isset($pricelist[$dfId])) {
                        $costM = (float)($pricelist[$dfId]['cost'][901] ?? 0);
                        $costC = (float)($pricelist[$dfId]['cost'][902] ?? 0);
                        $costD = (float)($pricelist[$dfId]['cost'][903] ?? 0);
                        $totDefMse += ($costM + ($costC * 2.0) + ($costD * 4.0)) * $qty;
                    }
                }
            }
        }

        $totTechLevels = 0;
        if (is_array($reslist['tech'])) {
            foreach ($reslist['tech'] as $tId) {
                $col = $resource[$tId] ?? '';
                if (!empty($col)) {
                    $totTechLevels += (int)($curUser[$col] ?? 0);
                }
            }
        }

        $hourlySnapshots[] = array(
            'sim_hour'             => $simHour,
            'relative_time'        => formatRelativeTime($nextHourlyMark, $simStartTime),
            'planets_count'        => count($curPlanetIds),
            'moons_count'          => count($curMoonIds),
            'max_metal_mine_level' => $maxMine,
            'production_per_hour'  => array(
                'metal'     => round($totM, 2),
                'crystal'   => round($totC, 2),
                'deuterium' => round($totD, 2),
                'mse'       => round($totM + ($totC * 2.0) + ($totD * 4.0), 2),
            ),
            'total_fleet_mse'      => round($totFleetMse, 2),
            'total_defense_mse'    => round($totDefMse, 2),
            'total_tech_levels'    => $totTechLevels,
        );

        $nextHourlyMark += 3600;
    }

    if ($turnsCount % 25 === 0 && $inTransaction) {
        $db->commit();
        $inTransaction = false;
    }

    // 5. Calculate Next Discrete Jump ($nextSimTime)
    $minBuildEnd = PHP_INT_MAX;
    $hasActiveHangar = false;

    foreach ($curPlanets as $p) {
        if (!empty($p['b_building']) && (int)$p['b_building'] > $simNow) {
            $minBuildEnd = min($minBuildEnd, (int)$p['b_building']);
        }
        if (!empty($p['b_building_id'])) {
            $q = @unserialize($p['b_building_id']);
            if (is_array($q) && !empty($q[0][3]) && (int)$q[0][3] > $simNow) {
                $minBuildEnd = min($minBuildEnd, (int)$q[0][3]);
            }
        }
        if (!empty($p['b_hangar_id'])) {
            $hasActiveHangar = true;
        }
    }

    $minTechEnd = PHP_INT_MAX;
    if (!empty($curUser['b_tech']) && (int)$curUser['b_tech'] > $simNow) {
        $minTechEnd = (int)$curUser['b_tech'];
    }

    $minFleetTime = PHP_INT_MAX;
    $fleetRow = $db->selectSingle(
        "SELECT MIN(
            CASE 
              WHEN fleet_mess = 0 THEN fleet_start_time
              WHEN fleet_mess = 2 THEN fleet_end_stay
              ELSE fleet_end_time
            END
         ) as min_time 
         FROM %%FLEETS%% 
         WHERE fleet_owner = :bid 
           AND (
             (fleet_mess = 0 AND fleet_start_time > :now0) OR 
             (fleet_mess = 2 AND fleet_end_stay > :now2) OR 
             (fleet_mess = 1 AND fleet_end_time > :now1)
           );",
        array(
            ':bid'  => $simBotId,
            ':now0' => $simNow,
            ':now2' => $simNow,
            ':now1' => $simNow,
        )
    );
    if (!empty($fleetRow['min_time'])) {
        $minFleetTime = (int)$fleetRow['min_time'];
    }

    $minTaskTime = PHP_INT_MAX;
    $taskRow = $db->selectSingle(
        "SELECT MIN(scheduled_at) as min_sched FROM %%BOT_TASKS%% WHERE bot_id = :bid AND status = 'pending' AND scheduled_at > :now;",
        array(':bid' => $simBotId, ':now' => $simNow)
    );
    if (!empty($taskRow['min_sched'])) {
        $minTaskTime = (int)$taskRow['min_sched'];
    }

    $elapsedHours = ($simNow - $simStartTime) / 3600.0;
    $dynamicMaxStep = ($elapsedHours < 1.0) ? min(45, $maxStep) : ($hasActiveHangar ? min(45, $maxStep) : $maxStep);

    $nextEvent = min($minBuildEnd, $minTechEnd, $minFleetTime, $minTaskTime, $simNow + $dynamicMaxStep);
    $simNow = min($realEndTime, max($simNow + 10, $nextEvent));
}

if ($inTransaction) {
    $db->commit();
    $inTransaction = false;
}

// Final closing step exactly at $realEndTime
BotClock::set($realEndTime);
processSimulatedBotFleets($simBotId, $realEndTime);
$botRow = $db->selectSingle("SELECT * FROM %%BOTS%% WHERE bot_id = :bid;", array(':bid' => $simBotId));
if (!empty($botRow)) {
    BotKernel::processTurn($botRow);
}

$realExecutionEnd = microtime(true);
$durationMs = round(($realExecutionEnd - $realExecutionStart) * 1000.0, 2);

// --- 2.5 GENERATE FORENSIC EXTENSIVE JSON REPORT ---
$finalUser    = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = :bid;", array(':bid' => $simBotId));
$finalPlanets = $db->select("SELECT * FROM %%PLANETS%% WHERE id_owner = :bid ORDER BY planet_type ASC, id ASC;", array(':bid' => $simBotId));
$finalBot     = $db->selectSingle("SELECT * FROM %%BOTS%% WHERE bot_id = :bid;", array(':bid' => $simBotId));

$finalRaceId = (int)($finalUser['race'] ?? 0);
$raceNameMap = array(
    1501 => 'Fotorianos',
    1502 => 'Sinuria',
    1503 => 'Protoss / Ethereal',
    1504 => 'Kratonianos',
);
$finalRaceName = $raceNameMap[$finalRaceId] ?? ($LNG['tech'][$finalRaceId] ?? "Raza #{$finalRaceId}");

// Empire totals
$totM = 0.0; $totC = 0.0; $totD = 0.0;
$physM = 0.0; $physC = 0.0; $physD = 0.0;
$planetsCount = 0; $moonsCount = 0;

foreach ($finalPlanets as $fp) {
    if ((int)$fp['planet_type'] === 3) {
        $moonsCount++;
    } else {
        $planetsCount++;
    }
    $totM += (float)($fp['metal_perhour'] ?? 0);
    $totC += (float)($fp['crystal_perhour'] ?? 0);
    $totD += (float)($fp['deuterium_perhour'] ?? 0);

    $physM += (float)($fp['metal'] ?? 0);
    $physC += (float)($fp['crystal'] ?? 0);
    $physD += (float)($fp['deuterium'] ?? 0);
}

// Decode virtual budgets state
$botCtx = new BotContext($finalBot, $finalUser);
$budgetMgr = new BotBudgetManager($botCtx);
$budgetCredits = $budgetMgr->getCredits();

// Research state
$finalResearchLevels = array();
if (is_array($reslist['tech'])) {
    foreach ($reslist['tech'] as $tId) {
        $col = $resource[$tId] ?? '';
        $finalResearchLevels[] = array(
            'id'     => $tId,
            'column' => $col,
            'name'   => $LNG['tech'][$tId] ?? "Tech #{$tId}",
            'level'  => !empty($col) ? (int)($finalUser[$col] ?? 0) : 0,
        );
    }
}

// Empire Fleet and Defense Totals
$totalFleetByType = array();
if (is_array($reslist['fleet'])) {
    foreach ($reslist['fleet'] as $fId) {
        $col = $resource[$fId] ?? '';
        if (empty($col)) continue;
        $stat = 0;
        $queued = 0;

        foreach ($finalPlanets as $fp) {
            $stat += (int)($fp[$col] ?? 0);
            if (!empty($fp['b_hangar_id'])) {
                $qArr = explode(';', trim($fp['b_hangar_id'], ';'));
                foreach ($qArr as $item) {
                    $p = explode(',', $item);
                    if (isset($p[0], $p[1]) && (int)$p[0] === $fId) {
                        $queued += (int)$p[1];
                    }
                }
            }
        }

        // In flight
        $inFlight = 0;
        $activeFleets = $db->select("SELECT fleet_array FROM %%FLEETS%% WHERE fleet_owner = :bid;", array(':bid' => $simBotId));
        if (is_array($activeFleets)) {
            foreach ($activeFleets as $af) {
                $fArr = explode(';', trim($af['fleet_array'], ';'));
                foreach ($fArr as $item) {
                    $p = explode(',', $item);
                    if (isset($p[0], $p[1]) && (int)$p[0] === $fId) {
                        $inFlight += (int)$p[1];
                    }
                }
            }
        }

        $tot = $stat + $inFlight + $queued;
        if ($tot > 0) {
            $totalFleetByType[] = array(
                'unit_id'   => $fId,
                'unit_name' => $LNG['tech'][$fId] ?? "Nave #{$fId}",
                'stationed' => $stat,
                'in_flight' => $inFlight,
                'queued'    => $queued,
                'total'     => $tot,
            );
        }
    }
}

$totalDefenseByType = array();
if (is_array($reslist['defense'])) {
    foreach ($reslist['defense'] as $dfId) {
        $col = $resource[$dfId] ?? '';
        if (empty($col)) continue;
        $stat = 0;
        $queued = 0;

        foreach ($finalPlanets as $fp) {
            $stat += (int)($fp[$col] ?? 0);
            if (!empty($fp['b_hangar_id'])) {
                $qArr = explode(';', trim($fp['b_hangar_id'], ';'));
                foreach ($qArr as $item) {
                    $p = explode(',', $item);
                    if (isset($p[0], $p[1]) && (int)$p[0] === $dfId) {
                        $queued += (int)$p[1];
                    }
                }
            }
        }

        $tot = $stat + $queued;
        if ($tot > 0) {
            $totalDefenseByType[] = array(
                'unit_id'   => $dfId,
                'unit_name' => $LNG['tech'][$dfId] ?? "Defensa #{$dfId}",
                'stationed' => $stat,
                'queued'    => $queued,
                'total'     => $tot,
            );
        }
    }
}

// Detailed Planets & Moons
$planetsAndMoonsDetailed = array();
foreach ($finalPlanets as $fp) {
    $pId = (int)$fp['id'];
    $isMoon = ((int)$fp['planet_type'] === 3);

    $bLevels = array();
    if (is_array($reslist['build'])) {
        foreach ($reslist['build'] as $bId) {
            $col = $resource[$bId] ?? '';
            if (empty($col)) continue;
            $lvl = (int)($fp[$col] ?? 0);
            if ($lvl > 0) {
                $bLevels[] = array(
                    'id'    => $bId,
                    'name'  => $LNG['tech'][$bId] ?? "Edificio #{$bId}",
                    'level' => $lvl,
                );
            }
        }
    }

    $statFleet = array();
    if (is_array($reslist['fleet'])) {
        foreach ($reslist['fleet'] as $fId) {
            $col = $resource[$fId] ?? '';
            if (!empty($col) && (int)($fp[$col] ?? 0) > 0) {
                $statFleet[] = array(
                    'id'    => $fId,
                    'name'  => $LNG['tech'][$fId] ?? "Nave #{$fId}",
                    'count' => (int)$fp[$col],
                );
            }
        }
    }

    $statDef = array();
    if (is_array($reslist['defense'])) {
        foreach ($reslist['defense'] as $dfId) {
            $col = $resource[$dfId] ?? '';
            if (!empty($col) && (int)($fp[$col] ?? 0) > 0) {
                $statDef[] = array(
                    'id'    => $dfId,
                    'name'  => $LNG['tech'][$dfId] ?? "Defensa #{$dfId}",
                    'count' => (int)$fp[$col],
                );
            }
        }
    }

    // Chronological log for this planet
    $planetChronology = array();
    foreach ($buildingCompletionLog as $bc) {
        if ($bc['planet_id'] === $pId) {
            $planetChronology[] = $bc;
        }
    }

    $planetsAndMoonsDetailed[] = array(
        'id'                  => $pId,
        'name'                => $fp['name'],
        'coordinates'         => sprintf("[%d:%d:%d]", $fp['galaxy'], $fp['system'], $fp['planet']),
        'type'                => $isMoon ? 'moon' : 'planet',
        'fields_used'         => (int)($fp['field_current'] ?? 0),
        'fields_max'          => (int)($fp['field_max'] ?? 0),
        'energy'              => array(
            'current' => (float)($fp['energy'] ?? 0),
            'max'     => (float)($fp['energy_max'] ?? 0),
        ),
        'production_per_hour' => array(
            'metal'     => (float)($fp['metal_perhour'] ?? 0),
            'crystal'   => (float)($fp['crystal_perhour'] ?? 0),
            'deuterium' => (float)($fp['deuterium_perhour'] ?? 0),
        ),
        'physical_stock'      => array(
            'metal'     => round((float)($fp['metal'] ?? 0), 2),
            'crystal'   => round((float)($fp['crystal'] ?? 0), 2),
            'deuterium' => round((float)($fp['deuterium'] ?? 0), 2),
        ),
        'virtual_budgets'     => $budgetCredits['planets'][$pId] ?? array(),
        'buildings_levels'    => $bLevels,
        'stationed_fleet'     => $statFleet,
        'stationed_defense'   => $statDef,
        'active_queues'       => array(
            'building' => !empty($fp['b_building_id']) ? @unserialize($fp['b_building_id']) : array(),
            'hangar'   => !empty($fp['b_hangar_id']) ? $fp['b_hangar_id'] : '',
        ),
        'chronological_log'   => $planetChronology,
    );
}

// Filter logistics timeline
$timelineColos = array();
$timelineConvoys = array();
$timelineDeploys = array();
$timelineMoonshots = array();

foreach ($logisticsAndExpansionLog as $item) {
    $t = $item['type'] ?? '';
    if ($t === 'colonization_dispatch' || $t === 'colony_founded') {
        $timelineColos[] = $item;
    } elseif ($t === 'logistics_dispatch') {
        $timelineConvoys[] = $item;
    } elseif ($t === 'fob_deployment' || $t === 'jump_gate_stage') {
        $timelineDeploys[] = $item;
    } elseif (strpos($t, 'moon') !== false) {
        $timelineMoonshots[] = $item;
    }
}

$report = array(
    'metadata' => array(
        'hours'             => $hours,
        'difficulty'        => $difficulty,
        'personality'       => $personality,
        'race_id'           => $finalRaceId,
        'race_name'         => $finalRaceName,
        'turns_executed'    => $turnsCount,
        'execution_time_ms' => $durationMs,
        'sim_start_time'    => date('Y-m-d H:i:s', $simStartTime),
        'sim_end_time'      => date('Y-m-d H:i:s', $realEndTime),
        'real_start_time'   => date('Y-m-d H:i:s', (int)$realExecutionStart),
        'real_end_time'     => date('Y-m-d H:i:s', (int)$realExecutionEnd),
    ),
    'empire_summary' => array(
        'total_planets'            => $planetsCount,
        'total_moons'              => $moonsCount,
        'production_per_hour'      => array(
            'metal'     => round($totM, 2),
            'crystal'   => round($totC, 2),
            'deuterium' => round($totD, 2),
            'mse'       => round($totM + ($totC * 2.0) + ($totD * 4.0), 2),
        ),
        'total_physical_resources' => array(
            'metal'     => round($physM, 2),
            'crystal'   => round($physC, 2),
            'deuterium' => round($physD, 2),
        ),
        'final_virtual_budgets'    => array(
            'research' => $budgetCredits['research'] ?? array(),
            'planets'  => $budgetCredits['planets'] ?? array(),
        ),
        'assigned_doctrines'       => array(
            'race'       => $finalRaceName,
            'government' => (int)($finalUser['formgovernment'] ?? 0),
            'ethics'     => (int)($finalUser['ethics'] ?? 0),
        ),
        'fob_moon_id'              => (int)($finalBot['fob_planet_id'] ?? 0),
        'total_counters'           => array(
            'buildings_completed'  => count($buildingCompletionLog),
            'researches_completed' => count($researchCompletionLog),
            'recruitment_batches'  => count($recruitmentLog),
            'colonies_dispatched'  => count($timelineColos),
            'convoys_dispatched'   => count($timelineConvoys),
            'moonshots_attempted'  => count($timelineMoonshots),
        ),
    ),
    'research_state_and_history' => array(
        'final_levels'               => $finalResearchLevels,
        'active_tech_queue'          => !empty($finalUser['b_tech_id']) ? $finalUser['b_tech_id'] : null,
        'chronological_research_log' => $researchCompletionLog,
    ),
    'empire_fleet_and_defense_totals' => array(
        'total_fleet_by_type'   => $totalFleetByType,
        'total_defense_by_type' => $totalDefenseByType,
    ),
    'planets_and_moons_detailed' => $planetsAndMoonsDetailed,
    'logistics_and_expansion_timeline' => array(
        'colonizations'        => $timelineColos,
        'logistics_convoys'    => $timelineConvoys,
        'orbital_deployments'  => $timelineDeploys,
        'moonshots'            => $timelineMoonshots,
    ),
    'hourly_progression_curve' => $hourlySnapshots,
);

$jsonContent = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
file_put_contents($outputPath, $jsonContent);

echo "\n===============================================================================\n";
echo " SIMULATION REPORT GENERATED SUCCESSFULLY\n";
echo " Path: {$outputPath}\n";
echo " File Size: " . number_format(strlen($jsonContent)) . " bytes\n";
echo " Turns: {$turnsCount} | Elapsed Simulation: {$hours}h | Real Time: " . round($durationMs / 1000.0, 2) . "s\n";
echo " Colonies: {$planetsCount} | Moons: {$moonsCount} | Buildings Built: " . count($buildingCompletionLog) . "\n";
echo " Technologies Researched: " . count($researchCompletionLog) . " | Hourly MSE Prod: " . number_format($report['empire_summary']['production_per_hour']['mse']) . "\n";
echo "===============================================================================\n";

// --- 2.5 CLEANUP AND RESET ---
if ($keepBot === 0) {
    echo "Performing clean teardown of simulated bot #{$simBotId}...\n";
    $db->delete("DELETE FROM %%BOTS%% WHERE bot_id = :bid;", array(':bid' => $simBotId));
    $db->delete("DELETE FROM %%BOT_TASKS%% WHERE bot_id = :bid;", array(':bid' => $simBotId));
    $db->delete("DELETE FROM %%BOT_DECISIONS%% WHERE bot_id = :bid;", array(':bid' => $simBotId));
    $db->delete("DELETE FROM %%BOT_LOGS%% WHERE bot_id = :bid;", array(':bid' => $simBotId));
    $db->delete("DELETE FROM %%BOT_INTEL%% WHERE bot_id = :bid;", array(':bid' => $simBotId));
    $db->delete("DELETE FROM %%FLEETS%% WHERE fleet_owner = :bid;", array(':bid' => $simBotId));
    $db->delete("DELETE FROM %%FLEETS_EVENT%% WHERE fleetID NOT IN (SELECT fleet_id FROM %%FLEETS%%);");
    $db->delete("DELETE FROM %%MESSAGES%% WHERE message_sender = :bid OR message_owner = :bid;", array(':bid' => $simBotId));
    $db->delete("DELETE FROM %%STATPOINTS%% WHERE id_owner = :bid;", array(':bid' => $simBotId));
    $db->delete("DELETE FROM %%PLANETS%% WHERE id_owner = :bid;", array(':bid' => $simBotId));
    $db->delete("DELETE FROM %%USERS%% WHERE id = :bid;", array(':bid' => $simBotId));
    echo "Teardown complete. Zero orphaned rows.\n";
} else {
    echo "Bot #{$simBotId} preserved in database (--keep-bot=1).\n";
}

BotClock::reset();
echo "BotClock reset to system time. Simulator finished.\n";

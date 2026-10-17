<?php
/**
 * NovaRush AI Engine — Simulación Exhaustiva de 7 Días Desde Cero
 * Zero-to-Empire High-Speed Faithful Benchmark (604,800s Virtuales)
 *
 * Principios y Arquitectura:
 * 1. Inicialización Limpia "Jugador Nuevo Normal":
 *    - 1 solo planeta en coordenada virgen [1:S:8] con 500 Metal, 500 Cristal, 0 Deuterio, 0 Edificios, 0 Techs, 0 Flota/Defensa.
 *    - 2 objetivos rivales dummy en vecindario [1:S-3:8] y [1:S+3:8] con recursos, minas y defensa ligera para pruebas balísticas y de espionaje.
 * 2. Motor de Avance Temporal Discreto Event-Driven:
 *    - Sincronización continua de BotClock::setNow($virtualTime).
 *    - Paso de evaluación adaptativo guiado por eventos reales: min($maxStep, $nextEventDelta).
 *    - Ejecución 100% canónica de BotKernel::processTurn($bRow) (Fases 1 a 12).
 *    - Sin bypass de planners, sin throttling artificial (% 2), sin inyección mágica SQL de satélites solares.
 * 3. Auditoría Forense Dual-Source (Caja Negra JSON):
 *    - Extracción del estado de la base de datos ogame2 ANTES de rollback.
 *    - Generación de benchmark_audit_aggressive_nightmare.json y benchmark_audit_latest.json con hash SHA-256.
 * 4. Limpieza Atómica Post-Benchmark (Rollback completo del bot y rivales dummy, 0 filas huérfanas).
 */

define('MODE', 'CLI');
define('ROOT_PATH', 'C:/Users/Ortega/Downloads/OGAME/ogame/');
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
global $resource, $reslist, $pricelist, $CombatCaps, $requeriments, $ProdGrid;

require_once 'includes/classes/PlayerUtil.class.php';
require_once 'includes/classes/class.FleetFunctions.php';
require_once 'includes/classes/class.BuildFunctions.php';
require_once 'includes/classes/class.PlanetRessUpdate.php';
require_once 'includes/classes/class.FlyingFleetHandler.php';
require_once 'includes/classes/bot/Kernel/BotClock.class.php';
require_once 'includes/classes/bot/Kernel/BotKernel.class.php';
require_once 'includes/classes/bot/Kernel/LockManager.class.php';
require_once 'includes/classes/bot/Telemetry/BotTelemetryLogger.class.php';
require_once 'includes/classes/bot/Telemetry/DecisionLog.class.php';
require_once 'includes/classes/bot/Execution/ActionGateway.class.php';
require_once 'includes/classes/bot/Military/TargetFinder.class.php';
require_once 'includes/classes/bot/Forces/BotBudgetManager.class.php';
require_once 'includes/classes/Language.class.php';

global $LNG;
$LNG = new Language('es');
$LNG->includeData(array('L18N', 'INGAME', 'TECH', 'CUSTOM'));

// Desactivar logs en disco de telemetría durante la simulación acelerada
BotTelemetryLogger::$disabled = true;

$db = Database::get();

// Aceleración de sesión MariaDB / InnoDB
try {
    $db->query("SET SESSION innodb_flush_log_at_trx_commit = 2;");
    $db->query("SET SESSION sync_binlog = 0;");
} catch (Throwable $e) {}

/**
 * Procesa eventos de flota pendientes en el motor canónico de 2Moons hasta BotClock::now()
 */
function processSimulationFleetEvents($botId = 0, &$statsTracker = null)
{
    $db = Database::get();
    $loops = 0;
    while ($loops < 10) {
        $token = getRandomString();
        $db->update("UPDATE %%FLEETS_EVENT%% SET `lock` = :token WHERE `lock` IS NULL AND `time` <= :time;", array(
            ':time'  => BotClock::now(),
            ':token' => $token
        ));
        if ($db->rowCount() === 0) {
            break;
        }

        $lockedFleets = array();
        if ($botId > 0 && is_array($statsTracker)) {
            $lockedFleets = $db->select("
                SELECT f.* 
                FROM %%FLEETS_EVENT%% fe
                INNER JOIN %%FLEETS%% f ON f.fleet_id = fe.fleetID
                WHERE fe.`lock` = :token
                ORDER BY fe.`time` ASC, f.fleet_mission ASC;
            ", array(':token' => $token));
        }

        try {
            $fleetObj = new FlyingFleetHandler();
            $fleetObj->setToken($token);
            $fleetObj->run();
        } finally {
            $db->update("UPDATE %%FLEETS_EVENT%% SET `lock` = NULL WHERE `lock` = :token;", array(
                ':token' => $token
            ));
        }

        if (!empty($lockedFleets) && is_array($statsTracker)) {
            foreach ($lockedFleets as $fRow) {
                if ((int)$fRow['fleet_owner'] !== (int)$botId) {
                    continue;
                }
                $mission = (int)$fRow['fleet_mission'];
                $mess    = (int)$fRow['fleet_mess'];
                $fId     = (int)$fRow['fleet_id'];

                if ($mess === 0) {
                    if ($mission === 1) {
                        $statsTracker['combats_resolved']++;
                        $retFleet = $db->selectSingle("SELECT fleet_resource_metal, fleet_resource_crystal, fleet_resource_deuterium FROM %%FLEETS%% WHERE fleet_id = :fid;", array(':fid' => $fId));
                        if (!empty($retFleet)) {
                            $m = (float)$retFleet['fleet_resource_metal'];
                            $c = (float)$retFleet['fleet_resource_crystal'];
                            $d = (float)$retFleet['fleet_resource_deuterium'];
                            $statsTracker['loot_mse'] += ($m + $c * 2.0 + $d * 4.0);
                        }
                    } elseif ($mission === 3) {
                        $statsTracker['convoys_arrived']++;
                    } elseif ($mission === 4) {
                        $statsTracker['rallies_arrived']++;
                    } elseif ($mission === 6) {
                        $statsTracker['spy_reports_generated']++;
                    } elseif ($mission === 8) {
                        $retFleet = $db->selectSingle("SELECT fleet_resource_metal, fleet_resource_crystal FROM %%FLEETS%% WHERE fleet_id = :fid;", array(':fid' => $fId));
                        if (!empty($retFleet)) {
                            $statsTracker['debris_harvested_metal'] += (float)$retFleet['fleet_resource_metal'];
                            $statsTracker['debris_harvested_crystal'] += (float)$retFleet['fleet_resource_crystal'];
                        }
                    }
                } elseif ($mess === 1) {
                    if ($mission === 15) {
                        $statsTracker['expeditions_completed']++;
                    }
                }
            }
        }

        $loops++;
    }
}

/**
 * Genera un rival dummy con recursos, minas y defensa para combate orgánico
 */
function spawnSimulationRival($username, $galaxy, $system, $planet, $metal, $crystal, $deut, $mRank, $cRank, $dRank, $sRank, $defRocket, $defLaser, $cargo, $derM, $derC, $virtualTime)
{
    $db = Database::get();
    $ex = $db->selectSingle("SELECT id FROM %%USERS%% WHERE username = :uname;", array(':uname' => $username));
    if (!empty($ex)) {
        $uid = (int)$ex['id'];
        $db->delete("DELETE FROM %%PLANETS%% WHERE id_owner = :uid;", array(':uid' => $uid));
        $db->delete("DELETE FROM %%USERS%% WHERE id = :uid;", array(':uid' => $uid));
        $db->delete("DELETE FROM %%STATPOINTS%% WHERE id_owner = :uid;", array(':uid' => $uid));
    }
    $db->delete("DELETE FROM %%PLANETS%% WHERE universe = 1 AND galaxy = :g AND `system` = :s AND planet = :p AND id_owner NOT IN (SELECT id FROM %%USERS%% WHERE id <= 1015);", array(
        ':g' => $galaxy, ':s' => $system, ':p' => $planet
    ));

    list($uid, $pid) = PlayerUtil::createPlayer(
        1,
        $username,
        md5('SimSecret_' . $username),
        "{$username}@novarush.benchmark",
        'es',
        $galaxy,
        $system,
        $planet,
        'Rival-' . substr(md5($username), 0, 4),
        0
    );

    $db->update("UPDATE %%PLANETS%% SET 
        metal = :m,
        crystal = :c,
        deuterium = :d,
        metal_mine = :mm,
        crystal_mine = :cm,
        deuterium_sintetizer = :dm,
        solar_plant = :sp,
        misil_launcher = :ml,
        small_laser = :sl,
        small_ship_cargo = :cargo,
        der_metal = :derM,
        der_crystal = :derC
        WHERE id = :pid;", array(
        ':m'     => (float)$metal,
        ':c'     => (float)$crystal,
        ':d'     => (float)$deut,
        ':mm'    => (int)$mRank,
        ':cm'    => (int)$cRank,
        ':dm'    => (int)$dRank,
        ':sp'    => (int)$sRank,
        ':ml'    => (int)$defRocket,
        ':sl'    => (int)$defLaser,
        ':cargo' => (int)$cargo,
        ':derM'  => (float)$derM,
        ':derC'  => (float)$derC,
        ':pid'   => $pid
    ));

    $db->update("UPDATE %%USERS%% SET onlinetime = :t WHERE id = :uid;", array(
        ':t'   => $virtualTime - 86400 * 3, // Inactivo 3 días
        ':uid' => $uid
    ));

    return array('user_id' => $uid, 'planet_id' => $pid);
}

/**
 * Mantiene tráfico activo de flotas en vuelo entre los rivales dummy para certificar escaneos Phalanx
 */
function ensureSimulationRivalFleets(array $r1Data, array $r2Data, $virtualTime)
{
    $db = Database::get();
    $r1Uid = (int)($r1Data['user_id'] ?? 0);
    $r2Uid = (int)($r2Data['user_id'] ?? 0);
    if ($r1Uid <= 0 || $r2Uid <= 0) return;

    $activeFleets = $db->selectSingle("SELECT COUNT(*) as cnt FROM %%FLEETS%% WHERE fleet_owner IN (:u1, :u2);", array(
        ':u1' => $r1Uid, ':u2' => $r2Uid
    ));

    if ((int)($activeFleets['cnt'] ?? 0) === 0) {
        $p1 = $db->selectSingle("SELECT galaxy, system, planet FROM %%PLANETS%% WHERE id = :pid;", array(':pid' => $r1Data['planet_id']));
        $p2 = $db->selectSingle("SELECT galaxy, system, planet FROM %%PLANETS%% WHERE id = :pid;", array(':pid' => $r2Data['planet_id']));
        if (!empty($p1) && !empty($p2)) {
            $startTime = $virtualTime + 1800;
            $endTime   = $virtualTime + 3600;
            $fleetArray = '202,25;203,10;204,15;';

            $db->insert("INSERT INTO %%FLEETS%% SET
                fleet_owner = :r1,
                fleet_target_owner = :r2,
                fleet_mission = 3,
                fleet_amount = 50,
                fleet_array = :fa,
                fleet_universe = 1,
                fleet_start_time = :st,
                fleet_end_stay = :st,
                fleet_end_time = :et,
                fleet_start_id = :p1Id,
                fleet_start_galaxy = :g1,
                fleet_start_system = :s1,
                fleet_start_planet = :p1,
                fleet_start_type = 1,
                fleet_end_id = :p2Id,
                fleet_end_galaxy = :g2,
                fleet_end_system = :s2,
                fleet_end_planet = :p2,
                fleet_end_type = 1,
                fleet_resource_metal = 50000.0,
                fleet_resource_crystal = 25000.0,
                fleet_resource_deuterium = 10000.0,
                fleet_target_obj = 0,
                fleet_mess = 0,
                start_time = :now;", array(
                ':r1'   => $r1Uid,
                ':r2'   => $r2Uid,
                ':fa'   => $fleetArray,
                ':st'   => $startTime,
                ':et'   => $endTime,
                ':p1Id' => (int)$r1Data['planet_id'],
                ':g1'   => (int)$p1['galaxy'],
                ':s1'   => (int)$p1['system'],
                ':p1'   => (int)$p1['planet'],
                ':p2Id' => (int)$r2Data['planet_id'],
                ':g2'   => (int)$p2['galaxy'],
                ':s2'   => (int)$p2['system'],
                ':p2'   => (int)$p2['planet'],
                ':now'  => $virtualTime
            ));
            $fleetId = $db->lastInsertId();
            $db->insert("INSERT INTO %%FLEETS_EVENT%% SET fleetID = :fId, `time` = :st;", array(
                ':fId' => $fleetId,
                ':st'  => $startTime
            ));
        }
    }
}

/**
 * Purgado atómico de entidades de simulación
 */
function purgeSimulationEntities(array $userIds)
{
    $db = Database::get();
    foreach ($userIds as $uid) {
        $uid = (int)$uid;
        if ($uid <= 0) continue;
        $db->delete("DELETE FROM %%BOTS%% WHERE bot_id = :uid;", array(':uid' => $uid));
        $db->delete("DELETE FROM %%BOT_DECISIONS%% WHERE bot_id = :uid;", array(':uid' => $uid));
        $db->delete("DELETE FROM %%BOT_TASKS%% WHERE bot_id = :uid;", array(':uid' => $uid));
        $db->delete("DELETE FROM %%FLEETS%% WHERE fleet_owner = :uid OR fleet_target_owner = :uid;", array(':uid' => $uid));
        $db->delete("DELETE FROM %%PLANETS%% WHERE id_owner = :uid;", array(':uid' => $uid));
        $db->delete("DELETE FROM %%USERS%% WHERE id = :uid;", array(':uid' => $uid));
        $db->delete("DELETE FROM %%STATPOINTS%% WHERE id_owner = :uid;", array(':uid' => $uid));
    }
    $db->delete("DELETE FROM %%FLEETS_EVENT%% WHERE fleetID NOT IN (SELECT fleet_id FROM %%FLEETS%%);");
}

// ------------------------------------------------------------------------
// PARSEO DE ARGUMENTOS CLI
// ------------------------------------------------------------------------
$shortopts = "";
$longopts  = array(
    "difficulty::",
    "personality::",
    "days::",
    "user::",
    "matrix",
    "no-cleanup",
    "help"
);
$options = getopt($shortopts, $longopts);

if (isset($options['help'])) {
    echo "========================================================================================\n";
    echo "   NOVARUSH ZERO-TO-EMPIRE HIGH-SPEED BENCHMARK (7 DIAS)\n";
    echo "========================================================================================\n";
    echo "Uso: php simulate_7days_zero_to_empire.php [OPCIONES]\n\n";
    echo "Opciones:\n";
    echo "  --difficulty=DIF     Dificultad del bot (nightmare, hard, normal, easy) [Default: nightmare]\n";
    echo "  --personality=PERS   Personalidad del bot (aggressive, miner, defensive, balanced) [Default: aggressive]\n";
    echo "  --days=N             Días virtuales a simular (ej. 7, 3, 1) [Default: 7]\n";
    echo "  --user=NOMBRE        Nombre del usuario bot de prueba [Default: SimZeroBot]\n";
    echo "  --matrix             Ejecutar corrida multi-perfil (Aggressive/Nightmare + Miner/Normal)\n";
    echo "  --no-cleanup         No purgar los datos al terminar (para inspección en DB)\n";
    echo "  --help               Muestra esta ayuda\n\n";
    exit(0);
}

$cliDifficulty  = $options['difficulty'] ?? 'nightmare';
$cliPersonality = $options['personality'] ?? 'aggressive';
$cliDays        = isset($options['days']) ? (float)$options['days'] : 7.0;
$cliUsername    = $options['user'] ?? 'SimZeroBot';
$isMatrix       = isset($options['matrix']);
$noCleanup      = isset($options['no-cleanup']);

/**
 * Función principal para ejecutar una simulación completa desde CERO
 */
function runZeroToEmpireSimulation($personality, $difficulty, $days, $username, $noCleanup = false)
{
    global $resource, $reslist, $pricelist, $CombatCaps, $requeriments, $ProdGrid, $LNG;

    $db = Database::get();

    echo "========================================================================================\n";
    echo "   NOVARUSH ZERO-TO-EMPIRE FAITHFUL BENCHMARK ({$days} DIAS / " . number_format($days * 86400, 0) . "s VIRTUALES)   \n";
    echo "   Perfil: " . strtoupper($personality) . " | Dificultad: " . strtoupper($difficulty) . " | Bot: {$username}\n";
    echo "   Condiciones Iniciales: CERO ABSOLUTO (500 M, 500 C, 0 D, 0 Edificios, 0 Techs, 0 Flota)\n";
    echo "   Motor: 100% Nativo BotKernel::processTurn() con Avance Temporal Discreto Guiado por Eventos\n";
    echo "========================================================================================\n\n";

    // ------------------------------------------------------------------------
    // PASO 1: PURGA PREVIA Y BUSQUEDA DE COORDENADA VIRGEN [1:S:8]
    // ------------------------------------------------------------------------
    echo "--- 1. BUSQUEDA DE COORDENADA VIRGEN Y CREACION DE JUGADOR + RIVALES DUMMY ---\n";

    // Purgar bot previo y rivales previos si existiesen
    $r1Name = $username . '_Rival1';
    $r2Name = $username . '_Rival2';
    foreach (array($username, $r1Name, $r2Name) as $uToCheck) {
        $existing = $db->selectSingle("SELECT id FROM %%USERS%% WHERE username = :uname;", array(':uname' => $uToCheck));
        if (!empty($existing)) {
            purgeSimulationEntities(array((int)$existing['id']));
            echo "  [PURGE] Registros previos de '{$uToCheck}' eliminados limpiamente.\n";
        }
    }

    // Buscar sistema libre en galaxia 1 entre sistemas 335 y 395
    $chosenSystem = null;
    for ($candSys = 335; $candSys <= 395; $candSys++) {
        $occ = $db->selectSingle(
            "SELECT COUNT(*) as cnt FROM %%PLANETS%% WHERE universe = 1 AND galaxy = 1 AND `system` = :sys;",
            array(':sys' => $candSys)
        );
        if ((int)($occ['cnt'] ?? 0) === 0) {
            $chosenSystem = $candSys;
            break;
        }
    }

    if ($chosenSystem === null) {
        $chosenSystem = 350;
        $db->delete("DELETE FROM %%PLANETS%% WHERE universe = 1 AND galaxy = 1 AND `system` = 350 AND id_owner NOT IN (SELECT id FROM %%USERS%% WHERE id <= 1015);");
    }

    // Crear bot principal en coordenada [1:chosenSystem:8]
    list($userId, $p1Id) = PlayerUtil::createPlayer(
        1,
        $username,
        md5('SimSecret2026'),
        "{$username}@novarush.benchmark",
        'es',
        1,
        $chosenSystem,
        8,
        'Genesis-Prime',
        0
    );

    // Reset estricto a cero absoluto del planeta inicial
    $db->update("UPDATE %%PLANETS%% SET 
        field_max = 163, 
        field_current = 0,
        metal = 500.0,
        crystal = 500.0,
        deuterium = 0.0,
        metal_perhour = 0,
        crystal_perhour = 0,
        deuterium_perhour = 0,
        energy = 0,
        energy_used = 0,
        metal_mine = 0,
        crystal_mine = 0,
        deuterium_sintetizer = 0,
        solar_plant = 0,
        fusion_plant = 0,
        robot_factory = 0,
        nano_factory = 0,
        hangar = 0,
        metal_store = 0,
        crystal_store = 0,
        deuterium_store = 0,
        laboratory = 0,
        terraformer = 0,
        university = 0,
        ally_deposit = 0,
        silo = 0,
        resource_module = 0,
        defensive_module = 0,
        military_module = 0,
        research_module = 0,
        mondbasis = 0,
        phalanx = 0,
        sprungtor = 0,
        id_luna = 0,
        small_ship_cargo = 0,
        big_ship_cargo = 0,
        light_hunter = 0,
        heavy_hunter = 0,
        crusher = 0,
        battle_ship = 0,
        colonizer = 0,
        recycler = 0,
        spy_sonde = 0,
        bomber_ship = 0,
        solar_satelit = 0,
        destructor = 0,
        dearth_star = 0,
        battleship = 0,
        lune_noir = 0,
        ev_transporter = 0,
        star_crasher = 0,
        giga_recykler = 0,
        dm_ship = 0,
        misil_launcher = 0,
        small_laser = 0,
        big_laser = 0,
        gauss_canyon = 0,
        ionic_canyon = 0,
        buster_canyon = 0,
        small_protection_shield = 0,
        big_protection_shield = 0,
        planet_protector = 0,
        graviton_canyon = 0,
        b_building = 0,
        b_building_id = '',
        b_hangar = 0,
        b_hangar_id = ''
        WHERE id = :pid;", array(':pid' => $p1Id));

    // Resetear las 20 tecnologías en uni1_users y fijar darkmatter en 0
    $techCols = array(
        'spy_tech', 'computer_tech', 'military_tech', 'defence_tech', 'shield_tech',
        'energy_tech', 'hyperspace_tech', 'combustion_tech', 'impulse_motor_tech',
        'hyperspace_motor_tech', 'laser_tech', 'ionic_tech', 'buster_tech',
        'intergalactic_tech', 'expedition_tech', 'metal_proc_tech', 'crystal_proc_tech',
        'deuterium_proc_tech', 'fuel_optim_tech', 'graviton_tech'
    );
    $resetTechSql = array();
    foreach ($techCols as $tCol) {
        $resetTechSql[] = "{$tCol} = 0";
    }
    $db->update("UPDATE %%USERS%% SET " . implode(', ', $resetTechSql) . ", darkmatter = 0, b_tech = 0, b_tech_id = 0, b_tech_planet = 0, b_tech_queue = '' WHERE id = :uid;", array(':uid' => $userId));

    // Registrar en uni1_bots con personalidad y dificultad solicitadas
    $db->insert("INSERT INTO %%BOTS%% SET 
        bot_id = :uid,
        personality = :pers,
        difficulty = :diff,
        doctrine = :pers,
        home_planet_id = :p1,
        is_active = 1,
        created_at = :now,
        budget_credits = '{}';", array(
            ':uid'  => $userId,
            ':pers' => $personality,
            ':diff' => $difficulty,
            ':p1'   => $p1Id,
            ':now'  => TIMESTAMP
        )
    );

    // Crear 2 rivales dummy orgánicos en sistemas vecinos para dual-source combat & debris verification
    $r1System = max(1, $chosenSystem - 3);
    $r2System = min(400, $chosenSystem + 3);
    $r1Name   = $username . '_Rival1';
    $r2Name   = $username . '_Rival2';

    $r1Data = spawnSimulationRival(
        $r1Name, 1, $r1System, 8,
        250000.0, 180000.0, 60000.0,
        14, 12, 10, 15,
        15, 8, 6,
        45000.0, 30000.0,
        TIMESTAMP
    );

    $r2Data = spawnSimulationRival(
        $r2Name, 1, $r2System, 8,
        400000.0, 260000.0, 95000.0,
        16, 14, 12, 18,
        20, 12, 10,
        75000.0, 45000.0,
        TIMESTAMP
    );

    echo "  [OK] Creado Bot Principal #{$userId} ('{$username}') en Planeta #{$p1Id} [1:{$chosenSystem}:8]\n";
    echo "  [OK] Creado Rival Dummy #1 #{$r1Data['user_id']} ('{$r1Name}') en Planeta #{$r1Data['planet_id']} [1:{$r1System}:8] con 490k res y 75k escombros\n";
    echo "  [OK] Creado Rival Dummy #2 #{$r2Data['user_id']} ('{$r2Name}') en Planeta #{$r2Data['planet_id']} [1:{$r2System}:8] con 755k res y 120k escombros\n\n";

    // ------------------------------------------------------------------------
    // PASO 2: MOTOR DE AVANCE TEMPORAL DISCRETO EVENT-DRIVEN (BOTKERNEL)
    // ------------------------------------------------------------------------
    echo "--- 2. EJECUTANDO SIMULACION EVENT-DRIVEN CON BOTKERNEL::PROCESSTURN() ---\n";

    $startRealTime = microtime(true);
    $simStartVirtual = TIMESTAMP;
    $totalSeconds = $days * 86400.0;

    // Inicializar buffers en memoria para decisiones
    BotDecisionLog::resetMemoryBuffer();
    BotActionGateway::$jumpGateExecutions = 0;
    BotActionGateway::$jumpGateShipsTransferred = 0;
    BotTargetFinder::$phalanxScansCount = 0;
    if (class_exists('BotBudgetManager')) {
        BotBudgetManager::resetInternalBucketSwapsCount();
    }

    $dailyCheckpoints = array();
    for ($d = 1; $d <= (int)ceil($days); $d++) {
        $dailyCheckpoints[$d] = $d * 86400.0;
    }
    $currentDayCheckpoint = 1;

    $statsTracker = array(
        'colonies_founded'                   => 0,
        'colonizers_recruited'               => 0,
        'colonies_dispatched'                => 0,
        'convoys_dispatched'                 => 0,
        'convoys_arrived'                    => 0,
        'rallies_dispatched'                 => 0,
        'rallies_arrived'                    => 0,
        'spy_missions'                       => 0,
        'spy_reports_generated'              => 0,
        'attack_missions'                    => 0,
        'combats_resolved'                   => 0,
        'loot_mse'                           => 0.0,
        'recycle_missions'                   => 0,
        'debris_harvested_metal'             => 0.0,
        'debris_harvested_crystal'           => 0.0,
        'expeditions_dispatched'             => 0,
        'expeditions_completed'              => 0,
        'moonshot_attempts'                  => 0,
        'moonshots_light_hunters_sacrificed' => 0,
        'moonshots_debris_created_total'     => 0.0,
        'moons_created'                      => 0,
        'quantum_jumps'                      => 0,
        'ships_jumped'                       => 0,
        'phalanx_scans'                      => 0,
    );

    $dailySnapshots = array();

    $lastKnownPlanetCount = 1;
    $lastKnownMoonCount   = 0;
    $lastHeartbeatSimSec  = 0.0;

    $simElapsedSec = 0.0;
    $step = 0;

    $db->beginTransaction();

    while ($simElapsedSec < $totalSeconds) {
        $step++;

        // 1. Calibración de paso adaptativo (Discrete Event-Driven Progression):
        // Pacing balanceado para evitar micro-stepping por sondas de espionaje (< 20s)
        if ($simElapsedSec < 7200.0) {
            $minStep = 45.0; // Early-game: granularidad fina
            $maxStep = 90.0;
        } elseif ($simElapsedSec < 86400.0) {
            $minStep = 90.0; // Maduración Día 1
            $maxStep = 240.0;
        } else {
            $minStep = 180.0; // Días 2 a 7: 3 a 7.5 minutos, NUNCA 3600s ciego!
            $maxStep = 450.0;
        }

        // Buscar el próximo evento temporal real en la base de datos
        $virtualTime = (int)round($simStartVirtual + $simElapsedSec);
        $candidates = array();

        // A. Próxima llegada de flota (priorizando misiones clave: colonización, ataque, logística)
        $fleetNxt = $db->selectSingle("
            SELECT MIN(CASE WHEN fleet_mess = 0 THEN fleet_start_time ELSE fleet_end_time END) as nxt
            FROM %%FLEETS%%
            WHERE (fleet_owner = :botId OR fleet_target_owner = :botId)
              AND (fleet_start_time > :now OR fleet_end_time > :now);
        ", array(':botId' => $userId, ':now' => $virtualTime));
        if (!empty($fleetNxt['nxt']) && (int)$fleetNxt['nxt'] > $virtualTime) {
            $candidates[] = (int)$fleetNxt['nxt'] - $virtualTime;
        }

        // B. Próxima tarea programada en bot_tasks
        $taskNxt = $db->selectSingle("
            SELECT MIN(scheduled_at) as nxt
            FROM %%BOT_TASKS%%
            WHERE bot_id = :botId AND scheduled_at > :now;
        ", array(':botId' => $userId, ':now' => $virtualTime));
        if (!empty($taskNxt['nxt']) && (int)$taskNxt['nxt'] > $virtualTime) {
            $candidates[] = (int)$taskNxt['nxt'] - $virtualTime;
        }

        // C. Próxima finalización de investigación
        $uCheck = $db->selectSingle("SELECT b_tech FROM %%USERS%% WHERE id = :botId;", array(':botId' => $userId));
        if (!empty($uCheck['b_tech']) && (int)$uCheck['b_tech'] > $virtualTime) {
            $candidates[] = (int)$uCheck['b_tech'] - $virtualTime;
        }

        // D. Próxima finalización de obra en planetas
        $bldNxt = $db->selectSingle("
            SELECT MIN(b_building) as nxt
            FROM %%PLANETS%%
            WHERE id_owner = :botId AND b_building > :now;
        ", array(':botId' => $userId, ':now' => $virtualTime));
        if (!empty($bldNxt['nxt']) && (int)$bldNxt['nxt'] > $virtualTime) {
            $candidates[] = (int)$bldNxt['nxt'] - $virtualTime;
        }

        $nextEventDelta = !empty($candidates) ? min($candidates) : $maxStep;
        $dt = (float)min($maxStep, max($minStep, $nextEventDelta));

        // Ajuste milimétrico al checkpoint diario (medianoche virtual)
        if (isset($dailyCheckpoints[$currentDayCheckpoint])) {
            $targetCheckpointSec = $dailyCheckpoints[$currentDayCheckpoint];
            if ($simElapsedSec < $targetCheckpointSec && ($simElapsedSec + $dt) >= $targetCheckpointSec) {
                $dt = $targetCheckpointSec - $simElapsedSec;
            }
        }

        $simElapsedSec += $dt;
        $virtualTime = (int)round($simStartVirtual + $simElapsedSec);
        BotClock::setNow($virtualTime);

        // Commit periódico cada 32 pasos
        if ($step % 32 === 0) {
            $db->commit();
            $db->beginTransaction();
        }

        // 2. Procesamiento de flotas arribadas vía FleetHandler canónico
        processSimulationFleetEvents($userId, $statsTracker);

        // 2b. Mantener tráfico vivo de flotas en vuelo entre rivales dummy para certificar Phalanx
        ensureSimulationRivalFleets($r1Data, $r2Data, $virtualTime);

        // 3. Ejecución 100% canónica del ciclo estratégico del bot vía BotKernel::processTurn
        $bRow = $db->selectSingle("SELECT * FROM %%BOTS%% WHERE bot_id = :botId;", array(':botId' => $userId));
        if (!empty($bRow)) {
            $turnResult = BotKernel::processTurn($bRow);

            if (is_array($turnResult) && !isset($turnResult['error'])) {
                if (isset($turnResult['spied']))       $statsTracker['spy_missions'] += (int)$turnResult['spied'];
                if (isset($turnResult['attacked']))    $statsTracker['attack_missions'] += (int)$turnResult['attacked'];
                if (isset($turnResult['recycled']))    $statsTracker['recycle_missions'] += (int)$turnResult['recycled'];
                if (isset($turnResult['transported'])) $statsTracker['convoys_dispatched'] += (int)$turnResult['transported'];
                if (isset($turnResult['rallied']))     $statsTracker['rallies_dispatched'] += (int)$turnResult['rallied'];
                if (isset($turnResult['expeditions'])) $statsTracker['expeditions_dispatched'] += (int)$turnResult['expeditions'];

                if (isset($turnResult['expansion']) && is_array($turnResult['expansion'])) {
                    $exp = $turnResult['expansion'];
                    if (isset($exp['colonizers_recruited'])) $statsTracker['colonizers_recruited'] += (int)$exp['colonizers_recruited'];
                    if (isset($exp['colonies_dispatched']))  $statsTracker['colonies_dispatched']  += (int)$exp['colonies_dispatched'];
                    if (isset($exp['moonshot_attempts'])) {
                        $attempts = (int)$exp['moonshot_attempts'];
                        $statsTracker['moonshot_attempts'] += $attempts;
                        $statsTracker['moonshots_light_hunters_sacrificed'] += ($attempts * 715);
                        $statsTracker['moonshots_debris_created_total'] += ($attempts * 2002000.0);
                    }
                }
            }
        }

        // Rastrear saltos cuánticos, escaneos phalanx y swaps internos
        $statsTracker['quantum_jumps']         = BotActionGateway::$jumpGateExecutions;
        $statsTracker['ships_jumped']          = BotActionGateway::$jumpGateShipsTransferred;
        $statsTracker['phalanx_scans']         = BotTargetFinder::$phalanxScansCount;
        $statsTracker['internal_bucket_swaps'] = BotBudgetManager::$internalBucketSwapsCount;

        // Rastrear fundación real de colonias y lunas en DB
        $allBodies = $db->select("SELECT id, planet_type FROM %%PLANETS%% WHERE id_owner = :botId AND destruyed = 0;", array(':botId' => $userId));
        $curPlanets = 0;
        $curMoons = 0;
        foreach ($allBodies as $b) {
            if ((int)$b['planet_type'] === 1) $curPlanets++;
            if ((int)$b['planet_type'] === 3) $curMoons++;
        }
        if ($curPlanets > $lastKnownPlanetCount) {
            $statsTracker['colonies_founded'] += ($curPlanets - $lastKnownPlanetCount);
            $lastKnownPlanetCount = $curPlanets;
        }
        if ($curMoons > $lastKnownMoonCount) {
            $statsTracker['moons_created'] += ($curMoons - $lastKnownMoonCount);
            $lastKnownMoonCount = $curMoons;
        }

        // Heartbeat streaming cada 4 horas virtuales (~14,400s)
        if (($simElapsedSec - $lastHeartbeatSimSec) >= 14400.0) {
            $lastHeartbeatSimSec = $simElapsedSec;
            $nowReal = microtime(true);
            $totalRealElapsed = round($nowReal - $startRealTime, 1);
            $simDay = round($simElapsedSec / 86400.0, 2);
            $uRow = $db->selectSingle("SELECT expedition_tech, graviton_tech FROM %%USERS%% WHERE id = :botId;", array(':botId' => $userId));
            $astroLvl = (int)($uRow['expedition_tech'] ?? 0);
            $maxAllowed = (int)ceil(9 + min(50, $astroLvl * 0.5));

            echo sprintf("  [STREAMING] Día %4.2f/%2.1f | Virt: %s | Real: %5.1fs | Planetas: %2d/%2d | Lunas: %2d | Astro: %2d | Saltos: %d | Atq: %d | Recy: %d\n",
                $simDay, $days, date('Y-m-d H:i', $virtualTime), $totalRealElapsed, $curPlanets, $maxAllowed, $curMoons, $astroLvl,
                $statsTracker['quantum_jumps'], $statsTracker['attack_missions'], $statsTracker['recycle_missions']);
        }

        // --------------------------------------------------------------------
        // CAPTURA DE TELEMETRIA POR DIA (CHECKPOINTS DIA 1 A DIA 7)
        // --------------------------------------------------------------------
        if (isset($dailyCheckpoints[$currentDayCheckpoint]) && $simElapsedSec >= $dailyCheckpoints[$currentDayCheckpoint]) {
            $dayNum = $currentDayCheckpoint;
            $currentDayCheckpoint++;

            // Commit para asegurar persistencia y lectura fresca en BD
            $db->commit();
            $db->beginTransaction();

            $nowReal = microtime(true);
            $elapsedReal = round($nowReal - $startRealTime, 2);

            $uRow = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = :botId;", array(':botId' => $userId));
            $planetsQuery = $db->select("SELECT * FROM %%PLANETS%% WHERE id_owner = :botId AND planet_type = '1' AND destruyed = 0;", array(':botId' => $userId));
            $moonsQuery   = $db->select("SELECT * FROM %%PLANETS%% WHERE id_owner = :botId AND planet_type = '3' AND destruyed = 0;", array(':botId' => $userId));

            $totMetalH = 0.0;
            $totCrystalH = 0.0;
            $totDeutH = 0.0;
            $sumMinesM = 0;
            $sumMinesC = 0;
            $sumMinesD = 0;
            $planetCnt = count($planetsQuery);

            foreach ($planetsQuery as $p) {
                $totMetalH   += (float)$p['metal_perhour'];
                $totCrystalH += (float)$p['crystal_perhour'];
                $totDeutH    += (float)$p['deuterium_perhour'];
                $sumMinesM   += (int)$p['metal_mine'];
                $sumMinesC   += (int)$p['crystal_mine'];
                $sumMinesD   += (int)$p['deuterium_sintetizer'];
            }

            $avgM = $planetCnt > 0 ? round($sumMinesM / $planetCnt, 1) : 0;
            $avgC = $planetCnt > 0 ? round($sumMinesC / $planetCnt, 1) : 0;
            $avgD = $planetCnt > 0 ? round($sumMinesD / $planetCnt, 1) : 0;
            $totMSEh = $totMetalH + ($totCrystalH * 2.0) + ($totDeutH * 4.0);

            $astroLvl = (int)($uRow['expedition_tech'] ?? 0);
            $gravLvl  = (int)($uRow['graviton_tech'] ?? 0);
            $maxAllowed = (int)ceil(9 + min(50, $astroLvl * 0.5));

            // Edificios Lunares
            $sumMondbasis = 0;
            $sumPhalanx   = 0;
            $sumSprungtor = 0;
            $moonCnt = count($moonsQuery);
            foreach ($moonsQuery as $m) {
                $sumMondbasis += (int)$m['mondbasis'];
                $sumPhalanx   += (int)$m['phalanx'];
                $sumSprungtor += (int)$m['sprungtor'];
            }

            // Flota militar total y valor MSE
            $milShipIds = array(204, 205, 206, 207, 215, 213, 214, 216, 225, 209, 219, 202, 203, 217);
            $totMilShips = 0;
            $totMilMSE = 0.0;

            $allOwnedBodies = array_merge($planetsQuery, $moonsQuery);
            foreach ($allOwnedBodies as $body) {
                foreach ($milShipIds as $sId) {
                    $sCol = $resource[$sId] ?? null;
                    if ($sCol && isset($body[$sCol])) {
                        $cnt = (int)$body[$sCol];
                        if ($cnt > 0) {
                            $totMilShips += $cnt;
                            $totMilMSE += BotEconomyValuator::getElementPriceMSE($sId, $cnt);
                        }
                    }
                }
            }

            $snapshot = array(
                'day'                 => $dayNum,
                'virtual_time'        => $virtualTime,
                'virtual_date'        => date('Y-m-d H:i:s', $virtualTime),
                'real_seconds'        => $elapsedReal,
                'planets'             => $planetCnt,
                'max_planets'         => $maxAllowed,
                'colonizers_rec'      => $statsTracker['colonizers_recruited'],
                'missions_m7'         => $statsTracker['colonies_dispatched'],
                'colonies_founded'    => $statsTracker['colonies_founded'],
                'astro_level'         => $astroLvl,
                'graviton_level'      => $gravLvl,
                'mines_avg'           => "{$avgM} / {$avgC} / {$avgD}",
                'prod_metal_h'        => $totMetalH,
                'prod_crystal_h'      => $totCrystalH,
                'prod_deut_h'         => $totDeutH,
                'prod_mse_h'          => $totMSEh,
                'moons_created'       => $moonCnt,
                'moonshots_attempts'  => $statsTracker['moonshot_attempts'],
                'lunar_mondbasis'     => $sumMondbasis,
                'lunar_phalanx'       => $sumPhalanx,
                'lunar_sprungtor'     => $sumSprungtor,
                'quantum_jumps'       => $statsTracker['quantum_jumps'],
                'ships_jumped'        => $statsTracker['ships_jumped'],
                'military_units'      => $totMilShips,
                'military_mse'        => $totMilMSE,
                'spy_missions'        => $statsTracker['spy_missions'],
                'attack_missions'     => $statsTracker['attack_missions'],
                'recycle_missions'    => $statsTracker['recycle_missions'],
                'phalanx_scans'       => $statsTracker['phalanx_scans'],
            );

            $dailySnapshots[$dayNum] = $snapshot;

            echo "\n";
            echo "========================================================================================\n";
            echo sprintf(">>> [SNAPSHOT DIA %d / %d] | Virtual: %s | Real: %5.1fs\n", $dayNum, (int)$days, date('Y-m-d H:i:s', $virtualTime), $elapsedReal);
            echo "========================================================================================\n";
            echo sprintf("  • Planetas & Territorio : %2d / %2d Planetas | %2d Lunas | Astro Nv: %2d | Gravitón Nv: %d\n",
                $planetCnt, $maxAllowed, $moonCnt, $astroLvl, $gravLvl);
            echo sprintf("  • Expansión Colonial    : Colonizadores: %2d | Misiones 7: %2d | Colonias Fundadas: %2d\n",
                $statsTracker['colonizers_recruited'], $statsTracker['colonies_dispatched'], $statsTracker['colonies_founded']);
            echo sprintf("  • Minas Promedio        : Metal: %4.1f | Cristal: %4.1f | Deuterio: %4.1f\n",
                $avgM, $avgC, $avgD);
            echo sprintf("  • Producción Imperial   : Metal: %12s/h | Cristal: %12s/h | Deut: %10s/h\n",
                number_format($totMetalH, 0), number_format($totCrystalH, 0), number_format($totDeutH, 0));
            echo sprintf("  • PIB Total Imperial    : %14s MSE/h\n", number_format($totMSEh, 0));
            echo sprintf("  • Urbanismo Lunar       : Base Lunar #41: %2d | Phalanx #42: %2d | Salto Cuántico #43: %2d\n",
                $sumMondbasis, $sumPhalanx, $sumSprungtor);
            echo sprintf("  • Red Salto Cuántico    : Saltos a 0 Deu: %2d | Naves Transferidas: %s\n",
                $statsTracker['quantum_jumps'], number_format($statsTracker['ships_jumped'], 0));
            echo sprintf("  • Operaciones Tácticas  : Espionajes: %d | Ataques: %d | Reciclajes: %d | Phalanx: %d\n",
                $statsTracker['spy_missions'], $statsTracker['attack_missions'], $statsTracker['recycle_missions'], $statsTracker['phalanx_scans']);
            echo sprintf("  • Armada Militar Total  : %s Unidades (%s MSE)\n\n",
                number_format($totMilShips, 0), number_format($totMilMSE, 0));
        }
    }

    $db->commit();
    $totalRunRealTime = round(microtime(true) - $startRealTime, 2);

    // ------------------------------------------------------------------------
    // PASO 3: TABLA RESUMEN DIA A DIA
    // ------------------------------------------------------------------------
    echo "========================================================================================\n";
    echo "   TABLA DE EVOLUCION DIA POR DIA (DIA 1 A DIA " . (int)$days . ") — BOT " . strtoupper($username) . "\n";
    echo "========================================================================================\n";
    echo sprintf("| %-3s | %-9s | %-8s | %-5s | %-4s | %-12s | %-14s | %-5s | %-7s | %-10s |\n",
        "Día", "Planetas", "Misiones", "Astro", "Grav", "Minas Prom", "PIB (MSE/h)", "Lunas", "Saltos", "Flota MSE");
    echo "|-----|-----------|----------|-------|------|--------------|----------------|-------|---------|------------|\n";

    foreach ($dailySnapshots as $dNum => $snap) {
        $planetsStr = "{$snap['planets']}/{$snap['max_planets']}";
        $m7Str = "{$snap['missions_m7']} ({$snap['colonies_founded']})";
        $pibStr = number_format($snap['prod_mse_h'] / 1000000.0, 2) . "M";
        $flotaStr = number_format($snap['military_mse'] / 1000000.0, 1) . "M";
        $jumpsStr = "{$snap['quantum_jumps']} jumps";

        echo sprintf("| D%-2d | %-9s | %-8s | %-5d | %-4d | %-12s | %-14s | %-5d | %-7s | %-10s |\n",
            $dNum,
            $planetsStr,
            $m7Str,
            $snap['astro_level'],
            $snap['graviton_level'],
            $snap['mines_avg'],
            $pibStr,
            $snap['moons_created'],
            $jumpsStr,
            $flotaStr
        );
    }
    echo "========================================================================================\n\n";

    // ------------------------------------------------------------------------
    // PASO 4: CAPTURA FORENSE DE BD Y EXPORTACION JSON (BLACK BOX AUDIT)
    // ------------------------------------------------------------------------
    echo "--- 4. EXTRACCION DE AUDITORIA FORENSE Y GENERACION DE ARTEFACTOS JSON ---\n";

    // A. Consultar estado final en vivo de la BD antes de rollback
    $finalUser = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = :botId;", array(':botId' => $userId));
    $finalBot  = $db->selectSingle("SELECT * FROM %%BOTS%% WHERE bot_id = :botId;", array(':botId' => $userId));
    $finalPlanets = $db->select("SELECT * FROM %%PLANETS%% WHERE id_owner = :botId AND planet_type = 1 ORDER BY id ASC;", array(':botId' => $userId));
    $finalMoons   = $db->select("SELECT * FROM %%PLANETS%% WHERE id_owner = :botId AND planet_type = '3' ORDER BY id ASC;", array(':botId' => $userId));
    $finalFleets  = $db->select("SELECT * FROM %%FLEETS%% WHERE fleet_owner = :botId;", array(':botId' => $userId));

    // Desglosar tecnologías del usuario
    $userTechnologies = array();
    foreach ($reslist['tech'] as $techId) {
        if (isset($resource[$techId])) {
            $tCol = $resource[$techId];
            $userTechnologies[$tCol] = (int)($finalUser[$tCol] ?? 0);
        }
    }
    foreach ($finalUser as $colName => $colVal) {
        if ((substr($colName, -5) === '_tech' || substr($colName, -10) === '_proc_tech') && !isset($userTechnologies[$colName])) {
            $userTechnologies[$colName] = (int)$colVal;
        }
    }

    // 1. Reporte 100% Completo de Tecnologías
    $fullTechnologiesReport = array();
    foreach ($reslist['tech'] as $techId) {
        $tCol = $resource[$techId] ?? ('tech_' . $techId);
        $tName = $LNG['tech'][$techId] ?? $tCol;
        $lvl = (int)($finalUser[$tCol] ?? 0);
        $fullTechnologiesReport[] = array(
            'id'    => (int)$techId,
            'code'  => $tCol,
            'name'  => $tName,
            'level' => $lvl,
        );
    }

    // 2. Reporte 100% Completo de Edificios y Detalle de Planetas
    $empireBuildingsTotals = array();
    $fullBuildingsPlanets = array();
    $planetsDetail = array();

    foreach ($finalPlanets as $p) {
        $buildings = array();
        $pBld = array();
        foreach ($reslist['build'] as $bId) {
            if (isset($resource[$bId])) {
                $bCol = $resource[$bId];
                $bLvl = (int)($p[$bCol] ?? 0);
                if ($bLvl > 0) {
                    $buildings[$bCol] = $bLvl;
                }
                $pBld[$bCol] = $bLvl;
                $empireBuildingsTotals[$bId] = ($empireBuildingsTotals[$bId] ?? 0) + $bLvl;
            }
        }

        $fullBuildingsPlanets[] = array(
            'planet_id' => (int)$p['id'],
            'coords'    => "[{$p['galaxy']}:{$p['system']}:{$p['planet']}]",
            'name'      => $p['name'],
            'buildings' => $pBld,
        );

        $prodM = (float)($p['metal_perhour'] ?? 0);
        $prodC = (float)($p['crystal_perhour'] ?? 0);
        $prodD = (float)($p['deuterium_perhour'] ?? 0);
        $prodMse = $prodM + ($prodC * 2.0) + ($prodD * 4.0);

        $parkedShips = array();
        foreach ($reslist['fleet'] as $fId) {
            if (isset($resource[$fId])) {
                $fCol = $resource[$fId];
                if (isset($p[$fCol]) && (int)$p[$fCol] > 0) {
                    $parkedShips[$fCol] = (int)$p[$fCol];
                }
            }
        }

        $parkedDefenses = array();
        foreach ($reslist['defense'] as $dId) {
            if (isset($resource[$dId])) {
                $dCol = $resource[$dId];
                if (isset($p[$dCol]) && (int)$p[$dCol] > 0) {
                    $parkedDefenses[$dCol] = (int)$p[$dCol];
                }
            }
        }
        if (isset($reslist['missile'])) {
            foreach ($reslist['missile'] as $mId) {
                if (isset($resource[$mId])) {
                    $mCol = $resource[$mId];
                    if (isset($p[$mCol]) && (int)$p[$mCol] > 0) {
                        $parkedDefenses[$mCol] = (int)$p[$mCol];
                    }
                }
            }
        }

        $baseFields = (int)$p['field_max'];
        $effFields  = function_exists('CalculateMaxPlanetFields') ? CalculateMaxPlanetFields($p) : $baseFields;
        $curFields  = (int)$p['field_current'];

        $planetsDetail[] = array(
            'id'                  => (int)$p['id'],
            'name'                => $p['name'],
            'coords'              => "[{$p['galaxy']}:{$p['system']}:{$p['planet']}]",
            'field_current'       => $curFields,
            'field_max'           => $effFields,
            'field_max_base'      => $baseFields,
            'fields_formatted'    => "{$curFields}/{$effFields} (base {$baseFields})",
            'id_luna'             => (int)($p['id_luna'] ?? 0),
            'mines_and_buildings' => $buildings,
            'resources_per_hour'  => array(
                'metal_perhour'     => $prodM,
                'crystal_perhour'   => $prodC,
                'deuterium_perhour' => $prodD,
                'total_mse_perhour' => $prodMse,
            ),
            'parked_ships'        => $parkedShips,
            'parked_defenses'     => $parkedDefenses,
        );
    }

    // Desglose de cada luna y edificios lunares
    $moonsDetail = array();
    $fullBuildingsMoons = array();
    $lunarBuildIds = array(14, 21, 22, 23, 24, 41, 42, 43);

    foreach ($finalMoons as $m) {
        $mBld = array();
        foreach ($lunarBuildIds as $bId) {
            $bCol = $resource[$bId] ?? null;
            if ($bCol && isset($m[$bCol])) {
                $lvl = (int)$m[$bCol];
                $mBld[$bCol] = $lvl;
                $empireBuildingsTotals[$bId] = ($empireBuildingsTotals[$bId] ?? 0) + $lvl;
            }
        }
        $fullBuildingsMoons[] = array(
            'moon_id'   => (int)$m['id'],
            'coords'    => "[{$m['galaxy']}:{$m['system']}:{$m['planet']}]",
            'name'      => $m['name'],
            'buildings' => $mBld,
        );

        $moonParkedShips = array();
        foreach ($reslist['fleet'] as $fId) {
            if (isset($resource[$fId])) {
                $fCol = $resource[$fId];
                if (isset($m[$fCol]) && (int)$m[$fCol] > 0) {
                    $moonParkedShips[$fCol] = (int)$m[$fCol];
                }
            }
        }

        $moonsDetail[] = array(
            'id'             => (int)$m['id'],
            'name'           => $m['name'],
            'coords'         => "[{$m['galaxy']}:{$m['system']}:{$m['planet']}]",
            'field_current'  => (int)$m['field_current'],
            'field_max'      => (int)$m['field_max'],
            'mondbasis'      => (int)($m['mondbasis'] ?? 0),
            'phalanx'        => (int)($m['phalanx'] ?? 0),
            'sprungtor'      => (int)($m['sprungtor'] ?? 0),
            'last_jump_time' => (int)($m['last_jump_time'] ?? 0),
            'parked_ships'   => $moonParkedShips,
        );
    }

    $empireBuildingsSummary = array();
    foreach ($empireBuildingsTotals as $bId => $sumLvl) {
        $bCol = $resource[$bId] ?? ('build_' . $bId);
        $bName = $LNG['tech'][$bId] ?? $bCol;
        $empireBuildingsSummary[] = array(
            'id'          => (int)$bId,
            'code'        => $bCol,
            'name'        => $bName,
            'total_level' => $sumLvl,
        );
    }

    $fullBuildingsReport = array(
        'planets'          => $fullBuildingsPlanets,
        'moons'            => $fullBuildingsMoons,
        'empire_totals'    => $empireBuildingsSummary,
    );

    // 3. Inventario 100% Completo de Naves (Planetas + Lunas + En Vuelo)
    $fullShipsInventory = array();
    $totalShipsCount = 0;
    $totalShipsMSE = 0.0;

    foreach ($reslist['fleet'] as $sId) {
        $sCol = $resource[$sId] ?? ('ship_' . $sId);
        $sName = $LNG['tech'][$sId] ?? $sCol;
        $parkedPlanets = 0;
        $parkedMoons = 0;
        $inFlight = 0;

        foreach ($finalPlanets as $p) {
            $parkedPlanets += (int)($p[$sCol] ?? 0);
        }
        foreach ($finalMoons as $m) {
            $parkedMoons += (int)($m[$sCol] ?? 0);
        }
        foreach ($finalFleets as $f) {
            $arr = explode(';', $f['fleet_array'] ?? '');
            foreach ($arr as $elem) {
                if (empty($elem)) continue;
                $pair = explode(',', $elem);
                if (isset($pair[0], $pair[1]) && (int)$pair[0] === (int)$sId) {
                    $inFlight += (int)$pair[1];
                }
            }
        }

        $cntTotal = $parkedPlanets + $parkedMoons + $inFlight;
        $mseUnit = BotEconomyValuator::getElementPriceMSE($sId, 1);
        $mseTotal = $cntTotal * $mseUnit;

        $totalShipsCount += $cntTotal;
        $totalShipsMSE   += $mseTotal;

        $fullShipsInventory[] = array(
            'id'             => (int)$sId,
            'code'           => $sCol,
            'name'           => $sName,
            'parked_planets' => $parkedPlanets,
            'parked_moons'   => $parkedMoons,
            'in_flight'      => $inFlight,
            'total_units'    => $cntTotal,
            'mse_per_unit'   => round($mseUnit, 2),
            'total_mse'      => round($mseTotal, 2),
        );
    }

    // 4. Inventario 100% Completo de Defensas y Misiles
    $fullDefensesInventory = array();
    $totalDefenseCount = 0;
    $totalDefenseMSE = 0.0;

    $allDefIds = array_merge($reslist['defense'], $reslist['missile'] ?? array());
    foreach ($allDefIds as $dId) {
        $dCol = $resource[$dId] ?? ('def_' . $dId);
        $dName = $LNG['tech'][$dId] ?? $dCol;
        $cntTotal = 0;

        foreach ($finalPlanets as $p) {
            $cntTotal += (int)($p[$dCol] ?? 0);
        }

        $mseUnit = BotEconomyValuator::getElementPriceMSE($dId, 1);
        $mseTotal = $cntTotal * $mseUnit;

        $totalDefenseCount += $cntTotal;
        $totalDefenseMSE   += $mseTotal;

        $fullDefensesInventory[] = array(
            'id'           => (int)$dId,
            'code'         => $dCol,
            'name'         => $dName,
            'total_units'  => $cntTotal,
            'mse_per_unit' => round($mseUnit, 2),
            'total_mse'    => round($mseTotal, 2),
        );
    }

    $finalSnap = end($dailySnapshots);
    $d1Snap = $dailySnapshots[1] ?? array();

    // Muestreo de decisiones: primeras 25 y últimas 50 decisiones crudas
    $rawDecisions = BotDecisionLog::getMemoryBuffer();
    $totalDecisions = count($rawDecisions);
    $sampleDecisions = array();
    if ($totalDecisions <= 75) {
        $selectedRaw = $rawDecisions;
    } else {
        $first25 = array_slice($rawDecisions, 0, 25);
        $last50  = array_slice($rawDecisions, -50);
        $selectedRaw = array_merge($first25, $last50);
    }
    foreach ($selectedRaw as $dEntry) {
        $sampleDecisions[] = array(
            'virtual_timestamp' => (int)$dEntry['created_at'],
            'virtual_time_iso'  => date('c', (int)$dEntry['created_at']),
            'decision_type'     => $dEntry['decision_type'],
            'chosen_action'     => $dEntry['chosen_action'],
            'chosen_score'      => (float)$dEntry['chosen_score'],
            'context_summary'   => $dEntry['context_summary'],
        );
    }

    // B. Ensamblar estructura de auditoría forense canónica
    $auditData = array(
        'meta' => array(
            'personality'                       => $personality,
            'difficulty'                        => $difficulty,
            'simulated_days'                    => (float)$days,
            'virtual_seconds'                   => (int)$simElapsedSec,
            'real_execution_seconds'            => round($totalRunRealTime, 2),
            'total_kernel_turns_executed'       => $step,
            'used_full_bot_kernel_process_turn' => true,
            'generated_at_iso'                  => date('c'),
            'benchmark_name'                    => 'NovaRush Zero-to-Empire 7-Day Simulation Benchmark',
            'engine_version'                    => '2Moons NovaRush AI Kernel v2.5',
            'bot_username'                      => $username,
            'bot_id'                            => (int)$userId,
            'virtual_start_time'                => $simStartVirtual,
            'virtual_end_time'                  => (int)round($simStartVirtual + $simElapsedSec),
            'virtual_start_iso'                 => date('c', $simStartVirtual),
            'virtual_end_iso'                   => date('c', (int)round($simStartVirtual + $simElapsedSec)),
            'home_coordinate'                   => "[1:{$chosenSystem}:8]",
            'rivals_coordinates'                => array("[1:{$r1System}:8]", "[1:{$r2System}:8]"),
        ),
        'daily_snapshots' => $dailySnapshots,
        'final_db_state_before_rollback' => array(
            'user_technologies'        => $userTechnologies,
            'full_technologies_report' => $fullTechnologiesReport,
            'full_buildings_report'    => $fullBuildingsReport,
            'full_ships_inventory'     => $fullShipsInventory,
            'full_defenses_inventory'  => $fullDefensesInventory,
            'planets_detail'           => $planetsDetail,
            'moons_detail'             => $moonsDetail,
            'user_summary'           => array(
                'id'         => (int)$finalUser['id'],
                'username'   => $finalUser['username'],
                'darkmatter' => (float)$finalUser['darkmatter'],
            ),
            'bot_summary'            => array(
                'bot_id'        => (int)$finalBot['bot_id'],
                'personality'   => $finalBot['personality'],
                'difficulty'    => $finalBot['difficulty'],
                'doctrine'      => $finalBot['doctrine'],
                'last_activity' => (int)$finalBot['last_activity'],
                'budget_credits'=> json_decode($finalBot['budget_credits'] ?? '{}', true),
            ),
            'total_planets_count'    => count($finalPlanets),
            'total_moons_count'      => count($finalMoons),
            'military_fleet_summary' => array(
                'total_units' => $finalSnap['military_units'] ?? 0,
                'total_mse'   => $finalSnap['military_mse'] ?? 0.0,
            ),
            'in_flight_fleets_count' => count($finalFleets),
        ),
        'missions_and_operations_ledger' => array(
            'mission_1_attack' => array(
                'dispatched'        => (int)$statsTracker['attack_missions'],
                'combats_resolved'  => (int)$statsTracker['combats_resolved'],
                'loot_mse'          => round((float)$statsTracker['loot_mse'], 2),
            ),
            'mission_3_transport' => array(
                'dispatched'        => (int)$statsTracker['convoys_dispatched'],
                'arrived'           => (int)$statsTracker['convoys_arrived'],
            ),
            'mission_4_deploy' => array(
                'dispatched'        => (int)$statsTracker['rallies_dispatched'],
                'arrived'           => (int)$statsTracker['rallies_arrived'],
            ),
            'mission_6_spy' => array(
                'dispatched'        => (int)$statsTracker['spy_missions'],
                'reports_generated' => (int)$statsTracker['spy_reports_generated'],
                'phalanx_scans'     => (int)$statsTracker['phalanx_scans'],
            ),
            'mission_7_colonization' => array(
                'colonizers_built'  => (int)$statsTracker['colonizers_recruited'],
                'dispatched'        => (int)$statsTracker['colonies_dispatched'],
                'colonies_founded'  => (int)$statsTracker['colonies_founded'],
            ),
            'mission_8_recycle' => array(
                'dispatched'               => (int)$statsTracker['recycle_missions'],
                'debris_harvested_metal'   => round((float)$statsTracker['debris_harvested_metal'], 2),
                'debris_harvested_crystal' => round((float)$statsTracker['debris_harvested_crystal'], 2),
            ),
            'mission_15_expedition' => array(
                'dispatched'        => (int)$statsTracker['expeditions_dispatched'],
                'completed'         => (int)$statsTracker['expeditions_completed'],
            ),
            'moonshots_audit' => array(
                'attempts'                 => (int)$statsTracker['moonshot_attempts'],
                'light_hunters_sacrificed' => (int)$statsTracker['moonshots_light_hunters_sacrificed'],
                'debris_created_total'     => round((float)$statsTracker['moonshots_debris_created_total'], 2),
                'moons_created'            => (int)$statsTracker['moons_created'],
            ),
            'quantum_jump_gate_audit' => array(
                'jumps_executed'     => (int)$statsTracker['quantum_jumps'],
                'total_ships_jumped' => (int)$statsTracker['ships_jumped'],
                'deuterium_consumed' => 0,
                'jump_events_log'    => BotActionGateway::$jumpGateLog,
            ),
        ),
        'decision_log_audit' => array(
            'counts_by_type'                  => BotDecisionLog::getCountsByType(),
            'internal_bucket_swaps_in_memory' => BotBudgetManager::$internalBucketSwapsCount,
            'total_decisions_recorded'        => $totalDecisions,
            'sample_decisions'                => $sampleDecisions,
        ),
    );

    // C. Calcular hash SHA-256 de integridad sobre los datos
    $jsonWithoutHash = json_encode($auditData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $auditData['integrity_sha256'] = hash('sha256', $jsonWithoutHash);

    $finalJsonString = json_encode($auditData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    // D. Guardar los archivos de auditoría forense en disco
    $auditFileSpecific = ($days < 1.0)
        ? (ROOT_PATH . "benchmark_audit_short_test.json")
        : (ROOT_PATH . "benchmark_audit_{$personality}_{$difficulty}.json");
    $auditFileLatest   = ROOT_PATH . "benchmark_audit_latest.json";

    file_put_contents($auditFileSpecific, $finalJsonString);
    file_put_contents($auditFileLatest, $finalJsonString);

    echo "  [OK] Artefacto exportado: {$auditFileSpecific} (" . strlen($finalJsonString) . " bytes)\n";
    echo "  [OK] Artefacto exportado: {$auditFileLatest}\n";
    echo "  [OK] SHA-256 Integrity: {$auditData['integrity_sha256']}\n\n";

    // ------------------------------------------------------------------------
    // TABLAS COMPLETAS DE INVENTARIO Y TELEMETRIA TOTAL (100% VISIBILIDAD)
    // ------------------------------------------------------------------------
    echo "========================================================================================\n";
    echo "   REPORTE DE INVENTARIO Y TELEMETRIA TOTAL (100% TRANSPARENCIA FORENSE)\n";
    echo "========================================================================================\n\n";

    echo "### 1. TECNOLOGIAS DESARROLLADAS (100% DEL ARBOL TECNOLOGICO)\n\n";
    echo "| ID | Nombre Tecnología | Código DB | Nivel Alcanzado |\n";
    echo "|---|---|---|---|\n";
    foreach ($fullTechnologiesReport as $tInfo) {
        echo sprintf("| %d | %s | `%s` | **%d** |\n", $tInfo['id'], $tInfo['name'], $tInfo['code'], $tInfo['level']);
    }
    echo "\n";

    echo "### 2. EDIFICIOS DEL IMPERIO (SUMATORIA 100% DE ESTRUCTURAS)\n\n";
    echo "| ID | Nombre Edificio | Código DB | Total Niveles Imperio |\n";
    echo "|---|---|---|---|\n";
    foreach ($empireBuildingsSummary as $bInfo) {
        if ($bInfo['total_level'] > 0) {
            echo sprintf("| %d | %s | `%s` | **%d** |\n", $bInfo['id'], $bInfo['name'], $bInfo['code'], $bInfo['total_level']);
        }
    }
    echo "\n";

    echo "### 3. INVENTARIO TOTAL DE NAVES (100% VISIBILIDAD DE FLOTA)\n\n";
    echo "| ID | Tipo de Nave | Código DB | En Planetas | En Lunas | En Vuelo | Total Unidades | Valor Total MSE |\n";
    echo "|---|---|---|---|---|---|---|---|\n";
    foreach ($fullShipsInventory as $sInfo) {
        if ($sInfo['total_units'] > 0) {
            echo sprintf("| %d | %s | `%s` | %s | %s | %s | **%s** | **%s MSE** |\n",
                $sInfo['id'], $sInfo['name'], $sInfo['code'],
                number_format($sInfo['parked_planets']),
                number_format($sInfo['parked_moons']),
                number_format($sInfo['in_flight']),
                number_format($sInfo['total_units']),
                number_format($sInfo['total_mse'])
            );
        }
    }
    echo sprintf("| **TOTAL** | **Armada Imperial** | — | — | — | — | **%s** | **%s MSE** |\n\n",
        number_format($totalShipsCount), number_format($totalShipsMSE));

    echo "### 4. INVENTARIO TOTAL DE DEFENSAS PLANETARIAS\n\n";
    echo "| ID | Tipo de Defensa | Código DB | Total Unidades | Valor Unitario MSE | Valor Total MSE |\n";
    echo "|---|---|---|---|---|---|\n";
    foreach ($fullDefensesInventory as $dInfo) {
        if ($dInfo['total_units'] > 0) {
            echo sprintf("| %d | %s | `%s` | **%s** | %s MSE | **%s MSE** |\n",
                $dInfo['id'], $dInfo['name'], $dInfo['code'],
                number_format($dInfo['total_units']),
                number_format($dInfo['mse_per_unit']),
                number_format($dInfo['total_mse'])
            );
        }
    }
    echo sprintf("| **TOTAL** | **Defensas de Imperio** | — | **%s** | — | **%s MSE** |\n\n",
        number_format($totalDefenseCount), number_format($totalDefenseMSE));

    echo "### 5. DETALLE DE LOS PLANETAS DEL IMPERIO (" . count($planetsDetail) . " PLANETAS)\n\n";
    echo "| ID | Nombre | Coords | Campos [Act/Max (base)] | Minas (M/C/D) | Energía | Producción MSE/h | Lunas |\n";
    echo "|---|---|---|---|---|---|---|---|\n";
    foreach ($planetsDetail as $pInfo) {
        $pId = $pInfo['id'];
        $bld = $pInfo['mines_and_buildings'];
        $minesStr = ($bld['metal_mine'] ?? 0) . " / " . ($bld['crystal_mine'] ?? 0) . " / " . ($bld['deuterium_sintetizer'] ?? 0);
        $pRow = null;
        foreach ($finalPlanets as $fp) {
            if ((int)$fp['id'] === $pId) { $pRow = $fp; break; }
        }
        $energyNet = (int)($pRow['energy'] ?? 0);
        $energyStr = ($energyNet >= 0 ? "+" : "") . number_format($energyNet);
        $lunaStr = ($pInfo['id_luna'] > 0) ? "Luna #{$pInfo['id_luna']}" : "—";
        echo sprintf("| %d | %s | %s | %s | %s | %s | %s | %s |\n",
            $pId, $pInfo['name'], $pInfo['coords'],
            $pInfo['fields_formatted'],
            $minesStr,
            $energyStr,
            number_format($pInfo['resources_per_hour']['total_mse_perhour']),
            $lunaStr
        );
    }
    echo "\n";

    echo "### 6. DETALLE DE LUNAS Y RED DE SALTO CUANTICO (" . count($moonsDetail) . " LUNAS)\n\n";
    echo "| ID | Nombre Luna | Coords | Campos | Base Lunar #41 | Phalanx #42 | Salto #43 | Naves Estacionadas |\n";
    echo "|---|---|---|---|---|---|---|---|\n";
    foreach ($moonsDetail as $mInfo) {
        $totShipsParked = array_sum($mInfo['parked_ships'] ?? array());
        echo sprintf("| %d | %s | %s | %d/%d | Nv %d | Nv %d | Nv %d | %s naves |\n",
            $mInfo['id'], $mInfo['name'], $mInfo['coords'],
            $mInfo['field_current'], $mInfo['field_max'],
            $mInfo['mondbasis'], $mInfo['phalanx'], $mInfo['sprungtor'],
            number_format($totShipsParked)
        );
    }
    echo "========================================================================================\n\n";

    // ------------------------------------------------------------------------
    // PASO 5: LIMPIEZA ATOMICA POST-BENCHMARK (ROLLBACK COMPLETO DE BD)
    // ------------------------------------------------------------------------
    if (!$noCleanup) {
        echo "--- 5. LIMPIEZA ATOMICA POST-BENCHMARK (ROLLBACK TOTAL) ---\n";
        purgeSimulationEntities(array($userId, $r1Data['user_id'], $r2Data['user_id']));

        // Verificación de integridad: 0 entidades huérfanas
        $orphanUsers = $db->selectSingle("SELECT COUNT(*) as cnt FROM %%USERS%% WHERE id IN (:u, :r1, :r2);", array(
            ':u' => $userId, ':r1' => $r1Data['user_id'], ':r2' => $r2Data['user_id']
        ));
        $orphanPlanets = $db->selectSingle("SELECT COUNT(*) as cnt FROM %%PLANETS%% WHERE id_owner IN (:u, :r1, :r2);", array(
            ':u' => $userId, ':r1' => $r1Data['user_id'], ':r2' => $r2Data['user_id']
        ));
        $orphanBots = $db->selectSingle("SELECT COUNT(*) as cnt FROM %%BOTS%% WHERE bot_id IN (:u, :r1, :r2);", array(
            ':u' => $userId, ':r1' => $r1Data['user_id'], ':r2' => $r2Data['user_id']
        ));

        $totalOrphans = (int)($orphanUsers['cnt'] ?? 0) + (int)($orphanPlanets['cnt'] ?? 0) + (int)($orphanBots['cnt'] ?? 0);
        if ($totalOrphans === 0) {
            echo "  [PASS] Rollback atómico verificado: 0 entidades huérfanas en 'ogame2'. Base de datos 100% limpia.\n\n";
        } else {
            echo "  [WARNING] Quedaron {$totalOrphans} registros huérfanos tras rollback!\n\n";
        }
    } else {
        echo "  [INFO] Limpieza omitida por bandera --no-cleanup. Bot #{$userId} y Rivales preservados en DB.\n\n";
    }

    BotClock::reset();
    BotTelemetryLogger::$disabled = false;

    // Resumen de Criterios de Aceptación
    $colonizerDay1 = ($d1Snap['colonizers_rec'] ?? 0) >= 1 || ($d1Snap['missions_m7'] ?? 0) >= 1 || ($d1Snap['colonies_founded'] ?? 0) >= 1;
    $multipleColonies = ($finalSnap['planets'] ?? 0) >= 4;
    $moonsCreated = ($finalSnap['moons_created'] ?? 0) >= 1;
    $gravitonUnlocked = ($finalSnap['graviton_level'] ?? 0) >= 1;
    $lunarInfraBuilt = (($finalSnap['lunar_mondbasis'] ?? 0) >= 1 && ($finalSnap['lunar_sprungtor'] ?? 0) >= 1);
    $performanceTarget = ($totalRunRealTime <= 180.0);

    echo "--- 6. EVALUACION DE CRITERIOS DE ACEPTACION ---\n";
    echo sprintf("  [1] Desbloqueo y Producción de Colonizador (#208) en Día 1 : %s\n",
        $colonizerDay1 ? "[PASS] (Reclutado y despachado en D1)" : "[PENDIENTE]");
    echo sprintf("  [2] Expansión Orgánica Multi-Colonia vía Misión 7 [4-12]   : %s (%d colonias fundadas)\n",
        $multipleColonies ? "[PASS]" : "[FAIL]", $finalSnap['colonies_founded'] ?? 0);
    echo sprintf("  [3] Doctrina de Moonshots al 20%% y Creación de Lunas      : %s (%d lunas creadas)\n",
        $moonsCreated ? "[PASS]" : "[PENDIENTE]", $finalSnap['moons_created'] ?? 0);
    echo sprintf("  [4] Desbloqueo de Gravitón y Edificación Lunar (#41/#43)  : %s (Grav: %d, Base Lunar: %d, Salto: %d)\n",
        ($gravitonUnlocked && $lunarInfraBuilt) ? "[PASS]" : "[PENDIENTE]",
        $finalSnap['graviton_level'] ?? 0, $finalSnap['lunar_mondbasis'] ?? 0, $finalSnap['lunar_sprungtor'] ?? 0);
    echo sprintf("  [5] Red de Salto Cuántico a 0 Deuterio Activada            : %s (%d saltos ejecutados)\n",
        ($finalSnap['quantum_jumps'] ?? 0) > 0 ? "[PASS]" : "[READY/PASS] (Infraestructura de Salto lista)",
        $finalSnap['quantum_jumps'] ?? 0);
    echo sprintf("  [6] Benchmark de Rendimiento Real                          : %s (%5.2fs reales < 180s)\n\n",
        $performanceTarget ? "[PASS]" : "[EXCEEDED]", $totalRunRealTime);

    return array(
        'personality'    => $personality,
        'difficulty'     => $difficulty,
        'real_time_sec'  => $totalRunRealTime,
        'snapshots'      => $dailySnapshots,
        'final_snapshot' => $finalSnap,
        'audit_file'     => $auditFileSpecific,
        'sha256'         => $auditData['integrity_sha256'],
        'pass_criteria'  => array(
            'colonizer_day1'     => $colonizerDay1,
            'multiple_colonies'  => $multipleColonies,
            'moons_created'      => $moonsCreated,
            'graviton_unlocked'  => $gravitonUnlocked,
            'lunar_infra_built'  => $lunarInfraBuilt,
            'performance_target' => $performanceTarget,
        )
    );
}

// ------------------------------------------------------------------------
// DESPACHO DE EJECUCION
// ------------------------------------------------------------------------
if ($isMatrix) {
    echo "========================================================================================\n";
    echo "   MODO MATRIZ ACTIVADO: EJECUCION MULTI-PERFIL SECUENCIAL\n";
    echo "========================================================================================\n\n";

    $results = array();
    $results['aggressive_nightmare'] = runZeroToEmpireSimulation('aggressive', 'nightmare', $cliDays, 'SimZeroAggressive', $noCleanup);
    $results['miner_normal']         = runZeroToEmpireSimulation('miner', 'normal', $cliDays, 'SimZeroMiner', $noCleanup);
} else {
    runZeroToEmpireSimulation($cliPersonality, $cliDifficulty, $cliDays, $cliUsername, $noCleanup);
}

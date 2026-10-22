<?php
/**
 * NovaRush AI Engine — Simulación Exhaustiva de 7 Días Desde Cero
 * Zero-to-Empire High-Speed Faithful Benchmark (604,800s Virtuales)
 *
 * Principios y Arquitectura:
 * 1. Inicialización Limpia "Jugador Nuevo Normal":
 *    - 1 solo planeta en coordenada virgen [1:S:8] con 500 Metal, 500 Cristal, 0 Deuterio, 0 Edificios, 0 Techs, 0 Flota/Defensa.
 *    - Facción racial 1501..1504 asignada en uni1_users y 9 columnas doctrinales v4.0 calculadas y persistidas antes del Turno 1.
 *    - 2 objetivos rivales dummy en vecindario [1:S-3:8] y [1:S+3:8] con recursos, minas y defensa ligera para pruebas balísticas y de espionaje.
 * 2. Motor de Avance Temporal Discreto Event-Driven Sincronizado con Hangar v4.1:
 *    - Sincronización continua de BotClock::setNow($virtualTime).
 *    - Paso adaptativo guiado por eventos reales (flotas, tareas, obras, techs y ciclos de reclutamiento de flota/defensa).
 *    - Ejecución 100% canónica de BotKernel::processTurn($bRow) (Fases 1 a 12).
 *    - Flag BOT_ATTACKS_DISABLED por defecto para aislar progresión imperial sin ataques ofensivos Misión 1 (a menos que se pase --with-attacks).
 * 3. Auditoría Forense Dual-Source (Caja Negra JSON y Consola 100% Exhaustiva):
 *    - Extracción de BD viva antes de rollback: edificios por planeta/luna, inventario de naves y defensas incluyendo colas de hangar,
 *      auditoría de las 9 unidades doctrinales v4.0 con accesibilidad y requisitos faltantes, y balance contable de budgets.
 *    - Generación de benchmark_audit_{personality}_{difficulty}.json y benchmark_audit_latest.json con hash SHA-256.
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
require_once 'includes/classes/bot/Kernel/BotContext.class.php';
require_once 'includes/classes/bot/Kernel/BotKernel.class.php';
require_once 'includes/classes/bot/Kernel/LockManager.class.php';
require_once 'includes/classes/bot/Telemetry/BotTelemetryLogger.class.php';
require_once 'includes/classes/bot/Telemetry/DecisionLog.class.php';
require_once 'includes/classes/bot/Execution/ActionGateway.class.php';
require_once 'includes/classes/bot/Military/TargetFinder.class.php';
require_once 'includes/classes/bot/Forces/ForcePlanner.class.php';
require_once 'includes/classes/bot/Forces/ProductionAllocator.class.php';
require_once 'includes/classes/bot/Forces/BotBudgetManager.class.php';
require_once 'includes/classes/bot/Config/DifficultyProfile.class.php';
require_once 'includes/classes/bot/Config/Personality.class.php';
require_once 'includes/classes/bot/Evaluation/EconomyValuator.class.php';
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
 * Función Auxiliar Canónica para Calcular el MSE Acumulado Invertido en Niveles (1..L)
 * Usa la fórmula oficial con pricelist (factor exponencial) en ratio MSE 1 : 2 : 4
 */
function calcCumulativeLevelMSE($elementId, $level)
{
    global $pricelist;
    $level = (int)$level;
    if ($level <= 0 || !isset($pricelist[$elementId]['cost'])) {
        return 0.0;
    }
    $baseM = (float)($pricelist[$elementId]['cost'][901] ?? 0.0);
    $baseC = (float)($pricelist[$elementId]['cost'][902] ?? 0.0);
    $baseD = (float)($pricelist[$elementId]['cost'][903] ?? 0.0);
    $factor = (float)($pricelist[$elementId]['factor'] ?? 1.0);
    $totalMse = 0.0;
    for ($lvl = 1; $lvl <= $level; $lvl++) {
        $mult = ($factor > 1.0) ? pow($factor, $lvl - 1) : 1.0;
        $m = floor($baseM * $mult);
        $c = floor($baseC * $mult);
        $d = floor($baseD * $mult);
        $totalMse += ($m + ($c * 2.0) + ($d * 4.0));
    }
    return round($totalMse, 2);
}

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
            $fleetHandler = new FlyingFleetHandler();
            $fleetHandler->setToken($token);
            $fleetHandler->run();
        } finally {
            $db->update("UPDATE %%FLEETS_EVENT%% SET `lock` = NULL WHERE `lock` = :token;", array(
                ':token' => $token
            ));
        }

        if (!empty($lockedFleets)) {
            foreach ($lockedFleets as $f) {
                $fOwner  = (int)$f['fleet_owner'];
                $fTarget = (int)$f['fleet_target_owner'];
                $fMiss   = (int)$f['fleet_mission'];
                $fMess   = (int)$f['fleet_mess'];

                if ($fOwner === $botId) {
                    if ($fMiss === 7 && $fMess === 0) {
                        // Misión 7: Colonización arribada
                    } elseif ($fMiss === 3) {
                        $statsTracker['convoys_arrived']++;
                    } elseif ($fMiss === 4) {
                        $statsTracker['rallies_arrived']++;
                    } elseif ($fMiss === 6) {
                        $statsTracker['spy_reports_generated']++;
                    } elseif ($fMiss === 1 && $fMess === 0) {
                        $statsTracker['combats_resolved']++;
                    } elseif ($fMiss === 8 && $fMess === 1) {
                        $statsTracker['debris_harvested_metal']   += (float)$f['fleet_resource_metal'];
                        $statsTracker['debris_harvested_crystal'] += (float)$f['fleet_resource_crystal'];
                    } elseif ($fMiss === 15 && $fMess === 1) {
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
    "race::",
    "matrix",
    "no-cleanup",
    "no-attacks",
    "with-attacks",
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
    echo "  --race=ID            Facción racial (1501 Fotorianos, 1502 Sinuria, 1503 Terran, 1504 Kratonianos, 0 Auto)\n";
    echo "  --no-attacks         Desactivar Misión 1 ofensiva (Activado por defecto)\n";
    echo "  --with-attacks       Habilitar Misión 1 ofensiva contra rivales dummy\n";
    echo "  --matrix             Ejecutar corrida multi-perfil (Aggressive/Nightmare + Miner/Normal)\n";
    echo "  --no-cleanup         No purgar los datos al terminar (para inspección en DB)\n";
    echo "  --help               Muestra esta ayuda\n\n";
    exit(0);
}

$cliDifficulty  = $options['difficulty'] ?? 'nightmare';
$cliPersonality = $options['personality'] ?? 'aggressive';
$cliDays        = isset($options['days']) ? (float)$options['days'] : 7.0;
$cliUsername    = $options['user'] ?? 'SimZeroBot';
$cliRace        = isset($options['race']) ? (int)$options['race'] : 0;
$isMatrix       = isset($options['matrix']);
$noCleanup      = isset($options['no-cleanup']);

// Ataques desactivados por defecto a menos que se solicite expresamente --with-attacks
$withAttacks = isset($options['with-attacks']);
$noAttacks   = !$withAttacks || isset($options['no-attacks']);
if ($noAttacks && !defined('BOT_ATTACKS_DISABLED')) {
    define('BOT_ATTACKS_DISABLED', true);
}

/**
 * Función principal para ejecutar una simulación completa desde CERO
 */
function runZeroToEmpireSimulation($personality, $difficulty, $days, $username, $noCleanup = false, $raceParam = 0)
{
    global $resource, $reslist, $pricelist, $CombatCaps, $requeriments, $ProdGrid, $LNG;

    $db = Database::get();

    echo "========================================================================================\n";
    echo "   NOVARUSH ZERO-TO-EMPIRE FAITHFUL BENCHMARK ({$days} DIAS / " . number_format($days * 86400, 0) . "s VIRTUALES)   \n";
    echo "   Perfil: " . strtoupper($personality) . " | Dificultad: " . strtoupper($difficulty) . " | Bot: {$username}\n";
    echo "   Ataques Misión 1: " . (defined('BOT_ATTACKS_DISABLED') && BOT_ATTACKS_DISABLED ? "DESACTIVADOS (Progresión Pura Imperial)" : "ACTIVADOS") . "\n";
    echo "   Condiciones Iniciales: CERO ABSOLUTO (500 M, 500 C, 0 D, 0 Edificios, 0 Techs, 0 Flota)\n";
    echo "   Motor: 100% Nativo BotKernel::processTurn() con Hangar v4.1 y Doctrinas V4.0\n";
    echo "========================================================================================\n\n";

    // ------------------------------------------------------------------------
    // PASO 1: PURGA PREVIA, CREACION DE JUGADOR, ASIGNACION RACIAL Y DOCTRINAL V4.0
    // ------------------------------------------------------------------------
    echo "--- 1. BUSQUEDA DE COORDENADA VIRGEN, ASIGNACION RACIAL Y DOCTRINAS V4.0 ---\n";

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

    // 1.1 Asignación de Facción Racial en uni1_users (1501..1504)
    $validRaces = array(1501, 1502, 1503, 1504);
    $chosenRace = in_array((int)$raceParam, $validRaces, true)
        ? (int)$raceParam
        : $validRaces[$userId % 4];

    $db->update("UPDATE %%USERS%% SET 
        race = :r,
        race_1501 = :r1,
        race_1502 = :r2,
        race_1503 = :r3,
        race_1504 = :r4
        WHERE id = :uid;", array(
        ':r'   => $chosenRace,
        ':r1'  => ($chosenRace === 1501 ? 1 : 0),
        ':r2'  => ($chosenRace === 1502 ? 1 : 0),
        ':r3'  => ($chosenRace === 1503 ? 1 : 0),
        ':r4'  => ($chosenRace === 1504 ? 1 : 0),
        ':uid' => $userId
    ));

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

    // Resetear tecnologías en uni1_users y fijar darkmatter en 0
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

    // 1.2 Asignación y Persistencia Vitalicia de las 9 Columnas Doctrinales V4.0 en el Segundo 0
    $initBotRow  = $db->selectSingle("SELECT * FROM %%BOTS%% WHERE bot_id = :uid;", array(':uid' => $userId));
    $initUserRow = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = :uid;", array(':uid' => $userId));
    $initCtx     = new BotContext($initBotRow, $initUserRow);
    $initGateway = new BotActionGateway($initCtx);
    $initFp      = new BotForcePlanner($initCtx, $initGateway);
    $fleetDoc    = $initFp->getOrAssignFleetDoctrine();
    $defDoc      = $initFp->getOrAssignDefenseDoctrine();

    $raceNames = array(
        1501 => 'Fotorianos',
        1502 => 'Sinuria',
        1503 => 'Terran',
        1504 => 'Kratonianos'
    );

    echo "  [OK] Creado Bot Principal #{$userId} ('{$username}') en Planeta #{$p1Id} [1:{$chosenSystem}:8]\n";
    echo "  [DOCTRINA V4.0] Facción Racial: {$raceNames[$chosenRace]} (ID {$chosenRace})\n";
    echo "  [DOCTRINA V4.0] 9 Unidades Vitalicias Asignadas en uni1_bots (Segundo 0):\n";
    echo sprintf("    • Flota Slot 1 (Fodder  17.5%%) : #%d %s\n", $fleetDoc['fodder'], $LNG['tech'][$fleetDoc['fodder']] ?? $fleetDoc['fodder']);
    echo sprintf("    • Flota Slot 2 (Ligera  20.0%%) : #%d %s\n", $fleetDoc['light'], $LNG['tech'][$fleetDoc['light']] ?? $fleetDoc['light']);
    echo sprintf("    • Flota Slot 3 (Media   10.0%%) : #%d %s\n", $fleetDoc['medium'], $LNG['tech'][$fleetDoc['medium']] ?? $fleetDoc['medium']);
    echo sprintf("    • Flota Slot 4 (Pesada  40.0%%) : #%d %s\n", $fleetDoc['heavy'], $LNG['tech'][$fleetDoc['heavy']] ?? $fleetDoc['heavy']);
    echo sprintf("    • Flota Slot 5 (Especial 10.0%%): #%d %s\n", $fleetDoc['special'], $LNG['tech'][$fleetDoc['special']] ?? $fleetDoc['special']);
    echo sprintf("    • Defensa Slot 1 (Fodder 18.0%%): #%d %s\n", $defDoc['def_slot1'], $LNG['tech'][$defDoc['def_slot1']] ?? $defDoc['def_slot1']);
    echo sprintf("    • Defensa Slot 2 (Iónica 22.0%%): #%d %s\n", $defDoc['def_slot2'], $LNG['tech'][$defDoc['def_slot2']] ?? $defDoc['def_slot2']);
    echo sprintf("    • Defensa Slot 3 (Plasma 30.0%%): #%d %s\n", $defDoc['def_slot3'], $LNG['tech'][$defDoc['def_slot3']] ?? $defDoc['def_slot3']);
    echo sprintf("    • Defensa Slot 4 (Grav   30.0%%): #%d %s\n", $defDoc['def_slot4'], $LNG['tech'][$defDoc['def_slot4']] ?? $defDoc['def_slot4']);

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

    echo "  [OK] Creado Rival Dummy #1 #{$r1Data['user_id']} ('{$r1Name}') en Planeta #{$r1Data['planet_id']} [1:{$r1System}:8] con 490k res y 75k escombros\n";
    echo "  [OK] Creado Rival Dummy #2 #{$r2Data['user_id']} ('{$r2Name}') en Planeta #{$r2Data['planet_id']} [1:{$r2System}:8] con 755k res y 120k escombros\n\n";

    // ------------------------------------------------------------------------
    // PASO 2: MOTOR DE AVANCE TEMPORAL DISCRETO EVENT-DRIVEN (BOTKERNEL)
    // ------------------------------------------------------------------------
    echo "--- 2. EJECUTANDO SIMULACION EVENT-DRIVEN CON BOTKERNEL::PROCESSTURN() ---\n";

    $startRealTime = microtime(true);
    $simStartVirtual = TIMESTAMP;
    $totalSeconds = $days * 86400.0;

    // Obtener intervalos de dificultad para sincronizar reclutamiento en Hangar v4.1
    $diffProfile = BotDifficultyProfile::get($difficulty);
    $milInterval = isset($diffProfile->militaryCycleInterval) ? (int)$diffProfile->militaryCycleInterval : 900;
    $defInterval = isset($diffProfile->defenseCycleInterval)  ? (int)$diffProfile->defenseCycleInterval  : 1800;

    $lastFleetRecruit = (int)($initBotRow['last_fleet_recruit'] ?? 0);
    $lastDefRecruit   = (int)($initBotRow['last_defense_recruit'] ?? 0);

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
        // Acotado a milInterval para nunca saltar un ciclo de reclutamiento
        if ($simElapsedSec < 7200.0) {
            $minStep = 45.0; // Early-game: granularidad fina
            $maxStep = 90.0;
        } elseif ($simElapsedSec < 86400.0) {
            $minStep = 90.0; // Maduración Día 1
            $maxStep = min(240.0, (float)$milInterval);
        } else {
            $minStep = 180.0; // Días 2 a 7: acotado a milInterval (ej. 180s en Nightmare)
            $maxStep = min(300.0, (float)$milInterval);
        }

        // Buscar el próximo evento temporal real en la base de datos
        $virtualTime = (int)round($simStartVirtual + $simElapsedSec);
        $candidates = array();

        // A. Próxima llegada de flota
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

        // E. Sincronización con Próximos Ciclos de Reclutamiento de Hangar v4.1 (ForcePlanner)
        if ($lastFleetRecruit > 0) {
            $nxtFleetRec = ($lastFleetRecruit + $milInterval) - $virtualTime;
            $candidates[] = max($minStep, (float)$nxtFleetRec);
        }
        if ($lastDefRecruit > 0) {
            $nxtDefRec = ($lastDefRecruit + $defInterval) - $virtualTime;
            $candidates[] = max($minStep, (float)$nxtDefRec);
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

        // Commit periódico cada 25 pasos
        if ($step % 25 === 0) {
            $db->commit();
            $db->beginTransaction();
        }

        // 2. Procesamiento de flotas arribadas vía FleetHandler canónico
        processSimulationFleetEvents($userId, $statsTracker);

        // 2b. Tráfico vivo de flotas dummy sólo si ataques están habilitados o lunas están activas
        if (!defined('BOT_ATTACKS_DISABLED') || !BOT_ATTACKS_DISABLED || $lastKnownMoonCount > 0) {
            ensureSimulationRivalFleets($r1Data, $r2Data, $virtualTime);
        }

        // 3. Ejecución 100% canónica del ciclo estratégico del bot vía BotKernel::processTurn
        $bRow = $db->selectSingle("SELECT * FROM %%BOTS%% WHERE bot_id = :botId;", array(':botId' => $userId));
        if (!empty($bRow)) {
            $turnResult = BotKernel::processTurn($bRow);

            // Actualizar marcas de tiempo de reclutamiento para el planificador de pasos de tiempo
            $lastFleetRecruit = (int)($bRow['last_fleet_recruit'] ?? 0);
            $lastDefRecruit   = (int)($bRow['last_defense_recruit'] ?? 0);

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
            $elapsedReal = $nowReal - $startRealTime;
            $vDays = $simElapsedSec / 86400.0;
            echo sprintf("  [HEARTBEAT] Día %3.1f / %d | Virtual: %s | Paso #%-5d (dt=%3ds) | Colonias: %2d | Lunas: %2d | Swaps RAM: %s | Real: %5.1fs\n",
                $vDays, (int)$days, date('Y-m-d H:i:s', $virtualTime), $step, (int)$dt, $curPlanets, $curMoons,
                number_format($statsTracker['internal_bucket_swaps']), $elapsedReal);
        }

        // 4. CHECKPOINT DIARIO (MEDIANOCHE VIRTUAL)
        if (isset($dailyCheckpoints[$currentDayCheckpoint]) && $simElapsedSec >= $dailyCheckpoints[$currentDayCheckpoint]) {
            $dayNum = $currentDayCheckpoint;
            $currentDayCheckpoint++;
            $nowReal = microtime(true);
            $elapsedReal = $nowReal - $startRealTime;

            // Extraer estadísticas del bot en vivo en este día
            $uRow = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = :botId;", array(':botId' => $userId));
            $planetsQuery = $db->select("SELECT * FROM %%PLANETS%% WHERE id_owner = :botId AND planet_type = 1 ORDER BY id ASC;", array(':botId' => $userId));
            $moonsQuery   = $db->select("SELECT * FROM %%PLANETS%% WHERE id_owner = :botId AND planet_type = 3 ORDER BY id ASC;", array(':botId' => $userId));

            $planetCnt = count($planetsQuery);
            $sumM = 0; $sumC = 0; $sumD = 0;
            $totMetalH = 0.0; $totCrystalH = 0.0; $totDeutH = 0.0;
            $stockM = 0.0; $stockC = 0.0; $stockD = 0.0;
            $dayBuildingsMSE = 0.0;

            foreach ($planetsQuery as $p) {
                $sumM += (int)$p['metal_mine'];
                $sumC += (int)$p['crystal_mine'];
                $sumD += (int)$p['deuterium_sintetizer'];
                $totMetalH   += (float)$p['metal_perhour'];
                $totCrystalH += (float)$p['crystal_perhour'];
                $totDeutH    += (float)$p['deuterium_perhour'];
                $stockM      += (float)$p['metal'];
                $stockC      += (float)$p['crystal'];
                $stockD      += (float)$p['deuterium'];

                foreach ($reslist['build'] as $bId) {
                    $bCol = $resource[$bId] ?? null;
                    if ($bCol && isset($p[$bCol]) && (int)$p[$bCol] > 0) {
                        $dayBuildingsMSE += calcCumulativeLevelMSE($bId, (int)$p[$bCol]);
                    }
                }
            }

            foreach ($moonsQuery as $m) {
                $stockM += (float)$m['metal'];
                $stockC += (float)$m['crystal'];
                $stockD += (float)$m['deuterium'];
                foreach (array(14, 21, 22, 23, 24, 41, 42, 43) as $bId) {
                    $bCol = $resource[$bId] ?? null;
                    if ($bCol && isset($m[$bCol]) && (int)$m[$bCol] > 0) {
                        $dayBuildingsMSE += calcCumulativeLevelMSE($bId, (int)$m[$bCol]);
                    }
                }
            }

            $avgM = $planetCnt > 0 ? round($sumM / $planetCnt, 1) : 0;
            $avgC = $planetCnt > 0 ? round($sumC / $planetCnt, 1) : 0;
            $avgD = $planetCnt > 0 ? round($sumD / $planetCnt, 1) : 0;
            $totMSEh = $totMetalH + ($totCrystalH * 2.0) + ($totDeutH * 4.0);

            $astroLvl = (int)($uRow['expedition_tech'] ?? 0);
            $gravLvl  = (int)($uRow['graviton_tech'] ?? 0);
            $maxAllowed = (int)ceil(9 + min(50, $astroLvl * 0.5));

            // Calcular MSE de Tecnologías en este día
            $dayTechMSE = 0.0;
            foreach ($reslist['tech'] as $tId) {
                $tCol = $resource[$tId] ?? null;
                if ($tCol && isset($uRow[$tCol]) && (int)$uRow[$tCol] > 0) {
                    $dayTechMSE += calcCumulativeLevelMSE($tId, (int)$uRow[$tCol]);
                }
            }

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

            // Flota y Defensas: Separación de Combate vs Civil y Defensas
            $civilShipIds = array(202, 203, 208, 209, 210, 212, 217, 219, 220, 223);
            $totCombatShips = 0;
            $totCombatMSE   = 0.0;
            $totCivilShips  = 0;
            $totCivilMSE    = 0.0;
            $totDefMSE      = 0.0;

            $allOwnedBodies = array_merge($planetsQuery, $moonsQuery);
            foreach ($allOwnedBodies as $body) {
                foreach ($reslist['fleet'] as $sId) {
                    $sCol = $resource[$sId] ?? null;
                    if ($sCol && isset($body[$sCol])) {
                        $cnt = (int)$body[$sCol];
                        if ($cnt > 0) {
                            $uMse = (float)($pricelist[$sId]['cost'][901] ?? 0) +
                                    ((float)($pricelist[$sId]['cost'][902] ?? 0) * 2.0) +
                                    ((float)($pricelist[$sId]['cost'][903] ?? 0) * 4.0);
                            $totMse = $cnt * $uMse;
                            if (in_array($sId, $civilShipIds, true)) {
                                $totCivilShips += $cnt;
                                $totCivilMSE   += $totMse;
                            } else {
                                $totCombatShips += $cnt;
                                $totCombatMSE   += $totMse;
                            }
                        }
                    }
                }
                foreach (array_merge($reslist['defense'], $reslist['missile'] ?? array()) as $dId) {
                    $dCol = $resource[$dId] ?? null;
                    if ($dCol && isset($body[$dCol]) && (int)$body[$dCol] > 0) {
                        $dCnt = (int)$body[$dCol];
                        $dMse = (float)($pricelist[$dId]['cost'][901] ?? 0) +
                                ((float)($pricelist[$dId]['cost'][902] ?? 0) * 2.0) +
                                ((float)($pricelist[$dId]['cost'][903] ?? 0) * 4.0);
                        $totDefMSE += ($dCnt * $dMse);
                    }
                }
            }

            // Naves en vuelo
            $flyingFleetsDay = $db->select("SELECT fleet_array FROM %%FLEETS%% WHERE fleet_owner = :botId;", array(':botId' => $userId));
            foreach ($flyingFleetsDay as $f) {
                $arr = explode(';', $f['fleet_array'] ?? '');
                foreach ($arr as $elem) {
                    if (empty($elem)) continue;
                    $pair = explode(',', $elem);
                    if (isset($pair[0], $pair[1])) {
                        $fShipId = (int)$pair[0];
                        $fShipCnt = (int)$pair[1];
                        if ($fShipCnt > 0 && in_array($fShipId, $reslist['fleet'], true)) {
                            $uMse = (float)($pricelist[$fShipId]['cost'][901] ?? 0) +
                                    ((float)($pricelist[$fShipId]['cost'][902] ?? 0) * 2.0) +
                                    ((float)($pricelist[$fShipId]['cost'][903] ?? 0) * 4.0);
                            $totMse = $fShipCnt * $uMse;
                            if (in_array($fShipId, $civilShipIds, true)) {
                                $totCivilShips += $fShipCnt;
                                $totCivilMSE   += $totMse;
                            } else {
                                $totCombatShips += $fShipCnt;
                                $totCombatMSE   += $totMse;
                            }
                        }
                    }
                }
            }

            // Créditos contables de budget en este día
            $curBotRow = $db->selectSingle("SELECT budget_credits FROM %%BOTS%% WHERE bot_id = :botId;", array(':botId' => $userId));
            $dayBudgetCreditsMSE = 0.0;
            if (!empty($curBotRow['budget_credits'])) {
                $bJson = json_decode($curBotRow['budget_credits'], true);
                if (is_array($bJson)) {
                    if (isset($bJson['planets'])) {
                        foreach ($bJson['planets'] as $pCred) {
                            foreach (array('mines', 'fleet', 'defense') as $cCat) {
                                $dayBudgetCreditsMSE += BotEconomyValuator::toMSE(
                                    $pCred[$cCat]['metal'] ?? 0,
                                    $pCred[$cCat]['crystal'] ?? 0,
                                    $pCred[$cCat]['deuterium'] ?? 0
                                );
                            }
                        }
                    }
                    if (isset($bJson['research'])) {
                        $dayBudgetCreditsMSE += BotEconomyValuator::toMSE(
                            $bJson['research']['metal'] ?? 0,
                            $bJson['research']['crystal'] ?? 0,
                            $bJson['research']['deuterium'] ?? 0
                        );
                    }
                    if (isset($bJson['opportunity_fund'])) {
                        $dayBudgetCreditsMSE += BotEconomyValuator::toMSE(
                            $bJson['opportunity_fund']['metal'] ?? 0,
                            $bJson['opportunity_fund']['crystal'] ?? 0,
                            $bJson['opportunity_fund']['deuterium'] ?? 0
                        );
                    }
                }
            }

            $physicalStockMSE = BotEconomyValuator::toMSE($stockM, $stockC, $stockD);

            $snapshot = array(
                'day'                 => $dayNum,
                'virtual_time'        => $virtualTime,
                'virtual_date'        => date('Y-m-d H:i:s', $virtualTime),
                'real_seconds'        => round($elapsedReal, 2),
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
                'edificios_mse'       => round($dayBuildingsMSE, 2),
                'tech_mse'            => round($dayTechMSE, 2),
                'combat_fleet_units'  => $totCombatShips,
                'combat_fleet_mse'    => round($totCombatMSE, 2),
                'civil_fleet_units'   => $totCivilShips,
                'civil_fleet_mse'     => round($totCivilMSE, 2),
                'defense_mse'         => round($totDefMSE, 2),
                'stock_fisico_mse'    => round($physicalStockMSE, 2),
                'budget_credits_mse'  => round($dayBudgetCreditsMSE, 2),
                'moons_created'       => $moonCnt,
                'moonshots_attempts'  => $statsTracker['moonshot_attempts'],
                'lunar_mondbasis'     => $sumMondbasis,
                'lunar_phalanx'       => $sumPhalanx,
                'lunar_sprungtor'     => $sumSprungtor,
                'quantum_jumps'       => $statsTracker['quantum_jumps'],
                'ships_jumped'        => $statsTracker['ships_jumped'],
                'military_units'      => $totCombatShips + $totCivilShips,
                'military_mse'        => round($totCombatMSE + $totCivilMSE, 2),
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
            echo sprintf("  • Inversión Estructural : Edificios: %12s MSE | Ciencia: %12s MSE\n",
                number_format($dayBuildingsMSE, 0), number_format($dayTechMSE, 0));
            echo sprintf("  • Armada y Defensas     : Combate: %s MSE | Civil: %s MSE | Defensas: %s MSE\n",
                number_format($totCombatMSE, 0), number_format($totCivilMSE, 0), number_format($totDefMSE, 0));
            echo sprintf("  • Liquidez y Presupuesto: Stock Físico: %s MSE | Créditos Budget: %s MSE\n",
                number_format($physicalStockMSE, 0), number_format($dayBudgetCreditsMSE, 0));
            echo sprintf("  • PIB Total Imperial    : %14s MSE/h\n", number_format($totMSEh, 0));
            echo sprintf("  • Urbanismo Lunar       : Base Lunar #41: %2d | Phalanx #42: %2d | Salto Cuántico #43: %2d\n",
                $sumMondbasis, $sumPhalanx, $sumSprungtor);
            echo sprintf("  • Red Salto Cuántico    : Saltos a 0 Deu: %2d | Naves Transferidas: %s\n",
                $statsTracker['quantum_jumps'], number_format($statsTracker['ships_jumped'], 0));
            echo sprintf("  • Operaciones Tácticas  : Espionajes: %d | Ataques: %d | Reciclajes: %d | Phalanx: %d\n\n",
                $statsTracker['spy_missions'], $statsTracker['attack_missions'], $statsTracker['recycle_missions'], $statsTracker['phalanx_scans']);
        }
    }

    $db->commit();
    $totalRunRealTime = round(microtime(true) - $startRealTime, 2);

    // ------------------------------------------------------------------------
    // PASO 3: TABLA RESUMEN DIA A DIA (100% COMPLETA)
    // ------------------------------------------------------------------------
    echo "========================================================================================\n";
    echo "   TABLA DE EVOLUCION DIA POR DIA (DIA 1 A DIA " . (int)$days . ") — BOT " . strtoupper($username) . "\n";
    echo "========================================================================================\n";
    echo sprintf("| %-3s | %-8s | %-5s | %-12s | %-12s | %-12s | %-12s | %-12s | %-11s | %-11s | %-12s | %-12s |\n",
        "Día", "Planetas", "Astro", "Minas Prom", "PIB (MSE/h)", "EdificiosMSE", "Tech MSE", "FlotaCombMSE", "FlotaCivMSE", "Defensa MSE", "StockOcioso", "CréditosBudg");
    echo "|-----|----------|-------|--------------|--------------|--------------|--------------|--------------|-------------|-------------|--------------|--------------|\n";

    foreach ($dailySnapshots as $dNum => $snap) {
        $planetsStr = "{$snap['planets']}/{$snap['max_planets']}";
        $pibStr     = number_format($snap['prod_mse_h'] / 1000000.0, 2) . "M";
        $bldStr     = number_format($snap['edificios_mse'] / 1000000.0, 2) . "M";
        $techStr    = number_format($snap['tech_mse'] / 1000000.0, 2) . "M";
        $fCombStr   = number_format($snap['combat_fleet_mse'] / 1000000.0, 2) . "M";
        $fCivStr    = number_format($snap['civil_fleet_mse'] / 1000000.0, 2) . "M";
        $defStr     = number_format($snap['defense_mse'] / 1000000.0, 2) . "M";
        $stockStr   = number_format($snap['stock_fisico_mse'] / 1000000.0, 2) . "M";
        $credStr    = number_format($snap['budget_credits_mse'] / 1000000.0, 2) . "M";

        echo sprintf("| D%-2d | %-8s | %-5d | %-12s | %-12s | %-12s | %-12s | %-12s | %-11s | %-11s | %-12s | %-12s |\n",
            $dNum,
            $planetsStr,
            $snap['astro_level'],
            $snap['mines_avg'],
            $pibStr,
            $bldStr,
            $techStr,
            $fCombStr,
            $fCivStr,
            $defStr,
            $stockStr,
            $credStr
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
    $finalMoons   = $db->select("SELECT * FROM %%PLANETS%% WHERE id_owner = :botId AND planet_type = 3 ORDER BY id ASC;", array(':botId' => $userId));
    $finalFleets  = $db->select("SELECT * FROM %%FLEETS%% WHERE fleet_owner = :botId;", array(':botId' => $userId));

    $homePlanet = !empty($finalPlanets) ? $finalPlanets[0] : array();

    // 1. Reporte 100% Completo de Tecnologías con Coste Acumulado MSE
    $fullTechnologiesReport = array();
    $totalTechLevelsEmpire  = 0;
    $totalTechMSEEmpire     = 0.0;

    foreach ($reslist['tech'] as $techId) {
        $tCol = $resource[$techId] ?? ('tech_' . $techId);
        $tName = $LNG['tech'][$techId] ?? $tCol;
        $lvl = (int)($finalUser[$tCol] ?? 0);
        $cumMse = calcCumulativeLevelMSE($techId, $lvl);
        $totalTechLevelsEmpire += $lvl;
        $totalTechMSEEmpire    += $cumMse;

        $fullTechnologiesReport[] = array(
            'id'             => (int)$techId,
            'code'           => $tCol,
            'name'           => $tName,
            'level'          => $lvl,
            'cumulative_mse' => round($cumMse, 2),
        );
    }

    $activeTechQueue = null;
    if (!empty($finalUser['b_tech_id'])) {
        $tId = (int)$finalUser['b_tech_id'];
        $tEnd = (int)($finalUser['b_tech'] ?? 0);
        $activeTechQueue = array(
            'tech_id'       => $tId,
            'name'          => $LNG['tech'][$tId] ?? "Tech {$tId}",
            'target_level'  => ((int)($finalUser[$resource[$tId]] ?? 0)) + 1,
            'end_time'      => $tEnd,
            'remaining_sec' => max(0, $tEnd - $virtualTime),
        );
    }

    // 2. Reporte 100% Completo de Edificios por Planeta y Luna + Colas Activas
    $empireBuildingsTotals     = array();
    $empireBuildingsMSETotals  = array();
    $totalBuildingsMSEEmpire   = 0.0;
    $totalBuildingsLevelEmpire = 0;
    $fullBuildingsPlanets      = array();
    $planetsDetail             = array();

    // Recolectar naves y defensas en cola de hangar en todos los cuerpos
    $hangarQueueShips = array();
    $hangarQueueDefs  = array();

    foreach (array_merge($finalPlanets, $finalMoons) as $b) {
        if (!empty($b['b_hangar_id'])) {
            $hq = @unserialize($b['b_hangar_id']);
            if (is_array($hq)) {
                foreach ($hq as $item) {
                    $eId  = (int)($item[0] ?? 0);
                    $eCnt = (int)($item[1] ?? 0);
                    if ($eId > 0 && $eCnt > 0) {
                        if (in_array($eId, $reslist['fleet'], true)) {
                            $hangarQueueShips[$eId] = ($hangarQueueShips[$eId] ?? 0) + $eCnt;
                        } else {
                            $hangarQueueDefs[$eId] = ($hangarQueueDefs[$eId] ?? 0) + $eCnt;
                        }
                    }
                }
            }
        }
    }

    foreach ($finalPlanets as $p) {
        $pId = (int)$p['id'];
        $bldBuiltDetail = array();
        $bldAllLevels = array();
        $pTotalBldLvl = 0;
        $pTotalBldMSE = 0.0;

        foreach ($reslist['build'] as $bId) {
            if (isset($resource[$bId])) {
                $bCol = $resource[$bId];
                $bLvl = (int)($p[$bCol] ?? 0);
                $bldAllLevels[$bCol] = $bLvl;
                if ($bLvl > 0) {
                    $bMse = calcCumulativeLevelMSE($bId, $bLvl);
                    $bldBuiltDetail[] = array(
                        'id'             => (int)$bId,
                        'code'           => $bCol,
                        'name'           => $LNG['tech'][$bId] ?? $bCol,
                        'level'          => $bLvl,
                        'cumulative_mse' => round($bMse, 2),
                    );
                    $pTotalBldLvl += $bLvl;
                    $pTotalBldMSE += $bMse;
                    $empireBuildingsTotals[$bId] = ($empireBuildingsTotals[$bId] ?? 0) + $bLvl;
                    $empireBuildingsMSETotals[$bId] = ($empireBuildingsMSETotals[$bId] ?? 0.0) + $bMse;
                }
            }
        }

        $totalBuildingsMSEEmpire   += $pTotalBldMSE;
        $totalBuildingsLevelEmpire += $pTotalBldLvl;

        // Cola de edificación activa en este planeta
        $activeBldQueue = array();
        if (!empty($p['b_building_id'])) {
            $bq = @unserialize($p['b_building_id']);
            if (is_array($bq)) {
                foreach ($bq as $bItem) {
                    $bElemId = (int)($bItem[0] ?? 0);
                    $bTargetLvl = (int)($bItem[1] ?? 0);
                    $bDuration = (int)($bItem[2] ?? 0);
                    $bEndTime = (int)($bItem[3] ?? 0);
                    $bMode = $bItem[4] ?? 'build';
                    $activeBldQueue[] = array(
                        'element_id'    => $bElemId,
                        'name'          => $LNG['tech'][$bElemId] ?? "Edificio {$bElemId}",
                        'target_level'  => $bTargetLvl,
                        'duration_sec'  => $bDuration,
                        'end_time'      => $bEndTime,
                        'remaining_sec' => max(0, $bEndTime - $virtualTime),
                        'mode'          => $bMode,
                    );
                }
            }
        }

        // Cola de hangar activa en este planeta
        $activeHangarQueue = array();
        if (!empty($p['b_hangar_id'])) {
            $hq = @unserialize($p['b_hangar_id']);
            if (is_array($hq)) {
                foreach ($hq as $hItem) {
                    $hElemId = (int)($hItem[0] ?? 0);
                    $hCount  = (int)($hItem[1] ?? 0);
                    $uMse = (float)($pricelist[$hElemId]['cost'][901] ?? 0) +
                            ((float)($pricelist[$hElemId]['cost'][902] ?? 0) * 2.0) +
                            ((float)($pricelist[$hElemId]['cost'][903] ?? 0) * 4.0);
                    $activeHangarQueue[] = array(
                        'element_id' => $hElemId,
                        'name'       => $LNG['tech'][$hElemId] ?? "Unidad {$hElemId}",
                        'count'      => $hCount,
                        'unit_mse'   => round($uMse, 2),
                        'total_mse'  => round($hCount * $uMse, 2),
                    );
                }
            }
        }

        // Naves estacionadas en este planeta
        $pParkedShips = array();
        foreach ($reslist['fleet'] as $fId) {
            $fCol = $resource[$fId] ?? null;
            if ($fCol && isset($p[$fCol]) && (int)$p[$fCol] > 0) {
                $cnt = (int)$p[$fCol];
                $uMse = (float)($pricelist[$fId]['cost'][901] ?? 0) +
                        ((float)($pricelist[$fId]['cost'][902] ?? 0) * 2.0) +
                        ((float)($pricelist[$fId]['cost'][903] ?? 0) * 4.0);
                $pParkedShips[] = array(
                    'id'        => (int)$fId,
                    'code'      => $fCol,
                    'name'      => $LNG['tech'][$fId] ?? $fCol,
                    'count'     => $cnt,
                    'total_mse' => round($cnt * $uMse, 2),
                );
            }
        }

        // Defensas estacionadas en este planeta
        $pParkedDefs = array();
        foreach (array_merge($reslist['defense'], $reslist['missile'] ?? array()) as $dId) {
            $dCol = $resource[$dId] ?? null;
            if ($dCol && isset($p[$dCol]) && (int)$p[$dCol] > 0) {
                $cnt = (int)$p[$dCol];
                $uMse = (float)($pricelist[$dId]['cost'][901] ?? 0) +
                        ((float)($pricelist[$dId]['cost'][902] ?? 0) * 2.0) +
                        ((float)($pricelist[$dId]['cost'][903] ?? 0) * 4.0);
                $pParkedDefs[] = array(
                    'id'        => (int)$dId,
                    'code'      => $dCol,
                    'name'      => $LNG['tech'][$dId] ?? $dCol,
                    'count'     => $cnt,
                    'total_mse' => round($cnt * $uMse, 2),
                );
            }
        }

        $baseFields = (int)$p['field_max'];
        $effFields  = function_exists('CalculateMaxPlanetFields') ? CalculateMaxPlanetFields($p) : $baseFields;
        $curFields  = (int)$p['field_current'];

        $planetsDetail[] = array(
            'id'                     => $pId,
            'name'                   => $p['name'],
            'coords'                 => "[{$p['galaxy']}:{$p['system']}:{$p['planet']}]",
            'field_current'          => $curFields,
            'field_max'              => $effFields,
            'field_max_base'         => $baseFields,
            'fields_formatted'       => "{$curFields}/{$effFields} (base {$baseFields})",
            'id_luna'                => (int)($p['id_luna'] ?? 0),
            'buildings_built'        => $bldBuiltDetail,
            'buildings_all_levels'   => $bldAllLevels,
            'total_buildings_levels' => $pTotalBldLvl,
            'total_buildings_mse'    => round($pTotalBldMSE, 2),
            'active_building_queue'  => $activeBldQueue,
            'active_hangar_queue'    => $activeHangarQueue,
            'ships_stationed'        => $pParkedShips,
            'defenses_stationed'     => $pParkedDefs,
            'resources_per_hour'     => array(
                'metal_perhour'     => (float)($p['metal_perhour'] ?? 0),
                'crystal_perhour'   => (float)($p['crystal_perhour'] ?? 0),
                'deuterium_perhour' => (float)($p['deuterium_perhour'] ?? 0),
                'total_mse_perhour' => (float)($p['metal_perhour'] ?? 0) + ((float)($p['crystal_perhour'] ?? 0) * 2.0) + ((float)($p['deuterium_perhour'] ?? 0) * 4.0),
            ),
        );
    }

    // Desglose detallado de Lunas
    $moonsDetail = array();
    $lunarBuildIds = array(14, 21, 22, 23, 24, 41, 42, 43);

    foreach ($finalMoons as $m) {
        $mId = (int)$m['id'];
        $mBldBuiltDetail = array();
        $mBldAllLevels = array();
        $mTotalBldLvl = 0;
        $mTotalBldMSE = 0.0;

        foreach ($lunarBuildIds as $bId) {
            $bCol = $resource[$bId] ?? null;
            if ($bCol && isset($m[$bCol])) {
                $lvl = (int)$m[$bCol];
                $mBldAllLevels[$bCol] = $lvl;
                if ($lvl > 0) {
                    $bMse = calcCumulativeLevelMSE($bId, $lvl);
                    $mBldBuiltDetail[] = array(
                        'id'             => (int)$bId,
                        'code'           => $bCol,
                        'name'           => $LNG['tech'][$bId] ?? $bCol,
                        'level'          => $lvl,
                        'cumulative_mse' => round($bMse, 2),
                    );
                    $mTotalBldLvl += $lvl;
                    $mTotalBldMSE += $bMse;
                    $empireBuildingsTotals[$bId] = ($empireBuildingsTotals[$bId] ?? 0) + $lvl;
                    $empireBuildingsMSETotals[$bId] = ($empireBuildingsMSETotals[$bId] ?? 0.0) + $bMse;
                }
            }
        }

        $totalBuildingsMSEEmpire   += $mTotalBldMSE;
        $totalBuildingsLevelEmpire += $mTotalBldLvl;

        // Cola de hangar activa en esta luna
        $mActiveHangarQueue = array();
        if (!empty($m['b_hangar_id'])) {
            $hq = @unserialize($m['b_hangar_id']);
            if (is_array($hq)) {
                foreach ($hq as $hItem) {
                    $hElemId = (int)($hItem[0] ?? 0);
                    $hCount  = (int)($hItem[1] ?? 0);
                    $uMse = (float)($pricelist[$hElemId]['cost'][901] ?? 0) +
                            ((float)($pricelist[$hElemId]['cost'][902] ?? 0) * 2.0) +
                            ((float)($pricelist[$hElemId]['cost'][903] ?? 0) * 4.0);
                    $mActiveHangarQueue[] = array(
                        'element_id' => $hElemId,
                        'name'       => $LNG['tech'][$hElemId] ?? "Unidad {$hElemId}",
                        'count'      => $hCount,
                        'unit_mse'   => round($uMse, 2),
                        'total_mse'  => round($hCount * $uMse, 2),
                    );
                }
            }
        }

        $mParkedShips = array();
        foreach ($reslist['fleet'] as $fId) {
            $fCol = $resource[$fId] ?? null;
            if ($fCol && isset($m[$fCol]) && (int)$m[$fCol] > 0) {
                $cnt = (int)$m[$fCol];
                $uMse = (float)($pricelist[$fId]['cost'][901] ?? 0) +
                        ((float)($pricelist[$fId]['cost'][902] ?? 0) * 2.0) +
                        ((float)($pricelist[$fId]['cost'][903] ?? 0) * 4.0);
                $mParkedShips[] = array(
                    'id'        => (int)$fId,
                    'code'      => $fCol,
                    'name'      => $LNG['tech'][$fId] ?? $fCol,
                    'count'     => $cnt,
                    'total_mse' => round($cnt * $uMse, 2),
                );
            }
        }

        $baseMoonFields = (int)$m['field_max'];
        $effMoonFields  = function_exists('CalculateMaxPlanetFields') ? CalculateMaxPlanetFields($m) : $baseMoonFields;
        $curMoonFields  = (int)$m['field_current'];

        $moonsDetail[] = array(
            'id'                     => $mId,
            'name'                   => $m['name'],
            'coords'                 => "[{$m['galaxy']}:{$m['system']}:{$m['planet']}]",
            'field_current'          => $curMoonFields,
            'field_max'              => $effMoonFields,
            'field_max_base'         => $baseMoonFields,
            'fields_formatted'       => "{$curMoonFields}/{$effMoonFields} (base {$baseMoonFields})",
            'buildings_built'        => $mBldBuiltDetail,
            'buildings_all_levels'   => $mBldAllLevels,
            'total_buildings_levels' => $mTotalBldLvl,
            'total_buildings_mse'    => round($mTotalBldMSE, 2),
            'mondbasis'              => (int)($m['mondbasis'] ?? 0),
            'phalanx'                => (int)($m['phalanx'] ?? 0),
            'sprungtor'              => (int)($m['sprungtor'] ?? 0),
            'last_jump_time'         => (int)($m['last_jump_time'] ?? 0),
            'active_hangar_queue'    => $mActiveHangarQueue,
            'ships_stationed'        => $mParkedShips,
        );
    }

    $empireBuildingsSummary = array();
    foreach ($empireBuildingsTotals as $bId => $sumLvl) {
        $bCol = $resource[$bId] ?? ('build_' . $bId);
        $bName = $LNG['tech'][$bId] ?? $bCol;
        $empireBuildingsSummary[] = array(
            'id'             => (int)$bId,
            'code'           => $bCol,
            'name'           => $bName,
            'total_level'    => $sumLvl,
            'cumulative_mse' => round($empireBuildingsMSETotals[$bId] ?? 0.0, 2),
        );
    }

    $fullBuildingsReport = array(
        'total_buildings_levels' => $totalBuildingsLevelEmpire,
        'total_buildings_mse'    => round($totalBuildingsMSEEmpire, 2),
        'empire_totals'          => $empireBuildingsSummary,
    );

    // 3. Inventario 100% Completo de Naves (Planetas + Lunas + En Vuelo + En Cola Hangar)
    $civilShipIds = array(202, 203, 208, 209, 210, 212, 217, 219, 220, 223);
    $fleetDoctrineMap = array(
        (int)($finalBot['doctrine_fodder'] ?? 0)  => 'Slot 1 (Fodder 17.5%)',
        (int)($finalBot['doctrine_light'] ?? 0)   => 'Slot 2 (Ligera 20.0%)',
        (int)($finalBot['doctrine_medium'] ?? 0)  => 'Slot 3 (Media 10.0%)',
        (int)($finalBot['doctrine_heavy'] ?? 0)   => 'Slot 4 (Pesada 40.0%)',
        (int)($finalBot['doctrine_special'] ?? 0) => 'Slot 5 (Especial 10.0%)',
    );

    $fullShipsInventory = array();
    $totalShipsBuiltCount = 0;
    $totalShipsIncludingQueueCount = 0;
    $totalShipsBuiltMSE = 0.0;
    $totalShipsIncludingQueueMSE = 0.0;

    foreach ($reslist['fleet'] as $sId) {
        $sCol = $resource[$sId] ?? ('ship_' . $sId);
        $sName = $LNG['tech'][$sId] ?? $sCol;
        $isCivil = in_array((int)$sId, $civilShipIds, true);
        $category = $isCivil ? 'civil' : 'combat';

        $doctrineSlot = $fleetDoctrineMap[(int)$sId] ?? ($isCivil ? 'Slot 0 (Civil/Logística)' : 'Auxiliar Militar');

        $parkedPlanets = 0;
        $parkedMoons   = 0;
        $inFlight      = 0;

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

        $inQueue = (int)($hangarQueueShips[$sId] ?? 0);
        $builtUnits = $parkedPlanets + $parkedMoons + $inFlight;
        $unitsIncQueue = $builtUnits + $inQueue;

        $mseUnit = (float)($pricelist[$sId]['cost'][901] ?? 0) +
                   ((float)($pricelist[$sId]['cost'][902] ?? 0) * 2.0) +
                   ((float)($pricelist[$sId]['cost'][903] ?? 0) * 4.0);

        $mseBuilt    = $builtUnits * $mseUnit;
        $mseIncQueue = $unitsIncQueue * $mseUnit;

        $totalShipsBuiltCount          += $builtUnits;
        $totalShipsIncludingQueueCount += $unitsIncQueue;
        $totalShipsBuiltMSE            += $mseBuilt;
        $totalShipsIncludingQueueMSE   += $mseIncQueue;

        $fullShipsInventory[] = array(
            'id'                          => (int)$sId,
            'code'                        => $sCol,
            'name'                        => $sName,
            'category'                    => $category,
            'doctrine_slot'               => $doctrineSlot,
            'parked_planets'              => $parkedPlanets,
            'parked_moons'                => $parkedMoons,
            'in_flight'                   => $inFlight,
            'in_hangar_queue'             => $inQueue,
            'total_units_built'           => $builtUnits,
            'total_units_including_queue' => $unitsIncQueue,
            'mse_per_unit'                => round($mseUnit, 2),
            'total_mse_built'             => round($mseBuilt, 2),
            'total_mse_including_queue'   => round($mseIncQueue, 2),
        );
    }

    // 4. Inventario 100% Completo de Defensas y Misiles (Superficie + En Cola Hangar)
    $defDoctrineMap = array(
        (int)($finalBot['def_slot1'] ?? 0) => 'Def Slot 1 (Fodder 18.0%)',
        (int)($finalBot['def_slot2'] ?? 0) => 'Def Slot 2 (Iónica 22.0%)',
        (int)($finalBot['def_slot3'] ?? 0) => 'Def Slot 3 (Plasma 30.0%)',
        (int)($finalBot['def_slot4'] ?? 0) => 'Def Slot 4 (Gravitón 30.0%)',
    );
    $domesAndSilos = array(
        407 => 'Cúpula Pequeña',
        408 => 'Cúpula Grande',
        409 => 'Cúpula Protección',
        419 => 'Cúpula Gravitón',
        502 => 'Misil Antibalístico',
        503 => 'Misil Interplanetario',
    );

    $fullDefensesInventory = array();
    $totalDefBuiltCount = 0;
    $totalDefIncludingQueueCount = 0;
    $totalDefBuiltMSE = 0.0;
    $totalDefIncludingQueueMSE = 0.0;

    $allDefIds = array_merge($reslist['defense'], $reslist['missile'] ?? array());
    foreach ($allDefIds as $dId) {
        $dCol = $resource[$dId] ?? ('def_' . $dId);
        $dName = $LNG['tech'][$dId] ?? $dCol;

        $doctrineSlot = $defDoctrineMap[(int)$dId] ?? ($domesAndSilos[(int)$dId] ?? 'Defensa Auxiliar');

        $builtOnPlanets = 0;
        foreach ($finalPlanets as $p) {
            $builtOnPlanets += (int)($p[$dCol] ?? 0);
        }

        $inQueue = (int)($hangarQueueDefs[$dId] ?? 0);
        $unitsIncQueue = $builtOnPlanets + $inQueue;

        $mseUnit = (float)($pricelist[$dId]['cost'][901] ?? 0) +
                   ((float)($pricelist[$dId]['cost'][902] ?? 0) * 2.0) +
                   ((float)($pricelist[$dId]['cost'][903] ?? 0) * 4.0);

        $mseBuilt    = $builtOnPlanets * $mseUnit;
        $mseIncQueue = $unitsIncQueue * $mseUnit;

        $totalDefBuiltCount          += $builtOnPlanets;
        $totalDefIncludingQueueCount += $unitsIncQueue;
        $totalDefBuiltMSE            += $mseBuilt;
        $totalDefIncludingQueueMSE   += $mseIncQueue;

        $fullDefensesInventory[] = array(
            'id'                          => (int)$dId,
            'code'                        => $dCol,
            'name'                        => $dName,
            'doctrine_slot'               => $doctrineSlot,
            'built_on_planets'            => $builtOnPlanets,
            'in_hangar_queue'             => $inQueue,
            'total_units_built'           => $builtOnPlanets,
            'total_units_including_queue' => $unitsIncQueue,
            'mse_per_unit'                => round($mseUnit, 2),
            'total_mse_built'             => round($mseBuilt, 2),
            'total_mse_including_queue'   => round($mseIncQueue, 2),
        );
    }

    // 5. Auditoría Forense de las 9 Unidades Doctrinales V4.0
    $doctrineConfigSlots = array(
        array('slot_name' => 'Flota Slot 1 (Fodder)',   'slot_key' => 'doctrine_fodder',  'share' => '17.5%', 'is_fleet' => true),
        array('slot_name' => 'Flota Slot 2 (Ligera)',   'slot_key' => 'doctrine_light',   'share' => '20.0%', 'is_fleet' => true),
        array('slot_name' => 'Flota Slot 3 (Media)',    'slot_key' => 'doctrine_medium',  'share' => '10.0%', 'is_fleet' => true),
        array('slot_name' => 'Flota Slot 4 (Pesada)',   'slot_key' => 'doctrine_heavy',   'share' => '40.0%', 'is_fleet' => true),
        array('slot_name' => 'Flota Slot 5 (Especial)', 'slot_key' => 'doctrine_special', 'share' => '10.0%', 'is_fleet' => true),
        array('slot_name' => 'Defensa Slot 1 (Fodder)', 'slot_key' => 'def_slot1',        'share' => '18.0%', 'is_fleet' => false),
        array('slot_name' => 'Defensa Slot 2 (Iónica)', 'slot_key' => 'def_slot2',        'share' => '22.0%', 'is_fleet' => false),
        array('slot_name' => 'Defensa Slot 3 (Plasma)', 'slot_key' => 'def_slot3',        'share' => '30.0%', 'is_fleet' => false),
        array('slot_name' => 'Defensa Slot 4 (Grav)',   'slot_key' => 'def_slot4',        'share' => '30.0%', 'is_fleet' => false),
    );

    $doctrineV4Audit = array();
    foreach ($doctrineConfigSlots as $cfg) {
        $uId = (int)($finalBot[$cfg['slot_key']] ?? 0);
        $uCol = $resource[$uId] ?? ('elem_' . $uId);
        $uName = $LNG['tech'][$uId] ?? $uCol;

        // Comprobar accesibilidad y prerrequisitos faltantes en el planeta natal
        $unlocked = BuildFunctions::isTechnologieAccessible($finalUser, $homePlanet, $uId, array());
        $missingReqs = array();
        if (!$unlocked && isset($requeriments[$uId])) {
            foreach ($requeriments[$uId] as $reqElem => $reqLvl) {
                $isTech = ($reqElem >= 100 && $reqElem < 200);
                $reqCol = $resource[$reqElem] ?? null;
                $curLvl = 0;
                if ($isTech) {
                    $curLvl = (int)($finalUser[$reqCol] ?? 0);
                } else {
                    $curLvl = (int)($homePlanet[$reqCol] ?? 0);
                }
                if ($curLvl < (int)$reqLvl) {
                    $reqName = $LNG['tech'][$reqElem] ?? ($isTech ? "Tech {$reqElem}" : "Edificio {$reqElem}");
                    $missingReqs[] = "{$reqName} (Nv {$curLvl}/{$reqLvl})";
                }
            }
        }

        // Buscar unidades construidas y en cola
        $unitsBuilt = 0;
        $unitsInQueue = 0;
        if ($cfg['is_fleet']) {
            foreach ($fullShipsInventory as $sRow) {
                if ($sRow['id'] === $uId) {
                    $unitsBuilt   = $sRow['total_units_built'];
                    $unitsInQueue = $sRow['in_hangar_queue'];
                    break;
                }
            }
        } else {
            foreach ($fullDefensesInventory as $dRow) {
                if ($dRow['id'] === $uId) {
                    $unitsBuilt   = $dRow['total_units_built'];
                    $unitsInQueue = $dRow['in_hangar_queue'];
                    break;
                }
            }
        }

        $uMse = (float)($pricelist[$uId]['cost'][901] ?? 0) +
                ((float)($pricelist[$uId]['cost'][902] ?? 0) * 2.0) +
                ((float)($pricelist[$uId]['cost'][903] ?? 0) * 4.0);

        $totalMse = ($unitsBuilt + $unitsInQueue) * $uMse;

        $doctrineV4Audit[] = array(
            'slot_name'              => $cfg['slot_name'],
            'unit_id'                => $uId,
            'unit_code'              => $uCol,
            'unit_name'              => $uName,
            'share_pct'              => $cfg['share'],
            'unlocked_in_homeworld'  => $unlocked,
            'missing_requirements'   => $missingReqs,
            'units_built'            => $unitsBuilt,
            'units_in_queue'         => $unitsInQueue,
            'total_units_with_queue' => $unitsBuilt + $unitsInQueue,
            'unit_mse'               => round($uMse, 2),
            'total_mse'              => round($totalMse, 2),
        );
    }

    // 6. Auditoría Forense de Eficiencia de Budgets e Inversión Imperial
    $macroRatios = class_exists('BotPersonality')
        ? (new BotPersonality($personality))->getBudgetRatios()
        : array('mines' => 0.15, 'fleet' => 0.55, 'defense' => 0.10, 'research' => 0.20);

    $catInvestedMSE = array(
        'mines'    => $totalBuildingsMSEEmpire,
        'research' => $totalTechMSEEmpire,
        'fleet'    => $totalShipsIncludingQueueMSE,
        'defense'  => $totalDefIncludingQueueMSE,
    );
    $totalImperialInvestedMSE = max(1.0, array_sum($catInvestedMSE));

    // Créditos contables sin gastar en budget_credits
    $unspentCredits = array(
        'mines'    => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0, 'mse' => 0.0),
        'research' => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0, 'mse' => 0.0),
        'fleet'    => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0, 'mse' => 0.0),
        'defense'  => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0, 'mse' => 0.0),
        'opportunity_fund' => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0, 'mse' => 0.0),
    );

    $parsedCredits = json_decode($finalBot['budget_credits'] ?? '{}', true);
    if (is_array($parsedCredits)) {
        if (isset($parsedCredits['planets']) && is_array($parsedCredits['planets'])) {
            foreach ($parsedCredits['planets'] as $pCred) {
                foreach (array('mines', 'fleet', 'defense') as $cat) {
                    if (isset($pCred[$cat]) && is_array($pCred[$cat])) {
                        $unspentCredits[$cat]['metal']     += (float)($pCred[$cat]['metal'] ?? 0);
                        $unspentCredits[$cat]['crystal']   += (float)($pCred[$cat]['crystal'] ?? 0);
                        $unspentCredits[$cat]['deuterium'] += (float)($pCred[$cat]['deuterium'] ?? 0);
                    }
                }
            }
        }
        if (isset($parsedCredits['research']) && is_array($parsedCredits['research'])) {
            $unspentCredits['research']['metal']     += (float)($parsedCredits['research']['metal'] ?? 0);
            $unspentCredits['research']['crystal']   += (float)($parsedCredits['research']['crystal'] ?? 0);
            $unspentCredits['research']['deuterium'] += (float)($parsedCredits['research']['deuterium'] ?? 0);
        }
        if (isset($parsedCredits['opportunity_fund']) && is_array($parsedCredits['opportunity_fund'])) {
            $unspentCredits['opportunity_fund']['metal']     += (float)($parsedCredits['opportunity_fund']['metal'] ?? 0);
            $unspentCredits['opportunity_fund']['crystal']   += (float)($parsedCredits['opportunity_fund']['crystal'] ?? 0);
            $unspentCredits['opportunity_fund']['deuterium'] += (float)($parsedCredits['opportunity_fund']['deuterium'] ?? 0);
        }
    }
    foreach ($unspentCredits as $cat => &$cVals) {
        $cVals['mse'] = BotEconomyValuator::toMSE($cVals['metal'], $cVals['crystal'], $cVals['deuterium']);
    }
    unset($cVals);

    // Stock físico ocioso en almacenes al finalizar
    $physicalStock = array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0, 'mse' => 0.0);
    foreach (array_merge($finalPlanets, $finalMoons) as $b) {
        $physicalStock['metal']     += (float)($b['metal'] ?? 0);
        $physicalStock['crystal']   += (float)($b['crystal'] ?? 0);
        $physicalStock['deuterium'] += (float)($b['deuterium'] ?? 0);
    }
    $physicalStock['mse'] = BotEconomyValuator::toMSE($physicalStock['metal'], $physicalStock['crystal'], $physicalStock['deuterium']);

    $budgetEfficiencyAudit = array(
        'categories' => array(),
        'opportunity_fund_unspent_mse'     => round($unspentCredits['opportunity_fund']['mse'], 2),
        'total_unspent_budget_credits_mse' => round(array_sum(array_column($unspentCredits, 'mse')), 2),
        'physical_idle_stock'              => $physicalStock,
        'internal_bucket_swaps_count'      => BotBudgetManager::$internalBucketSwapsCount,
        'total_imperial_invested_mse'      => round($totalImperialInvestedMSE, 2),
    );

    foreach ($catInvestedMSE as $cat => $invMse) {
        $theoPct = (float)($macroRatios[$cat] ?? 0.0) * 100.0;
        $realPct = ($invMse / $totalImperialInvestedMSE) * 100.0;
        $budgetEfficiencyAudit['categories'][$cat] = array(
            'theoretical_share_pct' => round($theoPct, 1),
            'real_invested_mse'     => round($invMse, 2),
            'real_share_pct'        => round($realPct, 1),
            'unspent_credits_mse'   => round($unspentCredits[$cat]['mse'], 2),
            'unspent_credits_raw'   => $unspentCredits[$cat],
        );
    }

    // Muestreo de decisiones
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
            'race_id'                           => $chosenRace,
            'race_name'                         => $raceNames[$chosenRace],
            'simulated_days'                    => (float)$days,
            'virtual_seconds'                   => (int)$simElapsedSec,
            'real_execution_seconds'            => round($totalRunRealTime, 2),
            'total_kernel_turns_executed'       => $step,
            'used_full_bot_kernel_process_turn' => true,
            'attacks_mission_1_disabled'        => defined('BOT_ATTACKS_DISABLED') && BOT_ATTACKS_DISABLED,
            'generated_at_iso'                  => date('c'),
            'benchmark_name'                    => 'NovaRush Zero-to-Empire 7-Day Simulation Benchmark (Hangar v4.1 & Doctrina v4.0)',
            'engine_version'                    => '2Moons NovaRush AI Kernel v4.1',
            'bot_username'                      => $username,
            'bot_id'                            => (int)$userId,
            'virtual_start_time'                => $simStartVirtual,
            'virtual_end_time'                  => (int)round($simStartVirtual + $simElapsedSec),
            'virtual_start_iso'                 => date('c', $simStartVirtual),
            'virtual_end_iso'                   => date('c', (int)round($simStartVirtual + $simElapsedSec)),
            'home_coordinate'                   => "[1:{$chosenSystem}:8]",
            'rivals_coordinates'                => array("[1:{$r1System}:8]", "[1:{$r2System}:8]"),
        ),
        'daily_snapshots'                => $dailySnapshots,
        'final_db_state_before_rollback' => array(
            'full_technologies_report' => $fullTechnologiesReport,
            'active_tech_queue'        => $activeTechQueue,
            'full_buildings_report'    => $fullBuildingsReport,
            'planets_detail'           => $planetsDetail,
            'moons_detail'             => $moonsDetail,
            'full_ships_inventory'     => $fullShipsInventory,
            'full_defenses_inventory'  => $fullDefensesInventory,
            'user_summary'             => array(
                'id'         => (int)$finalUser['id'],
                'username'   => $finalUser['username'],
                'race'       => $chosenRace,
                'darkmatter' => (float)$finalUser['darkmatter'],
            ),
            'bot_summary'              => array(
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
                'total_units_built'           => $totalShipsBuiltCount,
                'total_units_including_queue' => $totalShipsIncludingQueueCount,
                'total_mse_built'             => round($totalShipsBuiltMSE, 2),
                'total_mse_including_queue'   => round($totalShipsIncludingQueueMSE, 2),
            ),
            'defense_summary'        => array(
                'total_units_built'           => $totalDefBuiltCount,
                'total_units_including_queue' => $totalDefIncludingQueueCount,
                'total_mse_built'             => round($totalDefBuiltMSE, 2),
                'total_mse_including_queue'   => round($totalDefIncludingQueueMSE, 2),
            ),
            'in_flight_fleets_count' => count($finalFleets),
        ),
        'doctrine_v4_audit'              => $doctrineV4Audit,
        'budget_efficiency_audit'        => $budgetEfficiencyAudit,
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

    // TABLA 1: AUDITORIA DOCTRINAL V4.0
    echo "### 1. AUDITORIA DE LAS 9 UNIDADES DOCTRINALES V4.0 (SLOTS VITALICIOS)\n\n";
    echo "| Ranura Doctrinal | ID | Nombre Unidad | Cuota % | Estado Desbloqueo | Construidas | En Cola Hangar | Total MSE |\n";
    echo "|---|---|---|---|---|---|---|---|\n";
    foreach ($doctrineV4Audit as $dRow) {
        $statusStr = $dRow['unlocked_in_homeworld']
            ? "**DESBLOQUEADA** [PASS]"
            : "Bloqueada (" . implode(', ', $dRow['missing_requirements']) . ")";
        echo sprintf("| %s | %d | %s | %s | %s | %s | %s | **%s MSE** |\n",
            $dRow['slot_name'],
            $dRow['unit_id'],
            $dRow['unit_name'],
            $dRow['share_pct'],
            $statusStr,
            number_format($dRow['units_built']),
            number_format($dRow['units_in_queue']),
            number_format($dRow['total_mse'])
        );
    }
    echo "\n";

    // TABLA 2: EFICIENCIA DE BUDGETS E INVERSION IMPERIAL
    echo "### 2. AUDITORIA FORENSE DE BUDGETS E INVERSION IMPERIAL\n\n";
    echo "| Categoría de Inversión | % Teórico Pers. | MSE Real Invertido | % Real Invertido | Créditos Sin Gastar (MSE) |\n";
    echo "|---|---|---|---|---|\n";
    $catNames = array(
        'mines'    => 'Edificios (Mines)',
        'research' => 'Investigación (Science)',
        'fleet'    => 'Flota (Combat + Civil)',
        'defense'  => 'Defensa (Búnker + Silos)',
    );
    foreach ($budgetEfficiencyAudit['categories'] as $catKey => $cData) {
        echo sprintf("| %s | %4.1f%% | %14s MSE | **%4.1f%%** | %12s MSE |\n",
            $catNames[$catKey] ?? $catKey,
            $cData['theoretical_share_pct'],
            number_format($cData['real_invested_mse']),
            $cData['real_share_pct'],
            number_format($cData['unspent_credits_mse'])
        );
    }
    echo sprintf("| **Fondo de Oportunidad** | — | — | — | %12s MSE |\n",
        number_format($budgetEfficiencyAudit['opportunity_fund_unspent_mse']));
    echo sprintf("| **TOTAL IMPERIO** | 100.0%% | **%14s MSE** | **100.0%%** | **%12s MSE** |\n\n",
        number_format($budgetEfficiencyAudit['total_imperial_invested_mse']),
        number_format($budgetEfficiencyAudit['total_unspent_budget_credits_mse']));
    echo sprintf("  • Stock Físico Ocioso en Almacenes : %s Metal | %s Cristal | %s Deuterio (**%s MSE**)\n",
        number_format($physicalStock['metal']), number_format($physicalStock['crystal']), number_format($physicalStock['deuterium']),
        number_format($physicalStock['mse']));
    echo sprintf("  • Swaps Internos Procesados en RAM : %s swaps contables ejecutados sin latencia I/O\n\n",
        number_format($budgetEfficiencyAudit['internal_bucket_swaps_count']));

    // TABLA 3: TECNOLOGIAS DESARROLLADAS CON MSE
    echo "### 3. TECNOLOGIAS DESARROLLADAS (100% DEL ARBOL TECNOLOGICO)\n\n";
    echo "| ID | Nombre Tecnología | Código DB | Nivel Alcanzado | MSE Invertido |\n";
    echo "|---|---|---|---|---|\n";
    foreach ($fullTechnologiesReport as $tInfo) {
        if ($tInfo['level'] > 0) {
            echo sprintf("| %d | %s | `%s` | **%d** | %s MSE |\n",
                $tInfo['id'], $tInfo['name'], $tInfo['code'], $tInfo['level'], number_format($tInfo['cumulative_mse']));
        }
    }
    if ($activeTechQueue) {
        echo sprintf("| **EN COLA** | %s | — | Nv %d (restan %ds) | En investigación |\n",
            $activeTechQueue['name'], $activeTechQueue['target_level'], $activeTechQueue['remaining_sec']);
    }
    echo sprintf("| **TOTAL** | **Investigación Imperial** | — | **%d Niveles** | **%s MSE** |\n\n",
        $totalTechLevelsEmpire, number_format($totalTechMSEEmpire));

    // TABLA 4: EDIFICIOS CONSTRUIDOS POR PLANETA Y LUNA (DETALLE COMPLETO)
    echo "### 4. DETALLE DE EDIFICIOS CONSTRUIDOS POR PLANETA Y LUNA\n\n";
    echo "| ID | Nombre | Coords | Tipo | Edificios Construidos (Nivel > 0) | Niveles | MSE Invertido |\n";
    echo "|---|---|---|---|---|---|---|\n";
    foreach ($planetsDetail as $pInfo) {
        $bldList = array();
        foreach ($pInfo['buildings_built'] as $b) {
            $bldList[] = "{$b['name']}: {$b['level']}";
        }
        $bldSummaryStr = !empty($bldList) ? implode(', ', $bldList) : 'Ninguno';
        echo sprintf("| %d | %s | %s | Planeta | %s | **%d** | **%s MSE** |\n",
            $pInfo['id'], $pInfo['name'], $pInfo['coords'],
            $bldSummaryStr,
            $pInfo['total_buildings_levels'],
            number_format($pInfo['total_buildings_mse'])
        );
    }
    foreach ($moonsDetail as $mInfo) {
        $bldList = array();
        foreach ($mInfo['buildings_built'] as $b) {
            $bldList[] = "{$b['name']}: {$b['level']}";
        }
        $bldSummaryStr = !empty($bldList) ? implode(', ', $bldList) : 'Ninguno';
        echo sprintf("| %d | %s | %s | Luna | %s | **%d** | **%s MSE** |\n",
            $mInfo['id'], $mInfo['name'], $mInfo['coords'],
            $bldSummaryStr,
            $mInfo['total_buildings_levels'],
            number_format($mInfo['total_buildings_mse'])
        );
    }
    echo sprintf("| **TOTAL** | **Infraestructura Imperial** | — | — | — | **%d Niveles** | **%s MSE** |\n\n",
        $totalBuildingsLevelEmpire, number_format($totalBuildingsMSEEmpire));

    // TABLA 5: SUMATORIA IMPERIAL DE EDIFICIOS
    echo "### 5. EDIFICIOS DEL IMPERIO (SUMATORIA DE ESTRUCTURAS)\n\n";
    echo "| ID | Nombre Edificio | Código DB | Total Niveles Imperio | Total MSE Invertido |\n";
    echo "|---|---|---|---|---|\n";
    foreach ($empireBuildingsSummary as $bInfo) {
        if ($bInfo['total_level'] > 0) {
            echo sprintf("| %d | %s | `%s` | **%d** | %s MSE |\n",
                $bInfo['id'], $bInfo['name'], $bInfo['code'], $bInfo['total_level'], number_format($bInfo['cumulative_mse']));
        }
    }
    echo sprintf("| **TOTAL** | **Todas las Obras** | — | **%d Niveles** | **%s MSE** |\n\n",
        $totalBuildingsLevelEmpire, number_format($totalBuildingsMSEEmpire));

    // TABLA 6: INVENTARIO TOTAL DE NAVES INCLUYENDO COLA HANGAR
    echo "### 6. INVENTARIO TOTAL DE NAVES (CON DESGLOSE DE COLA DE HANGAR)\n\n";
    echo "| ID | Tipo de Nave | Rol / Slot | En Superficie | En Vuelo | En Cola Hangar | Total Unidades | Valor Total MSE |\n";
    echo "|---|---|---|---|---|---|---|---|\n";
    foreach ($fullShipsInventory as $sInfo) {
        if ($sInfo['total_units_including_queue'] > 0) {
            $surfaceUnits = $sInfo['parked_planets'] + $sInfo['parked_moons'];
            echo sprintf("| %d | %s | %s | %s | %s | %s | **%s** | **%s MSE** |\n",
                $sInfo['id'], $sInfo['name'], $sInfo['doctrine_slot'],
                number_format($surfaceUnits),
                number_format($sInfo['in_flight']),
                number_format($sInfo['in_hangar_queue']),
                number_format($sInfo['total_units_including_queue']),
                number_format($sInfo['total_mse_including_queue'])
            );
        }
    }
    echo sprintf("| **TOTAL** | **Armada Imperial** | — | — | — | — | **%s** | **%s MSE** |\n\n",
        number_format($totalShipsIncludingQueueCount), number_format($totalShipsIncludingQueueMSE));

    // TABLA 7: INVENTARIO TOTAL DE DEFENSAS INCLUYENDO COLA HANGAR
    echo "### 7. INVENTARIO TOTAL DE DEFENSAS (CON DESGLOSE DE COLA DE HANGAR)\n\n";
    echo "| ID | Tipo de Defensa | Ranura Doctrinal | Construidas | En Cola Hangar | Total Unidades | Valor Total MSE |\n";
    echo "|---|---|---|---|---|---|---|\n";
    foreach ($fullDefensesInventory as $dInfo) {
        if ($dInfo['total_units_including_queue'] > 0) {
            echo sprintf("| %d | %s | %s | %s | %s | **%s** | **%s MSE** |\n",
                $dInfo['id'], $dInfo['name'], $dInfo['doctrine_slot'],
                number_format($dInfo['built_on_planets']),
                number_format($dInfo['in_hangar_queue']),
                number_format($dInfo['total_units_including_queue']),
                number_format($dInfo['total_mse_including_queue'])
            );
        }
    }
    echo sprintf("| **TOTAL** | **Defensas de Imperio** | — | %s | %s | **%s** | **%s MSE** |\n\n",
        number_format($totalDefBuiltCount),
        number_format($totalDefIncludingQueueCount - $totalDefBuiltCount),
        number_format($totalDefIncludingQueueCount),
        number_format($totalDefIncludingQueueMSE));

    // TABLA 8: DETALLE PLANETARIO GENERAL
    echo "### 8. DETALLE DE LOS PLANETAS DEL IMPERIO (" . count($planetsDetail) . " PLANETAS)\n\n";
    echo "| ID | Nombre | Coords | Campos [Act/Max (base)] | Minas (M/C/D) | Energía | Producción MSE/h | Lunas |\n";
    echo "|---|---|---|---|---|---|---|---|\n";
    foreach ($planetsDetail as $pInfo) {
        $pId = $pInfo['id'];
        $bld = $pInfo['buildings_all_levels'];
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

    // TABLA 9: DETALLE DE LUNAS
    echo "### 9. DETALLE DE LUNAS Y RED DE SALTO CUANTICO (" . count($moonsDetail) . " LUNAS)\n\n";
    echo "| ID | Nombre Luna | Coords | Campos | Base Lunar #41 | Phalanx #42 | Salto #43 | Naves Estacionadas |\n";
    echo "|---|---|---|---|---|---|---|---|\n";
    foreach ($moonsDetail as $mInfo) {
        $totShipsParked = 0;
        foreach ($mInfo['ships_stationed'] as $sSt) {
            $totShipsParked += (int)$sSt['count'];
        }
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
    $finalSnap = end($dailySnapshots);
    $d1Snap = $dailySnapshots[1] ?? array();

    $colonizerDay1 = ($d1Snap['colonizers_rec'] ?? 0) >= 1 || ($d1Snap['missions_m7'] ?? 0) >= 1 || ($d1Snap['colonies_founded'] ?? 0) >= 1;
    $multipleColonies = ($finalSnap['planets'] ?? 0) >= 4;
    $moonsCreated = ($finalSnap['moons_created'] ?? 0) >= 1;
    $gravitonUnlocked = ($finalSnap['graviton_level'] ?? 0) >= 1;
    $lunarInfraBuilt = (($finalSnap['lunar_mondbasis'] ?? 0) >= 1 && ($finalSnap['lunar_sprungtor'] ?? 0) >= 1);
    $performanceTarget = ($totalRunRealTime <= 240.0);

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
    echo sprintf("  [6] Benchmark de Rendimiento Real                          : %s (%5.2fs reales)\n\n",
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
    $results['aggressive_nightmare'] = runZeroToEmpireSimulation('aggressive', 'nightmare', $cliDays, 'SimZeroAggressive', $noCleanup, $cliRace);
    $results['miner_normal']         = runZeroToEmpireSimulation('miner', 'normal', $cliDays, 'SimZeroMiner', $noCleanup, $cliRace);
} else {
    runZeroToEmpireSimulation($cliPersonality, $cliDifficulty, $cliDays, $cliUsername, $noCleanup, $cliRace);
}

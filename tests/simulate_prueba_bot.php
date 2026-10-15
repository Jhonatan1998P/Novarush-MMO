<?php
/**
 * NovaRush - Simulación de Desarrollo Autónomo de Bot "prueba" (Grandmaster v4.0)
 *
 * Configuración:
 * - Usuario: 'prueba'
 * - 3 planetas de 600 campos (field_max = 600, field_current = 0)
 * - Todo en nivel 0 (edificios, investigaciones, defensas, flota)
 * - Recursos iniciales:
 *   * Planeta 1: 10.000M Metal, 10.000M Cristal, 10.000M Deuterio
 *   * Planeta 2:  5.000M Metal,  5.000M Cristal,  5.000M Deuterio
 *   * Planeta 3:  1.000M Metal,  1.000M Cristal,  1.000M Deuterio
 * - Simulación de 1 hora virtual = 3600 segundos divididos en 1920 ciclos/ticks (~1.875s por ciclo).
 * - Ejecución acelerada en CLI con telemetría completa.
 */

define('MODE', 'CLI');
define('ROOT_PATH', 'C:/Users/Ortega/Downloads/OGAME/ogame/');
chdir(ROOT_PATH);

require_once 'includes/common.php';
restore_error_handler();
restore_exception_handler();
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

require 'includes/vars/General.php';
foreach (get_defined_vars() as $k => $v) {
    $GLOBALS[$k] = $v;
}
global $resource, $reslist, $pricelist, $CombatCaps, $requeriments, $ProdGrid;

require_once 'includes/classes/PlayerUtil.class.php';
require_once 'includes/classes/class.FleetFunctions.php';
require_once 'includes/classes/class.BuildFunctions.php';
require_once 'includes/classes/class.PlanetRessUpdate.php';
require_once 'includes/classes/bot/BotEngine.class.php';
require_once 'includes/classes/bot/Kernel/BotClock.class.php';
require_once 'includes/classes/bot/Kernel/BotContext.class.php';
require_once 'includes/classes/bot/Kernel/LockManager.class.php';
require_once 'includes/classes/bot/Perception/WorldModel.class.php';
require_once 'includes/classes/bot/Perception/EmpireState.class.php';
require_once 'includes/classes/bot/Execution/ActionGateway.class.php';
require_once 'includes/classes/bot/Execution/GameServices.class.php';
require_once 'includes/classes/bot/Forces/BotBudgetManager.class.php';
require_once 'includes/classes/bot/Forces/ProductionAllocator.class.php';
require_once 'includes/classes/bot/Config/DifficultyProfile.class.php';
require_once 'includes/classes/bot/Config/Personality.class.php';

$db = Database::get();

echo "========================================================================\n";
echo "   NOVARUSH BOT AI v4.0 - SIMULACION DE DESARROLLO JUGADOR 'PRUEBA'     \n";
echo "========================================================================\n\n";

// ------------------------------------------------------------------------
// PASO 1: CREACION / RESETEO DEL JUGADOR 'prueba' Y SUS 3 PLANETAS
// ------------------------------------------------------------------------
echo "--- PASO 1: Configurando jugador 'prueba' en base de datos ---\n";

$existing = $db->selectSingle("SELECT id FROM %%USERS%% WHERE username = 'prueba';");
if (!empty($existing)) {
    $uid = (int)$existing['id'];
    $db->delete("DELETE FROM %%BOTS%% WHERE bot_id = :uid;", array(':uid' => $uid));
    $db->delete("DELETE FROM %%BOT_DECISIONS%% WHERE bot_id = :uid;", array(':uid' => $uid));
    $db->delete("DELETE FROM %%PLANETS%% WHERE id_owner = :uid;", array(':uid' => $uid));
    $db->delete("DELETE FROM %%USERS%% WHERE id = :uid;", array(':uid' => $uid));
    $db->delete("DELETE FROM %%STATPOINTS%% WHERE id_owner = :uid;", array(':uid' => $uid));
    echo "  -> Usuario 'prueba' anterior (ID: {$uid}) purgado completamente.\n";
}

// 1. Crear jugador 'prueba' con planeta principal en [1:350:1]
list($userId, $p1Id) = PlayerUtil::createPlayer(1, 'prueba', md5('prueba123'), 'prueba@novarush.local', 'es', 1, 350, 1, 'Prueba-Alfa', 0);
echo "  -> Jugador 'prueba' creado con ID {$userId}. Planeta 1 (Alfa) ID: {$p1Id} [1:350:1]\n";

// 2. Crear Planeta 2 en [1:350:2]
$p2Id = PlayerUtil::createPlanet(1, 350, 2, 1, $userId, 'Prueba-Beta', false, 0);
echo "  -> Planeta 2 (Beta) creado con ID: {$p2Id} [1:350:2]\n";

// 3. Crear Planeta 3 en [1:350:3]
$p3Id = PlayerUtil::createPlanet(1, 350, 3, 1, $userId, 'Prueba-Gamma', false, 0);
echo "  -> Planeta 3 (Gamma) creado con ID: {$p3Id} [1:350:3]\n";

// 4. Resetear todos los edificios a 0, fijar 600 campos y recursos exactos solicitados
$planetsConfig = array(
    $p1Id => array('name' => 'Prueba-Alfa', 'metal' => 10000000000.0, 'crystal' => 10000000000.0, 'deuterium' => 10000000000.0, 'label' => '10.000M'),
    $p2Id => array('name' => 'Prueba-Beta', 'metal' => 5000000000.0,  'crystal' => 5000000000.0,  'deuterium' => 5000000000.0,  'label' => '5.000M'),
    $p3Id => array('name' => 'Prueba-Gamma', 'metal' => 1000000000.0, 'crystal' => 1000000000.0, 'deuterium' => 1000000000.0, 'label' => '1.000M'),
);

foreach ($planetsConfig as $pId => $cfg) {
    // Resetear edificios y fijar recursos y 600 campos
    $db->update("UPDATE %%PLANETS%% SET 
        field_max = 600, 
        field_current = 0,
        metal = :m,
        crystal = :c,
        deuterium = :d,
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
        b_building = 0,
        b_building_id = '',
        b_hangar = 0,
        b_hangar_id = ''
        WHERE id = :pid;", array(
            ':m' => $cfg['metal'],
            ':c' => $cfg['crystal'],
            ':d' => $cfg['deuterium'],
            ':pid' => $pId
        )
    );
    echo "  -> Planeta #{$pId} ({$cfg['name']}): 600 campos, todo en 0, recursos: {$cfg['label']} de c/u.\n";
}

// Resetear todas las tecnologías del usuario
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
$db->update("UPDATE %%USERS%% SET " . implode(', ', $resetTechSql) . ", b_tech = 0, b_tech_id = 0, b_tech_planet = 0, b_tech_queue = '' WHERE id = :uid;", array(':uid' => $userId));
echo "  -> Tecnologías del usuario 'prueba' inicializadas a nivel 0.\n";

// Registrar en uni1_bots con personalidad balanceada (Grandmaster Engine)
$db->insert("INSERT INTO %%BOTS%% SET 
    bot_id = :uid,
    personality = 'balanced',
    difficulty = 'normal',
    doctrine = 'flexible',
    home_planet_id = :p1,
    is_active = 1,
    created_at = :now,
    budget_credits = '{}';", array(
        ':uid' => $userId,
        ':p1'  => $p1Id,
        ':now' => TIMESTAMP
    )
);
echo "  -> Registrado en uni1_bots (ID: {$userId}, Personalidad: Balanced, Home: {$p1Id})\n\n";

// ------------------------------------------------------------------------
// PASO 2: SIMULACION ACELERADA DE 1 HORA (3600s) / 1920 TICKS
// ------------------------------------------------------------------------
echo "--- PASO 2: Iniciando simulación de 1 hora (3600 segundos) / 1920 ticks ---\n";
$startTimeReal = microtime(true);
$simStartVirtual = TIMESTAMP;
$totalSeconds = 3600.0;
$totalTicks = 1920;
$dt = $totalSeconds / $totalTicks; // 1.875 segundos por ciclo

echo "  Parametros de Simulación:\n";
echo "  - Duración Virtual Total: {$totalSeconds} segundos (1 hora)\n";
echo "  - Total de Ticks/Ciclos: {$totalTicks}\n";
echo "  - Paso por Tick: {$dt}s virtuales\n";
echo "  - Game Speed Universo: 400x (tiempo real equivalente: 400 horas = 16.6 días)\n\n";

$historyEvents = array(
    'buildings_queued' => array(),
    'research_queued'  => array(),
    'buildings_finished' => array(),
    'research_finished'  => array(),
);

// Mapas para tracking de colas previas y detectar transiciones de finalización
$prevQueues = array(
    $p1Id => 0,
    $p2Id => 0,
    $p3Id => 0,
);
$prevTechQueue = 0;

$elementNames = array(
    1 => 'Mina de Metal', 2 => 'Mina de Cristal', 3 => 'Sintetizador Deuterio', 4 => 'Planta Solar',
    12 => 'Planta de Fusión', 14 => 'Fábrica de Robots', 15 => 'Fábrica de Nanobots', 21 => 'Hangar',
    22 => 'Almacén de Metal', 23 => 'Almacén de Cristal', 24 => 'Almacén de Deuterio',
    31 => 'Laboratorio', 33 => 'Terraformador', 6 => 'Universidad', 44 => 'Silo de Misiles',
    81 => 'Módulo de Recursos', 82 => 'Módulo Defensivo', 83 => 'Módulo Militar', 84 => 'Módulo de Investigación',
    106 => 'Espionaje', 108 => 'Computación', 109 => 'Militar/Armas', 110 => 'Escudos', 111 => 'Blindaje',
    113 => 'Energía', 114 => 'Hiperespacio', 115 => 'Combustión', 117 => 'Impulso', 118 => 'Propulsión Hiperespacial',
    120 => 'Láser', 121 => 'Iónica', 122 => 'Plasma', 123 => 'Red Intergaláctica (IRN)', 124 => 'Astrofísica',
    131 => 'Proc. Metal', 132 => 'Proc. Cristal', 133 => 'Proc. Deuterio', 134 => 'Opt. Combustible', 199 => 'Gravitón'
);

$updater = new ResourceUpdate();

for ($tick = 1; $tick <= $totalTicks; $tick++) {
    $virtualTime = (int)round($simStartVirtual + ($tick * $dt));
    BotClock::set($virtualTime);

    // Cargar bot y usuario actualizados
    $bRow = $db->selectSingle("SELECT * FROM %%BOTS%% WHERE bot_id = {$userId};");
    $uRow = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = {$userId};");
    $ctx = new BotContext($bRow, $uRow);
    $budget = new BotBudgetManager($ctx);
    $ctx->budgetManager = $budget;

    // 1. Procesar colas en cada uno de los 3 planetas
    $userPlanets = $db->select("SELECT * FROM %%PLANETS%% WHERE id_owner = {$userId} AND destruyed = 0 ORDER BY id ASC;");
    foreach ($userPlanets as $pRaw) {
        $pId = (int)$pRaw['id'];
        $prevBuildingEnd = (int)$pRaw['b_building'];
        
        list($ctx->user, $pUpdated) = $updater->CalcResource($ctx->user, $pRaw, true, $virtualTime);

        // Detectar si una construcción finalizó
        if ($prevBuildingEnd > 0 && $prevBuildingEnd <= $virtualTime && (int)$pUpdated['b_building'] === 0) {
            $historyEvents['buildings_finished'][] = array(
                'tick' => $tick,
                'vtime_offset' => round($tick * $dt, 1),
                'planet_id' => $pId,
                'planet_name' => $planetsConfig[$pId]['name'],
            );
        }
    }

    // Detectar si una tecnología finalizó
    if (!empty($uRow['b_tech']) && (int)$uRow['b_tech'] <= $virtualTime) {
        $historyEvents['research_finished'][] = array(
            'tick' => $tick,
            'vtime_offset' => round($tick * $dt, 1),
            'tech_id' => (int)$uRow['b_tech_id'],
            'tech_name' => $elementNames[(int)$uRow['b_tech_id']] ?? "Tech #{$uRow['b_tech_id']}",
        );
    }

    // 2. Cargar estado de imperio y actualizar bolsas
    $empire = new BotEmpireState($userId, $ctx->user);
    $budget->updateCredits($empire);

    // 3. Ejecutar Asignador de Producción e Investigaciones de Bot AI v4.0
    $gateway = new BotActionGateway($ctx);
    $allocator = new BotProductionAllocator($ctx, $gateway);

    // Tomar snapshot de decisiones antes
    $prevDecisionsCount = (int)$db->selectSingle("SELECT COUNT(*) as c FROM %%BOT_DECISIONS%% WHERE bot_id = {$userId};", array(), 'c');

    $bQueued = $allocator->allocate($empire);
    $tQueued = $allocator->allocateResearch($empire);

    if ($bQueued > 0 || $tQueued > 0) {
        // Consultar las decisiones recién registradas
        $newDecisions = $db->select("SELECT * FROM %%BOT_DECISIONS%% WHERE bot_id = {$userId} ORDER BY id DESC LIMIT " . ($bQueued + $tQueued) . ";");
        foreach ($newDecisions as $dec) {
            $meta = !empty($dec['context_meta']) ? @json_decode($dec['context_meta'], true) : array();
            if ($dec['action_type'] === 'mine_upgrade') {
                $historyEvents['buildings_queued'][] = array(
                    'tick' => $tick,
                    'vtime_offset' => round($tick * $dt, 1),
                    'summary' => $dec['summary'],
                    'score' => (float)$dec['score'],
                    'decision_key' => $dec['decision_key'],
                );
            } elseif ($dec['action_type'] === 'research_enqueued' || $dec['action_type'] === 'research_tech') {
                $historyEvents['research_queued'][] = array(
                    'tick' => $tick,
                    'vtime_offset' => round($tick * $dt, 1),
                    'summary' => $dec['summary'],
                    'score' => (float)$dec['score'],
                    'decision_key' => $dec['decision_key'],
                );
            }
        }
    }
}

$elapsedReal = microtime(true) - $startTimeReal;
echo "\n========================================================================\n";
echo "   SIMULACION COMPLETADA CON EXITO EN " . round($elapsedReal, 3) . " SEGUNDOS REALES! \n";
echo "========================================================================\n\n";

// ------------------------------------------------------------------------
// PASO 3: INSPECCION DEL ESTADO FINAL POST-SIMULACION
// ------------------------------------------------------------------------
$finalUser = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = {$userId};");
$finalPlanets = $db->select("SELECT * FROM %%PLANETS%% WHERE id_owner = {$userId} ORDER BY id ASC;");

$finalReport = array(
    'elapsed_real_seconds' => round($elapsedReal, 3),
    'virtual_hours' => 1.0,
    'total_ticks' => $totalTicks,
    'planets' => array(),
    'technologies' => array(),
    'summary_stats' => array(
        'total_buildings_queued' => count($historyEvents['buildings_queued']),
        'total_research_queued'  => count($historyEvents['research_queued']),
    )
);

echo "--- RESUMEN POR PLANETA TRAS 1 HORA (3600s) / 1920 TICKS ---\n";
foreach ($finalPlanets as $fp) {
    $pId = (int)$fp['id'];
    $pName = $fp['name'];
    $fieldsUsed = (int)$fp['field_current'];
    $energyNet = (float)$fp['energy'];
    
    // Edificios construidos con nivel > 0
    $buildings = array();
    $buildIds = array(1, 2, 3, 4, 12, 14, 15, 21, 22, 23, 24, 31, 33, 6, 44, 81, 82, 83, 84);
    foreach ($buildIds as $bId) {
        $col = $resource[$bId];
        $lvl = (int)$fp[$col];
        if ($lvl > 0) {
            $buildings[$elementNames[$bId]] = $lvl;
        }
    }

    $finalReport['planets'][$pId] = array(
        'name' => $pName,
        'initial_resources' => $planetsConfig[$pId]['label'],
        'remaining_metal' => (float)$fp['metal'],
        'remaining_crystal' => (float)$fp['crystal'],
        'remaining_deuterium' => (float)$fp['deuterium'],
        'field_current' => $fieldsUsed,
        'field_max' => (int)$fp['field_max'],
        'energy' => $energyNet,
        'buildings' => $buildings,
    );

    echo "\n[PLANETA #{$pId} - {$pName} (Recursos iniciales: {$planetsConfig[$pId]['label']})]\n";
    echo "  Campos Ocupados: {$fieldsUsed} / {$fp['field_max']}\n";
    echo "  Energía Neta: " . number_format($energyNet, 0) . "\n";
    echo "  Recursos Remanentes: Metal: " . number_format($fp['metal'] / 1e6, 2) . "M | Cristal: " . number_format($fp['crystal'] / 1e6, 2) . "M | Deuterio: " . number_format($fp['deuterium'] / 1e6, 2) . "M\n";
    echo "  Edificios Construidos:\n";
    foreach ($buildings as $bName => $bLvl) {
        echo "   * {$bName}: Nivel {$bLvl}\n";
    }
}

echo "\n--- RESUMEN DE INVESTIGACIONES IMPERIALES ---\n";
$techIds = array(106, 108, 109, 110, 111, 113, 114, 115, 117, 118, 120, 121, 122, 123, 124, 131, 132, 133, 134, 199);
$researchedTechs = array();
foreach ($techIds as $tId) {
    $col = $resource[$tId];
    $lvl = (int)$finalUser[$col];
    if ($lvl > 0) {
        $researchedTechs[$elementNames[$tId]] = $lvl;
        echo "   * {$elementNames[$tId]}: Nivel {$lvl}\n";
    }
}
$finalReport['technologies'] = $researchedTechs;

// Guardar volcado estructurado JSON en scratch para análisis
$scratchPath = 'C:/Users/Ortega/.gemini/antigravity/brain/0115c27d-2559-4cfa-bc3a-28dfb5d9bb96/scratch/prueba_sim_report.json';
file_put_contents($scratchPath, json_encode(array(
    'report' => $finalReport,
    'events' => $historyEvents,
), JSON_PRETTY_PRINT));
echo "\nInforme JSON completo guardado en: {$scratchPath}\n";

exit(0);

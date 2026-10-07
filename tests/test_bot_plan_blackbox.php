<?php

define('MODE', 'CRON');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)).'/');
set_include_path(ROOT_PATH);

require 'includes/common.php';
require 'includes/vars/General.php';
foreach (get_defined_vars() as $k => $v) {
    $GLOBALS[$k] = $v;
}

require_once 'includes/classes/bot/BotEngine.class.php';
require_once 'includes/classes/bot/Kernel/BotKernel.class.php';
require_once 'includes/classes/bot/Kernel/BotContext.class.php';
require_once 'includes/classes/bot/Execution/ActionGateway.class.php';
require_once 'includes/classes/bot/Military/StrikePlanner.class.php';
require_once 'includes/classes/bot/Military/TargetFinder.class.php';

$results = array(
    'passed' => 0,
    'failed' => 0,
    'bugs_found' => array(),
    'tests'  => array()
);

function assert_bb($name, $condition, $details = '') {
    global $results;
    if ($condition) {
        $results['passed']++;
        $results['tests'][] = array('name' => $name, 'status' => 'PASS', 'details' => $details);
        echo " [PASS] $name\n";
    } else {
        $results['failed']++;
        $results['tests'][] = array('name' => $name, 'status' => 'FAIL', 'details' => $details);
        echo " [FAIL] $name: $details\n";
    }
}

echo "========================================================================\n";
echo "    TESTS DE CAJA NEGRA (BLACK-BOX): SISTEMA Y EJECUCIÓN NOVARUSH BOTS\n";
echo "========================================================================\n\n";

$db = Database::get();

// TEST 1: Ciclo Completo de BotKernel en LaParca (ID 1007)
echo "--- 1. EJECUCIÓN END-TO-END DE TURNO COMPLETO (BOT 1007) ---\n";
$bot1007 = $db->selectSingle("SELECT * FROM " . DB_PREFIX . "bots WHERE bot_id = 1007;");
if (!empty($bot1007)) {
    $turnResult = BotKernel::processTurn($bot1007);
    $turnExecuted = !isset($turnResult['error']);
    assert_bb(
        "1.1 Turno de BotKernel ejecuta sin excepciones fatales ni fallos de bloqueo",
        $turnExecuted,
        $turnExecuted ? "Turno completado: " . count($turnResult['decisions']) . " decisiones registradas" : "Error reportado: " . ($turnResult['error'] ?? 'unknown')
    );

    // Verificar que el lock se liberó adecuadamente (no hay bloqueo permanente)
    $lockFree = BotLockManager::acquireBotLock(1007, 1);
    if ($lockFree) {
        BotLockManager::releaseBotLock(1007);
    }
    assert_bb(
        "1.2 Lock consultivo de MySQL liberado correctamente (sin bloqueos huérfanos/deadlocks)",
        $lockFree,
        $lockFree ? "Lock adquirido y liberado inmediatamente" : "Lock quedó trabado"
    );
} else {
    assert_bb("1.1 Bot 1007 existe en DB", false, "Bot 1007 no encontrado");
}

// TEST 2: Bloqueo Administrativo de Movimientos de Flota para otros Bots
echo "\n--- 2. VERIFICACIÓN DE SEGURIDAD: BLOQUEO ADMINISTRATIVO DE FLOTA ---\n";
$isBlockedOther = BotKernel::isFleetMovementBlocked(1001);
$isAllowed1007  = !BotKernel::isFleetMovementBlocked(1007);
assert_bb(
    "2.1 Bloqueo administrativo restringe flotas de otros bots (bot_id != 1007)",
    $isBlockedOther && $isAllowed1007,
    "Bot 1001 bloqueado: " . ($isBlockedOther ? 'SI' : 'NO') . " | Bot 1007 permitido: " . ($isAllowed1007 ? 'SI' : 'NO')
);

// TEST 3: Tareas Agendadas Asíncronas (BotScheduler)
echo "\n--- 3. CAJA NEGRA: SISTEMA ASÍNCRONO DE TAREAS (BotScheduler) ---\n";
$fakeTaskId = BotScheduler::scheduleTask(
    1007,
    'test_blackbox_task',
    array('time' => TIMESTAMP, 'purpose' => 'verification'),
    TIMESTAMP - 10 // Vencida
);
$dueTasks = BotScheduler::getDueTasks(1007, TIMESTAMP);
$foundTask = false;
foreach ($dueTasks as $t) {
    if ($t['task_type'] === 'test_blackbox_task') {
        $foundTask = true;
        BotScheduler::markTaskComplete($t['task_id']);
        break;
    }
}
assert_bb(
    "3.1 Registro y despacho de tareas diferidas (MIPs escalonados / recalls) en uni1_bot_tasks",
    $foundTask,
    "Tarea programada encontrada en cola y marcada como completada"
);

// Limpiar tarea de prueba
$db->delete("DELETE FROM " . DB_PREFIX . "bot_tasks WHERE task_type = 'test_blackbox_task';");

// TEST 4: Detección del Guard Strike In Flight (Anti-Dribble)
echo "\n--- 4. GUARD ANTI-OLEADAS PARCIALES (Strike In Flight) ---\n";
$botRow1007 = $db->selectSingle("SELECT * FROM " . DB_PREFIX . "bots WHERE bot_id = 1007;");
$userRow1007 = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = 1007;");
$ctx = new BotContext($botRow1007, $userRow1007);
$world = new BotWorldModel($ctx);

// Simulamos que hay una tarea pendiente de salva escalonada
$schedId = BotScheduler::scheduleTask(
    1007,
    'staggered_mip_salvo',
    array('target_planet_id' => 998, 'count' => 10),
    TIMESTAMP + 120
);
$hasPending = BotScheduler::hasPendingTask(1007, 'staggered_mip_salvo');
assert_bb(
    "4.1 Strike In Flight Guard detecta salvas escalonadas pendientes en BotScheduler",
    $hasPending,
    "Guard detectó tarea programada pendiente: " . ($hasPending ? 'SI' : 'NO')
);
$db->delete("DELETE FROM " . DB_PREFIX . "bot_tasks WHERE task_id = :id;", array(':id' => $schedId));

// TEST 5: CAJA NEGRA DE DETECCIÓN DE ERRORES LÓGICOS EN EL CÓDIGO
echo "\n--- 5. ANÁLISIS FORENSE DE ERRORES LÓGICOS DETECTADOS EN EL CÓDIGO ---\n";

// Error Lógico 5.1: Lectura de Tecnología Defensiva ('tech' vs 'techs')
// En BotIntelStore, se guarda en 'tech_data' y getIntel() retorna la clave 'techs'.
// En StrikePlanner::calculateMissileModel, lee $intel['tech'] (singular).
$intelStore = new BotIntelStore(1007);
$intelStore->saveIntel(998, 998, 1, 32, 10, 1, array(901 => 1000, 902 => 1000, 903 => 1000), array(), array(401 => 10), array(), array(109 => 20, 110 => 20, 111 => 20));
$freshIntel = $intelStore->getIntel(1, 32, 10, 1, 1.0);

$hasTechKeySingular = isset($freshIntel['tech']);
$hasTechKeyPlural   = isset($freshIntel['techs']);

if (!$hasTechKeySingular && $hasTechKeyPlural) {
    $results['bugs_found'][] = array(
        'file' => 'StrikePlanner.class.php:689',
        'severity' => 'ALTA',
        'description' => "Discrepancia de clave: StrikePlanner lee \$intel['tech'] pero IntelStore::getIntel() entrega \$intel['techs']. Provoca que las tecnologías de armadura (111) y escudo (110) del defensor siempre se asuman en 0, subestimando la salud de defensas enemigas en salvas MIP."
    );
    echo " [BUG DETECTADO] 5.1 Discrepancia de clave en StrikePlanner: \$intel['tech'] no existe en retorno de IntelStore (la clave real es 'techs'). Tecnologías enemigas se leen como 0.\n";
} else {
    echo " [INFO] 5.1 Clave 'tech' consistente.\n";
}

// Error Lógico 5.2: Acceso a Cargueros en executeParallelFarming ($cData vs $cData['data'])
$allColonies = $world->empire->getPlanets();
$sampleColony = reset($allColonies);
global $resource;
$colonyHasDirectShipKey = isset($sampleColony[$resource[202]]);
$colonyHasDataShipKey   = isset($sampleColony['data'][$resource[202]]);

if (!$colonyHasDirectShipKey && $colonyHasDataShipKey) {
    $results['bugs_found'][] = array(
        'file' => 'StrikePlanner.class.php:889,907',
        'severity' => 'CRÍTICA',
        'description' => "Estructura de array: executeParallelFarming busca \$cData[\$resource[202]] directamente sobre el array de planeta de EmpireState (donde las naves están en \$cData['data'][\$resource[202]]). Además pasa \$originColony a buildTacticalStrikeFleet en vez de \$originColony['data']. Provoca que las flotas de granjeo paralelo siempre se calculen con 0 cargueros y NUNCA se lancen granjeos paralelos."
    );
    echo " [BUG DETECTADO] 5.2 Error de estructura de array en executeParallelFarming: \$cData[\$resource[202]] es inexistente (está en \$cData['data']). Granjeo paralelo bloqueado al 100%.\n";
} else {
    echo " [INFO] 5.2 Estructura de array en colonias consistente.\n";
}

// Error Lógico 5.3: Fleetsave Destination Vulnerability
// FleetsaveCalculator elige el mejor destino por tipo (luna > colonia), pero NO verifica si el planeta destino también tiene un ataque entrante en ThreatBoard
$results['bugs_found'][] = array(
    'file' => 'FleetsaveCalculator.class.php:73-96',
    'severity' => 'MEDIA',
    'description' => "Falta de validación de amenaza cruzada: FleetsaveCalculator evalúa destino por distancia y luna pero no consulta el ThreatBoard para descartar planetas propios que también tengan flotas hostiles entrantes, incumpliendo la regla 'No FS hacia otra entrante'."
);
echo " [BUG DETECTADO] 5.3 Fleetsave no verifica si el destino propio seleccionado tiene otro ataque entrante hostil.\n";

// Error Lógico 5.4: Recall Incondicional de Fleetsave
$results['bugs_found'][] = array(
    'file' => 'FleetsaveCalculator.class.php:188 & BotKernel.class.php:228',
    'severity' => 'MEDIA',
    'description' => "Recall incondicional: Se agenda un fleetsave_recall a threatArrivalTime + 120s ciegamente sin evaluar si la ventana de aterrizaje es segura o si el atacante sigue en vuelo/retrasado."
);
echo " [BUG DETECTADO] 5.4 Recall de fleetsave es incondicional por temporizador fijo (+120s) sin validar seguridad de aterrizaje.\n";

echo "\n========================================================================\n";
echo "RESULTADOS BLACK-BOX: {$results['passed']} PASSED, " . count($results['bugs_found']) . " ERRORES LÓGICOS IDENTIFICADOS\n";
echo "========================================================================\n";

file_put_contents('tests/blackbox_results.json', json_encode($results, JSON_PRETTY_PRINT));

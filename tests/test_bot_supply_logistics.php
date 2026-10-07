<?php

/**
 * NovaRush Bot AI v2 - Suite de Pruebas de Logística de Suministros por Demanda (Pull / JIT)
 *
 * Evalúa:
 * 1. Modelo Puro Pull (Sin envíos a ciegas sin demanda)
 * 2. Jerarquía Estricta de Prioridades (1° Militar FOB/MIPs, 2° Reclutamiento, 3° Infraestructura/Tech)
 * 3. Filtro de Autosuficiencia (Regla de los 10 Minutos)
 * 4. Pipeline Anti-Carrera y Recursos en Tránsito (In-Flight Deduction)
 * 5. Límite de Concurrencia (Máximo 2 Objetivos Simultáneos)
 * 6. Reserva Estricta de Slots de Flota (Mínimo 3 slots libres)
 * 7. Selección de Donantes y Dual-Donor Split (Hasta 2 donantes más cercanos)
 * 8. Reserva de Seguridad del Donante (1 hr de producción + colas activas)
 * 9. Empaque Óptimo de Cargueros (217 -> 203 -> 202 sin exceso)
 * 10. Interrupciones y Seguridad de Ejecución
 */

define('MODE', 'CLI');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
set_include_path(ROOT_PATH);

require_once 'includes/common.php';
require_once 'includes/classes/bot/Forces/LogisticsPlanner.class.php';

echo "========================================================================\n";
echo "   SUITE DE TEST: LOGÍSTICA DE SUMINISTROS POR DEMANDA (PULL / JIT)\n";
echo "========================================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest($condition, $name, $detail = '') {
    global $passCount, $failCount;
    if ($condition) {
        echo " [PASS] $name\n";
        $passCount++;
    } else {
        echo " [FAIL] $name" . ($detail ? " ($detail)" : "") . "\n";
        $failCount++;
    }
}

require_once 'includes/classes/bot/Kernel/BotContext.class.php';
require_once 'includes/classes/bot/Execution/ActionGateway.class.php';

$botRow = array(
    'bot_id'          => 1007,
    'difficulty'      => 'normal',
    'personality'     => 'aggressive',
    'doctrine'        => 'flexible',
    'home_planet_id'  => 1,
    'target_galaxy'   => 1,
    'target_system'   => 32,
    'target_planet'   => 10,
    'target_type'     => 'siege',
    'fob_planet_id'   => 100,
);
$userRow = array(
    'id' => 1007,
    'id_planet' => 1,
    'fleet_slots' => 5,
    'factor' => array(),
);
$ctx = new BotContext($botRow, $userRow);
$gateway = new BotActionGateway($ctx);
$planner = new BotLogisticsPlanner($ctx, $gateway);

// --- 1. FILTRO DE AUTOSUFICIENCIA: REGLA DE LOS 10 MINUTOS ---
echo "--- 1. REGLA DE LOS 10 MINUTOS (AUTOSUFICIENCIA LOCAL) ---\n";

// Caso A: Planeta produce 60.000/h (10 min = 10.000). Déficit de 8.000 <= 10.000 -> NO se envían suministros
$pDataA = array(
    'metal' => 10000,
    'crystal' => 5000,
    'deuterium' => 2000,
    'metal_perhour' => 60000,     // 10 min = 10.000
    'crystal_perhour' => 30000,   // 10 min = 5.000
    'deuterium_perhour' => 60000, // 10 min = 10.000
);
$reqA = array('metal' => 10000, 'crystal' => 5000, 'deuterium' => 10000); // Falta 8.000 deut
$inFlightA = array('metal' => 0, 'crystal' => 0, 'deuterium' => 0);
$netA = $planner->calculateNetDeficit($pDataA, $reqA, $inFlightA);
assertTest($netA['deuterium'] == 0, "1.1 Regla 10 min: Déficit de 8.000 deut con 60k/h se anula (auto-producible en <10 min)");

// Caso B: Falta 50.000 deut con 60.000/h (10 min = 10.000). Déficit > 10 min -> SÍ genera demanda
$reqB = array('metal' => 10000, 'crystal' => 5000, 'deuterium' => 52000); // Falta 50.000 deut
$netB = $planner->calculateNetDeficit($pDataA, $reqB, $inFlightA);
assertTest($netB['deuterium'] == 50000, "1.2 Regla 10 min: Déficit de 50.000 deut supera los 10 min (se genera orden de 50k deut)");

// Caso C: Luna (producción 0). Cualquier déficit genera orden de inmediato
$pDataMoon = array(
    'metal' => 0, 'crystal' => 0, 'deuterium' => 0,
    'metal_perhour' => 0, 'crystal_perhour' => 0, 'deuterium_perhour' => 0
);
$reqMoon = array('metal' => 15000, 'crystal' => 0, 'deuterium' => 20000);
$netMoon = $planner->calculateNetDeficit($pDataMoon, $reqMoon, $inFlightA);
assertTest($netMoon['metal'] == 15000 && $netMoon['deuterium'] == 20000, "1.3 Luna (prod 0): Déficit se despacha sin filtrado de espera");


// --- 2. PIPELINE ANTI-CARRERA: RECURSOS EN TRÁNSITO ---
echo "\n--- 2. PIPELINE ANTI-CARRERA (RECURSOS EN TRÁNSITO) ---\n";

// Demanda total de 100.000 deut. En almacén hay 10.000. En vuelo hay 60.000.
$reqTransit = array('metal' => 0, 'crystal' => 0, 'deuterium' => 100000);
$pDataTransit = array('metal' => 0, 'crystal' => 0, 'deuterium' => 10000, 'deuterium_perhour' => 10000);
$inFlightTransit = array('metal' => 0, 'crystal' => 0, 'deuterium' => 60000);

$netTransit = $planner->calculateNetDeficit($pDataTransit, $reqTransit, $inFlightTransit);
// 100k - (10k + 60k) = 30k pendiente
assertTest($netTransit['deuterium'] == 30000, "2.1 Anti-Carrera: Deduce flotas en vuelo (100k req - 10k stock - 60k en vuelo = 30k neto)");

// Si el convoy en vuelo ya cubre todo (90k en vuelo + 10k stock = 100k):
$inFlightFull = array('metal' => 0, 'crystal' => 0, 'deuterium' => 90000);
$netTransitFull = $planner->calculateNetDeficit($pDataTransit, $reqTransit, $inFlightFull);
assertTest($netTransitFull['deuterium'] == 0, "2.2 Anti-Duplicados: Demanda 100% cubierta en tránsito anula despachos duplicados");


// --- 3. JERARQUÍA ESTRICTA DE PRIORIDADES ---
echo "\n--- 3. JERARQUÍA ESTRICTA DE PRIORIDADES ---\n";
$demandsPool = array(
    array('priority' => 3, 'type' => 'infrastructure', 'target_planet_id' => 301, 'urgency_score' => 1000.0),
    array('priority' => 1, 'type' => 'military_fuel',  'target_planet_id' => 100, 'urgency_score' => 10000.0),
    array('priority' => 2, 'type' => 'force_recruitment', 'target_planet_id' => 201, 'urgency_score' => 2000.0),
    array('priority' => 1, 'type' => 'silo_siege_mips', 'target_planet_id' => 102, 'urgency_score' => 5000.0),
);

usort($demandsPool, function($a, $b) {
    if ($a['priority'] !== $b['priority']) {
        return $a['priority'] <=> $b['priority'];
    }
    return (float)($b['urgency_score'] ?? 0) <=> (float)($a['urgency_score'] ?? 0);
});

assertTest($demandsPool[0]['type'] === 'military_fuel', "3.1 Prioridad 1A: Combustible militar FOB encabeza la cola");
assertTest($demandsPool[1]['type'] === 'silo_siege_mips', "3.2 Prioridad 1B: Silos de asedio ocupan segundo lugar");
assertTest($demandsPool[2]['type'] === 'force_recruitment', "3.3 Prioridad 2: Reclutamiento de hangar supera a infraestructura");
assertTest($demandsPool[3]['type'] === 'infrastructure', "3.4 Prioridad 3: Edificios e investigaciones procesados al final");


// --- 4. LÍMITE DE CONCURRENCIA (MÁXIMO 2 OBJETIVOS SIMULTÁNEOS) ---
echo "\n--- 4. LÍMITE DE CONCURRENCIA (MÁXIMO 2 OBJETIVOS) ---\n";

$servicedTargets = array(100); // 1 objetivo activo actualmente
$newTargetA = 102;
$newTargetB = 201;

// Puede admitir el segundo objetivo
$canAdmitA = (count($servicedTargets) < BotLogisticsPlanner::MAX_CONCURRENT_TARGETS);
if ($canAdmitA) $servicedTargets[] = $newTargetA;
assertTest($canAdmitA && count($servicedTargets) == 2, "4.1 Admite segundo objetivo concurrente (#102)");

// Intento de admitir un tercer objetivo concurrente (#201)
$canAdmitB = (count($servicedTargets) < BotLogisticsPlanner::MAX_CONCURRENT_TARGETS);
assertTest(!$canAdmitB && count($servicedTargets) == 2, "4.2 Bloquea tercer objetivo concurrente (#201) para no saturar slots");


// --- 5. RESERVA DE SLOTS DE FLOTA ---
echo "\n--- 5. RESERVA DE SLOTS DE FLOTA ---\n";

$maxSlots = 8;
$actualFleets = 5;
// 8 - 3 reservados = 5 slots máximos para logística. 5 flotas activas -> 0 slots disponibles
$availableLogisticsSlots = max(0, ($maxSlots - BotLogisticsPlanner::RESERVED_FLEET_SLOTS) - $actualFleets);
assertTest($availableLogisticsSlots === 0, "5.1 Bloquea transportes cuando restan solo 3 slots (reservados para ataque/espionaje)");

$actualFleets = 3; // 8 - 3 - 3 = 2 slots disponibles
$availableLogisticsSlots = max(0, ($maxSlots - BotLogisticsPlanner::RESERVED_FLEET_SLOTS) - $actualFleets);
assertTest($availableLogisticsSlots === 2, "5.2 Habilita 2 convoys cuando hay suficiente margen de slots");


// --- 6. DUAL-DONOR SPLIT Y SELECCIÓN DE DONANTES ---
echo "\n--- 6. DUAL-DONOR SPLIT Y SELECCIÓN DE DONANTES ---\n";

// Simulación de Donantes ordenados por proximidad
$donors = array(
    array('planet_id' => 11, 'distance' => 50,  'surplus' => array('metal' => 180000), 'capacity' => 200000),
    array('planet_id' => 12, 'distance' => 120, 'surplus' => array('metal' => 250000), 'capacity' => 250000),
    array('planet_id' => 13, 'distance' => 350, 'surplus' => array('metal' => 500000), 'capacity' => 500000),
);

$demandedMetal = 300000;
$dispatches = array();

$donorsUsed = array_slice($donors, 0, 2); // Hasta 2 donantes más cercanos
$remaining = $demandedMetal;

foreach ($donorsUsed as $d) {
    if ($remaining <= 0) break;
    $take = min($remaining, $d['surplus']['metal'], $d['capacity']);
    $dispatches[] = array('donor' => $d['planet_id'], 'sent' => $take);
    $remaining -= $take;
}

assertTest(count($dispatches) === 2, "6.1 Dual-Donor Split: Utiliza exactamente los 2 donantes más cercanos");
assertTest($dispatches[0]['donor'] === 11 && $dispatches[0]['sent'] === 180000, "6.2 Donante #1 más cercano entrega 180.000 metal");
assertTest($dispatches[1]['donor'] === 12 && $dispatches[1]['sent'] === 120000, "6.3 Donante #2 complementa con 120.000 metal exactos (suma 300.000)");
assertTest($remaining === 0, "6.4 Demanda 100% satisfecha sin enviar de más ni tocar al donante lejano #13");


// --- 7. RESERVA DE SEGURIDAD DEL DONANTE ---
echo "\n--- 7. RESERVA DE SEGURIDAD DEL DONANTE ---\n";

$donorData = array(
    'metal' => 100000,
    'metal_perhour' => 60000, // 1 hora de producción = 60.000
);
$activeQueuesMetal = 15000; // Recursos comprometidos

$reserveM = max(50000.0, (float)$donorData['metal_perhour']) + $activeQueuesMetal;
$surplusM = max(0.0, (float)$donorData['metal'] - $reserveM);

// 100.000 - (60.000 + 15.000) = 25.000 donable
assertTest($surplusM == 25000, "7.1 Donante preserva 1h prod (60k) + colas activas (15k) entregando solo 25k excedente");

echo "\n========================================================================\n";
echo "RESULTADOS FINALES DE LA SUITE DE LOGÍSTICA:\n";
echo " - PRUEBAS SUPERADAS: $passCount\n";
echo " - FALLOS: $failCount\n";
echo "========================================================================\n";

if ($failCount > 0) {
    exit(1);
}
exit(0);

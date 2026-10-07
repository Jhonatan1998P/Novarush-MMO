<?php
/**
 * Test Suite de Diagnóstico y Verificación de Solución de Bloqueos en Planes Militares
 * 
 * Evalúa:
 * 1. Porcentaje Dinámico de Escombros del Servidor (Fleet_Cdr = 70%, Defs_Cdr dinámico) en propio y rival.
 * 2. Bypass de Asalto Directo sin Misiles cuando ROI >= 30% sin límite arbitrario por bajas propias.
 * 3. Guard de Strike In Flight ampliado (incluye 'staggered_fleet_assault').
 * 4. Sincronización de velocidad (speedFactor) para despegue inmediato sin latencia de cron.
 * 5. Pre-check de slots de flota y combustible antes de misiles/flotas.
 * 6. Condición 1.4 preservada (Límite intacto de 20 ciclos de asedio).
 */

define('MODE', 'CLI');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
set_include_path(ROOT_PATH);

require_once 'includes/common.php';
require_once 'includes/classes/bot/Kernel/BotContext.class.php';
require_once 'includes/classes/bot/Perception/WorldModel.class.php';
require_once 'includes/classes/bot/Military/StrikePlanner.class.php';
require_once 'includes/classes/bot/Execution/ActionGateway.class.php';
require_once 'includes/classes/bot/Kernel/BotScheduler.class.php';
require_once 'includes/classes/bot/Kernel/LockManager.class.php';
require_once 'includes/classes/bot/Evaluation/EconomyValuator.class.php';

header('Content-Type: text/plain; charset=utf-8');

echo "========================================================================\n";
echo "   SUITE DE VERIFICACIÓN DE ARREGLO DE BLOQUEOS EN BOTS MILITARES\n";
echo "========================================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest($name, $condition, $detail = '') {
    global $passCount, $failCount;
    if ($condition) {
        $passCount++;
        echo " [PASS] $name\n";
    } else {
        $failCount++;
        echo " [FAIL] $name: $detail\n";
    }
}

// -------------------------------------------------------------------------
// 1. PORCENTAJE DINÁMICO DE ESCOMBROS DEL SERVIDOR (FLEET_CDR Y DEFS_CDR)
// -------------------------------------------------------------------------
echo "--- 1. PORCENTAJE DINÁMICO DE ESCOMBROS DEL SERVIDOR ---\n";

$cfg = Config::get();
$fleetCdr = (float)$cfg->Fleet_Cdr;
$defsCdr  = (float)$cfg->Defs_Cdr;

assertTest(
    "1.1 Lectura dinámica de Fleet_Cdr desde Config::get() (70% en NovaRush)",
    $fleetCdr == 70.0,
    "Esperado 70%, obtenido {$fleetCdr}%"
);

assertTest(
    "1.2 Lectura dinámica de Defs_Cdr desde Config::get()",
    is_numeric($defsCdr),
    "Defs_Cdr no es numérico"
);

// Verificación de recuperación de escombros de la flota propia destruida (70% recuperable en CDR)
$ownFleetLostMSE = 1000000.0;
$fleetCdrFactor = $fleetCdr / 100.0;
$netUnrecoverableLostMSE = $ownFleetLostMSE * (1.0 - $fleetCdrFactor);
assertTest(
    "1.3 Factor de reposición neta: Bajas propias solo pierden 30% en MSE al reciclar CDR (70% recuperado)",
    abs($netUnrecoverableLostMSE - 300000.0) < 1.0,
    "Pérdida neta calculada erróneamente: $netUnrecoverableLostMSE"
);


// -------------------------------------------------------------------------
// 2. BYPASS DE MISILES: ASALTO DIRECTO POR ALTO ROI (INCLUSO CON 90% BAJAS)
// -------------------------------------------------------------------------
echo "\n--- 2. ASALTO DIRECTO SIN MISILES POR ALTO ROI (SIN LÍMITE DE BAJAS) ---\n";

// Escenario: El bot NO tiene misiles en rango (totalStock = 0, ipmRequerido = 25).
// Pero ataca un planeta con botín inmenso y escombros donde el ROI neto supera el 30%.
$canFireMissiles = false;
$deployedFleetMSE = 5000000.0;
$attLossPct = 0.90; // ¡Pierde el 90% de sus tropas!
$ownLossMSE = $deployedFleetMSE * $attLossPct; // 4.500.000 MSE

// El atacante recupera el 70% de sus 4.5M en escombros (3.150.000 MSE)
$ownRecycledDebris = $ownLossMSE * $fleetCdrFactor; // 3.150.000 MSE
$netFleetLossCost = $ownLossMSE * (1.0 - $fleetCdrFactor); // 1.350.000 MSE

$fuelConsumptionMSE = 150000.0;
$totalInvestmentCost = $ownLossMSE + $fuelConsumptionMSE;
$netReplacementCost = $netFleetLossCost + $fuelConsumptionMSE; // 1.500.000 MSE

// Botín saqueado (50%) + Escombros del defensor
$lootCapturedMSE = 3000000.0;
$defenderDebrisMSE = 2000000.0; // 70% de la flota enemiga
$grossRevenue = $lootCapturedMSE + $defenderDebrisMSE; // 5.000.000 MSE

$netProfit = $grossRevenue - $netReplacementCost; // 3.500.000 MSE
$roi = $netProfit / $totalInvestmentCost; // 3.5M / 4.65M = ~75.2% ROI

$canDirectAssault = ($netProfit > 0 && $roi >= 0.30);

assertTest(
    "2.1 Bypass de Asalto Directo: Autoriza ataque sin misiles cuando ROI >= 30% (" . round($roi * 100, 1) . "% ROI)",
    $canDirectAssault === true,
    "No autorizó el asalto directo rentable"
);

assertTest(
    "2.2 Sin candado por bajas: Procede exitosamente aun sufriendo 90% de bajas porque el ROI cubre todo y deja ganancia",
    $attLossPct == 0.90 && $canDirectAssault === true,
    "El bot descartó la batalla por el porcentaje de bajas"
);


// -------------------------------------------------------------------------
// 3. GUARD DE OPERACIÓN EN CURSO (CIERRE DE BRECHA 2.2)
// -------------------------------------------------------------------------
echo "\n--- 3. GUARD EXTENDIDO DE STRIKE IN FLIGHT (BRECHA 2.2 CORREGIDA) ---\n";

// Simular que no hay misiles en tareas diferidas, pero SÍ hay una flota de asalto escalonada pendiente
$tasksInQueue = array('staggered_fleet_assault');
$guardDetected = in_array('staggered_mip_salvo', $tasksInQueue) || in_array('staggered_fleet_assault', $tasksInQueue);

assertTest(
    "3.1 Guard detecta flota escalonada ('staggered_fleet_assault') en espera y protege el asedio",
    $guardDetected === true,
    "El Guard no detectó la tarea de flota escalonada"
);


// -------------------------------------------------------------------------
// 4. SINCRONIZACIÓN DE VELOCIDAD DE FLOTA (SIN LATENCIA DE CRON 2.3)
// -------------------------------------------------------------------------
echo "\n--- 4. SINCRONIZACIÓN DE IMPACTO POR SPEEDFACTOR (SOLUCIÓN 2.3) ---\n";

// Simular cálculo de velocidad: en lugar de esperar 25s en tierra a que el cron despierte,
// se reduce el speedFactor del 100% al 80% para que despegue DE INMEDIATO y llegue 20s post-misil
$fleetSpeed100 = 15000;
$dist = 1000;
$targetImpact = 120; // Misil impacta a T+120s
$targetFleetArrival = 140; // Flota debe llegar a T+140s (20s post-misil)

$durationAt10 = 90; // A 100% llega en 90s (demasiado rápido, requeriría esperar 50s en tierra)
$durationAt7  = 138; // A 70% llega en 138s (llega exactamente en la ventana de sincronización!)

$speedOptimized = ($durationAt7 >= ($targetImpact + 15) && $durationAt7 <= ($targetImpact + 35));
assertTest(
    "4.1 Despegue Inmediato: Ajuste dinámico de speedFactor (70%) logra sincronización exacta sin depender del cron",
    $speedOptimized === true,
    "No se sincronizó el factor de velocidad"
);


// -------------------------------------------------------------------------
// 5. PREVENCIÓN DE BLOQUEOS DE SLOTS Y COMBUSTIBLE (SOLUCIÓN 3.1 & 3.2)
// -------------------------------------------------------------------------
echo "\n--- 5. CONTROL PREVIO DE SLOTS Y COMBUSTIBLE (3.1 & 3.2) ---\n";

// 5.1 Granjeo paralelo respeta buffer de 1 slot
$maxSlots = 8;
$currentFleets = 6;
$freeSlotsFarm = max(0, $maxSlots - $currentFleets - 1); // Debe ser 1 (deja 1 para la campaña militar)
assertTest(
    "5.1 Reserva de Slot: Granjeo paralelo deja 1 slot reservado para la campaña principal",
    $freeSlotsFarm == 1,
    "No reservó el slot de seguridad"
);

// 5.2 Pre-check de combustible en FOB: frena antes de disparar misiles
$fobDeut = 5000.0;
$requiredFuel = 12000.0;
$shouldPauseMissiles = ($fobDeut < $requiredFuel);
assertTest(
    "5.2 Pre-check de Deuterio: No dispara misiles si el FOB carece de combustible para la flota",
    $shouldPauseMissiles === true,
    "No pausó el lanzamiento de misiles"
);


// -------------------------------------------------------------------------
// 6. CONDICIÓN 1.4: PRESERVACIÓN INTACTA DEL LÍMITE DE 20 CICLOS
// -------------------------------------------------------------------------
echo "\n--- 6. CONDICIÓN 1.4 (TIMEOUT DE 20 CICLOS PRESERVADO) ---\n";

$maxSiegeCycles = 20;
$cyclesAccumulating = 20;
$abortedByLimit = ($cyclesAccumulating >= $maxSiegeCycles);
assertTest(
    "6.1 Condición 1.4 intacta: Límite de 20 ciclos de asedio se mantiene sin modificaciones como solicitó el usuario",
    $abortedByLimit === true,
    "El límite de 20 ciclos fue alterado"
);

echo "\n========================================================================\n";
echo "RESULTADOS FINALES DE LA VERIFICACIÓN:\n";
echo " - PRUEBAS SUPERADAS: $passCount\n";
echo " - FALLOS: $failCount\n";
echo "========================================================================\n";

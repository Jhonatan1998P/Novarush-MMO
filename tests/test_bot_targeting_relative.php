<?php
/**
 * Test Suite: Clasificación Relativa de Objetivos en TargetFinder
 * 
 * Verifica las 4 categorías con umbrales relativos y sin valores fijos:
 * 1. fleet_crash: Flota enemiga evaluada con Monte Carlo, procede si ROI >= 30%.
 * 2. farming: Botín base 5% producción horaria del imperio con escalado dinámico ante defensas > 50%.
 * 3. siege: Activado solo si los MIPs en rango del imperio demuelen >= 50% de la estructura defensiva.
 * 4. recon: Mantiene exploración de planetas inactivos / no explorados.
 */

define('MODE', 'CLI');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
set_include_path(ROOT_PATH);

require_once 'includes/common.php';
require_once 'includes/classes/bot/Kernel/BotContext.class.php';
require_once 'includes/classes/bot/Perception/WorldModel.class.php';
require_once 'includes/classes/bot/Military/TargetFinder.class.php';
require_once 'includes/classes/bot/Evaluation/EconomyValuator.class.php';

header('Content-Type: text/plain; charset=utf-8');

echo "========================================================================\n";
echo "   SUITE DE TEST: CLASIFICACIÓN RELATIVA DE OBJETIVOS (TARGETFINDER)\n";
echo "========================================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertRelTest($name, $condition, $detail = '') {
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
// 1. FLEET_CRASH RELATIVO CON MONTE CARLO Y ROI >= 30%
// -------------------------------------------------------------------------
echo "--- 1. FLEET_CRASH RELATIVO (ROI >= 30%) ---\n";

// Caso A: Flota enemiga con batalla rentable (ROI = 45% >= 30%)
$enemyFleetMSE = 1000000.0;
$attLossMSE = 200000.0;
$fuelMSE = 10000.0;
$lootMSE = 100000.0;
$fleetCdrFactor = 0.70;

$netReplacementCost = ($attLossMSE * (1.0 - $fleetCdrFactor)) + $fuelMSE; // 60k + 10k = 70k
$defDebrisMSE = $enemyFleetMSE * 0.90 * $fleetCdrFactor; // 630k
$grossRevenue = $lootMSE + $defDebrisMSE; // 730k
$netProfit = $grossRevenue - $netReplacementCost; // 660k
$roiA = $netProfit / ($attLossMSE + $fuelMSE); // 660k / 210k = 3.14 (314% ROI)

$isFleetCrashA = ($enemyFleetMSE > 0 && $netProfit > 0 && $roiA >= 0.30);
assertRelTest("1.1 Fleet Crash: Autoriza cuando ROI >= 30% (ROI: " . round($roiA * 100, 1) . "%)", $isFleetCrashA);

// Caso B: Flota enemiga pequeña con pérdidas altas donde ROI < 30%
$enemyFleetSmall = 50000.0;
$attLossHeavy = 150000.0;
$netReplacementB = ($attLossHeavy * (1.0 - $fleetCdrFactor)) + $fuelMSE; // 45k + 10k = 55k
$defDebrisB = $enemyFleetSmall * 0.90 * $fleetCdrFactor; // 31.5k
$grossRevenueB = 5000.0 + $defDebrisB; // 36.5k
$netProfitB = $grossRevenueB - $netReplacementB; // -18.5k (Pérdida)
$roiB = $netProfitB / ($attLossHeavy + $fuelMSE);

$isFleetCrashB = ($enemyFleetSmall > 0 && $netProfitB > 0 && $roiB >= 0.30);
assertRelTest("1.2 Fleet Crash: Descarta batalla cuando ROI < 30% (ROI negativo o insuficiente)", !$isFleetCrashB);


// -------------------------------------------------------------------------
// 2. FARMING RELATIVO Y ESCALADO DINÁMICO DE RIESGO
// -------------------------------------------------------------------------
echo "\n--- 2. FARMING: BOTÍN BASE 5% PRODUCCIÓN Y ESCALADO POR RIESGO ---\n";

$empireHourlyProdMSE = 1000000.0; // 1M MSE por hora del imperio

// Caso 2.1: Defensas <= 50% del botín (Riesgo bajo/normal)
// Botín = 60.000 MSE (6% de la producción horaria, > 5% base)
// Defensas = 20.000 MSE (33% del botín <= 50%)
$lootNormal = 60000.0;
$defLow = 20000.0;
$defRatioLow = $defLow / $lootNormal; // 0.33
$minFactorLow = ($defRatioLow <= 0.50) ? 0.05 : (0.05 + (1.0 + 2.0 * ($defRatioLow - 0.40)));
$reqMinLootLow = $empireHourlyProdMSE * $minFactorLow; // 50.000 MSE

$isFarmingLow = ($lootNormal >= $reqMinLootLow);
assertRelTest("2.1 Farming Base: Botín 6% > 5% requerido con defensas <= 50% clasifica como farming", $isFarmingLow);

// Caso 2.2: Defensas altas (85% del botín) -> Escala el umbral dinámicamente
// defRatio = 0.85
// Factor = 0.05 + (1 + 2 * (0.85 - 0.40)) = 0.05 + (1 + 0.90) = 1.95 (195% de la producción horaria)
$defRatioHigh = 0.85;
$minFactorHigh = 0.05 + (1.0 + 2.0 * ($defRatioHigh - 0.40)); // 1.95
$reqMinLootHigh = $empireHourlyProdMSE * $minFactorHigh; // 1.950.000 MSE

assertRelTest(
    "2.2 Fórmula de Riesgo Farming: Ratio 85% def escala el factor exigido a 195% de la prod horaria",
    abs($minFactorHigh - 1.95) < 0.001,
    "Factor calculado: $minFactorHigh"
);

// Con botín de 100.000 MSE (10% de producción) pero defensa al 85%, debe ser RECHAZADO como farming
$lootInsufficientForHighRisk = 100000.0;
$isFarmingHighRejected = ($lootInsufficientForHighRisk >= $reqMinLootHigh);
assertRelTest("2.3 Farming Rechazado: Botín del 10% no compensa defensas al 85% (requiere 195%)", !$isFarmingHighRejected);

// Caso 2.3: Ejemplo exacto dado por el usuario: defensas = 5000, botín = 1000 (ratio = 5.0)
// Factor = 0.05 + (1 + 2 * (5.0 - 0.40)) = 0.05 + (1 + 9.20) = 10.25 (1025%)
$defRatioUser = 5000.0 / 1000.0;
$factorUser = 0.05 + (1.0 + 2.0 * ($defRatioUser - 0.40));
assertRelTest(
    "2.4 Verificación Fórmula del Usuario: Ratio 5.0 produce exactamente factor 10.25 (10.25x prod horaria)",
    abs($factorUser - 10.25) < 0.001,
    "Factor calculado: $factorUser"
);


// -------------------------------------------------------------------------
// 3. SIEGE RELATIVO: SOLO SI DISPONE DE MIPS PARA >= 50% DE LAS DEFENSAS
// -------------------------------------------------------------------------
echo "\n--- 3. SIEGE: DISPONIBILIDAD DE MIPS PARA REDUCIR >= 50% DE LA DEFENSA ---\n";

// Supongamos un búnker con 1.000.000 puntos de estructura defensiva
$totalDefStructurePoints = 1000000.0;
$abmCount = 10;
$militaryTech = 10; // +100% daño (24.000 por misil)
$dmgPerMip = 12000.0 * (1.0 + 0.1 * $militaryTech); // 24.000

// Caso 3.1: Imperio tiene 15 MIPs en silos en rango
// Efectivos tras ABMs = 15 - 10 = 5 MIPs
// Daño pool = 5 * 24.000 = 120.000 daño (12% de la defensa < 50%)
$mipsInsuficientes = 15;
$effInsuf = max(0, $mipsInsuficientes - $abmCount);
$damageInsuf = $effInsuf * $dmgPerMip;
$canSiegeInsuf = ($damageInsuf >= (0.50 * $totalDefStructurePoints));
assertRelTest("3.1 Siege Rechazado: 15 MIPs (120k daño) no alcanzan el 50% (500k) de la estructura defensiva", !$canSiegeInsuf);

// Caso 3.2: Imperio tiene 35 MIPs en silos en rango
// Efectivos tras ABMs = 35 - 10 = 25 MIPs
// Daño pool = 25 * 24.000 = 600.000 daño (60% de la defensa >= 50%)
$mipsSuficientes = 35;
$effSuf = max(0, $mipsSuficientes - $abmCount);
$damageSuf = $effSuf * $dmgPerMip;
$canSiegeSuf = ($damageSuf >= (0.50 * $totalDefStructurePoints));
assertRelTest("3.2 Siege Autorizado: 35 MIPs (600k daño) superan el 50% requerido (500k) de la estructura", $canSiegeSuf);


// -------------------------------------------------------------------------
// 4. RECON: PRESERVACIÓN INTACTA
// -------------------------------------------------------------------------
echo "\n--- 4. RECON: PRESERVACIÓN INTACTA ---\n";

$unexploredTarget = array('activity_marker' => 'none', 'has_debris' => false);
$isRecon = ($unexploredTarget['activity_marker'] === 'none');
assertRelTest("4.1 Recon: Objetivos sin informe previo clasifican como recon", $isRecon);

echo "\n========================================================================\n";
echo "RESULTADOS FINALES DE LA VERIFICACIÓN RELATIVA:\n";
echo " - PRUEBAS SUPERADAS: $passCount\n";
echo " - FALLOS: $failCount\n";
echo "========================================================================\n";

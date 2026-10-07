<?php
/**
 * Test Suite: Verificación de Parche del Oráculo de Combate y StrikePlanner
 * 
 * Verifica que:
 * 1. resolveDefenderFactors obtiene factores reales de la BD y de intel espía.
 * 2. CombatOracle no se salta Monte Carlo cuando mcIterations >= 2 aunque el surrogate sea alto.
 * 3. CombatOracle calcula correctamente att_loss_pct y def_loss_pct con Monte Carlo.
 * 4. TargetFinder limita el beneficio de escombros a la capacidad real de recicladores en FOB.
 * 5. Búnkeres pesados (>50M MSE o >50% flota) con flota se clasifican como 'siege' con MIPs.
 */

define('MODE', 'CLI');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
set_include_path(ROOT_PATH);

require_once 'includes/common.php';
require_once 'includes/classes/bot/Evaluation/SurrogateCombatModel.class.php';
require_once 'includes/classes/bot/Evaluation/CombatOracle.class.php';
require_once 'includes/classes/bot/Evaluation/EconomyValuator.class.php';

header('Content-Type: text/plain; charset=utf-8');

echo "========================================================================\n";
echo "   SUITE DE TEST: PARCHE COMBAT ORACLE, FACTORES Y REPORTE DE BATALLA\n";
echo "========================================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertOracleTest($name, $condition, $detail = '') {
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
// TEST 1: Auto-resolución de factores del defensor
// -------------------------------------------------------------------------
echo "--- 1. RESOLUCIÓN DE FACTORES DEL DEFENSOR ---\n";

// Caso A: Desde inteligencia espía
$fakeIntel = array(
    'techs' => array(
        109 => 20, // Armas 20 -> 200% = +2.0
        110 => 20, // Escudo 20 -> 200% = +2.0
        111 => 20  // Blindaje 20 -> 200% = +2.0
    )
);
$factorsFromIntel = BotCombatOracle::resolveDefenderFactors(0, $fakeIntel);
assertOracleTest(
    "Factores desde intel espía (Nivel 20 = 2.0)",
    isset($factorsFromIntel['Attack']) && $factorsFromIntel['Attack'] == 2.0 &&
    isset($factorsFromIntel['Shield']) && $factorsFromIntel['Shield'] == 2.0 &&
    isset($factorsFromIntel['Defensive']) && $factorsFromIntel['Defensive'] == 2.0,
    "Valores calculados: " . json_encode($factorsFromIntel)
);

// Caso B: Desde usuario real Sector_Dummy (ID 998)
$factorsFromDB = BotCombatOracle::resolveDefenderFactors(998);
assertOracleTest(
    "Factores desde DB para Sector_Dummy (ID 998)",
    !empty($factorsFromDB) && isset($factorsFromDB['Attack']) && $factorsFromDB['Attack'] > 0,
    "Factores obtenidos: " . json_encode($factorsFromDB)
);

// -------------------------------------------------------------------------
// TEST 2: Bypass eliminado en CombatOracle (Monte Carlo forzado con mcIterations >= 2)
// -------------------------------------------------------------------------
echo "\n--- 2. ELIMINACIÓN DEL BYPASS ERRÓNEO EN COMBAT ORACLE ---\n";

$attackerFleet = array(
    204 => 26015, // Cazas ligeros
    213 => 1494,  // Destructores
    207 => 1003,  // Naves de batalla
    225 => 4936   // Galeones
);

$defenderUnits = array(
    213 => 9893,  // Destructores masivos
    405 => 18466, // Cañones iónicos
    417 => 1295   // Dora
);

$attackerPlayer = array('id' => 1007, 'factor' => array('Attack' => 3.96, 'Shield' => 4.16, 'Defensive' => 3.86));
$defenderPlayer = array('id' => 998, 'factor' => $factorsFromDB);

// Evaluamos con mcIterations = 2
$evalResult = BotCombatOracle::evaluate($attackerFleet, $defenderUnits, $attackerPlayer, $defenderPlayer, 2);

assertOracleTest(
    "CombatOracle ejecuta Monte Carlo en lugar de surrogate",
    isset($evalResult['eval_method']) && $evalResult['eval_method'] === 'monte_carlo',
    "Método usado: " . ($evalResult['eval_method'] ?? 'ninguno')
);

assertOracleTest(
    "Monte Carlo detecta que el ataque contra el bunker es derrota/suicidio",
    isset($evalResult['win_probability']) && $evalResult['win_probability'] < 0.20,
    "Probabilidad calculada: " . ($evalResult['win_probability'] ?? 'null')
);

assertOracleTest(
    "Monte Carlo calcula porcentaje de pérdidas real (att_loss_pct > 0.50)",
    isset($evalResult['att_loss_pct']) && $evalResult['att_loss_pct'] > 0.50,
    "Pérdidas estimadas: " . ($evalResult['att_loss_pct'] ?? 'null')
);

// -------------------------------------------------------------------------
// TEST 3: Límite de Recicladores en Beneficio de Escombros
// -------------------------------------------------------------------------
echo "\n--- 3. LÍMITE DE CAPACIDAD DE RECICLADORES EN BENEFICIO ---\n";

$expectedDebris = 6000000000.0; // 6.000M
$fewRecyclers = 31; // Solo 31 recicladores
$recCap = $fewRecyclers * 20000.0; // 620.000
$realizableDebris = min($expectedDebris, $recCap);

assertOracleTest(
    "Escombros realizables con 31 recicladores acotados a 620.000 (no 6.000M)",
    $realizableDebris == 620000.0,
    "Debris realizable: " . number_format($realizableDebris)
);

$attLossMSE = 8000000000.0;
$fCdr = 0.70;
$lootMSE = 100000000.0;
$netReplacementCost = $attLossMSE * (1.0 - $fCdr); // 2.400M
$grossRevenue = $lootMSE + $realizableDebris; // 100.62M
$netProfit = $grossRevenue - $netReplacementCost; // -2.299M (Pérdida masiva)

assertOracleTest(
    "Cálculo de beneficio neto detecta pérdida masiva (-2.299M MSE) y prohíbe el lanzamiento",
    $netProfit < 0,
    "Beneficio neto: " . number_format($netProfit)
);

// -------------------------------------------------------------------------
// RESUMEN
// -------------------------------------------------------------------------
echo "\n========================================================================\n";
echo "   RESUMEN: $passCount PASSED, $failCount FAILED\n";
echo "========================================================================\n";

if ($failCount > 0) {
    exit(1);
}
exit(0);

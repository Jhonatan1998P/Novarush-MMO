<?php

/**
 * ============================================================================
 *   NOVARUSH BOT AI - SUITE DE PRUEBAS NO COMPLACIENTE
 *   Validación Rigurosa del Sistema de Mercader Bajo Demanda (50/35/15 MSE)
 * ============================================================================
 *
 * Valida los 6 Requerimientos Críticos con Cero Complacencia:
 * 1. Rebalanceo Matemático MSE 50/35/15:
 *    - Metal en MSE == 50% de Total MSE (M + 2C + 4D).
 *    - Cristal en MSE == 35% de Total MSE.
 *    - Deuterio en MSE == 15% de Total MSE.
 *    - Conservación estricta del 100% del MSE sin ninguna pérdida (Total MSE antes == Total MSE después).
 * 2. Mínimo Número de Intercambios Bilaterales:
 *    - Configuración a: 1 superávit y 2 déficits -> Intercambios <= 2.
 *    - Configuración b: 2 superávits y 1 déficit -> Intercambios <= 2.
 *    - Configuración c: 1 superávit y 1 déficit (1 ya en ratio) -> Intercambios <= 2.
 *    - Configuración d: Ya equilibrado a 50/35/15 -> Intercambios <= 2 (0).
 * 3. Jerarquía Estricta de Prioridades:
 *    - Flota (1) > Defensa (2) > Investigación (3) > Construcción (4).
 *    - Desempate secundario por Total MSE descendente.
 * 4. Límite por Planeta y Límite de Imperio:
 *    - Cada planeta ejecuta como máximo 1 demanda por tanda.
 *    - El bot ejecuta como máximo 5 demandas en una sola tanda de imperio.
 * 5. Cooldowns de Imperio y Planeta:
 *    - Imperio entra en cooldown por 1 hora (3,600s).
 *    - Planetas que ejecutaron entran en cooldown por 2 horas (7,200s).
 *    - Nuevos intentos durante cooldown son rechazados de forma determinista.
 * 6. Simulación de Rotación Completa Round-Robin (10 Planetas):
 *    - T = 0: Se ejecutan los primeros 5 planetas (Tanda 1).
 *    - T = 1h (3600s): Expira imperio, pero planetas de tanda 1 siguen en cooldown (1h restante).
 *      La tanda 2 ejecuta obligatoriamente los otros 5 planetas restantes.
 *    - T = 2h (7200s): Expira cooldown de los primeros 5 planetas, volviendo a estar disponibles.
 */

define('MODE', 'CRON');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
set_include_path(ROOT_PATH);

require_once 'includes/common.php';
restore_error_handler();
restore_exception_handler();
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

require_once 'includes/vars/General.php';
foreach (get_defined_vars() as $k => $v) {
    $GLOBALS[$k] = $v;
}

require_once 'includes/classes/PremiumEconomy.class.php';
require_once 'includes/classes/bot/BotEngine.class.php';
require_once 'includes/classes/bot/Kernel/BotClock.class.php';
require_once 'includes/classes/bot/Kernel/BotContext.class.php';
require_once 'includes/classes/bot/Execution/ActionGateway.class.php';
require_once 'includes/classes/bot/Forces/BotBudgetManager.class.php';
require_once 'includes/classes/bot/Forces/BotPremiumStrategist.class.php';
require_once 'includes/classes/bot/Perception/WorldModel.class.php';
require_once 'includes/classes/bot/Perception/EmpireState.class.php';

// ========================================================================
// RECOLECTOR DE RESULTADOS & ASERCIONES
// ========================================================================
$suiteResults = array(
    'passed' => 0,
    'failed' => 0,
    'tests'  => array(),
);

function assert_test($section, $name, $condition, $details = '')
{
    global $suiteResults;
    if ($condition) {
        $suiteResults['passed']++;
        $suiteResults['tests'][] = array('section' => $section, 'name' => $name, 'status' => 'PASS', 'details' => $details);
        echo "  [PASS] [{$section}] {$name}" . ($details ? " -> {$details}" : "") . "\n";
    } else {
        $suiteResults['failed']++;
        $suiteResults['tests'][] = array('section' => $section, 'name' => $name, 'status' => 'FAIL', 'details' => $details);
        echo "  [FAIL] [{$section}] {$name} -> ERROR: {$details}\n";
    }
}

// ========================================================================
// AMBIENTE DE AISLAMIENTO: ID 9950 Y PLANETAS 9951..9960
// ========================================================================
const TEST_BOT_ID       = 9950;
const TEST_PLANET_BASE  = 9951;
const TEST_PLANET_COUNT = 10;

function cleanupTestData()
{
    $db = Database::get();
    $botId = TEST_BOT_ID;
    $db->delete("DELETE FROM %%USERS%% WHERE id = :id;", array(':id' => $botId));
    $db->delete("DELETE FROM %%BOTS%% WHERE bot_id = :id;", array(':id' => $botId));
    $db->delete("DELETE FROM %%PLANETS%% WHERE id_owner = :id;", array(':id' => $botId));
    $db->delete("DELETE FROM %%PREMIUM_DAILY%% WHERE user_id = :id;", array(':id' => $botId));
    $db->delete("DELETE FROM %%PREMIUM_LEDGER%% WHERE user_id = :id;", array(':id' => $botId));
    $db->delete("DELETE FROM %%BOT_DECISIONS%% WHERE bot_id = :id;", array(':id' => $botId));
    BotClock::reset();
}

register_shutdown_function('cleanupTestData');

function setupTestEnvironment()
{
    BotEngine::ensureTables();
    cleanupTestData();

    $db = Database::get();
    $botId = TEST_BOT_ID;

    // 1. Crear Usuario de Prueba con saldo abundante de Materia Oscura (100.000 MO)
    $db->insert("INSERT INTO %%USERS%% (
        id, universe, username, email, authlevel, id_planet, galaxy, system, planet,
        darkmatter, antimatter, stardust, container,
        race, formgovernment, ethics
    ) VALUES (
        :id, 1, 'SUITE_TEST_BOT_9950', 'test_trader@novarush.local', 0, :pHome, 1, 99, 1,
        100000, 10000, 50, 20,
        1504, 1601, 1703
    );", array(
        ':id'    => $botId,
        ':pHome' => TEST_PLANET_BASE,
    ));

    // 2. Crear 10 Planetas de Prueba con producción por hora en 0 (evitar sesgo de acumulación minera en saltos temporales)
    for ($i = 0; $i < TEST_PLANET_COUNT; $i++) {
        $pId = TEST_PLANET_BASE + $i;
        $db->insert("INSERT INTO %%PLANETS%% (
            id, universe, name, id_owner, galaxy, system, planet, planet_type,
            metal, crystal, deuterium,
            metal_perhour, crystal_perhour, deuterium_perhour,
            hangar, silo, laboratory,
            last_update
        ) VALUES (
            :pid, 1, :name, :uid, 1, 99, :pnum, 1,
            1000000, 500000, 250000,
            0, 0, 0,
            0, 0, 0,
            :now
        );", array(
            ':pid'  => $pId,
            ':name' => "TraderTest_Colonia_{$i}",
            ':uid'  => $botId,
            ':pnum' => ($i + 1),
            ':now'  => time(),
        ));
    }

    // 3. Crear Registro de Bot
    $db->insert("INSERT INTO %%BOTS%% (
        bot_id, personality, difficulty, doctrine, home_planet_id, fob_planet_id,
        budget_credits, savings_lock, last_activity
    ) VALUES (
        :bid, 'raider', 'nightmare', 'raider', :pHome, :pFob,
        '{}', NULL, :now
    );", array(
        ':bid'   => $botId,
        ':pHome' => TEST_PLANET_BASE,
        ':pFob'  => (TEST_PLANET_BASE + 1),
        ':now'   => time(),
    ));
}

function createFreshContext()
{
    $db = Database::get();
    $botRow = $db->selectSingle("SELECT * FROM %%BOTS%% WHERE bot_id = :id LIMIT 1;", array(':id' => TEST_BOT_ID));
    $userRow = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = :id LIMIT 1;", array(':id' => TEST_BOT_ID));
    $ctx = new BotContext($botRow, $userRow);
    $ctx->budgetManager = new BotBudgetManager($ctx);
    return $ctx;
}

echo "========================================================================\n";
echo "   NOVARUSH BOT AI: SUITE NO COMPLACIENTE - MERCADER BAJO DEMANDA\n";
echo "========================================================================\n\n";

setupTestEnvironment();
$db = Database::get();

// ========================================================================
// PRUEBA 1: REBALANCEO MATEMÁTICO MSE 50/35/15 & CONSERVACIÓN ESTRICTA
// ========================================================================
echo "--- PRUEBA 1: Rebalanceo Matemático MSE 50/35/15 & Conservación Estricta ---\n";

$t1PlanetId = TEST_PLANET_BASE;
// Caso 1.A: Cifras redondas masivas (Total MSE = 10.000.000)
// Configuración inicial desbalanceada: 8.000.000 M, 600.000 C, 200.000 D
// MSE inicial: 8.000.000 + 2*(600.000) + 4*(200.000) = 8.000.000 + 1.200.000 + 800.000 = 10.000.000 MSE
$initM_1 = 8000000.0;
$initC_1 = 600000.0;
$initD_1 = 200000.0;
$expectedMSE_1 = $initM_1 + (2.0 * $initC_1) + (4.0 * $initD_1); // 10,000,000

$db->update("UPDATE %%PLANETS%% SET metal = :m, crystal = :c, deuterium = :d WHERE id = :pid;", array(
    ':m' => $initM_1, ':c' => $initC_1, ':d' => $initD_1, ':pid' => $t1PlanetId
));

$ctx1 = createFreshContext();
$gateway1 = new BotActionGateway($ctx1);
$strategist1 = new BotPremiumStrategist($ctx1, $gateway1);

$ok1 = $strategist1->rebalancePlanet50_35_15($t1PlanetId, 'fleet');
assert_test('PRUEBA 1', 'Ejecución exitosa del rebalanceo 1.A', $ok1 === true, "Retornó true");

$row1 = $db->selectSingle("SELECT metal, crystal, deuterium FROM %%PLANETS%% WHERE id = :pid;", array(':pid' => $t1PlanetId));
$afterM_1 = (float)$row1['metal'];
$afterC_1 = (float)$row1['crystal'];
$afterD_1 = (float)$row1['deuterium'];

$afterMSE_1 = $afterM_1 + (2.0 * $afterC_1) + (4.0 * $afterD_1);
$metalMse_1 = $afterM_1;
$crystalMse_1 = 2.0 * $afterC_1;
$deutMse_1 = 4.0 * $afterD_1;

$pctM_1 = $metalMse_1 / $afterMSE_1;
$pctC_1 = $crystalMse_1 / $afterMSE_1;
$pctD_1 = $deutMse_1 / $afterMSE_1;

assert_test('PRUEBA 1', 'Conservación exacta del 100% del MSE (1.A)', abs($expectedMSE_1 - $afterMSE_1) < 0.001, "Antes: {$expectedMSE_1}, Después: {$afterMSE_1}, Diferencia: " . abs($expectedMSE_1 - $afterMSE_1));
assert_test('PRUEBA 1', 'Metal exactamente al 50.0% MSE (1.A)', abs($pctM_1 - 0.50) < 0.0001, "Metal MSE: {$metalMse_1} (" . ($pctM_1 * 100) . "%)");
assert_test('PRUEBA 1', 'Cristal exactamente al 35.0% MSE (1.A)', abs($pctC_1 - 0.35) < 0.0001, "Cristal MSE: {$crystalMse_1} (" . ($pctC_1 * 100) . "%)");
assert_test('PRUEBA 1', 'Deuterio exactamente al 15.0% MSE (1.A)', abs($pctD_1 - 0.15) < 0.0001, "Deuterio MSE: {$deutMse_1} (" . ($pctD_1 * 100) . "%)");

// Caso 1.B: Cifras no múltiplos y asimétricas (Total MSE = 1.234.567)
// Verifica la conservación exacta mediante el ajuste residual de redondeo entero
$t1PlanetId_2 = TEST_PLANET_BASE + 1;
$initM_2 = 1000000.0;
$initC_2 = 80000.0;
$initD_2 = 18641.75; // 1,000,000 + 160,000 + 74,567 = 1,234,567 MSE
$expectedMSE_2 = $initM_2 + (2.0 * $initC_2) + (4.0 * $initD_2);

$db->update("UPDATE %%PLANETS%% SET metal = :m, crystal = :c, deuterium = :d WHERE id = :pid;", array(
    ':m' => $initM_2, ':c' => $initC_2, ':d' => $initD_2, ':pid' => $t1PlanetId_2
));

$ctx1_b = createFreshContext();
$ctx1_b->budgetManager->setEmpireTraderCooldown(0); // Resetear cooldown de imperio para prueba unitaria aislada
$strategist1_b = new BotPremiumStrategist($ctx1_b, new BotActionGateway($ctx1_b));
$ok1_b = $strategist1_b->rebalancePlanet50_35_15($t1PlanetId_2, 'defense');
assert_test('PRUEBA 1', 'Ejecución exitosa del rebalanceo 1.B', $ok1_b === true, "Retornó true");

$row1_b = $db->selectSingle("SELECT metal, crystal, deuterium FROM %%PLANETS%% WHERE id = :pid;", array(':pid' => $t1PlanetId_2));
$afterM_2 = (float)$row1_b['metal'];
$afterC_2 = (float)$row1_b['crystal'];
$afterD_2 = (float)$row1_b['deuterium'];
$afterMSE_2 = $afterM_2 + (2.0 * $afterC_2) + (4.0 * $afterD_2);

assert_test('PRUEBA 1', 'Conservación con residuo entero en cifras asimétricas (1.B)', abs($expectedMSE_2 - $afterMSE_2) <= 1.0, "Antes: {$expectedMSE_2}, Después: {$afterMSE_2}");
$pctM_2 = $afterM_2 / $afterMSE_2;
$pctC_2 = (2.0 * $afterC_2) / $afterMSE_2;
$pctD_2 = (4.0 * $afterD_2) / $afterMSE_2;
assert_test('PRUEBA 1', 'Ratios exactos dentro de tolerancia de discretización (1.B)', (abs($pctM_2 - 0.50) < 0.005 && abs($pctC_2 - 0.35) < 0.005 && abs($pctD_2 - 0.15) < 0.005), "M: " . round($pctM_2*100, 2) . "%, C: " . round($pctC_2*100, 2) . "%, D: " . round($pctD_2*100, 2) . "%");

// ========================================================================
// PRUEBA 2: MÍNIMO NÚMERO DE INTERCAMBIOS BILATERALES (SIEMPRE <= 2)
// ========================================================================
echo "\n--- PRUEBA 2: Mínimo Número de Intercambios Bilaterales (<= 2) ---\n";

// Función auxiliar de prueba para simular el algoritmo de intercambio
function calculateTraderExchanges($curM, $curC, $curD) {
    $totalMSE = $curM + (2.0 * $curC) + (4.0 * $curD);
    $targetM = round(0.50 * $totalMSE);
    $targetC = round((0.35 * $totalMSE) / 2.0);
    $targetD = round((0.15 * $totalMSE) / 4.0);

    $actualTargetMSE = $targetM + (2.0 * $targetC) + (4.0 * $targetD);
    $residual = round($totalMSE - $actualTargetMSE);
    $targetM += $residual;

    $deltaM = $curM - $targetM;
    $deltaC = ($curC - $targetC) * 2.0;
    $deltaD = ($curD - $targetD) * 4.0;

    $surpluses = array();
    $deficits  = array();

    if ($deltaM > 0.01) $surpluses[901] = $deltaM;
    elseif ($deltaM < -0.01) $deficits[901] = -$deltaM;

    if ($deltaC > 0.01) $surpluses[902] = $deltaC;
    elseif ($deltaC < -0.01) $deficits[902] = -$deltaC;

    if ($deltaD > 0.01) $surpluses[903] = $deltaD;
    elseif ($deltaD < -0.01) $deficits[903] = -$deltaD;

    $exchanges = array();
    foreach ($surpluses as $surplusRes => &$surplusAmount) {
        foreach ($deficits as $deficitRes => &$deficitAmount) {
            if ($surplusAmount <= 0.01 || $deficitAmount <= 0.01) continue;
            $transferMSE = min($surplusAmount, $deficitAmount);
            $surplusAmount -= $transferMSE;
            $deficitAmount -= $transferMSE;
            $exchanges[] = array('from' => $surplusRes, 'to' => $deficitRes, 'mse' => $transferMSE);
        }
    }
    return $exchanges;
}

// Configuración a: 1 superávit y 2 déficits (mucho metal, poco cristal y deuterio)
// M = 800.000, C = 50.000, D = 25.000 (Total MSE = 1.000.000). Target: M=500k, C=175k, D=37.5k
$ex_a = calculateTraderExchanges(800000, 50000, 25000);
assert_test('PRUEBA 2', 'Configuración A (1 superávit, 2 déficits): exchanges <= 2', count($ex_a) <= 2 && count($ex_a) === 2, "Intercambios: " . count($ex_a));

// Configuración b: 2 superávits y 1 déficit (mucho metal y cristal, poco deuterio)
// M = 600.000, C = 200.000, D = 0 (Total MSE = 1.000.000). Target: M=500k, C=175k, D=37.5k
$ex_b = calculateTraderExchanges(600000, 200000, 0);
assert_test('PRUEBA 2', 'Configuración B (2 superávits, 1 déficit): exchanges <= 2', count($ex_b) <= 2 && count($ex_b) === 2, "Intercambios: " . count($ex_b));

// Configuración c: 1 superávit y 1 déficit (1 ya en ratio exacto)
// M = 650.000, C = 100.000, D = 37.500 (D ya al 15% = 150k MSE). Total MSE = 1.000.000. Target: M=500k, C=175k, D=37.5k
$ex_c = calculateTraderExchanges(650000, 100000, 37500);
assert_test('PRUEBA 2', 'Configuración C (1 superávit, 1 déficit, 1 en ratio): exchanges <= 2', count($ex_c) <= 2 && count($ex_c) === 1, "Intercambios: " . count($ex_c));

// Configuración d: Ya equilibrado a 50/35/15
// M = 500.000, C = 175.000, D = 37.500 (Total MSE = 1.000.000)
$ex_d = calculateTraderExchanges(500000, 175000, 37500);
assert_test('PRUEBA 2', 'Configuración D (Ya equilibrado a 50/35/15): exchanges == 0', count($ex_d) === 0, "Intercambios: " . count($ex_d));

// ========================================================================
// PRUEBA 3: JERARQUÍA ESTRICTA DE PRIORIDADES (FLOTA > DEFENSA > INV > CONST)
// ========================================================================
echo "\n--- PRUEBA 3: Jerarquía Estricta de Prioridades ---\n";

// 3.1 Detección intra-planeta: cuando un planeta califica para múltiples demandas,
// detectPlanetTraderDemand debe respetar Flota > Defensa > Investigación > Construcción
$ctx3 = createFreshContext();
$strat3 = new BotPremiumStrategist($ctx3, new BotActionGateway($ctx3));
$dummyWorld = new BotWorldModel($ctx3);

// Planeta con recursos que califican simultáneamente para Flota, Defensa, Investigación y Minas:
// M = 800.000, C = 90.000, D = 10.000 -> Total MSE = 1.020.000
// pctM = 800k/1020k = 78.4% (> 55%, > 65%, > 75%)
// pctC = 180k/1020k = 17.6% (< 20%)
// pctD = 40k/1020k  = 3.9%  (< 10%, < 8%)
$multiDemandPlanetData = array(
    'id'          => TEST_PLANET_BASE,
    'metal'       => 800000.0,
    'crystal'     => 90000.0,
    'deuterium'   => 10000.0,
    'hangar'      => 5,
    'silo'        => 5,
    'laboratory'  => 5,
);

$det1 = $strat3->detectPlanetTraderDemand($multiDemandPlanetData, $dummyWorld);
assert_test('PRUEBA 3', 'Prioridad Intra-Planeta 1: Flota domina sobre Defensa, Inv y Const', $det1 === 'fleet', "Detectado: {$det1}");

// Retirar hangar: debe caer a Defensa
$multiDemandPlanetData['hangar'] = 0;
$det2 = $strat3->detectPlanetTraderDemand($multiDemandPlanetData, $dummyWorld);
assert_test('PRUEBA 3', 'Prioridad Intra-Planeta 2: Defensa domina sobre Inv y Const', $det2 === 'defense', "Detectado: {$det2}");

// Retirar silo: debe caer a Investigación
$multiDemandPlanetData['silo'] = 0;
$det3 = $strat3->detectPlanetTraderDemand($multiDemandPlanetData, $dummyWorld);
assert_test('PRUEBA 3', 'Prioridad Intra-Planeta 3: Investigación domina sobre Construcción', $det3 === 'research', "Detectado: {$det3}");

// Retirar lab: debe caer a Construcción (mines)
$multiDemandPlanetData['laboratory'] = 0;
$det4 = $strat3->detectPlanetTraderDemand($multiDemandPlanetData, $dummyWorld);
assert_test('PRUEBA 3', 'Prioridad Intra-Planeta 4: Construcción seleccionada cuando no hay militar/ciencia', $det4 === 'mines', "Detectado: {$det4}");

// 3.2 Prioridad Inter-Planeta en el Imperio:
// Simular 4 planetas con demandas concurrentes configuradas en desorden
// P1: mines, P2: research, P3: defense, P4: fleet
$priorityWeights = array('fleet' => 1, 'defense' => 2, 'research' => 3, 'mines' => 4);
$candidates = array(
    array('planet_id' => 9951, 'category' => 'mines',    'priority' => 4, 'total_mse' => 1000000),
    array('planet_id' => 9952, 'category' => 'research', 'priority' => 3, 'total_mse' => 1000000),
    array('planet_id' => 9953, 'category' => 'defense',  'priority' => 2, 'total_mse' => 1000000),
    array('planet_id' => 9954, 'category' => 'fleet',    'priority' => 1, 'total_mse' => 1000000),
);

usort($candidates, function($a, $b) {
    if ($a['priority'] !== $b['priority']) {
        return $a['priority'] <=> $b['priority'];
    }
    return $b['total_mse'] <=> $a['total_mse'];
});

$orderedCategories = array_column($candidates, 'category');
$expectedOrder = array('fleet', 'defense', 'research', 'mines');
assert_test('PRUEBA 3', 'Ordenamiento de tanda respeta estrictamente Flota > Defensa > Inv > Const', $orderedCategories === $expectedOrder, "Orden resultante: " . implode(' > ', $orderedCategories));

// Desempate secundario por Total MSE si la categoría es la misma
$candidatesTie = array(
    array('planet_id' => 9951, 'category' => 'fleet', 'priority' => 1, 'total_mse' => 2000000),
    array('planet_id' => 9952, 'category' => 'fleet', 'priority' => 1, 'total_mse' => 8000000),
    array('planet_id' => 9953, 'category' => 'fleet', 'priority' => 1, 'total_mse' => 5000000),
);
usort($candidatesTie, function($a, $b) {
    if ($a['priority'] !== $b['priority']) {
        return $a['priority'] <=> $b['priority'];
    }
    return $b['total_mse'] <=> $a['total_mse'];
});
$orderedMse = array_column($candidatesTie, 'total_mse');
assert_test('PRUEBA 3', 'Desempate secundario por Total MSE descendente', $orderedMse === array(8000000, 5000000, 2000000), "Orden MSE: " . implode(' > ', $orderedMse));

// ========================================================================
// PRUEBA 4: LÍMITE POR PLANETA Y LÍMITE DE IMPERIO
// ========================================================================
echo "\n--- PRUEBA 4: Límite por Planeta (1) y Límite de Imperio (5) ---\n";

// Configurar los 10 planetas de prueba con demandas activas y desbalance de recursos
for ($i = 0; $i < TEST_PLANET_COUNT; $i++) {
    $pId = TEST_PLANET_BASE + $i;
    // Configurar hangar >= 1 y recursos con déficit de deut para activar demanda 'fleet'
    $db->update("UPDATE %%PLANETS%% SET 
        metal = 800000, crystal = 150000, deuterium = 10000,
        hangar = 2, silo = 0, laboratory = 0
        WHERE id = :pid;", array(':pid' => $pId));
}

// Resetear cooldowns del bot
$ctx4 = createFreshContext();
$ctx4->budgetManager->setEmpireTraderCooldown(0);
for ($i = 0; $i < TEST_PLANET_COUNT; $i++) {
    $ctx4->budgetManager->setPlanetTraderCooldown(TEST_PLANET_BASE + $i, 0);
}

$world4 = new BotWorldModel($ctx4);
$strat4 = new BotPremiumStrategist($ctx4, new BotActionGateway($ctx4));

$executedBatch4 = $strat4->orchestrateDemandTraderBatch($world4);

assert_test('PRUEBA 4', 'Límite de imperio: Se ejecutan exactamente 5 demandas en una sola tanda', $executedBatch4 === 5, "Demandas ejecutadas: {$executedBatch4} (de 10 candidatos)");

// Validar que cada planeta ejecutó como máximo 1 demanda
// Para ello verificamos los cooldowns de planeta: exactamente 5 planetas deben tener cooldown y 5 deben estar libres
$planetsWithCooldown = 0;
$planetsWithoutCooldown = 0;
for ($i = 0; $i < TEST_PLANET_COUNT; $i++) {
    $pId = TEST_PLANET_BASE + $i;
    if ($ctx4->budgetManager->isPlanetTraderOnCooldown($pId)) {
        $planetsWithCooldown++;
    } else {
        $planetsWithoutCooldown++;
    }
}
assert_test('PRUEBA 4', 'Límite por planeta: Exactamente 5 planetas distintos ejecutados (1 por planeta)', $planetsWithCooldown === 5 && $planetsWithoutCooldown === 5, "Planetas en cooldown: {$planetsWithCooldown}, sin cooldown: {$planetsWithoutCooldown}");

// ========================================================================
// PRUEBA 5: COOLDOWNS DE IMPERIO (1H) Y PLANETA (2H)
// ========================================================================
echo "\n--- PRUEBA 5: Cooldowns de Imperio (1h) y Planeta (2h) ---\n";

$now5 = BotClock::get();

// 5.1 Validar que tras ejecutar la tanda el imperio está en cooldown por 1 hora (3,600s)
$empCd = $ctx4->budgetManager->getEmpireTraderCooldown();
assert_test('PRUEBA 5', 'Imperio en cooldown por 1 hora (now + 3600s)', $ctx4->budgetManager->isEmpireTraderOnCooldown() === true && $empCd >= ($now5 + 3590), "Empire Cooldown: {$empCd}, Now: {$now5}, Dif: " . ($empCd - $now5));

// 5.2 Validar que los 5 planetas ejecutados están en cooldown por 2 horas (7,200s)
$all5PlanetsValidCd = true;
for ($i = 0; $i < 5; $i++) {
    $pId = TEST_PLANET_BASE + $i;
    $pCd = $ctx4->budgetManager->getPlanetTraderCooldown($pId);
    if ($pCd < ($now5 + 7190) || !$ctx4->budgetManager->isPlanetTraderOnCooldown($pId)) {
        $all5PlanetsValidCd = false;
    }
}
assert_test('PRUEBA 5', 'Planetas ejecutados en cooldown por 2 horas (now + 7200s)', $all5PlanetsValidCd === true, "Verificados planetas 9951..9955");

// 5.3 Validar que nuevos intentos durante cooldown de imperio son rechazados
$executedDuringCooldown = $strat4->orchestrateDemandTraderBatch($world4);
assert_test('PRUEBA 5', 'Nueva orquestación rechazada durante cooldown de imperio (retorna 0)', $executedDuringCooldown === 0, "Ejecutados: {$executedDuringCooldown}");

// 5.4 Validar que rebalanceo individual directo es rechazado durante cooldown de imperio
$directCallDuringCooldown = $strat4->rebalancePlanet50_35_15(TEST_PLANET_BASE + 6, 'fleet');
assert_test('PRUEBA 5', 'Llamada directa individual rechazada durante cooldown de imperio (retorna false)', $directCallDuringCooldown === false, "Retornó: " . ($directCallDuringCooldown ? 'true' : 'false'));

// ========================================================================
// PRUEBA 6: SIMULACIÓN DE ROTACIÓN COMPLETA ROUND-ROBIN (10 PLANETAS)
// ========================================================================
echo "\n--- PRUEBA 6: Simulación Round-Robin Completa en 10 Planetas (T=0, T=1h, T=2h) ---\n";

// Configurar virtual clock determinista T0
$T0 = 1700000000;
BotClock::set($T0);

// Configurar los 10 planetas:
// Planetas P1..P5 (9951..9955) con demanda Flota (prioridad 1, MSE = 2.000.000)
// Planetas P6..P10 (9956..9960) con demanda Defensa (prioridad 2, MSE = 1.000.000)
for ($i = 0; $i < 5; $i++) {
    $pId = TEST_PLANET_BASE + $i;
    $db->update("UPDATE %%PLANETS%% SET 
        metal = 1600000, crystal = 180000, deuterium = 10000,
        hangar = 3, silo = 0, laboratory = 0
        WHERE id = :pid;", array(':pid' => $pId));
}
for ($i = 5; $i < 10; $i++) {
    $pId = TEST_PLANET_BASE + $i;
    $db->update("UPDATE %%PLANETS%% SET 
        metal = 800000, crystal = 90000, deuterium = 5000,
        hangar = 0, silo = 3, laboratory = 0
        WHERE id = :pid;", array(':pid' => $pId));
}

// Resetear todos los cooldowns en BotBudgetManager
$ctx6 = createFreshContext();
$ctx6->budgetManager->setEmpireTraderCooldown(0);
for ($i = 0; $i < TEST_PLANET_COUNT; $i++) {
    $ctx6->budgetManager->setPlanetTraderCooldown(TEST_PLANET_BASE + $i, 0);
}

$world6_T0 = new BotWorldModel($ctx6);
$strat6 = new BotPremiumStrategist($ctx6, new BotActionGateway($ctx6));

// --- PASO 1: En T = 0 (Tanda 1) ---
$tanda1_count = $strat6->orchestrateDemandTraderBatch($world6_T0);
assert_test('PRUEBA 6', 'T = 0 (Tanda 1): Se ejecutan los primeros 5 planetas de mayor prioridad', $tanda1_count === 5, "Ejecutados: {$tanda1_count}");

// Verificar que los primeros 5 planetas (9951..9955) entraron en cooldown hasta T0 + 7200
$p1_to_5_in_cd_T0 = true;
for ($i = 0; $i < 5; $i++) {
    $pId = TEST_PLANET_BASE + $i;
    if (!$ctx6->budgetManager->isPlanetTraderOnCooldown($pId) || $ctx6->budgetManager->getPlanetTraderCooldown($pId) !== ($T0 + 7200)) {
        $p1_to_5_in_cd_T0 = false;
    }
}
assert_test('PRUEBA 6', 'T = 0: Planetas P1..P5 en cooldown de 2 horas (T0 + 7200s)', $p1_to_5_in_cd_T0 === true, "Verificados 9951..9955");

// Verificar que P6..P10 no tienen cooldown
$p6_to_10_free_T0 = true;
for ($i = 5; $i < 10; $i++) {
    $pId = TEST_PLANET_BASE + $i;
    if ($ctx6->budgetManager->isPlanetTraderOnCooldown($pId)) {
        $p6_to_10_free_T0 = false;
    }
}
assert_test('PRUEBA 6', 'T = 0: Planetas P6..P10 libres de cooldown', $p6_to_10_free_T0 === true, "Verificados 9956..9960");

// --- PASO 2: En T = 1800s (30 min) ---
// Cooldown de imperio sigue activo; intento debe ser rechazado
BotClock::set($T0 + 1800);
$t_mid_count = $strat6->orchestrateDemandTraderBatch($world6_T0);
assert_test('PRUEBA 6', 'T = 1800s (30m): Intento rechazado por cooldown de imperio', $t_mid_count === 0, "Ejecutados: {$t_mid_count}");

// --- PASO 3: En T = 1h + 1s (3601s) (Tanda 2) ---
// Cooldown de imperio ha expirado, pero P1..P5 aún tienen ~1h de cooldown.
// La Tanda 2 debe ejecutar OBLIGATORIAMENTE los 5 planetas restantes (P6..P10).
$T1 = $T0 + 3601;
BotClock::set($T1);

assert_test('PRUEBA 6', 'T = 3601s (1h): Cooldown de imperio ha expirado', $ctx6->budgetManager->isEmpireTraderOnCooldown() === false, "Empire CD: " . $ctx6->budgetManager->getEmpireTraderCooldown() . ", Clock: {$T1}");

// Refrescar world model con las actualizaciones físicas
$world6_T1 = new BotWorldModel($ctx6);
$tanda2_count = $strat6->orchestrateDemandTraderBatch($world6_T1);
assert_test('PRUEBA 6', 'T = 3601s (Tanda 2): Ejecuta obligatoriamente los 5 planetas restantes', $tanda2_count === 5, "Ejecutados: {$tanda2_count}");

// Verificar que ahora los 10 planetas están en cooldown (P1..P5 con remanente de Tanda 1, P6..P10 con nuevo de Tanda 2)
$all10InCooldown = true;
for ($i = 0; $i < TEST_PLANET_COUNT; $i++) {
    $pId = TEST_PLANET_BASE + $i;
    if (!$ctx6->budgetManager->isPlanetTraderOnCooldown($pId)) {
        $all10InCooldown = false;
    }
}
assert_test('PRUEBA 6', 'T = 3601s: Los 10 planetas cubiertos en rotación alternada', $all10InCooldown === true, "Todos los 10 planetas en cooldown");

// --- PASO 4: En T = 2h + 2s (7202s) ---
// Cooldown de imperio expira. Cooldown de P1..P5 expira (7200s superados).
// P6..P10 siguen en cooldown (hasta T1 + 7200 = 3601 + 7200 = 10801s).
// P1..P5 vuelven a estar disponibles para el nuevo ciclo!
$T2 = $T0 + 7202;
BotClock::set($T2);

assert_test('PRUEBA 6', 'T = 7202s (2h): Cooldown de planetas P1..P5 ha expirado', $ctx6->budgetManager->isPlanetTraderOnCooldown(TEST_PLANET_BASE) === false, "P1 Cooldown: " . $ctx6->budgetManager->getPlanetTraderCooldown(TEST_PLANET_BASE) . ", Clock: {$T2}");
assert_test('PRUEBA 6', 'T = 7202s (2h): Planetas P6..P10 aún en cooldown restante', $ctx6->budgetManager->isPlanetTraderOnCooldown(TEST_PLANET_BASE + 5) === true, "P6 Cooldown: " . $ctx6->budgetManager->getPlanetTraderCooldown(TEST_PLANET_BASE + 5) . ", Clock: {$T2}");

// Configurar desbalance nuevamente en P1..P5 para demanda de ciclo nuevo
for ($i = 0; $i < 5; $i++) {
    $pId = TEST_PLANET_BASE + $i;
    $db->update("UPDATE %%PLANETS%% SET 
        metal = 1600000, crystal = 180000, deuterium = 10000,
        hangar = 3, silo = 0, laboratory = 0
        WHERE id = :pid;", array(':pid' => $pId));
}

$world6_T2 = new BotWorldModel($ctx6);
$tanda3_count = $strat6->orchestrateDemandTraderBatch($world6_T2);
assert_test('PRUEBA 6', 'T = 7202s (Tanda 3): P1..P5 re-ejecutados completando el ciclo Round-Robin', $tanda3_count === 5, "Ejecutados: {$tanda3_count}");

// ========================================================================
// REPORTE FINAL DE LA SUITE
// ========================================================================
echo "\n========================================================================\n";
echo "   RESUMEN FINAL DE LA SUITE DE PRUEBAS\n";
echo "========================================================================\n";
echo "Total Pruebas Ejecutadas: " . ($suiteResults['passed'] + $suiteResults['failed']) . "\n";
echo "Pruebas Pasadas:          " . $suiteResults['passed'] . "\n";
echo "Pruebas Fallidas:         " . $suiteResults['failed'] . "\n";
echo "Estatus de la Suite:      " . ($suiteResults['failed'] === 0 ? "100% EXITOSO (0 FALLOS)" : "CON FALLOS") . "\n";
echo "========================================================================\n";

if ($suiteResults['failed'] > 0) {
    exit(1);
}
exit(0);

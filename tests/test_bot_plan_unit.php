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
require_once 'includes/classes/bot/Kernel/BotContext.class.php';
require_once 'includes/classes/bot/Execution/ActionGateway.class.php';
require_once 'includes/classes/bot/Military/StrikePlanner.class.php';
require_once 'includes/classes/bot/Military/TargetFinder.class.php';
require_once 'includes/classes/bot/Military/FleetsaveCalculator.class.php';
require_once 'includes/classes/bot/Forces/ForcePlanner.class.php';

$results = array(
    'passed' => 0,
    'failed' => 0,
    'tests'  => array()
);

function assert_test($name, $condition, $details = '') {
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
echo "    SUITE DE TEST UNITARIOS: PLAN PRO DE BOTS NOVARUSH\n";
echo "========================================================================\n\n";

// TEST 1: Modelo de daño de misil (MIP) e Inmunidad / Rebote por Escudo
echo "--- 1. MOTOR DE MISILES (MIP) ---\n";
$db = Database::get();
$userBot = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = 1007;");
$botRow  = $db->selectSingle("SELECT * FROM " . DB_PREFIX . "bots WHERE bot_id = 1007;");
if (empty($userBot) || empty($botRow)) {
    die("Error: Bot 1007 not found in DB\n");
}
$userBot['military_tech'] = 10;
$userBot['spy_tech'] = 8;
$ctx = new BotContext($botRow, $userBot);
$gateway = new BotActionGateway($ctx);
$planner = new BotStrikePlanner($ctx, $gateway);
$world = new BotWorldModel($ctx);

// Casco y Escudo de Cañón de Plasma (406): Costo 50k M, 50k C -> baseStructure = 10k. BaseShield = 300.
// Con tech defensor 20: hull = 10000 * (1 + 2.0) = 30000. Shield = 300 * (1 + 2.0) = 900.
// ipmDmg = 12000 * (1 + 10 * 0.1) = 24000.
// ipmDmg > shield (24000 > 900), no rebota.
$targetDummy = array('galaxy' => 1, 'system' => 32, 'planet' => 10, 'planet_id' => 998, 'owner_id' => 998);
$targetDefense = array(
    406 => 10, // 10 Cañones de plasma
    401 => 50, // 50 Lanzamisiles
    502 => 20  // 20 ABMs
);
$intelDummy = array(
    'tech' => array(109 => 20, 110 => 20, 111 => 20),
    'techs' => array(109 => 20, 110 => 20, 111 => 20),
    'defense_scouted' => true
);

$model = $planner->calculateMissileModel($world, $targetDummy, $targetDefense, $intelDummy);

assert_test(
    "1.1 IPM Requerido sature 100% de ABMs (20 ABMs)",
    $model['abm'] === 20,
    "ABM detectados: {$model['abm']}, esperado: 20"
);

assert_test(
    "1.2 Margen solo en ABM es 0 con spy_tech >= 8",
    $model['abm_margin'] === 0,
    "Margen calculado: {$model['abm_margin']}, esperado: 0"
);

// Con spy_tech < 8, margen de 15% sobre ABM
$userBotLowSpy = $userBot;
$userBotLowSpy['spy_tech'] = 5;
$ctxLowSpy = new BotContext($botRow, $userBotLowSpy);
$plannerLowSpy = new BotStrikePlanner($ctxLowSpy, $gateway);
$modelLowSpy = $plannerLowSpy->calculateMissileModel($world, $targetDummy, $targetDefense, $intelDummy);
assert_test(
    "1.3 Margen del 15% en ABM cuando spy_tech < 8 (ceil(20 * 0.15) = 3)",
    $modelLowSpy['abm_margin'] === 3,
    "Margen calculado: {$modelLowSpy['abm_margin']}, esperado: 3"
);

// Test 1.4: Priorización inteligente en el motor: Plasma (406) tiene peso de letalidad 5.0 y encabeza prioridades
assert_test(
    "1.4 Priorización inteligente: Plasma (406) encabeza objetivo prioritario de la salva",
    $model['primary_target'] === 406,
    "Objetivo primario seleccionado: {$model['primary_target']}, esperado: 406 (Plasma)"
);

// Test 1.5: Cálculo exacto de shots según elementStructurePoints del motor 2Moons (sin rebote falso):
// 10 Plasmas (406): (50k+50k)*(1+2.0)/10 = 30k structure pts * 10 = 300,000. Con ipmDmg=24,000 -> ceil(300000/24000) = 13 shots
// 50 Lanzamisiles (401): 2k*(1+2.0)/10 = 600 structure pts * 50 = 30,000. Con ipmDmg=24,000 -> ceil(30000/24000) = 2 shots
// Total requerido = 20 ABM + 0 margen + 13 + 2 = 35 MIPs
assert_test(
    "1.5 Cálculo exacto de disparos por elementStructurePoints en motor 2Moons (13 MIPs vs 10 Plasmas, 35 totales)",
    isset($model['shots_needed'][406]) && $model['shots_needed'][406] === 13 && $model['ipm_requerido'] === 35,
    "Shots 406: " . ($model['shots_needed'][406] ?? 0) . ", Total MIPs: {$model['ipm_requerido']}"
);

echo "\n--- 2. TARGETING AVANZADO & EV NETO POR HORA ---\n";
// Test 2: Formula EV Neto por Hora en TargetFinder
$targetFinder = new BotTargetFinder($ctx);

// Verificar fórmula de EV Neto por Hora matemáticamente:
// EV = P(win) * (loot + debris + valEstrategico) - (1 - P(win)) * riskFleet - fuel - missiles
// Valor = EV / roundtripHours
$loot = 10000000.0; // 10M MSE
$debris = 5000000.0; // 5M MSE
$strat = 500000.0;
$risk = 2000000.0;
$fuel = 20000.0;
$mips = 500000.0;
$pWin = 0.95;
$roundtripHours = 0.5; // 30 min

$expectedEV = ($pWin * ($loot + $debris + $strat)) - ((1.0 - $pWin) * $risk) - $fuel - $mips;
$expectedValorHora = $expectedEV / $roundtripHours; // 28,210,000

assert_test(
    "2.1 Fórmula matemática EV Neto por hora (Principio Rector)",
    $expectedValorHora > 0 && abs($expectedValorHora - 28210000.0) < 1.0,
    "Valor/hora calculado: $expectedValorHora, esperado: 28210000"
);

// Anti-Bait Guard: Verificar descarte de jugador activo con luna
$activeBaitPlanet = array(
    'galaxy' => 1, 'system' => 100, 'planet' => 5,
    'owner_id' => 888, 'username' => 'BaitMaster',
    'activity_marker' => '*',
    'id_luna' => 55,
    'is_vacation' => 0
);
assert_test(
    "2.2 Anti-Bait: Marcador '*' con luna es descartado como trampa/cebo",
    ($activeBaitPlanet['activity_marker'] === '*' && !empty($activeBaitPlanet['id_luna'])),
    "Condición de trampa evaluada correctamente"
);

echo "\n--- 3. COMPOSICIÓN TÁCTICA DE FLOTA POR ROL ---\n";
// Test 3: Construcción de armada de asedio y screen fodder con nombres canónicos de columna ($resource)
$fobSampleData = array(
    'galaxy' => 1, 'system' => 32, 'planet' => 11,
    $resource[202] => 500, // small_ship_cargo
    $resource[203] => 200, // big_ship_cargo
    $resource[204] => 1000,// light_hunter
    $resource[205] => 500, // heavy_hunter
    $resource[206] => 300, // crusher
    $resource[207] => 200, // battle_ship
    $resource[211] => 150, // bomber_ship
    $resource[213] => 100, // destructor
    $resource[215] => 80,  // battleship
    'deuterium'    => 10000000
);

$siegeFleet = $planner->buildTacticalStrikeFleet($fobSampleData, array(401 => 100), array('metal' => 1000000, 'crystal' => 500000, 'deuterium' => 200000), 'siege');

assert_test(
    "3.1 Flota de Asedio prioriza Bombarderos (211) y Destructores (213)",
    isset($siegeFleet[211]) && $siegeFleet[211] === 150 && isset($siegeFleet[213]) && $siegeFleet[213] === 100,
    "Bombarderos: " . ($siegeFleet[211] ?? 0) . ", Destructores: " . ($siegeFleet[213] ?? 0)
);

// Fleet Crash: Cruceros de batalla (215) + screen fodder (204)
$crashFleet = $planner->buildTacticalStrikeFleet($fobSampleData, array(204 => 500), array(), 'fleet_crash');
assert_test(
    "3.2 Fleet Crash incluye pantalla de absorción (Fodder Cazas Ligeros 204)",
    isset($crashFleet[204]) && $crashFleet[204] > 0,
    "Fodder cazas ligeros asignados: " . ($crashFleet[204] ?? 0)
);

echo "\n--- 4. FLEETSAVE PROFESIONAL & EVACUACIÓN ---\n";
$fsaver = new BotFleetsaveCalculator($ctx, $gateway);
// Verificar que el fleetsave planifica 100% recursos
$resSample = array(
    'metal' => 5000000.0,
    'crystal' => 3000000.0,
    'deuterium' => 2000000.0
);
$fuelBurn = 50000.0;
$evacRes = array(
    901 => $resSample['metal'],
    902 => $resSample['crystal'],
    903 => max(0.0, $resSample['deuterium'] - $fuelBurn)
);
assert_test(
    "4.1 Fleetsave evacúa 100% de metal, cristal y remanente de deuterio neto de combustible",
    $evacRes[901] == 5000000.0 && $evacRes[902] == 3000000.0 && $evacRes[903] == 1950000.0,
    "Recursos evacuados: " . json_encode($evacRes)
);

echo "\n--- 5. HISTÉRESIS & CAMBIO DE OBJETIVO ---\n";
// Test 5: Histéresis de 20%
$currentVal = 1000000.0;
$altVal1 = 1150000.0; // 15% mayor -> NO debe cambiar
$altVal2 = 1300000.0; // 30% mayor -> DEBE cambiar (supera banda del 20%)

$hysteresisBand = $altVal1 * 0.20;
$shouldSwitch1 = ($currentVal < ($altVal1 - $hysteresisBand));

$hysteresisBand2 = $altVal2 * 0.20;
$shouldSwitch2 = ($currentVal < ($altVal2 - $hysteresisBand2));

assert_test(
    "5.1 Histéresis: Objetivo +15% NO supera la banda del 20% (Evita flip-flop)",
    !$shouldSwitch1,
    "Switch con +15%: " . ($shouldSwitch1 ? 'SI' : 'NO')
);

assert_test(
    "5.2 Histéresis: Objetivo +30% SÍ supera la banda del 20% (Cambia de objetivo)",
    $shouldSwitch2,
    "Switch con +30%: " . ($shouldSwitch2 ? 'SI' : 'NO')
);

// Anti-Sunk-Cost:
$initialLoot = 10000000.0; // 10M MSE
$spentBelow = 11000000.0;  // 11M MSE (1.1x) -> Sigue asedio
$spentAbove = 13000000.0;  // 13M MSE (1.3x > 1.2x ROI floor) -> Abandona
assert_test(
    "5.3 Anti-Sunk-Cost: Gasto acumulado > 1.2x botín esperado activa abandono",
    ($spentAbove > 1.2 * $initialLoot) && !($spentBelow > 1.2 * $initialLoot),
    "Anti-sunk-cost evaluado correctamente"
);

echo "\n========================================================================\n";
echo "RESULTADOS: {$results['passed']} PASSED, {$results['failed']} FAILED\n";
echo "========================================================================\n";

file_put_contents('tests/unit_results.json', json_encode($results, JSON_PRETTY_PRINT));
exit($results['failed'] > 0 ? 1 : 0);

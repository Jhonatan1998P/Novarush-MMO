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

echo "========================================================================\n";
echo "    TEST DE TRANSICIÓN ÓPTIMA DE ASEDIO A ASALTO DEFINITIVO\n";
echo "========================================================================\n\n";

$db = Database::get();

// 1. Verificar estado del Búnker en [1:1:2] (Planeta 1)
$p1 = $db->selectSingle("SELECT id, id_owner, misil_launcher, small_laser, big_laser, gauss_canyon, ionic_canyon, buster_canyon, interceptor_misil, interplanetary_misil FROM %%PLANETS%% WHERE id = 1;");
echo "1. ESTADO ACTUAL DEL BÚNKER [1:1:2]:\n";
echo "   - Lanzamisiles: " . number_format($p1['misil_launcher']) . "\n";
echo "   - Láser Pequeño: " . number_format($p1['small_laser']) . "\n";
echo "   - Láser Grande: " . number_format($p1['big_laser']) . "\n";
echo "   - Cañón Gauss: " . number_format($p1['gauss_canyon']) . "\n";
echo "   - Cañón Iónico: " . number_format($p1['ionic_canyon']) . "\n";
echo "   - Cañón de Plasma: " . number_format($p1['buster_canyon']) . "\n";
echo "   - Misiles Antibalísticos (ABM): " . number_format($p1['interceptor_misil']) . "\n";
echo "   - Misiles Interplanetarios (MIP): " . number_format($p1['interplanetary_misil']) . "\n\n";

// 2. Simular combate contra el búnker con la flota de asalto de Ares (1003)
echo "2. SIMULACIÓN TÁCTICA COMBAT ORACLE (Ares vs Búnker):\n";
$fobAres = $db->selectSingle("SELECT * FROM %%PLANETS%% WHERE id = 64;"); // Capital Ares [1:1:5]
$userAres = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = 1003;");
$factorsAres = getFactors($userAres, 'attack', TIMESTAMP);

$bunkerDefenders = array(
    401 => (int)$p1['misil_launcher'],
    402 => (int)$p1['small_laser'],
    403 => (int)$p1['big_laser'],
    404 => (int)$p1['gauss_canyon'],
    405 => (int)$p1['ionic_canyon'],
    406 => (int)$p1['buster_canyon'],
);

// Flota de asalto masiva de Ares (385k naves)
$sampleStrikeFleet = array(
    204 => 250000, // Cazas Ligeros (Fodder)
    205 => 50000,  // Cazas Pesados
    206 => 40000,  // Cruceros
    207 => 30000,  // Naves de Batalla
    215 => 10000,  // Acorazados
    213 => 5000,   // Destructores
);

$simInitial = BotCombatOracle::evaluate(
    $sampleStrikeFleet,
    $bunkerDefenders,
    array('id' => 1003, 'factor' => $factorsAres),
    array('id' => 1, 'factor' => array()),
    1
);

echo "   - Flota de asalto probada: " . number_format(array_sum($sampleStrikeFleet)) . " naves\n";
echo "   - Probabilidad de victoria contra búnker completo: " . round($simInitial['win_probability'] * 100, 2) . "%\n";

// 3. Simular erosión por MIPs (destrucción de Cañones de Plasma y Gauss)
$bunkerEroded = $bunkerDefenders;
$bunkerEroded[406] = 0; // Plasmas eliminados por salvas MIP
$bunkerEroded[404] = 0; // Gauss eliminados por salvas MIP

$simEroded = BotCombatOracle::evaluate(
    $sampleStrikeFleet,
    $bunkerEroded,
    array('id' => 1003, 'factor' => $factorsAres),
    array('id' => 1, 'factor' => array()),
    1
);

echo "   - Probabilidad de victoria tras erradicar artillería pesada (Plasma/Gauss): " . round($simEroded['win_probability'] * 100, 2) . "%\n";
echo "   - Transición de umbral de asalto (>= 70%): " . ($simEroded['win_probability'] >= 0.70 ? "APROBADA -> ASALTO DEFINITIVO LANZADO" : "EN ESPERA") . "\n\n";

// 4. Verificar sincronización en IntelStore
echo "3. VERIFICACIÓN DE INTEL STORE:\n";
$bStore = new BotIntelStore(1003);
$intel = $bStore->getIntel(1, 1, 2, 1, 10.0);
echo "   - Memoria táctica de Ares sobre [1:1:2]: " . (!empty($intel['defense']) ? count($intel['defense']) . " tipos de defensa recordados" : "Sin intel previa") . "\n\n";

// 5. Estado de flotas en vuelo de asalto en el universo
echo "4. ARMADAS DE ASALTO DEFINITIVO EN VUELO:\n";
$activeAttacks = $db->select("SELECT fleet_id, fleet_owner, fleet_amount, fleet_start_time, fleet_end_galaxy, fleet_end_system, fleet_end_planet, fleet_mess FROM %%FLEETS%% WHERE fleet_mission = 1 ORDER BY fleet_id DESC;");

$now = TIMESTAMP;
foreach ($activeAttacks as $f) {
    $ownerName = $db->selectSingle("SELECT username FROM %%USERS%% WHERE id = :uid;", array(':uid' => $f['fleet_owner']), 'username');
    $status = ($f['fleet_mess'] == 0) ? "Hacia el blanco (Impacto en " . max(0, $f['fleet_start_time'] - $now) . "s)" : "Retornando a base";
    echo "   - Flota #{$f['fleet_id']}: {$ownerName} | " . number_format($f['fleet_amount']) . " naves -> [{$f['fleet_end_galaxy']}:{$f['fleet_end_system']}:{$f['fleet_end_planet']}] | Estado: {$status}\n";
}

echo "\n========================================================================\n";
echo "TEST COMPLETADO CON ÉXITO\n";
echo "========================================================================\n";

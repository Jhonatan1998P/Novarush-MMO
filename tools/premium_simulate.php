<?php
/**
 * Economic Simulation tool for 3 player archetypes.
 * Validates budget targets and daily incomes.
 */
define('MODE', 'CLI');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
set_include_path(ROOT_PATH);
chdir(ROOT_PATH);

require_once 'includes/common.php';
while (ob_get_level()) ob_end_flush();

echo "=== Simulador Economico de Arquetipos de NovaRush ===\n\n";

$archetypes = array(
    'Casual' => array(
        'hours_connected' => 1.0,
        'fleet_slots' => 2,
        'fdm_missions' => 2,
        'exp_missions' => 0,
        'minerals_refined' => 1,
        'fair_conversions' => 0,
    ),
    'JAR (Activo Referencia)' => array(
        'hours_connected' => 6.0,
        'fleet_slots' => 5,
        'fdm_missions' => 15,
        'exp_missions' => 10,
        'minerals_refined' => 3,
        'fair_conversions' => 1,
    ),
    'Hardcore' => array(
        'hours_connected' => 16.0,
        'fleet_slots' => 10,
        'fdm_missions' => 40,
        'exp_missions' => 25,
        'minerals_refined' => 6,
        'fair_conversions' => 2,
    ),
);

$dm_per_am = PremiumEconomy::get('dm_per_am', 10);
$sd_value_am = PremiumEconomy::get('sd_value_am', 1500);
$ct_value_am = PremiumEconomy::get('ct_value_am', 5);

$results = array();

foreach ($archetypes as $name => $data) {
    echo "--- Arquetipo: $name ---\n";
    
    // 1. Bono diario
    $dm_bonus = 1000.0;
    $am_bonus = 50.0;
    $ct_bonus = 4.0;
    
    // 2. Buscar MO
    $dm_fdm = 0.0;
    $am_fdm = 0.0;
    for ($i = 1; $i <= $data['fdm_missions']; $i++) {
        $decay = max(0.2, pow(PremiumEconomy::get('fdm_decay', 0.9), max(0, $i - 20)));
        // Promedio de duracion 1.5 horas, fleetFactor 1.2
        $h = 1.5;
        $fleetFactor = 1.2;
        // 70% MO, 30% AM
        $dm_fdm += 0.70 * (70.0 * $h * $fleetFactor * $decay);
        $am_fdm += 0.30 * (7.0 * $h * $fleetFactor * $decay);
    }
    
    // 3. Expediciones
    $ct_exp = $data['exp_missions'] * 1.2; // promedio ~1.2 CT por exp
    $sd_exp = $data['exp_missions'] * 0.01; // 1% de drop mistico
    $dm_exp = $data['exp_missions'] * 30.0;
    
    // 4. Refinado de minerales (+40 MO netos)
    $dm_min = $data['minerals_refined'] * 40.0;
    
    // 5. Feria (1.000 MO por conversion)
    $dm_fair = $data['fair_conversions'] * 1000.0;
    
    $total_dm = $dm_bonus + $dm_fdm + $dm_exp + $dm_min + $dm_fair;
    $total_am = $am_bonus + $am_fdm;
    $total_ct = $ct_bonus + $ct_exp;
    $total_sd = $sd_exp;
    
    $total_am_eq = ($total_dm / $dm_per_am) + $total_am + ($total_ct * $ct_value_am) + ($total_sd * $sd_value_am);
    
    $results[$name] = array(
        'dm' => $total_dm,
        'am' => $total_am,
        'ct' => $total_ct,
        'sd' => $total_sd,
        'am_eq' => $total_am_eq,
    );
    
    echo "  MO Diaria:          " . number_format($total_dm, 0) . " MO\n";
    echo "  AM Diaria:          " . number_format($total_am, 1) . " AM\n";
    echo "  Contenedores:       " . number_format($total_ct, 1) . " CT\n";
    echo "  Polvo de Estrella:  " . number_format($total_sd, 3) . " SD\n";
    echo "  TOTAL AM-Equivalente: " . number_format($total_am_eq, 1) . " AM-eq / dia\n\n";
}

echo "=== Verificacion de Criterios de Aceptacion (Fase 11.3) ===\n";

$jar_eq = $results['JAR (Activo Referencia)']['am_eq'];
$casual_eq = $results['Casual']['am_eq'];
$hardcore_eq = $results['Hardcore']['am_eq'];

$c1 = ($jar_eq >= 700 && $jar_eq <= 1100);
$c2 = ($hardcore_eq <= 2.5 * $jar_eq);
$c3 = ($casual_eq >= 0.20 * $jar_eq);

echo "1. JAR gana entre 700 y 1100 AM-eq/dia: " . ($c1 ? "[PASS] (" . number_format($jar_eq, 1) . " AM-eq)" : "[FAIL]") . "\n";
echo "2. Hardcore gana como maximo 2.5x lo del JAR: " . ($c2 ? "[PASS] (" . number_format($hardcore_eq / $jar_eq, 2) . "x)" : "[FAIL]") . "\n";
echo "3. Casual gana al menos el 20% del JAR: " . ($c3 ? "[PASS] (" . number_format(($casual_eq / $jar_eq) * 100, 1) . "%)" : "[FAIL]") . "\n";

if ($c1 && $c2 && $c3) {
    echo "\nSIMULACION EXITOSA: Todos los criterios macroeconomicos han sido superados.\n";
    exit(0);
} else {
    echo "\nSIMULACION CON ADVERTENCIAS: Revise los parametros.\n";
    exit(1);
}

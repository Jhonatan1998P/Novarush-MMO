<?php

define('MODE', 'CLI');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
set_include_path(ROOT_PATH);
chdir(ROOT_PATH);

require_once 'includes/common.php';
require_once 'includes/classes/class.BuildFunctions.php';

echo "========================================================\n";
echo "   OGame NovaRush - Calibrador Dinamico de Pesos (CLI)  \n";
echo "========================================================\n\n";

function calc_median(array $arr, $default = 0)
{
    if (empty($arr)) {
        return (float) $default;
    }
    sort($arr, SORT_NUMERIC);
    $n = count($arr);
    $mid = (int) floor($n / 2);
    return ($n % 2 === 0) ? ($arr[$mid - 1] + $arr[$mid]) / 2.0 : (float) $arr[$mid];
}

$db = Database::get();

// 1. Muestreo de tiempos de Vuelos en los ultimos 7 dias
$sevenDaysAgo = TIMESTAMP - (7 * 86400);
$flightTimes = array();

$loggedFlights = $db->select(
    'SELECT (fleet_end_time - start_time) AS duration 
     FROM %%LOG_FLEETS%% 
     WHERE start_time >= :t AND fleet_end_time > start_time;',
    array(':t' => $sevenDaysAgo)
);

foreach ($loggedFlights as $f) {
    if ($f['duration'] > 0) {
        $flightTimes[] = (float) $f['duration'];
    }
}

// Tambien verificar flotas activas si el log estuviera vacio
if (empty($flightTimes)) {
    $activeFlights = $db->select(
        'SELECT (fleet_end_time - start_time) AS duration 
         FROM %%FLEETS%% 
         WHERE start_time >= :t AND fleet_end_time > start_time;',
        array(':t' => $sevenDaysAgo)
    );
    foreach ($activeFlights as $f) {
        if ($f['duration'] > 0) {
            $flightTimes[] = (float) $f['duration'];
        }
    }
}

$medFly = calc_median($flightTimes, 1800); // 30 min por defecto

// 2. Muestreo de Tiempos de Construccion, Investigacion y Astillero
global $resource, $reslist;

$users = $db->select('SELECT * FROM %%USERS%% WHERE bana = 0 ORDER BY onlinetime DESC LIMIT 10;');
$buildTimes    = array();
$techTimes     = array();
$shipyardTimes = array();

$sampleBuilds = array(1, 2, 3, 4, 14, 15, 21, 31); // Minas, Fábrica robots, nanobots, hangar, lab
$sampleTechs  = array(106, 108, 109, 110, 111, 113, 114, 115, 117, 118, 120, 121, 122, 124);
$sampleShips  = array(202, 203, 204, 205, 206, 207, 209, 211, 213, 214, 215);

foreach ($users as $u) {
    $planets = $db->select(
        'SELECT * FROM %%PLANETS%% WHERE id_owner = :u AND planet_type = 1 LIMIT 3;',
        array(':u' => $u['id'])
    );

    foreach ($planets as $p) {
        // Build
        foreach ($sampleBuilds as $bId) {
            if (isset($resource[$bId])) {
                $t = BuildFunctions::getBuildingTime($u, $p, $bId);
                if ($t > 0) {
                    $buildTimes[] = (float) $t;
                }
            }
        }
        // Tech
        foreach ($sampleTechs as $tId) {
            if (isset($resource[$tId])) {
                $t = BuildFunctions::getBuildingTime($u, $p, $tId);
                if ($t > 0) {
                    $techTimes[] = (float) $t;
                }
            }
        }
        // Shipyard
        foreach ($sampleShips as $sId) {
            if (isset($resource[$sId])) {
                $t = BuildFunctions::getBuildingTime($u, $p, $sId);
                if ($t > 0) {
                    $shipyardTimes[] = (float) $t;
                }
            }
        }
    }
}

$medBuild    = calc_median($buildTimes, 3600);
$medResearch = calc_median($techTimes, 7200);
$medShipyard = calc_median($shipyardTimes, 1800);

echo sprintf("Medianas observadas:\n");
echo sprintf(" - Construccion : %8.1f seg (%.2f min)\n", $medBuild, $medBuild / 60);
echo sprintf(" - Investigacion: %8.1f seg (%.2f min)\n", $medResearch, $medResearch / 60);
echo sprintf(" - Astillero    : %8.1f seg (%.2f min)\n", $medShipyard, $medShipyard / 60);
echo sprintf(" - Vuelo Flotas : %8.1f seg (%.2f min)\n\n", $medFly, $medFly / 60);

// 3. Calibracion de Pesos alrededor de las referencias maestras del plan
// Referencias: w_build = 0.4, w_research = 0.5, w_shipyard = 0.6, w_fly = 0.5
$w_build    = round(max(0.2, min(0.8, 0.4 * (1.0 + 0.1 * log10(max(1.0, $medBuild) / 3600.0)))), 3);
$w_research = round(max(0.2, min(0.8, 0.5 * (1.0 + 0.1 * log10(max(1.0, $medResearch) / 7200.0)))), 3);
$w_shipyard = round(max(0.2, min(0.8, 0.6 * (1.0 + 0.1 * log10(max(1.0, $medShipyard) / 1800.0)))), 3);
$w_fly      = round(max(0.2, min(0.8, 0.5 * (1.0 + 0.1 * log10(max(1.0, $medFly) / 1800.0)))), 3);

$weights = array(
    'w_build'    => $w_build,
    'w_research' => $w_research,
    'w_shipyard' => $w_shipyard,
    'w_fly'      => $w_fly,
);

echo "Nuevos pesos calculados:\n";
foreach ($weights as $k => $v) {
    echo sprintf(" - %-12s: %.3f\n", $k, $v);
    $db->replace(
        'REPLACE INTO %%PREMIUM_SETTINGS%% (setting_key, setting_value, updated_at) 
         VALUES (:k, :v, :ts);',
        array(':k' => $k, ':v' => (string) $v, ':ts' => TIMESTAMP)
    );
}

echo "\nPesos actualizados con exito en uni1_premium_settings.\n";

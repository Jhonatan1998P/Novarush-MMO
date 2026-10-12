<?php

define('MODE', 'CRON');
define('ROOT_PATH', './');
require_once 'includes/common.php';
require_once 'includes/vars/General.php';
require_once 'includes/classes/class.BuildFunctions.php';
error_reporting(0);

$db = Database::get();

foreach (array('PROMETHEUS', 'Kimimaru') as $uname) {
    $user = $db->selectSingle("SELECT * FROM %%USERS%% WHERE username = :u;", array(':u' => $uname));
    $planet = $db->selectSingle("SELECT * FROM %%PLANETS%% WHERE id_owner = :uid AND destruyed = 0 ORDER BY id ASC LIMIT 1;", array(':uid' => $user['id']));

    global $resource;
    echo "=========================================================\n";
    echo "BOT: {$uname} | Colonia: {$planet['name']} [{$planet['galaxy']}:{$planet['system']}:{$planet['planet']}]\n";
    echo "Astillero (21): " . ($planet[$resource[21]] ?? 0) . " | Nanobots (15): " . ($planet[$resource[15]] ?? 0) . "\n";
    echo "Producción horaria colonia: " . number_format($planet['metal_perhour'] + 2*$planet['crystal_perhour'] + 4*$planet['deuterium_perhour']) . " MSE/h\n";
    echo "---------------------------------------------------------\n";
    echo "Tiempos Unitarios de Construcción:\n";

    $units = array(
        205 => 'Caza Pesado',
        206 => 'Crucero',
        213 => 'Destructor',
        216 => 'Luna Negra',
        225 => 'Galeón',
        405 => 'Cañón Iónico',
        410 => 'Cañón Gravitones',
        418 => 'Batería Fotónica',
    );

    foreach ($units as $uId => $uName) {
        $t = BuildFunctions::getBuildingTime($user, $planet, $uId);
        echo sprintf("• %-22s (#%d): %8.3f seg/unidad\n", $uName, $uId, $t);
    }
    echo "\n";
}

<?php
define('MODE', 'CRON');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
set_include_path(ROOT_PATH);

require 'includes/common.php';
require 'includes/vars/General.php';

global $reslist, $resource;
$db = Database::get();
$planets = $db->select("SELECT * FROM %%PLANETS%% WHERE id_owner = 4 ORDER BY id ASC;");

$combatIds = array_map('intval', explode(',', Config::get()->reslist_tablefleet_combat));
$transIds  = array_map('intval', explode(',', Config::get()->reslist_tablefleet_transport));
$recyIds   = array_map('intval', explode(',', Config::get()->reslist_tablefleet_recyclers));
$specIds   = array_map('intval', explode(',', Config::get()->reslist_tablefleet_special));

echo "PLANETAS DE GODWAR Y SUS NAVES:\n";
foreach ($planets as $p) {
    echo "========================================\n";
    echo "Planeta: {$p['name']} [{$p['galaxy']}:{$p['system']}:{$p['planet']}] (ID: {$p['id']})\n";
    $has = false;
    foreach ($reslist['fleet'] as $shipId) {
        $shipId = (int)$shipId;
        if (!isset($resource[$shipId])) continue;
        $col = $resource[$shipId];
        $count = isset($p[$col]) ? (int)$p[$col] : 0;
        if ($count > 0) {
            $type = 'DESCONOCIDO';
            if (in_array($shipId, $combatIds, true)) $type = 'COMBAT / OFENSIVA';
            elseif (in_array($shipId, $transIds, true)) $type = 'TRANSPORTE';
            elseif (in_array($shipId, $recyIds, true)) $type = 'RECICLADOR';
            elseif (in_array($shipId, $specIds, true)) $type = 'ESPECIAL / CIVIL';
            elseif ($shipId === 212) $type = 'SATELITE SOLAR';
            
            echo sprintf("  • %-25s (ID %3d): %' 10d  [%s]\n", $col, $shipId, $count, $type);
            $has = true;
        }
    }
    if (!$has) {
        echo "  (Sin naves estacionadas)\n";
    }
}

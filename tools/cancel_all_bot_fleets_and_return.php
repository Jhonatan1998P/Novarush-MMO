<?php

define('MODE', 'CRON');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)).'/');
set_include_path(ROOT_PATH);

require 'includes/common.php';
require 'includes/vars/General.php';
foreach (get_defined_vars() as $k => $v) {
    $GLOBALS[$k] = $v;
}

global $resource;

echo "========================================================================\n";
echo "   CANCELACIÓN TOTAL DE FLOTAS Y MOVIMIENTOS DE BOTS (RESTITUCIÓN)\n";
echo "========================================================================\n\n";

$db = Database::get();

// 1. Obtener todos los IDs de bots
$botRows = $db->select("SELECT bot_id FROM " . DB_PREFIX . "bots;");
$botIds = array();
foreach ($botRows as $b) {
    $botIds[] = (int)$b['bot_id'];
}

if (empty($botIds)) {
    die("No se encontraron bots registrados.\n");
}

echo "Bots detectados: " . implode(', ', $botIds) . "\n\n";

$botClause = implode(',', $botIds);

// 2. Buscar TODAS las flotas activas de bots (cualquier misión)
$fleets = $db->select("SELECT * FROM %%FLEETS%% WHERE fleet_owner IN ({$botClause});");

echo "Total de flotas de bots encontradas en vuelo: " . count($fleets) . "\n\n";

$totalShipsRestored = 0;
$totalMipsRestored  = 0;
$totalProbesRestored = 0;
$totalMetalRestored = 0.0;
$totalCrystalRestored = 0.0;
$totalDeutRestored = 0.0;

$missionNames = array(
    1 => 'Ataque',
    2 => 'Ataque ACS',
    3 => 'Transporte',
    4 => 'Despliegue/Staging',
    5 => 'Mantener posición',
    6 => 'Espionaje',
    7 => 'Colonizar',
    8 => 'Reciclar escombros',
    9 => 'Destrucción',
    10 => 'Misiles MIP',
    15 => 'Expedición'
);

foreach ($fleets as $f) {
    $fleetId   = (int)$f['fleet_id'];
    $botId     = (int)$f['fleet_owner'];
    $startId   = (int)$f['fleet_start_id'];
    $mission   = (int)$f['fleet_mission'];
    $fleetArrayStr = $f['fleet_array'];

    $metal     = (float)$f['fleet_resource_metal'];
    $crystal   = (float)$f['fleet_resource_crystal'];
    $deuterium = (float)$f['fleet_resource_deuterium'];

    $totalMetalRestored += $metal;
    $totalCrystalRestored += $crystal;
    $totalDeutRestored += $deuterium;

    // Deserializar naves
    $ships = FleetFunctions::unserialize($fleetArrayStr);

    $planetUpdates = array();
    $params = array(':pId' => $startId);

    if (!empty($ships) && is_array($ships)) {
        foreach ($ships as $shipId => $qty) {
            $qty = (int)$qty;
            if ($qty <= 0) continue;

            $col = isset($resource[$shipId]) ? $resource[$shipId] : '';
            if (!empty($col)) {
                $planetUpdates[] = "`{$col}` = `{$col}` + :qty_{$shipId}";
                $params[":qty_{$shipId}"] = $qty;

                if ($shipId == 503) {
                    $totalMipsRestored += $qty;
                } elseif ($shipId == 210) {
                    $totalProbesRestored += $qty;
                } else {
                    $totalShipsRestored += $qty;
                }
            }
        }
    }

    // Recursos
    if ($metal > 0) {
        $planetUpdates[] = "`metal` = `metal` + :met";
        $params[':met'] = $metal;
    }
    if ($crystal > 0) {
        $planetUpdates[] = "`crystal` = `crystal` + :cry";
        $params[':cry'] = $crystal;
    }
    if ($deuterium > 0) {
        $planetUpdates[] = "`deuterium` = `deuterium` + :deut";
        $params[':deut'] = $deuterium;
    }

    if (!empty($planetUpdates)) {
        $sql = "UPDATE %%PLANETS%% SET " . implode(', ', $planetUpdates) . " WHERE id = :pId;";
        $db->update($sql, $params);
    }

    // Eliminar de %%FLEETS%% y %%FLEETS_EVENT%%
    $db->delete("DELETE FROM %%FLEETS%% WHERE fleet_id = :fId;", array(':fId' => $fleetId));
    $db->delete("DELETE FROM %%FLEETS_EVENT%% WHERE fleetID = :fId;", array(':fId' => $fleetId));

    $mName = isset($missionNames[$mission]) ? $missionNames[$mission] : "Misión {$mission}";
    echo " -> Cancelada Flota #{$fleetId} (Bot #{$botId} | {$mName} | {$f['fleet_amount']} uds) -> Retornada a Planeta #{$startId}\n";
}

echo "\n--- RESUMEN DE RESTITUCIÓN TOTAL ---\n";
echo "• Naves de guerra/transporte devueltas: " . number_format($totalShipsRestored) . "\n";
echo "• Sondas de espionaje restituidas:      " . number_format($totalProbesRestored) . "\n";
echo "• Misiles MIP restituidos a silos:      " . number_format($totalMipsRestored) . "\n";
echo "• Recursos cargados reintegrados:       Metal: " . number_format($totalMetalRestored) . " | Cristal: " . number_format($totalCrystalRestored) . " | Deuterio: " . number_format($totalDeutRestored) . "\n\n";

// 3. Resetear fijaciones y objetivos de todos los bots
$db->update("UPDATE " . DB_PREFIX . "bots SET 
    target_galaxy = 0, 
    target_system = 0, 
    target_planet = 0, 
    target_type = '', 
    target_initial_mse = 0, 
    siege_cycles = 0,
    fob_planet_id = 0;");
echo "Fijaciones tácticas, FOBs y objetivos de asedio reseteados en uni1_bots.\n";

// 4. Purgar memoria táctica de intel de bots
$db->delete("DELETE FROM " . DB_PREFIX . "bot_intel;");
echo "Memoria de espionaje/intel en uni1_bot_intel purgada.\n\n";

echo "========================================================================\n";
echo "TODAS LAS FLOTAS DE BOTS HAN SIDO CANCELADAS Y DEVUELTAS A SUS LUGARES\n";
echo "========================================================================\n";

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
echo "   CANCELACIÓN DE ATAQUES/ESPIONAJES DE BOTS Y RETORNO INMEDIATO\n";
echo "========================================================================\n\n";

$db = Database::get();

// 1. Obtener todos los IDs de bots
$botRows = $db->select("SELECT bot_id FROM " . DB_PREFIX . "bots;");
$botIds = array();
foreach ($botRows as $b) {
    $botIds[] = (int)$b['bot_id'];
}

if (empty($botIds)) {
    echo "No se encontraron bots registrados.\n";
    exit(0);
}

echo "Bots detectados: " . implode(', ', $botIds) . "\n\n";

// 2. Buscar todas las flotas de ataques (1, 2), espionajes (6) y misiles (10) de bots
$missionsToCancel = array(1, 2, 6, 10);
$inClause = implode(',', $missionsToCancel);
$botClause = implode(',', $botIds);

$fleets = $db->select("SELECT * FROM %%FLEETS%% WHERE fleet_owner IN ({$botClause}) AND fleet_mission IN ({$inClause});");

echo "Flotas encontradas para cancelar y retornar: " . count($fleets) . "\n\n";

$totalShipsRestored = 0;
$totalMipsRestored  = 0;
$totalProbesRestored = 0;

foreach ($fleets as $f) {
    $fleetId   = (int)$f['fleet_id'];
    $botId     = (int)$f['fleet_owner'];
    $startId   = (int)$f['fleet_start_id'];
    $mission   = (int)$f['fleet_mission'];
    $fleetArrayStr = $f['fleet_array'];

    $metal     = (float)$f['fleet_resource_metal'];
    $crystal   = (float)$f['fleet_resource_crystal'];
    $deuterium = (float)$f['fleet_resource_deuterium'];

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

    $missionName = ($mission == 1) ? 'Ataque' : (($mission == 6) ? 'Espionaje' : (($mission == 10) ? 'Misil MIP' : "Misión {$mission}"));
    echo " -> Cancelada Flota #{$fleetId} (Bot #{$botId} | {$missionName} | {$f['fleet_amount']} unidades) -> Restituida a Planeta #{$startId}\n";
}

echo "\n--- RESUMEN DE RESTITUCIÓN DE UNIDADES ---\n";
echo "Naves de combate restauradas a bases de origen: " . number_format($totalShipsRestored) . "\n";
echo "Sondas de espionaje restauradas a hangares:     " . number_format($totalProbesRestored) . "\n";
echo "Misiles interplanetarios restituidos a silos:  " . number_format($totalMipsRestored) . "\n\n";

// 3. Resetear objetivos fijados hacia el admin y objetivos actuales de bots
$db->update("UPDATE " . DB_PREFIX . "bots SET target_galaxy = 0, target_system = 0, target_planet = 0, target_type = '', target_initial_mse = 0, siege_cycles = 0;");
echo "Fijaciones tácticas de todos los bots reseteadas.\n";

// 4. Purgar intel sobre el admin (User ID 1 / Planeta [1:1:2]) de la memoria táctica
$db->delete("DELETE FROM " . DB_PREFIX . "bot_intel WHERE galaxy = 1 AND system = 1 AND planet = 2;");
$db->delete("DELETE FROM " . DB_PREFIX . "bot_intel WHERE target_user_id = 1;");
echo "Memoria táctica sobre cuentas de administrador purgada de uni1_bot_intel.\n\n";

echo "========================================================================\n";
echo "OPERACIÓN DE RETORNO Y EXCLUSIÓN DE ADMIN COMPLETADA CON ÉXITO\n";
echo "========================================================================\n";

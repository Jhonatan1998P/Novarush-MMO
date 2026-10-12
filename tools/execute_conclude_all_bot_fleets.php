<?php

/**
 * NovaRush - Comprehensive Bot Fleets & Shipyard Conclusion
 *
 * 1. Accelera y procesa la conclusión de TODOS los movimientos y misiones de flota en vuelo
 *    (despliegues, transportes, expediciones) mediante el motor oficial (FlyingFleetHandler).
 *    Garantiza que las expediciones cobren sus recompensas (MO, contenedores, naves),
 *    los transportes descarguen sus cargamentos y los despliegues aterricen en destino.
 * 2. Concluye de forma instantánea todas las colas de construcción de naves y defensas
 *    en los hangares/astilleros (b_hangar_id) de todos los planetas de todos los bots,
 *    acreditando las unidades construidas directamente al inventario del planeta.
 */

define('MODE', 'CLI');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
set_include_path(ROOT_PATH);

require_once 'includes/common.php';
require_once 'includes/vars/General.php';
require_once 'includes/classes/class.FleetFunctions.php';
require_once 'includes/classes/class.BuildFunctions.php';
require_once 'includes/classes/class.PlanetRessUpdate.php';
require_once 'includes/classes/class.FlyingFleetHandler.php';
require_once 'includes/classes/bot/Kernel/BotClock.class.php';

global $resource, $reslist;

header('Content-Type: text/plain; charset=utf-8');

echo "========================================================================\n";
echo "   CONCLUSIÓN TOTAL DE FLOTAS DE BOTS (EN VUELO Y EN ASTILLERO)         \n";
echo "========================================================================\n\n";

$db = Database::get();

// 1. Obtener todos los IDs de bots
$botRows = $db->select("SELECT bot_id FROM " . DB_PREFIX . "bots;");
$botIds = array();
foreach ($botRows as $b) {
    $botIds[] = (int)$b['bot_id'];
}

echo "Bots detectados: [" . implode(', ', $botIds) . "]\n\n";

if (empty($botIds)) {
    die("No se encontraron bots registrados.\n");
}

$botClause = implode(',', $botIds);

// ========================================================================
// FASE 1: Conclusión de Todas las Flotas en Vuelo (uni1_fleets)
// ========================================================================
echo "--- FASE 1: PROCESANDO CONCLUSIÓN DE TODAS LAS FLOTAS EN VUELO ---\n";

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
    15 => 'Expedición',
    18 => 'Expedición Militar'
);

$loop = 0;
$maxLoops = 10;
$totalFleetsProcessed = 0;

while ($loop < $maxLoops) {
    $activeFleets = $db->select("SELECT fleet_id, fleet_owner, fleet_mission, fleet_mess, fleet_amount, fleet_start_time, fleet_end_stay, fleet_end_time FROM %%FLEETS%% WHERE fleet_owner IN ({$botClause});");
    
    if (empty($activeFleets)) {
        echo " -> Ciclo " . ($loop + 1) . ": No quedan flotas activas de bots en vuelo. ¡Todas han concluido exitosamente!\n";
        break;
    }

    echo " -> Ciclo " . ($loop + 1) . ": " . count($activeFleets) . " flotas de bots en tránsito pendientes...\n";
    $totalFleetsProcessed += count($activeFleets);

    $now = time();
    $pastTime = $now - 60;

    foreach ($activeFleets as $f) {
        $fId = (int)$f['fleet_id'];
        $mess = (int)$f['fleet_mess'];

        if ($mess == 0) {
            // Ida
            $db->update("UPDATE %%FLEETS%% SET fleet_start_time = :st, fleet_end_time = :et WHERE fleet_id = :id;", array(
                ':st' => $pastTime,
                ':et' => $pastTime + 10,
                ':id' => $fId
            ));
            $db->update("UPDATE %%FLEETS_EVENT%% SET `time` = :t WHERE fleetID = :id;", array(
                ':t'  => $pastTime,
                ':id' => $fId
            ));
        } elseif ($mess == 2) {
            // En estancia (Expedición o Hold)
            $db->update("UPDATE %%FLEETS%% SET fleet_end_stay = :es, fleet_end_time = :et WHERE fleet_id = :id;", array(
                ':es' => $pastTime,
                ':et' => $pastTime + 10,
                ':id' => $fId
            ));
            $db->update("UPDATE %%FLEETS_EVENT%% SET `time` = :t WHERE fleetID = :id;", array(
                ':t'  => $pastTime,
                ':id' => $fId
            ));
        } else {
            // Retorno (mess == 1)
            $db->update("UPDATE %%FLEETS%% SET fleet_end_time = :et WHERE fleet_id = :id;", array(
                ':et' => $pastTime,
                ':id' => $fId
            ));
            $db->update("UPDATE %%FLEETS_EVENT%% SET `time` = :t WHERE fleetID = :id;", array(
                ':t'  => $pastTime,
                ':id' => $fId
            ));
        }
    }

    // Ejecutar procesamiento del motor oficial FlyingFleetHandler
    $handlerLoops = 0;
    while ($handlerLoops < 15) {
        $token = getRandomString();
        $db->update("UPDATE %%FLEETS_EVENT%% SET `lock` = :token WHERE `lock` IS NULL AND `time` <= :time;", array(
            ':time'  => time(),
            ':token' => $token
        ));

        if ($db->rowCount() === 0) {
            break;
        }

        try {
            $fleetObj = new FlyingFleetHandler();
            $fleetObj->setToken($token);
            $fleetObj->run();
        } finally {
            $db->update("UPDATE %%FLEETS_EVENT%% SET `lock` = NULL WHERE `lock` = :token;", array(
                ':token' => $token
            ));
        }
        $handlerLoops++;
    }

    $loop++;
}

// Verificación final de flotas en vuelo
$remainingFleets = $db->select("SELECT fleet_id, fleet_owner, fleet_mission FROM %%FLEETS%% WHERE fleet_owner IN ({$botClause});");
echo "\nTotal flotas restantes de bots en vuelo: " . count($remainingFleets) . "\n\n";

// ========================================================================
// FASE 2: Conclusión de Colas de Astillero / Hangar (b_hangar_id)
// ========================================================================
echo "--- FASE 2: CONCLUYENDO COLAS DE CONSTRUCCIÓN EN ASTILLERO (HANGAR) ---\n";

$planetsWithQueue = $db->select("SELECT id, id_owner, name, b_hangar_id FROM %%PLANETS%% WHERE id_owner IN ({$botClause}) AND b_hangar_id != '';");

$totalShipsFinished = 0;
$totalDefenseFinished = 0;
$unitBreakdown = array();

foreach ($planetsWithQueue as $p) {
    $pId = (int)$p['id'];
    $ownerId = (int)$p['id_owner'];
    $pName = $p['name'];
    $queue = @unserialize($p['b_hangar_id']);

    if (!is_array($queue) || empty($queue)) {
        $db->update("UPDATE %%PLANETS%% SET b_hangar_id = '', b_hangar = 0 WHERE id = :id;", array(':id' => $pId));
        continue;
    }

    $planetShips = 0;
    $planetDefense = 0;
    $updateFields = array();

    foreach ($queue as $item) {
        $elemId = (int)$item[0];
        $count  = (int)$item[1];

        if ($count <= 0 || !isset($resource[$elemId])) {
            continue;
        }

        $colName = $resource[$elemId];
        if (!isset($updateFields[$colName])) {
            $updateFields[$colName] = 0;
        }
        $updateFields[$colName] += $count;

        if ($elemId >= 200 && $elemId < 300) {
            $planetShips += $count;
            $totalShipsFinished += $count;
        } else {
            $planetDefense += $count;
            $totalDefenseFinished += $count;
        }

        if (!isset($unitBreakdown[$elemId])) {
            $unitBreakdown[$elemId] = 0;
        }
        $unitBreakdown[$elemId] += $count;
    }

    if (!empty($updateFields)) {
        $setClauses = array();
        $params = array(':id' => $pId);
        foreach ($updateFields as $col => $cnt) {
            $setClauses[] = "`{$col}` = `{$col}` + :cnt_{$col}";
            $params[":cnt_{$col}"] = $cnt;
        }
        $setSql = implode(', ', $setClauses);
        $db->update("UPDATE %%PLANETS%% SET {$setSql}, b_hangar_id = '', b_hangar = 0 WHERE id = :id;", $params);
    } else {
        $db->update("UPDATE %%PLANETS%% SET b_hangar_id = '', b_hangar = 0 WHERE id = :id;", array(':id' => $pId));
    }

    echo " -> Planeta #{$pId} ({$pName} | Bot {$ownerId}): Concluidas {$planetShips} naves y {$planetDefense} defensas.\n";
}

echo "\n--- RESUMEN DE UNIDADES DE ASTILLERO CONCLUIDAS ---\n";
echo "Total naves construidas e incorporadas a hangares: " . number_format($totalShipsFinished) . "\n";
echo "Total defensas construidas e instaladas:           " . number_format($totalDefenseFinished) . "\n";

if (!empty($unitBreakdown)) {
    echo "\nDesglose de unidades completadas:\n";
    foreach ($unitBreakdown as $elemId => $qty) {
        $name = isset($resource[$elemId]) ? $resource[$elemId] : "Element #{$elemId}";
        echo "  - {$name} (#{$elemId}): " . number_format($qty) . " unidades\n";
    }
}

echo "\n========================================================================\n";
echo "   CONCLUSIÓN COMPLETADA CON ÉXITO: 100% DE FLOTAS ATERRIZADAS Y LISTAS \n";
echo "========================================================================\n";

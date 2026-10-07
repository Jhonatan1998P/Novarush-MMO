<?php

/**
 * NovaRush - Conclude Bot Fleets & Reset All Target Selectors
 *
 * 1. Accelera y procesa la conclusión de todos los envíos y flotas en vuelo de todos los bots
 *    a través del motor oficial (FleetHandler / FlyingFleetHandler), garantizando que los
 *    recursos transportados se entreguen, las flotas desplegadas aterricen y las naves de retorno vuelvan a sus hangares.
 * 2. Limpia todos los selectores de objetivos (target_galaxy, target_system, target_planet, target_type, siege_cycles, fob_planet_id)
 *    en uni1_bots para que todos los bots queden en estado limpio desde cero.
 */

define('MODE', 'CLI');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
set_include_path(ROOT_PATH);

require_once 'includes/common.php';
require_once 'includes/classes/class.FleetFunctions.php';
require_once 'includes/classes/class.FlyingFleetHandler.php';

header('Content-Type: text/plain; charset=utf-8');

echo "========================================================================\n";
echo "   CONCLUSIÓN DE ENVÍOS Y LIMPIEZA DE SELECTORES DE OBJETIVOS DE BOTS   \n";
echo "========================================================================\n\n";

$db = Database::get();

// 1. Obtener todos los IDs de bots
$botRows = $db->select("SELECT bot_id FROM " . DB_PREFIX . "bots;");
$botIds = array();
foreach ($botRows as $b) {
    $botIds[] = (int)$b['bot_id'];
}

echo "Bots registrados en el sistema: [" . implode(', ', $botIds) . "]\n\n";

if (empty($botIds)) {
    die("No se encontraron bots.\n");
}

$botClause = implode(',', $botIds);

// 2. Concluir todas las flotas de los bots paso a paso
echo "--- 1. PROCESANDO CONCLUSIÓN DE FLOTAS EN VUELO ---\n";

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

$loop = 0;
while ($loop < 10) {
    // Buscar flotas activas de bots
    $activeFleets = $db->select("SELECT fleet_id, fleet_owner, fleet_mission, fleet_mess, fleet_start_id, fleet_end_id FROM %%FLEETS%% WHERE fleet_owner IN ({$botClause});");
    if (empty($activeFleets)) {
        echo " -> No quedan flotas activas de bots en vuelo. Todas han concluido con éxito.\n";
        break;
    }

    echo " -> Ciclo " . ($loop + 1) . ": " . count($activeFleets) . " flotas de bots en proceso...\n";

    // Adelantar timestamps para que el motor las procese de inmediato
    $now = time();
    $pastStart = $now - 60;
    $pastEnd   = $now - 10;

    foreach ($activeFleets as $f) {
        $fId = (int)$f['fleet_id'];
        $mId = (int)$f['fleet_mission'];
        $mess = (int)$f['fleet_mess'];
        $mName = isset($missionNames[$mId]) ? $missionNames[$mId] : "Misión #{$mId}";

        // Si la flota va de ida, forzar inicio en el pasado; si va de vuelta, forzar fin en el pasado
        if ($mess == 0) {
            $db->update("UPDATE %%FLEETS%% SET fleet_start_time = :st, fleet_end_time = :et WHERE fleet_id = :id;", array(
                ':st' => $pastStart,
                ':et' => $pastEnd,
                ':id' => $fId
            ));
            $db->update("UPDATE %%FLEETS_EVENT%% SET `time` = :t WHERE fleetID = :id;", array(
                ':t'  => $pastStart,
                ':id' => $fId
            ));
        } else {
            $db->update("UPDATE %%FLEETS%% SET fleet_end_time = :et WHERE fleet_id = :id;", array(
                ':et' => $pastEnd,
                ':id' => $fId
            ));
            $db->update("UPDATE %%FLEETS_EVENT%% SET `time` = :t WHERE fleetID = :id;", array(
                ':t'  => $pastEnd,
                ':id' => $fId
            ));
        }
    }

    // Ejecutar FleetHandler
    require 'includes/FleetHandler.php';

    $loop++;
}

// Verificar estado final de flotas
$remainingFleets = $db->select("SELECT fleet_id, fleet_owner, fleet_mission FROM %%FLEETS%% WHERE fleet_owner IN ({$botClause});");
echo "\nTotal flotas restantes de bots en vuelo: " . count($remainingFleets) . "\n\n";

// 3. Limpiar todos los selectores de objetivos en uni1_bots
echo "--- 2. LIMPIEZA DE SELECTORES DE OBJETIVOS DE BOTS ---\n";

$updateResult = $db->update(
    "UPDATE " . DB_PREFIX . "bots 
     SET target_galaxy = 0, 
         target_system = 0, 
         target_planet = 0, 
         target_type = '', 
         target_initial_mse = 0, 
         target_lock_time = 0, 
         siege_cycles = 0, 
         fob_planet_id = 0, 
         siege_spent_mse = 0;"
);

echo " -> Columnas de objetivos reiniciadas en uni1_bots para todos los bots.\n";

// Limpiar tareas diferidas de objetivos pasados
$db->update("UPDATE " . DB_PREFIX . "bot_tasks SET status = 'completed' WHERE status != 'completed';");
echo " -> Tareas programadas de bots archivadas como 'completed'.\n\n";

// 4. Mostrar estado actual de uni1_bots
echo "--- 3. ESTADO ACTUAL DE SELECTORES EN uni1_bots ---\n";
$afterBots = $db->select("SELECT bot_id, personality, target_galaxy, target_system, target_planet, target_type, siege_cycles, fob_planet_id FROM " . DB_PREFIX . "bots;");

printf("%-8s | %-12s | %-16s | %-12s | %-12s | %-8s\n", "Bot ID", "Personalidad", "Objetivo Coords", "Categoría", "Ciclos Asedio", "FOB ID");
echo str_repeat('-', 80) . "\n";
foreach ($afterBots as $ab) {
    $coords = "[{$ab['target_galaxy']}:{$ab['target_system']}:{$ab['target_planet']}]";
    $cat = !empty($ab['target_type']) ? $ab['target_type'] : "(ninguno)";
    printf("%-8d | %-12s | %-16s | %-12s | %-12d | %-8d\n", 
        $ab['bot_id'], 
        $ab['personality'], 
        $coords, 
        $cat, 
        $ab['siege_cycles'], 
        $ab['fob_planet_id']
    );
}

echo "\n========================================================================\n";
echo "   SISTEMA LIMPIO: LISTO PARA NUEVA SELECCIÓN Y LOGÍSTICA A DEMANDA     \n";
echo "========================================================================\n";

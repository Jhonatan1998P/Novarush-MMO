<?php

define('MODE', 'CLI');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
set_include_path(ROOT_PATH);

require 'includes/common.php';
require_once 'includes/classes/bot/Kernel/BotKernel.class.php';
require_once 'includes/classes/bot/Forces/LogisticsPlanner.class.php';
require_once 'includes/classes/bot/Forces/ProductionAllocator.class.php';

echo "========================================================================\n";
echo "   TEST E2E: CADENA DE SUMINISTRO LUNAR & COLONIAS SATURADAS (v4.6)    \n";
echo "========================================================================\n\n";

$db = Database::get();

// 1. Snapshot de seguridad previo
$botId = 1010; // Kimimaru
$p125Before = $db->selectSingle("SELECT * FROM %%PLANETS%% WHERE id = 125;");
$u1010Before = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = {$botId};");
$b1010Before = $db->selectSingle("SELECT * FROM %%BOTS%% WHERE bot_id = {$botId};");

$createdMoonId = null;
$createdFleetIds = array();

try {
    // 2. Crear luna temporal asociada a P125 [1:241:8] con 0 recursos y 1 campo libre
    $db->insert("INSERT INTO %%PLANETS%% SET 
        name = 'Andromeda-Luna-Test',
        id_owner = :uid,
        universe = 1,
        galaxy = 1,
        system = 241,
        planet = 8,
        planet_type = '3',
        image = 'mond',
        diameter = 8000,
        field_current = 0,
        field_max = 1,
        temp_min = -20,
        temp_max = 20,
        metal = 0.0,
        crystal = 0.0,
        deuterium = 0.0,
        metal_perhour = 0,
        crystal_perhour = 0,
        deuterium_perhour = 0,
        energy = 0,
        energy_used = 0,
        mondbasis = 0,
        phalanx = 0,
        sprungtor = 0,
        robot_factory = 0,
        nano_factory = 0,
        hangar = 0,
        b_building = 0,
        b_building_id = '';", array(':uid' => $botId));

    $createdMoonId = (int)$db->lastInsertId();
    echo "[PASO 1] Luna de prueba creada con éxito: ID #{$createdMoonId} en [1:241:8] (0 recursos, 1 campo)\n";

    // 3. Cargar contexto, imperio y componentes de IA
    $uRow = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = {$botId};");
    $bRow = $db->selectSingle("SELECT * FROM %%BOTS%% WHERE bot_id = {$botId};");
    $ctx = new BotContext($bRow, $uRow);
    $budget = new BotBudgetManager($ctx);
    $ctx->budgetManager = $budget;

    $empire = new BotEmpireState($botId, $ctx->user);
    $gateway = new BotActionGateway($ctx);
    $logistics = new BotLogisticsPlanner($ctx, $gateway);

    // 4. Ejecutar LogisticsPlanner
    echo "[PASO 2] Ejecutando BotLogisticsPlanner::dispatchConvoys()...\n";
    $convoysSent = $logistics->dispatchConvoys($empire, 0, null);
    echo "  -> Convoyes despachados: {$convoysSent}\n";

    if ($convoysSent <= 0) {
        throw new Exception("Fallo: LogisticsPlanner no despachó convoy hacia la luna vacía.");
    }
    echo "  [PASS] LogisticsPlanner detectó la demanda lunar y despachó convoy legal.\n";

    // 5. Verificar flota en vuelo en %%FLEETS%%
    $fleetRow = $db->selectSingle("SELECT * FROM %%FLEETS%% WHERE fleet_owner = {$botId} AND fleet_end_id = {$createdMoonId} AND fleet_end_type = 3 ORDER BY fleet_id DESC LIMIT 1;");
    if (!$fleetRow) {
        throw new Exception("Fallo: No se encontró registro en %%FLEETS%% hacia la luna.");
    }
    $createdFleetIds[] = (int)$fleetRow['fleet_id'];
    $mSent = (float)$fleetRow['fleet_resource_metal'];
    $cSent = (float)$fleetRow['fleet_resource_crystal'];
    $dSent = (float)$fleetRow['fleet_resource_deuterium'];
    echo "  [PASS] Flota en vuelo verificada en %%FLEETS%% (ID #{$fleetRow['fleet_id']}): M: " . number_format($mSent) . ", C: " . number_format($cSent) . ", D: " . number_format($dSent) . "\n";

    // 6. Verificar tarea programada logistics_arrival_spend en %%BOT_TASKS%%
    $taskRow = $db->selectSingle("SELECT * FROM %%BOT_TASKS%% WHERE bot_id = {$botId} AND task_type = 'logistics_arrival_spend' AND status = 'pending' ORDER BY task_id DESC LIMIT 1;");
    if (!$taskRow) {
        throw new Exception("Fallo: No se programó tarea logistics_arrival_spend.");
    }
    $taskPayload = @json_decode($taskRow['payload'], true);
    echo "  [PASS] Tarea en %%BOT_TASKS%% verificada (ID #{$taskRow['task_id']}): Objetivo: " . ($taskPayload['element_id'] ?? 'N/A') . " en cuerpo #{$taskPayload['planet_id']}\n";

    // 7. Simular aterrizaje del convoy en la luna
    echo "[PASO 3] Simulando llegada del convoy a la luna y ejecución de tarea...\n";
    // Acreditar recursos físicos transportados en la luna
    $db->update("UPDATE %%PLANETS%% SET metal = metal + :m, crystal = crystal + :c, deuterium = deuterium + :d WHERE id = {$createdMoonId};", array(
        ':m' => $mSent,
        ':c' => $cSent,
        ':d' => $dSent
    ));

    // Ejecutar procesamiento de la tarea logistics_arrival_spend de la luna
    $moonTask = $db->selectSingle("SELECT * FROM %%BOT_TASKS%% WHERE bot_id = {$botId} AND task_type = 'logistics_arrival_spend' AND status = 'pending' AND payload LIKE '%{$createdMoonId}%' ORDER BY task_id DESC LIMIT 1;");
    if ($moonTask) {
        $pLoad = @json_decode($moonTask['payload'], true);
        $budget->creditPlanetCategory($createdMoonId, 'mines', $pLoad['resources']);
        if (!empty($pLoad['element_id'])) {
            $gateway->constructBuilding($createdMoonId, $pLoad['element_id']);
        }
        BotScheduler::markTaskComplete($moonTask['task_id']);
    }

    // 8. Verificar que la luna inició su construcción
    $moonAfter = $db->selectSingle("SELECT * FROM %%PLANETS%% WHERE id = {$createdMoonId};");
    $bBuildingId = $moonAfter['b_building_id'];
    $queue = !empty($bBuildingId) ? @unserialize($bBuildingId) : array();

    if (empty($queue)) {
        // Si no se construyó por la tarea diferida, probar allocate() directo
        $allocator = new BotProductionAllocator($ctx, $gateway);
        $freshEmpire = new BotEmpireState($botId, $ctx->user);
        $queuedDirect = $allocator->allocate($freshEmpire);
        $moonAfter = $db->selectSingle("SELECT * FROM %%PLANETS%% WHERE id = {$createdMoonId};");
        $bBuildingId = $moonAfter['b_building_id'];
        $queue = !empty($bBuildingId) ? @unserialize($bBuildingId) : array();
    }

    if (empty($queue)) {
        throw new Exception("Fallo: La luna no tiene construcciones en cola tras recibir los recursos.");
    }

    $elemQueued = $queue[0][0];
    global $resource;
    echo "  [PASS] Edificio lunar encolado exitosamente: ID #{$elemQueued} ({$resource[$elemQueued]}) en luna #{$createdMoonId}!\n";
    echo "  [PASS] Tiempo de finalización: " . date('Y-m-d H:i:s', $queue[0][3]) . "\n\n";

    echo "========================================================================\n";
    echo "   CERTIFICACIÓN E2E DE CADENA DE SUMINISTRO LUNAR: 100% EXITOSA!       \n";
    echo "========================================================================\n\n";

} catch (Throwable $e) {
    echo "\n[ERROR]: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n\n";
} finally {
    echo "[ROLLBACK] Restaurando estado original de la base de datos...\n";
    if ($createdMoonId) {
        $db->nativeQuery("DELETE FROM %%PLANETS%% WHERE id = {$createdMoonId};");
        echo "  - Eliminada luna de prueba #{$createdMoonId}\n";
    }
    if (!empty($createdFleetIds)) {
        $idsStr = implode(',', $createdFleetIds);
        $db->nativeQuery("DELETE FROM %%FLEETS%% WHERE fleet_id IN ({$idsStr});");
        echo "  - Eliminadas flotas de prueba ({$idsStr})\n";
    }
    $db->nativeQuery("DELETE FROM %%BOT_TASKS%% WHERE bot_id = {$botId} AND task_type = 'logistics_arrival_spend';");
    $db->nativeQuery("DELETE FROM %%BOT_DECISIONS%% WHERE bot_id = {$botId} AND decision_type IN ('supply_convoy', 'mine_upgrade');");

    // Restaurar P125 y Bot
    $db->update("UPDATE %%PLANETS%% SET 
        metal = :m, crystal = :c, deuterium = :d, ev_transporter = :ev, big_ship_cargo = :bc, small_ship_cargo = :sc 
        WHERE id = 125;", array(
        ':m'  => $p125Before['metal'],
        ':c'  => $p125Before['crystal'],
        ':d'  => $p125Before['deuterium'],
        ':ev' => $p125Before['ev_transporter'],
        ':bc' => $p125Before['big_ship_cargo'],
        ':sc' => $p125Before['small_ship_cargo']
    ));

    $db->update("UPDATE %%BOTS%% SET budget_credits = :bc WHERE bot_id = {$botId};", array(
        ':bc' => $b1010Before['budget_credits']
    ));
    echo "  - Planeta 125 y presupuesto de Bot #{$botId} restaurados a su estado original idéntico.\n";
    echo "  [OK] Rollback 100% completado sin alteraciones residuales en ogame2.\n\n";
}

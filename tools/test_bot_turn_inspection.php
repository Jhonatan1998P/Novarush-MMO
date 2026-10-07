<?php

define('MODE', 'CLI');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
set_include_path(ROOT_PATH);

require_once 'includes/common.php';
require_once 'includes/classes/bot/BotEngine.class.php';

header('Content-Type: text/plain; charset=utf-8');

echo "========================================================================\n";
echo "   OBSERVACIÓN EN VIVO: CICLO DE BOT 1007 (LaParca) TRAS RESET         \n";
echo "========================================================================\n\n";

$db = Database::get();

$row = $db->selectSingle("SELECT MAX(id) as max_id FROM " . DB_PREFIX . "bot_decisions;");
$lastLogId = !empty($row['max_id']) ? (int)$row['max_id'] : 0;

echo "Ejecutando ciclo de Bot 1007 (LaParca)...\n";
$result = BotEngine::runSingleBot(1007);

echo "Resultado del Kernel: " . json_encode($result) . "\n\n";

// 1. Estado de selección de objetivo en uni1_bots
echo "--- 1. ESTADO DEL OBJETIVO TRAS EL CICLO (uni1_bots) ---\n";
$botRow = $db->selectSingle("SELECT bot_id, target_galaxy, target_system, target_planet, target_type, siege_cycles, fob_planet_id FROM " . DB_PREFIX . "bots WHERE bot_id = 1007;");
if (!empty($botRow)) {
    echo "Bot ID:        {$botRow['bot_id']}\n";
    echo "Objetivo:      [{$botRow['target_galaxy']}:{$botRow['target_system']}:{$botRow['target_planet']}]\n";
    echo "Categoría:     {$botRow['target_type']}\n";
    echo "Ciclos Asedio: {$botRow['siege_cycles']}\n";
    echo "FOB ID:        {$botRow['fob_planet_id']}\n\n";
}

// 2. Flotas despachadas en este ciclo
echo "--- 2. FLOTAS DESPACHADAS EN ESTE CICLO (uni1_fleets) ---\n";
$fleets = $db->select("SELECT fleet_id, fleet_mission, fleet_start_id, fleet_end_id, fleet_resource_metal, fleet_resource_crystal, fleet_resource_deuterium FROM %%FLEETS%% WHERE fleet_owner = 1007;");
if (empty($fleets)) {
    echo "No se despacharon flotas en este ciclo (todo en reposo o evaluando).\n\n";
} else {
    $missionNames = array(1 => 'Ataque', 3 => 'Transporte (Suministro)', 4 => 'Despliegue/Staging', 6 => 'Espionaje', 8 => 'Reciclaje');
    foreach ($fleets as $f) {
        $mName = isset($missionNames[$f['fleet_mission']]) ? $missionNames[$f['fleet_mission']] : "Misión #{$f['fleet_mission']}";
        echo " - Flota #{$f['fleet_id']}: {$mName} desde #{$f['fleet_start_id']} hacia #{$f['fleet_end_id']} [Metal: " . number_format($f['fleet_resource_metal']) . ", Cristal: " . number_format($f['fleet_resource_crystal']) . ", Deut: " . number_format($f['fleet_resource_deuterium']) . "]\n";
    }
    echo "\n";
}

// 3. Decisiones registradas en telemetría
echo "--- 3. TELEMETRÍA Y DECISIONES EN ESTE CICLO (uni1_bot_decisions) ---\n";
$decisions = $db->select("SELECT decision_type, context_summary, created_at FROM " . DB_PREFIX . "bot_decisions WHERE bot_id = 1007 AND id > :lastId ORDER BY id ASC;", array(':lastId' => $lastLogId));
if (empty($decisions)) {
    echo "No hay nuevas decisiones registradas.\n";
} else {
    foreach ($decisions as $d) {
        echo " [{$d['decision_type']}] {$d['context_summary']}\n";
    }
}

echo "\n========================================================================\n";

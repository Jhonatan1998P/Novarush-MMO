<?php
define('MODE', 'CLI');
define('ROOT_PATH', './');
require_once 'includes/common.php';

$db = Database::get();
$bl = $db->select("SELECT * FROM " . DB_PREFIX . "bot_target_blacklist;");
echo "=== BLACKLIST ===\n";
echo json_encode($bl, JSON_PRETTY_PRINT) . "\n";

$cd = $db->select("SELECT * FROM " . DB_PREFIX . "bot_planet_cooldown;");
echo "=== COOLDOWNS ===\n";
echo json_encode($cd, JSON_PRETTY_PRINT) . "\n";

$fleets = $db->select("SELECT fleet_id, fleet_owner, fleet_mission, fleet_start_galaxy, fleet_start_system, fleet_start_planet, fleet_end_galaxy, fleet_end_system, fleet_end_planet, fleet_amount, fleet_start_time, fleet_end_time, fleet_mess FROM %%FLEETS%% WHERE fleet_owner IN (1007, 1010, 1011);");
echo "=== ALL BOT FLEETS IN FLIGHT ===\n";
echo json_encode($fleets, JSON_PRETTY_PRINT) . "\n";

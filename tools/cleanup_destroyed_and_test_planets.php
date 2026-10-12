<?php

define('MODE', 'CLI');
define('ROOT_PATH', 'C:/Users/Ortega/Downloads/OGAME/ogame/');
chdir(ROOT_PATH);

require_once 'includes/common.php';

$db = Database::get();

// 1. Identify test planets on Godwar (id_owner = 4)
$testPlanets = $db->select("SELECT id, name, galaxy, system, planet FROM %%PLANETS%% WHERE id IN (88881, 88882);");

// 2. Identify all destroyed planets across the entire universe
$destroyedPlanets = $db->select("SELECT id, name, galaxy, system, planet, id_owner, destruyed FROM %%PLANETS%% WHERE destruyed != 0 OR name LIKE '%destruid%' OR name LIKE '%destruy%';");

echo "=== PLANET CLEANUP AUDIT ===\n";
echo "Test planets on Godwar found: " . count($testPlanets) . "\n";
foreach ($testPlanets as $tp) {
    echo "  - [ID {$tp['id']}] {$tp['name']} at [{$tp['galaxy']}:{$tp['system']}:{$tp['planet']}]\n";
}

echo "Destroyed planets across universe found: " . count($destroyedPlanets) . "\n";
foreach ($destroyedPlanets as $dp) {
    echo "  - [ID {$dp['id']}] {$dp['name']} at [{$dp['galaxy']}:{$dp['system']}:{$dp['planet']}] (Owner: {$dp['id_owner']}, Destruyed TS: {$dp['destruyed']})\n";
}

$allIdsToDelete = array();
foreach ($testPlanets as $tp) {
    $allIdsToDelete[] = (int)$tp['id'];
}
foreach ($destroyedPlanets as $dp) {
    $allIdsToDelete[] = (int)$dp['id'];
}

$allIdsToDelete = array_unique($allIdsToDelete);

if (!empty($allIdsToDelete)) {
    $idList = implode(',', $allIdsToDelete);
    echo "\nDeleting planets: ({$idList})\n";

    // Delete planets
    $deletedPlanets = $db->delete("DELETE FROM %%PLANETS%% WHERE id IN ({$idList});");
    echo "Deleted {$deletedPlanets} rows from %%PLANETS%%\n";

    // Delete intel
    $deletedIntel = $db->delete("DELETE FROM %%BOT_INTEL%% WHERE target_planet_id IN ({$idList});");
    echo "Deleted {$deletedIntel} rows from %%BOT_INTEL%%\n";

    // Delete cooldowns
    $cooldownTable = DB_PREFIX . 'bot_planet_cooldown';
    $deletedCooldown = $db->delete("DELETE FROM {$cooldownTable} WHERE planet_id IN ({$idList});");
    echo "Deleted {$deletedCooldown} rows from {$cooldownTable}\n";

    // Delete blacklist records
    $deletedBlacklist = $db->delete("DELETE FROM %%BOT_TARGET_BLACKLIST%% WHERE target_planet_id IN ({$idList});");
    echo "Deleted {$deletedBlacklist} rows from %%BOT_TARGET_BLACKLIST%%\n";
} else {
    echo "No planets to delete.\n";
}

// Verification:
$remainingGodwar = $db->select("SELECT id, name, galaxy, system, planet FROM %%PLANETS%% WHERE id_owner = 4 ORDER BY id ASC;");
echo "\n=== REMAINING GODWAR PLANETS (" . count($remainingGodwar) . ") ===\n";
foreach ($remainingGodwar as $p) {
    echo "  - [ID {$p['id']}] {$p['name']} [{$p['galaxy']}:{$p['system']}:{$p['planet']}]\n";
}

$remainingDestroyed = $db->select("SELECT COUNT(*) as cnt FROM %%PLANETS%% WHERE destruyed != 0 OR name LIKE '%destruid%';");
echo "\nRemaining destroyed planets in universe: " . $remainingDestroyed[0]['cnt'] . "\n";
echo "=== CLEANUP FINISHED SUCCESSFULLY ===\n";

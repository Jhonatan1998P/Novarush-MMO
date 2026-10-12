<?php
define('MODE', 'CLI');
define('ROOT_PATH', 'C:/Users/Ortega/Downloads/OGAME/ogame/');
chdir(ROOT_PATH);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
require_once 'includes/common.php';
require_once 'includes/classes/class.FleetFunctions.php';

$pdo = Database::get()->getHandle();
$now = time();

echo "=========================================================\n";
echo " BOT FRESH START: RESET INTEL, TARGETS & RESOURCE EQUALIZER\n";
echo "=========================================================\n";

$pdo->beginTransaction();

try {
    // ---------------------------------------------------------
    // 1. RECALL & RESTORE ALL IN-FLIGHT FLEETS FOR BOTS
    // ---------------------------------------------------------
    echo "\n>>> 1. Docking all in-flight bot fleets...\n";
    $botIds = $pdo->query("SELECT bot_id FROM uni1_bots ORDER BY bot_id ASC")->fetchAll(PDO::FETCH_COLUMN);
    $botIdList = implode(',', $botIds);

    $fleets = $pdo->query("SELECT * FROM uni1_fleets WHERE fleet_owner IN ({$botIdList})")->fetchAll(PDO::FETCH_ASSOC);
    echo "Found " . count($fleets) . " fleets in flight for bots.\n";

    global $resource;

    foreach ($fleets as $fleet) {
        $fleetId = (int)$fleet['fleet_id'];
        $originPlanetId = (int)$fleet['fleet_start_id'];
        $ships = FleetFunctions::unserialize($fleet['fleet_array']);

        // Restore ships to origin planet
        if (!empty($ships)) {
            $shipUpdates = array();
            foreach ($ships as $shipId => $shipCount) {
                if (isset($resource[$shipId])) {
                    $colName = $resource[$shipId];
                    $shipCount = (float)$shipCount;
                    $shipUpdates[] = "`{$colName}` = `{$colName}` + {$shipCount}";
                }
            }
            if (!empty($shipUpdates)) {
                $sqlRestoreShips = "UPDATE uni1_planets SET " . implode(', ', $shipUpdates) . " WHERE id = {$originPlanetId};";
                $pdo->exec($sqlRestoreShips);
            }
        }

        // Restore cargo to origin planet
        $mCargo = (float)$fleet['fleet_resource_metal'];
        $cCargo = (float)$fleet['fleet_resource_crystal'];
        $dCargo = (float)$fleet['fleet_resource_deuterium'];
        if ($mCargo > 0 || $cCargo > 0 || $dCargo > 0) {
            $sqlRestoreCargo = "UPDATE uni1_planets SET
                metal = metal + {$mCargo},
                crystal = crystal + {$cCargo},
                deuterium = deuterium + {$dCargo}
                WHERE id = {$originPlanetId};";
            $pdo->exec($sqlRestoreCargo);
        }

        // Delete fleet and event
        $pdo->exec("DELETE FROM uni1_fleets WHERE fleet_id = {$fleetId};");
        $pdo->exec("DELETE FROM uni1_fleet_event WHERE fleetID = {$fleetId};");
    }
    echo "All bot fleets docked and cargo returned to origin planets successfully.\n";

    // ---------------------------------------------------------
    // 2. PURGE INTEL, BLACKLISTS, COOLDOWNS & TASKS
    // ---------------------------------------------------------
    echo "\n>>> 2. Purging bot intelligence, targets, blacklists & history...\n";

    $delIntel = $pdo->exec("DELETE FROM uni1_bot_intel;");
    echo "Deleted {$delIntel} records from uni1_bot_intel.\n";

    $delBlacklist = $pdo->exec("DELETE FROM uni1_bot_target_blacklist;");
    echo "Deleted {$delBlacklist} records from uni1_bot_target_blacklist.\n";

    $delCooldown = $pdo->exec("DELETE FROM uni1_bot_planet_cooldown;");
    echo "Deleted {$delCooldown} records from uni1_bot_planet_cooldown.\n";

    $delTasks = $pdo->exec("DELETE FROM uni1_bot_tasks;");
    echo "Deleted {$delTasks} records from uni1_bot_tasks.\n";

    $delDecisions = $pdo->exec("DELETE FROM uni1_bot_decisions;");
    echo "Deleted {$delDecisions} records from uni1_bot_decisions.\n";

    $delOpponents = $pdo->exec("DELETE FROM uni1_bot_opponents;");
    echo "Deleted {$delOpponents} records from uni1_bot_opponents.\n";

    $delLogs = $pdo->exec("DELETE FROM uni1_bot_logs;");
    echo "Deleted {$delLogs} records from uni1_bot_logs.\n";

    $delMessages = $pdo->exec("DELETE FROM uni1_messages WHERE message_owner IN ({$botIdList}) AND message_type IN (0, 5);");
    echo "Deleted {$delMessages} spy/log messages from uni1_messages.\n";

    // Reset bot target state in uni1_bots
    $stmtResetBots = $pdo->prepare("UPDATE uni1_bots SET
        target_galaxy = 0,
        target_system = 0,
        target_planet = 0,
        target_type = NULL,
        target_initial_mse = 0,
        target_lock_time = 0,
        siege_cycles = 0,
        fob_planet_id = NULL,
        siege_spent_mse = 0,
        budget_credits = NULL,
        savings_lock = NULL,
        last_spy = 0,
        last_attack = 0,
        last_logistics = 0,
        last_build = 0,
        last_recruit = 0,
        last_budget_time = 0,
        last_fleet_recruit = 0,
        last_defense_recruit = 0;");
    $stmtResetBots->execute();
    echo "Reset uni1_bots target and decision state to virgin starting conditions.\n";

    // ---------------------------------------------------------
    // 3. UNIFY & EQUITABLY DISTRIBUTE RESOURCES PER BOT
    // ---------------------------------------------------------
    echo "\n>>> 3. Unifying and equalizing resources across colonies per bot...\n";

    foreach ($botIds as $botId) {
        $user = $pdo->query("SELECT username FROM uni1_users WHERE id = {$botId}")->fetch(PDO::FETCH_ASSOC);
        $name = $user ? $user['username'] : "Bot {$botId}";

        $botRow = $pdo->query("SELECT home_planet_id FROM uni1_bots WHERE bot_id = {$botId}")->fetch(PDO::FETCH_ASSOC);
        $homePlanetId = (int)($botRow['home_planet_id'] ?? 0);

        $planets = $pdo->query("SELECT id, name, galaxy, system, planet, metal, crystal, deuterium FROM uni1_planets WHERE id_owner = {$botId} ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
        $numPlanets = count($planets);

        if ($numPlanets === 0) {
            echo "Bot {$name} has 0 planets, skipping.\n";
            continue;
        }

        $totalM = 0; $totalC = 0; $totalD = 0;
        foreach ($planets as $p) {
            $totalM += (float)$p['metal'];
            $totalC += (float)$p['crystal'];
            $totalD += (float)$p['deuterium'];
        }

        $shareM = floor($totalM / $numPlanets);
        $shareC = floor($totalC / $numPlanets);
        $shareD = floor($totalD / $numPlanets);

        $remM = $totalM - ($shareM * $numPlanets);
        $remC = $totalC - ($shareC * $numPlanets);
        $remD = $totalD - ($shareD * $numPlanets);

        echo "\nBot [{$name}] (ID {$botId}):\n";
        echo "  Colonies: {$numPlanets}\n";
        echo "  Total Sum: M=" . number_format($totalM) . " | C=" . number_format($totalC) . " | D=" . number_format($totalD) . "\n";
        echo "  Share per colony: M=" . number_format($shareM) . " | C=" . number_format($shareC) . " | D=" . number_format($shareD) . "\n";

        $stmtUpdatePlanet = $pdo->prepare("UPDATE uni1_planets SET
            metal = :m,
            crystal = :c,
            deuterium = :d,
            last_update = :ts,
            eco_hash = ''
            WHERE id = :pid");

        foreach ($planets as $p) {
            $pId = (int)$p['id'];
            $pM = $shareM;
            $pC = $shareC;
            $pD = $shareD;

            // Assign remainder units to home planet
            if ($pId === $homePlanetId || ($homePlanetId === 0 && $pId === (int)$planets[0]['id'])) {
                $pM += $remM;
                $pC += $remC;
                $pD += $remD;
            }

            $stmtUpdatePlanet->execute(array(
                ':m' => $pM,
                ':c' => $pC,
                ':d' => $pD,
                ':ts' => $now,
                ':pid' => $pId
            ));
        }
    }

    $pdo->commit();
    echo "\n>>> TRANSACTION COMMITTED SUCCESSFULLY! <<<\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "ERROR OCCURRED: " . $e->getMessage() . "\n";
    exit(1);
}

// ---------------------------------------------------------
// 4. VERIFICATION OF FINAL STATE
// ---------------------------------------------------------
echo "\n=========================================================\n";
echo " FINAL VERIFICATION\n";
echo "=========================================================\n";

$intelCount = $pdo->query("SELECT COUNT(*) FROM uni1_bot_intel")->fetchColumn();
$blacklistCount = $pdo->query("SELECT COUNT(*) FROM uni1_bot_target_blacklist")->fetchColumn();
$cooldownCount = $pdo->query("SELECT COUNT(*) FROM uni1_bot_planet_cooldown")->fetchColumn();
$tasksCount = $pdo->query("SELECT COUNT(*) FROM uni1_bot_tasks")->fetchColumn();
$flyingFleetsCount = $pdo->query("SELECT COUNT(*) FROM uni1_fleets WHERE fleet_owner IN ({$botIdList})")->fetchColumn();

echo "Intel count: {$intelCount}\n";
echo "Blacklist count: {$blacklistCount}\n";
echo "Cooldown count: {$cooldownCount}\n";
echo "Pending tasks count: {$tasksCount}\n";
echo "Active bot fleets: {$flyingFleetsCount}\n";

$botStates = $pdo->query("SELECT b.bot_id, u.username, b.target_galaxy, b.target_system, b.target_planet, b.target_type, b.fob_planet_id FROM uni1_bots b JOIN uni1_users u ON b.bot_id = u.id")->fetchAll(PDO::FETCH_ASSOC);
foreach ($botStates as $bs) {
    echo "Bot {$bs['username']} (ID {$bs['bot_id']}): Target=[{$bs['target_galaxy']}:{$bs['target_system']}:{$bs['target_planet']}], Type=" . ($bs['target_type'] ?? 'NONE') . ", FOB=" . ($bs['fob_planet_id'] ?? 'NONE') . "\n";
}

echo "\nPlanetary Resources per Bot:\n";
foreach ($botIds as $botId) {
    $user = $pdo->query("SELECT username FROM uni1_users WHERE id = {$botId}")->fetch(PDO::FETCH_ASSOC);
    $name = $user ? $user['username'] : "Bot {$botId}";
    echo "\n--- {$name} ---\n";
    $planets = $pdo->query("SELECT id, name, galaxy, system, planet, metal, crystal, deuterium FROM uni1_planets WHERE id_owner = {$botId} ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($planets as $p) {
        echo "  [ID {$p['id']}] {$p['name']} [{$p['galaxy']}:{$p['system']}:{$p['planet']}]: M=" . number_format($p['metal']) . " | C=" . number_format($p['crystal']) . " | D=" . number_format($p['deuterium']) . "\n";
    }
}

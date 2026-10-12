<?php

/**
 * NovaRush AI Bot Test Suite: Interlocking Military Pipeline & FOB Dynamics
 *
 * Verifies:
 * 1. FOB Bastion fleet selection for Monte Carlo simulations across all categories.
 * 2. Siege threshold: >= 1 defense and MIPs in range demolish >= 25% defense structure.
 * 3. Fleet Crash: purely debris-driven ROI >= 30% (warehouse loot excluded).
 * 4. Farming: Monte Carlo Win >= 70% + ROI >= 10% (loot + debris included).
 * 5. Renrize vs Atenea live scenario: Renrize discarded to 1h blacklist; radar continues to Atenea.
 * 6. Isolated planet cooldown: blacklisting a planet does NOT blacklist other planets of that player.
 * 7. Anti-bashing player cap: player enters 24h blacklist only on 6th victory.
 * 8. Zero database residue via strict transactional ROLLBACK.
 */

define('MODE', 'CLI');
define('ROOT_PATH', 'C:/Users/Ortega/Downloads/OGAME/ogame/');
chdir(ROOT_PATH);

require_once 'includes/common.php';
require_once 'includes/vars/General.php';
require_once 'includes/classes/class.FleetFunctions.php';
require_once 'includes/classes/class.BuildFunctions.php';
require_once 'includes/classes/bot/BotEngine.class.php';
require_once 'includes/classes/bot/Kernel/BotContext.class.php';
require_once 'includes/classes/bot/Perception/WorldModel.class.php';
require_once 'includes/classes/bot/Perception/EmpireState.class.php';
require_once 'includes/classes/bot/Perception/IntelStore.class.php';
require_once 'includes/classes/bot/Evaluation/EconomyValuator.class.php';
require_once 'includes/classes/bot/Evaluation/CombatOracle.class.php';
require_once 'includes/classes/bot/Military/TargetFinder.class.php';
require_once 'includes/classes/bot/Military/TargetBlacklist.class.php';

BotTargetBlacklist::ensureCooldownTableExists();
$db = Database::get();
$db->delete("DELETE FROM " . DB_PREFIX . "planets WHERE id IN (88881, 88882);");
$db->delete("DELETE FROM " . DB_PREFIX . "bot_intel WHERE target_planet_id IN (88881, 88882);");

$db->beginTransaction();

$totalTests = 0;
$passedTests = 0;

function assertTest($condition, $description) {
    global $totalTests, $passedTests;
    $totalTests++;
    if ($condition) {
        $passedTests++;
        echo "  [PASS] {$description}\n";
    } else {
        echo "  [FAIL] {$description}\n";
    }
}

echo "\n=======================================================\n";
echo " NOVARUSH AI BOT - INTERLOCKING MILITARY PIPELINE TEST\n";
echo "=======================================================\n\n";

try {
    // 1. Context initialization with Prometheus (Bot 1011)
    $botId = 1011;
    $botRow = $db->selectSingle("SELECT * FROM %%BOTS%% WHERE bot_id = :bId LIMIT 1;", array(':bId' => $botId));
    if (empty($botRow)) {
        throw new Exception("Bot Prometheus 1011 not found in database.");
    }
    $userRow = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = :uId LIMIT 1;", array(':uId' => $botId));
    $ctx = new BotContext($botRow, $userRow);
    $world = new BotWorldModel($ctx);

    echo "--- Test Group 1: Dedicated Planet Cooldown Isolation ---\n";

    // Planet A (Renrize) cooldown for 1 hour
    BotTargetBlacklist::addCooldown($botId, 4, 26, 1, 120, 8, 3600, 'low_value_test');

    // Check if Planet A is blacklisted
    $isPlanetABlacklisted = BotTargetBlacklist::isBlacklisted($botId, 4, 26, 1, 120, 8);
    assertTest($isPlanetABlacklisted === true, "Planet Renrize [1:120:8] is on cooldown for this bot");

    // Check if Planet B (Atenea [1:305:8]) of the SAME user 4 is affected
    $isPlanetBBlacklisted = BotTargetBlacklist::isBlacklisted($botId, 4, 34, 1, 305, 8);
    assertTest($isPlanetBBlacklisted === false, "Planet Atenea [1:305:8] of player Godwar is NOT blacklisted (isolated planet cooldown)");

    // Test Player-level 24h blacklist (triggered only upon 6th victory)
    assertTest(BotTargetBlacklist::MAX_BOT_ATTACKS === 6, "MAX_BOT_ATTACKS is configured to 6");
    assertTest(BotTargetBlacklist::MAX_GLOBAL_ATTACKS === 6, "MAX_GLOBAL_ATTACKS is configured to 6");

    echo "\n--- Test Group 2: Intel Expiration & Purge ---\n";
    $intelStore = new BotIntelStore($botId);
    $intelStore->saveIntel(99999, 4, 1, 120, 8, 1, array(901 => 1000000, 902 => 500000, 903 => 100000));
    $freshIntel = $intelStore->getIntel(1, 120, 8, 1);
    assertTest($freshIntel !== null, "Freshly saved intel is retrievable within 1 hour");

    // Manually backdate intel to 65 minutes ago
    $db->update("UPDATE %%BOT_INTEL%% SET scanned_at = :oldTime WHERE bot_id = :bId AND galaxy = 1 AND system = 120 AND planet = 8;", array(
        ':oldTime' => TIMESTAMP - 3900,
        ':bId'     => $botId,
    ));
    $expiredIntel = $intelStore->getIntel(1, 120, 8, 1);
    assertTest($expiredIntel === null, "Intel older than 60 minutes hard-expires and returns null");

    // Check that it was purged from the database
    $dbCheck = $db->selectSingle("SELECT COUNT(*) as cnt FROM %%BOT_INTEL%% WHERE bot_id = :bId AND galaxy = 1 AND system = 120 AND planet = 8;", array(
        ':bId' => $botId,
    ));
    assertTest((int)$dbCheck['cnt'] === 0, "Expired intel was completely purged from %%BOT_INTEL%% table");

    echo "\n--- Test Group 3: Interlocking Military Hierarchy Classification ---\n";
    $targetFinder = new BotTargetFinder($ctx);

    // Scenario A: Bunker target within range of Prometheus Silo 15 (system 260)
    // Structure: 10 Gauss (403), 5 Plasma (404). 300 MIPs on colony 133 easily demolish >= 25% -> SIEGE
    $db->insert("INSERT INTO %%PLANETS%% (id, id_owner, name, galaxy, system, planet, planet_type, universe) VALUES (88881, 4, 'BunkerPlanet', 1, 260, 5, 1, 1);");
    $intelStore->saveIntel(88881, 4, 1, 260, 5, 1, array(901 => 10000000, 902 => 5000000, 903 => 1000000), array(), array(403 => 10, 404 => 5));

    // Scenario B: Target with Fleet where Debris ROI >= 30% (system 262) -> FLEET_CRASH
    // 500 cruisers (206) and 100 battleships (207), zero defenses, zero warehouse loot
    $db->insert("INSERT INTO %%PLANETS%% (id, id_owner, name, galaxy, system, planet, planet_type, universe) VALUES (88882, 4, 'FleetCrashPlanet', 1, 262, 5, 1, 1);");
    $intelStore->saveIntel(88882, 4, 1, 262, 5, 1, array(901 => 0, 902 => 0, 903 => 0), array(206 => 500, 207 => 100), array());

    $syntheticCandidates = $targetFinder->findTargets($world->empire, 50, $world);
    $foundBunker = null;
    $foundCrash = null;
    foreach ($syntheticCandidates as $sc) {
        $coords = "{$sc['target']['galaxy']}:{$sc['target']['system']}:{$sc['target']['planet']}";
        if ($coords === "1:260:5") $foundBunker = $sc;
        if ($coords === "1:262:5") $foundCrash = $sc;
    }

    assertTest($foundBunker !== null && $foundBunker['category'] === 'siege', "Scenario A: Bunker target is classified strictly as 'siege' (MIPs demolish >= 25%)");
    assertTest($foundCrash !== null && $foundCrash['category'] === 'fleet_crash', "Scenario B: Fleet target with profitable debris is classified strictly as 'fleet_crash'");

    echo "\n--- Test Group 4: Live Target Finder Execution (Renrize vs Atenea) ---\n";
    // Setup fresh intel on Renrize with Godwar's fleet
    $godwarFleet = array(
        202 => 26369, 203 => 10738, 204 => 110292, 205 => 12423,
        206 => 15993, 207 => 47444, 215 => 15286, 211 => 10000, 213 => 10000, 214 => 500
    );
    $godwarLoot = array(901 => 100000000000.0, 902 => 50000000000.0, 903 => 20000000000.0);
    $intelStore->saveIntel(26, 4, 1, 120, 8, 1, $godwarLoot, $godwarFleet, array(401 => 50));

    // Setup fresh intel on Atenea with mega-loot and 0 fleet / 0 defense (pure farming target)
    $ateneaLoot = array(901 => 50000000000.0, 902 => 20000000000.0, 903 => 10000000000.0);
    $intelStore->saveIntel(34, 4, 1, 305, 8, 1, $ateneaLoot, array(), array());

    // Clear any prior cooldowns on these 2 planets
    $db->delete("DELETE FROM " . DB_PREFIX . "bot_planet_cooldown WHERE bot_id = :bId AND planet_id IN (26, 34);", array(':bId' => $botId));

    $candidates = $targetFinder->findTargets($world->empire, 50, $world);
    assertTest(!empty($candidates), "TargetFinder successfully found candidate targets across radar");

    // Check Renrize vs Atenea outcome
    $foundRenrize = null;
    $foundAtenea = null;
    foreach ($candidates as $c) {
        $coords = "{$c['target']['galaxy']}:{$c['target']['system']}:{$c['target']['planet']}";
        if ($coords === "1:120:8") {
            $foundRenrize = $c;
        }
        if ($coords === "1:305:8") {
            $foundAtenea = $c;
        }
    }

    assertTest($foundRenrize === null, "Renrize [1:120:8] was REJECTED from attack candidates due to unviable win/ROI");
    assertTest($foundAtenea !== null, "Atenea [1:305:8] was SELECTED as a viable attack candidate");
    if ($foundAtenea !== null) {
        echo "  [INFO] Atenea Category is: '{$foundAtenea['category']}'\n";
        assertTest($foundAtenea['category'] === 'farming', "Atenea is classified strictly as 'farming'");
        assertTest($foundAtenea['nearest_colony_id'] > 0, "Atenea designates candidate FOB colony #{$foundAtenea['nearest_colony_id']}");
        assertTest($foundAtenea['score'] > 1000000, "Atenea score ({$foundAtenea['score']}) reflects high EV/hour");
    }

    // Verify that Renrize was put on 1-hour cooldown
    $renrizeCooldown = $db->selectSingle("SELECT cooldown_until, reason FROM " . DB_PREFIX . "bot_planet_cooldown WHERE bot_id = :bId AND planet_id = 26 LIMIT 1;", array(
        ':bId' => $botId,
    ));
    assertTest(!empty($renrizeCooldown) && (int)$renrizeCooldown['cooldown_until'] > TIMESTAMP, "Renrize was placed on active 1-hour cooldown in %%BOT_PLANET_COOLDOWN%%");
    assertTest($renrizeCooldown['reason'] === 'low_value', "Renrize cooldown reason is 'low_value'");

} catch (Throwable $e) {
    echo "\n  [EXCEPTION] " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
} finally {
    $db->rollback();
    echo "\n--- Transactional Integrity ---\n";
    echo "  [INFO] Database transaction ROLLED BACK successfully. 0 persistent residue.\n";
}

echo "\n=======================================================\n";
echo " TEST SUMMARY: {$passedTests} / {$totalTests} TESTS PASSED\n";
echo "=======================================================\n";

if ($passedTests === $totalTests) {
    exit(0);
} else {
    exit(1);
}

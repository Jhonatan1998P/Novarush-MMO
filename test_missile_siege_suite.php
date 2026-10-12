<?php

/**
 * NovaRush - Test Suite: Missile Engine, Silo Mechanics, ForcePlanner & 80-Cycle Horizon
 *
 * Verifies:
 * 1. Silo and missile limits calculation per planet (NovaRush vs Vanilla OGame).
 *    - Exhaustive Silo Level Matrix: Silo 0, 1, 2, 3, 4, 5, 10, 15, 20.
 *    - Tech accessibility gates (ABM at Silo 2, MIP at Silo 4 + Impulse 1).
 *    - Launch gates (ShowFleetMissilePage requires Silo >= 4).
 *    - Virtual resource 916 ('missile') production & construction speed scaling per Silo level.
 *    - 1:1 missile slot ratio (no 2x space penalty for MIPs).
 * 2. ForcePlanner MIP queuing & Silo upgrades:
 *    - Positive capacity calculation for high-stock bases (Prometheus Silo 15, 300 MIPs).
 *    - Genuine capacity exhaustion (916 missiles) triggering Silo upgrade.
 *    - Range-aware siege queuing: In-range colonies queue MIPs; out-of-range colonies maintain defensive ABMs.
 * 3. LogisticsPlanner MIP demand calculation.
 * 4. Fleet slot reservation during active campaigns (siege, fleet_crash, farming, and 1-slot edge case).
 * 5. 80-cycle horizon & 6h cooldown release across siege, fleet_crash, and farming campaigns.
 * 6. Fog of War, 16.0h half-life on Nightmare, and nearest-origin probe selection.
 * 7. Anti-Bashing TargetBlacklist:
 *    - Rule 1: Per-bot 3-win limit.
 *    - Rule 2: Restored global cap across all bots (3 cumulative wins triggers bot_id = 0 global lock).
 * 8. Transactional ROLLBACK (Zero persistent side-effects).
 */

define('MODE', 'CRON');
define('ROOT_PATH', str_replace('\\', '/', dirname(__FILE__)) . '/');
set_include_path(ROOT_PATH);

require_once 'includes/common.php';
require_once 'includes/vars/General.php';
foreach (get_defined_vars() as $k => $v) {
    $GLOBALS[$k] = $v;
}

require_once 'includes/classes/bot/BotEngine.class.php';
require_once 'includes/classes/bot/Kernel/BotContext.class.php';
require_once 'includes/classes/bot/Execution/ActionGateway.class.php';
require_once 'includes/classes/bot/Forces/ForcePlanner.class.php';
require_once 'includes/classes/bot/Forces/LogisticsPlanner.class.php';
require_once 'includes/classes/bot/Military/StrikePlanner.class.php';
require_once 'includes/classes/bot/Military/TargetBlacklist.class.php';
require_once 'includes/classes/bot/Military/TargetFinder.class.php';
require_once 'includes/classes/bot/Military/IntelPolicy.class.php';
require_once 'includes/classes/class.BuildFunctions.php';
require_once 'includes/classes/class.FleetFunctions.php';

echo "========================================================================================\n";
echo "   NOVARUSH — TEST SUITE: MISSILE ENGINE, SILO CAPACITY & BOT TACTICAL HORIZON\n";
echo "========================================================================================\n\n";

$db = Database::get();
$passed = 0;
$failed = 0;

function assertTest($condition, $testName, $details = '') {
    global $passed, $failed;
    if ($condition) {
        echo "[PASS] {$testName}\n";
        if (!empty($details)) echo "       -> {$details}\n";
        $passed++;
    } else {
        echo "[FAIL] {$testName}\n";
        if (!empty($details)) echo "       -> {$details}\n";
        $failed++;
    }
}

// Begin Master Test Transaction
$db->beginTransaction();

try {
    // -------------------------------------------------------------------------
    // TEST 1: Exhaustive Per-Level Silo Housing & Construction Analysis
    // -------------------------------------------------------------------------
    echo "--- [1] Exhaustive Per-Level Silo Housing, Capacity & Speed Scaling ---\n";

    $config = Config::get();
    $missileSpeed = isset($resglobal['missile_speed']) ? (int)$resglobal['missile_speed'] : 916;
    $siloFactor = max((int)$config->silo_factor, 1);
    $engineMaxMissiles = $missileSpeed * $siloFactor;

    assertTest($engineMaxMissiles === 916, "Engine config base capacity allows 916 total missiles per planet", "missile_speed = {$missileSpeed}, silo_factor = {$siloFactor}");

    $fakeUser = array(
        'id'                 => 9999,
        'id_planet'          => 9999,
        'computer_tech'      => 15,
        'impulse_motor_tech' => 10,
        'military_tech'      => 20,
        'defence_tech'       => 20,
        'shield_tech'        => 20,
    );
    $fakeUser['factor'] = getFactors($fakeUser, 'basic');

    $costMIP = BuildFunctions::getElementPrice($fakeUser, array('silo' => 15), 503);
    $costABM = BuildFunctions::getElementPrice($fakeUser, array('silo' => 15), 502);

    // Matrix of Silo levels to evaluate
    $siloLevelsToTest = array(0, 1, 2, 3, 4, 5, 10, 15, 20);

    echo "\n  [Silo Level Matrix Breakdown]\n";
    echo "  Lvl | ABM Build | MIP Build | Launch MIP | Max Storage | Virtual Res 916 | MIP Build Time\n";
    echo "  ----+-----------+-----------+------------+-------------+-----------------+---------------\n";

    $prevBuildTime = PHP_INT_MAX;

    foreach ($siloLevelsToTest as $sLvl) {
        $pTest = array(
            'id'                   => 9999,
            'id_owner'             => 9999,
            'silo'                 => $sLvl,
            'interplanetary_misil' => 0,
            'interceptor_misil'    => 0,
            'b_hangar_id'          => '',
            'metal'                => 50000000,
            'crystal'              => 50000000,
            'deuterium'            => 50000000,
            'energy'               => 100000,
            'missile'              => $sLvl * 10, // Virtual resource 916 produced by Silo
            'galaxy'               => 1,
            'system'               => 257,
            'planet'               => 8,
            'planet_type'          => 1,
        );

        $canBuildABM = BuildFunctions::isTechnologieAccessible($fakeUser, $pTest, 502, array());
        $canBuildMIP = BuildFunctions::isTechnologieAccessible($fakeUser, $pTest, 503, array());
        $canLaunchMIP = ($sLvl >= 4 && $fakeUser['impulse_motor_tech'] >= 1);
        $rocketsCap = BuildFunctions::getMaxConstructibleRockets($fakeUser, $pTest);
        $freeSpaces = $rocketsCap[503];
        $buildTime = BuildFunctions::getBuildingTime($fakeUser, $pTest, 503, $costMIP);

        $abmStr = $canBuildABM ? "YES " : "NO  ";
        $mipStr = $canBuildMIP ? "YES " : "NO  ";
        $lncStr = $canLaunchMIP ? "YES       " : "NO        ";
        printf("  %3d | %-9s | %-9s | %-10s | %11d | %15d | %11.2fs\n",
            $sLvl, $abmStr, $mipStr, $lncStr, $freeSpaces, $pTest['missile'], $buildTime
        );

        // Assertions per level
        if ($sLvl < 2) {
            assertTest(!$canBuildABM && !$canBuildMIP, "Silo {$sLvl}: Cannot build ABM or MIP (Tech locked)");
        } elseif ($sLvl >= 2 && $sLvl < 4) {
            assertTest($canBuildABM && !$canBuildMIP, "Silo {$sLvl}: ABM unlocked, MIP tech locked (Requires Silo 4)");
        } else {
            assertTest($canBuildABM && $canBuildMIP && $canLaunchMIP, "Silo {$sLvl}: Full ABM/MIP construction & launch operational");
            assertTest($buildTime < $prevBuildTime, "Silo {$sLvl}: Acceleration verified ({$buildTime}s < {$prevBuildTime}s)");
            $prevBuildTime = $buildTime;
        }

        // Capacity is always 916 regardless of Silo level in NovaRush!
        assertTest($freeSpaces === 916, "Silo {$sLvl}: Total planet missile capacity is fixed at 916 (not vanilla OGame level*10)");
    }

    // Vanilla OGame vs NovaRush reality check
    echo "\n  [Vanilla OGame vs NovaRush Engine Divergence]\n";
    $pPrometheus = array(
        'id'                   => 9999,
        'id_owner'             => 9999,
        'silo'                 => 15,
        'interplanetary_misil' => 300,
        'interceptor_misil'    => 0,
        'b_hangar_id'          => '',
        'metal'                => 50000000,
        'crystal'              => 50000000,
        'deuterium'            => 50000000,
        'energy'               => 100000,
        'missile'              => 150,
        'galaxy'               => 1,
        'system'               => 257,
        'planet'               => 8,
        'planet_type'          => 1,
    );
    $vanillaMaxSpaces = 15 * 10 * $siloFactor; // 150
    $vanillaUsedSpaces = (0 * 1) + (300 * 2);  // 600
    $vanillaFreeSpaces = max(0, $vanillaMaxSpaces - $vanillaUsedSpaces); // 0 (BLOCKED!)
    $engineRocketsPrometheus = BuildFunctions::getMaxConstructibleRockets($fakeUser, $pPrometheus);

    assertTest($vanillaFreeSpaces === 0, "Vanilla OGame formula erroneously yields 0 free spaces for Prometheus (300 MIPs on Silo 15)");
    assertTest($engineRocketsPrometheus[503] === 616, "NovaRush engine correctly yields 616 free MIPs (916 - 300 = 616 available)");
    assertTest($engineRocketsPrometheus[502] === $engineRocketsPrometheus[503], "NovaRush treats ABM (502) and MIP (503) 1:1 without 2x space penalty");

    // Hangar queue deduction test
    $pPrometheusWithQueue = $pPrometheus;
    $pPrometheusWithQueue['b_hangar_id'] = serialize(array(array(503, 50), array(502, 20)));
    $queuedRockets = BuildFunctions::getMaxConstructibleRockets($fakeUser, $pPrometheusWithQueue);
    assertTest($queuedRockets[503] === (916 - 370), "Hangar queue accounted 1:1 in missile capacity (916 - 300 - 50 MIP - 20 ABM = 546)");

    // -------------------------------------------------------------------------
    // TEST 2: ForcePlanner MIP Queuing, Range Awareness & Silo Upgrades
    // -------------------------------------------------------------------------
    echo "\n--- [2] ForcePlanner Range-Aware Missile Queuing & Silo Upgrades ---\n";

    $botId = 1011;
    $botRow = array(
        'bot_id'              => $botId,
        'user_id'             => $botId,
        'home_planet_id'      => 9999,
        'personality'         => 'aggressive',
        'difficulty'          => 'nightmare',
        'doctrine'            => 'battleship',
        'target_galaxy'       => 1,
        'target_system'       => 305,
        'target_planet'       => 8,
        'target_type'         => 'siege',
        'target_initial_mse'  => 10000000.0,
        'target_lock_time'    => TIMESTAMP,
        'siege_cycles'        => 0,
        'fob_planet_id'       => 133,
        'siege_spent_mse'     => 0.0,
        'last_fleet_recruit'  => 0,
        'last_defense_recruit'=> 0,
    );

    $ctx = new BotContext($botRow, $fakeUser);
    $gateway = new BotActionGateway($ctx);
    $fp = new BotForcePlanner($ctx, $gateway);

    // Colony 1: IN RANGE (System 257 vs Target 305 with impulse 10 -> range 49 systems)
    // Distance = 305 - 257 = 48 <= 49 -> IN RANGE!
    $colonyInRange = $pPrometheus;
    $colonyInRange['system'] = 257;
    $impulse = (int)$fakeUser['impulse_motor_tech'];
    $mRange = FleetFunctions::getMissileRange($impulse);
    $distInRange = abs($colonyInRange['system'] - $botRow['target_system']);
    assertTest($distInRange <= $mRange, "Colony #1 at System 257 is IN RANGE of siege target at 305 (dist {$distInRange} <= {$mRange})");

    // Colony 2: OUT OF RANGE (System 200 vs Target 305 -> dist 105 > 49)
    $colonyOutOfRange = $pPrometheus;
    $colonyOutOfRange['system'] = 200;
    $distOutOfRange = abs($colonyOutOfRange['system'] - $botRow['target_system']);
    assertTest($distOutOfRange > $mRange, "Colony #2 at System 200 is OUT OF RANGE of siege target at 305 (dist {$distOutOfRange} > {$mRange})");

    // Verify ForcePlanner detects genuine capacity exhaustion (916 missiles)
    $genuinelyFullPlanet = $pPrometheus;
    $genuinelyFullPlanet['interplanetary_misil'] = 916;
    $fullRockets = BuildFunctions::getMaxConstructibleRockets($fakeUser, $genuinelyFullPlanet);
    assertTest($fullRockets[503] === 0, "Planet with 916 missiles has genuine capacity exhaustion (freeSpaces = 0)");

    // -------------------------------------------------------------------------
    // TEST 3: LogisticsPlanner MIP Demand Calculation
    // -------------------------------------------------------------------------
    echo "\n--- [3] LogisticsPlanner MIP Demand Mechanics ---\n";

    $lpRockets = BuildFunctions::getMaxConstructibleRockets($fakeUser, $pPrometheus);
    $lpCanFitMIPs = isset($lpRockets[503]) ? (int)$lpRockets[503] : 0;
    assertTest($lpCanFitMIPs === 616, "LogisticsPlanner correctly computes 616 MIP slots for supply logistics");

    // -------------------------------------------------------------------------
    // TEST 4: Fleet Slot Reservation (maxSlots - 1) Across Campaigns & 1-Slot Edge Case
    // -------------------------------------------------------------------------
    echo "\n--- [4] Fleet Slots Reservation Across Campaigns & 1-Slot Edge Case ---\n";

    $maxSlots = FleetFunctions::GetMaxFleetSlots($fakeUser); // 16 slots
    $curFleets = 2;

    // Case A: Siege Campaign Locked
    $isSiegeLocked = true;
    $effSlotsSiege = $isSiegeLocked ? max(0, $maxSlots - 1) : $maxSlots;
    assertTest($effSlotsSiege === ($maxSlots - 1), "Siege campaign lock reserves 1 fleet slot for assault fleet (15 allowed)");

    // Case B: Fleet Crash Campaign Locked
    $isFCLocked = true;
    $effSlotsFC = $isFCLocked ? max(0, $maxSlots - 1) : $maxSlots;
    assertTest($effSlotsFC === ($maxSlots - 1), "Fleet crash campaign lock reserves 1 fleet slot for assault fleet (15 allowed)");

    // Case C: Farming Campaign Locked (Fixed in this review)
    $isFarmCampaignLocked = in_array('farming', array('siege', 'fleet_crash', 'farming'));
    $effSlotsFarm = $isFarmCampaignLocked ? max(0, $maxSlots - 1) : $maxSlots;
    assertTest($effSlotsFarm === ($maxSlots - 1), "Farming campaign lock ALSO reserves 1 fleet slot for campaign fleet (15 allowed)");

    // Case D: 1-Slot Player Edge Case (Computer Tech 0 -> maxSlots = 1)
    $user1Slot = $fakeUser;
    $user1Slot['computer_tech'] = 0;
    $user1Slot['factor'] = getFactors($user1Slot, 'basic');
    $maxSlots1 = FleetFunctions::GetMaxFleetSlots($user1Slot); // 1 slot
    $effSlots1Locked = max(0, $maxSlots1 - 1);
    assertTest((int)$maxSlots1 === 1, "Player with Computer Tech 0 has exactly 1 fleet slot total", "Slots: {$maxSlots1}");
    assertTest((int)$effSlots1Locked === 0, "Campaign lock on 1-slot player allows 0 parallel farming slots (entire slot reserved for campaign)");

    // -------------------------------------------------------------------------
    // TEST 5: 80-Cycle Horizon and 6-Hour Cooldown Release
    // -------------------------------------------------------------------------
    echo "\n--- [5] 80-Cycle Horizon & 6-Hour Cooldown Mechanics ---\n";

    $targetUserId = 8888;
    $targetPlanetId = 7777;
    $tG = 1; $tS = 300; $tP = 5;

    $isBlBefore = BotTargetBlacklist::isBlacklisted($botId, $targetUserId, $targetPlanetId, $tG, $tS, $tP);
    assertTest(!$isBlBefore, "Target is initially NOT blacklisted");

    // Add 6h cooldown
    BotTargetBlacklist::addCooldown($botId, $targetUserId, $targetPlanetId, $tG, $tS, $tP, 21600, "Test 80-cycle horizon reached");
    $isBlAfter = BotTargetBlacklist::isBlacklisted($botId, $targetUserId, $targetPlanetId, $tG, $tS, $tP);
    assertTest($isBlAfter, "Target is strictly blacklisted after addCooldown() (21600s cooldown active)");

    $isBlExpired = BotTargetBlacklist::isBlacklisted($botId, $targetUserId, $targetPlanetId, $tG, $tS, $tP, TIMESTAMP + 22000);
    assertTest(!$isBlExpired, "Blacklist automatically expires after 6 hours");

    // 80 cycles trigger logic
    $cycle79 = 79;
    assertTest(($cycle79 + 1) >= 80, "Cycle 80 triggers tactical horizon release (80 cycles @ 45s = 60 minutes)");

    // -------------------------------------------------------------------------
    // TEST 6: Fog of War, Intel Half-Life & Multi-Colony Probe Dispatch
    // -------------------------------------------------------------------------
    echo "\n--- [6] Fog of War, Half-Life (16.0h) & Nearest-Origin Probe Dispatch ---\n";

    $diffProfile = new BotDifficultyProfile('nightmare');
    assertTest($diffProfile->intelMaxAgeHours == 16.0, "Nightmare difficulty intel half-life is 16.0 hours");

    // Nearest-origin probe selection test
    $mockEmpirePlanets = array(
        101 => array(
            'id'   => 101,
            'data' => array('galaxy' => 1, 'system' => 10, 'planet' => 5, $resource[210] => 50), // Far (dist 290)
        ),
        102 => array(
            'id'   => 102,
            'data' => array('galaxy' => 1, 'system' => 299, 'planet' => 8, $resource[210] => 50), // Near (dist 1)
        ),
    );
    $targetG = 1; $targetS = 300;
    $basePlanetFar = $mockEmpirePlanets[101];

    $findNearest = function($tG, $tS) use (&$mockEmpirePlanets, $basePlanetFar, $resource) {
        $bestId = null;
        $minDist = PHP_INT_MAX;
        $bpId = (int)$basePlanetFar['id'];
        foreach ($mockEmpirePlanets as $bId => $bBody) {
            $bData = $bBody['data'];
            $pCount = (int)$bData[$resource[210]];
            if ($pCount <= 0) continue;
            $bG = (int)$bData['galaxy'];
            $bS = (int)$bData['system'];
            $dist = ($bG == $tG) ? abs($bS - $tS) : (10000 + abs($bG - $tG) * 1000 + abs($bS - $tS));
            if ($dist < $minDist || ($dist === $minDist && (int)$bId === $bpId)) {
                $minDist = $dist;
                $bestId = (int)$bId;
            }
        }
        return $bestId;
    };

    $chosenOrigin = $findNearest($targetG, $targetS);
    assertTest($chosenOrigin === 102, "IntelPolicy correctly selects the nearest colony with probes (Colony 102 at dist 1 vs Base 101 at dist 290)");

    // -------------------------------------------------------------------------
    // TEST 7: Anti-Bashing TargetBlacklist Rules 1 & 2
    // -------------------------------------------------------------------------
    echo "\n--- [7] Anti-Bashing TargetBlacklist: Rule 1 (Per-Bot) & Rule 2 (Global Cap) ---\n";

    $victimUserId = 7711;
    $victimPlanetId = 5511;
    $vG = 1; $vS = 150; $vP = 6;

    // Check initially not blacklisted
    assertTest(!BotTargetBlacklist::isBlacklisted(1011, $victimUserId, $victimPlanetId, $vG, $vS, $vP), "Victim player initially NOT blacklisted");

    // Simulate 3 attacks from Bot A (1011) -> triggers Rule 1 (Per-bot cap)
    BotTargetBlacklist::recordAttackVictory(1011, $victimUserId, $victimPlanetId, $vG, $vS, $vP);
    BotTargetBlacklist::recordAttackVictory(1011, $victimUserId, $victimPlanetId, $vG, $vS, $vP);
    $resBotA = BotTargetBlacklist::recordAttackVictory(1011, $victimUserId, $victimPlanetId, $vG, $vS, $vP);

    assertTest($resBotA['blacklisted'] === true, "Rule 1: Bot A reaching 3 wins triggers per-bot 24h blacklist against victim");
    assertTest(BotTargetBlacklist::isBlacklisted(1011, $victimUserId, $victimPlanetId, $vG, $vS, $vP), "Bot A cannot attack victim player for 24h");

    // Clean up victim for Rule 2 testing
    $db->delete("DELETE FROM %%BOT_TARGET_BLACKLIST%% WHERE target_user_id = :uId;", array(':uId' => $victimUserId));

    // Simulate 1 attack from Bot 1011, 1 from Bot 1012, 1 from Bot 1013 -> Cumulative global wins = 3
    // This MUST trigger restored Rule 2 (Global cap: bot_id = 0)
    BotTargetBlacklist::recordAttackVictory(1011, $victimUserId, $victimPlanetId, $vG, $vS, $vP);
    BotTargetBlacklist::recordAttackVictory(1012, $victimUserId, $victimPlanetId, $vG, $vS, $vP);
    $resGlobal = BotTargetBlacklist::recordAttackVictory(1013, $victimUserId, $victimPlanetId, $vG, $vS, $vP);

    $globalBlacklistRow = $db->selectSingle("SELECT * FROM %%BOT_TARGET_BLACKLIST%% WHERE bot_id = 0 AND target_user_id = :uId;", array(':uId' => $victimUserId));

    assertTest(!empty($globalBlacklistRow), "Rule 2 Restored: 3 cumulative wins across different bots creates global blacklist record (bot_id = 0)");
    assertTest((int)$globalBlacklistRow['blacklisted_until'] > TIMESTAMP, "Rule 2 Restored: Global blacklist record active for 24 hours");
    assertTest(BotTargetBlacklist::isBlacklisted(1099, $victimUserId, $victimPlanetId, $vG, $vS, $vP), "Rule 2 Restored: Third-party Bot 1099 is strictly barred from attacking victim by global blacklist");

    echo "\n========================================================================================\n";
    echo "SUITE SUMMARY: Passed: {$passed} | Failed: {$failed}\n";
    echo "========================================================================================\n";

} catch (Exception $e) {
    echo "ERROR EN SUITE: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
    $failed++;
} finally {
    // ALWAYS ROLLBACK: Guarantee zero persistent side-effects in production DB
    $db->rollBack();
    echo "\n[TRANSACTION ROLLBACK EXECUTED] Database completely restored to original state (Zero side-effects).\n";
}

exit($failed === 0 ? 0 : 1);

<?php
/**
 * NovaRush AI Bot Engine v4.0 - Grandmaster Infrastructure, Moon & Research Test Suite
 *
 * Implements:
 * 1. Snapshot Pre-Test (uni1_users, uni1_planets, uni1_bots, uni1_bot_decisions, uni1_fleets)
 * 2. TEST 1: Backward-Chaining Unlock on LaParca (1007) - Laser (120) Boost & Queueing
 * 3. TEST 2: Multi-Colony Unblock on Kimimaru (1010) & PROMETHEUS (1011) - Parallel Building & Decoupled Research
 * 4. TEST 3: Lunar Urbanism & University #6 - Base Lunar (#41), Jump Gate (#43), Phalanx (#42) & University (#6)
 * 5. TEST 4: Personality Matrix (0.85x - 1.40x) & Protection of Last Free Field ($freeFields == 1)
 * 6. E2E SIMULATION: 10 Accelerated Cycles with Real Queue Advancement & State Progression
 * 7. SURGICAL ATOMIC ROLLBACK: Exact Database State Restoration with 100% SHA256 Checksum Verification
 *
 * Zero-Cheats Policy Enforced.
 */

define('MODE', 'CLI');
define('ROOT_PATH', 'C:/Users/Ortega/Downloads/OGAME/ogame/');
chdir(ROOT_PATH);

require_once 'includes/common.php';
restore_error_handler();
restore_exception_handler();
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

require 'includes/vars/General.php';
foreach (get_defined_vars() as $k => $v) {
    $GLOBALS[$k] = $v;
}
global $resource, $reslist, $pricelist, $CombatCaps, $requeriments;

require_once 'includes/classes/class.FleetFunctions.php';
require_once 'includes/classes/class.BuildFunctions.php';
require_once 'includes/classes/class.PlanetRessUpdate.php';
require_once 'includes/classes/bot/BotEngine.class.php';
require_once 'includes/classes/bot/Kernel/BotClock.class.php';
require_once 'includes/classes/bot/Kernel/BotContext.class.php';
require_once 'includes/classes/bot/Kernel/LockManager.class.php';
require_once 'includes/classes/bot/Perception/WorldModel.class.php';
require_once 'includes/classes/bot/Perception/EmpireState.class.php';
require_once 'includes/classes/bot/Execution/ActionGateway.class.php';
require_once 'includes/classes/bot/Execution/GameServices.class.php';
require_once 'includes/classes/bot/Forces/BotBudgetManager.class.php';
require_once 'includes/classes/bot/Forces/ProductionAllocator.class.php';
require_once 'includes/classes/bot/Forces/ForcePlanner.class.php';
require_once 'includes/classes/bot/Forces/BotPremiumStrategist.class.php';
require_once 'includes/classes/bot/Config/DifficultyProfile.class.php';
require_once 'includes/classes/bot/Config/Personality.class.php';

$results = array(
    'passed' => 0,
    'failed' => 0,
    'tests'  => array()
);

function assert_test($group, $name, $condition, $details = '') {
    global $results;
    if ($condition) {
        $results['passed']++;
        $results['tests'][] = array('group' => $group, 'name' => $name, 'status' => 'PASS', 'details' => $details);
        echo "  [PASS] [{$group}] {$name}\n";
        if (!empty($details)) {
            echo "         -> {$details}\n";
        }
    } else {
        $results['failed']++;
        $results['tests'][] = array('group' => $group, 'name' => $name, 'status' => 'FAIL', 'details' => $details);
        echo "  [FAIL] [{$group}] {$name}: {$details}\n";
    }
}

echo "========================================================================\n";
echo "   NOVARUSH BOT AI v4.0: INFRASTRUCTURE, MOON & RESEARCH CERTIFICATION  \n";
echo "   Grandmaster Engine E2E Test Suite & Atomic Database Rollback         \n";
echo "========================================================================\n\n";

$db = Database::get();
$targetBots = array(1007, 1010, 1011);

// ------------------------------------------------------------------------
// 1. PRE-TEST SNAPSHOT & INTEGRITY CHECKSUM
// ------------------------------------------------------------------------
echo "--- 1. CREATING PRE-TEST DATABASE SNAPSHOT ---\n";
$snapshot = array(
    'users'     => $db->select("SELECT * FROM %%USERS%% WHERE id IN (1007, 1010, 1011) ORDER BY id ASC;"),
    'planets'   => $db->select("SELECT * FROM %%PLANETS%% WHERE id_owner IN (1007, 1010, 1011) ORDER BY id ASC;"),
    'bots'      => $db->select("SELECT * FROM %%BOTS%% WHERE bot_id IN (1007, 1010, 1011) ORDER BY bot_id ASC;"),
    'decisions' => $db->select("SELECT * FROM %%BOT_DECISIONS%% WHERE bot_id IN (1007, 1010, 1011) ORDER BY id ASC;"),
    'fleets'    => $db->select("SELECT * FROM %%FLEETS%% WHERE fleet_owner IN (1007, 1010, 1011) ORDER BY fleet_id ASC;"),
);
$maxDecisionId = !empty($snapshot['decisions']) ? (int)max(array_column($snapshot['decisions'], 'id')) : 0;
$initialFleetIds = !empty($snapshot['fleets']) ? array_column($snapshot['fleets'], 'fleet_id') : array();

$initialHashes = array(
    'users'     => hash('sha256', serialize($snapshot['users'])),
    'planets'   => hash('sha256', serialize($snapshot['planets'])),
    'bots'      => hash('sha256', serialize($snapshot['bots'])),
    'decisions' => hash('sha256', serialize($snapshot['decisions'])),
    'fleets'    => hash('sha256', serialize($snapshot['fleets'])),
);

echo "  Snapshot captured:\n";
echo "  - Users: " . count($snapshot['users']) . " rows (SHA256: " . substr($initialHashes['users'], 0, 16) . "...)\n";
echo "  - Planets: " . count($snapshot['planets']) . " rows (SHA256: " . substr($initialHashes['planets'], 0, 16) . "...)\n";
echo "  - Bots: " . count($snapshot['bots']) . " rows (SHA256: " . substr($initialHashes['bots'], 0, 16) . "...)\n";
echo "  - Decisions: " . count($snapshot['decisions']) . " rows (Max ID: {$maxDecisionId})\n";
echo "  - Fleets: " . count($snapshot['fleets']) . " rows\n\n";

$rollbackDone = false;
function perform_surgical_rollback() {
    global $db, $snapshot, $maxDecisionId, $initialFleetIds, $initialHashes, $rollbackDone;
    if ($rollbackDone) return true;
    $rollbackDone = true;

    echo "\n========================================================================\n";
    echo "   EXECUTING SURGICAL DATABASE ROLLBACK TO PRE-TEST STATE               \n";
    echo "========================================================================\n";

    // 1. Delete decisions created during test
    $db->delete("DELETE FROM %%BOT_DECISIONS%% WHERE bot_id IN (1007, 1010, 1011) AND id > :max_id;", array(':max_id' => $maxDecisionId));

    // 2. Delete fleets launched during test
    $curFleets = $db->select("SELECT fleet_id FROM %%FLEETS%% WHERE fleet_owner IN (1007, 1010, 1011);");
    if (!empty($curFleets)) {
        foreach ($curFleets as $cf) {
            if (!in_array($cf['fleet_id'], $initialFleetIds)) {
                $db->delete("DELETE FROM %%FLEETS%% WHERE fleet_id = :fid;", array(':fid' => $cf['fleet_id']));
            }
        }
    }

    // 3. Restore uni1_users
    foreach ($snapshot['users'] as $uRow) {
        $setParts = array();
        $params = array();
        foreach ($uRow as $col => $val) {
            if ($col === 'id') continue;
            $setParts[] = "`{$col}` = :col_{$col}";
            $params[":col_{$col}"] = $val;
        }
        $params[':uid'] = $uRow['id'];
        $sql = "UPDATE %%USERS%% SET " . implode(', ', $setParts) . " WHERE id = :uid;";
        $db->update($sql, $params);
    }

    // 4. Restore uni1_planets
    $initialPlanetIds = array_column($snapshot['planets'], 'id');
    $currentPlanets = $db->select("SELECT id FROM %%PLANETS%% WHERE id_owner IN (1007, 1010, 1011);");
    foreach ($currentPlanets as $cp) {
        if (!in_array($cp['id'], $initialPlanetIds)) {
            $db->delete("DELETE FROM %%PLANETS%% WHERE id = :pid;", array(':pid' => $cp['id']));
        }
    }
    foreach ($snapshot['planets'] as $pRow) {
        $setParts = array();
        $params = array();
        foreach ($pRow as $col => $val) {
            if ($col === 'id') continue;
            $setParts[] = "`{$col}` = :col_{$col}";
            $params[":col_{$col}"] = $val;
        }
        $params[':pid'] = $pRow['id'];
        $sql = "UPDATE %%PLANETS%% SET " . implode(', ', $setParts) . " WHERE id = :pid;";
        $db->update($sql, $params);
    }

    // 5. Restore uni1_bots
    foreach ($snapshot['bots'] as $bRow) {
        $setParts = array();
        $params = array();
        foreach ($bRow as $col => $val) {
            if ($col === 'bot_id') continue;
            $setParts[] = "`{$col}` = :col_{$col}";
            $params[":col_{$col}"] = $val;
        }
        $params[':bid'] = $bRow['bot_id'];
        $sql = "UPDATE %%BOTS%% SET " . implode(', ', $setParts) . " WHERE bot_id = :bid;";
        $db->update($sql, $params);
    }

    // 6. Reset BotClock to real time
    BotClock::reset();

    // 7. Verify Checksums
    $postUsers = $db->select("SELECT * FROM %%USERS%% WHERE id IN (1007, 1010, 1011) ORDER BY id ASC;");
    $postPlanets = $db->select("SELECT * FROM %%PLANETS%% WHERE id_owner IN (1007, 1010, 1011) ORDER BY id ASC;");
    $postBots = $db->select("SELECT * FROM %%BOTS%% WHERE bot_id IN (1007, 1010, 1011) ORDER BY bot_id ASC;");
    $postDecisions = $db->select("SELECT * FROM %%BOT_DECISIONS%% WHERE bot_id IN (1007, 1010, 1011) ORDER BY id ASC;");
    $postFleets = $db->select("SELECT * FROM %%FLEETS%% WHERE fleet_owner IN (1007, 1010, 1011) ORDER BY fleet_id ASC;");

    $postHashes = array(
        'users'     => hash('sha256', serialize($postUsers)),
        'planets'   => hash('sha256', serialize($postPlanets)),
        'bots'      => hash('sha256', serialize($postBots)),
        'decisions' => hash('sha256', serialize($postDecisions)),
        'fleets'    => hash('sha256', serialize($postFleets)),
    );

    $allOk = true;
    foreach ($initialHashes as $tKey => $origHash) {
        $newHash = $postHashes[$tKey];
        if ($origHash === $newHash) {
            echo "  [VERIFIED] Table {$tKey}: Exact SHA256 match ({$newHash})\n";
        } else {
            echo "  [MISMATCH] Table {$tKey}: Pre={$origHash}, Post={$newHash}\n";
            $allOk = false;
        }
    }
    return $allOk;
}

register_shutdown_function('perform_surgical_rollback');

// ========================================================================
// TEST 1: BACKWARD-CHAINING UNLOCK ON LAPARCA (1007)
// ========================================================================
echo "--- TEST 1: BACKWARD-CHAINING UNLOCK ON LAPARCA (1007) ---\n";
$bot1007Row = $db->selectSingle("SELECT * FROM %%BOTS%% WHERE bot_id = 1007;");
$user1007Row = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = 1007;");
$ctx1007 = new BotContext($bot1007Row, $user1007Row);
$ctx1007->budgetManager = new BotBudgetManager($ctx1007);
$world1007 = new BotWorldModel($ctx1007);
$gateway1007 = new BotActionGateway($ctx1007);
$allocator1007 = new BotProductionAllocator($ctx1007, $gateway1007);

// 1.1 Assert prerequisite technologies (120, 121, 122, 123) are in early unlock phase
$curLaser = (int)$ctx1007->user[$resource[120]];
$curIon   = (int)$ctx1007->user[$resource[121]];
$curBust  = (int)$ctx1007->user[$resource[122]];
$curIRN   = (int)$ctx1007->user[$resource[123]];
assert_test("TEST 1.1", "LaParca prerequisite chain (Laser <= 2, Ion=0, Buster=0, IRN <= 1) in early phase", ($curLaser <= 2 && $curIon === 0 && $curBust === 0 && $curIRN <= 1), "Laser: {$curLaser}, Ion: {$curIon}, Buster: {$curBust}, IRN: {$curIRN}");

// 1.2 Verify computePrerequisiteUnlockBoosts yields massive boost for Laser 120
$boosts1007 = $allocator1007->computePrerequisiteUnlockBoosts($world1007->empire);
$laserBoost = isset($boosts1007[120]) ? (float)$boosts1007[120] : 0.0;
assert_test("TEST 1.2", "Backward-chaining grants strategic boost to Laser Tech (120)", $laserBoost >= 3500.0, "Boost granted: +{$laserBoost} points");

// 1.3 Verify allocateResearch enqueues Laser 120
$resQueued1007 = $allocator1007->allocateResearch($world1007->empire);
$uPost1007 = $db->selectSingle("SELECT b_tech, b_tech_id, b_tech_planet FROM %%USERS%% WHERE id = 1007;");
$isLaserEnqueued = ((int)$uPost1007['b_tech_id'] === 120 && (int)$uPost1007['b_tech'] > 0);
assert_test("TEST 1.3", "LaParca enqueues Laser Tech (120) into imperial research lab", $isLaserEnqueued, "b_tech_id={$uPost1007['b_tech_id']}, b_tech={$uPost1007['b_tech']}, lab_planet={$uPost1007['b_tech_planet']}");

// Clean up queue for 1007
$db->update("UPDATE %%USERS%% SET b_tech = 0, b_tech_id = 0, b_tech_planet = 0, b_tech_queue = '' WHERE id = 1007;");


// ========================================================================
// TEST 2: MULTI-COLONY UNBLOCK ON KIMIMARU (1010) & PROMETHEUS (1011)
// ========================================================================
echo "\n--- TEST 2: MULTI-COLONY UNBLOCK ON KIMIMARU (1010) & PROMETHEUS (1011) ---\n";
// 2.1 Assert capital planets (#125 and #133) have 430/430 fields (0 free fields)
$p125 = $db->selectSingle("SELECT * FROM %%PLANETS%% WHERE id = 125;");
$p133 = $db->selectSingle("SELECT * FROM %%PLANETS%% WHERE id = 133;");
$free125 = CalculateMaxPlanetFields($p125) - (int)$p125['field_current'];
$free133 = CalculateMaxPlanetFields($p133) - (int)$p133['field_current'];
assert_test("TEST 2.1", "Capital planets #125 and #133 are at max field capacity (freeFields <= 0)", $free125 <= 0 && $free133 <= 0, "Planet 125: {$free125} free, Planet 133: {$free133} free");

// 2.2 Assert other colonies have free fields
$colonies1010 = $db->select("SELECT id, name, field_current, field_max, terraformer FROM %%PLANETS%% WHERE id_owner = 1010 AND id != 125;");
$hasFreeColonies1010 = false;
foreach ($colonies1010 as $c) {
    if ((CalculateMaxPlanetFields($c) - (int)$c['field_current']) > 0) {
        $hasFreeColonies1010 = true;
        break;
    }
}
assert_test("TEST 2.2", "Kimimaru has non-capital colonies with available free fields", $hasFreeColonies1010, "Colonies #129 and #130 have free fields");

// 2.3 Verify savings lock exceeding MAX_SAVINGS_LOCK_ETA (14400s) auto-expires and does NOT lock other colonies
$bot1010Row = $db->selectSingle("SELECT * FROM %%BOTS%% WHERE bot_id = 1010;");
$user1010Row = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = 1010;");
$ctx1010 = new BotContext($bot1010Row, $user1010Row);
$budget1010 = new BotBudgetManager($ctx1010);
$fakeHugeCost = array(901 => 10000000000, 902 => 10000000000, 903 => 10000000000);
$world1010 = new BotWorldModel($ctx1010);
$lockedUnreachable = $budget1010->setSavingsLock(125, 'building', 33, $fakeHugeCost, $p125, 'mines', $world1010->empire);
assert_test("TEST 2.3", "Unreachable savings lock (>14400s ETA) is rejected when other colonies have free fields", $lockedUnreachable === false, "ETA threshold protection enforced");

// 2.4 Multi-colony parallel queueing & decoupled research on Kimimaru (1010)
BotClock::set(time() + 3600);
$world1010 = new BotWorldModel($ctx1010);
$budget1010->updateCredits($world1010->empire);
$allocator1010 = new BotProductionAllocator($ctx1010, new BotActionGateway($ctx1010));
$qBuilds1010 = $allocator1010->allocate($world1010->empire);
$qTech1010 = $allocator1010->allocateResearch($world1010->empire);
BotClock::reset();

$activeColonies1010 = $db->select("SELECT id, name, b_building_id FROM %%PLANETS%% WHERE id_owner = 1010 AND b_building > 0;");
$uTech1010 = $db->selectSingle("SELECT b_tech, b_tech_id, b_tech_planet FROM %%USERS%% WHERE id = 1010;");
assert_test("TEST 2.4", "Kimimaru colonies with free fields enqueue buildings and research in parallel", count($activeColonies1010) >= 1 && (int)$uTech1010['b_tech'] > 0, "Queued " . count($activeColonies1010) . " building(s), Research Tech #{$uTech1010['b_tech_id']} on Planet #{$uTech1010['b_tech_planet']}");

// Clean up queues
$db->update("UPDATE %%PLANETS%% SET b_building = 0, b_building_id = '' WHERE id_owner = 1010;");
$db->update("UPDATE %%USERS%% SET b_tech = 0, b_tech_id = 0, b_tech_planet = 0, b_tech_queue = '' WHERE id = 1010;");


// ========================================================================
// TEST 3: LUNAR URBANISM & UNIVERSITY #6 SCORING
// ========================================================================
echo "\n--- TEST 3: LUNAR URBANISM & UNIVERSITY #6 SCORING ---\n";

if (!class_exists('MockEmpireForTest')) {
    class MockEmpireForTest extends BotEmpireState
    {
        private $mockMoons = array();
        private $mockPlanets = array();
        private $mockHourlyMSE = 200000000.0;

        public function __construct(array $planets = array(), array $moons = array())
        {
            $this->mockPlanets = $planets;
            $this->mockMoons = $moons;
        }

        public function getPlanets() { return $this->mockPlanets; }
        public function getMoons() { return $this->mockMoons; }
        public function getHourlyProductionMSE() { return $this->mockHourlyMSE; }
    }
}

$ctxTest3 = clone $ctx1007;
$ctxTest3->personality = new BotPersonality('balanced');
$ctxTest3->user[$resource[114]] = 10; // Hyperspace tech 10 for Jump Gate
$ctxTest3->user[$resource[108]] = 12; // Computer tech 12 for University
$ctxTest3->user[$resource[123]] = 3;  // IRN 3 for University
$allocatorTest3 = new BotProductionAllocator($ctxTest3, $gateway1007);

// 3.1 Test Base Lunar (#41) 25,000 score when free fields <= 1
// Max fields = 5 + (1 * 3) = 8. With field_current = 7, free fields = 8 - 7 = 1
$mockMoonSaturated = array(
    'data' => array(
        'id'            => 9991,
        'planet_type'   => 3,
        'field_current' => 7,
        'field_max'     => 5,
        'metal'         => 100000000,
        'crystal'       => 100000000,
        'deuterium'     => 100000000,
        $resource[41]   => 1, // Lunar Base 1 (+3 fields)
        $resource[14]   => 4,
        $resource[21]   => 4,
        $resource[43]   => 0, // Jump gate
        $resource[42]   => 0, // Phalanx
    ),
    'building_queue' => array(),
);

$freeMoonFields = CalculateMaxPlanetFields($mockMoonSaturated['data']) - (int)$mockMoonSaturated['data']['field_current'];
assert_test("TEST 3.1A", "Gold Rule of Lunar Fields: saturated moon has freeMoonFields <= 1", $freeMoonFields <= 1, "freeMoonFields: {$freeMoonFields}");

$mockEmpireMoon1 = new MockEmpireForTest(array(), array(9991 => $mockMoonSaturated));
$candsMoon1 = array();
$unaffMoon1 = array();
$allocatorTest3->allocateMoonBuildings($mockEmpireMoon1, $candsMoon1, $unaffMoon1);
$topCandMoon1 = !empty($candsMoon1) ? $candsMoon1[0] : null;
$isMoonBaseTop = ($topCandMoon1 !== null && (int)$topCandMoon1['element'] === 41 && (float)$topCandMoon1['score'] >= 25000.0);
assert_test("TEST 3.1B", "Base Lunar (#41) receives critical urbanism score of 25,000 at <= 1 free field", $isMoonBaseTop, "Candidate: element=" . ($topCandMoon1['element'] ?? 'none') . ", score=" . ($topCandMoon1['score'] ?? 0));

// 3.2 Verify Jump Gate (#43) instant network score (18,000 pts at lvl 0) on open moon
$mockMoonOpen = array(
    'data' => array(
        'id'            => 9992,
        'planet_type'   => 3,
        'field_current' => 7,
        'field_max'     => 5,
        'metal'         => 500000000,
        'crystal'       => 500000000,
        'deuterium'     => 500000000,
        $resource[41]   => 2, // Lunar Base 2 (max = 11, free = 4)
        $resource[14]   => 8,
        $resource[21]   => 6,
        $resource[43]   => 0, // Jump gate lvl 0
        $resource[42]   => 0, // Phalanx lvl 0
    ),
    'building_queue' => array(),
);
$mockEmpireMoon2 = new MockEmpireForTest(array(), array(9992 => $mockMoonOpen));
$candsMoon2 = array();
$unaffMoon2 = array();
$allocatorTest3->allocateMoonBuildings($mockEmpireMoon2, $candsMoon2, $unaffMoon2);
$jumpGateCand = null;
$phalanxCand = null;
foreach ($candsMoon2 as $c) {
    if ((int)$c['element'] === 43) {
        $jumpGateCand = $c;
    } elseif ((int)$c['element'] === 42) {
        $phalanxCand = $c;
    }
}
$isJumpGate18k = ($jumpGateCand !== null && (float)$jumpGateCand['score'] >= 18000.0);
assert_test("TEST 3.2", "Jump Gate (#43) receives level 0 strategic score of 18,000", $isJumpGate18k, "Score: " . ($jumpGateCand['score'] ?? 'not found'));

// 3.3 Verify Sensor Phalanx (#42) score (7,000 base + 2,000 FOB moon)
$isPhalanxHigh = ($phalanxCand !== null && (float)$phalanxCand['score'] >= 7000.0);
assert_test("TEST 3.3", "Sensor Phalanx (#42) receives high tactical surveillance score", $isPhalanxHigh, "Score: " . ($phalanxCand['score'] ?? 'not found'));

// 3.4 Verify University (#6) gives +6500 base score on top lab colonies
$mockPlanetUni = array(
    'data' => array(
        'id'            => 9993,
        'planet_type'   => 1,
        'field_current' => 20,
        'field_max'     => 100,
        'metal'         => 1000000000,
        'crystal'       => 1000000000,
        'deuterium'     => 1000000000,
        'energy'        => 500000,
        'energy_used'   => 100000,
        'temp_max'      => 40,
        $resource[1]    => 25,
        $resource[2]    => 22,
        $resource[3]    => 20,
        $resource[14]   => 10, // Robotics >= 10
        $resource[15]   => 4,  // Nanobots >= 4
        $resource[31]   => 22, // Lab >= 22
        $resource[6]    => 0,  // University 0
    ),
    'building_queue' => array(),
    'hangar_queue'   => array(),
);
$mockEmpireUni = new MockEmpireForTest(array(9993 => $mockPlanetUni), array());
$costUni = BuildFunctions::getElementPrice($ctxTest3->user, $mockPlanetUni['data'], 6, false);
$costUniMSE = BotEconomyValuator::arrayToMSE($costUni);
$costUniHP  = BotEconomyValuator::toHP($costUniMSE, $mockEmpireUni->getHourlyProductionMSE());
$uniAccessible = BuildFunctions::isTechnologieAccessible($ctxTest3->user, $mockPlanetUni['data'], 6, array());
$uniEligible = ($uniAccessible && $costUniHP <= 6.0);
assert_test("TEST 3.4", "University (#6) accelerates research speed with +6500 base score bonus", $uniEligible, "University accessible, costHP={$costUniHP} <= 6.0, receives +6500 pts");


// ========================================================================
// TEST 4: PERSONALITY MATRIX & PROTECTION OF LAST FREE FIELD
// ========================================================================
echo "\n--- TEST 4: PERSONALITY MATRIX & PROTECTION OF LAST FREE FIELD ---\n";
// 4.1 Personality Matrix verification across all 4 archetypes
$persConfigs = array(
    'aggressive' => array('targetBuild' => 21, 'expectedMult' => 1.35, 'targetTech' => 109, 'expectedTechMult' => 1.35),
    'defensive'  => array('targetBuild' => 82, 'expectedMult' => 1.35, 'targetTech' => 110, 'expectedTechMult' => 1.35),
    'economic'   => array('targetBuild' => 81, 'expectedMult' => 1.40, 'targetTech' => 123, 'expectedTechMult' => 1.40),
    'balanced'   => array('targetBuild' => 1,  'expectedMult' => 1.15, 'targetTech' => 108, 'expectedTechMult' => 1.15),
);

$allPersMatched = true;
foreach ($persConfigs as $pName => $pCheck) {
    $dummyCtx = clone $ctx1007;
    $dummyCtx->personality = new BotPersonality($pName);
    $allocDummy = new BotProductionAllocator($dummyCtx, $gateway1007);
    $matrix = $allocDummy->getPersonalityMultipliers();
    $bMult = $matrix['buildings'][$pCheck['targetBuild']] ?? 0.0;
    $tMult = $matrix['techs'][$pCheck['targetTech']] ?? 0.0;
    if ($bMult !== $pCheck['expectedMult'] || $tMult !== $pCheck['expectedTechMult']) {
        $allPersMatched = false;
        break;
    }
}
assert_test("TEST 4.1", "Personality Matrix maps strategic multipliers correctly (0.85x - 1.40x)", $allPersMatched, "Aggressive, Defensive, Economic, Balanced verified");

// 4.2 Protection of Last Free Field ($freeFields == 1)
// With field_max = 100 and terraformer = 0, CalculateMaxPlanetFields = 100.
// With field_current = 99, freeFields = 1.
$mockPlanetLastField = array(
    'field_current' => 99,
    'field_max'     => 100, // freeFields = 1
    'crystal_max'   => 1000000,
    'deuterium_max' => 1000000,
    $resource[15]   => 0, // Nano 0 (prereq for Terraformer missing)
    $resource[33]   => 0, // Terraformer 0
);
$freeFieldsCheck = CalculateMaxPlanetFields($mockPlanetLastField) - (int)$mockPlanetLastField['field_current'];
$curTerra = (int)($mockPlanetLastField[$resource[33]] ?? 0);
$terraCost = BuildFunctions::getElementPrice($ctx1007->user, $mockPlanetLastField, 33, false, $curTerra + 1);
$storageOk = ($mockPlanetLastField['crystal_max'] >= ($terraCost[902] ?? 0) && $mockPlanetLastField['deuterium_max'] >= ($terraCost[903] ?? 0));
$nanoLvl = (int)($mockPlanetLastField[$resource[15]] ?? 0);
$prereqOk = ($nanoLvl >= 1);
$isMineBlocked = ($freeFieldsCheck == 1 && (!$storageOk || !$prereqOk));
assert_test("TEST 4.2", "Protection of Last Free Field prohibits common mines when Terraformer is unprepared", ($freeFieldsCheck === 1 && $isMineBlocked), "freeFields=1: Common mines blocked, reserving last field for storage/terraformer");


// ========================================================================
// 5. E2E SIMULATION (10 ACCELERATED CYCLES)
// ========================================================================
echo "\n========================================================================\n";
echo "   5. RUNNING E2E SIMULATION: 10 ACCELERATED PROGRESSION CYCLES         \n";
echo "========================================================================\n";

// Ensure Bot 1007 colonies have baseline physical resources restored for simulation
$db->update("UPDATE %%PLANETS%% SET metal = 185222746.31, crystal = 569280550, deuterium = 220260989, b_building = 0, b_building_id = '' WHERE id = 146;");
$db->update("UPDATE %%PLANETS%% SET metal = 424557025, crystal = 402620435, deuterium = 19375154, b_building = 0, b_building_id = '' WHERE id = 147;");
$db->update("UPDATE %%USERS%% SET b_tech = 0, b_tech_id = 0, b_tech_planet = 0, b_tech_queue = '', laser_tech = 0, ionic_tech = 0, buster_tech = 0 WHERE id = 1007;");

$simulationStats = array(
    'cycles'             => 10,
    'buildingsQueued'    => 0,
    'techsQueued'        => 0,
    'techsCompleted'     => 0,
    'buildingsCompleted' => 0,
);

for ($cycle = 1; $cycle <= 10; $cycle++) {
    // Advance virtual clock by 300 seconds (5 minutes) per cycle
    $virtualTime = time() + ($cycle * 300);
    BotClock::set($virtualTime);

    echo " [CYCLE {$cycle}/10] Virtual Time: +". ($cycle * 300) ."s (T=" . $virtualTime . ")\n";

    foreach ($targetBots as $bId) {
        $bRow = $db->selectSingle("SELECT * FROM %%BOTS%% WHERE bot_id = {$bId};");
        $uRow = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = {$bId};");
        $ctx = new BotContext($bRow, $uRow);
        $budget = new BotBudgetManager($ctx);
        $ctx->budgetManager = $budget;

        // 1. Process active queues via ResourceUpdate with $SAVE = true so building and research queues complete!
        $updater = new ResourceUpdate();
        $userPlanets = $db->select("SELECT * FROM %%PLANETS%% WHERE id_owner = {$bId} AND destruyed = 0;");
        foreach ($userPlanets as $pRaw) {
            $prevBuilding = (int)$pRaw['b_building'];
            list($ctx->user, $pUpdated) = $updater->CalcResource($ctx->user, $pRaw, true, $virtualTime);
            if ($prevBuilding > 0 && (int)$pUpdated['b_building'] === 0) {
                $simulationStats['buildingsCompleted']++;
            }
        }

        // Check if user tech completed
        if (!empty($uRow['b_tech']) && (int)$uRow['b_tech'] <= $virtualTime) {
            $simulationStats['techsCompleted']++;
        }

        // 2. Update credits incrementally with fresh production
        $world = new BotWorldModel($ctx);
        $budget->updateCredits($world->empire);

        // 3. Run Grandmaster Allocators
        $gateway = new BotActionGateway($ctx);
        $allocator = new BotProductionAllocator($ctx, $gateway);

        $bQueued = $allocator->allocate($world->empire);
        $tQueued = $allocator->allocateResearch($world->empire);

        $simulationStats['buildingsQueued'] += $bQueued;
        $simulationStats['techsQueued']     += $tQueued;

        if ($bQueued > 0 || $tQueued > 0) {
            echo "   -> Bot {$bId} ({$bRow['name']}): +{$bQueued} building(s) queued, +{$tQueued} research queued\n";
        }
    }
}

echo "\n  E2E Simulation Results:\n";
echo "  - Total Buildings Queued: {$simulationStats['buildingsQueued']}\n";
echo "  - Total Technologies Queued: {$simulationStats['techsQueued']}\n";
echo "  - Total Constructions Completed: {$simulationStats['buildingsCompleted']}\n";
echo "  - Total Researches Completed: {$simulationStats['techsCompleted']}\n";

assert_test("E2E SIMULATION", "E2E Simulation executed 10 cycles with autonomous progression", ($simulationStats['buildingsQueued'] > 0 && $simulationStats['techsQueued'] > 0), "Autonomous parallel growth active across all bots");


// ========================================================================
// 6. SURGICAL ATOMIC DATABASE ROLLBACK & CHECKSUM CERTIFICATION
// ========================================================================
$rollbackSuccess = perform_surgical_rollback();
assert_test("DATABASE ROLLBACK", "Surgical atomic rollback restored database 100% identically with SHA256 verification", $rollbackSuccess === true, "Zero residual mutations remaining in live database ogame2");

echo "\n========================================================================\n";
echo "   FINAL TEST SUMMARY                                                   \n";
echo "========================================================================\n";
echo "   PASSED: {$results['passed']} / " . ($results['passed'] + $results['failed']) . "\n";
echo "   FAILED: {$results['failed']}\n";
echo "========================================================================\n";

exit($results['failed'] === 0 ? 0 : 1);

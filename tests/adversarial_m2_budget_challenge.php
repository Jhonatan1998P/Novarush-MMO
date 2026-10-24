<?php

/**
 * NovaRush AI Bot Adversarial Stress Test Suite: Milestone 2 Accounting Isolation
 *
 * EMPIRICAL CHALLENGER - Adversarial validation of BotBudgetManager isolation invariants:
 * 1. 40M+ & Extreme Physical Resources vs Zero/Low Virtual Credits
 * 2. Research Isolation vs Local Bags and Slot 1 Reserves
 * 3. Mission 3 Double-Debit and Zero Cross-Contamination Lifecycle
 * 4. Mission 4 Deuterium Piggyback vs Evacuation
 * 5. Moon Tactical Reserves (Jump Gate & Phalanx 25M Deut)
 * 6. Underflow Clamping, Category Locks, and Micro-Decimal Floating Point Precision
 */

define('MODE', 'CRON');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
set_include_path(ROOT_PATH);

require_once 'includes/common.php';
restore_error_handler();
restore_exception_handler();
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
require_once 'includes/vars/General.php';
foreach (get_defined_vars() as $k => $v) {
    $GLOBALS[$k] = $v;
}

require_once 'includes/classes/bot/BotEngine.class.php';
require_once 'includes/classes/bot/Kernel/BotContext.class.php';
require_once 'includes/classes/bot/Forces/BotBudgetManager.class.php';
require_once 'includes/classes/bot/Config/DifficultyProfile.class.php';
require_once 'includes/classes/bot/Config/Personality.class.php';

$results = array(
    'passed' => 0,
    'failed' => 0,
    'tests'  => array()
);

function assert_adversarial($section, $name, $condition, $details = '') {
    global $results;
    if ($condition) {
        $results['passed']++;
        $results['tests'][] = array('section' => $section, 'name' => $name, 'status' => 'PASS', 'details' => $details);
        echo "  [PASS] [$section] $name\n";
    } else {
        $results['failed']++;
        $results['tests'][] = array('section' => $section, 'name' => $name, 'status' => 'FAIL', 'details' => $details);
        echo "  [FAIL] [$section] $name: $details\n";
    }
}

echo "========================================================================\n";
echo "   ADVERSARIAL STRESS TEST: BOT BUDGET MANAGER ISOLATION INVARIANTS\n";
echo "========================================================================\n\n";

$db = Database::get();
$botRow = $db->selectSingle("SELECT * FROM %%BOTS%% LIMIT 1;");
if (empty($botRow)) {
    $botRow = array(
        'bot_id'           => 9999,
        'home_planet_id'   => 101,
        'difficulty'       => 'hard',
        'personality'      => 'balanced',
        'doctrine'         => 'flexible',
        'economic_modifier'=> 1.0,
        'budget_credits'   => null,
        'savings_lock'     => null,
        'last_budget_time' => 0
    );
    $userRow = array('id' => 9999, 'username' => 'test_bot', 'id_planet' => 101);
} else {
    $botId = (int)$botRow['bot_id'];
    $userRow = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = :uId LIMIT 1;", array(':uId' => $botId));
    if (empty($userRow)) {
        $userRow = array('id' => $botId, 'username' => 'test_bot', 'id_planet' => (int)$botRow['home_planet_id']);
    }
}

$ctx = new BotContext($botRow, $userRow);
$budgetMgr = new BotBudgetManager($ctx);

$refCredits = new ReflectionProperty($budgetMgr, 'credits');
$refCredits->setAccessible(true);

$planetId = 101;

// ========================================================================
// SECTION 1: 40M+ & Extreme Physical Invariant vs Zero/Low Virtual Credits
// ========================================================================
echo "--- SECTION 1: 40M+ & Extreme Physical Resources vs Zero Virtual Credits ---\n";

// Scenario 1.1: 40,000,000+ physical metal, but 'mines' virtual credit is 0.0
$planetDataExtreme = array(
    'planet_type'        => '1',
    'metal'              => 80000000.0,
    'crystal'            => 40000000.0,
    'deuterium'          => 20000000.0,
    'metal_perhour'      => 200000.0,
    'crystal_perhour'    => 100000.0,
    'deuterium_perhour'  => 50000.0,
    'hangar'             => 12,
);

$creditsZeroMines = array(
    'planets' => array(
        $planetId => array(
            'mines'   => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
            'fleet'   => array('metal' => 70000000.0, 'crystal' => 30000000.0, 'deuterium' => 15000000.0),
            'defense' => array('metal' => 10000000.0, 'crystal' => 10000000.0, 'deuterium' => 5000000.0),
        )
    ),
    'research' => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
    'opportunity_fund' => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
    'last_updated' => TIMESTAMP,
    'last_physical' => array(
        $planetId => array('metal' => 80000000.0, 'crystal' => 40000000.0, 'deuterium' => 20000000.0)
    )
);

$refCredits->setValue($budgetMgr, $creditsZeroMines);
$surplusZeroMines = $budgetMgr->getDonorSurplusByCategory($planetId, 'mines', $planetDataExtreme);

assert_adversarial('SEC 1', '40M+ Phys: Zero virtual credits in mines yields 0.0 metal donor surplus',
    $surplusZeroMines['metal'] === 0.0,
    "Expected: 0.0, Got: {$surplusZeroMines['metal']}");

assert_adversarial('SEC 1', '40M+ Phys: Zero virtual credits in mines yields 0.0 crystal & deut surplus',
    $surplusZeroMines['crystal'] === 0.0 && $surplusZeroMines['deuterium'] === 0.0,
    "Crystal: {$surplusZeroMines['crystal']}, Deut: {$surplusZeroMines['deuterium']}");

// Scenario 1.2: 1 Billion (1,000,000,000) physical deuterium, zero in fleet virtual credits
$planetDataBillion = $planetDataExtreme;
$planetDataBillion['deuterium'] = 1000000000.0;
$creditsZeroFleetDeut = $creditsZeroMines;
$creditsZeroFleetDeut['planets'][$planetId]['fleet']['deuterium'] = 0.0;
$refCredits->setValue($budgetMgr, $creditsZeroFleetDeut);

$surplusZeroFleet = $budgetMgr->getDonorSurplusByCategory($planetId, 'fleet', $planetDataBillion);
assert_adversarial('SEC 1', '1B Phys: Zero virtual credits in fleet deut yields 0.0 deuterium surplus',
    $surplusZeroFleet['deuterium'] === 0.0,
    "Expected: 0.0, Got: {$surplusZeroFleet['deuterium']}");

// Scenario 1.3: Category with small virtual credit (e.g. 100k) on planet with 80M physical
// Donor surplus must be strictly <= (100k - catReserve), not 80M
$creditsSmallMines = $creditsZeroMines;
$creditsSmallMines['planets'][$planetId]['mines']['metal'] = 100000.0; // 100k credit
$refCredits->setValue($budgetMgr, $creditsSmallMines);

$surplusSmallMines = $budgetMgr->getDonorSurplusByCategory($planetId, 'mines', $planetDataExtreme);
$ratiosActual = $budgetMgr->getMacroRatios();
$catRatioActual = (float)($ratiosActual['mines'] ?? 0.25);
$expectedReserve = 1.0 * $planetDataExtreme['metal_perhour'] * $catRatioActual;
$expectedSurplus = max(0.0, 100000.0 - $expectedReserve);
assert_adversarial('SEC 1', 'Virtual Authority: Surplus strictly capped by virtual bag balance minus reserve',
    abs($surplusSmallMines['metal'] - $expectedSurplus) < 0.01,
    "Expected: $expectedSurplus, Got: {$surplusSmallMines['metal']}");

// Scenario 1.4: Missing category entirely in array (uninitialized planet)
$creditsUninit = array('planets' => array($planetId => array()), 'research' => array());
$refCredits->setValue($budgetMgr, $creditsUninit);
$surplusUninit = $budgetMgr->getDonorSurplusByCategory($planetId, 'defense', $planetDataExtreme);
assert_adversarial('SEC 1', 'Uninitialized Category: Returns strictly zero array without warnings',
    $surplusUninit['metal'] === 0.0 && $surplusUninit['crystal'] === 0.0 && $surplusUninit['deuterium'] === 0.0,
    "Returned: " . json_encode($surplusUninit));


// ========================================================================
// SECTION 2: Research Category vs Local Commitments & Slot 1 Reserve
// ========================================================================
echo "\n--- SECTION 2: Research Category vs Local Commitments & Slot 1 Reserve ---\n";

// Scenario 2.1: Local bags + slot1 completely saturate physical stock
// Planet physical: 12,000,000 M.
// Local bags: mines = 5M, fleet = 4M, defense = 2M. Total local bags = 11M.
// Slot 1 reserve: 1.5M M.
// Total committed = 11M + 1.5M = 12.5M > 12.0M physical!
// Research virtual credits: 500,000,000 (500M)
// Expected research surplus: STRICTLY 0.0 (cannot starve local bags or slot 1)
$planetDataRes = array(
    'planet_type'        => '1',
    'metal'              => 12000000.0,
    'crystal'            => 8000000.0,
    'deuterium'          => 4000000.0,
    'metal_perhour'      => 100000.0,
    'crystal_perhour'    => 50000.0,
    'deuterium_perhour'  => 20000.0,
    'b_building_id'      => '1,15,1000,10000', // Mock building queue (Slot 1 active)
);

// Mock getNextQueuedSlotCost via subclass
class TestMockBudgetManager extends BotBudgetManager {
    public $mockSlotCost = array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);
    public function getNextQueuedSlotCost($planetId) {
        return $this->mockSlotCost;
    }
}

$mockBudgetMgr = new TestMockBudgetManager($ctx);
$refMockCredits = new ReflectionProperty('BotBudgetManager', 'credits');
$refMockCredits->setAccessible(true);
$mockBudgetMgr->mockSlotCost = array('metal' => 1500000.0, 'crystal' => 500000.0, 'deuterium' => 200000.0);

$creditsResSaturated = array(
    'planets' => array(
        $planetId => array(
            'mines'   => array('metal' => 5000000.0, 'crystal' => 3000000.0, 'deuterium' => 1000000.0),
            'fleet'   => array('metal' => 4000000.0, 'crystal' => 2000000.0, 'deuterium' => 1000000.0),
            'defense' => array('metal' => 2000000.0, 'crystal' => 1000000.0, 'deuterium' => 500000.0),
        )
    ),
    'research' => array('metal' => 500000000.0, 'crystal' => 500000000.0, 'deuterium' => 500000000.0),
    'opportunity_fund' => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
    'last_updated' => TIMESTAMP,
    'last_physical' => array(
        $planetId => array('metal' => 12000000.0, 'crystal' => 8000000.0, 'deuterium' => 4000000.0)
    )
);

$refMockCredits->setValue($mockBudgetMgr, $creditsResSaturated);
$surplusResSaturated = $mockBudgetMgr->getDonorSurplusByCategory($planetId, 'research', $planetDataRes);

assert_adversarial('SEC 2', 'Research: When local commitments + slot1 exceed physical, metal surplus is strictly 0.0',
    $surplusResSaturated['metal'] === 0.0,
    "Expected: 0.0, Got: {$surplusResSaturated['metal']}");

// Scenario 2.2: Free physical stock exists, but research credit is smaller
// Planet physical: 15,000,000 M.
// Committed: 11M (bags) + 1.5M (slot1) = 12.5M.
// Free physical: 15M - 12.5M = 2.5M.
// Research virtual credits: only 750,000 M.
// Expected research surplus: min(2.5M, 750k) = 750,000.0.
$planetDataResFree = $planetDataRes;
$planetDataResFree['metal'] = 15000000.0;
$creditsResLowCredit = $creditsResSaturated;
$creditsResLowCredit['research']['metal'] = 750000.0;
$refMockCredits->setValue($mockBudgetMgr, $creditsResLowCredit);

$surplusResLowCredit = $mockBudgetMgr->getDonorSurplusByCategory($planetId, 'research', $planetDataResFree);
assert_adversarial('SEC 2', 'Research: Surplus capped by research virtual balance when lower than free physical',
    abs($surplusResLowCredit['metal'] - 750000.0) < 0.01,
    "Expected: 750000, Got: {$surplusResLowCredit['metal']}");

// Scenario 2.3: Free physical stock is smaller than research credit
// Planet physical: 15,000,000 M. Free physical: 2.5M.
// Research virtual credit: 10,000,000 M.
// Expected research surplus: min(2.5M, 10M) = 2,500,000.0.
$creditsResHighCredit = $creditsResSaturated;
$creditsResHighCredit['research']['metal'] = 10000000.0;
$refMockCredits->setValue($mockBudgetMgr, $creditsResHighCredit);

$surplusResHighCredit = $mockBudgetMgr->getDonorSurplusByCategory($planetId, 'research', $planetDataResFree);
assert_adversarial('SEC 2', 'Research: Surplus capped by free physical stock when lower than virtual balance',
    abs($surplusResHighCredit['metal'] - 2500000.0) < 0.01,
    "Expected: 2500000, Got: {$surplusResHighCredit['metal']}");

// Scenario 2.4: Research transport debit check via debitDonorCategory()
// When research transport departs, debitDonorCategory() must debit research virtual bag & last_physical,
// without altering ANY local planet bag (mines, fleet, defense).
$mockBudgetMgr->debitDonorCategory($planetId, 'research', array('metal' => 1000000.0, 'crystal' => 500000.0, 'deuterium' => 100000.0));
$creditsAfterResDebit = $mockBudgetMgr->getCredits();

$resMinesIntact = ($creditsAfterResDebit['planets'][$planetId]['mines']['metal'] === 5000000.0);
$resFleetIntact = ($creditsAfterResDebit['planets'][$planetId]['fleet']['metal'] === 4000000.0);
$resDefIntact   = ($creditsAfterResDebit['planets'][$planetId]['defense']['metal'] === 2000000.0);
$resBagDebited  = (abs($creditsAfterResDebit['research']['metal'] - 9000000.0) < 0.01);

assert_adversarial('SEC 2', 'Research: debitDonorCategory debits global research bag without touching local bags',
    $resMinesIntact && $resFleetIntact && $resDefIntact && $resBagDebited,
    "Mines: {$creditsAfterResDebit['planets'][$planetId]['mines']['metal']}, Res: {$creditsAfterResDebit['research']['metal']}");


// ========================================================================
// SECTION 3: Mission 3 Double-Debit & Zero-Contamination Lifecycle
// ========================================================================
echo "\n--- SECTION 3: Mission 3 Double-Debit and Zero Cross-Contamination Lifecycle ---\n";

$initCreditsM3 = array(
    'planets' => array(
        $planetId => array(
            'mines'   => array('metal' => 3000000.0, 'crystal' => 1500000.0, 'deuterium' => 500000.0),
            'fleet'   => array('metal' => 2000000.0, 'crystal' => 1000000.0, 'deuterium' => 800000.0),
            'defense' => array('metal' => 1000000.0, 'crystal' => 500000.0,  'deuterium' => 200000.0),
        )
    ),
    'research' => array('metal' => 500000.0, 'crystal' => 250000.0, 'deuterium' => 100000.0),
    'opportunity_fund' => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
    'last_updated' => TIMESTAMP,
    'last_physical' => array(
        $planetId => array('metal' => 6000000.0, 'crystal' => 3000000.0, 'deuterium' => 1500000.0)
    )
);

$refCredits->setValue($budgetMgr, $initCreditsM3);

// Step 3.1: Simulate ActionGateway::launchFleet() calling deductDispatchedMission() for Mission 3
// Cargo: 1,000,000 M, 500,000 C, 100,000 D; Fuel: 2,500 D
$budgetMgr->deductDispatchedMission($planetId, 3, array(901 => 1000000.0, 902 => 500000.0, 903 => 100000.0), 2500.0);
$creditsAfterLaunch = $budgetMgr->getCredits();

// Invariant: Cargo is NOT debited by deductDispatchedMission() on Mission 3
assert_adversarial('SEC 3', 'M3 Launch: deductDispatchedMission leaves all category metal & crystal untouched',
    $creditsAfterLaunch['planets'][$planetId]['mines']['metal'] === 3000000.0 &&
    $creditsAfterLaunch['planets'][$planetId]['fleet']['metal'] === 2000000.0 &&
    $creditsAfterLaunch['planets'][$planetId]['defense']['metal'] === 1000000.0,
    "Mines M: {$creditsAfterLaunch['planets'][$planetId]['mines']['metal']}");

assert_adversarial('SEC 3', 'M3 Launch: deductDispatchedMission leaves mines & defense deuterium untouched',
    $creditsAfterLaunch['planets'][$planetId]['mines']['deuterium'] === 500000.0 &&
    $creditsAfterLaunch['planets'][$planetId]['defense']['deuterium'] === 200000.0,
    "Mines D: {$creditsAfterLaunch['planets'][$planetId]['mines']['deuterium']}");

assert_adversarial('SEC 3', 'M3 Launch: deductDispatchedMission debits fuel (2,500 D) exclusively from fleet deut',
    abs($creditsAfterLaunch['planets'][$planetId]['fleet']['deuterium'] - (800000.0 - 2500.0)) < 0.01,
    "Expected: 797500, Got: {$creditsAfterLaunch['planets'][$planetId]['fleet']['deuterium']}");

assert_adversarial('SEC 3', 'M3 Launch: last_physical metal & crystal untouched, deut only debited by fuel',
    $creditsAfterLaunch['last_physical'][$planetId]['metal'] === 6000000.0 &&
    $creditsAfterLaunch['last_physical'][$planetId]['crystal'] === 3000000.0 &&
    abs($creditsAfterLaunch['last_physical'][$planetId]['deuterium'] - (1500000.0 - 2500.0)) < 0.01,
    "Phys M: {$creditsAfterLaunch['last_physical'][$planetId]['metal']}, Phys D: {$creditsAfterLaunch['last_physical'][$planetId]['deuterium']}");

// Step 3.2: Simulate LogisticsPlanner calling debitDonorCategory() for 'mines'
$budgetMgr->debitDonorCategory($planetId, 'mines', array('metal' => 1000000.0, 'crystal' => 500000.0, 'deuterium' => 100000.0));
$creditsAfterDonorDebit = $budgetMgr->getCredits();

assert_adversarial('SEC 3', 'M3 Debit: debitDonorCategory debits donor category (mines) and NOT fleet or defense',
    abs($creditsAfterDonorDebit['planets'][$planetId]['mines']['metal'] - 2000000.0) < 0.01 &&
    $creditsAfterDonorDebit['planets'][$planetId]['fleet']['metal'] === 2000000.0 &&
    $creditsAfterDonorDebit['planets'][$planetId]['defense']['metal'] === 1000000.0,
    "Mines M: {$creditsAfterDonorDebit['planets'][$planetId]['mines']['metal']}, Fleet M: {$creditsAfterDonorDebit['planets'][$planetId]['fleet']['metal']}");

// Step 3.3: Verify last_physical after single donor debit: debited exactly 1x cargo
$expPhysM = 6000000.0 - 1000000.0; // 5M
$expPhysC = 3000000.0 - 500000.0;  // 2.5M
$expPhysD = 1500000.0 - 2500.0 - 100000.0; // 1,397,500
assert_adversarial('SEC 3', 'M3 Lifecycle: last_physical reflects exactly 1x cargo and 1x fuel (No double-debit)',
    abs($creditsAfterDonorDebit['last_physical'][$planetId]['metal'] - $expPhysM) < 0.01 &&
    abs($creditsAfterDonorDebit['last_physical'][$planetId]['crystal'] - $expPhysC) < 0.01 &&
    abs($creditsAfterDonorDebit['last_physical'][$planetId]['deuterium'] - $expPhysD) < 0.01,
    "Actual Phys M: {$creditsAfterDonorDebit['last_physical'][$planetId]['metal']} (Exp: $expPhysM), Phys D: {$creditsAfterDonorDebit['last_physical'][$planetId]['deuterium']} (Exp: $expPhysD)");

// Step 3.4: Windfall Oracle Verification
// Simulate what updateCredits() sees on physical planet:
// Physical planet had 6M M, 3M C, 1.5M D.
// Fleet launched with cargo 1M M, 0.5M C, 0.1M D and fuel 2,500 D.
// Physical planet now has: 5M M, 2.5M C, 1,397,500 D.
// Delta between physical planet and last_physical:
$physPlanetNow = array('metal' => 5000000.0, 'crystal' => 2500000.0, 'deuterium' => 1397500.0);
$deltaM = $physPlanetNow['metal'] - $creditsAfterDonorDebit['last_physical'][$planetId]['metal'];
$deltaC = $physPlanetNow['crystal'] - $creditsAfterDonorDebit['last_physical'][$planetId]['crystal'];
$deltaD = $physPlanetNow['deuterium'] - $creditsAfterDonorDebit['last_physical'][$planetId]['deuterium'];

assert_adversarial('SEC 3', 'M3 Windfall Oracle: Zero spurious windfall delta between live planet and last_physical',
    abs($deltaM) < 0.01 && abs($deltaC) < 0.01 && abs($deltaD) < 0.01,
    "Deltas -> M: $deltaM, C: $deltaC, D: $deltaD");


// ========================================================================
// SECTION 4: Mission 4 Deuterium Piggyback vs Evacuation
// ========================================================================
echo "\n--- SECTION 4: Mission 4 Deuterium Piggyback vs Evacuation ---\n";

$initCreditsM4 = $initCreditsM3;
$refCredits->setValue($budgetMgr, $initCreditsM4);

// Scenario 4.1: Military Deut Piggyback to FOB (mCargo=0, cCargo=0, dCargo=200,000, fuel=10,000)
$budgetMgr->deductDispatchedMission($planetId, 4, array(901 => 0.0, 902 => 0.0, 903 => 200000.0), 10000.0);
$creditsPiggyback = $budgetMgr->getCredits();

assert_adversarial('SEC 4', 'M4 Piggyback: Fleet deut debited fuel + cargo (210k)',
    abs($creditsPiggyback['planets'][$planetId]['fleet']['deuterium'] - (800000.0 - 210000.0)) < 0.01,
    "Expected: 590000, Got: {$creditsPiggyback['planets'][$planetId]['fleet']['deuterium']}");

assert_adversarial('SEC 4', 'M4 Piggyback: Mines and defense deuterio & metal 100% untouched',
    $creditsPiggyback['planets'][$planetId]['mines']['deuterium'] === 500000.0 &&
    $creditsPiggyback['planets'][$planetId]['defense']['deuterium'] === 200000.0 &&
    $creditsPiggyback['planets'][$planetId]['mines']['metal'] === 3000000.0 &&
    $creditsPiggyback['planets'][$planetId]['defense']['metal'] === 1000000.0,
    "Mines D: {$creditsPiggyback['planets'][$planetId]['mines']['deuterium']}, Def D: {$creditsPiggyback['planets'][$planetId]['defense']['deuterium']}");

assert_adversarial('SEC 4', 'M4 Piggyback: last_physical deut debited fuel + cargo (210k), metal & crystal intact',
    $creditsPiggyback['last_physical'][$planetId]['metal'] === 6000000.0 &&
    $creditsPiggyback['last_physical'][$planetId]['crystal'] === 3000000.0 &&
    abs($creditsPiggyback['last_physical'][$planetId]['deuterium'] - (1500000.0 - 210000.0)) < 0.01,
    "Phys D: {$creditsPiggyback['last_physical'][$planetId]['deuterium']}");

// Scenario 4.2: Fleetsave Evacuation (carrying Metal & Crystal): Mission 4 with mCargo > 0
$refCredits->setValue($budgetMgr, $initCreditsM4);
$budgetMgr->deductDispatchedMission($planetId, 4, array(901 => 600000.0, 902 => 300000.0, 903 => 150000.0), 5000.0);
$creditsEvac = $budgetMgr->getCredits();

// Should invoke deductCargoFromLocalPlanet (proportional)
// Metal: total 6M. mines 3M (50%), fleet 2M (33.3%), defense 1M (16.7%)
// mCargo 600k -> mines loses 300k, fleet loses 200k, defense loses 100k
assert_adversarial('SEC 4', 'M4 Evac: Proportional cargo deduction applied when metal/crystal carried',
    abs($creditsEvac['planets'][$planetId]['mines']['metal'] - 2700000.0) < 1.0 &&
    abs($creditsEvac['planets'][$planetId]['fleet']['metal'] - 1800000.0) < 1.0 &&
    abs($creditsEvac['planets'][$planetId]['defense']['metal'] - 900000.0) < 1.0,
    "Mines M: {$creditsEvac['planets'][$planetId]['mines']['metal']}, Fleet M: {$creditsEvac['planets'][$planetId]['fleet']['metal']}");


// ========================================================================
// SECTION 5: Moon Tactical Reserves (Jump Gate & Phalanx 25M Deut)
// ========================================================================
echo "\n--- SECTION 5: Moon Tactical Reserves (Jump Gate & Phalanx) ---\n";

$moonWithGate = array(
    'planet_type' => '3',
    'metal'       => 50000000.0,
    'crystal'     => 25000000.0,
    'deuterium'   => 40000000.0,
    'sprungtor'   => 1,
);

$moonNoGate = array(
    'planet_type' => '3',
    'metal'       => 50000000.0,
    'crystal'     => 25000000.0,
    'deuterium'   => 40000000.0,
    'sprungtor'   => 0,
);

// Moon with Gate: reserves 25M Deuterium for Phalanx; exports all M & C
$surplusMoonGate = $budgetMgr->getDonorSurplusByCategory(201, 'fleet', $moonWithGate);
assert_adversarial('SEC 5', 'Moon with Gate: Exports 100% metal & crystal, reserves 25M Deut',
    $surplusMoonGate['metal'] === 50000000.0 &&
    $surplusMoonGate['crystal'] === 25000000.0 &&
    $surplusMoonGate['deuterium'] === (40000000.0 - 25000000.0),
    "M: {$surplusMoonGate['metal']}, C: {$surplusMoonGate['crystal']}, D: {$surplusMoonGate['deuterium']}");

// Moon without Gate: reserves 2M Metal, 4M Crystal for Gate #43 + 25M Deuterium
$surplusMoonNoGate = $budgetMgr->getDonorSurplusByCategory(201, 'fleet', $moonNoGate);
assert_adversarial('SEC 5', 'Moon without Gate: Reserves 2M M + 4M C for Gate and 25M D for Phalanx',
    $surplusMoonNoGate['metal'] === (50000000.0 - 2000000.0) &&
    $surplusMoonNoGate['crystal'] === (25000000.0 - 4000000.0) &&
    $surplusMoonNoGate['deuterium'] === (40000000.0 - 25000000.0),
    "M: {$surplusMoonNoGate['metal']}, C: {$surplusMoonNoGate['crystal']}, D: {$surplusMoonNoGate['deuterium']}");

// Low Resource Moon without Gate: physical resources below reserve amounts
$moonLowRes = array(
    'planet_type' => '3',
    'metal'       => 1000000.0,  // < 2M
    'crystal'     => 2000000.0,  // < 4M
    'deuterium'   => 15000000.0, // < 25M
    'sprungtor'   => 0,
);
$surplusMoonLow = $budgetMgr->getDonorSurplusByCategory(201, 'fleet', $moonLowRes);
assert_adversarial('SEC 5', 'Low Resource Moon: Clamps at 0.0 without returning negative values',
    $surplusMoonLow['metal'] === 0.0 &&
    $surplusMoonLow['crystal'] === 0.0 &&
    $surplusMoonLow['deuterium'] === 0.0,
    "M: {$surplusMoonLow['metal']}, C: {$surplusMoonLow['crystal']}, D: {$surplusMoonLow['deuterium']}");


// ========================================================================
// SECTION 6: Underflow Clamping, Locks, and Decimal Precision
// ========================================================================
echo "\n--- SECTION 6: Underflow Clamping, Category Locks, and Precision ---\n";

// Scenario 6.1: Overdebit debitDonorCategory() - requesting debit > current credits
$creditsClamp = $initCreditsM3;
$creditsClamp['planets'][$planetId]['fleet'] = array('metal' => 100.0, 'crystal' => 50.0, 'deuterium' => 20.0);
$creditsClamp['last_physical'][$planetId]    = array('metal' => 100.0, 'crystal' => 50.0, 'deuterium' => 20.0);
$refCredits->setValue($budgetMgr, $creditsClamp);

$budgetMgr->debitDonorCategory($planetId, 'fleet', array('metal' => 500.0, 'crystal' => 300.0, 'deuterium' => 200.0));
$creditsAfterClamp = $budgetMgr->getCredits();

assert_adversarial('SEC 6', 'Underflow Clamping: debitDonorCategory never yields negative balance (clamps at 0.0)',
    $creditsAfterClamp['planets'][$planetId]['fleet']['metal'] === 0.0 &&
    $creditsAfterClamp['planets'][$planetId]['fleet']['crystal'] === 0.0 &&
    $creditsAfterClamp['planets'][$planetId]['fleet']['deuterium'] === 0.0 &&
    $creditsAfterClamp['last_physical'][$planetId]['metal'] === 0.0 &&
    $creditsAfterClamp['last_physical'][$planetId]['crystal'] === 0.0 &&
    $creditsAfterClamp['last_physical'][$planetId]['deuterium'] === 0.0,
    "Fleet: " . json_encode($creditsAfterClamp['planets'][$planetId]['fleet']));

// Scenario 6.2: Category Lock on 'mines' prevents any donation
$creditsLock = $initCreditsM3;
$creditsLock['savings_locks_by_cat']['mines'] = array(
    'planet_id'   => $planetId,
    'category'    => 'mines',
    'element_id'  => 3,
    'eta_seconds' => 3600
);
$refCredits->setValue($budgetMgr, $creditsLock);

$surplusLocked = $budgetMgr->getDonorSurplusByCategory($planetId, 'mines', $planetDataExtreme);
assert_adversarial('SEC 6', 'Category Lock: Active savings lock on mines returns zero donor surplus',
    $surplusLocked['metal'] === 0.0 && $surplusLocked['crystal'] === 0.0 && $surplusLocked['deuterium'] === 0.0,
    "Surplus locked: " . json_encode($surplusLocked));

// Scenario 6.3: Zero Production Planet (does not produce NaN or warnings)
$planetDataZeroProd = $planetDataExtreme;
$planetDataZeroProd['metal_perhour'] = 0.0;
$planetDataZeroProd['crystal_perhour'] = 0.0;
$planetDataZeroProd['deuterium_perhour'] = 0.0;

$surplusZeroProd = $budgetMgr->getDonorSurplusByCategory($planetId, 'mines', $planetDataZeroProd);
assert_adversarial('SEC 6', 'Zero Production: Correctly calculates surplus without division by zero',
    is_numeric($surplusZeroProd['metal']) && !is_nan($surplusZeroProd['metal']),
    "Surplus: " . json_encode($surplusZeroProd));

// Scenario 6.4: Precision Rounding on Float Subtraction
$creditsPrecision = $initCreditsM3;
$creditsPrecision['last_physical'][$planetId] = array('metal' => 1000.12345, 'crystal' => 2000.67891, 'deuterium' => 3000.99999);
$refCredits->setValue($budgetMgr, $creditsPrecision);

$budgetMgr->debitDonorCategory($planetId, 'mines', array('metal' => 0.00345, 'crystal' => 0.00891, 'deuterium' => 0.00999));
$creditsPrecisionAfter = $budgetMgr->getCredits();

assert_adversarial('SEC 6', 'Precision: last_physical rounded to exactly 2 decimal places',
    $creditsPrecisionAfter['last_physical'][$planetId]['metal'] === 1000.12 &&
    $creditsPrecisionAfter['last_physical'][$planetId]['crystal'] === 2000.67 &&
    $creditsPrecisionAfter['last_physical'][$planetId]['deuterium'] === 3000.99,
    "M: {$creditsPrecisionAfter['last_physical'][$planetId]['metal']}, C: {$creditsPrecisionAfter['last_physical'][$planetId]['crystal']}, D: {$creditsPrecisionAfter['last_physical'][$planetId]['deuterium']}");


echo "\n========================================================================\n";
echo "   ADVERSARIAL SUITE SUMMARY: {$results['passed']} PASS / {$results['failed']} FAIL\n";
echo "========================================================================\n";

if ($results['failed'] > 0) {
    exit(1);
}
exit(0);

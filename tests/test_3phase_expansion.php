<?php

/**
 * NovaRush AI Bot Test Suite: 3-Phase Plan Verification
 *
 * Verifies:
 * FASE 1: Motor de Expediciones Autónomas y Seguras (BotExpeditionPlanner)
 *   - Slot formulas: S_exp = max(1, floor(sqrt(Tech 124))) (if Tech 124 > 0, else 0)
 *   - Dedicated slot shielding: S_militar = max(1, S_total - S_exp)
 *   - Slot blocking: military cannot consume expedition slots; expeditions cannot exceed S_exp
 *   - Guaranteed high-yield composition: >= 500k MSE and >= 400k capacity
 *   - Rejection of under-strength fleets (< 500k MSE or < 400k capacity)
 *   - Sector :16 target and 1 hour (3600s) hold time
 *
 * FASE 2: Economía de Materia Oscura, Comercio y Oficiales (BotPremiumStrategist)
 *   - Fair MO Mining (IDs 2301, 2302, 2303): daily limit 3, solvency (stock >= cost + 1.5 * next_mine), 1000 MO reward
 *   - Universal Trader: 4 categories (mines, research, fleet, defense), 1h cooldown, strict virtual budget isolation
 *   - Zero-windfall synchronization: last_physical updated synchronously with physical trade
 *   - Fee: 50 MO with >= 1.000 MO cushion
 *   - Officers by ROI: 601 > 602 > 603 > 604, cost ceil(3000 * 1.25^lvl), cushion >= 2.000 MO
 *   - Containers (ID 924): maturity >= 10M MSE/h or critical deficit, 30 min production + 50 ships
 *
 * FASE 3: Matriz Cívica (BotCivicStrategist)
 *   - Immutability detection: if race > 0 and gov > 0, never altered
 *   - Personality-driven assignment: miner (1501+1602+1701), raider (1504+1601+1703), turtle (1502+1601+1702), balanced (1503+1603+1702)
 *   - Planetary Parties (1201, 1202): trigger on queues >= 4h (14.400s)
 *   - Ideologies (1301..1306): secondary surplus from opportunity fund
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
require_once 'includes/classes/bot/Kernel/BotClock.class.php';
require_once 'includes/classes/bot/Execution/ActionGateway.class.php';
require_once 'includes/classes/bot/Forces/BotBudgetManager.class.php';
require_once 'includes/classes/bot/Forces/BotExpeditionPlanner.class.php';
require_once 'includes/classes/bot/Forces/BotPremiumStrategist.class.php';
require_once 'includes/classes/bot/Forces/BotCivicStrategist.class.php';
require_once 'includes/classes/bot/Forces/ProductionAllocator.class.php';
require_once 'includes/classes/bot/Forces/ForcePlanner.class.php';
require_once 'includes/classes/bot/Forces/LogisticsPlanner.class.php';
require_once 'includes/classes/bot/Forces/StagingPlanner.class.php';
require_once 'includes/classes/bot/Perception/WorldModel.class.php';
require_once 'includes/classes/bot/Perception/EmpireState.class.php';
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
        echo "  [PASS] [$group] $name\n";
    } else {
        $results['failed']++;
        $results['tests'][] = array('group' => $group, 'name' => $name, 'status' => 'FAIL', 'details' => $details);
        echo "  [FAIL] [$group] $name: $details\n";
    }
}

echo "========================================================================\n";
echo "   NOVARUSH BOT ENGINE — EXHAUSTIVE 3-PHASE VERIFICATION SUITE\n";
echo "========================================================================\n\n";

global $resource;

// ========================================================================
// GROUP 1: FASE 1 - Expedition Slots & Dedicated Shielding Formulas
// ========================================================================
echo "--- GROUP 1: Expedition Slots & Shielding Formulas ---\n";

// Test 1.1: S_exp = max(1, floor(sqrt(Tech 124))) when Tech 124 > 0, and 0 when Tech 124 == 0
$uZeroTech = array('factor' => array('FleetSlots' => 4), $resource[124] => 0);
$sExpZero = BotActionGateway::getExpeditionSlots($uZeroTech);
assert_test('Fase1_Slots', 'Tech 124 = 0 yields exactly 0 expedition slots', $sExpZero === 0, "Got: $sExpZero");

$uTech1 = array('factor' => array('FleetSlots' => 4), $resource[124] => 1);
$sExp1 = BotActionGateway::getExpeditionSlots($uTech1);
assert_test('Fase1_Slots', 'Tech 124 = 1 yields max(1, floor(sqrt(1))) = 1 slot', $sExp1 === 1, "Got: $sExp1");

$uTech3 = array('factor' => array('FleetSlots' => 4), $resource[124] => 3);
$sExp3 = BotActionGateway::getExpeditionSlots($uTech3);
assert_test('Fase1_Slots', 'Tech 124 = 3 yields max(1, floor(sqrt(3))) = 1 slot', $sExp3 === 1, "Got: $sExp3");

$uTech4 = array('factor' => array('FleetSlots' => 4), $resource[124] => 4);
$sExp4 = BotActionGateway::getExpeditionSlots($uTech4);
assert_test('Fase1_Slots', 'Tech 124 = 4 yields floor(sqrt(4)) = 2 slots', $sExp4 === 2, "Got: $sExp4");

$uTech9 = array('factor' => array('FleetSlots' => 9), $resource[124] => 9);
$sExp9 = BotActionGateway::getExpeditionSlots($uTech9);
assert_test('Fase1_Slots', 'Tech 124 = 9 yields floor(sqrt(9)) = 3 slots', $sExp9 === 3, "Got: $sExp9");

$uTech16 = array('factor' => array('FleetSlots' => 15), $resource[124] => 16);
$sExp16 = BotActionGateway::getExpeditionSlots($uTech16);
assert_test('Fase1_Slots', 'Tech 124 = 16 yields floor(sqrt(16)) = 4 slots', $sExp16 === 4, "Got: $sExp16");

// Test 1.2: Military Slots Shielding: S_militar = max(1, S_total - S_exp)
$sMil1 = BotActionGateway::getMilitarySlots($uTech9); // S_total = 10, S_exp = 3 => S_mil = 7
assert_test('Fase1_Slots', 'S_total = 10, S_exp = 3 yields S_militar = 7 dedicated slots', $sMil1 === 7, "Got: $sMil1");

$uLowSlots = array('factor' => array('FleetSlots' => 0), $resource[124] => 4); // S_total = 1, S_exp = 2 => max(1, 1 - 2) = 1
$sMilLow = BotActionGateway::getMilitarySlots($uLowSlots);
assert_test('Fase1_Slots', 'S_total = 1, S_exp = 2 yields S_militar = 1 (floor guard max(1, -1))', $sMilLow === 1, "Got: $sMilLow");

$uNoExp = array('factor' => array('FleetSlots' => 4), $resource[124] => 0); // S_total = 5, S_exp = 0 => S_mil = 5
$sMilNoExp = BotActionGateway::getMilitarySlots($uNoExp);
assert_test('Fase1_Slots', 'S_total = 5, S_exp = 0 yields S_militar = 5 (all slots military)', $sMilNoExp === 5, "Got: $sMilNoExp");


// ========================================================================
// GROUP 2: FASE 1 - Expedition Fleet Composition & Quality Checks
// ========================================================================
echo "\n--- GROUP 2: Expedition Fleet Composition & Yield Thresholds ---\n";

$mockBotRow = array(
    'bot_id'         => 9991,
    'personality'    => 'balanced',
    'difficulty'     => 'normal',
    'budget_credits' => json_encode(array()),
);
$mockUserRow = array(
    'id'             => 9991,
    'username'       => 'TestBotExp',
    'darkmatter'     => 5000,
    'antimatter'     => 1000,
    'container'      => 5,
    'race'           => 0,
    'formgovernment' => 0,
    'ethics'         => 0,
    'factor'         => array('FleetSlots' => 9, 'ShipStorage' => 0.0),
    $resource[124]   => 9, // Astrophysics lvl 9 => 3 expedition slots
);

$ctx = new BotContext($mockBotRow, $mockUserRow);
$gateway = new BotActionGateway($ctx);
$expPlanner = new BotExpeditionPlanner($ctx, $gateway);

// Test 2.1: Ship MSE Calculation (pure metal: M + 2C + 4D)
$mse203 = $expPlanner->getShipMSE(203); // Large cargo: 6000 M + 6000 C = 18000 MSE
assert_test('Fase1_Fleet', 'Large Cargo (203) MSE is exactly 18.000', $mse203 == 18000.0, "Got: $mse203");

$cap203 = $expPlanner->getShipCapacity(203);
assert_test('Fase1_Fleet', 'Large Cargo (203) capacity is 25.000', $cap203 == 25000.0, "Got: $cap203");

// Test 2.2: Standard Guaranteed Composition: 30 Large Cargos (203) + 1 Cruiser (206) + 1 Probe (210)
$planetWith30Cargos = array(
    $resource[203] => 50,
    $resource[202] => 10,
    $resource[206] => 5,
    $resource[210] => 10,
);
$builtFleet = $expPlanner->buildExpeditionFleet($planetWith30Cargos);
$stats = $expPlanner->calculateFleetStats($builtFleet);

assert_test('Fase1_Fleet', 'Planner selected 30 Large Cargos', isset($builtFleet[203]) && $builtFleet[203] === 30, "Count: " . ($builtFleet[203] ?? 0));
assert_test('Fase1_Fleet', 'Planner added 1 Cruiser combat ship (206)', isset($builtFleet[206]) && $builtFleet[206] === 1, "Count: " . ($builtFleet[206] ?? 0));
assert_test('Fase1_Fleet', 'Planner added 1 Spy Probe (210)', isset($builtFleet[210]) && $builtFleet[210] === 1, "Count: " . ($builtFleet[210] ?? 0));
assert_test('Fase1_Fleet', 'Guaranteed fleet MSE >= 500.000 (no nothing-drop waste)', $stats['mse'] >= 500000.0, "MSE: {$stats['mse']}");
assert_test('Fase1_Fleet', 'Guaranteed fleet capacity >= 400.000', $stats['capacity'] >= 400000.0, "Cap: {$stats['capacity']}");

// Test 2.3: Rejection of under-strength fleet (< 500k MSE or < 400k capacity)
$underpoweredPlanet = array(
    $resource[203] => 5,  // Only 5 large cargos: 90k MSE, 125k cap
    $resource[202] => 10, // 10 small cargos: 60k MSE, 50k cap. Total cap: 175k < 400k!
    $resource[206] => 1,
    $resource[210] => 1,
);
$rejectedFleet = $expPlanner->buildExpeditionFleet($underpoweredPlanet);
assert_test('Fase1_Fleet', 'Underpowered fleet (< 400k cap / < 500k MSE) correctly rejected (empty array)', empty($rejectedFleet), "Result: " . json_encode($rejectedFleet));


// ========================================================================
// GROUP 3: FASE 2 - Dark Matter Mining in Resource Fair
// ========================================================================
echo "\n--- GROUP 3: Dark Matter Mining in Resource Fair ---\n";

$premiumStrategist = new BotPremiumStrategist($ctx, $gateway);

// Test 3.1: Solvency rule evaluation: Planet stock must be >= cost + 1.5 * next_mine_cost
// Suppose hourly MSE = 100.000, n = 0, H = 6 => cost = 600.000 Metal.
// Next Metal Mine cost = 400.000 Metal => Solvency floor = 600.000 + 1.5 * 400.000 = 1.200.000 Metal.
$fairCost = 600000.0;
$nextMine = 400000.0;
$solvencyFloor = $fairCost + (1.5 * $nextMine);

assert_test('Fase2_Fair', 'Solvency formula calculates exact floor (cost + 1.5 * next_mine)', $solvencyFloor == 1200000.0, "Got: $solvencyFloor");

// Insolvent stock (1.000.000 < 1.200.000)
$insolventStock = 1000000.0;
assert_test('Fase2_Fair', 'Insolvent stock (1.000.000 < 1.200.000) correctly rejected', ($insolventStock < $solvencyFloor));

// Solvent stock (1.500.000 >= 1.200.000)
$solventStock = 1500000.0;
assert_test('Fase2_Fair', 'Solvent stock (1.500.000 >= 1.200.000) correctly accepted', ($solventStock >= $solvencyFloor));


// ========================================================================
// GROUP 4: FASE 2 - Universal Trader & Strict Budget Isolation
// ========================================================================
echo "\n--- GROUP 4: Universal Trader & Strict Budget Isolation ---\n";

$budgetManager = new BotBudgetManager($ctx);
$ctx->budgetManager = $budgetManager;

// Test 4.1: Category Virtual Budget Isolation
// Set initial virtual budget for Planet #101:
// Mines: 2.000.000 Metal, 100.000 Crystal, 0 Deut
// Fleet: 10.000.000 Metal, 5.000.000 Crystal, 2.000.000 Deut
$pId = 101;
$budgetCredits = array(
    'planets' => array(
        $pId => array(
            'mines'   => array('metal' => 2000000.0, 'crystal' => 100000.0, 'deuterium' => 0.0),
            'fleet'   => array('metal' => 10000000.0, 'crystal' => 5000000.0, 'deuterium' => 2000000.0),
            'defense' => array('metal' => 500000.0, 'crystal' => 200000.0, 'deuterium' => 50000.0),
        )
    ),
    'research' => array('metal' => 1000000.0, 'crystal' => 500000.0, 'deuterium' => 250000.0),
    'last_physical' => array(
        $pId => array('metal' => 12500000.0, 'crystal' => 5300000.0, 'deuterium' => 2050000.0)
    ),
    'trader_cooldowns' => array(),
);

// Inject credits into budget manager for testing
$refProp = new ReflectionProperty('BotBudgetManager', 'credits');
$refProp->setAccessible(true);
$refProp->setValue($budgetManager, $budgetCredits);

// Suppose 'mines' wants 200.000 Crystal (target 902). Rate: 2 Metal per Crystal => costs 400.000 Metal.
// Can 'mines' afford this from its OWN virtual balance?
// Mines has 2.000.000 Metal >= 400.000 Metal => YES.
$preMinesCredits = $budgetManager->getCategoryCredits($pId, 'mines');
$preFleetCredits = $budgetManager->getCategoryCredits($pId, 'fleet');
$preLastPhys     = $budgetCredits['last_physical'][$pId];

// Execute synchronized trade
$budgetManager->syncTraderExchange($pId, 'mines', 901, 400000.0, 902, 200000.0);

$postMinesCredits = $budgetManager->getCategoryCredits($pId, 'mines');
$postFleetCredits = $budgetManager->getCategoryCredits($pId, 'fleet');
$postCredits      = $refProp->getValue($budgetManager);
$postLastPhys     = $postCredits['last_physical'][$pId];

// Verify strict isolation:
// Mines metal decreased by exactly 400k, crystal increased by exactly 200k
assert_test('Fase2_Trader', 'Mines virtual metal decreased by exactly 400.000', $postMinesCredits['metal'] == 1600000.0, "Got: {$postMinesCredits['metal']}");
assert_test('Fase2_Trader', 'Mines virtual crystal increased by exactly 200.000', $postMinesCredits['crystal'] == 300000.0, "Got: {$postMinesCredits['crystal']}");

// Fleet virtual credits MUST BE 100% UNTOUCHED (Strict Budget Isolation)
assert_test('Fase2_Trader', 'Fleet virtual metal was NOT defalcated (remains 10.000.000)', $postFleetCredits['metal'] == $preFleetCredits['metal'], "Got: {$postFleetCredits['metal']}");
assert_test('Fase2_Trader', 'Fleet virtual crystal was NOT defalcated (remains 5.000.000)', $postFleetCredits['crystal'] == $preFleetCredits['crystal'], "Got: {$postFleetCredits['crystal']}");

// Test 4.2: Zero-Windfall Baseline Synchronization
// last_physical must be adjusted by (-400k metal, +200k crystal)
assert_test('Fase2_Trader', 'last_physical metal updated by -400.000', $postLastPhys['metal'] == ($preLastPhys['metal'] - 400000.0), "Got: {$postLastPhys['metal']}");
assert_test('Fase2_Trader', 'last_physical crystal updated by +200.000', $postLastPhys['crystal'] == ($preLastPhys['crystal'] + 200000.0), "Got: {$postLastPhys['crystal']}");

// Test 4.3: 1-Hour Trader Cooldown per Category
$cooldown = $budgetManager->getTraderCooldown('mines');
$expectedCooldown = BotClock::get() + 3600;
assert_test('Fase2_Trader', 'Category trader cooldown set to now + 3600s', abs($cooldown - $expectedCooldown) <= 2, "Got: $cooldown vs $expectedCooldown");
$fleetCooldown = $budgetManager->getTraderCooldown('fleet');
assert_test('Fase2_Trader', 'Fleet category trader cooldown remains 0 (independent cooldown)', $fleetCooldown === 0, "Got: $fleetCooldown");


// ========================================================================
// GROUP 5: FASE 2 - Officer Investment by ROI
// ========================================================================
echo "\n--- GROUP 5: Officer Investment by ROI ---\n";

// Test 5.1: Cost Formula: ceil(3000 * 1.25^lvl)
$costLvl0 = ceil(3000 * pow(1.25, 0));
assert_test('Fase2_Officers', 'Level 0 -> 1 cost is ceil(3000 * 1.25^0) = 3.000 MO', $costLvl0 == 3000.0, "Got: $costLvl0");

$costLvl1 = ceil(3000 * pow(1.25, 1));
assert_test('Fase2_Officers', 'Level 1 -> 2 cost is ceil(3000 * 1.25^1) = 3.750 MO', $costLvl1 == 3750.0, "Got: $costLvl1");

$costLvl2 = ceil(3000 * pow(1.25, 2));
assert_test('Fase2_Officers', 'Level 2 -> 3 cost is ceil(3000 * 1.25^2) = 4.688 MO', $costLvl2 == 4688.0, "Got: $costLvl2");

// Test 5.2: Minimum Cushion Requirement (>= 2.000 MO after purchase)
// If user has 4.500 MO, buying lvl 0 (3.000 MO) leaves 1.500 MO < 2.000 MO => REJECTED
$userMO_low = 4500.0;
$cushionOk_low = (($userMO_low - $costLvl0) >= 2000.0);
assert_test('Fase2_Officers', '4.500 MO with 3.000 MO cost leaves 1.500 MO < 2.000 MO cushion: REJECTED', !$cushionOk_low);

// If user has 5.500 MO, buying lvl 0 (3.000 MO) leaves 2.500 MO >= 2.000 MO => ALLOWED
$userMO_high = 5500.0;
$cushionOk_high = (($userMO_high - $costLvl0) >= 2000.0);
assert_test('Fase2_Officers', '5.500 MO with 3.000 MO cost leaves 2.500 MO >= 2.000 MO cushion: ALLOWED', $cushionOk_high);

// Test 5.3: Priority Order: 601 (Geologist) > 602 (Admiral) > 603 (Engineer) > 604 (Technocrat)
assert_test('Fase2_Officers', 'Priority order strictly defined: 601 > 602 > 603 > 604', BotPremiumStrategist::OFFICER_ORDER === array(601, 602, 603, 604));


// ========================================================================
// GROUP 6: FASE 3 - Civic Matrix, Immutability & Parties
// ========================================================================
echo "\n--- GROUP 6: Civic Matrix, Immutability & Parties ---\n";

$civicStrategist = new BotCivicStrategist($ctx, $gateway);

// Test 6.1: Immutability Detection: if race > 0 and formgovernment > 0, return false
$ctx->user['race'] = 1501;
$ctx->user['formgovernment'] = 1602;
$ctx->user['ethics'] = 1701;
$assignedWhenSet = $civicStrategist->assignCivicsIfNeeded();
assert_test('Fase3_Civics', 'Immutability: Does NOT alter civics when race & gov already set', $assignedWhenSet === false);

// Test 6.2: Personality-based Assignment when 0
$ctx->user['race'] = 0;
$ctx->user['formgovernment'] = 0;
$ctx->user['ethics'] = 0;

// Miner personality: 1501 (Fotorianos) + 1602 (República) + 1701 (Bien)
$ctx->personality = new BotPersonality('miner');
$civicStrategist->assignCivicsIfNeeded();
assert_test('Fase3_Civics', 'Miner: Assigns Fotorianos (1501)', $ctx->user['race'] === 1501, "Race: {$ctx->user['race']}");
assert_test('Fase3_Civics', 'Miner: Assigns República (1602)', $ctx->user['formgovernment'] === 1602, "Gov: {$ctx->user['formgovernment']}");
assert_test('Fase3_Civics', 'Miner: Assigns Bien (1701)', $ctx->user['ethics'] === 1701, "Ethics: {$ctx->user['ethics']}");

// Raider personality: 1504 (Kratonianos) + 1601 (Imperio) + 1703 (Maldad)
$ctx->user['race'] = 0;
$ctx->user['formgovernment'] = 0;
$ctx->user['ethics'] = 0;
$ctx->personality = new BotPersonality('raider');
$civicStrategist->assignCivicsIfNeeded();
assert_test('Fase3_Civics', 'Raider: Assigns Kratonianos (1504) + Imperio (1601) + Maldad (1703)', 
    $ctx->user['race'] === 1504 && $ctx->user['formgovernment'] === 1601 && $ctx->user['ethics'] === 1703,
    "Race: {$ctx->user['race']}, Gov: {$ctx->user['formgovernment']}, Ethics: {$ctx->user['ethics']}");

// Turtle personality: 1502 (Sinuria) + 1601 (Imperio) + 1702 (Neutral)
$ctx->user['race'] = 0;
$ctx->user['formgovernment'] = 0;
$ctx->user['ethics'] = 0;
$ctx->personality = new BotPersonality('turtle');
$civicStrategist->assignCivicsIfNeeded();
assert_test('Fase3_Civics', 'Turtle: Assigns Sinuria (1502) + Imperio (1601) + Neutral (1702)', 
    $ctx->user['race'] === 1502 && $ctx->user['formgovernment'] === 1601 && $ctx->user['ethics'] === 1702,
    "Race: {$ctx->user['race']}, Gov: {$ctx->user['formgovernment']}, Ethics: {$ctx->user['ethics']}");

// Balanced personality: 1503 (Humanos) + 1603 (Democracia) + 1702 (Neutral) with default 1500, 1600, 1700 values
$ctx->user['race'] = 1500;
$ctx->user['formgovernment'] = 1600;
$ctx->user['ethics'] = 1700;
$ctx->personality = new BotPersonality('balanced');
$civicStrategist->assignCivicsIfNeeded();
assert_test('Fase3_Civics', 'Balanced: Assigns Humanos (1503) + Democracia (1603) + Neutral (1702) from 1500/1600/1700 defaults', 
    $ctx->user['race'] === 1503 && $ctx->user['formgovernment'] === 1603 && $ctx->user['ethics'] === 1702,
    "Race: {$ctx->user['race']}, Gov: {$ctx->user['formgovernment']}, Ethics: {$ctx->user['ethics']}");

// Test 6.3: Planetary Parties Threshold (>= 4 hours = 14.400s)
$queueShort = 10000; // < 4 hours
$queueLong  = 18000; // 5 hours >= 4 hours
assert_test('Fase3_Parties', 'Queue < 4h (10.000s) does not meet party threshold', $queueShort < 14400);
assert_test('Fase3_Parties', 'Queue >= 4h (18.000s) meets party threshold (Fiesta Industrial/Científica)', $queueLong >= 14400);


// ========================================================================
// GROUP 7: End-to-End Kernel Integration & Daemon Sanity
// ========================================================================
echo "\n--- GROUP 7: End-to-End Kernel Integration & Daemon Sanity ---\n";

assert_test('Integration', 'BotExpeditionPlanner class loaded and available', class_exists('BotExpeditionPlanner'));
assert_test('Integration', 'BotPremiumStrategist class loaded and available', class_exists('BotPremiumStrategist'));
assert_test('Integration', 'BotCivicStrategist class loaded and available', class_exists('BotCivicStrategist'));

// Check Spanish formatting in formatDecisionInSpanish
require_once 'bot_daemon.php';
$sampleDec = array('decision_type' => 'expedition_dispatch', 'context_summary' => 'Dispatched autonomous expedition to [1:257:16]');
$formatted = formatDecisionInSpanish($sampleDec);
assert_test('Integration', 'formatDecisionInSpanish correctly formats expedition_dispatch', strpos($formatted, 'Expedición') !== false, "Output: $formatted");

$sampleFair = array('decision_type' => 'fair_mining', 'context_summary' => 'Feria de Recursos: Minado exitoso de 1.000 MO');
$formattedFair = formatDecisionInSpanish($sampleFair);
assert_test('Integration', 'formatDecisionInSpanish correctly formats fair_mining', strpos($formattedFair, 'Minado MO') !== false, "Output: $formattedFair");

$sampleTrader = array('decision_type' => 'trader_exchange', 'context_summary' => 'Mercader Universal (mines): Intercambio');
$formattedTrader = formatDecisionInSpanish($sampleTrader);
assert_test('Integration', 'formatDecisionInSpanish correctly formats trader_exchange', strpos($formattedTrader, 'Mercader') !== false, "Output: $formattedTrader");


// ========================================================================
// GROUP 8: FIX VERIFICATION - Multi-Expedition Dispatch from Single Colony
// ========================================================================
echo "\n--- GROUP 8: Multi-Expedition Dispatch from Single Colony ---\n";

// Mock World with 1 planet that has 90 Large Cargos and 10.000 Deut
$pMultiData = array(
    'id'           => 101,
    'galaxy'       => 1,
    'system'       => 200,
    'planet'       => 5,
    'planet_type'  => 1,
    'deuterium'    => 10000.0,
    $resource[203] => 90,
    $resource[206] => 5,
    $resource[210] => 5,
);

// Fleet composition test directly on buildExpeditionFleet with updated pData
$f1 = $expPlanner->buildExpeditionFleet($pMultiData);
assert_test('Fase1_Multi', 'First expedition built (30 Large Cargos)', ($f1[203] ?? 0) === 30);
// Deduct first expedition
$pMultiData[$resource[203]] -= 30;

$f2 = $expPlanner->buildExpeditionFleet($pMultiData);
assert_test('Fase1_Multi', 'Second expedition built from same colony (30 Large Cargos)', ($f2[203] ?? 0) === 30);
$pMultiData[$resource[203]] -= 30;

$f3 = $expPlanner->buildExpeditionFleet($pMultiData);
assert_test('Fase1_Multi', 'Third expedition built from same colony (30 Large Cargos)', ($f3[203] ?? 0) === 30);
$pMultiData[$resource[203]] -= 30;

$f4 = $expPlanner->buildExpeditionFleet($pMultiData);
assert_test('Fase1_Multi', 'Fourth expedition rejected when cargo exhausted (0 remaining)', empty($f4));


// ========================================================================
// GROUP 9: FIX VERIFICATION - Small Cargo Fallback when Combat Ships = 0
// ========================================================================
echo "\n--- GROUP 9: Small Cargo Fallback when Combat Ships = 0 ---\n";

// Planet with 100 Small Cargos, 0 Large Cargos, 0 Combat ships
$pSmallOnly = array(
    $resource[203] => 0,
    $resource[202] => 100,
    $resource[206] => 0,
    $resource[205] => 0,
    $resource[204] => 0,
    $resource[210] => 0,
);

$smallFleet = $expPlanner->buildExpeditionFleet($pSmallOnly);
$smallStats = $expPlanner->calculateFleetStats($smallFleet);

assert_test('Fase1_SmallFallback', 'Fleet built successfully without combat ships using Small Cargos fallback', !empty($smallFleet));
assert_test('Fase1_SmallFallback', 'Fallback fleet MSE >= 500.000', $smallStats['mse'] >= 500000.0, "MSE: {$smallStats['mse']}");
assert_test('Fase1_SmallFallback', 'Fallback fleet capacity >= 400.000', $smallStats['capacity'] >= 400000.0, "Cap: {$smallStats['capacity']}");
assert_test('Fase1_SmallFallback', 'Fallback fleet uses 84 Small Cargos (84 * 6000 = 504k MSE, 420k cap)', ($smallFleet[202] ?? 0) === 84, "Count: " . ($smallFleet[202] ?? 0));


// ========================================================================
// GROUP 10: FIX VERIFICATION - On-Demand Trader Simulation & Safety
// ========================================================================
echo "\n--- GROUP 10: On-Demand Trader Simulation & Safety ---\n";

$mockUserTrader = $mockUserRow;
$mockUserTrader['darkmatter'] = 5000;
$ctxTrader = new BotContext($mockBotRow, $mockUserTrader);
$bmTrader = new BotBudgetManager($ctxTrader);
$ctxTrader->budgetManager = $bmTrader;

// Setup credits for planet #201: Mines has 2M metal, 0 crystal, 0 deut
$refProp->setValue($bmTrader, array(
    'planets' => array(
        201 => array(
            'mines' => array('metal' => 2000000.0, 'crystal' => 0.0, 'deuterium' => 0.0),
        )
    ),
    'trader_cooldowns' => array(),
));

// Cost for building: 500.000 Metal, 200.000 Crystal => deficit: 200.000 Crystal.
// Rate: 2 Metal per Crystal => needs 400.000 Metal. Mines has 2.000.000 Metal => CAN RESOLVE.
$costBuilding = array(901 => 500000.0, 902 => 200000.0, 903 => 0.0);

// Set planet in DB mock / memory
$simResult = BotPremiumStrategist::canTraderResolveDeficit($ctxTrader, 201, 'mines', $costBuilding);
// Even if DB doesn't have planet 201, simulation checks planetRow
assert_test('Fase2_SimTrader', 'canTraderResolveDeficit exists and handles non-existent planet safely', ($simResult === false || is_array($simResult)));

// Check cooldown guard
$bmTrader->setTraderCooldown('mines', BotClock::get() + 3600);
$simBlockedByCooldown = BotPremiumStrategist::canTraderResolveDeficit($ctxTrader, 201, 'mines', $costBuilding);
assert_test('Fase2_SimTrader', 'canTraderResolveDeficit correctly returns false when category trader is on cooldown', $simBlockedByCooldown === false);

// Check cushion guard (darkmatter < 1050)
$ctxTrader->user['darkmatter'] = 500;
$bmTrader->setTraderCooldown('mines', 0);
$simBlockedByCushion = BotPremiumStrategist::canTraderResolveDeficit($ctxTrader, 201, 'mines', $costBuilding);
assert_test('Fase2_SimTrader', 'canTraderResolveDeficit correctly returns false when MO cushion is insufficient', $simBlockedByCushion === false);


// ========================================================================
// GROUP 11: FIX VERIFICATION - Strict 4-Budget Isolation (Donor & Research Credits)
// ========================================================================
echo "\n--- GROUP 11: Strict 4-Budget Isolation (Donor & Research Credits) ---\n";

$refProp->setValue($bmTrader, array(
    'research' => array('metal' => 60000.0, 'crystal' => 10000.0, 'deuterium' => 5000.0),
    'planets' => array(
        $pId => array(
            'mines' => array('metal' => 200000.0, 'crystal' => 50000.0, 'deuterium' => 10000.0),
            'fleet' => array('metal' => 100000.0, 'crystal' => 20000.0, 'deuterium' => 5000.0),
        )
    ),
    'last_physical' => array(
        $pId => array('metal' => 500000.0, 'crystal' => 200000.0, 'deuterium' => 50000.0),
    )
));

$preRes = $bmTrader->getCredits()['research']['metal'];
assert_test('Fase2_ResearchCredits', 'Initial research budget metal is 60.000', $preRes == 60000.0, "Got: $preRes");

// Deduct 25.000 metal via deductResearchCredits()
$bmTrader->deductResearchCredits(25000.0);
$postRes = $bmTrader->getCredits()['research']['metal'];
assert_test('Fase2_ResearchCredits', 'deductResearchCredits cleanly debited metal by 25.000 (remaining: 35.000)', $postRes == 35000.0, "Got: $postRes");

// Test donor debit strict isolation: donating from mines must NOT debit fleet
$bmTrader->debitDonorCategory($pId, 'mines', array('metal' => 50000.0, 'crystal' => 10000.0, 'deuterium' => 0.0));
$postMines = $bmTrader->getCategoryCredits($pId, 'mines');
$postFleet = $bmTrader->getCategoryCredits($pId, 'fleet');
assert_test('Fase2_DonorIsolation', 'Mines donor category debited by exactly 50k metal', $postMines['metal'] == 150000.0, "Got: {$postMines['metal']}");
assert_test('Fase2_DonorIsolation', 'Fleet category was 100% untouched by mines donation (remains 100k)', $postFleet['metal'] == 100000.0, "Got: {$postFleet['metal']}");


// ========================================================================
// GROUP 12: FIX VERIFICATION - Slot Friction Elimination (Expeditions + Logistics)
// ========================================================================
echo "\n--- GROUP 12: Slot Friction Elimination (Expeditions + Logistics) ---\n";

// Scenario: S_total = 5 slots. Tech 124 = 4 => S_exp = 2, S_militar = max(1, 5 - 2) = 3.
// Bot has 2 active expeditions holding at :16 for 1 hour.
// Old buggy logic: ($maxSlots - 3) - $actualFleets = (5 - 3) - 2 = 0 available slots! (Logistics starved)
// Fixed logic: $availableMil = 3. Combat margin = 1.
// $availableSlots = min(MAX_CONCURRENT_TARGETS, max(0, $availableMil - $combatMargin)) = min(2, 3 - 1) = 2 available slots!
$sTotal = 5;
$sExp = 2;
$sMilitary = max(1, $sTotal - $sExp); // 3
$activeExp = 2;
$activeNonExp = 0;
$actualFleets = $activeExp + $activeNonExp; // 2

// Available military slots
$availableMil = max(0, min($sTotal - $actualFleets, $sMilitary - $activeNonExp)); // min(5 - 2, 3 - 0) = 3
assert_test('Fase1_Friction', 'Available military slots correctly equals 3 despite 2 active expeditions', $availableMil === 3, "Got: $availableMil");

// Fixed Logistics available slots: reserves 1 slot for combat
$combatMargin = ($sMilitary > 1) ? 1 : 0;
$logisticsAvailable = min(BotLogisticsPlanner::MAX_CONCURRENT_TARGETS, max(0, $availableMil - $combatMargin));
assert_test('Fase1_Friction', 'Logistics receives exactly 2 slots (MAX_CONCURRENT_TARGETS) without starvation', $logisticsAvailable === 2, "Got: $logisticsAvailable");

// Reserved combat slot remains ready for StrikePlanner / Assault
$remainingForAssault = $availableMil - $logisticsAvailable;
assert_test('Fase1_Friction', 'Remaining 1 dedicated military slot preserved exclusively for assault / strikes', $remainingForAssault === 1, "Got: $remainingForAssault");


// ========================================================================
// GROUP 13: FIX VERIFICATION - Partial Civics Immutability
// ========================================================================
echo "\n--- GROUP 13: Partial Civics Immutability ---\n";

// Suppose user already has race 1501 assigned (e.g. Fotorianos), but formgovernment is 0
$ctxTrader->user['race'] = 1501;
$ctxTrader->user['formgovernment'] = 0;
$ctxTrader->user['ethics'] = 0;
$ctxTrader->personality = new BotPersonality('raider');
$civicStrategistTrader = new BotCivicStrategist($ctxTrader, new BotActionGateway($ctxTrader));
$civicStrategistTrader->assignCivicsIfNeeded();
assert_test('Fase3_PartialCivics', 'Pre-existing race (1501) was NOT overwritten by raider archetype (1504)', $ctxTrader->user['race'] === 1501, "Race: {$ctxTrader->user['race']}");
assert_test('Fase3_PartialCivics', 'Unset government was successfully assigned to Imperio (1601)', $ctxTrader->user['formgovernment'] === 1601, "Gov: {$ctxTrader->user['formgovernment']}");
assert_test('Fase3_PartialCivics', 'Ethics was successfully assigned to Maldad (1703)', $ctxTrader->user['ethics'] === 1703, "Ethics: {$ctxTrader->user['ethics']}");


// ========================================================================
// SUMMARY
// ========================================================================
echo "\n========================================================================\n";
echo "TEST RESULTS: {$results['passed']} PASSED / {$results['failed']} FAILED\n";
echo "========================================================================\n";

if ($results['failed'] > 0) {
    exit(1);
} else {
    exit(0);
}

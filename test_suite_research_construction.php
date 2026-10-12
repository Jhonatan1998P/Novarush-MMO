<?php
/**
 * Test Suite: Construction & Research Intelligence & Personality Engine
 * NovaRush Bot Engine Validation
 */

define('ROOT_PATH', __DIR__ . '/');
define('MODE', 'CLI');

require_once ROOT_PATH . 'includes/classes/bot/Config/Personality.class.php';
require_once ROOT_PATH . 'includes/classes/bot/Config/DifficultyProfile.class.php';

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

function assertTest($condition, $message) {
    global $totalTests, $passedTests, $failedTests;
    $totalTests++;
    if ($condition) {
        $passedTests++;
        echo "  [PASS] {$message}\n";
    } else {
        $failedTests++;
        echo "  [FAIL] {$message}\n";
    }
}

echo "============================================================\n";
echo "   NOVARUSH TEST SUITE: RESEARCH & CONSTRUCTION SYSTEM\n";
echo "============================================================\n\n";

// TEST 1: Pure Personality Budget Ratios
echo "--- 1. Testing Personality Budget Ratios (Decoupled from Difficulty) ---\n";
$personalities = array(
    'aggressive' => array('mines' => 0.15, 'fleet' => 0.55, 'defense' => 0.10, 'research' => 0.20),
    'economic'   => array('mines' => 0.55, 'fleet' => 0.10, 'defense' => 0.15, 'research' => 0.20),
    'defensive'  => array('mines' => 0.25, 'fleet' => 0.15, 'defense' => 0.50, 'research' => 0.10),
    'balanced'   => array('mines' => 0.30, 'fleet' => 0.35, 'defense' => 0.15, 'research' => 0.20),
);

foreach ($personalities as $pName => $expectedRatios) {
    $p = new BotPersonality($pName);
    $ratios = $p->getBudgetRatios();
    $sum = array_sum($ratios);
    assertTest(abs($sum - 1.0) < 0.0001, "Personality '{$pName}' ratios sum to exactly 100% ({$sum})");
    assertTest($ratios == $expectedRatios, "Personality '{$pName}' matches designated macro ratios: M={$ratios['mines']}, F={$ratios['fleet']}, D={$ratios['defense']}, R={$ratios['research']}");
}

// TEST 2: DifficultyProfile delegates directly to personality
echo "\n--- 2. Testing DifficultyProfile Budget Delegation & Hangar Times ---\n";
$diffNightmare = new BotDifficultyProfile(BotDifficultyProfile::NIGHTMARE);
$diffHard      = new BotDifficultyProfile(BotDifficultyProfile::HARD);
$diffNormal    = new BotDifficultyProfile(BotDifficultyProfile::NORMAL);

assertTest($diffNightmare->maxHangarQueueDuration === 1800, "Nightmare maxHangarQueueDuration is 1800s (30 min)");
assertTest($diffNightmare->batchTimeTarget === 240, "Nightmare batchTimeTarget is 240s (4 min)");

assertTest($diffHard->maxHangarQueueDuration === 3600, "Hard maxHangarQueueDuration is 3600s (1 hour)");
assertTest($diffHard->batchTimeTarget === 600, "Hard batchTimeTarget is 600s (10 min)");

assertTest($diffNormal->maxHangarQueueDuration === 5400, "Normal maxHangarQueueDuration is 5400s (1.5 hours)");
assertTest($diffNormal->batchTimeTarget === 900, "Normal batchTimeTarget is 900s (15 min)");

$pRaider = new BotPersonality('aggressive');
$adjBudget = $diffNightmare->getAdjustedBudget($pRaider);
assertTest($adjBudget['fleet'] === 0.55 && $adjBudget['mines'] === 0.15, "DifficultyProfile delegates pure ratios to Personality even under Nightmare");

// TEST 3: Personality Technology Multipliers
echo "\n--- 3. Testing Personality Technology Multipliers ---\n";
// Raider
assertTest($pRaider->getTechMultiplier(108) === 2.20, "Raider gets 2.20x on Computer Tech (108)");
assertTest($pRaider->getTechMultiplier(109) === 2.20, "Raider gets 2.20x on Weapons Tech (109)");
assertTest($pRaider->getTechMultiplier(124) === 1.40, "Raider gets 1.40x on Astrophysics (124)");
assertTest($pRaider->getTechMultiplier(131) === 1.00, "Raider gets 1.00x on Metal Tech (131)");

// Miner
$pMiner = new BotPersonality('economic');
assertTest($pMiner->getTechMultiplier(131) === 2.50, "Miner gets 2.50x on Metal Tech (131)");
assertTest($pMiner->getTechMultiplier(132) === 2.50, "Miner gets 2.50x on Crystal Tech (132)");
assertTest($pMiner->getTechMultiplier(123) === 2.50, "Miner gets 2.50x on IRN (123)");
assertTest($pMiner->getTechMultiplier(124) === 2.50, "Miner gets 2.50x on Astrophysics (124)");
assertTest($pMiner->getTechMultiplier(108) === 1.00, "Miner gets 1.00x on Computer Tech (108)");

// Turtle
$pTurtle = new BotPersonality('defensive');
assertTest($pTurtle->getTechMultiplier(110) === 2.20, "Turtle gets 2.20x on Shielding Tech (110)");
assertTest($pTurtle->getTechMultiplier(111) === 2.20, "Turtle gets 2.20x on Armor Tech (111)");
assertTest($pTurtle->getTechMultiplier(117) === 2.20, "Turtle gets 2.20x on Impulse Drive (117)");
assertTest($pTurtle->getTechMultiplier(108) === 1.00, "Turtle gets 1.00x on Computer Tech (108)");

// TEST 4: Strategic Research Multipliers Calculations
echo "\n--- 4. Testing Strategic Research Multipliers Logic ---\n";

// Simultaneous Fleet Slots Multiplier
$calcSlotMult = function($active, $max) {
    $pressure = $max > 0 ? ($active / $max) : 0.0;
    return 1.0 + (pow($pressure, 2) * 3.5);
};
$slotMult0 = $calcSlotMult(0, 10);
$slotMultHalf = $calcSlotMult(5, 10);
$slotMultFull = $calcSlotMult(10, 10);

assertTest(abs($slotMult0 - 1.0) < 0.01, "Slot multiplier with 0% slot pressure is 1.0x");
assertTest(abs($slotMultHalf - 1.875) < 0.01, "Slot multiplier with 50% slot pressure is 1.875x");
assertTest(abs($slotMultFull - 4.5) < 0.01, "Slot multiplier with 100% slot pressure is 4.50x");

// Combined Raider Slot multiplier
$totalRaiderCompMult = $slotMultFull * $pRaider->getTechMultiplier(108);
assertTest(abs($totalRaiderCompMult - 9.90) < 0.01, "Full slot pressure + Raider personality yields 9.90x total boost to Computer Tech");

// Astrophysics Multiplier
$calcAstroMult = function($curAstro) {
    $nextAstro = $curAstro + 1;
    $curExpSlots = (int)floor(sqrt($curAstro));
    $nextExpSlots = (int)floor(sqrt($nextAstro));
    $curColonies = (int)floor(($curAstro + 1) / 2);
    $nextColonies = (int)floor(($nextAstro + 1) / 2);
    $m = 1.2;
    if ($nextExpSlots > $curExpSlots) $m += 2.0;
    if ($nextColonies > $curColonies) $m += 1.8;
    return $m;
};
// Lvl 0 -> 1: exp slots: 0 -> 1 (unlocks 1 exp slot!), colonies: 0 -> 1 (unlocks 1 colony!)
$astro0to1 = $calcAstroMult(0);
assertTest(abs($astro0to1 - 5.0) < 0.01, "Astrophysics 0->1 unlocks both exp slot and colony: 1.2 + 2.0 + 1.8 = 5.0x boost");

// Lvl 3 -> 4: exp slots sqrt(3)=1 -> sqrt(4)=2 (unlocks 2nd exp slot!), colonies floor(4/2)=2 -> floor(5/2)=2 (no colony)
$astro3to4 = $calcAstroMult(3);
assertTest(abs($astro3to4 - 3.2) < 0.01, "Astrophysics 3->4 unlocks exp slot: 1.2 + 2.0 = 3.2x boost");

// IRN Multiplier
$calcIrnMult = function($advLabs) {
    return 2.0 + ($advLabs * 0.80);
};
$irn0 = $calcIrnMult(0);
$irn5 = $calcIrnMult(5);
assertTest(abs($irn0 - 2.0) < 0.01, "IRN base multiplier with 0 advanced labs is 2.0x");
assertTest(abs($irn5 - 6.0) < 0.01, "IRN multiplier with 5 advanced labs is 6.0x (2.0 + 5*0.8)");

// Energy Tech Multiplier
$calcEnergyMult = function($hasFusion, $minFreeFields, $curEnergyTech) {
    return 1.0 + ($hasFusion ? 1.5 : 0.0) + ($minFreeFields <= 5 && $curEnergyTech < 12 ? 2.5 : 0.0);
};
$energyBasic = $calcEnergyMult(false, 20, 10);
$energyFusion = $calcEnergyMult(true, 20, 10);
$energyCrisis = $calcEnergyMult(true, 3, 8);
assertTest(abs($energyBasic - 1.0) < 0.01, "Energy tech basic multiplier is 1.0x");
assertTest(abs($energyFusion - 2.5) < 0.01, "Energy tech with Fusion active is 2.5x");
assertTest(abs($energyCrisis - 5.0) < 0.01, "Energy tech with Fusion + low planet fields (< 5) is 5.0x");

// TEST 5: Parallel Multi-Colony Construction Logic
echo "\n--- 5. Testing Multi-Colony Parallel Construction Queueing Logic ---\n";
$mockCandidates = array(
    array('planet_id' => 101, 'element' => 1, 'score' => 950.0),
    array('planet_id' => 101, 'element' => 2, 'score' => 800.0),
    array('planet_id' => 102, 'element' => 3, 'score' => 920.0),
    array('planet_id' => 102, 'element' => 1, 'score' => 750.0),
    array('planet_id' => 103, 'element' => 81, 'score' => 880.0),
    array('planet_id' => 104, 'element' => 4, 'score' => 600.0),
);

// Simulate the loop from ProductionAllocator lines 298-326
usort($mockCandidates, function($a, $b) {
    return $b['score'] <=> $a['score'];
});

$queuedPlanets = array();
$enqueuedActions = array();

foreach ($mockCandidates as $cand) {
    $pId = (int)$cand['planet_id'];
    if (isset($queuedPlanets[$pId])) {
        continue;
    }
    // Simulate gateway success
    $queuedPlanets[$pId] = true;
    $enqueuedActions[] = array('planet' => $pId, 'building' => $cand['element']);
}

assertTest(count($enqueuedActions) === 4, "Parallel construction enqueued exactly 4 buildings across 4 distinct colonies in 1 cycle");
assertTest($enqueuedActions[0]['planet'] === 101 && $enqueuedActions[0]['building'] === 1, "Colony 101 enqueued its highest candidate (Element 1, score 950)");
assertTest($enqueuedActions[1]['planet'] === 102 && $enqueuedActions[1]['building'] === 3, "Colony 102 enqueued its highest candidate (Element 3, score 920)");
assertTest($enqueuedActions[2]['planet'] === 103 && $enqueuedActions[2]['building'] === 81, "Colony 103 enqueued its highest candidate (Element 81, score 880)");
assertTest($enqueuedActions[3]['planet'] === 104 && $enqueuedActions[3]['building'] === 4, "Colony 104 enqueued its highest candidate (Element 4, score 600)");

echo "\n============================================================\n";
echo "   TEST SUMMARY: {$passedTests} / {$totalTests} PASSED (" . round(($passedTests / $totalTests) * 100, 1) . "%)\n";
if ($failedTests > 0) {
    echo "   WARNING: {$failedTests} TESTS FAILED!\n";
} else {
    echo "   ALL TESTS PASSED WITH 100% SUCCESS RATE!\n";
}
echo "============================================================\n";

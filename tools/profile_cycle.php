<?php
define('MODE', 'CLI');
define('ROOT_PATH', 'C:/Users/Ortega/Downloads/OGAME/ogame/');
chdir(ROOT_PATH);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
require_once 'includes/common.php';
require_once 'includes/vars/General.php';
foreach (get_defined_vars() as $k => $v) {
    $GLOBALS[$k] = $v;
}
require_once 'includes/classes/bot/BotEngine.class.php';
require_once 'includes/classes/bot/Kernel/BotClock.class.php';

$pdo = Database::get()->getHandle();
$pdo->beginTransaction();

try {
    $simTime = time() + 180;
    BotClock::set($simTime);
    $botRow = Database::get()->selectSingle("SELECT * FROM %%BOTS%% WHERE bot_id = 1011;");

    $t0 = microtime(true);
    require 'includes/FleetHandler.php';
    $tFleet = microtime(true) - $t0;

    $t1 = microtime(true);
    $ctx = new BotContext($botRow, Database::get()->selectSingle("SELECT * FROM %%USERS%% WHERE id = 1011"));
    $ctx->budgetManager = new BotBudgetManager($ctx);
    $tCtx = microtime(true) - $t1;

    $t2 = microtime(true);
    $world = new BotWorldModel($ctx);
    $ctx->budgetManager->updateCredits($world->empire);
    $tWorld = microtime(true) - $t2;

    $t3 = microtime(true);
    $gateway = new BotActionGateway($ctx);
    $ctx->budgetManager->processSavingsLock($world->empire, $gateway);
    $tSavings = microtime(true) - $t3;

    $t4 = microtime(true);
    $allocator = new BotProductionAllocator($ctx, $gateway);
    $builtMines = $allocator->allocate($world->empire);
    $researched = $allocator->allocateResearch($world->empire);
    $tAlloc = microtime(true) - $t4;

    $t5 = microtime(true);
    $forcePlanner = new BotForcePlanner($ctx, $gateway);
    $recruitedUnits = $forcePlanner->planForces($world->empire, false, $world);
    $tForce = microtime(true) - $t5;

    $t6 = microtime(true);
    $stagingPlanner = new BotStagingPlanner($ctx, $gateway);
    $hubId = $stagingPlanner->getBestStagingBase($world->empire);
    $logistics = new BotLogisticsPlanner($ctx, $gateway);
    $convoys = $logistics->dispatchConvoys($world->empire, $hubId, $world);
    $tLogistics = microtime(true) - $t6;

    $t7 = microtime(true);
    $targetFinder = new BotTargetFinder($ctx);
    $baseForOps = $world->empire->getPlanet($hubId) ?: reset($world->empire->getPlanets());
    $targets = $targetFinder->findTargets($world->empire, 16, $world);
    $tTargets = microtime(true) - $t7;

    $t8 = microtime(true);
    $achManager = new BotAchievementManager($ctx);
    $achManager->sync($world->empire);
    $tAch = microtime(true) - $t8;

    $total = microtime(true) - $t0;

    echo "Profile of 1 cycle ({$total}s total):\n";
    echo "  - FleetHandler:     " . round($tFleet, 4) . "s\n";
    echo "  - Context & Budget: " . round($tCtx, 4) . "s\n";
    echo "  - WorldModel & Res: " . round($tWorld, 4) . "s\n";
    echo "  - Savings Lock:     " . round($tSavings, 4) . "s\n";
    echo "  - Allocator (Mines):" . round($tAlloc, 4) . "s\n";
    echo "  - ForcePlanner:     " . round($tForce, 4) . "s\n";
    echo "  - Logistics:        " . round($tLogistics, 4) . "s\n";
    echo "  - TargetFinder:     " . round($tTargets, 4) . "s\n";
    echo "  - Achievements:     " . round($tAch, 4) . "s\n";

} finally {
    $pdo->rollBack();
    BotClock::reset();
}

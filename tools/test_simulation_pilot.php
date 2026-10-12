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
    $simStart = time();
    $t0 = microtime(true);
    $cycles = 30;
    echo "Benchmarking {$cycles} cycles with delta=180s...\n";
    for ($i = 1; $i <= $cycles; $i++) {
        $simTime = $simStart + ($i * 180);
        BotClock::set($simTime);
        require 'includes/FleetHandler.php';
        $botRow = Database::get()->selectSingle("SELECT * FROM %%BOTS%% WHERE bot_id = 1011;");
        $res = BotKernel::processTurn($botRow);
    }
    $elapsed = microtime(true) - $t0;
    $simulatedHours = ($cycles * 180) / 3600;
    echo "Completed {$cycles} cycles ({$simulatedHours}h simulated) in " . round($elapsed, 2) . "s! (" . round($cycles / $elapsed, 2) . " cycles/sec)\n";
    echo "Pilot run SUCCESS!\n";
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
} finally {
    $pdo->rollBack();
    BotClock::reset();
    echo "Rolled back pilot run.\n";
}

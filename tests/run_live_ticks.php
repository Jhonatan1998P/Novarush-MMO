<?php

define('MODE', 'CRON');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)).'/');
set_include_path(ROOT_PATH);

require 'includes/common.php';
require 'includes/vars/General.php';
foreach (get_defined_vars() as $k => $v) {
    $GLOBALS[$k] = $v;
}

require_once 'includes/classes/bot/BotEngine.class.php';

echo "========================================================================\n";
echo "   LIVE TICK VERIFICATION: BOTS 1007, 1010, 1011\n";
echo "========================================================================\n\n";

$targetBots = array(1007, 1010, 1011);
$db = Database::get();

foreach ($targetBots as $bId) {
    echo ">>> Executing live tick for Bot #{$bId}...\n";
    $botRow = $db->selectSingle("SELECT * FROM " . DB_PREFIX . "bots WHERE bot_id = :id;", array(':id' => $bId));
    if (empty($botRow)) {
        echo "   [WARNING] Bot #{$bId} not found in database!\n";
        continue;
    }
    
    echo "   Bot name: " . ($db->selectSingle("SELECT username FROM %%USERS%% WHERE id = :id;", array(':id' => $bId), 'username')) . "\n";
    echo "   Personality: {$botRow['personality']}, Difficulty: {$botRow['difficulty']}\n";
    
    $startTime = microtime(true);
    try {
        $res = BotEngine::runSingleBot($bId);
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        echo "   [SUCCESS] Live tick completed in {$duration}ms\n";
        echo "   Result: " . json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";
    } catch (Throwable $e) {
        echo "   [FATAL ERROR] " . $e->getMessage() . "\n";
        echo "   Trace: " . $e->getTraceAsString() . "\n\n";
        exit(1);
    }
}

echo ">>> All live bot ticks completed with 0 errors! <<<\n";

<?php

define('MODE', 'INSTALL');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
chdir(ROOT_PATH);

require 'includes/common.php';

$db = Database::get();

$allMsgs = $db->select("SELECT message_id, message_owner, message_sender, message_from, message_type, message_subject, message_time FROM %%MESSAGES%% ORDER BY message_id DESC;");

echo "Total mensajes en la base de datos: " . count($allMsgs) . "\n\n";

$testCount = 0;
$realCount = 0;

foreach ($allMsgs as $m) {
    $isTest = false;
    if ($m['message_type'] == 50 
        || $m['message_sender'] == 999 
        || stripos($m['message_from'], 'Ancestral') !== false 
        || stripos($m['message_subject'], 'Fortaleza') !== false
        || stripos($m['message_subject'], 'ESTOCADA') !== false
        || stripos($m['message_subject'], 'EVENTO FINALIZADO') !== false
        || stripos($m['message_subject'], 'VICTORIA: RECOMPENSA') !== false) {
        $isTest = true;
        $testCount++;
        echo "[TEST] ID: {$m['message_id']} | To: {$m['message_owner']} | From: {$m['message_from']} ({$m['message_sender']}) | Type: {$m['message_type']} | Subj: {$m['message_subject']}\n";
    } else {
        $realCount++;
        echo "[REAL] ID: {$m['message_id']} | To: {$m['message_owner']} | From: {$m['message_from']} ({$m['message_sender']}) | Type: {$m['message_type']} | Subj: {$m['message_subject']}\n";
    }
}

echo "\nResumen:\n";
echo "Mensajes de Test: $testCount\n";
echo "Mensajes Legítimos: $realCount\n";

<?php

define('MODE', 'CRON');
define('ROOT_PATH', './');
require_once 'includes/common.php';
require_once 'includes/vars/General.php';
require_once 'includes/classes/bot/Evaluation/EconomyValuator.class.php';

$db = Database::get();
$b = $db->selectSingle("SELECT u.id, u.username, b.* FROM %%USERS%% u INNER JOIN " . DB_PREFIX . "bots b ON u.id = b.bot_id WHERE u.username = 'Kimimaru';");

echo "ID: {$b['id']}\n";
echo "Personality: {$b['personality']}\n";
echo "Difficulty: {$b['difficulty']}\n";
echo "Doctrine: {$b['doctrine']}\n";
echo "Last defense recruit: {$b['last_defense_recruit']} (" . date('Y-m-d H:i:s', $b['last_defense_recruit']) . ")\n";
echo "Last fleet recruit: {$b['last_fleet_recruit']} (" . date('Y-m-d H:i:s', $b['last_fleet_recruit']) . ")\n\n";

$creds = json_decode($b['budget_credits'], true);
$defCreditsTotal = 0;
foreach (($creds['planets'] ?? []) as $pid => $c) {
    $dMse = ($c['defense']['metal'] ?? 0) + 2*($c['defense']['crystal'] ?? 0) + 4*($c['defense']['deuterium'] ?? 0);
    $defCreditsTotal += $dMse;
}
echo "Créditos Defensa acumulados en buffer: " . number_format($defCreditsTotal) . " MSE\n\n";

// Chequear las defensas actuales de cada planeta de Kimimaru
$planets = $db->select("SELECT id, name, galaxy, system, planet, b_hangar_id FROM %%PLANETS%% WHERE id_owner = :uid AND destruyed = 0;", array(':uid' => $b['id']));

global $reslist, $resource;
echo "=== TECHO DEFENSIVO VS DEFENSAS EXISTENTES ===\n";
foreach ($planets as $p) {
    $pFull = $db->selectSingle("SELECT * FROM %%PLANETS%% WHERE id = :id;", array(':id' => $p['id']));
    $hourlyMSE = (float)($pFull['metal_perhour'] ?? 0) + 2.0 * (float)($pFull['crystal_perhour'] ?? 0) + 4.0 * (float)($pFull['deuterium_perhour'] ?? 0);
    $dailyMSE  = $hourlyMSE * 24.0;
    
    $totalMSE = 0.0;
    foreach ($reslist['defense'] as $defId) {
        $key = $resource[$defId] ?? '';
        if (!empty($key) && isset($pFull[$key]) && (int)$pFull[$key] > 0) {
            $count = (int)$pFull[$key];
            $costMSE = BotEconomyValuator::getElementPriceMSE($defId);
            $totalMSE += ($count * $costMSE);
        }
    }
    $ratio = ($dailyMSE > 0) ? ($totalMSE / $dailyMSE) : 0;
    echo sprintf("• %s: Def MSE = %s | Daily MSE = %s | Ratio = %.2fx (Techo = 1.0x o 3.0x)\n",
        $p['name'], number_format($totalMSE), number_format($dailyMSE), $ratio
    );
}

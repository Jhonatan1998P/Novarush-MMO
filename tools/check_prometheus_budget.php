<?php

define('MODE', 'CRON');
define('ROOT_PATH', './');
require_once 'includes/common.php';
require_once 'includes/vars/General.php';

$db = Database::get();
$bot = $db->selectSingle("SELECT u.id, u.username, b.* FROM %%USERS%% u INNER JOIN " . DB_PREFIX . "bots b ON u.id = b.bot_id WHERE u.username = 'PROMETHEUS';");

echo "=== PROMETHEUS BOT STATE ===\n";
echo "ID: {$bot['id']}\n";
echo "Personality: {$bot['personality']}\n";
echo "Difficulty: {$bot['difficulty']}\n\n";

$planets = $db->select("SELECT id, name, galaxy, system, planet, metal, crystal, deuterium, metal_perhour, crystal_perhour, deuterium_perhour, metal_max, crystal_max, deuterium_max FROM %%PLANETS%% WHERE id_owner = :uid AND destruyed = 0 ORDER BY id ASC;", array(':uid' => $bot['id']));

$totalProdM = 0; $totalProdC = 0; $totalProdD = 0;
$totalStockM = 0; $totalStockC = 0; $totalStockD = 0;

echo "=== COLONIAS (STOCK ACTUAL Y PRODUCCION/HORA) ===\n";
foreach ($planets as $p) {
    $totalStockM += $p['metal'];
    $totalStockC += $p['crystal'];
    $totalStockD += $p['deuterium'];

    $totalProdM += $p['metal_perhour'];
    $totalProdC += $p['crystal_perhour'];
    $totalProdD += $p['deuterium_perhour'];

    $mseProd = $p['metal_perhour'] + (2 * $p['crystal_perhour']) + (4 * $p['deuterium_perhour']);
    $mseStock = $p['metal'] + (2 * $p['crystal']) + (4 * $p['deuterium']);

    echo sprintf("• %s [%d:%d:%d]: Stock MSE = %s | Prod/h MSE = %s\n",
        $p['name'], $p['galaxy'], $p['system'], $p['planet'],
        number_format($mseStock), number_format($mseProd)
    );
}

$empireMseProd = $totalProdM + (2 * $totalProdC) + (4 * $totalProdD);
$empireMseStock = $totalStockM + (2 * $totalStockC) + (4 * $totalStockD);

echo "\n=== TOTALES DEL IMPERIO ===\n";
echo "Stock actual Total MSE: " . number_format($empireMseStock) . "\n";
echo "Producción total horaria Metal: " . number_format($totalProdM) . "\n";
echo "Producción total horaria Cristal: " . number_format($totalProdC) . "\n";
echo "Producción total horaria Deuterio: " . number_format($totalProdD) . "\n";
echo "Producción total horaria MSE: " . number_format($empireMseProd) . " MSE/h\n";

$creds = json_decode($bot['budget_credits'], true);
echo "\n=== BUDGET CREDITS JSON ===\n";
if (!empty($creds['planets'])) {
    $fleetCredsTot = 0;
    $defCredsTot = 0;
    $minesCredsTot = 0;
    foreach ($creds['planets'] as $pid => $c) {
        $fMse = ($c['fleet']['metal'] ?? 0) + 2*($c['fleet']['crystal'] ?? 0) + 4*($c['fleet']['deuterium'] ?? 0);
        $dMse = ($c['defense']['metal'] ?? 0) + 2*($c['defense']['crystal'] ?? 0) + 4*($c['defense']['deuterium'] ?? 0);
        $mMse = ($c['mines']['metal'] ?? 0) + 2*($c['mines']['crystal'] ?? 0) + 4*($c['mines']['deuterium'] ?? 0);
        $fleetCredsTot += $fMse;
        $defCredsTot += $dMse;
        $minesCredsTot += $mMse;
    }
    echo "Créditos Flota Totales en buffer: " . number_format($fleetCredsTot) . " MSE\n";
    echo "Créditos Defensa Totales en buffer: " . number_format($defCredsTot) . " MSE\n";
    echo "Créditos Minas Totales en buffer: " . number_format($minesCredsTot) . " MSE\n";
}
if (!empty($creds['research'])) {
    $rMse = ($creds['research']['metal'] ?? 0) + 2*($creds['research']['crystal'] ?? 0) + 4*($creds['research']['deuterium'] ?? 0);
    echo "Créditos Research en buffer: " . number_format($rMse) . " MSE\n";
}
if (!empty($creds['opportunity_fund'])) {
    $oppMse = ($creds['opportunity_fund']['metal'] ?? 0) + 2*($creds['opportunity_fund']['crystal'] ?? 0) + 4*($creds['opportunity_fund']['deuterium'] ?? 0);
    echo "Fondo de Oportunidad: " . number_format($oppMse) . " MSE\n";
}

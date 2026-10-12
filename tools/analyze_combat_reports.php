<?php
define('MODE', 'CRON');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
set_include_path(ROOT_PATH);

require 'includes/common.php';
require 'includes/vars/General.php';

global $resource;
$db = Database::get();

$reports = $db->select("SELECT * FROM " . DB_PREFIX . "raports WHERE rid IN ('c956daf9236f01c4f4b11affa2b85b14', '19b4bad711f61560fb8bb97b83ed9a1a') ORDER BY time ASC;");

foreach ($reports as $idx => $r) {
    $data = unserialize($r['raport']);
    echo "====================================================================\n";
    echo "BATALLA #" . ($idx + 1) . " — RID: {$r['rid']}\n";
    $attackerName = ($r['attacker'] == 1010) ? 'Kimimaru' : (($r['attacker'] == 1011) ? 'PROMETHEUS' : 'ID ' . $r['attacker']);
    echo "ATACANTE: {$attackerName} (ID {$r['attacker']}) vs DEFENSOR: GODWAR (ID 4)\n";
    echo "FECHA/HORA: " . date('Y-m-d H:i:s', $r['time']) . "\n";
    echo "RESULTADO: Victoria Defensor en " . count($data['rounds']) . " rondas\n";
    echo "====================================================================\n";

    $r0 = $data['rounds'][0];
    $rEnd = end($data['rounds']);

    echo "1. FLOTA ATACANTE:\n";
    $totalAttShips = 0;
    foreach ($r0['attacker'][0]['ships'] as $sId => $sData) {
        $name = isset($resource[$sId]) ? $resource[$sId] : "ID {$sId}";
        $initQty = (int)$sData[0];
        $totalAttShips += $initQty;
        echo sprintf("   • %-22s: %' 10d naves  ->  0 supervivientes (100%% DESTRUIDAS)\n", ucfirst($name), $initQty);
    }
    echo "   TOTAL ATACANTE: " . number_format($totalAttShips) . " naves\n\n";

    echo "2. FLOTA DEFENSORA (GODWAR):\n";
    $totalDefInit = 0;
    $totalDefLost = 0;
    $totalDefSurv = 0;

    $endShips = isset($rEnd['defender'][0]['ships']) ? $rEnd['defender'][0]['ships'] : array();

    foreach ($r0['defender'][0]['ships'] as $sId => $sData) {
        $name = isset($resource[$sId]) ? $resource[$sId] : "ID {$sId}";
        $initQty = (int)$sData[0];
        $survQty = isset($endShips[$sId]) ? (int)$endShips[$sId][0] : 0;
        $lostQty = $initQty - $survQty;

        $totalDefInit += $initQty;
        $totalDefLost += $lostQty;
        $totalDefSurv += $survQty;

        $pctLost = ($initQty > 0) ? round(($lostQty / $initQty) * 100, 1) : 0;
        echo sprintf("   • %-22s: %' 10d iniciales  |  -%' 8d perdidas (%5.1f%%)  |  %' 10d sobreviven\n", 
            ucfirst($name), $initQty, $lostQty, $pctLost, $survQty
        );
    }
    echo sprintf("   TOTAL DEFENSOR: %s iniciales | -%s perdidas | %s sobreviven\n\n",
        number_format($totalDefInit), number_format($totalDefLost), number_format($totalDefSurv)
    );

    if (isset($data['debree'])) {
        $metDebris = $data['debree']['att'][0] + $data['debree']['def'][0];
        $cryDebris = $data['debree']['att'][1] + $data['debree']['def'][1];
        echo sprintf("3. ESCOMBROS CREADOS EN ÓRBITA:\n   • Metal: %s | Cristal: %s | Total: %s\n\n",
            number_format($metDebris), number_format($cryDebris), number_format($metDebris + $cryDebris)
        );
    }
}

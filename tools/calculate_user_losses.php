<?php
define('MODE', 'CRON');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
set_include_path(ROOT_PATH);

require 'includes/common.php';
require 'includes/vars/General.php';

global $pricelist, $resource;
$db = Database::get();

$reports = $db->select("SELECT * FROM " . DB_PREFIX . "raports WHERE rid IN ('c956daf9236f01c4f4b11affa2b85b14', '19b4bad711f61560fb8bb97b83ed9a1a') ORDER BY time ASC;");

function formatM($amount) {
    // Convierte a formato estilo "1.000M"
    // e.g. 1,000,000,000 -> 1.000M
    // 12,345,678,901 -> 12.345,67M o 12.346M
    $millions = $amount / 1000000.0;
    return number_format($millions, 2, ',', '.') . 'M';
}

$grandTotalLosses = array(
    'metal'     => 0.0,
    'crystal'   => 0.0,
    'deuterium' => 0.0,
    'total'     => 0.0
);

$grandTotalUnits = array();

foreach ($reports as $idx => $r) {
    $data = unserialize($r['raport']);
    $rNum = $idx + 1;
    $attackerName = ($r['attacker'] == 1010) ? 'Kimimaru' : 'PROMETHEUS';
    echo "====================================================================\n";
    echo "BATALLA #{$rNum} vs {$attackerName}\n";
    echo "====================================================================\n";

    $r0 = $data['rounds'][0];
    $rEnd = end($data['rounds']);
    $endShips = isset($rEnd['defender'][0]['ships']) ? $rEnd['defender'][0]['ships'] : array();

    $bMetal = 0.0;
    $bCrystal = 0.0;
    $bDeut = 0.0;

    foreach ($r0['defender'][0]['ships'] as $sId => $sData) {
        $name = isset($resource[$sId]) ? $resource[$sId] : "ID {$sId}";
        $initQty = (int)$sData[0];
        $survQty = isset($endShips[$sId]) ? (int)$endShips[$sId][0] : 0;
        $lostQty = $initQty - $survQty;

        if ($lostQty <= 0) continue;

        $cost = isset($pricelist[$sId]['cost']) ? $pricelist[$sId]['cost'] : array();
        $mCost = (isset($cost[901]) ? (float)$cost[901] : 0.0) * $lostQty;
        $cCost = (isset($cost[902]) ? (float)$cost[902] : 0.0) * $lostQty;
        $dCost = (isset($cost[903]) ? (float)$cost[903] : 0.0) * $lostQty;

        $bMetal += $mCost;
        $bCrystal += $cCost;
        $bDeut += $dCost;

        $grandTotalUnits[$sId] = (isset($grandTotalUnits[$sId]) ? $grandTotalUnits[$sId] : 0) + $lostQty;

        echo sprintf("  • %-20s: %' 8d perdidas | M: %-12s | C: %-12s | D: %-12s\n",
            ucfirst($name), $lostQty, formatM($mCost), formatM($cCost), formatM($dCost)
        );
    }

    $bTotal = $bMetal + $bCrystal + $bDeut;
    echo "--------------------------------------------------------------------\n";
    echo "SUBTOTAL BATALLA #{$rNum}:\n";
    echo "  • Metal:     " . formatM($bMetal) . " (" . number_format($bMetal) . ")\n";
    echo "  • Cristal:   " . formatM($bCrystal) . " (" . number_format($bCrystal) . ")\n";
    echo "  • Deuterio:  " . formatM($bDeut) . " (" . number_format($bDeut) . ")\n";
    echo "  • TOTAL:     " . formatM($bTotal) . " (" . number_format($bTotal) . ")\n\n";

    $grandTotalLosses['metal'] += $bMetal;
    $grandTotalLosses['crystal'] += $bCrystal;
    $grandTotalLosses['deuterium'] += $bDeut;
    $grandTotalLosses['total'] += $bTotal;
}

echo "====================================================================\n";
echo "RESUMEN CONSOLIDADO DE PÉRDIDAS TOTALES (AMBAS BATALLAS):\n";
echo "====================================================================\n";
echo "PÉRDIDAS POR RECURSO:\n";
echo "  • Metal Perdido:     " . formatM($grandTotalLosses['metal']) . " (" . number_format($grandTotalLosses['metal']) . ")\n";
echo "  • Cristal Perdido:   " . formatM($grandTotalLosses['crystal']) . " (" . number_format($grandTotalLosses['crystal']) . ")\n";
echo "  • Deuterio Perdido:  " . formatM($grandTotalLosses['deuterium']) . " (" . number_format($grandTotalLosses['deuterium']) . ")\n";
echo "--------------------------------------------------------------------\n";
echo "  ★ PÉRDIDA TOTAL GLOBAL: " . formatM($grandTotalLosses['total']) . " (" . number_format($grandTotalLosses['total']) . " recursos)\n";
echo "====================================================================\n";

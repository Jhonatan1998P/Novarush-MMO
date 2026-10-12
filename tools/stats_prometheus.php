<?php

define('MODE', 'INGAME');
define('ROOT_PATH', './');
require_once 'includes/common.php';
require_once 'includes/vars/General.php';

$logFile = 'tele_prometheus.log';
if (!file_exists($logFile)) {
    die("tele_prometheus.log not found\n");
}

$lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$perPlanet = array();
$globalUnits = array();
$globalRes = array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);

global $resource, $pricelist, $LNG;

foreach ($lines as $line) {
    if (!preg_match('/\[(FLOTA|DEFENSA)\]\s+\[(.*?\[\d+:\d+:\d+\])\]\s+.*\|\s*ID:\s*#(\d+)\s*\|\s*Cantidad:\s*([\d,]+)/i', $line, $m)) {
        continue;
    }

    $type   = $m[1];
    $planet = trim($m[2]);
    $unitId = (int)$m[3];
    $count  = (int)str_replace(',', '', $m[4]);

    if (!isset($perPlanet[$planet])) {
        $perPlanet[$planet] = array(
            'units'     => array(),
            'metal'     => 0.0,
            'crystal'   => 0.0,
            'deuterium' => 0.0
        );
    }

    if (!isset($perPlanet[$planet]['units'][$unitId])) {
        $perPlanet[$planet]['units'][$unitId] = 0;
    }
    $perPlanet[$planet]['units'][$unitId] += $count;

    if (!isset($globalUnits[$unitId])) {
        $globalUnits[$unitId] = 0;
    }
    $globalUnits[$unitId] += $count;

    $costM = (float)($pricelist[$unitId]['cost'][901] ?? 0) * $count;
    $costC = (float)($pricelist[$unitId]['cost'][902] ?? 0) * $count;
    $costD = (float)($pricelist[$unitId]['cost'][903] ?? 0) * $count;

    $perPlanet[$planet]['metal']     += $costM;
    $perPlanet[$planet]['crystal']   += $costC;
    $perPlanet[$planet]['deuterium'] += $costD;

    $globalRes['metal']     += $costM;
    $globalRes['crystal']   += $costC;
    $globalRes['deuterium'] += $costD;
}

$unitNames = array(
    204 => 'Caza Ligero',
    205 => 'Caza Pesado',
    206 => 'Crucero',
    213 => 'Destructor',
    217 => 'Megatransportador',
    219 => 'Reciclador Pesado',
    225 => 'Galeón',
    405 => 'Cañón Iónico',
);

echo "========================================================================================\n";
echo " RESUMEN DE RECLUTAMIENTO: PROMETHEUS (POR PLANETA Y GLOBAL)\n";
echo "========================================================================================\n\n";

echo "--- 1. DESGLOSE POR PLANETA ---\n";
foreach ($perPlanet as $pName => $pData) {
    echo "• Planeta: {$pName}\n";
    $uStrs = array();
    foreach ($pData['units'] as $uId => $qty) {
        $name = $unitNames[$uId] ?? "ID#{$uId}";
        $uStrs[] = number_format($qty) . " " . $name;
    }
    echo "  Tropas: " . implode(', ', $uStrs) . "\n";
    $mse = $pData['metal'] + (2.0 * $pData['crystal']) + (4.0 * $pData['deuterium']);
    echo "  Recursos gastados: " . number_format($pData['metal']) . " Metal, " . number_format($pData['crystal']) . " Cristal, " . number_format($pData['deuterium']) . " Deuterio (Total MSE: " . number_format($mse) . ")\n\n";
}

echo "--- 2. TOTAL GLOBAL DE TROPAS RECLUTADAS ---\n";
$totalTropas = 0;
foreach ($globalUnits as $uId => $qty) {
    $totalTropas += $qty;
    $name = $unitNames[$uId] ?? "ID#{$uId}";
    echo "  • " . str_pad($name . " (#{$uId}):", 28) . number_format($qty) . " unidades\n";
}
echo "  >> TOTAL TROPAS: " . number_format($totalTropas) . " unidades\n\n";

$globalMse = $globalRes['metal'] + (2.0 * $globalRes['crystal']) + (4.0 * $globalRes['deuterium']);
echo "--- 3. TOTAL GLOBAL DE RECURSOS INVERTIDOS ---\n";
echo "  • Metal:    " . number_format($globalRes['metal']) . "\n";
echo "  • Cristal:  " . number_format($globalRes['crystal']) . "\n";
echo "  • Deuterio: " . number_format($globalRes['deuterium']) . "\n";
echo "  >> TOTAL MSE: " . number_format($globalMse) . "\n";

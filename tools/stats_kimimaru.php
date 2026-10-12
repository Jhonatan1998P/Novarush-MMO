<?php

define('MODE', 'INGAME');
define('ROOT_PATH', './');
require_once 'includes/common.php';
require_once 'includes/vars/General.php';

$logFile = 'tele_kimimaru.log';
if (!file_exists($logFile)) {
    die("tele_kimimaru.log not found\n");
}

$lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$perPlanet = array();
$globalUnits = array();
$globalRes = array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);

$firstTs = null;
$lastTs = null;
$firstLine = null;
$lastLine = null;

global $pricelist;

foreach ($lines as $line) {
    if (!preg_match('/^\[(.*?)\]\s+\[(FLOTA|DEFENSA)\]\s+\[(.*?\[\d+:\d+:\d+\])\]\s+.*\|\s*ID:\s*#(\d+)\s*\|\s*Cantidad:\s*([\d,]+)/i', $line, $m)) {
        continue;
    }

    $timestamp = $m[1];
    $type      = $m[2];
    $planet    = trim($m[3]);
    $unitId    = (int)$m[4];
    $count     = (int)str_replace(',', '', $m[5]);

    if ($firstTs === null) {
        $firstTs = $timestamp;
        $firstLine = $line;
    }
    $lastTs = $timestamp;
    $lastLine = $line;

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
    202 => 'Nave pequeña de carga',
    203 => 'Nave grande de carga',
    204 => 'Cazador Ligero',
    205 => 'Cazador Pesado',
    206 => 'Crucero',
    207 => 'Nave de Batalla',
    208 => 'Colonizador',
    209 => 'Reciclador',
    210 => 'Sonda de espionaje',
    211 => 'Bombardero',
    212 => 'Satélite Solar',
    213 => 'Destructor',
    214 => 'Estrella de la Muerte',
    215 => 'Acorazado',
    216 => 'Luna Negra',
    217 => 'Megatransportador',
    219 => 'Reciclador Pesado',
    225 => 'Galeón Espacial',
    401 => 'Lanzamisiles',
    402 => 'Láser pequeño',
    403 => 'Láser grande',
    404 => 'Cañón Gauss',
    405 => 'Cañón Iónico',
    406 => 'Cañón de Plasma',
    407 => 'Cúpula pequeña',
    408 => 'Cúpula grande',
    502 => 'Misil de intercepción',
    503 => 'Misil interplanetario'
);

$tzServer = new DateTimeZone('Europe/Berlin');
$tzVzla   = new DateTimeZone('America/Caracas');

$dtFirstVzla = null;
$dtLastVzla = null;
$interval = null;

if ($firstTs && $lastTs) {
    $dtFirst = new DateTime($firstTs, $tzServer);
    $dtFirstVzla = clone $dtFirst;
    $dtFirstVzla->setTimezone($tzVzla);

    $dtLast = new DateTime($lastTs, $tzServer);
    $dtLastVzla = clone $dtLast;
    $dtLastVzla->setTimezone($tzVzla);

    $interval = $dtFirst->diff($dtLast);
}

echo "========================================================================================\n";
echo " RESUMEN DE RECLUTAMIENTO: KIMIMARU\n";
echo "========================================================================================\n\n";

echo "--- 1. VENTANA TEMPORAL (HORA DE VENEZUELA UTC-4) ---\n";
if ($dtFirstVzla && $dtLastVzla) {
    echo "• Inicio del análisis: " . $dtFirstVzla->format('d/m/Y h:i:s A') . " (24h: " . $dtFirstVzla->format('Y-m-d H:i:s') . ")\n";
    echo "• Fin del análisis:    " . $dtLastVzla->format('d/m/Y h:i:s A') . " (24h: " . $dtLastVzla->format('Y-m-d H:i:s') . ")\n";
    echo "• Tiempo transcurrido: " . $interval->format('%h horas, %i minutos y %s segundos') . "\n\n";
} else {
    echo "No se encontraron eventos de reclutamiento.\n\n";
}

echo "--- 2. DESGLOSE POR COLONIA ---\n";
foreach ($perPlanet as $pName => $pData) {
    echo "• Planeta: {$pName}\n";
    $uStrs = array();
    foreach ($pData['units'] as $uId => $qty) {
        $name = $unitNames[$uId] ?? "ID#{$uId}";
        $uStrs[] = number_format($qty) . " " . $name;
    }
    echo "  Tropas: " . implode(', ', $uStrs) . "\n";
    echo "  Recursos gastados: " . number_format($pData['metal']) . " Metal | " . number_format($pData['crystal']) . " Cristal | " . number_format($pData['deuterium']) . " Deuterio\n\n";
}

echo "--- 3. TOTAL GLOBAL DE TROPAS RECLUTADAS ---\n";
$totalTropas = 0;
foreach ($globalUnits as $uId => $qty) {
    $totalTropas += $qty;
    $name = $unitNames[$uId] ?? "ID#{$uId}";
    echo "  • " . str_pad($name . " (#{$uId}):", 30) . number_format($qty) . " unidades\n";
}
echo "  >> TOTAL TROPAS: " . number_format($totalTropas) . " unidades\n\n";

echo "--- 4. TOTAL GLOBAL DE RECURSOS INVERTIDOS (SIN MSE) ---\n";
echo "  • Metal:    " . number_format($globalRes['metal']) . "\n";
echo "  • Cristal:  " . number_format($globalRes['crystal']) . "\n";
echo "  • Deuterio: " . number_format($globalRes['deuterium']) . "\n";

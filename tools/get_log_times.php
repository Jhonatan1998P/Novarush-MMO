<?php

$logFile = 'tele_prometheus.log';
if (!file_exists($logFile)) {
    die("File not found\n");
}

$lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$firstOverall = reset($lines);
$lastOverall  = end($lines);

$firstTs = null;
$lastTs = null;

foreach ($lines as $line) {
    if (preg_match('/^\[(.*?)\]\s+\[(FLOTA|DEFENSA)\]/', $line, $m)) {
        if ($firstTs === null) {
            $firstTs = $m[1];
        }
        $lastTs = $m[1];
    }
}

echo "--- Servidor (Europe/Berlin UTC+2) ---\n";
echo "Inicio: {$firstTs}\n";
echo "Fin:    {$lastTs}\n\n";

$tzServer = new DateTimeZone('Europe/Berlin');
$tzVzla   = new DateTimeZone('America/Caracas');

$dtFirst = new DateTime($firstTs, $tzServer);
$dtFirstVzla = clone $dtFirst;
$dtFirstVzla->setTimezone($tzVzla);

$dtLast = new DateTime($lastTs, $tzServer);
$dtLastVzla = clone $dtLast;
$dtLastVzla->setTimezone($tzVzla);

$interval = $dtFirst->diff($dtLast);

echo "--- Venezuela (America/Caracas UTC-4) ---\n";
echo "Inicio Reclutamiento: " . $dtFirstVzla->format('d/m/Y h:i:s A') . " (24h: " . $dtFirstVzla->format('Y-m-d H:i:s') . ")\n";
echo "Fin Reclutamiento:    " . $dtLastVzla->format('d/m/Y h:i:s A') . " (24h: " . $dtLastVzla->format('Y-m-d H:i:s') . ")\n\n";
echo "Ventana de reclutamiento: " . $interval->format('%h horas, %i minutos y %s segundos') . "\n\n";

echo "Primera línea del archivo:\n  $firstOverall\n";
echo "Última línea del archivo:\n  $lastOverall\n";

<?php

/**
 * NovaRush - Real-Time Bot Telemetry Viewer & Monitor (CLI)
 *
 * Usage:
 *   php tools/tail_telemetry.php [bot_name|all] [options]
 *
 * Options:
 *   --lines=N        Number of recent lines to display (default: 30)
 *   --filter=TAG     Filter lines by tag or keyword (e.g., MERCADER, FIESTA, FLOTA, PREMIUM)
 *   --follow, -f     Continuously follow log in real time (tail -f)
 *   --summary        Show telemetry stats summary only
 *
 * Examples:
 *   php tools/tail_telemetry.php prometheus
 *   php tools/tail_telemetry.php kimimaru --lines=50
 *   php tools/tail_telemetry.php laparca --filter=MERCADER
 *   php tools/tail_telemetry.php prometheus -f
 *   php tools/tail_telemetry.php all
 */

if (php_sapi_name() !== 'cli') {
    die("This tool can only be executed via PHP CLI.\n");
}

$root = dirname(__DIR__) . DIRECTORY_SEPARATOR;

// Parse CLI arguments
$targetBot = 'all';
$linesCount = 30;
$filterTag = '';
$followMode = false;
$summaryOnly = false;

foreach ($argv as $i => $arg) {
    if ($i === 0) continue;
    if ($arg === '--follow' || $arg === '-f') {
        $followMode = true;
    } elseif ($arg === '--summary') {
        $summaryOnly = true;
    } elseif (strpos($arg, '--lines=') === 0) {
        $linesCount = (int)substr($arg, 8);
    } elseif (strpos($arg, '--filter=') === 0) {
        $filterTag = strtoupper(trim(substr($arg, 9)));
    } elseif (strpos($arg, '-') !== 0) {
        $targetBot = strtolower(trim($arg));
    }
}

// Discover available bot logs
$logFiles = array();
$knownBots = array('prometheus', 'kimimaru', 'laparca');

foreach ($knownBots as $kb) {
    $path = $root . "tele_{$kb}.log";
    $logFiles[$kb] = $path;
}

// Scan directory for any other tele_*.log files
$scanned = glob($root . "tele_*.log");
if (!empty($scanned)) {
    foreach ($scanned as $sf) {
        $base = basename($sf);
        if (preg_match('/^tele_(.+)\.log$/', $base, $m)) {
            $bName = strtolower($m[1]);
            $logFiles[$bName] = $sf;
        }
    }
}

// ANSI Color helper
function colorize($text, $colorCode) {
    // Check if terminal supports ANSI colors
    if (DIRECTORY_SEPARATOR === '\\' && !getenv('ANSICON') && !getenv('ConEmuANSI') && !getenv('WT_SESSION')) {
        // Windows Terminal supports ANSI
        return "\033[{$colorCode}m{$text}\033[0m";
    }
    return "\033[{$colorCode}m{$text}\033[0m";
}

function formatTelemetryLine($line) {
    // 1. Colorize planet locations first (before tags insert ANSI escape sequences with brackets)
    $line = preg_replace('/\[([^\]]+ \[\d+:\d+:\d+\])\]/', colorize('[$1]', '1;37'), $line);

    // 2. Colorize timestamps
    $line = preg_replace('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', colorize('[$1]', '0;90'), $line);

    // 3. Colorize tags
    $line = preg_replace_callback('/\[(CONSTRUCCION|INVESTIGACION|FLOTA|DEFENSA|MERCADER|PREMIUM_GASTO|PREMIUM_CANJE|PREMIUM_GANANCIA|FIESTA|CONTENEDORES|MISION_FLOTA|DESGUACE)\]/', function($m) {
        switch ($m[1]) {
            case 'CONSTRUCCION':     return colorize("[{$m[1]}]", '1;33'); // Yellow bold
            case 'INVESTIGACION':    return colorize("[{$m[1]}]", '1;36'); // Cyan bold
            case 'FLOTA':            return colorize("[{$m[1]}]", '1;32'); // Green bold
            case 'DEFENSA':          return colorize("[{$m[1]}]", '1;34'); // Blue bold
            case 'MERCADER':         return colorize("[{$m[1]}]", '1;35'); // Magenta bold
            case 'PREMIUM_GASTO':    return colorize("[{$m[1]}]", '1;31'); // Red bold
            case 'PREMIUM_CANJE':    return colorize("[{$m[1]}]", '0;35'); // Magenta
            case 'PREMIUM_GANANCIA': return colorize("[{$m[1]}]", '1;92'); // High Green
            case 'FIESTA':           return colorize("[{$m[1]}]", '1;93'); // Gold/Yellow
            case 'CONTENEDORES':     return colorize("[{$m[1]}]", '1;96'); // Bright Cyan
            case 'MISION_FLOTA':     return colorize("[{$m[1]}]", '1;94'); // Bright Blue bold
            case 'DESGUACE':         return colorize("[{$m[1]}]", '0;33'); // Dark Yellow
            default:                 return "[{$m[1]}]";
        }
    }, $line);

    return $line;
}

echo colorize("================================================================================", '1;30') . "\n";
echo colorize(" 🚀 NovaRush — Monitor de Telemetría de Bots en Tiempo Real", '1;37') . "\n";
echo colorize("================================================================================", '1;30') . "\n";

if ($targetBot === 'all') {
    echo colorize("Bots detectados en el sistema:\n", '0;36');
    foreach ($logFiles as $bName => $fPath) {
        $exists = file_exists($fPath);
        $size = $exists ? round(filesize($fPath) / 1024, 2) . ' KB' : 'No creado aún (0 KB)';
        $status = $exists ? colorize("ACTIVO ({$size})", '1;32') : colorize("INACTIVO", '0;90');
        echo " • " . str_pad(strtoupper($bName), 14) . " -> " . basename($fPath) . " [{$status}]\n";
    }
    echo "\n";

    if ($summaryOnly) {
        exit(0);
    }

    echo colorize("--- Mostrando últimas {$linesCount} acciones por bot ---\n\n", '0;33');
    foreach ($logFiles as $bName => $fPath) {
        if (!file_exists($fPath)) continue;
        echo colorize(">>> Telemetría de " . strtoupper($bName) . " (" . basename($fPath) . "):\n", '1;36');
        $lines = file($fPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (empty($lines)) {
            echo " (Archivo vacío)\n\n";
            continue;
        }
        if (!empty($filterTag)) {
            $lines = array_filter($lines, function($l) use ($filterTag) {
                return stripos($l, $filterTag) !== false;
            });
        }
        $slice = array_slice($lines, -$linesCount);
        foreach ($slice as $l) {
            echo " " . formatTelemetryLine($l) . "\n";
        }
        echo "\n";
    }
    exit(0);
}

// Single Bot Target
if (!isset($logFiles[$targetBot])) {
    die(colorize("Error: Bot '{$targetBot}' no encontrado. Disponibles: " . implode(', ', array_keys($logFiles)) . "\n", '1;31'));
}

$activeFile = $logFiles[$targetBot];
echo colorize("Objetivo:", '0;36') . " " . strtoupper($targetBot) . " (" . basename($activeFile) . ")\n";
echo colorize("Ruta:", '0;36') . " {$activeFile}\n";

if (!file_exists($activeFile)) {
    echo colorize("Aviso: El archivo '{$activeFile}' aún no ha sido creado. Esperando actividad del daemon...\n", '1;33');
    if (!$followMode) {
        exit(0);
    }
}

if ($followMode) {
    echo colorize("Modo Follow (-f) activado. Presiona Ctrl+C para salir.\n\n", '1;32');
    $lastPos = 0;
    if (file_exists($activeFile)) {
        $lines = file($activeFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!empty($lines)) {
            if (!empty($filterTag)) {
                $lines = array_filter($lines, function($l) use ($filterTag) {
                    return stripos($l, $filterTag) !== false;
                });
            }
            $slice = array_slice($lines, -$linesCount);
            foreach ($slice as $l) {
                echo formatTelemetryLine($l) . "\n";
            }
        }
        $lastPos = filesize($activeFile);
    }

    while (true) {
        clearstatcache(false, $activeFile);
        if (file_exists($activeFile)) {
            $curSize = filesize($activeFile);
            if ($curSize < $lastPos) {
                // Rotated
                $lastPos = 0;
            }
            if ($curSize > $lastPos) {
                $fp = fopen($activeFile, 'r');
                fseek($fp, $lastPos);
                while (($line = fgets($fp)) !== false) {
                    $trimLine = trim($line);
                    if (!empty($trimLine)) {
                        if (empty($filterTag) || stripos($trimLine, $filterTag) !== false) {
                            echo formatTelemetryLine($trimLine) . "\n";
                        }
                    }
                }
                $lastPos = ftell($fp);
                fclose($fp);
            }
        }
        usleep(500000); // 0.5s polling
    }
} else {
    $lines = file_exists($activeFile) ? file($activeFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : array();
    if (empty($lines)) {
        echo "No hay registros aún.\n";
        exit(0);
    }
    if (!empty($filterTag)) {
        $lines = array_filter($lines, function($l) use ($filterTag) {
            return stripos($l, $filterTag) !== false;
        });
    }
    $slice = array_slice($lines, -$linesCount);
    echo colorize("--- Últimas " . count($slice) . " acciones registradas ---\n", '0;33');
    foreach ($slice as $l) {
        echo formatTelemetryLine($l) . "\n";
    }
    echo "\n";
}

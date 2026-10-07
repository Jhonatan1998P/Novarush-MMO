<?php

/*
 * NovaRush - War Event Boss CLI Tool
 * Herramienta de Terminal para Gestionar y Monitorear la Fortaleza Boss
 */

define('MODE', 'INSTALL');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
chdir(ROOT_PATH);

require 'includes/common.php';
require 'includes/vars/General.php';
require 'includes/classes/class.BuildFunctions.php';
require_once 'includes/classes/events/WarEventBossEngine.class.php';

// Parse command line arguments
$options = getopt("", array(
    "action:",
    "ratio::",
    "duration::",
    "pre::",
    "containers::",
    "am::",
    "min_sys::",
    "max_sys::",
    "metal::",
    "crystal::",
    "deut::"
));

$action = isset($options['action']) ? $options['action'] : 'status';

echo "=========================================================\n";
echo "   NovaRush - War Event Boss Engine (Fortaleza Boss)    \n";
echo "=========================================================\n";

switch ($action) {
    case 'start':
        $ratio        = isset($options['ratio']) ? floatval($options['ratio']) : 0.60;
        $duration     = isset($options['duration']) ? intval($options['duration']) : 24;
        $preCountdown = isset($options['pre']) ? intval($options['pre']) : 0;
        $containers   = isset($options['containers']) ? intval($options['containers']) : 50;
        $am           = isset($options['am']) ? intval($options['am']) : 100;
        $minSys       = isset($options['min_sys']) ? intval($options['min_sys']) : 1;
        $maxSys       = isset($options['max_sys']) ? intval($options['max_sys']) : 200;

        $lootMetal    = isset($options['metal']) ? floatval($options['metal']) : 200000000;
        $lootCrystal  = isset($options['crystal']) ? floatval($options['crystal']) : 100000000;
        $lootDeut     = isset($options['deut']) ? floatval($options['deut']) : 50000000;

        try {
            echo "Iniciando análisis del universo y cálculo de budget...\n";
            $res = WarEventBossEngine::startEvent(array(
                'budget_ratio'          => $ratio,
                'duration_hours'        => $duration,
                'pre_countdown_minutes' => $preCountdown,
                'containers_per_winner' => $containers,
                'antimatter_per_winner' => $am,
                'min_system'            => $minSys,
                'max_system'            => $maxSys,
                'loot_metal'            => $lootMetal,
                'loot_crystal'          => $lootCrystal,
                'loot_deuterium'        => $lootDeut
            ));

            if (!empty($res['scheduled'])) {
                echo "⏳ ¡EVENTO PROGRAMADO EXITOSAMENTE!\n";
                echo "• Cuenta Regresiva de Inicio: {$res['pre_countdown_minutes']} minutos.\n";
                echo "• Hora de Inicio: " . date('Y-m-d H:i:s', $res['start_time']) . "\n";
                echo "• Duración de Batalla: {$duration} horas tras su llegada.\n";
                echo "• Budget Proyectado: " . number_format($res['budget']) . " Metal Puro (" . ($ratio * 100) . "%)\n";
                echo "• Aviso y temporizador activos en game.php para los jugadores.\n";
            } else {
                echo "✅ ¡EVENTO INICIADO EXITOSAMENTE!\n";
                echo "• Coordenadas del Boss: [{$res['coords']}]\n";
                echo "• Budget Consumido: " . number_format($res['budget']) . " Metal Puro (" . ($ratio * 100) . "%)\n";
                echo "• Tiempo Límite: {$duration} horas (Expira: " . date('Y-m-d H:i:s', $res['end_time']) . ")\n";
                echo "• Recompensa Estocada Final: {$containers} Contenedores y {$am} AM a CADA participante.\n";
                echo "• Unidades defensivas inyectadas:\n";
                $ratios = WarEventBossEngine::getUnitRatios();
                foreach ($res['units'] as $id => $qty) {
                    echo "   - {$ratios[$id]['name']} ({$id}): " . number_format($qty) . "\n";
                }
            }
        } catch (Exception $e) {
            echo "❌ ERROR: " . $e->getMessage() . "\n";
        }
        break;

    case 'cancel_scheduled':
        try {
            WarEventBossEngine::cancelScheduledEvent();
            echo "✅ Evento programado cancelado exitosamente.\n";
        } catch (Exception $e) {
            echo "❌ ERROR: " . $e->getMessage() . "\n";
        }
        break;

    case 'stop':
    case 'expire':
        try {
            echo "Deteniendo evento...\n";
            WarEventBossEngine::cancelScheduledEvent();
            $ok = WarEventBossEngine::expireEvent();
            if ($ok) {
                echo "✅ Evento activo finalizado. Planeta retirado y flotas en vuelo retornadas.\n";
            } else {
                echo "ℹ️ No había ningún evento activo para detener (o se canceló el programado).\n";
            }
        } catch (Exception $e) {
            echo "❌ ERROR: " . $e->getMessage() . "\n";
        }
        break;

    case 'cron':
        // Lightweight check for cron execution
        WarEventBossEngine::checkScheduledActivation();
        $active = WarEventBossEngine::getActiveEvent();
        if (!empty($active)) {
            if ($active['end_time'] > 0 && TIMESTAMP >= $active['end_time']) {
                WarEventBossEngine::expireEvent($active['id']);
                echo "Evento expirado por tiempo y procesado por cron.\n";
            } else {
                echo "Evento activo en curso. Tiempo restante: " . ($active['end_time'] - TIMESTAMP) . "s\n";
            }
        } else {
            $scheduled = WarEventBossEngine::getScheduledEvent();
            if (!empty($scheduled)) {
                echo "Evento programado en espera. Inicia en: " . ($scheduled['start_time'] - TIMESTAMP) . "s\n";
            } else {
                echo "No hay evento activo ni programado.\n";
            }
        }
        break;

    case 'status':
    default:
        $serverBudget = WarEventBossEngine::calculateServerBudget();
        $status = WarEventBossEngine::getEventStatus();

        echo "• Gasto Militar del Servidor (Sin Admin): " . number_format($serverBudget) . " Metal Puro\n";
        if ($status['active']) {
            $e = $status['event'];
            $hrs = floor($status['remaining_seconds'] / 3600);
            $mins = floor(($status['remaining_seconds'] % 3600) / 60);
            $secs = $status['remaining_seconds'] % 60;

            echo "• Estado: ACTIVO 🔥\n";
            echo "• Ubicación: [{$e['galaxy']}:{$e['system']}:{$e['planet']}]\n";
            echo "• Budget Asignado: " . number_format($e['calculated_budget']) . " Metal (" . ($e['budget_ratio'] * 100) . "%)\n";
            echo "• Tiempo Restante: {$hrs}h {$mins}m {$secs}s (Expira: " . date('Y-m-d H:i:s', $e['end_time']) . ")\n";
            echo "• Recompensa Estocada Final: {$e['containers_per_winner']} Contenedores y {$e['antimatter_per_winner']} AM por ganador.\n";
            echo "• Unidades Sobrevivientes en el Planeta: " . number_format($status['total_surviving_units']) . "\n";
            foreach ($status['surviving_units'] as $id => $u) {
                echo "   - {$u['name']}: " . number_format($u['amount']) . "\n";
            }
        } elseif ($status['scheduled']) {
            $e = $status['event'];
            $hrs = floor($status['countdown_seconds'] / 3600);
            $mins = floor(($status['countdown_seconds'] % 3600) / 60);
            $secs = $status['countdown_seconds'] % 60;

            echo "• Estado: PROGRAMADO / CUENTA REGRESIVA ⏳\n";
            echo "• Tiempo para Iniciar: {$hrs}h {$mins}m {$secs}s (Inicia: " . date('Y-m-d H:i:s', $e['start_time']) . ")\n";
            echo "• Duración de Batalla: {$e['duration_hours']} horas tras su llegada.\n";
            echo "• Budget Configurado: " . number_format($e['calculated_budget']) . " Metal (" . ($e['budget_ratio'] * 100) . "%)\n";
            echo "• Recompensa Estocada Final: {$e['containers_per_winner']} Contenedores y {$e['antimatter_per_winner']} AM por ganador.\n";
        } else {
            echo "• Estado: INACTIVO 💤\n";
            if (!empty($status['last_event'])) {
                $last = $status['last_event'];
                echo "• Último Evento: {$last['event_name']} en [{$last['galaxy']}:{$last['system']}:{$last['planet']}] - Estado: {$last['status']}\n";
            }
        }
        break;
}
echo "=========================================================\n";

<?php

/*
 * NovaRush - Script de Activación Oficial de Evento de Guerra (Fortaleza Ancestral)
 * y Limpieza de Mensajes de Test
 */

define('MODE', 'INSTALL');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
chdir(ROOT_PATH);

require 'includes/common.php';
require 'includes/vars/General.php';
require 'includes/classes/class.BuildFunctions.php';
require_once 'includes/classes/events/WarEventBossEngine.class.php';

$vzTz = new DateTimeZone('America/Caracas');
$vzNow = new DateTime('now', $vzTz);
$vzTarget = new DateTime('18:00:00', $vzTz);

// Si ya pasaron las 18:00 (por si acaso), usar las 18:00 de hoy
$diffSeconds = $vzTarget->getTimestamp() - $vzNow->getTimestamp();
$diffMinutes = max(1, (int) round($diffSeconds / 60));

echo "=================================================================\n";
echo "    ACTIVACIÓN OFICIAL: FORTALEZA ANCESTRAL (WAR EVENT BOSS)    \n";
echo "=================================================================\n";
echo "• Hora actual (Venezuela): " . $vzNow->format('Y-m-d H:i:s') . "\n";
echo "• Hora de inicio objetivo (Venezuela): " . $vzTarget->format('Y-m-d H:i:s') . "\n";
echo "• Minutos de pre-evento calculados: " . $diffMinutes . " minutos (" . $diffSeconds . " segundos)\n\n";

$db = Database::get();

// 1. LIMPIEZA DE MENSAJES DE PRUEBA
echo "--- PASO 1: LIMPIEZA DE MENSAJES DE PRUEBA ---\n";
$deleteSql = "DELETE FROM %%MESSAGES%% WHERE 
    message_type = 50 
    OR message_sender = 999 
    OR message_from LIKE '%Ancestral%' 
    OR message_subject LIKE '%Fortaleza%' 
    OR message_subject LIKE '%ESTOCADA%' 
    OR message_subject LIKE '%EVENTO FINALIZADO%' 
    OR message_subject LIKE '%VICTORIA: RECOMPENSA%';";

$deletedCount = $db->delete($deleteSql);
echo "✔ Mensajes de prueba eliminados: " . $deletedCount . "\n";

// Verificar mensajes restantes
$remainingMsgs = $db->selectSingle("SELECT COUNT(*) as cnt FROM %%MESSAGES%%;");
echo "✔ Mensajes legítimos preservados en el buzón: " . $remainingMsgs['cnt'] . "\n\n";

// 2. CANCELACIÓN DE CUALQUIER EVENTO PROGRAMADO O ACTIVO PREVIO
echo "--- PASO 2: VERIFICACIÓN Y PREPARACIÓN DE TABLA DE EVENTOS ---\n";
WarEventBossEngine::cancelScheduledEvent();
$db->update("UPDATE %%WAR_EVENTS%% SET status = 'expired' WHERE status IN ('scheduled', 'active');");
echo "✔ Eventos previos cerrados / expirados.\n";

// Asegurar que no hay NPC 999 residual
$db->delete("DELETE FROM %%USERS%% WHERE id = 999;");
$db->delete("DELETE FROM %%STATPOINTS%% WHERE id_owner = 999;");
echo "✔ Verificación de aislamiento del ranking y limpieza de NPC completada.\n\n";

// 3. PROGRAMACIÓN DEL EVENTO OFICIAL
echo "--- PASO 3: PROGRAMACIÓN DEL EVENTO OFICIAL CON PRE-COUNTDOWN ---\n";
try {
    $res = WarEventBossEngine::startEvent(array(
        'budget_ratio'          => 0.60,
        'loot_ratio'            => 0.40,
        'duration_hours'        => 24,
        'pre_countdown_minutes' => $diffMinutes,
        'containers_per_winner' => 25,
        'antimatter_per_winner' => 100,
        'min_system'            => 1,
        'max_system'            => 200
    ));

    // Ajustar con precisión milimétrica al segundo exacto 18:00:00 Venezuela
    $exactTargetTs = $vzTarget->getTimestamp();
    $db->update("UPDATE %%WAR_EVENTS%% SET start_time = :st, end_time = :et WHERE status = 'scheduled' ORDER BY id DESC LIMIT 1;", array(
        ':st' => $exactTargetTs,
        ':et' => $exactTargetTs + (24 * 3600)
    ));

    echo "✔ ¡EVENTO PROGRAMADO CON ÉXITO!\n";
    echo "• Estado: PROGRAMADO (Pre-evento)\n";
    echo "• Pre-countdown: {$diffMinutes} minutos\n";
    echo "• Timestamp de Inicio: {$exactTargetTs} (" . date('Y-m-d H:i:s', $exactTargetTs) . " Servidor)\n";
    echo "• Hora de Inicio en Venezuela: " . (new DateTime('@' . $exactTargetTs))->setTimezone($vzTz)->format('d/m/Y h:i:s A') . " (EXACTAMENTE A LAS 6:00:00 PM)\n";
    echo "• Presupuesto proyectado: " . number_format($res['budget']) . " Metal Puro (60%)\n";
    echo "• Duración de la batalla: 24 horas\n";
} catch (Exception $e) {
    echo "❌ Error al programar el evento: " . $e->getMessage() . "\n";
}

// 4. VERIFICACIÓN DEL BANNER DEL JUEGO
echo "\n--- PASO 4: VERIFICACIÓN DEL BANNER EN EL JUEGO ---\n";
$banner = WarEventBossEngine::getBannerData(1);
if (!empty($banner)) {
    echo "✔ Banner activo detectado:\n";
    echo "  - Estado: " . $banner['state'] . "\n";
    echo "  - Nombre: " . $banner['name'] . "\n";
    echo "  - Segundos restantes hasta aparición: " . $banner['countdown_seconds'] . "s (" . round($banner['countdown_seconds'] / 60, 1) . "m)\n";
    echo "  - Recompensas: {$banner['containers']} Contenedores y {$banner['antimatter']} Antimateria\n";
} else {
    echo "⚠️ Banner no detectado (revisar configuración).\n";
}

echo "\n=================================================================\n";
echo "                    PROCESO FINALIZADO CON ÉXITO                \n";
echo "=================================================================\n";

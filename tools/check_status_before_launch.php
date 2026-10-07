<?php

define('MODE', 'INSTALL');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
chdir(ROOT_PATH);

require 'includes/common.php';
require_once 'includes/classes/events/WarEventBossEngine.class.php';

$vzTz = new DateTimeZone('America/Caracas');
$vzNow = new DateTime('now', $vzTz);
$vzTarget = new DateTime('18:00:00', $vzTz);

$diffSec = $vzTarget->getTimestamp() - $vzNow->getTimestamp();
$diffMin = (int) round($diffSec / 60);

echo "==========================================\n";
echo "1. VERIFICACIÓN DE HORARIO:\n";
echo "Hora en Venezuela actual: " . $vzNow->format('Y-m-d H:i:s') . "\n";
echo "Hora objetivo en Venezuela: " . $vzTarget->format('Y-m-d H:i:s') . "\n";
echo "Segundos restantes: " . $diffSec . "\n";
echo "Minutos restantes (pre-countdown): " . $diffMin . " minutos\n";
echo "==========================================\n";

$db = Database::get();

// Verificar estado de eventos en DB
echo "\n2. ESTADO ACTUAL DE EVENTOS EN DB:\n";
$events = $db->select("SELECT id, event_name, status, start_time, end_time, pre_countdown_minutes FROM %%WAR_EVENTS%% ORDER BY id DESC LIMIT 5;");
foreach ($events as $ev) {
    echo "ID: {$ev['id']} | Nombre: {$ev['event_name']} | Estado: {$ev['status']} | Start: " . date('Y-m-d H:i:s', $ev['start_time']) . " | Pre: {$ev['pre_countdown_minutes']}m\n";
}

// Verificar mensajes de prueba existentes
echo "\n3. MENSAJES DE PRUEBA EN %%MESSAGES%%:\n";
$msgCount = $db->selectSingle("SELECT COUNT(*) as cnt FROM %%MESSAGES%% WHERE message_type = 50 OR message_subject LIKE '%Fortaleza%' OR message_subject LIKE '%VICTORIA%' OR message_subject LIKE '%EVENTO FINALIZADO%';");
echo "Total de mensajes de test detectados: " . $msgCount['cnt'] . "\n";

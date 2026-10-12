<?php

define('MODE', 'CRON');
define('ROOT_PATH', './');
require_once 'includes/common.php';
require_once 'includes/vars/General.php';

$db = Database::get();
$planets = $db->select("SELECT p.name, p.hangar, p.b_hangar, p.b_hangar_id, u.username FROM %%PLANETS%% p INNER JOIN %%USERS%% u ON u.id = p.id_owner WHERE u.username IN ('PROMETHEUS', 'Kimimaru') AND p.destruyed = 0 LIMIT 6;");

echo "=== ESTADO REAL DE COLAS Y NANO EN ASTILLEROS ===\n";
foreach ($planets as $p) {
    $queue = !empty($p['b_hangar_id']) ? @unserialize($p['b_hangar_id']) : array();
    $totalSec = 0;
    foreach ($queue as $q) {
        $totalSec += ($q[1] * $q[2]);
    }
    echo sprintf("• [%s] %s: Astillero=%d, Nanobots=%d | Jobs en cola=%d | Tiempo total cola=%ds (%.1f min)\n",
        $p['username'], $p['name'],
        $p['shipyard'] ?? 0, $p['nanite_factory'] ?? 0,
        count($queue), $totalSec, $totalSec / 60
    );
    foreach ($queue as $q) {
        echo sprintf("    -> ID #%d: Cantidad = %d | Tiempo/u = %ds | Total job = %ds\n",
            $q[0], $q[1], $q[2], ($q[1] * $q[2])
        );
    }
}

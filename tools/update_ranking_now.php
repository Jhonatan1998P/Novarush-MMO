<?php
define('MODE', 'CRON');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
set_include_path(ROOT_PATH);

require 'includes/common.php';
require 'includes/vars/General.php';
require_once 'includes/classes/class.statbuilder.php';

$stat = new Statbuilder();
$stat->MakeStats();

$db = Database::get();

// Obtener el ranking de usuarios
$sql = "SELECT 
            sp.total_rank, 
            sp.id_owner, 
            u.username, 
            sp.total_points, 
            sp.fleet_points, 
            sp.build_points, 
            sp.defs_points, 
            sp.tech_points
        FROM %%STATPOINTS%% sp
        INNER JOIN %%USERS%% u ON u.id = sp.id_owner
        WHERE sp.stat_type = 1
        ORDER BY sp.total_rank ASC;";

$ranking = $db->select($sql);

echo "========================================================================================\n";
echo "                          RANKING GENERAL ACTUALIZADO (UNIVERSO 1)\n";
echo "========================================================================================\n";
echo sprintf("%-5s | %-16s | %-15s | %-14s | %-14s | %-12s\n", 
    "Pos", "Jugador", "Puntos Totales", "Puntos Flota", "Puntos Edif.", "Puntos Def."
);
echo "----------------------------------------------------------------------------------------\n";

foreach ($ranking as $r) {
    echo sprintf("#%-4d | %-16s | %' 14s | %' 13s | %' 13s | %' 11s\n",
        $r['total_rank'],
        $r['username'],
        number_format($r['total_points']),
        number_format($r['fleet_points']),
        number_format($r['build_points']),
        number_format($r['defs_points'])
    );
}
echo "========================================================================================\n";

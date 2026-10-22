<?php
/**
 * execute_olyssium_server_reset.php
 * 
 * Script canónico de reinicio total y purga absoluta del universo NovaRush -> Olyssium.
 * Ejecuta:
 * 1. Verificación de respaldo previo en includes/backups/
 * 2. Purga total de cuentas excepto admin (ID 1)
 * 3. Purga total de planetas y lunas excepto planeta principal de admin (ID 1)
 * 4. Reseteo a Nivel 0 de todas las estructuras, flotas, defensas y módulos en Planeta 1
 * 5. Reseteo a Nivel 0 de todas las investigaciones, oficiales y bonos de Admin
 * 6. Truncado/limpieza de todas las tablas transaccionales, logs, colas, bots y combate
 * 7. Actualización de parámetros en uni1_config:
 *    - uni_name = 'Olyssium'
 *    - energySpeed = 200 (la mitad de 400)
 *    - Fleet_Cdr = 70 (70%)
 *    - noobprotection = 1
 *    - noobprotectiontime = 500000 (500.000 pts)
 *    - noobprotectionmulti = 5
 *    - stat_settings = 50000 (50.000 recursos = 1 pt)
 * 8. Reconstrucción de estadísticas y limpieza profunda de cachés
 */

define('MODE', 'CRON');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
set_include_path(ROOT_PATH);

require 'includes/common.php';
require 'includes/vars/General.php';
require_once 'includes/classes/class.statbuilder.php';

$db = Database::get();

echo "========================================================================\n";
echo "              INICIANDO REINICIO TOTAL DEL SERVIDOR: OLYSSIUM           \n";
echo "========================================================================\n";

// 1. Verificar respaldo
$backupFile = ROOT_PATH . 'includes/backups/Backup_ogame2_Pre_Olyssium_Reset_2026_10_17.sql';
if (!file_exists($backupFile) || filesize($backupFile) < 1000000) {
    die("ERROR CRITICO: Respaldo de seguridad no encontrado o invalido en $backupFile. Abortando.\n");
}
echo "[OK] Respaldo verificado: $backupFile (" . number_format(filesize($backupFile) / (1024 * 1024), 2) . " MB)\n";

// 2. Actualizar configuración uni1_config
echo "[1/7] Actualizando configuracion del servidor en uni1_config...\n";
$updateConfigSql = "UPDATE uni1_config SET 
    uni_name = 'Olyssium',
    energySpeed = 200,
    Fleet_Cdr = 70,
    noobprotection = 1,
    noobprotectiontime = 500000,
    noobprotectionmulti = 5,
    stat_settings = 50000
WHERE uni = 1;";
$db->query($updateConfigSql);
echo "  -> uni_name = 'Olyssium'\n";
echo "  -> energySpeed = 200 (reducido a la mitad)\n";
echo "  -> Fleet_Cdr = 70 (70% de flotas a escombros)\n";
echo "  -> noobprotection = 1 (activado)\n";
echo "  -> noobprotectiontime = 500000 (limite 500,000 puntos)\n";
echo "  -> noobprotectionmulti = 5\n";
echo "  -> stat_settings = 50000 (50,000 recursos = 1 punto)\n";

// 3. Purgar tablas transaccionales y de bots
echo "[2/7] Purgando tablas transaccionales, logs, reportes y motor de bots...\n";
$tablesToTruncate = array(
    'uni1_fleets',
    'uni1_fleet_event',
    'uni1_messages',
    'uni1_raports',
    'uni1_topkb',
    'uni1_users_to_topkb',
    'uni1_buddy',
    'uni1_buddy_request',
    'uni1_notes',
    'uni1_statpoints',
    'uni1_banned',
    'uni1_alliance',
    'uni1_alliance_ranks',
    'uni1_alliance_request',
    'uni1_aks',
    'uni1_bot_tasks',
    'uni1_bot_intel',
    'uni1_bot_decisions',
    'uni1_bot_logs',
    'uni1_bot_target_blacklist',
    'uni1_bot_opponents',
    'uni1_bot_planet_cooldown',
    'uni1_bots',
    'uni1_market',
    'uni1_shortcuts',
    'uni1_log',
    'uni1_log_fleets',
    'uni1_session',
    'uni1_chat_messages',
    'uni1_chat_online',
    'uni1_chat_bans',
    'uni1_chat_invitations',
    'uni1_premium_daily',
    'uni1_premium_ledger',
    'uni1_conteiners',
    'uni1_records',
    'uni1_users_premium_backup',
    'uni1_ticket',
    'uni1_ticket_answer',
    'uni1_war_events',
    'uni1_diplo',
    'uni1_users_valid'
);

foreach ($tablesToTruncate as $tbl) {
    try {
        $db->query("TRUNCATE TABLE $tbl;");
        echo "  - Truncada tabla: $tbl\n";
    } catch (Exception $e) {
        // En caso de fallo por foreign key, intentar DELETE
        try {
            $db->query("DELETE FROM $tbl;");
            echo "  - Vaciada tabla (DELETE): $tbl\n";
        } catch (Exception $ex) {
            echo "  ! Advertencia al vaciar $tbl: " . $ex->getMessage() . "\n";
        }
    }
}

// 4. Eliminar usuarios y planetas secundarios (Purga Absoluta)
echo "[3/7] Purgando todos los usuarios y planetas/lunas excepto Admin (ID 1)...\n";
$db->query("DELETE FROM uni1_users WHERE id != 1;");
$db->query("DELETE FROM uni1_planets WHERE id_owner != 1 OR id != 1;");
echo "  -> Usuarios purgados: conservado exclusivamente admin (id=1)\n";
echo "  -> Planetas purgados: conservado exclusivamente planeta 1 (Principal [1:1:2])\n";

// 5. Resetear a Nivel 0 Planeta 1 de Admin
echo "[4/7] Reseteando planeta principal de Admin a Nivel 0 virginal...\n";
// Obtener columnas de uni1_planets
$planetCols = $db->select("SELECT COLUMN_NAME as Field FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'uni1_planets' AND TABLE_SCHEMA = 'ogame2';");
$colsToZero = array();
$ignoreColsPlanet = array(
    'id', 'name', 'id_owner', 'id_luna', 'universe', 'galaxy', 'system', 'planet',
    'planet_type', 'destruyed', 'b_building', 'b_building_id', 'b_hangar', 'b_hangar_id',
    'image', 'diameter', 'field_current', 'field_max', 'temp_min', 'temp_max',
    'metal', 'crystal', 'deuterium', 'eco_hash', 'last_update',
    'metal_perhour', 'crystal_perhour', 'deuterium_perhour',
    'metal_max', 'crystal_max', 'deuterium_max',
    'energy_used', 'energy'
);

foreach ($planetCols as $col) {
    $field = $col['Field'];
    if (in_array($field, $ignoreColsPlanet)) {
        continue;
    }
    if (strpos($field, '_porcent') !== false) {
        $colsToZero[] = "`$field` = '10'";
    } else {
        $colsToZero[] = "`$field` = '0'";
    }
}

// Re-cargar config para obtener valores
$config = Config::get();
$mStart = (float)$config->metal_start;
$cStart = (float)$config->crystal_start;
$dStart = (float)$config->deuterium_start;

$resetPlanetSql = "UPDATE uni1_planets SET 
    `id_luna` = 0,
    `destruyed` = 0,
    `b_building` = 0,
    `b_building_id` = '',
    `b_hangar` = 0,
    `b_hangar_id` = '',
    `field_current` = 0,
    `field_max` = 280,
    `metal` = $mStart,
    `crystal` = $cStart,
    `deuterium` = $dStart,
    `metal_perhour` = 0,
    `crystal_perhour` = 0,
    `deuterium_perhour` = 0,
    `energy_used` = 0,
    `energy` = 0,
    `eco_hash` = '',
    " . implode(", ", $colsToZero) . "
WHERE id = 1;";
$db->query($resetPlanetSql);
echo "  -> Planeta 1 reseteado con 0 campos construidos, 0 naves, 0 defensas y recursos de inicio: M: $mStart, C: $cStart, D: $dStart\n";

// 6. Resetear cuenta Admin a Nivel 0
echo "[5/7] Reseteando usuario Admin a Nivel 0 virginal...\n";
$userCols = $db->select("SELECT COLUMN_NAME as Field FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'uni1_users' AND TABLE_SCHEMA = 'ogame2';");
$colsToZeroUser = array();
$ignoreColsUser = array(
    'id', 'username', 'password', 'email', 'email_2', 'lang', 'authattack', 'authlevel',
    'rights', 'id_planet', 'universe', 'galaxy', 'system', 'planet', 'user_lastip',
    'ip_at_reg', 'register_time', 'onlinetime', 'dpath', 'timezone', 'planet_sort',
    'planet_sort_order', 'spio_anz', 'settings_fleetactions', 'settings_esp', 'settings_wri',
    'settings_bud', 'settings_mis', 'settings_blockPM', 'darkmatter', 'antimatter',
    'stardust', 'container', 'container_set', 'race', 'formgovernment', 'ethics',
    'foto', 'background', 'sound_ataks', 'setting_msg'
);

foreach ($userCols as $col) {
    $field = $col['Field'];
    if (in_array($field, $ignoreColsUser)) {
        continue;
    }
    $colsToZeroUser[] = "`$field` = '0'";
}

$dmStart = (float)$config->darkmatter_start;
$resetUserSql = "UPDATE uni1_users SET 
    `id_planet` = 1,
    `galaxy` = 1,
    `system` = 1,
    `planet` = 2,
    `darkmatter` = $dmStart,
    `antimatter` = 0,
    `stardust` = 0,
    `container` = 0,
    `container_set` = 10,
    `race` = 1500,
    `formgovernment` = 1600,
    `ethics` = 1700,
    `b_tech_planet` = 0,
    `b_tech` = 0,
    `b_tech_id` = 0,
    `b_tech_queue` = '',
    `bana` = 0,
    `banaday` = 0,
    `urlaubs_modus` = 0,
    `urlaubs_until` = 0,
    `ally_id` = 0,
    `ally_register_time` = 0,
    `ally_rank_id` = 0,
    `wons` = 0,
    `loos` = 0,
    `draws` = 0,
    `kbmetal` = 0,
    `kbcrystal` = 0,
    `lostunits` = 0,
    `desunits` = 0,
    " . implode(", ", $colsToZeroUser) . "
WHERE id = 1;";
$db->query($resetUserSql);
echo "  -> Admin reseteado con 0 investigaciones, 0 oficiales, 0 estadísticas y Materia Oscura inicial: $dmStart MO\n";

// 7. Regenerar estadísticas y vaciar cachés
echo "[6/7] Reconstruyendo estadísticas oficiales...\n";
$stat = new Statbuilder();
$stat->MakeStats();
echo "  -> Estadísticas reconstruidas limpiamente.\n";

echo "[7/7] Limpiando cachés del sistema...\n";
ClearCache();
echo "  -> Caché vaciado y cronjobs recalculados.\n";

echo "\n========================================================================\n";
echo "              REINICIO DE OLYSSIUM COMPLETADO EXITOSAMENTE              \n";
echo "========================================================================\n";

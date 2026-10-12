<?php
/**
 * NovaRush - Herramienta de Auditoría y Conciliación Contable Premium
 * Uso por CLI:
 *   php tools/audit_economy.php
 *   php tools/audit_economy.php --user=3
 */

define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
set_include_path(ROOT_PATH);
define('DATABASE_VERSION', 'OLD');
define('MODE', 'INGAME');

require_once 'includes/constants.php';
require_once 'includes/GeneralFunctions.php';
require_once 'includes/classes/Database.class.php';
require_once 'includes/classes/Config.class.php';
require_once 'includes/classes/PremiumEconomy.class.php';

$db = Database::get();

// Parse CLI options
$options = getopt('', array('user::'));
$filterUserId = isset($options['user']) ? (int) $options['user'] : 0;

$currencies = array(
    921 => array('name' => 'Materia Oscura (MO)', 'col' => 'darkmatter'),
    922 => array('name' => 'Antimateria (AM)',     'col' => 'antimatter'),
    923 => array('name' => 'Polvo Estelar (PE)',    'col' => 'stardust'),
    924 => array('name' => 'Contenedores (CT)',     'col' => 'container'),
);

echo "========================================================================================\n";
echo "            NOVARUSH — AUDITORÍA CONTABLE Y CONCILIACIÓN PREMIUM (ZERO-BYPASS)         \n";
echo "========================================================================================\n\n";

$userQuery = "SELECT id, username, darkmatter, antimatter, stardust, container, 
                     rpg_geologue, rpg_amiral, rpg_ingenieur, rpg_technocrate, 
                     rpg_constructeur, rpg_scientifique, rpg_commandant, rpg_general 
              FROM %%USERS%% WHERE authlevel = 0" 
           . ($filterUserId > 0 ? " AND id = {$filterUserId}" : "")
           . " ORDER BY id ASC;";

$users = $db->select($userQuery);

if (empty($users)) {
    echo "No se encontraron usuarios para auditar.\n";
    exit(0);
}

foreach ($users as $u) {
    echo "----------------------------------------------------------------------------------------\n";
    echo " JUGADOR: {$u['username']} (ID: {$u['id']})\n";
    
    // Oficiales activos
    $activeOfficers = array();
    foreach (array('geologue', 'amiral', 'ingenieur', 'technocrate', 'constructeur', 'scientifique', 'commandant', 'general') as $off) {
        $lvl = (int) $u['rpg_' . $off];
        if ($lvl > 0) {
            $activeOfficers[] = ucfirst($off) . ": Nvl {$lvl}";
        }
    }
    $offStr = empty($activeOfficers) ? "Ninguno (0)" : implode(', ', $activeOfficers);
    echo " Oficiales activos: {$offStr}\n";
    echo "----------------------------------------------------------------------------------------\n";
    printf("%-20s | %-12s | %-12s | %-12s | %-12s\n", "Divisa", "Saldo Actual", "Total Entradas", "Total Gastos", "Diferencia");
    echo "---------------------+--------------+--------------+--------------+---------------------\n";

    foreach ($currencies as $curId => $meta) {
        $actualBalance = (float) $u[$meta['col']];
        
        // Calcular sumatorias desde el libro contable
        $ledgerStats = $db->selectSingle(
            "SELECT 
                COALESCE(SUM(CASE WHEN delta > 0 THEN delta ELSE 0 END), 0) AS inflow,
                COALESCE(SUM(CASE WHEN delta < 0 THEN ABS(delta) ELSE 0 END), 0) AS outflow,
                COALESCE(SUM(delta), 0) AS net,
                COUNT(*) AS tx_count
             FROM %%PREMIUM_LEDGER%% 
             WHERE user_id = :u AND currency = :c;",
            array(':u' => $u['id'], ':c' => $curId)
        );

        $inflow = (float) $ledgerStats['inflow'];
        $outflow = (float) $ledgerStats['outflow'];
        $txCount = (int) $ledgerStats['tx_count'];

        // Último balance_after registrado en el ledger (si existe)
        $lastRow = $db->selectSingle(
            "SELECT balance_after FROM %%PREMIUM_LEDGER%% 
             WHERE user_id = :u AND currency = :c 
             ORDER BY id DESC LIMIT 1;",
            array(':u' => $u['id'], ':c' => $curId)
        );

        $lastAfter = $lastRow ? (float) $lastRow['balance_after'] : null;
        $status = "OK";
        if ($lastAfter !== null && abs($actualBalance - $lastAfter) > 0.01) {
            $diff = $actualBalance - $lastAfter;
            $status = sprintf("DISCREPANCIA (%+.0f)", $diff);
        } elseif ($txCount === 0 && $actualBalance > 0) {
            $status = "Sin registros";
        }

        printf("%-20s | %12.0f | %12.0f | %12.0f | %-12s\n", 
            $meta['name'], 
            $actualBalance, 
            $inflow, 
            $outflow, 
            $status
        );
    }

    // Desglose de fuentes para este usuario
    $breakdown = $db->select(
        "SELECT currency, source, COUNT(*) AS count, SUM(delta) AS total_delta
         FROM %%PREMIUM_LEDGER%% 
         WHERE user_id = :u 
         GROUP BY currency, source 
         ORDER BY currency, total_delta DESC;",
        array(':u' => $u['id'])
    );

    if (!empty($breakdown)) {
        echo "\n Desglose por origen/destino:\n";
        foreach ($breakdown as $b) {
            $curName = $currencies[$b['currency']]['name'] ?? "ID {$b['currency']}";
            $sign = $b['total_delta'] >= 0 ? '+' : '';
            printf("   • [%-18s] %-22s: %s%.0f (en %d transacciones)\n", 
                $curName, $b['source'], $sign, (float)$b['total_delta'], (int)$b['count']);
        }
    }
    echo "\n";
}

echo "========================================================================================\n";
echo " Auditoría finalizada exitosamente.\n";
echo "========================================================================================\n";

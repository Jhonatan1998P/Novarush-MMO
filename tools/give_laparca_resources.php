<?php
define('MODE', 'CRON');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
set_include_path(ROOT_PATH);

require 'includes/common.php';
require 'includes/vars/General.php';
require_once 'includes/classes/class.PlanetRessUpdate.php';
require_once 'includes/classes/class.statbuilder.php';

$db = Database::get();
$botId = 1007; // LaParca
$amountToAdd = 2500000000.0; // 2.5B (2.500 millones)

echo "====================================================================\n";
echo "   ENTREGA DE RECURSOS A LAPARCA (ID 1007) - 2.5B POR PLANETA     \n";
echo "====================================================================\n\n";

$planets = $db->select("SELECT * FROM %%PLANETS%% WHERE id_owner = :uid ORDER BY id ASC;", array(
    ':uid' => $botId
));

if (empty($planets)) {
    die("Error: No se encontraron planetas para el bot ID {$botId}.\n");
}

$userRow = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = :uid;", array(':uid' => $botId));
$userRow['factor'] = getFactors($userRow, 'basic', TIMESTAMP);
$resObj = new ResourceUpdate();

foreach ($planets as $p) {
    list($userRow, $pUpdated) = $resObj->CalcResource($userRow, $p, true, TIMESTAMP);

    $oldM = (float)$pUpdated['metal'];
    $oldC = (float)$pUpdated['crystal'];
    $oldD = (float)$pUpdated['deuterium'];

    $newM = $oldM + $amountToAdd;
    $newC = $oldC + $amountToAdd;
    $newD = $oldD + $amountToAdd;

    $db->update("UPDATE %%PLANETS%% SET metal = :m, crystal = :c, deuterium = :d WHERE id = :pid;", array(
        ':m'   => $newM,
        ':c'   => $newC,
        ':d'   => $newD,
        ':pid' => $p['id']
    ));

    echo sprintf("Planeta: %s [%d:%d:%d] (ID %d)\n", $p['name'], $p['galaxy'], $p['system'], $p['planet'], $p['id']);
    echo sprintf("  • Metal:     %' 16s  ->  %' 16s  (+%s)\n", number_format($oldM), number_format($newM), number_format($amountToAdd));
    echo sprintf("  • Cristal:   %' 16s  ->  %' 16s  (+%s)\n", number_format($oldC), number_format($newC), number_format($amountToAdd));
    echo sprintf("  • Deuterio:  %' 16s  ->  %' 16s  (+%s)\n\n", number_format($oldD), number_format($newD), number_format($amountToAdd));
}

echo "Actualizando estadísticas del universo...\n";
$stat = new Statbuilder();
$stat->MakeStats();

echo "Operación completada con éxito.\n";

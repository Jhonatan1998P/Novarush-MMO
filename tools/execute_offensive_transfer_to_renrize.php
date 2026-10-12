<?php
define('MODE', 'CRON');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
set_include_path(ROOT_PATH);

require 'includes/common.php';
require 'includes/vars/General.php';
require_once 'includes/classes/class.PlanetRessUpdate.php';
require_once 'includes/classes/class.statbuilder.php';

global $reslist, $resource;
$db = Database::get();

echo "====================================================================\n";
echo "   TRANSFERENCIA DE FLOTAS OFENSIVAS DE GODWAR -> RENRIZE [1:120:8] \n";
echo "====================================================================\n\n";

$userId = 4;
$targetPlanetId = 26; // Renrize [1:120:8]

$targetPlanet = $db->selectSingle("SELECT * FROM %%PLANETS%% WHERE id = :id AND id_owner = :uid;", array(
    ':id'  => $targetPlanetId,
    ':uid' => $userId
));

if (empty($targetPlanet)) {
    die("Error: Planeta Renrize (ID {$targetPlanetId}) no encontrado para el usuario {$userId}.\n");
}

// Lista oficial de IDs de flotas de combate / ofensivas
$combatIds = array_map('intval', explode(',', Config::get()->reslist_tablefleet_combat));

// Exclusiones explícitas de naves civiles, de transporte, reciclaje y especiales
$nonCombatIds = array(202, 203, 208, 209, 210, 212, 217, 219, 220, 223);

$planets = $db->select("SELECT * FROM %%PLANETS%% WHERE id_owner = :uid ORDER BY (id = :targetId) ASC;", array(
    ':uid'      => $userId,
    ':targetId' => $targetPlanetId
));

$transferredTotals = array();
$perPlanetManifest = array();

echo "1. Extrayendo flotas ofensivas de las colonias y bases...\n";

foreach ($planets as $p) {
    $pId = (int)$p['id'];
    if ($pId === $targetPlanetId) {
        continue;
    }

    $planetTransfers = array();
    $updateSet = array();
    $updateParams = array(':pId' => $pId);

    foreach ($reslist['fleet'] as $shipId) {
        $shipId = (int)$shipId;
        if (!in_array($shipId, $combatIds, true) || in_array($shipId, $nonCombatIds, true)) {
            continue; // Solo flotas ofensivas
        }

        if (!isset($resource[$shipId])) continue;
        $col = $resource[$shipId];
        $count = isset($p[$col]) ? (int)$p[$col] : 0;

        if ($count > 0) {
            $transferredTotals[$shipId] = (isset($transferredTotals[$shipId]) ? $transferredTotals[$shipId] : 0) + $count;
            $planetTransfers[$col] = $count;
            $updateSet[] = "{$col} = 0";
        }
    }

    if (!empty($updateSet)) {
        $sql = "UPDATE %%PLANETS%% SET " . implode(', ', $updateSet) . " WHERE id = :pId;";
        $db->update($sql, $updateParams);
        $perPlanetManifest[] = sprintf(
            "   - %s [%d:%d:%d] (ID %d): %s",
            $p['name'],
            $p['galaxy'],
            $p['system'],
            $p['planet'],
            $pId,
            json_encode($planetTransfers)
        );
    } else {
        $perPlanetManifest[] = sprintf(
            "   - %s [%d:%d:%d] (ID %d): (Sin naves ofensivas)",
            $p['name'],
            $p['galaxy'],
            $p['system'],
            $p['planet'],
            $pId
        );
    }
}

foreach ($perPlanetManifest as $line) {
    echo $line . "\n";
}

echo "\n2. Estacionando flotas ofensivas en Renrize [1:120:8]...\n";
if (!empty($transferredTotals)) {
    $renrizeUpdates = array();
    $renrizeParams = array(':rId' => $targetPlanetId);

    foreach ($transferredTotals as $shipId => $qty) {
        $col = $resource[$shipId];
        $renrizeUpdates[] = "{$col} = {$col} + :add_{$col}";
        $renrizeParams[":add_{$col}"] = $qty;
        echo sprintf("   + %-20s: +%s naves\n", ucfirst($col), number_format($qty));
    }

    $sqlRenrize = "UPDATE %%PLANETS%% SET " . implode(', ', $renrizeUpdates) . " WHERE id = :rId;";
    $db->update($sqlRenrize, $renrizeParams);
    echo "   -> Flotas integradas con éxito a la guarnición de Renrize.\n";
} else {
    echo "   -> No se encontraron naves ofensivas adicionales para transferir.\n";
}

// 3. Actualizar estadísticas del universo
echo "\n3. Actualizando puntos y estadísticas...\n";
$stat = new Statbuilder();
$stat->MakeStats();

// 4. Verificación del estado final en Renrize
$finalRenrize = $db->selectSingle("SELECT * FROM %%PLANETS%% WHERE id = :rId;", array(':rId' => $targetPlanetId));
echo "\n====================================================================\n";
echo "   ESTADO DEFENSIVO / OFENSIVO FINAL EN RENRIZE [1:120:8]\n";
echo "====================================================================\n";
$totalFleetOnRenrize = 0;
foreach ($reslist['fleet'] as $shipId) {
    $shipId = (int)$shipId;
    if (!isset($resource[$shipId])) continue;
    $col = $resource[$shipId];
    $count = isset($finalRenrize[$col]) ? (int)$finalRenrize[$col] : 0;
    if ($count > 0) {
        $isCombat = in_array($shipId, $combatIds, true) && !in_array($shipId, $nonCombatIds, true);
        echo sprintf("  • %-25s (ID %3d): %' 12d  [%s]\n", ucfirst($col), $shipId, $count, $isCombat ? 'OFENSIVA' : 'CIVIL/UTILIDAD');
        if ($isCombat) {
            $totalFleetOnRenrize += $count;
        }
    }
}
echo "--------------------------------------------------------------------\n";
echo "  TOTAL DE NAVES OFENSIVAS EN RENRIZE: " . number_format($totalFleetOnRenrize) . "\n";
echo "====================================================================\n";

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
echo "   TRANSFERENCIA DE FLOTAS DE GODWAR -> ATENEA [1:305:8]\n";
echo "   Categorías: Ofensivas, Reciclaje y Carga\n";
echo "====================================================================\n\n";

$userId = 4;
$targetPlanetId = 34; // Atenea [1:305:8]

$targetPlanet = $db->selectSingle("SELECT * FROM %%PLANETS%% WHERE id = :id AND id_owner = :uid;", array(
    ':id'  => $targetPlanetId,
    ':uid' => $userId
));

if (empty($targetPlanet)) {
    die("Error: Planeta Atenea (ID {$targetPlanetId}) no encontrado para el usuario {$userId}.\n");
}

// Categorías según requerimiento:
// 1. Ofensivas (Combate)
$combatIds = array_map('intval', explode(',', Config::get()->reslist_tablefleet_combat));

// 2. Reciclaje
$recyIds = array(209, 219);

// 3. Transporte / Carga
$transIds = array(202, 203, 217);

// Unir todas las permitidas
$allowedShipIds = array_unique(array_merge($combatIds, $recyIds, $transIds));

// Exclusiones explícitas obligatorias
// 212: Satélites solares (generadores fijos de energía)
// 210: Sondas de espionaje (especial / civil)
// 208: Naves colonizadoras
$excludedIds = array(212, 210, 208);
$allowedShipIds = array_diff($allowedShipIds, $excludedIds);

$planets = $db->select("SELECT * FROM %%PLANETS%% WHERE id_owner = :uid ORDER BY (id = :targetId) ASC;", array(
    ':uid'      => $userId,
    ':targetId' => $targetPlanetId
));

$transferredTotals = array();
$perPlanetManifest = array();

echo "1. Extrayendo flotas ofensivas, de reciclaje y carga de las bases...\n";

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
        if (!in_array($shipId, $allowedShipIds, true)) {
            continue;
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
            "   - %s [%d:%d:%d] (ID %d): (Sin naves para transferir)",
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

echo "\n2. Estacionando flotas en Atenea [1:305:8]...\n";
if (!empty($transferredTotals)) {
    $ateneaUpdates = array();
    $ateneaParams = array(':aId' => $targetPlanetId);

    foreach ($transferredTotals as $shipId => $qty) {
        $col = $resource[$shipId];
        $ateneaUpdates[] = "{$col} = {$col} + :add_{$col}";
        $ateneaParams[":add_{$col}"] = $qty;
        
        $cat = 'OFENSIVA';
        if (in_array($shipId, $recyIds, true)) $cat = 'RECICLAJE';
        elseif (in_array($shipId, $transIds, true)) $cat = 'CARGA';

        echo sprintf("   + %-20s (ID %3d): +%' 12s naves  [%s]\n", ucfirst($col), $shipId, number_format($qty), $cat);
    }

    $sqlAtenea = "UPDATE %%PLANETS%% SET " . implode(', ', $ateneaUpdates) . " WHERE id = :aId;";
    $db->update($sqlAtenea, $ateneaParams);
    echo "   -> Flotas integradas con éxito a la guarnición de Atenea.\n";
} else {
    echo "   -> No se encontraron naves para transferir.\n";
}

// 3. Actualizar estadísticas del universo
echo "\n3. Actualizando puntos y estadísticas...\n";
$stat = new Statbuilder();
$stat->MakeStats();

// 4. Verificación del estado final en Atenea
$finalAtenea = $db->selectSingle("SELECT * FROM %%PLANETS%% WHERE id = :aId;", array(':aId' => $targetPlanetId));
echo "\n====================================================================\n";
echo "   ESTADO FINAL DE FLOTAS ESTACIONADAS EN ATENEA [1:305:8]\n";
echo "====================================================================\n";
$totalFleetOnAtenea = 0;
foreach ($reslist['fleet'] as $shipId) {
    $shipId = (int)$shipId;
    if (!isset($resource[$shipId])) continue;
    $col = $resource[$shipId];
    $count = isset($finalAtenea[$col]) ? (int)$finalAtenea[$col] : 0;
    if ($count > 0) {
        $cat = 'OTRA';
        if (in_array($shipId, $combatIds, true) && !in_array($shipId, $excludedIds, true)) $cat = 'OFENSIVA';
        elseif (in_array($shipId, $recyIds, true)) $cat = 'RECICLAJE';
        elseif (in_array($shipId, $transIds, true)) $cat = 'CARGA';

        echo sprintf("  • %-25s (ID %3d): %' 12s  [%s]\n", ucfirst($col), $shipId, number_format($count), $cat);
        $totalFleetOnAtenea += $count;
    }
}
echo "--------------------------------------------------------------------\n";
echo "  TOTAL DE NAVES EN ATENEA: " . number_format($totalFleetOnAtenea) . "\n";
echo "====================================================================\n";

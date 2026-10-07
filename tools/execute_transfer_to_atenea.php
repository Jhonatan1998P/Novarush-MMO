<?php
define('MODE', 'CRON');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
set_include_path(ROOT_PATH);

require 'includes/common.php';
require 'includes/vars/General.php';
require_once 'includes/classes/class.PlanetRessUpdate.php';
require_once 'includes/classes/class.BuildFunctions.php';
require_once 'includes/classes/class.statbuilder.php';

global $reslist, $resource;
$db = Database::get();

echo "====================================================================\n";
echo "   CONSOLIDACIÓN TOTAL DE RECURSOS Y TROPAS PARA GODWAR -> ATENEA   \n";
echo "====================================================================\n\n";

$userId = 4;
$targetPlanetId = 34; // Atenea [1:305:8]

$user = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = :uid;", array(':uid' => $userId));
if (empty($user)) {
    die("Error: Usuario ID 4 no encontrado.\n");
}
$user['factor'] = getFactors($user, 'basic', TIMESTAMP);

// 1. Obtener y actualizar todos los planetas/lunas de Godwar
$planetsRaw = $db->select("SELECT * FROM %%PLANETS%% WHERE id_owner = :uid ORDER BY (id = :targetId) ASC;", array(
    ':uid' => $userId,
    ':targetId' => $targetPlanetId
));

$resObj = new ResourceUpdate();
$planets = array();
foreach ($planetsRaw as $pRaw) {
    list($user, $pUpdated) = $resObj->CalcResource($user, $pRaw, true, TIMESTAMP);
    $planets[$pUpdated['id']] = $pUpdated;
}

if (!isset($planets[$targetPlanetId])) {
    die("Error: Planeta Atenea (ID {$targetPlanetId}) no encontrado en el imperio de Godwar.\n");
}

// 2. Finalizar colas de astillero pendientes en todos los planetas
echo "1. Procesando y completando colas de astillero pendientes...\n";
$completedUnits = array();
foreach ($planets as $pId => &$p) {
    $q = !empty($p['b_hangar_id']) ? unserialize($p['b_hangar_id']) : array();
    if (!empty($q)) {
        foreach ($q as $item) {
            $elem = (int)$item[0];
            $count = (int)$item[1];
            if (isset($resource[$elem])) {
                $col = $resource[$elem];
                $p[$col] = (isset($p[$col]) ? (int)$p[$col] : 0) + $count;
                $completedUnits[$elem] = (isset($completedUnits[$elem]) ? $completedUnits[$elem] : 0) + $count;
            }
        }
        $p['b_hangar_id'] = '';
        $p['b_hangar'] = 0;
        echo "   - Planeta {$p['name']} (ID: {$pId}): cola completada.\n";
    }
}
unset($p);

if (!empty($completedUnits)) {
    echo "   -> Unidades finalizadas de colas: ";
    $parts = array();
    foreach ($completedUnits as $eId => $qty) {
        $name = isset($resource[$eId]) ? $resource[$eId] : "ID {$eId}";
        $parts[] = number_format($qty) . "x {$name}";
    }
    echo implode(', ', $parts) . "\n\n";
} else {
    echo "   -> No había colas pendientes.\n\n";
}

// 3. Consolidar recursos y flotas de las colonias/luna hacia Atenea
echo "2. Consolidando recursos y tropas hacia Atenea [1:305:8]...\n";
$metalMoved = 0.0;
$crystalMoved = 0.0;
$deutMoved = 0.0;
$shipsMoved = array();
$originManifest = array();

foreach ($planets as $pId => &$p) {
    if ($pId == $targetPlanetId) {
        continue; // Es Atenea, aquí recibimos
    }

    $pMetal = (float)$p['metal'];
    $pCrystal = (float)$p['crystal'];
    $pDeut = (float)$p['deuterium'];

    $metalMoved += $pMetal;
    $crystalMoved += $pCrystal;
    $deutMoved += $pDeut;

    $p['metal'] = 0;
    $p['crystal'] = 0;
    $p['deuterium'] = 0;

    $pShips = array();
    foreach ($reslist['fleet'] as $shipId) {
        if ((int)$shipId === 212) {
            continue; // Satélites solares permanecen para mantener energía de minas
        }
        if (!isset($resource[$shipId])) continue;
        $col = $resource[$shipId];
        $count = isset($p[$col]) ? (int)$p[$col] : 0;
        if ($count > 0) {
            $shipsMoved[$shipId] = (isset($shipsMoved[$shipId]) ? $shipsMoved[$shipId] : 0) + $count;
            $pShips[$col] = $count;
            $p[$col] = 0;
        }
    }

    $originManifest[] = sprintf(
        "   - %s [%d:%d:%d]: Transferido Metal: %s | Cristal: %s | Deuterio: %s | Naves: %s",
        $p['name'],
        $p['galaxy'],
        $p['system'],
        $p['planet'],
        number_format($pMetal),
        number_format($pCrystal),
        number_format($pDeut),
        empty($pShips) ? '0' : json_encode($pShips)
    );
}
unset($p);

foreach ($originManifest as $line) {
    echo $line . "\n";
}

// 4. Acreditar todo en Atenea
$atenea = &$planets[$targetPlanetId];
$ateneaPrevMetal = (float)$atenea['metal'];
$ateneaPrevCrystal = (float)$atenea['crystal'];
$ateneaPrevDeut = (float)$atenea['deuterium'];

$atenea['metal'] += $metalMoved;
$atenea['crystal'] += $crystalMoved;
$atenea['deuterium'] += $deutMoved;

foreach ($shipsMoved as $shipId => $count) {
    $col = $resource[$shipId];
    $atenea[$col] = (isset($atenea[$col]) ? (int)$atenea[$col] : 0) + $count;
}

// 5. Guardar en Base de Datos de forma atómica
echo "\n3. Persistiendo cambios en la base de datos...\n";
foreach ($planets as $pId => $p) {
    $setClauses = array(
        'metal = :metal',
        'crystal = :crystal',
        'deuterium = :deuterium',
        'b_hangar = :b_hangar',
        'b_hangar_id = :b_hangar_id'
    );
    $params = array(
        ':pId'        => $pId,
        ':metal'      => $p['metal'],
        ':crystal'    => $p['crystal'],
        ':deuterium'  => $p['deuterium'],
        ':b_hangar'   => $p['b_hangar'],
        ':b_hangar_id'=> $p['b_hangar_id']
    );

    foreach ($reslist['fleet'] as $shipId) {
        if ((int)$shipId === 212) continue;
        if (!isset($resource[$shipId])) continue;
        $col = $resource[$shipId];
        $setClauses[] = "{$col} = :{$col}";
        $params[":{$col}"] = (int)$p[$col];
    }

    $sql = "UPDATE %%PLANETS%% SET " . implode(', ', $setClauses) . " WHERE id = :pId;";
    $db->update($sql, $params);
}
echo "   -> Todos los planetas y lunas han sido actualizados con éxito.\n";

// 6. Actualizar ranking
echo "\n4. Actualizando ranking y estadísticas del universo...\n";
$stat = new Statbuilder();
$stat->MakeStats();
echo "   -> Ranking actualizado.\n";

// 7. Resumen final
echo "\n====================================================================\n";
echo "   RESUMEN FINAL DE TRANSFERENCIA A ATENEA [1:305:8]\n";
echo "====================================================================\n";
echo "RECURSOS TOTALES TRANSFERIDOS:\n";
echo "  • Metal:    +" . number_format($metalMoved) . "\n";
echo "  • Cristal:  +" . number_format($crystalMoved) . "\n";
echo "  • Deuterio: +" . number_format($deutMoved) . "\n\n";

echo "INVENTARIO FINAL DE RECURSOS EN ATENEA:\n";
echo "  • Metal:    " . number_format($atenea['metal']) . "\n";
echo "  • Cristal:  " . number_format($atenea['crystal']) . "\n";
echo "  • Deuterio: " . number_format($atenea['deuterium']) . "\n\n";

echo "TROPAS / FLOTAS TOTALES TRANSFERIDAS A ATENEA:\n";
foreach ($shipsMoved as $shipId => $count) {
    $name = isset($resource[$shipId]) ? $resource[$shipId] : "ID {$shipId}";
    echo "  • " . ucfirst($name) . ": +" . number_format($count) . "\n";
}

echo "\nINVENTARIO FINAL DE TROPAS EN ATENEA:\n";
foreach ($reslist['fleet'] as $shipId) {
    if ((int)$shipId === 212) continue;
    if (!isset($resource[$shipId])) continue;
    $col = $resource[$shipId];
    $count = (int)$atenea[$col];
    if ($count > 0) {
        echo "  • " . ucfirst($col) . ": " . number_format($count) . "\n";
    }
}
echo "====================================================================\n";

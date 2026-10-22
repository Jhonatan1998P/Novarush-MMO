<?php
define('MODE', 'CLI');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
chdir(ROOT_PATH);

require_once ROOT_PATH . 'includes/common.php';
require_once ROOT_PATH . 'includes/vars/General.php';
require_once ROOT_PATH . 'includes/classes/class.statbuilder.php';

global $resource, $reslist;

echo "========================================================================================\n";
echo "      LIQUIDACION Y CONCLUSION INMEDIATA DE TODAS LAS COLAS DE BOTS (SIN DAEMON)        \n";
echo "========================================================================================\n\n";

$db = Database::get();

// 1. Obtener lista de IDs de bots
$botRows = $db->select("SELECT bot_id FROM %%BOTS%%;");
$botIds = array();
foreach ($botRows as $r) {
    $botIds[] = (int)$r['bot_id'];
}

echo "Bots registrados en el sistema: " . count($botIds) . " cuentas.\n\n";

if (empty($botIds)) {
    die("ERROR: No se encontraron bots.\n");
}

$botIdsList = implode(',', $botIds);

// --------------------------------------------------------------------------------------
// 2. CONCLUSION DE COLAS DE RECLUTAMIENTO (FLOTA Y DEFENSA: b_hangar_id)
// --------------------------------------------------------------------------------------
echo "--- 1. PROCESANDO COLAS DE HANGAR (FLOTA Y DEFENSA) ---\n";
$hangarPlanets = $db->select("SELECT * FROM %%PLANETS%% WHERE id_owner IN ({$botIdsList}) AND b_hangar_id != '';");
echo "Planetas/Lunas con cola de hangar activa: " . count($hangarPlanets) . "\n";

$totalUnitsFinished = 0;
$unitsBreakdown = array();
$planetsHangarUpdated = 0;

foreach ($hangarPlanets as $p) {
    $pId = (int)$p['id'];
    $rawQueue = $p['b_hangar_id'];
    $queue = @unserialize($rawQueue);

    if (!is_array($queue) || empty($queue)) {
        $db->update("UPDATE %%PLANETS%% SET b_hangar = 0, b_hangar_id = '' WHERE id = :pId;", array(':pId' => $pId));
        continue;
    }

    $updateFields = array();
    foreach ($queue as $item) {
        $elementId = (int)$item[0];
        $count = (float)$item[1];

        if ($elementId <= 0 || $count <= 0) {
            continue;
        }

        $col = $resource[$elementId] ?? null;
        if ($col && isset($p[$col])) {
            $curVal = isset($updateFields[$col]) ? $updateFields[$col] : (float)$p[$col];
            $updateFields[$col] = $curVal + $count;

            $totalUnitsFinished += $count;
            $unitsBreakdown[$elementId] = ($unitsBreakdown[$elementId] ?? 0) + $count;
        }
    }

    $setClauses = array("b_hangar = 0", "b_hangar_id = ''");
    $params = array(':pId' => $pId);

    foreach ($updateFields as $col => $val) {
        $setClauses[] = "`{$col}` = :{$col}";
        $params[":{$col}"] = $val;
    }

    $setSql = implode(', ', $setClauses);
    $db->update("UPDATE %%PLANETS%% SET {$setSql} WHERE id = :pId;", $params);
    $planetsHangarUpdated++;
}

echo "   -> Finalizados " . count($hangarPlanets) . " hangares.\n";
echo "   -> Total unidades liquidadas a superficie: " . number_format($totalUnitsFinished) . "\n\n";

// --------------------------------------------------------------------------------------
// 3. CONCLUSION DE COLAS DE CONSTRUCCION DE EDIFICIOS (b_building_id)
// --------------------------------------------------------------------------------------
echo "--- 2. PROCESANDO COLAS DE EDIFICIOS (b_building_id) ---\n";
$buildPlanets = $db->select("SELECT * FROM %%PLANETS%% WHERE id_owner IN ({$botIdsList}) AND b_building_id != '';");
echo "Planetas/Lunas con obras activas: " . count($buildPlanets) . "\n";

$totalBuildingsFinished = 0;
$buildingsBreakdown = array();
$planetsBuildUpdated = 0;

foreach ($buildPlanets as $p) {
    $pId = (int)$p['id'];
    $rawQueue = $p['b_building_id'];
    $queue = @unserialize($rawQueue);

    if (!is_array($queue) || empty($queue)) {
        $db->update("UPDATE %%PLANETS%% SET b_building = 0, b_building_id = '' WHERE id = :pId;", array(':pId' => $pId));
        continue;
    }

    $workingPlanet = $p;
    $modifiedCols = array();

    foreach ($queue as $item) {
        $elementId = (int)$item[0];
        $targetLevel = (int)$item[1];
        $mode = (string)($item[4] ?? 'build');

        if ($elementId <= 0) {
            continue;
        }

        $col = $resource[$elementId] ?? null;
        if ($col && isset($workingPlanet[$col])) {
            if ($mode === 'destroy') {
                $workingPlanet[$col] = max(0, $targetLevel);
            } else {
                $workingPlanet[$col] = max((int)$workingPlanet[$col], $targetLevel);
            }
            $modifiedCols[$col] = $workingPlanet[$col];

            $totalBuildingsFinished++;
            $buildingsBreakdown[$elementId] = ($buildingsBreakdown[$elementId] ?? 0) + 1;
        }
    }

    // Recalcular field_current con la suma canónica de edificios
    $fieldCurrent = 0;
    foreach ($reslist['build'] as $bElemId) {
        $bCol = $resource[$bElemId] ?? null;
        if ($bCol && isset($workingPlanet[$bCol])) {
            $fieldCurrent += (int)$workingPlanet[$bCol];
        }
    }
    $modifiedCols['field_current'] = $fieldCurrent;

    $setClauses = array("b_building = 0", "b_building_id = ''");
    $params = array(':pId' => $pId);

    foreach ($modifiedCols as $col => $val) {
        $setClauses[] = "`{$col}` = :{$col}";
        $params[":{$col}"] = $val;
    }

    $setSql = implode(', ', $setClauses);
    $db->update("UPDATE %%PLANETS%% SET {$setSql} WHERE id = :pId;", $params);
    $planetsBuildUpdated++;
}

echo "   -> Finalizadas " . count($buildPlanets) . " colas de edificios.\n";
echo "   -> Total obras completadas: " . number_format($totalBuildingsFinished) . "\n\n";

// --------------------------------------------------------------------------------------
// 4. CONCLUSION DE INVESTIGACIONES TECNOLOGICAS (b_tech_id, b_tech_queue)
// --------------------------------------------------------------------------------------
echo "--- 3. PROCESANDO INVESTIGACIONES TECNOLOGICAS ---\n";
$techUsers = $db->select("SELECT * FROM %%USERS%% WHERE id IN ({$botIdsList}) AND (b_tech_id != 0 OR b_tech != 0 OR b_tech_queue != '');");
echo "Bots con investigaciones en curso: " . count($techUsers) . "\n";

$totalTechsFinished = 0;
$techsBreakdown = array();

foreach ($techUsers as $u) {
    $uId = (int)$u['id'];
    $workingUser = $u;
    $modifiedCols = array();

    // 1. Cola serializada b_tech_queue
    if (!empty($u['b_tech_queue'])) {
        $tQueue = @unserialize($u['b_tech_queue']);
        if (is_array($tQueue)) {
            foreach ($tQueue as $item) {
                $tId = (int)$item[0];
                $tLvl = (int)$item[1];
                $col = $resource[$tId] ?? null;
                if ($col && isset($workingUser[$col])) {
                    $workingUser[$col] = max((int)$workingUser[$col], $tLvl);
                    $modifiedCols[$col] = $workingUser[$col];

                    $totalTechsFinished++;
                    $techsBreakdown[$tId] = ($techsBreakdown[$tId] ?? 0) + 1;
                }
            }
        }
    }

    // 2. Tecnología activa individual b_tech_id
    if (!empty($u['b_tech_id'])) {
        $tId = (int)$u['b_tech_id'];
        $col = $resource[$tId] ?? null;
        if ($col && isset($workingUser[$col])) {
            $workingUser[$col] = max((int)$workingUser[$col], (int)$workingUser[$col] + 1);
            $modifiedCols[$col] = $workingUser[$col];

            $totalTechsFinished++;
            $techsBreakdown[$tId] = ($techsBreakdown[$tId] ?? 0) + 1;
        }
    }

    $setClauses = array("b_tech = 0", "b_tech_id = 0", "b_tech_planet = 0", "b_tech_queue = ''");
    $params = array(':uId' => $uId);

    foreach ($modifiedCols as $col => $val) {
        $setClauses[] = "`{$col}` = :{$col}";
        $params[":{$col}"] = $val;
    }

    $setSql = implode(', ', $setClauses);
    $db->update("UPDATE %%USERS%% SET {$setSql} WHERE id = :uId;", $params);
}

echo "   -> Finalizadas " . count($techUsers) . " investigaciones.\n";
echo "   -> Total tecnologías avanzadas: " . number_format($totalTechsFinished) . "\n\n";

// --------------------------------------------------------------------------------------
// 5. AUDITORIA FORENSE POST-PROCESO
// --------------------------------------------------------------------------------------
echo "--- 4. AUDITORIA POST-CONCLUSION DE COLAS ---\n";
$audit = $db->selectSingle("
SELECT 
    COUNT(CASE WHEN p.b_hangar_id != '' THEN 1 END) as pending_hangar,
    COUNT(CASE WHEN p.b_building_id != '' THEN 1 END) as pending_buildings,
    (SELECT COUNT(*) FROM %%USERS%% u JOIN %%BOTS%% b ON u.id = b.bot_id WHERE u.b_tech_id != 0 OR u.b_tech_queue != '' OR u.b_tech != 0) as pending_research
FROM %%PLANETS%% p
JOIN %%BOTS%% b ON p.id_owner = b.bot_id;
");

echo "Colas de Hangar pendientes en bots: " . (int)$audit['pending_hangar'] . "\n";
echo "Colas de Edificios pendientes en bots: " . (int)$audit['pending_buildings'] . "\n";
echo "Investigaciones pendientes en bots: " . (int)$audit['pending_research'] . "\n\n";

// --------------------------------------------------------------------------------------
// 6. ACTUALIZACION OFICIAL DE RANKING Y PUNTUACIONES
// --------------------------------------------------------------------------------------
echo "--- 5. RECALCULANDO ESTADISTICAS Y RANKING ---\n";
$stat = new Statbuilder();
$stat->MakeStats();
echo "Ranking y puntos actualizados correctamente.\n\n";

// --------------------------------------------------------------------------------------
// 7. DESGLOSE RESUMIDO
// --------------------------------------------------------------------------------------
echo "========================================================================================\n";
echo "                             RESUMEN DE UNIDADES LIQUIDADAS                             \n";
echo "========================================================================================\n";
arsort($unitsBreakdown);
foreach ($unitsBreakdown as $uId => $cnt) {
    $name = $resource[$uId] ?? "Unit #{$uId}";
    echo sprintf("   %-25s (ID %3d): %' 10s unidades\n", $name, $uId, number_format($cnt));
}
echo "----------------------------------------------------------------------------------------\n";
echo "TOTAL UNIDADES MILITARES ENTREGADAS A SUPERFICIE: " . number_format($totalUnitsFinished) . "\n";
echo "TOTAL EDIFICIOS FINALIZADOS: " . number_format($totalBuildingsFinished) . "\n";
echo "TOTAL TECNOLOGIAS FINALIZADAS: " . number_format($totalTechsFinished) . "\n";
echo "========================================================================================\n";

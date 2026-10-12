<?php

/**
 * NovaRush - Encolar en todas las colonias de -GODWAR- (User ID 4):
 * - Mina de Metal a nivel 40 (niveles 36..40)
 * - Mina de Cristal a nivel 39 (niveles 35..39)
 * - Sintetizador de Deuterio a nivel 37 (niveles 32..37)
 * Total: 16 niveles de construcción por colonia.
 * Se deduce el coste del elemento inicial (según la mecánica nativa de 2Moons)
 * y se configuran los timestamps reales de finalización para los 16 elementos.
 */

define('MODE', 'CLI');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
set_include_path(ROOT_PATH);

require_once 'includes/common.php';
require_once 'includes/vars/General.php';
require_once 'includes/classes/class.BuildFunctions.php';
require_once 'includes/classes/class.PlanetRessUpdate.php';

$db = Database::get();

// 1. Obtener usuario -GODWAR- (ID 4)
$user = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = 4;");
if (empty($user)) {
    echo "ERROR: Usuario ID 4 (-GODWAR-) no encontrado.\n";
    exit(1);
}
$user['factor'] = getFactors($user, 'basic', time());

// 2. Obtener las 8 colonias (planet_type = 1)
$planets = $db->select("SELECT * FROM %%PLANETS%% WHERE id_owner = 4 AND destruyed = 0 AND planet_type = 1 ORDER BY id ASC;");

if (empty($planets)) {
    echo "ERROR: No se encontraron colonias para el usuario 4.\n";
    exit(1);
}

echo "========================================================================\n";
echo "      ENCOLADO DE MINAS EN TODAS LAS COLONIAS DE -GODWAR- (ID 4)        \n";
echo "========================================================================\n\n";

// Definición de las mejoras requeridas
$upgrades = array(
    // Mina de Metal: 36..40 (5 niveles)
    array(1, 36, 'Mina de Metal'),
    array(1, 37, 'Mina de Metal'),
    array(1, 38, 'Mina de Metal'),
    array(1, 39, 'Mina de Metal'),
    array(1, 40, 'Mina de Metal'),
    // Mina de Cristal: 35..39 (5 niveles)
    array(2, 35, 'Mina de Cristal'),
    array(2, 36, 'Mina de Cristal'),
    array(2, 37, 'Mina de Cristal'),
    array(2, 38, 'Mina de Cristal'),
    array(2, 39, 'Mina de Cristal'),
    // Sintetizador de Deuterio: 32..37 (6 niveles)
    array(3, 32, 'Sintetizador Deuterio'),
    array(3, 33, 'Sintetizador Deuterio'),
    array(3, 34, 'Sintetizador Deuterio'),
    array(3, 35, 'Sintetizador Deuterio'),
    array(3, 36, 'Sintetizador Deuterio'),
    array(3, 37, 'Sintetizador Deuterio'),
);

$totalLevels = count($upgrades); // 16

$db->beginTransaction();

try {
    // Asegurar campo suficiente en todas las colonias (mínimo 350 base)
    $db->update("UPDATE %%PLANETS%% SET field_max = GREATEST(field_max, 350) WHERE id_owner = 4 AND destruyed = 0 AND planet_type = 1;");

    $reports = array();

    foreach ($planets as $p) {
        $pId = (int)$p['id'];
        $now = time();
        $accumTime = $now;
        $queue = array();

        $totalCostM = 0.0;
        $totalCostC = 0.0;

        foreach ($upgrades as $idx => $u) {
            $elemId    = $u[0];
            $targetLvl = $u[1];
            $expLvl    = $targetLvl - 1;

            $cost = BuildFunctions::getElementPrice($user, $p, $elemId, false, $expLvl);
            $time = max(1, (int)BuildFunctions::getBuildingTime($user, $p, $elemId, $cost, false, $expLvl));

            $endTime    = $accumTime + $time;
            $accumTime  = $endTime;

            $queue[] = array($elemId, $targetLvl, $time, $endTime, 'build');

            $totalCostM += (float)($cost[901] ?? 0.0);
            $totalCostC += (float)($cost[902] ?? 0.0);
        }

        // Coste del elemento 0 (deducido upfront al encolar según 2Moons)
        $cost0 = BuildFunctions::getElementPrice($user, $p, 1, false, 35);
        $cost0M = (float)($cost0[901] ?? 0.0);
        $cost0C = (float)($cost0[902] ?? 0.0);

        $newPlanetM = max(0.0, (float)$p['metal'] - $cost0M);
        $newPlanetC = max(0.0, (float)$p['crystal'] - $cost0C);
        $newPlanetD = (float)$p['deuterium'];

        $serializedQueue = serialize($queue);
        $firstEndTime = $queue[0][3];

        $db->update(
            "UPDATE %%PLANETS%% SET 
                metal = :m, 
                crystal = :c, 
                b_building = :b_building, 
                b_building_id = :b_building_id
             WHERE id = :id;",
            array(
                ':m'             => $newPlanetM,
                ':c'             => $newPlanetC,
                ':b_building'    => $firstEndTime,
                ':b_building_id' => $serializedQueue,
                ':id'            => $pId,
            )
        );

        $reports[] = array(
            'name'       => $p['name'],
            'id'         => $pId,
            'levels'     => $totalLevels,
            'time_total' => $accumTime - $now,
            'cost_total_m' => $totalCostM,
            'cost_total_c' => $totalCostC,
            'cost0_m'    => $cost0M,
            'cost0_c'    => $cost0C,
            'new_m'      => $newPlanetM,
            'new_c'      => $newPlanetC,
            'final_est_m' => max(0.0, (float)$p['metal'] - $totalCostM),
            'final_est_c' => max(0.0, (float)$p['crystal'] - $totalCostC),
        );
    }

    $db->commit();
    echo "¡Encolado ejecutado exitosamente en las 8 colonias!\n\n";

} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "ERROR EXCEPCIÓN: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "--- DETALLE DE COLAS ENCOLADAS POR COLONIA ---\n";
foreach ($reports as $r) {
    echo sprintf("Colonia %-12s [ID %2d]:\n", $r['name'], $r['id']);
    echo sprintf("  - Niveles Encolados:  16 mejoras (Metal 36->40, Cristal 35->39, Deut 32->37)\n");
    echo sprintf("  - Tiempo Total Cola:  %d segundos (~%4.2f min)\n", $r['time_total'], $r['time_total'] / 60);
    echo sprintf("  - Deducido Inicial:   M=%14s | C=%14s (Coste Nvl 36)\n", number_format($r['cost0_m'], 2), number_format($r['cost0_c'], 2));
    echo sprintf("  - Coste Total Cola:   M=%14s | C=%14s (16 niveles)\n", number_format($r['cost_total_m'], 2), number_format($r['cost_total_c'], 2));
    echo sprintf("  - Recursos Restantes: M=%14s | C=%14s\n\n", number_format($r['new_m'], 2), number_format($r['new_c'], 2));
}

echo "========================================================================\n";
echo "                    RESUMEN GLOBAL DEL IMPERIO                          \n";
echo "========================================================================\n";
$grandTotalCostM = $reports[0]['cost_total_m'] * 8;
$grandTotalCostC = $reports[0]['cost_total_c'] * 8;
echo sprintf("Total Mejoras Encoladas:     %d construcciones (16 en cada uno de los 8 planetas)\n", 16 * 8);
echo sprintf("Coste Total Metal Imperio:   %s\n", number_format($grandTotalCostM, 2));
echo sprintf("Coste Total Cristal Imperio: %s\n", number_format($grandTotalCostC, 2));
echo sprintf("Duración de cada cola:       %d segundos (~%4.2f minutos)\n", $reports[0]['time_total'], $reports[0]['time_total'] / 60);
echo "========================================================================\n";

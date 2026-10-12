<?php

/**
 * NovaRush - Rebalance -GODWAR- Empire Resources (50% M / 35% C / 15% D)
 * and Enqueue 18 Hours of Fleet (50% Cruceros, 30% Destructores, 20% Galeones) on each planet.
 */

define('MODE', 'CLI');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
set_include_path(ROOT_PATH);

require_once 'includes/common.php';
require_once 'includes/vars/General.php';
require_once 'includes/classes/class.BuildFunctions.php';
require_once 'includes/classes/class.PlanetRessUpdate.php';

$db = Database::get();

// 1. Fetch user -GODWAR- (ID 4)
$user = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = 4;");
if (empty($user)) {
    echo "ERROR: User ID 4 (-GODWAR-) not found.\n";
    exit(1);
}
$user['factor'] = getFactors($user, 'basic', time());

// 2. Fetch all 8 colonies (planet_type = 1) and any moons (planet_type = 3)
$planets = $db->select("SELECT * FROM %%PLANETS%% WHERE id_owner = 4 AND destruyed = 0 AND planet_type = 1 ORDER BY id ASC;");
$moons   = $db->select("SELECT * FROM %%PLANETS%% WHERE id_owner = 4 AND destruyed = 0 AND planet_type = 3 ORDER BY id ASC;");

$numPlanets = count($planets);
if ($numPlanets === 0) {
    echo "ERROR: No active colonies found for user 4.\n";
    exit(1);
}

echo "========================================================================\n";
echo "           NOVARUSH - REBALANCE & SHIPYARD QUEUE: -GODWAR-             \n";
echo "========================================================================\n\n";

// Sum all empire resources (8 colonies + moons)
$rawM = 0.0;
$rawC = 0.0;
$rawD = 0.0;

echo "--- ESTADO DE RECURSOS INICIALES ---\n";
foreach ($planets as $p) {
    $rawM += (float)$p['metal'];
    $rawC += (float)$p['crystal'];
    $rawD += (float)$p['deuterium'];
    $pMSE = (float)$p['metal'] + (2.0 * (float)$p['crystal']) + (4.0 * (float)$p['deuterium']);
    echo sprintf("Planeta %-12s [ID %2d]: M=%19s | C=%19s | D=%18s | MSE=%20s\n",
        $p['name'], $p['id'],
        number_format((float)$p['metal'], 2),
        number_format((float)$p['crystal'], 2),
        number_format((float)$p['deuterium'], 2),
        number_format($pMSE, 2)
    );
}

$moonM = 0.0;
$moonC = 0.0;
$moonD = 0.0;
if (!empty($moons)) {
    foreach ($moons as $m) {
        $moonM += (float)$m['metal'];
        $moonC += (float)$m['crystal'];
        $moonD += (float)$m['deuterium'];
        if ((float)$m['metal'] > 0 || (float)$m['crystal'] > 0 || (float)$m['deuterium'] > 0) {
            $mMSE = (float)$m['metal'] + (2.0 * (float)$m['crystal']) + (4.0 * (float)$m['deuterium']);
            echo sprintf("Luna    %-12s [ID %2d]: M=%19s | C=%19s | D=%18s | MSE=%20s\n",
                $m['name'], $m['id'],
                number_format((float)$m['metal'], 2),
                number_format((float)$m['crystal'], 2),
                number_format((float)$m['deuterium'], 2),
                number_format($mMSE, 2)
            );
        }
    }
}

$totalRawM = $rawM + $moonM;
$totalRawC = $rawC + $moonC;
$totalRawD = $rawD + $moonD;

// Calculate Total Empire MSE
$totalEmpireMSE = $totalRawM + (2.0 * $totalRawC) + (4.0 * $totalRawD);

echo "\n------------------------------------------------------------------------\n";
echo "Total Imperio Metal:     " . number_format($totalRawM, 2) . "\n";
echo "Total Imperio Cristal:   " . number_format($totalRawC, 2) . "\n";
echo "Total Imperio Deuterio:  " . number_format($totalRawD, 2) . "\n";
echo "TOTAL IMPERIO MSE:       " . number_format($totalEmpireMSE, 2) . " MSE\n";
echo "------------------------------------------------------------------------\n\n";

// Distribute Total MSE into 50% Metal, 35% Crystal, 15% Deuterium
$targetTotalM = round($totalEmpireMSE * 0.50, 2);
$targetTotalC = round($totalEmpireMSE * 0.35, 2);
$targetTotalD = round($totalEmpireMSE * 0.15, 2);

// Divide equally among the 8 colonies
$perColonyM = round($targetTotalM / $numPlanets, 2);
$perColonyC = round($targetTotalC / $numPlanets, 2);
$perColonyD = round($targetTotalD / $numPlanets, 2);

// Remainder assigned to the first colony (Vampire, home planet)
$remainderM = round($targetTotalM - ($perColonyM * $numPlanets), 2);
$remainderC = round($targetTotalC - ($perColonyC * $numPlanets), 2);
$remainderD = round($targetTotalD - ($perColonyD * $numPlanets), 2);

echo "--- REPARTO OBJETIVO (50% M / 35% C / 15% D) ENTRE 8 PLANETAS ---\n";
echo "Metal por planeta:    " . number_format($perColonyM, 2) . " (Resto en Vampire: " . number_format($remainderM, 2) . ")\n";
echo "Cristal por planeta:  " . number_format($perColonyC, 2) . " (Resto en Vampire: " . number_format($remainderC, 2) . ")\n";
echo "Deuterio por planeta: " . number_format($perColonyD, 2) . " (Resto en Vampire: " . number_format($remainderD, 2) . ")\n\n";

// Transaction start
$db->beginTransaction();

try {
    // 1. Update resources on moons to 0 if consolidated
    if (!empty($moons)) {
        foreach ($moons as $m) {
            $db->update("UPDATE %%PLANETS%% SET metal = 0, crystal = 0, deuterium = 0 WHERE id = :id;", array(':id' => $m['id']));
        }
    }

    $rebalancedPlanets = array();
    foreach ($planets as $idx => $p) {
        $pId = (int)$p['id'];
        $colonyM = $perColonyM + (($idx === 0) ? $remainderM : 0.0);
        $colonyC = $perColonyC + (($idx === 0) ? $remainderC : 0.0);
        $colonyD = $perColonyD + (($idx === 0) ? $remainderD : 0.0);

        $db->update(
            "UPDATE %%PLANETS%% SET metal = :m, crystal = :c, deuterium = :d WHERE id = :id;",
            array(
                ':m'  => $colonyM,
                ':c'  => $colonyC,
                ':d'  => $colonyD,
                ':id' => $pId,
            )
        );

        $p['metal']     = $colonyM;
        $p['crystal']   = $colonyC;
        $p['deuterium'] = $colonyD;
        $rebalancedPlanets[$pId] = $p;
    }

    echo "--- RECURSOS REBALANCEADOS EN 8 PLANETAS (PRE-COLA DE FLOTA) ---\n";
    foreach ($rebalancedPlanets as $pId => $p) {
        echo sprintf("Planeta %-12s [ID %2d]: M=%18s | C=%18s | D=%18s\n",
            $p['name'], $pId,
            number_format($p['metal'], 2),
            number_format($p['crystal'], 2),
            number_format($p['deuterium'], 2)
        );
    }

    echo "\n========================================================================\n";
    echo "       ENCOLANDO 18 HORAS DE FLOTA (50% CRU / 30% DES / 20% GAL)       \n";
    echo "========================================================================\n\n";

    $totalQueued = array(206 => 0, 213 => 0, 225 => 0);
    $totalDeducted = array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);
    $queueSummary = array();

    foreach ($rebalancedPlanets as $pId => &$p) {
        // Calculate construction times per unit on this planet
        $t206 = BuildFunctions::getBuildingTime($user, $p, 206);
        $t213 = BuildFunctions::getBuildingTime($user, $p, 213);
        $t225 = BuildFunctions::getBuildingTime($user, $p, 225);

        // 18 hours = 64,800 seconds total
        // 50% Cruceros (32,400s), 30% Destructores (19,440s), 20% Galeones (12,960s)
        $n206 = (int)round((64800.0 * 0.50) / $t206);
        $n213 = (int)round((64800.0 * 0.30) / $t213);
        $n225 = (int)round((64800.0 * 0.20) / $t225);

        $actualTime = ($n206 * $t206) + ($n213 * $t213) + ($n225 * $t225);

        // Calculate costs using getElementPrice
        $cost206 = BuildFunctions::getElementPrice($user, $p, 206, false, $n206);
        $cost213 = BuildFunctions::getElementPrice($user, $p, 213, false, $n213);
        $cost225 = BuildFunctions::getElementPrice($user, $p, 225, false, $n225);

        $costM = ($cost206[901] ?? 0.0) + ($cost213[901] ?? 0.0) + ($cost225[901] ?? 0.0);
        $costC = ($cost206[902] ?? 0.0) + ($cost213[902] ?? 0.0) + ($cost225[902] ?? 0.0);
        $costD = ($cost206[903] ?? 0.0) + ($cost213[903] ?? 0.0) + ($cost225[903] ?? 0.0);

        // Deduct from planet resources
        $newM = max(0.0, $p['metal'] - $costM);
        $newC = max(0.0, $p['crystal'] - $costC);
        $newD = max(0.0, $p['deuterium'] - $costD);

        // Construct queue
        $queue = array(
            array(206, $n206),
            array(213, $n213),
            array(225, $n225),
        );
        $serializedQueue = serialize($queue);

        // Update uni1_planets
        $db->update(
            "UPDATE %%PLANETS%% SET 
                metal = :m, 
                crystal = :c, 
                deuterium = :d, 
                b_hangar = 0,
                b_hangar_id = :b_hangar_id,
                last_update = :now
             WHERE id = :id;",
            array(
                ':m'           => $newM,
                ':c'           => $newC,
                ':d'           => $newD,
                ':b_hangar_id' => $serializedQueue,
                ':now'         => time(),
                ':id'          => $pId,
            )
        );

        $totalQueued[206] += $n206;
        $totalQueued[213] += $n213;
        $totalQueued[225] += $n225;

        $totalDeducted['metal']     += $costM;
        $totalDeducted['crystal']   += $costC;
        $totalDeducted['deuterium'] += $costD;

        $queueSummary[] = array(
            'name'       => $p['name'],
            'id'         => $pId,
            'cruceros'   => $n206,
            'destruct'   => $n213,
            'galeones'   => $n225,
            'hours'      => round($actualTime / 3600.0, 2),
            'cost_m'     => $costM,
            'cost_c'     => $costC,
            'cost_d'     => $costD,
            'final_m'    => $newM,
            'final_c'    => $newC,
            'final_d'    => $newD,
        );

        echo sprintf("Planeta %-12s [ID %2d]:\n", $p['name'], $pId);
        echo sprintf("  - Cruceros (206):     %7s unidades (%4.1fh)\n", number_format($n206), round($n206 * $t206 / 3600, 1));
        echo sprintf("  - Destructores (213): %7s unidades (%4.1fh)\n", number_format($n213), round($n213 * $t213 / 3600, 1));
        echo sprintf("  - Galeones (225):     %7s unidades (%4.1fh)\n", number_format($n225), round($n225 * $t225 / 3600, 1));
        echo sprintf("  - Tiempo Total Cola:  %4.2f horas (%s s)\n", $actualTime / 3600.0, number_format($actualTime));
        echo sprintf("  - Coste Deducido:     M=%16s | C=%16s | D=%15s\n", number_format($costM, 2), number_format($costC, 2), number_format($costD, 2));
        echo sprintf("  - Recursos Finales:   M=%16s | C=%16s | D=%15s\n\n", number_format($newM, 2), number_format($newC, 2), number_format($newD, 2));
    }

    $db->commit();

    echo "========================================================================\n";
    echo "                   RESUMEN GLOBAL DEL IMPERIO -GODWAR-                  \n";
    echo "========================================================================\n";
    echo sprintf("Total Cruceros Encolados:     %10s unidades\n", number_format($totalQueued[206]));
    echo sprintf("Total Destructores Encolados: %10s unidades\n", number_format($totalQueued[213]));
    echo sprintf("Total Galeones Encolados:     %10s unidades\n", number_format($totalQueued[225]));
    echo sprintf("TOTAL NAVES EN PRODUCCIÓN:    %10s unidades en 8 astilleros (18h cada uno)\n\n", number_format(array_sum($totalQueued)));

    echo sprintf("Coste Total Imperio: M=%18s | C=%18s | D=%18s\n",
        number_format($totalDeducted['metal'], 2),
        number_format($totalDeducted['crystal'], 2),
        number_format($totalDeducted['deuterium'], 2)
    );

    $finalEmpireM = 0.0;
    $finalEmpireC = 0.0;
    $finalEmpireD = 0.0;
    foreach ($queueSummary as $qs) {
        $finalEmpireM += $qs['final_m'];
        $finalEmpireC += $qs['final_c'];
        $finalEmpireD += $qs['final_d'];
    }
    $finalEmpireMSE = $finalEmpireM + (2.0 * $finalEmpireC) + (4.0 * $finalEmpireD);

    echo sprintf("Restante Imperio:    M=%18s | C=%18s | D=%18s | MSE=%18s\n",
        number_format($finalEmpireM, 2),
        number_format($finalEmpireC, 2),
        number_format($finalEmpireD, 2),
        number_format($finalEmpireMSE, 2)
    );
    echo "========================================================================\n";
    echo "¡EJECUCIÓN COMPLETADA CON ÉXITO AL 100%!\n";

} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "ERROR EXCEPCIÓN: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

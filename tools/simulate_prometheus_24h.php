<?php
define('MODE', 'CLI');
define('ROOT_PATH', 'C:/Users/Ortega/Downloads/OGAME/ogame/');
chdir(ROOT_PATH);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
require_once 'includes/common.php';
require_once 'includes/vars/General.php';
foreach (get_defined_vars() as $k => $v) {
    $GLOBALS[$k] = $v;
}
require_once 'includes/classes/bot/BotEngine.class.php';
require_once 'includes/classes/bot/Kernel/BotClock.class.php';
require_once 'includes/classes/bot/Evaluation/EconomyValuator.class.php';
require_once 'includes/classes/class.BuildFunctions.php';

$botId = 1011; // PROMETHEUS
$db = Database::get();
$pdo = $db->getHandle();

echo "========================================================================================\n";
echo "   NOVARUSH — SIMULACIÓN ESTRATÉGICA FIDEDIGNA DE 24 HORAS (PROMETHEUS #1011)\n";
echo "========================================================================================\n\n";

function computeStats($userId) {
    global $resource, $reslist, $pricelist;
    $db = Database::get();
    $user = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = :id", array(':id' => $userId));
    $user['factor'] = getFactors($user, 'basic', BotClock::get());
    $planets = $db->select("SELECT * FROM %%PLANETS%% WHERE id_owner = :id AND destruyed = 0 ORDER BY id ASC", array(':id' => $userId));
    $statSettings = max(1, (int)Config::get()->stat_settings);

    // 1. Build points
    $buildPtsRaw = 0;
    foreach ($planets as $p) {
        foreach ($reslist['build'] as $bId) {
            $lvl = (int)($p[$resource[$bId]] ?? 0);
            if ($lvl <= 0) continue;
            $resPts = BuildFunctions::resourcesPoints(null, $bId);
            $factor = isset($pricelist[$bId]['factor']) ? (float)$pricelist[$bId]['factor'] : 1.5;
            $buildPtsRaw += $resPts * $factor * (2 * (pow($factor, $lvl) - $factor) + 1);
        }
    }
    $buildPoints = floor($buildPtsRaw / $statSettings);

    // 2. Tech points
    $techPtsRaw = 0;
    foreach ($reslist['tech'] as $tId) {
        $lvl = (int)($user[$resource[$tId]] ?? 0);
        if ($lvl <= 0) continue;
        $resPts = BuildFunctions::resourcesPoints(null, $tId);
        $factor = isset($pricelist[$tId]['factor']) ? (float)$pricelist[$tId]['factor'] : 2.0;
        $techPtsRaw += $resPts * $factor * (2 * (pow($factor, $lvl) - $factor) + 1);
    }
    $techPoints = floor($techPtsRaw / $statSettings);

    // 3. Fleet points
    $fleetPtsRaw = 0;
    $fleetCounts = array();
    foreach ($reslist['fleet'] as $fId) {
        $count = 0;
        foreach ($planets as $p) {
            $count += (float)($p[$resource[$fId]] ?? 0);
        }
        $fleetCounts[$fId] = $count;
        $resPts = BuildFunctions::resourcesPoints(null, $fId);
        $fleetPtsRaw += $resPts * $count;
    }
    $fleetPoints = floor($fleetPtsRaw / $statSettings);

    // 4. Defense points
    $defPtsRaw = 0;
    $defCounts = array();
    foreach (array_merge($reslist['defense'], $reslist['missile']) as $dId) {
        $count = 0;
        foreach ($planets as $p) {
            $count += (float)($p[$resource[$dId]] ?? 0);
        }
        $defCounts[$dId] = $count;
        $resPts = BuildFunctions::resourcesPoints(null, $dId);
        $defPtsRaw += $resPts * $count;
    }
    $defensePoints = floor($defPtsRaw / $statSettings);

    $totalPoints = $buildPoints + $techPoints + $fleetPoints + $defensePoints;

    return array(
        'build'   => $buildPoints,
        'tech'    => $techPoints,
        'fleet'   => $fleetPoints,
        'defense' => $defensePoints,
        'total'   => $totalPoints,
        'fleet_counts' => $fleetCounts,
        'def_counts'   => $defCounts,
        'user'    => $user,
        'planets' => $planets,
    );
}

// Iniciar simulación en transacción protegida
$pdo->beginTransaction();

try {
    $initialStats = computeStats($botId);
    $initialPlanets = array();
    $initialStockEmpire = array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);

    foreach ($initialStats['planets'] as $p) {
        $pId = (int)$p['id'];
        $initialPlanets[$pId] = array(
            'id' => $pId,
            'name' => $p['name'],
            'coord' => "[{$p['galaxy']}:{$p['system']}:{$p['planet']}]",
            'metal' => (float)$p['metal'],
            'crystal' => (float)$p['crystal'],
            'deuterium' => (float)$p['deuterium'],
            'buildings' => array(),
        );
        $initialStockEmpire['metal'] += (float)$p['metal'];
        $initialStockEmpire['crystal'] += (float)$p['crystal'];
        $initialStockEmpire['deuterium'] += (float)$p['deuterium'];

        foreach ($reslist['build'] as $bId) {
            $initialPlanets[$pId]['buildings'][$bId] = (int)($p[$resource[$bId]] ?? 0);
        }
    }

    $initialTechs = array();
    foreach ($reslist['tech'] as $tId) {
        $initialTechs[$tId] = (int)($initialStats['user'][$resource[$tId]] ?? 0);
    }

    echo "Puntos Iniciales de PROMETHEUS:\n";
    echo "  - Edificios:       " . number_format($initialStats['build']) . " pts\n";
    echo "  - Investigaciones: " . number_format($initialStats['tech']) . " pts\n";
    echo "  - Flota:           " . number_format($initialStats['fleet']) . " pts\n";
    echo "  - Defensas:        " . number_format($initialStats['defense']) . " pts\n";
    echo "  - TOTAL:           " . number_format($initialStats['total']) . " pts\n\n";

    echo "Stock Inicial de Recursos:\n";
    echo "  - Metal:     " . number_format($initialStockEmpire['metal']) . "\n";
    echo "  - Cristal:   " . number_format($initialStockEmpire['crystal']) . "\n";
    echo "  - Deuterio:  " . number_format($initialStockEmpire['deuterium']) . "\n";
    $initialMSE = BotEconomyValuator::toMSE($initialStockEmpire['metal'], $initialStockEmpire['crystal'], $initialStockEmpire['deuterium']);
    echo "  - MSE Total: " . number_format($initialMSE) . "\n\n";

    $telemetry = array(
        'cycles_count'     => 0,
        'spent_by_category' => array(
            'mines'    => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
            'research' => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
            'fleet'    => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
            'defense'  => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
        ),
        'income_by_category' => array(
            'mines'    => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
            'research' => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
            'fleet'    => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
            'defense'  => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
        ),
        'planet_income'    => array(),
        'planet_spent'     => array(),
        'logistics_support' => array(), // [fromId][toId] => array(category, metal, crystal, deuterium, mse, transfers)
        'savings_events'   => array(),
        'recruited_total'  => array(),
        'upgraded_buildings' => array(),
        'upgraded_techs'   => array(),
    );

    foreach (array_keys($initialPlanets) as $pId) {
        $telemetry['planet_income'][$pId] = array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);
        $telemetry['planet_spent'][$pId]  = array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);
    }

    $simStart   = time();
    $simEnd     = $simStart + 86400; // 24 hours
    $stepDelta  = 300; // 5 minutos por ciclo = 288 ciclos exactos
    $totalSteps = (int)ceil(86400 / $stepDelta);

    echo "Ejecutando simulación macro de 24 horas ({$totalSteps} ciclos de 5 min)...\n";
    $simT0 = microtime(true);

    $seenConvoys = array();

    for ($step = 1; $step <= $totalSteps; $step++) {
        $simTime = $simStart + ($step * $stepDelta);
        BotClock::set($simTime);

        // Track new convoys dispatched before landing
        $pendingFleets = $db->select("SELECT fleet_id, fleet_start_id, fleet_end_id, fleet_resource_metal, fleet_resource_crystal, fleet_resource_deuterium FROM %%FLEETS%% WHERE fleet_owner = :id AND fleet_mission = 3 AND fleet_mess = 0;", array(':id' => $botId));
        foreach ($pendingFleets as $pf) {
            $fId = (int)$pf['fleet_id'];
            if (!isset($seenConvoys[$fId])) {
                $seenConvoys[$fId] = true;
                $fromP = (int)$pf['fleet_start_id'];
                $toP   = (int)$pf['fleet_end_id'];
                $cM    = (float)$pf['fleet_resource_metal'];
                $cC    = (float)$pf['fleet_resource_crystal'];
                $cD    = (float)$pf['fleet_resource_deuterium'];
                $cMSE  = BotEconomyValuator::toMSE($cM, $cC, $cD);

                if (!isset($telemetry['logistics_support'][$fromP][$toP])) {
                    $telemetry['logistics_support'][$fromP][$toP] = array(
                        'from_name' => $initialPlanets[$fromP]['name'] ?? "Colonia #{$fromP}",
                        'to_name'   => $initialPlanets[$toP]['name'] ?? "Colonia #{$toP}",
                        'metal'     => 0.0,
                        'crystal'   => 0.0,
                        'deuterium' => 0.0,
                        'mse'       => 0.0,
                        'transfers' => 0,
                    );
                }
                $telemetry['logistics_support'][$fromP][$toP]['metal'] += $cM;
                $telemetry['logistics_support'][$fromP][$toP]['crystal'] += $cC;
                $telemetry['logistics_support'][$fromP][$toP]['deuterium'] += $cD;
                $telemetry['logistics_support'][$fromP][$toP]['mse'] += $cMSE;
                $telemetry['logistics_support'][$fromP][$toP]['transfers']++;
            }
        }

        // Fleet arrivals
        require 'includes/FleetHandler.php';

        // Run bot turn
        $botRow = Database::get()->selectSingle("SELECT * FROM %%BOTS%% WHERE bot_id = :id;", array(':id' => $botId));
        $res = BotKernel::processTurn($botRow);

        if (isset($res['error'])) {
            echo "Error en ciclo {$step}: {$res['error']}\n";
            break;
        }

        $telemetry['cycles_count']++;

        // Track savings lock state
        $curLock = !empty($botRow['savings_lock']) ? @json_decode($botRow['savings_lock'], true) : null;
        if (!empty($curLock) && !empty($curLock['element_id'])) {
            $lockKey = $curLock['target_type'] . ':' . $curLock['element_id'];
            if (!isset($telemetry['savings_events'][$lockKey])) {
                $telemetry['savings_events'][$lockKey] = array(
                    'type'        => $curLock['target_type'],
                    'element_id'  => $curLock['element_id'],
                    'planet_id'   => $curLock['planet_id'],
                    'planet_name' => $initialPlanets[$curLock['planet_id']]['name'] ?? "Colonia #{$curLock['planet_id']}",
                    'cost'        => $curLock['cost'] ?? array(),
                    'deficit'     => $curLock['deficit_resource'] ?? 901,
                    'start_sim_h' => round(($step * $stepDelta) / 3600, 2),
                    'cycles_seen' => 1,
                );
            } else {
                $telemetry['savings_events'][$lockKey]['cycles_seen']++;
                $telemetry['savings_events'][$lockKey]['duration_hours'] = round(($telemetry['savings_events'][$lockKey]['cycles_seen'] * $stepDelta) / 3600, 2);
            }
        }

        // Track units recruited in this turn
        if (!empty($res['recruited']) && is_array($res['recruited'])) {
            foreach ($res['recruited'] as $uId => $cnt) {
                if (!isset($telemetry['recruited_total'][$uId])) {
                    $telemetry['recruited_total'][$uId] = 0;
                }
                $telemetry['recruited_total'][$uId] += (int)$cnt;
            }
        }

        // Progress bar
        if ($step % (int)($totalSteps / 10) === 0 || $step === $totalSteps) {
            $pct = round(($step / $totalSteps) * 100);
            $simHours = round(($step * $stepDelta) / 3600, 1);
            $realSec = round(microtime(true) - $simT0, 1);
            echo "  [{$pct}%] Simulado: {$simHours}h / 24h | Tiempo real: {$realSec}s\n";
        }
    }

    // Final arrival check at simEnd
    BotClock::set($simEnd);
    require 'includes/FleetHandler.php';

    // Flush all planet production to simEnd
    $updater = new ResourceUpdate();
    $curPlanetsRaw = $db->select("SELECT * FROM %%PLANETS%% WHERE id_owner = :id AND destruyed = 0;", array(':id' => $botId));
    $userRaw = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = :id;", array(':id' => $botId));
    $userRaw['factor'] = getFactors($userRaw, 'basic', $simEnd);
    foreach ($curPlanetsRaw as $pRaw) {
        $updater->CalcResource($userRaw, $pRaw, true, $simEnd);
    }

    $finalStats = computeStats($botId);
    $realElapsed = round(microtime(true) - $simT0, 2);
    echo "\nSimulación finalizada en {$realElapsed}s reales.\n\n";

    // Puntos
    $deltaBuildPoints = $finalStats['build'] - $initialStats['build'];
    $deltaTechPoints  = $finalStats['tech'] - $initialStats['tech'];
    $deltaFleetPoints = $finalStats['fleet'] - $initialStats['fleet'];
    $deltaDefPoints   = $finalStats['defense'] - $initialStats['defense'];
    $deltaTotalPoints = $finalStats['total'] - $initialStats['total'];

    // Detect upgraded buildings & calculate spending
    foreach ($finalStats['planets'] as $fp) {
        $pId = (int)$fp['id'];
        $pName = $fp['name'];
        foreach ($reslist['build'] as $bId) {
            $initLvl = (int)($initialPlanets[$pId]['buildings'][$bId] ?? 0);
            $finLvl  = (int)($fp[$resource[$bId]] ?? 0);
            if ($finLvl > $initLvl) {
                for ($l = $initLvl + 1; $l <= $finLvl; $l++) {
                    $cost = BuildFunctions::getElementPrice($initialStats['user'], $fp, $bId, false, $l);
                    $cM = (float)($cost[901] ?? 0);
                    $cC = (float)($cost[902] ?? 0);
                    $cD = (float)($cost[903] ?? 0);
                    $telemetry['spent_by_category']['mines']['metal'] += $cM;
                    $telemetry['spent_by_category']['mines']['crystal'] += $cC;
                    $telemetry['spent_by_category']['mines']['deuterium'] += $cD;
                    $telemetry['planet_spent'][$pId]['metal'] += $cM;
                    $telemetry['planet_spent'][$pId]['crystal'] += $cC;
                    $telemetry['planet_spent'][$pId]['deuterium'] += $cD;
                }
                $telemetry['upgraded_buildings'][] = array(
                    'planet_id'   => $pId,
                    'planet_name' => $pName,
                    'coord'       => "[{$fp['galaxy']}:{$fp['system']}:{$fp['planet']}]",
                    'element_id'  => $bId,
                    'old_level'   => $initLvl,
                    'new_level'   => $finLvl,
                );
            }
        }
    }

    // Detect upgraded tech
    foreach ($reslist['tech'] as $tId) {
        $initLvl = (int)($initialTechs[$tId] ?? 0);
        $finLvl  = (int)($finalStats['user'][$resource[$tId]] ?? 0);
        if ($finLvl > $initLvl) {
            for ($l = $initLvl + 1; $l <= $finLvl; $l++) {
                $cost = BuildFunctions::getElementPrice($initialStats['user'], $initialStats['planets'][0], $tId, false, $l);
                $cM = (float)($cost[901] ?? 0);
                $cC = (float)($cost[902] ?? 0);
                $cD = (float)($cost[903] ?? 0);
                $telemetry['spent_by_category']['research']['metal'] += $cM;
                $telemetry['spent_by_category']['research']['crystal'] += $cC;
                $telemetry['spent_by_category']['research']['deuterium'] += $cD;
            }
            $telemetry['upgraded_techs'][] = array(
                'tech_id'   => $tId,
                'old_level' => $initLvl,
                'new_level' => $finLvl,
            );
        }
    }

    // Fleet & defense spending from recruited units
    foreach ($telemetry['recruited_total'] as $uId => $cnt) {
        $price = $pricelist[$uId];
        $cM = (float)($price['cost'][901] ?? 0) * $cnt;
        $cC = (float)($price['cost'][902] ?? 0) * $cnt;
        $cD = (float)($price['cost'][903] ?? 0) * $cnt;

        if ($uId >= 400 && $uId < 600 && $uId != 503) {
            $telemetry['spent_by_category']['defense']['metal'] += $cM;
            $telemetry['spent_by_category']['defense']['crystal'] += $cC;
            $telemetry['spent_by_category']['defense']['deuterium'] += $cD;
        } else {
            $telemetry['spent_by_category']['fleet']['metal'] += $cM;
            $telemetry['spent_by_category']['fleet']['crystal'] += $cC;
            $telemetry['spent_by_category']['fleet']['deuterium'] += $cD;
        }
    }

    // Final stocks & total produced
    $finalStockEmpire = array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);
    foreach ($finalStats['planets'] as $fp) {
        $pId = (int)$fp['id'];
        $finalStockEmpire['metal'] += (float)$fp['metal'];
        $finalStockEmpire['crystal'] += (float)$fp['crystal'];
        $finalStockEmpire['deuterium'] += (float)$fp['deuterium'];

        // Planet production: finalStock + planetSpent - initialStock
        $pMProd = max(0.0, (float)$fp['metal'] + $telemetry['planet_spent'][$pId]['metal'] - (float)$initialPlanets[$pId]['metal']);
        $pCProd = max(0.0, (float)$fp['crystal'] + $telemetry['planet_spent'][$pId]['crystal'] - (float)$initialPlanets[$pId]['crystal']);
        $pDProd = max(0.0, (float)$fp['deuterium'] + $telemetry['planet_spent'][$pId]['deuterium'] - (float)$initialPlanets[$pId]['deuterium']);
        $telemetry['planet_income'][$pId] = array('metal' => $pMProd, 'crystal' => $pCProd, 'deuterium' => $pDProd);
    }

    $totalSpentGlobal = array(
        'metal'     => $telemetry['spent_by_category']['mines']['metal'] + $telemetry['spent_by_category']['research']['metal'] + $telemetry['spent_by_category']['fleet']['metal'] + $telemetry['spent_by_category']['defense']['metal'],
        'crystal'   => $telemetry['spent_by_category']['mines']['crystal'] + $telemetry['spent_by_category']['research']['crystal'] + $telemetry['spent_by_category']['fleet']['crystal'] + $telemetry['spent_by_category']['defense']['crystal'],
        'deuterium' => $telemetry['spent_by_category']['mines']['deuterium'] + $telemetry['spent_by_category']['research']['deuterium'] + $telemetry['spent_by_category']['fleet']['deuterium'] + $telemetry['spent_by_category']['defense']['deuterium'],
    );
    $totalSpentMSE = BotEconomyValuator::toMSE($totalSpentGlobal['metal'], $totalSpentGlobal['crystal'], $totalSpentGlobal['deuterium']);

    $totalProducedGlobal = array(
        'metal'     => max(0.0, $finalStockEmpire['metal'] + $totalSpentGlobal['metal'] - $initialStockEmpire['metal']),
        'crystal'   => max(0.0, $finalStockEmpire['crystal'] + $totalSpentGlobal['crystal'] - $initialStockEmpire['crystal']),
        'deuterium' => max(0.0, $finalStockEmpire['deuterium'] + $totalSpentGlobal['deuterium'] - $initialStockEmpire['deuterium']),
    );
    $totalProducedMSE = BotEconomyValuator::toMSE($totalProducedGlobal['metal'], $totalProducedGlobal['crystal'], $totalProducedGlobal['deuterium']);

    // Budget income totals (based on macro ratios: mines 30%, fleet 35%, def 15%, research 20%)
    $telemetry['income_by_category']['mines'] = array(
        'metal' => $totalProducedGlobal['metal'] * 0.30,
        'crystal' => $totalProducedGlobal['crystal'] * 0.30,
        'deuterium' => $totalProducedGlobal['deuterium'] * 0.30,
    );
    $telemetry['income_by_category']['fleet'] = array(
        'metal' => $totalProducedGlobal['metal'] * 0.35,
        'crystal' => $totalProducedGlobal['crystal'] * 0.35,
        'deuterium' => $totalProducedGlobal['deuterium'] * 0.35,
    );
    $telemetry['income_by_category']['defense'] = array(
        'metal' => $totalProducedGlobal['metal'] * 0.15,
        'crystal' => $totalProducedGlobal['crystal'] * 0.15,
        'deuterium' => $totalProducedGlobal['deuterium'] * 0.15,
    );
    $telemetry['income_by_category']['research'] = array(
        'metal' => $totalProducedGlobal['metal'] * 0.20,
        'crystal' => $totalProducedGlobal['crystal'] * 0.20,
        'deuterium' => $totalProducedGlobal['deuterium'] * 0.20,
    );

    $fullReport = array(
        'bot_name'               => 'PROMETHEUS',
        'bot_id'                 => 1011,
        'simulated_hours'        => 24,
        'real_duration_seconds'  => $realElapsed,
        'cycles_executed'        => $telemetry['cycles_count'],
        'points' => array(
            'initial' => array('build' => $initialStats['build'], 'tech' => $initialStats['tech'], 'fleet' => $initialStats['fleet'], 'defense' => $initialStats['defense'], 'total' => $initialStats['total']),
            'final'   => array('build' => $finalStats['build'], 'tech' => $finalStats['tech'], 'fleet' => $finalStats['fleet'], 'defense' => $finalStats['defense'], 'total' => $finalStats['total']),
            'delta'   => array('build' => $deltaBuildPoints, 'tech' => $deltaTechPoints, 'fleet' => $deltaFleetPoints, 'defense' => $deltaDefPoints, 'total' => $deltaTotalPoints),
        ),
        'economy' => array(
            'initial_stock'      => $initialStockEmpire,
            'final_stock'        => $finalStockEmpire,
            'total_produced'     => $totalProducedGlobal,
            'total_produced_mse' => $totalProducedMSE,
            'total_spent'        => $totalSpentGlobal,
            'total_spent_mse'    => $totalSpentMSE,
            'spent_by_category'  => $telemetry['spent_by_category'],
            'income_by_category' => $telemetry['income_by_category'],
            'planet_income'      => $telemetry['planet_income'],
            'planet_spent'       => $telemetry['planet_spent'],
            'planet_metadata'    => $initialPlanets,
        ),
        'units_recruited'        => $telemetry['recruited_total'],
        'fleet_start_vs_end'     => array(
            'start' => $initialStats['fleet_counts'],
            'end'   => $finalStats['fleet_counts'],
        ),
        'defense_start_vs_end'   => array(
            'start' => $initialStats['def_counts'],
            'end'   => $finalStats['def_counts'],
        ),
        'upgraded_buildings'     => $telemetry['upgraded_buildings'],
        'upgraded_techs'         => $telemetry['upgraded_techs'],
        'savings_events'         => $telemetry['savings_events'],
        'logistics_support'      => $telemetry['logistics_support'],
    );

    file_put_contents('tools/simulation_report_prometheus_24h.json', json_encode($fullReport, JSON_PRETTY_PRINT));
    echo "Reporte guardado exitosamente en tools/simulation_report_prometheus_24h.json\n";

} catch (Throwable $e) {
    echo "ERROR EN SIMULACIÓN: " . $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
} finally {
    $pdo->rollBack();
    BotClock::reset();
    echo "\n>>> TRANSACCIÓN REVERTIDA (ROLLBACK). BASE DE DATOS INTACTA Y CONSISTENTE. <<<\n";
}

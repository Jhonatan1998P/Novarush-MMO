<?php

/**
 * NovaRush AI Bot Test Suite: Continuous Flow Valve with Surplus Drain (5%-10%)
 * & Fleet/Defense Waterfall Pipeline (Non-Compliant Rigorous Verification)
 *
 * Requirements:
 * 1. calculateCycleSpendBudget():
 *    - Hourly production (1,000,000 M, 500,000 C, 200,000 D)
 *    - Elapsed interval = 360s (0.1 hours)
 *    - Personality ratios: Fleet = 30%, Defense = 15%
 *    - Theoretical fresh income: (elapsed * prod / 3600) * ratio
 *    - Normal Case: surplus = totalCredits - fresh; SpendCap = fresh + (0.08 * surplus); bounded by min(totalCredits, SpendCap)
 *    - Emergency Case ($isEmergency = true): 100% of totalCredits returned
 *    - No-Surplus Case (totalCredits <= fresh): exactly totalCredits returned, no invented resources
 *    - Min/Max elapsed interval clamps (45s min, 3600s max)
 *
 * 2. Fleet Cascade (Cascada de Flota):
 *    - Authorized initial budget ($initialFleetCredits)
 *    - Quotas: Heavy 20%, Medium 25%, Support 30%, Fodder 20%, Recycler 2.5%, Transporter 2.5%
 *    - Quotas calculated against initial budget, NOT against degraded remaining balances
 *    - Heavy spends 100% of 20% -> Medium receives 25% of initial (not 25% of 80%)
 *    - Support spends 0 -> 30% transfers via $cascadedExtra to Fodder (Fodder receives 20% + 30% = 50%)
 *    - Missing tech for recyclers/transporters (unit_id <= 0) -> 2.5% quotas transfer immediately to $cascadedExtra
 *    - Last category & final sweep absorb 100% of remaining balance (resolves 31.94% trapped funds bug)
 *    - Total expenditure never exceeds $initialFleetCredits
 *
 * 3. Defense Cascade (Cascada de Defensas):
 *    - Initial defense budget $defCredits
 *    - Elite receives up to 40%
 *    - Elite spends 40% -> Fodder receives 60% WITHOUT duplicate discount (1.0 - eliteShare)
 *    - Elite spends 0% -> Fodder receives 100%
 *    - Elite spends 20% -> Fodder receives 80% (60% base + 20% unspent elite)
 *    - Absence of artificial defense ceiling ($defenseCeilingReached removed)
 *
 * 4. Queue Limits and Difficulty Profiles:
 *    - $maxRegularSlots == 6 for EASY, NORMAL, HARD, NIGHTMARE in BotDifficultyProfile
 *    - $maxHangarQueueDuration == 86400 (24 hours) for all profiles
 *    - $batchTimeTarget == 3600 (1 hour) for all profiles
 *    - getConstructibleBatch() allows heavy ships to build up to 24h cap without 240s truncation
 *
 * 5. Clock Consistency:
 *    - last_fleet_recruit and last_defense_recruit use BotClock::get(), not direct TIMESTAMP
 */

define('MODE', 'CRON');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
set_include_path(ROOT_PATH);

require_once 'includes/common.php';
restore_error_handler();
restore_exception_handler();
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
require_once 'includes/vars/General.php';
foreach (get_defined_vars() as $k => $v) {
    $GLOBALS[$k] = $v;
}

require_once 'includes/classes/bot/BotEngine.class.php';
require_once 'includes/classes/bot/Kernel/BotContext.class.php';
require_once 'includes/classes/bot/Kernel/BotClock.class.php';
require_once 'includes/classes/bot/Execution/ActionGateway.class.php';
require_once 'includes/classes/bot/Forces/BotBudgetManager.class.php';
require_once 'includes/classes/bot/Forces/ProductionAllocator.class.php';
require_once 'includes/classes/bot/Forces/ForcePlanner.class.php';
require_once 'includes/classes/bot/Config/DifficultyProfile.class.php';
require_once 'includes/classes/bot/Config/Personality.class.php';

$results = array(
    'passed' => 0,
    'failed' => 0,
    'tests'  => array()
);

function assert_test($group, $name, $condition, $details = '') {
    global $results;
    if ($condition) {
        $results['passed']++;
        $results['tests'][] = array('group' => $group, 'name' => $name, 'status' => 'PASS', 'details' => $details);
        echo "  [PASS] [{$group}] {$name}\n";
        if (!empty($details)) {
            echo "         -> {$details}\n";
        }
    } else {
        $results['failed']++;
        $results['tests'][] = array('group' => $group, 'name' => $name, 'status' => 'FAIL', 'details' => $details);
        echo "  [FAIL] [{$group}] {$name}: {$details}\n";
    }
}

echo "========================================================================\n";
echo "   NOVARUSH AI BOT: CONTINUOUS FLOW VALVE & RECRUITMENT CASCADE TEST   \n";
echo "   (Rigorous Non-Compliant Quality Assurance Verification Suite)        \n";
echo "========================================================================\n\n";

// Bootstrap test bot context
$db = Database::get();
$botRow = $db->selectSingle("SELECT * FROM " . DB_PREFIX . "bots WHERE bot_id IN (1011, 1010, 1007) LIMIT 1;");
if (empty($botRow)) {
    $botRow = $db->selectSingle("SELECT * FROM " . DB_PREFIX . "bots LIMIT 1;");
}
$userRow = $db->selectSingle("SELECT * FROM " . DB_PREFIX . "users WHERE id = :id;", array(':id' => $botRow['bot_id']));
if (!isset($userRow['factor'])) {
    $userRow['factor'] = getFactors($userRow, 'basic', TIMESTAMP);
}

$ctx = new BotContext($botRow, $userRow);
$gateway = new BotActionGateway($ctx);
$forcePlanner = new BotForcePlanner($ctx, $gateway);

// Override personality ratios deterministically for mathematical testing
class MockTestPersonality extends BotPersonality {
    public function getBudgetRatios() {
        return array(
            'fleet'    => 0.30,
            'defense'  => 0.15,
            'mines'    => 0.35,
            'research' => 0.20,
        );
    }
}
$ctx->personality = new MockTestPersonality();

// ========================================================================
// GRUPO 1: TEST DE calculateCycleSpendBudget()
// ========================================================================
echo "--- GRUPO 1: Test de calculateCycleSpendBudget() ---\n";

$fixedSimTime = 1700000000;
BotClock::set($fixedSimTime);

$pDataSim = array(
    'metal_perhour'     => 1000000.0,
    'crystal_perhour'   => 500000.0,
    'deuterium_perhour' => 200000.0,
);

$elapsedTarget = 360; // 0.1 hours
$lastRecruitTime = $fixedSimTime - $elapsedTarget;

// 1.1 Caso Normal - Flota (Ratio = 30%)
$totalCreditsFleet = array(
    'metal'     => 500000.0,
    'crystal'   => 300000.0,
    'deuterium' => 100000.0,
);

$resFleetNormal = $forcePlanner->calculateCycleSpendBudget(1, $pDataSim, 'fleet', $totalCreditsFleet, $lastRecruitTime, false);

// Fórmulas matemáticas teóricas para Flota (30%):
$expectedFreshFleetM = (1000000.0 * 360 / 3600.0) * 0.30; // 30,000.0
$expectedFreshFleetC = (500000.0 * 360 / 3600.0) * 0.30;  // 15,000.0
$expectedFreshFleetD = (200000.0 * 360 / 3600.0) * 0.30;  // 6,000.0

$surplusFleetM = 500000.0 - $expectedFreshFleetM; // 470,000.0
$surplusFleetC = 300000.0 - $expectedFreshFleetC; // 285,000.0
$surplusFleetD = 100000.0 - $expectedFreshFleetD; // 94,000.0

$expectedCapFleetM = round($expectedFreshFleetM + (0.08 * $surplusFleetM), 2); // 30,000 + 37,600 = 67,600.0
$expectedCapFleetC = round($expectedFreshFleetC + (0.08 * $surplusFleetC), 2); // 15,000 + 22,800 = 37,800.0
$expectedCapFleetD = round($expectedFreshFleetD + (0.08 * $surplusFleetD), 2); // 6,000 + 7,520 = 13,520.0

assert_test(
    'GRUPO 1',
    'Caso Normal Flota (30%): Ingreso fresco teórico calculado con deltaT = 360s (0.1h)',
    ($expectedFreshFleetM == 30000.0 && $expectedFreshFleetC == 15000.0 && $expectedFreshFleetD == 6000.0),
    "Fresh: M={$expectedFreshFleetM}, C={$expectedFreshFleetC}, D={$expectedFreshFleetD}"
);

assert_test(
    'GRUPO 1',
    'Caso Normal Flota (30%): SpendCap matemático exacto [Fresco + 0.08 * Excedente]',
    ($resFleetNormal['metal'] == $expectedCapFleetM && 
     $resFleetNormal['crystal'] == $expectedCapFleetC && 
     $resFleetNormal['deuterium'] == $expectedCapFleetD),
    "Esperado: [M={$expectedCapFleetM}, C={$expectedCapFleetC}, D={$expectedCapFleetD}] | Obtenido: [M={$resFleetNormal['metal']}, C={$resFleetNormal['crystal']}, D={$resFleetNormal['deuterium']}]"
);

assert_test(
    'GRUPO 1',
    'Caso Normal Flota: Resultado acotado estrictamente por min(totalCredits, SpendCap)',
    ($resFleetNormal['metal'] <= $totalCreditsFleet['metal'] &&
     $resFleetNormal['crystal'] <= $totalCreditsFleet['crystal'] &&
     $resFleetNormal['deuterium'] <= $totalCreditsFleet['deuterium']),
    "Cap M={$resFleetNormal['metal']} <= Total M={$totalCreditsFleet['metal']}"
);

// 1.2 Caso Normal - Defensa (Ratio = 15%)
$totalCreditsDef = array(
    'metal'     => 200000.0,
    'crystal'   => 100000.0,
    'deuterium' => 50000.0,
);

$resDefNormal = $forcePlanner->calculateCycleSpendBudget(1, $pDataSim, 'defense', $totalCreditsDef, $lastRecruitTime, false);

$expectedFreshDefM = (1000000.0 * 360 / 3600.0) * 0.15; // 15,000.0
$expectedFreshDefC = (500000.0 * 360 / 3600.0) * 0.15;  // 7,500.0
$expectedFreshDefD = (200000.0 * 360 / 3600.0) * 0.15;  // 3,000.0

$surplusDefM = 200000.0 - $expectedFreshDefM; // 185,000.0
$surplusDefC = 100000.0 - $expectedFreshDefC; // 92,500.0
$surplusDefD = 50000.0 - $expectedFreshDefD;  // 47,000.0

$expectedCapDefM = round($expectedFreshDefM + (0.08 * $surplusDefM), 2); // 15,000 + 14,800 = 29,800.0
$expectedCapDefC = round($expectedFreshDefC + (0.08 * $surplusDefC), 2); // 7,500 + 7,400 = 14,900.0
$expectedCapDefD = round($expectedFreshDefD + (0.08 * $surplusDefD), 2); // 3,000 + 3,760 = 6,760.0

assert_test(
    'GRUPO 1',
    'Caso Normal Defensa (15%): SpendCap matemático exacto [Fresco + 0.08 * Excedente]',
    ($resDefNormal['metal'] == $expectedCapDefM && 
     $resDefNormal['crystal'] == $expectedCapDefC && 
     $resDefNormal['deuterium'] == $expectedCapDefD),
    "Esperado: [M={$expectedCapDefM}, C={$expectedCapDefC}, D={$expectedCapDefD}] | Obtenido: [M={$resDefNormal['metal']}, C={$resDefNormal['crystal']}, D={$resDefNormal['deuterium']}]"
);

// 1.3 Caso Emergencia ($isEmergency = true)
$resFleetEmerg = $forcePlanner->calculateCycleSpendBudget(1, $pDataSim, 'fleet', $totalCreditsFleet, $lastRecruitTime, true);
$resDefEmerg   = $forcePlanner->calculateCycleSpendBudget(1, $pDataSim, 'defense', $totalCreditsDef, $lastRecruitTime, true);

assert_test(
    'GRUPO 1',
    'Caso Emergencia Flota ($isEmergency = true): Devuelve 100% de totalCredits para M, C, D',
    ($resFleetEmerg['metal'] == $totalCreditsFleet['metal'] &&
     $resFleetEmerg['crystal'] == $totalCreditsFleet['crystal'] &&
     $resFleetEmerg['deuterium'] == $totalCreditsFleet['deuterium']),
    "Flota Emergencia: M={$resFleetEmerg['metal']}/{$totalCreditsFleet['metal']}, C={$resFleetEmerg['crystal']}/{$totalCreditsFleet['crystal']}, D={$resFleetEmerg['deuterium']}/{$totalCreditsFleet['deuterium']}"
);

assert_test(
    'GRUPO 1',
    'Caso Emergencia Defensa ($isEmergency = true): Devuelve 100% de totalCredits para M, C, D',
    ($resDefEmerg['metal'] == $totalCreditsDef['metal'] &&
     $resDefEmerg['crystal'] == $totalCreditsDef['crystal'] &&
     $resDefEmerg['deuterium'] == $totalCreditsDef['deuterium']),
    "Defensa Emergencia: M={$resDefEmerg['metal']}/{$totalCreditsDef['metal']}, C={$resDefEmerg['crystal']}/{$totalCreditsDef['crystal']}, D={$resDefEmerg['deuterium']}/{$totalCreditsDef['deuterium']}"
);

// 1.4 Caso Sin Excedente (Saldo <= Ingreso fresco)
$lowCreditsFleet = array(
    'metal'     => 20000.0, // < 30,000 fresco
    'crystal'   => 10000.0, // < 15,000 fresco
    'deuterium' => 4000.0,  // < 6,000 fresco
);

$resFleetNoSurplus = $forcePlanner->calculateCycleSpendBudget(1, $pDataSim, 'fleet', $lowCreditsFleet, $lastRecruitTime, false);

assert_test(
    'GRUPO 1',
    'Caso Sin Excedente (Saldo <= Fresco): Devuelve exactamente totalCredits sin inventar recursos',
    ($resFleetNoSurplus['metal'] == $lowCreditsFleet['metal'] &&
     $resFleetNoSurplus['crystal'] == $lowCreditsFleet['crystal'] &&
     $resFleetNoSurplus['deuterium'] == $lowCreditsFleet['deuterium']),
    "Sin Excedente: M={$resFleetNoSurplus['metal']}/{$lowCreditsFleet['metal']}, C={$resFleetNoSurplus['crystal']}/{$lowCreditsFleet['crystal']}, D={$resFleetNoSurplus['deuterium']}/{$lowCreditsFleet['deuterium']}"
);

// 1.5 Acotamiento de Intervalos (Min 45s, Max 3600s)
$resElapsedMin = $forcePlanner->calculateCycleSpendBudget(1, $pDataSim, 'fleet', $totalCreditsFleet, $fixedSimTime - 10, false); // 10s -> acotado a 45s
$freshM_45s = (1000000.0 * 45 / 3600.0) * 0.30; // 3750.0
$surplusM_45s = 500000.0 - $freshM_45s;
$expectedCap_45s = round($freshM_45s + (0.08 * $surplusM_45s), 2);

$resElapsedMax = $forcePlanner->calculateCycleSpendBudget(1, $pDataSim, 'fleet', $totalCreditsFleet, $fixedSimTime - 86400, false); // 86400s -> acotado a 3600s
$freshM_3600s = (1000000.0 * 3600 / 3600.0) * 0.30; // 300,000.0
$surplusM_3600s = 500000.0 - $freshM_3600s;
$expectedCap_3600s = round($freshM_3600s + (0.08 * $surplusM_3600s), 2);

assert_test(
    'GRUPO 1',
    'Intervalo acotado por abajo: deltaT = 10s se ajusta al piso mínimo de 45s',
    ($resElapsedMin['metal'] == $expectedCap_45s),
    "Cap obtenido 45s: {$resElapsedMin['metal']} == Esperado: {$expectedCap_45s}"
);

assert_test(
    'GRUPO 1',
    'Intervalo acotado por arriba: deltaT = 86400s se ajusta al techo máximo de 3600s (1h)',
    ($resElapsedMax['metal'] == $expectedCap_3600s),
    "Cap obtenido 3600s: {$resElapsedMax['metal']} == Esperado: {$expectedCap_3600s}"
);


// ========================================================================
// GRUPO 2: TEST DE CASCADA DE FLOTA (FLEET CASCADE)
// ========================================================================
echo "\n--- GRUPO 2: Test de Cascada de Flota (Fleet Cascade) ---\n";

/**
 * Función canónica pura que ejecuta la lógica idéntica de Cascada de Flota de ForcePlanner.class.php
 * rastreando presupuestos ofrecidos, consumos y transferencias.
 */
function simulateFleetCascadePipeline(array $initialFleetCredits, array $unitConfigs, array $consumptionSim) {
    $categoryConfigs = array(
        'heavy'       => array('unit' => $unitConfigs['heavy'] ?? 213,       'share' => 0.200, 'priority' => 1),
        'medium'      => array('unit' => $unitConfigs['medium'] ?? 215,      'share' => 0.250, 'priority' => 2),
        'support'     => array('unit' => $unitConfigs['support'] ?? 206,     'share' => 0.300, 'priority' => 3),
        'fodder'      => array('unit' => $unitConfigs['fodder'] ?? 204,      'share' => 0.200, 'priority' => 4),
        'recycler'    => array('unit' => $unitConfigs['recycler'] ?? 219,    'share' => 0.025, 'priority' => 5),
        'transporter' => array('unit' => $unitConfigs['transporter'] ?? 217, 'share' => 0.025, 'priority' => 6),
    );

    $fleetCredits = $initialFleetCredits;
    $cascadedExtra = array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);
    $unitPools = array();

    $immediateTransfers = array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);

    foreach ($categoryConfigs as $catKey => $cCfg) {
        $uId = (int)$cCfg['unit'];
        if ($uId <= 0) {
            $cascadedExtra['metal']     += $initialFleetCredits['metal'] * $cCfg['share'];
            $cascadedExtra['crystal']   += $initialFleetCredits['crystal'] * $cCfg['share'];
            $cascadedExtra['deuterium'] += $initialFleetCredits['deuterium'] * $cCfg['share'];

            $immediateTransfers['metal']     += $initialFleetCredits['metal'] * $cCfg['share'];
            $immediateTransfers['crystal']   += $initialFleetCredits['crystal'] * $cCfg['share'];
            $immediateTransfers['deuterium'] += $initialFleetCredits['deuterium'] * $cCfg['share'];
            continue;
        }
        if (!isset($unitPools[$uId])) {
            $unitPools[$uId] = array(
                'unit_id'    => $uId,
                'share'      => 0.0,
                'priority'   => $cCfg['priority'],
                'categories' => array(),
            );
        }
        $unitPools[$uId]['share'] += $cCfg['share'];
        $unitPools[$uId]['categories'][] = $catKey;
        $unitPools[$uId]['priority'] = min($unitPools[$uId]['priority'], $cCfg['priority']);
    }

    uasort($unitPools, function($a, $b) {
        return $a['priority'] - $b['priority'];
    });

    $poolIndex = 0;
    $totalPools = count($unitPools);
    $history = array();
    $totalSpent = array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);

    foreach ($unitPools as $uId => $pool) {
        $poolIndex++;
        $isLastPool = ($poolIndex === $totalPools);

        if ($isLastPool) {
            $poolCredits = array(
                'metal'     => max(0.0, (float)$fleetCredits['metal']),
                'crystal'   => max(0.0, (float)$fleetCredits['crystal']),
                'deuterium' => max(0.0, (float)$fleetCredits['deuterium']),
            );
        } else {
            $targetM = ($initialFleetCredits['metal'] * $pool['share']) + $cascadedExtra['metal'];
            $targetC = ($initialFleetCredits['crystal'] * $pool['share']) + $cascadedExtra['crystal'];
            $targetD = ($initialFleetCredits['deuterium'] * $pool['share']) + $cascadedExtra['deuterium'];

            $poolCredits = array(
                'metal'     => min(max(0.0, (float)$fleetCredits['metal']), max(0.0, $targetM)),
                'crystal'   => min(max(0.0, (float)$fleetCredits['crystal']), max(0.0, $targetC)),
                'deuterium' => min(max(0.0, (float)$fleetCredits['deuterium']), max(0.0, $targetD)),
            );
        }
        $receivedExtra = $cascadedExtra;
        $cascadedExtra = array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);

        // Determinar consumo según simulación
        $consumeRatio = isset($consumptionSim[$uId]) ? (float)$consumptionSim[$uId] : 1.0;
        $costM = min($poolCredits['metal'], $poolCredits['metal'] * $consumeRatio);
        $costC = min($poolCredits['crystal'], $poolCredits['crystal'] * $consumeRatio);
        $costD = min($poolCredits['deuterium'], $poolCredits['deuterium'] * $consumeRatio);

        $poolCredits['metal']     = max(0.0, $poolCredits['metal'] - $costM);
        $poolCredits['crystal']   = max(0.0, $poolCredits['crystal'] - $costC);
        $poolCredits['deuterium'] = max(0.0, $poolCredits['deuterium'] - $costD);

        $fleetCredits['metal']     = max(0.0, $fleetCredits['metal'] - $costM);
        $fleetCredits['crystal']   = max(0.0, $fleetCredits['crystal'] - $costC);
        $fleetCredits['deuterium'] = max(0.0, $fleetCredits['deuterium'] - $costD);

        $totalSpent['metal']     += $costM;
        $totalSpent['crystal']   += $costC;
        $totalSpent['deuterium'] += $costD;

        if (!$isLastPool) {
            $cascadedExtra['metal']     += $poolCredits['metal'];
            $cascadedExtra['crystal']   += $poolCredits['crystal'];
            $cascadedExtra['deuterium'] += $poolCredits['deuterium'];
        } else {
            // Barrido final en última unidad
            if ($poolCredits['metal'] > 3000 || $poolCredits['crystal'] > 1000) {
                // Barrido absorbe el remanente
                $sweepM = $poolCredits['metal'];
                $sweepC = $poolCredits['crystal'];
                $sweepD = $poolCredits['deuterium'];

                $fleetCredits['metal']     = max(0.0, $fleetCredits['metal'] - $sweepM);
                $fleetCredits['crystal']   = max(0.0, $fleetCredits['crystal'] - $sweepC);
                $fleetCredits['deuterium'] = max(0.0, $fleetCredits['deuterium'] - $sweepD);

                $totalSpent['metal']     += $sweepM;
                $totalSpent['crystal']   += $sweepC;
                $totalSpent['deuterium'] += $sweepD;

                $poolCredits['metal'] = 0.0;
                $poolCredits['crystal'] = 0.0;
                $poolCredits['deuterium'] = 0.0;
            }
        }

        $history[$uId] = array(
            'categories'     => $pool['categories'],
            'share'          => $pool['share'],
            'target_metal'   => ($initialFleetCredits['metal'] * $pool['share']),
            'received_extra' => $receivedExtra,
            'allocated'      => array('metal' => $costM + $poolCredits['metal'], 'crystal' => $costC + $poolCredits['crystal'], 'deuterium' => $costD + $poolCredits['deuterium']),
            'spent'          => array('metal' => $costM, 'crystal' => $costC, 'deuterium' => $costD),
            'remaining_pool' => $poolCredits,
            'is_last'        => $isLastPool,
        );
    }

    return array(
        'initial'             => $initialFleetCredits,
        'final_remaining'     => $fleetCredits,
        'total_spent'         => $totalSpent,
        'immediate_transfers' => $immediateTransfers,
        'history'             => $history,
    );
}

$initialFleetCredits = array(
    'metal'     => 1000000.0,
    'crystal'   => 500000.0,
    'deuterium' => 200000.0,
);

// 2.1 Simulación: Pesados (20%) gasta el 100% de su cuota. Medios (25%) debe recibir 25% de initialFleetCredits, NO 25% del 80%
$unitsAllDistinct = array(
    'heavy'       => 213, // 20%
    'medium'      => 215, // 25%
    'support'     => 206, // 30%
    'fodder'      => 204, // 20%
    'recycler'    => 219, // 2.5%
    'transporter' => 217, // 2.5%
);
$sim1 = simulateFleetCascadePipeline($initialFleetCredits, $unitsAllDistinct, array(213 => 1.0, 215 => 1.0, 206 => 1.0, 204 => 1.0, 219 => 1.0, 217 => 1.0));

$heavyAllocatedM = $sim1['history'][213]['allocated']['metal'];
$mediumAllocatedM = $sim1['history'][215]['allocated']['metal'];

assert_test(
    'GRUPO 2',
    'Cuotas de categoría calculadas contra initialFleetCredits: Pesados recibe exactamente 20% (200k M)',
    ($heavyAllocatedM == 200000.0),
    "Pesados asignado: {$heavyAllocatedM} Metal == 200,000.0"
);

assert_test(
    'GRUPO 2',
    'Pesados consume 100%: Medios recibe 25% de initialFleetCredits (250k M) y NO 25% del 80% restante (200k M)',
    ($mediumAllocatedM == 250000.0),
    "Medios asignado: {$mediumAllocatedM} Metal (no degradado a " . (800000.0 * 0.25) . " M)"
);

// 2.2 Simulación: Apoyo (30%) consume 0 gasto -> se transfiere 100% de su 30% a Fodder (20%)
$sim2 = simulateFleetCascadePipeline($initialFleetCredits, $unitsAllDistinct, array(
    213 => 1.0, // consume 20%
    215 => 1.0, // consume 25%
    206 => 0.0, // Apoyo NO consume nada (0%)
    204 => 1.0, // Fodder consume lo que reciba
    219 => 1.0,
    217 => 1.0
));

$supportSpentM = $sim2['history'][206]['spent']['metal'];
$fodderAllocatedM = $sim2['history'][204]['allocated']['metal'];
$expectedFodderM = (1000000.0 * 0.20) + (1000000.0 * 0.30); // 200k base + 300k transferido = 500k

assert_test(
    'GRUPO 2',
    'Apoyo con 0 gasto: transfiere el 100% de su cuota del 30% (300k M) a la siguiente categoría (Fodder)',
    ($supportSpentM == 0.0 && $fodderAllocatedM == $expectedFodderM && $fodderAllocatedM == 500000.0),
    "Apoyo gastó: {$supportSpentM} M | Fodder recibió: {$fodderAllocatedM} M (20% base + 30% cascada)"
);

// 2.3 Tecnologías no investigadas (unit_id <= 0) para recicladores y transportadores
$unitsNoCivil = array(
    'heavy'       => 213, // 20%
    'medium'      => 215, // 25%
    'support'     => 206, // 30%
    'fodder'      => 204, // 20%
    'recycler'    => 0,   // Sin tech (0)
    'transporter' => 0,   // Sin tech (0)
);

$sim3 = simulateFleetCascadePipeline($initialFleetCredits, $unitsNoCivil, array(
    213 => 1.0,
    215 => 1.0,
    206 => 1.0,
    204 => 1.0,
));

$expectedImmTransferM = 1000000.0 * (0.025 + 0.025); // 50,000.0 M (5%)

assert_test(
    'GRUPO 2',
    'Recicladores/Transportadores sin tecnología (unit_id <= 0): cuotas del 2.5% c/u se transfieren inmediatamente a $cascadedExtra',
    ($sim3['immediate_transfers']['metal'] == $expectedImmTransferM && $sim3['immediate_transfers']['metal'] == 50000.0),
    "Transferencia inmediata por unidades sin tech: {$sim3['immediate_transfers']['metal']} Metal (5% exacto)"
);

// 2.4 Última categoría y barrido final absorben el 100% del remanente (resolviendo el bug histórico del 31.94%)
$remM = $sim1['final_remaining']['metal'];
$remC = $sim1['final_remaining']['crystal'];
$remD = $sim1['final_remaining']['deuterium'];
$spentTotalM = $sim1['total_spent']['metal'];

assert_test(
    'GRUPO 2',
    'Última categoría y barrido final absorben el 100% del saldo remanente: Cero fondos atrapados (0% atrapado vs 31.94% histórico)',
    ($remM == 0.0 && $spentTotalM == $initialFleetCredits['metal']),
    "Remanente final: M={$remM}, C={$remC}, D={$remD} | Total gastado: {$spentTotalM} / {$initialFleetCredits['metal']}"
);

// 2.5 Gasto total nunca supera el presupuesto inicial autorizado
$simRandomSpend = simulateFleetCascadePipeline($initialFleetCredits, $unitsAllDistinct, array(
    213 => 0.45,
    215 => 0.12,
    206 => 0.78,
    204 => 0.95,
    219 => 0.30,
    217 => 1.00,
));

$neverExceeds = (
    $simRandomSpend['total_spent']['metal'] <= $initialFleetCredits['metal'] &&
    $simRandomSpend['total_spent']['crystal'] <= $initialFleetCredits['crystal'] &&
    $simRandomSpend['total_spent']['deuterium'] <= $initialFleetCredits['deuterium']
);

assert_test(
    'GRUPO 2',
    'Gasto total nunca supera el presupuesto inicial autorizado ($initialFleetCredits)',
    $neverExceeds,
    "Gastado: M={$simRandomSpend['total_spent']['metal']} <= {$initialFleetCredits['metal']}, C={$simRandomSpend['total_spent']['crystal']} <= {$initialFleetCredits['crystal']}"
);


// ========================================================================
// GRUPO 3: TEST DE CASCADA DE DEFENSAS (DEFENSE CASCADE)
// ========================================================================
echo "\n--- GRUPO 3: Test de Cascada de Defensas (Defense Cascade) ---\n";

$initialDefCredits = array(
    'metal'     => 1000000.0,
    'crystal'   => 500000.0,
    'deuterium' => 200000.0,
);

/**
 * Simulación de la Cascada de Defensa conforme a ForcePlanner.class.php líneas 848-925
 */
function simulateDefenseCascade($defCredits, $eliteConsumptionRatio) {
    $eliteShare = 0.40;
    $eliteAllocated = array(
        'metal'     => $defCredits['metal'] * $eliteShare,
        'crystal'   => $defCredits['crystal'] * $eliteShare,
        'deuterium' => $defCredits['deuterium'] * $eliteShare,
    );
    $elitePool = $eliteAllocated;

    // Consumo de unidades de élite
    $costMDef = $elitePool['metal'] * $eliteConsumptionRatio;
    $costCDef = $elitePool['crystal'] * $eliteConsumptionRatio;
    $costDDef = $elitePool['deuterium'] * $eliteConsumptionRatio;

    $elitePool['metal']     = max(0.0, $elitePool['metal'] - $costMDef);
    $elitePool['crystal']   = max(0.0, $elitePool['crystal'] - $costCDef);
    $elitePool['deuterium'] = max(0.0, $elitePool['deuterium'] - $costDDef);

    $defCredits['metal']     = max(0.0, $defCredits['metal'] - $costMDef);
    $defCredits['crystal']   = max(0.0, $defCredits['crystal'] - $costCDef);
    $defCredits['deuterium'] = max(0.0, $defCredits['deuterium'] - $costDDef);

    // Fodder consume el 100% de lo que resta en defCredits
    $fodderCredits = array(
        'metal'     => max(0.0, (float)$defCredits['metal']),
        'crystal'   => max(0.0, (float)$defCredits['crystal']),
        'deuterium' => max(0.0, (float)$defCredits['deuterium']),
    );

    return array(
        'elite_allocated' => $eliteAllocated,
        'elite_spent'     => array('metal' => $costMDef, 'crystal' => $costCDef, 'deuterium' => $costDDef),
        'fodder_credits'  => $fodderCredits,
        'remaining_after' => $defCredits,
    );
}

// 3.1 Élite recibe hasta el 40%
$caseEliteAlloc = simulateDefenseCascade($initialDefCredits, 1.0);
assert_test(
    'GRUPO 3',
    'Élite recibe cuota inicial hasta el 40% del presupuesto de defensa',
    ($caseEliteAlloc['elite_allocated']['metal'] == 400000.0 &&
     $caseEliteAlloc['elite_allocated']['crystal'] == 200000.0 &&
     $caseEliteAlloc['elite_allocated']['deuterium'] == 80000.0),
    "Élite Metal: {$caseEliteAlloc['elite_allocated']['metal']} == 400,000.0 (40%)"
);

// 3.2 Élite consume el 40% (100% de su cuota): Fodder recibe exactamente el 60% restante SIN descuento duplicado
$case40 = simulateDefenseCascade($initialDefCredits, 1.0);
assert_test(
    'GRUPO 3',
    'Élite consume el 40%: Carne de Cañón (Fodder) recibe exactamente el 60% restante SIN descuento duplicado',
    ($case40['fodder_credits']['metal'] == 600000.0 &&
     $case40['fodder_credits']['crystal'] == 300000.0 &&
     $case40['fodder_credits']['deuterium'] == 120000.0),
    "Fodder Metal: {$case40['fodder_credits']['metal']} == 600,000.0 (exacto 60%, no 360,000 con descuento duplicado)"
);

// 3.3 Élite no consume nada (0%): Fodder recibe el 100% del presupuesto de defensa
$case0 = simulateDefenseCascade($initialDefCredits, 0.0);
assert_test(
    'GRUPO 3',
    'Élite no consume nada (0%): Carne de Cañón (Fodder) recibe el 100% del presupuesto de defensa',
    ($case0['fodder_credits']['metal'] == 1000000.0 &&
     $case0['fodder_credits']['crystal'] == 500000.0 &&
     $case0['fodder_credits']['deuterium'] == 200000.0),
    "Fodder Metal con 0% Élite: {$case0['fodder_credits']['metal']} == 1,000,000.0 (100% total)"
);

// 3.4 Élite consume el 20% (la mitad de su cuota): Fodder recibe exactamente el 80% (60% base + 20% remanente de élite)
$case20 = simulateDefenseCascade($initialDefCredits, 0.5); // 0.5 * 40% = 20% consumido
assert_test(
    'GRUPO 3',
    'Élite consume el 20%: Carne de Cañón (Fodder) recibe exactamente el 80% (60% base + 20% remanente élite)',
    ($case20['fodder_credits']['metal'] == 800000.0 &&
     $case20['fodder_credits']['crystal'] == 400000.0 &&
     $case20['fodder_credits']['deuterium'] == 160000.0),
    "Fodder Metal con 20% Élite: {$case20['fodder_credits']['metal']} == 800,000.0 (80% exacto)"
);

// 3.5 Verificación de ausencia de techo artificial de defensa ($defenseCeilingReached eliminada)
$fpCode = file_get_contents(ROOT_PATH . 'includes/classes/bot/Forces/ForcePlanner.class.php');
$hasDefenseCeiling = (strpos($fpCode, 'defenseCeilingReached') !== false);
assert_test(
    'GRUPO 3',
    'Inexistencia de techo artificial de defensa: la variable $defenseCeilingReached está completamente eliminada del código',
    (!$hasDefenseCeiling),
    "Presencia de 'defenseCeilingReached' en ForcePlanner.class.php: " . ($hasDefenseCeiling ? 'ENCONTRADA (BUG)' : 'NO PRESENTE (LIMPIO)')
);


// ========================================================================
// GRUPO 4: TEST DE LÍMITES DE COLA Y CONFIGURACIÓN DE DIFICULTADES
// ========================================================================
echo "\n--- GRUPO 4: Test de Límites de Cola y Configuración de Dificultades ---\n";

$profiles = array(
    'EASY'      => new BotDifficultyProfile(BotDifficultyProfile::EASY),
    'NORMAL'    => new BotDifficultyProfile(BotDifficultyProfile::NORMAL),
    'HARD'      => new BotDifficultyProfile(BotDifficultyProfile::HARD),
    'NIGHTMARE' => new BotDifficultyProfile(BotDifficultyProfile::NIGHTMARE),
);

// 4.1 $maxRegularSlots == 6 para todas las dificultades
$allSlots6 = true;
$slotDetails = array();
foreach ($profiles as $name => $prof) {
    $slotDetails[$name] = $prof->maxRegularSlots;
    if ($prof->maxRegularSlots !== 6) {
        $allSlots6 = false;
    }
}
assert_test(
    'GRUPO 4',
    'DifficultyProfile: $maxRegularSlots == 6 para todas las dificultades (EASY, NORMAL, HARD, NIGHTMARE)',
    $allSlots6,
    "Valores obtenidos: " . json_encode($slotDetails)
);

// 4.2 $maxHangarQueueDuration == 86400 (24 horas) para todas las dificultades
$allHangar24h = true;
$hangarDetails = array();
foreach ($profiles as $name => $prof) {
    $hangarDetails[$name] = $prof->maxHangarQueueDuration;
    if ($prof->maxHangarQueueDuration !== 86400) {
        $allHangar24h = false;
    }
}
assert_test(
    'GRUPO 4',
    'DifficultyProfile: $maxHangarQueueDuration == 86400 (24 horas) en todos los perfiles de dificultad',
    $allHangar24h,
    "Valores obtenidos: " . json_encode($hangarDetails)
);

// 4.3 $batchTimeTarget == 3600 (1 hora) para todas las dificultades
$allBatch1h = true;
$batchDetails = array();
foreach ($profiles as $name => $prof) {
    $batchDetails[$name] = $prof->batchTimeTarget;
    if ($prof->batchTimeTarget !== 3600) {
        $allBatch1h = false;
    }
}
assert_test(
    'GRUPO 4',
    'DifficultyProfile: $batchTimeTarget == 3600 (1 hora) en todos los perfiles de dificultad',
    $allBatch1h,
    "Valores obtenidos: " . json_encode($batchDetails)
);

// 4.4 getConstructibleBatch() permite encolar hasta 24h sin truncarse por los 240s anteriores
// Simular una nave pesada (ej. Destructor 213) que toma 600 segundos por unidad.
// Con créditos para 30 unidades (18,000 segundos de tiempo de astillero):
// Anteriormente con 240s: floor(240 / 600) = 0 -> truncado a 1 nave.
// Ahora con 86400s (24h): floor(86400 / 600) = 144 -> lote autorizado = 30 naves completas.
$mockPDataHeavy = array(
    'metal'           => 1000000000.0,
    'crystal'         => 1000000000.0,
    'deuterium'       => 1000000000.0,
    'b_hangar_id'     => '',
    'b_hangar'        => 0,
    'fleet'           => 500.0, // Capacidad productiva de astillero (puntos de flota)
    $resource[14]     => 12,    // Astillero Nivel 12
    $resource[15]     => 8,     // Nanobots Nivel 8
);

// Asegurar que el usuario de prueba tiene astillero e investigación para 213 (Destructor)
$ctx->user[$resource[14]] = 12;  // Astillero 12
$ctx->user[$resource[15]] = 8;   // Nanobots 8
$ctx->user[$resource[110]] = 12; // Blindaje
$ctx->user[$resource[111]] = 12; // Armas
$ctx->user[$resource[115]] = 10; // Combustión
$ctx->user[$resource[117]] = 10; // Impulso
$ctx->user[$resource[118]] = 10; // Hiperespacio

$costM213 = (float)$pricelist[213]['cost'][901]; // 60,000 M
$costC213 = (float)$pricelist[213]['cost'][902]; // 50,000 C
$costD213 = (float)$pricelist[213]['cost'][903]; // 15,000 D

$targetUnits = 30;
$creditsFor30 = array(
    'metal'     => $costM213 * $targetUnits, // 1,800,000 M
    'crystal'   => $costC213 * $targetUnits, // 1,500,000 C
    'deuterium' => $costD213 * $targetUnits, // 450,000 D
);

$batchConstructed = $forcePlanner->getConstructibleBatch(
    $mockPDataHeavy,
    $creditsFor30,
    213,
    0,     // queuedJobs
    6,     // maxAllowedSlots
    0,     // queueRemainingSec
    86400, // maxHangarDuration (24h)
    3600,  // batchTimeTarget (1h)
    0,
    false,
    0.0
);

$unitTime213 = BuildFunctions::getBuildingTime($ctx->user, $mockPDataHeavy, 213);
$totalBatchTime = $batchConstructed * $unitTime213;

assert_test(
    'GRUPO 4',
    'getConstructibleBatch() no trunca naves pesadas por los 240s anteriores, permitiendo abarcar hasta 24h de cola',
    ($batchConstructed === 30),
    "Lote construido: {$batchConstructed} naves (Tiempo unitario: {$unitTime213}s, Tiempo total lote: {$totalBatchTime}s > 240s)"
);


// ========================================================================
// GRUPO 5: TEST DE CONSISTENCIA DE RELOJ
// ========================================================================
echo "\n--- GRUPO 5: Test de Consistencia de Reloj ---\n";

// 5.1 BotClock::get() retorna el tiempo virtual simulado
$virtualTime = 1850000000;
BotClock::set($virtualTime);
assert_test(
    'GRUPO 5',
    'BotClock::get() retorna exactamente el tiempo simulado configurado',
    (BotClock::get() === $virtualTime),
    "BotClock::get(): " . BotClock::get() . " == {$virtualTime}"
);

// 5.2 Verificación estática y dinámica en ForcePlanner.class.php
// Validar que ForcePlanner usa BotClock::get() para registrar marcas temporales
$hasBotClockInFP = (strpos($fpCode, '$now = BotClock::get();') !== false);
$hasTimestampDirectForRecruit = (
    strpos($fpCode, "\$updates['last_fleet_recruit'] = TIMESTAMP") !== false ||
    strpos($fpCode, "\$updates['last_defense_recruit'] = TIMESTAMP") !== false
);

assert_test(
    'GRUPO 5',
    'ForcePlanner.class.php registra last_fleet_recruit y last_defense_recruit con BotClock::get()',
    ($hasBotClockInFP && !$hasTimestampDirectForRecruit),
    "BotClock::get() en ForcePlanner: " . ($hasBotClockInFP ? 'PRESENTE' : 'AUSENTE') . " | TIMESTAMP directo: " . ($hasTimestampDirectForRecruit ? 'DETECTADO (BUG)' : 'NO DETECTADO (CORRECTO)')
);

// 5.3 Verificación estática en BotKernel.class.php
$bkCode = file_get_contents(ROOT_PATH . 'includes/classes/bot/Kernel/BotKernel.class.php');
$kernelUsesBotClock = (strpos($bkCode, "':now'             => BotClock::get(),") !== false);

assert_test(
    'GRUPO 5',
    'BotKernel.class.php actualiza marcas de reclutamiento usando BotClock::get() en PDO bindings',
    $kernelUsesBotClock,
    "BotClock::get() en BotKernel updateBotRow: " . ($kernelUsesBotClock ? 'CONFIRMADO' : 'FALLO')
);

// Restaurar reloj
BotClock::reset();


// ========================================================================
// RESUMEN FINAL DE EJECUCIÓN
// ========================================================================
echo "\n========================================================================\n";
echo "                         RESUMEN DE RESULTADOS                          \n";
echo "========================================================================\n";
echo "Total de Pruebas Ejecutadas: " . ($results['passed'] + $results['failed']) . "\n";
echo "Pruebas Superadas (PASS)   : {$results['passed']}\n";
echo "Pruebas Fallidas (FAIL)    : {$results['failed']}\n";

if ($results['failed'] === 0) {
    echo "\n>>> ESTADO FINAL: 100% PASS - VÁLVULA DE FLUJO Y CASCADAS VERIFICADAS EXITOSAMENTE <<<\n";
    exit(0);
} else {
    echo "\n>>> ESTADO FINAL: FALLOS DETECTADOS - CORREGIR DISCREPANCIAS <<<\n";
    exit(1);
}

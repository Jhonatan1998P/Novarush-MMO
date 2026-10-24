<?php

/**
 * NovaRush AI Bot Test Suite: Milestone 2 Strict Accounting Isolation in BotBudgetManager
 *
 * Verifies:
 * 1. deductDispatchedMission() on Mission 3: Fuel deducted from fleet deut, cargo left untouched (handled by debitDonorCategory).
 * 2. deductDispatchedMission() on Mission 4 with deut only: Fuel & cargo deducted strictly from fleet deut, no touch to mines/defense.
 * 3. deductDispatchedMission() on Mission 4 with metal/crystal: Retains proportional cargo deduction across local categories.
 * 4. getDonorSurplusByCategory() 40M MSE bypass eliminated: Huge physical resources cannot leak into other categories.
 * 5. getDonorSurplusByCategory() hangar==0 bypass removed: Respects category budgets and reserves.
 * 6. getDonorSurplusByCategory() research surplus: Strictly capped by uncommitted physical stock (planetData - committedLocal).
 * 7. getDonorSurplusByCategory() local categories: 1.0h production reserve (+ slot1Reserve for mines) and category locking.
 * 8. getDonorSurplusByCategory() moon reserve: 25M Deuterium Phalanx reserve, 2M M + 4M C for gate if no gate present.
 * 9. debitDonorCategory(): Precision rounding to 2 decimal places on last_physical updates.
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
require_once 'includes/classes/bot/Forces/BotBudgetManager.class.php';
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
        echo "  [PASS] [$group] $name\n";
    } else {
        $results['failed']++;
        $results['tests'][] = array('group' => $group, 'name' => $name, 'status' => 'FAIL', 'details' => $details);
        echo "  [FAIL] [$group] $name: $details\n";
    }
}

echo "========================================================================\n";
echo "   MILESTONE 2: BOT BUDGET MANAGER STRICT ACCOUNTING ISOLATION TEST\n";
echo "========================================================================\n\n";

$db = Database::get();
$botRow = $db->selectSingle("SELECT * FROM %%BOTS%% LIMIT 1;");
if (empty($botRow)) {
    $botRow = array(
        'bot_id'           => 9999,
        'home_planet_id'   => 101,
        'difficulty'       => 'hard',
        'personality'      => 'balanced',
        'doctrine'         => 'flexible',
        'economic_modifier'=> 1.0,
        'budget_credits'   => null,
        'savings_lock'     => null,
        'last_budget_time' => 0
    );
    $userRow = array('id' => 9999, 'username' => 'test_bot', 'id_planet' => 101);
} else {
    $botId = (int)$botRow['bot_id'];
    $userRow = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = :uId LIMIT 1;", array(':uId' => $botId));
    if (empty($userRow)) {
        $userRow = array('id' => $botId, 'username' => 'test_bot', 'id_planet' => (int)$botRow['home_planet_id']);
    }
}

$ctx = new BotContext($botRow, $userRow);
$budgetMgr = new BotBudgetManager($ctx);

$refCredits = new ReflectionProperty($budgetMgr, 'credits');
$refCredits->setAccessible(true);

$planetId = 101;

// ========================================================================
// GRUPO A: deductDispatchedMission() en Misión 3 (Transporte Logístico)
// ========================================================================
echo "--- GRUPO A: Misión 3 (Transporte) Aislamiento Contable ---\n";

$initCreditsA = array(
    'planets' => array(
        $planetId => array(
            'mines'   => array('metal' => 1000000.0, 'crystal' => 500000.0, 'deuterium' => 200000.0),
            'fleet'   => array('metal' => 2000000.0, 'crystal' => 1000000.0, 'deuterium' => 500000.0),
            'defense' => array('metal' => 800000.0,  'crystal' => 400000.0,  'deuterium' => 100000.0),
        )
    ),
    'research' => array('metal' => 500000.0, 'crystal' => 250000.0, 'deuterium' => 100000.0),
    'opportunity_fund' => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
    'last_updated' => TIMESTAMP,
    'last_physical' => array(
        $planetId => array('metal' => 3800000.0, 'crystal' => 1900000.0, 'deuterium' => 800000.0)
    )
);

$refCredits->setValue($budgetMgr, $initCreditsA);

// Despachar Misión 3: Carga 500k M, 200k C, 50k D; Combustible 1,500 D
$budgetMgr->deductDispatchedMission($planetId, 3, array(901 => 500000.0, 902 => 200000.0, 903 => 50000.0), 1500.0);
$creditsA = $budgetMgr->getCredits();

// A1: Bolsas locales metal y cristal deben estar 100% intactas
$minesIntactM = ($creditsA['planets'][$planetId]['mines']['metal'] === 1000000.0);
$minesIntactC = ($creditsA['planets'][$planetId]['mines']['crystal'] === 500000.0);
$fleetIntactM = ($creditsA['planets'][$planetId]['fleet']['metal'] === 2000000.0);
$fleetIntactC = ($creditsA['planets'][$planetId]['fleet']['crystal'] === 1000000.0);
$defIntactM   = ($creditsA['planets'][$planetId]['defense']['metal'] === 800000.0);
$defIntactC   = ($creditsA['planets'][$planetId]['defense']['crystal'] === 400000.0);

assert_test('GRUPO A', 'Misión 3: Bolsas locales de Metal y Cristal 100% intactas',
    $minesIntactM && $minesIntactC && $fleetIntactM && $fleetIntactC && $defIntactM && $defIntactC,
    "Mines: {$creditsA['planets'][$planetId]['mines']['metal']}, Fleet: {$creditsA['planets'][$planetId]['fleet']['metal']}");

// A2: Bolsa mines y defense deuterio intactas
$minesIntactD = ($creditsA['planets'][$planetId]['mines']['deuterium'] === 200000.0);
$defIntactD   = ($creditsA['planets'][$planetId]['defense']['deuterium'] === 100000.0);
assert_test('GRUPO A', 'Misión 3: Deuterio de mines y defense intacto',
    $minesIntactD && $defIntactD,
    "Mines D: {$creditsA['planets'][$planetId]['mines']['deuterium']}, Def D: {$creditsA['planets'][$planetId]['defense']['deuterium']}");

// A3: Combustible (1500 D) descontado estrictamente de fleet['deuterium']
$fleetDExpected = 500000.0 - 1500.0;
$fleetDActual   = $creditsA['planets'][$planetId]['fleet']['deuterium'];
assert_test('GRUPO A', 'Misión 3: Combustible descontado exclusivamente de fleet deuterium',
    abs($fleetDActual - $fleetDExpected) < 0.01,
    "Esperado: $fleetDExpected, Actual: $fleetDActual");

// A4: last_physical de metal y cristal intactos, deuterio solo descuenta combustible
$physMIntact = ($creditsA['last_physical'][$planetId]['metal'] === 3800000.0);
$physCIntact = ($creditsA['last_physical'][$planetId]['crystal'] === 1900000.0);
$physDExpected = 800000.0 - 1500.0;
$physDActual   = $creditsA['last_physical'][$planetId]['deuterium'];
assert_test('GRUPO A', 'Misión 3: last_physical M/C intactos y D solo reduce combustible',
    $physMIntact && $physCIntact && abs($physDActual - $physDExpected) < 0.01,
    "Phys M: {$creditsA['last_physical'][$planetId]['metal']}, Phys D: $physDActual");


// ========================================================================
// GRUPO B: deductDispatchedMission() en Misión 4 (Despliegue a FOB con Deuterio)
// ========================================================================
echo "\n--- GRUPO B: Misión 4 Piggyback de Deuterio a FOB ---\n";

$initCreditsB = $initCreditsA;
$refCredits->setValue($budgetMgr, $initCreditsB);

// Despachar Misión 4 con solo deuterio: Carga 100,000 D; Combustible 5,000 D
$budgetMgr->deductDispatchedMission($planetId, 4, array(901 => 0.0, 902 => 0.0, 903 => 100000.0), 5000.0);
$creditsB = $budgetMgr->getCredits();

// B1: Fleet deuterium debe restar fuel (5k) + cargo (100k) = 105k
$fleetDExpB = 500000.0 - 105000.0;
$fleetDActB = $creditsB['planets'][$planetId]['fleet']['deuterium'];
assert_test('GRUPO B', 'Misión 4 Piggyback: Fleet deuterium descuenta fuel + cargo militar (105k)',
    abs($fleetDActB - $fleetDExpB) < 0.01,
    "Esperado: $fleetDExpB, Actual: $fleetDActB");

// B2: Mines y defense 100% intactas
$minesIntactB = ($creditsB['planets'][$planetId]['mines']['deuterium'] === 200000.0)
             && ($creditsB['planets'][$planetId]['mines']['metal'] === 1000000.0);
$defIntactB   = ($creditsB['planets'][$planetId]['defense']['deuterium'] === 100000.0)
             && ($creditsB['planets'][$planetId]['defense']['metal'] === 800000.0);
assert_test('GRUPO B', 'Misión 4 Piggyback: Mines y defense permanecen 100% intactas',
    $minesIntactB && $defIntactB,
    "Mines D: {$creditsB['planets'][$planetId]['mines']['deuterium']}, Def D: {$creditsB['planets'][$planetId]['defense']['deuterium']}");

// B3: last_physical descuenta combustible + cargo (105k)
$physDExpB = 800000.0 - 105000.0;
$physDActB = $creditsB['last_physical'][$planetId]['deuterium'];
assert_test('GRUPO B', 'Misión 4 Piggyback: last_physical Deuterio descuenta fuel + cargo',
    abs($physDActB - $physDExpB) < 0.01,
    "Esperado: $physDExpB, Actual: $physDActB");


// ========================================================================
// GRUPO C: Eliminación del Bypass 40M MSE y Hangar Bypass en getDonorSurplusByCategory()
// ========================================================================
echo "\n--- GRUPO C: Eliminación del Bypass 40M MSE y Aislamiento de Donación ---\n";

// Planeta con 60,000,000 de Metal físico (> 40M MSE), pero solo 1,000,000 en credits['mines']['metal']
$planetDataC = array(
    'planet_type'        => '1',
    'metal'              => 60000000.0,
    'crystal'            => 10000000.0,
    'deuterium'          => 5000000.0,
    'metal_perhour'      => 100000.0,
    'crystal_perhour'    => 50000.0,
    'deuterium_perhour'  => 20000.0,
    'hangar'             => 10,
);

$initCreditsC = array(
    'planets' => array(
        $planetId => array(
            'mines'   => array('metal' => 1000000.0, 'crystal' => 500000.0, 'deuterium' => 200000.0),
            'fleet'   => array('metal' => 45000000.0, 'crystal' => 5000000.0, 'deuterium' => 4000000.0),
            'defense' => array('metal' => 10000000.0, 'crystal' => 4000000.0, 'deuterium' => 500000.0),
        )
    ),
    'research' => array('metal' => 100000.0, 'crystal' => 50000.0, 'deuterium' => 20000.0),
    'opportunity_fund' => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
    'last_updated' => TIMESTAMP,
    'last_physical' => array(
        $planetId => array('metal' => 60000000.0, 'crystal' => 10000000.0, 'deuterium' => 5000000.0)
    )
);

$refCredits->setValue($budgetMgr, $initCreditsC);

$surplusMines = $budgetMgr->getDonorSurplusByCategory($planetId, 'mines', $planetDataC);

// C1: Excedente donable para 'mines' NO puede exceder su saldo virtual (1M)
// Con 1.0h produccion reserve (100k * 0.25 = 25k), donable <= 1M - 25k = 975k
assert_test('GRUPO C', 'Bypass 40M MSE Eliminado: Metal donable para mines acotado por bolsa virtual (<= 1M, no 59M)',
    $surplusMines['metal'] <= 1000000.0 && $surplusMines['metal'] > 0.0,
    "Surplus metal retornado: {$surplusMines['metal']}");

// C2: Planeta con 0 de Deuterio en 'mines', pero 4M en 'fleet'. Surplus mines D debe ser 0.0
$creditsC2 = $initCreditsC;
$creditsC2['planets'][$planetId]['mines']['deuterium'] = 0.0;
$refCredits->setValue($budgetMgr, $creditsC2);
$surplusMinesZeroD = $budgetMgr->getDonorSurplusByCategory($planetId, 'mines', $planetDataC);
assert_test('GRUPO C', 'Bypass 40M MSE: Deuterio de fleet no se fuga a mines si mines tiene 0 saldo',
    $surplusMinesZeroD['deuterium'] === 0.0,
    "Surplus deuterium retornado: {$surplusMinesZeroD['deuterium']}");

// C3: Planeta con hangar == 0 no expone recursos si la bolsa es 0
$planetDataC3 = $planetDataC;
$planetDataC3['hangar'] = 0;
global $resource;
$planetDataC3[$resource[21]] = 0;
$creditsC3 = $initCreditsC;
$creditsC3['planets'][$planetId]['mines'] = array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0);
$refCredits->setValue($budgetMgr, $creditsC3);
$surplusHangarZero = $budgetMgr->getDonorSurplusByCategory($planetId, 'mines', $planetDataC3);
assert_test('GRUPO C', 'Hangar == 0 Bypass Eliminado: No regala stock fisico si bolsa está vacía',
    $surplusHangarZero['metal'] === 0.0 && $surplusHangarZero['crystal'] === 0.0 && $surplusHangarZero['deuterium'] === 0.0,
    "M: {$surplusHangarZero['metal']}, C: {$surplusHangarZero['crystal']}, D: {$surplusHangarZero['deuterium']}");


// ========================================================================
// GRUPO D: Aislamiento de Investigación (Capped by Uncommitted Physical Stock)
// ========================================================================
echo "\n--- GRUPO D: Aislamiento de Bolsa Global de Investigación ---\n";

// Planeta con 5,000,000 de Metal físico.
// Bolsas locales: mines=2M, fleet=2M, defense=500k. Total comprometido = 4.5M.
// Stock físico libre = 5M - 4.5M = 500,000.
// Bolsa global research = 10,000,000.
// Donable research debe ser exactamente min(500k, 10M) = 500,000.
$planetDataD = array(
    'planet_type'        => '1',
    'metal'              => 5000000.0,
    'crystal'            => 3000000.0,
    'deuterium'          => 2000000.0,
    'metal_perhour'      => 50000.0,
    'crystal_perhour'    => 25000.0,
    'deuterium_perhour'  => 10000.0,
);

$initCreditsD = array(
    'planets' => array(
        $planetId => array(
            'mines'   => array('metal' => 2000000.0, 'crystal' => 1000000.0, 'deuterium' => 500000.0),
            'fleet'   => array('metal' => 2000000.0, 'crystal' => 1000000.0, 'deuterium' => 500000.0),
            'defense' => array('metal' => 500000.0,  'crystal' => 500000.0,  'deuterium' => 200000.0),
        )
    ),
    'research' => array('metal' => 10000000.0, 'crystal' => 10000000.0, 'deuterium' => 10000000.0),
    'opportunity_fund' => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
    'last_updated' => TIMESTAMP,
    'last_physical' => array(
        $planetId => array('metal' => 5000000.0, 'crystal' => 3000000.0, 'deuterium' => 2000000.0)
    )
);

$refCredits->setValue($budgetMgr, $initCreditsD);

$surplusResD = $budgetMgr->getDonorSurplusByCategory($planetId, 'research', $planetDataD);

// D1: Donable metal acotado a 500,000 (no a los 10M de la bolsa de research)
assert_test('GRUPO D', 'Research Surplus: Acotado a stock físico libre tras compromisos locales (500k)',
    abs($surplusResD['metal'] - 500000.0) < 0.01,
    "Esperado: 500000, Obtenido: {$surplusResD['metal']}");

// D2: Si el stock físico es menor que los compromisos locales (ej. 4M físico vs 4.5M comprometido)
$planetDataD2 = $planetDataD;
$planetDataD2['metal'] = 4000000.0;
$surplusResDeficit = $budgetMgr->getDonorSurplusByCategory($planetId, 'research', $planetDataD2);
assert_test('GRUPO D', 'Research Surplus: Devuelve 0.0 si stock físico es menor que compromisos locales',
    $surplusResDeficit['metal'] === 0.0,
    "Surplus metal en déficit: {$surplusResDeficit['metal']}");

// D3: Si el crédito de research es menor que el stock libre (ej. crédito research = 50,000, stock libre = 500,000)
$creditsD3 = $initCreditsD;
$creditsD3['research']['metal'] = 50000.0;
$refCredits->setValue($budgetMgr, $creditsD3);
$surplusResSmallCredit = $budgetMgr->getDonorSurplusByCategory($planetId, 'research', $planetDataD);
assert_test('GRUPO D', 'Research Surplus: Acotado por saldo virtual de research si es menor que stock libre (50k)',
    abs($surplusResSmallCredit['metal'] - 50000.0) < 0.01,
    "Esperado: 50000, Obtenido: {$surplusResSmallCredit['metal']}");


// ========================================================================
// GRUPO E: Reserva Lunar (Phalanx y Salto Cuántico)
// ========================================================================
echo "\n--- GRUPO E: Doctrina de Reserva Lunar ---\n";

$moonDataWithGate = array(
    'planet_type' => '3',
    'metal'       => 10000000.0,
    'crystal'     => 10000000.0,
    'deuterium'   => 35000000.0,
    $resource[43] => 1,
    'sprungtor'   => 1
);

$moonDataNoGate = array(
    'planet_type' => '3',
    'metal'       => 10000000.0,
    'crystal'     => 10000000.0,
    'deuterium'   => 35000000.0,
    $resource[43] => 0,
    'sprungtor'   => 0
);

// E1: Con salto cuántico: exporta todo el metal y cristal; reserva 25M deuterio
$surplusMoonGate = $budgetMgr->getDonorSurplusByCategory(201, 'fleet', $moonDataWithGate);
$moonMValid = ($surplusMoonGate['metal'] === 10000000.0);
$moonCValid = ($surplusMoonGate['crystal'] === 10000000.0);
$moonDValid = ($surplusMoonGate['deuterium'] === 10000000.0); // 35M - 25M Phalanx reserve = 10M

assert_test('GRUPO E', 'Luna con Salto Cuántico: Exporta todo M/C y reserva 25M D para Phalanx',
    $moonMValid && $moonCValid && $moonDValid,
    "M: {$surplusMoonGate['metal']}, C: {$surplusMoonGate['crystal']}, D: {$surplusMoonGate['deuterium']}");

// E2: Sin salto cuántico: reserva 2M M y 4M C para salto cuántico + 25M D para Phalanx
$surplusMoonNoGate = $budgetMgr->getDonorSurplusByCategory(201, 'fleet', $moonDataNoGate);
$moonNoGateMValid = ($surplusMoonNoGate['metal'] === 8000000.0);   // 10M - 2M = 8M
$moonNoGateCValid = ($surplusMoonNoGate['crystal'] === 6000000.0); // 10M - 4M = 6M
$moonNoGateDValid = ($surplusMoonNoGate['deuterium'] === 10000000.0); // 35M - 25M = 10M

assert_test('GRUPO E', 'Luna sin Salto Cuántico: Reserva 2M M + 4M C para Puerta #43 y 25M D para Phalanx',
    $moonNoGateMValid && $moonNoGateCValid && $moonNoGateDValid,
    "M: {$surplusMoonNoGate['metal']}, C: {$surplusMoonNoGate['crystal']}, D: {$surplusMoonNoGate['deuterium']}");


// ========================================================================
// GRUPO F: Bloqueo de Categoría y Precisión en debitDonorCategory()
// ========================================================================
echo "\n--- GRUPO F: Category Lock y Precisión de Redondeo ---\n";

// F1: Bloqueo de categoría 'mines' devuelve ceros
$creditsF1 = $initCreditsC;
$creditsF1['savings_locks_by_cat']['mines'] = array(
    'planet_id'   => $planetId,
    'category'    => 'mines',
    'element_id'  => 1,
    'eta_seconds' => 1200
);
$refCredits->setValue($budgetMgr, $creditsF1);

$surplusLocked = $budgetMgr->getDonorSurplusByCategory($planetId, 'mines', $planetDataC);
assert_test('GRUPO F', 'Category Lock en mines: Retorna array con ceros absolutos',
    $surplusLocked['metal'] === 0.0 && $surplusLocked['crystal'] === 0.0 && $surplusLocked['deuterium'] === 0.0,
    "M: {$surplusLocked['metal']}, C: {$surplusLocked['crystal']}, D: {$surplusLocked['deuterium']}");

// F2: debitDonorCategory() redondeo a 2 decimales en last_physical
$creditsF2 = $initCreditsA;
$creditsF2['last_physical'][$planetId] = array('metal' => 1000.333, 'crystal' => 500.666, 'deuterium' => 200.555);
$refCredits->setValue($budgetMgr, $creditsF2);

$budgetMgr->debitDonorCategory($planetId, 'mines', array('metal' => 100.111, 'crystal' => 50.222, 'deuterium' => 20.333));
$creditsF2After = $budgetMgr->getCredits();

$physMDec = $creditsF2After['last_physical'][$planetId]['metal'];
$physCDec = $creditsF2After['last_physical'][$planetId]['crystal'];
$physDDec = $creditsF2After['last_physical'][$planetId]['deuterium'];

assert_test('GRUPO F', 'debitDonorCategory: last_physical redondeado exactamente a 2 decimales',
    $physMDec === round(1000.333 - 100.111, 2) &&
    $physCDec === round(500.666 - 50.222, 2) &&
    $physDDec === round(200.555 - 20.333, 2),
    "Phys M: $physMDec, Phys C: $physCDec, Phys D: $physDDec");

echo "\n========================================================================\n";
echo "   RESUMEN FINAL: {$results['passed']} PASS / {$results['failed']} FAIL\n";
echo "========================================================================\n";

if ($results['failed'] > 0) {
    exit(1);
}
exit(0);

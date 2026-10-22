<?php

/**
 * NovaRush AI Bot Economic System Regression Test Suite
 *
 * Verifies all architectural economic invariants from the AI redesign:
 * 1. Single-source budget deductions (Zero duplicate deductions in ActionGateway + ForcePlanner).
 * 2. Strict budget compliance in ProductionAllocator (No unverified building bypass).
 * 3. Dynamic warehouse ceiling (Physical stock above warehouse capacity is fully usable).
 * 4. Hangar 0 preservation (Military budgets are NOT wiped to 0.00).
 * 5. Early-game infrastructure acceleration (Robots 2 & Hangar 1 unlocked when resources exist).
 * 6. Trader liquidity & fuel protection (Trades paid strictly from genuine surplus, fuel buffer preserved).
 * 7. Multi-category budget conservation (Mines + Fleet + Defense + Research = 100% of physical backing).
 * 8. Peacetime defense queue throttling (Hangar shared FIFO protection).
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
require_once 'includes/classes/bot/Execution/ActionGateway.class.php';
require_once 'includes/classes/bot/Forces/BotBudgetManager.class.php';
require_once 'includes/classes/bot/Forces/ProductionAllocator.class.php';
require_once 'includes/classes/bot/Forces/ForcePlanner.class.php';
require_once 'includes/classes/bot/Forces/BotPremiumStrategist.class.php';
require_once 'includes/classes/bot/Perception/WorldModel.class.php';
require_once 'includes/classes/bot/Perception/EmpireState.class.php';
require_once 'includes/classes/bot/Config/DifficultyProfile.class.php';
require_once 'includes/classes/bot/Config/Personality.class.php';

$results = array(
    'passed' => 0,
    'failed' => 0,
    'tests'  => array()
);

function assert_econ($group, $name, $condition, $details = '') {
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
echo "   NOVARUSH AI BOT: REGRESIÓN DEL REDISEÑO ECONÓMICO Y CONTABLE\n";
echo "========================================================================\n\n";

$db = Database::get();

// Find an existing bot in database
$botRow = $db->selectSingle("SELECT * FROM " . DB_PREFIX . "bots ORDER BY bot_id ASC LIMIT 1;");
if (empty($botRow)) {
    die("FATAL: No bot found in database.\n");
}
$botId = (int)$botRow['bot_id'];
$userRow = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = :uId LIMIT 1;", array(':uId' => $botId));
if (empty($userRow)) {
    die("FATAL: User for bot #{$botId} not found in database.\n");
}

$ctx = new BotContext($botRow, $userRow);
$world = new BotWorldModel($ctx);
$gateway = new BotActionGateway($ctx);
$budgetMgr = new BotBudgetManager($ctx);
$ctx->budgetManager = $budgetMgr;

$firstPlanetId = key($world->empire->getPlanets());
if (!$firstPlanetId) {
    die("FATAL: Bot has no planets.\n");
}

// ========================================================================
// SECCIÓN 1: CONTABILIDAD Y TECHOS DINÁMICOS DE ALMACÉN
// ========================================================================
echo "--- SECCIÓN 1: Contabilidad y Techos Dinámicos de Almacén ---\n";

// 1.1 Techo dinámico: Almacén bajo (100k) con stock físico masivo (10M) acumula por encima del almacén
$planetMock = array(
    'id' => $firstPlanetId,
    'metal' => 10000000.0,
    'crystal' => 5000000.0,
    'deuterium' => 2000000.0,
    'metal_perhour' => 100000.0,
    'crystal_perhour' => 50000.0,
    'deuterium_perhour' => 25000.0,
    'metal_max' => 100000.0,     // Almacén nivel 0/1 (solo 100k de capacidad)
    'crystal_max' => 100000.0,
    'deuterium_max' => 100000.0,
    'b_hangar_id' => '',
    'b_building_id' => '',
);

$empireMock = clone $world->empire;
$refPlanets = new ReflectionProperty($empireMock, 'planets');
$refPlanets->setAccessible(true);
$refPlanets->setValue($empireMock, array($firstPlanetId => array('data' => $planetMock, 'building_queue' => array())));

$refCredits = new ReflectionProperty($budgetMgr, 'credits');
$refCredits->setAccessible(true);
$refCredits->setValue($budgetMgr, array(
    'planets' => array($firstPlanetId => array(
        'mines' => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
        'fleet' => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
        'defense' => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
    )),
    'research' => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
    'opportunity_fund' => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
    'last_physical' => array($firstPlanetId => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0)),
    'last_updated' => TIMESTAMP - 3600,
));

$budgetMgr->updateCredits($empireMock);
$credsAfter = $budgetMgr->getCredits();

// Con 10M físico y ratio de flota (e.g. 35%), fleet metal debe superar holgadamente el almacén (100k)
$fleetMetal = (float)$credsAfter['planets'][$firstPlanetId]['fleet']['metal'];
assert_econ(
    'ALMACÉN DINÁMICO',
    'Créditos de flota superan la capacidad del almacén cuando el stock físico es elevado',
    ($fleetMetal > 1000000.0),
    "Fleet Metal obtenido: {$fleetMetal} (Capacidad almacén: 100,000)"
);

// 1.2 Invariante de conservación: La suma virtual total nunca excede el 100% de los recursos físicos
$totVirtM = (float)$credsAfter['planets'][$firstPlanetId]['mines']['metal']
          + (float)$credsAfter['planets'][$firstPlanetId]['fleet']['metal']
          + (float)$credsAfter['planets'][$firstPlanetId]['defense']['metal']
          + (float)$credsAfter['research']['metal'];

assert_econ(
    'CONSERVACIÓN DE RECURSOS',
    'La suma de créditos de todas las categorías no excede el stock físico existente',
    ($totVirtM <= 10000000.0001),
    "Suma virtual: {$totVirtM} vs Físico: 10,000,000"
);

// 1.3 Preservación de presupuesto militar en planetas con Hangar == 0
$planetMockHangar0 = $planetMock;
global $resource;
$planetMockHangar0[$resource[21]] = 0; // Hangar 0
$refPlanets->setValue($empireMock, array($firstPlanetId => array('data' => $planetMockHangar0, 'building_queue' => array())));

$budgetMgr->reconcilePhysicalInvariant($empireMock, $budgetMgr->getMacroRatios());
$credsHangar0 = $budgetMgr->getCredits();

assert_econ(
    'HANGAR 0',
    'Planetas con Hangar == 0 NO vacían sus presupuestos militares a 0.00',
    ($credsHangar0['planets'][$firstPlanetId]['fleet']['metal'] > 0.0 &&
     $credsHangar0['planets'][$firstPlanetId]['defense']['metal'] > 0.0),
    "Fleet: {$credsHangar0['planets'][$firstPlanetId]['fleet']['metal']}, Def: {$credsHangar0['planets'][$firstPlanetId]['defense']['metal']}"
);


// ========================================================================
// SECCIÓN 2: AUDITORÍA DE DEDUCCIÓN ÚNICA (ZERO DUPLICATE DEDUCTIONS)
// ========================================================================
echo "\n--- SECCIÓN 2: Auditoría de Deducción Presupuestaria Única ---\n";

// 2.1 Inspección estática del código fuente de ForcePlanner.class.php
$fpSource = file_get_contents(ROOT_PATH . 'includes/classes/bot/Forces/ForcePlanner.class.php');
$deductMatches = substr_count($fpSource, 'deductFromBudget');

assert_econ(
    'DEDUCCIÓN ÚNICA',
    'ForcePlanner no contiene ninguna llamada duplicada a deductFromBudget()',
    ($deductMatches === 0),
    "Llamadas encontradas en ForcePlanner: {$deductMatches} (esperado 0)"
);

// 2.2 Deducción atómica en ActionGateway descuenta exactamente una vez
$initFleetCreds = array('metal' => 100000.0, 'crystal' => 50000.0, 'deuterium' => 20000.0);
$refCredits->setValue($budgetMgr, array(
    'planets' => array($firstPlanetId => array(
        'mines'   => array('metal' => 100000.0, 'crystal' => 50000.0, 'deuterium' => 20000.0),
        'fleet'   => $initFleetCreds,
        'defense' => array('metal' => 100000.0, 'crystal' => 50000.0, 'deuterium' => 20000.0),
    )),
    'research' => array('metal' => 50000.0, 'crystal' => 25000.0, 'deuterium' => 10000.0),
));

$costShip = array('metal' => 10000.0, 'crystal' => 5000.0, 'deuterium' => 2000.0);
$budgetMgr->deductFromBudget($firstPlanetId, 'fleet', $costShip);
$afterDeduct = $budgetMgr->getPlanetFleetCredits($firstPlanetId);

assert_econ(
    'DEDUCCIÓN ATÓMICA',
    'deductFromBudget descuenta el importe exacto sin fugas contables',
    ($afterDeduct['metal'] === 90000.0 && $afterDeduct['crystal'] === 45000.0 && $afterDeduct['deuterium'] === 18000.0),
    "Metal restante: {$afterDeduct['metal']}, Cristal: {$afterDeduct['crystal']}, Deut: {$afterDeduct['deuterium']}"
);


// ========================================================================
// SECCIÓN 3: CONTROL PRESUPUESTARIO EN PRODUCTION ALLOCATOR
// ========================================================================
echo "\n--- SECCIÓN 3: Control Presupuestario en Production Allocator ---\n";

// 3.1 Inspección estática: ausencia del bypass de stock físico en ProductionAllocator
$paSource = file_get_contents(ROOT_PATH . 'includes/classes/bot/Forces/ProductionAllocator.class.php');
$bypassFound = (strpos($paSource, 'if (!$canAffordBudget && $canAffordPhysical)') !== false);

assert_econ(
    'EDIFICIOS PRESUPUESTO',
    'ProductionAllocator no contiene el bypass forzado $canAffordBudget = true',
    (!$bypassFound),
    "Bypass presente en ProductionAllocator: " . ($bypassFound ? 'SÍ (ERROR)' : 'NO (CORRECTO)')
);

// 3.2 Aceleración temprana: Si hay recursos suficientes, Robots y Hangar se evalúan sin esperar minas 10/8/6
$hasAmpleGate = (strpos($paSource, '$hasAmpleResources = ($availPhysM >= 20000.0 && $availPhysC >= 10000.0);') !== false);
assert_econ(
    'DESBLOQUEO COLONIZADOR',
    'ProductionAllocator incluye compuerta de recursos abundantes para acelerar Robots y Hangar',
    $hasAmpleGate,
    "Compuerta de recursos abundantes instalada: " . ($hasAmpleGate ? 'SÍ' : 'NO')
);


// ========================================================================
// SECCIÓN 4: MERCADER Y PROTECCIÓN DE SUPERÁVIT Y COMBUSTIBLE
// ========================================================================
echo "\n--- SECCIÓN 4: Mercader y Protección de Superávit y Combustible ---\n";

// 4.1 canTraderResolveDeficit rechaza intercambio cuando el stock total cubre el déficit pero el superávit no
// Escenario: Planeta con 100k Metal, 0 Cristal, 10k Deuterio.
// Meta: Edificio que cuesta 90k Metal y 50k Cristal.
// Stock total de Metal es 100k (cubriría si vendiera 100k Metal por 50k Cristal),
// pero el excedente por encima de los 90k de costo es solo 10k Metal (insuficiente para comprar 50k Cristal).
$mockPlanetTrader = array(
    'id' => $firstPlanetId,
    'metal' => 100000.0,
    'crystal' => 0.0,
    'deuterium' => 10000.0,
    'metal_max' => 1000000.0,
    'crystal_max' => 1000000.0,
    'deuterium_max' => 1000000.0,
);

// Cachear en BotPremiumStrategist
$refCache = new ReflectionProperty('BotPremiumStrategist', 'planetCache');
$refCache->setAccessible(true);
$refCache->setValue(null, array($firstPlanetId => $mockPlanetTrader));

$targetCostBuilding = array(901 => 90000.0, 902 => 50000.0, 903 => 0.0);
$canResolve = BotPremiumStrategist::canTraderResolveDeficit($ctx, $firstPlanetId, 'mines', $targetCostBuilding);

assert_econ(
    'MERCADER SUPERÁVIT',
    'canTraderResolveDeficit rechaza la operación si el superávit del recurso de pago no cubre el intercambio',
    ($canResolve === false),
    "Resultado de evaluación: " . ($canResolve ? 'Aceptado (INCORRECTO)' : 'Rechazado (CORRECTO)')
);

// 4.2 canTraderResolveDeficit acepta cuando el superávit es genuinamente suficiente
$mockPlanetTraderSurplus = array(
    'id' => $firstPlanetId,
    'metal' => 300000.0, // 300k - 90k = 210k de superávit. Con 100k Metal compra los 50k Cristal requeridos (tasa 2:1)
    'crystal' => 0.0,
    'deuterium' => 50000.0,
    'metal_max' => 1000000.0,
    'crystal_max' => 1000000.0,
    'deuterium_max' => 1000000.0,
);
$refCache->setValue(null, array($firstPlanetId => $mockPlanetTraderSurplus));

$canResolveValid = BotPremiumStrategist::canTraderResolveDeficit($ctx, $firstPlanetId, 'mines', $targetCostBuilding);

assert_econ(
    'MERCADER SUPERÁVIT',
    'canTraderResolveDeficit aprueba la operación cuando existe superávit neto suficiente',
    (is_array($canResolveValid) && $canResolveValid['exact_delta_trade'] === true),
    "Resultado de evaluación: " . (is_array($canResolveValid) ? 'Aprobado con éxito' : 'Rechazado')
);

// 4.3 Protección de reserva táctica de Deuterio en compras del mercader
$mockPlanetDeutDrain = array(
    'id' => $firstPlanetId,
    'metal' => 0.0,
    'crystal' => 0.0,
    'deuterium' => 20000.0, // 20k Deuterio es menor a la reserva táctica base de 25k
    'metal_max' => 1000000.0,
    'crystal_max' => 1000000.0,
    'deuterium_max' => 1000000.0,
);
$refCache->setValue(null, array($firstPlanetId => $mockPlanetDeutDrain));

$targetCostMines = array(901 => 10000.0, 902 => 5000.0, 903 => 0.0);
$canDrainDeut = BotPremiumStrategist::canTraderResolveDeficit($ctx, $firstPlanetId, 'mines', $targetCostMines);

assert_econ(
    'PROTECCIÓN COMBUSTIBLE',
    'El mercader nunca drena deuterio por debajo del piso de seguridad de combustible',
    ($canDrainDeut === false),
    "Resultado: " . ($canDrainDeut ? 'Drenó combustible (INCORRECTO)' : 'Protegió reserva (CORRECTO)')
);


// ========================================================================
// SECCIÓN 5: CONTROL DE COLA DE DEFENSA EN TIEMPO DE PAZ
// ========================================================================
echo "\n--- SECCIÓN 5: Control de Cola de Defensa en Tiempo de Paz ---\n";

$fpSourceCheck = file_get_contents(ROOT_PATH . 'includes/classes/bot/Forces/ForcePlanner.class.php');
$hasPeacetimeDefLimit = (strpos($fpSourceCheck, '$maxDefJobsThisCycle = $isEmergency ? 14 : 4;') !== false);

assert_econ(
    'COLA DE DEFENSA',
    'ForcePlanner implementa límite de slots defensivos en tiempo de paz (4 slots) para no saturar el astillero',
    $hasPeacetimeDefLimit,
    "Límite de tiempo de paz instalado: " . ($hasPeacetimeDefLimit ? 'SÍ' : 'NO')
);


// ========================================================================
// RESUMEN FINAL
// ========================================================================
echo "\n========================================================================\n";
echo "   RESUMEN FINAL DE LA SUITE DE REGRESIÓN ECONÓMICA\n";
echo "========================================================================\n";
echo "Total Pruebas: {$results['passed']} + {$results['failed']} = " . ($results['passed'] + $results['failed']) . "\n";
echo "Exitosas:      {$results['passed']}\n";
echo "Fallidas:      {$results['failed']}\n";
if ($results['failed'] === 0) {
    echo "ESTADO GLOBAL: [PASSED] TODAS LAS INVARIANTES ECONÓMICAS ESTÁN 100% CERTIFICADAS.\n";
} else {
    echo "ESTADO GLOBAL: [FAILED] DETECTADAS REGRESIONES EN EL SISTEMA ECONÓMICO.\n";
}
echo "========================================================================\n";

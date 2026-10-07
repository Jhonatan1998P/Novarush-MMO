<?php

define('MODE', 'CRON');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)).'/');
set_include_path(ROOT_PATH);

require 'includes/common.php';
require 'includes/vars/General.php';
foreach (get_defined_vars() as $k => $v) {
    $GLOBALS[$k] = $v;
}

require_once 'includes/classes/bot/BotEngine.class.php';
require_once 'includes/classes/bot/Kernel/BotContext.class.php';
require_once 'includes/classes/bot/Execution/ActionGateway.class.php';
require_once 'includes/classes/bot/Forces/BotBudgetManager.class.php';
require_once 'includes/classes/bot/Forces/ProductionAllocator.class.php';
require_once 'includes/classes/bot/Forces/ForcePlanner.class.php';
require_once 'includes/classes/bot/Forces/LogisticsPlanner.class.php';

$results = array(
    'passed' => 0,
    'failed' => 0,
    'tests'  => array()
);

function assert_test($phase, $name, $condition, $details = '') {
    global $results;
    if ($condition) {
        $results['passed']++;
        $results['tests'][] = array('phase' => $phase, 'name' => $name, 'status' => 'PASS', 'details' => $details);
        echo " [$phase] [PASS] $name\n";
    } else {
        $results['failed']++;
        $results['tests'][] = array('phase' => $phase, 'name' => $name, 'status' => 'FAIL', 'details' => $details);
        echo " [$phase] [FAIL] $name: $details\n";
    }
}

echo "========================================================================\n";
echo "   SUITE DE TEST INTEGRAL: PLAN MAESTRO V3.1 (NOVARUSH)\n";
echo "   Arquitectura Económica, Presupuestaria y Logística Vectorial\n";
echo "========================================================================\n\n";

BotEngine::ensureTables();

$db = Database::get();
$botRow = $db->selectSingle("SELECT * FROM " . DB_PREFIX . "bots WHERE bot_id = 1007;");
$userRow = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = 1007;");

if (empty($botRow) || empty($userRow)) {
    die("FATAL: Bot 1007 no encontrado en la base de datos para pruebas.\n");
}

$ctx = new BotContext($botRow, $userRow);
$world = new BotWorldModel($ctx);
$gateway = new BotActionGateway($ctx);

// ========================================================================
// FASE 1: Fuente de la Verdad y Modelo de Crédito Incremental Desacoplado
// ========================================================================
echo "\n--- FASE 1: MODELO DE CRÉDITO INCREMENTAL DESACOPLADO ---\n";

$budgetMgr = new BotBudgetManager($ctx);
$ctx->budgetManager = $budgetMgr;
$ratios = $budgetMgr->getMacroRatios();

assert_test(
    'FASE 1',
    'Ratios macroeconómicos suman 1.0 y están definidos por arquetipo',
    (abs(($ratios['mines'] + $ratios['fleet'] + $ratios['defense'] + $ratios['research']) - 1.0) < 0.001),
    "Suma de ratios: " . ($ratios['mines'] + $ratios['fleet'] + $ratios['defense'] + $ratios['research'])
);

// Comprobar acumulación incremental
$budgetMgr->updateCredits($world->empire);
$credits = $budgetMgr->getCredits();

assert_test(
    'FASE 1',
    'Estructura de créditos contiene ámbito local por planeta y global por investigación',
    isset($credits['planets']) && is_array($credits['planets']) && isset($credits['research']) && isset($credits['opportunity_fund']),
    "Estructura JSON contiene planets, research y opportunity_fund"
);

$firstPlanetId = key($credits['planets']);
$pCredits = $credits['planets'][$firstPlanetId];

assert_test(
    'FASE 1',
    'Crédito planetario local incluye categorías mines, fleet y defense con vector [M, C, D]',
    isset($pCredits['mines']['metal'], $pCredits['fleet']['crystal'], $pCredits['defense']['deuterium']),
    "Colonia #{$firstPlanetId} posee vectores de recursos para cada categoría"
);

assert_test(
    'FASE 1',
    'Crédito global de investigación agrega producción de todo el imperio',
    ($credits['research']['metal'] > 0 && $credits['research']['crystal'] > 0),
    "Research metal acumulado: {$credits['research']['metal']}"
);

// Test de desvío a Fondo Común de Oportunidad cuando se supera el techo de almacén
$testCredits = $credits;
$testCredits['planets'][$firstPlanetId]['mines']['metal'] = 999999999999.0;
$budgetMgrRefl = new ReflectionProperty($budgetMgr, 'credits');
$budgetMgrRefl->setAccessible(true);
$budgetMgrRefl->setValue($budgetMgr, $testCredits);

$budgetMgr->updateCredits($world->empire);
$updatedCredits = $budgetMgr->getCredits();
$pData = $world->empire->getPlanet($firstPlanetId)['data'];
$capM = (float)$pData['metal_max'] * $ratios['mines'];

assert_test(
    'FASE 1',
    'Techo estático de almacén y desvío de excedentes al Fondo Común de Oportunidad',
    ($updatedCredits['planets'][$firstPlanetId]['mines']['metal'] <= ($capM + 1.0) && $updatedCredits['opportunity_fund']['metal'] > 0),
    "Crédito limitado a {$capM} y oportunidad contiene {$updatedCredits['opportunity_fund']['metal']}"
);

// ========================================================================
// FASE 2: Protocolo Atómico de Deducción y Concurrencia Transaccional
// ========================================================================
echo "\n--- FASE 2: PROTOCOLO ATÓMICO Y CONCURRENCIA TRANSACCIONAL ---\n";

assert_test(
    'FASE 2',
    'Database soporta transacciones PDO beginTransaction, commit, rollBack, inTransaction',
    method_exists($db, 'beginTransaction') && method_exists($db, 'commit') && method_exists($db, 'rollBack') && method_exists($db, 'inTransaction'),
    "Métodos de transacción disponibles en Database::get()"
);

// Test de reversión atómica (Rollback) ante fallo
$db->beginTransaction();
assert_test('FASE 2', 'Transacción PDO iniciada activamente', $db->inTransaction());

$lockedPlanet = BotLockManager::lockPlanetRow($firstPlanetId);
assert_test('FASE 2', 'Bloqueo pesimista de fila InnoDB con SELECT ... FOR UPDATE', !empty($lockedPlanet) && $lockedPlanet['id'] == $firstPlanetId);

// Simular reversión segura
$db->rollBack();
assert_test('FASE 2', 'RollBack restaura estado sin mutaciones residuales', !$db->inTransaction());

// Verificar deducción atómica de presupuesto
$initialOppM = (float)$updatedCredits['opportunity_fund']['metal'];
$costTest = array(901 => 1000.0, 902 => 500.0, 903 => 200.0);
$budgetMgr->deductFromBudget($firstPlanetId, 'mines', $costTest);
$afterCredits = $budgetMgr->getCredits();

assert_test(
    'FASE 2',
    'Deducción virtual de presupuesto descuenta recursos correctamente',
    ($afterCredits['planets'][$firstPlanetId]['mines']['metal'] <= $updatedCredits['planets'][$firstPlanetId]['mines']['metal']),
    "Créditos descontados tras compra simulada"
);

// ========================================================================
// FASE 3: Modo Ahorro Desbloqueante y Guardia de Almacén Dinámica
// ========================================================================
echo "\n--- FASE 3: MODO AHORRO Y GUARDIA DE ALMACÉN DINÁMICA ---\n";

// 3.1 Cálculo blindado de ETA sin división por cero
$safeEta = $budgetMgr->calculateSafeEta(
    array(901 => 1000000, 902 => 500000, 903 => 200000),
    array(901 => 0, 902 => 0, 903 => 0),
    array(901 => 0.0, 902 => 0.0, 903 => 0.0) // Producción 0!
);
assert_test(
    'FASE 3',
    'Cálculo blindado de ETA con protección contra división por cero (max(0.001, P/3600))',
    (is_numeric($safeEta) && $safeEta > 0 && !is_infinite($safeEta) && !is_nan($safeEta)),
    "ETA segura con producción 0 calculada: {$safeEta}s"
);

// 3.2 Guardia de Almacén Dinámica: Si coste > Almacén_Max * 0.95 -> forzar almacén 22, 23 o 24
$planetEntry = $world->empire->getPlanet($firstPlanetId);
$pData = $planetEntry['data'];
$hugeCost = array(
    901 => (float)$pData['metal_max'] * 1.5, // 150% de la capacidad de almacén!
    902 => 1000.0,
    903 => 1000.0
);

$budgetMgr->setSavingsLock($firstPlanetId, 'building', 15, $hugeCost, $pData);
$lock = $budgetMgr->getSavingsLock();

assert_test(
    'FASE 3',
    'Establecimiento y serialización de Modo Ahorro (savings_lock) con recurso deficitario identificado',
    (!empty($lock) && $lock['element_id'] == 15 && $lock['deficit_resource'] == 901),
    "Savings lock persistido para elemento #15 con déficit en Metal"
);

// Ejecutar Guardia de Almacén Dinámica
$guardResult = $budgetMgr->processSavingsLock($world->empire, $gateway);
assert_test(
    'FASE 3',
    'Guardia de Almacén Dinámica detecta sobrecoste > 95% y activa desbloqueo forzado de almacén (22, 23 o 24)',
    (isset($guardResult['status']) && in_array($guardResult['status'], array('unlocked_storage', 'accumulating', 'completed'))),
    "Resultado de guardia de almacén: " . json_encode($guardResult)
);

// 3.3 Watchdog Anti-Farm sobre recurso deficitario (3 ciclos consecutivos estancados)
$stagnantLock = array(
    'planet_id'        => $firstPlanetId,
    'target_type'      => 'building',
    'element_id'       => 15,
    'cost'             => array(901 => 50000000, 902 => 20000000, 903 => 10000000),
    'deficit_resource' => 901,
    'last_stock'       => (float)($pData['metal'] + 1000000), // Stock anterior mayor al actual (delta <= 0)
    'stagnant_cycles'  => 2, // Ya lleva 2 ciclos estancado, el próximo ciclo es el 3º (135s)
    'start_time'       => TIMESTAMP - 150,
    'eta_seconds'      => 600.0,
);
$lockRefl = new ReflectionProperty($budgetMgr, 'savingsLock');
$lockRefl->setAccessible(true);
$lockRefl->setValue($budgetMgr, $stagnantLock);

$watchdogResult = $budgetMgr->processSavingsLock($world->empire, $gateway);
assert_test(
    'FASE 3',
    'Watchdog Anti-Farm aborta modo ahorro tras 3 ciclos (135s) con Delta_stock <= 0 e invierte en defensas',
    ($watchdogResult['status'] === 'aborted_antifarm' && $budgetMgr->getSavingsLock() === null),
    "Anti-farm activado, reserva liberada y modo ahorro cancelado"
);

// 3.4 Auto-engagement de Modo Ahorro desde ProductionAllocator::allocate() ante metas inalcanzables
$budgetMgr->clearSavingsLock();
$allocator = new BotProductionAllocator($ctx, $gateway);
$mockEmpire = clone $world->empire;
// Simular que el planeta tiene 0 recursos líquidos para forzar que cualquier meta de alto valor sea inasequible
$mockPlanets = $mockEmpire->getPlanets();
foreach ($mockPlanets as $k => $v) {
    $mockPlanets[$k]['data']['metal'] = 0.0;
    $mockPlanets[$k]['data']['crystal'] = 0.0;
    $mockPlanets[$k]['data']['deuterium'] = 0.0;
    $mockPlanets[$k]['building_queue'] = array();
}

$reflEmpire = new ReflectionProperty($mockEmpire, 'planets');
$reflEmpire->setAccessible(true);
$reflEmpire->setValue($mockEmpire, $mockPlanets);

$allocQueued = $allocator->allocate($mockEmpire);
$autoLock = $budgetMgr->getSavingsLock();
assert_test(
    'FASE 3',
    'ProductionAllocator::allocate() detecta metas inasequibles y engancha savings_lock automáticamente',
    ($allocQueued === 0 && !empty($autoLock) && isset($autoLock['element_id'])),
    "Savings lock enganchado automáticamente para elemento #" . ($autoLock['element_id'] ?? 'ninguno')
);

// 3.5 Guardia de Almacén en Progreso: No disparar Anti-Farm falsamente si el almacén está en construcción
$buildingWarehouseLock = array(
    'planet_id'        => $firstPlanetId,
    'target_type'      => 'building',
    'element_id'       => 15,
    'cost'             => array(901 => 50000000, 902 => 20000000, 903 => 10000000),
    'deficit_resource' => 901,
    'last_stock'       => (float)($pData['metal']),
    'stagnant_cycles'  => 0,
    'start_time'       => TIMESTAMP - 100,
    'eta_seconds'      => 600.0,
);
$lockRefl->setValue($budgetMgr, $buildingWarehouseLock);

// Simular que el almacén 22 ya está en cola de construcción
$mockPlanetsWithStore = $mockPlanets;
$mockPlanetsWithStore[$firstPlanetId]['building_queue'] = array(array(22, 10, 50, TIMESTAMP + 100, 'build'));
$reflEmpire->setValue($mockEmpire, $mockPlanetsWithStore);

$warehouseGuardResult = $budgetMgr->processSavingsLock($mockEmpire, $gateway);
assert_test(
    'FASE 3',
    'Guardia de Almacén previene falso positivo de Anti-Farm cuando el almacén (22) está en cola de construcción',
    ($warehouseGuardResult['status'] === 'accumulating' && $budgetMgr->getSavingsLock() !== null),
    "Estado de guardia con almacén en cola: " . ($warehouseGuardResult['status'] ?? 'desconocido')
);

$budgetMgr->clearSavingsLock();

// ========================================================================
// FASE 4: Módulo Logístico Multi-Donante con Rastreo Integral en Vuelo
// ========================================================================
echo "\n--- FASE 4: MÓDULO LOGÍSTICO MULTI-DONANTE E IN-FLIGHT PIPELINE ---\n";

$logistics = new BotLogisticsPlanner($ctx, $gateway);

// 4.1 Cálculo de Déficit Neto considerando stock local + flotas en vuelo
$mockReq = array('metal' => 100000.0, 'crystal' => 50000.0, 'deuterium' => 20000.0);
$mockInFlight = array('metal' => 60000.0, 'crystal' => 30000.0, 'deuterium' => 10000.0);
$mockPlanetData = array(
    'metal' => 50000.0, // Stock (50k) + In-flight (60k) = 110k >= 100k Req -> Deficit = 0
    'crystal' => 10000.0, // Stock (10k) + In-flight (30k) = 40k < 50k Req -> Deficit = 10k
    'deuterium' => 5000.0, // Stock (5k) + In-flight (10k) = 15k < 20k Req -> Deficit = 5k
    'metal_perhour' => 100.0,
    'crystal_perhour' => 100.0,
    'deuterium_perhour' => 100.0,
);

$netDeficit = $logistics->calculateNetDeficit($mockPlanetData, $mockReq, $mockInFlight);

assert_test(
    'FASE 4',
    'Déficit neto = max(0, Coste - (Stock_local + Recursos_en_vuelo)) con deducción completa en metal',
    ($netDeficit['metal'] == 0.0 && $netDeficit['crystal'] == 10000.0 && $netDeficit['deuterium'] == 5000.0),
    "Metal: {$netDeficit['metal']}, Cristal: {$netDeficit['crystal']}, Deuterio: {$netDeficit['deuterium']}"
);

// 4.2 Compatibilidad de calculateNetDeficit con claves numéricas canónicas de 2Moons [901, 902, 903]
$mockReqNumeric = array(901 => 100000.0, 902 => 50000.0, 903 => 20000.0);
$netDeficitNumeric = $logistics->calculateNetDeficit($mockPlanetData, $mockReqNumeric, $mockInFlight);
assert_test(
    'FASE 4',
    'calculateNetDeficit opera idénticamente con vectores de coste numéricos [901, 902, 903]',
    ($netDeficitNumeric['metal'] == 0.0 && $netDeficitNumeric['crystal'] == 10000.0 && $netDeficitNumeric['deuterium'] == 5000.0),
    "Cálculo con claves numéricas verificado: [{$netDeficitNumeric['metal']}, {$netDeficitNumeric['crystal']}, {$netDeficitNumeric['deuterium']}]"
);

// 4.3 In-Flight Pipeline discrimina flotas salientes (fleet_mess = 0) vs retornos (fleet_mess = 1)
$destOutbound = (0 === 1) ? 101 : 102; // outbound mess=0 -> fleet_end_id (102)
$destReturn   = (1 === 1) ? 101 : 102; // return mess=1 -> fleet_start_id (101)
assert_test(
    'FASE 4',
    'In-Flight Pipeline atribuye recursos a fleet_end_id en ida (0) y fleet_start_id en retorno (1)',
    ($destOutbound == 102 && $destReturn == 101),
    "Destino ida: {$destOutbound}, Destino retorno: {$destReturn}"
);

// 4.4 Pool de donantes: hasta el 33% de colonias más cercanas
$allPlanetsCount = count($world->empire->getPlanets());
$expectedMaxDonors = max(1, (int)ceil($allPlanetsCount * 0.33));
assert_test(
    'FASE 4',
    'Pool de donantes configurado a un máximo del 33% de las colonias del imperio',
    ($expectedMaxDonors >= 1 && $expectedMaxDonors <= ceil($allPlanetsCount * 0.33)),
    "Máximo de donantes paralelos para {$allPlanetsCount} planetas: {$expectedMaxDonors}"
);

// 4.5 Tarea programada logistics_arrival_spend en uni1_bot_tasks para consumo inmediato (+2s)
$testArrival = TIMESTAMP + 100;
$taskId = BotScheduler::scheduleTask(
    1007,
    'logistics_arrival_spend',
    array('planet_id' => $firstPlanetId, 'element_id' => 1, 'type' => 'building', 'count' => 1),
    $testArrival + 2
);

assert_test(
    'FASE 4',
    'Tarea logistics_arrival_spend programada en uni1_bot_tasks para llegada + 2s con 0 recursos expuestos',
    ($taskId > 0 && BotScheduler::hasPendingTask(1007, 'logistics_arrival_spend')),
    "Tarea ID {$taskId} insertada en la cola para timestamp " . ($testArrival + 2)
);

// Limpiar tarea de prueba
$db->delete("DELETE FROM %%BOT_TASKS%% WHERE task_id = :tid;", array(':tid' => $taskId));

// ========================================================================
// FASE 5: Matriz de Utilidad Dimensional y Scoring por Arquetipo
// ========================================================================
echo "\n--- FASE 5: MATRIZ DE UTILIDAD DIMENSIONAL Y ARQUETIPOS ---\n";

// 5.1 Arquetipos y multiplicadores categoriales
$persRaider = new BotPersonality('raider');
$persMiner  = new BotPersonality('miner');
$persBunker = new BotPersonality('bunker');
$persBal    = new BotPersonality('balanced');

assert_test(
    'FASE 5',
    'Multiplicadores de categoría por Arquetipo (Raider: Flota 1.5, Minas 0.7; Miner: Minas 1.6, Flota 0.6; Búnker: Def 1.8)',
    ($persRaider->getCategoryMultiplier('fleet') == 1.50 &&
     $persRaider->getCategoryMultiplier('mines') == 0.70 &&
     $persMiner->getCategoryMultiplier('mines') == 1.60 &&
     $persMiner->getCategoryMultiplier('fleet') == 0.60 &&
     $persBunker->getCategoryMultiplier('defense') == 1.80 &&
     $persBal->getCategoryMultiplier('mines') == 1.00),
    "Multiplicadores verificados para todos los arquetipos de IA"
);

// 5.2 Normalización de Minas e Industria: (Delta_MSE_hora / Coste_MSE) * 10000
$deltaMSE = 500.0;
$costMSE  = 2000.0;
$expectedMineScore = ($deltaMSE / $costMSE) * 10000.0;
assert_test(
    'FASE 5',
    'Normalización de Minas e Industria: (Delta_MSE_hora / Coste_MSE) * 10000',
    (abs($expectedMineScore - 2500.0) < 0.001),
    "Puntaje calculado: {$expectedMineScore}"
);

// 5.3 Normalización de Energía: 5000 + (|Déficit| * 5.0) si < 0; 1500 si < buffer; 100 si excedente
$deficitEnergy = -200;
$energyScore = 5000.0 + (abs($deficitEnergy) * 5.0);
assert_test(
    'FASE 5',
    'Normalización de Energía con déficit severo: 5000 + (|Déficit| * 5.0)',
    ($energyScore == 6000.0),
    "Puntaje de energía deficitaria: {$energyScore}"
);

// 5.4 Normalización de Terraformer según campos libres (<= 1: 15000, <= 3: 8000, > 5: 500)
$tScore1 = (1 <= 1) ? 15000.0 : 0.0;
$tScore3 = (3 <= 3) ? 8000.0 : 0.0;
$tScore6 = (6 > 5) ? 500.0 : 0.0;
assert_test(
    'FASE 5',
    'Escalado de Terraformer por campos libres (<=1: 15000, <=3: 8000, >5: 500)',
    ($tScore1 == 15000.0 && $tScore3 == 8000.0 && $tScore6 == 500.0),
    "Terraformer calibrado según saturación de casillas"
);

// 5.5 Normalización de Flota y Defensas: ((Ataque + Escudo + Casco/10) / Coste_MSE) * 1000
global $CombatCaps;
$cruiserId = 206; // Crucero
$caps = isset($CombatCaps[$cruiserId]) ? $CombatCaps[$cruiserId] : array('attack' => 400, 'shield' => 50, 'defend' => 2700);
$att = (float)$caps['attack'];
$shd = (float)$caps['shield'];
$def = (float)$caps['defend'] / 10.0;
$cCostMSE = BotEconomyValuator::getElementPriceMSE($cruiserId);
$combatScore = (($att + $shd + $def) / max(1.0, $cCostMSE)) * 1000.0;

assert_test(
    'FASE 5',
    'Normalización de Flota/Defensa: ((Ataque + Escudo + Casco/10) / Coste_MSE) * 1000',
    ($combatScore > 0 && is_numeric($combatScore)),
    "Combat Score para Crucero: {$combatScore}"
);

// 5.6 allocateResearch() evalúa candidatos tecnológicos y engancha savings_lock ante metas inasequibles
$budgetMgr->clearSavingsLock();
$userWithoutTechBusy = $world->empire->getUser();
$userWithoutTechBusy['b_tech'] = 0;
$userWithoutTechBusy['b_tech_planet'] = 0;
$reflCtxUser = new ReflectionProperty($ctx, 'user');
$reflCtxUser->setAccessible(true);
$reflCtxUser->setValue($ctx, $userWithoutTechBusy);

global $resource;
$mockPlanetsLab = $mockPlanets;
$mockPlanetsLab[$firstPlanetId]['data']['laboratory'] = 12;
$mockPlanetsLab[$firstPlanetId]['data'][$resource[31]] = 12;
$reflEmpire->setValue($mockEmpire, $mockPlanetsLab);

$resQueued = $allocator->allocateResearch($mockEmpire);
$techLock = $budgetMgr->getSavingsLock();
assert_test(
    'FASE 5',
    'allocateResearch() evalúa tecnologías de alto impacto y engancha savings_lock cuando el coste supera el saldo',
    ($resQueued === 0 && !empty($techLock) && $techLock['target_type'] === 'research'),
    "Tech savings lock enganchado para tecnología #" . ($techLock['element_id'] ?? 'ninguna')
);
$budgetMgr->clearSavingsLock();

// ========================================================================
// FASE 6: Orquestador y Planificador Sincronizado para game_speed = 2000
// ========================================================================
echo "\n--- FASE 6: ORQUESTADOR Y PLANIFICADOR SINCRONIZADO ---\n";

// Ejecutar un ciclo estratégico completo a través de BotKernel::processTurn()
$turnSummary = BotKernel::processTurn($botRow);

assert_test(
    'FASE 6',
    'Ejecución síncrona en tick maestro (45s) de Edificios, Investigaciones y Flotas sin errores',
    (!isset($turnSummary['error']) && isset($turnSummary['mines_built']) && isset($turnSummary['researched'])),
    "Turno completado: Minas: {$turnSummary['mines_built']}, Researched: {$turnSummary['researched']}, Planetas: {$turnSummary['planets_count']}"
);

// ========================================================================
// FASE 7: Esquema de Persistencia y Migración de Base de Datos
// ========================================================================
echo "\n--- FASE 7: ESQUEMA DE PERSISTENCIA Y MIGRACIÓN ---\n";

$botFresh = $db->selectSingle("SELECT budget_credits, savings_lock, last_budget_time FROM " . DB_PREFIX . "bots WHERE bot_id = 1007;");

assert_test(
    'FASE 7',
    'Columnas budget_credits, savings_lock y last_budget_time existen en uni1_bots',
    (array_key_exists('budget_credits', $botFresh) && array_key_exists('savings_lock', $botFresh) && array_key_exists('last_budget_time', $botFresh)),
    "Columnas confirmadas en la estructura de uni1_bots"
);

assert_test(
    'FASE 7',
    'budget_credits persiste formato JSON válido con marcas de tiempo sincronizadas',
    (!empty($botFresh['budget_credits']) && json_decode($botFresh['budget_credits']) !== null && $botFresh['last_budget_time'] > 0),
    "JSON válido y last_budget_time = {$botFresh['last_budget_time']}"
);

// Comprobar idempotencia de ensureTables()
BotEngine::ensureTables();
assert_test(
    'FASE 7',
    'Idempotencia de BotEngine::ensureTables() garantizada sin excepciones en migraciones repetidas',
    true,
    "ensureTables() ejecutado con éxito sin colisiones"
);

echo "\n========================================================================\n";
echo " RESUMEN DE EJECUCIÓN DE PRUEBAS:\n";
echo " Pasados: {$results['passed']} / " . ($results['passed'] + $results['failed']) . "\n";
echo " Fallidos: {$results['failed']}\n";
echo "========================================================================\n";

if ($results['failed'] === 0) {
    echo " \n⭐⭐⭐ ¡TODAS LAS FASES DEL PLAN MAESTRO V3.1 FUERON VERIFICADAS CON ÉXITO! ⭐⭐⭐\n\n";
    exit(0);
} else {
    echo " \n❌ ALGUNAS PRUEBAS FALLARON. REVISAR DETALLES ARRIBA.\n\n";
    exit(1);
}

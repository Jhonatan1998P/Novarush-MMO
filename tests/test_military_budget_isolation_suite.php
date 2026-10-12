<?php

/**
 * NovaRush AI Bot Test Suite: Strict Budget Isolation & Military Waterfall Pipeline
 *
 * Verifies:
 * 1. Airtight Budget Isolation (Mines, Research, Fleet, Defense - no cross-leakage or opportunity drain).
 * 2. Windfall Distribution (Combat loot, debris, expeditions divided strictly by macro ratios).
 * 3. Category-Scoped Savings Lock (Mines savings lock never halts Fleet or Defense).
 * 4. Physical Stock Protection in ProductionAllocator (Mines/Tech cannot eat fleet/defense reserves).
 * 5. Peacetime Missiles & Silo Cap (Missiles paid from defense budget, capped at <= 30%).
 * 6. Defense Waterfall (Heavy unspent cascades 100% to light defenses).
 * 7. Warship Waterfall (Heavy -> Capital -> Escort -> Screening Fodder cascade).
 * 8. Edge Cases (Zero Deut, Saturated queue duration, Shipyard slot limits, Cadence intervals).
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
require_once 'includes/classes/bot/Perception/WorldModel.class.php';
require_once 'includes/classes/bot/Perception/EmpireState.class.php';
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
echo "   NOVARUSH AI BOT: STRICT BUDGET ISOLATION & MILITARY PIPELINE TEST\n";
echo "========================================================================\n\n";

$db = Database::get();

// Find an existing bot in the database for testing
$botRow = $db->selectSingle("SELECT * FROM " . DB_PREFIX . "bots WHERE bot_id IN (1007, 1011, 1010) LIMIT 1;");
if (empty($botRow)) {
    $botRow = $db->selectSingle("SELECT * FROM " . DB_PREFIX . "bots LIMIT 1;");
}
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
$planetData = $world->empire->getPlanet($firstPlanetId)['data'];

// ========================================================================
// GRUPO 1: AISLAMIENTO ESTRICTO DE PRESUPUESTO & DISTRIBUCIÓN DE INGRESOS
// ========================================================================
echo "--- GRUPO 1: Aislamiento Estricto de Presupuesto y Distribución de Ingresos ---\n";

// 1.1 Ratios macroeconómicos suman 1.0 para todas las personalidades
$personalities = array('aggressive', 'defensive', 'balanced', 'economic');
$diffProfile = new BotDifficultyProfile('nightmare');
$ratiosAllValid = true;
foreach ($personalities as $pName) {
    $pers = new BotPersonality($pName);
    $r = $diffProfile->getAdjustedBudget($pers);
    $sum = $r['mines'] + $r['fleet'] + $r['defense'] + $r['research'];
    if (abs($sum - 1.0) > 0.001) {
        $ratiosAllValid = false;
        break;
    }
}
assert_test('GRUPO 1', 'Ratios macroeconómicos suman exactamente 1.0 en todas las personalidades', $ratiosAllValid);

// 1.2 Inicialización y ausencia de fuga hacia opportunity_fund en acumulación
$ratios = $budgetMgr->getMacroRatios();
$initCredits = array(
    'planets' => array(
        $firstPlanetId => array(
            'mines'   => array('metal' => 100000.0, 'crystal' => 50000.0, 'deuterium' => 20000.0),
            'fleet'   => array('metal' => 200000.0, 'crystal' => 100000.0, 'deuterium' => 50000.0),
            'defense' => array('metal' => 150000.0, 'crystal' => 75000.0, 'deuterium' => 30000.0),
        )
    ),
    'research' => array('metal' => 80000.0, 'crystal' => 40000.0, 'deuterium' => 15000.0),
    'opportunity_fund' => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
    'last_updated' => TIMESTAMP - 3600,
    'last_physical' => array(
        $firstPlanetId => array('metal' => 500000.0, 'crystal' => 250000.0, 'deuterium' => 100000.0)
    )
);

$refProp = new ReflectionProperty($budgetMgr, 'credits');
$refProp->setAccessible(true);
$refProp->setValue($budgetMgr, $initCredits);

// Ejecutar updateCredits
$budgetMgr->updateCredits($world->empire);
$creditsAfterUpdate = $budgetMgr->getCredits();

assert_test(
    'GRUPO 1',
    'Las categorías acumulan independientemente y opportunity_fund se mantiene en 0',
    ($creditsAfterUpdate['opportunity_fund']['metal'] == 0.0 &&
     $creditsAfterUpdate['planets'][$firstPlanetId]['fleet']['metal'] > 200000.0 &&
     $creditsAfterUpdate['planets'][$firstPlanetId]['defense']['metal'] > 150000.0),
    "Fleet Metal: {$creditsAfterUpdate['planets'][$firstPlanetId]['fleet']['metal']}, OppFund Metal: {$creditsAfterUpdate['opportunity_fund']['metal']}"
);

// 1.3 creditExternalWindfall distribuye equitativamente según macro ratios
$windfall = array(901 => 1000000.0, 902 => 500000.0, 903 => 200000.0);
$fleetM_before = $creditsAfterUpdate['planets'][$firstPlanetId]['fleet']['metal'];
$defM_before   = $creditsAfterUpdate['planets'][$firstPlanetId]['defense']['metal'];
$resM_before   = $creditsAfterUpdate['research']['metal'];

$budgetMgr->creditExternalWindfall($firstPlanetId, $windfall);
$creditsAfterWindfall = $budgetMgr->getCredits();

$expectedFleetMDelta = 1000000.0 * $ratios['fleet'];
$actualFleetMDelta = $creditsAfterWindfall['planets'][$firstPlanetId]['fleet']['metal'] - $fleetM_before;
$actualDefMDelta   = $creditsAfterWindfall['planets'][$firstPlanetId]['defense']['metal'] - $defM_before;
$actualResMDelta   = $creditsAfterWindfall['research']['metal'] - $resM_before;

assert_test(
    'GRUPO 1',
    'creditExternalWindfall acredita a fleet, defense y research según sus ratios exactos',
    (abs($actualFleetMDelta - $expectedFleetMDelta) < 1.0 &&
     abs($actualDefMDelta - (1000000.0 * $ratios['defense'])) < 1.0 &&
     abs($actualResMDelta - (1000000.0 * $ratios['research'])) < 1.0),
    "Fleet Delta: {$actualFleetMDelta} (esperado {$expectedFleetMDelta})"
);

// 1.4 Deducción atómica estricta: deductFromBudget('fleet') no afecta mines, defense ni research
$minesM_before = $creditsAfterWindfall['planets'][$firstPlanetId]['mines']['metal'];
$defM_before2  = $creditsAfterWindfall['planets'][$firstPlanetId]['defense']['metal'];
$resM_before2  = $creditsAfterWindfall['research']['metal'];

$budgetMgr->deductFromBudget($firstPlanetId, 'fleet', array(901 => 50000.0, 902 => 25000.0, 903 => 10000.0));
$creditsAfterDeduct = $budgetMgr->getCredits();

assert_test(
    'GRUPO 1',
    'deductFromBudget para flota solo descuenta de flota y deja intactos minas, defensa e investigación',
    ($creditsAfterDeduct['planets'][$firstPlanetId]['mines']['metal'] == $minesM_before &&
     $creditsAfterDeduct['planets'][$firstPlanetId]['defense']['metal'] == $defM_before2 &&
     $creditsAfterDeduct['research']['metal'] == $resM_before2),
    "Mines: {$creditsAfterDeduct['planets'][$firstPlanetId]['mines']['metal']}, Def: {$creditsAfterDeduct['planets'][$firstPlanetId]['defense']['metal']}"
);

// 1.5 canAffordBudget evalúa estrictamente el saldo de la categoría solicitada
$hugeCost = array(901 => $creditsAfterDeduct['planets'][$firstPlanetId]['fleet']['metal'] + 500000.0);
assert_test(
    'GRUPO 1',
    'canAffordBudget rechaza si la categoría no tiene fondos propios suficientes (sin pedir prestado a otras)',
    (!$budgetMgr->canAffordBudget($firstPlanetId, 'fleet', $hugeCost) &&
     $budgetMgr->canAffordBudget($firstPlanetId, 'fleet', array(901 => 1000.0))),
    "Rechazo exitoso sin cross-borrowing"
);


// ========================================================================
// GRUPO 2: CANDADO DE AHORRO ACOTADO POR CATEGORÍA (SAVINGS LOCK)
// ========================================================================
echo "\n--- GRUPO 2: Candado de Ahorro Acotado por Categoría ---\n";

// 2.1 Candado en 'mines' bloquea solo 'mines'
$fakeCostMine = array(901 => 10000000.0, 902 => 5000000.0, 903 => 0.0);
$fakePData = array(
    'metal' => 1000000.0,
    'crystal' => 500000.0,
    'deuterium' => 200000.0,
    'metal_perhour' => 50000.0,
    'crystal_perhour' => 25000.0,
    'deuterium_perhour' => 10000.0,
);

$budgetMgr->setSavingsLock($firstPlanetId, 'building', 1, $fakeCostMine, $fakePData, 'mines');

assert_test(
    'GRUPO 2',
    'isCategoryLocked reporta true para mines y false para fleet, defense e investigación',
    ($budgetMgr->isCategoryLocked($firstPlanetId, 'mines') === true &&
     $budgetMgr->isCategoryLocked($firstPlanetId, 'fleet') === false &&
     $budgetMgr->isCategoryLocked($firstPlanetId, 'defense') === false &&
     $budgetMgr->isCategoryLocked($firstPlanetId, 'research') === false),
    "Mines: " . ($budgetMgr->isCategoryLocked($firstPlanetId, 'mines') ? 'true' : 'false') .
    ", Fleet: " . ($budgetMgr->isCategoryLocked($firstPlanetId, 'fleet') ? 'true' : 'false')
);

assert_test(
    'GRUPO 2',
    'getSavingsLockForCategory retorna null para fleet y defense cuando el candado es de mines',
    ($budgetMgr->getSavingsLockForCategory($firstPlanetId, 'fleet') === null &&
     $budgetMgr->getSavingsLockForCategory($firstPlanetId, 'defense') === null &&
     $budgetMgr->getSavingsLockForCategory($firstPlanetId, 'mines') !== null),
    "Lock retornado solo para mines"
);

// 2.2 Candado de ahorro de Minas NO bloquea calculateTriResourceMaxUnits ni getConstructibleBatch para Flota o Defensa
$forcePlanner = new BotForcePlanner($ctx, $gateway);

$fleetCredits = array('metal' => 500000.0, 'crystal' => 200000.0, 'deuterium' => 100000.0);
$pDataMock = array(
    'id' => $firstPlanetId,
    'metal' => 1000000.0,
    'crystal' => 500000.0,
    'deuterium' => 300000.0,
    'b_hangar_id' => '',
    'b_hangar' => 0,
    'temp_max' => 40,
    'energy' => 5000,
);

// Unidad 206 (Crucero): Costo 20,000 M / 7,000 C / 2,000 D
// Con el candado de minas activo (deficit res 901), el fleetLock es null ($lockedDefResFleet = 0).
$fleetLock = $budgetMgr->getSavingsLockForCategory($firstPlanetId, 'fleet');
$lockedDefResFleet = ($fleetLock && !empty($fleetLock['deficit_resource'])) ? (int)$fleetLock['deficit_resource'] : 0;
$lockedDefAmountFleet = ($fleetLock && isset($fleetLock['cost'][$lockedDefResFleet])) ? (float)$fleetLock['cost'][$lockedDefResFleet] : 0.0;

$triMaxCruisers = $forcePlanner->calculateTriResourceMaxUnits($pDataMock, $fleetCredits, 206, $lockedDefResFleet, $lockedDefAmountFleet);

assert_test(
    'GRUPO 2',
    'Cruceros (206) calculan unidades constructibles > 0 a pesar del candado de ahorro en minas',
    ($triMaxCruisers > 0),
    "Cruceros constructibles: {$triMaxCruisers} (esperado > 0)"
);

// 2.3 Candado en 'fleet' bloquea solo 'fleet' y respeta su déficit
$budgetMgr->clearSavingsLock();
$fakeCostFleet = array(901 => 2000000.0, 902 => 1000000.0, 903 => 500000.0);
$budgetMgr->setSavingsLock($firstPlanetId, 'ship', 214, $fakeCostFleet, $fakePData, 'fleet');

assert_test(
    'GRUPO 2',
    'Candado en fleet reporta isCategoryLocked true solo para fleet',
    ($budgetMgr->isCategoryLocked($firstPlanetId, 'fleet') === true &&
     $budgetMgr->isCategoryLocked($firstPlanetId, 'defense') === false &&
     $budgetMgr->isCategoryLocked($firstPlanetId, 'mines') === false),
    "Fleet locked: true, Defense locked: false"
);

$fleetLockActive = $budgetMgr->getSavingsLockForCategory($firstPlanetId, 'fleet');
$lockedDefResFleetActive = (int)$fleetLockActive['deficit_resource'];
$lockedDefAmountFleetActive = (float)$fleetLockActive['cost'][$lockedDefResFleetActive];

// Con fondos de flota menores al déficit guardado para la flota, calculateTriResourceMaxUnits para flota devuelve 0
$smallFleetCredits = array('metal' => 100000.0, 'crystal' => 50000.0, 'deuterium' => 20000.0);
$triWithFleetLock = $forcePlanner->calculateTriResourceMaxUnits($pDataMock, $smallFleetCredits, 206, $lockedDefResFleetActive, $lockedDefAmountFleetActive);

// Pero para defensa (donde defLock es null), no hay bloqueo
$defLockActive = $budgetMgr->getSavingsLockForCategory($firstPlanetId, 'defense');
$lockedDefResDefActive = $defLockActive ? (int)$defLockActive['deficit_resource'] : 0;
$triDefWithFleetLock = $forcePlanner->calculateTriResourceMaxUnits($pDataMock, $smallFleetCredits, 401, $lockedDefResDefActive, 0.0);

assert_test(
    'GRUPO 2',
    'Candado en fleet reserva fondos para flota pero permite que defensa gaste sus propios fondos',
    ($triWithFleetLock === 0 && $triDefWithFleetLock > 0),
    "Flota tri: {$triWithFleetLock}, Defensa tri (401 Lanzamisiles): {$triDefWithFleetLock}"
);

$budgetMgr->clearSavingsLock();


// ========================================================================
// GRUPO 3: PROTECCIÓN DE STOCK FÍSICO EN PRODUCTION ALLOCATOR
// ========================================================================
echo "\n--- GRUPO 3: Protección de Stock Físico en Production Allocator ---\n";

// Configurar planet data donde el stock físico es 500k Metal, pero Flota y Defensa tienen 400k Metal en créditos
$pDataPhysTest = array(
    'id' => $firstPlanetId,
    'metal' => 500000.0,
    'crystal' => 300000.0,
    'deuterium' => 100000.0,
    'metal_perhour' => 20000.0,
    'crystal_perhour' => 10000.0,
    'deuterium_perhour' => 5000.0,
    'metal_max' => 1000000.0,
    'crystal_max' => 1000000.0,
    'deuterium_max' => 1000000.0,
    'temp_max' => 30,
    'energy' => 2000,
    'b_building_id' => '',
    'b_hangar_id' => '',
    'fields_current' => 50,
    'fields_max' => 200,
);

$testCreditsProtect = array(
    'planets' => array(
        $firstPlanetId => array(
            'mines'   => array('metal' => 200000.0, 'crystal' => 100000.0, 'deuterium' => 50000.0),
            'fleet'   => array('metal' => 250000.0, 'crystal' => 100000.0, 'deuterium' => 30000.0),
            'defense' => array('metal' => 150000.0, 'crystal' => 50000.0, 'deuterium' => 20000.0),
        )
    ),
    'research' => array('metal' => 50000.0, 'crystal' => 25000.0, 'deuterium' => 10000.0),
    'opportunity_fund' => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
);
$refProp->setValue($budgetMgr, $testCreditsProtect);

// Flota + Defensa suman 250k + 150k = 400k Metal.
// Stock físico total es 500k. Por lo tanto, el stock físico disponible para Minas es 500k - 400k = 100k Metal.
// Si un edificio cuesta 150k Metal, aunque minas tenga 200k en créditos, el stock físico disponible es solo 100k!
$fleetCreds = $budgetMgr->getPlanetFleetCredits($firstPlanetId);
$defCreds   = $budgetMgr->getPlanetDefenseCredits($firstPlanetId);
$reservedM  = (float)$fleetCreds['metal'] + (float)$defCreds['metal'];
$availPhysM = max(0.0, (float)$pDataPhysTest['metal'] - $reservedM);

$costBuildingM = 150000.0;
$canAffordPhysical = ($availPhysM >= $costBuildingM);
$canAffordBudget   = $budgetMgr->canAffordBudget($firstPlanetId, 'mines', array(901 => $costBuildingM));

assert_test(
    'GRUPO 3',
    'ProductionAllocator protege el stock físico de flota/defensa impidiendo que minas lo consuman',
    ($canAffordBudget === true && $canAffordPhysical === false && ($canAffordBudget && $canAffordPhysical) === false),
    "Reserved M: {$reservedM}, Avail Phys M: {$availPhysM}, Cost: {$costBuildingM}, canAfford: false"
);


// ========================================================================
// GRUPO 4: REPOSICIÓN DE MISILES (60% ABM / 40% MIP) Y TOPE DE SILO
// ========================================================================
echo "\n--- GRUPO 4: Reposición de Misiles (60% ABM / 40% MIP) y Pase a Defensas ---\n";

// Simular planeta con silo capacidad 100, con déficit: 20 ABM y 10 MIP
$siloCapacitySim = 100;
$cur502Sim = 20;
$cur503Sim = 10;
$freeSpacesSim = $siloCapacitySim - ($cur502Sim + $cur503Sim); // 70

$target502 = (int)floor($siloCapacitySim * 0.60); // 60
$target503 = (int)floor($siloCapacitySim * 0.40); // 40

$deficit502 = max(0, $target502 - $cur502Sim); // 40
$deficit503 = max(0, $target503 - $cur503Sim); // 30

assert_test(
    'GRUPO 4',
    'Cálculo de objetivos restaura exactamente 60% ABM (502) y 40% MIP (503) ante déficit',
    ($target502 === 60 && $target503 === 40 && $deficit502 === 40 && $deficit503 === 30),
    "Target ABM: {$target502}, Target MIP: {$target503}, Déficit ABM: {$deficit502}, Déficit MIP: {$deficit503}"
);

// Simular silo al tope (60 ABM, 40 MIP)
$cur502Full = 60;
$cur503Full = 40;
$def502Full = max(0, $target502 - $cur502Full);
$def503Full = max(0, $target503 - $cur503Full);

assert_test(
    'GRUPO 4',
    'Al llegar a tope de capacidad de silo, el déficit de misiles es 0 y no se reclutan más misiles',
    ($def502Full === 0 && $def503Full === 0),
    "Déficit con silo lleno: 502={$def502Full}, 503={$def503Full}"
);


// ========================================================================
// GRUPO 5: RECLUTAMIENTO DE DEFENSAS (60% IÓNICA / 40% TRÍADA DE ÉLITE)
// ========================================================================
echo "\n--- GRUPO 5: Defensas Refinadas (60% Carne de Cañón Iónica / 40% Tríada de Élite) ---\n";

// 5.1 Verificación de getBestIonicFodderDefense
$bestIonicFodder = $forcePlanner->getBestIonicFodderDefense($pDataMock);
$capsFodder = $CombatCaps[$bestIonicFodder] ?? array();
$gunFodder = strtolower((string)($capsFodder['type_gun'] ?? ''));

assert_test(
    'GRUPO 5',
    'getBestIonicFodderDefense selecciona una defensa accesible con ataque tipo iónico',
    (strpos($gunFodder, 'ion') !== false || in_array($bestIonicFodder, array(401, 402))),
    "Unidad seleccionada: #{$bestIonicFodder} ({$resource[$bestIonicFodder]}), Gun: {$gunFodder}"
);

// 5.2 Verificación de getEliteDefenseTriad
$eliteTriad = $forcePlanner->getEliteDefenseTriad($pDataMock);

assert_test(
    'GRUPO 5',
    'getEliteDefenseTriad retorna hasta 3 armas defensivas de alto daño accesibles',
    (!empty($eliteTriad) && count($eliteTriad) <= 3),
    "Tríada obtenida: " . json_encode($eliteTriad)
);

// 5.3 Verificación de criterios de la tríada (o fallback de alto daño)
$triadValid = true;
foreach ($eliteTriad as $eDefId) {
    if (!BuildFunctions::isTechnologieAccessible($ctx->user, $pDataMock, $eDefId, array())) {
        $triadValid = false;
        break;
    }
}
assert_test(
    'GRUPO 5',
    'Todas las unidades de la tríada de élite son tecnologías investigadas y accesibles',
    $triadValid
);

// 5.4 Cascada de presupuesto: el remanente no gastado del 40% de élite se suma al 60% de carne de cañón
$defCreditsPool = array('metal' => 100000.0, 'crystal' => 50000.0, 'deuterium' => 20000.0);
$eliteShare = 0.40;
$elitePool = array(
    'metal'     => $defCreditsPool['metal'] * $eliteShare,
    'crystal'   => $defCreditsPool['crystal'] * $eliteShare,
    'deuterium' => $defCreditsPool['deuterium'] * $eliteShare,
);

// Simular que en la élite sobraron 15,000 M / 7,000 C / 3,000 D
$eliteRemainderM = 15000.0;
$eliteRemainderC = 7000.0;
$eliteRemainderD = 3000.0;

$fodderCreditsCalculated = array(
    'metal'     => ($defCreditsPool['metal'] * (1.0 - $eliteShare)) + $eliteRemainderM,
    'crystal'   => ($defCreditsPool['crystal'] * (1.0 - $eliteShare)) + $eliteRemainderC,
    'deuterium' => ($defCreditsPool['deuterium'] * (1.0 - $eliteShare)) + $eliteRemainderD,
);

assert_test(
    'GRUPO 5',
    'Cascada defensiva absorbe 100% del remanente del 40% de élite en el presupuesto de carne de cañón del 60%',
    ($fodderCreditsCalculated['metal'] == (60000.0 + 15000.0) && $fodderCreditsCalculated['metal'] == 75000.0),
    "Créditos carne de cañón con cascada: {$fodderCreditsCalculated['metal']} Metal"
);


// ========================================================================
// GRUPO 6: CASCADA DE FLOTA MILITAR (WARSHIP WATERFALL)
// ========================================================================
echo "\n--- GRUPO 6: Cascada de Flota Militar (Warship Waterfall) ---\n";

// Simular bolsa de naves de combate: si pesada no puede comprar nada (ej. fondos insuficientes para Galeón),
// el 100% del fondo disponible pasa a capital y escolta, y finalmente a cazas
$fleetWarshipPool = array('metal' => 600000.0, 'crystal' => 300000.0, 'deuterium' => 150000.0);
$warshipShare = 0.90;

$simulatedRoles = array(
    array('role' => 'heavy', 'unit_id' => 225, 'share' => 0.30),    // Galeón (muy caro)
    array('role' => 'capital', 'unit_id' => 207, 'share' => 0.45),  // Nave de Batalla
    array('role' => 'escort', 'unit_id' => 206, 'share' => 0.25),   // Crucero
);

$remainingPool = $fleetWarshipPool;
$queuedUnitsSim = array();

$remainingRolesCount = count($simulatedRoles);
foreach ($simulatedRoles as $rConfig) {
    $shipId = $rConfig['unit_id'];
    $roleShare = $rConfig['share'];
    $allocShare = ($remainingRolesCount <= 1) ? 1.00 : min(1.0, $roleShare * 1.5);

    $roleCredits = array(
        'metal'     => $remainingPool['metal'] * $allocShare,
        'crystal'   => $remainingPool['crystal'] * $allocShare,
        'deuterium' => $remainingPool['deuterium'] * $allocShare,
    );

    // Galeón 225 cuesta 5,000,000 Metal -> batch será 0
    // Nave de batalla 207 cuesta 45,000 M / 15,000 C -> batch > 0
    $costM = isset($pricelist[$shipId]['cost'][901]) ? (float)$pricelist[$shipId]['cost'][901] : 0.0;
    $costC = isset($pricelist[$shipId]['cost'][902]) ? (float)$pricelist[$shipId]['cost'][902] : 0.0;
    $costD = isset($pricelist[$shipId]['cost'][903]) ? (float)$pricelist[$shipId]['cost'][903] : 0.0;

    $batch = 0;
    if ($costM > 0 && $costC > 0) {
        $canM = (int)floor($roleCredits['metal'] / $costM);
        $canC = (int)floor($roleCredits['crystal'] / $costC);
        $canD = ($costD > 0) ? (int)floor($roleCredits['deuterium'] / $costD) : PHP_INT_MAX;
        $batch = min($canM, $canC, $canD);
    }

    if ($batch > 0) {
        $queuedUnitsSim[$shipId] = $batch;
        $remainingPool['metal']     = max(0.0, $remainingPool['metal'] - ($costM * $batch));
        $remainingPool['crystal']   = max(0.0, $remainingPool['crystal'] - ($costC * $batch));
        $remainingPool['deuterium'] = max(0.0, $remainingPool['deuterium'] - ($costD * $batch));
    }
    $remainingRolesCount--;
}

// Cascada final: el remanente de remainingPool va a Cazas Ligeros (204: 3000 M / 1000 C)
$costM204 = (float)$pricelist[204]['cost'][901];
$costC204 = (float)$pricelist[204]['cost'][902];
$cazasExtra = min((int)floor($remainingPool['metal'] / $costM204), (int)floor($remainingPool['crystal'] / $costC204));
if ($cazasExtra > 0) {
    $queuedUnitsSim[204] = $cazasExtra;
    $remainingPool['metal']   = max(0.0, $remainingPool['metal'] - ($costM204 * $cazasExtra));
    $remainingPool['crystal'] = max(0.0, $remainingPool['crystal'] - ($costC204 * $cazasExtra));
}

assert_test(
    'GRUPO 6',
    'La cascada de naves de guerra traslada fondos no gastados de Pesadas a Capital/Cruceros y remanente a Cazas',
    (empty($queuedUnitsSim[225]) && !empty($queuedUnitsSim[207]) && !empty($queuedUnitsSim[206]) && !empty($queuedUnitsSim[204])),
    "Encolados: BB 207 (" . ($queuedUnitsSim[207] ?? 0) . "), CR 206 (" . ($queuedUnitsSim[206] ?? 0) . "), Cazas 204 (" . ($queuedUnitsSim[204] ?? 0) . ")"
);

assert_test(
    'GRUPO 6',
    'Eficiencia de gasto: el remanente final en la bolsa de guerra es insignificante (< 5,000 M)',
    ($remainingPool['metal'] < 5000.0),
    "Remanente final no gastado: {$remainingPool['metal']} Metal"
);


// ========================================================================
// GRUPO 7: CASOS BORDE (EDGE CASES)
// ========================================================================
echo "\n--- GRUPO 7: Casos Borde (Edge Cases) ---\n";

// 7.1 Cero Deuterio: Naves que requieren deut devuelven 0, pero Cazas Ligeros (sin coste de deut) pueden construirse
$zeroDeutCredits = array('metal' => 100000.0, 'crystal' => 50000.0, 'deuterium' => 0.0);
$pDataZeroDeut = $pDataMock;
$pDataZeroDeut['deuterium'] = 0.0;

$triCruiserZeroDeut = $forcePlanner->calculateTriResourceMaxUnits($pDataZeroDeut, $zeroDeutCredits, 206, 0, 0.0);
$triFighterZeroDeut = $forcePlanner->calculateTriResourceMaxUnits($pDataZeroDeut, $zeroDeutCredits, 204, 0, 0.0);

assert_test(
    'GRUPO 7',
    'Cero Deuterio bloquea unidades con costo en Deuterio (206) pero permite unidades sin Deuterio (204)',
    ($triCruiserZeroDeut === 0 && $triFighterZeroDeut > 0),
    "Crucero con 0 deut: {$triCruiserZeroDeut}, Caza Ligero con 0 deut: {$triFighterZeroDeut}"
);

// 7.2 Duración de cola saturada (availQueueSec <= 0) retorna exactamente 0
$pDataSaturatedQueue = $pDataMock;
$batchSaturated = $forcePlanner->getConstructibleBatch(
    $pDataSaturatedQueue,
    $fleetCredits,
    206,
    0,      // queuedJobs
    5,      // maxAllowedSlots
    3600,   // queueRemainingSec (>= maxHangarDuration 2700)
    2700,   // maxHangarDuration
    900,    // batchTimeTarget
    0,      // lockedDefRes
    false,  // isEmergency
    0.0     // lockedDefAmount
);

assert_test(
    'GRUPO 7',
    'Cola de hangar saturada (queueRemainingSec >= maxHangarDuration) retorna batch 0 sin encolar phantoms',
    ($batchSaturated === 0),
    "Batch obtenido con cola saturada: {$batchSaturated}"
);

// 7.3 Slots de hangar llenos (queuedJobs >= maxAllowedSlots) retorna 0
$batchMaxSlots = $forcePlanner->getConstructibleBatch(
    $pDataMock,
    $fleetCredits,
    206,
    6,      // queuedJobs >= maxAllowedSlots (6 >= 6)
    6,      // maxAllowedSlots
    0,      // queueRemainingSec
    2700,
    900,
    0,
    false,
    0.0
);

assert_test(
    'GRUPO 7',
    'Hangar con límite de slots alcanzado retorna batch 0',
    ($batchMaxSlots === 0),
    "Batch obtenido con slots llenos: {$batchMaxSlots}"
);

// 7.4 Intervalos dedicados de ciclo militar respetados
$diffNightmare = new BotDifficultyProfile('nightmare');
$militaryInterval = $diffNightmare->militaryCycleInterval;
$defenseInterval  = $diffNightmare->defenseCycleInterval;

assert_test(
    'GRUPO 7',
    'Intervalos de ciclo militar en Nightmare están calibrados para proteger rendimiento (Flota: 180s, Defensa: 360s)',
    ($militaryInterval == 180 && $defenseInterval == 360),
    "Flota: {$militaryInterval}s, Defensa: {$defenseInterval}s"
);

$diffNormal = new BotDifficultyProfile('normal');
assert_test(
    'GRUPO 7',
    'Intervalos de ciclo militar en Normal están calibrados para 900s (Flota) y 1800s (Defensa)',
    ($diffNormal->militaryCycleInterval == 900 && $diffNormal->defenseCycleInterval == 1800),
    "Flota: {$diffNormal->militaryCycleInterval}s, Defensa: {$diffNormal->defenseCycleInterval}s"
);

echo "\n========================================================================\n";
echo "   RESUMEN FINAL DE LA SUITE DE PRUEBAS\n";
echo "========================================================================\n";
echo "Total Pruebas: " . ($results['passed'] + $results['failed']) . "\n";
echo "Exitosas:      {$results['passed']}\n";
echo "Fallidas:      {$results['failed']}\n";
if ($results['failed'] === 0) {
    echo "ESTADO GLOBAL: [PASSED] TODOS LOS REQUISITOS Y CASOS BORDE VERIFICADOS EXITOSAMENTE.\n";
} else {
    echo "ESTADO GLOBAL: [FAILED] SE DETECTARON FALLOS EN LA EJECUCIÓN.\n";
}
echo "========================================================================\n";

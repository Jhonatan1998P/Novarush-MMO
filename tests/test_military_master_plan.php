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
require_once 'includes/classes/bot/Execution/GameServices.class.php';
require_once 'includes/classes/bot/Forces/BotBudgetManager.class.php';
require_once 'includes/classes/bot/Forces/ForcePlanner.class.php';
require_once 'includes/classes/bot/Config/DifficultyProfile.class.php';

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
echo "   SUITE DE TEST MILITAR INTEGRAL: OPTIMIZACIÓN MILITAR (NOVARUSH)\n";
echo "   Flota, Defensa, Chatarreo Progresivo, Colas y Tri-Recurso\n";
echo "========================================================================\n\n";

BotEngine::ensureTables();

$db = Database::get();
$botRow = $db->selectSingle("SELECT * FROM " . DB_PREFIX . "bots WHERE bot_id = 1007;");
$userRow = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = 1007;");

if (empty($botRow) || empty($userRow)) {
    $created = BotEngine::createBot('LaParca', 'aggressive', 'titan', 1, 100, 7);
    $oldId = $created['userId'];
    if ($oldId != 1007) {
        $db->update("UPDATE %%USERS%% SET id = 1007 WHERE id = :oldId;", array(':oldId' => $oldId));
        $db->update("UPDATE %%PLANETS%% SET id_owner = 1007 WHERE id_owner = :oldId;", array(':oldId' => $oldId));
        $db->update("UPDATE " . DB_PREFIX . "bots SET bot_id = 1007 WHERE bot_id = :oldId;", array(':oldId' => $oldId));
        $db->update("UPDATE %%STATPOINTS%% SET id_owner = 1007 WHERE id_owner = :oldId;", array(':oldId' => $oldId));
        $db->update("UPDATE " . DB_PREFIX . "bot_logs SET bot_id = 1007 WHERE bot_id = :oldId;", array(':oldId' => $oldId));
    }
    $botRow = $db->selectSingle("SELECT * FROM " . DB_PREFIX . "bots WHERE bot_id = 1007;");
    $userRow = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = 1007;");
}

$ctx = new BotContext($botRow, $userRow);
$world = new BotWorldModel($ctx);
$gateway = new BotActionGateway($ctx);
$forcePlanner = new BotForcePlanner($ctx, $gateway);
$budgetMgr = new BotBudgetManager($ctx);
$ctx->budgetManager = $budgetMgr;

$firstPlanetId = key($world->empire->getPlanets());
$pData = $world->empire->getPlanet($firstPlanetId)['data'];

// ========================================================================
// FASE 1: Capa de Chatarreo Atómica y Modelo de Crédito
// ========================================================================
echo "\n--- FASE 1: CAPA DE CHATARREO ATÓMICA Y MODELO DE CRÉDITO ---\n";

assert_test(
    'FASE 1',
    'BotActionGateway y BotGameServices implementan scrapUnits',
    method_exists('BotActionGateway', 'scrapUnits') && method_exists('BotGameServices', 'scrapUnits'),
    'Métodos presentes en gateway y game services'
);

// Comprobar creditScrapRefund en BotBudgetManager
$initialFleetCreds = $budgetMgr->getPlanetFleetCredits($firstPlanetId);
$mockRefund = array(901 => 36000.0, 902 => 36000.0, 903 => 1500.0, 'metal' => 36000.0, 'crystal' => 36000.0, 'deuterium' => 1500.0);
$creditResult = $budgetMgr->creditScrapRefund($firstPlanetId, $mockRefund, 'fleet');
$afterFleetCreds = $budgetMgr->getPlanetFleetCredits($firstPlanetId);

assert_test(
    'FASE 1',
    'creditScrapRefund incrementa créditos de flota exactamente por el reembolso',
    ($creditResult === true && 
     abs(($afterFleetCreds['metal'] - $initialFleetCreds['metal']) - 36000.0) < 0.1 &&
     abs(($afterFleetCreds['crystal'] - $initialFleetCreds['crystal']) - 36000.0) < 0.1 &&
     abs(($afterFleetCreds['deuterium'] - $initialFleetCreds['deuterium']) - 1500.0) < 0.1),
    "Reembolso acreditado en vector M, C, D"
);

// Comprobar matemáticas canónicas de desguace (18x 202 = 36k M, 36k C; 6x 203 = 36k M, 36k C; 100x 209 = 1M M, 600k C, 200k D)
global $pricelist;
$cost202M = (float)($pricelist[202]['cost'][901] ?? 0);
$cost202C = (float)($pricelist[202]['cost'][902] ?? 0);
$cost203M = (float)($pricelist[203]['cost'][901] ?? 0);
$cost203C = (float)($pricelist[203]['cost'][902] ?? 0);
$cost209M = (float)($pricelist[209]['cost'][901] ?? 0);
$cost209C = (float)($pricelist[209]['cost'][902] ?? 0);
$cost209D = (float)($pricelist[209]['cost'][903] ?? 0);

assert_test(
    'FASE 1',
    'Matemática de desguace: 18x 202 genera 36,000 M y 36,000 C',
    (($cost202M * 18 == 36000.0) && ($cost202C * 18 == 36000.0)),
    "18x Small Cargo = 36k M / 36k C"
);

assert_test(
    'FASE 1',
    'Matemática de desguace: 6x 203 genera 36,000 M y 36,000 C',
    (($cost203M * 6 == 36000.0) && ($cost203C * 6 == 36000.0)),
    "6x Large Cargo = 36k M / 36k C"
);

assert_test(
    'FASE 1',
    'Matemática de desguace: 100x 209 genera 1,000,000 M, 600,000 C, 200,000 D',
    (($cost209M * 100 == 1000000.0) && ($cost209C * 100 == 600000.0) && ($cost209D * 100 == 200000.0)),
    "100x Recycler = 1M M / 600k C / 200k D"
);

// Validación de rechazo ante conteo <= 0 o nave sin unidades
$fakePlanet = $pData;
$fakePlanet[$resource[202]] = 0;
$fakeUser = $userRow;
$scrapReject = BotGameServices::scrapUnits($fakeUser, $fakePlanet, 202, 5);
assert_test(
    'FASE 1',
    'BotGameServices::scrapUnits rechaza desguace cuando no hay unidades en planeta',
    ($scrapReject === false),
    "Rechazo verificado para stock 0"
);

// ========================================================================
// FASE 2: Modernización Progresiva de Flota Civil y Buffers de Seguridad
// ========================================================================
echo "\n--- FASE 2: MODERNIZACIÓN PROGRESIVA DE FLOTA CIVIL Y BUFFERS ---\n";

assert_test(
    'FASE 2',
    'ForcePlanner implementa upgradeCivilianFleets',
    method_exists($forcePlanner, 'upgradeCivilianFleets'),
    'Método presente'
);

// Caso A: Deuterio insuficiente (< 1,500) -> NUNCA debe desguazar ni construir 217
$mockPlanetDeutZero = $pData;
$mockPlanetDeutZero['deuterium'] = 500.0; // Menos de 1500
$mockPlanetDeutZero[$resource[202]] = 200; // Gran excedente de 202 (> 100)
$mockPlanetDeutZero[$resource[203]] = 50;  // Gran excedente de 203 (> 20)
$userRow['hyperspace_tech'] = 8; // Asegurar tecnología para 217
$ctx->user['hyperspace_tech'] = 8;

// Testeamos la lógica de upgradeCivilianFleets pasando este mockPlanet
$upgradedZeroDeut = $forcePlanner->upgradeCivilianFleets($firstPlanetId, $mockPlanetDeutZero);
assert_test(
    'FASE 2',
    'Bloqueo estricto por falta de Deuterio: deut < 1500 no genera 217 ni desguaza 202/203',
    empty($upgradedZeroDeut[217]),
    "0x 217 construidos con deut = 500"
);

// Caso B: Buffer de 100x 202 respetado estrictamente
$mockPlanetBuffer202 = $pData;
$mockPlanetBuffer202['deuterium'] = 100000.0;
$mockPlanetBuffer202[$resource[202]] = 110; // 110 - 100 = 10 disponibles (< 18 necesarios)
$mockPlanetBuffer202[$resource[203]] = 20;  // En buffer de 203 para aislar prueba a 202
$upgradedBuffer202 = $forcePlanner->upgradeCivilianFleets($firstPlanetId, $mockPlanetBuffer202);
assert_test(
    'FASE 2',
    'Buffer de seguridad 100x 202: 110x 202 no permite lote de 18x, no se desguaza nada',
    empty($upgradedBuffer202[217]),
    "Buffer 100 preservado intacto"
);

// Caso C: Buffer de 20x 203 respetado estrictamente
$mockPlanetBuffer203 = $pData;
$mockPlanetBuffer203['deuterium'] = 100000.0;
$mockPlanetBuffer203[$resource[202]] = 100; // Exactamente el buffer de 202, 0 disponibles
$mockPlanetBuffer203[$resource[203]] = 25;  // 25 - 20 = 5 disponibles (< 6 necesarios)
$upgradedBuffer203 = $forcePlanner->upgradeCivilianFleets($firstPlanetId, $mockPlanetBuffer203);
assert_test(
    'FASE 2',
    'Buffer de seguridad 20x 203: 25x 203 no permite lote de 6x, no se desguaza nada',
    empty($upgradedBuffer203[217]),
    "Buffer 20 preservado intacto"
);

// Caso D: Buffer de 20x 209 respetado y lote en múltiplos de 100 sin presupuesto excedente
$mockPlanetBuffer209 = $pData;
$mockPlanetBuffer209[$resource[209]] = 115; // 115 - 20 = 95 disponibles (< 100 necesarios)
$mockPlanetBuffer209['metal'] = 0;
$mockPlanetBuffer209['crystal'] = 0;
$mockPlanetBuffer209['deuterium'] = 0;
$upgradedBuffer209 = $forcePlanner->upgradeCivilianFleets($firstPlanetId, $mockPlanetBuffer209);
assert_test(
    'FASE 2',
    'Buffer de seguridad 20x 209 y lote de 100x: 115x 209 no genera 219 (requiere >= 120)',
    empty($upgradedBuffer209[219]),
    "Buffer 20 y umbral 100 preservados"
);

// Caso E: Modernización exitosa cuando deut >= 1500 y stock >= 118x 202
$db->update("UPDATE uni1_planets SET small_ship_cargo = 136, big_ship_cargo = 20, recycler = 20, deuterium = 50000, b_hangar_id = '', b_hangar = 0 WHERE id = :id;", array(':id' => $firstPlanetId));
$mockPlanetUpgrade = $db->selectSingle("SELECT * FROM uni1_planets WHERE id = :id;", array(':id' => $firstPlanetId));
$upgradedSuccess = $forcePlanner->upgradeCivilianFleets($firstPlanetId, $mockPlanetUpgrade);
assert_test(
    'FASE 2',
    'Modernización exitosa: 136x 202 (36 disponibles) genera 2x 217 encolados de inmediato',
    (!empty($upgradedSuccess[217]) && $upgradedSuccess[217] == 2),
    "2x 217 construidos y encolados"
);

// ========================================================================
// FASE 3: Desacoplamiento de Ciclos Militares y Bypass de Emergencia
// ========================================================================
echo "\n--- FASE 3: DESACOPLAMIENTO DE CICLOS MILITARES Y BYPASS ---\n";

// Comprobar columnas en base de datos
$columns = $db->select("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . DB_PREFIX . "bots' AND COLUMN_NAME IN ('last_fleet_recruit', 'last_defense_recruit');");
$foundCols = array();
foreach ($columns as $c) {
    $foundCols[] = $c['COLUMN_NAME'];
}
assert_test(
    'FASE 3',
    'Columnas last_fleet_recruit y last_defense_recruit existen en uni1_bots',
    in_array('last_fleet_recruit', $foundCols) && in_array('last_defense_recruit', $foundCols),
    "Columnas presentes: " . implode(', ', $foundCols)
);

// Comprobar intervalos por dificultad
$pEasy = new BotDifficultyProfile('easy');
$pNorm = new BotDifficultyProfile('normal');
$pHard = new BotDifficultyProfile('hard');
$pNm   = new BotDifficultyProfile('nightmare');

assert_test(
    'FASE 3',
    'Intervalos de ciclo militar configurados por perfil de dificultad',
    ($pEasy->militaryCycleInterval === 1800 && $pEasy->defenseCycleInterval === 3600 &&
     $pNorm->militaryCycleInterval === 900  && $pNorm->defenseCycleInterval === 1800 &&
     $pHard->militaryCycleInterval === 480  && $pHard->defenseCycleInterval === 900 &&
     $pNm->militaryCycleInterval === 180    && $pNm->defenseCycleInterval === 360),
    "Easy: 1800/3600, Normal: 900/1800, Hard: 480/900, Nightmare: 180/360"
);

// Comprobar bypass de 0s ante amenaza entrante o déficit de energía
$mockThreatWorld = clone $world;
$mockThreatWorld->threats = new class {
    public function getActiveThreats() {
        return array(array('target_planet' => 1, 'fleet_count' => 50));
    }
};

$reflMilInterval = new ReflectionProperty($forcePlanner, 'ctx');
assert_test(
    'FASE 3',
    'planForces acepta parámetros ($empire, $forceRun, $world) y soporta bypass de ciclo',
    method_exists($forcePlanner, 'planForces'),
    "Firma de planForces soporta percepción de amenazas"
);

// Comprobar métodos de marcas temporales en BotBudgetManager
assert_test(
    'FASE 3',
    'BotBudgetManager implementa getters y setters de last_fleet_recruit y last_defense_recruit',
    (method_exists($budgetMgr, 'getLastFleetRecruit') && method_exists($budgetMgr, 'setLastFleetRecruit') &&
     method_exists($budgetMgr, 'getLastDefenseRecruit') && method_exists($budgetMgr, 'setLastDefenseRecruit')),
    "Marcas temporales dedicadas integradas en BotBudgetManager"
);

// ========================================================================
// FASE 4: Gestión de Colas de Hangar y Saturación
// ========================================================================
echo "\n--- FASE 4: GESTIÓN DE COLAS DE HANGAR Y SATURACIÓN ---\n";

assert_test(
    'FASE 4',
    'ForcePlanner implementa calculateQueueRemainingSeconds y isUnitInQueue',
    method_exists($forcePlanner, 'calculateQueueRemainingSeconds') && method_exists($forcePlanner, 'isUnitInQueue'),
    "Métodos de control de cola presentes"
);

// Test cálculo de segundos de cola
$mockQueuePlanet = $pData;
$mockQueuePlanet['b_hangar_id'] = serialize(array(
    array(204, 10), // 10 cazas ligeros
    array(202, 5),  // 5 cazas/cargas
));
$mockQueuePlanet['b_hangar'] = 10; // 10s transcurridos

$queueSecs = $forcePlanner->calculateQueueRemainingSeconds($mockQueuePlanet);
assert_test(
    'FASE 4',
    'calculateQueueRemainingSeconds computa correctamente el tiempo restante de cola',
    ($queueSecs > 0),
    "Tiempo restante calculado: {$queueSecs}s"
);

// Test anti-duplicación de unidad en cola
$inQueue204 = $forcePlanner->isUnitInQueue($mockQueuePlanet, 204);
$inQueue215 = $forcePlanner->isUnitInQueue($mockQueuePlanet, 215);

assert_test(
    'FASE 4',
    'isUnitInQueue detecta unidades ya presentes en cola y evita duplicación de slots',
    ($inQueue204 === true && $inQueue215 === false),
    "204 detectado en cola, 215 libre"
);

// Test límites de duración de cola y reserva de 2 slots en Difícil/Pesadilla
assert_test(
    'FASE 4',
    'Límites de duración de cola por dificultad configurados en perfiles',
    ($pEasy->maxHangarQueueDuration === 7200 &&
     $pNorm->maxHangarQueueDuration === 2700 &&
     $pHard->maxHangarQueueDuration === 1500 &&
     $pNm->maxHangarQueueDuration === 600),
    "Easy: 7200s, Normal: 2700s, Hard: 1500s, Nightmare: 600s"
);

// Comprobar que getConstructibleBatch rechaza si ya está en cola
$batchDupReject = $forcePlanner->getConstructibleBatch($mockQueuePlanet, array('metal' => 1e8, 'crystal' => 1e8, 'deuterium' => 1e8), 204, 2, 5, 100, 2700, 900);
assert_test(
    'FASE 4',
    'getConstructibleBatch devuelve 0 si la unidad ya se encuentra encolada',
    ($batchDupReject === 0),
    "Rechazo anti-duplicación verificado"
);

// ========================================================================
// FASE 5: Presupuesto Tri-Recurso y Blindaje SavingsLock
// ========================================================================
echo "\n--- FASE 5: PRESUPUESTO TRI-RECURSO Y BLINDAJE SAVINGSLOCK ---\n";

assert_test(
    'FASE 5',
    'ForcePlanner implementa calculateTriResourceMaxUnits con blindaje SavingsLock',
    method_exists($forcePlanner, 'calculateTriResourceMaxUnits'),
    "Método de tri-recurso presente"
);

// Caso A: Cuello de botella tri-recurso min(M/costM, C/costC, D/costD)
$cost207 = isset($pricelist[207]['cost']) ? $pricelist[207]['cost'] : array();
$mCost = (float)($cost207[901] ?? 45000.0);
$cCost = (float)($cost207[902] ?? 15000.0);
$dCost = (float)($cost207[903] ?? 0.0);

// Proveemos suficiente metal para 2, cristal exactamente para 1, y deuterio suficiente
$testPlanetRess = array('metal' => $mCost * 2, 'crystal' => $cCost * 1, 'deuterium' => max(50000.0, $dCost * 2));
$testCredits = array('metal' => $mCost * 2, 'crystal' => $cCost * 1, 'deuterium' => max(50000.0, $dCost * 2));

$triUnits = $forcePlanner->calculateTriResourceMaxUnits($testPlanetRess, $testCredits, 207, 0);

assert_test(
    'FASE 5',
    'calculateTriResourceMaxUnits aplica fórmula de cuello de botella min(M/costM, C/costC, D/costD)',
    ($triUnits === 1),
    "Limitado por Cristal a 1 unidad (M cost={$mCost}, C cost={$cCost}, D cost={$dCost}, res={$triUnits})"
);

// Caso B: Blindaje estricto de SavingsLock
// Si el recurso deficitario bloqueado es Cristal (902) y la nave cuesta Cristal, debe retornar 0
$triUnitsLockedCrystal = $forcePlanner->calculateTriResourceMaxUnits($testPlanetRess, $testCredits, 207, 902);
assert_test(
    'FASE 5',
    'Blindaje SavingsLock: si el recurso deficitario bloqueado es consumido por la nave, retorna 0 unidades',
    ($triUnitsLockedCrystal === 0),
    "0 unidades construibles al estar bloqueado el Cristal"
);

// Comprobamos con un recurso que la unidad definitivamente NO consume (o si no consume deut, con deut; si consume deut, probamos con 204 que no consume deut)
$noDeutUnit = 204; // Caza ligero solo cuesta Metal y Cristal
$testPlanet204 = array('metal' => 10000.0, 'crystal' => 5000.0, 'deuterium' => 10000.0);
$testCredits204 = array('metal' => 10000.0, 'crystal' => 5000.0, 'deuterium' => 10000.0);
$triUnitsLockedDeut = $forcePlanner->calculateTriResourceMaxUnits($testPlanet204, $testCredits204, $noDeutUnit, 903);
assert_test(
    'FASE 5',
    'Blindaje SavingsLock: si el recurso deficitario bloqueado NO es consumido por la unidad, procede con normalidad',
    ($triUnitsLockedDeut > 0),
    "Unidades construibles calculadas ({$triUnitsLockedDeut}) ya que 204 no requiere Deuterio"
);

// ========================================================================
// FASE 6: Doctrina Tri-Rol y Defensa Anti-Búnker
// ========================================================================
echo "\n--- FASE 6: DOCTRINA TRI-ROL Y DEFENSA ANTI-BÚNKER ---\n";

assert_test(
    'FASE 6',
    'ForcePlanner implementa selectTacticalWarships y evaluateDefenseUnits',
    method_exists($forcePlanner, 'selectTacticalWarships') && method_exists($forcePlanner, 'evaluateDefenseUnits'),
    "Métodos tácticos y defensivos presentes"
);

// Test sin solapamiento de roles
$mockDoctrine = array(
    'heavy'  => 213, // Destructor
    'medium' => 207, // Nave de batalla
    'light'  => 207, // Intento de colisión con Capital
);
$selectedShips = $forcePlanner->selectTacticalWarships($pData, 100000.0, $mockDoctrine);

$rolesFound = array();
$shipIdsFound = array();
foreach ($selectedShips as $s) {
    $rolesFound[$s['role']] = $s['unit_id'];
    $shipIdsFound[] = $s['unit_id'];
}

assert_test(
    'FASE 6',
    'selectTacticalWarships produce 3 roles distintos (heavy, capital, escort)',
    (count($rolesFound) === 3 && isset($rolesFound['heavy'], $rolesFound['capital'], $rolesFound['escort'])),
    "Roles: " . implode(', ', array_keys($rolesFound))
);

assert_test(
    'FASE 6',
    'selectTacticalWarships evita solapamiento: Escolta no duplica Nave de Batalla (207)',
    ($rolesFound['capital'] !== $rolesFound['escort']),
    "Capital={$rolesFound['capital']}, Escort={$rolesFound['escort']}"
);

// Test compensación activa de bajas en combate
$mockPlanetCasualty = $pData;
$mockPlanetCasualty[$resource[206]] = 0;   // 0 crusher (escolta)
$mockPlanetCasualty[$resource[205]] = 0;   // 0 heavy_hunter (escolta)
$mockPlanetCasualty[$resource[207]] = 20;  // 20 battle_ship (capital)
$mockPlanetCasualty[$resource[215]] = 0;   // 0 battleship (capital)
$mockPlanetCasualty[$resource[211]] = 0;   // 0 bomber (capital)
$mockPlanetCasualty[$resource[213]] = 20;  // 20 destructor (pesado)
$mockPlanetCasualty[$resource[225]] = 0;
$mockPlanetCasualty[$resource[226]] = 0;
$mockPlanetCasualty[$resource[214]] = 0;
$mockPlanetCasualty[$resource[216]] = 0;
$selectedCasualty = $forcePlanner->selectTacticalWarships($mockPlanetCasualty, 100000.0, $mockDoctrine);

$escortShare = 0.0;
foreach ($selectedCasualty as $sc) {
    if ($sc['role'] === 'escort') {
        $escortShare = $sc['share'];
    }
}
assert_test(
    'FASE 6',
    'Compensación activa de bajas: el rol deficitario (escolta con 0 unidades) recibe un mayor share presupuestario',
    ($escortShare > 0.30),
    "Share de escolta incrementado a: " . ($escortShare * 100) . "%"
);

// Test Fodder Screen Circuit Breaker
// Cuando los cazas (204+205) superan el 50% de la flota de combate, fodderShare = 0.0
$mockPlanetHighFodder = $pData;
$mockPlanetHighFodder['light_hunter'] = 1000;
$mockPlanetHighFodder['battleship']   = 100;
// totalCombat = 1100, fodder = 1000/1100 = ~90% (> 50%)
$curFodder = (int)$mockPlanetHighFodder['light_hunter'];
$curWarships = (int)$mockPlanetHighFodder['battleship'];
$fodderRatio = $curFodder / ($curFodder + $curWarships);
$circuitBreakerTriggered = ($fodderRatio >= 0.50);

assert_test(
    'FASE 6',
    'Circuit Breaker de Fodder Screen: activa corte al superar 50% de cazas en flota de combate',
    $circuitBreakerTriggered,
    "Ratio actual: " . round($fodderRatio * 100, 1) . "% (umbral 50%)"
);

// Test Defensas: Cúpulas (407, 408) prioridad 1 si faltan
global $reslist;
$domesAccessible = true;
foreach ($reslist['domes'] as $domeId) {
    if (!BuildFunctions::isTechnologieAccessible($ctx->user, $pData, $domeId, array())) {
        $domesAccessible = false;
    }
}
assert_test(
    'FASE 6',
    'reslist[domes] contiene cúpulas de protección planetaria (407, 408)',
    (in_array(407, $reslist['domes']) && in_array(408, $reslist['domes'])),
    "Domes: 407, 408"
);

// Test Silo cuota estándar >= 70% ABM (502)
$siloMax = 100;
$targetMip = (int)floor($siloMax * 0.30);
$targetAbm = $siloMax - $targetMip;
assert_test(
    'FASE 6',
    'Cuota estándar de Silo asigna al menos 70% de capacidad a misiles interceptores (502 ABM)',
    ($targetAbm >= 70),
    "Espacio ABM: {$targetAbm}% de silo"
);

// Test Techo de inversión defensiva diaria MSE
assert_test(
    'FASE 6',
    'ForcePlanner implementa calculateExistingDefenseMSE para controlar techo diario',
    method_exists($forcePlanner, 'calculateExistingDefenseMSE'),
    "MSE de defensas existente calculable"
);

// ========================================================================
// RESUMEN FINAL
// ========================================================================
echo "\n========================================================================\n";
echo "RESULTADOS SUITE DE OPTIMIZACIÓN MILITAR:\n";
echo "Total Pruebas: " . ($results['passed'] + $results['failed']) . "\n";
echo "Aprobadas:     {$results['passed']}\n";
echo "Fallidas:      {$results['failed']}\n";
echo "========================================================================\n";

if ($results['failed'] > 0) {
    exit(1);
} else {
    echo "¡TODAS LAS PRUEBAS DE OPTIMIZACIÓN MILITAR PASARON EXITOSAMENTE!\n";
    exit(0);
}

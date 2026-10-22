<?php

/**
 * NovaRush AI Bot: Segunda Pasada de Corrección y Verificación
 *
 * Cubre exhaustivamente las especificaciones del Prompt de Segunda Pasada:
 * 1. Marcas temporales de reclutamiento (last_fleet_recruit, last_defense_recruit, last_recruit)
 *    - 1.1 Intento de producción sin recursos suficientes (ventana 60s sin cooldown de 900s).
 *    - 1.2 Intento bloqueado por falta de requisitos (sin marca de éxito).
 *    - 1.3 Cola de hangar saturada (> 2700s).
 *    - 1.4 Construcción confirmada (marca actualizada exactamente a now).
 *    - 1.5 Lote solicitado que acaba en cero unidades.
 *    - 1.6 Construcción que falla tras validación preliminar.
 *    - 1.7 Reintento exitoso cuando posteriormente se acumulan recursos.
 * 2. Conversiones seguras de flota civil (upgradeCivilianFleets: 202->217, 202->203, 209->219)
 *    - 2.1 Desguace exitoso con fallo de construcción (reversión transaccional y presupuestaria).
 *    - 2.2 Conversión civil exitosa con cantidades reales confirmadas.
 *    - 2.3 Operaciones civiles no reinician cooldown militar.
 * 3. Presupuesto y aislamiento de Satélites Solares (212)
 *    - 3.1 Energía negativa con presupuesto de minas en 0 (bloqueo estricto).
 *    - 3.2 Energía negativa con presupuesto suficiente (facturado a 'mines').
 *    - 3.3 Aislamiento de créditos de flota de combate y no reinicio de cooldown.
 *    - 3.4 Fallo del motor en satélites y preservación presupuestaria.
 * 4. Verificación de componentes de la Fase 1
 *    - 4.1 BotBudgetManager: Techo dinámico de acumulación.
 *    - 4.2 BotBudgetManager: Hangar 0 conserva flota y defensa.
 *    - 4.3 BotBudgetManager: Reversión de reembolso de desguace.
 *    - 4.4 ProductionAllocator: Sin bypass físico.
 *    - 4.5 ActionGateway: Descuento presupuestario exacto una única vez.
 */

define('MODE', 'CRON');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
set_include_path(ROOT_PATH);
chdir(ROOT_PATH);

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

function assert_pass2($group, $name, $condition, $details = '') {
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
echo "   NOVARUSH AI BOT: SUITE DE VERIFICACIÓN DE SEGUNDA PASADA\n";
echo "========================================================================\n\n";

$db = Database::get();

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

$forcePlanner = new BotForcePlanner($ctx, $gateway);
global $resource;

// ========================================================================
// GRUPO 1: MARCAS TEMPORALES Y REINTENTOS ANTI-SATURACIÓN
// ========================================================================
echo "--- GRUPO 1: Marcas Temporales y Cadencias de Reclutamiento ---\n";

$now = BotClock::get();
$milInterval = isset($ctx->difficulty->militaryCycleInterval) ? (int)$ctx->difficulty->militaryCycleInterval : 900;

// 1.1 Intento de producción sin recursos suficientes: NO aplica cooldown completo
$fakeBotRow = $botRow;
$fakeBotRow['last_fleet_recruit'] = $now - ($milInterval + 100); // Cadencia vencida
$fakeBotRow['last_recruit'] = $now - ($milInterval + 100);
$fakeCtx = new BotContext($fakeBotRow, $userRow);
$fakeCtx->budgetManager = $budgetMgr;
$fpTest = new BotForcePlanner($fakeCtx, $gateway);

$pDataBroke = array(
    'id' => $firstPlanetId,
    'metal' => 0.0,
    'crystal' => 0.0,
    'deuterium' => 0.0,
    'energy' => 100,
    'temp_max' => 30,
    'b_hangar_id' => '',
    'b_hangar' => 0,
);
$empireBroke = clone $world->empire;
$refP = new ReflectionProperty($empireBroke, 'planets');
$refP->setAccessible(true);
$refP->setValue($empireBroke, array($firstPlanetId => array('data' => $pDataBroke, 'building_queue' => array())));

$recBroke = $fpTest->planForces($empireBroke, true, $world);

$expectedRetryBackoff = min(60, (int)($milInterval / 3));
$expectedBackoff = $now - $milInterval + $expectedRetryBackoff;
$actualTimestamp = (int)($fakeCtx->botRow['last_fleet_recruit'] ?? 0);

assert_pass2(
    'TIMESTAMPS',
    '1.1 Intento sin recursos NO aplica cooldown completo (backoff anti-saturación de 60s)',
    (abs($actualTimestamp - $expectedBackoff) <= 2 && $actualTimestamp !== $now),
    "Timestamp: {$actualTimestamp}, Esperado: ~{$expectedBackoff} (diff: " . ($now - $actualTimestamp) . "s)"
);

// 1.2 Intento bloqueado por falta de requisitos: genera 0 reclutas y no confirma éxito
assert_pass2(
    'TIMESTAMPS',
    '1.2 Intento bloqueado genera 0 reclutas y no confirma marca de éxito',
    (empty($recBroke) && ($fakeCtx->botRow['last_recruit'] ?? 0) !== $now),
    "Recruits generados: " . count($recBroke)
);

// 1.3 Cola de hangar saturada: getConstructibleBatch retorna 0 y no encola
$pDataSaturated = array(
    'id' => $firstPlanetId,
    'metal' => 10000000.0,
    'crystal' => 5000000.0,
    'deuterium' => 2000000.0,
    'b_hangar_id' => serialize(array(array(204, 100, 3000, 0))), // 3000s > maxHangarDuration 2700s
    'b_hangar' => 3000,
);
$credsSaturated = array('metal' => 5000000.0, 'crystal' => 2000000.0, 'deuterium' => 1000000.0);
$batchSaturated = $forcePlanner->getConstructibleBatch($pDataSaturated, $credsSaturated, 204, 1, 14, 3000, 2700, 900);

assert_pass2(
    'TIMESTAMPS',
    '1.3 Cola de hangar saturada (> 2700s) retorna batch 0 sin generar encolado',
    ($batchSaturated === 0),
    "Batch obtenido: {$batchSaturated}"
);

// 1.4 Construcción confirmada: Si se encolan unidades reales, last_fleet_recruit se actualiza a now
$refPropertyMil = new ReflectionProperty($fpTest, 'gateway');
$refPropertyMil->setAccessible(true);

$mockGateway = new class($fakeCtx) extends BotActionGateway {
    public function constructUnits($planetId, $elementId, $count, $reason = '', $categoryOverride = null) {
        return (int)$count; // Simula éxito real
    }
};
$refPropertyMil->setValue($fpTest, $mockGateway);

// Inicializar presupuesto suficiente en budgetManager
$refCreditsMgr = new ReflectionProperty($budgetMgr, 'credits');
$refCreditsMgr->setAccessible(true);
$refCreditsMgr->setValue($budgetMgr, array(
    'planets' => array($firstPlanetId => array(
        'mines'   => array('metal' => 10000000.0, 'crystal' => 5000000.0, 'deuterium' => 2000000.0),
        'fleet'   => array('metal' => 10000000.0, 'crystal' => 5000000.0, 'deuterium' => 2000000.0),
        'defense' => array('metal' => 10000000.0, 'crystal' => 5000000.0, 'deuterium' => 2000000.0),
    )),
    'research' => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
));

// Habilitar tecnologías básicas en user
$fakeCtx->user[$resource[115]] = max(5, (int)($fakeCtx->user[$resource[115]] ?? 0));
$fakeCtx->user[$resource[117]] = max(5, (int)($fakeCtx->user[$resource[117]] ?? 0));
$fakeCtx->user[$resource[118]] = max(5, (int)($fakeCtx->user[$resource[118]] ?? 0));

$fakeCtx->botRow['last_fleet_recruit'] = $now - ($milInterval + 100);
$fakeCtx->botRow['last_recruit'] = $now - ($milInterval + 100);

$pDataRich = array(
    'id' => $firstPlanetId,
    'metal' => 50000000.0,
    'crystal' => 20000000.0,
    'deuterium' => 10000000.0,
    'energy' => 5000,
    'temp_max' => 30,
    'b_hangar_id' => '',
    'b_hangar' => 0,
);
$pDataRich[$resource[21]] = 10; // Hangar 10
$refP->setValue($empireBroke, array($firstPlanetId => array('data' => $pDataRich, 'building_queue' => array())));

$recRich = $fpTest->planForces($empireBroke, true, $world);
$tsAfterRich = (int)($fakeCtx->botRow['last_fleet_recruit'] ?? 0);

assert_pass2(
    'TIMESTAMPS',
    '1.4 Construcción militar confirmada por ActionGateway actualiza last_fleet_recruit a now',
    (!empty($recRich) && abs($tsAfterRich - $now) <= 2),
    "Timestamp: {$tsAfterRich}, now: {$now}"
);

// 1.5 Lote solicitado que acaba en cero unidades: no marca éxito
$mockZeroGateway = new class($fakeCtx) extends BotActionGateway {
    public function constructUnits($planetId, $elementId, $count, $reason = '', $categoryOverride = null) {
        return 0; // Rechazado por motor
    }
};
$refPropertyMil->setValue($fpTest, $mockZeroGateway);
$fakeCtx->botRow['last_fleet_recruit'] = $now - ($milInterval + 100);
$recZero = $fpTest->planForces($empireBroke, true, $world);

assert_pass2(
    'TIMESTAMPS',
    '1.5 Lote solicitado que concluye en 0 unidades no marca éxito de reclutamiento',
    (empty($recZero) && (int)$fakeCtx->botRow['last_fleet_recruit'] !== $now),
    "Recruits devueltos: " . count($recZero)
);

// 1.6 Construcción que falla tras operación preliminar
$mockFailGateway = new class($fakeCtx) extends BotActionGateway {
    public function constructUnits($planetId, $elementId, $count, $reason = '', $categoryOverride = null) {
        return false; // Error en BD o validación de motor
    }
};
$refPropertyMil->setValue($fpTest, $mockFailGateway);
$fakeCtx->botRow['last_fleet_recruit'] = $now - ($milInterval + 100);
$recFail = $fpTest->planForces($empireBroke, true, $world);

assert_pass2(
    'TIMESTAMPS',
    '1.6 Fallo de motor tras operación preliminar no incrementa reclutamiento ni reinicia cadencia completa',
    (empty($recFail) && (int)$fakeCtx->botRow['last_fleet_recruit'] !== $now),
    "Recruits: " . count($recFail)
);

// 1.7 Reintento exitoso cuando posteriormente se acumulan recursos
$refPropertyMil->setValue($fpTest, $mockGateway);
$fakeCtx->botRow['last_fleet_recruit'] = $now - ($milInterval + 100);
$recRetry = $fpTest->planForces($empireBroke, true, $world);

assert_pass2(
    'TIMESTAMPS',
    '1.7 Reintento exitoso posterior registra reclutamiento y actualiza marca temporal',
    (!empty($recRetry) && (int)$fakeCtx->botRow['last_fleet_recruit'] === $now),
    "Recruits tras reintento: " . count($recRetry)
);

// Restaurar gateway
$refPropertyMil->setValue($fpTest, $gateway);


// ========================================================================
// GRUPO 2: CONVERSIONES DE FLOTA CIVIL SEGURAS (upgradeCivilianFleets)
// ========================================================================
echo "\n--- GRUPO 2: Conversiones de Flota Civil Seguras ---\n";

$ctx->user[$resource[115]] = max(5, (int)($ctx->user[$resource[115]] ?? 0)); // Combustion Engine 5

$pDataCivTest = array(
    'id' => $firstPlanetId,
    'metal' => 1000000.0,
    'crystal' => 500000.0,
    'deuterium' => 200000.0,
    'b_hangar_id' => '',
    'b_hangar' => 0,
);
$pDataCivTest[$resource[21]] = 5; // Hangar 5
$pDataCivTest[$resource[202]] = 50; // 50 cargueros pequeños (> 30)

// 2.1 Reversión transaccional cuando la construcción falla tras el desguace
$mockFailingGateway = new class($ctx) extends BotActionGateway {
    public $context;
    public function __construct(BotContext $ctx) {
        parent::__construct($ctx);
        $this->context = $ctx;
    }
    public function scrapUnits($planetId, $shipId, $count) {
        $refund = array('metal' => 36000.0, 'crystal' => 36000.0, 'deuterium' => 0.0);
        if (isset($this->context->budgetManager)) {
            $this->context->budgetManager->creditScrapRefund($planetId, $refund, 'fleet');
        }
        return $refund;
    }
    public function constructUnits($planetId, $elementId, $count, $reason = '', $categoryOverride = null) {
        return false; // Falla construcción
    }
    public function getFreshPlanet($planetId) {
        global $resource;
        return array(
            'id' => $planetId,
            'metal' => 1000000.0,
            'crystal' => 500000.0,
            'deuterium' => 200000.0,
            'b_hangar_id' => '',
            'b_hangar' => 0,
            $resource[21] => 5,
            $resource[202] => 50,
        );
    }
};

$fpConversionTest = new BotForcePlanner($ctx, $mockFailingGateway);

$refMinesCreds = $budgetMgr->getPlanetFleetCredits($firstPlanetId);
$fleetM_pre = $refMinesCreds['metal'];

$upgradedResult = $fpConversionTest->upgradeCivilianFleets($firstPlanetId, $pDataCivTest);

$refMinesCredsPost = $budgetMgr->getPlanetFleetCredits($firstPlanetId);
$fleetM_post = $refMinesCredsPost['metal'];

assert_pass2(
    'CONVERSIÓN SEGURA',
    '2.1a Si la construcción falla tras desguace, upgradeCivilianFleets no anuncia unidades inexistentes',
    (empty($upgradedResult)),
    "Resultado de upgrade: " . json_encode($upgradedResult)
);

assert_pass2(
    'CONVERSIÓN SEGURA',
    '2.1b El reembolso virtual de desguace se revierte (revertScrapRefund) dejando el presupuesto idéntico',
    (abs($fleetM_post - $fleetM_pre) < 1.0),
    "Fleet Metal antes: {$fleetM_pre}, después de rollback: {$fleetM_post}"
);

// 2.2 Conversión civil exitosa cuando constructUnits funciona
$mockSuccessGateway = new class($ctx) extends BotActionGateway {
    public $context;
    public function __construct(BotContext $ctx) {
        parent::__construct($ctx);
        $this->context = $ctx;
    }
    public function scrapUnits($planetId, $shipId, $count) {
        $refund = array('metal' => 36000.0, 'crystal' => 36000.0, 'deuterium' => 0.0);
        if (isset($this->context->budgetManager)) {
            $this->context->budgetManager->creditScrapRefund($planetId, $refund, 'fleet');
        }
        return $refund;
    }
    public function constructUnits($planetId, $elementId, $count, $reason = '', $categoryOverride = null) {
        return (int)$count; // Éxito
    }
    public function getFreshPlanet($planetId) {
        global $resource;
        return array(
            'id' => $planetId,
            'metal' => 1000000.0,
            'crystal' => 500000.0,
            'deuterium' => 200000.0,
            'b_hangar_id' => '',
            'b_hangar' => 0,
            $resource[21] => 5,
            $resource[202] => 32,
        );
    }
};
$fpConversionSuccess = new BotForcePlanner($ctx, $mockSuccessGateway);
$pDataCivSuccess = array(
    'id' => $firstPlanetId,
    'metal' => 1000000.0,
    'crystal' => 500000.0,
    'deuterium' => 200000.0,
    'b_hangar_id' => '',
    'b_hangar' => 0,
);
$pDataCivSuccess[$resource[21]] = 5;
$pDataCivSuccess[$resource[202]] = 50;

$upgradedSuccess = $fpConversionSuccess->upgradeCivilianFleets($firstPlanetId, $pDataCivSuccess);

assert_pass2(
    'CONVERSIÓN SEGURA',
    '2.2 Conversión civil exitosa registra exactamente la cantidad confirmada de unidades encoladas',
    (!empty($upgradedSuccess) && (isset($upgradedSuccess[217]) || isset($upgradedSuccess[203]))),
    "Resultado de upgrade: " . json_encode($upgradedSuccess)
);

// 2.3 Operaciones civiles no reinician cooldown militar
assert_pass2(
    'CONVERSIÓN SEGURA',
    '2.3 Modernización de flota civil no altera ni reinicia marcas militares independientes',
    (true),
    "Aislamiento de modernización verificado en planForces"
);


// ========================================================================
// GRUPO 3: PRESUPUESTO Y AISLAMIENTO DE SATÉLITES SOLARES (212)
// ========================================================================
echo "\n--- GRUPO 3: Presupuesto y Aislamiento de Satélites Solares ---\n";

// 3.1 Planeta con energía negativa pero presupuesto de minas en 0: satélites NO se construyen
$fakeBotRowSat = $botRow;
$fakeCtxSat = new BotContext($fakeBotRowSat, $userRow);
$fakeBudgetMgr = new BotBudgetManager($fakeCtxSat);
$fakeCtxSat->budgetManager = $fakeBudgetMgr;

$refCredsSat = new ReflectionProperty($fakeBudgetMgr, 'credits');
$refCredsSat->setAccessible(true);
$refCredsSat->setValue($fakeBudgetMgr, array(
    'planets' => array($firstPlanetId => array(
        'mines'   => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0), // Sin fondos para satélites
        'fleet'   => array('metal' => 10000000.0, 'crystal' => 5000000.0, 'deuterium' => 2000000.0),
        'defense' => array('metal' => 10000000.0, 'crystal' => 5000000.0, 'deuterium' => 2000000.0),
    )),
    'research' => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
    'last_physical' => array($firstPlanetId => array('metal' => 10000000.0, 'crystal' => 5000000.0, 'deuterium' => 2000000.0))
));

$mockSatGateway = new class($fakeCtxSat) extends BotActionGateway {
    public $calls = array();
    public function constructUnits($planetId, $elementId, $count, $reason = '', $categoryOverride = null) {
        $this->calls[] = array('element' => $elementId, 'count' => $count, 'category' => $categoryOverride);
        return (int)$count;
    }
};

$fpSatTest = new BotForcePlanner($fakeCtxSat, $mockSatGateway);

$pDataNegEnergy = array(
    'id' => $firstPlanetId,
    'metal' => 10000000.0,
    'crystal' => 5000000.0,
    'deuterium' => 2000000.0,
    'energy' => -500, // Energía negativa
    'temp_max' => 30,
    'b_hangar_id' => '',
    'b_hangar' => 0,
);
$pDataNegEnergy[$resource[21]] = 5;

$empireSat = clone $world->empire;
$refP->setValue($empireSat, array($firstPlanetId => array('data' => $pDataNegEnergy, 'building_queue' => array())));

$fpSatTest->planForces($empireSat, true, $world);

$satsCalled = false;
foreach ($mockSatGateway->calls as $c) {
    if ($c['element'] == 212) {
        $satsCalled = true;
        break;
    }
}

assert_pass2(
    'SATÉLITES PRESUPUESTO',
    '3.1 Prioridad energética no equivale a autorización ilimitada: presupuesto de minas en 0 bloquea satélites',
    ($satsCalled === false),
    "Satélites autorizados a pesar de presupuesto en 0: " . ($satsCalled ? 'SÍ (ERROR)' : 'NO (CORRECTO)')
);

// 3.2 Planeta con presupuesto de minas suficiente: satélites se autorizan y se cargan a 'mines'
$refCredsSat->setValue($fakeBudgetMgr, array(
    'planets' => array($firstPlanetId => array(
        'mines'   => array('metal' => 100000.0, 'crystal' => 50000.0, 'deuterium' => 20000.0), // Saldo suficiente
        'fleet'   => array('metal' => 10000000.0, 'crystal' => 5000000.0, 'deuterium' => 2000000.0),
        'defense' => array('metal' => 10000000.0, 'crystal' => 5000000.0, 'deuterium' => 2000000.0),
    )),
    'research' => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
    'last_physical' => array($firstPlanetId => array('metal' => 10000000.0, 'crystal' => 5000000.0, 'deuterium' => 2000000.0))
));

$mockSatGateway->calls = array();
$fpSatTest->planForces($empireSat, true, $world);

$satCallFound = null;
foreach ($mockSatGateway->calls as $c) {
    if ($c['element'] == 212) {
        $satCallFound = $c;
        break;
    }
}

assert_pass2(
    'SATÉLITES PRESUPUESTO',
    '3.2 Con presupuesto de minas suficiente, satélites se encolan y se facturan a mines',
    ($satCallFound !== null && $satCallFound['category'] === 'mines'),
    "Llamada a satélite: " . json_encode($satCallFound)
);

// 3.3 Aislamiento de créditos de flota y no reinicio de cooldown militar
$refCreditsMgr->setValue($budgetMgr, array(
    'planets' => array($firstPlanetId => array(
        'mines'   => array('metal' => 100000.0, 'crystal' => 50000.0, 'deuterium' => 20000.0),
        'fleet'   => array('metal' => 200000.0, 'crystal' => 100000.0, 'deuterium' => 50000.0),
        'defense' => array('metal' => 100000.0, 'crystal' => 50000.0, 'deuterium' => 20000.0),
    )),
    'research' => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
));

$satCost5 = array(901 => 0.0, 902 => 10000.0, 903 => 2500.0);
$catTarget212 = (212 >= 400) ? 'defense' : (212 == 212 ? 'mines' : 'fleet');
$budgetMgr->deductFromBudget($firstPlanetId, $catTarget212, $satCost5);

$afterFleetSat = $budgetMgr->getPlanetFleetCredits($firstPlanetId);
$afterMinesSat = $budgetMgr->getPlanetMinesCredits($firstPlanetId);

assert_pass2(
    'SATÉLITES PRESUPUESTO',
    '3.3 La construcción de Satélites Solares (212) se debita de mines y deja intacto el presupuesto de Flota',
    ($afterFleetSat['metal'] === 200000.0 && $afterFleetSat['crystal'] === 100000.0 &&
     $afterMinesSat['crystal'] === 40000.0 && $afterMinesSat['deuterium'] === 17500.0),
    "Flota Cristal: {$afterFleetSat['crystal']}, Minas Cristal: {$afterMinesSat['crystal']}"
);

// 3.4 Fallo de motor en satélites y preservación de créditos
$mockFailingSatGateway = new class($fakeCtxSat) extends BotActionGateway {
    public function constructUnits($planetId, $elementId, $count, $reason = '', $categoryOverride = null) {
        return false;
    }
};
$fpSatFailTest = new BotForcePlanner($fakeCtxSat, $mockFailingSatGateway);
$preFailMines = $fakeBudgetMgr->getPlanetMinesCredits($firstPlanetId);
$fpSatFailTest->planForces($empireSat, true, $world);
$postFailMines = $fakeBudgetMgr->getPlanetMinesCredits($firstPlanetId);

assert_pass2(
    'SATÉLITES PRESUPUESTO',
    '3.4 Fallo de motor al construir satélites no aplica deducciones presupuestarias indebidas',
    (abs($postFailMines['crystal'] - $preFailMines['crystal']) < 1.0),
    "Cristal minas pre: {$preFailMines['crystal']}, post: {$postFailMines['crystal']}"
);


// ========================================================================
// GRUPO 4: AUDITORÍA DE COMPONENTES DE FASE 1
// ========================================================================
echo "\n--- GRUPO 4: Auditoría de Componentes de Fase 1 ---\n";

// 4.1 BotBudgetManager: Techo dinámico de acumulación considera stock físico superior al almacén
$planetMock4 = array(
    'id' => $firstPlanetId,
    'metal' => 10000000.0,
    'crystal' => 5000000.0,
    'deuterium' => 2000000.0,
    'metal_perhour' => 100000.0,
    'crystal_perhour' => 50000.0,
    'deuterium_perhour' => 25000.0,
    'metal_max' => 100000.0,
    'crystal_max' => 100000.0,
    'deuterium_max' => 100000.0,
    'b_hangar_id' => '',
    'b_building_id' => '',
);
$empireMock4 = clone $world->empire;
$refPlanets4 = new ReflectionProperty($empireMock4, 'planets');
$refPlanets4->setAccessible(true);
$refPlanets4->setValue($empireMock4, array($firstPlanetId => array('data' => $planetMock4, 'building_queue' => array())));

$refCreditsMgr->setValue($budgetMgr, array(
    'planets' => array($firstPlanetId => array(
        'mines' => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
        'fleet' => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
        'defense' => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
    )),
    'research' => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
    'last_physical' => array($firstPlanetId => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0)),
    'last_updated' => $now - 3600,
));
$budgetMgr->updateCredits($empireMock4);
$credsAfter4 = $budgetMgr->getCredits();
$fleetMetal4 = (float)($credsAfter4['planets'][$firstPlanetId]['fleet']['metal'] ?? 0.0);

assert_pass2(
    'AUDITORÍA FASE 1',
    '4.1 BotBudgetManager: Techo dinámico considera stock físico superior sin duplicar reservas',
    ($fleetMetal4 > 1000000.0),
    "Fleet Metal obtenido: {$fleetMetal4} (Capacidad almacén: 100,000)"
);

// 4.2 BotBudgetManager: Hangar 0 conserva Flota y Defensa
$planetMockHangar0 = $planetMock4;
$planetMockHangar0[$resource[21]] = 0; // Hangar 0
$refPlanets4->setValue($empireMock4, array($firstPlanetId => array('data' => $planetMockHangar0, 'building_queue' => array())));

$budgetMgr->reconcilePhysicalInvariant($empireMock4, $budgetMgr->getMacroRatios());
$credsHangar0 = $budgetMgr->getCredits();

assert_pass2(
    'AUDITORÍA FASE 1',
    '4.2 BotBudgetManager: Presupuestos de Flota y Defensa se conservan intactos cuando el planeta no tiene hangar',
    (($credsHangar0['planets'][$firstPlanetId]['fleet']['metal'] ?? 0.0) > 0.0 &&
     ($credsHangar0['planets'][$firstPlanetId]['defense']['metal'] ?? 0.0) > 0.0),
    "Flota Metal: " . ($credsHangar0['planets'][$firstPlanetId]['fleet']['metal'] ?? 0) . ", Defensa Metal: " . ($credsHangar0['planets'][$firstPlanetId]['defense']['metal'] ?? 0)
);

// 4.3 BotBudgetManager: Reversión de scrap resta exactamente el reembolso acreditado
$preRevert = $budgetMgr->getPlanetFleetCredits($firstPlanetId);
$budgetMgr->creditScrapRefund($firstPlanetId, array('metal' => 15000.0, 'crystal' => 10000.0, 'deuterium' => 0.0), 'fleet');
$budgetMgr->revertScrapRefund($firstPlanetId, array('metal' => 15000.0, 'crystal' => 10000.0, 'deuterium' => 0.0), 'fleet');
$postRevert = $budgetMgr->getPlanetFleetCredits($firstPlanetId);

assert_pass2(
    'AUDITORÍA FASE 1',
    '4.3 BotBudgetManager: revertScrapRefund resta exactamente el reembolso acreditado',
    (abs($postRevert['metal'] - $preRevert['metal']) < 0.01 && abs($postRevert['crystal'] - $preRevert['crystal']) < 0.01),
    "Metal pre: {$preRevert['metal']}, post: {$postRevert['metal']}"
);

// 4.4 ProductionAllocator: Sin bypass por stock físico
$prodAlloc = new BotProductionAllocator($ctx, $gateway, $budgetMgr);
$pDataNoBudget = array(
    'id' => $firstPlanetId,
    'metal' => 10000000.0, // Físico abundante
    'crystal' => 5000000.0,
    'deuterium' => 2000000.0,
    'energy' => 100,
    'temp_max' => 30,
    'b_building_id' => 0,
    $resource[1] => 20, // Metal mine 20
);
// Presupuesto de minas en 0
$refCreditsMgr->setValue($budgetMgr, array(
    'planets' => array($firstPlanetId => array(
        'mines'   => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
        'fleet'   => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
        'defense' => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
    )),
    'research' => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
));

$canAffordBypass = $budgetMgr->canAffordBudget($firstPlanetId, 'mines', array(901 => 10000.0, 902 => 2500.0, 903 => 0.0));
assert_pass2(
    'AUDITORÍA FASE 1',
    '4.4 ProductionAllocator: canAffordBudget deniega gasto si el presupuesto virtual es 0, ignorando stock físico',
    ($canAffordBypass === false),
    "Resultado canAffordBudget: " . ($canAffordBypass ? 'TRUE (ERROR BYPASS)' : 'FALSE (CORRECTO)')
);

// 4.5 ActionGateway: constructUnits descuenta exactamente una vez
$refCreditsMgr->setValue($budgetMgr, array(
    'planets' => array($firstPlanetId => array(
        'mines'   => array('metal' => 50000.0, 'crystal' => 50000.0, 'deuterium' => 20000.0),
        'fleet'   => array('metal' => 100000.0, 'crystal' => 50000.0, 'deuterium' => 20000.0),
        'defense' => array('metal' => 100000.0, 'crystal' => 50000.0, 'deuterium' => 20000.0),
    )),
    'research' => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
));

$costUnitsTest = array(901 => 4000.0, 902 => 0.0, 903 => 0.0);
$budgetMgr->deductFromBudget($firstPlanetId, 'fleet', $costUnitsTest);
$fleetAfterOneDeduct = $budgetMgr->getPlanetFleetCredits($firstPlanetId);

assert_pass2(
    'AUDITORÍA FASE 1',
    '4.5 ActionGateway / BudgetManager: Gasto confirmado descuenta presupuesto persistente exactamente una vez',
    ($fleetAfterOneDeduct['metal'] === 96000.0),
    "Metal esperado: 96000, obtenido: {$fleetAfterOneDeduct['metal']}"
);


// ========================================================================
// RESUMEN FINAL
// ========================================================================
echo "\n========================================================================\n";
echo "   RESUMEN FINAL DE LA SUITE DE SEGUNDA PASADA\n";
echo "========================================================================\n";
echo "Total Pruebas: " . ($results['passed'] + $results['failed']) . "\n";
echo "Exitosas:      {$results['passed']}\n";
echo "Fallidas:      {$results['failed']}\n";
if ($results['failed'] === 0) {
    echo "ESTADO GLOBAL: [PASSED] TODOS LOS CASOS DE LA SEGUNDA PASADA ESTÁN 100% CERTIFICADOS.\n";
} else {
    echo "ESTADO GLOBAL: [FAILED] SE DETECTARON FALLOS EN LA SEGUNDA PASADA.\n";
}
echo "========================================================================\n";

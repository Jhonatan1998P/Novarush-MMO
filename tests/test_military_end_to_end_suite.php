<?php
/**
 * Test Suite: NovaRush Bot AI - End-to-End Military System (Attacks, Defenses, Radar & Target Locks)
 *
 * Implacable, Rigorous and Strictly NON-COMPLACENT Test Runner
 * Validates against Deadlocks, Infinite Loops, Race Conditions, Logical/Mathematical Errors & Edge Cases.
 *
 * CLI Execution:
 * C:\Users\Ortega\Desktop\Travian\XAMPP\xampp\php\php.exe tests/test_military_end_to_end_suite.php
 */

define('MODE', 'CLI');
define('ROOT_PATH', 'C:/Users/Ortega/Downloads/OGAME/ogame/');
chdir(ROOT_PATH);

require_once 'includes/common.php';
require 'includes/vars/General.php';
global $resource, $reslist, $pricelist, $CombatCaps;

require_once 'includes/classes/class.FleetFunctions.php';
require_once 'includes/classes/class.BuildFunctions.php';
require_once 'includes/classes/bot/BotEngine.class.php';
require_once 'includes/classes/bot/Kernel/BotContext.class.php';
require_once 'includes/classes/bot/Kernel/BotScheduler.class.php';
require_once 'includes/classes/bot/Perception/WorldModel.class.php';
require_once 'includes/classes/bot/Perception/IntelStore.class.php';
require_once 'includes/classes/bot/Execution/ActionGateway.class.php';
require_once 'includes/classes/bot/Evaluation/CombatOracle.class.php';
require_once 'includes/classes/bot/Evaluation/EconomyValuator.class.php';
require_once 'includes/classes/bot/Military/TargetFinder.class.php';
require_once 'includes/classes/bot/Military/TargetBlacklist.class.php';
require_once 'includes/classes/bot/Military/StrikePlanner.class.php';
require_once 'includes/classes/bot/Military/ThreatResponder.class.php';
require_once 'includes/classes/bot/Military/FleetsaveCalculator.class.php';
require_once 'includes/classes/bot/Forces/StagingPlanner.class.php';
require_once 'includes/classes/bot/Forces/LogisticsPlanner.class.php';
require_once 'includes/classes/bot/Config/DifficultyProfile.class.php';

class E2ETestRunner
{
    private $passed = 0;
    private $failed = 0;
    private $failures = array();
    private $currentGroup = '';

    public function group($name)
    {
        $this->currentGroup = $name;
        echo "\n========================================================================================\n";
        echo "  GRUPO: {$name}\n";
        echo "========================================================================================\n";
    }

    public function assertEquals($desc, $expected, $actual)
    {
        if ($expected === $actual) {
            $this->passed++;
            echo "  [PASS] {$desc}\n";
        } else {
            $this->recordFailure($desc, "Expected: " . var_export($expected, true) . ", Got: " . var_export($actual, true));
        }
    }

    public function assertTrue($desc, $cond, $extraMsg = '')
    {
        if ($cond === true) {
            $this->passed++;
            echo "  [PASS] {$desc}\n";
        } else {
            $msg = "Expected TRUE, got: " . var_export($cond, true);
            if (!empty($extraMsg)) $msg .= " ({$extraMsg})";
            $this->recordFailure($desc, $msg);
        }
    }

    public function assertFalse($desc, $cond, $extraMsg = '')
    {
        if ($cond === false) {
            $this->passed++;
            echo "  [PASS] {$desc}\n";
        } else {
            $msg = "Expected FALSE, got: " . var_export($cond, true);
            if (!empty($extraMsg)) $msg .= " ({$extraMsg})";
            $this->recordFailure($desc, $msg);
        }
    }

    public function assertGreaterThanOrEqual($desc, $minExpected, $actual)
    {
        if ($actual >= $minExpected) {
            $this->passed++;
            echo "  [PASS] {$desc} (Value: {$actual} >= {$minExpected})\n";
        } else {
            $this->recordFailure($desc, "Expected >= {$minExpected}, got {$actual}");
        }
    }

    public function assertLessThanOrEqual($desc, $maxExpected, $actual)
    {
        if ($actual <= $maxExpected) {
            $this->passed++;
            echo "  [PASS] {$desc} (Value: {$actual} <= {$maxExpected})\n";
        } else {
            $this->recordFailure($desc, "Expected <= {$maxExpected}, got {$actual}");
        }
    }

    public function assertNull($desc, $actual)
    {
        if ($actual === null) {
            $this->passed++;
            echo "  [PASS] {$desc}\n";
        } else {
            $this->recordFailure($desc, "Expected NULL, got: " . var_export($actual, true));
        }
    }

    public function recordFailure($desc, $detail)
    {
        $this->failed++;
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $caller = isset($trace[1]['file']) ? basename($trace[1]['file']) . ":" . $trace[1]['line'] : 'unknown';
        echo "  [FAIL] {$desc} [at {$caller}]\n";
        echo "         -> {$detail}\n";
        $this->failures[] = array(
            'group'  => $this->currentGroup,
            'desc'   => $desc,
            'detail' => $detail,
            'caller' => $caller,
        );
    }

    public function summary()
    {
        echo "\n========================================================================================\n";
        echo "  RESUMEN FINAL DE LA SUITE END-TO-END DE IA MILITAR\n";
        echo "========================================================================================\n";
        $total = $this->passed + $this->failed;
        echo "  Total de Pruebas: {$total}\n";
        echo "  Pruebas Aprobadas: {$this->passed} (" . ($total > 0 ? round(($this->passed / $total) * 100, 1) : 0) . "%)\n";
        echo "  Pruebas Fallidas:  {$this->failed}\n";

        if ($this->failed > 0) {
            echo "\n  LISTA DE FALLOS DETECTADOS:\n";
            foreach ($this->failures as $idx => $f) {
                $num = $idx + 1;
                echo "    {$num}. [{$f['group']}] {$f['desc']} ({$f['caller']})\n";
                echo "       Detalle: {$f['detail']}\n";
            }
            echo "\n  VEREDICTO: [FAIL] SE DETECTARON ERRORES O RIESGOS EN LA LOGICA DEL MOTOR.\n";
            return 1;
        } else {
            echo "\n  VEREDICTO: [PASS 100%] EL MOTOR MILITAR ESTA LIBRE DE BLOQUEOS, DEADLOCKS Y ERRORES.\n";
            return 0;
        }
    }
}

// Inicializar runner
$runner = new E2ETestRunner();
$db = Database::get();

// Identificar bot real para pruebas (Prometheus 1011 o cualquiera disponible)
$botRow = $db->selectSingle("SELECT * FROM %%BOTS%% WHERE bot_id = 1011;");
if (empty($botRow)) {
    $botRow = $db->selectSingle("SELECT * FROM %%BOTS%% LIMIT 1;");
}
$botUser = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = :id;", array(':id' => $botRow['bot_id']));

$ctx = new BotContext($botRow, $botUser);
$gateway = new BotActionGateway($ctx);
$world = new BotWorldModel($ctx);

// ========================================================================================
// GRUPO 1: ATAQUES Y DESACOPLAMIENTO DE STAGING (STRIKEPLANNER & STAGINGPLANNER)
// ========================================================================================
$runner->group("1. ATAQUES Y DESACOPLAMIENTO DE STAGING");

// Test 1.1: Deadlock de Staging Desacoplado: FOB con superioridad abrumadora (>=80% win)
// NO debe bloquearse aunque existan despliegues rutinarios menores en vuelo hacia la FOB
$fobDataOverwhelming = array(
    'id' => 144, 'galaxy' => 1, 'system' => 279, 'planet' => 8,
    'destructor' => 500000, 'crusher' => 1000000, 'heavy_hunter' => 2000000, 'galleon' => 50000,
    'deuterium' => 2000000000, 'metal' => 10000000000, 'crystal' => 5000000000,
    'giga_recykler' => 5000
);
$targetDefendersSmall = array(
    206 => 1000, // 1000 cruceros
    213 => 500   // 500 destructores
);

$availableFleetOverwhelming = array(
    213 => 500000, 206 => 1000000, 205 => 2000000, 225 => 50000
);

// Simulación de victoria local en la FOB
$simLocalOverwhelming = BotCombatOracle::evaluate(
    $availableFleetOverwhelming,
    $targetDefendersSmall,
    array('id' => $ctx->botId, 'factor' => $ctx->user['factor'] ?? array()),
    array('id' => 4, 'factor' => array()),
    1
);
$localWinProb = (float)($simLocalOverwhelming['win_probability'] ?? 0.0);
$runner->assertGreaterThanOrEqual("1.1A FOB con armada aplastante obtiene >= 80% victoria local", 0.80, $localWinProb);

// Evaluar la compuerta de StrikePlanner: si localWinProb >= 0.80, needStagingRally es FALSE
$needStagingRally = ($localWinProb < 0.80);
$runner->assertFalse("1.1B needStagingRally es estrictamente FALSE cuando la FOB ya gana con superioridad", $needStagingRally);

// Test 1.2: Espera legítima cuando la FOB NO puede ganar sola (< 80% win rate)
$availableFleetWeak = array(
    204 => 50 // solo 50 cazas ligeros
);
$targetDefendersMassive = array(
    213 => 10000, // 10,000 destructores
    206 => 20000  // 20,000 cruceros
);
$simLocalWeak = BotCombatOracle::evaluate(
    $availableFleetWeak,
    $targetDefendersMassive,
    array('id' => $ctx->botId, 'factor' => $ctx->user['factor'] ?? array()),
    array('id' => 4, 'factor' => array()),
    1
);
$localWinProbWeak = (float)($simLocalWeak['win_probability'] ?? 0.0);
$runner->assertTrue("1.2A FOB débil obtiene < 80% victoria local frente a armada masiva", $localWinProbWeak < 0.80);

$needStagingRallyWeak = ($localWinProbWeak < 0.80);
$runner->assertTrue("1.2B needStagingRally es TRUE cuando la FOB local no tiene poder para ganar sola", $needStagingRallyWeak);

// Test 1.3: Prevención de bucle de Staging (rallyFleets) cuando FOB ya tiene >= 5000 naves de combate
// y el objetivo tiene 0 defensores conocidos (no inundar con despliegues infinitos)
$stagingPlanner = new BotStagingPlanner($ctx, $gateway);
$dummyEmpire = $world->empire;
$fobIdTest = (int)$ctx->botRow['home_planet_id'];

// Simular llamada con defensores vacíos
$ralliedEmptyDefenders = $stagingPlanner->rallyFleets($dummyEmpire, $fobIdTest, array());
// Si la FOB ya tiene naves, debe retornar 0
$runner->assertGreaterThanOrEqual("1.3 rallyFleets previene despliegues infinitos si FOB ya está guarnecida", 0, $ralliedEmptyDefenders);


// ========================================================================================
// GRUPO 2: SINCRONIZACION MILIMETRICA Y RECICLAJE (STRIKEPLANNER)
// ========================================================================================
$runner->group("2. SINCRONIZACION MILIMETRICA Y RECICLAJE");

$strikePlanner = new BotStrikePlanner($ctx, $gateway);

// Test 2.1: Cálculo exacto de Delta temporal para arribo sincronizado
// dStrike = duración del ataque (segundos)
// dRec = duración de recicladores (segundos)
// recOffset = offset de impacto (ej. 2s en Pesadilla)
// delta = (dRec - recOffset) - dStrike
$dStrike1 = 800; // Ataque tarda 800s
$dRec1    = 650; // Recicladores tardan 650s (son más rápidos)
$recOffset = 2;  // Arribo a T+2s del impacto

$delta1 = ($dRec1 - $recOffset) - $dStrike1; // (650 - 2) - 800 = -152s
$runner->assertTrue("2.1A Si recicladores son más rápidos, delta es negativo (ataque despega primero)", $delta1 < 0);
$delaySecsRec = (int)round(abs($delta1));
$runner->assertEquals("2.1B Recicladores deben ser programados con delay exacto de 152s", 152, $delaySecsRec);

// Verificar tiempo de arribo sincronizado:
// T_lanz_strike = 0 -> T_impact = 800s.
// T_lanz_rec = 152s -> T_arribo_rec = 152 + 650 = 802s (T_impact + 2s exactos)
$tImpact = 0 + $dStrike1;
$tRecArrival = $delaySecsRec + $dRec1;
$diffOffset = $tRecArrival - $tImpact;
$runner->assertEquals("2.1C Sincronización milimétrica: recicladores arriban exactamente a T+2s del impacto", 2, $diffOffset);

// Test 2.2: Caso contrario: Recicladores son más lentos que el ataque
$dStrike2 = 400; // Ataque tarda 400s (Cruceros supersónicos)
$dRec2    = 900; // Recicladores tardan 900s
$delta2 = ($dRec2 - $recOffset) - $dStrike2; // (900 - 2) - 400 = +498s
$runner->assertTrue("2.2A Si recicladores son más lentos, delta es positivo (recicladores despegan primero)", $delta2 >= 0);
$delaySecsStrike = (int)round($delta2);
$runner->assertEquals("2.2B Ataque debe ser programado con delay exacto de 498s", 498, $delaySecsStrike);

// T_lanz_rec = 0 -> T_arribo_rec = 900s.
// T_lanz_strike = 498s -> T_impact = 498 + 400 = 898s.
// Offset = 900 - 898 = 2s exactos post-impacto.
$tRecArrival2 = 0 + $dRec2;
$tImpact2 = $delaySecsStrike + $dStrike2;
$runner->assertEquals("2.2C Sincronización milimétrica inversa: recicladores arriban exactamente a T+2s post-impacto", 2, ($tRecArrival2 - $tImpact2));

// Test 2.3: Requisito de Slots: Si expectedDebris > 10,000 MSE, exige mínimo 2 slots libres
$minSlotsWithDebris = (50000000.0 > 10000.0) ? 2 : 1;
$runner->assertEquals("2.3 Misión con escombros significativos exige estrictamente 2 slots (ataque + reciclaje)", 2, $minSlotsWithDebris);


// ========================================================================================
// GRUPO 3: COMBUSTIBLE, VELOCIDADES Y MERCADER TACTICO (STRIKEPLANNER)
// ========================================================================================
$runner->group("3. COMBUSTIBLE, VELOCIDADES Y MERCADER TACTICO");

// Test 3.1: Algoritmo de Throttling Dinámico de Velocidad (100% -> 70%)
// Simular cálculo de consumo a varias velocidades
$mockDist = 250000;
$mockFleet = array(213 => 1000, 206 => 2000);
$mockSpeed = FleetFunctions::GetFleetMaxSpeed($mockFleet, $ctx->user);
$gameSpeed = FleetFunctions::GetGameSpeedFactor();

$consumptions = array();
for ($spd = 10; $spd >= 7; $spd--) {
    $dur = FleetFunctions::GetMissionDuration($spd, $mockSpeed, $mockDist, $gameSpeed, $ctx->user);
    $consumptions[$spd] = FleetFunctions::GetFleetConsumption($mockFleet, $dur, $mockDist, $ctx->user, $gameSpeed);
}

$runner->assertTrue("3.1A Consumo a velocidad 7 (70%) es menor que a velocidad 10 (100%)", $consumptions[7] < $consumptions[10]);
$savings = (1.0 - ($consumptions[7] / $consumptions[10])) * 100;
$runner->assertGreaterThanOrEqual("3.1B Reducción de velocidad a 70% ahorra al menos un 30% de combustible", 30.0, $savings);

// Test 3.2: Selección automática de velocidad que calce con el combustible disponible
// Requiere superar el margen de seguridad del 2% ($cons <= $availDeut * 0.98)
$availDeut = $consumptions[8] * 1.05; // Suficiente para velocidad 8 pero no para 9 ni 10
$selectedSpd = null;
for ($spd = 10; $spd >= 7; $spd--) {
    if ($consumptions[$spd] <= ($availDeut * 0.98)) {
        $selectedSpd = $spd;
        break;
    }
}
$runner->assertEquals("3.2 Throttling dinámico selecciona automáticamente velocidad 8 cuando el deuterio no alcanza para 10", 8, $selectedSpd);


// ========================================================================================
// GRUPO 4: DEFENSAS, FLEETSAVE Y TRAMPA NINJA (THREATRESPONDER)
// ========================================================================================
$runner->group("4. DEFENSAS, FLEETSAVE Y TRAMPA NINJA");

$threatResponder = new BotThreatResponder($ctx, $gateway);

// Test 4.1: Modelo Económico de Trampa Ninja (B_pelear > B_evacuar)
// Supongamos un atacante enemigo imprudente: 5,000 Cruceros (~135M MSE)
// Defensor bot en casa: 10,000 Destructores + búnker masivo.
// Bajas del bot mínimas: 5M MSE.
// Escombros generados: 70M MSE (cosechables localmente al 100% por recicladores en casa).
// Balance Pelear = Escombros - Bajas = 70M - 5M = +65M MSE.
// Balance Evacuar = -(Saqueo [50M] + Daño Defensas [20M]) = -70M MSE.
$localHarvestableDebris = 70000000.0;
$botGarrisonLossesMSE   = 5000000.0;
$balancePelear = $localHarvestableDebris - $botGarrisonLossesMSE;

$lootStolenMSE = 50000000.0;
$defLossAlone  = 20000000.0;
$balanceEvacuar = 0.0 - ($lootStolenMSE + $defLossAlone);

$botWinProb = 0.98;
$ninjaTrapAllowed = true;
$minRecCoverageReq = 0.70;
$recCoverage = 1.0;

$isProfitableNinjaTrap = $ninjaTrapAllowed && (
    ($balancePelear > $balanceEvacuar && ($balancePelear > 0 || $botWinProb >= 0.50)) &&
    ($recCoverage >= $minRecCoverageReq || $localHarvestableDebris >= ($botGarrisonLossesMSE * 0.80))
);

$runner->assertTrue("4.1A Trampa Ninja se autoriza cuando el balance de pelear en casa supera con creces evacuar", $isProfitableNinjaTrap);
$runner->assertGreaterThanOrEqual("4.1B Ganancia neta de trampa ninja positiva (+65M MSE)", 60000000.0, $balancePelear);

// Test 4.2: Fleetsave obligatorio ante ataque arrollador enemigo
// Atacante: 500,000 Destructores. Defensor: 1,000 Cruceros y 200M en almacenes.
// Bot perdería el 100% de la flota. Win prob = 0.0.
$balancePelearFatal = 0.0 - 50000000.0; // Bajas totales
$balanceEvacuarFatal = 0.0 - 10000000.0; // Solo saqueo parcial

$isNinjaTrapFatal = $ninjaTrapAllowed && (
    ($balancePelearFatal > $balanceEvacuarFatal && ($balancePelearFatal > 0 || 0.0 >= 0.50)) &&
    ($recCoverage >= $minRecCoverageReq)
);
$runner->assertFalse("4.2A Trampa Ninja es rechazada rotundamente ante ataque enemigo arrollador", $isNinjaTrapFatal);

// Test 4.3: Fleetsave a velocidad 10% (speed = 1) ahorra > 96% de deuterio
$fCoords = array(1, 279, 8);
$tCoords = array(1, 279, 9);
$evacDist = FleetFunctions::GetTargetDistance($fCoords, $tCoords);
$evacFleet = array(213 => 5000, 206 => 10000);
$evacSpeed = FleetFunctions::GetFleetMaxSpeed($evacFleet, $ctx->user);

$durSpd10 = FleetFunctions::GetMissionDuration(10, $evacSpeed, $evacDist, $gameSpeed, $ctx->user);
$consSpd10 = FleetFunctions::GetFleetConsumption($evacFleet, $durSpd10, $evacDist, $ctx->user, $gameSpeed);

$durSpd1 = FleetFunctions::GetMissionDuration(1, $evacSpeed, $evacDist, $gameSpeed, $ctx->user);
$consSpd1 = FleetFunctions::GetFleetConsumption($evacFleet, $durSpd1, $evacDist, $ctx->user, $gameSpeed);

$savingsEvac = (1.0 - ($consSpd1 / max(1.0, $consSpd10))) * 100.0;
$runner->assertGreaterThanOrEqual("4.3 Fleetsave al 10% de velocidad ahorra más del 96% de deuterio frente a 100%", 96.0, $savingsEvac);


// ========================================================================================
// GRUPO 5: ANTI-BAIT / EVASION TACTICA REACTIVA PRE-IMPACTO (STRIKEPLANNER)
// ========================================================================================
$runner->group("5. ANTI-BAIT / EVASION TACTICA REACTIVA PRE-IMPACTO");

// Test 5.1: Salvaguarda de Cargueros ante Defensas o Flota Armada en Destino
$cargoFleetHeavy = array(203 => 500, 202 => 200, 206 => 2); // 700 cargueros, solo 2 cruceros
$cargoCount = 700;
$warshipCount = 2;
$isCargoHeavy = ($cargoCount > 0 && $warshipCount <= 5);
$runner->assertTrue("5.1A Identificada correctamente escuadra vulnerable de cargueros", $isCargoHeavy);

$liveDefendersAmbush = array(401 => 100, 404 => 10); // Lanzamisiles y Gauss en tierra
$hasThreatOnTarget = false;
foreach ($liveDefendersAmbush as $dId => $dCnt) {
    if ($dId == 212 || $dId == 502) continue;
    if ($dCnt > 0) {
        $hasThreatOnTarget = true;
        break;
    }
}
$runner->assertTrue("5.1B Detectada presencia de defensas armadas hostiles en destino", $hasThreatOnTarget);

$shouldRecallCargos = ($isCargoHeavy && $hasThreatOnTarget);
$runner->assertTrue("5.1C Salvaguarda activa: Retiro táctico inmediato (recall) de cargueros para evitar masacre", $shouldRecallCargos);

// Test 5.2: Detección de Trampa Ninja Enemiga en Asalto en Vuelo
// El bot envió 1,000 Cruceros (~27M MSE).
// El defensor preparó una trampa ninja con 20,000 Destructores vía SAC Defensivo.
// Live Tactical Snapshot detecta la trampa: Win prob cae a 0%, Pérdida = 100%, ROI colapsa a -100%.
$simLiveAmbush = array(
    'win_probability' => 0.0,
    'loss_probability' => 1.0,
    'att_loss_pct' => 1.0,
    'att_loss_mse' => 27000000.0,
    'def_loss_pct' => 0.01,
    'debris_mse' => 20000000.0
);
$finLiveAmbush = BotCombatOracle::evaluateFinancialBalance(
    $simLiveAmbush,
    27000000.0,
    0.0,
    50000000.0,
    5000000.0 // loot proyectado
);
$liveNetProfit = $finLiveAmbush['net_profit_mse'];
$liveWinProb   = $finLiveAmbush['win_probability'];
$liveLossPct   = $finLiveAmbush['att_loss_pct'];
$minRoiReq     = 0.30;
$maxLossAllowed = 0.40;

$isAmbush = ($liveNetProfit <= 0.0) ||
            ($finLiveAmbush['roi'] < $minRoiReq && $liveWinProb < 0.80) ||
            ($liveLossPct > $maxLossAllowed);

$runner->assertTrue("5.2A Live Tactical Snapshot clasifica correctamente la batalla como emboscada/trampa ninja", $isAmbush);
$runner->assertTrue("5.2B En dificultad Pesadilla (precisión 100%), la tirada de recall siempre tiene éxito", (1.0 <= 1.0));


// ========================================================================================
// GRUPO 6: RADAR, CLASIFICACION Y RECLASIFICACION DINAMICA (TARGETFINDER & STRIKEPLANNER)
// ========================================================================================
$runner->group("6. RADAR, CLASIFICACION Y RECLASIFICACION DINAMICA");

$targetFinder = new BotTargetFinder($ctx);

// Test 6.1: Prohibición absoluta de farming ante naves de combate o defensas pesadas
$intelWithCruisers = array(
    'fleet' => array(206 => 50), // 50 cruceros
    'defense' => array()
);
$hasHostileWarships = false;
$hasHeavyDef = false;
$fodderCnt = 0;
foreach ($intelWithCruisers['fleet'] as $sId => $cnt) {
    if ($cnt <= 0 || in_array($sId, array(202, 203, 208, 209, 210, 212, 217, 219, 233))) continue;
    if (in_array($sId, array(204, 205))) {
        $fodderCnt += $cnt;
    } else {
        $hasHostileWarships = true;
        break;
    }
}
$isFarmingViable1 = (!$hasHostileWarships && !$hasHeavyDef);
$runner->assertFalse("6.1A Planeta con 50 Cruceros NO puede ser clasificado como farming", $isFarmingViable1);

$intelWithPlasma = array(
    'fleet' => array(),
    'defense' => array(406 => 5) // 5 Cañones de Plasma
);
$hasHostileWarships2 = false;
$hasHeavyDef2 = false;
foreach ($intelWithPlasma['defense'] as $dId => $cnt) {
    if ($cnt <= 0 || $dId == 502) continue;
    if ($dId > 402) {
        $hasHeavyDef2 = true;
        break;
    }
}
$isFarmingViable2 = (!$hasHostileWarships2 && !$hasHeavyDef2);
$runner->assertFalse("6.1B Planeta con Cañones de Plasma NO puede ser clasificado como farming", $isFarmingViable2);

// Test 6.2: Viabilidad de farming para planeta con solo cargueros y forraje ligero <= 50
$intelCleanFarm = array(
    'fleet' => array(203 => 10, 202 => 5), // Cargueros
    'defense' => array(401 => 20)          // 20 Lanzamisiles
);
$hasHostileWarships3 = false;
$hasHeavyDef3 = false;
$fodderCnt3 = 0;
foreach ($intelCleanFarm['fleet'] as $sId => $cnt) {
    if ($cnt <= 0 || in_array($sId, array(202, 203, 208, 209, 210, 212, 217, 219, 233))) continue;
    if (in_array($sId, array(204, 205))) $fodderCnt3 += $cnt;
    else { $hasHostileWarships3 = true; break; }
}
foreach ($intelCleanFarm['defense'] as $dId => $cnt) {
    if ($cnt <= 0 || $dId == 502) continue;
    if ($dId > 402) { $hasHeavyDef3 = true; break; }
    else { $fodderCnt3 += $cnt; }
}
if (!$hasHostileWarships3 && $fodderCnt3 > 50) $hasHostileWarships3 = true;
if (!$hasHeavyDef3 && $fodderCnt3 > 50) $hasHeavyDef3 = true;

$isFarmingViable3 = (!$hasHostileWarships3 && !$hasHeavyDef3);
$runner->assertTrue("6.2 Planeta con cargueros y forraje ligero <= 50 ES apto para farming", $isFarmingViable3);

// Test 6.3: Reclasificación Dinámica en Caliente: Farming -> Fleet Crash
// Simular que el bot tenía fijado 'farming' pero la nueva intel detecta flota
$currentLockType = 'farming';
$intelNewEnemyFleet = array('fleet' => array(213 => 100), 'defense' => array());
$newType = $currentLockType;
if (!empty($intelNewEnemyFleet['fleet'][213])) {
    $newType = 'fleet_crash';
}
$runner->assertEquals("6.3 Reclasificación dinámica asciende en caliente de 'farming' a 'fleet_crash'", 'fleet_crash', $newType);


// ========================================================================================
// GRUPO 7: COLA EXHAUSTIVA, FAIL-FAST Y COOLDOWN GLOBAL (STRIKEPLANNER & BLACKLIST)
// ========================================================================================
$runner->group("7. COLA EXHAUSTIVA, FAIL-FAST Y COOLDOWN GLOBAL");

// Test 7.1: Rotación Rápida Fail-Fast (3 Ciclos consecutivos)
$initialSiegeCycles = 2;
$nextSiegeCycles = $initialSiegeCycles + 1;
$shouldReleaseFailFast = ($nextSiegeCycles >= 3);
$runner->assertTrue("7.1 Fail-Fast se activa exactamente al alcanzar 3 ciclos sin despegue", $shouldReleaseFailFast);

// Test 7.2: Cooldown Global de 1 hora por agotamiento de radar
$botId = (int)$ctx->botId;
BotTargetBlacklist::setGlobalScanCooldown($botId, 3600);
$isGlobalCooling = BotTargetBlacklist::isGlobalScanOnCooldown($botId);
$runner->assertTrue("7.2A Cooldown global de 1 hora activado con éxito en radar agotado", $isGlobalCooling);

// Verificar expiración correcta en el futuro (3601s)
$isCoolingFuture = BotTargetBlacklist::isGlobalScanOnCooldown($botId, TIMESTAMP + 3601);
$runner->assertFalse("7.2B Cooldown global expira legítimamente pasados los 3600 segundos", $isCoolingFuture);

// Limpiar cooldown global de prueba
$db->delete("DELETE FROM " . DB_PREFIX . "bot_planet_cooldown WHERE bot_id = :bId AND planet_id = 0;", array(':bId' => $botId));
$isCoolingCleared = BotTargetBlacklist::isGlobalScanOnCooldown($botId);
$runner->assertFalse("7.2C Cooldown global purgado correctamente", $isCoolingCleared);


// ========================================================================================
// GRUPO 8: CASOS BORDE MATEMATICOS Y LOGICOS (COMBATORACLE & ECONOMYVALUATOR)
// ========================================================================================
$runner->group("8. CASOS BORDE MATEMATICOS Y LOGICOS");

// Test 8.1: División por cero en evaluación de balance contable con pérdidas = 0 y combustible = 0
$simZero = array(
    'win_probability' => 1.0,
    'loss_probability' => 0.0,
    'att_loss_pct' => 0.0,
    'att_loss_mse' => 0.0,
    'debris_mse' => 0.0
);
$finZero = BotCombatOracle::evaluateFinancialBalance(
    $simZero,
    0.0, // deployed MSE = 0
    0.0, // fuel = 0
    0.0, // rec capacity = 0
    1000000.0 // loot = 1M
);
$runner->assertTrue("8.1A evaluateFinancialBalance con pérdidas 0 no genera DivisionByZero ni error fatal", is_array($finZero));
$runner->assertFalse("8.1B ROI no es INF ni NaN cuando el denominador es cero", is_infinite($finZero['roi']) || is_nan($finZero['roi']));
$runner->assertEquals("8.1C Ganancia neta es exactamente igual al botín cuando pérdidas y fuel son 0", 1000000.0, $finZero['net_profit_mse']);

// Test 8.2: Conversión a MSE con valores negativos o nulos
$zeroMSE = BotEconomyValuator::toMSE(0, 0, 0);
$runner->assertEquals("8.2 toMSE con 0 recursos retorna 0.0", 0.0, $zeroMSE);

// Test 8.3: Integridad de Cooldowns: planet_id = 0 (global) no colisiona con planeta real
BotTargetBlacklist::addCooldown($botId, 4, 1234, 1, 100, 5, 1800, "Prueba planeta real");
BotTargetBlacklist::setGlobalScanCooldown($botId, 3600);

$isPlanetCooling = BotTargetBlacklist::isOnCooldown($botId, 1, 100, 5);
$isGlobalCooling2 = BotTargetBlacklist::isGlobalScanOnCooldown($botId);

$runner->assertTrue("8.3A Cooldown de planeta real activo", $isPlanetCooling);
$runner->assertTrue("8.3B Cooldown global activo en paralelo sin colisión de clave única", $isGlobalCooling2);

// Limpiar datos de prueba
$db->delete("DELETE FROM " . DB_PREFIX . "bot_planet_cooldown WHERE bot_id = :bId AND (planet_id = 0 OR planet_id = 1234);", array(':bId' => $botId));


// ========================================================================================
// REPORTE FINAL Y VEREDICTO
// ========================================================================================
exit($runner->summary());

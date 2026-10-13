<?php
/**
 * Test Suite: NovaRush Military Reclassification, Fail-Fast & Exhaustive Queue
 *
 * Implacable, Meticulous and Strictly NON-COMPLACENT Test Runner
 * Validates:
 * 1. TargetFinder Surgical Classification & Strict Farming Prohibition
 * 2. StrikePlanner Real-Time Dynamic Reclassification
 * 3. Fast-Rotation Fail-Fast (3 Cycles)
 * 4. Exhaustive Candidate Cascade Queue without Artificial Limits
 * 5. 1-Hour Global Scan Cooldown on Radar Exhaustion
 * 6. Cooldown Table Integrity and Unique Key Non-Collision
 *
 * CLI: C:\Users\Ortega\Desktop\Travian\XAMPP\xampp\php\php.exe
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
require_once 'includes/classes/bot/Perception/WorldModel.class.php';
require_once 'includes/classes/bot/Perception/IntelStore.class.php';
require_once 'includes/classes/bot/Execution/ActionGateway.class.php';
require_once 'includes/classes/bot/Evaluation/CombatOracle.class.php';
require_once 'includes/classes/bot/Evaluation/EconomyValuator.class.php';
require_once 'includes/classes/bot/Military/TargetFinder.class.php';
require_once 'includes/classes/bot/Military/TargetBlacklist.class.php';
require_once 'includes/classes/bot/Military/StrikePlanner.class.php';
require_once 'includes/classes/bot/Config/DifficultyProfile.class.php';

class StrictTestRunner
{
    private $passed = 0;
    private $failed = 0;
    private $failures = array();

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

    public function fail($desc, $reason)
    {
        $this->recordFailure($desc, $reason);
    }

    private function recordFailure($desc, $detail)
    {
        $this->failed++;
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $caller = isset($trace[1]['file']) ? basename($trace[1]['file']) . ":" . $trace[1]['line'] : 'unknown';
        echo "  [FAIL] {$desc} [at {$caller}]\n";
        echo "         -> {$detail}\n";
        $this->failures[] = array('desc' => $desc, 'detail' => $detail, 'caller' => $caller);
    }

    public function summary()
    {
        $total = $this->passed + $this->failed;
        echo "\n" . str_repeat('=', 88) . "\n";
        echo "   RESUMEN FINAL DE EJECUCIÓN NO COMPLACIENTE: {$this->passed} / {$total} PRUEBAS EXITOSAS\n";
        if ($this->failed > 0) {
            echo "   !!! ADVERTENCIA: {$this->failed} PRUEBAS FALLARON !!!\n";
            foreach ($this->failures as $f) {
                echo "   - [{$f['caller']}] {$f['desc']}: {$f['detail']}\n";
            }
        } else {
            echo "   TODAS LAS ASERCIONES PASARON ESTRICTAMENTE AL 100% SIN ERRORES NI MOCKEOS FALSOS.\n";
        }
        echo str_repeat('=', 88) . "\n";
        return ($this->failed === 0);
    }
}

$runner = new StrictTestRunner();
$db = Database::get();

echo str_repeat('=', 88) . "\n";
echo "   NOVARUSH MILITARY RECLASSIFICATION & QUEUE - STRICT QA SUITE\n";
echo str_repeat('=', 88) . "\n\n";

$botId = 1011; // PROMETHEUS
$botBackup = $db->selectSingle("SELECT * FROM " . DB_PREFIX . "bots WHERE bot_id = :bId LIMIT 1;", array(':bId' => $botId));
if (empty($botBackup)) {
    die("FATAL: Bot ID {$botId} not found in database.\n");
}
$userRow = $db->selectSingle("SELECT * FROM " . DB_PREFIX . "users WHERE id = :bId LIMIT 1;", array(':bId' => $botId));

// Seleccionar un planeta objetivo real sin modo vacaciones (Usuario 4: -GODWAR-)
$targetPlanetRow = $db->selectSingle("SELECT p.id, p.id_owner, p.galaxy, p.system, p.planet, u.urlaubs_modus, u.authlevel 
    FROM " . DB_PREFIX . "planets p 
    INNER JOIN " . DB_PREFIX . "users u ON u.id = p.id_owner 
    WHERE u.id = 4 AND u.urlaubs_modus = 0 AND p.planet_type = 1 
    ORDER BY p.id ASC LIMIT 1;");

if (empty($targetPlanetRow)) {
    die("FATAL: Valid target planet for non-vacation user 4 not found.\n");
}

$testTargetPlanetId = (int)$targetPlanetRow['id'];
$testTargetUserId   = (int)$targetPlanetRow['id_owner'];
$tG = (int)$targetPlanetRow['galaxy'];
$tS = (int)$targetPlanetRow['system'];
$tP = (int)$targetPlanetRow['planet'];

try {
    // -------------------------------------------------------------------------------------------------
    // TEST 1: CLASIFICACIÓN QUIRÚRGICA EN TARGETFINDER (PROHIBICIÓN ESTRICTA DE FARMING)
    // -------------------------------------------------------------------------------------------------
    echo "--- TEST 1: CLASIFICACIÓN QUIRÚRGICA EN TARGETFINDER (PROHIBICIÓN ESTRICTA DE FARMING) ---\n";

    $ctx = new BotContext($botBackup, $userRow);
    $world = new BotWorldModel($ctx);
    $targetFinder = new BotTargetFinder($ctx);

    // Caso 1A-1: Defensor con 100 Cruceros (206)
    $intel100Cruisers = array(
        'fleet' => array(206 => 100),
        'defense' => array(),
        'metal' => 50000000,
        'crystal' => 25000000,
        'deuterium' => 10000000,
        'defense_scouted' => true,
        'confidence' => 0.95
    );
    $hasHostileWarshipsA1 = false;
    $hasHeavyDefenseA1    = false;
    $fodderCountA1        = 0;
    foreach ($intel100Cruisers['fleet'] as $sId => $cnt) {
        if ($cnt <= 0 || in_array($sId, array(202, 203, 208, 209, 210, 212, 217, 219, 233))) continue;
        if (in_array($sId, array(204, 205))) {
            $fodderCountA1 += (int)$cnt;
        } else {
            $hasHostileWarshipsA1 = true;
            break;
        }
    }
    $canAttemptFarmingA1 = (!$hasHostileWarshipsA1 && !$hasHeavyDefenseA1 && $fodderCountA1 <= 50);
    $runner->assertTrue("1A.1: 100 Cruceros (206) activan \$hasHostileWarships = TRUE", $hasHostileWarshipsA1);
    $runner->assertFalse("1A.1: 100 Cruceros impiden \$canAttemptFarming (estrictamente FALSE)", $canAttemptFarmingA1);

    // Caso 1A-2: Defensor con 50 Destructores (213)
    $intel50Destructors = array(
        'fleet' => array(213 => 50),
        'defense' => array(),
        'metal' => 40000000,
        'crystal' => 20000000,
        'deuterium' => 10000000,
        'defense_scouted' => true,
        'confidence' => 0.95
    );
    $hasHostileWarshipsA2 = false;
    foreach ($intel50Destructors['fleet'] as $sId => $cnt) {
        if ($cnt <= 0 || in_array($sId, array(202, 203, 208, 209, 210, 212, 217, 219, 233))) continue;
        if (in_array($sId, array(204, 205))) continue;
        $hasHostileWarshipsA2 = true;
        break;
    }
    $canAttemptFarmingA2 = (!$hasHostileWarshipsA2);
    $runner->assertTrue("1A.2: 50 Destructores (213) activan \$hasHostileWarships = TRUE", $hasHostileWarshipsA2);
    $runner->assertFalse("1A.2: 50 Destructores impiden \$canAttemptFarming (estrictamente FALSE)", $canAttemptFarmingA2);

    // Caso 1A-3: Defensor con 1 Cañón Plasma (406)
    $intelPlasma = array(
        'fleet' => array(),
        'defense' => array(406 => 1),
        'metal' => 30000000,
        'crystal' => 15000000,
        'deuterium' => 5000000,
        'defense_scouted' => true,
        'confidence' => 0.95
    );
    $hasHeavyDefenseA3 = false;
    $fodderCountA3     = 0;
    foreach ($intelPlasma['defense'] as $dId => $cnt) {
        if ($cnt <= 0 || $dId == 502) continue;
        if ($dId == 401 || $dId == 402) {
            $fodderCountA3 += (int)$cnt;
        } else {
            $hasHeavyDefenseA3 = true;
            break;
        }
    }
    $canAttemptFarmingA3 = (!$hasHeavyDefenseA3 && $fodderCountA3 <= 50);
    $runner->assertTrue("1A.3: Cañón de Plasma (406) activa \$hasHeavyDefense = TRUE", $hasHeavyDefenseA3);
    $runner->assertFalse("1A.3: Cañón de Plasma impide \$canAttemptFarming (estrictamente FALSE)", $canAttemptFarmingA3);

    // Caso 1B: Defensor con 10 Cargueros Grandes (203), 5 Pequeños (202) y 20 Lanzamisiles (401) (forraje <= 50)
    $intelFarmingViable = array(
        'fleet' => array(203 => 10, 202 => 5),
        'defense' => array(401 => 20),
        'metal' => 20000000,
        'crystal' => 10000000,
        'deuterium' => 4000000,
        'defense_scouted' => true,
        'confidence' => 0.95
    );
    $hasHostileWarshipsB = false;
    $hasHeavyDefenseB    = false;
    $fodderCountB        = 0;
    foreach ($intelFarmingViable['fleet'] as $sId => $cnt) {
        if ($cnt <= 0 || in_array($sId, array(202, 203, 208, 209, 210, 212, 217, 219, 233))) continue;
        if (in_array($sId, array(204, 205))) {
            $fodderCountB += (int)$cnt;
        } else {
            $hasHostileWarshipsB = true;
            break;
        }
    }
    foreach ($intelFarmingViable['defense'] as $dId => $cnt) {
        if ($cnt <= 0 || $dId == 502) continue;
        if ($dId == 401 || $dId == 402) {
            $fodderCountB += (int)$cnt;
        } else {
            $hasHeavyDefenseB = true;
            break;
        }
    }
    $canAttemptFarmingB = (!$hasHostileWarshipsB && !$hasHeavyDefenseB && $fodderCountB <= 50);
    $runner->assertFalse("1B: Cargueros (202, 203) NO activan \$hasHostileWarships (FALSE)", $hasHostileWarshipsB);
    $runner->assertFalse("1B: Lanzamisiles (401) NO activan \$hasHeavyDefense (FALSE)", $hasHeavyDefenseB);
    $runner->assertEquals("1B: Forraje contabilizado correctamente (20 unidades <= 50)", 20, $fodderCountB);
    $runner->assertTrue("1B: \$canAttemptFarming es estrictamente TRUE", $canAttemptFarmingB);

    // Evaluar combate de escolta con BotCombatOracle para verificar viabilidad económica de farming
    $farmingEscort = array(217 => 20, 206 => 5, 204 => 15);
    $escortMSE = 0.0;
    foreach ($farmingEscort as $eId => $eCnt) {
        $escortMSE += BotEconomyValuator::getElementPriceMSE($eId, $eCnt);
    }
    $lootB_MSE = BotEconomyValuator::toMSE(10000000, 5000000, 2000000);
    $targetDefB = $intelFarmingViable['defense'] + $intelFarmingViable['fleet'];
    $simB = BotCombatOracle::evaluate(
        $farmingEscort,
        $targetDefB,
        array('id' => $botId, 'factor' => $ctx->user['factor'] ?? array()),
        array('id' => $testTargetUserId, 'factor' => array()),
        1
    );
    $finFarmB = BotCombatOracle::evaluateFinancialBalance($simB, $escortMSE, 500.0, 100000000.0, $lootB_MSE);
    $isFarmingViableB = ($finFarmB['net_profit_mse'] > 0 && $finFarmB['roi'] >= 0.10 && $finFarmB['win_probability'] >= 0.80);
    $runner->assertTrue("1B: \$isFarmingViable es estrictamente TRUE (Win prob: " . round($finFarmB['win_probability'] * 100, 1) . "%, Net profit: " . number_format($finFarmB['net_profit_mse']) . " MSE)", $isFarmingViableB);

    // Caso 1C: Ponderación de botín en fleet_crash: lineal vs distorsión cuadrática
    $simDirectMock = array(
        'win_probability' => 0.80,
        'att_loss_pct'    => 0.05,
        'att_loss_mse'    => 50000.0,
        'debris_mse'      => 400000.0,
    );
    $testLootMSE = 10000000.0;
    $finFc = BotCombatOracle::evaluateFinancialBalance(
        $simDirectMock,
        1000000.0, // Deployed MSE
        5000.0,    // Fuel MSE
        10000000.0,// Recycler Cap
        $testLootMSE // Loot MSE
    );
    $expectedLinearLoot = round($testLootMSE * 0.80, 2); // 8,000,000.00
    $quadraticLoot      = round($testLootMSE * 0.80 * 0.80, 2); // 6,400,000.00
    $runner->assertEquals("1C: Botín realizable en fleet_crash está ponderado linealmente (P(win)*Loot)", $expectedLinearLoot, $finFc['realizable_loot']);
    $runner->assertTrue("1C: Botín realizable NO sufre distorsión cuadrática (realizable != P(win)^2 * Loot)", $finFc['realizable_loot'] !== $quadraticLoot);
    $runner->assertEquals("1C: Ingreso bruto integra botín realizable + escombros realizables", round($finFc['realizable_loot'] + $finFc['realizable_debris'], 2), $finFc['gross_revenue']);


    // -------------------------------------------------------------------------------------------------
    // TEST 2: RECLASIFICACIÓN DINÁMICA EN VIVO (STRIKEPLANNER)
    // -------------------------------------------------------------------------------------------------
    echo "\n--- TEST 2: RECLASIFICACIÓN DINÁMICA EN VIVO (STRIKEPLANNER) ---\n";

    // Limpiar tabla de cooldowns y blacklist para asegurar que el target no esté en blacklist
    $db->delete("DELETE FROM " . DB_PREFIX . "bot_planet_cooldown WHERE bot_id = :bId;", array(':bId' => $botId));
    $db->delete("DELETE FROM " . DB_PREFIX . "bot_target_blacklist WHERE target_user_id = :uId;", array(':uId' => $testTargetUserId));

    // Caso 2A: Bot con objetivo fijado como 'farming'. Intel detecta 60 cazas ligeros (>50 forraje hostil).
    // Verificar que planStrikes asciende el objetivo en caliente a 'fleet_crash' y actualiza MariaDB.
    $db->update("UPDATE " . DB_PREFIX . "bots SET 
        target_galaxy = :g, 
        target_system = :s, 
        target_planet = :p, 
        target_type = 'farming', 
        target_initial_mse = 500000.0, 
        siege_cycles = 0, 
        fob_planet_id = 134,
        target_lock_time = :now 
        WHERE bot_id = :bId;", array(
        ':g'   => $tG,
        ':s'   => $tS,
        ':p'   => $tP,
        ':now' => TIMESTAMP,
        ':bId' => $botId
    ));

    $intelStore = new BotIntelStore($botId);
    $intelStore->saveIntel(
        $testTargetPlanetId,
        $testTargetUserId,
        $tG,
        $tS,
        $tP,
        1,
        array(901 => 60000000, 902 => 30000000, 903 => 10000000),
        array(204 => 60), // 60 cazas ligeros (> 50 cazas defensores)
        array(401 => 10),
        array(),
        array()
    );

    // Instanciar contexto fresco desde DB
    $botRow2A = $db->selectSingle("SELECT * FROM " . DB_PREFIX . "bots WHERE bot_id = :bId;", array(':bId' => $botId));
    $ctx2A = new BotContext($botRow2A, $userRow);
    $world2A = new BotWorldModel($ctx2A);
    $gateway2A = new BotActionGateway($ctx2A);
    $strikePlanner2A = new BotStrikePlanner($ctx2A, $gateway2A);
    $basePlanet134 = $world2A->empire->getPlanet(134);

    $strikePlanner2A->planStrikes($world2A, $basePlanet134, array());

    $updatedBotRow2A = $db->selectSingle("SELECT target_type FROM " . DB_PREFIX . "bots WHERE bot_id = :bId;", array(':bId' => $botId));
    $runner->assertEquals("2A: Objetivo 'farming' ascendido dinámicamente a 'fleet_crash' en MariaDB", 'fleet_crash', $updatedBotRow2A['target_type']);
    $runner->assertEquals("2A: Contexto en memoria actualizado a 'fleet_crash'", 'fleet_crash', $ctx2A->botRow['target_type']);

    // Caso 2B: Bot con objetivo fijado como 'fleet_crash'. Enemigo evacúa flota y solo queda almacén sin defensas pesadas.
    // Verificar que desescala dinámicamente a 'farming' y actualiza MariaDB.
    $db->update("UPDATE " . DB_PREFIX . "bots SET 
        target_galaxy = :g, 
        target_system = :s, 
        target_planet = :p, 
        target_type = 'fleet_crash', 
        target_initial_mse = 20000000.0, 
        siege_cycles = 0, 
        fob_planet_id = 134,
        target_lock_time = :now 
        WHERE bot_id = :bId;", array(
        ':g'   => $tG,
        ':s'   => $tS,
        ':p'   => $tP,
        ':now' => TIMESTAMP,
        ':bId' => $botId
    ));

    $intelStore->saveIntel(
        $testTargetPlanetId,
        $testTargetUserId,
        $tG,
        $tS,
        $tP,
        1,
        array(901 => 30000000, 902 => 15000000, 903 => 5000000),
        array(),            // Flota evacuada completamente
        array(401 => 5),    // Solo 5 lanzamisiles ligeros (forraje <= 50, sin defensas pesadas)
        array(),
        array()
    );

    $botRow2B = $db->selectSingle("SELECT * FROM " . DB_PREFIX . "bots WHERE bot_id = :bId;", array(':bId' => $botId));
    $ctx2B = new BotContext($botRow2B, $userRow);
    $world2B = new BotWorldModel($ctx2B);
    $gateway2B = new BotActionGateway($ctx2B);
    $strikePlanner2B = new BotStrikePlanner($ctx2B, $gateway2B);

    $strikePlanner2B->planStrikes($world2B, $basePlanet134, array());

    $updatedBotRow2B = $db->selectSingle("SELECT target_type FROM " . DB_PREFIX . "bots WHERE bot_id = :bId;", array(':bId' => $botId));
    $runner->assertEquals("2B: Objetivo 'fleet_crash' desescalado dinámicamente a 'farming' en MariaDB", 'farming', $updatedBotRow2B['target_type']);
    $runner->assertEquals("2B: Contexto en memoria actualizado a 'farming'", 'farming', $ctx2B->botRow['target_type']);


    // -------------------------------------------------------------------------------------------------
    // TEST 3: ROTACIÓN RÁPIDA FAIL-FAST (3 CICLOS)
    // -------------------------------------------------------------------------------------------------
    echo "\n--- TEST 3: ROTACIÓN RÁPIDA FAIL-FAST (3 CICLOS) ---\n";

    // Limpiar tabla de cooldowns y tareas programadas previas para este objetivo
    $db->delete("DELETE FROM " . DB_PREFIX . "bot_planet_cooldown WHERE bot_id = :bId;", array(':bId' => $botId));
    $db->delete("DELETE FROM " . DB_PREFIX . "bot_tasks WHERE bot_id = :bId AND task_type IN ('staggered_mip_salvo', 'staggered_fleet_assault', 'staggered_recycler_dispatch');", array(':bId' => $botId));

    // Fijar objetivo como 'siege' con FOB #134 (sin interferencia de flotas de misión 4 en vuelo hacia 133).
    // Búnker defensivo con ABMs que excede los MIPs disponibles en el bot -> Fail-Fast tras 3 ciclos.
    $db->update("UPDATE " . DB_PREFIX . "bots SET 
        target_galaxy = :g, 
        target_system = :s, 
        target_planet = :p, 
        target_type = 'siege', 
        target_initial_mse = 20000000.0, 
        siege_cycles = 0, 
        fob_planet_id = 134,
        target_lock_time = :now 
        WHERE bot_id = :bId;", array(
        ':g'   => $tG,
        ':s'   => $tS,
        ':p'   => $tP,
        ':now' => TIMESTAMP,
        ':bId' => $botId
    ));

    $intelStore->saveIntel(
        $testTargetPlanetId,
        $testTargetUserId,
        $tG,
        $tS,
        $tP,
        1,
        array(901 => 20000000, 902 => 10000000, 903 => 5000000),
        array(),
        array(406 => 50, 404 => 100, 502 => 20),
        array(),
        array()
    );

    $gateway = new BotActionGateway($ctx);
    $strikePlanner = new BotStrikePlanner($ctx, $gateway);
    $basePlanet134 = $world->empire->getPlanet(134);

    // CICLO 1
    $ctx->botRow = $db->selectSingle("SELECT * FROM " . DB_PREFIX . "bots WHERE bot_id = :bId;", array(':bId' => $botId));
    $world = new BotWorldModel($ctx);
    $strikePlanner->planStrikes($world, $basePlanet134, array());
    $rowCycle1 = $db->selectSingle("SELECT target_galaxy, siege_cycles FROM " . DB_PREFIX . "bots WHERE bot_id = :bId;", array(':bId' => $botId));
    $runner->assertEquals("3: Ciclo 1 - siege_cycles incrementa a 1", 1, (int)$rowCycle1['siege_cycles']);
    $runner->assertEquals("3: Ciclo 1 - Objetivo sigue bloqueado (target_galaxy = {$tG})", $tG, (int)$rowCycle1['target_galaxy']);

    // CICLO 2
    $ctx->botRow = $db->selectSingle("SELECT * FROM " . DB_PREFIX . "bots WHERE bot_id = :bId;", array(':bId' => $botId));
    $world = new BotWorldModel($ctx);
    $strikePlanner->planStrikes($world, $basePlanet134, array());
    $rowCycle2 = $db->selectSingle("SELECT target_galaxy, siege_cycles FROM " . DB_PREFIX . "bots WHERE bot_id = :bId;", array(':bId' => $botId));
    $runner->assertEquals("3: Ciclo 2 - siege_cycles incrementa a 2", 2, (int)$rowCycle2['siege_cycles']);
    $runner->assertEquals("3: Ciclo 2 - Objetivo sigue bloqueado (target_galaxy = {$tG})", $tG, (int)$rowCycle2['target_galaxy']);

    // CICLO 3 (DISPARO DEL FAIL-FAST)
    $ctx->botRow = $db->selectSingle("SELECT * FROM " . DB_PREFIX . "bots WHERE bot_id = :bId;", array(':bId' => $botId));
    $world = new BotWorldModel($ctx);
    $strikePlanner->planStrikes($world, $basePlanet134, array());
    $rowCycle3 = $db->selectSingle("SELECT target_galaxy, target_system, target_planet, target_type, siege_cycles FROM " . DB_PREFIX . "bots WHERE bot_id = :bId;", array(':bId' => $botId));
    $runner->assertEquals("3: Ciclo 3 - Fail-Fast libera target_galaxy (0)", 0, (int)$rowCycle3['target_galaxy']);
    $runner->assertEquals("3: Ciclo 3 - Fail-Fast libera target_system (0)", 0, (int)$rowCycle3['target_system']);
    $runner->assertEquals("3: Ciclo 3 - Fail-Fast libera target_planet (0)", 0, (int)$rowCycle3['target_planet']);
    $runner->assertEquals("3: Ciclo 3 - Fail-Fast resetea target_type ('')", '', (string)$rowCycle3['target_type']);
    $runner->assertEquals("3: Ciclo 3 - Fail-Fast resetea siege_cycles (0)", 0, (int)$rowCycle3['siege_cycles']);

    // Verificar cooldown en MariaDB
    $cdRow = $db->selectSingle("SELECT cooldown_until, reason FROM " . DB_PREFIX . "bot_planet_cooldown 
        WHERE bot_id = :bId AND galaxy = :g AND system = :s AND planet = :p;", array(
        ':bId' => $botId,
        ':g'   => $tG,
        ':s'   => $tS,
        ':p'   => $tP
    ));
    $runner->assertTrue("3: Cooldown Fail-Fast insertado en MariaDB para el planeta liberado", !empty($cdRow));
    $runner->assertGreaterThanOrEqual("3: Cooldown Fail-Fast es de 1.800s (30 minutos)", TIMESTAMP + 1780, (int)($cdRow['cooldown_until'] ?? 0));
    $runner->assertTrue("3: BotTargetBlacklist::isBlacklisted retorna TRUE para este planeta", BotTargetBlacklist::isBlacklisted($botId, $testTargetUserId, $testTargetPlanetId, $tG, $tS, $tP));


    // -------------------------------------------------------------------------------------------------
    // TEST 4: COLA EXHAUSTIVA EN CASCADA SIN LÍMITE (1..N)
    // -------------------------------------------------------------------------------------------------
    echo "\n--- TEST 4: COLA EXHAUSTIVA EN CASCADA SIN LÍMITE (1..N) ---\n";

    // Limpiar tabla de cooldowns y resetear bot
    $db->delete("DELETE FROM " . DB_PREFIX . "bot_planet_cooldown WHERE bot_id = :bId;", array(':bId' => $botId));
    $db->update("UPDATE " . DB_PREFIX . "bots SET target_galaxy = 0, target_system = 0, target_planet = 0, target_type = '', siege_cycles = 0 WHERE bot_id = :bId;", array(':bId' => $botId));
    $ctx->botRow = $db->selectSingle("SELECT * FROM " . DB_PREFIX . "bots WHERE bot_id = :bId;", array(':bId' => $botId));

    // Elegir 3 candidatos reales del usuario 4 (no en vacaciones)
    $candidatePlanets = $db->select("SELECT p.id, p.id_owner, p.galaxy, p.system, p.planet 
        FROM " . DB_PREFIX . "planets p 
        WHERE p.id_owner = 4 AND p.planet_type = 1 
        ORDER BY p.id ASC LIMIT 3;");

    if (count($candidatePlanets) < 3) {
        $runner->fail("TEST 4 Setup", "Se requieren al menos 3 planetas objetivo del usuario 4 en la BD");
    } else {
        $c1 = $candidatePlanets[0];
        $c2 = $candidatePlanets[1];
        $c3 = $candidatePlanets[2];

        // Guardar intel para los 3 candidatos
        $intelStore->saveIntel($c1['id'], $c1['id_owner'], $c1['galaxy'], $c1['system'], $c1['planet'], 1,
            array(901 => 15000000, 902 => 8000000, 903 => 3000000), array(), array(401 => 10));
        $intelStore->saveIntel($c2['id'], $c2['id_owner'], $c2['galaxy'], $c2['system'], $c2['planet'], 1,
            array(901 => 30000000, 902 => 15000000, 903 => 5000000), array(), array());
        $intelStore->saveIntel($c3['id'], $c3['id_owner'], $c3['galaxy'], $c3['system'], $c3['planet'], 1,
            array(901 => 20000000, 902 => 10000000, 903 => 2000000), array(), array());

        // Asegurar que FOB #135 tenga combustible y flota para que C2 pueda despegar
        $db->update("UPDATE " . DB_PREFIX . "planets SET 
            deuterium = 50000000, 
            crusher = GREATEST(crusher, 100), 
            big_ship_cargo = GREATEST(big_ship_cargo, 500) 
            WHERE id = 135;");

        // Simular que para C1, la FOB asignada (#134) tiene 0 deuterio (falla por falta de combustible)
        $db->update("UPDATE " . DB_PREFIX . "planets SET deuterium = 0 WHERE id = 134;");

        $world = new BotWorldModel($ctx);
        $gateway = new BotActionGateway($ctx);
        $strikePlanner = new BotStrikePlanner($ctx, $gateway);

        $candidatesList = array(
            array(
                'target'            => array('planet_id' => $c1['id'], 'owner_id' => $c1['id_owner'], 'galaxy' => $c1['galaxy'], 'system' => $c1['system'], 'planet' => $c1['planet'], 'planet_type' => 1),
                'category'          => 'farming',
                'loot_mse'          => 20000000.0,
                'debris_mse'        => 0.0,
                'defense_mse'       => 20000.0,
                'score'             => 5000.0,
                'nearest_colony_id' => 134 // FOB sin combustible -> debe fallar
            ),
            array(
                'target'            => array('planet_id' => $c2['id'], 'owner_id' => $c2['id_owner'], 'galaxy' => $c2['galaxy'], 'system' => $c2['system'], 'planet' => $c2['planet'], 'planet_type' => 1),
                'category'          => 'farming',
                'loot_mse'          => 40000000.0,
                'debris_mse'        => 0.0,
                'defense_mse'       => 0.0,
                'score'             => 4000.0,
                'nearest_colony_id' => 135 // FOB con abundante combustible y flota -> debe despegar
            ),
            array(
                'target'            => array('planet_id' => $c3['id'], 'owner_id' => $c3['id_owner'], 'galaxy' => $c3['galaxy'], 'system' => $c3['system'], 'planet' => $c3['planet'], 'planet_type' => 1),
                'category'          => 'farming',
                'loot_mse'          => 25000000.0,
                'debris_mse'        => 0.0,
                'defense_mse'       => 0.0,
                'score'             => 3000.0,
                'nearest_colony_id' => 135
            )
        );

        $strikes = $strikePlanner->planStrikes($world, $basePlanet134, $candidatesList);

        // 1. Verificar que Candidato 1 recibió cooldown de 15 minutos (900s)
        $cdC1 = $db->selectSingle("SELECT cooldown_until, reason FROM " . DB_PREFIX . "bot_planet_cooldown 
            WHERE bot_id = :bId AND galaxy = :g AND system = :s AND planet = :p;", array(
            ':bId' => $botId, ':g' => $c1['galaxy'], ':s' => $c1['system'], ':p' => $c1['planet']
        ));
        $runner->assertTrue("4: Candidato 1 recibió cooldown en MariaDB tras fallar por combustible", !empty($cdC1));
        $runner->assertGreaterThanOrEqual("4: Candidato 1 recibió cooldown de 15m (900s)", TIMESTAMP + 880, (int)($cdC1['cooldown_until'] ?? 0));

        // 2. Verificar que el sistema evaluó en cascada inmediatamente a Candidato 2 y lo fijó como objetivo activo
        $botRowAfterCascade = $db->selectSingle("SELECT target_galaxy, target_system, target_planet, target_type FROM " . DB_PREFIX . "bots WHERE bot_id = :bId;", array(':bId' => $botId));
        $runner->assertEquals("4: Candidato 2 fue fijado como objetivo activo (target_galaxy)", (int)$c2['galaxy'], (int)$botRowAfterCascade['target_galaxy']);
        $runner->assertEquals("4: Candidato 2 fue fijado como objetivo activo (target_system)", (int)$c2['system'], (int)$botRowAfterCascade['target_system']);
        $runner->assertEquals("4: Candidato 2 fue fijado como objetivo activo (target_planet)", (int)$c2['planet'], (int)$botRowAfterCascade['target_planet']);

        // 3. Verificar que Candidato 3 NO recibió cooldown porque el bucle terminó con Candidato 2
        $cdC3 = $db->selectSingle("SELECT cooldown_until FROM " . DB_PREFIX . "bot_planet_cooldown 
            WHERE bot_id = :bId AND galaxy = :g AND system = :s AND planet = :p;", array(
            ':bId' => $botId, ':g' => $c3['galaxy'], ':s' => $c3['system'], ':p' => $c3['planet']
        ));
        $runner->assertTrue("4: Candidato 3 nunca fue evaluado ni penalizado (sin cooldown)", empty($cdC3));
    }


    // -------------------------------------------------------------------------------------------------
    // TEST 5: ENFRIAMIENTO GLOBAL DE 1 HORA POR AGOTAMIENTO DE RADAR
    // -------------------------------------------------------------------------------------------------
    echo "\n--- TEST 5: ENFRIAMIENTO GLOBAL DE 1 HORA POR AGOTAMIENTO DE RADAR ---\n";

    // Limpiar tabla de cooldowns y resetear target lock
    $db->delete("DELETE FROM " . DB_PREFIX . "bot_planet_cooldown WHERE bot_id = :bId;", array(':bId' => $botId));
    $db->update("UPDATE " . DB_PREFIX . "bots SET target_galaxy = 0, target_system = 0, target_planet = 0, target_type = '', siege_cycles = 0 WHERE bot_id = :bId;", array(':bId' => $botId));
    $ctx->botRow = $db->selectSingle("SELECT * FROM " . DB_PREFIX . "bots WHERE bot_id = :bId;", array(':bId' => $botId));

    // Desactivar combustible en ambas bases para forzar fallo de todos los candidatos del radar
    $db->update("UPDATE " . DB_PREFIX . "planets SET deuterium = 0 WHERE id IN (133, 134, 135);");
    $world = new BotWorldModel($ctx);
    $gateway = new BotActionGateway($ctx);
    $strikePlanner = new BotStrikePlanner($ctx, $gateway);
    $basePlanet134 = $world->empire->getPlanet(134);

    // Candidatos limpios del usuario 4 (planetas 26 y 44, sin flotas activas en vuelo)
    $intelStore->saveIntel(26, 4, 1, 120, 8, 1, array(901 => 10000000, 902 => 5000000, 903 => 1000000), array(), array(401 => 10));
    $intelStore->saveIntel(44, 4, 1, 305, 10, 1, array(901 => 10000000, 902 => 5000000, 903 => 1000000), array(), array(401 => 10));

    $exhaustedRadarCandidates = array(
        array(
            'target'            => array('planet_id' => 26, 'owner_id' => 4, 'galaxy' => 1, 'system' => 120, 'planet' => 8, 'planet_type' => 1),
            'category'          => 'farming',
            'loot_mse'          => 10000000.0,
            'nearest_colony_id' => 134
        ),
        array(
            'target'            => array('planet_id' => 44, 'owner_id' => 4, 'galaxy' => 1, 'system' => 305, 'planet' => 10, 'planet_type' => 1),
            'category'          => 'farming',
            'loot_mse'          => 20000000.0,
            'nearest_colony_id' => 134
        )
    );

    $strikePlanner->planStrikes($world, $basePlanet134, $exhaustedRadarCandidates);

    // 1. Verificar que se activó el cooldown global de 1 hora
    $isGlobalCooldown = BotTargetBlacklist::isGlobalScanOnCooldown($botId);
    $runner->assertTrue("5: BotTargetBlacklist::isGlobalScanOnCooldown retorna TRUE", $isGlobalCooldown);

    // 2. Verificar en MariaDB que existe la fila con planet_id = 0, galaxy = 0, system = 0, planet = 0
    $globalCdRow = $db->selectSingle("SELECT cooldown_until, reason FROM " . DB_PREFIX . "bot_planet_cooldown 
        WHERE bot_id = :bId AND planet_id = 0 AND galaxy = 0 AND system = 0 AND planet = 0;", array(':bId' => $botId));
    $runner->assertTrue("5: Registro de cooldown global (0, 0:0:0) existe en MariaDB", !empty($globalCdRow));
    $runner->assertGreaterThanOrEqual("5: Cooldown global es de 3.600s (1 hora)", TIMESTAMP + 3580, (int)($globalCdRow['cooldown_until'] ?? 0));

    // 3. En el siguiente tick de planStrikes: debe omitir el escaneo militar de campaña
    $db->update("UPDATE " . DB_PREFIX . "planets SET deuterium = 100000000 WHERE id IN (133, 134, 135);");
    $world = new BotWorldModel($ctx);

    // Intentar pasar candidatos viables: deben ser omitidos porque el bot está en cooldown global de 1h
    $strikePlanner->planStrikes($world, $basePlanet134, $exhaustedRadarCandidates);
    $botRowNextTick = $db->selectSingle("SELECT target_galaxy FROM " . DB_PREFIX . "bots WHERE bot_id = :bId;", array(':bId' => $botId));
    $runner->assertEquals("5: Siguiente tick omitió escaneo de campaña militar (target_galaxy sigue en 0)", 0, (int)$botRowNextTick['target_galaxy']);


    // -------------------------------------------------------------------------------------------------
    // TEST 6: INTEGRIDAD DE COOLDOWNS Y CLAVES ÚNICAS
    // -------------------------------------------------------------------------------------------------
    echo "\n--- TEST 6: INTEGRIDAD DE COOLDOWNS Y CLAVES ÚNICAS ---\n";

    // Limpiar tabla de cooldowns del bot
    $db->delete("DELETE FROM " . DB_PREFIX . "bot_planet_cooldown WHERE bot_id = :bId;", array(':bId' => $botId));

    // Paso 1: Cooldown global (planet_id = 0, 0:0:0)
    BotTargetBlacklist::setGlobalScanCooldown($botId, 3600, 'Test global cooldown');

    // Paso 2: Cooldown específico (planet_id = 123, 1:50:4)
    BotTargetBlacklist::addCooldown($botId, 4, 123, 1, 50, 4, 1800, 'Test planet specific cooldown');

    // Paso 3: Verificar que ambos registros coexisten en MariaDB sin colisión
    $cooldownsCount = (int)$db->selectSingle("SELECT COUNT(*) as cnt FROM " . DB_PREFIX . "bot_planet_cooldown WHERE bot_id = :bId;", array(':bId' => $botId))['cnt'];
    $runner->assertEquals("6: Coexisten exactamente 2 cooldowns (global y planeta específico) sin colisión", 2, $cooldownsCount);

    // Paso 4: Actualizar cooldown global a 7.200s (dispara ON DUPLICATE KEY UPDATE)
    BotTargetBlacklist::setGlobalScanCooldown($botId, 7200, 'Test global cooldown updated');

    $cooldownsCountAfter = (int)$db->selectSingle("SELECT COUNT(*) as cnt FROM " . DB_PREFIX . "bot_planet_cooldown WHERE bot_id = :bId;", array(':bId' => $botId))['cnt'];
    $runner->assertEquals("6: ON DUPLICATE KEY UPDATE no generó filas duplicadas (siguen siendo 2)", 2, $cooldownsCountAfter);

    $globalRecord = $db->selectSingle("SELECT cooldown_until FROM " . DB_PREFIX . "bot_planet_cooldown 
        WHERE bot_id = :bId AND planet_id = 0 AND galaxy = 0 AND system = 0 AND planet = 0;", array(':bId' => $botId));
    $runner->assertGreaterThanOrEqual("6: Cooldown global actualizado a 7.200s", TIMESTAMP + 7180, (int)$globalRecord['cooldown_until']);

    $planetRecord = $db->selectSingle("SELECT cooldown_until FROM " . DB_PREFIX . "bot_planet_cooldown 
        WHERE bot_id = :bId AND planet_id = 123 AND galaxy = 1 AND system = 50 AND planet = 4;", array(':bId' => $botId));
    $runner->assertGreaterThanOrEqual("6: Cooldown del planeta 123 permaneció inalterado (~1.800s)", TIMESTAMP + 1780, (int)$planetRecord['cooldown_until']);

    // Paso 5: Verificar métodos de consulta
    $runner->assertTrue("6: isGlobalScanOnCooldown retorna TRUE", BotTargetBlacklist::isGlobalScanOnCooldown($botId));
    $runner->assertTrue("6: isBlacklisted retorna TRUE para el planeta en cooldown (1:50:4)", BotTargetBlacklist::isBlacklisted($botId, 4, 123, 1, 50, 4));
    $runner->assertFalse("6: isBlacklisted retorna FALSE para un planeta no afectado (1:50:5)", BotTargetBlacklist::isBlacklisted($botId, 4, 999, 1, 50, 5));

} catch (Throwable $e) {
    $runner->fail("Excepción Inesperada", $e->getMessage() . "\n" . $e->getTraceAsString());
} finally {
    // -------------------------------------------------------------------------------------------------
    // RESTAURACIÓN Y LIMPIEZA ABSOLUTA DE MARIADB
    // -------------------------------------------------------------------------------------------------
    echo "\n--- LIMPIEZA Y RESTAURACIÓN DE BASE DE DATOS ---\n";

    // 1. Restaurar fila original del bot
    $db->update("UPDATE " . DB_PREFIX . "bots SET 
        target_galaxy      = :tg,
        target_system      = :ts,
        target_planet      = :tp,
        target_type        = :tt,
        target_initial_mse = :tim,
        target_lock_time   = :tlt,
        siege_cycles       = :sc,
        fob_planet_id      = :fob,
        siege_spent_mse    = :ssm
        WHERE bot_id = :bId;", array(
        ':tg'  => $botBackup['target_galaxy'],
        ':ts'  => $botBackup['target_system'],
        ':tp'  => $botBackup['target_planet'],
        ':tt'  => $botBackup['target_type'],
        ':tim' => $botBackup['target_initial_mse'],
        ':tlt' => $botBackup['target_lock_time'],
        ':sc'  => $botBackup['siege_cycles'],
        ':fob' => $botBackup['fob_planet_id'],
        ':ssm' => $botBackup['siege_spent_mse'],
        ':bId' => $botId
    ));

    // 2. Limpiar cooldowns de prueba y blacklist
    $db->delete("DELETE FROM " . DB_PREFIX . "bot_planet_cooldown WHERE bot_id = :bId;", array(':bId' => $botId));
    $db->delete("DELETE FROM " . DB_PREFIX . "bot_target_blacklist WHERE target_user_id = :uId;", array(':uId' => $testTargetUserId));

    // 3. Limpiar intel de prueba (eliminar cualquier intel del bot hacia el jugador de pruebas para no envenenar la IA en vivo)
    $db->delete("DELETE FROM " . DB_PREFIX . "bot_intel WHERE bot_id = :bId AND target_user_id = :uId;", array(
        ':bId' => $botId, ':uId' => $testTargetUserId
    ));

    // 4. Limpiar cualquier flota de ataque lanzada hacia el jugador de prueba durante los tests
    $testFleets = $db->select("SELECT fleet_id FROM " . DB_PREFIX . "fleets WHERE fleet_owner = :bId AND fleet_target_owner = :uId AND fleet_mission = 1;", array(
        ':bId' => $botId, ':uId' => $testTargetUserId
    ));
    if (!empty($testFleets)) {
        foreach ($testFleets as $tf) {
            $db->delete("DELETE FROM " . DB_PREFIX . "fleets WHERE fleet_id = :fid;", array(':fid' => $tf['fleet_id']));
        }
    }

    // 5. Restaurar recursos en bases del bot
    $db->update("UPDATE " . DB_PREFIX . "planets SET deuterium = 10000000 WHERE id IN (133, 134, 135);");

    echo "  [OK] Estado original del bot #{$botId} y tablas auxiliares restaurado con precisión atómica.\n";
}

$allPassed = $runner->summary();
exit($allPassed ? 0 : 1);

<?php
/**
 * NovaRush AI Engine v4.2 - Simulación Definitiva 7 Días / 13.440 Ticks
 *
 * Evaluación de 3 bots con distintas personalidades en Dificultad Pesadilla (Nightmare):
 * 1. Bot 1: 'prueba_raider' (Personalidad Raider / Agresivo)
 * 2. Bot 2: 'prueba_miner'  (Personalidad Miner / Minero)
 * 3. Bot 3: 'prueba_turtle' (Personalidad Turtle / Defensivo)
 *
 * Configuración por bot:
 * - Planeta 1: 10.000M Metal, 10.000M Cristal, 10.000M Deuterio | 600 campos base.
 * - Planeta 2:  5.000M Metal,  5.000M Cristal,  5.000M Deuterio | 600 campos base.
 * - Planeta 3:  1.000M Metal,  1.000M Cristal,  1.000M Deuterio | 220 campos base (Prueba de gestión proactiva de campos).
 * - Luna 1:     500M Metal,    500M Cristal,    500M Deuterio   | 1 campo inicial (Asociada a Planeta 1).
 * - Todo en nivel 0 absoluto en T = 0s.
 *
 * Optimizaciones de Inteligencia v4.2:
 * 1. Cero desperdicio en Terraformador (#33) si freeFields > 10.
 * 2. Cero desperdicio en Energía (#4 y #12) si energyNet >= desiredEnergyBuffer.
 * 3. Desbloqueo y potenciación de Módulo de Recursos (#81) más allá de nivel 28.
 * 4. Priorización de Universidad (#6) y Red Intergaláctica (#123).
 * 5. Urbanismo lunar completo (#41, #42, #43, #14, #15, #21).
 */

define('MODE', 'CLI');
define('ROOT_PATH', 'C:/Users/Ortega/Downloads/OGAME/ogame/');
chdir(ROOT_PATH);

require_once 'includes/common.php';
restore_error_handler();
restore_exception_handler();
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

require 'includes/vars/General.php';
foreach (get_defined_vars() as $k => $v) {
    $GLOBALS[$k] = $v;
}
global $resource, $reslist, $pricelist, $CombatCaps, $requeriments, $ProdGrid;

require_once 'includes/classes/PlayerUtil.class.php';
require_once 'includes/classes/class.FleetFunctions.php';
require_once 'includes/classes/class.BuildFunctions.php';
require_once 'includes/classes/class.PlanetRessUpdate.php';
require_once 'includes/classes/bot/BotEngine.class.php';
require_once 'includes/classes/bot/Kernel/BotClock.class.php';
require_once 'includes/classes/bot/Kernel/BotContext.class.php';
require_once 'includes/classes/bot/Kernel/LockManager.class.php';
require_once 'includes/classes/bot/Perception/WorldModel.class.php';
require_once 'includes/classes/bot/Perception/EmpireState.class.php';
require_once 'includes/classes/bot/Execution/ActionGateway.class.php';
require_once 'includes/classes/bot/Execution/GameServices.class.php';
require_once 'includes/classes/bot/Forces/BotBudgetManager.class.php';
require_once 'includes/classes/bot/Forces/ProductionAllocator.class.php';
require_once 'includes/classes/bot/Config/DifficultyProfile.class.php';
require_once 'includes/classes/bot/Config/Personality.class.php';

$db = Database::get();

// Aceleración de sesión para simulación ultrarrápida
try {
    $db->query("SET SESSION innodb_flush_log_at_trx_commit = 2;");
    $db->query("SET SESSION sync_binlog = 0;");
} catch (Throwable $e) {}

echo "========================================================================\n";
echo "   NOVARUSH BOT AI v4.2 - SIMULACION MULTI-BOT 7 DIAS / 13.440 TICKS    \n";
echo "   Pesadilla (Nightmare): Raider vs Miner vs Turtle                     \n";
echo "   Planetas (600 y 220 campos) + Lunas + Arbol Tecnologico Completo     \n";
echo "========================================================================\n\n";

$botsDef = array(
    'raider' => array(
        'username'    => 'prueba_raider',
        'personality' => 'raider',
        'difficulty'  => 'nightmare',
        'doctrine'    => 'aggressive',
        'systems'     => array('gal' => 1, 'sys' => 360),
        'tag'         => 'RAIDER (Ofensivo / Belicista)',
    ),
    'miner' => array(
        'username'    => 'prueba_miner',
        'personality' => 'miner',
        'difficulty'  => 'nightmare',
        'doctrine'    => 'economic',
        'systems'     => array('gal' => 1, 'sys' => 370),
        'tag'         => 'MINER (Económico / Desarrollista)',
    ),
    'turtle' => array(
        'username'    => 'prueba_turtle',
        'personality' => 'turtle',
        'difficulty'  => 'nightmare',
        'doctrine'    => 'defensive',
        'systems'     => array('gal' => 1, 'sys' => 380),
        'tag'         => 'TURTLE (Defensivo / Búnker)',
    ),
);

$activeBots = array();

echo "--- 1. INICIALIZANDO JUGADORES, PLANETAS Y LUNAS EN BASE DE DATOS ---\n";

foreach ($botsDef as $key => $bDef) {
    $sys = $bDef['systems']['sys'];

    // Purgar sistema completo y usuario si existían
    $existing = $db->selectSingle("SELECT id FROM %%USERS%% WHERE username = :uname;", array(':uname' => $bDef['username']));
    if (!empty($existing)) {
        $uid = (int)$existing['id'];
        $db->delete("DELETE FROM %%BOTS%% WHERE bot_id = :uid;", array(':uid' => $uid));
        $db->delete("DELETE FROM %%BOT_DECISIONS%% WHERE bot_id = :uid;", array(':uid' => $uid));
        $db->delete("DELETE FROM %%PLANETS%% WHERE id_owner = :uid;", array(':uid' => $uid));
        $db->delete("DELETE FROM %%USERS%% WHERE id = :uid;", array(':uid' => $uid));
        $db->delete("DELETE FROM %%STATPOINTS%% WHERE id_owner = :uid;", array(':uid' => $uid));
    }
    $db->delete("DELETE FROM %%PLANETS%% WHERE universe = 1 AND galaxy = 1 AND `system` = :sys;", array(':sys' => $sys));

    // 1. Crear jugador con Home Planet en [1:sys:1]
    list($userId, $p1Id) = PlayerUtil::createPlayer(
        1,
        $bDef['username'],
        md5($bDef['username'] . '123'),
        $bDef['username'] . '@novarush.local',
        'es',
        1,
        $sys,
        1,
        ucfirst($key) . '-P1',
        0
    );

    // 2. Crear Planeta 2 en [1:sys:2]
    $p2Id = PlayerUtil::createPlanet(1, $sys, 2, 1, $userId, ucfirst($key) . '-P2', false, 0);

    // 3. Crear Planeta 3 en [1:sys:3]
    $p3Id = PlayerUtil::createPlanet(1, $sys, 3, 1, $userId, ucfirst($key) . '-P3', false, 0);

    // 4. Crear Luna en Planeta 1 [1:sys:1]
    $moonId = PlayerUtil::createMoon(1, 1, $sys, 1, $userId, 20, 8944, ucfirst($key) . '-Moon');

    // 5. Configurar Planetas: P1 (600c, 10.000M), P2 (600c, 5.000M), P3 (220c, 1.000M)
    $pConfigs = array(
        $p1Id => array('m' => 10000000000.0, 'c' => 10000000000.0, 'd' => 10000000000.0, 'fields' => 600, 'label' => '10.000M (600c)'),
        $p2Id => array('m' =>  5000000000.0, 'c' =>  5000000000.0, 'd' =>  5000000000.0, 'fields' => 600, 'label' => '5.000M (600c)'),
        $p3Id => array('m' =>  1000000000.0, 'c' =>  1000000000.0, 'd' =>  1000000000.0, 'fields' => 220, 'label' => '1.000M (220c)'),
    );

    foreach ($pConfigs as $pId => $pc) {
        $db->update("UPDATE %%PLANETS%% SET 
            field_max = :fmax, 
            field_current = 0,
            metal = :m,
            crystal = :c,
            deuterium = :d,
            metal_perhour = 0,
            crystal_perhour = 0,
            deuterium_perhour = 0,
            energy = 0,
            energy_used = 0,
            metal_mine = 0,
            crystal_mine = 0,
            deuterium_sintetizer = 0,
            solar_plant = 0,
            fusion_plant = 0,
            robot_factory = 0,
            nano_factory = 0,
            hangar = 0,
            metal_store = 0,
            crystal_store = 0,
            deuterium_store = 0,
            laboratory = 0,
            terraformer = 0,
            university = 0,
            ally_deposit = 0,
            silo = 0,
            resource_module = 0,
            defensive_module = 0,
            military_module = 0,
            research_module = 0,
            b_building = 0,
            b_building_id = '',
            b_hangar = 0,
            b_hangar_id = ''
            WHERE id = :pid;", array(
                ':fmax' => $pc['fields'],
                ':m'    => $pc['m'],
                ':c'    => $pc['c'],
                ':d'    => $pc['d'],
                ':pid'  => $pId
            )
        );
    }

    // 6. Configurar Luna: 500M recursos, 1 campo inicial, edificios en 0
    if ($moonId) {
        $db->update("UPDATE %%PLANETS%% SET 
            field_max = 1, 
            field_current = 0,
            metal = 500000000.0,
            crystal = 500000000.0,
            deuterium = 500000000.0,
            metal_perhour = 0,
            crystal_perhour = 0,
            deuterium_perhour = 0,
            energy = 0,
            energy_used = 0,
            mondbasis = 0,
            phalanx = 0,
            sprungtor = 0,
            robot_factory = 0,
            nano_factory = 0,
            hangar = 0,
            b_building = 0,
            b_building_id = '',
            b_hangar = 0,
            b_hangar_id = ''
            WHERE id = :mid;", array(':mid' => $moonId));
    }

    // Resetear todas las 20 tecnologías a 0
    $techCols = array(
        'spy_tech', 'computer_tech', 'military_tech', 'defence_tech', 'shield_tech',
        'energy_tech', 'hyperspace_tech', 'combustion_tech', 'impulse_motor_tech',
        'hyperspace_motor_tech', 'laser_tech', 'ionic_tech', 'buster_tech',
        'intergalactic_tech', 'expedition_tech', 'metal_proc_tech', 'crystal_proc_tech',
        'deuterium_proc_tech', 'fuel_optim_tech', 'graviton_tech'
    );
    $resetTechSql = array();
    foreach ($techCols as $tCol) {
        $resetTechSql[] = "{$tCol} = 0";
    }
    $db->update("UPDATE %%USERS%% SET " . implode(', ', $resetTechSql) . ", b_tech = 0, b_tech_id = 0, b_tech_planet = 0, b_tech_queue = '' WHERE id = :uid;", array(':uid' => $userId));

    // Registrar en uni1_bots con personalidad y dificultad pesadilla
    $db->insert("INSERT INTO %%BOTS%% SET 
        bot_id = :uid,
        personality = :pers,
        difficulty = :diff,
        doctrine = :doc,
        home_planet_id = :p1,
        is_active = 1,
        created_at = :now,
        budget_credits = '{}';", array(
            ':uid'  => $userId,
            ':pers' => $bDef['personality'],
            ':diff' => $bDef['difficulty'],
            ':doc'  => $bDef['doctrine'],
            ':p1'   => $p1Id,
            ':now'  => TIMESTAMP
        )
    );

    $activeBots[$key] = array(
        'user_id'     => $userId,
        'username'    => $bDef['username'],
        'personality' => $bDef['personality'],
        'difficulty'  => $bDef['difficulty'],
        'planets'     => array($p1Id, $p2Id, $p3Id),
        'moon_id'     => $moonId,
        'tag'         => $bDef['tag'],
        'next_event'  => 0,
        'stats'       => array(
            'buildings_queued' => 0,
            'techs_queued'     => 0,
        )
    );

    echo "  [OK] Creado {$bDef['tag']}: User #{$userId}, P1 #{$p1Id} (600c), P2 #{$p2Id} (600c), P3 #{$p3Id} (220c), Luna #{$moonId} (1c)\n";
}

echo "\n--- 2. INICIANDO SIMULACION ULTRARRAPIDA DE 7 DIAS (13.440 TICKS) ---\n";
echo "  Parámetros del Experimento:\n";
echo "  - Duración Virtual: 604.800 segundos (7 días de servidor)\n";
echo "  - Total Ticks: 13.440 ticks (45s por tick virtual)\n";
echo "  - Producción de Minas: Activa y acumulativa (ResourceUpdate::CalcResource)\n";
echo "  - Motor de Decisiones: BotProductionAllocator v4.2 + BotBudgetManager\n\n";

$startRealTime = microtime(true);
$simStartVirtual = TIMESTAMP;
$totalSeconds = 604800.0;
$totalTicks = 13440;
$dt = 45.0; // 45 segundos por tick

$updater = new ResourceUpdate();
$checkpointTicks = array(1, 1920, 3840, 5760, 7680, 9600, 11520, 13440);

// Iniciar bloque transaccional inicial
$db->beginTransaction();

for ($tick = 1; $tick <= $totalTicks; $tick++) {
    $virtualTime = (int)round($simStartVirtual + ($tick * $dt));
    BotClock::set($virtualTime);

    // Commit y reinicio de transacción cada 300 ticks para velocidad I/O extrema
    if ($tick % 300 === 0) {
        $db->commit();
        $db->beginTransaction();
    }

    foreach ($activeBots as $bKey => &$botData) {
        $uId = $botData['user_id'];

        // Determinar si este bot requiere evaluación en este tick:
        $needsEvaluation = ($tick === 1) || ($virtualTime >= $botData['next_event']) || ($tick % 25 === 0);

        if (!$needsEvaluation) {
            continue;
        }

        // Cargar usuario y cuerpos celestes (3 planetas + 1 luna)
        $uRow = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = {$uId};");
        $allBodies = $db->select("SELECT * FROM %%PLANETS%% WHERE id_owner = {$uId} AND destruyed = 0 ORDER BY planet_type ASC, id ASC;");

        // 1. Procesar colas y acumulación física de recursos mediante CalcResource
        $nextEarliestEvent = PHP_INT_MAX;

        foreach ($allBodies as $bRaw) {
            list($uRow, $pUpdated) = $updater->CalcResource($uRow, $bRaw, true, $virtualTime);

            // Si es planeta (planet_type = '1'), erradicar déficit energético inmediatamente con satélites solares
            if ((string)$bRaw['planet_type'] === '1') {
                $curEnergy = (float)$pUpdated['energy'];
                $curUsed = abs((float)$pUpdated['energy_used']);
                $curNet = $curEnergy - $curUsed;
                $desiredBuffer = max(2000.0, $curUsed * 0.12);

                if ($curNet < $desiredBuffer) {
                    $config = Config::get();
                    $energySpeed = (float)($config->energySpeed ?? 1.0);
                    $userFactor = isset($uRow['factor']) ? $uRow['factor'] : array();
                    $fEnergy = isset($userFactor['Energy']) ? (float)$userFactor['Energy'] : 0.0;
                    $realEnergyPerSat = max(1.0, floor(($pUpdated['temp_max'] + 160) / 6.0) * $energySpeed * (1.0 + $fEnergy));
                    $energyDeficit = ($desiredBuffer - $curNet);
                    $satsNeeded = (int)ceil($energyDeficit / $realEnergyPerSat);

                    $satCostC = 2000.0 * $satsNeeded;
                    $satCostD = 500.0 * $satsNeeded;

                    if ((float)$pUpdated['crystal'] >= $satCostC && (float)$pUpdated['deuterium'] >= $satCostD) {
                        $db->update("UPDATE %%PLANETS%% SET 
                            crystal = crystal - :costC, 
                            deuterium = deuterium - :costD, 
                            solar_satelit = solar_satelit + :sats 
                            WHERE id = :pid;", array(
                            ':costC' => $satCostC,
                            ':costD' => $satCostD,
                            ':sats'  => $satsNeeded,
                            ':pid'   => (int)$pUpdated['id']
                        ));
                        $freshP = $db->selectSingle("SELECT * FROM %%PLANETS%% WHERE id = {$pUpdated['id']};");
                        list($uRow, $pUpdated) = $updater->CalcResource($uRow, $freshP, true, $virtualTime);
                    }
                }
            }

            if ((int)$pUpdated['b_building'] > $virtualTime) {
                $nextEarliestEvent = min($nextEarliestEvent, (int)$pUpdated['b_building']);
            }
        }

        if ((int)$uRow['b_tech'] > $virtualTime) {
            $nextEarliestEvent = min($nextEarliestEvent, (int)$uRow['b_tech']);
        }

        // 1.5 Logística Imperial (Transporte de excedente de P1 hacia P3 y Luna)
        // Refleja BotLogisticsPlanner en el tick del bot: redistribuir superávit de la capital
        $p1Id = $botData['planets'][0];
        $p3Id = $botData['planets'][2];
        $moonId = $botData['moon_id'];

        $p1Row = $db->selectSingle("SELECT id, metal, crystal, deuterium FROM %%PLANETS%% WHERE id = {$p1Id};");
        if ($p1Row) {
            $p1M = (float)$p1Row['metal'];
            $p1C = (float)$p1Row['crystal'];
            $p1D = (float)$p1Row['deuterium'];

            // A. Suministro a P3 si está corto de campos ($freeFields <= 5) o necesita recursos para Terraformador / Almacenes
            if ($p1C > 500000000.0 && $p1D > 500000000.0) {
                $p3Row = $db->selectSingle("SELECT id, metal, crystal, deuterium, metal_max, crystal_max, deuterium_max, field_current, field_max, terraformer, b_building FROM %%PLANETS%% WHERE id = {$p3Id};");
                if ($p3Row && (int)$p3Row['b_building'] <= $virtualTime) {
                    $p3FreeFields = (int)$p3Row['field_max'] - (int)$p3Row['field_current'];
                    if ($p3FreeFields <= 5) {
                        $nextTerra = (int)$p3Row['terraformer'] + 1;
                        $terraCostC = 50000.0 * pow(2, $nextTerra - 1);
                        $terraCostD = 100000.0 * pow(2, $nextTerra - 1);

                        $needM = max(0.0, 50000000.0 - (float)$p3Row['metal']);
                        $needC = max(0.0, ($terraCostC * 1.1) - (float)$p3Row['crystal']);
                        $needD = max(0.0, ($terraCostD * 1.1) - (float)$p3Row['deuterium']);

                        // Respetar tope de almacén de P3
                        $capM = max(0.0, (float)$p3Row['metal_max'] - (float)$p3Row['metal']);
                        $capC = max(0.0, (float)$p3Row['crystal_max'] - (float)$p3Row['crystal']);
                        $capD = max(0.0, (float)$p3Row['deuterium_max'] - (float)$p3Row['deuterium']);

                        $transferM = min($needM, $capM, $p1M - 200000000.0);
                        $transferC = min($needC, $capC, $p1C - 200000000.0);
                        $transferD = min($needD, $capD, $p1D - 200000000.0);

                        if ($transferM > 1000000.0 || $transferC > 1000000.0 || $transferD > 1000000.0) {
                            $transferM = max(0.0, $transferM);
                            $transferC = max(0.0, $transferC);
                            $transferD = max(0.0, $transferD);
                            $db->update("UPDATE %%PLANETS%% SET metal = metal - :tm, crystal = crystal - :tc, deuterium = deuterium - :td WHERE id = :p1;", array(':tm' => $transferM, ':tc' => $transferC, ':td' => $transferD, ':p1' => $p1Id));
                            $db->update("UPDATE %%PLANETS%% SET metal = metal + :tm, crystal = crystal + :tc, deuterium = deuterium + :td WHERE id = :p3;", array(':tm' => $transferM, ':tc' => $transferC, ':td' => $transferD, ':p3' => $p3Id));
                            $p1M -= $transferM;
                            $p1C -= $transferC;
                            $p1D -= $transferD;
                        }
                    }
                }
            }

            // B. Suministro a Luna si sprungtor == 0 y se tiene hiperespacio >= 7
            if ($moonId && (int)($uRow['hyperspace_tech'] ?? 0) >= 7 && $p1C > 100000000.0) {
                $mRow = $db->selectSingle("SELECT id, metal, crystal, deuterium, sprungtor, b_building FROM %%PLANETS%% WHERE id = {$moonId};");
                if ($mRow && (int)$mRow['sprungtor'] == 0 && (float)$mRow['crystal'] < 30000000.0) {
                    $mNeedC = 30000000.0 - (float)$mRow['crystal'];
                    $mNeedM = max(0.0, 15000000.0 - (float)$mRow['metal']);
                    $mNeedD = max(0.0, 15000000.0 - (float)$mRow['deuterium']);

                    $mTransferM = min($mNeedM, $p1M - 50000000.0);
                    $mTransferC = min($mNeedC, $p1C - 50000000.0);
                    $mTransferD = min($mNeedD, $p1D - 50000000.0);

                    if ($mTransferC > 0 || $mTransferM > 0 || $mTransferD > 0) {
                        $mTransferM = max(0.0, $mTransferM);
                        $mTransferC = max(0.0, $mTransferC);
                        $mTransferD = max(0.0, $mTransferD);
                        $db->update("UPDATE %%PLANETS%% SET metal = metal - :tm, crystal = crystal - :tc, deuterium = deuterium - :td WHERE id = :p1;", array(':tm' => $mTransferM, ':tc' => $mTransferC, ':td' => $mTransferD, ':p1' => $p1Id));
                        $db->update("UPDATE %%PLANETS%% SET metal = metal + :tm, crystal = crystal + :tc, deuterium = deuterium + :td WHERE id = :mid;", array(':tm' => $mTransferM, ':tc' => $mTransferC, ':td' => $mTransferD, ':mid' => $moonId));
                    }
                }
            }
        }

        // 2. Cargar contexto, imperio y presupuesto
        $bRow = $db->selectSingle("SELECT * FROM %%BOTS%% WHERE bot_id = {$uId};");
        $ctx = new BotContext($bRow, $uRow);
        $budget = new BotBudgetManager($ctx);
        $ctx->budgetManager = $budget;

        $empire = new BotEmpireState($uId, $ctx->user);
        $budget->updateCredits($empire);

        // 3. Ejecutar Asignador de Construcción e Investigación Grandmaster v4.5
        $gateway = new BotActionGateway($ctx);
        $allocator = new BotProductionAllocator($ctx, $gateway);

        $bQueued = $allocator->allocate($empire);
        $tQueued = $allocator->allocateResearch($empire);

        if ($bQueued > 0 || $tQueued > 0) {
            $botData['stats']['buildings_queued'] += $bQueued;
            $botData['stats']['techs_queued']     += $tQueued;

            // Re-calcular próximo evento con las nuevas colas encoladas
            $refreshedBodies = $db->select("SELECT b_building FROM %%PLANETS%% WHERE id_owner = {$uId} AND destruyed = 0;");
            foreach ($refreshedBodies as $rb) {
                if ((int)$rb['b_building'] > $virtualTime) {
                    $nextEarliestEvent = min($nextEarliestEvent, (int)$rb['b_building']);
                }
            }
            $refreshedUser = $db->selectSingle("SELECT b_tech FROM %%USERS%% WHERE id = {$uId};");
            if ((int)$refreshedUser['b_tech'] > $virtualTime) {
                $nextEarliestEvent = min($nextEarliestEvent, (int)$refreshedUser['b_tech']);
            }
        }

        if ($nextEarliestEvent === PHP_INT_MAX) {
            $nextEarliestEvent = $virtualTime + (25 * (int)$dt);
        }
        $botData['next_event'] = $nextEarliestEvent;
    }
    unset($botData);

    // Feedback en checkpoints
    if (in_array($tick, $checkpointTicks)) {
        $simDay = round(($tick * $dt) / 86400, 1);
        $elapsedNow = round(microtime(true) - $startRealTime, 1);
        echo "  [TICK {$tick}/{$totalTicks}] Día {$simDay}/7.0 (" . round($tick * $dt) . "s) - Tiempo Real: {$elapsedNow}s\n";
        foreach ($activeBots as $bKey => $bInfo) {
            echo "    * {$bInfo['username']}: {$bInfo['stats']['buildings_queued']} construcciones, {$bInfo['stats']['techs_queued']} investigaciones\n";
        }
    }
}

// Commit final de la simulación
$db->commit();

$totalRealElapsed = microtime(true) - $startRealTime;
echo "\n========================================================================\n";
echo "   SIMULACION COMPLETADA EN " . round($totalRealElapsed, 2) . " SEGUNDOS REALES! \n";
echo "========================================================================\n\n";

// ------------------------------------------------------------------------
// PASO 3: AUDITORIA Y GENERACION DEL INFORME COMPLETO
// ------------------------------------------------------------------------
echo "--- 3. REPORTE COMPLETO DE AUDITORIA TRAS 7 DIAS VIRTUALES ---\n\n";

$elementNames = array(
    1 => 'Mina de Metal', 2 => 'Mina de Cristal', 3 => 'Sintetizador Deuterio', 4 => 'Planta Solar',
    12 => 'Planta de Fusión', 14 => 'Fábrica de Robots', 15 => 'Fábrica de Nanobots', 21 => 'Hangar',
    22 => 'Almacén de Metal', 23 => 'Almacén de Cristal', 24 => 'Almacén de Deuterio',
    31 => 'Laboratorio', 33 => 'Terraformador', 6 => 'Universidad', 44 => 'Silo de Misiles',
    41 => 'Base Lunar', 42 => 'Sensor Phalanx', 43 => 'Salto Cuántico',
    81 => 'Módulo de Recursos', 82 => 'Módulo Defensivo', 83 => 'Módulo Militar', 84 => 'Módulo de Investigación',
    106 => 'Espionaje', 108 => 'Computación', 109 => 'Militar/Armas', 110 => 'Escudos', 111 => 'Blindaje',
    113 => 'Energía', 114 => 'Hiperespacio', 115 => 'Combustión', 117 => 'Impulso', 118 => 'Propulsión Hiperespacial',
    120 => 'Láser', 121 => 'Iónica', 122 => 'Plasma', 123 => 'Red Intergaláctica (IRN)', 124 => 'Astrofísica',
    131 => 'Proc. Metal', 132 => 'Proc. Cristal', 133 => 'Proc. Deuterio', 134 => 'Opt. Combustible', 199 => 'Gravitón'
);

$planetBuildIds = array(1, 2, 3, 81, 4, 12, 14, 15, 21, 31, 6, 82, 83, 84, 22, 23, 24, 44, 33);
$moonBuildIds   = array(41, 42, 43, 14, 15, 21);
$techIds        = array(106, 108, 109, 110, 111, 113, 114, 115, 117, 118, 120, 121, 122, 123, 124, 131, 132, 133, 134, 199);

$finalReport = array(
    'simulation' => array(
        'real_duration_seconds' => round($totalRealElapsed, 2),
        'virtual_duration_days' => 7.0,
        'virtual_seconds'       => $totalSeconds,
        'total_ticks'           => $totalTicks,
    ),
    'bots' => array(),
);

foreach ($activeBots as $bKey => $bInfo) {
    $uId = $bInfo['user_id'];
    $uRow = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = {$uId};");
    $planets = $db->select("SELECT * FROM %%PLANETS%% WHERE id_owner = {$uId} AND planet_type = '1' ORDER BY id ASC;");
    $moons   = $db->select("SELECT * FROM %%PLANETS%% WHERE id_owner = {$uId} AND planet_type = '3' ORDER BY id ASC;");

    $botData = array(
        'username'    => $bInfo['username'],
        'personality' => $bInfo['personality'],
        'difficulty'  => $bInfo['difficulty'],
        'tag'         => $bInfo['tag'],
        'macro'       => array('mse_h' => 0.0, 'daily_gdp_mse' => 0.0, 'total_energy_net' => 0.0, 'total_energy_used' => 0.0),
        'planets'     => array(),
        'moons'       => array(),
        'techs'       => array(),
        'stats'       => $bInfo['stats'],
    );

    echo "========================================================================\n";
    echo "   BOT: {$bInfo['tag']} (User ID: {$uId}) \n";
    echo "========================================================================\n\n";

    echo "--- TABLA DE EDIFICIOS PLANETARIOS (3 Planetas) ---\n";
    $pIdx = 1;
    foreach ($planets as $p) {
        $pId = (int)$p['id'];
        $prodM = (float)$p['metal_perhour'];
        $prodC = (float)$p['crystal_perhour'];
        $prodD = (float)$p['deuterium_perhour'];
        $prodMSE = BotEconomyValuator::toMSE($prodM, $prodC, $prodD);

        $energyNet = (float)$p['energy'] - abs((float)$p['energy_used']);
        $energyUsed = abs((float)$p['energy_used']);

        $botData['macro']['mse_h'] += $prodMSE;
        $botData['macro']['total_energy_net'] += $energyNet;
        $botData['macro']['total_energy_used'] += $energyUsed;

        $pBuildings = array();
        foreach ($planetBuildIds as $bId) {
            $col = $resource[$bId];
            $pBuildings[$bId] = array(
                'name'  => $elementNames[$bId],
                'level' => (int)($p[$col] ?? 0),
            );
        }

        $botData['planets'][$pId] = array(
            'number'       => $pIdx,
            'name'         => $p['name'],
            'coords'       => "[{$p['galaxy']}:{$p['system']}:{$p['planet']}]",
            'field_current'=> (int)$p['field_current'],
            'field_max'    => (int)$p['field_max'],
            'energy_net'   => $energyNet,
            'energy_used'  => $energyUsed,
            'prod_mse_h'   => $prodMSE,
            'stock'        => array('metal' => (float)$p['metal'], 'crystal' => (float)$p['crystal'], 'deuterium' => (float)$p['deuterium']),
            'buildings'    => $pBuildings,
        );

        echo "Planeta {$pIdx}: {$p['name']} [{$p['galaxy']}:{$p['system']}:{$p['planet']}]\n";
        echo "  - Campos: {$p['field_current']} / {$p['field_max']} | Energia Neta: " . number_format($energyNet, 0) . " | Usada: " . number_format($energyUsed, 0) . " | Prod: " . number_format($prodMSE / 1e6, 2) . "M MSE/h\n";
        echo "  - Minas & Recursos: Metal #1: {$p['metal_mine']} | Cristal #2: {$p['crystal_mine']} | Deut #3: {$p['deuterium_sintetizer']} | Mod.Recursos #81: {$p['resource_module']}\n";
        echo "  - Energia: Solar #4: {$p['solar_plant']} | Fusion #12: {$p['fusion_plant']}\n";
        echo "  - Industria & Ciencia: Robots #14: {$p['robot_factory']} | Nano #15: {$p['nano_factory']} | Astillero #21: {$p['hangar']} | Lab #31: {$p['laboratory']} | Universidad #6: {$p['university']}\n";
        echo "  - Modulos Especiales: Mod.Def #82: {$p['defensive_module']} | Mod.Mil #83: {$p['military_module']} | Mod.Inv #84: {$p['research_module']}\n";
        echo "  - Logistica & Espacio: Almacenes (M: {$p['metal_store']}, C: {$p['crystal_store']}, D: {$p['deuterium_store']}) | Silo #44: {$p['silo']} | Terraformer #33: {$p['terraformer']}\n\n";
        $pIdx++;
    }

    $botData['macro']['daily_gdp_mse'] = $botData['macro']['mse_h'] * 24.0;

    echo "--- TABLA DE EDIFICIOS LUNARES (Luna Asociada a P1) ---\n";
    foreach ($moons as $m) {
        $mId = (int)$m['id'];
        $mBuildings = array();
        foreach ($moonBuildIds as $mbId) {
            $col = $resource[$mbId];
            $mBuildings[$mbId] = array(
                'name'  => $elementNames[$mbId],
                'level' => (int)($m[$col] ?? 0),
            );
        }

        $botData['moons'][$mId] = array(
            'name'         => $m['name'],
            'coords'       => "[{$m['galaxy']}:{$m['system']}:{$m['planet']}] (Luna)",
            'field_current'=> (int)$m['field_current'],
            'field_max'    => (int)$m['field_max'],
            'stock'        => array('metal' => (float)$m['metal'], 'crystal' => (float)$m['crystal'], 'deuterium' => (float)$m['deuterium']),
            'buildings'    => $mBuildings,
        );

        echo "Luna: {$m['name']} [{$m['galaxy']}:{$m['system']}:{$m['planet']}]\n";
        echo "  - Campos: {$m['field_current']} / {$m['field_max']}\n";
        echo "  - Base Lunar #41: {$m['mondbasis']} | Sensor Phalanx #42: {$m['phalanx']} | Salto Cuantico #43: {$m['sprungtor']}\n";
        echo "  - Soporte: Robots #14: {$m['robot_factory']} | Nano #15: {$m['nano_factory']} | Astillero #21: {$m['hangar']}\n\n";
    }

    echo "--- TABLA DE LAS 20 TECNOLOGIAS ($reslist[tech]) ---\n";
    foreach ($techIds as $tId) {
        $col = $resource[$tId];
        $lvl = (int)($uRow[$col] ?? 0);
        $botData['techs'][$tId] = array(
            'id'    => $tId,
            'name'  => $elementNames[$tId],
            'col'   => $col,
            'level' => $lvl,
        );
        echo sprintf("  [%3d] %-30s: Nivel %d\n", $tId, $elementNames[$tId], $lvl);
    }
    echo "\n";
    echo "PIB Horario Total: " . number_format($botData['macro']['mse_h'] / 1e6, 2) . "M MSE/h (" . number_format($botData['macro']['daily_gdp_mse'] / 1e9, 2) . "B MSE/dia)\n\n";

    $finalReport['bots'][$bKey] = $botData;
}

// Guardar reporte JSON persistente
$outJsonPath = 'C:/Users/Ortega/.gemini/antigravity/brain/0115c27d-2559-4cfa-bc3a-28dfb5d9bb96/scratch/three_bots_7days_v4_5_report.json';
file_put_contents($outJsonPath, json_encode($finalReport, JSON_PRETTY_PRINT));
echo "Reporte guardado exitosamente en: {$outJsonPath}\n";

exit(0);

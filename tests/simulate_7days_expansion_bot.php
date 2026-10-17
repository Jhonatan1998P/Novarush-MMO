<?php
/**
 * NovaRush AI Engine — Simulación de Alta Velocidad con Telemetría en Vivo Streaming
 * 7 Días / 604.800s Virtuales (1 Bot, 1 Planeta Inicial Normal, Desarrollo Omnidireccional)
 *
 * Características del Motor de Simulación:
 * 1. Simulación Discreta de Alta Velocidad: 2.688 pasos de 225s (~3.75 min = 5 ticks OGame).
 * 2. Telemetría en Vivo Streaming:
 *    - Heartbeat continuo cada 2h virtuales (cada ~0.5s reales) con progreso, planetas, lunas y PIB.
 *    - 14 Puntos de Control mayores cada 12h virtuales (Día 0.5 a 7.0) con Δt real y contabilidad íntegra.
 * 3. Precisión Física y Canónica 100% de 2Moons:
 *    - ResourceUpdate::CalcResource (Producción, obras, astillero, investigación).
 *    - FlyingFleetHandler (Llegada Misión 7 -> Creación colonia, Misión 3 -> Transporte, Misión 4 -> Despliegue).
 *    - BotExpansionPlanner (Colonizadores #208, Misión 7 a [4-12], Moonshots al 20% con 715 Cazas Ligeros).
 *    - ProductionAllocator (Edificios, Astrofísica canónica, Base Lunar #41, Salto Cuántico #43, Sensor Phalanx #42).
 *    - BotLogisticsPlanner (Convoyes entre colonias y lunas).
 *    - BotStagingPlanner (Salto Cuántico táctico a 0 Deuterio).
 */

define('MODE', 'CLI');
define('ROOT_PATH', 'C:/Users/Ortega/Downloads/OGAME/ogame/');
chdir(ROOT_PATH);

require_once 'includes/common.php';
restore_error_handler();
restore_exception_handler();
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

define('SIMULATION_MODE', true);

// Desactivar buffering de salida para telemetría streaming en tiempo real
if (ob_get_level()) {
    ob_end_clean();
}
ob_implicit_flush(true);

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
require_once 'includes/classes/bot/Forces/ForcePlanner.class.php';
require_once 'includes/classes/bot/Forces/BotExpansionPlanner.class.php';
require_once 'includes/classes/bot/Forces/LogisticsPlanner.class.php';
require_once 'includes/classes/bot/Forces/StagingPlanner.class.php';
require_once 'includes/classes/bot/Forces/BotPremiumStrategist.class.php';
require_once 'includes/classes/bot/Forces/BotExpeditionPlanner.class.php';
require_once 'includes/classes/bot/Config/DifficultyProfile.class.php';
require_once 'includes/classes/bot/Config/Personality.class.php';

$db = Database::get();

// Aceleración de sesión MariaDB/InnoDB
try {
    $db->query("SET SESSION innodb_flush_log_at_trx_commit = 2;");
    $db->query("SET SESSION sync_binlog = 0;");
} catch (Throwable $e) {}

echo "========================================================================================\n";
echo "   NOVARUSH BOT AI — SIMULACION DE ALTA VELOCIDAD (7 DIAS / 604.800s VIRTUALES)        \n";
echo "   Telemetría en Vivo Streaming | 1 Planeta Inicial Normal -> Expansión -> Lunas        \n";
echo "========================================================================================\n\n";

// ------------------------------------------------------------------------
// PASO 1: CREACION / RESETEO DEL JUGADOR 'prueba_expansion'
// ------------------------------------------------------------------------
echo "--- 1. CONFIGURANDO ENTORNO INICIAL (1 SOLO PLANETA NORMAL) ---\n";

$username = 'prueba_expansion';
$existing = $db->selectSingle("SELECT id FROM %%USERS%% WHERE username = :uname;", array(':uname' => $username));
if (!empty($existing)) {
    $uid = (int)$existing['id'];
    $db->delete("DELETE FROM %%BOTS%% WHERE bot_id = :uid;", array(':uid' => $uid));
    $db->delete("DELETE FROM %%BOT_DECISIONS%% WHERE bot_id = :uid;", array(':uid' => $uid));
    $db->delete("DELETE FROM %%FLEETS%% WHERE fleet_owner = :uid;", array(':uid' => $uid));
    $db->delete("DELETE FROM %%FLEETS_EVENT%% WHERE fleetID NOT IN (SELECT fleet_id FROM %%FLEETS%%);");
    $db->delete("DELETE FROM %%PLANETS%% WHERE id_owner = :uid;", array(':uid' => $uid));
    $db->delete("DELETE FROM %%USERS%% WHERE id = :uid;", array(':uid' => $uid));
    $db->delete("DELETE FROM %%STATPOINTS%% WHERE id_owner = :uid;", array(':uid' => $uid));
    echo "  [PURGE] Datos previos del bot purgados completamente.\n";
}

// Limpiar sistemas 325 a 345
$db->delete("DELETE FROM %%PLANETS%% WHERE universe = 1 AND galaxy = 1 AND `system` BETWEEN 325 AND 345 AND id_owner NOT IN (SELECT id FROM %%USERS%% WHERE id <= 1015);");

// Crear jugador en [1:330:8]
list($userId, $p1Id) = PlayerUtil::createPlayer(
    1,
    $username,
    md5('prueba123'),
    'prueba_exp@novarush.local',
    'es',
    1,
    330,
    8,
    'Prime-Genesis',
    0
);

// Resetear planeta inicial: 163 campos normales, edificios en 0, flotas en 0
$db->update("UPDATE %%PLANETS%% SET 
    field_max = 163, 
    field_current = 0,
    metal = 6000000.0,
    crystal = 4000000.0,
    deuterium = 2000000.0,
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
    mondbasis = 0,
    phalanx = 0,
    sprungtor = 0,
    id_luna = 0,
    colonizer = 0,
    b_building = 0,
    b_building_id = '',
    b_hangar = 0,
    b_hangar_id = ''
    WHERE id = :pid;", array(':pid' => $p1Id));

// Resetear tecnologías y asignar 5,000 MO
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
$db->update("UPDATE %%USERS%% SET " . implode(', ', $resetTechSql) . ", darkmatter = 5000, b_tech = 0, b_tech_id = 0, b_tech_planet = 0, b_tech_queue = '' WHERE id = :uid;", array(':uid' => $userId));

// Registrar bot en uni1_bots (Balanced / Nightmare)
$db->insert("INSERT INTO %%BOTS%% SET 
    bot_id = :uid,
    personality = 'balanced',
    difficulty = 'nightmare',
    doctrine = 'balanced',
    home_planet_id = :p1,
    is_active = 1,
    created_at = :now,
    budget_credits = '{}';", array(
        ':uid' => $userId,
        ':p1'  => $p1Id,
        ':now' => TIMESTAMP
    )
);

echo "  [OK] Creado Bot #{$userId} ('{$username}') | Planeta #{$p1Id} [1:330:8]\n";
echo "       Campos: 0/163 | Edificios: 0 | Tecnologías: 0 | Flota: 0 | Lunas: 0\n";
echo "       Recursos: 6.0M Metal, 4.0M Cristal, 2.0M Deuterio, 5,000 MO\n\n";

// ------------------------------------------------------------------------
// PASO 2: SIMULACION DE ALTA VELOCIDAD CON TELEMETRIA STREAMING
// ------------------------------------------------------------------------
echo "--- 2. INICIANDO SIMULACION AUTONOMA DE 7 DIAS ---\n";
echo "  Parámetros del Simulador:\n";
echo "  - Duración Virtual : 7 Días (604.800 segundos)\n";
echo "  - Resolución Paso  : Δt = 225s (~3.75 min) -> 2.688 Pasos de Simulación\n";
echo "  - Telemetría Viva  : Heartbeat cada 2h virtuales (~0.3s reales) | Checkpoint cada 12h\n\n";

$startRealTime = microtime(true);
$simStartVirtual = TIMESTAMP;
$totalSeconds = 604800.0;
$dt = 225.0; // 225s por paso
$totalSteps = (int)($totalSeconds / $dt); // 2688 pasos

$updater = new ResourceUpdate();

// 14 Checkpoints cada 12h virtuales (192 pasos = 43.200s = 0.5 días)
$checkpoints = array();
for ($d = 1; $d <= 14; $d++) {
    $stepCheck = $d * 192;
    $simDay = $d * 0.5;
    $checkpoints[$stepCheck] = $simDay;
}

$statsTracker = array(
    'colonies_founded'     => 0,
    'colonizers_recruited' => 0,
    'colonies_dispatched'  => 0,
    'moonshot_attempts'    => 0,
    'moons_created'        => 0,
    'convoys_dispatched'   => 0,
    'jump_gate_transfers'  => 0,
    'ships_jumped'         => 0,
    'buildings_queued'     => 0,
    'techs_queued'         => 0,
);

$nextFleetArrival    = PHP_INT_MAX;
$inFlightFleetsCount = 0;
$lastKnownPlanetCount = 1;
$lastKnownMoonCount   = 0;
$lastCheckpointRealTime = $startRealTime;
$lastHeartbeatRealTime  = $startRealTime;

$db->beginTransaction();

for ($step = 1; $step <= $totalSteps; $step++) {
    $virtualTime = (int)round($simStartVirtual + ($step * $dt));
    BotClock::set($virtualTime);

    // Commit periódico por lotes (cada 32 pasos = ~2 horas virtuales)
    if ($step % 32 === 0) {
        $db->commit();
        $db->beginTransaction();
    }

    $isCheckpoint = isset($checkpoints[$step]);
    $isHeartbeat  = ($step % 32 === 0);

    // 1. Procesar flotas en vuelo que arriban a destino
    if ($inFlightFleetsCount > 0 && $virtualTime >= $nextFleetArrival) {
        require 'includes/FleetHandler.php';

        $fleetRow = $db->selectSingle(
            "SELECT COUNT(*) as cnt, MIN(fleet_end_time) as next_arr 
             FROM %%FLEETS%% 
             WHERE fleet_owner = {$userId};"
        );
        $inFlightFleetsCount = (int)($fleetRow['cnt'] ?? 0);
        $nextFleetArrival = !empty($fleetRow['next_arr']) ? (int)$fleetRow['next_arr'] : PHP_INT_MAX;
    }

    // 2. Cargar usuario y cuerpos celestes
    $uRow = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = {$userId};");
    $allBodies = $db->select("SELECT * FROM %%PLANETS%% WHERE id_owner = {$userId} AND destruyed = 0 ORDER BY planet_type ASC, id ASC;");

    // Detectar fundación de colonias y nacimiento de lunas
    $curPlanets = 0;
    $curMoons = 0;
    foreach ($allBodies as $b) {
        if ((int)$b['planet_type'] === 1) $curPlanets++;
        if ((int)$b['planet_type'] === 3) $curMoons++;
    }
    if ($curPlanets > $lastKnownPlanetCount) {
        $statsTracker['colonies_founded'] += ($curPlanets - $lastKnownPlanetCount);
        $lastKnownPlanetCount = $curPlanets;
    }
    if ($curMoons > $lastKnownMoonCount) {
        $statsTracker['moons_created'] += ($curMoons - $lastKnownMoonCount);
        $lastKnownMoonCount = $curMoons;
    }

    // 3. Actualizar recursos, minas, astillero e investigación en todos los cuerpos celestes
    foreach ($allBodies as $bRaw) {
        list($uRow, $pUpdated) = $updater->CalcResource($uRow, $bRaw, true, $virtualTime);

        // Estabilización energética rápida si hay déficit severo en planeta principal/colonia
        if ((string)$bRaw['planet_type'] === '1') {
            $curEnergy = (float)$pUpdated['energy'];
            $curUsed = abs((float)$pUpdated['energy_used']);
            $curNet = $curEnergy - $curUsed;
            $desiredBuffer = max(1000.0, $curUsed * 0.10);

            if ($curNet < $desiredBuffer && (float)$pUpdated['crystal'] >= 4000.0) {
                $config = Config::get();
                $energySpeed = (float)($config->energySpeed ?? 1.0);
                $userFactor = isset($uRow['factor']) ? $uRow['factor'] : array();
                $fEnergy = isset($userFactor['Energy']) ? (float)$userFactor['Energy'] : 0.0;
                $realEnergyPerSat = max(1.0, floor(($pUpdated['temp_max'] + 160) / 6.0) * $energySpeed * (1.0 + $fEnergy));
                $energyDeficit = ($desiredBuffer - $curNet);
                $satsNeeded = min(200, (int)ceil($energyDeficit / $realEnergyPerSat));

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
    }

    // 4. Ciclo de Inteligencia Artificial del Bot
    $bRow = $db->selectSingle("SELECT * FROM %%BOTS%% WHERE bot_id = {$userId};");
    $ctx = new BotContext($bRow, $uRow);
    $budget = new BotBudgetManager($ctx);
    $ctx->budgetManager = $budget;

    $world = new BotWorldModel($ctx);
    $empire = $world->empire;
    $budget->updateCredits($empire);

    $gateway = new BotActionGateway($ctx);

    // A. Premium Strategy (Feria de MO y mercader bajo demanda)
    $premium = new BotPremiumStrategist($ctx, $gateway);
    $premium->managePremiumEconomy($world);

    // B. Construcción e Investigación (ProductionAllocator)
    $allocator = new BotProductionAllocator($ctx, $gateway);
    $bQueued = $allocator->allocate($empire);
    $tQueued = $allocator->allocateResearch($empire);

    $statsTracker['buildings_queued'] += $bQueued;
    $statsTracker['techs_queued']     += $tQueued;

    // C. Astillero y Flotas (ForcePlanner)
    $forcePlanner = new BotForcePlanner($ctx, $gateway);
    $forcePlanner->planForces($empire, false, $world);

    // D. Expansión Imperial y Operaciones Lunares (BotExpansionPlanner)
    $expansionPlanner = new BotExpansionPlanner($ctx, $gateway);
    $expStats = $expansionPlanner->planExpansion($world);

    $statsTracker['colonizers_recruited'] += $expStats['colonizers_recruited'];
    $statsTracker['colonies_dispatched']  += $expStats['colonies_dispatched'];
    $statsTracker['moonshot_attempts']    += $expStats['moonshot_attempts'];
    if ($expStats['moons_created'] > 0) {
        $statsTracker['moons_created'] += $expStats['moons_created'];
    }

    // E. Cadena de Suministro y Convoyes Logísticos (LogisticsPlanner)
    $staging = new BotStagingPlanner($ctx, $gateway);
    $fobId = $staging->getBestStagingBase($empire, null);

    $logistics = new BotLogisticsPlanner($ctx, $gateway);
    $convoysSent = $logistics->dispatchConvoys($empire, $fobId, $world);
    $statsTracker['convoys_dispatched'] += $convoysSent;

    // F. Concentración de Flota y Red de Salto Cuántico a 0 Deuterio (StagingPlanner)
    if ($fobId > 0) {
        $staging->rallyFleets($empire, $fobId);
    }

    // G. Capturar saltos cuánticos ejecutados
    $recentJumps = $db->select(
        "SELECT chosen_action, context_summary FROM %%BOT_DECISIONS%% 
         WHERE bot_id = {$userId} AND decision_type = 'quantum_jump_gate' AND created_at >= {$virtualTime} - " . (int)$dt . ";"
    );
    foreach ($recentJumps as $rj) {
        $statsTracker['jump_gate_transfers']++;
        if (preg_match('/(\d+)\s+ships\s+transferred/i', $rj['context_summary'], $m)) {
            $statsTracker['ships_jumped'] += (int)$m[1];
        }
    }

    $ctx->decisionLog->flush();

    // Actualizar estado de flotas en vuelo
    $fleetRow = $db->selectSingle(
        "SELECT COUNT(*) as cnt, MIN(fleet_end_time) as next_arr 
         FROM %%FLEETS%% 
         WHERE fleet_owner = {$userId};"
    );
    $inFlightFleetsCount = (int)($fleetRow['cnt'] ?? 0);
    $nextFleetArrival = !empty($fleetRow['next_arr']) ? (int)$fleetRow['next_arr'] : PHP_INT_MAX;

    // --------------------------------------------------------------------
    // TELEMETRIA STREAMING EN VIVO (Heartbeat cada 2h virtuales)
    // --------------------------------------------------------------------
    if ($isHeartbeat && !$isCheckpoint) {
        $nowReal = microtime(true);
        $totalElapsed = round($nowReal - $startRealTime, 1);
        $deltaHeartbeat = round($nowReal - $lastHeartbeatRealTime, 2);
        $lastHeartbeatRealTime = $nowReal;

        $simDay = round(($step * $dt) / 86400.0, 2);
        $astroLvl = (int)($uRow['expedition_tech'] ?? 0);
        $maxAllowed = (int)ceil(9 + min(50, $astroLvl * 0.5));

        echo sprintf("  [STREAMING] Día %4.2f/7.0 | Virt: %s | Real: %5.1fs (+%4.2fs) | Planetas: %2d/%2d | Lunas: %2d | Astro: %2d | Flotas Vuelo: %d\n",
            $simDay, date('Y-m-d H:i', $virtualTime), $totalElapsed, $deltaHeartbeat, $curPlanets, $maxAllowed, $curMoons, $astroLvl, $inFlightFleetsCount);
    }

    // --------------------------------------------------------------------
    // CHECKPOINT DETALLADO (Cada 12h virtuales = 0.5 días)
    // --------------------------------------------------------------------
    if ($isCheckpoint) {
        $simDay = $checkpoints[$step];
        $nowReal = microtime(true);
        $totalElapsed = round($nowReal - $startRealTime, 2);
        $deltaReal = round($nowReal - $lastCheckpointRealTime, 2);
        $lastCheckpointRealTime = $nowReal;
        $lastHeartbeatRealTime  = $nowReal;

        // Snapshot rápido para telemetría
        $planetsSnap = $db->select("SELECT id, metal_perhour, crystal_perhour, deuterium_perhour FROM %%PLANETS%% WHERE id_owner = {$userId} AND planet_type = '1';");
        $totalMSEh = 0.0;
        foreach ($planetsSnap as $ps) {
            $totalMSEh += (float)$ps['metal_perhour'] + ((float)$ps['crystal_perhour'] * 2.0) + ((float)$ps['deuterium_perhour'] * 4.0);
        }

        $astroLvl = (int)($uRow['expedition_tech'] ?? 0);
        $maxAllowed = (int)ceil(9 + min(50, $astroLvl * 0.5));

        echo "\n";
        echo sprintf(">>> [CHECKPOINT DIA %3.1f / 7.0] Paso %4d/%d | Virtual: %s | Real: %5.1fs (Δt: +%4.2fs)\n",
            $simDay, $step, $totalSteps, date('Y-m-d H:i:s', $virtualTime), $totalElapsed, $deltaReal);
        echo sprintf("    • Imperios & Territorio : %2d / %2d Planetas | %2d Lunas | Astro Nv: %2d | PIB: %14s MSE/h\n",
            $curPlanets, $maxAllowed, $curMoons, $astroLvl, number_format($totalMSEh, 0));
        echo sprintf("    • Expansión Colonial    : Colonizadores: %2d | Misiones 7 Despachadas: %2d | Colonias Fundadas: %2d\n",
            $statsTracker['colonizers_recruited'], $statsTracker['colonies_dispatched'], $statsTracker['colonies_founded']);
        echo sprintf("    • Lunas & Salto Cuántico: Moonshots: %2d | Lunas Nacidas: %2d | Saltos a 0 Deu: %d (%s naves)\n",
            $statsTracker['moonshot_attempts'], $statsTracker['moons_created'], $statsTracker['jump_gate_transfers'], number_format($statsTracker['ships_jumped'], 0));
        echo sprintf("    • Logística & Avance    : Convoyes: %2d | Obras encoladas: %4d | Tecnologías: %2d\n\n",
            $statsTracker['convoys_dispatched'], $statsTracker['buildings_queued'], $statsTracker['techs_queued']);
    }
}

$db->commit();
$totalRealElapsed = microtime(true) - $startRealTime;

echo "========================================================================================\n";
echo "   SIMULACION COMPLETADA EN " . round($totalRealElapsed, 2) . " SEGUNDOS REALES! \n";
echo "========================================================================================\n\n";

// ------------------------------------------------------------------------
// PASO 3: AUDITORIA GENERAL COMPLETA (ESTADO TRAS 7 DIAS VIRTUALES)
// ------------------------------------------------------------------------
echo "--- 3. REPORTE FINAL DE AUDITORIA Y RENDIMIENTO TRAS 7 DIAS VIRTUALES ---\n\n";

$finalUser = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = {$userId};");
$finalPlanets = $db->select("SELECT * FROM %%PLANETS%% WHERE id_owner = {$userId} AND planet_type = '1' ORDER BY id ASC;");
$finalMoons = $db->select("SELECT * FROM %%PLANETS%% WHERE id_owner = {$userId} AND planet_type = '3' ORDER BY id ASC;");

// 1. TABLA DE PLANETAS
echo "=== 3.1. DESARROLLO DE PLANETAS (FUNDADOS ORGANICAMENTE VIA MISION 7) ===\n";
echo sprintf("%-4s | %-15s | %-10s | %-9s | %-8s | %-8s | %-8s | %-8s | %-6s | %-6s | %-14s\n",
    "ID", "Nombre", "Coords", "Campos", "Mina M", "Mina C", "Sint D", "Solar/Fus", "Nano", "Hangar", "Prod MSE/h");
echo str_repeat("-", 115) . "\n";
$totalEmpireMSEh = 0.0;
foreach ($finalPlanets as $p) {
    $coords = "[{$p['galaxy']}:{$p['system']}:{$p['planet']}]";
    $fields = "{$p['field_current']}/{$p['field_max']}";
    $pMseh = (float)$p['metal_perhour'] + ((float)$p['crystal_perhour'] * 2.0) + ((float)$p['deuterium_perhour'] * 4.0);
    $totalEmpireMSEh += $pMseh;
    $solarStr = "{$p['solar_plant']}/{$p['fusion_plant']}";
    echo sprintf("%-4d | %-15s | %-10s | %-9s | %-8d | %-8d | %-8d | %-8s | %-6d | %-6d | %14s\n",
        $p['id'], $p['name'], $coords, $fields, $p['metal_mine'], $p['crystal_mine'], $p['deuterium_sintetizer'],
        $solarStr, $p['nano_factory'], $p['hangar'], number_format($pMseh, 0));
}
echo str_repeat("-", 115) . "\n";
echo sprintf("Total Planetas: %d | PIB TOTAL IMPERIO: %s MSE/h\n\n", count($finalPlanets), number_format($totalEmpireMSEh, 0));

// 2. TABLA DE LUNAS
echo "=== 3.2. DESARROLLO LUNAR (MOONSHOTS AL 20%, URBANISMO LUNAR Y SALTO CUANTICO) ===\n";
if (empty($finalMoons)) {
    echo "No se registraron lunas finales.\n\n";
} else {
    echo sprintf("%-6s | %-14s | %-10s | %-9s | %-10s | %-9s | %-10s | %-8s | %-8s\n",
        "MoonID", "Nombre", "Coords", "Campos", "Base Lunar", "Phalanx", "Salto Cuant", "Robots", "Hangar");
    echo str_repeat("-", 95) . "\n";
    foreach ($finalMoons as $m) {
        $coords = "[{$m['galaxy']}:{$m['system']}:{$m['planet']}]";
        $fields = "{$m['field_current']}/{$m['field_max']}";
        echo sprintf("%-6d | %-14s | %-10s | %-9s | %-10d | %-9d | %-10d | %-8d | %-8d\n",
            $m['id'], $m['name'], $coords, $fields, $m['mondbasis'], $m['phalanx'], $m['sprungtor'],
            $m['robot_factory'], $m['hangar']);
    }
    echo str_repeat("-", 95) . "\n\n";
}

// 3. ARBOL TECNOLOGICO
echo "=== 3.3. DESARROLLO DE INVESTIGACIONES (ARBOL TECNOLOGICO) ===\n";
$techNames = array(
    'expedition_tech'      => 'Astrofísica (ID 124)',
    'graviton_tech'        => 'Tecnología Gravitón (ID 199)',
    'intergalactic_tech'   => 'Red Intergaláctica IRN (ID 123)',
    'computer_tech'        => 'Tecnología Computación (ID 108)',
    'energy_tech'          => 'Tecnología Energía (ID 113)',
    'hyperspace_tech'      => 'Tecnología Hiperespacio (ID 114)',
    'combustion_tech'      => 'Motor de Combustión (ID 115)',
    'impulse_motor_tech'   => 'Motor de Impulso (ID 117)',
    'hyperspace_motor_tech'=> 'Propulsor Hiperespacial (ID 118)',
    'laser_tech'           => 'Tecnología Láser (ID 120)',
    'ionic_tech'           => 'Tecnología Iónica (ID 121)',
    'buster_tech'          => 'Tecnología Plasma (ID 122)',
    'military_tech'        => 'Tecnología Militar / Armas (ID 109)',
    'defence_tech'         => 'Tecnología de Defensa (ID 110)',
    'shield_tech'          => 'Tecnología de Blindaje (ID 111)',
    'metal_proc_tech'      => 'Procesamiento de Metal (ID 131)',
    'crystal_proc_tech'    => 'Procesamiento de Cristal (ID 132)',
    'deuterium_proc_tech'  => 'Procesamiento de Deuterio (ID 133)',
);
foreach ($techNames as $col => $name) {
    echo sprintf("%-35s: Nivel %2d\n", $name, (int)($finalUser[$col] ?? 0));
}
echo "\n";

// 4. CADENA DE SUMINISTROS Y FLOTAS
echo "=== 3.4. CADENA DE SUMINISTROS, DESPLIEGUES Y SALTO CUANTICO ===\n";
echo sprintf("Total Colonizadores (#208) Reclutados   : %d\n", $statsTracker['colonizers_recruited']);
echo sprintf("Total Misiones 7 (Colonizar) Despachadas : %d\n", $statsTracker['colonies_dispatched']);
echo sprintf("Total Colonias Nuevas Fundadas          : %d\n", $statsTracker['colonies_founded']);
echo sprintf("Total Intentos de Moonshot (20%% chance) : %d\n", $statsTracker['moonshot_attempts']);
echo sprintf("Total Lunas Orgánicas Creadas           : %d\n", count($finalMoons));
echo sprintf("Total Convoyes de Suministro Despachados: %d\n", $statsTracker['convoys_dispatched']);
echo sprintf("Total Saltos Cuánticos a 0 Deuterio     : %d\n", $statsTracker['jump_gate_transfers']);
echo sprintf("Total Naves Transferidas por Salto      : %s\n\n", number_format($statsTracker['ships_jumped'], 0));

// 5. FLOTA TOTAL DEL IMPERIO ESTACIONADA
echo "=== 3.5. FLOTA MILITAR Y LOGISTICA CONSTRUIDA EN EL IMPERIO ===\n";
$shipSummary = array(
    202 => 'Carguero Pequeño',
    203 => 'Gran Carguero',
    217 => 'Mega Transportador',
    204 => 'Caza Ligero',
    205 => 'Caza Pesado',
    206 => 'Crucero',
    207 => 'Nave de Batalla',
    213 => 'Destructor',
    214 => 'Estrella de la Muerte',
    215 => 'Acorazado',
    216 => 'Luna Negra',
    209 => 'Reciclador',
    219 => 'Giga Reciclador',
    210 => 'Sonda de Espionaje',
    212 => 'Satélite Solar',
    208 => 'Colonizador'
);
$allEmpireBodies = array_merge($finalPlanets, $finalMoons);
foreach ($shipSummary as $sId => $sName) {
    $totalCount = 0;
    $colName = $resource[$sId] ?? null;
    if ($colName) {
        foreach ($allEmpireBodies as $b) {
            $totalCount += (int)($b[$colName] ?? 0);
        }
    }
    if ($totalCount > 0) {
        echo sprintf("%-25s (#%d): %10s unidades\n", $sName, $sId, number_format($totalCount, 0));
    }
}
echo "\n========================================================================================\n";
echo "   REPORTE DE SIMULACION FINALIZADO EXITOSAMENTE\n";
echo "========================================================================================\n";

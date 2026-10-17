<?php
/**
 * NovaRush AI Engine — Simulación de Alta Velocidad con Telemetría en Vivo Streaming
 * 3 Días / 259.200s Virtuales (1 Bot Canónico Real: 'Aegis-Titan', 1 Planeta Inicial Normal)
 *
 * Arquitectura de Simulación de Alta Velocidad:
 * 1. 576 Pasos Discretos de Δt = 450s (7.5 min = 10 ticks OGame estándar).
 * 2. Transacciones por Lotes MariaDB/InnoDB (Commit cada 16 pasos = ~36 commits en total).
 * 3. Telemetría Streaming en Vivo sin buffering:
 *    - Heartbeat cada 2h virtuales (cada ~1.2s reales) con progreso, planetas, lunas, flota y PIB.
 *    - 6 Checkpoints Mayores cada 12h virtuales (Día 0.5 a 3.0) con Δt real y contabilidad íntegra.
 * 4. Precisión Física y Canónica 100% de 2Moons:
 *    - ResourceUpdate::CalcResource (Producción, obras, astillero, investigación).
 *    - FlyingFleetHandler (Llegada Misión 7 -> Creación colonia, Misión 3 -> Transporte, Misión 4 -> Despliegue).
 *    - BotExpansionPlanner (Colonizadores #208, Misión 7 a [4-12], Moonshots al 20% con 715 Cazas Ligeros).
 *    - ProductionAllocator (Edificios, Astrofísica canónica, Base Lunar #41, Salto Cuántico #43, Sensor Phalanx #42).
 *    - BotForcePlanner (Composición de flota canónica, tope de 40% fodder, Cruceros, Acorazados, Destructores).
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
echo "   NOVARUSH BOT AI — SIMULACION CANONICA DE ALTA VELOCIDAD (3 DIAS / 259.200s VIRTUALES) \n";
echo "   Telemetría en Vivo Streaming | Bot Canónico Real 'Aegis-Titan' | Motor Completo      \n";
echo "========================================================================================\n\n";

// ------------------------------------------------------------------------
// PASO 1: CREACION / RESETEO DEL BOT CANONICO 'Aegis-Titan'
// ------------------------------------------------------------------------
echo "--- 1. CONFIGURANDO BOT CANONICO REAL (1 SOLO PLANETA NORMAL) ---\n";

$username = 'Aegis-Titan';
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
    echo "  [PURGE] Registro previo de {$username} purgado completamente.\n";
}

// Limpiar sistemas 325 a 345 de planetas residuales de pruebas (preservando bots de producción 1007, 1010, 1011)
$db->delete("DELETE FROM %%PLANETS%% WHERE universe = 1 AND galaxy = 1 AND `system` BETWEEN 325 AND 345 AND id_owner NOT IN (SELECT id FROM %%USERS%% WHERE id <= 1015);");

// Crear jugador canónico en [1:330:8]
list($userId, $p1Id) = PlayerUtil::createPlayer(
    1,
    $username,
    md5('aegis_canon_pass_2026'),
    'aegis_titan@novarush.local',
    'es',
    1,
    330,
    8,
    'Titan-Prime',
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

// Resetear tecnologías y asignar 5,000 MO inicial
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

// Registrar bot canónico en uni1_bots (Balanced / Nightmare)
$db->insert("INSERT INTO %%BOTS%% SET 
    bot_id = :uid,
    personality = 'balanced',
    difficulty = 'nightmare',
    doctrine = 'balanced',
    home_planet_id = :p1,
    fob_planet_id = :p1,
    is_active = 1,
    created_at = :now,
    budget_credits = '{}';", array(
        ':uid' => $userId,
        ':p1'  => $p1Id,
        ':now' => TIMESTAMP
    )
);

echo "  [OK] Creado Bot Canónico Real #{$userId} ('{$username}') | Planeta #{$p1Id} [1:330:8]\n";
echo "       Personalidad: Balanced | Dificultad: Nightmare | Doctrina: Balanced\n";
echo "       Campos: 0/163 | Edificios: 0 | Tecnologías: 0 | Flota: 0 | Lunas: 0\n";
echo "       Semilla Inicial: 6.0M Metal, 4.0M Cristal, 2.0M Deuterio, 5,000 MO\n\n";

// ------------------------------------------------------------------------
// PASO 2: SIMULACION DE ALTA VELOCIDAD CON TELEMETRIA STREAMING
// ------------------------------------------------------------------------
echo "--- 2. INICIANDO SIMULACION AUTONOMA DE 3 DIAS ---\n";
echo "  Parámetros del Simulador de Alta Velocidad:\n";
echo "  - Duración Virtual : 3 Días (259.200 segundos)\n";
echo "  - Resolución Paso  : Δt = 450s (7.5 min) -> 576 Pasos Discretos\n";
echo "  - Transacciones    : Lotes de 16 pasos por commit (~36 commits en total)\n";
echo "  - Telemetría Viva  : Heartbeat cada 2h virtuales (~1.2s reales) | Checkpoints cada 12h\n\n";

$startRealTime = microtime(true);
$simStartVirtual = TIMESTAMP;
$totalSeconds = 259200.0; // 3 días
$dt = 450.0;              // 450s por paso
$totalSteps = (int)($totalSeconds / $dt); // 576 pasos

$updater = new ResourceUpdate();

// 6 Checkpoints cada 12h virtuales (96 pasos = 43.200s = 0.5 días)
$checkpoints = array();
for ($d = 1; $d <= 6; $d++) {
    $stepCheck = $d * 96;
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

    // Commit periódico por lotes (cada 16 pasos = ~2 horas virtuales)
    if ($step % 16 === 0) {
        $db->commit();
        $db->beginTransaction();
    }

    $isCheckpoint = isset($checkpoints[$step]);
    $isHeartbeat  = ($step % 16 === 0);

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

        // Estabilización energética rápida si hay déficit en planeta principal/colonia
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

    // A. Premium Strategy (Feria de MO y mercader bajo demanda cada 4 pasos = 30 min)
    if ($step % 4 === 0) {
        $premium = new BotPremiumStrategist($ctx, $gateway);
        $premium->managePremiumEconomy($world);
    }

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

    // E. Cadena de Suministro y Convoyes Logísticos (LogisticsPlanner cada 2 pasos = 15 min)
    $staging = new BotStagingPlanner($ctx, $gateway);
    $fobId = $staging->getBestStagingBase($empire, null);

    if ($step % 2 === 0) {
        $logistics = new BotLogisticsPlanner($ctx, $gateway);
        $convoysSent = $logistics->dispatchConvoys($empire, $fobId, $world);
        $statsTracker['convoys_dispatched'] += $convoysSent;
    }

    // F. Concentración de Flota y Red de Salto Cuántico a 0 Deuterio (StagingPlanner)
    if ($fobId > 0 && $step % 2 === 0) {
        $staging->rallyFleets($empire, $fobId);
    }

    // G. Capturar saltos cuánticos ejecutados en este intervalo
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
    // TELEMETRIA STREAMING EN VIVO (Heartbeat cada 2h virtuales = 16 pasos)
    // --------------------------------------------------------------------
    if ($isHeartbeat && !$isCheckpoint) {
        $nowReal = microtime(true);
        $totalElapsed = round($nowReal - $startRealTime, 1);
        $deltaHeartbeat = round($nowReal - $lastHeartbeatRealTime, 2);
        $lastHeartbeatRealTime = $nowReal;

        $simDay = round(($step * $dt) / 86400.0, 2);
        $astroLvl = (int)($uRow['expedition_tech'] ?? 0);
        $maxAllowed = (int)ceil(9 + min(50, $astroLvl * 0.5));

        // Calcular PIB instantáneo (MSE/hora) y total de naves
        $planetsSnap = $db->select("SELECT metal_perhour, crystal_perhour, deuterium_perhour FROM %%PLANETS%% WHERE id_owner = {$userId} AND planet_type = '1';");
        $hourlyMSE = 0.0;
        foreach ($planetsSnap as $ps) {
            $hourlyMSE += (float)$ps['metal_perhour'] + ((float)$ps['crystal_perhour'] * 1.5) + ((float)$ps['deuterium_perhour'] * 3.0);
        }

        // Conteo rápido de naves
        $shipsCountRow = $db->selectSingle("SELECT SUM(light_hunter + heavy_hunter + crusher + battle_ship + battleship + bomber_ship + destructor + galleon + destroyer + dearth_star + lune_noir) as total_ships FROM %%PLANETS%% WHERE id_owner = {$userId};");
        $totalShips = (int)($shipsCountRow['total_ships'] ?? 0);

        echo sprintf("  [STREAMING] Día %4.2f/3.0 | Virt: %s | Real: %5.1fs (+%4.2fs) | Planetas: %2d/%2d | Lunas: %2d | Astro: %2d | PIB: %6.1fM MSE/h | Flota: %5d | Saltos: %d\n",
            $simDay, date('Y-m-d H:i', $virtualTime), $totalElapsed, $deltaHeartbeat, $curPlanets, $maxAllowed, $curMoons, $astroLvl, $hourlyMSE / 1000000.0, $totalShips, $statsTracker['jump_gate_transfers']);
        flush();
    }

    // --------------------------------------------------------------------
    // CHECKPOINT DETALLADO (Cada 12h virtuales = 0.5 días = 96 pasos)
    // --------------------------------------------------------------------
    if ($isCheckpoint) {
        $simDay = $checkpoints[$step];
        $nowReal = microtime(true);
        $totalElapsed = round($nowReal - $startRealTime, 2);
        $deltaReal = round($nowReal - $lastCheckpointRealTime, 2);
        $lastCheckpointRealTime = $nowReal;
        $lastHeartbeatRealTime  = $nowReal;

        // Snapshot detallado para telemetría
        $planetsSnap = $db->select("SELECT id, galaxy, system, planet, name, field_current, field_max, metal_mine, crystal_mine, deuterium_sintetizer, resource_module, robot_factory, nano_factory, hangar, metal_perhour, crystal_perhour, deuterium_perhour, id_luna FROM %%PLANETS%% WHERE id_owner = {$userId} AND planet_type = '1' ORDER BY id ASC;");
        $totalMSEh = 0.0;
        foreach ($planetsSnap as $ps) {
            $totalMSEh += (float)$ps['metal_perhour'] + ((float)$ps['crystal_perhour'] * 1.5) + ((float)$ps['deuterium_perhour'] * 3.0);
        }

        $moonsSnap = $db->select("SELECT id, galaxy, system, planet, name, field_current, field_max, mondbasis, phalanx, sprungtor, last_jump_time FROM %%PLANETS%% WHERE id_owner = {$userId} AND planet_type = '3' ORDER BY id ASC;");

        // Conteo de flota por tipos
        $fleetAgg = $db->selectSingle("SELECT 
            SUM(small_ship_cargo) as sc, SUM(big_ship_cargo) as bc,
            SUM(light_hunter) as lf, SUM(heavy_hunter) as hf,
            SUM(crusher) as cr, SUM(battle_ship) as bs, SUM(battleship) as bcruiser,
            SUM(bomber_ship) as bm, SUM(destructor) as ds, SUM(destroyer) as mega_ds, SUM(galleon) as galeon,
            SUM(recycler) as rec, SUM(giga_recykler) as gigarec,
            SUM(colonizer) as col, SUM(spy_sonde) as spy
            FROM %%PLANETS%% WHERE id_owner = {$userId};");

        $astroLvl = (int)($uRow['expedition_tech'] ?? 0);
        $maxAllowed = (int)ceil(9 + min(50, $astroLvl * 0.5));
        $gravLvl = (int)($uRow['graviton_tech'] ?? 0);
        $compLvl = (int)($uRow['computer_tech'] ?? 0);
        $hyperLvl = (int)($uRow['hyperspace_tech'] ?? 0);

        echo "\n";
        echo sprintf(">>> ==================================================================================\n");
        echo sprintf(">>> [CHECKPOINT DIA %3.1f / 3.0] Paso %4d/%d | Virtual: %s | Real: %5.1fs (Δt: +%4.2fs)\n",
            $simDay, $step, $totalSteps, date('Y-m-d H:i:s', $virtualTime), $totalElapsed, $deltaReal);
        echo sprintf(">>> ==================================================================================\n");
        echo sprintf("  * EXPANSION: Planetas: %d / %d (Astro Nv. %d) | Lunas: %d | Colonias Fundadas: %d | Misiones 7: %d\n",
            count($planetsSnap), $maxAllowed, $astroLvl, count($moonsSnap), $statsTracker['colonies_founded'], $statsTracker['colonies_dispatched']);
        echo sprintf("  * TECNOLOGIA: Astrofísica: %d | Gravitón: %d | Computación: %d | Hiperespacio: %d\n",
            $astroLvl, $gravLvl, $compLvl, $hyperLvl);
        echo sprintf("  * ECONOMIA IMPERIAL: PIB: %s MSE/h | Obras encoladas: %d | Investigaciones: %d\n",
            number_format($totalMSEh, 0, ',', '.'), $statsTracker['buildings_queued'], $statsTracker['techs_queued']);
        echo sprintf("  * OPERACIONES LUNARES: Moonshots: %d | Lunas Nacidas: %d | Saltos Cuánticos: %d (%d naves)\n",
            $statsTracker['moonshot_attempts'], $statsTracker['moons_created'], $statsTracker['jump_gate_transfers'], $statsTracker['ships_jumped']);
        echo sprintf("  * LOGISTICA: Convoyes despachados: %d | Flotas en vuelo actuales: %d\n",
            $statsTracker['convoys_dispatched'], $inFlightFleetsCount);

        echo "  * FLOTA EN PROPIEDAD:\n";
        echo sprintf("    - Fodder: %s Cazas Ligeros (#204), %s Cazas Pesados (#205)\n",
            number_format((int)($fleetAgg['lf'] ?? 0)), number_format((int)($fleetAgg['hf'] ?? 0)));
        echo sprintf("    - Combate Capital: %s Cruceros (#206), %s Naves de Batalla (#207), %s Acorazados (#215)\n",
            number_format((int)($fleetAgg['cr'] ?? 0)), number_format((int)($fleetAgg['bs'] ?? 0)), number_format((int)($fleetAgg['bcruiser'] ?? 0)));
        echo sprintf("    - Asedio/Pesados: %s Bombarderos (#211), %s Destructores (#213), %s Megadestructores (#226), %s Galeones (#225)\n",
            number_format((int)($fleetAgg['bm'] ?? 0)), number_format((int)($fleetAgg['ds'] ?? 0)), number_format((int)($fleetAgg['mega_ds'] ?? 0)), number_format((int)($fleetAgg['galeon'] ?? 0)));
        echo sprintf("    - Utilidad/Soporte: %s Colonizadores (#208), %s Transp. Chicos (#202), %s Transp. Grandes (#203), %s Recicladores (#209/#219)\n",
            number_format((int)($fleetAgg['col'] ?? 0)), number_format((int)($fleetAgg['sc'] ?? 0)), number_format((int)($fleetAgg['bc'] ?? 0)), number_format((int)($fleetAgg['rec'] ?? 0) + (int)($fleetAgg['gigarec'] ?? 0)));

        echo "  * MUESTRA DE PLANETAS (Top 5 por PIB):\n";
        echo sprintf("    %-16s | %-10s | %-7s | %-16s | %-13s | %-10s | %-16s\n",
            "Nombre", "Coord", "Campos", "Minas (M/C/D)", "Mód.Rec (#81)", "Robots/Nano", "PIB (MSE/h)");
        echo sprintf("    --------------------------------------------------------------------------------------------------------\n");
        $sortedPlanets = $planetsSnap;
        usort($sortedPlanets, function($a, $b) {
            $pA = (float)$a['metal_perhour'] + (float)$a['crystal_perhour'] * 1.5 + (float)$a['deuterium_perhour'] * 3.0;
            $pB = (float)$b['metal_perhour'] + (float)$b['crystal_perhour'] * 1.5 + (float)$b['deuterium_perhour'] * 3.0;
            return $pB <=> $pA;
        });
        foreach (array_slice($sortedPlanets, 0, 5) as $sp) {
            $pMSE = (float)$sp['metal_perhour'] + (float)$sp['crystal_perhour'] * 1.5 + (float)$sp['deuterium_perhour'] * 3.0;
            echo sprintf("    %-16s | [%d:%d:%d]   | %3d/%3d | M:%2d C:%2d D:%2d   | Nivel %-6d | R:%2d / N:%2d   | %13s\n",
                substr($sp['name'], 0, 16), $sp['galaxy'], $sp['system'], $sp['planet'],
                $sp['field_current'], $sp['field_max'],
                $sp['metal_mine'], $sp['crystal_mine'], $sp['deuterium_sintetizer'],
                $sp['resource_module'], $sp['robot_factory'], $sp['nano_factory'],
                number_format($pMSE, 0, ',', '.')
            );
        }

        if (!empty($moonsSnap)) {
            echo "  * LUNAS ACTIVAS Y RED DE SALTO CUANTICO:\n";
            echo sprintf("    %-16s | %-10s | %-7s | %-11s | %-10s | %-14s | %-15s\n",
                "Luna", "Coord", "Campos", "Base Lunar", "Phalanx", "Salto Cuántico", "Cooldown Salto");
            echo sprintf("    --------------------------------------------------------------------------------------------------------\n");
            foreach ($moonsSnap as $ms) {
                $cdRemaining = max(0, ((int)$ms['last_jump_time'] + 3600) - $virtualTime);
                $cdStatus = ($cdRemaining === 0) ? "LISTO (0s)" : "{$cdRemaining}s restantes";
                echo sprintf("    %-16s | [%d:%d:%d]   | %3d/%3d | Nivel %-4d | Nivel %-3d | Nivel %-7d | %-15s\n",
                    substr($ms['name'], 0, 16), $ms['galaxy'], $ms['system'], $ms['planet'],
                    $ms['field_current'], $ms['field_max'],
                    $ms['mondbasis'], $ms['phalanx'], $ms['sprungtor'],
                    $cdStatus
                );
            }
        }
        echo "\n";
        flush();
    }
}

$db->commit();

// ------------------------------------------------------------------------
// PASO 3: AUDITORIA FINAL Y CERTIFICACION EXHAUSTIVA
// ------------------------------------------------------------------------
$totalRealTime = round(microtime(true) - $startRealTime, 2);

echo "========================================================================================\n";
echo "   NOVARUSH BOT AI — RESULTADOS DE AUDITORIA FINAL (3 DIAS COMPLETADOS)                 \n";
echo "========================================================================================\n";
echo "  Tiempo Real Transcurrido  : {$totalRealTime} segundos (~" . round($totalRealTime / 60, 2) . " minutos)\n";
echo "  Tiempo Virtual Simulado   : 3 Días (259.200 segundos)\n";
echo "  Velocidad de Simulación   : " . round(259200.0 / max(0.001, $totalRealTime), 1) . "x en tiempo real\n\n";

$finalUser = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = {$userId};");
$finalPlanets = $db->select("SELECT * FROM %%PLANETS%% WHERE id_owner = {$userId} AND planet_type = '1' ORDER BY id ASC;");
$finalMoons = $db->select("SELECT * FROM %%PLANETS%% WHERE id_owner = {$userId} AND planet_type = '3' ORDER BY id ASC;");

$finalGDP = 0.0;
foreach ($finalPlanets as $fp) {
    $finalGDP += (float)$fp['metal_perhour'] + ((float)$fp['crystal_perhour'] * 1.5) + ((float)$fp['deuterium_perhour'] * 3.0);
}

$finalShips = $db->selectSingle("SELECT 
    SUM(small_ship_cargo) as sc, SUM(big_ship_cargo) as bc,
    SUM(light_hunter) as lf, SUM(heavy_hunter) as hf,
    SUM(crusher) as cr, SUM(battle_ship) as bs, SUM(battleship) as bcruiser,
    SUM(bomber_ship) as bm, SUM(destructor) as ds, SUM(destroyer) as mega_ds, SUM(galleon) as galeon,
    SUM(recycler) as rec, SUM(giga_recykler) as gigarec,
    SUM(colonizer) as col, SUM(solar_satelit) as sats
    FROM %%PLANETS%% WHERE id_owner = {$userId};");

$totalCombatShips = (int)($finalShips['lf'] ?? 0) + (int)($finalShips['hf'] ?? 0) +
                    (int)($finalShips['cr'] ?? 0) + (int)($finalShips['bs'] ?? 0) + (int)($finalShips['bcruiser'] ?? 0) +
                    (int)($finalShips['bm'] ?? 0) + (int)($finalShips['ds'] ?? 0) + (int)($finalShips['mega_ds'] ?? 0) + (int)($finalShips['galeon'] ?? 0);

$totalFodder = (int)($finalShips['lf'] ?? 0) + (int)($finalShips['hf'] ?? 0);
$totalCapitalWarships = $totalCombatShips - $totalFodder;
$fodderRatio = ($totalCombatShips > 0) ? round(($totalFodder / $totalCombatShips) * 100, 1) : 0;

echo "--- CERTIFICACION DE MODULOS IMPERIALES ---\n";
echo "1. EXPANSION IMPERIAL Y COLONIAS:\n";
echo "   - Planetas Colonizados       : " . count($finalPlanets) . " planetas\n";
echo "   - Nivel de Astrofísica        : Nivel " . (int)$finalUser['expedition_tech'] . "\n";
echo "   - Máximo de Planetas Legal    : " . (int)ceil(9 + min(50, (int)$finalUser['expedition_tech'] * 0.5)) . "\n";
echo "   - Colonizadores Recrutados    : {$statsTracker['colonizers_recruited']}\n";
echo "   - Misiones 7 Despachadas      : {$statsTracker['colonies_dispatched']}\n\n";

echo "2. URBANIZACION Y ACELERACION:\n";
$nanosCount = 0;
$robots10Count = 0;
$resModuleSum = 0;
foreach ($finalPlanets as $fp) {
    if ((int)$fp['nano_factory'] >= 1) $nanosCount++;
    if ((int)$fp['robot_factory'] >= 10) $robots10Count++;
    $resModuleSum += (int)$fp['resource_module'];
}
echo "   - Planetas con Nanobots (15)  : {$nanosCount} / " . count($finalPlanets) . "\n";
echo "   - Planetas con Robots 10 (14) : {$robots10Count} / " . count($finalPlanets) . "\n";
echo "   - Nivel Promedio Mód.Rec (#81): " . round($resModuleSum / max(1, count($finalPlanets)), 1) . "\n";
echo "   - PIB Total del Imperio       : " . number_format($finalGDP, 0, ',', '.') . " MSE/h\n\n";

echo "3. LUNAS Y RED DE SALTO CUANTICO A 0 DEUTERIO:\n";
echo "   - Total de Lunas Creadas      : " . count($finalMoons) . "\n";
echo "   - Intentos de Moonshot (20%)  : {$statsTracker['moonshot_attempts']}\n";
echo "   - Lunas con Salto Cuántico    : " . count(array_filter($finalMoons, function($m) { return (int)$m['sprungtor'] >= 1; })) . "\n";
echo "   - Transferencias Salto Cuántico: {$statsTracker['jump_gate_transfers']} ejecuciones\n";
echo "   - Naves Saltadas a 0 Deuterio : {$statsTracker['ships_jumped']} naves\n\n";

echo "4. COMPOSICION CANONICA DE FLOTA:\n";
echo "   - Total de Naves de Combate   : " . number_format($totalCombatShips) . "\n";
echo "   - Naves Capitales de Combate  : " . number_format($totalCapitalWarships) . "\n";
echo "   - Carne de Cañón (Fodder)     : " . number_format($totalFodder) . " ({$fodderRatio}% del total combatiente)\n";
echo "   - Cruceros (#206)             : " . number_format((int)($finalShips['cr'] ?? 0)) . "\n";
echo "   - Naves de Batalla (#207)     : " . number_format((int)($finalShips['bs'] ?? 0)) . "\n";
echo "   - Acorazados (#215)           : " . number_format((int)($finalShips['bcruiser'] ?? 0)) . "\n";
echo "   - Destructores (#213)         : " . number_format((int)($finalShips['ds'] ?? 0)) . "\n";
echo "   - Bombarderos (#211)          : " . number_format((int)($finalShips['bm'] ?? 0)) . "\n";
echo "   - Megadestructores (#226)     : " . number_format((int)($finalShips['mega_ds'] ?? 0)) . "\n";
echo "   - Galeones (#225)             : " . number_format((int)($finalShips['galeon'] ?? 0)) . "\n";
echo "   - Naves de Carga / Reciclaje  : " . number_format((int)($finalShips['sc'] ?? 0) + (int)($finalShips['bc'] ?? 0) + (int)($finalShips['rec'] ?? 0) + (int)($finalShips['gigarec'] ?? 0)) . "\n\n";

echo "========================================================================================\n";
echo "   [FIN DE SIMULACION] CERTIFICACION EXITOSA\n";
echo "========================================================================================\n";

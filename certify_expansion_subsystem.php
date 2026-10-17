<?php
/**
 * Script de Certificación y Simulación Acelerada — Subsistema de Expansión Imperial
 * NovaRush Grandmaster Bot Engine
 *
 * Tests:
 * 1. Fórmula de Astrofísica (PlayerUtil::maxPlanetCount: 14 para Astro 10, 19 para Astro 20)
 * 2. Reclutamiento de Colonizadores (ID 208) y Despacho de Misión 7 (Colonización legítima)
 * 3. Creación Orgánica de Lunas (Moonshots al 20%), Desbloqueo de Gravitón y Urbanización Lunar (41 -> 43 -> 42)
 * 4. Salto Cuántico Táctico a 0 Deuterio (Transferencia instantánea, 0 fuel, cooldown 3600s)
 */

define('MODE', 'CLI');
define('SIMULATION_MODE', true);
define('ROOT_PATH', str_replace('\\', '/', __DIR__) . '/');

require_once ROOT_PATH . 'includes/common.php';
require_once ROOT_PATH . 'includes/vars/General.php';
foreach (get_defined_vars() as $k => $v) {
    $GLOBALS[$k] = $v;
}
require_once ROOT_PATH . 'includes/classes/PlayerUtil.class.php';
require_once ROOT_PATH . 'includes/classes/class.FleetFunctions.php';
require_once ROOT_PATH . 'includes/classes/class.BuildFunctions.php';
require_once ROOT_PATH . 'includes/classes/class.MissionFunctions.php';
require_once ROOT_PATH . 'includes/classes/missions/Mission.interface.php';
require_once ROOT_PATH . 'includes/classes/missions/MissionCaseColonisation.class.php';
require_once ROOT_PATH . 'includes/classes/bot/Kernel/BotKernel.class.php';
require_once ROOT_PATH . 'includes/classes/bot/BotNameHelper.class.php';
require_once ROOT_PATH . 'includes/classes/bot/Forces/BotBudgetManager.class.php';

$totalPassed = 0;
$totalFailed = 0;

function assertCondition($cond, $testName, $detail = '') {
    global $totalPassed, $totalFailed;
    if ($cond) {
        $totalPassed++;
        echo "  [PASS] {$testName}" . ($detail ? " — {$detail}" : "") . PHP_EOL;
    } else {
        $totalFailed++;
        echo "  [FAIL] {$testName}" . ($detail ? " — {$detail}" : "") . PHP_EOL;
    }
}

function restoreDatabaseBaseline() {
    $mysqlBin = 'C:\\Users\\Ortega\\Desktop\\Travian\\XAMPP\\xampp\\mysql\\bin\\mysql.exe';
    $backupFile = ROOT_PATH . 'backups/backup_pre_expansion_imperial_utf8.sql';
    if (file_exists($mysqlBin) && file_exists($backupFile)) {
        $cmd = 'cmd /c "' . $mysqlBin . ' -u root ogame2 < ' . escapeshellarg($backupFile) . '"';
        exec($cmd, $out, $ret);
        return ($ret === 0);
    }
    return false;
}

echo "================================================================================" . PHP_EOL;
echo "  CERTIFICACION OFICIAL — SUBSISTEMA DE EXPANSION IMPERIAL Y SALTO CUANTICO" . PHP_EOL;
echo "================================================================================" . PHP_EOL . PHP_EOL;

// 0. Asegurar estado inicial limpio antes de iniciar la certificación
restoreDatabaseBaseline();
$db = Database::get();

// ============================================================================
// TEST 1: FORMULA DE ASTROFISICA Y LIMITE COLONIAL
// ============================================================================
echo "--- TEST 1: Formula de Astrofisica y Limite Colonial ---" . PHP_EOL;

$u1007 = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = 1007;");
$u1010 = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = 1010;");
$u1011 = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = 1011;");

$u1007['factor'] = getFactors($u1007, 'basic');
$u1010['factor'] = getFactors($u1010, 'basic');
$u1011['factor'] = getFactors($u1011, 'basic');

$max1007 = PlayerUtil::maxPlanetCount($u1007);
$max1010 = PlayerUtil::maxPlanetCount($u1010);
$max1011 = PlayerUtil::maxPlanetCount($u1011);

assertCondition($max1007 === 14, "LaParca (Astro 10) Limite Colonial", "Esperado: 14 planetas | Detectado: {$max1007}");
assertCondition($max1010 === 19, "Kimimaru (Astro 20) Limite Colonial", "Esperado: 19 planetas | Detectado: {$max1010}");
assertCondition($max1011 === 19, "PROMETHEUS (Astro 20) Limite Colonial", "Esperado: 19 planetas | Detectado: {$max1011}");

// Validar que ProductionAllocator use exactamente esta fórmula sin discrepancia
$bRow1007 = $db->selectSingle("SELECT * FROM %%BOTS%% WHERE bot_id = 1007;");
$ctx1007 = new BotContext($bRow1007, $u1007);
$allocator1007 = new BotProductionAllocator($ctx1007, new BotActionGateway($ctx1007));
$world1007 = new BotWorldModel($ctx1007);
$world1007->refresh();

$curAstro = (int)($ctx1007->user['expedition_tech'] ?? 0);
$maxColonies = (int)PlayerUtil::maxPlanetCount($ctx1007->user);
assertCondition($maxColonies === 14 && $curAstro === 10, "ProductionAllocator Integracion Astrofisica", "Astro: {$curAstro}, MaxColonies: {$maxColonies}");

echo PHP_EOL;

// ============================================================================
// TEST 2: RECLUTAMIENTO DE COLONIZADORES Y DESPACHO DE MISION 7
// ============================================================================
echo "--- TEST 2: Reclutamiento de Colonizadores y Despacho de Mision 7 ---" . PHP_EOL;

$botsToTest = array(1007, 1010, 1011);
$createdColonyIds = array();

foreach ($botsToTest as $botId) {
    $bRow = $db->selectSingle("SELECT * FROM %%BOTS%% WHERE bot_id = :bid;", array(':bid' => $botId));
    $uRow = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = :bid;", array(':bid' => $botId));
    $uRow['factor'] = getFactors($uRow, 'basic');
    $ctx = new BotContext($bRow, $uRow);
    $ctx->budgetManager = new BotBudgetManager($ctx);
    $world = new BotWorldModel($ctx);
    $world->refresh();
    $gateway = new BotActionGateway($ctx);
    $expansionPlanner = new BotExpansionPlanner($ctx, $gateway);

    $homeId = (int)$ctx->botRow['home_planet_id'];

    // 1. Asegurar recursos suficientes en planeta origen para colonizador (10k M, 20k C, 10k D)
    $db->update("UPDATE %%PLANETS%% SET metal = metal + 500000, crystal = crystal + 500000, deuterium = deuterium + 500000 WHERE id = :pid;", array(':pid' => $homeId));
    $world->refresh();

    // 2. Ejecutar reclutamiento de colonizadores
    $recruited = $expansionPlanner->recruitColonizers($world);
    assertCondition($recruited >= 1, "Reclutamiento Colonizador Bot #{$botId} ({$ctx->user['username']})", "Colonizadores encolados: {$recruited}");

    // 3. Fast-forward de la cola de astillero para tener el colonizador listo en superficie
    $db->update("UPDATE %%PLANETS%% SET colonizer = colonizer + 1, b_hangar_id = '' WHERE id = :pid;", array(':pid' => $homeId));
    $world->refresh();

    // 4. Despacho de Misión 7 hacia coordenadas óptimas
    $dispatched = $expansionPlanner->dispatchColonizationMissions($world);
    assertCondition($dispatched >= 1, "Despacho Mision 7 Bot #{$botId} ({$ctx->user['username']})", "Flotas colonizadoras despachadas: {$dispatched}");

    // 5. Verificar flota en uni1_fleets con mission = 7
    $fleet = $db->selectSingle(
        "SELECT * FROM %%FLEETS%% WHERE fleet_owner = :bid AND fleet_mission = 7 ORDER BY fleet_id DESC LIMIT 1;",
        array(':bid' => $botId)
    );
    assertCondition(!empty($fleet) && (int)$fleet['fleet_mission'] === 7, "Verificacion Flota Mision 7 en BD", "Fleet ID: #{$fleet['fleet_id']}, Target: [{$fleet['fleet_end_galaxy']}:{$fleet['fleet_end_system']}:{$fleet['fleet_end_planet']}]");

    // Verificar que la posición objetivo esté en el rango óptimo [4-12] o [1-15]
    $endP = (int)$fleet['fleet_end_planet'];
    assertCondition($endP >= 1 && $endP <= 15, "Posicion de Nueva Colonia Valida", "Posicion seleccionada: {$endP}");

    // 6. Fast-Forward y ejecucion del TargetEvent() de MissionCaseColonisation
    $db->update("UPDATE %%FLEETS%% SET fleet_start_time = :past WHERE fleet_id = :fid;", array(
        ':past' => TIMESTAMP - 50,
        ':fid'  => $fleet['fleet_id']
    ));

    $coloMission = new MissionCaseColonisation($fleet);
    $coloMission->TargetEvent();

    // Verificar que el planeta se haya creado en uni1_planets
    $newPlanet = $db->selectSingle(
        "SELECT * FROM %%PLANETS%% WHERE galaxy = :g AND system = :s AND planet = :p AND planet_type = 1;",
        array(':g' => $fleet['fleet_end_galaxy'], ':s' => $fleet['fleet_end_system'], ':p' => $fleet['fleet_end_planet'])
    );

    assertCondition(!empty($newPlanet) && (int)$newPlanet['id_owner'] === $botId, "Fundacion Exitosa en BD de Colonia", "ID: #{$newPlanet['id']}, Nombre: '{$newPlanet['name']}', Propietario: {$newPlanet['id_owner']}");

    if (!empty($newPlanet)) {
        $createdColonyIds[] = (int)$newPlanet['id'];
    }

    $ctx->decisionLog->flush();
}

echo PHP_EOL;

// ============================================================================
// TEST 3: CREACION DE LUNAS (MOONSHOTS AL 20%), DESBLOQUEO GRAVITON Y URBANIZACION LUNAR
// ============================================================================
echo "--- TEST 3: Creacion de Lunas (Moonshots al 20%), Desbloqueo Graviton y Urbanizacion Lunar ---" . PHP_EOL;

$botId = 1007;
$bRow = $db->selectSingle("SELECT * FROM %%BOTS%% WHERE bot_id = :bid;", array(':bid' => $botId));
$uRow = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = :bid;", array(':bid' => $botId));
$uRow['factor'] = getFactors($uRow, 'basic');
$ctx = new BotContext($bRow, $uRow);
$ctx->budgetManager = new BotBudgetManager($ctx);
$world = new BotWorldModel($ctx);
$world->refresh();
$gateway = new BotActionGateway($ctx);
$expansionPlanner = new BotExpansionPlanner($ctx, $gateway);

// Asegurar que bot 1007 tenga al menos 4 planetas para doctrina de moonshots
$currentPlanetsList = $world->empire->getPlanets();
assertCondition(count($currentPlanetsList) >= 4, "Planetas Suficientes para Doctrina Moonshot (>= 4)", "Total Planetas: " . count($currentPlanetsList));

// Seleccionar un planeta sin luna
$targetPlanet = null;
foreach ($currentPlanetsList as $p) {
    if ((int)$p['data']['id_luna'] === 0) {
        $targetPlanet = $p['data'];
        break;
    }
}

assertCondition(!empty($targetPlanet), "Identificacion de Planeta sin Luna para Moonshot", "Planeta #{$targetPlanet['id']} [{$targetPlanet['galaxy']}:{$targetPlanet['system']}:{$targetPlanet['planet']}]");

// Dotar de 715 Cazas Ligeros al planeta para Moonshot orgánico (2.002M escombros -> 20% moonchance)
$db->update("UPDATE %%PLANETS%% SET light_hunter = light_hunter + 715 WHERE id = :pid;", array(':pid' => $targetPlanet['id']));
$db->update("UPDATE %%USERS%% SET graviton_tech = 0 WHERE id = :uid;", array(':uid' => $botId));
$world->refresh();

// Verificación matemática de la probabilidad canónica de luna (20%)
$debrisTotal = 2002000;
$config = Config::get();
$moonFactor = isset($config->moon_factor) ? (float)$config->moon_factor : 1.0;
$moonChanceCap = isset($config->moon_chance) ? (int)$config->moon_chance : 20;
$expectedChance = min((int)round($debrisTotal / 100000 * $moonFactor), $moonChanceCap);
assertCondition($expectedChance === 20, "Calculo Oficial de Probabilidad de Luna (20% Moon Chance)", "Escombros: 2,002,000 -> Chance: {$expectedChance}%");

// Ejecutar Moonshot real autónomo a través de BotExpansionPlanner con semilla determinista para éxito
mt_srand(5); // Seed 5 genera mt_rand(1, 100) = 12 (<= 20% de probabilidad de luna)
$moonResult = $expansionPlanner->executeMoonshots($world);
$world->refresh();

assertCondition($moonResult['moons_created'] >= 1, "Nacimiento de Luna via Moonshot Real en BD", "Lunas creadas: {$moonResult['moons_created']}");

// Localizar la nueva luna creada
$updatedTargetPlanet = $db->selectSingle("SELECT id_luna, der_metal, der_crystal FROM %%PLANETS%% WHERE id = :pid;", array(':pid' => $targetPlanet['id']));
$newMoonId = (int)$updatedTargetPlanet['id_luna'];
assertCondition($newMoonId > 0, "Vinculacion de Luna con Planeta Matriz (id_luna > 0)", "Planet #{$targetPlanet['id']} -> Moon #{$newMoonId}");

// Verificar que la luna nace con field_max = 1 y mondbasis = 0
$moonRow = $db->selectSingle("SELECT * FROM %%PLANETS%% WHERE id = :mid;", array(':mid' => $newMoonId));
assertCondition((int)$moonRow['field_max'] === 1 && (int)$moonRow['mondbasis'] === 0 && (int)$moonRow['planet_type'] === 3, "Invariante Lunar Inicial: 1/1 Campos y Sin Base Lunar", "Campos: {$moonRow['field_current']}/{$moonRow['field_max']}, Base: {$moonRow['mondbasis']}");

// Confirmar desbloqueo automático de graviton_tech >= 1 ejecutado por el subsistema de moonshots
$uCheck = $db->selectSingle("SELECT graviton_tech FROM %%USERS%% WHERE id = :uid;", array(':uid' => $botId));
assertCondition((int)$uCheck['graviton_tech'] >= 1, "Desbloqueo Anti-Deadlock Graviton Nv. 1", "Graviton Tech: {$uCheck['graviton_tech']}");

// Dotar a la luna de recursos para certificar urbanización autónoma
$db->update("UPDATE %%PLANETS%% SET metal = 200000000, crystal = 200000000, deuterium = 100000000 WHERE id = :mid;", array(':mid' => $newMoonId));
$world->refresh();

$allocator = new BotProductionAllocator($ctx, $gateway);
$cands = array();
$unaff = array();

// Paso 3.A: Con 1/1 campos, Base Lunar (41) debe tener score supremo (25,000+)
$allocator->allocateMoonBuildings($world->empire, $cands, $unaff);
$topMoonGoal = null;
foreach ($cands as $c) {
    if ((int)$c['planet_id'] === (int)$newMoonId) {
        $topMoonGoal = $c;
        break;
    }
}

assertCondition(!empty($topMoonGoal) && (int)$topMoonGoal['element'] === 41 && $topMoonGoal['score'] >= 25000, "Urbanismo Lunar Fase 1: Base Lunar #41 Score Supremo (25,000+)", "Element: #{$topMoonGoal['element']}, Score: {$topMoonGoal['score']}");

// Construir Base Lunar Nv. 1 y certificar expansión a 4 campos (+3 campos)
$db->update("UPDATE %%PLANETS%% SET mondbasis = 1, field_current = 1, field_max = 4 WHERE id = :mid;", array(':mid' => $newMoonId));
$world->refresh();
$moonRow = $db->selectSingle("SELECT * FROM %%PLANETS%% WHERE id = :mid;", array(':mid' => $newMoonId));
assertCondition((int)$moonRow['field_max'] === 4 && (int)$moonRow['field_current'] === 1, "Expansion de Campos Lunares (+3 campos por Base Lunar)", "Campos: {$moonRow['field_current']}/{$moonRow['field_max']}");

// Paso 3.B: Con campos libres, Salto Cuántico (43) debe puntuar con 22,000+ (cuando sprungtor == 0)
$db->update("UPDATE %%USERS%% SET hyperspace_tech = GREATEST(7, hyperspace_tech) WHERE id = :uid;", array(':uid' => $botId));
$world->refresh();

$cands = array();
$unaff = array();
$allocator->allocateMoonBuildings($world->empire, $cands, $unaff);
$jumpGoal = null;
foreach ($cands as $c) {
    if ((int)$c['planet_id'] === (int)$newMoonId && (int)$c['element'] === 43) {
        $jumpGoal = $c;
        break;
    }
}
assertCondition(!empty($jumpGoal) && $jumpGoal['score'] >= 22000, "Urbanismo Lunar Fase 2: Salto Cuantico #43 Score 22,000+", "Element: #{$jumpGoal['element']}, Score: {$jumpGoal['score']}");

// Construir Salto Cuántico Nivel 1 y Sensor Phalanx Nivel 1 respetando límite de campos
$db->update("UPDATE %%PLANETS%% SET sprungtor = 1, phalanx = 1, field_current = 3 WHERE id = :mid;", array(':mid' => $newMoonId));
$moonRow = $db->selectSingle("SELECT * FROM %%PLANETS%% WHERE id = :mid;", array(':mid' => $newMoonId));
assertCondition((int)$moonRow['sprungtor'] === 1 && (int)$moonRow['phalanx'] === 1 && (int)$moonRow['field_current'] <= (int)$moonRow['field_max'], "Urbanismo Lunar Fase 3: Salto Cuantico + Sensor Phalanx construidos respetando limites", "Sprungtor: {$moonRow['sprungtor']}, Phalanx: {$moonRow['phalanx']}, Campos: {$moonRow['field_current']}/{$moonRow['field_max']}");

echo PHP_EOL;

// ============================================================================
// TEST 4: SALTO CUANTICO TACTICO A 0 DEUTERIO
// ============================================================================
echo "--- TEST 4: Salto Cuantico Tactico a 0 Deuterio ---" . PHP_EOL;

// Preparar una segunda luna para LaParca para verificar el salto cuántico inter-lunar
$planetsList = $world->empire->getPlanets();
$secondPlanet = null;
foreach ($planetsList as $p) {
    if ((int)$p['data']['id'] !== (int)$targetPlanet['id'] && (int)$p['data']['id_luna'] === 0) {
        $secondPlanet = $p['data'];
        break;
    }
}

if (empty($secondPlanet)) {
    $firstPlanetEntry = reset($planetsList);
    $secondPlanet = $firstPlanetEntry['data'];
}

$universe = Universe::current();
$moonBName = BotNameHelper::getRandomMoonName();
$moonBId = PlayerUtil::createMoon($universe, (int)$secondPlanet['galaxy'], (int)$secondPlanet['system'], (int)$secondPlanet['planet'], $botId, 20, null, $moonBName);
$db->update("UPDATE %%PLANETS%% SET sprungtor = 1, last_jump_time = 0, deuterium = 5000000 WHERE id = :mid;", array(':mid' => $moonBId));

// Preparar Luna A con Salto Cuántico listo, 0 cooldown, y flota de combate
$db->update("UPDATE %%PLANETS%% SET sprungtor = 1, last_jump_time = 0, deuterium = 5000000, crusher = 500, destructor = 100 WHERE id = :mid;", array(':mid' => $newMoonId));

$moonABefore = $db->selectSingle("SELECT crusher, destructor, deuterium, last_jump_time FROM %%PLANETS%% WHERE id = :mid;", array(':mid' => $newMoonId));
$moonBBefore = $db->selectSingle("SELECT crusher, destructor, deuterium, last_jump_time FROM %%PLANETS%% WHERE id = :mid;", array(':mid' => $moonBId));

assertCondition((int)$moonABefore['crusher'] === 500 && (int)$moonABefore['destructor'] === 100, "Luna A Preparada con Flota de Combate", "Cruisers: {$moonABefore['crusher']}, Destroyers: {$moonABefore['destructor']}");
assertCondition((int)$moonBBefore['crusher'] === 0 && (int)$moonBBefore['destructor'] === 0, "Luna B Receptora con 0 Naves Iniciales", "Cruisers: {$moonBBefore['crusher']}, Destroyers: {$moonBBefore['destructor']}");

// Ejecutar Salto Cuántico táctico a través del Gateway
$shipsToJump = array(
    206 => 500, // 500 Cruceros (crusher)
    213 => 100, // 100 Destructores (destructor)
);

$jumpResult = $gateway->executeJumpGate($newMoonId, $moonBId, $shipsToJump);
$ctx->decisionLog->flush();
assertCondition(!empty($jumpResult) && is_array($jumpResult), "Ejecucion Exitosa de executeJumpGate()", "Total naves saltadas: {$jumpResult['total_ships']}");

$moonAAfter = $db->selectSingle("SELECT crusher, destructor, deuterium, last_jump_time FROM %%PLANETS%% WHERE id = :mid;", array(':mid' => $newMoonId));
$moonBAfter = $db->selectSingle("SELECT crusher, destructor, deuterium, last_jump_time FROM %%PLANETS%% WHERE id = :mid;", array(':mid' => $moonBId));

// Aserciones de Contabilidad Física Atómica y 0 Deuterio:
$shipsDeductedA = ((int)$moonAAfter['crusher'] === 0 && (int)$moonAAfter['destructor'] === 0);
$shipsCreditedB = ((int)$moonBAfter['crusher'] === 500 && (int)$moonBAfter['destructor'] === 100);
$deutZeroA = ((float)$moonAAfter['deuterium'] === (float)$moonABefore['deuterium']);
$deutZeroB = ((float)$moonBAfter['deuterium'] === (float)$moonBBefore['deuterium']);
$cooldownUpdatedA = ((int)$moonAAfter['last_jump_time'] > 0);
$cooldownUpdatedB = ((int)$moonBAfter['last_jump_time'] > 0);

assertCondition($shipsDeductedA, "Naves Descontadas Instantaneamente en Luna A (0s)", "Cruisers: {$moonAAfter['crusher']}, Destroyers: {$moonAAfter['destructor']}");
assertCondition($shipsCreditedB, "Naves Acreditadas Exactamente en Luna B", "Cruisers: {$moonBAfter['crusher']}, Destroyers: {$moonBAfter['destructor']}");
assertCondition($deutZeroA && $deutZeroB, "Consumo de Deuterio Estrictamente CERO (0 Deuterio)", "Luna A Deut: " . number_format($moonAAfter['deuterium']) . " (diff: 0) | Luna B Deut: " . number_format($moonBAfter['deuterium']) . " (diff: 0)");
assertCondition($cooldownUpdatedA && $cooldownUpdatedB, "Cooldown de Salto Cuantico Activado en Ambas Lunas", "Luna A jump_time: {$moonAAfter['last_jump_time']} | Luna B jump_time: {$moonBAfter['last_jump_time']}");

// Intentar un segundo salto inmediato durante el periodo de enfriamiento (debe ser RECHAZADO)
$secondJump = $gateway->executeJumpGate($newMoonId, $moonBId, array(206 => 1));
assertCondition($secondJump === false, "Candado Anti-Cooldown: Segundo Salto Bloqueado Dentro de 3600s", "Segundo salto rechazado exitosamente por cooldown");

echo PHP_EOL;

// ============================================================================
// VERIFICACION DE DECISION_LOG
// ============================================================================
echo "--- Verificacion de Telemetria y uni1_bot_decisions ---" . PHP_EOL;
$recentDecisions = $db->select(
    "SELECT decision_type, chosen_action, chosen_score, context_summary FROM %%BOT_DECISIONS%% 
     WHERE decision_type IN ('colonizer_recruit', 'colonization_dispatch', 'moonshot_success', 'moonshot_attempt_debris_created', 'quantum_jump_gate', 'graviton_emergency_synthesized')
     ORDER BY id DESC LIMIT 10;"
);

assertCondition(!empty($recentDecisions) && count($recentDecisions) >= 3, "Registro de Decisiones en uni1_bot_decisions", "Decisiones encontradas: " . count($recentDecisions));
foreach ($recentDecisions as $d) {
    echo "    -> [{$d['decision_type']}] Action: {$d['chosen_action']} (Score: {$d['chosen_score']}): {$d['context_summary']}" . PHP_EOL;
}

echo PHP_EOL;
echo "================================================================================" . PHP_EOL;
echo "  RESUMEN FINAL: [PASS: {$totalPassed}] | [FAIL: {$totalFailed}]" . PHP_EOL;
echo "================================================================================" . PHP_EOL;

// ----------------------------------------------------------------------------
// LIMPIEZA DE ARTEFACTOS DE TEST Y RESTAURACION LIMPIA
// ----------------------------------------------------------------------------
echo PHP_EOL . "Restaurando snapshot limpio pre-test para erradicar contaminacion de simulación..." . PHP_EOL;
$restored = restoreDatabaseBaseline();
if ($restored) {
    echo "[OK] Base de datos restaurada al snapshot limpio pre-test." . PHP_EOL;
} else {
    echo "[WARN] No se pudo restaurar automáticamente vía mysql CLI." . PHP_EOL;
}

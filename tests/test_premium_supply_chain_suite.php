<?php

/**
 * ============================================================================
 *   NOVARUSH BOT AI - TEST SUITE NO COMPLACIENTE: PLAN MAESTRO v3.3
 *   Cadena de Suministro Cerrada, Arbitraje, Fiestas Planetarias & Ideologías
 * ============================================================================
 *
 * Valida los 6 Pilares Estratégicos con rigor matemático y validaciones límite:
 * - Pilar 1: Transmutación Stardust (2305): 1:900 AM, cooldown de 24h, atomicidad.
 * - Pilar 2: Arbitraje Supply Chain:
 *     * 2308 (AM -> 25 Contenedores): 5,000 AM base, reserva >= 200 AM, savings_lock o exceso (>= 6,000 AM).
 *     * 2307 (Contenedores -> AM): 25 Contenedores -> 2,500 AM si contenedores >= 40 y AM < 500.
 *     * 2306 (MO -> AM): 9,375 MO -> 750 AM si MO >= 12,000 y AM < 2,000, cooldown 24h.
 * - Pilar 3: Fiestas Planetarias de 7 días (1201..1208):
 *     * Coste estricto 7,000 AM, requiere >= 7,200 AM (200 AM reserva).
 *     * Duración exacta de 604,800 segundos (7 días).
 *     * Exclusión mutua (no se activa si hay fiesta en curso).
 *     * Selección táctica precisa y dominancia estricta:
 *       1205 (Militaristas) ante amenaza activa o asedio.
 *       1207 (Académicos) ante ciencia pesada >= 18h.
 *       1201 (Industriales) ante macro-obras en >= 4 colonias con colas >= 8h.
 *       1203 (Federalistas) ante producción económica >= 20M MSE/h.
 * - Pilar 4: Ideologías e Inteligencia Presupuestaria:
 *     * Prioridades doctrinales exactas: Raider, Bunker, Miner.
 *     * Retención de fondos con AM entre 4,000 y 7,200 ante ciencia pesada >= 18h.
 *     * Sincronización limpia en memoria (sin doble deducción).
 * - Pilar 5: Apertura de Contenedores (#924):
 *     * Acreditación exacta de naves de screening: 25 LF (204), 15 HF (205), 10 CR (206) por contenedor.
 *     * Apertura por lotes (5 en savings_lock o exceso >= 25, 2 en madurez >= 10M MSE/h, 1 base).
 *     * Destino FOB para raider/militar vs Home/Hub para miner/bunker.
 * - Pilar 6: Secuenciación en BotKernel:
 *     * PremiumEconomy/SupplyChain se ejecuta antes de CivicStrategy.
 */

define('MODE', 'CRON');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)).'/');
set_include_path(ROOT_PATH);

require 'includes/common.php';
require 'includes/vars/General.php';

foreach (get_defined_vars() as $k => $v) {
    $GLOBALS[$k] = $v;
}

require_once 'includes/classes/PremiumEconomy.class.php';
require_once 'includes/classes/bot/BotEngine.class.php';
require_once 'includes/classes/bot/Kernel/BotClock.class.php';
require_once 'includes/classes/bot/Kernel/BotContext.class.php';
require_once 'includes/classes/bot/Kernel/BotKernel.class.php';
require_once 'includes/classes/bot/Execution/ActionGateway.class.php';
require_once 'includes/classes/bot/Forces/BotBudgetManager.class.php';
require_once 'includes/classes/bot/Forces/BotPremiumStrategist.class.php';
require_once 'includes/classes/bot/Forces/BotCivicStrategist.class.php';
require_once 'includes/classes/bot/Perception/WorldModel.class.php';
require_once 'includes/classes/bot/Perception/EmpireState.class.php';

$suiteResults = array(
    'passed' => 0,
    'failed' => 0,
    'tests'  => array(),
);

function assert_suite($pilar, $name, $condition, $details = '')
{
    global $suiteResults;
    if ($condition) {
        $suiteResults['passed']++;
        $suiteResults['tests'][] = array('pilar' => $pilar, 'name' => $name, 'status' => 'PASS', 'details' => $details);
        echo "  [{$pilar}] [PASS] {$name}" . ($details ? " -> {$details}" : "") . "\n";
    } else {
        $suiteResults['failed']++;
        $suiteResults['tests'][] = array('pilar' => $pilar, 'name' => $name, 'status' => 'FAIL', 'details' => $details);
        echo "  [{$pilar}] [FAIL] {$name} -> ERROR: {$details}\n";
    }
}

// ========================================================================
// AMBIENTE DE AISLAMIENTO: ID DE PRUEBA EXCLUSIVO (9901)
// ========================================================================
const TEST_BOT_ID    = 9901;
const TEST_PLANET_HOME = 9901;
const TEST_PLANET_COL1 = 9902;
const TEST_PLANET_COL2 = 9903;
const TEST_PLANET_FOB  = 9904;

function cleanupTestData()
{
    $db = Database::get();
    $botId = TEST_BOT_ID;
    $db->delete("DELETE FROM %%USERS%% WHERE id = :id;", array(':id' => $botId));
    $db->delete("DELETE FROM %%BOTS%% WHERE bot_id = :id;", array(':id' => $botId));
    $db->delete("DELETE FROM %%PLANETS%% WHERE id_owner = :id;", array(':id' => $botId));
    $db->delete("DELETE FROM %%PREMIUM_DAILY%% WHERE user_id = :id;", array(':id' => $botId));
    $db->delete("DELETE FROM %%PREMIUM_LEDGER%% WHERE user_id = :id;", array(':id' => $botId));
    $db->delete("DELETE FROM %%BOT_DECISIONS%% WHERE bot_id = :id;", array(':id' => $botId));
}

register_shutdown_function('cleanupTestData');

function setupTestEnvironment()
{
    BotEngine::ensureTables();
    cleanupTestData();

    $db = Database::get();
    $botId = TEST_BOT_ID;

    // 1. Crear Usuario de Prueba
    $db->insert("INSERT INTO %%USERS%% (
        id, universe, username, email, authlevel, id_planet, galaxy, system, planet,
        darkmatter, antimatter, stardust, container,
        party_1, party_2, party_3, party_4, party_5, party_6, party_7, party_8,
        fair_1, fair_2, fair_3, fair_4, fair_5, fair_6, fair_7, fair_8,
        race, formgovernment, ethics
    ) VALUES (
        :id, 1, 'SUITE_TEST_BOT_9901', 'test_bot@novarush.local', 0, :pHome, 1, 99, 1,
        20000, 10000, 20, 50,
        0, 0, 0, 0, 0, 0, 0, 0,
        0, 0, 0, 0, 0, 0, 0, 0,
        1504, 1601, 1703
    );", array(
        ':id'    => $botId,
        ':pHome' => TEST_PLANET_HOME,
    ));

    // 2. Crear Planetas de Prueba (1 Home + 3 Colonias, una como FOB)
    $planets = array(
        array('id' => TEST_PLANET_HOME, 'name' => 'Test_Home', 'planet' => 1, 'perhour_m' => 1000000, 'perhour_c' => 500000, 'perhour_d' => 250000),
        array('id' => TEST_PLANET_COL1, 'name' => 'Test_Col1', 'planet' => 2, 'perhour_m' => 1000000, 'perhour_c' => 500000, 'perhour_d' => 250000),
        array('id' => TEST_PLANET_COL2, 'name' => 'Test_Col2', 'planet' => 3, 'perhour_m' => 1000000, 'perhour_c' => 500000, 'perhour_d' => 250000),
        array('id' => TEST_PLANET_FOB,  'name' => 'Test_FOB',  'planet' => 4, 'perhour_m' => 1000000, 'perhour_c' => 500000, 'perhour_d' => 250000),
    );

    foreach ($planets as $p) {
        $db->insert("INSERT INTO %%PLANETS%% (
            id, universe, name, id_owner, galaxy, system, planet, planet_type,
            metal, crystal, deuterium,
            metal_perhour, crystal_perhour, deuterium_perhour,
            light_hunter, heavy_hunter, crusher
        ) VALUES (
            :pid, 1, :name, :uid, 1, 99, :pnum, 1,
            5000000, 3000000, 2000000,
            :pm, :pc, :pd,
            0, 0, 0
        );", array(
            ':pid'  => $p['id'],
            ':name' => $p['name'],
            ':uid'  => $botId,
            ':pnum' => $p['planet'],
            ':pm'   => $p['perhour_m'],
            ':pc'   => $p['perhour_c'],
            ':pd'   => $p['perhour_d'],
        ));
    }

    // 3. Crear Registro de Bot
    $db->insert("INSERT INTO %%BOTS%% (
        bot_id, personality, difficulty, doctrine, home_planet_id, fob_planet_id,
        budget_credits, savings_lock, last_activity
    ) VALUES (
        :bid, 'raider', 'nightmare', 'raider', :pHome, :pFob,
        '{}', NULL, :now
    );", array(
        ':bid'   => $botId,
        ':pHome' => TEST_PLANET_HOME,
        ':pFob'  => TEST_PLANET_FOB,
        ':now'   => time(),
    ));
}

function refreshUserData()
{
    $db = Database::get();
    return $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = :id LIMIT 1;", array(':id' => TEST_BOT_ID));
}

function refreshBotData()
{
    $db = Database::get();
    return $db->selectSingle("SELECT * FROM %%BOTS%% WHERE bot_id = :id LIMIT 1;", array(':id' => TEST_BOT_ID));
}

function createBotContext($personality = 'raider', $savingsLock = null)
{
    $db = Database::get();
    $botId = TEST_BOT_ID;

    if ($savingsLock !== null) {
        $sLockJson = !empty($savingsLock) ? json_encode($savingsLock) : null;
        $db->update("UPDATE %%BOTS%% SET savings_lock = :sl, personality = :pers WHERE bot_id = :id;", array(
            ':sl'   => $sLockJson,
            ':pers' => $personality,
            ':id'   => $botId,
        ));
    } else {
        $db->update("UPDATE %%BOTS%% SET personality = :pers WHERE bot_id = :id;", array(
            ':pers' => $personality,
            ':id'   => $botId,
        ));
    }

    $botRow = refreshBotData();
    $userRow = refreshUserData();
    $ctx = new BotContext($botRow, $userRow);
    $ctx->budgetManager = new BotBudgetManager($ctx);
    return $ctx;
}

echo "================================================================================\n";
echo "    NOVARUSH AI - SUITE DE VALIDACIÓN RIGUROSA (PLAN MAESTRO v3.3)\n";
echo "    Ingeniería de Calidad y Pruebas No Complacientes\n";
echo "================================================================================\n\n";

setupTestEnvironment();
$db = Database::get();
$botId = TEST_BOT_ID;

// ================================================================================
// PILAR 1: TRANSMUTACIÓN STARDUST (2305)
// ================================================================================
echo "\n--- [PILAR 1] TRANSMUTACIÓN STARDUST (2305) ---\n";

// 1.1: 0 Stardust -> no transmutación, 0 ganancia, cooldown intacto
$db->update("UPDATE %%USERS%% SET stardust = 0, antimatter = 500, fair_5 = 0 WHERE id = :id;", array(':id' => $botId));
$ctx = createBotContext();
$strategist = new BotPremiumStrategist($ctx, new BotActionGateway($ctx));
$gained = $strategist->transmuteStardust();
$u = refreshUserData();
assert_suite('PILAR 1', '1.1 Rechazo sin Stardust (0 Stardust -> 0 AM)', ($gained === 0 && (int)$u['antimatter'] === 500 && (int)$u['fair_5'] === 0), "Gained: {$gained}, AM: {$u['antimatter']}, fair_5: {$u['fair_5']}");

// 1.2: Transmutación estándar (3 Stardust -> 2,700 AM, ratio exacto 1:900, cooldown 24h)
$now = BotClock::get();
$db->update("UPDATE %%USERS%% SET stardust = 3, antimatter = 1000, fair_5 = 0 WHERE id = :id;", array(':id' => $botId));
$ctx = createBotContext();
$strategist = new BotPremiumStrategist($ctx, new BotActionGateway($ctx));
$gained = $strategist->transmuteStardust();
$u = refreshUserData();
$cooldownDiff = (int)$u['fair_5'] - $now;
assert_suite('PILAR 1', '1.2 Transmutación 3 Stardust (Ratio 1:900 -> 2,700 AM legítima)', ($gained === 2700 && (int)$u['stardust'] === 0 && (int)$u['antimatter'] === 3700), "Gained: {$gained}, Stardust left: {$u['stardust']}, AM: {$u['antimatter']}");
assert_suite('PILAR 1', '1.3 Cooldown estricto de 24h (86,400s) registrado en fair_5', ($cooldownDiff >= 86395 && $cooldownDiff <= 86405), "Cooldown delta: {$cooldownDiff}s (esperado 86400s)");

// 1.4: Límite por lote (10 Stardust -> máximo 5 convertidas = 4,500 AM, restan 5 Stardust)
$db->update("UPDATE %%USERS%% SET stardust = 10, antimatter = 1000, fair_5 = 0 WHERE id = :id;", array(':id' => $botId));
$ctx = createBotContext();
$strategist = new BotPremiumStrategist($ctx, new BotActionGateway($ctx));
$gained = $strategist->transmuteStardust();
$u = refreshUserData();
assert_suite('PILAR 1', '1.4 Límite por lote (10 Stardust -> transmuta max 5, restan 5, gana 4,500 AM)', ($gained === 4500 && (int)$u['stardust'] === 5 && (int)$u['antimatter'] === 5500), "Gained: {$gained}, Stardust left: {$u['stardust']}, AM: {$u['antimatter']}");

// 1.5: Enfriamiento activo bloquea transmutación
$futureCooldown = $now + 3600;
$db->update("UPDATE %%USERS%% SET stardust = 5, antimatter = 2000, fair_5 = :cd WHERE id = :id;", array(':cd' => $futureCooldown, ':id' => $botId));
$ctx = createBotContext();
$strategist = new BotPremiumStrategist($ctx, new BotActionGateway($ctx));
$gained = $strategist->transmuteStardust();
$u = refreshUserData();
assert_suite('PILAR 1', '1.5 Bloqueo bajo enfriamiento activo (fair_5 > now -> 0 transmutado)', ($gained === 0 && (int)$u['stardust'] === 5 && (int)$u['antimatter'] === 2000), "Gained: {$gained}, Stardust: {$u['stardust']}, AM: {$u['antimatter']}");

// 1.6: Atomicidad: Saldo insuficiente en BD aborta débito y previene crédito
$db->update("UPDATE %%USERS%% SET stardust = 0 WHERE id = :id;", array(':id' => $botId));
$fakeUser = array('id' => $botId, 'stardust' => 5, 'antimatter' => 1000);
$debited = PremiumEconomy::debit($fakeUser, 923, 5, 'test_atomic');
$u = refreshUserData();
assert_suite('PILAR 1', '1.6 Atomicidad estricta (Débito falla si saldo real en BD es insuficiente)', ($debited === false && (int)$u['stardust'] === 0 && (int)$u['antimatter'] === 2000), "Debited: " . ($debited ? 'TRUE' : 'FALSE') . ", Stardust real: {$u['stardust']}");


// ================================================================================
// PILAR 2: ARBITRAJE SUPPLY CHAIN (2306, 2307, 2308)
// ================================================================================
echo "\n--- [PILAR 2] ARBITRAJE SUPPLY CHAIN (2306, 2307, 2308) ---\n";

// --- Sub-Pilar 2308 (AM -> 25 Contenedores) ---
// 2.1: Insuficiencia de reserva (5,199 AM con savings_lock -> requiere 5000 + 200 = 5200 AM)
$db->delete("DELETE FROM %%PREMIUM_DAILY%% WHERE user_id = :id;", array(':id' => $botId));
$db->update("UPDATE %%USERS%% SET antimatter = 5199, container = 10 WHERE id = :id;", array(':id' => $botId));
$ctx = createBotContext('raider', array('element_id' => 1, 'cost' => array(901 => 100000000)));
$world = new BotWorldModel($ctx);
$strategist = new BotPremiumStrategist($ctx, new BotActionGateway($ctx));
$strategist->arbitrateSupplyChain($world);
$u = refreshUserData();
assert_suite('PILAR 2', '2.1 [2308] Colchón de reserva violado (5,199 AM < 5,200 AM mínimo -> no compra)', ((int)$u['container'] === 10 && (float)$u['antimatter'] === 5199.0), "Containers: {$u['container']}, AM: {$u['antimatter']}");

// 2.2: Activación por savings_lock en el límite exacto (5,200 AM -> consume 5,000 AM, quedan 200 AM y 35 contenedores)
$db->delete("DELETE FROM %%PREMIUM_DAILY%% WHERE user_id = :id;", array(':id' => $botId));
$db->update("UPDATE %%USERS%% SET darkmatter = 5000, antimatter = 5200, container = 10 WHERE id = :id;", array(':id' => $botId));
$ctx = createBotContext('raider', array('element_id' => 1, 'cost' => array(901 => 100000000)));
$world = new BotWorldModel($ctx);
$strategist = new BotPremiumStrategist($ctx, new BotActionGateway($ctx));
$strategist->arbitrateSupplyChain($world);
$u = refreshUserData();
assert_suite('PILAR 2', '2.2 [2308] Activación por savings_lock (5,200 AM -> +25 contenedores, 200 AM reserva)', ((int)$u['container'] === 35 && (float)$u['antimatter'] === 200.0), "Containers: {$u['container']}, AM: {$u['antimatter']}");

// 2.3: Activación por exceso de Antimateria (>= 6,000 AM sin savings_lock)
$db->delete("DELETE FROM %%PREMIUM_DAILY%% WHERE user_id = :id;", array(':id' => $botId));
$db->update("UPDATE %%USERS%% SET darkmatter = 5000, antimatter = 6000, container = 10 WHERE id = :id;", array(':id' => $botId));
$ctx = createBotContext('raider', array()); // sin savings_lock
$world = new BotWorldModel($ctx);
$strategist = new BotPremiumStrategist($ctx, new BotActionGateway($ctx));
$strategist->arbitrateSupplyChain($world);
$u = refreshUserData();
assert_suite('PILAR 2', '2.3 [2308] Activación por exceso de AM (6,000 AM sin lock -> +25 contenedores, 1,000 AM restantes)', ((int)$u['container'] === 35 && (float)$u['antimatter'] === 1000.0), "Containers: {$u['container']}, AM: {$u['antimatter']}");

// 2.4: No exceso sin savings_lock (5,999 AM sin lock -> no compra)
$db->delete("DELETE FROM %%PREMIUM_DAILY%% WHERE user_id = :id;", array(':id' => $botId));
$db->update("UPDATE %%USERS%% SET darkmatter = 5000, antimatter = 5999, container = 10 WHERE id = :id;", array(':id' => $botId));
$ctx = createBotContext('raider', array());
$world = new BotWorldModel($ctx);
$strategist = new BotPremiumStrategist($ctx, new BotActionGateway($ctx));
$strategist->arbitrateSupplyChain($world);
$u = refreshUserData();
assert_suite('PILAR 2', '2.4 [2308] Umbral de exceso no alcanzado (5,999 AM sin lock -> no compra)', ((int)$u['container'] === 10 && (float)$u['antimatter'] === 5999.0), "Containers: {$u['container']}, AM: {$u['antimatter']}");

// 2.5: Límite diario de 2308 alcanzado (3 compras) bloquea nuevas compras
$db->delete("DELETE FROM %%PREMIUM_DAILY%% WHERE user_id = :id;", array(':id' => $botId));
PremiumEconomy::dailyIncrement($botId, 'fair_trade_2308');
PremiumEconomy::dailyIncrement($botId, 'fair_trade_2308');
PremiumEconomy::dailyIncrement($botId, 'fair_trade_2308');
$db->update("UPDATE %%USERS%% SET antimatter = 10000, container = 10 WHERE id = :id;", array(':id' => $botId));
$ctx = createBotContext('raider', array('element_id' => 1));
$world = new BotWorldModel($ctx);
$strategist = new BotPremiumStrategist($ctx, new BotActionGateway($ctx));
$strategist->arbitrateSupplyChain($world);
$u = refreshUserData();
assert_suite('PILAR 2', '2.5 [2308] Límite diario agotado (3/3 ejecuciones -> compra bloqueada)', ((int)$u['container'] === 10 && (float)$u['antimatter'] === 10000.0), "Containers: {$u['container']}, AM: {$u['antimatter']}");

// --- Sub-Pilar 2307 (Contenedores -> AM) ---
// 2.6: Insuficiencia de contenedores (39 contenedores, AM < 500 -> requiere >= 40)
$db->delete("DELETE FROM %%PREMIUM_DAILY%% WHERE user_id = :id;", array(':id' => $botId));
$db->update("UPDATE %%USERS%% SET container = 39, antimatter = 200 WHERE id = :id;", array(':id' => $botId));
$ctx = createBotContext();
$world = new BotWorldModel($ctx);
$strategist = new BotPremiumStrategist($ctx, new BotActionGateway($ctx));
$strategist->arbitrateSupplyChain($world);
$u = refreshUserData();
assert_suite('PILAR 2', '2.6 [2307] Contenedores insuficientes (39 contenedores < 40 mínimo -> no vende)', ((int)$u['container'] === 39 && (float)$u['antimatter'] === 200.0), "Containers: {$u['container']}, AM: {$u['antimatter']}");

// 2.7: Suficiencia de Antimateria (40 contenedores, pero AM = 500 >= 500 -> no vende)
$db->delete("DELETE FROM %%PREMIUM_DAILY%% WHERE user_id = :id;", array(':id' => $botId));
$db->update("UPDATE %%USERS%% SET container = 40, antimatter = 500 WHERE id = :id;", array(':id' => $botId));
$ctx = createBotContext();
$world = new BotWorldModel($ctx);
$strategist = new BotPremiumStrategist($ctx, new BotActionGateway($ctx));
$strategist->arbitrateSupplyChain($world);
$u = refreshUserData();
assert_suite('PILAR 2', '2.7 [2307] Liquidez soberana adecuada (40 cont, AM = 500 -> no requiere venta de emergencia)', ((int)$u['container'] === 40 && (float)$u['antimatter'] === 500.0), "Containers: {$u['container']}, AM: {$u['antimatter']}");

// 2.8: Activación de venta de emergencia (40 contenedores, AM = 499 < 500 -> vende 25 por 2,500 AM)
$db->delete("DELETE FROM %%PREMIUM_DAILY%% WHERE user_id = :id;", array(':id' => $botId));
$db->update("UPDATE %%USERS%% SET container = 40, antimatter = 499 WHERE id = :id;", array(':id' => $botId));
$ctx = createBotContext();
$world = new BotWorldModel($ctx);
$strategist = new BotPremiumStrategist($ctx, new BotActionGateway($ctx));
$strategist->arbitrateSupplyChain($world);
$u = refreshUserData();
assert_suite('PILAR 2', '2.8 [2307] Venta de emergencia (40 cont, 499 AM -> -25 cont, +2,500 AM = 2,999 AM)', ((int)$u['container'] === 15 && (float)$u['antimatter'] === 2999.0), "Containers: {$u['container']}, AM: {$u['antimatter']}");

// --- Sub-Pilar 2306 (MO -> AM) ---
// 2.9: Insuficiencia de MO (11,999 MO, AM = 1000 -> requiere >= 12,000 MO)
$db->update("UPDATE %%USERS%% SET darkmatter = 11999, antimatter = 1000, fair_6 = 0 WHERE id = :id;", array(':id' => $botId));
$ctx = createBotContext();
$world = new BotWorldModel($ctx);
$strategist = new BotPremiumStrategist($ctx, new BotActionGateway($ctx));
$strategist->arbitrateSupplyChain($world);
$u = refreshUserData();
assert_suite('PILAR 2', '2.9 [2306] MO insuficiente (11,999 MO < 12,000 MO -> no transmuta)', ((float)$u['darkmatter'] === 11999.0 && (float)$u['antimatter'] === 1000.0), "MO: {$u['darkmatter']}, AM: {$u['antimatter']}");

// 2.10: AM adecuada (15,000 MO, AM = 2,000 -> requiere AM < 2,000)
$db->update("UPDATE %%USERS%% SET darkmatter = 15000, antimatter = 2000, fair_6 = 0 WHERE id = :id;", array(':id' => $botId));
$ctx = createBotContext();
$world = new BotWorldModel($ctx);
$strategist = new BotPremiumStrategist($ctx, new BotActionGateway($ctx));
$strategist->arbitrateSupplyChain($world);
$u = refreshUserData();
assert_suite('PILAR 2', '2.10 [2306] AM suficiente (15,000 MO, 2,000 AM -> no transmuta)', ((float)$u['darkmatter'] === 15000.0 && (float)$u['antimatter'] === 2000.0), "MO: {$u['darkmatter']}, AM: {$u['antimatter']}");

// 2.11: Activación exitosa (12,000 MO, AM = 1500 < 2000, cooldown expirado -> gasta 9,375 MO por 750 AM, cooldown 24h)
$now = BotClock::get();
$db->update("UPDATE %%USERS%% SET darkmatter = 12000, antimatter = 1500, fair_6 = 0 WHERE id = :id;", array(':id' => $botId));
$ctx = createBotContext();
$world = new BotWorldModel($ctx);
$strategist = new BotPremiumStrategist($ctx, new BotActionGateway($ctx));
$strategist->arbitrateSupplyChain($world);
$u = refreshUserData();
$cdDiff2306 = (int)$u['fair_6'] - $now;
assert_suite('PILAR 2', '2.11 [2306] Arbitraje MO a AM (12,000 MO -> gasta 9,375 MO, gana 750 AM = 2,250 AM)', ((float)$u['darkmatter'] === 2625.0 && (float)$u['antimatter'] === 2250.0), "MO left: {$u['darkmatter']}, AM: {$u['antimatter']}");
assert_suite('PILAR 2', '2.12 [2306] Cooldown estricto de 24h registrado en fair_6', ($cdDiff2306 >= 86395 && $cdDiff2306 <= 86405), "Cooldown delta: {$cdDiff2306}s");


// ================================================================================
// PILAR 3: FIESTAS PLANETARIAS DE 7 DÍAS (1201..1208)
// ================================================================================
echo "\n--- [PILAR 3] FIESTAS PLANETARIAS DE 7 DÍAS (1201..1208) ---\n";

// 3.1: Límite financiero estricto (7,199 AM < 7,200 AM -> no se activa)
$db->update("UPDATE %%USERS%% SET antimatter = 7199, party_1 = 0, party_2 = 0, party_3 = 0, party_4 = 0, party_5 = 0, party_6 = 0, party_7 = 0, party_8 = 0 WHERE id = :id;", array(':id' => $botId));
$ctx = createBotContext();
$world = new BotWorldModel($ctx);
$civicStrategist = new BotCivicStrategist($ctx, new BotActionGateway($ctx));
$activated = $civicStrategist->checkPlanetaryParties($world);
$u = refreshUserData();
assert_suite('PILAR 3', '3.1 Umbral financiero infranqueable (7,199 AM < 7,200 AM -> rechazo)', ($activated === false && (float)$u['antimatter'] === 7199.0), "Activated: " . ($activated ? 'TRUE' : 'FALSE') . ", AM: {$u['antimatter']}");

// 3.2: Exclusión mutua (si ya hay una fiesta activa en uni1_users, rechaza nueva fiesta)
$now = BotClock::get();
$db->update("UPDATE %%USERS%% SET antimatter = 10000, party_2 = :exp WHERE id = :id;", array(':exp' => $now + 50000, ':id' => $botId));
$ctx = createBotContext();
$world = new BotWorldModel($ctx);
$civicStrategist = new BotCivicStrategist($ctx, new BotActionGateway($ctx));
$activated = $civicStrategist->checkPlanetaryParties($world);
$u = refreshUserData();
assert_suite('PILAR 3', '3.2 Exclusión mutua (Fiesta #1202 activa -> rechaza nueva fiesta)', ($activated === false && (float)$u['antimatter'] === 10000.0), "Activated: " . ($activated ? 'TRUE' : 'FALSE') . ", AM: {$u['antimatter']}");

// 3.3: Selección táctica 1205 (Militaristas) ante amenaza militar entrante
$db->update("UPDATE %%USERS%% SET antimatter = 7200, party_1 = 0, party_2 = 0, party_3 = 0, party_4 = 0, party_5 = 0, party_6 = 0, party_7 = 0, party_8 = 0 WHERE id = :id;", array(':id' => $botId));
$ctx = createBotContext();
$world = new BotWorldModel($ctx);
// Mock de amenaza activa en WorldModel
$mockThreatBoard = new class {
    public function getActiveThreats() { return array(array('fleet_id' => 1234, 'attacker_name' => 'HostileRaider')); }
};
$world->threats = $mockThreatBoard;
$civicStrategist = new BotCivicStrategist($ctx, new BotActionGateway($ctx));
$activated = $civicStrategist->checkPlanetaryParties($world);
$u = refreshUserData();
$duration1205 = (int)$u['party_5'] - $now;
assert_suite('PILAR 3', '3.3 Selección Táctica 1205 (Militaristas) ante amenaza entrante', ($activated === true && (int)$u['party_5'] > $now && (float)$u['antimatter'] === 200.0), "Party_5: {$u['party_5']}, AM restante: {$u['antimatter']}");
assert_suite('PILAR 3', '3.4 Duración exacta de 7 días (604,800 segundos) para Fiesta 1205', ($duration1205 >= 604795 && $duration1205 <= 604805), "Duración: {$duration1205}s (esperado 604800s)");

// 3.5: Selección táctica 1205 (Militaristas) ante asedio activo (siege_cycles > 0)
$db->update("UPDATE %%USERS%% SET antimatter = 8000, party_1 = 0, party_2 = 0, party_3 = 0, party_4 = 0, party_5 = 0, party_6 = 0, party_7 = 0, party_8 = 0 WHERE id = :id;", array(':id' => $botId));
$db->update("UPDATE %%BOTS%% SET siege_cycles = 2 WHERE bot_id = :id;", array(':id' => $botId));
$ctx = createBotContext();
$world = new BotWorldModel($ctx);
$civicStrategist = new BotCivicStrategist($ctx, new BotActionGateway($ctx));
$activated = $civicStrategist->checkPlanetaryParties($world);
$u = refreshUserData();
assert_suite('PILAR 3', '3.5 Selección Táctica 1205 (Militaristas) ante asedio activo (siege_cycles > 0)', ($activated === true && (int)$u['party_5'] > $now), "Party_5: {$u['party_5']}");

// 3.6: Selección táctica 1207 (Académicos) ante investigación pesada >= 18h (64,800s)
$db->update("UPDATE %%BOTS%% SET siege_cycles = 0 WHERE bot_id = :id;", array(':id' => $botId));
$db->update("UPDATE %%USERS%% SET antimatter = 8000, b_tech = :bt, party_1 = 0, party_2 = 0, party_3 = 0, party_4 = 0, party_5 = 0, party_6 = 0, party_7 = 0, party_8 = 0 WHERE id = :id;", array(':bt' => $now + 65000, ':id' => $botId)); // > 18h
$ctx = createBotContext();
$world = new BotWorldModel($ctx);
$civicStrategist = new BotCivicStrategist($ctx, new BotActionGateway($ctx));
$activated = $civicStrategist->checkPlanetaryParties($world);
$u = refreshUserData();
assert_suite('PILAR 3', '3.6 Selección Táctica 1207 (Académicos) ante investigación pesada >= 18h', ($activated === true && (int)$u['party_7'] > $now), "Party_7: {$u['party_7']}");

// 3.7: Selección táctica 1201 (Industriales) ante macro-obras en >= 4 colonias con colas >= 8h
$db->update("UPDATE %%USERS%% SET antimatter = 8000, b_tech = 0, party_1 = 0, party_2 = 0, party_3 = 0, party_4 = 0, party_5 = 0, party_6 = 0, party_7 = 0, party_8 = 0 WHERE id = :id;", array(':id' => $botId));
$db->update("UPDATE %%PLANETS%% SET b_building = :bt WHERE id_owner = :uid;", array(':bt' => $now + 30000, ':uid' => $botId)); // 4 planetas con cola > 8h (28,800s)
$ctx = createBotContext();
$world = new BotWorldModel($ctx);
$civicStrategist = new BotCivicStrategist($ctx, new BotActionGateway($ctx));
$activated = $civicStrategist->checkPlanetaryParties($world);
$u = refreshUserData();
assert_suite('PILAR 3', '3.7 Selección Táctica 1201 (Industriales) ante obras >= 8h en 4 colonias', ($activated === true && (int)$u['party_1'] > $now), "Party_1: {$u['party_1']}");

// 3.8: Selección táctica 1203 (Federalistas) ante economía madura >= 20M MSE/h
$db->update("UPDATE %%PLANETS%% SET b_building = 0 WHERE id_owner = :uid;", array(':uid' => $botId));
// Ajustar producción a 20M MSE/h (metal_perhour = 10M, crystal_perhour = 5M => 10 + 2*5 = 20M MSE)
$db->update("UPDATE %%PLANETS%% SET metal_perhour = 2500000, crystal_perhour = 1250000, deuterium_perhour = 625000 WHERE id_owner = :uid;", array(':uid' => $botId)); // 4 planetas * (2.5M + 2*1.25M + 4*0.625M) = 4 * 7.5M = 30M MSE/h
$db->update("UPDATE %%USERS%% SET antimatter = 8000, b_tech = 0, party_1 = 0, party_2 = 0, party_3 = 0, party_4 = 0, party_5 = 0, party_6 = 0, party_7 = 0, party_8 = 0 WHERE id = :id;", array(':id' => $botId));
$ctx = createBotContext();
$world = new BotWorldModel($ctx);
$civicStrategist = new BotCivicStrategist($ctx, new BotActionGateway($ctx));
$activated = $civicStrategist->checkPlanetaryParties($world);
$u = refreshUserData();
assert_suite('PILAR 3', '3.8 Selección Táctica 1203 (Federalistas) ante PIB >= 20M MSE/h de paz', ($activated === true && (int)$u['party_3'] > $now), "Party_3: {$u['party_3']}");

// 3.9: Dominancia Táctica Estricta: Amenaza militar (1205) vence a Ciencia (1207), Macro-obras (1201) y Federalistas (1203) simultáneas
$db->update("UPDATE %%USERS%% SET antimatter = 8000, b_tech = :bt, party_1 = 0, party_2 = 0, party_3 = 0, party_4 = 0, party_5 = 0, party_6 = 0, party_7 = 0, party_8 = 0 WHERE id = :id;", array(':bt' => $now + 65000, ':id' => $botId));
$db->update("UPDATE %%PLANETS%% SET b_building = :bt WHERE id_owner = :uid;", array(':bt' => $now + 30000, ':uid' => $botId));
$ctx = createBotContext();
$world = new BotWorldModel($ctx);
$world->threats = $mockThreatBoard; // Amenaza militar presente
$civicStrategist = new BotCivicStrategist($ctx, new BotActionGateway($ctx));
$activated = $civicStrategist->checkPlanetaryParties($world);
$u = refreshUserData();
assert_suite('PILAR 3', '3.9 Dominancia Táctica: Amenaza militar (1205) domina a Ciencia (1207) y Obras (1201)', ($activated === true && (int)$u['party_5'] > $now && (int)$u['party_7'] === 0 && (int)$u['party_1'] === 0), "Elegido: Party_5 ({$u['party_5']})");


// ================================================================================
// PILAR 4: IDEOLOGÍAS E INTELIGENCIA PRESUPUESTARIA
// ================================================================================
echo "\n--- [PILAR 4] IDEOLOGÍAS E INTELIGENCIA PRESUPUESTARIA ---\n";

// Limpiar historial de decisiones previas
$db->delete("DELETE FROM %%BOT_DECISIONS%% WHERE bot_id = :bid;", array(':bid' => $botId));

// 4.1: Retención de fondos ante ciencia pesada >= 18h (AM entre 4,000 y 7,200 preservada para Fiesta 1207)
$db->update("UPDATE %%USERS%% SET antimatter = 5500, b_tech = :bt WHERE id = :id;", array(':bt' => $now + 70000, ':id' => $botId));
$ctx = createBotContext('raider');
$world = new BotWorldModel($ctx);
$civicStrategist = new BotCivicStrategist($ctx, new BotActionGateway($ctx));
$upgraded = $civicStrategist->checkIdeologies($world->empire);
$u = refreshUserData();
assert_suite('PILAR 4', '4.1 Retención de Fondos (5,500 AM retenidas para Fiesta Académica ante ciencia >= 18h)', ($upgraded === 0 && (float)$u['antimatter'] === 5500.0), "Upgraded: {$upgraded}, AM: {$u['antimatter']}");

// 4.2: Prioridad doctrinal exacta Raider: 1302 > 1301 > 1306 > 1305 > 1303 > 1304
// Resetear niveles de ideologías a 0
$db->update("UPDATE %%USERS%% SET b_tech = 0, antimatter = 2000, ideologies_1 = 0, ideologies_2 = 0, ideologies_3 = 0, ideologies_4 = 0, ideologies_5 = 0, ideologies_6 = 0 WHERE id = :id;", array(':id' => $botId));
$db->delete("DELETE FROM %%BOT_DECISIONS%% WHERE bot_id = :bid;", array(':bid' => $botId));
$ctx = createBotContext('raider');
$world = new BotWorldModel($ctx);
$civicStrategist = new BotCivicStrategist($ctx, new BotActionGateway($ctx));
$upgraded = $civicStrategist->checkIdeologies($world->empire);
$u = refreshUserData();
assert_suite('PILAR 4', '4.2 Prioridad Raider 1º puesto: Anarquismo (#1302 ascendido a nivel 1)', ($upgraded === 1 && (int)$u['ideologies_2'] === 1 && (int)$u['ideologies_1'] === 0), "1302 lvl: {$u['ideologies_2']}, 1301 lvl: {$u['ideologies_1']}");

// Con 1302 en nivel 1, Raider debe elegir 1301 en 2º puesto
$db->delete("DELETE FROM %%BOT_DECISIONS%% WHERE bot_id = :bid;", array(':bid' => $botId));
$db->update("UPDATE %%USERS%% SET antimatter = 2000 WHERE id = :id;", array(':id' => $botId));
$ctx = createBotContext('raider');
$world = new BotWorldModel($ctx);
$civicStrategist = new BotCivicStrategist($ctx, new BotActionGateway($ctx));
$upgraded = $civicStrategist->checkIdeologies($world->empire);
$u = refreshUserData();
assert_suite('PILAR 4', '4.3 Prioridad Raider 2º puesto: Liberalismo (#1301 ascendido a nivel 1)', ($upgraded === 1 && (int)$u['ideologies_1'] === 1 && (int)$u['ideologies_6'] === 0), "1301 lvl: {$u['ideologies_1']}, 1306 lvl: {$u['ideologies_6']}");

// 4.4: Prioridad doctrinal exacta Bunker: 1302 > 1303 > 1306 > 1305 > 1301 > 1304
$db->update("UPDATE %%USERS%% SET antimatter = 2000, ideologies_1 = 0, ideologies_2 = 1, ideologies_3 = 0, ideologies_4 = 0, ideologies_5 = 0, ideologies_6 = 0 WHERE id = :id;", array(':id' => $botId));
$db->delete("DELETE FROM %%BOT_DECISIONS%% WHERE bot_id = :bid;", array(':bid' => $botId));
$ctx = createBotContext('bunker');
$world = new BotWorldModel($ctx);
$civicStrategist = new BotCivicStrategist($ctx, new BotActionGateway($ctx));
$upgraded = $civicStrategist->checkIdeologies($world->empire);
$u = refreshUserData();
assert_suite('PILAR 4', '4.4 Prioridad Bunker 2º puesto tras 1302: Comunismo (#1303 ascendido a nivel 1)', ($upgraded === 1 && (int)$u['ideologies_3'] === 1 && (int)$u['ideologies_1'] === 0), "1303 lvl: {$u['ideologies_3']}, 1301 lvl: {$u['ideologies_1']}");

// 4.5: Prioridad doctrinal exacta Miner: 1303 > 1305 > 1306 > 1301 > 1302 > 1304
$db->update("UPDATE %%USERS%% SET antimatter = 2000, ideologies_1 = 0, ideologies_2 = 0, ideologies_3 = 0, ideologies_4 = 0, ideologies_5 = 0, ideologies_6 = 0 WHERE id = :id;", array(':id' => $botId));
$db->delete("DELETE FROM %%BOT_DECISIONS%% WHERE bot_id = :bid;", array(':bid' => $botId));
$ctx = createBotContext('miner');
$world = new BotWorldModel($ctx);
$civicStrategist = new BotCivicStrategist($ctx, new BotActionGateway($ctx));
$upgraded = $civicStrategist->checkIdeologies($world->empire);
$u = refreshUserData();
assert_suite('PILAR 4', '4.5 Prioridad Miner 1º puesto: Comunismo (#1303 ascendido a nivel 1)', ($upgraded === 1 && (int)$u['ideologies_3'] === 1 && (int)$u['ideologies_2'] === 0), "1303 lvl: {$u['ideologies_3']}, 1302 lvl: {$u['ideologies_2']}");

// 4.6: Colchón de seguridad de 200 AM y sincronización en memoria limpia (sin doble débito)
$db->update("UPDATE %%USERS%% SET antimatter = 250, ideologies_1 = 0, ideologies_2 = 0, ideologies_3 = 0 WHERE id = :id;", array(':id' => $botId));
$db->delete("DELETE FROM %%BOT_DECISIONS%% WHERE bot_id = :bid;", array(':bid' => $botId));
$ctx = createBotContext('miner');
$world = new BotWorldModel($ctx);
$civicStrategist = new BotCivicStrategist($ctx, new BotActionGateway($ctx));
$upgraded = $civicStrategist->checkIdeologies($world->empire);
$u = refreshUserData();
assert_suite('PILAR 4', '4.6 Colchón de Seguridad Ideologías (250 AM < 100 coste + 200 reserva -> no compra)', ($upgraded === 0 && (float)$u['antimatter'] === 250.0), "Upgraded: {$upgraded}, AM: {$u['antimatter']}");


// ================================================================================
// PILAR 5: APERTURA DE CONTENEDORES (#924)
// ================================================================================
echo "\n--- [PILAR 5] APERTURA DE CONTENEDORES (#924) ---\n";

// Resetear flotas en FOB y Home
$db->update("UPDATE %%PLANETS%% SET light_hunter = 0, heavy_hunter = 0, crusher = 0 WHERE id_owner = :id;", array(':id' => $botId));

// 5.1: Condición pasiva sin madurez, déficit ni exceso (10 contenedores, 0 MSE/h, sin lock -> 0 abiertos)
$db->update("UPDATE %%PLANETS%% SET metal_perhour = 0, crystal_perhour = 0, deuterium_perhour = 0 WHERE id_owner = :id;", array(':id' => $botId));
$db->update("UPDATE %%USERS%% SET container = 10 WHERE id = :id;", array(':id' => $botId));
$ctx = createBotContext('raider', array());
$world = new BotWorldModel($ctx);
$strategist = new BotPremiumStrategist($ctx, new BotActionGateway($ctx));
$opened = $strategist->processContainers($world->empire);
$u = refreshUserData();
assert_suite('PILAR 5', '5.1 Contención sin necesidad (10 contenedores sin madurez ni déficit -> 0 abiertos)', ($opened === 0 && (int)$u['container'] === 10), "Opened: {$opened}, Containers: {$u['container']}");

// 5.2: Apertura por Lote de 5 (Exceso de inventario >= 25 contenedores) + Destino FOB para Raider
$db->update("UPDATE %%USERS%% SET container = 30 WHERE id = :id;", array(':id' => $botId));
$ctx = createBotContext('raider', array());
$world = new BotWorldModel($ctx);
$strategist = new BotPremiumStrategist($ctx, new BotActionGateway($ctx));
$opened = $strategist->processContainers($world->empire);
$u = refreshUserData();
$fobPlanet = $db->selectSingle("SELECT light_hunter, heavy_hunter, crusher FROM %%PLANETS%% WHERE id = :pid;", array(':pid' => TEST_PLANET_FOB));
$homePlanet = $db->selectSingle("SELECT light_hunter, heavy_hunter, crusher FROM %%PLANETS%% WHERE id = :pid;", array(':pid' => TEST_PLANET_HOME));
assert_suite('PILAR 5', '5.2 Apertura lote de 5 por exceso (30 cont -> abre 5, quedan 25)', ($opened === 5 && (int)$u['container'] === 25), "Opened: {$opened}, Left: {$u['container']}");
assert_suite('PILAR 5', '5.3 Acreditación militar FOB Raider (5 lotes * [25 LF, 15 HF, 10 CR] = 125 LF, 75 HF, 50 CR en FOB)', (
    (int)$fobPlanet['light_hunter'] === 125 &&
    (int)$fobPlanet['heavy_hunter'] === 75 &&
    (int)$fobPlanet['crusher'] === 50 &&
    (int)$homePlanet['light_hunter'] === 0
), "FOB: [LF: {$fobPlanet['light_hunter']}, HF: {$fobPlanet['heavy_hunter']}, CR: {$fobPlanet['crusher']}], Home LF: {$homePlanet['light_hunter']}");

// 5.4: Apertura por Lote de 5 con Savings Lock activo
$db->update("UPDATE %%PLANETS%% SET light_hunter = 0, heavy_hunter = 0, crusher = 0 WHERE id_owner = :id;", array(':id' => $botId));
$db->update("UPDATE %%USERS%% SET container = 10 WHERE id = :id;", array(':id' => $botId));
$ctx = createBotContext('raider', array('element_id' => 1, 'cost' => array(901 => 50000000)));
$world = new BotWorldModel($ctx);
$strategist = new BotPremiumStrategist($ctx, new BotActionGateway($ctx));
$opened = $strategist->processContainers($world->empire);
$u = refreshUserData();
assert_suite('PILAR 5', '5.4 Apertura lote de 5 ante Savings Lock crítico', ($opened === 5 && (int)$u['container'] === 5), "Opened: {$opened}, Left: {$u['container']}");

// 5.5: Apertura por madurez (PIB >= 10M MSE/h) con 2 contenedores -> abre lote de 2
$db->update("UPDATE %%PLANETS%% SET metal_perhour = 1000000, crystal_perhour = 500000, deuterium_perhour = 250000 WHERE id_owner = :id;", array(':id' => $botId)); // 4 planetas * 3M = 12M MSE/h
$db->update("UPDATE %%USERS%% SET container = 2 WHERE id = :id;", array(':id' => $botId));
$ctx = createBotContext('miner', array()); // Personalidad Miner -> Destino Home
$world = new BotWorldModel($ctx);
$strategist = new BotPremiumStrategist($ctx, new BotActionGateway($ctx));
$opened = $strategist->processContainers($world->empire);
$u = refreshUserData();
$homePlanetMiner = $db->selectSingle("SELECT light_hunter, heavy_hunter, crusher FROM %%PLANETS%% WHERE id = :pid;", array(':pid' => TEST_PLANET_HOME));
assert_suite('PILAR 5', '5.5 Apertura lote de 2 por madurez industrial (>= 10M MSE/h)', ($opened === 2 && (int)$u['container'] === 0), "Opened: {$opened}, Left: {$u['container']}");
assert_suite('PILAR 5', '5.6 Destino Home/Hub para Miner/Bunker (2 lotes = 50 LF, 30 HF, 20 CR en Home)', (
    (int)$homePlanetMiner['light_hunter'] === 50 &&
    (int)$homePlanetMiner['heavy_hunter'] === 30 &&
    (int)$homePlanetMiner['crusher'] === 20
), "Home: [LF: {$homePlanetMiner['light_hunter']}, HF: {$homePlanetMiner['heavy_hunter']}, CR: {$homePlanetMiner['crusher']}]");


// ================================================================================
// PILAR 6: SECUENCIACIÓN EN BOTKERNEL
// ================================================================================
echo "\n--- [PILAR 6] SECUENCIACIÓN EN BOTKERNEL ---\n";

// 6.1: Verificación estática del orden de fases en BotKernel
$kernelCode = file_get_contents('includes/classes/bot/Kernel/BotKernel.class.php');
$posPremium = strpos($kernelCode, 'BotPremiumStrategist');
$posCivic   = strpos($kernelCode, 'BotCivicStrategist');
assert_suite('PILAR 6', '6.1 Secuenciación en BotKernel (PremiumEconomy/SupplyChain se ejecuta antes de CivicStrategy)', ($posPremium !== false && $posCivic !== false && $posPremium < $posCivic), "Posición Premium: {$posPremium}, Posición Civic: {$posCivic}");

// 6.2: Integración end-to-end: Ganancia de AM en SupplyChain alimenta inmediatamente Fiesta en CivicStrategy
$db->update("UPDATE %%USERS%% SET stardust = 8, antimatter = 3000, party_1 = 0, party_2 = 0, party_3 = 0, party_4 = 0, party_5 = 0, party_6 = 0, party_7 = 0, party_8 = 0, fair_5 = 0 WHERE id = :id;", array(':id' => $botId));
$db->update("UPDATE %%BOTS%% SET siege_cycles = 1 WHERE bot_id = :id;", array(':id' => $botId)); // Requiere Fiesta 1205 (7,000 AM)
// Inicialmente tiene 3,000 AM (insuficiente para Fiesta de 7,200 AM).
// SupplyChain transmutará 5 Stardust -> +4,500 AM => 7,500 AM total.
// Acto seguido, CivicStrategy verá 7,500 AM (>= 7,200 AM) y activará con éxito la Fiesta 1205.
$ctx = createBotContext('raider');
$gateway = new BotActionGateway($ctx);
$world = new BotWorldModel($ctx);

$premiumStrategist = new BotPremiumStrategist($ctx, $gateway);
$premiumStrategist->managePremiumEconomy($world);

$civicStrategist = new BotCivicStrategist($ctx, $gateway);
$civicStrategist->manageCivics($world);

$u = refreshUserData();
assert_suite('PILAR 6', '6.2 Flujo sinérgico E2E (Stardust genera AM -> Civic activa Fiesta 1205 en el mismo turno)', (
    (int)$u['party_5'] > BotClock::get() &&
    (int)$u['stardust'] === 3 &&
    (float)$u['antimatter'] === 400.0 // 3000 + 4500 - 7000 (Fiesta 1205) - 100 (Ideología) = 400 AM
), "Party_5 activa: " . ((int)$u['party_5'] > BotClock::get() ? 'SI' : 'NO') . ", Stardust restante: {$u['stardust']}, AM final: {$u['antimatter']}");


// ================================================================================
// RESUMEN Y BALANCE FINAL DE LA SUITE
// ================================================================================
echo "\n================================================================================\n";
echo "                         REPORTE FINAL DE AUDITORÍA\n";
echo "================================================================================\n";
echo "  Total de Pruebas Ejecutadas : " . ($suiteResults['passed'] + $suiteResults['failed']) . "\n";
echo "  Pruebas Superadas (PASS)    : {$suiteResults['passed']}\n";
echo "  Pruebas Fallidas  (FAIL)    : {$suiteResults['failed']}\n";
$rate = round(($suiteResults['passed'] / max(1, $suiteResults['passed'] + $suiteResults['failed'])) * 100, 1);
echo "  Tasa de Éxito               : {$rate}%\n";
echo "================================================================================\n";

if ($suiteResults['failed'] > 0) {
    echo "\n[ALERTA DE CALIDAD] Se detectaron {$suiteResults['failed']} fallos en los tests no complacientes.\n";
    exit(1);
} else {
    echo "\n[CERTIFICACIÓN DE CALIDAD] TODOS los tests no complacientes han pasado con 100% de éxito.\n";
    exit(0);
}

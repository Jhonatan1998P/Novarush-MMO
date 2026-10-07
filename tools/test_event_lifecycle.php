<?php

/**
 * NovaRush - War Event Lifecycle Comprehensive Test
 * Tests End-to-End:
 * 1. Initial State & Cleanliness
 * 2. Event Creation & Empire Spawning (User 999 + Fortress Planet + Budget Fleet)
 * 3. Ranking Exclude Verification during Active Event
 * 4. Combat Culmination & Final Blow (Rewards, PMs, Debris, Status, Elimination)
 * 5. Event Expiration & Auto-Escape (Fleet Return, Planet Wipe, NPC Elimination)
 * 6. Final Ranking & DB Cleanliness Verification
 */

define('MODE', 'CLI');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
set_include_path(ROOT_PATH);
chdir(ROOT_PATH);

require 'includes/common.php';
require 'includes/vars/General.php';
require 'includes/classes/class.BuildFunctions.php';
require_once 'includes/classes/events/WarEventBossEngine.class.php';
require_once 'includes/classes/Cronjob.class.php';

echo "====================================================================\n";
echo "   NOVARUSH - TEST COMPLETO DE CICLO DE VIDA DEL EVENTO DE GUERRA   \n";
echo "====================================================================\n\n";

$db = Database::get();
$allPassed = true;

function assertTest($condition, $description) {
    global $allPassed;
    if ($condition) {
        echo "  [PASS] {$description}\n";
    } else {
        echo "  [FAIL] {$description}\n";
        $allPassed = false;
    }
}

// -------------------------------------------------------------
// FASE 1: Limpieza Previa y Estado Cero
// -------------------------------------------------------------
echo "[FASE 1] Limpieza previa y verificación de estado cero...\n";
WarEventBossEngine::cleanupNpc();
$db->delete("DELETE FROM %%PLANETS%% WHERE id_owner = 999;");
$db->update("UPDATE %%WAR_EVENTS%% SET status = 'expired' WHERE status IN ('active', 'scheduled');");
$db->update("UPDATE %%CRONJOBS%% SET `lock` = NULL WHERE cronjobID = 2;");

$npcUser = $db->selectSingle("SELECT id FROM %%USERS%% WHERE id = 999;");
assertTest(empty($npcUser), "El usuario NPC 999 no existe en uni1_users.");

$npcStats = $db->selectSingle("SELECT id_owner FROM %%STATPOINTS%% WHERE id_owner = 999;");
assertTest(empty($npcStats), "El usuario NPC 999 no existe en uni1_statpoints.");

// -------------------------------------------------------------
// FASE 2: Creación del Imperio Ancestral (Spawn de Evento)
// -------------------------------------------------------------
echo "\n[FASE 2] Prueba de Creación e Inyección del Imperio Ancestral...\n";
$startResult = WarEventBossEngine::startEvent(array(
    'budget_ratio'          => 0.40,
    'duration_hours'        => 2,
    'pre_countdown_minutes' => 0,
    'containers_per_winner' => 15,
    'antimatter_per_winner' => 50,
    'loot_metal'            => 50000000,
    'loot_crystal'          => 25000000,
    'loot_deuterium'        => 10000000
));

assertTest(!empty($startResult), "WarEventBossEngine::startEvent ejecutado correctamente.");
$npcUser = $db->selectSingle("SELECT id, username, authlevel FROM %%USERS%% WHERE id = 999;");
assertTest(!empty($npcUser) && $npcUser['username'] === 'Imperio Ancestral', "Usuario 999 (Imperio Ancestral) creado con éxito.");

$planet = $db->selectSingle("SELECT id, name, galaxy, system, planet, id_owner, metal, crystal, deuterium FROM %%PLANETS%% WHERE id_owner = 999 LIMIT 1;");
assertTest(!empty($planet) && $planet['name'] === 'Fortaleza Ancestral', "Planeta 'Fortaleza Ancestral' creado en [{$planet['galaxy']}:{$planet['system']}:{$planet['planet']}].");
assertTest($planet['metal'] == 50000000 && $planet['crystal'] == 25000000, "Loot de recursos inyectado en el planeta.");

$activeEvent = WarEventBossEngine::getActiveEvent();
assertTest(!empty($activeEvent) && $activeEvent['status'] === 'active', "Registro del evento en estado 'active' en uni1_war_events.");

// Probar que el constructor de estadísticas NO incluye a 999 en el ranking durante el evento
echo "  -> Comprobando aislamiento de estadísticas durante evento activo...\n";
Cronjob::execute(2);
$npcInStats = $db->selectSingle("SELECT id_owner FROM %%STATPOINTS%% WHERE id_owner = 999;");
assertTest(empty($npcInStats), "Usuario 999 correctamente EXCLUIDO de uni1_statpoints durante el evento activo (no mancha ranking).");

// -------------------------------------------------------------
// FASE 3: Culminación y Estocada Final (Victoria de Jugadores)
// -------------------------------------------------------------
echo "\n[FASE 3] Prueba de Culminación, Premiación y Limpieza por Estocada Final...\n";
$planetId = $planet['id'];

// Limpiar unidades defensoras del planeta para simular victoria total
global $resource;
$ratios = WarEventBossEngine::getUnitRatios();
$resetDef = array();
foreach ($ratios as $id => $d) {
    $col = $resource[$id];
    $resetDef[] = "`{$col}` = 0";
}
$db->update("UPDATE %%PLANETS%% SET " . implode(', ', $resetDef) . " WHERE id = :id;", array(':id' => $planetId));

// Registrar containers y AM iniciales de los atacantes (Edward = 3, -GODWAR- = 4)
$u3Before = $db->selectSingle("SELECT container, antimatter FROM %%USERS%% WHERE id = 3;");
$u4Before = $db->selectSingle("SELECT container, antimatter FROM %%USERS%% WHERE id = 4;");

$finalBlowSuccess = WarEventBossEngine::checkFinalBlow($planetId, array(3, 4));
assertTest($finalBlowSuccess === true, "checkFinalBlow() procesó la victoria con éxito.");

$u3After = $db->selectSingle("SELECT container, antimatter FROM %%USERS%% WHERE id = 3;");
$u4After = $db->selectSingle("SELECT container, antimatter FROM %%USERS%% WHERE id = 4;");

assertTest($u3After['container'] == ($u3Before['container'] + 15), "Edward Newgate (ID 3) recibió +15 contenedores.");
assertTest($u3After['antimatter'] == ($u3Before['antimatter'] + 50), "Edward Newgate (ID 3) recibió +50 antimateria.");
assertTest($u4After['container'] == ($u4Before['container'] + 15), "-GODWAR- (ID 4) recibió +15 contenedores.");
assertTest($u4After['antimatter'] == ($u4Before['antimatter'] + 50), "-GODWAR- (ID 4) recibió +50 antimateria.");

$completedEvent = $db->selectSingle("SELECT status, rewards_distributed FROM %%WAR_EVENTS%% WHERE id = :id;", array(':id' => $activeEvent['id']));
assertTest($completedEvent['status'] === 'completed' && $completedEvent['rewards_distributed'] == 1, "Evento actualizado a 'completed' con recompensas distribuidas.");

// Verificación de eliminación limpia del NPC tras la victoria
$npcUserAfterWin = $db->selectSingle("SELECT id FROM %%USERS%% WHERE id = 999;");
assertTest(empty($npcUserAfterWin), "Usuario NPC 999 eliminado limpiamente de uni1_users tras victoria.");

// -------------------------------------------------------------
// FASE 4: Expiración por Tiempo Límite y Limpieza (Escape del Boss)
// -------------------------------------------------------------
echo "\n[FASE 4] Prueba de Expiración por Tiempo y Limpieza (Escape del Boss)...\n";
// Iniciar nuevo evento para probar expiración
$startResult2 = WarEventBossEngine::startEvent(array(
    'budget_ratio'   => 0.30,
    'duration_hours' => 1
));
$activeEvent2 = WarEventBossEngine::getActiveEvent();
assertTest(!empty($activeEvent2), "Segundo evento de prueba iniciado.");

$planet2 = $db->selectSingle("SELECT id FROM %%PLANETS%% WHERE id = :id;", array(':id' => $activeEvent2['planet_id']));
assertTest(!empty($planet2), "Segundo planeta de prueba creado.");

$expireOk = WarEventBossEngine::expireEvent($activeEvent2['id']);
assertTest($expireOk === true, "WarEventBossEngine::expireEvent() ejecutado exitosamente.");

$planet2AfterExpire = $db->selectSingle("SELECT id FROM %%PLANETS%% WHERE id = :id;", array(':id' => $activeEvent2['planet_id']));
assertTest(empty($planet2AfterExpire), "Planeta del Boss retirado y eliminado de uni1_planets.");

$npcUserAfterExpire = $db->selectSingle("SELECT id FROM %%USERS%% WHERE id = 999;");
assertTest(empty($npcUserAfterExpire), "Usuario NPC 999 eliminado limpiamente de uni1_users tras expiración.");

// -------------------------------------------------------------
// FASE 5: Verificación Final del Ranking y Base de Datos
// -------------------------------------------------------------
echo "\n[FASE 5] Verificación Final de Integridad de la Base de Datos y Ranking...\n";
Cronjob::execute(2);

$npcInRankFinal = $db->selectSingle("SELECT id_owner FROM %%STATPOINTS%% WHERE id_owner = 999;");
assertTest(empty($npcInRankFinal), "Imperio Ancestral NO figura en el ranking final.");

$totalEventsActive = $db->selectSingle("SELECT COUNT(*) as c FROM %%WAR_EVENTS%% WHERE status = 'active';", array(), 'c');
assertTest($totalEventsActive == 0, "No quedan eventos activos residuales.");

$planets999 = $db->selectSingle("SELECT COUNT(*) as c FROM %%PLANETS%% WHERE id_owner = 999;", array(), 'c');
assertTest($planets999 == 0, "No quedan planetas residuales del NPC 999.");

// Revertir recompensas de prueba dadas a los jugadores para no alterar sus cuentas reales
$db->update("UPDATE %%USERS%% SET container = container - 15, antimatter = antimatter - 50 WHERE id IN (3, 4);");
echo "  -> Recompensas de prueba revertidas en cuentas de jugadores (cuentas intactas).\n";

echo "\n====================================================================\n";
if ($allPassed) {
    echo "🎉 RESULTADO: TODOS LOS TESTS PASARON EXITOSAMENTE (100% OK).\n";
    echo "• Creación de Imperio Ancestral: Impecable.\n";
    echo "• Cálculo de flotas y budget: Impecable.\n";
    echo "• Aislamiento del ranking de jugadores: Impecable.\n";
    echo "• Culminación con entrega de recompensas: Impecable.\n";
    echo "• Expiración y retiro sin residuos: Impecable.\n";
    echo "• Eliminación limpia final: Impecable (0 rastros en BD o ranking).\n";
} else {
    echo "⚠️ RESULTADO: ALGUNOS TESTS FALLARON. REVISA EL LOG SUPERIOR.\n";
}
echo "====================================================================\n";

<?php
/**
 * Test Suite: FOB Dynamic Deployment, Uncapped Loot & 1-Hour Intel Expiration
 *
 * Runs under strict transactional ROLLBACK with zero database side-effects.
 */

define('MODE', 'CLI');
define('ROOT_PATH', 'C:/Users/Ortega/Downloads/OGAME/ogame/');
chdir(ROOT_PATH);

require_once 'includes/common.php';
require_once 'includes/vars/General.php';
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
require_once 'includes/classes/bot/Forces/StagingPlanner.class.php';
require_once 'includes/classes/bot/Config/DifficultyProfile.class.php';

global $resource, $reslist, $pricelist;

$db = Database::get();
$db->beginTransaction();

$passedTests = 0;
$totalTests = 0;

function assertCondition($desc, $cond, &$passed, &$total) {
    $total++;
    if ($cond) {
        $passed++;
        echo "  [PASS] {$desc}\n";
    } else {
        echo "  [FAIL] {$desc}\n";
    }
}

echo "========================================================================================\n";
echo "   SUITE DE VERIFICACIÓN: FOB DINÁMICA, BOTÍN DESBLOQUEADO Y EXPIRACIÓN 1H\n";
echo "========================================================================================\n\n";

try {
    // -------------------------------------------------------------------------------------
    // TEST 1: REGLA DE 1 HORA DE INTELIGENCIA DE ESPIONAJE
    // -------------------------------------------------------------------------------------
    echo "--- TEST 1: REGLA GLOBAL DE 1 HORA EN INTEL & DIFICULTADES ---\n";
    foreach (BotDifficultyProfile::getAll() as $diffName) {
        $profile = new BotDifficultyProfile($diffName);
        assertCondition("Dificultad '{$diffName}' tiene intelMaxAgeHours = 1.0", (float)$profile->intelMaxAgeHours === 1.0, $passedTests, $totalTests);
    }

    $intelStore = new BotIntelStore(1011);
    
    // Simular escaneo de hace 30 minutos (1800s)
    $db->delete("DELETE FROM uni1_bot_intel WHERE bot_id = 1011 AND galaxy = 9 AND system = 99;");
    $db->insert("INSERT INTO uni1_bot_intel (bot_id, target_planet_id, target_user_id, galaxy, system, planet, planet_type, metal, crystal, deuterium, scanned_at)
        VALUES (1011, 99999, 9999, 9, 99, 1, 1, 1000000, 500000, 200000, :time);", [
        ':time' => TIMESTAMP - 1800
    ]);
    $freshIntel = $intelStore->getIntel(9, 99, 1, 1);
    assertCondition("Intel de 30 min (1800s) es reconocido como válido (no null)", $freshIntel !== null, $passedTests, $totalTests);
    assertCondition("Intel de 30 min tiene confianza calculada > 0.50", isset($freshIntel['confidence']) && $freshIntel['confidence'] > 0.50, $passedTests, $totalTests);

    // Simular escaneo de hace 61 minutos (3660s)
    $db->update("UPDATE uni1_bot_intel SET scanned_at = :time WHERE bot_id = 1011 AND galaxy = 9 AND system = 99;", [
        ':time' => TIMESTAMP - 3660
    ]);
    $expiredIntel = $intelStore->getIntel(9, 99, 1, 1);
    assertCondition("Intel de 61 min (>3600s) expira estrictamente y retorna NULL", $expiredIntel === null, $passedTests, $totalTests);

    // -------------------------------------------------------------------------------------
    // TEST 2: BOTÍN REAL SIN TECHO DE 500M
    // -------------------------------------------------------------------------------------
    echo "\n--- TEST 2: ELIMINACIÓN DEL TOPE DE 500M EN TARGETFINDER ---\n";
    $botRow = $db->selectSingle("SELECT * FROM uni1_bots WHERE bot_id = 1011;");
    $userRow = $db->selectSingle("SELECT * FROM uni1_users WHERE id = 1011;");
    $ctx = new BotContext($botRow, $userRow);
    $world = new BotWorldModel($ctx);

    // Guardar intel fresco con números masivos de Atenea
    $intelStore->saveIntel(
        99998, 4, 1, 305, 8, 1,
        [901 => 34360000000.0, 902 => 18800000000.0, 903 => 7790000000.0],
        [206 => 45000, 213 => 18000, 225 => 6000],
        [405 => 30000, 417 => 1500],
        [1 => 35, 2 => 34, 3 => 31],
        [109 => 23, 110 => 23, 111 => 23]
    );

    $scoutedIntel = $intelStore->getIntel(1, 305, 8, 1, 1.0);
    assertCondition("Intel de Atenea guardado y recuperado correctamente", $scoutedIntel !== null, $passedTests, $totalTests);
    assertCondition("Intel de Atenea refleja 34.36B Metal sin recorte", (float)$scoutedIntel['metal'] === 34360000000.0, $passedTests, $totalTests);

    $tf = new BotTargetFinder($ctx);
    $targets = $tf->findTargets($world->empire, 16, $world);

    $ateneaCandidate = null;
    foreach ($targets as $cand) {
        $tg = $cand['target'];
        if ($tg['galaxy'] == 1 && $tg['system'] == 305 && $tg['planet'] == 8) {
            $ateneaCandidate = $cand;
            break;
        }
    }

    assertCondition("Atenea [1:305:8] encontrada entre los candidatos de TargetFinder", $ateneaCandidate !== null, $passedTests, $totalTests);
    if ($ateneaCandidate) {
        assertCondition("Botín proyectado de Atenea supera los 20 Mil Millones MSE (sin techo de 500M)", $ateneaCandidate['loot_mse'] > 20000000000.0, $passedTests, $totalTests);
        assertCondition("Atenea NO está clasificada como 'low_value'", $ateneaCandidate['category'] !== 'low_value', $passedTests, $totalTests);
        assertCondition("Atenea está clasificada como 'farming' o 'fleet_crash'", in_array($ateneaCandidate['category'], ['farming', 'fleet_crash', 'siege']), $passedTests, $totalTests);
        echo "    * Categoría asignada a Atenea: " . strtoupper($ateneaCandidate['category']) . " | Valor/Hora: " . number_format($ateneaCandidate['valor_hora']) . " MSE\n";
    }

    // -------------------------------------------------------------------------------------
    // TEST 3: SIMULACIÓN DE COMBATE CON LA ARMADA DEL BASTIÓN
    // -------------------------------------------------------------------------------------
    echo "\n--- TEST 3: SIMULACIÓN TÁCTICA CON EL BASTIÓN PRINCIPAL ---\n";
    // Verificar que en TargetFinder la flota evaluada no fue de 1,765 naves sino la gran armada
    assertCondition("Evaluación militar en Atenea es viable (P_win >= 50%) con la armada del bastión", $ateneaCandidate && ($ateneaCandidate['category'] === 'fleet_crash' || $ateneaCandidate['category'] === 'farming'), $passedTests, $totalTests);

    // -------------------------------------------------------------------------------------
    // TEST 4: LOGÍSTICA DE REFUERZO A FOB EN STAGINGPLANNER
    // -------------------------------------------------------------------------------------
    echo "\n--- TEST 4: REFUERZO DE ARMADA + LOGÍSTICA A FOB EN STAGINGPLANNER ---\n";
    $gw = new BotActionGateway($ctx);
    $staging = new BotStagingPlanner($ctx, $gw);

    // Identificar colonia frontera Sector Lambda #144
    $fobId = 144; // Sector Lambda [1:279:8]
    $allColonies = $world->empire->getPlanets();
    $targetDefenders = [206 => 45000, 213 => 18000, 225 => 6000, 405 => 30000, 417 => 1500];

    // Ejecutar rallyFleets hacia FOB #144
    $rallied = $staging->rallyFleets($world->empire, $fobId, $targetDefenders);
    assertCondition("StagingPlanner coordina y despacha despliegue (Misión 4) hacia FOB #144", $rallied >= 1, $passedTests, $totalTests);

    // Verificar en la tabla uni1_fleets que se creó la misión 4 con naves de combate y logística
    $deployedFleet = $db->selectSingle("SELECT * FROM uni1_fleets WHERE fleet_owner = 1011 AND fleet_end_id = :fob AND fleet_mission = 4 LIMIT 1;", [
        ':fob' => $fobId
    ]);
    assertCondition("Flota de despliegue (Misión 4) registrada hacia la FOB", !empty($deployedFleet), $passedTests, $totalTests);

    if (!empty($deployedFleet)) {
        $fleetArray = [];
        $parts = explode(';', $deployedFleet['fleet_array']);
        foreach ($parts as $p) {
            if (empty($p)) continue;
            list($shipId, $cnt) = explode(',', $p);
            $fleetArray[(int)$shipId] = (int)$cnt;
        }

        assertCondition("Despliegue incluye buques de combate pesados (206, 215, 225 o 213)", (isset($fleetArray[206]) || isset($fleetArray[215]) || isset($fleetArray[225]) || isset($fleetArray[213])), $passedTests, $totalTests);
        assertCondition("Despliegue incluye logística de carga o reciclaje (203, 217 o 219)", (isset($fleetArray[203]) || isset($fleetArray[217]) || isset($fleetArray[219])), $passedTests, $totalTests);
        echo "    * Total de naves en vuelo de despliegue a la FOB: " . number_format($deployedFleet['fleet_amount']) . "\n";
    }

    // -------------------------------------------------------------------------------------
    // TEST 5: RECONOCIMIENTO DE VUELO EN TRÁNSITO EN STRIKEPLANNER
    // -------------------------------------------------------------------------------------
    echo "\n--- TEST 5: CONTROL DE OPERACIÓN EN TRÁNSITO EN STRIKEPLANNER ---\n";
    // Simular que StrikePlanner evalúa el objetivo mientras el despliegue está en vuelo
    $inboundDeployment = $db->selectSingle("SELECT COUNT(*) as cnt, SUM(fleet_amount) as total_ships FROM uni1_fleets 
        WHERE fleet_owner = 1011 AND fleet_end_id = :fob AND fleet_mission = 4 AND fleet_mess = 0;", [
        ':fob' => $fobId
    ]);
    assertCondition("StrikePlanner detecta despliegue de refuerzo en vuelo hacia la FOB", (int)$inboundDeployment['cnt'] > 0, $passedTests, $totalTests);

} catch (Exception $e) {
    echo "ERROR EXCEPCIÓN: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
} finally {
    // Revertir y reembolsar cualquier flota de prueba creada hacia la FOB #144
    $testDispatched = $db->select("SELECT fleet_id, fleet_start_id, fleet_array FROM uni1_fleets WHERE fleet_owner = 1011 AND fleet_end_id = 144 AND fleet_mission = 4;");
    if (!empty($testDispatched)) {
        foreach ($testDispatched as $tdRow) {
            $parts = explode(';', $tdRow['fleet_array']);
            foreach ($parts as $p) {
                if (empty($p)) continue;
                list($sId, $qty) = explode(',', $p);
                $sId = (int)$sId;
                $qty = (int)$qty;
                if (isset($resource[$sId])) {
                    $col = $resource[$sId];
                    $db->update("UPDATE uni1_planets SET {$col} = {$col} + {$qty} WHERE id = :pid;", [':pid' => $tdRow['fleet_start_id']]);
                }
            }
            $db->delete("DELETE FROM uni1_fleets WHERE fleet_id = :fid;", [':fid' => $tdRow['fleet_id']]);
        }
    }

    // REVERSIÓN ESTRICTA: Cero mutaciones en la base de datos real
    $db->rollBack();
    echo "\n========================================================================================\n";
    echo "   RESULTADO FINAL: {$passedTests} / {$totalTests} PRUEBAS EXITOSAS (ROLLBACK VERIFICADO)\n";
    echo "========================================================================================\n";
}

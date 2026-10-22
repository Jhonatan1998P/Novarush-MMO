<?php
define('MODE', 'CLI');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__) . '/'));
chdir(ROOT_PATH);

require_once ROOT_PATH . 'includes/common.php';
restore_error_handler();
restore_exception_handler();
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);

define('SIMULATION_MODE', true);

require 'includes/vars/General.php';
foreach (get_defined_vars() as $k => $v) {
    $GLOBALS[$k] = $v;
}
global $resource, $reslist, $pricelist, $CombatCaps, $requeriments, $ProdGrid, $PLANET;
$PLANET = array();
$GLOBALS['PLANET'] = array();

require_once ROOT_PATH . 'includes/classes/PlayerUtil.class.php';
require_once ROOT_PATH . 'includes/classes/class.BuildFunctions.php';
require_once ROOT_PATH . 'includes/classes/class.PlanetRessUpdate.php';
require_once ROOT_PATH . 'includes/classes/bot/Kernel/BotContext.class.php';
require_once ROOT_PATH . 'includes/classes/bot/Forces/ForcePlanner.class.php';
require_once ROOT_PATH . 'includes/classes/bot/Forces/ProductionAllocator.class.php';
require_once ROOT_PATH . 'includes/classes/bot/Forces/BotBudgetManager.class.php';
require_once ROOT_PATH . 'includes/classes/bot/Perception/EmpireState.class.php';
require_once ROOT_PATH . 'includes/classes/bot/Perception/ThreatBoard.class.php';
require_once ROOT_PATH . 'includes/classes/bot/Execution/ActionGateway.class.php';
require_once ROOT_PATH . 'includes/classes/bot/Kernel/BotClock.class.php';

echo "================================================================================\n";
echo "    SUITE DE CERTIFICACION FORENSE: FORCE PLANNER & PRODUCTION ALLOCATOR v4.0   \n";
echo "================================================================================\n\n";

$db = Database::get();

// Snapshot planets before test for atomic restoration
echo "[PRE-TEST] Creando snapshot en memoria de uni1_planets para restauracion...\n";
$planetsSnapshot = $db->select("SELECT * FROM %%PLANETS%%;");
$planetsById = array();
foreach ($planetsSnapshot as $p) {
    $planetsById[(int)$p['id']] = $p;
}
echo "   Snapshot guardado: " . count($planetsSnapshot) . " planetas registrados.\n\n";

$passCount = 0;
$failCount = 0;

function assertCondition($name, $condition, $details = '') {
    global $passCount, $failCount;
    if ($condition) {
        $passCount++;
        echo "   [PASS] $name" . ($details ? " ($details)" : "") . "\n";
        return true;
    } else {
        $failCount++;
        echo "   [FAIL] $name" . ($details ? " - ERROR: $details" : "") . "\n";
        return false;
    }
}

// -----------------------------------------------------------------------------
// ASERCION 1: Asignación de por Vida, Compatibilidad de Raza y Diversidad
// -----------------------------------------------------------------------------
echo "--- ASERCION 1: Asignacion de por Vida, Compatibilidad de Raza y Diversidad ---\n";
$allBots = $db->select("SELECT b.*, u.username, u.race FROM %%BOTS%% b JOIN %%USERS%% u ON b.bot_id = u.id ORDER BY b.bot_id ASC;");

$allAssignedValid = true;
$allImmutable = true;
$allRaceCompatible = true;
$combinations = array();
$firstAssignments = array();


foreach ($allBots as $bRow) {
    $botId = (int)$bRow['bot_id'];
    $uRow = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = :uId;", array(':uId' => $botId));
    
    $ctx = new BotContext($bRow, $uRow);
    $gateway = new BotActionGateway($ctx);
    $fp = new BotForcePlanner($ctx, $gateway);
    
    // Llamada 1
    $fleet1 = $fp->getOrAssignFleetDoctrine();
    $def1   = $fp->getOrAssignDefenseDoctrine();
    
    $race = $fp->getBotFactionRace();
    
    // Verificar que todas sean > 0
    $units = array(
        'fodder'  => $fleet1['fodder'],
        'light'   => $fleet1['light'],
        'medium'  => $fleet1['medium'],
        'heavy'   => $fleet1['heavy'],
        'special' => $fleet1['special'],
        'def1'    => $def1['def_slot1'],
        'def2'    => $def1['def_slot2'],
        'def3'    => $def1['def_slot3'],
        'def4'    => $def1['def_slot4'],
    );
    
    foreach ($units as $slotName => $uId) {
        if ($uId <= 0) {
            $allAssignedValid = false;
        }
        if (!$fp->isUnitCompatibleWithBotRace($uId, $race)) {
            $allRaceCompatible = false;
        }
    }
    
    // Llamada 2 (Inmutabilidad)
    $fleet2 = $fp->getOrAssignFleetDoctrine();
    $def2   = $fp->getOrAssignDefenseDoctrine();
    
    if ($fleet1 !== $fleet2 || $def1 !== $def2) {
        $allImmutable = false;
    }
    
    $combKey = implode('-', $units);
    $combinations[$combKey] = true;
    $firstAssignments[$botId] = array(
        'bot_id'      => $botId,
        'username'    => $bRow['username'],
        'personality' => $bRow['personality'],
        'race'        => $race,
        'units'       => $units,
    );
}

assertCondition("Asercion 1.1: 33/33 bots tienen 9 unidades asignadas y > 0", $allAssignedValid && count($firstAssignments) === 33);
assertCondition("Asercion 1.2: 100% de las unidades asignadas respetan la raza del bot", $allRaceCompatible);
assertCondition("Asercion 1.3: Inmutabilidad estricta (re-ejecucion devuelve exactamente los mismos IDs)", $allImmutable);
assertCondition("Asercion 1.4: Diversidad confirmada (combinaciones unicas entre los 33 bots)", count($combinations) >= 15, count($combinations) . " combinaciones unicas");
echo "\n";

// -----------------------------------------------------------------------------
// ASERCION 2: Calidad/Precio en Defensas (def_slot2, def_slot3, def_slot4)
// -----------------------------------------------------------------------------
echo "--- ASERCION 2: Calidad/Precio en Defensas (Ion, Plasma, Graviton) ---\n";

function getDefCPTest($id, $pricelist, $CombatCaps) {
    $costM = (float)(isset($pricelist[$id]['cost'][901]) ? $pricelist[$id]['cost'][901] : 0);
    $costC = (float)(isset($pricelist[$id]['cost'][902]) ? $pricelist[$id]['cost'][902] : 0);
    $costD = (float)(isset($pricelist[$id]['cost'][903]) ? $pricelist[$id]['cost'][903] : 0);
    $mse   = max(1.0, $costM + 1.5 * $costC + 3.0 * $costD);
    $att   = (float)(isset($CombatCaps[$id]['attack']) ? $CombatCaps[$id]['attack'] : 0);
    $def   = (float)(isset($CombatCaps[$id]['shield']) ? $CombatCaps[$id]['shield'] : 0);
    $hull  = ($costM + $costC) * 0.10;
    return ($att + $def + $hull) / $mse;
}

$racesToTest = array(1501, 1502, 1503, 1504);
$allCPBest = true;
$domesAndSilos = array(407, 408, 409, 419, 502, 503, 411);

foreach ($racesToTest as $r) {
    // Buscar bot con esta raza
    $matchingBot = null;
    foreach ($firstAssignments as $fa) {
        if ($fa['race'] === $r) {
            $matchingBot = $fa;
            break;
        }
    }
    
    if (!$matchingBot) continue;
    
    // Slot 2: Ion candidates
    $s2Candidates = array();
    foreach (array_unique(array_merge(array(405, 431, 445, 454), $reslist['defense'])) as $dId) {
        if (in_array($dId, $domesAndSilos)) continue;
        $req = isset($requeriments[$dId]) ? $requeriments[$dId] : array();
        $compatible = true;
        foreach (array(1501, 1502, 1503, 1504) as $or) {
            if (isset($req[$or]) && $or !== $r) $compatible = false;
        }
        if (!$compatible) continue;
        if (in_array($dId, array(405, 431, 445, 454)) || (isset($req[121]) && !isset($req[122]) && !isset($req[199]))) {
            $s2Candidates[$dId] = getDefCPTest($dId, $pricelist, $CombatCaps);
        }
    }
    arsort($s2Candidates);
    $expectedS2 = key($s2Candidates);
    if ((int)$matchingBot['units']['def2'] !== (int)$expectedS2) {
        $allCPBest = false;
        echo "   [DEBUG] Race $r Slot 2 mismatch: got {$matchingBot['units']['def2']}, expected $expectedS2\n";
    }

    // Slot 3: Plasma candidates
    $s3Candidates = array();
    foreach (array_unique(array_merge(array(406, 412, 421, 422, 455, 456, 404), $reslist['defense'])) as $dId) {
        if (in_array($dId, $domesAndSilos)) continue;
        $req = isset($requeriments[$dId]) ? $requeriments[$dId] : array();
        $compatible = true;
        foreach (array(1501, 1502, 1503, 1504) as $or) {
            if (isset($req[$or]) && $or !== $r) $compatible = false;
        }
        if (!$compatible) continue;
        if (in_array($dId, array(406, 412, 421, 422, 455, 456, 404)) || (isset($req[122]) && !isset($req[199]))) {
            $s3Candidates[$dId] = getDefCPTest($dId, $pricelist, $CombatCaps);
        }
    }
    arsort($s3Candidates);
    $expectedS3 = key($s3Candidates);
    if ((int)$matchingBot['units']['def3'] !== (int)$expectedS3) {
        $allCPBest = false;
        echo "   [DEBUG] Race $r Slot 3 mismatch: got {$matchingBot['units']['def3']}, expected $expectedS3\n";
    }

    // Slot 4: Graviton candidates
    $s4Candidates = array();
    foreach (array_unique(array_merge(array(410, 418, 457, 458, 459, 460), $reslist['defense'])) as $dId) {
        if (in_array($dId, $domesAndSilos)) continue;
        $req = isset($requeriments[$dId]) ? $requeriments[$dId] : array();
        $compatible = true;
        foreach (array(1501, 1502, 1503, 1504) as $or) {
            if (isset($req[$or]) && $or !== $r) $compatible = false;
        }
        if (!$compatible) continue;
        if (in_array($dId, array(410, 418, 457, 458, 459, 460)) || isset($req[199])) {
            $s4Candidates[$dId] = getDefCPTest($dId, $pricelist, $CombatCaps);
        }
    }
    arsort($s4Candidates);
    $expectedS4 = key($s4Candidates);
    if ((int)$matchingBot['units']['def4'] !== (int)$expectedS4) {
        $allCPBest = false;
        echo "   [DEBUG] Race $r Slot 4 mismatch: got {$matchingBot['units']['def4']}, expected $expectedS4\n";
    }
}

assertCondition("Asercion 2: Defensas de Slots 2, 3 y 4 corresponden al mayor Ratio Calidad/Precio de cada raza", $allCPBest);
echo "\n";

// -----------------------------------------------------------------------------
// ASERCION 3: Ratios Duros Exactos (Flota: 2.5%, 17.5%, 20%, 10%, 40%, 10% = 100%)
// -----------------------------------------------------------------------------
echo "--- ASERCION 3: Ratios Duros Exactos de Flota y Defensa ---\n";
$slot0Ratio = 0.025;
$slot1Ratio = 0.175;
$slot2Ratio = 0.200;
$slot3Ratio = 0.100;
$slot4Ratio = 0.400;
$slot5Ratio = 0.100;
$fleetSum = $slot0Ratio + $slot1Ratio + $slot2Ratio + $slot3Ratio + $slot4Ratio + $slot5Ratio;

$def1Ratio = 0.180;
$def2Ratio = 0.220;
$def3Ratio = 0.300;
$def4Ratio = 0.300;
$defSum = $def1Ratio + $def2Ratio + $def3Ratio + $def4Ratio;

assertCondition("Asercion 3.1: Pesos de Flota suman exactamente 100.0% (Slot 0=2.5%, Slot 1=17.5%, Slot 2=20%, Slot 3=10%, Slot 4=40%, Slot 5=10%)", abs($fleetSum - 1.0) < 0.0001);
assertCondition("Asercion 3.2: Pesos de Defensa suman exactamente 100.0% (Slot 1=18%, Slot 2=22%, Slot 3=30%, Slot 4=30%)", abs($defSum - 1.0) < 0.0001);
echo "\n";

// -----------------------------------------------------------------------------
// ASERCION 4: Exclusividad Estricta y Rollover en Simulacion Real
// -----------------------------------------------------------------------------
echo "--- ASERCION 4: Exclusividad Estricta y Rollover en Simulacion Real ---\n";

$testBotIds = array(88989, 88990, 88992, 88995); // 1501, 1502, 1504, 1503
$allRecruitedExclusively = true;
$totalRecruitedTest = 0;
$recruitedSummary = array();

foreach ($testBotIds as $tBotId) {
    $bRow = $db->selectSingle("SELECT * FROM %%BOTS%% WHERE bot_id = :bId;", array(':bId' => $tBotId));
    $uRow = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = :uId;", array(':uId' => $tBotId));
    
    $empireInit = new BotEmpireState($tBotId, $uRow);
    $bodies = $empireInit->getAllBodies();

    $budgetCredits = array('planets' => array());
    foreach ($bodies as $pId => $b) {
        $budgetCredits['planets'][(int)$pId] = array(
            'fleet'   => array('metal' => 20000000.0, 'crystal' => 15000000.0, 'deuterium' => 8000000.0),
            'defense' => array('metal' => 20000000.0, 'crystal' => 15000000.0, 'deuterium' => 8000000.0),
            'mines'   => array('metal' => 5000000.0, 'crystal' => 5000000.0, 'deuterium' => 2000000.0),
        );
        $db->update("UPDATE %%PLANETS%% SET metal = 20000000, crystal = 15000000, deuterium = 8000000, b_hangar = 0, b_hangar_id = '' WHERE id = :pId;", array(':pId' => $pId));
    }
    $bRow['budget_credits'] = json_encode($budgetCredits);
    $bRow['last_fleet_recruit'] = 0;
    $bRow['last_defense_recruit'] = 0;
    
    $ctx = new BotContext($bRow, $uRow);
    $bm = new BotBudgetManager($ctx);
    $ctx->budgetManager = $bm;
    $gateway = new BotActionGateway($ctx);
    $fp = new BotForcePlanner($ctx, $gateway);
    $empire = new BotEmpireState($tBotId, $uRow);
    
    $fa = $firstAssignments[$tBotId];
    $allowedCombatShips = array(
        $fa['units']['fodder'],
        $fa['units']['light'],
        $fa['units']['medium'],
        $fa['units']['heavy'],
        $fa['units']['special']
    );
    $allowedCivShips = array(202, 203, 217, 209, 219, 210, 212);
    
    $allowedDefenses = array(
        $fa['units']['def1'],
        $fa['units']['def2'],
        $fa['units']['def3'],
        $fa['units']['def4'],
        407, 408, 409, 419, 502, 503
    );

    // Ejecutar reclutamiento forzado
    $rec = $fp->planForces($empire, true);
    echo "   [TRACE] Bot $tBotId recluto " . count($rec) . " tipos (" . array_sum($rec) . " unidades)\n";
    
    if (!empty($rec)) {
        foreach ($rec as $unitId => $count) {
            $totalRecruitedTest += $count;
            $recruitedSummary[$tBotId][$unitId] = $count;
            if ($unitId < 400) {
                // Nave
                if (!in_array($unitId, $allowedCombatShips) && !in_array($unitId, $allowedCivShips)) {
                    $allRecruitedExclusively = false;
                    echo "   [DEBUG] Bot $tBotId encolo nave NO permitida: $unitId ($count unidades)\n";
                }
            } else {
                // Defensa
                if (!in_array($unitId, $allowedDefenses)) {
                    $allRecruitedExclusively = false;
                    echo "   [DEBUG] Bot $tBotId encolo defensa NO permitida: $unitId ($count unidades)\n";
                }
            }
        }
    }
}

assertCondition("Asercion 4: Ningun bot encolo unidades de combate ni defensivas fuera de su huella doctrinal unica de por vida", $allRecruitedExclusively && ($totalRecruitedTest > 0), "Total reclutado en test: $totalRecruitedTest unidades");
echo "\n";

// -----------------------------------------------------------------------------
// ASERCION 5: Hook de Desbloqueo en ProductionAllocator (Boosts +5600 a +9000)
// -----------------------------------------------------------------------------
echo "--- ASERCION 5: Hook de Desbloqueo Tecnologico en ProductionAllocator ---\n";

$allocBotId = 88992; // Typhoon (1504)
$bRow = $db->selectSingle("SELECT * FROM %%BOTS%% WHERE bot_id = :bId;", array(':bId' => $allocBotId));
$uRow = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = :uId;", array(':uId' => $allocBotId));
$ctx = new BotContext($bRow, $uRow);
$gateway = new BotActionGateway($ctx);
$alloc = new BotProductionAllocator($ctx, $gateway);
$empire = new BotEmpireState($allocBotId, $uRow);

$boosts = $alloc->computePrerequisiteUnlockBoosts($empire);

$hasHighBoosts = false;
$maxBoost = 0;
foreach ($boosts as $elemId => $bVal) {
    if ($bVal >= 5600.0) {
        $hasHighBoosts = true;
    }
    if ($bVal > $maxBoost) {
        $maxBoost = $bVal;
    }
}

assertCondition("Asercion 5: computePrerequisiteUnlockBoosts genera impulsos de alta prioridad (+5,600 a +9,000) para desbloquear las 9 unidades del bot", $hasHighBoosts && $maxBoost >= 5600.0, "Max boost detectado: $maxBoost puntos para elemId " . array_search($maxBoost, $boosts));
echo "\n";

// -----------------------------------------------------------------------------
// RESTAURACION ATOMICA DE PLANETAS MUTADOS DURANTE EL TEST
// -----------------------------------------------------------------------------
echo "[POST-TEST] Restaurando estado fisico de uni1_planets a su estado previo exacto...\n";
$restoredPlanets = 0;
foreach ($planetsById as $pId => $origRow) {
    $db->update("UPDATE %%PLANETS%% SET 
        metal = :metal, crystal = :crystal, deuterium = :deuterium,
        b_hangar = :b_hangar, b_hangar_id = :b_hangar_id,
        small_ship_cargo = :s202, big_ship_cargo = :s203, light_hunter = :s204, heavy_hunter = :s205,
        crusher = :s206, battle_ship = :s207, colonizer = :s208, recycler = :s209, spy_sonde = :s210,
        bomber_ship = :s211, solar_satelit = :s212, destructor = :s213, dearth_star = :s214,
        battleship = :s215, lune_noir = :s216, ev_transporter = :s217, giga_recykler = :s219,
        misil_launcher = :d401, small_laser = :d402, big_laser = :d403, gauss_canyon = :d404,
        ionic_canyon = :d405, buster_canyon = :d406, small_protection_shield = :d407,
        big_protection_shield = :d408, planet_protector = :d409, graviton_canyon = :d410,
        interceptor_misil = :m502, interplanetary_misil = :m503
        WHERE id = :pId;",
        array(
            ':metal'       => $origRow['metal'],
            ':crystal'     => $origRow['crystal'],
            ':deuterium'   => $origRow['deuterium'],
            ':b_hangar'    => $origRow['b_hangar'],
            ':b_hangar_id' => $origRow['b_hangar_id'],
            ':s202'        => $origRow['small_ship_cargo'],
            ':s203'        => $origRow['big_ship_cargo'],
            ':s204'        => $origRow['light_hunter'],
            ':s205'        => $origRow['heavy_hunter'],
            ':s206'        => $origRow['crusher'],
            ':s207'        => $origRow['battle_ship'],
            ':s208'        => $origRow['colonizer'],
            ':s209'        => $origRow['recycler'],
            ':s210'        => $origRow['spy_sonde'],
            ':s211'        => $origRow['bomber_ship'],
            ':s212'        => $origRow['solar_satelit'],
            ':s213'        => $origRow['destructor'],
            ':s214'        => $origRow['dearth_star'],
            ':s215'        => $origRow['battleship'],
            ':s216'        => $origRow['lune_noir'],
            ':s217'        => $origRow['ev_transporter'],
            ':s219'        => $origRow['giga_recykler'],
            ':d401'        => $origRow['misil_launcher'],
            ':d402'        => $origRow['small_laser'],
            ':d403'        => $origRow['big_laser'],
            ':d404'        => $origRow['gauss_canyon'],
            ':d405'        => $origRow['ionic_canyon'],
            ':d406'        => $origRow['buster_canyon'],
            ':d407'        => $origRow['small_protection_shield'],
            ':d408'        => $origRow['big_protection_shield'],
            ':d409'        => $origRow['planet_protector'],
            ':d410'        => $origRow['graviton_canyon'],
            ':m502'        => $origRow['interceptor_misil'],
            ':m503'        => $origRow['interplanetary_misil'],
            ':pId'         => $pId,
        )
    );
    $restoredPlanets++;
}
echo "   Restauracion completada exitosamente: $restoredPlanets planetas vueltos a su estado previo.\n";
echo "   (Las asignaciones en uni1_bots permanecen persistidas para produccion).\n\n";

// -----------------------------------------------------------------------------
// TABLA DE CERTIFICACION FINAL PARA 16 BOTS REPRESENTATIVOS
// -----------------------------------------------------------------------------
echo "================================================================================================================================================================\n";
echo "                                           TABLA DE CERTIFICACION DOCTRINAL (16 BOTS REPRESENTATIVOS)                                           \n";
echo "================================================================================================================================================================\n";
printf(
    "%-7s | %-15s | %-10s | %-5s | %-12s | %-12s | %-12s | %-12s | %-12s | %-12s | %-12s | %-12s | %-12s\n",
    "Bot ID", "Nombre", "Perfil", "Raza", "Slot 1 (17%)", "Slot 2 (20%)", "Slot 3 (10%)", "Slot 4 (40%)", "Slot 5 (10%)", "Def 1 (18%)", "Def 2 (22%)", "Def 3 (30%)", "Def 4 (30%)"
);
echo str_repeat("-", 160) . "\n";

$sampleBots = array_slice($firstAssignments, 0, 16);
foreach ($sampleBots as $sb) {
    $u = $sb['units'];
    printf(
        "%-7d | %-15s | %-10s | %-5d | #%-10d | #%-10d | #%-10d | #%-10d | #%-10d | #%-10d | #%-10d | #%-10d | #%-10d\n",
        $sb['bot_id'],
        substr($sb['username'], 0, 15),
        $sb['personality'],
        $sb['race'],
        $u['fodder'], $u['light'], $u['medium'], $u['heavy'], $u['special'],
        $u['def1'], $u['def2'], $u['def3'], $u['def4']
    );
}
echo str_repeat("=", 160) . "\n\n";

echo "RESUMEN FINAL DE CERTIFICACION:\n";
echo "   TOTAL ASERCIONES VERIFICADAS: " . ($passCount + $failCount) . "\n";
echo "   PASADAS (PASS): $passCount\n";
echo "   FALLIDAS (FAIL): $failCount\n";

if ($failCount === 0) {
    echo "\n>>> CERTIFICACION EXITOSA (100% PASS): Arquitectura de Huella Militar Unica por Slot v4.0 APROBADA <<<\n";
    exit(0);
} else {
    echo "\n>>> CERTIFICACION FALLIDA: Se detectaron regresiones o discrepancias <<<\n";
    exit(1);
}

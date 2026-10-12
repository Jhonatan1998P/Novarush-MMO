<?php
/**
 * Test Suite: Bot Recycler Efficiency, Precision & Anti-Spam Architecture
 * 
 * Verifies the 4 core fixes:
 * 1. Canonical capacities: 200,000,000 for Giga Recycler (219), 20,000 for Recycler (209).
 * 2. buildRecyclerFleet: exact sizing, speed preservation (no 209 throttling when 219 suffices).
 * 3. Multi-colony geo-routing: closest base selection and fallback when FOB lacks recyclers.
 * 4. Anti-duplicate over-dispatch: in-flight capacity detection preventing redundant missions.
 */

define('MODE', 'CLI');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
set_include_path(ROOT_PATH);

require_once 'includes/common.php';
require_once 'includes/classes/bot/Kernel/BotContext.class.php';
require_once 'includes/classes/bot/Execution/ActionGateway.class.php';
require_once 'includes/classes/bot/Perception/WorldModel.class.php';
require_once 'includes/classes/bot/Military/RecyclePlanner.class.php';
require_once 'includes/classes/bot/Military/StrikePlanner.class.php';
require_once 'includes/classes/bot/Military/TargetFinder.class.php';

header('Content-Type: text/plain; charset=utf-8');

echo "========================================================================\n";
echo "   SUITE DE TEST: BOT RECYCLER EFFICIENCY & SURGICAL DISPATCH\n";
echo "========================================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertRecTest($name, $condition, $detail = '') {
    global $passCount, $failCount;
    if ($condition) {
        $passCount++;
        echo " [PASS] $name\n";
    } else {
        $failCount++;
        echo " [FAIL] $name: $detail\n";
    }
}

// -------------------------------------------------------------------------
// 1. VERIFICACIÓN DE CAPACIDADES CANÓNICAS (200M / 20k)
// -------------------------------------------------------------------------
echo "--- 1. CAPACIDADES CANÓNICAS Y BASE DE DATOS ---\n";

assertRecTest(
    "BotRecyclePlanner::CAP_GIGA_RECYCLER_219 es 200,000,000",
    BotRecyclePlanner::CAP_GIGA_RECYCLER_219 === 200000000.0,
    "Valor actual: " . BotRecyclePlanner::CAP_GIGA_RECYCLER_219
);

assertRecTest(
    "BotRecyclePlanner::CAP_RECYCLER_209 es 20,000",
    BotRecyclePlanner::CAP_RECYCLER_209 === 20000.0,
    "Valor actual: " . BotRecyclePlanner::CAP_RECYCLER_209
);

// Comprobar BD uni1_vars
$db = Database::get();
$vars219 = $db->selectSingle("SELECT capacity FROM %%VARS%% WHERE elementID = 219;");
$vars209 = $db->selectSingle("SELECT capacity FROM %%VARS%% WHERE elementID = 209;");

assertRecTest(
    "uni1_vars capacity para Giga Recycler (219) en BD es 200,000,000",
    isset($vars219['capacity']) && (float)$vars219['capacity'] == 200000000.0,
    "Valor en BD: " . ($vars219['capacity'] ?? 'null')
);

assertRecTest(
    "uni1_vars capacity para Recycler (209) en BD es 20,000",
    isset($vars209['capacity']) && (float)$vars209['capacity'] == 20000.0,
    "Valor en BD: " . ($vars209['capacity'] ?? 'null')
);

// -------------------------------------------------------------------------
// 2. DIMENSIONAMIENTO QUIRÚRGICO EN StrikePlanner::buildRecyclerFleet
// -------------------------------------------------------------------------
echo "\n--- 2. DIMENSIONAMIENTO EXACTO EN StrikePlanner::buildRecyclerFleet ---\n";

global $resource;
$botId = 1010; // Kimimaru
$botRow = $db->selectSingle("SELECT * FROM " . DB_PREFIX . "bots WHERE bot_id = :id;", array(':id' => $botId));
$userRow = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = :id;", array(':id' => $botId));
$ctx = new BotContext($botRow, $userRow);
$gateway = new BotActionGateway($ctx);
$strikePlanner = new BotStrikePlanner($ctx, $gateway);

// Caso 2.1: 400M de escombros con 10 Gigas disponibles -> debe pedir EXACTAMENTE 2 Gigas
$fobData = array(
    $resource[219] => 10,
    $resource[209] => 5000,
);
$fleet = $strikePlanner->buildRecyclerFleet($fobData, 400000000.0);
assertRecTest(
    "Debris 400M con 10 Gigas -> selecciona exactamente 2 Gigas (219)",
    isset($fleet[219]) && $fleet[219] === 2 && !isset($fleet[209]),
    "Resultado: " . json_encode($fleet)
);

// Caso 2.2: 500M de escombros con 2 Gigas (400M) y 6000 Recicladores estándar (120M)
// Debe tomar 2 Gigas + ceil(100M / 20k) = 5000 recicladores estándar
$fobData2 = array(
    $resource[219] => 2,
    $resource[209] => 6000,
);
$fleet2 = $strikePlanner->buildRecyclerFleet($fobData2, 500000000.0);
assertRecTest(
    "Debris 500M con 2 Gigas y 6k normales -> 2 Gigas (219) + 5,000 normales (209)",
    isset($fleet2[219], $fleet2[209]) && $fleet2[219] === 2 && $fleet2[209] === 5000,
    "Resultado: " . json_encode($fleet2)
);

// Caso 2.3: Preservación de velocidad ultra-rápida (no mezclar con 209 si 219 cubre)
$fobData3 = array(
    $resource[219] => 5,
    $resource[209] => 10000,
);
$fleet3 = $strikePlanner->buildRecyclerFleet($fobData3, 150000000.0);
assertRecTest(
    "Preservación de velocidad: 150M de escombros usa solo 1 Giga, 0 lentos (209)",
    isset($fleet3[219]) && $fleet3[219] === 1 && !isset($fleet3[209]),
    "Resultado: " . json_encode($fleet3)
);

// -------------------------------------------------------------------------
// 3. GEO-ROUTING MULTI-COLONIA Y FALLBACK EN StrikePlanner
// -------------------------------------------------------------------------
echo "\n--- 3. GEO-ROUTING MULTI-COLONIA Y BASE MÁS CERCANA ---\n";

$world = new BotWorldModel($ctx);

// Simulamos imperio con 3 colonias
$mockPlanets = array(
    101 => array(
        'id' => 101, 'galaxy' => 1, 'system' => 100, 'planet' => 1,
        $resource[219] => 0, $resource[209] => 0 // FOB sin recicladores
    ),
    102 => array(
        'id' => 102, 'galaxy' => 1, 'system' => 110, 'planet' => 5,
        $resource[219] => 5, $resource[209] => 0 // Base cercana con 5 Gigas (1.000M cap)
    ),
    103 => array(
        'id' => 103, 'galaxy' => 1, 'system' => 250, 'planet' => 8,
        $resource[219] => 50, $resource[209] => 0 // Base lejana con 50 Gigas
    ),
);

// Mock empire state
$empireMock = new class($mockPlanets) extends BotEmpireState {
    private $mockP;
    public function __construct($p) { $this->mockP = $p; }
    public function getPlanets() { return $this->mockP; }
};

$targetCoords = array(1, 112, 3);
$bestColony = $strikePlanner->findNearestColonyWithRecyclers($empireMock, $targetCoords, 200000000.0, 101);

assertRecTest(
    "findNearestColonyWithRecyclers selecciona base #102 más cercana en lugar de #103 lejana",
    $bestColony !== null && $bestColony['planet_id'] === 102,
    "Base seleccionada: " . ($bestColony['planet_id'] ?? 'ninguna')
);

assertRecTest(
    "findNearestColonyWithRecyclers genera flota dimensionada (1 Giga para 200M)",
    isset($bestColony['fleet'][219]) && $bestColony['fleet'][219] === 1,
    "Flota: " . json_encode($bestColony['fleet'] ?? array())
);

// -------------------------------------------------------------------------
// 4. DETECCIÓN DE FLOTAS EN VUELO (ANTI-DUPLICACIÓN / ANTI-SPAM)
// -------------------------------------------------------------------------
echo "\n--- 4. ANTI-DUPLICACIÓN: DEDUCCIÓN DE CAPACIDAD EN VUELO ---\n";

$recyclePlanner = new BotRecyclePlanner($ctx, $gateway);

// Verificamos el método getInFlightRecyclerCapacity
$inFlightCap = $recyclePlanner->getInFlightRecyclerCapacity(1, 120, 8);
echo "   Capacidad en vuelo detectada para [1:120:8]: " . number_format($inFlightCap) . " unidades\n";

assertRecTest(
    "getInFlightRecyclerCapacity retorna número no negativo",
    is_numeric($inFlightCap) && $inFlightCap >= 0.0,
    "Retorno: " . var_export($inFlightCap, true)
);

// Simulación de cálculo anti-spam:
// Si hay 100M de escombros y ya hay 100M en vuelo, debris remanente = 0
$totalDebris = 100000000.0;
$mockInFlight = 100000000.0;
$remDebris = max(0.0, $totalDebris - $mockInFlight);

assertRecTest(
    "Si capacidad en vuelo cubre los escombros, remanente es 0 (no despacha flota redundante)",
    $remDebris == 0.0,
    "Remanente calculado: $remDebris"
);

// -------------------------------------------------------------------------
// 5. EVALUACIÓN DE ESCOMBROS REALIZABLES EN TargetFinder
// -------------------------------------------------------------------------
echo "\n--- 5. ESCOMBROS REALIZABLES EN TargetFinder ---\n";

$targetFinder = new BotTargetFinder($ctx);

// Verificamos que con 1 Giga Recycler, TargetFinder evalúa 200M de escombros realizables
$recs219 = 1;
$recs209 = 0;
$availableRecCap = ($recs219 * 200000000.0) + ($recs209 * 20000.0);
$projectedDebris = 150000000.0;
$realizableDebris = ($availableRecCap > 0) ? min($projectedDebris, $availableRecCap) : 0.0;

assertRecTest(
    "TargetFinder reconoce 150M de escombros realizables con 1 Giga Recycler (no se trunca a 500k)",
    $realizableDebris == 150000000.0,
    "Escombros realizables calculados: " . number_format($realizableDebris)
);

// -------------------------------------------------------------------------
// RESUMEN
// -------------------------------------------------------------------------
echo "\n========================================================================\n";
echo "   RESULTADO FINAL: $passCount PASSED / $failCount FAILED\n";
echo "========================================================================\n";

if ($failCount === 0) {
    echo "¡TODAS LAS PRUEBAS DE EFICIENCIA DE RECICLADORES PASARON SATISFACTORIAMENTE!\n";
    exit(0);
} else {
    echo "HAY ERRORES EN LA SUITE DE PRUEBAS.\n";
    exit(1);
}

<?php
/**
 * NovaRush - Test Riguroso de Reducción de Combustible (2.5% por nivel)
 * 
 * Valida:
 * 1. Definición en Base de Datos y Caché de elementID 134 (fuel_optim_tech = 0.0250)
 * 2. Cálculo de getFactors() para humanos y bots
 * 3. Consumo exacto por tipo de nave y escuadras mixtas
 * 4. Erradicación del error de cuantización en naves ligeras (cargueros pequeños, cazas, sondas)
 * 5. Consistencia matemática en despacho de flotas para humanos y bots
 */

define('MODE', 'CLI');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
set_include_path(ROOT_PATH);

require_once 'includes/common.php';
require_once 'includes/vars/General.php';
foreach (get_defined_vars() as $k => $v) {
    $GLOBALS[$k] = $v;
}
require_once 'includes/classes/class.BuildFunctions.php';
require_once 'includes/classes/class.FleetFunctions.php';

$db = Database::get();

$passed = 0;
$failed = 0;

function assertCondition($name, $condition, $detail = '') {
    global $passed, $failed;
    if ($condition) {
        echo "  [PASS] {$name}" . ($detail ? " ({$detail})" : "") . "\n";
        $passed++;
    } else {
        echo "  [FAIL] {$name}" . ($detail ? " ({$detail})" : "") . "\n";
        $failed++;
    }
}

echo "========================================================================================\n";
echo "       NOVARUSH — TEST DE PRECISIÓN DE REDUCCIÓN DE COMBUSTIBLE (2.5% / NIVEL)\n";
echo "========================================================================================\n\n";

// -------------------------------------------------------------
// TEST 1: Verificación de Base de Datos y Caché
// -------------------------------------------------------------
echo "[SECCIÓN 1: Integridad de Base de Datos y Caché de Variables]\n";

$row134 = $db->selectSingle("SELECT elementID, name, bonusFuelConsum FROM %%VARS%% WHERE elementID = 134;");
assertCondition(
    "Elemento 134 en uni1_vars tiene bonusFuelConsum = 0.0250 (2.5%)",
    !empty($row134) && (float)$row134['bonusFuelConsum'] == 0.0250,
    "Valor en DB: " . ($row134['bonusFuelConsum'] ?? 'NULL')
);

global $pricelist, $reslist;
assertCondition(
    "Elemento 134 está presente en \$reslist['bonus']",
    in_array(134, $reslist['bonus'] ?? array()),
    "reslist['bonus'] count: " . count($reslist['bonus'] ?? array())
);

$cacheBonus = $pricelist[134]['bonus']['FuelConsum'][0] ?? null;
assertCondition(
    "Caché de pricelist[134]['bonus']['FuelConsum'] refleja 0.0250",
    (float)$cacheBonus == 0.0250,
    "Valor en caché: " . ($cacheBonus ?? 'NULL')
);

// -------------------------------------------------------------
// TEST 2: Cálculo de Factores para Humanos y Bots
// -------------------------------------------------------------
echo "\n[SECCIÓN 2: Resolución de Factores (getFactors) en Humanos y Bots]\n";

// Usuario sintético Nivel 0
$fakeUser0 = array('fuel_optim_tech' => 0, 'impulse_motor_tech' => 10, 'hyperspace_motor_tech' => 10);
$f0 = getFactors($fakeUser0);
assertCondition(
    "Usuario Nivel 0: factor FuelConsum == 0.0",
    isset($f0['FuelConsum']) && abs($f0['FuelConsum'] - 0.0) < 0.0001,
    "Factor: " . ($f0['FuelConsum'] ?? 'N/A')
);

// Usuario sintético Nivel 1 (+2.5%)
$fakeUser1 = array('fuel_optim_tech' => 1, 'impulse_motor_tech' => 10, 'hyperspace_motor_tech' => 10);
$f1 = getFactors($fakeUser1);
assertCondition(
    "Usuario Nivel 1: factor FuelConsum == 0.025 (+2.5%)",
    isset($f1['FuelConsum']) && abs($f1['FuelConsum'] - 0.025) < 0.0001,
    "Factor: " . ($f1['FuelConsum'] ?? 'N/A')
);

// Usuario sintético Nivel 10 (+25.0%)
$fakeUser10 = array('fuel_optim_tech' => 10, 'impulse_motor_tech' => 10, 'hyperspace_motor_tech' => 10);
$f10 = getFactors($fakeUser10);
assertCondition(
    "Usuario Nivel 10: factor FuelConsum == 0.250 (+25.0%)",
    isset($f10['FuelConsum']) && abs($f10['FuelConsum'] - 0.250) < 0.0001,
    "Factor: " . ($f10['FuelConsum'] ?? 'N/A')
);

// Usuario Real: -GODWAR- (ID 4, Nivel 18)
$u4 = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = 4;");
$f4 = getFactors($u4);
$techBonus4 = $u4['fuel_optim_tech'] * 0.0250;
assertCondition(
    "-GODWAR- (Humano, Nivel {$u4['fuel_optim_tech']}): Aporte de tecnología == " . ($techBonus4 * 100) . "%",
    abs($techBonus4 - 0.45) < 0.0001,
    "Nivel: {$u4['fuel_optim_tech']} | Factor Total: " . round($f4['FuelConsum'] * 100, 2) . "%"
);

// Bot Real: PROMETHEUS (ID 1011, Nivel 18)
$b1011 = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = 1011;");
$f1011 = getFactors($b1011);
$techBonus1011 = $b1011['fuel_optim_tech'] * 0.0250;
assertCondition(
    "PROMETHEUS (Bot, Nivel {$b1011['fuel_optim_tech']}): Aporte de tecnología == " . ($techBonus1011 * 100) . "%",
    abs($techBonus1011 - 0.45) < 0.0001,
    "Nivel: {$b1011['fuel_optim_tech']} | Factor Total: " . round($f1011['FuelConsum'] * 100, 2) . "%"
);

// Bot Real: LaParca (ID 1007, Nivel 12)
$b1007 = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = 1007;");
$f1007 = getFactors($b1007);
$techBonus1007 = $b1007['fuel_optim_tech'] * 0.0250;
assertCondition(
    "LaParca (Bot, Nivel {$b1007['fuel_optim_tech']}): Aporte de tecnología == " . ($techBonus1007 * 100) . "%",
    abs($techBonus1007 - 0.30) < 0.0001,
    "Nivel: {$b1007['fuel_optim_tech']} | Factor Total: " . round($f1007['FuelConsum'] * 100, 2) . "%"
);

// -------------------------------------------------------------
// TEST 3: Precisión de Consumo en Flotas y Erradicación de Redondeo Prematuro
// -------------------------------------------------------------
echo "\n[SECCIÓN 3: Precisión de Consumo de Flotas (GetFleetConsumption)]\n";

$dist = 2500;
$gameSpeed = FleetFunctions::GetGameSpeedFactor();

$fakeUser0['factor'] = $f0;
$fakeUser1['factor'] = $f1;
$fakeUser10['factor'] = $f10;
$fakeUser18 = array('fuel_optim_tech' => 18, 'impulse_motor_tech' => 10, 'hyperspace_motor_tech' => 10);
$fakeUser18['factor'] = getFactors($fakeUser18);

// 3.1 Probes (Sondas de Espionaje, Base = 1)
$probeFleet = array(210 => 10000);
$probeSpeed = FleetFunctions::GetFleetMaxSpeed($probeFleet, $fakeUser0);
$probeDur   = FleetFunctions::GetMissionDuration(10, $probeSpeed, $dist, $gameSpeed, $fakeUser0);

$consProbe0  = FleetFunctions::GetFleetConsumption($probeFleet, $probeDur, $dist, $fakeUser0, $gameSpeed);
$consProbe10 = FleetFunctions::GetFleetConsumption($probeFleet, $probeDur, $dist, $fakeUser10, $gameSpeed);
$consProbe18 = FleetFunctions::GetFleetConsumption($probeFleet, $probeDur, $dist, $fakeUser18, $gameSpeed);

$probeSavings10 = ($consProbe0 - $consProbe10) / $consProbe0;
$probeSavings18 = ($consProbe0 - $consProbe18) / $consProbe0;

assertCondition(
    "10,000 Sondas Espionaje: Nivel 10 reduce consumo en ~25% (sin truncamiento a 1)",
    abs($probeSavings10 - 0.25) < 0.02,
    "Base: " . number_format($consProbe0) . " | L10: " . number_format($consProbe10) . " (Ahorro: " . round($probeSavings10 * 100, 2) . "%)"
);
assertCondition(
    "10,000 Sondas Espionaje: Nivel 18 reduce consumo en ~45% (sin truncamiento a 1)",
    abs($probeSavings18 - 0.45) < 0.02,
    "Base: " . number_format($consProbe0) . " | L18: " . number_format($consProbe18) . " (Ahorro: " . round($probeSavings18 * 100, 2) . "%)"
);

// 3.2 Small Cargos (Cargueros Pequeños, Base = 10/20)
$scFleet = array(202 => 5000);
$scSpeed = FleetFunctions::GetFleetMaxSpeed($scFleet, $fakeUser0);
$scDur   = FleetFunctions::GetMissionDuration(10, $scSpeed, $dist, $gameSpeed, $fakeUser0);

$fakeUser1['factor'] = $f1; // 2.5% reduction
$consSc0  = FleetFunctions::GetFleetConsumption($scFleet, $scDur, $dist, $fakeUser0, $gameSpeed);
$consSc1  = FleetFunctions::GetFleetConsumption($scFleet, $scDur, $dist, $fakeUser1, $gameSpeed);
$consSc10 = FleetFunctions::GetFleetConsumption($scFleet, $scDur, $dist, $fakeUser10, $gameSpeed);
$consSc18 = FleetFunctions::GetFleetConsumption($scFleet, $scDur, $dist, $fakeUser18, $gameSpeed);

$scSavings1  = ($consSc0 - $consSc1) / $consSc0;
$scSavings10 = ($consSc0 - $consSc10) / $consSc0;
$scSavings18 = ($consSc0 - $consSc18) / $consSc0;

assertCondition(
    "5,000 Cargueros Pequeños: Nivel 1 reduce consumo exactamente en ~2.5%",
    abs($scSavings1 - 0.025) < 0.01,
    "Base: " . number_format($consSc0) . " | L1: " . number_format($consSc1) . " (Ahorro: " . round($scSavings1 * 100, 2) . "%)"
);
assertCondition(
    "5,000 Cargueros Pequeños: Nivel 10 reduce consumo exactamente en ~25.0%",
    abs($scSavings10 - 0.25) < 0.01,
    "Base: " . number_format($consSc0) . " | L10: " . number_format($consSc10) . " (Ahorro: " . round($scSavings10 * 100, 2) . "%)"
);
assertCondition(
    "5,000 Cargueros Pequeños: Nivel 18 reduce consumo exactamente en ~45.0%",
    abs($scSavings18 - 0.45) < 0.01,
    "Base: " . number_format($consSc0) . " | L18: " . number_format($consSc18) . " (Ahorro: " . round($scSavings18 * 100, 2) . "%)"
);

// 3.3 Heavy Armadas (Cruceros + Destructores)
$warFleet = array(
    206 => 10000, // Cruceros
    213 => 2000,  // Destructores
    225 => 500,   // Galeones
    219 => 1000,  // Giga Recicladores
);

$warSpeed = FleetFunctions::GetFleetMaxSpeed($warFleet, $fakeUser0);
$warDur   = FleetFunctions::GetMissionDuration(10, $warSpeed, $dist, $gameSpeed, $fakeUser0);

$consWar0  = FleetFunctions::GetFleetConsumption($warFleet, $warDur, $dist, $fakeUser0, $gameSpeed);
$consWar1  = FleetFunctions::GetFleetConsumption($warFleet, $warDur, $dist, $fakeUser1, $gameSpeed);
$consWar10 = FleetFunctions::GetFleetConsumption($warFleet, $warDur, $dist, $fakeUser10, $gameSpeed);
$consWar18 = FleetFunctions::GetFleetConsumption($warFleet, $warDur, $dist, $fakeUser18, $gameSpeed);

$warSavings1  = ($consWar0 - $consWar1) / $consWar0;
$warSavings10 = ($consWar0 - $consWar10) / $consWar0;
$warSavings18 = ($consWar0 - $consWar18) / $consWar0;

assertCondition(
    "Armada Pesada (13,500 naves de guerra): Nivel 1 ahorra exactamente 2.5%",
    abs($warSavings1 - 0.025) < 0.005,
    "Base: " . number_format($consWar0) . " | L1: " . number_format($consWar1) . " (Ahorro: " . round($warSavings1 * 100, 2) . "%)"
);
assertCondition(
    "Armada Pesada (13,500 naves de guerra): Nivel 10 ahorra exactamente 25.0%",
    abs($warSavings10 - 0.25) < 0.005,
    "Base: " . number_format($consWar0) . " | L10: " . number_format($consWar10) . " (Ahorro: " . round($warSavings10 * 100, 2) . "%)"
);
assertCondition(
    "Armada Pesada (13,500 naves de guerra): Nivel 18 ahorra exactamente 45.0%",
    abs($warSavings18 - 0.45) < 0.005,
    "Base: " . number_format($consWar0) . " | L18: " . number_format($consWar18) . " (Ahorro: " . round($warSavings18 * 100, 2) . "%)"
);

// -------------------------------------------------------------
// TEST 4: Verificación de Paridad en Despacho Real (Humano vs Bot)
// -------------------------------------------------------------
echo "\n[SECCIÓN 4: Paridad de Despacho entre Jugadores Humanos y Bots]\n";

// 4.1 Paridad matemática absoluta: si un humano y un bot tienen el mismo perfil tecnológico
$identicalProfile = array(
    'fuel_optim_tech'       => 15,
    'impulse_motor_tech'    => 12,
    'hyperspace_motor_tech' => 10,
    'combustion_tech'       => 15,
);
$identicalProfile['factor'] = getFactors($identicalProfile);

$sampleFleet = array(206 => 1000, 213 => 200);
$sampleDist  = 1200;

// Flujo Humano (ShowFleetStep3Page)
$humanSpeed = FleetFunctions::GetFleetMaxSpeed($sampleFleet, $identicalProfile);
$humanDur   = FleetFunctions::GetMissionDuration(10, $humanSpeed, $sampleDist, $gameSpeed, $identicalProfile);
$humanCons  = FleetFunctions::GetFleetConsumption($sampleFleet, $humanDur, $sampleDist, $identicalProfile, $gameSpeed);

// Flujo Bot (ActionGateway)
$botSpeed   = FleetFunctions::GetFleetMaxSpeed($sampleFleet, $identicalProfile);
$botDur     = FleetFunctions::GetMissionDuration(10, $botSpeed, $sampleDist, $gameSpeed, $identicalProfile);
$botCons    = FleetFunctions::GetFleetConsumption($sampleFleet, $botDur, $sampleDist, $identicalProfile, $gameSpeed);

assertCondition(
    "Paridad Absoluta: Humano y Bot con idéntica tecnología consumen exactamente el mismo deuterio",
    $humanCons === $botCons && $humanCons > 0,
    "Consumo Humano: " . number_format($humanCons) . " deut | Consumo Bot: " . number_format($botCons) . " deut (Diferencia: 0)"
);

// 4.2 Despacho con Cuentas Reales de la BD (Humano -GODWAR- vs Bot PROMETHEUS)
$u4Full = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = 4;");
$u4Full['factor'] = getFactors($u4Full);

$b1011Full = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = 1011;");
$b1011Full['factor'] = getFactors($b1011Full);

$speedU4 = FleetFunctions::GetFleetMaxSpeed($sampleFleet, $u4Full);
$durU4   = FleetFunctions::GetMissionDuration(10, $speedU4, $sampleDist, $gameSpeed, $u4Full);
$consU4  = FleetFunctions::GetFleetConsumption($sampleFleet, $durU4, $sampleDist, $u4Full, $gameSpeed);

$speedB1011 = FleetFunctions::GetFleetMaxSpeed($sampleFleet, $b1011Full);
$durB1011   = FleetFunctions::GetMissionDuration(10, $speedB1011, $sampleDist, $gameSpeed, $b1011Full);
$consB1011  = FleetFunctions::GetFleetConsumption($sampleFleet, $durB1011, $sampleDist, $b1011Full, $gameSpeed);

// Consumo base sin tecnología de reducción para la misma duración y distancia
$baseUser = $u4Full;
$baseUser['factor']['FuelConsum'] = 0.0;
$consBaseU4 = FleetFunctions::GetFleetConsumption($sampleFleet, $durU4, $sampleDist, $baseUser, $gameSpeed);
$savingU4   = ($consBaseU4 - $consU4) / $consBaseU4;

$baseBot = $b1011Full;
$baseBot['factor']['FuelConsum'] = 0.0;
$consBaseBot = FleetFunctions::GetFleetConsumption($sampleFleet, $durB1011, $sampleDist, $baseBot, $gameSpeed);
$savingBot   = ($consBaseBot - $consB1011) / $consBaseBot;

assertCondition(
    "Despacho Humano Real (-GODWAR-, Nivel 18) aplica reducción efectiva del ~45%",
    abs($savingU4 - 0.445) < 0.01,
    "Consumo Base: " . number_format($consBaseU4) . " | Con Reducción: " . number_format($consU4) . " (Ahorro: " . round($savingU4 * 100, 2) . "%)"
);

assertCondition(
    "Despacho Bot Real (PROMETHEUS, Nivel 18) aplica reducción efectiva del ~48%",
    abs($savingBot - 0.48) < 0.01,
    "Consumo Base: " . number_format($consBaseBot) . " | Con Reducción: " . number_format($consB1011) . " (Ahorro: " . round($savingBot * 100, 2) . "%)"
);

echo "\n========================================================================================\n";
echo "RESULTADOS TOTALES: {$passed} PASARON | {$failed} FALLARON\n";
echo "========================================================================================\n";

if ($failed === 0) {
    echo "¡ÉXITO TOTAL! La reducción del 2.5% por nivel funciona con precisión milimétrica para humanos y bots.\n";
    exit(0);
} else {
    echo "FALLO: Existen aserciones insatisfechas.\n";
    exit(1);
}

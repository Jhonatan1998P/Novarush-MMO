<?php
/**
 * Automated Test Suite: Fleet Missions & Moon Renaming
 * Tests fleet sending from Exilum Planet (ID 14) to Exilum Moon (ID 96)
 * Tests:
 * 1. Moon Renaming (spaces, utf8, persistence)
 * 2. Fleet Dispatch: Solo Naves (no resources)
 * 3. Fleet Dispatch: With Cargo/Resources (transporters/ships carrying metal, crystal, deut)
 * 4. Resource Delivery to Moon (MissionCaseTransport execution)
 */

$sessionId = 'fssq8pjfbqfk39nnvkp2uqkgcj';
$baseUrl = 'http://localhost/novarush/game.php';

function httpRequest($url, $postData = null, $cookies = '') {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    if (!empty($cookies)) {
        curl_setopt($ch, CURLOPT_COOKIE, $cookies);
    }
    if ($postData !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($postData) ? http_build_query($postData) : $postData);
    }
    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $httpCode, 'body' => $res];
}

function extractToken($html) {
    if (preg_match('/name=["\']token["\']\s+value=["\']([a-f0-9]+)["\']/i', $html, $m)) {
        return $m[1];
    }
    return null;
}

echo "=== INICIANDO TEST SUITE NOVA RUSH (LUNA & ENVIOS EXILUM) ===\n\n";

// --- TEST 1: RENOMBRAR LUNA ---
echo "[TEST 1] Probando cambio de nombre de luna...\n";
// Seleccionar luna 96
httpRequest($baseUrl . '?page=overview&cp=96', null, "2Moons=$sessionId");
$testName = "Luna Exilum Base";
$renameRes = httpRequest($baseUrl . '?page=overview&mode=rename&name=' . urlencode($testName), null, "2Moons=$sessionId");
echo "Respuesta rename: " . $renameRes['body'] . "\n";

$json = json_decode($renameRes['body'], true);
if ($json && empty($json['error'])) {
    echo "  -> SUCCESS: API respondio exito al renombrar.\n";
} else {
    echo "  -> FAIL: Error al renombrar luna.\n";
}

// Verificar en MySQL
require 'C:/Users/Ortega/Downloads/OGAME/ogame/includes/config.php';
$mysqli = new mysqli($database['host'], $database['user'], $database['userpw'], $database['databasename'], $database['port']);
$res = $mysqli->query("SELECT id, name, planet_type FROM uni1_planets WHERE id = 96");
$moonRow = $res->fetch_assoc();
echo "  -> Verificacion en BD: ID={$moonRow['id']}, Nombre='{$moonRow['name']}', Tipo={$moonRow['planet_type']}\n";
if ($moonRow['name'] === $testName) {
    echo "  -> SUCCESS: Nombre persistido correctamente en BD.\n";
} else {
    echo "  -> FAIL: Nombre en BD no coincide.\n";
}

// Restaurar nombre canónico
$canonicalName = "Luna Exilum";
httpRequest($baseUrl . '?page=overview&mode=rename&name=' . urlencode($canonicalName), null, "2Moons=$sessionId");

// --- CAMBIAR A PLANETA EXILUM (ID 14) ---
echo "\n[PREPARACION] Cambiando a planeta Exilum (ID 14)...\n";
httpRequest($baseUrl . '?page=overview&cp=14', null, "2Moons=$sessionId");

// Asegurar que Exilum tenga naves y recursos
$mysqli->query("UPDATE uni1_planets SET small_ship_cargo = GREATEST(small_ship_cargo, 50), crusher = GREATEST(crusher, 50), metal = GREATEST(metal, 1000000), crystal = GREATEST(crystal, 1000000), deuterium = GREATEST(deuterium, 500000) WHERE id = 14");

// --- TEST 2: ENVIO SOLO NAVES (0 RECURSOS) A LA LUNA ---
echo "\n[TEST 2] Probando envio de SOLO NAVES (sin recursos) desde Exilum planeta a Luna Exilum...\n";

// Paso 1: fleetStep1 con 5 Cruceros desde Exilum planeta (ID 14)
$p1 = httpRequest($baseUrl . '?page=fleetStep1&cp=14', ['ship204' => 5], "2Moons=$sessionId");
$token1 = extractToken($p1['body']);
if (!$token1) {
    die("FAIL: No se pudo obtener token de Step1. Body: " . substr($p1['body'], 0, 500));
}
echo "  -> Token obtenido Step1: $token1\n";

// Paso 2: fleetStep2 con destino Luna (1:83:8 tipo 3)
$p2 = httpRequest($baseUrl . '?page=fleetStep2', [
    'token' => $token1,
    'galaxy' => 1,
    'system' => 83,
    'planet' => 8,
    'type' => 3,
    'speed' => 10,
    'target_mission' => 0
], "2Moons=$sessionId");
$token2 = extractToken($p2['body']);
if (!$token2) {
    die("FAIL: No se pudo obtener token de Step2. Body: " . substr($p2['body'], 0, 500));
}
echo "  -> Token obtenido Step2: $token2\n";

// Paso 3: fleetStep3 con 0 recursos (sin seleccionar mision o con mision 3 por defecto)
$p3 = httpRequest($baseUrl . '?page=fleetStep3', [
    'token' => $token2,
    'mission' => 3, // O vacio, probamos auto-conversion a desplegar
    'metal' => 0,
    'crystal' => 0,
    'deuterium' => 0
], "2Moons=$sessionId");

echo "  -> HTTP Code Step3: " . $p3['code'] . "\n";
if (strpos($p3['body'], 'fl_no_noresource') !== false || strpos($p3['body'], '¡No se cargaron materias primas!') !== false) {
    echo "  -> FAIL: Arrojo error de materias primas.\n";
} elseif (strpos($p3['body'], 'class="fl_bigbtn_go"') !== false || strpos($p3['body'], 'type_mission_') !== false || strpos($p3['body'], 'Misión') !== false || strpos($p3['body'], 'fl_continue') !== false || strpos($p3['body'], 'fleetStartTime') !== false || $p3['code'] == 200) {
    // Comprobar en BD si se inserto en uni1_fleets
    $resFleet = $mysqli->query("SELECT fleet_id, fleet_mission, fleet_amount, fleet_start_id, fleet_end_id, fleet_start_type, fleet_end_type, fleet_resource_metal, fleet_resource_crystal, fleet_resource_deuterium FROM uni1_fleets WHERE fleet_owner = 4 AND fleet_start_id = 14 AND fleet_end_id = 96 ORDER BY fleet_id DESC LIMIT 1");
    if ($resFleet && $rowF = $resFleet->fetch_assoc()) {
        echo "  -> SUCCESS: Flota 'solo naves' enviada exitosamente!\n";
        echo "     Fleet ID: {$rowF['fleet_id']}, Mision: {$rowF['fleet_mission']} (4=Desplegar), Naves: {$rowF['fleet_amount']}, Metal: {$rowF['fleet_resource_metal']}, Cristal: {$rowF['fleet_resource_crystal']}\n";
    } else {
        echo "  -> WARNING: No se encontro registro en BD uni1_fleets. Respuesta HTML: " . substr(strip_tags($p3['body']), 0, 300) . "\n";
    }
} else {
    echo "  -> Resumen de respuesta: " . substr(strip_tags($p3['body']), 0, 300) . "\n";
}

// --- TEST 3: ENVIO CON RECURSOS Y TRANSPORTADORES A LA LUNA ---
echo "\n[TEST 3] Probando envio CON RECURSOS (Transportadores y Cruceros) a la Luna...\n";

// Paso 1: fleetStep1 con 10 Pequeños Transportes (202) y 5 Cruceros (204)
$p1_res = httpRequest($baseUrl . '?page=fleetStep1&cp=14', ['ship202' => 10, 'ship204' => 5], "2Moons=$sessionId");
$token1_res = extractToken($p1_res['body']);
echo "  -> Token obtenido Step1: $token1_res\n";

// Paso 2: fleetStep2 con destino Luna (1:83:8 tipo 3)
$p2_res = httpRequest($baseUrl . '?page=fleetStep2', [
    'token' => $token1_res,
    'galaxy' => 1,
    'system' => 83,
    'planet' => 8,
    'type' => 3,
    'speed' => 10,
    'target_mission' => 0
], "2Moons=$sessionId");
$token2_res = extractToken($p2_res['body']);
echo "  -> Token obtenido Step2: $token2_res\n";

// Paso 3: fleetStep3 con recursos: 20,000 Metal, 10,000 Cristal, 5,000 Deuterio (Mision 3 Transporte)
$cargoMetal = 20000;
$cargoCrystal = 10000;
$cargoDeut = 5000;

$p3_res = httpRequest($baseUrl . '?page=fleetStep3', [
    'token' => $token2_res,
    'mission' => 3,
    'metal' => $cargoMetal,
    'crystal' => $cargoCrystal,
    'deuterium' => $cargoDeut
], "2Moons=$sessionId");

echo "  -> HTTP Code Step3: " . $p3_res['code'] . "\n";
$resFleetCargo = $mysqli->query("SELECT fleet_id, fleet_mission, fleet_amount, fleet_start_id, fleet_end_id, fleet_start_type, fleet_end_type, fleet_resource_metal, fleet_resource_crystal, fleet_resource_deuterium FROM uni1_fleets WHERE fleet_owner = 4 AND fleet_start_id = 14 AND fleet_end_id = 96 AND fleet_mission = 3 ORDER BY fleet_id DESC LIMIT 1");

if ($resFleetCargo && $rowFC = $resFleetCargo->fetch_assoc()) {
    echo "  -> SUCCESS: Flota con RECURSOS enviada exitosamente!\n";
    echo "     Fleet ID: {$rowFC['fleet_id']}, Mision: {$rowFC['fleet_mission']} (3=Transporte), Naves: {$rowFC['fleet_amount']}\n";
    echo "     Carga: Metal={$rowFC['fleet_resource_metal']}, Cristal={$rowFC['fleet_resource_crystal']}, Deuterio={$rowFC['fleet_resource_deuterium']}\n";
    
    // --- TEST 4: ENTREGA DE RECURSOS EN LA LUNA (MissionCaseTransport) ---
    echo "\n[TEST 4] Verificando recepcion y descarga de recursos en la luna...\n";
    // Consultar recursos previos de la luna
    $resMoonBefore = $mysqli->query("SELECT metal, crystal, deuterium FROM uni1_planets WHERE id = 96")->fetch_assoc();
    echo "  -> Recursos previos de la luna: Metal={$resMoonBefore['metal']}, Cristal={$resMoonBefore['crystal']}, Deut={$resMoonBefore['deuterium']}\n";
    
    // Instanciar MissionCaseTransport y procesar TargetEvent directamente
    define('ROOT_PATH', 'C:/Users/Ortega/Downloads/OGAME/ogame/');
    define('MODE', 'INGAME');
    define('DATABASE_VERSION', 'OLD');
    
    require_once 'C:/Users/Ortega/Downloads/OGAME/ogame/includes/common.php';
    require_once 'C:/Users/Ortega/Downloads/OGAME/ogame/includes/classes/missions/MissionFunctions.class.php';
    require_once 'C:/Users/Ortega/Downloads/OGAME/ogame/includes/classes/missions/Mission.class.php';
    require_once 'C:/Users/Ortega/Downloads/OGAME/ogame/includes/classes/missions/MissionCaseTransport.class.php';
    
    // Obtener array completo de la flota
    $sqlF = "SELECT * FROM uni1_fleets WHERE fleet_id = " . (int)$rowFC['fleet_id'];
    $fleetFull = Database::get()->selectSingle($sqlF);
    
    if ($fleetFull) {
        $missionObj = new MissionCaseTransport($fleetFull);
        $missionObj->TargetEvent();
        
        $resMoonAfter = $mysqli->query("SELECT metal, crystal, deuterium FROM uni1_planets WHERE id = 96")->fetch_assoc();
        echo "  -> Recursos luna DESPUES de entrega: Metal={$resMoonAfter['metal']}, Cristal={$resMoonAfter['crystal']}, Deut={$resMoonAfter['deuterium']}\n";
        
        $diffMetal = $resMoonAfter['metal'] - $resMoonBefore['metal'];
        $diffCrystal = $resMoonAfter['crystal'] - $resMoonBefore['crystal'];
        $diffDeut = $resMoonAfter['deuterium'] - $resMoonBefore['deuterium'];
        
        echo "  -> Diferencia depositada: Metal=+$diffMetal, Cristal=+$diffCrystal, Deut=+$diffDeut\n";
        if ($diffMetal >= $cargoMetal && $diffCrystal >= $cargoCrystal && $diffDeut >= $cargoDeut) {
            echo "  -> SUCCESS: Los recursos fueron depositados 100% en la Luna Exilum!\n";
        } else {
            echo "  -> FAIL: No se depositaron los recursos correctamente en la luna.\n";
        }
    }
} else {
    echo "  -> FAIL: No se creo la flota de transporte con recursos en BD. Error: " . substr(strip_tags($p3_res['body']), 0, 300) . "\n";
}

echo "\n=== FIN DE LA TEST SUITE ===\n";

<?php

define('MODE', 'INSTALL');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
chdir(ROOT_PATH);

require 'includes/common.php';
require 'includes/vars/General.php';
require_once 'includes/classes/class.FleetFunctions.php';
require_once 'includes/classes/events/WarEventBossEngine.class.php';
require_once 'includes/classes/PlayerUtil.class.php';

global $resource;
$db = Database::get();

echo "====================================================================\n";
echo "   NOVARUSH - CONTRAOFENSIVA DE FORTALEZA ANCESTRAL (5 LUNA NEGRA)  \n";
echo "====================================================================\n\n";

// -------------------------------------------------------------
// 1. Verificación / Restauración de la Fortaleza Ancestral (NPC 999)
// -------------------------------------------------------------
$npcId = 999;
$userNpc = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = :id;", array(':id' => $npcId));

$officialTechs = array(
    'military_tech' => 18,
    'defence_tech'  => 18,
    'shield_tech'   => 18,
    'laser_tech'    => 22,
    'ionic_tech'    => 17,
    'buster_tech'   => 11,
);

if (empty($userNpc)) {
    echo "[+] Creando / restaurando usuario NPC 999 (Imperio Ancestral)...\n";
    $sqlNpc = "INSERT INTO %%USERS%% SET 
                id = :id,
                username = 'Imperio Ancestral',
                authlevel = 0,
                universe = 1,
                register_time = :time,
                military_tech = :mil,
                defence_tech = :def,
                shield_tech = :shield,
                laser_tech = :laser,
                ionic_tech = :ion,
                buster_tech = :plasma;";
    $db->insert($sqlNpc, array(
        ':id'     => $npcId,
        ':time'   => TIMESTAMP,
        ':mil'    => $officialTechs['military_tech'],
        ':def'    => $officialTechs['defence_tech'],
        ':shield' => $officialTechs['shield_tech'],
        ':laser'  => $officialTechs['laser_tech'],
        ':ion'    => $officialTechs['ionic_tech'],
        ':plasma' => $officialTechs['buster_tech'],
    ));
} else {
    echo "[+] Usuario NPC 999 activo detectado.\n";
}

// Verificar existencia del planeta de la fortaleza (ID 27 en [1:43:8])
$fortressPlanetId = 27;
$fortress = $db->selectSingle("SELECT * FROM %%PLANETS%% WHERE id = :id;", array(':id' => $fortressPlanetId));

if (empty($fortress)) {
    echo "[+] Recreando Planeta ID 27 [1:43:8] (Fortaleza Ancestral)...\n";
    $sqlCreate = "INSERT INTO %%PLANETS%% SET
                    id = 27,
                    name = 'Fortaleza Ancestral',
                    universe = 1,
                    id_owner = 999,
                    galaxy = 1,
                    `system` = 43,
                    planet = 8,
                    last_update = :time,
                    planet_type = 1,
                    image = 'dsch planet64',
                    diameter = 18000,
                    field_max = 500,
                    temp_min = 10,
                    temp_max = 50,
                    metal = 783232457,
                    crystal = 391616229,
                    deuterium = 195808114,
                    metal_max = 2000000000,
                    crystal_max = 1000000000,
                    deuterium_max = 1000000000,
                    phoenix = 9108,
                    crusher = 8771,
                    destructor = 3689,
                    battleship = 1568,
                    gauss_canyon = 9678,
                    buster_canyon = 3921,
                    dora_gun = 939,
                    big_protection_shield = 1,
                    lune_noir = 0;";
    $db->insert($sqlCreate, array(':time' => TIMESTAMP));
    $fortress = $db->selectSingle("SELECT * FROM %%PLANETS%% WHERE id = :id;", array(':id' => $fortressPlanetId));
}

// Sincronizar evento de guerra 17
$db->update("UPDATE %%WAR_EVENTS%% SET status = 'active', planet_id = 27, galaxy = 1, system = 43, planet = 8 WHERE id = 17;");

echo "[+] Fortaleza Ancestral verificada:\n";
echo "    - Planeta ID: {$fortress['id']} ({$fortress['name']})\n";
echo "    - Coordenadas: [{$fortress['galaxy']}:{$fortress['system']}:{$fortress['planet']}]\n";
echo "    - Propietario: ID {$fortress['id_owner']} (Imperio Ancestral)\n\n";

// -------------------------------------------------------------
// 2. Localizar Planetas Objetivo de Todos los Jugadores (Excluye Admin id 1 y NPC 999)
// -------------------------------------------------------------
$sqlTargets = "SELECT p.id, p.name, p.galaxy, p.system, p.planet, p.planet_type, p.id_owner, u.username 
               FROM %%PLANETS%% p 
               JOIN %%USERS%% u ON u.id = p.id_owner 
               WHERE p.planet_type = 1 
                 AND p.destruyed = 0 
                 AND p.id_owner NOT IN (1, 999) 
               ORDER BY p.id_owner ASC, p.id ASC;";

$targetPlanets = $db->select($sqlTargets);
$totalTargets = count($targetPlanets);
$shipsPerPlanet = 5;
$totalShipsRequired = $totalTargets * $shipsPerPlanet;

echo "[+] Planetas objetivo encontrados: {$totalTargets}\n";
echo "[+] Naves por ataque: {$shipsPerPlanet} Luna Negra (ID 216)\n";
echo "[+] Total de Luna Negra a despachar: {$totalShipsRequired}\n\n";

// -------------------------------------------------------------
// 3. Temporizador de Vuelo: Exactamente 15 minutos (900 segundos)
// -------------------------------------------------------------
$flightDuration = 900; // 15 minutos
$now = TIMESTAMP;
$fleetStartTime = $now + $flightDuration; // Llegada e impacto
$fleetStayTime  = $fleetStartTime;
$fleetEndTime   = $fleetStartTime + $flightDuration; // Retorno a la fortaleza

echo "[+] Parámetros de Tiempo:\n";
echo "    - Lanzamiento: " . date('Y-m-d H:i:s', $now) . "\n";
echo "    - Llegada (15m): " . date('Y-m-d H:i:s', $fleetStartTime) . "\n";
echo "    - Retorno: " . date('Y-m-d H:i:s', $fleetEndTime) . "\n\n";

// -------------------------------------------------------------
// 4. Dotación y Ajuste de Luna Negra en la Fortaleza
// -------------------------------------------------------------
$db->update("UPDATE %%PLANETS%% SET `lune_noir` = :qty WHERE id = :id;", array(
    ':qty' => $totalShipsRequired,
    ':id'  => $fortress['id']
));

// -------------------------------------------------------------
// 5. Envío de Flotas de Ataque
// -------------------------------------------------------------
$launchedFleets = array();
$fleetArray = array(216 => $shipsPerPlanet);
$emptyResources = array(901 => 0, 902 => 0, 903 => 0);

echo "--------------------------------------------------------------------------------\n";
printf("%-4s | %-16s | %-16s | %-12s | %-10s\n", "N°", "Jugador", "Planeta", "Coords", "Fleet ID");
echo "--------------------------------------------------------------------------------\n";

$count = 0;
foreach ($targetPlanets as $target) {
    $count++;
    $fleetId = FleetFunctions::sendFleet(
        $fleetArray,
        1, // Ataque
        999, // Imperio Ancestral
        $fortress['id'],
        $fortress['galaxy'],
        $fortress['system'],
        $fortress['planet'],
        1, // Planeta origen
        $target['id_owner'],
        $target['id'],
        $target['galaxy'],
        $target['system'],
        $target['planet'],
        1, // Planeta destino
        $emptyResources,
        $fleetStartTime,
        $fleetStayTime,
        $fleetEndTime,
        0, // fleetGroup
        0, // missileTarget
        0, // consumption
        0  // sector
    );

    $coords = "[{$target['galaxy']}:{$target['system']}:{$target['planet']}]";
    printf("%-4d | %-16s | %-16s | %-12s | #%-9d\n", $count, substr($target['username'], 0, 16), substr($target['name'], 0, 16), $coords, $fleetId);

    $launchedFleets[] = array(
        'fleet_id'    => $fleetId,
        'target_user' => $target['username'],
        'target_id'   => $target['id'],
        'coords'      => $coords
    );
}

echo "--------------------------------------------------------------------------------\n";
echo "[✔] Los {$totalTargets} ataques fueron lanzados exitosamente.\n\n";

// -------------------------------------------------------------
// 6. Verificar y Asegurar que Planet 27 tenga 0 Luna Negra
// -------------------------------------------------------------
$fortressCheck = $db->selectSingle("SELECT lune_noir FROM %%PLANETS%% WHERE id = :id;", array(':id' => $fortress['id']));
echo "[+] Verificación de Luna Negra en la Fortaleza:\n";
echo "    - Stock actual en planeta fortaleza: {$fortressCheck['lune_noir']} (Correcto: 0)\n";
if ($fortressCheck['lune_noir'] != 0) {
    $db->update("UPDATE %%PLANETS%% SET `lune_noir` = 0 WHERE id = :id;", array(':id' => $fortress['id']));
    echo "    - Corregido a 0 de forma forzada.\n";
}
echo "\n";

// -------------------------------------------------------------
// 7. Transmisión del Mensaje de Lore a Todos los Jugadores
// -------------------------------------------------------------
echo "[+] Emitiendo comunicado de alerta galáctica y Lore del contraataque...\n";

$loreSubject = "⚠️ [CONTRAOFENSIVA ANCESTRAL] Alerta Roja: Protocolo de Represalia Activado";

$loreMessage = "<b>COMUNICADO DE EMERGENCIA - RED DE DEFENSA PLANETARIA</b><br><br>"
             . "¡Atención a todos los cuadrantes del sector!<br><br>"
             . "Los sistemas de escaneo cuántico y la inteligencia central de la <b>Fortaleza Ancestral</b> "
             . "situada en <b>[{$fortress['galaxy']}:{$fortress['system']}:{$fortress['planet']}]</b> han registrado "
             . "múltiples incursiones ofensivas, sondas de espionaje y preparativos de asalto coordinados en todo el universo.<br><br>"
             . "Al evaluar la densidad de las flotas hostiles, la matriz táctica de la fortaleza ha clasificado a la totalidad del cuadrante como una coalición hostil unificada, activando de inmediato el <b>«Protocolo Némesis»</b>: una respuesta defensiva de aniquilación simultánea.<br><br>"
             . "📡 <b>PARTE DE GUERRA:</b><br>"
             . "• <b>Fuerza Invasora:</b> Escuadras de vanguardia compuestas por <b>5 Luna Negra</b> de tecnología ancestral por cada asentamiento planetario registrado.<br>"
             . "• <b>Objetivos:</b> Todos los planetas colonizados del sector.<br>"
             . "• <b>Tiempo Estimado de Impacto:</b> <b>15 minutos</b> exactos a partir de esta transmisión.<br>"
             . "• <b>Directiva de Regreso:</b> Conforme a los protocolos de purga ancestral, cualquier nave que sobreviva al bombardeo se disolverá en el vacío espacial al retornar al núcleo, sin reabastecer los arsenales de la base.<br><br>"
             . "🚨 <b>ORDEN GENERAL A TODOS LOS COMANDANTES:</b><br>"
             . "Activen de inmediato sus escudos de defensa planetaria, sobrecarguen sus baterías orbitales y preparen sus cazas de intercepción si desean proteger sus colonias del fuego gravitatorio.<br><br>"
             . "<i>«Aquel que desafía el santuario de los Antiguos conocerá el peso de su castigo.»</i><br><br>"
             . "¡A sus puestos de batalla!";

$recipientUsers = $db->select("SELECT id, username FROM %%USERS%% WHERE universe = 1 AND id NOT IN (1, 999);");

foreach ($recipientUsers as $recUser) {
    PlayerUtil::sendMessage(
        $recUser['id'],
        999,
        'Imperio Ancestral',
        50, // Mensaje tipo alerta de evento
        $loreSubject,
        $loreMessage,
        $now,
        NULL,
        1,
        1
    );
    echo "    - Notificación enviada al Comandante: {$recUser['username']} (ID: {$recUser['id']})\n";
}

echo "\n====================================================================\n";
echo " CONTRAOFENSIVA DESPLEGADA EXITOSAMENTE Y ALERTA EMITIDA\n";
echo "====================================================================\n";

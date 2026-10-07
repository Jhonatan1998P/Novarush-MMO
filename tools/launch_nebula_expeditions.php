<?php
/**
 * Script CLI: Automatización de Expediciones Militares a la Nebulosa (Sector 3: Antiguos)
 * 
 * Permite lanzar N expediciones militares de nebulosa desde Atenea (o cualquier planeta)
 * a la posición 16 con la composición de flota configurada (por defecto 20 Cruceros + 1 Gran Transporte).
 * 
 * Parámetros soportados vía CLI:
 *   --count=N          Número de expediciones a lanzar (por defecto 4)
 *   --sector=N         Sector de nebulosa (1: Piratas, 2: Alienígenas, 3: Antiguos; defecto 3)
 *   --cruisers=N       Cruceros por expedición (defecto 20)
 *   --transporters=N   Megatransportadores / Gran Transporte por expedición (defecto 1)
 *   --user=N           ID del usuario (defecto 4, -GODWAR-)
 *   --planet=N         ID del planeta de origen (defecto 34, Atenea)
 *   --target-galaxy=N  Galaxia destino (defecto galaxia de origen)
 *   --target-system=N  Sistema destino (defecto sistema de origen)
 *   --target-planet=N  Planeta destino (defecto 16)
 *   --stay=N           Bloques de permanencia (defecto 1 bloque = 1 hora)
 *   --dry-run          Modo simulación (no inserta flotas ni descuenta recursos)
 */

define('MODE', 'INSTALL');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
chdir(ROOT_PATH);

require 'includes/common.php';
require 'includes/vars/General.php';
require 'includes/classes/class.BuildFunctions.php';

// Parsear argumentos CLI
$options = getopt('', [
    'count::',
    'sector::',
    'cruisers::',
    'transporters::',
    'user::',
    'planet::',
    'target-galaxy::',
    'target-system::',
    'target-planet::',
    'stay::',
    'dry-run',
    'help'
]);

if (isset($options['help'])) {
    echo "Uso: php tools/launch_nebula_expeditions.php [opciones]\n";
    echo "  --count=N          Cantidad de flotas a enviar (defecto: 4)\n";
    echo "  --sector=N         Sector Nebula (1: Piratas, 2: Aliens, 3: Antiguos; defecto: 3)\n";
    echo "  --cruisers=N       Cruceros por flota (defecto: 20)\n";
    echo "  --transporters=N   Grandes Transportes por flota (defecto: 1)\n";
    echo "  --user=N           ID de usuario (defecto: 4)\n";
    echo "  --planet=N         ID de planeta origen (defecto: 34 - Atenea)\n";
    echo "  --dry-run          Modo de prueba sin enviar\n";
    exit(0);
}

$reqCount       = isset($options['count']) ? max(1, (int)$options['count']) : 4;
$sector         = isset($options['sector']) ? (int)$options['sector'] : 3;
$cruiserCount   = isset($options['cruisers']) ? max(0, (int)$options['cruisers']) : 20;
$transpCount    = isset($options['transporters']) ? max(0, (int)$options['transporters']) : 1;
$userId         = isset($options['user']) ? (int)$options['user'] : 4;
$planetId       = isset($options['planet']) ? (int)$options['planet'] : 34;
$stayBlocks     = isset($options['stay']) ? max(1, (int)$options['stay']) : 1;
$isDryRun       = isset($options['dry-run']);

$db = Database::get();

echo "============================================================\n";
echo " LANZADOR AUTOMÁTICO DE EXPEDICIONES DE NEBULOSA (SECTOR {$sector})\n";
echo "============================================================\n";
if ($isDryRun) {
    echo ">>> MODO SIMULACIÓN ACTIVO (DRY RUN) <<<\n\n";
}

// 1. Cargar datos del usuario
$USER = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = :id;", [':id' => $userId]);
if (empty($USER)) {
    die("ERROR: Usuario ID {$userId} no encontrado en la base de datos.\n");
}
$USER['factor'] = getFactors($USER);

// Configurar zona horaria del usuario para reportes
$userTz = !empty($USER['timezone']) ? $USER['timezone'] : 'America/Caracas';
date_default_timezone_set($userTz);

echo "Usuario: {$USER['username']} (ID: {$USER['id']})\n";
echo "Zona Horaria: {$userTz} | Hora Local: " . date('Y-m-d H:i:s') . "\n";

// 2. Cargar datos del planeta origen
$PLANET = $db->selectSingle("SELECT * FROM %%PLANETS%% WHERE id = :id AND id_owner = :owner;", [
    ':id'    => $planetId,
    ':owner' => $userId
]);
if (empty($PLANET)) {
    die("ERROR: Planeta origen ID {$planetId} no encontrado o no pertenece al usuario ID {$userId}.\n");
}

echo "Planeta Origen: {$PLANET['name']} [{$PLANET['galaxy']}:{$PLANET['system']}:{$PLANET['planet']}]\n";

// Coordenadas objetivo
$targetGalaxy = isset($options['target-galaxy']) ? (int)$options['target-galaxy'] : (int)$PLANET['galaxy'];
$targetSystem = isset($options['target-system']) ? (int)$options['target-system'] : (int)$PLANET['system'];
$targetPlanet = isset($options['target-planet']) ? (int)$options['target-planet'] : 16;
$targetType   = 1; // Planeta / Posición 16
$targetMission = 18; // MissionCaseMilitaryExpedition (Nebula)

echo "Destino: [{$targetGalaxy}:{$targetSystem}:{$targetPlanet}] (Sector Nebula: {$sector} - Antiguos)\n\n";

// 3. Verificación de slots de expedición y slots de flota
$maxExpedition    = FleetFunctions::getExpeditionLimit($USER);
$activeExpedition = FleetFunctions::GetCurrentFleets($USER['id'], 15, true) + FleetFunctions::GetCurrentFleets($USER['id'], 18, true);
$availableExpSlots = max(0, $maxExpedition - $activeExpedition);

$maxFleets        = FleetFunctions::GetMaxFleetSlots($USER);
$activeFleets     = FleetFunctions::GetCurrentFleets($USER['id']);
$availableFleetSlots = max(0, $maxFleets - $activeFleets);

echo "Slots de Expedición: {$activeExpedition}/{$maxExpedition} en uso (Disponibles: {$availableExpSlots})\n";
echo "Slots Totales Flota: {$activeFleets}/{$maxFleets} en uso (Disponibles: {$availableFleetSlots})\n";

$allowedSlots = min($reqCount, $availableExpSlots, $availableFleetSlots);

if ($allowedSlots <= 0) {
    die("ABORTADO: No hay slots disponibles para enviar expediciones.\n");
}

if ($allowedSlots < $reqCount) {
    echo "AVISO: Se solicitaron {$reqCount} expediciones pero solo hay {$allowedSlots} slots disponibles.\n";
    echo "       Se procederá a despachar {$allowedSlots} expediciones.\n";
} else {
    echo "Se despacharán las {$allowedSlots} expediciones solicitadas.\n";
}
echo "------------------------------------------------------------\n";

// 4. Verificación de composición de flota y recursos
$fleetArray = [];
if ($cruiserCount > 0) {
    $fleetArray[206] = $cruiserCount; // Crucero (crusher)
}
if ($transpCount > 0) {
    $fleetArray[217] = $transpCount; // Gran Transporte / Megatransportador (ev_transporter)
}

if (empty($fleetArray)) {
    die("ERROR: No se configuraron naves para la expedición.\n");
}

$shipNames = [
    206 => 'Crucero(s)',
    217 => 'Gran Transporte(s)'
];

echo "Composición por expedición:\n";
foreach ($fleetArray as $shipId => $qty) {
    $name = isset($shipNames[$shipId]) ? $shipNames[$shipId] : "Nave #{$shipId}";
    echo "  - {$name}: " . number_format($qty) . "\n";
}

// 5. Cálculos de vuelo y combustible
$distance       = FleetFunctions::GetTargetDistance([$PLANET['galaxy'], $PLANET['system'], $PLANET['planet']], [$targetGalaxy, $targetSystem, $targetPlanet]);
$fleetMaxSpeed  = FleetFunctions::GetFleetMaxSpeed($fleetArray, $USER);
$speedFactor    = FleetFunctions::GetGameSpeedFactor();
$durationRaw    = FleetFunctions::GetMissionDuration(10, $fleetMaxSpeed, $distance, $speedFactor, $USER);
$duration       = (int)ceil($durationRaw);
$consumption    = FleetFunctions::GetFleetConsumption($fleetArray, $duration, $distance, $USER, $speedFactor);
$haltSpeed      = Config::get($USER['universe'])->halt_speed;
$stayDuration   = round(($stayBlocks / $haltSpeed) * 3600);

echo "\nParámetros de Navegación:\n";
echo "  - Distancia: {$distance}\n";
echo "  - Velocidad Máxima: {$fleetMaxSpeed}\n";
echo "  - Duración de Ida: {$duration}s (" . gmdate("i:s", $duration) . " min)\n";
echo "  - Tiempo en Órbita/Sector: {$stayDuration}s (" . ($stayDuration / 3600) . " hora(s))\n";
echo "  - Duración de Vuelta: {$duration}s (" . gmdate("i:s", $duration) . " min)\n";
echo "  - Tiempo Total Misión: " . (($duration * 2) + $stayDuration) . "s (" . gmdate("H:i:s", ($duration * 2) + $stayDuration) . ")\n";
echo "  - Consumo Deuterio por Flota: " . number_format($consumption) . " deut\n";
echo "------------------------------------------------------------\n";

// 6. Validar existencias en el planeta
$neededCruisers   = $cruiserCount * $allowedSlots;
$neededTransp     = $transpCount * $allowedSlots;
$neededDeuterium  = $consumption * $allowedSlots;

$availCruisers    = (int)$PLANET['crusher'];
$availTransp      = (int)$PLANET['ev_transporter'];
$availDeuterium   = (float)$PLANET['deuterium'];

if ($availCruisers < $neededCruisers) {
    die("ERROR: Cruceros insuficientes en Atenea. Disponibles: {$availCruisers}, Necesarios: {$neededCruisers}\n");
}
if ($availTransp < $neededTransp) {
    die("ERROR: Grandes Transportes insuficientes en Atenea. Disponibles: {$availTransp}, Necesarios: {$neededTransp}\n");
}
if ($availDeuterium < $neededDeuterium) {
    die("ERROR: Deuterio insuficiente en Atenea. Disponible: " . number_format($availDeuterium) . ", Necesario: " . number_format($neededDeuterium) . "\n");
}

echo "Recursos y Naves Verificados:\n";
echo "  - Cruceros en hangar: " . number_format($availCruisers) . " (Se usarán: {$neededCruisers})\n";
echo "  - Gran Transportes en hangar: " . number_format($availTransp) . " (Se usarán: {$neededTransp})\n";
echo "  - Deuterio en almacén: " . number_format($availDeuterium) . " (Consumo total: " . number_format($neededDeuterium) . ")\n";
echo "============================================================\n";

if ($isDryRun) {
    echo "SIMULACIÓN COMPLETADA CON ÉXITO. No se realizaron cambios en la base de datos.\n";
    exit(0);
}

// 7. Lanzamiento de las expediciones
$dispatched = [];
$fleetResource = [901 => 0, 902 => 0, 903 => 0];

for ($i = 1; $i <= $allowedSlots; $i++) {
    $now = TIMESTAMP;
    $fleetStartTime = $now + $duration;
    $fleetStayTime  = $fleetStartTime + $stayDuration;
    $fleetEndTime   = $fleetStayTime + $duration;

    FleetFunctions::sendFleet(
        $fleetArray,
        $targetMission,
        $USER['id'],
        $PLANET['id'],
        $PLANET['galaxy'],
        $PLANET['system'],
        $PLANET['planet'],
        $PLANET['planet_type'],
        0, // target owner
        0, // target planet id
        $targetGalaxy,
        $targetSystem,
        $targetPlanet,
        $targetType,
        $fleetResource,
        $fleetStartTime,
        $fleetStayTime,
        $fleetEndTime,
        0, // fleetGroup
        0, // missileTarget
        $consumption, // consumo a descontar del planeta en cada despacho
        $sector // Sector 3
    );

    $fleetId = $db->selectSingle("SELECT MAX(fleet_id) as fid FROM %%FLEETS%% WHERE fleet_owner = :owner AND fleet_mission = 18;", [':owner' => $USER['id']], 'fid');

    $dispatched[] = [
        'index'       => $i,
        'fleet_id'    => $fleetId,
        'start_time'  => $fleetStartTime,
        'stay_time'   => $fleetStayTime,
        'end_time'    => $fleetEndTime
    ];

    echo ">> [Flota #{$i}/{$allowedSlots}] Despachada exitosamente! ID en BD: {$fleetId}\n";
    echo "    Llegada al sector: " . date('Y-m-d H:i:s', $fleetStartTime) . "\n";
    echo "    Fin permanencia:   " . date('Y-m-d H:i:s', $fleetStayTime) . "\n";
    echo "    Retorno a Atenea:  " . date('Y-m-d H:i:s', $fleetEndTime) . "\n\n";
}

echo "============================================================\n";
echo " RESUMEN FINAL DEL DESPACHO:\n";
echo "============================================================\n";
echo "Total flotas enviadas: " . count($dispatched) . "\n";
echo "Total Cruceros desplegados: " . ($cruiserCount * count($dispatched)) . "\n";
echo "Total Grandes Transportes desplegados: " . ($transpCount * count($dispatched)) . "\n";
echo "Total Deuterio consumido: " . number_format($consumption * count($dispatched)) . " deut\n";
echo "Estado: Todas las expediciones están activas en vuelo hacia la zona 16.\n";
echo "============================================================\n";

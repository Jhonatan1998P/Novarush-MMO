<?php

define('MODE', 'CLI');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
set_include_path(ROOT_PATH);

require 'includes/common.php';
require_once 'includes/classes/bot/Kernel/BotKernel.class.php';

$db = Database::get();
$botIds = array(1007, 1010, 1011);

echo "========================================================================\n";
echo "   EJECUTANDO 1 TICK EN VIVO DEL DAEMON REAL PARA BOTS DE PRODUCCIÓN    \n";
echo "========================================================================\n\n";

global $resource, $elementNames;
$elementNames = array(
    1 => 'Mina Metal', 2 => 'Mina Cristal', 3 => 'Sint. Deut', 4 => 'Planta Solar',
    12 => 'Planta Fusión', 14 => 'Robots', 15 => 'Nanobots', 21 => 'Hangar',
    22 => 'Alm. Metal', 23 => 'Alm. Cristal', 24 => 'Alm. Deut', 31 => 'Laboratorio',
    33 => 'Terraformador', 6 => 'Universidad', 44 => 'Silo', 41 => 'Base Lunar',
    42 => 'Phalanx', 43 => 'Salto Cuántico', 81 => 'Mód. Recursos', 82 => 'Mód. Defensivo',
    83 => 'Mód. Militar', 84 => 'Mód. Investigación',
    106 => 'Espionaje', 108 => 'Computación', 109 => 'Armas', 110 => 'Escudos', 111 => 'Blindaje',
    113 => 'Energía', 114 => 'Hiperespacio', 115 => 'Combustión', 117 => 'Impulso',
    118 => 'Prop. Hiper', 120 => 'Láser', 121 => 'Iónica', 122 => 'Plasma',
    123 => 'IRN', 124 => 'Astrofísica', 131 => 'Proc. Metal', 132 => 'Proc. Cristal',
    133 => 'Proc. Deut', 134 => 'Opt. Fuel', 199 => 'Gravitón'
);

foreach ($botIds as $bId) {
    $bRow = $db->selectSingle("SELECT * FROM %%BOTS%% WHERE bot_id = {$bId};");
    $uRow = $db->selectSingle("SELECT username FROM %%USERS%% WHERE id = {$bId};");
    if (!$bRow || !$uRow) continue;

    echo ">>> Procesando Tick en Vivo para Bot #{$bId} ({$uRow['username']})...\n";
    $result = BotKernel::processTurn($bRow);
    echo "    Resultado: " . (isset($result['error']) ? $result['error'] : 'Tick completado con éxito') . "\n\n";
}

echo "========================================================================\n";
echo "   ESTADO EN VIVO DE PRODUCCIÓN (BD: ogame2) TRAS EL TICK REAL          \n";
echo "========================================================================\n\n";

foreach ($botIds as $bId) {
    $uRow = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = {$bId};");
    $bRow = $db->selectSingle("SELECT * FROM %%BOTS%% WHERE bot_id = {$bId};");
    $planets = $db->select("SELECT * FROM %%PLANETS%% WHERE id_owner = {$bId} AND destruyed = 0 ORDER BY planet_type ASC, id ASC;");
    $fleets = $db->select("SELECT * FROM %%FLEETS%% WHERE fleet_owner = {$bId} ORDER BY fleet_id DESC;");

    echo "------------------------------------------------------------------------\n";
    echo "BOT: {$uRow['username']} (ID: {$bId}) | Personalidad: {$bRow['personality']} | Dificultad: {$bRow['difficulty']}\n";
    echo "------------------------------------------------------------------------\n";

    // 1. Investigación
    $techId = (int)$uRow['b_tech_id'];
    $techEnd = (int)$uRow['b_tech'];
    if ($techId > 0 && $techEnd > TIMESTAMP) {
        $techName = $elementNames[$techId] ?? "Tech #{$techId}";
        $remSec = $techEnd - TIMESTAMP;
        echo "  [INVESTIGACIÓN EN PROGRESO]: {$techName} (Nivel " . ((int)($uRow[$resource[$techId]] ?? 0) + 1) . ") - Finaliza en {$remSec}s (" . date('H:i:s d/m', $techEnd) . ")\n";
    } else {
        echo "  [INVESTIGACIÓN]: Ociosa / Libre\n";
    }

    // 2. Construcciones por cuerpo celeste
    echo "  [COLONIAS Y CONSTRUCCIONES]:\n";
    foreach ($planets as $p) {
        $pId = (int)$p['id'];
        $typeLabel = ((string)$p['planet_type'] === '3') ? 'Luna' : 'Planeta';
        $pCoords = "[{$p['galaxy']}:{$p['system']}:{$p['planet']}]";
        $bIdRaw = $p['b_building_id'];
        $queue = !empty($bIdRaw) ? @unserialize($bIdRaw) : array();

        $buildingStr = "Libre / Ocioso";
        if (!empty($queue)) {
            $buildingParts = array();
            foreach ($queue as $qItem) {
                $eId = (int)$qItem[0];
                $eLvl = (int)$qItem[1];
                $eEnd = (int)$qItem[3];
                $rem = max(0, $eEnd - TIMESTAMP);
                $eName = $elementNames[$eId] ?? "Elemento #{$eId}";
                $buildingParts[] = "{$eName} Nv.{$eLvl} ({$rem}s)";
            }
            $buildingStr = implode(', ', $buildingParts);
        }

        $freeF = CalculateMaxPlanetFields($p) - ((int)$p['field_current'] + count($queue));
        echo sprintf("    * #%-3d %-7s %-12s: Campos: %3d/%-3d (Libres: %2d) | Obra: %s\n", 
            $pId, $typeLabel, "{$p['name']} {$pCoords}", (int)$p['field_current'], (int)$p['field_max'], $freeF, $buildingStr);
    }

    // 3. Flotas y Convoyes
    echo "  [FLOTAS EN VUELO]: " . count($fleets) . " activas\n";
    if (!empty($fleets)) {
        foreach ($fleets as $f) {
            $missionType = ((int)$f['fleet_mission'] === 3) ? 'Transporte' : (
                ((int)$f['fleet_mission'] === 4) ? 'Despliegue' : (
                    ((int)$f['fleet_mission'] === 1) ? 'Ataque' : (
                        ((int)$f['fleet_mission'] === 6) ? 'Espionaje' : "Misión #{$f['fleet_mission']}"
                    )
                )
            );
            $origin = "[{$f['fleet_start_galaxy']}:{$f['fleet_start_system']}:{$f['fleet_start_planet']}] (ID #{$f['fleet_start_id']})";
            $dest = "[{$f['fleet_end_galaxy']}:{$f['fleet_end_system']}:{$f['fleet_end_planet']}] (ID #{$f['fleet_end_id']})";
            $rem = max(0, (int)$f['fleet_start_time'] - TIMESTAMP);
            $cargoM = (float)$f['fleet_resource_metal'];
            $cargoC = (float)$f['fleet_resource_crystal'];
            $cargoD = (float)$f['fleet_resource_deuterium'];
            echo sprintf("    * Flota #%-6d %-10s | %s -> %s | Arribo: %3ds | Carga: M:%s, C:%s, D:%s\n",
                $f['fleet_id'], $missionType, $origin, $dest, $rem,
                number_format($cargoM), number_format($cargoC), number_format($cargoD)
            );
        }
    }
    echo "\n";
}

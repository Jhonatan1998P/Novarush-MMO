<?php
/**
 * NovaRush - Generador de 30 Bots Nivel 0 (Inicio Limpio)
 * 10 Pesadilla (nightmare), 10 Difícil (hard), 5 Media (normal), 5 Fácil (easy)
 * Personalidades aleatorias: raider, miner, turtle, balanced
 * Todos comienzan desde 0 como un jugador normal (1 planeta, nivel 0 en todo, recursos iniciales estándar)
 */

define('MODE', 'CRON');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
set_include_path(ROOT_PATH);

require 'includes/common.php';
require 'includes/vars/General.php';
foreach (get_defined_vars() as $k => $v) {
    $GLOBALS[$k] = $v;
}

require_once ROOT_PATH . 'includes/classes/PlayerUtil.class.php';
require_once ROOT_PATH . 'includes/classes/bot/BotEngine.class.php';
require_once ROOT_PATH . 'includes/classes/bot/BotNameHelper.class.php';
require_once ROOT_PATH . 'includes/classes/bot/Forces/BotAchievementManager.class.php';

$db = Database::get();
BotEngine::ensureTables();

echo "========================================================================================\n";
echo "   NOVARUSH — DESPLIEGUE DE 30 BOTS NIVEL 0 (INICIO LIMPIO / UNIVERSO OLYSSIUM)\n";
echo "========================================================================================\n\n";

// Definición de dificultades requeridas
$plan = array();
for ($i = 0; $i < 10; $i++) { $plan[] = 'nightmare'; }
for ($i = 0; $i < 10; $i++) { $plan[] = 'hard'; }
for ($i = 0; $i < 5; $i++)  { $plan[] = 'normal'; }
for ($i = 0; $i < 5; $i++)  { $plan[] = 'easy'; }

// Barajar el orden para una distribución heterogénea en la galaxia
shuffle($plan);

$personalities = array('raider', 'miner', 'turtle', 'balanced');

// Cargar nombres de jugadores y nombres de planetas
$allPlayerNames = BotNameHelper::getPlayerNames();
$allPlanetNames = BotNameHelper::getPlanetNames();

// Obtener nombres de usuarios ya existentes en la BD para evitar colisiones
$existingUsersRaw = $db->select("SELECT username FROM uni1_users;");
$usedUsernames = array();
foreach ($existingUsersRaw as $row) {
    $usedUsernames[strtolower($row['username'])] = true;
}

// Obtener posiciones ocupadas en Galaxia 1
$existingPlanetsRaw = $db->select("SELECT system, planet FROM uni1_planets WHERE universe = 1 AND galaxy = 1;");
$occupiedCoords = array();
foreach ($existingPlanetsRaw as $row) {
    $occupiedCoords[$row['system'] . ':' . $row['planet']] = true;
}

$createdBots = array();
$nameIndex = 0;
shuffle($allPlayerNames);
shuffle($allPlanetNames);
$planetIndex = 0;

$config = Config::get(1);

foreach ($plan as $index => $difficulty) {
    // 1. Personalidad aleatoria
    $personality = $personalities[array_rand($personalities)];

    // 2. Nombre único
    $botName = null;
    while ($nameIndex < count($allPlayerNames)) {
        $candidate = $allPlayerNames[$nameIndex++];
        if (!isset($usedUsernames[strtolower($candidate)])) {
            $botName = $candidate;
            $usedUsernames[strtolower($candidate)] = true;
            break;
        }
    }
    if (empty($botName)) {
        $botName = 'Cyber_' . mt_rand(1000, 9999);
    }

    // 3. Nombre de planeta único
    $planetName = null;
    while ($planetIndex < count($allPlanetNames)) {
        $candidatePlanet = $allPlanetNames[$planetIndex++];
        $planetName = $candidatePlanet;
        break;
    }
    if (empty($planetName)) {
        $planetName = 'Nova-' . mt_rand(100, 999);
    }

    // 4. Buscar posición libre en Galaxia 1 (sistemas distribuidos entre 1 y 400, planetas 4 a 12)
    $galaxy = 1;
    $system = null;
    $planet = null;

    for ($attempt = 0; $attempt < 100; $attempt++) {
        $candSys = mt_rand(1, min(400, $config->max_system));
        $candPos = mt_rand(4, 12);
        $coordKey = $candSys . ':' . $candPos;

        if (!isset($occupiedCoords[$coordKey]) && PlayerUtil::isPositionFree(1, $galaxy, $candSys, $candPos)) {
            $occupiedCoords[$coordKey] = true;
            $system = $candSys;
            $planet = $candPos;
            break;
        }
    }

    if (empty($system) || empty($planet)) {
        throw new Exception("No se encontró posición libre para el bot {$botName}");
    }

    // 5. Credenciales
    $email = 'bot_' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $botName)) . '_' . mt_rand(100, 999) . '@novarush.ai';
    $rawPass = 'BotPass_' . mt_rand(100000, 999999);
    $cryptPass = PlayerUtil::cryptPassword($rawPass);

    // 6. Crear jugador normal en 2Moons
    list($userId, $homePlanetId) = PlayerUtil::createPlayer(
        1,
        $botName,
        $cryptPass,
        $email,
        'es',
        $galaxy,
        $system,
        $planet,
        $planetName,
        AUTH_USR,
        '127.0.0.1'
    );

    // 7. Sanitizar IPs a 127.0.0.1 (recursos iniciales canónicos 6M M, 4M C, 2M D fijados por PlayerUtil::createPlayer)
    $db->update("UPDATE uni1_users SET user_lastip = '127.0.0.1', ip_at_reg = '127.0.0.1' WHERE id = :userId;", array(
        ':userId' => $userId
    ));

    // 8. Intervalo de reclutamiento según personalidad
    if ($personality === 'raider') {
        $recruitInterval = mt_rand(600, 900);
    } elseif ($personality === 'turtle' || $personality === 'miner') {
        $recruitInterval = mt_rand(1200, 1800);
    } else {
        $recruitInterval = mt_rand(900, 1200);
    }

    // 9. Registrar en uni1_bots (Sin trampas, sin colonias extra, nivel 0 en todo)
    $db->insert("INSERT INTO uni1_bots (
        bot_id, personality, difficulty, doctrine, economic_modifier, home_planet_id, 
        last_activity, is_active, created_at, doctrine_heavy, doctrine_medium, 
        doctrine_light, doctrine_fodder, recruit_interval
    ) VALUES (
        :botId, :pers, :diff, 'flexible', 1.0, :home,
        :now, 1, :now, 0, 0,
        0, 0, :interval
    );", array(
        ':botId'    => $userId,
        ':pers'     => $personality,
        ':diff'     => $difficulty,
        ':home'     => $homePlanetId,
        ':now'      => TIMESTAMP,
        ':interval' => $recruitInterval
    ));

    // 10. Recalcular logros legítimos
    BotAchievementManager::recalculateBot((int)$userId);

    // 11. Registrar en logs
    BotEngine::log($userId, 'create', "Bot creado: {$botName} [Dificultad: {$difficulty}, Personalidad: {$personality}] en [{$galaxy}:{$system}:{$planet}] (Planeta: {$planetName}, Nivel 0)");

    $createdBots[] = array(
        'id'          => $userId,
        'name'        => $botName,
        'difficulty'  => $difficulty,
        'personality' => $personality,
        'coords'      => "{$galaxy}:{$system}:{$planet}",
        'planet_name' => $planetName
    );

    echo sprintf(" [%02d/30] Bot #%-4d %-15s | Dificultad: %-9s | Personalidad: %-9s | Coords: [%s] | Planeta: %-12s\n",
        $index + 1,
        $userId,
        $botName,
        $difficulty,
        $personality,
        "{$galaxy}:{$system}:{$planet}",
        $planetName
    );
}

echo "\n========================================================================================\n";
echo " RESUMEN FINAL:\n";
echo " Total Bots Creados: " . count($createdBots) . "\n";

$countsByDiff = array();
$countsByPers = array();
foreach ($createdBots as $b) {
    $countsByDiff[$b['difficulty']] = ($countsByDiff[$b['difficulty']] ?? 0) + 1;
    $countsByPers[$b['personality']] = ($countsByPers[$b['personality']] ?? 0) + 1;
}

echo " Distribución por Dificultad:\n";
foreach ($countsByDiff as $d => $c) {
    echo "   - {$d}: {$c}\n";
}
echo " Distribución por Personalidad:\n";
foreach ($countsByPers as $p => $c) {
    echo "   - {$p}: {$c}\n";
}
echo "========================================================================================\n";

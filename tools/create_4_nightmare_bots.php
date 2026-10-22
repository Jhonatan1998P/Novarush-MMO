<?php
define('MODE', 'CRON');
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
set_include_path(ROOT_PATH);

chdir(ROOT_PATH);

require_once 'includes/common.php';
require_once 'includes/vars/General.php';
foreach (get_defined_vars() as $k => $v) {
    $GLOBALS[$k] = $v;
}

require_once ROOT_PATH . 'includes/classes/PlayerUtil.class.php';
require_once ROOT_PATH . 'includes/classes/bot/BotEngine.class.php';
require_once ROOT_PATH . 'includes/classes/bot/BotNameHelper.class.php';
require_once ROOT_PATH . 'includes/classes/bot/Config/Personality.class.php';
require_once ROOT_PATH . 'includes/classes/bot/Forces/BotAchievementManager.class.php';

$db = Database::get();
BotEngine::ensureTables();

$botsToCreate = array(
    array('name' => 'Bot 5', 'personality' => 'aggressive'),
    array('name' => 'Bot 6', 'personality' => 'defensive'),
    array('name' => 'Bot 7', 'personality' => 'economic'),
    array('name' => 'Bot 8', 'personality' => 'balanced'),
);

$difficulty = 'nightmare';
$initialResources = 30000000000.0; // 30.000M = 30,000,000,000

echo "========================================================================================\n";
echo "   CREACIÓN DE 4 NUEVOS BOTS PESADILLA (INICIO LIMPIO NIVEL 0 | 30.000M RECURSOS)       \n";
echo "========================================================================================\n\n";

$config = Config::get(1);
$createdBots = array();

global $reslist, $resource;

// Obtener columnas válidas de uni1_planets y uni1_users
$samplePlanet = $db->selectSingle("SELECT * FROM uni1_planets LIMIT 1;");
$validPlanetCols = array_flip(array_keys($samplePlanet));

$sampleUser = $db->selectSingle("SELECT * FROM uni1_users LIMIT 1;");
$validUserCols = array_flip(array_keys($sampleUser));

foreach ($botsToCreate as $bInfo) {
    $name = $bInfo['name'];
    $personality = $bInfo['personality'];

    // 1. Verificar si ya existe en uni1_users
    $existing = $db->selectSingle("SELECT id, id_planet FROM uni1_users WHERE username = :u;", array(':u' => $name));
    if ($existing) {
        $userId = (int)$existing['id'];
        $homePlanetId = (int)$existing['id_planet'];
        echo "[*] El usuario '{$name}' ya existe (ID: {$userId}, Planeta: {$homePlanetId}). Sincronizando...\n";
    } else {
        // Buscar posición libre en Galaxia 1
        $galaxy = 1;
        $system = null;
        $planet = null;
        for ($attempt = 0; $attempt < 500; $attempt++) {
            $candSys = mt_rand(1, min(200, (int)$config->max_system));
            $candPlan = mt_rand(4, 12);
            if (PlayerUtil::isPositionFree(1, $galaxy, $candSys, $candPlan)) {
                $system = $candSys;
                $planet = $candPlan;
                break;
            }
        }
        if (empty($system) || empty($planet)) {
            throw new Exception("No se encontraron coordenadas libres para {$name}");
        }

        $email = 'bot_' . strtolower(str_replace(' ', '', $name)) . '@novarush.ai';
        $rawPass = 'BotPass_' . mt_rand(100000, 999999);
        $cryptPass = PlayerUtil::cryptPassword($rawPass);
        $homePlanetName = BotNameHelper::getRandomPlanetName();

        // Crear jugador en 2Moons
        list($userId, $homePlanetId) = PlayerUtil::createPlayer(
            1,
            $name,
            $cryptPass,
            $email,
            'es',
            $galaxy,
            $system,
            $planet,
            $homePlanetName,
            AUTH_USR,
            '127.0.0.1'
        );
    }

    // 2. Limpiar cualquier luna o colonia secundaria asociada (garantizar exactamente 1 solo planeta)
    $otherPlanets = $db->select(
        "SELECT id FROM uni1_planets WHERE id_owner = :uid AND id != :hpid;", 
        array(':uid' => $userId, ':hpid' => $homePlanetId)
    );
    if (!empty($otherPlanets)) {
        foreach ($otherPlanets as $op) {
            $db->delete("DELETE FROM uni1_planets WHERE id = :pid;", array(':pid' => (int)$op['id']));
        }
    }

    // 3. Resetear planeta inicial: 0 edificios, 0 hangares, 0 defensas, 0 naves, y 30.000M recursos
    $planetUpdates = array(
        'metal'         => $initialResources,
        'crystal'       => $initialResources,
        'deuterium'     => $initialResources,
        'metal_max'     => $initialResources,
        'crystal_max'   => $initialResources,
        'deuterium_max' => $initialResources,
        'field_current' => 0,
        'b_building'    => 0,
        'b_building_id' => 0,
        'b_hangar'      => 0,
        'b_hangar_id'   => '',
        'b_hangar_plus' => 0,
    );

    // Poner a 0 todos los edificios
    foreach ($reslist['build'] as $bId) {
        if (!empty($resource[$bId])) {
            $col = $resource[$bId];
            if (isset($validPlanetCols[$col])) {
                $planetUpdates[$col] = 0;
            }
            if (isset($validPlanetCols[$col . '_porcent'])) {
                $planetUpdates[$col . '_porcent'] = 10;
            }
        }
    }

    // Poner a 0 todas las naves
    foreach ($reslist['fleet'] as $fId) {
        if (!empty($resource[$fId])) {
            $col = $resource[$fId];
            if (isset($validPlanetCols[$col])) {
                $planetUpdates[$col] = 0;
            }
        }
    }

    // Poner a 0 todas las defensas
    foreach ($reslist['defense'] as $dId) {
        if (!empty($resource[$dId])) {
            $col = $resource[$dId];
            if (isset($validPlanetCols[$col])) {
                $planetUpdates[$col] = 0;
            }
        }
    }

    // Construir UPDATE con parámetros únicos
    $setParts = array();
    $params = array(':target_planet_id' => $homePlanetId);
    $idx = 0;
    foreach ($planetUpdates as $col => $val) {
        $pKey = ':p_val_' . ($idx++);
        $setParts[] = "`{$col}` = {$pKey}";
        $params[$pKey] = $val;
    }
    $db->update("UPDATE uni1_planets SET " . implode(', ', $setParts) . " WHERE id = :target_planet_id;", $params);

    // 4. Resetear usuario en uni1_users: 0 tecnologías
    $userUpdates = array(
        'b_tech'        => 0,
        'b_tech_id'     => 0,
        'b_tech_planet' => 0,
        'darkmatter'    => 3000,
        'antimatter'    => 300,
        'user_lastip'   => '127.0.0.1',
    );
    foreach ($reslist['tech'] as $tId) {
        if (!empty($resource[$tId])) {
            $col = $resource[$tId];
            if (isset($validUserCols[$col])) {
                $userUpdates[$col] = 0;
            }
        }
    }
    $userSetParts = array();
    $userParams = array(':target_user_id' => $userId);
    $uIdx = 0;
    foreach ($userUpdates as $col => $val) {
        $uKey = ':u_val_' . ($uIdx++);
        $userSetParts[] = "`{$col}` = {$uKey}";
        $userParams[$uKey] = $val;
    }
    $db->update("UPDATE uni1_users SET " . implode(', ', $userSetParts) . " WHERE id = :target_user_id;", $userParams);

    // 5. Configurar bolsas iniciales (budget_credits)
    $persObj = new BotPersonality($personality);
    $ratios = $persObj->getBudgetRatios();
    $now = time();

    $newCredits = array(
        'planets' => array(
            $homePlanetId => array(
                'mines'   => array(
                    'metal'     => round($initialResources * (float)$ratios['mines'], 2),
                    'crystal'   => round($initialResources * (float)$ratios['mines'], 2),
                    'deuterium' => round($initialResources * (float)$ratios['mines'], 2),
                ),
                'fleet'   => array(
                    'metal'     => round($initialResources * (float)$ratios['fleet'], 2),
                    'crystal'   => round($initialResources * (float)$ratios['fleet'], 2),
                    'deuterium' => round($initialResources * (float)$ratios['fleet'], 2),
                ),
                'defense' => array(
                    'metal'     => round($initialResources * (float)$ratios['defense'], 2),
                    'crystal'   => round($initialResources * (float)$ratios['defense'], 2),
                    'deuterium' => round($initialResources * (float)$ratios['defense'], 2),
                ),
            )
        ),
        'research' => array(
            'metal'     => round($initialResources * (float)$ratios['research'], 2),
            'crystal'   => round($initialResources * (float)$ratios['research'], 2),
            'deuterium' => round($initialResources * (float)$ratios['research'], 2),
        ),
        'opportunity_fund' => array('metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0),
        'last_physical' => array(
            $homePlanetId => array(
                'metal'     => $initialResources,
                'crystal'   => $initialResources,
                'deuterium' => $initialResources,
            )
        ),
        'last_updated' => $now,
    );

    $recruitInterval = ($personality === 'aggressive') ? 600 : (($personality === 'balanced') ? 900 : 1200);

    // 6. Registrar o actualizar en uni1_bots
    $existingBot = $db->selectSingle("SELECT bot_id FROM uni1_bots WHERE bot_id = :bid;", array(':bid' => $userId));
    if ($existingBot) {
        $db->update("UPDATE uni1_bots SET 
            personality = :pers,
            difficulty = :diff,
            doctrine = 'flexible',
            economic_modifier = 1.0,
            home_planet_id = :hpid,
            is_active = 1,
            last_activity = :now,
            recruit_interval = :rint,
            budget_credits = :credits,
            savings_lock = NULL,
            last_budget_time = :now,
            last_fleet_recruit = 0,
            last_defense_recruit = 0
            WHERE bot_id = :bid;",
            array(
                ':pers'    => $personality,
                ':diff'    => $difficulty,
                ':hpid'    => $homePlanetId,
                ':now'     => $now,
                ':rint'    => $recruitInterval,
                ':credits' => json_encode($newCredits),
                ':bid'     => $userId
            )
        );
    } else {
        $db->insert("INSERT INTO uni1_bots (
            bot_id, personality, difficulty, doctrine, economic_modifier, home_planet_id, 
            last_activity, is_active, created_at, doctrine_heavy, doctrine_medium, 
            doctrine_light, doctrine_fodder, recruit_interval, budget_credits, 
            savings_lock, last_budget_time, last_fleet_recruit, last_defense_recruit
        ) VALUES (
            :bid, :pers, :diff, 'flexible', 1.0, :hpid, 
            :now, 1, :now, 0, 0, 
            0, 0, :rint, :credits, 
            NULL, :now, 0, 0
        );", array(
            ':bid'     => $userId,
            ':pers'    => $personality,
            ':diff'    => $difficulty,
            ':hpid'    => $homePlanetId,
            ':now'     => $now,
            ':rint'    => $recruitInterval,
            ':credits' => json_encode($newCredits)
        ));
    }

    // 7. Inicializar logros
    BotAchievementManager::recalculateBot((int)$userId);

    // Obtener datos del planeta para confirmar
    $pData = $db->selectSingle("SELECT name, galaxy, system, planet FROM uni1_planets WHERE id = :pid;", array(':pid' => $homePlanetId));

    $createdBots[] = array(
        'name'        => $name,
        'userId'      => $userId,
        'planetId'    => $homePlanetId,
        'planetName'  => $pData['name'],
        'coords'      => "[{$pData['galaxy']}:{$pData['system']}:{$pData['planet']}]",
        'personality' => $personality,
        'difficulty'  => $difficulty,
    );

    echo "✅ Bot configurado: {$name} (User ID: {$userId})\n";
    echo "   Planeta: {$pData['name']} {$createdBots[count($createdBots)-1]['coords']} (ID: {$homePlanetId})\n";
    echo "   Dificultad: {$difficulty} | Personalidad: {$personality}\n";
    echo "   Recursos en planeta: 30.000.000.000 Metal | 30.000.000.000 Cristal | 30.000.000.000 Deuterio\n";
    echo "   Estado: Jugador en nivel 0 (0 edificios, 0 naves, 0 defensas, 0 investigaciones)\n\n";
}

echo "========================================================================================\n";
echo "   RESUMEN FINAL DE LOS 4 NUEVOS BOTS CREADOS                                            \n";
echo "========================================================================================\n";
foreach ($createdBots as $cb) {
    echo "- {$cb['name']}: ID {$cb['userId']} | Personalidad: {$cb['personality']} | Dificultad: {$cb['difficulty']} | Planeta: {$cb['planetName']} {$cb['coords']} (ID: {$cb['planetId']})\n";
}
echo "========================================================================================\n";

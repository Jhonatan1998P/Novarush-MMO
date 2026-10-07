<?php

/**
 * NovaRush - Motor de Inteligencia Artificial para Bots Competitivos
 *
 * Características:
 * - Detección de ataques entrantes y Fleetsave / Auto-Dodge (esquivar ataques suicidas y evacuar recursos)
 * - Reclutamiento multi-planeta en paralelo (todos los planetas construyen simultáneamente para acelerar la armada)
 * - Composición de flota equilibrada (cazas ligeros/pesados como escudo, cruceros rápidos, acorazados/destructores pesados, cargueros y recicladores)
 * - Cadena logística entre colonias: mover recursos a bases necesitadas y unificar flotas hacia la fortaleza principal (Rallying)
 * - Espionaje activo y sistemático de jugadores en la galaxia
 * - Análisis táctico de combate: ataques de saqueo a granjas e inactivos y Fleet Crash cuando la superioridad es aplastante
 * - Cosecha automática de campos de escombros (debris recycling) tras los combates
 */

require_once 'includes/classes/bot/Kernel/BotKernel.class.php';

class BotEngine
{
    // Mapeo de IDs de naves estándar
    const SHIP_CARGO_SMALL     = 202;
    const SHIP_CARGO_BIG       = 203;
    const SHIP_LIGHT_HUNTER    = 204;
    const SHIP_HEAVY_HUNTER    = 205;
    const SHIP_CRUISER         = 206;
    const SHIP_BATTLESHIP      = 207;
    const SHIP_COLONIZER       = 208;
    const SHIP_RECYCLER        = 209;
    const SHIP_SPY_PROBE       = 210;
    const SHIP_BOMBER          = 211;
    const SHIP_DESTRUCTOR      = 213;
    const SHIP_DEATH_STAR      = 214;
    const SHIP_BATTLECRUISER   = 215;

    // Mapeo de defensas
    const DEF_MISSILE_LAUNCHER = 401;
    const DEF_SMALL_LASER      = 402;
    const DEF_BIG_LASER        = 403;
    const DEF_GAUSS_CANNON     = 404;
    const DEF_ION_CANNON       = 405;
    const DEF_PLASMA_TURRET    = 406;
    const DEF_SHIELD_SMALL     = 407;
    const DEF_SHIELD_BIG       = 408;

    // Misiones estándar OGame / 2Moons
    const MISSION_ATTACK       = 1;
    const MISSION_TRANSPORT    = 3;
    const MISSION_DEPLOY       = 4;
    const MISSION_SPY          = 6;
    const MISSION_RECYCLE      = 8;

    /**
     * Asegura la existencia de las tablas de bots en la base de datos
     */
    public static function ensureTables()
    {
        $db = Database::get();
        $sql1 = "CREATE TABLE IF NOT EXISTS %%BOTS%% (
            bot_id int(11) unsigned NOT NULL,
            personality varchar(32) NOT NULL DEFAULT 'balanced',
            difficulty varchar(32) NOT NULL DEFAULT 'normal',
            doctrine varchar(32) NOT NULL DEFAULT 'flexible',
            economic_modifier float NOT NULL DEFAULT 1.0,
            home_planet_id int(11) unsigned NOT NULL DEFAULT 0,
            last_activity int(11) NOT NULL DEFAULT 0,
            last_spy int(11) NOT NULL DEFAULT 0,
            last_attack int(11) NOT NULL DEFAULT 0,
            last_logistics int(11) NOT NULL DEFAULT 0,
            last_build int(11) NOT NULL DEFAULT 0,
            last_recruit int(11) NOT NULL DEFAULT 0,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_at int(11) NOT NULL DEFAULT 0,
            doctrine_heavy int(10) unsigned NOT NULL DEFAULT 0,
            doctrine_medium int(10) unsigned NOT NULL DEFAULT 0,
            doctrine_light int(10) unsigned NOT NULL DEFAULT 0,
            recruit_interval int(10) unsigned NOT NULL DEFAULT 900,
            target_galaxy int(11) DEFAULT 0,
            target_system int(11) DEFAULT 0,
            target_planet int(11) DEFAULT 0,
            target_type varchar(32) DEFAULT '',
            target_initial_mse double(50,2) DEFAULT 0.00,
            target_lock_time int(11) DEFAULT 0,
            siege_cycles int(11) DEFAULT 0,
            fob_planet_id int(11) unsigned DEFAULT 0,
            siege_spent_mse double(50,2) NOT NULL DEFAULT 0.00,
            budget_credits longtext NULL,
            savings_lock longtext NULL,
            last_budget_time int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (bot_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

        $sql2 = "CREATE TABLE IF NOT EXISTS %%BOT_LOGS%% (
            log_id int(11) unsigned NOT NULL AUTO_INCREMENT,
            bot_id int(11) unsigned NOT NULL,
            action_type varchar(32) NOT NULL,
            details text NOT NULL,
            timestamp int(11) NOT NULL,
            PRIMARY KEY (log_id),
            KEY bot_id (bot_id),
            KEY timestamp (timestamp)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

        $sql3 = "CREATE TABLE IF NOT EXISTS %%BOT_TASKS%% (
            task_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            bot_id INT UNSIGNED NOT NULL,
            task_type VARCHAR(64) NOT NULL,
            payload LONGTEXT NOT NULL,
            scheduled_at INT NOT NULL,
            created_at INT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            KEY idx_bot_status_time (bot_id, status, scheduled_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        $sql4 = "CREATE TABLE IF NOT EXISTS %%BOT_INTEL%% (
            intel_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            bot_id INT UNSIGNED NOT NULL,
            target_planet_id INT UNSIGNED NOT NULL DEFAULT 0,
            target_user_id INT UNSIGNED NOT NULL DEFAULT 0,
            galaxy TINYINT UNSIGNED NOT NULL,
            system SMALLINT UNSIGNED NOT NULL,
            planet TINYINT UNSIGNED NOT NULL,
            planet_type TINYINT UNSIGNED NOT NULL DEFAULT 1,
            metal BIGINT UNSIGNED NOT NULL DEFAULT 0,
            crystal BIGINT UNSIGNED NOT NULL DEFAULT 0,
            deuterium BIGINT UNSIGNED NOT NULL DEFAULT 0,
            fleet_data LONGTEXT NULL,
            defense_data LONGTEXT NULL,
            building_data LONGTEXT NULL,
            tech_data LONGTEXT NULL,
            scanned_at INT NOT NULL,
            UNIQUE KEY idx_bot_target (bot_id, galaxy, system, planet, planet_type),
            KEY idx_bot_scanned (bot_id, scanned_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        $sql5 = "CREATE TABLE IF NOT EXISTS %%BOT_OPPONENTS%% (
            bot_id INT UNSIGNED NOT NULL,
            target_user_id INT UNSIGNED NOT NULL,
            aggro_score FLOAT NOT NULL DEFAULT 0,
            activity_profile LONGTEXT NULL,
            last_attacked_at INT NOT NULL DEFAULT 0,
            last_retaliated_at INT NOT NULL DEFAULT 0,
            notes TEXT NULL,
            PRIMARY KEY (bot_id, target_user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        $sql6 = "CREATE TABLE IF NOT EXISTS %%BOT_DECISIONS%% (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            bot_id INT UNSIGNED NOT NULL,
            decision_type VARCHAR(64) NOT NULL,
            context_summary TEXT NULL,
            candidates_json LONGTEXT NULL,
            chosen_action VARCHAR(128) NOT NULL,
            chosen_score FLOAT NOT NULL DEFAULT 0,
            seed_used VARCHAR(64) NOT NULL,
            created_at INT NOT NULL,
            KEY idx_bot_time (bot_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        $sql7 = "CREATE TABLE IF NOT EXISTS %%BOT_TARGET_BLACKLIST%% (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            bot_id INT UNSIGNED NOT NULL DEFAULT 0,
            target_user_id INT UNSIGNED NOT NULL DEFAULT 0,
            target_planet_id INT UNSIGNED NOT NULL DEFAULT 0,
            galaxy TINYINT UNSIGNED NOT NULL DEFAULT 0,
            system SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            planet TINYINT UNSIGNED NOT NULL DEFAULT 0,
            successful_wins INT UNSIGNED NOT NULL DEFAULT 0,
            total_attacks INT UNSIGNED NOT NULL DEFAULT 0,
            blacklisted_until INT NOT NULL DEFAULT 0,
            created_at INT NOT NULL DEFAULT 0,
            updated_at INT NOT NULL DEFAULT 0,
            UNIQUE KEY idx_bot_user (bot_id, target_user_id),
            KEY idx_user_expire (target_user_id, blacklisted_until),
            KEY idx_coords (galaxy, system, planet, blacklisted_until),
            KEY idx_planet (target_planet_id, blacklisted_until),
            KEY idx_expire (blacklisted_until)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        try {
            $pdo = $db->getHandle();
            $pdo->exec(str_replace('%%BOTS%%', DB_PREFIX . 'bots', $sql1));
            $pdo->exec(str_replace('%%BOT_LOGS%%', DB_PREFIX . 'bot_logs', $sql2));
            $pdo->exec(str_replace('%%BOT_TASKS%%', DB_PREFIX . 'bot_tasks', $sql3));
            $pdo->exec(str_replace('%%BOT_INTEL%%', DB_PREFIX . 'bot_intel', $sql4));
            $pdo->exec(str_replace('%%BOT_OPPONENTS%%', DB_PREFIX . 'bot_opponents', $sql5));
            $pdo->exec(str_replace('%%BOT_DECISIONS%%', DB_PREFIX . 'bot_decisions', $sql6));
            $pdo->exec(str_replace('%%BOT_TARGET_BLACKLIST%%', DB_PREFIX . 'bot_target_blacklist', $sql7));

            // Migraciones de columnas V3.1 para presupuestos y modo ahorro
            try {
                $pdo->exec("ALTER TABLE " . DB_PREFIX . "bots 
                    ADD COLUMN IF NOT EXISTS budget_credits LONGTEXT NULL,
                    ADD COLUMN IF NOT EXISTS savings_lock LONGTEXT NULL,
                    ADD COLUMN IF NOT EXISTS last_budget_time INT(11) NOT NULL DEFAULT 0;");
            } catch (Throwable $eCol) {
                // Ya existen o no soportado IF NOT EXISTS en versión antigua
            }
        } catch (Throwable $e) {
            // Tablas ya existen o error menor
        }
    }

    /**
     * Registra un evento en el log de bots
     */
    public static function log($botId, $actionType, $details)
    {
        $db = Database::get();
        $db->insert("INSERT INTO " . DB_PREFIX . "bot_logs (bot_id, action_type, details, timestamp) VALUES (:botId, :type, :details, :time);", array(
            ':botId'   => (int)$botId,
            ':type'    => $actionType,
            ':details' => $details,
            ':time'    => TIMESTAMP
        ));

        // Poda de logs antiguos (más de 7 días)
        if (mt_rand(1, 50) === 1) {
            $db->delete("DELETE FROM " . DB_PREFIX . "bot_logs WHERE timestamp < :oldTime;", array(
                ':oldTime' => TIMESTAMP - (7 * 86400)
            ));
        }
    }

    /**
     * Obtiene la lista de bots registrados
     */
    public static function getBots($activeOnly = false)
    {
        $db = Database::get();
        self::ensureTables();

        $where = $activeOnly ? "WHERE b.is_active = 1" : "";
        $sql = "SELECT b.*, u.username, u.email, u.authlevel, u.id_planet, s.total_points, s.fleet_points, s.build_points, s.defs_points, s.tech_points
                FROM " . DB_PREFIX . "bots as b
                INNER JOIN %%USERS%% as u ON u.id = b.bot_id
                LEFT JOIN %%STATPOINTS%% as s ON s.id_owner = u.id AND s.stat_type = '1'
                {$where}
                ORDER BY b.bot_id ASC;";

        return $db->select($sql);
    }

    /**
     * Obtiene los logs recientes de un bot o globales
     */
    public static function getRecentLogs($botId = null, $limit = 50)
    {
        $db = Database::get();
        self::ensureTables();

        $where = $botId ? "WHERE l.bot_id = " . (int)$botId : "";
        $sql = "SELECT l.*, u.username 
                FROM " . DB_PREFIX . "bot_logs as l
                LEFT JOIN %%USERS%% as u ON u.id = l.bot_id
                {$where}
                ORDER BY l.log_id DESC LIMIT " . (int)$limit . ";";

        return $db->select($sql);
    }

    /**
     * Registra un usuario existente como bot
     */
    public static function registerExistingUserAsBot($userId, $personality = 'balanced')
    {
        $db = Database::get();
        self::ensureTables();

        $user = $db->selectSingle("SELECT id, id_planet, username FROM %%USERS%% WHERE id = :userId;", array(':userId' => $userId));
        if (empty($user)) {
            throw new Exception("Usuario no encontrado!");
        }

        $sql = "INSERT INTO " . DB_PREFIX . "bots (bot_id, personality, home_planet_id, last_activity, is_active, created_at)
                VALUES (:botId, :personality, :home, :now, 1, :now)
                ON DUPLICATE KEY UPDATE personality = :personality2, is_active = 1, home_planet_id = :home2;";

        $db->insert($sql, array(
            ':botId'        => $user['id'],
            ':personality'  => $personality,
            ':home'         => $user['id_planet'],
            ':now'          => TIMESTAMP,
            ':personality2' => $personality,
            ':home2'        => $user['id_planet']
        ));

        self::log($user['id'], 'system', "Bot registrado: {$user['username']} con personalidad {$personality}");
        return true;
    }

    /**
     * Crea un nuevo bot desde cero con colonias y flota inicial según el nivel elegido
     */
    public static function createBot($name = null, $personality = 'balanced', $tier = 'intermediate', $galaxy = 1, $system = null, $planet = null)
    {
        global $LNG;
        $db = Database::get();
        self::ensureTables();

        $config = Config::get(1);

        // Si no se proporcionó nombre, seleccionamos uno de botnames.txt
        if (empty($name)) {
            $botNamesPath = ROOT_PATH . 'botnames.txt';
            if (file_exists($botNamesPath)) {
                $lines = file($botNamesPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                for ($attempt = 0; $attempt < 30; $attempt++) {
                    $candidate = trim($lines[array_rand($lines)]);
                    $exists = $db->selectSingle("SELECT id FROM %%USERS%% WHERE username = :name;", array(':name' => $candidate));
                    if (empty($exists)) {
                        $name = $candidate;
                        break;
                    }
                }
            }
            if (empty($name)) {
                $name = 'Bot_' . mt_rand(1000, 9999);
            }
        }

        // Si no se especificó sistema o planeta, buscar posición libre
        if (empty($system) || empty($planet)) {
            for ($attempt = 0; $attempt < 50; $attempt++) {
                $candSystem = mt_rand(1, min(100, $config->max_system));
                $candPlanet = mt_rand(3, 12);
                if (PlayerUtil::isPositionFree(1, $galaxy, $candSystem, $candPlanet)) {
                    $system = $candSystem;
                    $planet = $candPlanet;
                    break;
                }
            }
            if (empty($system) || empty($planet)) {
                $system = 1;
                $planet = 4;
            }
        }

        $email = 'bot_' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name)) . '_' . mt_rand(100, 999) . '@novarush.ai';
        $rawPass = 'BotPass_' . mt_rand(100000, 999999);
        $cryptPass = PlayerUtil::cryptPassword($rawPass);

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
            'Fortaleza ' . $name,
            AUTH_USR
        );

        $diffMap = array(
            'novice'       => 'easy',
            'intermediate' => 'normal',
            'advanced'     => 'hard',
            'titan'        => 'nightmare',
        );
        $diff = isset($diffMap[$tier]) ? $diffMap[$tier] : 'normal';

        $heavyOptions  = array(225, 216, 214);
        $mediumOptions = array(213, 211, 215);
        $lightOptions  = array(206, 207);

        $chosenHeavy  = $heavyOptions[array_rand($heavyOptions)];
        $chosenMedium = $mediumOptions[array_rand($mediumOptions)];
        $chosenLight  = $lightOptions[array_rand($lightOptions)];

        if ($personality === 'raider') {
            $recruitInterval = mt_rand(600, 900); // 10 a 15 min
        } elseif ($personality === 'turtle' || $personality === 'economic') {
            $recruitInterval = mt_rand(1200, 1800); // 20 a 30 min
        } else {
            $recruitInterval = mt_rand(900, 1200); // 15 a 20 min (balanced)
        }

        // Registrar en uni1_bots
        $db->insert("INSERT INTO " . DB_PREFIX . "bots (bot_id, personality, difficulty, home_planet_id, last_activity, is_active, created_at, doctrine_heavy, doctrine_medium, doctrine_light, recruit_interval)
                     VALUES (:botId, :pers, :diff, :home, :now, 1, :now, :heavy, :medium, :light, :interval);", array(
            ':botId'    => $userId,
            ':pers'     => $personality,
            ':diff'     => $diff,
            ':home'     => $homePlanetId,
            ':now'      => TIMESTAMP,
            ':heavy'    => $chosenHeavy,
            ':medium'   => $chosenMedium,
            ':light'    => $chosenLight,
            ':interval' => $recruitInterval
        ));

        // Aplicar nivel inicial (tier)
        self::applyTierConfiguration($userId, $homePlanetId, $tier);

        // Crear 2 colonias adicionales para que el bot pueda utilizar de inmediato reclutamiento paralelo y logística
        self::createExtraColonies($userId, $galaxy, $system, 2);

        self::log($userId, 'create', "Bot creado: {$name} [Nivel: {$tier}, Personalidad: {$personality}] en {$galaxy}:{$system}:{$planet}");

        return array(
            'userId'       => $userId,
            'homePlanetId' => $homePlanetId,
            'name'         => $name,
            'email'        => $email,
            'tier'         => $tier,
            'coordinates'  => "{$galaxy}:{$system}:{$planet}"
        );
    }

    /**
     * Aplica niveles de minas, hangares, tecnologías y tropas según el tier del bot
     */
    protected static function applyTierConfiguration($userId, $planetId, $tier)
    {
        $db = Database::get();

        $tiers = array(
            'novice' => array(
                'mines' => array('metal_mine' => 14, 'crystal_mine' => 12, 'deuterium_sintetizer' => 10, 'solar_plant' => 16, 'resource_module' => 4, 'defensive_module' => 2, 'military_module' => 2, 'research_module' => 2, 'robot_factory' => 5, 'hangar' => 6, 'laboratory' => 4, 'silo' => 2),
                'techs' => array('spy_tech' => 4, 'computer_tech' => 4, 'military_tech' => 4, 'defence_tech' => 4, 'shield_tech' => 4, 'energy_tech' => 4, 'combustion_tech' => 5, 'impulse_motor_tech' => 3, 'metal_proc_tech' => 4, 'crystal_proc_tech' => 3, 'deuterium_proc_tech' => 3),
                'ships' => array('small_ship_cargo' => 50, 'big_ship_cargo' => 30, 'light_hunter' => 400, 'heavy_hunter' => 150, 'crusher' => 80, 'battle_ship' => 30, 'battleship' => 15, 'recycler' => 20, 'spy_sonde' => 50),
                'defs'  => array('misil_launcher' => 50, 'small_laser' => 30, 'small_protection_shield' => 1, 'interceptor_misil' => 10),
                'res'   => array('metal' => 300000, 'crystal' => 200000, 'deuterium' => 100000)
            ),
            'intermediate' => array(
                'mines' => array('metal_mine' => 25, 'crystal_mine' => 23, 'deuterium_sintetizer' => 20, 'solar_plant' => 27, 'fusion_plant' => 8, 'resource_module' => 12, 'defensive_module' => 8, 'military_module' => 8, 'research_module' => 8, 'robot_factory' => 9, 'nano_factory' => 3, 'hangar' => 12, 'laboratory' => 10, 'silo' => 6, 'terraformer' => 3),
                'techs' => array('spy_tech' => 10, 'computer_tech' => 10, 'military_tech' => 10, 'defence_tech' => 10, 'shield_tech' => 10, 'energy_tech' => 10, 'combustion_tech' => 10, 'impulse_motor_tech' => 8, 'hyperspace_motor_tech' => 7, 'expedition_tech' => 5, 'metal_proc_tech' => 10, 'crystal_proc_tech' => 10, 'deuterium_proc_tech' => 8, 'fuel_optim_tech' => 6),
                'ships' => array('small_ship_cargo' => 200, 'big_ship_cargo' => 200, 'ev_transporter' => 25, 'light_hunter' => 4000, 'heavy_hunter' => 1500, 'crusher' => 1200, 'battle_ship' => 600, 'battleship' => 400, 'bomber_ship' => 150, 'destructor' => 120, 'galleon' => 15, 'dearth_star' => 2, 'recycler' => 200, 'giga_recykler' => 40, 'spy_sonde' => 300),
                'defs'  => array('misil_launcher' => 500, 'small_laser' => 300, 'big_laser' => 150, 'gauss_canyon' => 50, 'ionic_canyon' => 40, 'buster_canyon' => 20, 'small_protection_shield' => 1, 'big_protection_shield' => 1, 'interceptor_misil' => 40, 'interplanetary_misil' => 15),
                'res'   => array('metal' => 15000000, 'crystal' => 10000000, 'deuterium' => 5000000)
            ),
            'advanced' => array(
                'mines' => array('metal_mine' => 30, 'crystal_mine' => 28, 'deuterium_sintetizer' => 25, 'solar_plant' => 32, 'fusion_plant' => 12, 'resource_module' => 18, 'defensive_module' => 12, 'military_module' => 12, 'research_module' => 12, 'robot_factory' => 10, 'nano_factory' => 6, 'hangar' => 14, 'laboratory' => 12, 'silo' => 8, 'terraformer' => 5),
                'techs' => array('spy_tech' => 14, 'computer_tech' => 14, 'military_tech' => 14, 'defence_tech' => 14, 'shield_tech' => 14, 'energy_tech' => 14, 'combustion_tech' => 14, 'impulse_motor_tech' => 12, 'hyperspace_motor_tech' => 10, 'expedition_tech' => 7, 'metal_proc_tech' => 14, 'crystal_proc_tech' => 14, 'deuterium_proc_tech' => 12, 'fuel_optim_tech' => 10),
                'ships' => array('big_ship_cargo' => 800, 'ev_transporter' => 150, 'light_hunter' => 15000, 'heavy_hunter' => 6000, 'crusher' => 5000, 'battle_ship' => 2500, 'battleship' => 1800, 'bomber_ship' => 800, 'destructor' => 600, 'galleon' => 80, 'destroyer' => 30, 'dearth_star' => 8, 'lune_noir' => 4, 'recycler' => 500, 'giga_recykler' => 150, 'spy_sonde' => 800),
                'defs'  => array('misil_launcher' => 2000, 'small_laser' => 1200, 'big_laser' => 500, 'gauss_canyon' => 200, 'ionic_canyon' => 150, 'buster_canyon' => 80, 'small_protection_shield' => 1, 'big_protection_shield' => 1, 'interceptor_misil' => 60, 'interplanetary_misil' => 25),
                'res'   => array('metal' => 45000000, 'crystal' => 30000000, 'deuterium' => 15000000)
            ),
            'titan' => array(
                'mines' => array('metal_mine' => 35, 'crystal_mine' => 33, 'deuterium_sintetizer' => 29, 'solar_plant' => 36, 'fusion_plant' => 16, 'resource_module' => 25, 'defensive_module' => 16, 'military_module' => 16, 'research_module' => 16, 'robot_factory' => 12, 'nano_factory' => 8, 'hangar' => 16, 'laboratory' => 15, 'silo' => 10, 'terraformer' => 8),
                'techs' => array('spy_tech' => 18, 'computer_tech' => 18, 'military_tech' => 18, 'defence_tech' => 18, 'shield_tech' => 18, 'energy_tech' => 18, 'combustion_tech' => 16, 'impulse_motor_tech' => 14, 'hyperspace_motor_tech' => 14, 'expedition_tech' => 10, 'metal_proc_tech' => 18, 'crystal_proc_tech' => 18, 'deuterium_proc_tech' => 16, 'fuel_optim_tech' => 12),
                'ships' => array('big_ship_cargo' => 2500, 'ev_transporter' => 500, 'light_hunter' => 40000, 'heavy_hunter' => 15000, 'crusher' => 12000, 'battle_ship' => 6000, 'battleship' => 4000, 'bomber_ship' => 1800, 'destructor' => 1500, 'galleon' => 250, 'destroyer' => 100, 'dearth_star' => 25, 'lune_noir' => 15, 'recycler' => 1500, 'giga_recykler' => 500, 'spy_sonde' => 2000),
                'defs'  => array('misil_launcher' => 5000, 'small_laser' => 3000, 'big_laser' => 1500, 'gauss_canyon' => 600, 'ionic_canyon' => 400, 'buster_canyon' => 200, 'small_protection_shield' => 1, 'big_protection_shield' => 1, 'interceptor_misil' => 80, 'interplanetary_misil' => 40),
                'res'   => array('metal' => 120000000, 'crystal' => 80000000, 'deuterium' => 40000000)
            )
        );

        $selected = isset($tiers[$tier]) ? $tiers[$tier] : $tiers['intermediate'];

        // Actualizar tecnologías en uni1_users
        $techUpdates = array();
        $paramsTech = array(':userId' => $userId);
        foreach ($selected['techs'] as $tech => $lvl) {
            $techUpdates[] = "{$tech} = :{$tech}";
            $paramsTech[':' . $tech] = $lvl;
        }
        $db->update("UPDATE %%USERS%% SET " . implode(', ', $techUpdates) . " WHERE id = :userId;", $paramsTech);

        // Actualizar edificios, naves, defensas y recursos en el planeta principal
        $planetUpdates = array();
        $paramsPlanet = array(':planetId' => $planetId);

        foreach ($selected['mines'] as $bld => $lvl) {
            $planetUpdates[] = "{$bld} = :{$bld}";
            $paramsPlanet[':' . $bld] = $lvl;
        }
        foreach ($selected['ships'] as $ship => $count) {
            $planetUpdates[] = "{$ship} = :{$ship}";
            $paramsPlanet[':' . $ship] = $count;
        }
        foreach ($selected['defs'] as $def => $count) {
            $planetUpdates[] = "{$def} = :{$def}";
            $paramsPlanet[':' . $def] = $count;
        }
        foreach ($selected['res'] as $res => $amount) {
            $planetUpdates[] = "{$res} = :{$res}";
            $paramsPlanet[':' . $res] = $amount;
        }

        $db->update("UPDATE %%PLANETS%% SET " . implode(', ', $planetUpdates) . " WHERE id = :planetId;", $paramsPlanet);
    }

    /**
     * Crea colonias adicionales para el bot en sistemas cercanos
     */
    protected static function createExtraColonies($userId, $galaxy, $baseSystem, $count = 2)
    {
        $db = Database::get();
        for ($i = 1; $i <= $count; $i++) {
            $targetSystem = max(1, $baseSystem + ($i * mt_rand(1, 4)));
            $targetPlanet = mt_rand(4, 10);

            for ($attempt = 0; $attempt < 20; $attempt++) {
                if (PlayerUtil::isPositionFree(1, $galaxy, $targetSystem, $targetPlanet)) {
                    try {
                        $colonyId = PlayerUtil::createPlanet($galaxy, $targetSystem, $targetPlanet, 1, $userId, 'Colonia ' . $i, false, AUTH_USR);
                        // Asignar minas e infraestructura básica a la colonia para que pueda producir y reclutar
                        $db->update("UPDATE %%PLANETS%% SET 
                            metal_mine = 18, 
                            crystal_mine = 16, 
                            deuterium_sintetizer = 12, 
                            solar_plant = 20, 
                            robot_factory = 6, 
                            hangar = 8, 
                            small_ship_cargo = 15,
                            metal = 400000, 
                            crystal = 250000, 
                            deuterium = 100000 
                            WHERE id = :colonyId;", array(':colonyId' => $colonyId));
                    } catch (Exception $e) {
                        // Error menor al colonizar, continuar
                    }
                    break;
                }
                $targetSystem = max(1, $targetSystem + 1);
            }
        }
    }

    /**
     * Ejecuta el ciclo de IA para todos los bots activos
     */
    public static function runAllBots()
    {
        self::ensureTables();
        $bots = self::getBots(true);
        $results = array();

        foreach ($bots as $bot) {
            try {
                $res = self::runSingleBot($bot['bot_id']);
                $results[$bot['bot_id']] = $res;
            } catch (Exception $e) {
                $results[$bot['bot_id']] = array('error' => $e->getMessage());
                self::log($bot['bot_id'], 'error', "Fallo en ejecución: " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Ciclo maestro de IA estratégica para un bot individual (v2)
     */
    public static function runSingleBot($botId)
    {
        $db = Database::get();
        self::ensureTables();

        $botData = $db->selectSingle("SELECT * FROM " . DB_PREFIX . "bots WHERE bot_id = :id AND is_active = 1;", array(':id' => $botId));
        if (empty($botData)) {
            return array('status' => 'inactive_or_not_found');
        }

        return BotKernel::processTurn($botData);
    }

    /**
     * Ciclo legado v1 (mantenido por retrocompatibilidad)
     */
    public static function runLegacySingleBot($botId)
    {
        $db = Database::get();
        self::ensureTables();

        $botData = $db->selectSingle("SELECT * FROM " . DB_PREFIX . "bots WHERE bot_id = :id AND is_active = 1;", array(':id' => $botId));
        if (empty($botData)) {
            return array('status' => 'inactive_or_not_found');
        }

        $botUser = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = :id;", array(':id' => $botId));
        if (empty($botUser)) {
            return array('status' => 'user_not_found');
        }

        // Cargar factores y variables
        $botUser['factor'] = getFactors($botUser, 'basic', TIMESTAMP);

        // Si home_planet_id es 0, asignarle el planeta principal
        $homePlanetId = (int)$botData['home_planet_id'];
        if ($homePlanetId == 0) {
            $homePlanetId = (int)$botUser['id_planet'];
            $db->update("UPDATE " . DB_PREFIX . "bots SET home_planet_id = :home WHERE bot_id = :id;", array(
                ':home' => $homePlanetId,
                ':id'   => $botId
            ));
        }

        // Obtener todos los planetas del bot
        $planets = $db->select("SELECT * FROM %%PLANETS%% WHERE id_owner = :botId ORDER BY id ASC;", array(':botId' => $botId));
        if (empty($planets)) {
            return array('status' => 'no_planets');
        }

        $report = array(
            'bot_id'       => $botId,
            'name'         => $botUser['username'],
            'dodged'       => 0,
            'recruited'    => array(),
            'rallied'      => 0,
            'transported'  => 0,
            'spied'        => 0,
            'attacked'     => 0,
            'recycled'     => 0
        );

        // -------------------------------------------------------------
        // PASO 1: REFRESCAR RECURSOS Y COLAS (ResourceUpdate)
        // -------------------------------------------------------------
        require_once 'includes/classes/class.PlanetRessUpdate.php';
        foreach ($planets as $idx => $p) {
            $resourceObj = new ResourceUpdate();
            $resourceObj->setData($botUser, $p);
            $resourceObj->CalcResource();
            $resourceObj->SavePlanetToDB();
        }
        // Recargar planetas tras la actualización de recursos
        $planets = $db->select("SELECT * FROM %%PLANETS%% WHERE id_owner = :botId ORDER BY id ASC;", array(':botId' => $botId));

        // -------------------------------------------------------------
        // PASO 2: DETECCIÓN DE ATAQUES Y FLEETSAVE / AUTO-DODGE
        // -------------------------------------------------------------
        $report['dodged'] = self::checkAndDodgeAttacks($botUser, $planets);

        // -------------------------------------------------------------
        // PASO 3: RECLUTAMIENTO MULTI-PLANETA EN PARALELO
        // -------------------------------------------------------------
        $report['recruited'] = self::executeMultiPlanetRecruitment($botUser, $planets, $botData['personality']);

        // -------------------------------------------------------------
        // PASO 4: CADENA LOGÍSTICA Y UNIFICACIÓN DE FLOTAS (Rallying)
        // -------------------------------------------------------------
        $logisticsResult = self::executeLogisticsAndRally($botUser, $planets, $homePlanetId);
        $report['rallied']     = $logisticsResult['rallied'];
        $report['transported'] = $logisticsResult['transported'];

        // -------------------------------------------------------------
        // PASO 5: ESPIONAJE ACTIVO DE JUGADORES
        // -------------------------------------------------------------
        $report['spied'] = self::executeEspionage($botUser, $planets, $homePlanetId);

        // -------------------------------------------------------------
        // PASO 6: ANÁLISIS DE COMBATE Y ATAQUES TÁCTICOS (Fleet Crash & Raids)
        // -------------------------------------------------------------
        $attackResult = self::executeTacticalAttacks($botUser, $planets, $homePlanetId, $botData['personality']);
        $report['attacked'] = $attackResult['attacks'];
        $report['recycled'] = $attackResult['recycling'];

        // -------------------------------------------------------------
        // PASO 7: INFRAESTRUCTURA ECONÓMICA BÁSICA (Mantenimiento)
        // -------------------------------------------------------------
        self::maintainInfrastructure($botUser, $planets);

        // Actualizar timestamp de última actividad
        $db->update("UPDATE " . DB_PREFIX . "bots SET last_activity = :now WHERE bot_id = :id;", array(
            ':now' => TIMESTAMP,
            ':id'  => $botId
        ));

        return $report;
    }

    /**
     * Detección de ataques hostiles y Fleetsave / Auto-Dodge
     * Si una flota enemiga se aproxima y supera a la defensa + flota local:
     * El bot evacúa toda la flota y todos los recursos transportables hacia otra colonia o campo de escombros,
     * e invierte lo que sobre en defensas instantáneas para dejar el planeta completamente vacío.
     */
    protected static function checkAndDodgeAttacks($botUser, $planets)
    {
        $db = Database::get();
        $dodgedCount = 0;

        $incoming = $db->select("SELECT * FROM %%FLEETS%% 
            WHERE fleet_target_owner = :botId AND fleet_mission = 1 AND fleet_mess = 0 AND fleet_start_time > :now
            ORDER BY fleet_start_time ASC;", array(
            ':botId' => $botUser['id'],
            ':now'   => TIMESTAMP
        ));

        if (empty($incoming)) {
            return 0;
        }

        foreach ($incoming as $threat) {
            $threatPlanetId = (int)$threat['fleet_end_id'];
            $targetPlanet = null;
            $safeDestPlanet = null;

            foreach ($planets as $p) {
                if ($p['id'] == $threatPlanetId) {
                    $targetPlanet = $p;
                } else {
                    $safeDestPlanet = $p; // Colonia de refugio
                }
            }

            if (empty($targetPlanet)) {
                continue;
            }

            // Calcular poder de ataque enemigo entrante
            $threatFleet = FleetFunctions::unserialize($threat['fleet_array']);
            if (!is_array($threatFleet)) {
                $threatFleet = array();
            }
            $threatPower = self::calculateFleetCombatPower($threatFleet);

            // Calcular poder defensor local
            $localFleet = self::getLocalStationedWarships($targetPlanet);
            $localDefPower = self::calculatePlanetDefensePower($targetPlanet);
            $localFleetPower = self::calculateFleetCombatPower($localFleet);
            $totalDefenderPower = $localDefPower + $localFleetPower;

            // Si la flota enemiga es abrumadora (o puede destruir la flota local con pérdidas)
            if ($threatPower > ($totalDefenderPower * 0.65) && array_sum($localFleet) > 0) {
                // FLEETSAVE REQUERIDO
                // Preparar todas las naves para evacuación
                global $reslist, $resource;
                $evacFleet = array();
                $fleetList = !empty($reslist['fleet']) ? $reslist['fleet'] : array(202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 213, 214, 215);

                foreach ($fleetList as $shipId) {
                    if ($shipId == 212) continue; // Satélites solares no pueden volar
                    $col = isset($resource[$shipId]) ? $resource[$shipId] : '';
                    if (!empty($col) && !empty($targetPlanet[$col]) && $targetPlanet[$col] > 0) {
                        $evacFleet[$shipId] = (float)$targetPlanet[$col];
                    }
                }

                if (empty($evacFleet) || array_sum($evacFleet) == 0) {
                    continue;
                }

                // Calcular capacidad de carga
                $capacity = FleetFunctions::GetFleetRoom($evacFleet);

                // Cargar recursos hasta el tope de capacidad
                $metal = (float)$targetPlanet['metal'];
                $crystal = (float)$targetPlanet['crystal'];
                $deuterium = (float)$targetPlanet['deuterium'];

                $loadMetal = min($metal, $capacity * 0.50);
                $capacityLeft = $capacity - $loadMetal;
                $loadCrystal = min($crystal, $capacityLeft * 0.60);
                $capacityLeft -= $loadCrystal;
                $loadDeuterium = min($deuterium, $capacityLeft);

                // Destino de evacuación
                if (!empty($safeDestPlanet)) {
                    $destGalaxy = $safeDestPlanet['galaxy'];
                    $destSystem = $safeDestPlanet['system'];
                    $destPlanet = $safeDestPlanet['planet'];
                    $destType   = 1;
                    $destOwner  = $botUser['id'];
                    $destId     = $safeDestPlanet['id'];
                    $mission    = self::MISSION_DEPLOY; // Desplegar en colonia segura
                } else {
                    // Si no tiene otra colonia, desplegar hacia un campo de escombros cercano
                    $destGalaxy = $targetPlanet['galaxy'];
                    $destSystem = $targetPlanet['system'];
                    $destPlanet = ($targetPlanet['planet'] > 1) ? $targetPlanet['planet'] - 1 : $targetPlanet['planet'] + 1;
                    $destType   = 2; // Debris field
                    $destOwner  = 0;
                    $destId     = 0;
                    $mission    = self::MISSION_RECYCLE;
                }

                $distance = FleetFunctions::GetTargetDistance(
                    array($targetPlanet['galaxy'], $targetPlanet['system'], $targetPlanet['planet']),
                    array($destGalaxy, $destSystem, $destPlanet)
                );
                $maxSpeed = FleetFunctions::GetFleetMaxSpeed($evacFleet, $botUser);
                $gameSpeed = Config::get(1)->fleet_speed;
                $duration = FleetFunctions::GetMissionDuration(10, $maxSpeed, $distance, $gameSpeed, $botUser);
                $speedFactor = FleetFunctions::GetGameSpeedFactor();
                $consumption = FleetFunctions::GetFleetConsumption($evacFleet, $duration, $distance, $botUser, $speedFactor);

                if ($deuterium < $consumption) {
                    $consumption = 0; // Prevenir fallo por milésimas
                } else {
                    $loadDeuterium = max(0, min($loadDeuterium, $deuterium - $consumption));
                }

                $fleetStartTime = TIMESTAMP + $duration;
                $fleetStayTime  = $fleetStartTime;
                $fleetEndTime   = ($mission == self::MISSION_DEPLOY) ? $fleetStartTime : $fleetStayTime + $duration;

                $fleetResources = array(
                    901 => $loadMetal,
                    902 => $loadCrystal,
                    903 => $loadDeuterium
                );

                try {
                    FleetFunctions::sendFleet(
                        $evacFleet,
                        $mission,
                        $botUser['id'],
                        $targetPlanet['id'],
                        $targetPlanet['galaxy'],
                        $targetPlanet['system'],
                        $targetPlanet['planet'],
                        1,
                        $destOwner,
                        $destId,
                        $destGalaxy,
                        $destSystem,
                        $destPlanet,
                        $destType,
                        $fleetResources,
                        $fleetStartTime,
                        $fleetStayTime,
                        $fleetEndTime,
                        0,
                        0,
                        $consumption
                    );

                    // Gastar recursos residuales que quedaron en el planeta en defensas instantáneas
                    $remMetal = max(0, $metal - $loadMetal);
                    $remCrystal = max(0, $crystal - $loadCrystal);
                    $buildLauncher = min(floor($remMetal / 2000), 200);
                    if ($buildLauncher > 0) {
                        $db->update("UPDATE %%PLANETS%% SET misil_launcher = misil_launcher + :cnt, metal = metal - :cost WHERE id = :id;", array(
                            ':cnt'  => $buildLauncher,
                            ':cost' => $buildLauncher * 2000,
                            ':id'   => $targetPlanet['id']
                        ));
                    }

                    $dodgedCount++;
                    self::log($botUser['id'], 'dodge', "¡FLEETSAVE EJECUTADO! Amenaza detectada de jugador ID {$threat['fleet_owner']} (Poder: " . number_format($threatPower) . "). Se evacuaron " . array_sum($evacFleet) . " naves y " . number_format($loadMetal + $loadCrystal + $loadDeuterium) . " recursos hacia [{$destGalaxy}:{$destSystem}:{$destPlanet}]. Planeta atacado dejado vacío.");
                } catch (Exception $e) {
                    self::log($botUser['id'], 'error', "Error en Fleetsave: " . $e->getMessage());
                }
            }
        }

        return $dodgedCount;
    }

    /**
     * Reclutamiento multi-planeta simultáneo
     * Todos los planetas con astillero construyen tropas en paralelo para multiplicar la velocidad militar
     */
    protected static function executeMultiPlanetRecruitment($botUser, $planets, $personality)
    {
        global $resource, $pricelist, $reslist, $CombatCaps;

        $db = Database::get();
        $totalRecruited = array();

        $civilShips = array(202, 203, 208, 209, 210, 212, 217, 219, 233);
        $fleetList = !empty($reslist['fleet']) ? $reslist['fleet'] : array(204, 205, 206, 207, 211, 213, 214, 215);
        $defList   = !empty($reslist['defense']) ? $reslist['defense'] : array(401, 402, 403, 404, 405, 406, 407, 408);

        foreach ($planets as $planet) {
            $hangarLevel = isset($planet['hangar']) ? (int)$planet['hangar'] : 0;
            if ($hangarLevel < 1) {
                continue;
            }

            // Verificar cola actual de astillero
            $currentQueue = (!empty($planet['b_hangar_id']) && is_array($hq = @unserialize($planet['b_hangar_id']))) ? $hq : array();
            if (count($currentQueue) >= 4) {
                continue; // Cola ocupada
            }

            // A. Verificación y reclutamiento logístico (Cargueros, Sondas, Recicladores)
            $logisticsCandidates = array(
                217 => 10, // ev_transporter
                203 => 30, // big cargo
                202 => 30, // small cargo
                210 => 25, // spy probe
                219 => 10, // giga recycler
                209 => 20  // recycler
            );

            foreach ($logisticsCandidates as $logId => $targetCnt) {
                if (count($currentQueue) >= 4) break;
                if (!in_array($logId, $fleetList)) continue;
                if (!BuildFunctions::isTechnologieAccessible($botUser, $planet, $logId, array())) continue;

                $colName = isset($resource[$logId]) ? $resource[$logId] : '';
                $curCnt  = (!empty($colName) && isset($planet[$colName])) ? (int)$planet[$colName] : 0;
                if ($curCnt < $targetCnt) {
                    $needed = $targetCnt - $curCnt;
                    $maxAffordable = BuildFunctions::getMaxConstructibleElements($botUser, $planet, $logId);
                    $batch = min($needed, min($maxAffordable, 20));
                    if ($batch > 0 && BotGameServices::enqueueShipyard($botUser, $planet, $logId, $batch)) {
                        $totalRecruited[$logId] = (isset($totalRecruited[$logId]) ? $totalRecruited[$logId] : 0) + $batch;
                        $currentQueue = (!empty($planet['b_hangar_id']) && is_array($hq = @unserialize($planet['b_hangar_id']))) ? $hq : array();
                    }
                }
            }

            // B. Reclutamiento de naves de combate sobre todas las naves NovaRush (202-267)
            if (count($currentQueue) < 4) {
                $accessibleWarships = array();
                foreach ($fleetList as $shipId) {
                    if (in_array($shipId, $civilShips)) continue;
                    if (!BuildFunctions::isTechnologieAccessible($botUser, $planet, $shipId, array())) continue;

                    $maxAffordable = BuildFunctions::getMaxConstructibleElements($botUser, $planet, $shipId);
                    if ($maxAffordable <= 0) continue;

                    $att = isset($CombatCaps[$shipId]['attack']) ? (float)$CombatCaps[$shipId]['attack'] : 10.0;
                    $shd = isset($CombatCaps[$shipId]['shield']) ? (float)$CombatCaps[$shipId]['shield'] : 10.0;
                    $def = isset($CombatCaps[$shipId]['defend']) ? (float)$CombatCaps[$shipId]['defend'] / 10.0 : 10.0;
                    $power = $att + $shd + $def;

                    $costMSE = BotEconomyValuator::getElementPriceMSE($shipId);
                    $score = sqrt(max(1.0, $power)) * ($power / max(1.0, $costMSE));
                    if ($power > 5000.0) $score += 200.0;

                    $accessibleWarships[] = array(
                        'id'    => $shipId,
                        'power' => $power,
                        'score' => $score,
                        'max'   => $maxAffordable
                    );
                }

                if (!empty($accessibleWarships)) {
                    usort($accessibleWarships, function($a, $b) {
                        return $b['score'] <=> $a['score'];
                    });

                    $best = $accessibleWarships[0];
                    $shipId = $best['id'];
                    $power  = $best['power'];
                    $maxAff = $best['max'];

                    // Escalado dinámico de lote de reclutamiento
                    if ($power > 50000.0) {
                        $batchSize = min($maxAff, max(2, (int)ceil($maxAff * 0.15)));
                    } elseif ($power > 5000.0) {
                        $batchSize = min($maxAff, max(5, (int)ceil($maxAff * 0.25)));
                    } elseif ($power > 500.0) {
                        $batchSize = min($maxAff, max(20, (int)ceil($maxAff * 0.40)));
                    } else {
                        $batchSize = min($maxAff, max(50, (int)ceil($maxAff * 0.60)));
                    }

                    if ($batchSize > 0 && BotGameServices::enqueueShipyard($botUser, $planet, $shipId, $batchSize)) {
                        $totalRecruited[$shipId] = (isset($totalRecruited[$shipId]) ? $totalRecruited[$shipId] : 0) + $batchSize;
                        $currentQueue = (!empty($planet['b_hangar_id']) && is_array($hq = @unserialize($planet['b_hangar_id']))) ? $hq : array();
                    }
                }
            }

            // C. Fortificación defensiva planetaria sobre todas las defensas NovaRush (401-460)
            if (count($currentQueue) < 4) {
                // Prioridad: Cúpulas de protección
                $domeQueued = false;
                if (!empty($reslist['domes'])) {
                    foreach ($reslist['domes'] as $domeId) {
                        if (!BuildFunctions::isTechnologieAccessible($botUser, $planet, $domeId, array())) continue;
                        $colName = isset($resource[$domeId]) ? $resource[$domeId] : '';
                        $curCnt  = (!empty($colName) && isset($planet[$colName])) ? (int)$planet[$colName] : 0;
                        if ($curCnt == 0 && BuildFunctions::getMaxConstructibleElements($botUser, $planet, $domeId) > 0) {
                            if (BotGameServices::enqueueShipyard($botUser, $planet, $domeId, 1)) {
                                $totalRecruited[$domeId] = (isset($totalRecruited[$domeId]) ? $totalRecruited[$domeId] : 0) + 1;
                                $currentQueue = (!empty($planet['b_hangar_id']) && is_array($hq = @unserialize($planet['b_hangar_id']))) ? $hq : array();
                                $domeQueued = true;
                                break;
                            }
                        }
                    }
                }

                if (!$domeQueued) {
                    $accessibleDefs = array();
                    foreach ($defList as $defId) {
                        if (in_array($defId, $reslist['domes']) || in_array($defId, $reslist['missile'])) continue;
                        if (!BuildFunctions::isTechnologieAccessible($botUser, $planet, $defId, array())) continue;

                        $maxAffordable = BuildFunctions::getMaxConstructibleElements($botUser, $planet, $defId);
                        if ($maxAffordable <= 0) continue;

                        $att = isset($CombatCaps[$defId]['attack']) ? (float)$CombatCaps[$defId]['attack'] : 10.0;
                        $shd = isset($CombatCaps[$defId]['shield']) ? (float)$CombatCaps[$defId]['shield'] : 10.0;
                        $def = isset($CombatCaps[$defId]['defend']) ? (float)$CombatCaps[$defId]['defend'] / 10.0 : 10.0;
                        $power = $att + $shd + $def;

                        $costMSE = BotEconomyValuator::getElementPriceMSE($defId);
                        $score = sqrt(max(1.0, $power)) * ($power / max(1.0, $costMSE));
                        if ($power > 1500.0) $score += 150.0;

                        $accessibleDefs[] = array(
                            'id'    => $defId,
                            'power' => $power,
                            'score' => $score,
                            'max'   => $maxAffordable
                        );
                    }

                    if (!empty($accessibleDefs)) {
                        usort($accessibleDefs, function($a, $b) {
                            return $b['score'] <=> $a['score'];
                        });

                        $bestDef = $accessibleDefs[0];
                        $defId   = $bestDef['id'];
                        $power   = $bestDef['power'];
                        $maxAff  = $bestDef['max'];

                        if ($power > 5000.0) {
                            $batchSize = min($maxAff, max(5, (int)ceil($maxAff * 0.25)));
                        } elseif ($power > 500.0) {
                            $batchSize = min($maxAff, max(20, (int)ceil($maxAff * 0.40)));
                        } else {
                            $batchSize = min($maxAff, max(50, (int)ceil($maxAff * 0.60)));
                        }

                        if ($batchSize > 0 && BotGameServices::enqueueShipyard($botUser, $planet, $defId, $batchSize)) {
                            $totalRecruited[$defId] = (isset($totalRecruited[$defId]) ? $totalRecruited[$defId] : 0) + $batchSize;
                        }
                    }
                }
            }
        }

        if (!empty($totalRecruited)) {
            $summary = array();
            foreach ($totalRecruited as $sId => $cnt) {
                $name = isset($resource[$sId]) ? $resource[$sId] : "ID {$sId}";
                $summary[] = "{$name}: {$cnt}";
            }
            self::log($botUser['id'], 'recruit', "Reclutamiento multi-planeta completado en paralelo: " . implode(', ', $summary));
        }

        return $totalRecruited;
    }

    /**
     * Cadena Logística y Concentración Militar (Fleet Rallying)
     * - Las colonias secundarias envían sus naves de guerra hacia la base principal (home_planet_id) para unificarse en una gran armada.
     * - Las colonias con exceso de recursos envían convoyes de transporte a la base principal o a colonias que los necesiten.
     */
    protected static function executeLogisticsAndRally($botUser, $planets, $homePlanetId)
    {
        $ralliedCount = 0;
        $transportCount = 0;

        $homePlanet = null;
        foreach ($planets as $p) {
            if ($p['id'] == $homePlanetId) {
                $homePlanet = $p;
                break;
            }
        }
        if (empty($homePlanet)) {
            $homePlanet = $planets[0];
            $homePlanetId = $homePlanet['id'];
        }

        global $resource;

        foreach ($planets as $colony) {
            if ($colony['id'] == $homePlanetId) {
                continue; // No transferirse a sí mismo
            }

            // 1. UNIFICACIÓN DE FLOTAS (Rallying hacia home_planet_id)
            $warshipsToRally = self::getLocalStationedWarships($colony);

            if (!empty($warshipsToRally) && array_sum($warshipsToRally) >= 10) {
                $distance = FleetFunctions::GetTargetDistance(
                    array($colony['galaxy'], $colony['system'], $colony['planet']),
                    array($homePlanet['galaxy'], $homePlanet['system'], $homePlanet['planet'])
                );
                $maxSpeed = FleetFunctions::GetFleetMaxSpeed($warshipsToRally, $botUser);
                $gameSpeed = Config::get(1)->fleet_speed;
                $duration = FleetFunctions::GetMissionDuration(10, $maxSpeed, $distance, $gameSpeed, $botUser);
                $speedFactor = FleetFunctions::GetGameSpeedFactor();
                $consumption = FleetFunctions::GetFleetConsumption($warshipsToRally, $duration, $distance, $botUser, $speedFactor);

                if ($colony['deuterium'] >= $consumption) {
                    $fleetStartTime = TIMESTAMP + $duration;
                    try {
                        FleetFunctions::sendFleet(
                            $warshipsToRally,
                            self::MISSION_DEPLOY, // Desplegar permanentemente en la base principal
                            $botUser['id'],
                            $colony['id'],
                            $colony['galaxy'],
                            $colony['system'],
                            $colony['planet'],
                            1,
                            $botUser['id'],
                            $homePlanet['id'],
                            $homePlanet['galaxy'],
                            $homePlanet['system'],
                            $homePlanet['planet'],
                            1,
                            array(901 => 0, 902 => 0, 903 => 0),
                            $fleetStartTime,
                            $fleetStartTime,
                            $fleetStartTime,
                            0,
                            0,
                            $consumption
                        );
                        $ralliedCount += array_sum($warshipsToRally);
                        self::log($botUser['id'], 'rally', "Flota unificada: " . array_sum($warshipsToRally) . " naves de guerra transferidas de [{$colony['galaxy']}:{$colony['system']}:{$colony['planet']}] hacia la base central [{$homePlanet['galaxy']}:{$homePlanet['system']}:{$homePlanet['planet']}].");
                    } catch (Exception $e) {
                        // Error de envío
                    }
                }
            }

            // 2. TRANSPORTE DE RECURSOS EXCEDENTES (Supply lines)
            $availCargos = array();
            $cargo217 = (isset($resource[217]) && !empty($colony[$resource[217]])) ? (float)$colony[$resource[217]] : 0;
            $cargo203 = (isset($resource[203]) && !empty($colony[$resource[203]])) ? (float)$colony[$resource[203]] : 0;
            $cargo202 = (isset($resource[202]) && !empty($colony[$resource[202]])) ? (float)$colony[$resource[202]] : 0;

            if ($cargo217 >= 2) {
                $availCargos[217] = min($cargo217, 20);
            } elseif ($cargo203 >= 2) {
                $availCargos[self::SHIP_CARGO_BIG] = min($cargo203, 30);
            } elseif ($cargo202 >= 5) {
                $availCargos[self::SHIP_CARGO_SMALL] = min($cargo202, 50);
            }

            // Si hay exceso de metal o cristal (> 250,000)
            if (!empty($availCargos) && ($colony['metal'] > 250000 || $colony['crystal'] > 150000)) {
                $cargoCapacity = FleetFunctions::GetFleetRoom($availCargos);
                $metalToSend = min(max(0, $colony['metal'] - 100000), $cargoCapacity * 0.60);
                $crystalToSend = min(max(0, $colony['crystal'] - 80000), $cargoCapacity - $metalToSend);

                if (($metalToSend + $crystalToSend) > 80000) {
                    $distance = FleetFunctions::GetTargetDistance(
                        array($colony['galaxy'], $colony['system'], $colony['planet']),
                        array($homePlanet['galaxy'], $homePlanet['system'], $homePlanet['planet'])
                    );
                    $maxSpeed = FleetFunctions::GetFleetMaxSpeed($availCargos, $botUser);
                    $gameSpeed = Config::get(1)->fleet_speed;
                    $duration = FleetFunctions::GetMissionDuration(10, $maxSpeed, $distance, $gameSpeed, $botUser);
                    $speedFactor = FleetFunctions::GetGameSpeedFactor();
                    $consumption = FleetFunctions::GetFleetConsumption($availCargos, $duration, $distance, $botUser, $speedFactor);

                    if ($colony['deuterium'] >= $consumption) {
                        $fleetStartTime = TIMESTAMP + $duration;
                        $fleetEndTime   = $fleetStartTime + $duration;
                        try {
                            FleetFunctions::sendFleet(
                                $availCargos,
                                self::MISSION_TRANSPORT,
                                $botUser['id'],
                                $colony['id'],
                                $colony['galaxy'],
                                $colony['system'],
                                $colony['planet'],
                                1,
                                $botUser['id'],
                                $homePlanet['id'],
                                $homePlanet['galaxy'],
                                $homePlanet['system'],
                                $homePlanet['planet'],
                                1,
                                array(901 => $metalToSend, 902 => $crystalToSend, 903 => 0),
                                $fleetStartTime,
                                $fleetStartTime,
                                $fleetEndTime,
                                0,
                                0,
                                $consumption
                            );
                            $transportCount++;
                            self::log($botUser['id'], 'transport', "Convoy de suministros enviado: " . number_format($metalToSend + $crystalToSend) . " recursos transportados de [{$colony['galaxy']}:{$colony['system']}:{$colony['planet']}] hacia la base central.");
                        } catch (Exception $e) {
                            // Error de envío
                        }
                    }
                }
            }
        }

        return array('rallied' => $ralliedCount, 'transported' => $transportCount);
    }

    /**
     * Espionaje Activo
     * Envía sondas de espionaje a planetas de jugadores para obtener inteligencia sobre defensas y recursos
     */
    protected static function executeEspionage($botUser, $planets, $homePlanetId)
    {
        $db = Database::get();
        $spiedCount = 0;

        // Buscar una colonia o base que tenga sondas disponibles
        $launchPlanet = null;
        foreach ($planets as $p) {
            if (!empty($p['spy_sonde']) && $p['spy_sonde'] >= 3) {
                $launchPlanet = $p;
                break;
            }
        }

        if (empty($launchPlanet)) {
            return 0;
        }

        // Verificar slots de flota libres
        $maxSlots = FleetFunctions::GetMaxFleetSlots($botUser);
        $usedSlots = (int)$db->selectSingle("SELECT COUNT(*) as cnt FROM %%FLEETS%% WHERE fleet_owner = :id;", array(':id' => (int)$botUser['id']), 'cnt');
        if ($usedSlots >= $maxSlots) {
            return 0;
        }

        // Buscar objetivos válidos (no administradores, no el mismo bot, no vacaciones)
        $targets = $db->select("SELECT p.id, p.id_owner, p.galaxy, p.system, p.planet, u.username
            FROM %%PLANETS%% as p
            INNER JOIN %%USERS%% as u ON u.id = p.id_owner
            WHERE p.id_owner != :botId AND u.authlevel = 0 AND u.urlaubs_modus = 0 AND p.planet_type = 1
            ORDER BY RAND() LIMIT 3;", array(':botId' => $botUser['id']));

        if (empty($targets)) {
            return 0;
        }

        foreach ($targets as $target) {
            if ($usedSlots >= $maxSlots) {
                break;
            }

            $probesToSend = min((float)$launchPlanet['spy_sonde'], 4);
            $fleetArray = array(self::SHIP_SPY_PROBE => $probesToSend);

            $distance = FleetFunctions::GetTargetDistance(
                array($launchPlanet['galaxy'], $launchPlanet['system'], $launchPlanet['planet']),
                array($target['galaxy'], $target['system'], $target['planet'])
            );
            $maxSpeed = FleetFunctions::GetFleetMaxSpeed($fleetArray, $botUser);
            $gameSpeed = Config::get(1)->fleet_speed;
            $duration = FleetFunctions::GetMissionDuration(10, $maxSpeed, $distance, $gameSpeed, $botUser);
            $speedFactor = FleetFunctions::GetGameSpeedFactor();
            $consumption = FleetFunctions::GetFleetConsumption($fleetArray, $duration, $distance, $botUser, $speedFactor);

            if ($launchPlanet['deuterium'] < $consumption) {
                continue;
            }

            $fleetStartTime = TIMESTAMP + $duration;
            $fleetEndTime   = $fleetStartTime + $duration;

            try {
                FleetFunctions::sendFleet(
                    $fleetArray,
                    self::MISSION_SPY,
                    $botUser['id'],
                    $launchPlanet['id'],
                    $launchPlanet['galaxy'],
                    $launchPlanet['system'],
                    $launchPlanet['planet'],
                    1,
                    $target['id_owner'],
                    $target['id'],
                    $target['galaxy'],
                    $target['system'],
                    $target['planet'],
                    1,
                    array(901 => 0, 902 => 0, 903 => 0),
                    $fleetStartTime,
                    $fleetStartTime,
                    $fleetEndTime,
                    0,
                    0,
                    $consumption
                );

                $spiedCount++;
                $usedSlots++;
                self::log($botUser['id'], 'spy', "Misión de espionaje lanzada hacia {$target['username']} [{$target['galaxy']}:{$target['system']}:{$target['planet']}] con {$probesToSend} sondas.");
                break; // 1 espionaje por ciclo para mantener ritmo orgánico
            } catch (Exception $e) {
                // Continuar
            }
        }

        return $spiedCount;
    }

    /**
     * Decisiones de Combate y Ataques Tácticos (Fleet Crash y Saqueos)
     * - Evalúa objetivos cercanos en la galaxia
     * - Calcula el poder de combate del objetivo vs poder de la armada del bot
     * - Si el bot tiene ventaja decisiva (>= 1.6x de poder de combate): lanza ataque y envía recicladores tras la batalla
     * - Si el objetivo es un inactivo con recursos y sin defensas: lanza saqueo (Raid) con cargueros y escolta
     * - Si la batalla es riesgosa o desventajosa: NO ataca
     */
    protected static function executeTacticalAttacks($botUser, $planets, $homePlanetId, $personality)
    {
        $db = Database::get();
        $attacksCount = 0;
        $recyclingCount = 0;

        // Verificar slots de flota
        $maxSlots = FleetFunctions::GetMaxFleetSlots($botUser);
        $usedSlots = (int)$db->selectSingle("SELECT COUNT(*) as cnt FROM %%FLEETS%% WHERE fleet_owner = :id;", array(':id' => (int)$botUser['id']), 'cnt');
        if ($usedSlots >= $maxSlots) {
            return array('attacks' => 0, 'recycling' => 0);
        }

        // Buscar base principal donde se concentran las naves
        $homePlanet = null;
        foreach ($planets as $p) {
            if ($p['id'] == $homePlanetId) {
                $homePlanet = $p;
                break;
            }
        }
        if (empty($homePlanet)) {
            $homePlanet = $planets[0];
        }

        $botWarships = self::getLocalStationedWarships($homePlanet);
        if (empty($botWarships) || array_sum($botWarships) < 20) {
            return array('attacks' => 0, 'recycling' => 0); // Armada insuficiente aún
        }

        $botCombatPower = self::calculateFleetCombatPower($botWarships);

        // Buscar planetas candidatos en la galaxia (excluir admin, self, y ataques ya en curso)
        $targets = $db->select("SELECT p.*, u.username, u.onlinetime, u.authlevel
            FROM %%PLANETS%% as p
            INNER JOIN %%USERS%% as u ON u.id = p.id_owner
            WHERE p.id_owner != :botId AND u.authlevel = 0 AND u.urlaubs_modus = 0 AND p.planet_type = 1
            AND p.id NOT IN (SELECT fleet_end_id FROM %%FLEETS%% WHERE fleet_owner = :botId2 AND fleet_mission = 1)
            ORDER BY ABS(p.system - :homeSys) ASC LIMIT 5;", array(
            ':botId'   => $botUser['id'],
            ':botId2'  => $botUser['id'],
            ':homeSys' => $homePlanet['system']
        ));

        if (empty($targets)) {
            return array('attacks' => 0, 'recycling' => 0);
        }

        foreach ($targets as $target) {
            if ($usedSlots >= $maxSlots) {
                break;
            }

            $targetFleet = self::getLocalStationedWarships($target);
            $targetDefPower = self::calculatePlanetDefensePower($target);
            $targetFleetPower = self::calculateFleetCombatPower($targetFleet);
            $totalTargetPower = $targetDefPower + $targetFleetPower;

            $lootableMetal = (float)$target['metal'] * 0.50;
            $lootableCrystal = (float)$target['crystal'] * 0.50;
            $lootableDeut = (float)$target['deuterium'] * 0.50;
            $totalLoot = $lootableMetal + $lootableCrystal + $lootableDeut;

            $isInactive = ($target['onlinetime'] < (TIMESTAMP - 7 * 86400));

            // CASO A: SAQUEO DE GRANJA / INACTIVO
            if ($totalTargetPower < 500 && $totalLoot > 40000) {
                // Se envía flota de saqueo: Cargos necesarios + pequeña escolta
                $cargosToSend = (int)ceil($totalLoot / 25000); // Grandes cargueros
                $availBigCargos = (int)$homePlanet['big_ship_cargo'];
                $availSmallCargos = (int)$homePlanet['small_ship_cargo'];

                $strikeFleet = array();
                if ($availBigCargos >= $cargosToSend) {
                    $strikeFleet[self::SHIP_CARGO_BIG] = min($availBigCargos, $cargosToSend + 2);
                } elseif ($availSmallCargos >= 10) {
                    $strikeFleet[self::SHIP_CARGO_SMALL] = min($availSmallCargos, 50);
                }

                // Escolta rápida
                if (!empty($homePlanet['light_hunter']) && $homePlanet['light_hunter'] >= 10) {
                    $strikeFleet[self::SHIP_LIGHT_HUNTER] = 15;
                }
                if (!empty($homePlanet['crusher']) && $homePlanet['crusher'] >= 5) {
                    $strikeFleet[self::SHIP_CRUISER] = 5;
                }

                if (!empty($strikeFleet)) {
                    $sent = self::dispatchMission(
                        $botUser,
                        $homePlanet,
                        $target,
                        $strikeFleet,
                        self::MISSION_ATTACK,
                        "¡SAQUEO TÁCTICO! Bot lanzó raid a colonia de {$target['username']} [{$target['galaxy']}:{$target['system']}:{$target['planet']}] con botín estimado de " . number_format($totalLoot) . " recursos."
                    );
                    if ($sent) {
                        $attacksCount++;
                        $usedSlots++;
                        break;
                    }
                }
            }

            // CASO B: FLEET CRASH / BATALLA FLOTA CONTRA FLOTA
            // Condición: El bot debe tener una superioridad aplastante (al menos 1.7x el poder total del defensor)
            if ($totalTargetPower > 500 && $botCombatPower >= ($totalTargetPower * 1.70)) {
                // Preparar armada de ataque proporcional (70% a 90% de sus naves de guerra)
                $attackFleet = array();
                foreach ($botWarships as $sId => $cnt) {
                    $attackFleet[$sId] = max(1, floor($cnt * 0.85));
                }

                // Añadir cargueros para recolectar el botín tras destruir la defensa
                if (!empty($homePlanet['big_ship_cargo']) && $homePlanet['big_ship_cargo'] >= 10) {
                    $attackFleet[self::SHIP_CARGO_BIG] = min((float)$homePlanet['big_ship_cargo'], 40);
                }

                $sent = self::dispatchMission(
                    $botUser,
                    $homePlanet,
                    $target,
                    $attackFleet,
                    self::MISSION_ATTACK,
                    "¡BATALLA LANZADA! Armada de combate del bot atacando flota/defensa de {$target['username']} [{$target['galaxy']}:{$target['system']}:{$target['planet']}]. Poder Bot: " . number_format($botCombatPower) . " vs Defensor: " . number_format($totalTargetPower) . " (Ratio de Victoria: " . round($botCombatPower / max(1, $totalTargetPower), 2) . "x)."
                );

                if ($sent) {
                    $attacksCount++;
                    $usedSlots++;

                    // Despachar recicladores para recoger los escombros de la batalla
                    if (!empty($homePlanet['recycler']) && $homePlanet['recycler'] >= 10 && $usedSlots < $maxSlots) {
                        $recyclersToSend = min((float)$homePlanet['recycler'], 60);
                        $recyFleet = array(self::SHIP_RECYCLER => $recyclersToSend);

                        self::dispatchMission(
                            $botUser,
                            $homePlanet,
                            $target,
                            $recyFleet,
                            self::MISSION_RECYCLE,
                            "Recicladores enviados al campo de escombros de [{$target['galaxy']}:{$target['system']}:{$target['planet']}].",
                            2 // Target type: Debris field
                        );
                        $recyclingCount++;
                        $usedSlots++;
                    }
                    break;
                }
            }
        }

        return array('attacks' => $attacksCount, 'recycling' => $recyclingCount);
    }

    /**
     * Envía una misión de flota y la registra en el log
     */
    protected static function dispatchMission($botUser, $originPlanet, $targetPlanet, $fleetArray, $mission, $logMsg, $destType = 1)
    {
        $distance = FleetFunctions::GetTargetDistance(
            array($originPlanet['galaxy'], $originPlanet['system'], $originPlanet['planet']),
            array($targetPlanet['galaxy'], $targetPlanet['system'], $targetPlanet['planet'])
        );
        $maxSpeed = FleetFunctions::GetFleetMaxSpeed($fleetArray, $botUser);
        $gameSpeed = Config::get(1)->fleet_speed;
        $duration = FleetFunctions::GetMissionDuration(10, $maxSpeed, $distance, $gameSpeed, $botUser);
        $speedFactor = FleetFunctions::GetGameSpeedFactor();
        $consumption = FleetFunctions::GetFleetConsumption($fleetArray, $duration, $distance, $botUser, $speedFactor);

        if ($originPlanet['deuterium'] < $consumption) {
            return false;
        }

        $fleetStartTime = TIMESTAMP + $duration;
        $fleetEndTime   = $fleetStartTime + $duration;

        try {
            FleetFunctions::sendFleet(
                $fleetArray,
                $mission,
                $botUser['id'],
                $originPlanet['id'],
                $originPlanet['galaxy'],
                $originPlanet['system'],
                $originPlanet['planet'],
                1,
                $targetPlanet['id_owner'],
                $targetPlanet['id'],
                $targetPlanet['galaxy'],
                $targetPlanet['system'],
                $targetPlanet['planet'],
                $destType,
                array(901 => 0, 902 => 0, 903 => 0),
                $fleetStartTime,
                $fleetStartTime,
                $fleetEndTime,
                0,
                0,
                $consumption
            );
            self::log($botUser['id'], ($mission == self::MISSION_ATTACK ? 'attack' : ($mission == self::MISSION_RECYCLE ? 'recycle' : 'fleet')), $logMsg);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Mantenimiento y desarrollo económico de colonias
     * Maneja energía con satélites solares (212) y plantas (4, 12),
     * expansión de campos con Terraformer (33),
     * potenciador con Módulo de Recursos (81) y mejora de minas.
     */
    protected static function maintainInfrastructure($botUser, $planets)
    {
        global $resource;

        foreach ($planets as $p) {
            $freeFields = CalculateMaxPlanetFields($p) - (int)$p['field_current'];

            // 1. Manejo de déficit energético: Satélites Solares (212) o Planta Solar (4)
            if ($p['energy'] < 0) {
                if (BuildFunctions::isTechnologieAccessible($botUser, $p, 212, array())) {
                    $energyPerSat = max(1.0, (($p['temp_max'] + 160) / 6.0));
                    $satsNeeded   = min(50, (int)ceil(abs($p['energy']) / $energyPerSat));
                    $maxCanSats   = BuildFunctions::getMaxConstructibleElements($botUser, $p, 212);
                    $satsToBuild  = min($satsNeeded, $maxCanSats);
                    if ($satsToBuild > 0) {
                        BotGameServices::enqueueShipyard($botUser, $p, 212, $satsToBuild);
                    }
                } elseif ($freeFields > 0 && BuildFunctions::isTechnologieAccessible($botUser, $p, 4, array())) {
                    BotGameServices::enqueueBuilding($botUser, $p, 4);
                }
            }

            // 2. Expansión de campos: Terraformer (33) cuando quedan pocos campos libres
            if ($freeFields <= 2 && BuildFunctions::isTechnologieAccessible($botUser, $p, 33, array())) {
                BotGameServices::enqueueBuilding($botUser, $p, 33);
                $freeFields = CalculateMaxPlanetFields($p) - (int)$p['field_current'];
            }

            // 3. Potenciador de producción: Módulo de Recursos NovaRush (81)
            if (BuildFunctions::isTechnologieAccessible($botUser, $p, 81, array())) {
                BotGameServices::enqueueBuilding($botUser, $p, 81);
            }

            // 4. Desarrollo de minas (Metal 1, Cristal 2, Deuterio 3) si hay campos disponibles
            if ($freeFields > 0) {
                $mLevel = isset($p[$resource[1]]) ? (int)$p[$resource[1]] : 0;
                $cLevel = isset($p[$resource[2]]) ? (int)$p[$resource[2]] : 0;
                $dLevel = isset($p[$resource[3]]) ? (int)$p[$resource[3]] : 0;

                // Priorizar la mina según balance económico
                $targetMine = 1;
                if ($mLevel > $cLevel + 2) {
                    $targetMine = 2;
                } elseif ($cLevel > $dLevel + 2) {
                    $targetMine = 3;
                }

                if (BuildFunctions::isTechnologieAccessible($botUser, $p, $targetMine, array())) {
                    BotGameServices::enqueueBuilding($botUser, $p, $targetMine);
                }
            }
        }
    }

    /**
     * Obtiene array con las naves de combate estacionadas en un planeta
     */
    public static function getLocalStationedWarships($planet)
    {
        global $reslist, $resource;
        $warships = array();
        $civilShips = array(202, 203, 208, 209, 210, 212, 217, 219, 233);
        $fleetList = !empty($reslist['fleet']) ? $reslist['fleet'] : array(204, 205, 206, 207, 211, 213, 214, 215);

        foreach ($fleetList as $id) {
            if (in_array($id, $civilShips)) continue;
            $col = isset($resource[$id]) ? $resource[$id] : '';
            if (!empty($col) && !empty($planet[$col]) && $planet[$col] > 0) {
                $warships[$id] = (float)$planet[$col];
            }
        }
        return $warships;
    }

    /**
     * Calcula una puntuación de combate para una flota dinámica de NovaRush
     */
    public static function calculateFleetCombatPower($fleetArray)
    {
        global $CombatCaps;
        if (empty($fleetArray)) return 0;

        $power = 0;
        foreach ($fleetArray as $shipId => $cnt) {
            if (isset($CombatCaps[$shipId])) {
                $att = (float)$CombatCaps[$shipId]['attack'];
                $shd = (float)$CombatCaps[$shipId]['shield'];
                $def = (float)$CombatCaps[$shipId]['defend'] / 10.0;
                $unitPower = max(10.0, $att + $shd + $def);
            } else {
                $unitPower = 100.0;
            }
            $power += $unitPower * (float)$cnt;
        }

        return $power;
    }

    /**
     * Calcula una puntuación defensiva dinámica para todas las defensas de NovaRush
     */
    public static function calculatePlanetDefensePower($planet)
    {
        global $reslist, $resource, $CombatCaps;
        $power = 0;
        $defList = !empty($reslist['defense']) ? $reslist['defense'] : array(401, 402, 403, 404, 405, 406, 407, 408);

        foreach ($defList as $defId) {
            $col = isset($resource[$defId]) ? $resource[$defId] : '';
            if (!empty($col) && !empty($planet[$col]) && $planet[$col] > 0) {
                if (isset($CombatCaps[$defId])) {
                    $att = (float)$CombatCaps[$defId]['attack'];
                    $shd = (float)$CombatCaps[$defId]['shield'];
                    $def = (float)$CombatCaps[$defId]['defend'] / 10.0;
                    $unitPower = max(10.0, $att + $shd + $def);
                } else {
                    $unitPower = 100.0;
                }
                $power += $unitPower * (float)$planet[$col];
            }
        }
        return $power;
    }
}

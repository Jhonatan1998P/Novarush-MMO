<?php

/*
 * NovaRush - War Event Boss Engine
 * Motor Automatizado de Eventos de Guerra PvE/PvP con Budget Dinámico,
 * Cuenta Regresiva Previa, Aviso en Pantalla Principal y Lore Inmersivo.
 */

class WarEventBossEngine
{
    const NPC_USER_ID = 999;
    const NPC_USERNAME = 'Imperio Ancestral';

    public static function getUnitRatios()
    {
        return array(
            242 => array('name' => 'Caza Fénix', 'ratio' => 0.072, 'cost' => 10000),
            206 => array('name' => 'Crucero', 'ratio' => 0.104, 'cost' => 15000),
            213 => array('name' => 'Destructor', 'ratio' => 0.175, 'cost' => 60000),
            215 => array('name' => 'Acorazado', 'ratio' => 0.062, 'cost' => 50000),
            404 => array('name' => 'Cañón Gauss', 'ratio' => 0.153, 'cost' => 20000),
            406 => array('name' => 'Cañón Plasma', 'ratio' => 0.155, 'cost' => 50000),
            417 => array('name' => 'Supercañón Dora', 'ratio' => 0.260, 'cost' => 350000),
            408 => array('name' => 'Cúpula Grande', 'ratio' => 0.000, 'cost' => 5000000, 'fixed' => 1),
        );
    }

    public static function calculateServerBudget($universe = 1)
    {
        global $reslist, $pricelist, $resource;
        $db = Database::get();

        $sql = "SELECT p.* FROM %%PLANETS%% p 
                JOIN %%USERS%% u ON u.id = p.id_owner 
                WHERE u.id != 1 AND u.universe = :universe;";
        $planets = $db->select($sql, array(':universe' => $universe));

        $totalMetal = 0;

        foreach ($planets as $p) {
            foreach ($reslist['fleet'] as $f) {
                if (isset($p[$resource[$f]]) && $p[$resource[$f]] > 0) {
                    $totalMetal += $p[$resource[$f]] * $pricelist[$f]['cost'][901];
                }
            }
            foreach ($reslist['defense'] as $d) {
                if (isset($p[$resource[$d]]) && $p[$resource[$d]] > 0) {
                    $totalMetal += $p[$resource[$d]] * $pricelist[$d]['cost'][901];
                }
            }
        }

        return max(1000000, $totalMetal);
    }

    public static function calculateTechnologiesAverage($universe = 1)
    {
        $db = Database::get();
        $sql = "SELECT 
                    AVG(military_tech) as avg_military,
                    AVG(defence_tech) as avg_defence,
                    AVG(shield_tech) as avg_shield,
                    AVG(laser_tech) as avg_laser,
                    AVG(ionic_tech) as avg_ionic,
                    AVG(buster_tech) as avg_buster
                FROM %%USERS%% 
                WHERE id != 1 AND universe = :universe 
                AND (military_tech > 0 OR defence_tech > 0 OR shield_tech > 0);";
        $row = $db->selectSingle($sql, array(':universe' => $universe));

        if (empty($row) || empty($row['avg_military'])) {
            return array(
                'military_tech' => 13,
                'defence_tech' => 15,
                'shield_tech' => 15,
                'laser_tech' => 16,
                'ionic_tech' => 12,
                'buster_tech' => 11,
            );
        }

        return array(
            'military_tech' => max(1, (int) round($row['avg_military'])),
            'defence_tech'  => max(1, (int) round($row['avg_defence'])),
            'shield_tech'   => max(1, (int) round($row['avg_shield'])),
            'laser_tech'    => max(1, (int) round($row['avg_laser'])),
            'ionic_tech'    => max(1, (int) round($row['avg_ionic'])),
            'buster_tech'   => max(1, (int) round($row['avg_buster'])),
        );
    }

    public static function computeUnitsForBudget($budget)
    {
        $ratios = self::getUnitRatios();
        $units = array();

        foreach ($ratios as $elementId => $data) {
            if (isset($data['fixed'])) {
                $units[$elementId] = (int) $data['fixed'];
            } else {
                $allocatedMetal = $budget * $data['ratio'];
                $units[$elementId] = max(0, (int) floor($allocatedMetal / $data['cost']));
            }
        }

        return $units;
    }

    public static function ensureNpcUser($techs, $universe = 1)
    {
        $db = Database::get();
        $npc = $db->selectSingle("SELECT id FROM %%USERS%% WHERE id = :id;", array(':id' => self::NPC_USER_ID));

        if (empty($npc)) {
            $sql = "INSERT INTO %%USERS%% SET 
                    id = :id,
                    username = :name,
                    password = '',
                    email = 'npc_boss@novarush.local',
                    authlevel = 0,
                    universe = :universe,
                    register_time = :time,
                    military_tech = :mil,
                    defence_tech = :def,
                    shield_tech = :shield,
                    laser_tech = :laser,
                    ionic_tech = :ion,
                    buster_tech = :plasma;";
            $db->insert($sql, array(
                ':id'       => self::NPC_USER_ID,
                ':name'     => self::NPC_USERNAME,
                ':universe' => $universe,
                ':time'     => TIMESTAMP,
                ':mil'      => $techs['military_tech'],
                ':def'      => $techs['defence_tech'],
                ':shield'   => $techs['shield_tech'],
                ':laser'    => $techs['laser_tech'],
                ':ion'      => $techs['ionic_tech'],
                ':plasma'   => $techs['buster_tech'],
            ));
        } else {
            $sql = "UPDATE %%USERS%% SET 
                    authlevel = 0,
                    military_tech = :mil,
                    defence_tech = :def,
                    shield_tech = :shield,
                    laser_tech = :laser,
                    ionic_tech = :ion,
                    buster_tech = :plasma
                    WHERE id = :id;";
            $db->update($sql, array(
                ':id'     => self::NPC_USER_ID,
                ':mil'    => $techs['military_tech'],
                ':def'    => $techs['defence_tech'],
                ':shield' => $techs['shield_tech'],
                ':laser'  => $techs['laser_tech'],
                ':ion'    => $techs['ionic_tech'],
                ':plasma' => $techs['buster_tech'],
            ));
        }

        return self::NPC_USER_ID;
    }

    public static function getScheduledEvent($universe = 1)
    {
        $db = Database::get();
        $sql = "SELECT * FROM %%WAR_EVENTS%% WHERE status = 'scheduled' ORDER BY id DESC LIMIT 1;";
        $event = $db->selectSingle($sql);

        if (!empty($event)) {
            if ($event['start_time'] <= TIMESTAMP) {
                return self::spawnActiveBoss($event, $event['id']);
            }
            return $event;
        }

        return null;
    }

    public static function checkScheduledActivation($universe = 1)
    {
        $db = Database::get();
        $sql = "SELECT * FROM %%WAR_EVENTS%% WHERE status = 'scheduled' AND start_time <= :now ORDER BY id ASC LIMIT 1;";
        $event = $db->selectSingle($sql, array(':now' => TIMESTAMP));

        if (!empty($event)) {
            return self::spawnActiveBoss($event, $event['id']);
        }

        return null;
    }

    public static function getActiveEvent($universe = 1)
    {
        self::checkScheduledActivation($universe);

        $db = Database::get();
        $sql = "SELECT * FROM %%WAR_EVENTS%% WHERE status = 'active' ORDER BY id DESC LIMIT 1;";
        $event = $db->selectSingle($sql);

        if (!empty($event)) {
            if ($event['end_time'] > 0 && TIMESTAMP >= $event['end_time']) {
                self::expireEvent($event['id']);
                return null;
            }
        }

        return $event;
    }

    public static function getEventStatus($universe = 1)
    {
        self::checkScheduledActivation($universe);

        $active = self::getActiveEvent($universe);
        if (!empty($active)) {
            $db = Database::get();
            $planet = $db->selectSingle("SELECT * FROM %%PLANETS%% WHERE id = :id;", array(':id' => $active['planet_id']));

            global $resource;
            $ratios = self::getUnitRatios();
            $survivingUnits = array();
            $totalSurviving = 0;

            if (!empty($planet)) {
                foreach ($ratios as $elementId => $data) {
                    $col = $resource[$elementId];
                    $amount = isset($planet[$col]) ? (int)$planet[$col] : 0;
                    $survivingUnits[$elementId] = array(
                        'name' => $data['name'],
                        'amount' => $amount
                    );
                    $totalSurviving += $amount;
                }
            }

            $remainingSeconds = max(0, $active['end_time'] - TIMESTAMP);

            return array(
                'active'                => true,
                'scheduled'             => false,
                'event'                 => $active,
                'planet'                => $planet,
                'remaining_seconds'     => $remainingSeconds,
                'surviving_units'       => $survivingUnits,
                'total_surviving_units' => $totalSurviving
            );
        }

        $scheduled = self::getScheduledEvent($universe);
        if (!empty($scheduled)) {
            $countdownSeconds = max(0, $scheduled['start_time'] - TIMESTAMP);
            return array(
                'active'            => false,
                'scheduled'         => true,
                'event'             => $scheduled,
                'countdown_seconds' => $countdownSeconds,
            );
        }

        $db = Database::get();
        $last = $db->selectSingle("SELECT * FROM %%WAR_EVENTS%% ORDER BY id DESC LIMIT 1;");
        return array(
            'active'     => false,
            'scheduled'  => false,
            'last_event' => $last
        );
    }

    public static function getBannerData($universe = 1)
    {
        if (empty($universe)) {
            $universe = 1;
        }

        self::checkScheduledActivation($universe);

        $active = self::getActiveEvent($universe);
        if (!empty($active)) {
            $db = Database::get();
            $planet = $db->selectSingle("SELECT * FROM %%PLANETS%% WHERE id = :id;", array(':id' => $active['planet_id']));
            global $resource;
            $ratios = self::getUnitRatios();
            $totalSurviving = 0;
            if (!empty($planet)) {
                foreach ($ratios as $elementId => $data) {
                    $col = $resource[$elementId];
                    if (isset($planet[$col]) && $planet[$col] > 0) {
                        $totalSurviving += (int)$planet[$col];
                    }
                }
            }

            return array(
                'state'             => 'active',
                'name'              => $active['event_name'],
                'galaxy'            => (int)$active['galaxy'],
                'system'            => (int)$active['system'],
                'planet'            => (int)$active['planet'],
                'coords'            => "[{$active['galaxy']}:{$active['system']}:{$active['planet']}]",
                'countdown_seconds' => max(0, $active['end_time'] - TIMESTAMP),
                'end_time'          => (int)$active['end_time'],
                'duration_hours'    => (int)$active['duration_hours'],
                'containers'        => (int)$active['containers_per_winner'],
                'antimatter'        => (int)$active['antimatter_per_winner'],
                'surviving_units'   => $totalSurviving,
            );
        }

        $scheduled = self::getScheduledEvent($universe);
        if (!empty($scheduled)) {
            return array(
                'state'             => 'scheduled',
                'name'              => $scheduled['event_name'],
                'countdown_seconds' => max(0, $scheduled['start_time'] - TIMESTAMP),
                'start_time'        => (int)$scheduled['start_time'],
                'duration_hours'    => (int)$scheduled['duration_hours'],
                'containers'        => (int)$scheduled['containers_per_winner'],
                'antimatter'        => (int)$scheduled['antimatter_per_winner'],
            );
        }

        return null;
    }

    public static function startEvent($params = array())
    {
        $active = self::getActiveEvent();
        if (!empty($active)) {
            throw new Exception("Ya existe un evento de guerra activo en [{$active['galaxy']}:{$active['system']}:{$active['planet']}]. Finalízalo antes de iniciar otro.");
        }

        $scheduled = self::getScheduledEvent();
        if (!empty($scheduled)) {
            throw new Exception("Ya existe un evento de guerra programado en cuenta regresiva. Cancélalo antes de programar otro.");
        }

        $universe = Universe::current();
        if (empty($universe)) {
            $universe = 1;
        }

        $budgetRatio    = isset($params['budget_ratio']) ? floatval($params['budget_ratio']) : 0.60;
        $durationHours  = isset($params['duration_hours']) ? max(1, intval($params['duration_hours'])) : 24;
        $preCountdown   = isset($params['pre_countdown_minutes']) ? max(0, intval($params['pre_countdown_minutes'])) : 0;
        $containers     = isset($params['containers_per_winner']) ? max(1, intval($params['containers_per_winner'])) : 50;
        $antimatter     = isset($params['antimatter_per_winner']) ? max(0, intval($params['antimatter_per_winner'])) : 100;
        $minSystem      = isset($params['min_system']) ? max(1, intval($params['min_system'])) : 1;
        $maxSystem      = isset($params['max_system']) ? min(400, intval($params['max_system'])) : 200;

        $serverBudget   = self::calculateServerBudget($universe);
        $bossBudget     = round($serverBudget * $budgetRatio);

        $lootRatio      = isset($params['loot_ratio']) ? floatval($params['loot_ratio']) : 0.40;
        $totalLootBudget = round($serverBudget * $lootRatio);

        // Distribución 4:2:1 (Metal ~57.14%, Cristal ~28.57%, Deuterio ~14.29%)
        $defaultLootMetal     = round($totalLootBudget * (4 / 7));
        $defaultLootCrystal   = round($totalLootBudget * (2 / 7));
        $defaultLootDeuterium = round($totalLootBudget * (1 / 7));

        $lootMetal      = (isset($params['loot_metal']) && $params['loot_metal'] > 0) ? floatval($params['loot_metal']) : $defaultLootMetal;
        $lootCrystal    = (isset($params['loot_crystal']) && $params['loot_crystal'] > 0) ? floatval($params['loot_crystal']) : $defaultLootCrystal;
        $lootDeuterium  = (isset($params['loot_deuterium']) && $params['loot_deuterium'] > 0) ? floatval($params['loot_deuterium']) : $defaultLootDeuterium;

        if ($preCountdown > 0) {
            $startTime = TIMESTAMP + ($preCountdown * 60);
            $endTime   = $startTime + ($durationHours * 3600);
            $system    = mt_rand($minSystem, $maxSystem);

            $db = Database::get();
            $insertSql = "INSERT INTO %%WAR_EVENTS%% SET 
                event_name = :name,
                status = 'scheduled',
                budget_ratio = :ratio,
                calculated_budget = :budget,
                galaxy = 1,
                system = :system,
                planet = 8,
                planet_id = 0,
                npc_user_id = :npc_id,
                containers_per_winner = :containers,
                antimatter_per_winner = :antimatter,
                duration_hours = :duration,
                pre_countdown_minutes = :pre_countdown,
                loot_metal = :metal,
                loot_crystal = :crystal,
                loot_deuterium = :deut,
                start_time = :start,
                end_time = :end;";

            $db->insert($insertSql, array(
                ':name'          => 'Fortaleza Ancestral',
                ':ratio'         => $budgetRatio,
                ':budget'        => $bossBudget,
                ':system'        => $system,
                ':npc_id'        => self::NPC_USER_ID,
                ':containers'    => $containers,
                ':antimatter'    => $antimatter,
                ':duration'      => $durationHours,
                ':pre_countdown' => $preCountdown,
                ':metal'         => $lootMetal,
                ':crystal'       => $lootCrystal,
                ':deut'          => $lootDeuterium,
                ':start'         => $startTime,
                ':end'           => $endTime
            ));

            return array(
                'scheduled'             => true,
                'pre_countdown_minutes' => $preCountdown,
                'start_time'            => $startTime,
                'duration'              => $durationHours,
                'budget'                => $bossBudget
            );
        }

        return self::spawnActiveBoss($params);
    }

    public static function spawnActiveBoss($params = array(), $existingEventId = null)
    {
        $universe = Universe::current();
        if (empty($universe)) {
            $universe = 1;
        }

        $budgetRatio    = isset($params['budget_ratio']) ? floatval($params['budget_ratio']) : 0.60;
        $durationHours  = isset($params['duration_hours']) ? max(1, intval($params['duration_hours'])) : 24;
        $containers     = isset($params['containers_per_winner']) ? max(1, intval($params['containers_per_winner'])) : 50;
        $antimatter     = isset($params['antimatter_per_winner']) ? max(0, intval($params['antimatter_per_winner'])) : 100;
        $minSystem      = isset($params['min_system']) ? max(1, intval($params['min_system'])) : 1;
        $maxSystem      = isset($params['max_system']) ? min(400, intval($params['max_system'])) : 200;

        $serverBudget   = self::calculateServerBudget($universe);
        $bossBudget     = round($serverBudget * $budgetRatio);
        $units          = self::computeUnitsForBudget($bossBudget);
        $techs          = self::calculateTechnologiesAverage($universe);

        $lootRatio      = isset($params['loot_ratio']) ? floatval($params['loot_ratio']) : 0.40;
        $totalLootBudget = round($serverBudget * $lootRatio);

        // Distribución 4:2:1 (Metal ~57.14%, Cristal ~28.57%, Deuterio ~14.29%)
        $defaultLootMetal     = round($totalLootBudget * (4 / 7));
        $defaultLootCrystal   = round($totalLootBudget * (2 / 7));
        $defaultLootDeuterium = round($totalLootBudget * (1 / 7));

        $lootMetal      = (isset($params['loot_metal']) && $params['loot_metal'] > 0) ? floatval($params['loot_metal']) : $defaultLootMetal;
        $lootCrystal    = (isset($params['loot_crystal']) && $params['loot_crystal'] > 0) ? floatval($params['loot_crystal']) : $defaultLootCrystal;
        $lootDeuterium  = (isset($params['loot_deuterium']) && $params['loot_deuterium'] > 0) ? floatval($params['loot_deuterium']) : $defaultLootDeuterium;

        $npcId          = self::ensureNpcUser($techs, $universe);

        // Pick coordinates
        $galaxy   = 1;
        $system   = (!empty($params['system']) && $params['system'] > 0) ? (int)$params['system'] : mt_rand($minSystem, $maxSystem);
        $position = 8;

        if (!PlayerUtil::isPositionFree($universe, $galaxy, $system, $position)) {
            $found = false;
            for ($pos = 1; $pos <= 15; $pos++) {
                if (PlayerUtil::isPositionFree($universe, $galaxy, $system, $pos)) {
                    $position = $pos;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $system   = ($system < 200) ? $system + 1 : $system - 1;
                $position = 8;
            }
        }

        // Create the planet
        $planetId = PlayerUtil::createPlanet($galaxy, $system, $position, $universe, $npcId, 'Fortaleza Ancestral', false, 0);

        // Inject fleet, defense and loot
        global $resource;
        $db = Database::get();
        $fields = array(
            'metal'         => $lootMetal,
            'crystal'       => $lootCrystal,
            'deuterium'     => $lootDeuterium,
            'metal_max'     => max(2000000000, $lootMetal * 2),
            'crystal_max'   => max(1000000000, $lootCrystal * 2),
            'deuterium_max' => max(1000000000, $lootDeuterium * 2),
        );

        foreach ($units as $elementId => $qty) {
            $col = $resource[$elementId];
            $fields[$col] = $qty;
        }

        $setParts = array();
        $binds = array(':planetId' => $planetId);
        foreach ($fields as $col => $val) {
            $setParts[] = "`{$col}` = :{$col}";
            $binds[":{$col}"] = $val;
        }

        $sql = "UPDATE %%PLANETS%% SET " . implode(', ', $setParts) . " WHERE id = :planetId;";
        $db->update($sql, $binds);

        $startTime = TIMESTAMP;
        $endTime   = $startTime + ($durationHours * 3600);

        if (!empty($existingEventId)) {
            $updateSql = "UPDATE %%WAR_EVENTS%% SET 
                status = 'active',
                calculated_budget = :budget,
                galaxy = :galaxy,
                system = :system,
                planet = :planet,
                planet_id = :planet_id,
                start_time = :start,
                end_time = :end
                WHERE id = :id;";
            $db->update($updateSql, array(
                ':budget'    => $bossBudget,
                ':galaxy'    => $galaxy,
                ':system'    => $system,
                ':planet'    => $position,
                ':planet_id' => $planetId,
                ':start'     => $startTime,
                ':end'       => $endTime,
                ':id'        => $existingEventId
            ));
        } else {
            $insertSql = "INSERT INTO %%WAR_EVENTS%% SET 
                event_name = :name,
                status = 'active',
                budget_ratio = :ratio,
                calculated_budget = :budget,
                galaxy = :galaxy,
                system = :system,
                planet = :planet,
                planet_id = :planet_id,
                npc_user_id = :npc_id,
                containers_per_winner = :containers,
                antimatter_per_winner = :antimatter,
                duration_hours = :duration,
                pre_countdown_minutes = 0,
                loot_metal = :metal,
                loot_crystal = :crystal,
                loot_deuterium = :deut,
                start_time = :start,
                end_time = :end;";

            $db->insert($insertSql, array(
                ':name'       => 'Fortaleza Ancestral',
                ':ratio'      => $budgetRatio,
                ':budget'     => $bossBudget,
                ':galaxy'     => $galaxy,
                ':system'     => $system,
                ':planet'     => $position,
                ':planet_id'  => $planetId,
                ':npc_id'     => $npcId,
                ':containers' => $containers,
                ':antimatter' => $antimatter,
                ':duration'   => $durationHours,
                ':metal'      => $lootMetal,
                ':crystal'    => $lootCrystal,
                ':deut'       => $lootDeuterium,
                ':start'      => $startTime,
                ':end'        => $endTime
            ));
        }

        // Global Announcement Broadcast (Approved Lore Message)
        $subject = "⚠️ [ALERTA GALÁCTICA] Una Fortaleza Ancestral ha despertado en el sector";
        $message = "Comandantes del universo:<br><br>"
                 . "Nuestros radares de largo alcance y sondas de reconocimiento en espacio profundo acaban de registrar una perturbación masiva en el hiperespacio. Una colosal <b>Fortaleza Ancestral</b> ha materializado sus escudos y plataformas de artillería pesada en las coordenadas <b>[{$galaxy}:{$system}:{$position}]</b>.<br><br>"
                 . "Los análisis tácticos confirman que se trata de un bastión fuertemente armado, equipado con naves de combate de élite, baterías Gauss, cañones de plasma y superestructuras defensivas de última generación. Ninguna flota solitaria podrá vulnerar sus defensas sin sufrir bajas catastróficas.<br><br>"
                 . "📜 <b>REGLAS DEL ENFRENTAMIENTO:</b><br>"
                 . "1. <b>Coordinación Militar:</b> La fortaleza cuenta con defensas impenetrables para ataques individuales. Se recomienda organizar ofensivas conjuntas (SAC) o asaltos coordinados con sus aliados.<br>"
                 . "2. <b>Botín Inmediato:</b> Cada oleada que impacte la base podrá saquear millones de recursos almacenados y generar inmensos campos de escombros en su órbita.<br>"
                 . "3. <b>La Estocada Final:</b> El evento concluirá en el instante exacto en que la última nave o torreta defensiva del planeta sea destruida.<br><br>"
                 . "🏆 <b>RECOMPENSAS POR LA VICTORIA:</b><br>"
                 . "Los comandantes que asesten la Estocada Final (todos los participantes de la última batalla que reduzca la fortaleza a 0) recibirán directamente en sus hangares:<br>"
                 . "📦 <b>{$containers} Contenedores</b><br>"
                 . "✨ <b>{$antimatter} de Antimateria</b><br>"
                 . "<i>(Entregados a CADA uno de los participantes victoriosos).</i><br><br>"
                 . "⏳ <b>TIEMPO LÍMITE:</b><br>"
                 . "Nuestros sensores indican que la fortaleza sobrecargará sus motores de salto dentro de <b>{$durationHours} horas</b>. Si para ese momento la base aún sigue en pie, escapará hacia el vacío galáctico y el evento finalizará sin ganadores.<br><br>"
                 . "¡Preparen sus flotas, organicen sus alianzas y que comience la batalla!";

        self::broadcastMessage($subject, $message, $universe);

        return array(
            'planet_id' => $planetId,
            'coords'    => "{$galaxy}:{$system}:{$position}",
            'budget'    => $bossBudget,
            'duration'  => $durationHours,
            'end_time'  => $endTime,
            'units'     => $units
        );
    }

    public static function cancelScheduledEvent($eventId = null)
    {
        $db = Database::get();
        if (!empty($eventId)) {
            $db->update("UPDATE %%WAR_EVENTS%% SET status = 'expired' WHERE id = :id AND status = 'scheduled';", array(':id' => $eventId));
        } else {
            $db->update("UPDATE %%WAR_EVENTS%% SET status = 'expired' WHERE status = 'scheduled';");
        }
        self::cleanupNpc();
        return true;
    }

    public static function checkFinalBlow($planetId, $winningFleetOwners)
    {
        $db = Database::get();
        $event = $db->selectSingle("SELECT * FROM %%WAR_EVENTS%% WHERE planet_id = :id AND status = 'active';", array(':id' => $planetId));

        if (empty($event)) {
            return false;
        }

        // Check if all defending units are destroyed
        global $resource;
        $ratios = self::getUnitRatios();
        $planet = $db->selectSingle("SELECT * FROM %%PLANETS%% WHERE id = :id;", array(':id' => $planetId));

        $remainingUnits = 0;
        if (!empty($planet)) {
            foreach ($ratios as $elementId => $data) {
                $col = $resource[$elementId];
                if (isset($planet[$col]) && $planet[$col] > 0) {
                    $remainingUnits += (int)$planet[$col];
                }
            }
        }

        if ($remainingUnits > 0) {
            return false;
        }

        // Final Blow achieved!
        $uniqueWinners = array_unique(array_filter($winningFleetOwners, function($id) {
            return $id > 0 && $id != WarEventBossEngine::NPC_USER_ID;
        }));

        if (empty($uniqueWinners)) {
            return false;
        }

        $containers = (int) $event['containers_per_winner'];
        $antimatter = (int) $event['antimatter_per_winner'];
        $winnerNames = array();

        foreach ($uniqueWinners as $userId) {
            $user = $db->selectSingle("SELECT username FROM %%USERS%% WHERE id = :id;", array(':id' => $userId));
            if (!empty($user)) {
                $winnerNames[] = $user['username'];
                $updateUserSql = "UPDATE %%USERS%% SET 
                                  container = container + :c, 
                                  antimatter = antimatter + :am 
                                  WHERE id = :id;";
                $db->update($updateUserSql, array(
                    ':c'  => $containers,
                    ':am' => $antimatter,
                    ':id' => $userId
                ));

                // Send PM to each winner
                $pmSubject = "🏆 ¡VICTORIA: RECOMPENSA DE LA FORTALEZA ANCESTRAL RECIBIDA!";
                $pmBody = "¡Felicitaciones Comandante <b>{$user['username']}</b>!<br><br>"
                        . "Tu flota ha asestado el golpe definitivo que destruyó la Fortaleza Ancestral en [{$event['galaxy']}:{$event['system']}:{$event['planet']}].<br>"
                        . "Se han acreditado en tu cuenta:<br>"
                        . "📦 <b>{$containers} Contenedores</b><br>"
                        . "✨ <b>{$antimatter} de Antimateria</b>.<br><br>"
                        . "¡La galaxia celebra tu triunfo!";
                PlayerUtil::sendMessage($userId, 1, 'Sistema de Eventos', 4, $pmSubject, $pmBody, TIMESTAMP, NULL, 1, 1);
            }
        }

        // Mark event as completed
        $db->update("UPDATE %%WAR_EVENTS%% SET 
                     status = 'completed', 
                     winner_ids = :winners, 
                     rewards_distributed = 1 
                     WHERE id = :id;", array(
            ':winners' => json_encode($winnerNames),
            ':id'      => $event['id']
        ));

        // Global Announcement of Victory (Approved Lore Message)
        $namesStr = implode(', ', $winnerNames);
        $subject = "👑 [VICTORIA GALÁCTICA] ¡La Fortaleza Ancestral ha sido destruida!";
        $message = "¡Gloria a los comandantes del universo!<br><br>"
                 . "Tras encarnizadas batallas y el despliegue de colosales flotas de guerra, la <b>Fortaleza Ancestral</b> situada en <b>[{$event['galaxy']}:{$event['system']}:{$event['planet']}]</b> ha sido totalmente erradicada. Su núcleo de energía ha colapsado y sus defensas han caído definitivamente a 0.<br><br>"
                 . "⚔️ <b>Comandantes que asestaron la Estocada Final:</b><br>"
                 . "<b>{$namesStr}</b><br><br>"
                 . "🎁 Cada uno de los miembros de esta ofensiva final ha recibido con éxito en su imperio:<br>"
                 . "📦 <b>{$containers} Contenedores</b><br>"
                 . "✨ <b>{$antimatter} de Antimateria</b><br><br>"
                 . "Agradecemos a todos los almirantes y alianzas que participaron debilitando sus defensas a lo largo de la campaña. Los restos del bastión ahora descansan en el espacio exterior.<br><br>"
                 . "¡El sector vuelve a estar a salvo... por ahora!";

        self::broadcastMessage($subject, $message, 1);

        // Mark planet as destroyed so debris field can be harvested, but reassign to admin (id 1) so NPC is freed
        $db->update("UPDATE %%PLANETS%% SET destruyed = :time, name = 'Fortaleza Destruida', id_owner = 1 WHERE id = :id;", array(
            ':time' => TIMESTAMP + 86400,
            ':id'   => $planetId
        ));

        // Clean up NPC user and stats so it doesn't remain in database or rankings
        self::cleanupNpc();

        return true;
    }

    public static function cleanupNpc()
    {
        $db = Database::get();
        $db->delete("DELETE FROM %%PLANETS%% WHERE id_owner = :id;", array(':id' => self::NPC_USER_ID));
        $db->delete("DELETE FROM %%USERS%% WHERE id = :id;", array(':id' => self::NPC_USER_ID));
        $db->delete("DELETE FROM %%STATPOINTS%% WHERE id_owner = :id;", array(':id' => self::NPC_USER_ID));
    }

    public static function expireEvent($eventId = null)
    {
        $db = Database::get();

        if (empty($eventId)) {
            $event = $db->selectSingle("SELECT * FROM %%WAR_EVENTS%% WHERE status = 'active' ORDER BY id DESC LIMIT 1;");
        } else {
            $event = $db->selectSingle("SELECT * FROM %%WAR_EVENTS%% WHERE id = :id;", array(':id' => $eventId));
        }

        if (empty($event) || $event['status'] !== 'active') {
            return false;
        }

        // Mark as expired
        $db->update("UPDATE %%WAR_EVENTS%% SET status = 'expired' WHERE id = :id;", array(':id' => $event['id']));

        // Return any incoming fleets safely
        $sql = "UPDATE %%FLEETS%% SET fleet_mess = 1 WHERE fleet_end_id = :planetId AND fleet_mess = 0;";
        $db->update($sql, array(':planetId' => $event['planet_id']));

        // Global Announcement of Expiration (Approved Lore Message)
        $subject = "⌛ [EVENTO FINALIZADO] La Fortaleza Ancestral ha escapado al hiperespacio";
        $message = "Comandantes:<br><br>"
                 . "El tiempo límite para neutralizar la amenaza ha expirado. La <b>Fortaleza Ancestral</b> situada en <b>[{$event['galaxy']}:{$event['system']}:{$event['planet']}]</b> ha completado la sobrecarga de sus reactores y ha realizado un salto hiperespacial de emergencia hacia espacio inexplorado.<br><br>"
                 . "A pesar de los valientes ataques registrados, las defensas de la fortaleza resistieron y no fue posible asestar la estocada final a tiempo. Por tanto, el bastión se ha desvanecido sin dejar rastro y no hay vencedores en esta ocasión.<br><br>"
                 . "Todas las flotas que se encontraban en rumbo hacia las coordenadas del evento han recibido la orden de regresar a salvo a sus planetas de origen.<br><br>"
                 . "Recuperen sus naves, reconstruyan sus arsenales y manténganse alertas: la galaxia nunca duerme.";

        self::broadcastMessage($subject, $message, 1);

        // Clean up the planet, NPC user and stats
        $db->delete("DELETE FROM %%PLANETS%% WHERE id = :id;", array(':id' => $event['planet_id']));
        self::cleanupNpc();

        return true;
    }

    public static function broadcastMessage($subject, $message, $universe = 1)
    {
        $db = Database::get();
        $users = $db->select("SELECT id, username FROM %%USERS%% WHERE universe = :uni AND id != 1;", array(':uni' => $universe));

        foreach ($users as $user) {
            PlayerUtil::sendMessage(
                $user['id'],
                1,
                'NovaRush Eventos',
                50,
                $subject,
                $message,
                TIMESTAMP,
                NULL,
                1,
                $universe
            );
        }
    }
}

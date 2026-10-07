<?php

/**
 * NovaRush Bot AI v2 - Intel Store
 *
 * Persists and retrieves espionage and reconnaissance intel with temporal decay.
 * Respects Fog of War: the bot only knows what it has actually spied.
 */
class BotIntelStore
{
    private $botId;

    public function __construct($botId)
    {
        $this->botId = (int)$botId;
    }

    /**
     * Store new espionage intel
     */
    public function saveIntel($targetPlanetId, $targetUserId, $galaxy, $system, $planet, $planetType, array $resources, $fleets = null, $defenses = null, $buildings = null, $techs = null)
    {
        $db = Database::get();

        $sql = "INSERT INTO " . DB_PREFIX . "bot_intel 
                (bot_id, target_planet_id, target_user_id, galaxy, system, planet, planet_type,
                 metal, crystal, deuterium, fleet_data, defense_data, building_data, tech_data, scanned_at)
                VALUES 
                (:bot_id, :target_planet_id, :target_user_id, :galaxy, :system, :planet, :planet_type,
                 :metal, :crystal, :deuterium, :fleet_data, :defense_data, :building_data, :tech_data, :scanned_at)
                ON DUPLICATE KEY UPDATE
                 target_planet_id = VALUES(target_planet_id),
                 target_user_id   = VALUES(target_user_id),
                 metal            = VALUES(metal),
                 crystal          = VALUES(crystal),
                 deuterium        = VALUES(deuterium),
                 fleet_data       = IF(VALUES(fleet_data) IS NOT NULL, VALUES(fleet_data), fleet_data),
                 defense_data     = IF(VALUES(defense_data) IS NOT NULL, VALUES(defense_data), defense_data),
                 building_data    = IF(VALUES(building_data) IS NOT NULL, VALUES(building_data), building_data),
                 tech_data        = IF(VALUES(tech_data) IS NOT NULL, VALUES(tech_data), tech_data),
                 scanned_at       = VALUES(scanned_at);";

        $db->insert($sql, array(
            ':bot_id'           => $this->botId,
            ':target_planet_id' => (int)$targetPlanetId,
            ':target_user_id'   => (int)$targetUserId,
            ':galaxy'           => (int)$galaxy,
            ':system'           => (int)$system,
            ':planet'           => (int)$planet,
            ':planet_type'      => (int)$planetType,
            ':metal'            => isset($resources[901]) ? (float)$resources[901] : 0,
            ':crystal'          => isset($resources[902]) ? (float)$resources[902] : 0,
            ':deuterium'        => isset($resources[903]) ? (float)$resources[903] : 0,
            ':fleet_data'       => ($fleets !== null && is_array($fleets)) ? json_encode($fleets) : null,
            ':defense_data'     => ($defenses !== null && is_array($defenses)) ? json_encode($defenses) : null,
            ':building_data'    => ($buildings !== null && is_array($buildings)) ? json_encode($buildings) : null,
            ':tech_data'        => ($techs !== null && is_array($techs)) ? json_encode($techs) : null,
            ':scanned_at'       => TIMESTAMP,
        ));
    }

    /**
     * Retrieve stored intel for a coordinate, computing confidence decay
     *
     * @param int $galaxy
     * @param int $system
     * @param int $planet
     * @param int $planetType
     * @param float $halfLifeHours
     * @return array|null Intel record with calculated confidence [0.0, 1.0], or null if unspied
     */
    public function getIntel($galaxy, $system, $planet, $planetType = 1, $halfLifeHours = 4.0)
    {
        $db = Database::get();
        $sql = "SELECT * FROM " . DB_PREFIX . "bot_intel 
                WHERE bot_id = :bot_id 
                  AND galaxy = :galaxy 
                  AND system = :system 
                  AND planet = :planet 
                  AND planet_type = :planet_type 
                LIMIT 1;";

        $row = $db->selectSingle($sql, array(
            ':bot_id'      => $this->botId,
            ':galaxy'      => (int)$galaxy,
            ':system'      => (int)$system,
            ':planet'      => (int)$planet,
            ':planet_type' => (int)$planetType,
        ));

        if (empty($row)) {
            return null;
        }

        $ageHours = max(0.0, (TIMESTAMP - (int)$row['scanned_at']) / 3600.0);
        $lambda   = log(2) / max(0.5, (float)$halfLifeHours);
        $conf     = exp(-$lambda * $ageHours);

        return array(
            'intel_id'         => (int)$row['intel_id'],
            'target_planet_id' => (int)$row['target_planet_id'],
            'target_user_id'   => (int)$row['target_user_id'],
            'galaxy'           => (int)$row['galaxy'],
            'system'           => (int)$row['system'],
            'planet'           => (int)$row['planet'],
            'planet_type'      => (int)$row['planet_type'],
            'metal'            => (float)$row['metal'],
            'crystal'          => (float)$row['crystal'],
            'deuterium'        => (float)$row['deuterium'],
            'fleet_scouted'    => ($row['fleet_data'] !== null),
            'defense_scouted'  => ($row['defense_data'] !== null),
            'buildings_scouted'=> ($row['building_data'] !== null),
            'techs_scouted'    => ($row['tech_data'] !== null),
            'fleet'            => ($row['fleet_data'] !== null && is_array($f = json_decode($row['fleet_data'], true))) ? $f : array(),
            'defense'          => ($row['defense_data'] !== null && is_array($d = json_decode($row['defense_data'], true))) ? $d : array(),
            'buildings'        => ($row['building_data'] !== null && is_array($b = json_decode($row['building_data'], true))) ? $b : array(),
            'techs'            => ($row['tech_data'] !== null && is_array($t = json_decode($row['tech_data'], true))) ? $t : array(),
            'scanned_at'       => (int)$row['scanned_at'],
            'age_hours'        => round($ageHours, 2),
            'confidence'       => round($conf, 3),
        );
    }

    /**
     * Deduct destroyed defenses from stored intel after confirmed missile strikes
     */
    public function deductDefenses($galaxy, $system, $planet, $planetType, array $destroyed)
    {
        $db = Database::get();
        $row = $db->selectSingle("SELECT defense_data FROM " . DB_PREFIX . "bot_intel 
                WHERE bot_id = :bot_id 
                  AND galaxy = :galaxy 
                  AND system = :system 
                  AND planet = :planet 
                  AND planet_type = :planet_type 
                LIMIT 1;", array(
            ':bot_id'      => $this->botId,
            ':galaxy'      => (int)$galaxy,
            ':system'      => (int)$system,
            ':planet'      => (int)$planet,
            ':planet_type' => (int)$planetType,
        ));

        if (empty($row) || empty($row['defense_data'])) {
            return;
        }

        $def = json_decode($row['defense_data'], true);
        if (!is_array($def)) {
            return;
        }

        foreach ($destroyed as $elemId => $cnt) {
            if (isset($def[$elemId])) {
                $def[$elemId] = max(0, (int)$def[$elemId] - (int)$cnt);
            }
        }

        $db->update("UPDATE " . DB_PREFIX . "bot_intel 
            SET defense_data = :data, scanned_at = :now 
            WHERE bot_id = :bot_id 
              AND galaxy = :galaxy 
              AND system = :system 
              AND planet = :planet 
              AND planet_type = :planet_type;", array(
            ':data'        => json_encode($def),
            ':now'         => TIMESTAMP,
            ':bot_id'      => $this->botId,
            ':galaxy'      => (int)$galaxy,
            ':system'      => (int)$system,
            ':planet'      => (int)$planet,
            ':planet_type' => (int)$planetType,
        ));
    }
}

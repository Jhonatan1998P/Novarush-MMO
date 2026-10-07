<?php

/**
 * NovaRush Bot AI v2 - Public Data Reader
 *
 * Enforces Information Parity. The bot reads ONLY publicly observable information:
 * - Public highscore / statpoints
 * - Galaxy view activity markers, moon presence, and debris fields
 *
 * Under no circumstance does this class inspect private opponent resources or fleets.
 */
class BotPublicDataReader
{
    /**
     * Get public rankings for players around a given rank or point range
     *
     * @param int $universe
     * @param int $limit
     * @return array Array of public player rows
     */
    public static function getPublicRankings($universe = 1, $limit = 100)
    {
        $db = Database::get();
        $sql = "SELECT s.id_owner, s.total_points, s.total_rank, s.fleet_points, s.defs_points, u.username, u.ally_id
                FROM %%STATPOINTS%% s
                INNER JOIN %%USERS%% u ON u.id = s.id_owner
                WHERE s.stat_type = 1 AND s.universe = :uni
                ORDER BY s.total_rank ASC
                LIMIT :limit;";

        return $db->select($sql, array(
            ':uni'   => (int)$universe,
            ':limit' => (int)$limit,
        ));
    }

    /**
     * Read public galaxy information for a specific system
     *
     * @param int $galaxy
     * @param int $system
     * @param int $universe
     * @return array List of observable celestial bodies
     */
    public static function getGalaxySystem($galaxy, $system, $universe = 1)
    {
        $db = Database::get();

        // Notice: only public columns are queried (no resources or fleet counts of other players!)
        $sql = "SELECT p.id, p.id_owner, p.galaxy, p.system, p.planet, p.planet_type,
                       p.der_metal, p.der_crystal, p.id_luna, p.last_update, p.last_fleet_out,
                       m.last_fleet_out as m_last_fleet_out,
                       u.username, u.authlevel, u.ally_id, u.onlinetime, u.urlaubs_modus, u.banaday
                FROM %%PLANETS%% p
                LEFT JOIN %%PLANETS%% m ON m.id = p.id_luna
                LEFT JOIN %%USERS%% u ON u.id = p.id_owner
                WHERE p.universe = :uni 
                  AND p.galaxy = :galaxy 
                  AND p.system = :system 
                  AND p.planet_type = 1 
                  AND p.destruyed = 0
                ORDER BY p.planet ASC;";

        $rows = $db->select($sql, array(
            ':uni'    => (int)$universe,
            ':galaxy' => (int)$galaxy,
            ':system' => (int)$system,
        ));

        $result = array();
        foreach ($rows as $row) {
            // Activity marker strictly calculated from outgoing fleet activity
            $activityTime = max((int)($row['last_fleet_out'] ?? 0), (int)($row['m_last_fleet_out'] ?? 0));
            $activityMinutes = ($activityTime > 0) ? floor((TIMESTAMP - $activityTime) / 60) : 999999;

            // Compute standard 2Moons activity marker
            $activityMarker = 'none';
            if ($activityMinutes < 15) {
                $activityMarker = 'active'; // '*'
            } elseif ($activityMinutes < 60) {
                $activityMarker = (int)$activityMinutes; // 'XX min'
            }

            $hasMoon = !empty($row['id_luna']);
            $hasDebris = ($row['der_metal'] > 0 || $row['der_crystal'] > 0);

            $result[] = array(
                'planet_id'        => (int)$row['id'],
                'owner_id'         => (int)$row['id_owner'],
                'username'         => $row['username'],
                'authlevel'        => (int)($row['authlevel'] ?? 0),
                'galaxy'           => (int)$row['galaxy'],
                'system'           => (int)$row['system'],
                'planet'           => (int)$row['planet'],
                'has_moon'         => $hasMoon,
                'has_debris'       => $hasDebris,
                'debris_metal'     => (float)$row['der_metal'],
                'debris_crystal'   => (float)$row['der_crystal'],
                'activity_marker'  => $activityMarker,
                'activity_minutes' => $activityMinutes,
                'is_vacation'      => (!empty($row['urlaubs_modus']) || (!empty($row['banaday']) && $row['banaday'] > TIMESTAMP)),
            );
        }

        return $result;
    }

    /**
     * Batch read public galaxy information across multiple system ranges
     * Consolidates multi-colony radar into a single high-performance SQL query.
     *
     * @param array $systemRanges List of ['galaxy' => int, 'start' => int, 'end' => int]
     * @param int $universe
     * @return array List of observable celestial bodies
     */
    public static function getGalaxySystemsBatch(array $systemRanges, $universe = 1)
    {
        if (empty($systemRanges)) {
            return array();
        }

        $db = Database::get();

        $clauses = array();
        $params  = array(':uni' => (int)$universe);
        $i = 0;

        foreach ($systemRanges as $range) {
            $g = (int)$range['galaxy'];
            $s = (int)$range['start'];
            $e = (int)$range['end'];
            if ($g <= 0 || $s <= 0 || $e < $s) continue;

            $clauses[] = "(p.galaxy = :g{$i} AND p.system BETWEEN :s{$i} AND :e{$i})";
            $params[":g{$i}"] = $g;
            $params[":s{$i}"] = $s;
            $params[":e{$i}"] = $e;
            $i++;
        }

        if (empty($clauses)) {
            return array();
        }

        $sql = "SELECT p.id, p.id_owner, p.galaxy, p.system, p.planet, p.planet_type,
                       p.der_metal, p.der_crystal, p.id_luna, p.last_update, p.last_fleet_out,
                       m.last_fleet_out as m_last_fleet_out,
                       u.username, u.authlevel, u.ally_id, u.onlinetime, u.urlaubs_modus, u.banaday
                FROM %%PLANETS%% p
                LEFT JOIN %%PLANETS%% m ON m.id = p.id_luna
                LEFT JOIN %%USERS%% u ON u.id = p.id_owner
                WHERE p.universe = :uni 
                  AND p.planet_type = 1 
                  AND p.destruyed = 0
                  AND (" . implode(' OR ', $clauses) . ")
                ORDER BY p.galaxy ASC, p.system ASC, p.planet ASC;";

        $rows = $db->select($sql, $params);

        $result = array();
        foreach ($rows as $row) {
            // Activity marker strictly calculated from outgoing fleet activity
            $activityTime = max((int)($row['last_fleet_out'] ?? 0), (int)($row['m_last_fleet_out'] ?? 0));
            $activityMinutes = ($activityTime > 0) ? floor((TIMESTAMP - $activityTime) / 60) : 999999;

            $activityMarker = 'none';
            if ($activityMinutes < 15) {
                $activityMarker = 'active'; // '*'
            } elseif ($activityMinutes < 60) {
                $activityMarker = (int)$activityMinutes; // 'XX min'
            }

            $hasMoon = !empty($row['id_luna']);
            $hasDebris = ($row['der_metal'] > 0 || $row['der_crystal'] > 0);

            $result[] = array(
                'planet_id'        => (int)$row['id'],
                'owner_id'         => (int)$row['id_owner'],
                'username'         => $row['username'],
                'authlevel'        => (int)($row['authlevel'] ?? 0),
                'galaxy'           => (int)$row['galaxy'],
                'system'           => (int)$row['system'],
                'planet'           => (int)$row['planet'],
                'has_moon'         => $hasMoon,
                'has_debris'       => $hasDebris,
                'debris_metal'     => (float)$row['der_metal'],
                'debris_crystal'   => (float)$row['der_crystal'],
                'activity_marker'  => $activityMarker,
                'activity_minutes' => $activityMinutes,
                'is_vacation'      => (!empty($row['urlaubs_modus']) || (!empty($row['banaday']) && $row['banaday'] > TIMESTAMP)),
            );
        }

        return $result;
    }

    /**
     * Get public statpoint profile for a specific user
     */
    public static function getUserPublicStats($userId)
    {
        $db = Database::get();
        $sql = "SELECT * FROM %%STATPOINTS%% WHERE id_owner = :id AND stat_type = 1 LIMIT 1;";
        return $db->selectSingle($sql, array(':id' => (int)$userId));
    }
}

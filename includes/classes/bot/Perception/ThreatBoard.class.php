<?php

/**
 * NovaRush Bot AI v2 - Threat Board
 *
 * Scans and tracks incoming hostile fleets, applying strict Espionage Technology
 * Fog of War as documented in BOT_AUDIT.md:
 * - SpyTech < 4: Threat alert only. Ship total and composition completely unknown.
 * - SpyTech 4..7: Total ship count and ship types known, individual counts unknown.
 * - SpyTech >= 8 or Phalanx: Exact composition known (ships and counts).
 */
class BotThreatBoard
{
    private $botId;
    private $spyTechLevel;

    public function __construct($botId, $spyTechLevel = 0)
    {
        $this->botId = (int)$botId;
        $this->spyTechLevel = (int)$spyTechLevel;
    }

    /**
     * Get all active incoming threats to the bot's planets/moons
     *
     * @return array List of threat structures with Fog of War applied
     */
    public function getActiveThreats()
    {
        $db = Database::get();

        $sql = "SELECT fleet_id, fleet_owner, fleet_target_owner, fleet_mission,
                       fleet_amount, fleet_array, fleet_start_time, fleet_start_id,
                       fleet_end_id, fleet_end_galaxy, fleet_end_system, fleet_end_planet, fleet_end_type
                FROM %%FLEETS%%
                WHERE fleet_target_owner = :bot_id 
                  AND fleet_mission IN (1, 2, 9) 
                  AND fleet_mess = 0 
                  AND fleet_start_time > :now
                ORDER BY fleet_start_time ASC;";

        $rows = $db->select($sql, array(
            ':bot_id' => $this->botId,
            ':now'    => TIMESTAMP,
        ));

        $threats = array();
        foreach ($rows as $row) {
            $etaSeconds = max(0, (int)$row['fleet_start_time'] - TIMESTAMP);
            $parsedFleet = FleetFunctions::unserialize($row['fleet_array']);
            if (!is_array($parsedFleet)) {
                $parsedFleet = array();
            }

            // Apply Fog of War
            $visibility = 'full';
            $visibleShips = array();
            $knownTotal = (int)$row['fleet_amount'];

            if ($this->spyTechLevel < 4) {
                // Defender sees NO fleet data
                $visibility   = 'none';
                $knownTotal   = null;
                $visibleShips = array();
            } elseif ($this->spyTechLevel < 8) {
                // Defender sees total amount and ship types present, but NOT counts
                $visibility = 'types_only';
                foreach (array_keys($parsedFleet) as $shipId) {
                    $visibleShips[$shipId] = null; // type known, count hidden
                }
            } else {
                // Full visibility
                $visibility   = 'full';
                $visibleShips = $parsedFleet;
            }

            $threats[] = array(
                'fleet_id'       => (int)$row['fleet_id'],
                'attacker_id'    => (int)$row['fleet_owner'],
                'mission'        => (int)$row['fleet_mission'],
                'target_planet'  => (int)$row['fleet_end_id'],
                'target_coords'  => $row['fleet_end_galaxy'] . ':' . $row['fleet_end_system'] . ':' . $row['fleet_end_planet'],
                'target_type'    => (int)$row['fleet_end_type'],
                'eta_seconds'    => $etaSeconds,
                'arrival_time'   => (int)$row['fleet_start_time'],
                'visibility'     => $visibility,
                'visible_total'  => $knownTotal,
                'visible_ships'  => $visibleShips,
                'raw_fleet'      => $parsedFleet, // Kept internal for engine simulator when allowed
            );
        }

        return $threats;
    }
}

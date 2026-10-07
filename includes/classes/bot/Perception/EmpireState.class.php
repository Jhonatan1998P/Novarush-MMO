<?php

require_once 'includes/classes/bot/Evaluation/EconomyValuator.class.php';

/**
 * NovaRush Bot AI v2 - Empire State
 *
 * Tracks the complete state of the bot's own empire: colonies, moons,
 * fleets on ground, defenses, active queues, and hourly production in MSE.
 */
class BotEmpireState
{
    private $botId;
    private $user;
    private $planets = array();
    private $moons   = array();
    private $hourlyProductionMSE = 0.0;
    private $totalResourcesMSE   = 0.0;

    public function __construct($botId, array $userRow)
    {
        $this->botId = (int)$botId;
        $this->user  = $userRow;
        $this->loadEmpire();
    }

    private function loadEmpire()
    {
        $db = Database::get();

        // 1. Fetch all planets and moons belonging to the bot
        $sql = "SELECT * FROM %%PLANETS%% 
                WHERE id_owner = :bot_id AND destruyed = 0 
                ORDER BY planet_type ASC, id ASC;";

        $rows = $db->select($sql, array(':bot_id' => $this->botId));

        $totalM = 0.0;
        $totalC = 0.0;
        $totalD = 0.0;
        $totalHourlyMSE = 0.0;

        foreach ($rows as $planet) {
            // Update resource accumulation to current timestamp
            $planetRess = new ResourceUpdate();
            list($this->user, $planetUpdated) = $planetRess->CalcResource($this->user, $planet, true, TIMESTAMP);

            $pId = (int)$planetUpdated['id'];
            $pType = (int)$planetUpdated['planet_type'];

            // Calculate hourly production in MSE for this celestial body
            $prodM = isset($planetUpdated['metal_perhour']) ? (float)$planetUpdated['metal_perhour'] : 0.0;
            $prodC = isset($planetUpdated['crystal_perhour']) ? (float)$planetUpdated['crystal_perhour'] : 0.0;
            $prodD = isset($planetUpdated['deuterium_perhour']) ? (float)$planetUpdated['deuterium_perhour'] : 0.0;
            $planetHourlyMSE = BotEconomyValuator::toMSE($prodM, $prodC, $prodD);

            $totalHourlyMSE += $planetHourlyMSE;
            $totalM += (float)$planetUpdated['metal'];
            $totalC += (float)$planetUpdated['crystal'];
            $totalD += (float)$planetUpdated['deuterium'];

            $planetEntry = array(
                'data'              => $planetUpdated,
                'id'                => $pId,
                'name'              => $planetUpdated['name'],
                'galaxy'            => (int)$planetUpdated['galaxy'],
                'system'            => (int)$planetUpdated['system'],
                'planet'            => (int)$planetUpdated['planet'],
                'planet_type'       => $pType,
                'metal'             => (float)$planetUpdated['metal'],
                'crystal'           => (float)$planetUpdated['crystal'],
                'deuterium'         => (float)$planetUpdated['deuterium'],
                'energy'            => (float)$planetUpdated['energy'],
                'hourly_mse'        => $planetHourlyMSE,
                'building_queue'    => (!empty($planetUpdated['b_building_id']) && is_array($bq = @unserialize($planetUpdated['b_building_id']))) ? $bq : array(),
                'hangar_queue'      => (!empty($planetUpdated['b_hangar_id']) && is_array($hq = @unserialize($planetUpdated['b_hangar_id']))) ? $hq : array(),
            );

            if ($pType === 3) {
                $this->moons[$pId] = $planetEntry;
            } else {
                $this->planets[$pId] = $planetEntry;
            }
        }

        $this->hourlyProductionMSE = max(100.0, $totalHourlyMSE);
        $this->totalResourcesMSE   = BotEconomyValuator::toMSE($totalM, $totalC, $totalD);
    }

    public function getPlanets()
    {
        return $this->planets;
    }

    public function getMoons()
    {
        return $this->moons;
    }

    public function getAllBodies()
    {
        return $this->planets + $this->moons;
    }

    public function getHourlyProductionMSE()
    {
        return $this->hourlyProductionMSE;
    }

    public function getTotalResourcesMSE()
    {
        return $this->totalResourcesMSE;
    }

    public function getPlanet($planetId)
    {
        if (isset($this->planets[$planetId])) {
            return $this->planets[$planetId];
        }
        if (isset($this->moons[$planetId])) {
            return $this->moons[$planetId];
        }
        return null;
    }

    public function getUser()
    {
        return $this->user;
    }
}

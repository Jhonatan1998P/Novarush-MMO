<?php

require_once 'includes/classes/class.FleetFunctions.php';
require_once 'includes/classes/class.BuildFunctions.php';
require_once 'includes/classes/class.PlanetRessUpdate.php';

/**
 * NovaRush Bot AI v2 - Game Services Wrapper
 *
 * Low-level operational bridge to authentic 2Moons game services.
 */
class BotGameServices
{
    /**
     * Send fleet through the canonical FleetFunctions::sendFleet engine method
     */
    public static function dispatchFleet(
        array $fleetArray,
        $mission,
        $startOwnerId,
        $startPlanetId,
        $startGalaxy,
        $startSystem,
        $startPlanet,
        $startType,
        $targetOwnerId,
        $targetPlanetId,
        $targetGalaxy,
        $targetSystem,
        $targetPlanet,
        $targetType,
        array $resources,
        $startTime,
        $stayTime,
        $endTime,
        $consumption = 0
    ) {
        // BLOQUEO ADMINISTRATIVO: Solo LaParca (bot_id 1007) esta autorizado a mover flota
        if ((int)$startOwnerId !== 1007) {
            return false;
        }

        return FleetFunctions::sendFleet(
            $fleetArray,
            (int)$mission,
            (int)$startOwnerId,
            (int)$startPlanetId,
            (int)$startGalaxy,
            (int)$startSystem,
            (int)$startPlanet,
            (int)$startType,
            (int)$targetOwnerId,
            (int)$targetPlanetId,
            (int)$targetGalaxy,
            (int)$targetSystem,
            (int)$targetPlanet,
            (int)$targetType,
            $resources,
            (int)$startTime,
            (int)$stayTime,
            (int)$endTime,
            0,
            0,
            (int)$consumption,
            0
        );
    }

    /**
     * Execute standard fleet recall
     */
    public static function recallFleet(array $userRow, $fleetId)
    {
        return FleetFunctions::SendFleetBack($userRow, (int)$fleetId);
    }

    /**
     * Dispatch an interplanetary missile strike (Mission 10: MIP)
     */
    public static function dispatchMissiles(
        $count,
        $startOwnerId,
        $startPlanetId,
        $startGalaxy,
        $startSystem,
        $startPlanet,
        $startType,
        $targetOwnerId,
        $targetPlanetId,
        $targetGalaxy,
        $targetSystem,
        $targetPlanet,
        $targetType,
        $primaryTarget = 0
    ) {
        // BLOQUEO ADMINISTRATIVO: Solo LaParca (bot_id 1007) esta autorizado a disparar misiles
        if ((int)$startOwnerId !== 1007) {
            return false;
        }

        $fleetArray = array(503 => (int)$count);
        $duration = FleetFunctions::GetMIPDuration($startSystem, $targetSystem);
        $startTime = TIMESTAMP + $duration;
        $stayTime  = $startTime;
        $endTime   = $startTime;
        $resources = array(901 => 0, 902 => 0, 903 => 0);

        return FleetFunctions::sendFleet(
            $fleetArray,
            10, // Mission 10: Missile Attack (MIP)
            (int)$startOwnerId,
            (int)$startPlanetId,
            (int)$startGalaxy,
            (int)$startSystem,
            (int)$startPlanet,
            (int)$startType,
            (int)$targetOwnerId,
            (int)$targetPlanetId,
            (int)$targetGalaxy,
            (int)$targetSystem,
            (int)$targetPlanet,
            (int)$targetType,
            $resources,
            (int)$startTime,
            (int)$stayTime,
            (int)$endTime,
            0,
            (int)$primaryTarget,
            0,
            0
        );
    }

    /**
     * Add building to real planet building queue
     */
    public static function enqueueBuilding(array &$user, array &$planet, $elementId)
    {
        global $resource, $pricelist, $reslist;

        if (!BuildFunctions::isTechnologieAccessible($user, $planet, $elementId, array())) {
            return false;
        }

        $currentQueue = !empty($planet['b_building_id']) ? @unserialize($planet['b_building_id']) : array();
        if (!is_array($currentQueue)) {
            $currentQueue = array();
        }
        $actualCount  = count($currentQueue);

        $config = Config::get();
        $maxSlots = $config->max_elements_build + (isset($user['factor']['BuildSlots']) ? (int)$user['factor']['BuildSlots'] : 0);
        if ($config->max_elements_build != 0 && $actualCount >= $maxSlots) {
            return false;
        }

        $currentMaxFields = CalculateMaxPlanetFields($planet);
        if ($elementId != 33 && $planet['field_current'] >= ($currentMaxFields - $actualCount)) {
            return false;
        }

        $buildLevel = $planet[$resource[$elementId]] + 1;
        foreach ($currentQueue as $q) {
            if ($q[0] == $elementId) {
                $buildLevel++;
            }
        }

        if (isset($pricelist[$elementId]['max']) && $pricelist[$elementId]['max'] < $buildLevel) {
            return false;
        }

        $costResources = BuildFunctions::getElementPrice($user, $planet, $elementId, false);
        if (!BuildFunctions::isElementBuyable($user, $planet, $elementId, $costResources)) {
            return false;
        }

        // Deduct resources atomically
        $planet['metal']     -= isset($costResources[901]) ? $costResources[901] : 0;
        $planet['crystal']   -= isset($costResources[902]) ? $costResources[902] : 0;
        $planet['deuterium'] -= isset($costResources[903]) ? $costResources[903] : 0;

        $elementTime  = BuildFunctions::getBuildingTime($user, $planet, $elementId, $costResources);
        $buildEndTime = ($actualCount == 0) ? (TIMESTAMP + $elementTime) : ($currentQueue[$actualCount - 1][3] + $elementTime);

        $currentQueue[] = array($elementId, $buildLevel, $elementTime, $buildEndTime, 'build');

        $db = Database::get();
        $sql = "UPDATE %%PLANETS%% SET 
                metal = :metal,
                crystal = :crystal,
                deuterium = :deuterium,
                b_building = :b_building,
                b_building_id = :b_building_id
                WHERE id = :planetId;";

        $res = $db->update($sql, array(
            ':metal'         => $planet['metal'],
            ':crystal'       => $planet['crystal'],
            ':deuterium'     => $planet['deuterium'],
            ':b_building'    => $currentQueue[0][3],
            ':b_building_id' => serialize($currentQueue),
            ':planetId'      => $planet['id'],
        ));

        if ($res === false || $db->rowCount() <= 0) {
            return false;
        }

        $planet['b_building_id'] = serialize($currentQueue);
        $planet['b_building']    = $currentQueue[0][3];

        return true;
    }

    /**
     * Add ships or defenses to real shipyard queue
     */
    public static function enqueueShipyard(array &$user, array &$planet, $elementId, $count)
    {
        global $resource, $pricelist, $reslist;

        $count = (int)$count;
        if ($count <= 0) return false;

        if (!BuildFunctions::isTechnologieAccessible($user, $planet, $elementId, array())) {
            return false;
        }

        $maxConstructible = BuildFunctions::getMaxConstructibleElements($user, $planet, $elementId);
        $count = min($count, $maxConstructible);
        if ($count <= 0) return false;

        // Specific limits per category
        if (in_array($elementId, $reslist['domes'])) {
            $domes = BuildFunctions::getMaxConstructibleDomes($user, $planet);
            if (isset($domes[$elementId])) {
                $count = min($count, $domes[$elementId]);
            }
        } elseif (in_array($elementId, $reslist['missile'])) {
            $missiles = BuildFunctions::getMaxConstructibleRockets($user, $planet);
            if (isset($missiles[$elementId])) {
                $count = min($count, $missiles[$elementId]);
            }
        } elseif (in_array($elementId, $reslist['orbital_bases'])) {
            $orbits = BuildFunctions::getMaxConstructibleOrbits($user, $planet);
            if (isset($orbits[$elementId])) {
                $count = min($count, $orbits[$elementId]);
            }
        } elseif (in_array($elementId, $reslist['one'])) {
            $buildArray = !empty($planet['b_hangar_id']) ? @unserialize($planet['b_hangar_id']) : array();
            if (!is_array($buildArray)) {
                $buildArray = array();
            }
            foreach ($buildArray as $item) {
                if ($item[0] == $elementId) return false;
            }
            if (!empty($planet[$resource[$elementId]])) return false;
            $count = 1;
        }

        if ($count <= 0) return false;

        $costResources = BuildFunctions::getElementPrice($user, $planet, $elementId, false, $count);
        if (!BuildFunctions::isElementBuyable($user, $planet, $elementId, $costResources)) {
            return false;
        }

        // Deduct resources
        $planet['metal']     -= isset($costResources[901]) ? $costResources[901] : 0;
        $planet['crystal']   -= isset($costResources[902]) ? $costResources[902] : 0;
        $planet['deuterium'] -= isset($costResources[903]) ? $costResources[903] : 0;

        $buildArray = !empty($planet['b_hangar_id']) ? @unserialize($planet['b_hangar_id']) : array();
        if (!is_array($buildArray)) {
            $buildArray = array();
        }
        $buildArray[] = array($elementId, $count);

        $db = Database::get();
        $sql = "UPDATE %%PLANETS%% SET 
                metal = :metal,
                crystal = :crystal,
                deuterium = :deuterium,
                b_hangar_id = :b_hangar_id
                WHERE id = :planetId;";

        $res = $db->update($sql, array(
            ':metal'       => $planet['metal'],
            ':crystal'     => $planet['crystal'],
            ':deuterium'   => $planet['deuterium'],
            ':b_hangar_id' => serialize($buildArray),
            ':planetId'    => $planet['id'],
        ));

        if ($res === false || $db->rowCount() <= 0) {
            return false;
        }

        $planet['b_hangar_id'] = serialize($buildArray);

        return true;
    }

    /**
     * Add technology to real research queue
     */
    public static function enqueueResearch(array &$user, array &$planet, $elementId)
    {
        global $resource, $pricelist, $reslist;

        if (!BuildFunctions::isTechnologieAccessible($user, $planet, $elementId, array())) {
            return false;
        }

        if ($user['b_tech_planet'] != 0) {
            return false; // Lab busy
        }

        $currentQueue = !empty($user['b_tech_queue']) ? @unserialize($user['b_tech_queue']) : array();
        if (!is_array($currentQueue)) {
            $currentQueue = array();
        }
        $actualCount  = count($currentQueue);

        $config = Config::get();
        $maxSlots = $config->max_elements_tech + (isset($user['factor']['ResearchSlots']) ? (int)$user['factor']['ResearchSlots'] : 0);
        if ($config->max_elements_tech != 0 && $actualCount >= $maxSlots) {
            return false;
        }

        $buildLevel = $user[$resource[$elementId]] + 1;
        if (isset($pricelist[$elementId]['max']) && $pricelist[$elementId]['max'] < $buildLevel) {
            return false;
        }

        $costResources = BuildFunctions::getElementPrice($user, $planet, $elementId, false);
        if (!BuildFunctions::isElementBuyable($user, $planet, $elementId, $costResources)) {
            return false;
        }

        $planet['metal']     -= isset($costResources[901]) ? $costResources[901] : 0;
        $planet['crystal']   -= isset($costResources[902]) ? $costResources[902] : 0;
        $planet['deuterium'] -= isset($costResources[903]) ? $costResources[903] : 0;

        $elementTime  = BuildFunctions::getBuildingTime($user, $planet, $elementId, $costResources);
        $buildEndTime = TIMESTAMP + $elementTime;

        $currentQueue[] = array($elementId, $buildLevel, $elementTime, $buildEndTime, $planet['id']);

        $db = Database::get();
        $res1 = $db->update("UPDATE %%PLANETS%% SET metal = :m, crystal = :c, deuterium = :d WHERE id = :pid;", array(
            ':m'   => $planet['metal'],
            ':c'   => $planet['crystal'],
            ':d'   => $planet['deuterium'],
            ':pid' => $planet['id'],
        ));

        $res2 = $db->update("UPDATE %%USERS%% SET b_tech = :b_tech, b_tech_planet = :pid, b_tech_id = :tid, b_tech_queue = :queue WHERE id = :uid;", array(
            ':b_tech' => $buildEndTime,
            ':pid'    => $planet['id'],
            ':tid'    => $elementId,
            ':queue'  => serialize($currentQueue),
            ':uid'    => $user['id'],
        ));

        if ($res1 === false || $res2 === false || $db->rowCount() <= 0) {
            return false;
        }

        $user['b_tech_queue']  = serialize($currentQueue);
        $user['b_tech_planet'] = $planet['id'];
        $user['b_tech_id']     = $elementId;
        $user['b_tech']        = $buildEndTime;

        return true;
    }
}

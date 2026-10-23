<?php

require_once 'includes/classes/class.FleetFunctions.php';
require_once 'includes/classes/class.BuildFunctions.php';
require_once 'includes/classes/class.PlanetRessUpdate.php';
require_once 'includes/classes/bot/Kernel/BotClock.class.php';

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
     * Execute Quantum Jump Gate between two moons belonging to the same user
     * Instantaneous travel, 0 deuterium consumption, resets last_jump_time on both moons.
     * Replicates native 2Moons ShowInformationPage::sendFleet logic.
     *
     * @param int $userId
     * @param int $sourceMoonId
     * @param int $targetMoonId
     * @param array $ships [shipId => count]
     * @return array|false Transferred ship details or false on failure
     */
    public static function executeJumpGate($userId, $sourceMoonId, $targetMoonId, array $ships)
    {
        global $resource, $reslist;

        $userId       = (int)$userId;
        $sourceMoonId = (int)$sourceMoonId;
        $targetMoonId = (int)$targetMoonId;

        if ($userId <= 0 || $sourceMoonId <= 0 || $targetMoonId <= 0 || $sourceMoonId === $targetMoonId) {
            return false;
        }

        $db = Database::get();
        $isOuterTx = $db->inTransaction();
        if (!$isOuterTx) {
            $db->beginTransaction();
        }

        try {
            $sourceMoon = $db->selectSingle(
                "SELECT * FROM %%PLANETS%% WHERE id = :id AND id_owner = :uid FOR UPDATE;",
                array(':id' => $sourceMoonId, ':uid' => $userId)
            );
            $targetMoon = $db->selectSingle(
                "SELECT * FROM %%PLANETS%% WHERE id = :id AND id_owner = :uid FOR UPDATE;",
                array(':id' => $targetMoonId, ':uid' => $userId)
            );

            if (empty($sourceMoon) || empty($targetMoon)) {
                if (!$isOuterTx && $db->inTransaction()) $db->rollBack();
                return false;
            }

            // Must be active moons with jump gate
            if ((int)$sourceMoon['planet_type'] !== 3 || (int)$targetMoon['planet_type'] !== 3 ||
                (int)$sourceMoon['destruyed'] !== 0 || (int)$targetMoon['destruyed'] !== 0 ||
                (int)($sourceMoon['sprungtor'] ?? 0) <= 0 || (int)($targetMoon['sprungtor'] ?? 0) <= 0) {
                if (!$isOuterTx && $db->inTransaction()) $db->rollBack();
                return false;
            }

            // Gate cooldown check
            $config = Config::get();
            $gateWaitTime = isset($config->gate_wait_time) ? (int)$config->gate_wait_time : 3600;
            $now = class_exists('BotClock') ? BotClock::get() : TIMESTAMP;

            if ($now < ((int)$sourceMoon['last_jump_time'] + $gateWaitTime) ||
                $now < ((int)$targetMoon['last_jump_time'] + $gateWaitTime)) {
                if (!$isOuterTx && $db->inTransaction()) $db->rollBack();
                return false;
            }

            $shipArray = array();
            $fleetAllowed = isset($reslist['fleet']) ? $reslist['fleet'] : array();
            $paramsOri = array(':planetID' => $sourceMoonId, ':jumptime' => $now);
            $paramsDes = array(':targetID' => $targetMoonId, ':jumptime' => $now);
            $setOri = array();
            $setDes = array();

            foreach ($fleetAllowed as $shipId) {
                $shipId = (int)$shipId;
                if (!isset($ships[$shipId]) || $shipId == 212) {
                    continue; // Solar satellites (212) cannot jump
                }

                $col = $resource[$shipId] ?? '';
                if (empty($col) || !isset($sourceMoon[$col])) {
                    continue;
                }

                $available = (int)$sourceMoon[$col];
                $requested = (int)$ships[$shipId];
                $toTransfer = max(0, min($requested, $available));

                if ($toTransfer <= 0) {
                    continue;
                }

                $shipArray[$shipId] = $toTransfer;
                $setOri[] = "{$col} = GREATEST(0, {$col} - :sub_{$shipId})";
                $paramsOri[":sub_{$shipId}"] = $toTransfer;
                $setDes[] = "{$col} = {$col} + :add_{$shipId}";
                $paramsDes[":add_{$shipId}"] = $toTransfer;
            }

            if (empty($shipArray) || array_sum($shipArray) <= 0) {
                if (!$isOuterTx && $db->inTransaction()) $db->rollBack();
                return false;
            }

            $setOri[] = "last_jump_time = :jumptime";
            $setDes[] = "last_jump_time = :jumptime";

            $sqlOri = "UPDATE %%PLANETS%% SET " . implode(', ', $setOri) . " WHERE id = :planetID;";
            $sqlDes = "UPDATE %%PLANETS%% SET " . implode(', ', $setDes) . " WHERE id = :targetID;";

            $db->update($sqlOri, $paramsOri);
            $db->update($sqlDes, $paramsDes);

            if (!$isOuterTx) {
                $db->commit();
            }

            $transferredMSE = 0.0;
            if (file_exists('includes/classes/bot/Evaluation/EconomyValuator.class.php')) {
                require_once 'includes/classes/bot/Evaluation/EconomyValuator.class.php';
            }
            if (class_exists('BotEconomyValuator')) {
                foreach ($shipArray as $sId => $cnt) {
                    $transferredMSE += BotEconomyValuator::getElementPriceMSE($sId, $cnt);
                }
            }

            return array(
                'source_moon_id' => $sourceMoonId,
                'target_moon_id' => $targetMoonId,
                'ships'          => $shipArray,
                'total_ships'    => array_sum($shipArray),
                'mse'            => $transferredMSE,
                'jump_time'      => $now,
            );
        } catch (Throwable $e) {
            if (!$isOuterTx && $db->inTransaction()) {
                $db->rollBack();
            }
            return false;
        }
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

        $fleetArray = array(503 => (int)$count);
        $duration = FleetFunctions::GetMIPDuration($startSystem, $targetSystem);
        $startTime = BotClock::get() + $duration;
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
    public static function enqueueBuilding(array &$user, array &$planet, $elementId, $forDestroy = false, $maxAllowedSlotsOverride = null)
    {
        global $resource, $pricelist, $reslist;

        $currentQueue = !empty($planet['b_building_id']) ? @unserialize($planet['b_building_id']) : array();
        if (!is_array($currentQueue)) {
            $currentQueue = array();
        }
        $actualCount  = count($currentQueue);

        $config = Config::get();
        $maxSlots = ($maxAllowedSlotsOverride !== null)
            ? (int)$maxAllowedSlotsOverride
            : ($config->max_elements_build + (isset($user['factor']['BuildSlots']) ? (int)$user['factor']['BuildSlots'] : 0));

        if ($maxSlots > 0 && $actualCount >= $maxSlots) {
            return false;
        }

        // Proyectar planeta sumando ordenes previas en cola para validar accesibilidad y nivel
        $projPlanet = $planet;
        foreach ($currentQueue as $q) {
            $qElem = (int)$q[0];
            $qCol  = $resource[$qElem] ?? null;
            if ($qCol) {
                if (($q[4] ?? 'build') === 'destroy') {
                    $projPlanet[$qCol] = max(0, (int)($projPlanet[$qCol] ?? 0) - 1);
                } else {
                    $projPlanet[$qCol] = (int)($projPlanet[$qCol] ?? 0) + 1;
                }
            }
        }

        if (!BuildFunctions::isTechnologieAccessible($user, $projPlanet, $elementId, array())) {
            return false;
        }

        $currentMaxFields = CalculateMaxPlanetFields($planet);
        if (!$forDestroy && $elementId != 33 && $elementId != 41 && $planet['field_current'] >= ($currentMaxFields - $actualCount)) {
            return false;
        }

        if ($forDestroy) {
            $buildLevel = (int)($projPlanet[$resource[$elementId]] ?? 0);
            if ($buildLevel <= 0) {
                return false;
            }
        } else {
            $buildLevel = (int)($projPlanet[$resource[$elementId]] ?? 0) + 1;
            if (isset($pricelist[$elementId]['max']) && $pricelist[$elementId]['max'] < $buildLevel) {
                return false;
            }
        }

        $levelForPrice = ($forDestroy) ? $buildLevel : ($buildLevel - 1);
        $costResources = BuildFunctions::getElementPrice($user, $planet, $elementId, $forDestroy, $levelForPrice);

        // Regla de Cobro Canonica: Slot 0 cobra al instante; Slots 1..N-1 no descuentan por adelantado
        if ($actualCount == 0) {
            if (!BuildFunctions::isElementBuyable($user, $planet, $elementId, $costResources, $forDestroy, $levelForPrice)) {
                return false;
            }

            $planet['metal']     -= isset($costResources[901]) ? $costResources[901] : 0;
            $planet['crystal']   -= isset($costResources[902]) ? $costResources[902] : 0;
            $planet['deuterium'] -= isset($costResources[903]) ? $costResources[903] : 0;
        }

        $elementTime  = BuildFunctions::getBuildingTime($user, $projPlanet, $elementId, $costResources, $forDestroy, $levelForPrice);
        $now = class_exists('BotClock') ? BotClock::get() : TIMESTAMP;
        $buildEndTime = ($actualCount == 0) ? ($now + $elementTime) : ($currentQueue[$actualCount - 1][3] + $elementTime);

        $currentQueue[] = array($elementId, $buildLevel, $elementTime, $buildEndTime, $forDestroy ? 'destroy' : 'build');

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

        if ($actualCount == 0 && class_exists('BotPremiumStrategist')) {
            BotPremiumStrategist::updateCachedPlanetResources($planet['id'], $planet['metal'], $planet['crystal'], $planet['deuterium']);
        }

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

        $buildArray = !empty($planet['b_hangar_id']) ? @unserialize($planet['b_hangar_id']) : array();
        if (!is_array($buildArray)) {
            $buildArray = array();
        }
        if (count($buildArray) >= 14) {
            return false;
        }

        $costResources = BuildFunctions::getElementPrice($user, $planet, $elementId, false, $count);
        if (!BuildFunctions::isElementBuyable($user, $planet, $elementId, $costResources)) {
            return false;
        }

        // Deduct resources
        $planet['metal']     -= isset($costResources[901]) ? $costResources[901] : 0;
        $planet['crystal']   -= isset($costResources[902]) ? $costResources[902] : 0;
        $planet['deuterium'] -= isset($costResources[903]) ? $costResources[903] : 0;

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

        if (class_exists('BotPremiumStrategist')) {
            BotPremiumStrategist::updateCachedPlanetResources($planet['id'], $planet['metal'], $planet['crystal'], $planet['deuterium']);
        }

        return true;
    }

    /**
     * Add technology to real research queue
     */
    public static function enqueueResearch(array &$user, array &$planet, $elementId, $maxAllowedSlotsOverride = null)
    {
        global $resource, $pricelist, $reslist;

        if (!isset($user['factor'])) {
            $user['factor'] = getFactors($user, 'basic', class_exists('BotClock') ? BotClock::get() : TIMESTAMP);
        }

        $currentQueue = !empty($user['b_tech_queue']) ? @unserialize($user['b_tech_queue']) : array();
        if (!is_array($currentQueue)) {
            $currentQueue = array();
        }

        $now = class_exists('BotClock') ? BotClock::get() : TIMESTAMP;
        if (!empty($user['b_tech_planet']) && empty($currentQueue)) {
            $tId = (int)$user['b_tech_id'];
            $col = $resource[$tId] ?? null;
            $curLvl = $col ? (int)($user[$col] ?? 0) : 0;
            $currentQueue[] = array(
                $tId,
                $curLvl + 1,
                max(1, (int)$user['b_tech'] - $now),
                (int)$user['b_tech'],
                (int)$user['b_tech_planet']
            );
        }

        $actualCount = count($currentQueue);
        $config = Config::get();
        $maxSlots = ($maxAllowedSlotsOverride !== null)
            ? (int)$maxAllowedSlotsOverride
            : ($config->max_elements_tech + (isset($user['factor']['ResearchSlots']) ? (int)$user['factor']['ResearchSlots'] : 0));

        if ($maxSlots > 0 && $actualCount >= $maxSlots) {
            return false;
        }

        // Proyectar tecnologias sumando ordenes previas en cola
        $projUser = $user;
        foreach ($currentQueue as $q) {
            $qElem = (int)$q[0];
            $qCol  = $resource[$qElem] ?? null;
            if ($qCol) {
                $projUser[$qCol] = (int)($projUser[$qCol] ?? 0) + 1;
            }
        }

        if (!BuildFunctions::isTechnologieAccessible($projUser, $planet, $elementId, array())) {
            return false;
        }

        $buildLevel = (int)($projUser[$resource[$elementId]] ?? 0) + 1;
        if (isset($pricelist[$elementId]['max']) && $pricelist[$elementId]['max'] < $buildLevel) {
            return false;
        }

        $levelForPrice = $buildLevel - 1;
        $costResources = BuildFunctions::getElementPrice($projUser, $planet, $elementId, false, $levelForPrice);

        $db = Database::get();

        // Regla de Cobro Canonica: Slot 0 cobra al instante; Slots 1..N-1 no descuentan por adelantado
        if ($actualCount == 0) {
            if (!BuildFunctions::isElementBuyable($projUser, $planet, $elementId, $costResources, false, $levelForPrice)) {
                return false;
            }

            $planet['metal']     -= isset($costResources[901]) ? $costResources[901] : 0;
            $planet['crystal']   -= isset($costResources[902]) ? $costResources[902] : 0;
            $planet['deuterium'] -= isset($costResources[903]) ? $costResources[903] : 0;

            $elementTime  = BuildFunctions::getBuildingTime($projUser, $planet, $elementId, $costResources, false, $levelForPrice);
            $buildEndTime = $now + $elementTime;
            $currentQueue[] = array($elementId, $buildLevel, $elementTime, $buildEndTime, $planet['id']);

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

            if (class_exists('BotPremiumStrategist')) {
                BotPremiumStrategist::updateCachedPlanetResources($planet['id'], $planet['metal'], $planet['crystal'], $planet['deuterium']);
            }
        } else {
            $elementTime  = BuildFunctions::getBuildingTime($projUser, $planet, $elementId, $costResources, false, $levelForPrice);
            $buildEndTime = $currentQueue[$actualCount - 1][3] + $elementTime;
            $currentQueue[] = array($elementId, $buildLevel, $elementTime, $buildEndTime, $planet['id']);

            $res = $db->update("UPDATE %%USERS%% SET b_tech_queue = :queue WHERE id = :uid;", array(
                ':queue' => serialize($currentQueue),
                ':uid'   => $user['id'],
            ));

            if ($res === false || $db->rowCount() <= 0) {
                return false;
            }

            $user['b_tech_queue'] = serialize($currentQueue);
        }

        return true;
    }

    /**
     * Scrap units and refund resources according to canonical rules
     */
    public static function scrapUnits(array &$user, array &$planet, $shipId, $count)
    {
        global $resource, $pricelist;

        $shipId = (int)$shipId;
        $count  = (int)$count;
        if ($shipId <= 0 || $count <= 0) {
            return false;
        }

        $config = Config::get();
        $allowedShipIDs = array_map('intval', array_filter(array_map('trim', explode(',', $config->trade_allowed_ships))));
        if (!in_array($shipId, $allowedShipIDs, true)) {
            return false;
        }

        $shipKey = isset($resource[$shipId]) ? $resource[$shipId] : '';
        if (empty($shipKey) || !isset($planet[$shipKey]) || (int)$planet[$shipKey] < $count) {
            return false;
        }

        $tradeCharge = 1.0 - ((float)$config->trade_charge / 100.0);
        $costMeta = isset($pricelist[$shipId]['cost']) ? $pricelist[$shipId]['cost'] : array();

        $refundM  = (float)$count * (isset($costMeta[901]) ? (float)$costMeta[901] : 0.0) * $tradeCharge;
        $refundC  = (float)$count * (isset($costMeta[902]) ? (float)$costMeta[902] : 0.0) * $tradeCharge;
        $refundD  = (float)$count * (isset($costMeta[903]) ? (float)$costMeta[903] : 0.0) * $tradeCharge;
        $refundDM = (float)$count * (isset($costMeta[921]) ? (float)$costMeta[921] : 0.0) * $tradeCharge;

        $planet['metal']     += $refundM;
        $planet['crystal']   += $refundC;
        $planet['deuterium'] += $refundD;
        $planet[$shipKey]    -= $count;

        $db = Database::get();
        $sql = "UPDATE %%PLANETS%% SET 
                metal = :metal,
                crystal = :crystal,
                deuterium = :deuterium,
                {$shipKey} = {$shipKey} - :count
                WHERE id = :planetId;";

        $res = $db->update($sql, array(
            ':metal'    => $planet['metal'],
            ':crystal'  => $planet['crystal'],
            ':deuterium'=> $planet['deuterium'],
            ':count'    => $count,
            ':planetId' => $planet['id'],
        ));

        if ($res === false || $db->rowCount() <= 0) {
            return false;
        }

        if ($refundDM > 0 && isset($user['darkmatter'])) {
            $user['darkmatter'] += $refundDM;
            $db->update("UPDATE %%USERS%% SET darkmatter = :dm WHERE id = :userId;", array(
                ':dm'     => $user['darkmatter'],
                ':userId' => $user['id'],
            ));
        }

        return array(
            901         => $refundM,
            902         => $refundC,
            903         => $refundD,
            921         => $refundDM,
            'metal'     => $refundM,
            'crystal'   => $refundC,
            'deuterium' => $refundD,
            'count'     => $count,
            'ship_id'   => $shipId,
        );
    }
}

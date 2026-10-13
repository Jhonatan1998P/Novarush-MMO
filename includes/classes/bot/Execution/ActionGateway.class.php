<?php

require_once 'includes/classes/bot/Execution/GameServices.class.php';
require_once 'includes/classes/bot/Kernel/LockManager.class.php';
require_once 'includes/classes/bot/Kernel/BotClock.class.php';

/**
 * NovaRush Bot AI v2 - Action Gateway
 *
 * Single non-negotiable entry point for all game mutations.
 * Enforces:
 * 1. 100% Rule Parity (slots, queues, fuel, flight times, tech requirements).
 * 2. Concurrency Safety (SELECT ... FOR UPDATE and PlanetRessUpdate before deductions).
 * 3. Never injects free unearned resources or ships.
 */
class BotActionGateway
{
    private $ctx;

    public function __construct(BotContext $ctx)
    {
        $this->ctx = $ctx;
    }

    /**
     * Enqueue a building on a planet through the real engine with Phase 2 atomic transaction
     */
    public function constructBuilding($planetId, $elementId, $reason = '')
    {
        $db = Database::get();
        $isOuterTx = $db->inTransaction();
        if (!$isOuterTx) {
            $db->beginTransaction();
        }
        try {
            $planet = BotLockManager::lockPlanetRow($planetId);
            if (empty($planet) || (int)$planet['id_owner'] !== (int)$this->ctx->botId) {
                if (!$isOuterTx) $db->rollBack();
                return false;
            }

            // Canonical resource update
            $updater = new ResourceUpdate();
            list($u, $p) = $updater->CalcResource($this->ctx->user, $planet, true, BotClock::get());

            if (!BuildFunctions::isTechnologieAccessible($u, $p, $elementId, array())) {
                if (!$isOuterTx) $db->rollBack();
                return false;
            }

            $costResources = BuildFunctions::getElementPrice($u, $p, $elementId, false);
            if (!BuildFunctions::isElementBuyable($u, $p, $elementId, $costResources)) {
                if (!$isOuterTx) $db->rollBack();
                return false;
            }

            $ok = BotGameServices::enqueueBuilding($u, $p, $elementId);
            if (!$ok) {
                if (!$isOuterTx) $db->rollBack();
                return false;
            }

            if (!$isOuterTx) $db->commit();
            $this->ctx->user = $u;

            if (isset($this->ctx->budgetManager)) {
                $this->ctx->budgetManager->deductFromBudget($planetId, 'mines', $costResources);
            }

            if (isset($this->ctx->telemetry)) {
                global $resource;
                $pName  = $planet['name'] ?? "Planeta #{$planetId}";
                $coords = isset($planet['galaxy']) ? "[{$planet['galaxy']}:{$planet['system']}:{$planet['planet']}]" : '';
                $lvlCol = $resource[$elementId] ?? '';
                $curLvl = (!empty($lvlCol) && isset($planet[$lvlCol])) ? (int)$planet[$lvlCol] : 0;
                $this->ctx->telemetry->logBuilding($pName, $coords, $elementId, $curLvl + 1, $costResources, $reason);
            }

            return true;
        } catch (Throwable $e) {
            if (!$isOuterTx && $db->inTransaction()) {
                $db->rollBack();
            }
            return false;
        }
    }

    /**
     * Enqueue ships or defense on a planet through the real engine with Phase 2 atomic transaction
     */
    public function constructUnits($planetId, $elementId, $count, $reason = '')
    {
        $db = Database::get();
        $isOuterTx = $db->inTransaction();
        if (!$isOuterTx) {
            $db->beginTransaction();
        }
        try {
            $planet = BotLockManager::lockPlanetRow($planetId);
            if (empty($planet) || (int)$planet['id_owner'] !== (int)$this->ctx->botId) {
                if (!$isOuterTx) $db->rollBack();
                return false;
            }

            $updater = new ResourceUpdate();
            list($u, $p) = $updater->CalcResource($this->ctx->user, $planet, true, BotClock::get());

            if (!BuildFunctions::isTechnologieAccessible($u, $p, $elementId, array())) {
                if (!$isOuterTx) $db->rollBack();
                return false;
            }

            $maxConstructible = BuildFunctions::getMaxConstructibleElements($u, $p, $elementId);
            $realCount = min((int)$count, $maxConstructible);
            if ($realCount <= 0) {
                if (!$isOuterTx) $db->rollBack();
                return false;
            }

            $costResources = BuildFunctions::getElementPrice($u, $p, $elementId, false, $realCount);
            if (!BuildFunctions::isElementBuyable($u, $p, $elementId, $costResources)) {
                if (!$isOuterTx) $db->rollBack();
                return false;
            }

            $ok = BotGameServices::enqueueShipyard($u, $p, $elementId, $realCount);
            if (!$ok) {
                if (!$isOuterTx) $db->rollBack();
                return false;
            }

            if (!$isOuterTx) $db->commit();
            $this->ctx->user = $u;

            if (isset($this->ctx->budgetManager)) {
                $cat = ($elementId >= 400) ? 'defense' : 'fleet';
                $this->ctx->budgetManager->deductFromBudget($planetId, $cat, $costResources);
            }

            if (isset($this->ctx->telemetry)) {
                $pName  = $planet['name'] ?? "Planeta #{$planetId}";
                $coords = isset($planet['galaxy']) ? "[{$planet['galaxy']}:{$planet['system']}:{$planet['planet']}]" : '';
                if ($elementId >= 400) {
                    $this->ctx->telemetry->logDefense($pName, $coords, $elementId, $realCount, $costResources, $reason);
                } else {
                    $this->ctx->telemetry->logFleet($pName, $coords, $elementId, $realCount, $costResources, $reason);
                }
            }

            return (int)$realCount;
        } catch (Throwable $e) {
            if (!$isOuterTx && $db->inTransaction()) {
                $db->rollBack();
            }
            return false;
        }
    }

    /**
     * Atomically scrap ships on a planet through the real engine with Phase 1 atomic transaction
     */
    public function scrapUnits($planetId, $shipId, $count)
    {
        $db = Database::get();
        $isOuterTx = $db->inTransaction();
        if (!$isOuterTx) {
            $db->beginTransaction();
        }
        try {
            $planet = BotLockManager::lockPlanetRow($planetId);
            if (empty($planet) || (int)$planet['id_owner'] !== (int)$this->ctx->botId) {
                if (!$isOuterTx) $db->rollBack();
                return false;
            }

            $updater = new ResourceUpdate();
            list($u, $p) = $updater->CalcResource($this->ctx->user, $planet, true, BotClock::get());

            $refund = BotGameServices::scrapUnits($u, $p, $shipId, $count);
            if (!$refund) {
                if (!$isOuterTx) $db->rollBack();
                return false;
            }

            if (!$isOuterTx) $db->commit();
            $this->ctx->user = $u;

            if (isset($this->ctx->budgetManager)) {
                $this->ctx->budgetManager->creditScrapRefund($planetId, $refund);
            }

            if (isset($this->ctx->telemetry)) {
                $pName  = $planet['name'] ?? "Planeta #{$planetId}";
                $coords = isset($planet['galaxy']) ? "[{$planet['galaxy']}:{$planet['system']}:{$planet['planet']}]" : '';
                $this->ctx->telemetry->logScrap($pName, $coords, $shipId, $count, $refund);
            }

            return $refund;
        } catch (Throwable $e) {
            if (!$isOuterTx && $db->inTransaction()) {
                $db->rollBack();
            }
            return false;
        }
    }

    /**
     * Enqueue a technology research through the real engine with Phase 2 atomic transaction
     */
    public function researchTech($planetId, $techId, $reason = '')
    {
        $db = Database::get();
        $isOuterTx = $db->inTransaction();
        if (!$isOuterTx) {
            $db->beginTransaction();
        }
        try {
            $planet = BotLockManager::lockPlanetRow($planetId);
            $user   = BotLockManager::lockUserRow($this->ctx->botId);
            if (empty($planet) || (int)$planet['id_owner'] !== (int)$this->ctx->botId || empty($user)) {
                if (!$isOuterTx) $db->rollBack();
                return false;
            }

            $updater = new ResourceUpdate();
            list($u, $p) = $updater->CalcResource($user, $planet, true, BotClock::get());

            if (!BuildFunctions::isTechnologieAccessible($u, $p, $techId, array())) {
                if (!$isOuterTx) $db->rollBack();
                return false;
            }

            $costResources = BuildFunctions::getElementPrice($u, $p, $techId, false);
            if (!BuildFunctions::isElementBuyable($u, $p, $techId, $costResources)) {
                if (!$isOuterTx) $db->rollBack();
                return false;
            }

            $ok = BotGameServices::enqueueResearch($u, $p, $techId);
            if (!$ok) {
                if (!$isOuterTx) $db->rollBack();
                return false;
            }

            if (!$isOuterTx) $db->commit();
            $this->ctx->user = $u;

            if (isset($this->ctx->budgetManager)) {
                $this->ctx->budgetManager->deductFromBudget($planetId, 'research', $costResources);
            }

            if (isset($this->ctx->telemetry)) {
                global $resource;
                $pName  = $planet['name'] ?? "Planeta #{$planetId}";
                $coords = isset($planet['galaxy']) ? "[{$planet['galaxy']}:{$planet['system']}:{$planet['planet']}]" : '';
                $techCol = $resource[$techId] ?? '';
                $curLvl = (!empty($techCol) && isset($u[$techCol])) ? (int)$u[$techCol] : 0;
                $this->ctx->telemetry->logResearch($techId, $curLvl + 1, $pName, $coords, $costResources, $reason);
            }

            return true;
        } catch (Throwable $e) {
            if (!$isOuterTx && $db->inTransaction()) {
                $db->rollBack();
            }
            return false;
        }
    }

    /**
     * Get maximum expedition slots: S_exp = max(1, floor(sqrt(Tech 124))) if Tech 124 > 0, else 0.
     */
    public static function getExpeditionSlots(array $user)
    {
        global $resource;
        $techId = isset($resource[124]) ? $resource[124] : 'astrophysics';
        $tech124 = isset($user[$techId]) ? (int)$user[$techId] : (isset($user[124]) ? (int)$user[124] : 0);
        return ($tech124 > 0) ? max(1, (int)floor(sqrt($tech124))) : 0;
    }

    /**
     * Get shielded military slots: S_militar = max(1, S_total - S_exp).
     */
    public static function getMilitarySlots(array $user)
    {
        $maxSlots = FleetFunctions::GetMaxFleetSlots($user);
        $expSlots = self::getExpeditionSlots($user);
        return max(1, $maxSlots - $expSlots);
    }

    /**
     * Get active expedition fleets (missions 15 and 18)
     */
    public static function getActiveExpFleets($botId)
    {
        return (int)FleetFunctions::GetCurrentFleets($botId, 15, true)
             + (int)FleetFunctions::GetCurrentFleets($botId, 18, true);
    }

    /**
     * Get active military / non-expedition fleets
     */
    public static function getActiveNonExpFleets($botId)
    {
        $totalFleets = (int)FleetFunctions::GetCurrentFleets($botId);
        $activeExp   = self::getActiveExpFleets($botId);
        return max(0, $totalFleets - $activeExp);
    }

    /**
     * Get available expedition slots for new expeditions
     */
    public static function getAvailableExpeditionSlots($botId, array $user)
    {
        $maxSlots     = FleetFunctions::GetMaxFleetSlots($user);
        $actualFleets = FleetFunctions::GetCurrentFleets($botId);
        if ($actualFleets >= $maxSlots) {
            return 0;
        }

        $sExp      = self::getExpeditionSlots($user);
        $activeExp = self::getActiveExpFleets($botId);
        return max(0, min($maxSlots - $actualFleets, $sExp - $activeExp));
    }

    /**
     * Get available military slots for new military / logistics / staging missions
     */
    public static function getAvailableMilitarySlots($botId, array $user)
    {
        $maxSlots     = FleetFunctions::GetMaxFleetSlots($user);
        $actualFleets = FleetFunctions::GetCurrentFleets($botId);
        if ($actualFleets >= $maxSlots) {
            return 0;
        }

        $sMilitary    = self::getMilitarySlots($user);
        $activeNonExp = self::getActiveNonExpFleets($botId);
        return max(0, min($maxSlots - $actualFleets, $sMilitary - $activeNonExp));
    }

    /**
     * Dispatch a fleet mission
     */
    public function launchFleet(
        array $fleetArray,
        $mission,
        $startPlanetId,
        $targetPlanetId,
        $targetOwnerId,
        $targetGalaxy,
        $targetSystem,
        $targetPlanet,
        $targetType,
        array $cargoResources = array(),
        $speedFactor = 10,
        &$outStartTime = null,
        $stayDuration = 0
    ) {
        global $resource;

        // 1. Enforce Fleet Slots & Dedicated Expedition Slots rule parity
        $maxSlots     = FleetFunctions::GetMaxFleetSlots($this->ctx->user);
        $actualFleets = FleetFunctions::GetCurrentFleets($this->ctx->botId);
        $sExp         = self::getExpeditionSlots($this->ctx->user);
        $activeExp    = self::getActiveExpFleets($this->ctx->botId);
        $activeNonExp = max(0, $actualFleets - $activeExp);
        $sMilitary    = self::getMilitarySlots($this->ctx->user);

        // Emergency Slot Preemption for Survival Fleetsave (Mission 4):
        if ($mission == 4 && ($maxSlots <= $actualFleets || $activeNonExp >= $sMilitary)) {
            $db = Database::get();
            $preempt = $db->selectSingle(
                "SELECT fleet_id, fleet_start_id, fleet_array, fleet_resource_metal, fleet_resource_crystal, fleet_resource_deuter 
                 FROM %%FLEETS%% 
                 WHERE fleet_owner = :bid AND fleet_mission = 3 
                 ORDER BY fleet_id DESC 
                 LIMIT 1;",
                array(':bid' => (int)$this->ctx->botId)
            );
            if (!empty($preempt)) {
                $shipUpdates = array();
                $shipEntries = explode(';', trim($preempt['fleet_array'], ';'));
                foreach ($shipEntries as $se) {
                    if (empty($se)) continue;
                    $parts = explode(',', $se);
                    if (count($parts) === 2) {
                        $sId = (int)$parts[0];
                        $sQty = (int)$parts[1];
                        if (isset($resource[$sId])) {
                            $shipUpdates[] = $resource[$sId] . " = " . $resource[$sId] . " + " . $sQty;
                        }
                    }
                }
                $shipSql = !empty($shipUpdates) ? ", " . implode(', ', $shipUpdates) : "";
                $db->update("UPDATE %%PLANETS%% SET 
                    metal = metal + :m, 
                    crystal = crystal + :c, 
                    deuterium = deuterium + :d 
                    {$shipSql} 
                    WHERE id = :pid;", array(
                    ':m'   => (float)$preempt['fleet_resource_metal'],
                    ':c'   => (float)$preempt['fleet_resource_crystal'],
                    ':d'   => (float)$preempt['fleet_resource_deuter'],
                    ':pid' => (int)$preempt['fleet_start_id'],
                ));
                $db->delete("DELETE FROM %%FLEETS%% WHERE fleet_id = :fid;", array(':fid' => (int)$preempt['fleet_id']));

                $actualFleets = FleetFunctions::GetCurrentFleets($this->ctx->botId);
                $activeExp    = self::getActiveExpFleets($this->ctx->botId);
                $activeNonExp = max(0, $actualFleets - $activeExp);
            }
        }

        if ($maxSlots <= $actualFleets) {
            // Mission 4 has emergency survival exception if preemption wasn't possible
            if ($mission != 4 || $actualFleets > ($maxSlots + 1)) {
                return false;
            }
        }

        if ($mission == 15 || $mission == 18) {
            // Expedition mission: strictly capped by sExp and overall maxSlots
            if ($sExp <= 0 || $activeExp >= $sExp) {
                return false;
            }
        } else {
            // Military / Logistics / Deployment / Spy missions:
            // Dedicated expedition slots sExp are locked exclusively for expeditions.
            // Non-expedition fleets can only use S_militar = max(1, S_total - S_exp).
            if ($activeNonExp >= $sMilitary && $mission != 4) {
                return false;
            }
        }

        $startPlanet = BotLockManager::lockPlanetRow($startPlanetId);
        if (empty($startPlanet) || $startPlanet['id_owner'] != $this->ctx->botId) {
            return false;
        }

        // 2. Verify ships are actually on planet
        foreach ($fleetArray as $shipId => $qty) {
            $col = $resource[$shipId];
            if (!isset($startPlanet[$col]) || $startPlanet[$col] < $qty) {
                return false;
            }
        }

        // 3. Compute distance and duration
        $startCoords  = array($startPlanet['galaxy'], $startPlanet['system'], $startPlanet['planet']);
        $targetCoords = array($targetGalaxy, $targetSystem, $targetPlanet);
        $distance     = FleetFunctions::GetTargetDistance($startCoords, $targetCoords);

        $fleetSpeed   = FleetFunctions::GetFleetMaxSpeed($fleetArray, $this->ctx->user);
        $speedFactor  = max(1, min(10, (int)$speedFactor));
        $gameSpeed    = FleetFunctions::GetGameSpeedFactor();

        $flightDuration = FleetFunctions::GetMissionDuration($speedFactor, $fleetSpeed, $distance, $gameSpeed, $this->ctx->user);
        $consumption    = FleetFunctions::GetFleetConsumption($fleetArray, $flightDuration, $distance, $this->ctx->user, $gameSpeed);

        // Check deuterium for consumption
        if ($startPlanet['deuterium'] < $consumption) {
            return false;
        }

        $startTime = BotClock::get() + $flightDuration;
        if ($mission == 15 || $mission == 18) {
            $effectiveStay = ($stayDuration > 0) ? (int)$stayDuration : 3600;
        } else {
            $effectiveStay = (int)$stayDuration;
        }
        $stayTime  = $startTime + $effectiveStay;
        $endTime   = $stayTime + $flightDuration;
        $outStartTime = $startTime;

        // 4. Validate and clamp cargo capacity (Storage rule parity)
        $fleetStorage = max(0.0, FleetFunctions::GetFleetRoom($fleetArray, $this->ctx->user) - $consumption);

        $mCargo = isset($cargoResources[901]) ? min((float)$cargoResources[901], (float)$startPlanet['metal']) : 0.0;
        $cCargo = isset($cargoResources[902]) ? min((float)$cargoResources[902], (float)$startPlanet['crystal']) : 0.0;
        $dCargo = isset($cargoResources[903]) ? min((float)$cargoResources[903], max(0.0, (float)$startPlanet['deuterium'] - $consumption)) : 0.0;

        $totalCargo = $mCargo + $cCargo + $dCargo;
        if ($totalCargo > $fleetStorage && $fleetStorage > 0) {
            $scale  = $fleetStorage / $totalCargo;
            $mCargo = floor($mCargo * $scale);
            $cCargo = floor($cCargo * $scale);
            $dCargo = floor($dCargo * $scale);
        } elseif ($fleetStorage <= 0) {
            $mCargo = 0.0;
            $cCargo = 0.0;
            $dCargo = 0.0;
        }

        $resToSend = array(
            901 => $mCargo,
            902 => $cCargo,
            903 => $dCargo,
        );

        $fleetId = BotGameServices::dispatchFleet(
            $fleetArray,
            $mission,
            $this->ctx->botId,
            $startPlanet['id'],
            $startPlanet['galaxy'],
            $startPlanet['system'],
            $startPlanet['planet'],
            $startPlanet['planet_type'],
            $targetOwnerId,
            $targetPlanetId,
            $targetGalaxy,
            $targetSystem,
            $targetPlanet,
            $targetType,
            $resToSend,
            $startTime,
            $stayTime,
            $endTime,
            $consumption
        );

        if (!empty($fleetId) && $fleetId > 0) {
            // Deduct dispatched cargo resources from origin planet (FleetFunctions::sendFleet only deducts ships and fuel)
            if ($mCargo > 0 || $cCargo > 0 || $dCargo > 0) {
                $db = Database::get();
                $db->update("UPDATE %%PLANETS%% SET 
                    metal = GREATEST(0, metal - :m), 
                    crystal = GREATEST(0, crystal - :c), 
                    deuterium = GREATEST(0, deuterium - :d) 
                    WHERE id = :pid;", array(
                    ':m'   => $mCargo,
                    ':c'   => $cCargo,
                    ':d'   => $dCargo,
                    ':pid' => $startPlanet['id'],
                ));
            }

            // Synchronize Virtual Budget for fuel consumption and dispatched cargo
            if (isset($this->ctx->budgetManager)) {
                $this->ctx->budgetManager->deductDispatchedMission(
                    $startPlanet['id'],
                    $mission,
                    array(901 => $mCargo, 902 => $cCargo, 903 => $dCargo),
                    $consumption
                );
            }

            if (isset($this->ctx->telemetry)) {
                $sName   = $startPlanet['name'] ?? "Planeta #{$startPlanetId}";
                $sCoords = "[{$startPlanet['galaxy']}:{$startPlanet['system']}:{$startPlanet['planet']}]";
                $tName   = ($targetOwnerId == $this->ctx->botId) ? "Colonia Propia" : "Objetivo";
                $tCoords = "[{$targetGalaxy}:{$targetSystem}:{$targetPlanet}]";
                $this->ctx->telemetry->logFleetMission(
                    $sName,
                    $sCoords,
                    $tName,
                    $tCoords,
                    $mission,
                    $fleetArray,
                    array(901 => $mCargo, 902 => $cCargo, 903 => $dCargo),
                    $consumption,
                    "Despacho operativo de flota naval"
                );
            }

            return (int)$fleetId;
        }

        return false;
    }

    /**
     * Recall a flying fleet
     */
    public function recallFleet($fleetId)
    {
        return BotGameServices::recallFleet($this->ctx->user, $fleetId);
    }

    /**
     * Launch an interplanetary missile strike (Mission 10: MIP) against a target
     */
    public function launchMissileStrike(
        $startPlanetId,
        $targetPlanetId,
        $targetOwnerId,
        $targetGalaxy,
        $targetSystem,
        $targetPlanet,
        $targetType,
        $count,
        $primaryTarget = 0
    ) {
        global $resource;

        $startPlanet = BotLockManager::lockPlanetRow($startPlanetId);
        if (empty($startPlanet) || $startPlanet['id_owner'] != $this->ctx->botId) {
            return false;
        }

        $count = (int)$count;
        if ($count <= 0) return false;

        // Check silo level >= 4 (engine rule for MIP launch)
        if (empty($startPlanet[$resource[44]]) || $startPlanet[$resource[44]] < 4) {
            return false;
        }

        // Check impulse drive tech >= 1
        $impulse = isset($this->ctx->user[$resource[117]]) ? (int)$this->ctx->user[$resource[117]] : 0;
        if ($impulse < 1) {
            return false;
        }

        // Check range: Range = (impulse * 2) - 1 systems in same galaxy
        $range = FleetFunctions::GetMissileRange($impulse);
        if ($targetGalaxy != $startPlanet['galaxy'] || abs($targetSystem - $startPlanet['system']) > $range) {
            return false;
        }

        // Check available interplanetary missiles (503)
        $availMIP = isset($startPlanet[$resource[503]]) ? (int)$startPlanet[$resource[503]] : 0;
        $count = min($count, $availMIP);
        if ($count <= 0) return false;

        return BotGameServices::dispatchMissiles(
            $count,
            $this->ctx->botId,
            $startPlanet['id'],
            $startPlanet['galaxy'],
            $startPlanet['system'],
            $startPlanet['planet'],
            $startPlanet['planet_type'],
            $targetOwnerId,
            $targetPlanetId,
            $targetGalaxy,
            $targetSystem,
            $targetPlanet,
            $targetType,
            $primaryTarget
        );
    }
}

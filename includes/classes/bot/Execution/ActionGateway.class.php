<?php

require_once 'includes/classes/bot/Execution/GameServices.class.php';
require_once 'includes/classes/bot/Kernel/LockManager.class.php';

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
    public function constructBuilding($planetId, $elementId)
    {
        $db = Database::get();
        $db->beginTransaction();
        try {
            $planet = BotLockManager::lockPlanetRow($planetId);
            if (empty($planet) || (int)$planet['id_owner'] !== (int)$this->ctx->botId) {
                $db->rollBack();
                return false;
            }

            // Canonical resource update
            $updater = new ResourceUpdate();
            list($u, $p) = $updater->CalcResource($this->ctx->user, $planet, true, TIMESTAMP);

            if (!BuildFunctions::isTechnologieAccessible($u, $p, $elementId, array())) {
                $db->rollBack();
                return false;
            }

            $costResources = BuildFunctions::getElementPrice($u, $p, $elementId, false);
            if (!BuildFunctions::isElementBuyable($u, $p, $elementId, $costResources)) {
                $db->rollBack();
                return false;
            }

            $ok = BotGameServices::enqueueBuilding($u, $p, $elementId);
            if (!$ok) {
                $db->rollBack();
                return false;
            }

            $db->commit();
            $this->ctx->user = $u;

            if (isset($this->ctx->budgetManager)) {
                $this->ctx->budgetManager->deductFromBudget($planetId, 'mines', $costResources);
            }

            return true;
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return false;
        }
    }

    /**
     * Enqueue ships or defense on a planet through the real engine with Phase 2 atomic transaction
     */
    public function constructUnits($planetId, $elementId, $count)
    {
        $db = Database::get();
        $db->beginTransaction();
        try {
            $planet = BotLockManager::lockPlanetRow($planetId);
            if (empty($planet) || (int)$planet['id_owner'] !== (int)$this->ctx->botId) {
                $db->rollBack();
                return false;
            }

            $updater = new ResourceUpdate();
            list($u, $p) = $updater->CalcResource($this->ctx->user, $planet, true, TIMESTAMP);

            if (!BuildFunctions::isTechnologieAccessible($u, $p, $elementId, array())) {
                $db->rollBack();
                return false;
            }

            $maxConstructible = BuildFunctions::getMaxConstructibleElements($u, $p, $elementId);
            $realCount = min((int)$count, $maxConstructible);
            if ($realCount <= 0) {
                $db->rollBack();
                return false;
            }

            $costResources = BuildFunctions::getElementPrice($u, $p, $elementId, false, $realCount);
            if (!BuildFunctions::isElementBuyable($u, $p, $elementId, $costResources)) {
                $db->rollBack();
                return false;
            }

            $ok = BotGameServices::enqueueShipyard($u, $p, $elementId, $realCount);
            if (!$ok) {
                $db->rollBack();
                return false;
            }

            $db->commit();
            $this->ctx->user = $u;

            if (isset($this->ctx->budgetManager)) {
                $cat = ($elementId >= 400) ? 'defense' : 'fleet';
                $this->ctx->budgetManager->deductFromBudget($planetId, $cat, $costResources);
            }

            return true;
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return false;
        }
    }

    /**
     * Enqueue a technology research through the real engine with Phase 2 atomic transaction
     */
    public function researchTech($planetId, $techId)
    {
        $db = Database::get();
        $db->beginTransaction();
        try {
            $planet = BotLockManager::lockPlanetRow($planetId);
            $user   = BotLockManager::lockUserRow($this->ctx->botId);
            if (empty($planet) || (int)$planet['id_owner'] !== (int)$this->ctx->botId || empty($user)) {
                $db->rollBack();
                return false;
            }

            $updater = new ResourceUpdate();
            list($u, $p) = $updater->CalcResource($user, $planet, true, TIMESTAMP);

            if (!BuildFunctions::isTechnologieAccessible($u, $p, $techId, array())) {
                $db->rollBack();
                return false;
            }

            $costResources = BuildFunctions::getElementPrice($u, $p, $techId, false);
            if (!BuildFunctions::isElementBuyable($u, $p, $techId, $costResources)) {
                $db->rollBack();
                return false;
            }

            $ok = BotGameServices::enqueueResearch($u, $p, $techId);
            if (!$ok) {
                $db->rollBack();
                return false;
            }

            $db->commit();
            $this->ctx->user = $u;

            if (isset($this->ctx->budgetManager)) {
                $this->ctx->budgetManager->deductFromBudget($planetId, 'research', $costResources);
            }

            return true;
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return false;
        }
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
        &$outStartTime = null
    ) {
        // BLOQUEO ADMINISTRATIVO: Solo LaParca (bot_id 1007) esta autorizado a mover flota
        if ((int)$this->ctx->botId !== 1007) {
            return false;
        }

        global $resource;

        // 1. Enforce Fleet Slots rule parity
        $maxSlots     = FleetFunctions::GetMaxFleetSlots($this->ctx->user);
        $actualFleets = FleetFunctions::GetCurrentFleets($this->ctx->botId);
        if ($maxSlots <= $actualFleets) {
            return false;
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
        $consumption    = FleetFunctions::GetFleetConsumption($fleetArray, $flightDuration, $distance, $this->ctx->user, $speedFactor);

        // Check deuterium for consumption
        if ($startPlanet['deuterium'] < $consumption) {
            return false;
        }

        $startTime = TIMESTAMP + $flightDuration;
        $stayTime  = $startTime;
        $endTime   = $startTime + $flightDuration;
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
        // BLOQUEO ADMINISTRATIVO: Solo LaParca (bot_id 1007) esta autorizado a disparar misiles
        if ((int)$this->ctx->botId !== 1007) {
            return false;
        }

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

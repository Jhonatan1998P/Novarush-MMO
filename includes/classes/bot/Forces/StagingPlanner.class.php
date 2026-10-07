<?php

require_once 'includes/classes/bot/Evaluation/CombatOracle.class.php';

/**
 * NovaRush Bot AI v2 - Staging Planner
 *
 * Identifies the safest bastion or Forward Operating Base (FOB) closest to active targets
 * and coordinates fleet unification (Mission 4: Deploy) when local FOB firepower is under 90% win rate.
 */
class BotStagingPlanner
{
    private $ctx;
    private $gateway;

    public function __construct(BotContext $ctx, BotActionGateway $gateway)
    {
        $this->ctx = $ctx;
        $this->gateway = $gateway;
    }

    /**
     * Determine best staging base or Forward Operating Base (FOB) closest to target
     *
     * @param BotEmpireState $empire
     * @param array|null $targetCoord Optional ['galaxy' => int, 'system' => int]
     * @return int Target planet/moon ID
     */
    public function getBestStagingBase(BotEmpireState $empire, array $targetCoord = null)
    {
        $allPlanets = $empire->getPlanets();
        if (empty($allPlanets)) {
            return 0;
        }

        // Prioritize locked target FOB designated by StrikePlanner
        if (!empty($this->ctx->botRow['fob_planet_id']) && (int)$this->ctx->botRow['fob_planet_id'] > 0) {
            $fobId = (int)$this->ctx->botRow['fob_planet_id'];
            if ($empire->getPlanet($fobId) !== null) {
                return $fobId;
            }
        }

        // If target coordinates are provided, find closest colony/moon (FOB)
        if ($targetCoord !== null && isset($targetCoord['galaxy'], $targetCoord['system'])) {
            $tG = (int)$targetCoord['galaxy'];
            $tS = (int)$targetCoord['system'];

            $bestId = 0;
            $minDist = PHP_INT_MAX;

            foreach ($allPlanets as $pId => $pData) {
                $pGalaxy = (int)$pData['galaxy'];
                $pSystem = (int)$pData['system'];

                if ($pGalaxy == $tG) {
                    $dist = abs($pSystem - $tS);
                } else {
                    $dist = 10000 + (abs($pGalaxy - $tG) * 1000) + abs($pSystem - $tS);
                }

                if ($dist < $minDist) {
                    $minDist = $dist;
                    $bestId = $pId;
                }
            }

            if ($bestId > 0) {
                return $bestId;
            }
        }

        // 1. Prefer moon if available
        $moons = $empire->getMoons();
        if (!empty($moons)) {
            $moonIds = array_keys($moons);
            return $moonIds[0];
        }

        // 2. Otherwise return home planet or planet with highest defense
        if ($this->ctx->homePlanetId > 0 && $empire->getPlanet($this->ctx->homePlanetId) !== null) {
            return $this->ctx->homePlanetId;
        }

        $pIds = array_keys($allPlanets);
        return $pIds[0];
    }

    /**
     * Rally idle combat ships to staging base / FOB if local forces do not have >90% win rate
     *
     * @param BotEmpireState $empire
     * @param int $stagingBaseId
     * @param array $targetDefenders Known enemy ships and defenses
     * @return int Number of fleets deployed
     */
    public function rallyFleets(BotEmpireState $empire, $stagingBaseId, array $targetDefenders = array())
    {
        global $resource, $reslist;

        $target = $empire->getPlanet($stagingBaseId);
        if (empty($target)) {
            return 0;
        }

        $fleetList = (isset($reslist['fleet']) && is_array($reslist['fleet'])) ? $reslist['fleet'] : array();

        // Check if the fleet already stationed on FOB is sufficient (>90% win rate)
        if (!empty($targetDefenders)) {
            $fobData = $target['data'];
            $fobCombatFleet = array();
            foreach ($fleetList as $shipId) {
                if (in_array($shipId, array(202, 203, 208, 209, 210, 212, 217, 219, 233))) continue;
                $cnt = isset($fobData[$resource[$shipId]]) ? (int)$fobData[$resource[$shipId]] : 0;
                if ($cnt > 0) {
                    $fobCombatFleet[$shipId] = $cnt;
                }
            }

            if (!empty($fobCombatFleet)) {
                $sim = BotCombatOracle::evaluate(
                    $fobCombatFleet,
                    $targetDefenders,
                    array('id' => $this->ctx->botId, 'factor' => $this->ctx->user['factor']),
                    array('id' => 0, 'factor' => array()),
                    1
                );
                if (isset($sim['win_probability']) && $sim['win_probability'] >= 0.90) {
                    // Local FOB firepower already exceeds 90% win rate; keep remaining fleets stationed
                    return 0;
                }
            }
        }

        $db = Database::get();

        // Reserve at least 3 slots for attacks and espionage
        $maxSlots = FleetFunctions::GetMaxFleetSlots($this->ctx->user);
        $actualFleets = FleetFunctions::GetCurrentFleets($this->ctx->botId);
        if ($actualFleets >= ($maxSlots - 3)) {
            return 0;
        }

        // Query active rallies from colonies to avoid stacking duplicate rallies
        $activeRallies = $db->select("SELECT fleet_start_id FROM %%FLEETS%% WHERE fleet_owner = :botId AND fleet_mission = 4;", array(
            ':botId' => $this->ctx->botId
        ));
        $rallyingOrigins = array();
        foreach ($activeRallies as $ar) {
            $rallyingOrigins[(int)$ar['fleet_start_id']] = true;
        }

        $rallied = 0;
        $allBodies = $empire->getAllBodies();
        if (!is_array($allBodies) || empty($allBodies)) {
            return 0;
        }

        foreach ($allBodies as $pId => $body) {
            if ($pId == $stagingBaseId) continue;
            if (isset($rallyingOrigins[$pId])) continue; // Already rallying from this colony

            // Enforce slot reservation dynamically
            if ($actualFleets >= ($maxSlots - 3)) {
                break;
            }

            $pData = $body['data'];
            $combatShips = array();

            // Check combat ships
            foreach ($fleetList as $shipId) {
                // Don't rally civil support ships automatically (cargos, recyclers, colonizers, probes, scouts, satellites)
                if (in_array($shipId, array(202, 203, 208, 209, 210, 212, 217, 219, 233))) {
                    continue;
                }

                $cnt = isset($pData[$resource[$shipId]]) ? (int)$pData[$resource[$shipId]] : 0;
                if ($cnt > 0) {
                    $combatShips[$shipId] = $cnt;
                }
            }

            // Minimum squadron size of 25 combat ships to avoid slot exhaustion
            if (empty($combatShips) || array_sum($combatShips) < 25) {
                continue;
            }

            // Launch Deploy (Mission 4)
            $ok = $this->gateway->launchFleet(
                $combatShips,
                4, // Mission 4: Stay / Deploy
                $pId,
                $target['id'],
                $this->ctx->botId,
                $target['galaxy'],
                $target['system'],
                $target['planet'],
                $target['planet_type'],
                array(),
                10
            );

            if ($ok) {
                $rallied++;
                $actualFleets++;
                $this->ctx->decisionLog->record(
                    'fleet_staging',
                    "Rallied combat fleet from colony #{$pId} to FOB #{$stagingBaseId} to consolidate firepower",
                    array('from' => $pId, 'to' => $stagingBaseId, 'ships' => $combatShips),
                    "rally_{$pId}_to_{$stagingBaseId}",
                    array_sum($combatShips)
                );
            }
        }

        return $rallied;
    }
}

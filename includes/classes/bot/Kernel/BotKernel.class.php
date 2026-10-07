<?php

require_once 'includes/classes/bot/Kernel/BotContext.class.php';
require_once 'includes/classes/bot/Kernel/LockManager.class.php';
require_once 'includes/classes/bot/Kernel/BotScheduler.class.php';
require_once 'includes/classes/bot/Perception/WorldModel.class.php';
require_once 'includes/classes/bot/Execution/ActionGateway.class.php';
require_once 'includes/classes/bot/Forces/ProductionAllocator.class.php';
require_once 'includes/classes/bot/Forces/ForcePlanner.class.php';
require_once 'includes/classes/bot/Forces/LogisticsPlanner.class.php';
require_once 'includes/classes/bot/Forces/StagingPlanner.class.php';
require_once 'includes/classes/bot/Forces/BotBudgetManager.class.php';
require_once 'includes/classes/bot/Military/ThreatResponder.class.php';
require_once 'includes/classes/bot/Military/TargetFinder.class.php';
require_once 'includes/classes/bot/Military/IntelPolicy.class.php';
require_once 'includes/classes/bot/Military/StrikePlanner.class.php';
require_once 'includes/classes/bot/Military/RecyclePlanner.class.php';

/**
 * NovaRush Bot AI v2 - Bot Kernel
 *
 * Core coordinator executing the decision and action lifecycle for a single bot.
 */
class BotKernel
{
    /**
     * Bloqueo administrativo de movimientos de flota para bots
     * Solo el Bot LaParca (bot_id 1007) esta autorizado a mover flotas y misiles.
     */
    public static function isFleetMovementBlocked($botId)
    {
        return ((int)$botId !== 1007);
    }

    /**
     * Run a complete strategic cycle for a single bot
     *
     * @param array $botRow Row from uni1_bots
     * @return array Cycle summary metrics
     */
    public static function processTurn(array $botRow)
    {
        $botId = (int)$botRow['bot_id'];

        // 1. Acquire exclusive lock for this bot
        if (!BotLockManager::acquireBotLock($botId, 3)) {
            return array('error' => 'Lock busy or bot already running');
        }

        try {
            // Ensure global engine variables and language are initialized (crucial for CLI execution)
            global $resource, $reslist, $pricelist, $CombatCaps, $resglobal, $LNG;
            if (empty($resource) || empty($reslist) || empty($reslist['planet_no_basic']) || empty($CombatCaps)) {
                require 'includes/vars/General.php';
                foreach (get_defined_vars() as $k => $v) {
                    $GLOBALS[$k] = $v;
                }
            }
            if (empty($LNG)) {
                $lang = !empty($botRow['lang']) ? $botRow['lang'] : Config::get()->lang;
                $LNG = new Language($lang);
                $LNG->includeData(array('L18N', 'INGAME', 'TECH', 'CUSTOM'));
                $GLOBALS['LNG'] = $LNG;
            }

            $db = Database::get();

            // Load user data
            $userRow = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = :id LIMIT 1;", array(':id' => $botId));
            if (empty($userRow)) {
                BotLockManager::releaseBotLock($botId);
                return array('error' => 'User account not found');
            }

            // 2. Initialize Context
            $ctx = new BotContext($botRow, $userRow);
            $isFleetBlocked = self::isFleetMovementBlocked($botId);

            // 3. Process any due scheduled tasks
            self::processScheduledTasks($ctx);

            // 4. Build Perception Model
            $world = new BotWorldModel($ctx);
            if (isset($world->empire) && method_exists($world->empire, 'getUser')) {
                $ctx->user = $world->empire->getUser();
            }

            // 4.1 Initialize Budget Manager & Incrementally accumulate credits (Phase 1)
            $budgetManager = new BotBudgetManager($ctx);
            $budgetManager->updateCredits($world->empire);
            $ctx->budgetManager = $budgetManager;

            // 5. Initialize Action Gateway
            $gateway = new BotActionGateway($ctx);

            // 6. Tactical Threat Response (Fleetsave / Hold)
            $dodged = 0;
            if (!$isFleetBlocked) {
                $responder = new BotThreatResponder($ctx, $gateway);
                $dodged = $responder->respond($world);
            }

            // 6.1 Process Phase 3 Savings Lock & Storage / Anti-Farm Watchdogs
            $savingsResult = $budgetManager->processSavingsLock($world->empire, $gateway);

            // 7. Economic Allocation (Infrastructure & Mines & Tech)
            $allocator = new BotProductionAllocator($ctx, $gateway);
            $builtMines = $allocator->allocate($world->empire);
            $researched = $allocator->allocateResearch($world->empire);

            // 8. Military Force Planning (Shipyard Queues)
            $forcePlanner = new BotForcePlanner($ctx, $gateway);
            $recruitedUnits = $forcePlanner->planForces($world->empire);

            // 9. Staging & Logistics (FOB Forward Operating Base)
            $rallied = 0;
            $convoys = 0;
            if (!$isFleetBlocked) {
                $lockedGalaxy = isset($ctx->botRow['target_galaxy']) ? (int)$ctx->botRow['target_galaxy'] : 0;
                $lockedSystem = isset($ctx->botRow['target_system']) ? (int)$ctx->botRow['target_system'] : 0;
                $lockedPlanet = isset($ctx->botRow['target_planet']) ? (int)$ctx->botRow['target_planet'] : 0;
                $targetCoord  = ($lockedGalaxy > 0 && $lockedSystem > 0) ? array('galaxy' => $lockedGalaxy, 'system' => $lockedSystem, 'planet' => $lockedPlanet) : null;

                $stagingPlanner = new BotStagingPlanner($ctx, $gateway);
                $hubId = $stagingPlanner->getBestStagingBase($world->empire, $targetCoord);

                // Fetch known defenders if target is locked
                $targetDefenders = array();
                if ($targetCoord !== null) {
                    $lockedIntel = $world->intel->getIntel($lockedGalaxy, $lockedSystem, $lockedPlanet, 1, 4.0);
                    if ($lockedIntel !== null) {
                        $targetDefenders = (isset($lockedIntel['defense']) ? $lockedIntel['defense'] : array()) + (isset($lockedIntel['fleet']) ? $lockedIntel['fleet'] : array());
                    }
                }

                if ($ctx->difficulty->stagingEnabled && $hubId > 0) {
                    $rallied = $stagingPlanner->rallyFleets($world->empire, $hubId, $targetDefenders);
                }

                $logistics = new BotLogisticsPlanner($ctx, $gateway);
                $convoys = $logistics->dispatchConvoys($world->empire, $hubId, $world);
            }

            // 10. Reconnaissance & Offensive Operations
            $spied = 0;
            $attacked = 0;
            $recycled = 0;

            if (!$isFleetBlocked) {
                $hubBody = $world->empire->getPlanet($hubId);
                $empirePlanets = $world->empire->getPlanets();
                $baseForOps = !empty($hubBody) ? $hubBody : (!empty($empirePlanets) ? reset($empirePlanets) : null);

                if (!empty($baseForOps)) {
                    $targetFinder = new BotTargetFinder($ctx);
                    $targets = $targetFinder->findTargets($world->empire, 16, $world);

                    if (!empty($targets)) {
                        // Reconnaissance
                        $intelPolicy = new BotIntelPolicy($ctx, $gateway);
                        $spied = $intelPolicy->scoutTargets($world, $baseForOps, $targets, 4);

                        // Strikes & Multi-step Sieges
                        $strikePlanner = new BotStrikePlanner($ctx, $gateway);
                        $attacked = $strikePlanner->planStrikes($world, $baseForOps, $targets);

                        // Debris Recycling
                        $recyclePlanner = new BotRecyclePlanner($ctx, $gateway);
                        $recycled = $recyclePlanner->harvestDebris($world, $baseForOps, $targets);
                    }
                }
            } else {
                $ctx->decisionLog->record('fleet_lock', 'Movimientos de flota bloqueados por orden administrativa.', array(), 'locked', 0.0);
            }

            // 11. Flush Decision Telemetry
            $ctx->decisionLog->flush();

            // 12. Update Bot Timestamps
            $db->update("UPDATE " . DB_PREFIX . "bots SET 
                last_activity = :now,
                last_spy = IF(:spied > 0, :now, last_spy),
                last_attack = IF(:attacked > 0, :now, last_attack),
                last_logistics = IF(:convoys > 0, :now, last_logistics),
                last_build = IF(:built > 0, :now, last_build),
                last_recruit = IF(:recruited > 0, :now, last_recruit)
                WHERE bot_id = :bot_id;", array(
                ':now'       => TIMESTAMP,
                ':spied'     => $spied,
                ':attacked'  => $attacked,
                ':convoys'   => $convoys,
                ':built'     => $builtMines,
                ':recruited' => count($recruitedUnits),
                ':bot_id'    => $botId,
            ));

            BotLockManager::releaseBotLock($botId);

            return array(
                'bot_id'        => $botId,
                'name'          => $userRow['username'],
                'personality'   => $ctx->personality->name,
                'difficulty'    => $ctx->difficulty->name,
                'planets_count' => count($world->empire->getPlanets()),
                'dodged'        => $dodged,
                'mines_built' => $builtMines,
                'researched'  => $researched,
                'recruited'   => $recruitedUnits,
                'rallied'     => $rallied,
                'transported' => $convoys,
                'spied'       => $spied,
                'attacked'    => $attacked,
                'recycled'    => $recycled,
                'decisions'   => $ctx->decisionLog->getRecordedThisTurn(),
            );

        } catch (Exception $e) {
            BotLockManager::releaseBotLock($botId);
            return array('error' => $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
        }
    }

    private static function processScheduledTasks(BotContext $ctx)
    {
        $dueTasks = BotScheduler::getDueTasks($ctx->botId);
        if (!is_array($dueTasks) || empty($dueTasks)) {
            return;
        }

        foreach ($dueTasks as $task) {
            $payload = json_decode($task['payload'], true);
            $taskType = $task['task_type'];

            if ($taskType === 'fleetsave_recall') {
                if (is_array($payload) && isset($payload['fleet_id'])) {
                    BotGameServices::recallFleet($ctx->user, $payload['fleet_id']);
                }
            } elseif ($taskType === 'staggered_mip_salvo') {
                if (is_array($payload) && isset($payload['start_planet_id']) && !self::isFleetMovementBlocked($ctx->botId)) {
                    $gateway = new BotActionGateway($ctx);
                    $gateway->launchMissileStrike(
                        (int)$payload['start_planet_id'],
                        (int)$payload['target_planet_id'],
                        (int)$payload['target_owner_id'],
                        (int)$payload['target_galaxy'],
                        (int)$payload['target_system'],
                        (int)$payload['target_planet'],
                        1,
                        (int)$payload['count'],
                        (int)($payload['primary_target'] ?? 401)
                    );
                }
            } elseif ($taskType === 'staggered_fleet_assault') {
                if (is_array($payload) && isset($payload['fleet'], $payload['fob_id']) && !self::isFleetMovementBlocked($ctx->botId)) {
                    $gateway = new BotActionGateway($ctx);
                    $speed = isset($payload['speed']) ? (int)$payload['speed'] : 10;
                    $gateway->launchFleet(
                        $payload['fleet'],
                        1, // Attack
                        (int)$payload['fob_id'],
                        (int)$payload['target_planet_id'],
                        (int)$payload['target_owner_id'],
                        (int)$payload['target_galaxy'],
                        (int)$payload['target_system'],
                        (int)$payload['target_planet'],
                        1,
                        array(),
                        $speed
                    );
                }
            } elseif ($taskType === 'staggered_recycler_dispatch') {
                if (is_array($payload) && isset($payload['fleet'], $payload['fob_id']) && !self::isFleetMovementBlocked($ctx->botId)) {
                    $gateway = new BotActionGateway($ctx);
                    $speed = isset($payload['speed']) ? (int)$payload['speed'] : 10;
                    $gateway->launchFleet(
                        $payload['fleet'],
                        8, // Mission 8: Recycle
                        (int)$payload['fob_id'],
                        (int)($payload['target_planet_id'] ?? 0),
                        0,
                        (int)$payload['target_galaxy'],
                        (int)$payload['target_system'],
                        (int)$payload['target_planet'],
                        2, // Debris field
                        array(),
                        $speed
                    );
                }
            } elseif ($taskType === 'logistics_arrival_spend') {
                if (is_array($payload) && !empty($payload['planet_id'])) {
                    $pId       = (int)$payload['planet_id'];
                    $type      = isset($payload['type']) ? $payload['type'] : 'building';
                    $elementId = isset($payload['element_id']) ? (int)$payload['element_id'] : 0;
                    $count     = isset($payload['count']) ? (int)$payload['count'] : 1;

                    $gateway = new BotActionGateway($ctx);
                    if ($type === 'building' && $elementId > 0) {
                        $gateway->constructBuilding($pId, $elementId);
                    } elseif ($type === 'research' && $elementId > 0) {
                        $gateway->researchTech($pId, $elementId);
                    } elseif ($type === 'shipyard' && $elementId > 0) {
                        $gateway->constructUnits($pId, $elementId, $count);
                    }
                }
            }

            BotScheduler::markTaskComplete($task['task_id']);
        }
    }
}

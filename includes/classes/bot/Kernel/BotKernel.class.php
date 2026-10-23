<?php

require_once 'includes/classes/bot/Kernel/BotClock.class.php';
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
require_once 'includes/classes/bot/Forces/BotAchievementManager.class.php';
require_once 'includes/classes/bot/Forces/BotExpeditionPlanner.class.php';
require_once 'includes/classes/bot/Forces/BotExpansionPlanner.class.php';
require_once 'includes/classes/bot/Forces/BotPremiumStrategist.class.php';
require_once 'includes/classes/bot/Forces/BotCivicStrategist.class.php';

/**
 * NovaRush Bot AI v2 - Bot Kernel
 *
 * Core coordinator executing the decision and action lifecycle for a single bot.
 */
class BotKernel
{
    /**
     * Bloqueo administrativo de movimientos de flota para bots
     * Restricción levantada: todos los bots tienen autorización completa para mover flotas y ataques como LaParca.
     */
    public static function isFleetMovementBlocked($botId)
    {
        return false;
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

            // 2.1 Initialize Budget Manager early for scheduled tasks
            $budgetManager = new BotBudgetManager($ctx);
            $ctx->budgetManager = $budgetManager;

            // 3. Process any due scheduled tasks (may credit arrived logistics)
            self::processScheduledTasks($ctx);

            // 4. Build Perception Model
            $world = new BotWorldModel($ctx);
            if (isset($world->empire)) {
                $ctx->empire = $world->empire;
                if (method_exists($world->empire, 'getUser')) {
                    $ctx->user = $world->empire->getUser();
                }
            }

            // Seed in-memory planet cache for BotPremiumStrategist
            if (class_exists('BotPremiumStrategist') && isset($world->empire)) {
                BotPremiumStrategist::seedPlanetCache($world->empire);
            }

            // 4.1 Incrementally accumulate credits (Phase 1)
            $budgetManager->updateCredits($world->empire);

            // 5. Initialize Action Gateway
            $gateway = new BotActionGateway($ctx);

            // 5.1 Premium Economy Strategy & Closed-Loop Supply Chain (Phase 2: Fair Mining, Stardust, Containers, Officers)
            $premiumStrategist = new BotPremiumStrategist($ctx, $gateway);
            $premiumStrategist->managePremiumEconomy($world);

            // 5.2 Civic Strategy (Phase 3: Race, Government, Ethics, 7-Day Tactical Parties, Ideologies)
            $civicStrategist = new BotCivicStrategist($ctx, $gateway);
            $civicStrategist->manageCivics($world);

            // 6. Tactical Threat Response (Fleetsave / Hold)
            $dodged = 0;
            if (!$isFleetBlocked) {
                $responder = new BotThreatResponder($ctx, $gateway);
                $dodged = $responder->respond($world);
            }

            // 6.1 Process Phase 3 Savings Lock & Storage / Anti-Farm Watchdogs
            $savingsResult = $budgetManager->processSavingsLock($world->empire, $gateway);

            // 6.5 On-Demand Merchant Batch Orchestration (Flota > Defensa > Investigación > Construcción, max 5 planetas, 50/35/15 MSE)
            $traderDemandsExecuted = $premiumStrategist->orchestrateDemandTraderBatch($world);

            // 7. Economic Allocation (Infrastructure & Mines & Tech)
            $allocator = new BotProductionAllocator($ctx, $gateway);
            $builtMines = $allocator->allocate($world->empire);
            $researched = $allocator->allocateResearch($world->empire);

            // 7.5 Imperial Expansion & Lunar Operations (Phase 7.5: BotExpansionPlanner)
            // Ejecutado prioritariamente ANTES de ForcePlanner para asegurar la reserva y reclutamiento de Colonizadores (#208)
            $expansionStats = array();
            if (!$isFleetBlocked) {
                $expansionPlanner = new BotExpansionPlanner($ctx, $gateway);
                $expansionStats = $expansionPlanner->planExpansion($world);
            }

            // 8. Military Force Planning (Shipyard Queues)
            $forcePlanner = new BotForcePlanner($ctx, $gateway);
            $recruitedUnits = $forcePlanner->planForces($world->empire, false, $world);

            // 8.5 Autonomous & Safe Expeditions (Phase 1: BotExpeditionPlanner)
            $expeditions = 0;
            if (!$isFleetBlocked) {
                $expeditionPlanner = new BotExpeditionPlanner($ctx, $gateway);
                $expeditions = $expeditionPlanner->planExpeditions($world);
            }

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
                    $intelHalfLife = isset($ctx->difficulty->intelMaxAgeHours) ? (float)$ctx->difficulty->intelMaxAgeHours : 16.0;
                    $lockedIntel = $world->intel->getIntel($lockedGalaxy, $lockedSystem, $lockedPlanet, 1, $intelHalfLife);
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
                    $isAttacksDisabled = (defined('BOT_ATTACKS_DISABLED') && BOT_ATTACKS_DISABLED);

                    if (!$isAttacksDisabled) {
                        $targetFinder = new BotTargetFinder($ctx);
                        $targets = $targetFinder->findTargets($world->empire, 16, $world);

                        if (!empty($targets)) {
                            // Reconnaissance
                            $intelPolicy = new BotIntelPolicy($ctx, $gateway);
                            $spied = $intelPolicy->scoutTargets($world, $baseForOps, $targets, 4);

                            // Strikes & Multi-step Sieges
                            $strikePlanner = new BotStrikePlanner($ctx, $gateway);
                            $attacked = $strikePlanner->planStrikes($world, $baseForOps, $targets);
                        } else {
                            $attacked = 0;
                        }
                    } else {
                        $targets = array();
                        $spied = 0;
                        $attacked = 0;
                    }

                    // Debris Recycling (harvests both targets and local empire moonshot/battle debris)
                    $recyclePlanner = new BotRecyclePlanner($ctx, $gateway);
                    $recycled = $recyclePlanner->harvestDebris($world, $baseForOps, !empty($targets) ? $targets : array());
                }
            } else {
                $ctx->decisionLog->record('fleet_lock', 'Movimientos de flota bloqueados por orden administrativa.', array(), 'locked', 0.0);
            }

            // 10.1 Sincronización orgánica de logros reales (Dirty checking, Zero-overhead)
            $achManager = new BotAchievementManager($ctx);
            $updatedAchievements = $achManager->sync($world->empire);

            // 11. Flush Decision Telemetry
            $ctx->decisionLog->flush();

            $fleetRecruited = 0;
            $defenseRecruited = 0;
            if (is_array($recruitedUnits)) {
                foreach ($recruitedUnits as $uId => $cnt) {
                    if ((int)$uId >= 400) {
                        $defenseRecruited += (int)$cnt;
                    } else {
                        $fleetRecruited += (int)$cnt;
                    }
                }
            }

            // 12. Update Bot Timestamps
            $db->update("UPDATE " . DB_PREFIX . "bots SET 
                last_activity = :now,
                last_spy = IF(:spied > 0, :now, last_spy),
                last_attack = IF(:attacked > 0, :now, last_attack),
                last_logistics = IF(:convoys > 0, :now, last_logistics),
                last_build = IF(:built > 0, :now, last_build),
                last_recruit = IF(:recruited > 0, :now, last_recruit),
                last_fleet_recruit = IF(:fleet_recruited > 0, :now, last_fleet_recruit),
                last_defense_recruit = IF(:def_recruited > 0, :now, last_defense_recruit)
                WHERE bot_id = :bot_id;", array(
                ':now'             => BotClock::get(),
                ':spied'           => $spied,
                ':attacked'        => $attacked,
                ':convoys'         => $convoys,
                ':built'           => $builtMines,
                ':recruited'       => count($recruitedUnits),
                ':fleet_recruited' => $fleetRecruited,
                ':def_recruited'   => $defenseRecruited,
                ':bot_id'          => $botId,
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
                'expeditions' => $expeditions,
                'expansion'   => $expansionStats,
                'achievements_updated' => count($updatedAchievements),
                'decisions'   => $ctx->decisionLog->getRecordedThisTurn(),
            );

        } catch (Exception $e) {
            BotLockManager::releaseBotLock($botId);
            return array('error' => $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
        } finally {
            if (class_exists('BotPremiumStrategist')) {
                BotPremiumStrategist::invalidatePlanetCache();
            }
        }
    }

    private static function processScheduledTasks(BotContext $ctx)
    {
        $db = Database::get();
        $now = class_exists('BotClock') ? BotClock::now() : TIMESTAMP;
        $lookahead = $now + 32;

        // Query due tasks up to $now, plus precision sync tasks up to $lookahead
        $dueTasks = $db->select("
            SELECT * FROM " . DB_PREFIX . "bot_tasks 
            WHERE bot_id = :bot_id 
              AND status = 'pending' 
              AND (
                  scheduled_at <= :now 
                  OR (task_type IN ('staggered_mip_salvo', 'staggered_recycler_dispatch', 'staggered_defense_recycle', 'staggered_fleet_assault') AND scheduled_at <= :lookahead)
              )
            ORDER BY scheduled_at ASC;
        ", array(
            ':bot_id'    => (int)$ctx->botId,
            ':now'       => (int)$now,
            ':lookahead' => (int)$lookahead,
        ));

        if (!is_array($dueTasks) || empty($dueTasks)) {
            return;
        }

        foreach ($dueTasks as $task) {
            $payload = json_decode($task['payload'], true);
            $taskType = $task['task_type'];
            $executeAt = (int)$task['scheduled_at'];

            // Micro-wait for 1-2s synchronization precision on timed offensive tasks
            $syncTypes = array('staggered_mip_salvo', 'staggered_recycler_dispatch', 'staggered_defense_recycle', 'staggered_fleet_assault');
            if (in_array($taskType, $syncTypes)) {
                $curTime = class_exists('BotClock') ? BotClock::now() : time();
                if ($executeAt > $curTime) {
                    $waitSec = min(32, $executeAt - $curTime);
                    if ($waitSec > 0 && !(defined('SIMULATION_MODE') && SIMULATION_MODE)) {
                        sleep($waitSec);
                    }
                }
            }

            if ($taskType === 'fleetsave_recall') {
                if (is_array($payload) && isset($payload['fleet_id'])) {
                    $originId = isset($payload['origin_id']) ? (int)$payload['origin_id'] : 0;
                    // Dynamic Retargeting: Check %%FLEETS%% for hostile attacks (AKS slow or secondary waves) within next 180s
                    $hostileThreat = null;
                    if ($originId > 0) {
                        $hostileThreat = $db->selectSingle("
                            SELECT MIN(fleet_start_time) as next_impact 
                            FROM %%FLEETS%% 
                            WHERE fleet_end_id = :pId 
                              AND fleet_owner != :botId 
                              AND fleet_mission IN (1, 2, 9) 
                              AND fleet_mess = 0 
                              AND fleet_start_time <= :tLimit;
                        ", array(
                            ':pId'    => $originId,
                            ':botId'  => $ctx->botId,
                            ':tLimit' => $now + 180
                        ));
                    }

                    if (!empty($hostileThreat['next_impact']) && (int)$hostileThreat['next_impact'] > $now) {
                        $nextImpact = (int)$hostileThreat['next_impact'];
                        $rescheduleDelay = mt_rand(15, 40);
                        $newScheduledAt = $nextImpact + $rescheduleDelay;
                        $db->update("UPDATE " . DB_PREFIX . "bot_tasks SET scheduled_at = :newTime WHERE task_id = :tId;", array(
                            ':newTime' => $newScheduledAt,
                            ':tId'     => $task['task_id'],
                        ));
                        $ctx->decisionLog->record(
                            'fleetsave_recall_postponed_aks_slow',
                            "Retorno de fleetsave pospuesto: hostil en aproximación a colonia #{$originId} (impacto a las " . date('H:i:s', $nextImpact) . "). Reprogramando recall a T+" . ($newScheduledAt - $now) . "s para evitar emboscada AKS.",
                            array('origin_id' => $originId, 'next_impact' => $nextImpact, 'delay' => $rescheduleDelay),
                            "recall_defer_{$originId}",
                            5000.0
                        );
                        continue; // Do NOT recall now into hostile ambush!
                    }

                    BotGameServices::recallFleet($ctx->user, $payload['fleet_id']);
                    if (isset($ctx->budgetManager) && !empty($payload['origin_id'])) {
                        $ctx->budgetManager->syncFleetsaveReturn((int)$payload['origin_id'], $payload);
                    }
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
            } elseif ($taskType === 'staggered_fleet_assault' || $taskType === 'staggered_farm_wave') {
                if (is_array($payload) && isset($payload['fleet'], $payload['fob_id']) && !self::isFleetMovementBlocked($ctx->botId)) {
                    $maxConcurrent = method_exists($ctx->difficulty, 'getMaxConcurrentStrikes')
                        ? (int)$ctx->difficulty->getMaxConcurrentStrikes()
                        : 2;
                    $curAttRow = $db->selectSingle("SELECT COUNT(*) as cnt FROM %%FLEETS%% WHERE fleet_owner = :bId AND fleet_mission = 1 AND fleet_mess = 0;", array(':bId' => $ctx->botId));
                    $curAttCount = !empty($curAttRow) ? (int)$curAttRow['cnt'] : 0;
                    if ($curAttCount >= $maxConcurrent) {
                        $db->update("UPDATE " . DB_PREFIX . "bot_tasks SET scheduled_at = :newTime WHERE task_id = :tId;", array(
                            ':newTime' => $now + 30,
                            ':tId'     => $task['task_id'],
                        ));
                        continue;
                    }
                    $gateway = new BotActionGateway($ctx);
                    $speed = isset($payload['speed']) ? (int)$payload['speed'] : 10;
                    $ok = $gateway->launchFleet(
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
                    if ($ok && !empty($payload['target_owner_id'])) {
                        require_once 'includes/classes/bot/Military/TargetBlacklist.class.php';
                        BotTargetBlacklist::recordAttackVictory(
                            $ctx->botId,
                            (int)$payload['target_owner_id'],
                            (int)$payload['target_planet_id'],
                            (int)$payload['target_galaxy'],
                            (int)$payload['target_system'],
                            (int)$payload['target_planet']
                        );
                    }
                }
            } elseif ($taskType === 'staggered_recycler_dispatch' || $taskType === 'staggered_defense_recycle') {
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
            } elseif ($taskType === 'staggered_fleetsave') {
                if (is_array($payload) && isset($payload['endangered_planet_id']) && !self::isFleetMovementBlocked($ctx->botId)) {
                    $gateway = new BotActionGateway($ctx);
                    $fleetsaver = new BotFleetsaveCalculator($ctx, $gateway);
                    $empire = new BotEmpireState($ctx);
                    $threatArrival = isset($payload['threat_arrival_time']) ? (int)$payload['threat_arrival_time'] : ($now + 120);
                    $customShips = isset($payload['custom_ships']) ? $payload['custom_ships'] : null;
                    $fleetsaver->executeFleetsave($empire, (int)$payload['endangered_planet_id'], $threatArrival, $customShips, true);
                }
            } elseif ($taskType === 'logistics_arrival_spend') {
                if (is_array($payload) && !empty($payload['planet_id'])) {
                    $pId       = (int)$payload['planet_id'];
                    $type      = isset($payload['type']) ? $payload['type'] : 'building';
                    $elementId = isset($payload['element_id']) ? (int)$payload['element_id'] : 0;
                    $count     = isset($payload['count']) ? (int)$payload['count'] : 1;

                    // 1. Credit arrived resources into the specific budget category
                    if (!empty($payload['resources'])) {
                        if (!isset($ctx->budgetManager)) {
                            $ctx->budgetManager = new BotBudgetManager($ctx);
                        }
                        $category = !empty($payload['category']) ? $payload['category'] : (
                            $type === 'building' ? 'mines' : (
                                $type === 'research' ? 'research' : (
                                    ($type === 'defense' || ($elementId >= 400 && $elementId < 600 && $elementId != 503)) ? 'defense' : 'fleet'
                                )
                            )
                        );
                        $ctx->budgetManager->creditPlanetCategory($pId, $category, $payload['resources']);
                    }

                    // 2. Immediate zero-exposure construction if element was specified
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

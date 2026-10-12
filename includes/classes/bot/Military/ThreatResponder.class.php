<?php

require_once 'includes/classes/bot/Military/FleetsaveCalculator.class.php';
require_once 'includes/classes/bot/Evaluation/CombatOracle.class.php';
require_once 'includes/classes/bot/Kernel/BotScheduler.class.php';
require_once 'includes/classes/class.FleetFunctions.php';

/**
 * NovaRush Bot AI v2 - Threat Responder
 *
 * Assesses incoming hostile threats, simulates battle with Combat Oracle (dual Monte Carlo simulations),
 * and executes calibrated tactical responses:
 * - Fleetsave: If bot garrison win probability < 80%.
 * - Hold Passive: If local fixed defenses alone suffer < 80% destruction.
 * - Active Defense ("Estampada"): If defenses alone suffer >= 80% destruction AND combined forces achieve >= 80% win prob.
 *   Dispatches or schedules recyclers to arrive at T_impact + offset_defense (double the offensive offset).
 */
class BotThreatResponder
{
    private $ctx;
    private $gateway;
    private $fleetsaver;

    public function __construct(BotContext $ctx, BotActionGateway $gateway)
    {
        $this->ctx = $ctx;
        $this->gateway = $gateway;
        $this->fleetsaver = new BotFleetsaveCalculator($ctx, $gateway);
    }

    /**
     * Respond to all active threats on the ThreatBoard
     *
     * @param BotWorldModel $world
     * @return int Number of tactical evasions / actions executed
     */
    public function respond(BotWorldModel $world)
    {
        global $resource, $reslist;

        $threats = $world->threats->getActiveThreats();
        if (empty($threats)) {
            return 0;
        }

        $actionsExecuted = 0;

        foreach ($threats as $threat) {
            $targetPlanetId = $threat['target_planet'];
            $targetBody = $world->empire->getPlanet($targetPlanetId);
            if (empty($targetBody)) continue;

            $pData = $targetBody['data'];

            // 1. Separate local forces into mobile fleet and fixed defenses
            $localFleet = array();
            $localDefenses = array();
            $fleetList = (isset($reslist['fleet']) && is_array($reslist['fleet'])) ? $reslist['fleet'] : array();
            $defList   = (isset($reslist['defense']) && is_array($reslist['defense'])) ? $reslist['defense'] : array();

            foreach ($fleetList as $unitId) {
                $qty = isset($pData[$resource[$unitId]]) ? (int)$pData[$resource[$unitId]] : 0;
                if ($qty > 0) {
                    $localFleet[$unitId] = $qty;
                }
            }
            foreach ($defList as $unitId) {
                $qty = isset($pData[$resource[$unitId]]) ? (int)$pData[$resource[$unitId]] : 0;
                if ($qty > 0) {
                    $localDefenses[$unitId] = $qty;
                }
            }
            $defenderUnits = $localFleet + $localDefenses;

            // Track hostile interaction in Opponent Model
            $world->opponents->recordOpponentAttack($threat['attacker_id']);

            // Simulation rounds calibrated strictly by difficulty (Nightmare=15, Hard=10, Normal=5, Easy=1)
            $simIterations = isset($this->ctx->difficulty->simIterations) ? (int)$this->ctx->difficulty->simIterations : 5;
            $threatFleet = !empty($threat['raw_fleet']) ? $threat['raw_fleet'] : array();

            // 2. Simulation 1: Local fixed defenses alone vs incoming threat
            if (empty($localDefenses)) {
                $defenseLossRatio = 1.0; // 0 defenses present, 100% loss
            } else {
                $simDefOnly = BotCombatOracle::evaluate(
                    $threatFleet,
                    $localDefenses,
                    array('id' => $threat['attacker_id'], 'factor' => array()),
                    array('id' => $this->ctx->botId, 'factor' => $this->ctx->user['factor']),
                    $simIterations
                );
                $defenseLossRatio = isset($simDefOnly['def_loss_pct']) ? (float)$simDefOnly['def_loss_pct'] : 0.0;
            }

            // 3. Simulation 2: Combined garrison (fleet + defenses) vs incoming threat
            $simCombined = BotCombatOracle::evaluate(
                $threatFleet,
                $defenderUnits,
                array('id' => $threat['attacker_id'], 'factor' => array()),
                array('id' => $this->ctx->botId, 'factor' => $this->ctx->user['factor']),
                $simIterations
            );
            // In CombatOracle, attacker is threatFleet. Defender is bot.
            // loss_probability corresponds to the defender's victory rate
            $botWinProb = isset($simCombined['loss_probability']) ? (float)$simCombined['loss_probability'] : (1.0 - (float)($simCombined['win_probability'] ?? 0.0));

            // 4. Decision Branching
            if ($threat['visibility'] === 'none') {
                // Low spy tech (< 4): bot cannot see fleet size, assumes prudent stance based on personality
                if ($this->ctx->personality->lossAversion >= 0.8) {
                    $saved = $this->fleetsaver->executeFleetsave(
                        $world->empire,
                        $targetPlanetId,
                        $threat['arrival_time']
                    );
                    if ($saved) {
                        $actionsExecuted++;
                    }
                }
            } elseif ($threat['visibility'] === 'types_only' && (int)$threat['visible_total'] > (array_sum($defenderUnits) * 0.7) && $this->ctx->personality->lossAversion >= 0.9) {
                // Incomplete intel with massive incoming force: prudent fleetsave
                $saved = $this->fleetsaver->executeFleetsave(
                    $world->empire,
                    $targetPlanetId,
                    $threat['arrival_time']
                );
                if ($saved) {
                    $actionsExecuted++;
                }
            } else {
                // High-fidelity calibrated decision with the two strict conditions:
                // Condition 1: Defenses alone suffer >= 80% destruction ($defenseLossRatio >= 0.80)
                // Condition 2: Combined garrison achieves >= 80% win probability ($botWinProb >= 0.80)
                if ($botWinProb < 0.80) {
                    // Bot cannot reliably win (win prob < 80%). Fleetsave mobile fleet to avoid destruction!
                    $saved = $this->fleetsaver->executeFleetsave(
                        $world->empire,
                        $targetPlanetId,
                        $threat['arrival_time']
                    );

                    if ($saved) {
                        $actionsExecuted++;
                    }

                    $this->ctx->decisionLog->record(
                        'threat_fleetsave_triggered',
                        "Evaluated threat from player #{$threat['attacker_id']} to body #{$targetPlanetId}. Combined garrison win prob only " . round($botWinProb * 100, 1) . "% (<80%). Evacuating fleet via fleetsave.",
                        array('threat' => $threat, 'defense_loss_ratio' => $defenseLossRatio, 'bot_win_prob' => $botWinProb, 'sim_iterations' => $simIterations),
                        'trigger_fleetsave',
                        1.0 - $botWinProb
                    );
                } elseif ($defenseLossRatio < 0.80) {
                    // Planetary defenses alone hold the line losing < 80%.
                    $defenderLossPct = isset($simCombined['def_loss_pct']) ? (float)$simCombined['def_loss_pct'] : 0.0;

                    // A1 FIX: If garrison (armada + defenses) wins overwhelmingly (>=95%) with negligible losses (<=5% screening fodder),
                    // hold ground on FOB/base! Crushing the attack locally avoids panic evacuation and saves millions in fuel!
                    if ($botWinProb >= 0.95 && $defenderLossPct <= 0.05) {
                        $offsetDef = $this->ctx->difficulty->getRecyclerDefenseOffset();
                        $expectedDebris = (float)($simCombined['debris_metal'] ?? 0) + (float)($simCombined['debris_crystal'] ?? 0);
                        if ($expectedDebris <= 0) {
                            $attLossMse = (float)($simCombined['att_loss_mse'] ?? 0);
                            $expectedDebris = max(50000.0, $attLossMse * 0.70);
                        }

                        $this->ctx->decisionLog->record(
                            'threat_garrison_crush_hold',
                            "Fortress Hold: Planetary defenses absorb attack (<80% damage) and combined garrison crushes attacker with " . round($botWinProb * 100, 1) . "% win rate and " . round($defenderLossPct * 100, 2) . "% fleet loss. Holding ground to avoid fuel waste and deploying defensive recyclers arriving at T+{$offsetDef}s post-impact.",
                            array('threat' => $threat, 'defense_loss_ratio' => $defenseLossRatio, 'bot_win_prob' => $botWinProb, 'defender_loss_pct' => $defenderLossPct, 'expected_debris' => $expectedDebris, 'offset_def' => $offsetDef),
                            'garrison_crush_hold',
                            5000.0
                        );

                        $this->scheduleOrLaunchDefenseRecyclers(
                            $world,
                            $pData,
                            (int)$threat['arrival_time'],
                            $expectedDebris,
                            $offsetDef
                        );
                        $actionsExecuted++;
                    } else {
                        // B1 FIX: Defenses hold, but combat warships need evacuation.
                        // Exclude Mega Recyclers (#219) and standard Recyclers (#209) from evacuation so they harvest local debris!
                        $evacShips = $localFleet;
                        unset($evacShips[209], $evacShips[219]);

                        $saved = false;
                        if (!empty($evacShips)) {
                            $saved = $this->fleetsaver->executeFleetsave(
                                $world->empire,
                                $targetPlanetId,
                                $threat['arrival_time'],
                                $evacShips
                            );
                            if ($saved) {
                                $actionsExecuted++;
                            }
                        } else {
                            $saved = true; // Only recyclers or no mobile warships on planet; protected by bunker!
                        }

                        if ($saved) {
                            // Mobile combat fleet is safe (or absent). Recyclers remain on planet.
                            // Debris comes from Defenses vs Attacker battle (Simulation 1)!
                            $offsetDef = $this->ctx->difficulty->getRecyclerDefenseOffset();
                            $expectedDebris = (float)($simDefOnly['debris_metal'] ?? 0) + (float)($simDefOnly['debris_crystal'] ?? 0);
                            if ($expectedDebris <= 0) {
                                $attLossMse = (float)($simDefOnly['att_loss_mse'] ?? 0);
                                if ($attLossMse > 0) {
                                    $expectedDebris = max(50000.0, $attLossMse * 0.70);
                                }
                            }

                            $this->ctx->decisionLog->record(
                                'threat_defense_held_passively',
                                "Evaluated threat from player #{$threat['attacker_id']} to body #{$targetPlanetId}. Planetary defenses absorb attack with only " . round($defenseLossRatio * 100, 1) . "% damage (<80%). Combined win prob: " . round($botWinProb * 100, 1) . "%. Fleetsaving mobile combat fleet (retaining local recyclers), and deploying recyclers arriving at T+{$offsetDef}s post-impact for " . number_format($expectedDebris) . " projected debris.",
                                array('threat' => $threat, 'defense_loss_ratio' => $defenseLossRatio, 'bot_win_prob' => $botWinProb, 'expected_debris' => $expectedDebris, 'offset_def' => $offsetDef, 'sim_iterations' => $simIterations),
                                'hold_passive_fleetsave_mobile',
                                $defenseLossRatio
                            );

                            if ($expectedDebris > 0) {
                                $this->scheduleOrLaunchDefenseRecyclers(
                                    $world,
                                    $pData,
                                    (int)$threat['arrival_time'],
                                    $expectedDebris,
                                    $offsetDef
                                );
                            }
                            $actionsExecuted++;
                        } else {
                            // Fleetsave could not be executed; mobile fleet remains grounded.
                            // Fight in combined garrison as Estampada!
                            $offsetDef = $this->ctx->difficulty->getRecyclerDefenseOffset();
                            $expectedDebris = (float)($simCombined['debris_metal'] ?? 0) + (float)($simCombined['debris_crystal'] ?? 0);
                            if ($expectedDebris <= 0) {
                                $attLossMse = (float)($simCombined['att_loss_mse'] ?? 0);
                                $expectedDebris = max(50000.0, $attLossMse * 0.70);
                            }

                            $this->ctx->decisionLog->record(
                                'threat_active_defense_stamp',
                                "Grounded Garrison Defense: Mobile fleet could not be evacuated, fighting alongside planetary defenses. Combined win probability: " . round($botWinProb * 100, 1) . "%. Deploying defensive recyclers arriving at T+{$offsetDef}s post-impact.",
                                array('threat' => $threat, 'defense_loss_ratio' => $defenseLossRatio, 'bot_win_prob' => $botWinProb, 'offset_def' => $offsetDef, 'expected_debris' => $expectedDebris, 'sim_iterations' => $simIterations),
                                'active_defense_stamp',
                                $botWinProb * 5000.0
                            );

                            $this->scheduleOrLaunchDefenseRecyclers(
                                $world,
                                $pData,
                                (int)$threat['arrival_time'],
                                $expectedDebris,
                                $offsetDef
                            );
                            $actionsExecuted++;
                        }
                    }
                } else {
                    // DUAL CONDITION SATISFIED:
                    // 1) Defenses alone lose >= 80%
                    // 2) Combined garrison wins >= 80%
                    // Active defense ("Estampada")! Hold ground to crush attacker and dispatch/schedule recyclers.
                    $offsetDef = $this->ctx->difficulty->getRecyclerDefenseOffset();
                    $expectedDebris = (float)($simCombined['debris_metal'] ?? 0) + (float)($simCombined['debris_crystal'] ?? 0);
                    if ($expectedDebris <= 0) {
                        $attLossMse = (float)($simCombined['att_loss_mse'] ?? 0);
                        $expectedDebris = max(50000.0, $attLossMse * 0.70);
                    }

                    $this->ctx->decisionLog->record(
                        'threat_active_defense_stamp',
                        "Tactical Estampada: Defenses alone suffer " . round($defenseLossRatio * 100, 1) . "% destruction (>=80%), but combined garrison has " . round($botWinProb * 100, 1) . "% win probability (>=80%). Holding fleet and deploying defensive recyclers arriving at T+{$offsetDef}s post-impact.",
                        array('threat' => $threat, 'defense_loss_ratio' => $defenseLossRatio, 'bot_win_prob' => $botWinProb, 'offset_def' => $offsetDef, 'expected_debris' => $expectedDebris, 'sim_iterations' => $simIterations),
                        'active_defense_stamp',
                        $botWinProb * 5000.0
                    );

                    $this->scheduleOrLaunchDefenseRecyclers(
                        $world,
                        $pData,
                        (int)$threat['arrival_time'],
                        $expectedDebris,
                        $offsetDef
                    );
                    $actionsExecuted++;
                }
            }
        }

        return $actionsExecuted;
    }

    /**
     * Build recycler fleet sized to collect expected debris field
     *
     * @param array $pData Planet row
     * @param float $expectedDebris Total debris (metal + crystal)
     * @return array [shipId => count]
     */
    public function buildRecyclerFleet(array $pData, $expectedDebris)
    {
        global $resource;

        $recs219 = isset($pData[$resource[219]]) ? (int)$pData[$resource[219]] : 0;
        $recs209 = isset($pData[$resource[209]]) ? (int)$pData[$resource[209]] : 0;

        $cap219 = 200000000.0; // 200M capacity for Giga Recycler (ID 219)
        $cap209 = 20000.0;     // 20k capacity for standard Recycler (ID 209)

        $recyclerFleet = array();
        $remDebris = (float)$expectedDebris;

        // If Giga Recyclers (219) are available and can carry at least the expected debris (or no 209 exist), use ONLY 219
        $gigaTotalCap = $recs219 * $cap219;
        if ($recs219 > 0 && ($gigaTotalCap >= $remDebris || $recs209 <= 0)) {
            $take219 = min($recs219, (int)ceil($remDebris / $cap219));
            if ($take219 > 0) {
                $recyclerFleet[219] = $take219;
            }
            return $recyclerFleet;
        }

        if ($recs219 > 0) {
            $take219 = min($recs219, (int)ceil($remDebris / $cap219));
            $recyclerFleet[219] = $take219;
            $remDebris -= ($take219 * $cap219);
        }

        if ($remDebris > 0 && $recs209 > 0) {
            $take209 = min($recs209, (int)ceil($remDebris / $cap209));
            $recyclerFleet[209] = $take209;
        }

        return $recyclerFleet;
    }

    /**
     * Finds the nearest colony belonging to the bot that possesses available recyclers.
     *
     * @param BotEmpireState $empire
     * @param array $tCoords Target coordinates [galaxy, system, planet]
     * @param float $expectedDebris
     * @param int $excludeId
     * @return array|null
     */
    public function findNearestColonyWithRecyclers(BotEmpireState $empire, array $tCoords, $expectedDebris, $excludeId = 0)
    {
        global $resource;
        $allPlanets = $empire->getPlanets();
        if (empty($allPlanets)) return null;

        $bestColony = null;
        $minDist = PHP_INT_MAX;

        foreach ($allPlanets as $pId => $pData) {
            if ($pId == $excludeId) continue;

            $recs219 = isset($pData[$resource[219]]) ? (int)$pData[$resource[219]] : 0;
            $recs209 = isset($pData[$resource[209]]) ? (int)$pData[$resource[209]] : 0;
            if ($recs219 <= 0 && $recs209 <= 0) continue;

            $fleet = $this->buildRecyclerFleet($pData, $expectedDebris);
            if (empty($fleet)) continue;

            $pCoords = array((int)$pData['galaxy'], (int)$pData['system'], (int)$pData['planet']);
            $dist = FleetFunctions::GetTargetDistance($pCoords, $tCoords);

            if ($dist < $minDist) {
                $minDist = $dist;
                $bestColony = array(
                    'planet_id'   => $pId,
                    'planet_data' => $pData,
                    'fleet'       => $fleet,
                    'distance'    => $dist,
                    'coords'      => $pCoords,
                );
            }
        }

        return $bestColony;
    }

    /**
     * Schedule or launch defensive recyclers to arrive at T_impact + offsetDef
     *
     * @param BotWorldModel $world
     * @param array $targetPlanetData
     * @param int $tImpact Unix timestamp of impact
     * @param float $expectedDebris Total expected debris
     * @param int $offsetDef Seconds after impact to arrive (Easy=30, Normal=20, Hard=10, Nightmare=6)
     * @return bool
     */
    public function scheduleOrLaunchDefenseRecyclers(BotWorldModel $world, array $targetPlanetData, $tImpact, $expectedDebris, $offsetDef)
    {
        $targetPlanetId = isset($targetPlanetData['id']) ? (int)$targetPlanetData['id'] : (int)$targetPlanetData['planet_id'];
        $tGalaxy = (int)$targetPlanetData['galaxy'];
        $tSystem = (int)$targetPlanetData['system'];
        $tPlanet = (int)$targetPlanetData['planet'];
        $tCoords = array($tGalaxy, $tSystem, $tPlanet);

        // 1. Prevent duplicate recycling missions unless subsequent wave produces higher debris
        $db = Database::get();
        $pendingTasks = $db->select("SELECT task_id, payload FROM %%BOT_TASKS%% WHERE bot_id = :bid AND task_type = 'staggered_defense_recycle' AND status = 'pending';", array(':bid' => (int)$this->ctx->botId));
        foreach ($pendingTasks as $pt) {
            $pl = json_decode($pt['payload'], true);
            if (isset($pl['target_planet_id']) && (int)$pl['target_planet_id'] === $targetPlanetId) {
                $prevDebris = isset($pl['expected_debris']) ? (float)$pl['expected_debris'] : 0.0;
                if ($expectedDebris > $prevDebris) {
                    // Subsequent wave generates larger debris; cancel smaller pending task to reschedule with upgraded fleet
                    BotScheduler::markTaskComplete((int)$pt['task_id']);
                    break;
                } else {
                    return false; // Already covered by equal or larger pending recycling fleet
                }
            }
        }

        $activeRecs = $db->selectSingle("SELECT COUNT(*) as cnt FROM %%FLEETS%% WHERE fleet_owner = :bid AND fleet_mission = 8 AND fleet_end_galaxy = :g AND fleet_end_system = :s AND fleet_end_planet = :p AND fleet_end_type = 2 AND fleet_mess = 0;", array(
            ':bid' => (int)$this->ctx->botId,
            ':g'   => $tGalaxy,
            ':s'   => $tSystem,
            ':p'   => $tPlanet,
        ));
        if (!empty($activeRecs['cnt']) && (int)$activeRecs['cnt'] > 0 && $expectedDebris < 100000000.0) {
            return false;
        }

        // 2. Locate recyclers (target planet first, then nearest colony)
        $originId = $targetPlanetId;
        $originData = $targetPlanetData;
        $recyclerFleet = $this->buildRecyclerFleet($targetPlanetData, $expectedDebris);

        if (empty($recyclerFleet)) {
            $nearest = $this->findNearestColonyWithRecyclers($world->empire, $tCoords, $expectedDebris, $targetPlanetId);
            if ($nearest) {
                $originId = (int)$nearest['planet_id'];
                $originData = $nearest['planet_data'];
                $recyclerFleet = $nearest['fleet'];
            }
        }

        if (empty($recyclerFleet)) {
            return false;
        }

        // 3. Calculate flight duration and launch timing
        $originCoords = array((int)$originData['galaxy'], (int)$originData['system'], (int)$originData['planet']);
        $fDist = FleetFunctions::GetTargetDistance($originCoords, $tCoords);
        $recSpeed = FleetFunctions::GetFleetMaxSpeed($recyclerFleet, $this->ctx->user);
        $gameSpeed = (float)FleetFunctions::GetGameSpeedFactor();
        $dRec = FleetFunctions::GetMissionDuration(10, $recSpeed, $fDist, $gameSpeed, $this->ctx->user);

        $tTargetRec = $tImpact + (int)$offsetDef;
        $tLaunch = $tTargetRec - $dRec;
        $now = TIMESTAMP;

        if ($tLaunch <= $now) {
            $ok = $this->gateway->launchFleet(
                $recyclerFleet,
                8, // Mission 8: Recycle
                $originId,
                $targetPlanetId,
                0,
                $tGalaxy,
                $tSystem,
                $tPlanet,
                2, // Debris
                array(),
                10 // 100% speed
            );

            if ($ok) {
                $this->ctx->decisionLog->record(
                    'defense_recycler_dispatched',
                    "Dispatched " . array_sum($recyclerFleet) . " recyclers immediately from planet #{$originId} to defense debris at [{$tGalaxy}:{$tSystem}:{$tPlanet}] (ETA: {$dRec}s | Target: Impact + {$offsetDef}s)",
                    array('origin_id' => $originId, 'target_planet_id' => $targetPlanetId, 'fleet' => $recyclerFleet, 'duration' => $dRec, 'offset' => $offsetDef),
                    "def_recycle_{$tGalaxy}_{$tSystem}_{$tPlanet}",
                    4500.0
                );
                return true;
            }
            return false;
        } else {
            $delay = $tLaunch - $now;
            if ($delay <= 2) {
                if ($delay > 0) {
                    sleep($delay);
                }
                $ok = $this->gateway->launchFleet(
                    $recyclerFleet,
                    8,
                    $originId,
                    $targetPlanetId,
                    0,
                    $tGalaxy,
                    $tSystem,
                    $tPlanet,
                    2,
                    array(),
                    10
                );
                if ($ok) {
                    $this->ctx->decisionLog->record(
                        'defense_recycler_dispatched',
                        "Dispatched " . array_sum($recyclerFleet) . " recyclers from planet #{$originId} to defense debris at [{$tGalaxy}:{$tSystem}:{$tPlanet}] after {$delay}s sleep (Target: Impact + {$offsetDef}s)",
                        array('origin_id' => $originId, 'target_planet_id' => $targetPlanetId, 'fleet' => $recyclerFleet, 'duration' => $dRec, 'offset' => $offsetDef),
                        "def_recycle_{$tGalaxy}_{$tSystem}_{$tPlanet}",
                        4500.0
                    );
                    return true;
                }
                return false;
            }

            BotScheduler::scheduleTask(
                $this->ctx->botId,
                'staggered_defense_recycle',
                array(
                    'fleet'            => $recyclerFleet,
                    'fob_id'           => $originId,
                    'target_planet_id' => $targetPlanetId,
                    'target_galaxy'    => $tGalaxy,
                    'target_system'    => $tSystem,
                    'target_planet'    => $tPlanet,
                    'speed'            => 10,
                    'expected_debris'  => $expectedDebris,
                ),
                $tLaunch
            );

            $this->ctx->decisionLog->record(
                'defense_recycler_scheduled',
                "Scheduled defensive recycler dispatch of " . array_sum($recyclerFleet) . " ships from planet #{$originId} for T+{$delay}s to arrive at [{$tGalaxy}:{$tSystem}:{$tPlanet}] at Impact + {$offsetDef}s",
                array('origin_id' => $originId, 'target_planet_id' => $targetPlanetId, 'fleet' => $recyclerFleet, 'delay' => $delay, 'offset' => $offsetDef, 't_launch' => $tLaunch),
                "sched_def_recycle_{$tGalaxy}_{$tSystem}_{$tPlanet}",
                4500.0
            );
            return true;
        }
    }
}

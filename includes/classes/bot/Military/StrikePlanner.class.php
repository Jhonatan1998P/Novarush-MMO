<?php

require_once 'includes/classes/bot/Evaluation/CombatOracle.class.php';
require_once 'includes/classes/bot/Evaluation/EconomyValuator.class.php';
require_once 'includes/classes/bot/Military/TargetBlacklist.class.php';

/**
 * NovaRush Bot AI v2 - Strike Planner (Advanced Architecture & Redesign)
 *
 * Implements:
 * 0. Core Principle: Bot optimizes Value = EV_net / roundtrip_hours of occupied fleet.
 * 1. Missile Engine (MIP) - Fix Principal:
 *    - 1.1 Global IPM_requerido across all in-range silos. If total stock < required, WAIT and accumulate.
 *    - 1.2 Correct Damage Model: hull = baseStructure * (1 + armorTech*0.1), shield = baseShield * (1 + shieldTech*0.1),
 *          ipmDmg = 12000 * (1 + weaponTech*0.1). Rebound check (ipmDmg <= shield -> immune). Shots needed.
 *    - 1.3 Smart Prioritization: priority(u) = firepower_contrib(u) / shotsNeeded(u). Top priority as primary target.
 *    - 1.4 Single Salvo Calculation: abmMargin = (spy_tech >= 8 ? 0 : ceil(0.15 * ABM)). Margin ONLY on ABM (1:1).
 *    - 1.5 Pooling all silos in range (silo >= 4, mip > 0, range = max((impulse*5)-1, 0)). Ascending flight time greedy fill. Reserves preserved.
 *    - 1.6 Impact alignment (staggered launches) & guard in strike_in_progress.
 * 2. Advanced Targeting:
 *    - 2.1 Net EV per hour: EV(t) = P(win)*(loot + debris + valEstrategico) - (1-P(win))*riskFleet - fuel - mips. Valor = EV / roundtrip_hours.
 *    - 2.2 Projected Loot at impact: min(storageCap, spied + prod * deltaT) * 0.5. Heavy penalty for recent activity ('*').
 *    - 2.3 Parallel Architecture: 1 locked Campaign target (siege/fleet_crash) + N parallel Farm Sweeps (no lock) with regeneration cooldown.
 *    - 2.4 Anti-traps & anti-baits: discard active players, phalanx moons, unprofitable turtles.
 *    - 2.5 Pre-impact confirmation: tactical re-espionage & recall if ninja or anomaly detected.
 *    - 2.6 Hysteresis & Anti-sunk-cost: switch target if Valor(current) < Valor(#2) - 20%. Abandon siege if spent > ROI_floor * expectedLoot or cycles >= 20.
 * 3. Fleet Composition by Role:
 *    - Siege: Bombers (211) + Destructors (213) + heavy capital ships.
 *    - Fleet Crash: Battlecruisers (215) + Destructors (213) + Light Fighters (204) as screen where marginal survival value > cost.
 *    - Farming: Cargos sized exactly for loot + minimal light escort.
 *    - Synchronized Recyclers: ceil(debris / cap) dispatched synchronized with combat.
 */
class BotStrikePlanner
{
    private $ctx;
    private $gateway;

    public function __construct(BotContext $ctx, BotActionGateway $gateway)
    {
        $this->ctx = $ctx;
        $this->gateway = $gateway;
    }

    /**
     * Plan and dispatch strike missions against vetted targets
     *
     * @param BotWorldModel $world
     * @param array $basePlanet
     * @param array $targetCandidates
     * @return int Number of strikes launched
     */
    public function planStrikes(BotWorldModel $world, array $basePlanet, array $targetCandidates)
    {
        global $resource, $reslist, $pricelist, $CombatCaps;

        $db = Database::get();

        // 0. PRE-IMPACT VERIFICATION: Check in-flight fleets for ninja/anomalies (Section 2.5)
        $this->verifyInFlightStrikes($world);

        // 1. EVALUATE ACTIVE TARGET LOCK (Campaign)
        $lockedGalaxy = isset($this->ctx->botRow['target_galaxy']) ? (int)$this->ctx->botRow['target_galaxy'] : 0;
        $lockedSystem = isset($this->ctx->botRow['target_system']) ? (int)$this->ctx->botRow['target_system'] : 0;
        $lockedPlanet = isset($this->ctx->botRow['target_planet']) ? (int)$this->ctx->botRow['target_planet'] : 0;
        $lockedType   = isset($this->ctx->botRow['target_type']) ? $this->ctx->botRow['target_type'] : '';
        $initialMse   = isset($this->ctx->botRow['target_initial_mse']) ? (float)$this->ctx->botRow['target_initial_mse'] : 0.0;
        $siegeCycles  = isset($this->ctx->botRow['siege_cycles']) ? (int)$this->ctx->botRow['siege_cycles'] : 0;
        $fobId        = isset($this->ctx->botRow['fob_planet_id']) ? (int)$this->ctx->botRow['fob_planet_id'] : 0;
        $spentSiege   = isset($this->ctx->botRow['siege_spent_mse']) ? (float)$this->ctx->botRow['siege_spent_mse'] : 0.0;

        $targetLocked = false;
        $activeTarget = null;

        if ($lockedGalaxy > 0 && $lockedSystem > 0 && $lockedPlanet > 0) {
            // Validate target in database
            $pRow = $db->selectSingle("SELECT p.id, p.id_owner, p.galaxy, p.system, p.planet, p.planet_type, u.urlaubs_modus, u.authlevel 
                FROM %%PLANETS%% p 
                INNER JOIN %%USERS%% u ON u.id = p.id_owner 
                WHERE p.galaxy = :g AND p.system = :s AND p.planet = :p AND p.planet_type = 1 LIMIT 1;", array(
                ':g' => $lockedGalaxy,
                ':s' => $lockedSystem,
                ':p' => $lockedPlanet,
            ));

            if (empty($pRow) || $pRow['id_owner'] == $this->ctx->botId) {
                $this->releaseLock("Planeta objetivo ya no existe o pertenece al imperio del bot", "{$lockedGalaxy}:{$lockedSystem}:{$lockedPlanet}");
            } elseif ((int)$pRow['id_owner'] === 1 || (!empty($pRow['authlevel']) && (int)$pRow['authlevel'] > 0)) {
                $this->releaseLock("Objetivo es cuenta de administrador protegida", "{$lockedGalaxy}:{$lockedSystem}:{$lockedPlanet}");
            } elseif (!empty($pRow['urlaubs_modus'])) {
                $this->releaseLock("El jugador defensor activó Modo Vacaciones", "{$lockedGalaxy}:{$lockedSystem}:{$lockedPlanet}");
            } elseif (BotTargetBlacklist::isBlacklisted($this->ctx->botId, (int)$pRow['id_owner'], (int)$pRow['id'], $lockedGalaxy, $lockedSystem, $lockedPlanet)) {
                $this->releaseLock("Objetivo en lista negra de 24 horas tras alcanzar límite de 3 ataques por jugador", "{$lockedGalaxy}:{$lockedSystem}:{$lockedPlanet}");
            } else {
                // Fetch latest intel
                $intel = $world->intel->getIntel($lockedGalaxy, $lockedSystem, $lockedPlanet, 1, 4.0);

                $release = false;
                $releaseReason = '';

                // Section 2.6: Anti-Sunk-Cost & Horizon Limits
                if ($lockedType === 'siege') {
                    if ($siegeCycles >= 20) {
                        $release = true;
                        $releaseReason = "Límite de horizonte de asedio alcanzado (20 ciclos sin brecha defensiva)";
                    } elseif ($spentSiege > 0 && $initialMse > 0 && $spentSiege > (1.2 * $initialMse)) {
                        $release = true;
                        $releaseReason = "Anti-Sunk-Cost: Gasto acumulado (" . number_format($spentSiege) . " MSE) superó el límite de rentabilidad ROI sobre el botín esperado (" . number_format($initialMse) . " MSE)";
                    }
                }

                // Check loss-of-value conditions
                if (!$release && $intel !== null && $intel['confidence'] >= 0.4) {
                    if ($lockedType === 'fleet_crash') {
                        $currentFleetMSE = 0.0;
                        if (!empty($intel['fleet']) && is_array($intel['fleet'])) {
                            foreach ($intel['fleet'] as $sId => $cnt) {
                                if ($sId == 212 || $cnt <= 0) continue;
                                $currentFleetMSE += BotEconomyValuator::getElementPriceMSE($sId, $cnt);
                            }
                        }
                        if ($currentFleetMSE <= 0 || ($initialMse > 0 && $currentFleetMSE < ($initialMse * 0.15))) {
                            $release = true;
                            $releaseReason = "Flota enemiga en tierra retirada o destruida (MSE actual: " . number_format($currentFleetMSE) . ")";
                        }
                    } elseif ($lockedType === 'farming') {
                        $lootMetal = (float)($intel['metal'] ?? 0) * 0.50;
                        $lootCry   = (float)($intel['crystal'] ?? 0) * 0.50;
                        $lootDeut  = (float)($intel['deuterium'] ?? 0) * 0.50;
                        $currentLootMSE = BotEconomyValuator::toMSE($lootMetal, $lootCry, $lootDeut);

                        if ($initialMse > 0 && $currentLootMSE < ($initialMse * 0.40)) {
                            $release = true;
                            $releaseReason = "Botín saqueado o reducido en más de un 60% (MSE actual: " . number_format($currentLootMSE) . ")";
                        }
                    }
                }

                // Section 2.6: Hysteresis Check against best alternative candidate
                if (!$release && is_array($targetCandidates) && !empty($targetCandidates)) {
                    $currentValor = 0.0;
                    if ($intel !== null) {
                        $currentLoot = BotEconomyValuator::toMSE((float)($intel['metal']??0)*0.5, (float)($intel['crystal']??0)*0.5, (float)($intel['deuterium']??0)*0.5);
                        $cfg = Config::get();
                        $fDebrisFactor = ((float)$cfg->Fleet_Cdr) / 100.0;
                        $dDebrisFactor = ((float)$cfg->Defs_Cdr) / 100.0;
                        $currentDebris = 0.0;
                        if (!empty($intel['fleet']) && is_array($intel['fleet'])) {
                            foreach ($intel['fleet'] as $sId => $cnt) {
                                if ($sId == 212 || $cnt <= 0) continue;
                                $currentDebris += BotEconomyValuator::getElementPriceMSE($sId, $cnt) * $fDebrisFactor;
                            }
                        }
                        if ($dDebrisFactor > 0 && !empty($intel['defense']) && is_array($intel['defense'])) {
                            foreach ($intel['defense'] as $dId => $cnt) {
                                if ($dId == 502 || $cnt <= 0) continue;
                                $currentDebris += BotEconomyValuator::getElementPriceMSE($dId, $cnt) * $dDebrisFactor;
                            }
                        }
                        // Real roundtrip calculation to locked target
                        $fobBody = $world->empire->getPlanet($fobId);
                        $fobCoords = !empty($fobBody) ? array($fobBody['galaxy'], $fobBody['system'], $fobBody['planet']) : array($lockedGalaxy, $lockedSystem, $lockedPlanet);
                        $curDist = FleetFunctions::GetTargetDistance($fobCoords, array($lockedGalaxy, $lockedSystem, $lockedPlanet));
                        $curFlightSecs = FleetFunctions::GetMissionDuration(10, 15000, $curDist, FleetFunctions::GetGameSpeedFactor(), $this->ctx->user);
                        $curRoundtripHours = max(0.05, (2.0 * $curFlightSecs) / 3600.0);
                        $currentValor = ($currentLoot + $currentDebris) / $curRoundtripHours;
                    }

                    // Look at top alternative campaign candidate (different coordinates)
                    foreach ($targetCandidates as $altCand) {
                        $altT = $altCand['target'];
                        if ($altT['galaxy'] == $lockedGalaxy && $altT['system'] == $lockedSystem && $altT['planet'] == $lockedPlanet) {
                            continue;
                        }
                        if (in_array($altCand['category'], array('fleet_crash', 'siege'))) {
                            $altValor = isset($altCand['valor_hora']) ? (float)$altCand['valor_hora'] : (float)($altCand['score'] ?? 0);
                            $hysteresisBand = $altValor * 0.20; // 20% hysteresis band
                            if ($currentValor > 0 && $currentValor < ($altValor - $hysteresisBand)) {
                                $release = true;
                                $releaseReason = "Histéresis: Objetivo alternativo en [{$altT['galaxy']}:{$altT['system']}:{$altT['planet']}] supera al actual por más del 20% (Valor actual: " . number_format($currentValor) . " vs #2: " . number_format($altValor) . ")";
                                break;
                            }
                        }
                    }
                }

                if ($release) {
                    $this->releaseLock($releaseReason, "{$lockedGalaxy}:{$lockedSystem}:{$lockedPlanet}");
                } else {
                    $targetLocked = true;
                    $activeTarget = array(
                        'target'       => array(
                            'planet_id'   => (int)$pRow['id'],
                            'owner_id'    => (int)$pRow['id_owner'],
                            'galaxy'      => $lockedGalaxy,
                            'system'      => $lockedSystem,
                            'planet'      => $lockedPlanet,
                            'planet_type' => 1,
                        ),
                        'category'     => $lockedType,
                        'initial_mse'  => $initialMse,
                        'siege_cycles' => $siegeCycles,
                        'fob_id'       => $fobId,
                    );
                }
            }
        }

        // 2. IF NO LOCKED TARGET, LOCK ONTO BEST QUALIFIED CAMPAIGN CANDIDATE
        // Section 2.3: Only 'fleet_crash' and 'siege' are locked as Campaign targets!
        // Farming targets are handled in parallel sweeps without target lock.
        if (!$targetLocked && is_array($targetCandidates) && !empty($targetCandidates)) {
            foreach ($targetCandidates as $cand) {
                $cat = $cand['category'];
                if (in_array($cat, array('fleet_crash', 'siege', 'farming'))) {
                    $t = $cand['target'];
                    if ((int)$t['owner_id'] === 1) {
                        continue;
                    }
                    $tG = (int)$t['galaxy'];
                    $tS = (int)$t['system'];
                    $tP = (int)$t['planet'];
                    $tId = isset($t['planet_id']) ? (int)$t['planet_id'] : (int)($t['id'] ?? 0);
                    $ownerId = (int)$t['owner_id'];

                    if (BotTargetBlacklist::isBlacklisted($this->ctx->botId, $ownerId, $tId, $tG, $tS, $tP)) {
                        continue;
                    }

                    $bestFobId = !empty($cand['nearest_colony_id']) ? (int)$cand['nearest_colony_id'] : $this->determineFobPlanetId($world->empire, $tG, $tS);

                    $initMse = 0.0;
                    if ($cat === 'fleet_crash') {
                        $cfg = Config::get();
                        $fCdr = max(0.01, ((float)$cfg->Fleet_Cdr) / 100.0);
                        $initMse = $cand['debris_mse'] / $fCdr;
                    } elseif ($cat === 'siege' || $cat === 'farming') {
                        $initMse = $cand['loot_mse'] + $cand['debris_mse'];
                    }

                    $this->ctx->updateBotRow(array(
                        'target_galaxy'      => $tG,
                        'target_system'      => $tS,
                        'target_planet'      => $tP,
                        'target_type'        => $cat,
                        'target_initial_mse' => (float)$initMse,
                        'target_lock_time'   => TIMESTAMP,
                        'siege_cycles'       => 0,
                        'fob_planet_id'      => $bestFobId,
                        'siege_spent_mse'    => 0.0,
                    ));

                    $this->ctx->decisionLog->record(
                        'target_locked',
                        "Campaign target locked at [{$tG}:{$tS}:{$tP}] classified as {$cat} (Initial Value: " . number_format($initMse) . " MSE, EV/h: " . number_format($cand['valor_hora'] ?? 0) . "). Designated FOB colony #{$bestFobId}",
                        array('galaxy' => $tG, 'system' => $tS, 'planet' => $tP, 'type' => $cat, 'mse' => $initMse, 'fob_id' => $bestFobId),
                        "lock_{$tG}_{$tS}_{$tP}",
                        5000.0
                    );

                    $targetLocked = true;
                    $activeTarget = array(
                        'target'       => $t,
                        'category'     => $cat,
                        'initial_mse'  => $initMse,
                        'siege_cycles' => 0,
                        'fob_id'       => $bestFobId,
                    );
                    break;
                }
            }
        }

        $campaignStrikes = 0;

        // 3. EXECUTE MISSION AGAINST LOCKED CAMPAIGN TARGET
        if ($targetLocked && !empty($activeTarget)) {
            $t = $activeTarget['target'];
            $tId = isset($t['planet_id']) ? (int)$t['planet_id'] : (int)($t['id'] ?? 0);
            $ownerId = (int)$t['owner_id'];
            if ((int)$t['owner_id'] === 1) {
                $this->releaseLock("Administrador protegido de ataques de IA", "{$t['galaxy']}:{$t['system']}:{$t['planet']}");
            } elseif (BotTargetBlacklist::isBlacklisted($this->ctx->botId, $ownerId, $tId, (int)$t['galaxy'], (int)$t['system'], (int)$t['planet'])) {
                $this->releaseLock("Objetivo en lista negra de 24 horas tras alcanzar límite de 3 ataques por jugador", "{$t['galaxy']}:{$t['system']}:{$t['planet']}");
            } else {
                $cat = $activeTarget['category'];
                $fobId = !empty($activeTarget['fob_id']) ? $activeTarget['fob_id'] : $basePlanet['id'];

                $fobBody = $world->empire->getPlanet($fobId);
                if (empty($fobBody)) {
                    $fobBody = $basePlanet;
                    $fobId = (int)$fobBody['id'];
                }

                $fobData = $fobBody['data'];

                // Gather available combat fleet on FOB
                $availableFleet = array();
                $fleetList = (isset($reslist['fleet']) && is_array($reslist['fleet'])) ? $reslist['fleet'] : array();
                foreach ($fleetList as $shipId) {
                    if ($shipId == 210 || $shipId == 212 || in_array($shipId, array(208, 209, 219))) continue;
                    $qty = isset($fobData[$resource[$shipId]]) ? (int)$fobData[$resource[$shipId]] : 0;
                    if ($qty > 0) {
                        $availableFleet[$shipId] = $qty;
                    }
                }

                // Fetch target intel
                $intel = $world->intel->getIntel($t['galaxy'], $t['system'], $t['planet'], 1, 4.0);
                $targetDefense = (isset($intel['defense']) && is_array($intel['defense'])) ? $intel['defense'] : array();
                $targetFleet   = (isset($intel['fleet']) && is_array($intel['fleet'])) ? $intel['fleet'] : array();
                $targetDefenders = $targetDefense + $targetFleet;

                // Guard: Active strike or missiles in transit (Section 1.6 Guard)
                $activeStrikes = $db->selectSingle("SELECT COUNT(*) as cnt, SUM(fleet_amount) as total_ships FROM %%FLEETS%% 
                    WHERE fleet_owner = :botId 
                      AND fleet_end_galaxy = :g 
                      AND fleet_end_system = :s 
                      AND fleet_end_planet = :p 
                      AND fleet_mission IN (1, 8, 10) 
                      AND fleet_mess = 0;", array(
                    ':botId' => $this->ctx->botId,
                    ':g'     => $t['galaxy'],
                    ':s'     => $t['system'],
                    ':p'     => $t['planet']
                ));

                // Also check if there are pending scheduled staggered missile, fleet or recycler tasks for this target
                $hasScheduledSalvo = BotScheduler::hasPendingTask($this->ctx->botId, 'staggered_mip_salvo')
                                  || BotScheduler::hasPendingTask($this->ctx->botId, 'staggered_fleet_assault')
                                  || BotScheduler::hasPendingTask($this->ctx->botId, 'staggered_recycler_dispatch');

                $hasAssaultInFlight = (!empty($activeStrikes) && (int)$activeStrikes['cnt'] > 0) || $hasScheduledSalvo;
                if ($hasAssaultInFlight) {
                    $this->ctx->decisionLog->record(
                        'strike_in_progress',
                        "Operación táctica en curso (" . number_format((int)($activeStrikes['total_ships'] ?? 0)) . " naves/misiles en vuelo/programados) hacia [{$t['galaxy']}:{$t['system']}:{$t['planet']}]. Esperando sincronización e impacto.",
                        array('target' => $t, 'ships' => (int)($activeStrikes['total_ships'] ?? 0)),
                        "inflight_{$t['galaxy']}_{$t['system']}_{$t['planet']}",
                        5000.0
                    );
                } else {
                    // --- BRANCH A: TACTICAL MULTI-STEP SIEGE (Sections 1.1 - 1.6) ---
                    if ($cat === 'siege') {
                        $campaignStrikes += $this->executeSiegeCampaign($world, $t, $activeTarget, $targetDefense, $targetDefenders, $intel, $fobData, $fobId);
                    }
                    // --- BRANCH B: FLEET CRASH (Sections 2 & 3) ---
                    elseif ($cat === 'fleet_crash') {
                        $campaignStrikes += $this->executeFleetCrashCampaign($world, $t, $activeTarget, $targetDefense, $targetFleet, $targetDefenders, $intel, $fobData, $fobId, $availableFleet);
                    }
                    // --- BRANCH C: FOCUSED HIGH-VALUE FARMING CAMPAIGN ---
                    elseif ($cat === 'farming') {
                        $campaignStrikes += $this->executeFarmingCampaign($world, $t, $activeTarget, $targetDefense, $targetDefenders, $intel, $fobData, $fobId);
                    }
                }
            }
        }

        // 4. PARALLEL ARCHITECTURE: EXECUTE FARMING SWEEPS IN PARALLEL (Section 2.3)
        // Dispatches independent farming squadrons to inactive farm targets without target lock!
        $farmStrikes = $this->executeParallelFarming($world, $basePlanet, $targetCandidates);

        return $campaignStrikes + $farmStrikes;
    }

    /**
     * Section 1: Executes tactical siege with the correct damage model and global MIP pooling
     */
    private function executeSiegeCampaign(
        BotWorldModel $world,
        array $t,
        array $activeTarget,
        array $targetDefense,
        array $targetDefenders,
        $intel,
        array $fobData,
        $fobId
    ) {
        $currentSiege = (int)$activeTarget['siege_cycles'] + 1;
        $this->ctx->updateBotRow(array('siege_cycles' => $currentSiege));

        // 1. Calculate Comprehensive Missile Model (Sections 1.1 - 1.5)
        $mModel = $this->calculateMissileModel($world, $t, $targetDefense, $intel);

        $ipmRequerido = $mModel['ipm_requerido'];
        $totalStock   = $mModel['total_stock'];
        $canFire      = $mModel['can_fire'];
        $dispatches   = $mModel['dispatches'];
        $primaryTarget= $mModel['primary_target'];
        $immuneDefs   = $mModel['immune_defenses'];

        // Section 1.2: Log immune defenses marked as "neutralizar con flota"
        if (!empty($immuneDefs)) {
            $immuneList = array();
            global $resource;
            foreach ($immuneDefs as $uId => $uCnt) {
                $uName = isset($resource[$uId]) ? $resource[$uId] : "Unidad #{$uId}";
                $immuneList[] = "{$uCnt}x {$uName}";
            }
            $this->ctx->decisionLog->record(
                'siege_immune_defenses_detected',
                "Rebote detectado: defensas inmunes a MIPs (ipmDmg <= shield): [" . implode(', ', $immuneList) . "]. Marcadas como 'neutralizar con flota', misiles preservados.",
                array('target' => $t, 'immune' => $immuneDefs),
                "immune_{$t['galaxy']}_{$t['system']}_{$t['planet']}",
                1500.0
            );
        }

        $lootNeeded = array(
            'metal'     => isset($intel['metal']) ? (float)$intel['metal'] * 0.50 : 0.0,
            'crystal'   => isset($intel['crystal']) ? (float)$intel['crystal'] * 0.50 : 0.0,
            'deuterium' => isset($intel['deuterium']) ? (float)$intel['deuterium'] * 0.50 : 0.0
        );

        if ($ipmRequerido > 0) {
            // Section 1.1: Global Stock Check & Direct Assault Bypass
            if (!$canFire) {
                // BYPASS EVALUATION: Si no hay misiles suficientes pero el asalto con flota es rentable (ROI >= 30%)
                $directFleet = $this->buildTacticalStrikeFleet($fobData, $targetDefenders, $lootNeeded, 'siege');
                $canDirectAssault = false;

                if (!empty($directFleet)) {
                    $cfg = Config::get();
                    $fCdr = ((float)$cfg->Fleet_Cdr) / 100.0;
                    $dCdr = ((float)$cfg->Defs_Cdr) / 100.0;

                    $targetFactors = BotCombatOracle::resolveDefenderFactors((int)$t['owner_id'], is_array($intel) ? $intel : array());
                    $simDirect = BotCombatOracle::evaluate(
                        $directFleet,
                        $targetDefenders,
                        array('id' => $this->ctx->botId, 'factor' => $this->ctx->user['factor']),
                        array('id' => $t['owner_id'], 'factor' => $targetFactors),
                        2
                    );

                    $winProb = isset($simDirect['win_probability']) ? (float)$simDirect['win_probability'] : 0.0;

                    if ($winProb >= 0.60) {
                        $deployedFleetMSE = 0.0;
                        foreach ($directFleet as $sId => $cnt) {
                            $deployedFleetMSE += BotEconomyValuator::getElementPriceMSE($sId, $cnt);
                        }

                        $attLossPct = isset($simDirect['att_loss_pct']) ? (float)$simDirect['att_loss_pct'] : 0.20;
                        $estimatedLossMSE = isset($simDirect['att_loss_mse']) ? (float)$simDirect['att_loss_mse'] : ($deployedFleetMSE * $attLossPct);

                        $defLossPct = isset($simDirect['def_loss_pct']) ? (float)$simDirect['def_loss_pct'] : 0.90;
                        $defenderFleetMSE = 0.0;
                        if (!empty($intel['fleet']) && is_array($intel['fleet'])) {
                            foreach ($intel['fleet'] as $sId => $cnt) {
                                if ($sId == 212 || $cnt <= 0) continue;
                                $defenderFleetMSE += BotEconomyValuator::getElementPriceMSE($sId, $cnt);
                            }
                        }
                        $defenderDefMSE = 0.0;
                        if (!empty($targetDefense) && is_array($targetDefense)) {
                            foreach ($targetDefense as $dId => $dCnt) {
                                if ($dId == 502 || $dCnt <= 0) continue;
                                $defenderDefMSE += BotEconomyValuator::getElementPriceMSE($dId, $dCnt);
                            }
                        }

                        $lootMetal = (float)($intel['metal'] ?? 0) * 0.50;
                        $lootCry   = (float)($intel['crystal'] ?? 0) * 0.50;
                        $lootDeut  = (float)($intel['deuterium'] ?? 0) * 0.50;
                        $lootMSE   = BotEconomyValuator::toMSE($lootMetal, $lootCry, $lootDeut);

                        $fSpeed = FleetFunctions::GetFleetMaxSpeed($directFleet, $this->ctx->user);
                        $fCoords = array($fobData['galaxy'], $fobData['system'], $fobData['planet']);
                        $tCoords = array($t['galaxy'], $t['system'], $t['planet']);
                        $fDist  = FleetFunctions::GetTargetDistance($fCoords, $tCoords);
                        $gSpeed = FleetFunctions::GetGameSpeedFactor();
                        $fDuration = FleetFunctions::GetMissionDuration(10, $fSpeed, $fDist, $gSpeed, $this->ctx->user);
                        $fuelConsumption = FleetFunctions::GetFleetConsumption($directFleet, $fDuration, $fDist, $this->ctx->user, 10);
                        $fuelMSE = BotEconomyValuator::toMSE(0, 0, (float)$fuelConsumption);

                        if ((float)$fobData['deuterium'] >= (float)$fuelConsumption) {
                            // Costo neto: El atacante recicla el 70% de sus propias pérdidas en escombros
                            $netReplacementCost = ($estimatedLossMSE * (1.0 - $fCdr)) + $fuelMSE;
                            $grossRevenue = $lootMSE + ($defenderFleetMSE * $defLossPct * $fCdr) + ($defenderDefMSE * $defLossPct * $dCdr);
                            $netProfit = $grossRevenue - $netReplacementCost;
                            $roi = $netProfit / max(1.0, ($estimatedLossMSE + $fuelMSE));

                            // Sin límite de bajas: si ROI >= 30% y ganancia neta positiva, avanzar
                            if ($netProfit > 0 && $roi >= 0.30) {
                                $canDirectAssault = true;
                                $strikeFleet = $directFleet;
                            }
                        }
                    }
                }

                if (!$canDirectAssault) {
                    $this->ctx->decisionLog->record(
                        'siege_accumulating_mips',
                        "Tactical siege (Paso {$currentSiege}/20): Stock de misiles en silos en rango ({$totalStock} MIPs) < IPM_requerido ({$ipmRequerido} MIPs). Asalto directo no rentable (ROI < 30%). ESPERANDO acumulación en silos.",
                        array('target' => $t, 'available_mips' => $totalStock, 'required_mips' => $ipmRequerido, 'step' => $currentSiege),
                        "siege_accumulate_{$t['galaxy']}_{$t['system']}_{$t['planet']}",
                        2500.0
                    );
                    return 0;
                } else {
                    $expectedDebris = ($defenderFleetMSE * $defLossPct * $fCdr) + ($defenderDefMSE * $defLossPct * $dCdr) + ($estimatedLossMSE * $fCdr);
                    return $this->launchSynchronizedStrikeAndRecyclers(
                        $fobData,
                        $fobId,
                        $t,
                        $strikeFleet,
                        $expectedDebris,
                        (1.0 - $attLossPct),
                        'siege_direct_assault_bypass',
                        array('roi' => $roi, 'net_profit' => $netProfit)
                    );
                }
            } else {
                // Section 1.6: Pre-check slots and fuel before firing missile salvo
                $maxSlots     = FleetFunctions::GetMaxFleetSlots($this->ctx->user);
                $actualFleets = FleetFunctions::GetCurrentFleets($this->ctx->botId);
                if ($actualFleets >= $maxSlots) {
                    $this->ctx->decisionLog->record(
                        'siege_slots_saturated',
                        "Tactical siege: Slots de flota copados ({$actualFleets}/{$maxSlots}). Pausando lanzamiento de misiles para no desaprovechar munición.",
                        array('target' => $t, 'slots' => $actualFleets),
                        "siege_slots_{$t['galaxy']}_{$t['system']}_{$t['planet']}",
                        2000.0
                    );
                    return 0;
                }

                $strikeFleet = $this->buildTacticalStrikeFleet($fobData, $targetDefenders, $lootNeeded, 'siege');
                if (empty($strikeFleet)) {
                    return 0;
                }

                $fleetSpeed = FleetFunctions::GetFleetMaxSpeed($strikeFleet, $this->ctx->user);
                $fobCoords  = array($fobData['galaxy'], $fobData['system'], $fobData['planet']);
                $tCoords    = array($t['galaxy'], $t['system'], $t['planet']);
                $fDist      = FleetFunctions::GetTargetDistance($fobCoords, $tCoords);
                $gameSpeed  = FleetFunctions::GetGameSpeedFactor();
                $fleetDuration = FleetFunctions::GetMissionDuration(10, $fleetSpeed, $fDist, $gameSpeed, $this->ctx->user);
                $fuelConsumption = FleetFunctions::GetFleetConsumption($strikeFleet, $fleetDuration, $fDist, $this->ctx->user, 10);

                if ((float)$fobData['deuterium'] < (float)$fuelConsumption) {
                    $this->ctx->decisionLog->record(
                        'siege_insufficient_fuel',
                        "Tactical siege: FOB #{$fobId} carece de combustible suficiente (" . number_format($fobData['deuterium']) . " < " . number_format($fuelConsumption) . " deut). Pausando salva de misiles.",
                        array('target' => $t, 'available_deut' => $fobData['deuterium'], 'needed_deut' => $fuelConsumption),
                        "siege_fuel_{$t['galaxy']}_{$t['system']}_{$t['planet']}",
                        2000.0
                    );
                    return 0;
                }

                $maxMipDuration = 0;
                foreach ($dispatches as $d) {
                    if ($d['duration'] > $maxMipDuration) {
                        $maxMipDuration = $d['duration'];
                    }
                }

                // Desired missile impact time: 20 seconds before fleet arrival
                $selectedSpeedFactor = 10;
                if ($fleetDuration >= ($maxMipDuration + 20)) {
                    $targetImpactTime = TIMESTAMP + $fleetDuration - 20;
                    $fleetArrival = TIMESTAMP + $fleetDuration;
                    $fleetDelay = 0;
                } else {
                    $targetImpactTime = TIMESTAMP + $maxMipDuration;
                    $fleetArrival = $targetImpactTime + 20;
                    $fleetDelay = max(0, $fleetArrival - $fleetDuration - TIMESTAMP);

                    // Speed sync: Adjust speedFactor to launch immediately without relying on cron latency
                    if ($fleetDelay > 5) {
                        for ($sp = 9; $sp >= 1; $sp--) {
                            $spDur = FleetFunctions::GetMissionDuration($sp, $fleetSpeed, $fDist, $gameSpeed, $this->ctx->user);
                            $spArrival = TIMESTAMP + $spDur;
                            if ($spArrival >= ($targetImpactTime + 15) && $spArrival <= ($targetImpactTime + 35)) {
                                $selectedSpeedFactor = $sp;
                                $fleetArrival = $spArrival;
                                $fleetDelay = 0; // Launch immediately at lower speed!
                                break;
                            }
                        }
                    }
                }

                $firedImmediate = 0;
                $scheduledCount = 0;
                $totalFiredMips = 0;

                foreach ($dispatches as $disp) {
                    $dispDelay = max(0, $targetImpactTime - $disp['duration'] - TIMESTAMP);
                    $totalFiredMips += $disp['count'];

                    if ($dispDelay <= 5) {
                        $ok = $this->gateway->launchMissileStrike(
                            $disp['colony_id'],
                            $t['planet_id'],
                            $t['owner_id'],
                            $t['galaxy'],
                            $t['system'],
                            $t['planet'],
                            1,
                            $disp['count'],
                            $primaryTarget
                        );
                        if ($ok) $firedImmediate++;
                    } else {
                        BotScheduler::scheduleTask(
                            $this->ctx->botId,
                            'staggered_mip_salvo',
                            array(
                                'start_planet_id' => $disp['colony_id'],
                                'target_planet_id' => $t['planet_id'],
                                'target_owner_id' => $t['owner_id'],
                                'target_galaxy' => $t['galaxy'],
                                'target_system' => $t['system'],
                                'target_planet' => $t['planet'],
                                'count' => $disp['count'],
                                'primary_target' => $primaryTarget,
                            ),
                            TIMESTAMP + $dispDelay
                        );
                        $scheduledCount++;
                    }
                }

                // Launch coordinated siege fleet timed to arrive 20s post-missile impact
                if ($fleetDelay <= 5) {
                    $this->gateway->launchFleet(
                        $strikeFleet,
                        1, // Attack
                        $fobId,
                        $t['planet_id'],
                        $t['owner_id'],
                        $t['galaxy'],
                        $t['system'],
                        $t['planet'],
                        1,
                        array(),
                        $selectedSpeedFactor
                    );
                } else {
                    BotScheduler::scheduleTask(
                        $this->ctx->botId,
                        'staggered_fleet_assault',
                        array(
                            'fleet'            => $strikeFleet,
                            'fob_id'           => $fobId,
                            'target_planet_id' => $t['planet_id'],
                            'target_owner_id'  => $t['owner_id'],
                            'target_galaxy'    => $t['galaxy'],
                            'target_system'    => $t['system'],
                            'target_planet'    => $t['planet'],
                        ),
                        TIMESTAMP + $fleetDelay
                    );
                }

                // Track cumulative expenditure in siege_spent_mse (Section 2.6 Anti-Sunk-Cost)
                $spentSalvoMSE = $totalFiredMips * BotEconomyValuator::getElementPriceMSE(503);
                $curSpent = isset($this->ctx->botRow['siege_spent_mse']) ? (float)$this->ctx->botRow['siege_spent_mse'] : 0.0;
                $this->ctx->updateBotRow(array('siege_spent_mse' => $curSpent + $spentSalvoMSE));

                $targetName = "Unidad #{$primaryTarget}";
                global $resource;
                if (isset($resource[$primaryTarget])) $targetName = $resource[$primaryTarget];

                $this->ctx->decisionLog->record(
                    'siege_bombardment',
                    "Tactical siege: Salva coordinada única de {$totalFiredMips}x MIPs ejecutada desde " . count($dispatches) . " bases contra [{$t['galaxy']}:{$t['system']}:{$t['planet']}] apuntando a {$targetName} sincronizada con arribo de armada (" . number_format(array_sum($strikeFleet)) . " naves a velocidad {$selectedSpeedFactor}0%) a T+" . ($fleetArrival - TIMESTAMP) . "s (Inmediatos: {$firedImmediate}, Escalonados: {$scheduledCount})",
                    array('target' => $t, 'total_mips' => $totalFiredMips, 'primary' => $primaryTarget, 'step' => $currentSiege, 'spent_mse' => $spentSalvoMSE, 'fleet_ships' => array_sum($strikeFleet)),
                    "siege_salvo_{$t['galaxy']}_{$t['system']}_{$t['planet']}_{$currentSiege}",
                    5500.0
                );

                return 1;
            }
        }

        // Section 3: If no missiles needed (bunker already broken or none present), execute mop-up fleet assault
        $strikeFleet = $this->buildTacticalStrikeFleet($fobData, $targetDefenders, $lootNeeded, 'siege');
        if (!empty($strikeFleet)) {
            $targetFactors = BotCombatOracle::resolveDefenderFactors((int)$t['owner_id'], is_array($intel) ? $intel : array());
            $sim = BotCombatOracle::evaluate(
                $strikeFleet,
                $targetDefenders,
                array('id' => $this->ctx->botId, 'factor' => $this->ctx->user['factor']),
                array('id' => $t['owner_id'], 'factor' => $targetFactors),
                2
            );

            $winThreshold = 0.80;
            if (isset($sim['win_probability']) && $sim['win_probability'] >= $winThreshold) {
                $ok = $this->gateway->launchFleet(
                    $strikeFleet,
                    1, // Attack
                    $fobId,
                    $t['planet_id'],
                    $t['owner_id'],
                    $t['galaxy'],
                    $t['system'],
                    $t['planet'],
                    1,
                    array(),
                    10
                );

                if ($ok) {
                    $this->ctx->decisionLog->record(
                        'siege_final_assault',
                        "Siege final assault launched on cycle {$currentSiege} from FOB #{$fobId} against [{$t['galaxy']}:{$t['system']}:{$t['planet']}] with " . round($sim['win_probability'] * 100, 1) . "% win probability (" . number_format(array_sum($strikeFleet)) . " warships)",
                        array('target' => $t, 'fleet' => $strikeFleet, 'eval' => $sim, 'step' => $currentSiege),
                        "siege_assault_{$t['galaxy']}_{$t['system']}_{$t['planet']}",
                        8000.0
                    );

                    $this->releaseLock("Asalto definitivo completado tras romper defensas", "{$t['galaxy']}:{$t['system']}:{$t['planet']}");
                    return 1;
                }
            }
        }

        return 0;
    }

    /**
     * Section 2 & 3: Executes fleet crash campaign with screen fodder and synchronized recyclers
     */
    private function executeFleetCrashCampaign(
        BotWorldModel $world,
        array $t,
        array $activeTarget,
        array $targetDefense,
        array $targetFleet,
        array $targetDefenders,
        $intel,
        array $fobData,
        $fobId,
        array $availableFleet
    ) {
        if (empty($availableFleet)) {
            return 0;
        }

        // Fog of War Guard
        if (empty($intel['defense_scouted'])) {
            $this->ctx->decisionLog->record(
                'strike_hold_unscouted_defense',
                "Target [{$t['galaxy']}:{$t['system']}:{$t['planet']}] held: defenses are unconfirmed (Fog of War). Awaiting reconnaissance before launch.",
                array('target' => $t, 'category' => 'fleet_crash'),
                "hold_unscouted_{$t['galaxy']}_{$t['system']}_{$t['planet']}",
                500.0
            );
            return 0;
        }

        $lootNeeded = array(
            'metal'     => isset($intel['metal']) ? (float)$intel['metal'] * 0.50 : 0.0,
            'crystal'   => isset($intel['crystal']) ? (float)$intel['crystal'] * 0.50 : 0.0,
            'deuterium' => isset($intel['deuterium']) ? (float)$intel['deuterium'] * 0.50 : 0.0
        );

        $strikeFleet = $this->buildTacticalStrikeFleet($fobData, $targetDefenders, $lootNeeded, 'fleet_crash');
        if (empty($strikeFleet)) {
            return 0;
        }

        $targetFactors = BotCombatOracle::resolveDefenderFactors((int)$t['owner_id'], is_array($intel) ? $intel : array());
        $sim = BotCombatOracle::evaluate(
            $strikeFleet,
            $targetDefenders,
            array('id' => $this->ctx->botId, 'factor' => $this->ctx->user['factor']),
            array('id' => $t['owner_id'], 'factor' => $targetFactors),
            2
        );

        $winProb = isset($sim['win_probability']) ? (float)$sim['win_probability'] : 0.0;
        $winThreshold = 0.80;

        // Section 3: SYNCHRONIZED PRE-IMPACT RECYCLER & STRIKE DEPLOYMENT
        $expectedDebris = 0.0;
        if (isset($sim['debris_metal'], $sim['debris_crystal']) && ($sim['debris_metal'] + $sim['debris_crystal']) > 0) {
            $expectedDebris = (float)$sim['debris_metal'] + (float)$sim['debris_crystal'];
        } else {
            $cfg = Config::get();
            $fCdr = ((float)$cfg->Fleet_Cdr) / 100.0;
            $dCdr = ((float)$cfg->Defs_Cdr) / 100.0;
            $targetFleetMSE = 0.0;
            if ($fCdr > 0 && !empty($targetFleet) && is_array($targetFleet)) {
                foreach ($targetFleet as $fId => $cnt) {
                    if ($cnt <= 0) continue;
                    $targetFleetMSE += BotEconomyValuator::getElementPriceMSE($fId, $cnt);
                }
            }
            $targetDefMSE = 0.0;
            if ($dCdr > 0 && !empty($targetDefense) && is_array($targetDefense)) {
                foreach ($targetDefense as $dId => $cnt) {
                    if ($dId == 502 || $cnt <= 0) continue;
                    $targetDefMSE += BotEconomyValuator::getElementPriceMSE($dId, $cnt);
                }
            }
            $expectedDebris = ($targetFleetMSE * $fCdr) + ($targetDefMSE * $dCdr);
        }

        // Realistic Net Profit & Recycler Check
        $deployedFleetMSE = 0.0;
        foreach ($strikeFleet as $sId => $cnt) {
            $deployedFleetMSE += BotEconomyValuator::getElementPriceMSE($sId, $cnt);
        }
        $attLossPct = isset($sim['att_loss_pct']) ? (float)$sim['att_loss_pct'] : 0.20;
        $attLossMSE = isset($sim['att_loss_mse']) ? (float)$sim['att_loss_mse'] : ($deployedFleetMSE * $attLossPct);

        global $resource;
        $recs219 = isset($fobData[$resource[219]]) ? (int)$fobData[$resource[219]] : 0;
        $recs209 = isset($fobData[$resource[209]]) ? (int)$fobData[$resource[209]] : 0;
        $availableRecCap = ($recs219 * 500000.0) + ($recs209 * 20000.0);
        $realizableDebris = ($availableRecCap > 0) ? min($expectedDebris, $availableRecCap) : 0.0;

        $cfg = Config::get();
        $fCdr = ((float)$cfg->Fleet_Cdr) / 100.0;
        $lootMSE = BotEconomyValuator::toMSE(
            (float)($intel['metal'] ?? 0) * 0.50,
            (float)($intel['crystal'] ?? 0) * 0.50,
            (float)($intel['deuterium'] ?? 0) * 0.50
        );

        $netReplacementCost = ($attLossMSE * (1.0 - $fCdr));
        $grossRevenue = $lootMSE + $realizableDebris;
        $netProfit = $grossRevenue - $netReplacementCost;

        if ($winProb < $winThreshold || $netProfit <= 0) {
            $currentHold = isset($this->ctx->botRow['siege_cycles']) ? (int)$this->ctx->botRow['siege_cycles'] : 0;
            $newHold = $currentHold + 1;
            if ($newHold >= 6) {
                $this->releaseLock("Fuerza en FOB insuficiente o batalla no rentable (" . round($winProb * 100, 1) . "% victoria, ganancia neta: " . number_format($netProfit) . " MSE). Liberando para buscar objetivo viable", "{$t['galaxy']}:{$t['system']}:{$t['planet']}");
                return 0;
            }
            $this->ctx->updateBotRow(array('siege_cycles' => $newHold));
            return 0;
        }

        return $this->launchSynchronizedStrikeAndRecyclers(
            $fobData,
            $fobId,
            $t,
            $strikeFleet,
            $expectedDebris,
            $winProb,
            'fleet_crash',
            array('eval' => $sim, 'net_profit' => $netProfit)
        );
    }

    /**
     * Executes focused farming campaign against a high-value economic target
     */
    private function executeFarmingCampaign(
        BotWorldModel $world,
        array $t,
        array $activeTarget,
        array $targetDefense,
        array $targetDefenders,
        $intel,
        array $fobData,
        $fobId
    ) {
        $lootNeeded = array(
            'metal'     => isset($intel['metal']) ? (float)$intel['metal'] * 0.50 : 0.0,
            'crystal'   => isset($intel['crystal']) ? (float)$intel['crystal'] * 0.50 : 0.0,
            'deuterium' => isset($intel['deuterium']) ? (float)$intel['deuterium'] * 0.50 : 0.0
        );

        $strikeFleet = $this->buildTacticalStrikeFleet($fobData, $targetDefenders, $lootNeeded, 'farming');
        if (empty($strikeFleet)) {
            $strikeFleet = $this->buildTacticalStrikeFleet($fobData, $targetDefenders, $lootNeeded, 'fleet_crash');
            if (empty($strikeFleet)) {
                return 0;
            }
        }

        // Evaluate with Combat Oracle
        $targetFactors = BotCombatOracle::resolveDefenderFactors((int)$t['owner_id'], is_array($intel) ? $intel : array());
        $sim = BotCombatOracle::evaluate(
            $strikeFleet,
            $targetDefenders,
            array('id' => $this->ctx->botId, 'factor' => $this->ctx->user['factor']),
            array('id' => $t['owner_id'], 'factor' => $targetFactors),
            2
        );

        $winProb = isset($sim['win_probability']) ? (float)$sim['win_probability'] : 0.0;
        if ($winProb < 0.80) {
            $strikeFleet = $this->buildTacticalStrikeFleet($fobData, $targetDefenders, $lootNeeded, 'fleet_crash');
            $sim = BotCombatOracle::evaluate(
                $strikeFleet,
                $targetDefenders,
                array('id' => $this->ctx->botId, 'factor' => $this->ctx->user['factor']),
                array('id' => $t['owner_id'], 'factor' => $targetFactors),
                2
            );
            $winProb = isset($sim['win_probability']) ? (float)$sim['win_probability'] : 0.0;
            if ($winProb < 0.80) {
                return 0;
            }
        }

        $maxSlots     = FleetFunctions::GetMaxFleetSlots($this->ctx->user);
        $actualFleets = FleetFunctions::GetCurrentFleets($this->ctx->botId);
        if ($actualFleets >= $maxSlots) {
            return 0;
        }

        $ok = $this->gateway->launchFleet(
            $strikeFleet,
            1, // Mission 1: Attack
            $fobId,
            $t['planet_id'],
            $t['owner_id'],
            $t['galaxy'],
            $t['system'],
            $t['planet'],
            1,
            array(),
            10
        );

        if ($ok) {
            $this->ctx->decisionLog->record(
                'farming_campaign_launched',
                "Farming campaign strike dispatched from FOB #{$fobId} against [{$t['galaxy']}:{$t['system']}:{$t['planet']}] with " . number_format(array_sum($strikeFleet)) . " ships (Loot capacity focused, Win prob: " . round($winProb * 100, 1) . "%)",
                array('target' => $t, 'fleet' => $strikeFleet, 'loot_needed' => $lootNeeded),
                "farm_campaign_{$t['galaxy']}_{$t['system']}_{$t['planet']}",
                6000.0
            );
            return 1;
        }

        return 0;
    }

    /**
     * Section 1: Calculate global IPM_requerido and pooling across in-range silos
     */
    public function calculateMissileModel(BotWorldModel $world, array $target, array $targetDefense, $intel)
    {
        global $resource, $pricelist, $CombatCaps;

        $defTech = array();
        if (isset($intel['techs']) && is_array($intel['techs'])) {
            $defTech = $intel['techs'];
        } elseif (isset($intel['tech']) && is_array($intel['tech'])) {
            $defTech = $intel['tech'];
        }

        $shieldTech_def = isset($defTech[110]) ? (int)$defTech[110] : 0;
        $armorTech_def  = isset($defTech[111]) ? (int)$defTech[111] : 0;
        $weaponTech_def = isset($defTech[109]) ? (int)$defTech[109] : 0;

        $weaponTech_atk = isset($this->ctx->user['military_tech']) ? (int)$this->ctx->user['military_tech'] : 0;
        $spyTech_atk    = isset($this->ctx->user['spy_tech']) ? (int)$this->ctx->user['spy_tech'] : 0;

        // Daño por misil en el motor 2Moons (CombatCaps[503]['attack'] * (1 + 0.1 * military_tech))
        $ipmDmg = 12000.0 * (1.0 + $weaponTech_atk * 0.1);

        // En 2Moons (calculateMIPAttack.php), el motor usa shield_tech (110) como multiplicador defensivo de estructura
        $targetDefTech = max($shieldTech_def, $armorTech_def);

        $immuneDefenses = array();
        $shotsNeeded    = array();
        $priorities     = array();

        foreach ($targetDefense as $u => $count) {
            if ($u == 502 || $count <= 0) continue;

            $costM = isset($pricelist[$u]['cost'][901]) ? (float)$pricelist[$u]['cost'][901] : 0.0;
            $costC = isset($pricelist[$u]['cost'][902]) ? (float)$pricelist[$u]['cost'][902] : 0.0;

            // Fórmula exacta del motor 2Moons (calculateMIPAttack.php línea 46):
            // elementStructurePoints = (costM + costC) * (1 + 0.1 * TargetDefTech) / 10
            $elementStructurePoints = ($costM + $costC) * (1.0 + $targetDefTech * 0.1) / 10.0;

            // En 2Moons los misiles NO rebotan: aportan su poder de ataque íntegro al pool.
            // Calculamos los disparos exactos requeridos según el pool de ataque del motor:
            $shots = (int)ceil(($elementStructurePoints * $count) / max(1.0, $ipmDmg));
            $shotsNeeded[$u] = max(1, $shots);

            // Priorización inteligente por contribución de fuego a nuestras bajas
            $lethalityWeight = 1.0;
            if ($u == 406)      $lethalityWeight = 5.0; // Plasma Turret: deadliest weapon vs capital ships
            elseif ($u == 404) $lethalityWeight = 3.0; // Gauss Cannon: kinetic heavy armor penetration
            elseif ($u == 405) $lethalityWeight = 2.0; // Ion Cannon: shield disruptor
            elseif ($u == 403) $lethalityWeight = 1.0; // Heavy Laser
            elseif ($u == 401 || $u == 402) $lethalityWeight = 0.25; // Light laser / Rocket launcher: bounces off capital ships
            elseif ($u == 407 || $u == 408) $lethalityWeight = 0.0;  // Shield domes: 0 attack

            $unitAtt = isset($CombatCaps[$u]['attack']) ? (float)$CombatCaps[$u]['attack'] : 0.0;
            $firepower_contrib = $unitAtt * $lethalityWeight * (1.0 + $weaponTech_def * 0.1) * $count;
            $priorities[$u] = $firepower_contrib / max(1, $shots);
        }

        // Sort descending by priority
        arsort($priorities);
        $primaryTarget = !empty($priorities) ? key($priorities) : 401;

        // Section 1.4: Single salvo calculation + margin ONLY on ABM
        $abm = isset($targetDefense[502]) ? (int)$targetDefense[502] : 0;
        $abmMargin = ($spyTech_atk >= 8) ? 0 : (int)ceil(0.15 * $abm);
        $totalShotsDefs = array_sum($shotsNeeded);
        $ipmRequerido = $abm + $abmMargin + $totalShotsDefs;

        // Section 1.5: Pooling of all silos in range
        $impulse = isset($this->ctx->user[$resource[117]]) ? (int)$this->ctx->user[$resource[117]] : 0;
        $range   = FleetFunctions::getMissileRange($impulse);

        $allBodies = $world->empire->getAllBodies();
        $inRangeColonies = array();
        $totalStockAvailable = 0;

        foreach ($allBodies as $bId => $bBody) {
            $bData = $bBody['data'];
            if ((int)$bData['galaxy'] !== (int)$target['galaxy']) continue;
            if (abs((int)$bData['system'] - (int)$target['system']) > $range) continue;

            $silo = isset($bData[$resource[44]]) ? (int)$bData[$resource[44]] : 0;
            $mips = isset($bData[$resource[503]]) ? (int)$bData[$resource[503]] : 0;

            if ($silo >= 4 && $mips > 0) {
                $flightDuration = FleetFunctions::GetMIPDuration((int)$bData['system'], (int)$target['system']);
                $inRangeColonies[] = array(
                    'id'       => (int)$bId,
                    'system'   => (int)$bData['system'],
                    'mips'     => $mips,
                    'duration' => $flightDuration,
                );
                $totalStockAvailable += $mips;
            }
        }

        // Sort ascending by flight time
        usort($inRangeColonies, function($a, $b) {
            return $a['duration'] <=> $b['duration'];
        });

        // Greedy fill up to IPM_requerido
        $remaining = $ipmRequerido;
        $dispatches = array();
        foreach ($inRangeColonies as $col) {
            if ($remaining <= 0) break;
            $take = min($col['mips'], $remaining);
            if ($take > 0) {
                $dispatches[] = array(
                    'colony_id' => $col['id'],
                    'system'    => $col['system'],
                    'count'     => $take,
                    'duration'  => $col['duration'],
                );
                $remaining -= $take;
            }
        }

        return array(
            'ipm_requerido' => $ipmRequerido,
            'total_stock'   => $totalStockAvailable,
            'can_fire'      => ($totalStockAvailable >= $ipmRequerido && $ipmRequerido > 0),
            'primary_target'=> $primaryTarget,
            'dispatches'    => $dispatches,
            'immune_defenses'=> $immuneDefenses,
            'shots_needed'  => $shotsNeeded,
            'abm'           => $abm,
            'abm_margin'    => $abmMargin,
        );
    }

    /**
     * Section 2.3: Parallel Farm Sweeps (N targets in parallel without target lock)
     */
    public function executeParallelFarming(BotWorldModel $world, array $basePlanet, array $targetCandidates)
    {
        global $resource, $pricelist;

        $maxSlots = FleetFunctions::GetMaxFleetSlots($this->ctx->user);
        $curFleets = FleetFunctions::GetCurrentFleets($this->ctx->botId);
        $freeSlots = max(0, $maxSlots - $curFleets - 1); // Keep 1 slot buffer for fleetsave/campaign

        if ($freeSlots <= 0 || !is_array($targetCandidates) || empty($targetCandidates)) {
            return 0;
        }

        $lockedGalaxy = isset($this->ctx->botRow['target_galaxy']) ? (int)$this->ctx->botRow['target_galaxy'] : 0;
        $lockedSystem = isset($this->ctx->botRow['target_system']) ? (int)$this->ctx->botRow['target_system'] : 0;
        $lockedPlanet = isset($this->ctx->botRow['target_planet']) ? (int)$this->ctx->botRow['target_planet'] : 0;

        $db = Database::get();
        $dispatchedFarms = 0;

        foreach ($targetCandidates as $cand) {
            if ($dispatchedFarms >= $freeSlots) break;

            if ($cand['category'] !== 'farming' && ($cand['category'] !== 'recon' || empty($cand['target']['is_inactive']))) {
                continue;
            }

            $t = $cand['target'];
            // Exclude locked campaign target
            if ($t['galaxy'] == $lockedGalaxy && $t['system'] == $lockedSystem && $t['planet'] == $lockedPlanet) {
                continue;
            }

            // NovaRush Anti-Bashing: Skip blacklisted players/planets
            $tId = isset($t['planet_id']) ? (int)$t['planet_id'] : (int)($t['id'] ?? 0);
            $ownerId = (int)$t['owner_id'];
            if (BotTargetBlacklist::isBlacklisted($this->ctx->botId, $ownerId, $tId, (int)$t['galaxy'], (int)$t['system'], (int)$t['planet'])) {
                continue;
            }

            // Section 2.3: Regeneration Cooldown check (cooldown(t) = lootUmbral / produccion(t))
            $lootUmbral = 500000.0;
            $hourlyProd = isset($cand['prod_mse_hourly']) && $cand['prod_mse_hourly'] > 1000.0 ? (float)$cand['prod_mse_hourly'] : 50000.0;
            $cooldownHours = max(1.0, min(72.0, $lootUmbral / $hourlyProd));
            $cooldownSecs  = (int)round($cooldownHours * 3600.0);

            $actionKey = "farm_{$t['galaxy']}_{$t['system']}_{$t['planet']}";
            $lastRaid = $db->selectSingle("SELECT MAX(created_at) as last_time FROM " . DB_PREFIX . "bot_decisions 
                WHERE bot_id = :botId AND chosen_action = :act;", array(
                ':botId' => $this->ctx->botId,
                ':act'   => $actionKey,
            ));

            if (!empty($lastRaid['last_time'])) {
                $elapsed = TIMESTAMP - (int)$lastRaid['last_time'];
                if ($elapsed < $cooldownSecs) {
                    continue; // Skip: target resources are still regenerating under cooldown
                }
            }

            // Exclude targets that already have an in-flight fleet heading to them
            $inFlight = $db->selectSingle("SELECT COUNT(*) as cnt FROM %%FLEETS%% 
                WHERE fleet_owner = :botId AND fleet_end_galaxy = :g AND fleet_end_system = :s AND fleet_end_planet = :p AND fleet_mess = 0;", array(
                ':botId' => $this->ctx->botId, ':g' => $t['galaxy'], ':s' => $t['system'], ':p' => $t['planet']
            ));
            if (!empty($inFlight['cnt']) && (int)$inFlight['cnt'] > 0) {
                continue;
            }

            // Find origin colony with available transports
            $allColonies = $world->empire->getPlanets();
            $originColony = null;
            $minDist = PHP_INT_MAX;

            foreach ($allColonies as $cId => $cData) {
                $cM = (int)$cData['galaxy'];
                $cS = (int)$cData['system'];
                if ($cM != $t['galaxy']) continue;
                $d = abs($cS - $t['system']);
                $pData = isset($cData['data']) ? $cData['data'] : $cData;
                $cTrans = (isset($pData[$resource[202]]) ? (int)$pData[$resource[202]] : 0)
                        + (isset($pData[$resource[203]]) ? (int)$pData[$resource[203]] : 0)
                        + (isset($pData[$resource[217]]) ? (int)$pData[$resource[217]] : 0);
                if ($cTrans > 0 && $d < $minDist) {
                    $minDist = $d;
                    $originColony = $cData;
                }
            }

            if (empty($originColony)) continue;

            $lootNeeded = array(
                'metal'     => isset($cand['loot_mse']) ? (float)$cand['loot_mse'] * 0.50 : 500000.0,
                'crystal'   => 0.0,
                'deuterium' => 0.0
            );

            // Build dedicated farming squadron (Cargos + minimal light escort)
            $originColonyData = isset($originColony['data']) ? $originColony['data'] : $originColony;
            $farmFleet = $this->buildTacticalStrikeFleet($originColonyData, array(), $lootNeeded, 'farming');
            if (empty($farmFleet)) continue;

            $ok = $this->gateway->launchFleet(
                $farmFleet,
                1, // Attack
                (int)$originColony['id'],
                (int)$t['planet_id'],
                (int)$t['owner_id'],
                (int)$t['galaxy'],
                (int)$t['system'],
                (int)$t['planet'],
                1,
                array(),
                10
            );

            if ($ok) {
                $dispatchedFarms++;
                $this->ctx->decisionLog->record(
                    'farm_sweep_launched',
                    "Parallel farm sweep: dispatched " . array_sum($farmFleet) . " ships from colony #{$originColony['id']} to farm [{$t['galaxy']}:{$t['system']}:{$t['planet']}] (Estimated Loot: " . number_format($cand['loot_mse'] ?? 0) . " MSE)",
                    array('target' => $t, 'fleet' => $farmFleet, 'origin' => $originColony['id']),
                    "farm_{$t['galaxy']}_{$t['system']}_{$t['planet']}",
                    3000.0
                );
            }
        }

        return $dispatchedFarms;
    }

    /**
     * Section 2.5: Pre-impact confirmation check. Recalls in-flight fleets if ninja or anomaly detected.
     */
    public function verifyInFlightStrikes(BotWorldModel $world)
    {
        $db = Database::get();

        $inFlight = $db->select("SELECT fleet_id, fleet_end_id, fleet_end_galaxy, fleet_end_system, fleet_end_planet, fleet_start_time, fleet_array 
            FROM %%FLEETS%% 
            WHERE fleet_owner = :botId AND fleet_mission = 1 AND fleet_mess = 0;", array(
            ':botId' => $this->ctx->botId
        ));

        if (!is_array($inFlight) || empty($inFlight)) {
            return;
        }

        foreach ($inFlight as $fleet) {
            $timeToImpact = (int)$fleet['fleet_start_time'] - TIMESTAMP;
            // Pre-impact tactical window: between 30 and 300 seconds
            if ($timeToImpact >= 30 && $timeToImpact <= 300) {
                $tG = (int)$fleet['fleet_end_galaxy'];
                $tS = (int)$fleet['fleet_end_system'];
                $tP = (int)$fleet['fleet_end_planet'];

                $freshIntel = $world->intel->getIntel($tG, $tS, $tP, 1, 0.10); // 6 min freshness
                if ($freshIntel === null || empty($freshIntel['defense_scouted'])) {
                    continue;
                }

                $dispatchedShips = array();
                $rawShips = explode(';', $fleet['fleet_array']);
                foreach ($rawShips as $rs) {
                    if (empty($rs)) continue;
                    list($sId, $cnt) = explode(',', $rs);
                    $dispatchedShips[(int)$sId] = (int)$cnt;
                }

                $curDefenders = (isset($freshIntel['defense']) && is_array($freshIntel['defense']) ? $freshIntel['defense'] : array())
                              + (isset($freshIntel['fleet']) && is_array($freshIntel['fleet']) ? $freshIntel['fleet'] : array());

                $freshTargetId = isset($freshIntel['target_user_id']) ? (int)$freshIntel['target_user_id'] : 0;
                $targetFactors = BotCombatOracle::resolveDefenderFactors($freshTargetId, $freshIntel);
                $sim = BotCombatOracle::evaluate(
                    $dispatchedShips,
                    $curDefenders,
                    array('id' => $this->ctx->botId, 'factor' => $this->ctx->user['factor']),
                    array('id' => $freshTargetId, 'factor' => $targetFactors),
                    2
                );

                // Check for "Flota Retirada" anomaly (Section 2.5)
                $curFleetMSE = 0.0;
                if (!empty($freshIntel['fleet']) && is_array($freshIntel['fleet'])) {
                    foreach ($freshIntel['fleet'] as $sId => $cnt) {
                        if ($sId == 212 || $cnt <= 0) continue;
                        $curFleetMSE += BotEconomyValuator::getElementPriceMSE($sId, $cnt);
                    }
                }

                $lockedType = isset($this->ctx->botRow['target_type']) ? $this->ctx->botRow['target_type'] : '';
                $initialMse = isset($this->ctx->botRow['target_initial_mse']) ? (float)$this->ctx->botRow['target_initial_mse'] : 0.0;
                $isFleetCrash = ($lockedType === 'fleet_crash' && $tG == (int)$this->ctx->botRow['target_galaxy'] && $tS == (int)$this->ctx->botRow['target_system'] && $tP == (int)$this->ctx->botRow['target_planet']);

                if ($isFleetCrash && $initialMse > 500000.0 && $curFleetMSE < ($initialMse * 0.15)) {
                    $this->gateway->recallFleet((int)$fleet['fleet_id']);
                    $this->ctx->decisionLog->record(
                        'pre_impact_abort_fleet_withdrawn',
                        "PRE-IMPACT ABORT: Flota enemiga retirada o evacuada en destino [{$tG}:{$tS}:{$tP}] (MSE restante: " . number_format($curFleetMSE) . "). Retirando flota para evitar desperdicio de deuterio.",
                        array('fleet_id' => $fleet['fleet_id'], 'target' => "{$tG}:{$tS}:{$tP}", 'current_fleet_mse' => $curFleetMSE),
                        "abort_fleet_withdrawn_{$fleet['fleet_id']}",
                        9999.0
                    );
                    continue;
                }

                // If win probability dropped below 70%, it is a ninja or heavy reinforcement!
                if (isset($sim['win_probability']) && $sim['win_probability'] < 0.70) {
                    $this->gateway->recallFleet((int)$fleet['fleet_id']);
                    $this->ctx->decisionLog->record(
                        'pre_impact_abort',
                        "PRE-IMPACT ABORT: Ninja o refuerzos detectados en destino [{$tG}:{$tS}:{$tP}]. Probabilidad de victoria cayó a " . round($sim['win_probability'] * 100, 1) . "%. Flota retirada.",
                        array('fleet_id' => $fleet['fleet_id'], 'eval' => $sim, 'target' => "{$tG}:{$tS}:{$tP}"),
                        "abort_{$fleet['fleet_id']}",
                        9999.0
                    );
                }
            }
        }
    }

    public function buildRecyclerFleet(array $fobData, $expectedDebris)
    {
        global $resource, $pricelist;

        $recs219 = isset($fobData[$resource[219]]) ? (int)$fobData[$resource[219]] : 0;
        $recs209 = isset($fobData[$resource[209]]) ? (int)$fobData[$resource[209]] : 0;

        $cap219 = isset($pricelist[219]['capacity']) ? (float)$pricelist[219]['capacity'] : 500000;
        $cap209 = isset($pricelist[209]['capacity']) ? (float)$pricelist[209]['capacity'] : 20000;

        $recyclerFleet = array();
        $remDebris = (float)$expectedDebris;

        // If Giga Recyclers (219) are available and can carry at least the expected debris (or no 209 exist), use ONLY 219
        // This preserves ultra-fast speed (~58,950) without being throttled by 209 (8,120)
        $gigaTotalCap = $recs219 * $cap219;
        if ($recs219 > 0 && ($gigaTotalCap >= $remDebris || $recs209 <= 0)) {
            $take219 = min($recs219, (int)ceil($remDebris / $cap219));
            if ($take219 > 0) {
                $recyclerFleet[219] = $take219;
            }
            return $recyclerFleet;
        }

        // Otherwise, take all available 219 and fill the rest with 209
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
     * Sincronización milimétrica OGame:
     * Los recicladores deben impactar exactamente a T_impacto + 2 segundos.
     * Si los recicladores tardan más que la flota de combate, se lanzan primero los recicladores
     * y se programa el lanzamiento de la flota de combate para T + Delta.
     * Si la flota de combate tarda más, se lanza primero el ataque y se programan los recicladores.
     */
    public function launchSynchronizedStrikeAndRecyclers(
        array $fobData,
        $fobId,
        array $t,
        array $strikeFleet,
        $expectedDebris,
        $winProb = 1.0,
        $category = 'fleet_crash',
        $extraLogContext = array()
    ) {
        $maxSlots     = FleetFunctions::GetMaxFleetSlots($this->ctx->user);
        $actualFleets = FleetFunctions::GetCurrentFleets($this->ctx->botId);
        if ($actualFleets >= $maxSlots) {
            return 0;
        }

        $fobCoords = array($fobData['galaxy'], $fobData['system'], $fobData['planet']);
        $tCoords   = array($t['galaxy'], $t['system'], $t['planet']);
        $fDist     = FleetFunctions::GetTargetDistance($fobCoords, $tCoords);
        $gameSpeed = FleetFunctions::GetGameSpeedFactor();

        $recyclerFleet = array();
        if ($expectedDebris > 10000.0) {
            $recyclerFleet = $this->buildRecyclerFleet($fobData, $expectedDebris);
        }

        // Caso sin recicladores disponibles o escombros despreciables
        if (empty($recyclerFleet)) {
            $ok = $this->gateway->launchFleet(
                $strikeFleet,
                1, // Attack
                $fobId,
                $t['planet_id'],
                $t['owner_id'],
                $t['galaxy'],
                $t['system'],
                $t['planet'],
                1,
                array(),
                10
            );

            if ($ok) {
                $fSpeed = FleetFunctions::GetFleetMaxSpeed($strikeFleet, $this->ctx->user);
                $fDuration = FleetFunctions::GetMissionDuration(10, $fSpeed, $fDist, $gameSpeed, $this->ctx->user);
                $this->ctx->decisionLog->record(
                    'strike_launched',
                    "Launched {$category} strike from FOB #{$fobId} against [{$t['galaxy']}:{$t['system']}:{$t['planet']}] with " . number_format(array_sum($strikeFleet)) . " warships (" . round($winProb * 100, 1) . "% win probability) at 100% speed (ETA: {$fDuration}s)",
                    array_merge(array('target' => $t, 'fleet' => $strikeFleet, 'fob_id' => $fobId, 'speed' => 10), $extraLogContext),
                    "attack_{$t['galaxy']}_{$t['system']}_{$t['planet']}",
                    6000.0
                );
                return 1;
            }
            return 0;
        }

        // Ambas flotas presentes: sincronización milimétrica para arribo a T_impacto + 2s
        $strikeSpeed = FleetFunctions::GetFleetMaxSpeed($strikeFleet, $this->ctx->user);
        $recSpeed    = FleetFunctions::GetFleetMaxSpeed($recyclerFleet, $this->ctx->user);

        // Velocidad 100% (10) para minimizar tiempo de vuelo y ventana de alerta defensiva
        $sStrike = 10;
        $sRec    = 10;
        $dStrike = FleetFunctions::GetMissionDuration($sStrike, $strikeSpeed, $fDist, $gameSpeed, $this->ctx->user);
        $dRec    = FleetFunctions::GetMissionDuration($sRec, $recSpeed, $fDist, $gameSpeed, $this->ctx->user);

        // delta = segundos de desfase entre arribos
        // Queremos: T_llegada_rec = T_llegada_strike + 2
        // T_lanz_rec + dRec = T_lanz_strike + dStrike + 2
        // delta = dRec - dStrike - 2
        $delta = ($dRec - 2) - $dStrike;

        if ($delta >= 0) {
            // Recicladores son MÁS LENTOS (o iguales): Se lanzan PRIMERO los recicladores a velocidad 100%
            $recOk = $this->gateway->launchFleet(
                $recyclerFleet,
                8, // Recycle
                $fobId,
                $t['planet_id'],
                0,
                $t['galaxy'],
                $t['system'],
                $t['planet'],
                2, // Debris
                array(),
                $sRec
            );

            if (!$recOk) {
                return 0;
            }

            $this->ctx->decisionLog->record(
                'synchronized_recycler_dispatch',
                "Synchronized pre-impact deployment of " . array_sum($recyclerFleet) . " recyclers to debris at [{$t['galaxy']}:{$t['system']}:{$t['planet']}] at {$sRec}0% speed (ETA: {$dRec}s | Debris: " . number_format($expectedDebris) . ")",
                array('target' => $t, 'recyclers' => $recyclerFleet, 'debris' => $expectedDebris, 'speed' => $sRec, 'duration' => $dRec),
                "sync_recycle_{$t['galaxy']}_{$t['system']}_{$t['planet']}",
                4500.0
            );

            $delaySeconds = (int)round($delta);

            if ($delaySeconds <= 3) {
                if ($delaySeconds > 0) {
                    sleep($delaySeconds);
                }
                $ok = $this->gateway->launchFleet(
                    $strikeFleet,
                    1,
                    $fobId,
                    $t['planet_id'],
                    $t['owner_id'],
                    $t['galaxy'],
                    $t['system'],
                    $t['planet'],
                    1,
                    array(),
                    $sStrike
                );

                if ($ok) {
                    $this->ctx->decisionLog->record(
                        'strike_launched',
                        "Launched {$category} strike from FOB #{$fobId} against [{$t['galaxy']}:{$t['system']}:{$t['planet']}] with " . number_format(array_sum($strikeFleet)) . " warships (" . round($winProb * 100, 1) . "% win probability) at {$sStrike}0% speed (Sincronizado: Recicladores arriban T+2s)",
                        array_merge(array('target' => $t, 'fleet' => $strikeFleet, 'fob_id' => $fobId, 'speed' => $sStrike), $extraLogContext),
                        "attack_{$t['galaxy']}_{$t['system']}_{$t['planet']}",
                        6000.0
                    );
                    return 1;
                }
            } else {
                BotScheduler::scheduleTask(
                    $this->ctx->botId,
                    'staggered_fleet_assault',
                    array(
                        'fleet'            => $strikeFleet,
                        'fob_id'           => $fobId,
                        'target_planet_id' => $t['planet_id'],
                        'target_owner_id'  => $t['owner_id'],
                        'target_galaxy'    => $t['galaxy'],
                        'target_system'    => $t['system'],
                        'target_planet'    => $t['planet'],
                        'speed'            => $sStrike,
                    ),
                    TIMESTAMP + $delaySeconds
                );

                $this->ctx->decisionLog->record(
                    'strike_scheduled_for_impact',
                    "Ofensiva programada: Despacho de " . number_format(array_sum($strikeFleet)) . " naves a velocidad {$sStrike}0% programado para T+{$delaySeconds}s hacia [{$t['galaxy']}:{$t['system']}:{$t['planet']}]. Arribo de recicladores calibrado exactamente a T+2s del impacto.",
                    array_merge(array('target' => $t, 'fleet' => $strikeFleet, 'delay' => $delaySeconds, 'speed' => $sStrike), $extraLogContext),
                    "sched_strike_{$t['galaxy']}_{$t['system']}_{$t['planet']}",
                    6000.0
                );
                return 1;
            }
        } else {
            // Flota de ataque es MÁS LENTA: Se lanza PRIMERO el ataque a velocidad 100%
            $ok = $this->gateway->launchFleet(
                $strikeFleet,
                1,
                $fobId,
                $t['planet_id'],
                $t['owner_id'],
                $t['galaxy'],
                $t['system'],
                $t['planet'],
                1,
                array(),
                $sStrike
            );

            if (!$ok) {
                return 0;
            }

            $this->ctx->decisionLog->record(
                'strike_launched',
                "Launched {$category} strike from FOB #{$fobId} against [{$t['galaxy']}:{$t['system']}:{$t['planet']}] with " . number_format(array_sum($strikeFleet)) . " warships (" . round($winProb * 100, 1) . "% win probability) at {$sStrike}0% speed (ETA: {$dStrike}s)",
                array_merge(array('target' => $t, 'fleet' => $strikeFleet, 'fob_id' => $fobId, 'speed' => $sStrike), $extraLogContext),
                "attack_{$t['galaxy']}_{$t['system']}_{$t['planet']}",
                6000.0
            );

            $delaySeconds = (int)round(abs($delta));

            if ($delaySeconds <= 3) {
                if ($delaySeconds > 0) {
                    sleep($delaySeconds);
                }
                $recOk = $this->gateway->launchFleet(
                    $recyclerFleet,
                    8,
                    $fobId,
                    $t['planet_id'],
                    0,
                    $t['galaxy'],
                    $t['system'],
                    $t['planet'],
                    2,
                    array(),
                    $sRec
                );

                if ($recOk) {
                    $this->ctx->decisionLog->record(
                        'synchronized_recycler_dispatch',
                        "Synchronized pre-impact deployment of " . array_sum($recyclerFleet) . " recyclers to debris at [{$t['galaxy']}:{$t['system']}:{$t['planet']}] at {$sRec}0% speed (Arribo: Impacto + 2s)",
                        array('target' => $t, 'recyclers' => $recyclerFleet, 'debris' => $expectedDebris, 'speed' => $sRec),
                        "sync_recycle_{$t['galaxy']}_{$t['system']}_{$t['planet']}",
                        4500.0
                    );
                }
            } else {
                BotScheduler::scheduleTask(
                    $this->ctx->botId,
                    'staggered_recycler_dispatch',
                    array(
                        'fleet'            => $recyclerFleet,
                        'fob_id'           => $fobId,
                        'target_planet_id' => $t['planet_id'],
                        'target_galaxy'    => $t['galaxy'],
                        'target_system'    => $t['system'],
                        'target_planet'    => $t['planet'],
                        'speed'            => $sRec,
                    ),
                    TIMESTAMP + $delaySeconds
                );

                $this->ctx->decisionLog->record(
                    'recycler_scheduled_for_impact',
                    "Recicladores programados: Despacho de " . array_sum($recyclerFleet) . " recicladores a velocidad {$sRec}0% programado para T+{$delaySeconds}s hacia [{$t['galaxy']}:{$t['system']}:{$t['planet']}]. Arribo calibrado exactamente a T+2s del impacto.",
                    array('target' => $t, 'recyclers' => $recyclerFleet, 'delay' => $delaySeconds, 'speed' => $sRec),
                    "sched_rec_{$t['galaxy']}_{$t['system']}_{$t['planet']}",
                    4500.0
                );
            }

            return 1;
        }

        return 0;
    }

    private function dispatchSynchronizedRecyclers(array $fobData, $fobId, array $t, $expectedDebris, $combatArrivalTime = 0)
    {
        $recyclerFleet = $this->buildRecyclerFleet($fobData, $expectedDebris);
        if (!empty($recyclerFleet)) {
            $this->gateway->launchFleet(
                $recyclerFleet,
                8,
                $fobId,
                $t['planet_id'],
                0,
                $t['galaxy'],
                $t['system'],
                $t['planet'],
                2,
                array(),
                10
            );
        }
    }

    /**
     * Section 3: Builds tactical strike fleet by role
     */
    public function buildTacticalStrikeFleet(array $fobData, array $targetDefenders, array $lootNeeded = array(), $category = 'farming')
    {
        global $resource, $pricelist;

        $warshipsOnFob = array();
        $fodderOnFob   = array();
        $cargoOnFob    = array();

        $warshipIds = array(206, 207, 211, 213, 214, 215, 216, 218, 221, 222, 224, 225, 226, 227, 228);
        $fodderIds  = array(204, 205);
        $cargoIds   = array(217, 203, 202);

        foreach ($warshipIds as $wId) {
            $col = isset($resource[$wId]) ? $resource[$wId] : '';
            $qty = (!empty($col) && isset($fobData[$col])) ? (int)$fobData[$col] : 0;
            if ($qty > 0) $warshipsOnFob[$wId] = $qty;
        }

        foreach ($fodderIds as $fId) {
            $col = isset($resource[$fId]) ? $resource[$fId] : '';
            $qty = (!empty($col) && isset($fobData[$col])) ? (int)$fobData[$col] : 0;
            if ($qty > 0) $fodderOnFob[$fId] = $qty;
        }

        foreach ($cargoIds as $cId) {
            $col = isset($resource[$cId]) ? $resource[$cId] : '';
            $qty = (!empty($col) && isset($fobData[$col])) ? (int)$fobData[$col] : 0;
            if ($qty > 0) $cargoOnFob[$cId] = $qty;
        }

        $totalWarshipsAvail = array_sum($warshipsOnFob);
        $totalFodderAvail   = array_sum($fodderOnFob);

        if ($totalWarshipsAvail <= 0 && $totalFodderAvail <= 0) {
            return array();
        }

        $warshipsToSend = array();
        $fodderToSend   = array();

        if ($category === 'farming') {
            // Section 3: Farming: Cargos + minimal light escort (5-15 light hunters or 2-5 cruisers)
            $escortNeed = 15;
            if (isset($warshipsOnFob[206]) && $warshipsOnFob[206] > 0) {
                $takeC = min(3, $warshipsOnFob[206]);
                $warshipsToSend[206] = $takeC;
                $escortNeed -= ($takeC * 3);
            }
            if ($escortNeed > 0 && isset($fodderOnFob[204]) && $fodderOnFob[204] > 0) {
                $takeF = min($escortNeed, $fodderOnFob[204]);
                $fodderToSend[204] = $takeF;
            }
        } elseif ($category === 'siege') {
            // Section 3: Siege: Bombers (211) con fuego rápido contra defensas + Destructores (213) y naves de línea pesadas
            $siegePriorityWarships = array(211, 213, 215, 216, 227, 228, 207);
            foreach ($siegePriorityWarships as $sWId) {
                if (isset($warshipsOnFob[$sWId]) && $warshipsOnFob[$sWId] > 0) {
                    $warshipsToSend[$sWId] = $warshipsOnFob[$sWId];
                }
            }
            // Add remaining warships if needed
            foreach ($warshipsOnFob as $wId => $qty) {
                if (!isset($warshipsToSend[$wId]) && $qty > 0) {
                    $warshipsToSend[$wId] = $qty;
                }
            }
            // Fodder buffer up to 25% for siege
            $maxFodder = (int)floor(array_sum($warshipsToSend) * 0.25);
            $fodderRemaining = min($maxFodder, $totalFodderAvail);
            foreach ($fodderOnFob as $fId => $qty) {
                if ($fodderRemaining <= 0) break;
                $take = min($qty, $fodderRemaining);
                $fodderToSend[$fId] = $take;
                $fodderRemaining -= $take;
            }
        } else {
            // Section 3: Fleet Crash: Cruceros de batalla / destructores + Cazas Ligeros como fodder
            // (pantalla de absorción de disparos dimensionada de forma que el valor marginal de supervivencia > costo)
            $fleetCrashPriority = array(215, 213, 206, 207, 216, 227, 228);
            foreach ($fleetCrashPriority as $fWId) {
                if (isset($warshipsOnFob[$fWId]) && $warshipsOnFob[$fWId] > 0) {
                    $warshipsToSend[$fWId] = $warshipsOnFob[$fWId];
                }
            }
            foreach ($warshipsOnFob as $wId => $qty) {
                if (!isset($warshipsToSend[$wId]) && $qty > 0) {
                    $warshipsToSend[$wId] = $qty;
                }
            }
            // Dimension screen fodder: up to 3.5x capital warships count where marginal survival > cost
            $coreWarshipsCount = array_sum($warshipsToSend);
            $targetFodder = (int)floor($coreWarshipsCount * 3.5);
            $fodderRemaining = min($targetFodder, $totalFodderAvail);
            // Prioritize light fighters (204) for screen
            if (isset($fodderOnFob[204]) && $fodderOnFob[204] > 0) {
                $takeLF = min($fodderOnFob[204], $fodderRemaining);
                $fodderToSend[204] = $takeLF;
                $fodderRemaining -= $takeLF;
            }
            if ($fodderRemaining > 0 && isset($fodderOnFob[205]) && $fodderOnFob[205] > 0) {
                $takeHF = min($fodderOnFob[205], $fodderRemaining);
                $fodderToSend[205] = $takeHF;
            }
        }

        // Calculate Cargo Ships needed for Loot
        $lootMetal = isset($lootNeeded['metal']) ? (float)$lootNeeded['metal'] : 0.0;
        $lootCry   = isset($lootNeeded['crystal']) ? (float)$lootNeeded['crystal'] : 0.0;
        $lootDeut  = isset($lootNeeded['deuterium']) ? (float)$lootNeeded['deuterium'] : 0.0;
        $totalLoot = ($lootMetal + $lootCry + $lootDeut) * 1.15;

        $cargosToSend = array();
        $carriedCapacity = 0.0;

        foreach ($cargoOnFob as $cId => $qty) {
            if ($carriedCapacity >= $totalLoot && $totalLoot > 0) break;
            $capPerUnit = isset($pricelist[$cId]['capacity']) ? (float)$pricelist[$cId]['capacity'] : 25000.0;
            if ($capPerUnit <= 0) $capPerUnit = 25000.0;

            if ($totalLoot > 0) {
                $needed = (int)ceil(($totalLoot - $carriedCapacity) / $capPerUnit);
                $take = min($qty, $needed);
            } else {
                $take = min($qty, 10);
            }

            if ($take > 0) {
                $cargosToSend[$cId] = $take;
                $carriedCapacity += $take * $capPerUnit;
            }
        }

        // Combine full strike fleet
        $strikeFleet = array();
        foreach ($warshipsToSend as $id => $cnt) {
            if ($cnt > 0) $strikeFleet[$id] = $cnt;
        }
        foreach ($fodderToSend as $id => $cnt) {
            if ($cnt > 0) $strikeFleet[$id] = isset($strikeFleet[$id]) ? $strikeFleet[$id] + $cnt : $cnt;
        }
        foreach ($cargosToSend as $id => $cnt) {
            if ($cnt > 0) $strikeFleet[$id] = isset($strikeFleet[$id]) ? $strikeFleet[$id] + $cnt : $cnt;
        }

        return $strikeFleet;
    }

    private function determineFobPlanetId(BotEmpireState $empire, $tG, $tS)
    {
        $allPlanets = $empire->getPlanets();
        if (empty($allPlanets)) return 0;

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

        return $bestId;
    }

    private function releaseLock($reason, $coords = '')
    {
        $this->ctx->updateBotRow(array(
            'target_galaxy'      => 0,
            'target_system'      => 0,
            'target_planet'      => 0,
            'target_type'        => '',
            'target_initial_mse' => 0.0,
            'target_lock_time'   => 0,
            'siege_cycles'       => 0,
            'fob_planet_id'      => 0,
            'siege_spent_mse'    => 0.0,
        ));

        $this->ctx->decisionLog->record(
            'target_released',
            "Released target {$coords} from tactical lock. Reason: {$reason}. Searching for new high-value target.",
            array('coords' => $coords, 'reason' => $reason),
            "release_{$coords}",
            1000.0
        );
    }
}

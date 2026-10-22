<?php

require_once 'includes/classes/bot/Kernel/BotClock.class.php';
require_once 'includes/classes/bot/Evaluation/CombatOracle.class.php';
require_once 'includes/classes/bot/Evaluation/EconomyValuator.class.php';
require_once 'includes/classes/bot/Military/TargetBlacklist.class.php';
require_once 'includes/classes/bot/Forces/BotPremiumStrategist.class.php';

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
    private static function now()
    {
        return class_exists('BotClock') ? BotClock::now() : (defined('TIMESTAMP') ? TIMESTAMP : time());
    }

    private static function sleepIfReal($seconds)
    {
        if (defined('SIMULATION_MODE') && SIMULATION_MODE) {
            return;
        }
        if ($seconds > 0) {
            sleep($seconds);
        }
    }

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
        if (defined('BOT_ATTACKS_DISABLED') && BOT_ATTACKS_DISABLED) {
            return 0;
        }

        global $resource, $reslist, $pricelist, $CombatCaps;

        $db = Database::get();

        // 0. PRE-IMPACT VERIFICATION: Check in-flight fleets for ninja/anomalies (Section 2.5)
        $this->verifyInFlightStrikes($world);

        // 0.0 STRICT CONCURRENT STRIKES CEILING (Warfare Doctrine v3.4 Apex Predator)
        $maxConcurrentStrikes = method_exists($this->ctx->difficulty, 'getMaxConcurrentStrikes')
            ? (int)$this->ctx->difficulty->getMaxConcurrentStrikes()
            : 2;
        $activeStrikesRow = $db->selectSingle("SELECT COUNT(*) as cnt FROM %%FLEETS%% WHERE fleet_owner = :botId AND fleet_mission = 1 AND fleet_mess = 0;", array(
            ':botId' => $this->ctx->botId,
        ));
        $activeAttacks = !empty($activeStrikesRow) ? (int)$activeStrikesRow['cnt'] : 0;
        if ($activeAttacks >= $maxConcurrentStrikes) {
            $this->ctx->decisionLog->record(
                'concurrent_strikes_limit_reached',
                "Límite estricto de ataques concurrentes alcanzado ({$activeAttacks}/{$maxConcurrentStrikes}). Postergando nuevas ofensivas de campaña y farming.",
                array('active_attacks' => $activeAttacks, 'max_concurrent' => $maxConcurrentStrikes),
                "limit_strikes_{$this->ctx->botId}",
                2500.0
            );
            return 0;
        }

        // 0.1 STAGNATION WATCHDOG (Detector de Estancamiento):
        // Si el bot lleva más de 2 horas sin realizar un ataque y no tiene ataques activos en vuelo,
        // purga los bloqueos temporales (no bashing) para desbloquear re-espionaje y re-evaluación inmediata.
        $lastAttack = isset($this->ctx->botRow['last_attack']) ? (int)$this->ctx->botRow['last_attack'] : 0;
        $refTime = ($lastAttack > 0) ? $lastAttack : (int)($this->ctx->botRow['created_at'] ?? ($this->ctx->user['register_time'] ?? self::now()));
        if ($activeAttacks == 0 && (self::now() - $refTime) >= 7200) {
            $db->delete("DELETE FROM " . DB_PREFIX . "bot_planet_cooldown 
                WHERE bot_id = :botId AND reason NOT LIKE '%bashing%' AND (planet_id = 0 OR cooldown_until < :futureMax);", array(
                ':botId'     => $this->ctx->botId,
                ':futureMax' => self::now() + 7200,
            ));
            $this->ctx->botRow['last_attack'] = self::now();
            $db->update("UPDATE %%BOTS%% SET last_attack = :la WHERE bot_id = :bId;", array(
                ':la'  => self::now(),
                ':bId' => $this->ctx->botId
            ));
            $this->ctx->decisionLog->record(
                'stagnation_watchdog_reset',
                "Stagnation Watchdog: Bot sin ataques ofensivos en las últimas 2h. Purgados cooldowns temporales para reanálisis táctico inmediato.",
                array('ref_time' => $refTime, 'elapsed' => (self::now() - $refTime)),
                "stagnation_reset_{$this->ctx->botId}",
                4000.0
            );
        }

        // 1. EVALUATE ACTIVE TARGET LOCK (Campaign)
        $lockedGalaxy = isset($this->ctx->botRow['target_galaxy']) ? (int)$this->ctx->botRow['target_galaxy'] : 0;
        $lockedSystem = isset($this->ctx->botRow['target_system']) ? (int)$this->ctx->botRow['target_system'] : 0;
        $lockedPlanet = isset($this->ctx->botRow['target_planet']) ? (int)$this->ctx->botRow['target_planet'] : 0;
        $lockedType   = isset($this->ctx->botRow['target_type']) ? $this->ctx->botRow['target_type'] : '';
        $initialMse   = isset($this->ctx->botRow['target_initial_mse']) ? (float)$this->ctx->botRow['target_initial_mse'] : 0.0;
        $siegeCycles  = isset($this->ctx->botRow['siege_cycles']) ? (int)$this->ctx->botRow['siege_cycles'] : 0;
        $fobId        = isset($this->ctx->botRow['fob_planet_id']) ? (int)$this->ctx->botRow['fob_planet_id'] : 0;
        $spentSiege   = isset($this->ctx->botRow['siege_spent_mse']) ? (float)$this->ctx->botRow['siege_spent_mse'] : 0.0;
        $targetLockTime = isset($this->ctx->botRow['target_lock_time']) ? (int)$this->ctx->botRow['target_lock_time'] : 0;
        if ($targetLockTime <= 0 && $lockedGalaxy > 0 && $lockedSystem > 0 && $lockedPlanet > 0) {
            $targetLockTime = self::now();
            $this->ctx->updateBotRow(array('target_lock_time' => self::now()));
        }

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
            } elseif (BotTargetBlacklist::isPlayerBlacklisted($this->ctx->botId, (int)$pRow['id_owner'])) {
                $this->releaseLock("Objetivo en lista negra de 24 horas tras alcanzar límite de victorias por jugador (Anti-Bashing)", "{$lockedGalaxy}:{$lockedSystem}:{$lockedPlanet}");
            } else {
                // Validación estricta de Protección de Principiantes (10K pts) y Regla de Rango Relativo x5
                $targetPointsRow = $db->selectSingle("SELECT total_points FROM %%STATPOINTS%% WHERE id_owner = :uid AND stat_type = 1;", array(':uid' => (int)$pRow['id_owner']));
                $targetPoints = !empty($targetPointsRow) ? (float)$targetPointsRow['total_points'] : 0.0;
                $botPoints = (float)($this->ctx->user['total_points'] ?? 0);
                $targetUserData = array('id_owner' => (int)$pRow['id_owner'], 'total_points' => $targetPoints);
                $botUserData = array('id_owner' => $this->ctx->botId, 'total_points' => $botPoints);
                $noobCheck = CheckNoobProtec($botUserData, $targetUserData, $targetUserData);
                if ($noobCheck['NoobPlayer']) {
                    $this->releaseLock("Objetivo protegido por protección de principiantes o rango x5 (NoobPlayer)", "{$lockedGalaxy}:{$lockedSystem}:{$lockedPlanet}");
                    return 0;
                } elseif ($noobCheck['StrongPlayer']) {
                    $this->releaseLock("Objetivo excede el rango x5 de puntos del bot (StrongPlayer)", "{$lockedGalaxy}:{$lockedSystem}:{$lockedPlanet}");
                    return 0;
                }

                // Fetch latest intel synced to difficulty half-life (16.0h on Nightmare)
                $intelHalfLife = isset($this->ctx->difficulty->intelMaxAgeHours) ? (float)$this->ctx->difficulty->intelMaxAgeHours : 16.0;
                $intel = $world->intel->getIntel($lockedGalaxy, $lockedSystem, $lockedPlanet, 1, $intelHalfLife);

                // --- DYNAMIC RECLASSIFICATION ENGINE (NovaRush Real-Time Tactical Adaptation) ---
                if ($intel !== null && $intel['confidence'] >= 0.4) {
                    $hasHostileFleet = false;
                    $hasHeavyDefs    = false;
                    $fodderCount     = 0;
                    if (!empty($intel['fleet']) && is_array($intel['fleet'])) {
                        foreach ($intel['fleet'] as $sId => $cnt) {
                            if ($cnt <= 0 || in_array($sId, array(202, 203, 208, 209, 210, 212, 217, 219, 233))) continue;
                            if (in_array($sId, array(204, 205))) {
                                $fodderCount += (int)$cnt;
                            } else {
                                $hasHostileFleet = true;
                                break;
                            }
                        }
                    }
                    if (!$hasHostileFleet && $fodderCount > 50) {
                        $hasHostileFleet = true;
                    }

                    if (!empty($intel['defense']) && is_array($intel['defense'])) {
                        foreach ($intel['defense'] as $dId => $dCnt) {
                            if ($dCnt <= 0 || $dId == 502) continue;
                            if ($dId > 402) {
                                $hasHeavyDefs = true;
                                break;
                            } else {
                                $fodderCount += (int)$dCnt;
                            }
                        }
                    }
                    if (!$hasHeavyDefs && $fodderCount > 50) {
                        $hasHeavyDefs = true;
                    }

                    // A) Ascender dinámicamente de 'farming' -> 'fleet_crash' si hay guarnición hostil
                    if ($lockedType === 'farming' && ($hasHostileFleet || $hasHeavyDefs)) {
                        $newType = 'fleet_crash';
                        $lockedType = $newType;
                        $db->update("UPDATE " . DB_PREFIX . "bots SET target_type = :tt WHERE bot_id = :bId;", array(
                            ':tt'  => $newType,
                            ':bId' => $this->ctx->botId
                        ));
                        $this->ctx->botRow['target_type'] = $newType;
                        $this->ctx->decisionLog->record(
                            'target_reclassified',
                            "Reclasificación dinámica en caliente: Objetivo [{$lockedGalaxy}:{$lockedSystem}:{$lockedPlanet}] ascendido de 'farming' a 'fleet_crash' tras confirmar presencia de guarnición hostil (" . ($hasHostileFleet ? "flota de combate" : "defensas pesadas") . ").",
                            array('target' => "{$lockedGalaxy}:{$lockedSystem}:{$lockedPlanet}", 'old_type' => 'farming', 'new_type' => 'fleet_crash'),
                            "reclass_{$lockedGalaxy}_{$lockedSystem}_{$lockedPlanet}",
                            4000.0
                        );
                    }
                    // B) Desescalar dinámicamente de 'fleet_crash' -> 'farming' si la flota enemiga evacuó
                    elseif ($lockedType === 'fleet_crash' && !$hasHostileFleet && !$hasHeavyDefs) {
                        $newType = 'farming';
                        $lockedType = $newType;
                        $db->update("UPDATE " . DB_PREFIX . "bots SET target_type = :tt WHERE bot_id = :bId;", array(
                            ':tt'  => $newType,
                            ':bId' => $this->ctx->botId
                        ));
                        $this->ctx->botRow['target_type'] = $newType;
                        $this->ctx->decisionLog->record(
                            'target_reclassified',
                            "Reclasificación dinámica en caliente: Objetivo [{$lockedGalaxy}:{$lockedSystem}:{$lockedPlanet}] desescalado de 'fleet_crash' a 'farming' al constatar retirada total de la flota defensora.",
                            array('target' => "{$lockedGalaxy}:{$lockedSystem}:{$lockedPlanet}", 'old_type' => 'fleet_crash', 'new_type' => 'farming'),
                            "reclass_{$lockedGalaxy}_{$lockedSystem}_{$lockedPlanet}",
                            4000.0
                        );
                    }
                }

                $release = false;
                $releaseReason = '';

                // Fail-Fast: Límite de horizonte estricto de asedio / asalto (3 ciclos = ~135s en lugar de 80 ciclos)
                if (in_array($lockedType, array('siege', 'fleet_crash', 'farming'))) {
                    if ($siegeCycles >= 3) {
                        $release = true;
                        $releaseReason = "Fail-Fast: Límite de horizonte alcanzado (3 ciclos consecutivos sin condiciones de despegue)";
                        BotTargetBlacklist::addCooldown($this->ctx->botId, (int)$pRow['id_owner'], (int)$pRow['id'], $lockedGalaxy, $lockedSystem, $lockedPlanet, 1800, $releaseReason);
                    } elseif ($spentSiege > 0 && $initialMse > 0 && $spentSiege > (1.2 * $initialMse)) {
                        $release = true;
                        $releaseReason = "Anti-Sunk-Cost: Gasto acumulado (" . number_format($spentSiege) . " MSE) superó el límite de rentabilidad ROI sobre el botín esperado (" . number_format($initialMse) . " MSE)";
                    }
                }

                // Wall-Clock Timeout: Horizon limit of 90 minutes (5400s) for any locked campaign target
                if (!$release && $targetLockTime > 0 && (self::now() - $targetLockTime) >= 5400) {
                    $activeAssault = $db->selectSingle("SELECT COUNT(*) as cnt FROM %%FLEETS%% 
                        WHERE fleet_owner = :botId 
                          AND fleet_end_galaxy = :g 
                          AND fleet_end_system = :s 
                          AND fleet_end_planet = :p 
                          AND fleet_mission IN (1, 8, 10) 
                          AND fleet_mess = 0;", array(
                        ':botId' => $this->ctx->botId,
                        ':g'     => $lockedGalaxy,
                        ':s'     => $lockedSystem,
                        ':p'     => $lockedPlanet
                    ));
                    $hasAssault = !empty($activeAssault) && (int)$activeAssault['cnt'] > 0;
                    if (!$hasAssault) {
                        $release = true;
                        $releaseReason = "Límite de horizonte temporal de campaña excedido (90 min sin ataque ejecutado)";
                        BotTargetBlacklist::addCooldown($this->ctx->botId, (int)$pRow['id_owner'], (int)$pRow['id'], $lockedGalaxy, $lockedSystem, $lockedPlanet, 21600, $releaseReason);
                    }
                }

                // Active Empire-Wide Viability Check (Fleet Crash & Siege)
                if (!$release && in_array($lockedType, array('fleet_crash', 'siege')) && $intel !== null && $intel['confidence'] >= 0.4) {
                    $empireCombatFleet = array();
                    global $reslist, $resource;
                    if ($world !== null && isset($world->empire)) {
                        foreach ($world->empire->getAllBodies() as $b) {
                            if (empty($b['data'])) continue;
                            foreach ($reslist['fleet'] as $sId) {
                                if ($sId == 210 || $sId == 212 || in_array($sId, array(208, 209, 219))) continue;
                                $cnt = isset($b['data'][$resource[$sId]]) ? (int)$b['data'][$resource[$sId]] : 0;
                                if ($cnt > 0) {
                                    $empireCombatFleet[$sId] = isset($empireCombatFleet[$sId]) ? $empireCombatFleet[$sId] + $cnt : $cnt;
                                }
                            }
                        }
                    }
                    $targetDefenders = (isset($intel['defense']) && is_array($intel['defense']) ? $intel['defense'] : array())
                                     + (isset($intel['fleet']) && is_array($intel['fleet']) ? $intel['fleet'] : array());
                    $targetFactors   = BotCombatOracle::resolveDefenderFactors((int)$pRow['id_owner'], is_array($intel) ? $intel : array());

                    if (!empty($targetDefenders) && !empty($empireCombatFleet)) {
                        $simEmpire = BotCombatOracle::evaluate(
                            $empireCombatFleet,
                            $targetDefenders,
                            array('id' => $this->ctx->botId, 'factor' => $this->ctx->user['factor'] ?? array()),
                            array('id' => (int)$pRow['id_owner'], 'factor' => $targetFactors),
                            2
                        );
                        $empireWinProb = isset($simEmpire['win_probability']) ? (float)$simEmpire['win_probability'] : 0.0;
                        $deployedEmpireMSE = 0.0;
                        foreach ($empireCombatFleet as $sId => $cnt) {
                            $deployedEmpireMSE += BotEconomyValuator::getElementPriceMSE($sId, $cnt);
                        }

                        $minRoiReq = isset($this->ctx->difficulty->minRoiRequired) ? (float)$this->ctx->difficulty->minRoiRequired : 0.20;
                        $maxLossAllowed = isset($this->ctx->difficulty->maxOwnFleetLossRatioAllowed) ? (float)$this->ctx->difficulty->maxOwnFleetLossRatioAllowed : 0.40;

                        $finEmpire = BotCombatOracle::evaluateFinancialBalance(
                            $simEmpire,
                            $deployedEmpireMSE,
                            0.0,
                            500000000000.0, // Cap amplio para verificar viabilidad macro
                            $initialMse * 0.50
                        );

                        $isEmpireViable = ($empireWinProb >= 0.70) || (
                            $finEmpire['is_net_profitable'] &&
                            $finEmpire['roi'] >= $minRoiReq &&
                            $finEmpire['att_loss_pct'] <= $maxLossAllowed
                        );

                        if (!$isEmpireViable) {
                            $release = true;
                            $releaseReason = "Inviable militar/financieramente para el imperio (Win: " . round($empireWinProb * 100, 1) . "%, ROI: " . round($finEmpire['roi'] * 100, 1) . "%)";
                            BotTargetBlacklist::addCooldown($this->ctx->botId, (int)$pRow['id_owner'], (int)$pRow['id'], $lockedGalaxy, $lockedSystem, $lockedPlanet, 1800, $releaseReason);
                        }
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
                    // CRITICAL SAFETY GATE: Never release target or cancel tasks if missiles (mission 10)
                    // or assault fleets (missions 1, 2) are already active in flight towards the locked target!
                    $activeInFlightOps = $db->selectSingle("
                        SELECT COUNT(*) as cnt 
                        FROM %%FLEETS%% 
                        WHERE fleet_owner = :botId 
                          AND fleet_end_galaxy = :g 
                          AND fleet_end_system = :s 
                          AND fleet_end_planet = :p 
                          AND fleet_mission IN (1, 2, 10) 
                          AND fleet_mess = 0;
                    ", array(
                        ':botId' => $this->ctx->botId,
                        ':g'     => $lockedGalaxy,
                        ':s'     => $lockedSystem,
                        ':p'     => $lockedPlanet
                    ));

                    if (!empty($activeInFlightOps['cnt']) && (int)$activeInFlightOps['cnt'] > 0) {
                        $release = false;
                        $this->ctx->decisionLog->record(
                            'target_release_blocked',
                            "Intento de liberación cancelado: Hay {$activeInFlightOps['cnt']} misiones activas (ataques/misiles) en vuelo hacia [{$lockedGalaxy}:{$lockedSystem}:{$lockedPlanet}]. Manteniendo objetivo bloqueado.",
                            array('galaxy' => $lockedGalaxy, 'system' => $lockedSystem, 'planet' => $lockedPlanet, 'active_missions' => (int)$activeInFlightOps['cnt']),
                            "block_rel_{$lockedGalaxy}_{$lockedSystem}_{$lockedPlanet}",
                            4000.0
                        );
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

        $campaignStrikes = 0;

        // 2. EXECUTE MISSION AGAINST CURRENTLY LOCKED CAMPAIGN TARGET (IF ANY)
        if ($targetLocked && !empty($activeTarget)) {
            $execRes = $this->tryExecuteCampaignTarget($world, $activeTarget, $basePlanet, false);
            if ($execRes['success']) {
                $campaignStrikes += $execRes['strikes'];
            } else {
                // If active target failed or reached Fail-Fast horizon (3 cycles) and lock was released:
                $stillLocked = !empty($this->ctx->botRow['target_galaxy']) &&
                               !empty($this->ctx->botRow['target_system']) &&
                               !empty($this->ctx->botRow['target_planet']);
                if (!$stillLocked) {
                    $targetLocked = false;
                    $activeTarget = null;
                }
            }
        }

        // 3. EXHAUSTIVE CANDIDATE QUEUE (CASCADE 1..N WITHOUT ARTIFICIAL LIMITS)
        // If there is no active target lock (or previous lock was just released by Fail-Fast),
        // sequentially evaluate every candidate in the radar.
        if (!$targetLocked && is_array($targetCandidates) && !empty($targetCandidates)) {
            $candidateAttempted = 0;
            foreach ($targetCandidates as $cand) {
                $cat = $cand['category'];
                if (!in_array($cat, array('fleet_crash', 'siege', 'farming'))) {
                    continue;
                }
                $t = $cand['target'];
                $ownerId = (int)$t['owner_id'];
                if ($ownerId === 1) {
                    continue;
                }
                $tG = (int)$t['galaxy'];
                $tS = (int)$t['system'];
                $tP = (int)$t['planet'];
                $tId = isset($t['planet_id']) ? (int)$t['planet_id'] : (int)($t['id'] ?? 0);

                if (BotTargetBlacklist::isBlacklisted($this->ctx->botId, $ownerId, $tId, $tG, $tS, $tP)) {
                    continue;
                }

                $bestFobId = !empty($cand['nearest_colony_id']) ? (int)$cand['nearest_colony_id'] : $this->determineFobPlanetId($world->empire, $tG, $tS);

                $initMse = 0.0;
                if ($cat === 'fleet_crash') {
                    $cfg = Config::get();
                    $fCdr = max(0.01, ((float)$cfg->Fleet_Cdr) / 100.0);
                    $initMse = $cand['debris_mse'] / $fCdr;
                } elseif ($cat === 'farming') {
                    $initMse = $cand['loot_mse'];
                } elseif ($cat === 'siege') {
                    $initMse = $cand['loot_mse'] + $cand['debris_mse'];
                }

                $candidateTarget = array(
                    'target'       => $t,
                    'category'     => $cat,
                    'initial_mse'  => $initMse,
                    'siege_cycles' => 0,
                    'fob_id'       => $bestFobId,
                );

                $candidateAttempted++;

                // Evaluate candidate for immediate assault or staging rally
                $res = $this->tryExecuteCampaignTarget($world, $candidateTarget, $basePlanet, true);
                if ($res['success']) {
                    // Candidate succeeded! Lock as active target in database
                    $this->lockCandidateTarget($candidateTarget);
                    $campaignStrikes += $res['strikes'];
                    $targetLocked = true;
                    break;
                } else {
                    // Candidate failed (fuel deficit, lack of local force, or slots full).
                    // Apply typed adaptive cooldown with jitter so we don't repeat it immediately,
                    // and continue to the next candidate in the queue!
                    $reasonStr = (string)$res['reason'];
                    if (stripos($reasonStr, 'ONLINE') !== false) {
                        $failTtl = mt_rand(600, 900); // 10-15m
                        $typedReason = "ONLINE_DISTANCE: {$reasonStr}";
                    } elseif (stripos($reasonStr, 'low_value') !== false || stripos($reasonStr, 'rentable') !== false) {
                        $failTtl = mt_rand(900, 1500); // 15-25m
                        $typedReason = "LOW_VALUE: {$reasonStr}";
                    } elseif (stripos($reasonStr, 'Watchdog') !== false || stripos($reasonStr, 'Emboscada') !== false) {
                        $failTtl = mt_rand(1800, 2700); // 30-45m
                        $typedReason = "WATCHDOG_RECALL: {$reasonStr}";
                    } else {
                        $failTtl = mt_rand(600, 1200); // 10-20m
                        $typedReason = "Candidato descartado en cola exhaustiva: {$reasonStr}";
                    }

                    BotTargetBlacklist::addCooldown(
                        $this->ctx->botId,
                        $ownerId,
                        $tId,
                        $tG,
                        $tS,
                        $tP,
                        $failTtl,
                        substr($typedReason, 0, 64)
                    );
                    $this->ctx->decisionLog->record(
                        'candidate_fallback_next',
                        "Candidato [{$tG}:{$tS}:{$tP}] descartado ({$typedReason}). Cooldown de " . round($failTtl / 60, 1) . "m aplicado; pasando al siguiente candidato de la cola.",
                        array('target' => $t, 'reason' => $reasonStr, 'ttl' => $failTtl),
                        "fallback_{$tG}_{$tS}_{$tP}",
                        3000.0
                    );
                }
            }

            // If ALL candidates in the radar were attempted/exhausted and none could be attacked:
            if (!$targetLocked && $candidateAttempted > 0) {
                // Do NOT freeze the bot with a 1-hour global lockout ([0:0:0]).
                // Allow dynamic next evaluation when individual candidate cooldowns expire.
                $this->ctx->decisionLog->record(
                    'CYCLE_EXHAUSTED',
                    "Todos los candidatos del radar ({$candidateAttempted} objetivos) evaluados en este ciclo; ninguno es atacable en este instante. Esperando expiración dinámica de cooldowns.",
                    array('candidates_evaluated' => $candidateAttempted),
                    "cycle_exhausted_{$this->ctx->botId}",
                    3000.0
                );
            }
        }

        // 4. PARALLEL ARCHITECTURE: EXECUTE FARMING SWEEPS IN PARALLEL (Section 2.3)
        // Dispatches independent farming squadrons to inactive farm targets without target lock!
        $farmStrikes = $this->executeParallelFarming($world, $basePlanet, $targetCandidates);

        return $campaignStrikes + $farmStrikes;
    }

    /**
     * Locks a candidate as the active campaign target in the bot table.
     */
    private function lockCandidateTarget(array $candTarget)
    {
        $t = $candTarget['target'];
        $cat = $candTarget['category'];
        $initMse = (float)($candTarget['initial_mse'] ?? 0.0);
        $bestFobId = (int)($candTarget['fob_id'] ?? 0);
        $tG = (int)$t['galaxy'];
        $tS = (int)$t['system'];
        $tP = (int)$t['planet'];

        $this->ctx->updateBotRow(array(
            'target_galaxy'      => $tG,
            'target_system'      => $tS,
            'target_planet'      => $tP,
            'target_type'        => $cat,
            'target_initial_mse' => $initMse,
            'target_lock_time'   => self::now(),
            'siege_cycles'       => 0,
            'fob_planet_id'      => $bestFobId,
            'siege_spent_mse'    => 0.0,
        ));

        $this->ctx->decisionLog->record(
            'target_locked',
            "Campaign target locked at [{$tG}:{$tS}:{$tP}] classified as {$cat} (Initial Value: " . number_format($initMse) . " MSE). Designated FOB colony #{$bestFobId}",
            array('galaxy' => $tG, 'system' => $tS, 'planet' => $tP, 'type' => $cat, 'mse' => $initMse, 'fob_id' => $bestFobId),
            "lock_{$tG}_{$tS}_{$tP}",
            5000.0
        );
    }

    /**
     * Attempts execution of a campaign target (active or candidate).
     *
     * @param BotWorldModel $world
     * @param array $activeTarget
     * @param array $basePlanet
     * @param bool $isCandidate True if evaluating an uncommitted candidate from queue
     * @return array ['success' => bool, 'strikes' => int, 'reason' => string]
     */
    private function tryExecuteCampaignTarget(
        BotWorldModel $world,
        array $activeTarget,
        array $basePlanet,
        $isCandidate = false
    ) {
        global $resource, $reslist;
        $db = Database::get();

        $t = $activeTarget['target'];
        $tG = (int)$t['galaxy'];
        $tS = (int)$t['system'];
        $tP = (int)$t['planet'];
        $tId = isset($t['planet_id']) ? (int)$t['planet_id'] : (int)($t['id'] ?? 0);
        $ownerId = (int)$t['owner_id'];

        if ($ownerId === 1) {
            if (!$isCandidate) {
                $this->releaseLock("Administrador protegido de ataques de IA", "{$tG}:{$tS}:{$tP}");
            }
            return array('success' => false, 'strikes' => 0, 'reason' => 'Administrador protegido');
        }

        if (!$isCandidate && BotTargetBlacklist::isPlayerBlacklisted($this->ctx->botId, $ownerId)) {
            $this->releaseLock("Objetivo en lista negra de 24 horas (Anti-Bashing)", "{$tG}:{$tS}:{$tP}");
            return array('success' => false, 'strikes' => 0, 'reason' => 'Objetivo en lista negra o tregua');
        } elseif ($isCandidate && BotTargetBlacklist::isBlacklisted($this->ctx->botId, $ownerId, $tId, $tG, $tS, $tP)) {
            return array('success' => false, 'strikes' => 0, 'reason' => 'Candidato en lista negra o enfriamiento temporal');
        }

        $cat = $activeTarget['category'];
        $fobId = !empty($activeTarget['fob_id']) ? (int)$activeTarget['fob_id'] : (int)$basePlanet['id'];

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

        // ONLINE vs OFFLINE Tactical Gate (Section 2.0 Apex Predator)
        $targetActivity = isset($t['activity']) ? $t['activity'] : '';
        if (empty($targetActivity)) {
            $targetUserOnline = $db->selectSingle("SELECT onlinetime FROM %%USERS%% WHERE id = :uId LIMIT 1;", array(':uId' => $ownerId));
            $idleSecs = !empty($targetUserOnline['onlinetime']) ? (self::now() - (int)$targetUserOnline['onlinetime']) : 99999;
            if ($idleSecs < 900) {
                $targetActivity = 'ONLINE';
            } elseif ($idleSecs >= 2700) {
                $targetActivity = 'OFFLINE';
            } else {
                $targetActivity = 'RECENT';
            }
        }

        $fobCoords = array((int)$fobBody['galaxy'], (int)$fobBody['system'], (int)$fobBody['planet']);
        $distSystems = abs((int)$fobCoords[1] - $tS);
        $isSameGalaxy = ((int)$fobCoords[0] === $tG);

        $isOnline = ($targetActivity === 'ONLINE');
        if ($isOnline) {
            // Defensor ONLINE (idle < 15 min): Solo autorizar si distancia <= 25 sistemas (misma galaxia)
            if (!$isSameGalaxy || $distSystems > 25) {
                return array('success' => false, 'strikes' => 0, 'reason' => 'ONLINE_DISTANCE: Defensor activo fuera de radio de intercepcion (>25 sistemas)');
            }
            // Requiere composición de intercepción rápida (Cruceros 206, Acorazados 215, Cazadores Pesados 205, Naves de Batalla 207 o Cazadores Ligeros 204 como escolta/fodder)
            $fastWarshipsCount = ($availableFleet[206] ?? 0) + ($availableFleet[215] ?? 0) + ($availableFleet[205] ?? 0) + ($availableFleet[207] ?? 0) + ($availableFleet[204] ?? 0);
            if ($fastWarshipsCount <= 0 && $cat !== 'farming') {
                return array('success' => false, 'strikes' => 0, 'reason' => 'Defensor ONLINE sin naves de intercepción rápida disponibles en FOB');
            }
        }

        // Fetch target intel
        $intelHalfLife = isset($this->ctx->difficulty->intelMaxAgeHours) ? (float)$this->ctx->difficulty->intelMaxAgeHours : 16.0;
        $intel = $world->intel->getIntel($tG, $tS, $tP, 1, $intelHalfLife);
        $targetDefense = (isset($intel['defense']) && is_array($intel['defense'])) ? $intel['defense'] : array();
        $targetFleet   = (isset($intel['fleet']) && is_array($intel['fleet'])) ? $intel['fleet'] : array();
        $targetDefenders = $targetDefense + $targetFleet;
        $targetFactors = BotCombatOracle::resolveDefenderFactors($ownerId, is_array($intel) ? $intel : array());

        // Check if strike or missiles are already in flight towards this target (Missions 1: Attack, 2: ACS Attack)
        $activeStrikes = $db->selectSingle("SELECT COUNT(*) as cnt, SUM(fleet_amount) as total_ships FROM %%FLEETS%% 
            WHERE fleet_owner = :botId 
              AND fleet_end_galaxy = :g 
              AND fleet_end_system = :s 
              AND fleet_end_planet = :p 
              AND fleet_mission IN (1, 2) 
              AND fleet_mess = 0;", array(
            ':botId' => $this->ctx->botId,
            ':g'     => $tG,
            ':s'     => $tS,
            ':p'     => $tP
        ));

        // Detect active in-flight missile salvos (Mission 10: Missile Attack)
        $activeMissiles = $db->selectSingle("SELECT COUNT(*) as cnt, MAX(fleet_start_time) as max_impact, SUM(fleet_amount) as total_mips FROM %%FLEETS%% 
            WHERE fleet_owner = :botId 
              AND fleet_end_galaxy = :g 
              AND fleet_end_system = :s 
              AND fleet_end_planet = :p 
              AND fleet_mission = 10 
              AND fleet_mess = 0;", array(
            ':botId' => $this->ctx->botId,
            ':g'     => $tG,
            ':s'     => $tS,
            ':p'     => $tP
        ));

        $hasScheduledSalvo = BotScheduler::hasPendingTaskForTarget(
            $this->ctx->botId,
            array('staggered_mip_salvo', 'staggered_fleet_assault', 'staggered_recycler_dispatch'),
            $tG,
            $tS,
            $tP
        );

        $hasAssaultInFlight = (!empty($activeStrikes) && (int)$activeStrikes['cnt'] > 0);
        $hasMissilesInFlight = (!empty($activeMissiles) && (int)$activeMissiles['cnt'] > 0);

        // Check if armada deployment (Mission 4) is currently en route to this FOB
        $inboundDeployment = $db->selectSingle("SELECT COUNT(*) as cnt, SUM(fleet_amount) as total_ships FROM %%FLEETS%% 
            WHERE fleet_owner = :botId 
              AND fleet_end_id = :fobId 
              AND fleet_mission = 4 
              AND fleet_mess = 0;", array(
            ':botId' => $this->ctx->botId,
            ':fobId' => $fobId,
        ));
        $hasDeploymentInFlight = (!empty($inboundDeployment) && (int)$inboundDeployment['cnt'] > 0);

        // If assault fleet is already in flight, OR if missiles are in flight AND assault is already scheduled:
        if ($hasAssaultInFlight || ($hasMissilesInFlight && $hasScheduledSalvo)) {
            $totalOrdnance = (int)($activeStrikes['total_ships'] ?? 0) + (int)($activeMissiles['total_mips'] ?? 0);
            $this->ctx->decisionLog->record(
                'strike_in_progress',
                "Operación táctica en curso (" . number_format($totalOrdnance) . " naves/misiles en vuelo/programados) hacia [{$tG}:{$tS}:{$tP}]. Esperando sincronización e impacto.",
                array('target' => $t, 'ships' => (int)($activeStrikes['total_ships'] ?? 0), 'mips' => (int)($activeMissiles['total_mips'] ?? 0)),
                "inflight_{$tG}_{$tS}_{$tP}",
                5000.0
            );
            return array('success' => true, 'strikes' => 0, 'reason' => 'Ataque ya en vuelo o programado hacia el objetivo');
        }

        // Check local FOB firepower win probability; if under 80%, rally armada to this FOB
        $needStagingRally = false;
        $localWinProb = 1.0;
        if (!empty($targetDefenders)) {
            $simLocal = !empty($availableFleet) ? BotCombatOracle::evaluate(
                $availableFleet,
                $targetDefenders,
                array('id' => $this->ctx->botId, 'factor' => $this->ctx->user['factor'] ?? array()),
                array('id' => $ownerId, 'factor' => BotCombatOracle::resolveDefenderFactors($ownerId, is_array($intel) ? $intel : array())),
                1
            ) : array('win_probability' => 0.0);

            $localWinProb = (float)($simLocal['win_probability'] ?? 0.0);
        } elseif (empty($availableFleet)) {
            $localWinProb = 0.0;
        }

        if (in_array($cat, array('fleet_crash', 'farming', 'siege')) && $localWinProb < 0.80) {
            // Validar viabilidad del imperio entero antes de comprometer flotas en Misión 4
            $empireCombatFleet = array();
            if (isset($world->empire)) {
                foreach ($world->empire->getAllBodies() as $b) {
                    if (empty($b['data'])) continue;
                    foreach ($reslist['fleet'] as $sId) {
                        if ($sId == 210 || $sId == 212 || in_array($sId, array(208, 209, 219))) continue;
                        $cnt = isset($b['data'][$resource[$sId]]) ? (int)$b['data'][$resource[$sId]] : 0;
                        if ($cnt > 0) {
                            $empireCombatFleet[$sId] = isset($empireCombatFleet[$sId]) ? $empireCombatFleet[$sId] + $cnt : $cnt;
                        }
                    }
                }
            }
            $simEmpire = !empty($empireCombatFleet) ? BotCombatOracle::evaluate(
                $empireCombatFleet,
                $targetDefenders,
                array('id' => $this->ctx->botId, 'factor' => $this->ctx->user['factor'] ?? array()),
                array('id' => $ownerId, 'factor' => BotCombatOracle::resolveDefenderFactors($ownerId, is_array($intel) ? $intel : array())),
                1
            ) : array('win_probability' => 0.0);
            $empireWin = (float)($simEmpire['win_probability'] ?? 0.0);

            $deployedEmpireMSE = 0.0;
            foreach ($empireCombatFleet as $sId => $cnt) {
                $deployedEmpireMSE += BotEconomyValuator::getElementPriceMSE($sId, $cnt);
            }

            $minRoiReq = isset($this->ctx->difficulty->minRoiRequired) ? (float)$this->ctx->difficulty->minRoiRequired : 0.20;
            $maxLossAllowed = isset($this->ctx->difficulty->maxOwnFleetLossRatioAllowed) ? (float)$this->ctx->difficulty->maxOwnFleetLossRatioAllowed : 0.40;

            $finEmpire = BotCombatOracle::evaluateFinancialBalance(
                $simEmpire,
                $deployedEmpireMSE,
                0.0,
                500000000000.0,
                0.0
            );

            $isViableStaging = ($empireWin >= 0.70) || (
                $finEmpire['is_net_profitable'] &&
                $finEmpire['roi'] >= $minRoiReq &&
                $finEmpire['att_loss_pct'] <= $maxLossAllowed
            );

            if (!$isViableStaging) {
                $reason = "Fuerza militar/financiera del imperio insuficiente para vencer al objetivo (" . round($empireWin * 100, 1) . "% win, ROI: " . round($finEmpire['roi'] * 100, 1) . "%).";
                if (!$isCandidate) {
                    BotTargetBlacklist::addCooldown($this->ctx->botId, $ownerId, $tId, $tG, $tS, $tP, 1800, $reason);
                    $this->releaseLock($reason, "{$tG}:{$tS}:{$tP}");
                }
                return array('success' => false, 'strikes' => 0, 'reason' => $reason);
            }

            // Intentar desplegar refuerzos desde las colonias disponibles hacia la FOB
            if (isset($world->empire)) {
                require_once 'includes/classes/bot/Forces/StagingPlanner.class.php';
                $stagingPlanner = new BotStagingPlanner($this->ctx, $this->gateway);
                $rallied = $stagingPlanner->rallyFleets($world->empire, $fobId, $targetDefenders, true);
                if ($rallied > 0) {
                    $this->ctx->decisionLog->record(
                        'campaign_staging_dispatched',
                        "Desplegada armada de refuerzo desde las colonias hacia la FOB frontera #{$fobId} para alcanzar superioridad militar contra [{$tG}:{$tS}:{$tP}].",
                        array('target' => $t, 'fob_id' => $fobId, 'rallied_fleets' => $rallied),
                        "stage_fob_{$fobId}",
                        5000.0
                    );
                    return array('success' => true, 'strikes' => 0, 'reason' => 'Refuerzos de armada desplegados hacia la FOB');
                }
            }

            // Si no se pudieron enviar nuevos rallies, pero ya hay despliegues en curso, esperar aterrizaje
            if ($hasDeploymentInFlight) {
                $this->ctx->decisionLog->record(
                    'staging_in_transit',
                    "Armada expedicionaria en vuelo de despliegue (Misión 4: " . number_format((int)($inboundDeployment['total_ships'] ?? 0)) . " naves) aproximándose a la FOB frontera #{$fobId}. Manteniendo objetivo y esperando aterrizaje para alcanzar superioridad militar sobre [{$tG}:{$tS}:{$tP}].",
                    array('target' => $t, 'fob_id' => $fobId, 'ships' => (int)($inboundDeployment['total_ships'] ?? 0)),
                    "staging_fob_{$fobId}",
                    5000.0
                );
                return array('success' => true, 'strikes' => 0, 'reason' => 'Despliegue a la FOB en vuelo necesario para alcanzar victoria local');
            }
        }

        // Execute campaign operation locally from FOB
        $campaignStrikes = 0;
        if ($cat === 'siege') {
            $campaignStrikes = $this->executeSiegeCampaign($world, $t, $activeTarget, $targetDefense, $targetDefenders, $intel, $fobData, $fobId);
        } elseif ($cat === 'fleet_crash') {
            $campaignStrikes = $this->executeFleetCrashCampaign($world, $t, $activeTarget, $targetDefense, $targetFleet, $targetDefenders, $intel, $fobData, $fobId, $availableFleet);
        } elseif ($cat === 'farming') {
            $campaignStrikes = $this->executeFarmingCampaign($world, $t, $activeTarget, $targetDefense, $targetDefenders, $intel, $fobData, $fobId);
        }

        if ($campaignStrikes > 0) {
            return array('success' => true, 'strikes' => $campaignStrikes, 'reason' => 'Ataque de campaña lanzado exitosamente');
        }

        return array('success' => false, 'strikes' => 0, 'reason' => 'No se pudo lanzar el ataque (combustible, slots, o condiciones locales no satisfechas)');
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
        $isTargetActuallyLocked = (
            !empty($this->ctx->botRow['target_galaxy']) &&
            (int)$this->ctx->botRow['target_galaxy'] === (int)$t['galaxy'] &&
            (int)$this->ctx->botRow['target_system'] === (int)$t['system'] &&
            (int)$this->ctx->botRow['target_planet'] === (int)$t['planet']
        );

        $currentSiege = isset($activeTarget['siege_cycles']) ? (int)$activeTarget['siege_cycles'] : 0;
        if ($isTargetActuallyLocked) {
            $currentSiege++;
            if ($currentSiege >= 3) {
                $reason = "Fail-Fast: Límite de horizonte de asedio alcanzado (3 ciclos sin brecha defensiva)";
                BotTargetBlacklist::addCooldown($this->ctx->botId, (int)$t['owner_id'], (int)($t['planet_id'] ?? 0), (int)$t['galaxy'], (int)$t['system'], (int)$t['planet'], 1800, $reason);
                $this->releaseLock($reason, "{$t['galaxy']}:{$t['system']}:{$t['planet']}");
                return 0;
            }
            $this->ctx->updateBotRow(array('siege_cycles' => $currentSiege));
        } else {
            $currentSiege = max(1, $currentSiege);
        }

        $targetFactors = BotCombatOracle::resolveDefenderFactors((int)$t['owner_id'], is_array($intel) ? $intel : array());
        $isOnline = (!empty($t['activity']) && $t['activity'] === 'ONLINE');

        // 1. Calculate Comprehensive Missile Model (Sections 1.1 - 1.5)
        $mModel = $this->calculateMissileModel($world, $t, $targetDefense, $intel, $fobData);

        $ipmRequerido = $mModel['ipm_requerido'];
        $totalStock   = $mModel['total_stock'];
        $canFire      = $mModel['can_fire'];
        $needsMips    = !empty($mModel['needs_mips']);
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

        // -------------------------------------------------------------
        // Sección 1.0: Detección y Sincronización Estricta (+2s) de Misiles en Vuelo
        // -------------------------------------------------------------
        $db = Database::get();
        $inFlightMips = $db->selectSingle("
            SELECT COUNT(*) as cnt, MAX(fleet_start_time) as max_impact, SUM(fleet_amount) as total_mips 
            FROM %%FLEETS%% 
            WHERE fleet_owner = :botId 
              AND fleet_end_galaxy = :g 
              AND fleet_end_system = :s 
              AND fleet_end_planet = :p 
              AND fleet_mission = 10 
              AND fleet_mess = 0;
        ", array(
            ':botId' => $this->ctx->botId,
            ':g'     => $t['galaxy'],
            ':s'     => $t['system'],
            ':p'     => $t['planet']
        ));
        $hasMipsInFlight = (!empty($inFlightMips['cnt']) && (int)$inFlightMips['cnt'] > 0);

        if ($hasMipsInFlight) {
            $lastMipImpact = (int)$inFlightMips['max_impact'];

            // Comprobar si ya existe asalto de flota en vuelo o programado
            $inFlightAssault = $db->selectSingle("
                SELECT COUNT(*) as cnt, MIN(fleet_start_time) as arrival_time 
                FROM %%FLEETS%% 
                WHERE fleet_owner = :botId 
                  AND fleet_end_galaxy = :g 
                  AND fleet_end_system = :s 
                  AND fleet_end_planet = :p 
                  AND fleet_mission IN (1, 2) 
                  AND fleet_mess = 0;
            ", array(
                ':botId' => $this->ctx->botId,
                ':g'     => $t['galaxy'],
                ':s'     => $t['system'],
                ':p'     => $t['planet']
            ));

            $hasScheduledAssault = BotScheduler::hasPendingTaskForTarget(
                $this->ctx->botId,
                array('staggered_fleet_assault'),
                $t['galaxy'],
                $t['system'],
                $t['planet']
            );

            if ((!empty($inFlightAssault['cnt']) && (int)$inFlightAssault['cnt'] > 0) || $hasScheduledAssault) {
                $this->ctx->decisionLog->record(
                    'siege_awaiting_coordinated_impact',
                    "Tactical siege: Salva de misiles en vuelo (" . number_format((int)$inFlightMips['total_mips']) . " MIPs) impactando a las " . date('H:i:s', $lastMipImpact) . " con asalto de flota coordinado en marcha hacia [{$t['galaxy']}:{$t['system']}:{$t['planet']}]. Esperando arribo.",
                    array('target' => $t, 'impact_time' => $lastMipImpact, 'mips' => (int)$inFlightMips['total_mips']),
                    "siege_coord_{$t['galaxy']}_{$t['system']}_{$t['planet']}",
                    4500.0
                );
                return 0;
            }

            // Los misiles vuelan pero falta la flota de asalto (por reanudación o cancelación previa):
            // Sincronizar armada atacante para arribar EXACTAMENTE a lastMipImpact + 2 segundos
            $strikeFleet = $this->buildTacticalStrikeFleet($fobData, $targetDefenders, $lootNeeded, 'siege', $targetFactors, $isOnline);
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
                return 0;
            }

            $fleetArrival = $lastMipImpact + 2;
            $fleetDelay = $fleetArrival - $fleetDuration - self::now();
            $selectedSpeedFactor = 10;

            if ($fleetDelay > 5) {
                for ($sp = 9; $sp >= 1; $sp--) {
                    $spDur = FleetFunctions::GetMissionDuration($sp, $fleetSpeed, $fDist, $gameSpeed, $this->ctx->user);
                    $spArrival = self::now() + $spDur;
                    if ($spArrival >= ($lastMipImpact + 2) && $spArrival <= ($lastMipImpact + 3)) {
                        $selectedSpeedFactor = $sp;
                        $fleetArrival = $spArrival;
                        $fleetDuration = $spDur;
                        $fleetDelay = 0;
                        break;
                    }
                }
            }

            if ($fleetDelay <= 3) {
                if ($fleetDelay > 0) {
                    self::sleepIfReal($fleetDelay);
                }
                $this->gateway->launchFleet(
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
                        'speed'            => $selectedSpeedFactor,
                    ),
                    self::now() + $fleetDelay
                );
            }

            $this->ctx->decisionLog->record(
                'siege_salvo_fleet_synchronized',
                "Tactical siege: Armada sincronizada con misiles en vuelo (" . number_format(array_sum($strikeFleet)) . " naves a velocidad {$selectedSpeedFactor}0%) hacia [{$t['galaxy']}:{$t['system']}:{$t['planet']}]. Arribo fijado a las " . date('H:i:s', $fleetArrival) . " (T_impacto_misil + 2s).",
                array('target' => $t, 'fleet' => $strikeFleet, 'arrival' => $fleetArrival, 'delay' => $fleetDelay),
                "siege_sync_assault_{$t['galaxy']}_{$t['system']}_{$t['planet']}",
                6000.0
            );

            return 1;
        }

        if ($ipmRequerido > 0) {
            // Section 1.1: Global Stock Check & Direct Assault Bypass
            if (!$canFire) {
                // BYPASS EVALUATION: Si no hay misiles suficientes pero el asalto con flota es rentable (ROI >= 30%)
                $directFleet = $this->buildTacticalStrikeFleet($fobData, $targetDefenders, $lootNeeded, 'siege', $targetFactors, $isOnline);
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

                        // Speed & Fuel Optimization
                        $isDefInactive = !empty($intel['is_inactive']);
                        $optSpeed = $this->determineOptimalAttackSpeed($directFleet, $fobData, $t, 'siege', $isDefInactive, $fDist, $lootMSE);

                        // Si falta combustible en la FOB pero el asalto tiene un ROI >= 30%, activar el Mercader Táctico (50 MO)
                        if ($optSpeed === null) {
                            $targetActivity = isset($t['activity']) ? $t['activity'] : '';
                            $isTargetOffline = ($targetActivity === 'OFFLINE' || $isDefInactive);
                            $evalSpeed = $isTargetOffline ? 7 : 10;
                            $evalDur = FleetFunctions::GetMissionDuration($evalSpeed, $fSpeed, $fDist, $gSpeed, $this->ctx->user);
                            $fuelRequired = FleetFunctions::GetFleetConsumption($directFleet, $evalDur, $fDist, $this->ctx->user, $gSpeed);
                            $fuelMSE = BotEconomyValuator::toMSE(0, 0, (float)$fuelRequired);
                            $grossRevenue = $lootMSE + ($defenderFleetMSE * $defLossPct * $fCdr) + ($defenderDefMSE * $defLossPct * $dCdr);
                            $netProfitTheo = $grossRevenue - (($estimatedLossMSE * (1.0 - $fCdr)) + $fuelMSE);
                            $roiTheo = $netProfitTheo / max(1.0, ($estimatedLossMSE + $fuelMSE));

                            // Si ROI >= 30% y ganancia neta positiva, justificar la compra del deuterio faltante por 50 MO
                            $userMO = (float)($this->ctx->user['darkmatter'] ?? 0.0);
                            $stockDeut = (float)($fobData['deuterium'] ?? 0.0);
                            if ($roiTheo >= 0.30 && $netProfitTheo > 0 && $userMO >= 50.0 && $stockDeut < $fuelRequired) {
                                $missingDeut = ceil(($fuelRequired - $stockDeut) * 1.05); // +5% de margen
                                $strategist = new BotPremiumStrategist($this->ctx, $this->gateway);
                                $tradeOk = $strategist->executeExactDeltaTrade((int)$fobId, array(903 => $missingDeut), 'offensive_strike_fuel');
                                if ($tradeOk) {
                                    $fobData = Database::get()->selectSingle("SELECT * FROM %%PLANETS%% WHERE id = :pid LIMIT 1;", array(':pid' => (int)$fobId));
                                    $optSpeed = $this->determineOptimalAttackSpeed($directFleet, $fobData, $t, 'siege', $isDefInactive, $fDist, $lootMSE);
                                }
                            }
                        }

                        if ($optSpeed !== null) {
                            $fuelConsumption = $optSpeed['fuel'];
                            $fuelMSE = BotEconomyValuator::toMSE(0, 0, (float)$fuelConsumption);

                            // Costo neto: El atacante recicla el 70% de sus propias pérdidas en escombros
                            $netReplacementCost = ($estimatedLossMSE * (1.0 - $fCdr)) + $fuelMSE;
                            $grossRevenue = $lootMSE + ($defenderFleetMSE * $defLossPct * $fCdr) + ($defenderDefMSE * $defLossPct * $dCdr);
                            $netProfit = $grossRevenue - $netReplacementCost;
                            $roi = $netProfit / max(1.0, ($estimatedLossMSE + $fuelMSE));

                            // Sin límite de bajas: si ROI >= 30% y ganancia neta positiva, avanzar
                            if ($netProfit > 0 && round($roi, 4) >= 0.30) {
                                $canDirectAssault = true;
                                $strikeFleet = $directFleet;
                            }
                        }
                    }
                }

                if (!$canDirectAssault) {
                    $logMsg = (!$needsMips)
                        ? "Tactical siege (Paso {$currentSiege}/3): Salva de misiles no rentable o innecesaria ({$totalStock} MIPs disponibles) y asalto directo no rentable (ROI < 30%). Bunker protegido; aplicando enfriamiento táctico y liberando blanco."
                        : "Tactical siege (Paso {$currentSiege}/3): Stock de misiles en silos en rango ({$totalStock} MIPs) < IPM_requerido ({$ipmRequerido} MIPs) y asalto directo no rentable (ROI < 30%). Aplicando enfriamiento para evaluar otros objetivos.";

                    $this->ctx->decisionLog->record(
                        'siege_accumulating_mips',
                        $logMsg,
                        array('target' => $t, 'available_mips' => $totalStock, 'required_mips' => $ipmRequerido, 'step' => $currentSiege, 'needs_mips' => $needsMips),
                        "siege_accumulate_{$t['galaxy']}_{$t['system']}_{$t['planet']}",
                        2500.0
                    );

                    $cdSeconds = mt_rand(1800, 3600);
                    BotTargetBlacklist::addCooldown(
                        $this->ctx->botId,
                        (int)$t['owner_id'],
                        (int)($t['planet_id'] ?? 0),
                        (int)$t['galaxy'],
                        (int)$t['system'],
                        (int)$t['planet'],
                        $cdSeconds,
                        'UNPROFITABLE_DEFENSE: Bunker no rentable ni por misiles ni por asalto directo (ROI < 30%)'
                    );

                    $this->releaseLock("Bunker no rentable ni por misiles ni por asalto directo", "{$t['galaxy']}:{$t['system']}:{$t['planet']}");

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
                        array('roi' => $roi, 'net_profit' => $netProfit),
                        $world
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

                $strikeFleet = $this->buildTacticalStrikeFleet($fobData, $targetDefenders, $lootNeeded, 'siege', $targetFactors, $isOnline);
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

                $mipLead = method_exists($this->ctx->difficulty, 'getMipImpactLeadSeconds')
                    ? (int)$this->ctx->difficulty->getMipImpactLeadSeconds()
                    : 2;

                $recOffset = method_exists($this->ctx->difficulty, 'getRecyclerStrikeOffset')
                    ? (int)$this->ctx->difficulty->getRecyclerStrikeOffset()
                    : 2;

                // Debris esperado del objetivo de asedio (defensas y flotas)
                $cfg = Config::get();
                $fCdr = ((float)$cfg->Fleet_Cdr) / 100.0;
                $dCdr = ((float)$cfg->Defs_Cdr) / 100.0;
                $targetFleetMSE = 0.0;
                if ($fCdr > 0 && !empty($intel['fleet']) && is_array($intel['fleet'])) {
                    foreach ($intel['fleet'] as $fId => $cnt) {
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

                $recyclerFleet = array();
                $dRec = 0;
                if ($expectedDebris > 10000.0) {
                    $recyclerFleet = $this->buildRecyclerFleet($fobData, $expectedDebris);
                    if (!empty($recyclerFleet)) {
                        $recSpeed = FleetFunctions::GetFleetMaxSpeed($recyclerFleet, $this->ctx->user);
                        $dRec = FleetFunctions::GetMissionDuration(10, $recSpeed, $fDist, $gameSpeed, $this->ctx->user);
                    }
                }

                // REGLA ESTRICTA DE SINCRONIZACIÓN MILITAR (BotWarfareDoctrine v3.4):
                // Flota arriba a T_impacto_misiles + mipLead
                // Recicladores arriban a T_impacto_flota + recOffset
                $selectedSpeedFactor = 10;
                $recSlowExtra = (!empty($recyclerFleet) && ($dRec - $recOffset) > $fleetDuration)
                    ? (($dRec - $recOffset) - $fleetDuration)
                    : 0;
                $mipSlowExtra = ($maxMipDuration + $mipLead > $fleetDuration)
                    ? (($maxMipDuration + $mipLead) - $fleetDuration)
                    : 0;
                $extraLead = max($recSlowExtra, $mipSlowExtra);

                $fleetArrival = self::now() + $fleetDuration + $extraLead;
                $targetImpactTime = $fleetArrival - $mipLead;
                $fleetDelay = $extraLead;
                $recDelay = !empty($recyclerFleet) ? max(0, ($fleetArrival + $recOffset) - $dRec - self::now()) : 0;

                $firedImmediate = 0;
                $scheduledCount = 0;
                $totalFiredMips = 0;

                foreach ($dispatches as $disp) {
                    $dispDelay = max(0, $targetImpactTime - $disp['duration'] - self::now());
                    $totalFiredMips += $disp['count'];

                    if ($dispDelay <= 10) {
                        if ($dispDelay > 0) {
                            self::sleepIfReal($dispDelay);
                        }
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
                            self::now() + $dispDelay
                        );
                        $scheduledCount++;
                    }
                }

                // Despacho sincronizado: la flota despega primero (o los recicladores primero si son más lentos)
                if (!empty($recyclerFleet) && $recDelay <= $fleetDelay) {
                    if ($recDelay <= 3) {
                        if ($recDelay > 0) self::sleepIfReal($recDelay);
                        $this->gateway->launchFleet(
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
                            10
                        );
                    } else {
                        BotScheduler::scheduleTask(
                            $this->ctx->botId,
                            'staggered_recycler_dispatch',
                            array(
                                'fleet'            => $recyclerFleet,
                                'fob_id'           => $fobId,
                                'target_planet_id' => $t['planet_id'],
                                'target_owner_id'  => 0,
                                'target_galaxy'    => $t['galaxy'],
                                'target_system'    => $t['system'],
                                'target_planet'    => $t['planet'],
                                'speed'            => 10,
                            ),
                            self::now() + $recDelay
                        );
                    }
                }

                // Launch coordinated siege fleet timed to arrive +mipLead post-missile impact
                if ($fleetDelay <= 3) {
                    if ($fleetDelay > 0) {
                        self::sleepIfReal($fleetDelay);
                    }
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
                            'speed'            => $selectedSpeedFactor,
                        ),
                        self::now() + $fleetDelay
                    );
                }

                if (!empty($recyclerFleet) && $recDelay > $fleetDelay) {
                    if ($recDelay <= 3) {
                        if ($recDelay > 0) self::sleepIfReal($recDelay);
                        $this->gateway->launchFleet(
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
                            10
                        );
                    } else {
                        BotScheduler::scheduleTask(
                            $this->ctx->botId,
                            'staggered_recycler_dispatch',
                            array(
                                'fleet'            => $recyclerFleet,
                                'fob_id'           => $fobId,
                                'target_planet_id' => $t['planet_id'],
                                'target_owner_id'  => 0,
                                'target_galaxy'    => $t['galaxy'],
                                'target_system'    => $t['system'],
                                'target_planet'    => $t['planet'],
                                'speed'            => 10,
                            ),
                            self::now() + $recDelay
                        );
                    }
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
                    "Tactical siege: Salva coordinada única de {$totalFiredMips}x MIPs ejecutada desde " . count($dispatches) . " bases contra [{$t['galaxy']}:{$t['system']}:{$t['planet']}] apuntando a {$targetName} sincronizada con arribo de armada (" . number_format(array_sum($strikeFleet)) . " naves a velocidad {$selectedSpeedFactor}0%) a las " . date('H:i:s', $fleetArrival) . " (T_impacto_misiles: " . date('H:i:s', $targetImpactTime) . ", Arribo_flota: +2s | Inmediatos: {$firedImmediate}, Escalonados: {$scheduledCount})",
                    array('target' => $t, 'total_mips' => $totalFiredMips, 'primary' => $primaryTarget, 'step' => $currentSiege, 'spent_mse' => $spentSalvoMSE, 'fleet_ships' => array_sum($strikeFleet), 'impact_time' => $targetImpactTime, 'fleet_arrival' => $fleetArrival),
                    "siege_salvo_{$t['galaxy']}_{$t['system']}_{$t['planet']}_{$currentSiege}",
                    5500.0
                );

                return 1;
            }
        }

        // Section 3: If no missiles needed (bunker already broken or none present), execute mop-up fleet assault
        // PRECAUCIÓN ESTRICTA: Si hay misiles en vuelo hacia este objetivo, NUNCA lanzar ataque de limpieza por adelantado
        if ($hasMipsInFlight) {
            return 0;
        }

        $strikeFleet = $this->buildTacticalStrikeFleet($fobData, $targetDefenders, $lootNeeded, 'siege', $targetFactors, $isOnline);
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

        $targetActivity = isset($t['activity']) ? $t['activity'] : '';
        if (empty($targetActivity) && !empty($activeTarget['target']['activity'])) {
            $targetActivity = $activeTarget['target']['activity'];
        }
        if (empty($targetActivity)) {
            $db = Database::get();
            $targetUserOnline = $db->selectSingle("SELECT onlinetime FROM %%USERS%% WHERE id = :uId LIMIT 1;", array(':uId' => (int)$t['owner_id']));
            $idleSecs = !empty($targetUserOnline['onlinetime']) ? (self::now() - (int)$targetUserOnline['onlinetime']) : 99999;
            $targetActivity = ($idleSecs < 900) ? 'ONLINE' : (($idleSecs >= 2700) ? 'OFFLINE' : 'RECENT');
        }
        $isOnline = ($targetActivity === 'ONLINE');

        $targetFactors = BotCombatOracle::resolveDefenderFactors((int)$t['owner_id'], is_array($intel) ? $intel : array());
        $strikeFleet = $this->buildTacticalStrikeFleet($fobData, $targetDefenders, $lootNeeded, 'fleet_crash', $targetFactors, $isOnline);
        if (empty($strikeFleet)) {
            return 0;
        }

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
        $availableRecCap = ($recs219 * 200000000.0) + ($recs209 * 20000.0);

        if ($availableRecCap < $expectedDebris && isset($world->empire)) {
            $allPlanets = $world->empire->getPlanets();
            foreach ($allPlanets as $pId => $pData) {
                if ($pId == $fobId) continue;
                $e219 = isset($pData[$resource[219]]) ? (int)$pData[$resource[219]] : 0;
                $e209 = isset($pData[$resource[209]]) ? (int)$pData[$resource[209]] : 0;
                $availableRecCap += ($e219 * 200000000.0) + ($e209 * 20000.0);
            }
        }
        $realizableDebris = ($availableRecCap > 0) ? min($expectedDebris, $availableRecCap) : 0.0;

        $cfg = Config::get();
        $fCdr = ((float)$cfg->Fleet_Cdr) / 100.0;
        $lootMSE = BotEconomyValuator::toMSE(
            (float)($intel['metal'] ?? 0) * 0.50,
            (float)($intel['crystal'] ?? 0) * 0.50,
            (float)($intel['deuterium'] ?? 0) * 0.50
        );

        $fin = BotCombatOracle::evaluateFinancialBalance(
            $sim,
            $deployedFleetMSE,
            0.0,
            $availableRecCap,
            $lootMSE
        );

        $netProfit = $fin['net_profit_mse'];
        $roi       = $fin['roi'];

        $minRoiReq      = isset($this->ctx->difficulty->minRoiRequired) ? (float)$this->ctx->difficulty->minRoiRequired : 0.20;
        $maxLossAllowed = isset($this->ctx->difficulty->maxOwnFleetLossRatioAllowed) ? (float)$this->ctx->difficulty->maxOwnFleetLossRatioAllowed : 0.40;
        $minRecCov      = isset($this->ctx->difficulty->minRecyclerCoverageRequired) ? (float)$this->ctx->difficulty->minRecyclerCoverageRequired : 0.70;

        // Operación viable si:
        // A) Victoria convencional (>=80%) con ganancia neta positiva
        // B) O trade financiero rentable que cumpla ROI, cobertura de reciclaje y tolerancia de bajas según dificultad
        $isViableExecution = ($netProfit > 0 && (
            ($winProb >= 0.80) ||
            ($roi >= $minRoiReq && $fin['att_loss_pct'] <= $maxLossAllowed && $fin['recycler_coverage'] >= $minRecCov)
        ));

        if (!$isViableExecution) {
            $isTargetActuallyLocked = (
                !empty($this->ctx->botRow['target_galaxy']) &&
                (int)$this->ctx->botRow['target_galaxy'] === (int)$t['galaxy'] &&
                (int)$this->ctx->botRow['target_system'] === (int)$t['system'] &&
                (int)$this->ctx->botRow['target_planet'] === (int)$t['planet']
            );

            if ($isTargetActuallyLocked) {
                $currentHold = isset($this->ctx->botRow['siege_cycles']) ? (int)$this->ctx->botRow['siege_cycles'] : 0;
                $newHold = $currentHold + 1;
                if ($newHold >= 3) {
                    $reason = "Fail-Fast: Fuerza en FOB insuficiente o batalla no rentable (" . round($winProb * 100, 1) . "% victoria, ROI: " . round($roi * 100, 1) . "%, ganancia neta: " . number_format($netProfit) . " MSE). Límite de 3 ciclos alcanzado.";
                    BotTargetBlacklist::addCooldown($this->ctx->botId, (int)$t['owner_id'], (int)($t['planet_id'] ?? 0), (int)$t['galaxy'], (int)$t['system'], (int)$t['planet'], 1800, $reason);
                    $this->releaseLock($reason, "{$t['galaxy']}:{$t['system']}:{$t['planet']}");
                    return 0;
                }
                $this->ctx->updateBotRow(array('siege_cycles' => $newHold));
            }
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
            array('eval' => $sim, 'net_profit' => $netProfit),
            $world
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

        $targetActivity = isset($t['activity']) ? $t['activity'] : '';
        if (empty($targetActivity) && !empty($activeTarget['target']['activity'])) {
            $targetActivity = $activeTarget['target']['activity'];
        }
        if (empty($targetActivity)) {
            $db = Database::get();
            $targetUserOnline = $db->selectSingle("SELECT onlinetime FROM %%USERS%% WHERE id = :uId LIMIT 1;", array(':uId' => (int)$t['owner_id']));
            $idleSecs = !empty($targetUserOnline['onlinetime']) ? (self::now() - (int)$targetUserOnline['onlinetime']) : 99999;
            $targetActivity = ($idleSecs < 900) ? 'ONLINE' : (($idleSecs >= 2700) ? 'OFFLINE' : 'RECENT');
        }
        $isOnline = ($targetActivity === 'ONLINE');
        $targetFactors = BotCombatOracle::resolveDefenderFactors((int)$t['owner_id'], is_array($intel) ? $intel : array());

        $strikeFleet = $this->buildTacticalStrikeFleet($fobData, $targetDefenders, $lootNeeded, 'farming', $targetFactors, $isOnline);
        if (empty($strikeFleet)) {
            $strikeFleet = $this->buildTacticalStrikeFleet($fobData, $targetDefenders, $lootNeeded, 'fleet_crash', $targetFactors, $isOnline);
            if (empty($strikeFleet)) {
                return 0;
            }
        }

        // Evaluate with Combat Oracle
        $sim = BotCombatOracle::evaluate(
            $strikeFleet,
            $targetDefenders,
            array('id' => $this->ctx->botId, 'factor' => $this->ctx->user['factor']),
            array('id' => $t['owner_id'], 'factor' => $targetFactors),
            2
        );

        $winProb = isset($sim['win_probability']) ? (float)$sim['win_probability'] : 0.0;
        if ($winProb < 0.80) {
            $strikeFleet = $this->buildTacticalStrikeFleet($fobData, $targetDefenders, $lootNeeded, 'fleet_crash', $targetFactors, $isOnline);
            $sim = BotCombatOracle::evaluate(
                $strikeFleet,
                $targetDefenders,
                array('id' => $this->ctx->botId, 'factor' => $this->ctx->user['factor']),
                array('id' => $t['owner_id'], 'factor' => $targetFactors),
                2
            );
            $winProb = isset($sim['win_probability']) ? (float)$sim['win_probability'] : 0.0;
            if ($winProb < 0.80) {
                $isTargetActuallyLocked = (
                    !empty($this->ctx->botRow['target_galaxy']) &&
                    (int)$this->ctx->botRow['target_galaxy'] === (int)$t['galaxy'] &&
                    (int)$this->ctx->botRow['target_system'] === (int)$t['system'] &&
                    (int)$this->ctx->botRow['target_planet'] === (int)$t['planet']
                );

                if ($isTargetActuallyLocked) {
                    $currentHold = isset($this->ctx->botRow['siege_cycles']) ? (int)$this->ctx->botRow['siege_cycles'] : 0;
                    $newHold = $currentHold + 1;
                    if ($newHold >= 3) {
                        $reason = "Fail-Fast: Límite de horizonte de campaña farming alcanzado (3 ciclos con probabilidad de victoria < 80%)";
                        BotTargetBlacklist::addCooldown($this->ctx->botId, (int)$t['owner_id'], (int)($t['planet_id'] ?? 0), (int)$t['galaxy'], (int)$t['system'], (int)$t['planet'], 1800, $reason);
                        $this->releaseLock($reason, "{$t['galaxy']}:{$t['system']}:{$t['planet']}");
                        return 0;
                    }
                    $this->ctx->updateBotRow(array('siege_cycles' => $newHold));
                }
                return 0;
            }
        }

        $maxSlots     = FleetFunctions::GetMaxFleetSlots($this->ctx->user);
        $actualFleets = FleetFunctions::GetCurrentFleets($this->ctx->botId);
        if ($actualFleets >= $maxSlots) {
            return 0;
        }

        $fCoords = array($fobData['galaxy'], $fobData['system'], $fobData['planet']);
        $tCoords = array($t['galaxy'], $t['system'], $t['planet']);
        $fDist   = FleetFunctions::GetTargetDistance($fCoords, $tCoords);
        $lootMSE = BotEconomyValuator::toMSE($lootNeeded['metal'], $lootNeeded['crystal'], $lootNeeded['deuterium']);
        $optSpeed = $this->determineOptimalAttackSpeed($strikeFleet, $fobData, $t, 'farming', !empty($intel['is_inactive']), $fDist, $lootMSE);
        if ($optSpeed === null) {
            return 0;
        }
        $speedToSend = (int)$optSpeed['speed'];

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
            $speedToSend
        );

        if ($ok) {
            $this->ctx->decisionLog->record(
                'farming_campaign_launched',
                "Farming campaign strike dispatched from FOB #{$fobId} against [{$t['galaxy']}:{$t['system']}:{$t['planet']}] at {$speedToSend}0% speed with " . number_format(array_sum($strikeFleet)) . " ships (Loot capacity focused, Win prob: " . round($winProb * 100, 1) . "%)",
                array('target' => $t, 'fleet' => $strikeFleet, 'loot_needed' => $lootNeeded, 'speed' => $speedToSend),
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
    public function calculateMissileModel(BotWorldModel $world, array $target, array $targetDefense, $intel, array $fobData = array())
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

        // Armar la flota táctica atacante del bot para la simulación real de bajas y blanco primario
        $defenderFleetOnTarget = isset($target['fleet']) && is_array($target['fleet']) ? $target['fleet'] : array();
        $doctrineFleet = array();
        if (!empty($fobData)) {
            $simLootNeeded = array(
                'metal'     => isset($intel['metal']) ? (float)$intel['metal'] * 0.50 : 0.0,
                'crystal'   => isset($intel['crystal']) ? (float)$intel['crystal'] * 0.50 : 0.0,
                'deuterium' => isset($intel['deuterium']) ? (float)$intel['deuterium'] * 0.50 : 0.0
            );
            $targetFactors = BotCombatOracle::resolveDefenderFactors((int)$target['owner_id'], is_array($intel) ? $intel : array());
            $doctrineFleet = $this->buildTacticalStrikeFleet($fobData, $targetDefense, $simLootNeeded, 'siege', $targetFactors, false);
        }

        // Selección quirúrgica de blanco primario según doctrina de flota en NovaRush
        $surgicalTarget = BotCombatOracle::selectSurgicalPrimaryTarget($targetDefense, $defenderFleetOnTarget);
        if ($surgicalTarget > 0 && isset($targetDefense[$surgicalTarget])) {
            $primaryTarget = $surgicalTarget;
        } else {
            arsort($priorities);
            $primaryTarget = !empty($priorities) ? key($priorities) : 401;
        }

        // Section 1.4: Single optimal surgical salvo calculation
        $abm = isset($targetDefense[502]) ? (int)$targetDefense[502] : 0;
        $abmMargin = ($spyTech_atk >= 8) ? 0 : (int)ceil(0.15 * $abm);
        $totalShotsDefs = array_sum($shotsNeeded);
        $shotsPrimary = isset($shotsNeeded[$primaryTarget]) ? (int)$shotsNeeded[$primaryTarget] : 1;

        // Disparos requeridos para erradicar unidades de alta letalidad (Plasma 406, Gauss 404, Láser Pesado 403)
        $lethalShots = 0;
        foreach (array(406, 404, 405, 403) as $lId) {
            if (!empty($shotsNeeded[$lId])) {
                $lethalShots += (int)$shotsNeeded[$lId];
            }
        }

        // Salva quirúrgica: anula ABMs + blanco primario / letal sin forzar la demolición de todo el forraje
        $shotsToBreach = max($shotsPrimary, min($totalShotsDefs, $lethalShots > 0 ? $lethalShots : (int)ceil($totalShotsDefs * 0.4)));
        $ipmRequerido = $abm + $abmMargin + $shotsToBreach;

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

        $canFire = ($totalStockAvailable >= $ipmRequerido && $ipmRequerido > 0);
        $needsMips = false;

        if ($ipmRequerido > 0) {
            $missileSpentMSE = $ipmRequerido * BotEconomyValuator::getElementPriceMSE(503, 1);
            $targetOwnerId = isset($target['owner_id']) ? (int)$target['owner_id'] : 0;
            $defenderFactors = BotCombatOracle::resolveDefenderFactors($targetOwnerId, is_array($intel) ? $intel : array());

            // 1. Simulación sin misiles
            $simSinMIP = BotCombatOracle::evaluate(
                $doctrineFleet,
                $targetDefense,
                array('id' => $this->ctx->botId, 'factor' => $this->ctx->user['factor'] ?? array()),
                array('id' => $targetOwnerId, 'factor' => $defenderFactors),
                1
            );
            $ownLossSinMIP = isset($simSinMIP['att_loss_mse']) ? (float)$simSinMIP['att_loss_mse'] : 0.0;
            $winProbSinMIP = isset($simSinMIP['win_probability']) ? (float)$simSinMIP['win_probability'] : 0.0;

            // 2. Simulación con misiles (brecha de misiles aplicada)
            $breach = BotCombatOracle::simulateMIPBreach(
                $targetDefense,
                $ipmRequerido,
                $abm,
                $weaponTech_atk,
                $targetDefTech,
                $primaryTarget
            );
            $remainingDefs = $breach['remaining'];

            $simConMIP = BotCombatOracle::evaluate(
                $doctrineFleet,
                $remainingDefs,
                array('id' => $this->ctx->botId, 'factor' => $this->ctx->user['factor'] ?? array()),
                array('id' => $targetOwnerId, 'factor' => $defenderFactors),
                1
            );
            $ownLossConMIP = isset($simConMIP['att_loss_mse']) ? (float)$simConMIP['att_loss_mse'] : 0.0;
            $winProbConMIP = isset($simConMIP['win_probability']) ? (float)$simConMIP['win_probability'] : 0.0;

            // Botín proyectado
            $lootMetal = (float)($intel['metal'] ?? 0) * 0.50;
            $lootCry   = (float)($intel['crystal'] ?? 0) * 0.50;
            $lootDeut  = (float)($intel['deuterium'] ?? 0) * 0.50;
            $lootMSE   = BotEconomyValuator::toMSE($lootMetal, $lootCry, $lootDeut);

            $deltaLoot = ($winProbConMIP * $lootMSE) - ($winProbSinMIP * $lootMSE);
            $deltaGain = ($ownLossSinMIP - $ownLossConMIP) + $deltaLoot;
            $evConMIP  = (float)($simConMIP['net_profit_mse'] ?? ($winProbConMIP * $lootMSE - $ownLossConMIP)) - $missileSpentMSE;

            if (($deltaGain >= (1.10 * $missileSpentMSE) || (empty($doctrineFleet) && $lootMSE >= (2.0 * $missileSpentMSE))) && $evConMIP > 0) {
                $needsMips = true;
            } else {
                $needsMips = false;
                $canFire = false;
            }
        }

        return array(
            'ipm_requerido' => $ipmRequerido,
            'total_stock'   => $totalStockAvailable,
            'can_fire'      => ($canFire && $needsMips),
            'needs_mips'    => $needsMips,
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
        $availableMil = BotActionGateway::getAvailableMilitarySlots($this->ctx->botId, $this->ctx->user);
        $isCampaignLocked = (!empty($this->ctx->botRow['target_type']) && in_array($this->ctx->botRow['target_type'], array('siege', 'fleet_crash', 'farming')));
        $effectiveMaxSlots = $isCampaignLocked ? max(0, $maxSlots - 1) : $maxSlots;
        $freeSlots = min($availableMil, max(0, $effectiveMaxSlots - $curFleets));

        $db = Database::get();
        $maxConcurrentStrikes = method_exists($this->ctx->difficulty, 'getMaxConcurrentStrikes')
            ? (int)$this->ctx->difficulty->getMaxConcurrentStrikes()
            : 2;
        $activeStrikesRow = $db->selectSingle("SELECT COUNT(*) as cnt FROM %%FLEETS%% WHERE fleet_owner = :botId AND fleet_mission = 1 AND fleet_mess = 0;", array(
            ':botId' => $this->ctx->botId,
        ));
        $activeAttacks = !empty($activeStrikesRow) ? (int)$activeStrikesRow['cnt'] : 0;
        $allowedNewStrikes = max(0, $maxConcurrentStrikes - $activeAttacks);

        if ($allowedNewStrikes <= 0 || $freeSlots <= 0 || !is_array($targetCandidates) || empty($targetCandidates)) {
            return 0;
        }
        $freeSlots = min($freeSlots, $allowedNewStrikes);

        $lockedGalaxy = isset($this->ctx->botRow['target_galaxy']) ? (int)$this->ctx->botRow['target_galaxy'] : 0;
        $lockedSystem = isset($this->ctx->botRow['target_system']) ? (int)$this->ctx->botRow['target_system'] : 0;
        $lockedPlanet = isset($this->ctx->botRow['target_planet']) ? (int)$this->ctx->botRow['target_planet'] : 0;

        $dispatchedFarms = 0;

        foreach ($targetCandidates as $cand) {
            $curActiveFleets = FleetFunctions::GetCurrentFleets($this->ctx->botId);
            if ($curActiveFleets >= $effectiveMaxSlots || $dispatchedFarms >= $freeSlots || $dispatchedFarms >= $allowedNewStrikes) break;

            if ($cand['category'] !== 'farming' && ($cand['category'] !== 'recon' || empty($cand['target']['is_inactive']))) {
                continue;
            }

            $t = $cand['target'];
            // Exclude locked campaign target
            if ($t['galaxy'] == $lockedGalaxy && $t['system'] == $lockedSystem && $t['planet'] == $lockedPlanet) {
                continue;
            }

            // NovaRush Anti-Bashing: Skip blacklisted players/planets & respect max 6 attacks/24h
            $tId = isset($t['planet_id']) ? (int)$t['planet_id'] : (int)($t['id'] ?? 0);
            $ownerId = (int)$t['owner_id'];
            if (BotTargetBlacklist::isBlacklisted($this->ctx->botId, $ownerId, $tId, (int)$t['galaxy'], (int)$t['system'], (int)$t['planet'])) {
                continue;
            }
            $attacksRemaining = BotTargetBlacklist::getRemainingAttacks($this->ctx->botId, $ownerId);
            if ($attacksRemaining <= 0) {
                continue;
            }

            $candLootMSE = isset($cand['loot_mse']) ? (float)$cand['loot_mse'] : 0.0;
            $isMacroInactive = (!empty($cand['target']['is_inactive']) && $candLootMSE >= 500000000.0);
            $maxAllowedWaves = $isMacroInactive ? min(3, $attacksRemaining) : 1;

            // Section 2.3: Regeneration Cooldown check (cooldown(t) = lootUmbral / produccion(t))
            $actionKey = "farm_{$t['galaxy']}_{$t['system']}_{$t['planet']}";
            $lastRaid = $db->selectSingle("SELECT MAX(created_at) as last_time FROM " . DB_PREFIX . "bot_decisions 
                WHERE bot_id = :botId AND chosen_action = :act;", array(
                ':botId' => $this->ctx->botId,
                ':act'   => $actionKey,
            ));

            if (!empty($lastRaid['last_time']) && !$isMacroInactive) {
                $lootUmbral = 500000.0;
                $hourlyProd = isset($cand['prod_mse_hourly']) && $cand['prod_mse_hourly'] > 1000.0 ? (float)$cand['prod_mse_hourly'] : 50000.0;
                $cooldownHours = max(1.0, min(72.0, $lootUmbral / $hourlyProd));
                $cooldownSecs  = (int)round($cooldownHours * 3600.0);
                $elapsed = self::now() - (int)$lastRaid['last_time'];
                if ($elapsed < $cooldownSecs) {
                    continue; // Skip: target resources are still regenerating under cooldown
                }
            }

            // Exclude targets that already have max concurrent in-flight fleets heading to them
            $inFlight = $db->selectSingle("SELECT COUNT(*) as cnt FROM %%FLEETS%% 
                WHERE fleet_owner = :botId AND fleet_end_galaxy = :g AND fleet_end_system = :s AND fleet_end_planet = :p AND fleet_mess = 0;", array(
                ':botId' => $this->ctx->botId, ':g' => $t['galaxy'], ':s' => $t['system'], ':p' => $t['planet']
            ));
            $currentInFlight = !empty($inFlight['cnt']) ? (int)$inFlight['cnt'] : 0;
            if ($currentInFlight >= $maxAllowedWaves) {
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

            // Fetch defender fleet and defenses from intelligence
            $targetDefenders = array();
            $intel = null;
            if ($world !== null && isset($world->intel)) {
                $intelHalfLife = isset($this->ctx->difficulty->intelMaxAgeHours) ? (float)$this->ctx->difficulty->intelMaxAgeHours : 16.0;
                $intel = $world->intel->getIntel((int)$t['galaxy'], (int)$t['system'], (int)$t['planet'], 1, $intelHalfLife);
                if ($intel !== null) {
                    $tDef = (isset($intel['defense']) && is_array($intel['defense'])) ? $intel['defense'] : array();
                    $tFlt = (isset($intel['fleet']) && is_array($intel['fleet'])) ? $intel['fleet'] : array();
                    $targetDefenders = $tDef + $tFlt;
                }
            }

            // Build dedicated farming squadron (Cargos + minimal light escort)
            $originColonyData = isset($originColony['data']) ? $originColony['data'] : $originColony;
            $targetFactors = BotCombatOracle::resolveDefenderFactors((int)$t['owner_id'], is_array($intel) ? $intel : array());
            $farmFleet = $this->buildTacticalStrikeFleet($originColonyData, $targetDefenders, $lootNeeded, 'farming', $targetFactors);
            if (empty($farmFleet)) continue;

            // Combat Oracle verification: ensure no suicide attacks against active defender forces
            if (!empty($targetDefenders)) {
                $sim = BotCombatOracle::evaluate(
                    $farmFleet,
                    $targetDefenders,
                    array('id' => $this->ctx->botId, 'factor' => $this->ctx->user['factor']),
                    array('id' => (int)$t['owner_id'], 'factor' => $targetFactors),
                    2
                );
                $winProb = isset($sim['win_probability']) ? (float)$sim['win_probability'] : 0.0;
                if ($winProb < 0.85) {
                    // Try escort with combat fleet
                    $farmFleet = $this->buildTacticalStrikeFleet($originColonyData, $targetDefenders, $lootNeeded, 'fleet_crash', $targetFactors);
                    if (empty($farmFleet)) continue;
                    $sim = BotCombatOracle::evaluate(
                        $farmFleet,
                        $targetDefenders,
                        array('id' => $this->ctx->botId, 'factor' => $this->ctx->user['factor']),
                        array('id' => (int)$t['owner_id'], 'factor' => $targetFactors),
                        2
                    );
                    $winProb = isset($sim['win_probability']) ? (float)$sim['win_probability'] : 0.0;
                    if ($winProb < 0.85) {
                        continue; // Target has dangerous defenses, skip
                    }
                }
            }

            // Modulación Inteligente de Velocidad vs Consumo de Deuterio
            $isInactive = !empty($t['is_inactive']);
            $fDistUnits = $minDist * 95 + 2700;
            $optSpeed = $this->determineOptimalAttackSpeed($farmFleet, $originColonyData, $t, 'farming', $isInactive, $fDistUnits, $candLootMSE);
            if ($optSpeed === null) {
                continue; // Deuterio insuficiente para cubrir la misión incluso a velocidad reducida
            }
            $speedToSend = (int)$optSpeed['speed'];

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
                $speedToSend
            );

            if ($ok) {
                $dispatchedFarms++;
                BotTargetBlacklist::recordAttackVictory($this->ctx->botId, $ownerId, $tId, (int)$t['galaxy'], (int)$t['system'], (int)$t['planet']);

                $this->ctx->decisionLog->record(
                    'farm_sweep_launched',
                    "Parallel farm sweep (Ola 1): dispatched " . array_sum($farmFleet) . " ships from colony #{$originColony['id']} to farm [{$t['galaxy']}:{$t['system']}:{$t['planet']}] at {$speedToSend}0% speed (Estimated Loot: " . number_format($cand['loot_mse'] ?? 0) . " MSE, Combustible: " . number_format($optSpeed['fuel']) . " deut)",
                    array('target' => $t, 'fleet' => $farmFleet, 'origin' => $originColony['id'], 'speed' => $speedToSend),
                    "farm_{$t['galaxy']}_{$t['system']}_{$t['planet']}",
                    3000.0
                );

                // Multi-Wave Scheduling for Macro-Inactives (Waves 2 & 3)
                $wavesRemaining = min($maxAllowedWaves - 1, $effectiveMaxSlots - $curActiveFleets - 1, max(0, $allowedNewStrikes - $dispatchedFarms));
                if ($wavesRemaining >= 1 && empty($targetDefenders)) {
                    // Wave 2: 50% of remaining 50% = 25% of initial loot
                    $lootWave2 = array('metal' => $candLootMSE * 0.25, 'crystal' => 0.0, 'deuterium' => 0.0);
                    $fleetWave2 = $this->buildTacticalStrikeFleet($originColonyData, array(), $lootWave2, 'farming');
                    if (!empty($fleetWave2)) {
                        BotScheduler::scheduleTask($this->ctx->botId, 'staggered_farm_wave', array(
                            'fleet'            => $fleetWave2,
                            'fob_id'           => (int)$originColony['id'],
                            'target_planet_id' => (int)$t['planet_id'],
                            'target_owner_id'  => $ownerId,
                            'target_galaxy'    => (int)$t['galaxy'],
                            'target_system'    => (int)$t['system'],
                            'target_planet'    => (int)$t['planet'],
                            'speed'            => $speedToSend
                        ), self::now() + 15);
                        $dispatchedFarms++;
                        BotTargetBlacklist::recordAttackVictory($this->ctx->botId, $ownerId, $tId, (int)$t['galaxy'], (int)$t['system'], (int)$t['planet']);
                    }

                    // Wave 3: 50% of remaining 25% = 12.5% of initial loot
                    if ($wavesRemaining >= 2) {
                        $lootWave3 = array('metal' => $candLootMSE * 0.125, 'crystal' => 0.0, 'deuterium' => 0.0);
                        $fleetWave3 = $this->buildTacticalStrikeFleet($originColonyData, array(), $lootWave3, 'farming');
                        if (!empty($fleetWave3)) {
                            BotScheduler::scheduleTask($this->ctx->botId, 'staggered_farm_wave', array(
                                'fleet'            => $fleetWave3,
                                'fob_id'           => (int)$originColony['id'],
                                'target_planet_id' => (int)$t['planet_id'],
                                'target_owner_id'  => $ownerId,
                                'target_galaxy'    => (int)$t['galaxy'],
                                'target_system'    => (int)$t['system'],
                                'target_planet'    => (int)$t['planet'],
                                'speed'            => $speedToSend
                            ), self::now() + 30);
                            $dispatchedFarms++;
                            BotTargetBlacklist::recordAttackVictory($this->ctx->botId, $ownerId, $tId, (int)$t['galaxy'], (int)$t['system'], (int)$t['planet']);
                        }
                    }
                }
            }
        }

        return $dispatchedFarms;
    }

    /**
     * Section 2.5: Pre-impact confirmation & Anti-Bait / Anti-Ninja Engine.
     * Uses Live Tactical Snapshot in MariaDB (zero fog-of-war for live defense)
     * and evaluates financial balance (War NAV / ROI) modulated by difficulty.
     */
    public function verifyInFlightStrikes(BotWorldModel $world)
    {
        // 0. If difficulty has anti-bait disabled (EASY), skip check (novice bot falls for traps)
        if (empty($this->ctx->difficulty->antiBaitEnabled)) {
            return;
        }

        $db = Database::get();
        global $resource, $reslist;

        $inFlight = $db->select("SELECT fleet_id, fleet_end_id, fleet_end_galaxy, fleet_end_system, fleet_end_planet, fleet_end_type, fleet_start_time, fleet_array 
            FROM %%FLEETS%% 
            WHERE fleet_owner = :botId AND fleet_mission = 1 AND fleet_mess = 0;", array(
            ':botId' => $this->ctx->botId
        ));

        if (!is_array($inFlight) || empty($inFlight)) {
            return;
        }

        $antiBaitWindow = isset($this->ctx->difficulty->antiBaitWindowSeconds) ? (int)$this->ctx->difficulty->antiBaitWindowSeconds : 120;
        $safetyProbeWindow = method_exists($this->ctx->difficulty, 'getSafetyProbeWindowSeconds')
            ? (int)$this->ctx->difficulty->getSafetyProbeWindowSeconds()
            : 60;
        $accuracy = isset($this->ctx->difficulty->antiBaitAccuracy) ? (float)$this->ctx->difficulty->antiBaitAccuracy : 0.85;
        $shouldSendProbe = method_exists($this->ctx->difficulty, 'shouldSendSafetyProbe')
            ? (bool)$this->ctx->difficulty->shouldSendSafetyProbe()
            : true;

        foreach ($inFlight as $fleet) {
            $timeToImpact = (int)$fleet['fleet_start_time'] - self::now();
            $tG = (int)$fleet['fleet_end_galaxy'];
            $tS = (int)$fleet['fleet_end_system'];
            $tP = (int)$fleet['fleet_end_planet'];
            $tType = (int)$fleet['fleet_end_type'];

            // -------------------------------------------------------------
            // FASE A: Cero Tolerancia a Modo Vacaciones o Administrador
            // -------------------------------------------------------------
            $targetUserRow = $db->selectSingle("SELECT p.id, p.id_owner, u.urlaubs_modus, u.authlevel FROM %%PLANETS%% p 
                INNER JOIN %%USERS%% u ON u.id = p.id_owner 
                WHERE p.galaxy = :g AND p.system = :s AND p.planet = :p AND p.planet_type = :t LIMIT 1;", array(
                ':g' => $tG, ':s' => $tS, ':p' => $tP, ':t' => $tType
            ));

            if (!empty($targetUserRow['urlaubs_modus']) || (!empty($targetUserRow['authlevel']) && (int)$targetUserRow['authlevel'] > 0)) {
                $this->executeTacticalRecall($fleet, "Defensor activó Modo Vacaciones o es Administrador", true);
                $db->delete("DELETE FROM %%BOT_TASKS%% WHERE bot_id = :bId AND status = 'pending' AND payload LIKE :tMatch;", array(
                    ':bId'   => $this->ctx->botId,
                    ':tMatch'=> "%\"target_galaxy\":{$tG}%\"target_system\":{$tS}%\"target_planet\":{$tP}%",
                ));
                continue;
            }

            $targetPlanetId = !empty($targetUserRow['id']) ? (int)$targetUserRow['id'] : (int)$fleet['fleet_end_id'];
            $targetOwnerId  = !empty($targetUserRow['id_owner']) ? (int)$targetUserRow['id_owner'] : 0;

            // -------------------------------------------------------------
            // FASE B: Sonda de Seguridad Previa al Impacto en [15s, safetyProbeWindow + 60s]
            // -------------------------------------------------------------
            if ($shouldSendProbe && $timeToImpact >= 15 && $timeToImpact <= ($safetyProbeWindow + 60)) {
                $probeInFlight = $db->selectSingle("
                    SELECT COUNT(*) as cnt FROM %%FLEETS%% 
                    WHERE fleet_owner = :botId AND fleet_mission = 6 
                      AND fleet_end_galaxy = :g AND fleet_end_system = :s AND fleet_end_planet = :p 
                      AND fleet_mess = 0;
                ", array(':botId' => $this->ctx->botId, ':g' => $tG, ':s' => $tS, ':p' => $tP));

                if (empty($probeInFlight['cnt'])) {
                    $allColonies = $world->empire->getPlanets();
                    foreach ($allColonies as $cId => $cData) {
                        $cPData = isset($cData['data']) ? $cData['data'] : $cData;
                        $probesAvail = isset($cPData[$resource[210]]) ? (int)$cPData[$resource[210]] : 0;
                        if ($probesAvail >= 1) {
                            $okProbe = $this->gateway->launchFleet(
                                array(210 => 1),
                                6, // Espionage
                                (int)$cId,
                                $targetPlanetId,
                                $targetOwnerId,
                                $tG,
                                $tS,
                                $tP,
                                $tType,
                                array(),
                                10
                            );
                            if ($okProbe) {
                                $this->ctx->decisionLog->record(
                                    'safety_probe_dispatched',
                                    "Watchdog Fase B: Sonda espía de confirmación táctica previa al impacto despachada a T-{$timeToImpact}s hacia [{$tG}:{$tS}:{$tP}].",
                                    array('target' => "{$tG}:{$tS}:{$tP}", 'time_to_impact' => $timeToImpact),
                                    "safety_probe_{$tG}_{$tS}_{$tP}",
                                    4000.0
                                );
                            }
                            break;
                        }
                    }
                }
            }

            // -------------------------------------------------------------
            // FASE C: Decisión Anti-Emboscada en [2s, antiBaitWindow + 65s]
            // -------------------------------------------------------------
            if ($timeToImpact >= 2 && $timeToImpact <= ($antiBaitWindow + 65)) {
                // 1. LIVE TACTICAL SNAPSHOT (Zero Fog of War in Live Database)
                $livePlanet = $db->selectSingle("SELECT * FROM %%PLANETS%% WHERE id = :pId LIMIT 1;", array(':pId' => $targetPlanetId));
                if (empty($livePlanet)) {
                    $this->executeTacticalRecall($fleet, "Planeta objetivo ya no existe", true);
                    continue;
                }

                $liveDefenders = array();
                $unitList = array_merge($reslist['fleet'], $reslist['defense'], $reslist['missile']);
                foreach ($unitList as $uId) {
                    if ($uId == 212) continue; // Skip solar satellites from threat calculation
                    $qty = isset($livePlanet[$resource[$uId]]) ? (int)$livePlanet[$resource[$uId]] : 0;
                    if ($qty > 0) {
                        $liveDefenders[$uId] = $qty;
                    }
                }

                // Query stationary ACS Defend (Mission 5 / SAC Defensivo) fleets currently stationed on target
                $stayFleets = $db->select("SELECT fleet_array FROM %%FLEETS%% 
                    WHERE fleet_end_id = :pId AND fleet_mission = 5 
                      AND fleet_start_time <= :now AND fleet_end_stay >= :now;", array(
                    ':pId' => $targetPlanetId,
                    ':now' => self::now()
                ));
                if (!empty($stayFleets) && is_array($stayFleets)) {
                    foreach ($stayFleets as $sf) {
                        if (!empty($sf['fleet_array'])) {
                            $sfShips = FleetFunctions::unserialize($sf['fleet_array']);
                            if (is_array($sfShips)) {
                                foreach ($sfShips as $sfId => $sfQty) {
                                    if ($sfId == 212) continue;
                                    $liveDefenders[$sfId] = isset($liveDefenders[$sfId]) ? $liveDefenders[$sfId] + $sfQty : $sfQty;
                                }
                            }
                        }
                    }
                }

                // Reconstruct dispatched strike fleet
                $dispatchedShips = array();
                $rawShips = explode(';', $fleet['fleet_array']);
                $deployedFleetMSE = 0.0;
                $warshipCount = 0;
                $cargoCount   = 0;
                foreach ($rawShips as $rs) {
                    if (empty($rs)) continue;
                    list($sId, $cnt) = explode(',', $rs);
                    $sId = (int)$sId;
                    $cnt = (int)$cnt;
                    if ($cnt > 0) {
                        $dispatchedShips[$sId] = $cnt;
                        $deployedFleetMSE += BotEconomyValuator::getElementPriceMSE($sId, $cnt);
                        if (in_array($sId, array(206, 207, 211, 213, 214, 215, 216, 221, 222, 224, 225, 226, 227, 228))) {
                            $warshipCount += $cnt;
                        }
                        if (in_array($sId, array(202, 203, 217))) {
                            $cargoCount += $cnt;
                        }
                    }
                }

                // Salvaguarda crítica para escuadras de cargueros
                $isCargoHeavy = ($cargoCount > 0 && $warshipCount <= 5);
                if ($isCargoHeavy && !empty($liveDefenders)) {
                    $hasThreat = false;
                    foreach ($liveDefenders as $dId => $dCnt) {
                        if ($dId == 212 || $dId == 502) continue;
                        if ($dCnt > 0) {
                            $hasThreat = true;
                            break;
                        }
                    }
                    if ($hasThreat) {
                        $this->executeTacticalRecall($fleet, "SALVAGUARDA DE CARGUEROS: Detectadas defensas o flota armada hostil en destino. Retirando cargueros.", true);
                        continue;
                    }
                }

                $targetFactors = BotCombatOracle::resolveDefenderFactors($targetOwnerId);

                // Live Combat Simulation (Simulación de impacto en tiempo real)
                $simLive = BotCombatOracle::evaluate(
                    $dispatchedShips,
                    $liveDefenders,
                    array('id' => $this->ctx->botId, 'factor' => $this->ctx->user['factor'] ?? array()),
                    array('id' => $targetOwnerId, 'factor' => $targetFactors),
                    2
                );

                $lootMetal = (float)($livePlanet['metal'] ?? 0) * 0.50;
                $lootCry   = (float)($livePlanet['crystal'] ?? 0) * 0.50;
                $lootDeut  = (float)($livePlanet['deuterium'] ?? 0) * 0.50;
                $lootMSE   = BotEconomyValuator::toMSE($lootMetal, $lootCry, $lootDeut);

                $recsCap = $this->estimateInFlightRecyclerCapacity($tG, $tS, $tP);

                $finLive = BotCombatOracle::evaluateFinancialBalance(
                    $simLive,
                    $deployedFleetMSE,
                    0.0,
                    $recsCap,
                    $lootMSE
                );

                $liveWinProb   = isset($finLive['win_probability']) ? (float)$finLive['win_probability'] : 0.0;
                $liveNetProfit = isset($finLive['net_profit_mse']) ? (float)$finLive['net_profit_mse'] : 0.0;
                $liveLossPct   = isset($finLive['att_loss_pct']) ? (float)$finLive['att_loss_pct'] : 0.0;
                $ownLossMSE    = isset($finLive['att_loss_mse']) ? (float)$finLive['att_loss_mse'] : ($deployedFleetMSE * $liveLossPct);
                $harvestableDebris = isset($finLive['att_debris_recovered']) ? (float)$finLive['att_debris_recovered'] : 0.0;

                $maxLossAllowed = isset($this->ctx->difficulty->maxOwnFleetLossRatioAllowed) ? (float)$this->ctx->difficulty->maxOwnFleetLossRatioAllowed : 0.40;

                // Chequeo de sonda destruida o flota hostil inesperada
                $intelFresh = $world->intel->getIntel($tG, $tS, $tP, $tType, 0.5);
                $probeDestroyed100 = ($intelFresh !== null && !empty($intelFresh['incomplete_intel']));

                // 4 Condiciones estrictas de aborto táctico:
                // 1. Sonda destruida al 100% o aparición de flota enemiga no prevista
                // 2. win_probability < 0.80 o OwnFleetLossMSE > 1.15 * (RealizableLoot + DebrisHarvestable)
                // 3. Defensor evacuó flota o gastó recursos provocando EV_net < 0
                // 4. ownLossRatio > maxAcceptableLossRatio
                $condProbeOrSurprise = $probeDestroyed100;
                $condLossExcessive   = ($liveWinProb < 0.80) || ($ownLossMSE > 1.15 * ($lootMSE + $harvestableDebris));
                $condEvNegative      = ($liveNetProfit < 0.0);
                $condLossRatio       = ($liveLossPct > $maxLossAllowed);

                $shouldAbort = ($condProbeOrSurprise || $condLossExcessive || $condEvNegative || $condLossRatio);

                if ($shouldAbort) {
                    $roll = mt_rand(1, 100) / 100.0;
                    if ($roll <= $accuracy) {
                        $reason = "WATCHDOG_RECALL: Emboscada/pérdida detectada a T-{$timeToImpact}s (Win: " . round($liveWinProb * 100, 1) . "%, Net: " . number_format($liveNetProfit) . " MSE, Loss: " . round($liveLossPct * 100, 1) . "%). Armada y recicladores retirados.";
                        $this->executeTacticalRecall($fleet, $reason, true);

                        $wdCooldown = mt_rand(1800, 2700);
                        BotTargetBlacklist::addCooldown($this->ctx->botId, $targetOwnerId, $targetPlanetId, $tG, $tS, $tP, $wdCooldown, $reason);

                        $this->ctx->decisionLog->record(
                            'anti_bait_ninja_evaded',
                            "ANTI-BAIT / WATCHDOG FASE C ACTIVADO: Live Tactical Snapshot a T-{$timeToImpact}s detectó hostil/trampa en [{$tG}:{$tS}:{$tP}]. Rentabilidad colapsó a " . number_format($liveNetProfit) . " MSE. Retirando armada y recicladores sincronizados.",
                            array(
                                'fleet_id' => $fleet['fleet_id'],
                                'target' => "{$tG}:{$tS}:{$tP}",
                                'time_to_impact' => $timeToImpact,
                                'live_win_prob' => $liveWinProb,
                                'live_net_profit' => $liveNetProfit,
                                'live_defenders_count' => count($liveDefenders),
                                'cond_probe' => $condProbeOrSurprise,
                                'cond_loss' => $condLossExcessive,
                                'cond_ev' => $condEvNegative,
                                'cond_ratio' => $condLossRatio
                            ),
                            "anti_bait_{$fleet['fleet_id']}",
                            9999.0
                        );
                        continue;
                    }
                }
            }
        }
    }

    /**
     * Executes a tactical recall of the strike fleet and any associated in-flight recyclers
     */
    private function executeTacticalRecall(array $fleet, $reason, $recallRecyclers = false)
    {
        $fleetId = (int)$fleet['fleet_id'];
        $ok = $this->gateway->recallFleet($fleetId);

        if ($recallRecyclers) {
            $db = Database::get();
            $tG = (int)$fleet['fleet_end_galaxy'];
            $tS = (int)$fleet['fleet_end_system'];
            $tP = (int)$fleet['fleet_end_planet'];

            $recFleets = $db->select("SELECT fleet_id FROM %%FLEETS%% 
                WHERE fleet_owner = :botId AND fleet_mission = 8 
                  AND fleet_end_galaxy = :g AND fleet_end_system = :s AND fleet_end_planet = :p 
                  AND fleet_mess = 0;", array(
                ':botId' => $this->ctx->botId,
                ':g'     => $tG,
                ':s'     => $tS,
                ':p'     => $tP,
            ));

            if (!empty($recFleets) && is_array($recFleets)) {
                foreach ($recFleets as $rf) {
                    $this->gateway->recallFleet((int)$rf['fleet_id']);
                }
            }
        }

        return $ok;
    }

    /**
     * Estimates capacity of in-flight recyclers heading to destination
     */
    private function estimateInFlightRecyclerCapacity($g, $s, $p)
    {
        $db = Database::get();
        $recFleets = $db->select("SELECT fleet_array FROM %%FLEETS%% 
            WHERE fleet_owner = :botId AND fleet_mission = 8 
              AND fleet_end_galaxy = :g AND fleet_end_system = :s AND fleet_end_planet = :p 
              AND fleet_mess = 0;", array(
            ':botId' => $this->ctx->botId,
            ':g'     => (int)$g,
            ':s'     => (int)$s,
            ':p'     => (int)$p,
        ));

        $totalCap = 0.0;
        if (!empty($recFleets) && is_array($recFleets)) {
            foreach ($recFleets as $rf) {
                if (!empty($rf['fleet_array'])) {
                    $ships = FleetFunctions::unserialize($rf['fleet_array']);
                    if (is_array($ships)) {
                        $cnt219 = isset($ships[219]) ? (int)$ships[219] : 0;
                        $cnt209 = isset($ships[209]) ? (int)$ships[209] : 0;
                        $totalCap += ($cnt219 * 200000000.0) + ($cnt209 * 20000.0);
                    }
                }
            }
        }

        if ($totalCap <= 0.0) {
            $totalCap = 50000000000.0;
        }

        return $totalCap;
    }

    public function buildRecyclerFleet(array $fobData, $expectedDebris)
    {
        global $resource;

        $recs219 = isset($fobData[$resource[219]]) ? (int)$fobData[$resource[219]] : 0;
        $recs209 = isset($fobData[$resource[209]]) ? (int)$fobData[$resource[209]] : 0;

        $cap219 = 200000000.0; // 200M capacity for Giga Recycler (ID 219)
        $cap209 = 20000.0;     // 20k capacity for standard Recycler (ID 209)

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
     * Finds the nearest colony belonging to the bot that possesses available recyclers.
     *
     * @param BotEmpireState $empire
     * @param array $tCoords Target coordinates [galaxy, system, planet]
     * @param float $expectedDebris
     * @param int $excludeId
     * @return array|null Array with colony details and fleet, or null if none found
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

            $cap = (($fleet[219] ?? 0) * 200000000.0) + (($fleet[209] ?? 0) * 20000.0);
            $pCoords = array((int)$pData['galaxy'], (int)$pData['system'], (int)$pData['planet']);
            $dist = FleetFunctions::GetTargetDistance($pCoords, $tCoords);

            if ($dist < $minDist) {
                $minDist = $dist;
                $bestColony = array(
                    'planet_id'   => $pId,
                    'planet_data' => $pData,
                    'fleet'       => $fleet,
                    'capacity'    => $cap,
                    'distance'    => $dist,
                    'coords'      => $pCoords,
                );
            }
        }

        return $bestColony;
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
        $extraLogContext = array(),
        $world = null
    ) {
        $maxSlots     = FleetFunctions::GetMaxFleetSlots($this->ctx->user);
        $actualFleets = FleetFunctions::GetCurrentFleets($this->ctx->botId);
        $availMil     = BotActionGateway::getAvailableMilitarySlots($this->ctx->botId, $this->ctx->user);
        $minSlotsRequired = ($expectedDebris > 10000.0) ? 2 : 1;
        if (($maxSlots - $actualFleets) < $minSlotsRequired || $availMil < $minSlotsRequired) {
            return 0;
        }

        $fobCoords = array($fobData['galaxy'], $fobData['system'], $fobData['planet']);
        $tCoords   = array($t['galaxy'], $t['system'], $t['planet']);
        $fDist     = FleetFunctions::GetTargetDistance($fobCoords, $tCoords);
        $gameSpeed = FleetFunctions::GetGameSpeedFactor();
        $strikeSpeed = FleetFunctions::GetFleetMaxSpeed($strikeFleet, $this->ctx->user);

        // 1. Dynamic Fuel & Speed Optimization for Strike Fleet (10 -> 7)
        $availDeutFob = (float)($fobData['deuterium'] ?? 0);
        $sStrike = 10;
        $strikeDuration = 0;
        $strikeConsumption = 0;
        $cons = 0;

        for ($spd = 10; $spd >= 7; $spd--) {
            $dur = FleetFunctions::GetMissionDuration($spd, $strikeSpeed, $fDist, $gameSpeed, $this->ctx->user);
            $cons = FleetFunctions::GetFleetConsumption($strikeFleet, $dur, $fDist, $this->ctx->user, $gameSpeed);
            if ($cons <= ($availDeutFob * 0.98)) {
                $sStrike = $spd;
                $strikeDuration = $dur;
                $strikeConsumption = $cons;
                break;
            }
        }

        // If fuel is still insufficient at speed 7, attempt tactical merchant trade
        if ($strikeConsumption <= 0 || $strikeConsumption > $availDeutFob) {
            if (class_exists('BotPremiumStrategist')) {
                $dur10 = FleetFunctions::GetMissionDuration(10, $strikeSpeed, $fDist, $gameSpeed, $this->ctx->user);
                $cons10 = FleetFunctions::GetFleetConsumption($strikeFleet, $dur10, $fDist, $this->ctx->user, $gameSpeed);
                $deficit = max(0.0, ($cons10 * 1.05) - $availDeutFob);
                $trader = new BotPremiumStrategist($this->ctx, $this->gateway);
                $gained = $trader->executeTacticalFuelTrade($fobId, $deficit);
                if ($gained > 0) {
                    $availDeutFob += $gained;
                    $fobData['deuterium'] = $availDeutFob;
                    for ($spd = 10; $spd >= 7; $spd--) {
                        $dur = FleetFunctions::GetMissionDuration($spd, $strikeSpeed, $fDist, $gameSpeed, $this->ctx->user);
                        $cons = FleetFunctions::GetFleetConsumption($strikeFleet, $dur, $fDist, $this->ctx->user, $gameSpeed);
                        if ($cons <= ($availDeutFob * 0.98)) {
                            $sStrike = $spd;
                            $strikeDuration = $dur;
                            $strikeConsumption = $cons;
                            break;
                        }
                    }
                }
            }
        }

        // If still deficient after throttling and merchant trade, hold attack to prevent deadlock
        if ($strikeConsumption <= 0 || $strikeConsumption > $availDeutFob) {
            $this->ctx->decisionLog->record(
                'strike_fuel_starvation',
                "Offensive strike held at FOB #{$fobId}: insufficient deuterium (" . number_format($availDeutFob) . " available vs " . number_format($strikeConsumption > 0 ? $strikeConsumption : $cons) . " required). Throttling and tactical trade insufficient.",
                array('target' => $t, 'fob_id' => $fobId, 'available_deut' => $availDeutFob, 'needed_deut' => $strikeConsumption),
                "fuel_starve_{$fobId}",
                2500.0
            );
            return 0;
        }

        $dStrike = $strikeDuration;

        $recyclerFleet = array();
        $recOriginData = $fobData;
        $recOriginId   = $fobId;
        $recCoords     = $fobCoords;
        $recDist       = $fDist;

        if ($expectedDebris > 10000.0) {
            $fobFleet = $this->buildRecyclerFleet($fobData, $expectedDebris);
            $fobCap   = (($fobFleet[219] ?? 0) * 200000000.0) + (($fobFleet[209] ?? 0) * 20000.0);

            if ($fobCap >= $expectedDebris || empty($world) || !isset($world->empire)) {
                $recyclerFleet = $fobFleet;
            } else {
                // FOB has partial or zero recyclers: check nearest empire colony with recyclers
                $altRec = $this->findNearestColonyWithRecyclers($world->empire, $tCoords, $expectedDebris, $fobId);
                if ($altRec !== null && ($altRec['capacity'] >= $expectedDebris || empty($fobFleet))) {
                    $recOriginData = $altRec['planet_data'];
                    $recOriginId   = $altRec['planet_id'];
                    $recCoords     = $altRec['coords'];
                    $recDist       = $altRec['distance'];
                    $recyclerFleet = $altRec['fleet'];
                } else {
                    $recyclerFleet = $fobFleet;
                }
            }
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
                $sStrike
            );

            if ($ok) {
                $this->ctx->decisionLog->record(
                    'strike_launched',
                    "Launched {$category} strike from FOB #{$fobId} against [{$t['galaxy']}:{$t['system']}:{$t['planet']}] with " . number_format(array_sum($strikeFleet)) . " warships (" . round($winProb * 100, 1) . "% win probability) at {$sStrike}0% speed (ETA: {$dStrike}s)",
                    array_merge(array('target' => $t, 'fleet' => $strikeFleet, 'fob_id' => $fobId, 'speed' => $sStrike), $extraLogContext),
                    "attack_{$t['galaxy']}_{$t['system']}_{$t['planet']}",
                    6000.0
                );
                return 1;
            }
            return 0;
        }

        // Ambas flotas presentes: sincronización milimétrica para arribo a T_impacto + recOffset
        $recSpeed = FleetFunctions::GetFleetMaxSpeed($recyclerFleet, $this->ctx->user);
        $sRec = 10;
        $dRec = FleetFunctions::GetMissionDuration($sRec, $recSpeed, $recDist, $gameSpeed, $this->ctx->user);

        // Pre-validar combustible para recicladores
        $availDeutRec = (float)($recOriginData['deuterium'] ?? 0);
        $recConsumption = FleetFunctions::GetFleetConsumption($recyclerFleet, $dRec, $recDist, $this->ctx->user, $gameSpeed);
        if ($recConsumption > ($availDeutRec * 0.98)) {
            for ($spd = 9; $spd >= 7; $spd--) {
                $durR = FleetFunctions::GetMissionDuration($spd, $recSpeed, $recDist, $gameSpeed, $this->ctx->user);
                $consR = FleetFunctions::GetFleetConsumption($recyclerFleet, $durR, $recDist, $this->ctx->user, $gameSpeed);
                if ($consR <= ($availDeutRec * 0.98)) {
                    $sRec = $spd;
                    $dRec = $durR;
                    $recConsumption = $consR;
                    break;
                }
            }
        }
        if ($recConsumption > $availDeutRec && class_exists('BotPremiumStrategist')) {
            $trader = new BotPremiumStrategist($this->ctx, $this->gateway);
            $gainedR = $trader->executeTacticalFuelTrade($recOriginId, ($recConsumption - $availDeutRec) * 1.05);
            if ($gainedR > 0) {
                $availDeutRec += $gainedR;
            }
        }

        if ($recConsumption > $availDeutRec) {
            $this->ctx->decisionLog->record(
                'recycler_fuel_starvation',
                "Recycler dispatch held at base #{$recOriginId}: insufficient deuterium (" . number_format($availDeutRec) . " available vs " . number_format($recConsumption) . " required).",
                array('target' => $t, 'rec_origin_id' => $recOriginId),
                "rec_fuel_starve_{$recOriginId}",
                2500.0
            );
            return 0;
        }

        // Validación atómica de combustible conjunto si ambas flotas operan desde la misma FOB
        if ($recOriginId == $fobId) {
            $totalDeutNeeded = $strikeConsumption + $recConsumption;
            if ($availDeutFob < ($totalDeutNeeded * 0.98)) {
                if (class_exists('BotPremiumStrategist')) {
                    $jointDeficit = max(0.0, ($totalDeutNeeded * 1.05) - $availDeutFob);
                    $trader = new BotPremiumStrategist($this->ctx, $this->gateway);
                    $gainedJoint = $trader->executeTacticalFuelTrade($fobId, $jointDeficit);
                    if ($gainedJoint > 0) {
                        $availDeutFob += $gainedJoint;
                        $fobData['deuterium'] = $availDeutFob;
                    }
                }
            }
            if ($availDeutFob < $totalDeutNeeded) {
                $this->ctx->decisionLog->record(
                    'joint_fuel_starvation',
                    "Synchronized strike + recyclers held at FOB #{$fobId}: combined fuel required (" . number_format($totalDeutNeeded) . ") exceeds available deuterium (" . number_format($availDeutFob) . "). Holding launch to prevent orphan recyclers.",
                    array('target' => $t, 'fob_id' => $fobId, 'strike_fuel' => $strikeConsumption, 'rec_fuel' => $recConsumption, 'available' => $availDeutFob),
                    "joint_fuel_starve_{$fobId}",
                    3000.0
                );
                return 0;
            }
        }

        $recOffset = method_exists($this->ctx->difficulty, 'getRecyclerStrikeOffset')
            ? (int)$this->ctx->difficulty->getRecyclerStrikeOffset()
            : (isset($this->ctx->difficulty->recyclerStrikeOffset) ? (int)$this->ctx->difficulty->recyclerStrikeOffset : mt_rand(1, 5));

        // delta = segundos de desfase entre arribos
        // Queremos: T_llegada_rec = T_llegada_strike + recOffset
        // T_lanz_rec + dRec = T_lanz_strike + dStrike + recOffset
        // delta = (dRec - recOffset) - dStrike
        $delta = ($dRec - $recOffset) - $dStrike;

        if ($delta >= 0) {
            // Recicladores son MÁS LENTOS (o iguales): Se lanzan PRIMERO los recicladores a velocidad 100%
            $recOk = $this->gateway->launchFleet(
                $recyclerFleet,
                8, // Recycle
                $recOriginId,
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
                "Synchronized pre-impact deployment of " . array_sum($recyclerFleet) . " recyclers from base #{$recOriginId} to debris at [{$t['galaxy']}:{$t['system']}:{$t['planet']}] at {$sRec}0% speed (ETA: {$dRec}s | Debris: " . number_format($expectedDebris) . ")",
                array('target' => $t, 'recyclers' => $recyclerFleet, 'debris' => $expectedDebris, 'speed' => $sRec, 'duration' => $dRec, 'origin_id' => $recOriginId),
                "sync_recycle_{$t['galaxy']}_{$t['system']}_{$t['planet']}",
                4500.0
            );

            $delaySeconds = (int)round($delta);

            if ($delaySeconds <= 10) {
                if ($delaySeconds > 0) {
                    self::sleepIfReal($delaySeconds);
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
                        "Launched {$category} strike from FOB #{$fobId} against [{$t['galaxy']}:{$t['system']}:{$t['planet']}] with " . number_format(array_sum($strikeFleet)) . " warships (" . round($winProb * 100, 1) . "% win probability) at {$sStrike}0% speed (Sincronizado: Recicladores arriban T+{$recOffset}s desde base #{$recOriginId})",
                        array_merge(array('target' => $t, 'fleet' => $strikeFleet, 'fob_id' => $fobId, 'speed' => $sStrike, 'rec_origin_id' => $recOriginId), $extraLogContext),
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
                    self::now() + $delaySeconds
                );

                $this->ctx->decisionLog->record(
                    'strike_scheduled_for_impact',
                    "Ofensiva programada: Despacho de " . number_format(array_sum($strikeFleet)) . " naves a velocidad {$sStrike}0% programado para T+{$delaySeconds}s hacia [{$t['galaxy']}:{$t['system']}:{$t['planet']}]. Arribo de recicladores desde base #{$recOriginId} calibrado exactamente a T+{$recOffset}s del impacto.",
                    array_merge(array('target' => $t, 'fleet' => $strikeFleet, 'delay' => $delaySeconds, 'speed' => $sStrike, 'rec_origin_id' => $recOriginId), $extraLogContext),
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

            if ($delaySeconds <= 10) {
                if ($delaySeconds > 0) {
                    self::sleepIfReal($delaySeconds);
                }
                $recOk = $this->gateway->launchFleet(
                    $recyclerFleet,
                    8,
                    $recOriginId,
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
                        "Synchronized pre-impact deployment of " . array_sum($recyclerFleet) . " recicladores desde base #{$recOriginId} to debris at [{$t['galaxy']}:{$t['system']}:{$t['planet']}] at {$sRec}0% speed (Arribo: Impacto + {$recOffset}s)",
                        array('target' => $t, 'recyclers' => $recyclerFleet, 'debris' => $expectedDebris, 'speed' => $sRec, 'origin_id' => $recOriginId),
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
                        'fob_id'           => $recOriginId,
                        'target_planet_id' => $t['planet_id'],
                        'target_galaxy'    => $t['galaxy'],
                        'target_system'    => $t['system'],
                        'target_planet'    => $t['planet'],
                        'speed'            => $sRec,
                    ),
                    self::now() + $delaySeconds
                );

                $this->ctx->decisionLog->record(
                    'recycler_scheduled_for_impact',
                    "Recicladores programados: Despacho de " . array_sum($recyclerFleet) . " recicladores desde base #{$recOriginId} a velocidad {$sRec}0% programado para T+{$delaySeconds}s hacia [{$t['galaxy']}:{$t['system']}:{$t['planet']}]. Arribo calibrado exactamente a T+2s del impacto.",
                    array('target' => $t, 'recyclers' => $recyclerFleet, 'delay' => $delaySeconds, 'speed' => $sRec, 'origin_id' => $recOriginId),
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
     * Determines optimal attack speed balancing tactical urgency vs deuterium conservation.
     *
     * Criteria:
     * - High speed (100% / speed = 10):
     *   1. Active player fleet crash (minimize warning and fleetsave reaction window).
     *   2. Short distance (<= 5 systems, flight time < 15m, negligible fuel saving).
     *   3. Urgent coordinated strike.
     * - Fuel conservation (Speed 50% - 90%):
     *   1. Inactive target or farming raid (inactives don't flee or react, saving 40-65% deuterium).
     *   2. FOB fuel deficit (stock < consumption at 100%, stepping down speed 9..5 enables viable raid).
     *   3. Extreme distance (>= 20 systems) where fuel savings exceed 50M Deut.
     */
    public function determineOptimalAttackSpeed(
        array $fleet,
        array $fobData,
        array $target,
        $category = 'farming',
        $isTargetInactive = false,
        $distance = 0,
        $expectedRevenue = 0.0
    ) {
        $gSpeed = FleetFunctions::GetGameSpeedFactor();
        $fSpeed = FleetFunctions::GetFleetMaxSpeed($fleet, $this->ctx->user);
        $stockDeut = (float)($fobData['deuterium'] ?? 0);

        $targetActivity = isset($target['activity']) ? $target['activity'] : (isset($target['activity_status']) ? $target['activity_status'] : '');
        $isTargetOffline = ($targetActivity === 'OFFLINE' || $isTargetInactive);
        $isFromPlanet = ((int)($fobData['planet_type'] ?? 1) === 1);

        if ($targetActivity === 'ONLINE') {
            // Defensor ONLINE: Escuadra rápida de intercepción al 100% de velocidad (speed = 10)
            $dur10 = FleetFunctions::GetMissionDuration(10, $fSpeed, $distance, $gSpeed, $this->ctx->user);
            $fuel10 = FleetFunctions::GetFleetConsumption($fleet, $dur10, $distance, $this->ctx->user, $gSpeed);
            if ($stockDeut >= $fuel10) {
                return array('speed' => 10, 'fuel' => $fuel10, 'duration' => $dur10, 'strategy' => 'online_fast_intercept_100pct');
            }
            return null; // Si no hay combustible para 100%, rechaza el ataque online
        }

        if ($isTargetOffline) {
            // Cascada descendente de velocidad: 90% (Speed Masking / -19% deut) -> 80% (-36%) -> 70% (-51%) -> 60% (-64%) -> 50% (-75%)
            $evalSpeeds = array(9, 8, 7, 6, 5);
            foreach ($evalSpeeds as $spd) {
                $dur = FleetFunctions::GetMissionDuration($spd, $fSpeed, $distance, $gSpeed, $this->ctx->user);
                $fuel = FleetFunctions::GetFleetConsumption($fleet, $dur, $distance, $this->ctx->user, $gSpeed);
                if ($stockDeut >= $fuel) {
                    return array(
                        'speed'    => $spd,
                        'fuel'     => $fuel,
                        'duration' => $dur,
                        'strategy' => ($spd === 9 ? 'speed_masking_phalanx_evasion' : "fuel_saving_{$spd}0pct")
                    );
                }
            }
            return null; // Si ni al 50% alcanza el deuterio, retornar null para evaluar el Mercader si ROI >= 30%
        }

        // High Speed Requirement: Active fleet crash or very short distance (<= 5 systems = <= 3175 units)
        $requiresMaxSpeed = ($category === 'fleet_crash' && !$isTargetInactive) || ($distance <= 3175);

        if ($requiresMaxSpeed) {
            $dur10 = FleetFunctions::GetMissionDuration(10, $fSpeed, $distance, $gSpeed, $this->ctx->user);
            $fuel10 = FleetFunctions::GetFleetConsumption($fleet, $dur10, $distance, $this->ctx->user, $gSpeed);
            if ($stockDeut >= $fuel10) {
                return array('speed' => 10, 'fuel' => $fuel10, 'duration' => $dur10, 'strategy' => 'max_speed');
            }
            // Fallback for urgent fleet crash if fuel is slightly short: try speed 9 down to 5
            for ($s = 9; $s >= 5; $s--) {
                $dur = FleetFunctions::GetMissionDuration($s, $fSpeed, $distance, $gSpeed, $this->ctx->user);
                $fuel = FleetFunctions::GetFleetConsumption($fleet, $dur, $distance, $this->ctx->user, $gSpeed);
                if ($stockDeut >= $fuel) {
                    return array('speed' => $s, 'fuel' => $fuel, 'duration' => $dur, 'strategy' => 'speed_fallback');
                }
            }
            return null;
        }

        // Fuel Conservation Doctrine: Inactive target or farming raid
        // Standard baseline speed for inactives at medium/long distance is 7 (70%) or 8 (80%)
        $baselineSpeed = ($isTargetInactive || $category === 'farming') ? ($distance > 3000 ? 7 : 8) : 10;

        // Try from baselineSpeed down to 3 if fuel is tight
        for ($s = $baselineSpeed; $s >= 3; $s--) {
            $dur  = FleetFunctions::GetMissionDuration($s, $fSpeed, $distance, $gSpeed, $this->ctx->user);
            $fuel = FleetFunctions::GetFleetConsumption($fleet, $dur, $distance, $this->ctx->user, $gSpeed);
            if ($stockDeut >= $fuel) {
                return array('speed' => $s, 'fuel' => $fuel, 'duration' => $dur, 'strategy' => 'fuel_saving_economy');
            }
        }

        // If baselineSpeed was throttled down but stock could afford higher speed if needed
        if ($baselineSpeed < 10) {
            for ($s = 10; $s > $baselineSpeed; $s--) {
                $dur  = FleetFunctions::GetMissionDuration($s, $fSpeed, $distance, $gSpeed, $this->ctx->user);
                $fuel = FleetFunctions::GetFleetConsumption($fleet, $dur, $distance, $this->ctx->user, $gSpeed);
                if ($stockDeut >= $fuel) {
                    return array('speed' => $s, 'fuel' => $fuel, 'duration' => $dur, 'strategy' => 'opportunistic_speed');
                }
            }
        }

        return null;
    }

    /**
     * Section 3: Builds tactical strike fleet by role
     */
    public function buildTacticalStrikeFleet(array $fobData, array $targetDefenders, array $lootNeeded = array(), $category = 'farming', array $defenderFactors = array(), $isTargetOnline = false)
    {
        global $resource, $pricelist, $CombatCaps;

        $warshipsOnFob = array();
        $fodderOnFob   = array();
        $cargoOnFob    = array();

        // Escuadra Rápida de Intercepción para defensores ONLINE: Cruceros (206) y Acorazados (215) + Cargueros rápidos (217, 202)
        $warshipIds = $isTargetOnline 
            ? array(206, 215) 
            : array(206, 207, 211, 213, 214, 215, 216, 218, 221, 222, 224, 225, 226, 227, 228);
        $fodderIds  = array(204, 205);
        $cargoIds   = $isTargetOnline 
            ? array(217, 202) 
            : array(217, 203, 202);

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
        $totalCargoAvail    = array_sum($cargoOnFob);

        $isTargetDefenseless = empty($targetDefenders);

        // Si no hay naves de guerra ni fodder, pero es una misión de granjeo (farming) a un objetivo indefenso con cargueros disponibles:
        if ($totalWarshipsAvail <= 0 && $totalFodderAvail <= 0) {
            if ($category === 'farming' && $isTargetDefenseless && $totalCargoAvail > 0) {
                // Permitido: expedición de solo cargueros para farming de inactivos/planetas indefensos
            } else {
                return array();
            }
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
        } else {
            // Section 3: Fleet Crash & Tactical Siege - Right-Sizing & Multi-Tier Force Scaling
            $defMSE = 0.0;
            $totalEnemyDefenseUnits = 0;
            $totalEnemyCombatShips  = 0;

            if (!empty($targetDefenders) && is_array($targetDefenders)) {
                foreach ($targetDefenders as $dId => $dCnt) {
                    if ($dId == 212 || $dId == 502 || $dCnt <= 0) continue;
                    $defMSE += BotEconomyValuator::getElementPriceMSE($dId, $dCnt);
                    if ($dId >= 400 && $dId < 500) {
                        $totalEnemyDefenseUnits += (int)$dCnt;
                    } elseif ($dId >= 200 && $dId < 300) {
                        if (!in_array($dId, array(208, 209, 210, 219, 233))) {
                            $totalEnemyCombatShips += (int)$dCnt;
                        }
                    }
                }
            }

            // Tecnologías del atacante y defensor para modelado militar exacto
            $attFactors = isset($this->ctx->user['factor']) && is_array($this->ctx->user['factor'])
                ? $this->ctx->user['factor']
                : (function_exists('getFactors') ? getFactors($this->ctx->user, 'attack') : array());

            $defAttBonus = isset($defenderFactors['Attack']) ? (float)$defenderFactors['Attack'] : 0.0;
            $defDefBonus = isset($defenderFactors['Defensive']) ? (float)$defenderFactors['Defensive'] : 0.0;
            $defShiBonus = isset($defenderFactors['Shield']) ? (float)$defenderFactors['Shield'] : 0.0;
            $defTechMultiplier = max(1.0, 1.0 + max($defDefBonus, $defShiBonus));

            // Dimensionar pantalla de absorción (fodder) según doctrina y absorción de daño por amenaza:
            $totalEnemyAttack = 0.0;
            if (!empty($targetDefenders) && is_array($targetDefenders)) {
                foreach ($targetDefenders as $dId => $dCnt) {
                    if ($dId == 212 || $dId == 502 || $dCnt <= 0) continue;
                    $unitAtt = isset($CombatCaps[$dId]['attack']) ? (float)$CombatCaps[$dId]['attack'] : 0.0;
                    $totalEnemyAttack += $unitAtt * (1.0 + $defAttBonus) * (float)$dCnt;
                }
            }

            // Cada cazador ligero absorbe ~4,000 daño (estructura + escudo).
            $fodderByThreat = (int)ceil(($totalEnemyAttack / 4000.0) * 1.5);
            $fodderByCount  = (int)ceil(0.35 * ($totalEnemyDefenseUnits + 1.5 * $totalEnemyCombatShips));
            $fodderCount    = max(100, min(50000, max($fodderByThreat, $fodderByCount)));
            $targetFodder   = min($fodderCount, $totalFodderAvail);
            $fodderRemaining = $targetFodder;

            if (isset($fodderOnFob[204]) && $fodderOnFob[204] > 0) {
                $takeLF = min($fodderOnFob[204], $fodderRemaining);
                $fodderToSend[204] = $takeLF;
                $fodderRemaining -= $takeLF;
            }
            if ($fodderRemaining > 0 && isset($fodderOnFob[205]) && $fodderOnFob[205] > 0) {
                $takeHF = min($fodderOnFob[205], $fodderRemaining);
                $fodderToSend[205] = $takeHF;
            }

            // Prioridad dinámica de naves anti-fuego rápido y roles tácticos:
            $hasGraviton = !empty($targetDefenders[410]) && $targetDefenders[410] > 0;
            $hasLepton   = !empty($targetDefenders[412]) && $targetDefenders[412] > 0;

            if ($isTargetOnline) {
                $fleetPriority = array(215, 206);
            } elseif ($hasGraviton) {
                // Contra Cañón Gravitatorio (410, RF 240 vs Destructores #213):
                // Priorizar naves pesadas inmunes a su fuego rápido: Lunas Negras (216), Galeones (225), Fragatas (227), Vagabundos (228)
                $fleetPriority = array(216, 225, 227, 228, 211, 215, 206, 207, 213);
            } elseif ($hasLepton) {
                // Contra Cañón Leptón (412, RF vs 215 y 207):
                $fleetPriority = array(213, 225, 216, 211, 227, 228, 206, 215, 207);
            } elseif ($category === 'siege') {
                $fleetPriority = array(211, 213, 225, 215, 206, 207, 216, 227, 228);
            } else {
                $fleetPriority = array(215, 213, 206, 207, 216, 225, 227, 228);
            }

            // Calcular MSE total de naves de combate en FOB y MSE efectivo de defensa con tecnologías
            $totalFobWarshipMSE = 0.0;
            foreach ($warshipsOnFob as $wId => $qty) {
                if ($qty > 0) {
                    $totalFobWarshipMSE += BotEconomyValuator::getElementPriceMSE($wId, $qty);
                }
            }
            $effectiveDefMSE = $defMSE * $defTechMultiplier;

            // Evaluación de escalones de fuerza progresivos: 1.0x, 1.5x, 2.0x, 2.5x, 3.0x, 3.5x, 4.0x, 4.5x, 5.0x y MaxFOB
            $tiers = array(1.0, 1.5, 2.0, 2.5, 3.0, 3.5, 4.0, 4.5, 5.0, 'MaxFOB');
            $selectedWarships = array();
            $prevLossMSE = null;
            $prevFuelMSE = null;
            $gSpeed = FleetFunctions::GetGameSpeedFactor();
            $fDistUnits = 5000;

            foreach ($tiers as $tierMul) {
                $tierBudgetMSE = ($tierMul === 'MaxFOB') ? $totalFobWarshipMSE : max(50000000.0, $effectiveDefMSE * (float)$tierMul);
                $candidateWarships = array();
                $accumulatedMSE = 0.0;

                if ($tierBudgetMSE >= $totalFobWarshipMSE) {
                    $candidateWarships = $warshipsOnFob;
                } else {
                    foreach ($fleetPriority as $fWId) {
                        if (isset($warshipsOnFob[$fWId]) && $warshipsOnFob[$fWId] > 0) {
                            $unitPrice = BotEconomyValuator::getElementPriceMSE($fWId, 1);
                            if ($unitPrice <= 0) continue;
                            $remainingMSE = max(0.0, $tierBudgetMSE - $accumulatedMSE);
                            $neededShips = (int)ceil($remainingMSE / $unitPrice);
                            $take = min($warshipsOnFob[$fWId], $neededShips);
                            if ($take > 0) {
                                $candidateWarships[$fWId] = $take;
                                $accumulatedMSE += $take * $unitPrice;
                            }
                            if ($accumulatedMSE >= $tierBudgetMSE) break;
                        }
                    }
                    if ($accumulatedMSE < $tierBudgetMSE) {
                        foreach ($warshipsOnFob as $wId => $qty) {
                            if (!isset($candidateWarships[$wId]) && $qty > 0) {
                                $unitPrice = BotEconomyValuator::getElementPriceMSE($wId, 1);
                                if ($unitPrice <= 0) continue;
                                $remainingMSE = max(0.0, $tierBudgetMSE - $accumulatedMSE);
                                $neededShips = (int)ceil($remainingMSE / $unitPrice);
                                $take = min($qty, $neededShips);
                                if ($take > 0) {
                                    $candidateWarships[$wId] = $take;
                                    $accumulatedMSE += $take * $unitPrice;
                                }
                                if ($accumulatedMSE >= $tierBudgetMSE) break;
                            }
                        }
                    }
                }

                $testFleet = $candidateWarships + $fodderToSend;
                if (empty($testFleet)) continue;

                $simTier = BotCombatOracle::evaluate(
                    $testFleet,
                    $targetDefenders,
                    array('id' => $this->ctx->botId, 'factor' => $attFactors),
                    array('id' => 0, 'factor' => $defenderFactors),
                    2
                );

                $tierWinProb = isset($simTier['win_probability']) ? (float)$simTier['win_probability'] : 0.0;
                $tierAttLossPct = isset($simTier['att_loss_pct']) ? (float)$simTier['att_loss_pct'] : 0.20;

                $deployedTierMSE = 0.0;
                foreach ($testFleet as $sId => $cnt) {
                    $deployedTierMSE += BotEconomyValuator::getElementPriceMSE($sId, $cnt);
                }
                $currLossMSE = isset($simTier['att_loss_mse']) ? (float)$simTier['att_loss_mse'] : ($deployedTierMSE * $tierAttLossPct);

                $fSpeed = FleetFunctions::GetFleetMaxSpeed($testFleet, $this->ctx->user);
                $dur = FleetFunctions::GetMissionDuration(10, $fSpeed, $fDistUnits, $gSpeed, $this->ctx->user);
                $fuelCons = FleetFunctions::GetFleetConsumption($testFleet, $dur, $fDistUnits, $this->ctx->user, $gSpeed);
                $currFuelMSE = BotEconomyValuator::toMSE(0, 0, (float)$fuelCons);

                if ($prevLossMSE !== null && $prevFuelMSE !== null) {
                    $deltaOwnLossMSE = max(0.0, $prevLossMSE - $currLossMSE);
                    $deltaFuelMSE    = max(0.0, $currFuelMSE - $prevFuelMSE);
                    if ($deltaOwnLossMSE < $deltaFuelMSE && $tierWinProb >= 0.85) {
                        // La reducción de bajas es menor al incremento de combustible y la victoria está asegurada
                        $selectedWarships = $candidateWarships;
                        break;
                    }
                }

                $selectedWarships = $candidateWarships;
                $prevLossMSE = $currLossMSE;
                $prevFuelMSE = $currFuelMSE;

                if ($tierWinProb >= 0.95) {
                    // Victoria aplastante y contundente lograda
                    break;
                }
            }

            $warshipsToSend = !empty($selectedWarships) ? $selectedWarships : $warshipsOnFob;
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
        if (class_exists('BotStagingPlanner')) {
            $staging = new BotStagingPlanner($this->ctx, $this->gateway);
            $baseId = $staging->getBestStagingBase($empire, array('galaxy' => (int)$tG, 'system' => (int)$tS));
            if ($baseId > 0) {
                return $baseId;
            }
        }

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
        $lockedG = (int)($this->ctx->botRow['target_galaxy'] ?? 0);
        $lockedS = (int)($this->ctx->botRow['target_system'] ?? 0);
        $lockedP = (int)($this->ctx->botRow['target_planet'] ?? 0);

        if ($lockedG > 0 && $lockedS > 0 && $lockedP > 0) {
            $db = Database::get();
            $inFlight = $db->selectSingle("SELECT COUNT(*) as cnt FROM %%FLEETS%% WHERE fleet_owner = :botId AND fleet_end_galaxy = :g AND fleet_end_system = :s AND fleet_end_planet = :p AND fleet_mission IN (1, 2, 10) AND fleet_mess = 0;", array(
                ':botId' => $this->ctx->botId,
                ':g'     => $lockedG,
                ':s'     => $lockedS,
                ':p'     => $lockedP
            ));
            if (!empty($inFlight['cnt']) && (int)$inFlight['cnt'] > 0) {
                // Ordnance or fleet currently traveling in space: abort release!
                return;
            }
        }

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

        if ($lockedG > 0 && $lockedS > 0 && $lockedP > 0) {
            BotScheduler::cancelPendingTasksForTarget($this->ctx->botId, $lockedG, $lockedS, $lockedP);
        }

        $this->ctx->decisionLog->record(
            'target_released',
            "Released target {$coords} from tactical lock. Reason: {$reason}. Searching for new high-value target.",
            array('coords' => $coords, 'reason' => $reason),
            "release_{$coords}",
            1000.0
        );
    }
}

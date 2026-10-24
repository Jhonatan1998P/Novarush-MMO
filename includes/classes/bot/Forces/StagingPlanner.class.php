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
     * Determine best staging base or Forward Operating Base (FOB) closest to target.
     * Doctrina FOB Exclusivamente Lunar Anti-Phalanx:
     * - La FOB SOLO puede ser una Luna (planet_type === 3).
     * - Si empty($allMoons), limpiar fob_planet_id = 0 en BD y memoria, y retornar 0.
     * - Si !empty($allMoons), migrar o descartar fob_planet_id si era un planeta.
     * - Si targetCoord está presente: elegir la luna con salto cuántico (sprungtor > 0)
     *   más cercana al objetivo si la dificultad lo permite, o la luna más cercana en distancia.
     * - Sin targetCoord: elegir la luna con mayor infraestructura de salto/hangar o la primera válida.
     *
     * @param BotEmpireState $empire
     * @param array|null $targetCoord Optional ['galaxy' => int, 'system' => int]
     * @return int Target moon ID (strictly planet_type === 3) or 0 if no moons
     */
    public function getBestStagingBase(BotEmpireState $empire, array $targetCoord = null)
    {
        $allMoons = $empire->getMoons();

        // Regla Anti-Phalanx Absoluta: Si el imperio no posee ninguna luna, no hay FOB (retornar 0)
        // y limpiar fob_planet_id en BD y memoria para no regalar un objetivo al Phalanx enemigo.
        if (empty($allMoons)) {
            if (!empty($this->ctx->botRow['fob_planet_id'])) {
                $this->ctx->updateBotRow(array('fob_planet_id' => 0));
            }
            return 0;
        }

        // Sanitización y migración de fob_planet_id si apuntaba a un planeta
        if (!empty($this->ctx->botRow['fob_planet_id']) && (int)$this->ctx->botRow['fob_planet_id'] > 0) {
            $fobId = (int)$this->ctx->botRow['fob_planet_id'];
            $fobBody = $empire->getPlanet($fobId);
            if ($fobBody !== null) {
                if ((int)($fobBody['planet_type'] ?? $fobBody['data']['planet_type'] ?? 1) !== 3) {
                    // Apuntaba a un planeta: verificar si tiene luna propia en las mismas coordenadas
                    $migratedMoonId = 0;
                    $pG = (int)($fobBody['galaxy'] ?? $fobBody['data']['galaxy'] ?? 0);
                    $pS = (int)($fobBody['system'] ?? $fobBody['data']['system'] ?? 0);
                    $pP = (int)($fobBody['planet'] ?? $fobBody['data']['planet'] ?? 0);
                    foreach ($allMoons as $mId => $mData) {
                        $mG = (int)($mData['galaxy'] ?? $mData['data']['galaxy'] ?? 0);
                        $mS = (int)($mData['system'] ?? $mData['data']['system'] ?? 0);
                        $mP = (int)($mData['planet'] ?? $mData['data']['planet'] ?? 0);
                        if ($mG === $pG && $mS === $pS && $mP === $pP) {
                            $migratedMoonId = (int)$mId;
                            break;
                        }
                    }
                    if ($migratedMoonId > 0) {
                        $this->ctx->updateBotRow(array('fob_planet_id' => $migratedMoonId));
                    } else {
                        // El planeta no tiene luna propia: descartar fob_planet_id
                        $this->ctx->updateBotRow(array('fob_planet_id' => 0));
                    }
                }
            } else {
                $this->ctx->updateBotRow(array('fob_planet_id' => 0));
            }
        }

        $canUseGate = (isset($this->ctx->difficulty) && method_exists($this->ctx->difficulty, 'canUseQuantumJumpStaging'))
            ? $this->ctx->difficulty->canUseQuantumJumpStaging()
            : false;

        // Caso 1: Objetivo activo ($targetCoord)
        if ($targetCoord !== null && isset($targetCoord['galaxy'], $targetCoord['system'])) {
            $tG = (int)$targetCoord['galaxy'];
            $tS = (int)$targetCoord['system'];

            $calcDist = function($mG, $mS) use ($tG, $tS) {
                if ($mG == $tG) {
                    return abs($mS - $tS);
                }
                return 10000 + (abs($mG - $tG) * 1000) + abs($mS - $tS);
            };

            // Si la dificultad permite salto cuántico, buscar lunas con Puerta de Salto Cuántico (sprungtor > 0)
            if ($canUseGate) {
                $gateMoons = array();
                foreach ($allMoons as $mId => $mData) {
                    $gate = (int)($mData['sprungtor'] ?? $mData['data']['sprungtor'] ?? 0);
                    if ($gate > 0) {
                        $gateMoons[$mId] = $mData;
                    }
                }

                if (!empty($gateMoons)) {
                    $bestGateMoonId = 0;
                    $minGateDist = PHP_INT_MAX;
                    foreach ($gateMoons as $mId => $mData) {
                        $mG = (int)($mData['galaxy'] ?? $mData['data']['galaxy'] ?? 0);
                        $mS = (int)($mData['system'] ?? $mData['data']['system'] ?? 0);
                        $dist = $calcDist($mG, $mS);
                        if ($dist < $minGateDist) {
                            $minGateDist = $dist;
                            $bestGateMoonId = (int)$mId;
                        }
                    }
                    if ($bestGateMoonId > 0) {
                        return $bestGateMoonId;
                    }
                }
            }

            // En su defecto, elegir la Luna propia más cercana al objetivo en distancia galáctica/sistema
            $bestMoonId = 0;
            $minMoonDist = PHP_INT_MAX;
            foreach ($allMoons as $mId => $mData) {
                $mG = (int)($mData['galaxy'] ?? $mData['data']['galaxy'] ?? 0);
                $mS = (int)($mData['system'] ?? $mData['data']['system'] ?? 0);
                $dist = $calcDist($mG, $mS);
                if ($dist < $minMoonDist) {
                    $minMoonDist = $dist;
                    $bestMoonId = (int)$mId;
                }
            }

            if ($bestMoonId > 0) {
                return $bestMoonId;
            }
        }

        // Caso 2: Sin $targetCoord
        // Si ya hay una luna asignada como FOB en fob_planet_id y sigue existiendo en $allMoons, respetarla
        if (!empty($this->ctx->botRow['fob_planet_id']) && isset($allMoons[(int)$this->ctx->botRow['fob_planet_id']])) {
            return (int)$this->ctx->botRow['fob_planet_id'];
        }

        // Si no hay FOB fijada, elegir la luna con mayor infraestructura de salto cuántico / hangar / campos
        $bestMoonId = 0;
        $bestScore = -1;
        foreach ($allMoons as $mId => $mData) {
            $gate   = (int)($mData['sprungtor'] ?? $mData['data']['sprungtor'] ?? 0);
            $hangar = (int)($mData['hangar'] ?? $mData['data']['hangar'] ?? 0);
            $fields = (int)($mData['field_current'] ?? $mData['data']['field_current'] ?? 0);
            $score  = ($gate * 1000) + ($hangar * 10) + $fields;
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMoonId = (int)$mId;
            }
        }

        if ($bestMoonId > 0) {
            return $bestMoonId;
        }

        $moonIds = array_keys($allMoons);
        return (int)$moonIds[0];
    }

    /**
     * Rally idle combat ships to staging base / FOB if local forces do not have >90% win rate
     *
     * @param BotEmpireState $empire
     * @param int $stagingBaseId
     * @param array $targetDefenders Known enemy ships and defenses
     * @return int Number of fleets deployed
     */
    public function rallyFleets(BotEmpireState $empire, $stagingBaseId, array $targetDefenders = array(), $isUrgent = false)
    {
        global $resource, $reslist, $pricelist;
        if (empty($pricelist)) {
            require 'includes/vars/General.php';
        }

        if ($stagingBaseId <= 0) {
            return 0;
        }

        $target = $empire->getPlanet($stagingBaseId);
        if (empty($target)) {
            return 0;
        }

        $tData = isset($target['data']) ? $target['data'] : $target;
        if ((int)($tData['planet_type'] ?? 1) !== 3) {
            return 0;
        }

        $fleetList = (isset($reslist['fleet']) && is_array($reslist['fleet'])) ? $reslist['fleet'] : array();

        $fobData = $tData;
        $fobCombatFleet = array();
        foreach ($fleetList as $shipId) {
            if (in_array($shipId, array(202, 203, 208, 209, 210, 212, 217, 219, 233))) continue;
            $cnt = isset($fobData[$resource[$shipId]]) ? (int)$fobData[$resource[$shipId]] : 0;
            if ($cnt > 0) {
                $fobCombatFleet[$shipId] = $cnt;
            }
        }

        $db = Database::get();

        // Query active in-flight deployments to FOB to count effective firepower
        $activeInbound = $db->select("SELECT fleet_array FROM %%FLEETS%% WHERE fleet_owner = :botId AND fleet_end_id = :fobId AND fleet_mission = 4 AND fleet_mess = 0;", array(
            ':botId' => $this->ctx->botId,
            ':fobId' => $stagingBaseId
        ));
        $effectiveFobFleet = $fobCombatFleet;
        if (!empty($activeInbound)) {
            foreach ($activeInbound as $aib) {
                $fArr = FleetFunctions::unserialize($aib['fleet_array']);
                if (is_array($fArr)) {
                    foreach ($fArr as $sId => $cnt) {
                        $sId = (int)$sId;
                        if (in_array($sId, array(202, 203, 208, 209, 210, 212, 217, 219, 233))) continue;
                        $effectiveFobFleet[$sId] = ($effectiveFobFleet[$sId] ?? 0) + (int)$cnt;
                    }
                }
            }
        }

        // Check if the fleet already stationed or inbound to FOB is sufficient (>90% win rate or massive garrison)
        if (!empty($targetDefenders)) {
            if (!empty($effectiveFobFleet)) {
                $sim = BotCombatOracle::evaluate(
                    $effectiveFobFleet,
                    $targetDefenders,
                    array('id' => $this->ctx->botId, 'factor' => $this->ctx->user['factor']),
                    array('id' => 0, 'factor' => array()),
                    1
                );
                if (isset($sim['win_probability']) && $sim['win_probability'] >= 0.90) {
                    // Local FOB firepower + inbound deployments already exceeds 90% win rate; keep remaining fleets stationed
                    return 0;
                }
            }
        } else {
            // Si el objetivo está indefenso o la inteligencia aún no reporta defensores, pero la FOB ya cuenta con guarnición respetable (>= 5,000 naves de combate), no saturar en despliegues salvo urgencia
            if (!$isUrgent && !empty($effectiveFobFleet) && array_sum($effectiveFobFleet) >= 5000) {
                return 0;
            }
        }

        // All celestial bodies in the empire
        $allBodies = $empire->getAllBodies();
        if (!is_array($allBodies) || empty($allBodies)) {
            return 0;
        }

        // Detect FOB Moon and Quantum Jump Gate readiness
        $now = class_exists('BotClock') ? BotClock::get() : TIMESTAMP;
        $config = Config::get();
        $gateWaitTime = isset($config->gate_wait_time) ? (int)$config->gate_wait_time : 3600;

        $fobMoonId = 0;
        $fobMoonReady = false;
        $tData = isset($target['data']) ? $target['data'] : $target;
        if ((int)($tData['planet_type'] ?? 1) === 3) {
            $fobMoonId = (int)$target['id'];
            if ((int)($tData['sprungtor'] ?? 0) > 0 && $now >= ((int)($tData['last_jump_time'] ?? 0) + $gateWaitTime)) {
                $fobMoonReady = true;
            }
        } elseif (!empty($tData['id_luna']) && (int)$tData['id_luna'] > 0) {
            $fobMoonId = (int)$tData['id_luna'];
            $fobMoonBody = $empire->getPlanet($fobMoonId);
            $fobMoonData = !empty($fobMoonBody) ? $fobMoonBody['data'] : null;
            if (!empty($fobMoonData) && (int)($fobMoonData['sprungtor'] ?? 0) > 0 && $now >= ((int)($fobMoonData['last_jump_time'] ?? 0) + $gateWaitTime)) {
                $fobMoonReady = true;
            }
        }

        // Helper to find any ready destination moon in the FOB sector (same system or <= 2 systems away)
        $findReadyFobMoon = function($excludeId = 0) use ($fobMoonReady, $fobMoonId, $allBodies, $tData, $now, $gateWaitTime) {
            if ($fobMoonReady && $fobMoonId > 0 && $fobMoonId != $excludeId) {
                return $fobMoonId;
            }
            foreach ($allBodies as $altId => $altBody) {
                if ($altId == $fobMoonId || $altId == $excludeId) continue;
                $altData = $altBody['data'];
                if ((int)($altData['planet_type'] ?? 1) === 3 && (int)($altData['sprungtor'] ?? 0) > 0) {
                    $altReady = ($now >= ((int)($altData['last_jump_time'] ?? 0) + $gateWaitTime));
                    if ($altReady && (int)$altData['galaxy'] === (int)$tData['galaxy'] && abs((int)$altData['system'] - (int)$tData['system']) <= 2) {
                        return (int)$altId;
                    }
                }
            }
            return 0;
        };

        $hasSectorFobGate = false;
        if ($fobMoonId > 0) {
            $fobMoonBody = $empire->getPlanet($fobMoonId);
            if (!empty($fobMoonBody) && (int)($fobMoonBody['data']['sprungtor'] ?? 0) > 0) {
                $hasSectorFobGate = true;
            }
        }
        if (!$hasSectorFobGate) {
            foreach ($allBodies as $altId => $altBody) {
                $altData = $altBody['data'];
                if ((int)($altData['planet_type'] ?? 1) === 3 && (int)($altData['sprungtor'] ?? 0) > 0) {
                    if ((int)$altData['galaxy'] === (int)$tData['galaxy'] && abs((int)$altData['system'] - (int)$tData['system']) <= 2) {
                        $hasSectorFobGate = true;
                        break;
                    }
                }
            }
        }

        $rallied = 0;

        $canQuantumJump = (isset($this->ctx->difficulty) && method_exists($this->ctx->difficulty, 'canUseQuantumJumpStaging'))
            ? $this->ctx->difficulty->canUseQuantumJumpStaging()
            : false;

        // --- PHASE 1: DIRECT QUANTUM JUMP GATE NETWORK (0 FLEET SLOTS, 0 DEUTERIUM) ---
        if ($canQuantumJump) {
            foreach ($allBodies as $pId => $body) {
                $pData = $body['data'];
                if ((int)($pData['planet_type'] ?? 1) === 3) {
                    $destMoonId = $findReadyFobMoon($pId);
                    if ($destMoonId > 0 && $pId != $destMoonId) {
                        $originJumpReady = ((int)($pData['sprungtor'] ?? 0) > 0 && $now >= ((int)($pData['last_jump_time'] ?? 0) + $gateWaitTime));
                        if ($originJumpReady) {
                            $jumpShips = array();
                            foreach ($fleetList as $shipId) {
                                if (in_array($shipId, array(208, 210, 212, 233))) continue;
                                $cnt = isset($pData[$resource[$shipId]]) ? (int)$pData[$resource[$shipId]] : 0;
                                if ($cnt > 0) {
                                    $jumpShips[$shipId] = $cnt;
                                }
                            }
                            if (!empty($jumpShips)) {
                                $jumpResult = $this->gateway->executeJumpGate($pId, $destMoonId, $jumpShips);
                                if ($jumpResult) {
                                    $rallied++;
                                    foreach ($jumpShips as $sId => $sCnt) {
                                        $effectiveFobFleet[$sId] = ($effectiveFobFleet[$sId] ?? 0) + $sCnt;
                                    }
                                    $this->ctx->decisionLog->record(
                                        'fleet_staging_jump_gate',
                                        "Quantum Jump transfer from Moon #{$pId} to FOB Sector Moon #{$destMoonId}: " . number_format(array_sum($jumpShips)) . " warships transferred instantly (0 Deuterium)",
                                        array('from_moon' => $pId, 'to_moon' => $destMoonId, 'ships' => $jumpShips),
                                        "jump_staging_{$pId}_to_{$destMoonId}",
                                        array_sum($jumpShips)
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }

        // --- PHASE 2: CONVENTIONAL FLEET STAGING & ORBITAL ASCENT ---
        // Reserve 1 slot for the campaign assault strike, strictly respecting shielded military slots
        $maxSlots       = FleetFunctions::GetMaxFleetSlots($this->ctx->user);
        $actualFleets   = FleetFunctions::GetCurrentFleets($this->ctx->botId);
        $availableMil   = BotActionGateway::getAvailableMilitarySlots($this->ctx->botId, $this->ctx->user);
        if ($actualFleets >= ($maxSlots - 1) || $availableMil < 1) {
            return $rallied;
        }

        // Query active rallies from colonies to avoid stacking duplicate rallies
        $activeRallies = $db->select("SELECT fleet_start_id FROM %%FLEETS%% WHERE fleet_owner = :botId AND fleet_mission = 4;", array(
            ':botId' => $this->ctx->botId
        ));
        $rallyingOrigins = array();
        foreach ($activeRallies as $ar) {
            $rallyingOrigins[(int)$ar['fleet_start_id']] = true;
        }

        // Prioriza transferencias en Misión 4 hacia Lunas propias (Luna -> Luna)
        uasort($allBodies, function($a, $b) use ($tData) {
            $aType = (int)($a['data']['planet_type'] ?? 1);
            $bType = (int)($b['data']['planet_type'] ?? 1);
            if ((int)($tData['planet_type'] ?? 1) === 3 && $aType !== $bType) {
                return ($aType === 3) ? -1 : 1;
            }
            return 0;
        });

        foreach ($allBodies as $pId => $body) {
            if ($pId == $stagingBaseId) continue;
            if (isset($rallyingOrigins[$pId])) continue; // Already rallying from this colony

            // Enforce slot reservation dynamically (leaving 1 slot for the assault)
            if ($actualFleets >= ($maxSlots - 1)) {
                break;
            }

            $pData = $body['data'];
            $combatShips = array();

            // Calculate 2 hours of physical resource production on this colony (Metal + Crystal + Deuterium, NOT MSE)
            $prod2h = 2.0 * (
                (float)($pData['metal_perhour'] ?? 0.0) +
                (float)($pData['crystal_perhour'] ?? 0.0) +
                (float)($pData['deuterium_perhour'] ?? 0.0)
            );
            if ($prod2h <= 0.0) {
                $prod2h = 100000.0;
            }

            // Real physical stock on the colony (Metal + Crystal + Deuterium)
            $stockFisicoTotalActual = (float)($pData['metal'] ?? 0.0) +
                                      (float)($pData['crystal'] ?? 0.0) +
                                      (float)($pData['deuterium'] ?? 0.0);

            // 1. Calculate minimum transporters to retain to move >= max(2h production, real physical stock)
            // guaranteeing FleetsaveCalculator can evacuate 100% of real stock
            $keepTrans = array(217 => 0, 203 => 0, 202 => 0);
            $remTransCap = max($prod2h, $stockFisicoTotalActual);
            foreach (array(217, 203, 202) as $tId) {
                if ($remTransCap <= 0) break;
                $avail = isset($pData[$resource[$tId]]) ? (int)$pData[$resource[$tId]] : 0;
                if ($avail <= 0) continue;
                $cap = max(1.0, (float)($pricelist[$tId]['capacity'] ?? 5000));
                if (class_exists('FleetFunctions')) {
                    $calcCap = FleetFunctions::GetFleetRoom(array($tId => 1), $this->ctx->user);
                    if ($calcCap > 0) $cap = $calcCap;
                }
                $needed = (int)ceil($remTransCap / $cap);
                $take = min($avail, $needed);
                $keepTrans[$tId] = $take;
                $remTransCap -= ($take * $cap);
            }
            if (array_sum($keepTrans) === 0) {
                foreach (array(217, 203, 202) as $tId) {
                    $avail = isset($pData[$resource[$tId]]) ? (int)$pData[$resource[$tId]] : 0;
                    if ($avail > 0) {
                        $keepTrans[$tId] = min($avail, 5);
                        break;
                    }
                }
            }

            // 2. Calculate minimum recyclers to retain to harvest >= 2h production
            $keepRec = array(219 => 0, 209 => 0);
            $remRecCap = $prod2h;
            foreach (array(219, 209) as $rId) {
                if ($remRecCap <= 0) break;
                $avail = isset($pData[$resource[$rId]]) ? (int)$pData[$resource[$rId]] : 0;
                if ($avail <= 0) continue;
                $cap = max(1.0, (float)($pricelist[$rId]['capacity'] ?? 20000));
                $needed = (int)ceil($remRecCap / $cap);
                $take = min($avail, $needed);
                $keepRec[$rId] = $take;
                $remRecCap -= ($take * $cap);
            }
            if (array_sum($keepRec) === 0) {
                foreach (array(219, 209) as $rId) {
                    $avail = isset($pData[$resource[$rId]]) ? (int)$pData[$resource[$rId]] : 0;
                    if ($avail > 0) {
                        $keepRec[$rId] = min($avail, 5);
                        break;
                    }
                }
            }

            // Check combat & campaign logistics ships
            $candidateShips = array();
            foreach ($fleetList as $shipId) {
                // Non-tactical civil units that should never rally (colonizers, probes, satellites, terraformers)
                if (in_array($shipId, array(208, 210, 212, 233))) {
                    continue;
                }

                $cnt = isset($pData[$resource[$shipId]]) ? (int)$pData[$resource[$shipId]] : 0;
                if ($cnt <= 0) continue;

                // Transporters: retain units to cover at least 2h physical production, rally the rest
                if (in_array($shipId, array(202, 203, 217))) {
                    $retained = $keepTrans[$shipId] ?? 0;
                    $rallyQty = max(0, $cnt - $retained);
                    if ($rallyQty > 0) {
                        $candidateShips[$shipId] = $rallyQty;
                    }
                }
                // Recyclers: retain units to cover at least 2h physical production, rally the rest
                elseif (in_array($shipId, array(209, 219))) {
                    $retained = $keepRec[$shipId] ?? 0;
                    $rallyQty = max(0, $cnt - $retained);
                    if ($rallyQty > 0) {
                        $candidateShips[$shipId] = $rallyQty;
                    }
                }
                // Combat warships:
                else {
                    $candidateShips[$shipId] = $cnt;
                }
            }

            // Minimum squadron size of 1 combat ship, or presence of idle recyclers to concentrate
            $hasRecyclers = (!empty($candidateShips[219]) || !empty($candidateShips[209]));
            if (empty($candidateShips) || (!$hasRecyclers && array_sum($candidateShips) < 1)) {
                continue;
            }

            // 3. FMA Sizing & Fuel-Aware Budgeting
            $isMoonOrigin = ((int)($pData['planet_type'] ?? 1) === 3);
            $canJumpToFob = ($fobMoonReady && $fobMoonId > 0 && $isMoonOrigin && $pId !== $fobMoonId && ((int)($pData['sprungtor'] ?? 0) > 0));
            $stockDeut = (float)($pData['deuterium'] ?? 0.0);
            $fuelBudget = max(0.0, $stockDeut * 0.90); // Reserve 10% for local operations
            if (!$canJumpToFob && $fuelBudget < 500.0) {
                continue; // Insufficient local fuel to launch conventional deploy
            }

            $origCoords   = array((int)$pData['galaxy'], (int)$pData['system'], (int)$pData['planet']);
            $targetCoords = array((int)$target['galaxy'], (int)$target['system'], (int)$target['planet']);
            $dist         = FleetFunctions::GetTargetDistance($origCoords, $targetCoords);
            $gameSpeed    = FleetFunctions::GetGameSpeedFactor();

            // Sizing: prioritize capital and heavy warships
            $selectedShips = array();
            if (!empty($targetDefenders)) {
                $targetMSE = 0.0;
                foreach ($targetDefenders as $dId => $dCnt) {
                    $targetMSE += BotEconomyValuator::getElementPriceMSE((int)$dId, (float)$dCnt);
                }
                $targetCapMSE = $targetMSE * 2.2;
                $currentMSE = 0.0;
                foreach ($effectiveFobFleet as $sId => $cnt) {
                    $currentMSE += BotEconomyValuator::getElementPriceMSE((int)$sId, (float)$cnt);
                }
                $remNeededMSE = max(50000000.0, ($targetCapMSE * 1.15) - $currentMSE);

                $priorityOrder = array(213, 225, 215, 211, 206, 207, 205, 204, 217, 203, 219, 209, 202);
                $accumMSE = 0.0;
                foreach ($priorityOrder as $sId) {
                    if (empty($candidateShips[$sId])) continue;
                    $priceMSE = BotEconomyValuator::getElementPriceMSE($sId, 1);
                    if ($priceMSE <= 0) $priceMSE = 1000.0;
                    $avail = $candidateShips[$sId];
                    if ($accumMSE < $remNeededMSE) {
                        $take = min($avail, (int)ceil(($remNeededMSE - $accumMSE) / $priceMSE));
                        if ($take > 0) {
                            $selectedShips[$sId] = $take;
                            $accumMSE += ($take * $priceMSE);
                        }
                    }
                }
                // Always concentrate idle recyclers & utility ships to the FOB
                foreach (array(219, 209, 217, 203) as $sId) {
                    if (!empty($candidateShips[$sId])) {
                        $selectedShips[$sId] = $candidateShips[$sId];
                    }
                }
            }

            if (empty($selectedShips) || (!$hasRecyclers && array_sum($selectedShips) < 1)) {
                $selectedShips = $candidateShips;
            }

            // --- TACTICAL QUANTUM JUMP GATE NETWORK (0 DEUTERIUM) ---
            if (!empty($selectedShips)) {
                // Caso A: Salto Directo Luna Origen -> Luna FOB (o Luna Secundaria de Sector) a 0 Deuterio
                if ($canQuantumJump && (int)($pData['planet_type'] ?? 1) === 3 && (int)($pData['sprungtor'] ?? 0) > 0) {
                    $destMoonId = $findReadyFobMoon($pId);
                    $originJumpReady = ($now >= ((int)($pData['last_jump_time'] ?? 0) + $gateWaitTime));
                    if ($destMoonId > 0 && $originJumpReady) {
                        $jumpResult = $this->gateway->executeJumpGate($pId, $destMoonId, $selectedShips);
                        if ($jumpResult) {
                            $rallied++;
                            foreach ($selectedShips as $sId => $sCnt) {
                                $effectiveFobFleet[$sId] = ($effectiveFobFleet[$sId] ?? 0) + $sCnt;
                            }
                            $this->ctx->decisionLog->record(
                                'fleet_staging_jump_gate',
                                "Quantum Jump transfer from Moon #{$pId} to FOB Sector Moon #{$destMoonId}: " . number_format(array_sum($selectedShips)) . " warships transferred instantly (0 Deuterium)",
                                array('from_moon' => $pId, 'to_moon' => $destMoonId, 'ships' => $selectedShips),
                                "jump_staging_{$pId}_to_{$destMoonId}",
                                array_sum($selectedShips)
                            );
                            continue; // Flota transferida instantáneamente; no requiere Misión 4
                        }
                    }
                    // Si el sector FOB cuenta con red de Salto Cuántico pero las puertas están en enfriamiento (<= 3600s),
                    // RETENER flota en luna de origen esperando el próximo turno. PROHIBIDO fugar a Misión 4 con gasto de deuterio.
                    if ($hasSectorFobGate) {
                        $this->ctx->decisionLog->record(
                            'fleet_staging_moon_retained',
                            "Retaining combat fleet on Moon #{$pId}: Jump Gate network active in FOB sector; awaiting gate cooldown to transfer at 0 Deuterium.",
                            array('origin_moon' => $pId, 'target_fob' => $stagingBaseId, 'ships' => $selectedShips),
                            "moon_retained_{$pId}",
                            array_sum($selectedShips)
                        );
                        continue;
                    }
                }

                // Caso B: Ascenso Orbital Anti-Phalanx Planeta -> Luna Propia
                $canOrbitalAscent = (isset($this->ctx->difficulty) && method_exists($this->ctx->difficulty, 'canUseOrbitalAscent'))
                    ? $this->ctx->difficulty->canUseOrbitalAscent()
                    : false;

                if ($canOrbitalAscent && (int)($pData['planet_type'] ?? 1) === 1 && !empty($pData['id_luna']) && (int)$pData['id_luna'] > 0) {
                    $ownMoonId = (int)$pData['id_luna'];
                    if ($ownMoonId !== (int)$stagingBaseId && $actualFleets < ($maxSlots - 1)) {
                        $okAscent = $this->gateway->launchFleet(
                            $selectedShips,
                            4, // Despliegue orbital corto
                            $pId,
                            $ownMoonId,
                            $this->ctx->botId,
                            (int)$pData['galaxy'],
                            (int)$pData['system'],
                            (int)$pData['planet'],
                            3, // Luna propia
                            array(),
                            10 // Máxima velocidad (distancia mínima = 5, arribo en segundos)
                        );
                        if ($okAscent) {
                            $rallied++;
                            $actualFleets++;
                            $this->ctx->decisionLog->record(
                                'orbital_ascent_anti_phalanx',
                                "Short orbital ascent from Colony #{$pId} to own Moon #{$ownMoonId} for Anti-Phalanx fleet staging towards FOB #{$stagingBaseId}",
                                array('origin' => $pId, 'own_moon' => $ownMoonId, 'target_fob' => $stagingBaseId, 'ships' => $selectedShips),
                                "ascent_{$pId}_to_moon_{$ownMoonId}",
                                array_sum($selectedShips)
                            );
                            continue;
                        }
                    }
                }
            }

            // Check tactical urgency: if FOB has target lock / siege or incoming threat
            $hasUrgency = $isUrgent ||
                          (!empty($this->ctx->botRow['offensive_urgency'])) ||
                          (!empty($this->ctx->botRow['target_lock_time']) && (int)$this->ctx->botRow['target_lock_time'] > 0 && !empty($this->ctx->botRow['siege_cycles']) && (int)$this->ctx->botRow['siege_cycles'] > 0) ||
                          (!empty($this->ctx->botRow['target_galaxy']) && !empty($this->ctx->botRow['target_system']));

            // Economic speed selector: if no offensive urgency, test speed 6 down to 1 (50%-60%); if urgent, test 10 down to 1
            $maxSpdCandidate = $hasUrgency ? 10 : 6;

            $fleetSpeed = FleetFunctions::GetFleetMaxSpeed($selectedShips, $this->ctx->user);
            $chosenSpeed = 0;
            $chosenConsumption = 0;

            for ($spd = $maxSpdCandidate; $spd >= 1; $spd--) {
                $dur = FleetFunctions::GetMissionDuration($spd, $fleetSpeed, $dist, $gameSpeed, $this->ctx->user);
                $cons = FleetFunctions::GetFleetConsumption($selectedShips, $dur, $dist, $this->ctx->user, $gameSpeed);
                if ($cons <= $fuelBudget) {
                    $chosenSpeed = $spd;
                    $chosenConsumption = $cons;
                    break;
                }
            }

            // If even at 10% speed consumption exceeds fuelBudget, scale down fleet to fit budget safely
            if ($chosenSpeed === 0) {
                $dur1 = FleetFunctions::GetMissionDuration(1, $fleetSpeed, $dist, $gameSpeed, $this->ctx->user);
                $cons1 = FleetFunctions::GetFleetConsumption($selectedShips, $dur1, $dist, $this->ctx->user, $gameSpeed);
                if ($cons1 > 0) {
                    $scale = min(0.95, ($fuelBudget / $cons1) * 0.90);
                    $scaled = array();
                    foreach ($selectedShips as $sId => $cnt) {
                        $nCnt = (int)floor($cnt * $scale);
                        if ($nCnt > 0) {
                            $scaled[$sId] = $nCnt;
                        }
                    }
                    if (!empty($scaled) && ($hasRecyclers || array_sum($scaled) >= 25)) {
                        $selectedShips = $scaled;
                        $fleetSpeed = FleetFunctions::GetFleetMaxSpeed($selectedShips, $this->ctx->user);
                        for ($spd = $maxSpdCandidate; $spd >= 1; $spd--) {
                            $dur = FleetFunctions::GetMissionDuration($spd, $fleetSpeed, $dist, $gameSpeed, $this->ctx->user);
                            $cons = FleetFunctions::GetFleetConsumption($selectedShips, $dur, $dist, $this->ctx->user, $gameSpeed);
                            if ($cons <= $fuelBudget) {
                                $chosenSpeed = $spd;
                                $chosenConsumption = $cons;
                                break;
                            }
                        }
                    }
                }
            }

            if ($chosenSpeed <= 0 || empty($selectedShips) || (!$hasRecyclers && array_sum($selectedShips) < 25)) {
                continue;
            }

            // Piggybacking of excess fuel to FOB in Mission 4 (Deploy)
            $totalFleetRoom = FleetFunctions::GetFleetRoom($selectedShips, $this->ctx->user);
            $netCargo = max(0.0, (float)$totalFleetRoom - (float)$chosenConsumption);

            // Calculate untouchable local fleetsave reserve for remaining ships at 10% speed
            $remainingShips = array();
            foreach ($fleetList as $sId) {
                $avail = isset($pData[$resource[$sId]]) ? (int)$pData[$resource[$sId]] : 0;
                $taken = isset($selectedShips[$sId]) ? (int)$selectedShips[$sId] : 0;
                $left  = max(0, $avail - $taken);
                if ($left > 0) {
                    $remainingShips[$sId] = $left;
                }
            }

            $localFsReserve = 0.0;
            if (!empty($remainingShips)) {
                $remSpeed = FleetFunctions::GetFleetMaxSpeed($remainingShips, $this->ctx->user);
                $durFs = FleetFunctions::GetMissionDuration(1, $remSpeed, $dist, $gameSpeed, $this->ctx->user);
                $localFsReserve = FleetFunctions::GetFleetConsumption($remainingShips, $durFs, $dist, $this->ctx->user, $gameSpeed);
            }
            $localFsReserve = max(5000.0, $localFsReserve);

            // Calculate committed deuterium in local queues or active savings lock on this colony
            $localCommittedDeut = 0.0;
            if (isset($this->ctx->budgetManager)) {
                $lock = method_exists($this->ctx->budgetManager, 'getSavingsLockForCategory')
                    ? $this->ctx->budgetManager->getSavingsLockForCategory($pId, 'fleet')
                    : $this->ctx->budgetManager->getSavingsLock();
                if (!empty($lock) && isset($lock['planet_id']) && (int)$lock['planet_id'] === (int)$pId) {
                    $localCommittedDeut += max(0.0, (float)($lock['cost'][903] ?? $lock['cost']['deuterium'] ?? 0.0));
                }
            }

            // Excedente financiado exclusivamente de la bolsa virtual 'fleet'
            $colonyDeut = (float)($pData['deuterium'] ?? 0.0);
            $fleetDeutVirtual = $colonyDeut;
            if (isset($this->ctx->budgetManager) && method_exists($this->ctx->budgetManager, 'getPlanetFleetCredits')) {
                $fleetCreds = $this->ctx->budgetManager->getPlanetFleetCredits($pId);
                $fleetDeutVirtual = (float)($fleetCreds['deuterium'] ?? 0.0);
            }

            $excessFleetDeut    = max(0.0, $fleetDeutVirtual - (float)$chosenConsumption - $localFsReserve - $localCommittedDeut);
            $excessPhysDeut     = max(0.0, $colonyDeut - (float)$chosenConsumption - $localFsReserve - $localCommittedDeut);
            $availableCargoDeut = min($excessFleetDeut, $excessPhysDeut);
            $cargoDeut          = floor(min($netCargo, $availableCargoDeut));

            $cargoResources = array();
            if ($cargoDeut > 0.0) {
                $cargoResources[903] = (float)$cargoDeut;
            }

            // Launch Deploy (Mission 4)
            $ok = $this->gateway->launchFleet(
                $selectedShips,
                4, // Mission 4: Stay / Deploy
                $pId,
                (int)($target['id'] ?? $stagingBaseId),
                $this->ctx->botId,
                (int)($target['galaxy'] ?? $tData['galaxy']),
                (int)($target['system'] ?? $tData['system']),
                (int)($target['planet'] ?? $tData['planet']),
                (int)($target['planet_type'] ?? $tData['planet_type'] ?? 3),
                $cargoResources,
                $chosenSpeed
            );

            if ($ok) {
                $rallied++;
                $actualFleets++;
                foreach ($selectedShips as $sId => $sCnt) {
                    $effectiveFobFleet[$sId] = ($effectiveFobFleet[$sId] ?? 0) + $sCnt;
                }
                $this->ctx->decisionLog->record(
                    'fleet_staging',
                    "Rallied combat fleet from colony #{$pId} to FOB #{$stagingBaseId} to consolidate firepower (Speed: {$chosenSpeed}0%, Ships: " . number_format(array_sum($selectedShips)) . ", Fuel: " . number_format($chosenConsumption) . " Deut, Piggyback Deut: " . number_format($cargoDeut) . ")",
                    array('from' => $pId, 'to' => $stagingBaseId, 'ships' => $selectedShips, 'speed' => $chosenSpeed, 'fuel' => $chosenConsumption, 'cargo_deut' => $cargoDeut),
                    "rally_{$pId}_to_{$stagingBaseId}",
                    array_sum($selectedShips)
                );
            }
        }

        return $rallied;
    }
}

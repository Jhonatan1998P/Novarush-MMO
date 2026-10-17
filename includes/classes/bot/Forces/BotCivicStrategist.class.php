<?php

require_once 'includes/classes/PremiumEconomy.class.php';
require_once 'includes/classes/bot/Kernel/BotClock.class.php';
require_once 'includes/classes/bot/Evaluation/EconomyValuator.class.php';

/**
 * NovaRush Bot AI - Civic Matrix, Parties & Ideologies Strategist (FASE 3)
 *
 * Responsibilities:
 * 1. Immutability Detection:
 *    - If race > 0 AND formgovernment > 0 in uni1_users, do NOT alter civics (avoids reset costs).
 * 2. Personality-driven Assignment (when race == 0 and formgovernment == 0):
 *    - miner / economic: Fotorianos (1501) + República (1602) + Bien (1701)
 *    - raider / aggressive: Kratonianos (1504) + Imperio (1601) + Maldad (1703)
 *    - turtle / defensive: Sinuria (1502) + Imperio (1601) + Neutral (1702)
 *    - balanced: Humanos (1503) + Democracia (1603) + Neutral (1702)
 * 3. Planetary Parties (ShowPartyPage, 1201..1208):
 *    - Timely activation if active queues >= 4 hours (14.400s):
 *      Building queue >= 4h -> Fiesta Industrial (1201)
 *      Research queue >= 4h -> Fiesta Científica (1202)
 *    - Debiting funds from respective category.
 * 4. Ideologies (ShowIdeologiesPage, 1301..1306):
 *    - Secondary surplus investment from opportunity fund.
 */
class BotCivicStrategist
{
    private $ctx;
    private $gateway;

    public function __construct(BotContext $ctx, BotActionGateway $gateway)
    {
        $this->ctx = $ctx;
        $this->gateway = $gateway;
    }

    /**
     * Coordinate civics management during turn
     *
     * @param BotWorldModel $world
     */
    public function manageCivics(BotWorldModel $world)
    {
        // 1. Assign civics matrix if unset
        $this->assignCivicsIfNeeded();

        // 2. Check and activate 7-day planetary parties if tactical conditions warrant
        $this->checkPlanetaryParties($world);

        // 3. Upgrade ideologies with secondary surpluses
        $this->checkIdeologies($world->empire);
    }

    /**
     * Personality-based Civics assignment with strict immutability check
     *
     * @return bool True if assigned
     */
    public function assignCivicsIfNeeded()
    {
        global $resource, $reslist;

        $userRace = (int)($this->ctx->user['race'] ?? 0);
        $userGov  = (int)($this->ctx->user['formgovernment'] ?? 0);
        $userEth  = (int)($this->ctx->user['ethics'] ?? 0);

        $isRaceSet = ($userRace > 0 && $userRace != 1500);
        $isGovSet  = ($userGov > 0 && $userGov != 1600);
        $isEthSet  = ($userEth > 0 && $userEth != 1700);

        // Immutability detection: if race and government are already set, never alter to prevent reset penalties
        if ($isRaceSet && $isGovSet) {
            return false;
        }

        $persName = strtolower($this->ctx->personality->name ?? 'balanced');
        $botId = (int)($this->ctx->user['id'] ?? $this->ctx->botId);
        $variantIdx = $botId % 4;

        switch ($persName) {
            case 'miner':
            case 'economic':
                $targetRace   = 1501; // Fotorianos
                $targetGov    = 1602; // República
                $targetEthics = 1701; // Bien
                $profileDesc  = 'Económico (Fotorianos + República + Bien)';
                break;

            case 'raider':
            case 'aggressive':
                $targetRace   = 1504; // Kratonianos
                $targetGov    = 1601; // Imperio
                $targetEthics = 1703; // Maldad
                $profileDesc  = 'Militar Ofensivo (Kratonianos + Imperio + Maldad)';
                break;

            case 'bunker':
            case 'turtle':
            case 'defensive':
                $targetRace   = 1502; // Sinuria
                $targetGov    = 1601; // Imperio
                $targetEthics = 1702; // Neutral
                $profileDesc  = 'Fortaleza Defensiva (Sinuria + Imperio + Neutral)';
                break;

            case 'balanced':
            default:
                if ($variantIdx === 0) {
                    $targetRace   = 1504; // Kratonianos
                    $targetGov    = 1601; // Imperio
                    $targetEthics = 1703; // Maldad
                    $profileDesc  = 'Equilibrado / Variante Kratoniana (Kratonianos + Imperio + Maldad)';
                } elseif ($variantIdx === 1) {
                    $targetRace   = 1501; // Fotorianos
                    $targetGov    = 1602; // República
                    $targetEthics = 1701; // Bien
                    $profileDesc  = 'Equilibrado / Variante Fotoriana (Fotorianos + República + Bien)';
                } elseif ($variantIdx === 2) {
                    $targetRace   = 1502; // Sinuria
                    $targetGov    = 1601; // Imperio
                    $targetEthics = 1702; // Neutral
                    $profileDesc  = 'Equilibrado / Variante Sinuria (Sinuria + Imperio + Neutral)';
                } else {
                    $targetRace   = 1503; // Humanos
                    $targetGov    = 1603; // Democracia
                    $targetEthics = 1702; // Neutral
                    $profileDesc  = 'Equilibrado / Variante Humana (Humanos + Democracia + Neutral)';
                }
                break;
        }

        // Preserve already assigned dimensions if partially set
        if ($isRaceSet) {
            $targetRace = $userRace;
        }
        if ($isGovSet) {
            $targetGov = $userGov;
        }
        if ($isEthSet) {
            $targetEthics = $userEth;
        }

        $db = Database::get();
        $botId = (int)$this->ctx->botId;

        // Debit initial Antimatter cost (100 AM per unset component) if user has sufficient funds
        $amCost = 0;
        if (!$isRaceSet) $amCost += 100;
        if (!$isGovSet)  $amCost += 100;
        if (!$isEthSet)  $amCost += 100;

        $userAM = (float)($this->ctx->user['antimatter'] ?? 0.0);
        if ($amCost > 0 && $userAM >= $amCost) {
            PremiumEconomy::debit($this->ctx->user, 922, $amCost, 'race_buy', $targetRace, 'initial_civics_setup');
        }

        // Zero out competing options and set chosen civics
        $setClauses = array(
            'race = :race',
            'formgovernment = :gov',
            'ethics = :ethics',
        );
        $params = array(
            ':race'   => $targetRace,
            ':gov'    => $targetGov,
            ':ethics' => $targetEthics,
            ':uid'    => $botId,
        );

        if (!empty($reslist['race'])) {
            foreach ($reslist['race'] as $rId) {
                if (isset($resource[$rId])) {
                    $setClauses[] = "{$resource[$rId]} = " . ($rId == $targetRace ? 1 : 0);
                    $this->ctx->user[$resource[$rId]] = ($rId == $targetRace ? 1 : 0);
                }
            }
        }
        if (!empty($reslist['formgovernment'])) {
            foreach ($reslist['formgovernment'] as $gId) {
                if (isset($resource[$gId])) {
                    $setClauses[] = "{$resource[$gId]} = " . ($gId == $targetGov ? 1 : 0);
                    $this->ctx->user[$resource[$gId]] = ($gId == $targetGov ? 1 : 0);
                }
            }
        }
        if (!empty($reslist['ethics'])) {
            foreach ($reslist['ethics'] as $eId) {
                if (isset($resource[$eId])) {
                    $setClauses[] = "{$resource[$eId]} = " . ($eId == $targetEthics ? 1 : 0);
                    $this->ctx->user[$resource[$eId]] = ($eId == $targetEthics ? 1 : 0);
                }
            }
        }

        $this->ctx->user['race']           = $targetRace;
        $this->ctx->user['formgovernment'] = $targetGov;
        $this->ctx->user['ethics']         = $targetEthics;

        $sql = "UPDATE %%USERS%% SET " . implode(', ', $setClauses) . " WHERE id = :uid;";
        $db->update($sql, $params);

        $this->ctx->decisionLog->record(
            'civics_assigned',
            "Matriz Cívica Establecida: Asignado perfil {$profileDesc}",
            array(
                'race'           => $targetRace,
                'formgovernment' => $targetGov,
                'ethics'         => $targetEthics,
                'personality'    => $persName,
            ),
            "civics_{$targetRace}_{$targetGov}",
            6000.0
        );

        if (isset($this->ctx->telemetry) && $amCost > 0) {
            $this->ctx->telemetry->logPremiumSpend(
                'Imperio',
                "Asignación de Matriz Cívica Inicial ({$profileDesc})",
                922,
                $amCost,
                (float)($this->ctx->user['antimatter'] ?? 0.0),
                "Establecimiento constitucional de Raza, Gobierno y Ética"
            );
        }

        return true;
    }

    /**
     * Tactical Evaluation and Activation of 7-Day Planetary Parties (ShowPartyPage, 1201..1208)
     * Cost: Strictly 7,000 Antimatter (uni1_vars.cost922 = 7000). Duration: 7 days (604,800s).
     *
     * The 4 Strategic Megabuffs:
     * - 1205 (Militaristas): +50% Attack, +50% Defense, +50% Shield
     *   Trigger: Active siege, ongoing war, or incoming threat exceeding safety threshold.
     * - 1207 (Académicos): +60% Tech Research Speed (bonusStech = 0.60)
     *   Trigger: Heavy tech (Astrofísica, IRN, Hiperespacio, Motores) with duration >= 18 hours.
     * - 1201 (Industriales): +35% Building, +25% Shipyard, +25% Defense Speed
     *   Trigger: Heavy multi-colony queues (>= 4 colonies with >= 8h queues) or combat rebuild.
     * - 1203 (Federalistas): +50% Resource Production (bonusResource = 0.50) + 30% Storage
     *   Trigger: Peaceful expansion and empire hourly production >= 20M MSE/h.
     *
     * Financial Rule: Requires >= 7,200 AM (7,000 AM cost + 200 AM reserve cushion).
     *
     * @param BotWorldModel $world
     * @return bool
     */
    public function checkPlanetaryParties(BotWorldModel $world)
    {
        global $resource, $reslist, $pricelist;

        $now = BotClock::get();

        // 1. Check if any party is currently running
        if (!empty($reslist['party'])) {
            foreach ($reslist['party'] as $pEl) {
                $pCol = $resource[$pEl];
                if (isset($this->ctx->user[$pCol]) && (int)$this->ctx->user[$pCol] > $now) {
                    return false; // Already an active party running
                }
            }
        }

        $userAM = (float)($this->ctx->user['antimatter'] ?? 0.0);
        $partyCost = 7000.0;

        // Must preserve at least 200 AM cushion above party cost
        if ($userAM < ($partyCost + 200.0)) {
            return false;
        }

        $botId = (int)$this->ctx->botId;
        $empire = $world->empire;
        $bestParty = 0;
        $bestScore = 0.0;
        $chosenReason = '';

        // --- Evaluador A: 1205 (Militaristas - +50% Ataque, Escudos, Blindaje) ---
        // Trigger: Ataque entrante grave o estado de asedio activo
        $hasThreat = false;
        if (isset($world->threats) && method_exists($world->threats, 'getActiveThreats')) {
            $threats = $world->threats->getActiveThreats();
            if (!empty($threats)) {
                $hasThreat = true;
            }
        }
        $isSiegeActive = (!empty($this->ctx->botRow['siege_cycles']) && (int)$this->ctx->botRow['siege_cycles'] > 0);

        if ($hasThreat || $isSiegeActive) {
            $scoreMilitar = $hasThreat ? 9500.0 : 9000.0;
            if ($scoreMilitar > $bestScore) {
                $bestScore = $scoreMilitar;
                $bestParty = 1205;
                $chosenReason = $hasThreat ? 'Amenaza militar entrante (Buff defensivo +50%)' : 'Asedio militar activo (Buff ofensivo +50%)';
            }
        }

        // --- Evaluador B: 1207 (Académicos - +60% Velocidad Investigación) ---
        // Trigger: Tecnología pesada activa (>= 18 horas de investigación)
        $techEndTime = (int)($this->ctx->user['b_tech'] ?? 0);
        if ($techEndTime > $now) {
            $remTech = $techEndTime - $now;
            if ($remTech >= 64800) { // >= 18 horas
                $scoreTech = 8500.0 + min(400.0, ($remTech / 86400.0) * 100.0);
                if ($scoreTech > $bestScore) {
                    $bestScore = $scoreTech;
                    $bestParty = 1207;
                    $chosenReason = "Investigación pesada en curso (" . round($remTech / 3600, 1) . "h restantes, +60% aceleración científica)";
                }
            }
        }

        // --- Evaluador C: 1201 (Industriales - +35% Edificios, +25% Astillero/Defensas) ---
        // Trigger: Obras simultáneas pesadas (>= 4 colonias con colas >= 8 horas)
        $heavyBuildColonies = 0;
        $planets = $empire->getPlanets();
        foreach ($planets as $pId => $pEntry) {
            $pData = $pEntry['data'];
            $bEndTime = (int)($pData['b_building'] ?? 0);
            $bQueue = $pEntry['building_queue'] ?? array();
            if (!empty($bQueue) && is_array($bQueue)) {
                $lastItem = end($bQueue);
                if (isset($lastItem[3]) && (int)$lastItem[3] > $bEndTime) {
                    $bEndTime = (int)$lastItem[3];
                }
            }
            if ($bEndTime > $now && ($bEndTime - $now) >= 28800) { // >= 8h
                $heavyBuildColonies++;
            }
        }

        if ($heavyBuildColonies >= 4) {
            $scoreBuild = 8000.0 + min(400.0, ($heavyBuildColonies - 4) * 100.0);
            if ($scoreBuild > $bestScore) {
                $bestScore = $scoreBuild;
                $bestParty = 1201;
                $chosenReason = "Macro-construcción en curso en {$heavyBuildColonies} planetas (+35% obras, +25% hangares)";
            }
        }

        // --- Evaluador D: 1203 (Federalistas - +50% Producción de Recursos) ---
        // Trigger: Economía madura de paz (>= 20M MSE/h producción total)
        $hourlyProd = PremiumEconomy::playerHourlyProduction($botId);
        $hourlyMSE  = $hourlyProd['metal'] + (2.0 * $hourlyProd['crystal']) + (4.0 * $hourlyProd['deuterium']);
        if ($hourlyMSE >= 20000000.0) {
            $scoreFederal = 7500.0 + min(400.0, ($hourlyMSE / 20000000.0) * 50.0);
            if ($scoreFederal > $bestScore) {
                $bestScore = $scoreFederal;
                $bestParty = 1203;
                $chosenReason = "Expansión industrial masiva (" . number_format($hourlyMSE / 1000000, 1) . "M MSE/h, +50% producción global)";
            }
        }

        if ($bestParty <= 0) {
            return false;
        }

        // Debit strictly 7,000 Antimatter
        if (!PremiumEconomy::debit($this->ctx->user, 922, $partyCost, 'party_buy', $bestParty, 'party_7days')) {
            return false;
        }

        $partyCol = $resource[$bestParty];
        $duration = 604800; // 7 days (168 hours)
        $newPartyExpiry = $now + $duration;
        $this->ctx->user[$partyCol] = $newPartyExpiry;

        $db = Database::get();
        $db->update("UPDATE %%USERS%% SET {$partyCol} = :t WHERE id = :uid;", array(
            ':t'   => $newPartyExpiry,
            ':uid' => $botId,
        ));

        $partyNames = array(
            1201 => 'Fiesta de los Industriales (#1201: +35% Obras, +25% Astillero/Def)',
            1203 => 'Fiesta de los Federalistas (#1203: +50% Recursos Imperio, +30% Almacén)',
            1205 => 'Fiesta de los Militaristas (#1205: +50% Ataque, Blindaje y Escudos)',
            1207 => 'Fiesta de los Académicos (#1207: +60% Velocidad de Investigación)',
        );
        $pTitle = $partyNames[$bestParty] ?? "Fiesta Planetaria #{$bestParty}";

        $this->ctx->decisionLog->record(
            'planetary_party',
            "Activada {$pTitle} (Coste: 7,000 AM, Duración: 7 días): {$chosenReason}",
            array(
                'party_id'     => $bestParty,
                'party_cost'   => $partyCost,
                'duration'     => $duration,
                'reason'       => $chosenReason,
                'remaining_am' => (float)$this->ctx->user['antimatter'],
            ),
            "party_{$bestParty}",
            $bestScore
        );

        if (isset($this->ctx->telemetry)) {
            $this->ctx->telemetry->logParty(
                $bestParty,
                $duration,
                $partyCost,
                (float)$this->ctx->user['antimatter'],
                $chosenReason
            );
        }

        return true;
    }

    /**
     * Invest in Ideologies (1301..1306) STRICTLY using legitimate Antimatter (922).
     * Ideologies cost strictly Antimatter. Never free, never using metal/crystal/deuterium!
     *
     * @param BotEmpireState $empire
     * @return int Number of ideologies upgraded
     */
    public function checkIdeologies(BotEmpireState $empire)
    {
        global $resource, $reslist, $pricelist;

        if (empty($reslist['ideologies'])) {
            return 0;
        }

        $botId = (int)$this->ctx->botId;
        $userAM = (float)($this->ctx->user['antimatter'] ?? 0.0);

        // Preserve a mandatory safety reserve of antimatter (200 AM)
        if ($userAM < 300.0) {
            return 0;
        }

        // Smart Budgeting: If bot has between 4,000 AM and 7,200 AM, check if saving for 7-day party or bulk containers
        // If an impending heavy tech or major industrial boom is near, preserve funds rather than spending on cheap levels
        if ($userAM >= 4000.0 && $userAM < 7200.0) {
            $techEndTime = (int)($this->ctx->user['b_tech'] ?? 0);
            $hasLongTech = ($techEndTime > BotClock::get() && ($techEndTime - BotClock::get()) >= 64800);
            if ($hasLongTech) {
                return 0; // Preserve AM to hit 7,200 AM threshold for Party #1207 (Académicos)
            }
        }

        $db = Database::get();

        // 2-hour strategic pacing cooldown between ideology purchases
        $recentUpgrade = $db->selectSingle("SELECT created_at FROM %%BOT_DECISIONS%% WHERE bot_id = :bid AND decision_type = 'ideology_upgrade' ORDER BY id DESC LIMIT 1;", array(':bid' => $botId));
        if (!empty($recentUpgrade['created_at']) && ((class_exists('BotClock') ? BotClock::now() : TIMESTAMP) - (int)$recentUpgrade['created_at']) < 7200) {
            return 0;
        }

        // Personality-tailored ideology priority
        // 1301: Liberalismo (+2% Velocidad)
        // 1302: Anarquismo (+2% Ataque, Escudo, Defensa)
        // 1303: Comunismo (+3% Recursos)
        // 1304: Socialdemocracia (+1% Velocidad)
        // 1305: Fascismo (+1% Obras y Ciencia)
        // 1306: Conservadurismo (+1% Flota y Defensa)
        $pers = strtolower($this->ctx->personality->name ?? 'balanced');
        if ($pers === 'raider' || $pers === 'aggressive') {
            $priority = array(1302, 1301, 1306, 1305, 1303, 1304);
        } elseif ($pers === 'miner' || $pers === 'economic') {
            $priority = array(1303, 1305, 1306, 1301, 1302, 1304);
        } elseif ($pers === 'bunker' || $pers === 'turtle' || $pers === 'defensive') {
            $priority = array(1302, 1303, 1306, 1305, 1301, 1304);
        } else {
            $priority = array(1303, 1302, 1305, 1301, 1306, 1304);
        }

        // Find minimum level among available priority ideologies to balance doctrinal progression across tiers
        $minLvl = 255;
        foreach ($priority as $pId) {
            if (in_array($pId, $reslist['ideologies'])) {
                $cName = $resource[$pId];
                $lvl   = (int)($this->ctx->user[$cName] ?? 0);
                if ($lvl < $minLvl) {
                    $minLvl = $lvl;
                }
            }
        }

        $upgraded = 0;

        foreach ($priority as $ideoId) {
            if (!in_array($ideoId, $reslist['ideologies'])) {
                continue;
            }
            $colName = $resource[$ideoId];
            $curLvl  = (int)($this->ctx->user[$colName] ?? 0);
            $maxLvl  = (int)($pricelist[$ideoId]['max'] ?? 255);

            if ($curLvl >= $maxLvl || $curLvl > $minLvl) {
                continue;
            }

            // Determine legitimate Antimatter cost
            $costResources = BuildFunctions::getElementPrice($this->ctx->user, array(), $ideoId);
            $costAM = !empty($costResources[922]) ? (float)$costResources[922] : (float)($pricelist[$ideoId]['cost'][922] ?? 100.0);

            if ($costAM <= 0.0) {
                $costAM = 100.0; // Strict floor: ideologies NEVER upgrade for 0 or free
            }

            // Only purchase if the bot has enough Antimatter above the safety reserve (200 AM)
            if ($userAM >= ($costAM + 200.0)) {
                $debited = PremiumEconomy::debit($this->ctx->user, 922, $costAM, 'ideology_buy', $ideoId, 'civics');
                if ($debited) {
                    $userAM = (float)$this->ctx->user['antimatter'];

                    $this->ctx->user[$colName] = $curLvl + 1;
                    $db->update("UPDATE %%USERS%% SET {$colName} = {$colName} + 1 WHERE id = :uid;", array(
                        ':uid' => $botId,
                    ));

                    $this->ctx->decisionLog->record(
                        'ideology_upgrade',
                        "Ideología Adquirida: Ascendida #{$ideoId} a nivel " . ($curLvl + 1) . " con Antimateria legítima (Coste: " . number_format($costAM, 0) . " AM)",
                        array(
                            'ideology_id' => $ideoId,
                            'new_level'   => $curLvl + 1,
                            'cost_am'     => $costAM,
                            'remaining_am'=> $userAM,
                        ),
                        "ideology_{$ideoId}",
                        2000.0
                    );

                    if (isset($this->ctx->telemetry)) {
                        $ideoName = BotTelemetryLogger::getElementName($ideoId);
                        $this->ctx->telemetry->logPremiumSpend(
                            'Imperio',
                            "Adoctrinamiento Ideológico: {$ideoName} a Nivel " . ($curLvl + 1),
                            922,
                            $costAM,
                            $userAM,
                            "Alineación doctrinal ({$pers}) para bonificación cívica permanente"
                        );
                    }

                    $upgraded++;
                    break; // 1 level upgrade per cycle to pace acquisition
                }
            }
        }

        return $upgraded;
    }
}

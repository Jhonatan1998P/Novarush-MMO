<?php

/**
 * NovaRush Bot AI v2 - Intel Policy
 *
 * Coordinates reconnaissance missions (Mission 6: Espionage)
 * when intelligence is missing or stale.
 */
class BotIntelPolicy
{
    private $ctx;
    private $gateway;

    public function __construct(BotContext $ctx, BotActionGateway $gateway)
    {
        $this->ctx = $ctx;
        $this->gateway = $gateway;
    }

    /**
     * Conduct espionage on target candidates
     *
     * @param BotWorldModel $world
     * @param array $basePlanet
     * @param array $targetCandidates
     * @param int $maxProbesToSend
     * @return int Number of spy probes launched
     */
    public function scoutTargets(BotWorldModel $world, array $basePlanet, array $targetCandidates, $maxProbesToSend = 5)
    {
        global $resource;

        $bpData = isset($basePlanet['data']) ? $basePlanet['data'] : $basePlanet;
        $allEmpirePlanets = ($world !== null && isset($world->empire)) ? $world->empire->getAllBodies() : array();
        if (empty($allEmpirePlanets)) {
            $allEmpirePlanets = array($basePlanet['id'] => $basePlanet);
        }

        // Helper to locate best origin with probes for a specific target (minimizing flight distance)
        // Helper to locate best origin with probes for a specific target (minimizing flight distance)
        $findOriginWithProbes = function($targetGalaxy, $targetSystem, $minProbes = 30) use (&$allEmpirePlanets, $basePlanet, $resource) {
            $bestBodyId = null;
            $bestProbes = 0;
            $minDist = PHP_INT_MAX;
            $bpId = (int)$basePlanet['id'];

            // First pass: find closest planet that has at least $minProbes
            foreach ($allEmpirePlanets as $bId => $bBody) {
                $bData = isset($bBody['data']) ? $bBody['data'] : $bBody;
                $pCount = isset($bData[$resource[210]]) ? (int)$bData[$resource[210]] : 0;
                if ($pCount < $minProbes) continue;

                $bG = (int)$bData['galaxy'];
                $bS = (int)$bData['system'];
                $dist = ($bG == $targetGalaxy) ? abs($bS - $targetSystem) : (10000 + abs($bG - $targetGalaxy) * 1000 + abs($bS - $targetSystem));

                if ($dist < $minDist || ($dist === $minDist && (int)$bId === $bpId)) {
                    $minDist = $dist;
                    $bestBodyId = (int)$bId;
                    $bestProbes = $pCount;
                }
            }

            // Fallback pass: if no planet has $minProbes, find closest planet with any probes (> 0)
            if ($bestBodyId === null) {
                $minDist = PHP_INT_MAX;
                foreach ($allEmpirePlanets as $bId => $bBody) {
                    $bData = isset($bBody['data']) ? $bBody['data'] : $bBody;
                    $pCount = isset($bData[$resource[210]]) ? (int)$bData[$resource[210]] : 0;
                    if ($pCount <= 0) continue;

                    $bG = (int)$bData['galaxy'];
                    $bS = (int)$bData['system'];
                    $dist = ($bG == $targetGalaxy) ? abs($bS - $targetSystem) : (10000 + abs($bG - $targetGalaxy) * 1000 + abs($bS - $targetSystem));

                    if ($dist < $minDist || ($dist === $minDist && (int)$bId === $bpId)) {
                        $minDist = $dist;
                        $bestBodyId = (int)$bId;
                        $bestProbes = $pCount;
                    }
                }
            }

            if ($bestBodyId !== null) {
                return array('id' => $bestBodyId, 'available' => $bestProbes);
            }

            return null;
        };

        // Check if bot has any probes anywhere in empire
        $totalEmpireProbes = 0;
        foreach ($allEmpirePlanets as $bBody) {
            $bData = isset($bBody['data']) ? $bBody['data'] : $bBody;
            $totalEmpireProbes += isset($bData[$resource[210]]) ? (int)$bData[$resource[210]] : 0;
        }
        if ($totalEmpireProbes <= 0) {
            return 0;
        }

        $db = Database::get();

        // In-flight spy mission map to avoid redundant launches
        $activeSpyFleets = $db->select("SELECT fleet_end_galaxy, fleet_end_system, fleet_end_planet 
            FROM %%FLEETS%% 
            WHERE fleet_owner = :botId AND fleet_mission = 6 AND fleet_mess = 0;", array(
            ':botId' => $this->ctx->botId
        ));
        $activeSpyMap = array();
        if (is_array($activeSpyFleets)) {
            foreach ($activeSpyFleets as $asf) {
                $activeSpyMap[(int)$asf['fleet_end_galaxy'] . ':' . (int)$asf['fleet_end_system'] . ':' . (int)$asf['fleet_end_planet']] = true;
            }
        }

        $scouted = 0;
        $halfLife = $this->ctx->difficulty->intelMaxAgeHours;

        // 1. PRIORITARY RE-SCOUTING: Check if bot has an active locked target
        $lockedGalaxy = isset($this->ctx->botRow['target_galaxy']) ? (int)$this->ctx->botRow['target_galaxy'] : 0;
        $lockedSystem = isset($this->ctx->botRow['target_system']) ? (int)$this->ctx->botRow['target_system'] : 0;
        $lockedPlanet = isset($this->ctx->botRow['target_planet']) ? (int)$this->ctx->botRow['target_planet'] : 0;
        $lockedType   = isset($this->ctx->botRow['target_type']) ? $this->ctx->botRow['target_type'] : '';

        if ($lockedGalaxy > 0 && $lockedSystem > 0 && $lockedPlanet > 0) {
            // Check fresh intel on locked target (freshness threshold: 0.15 hours = 9 minutes)
            $lockedIntel = $world->intel->getIntel($lockedGalaxy, $lockedSystem, $lockedPlanet, 1, 0.15);
            $needScouting = ($lockedIntel === null || $lockedIntel['confidence'] < 0.75 || empty($lockedIntel['defense_scouted']));

            if ($needScouting) {
                // Fetch planet details from database
                $db = Database::get();
                $targetRow = $db->selectSingle("SELECT id, id_owner FROM %%PLANETS%% WHERE galaxy = :g AND system = :s AND planet = :p AND planet_type = 1 LIMIT 1;", array(
                    ':g' => $lockedGalaxy,
                    ':s' => $lockedSystem,
                    ':p' => $lockedPlanet
                ));

                if (!empty($targetRow) && $targetRow['id_owner'] != $this->ctx->botId && (int)$targetRow['id_owner'] !== 1) {
                    $targetUser = $db->selectSingle("SELECT authlevel FROM %%USERS%% WHERE id = :uid;", array(':uid' => (int)$targetRow['id_owner']));
                    if (!empty($targetUser) && (int)$targetUser['authlevel'] > 0) {
                        return 0;
                    }

                    // NovaRush Anti-Bashing: Do not scout blacklisted planets
                    require_once 'includes/classes/bot/Military/TargetBlacklist.class.php';
                    if (BotTargetBlacklist::isBlacklisted($this->ctx->botId, (int)$targetRow['id_owner'], (int)$targetRow['id'], $lockedGalaxy, $lockedSystem, $lockedPlanet)) {
                        return 0;
                    }

                    $targetKey = "{$lockedGalaxy}:{$lockedSystem}:{$lockedPlanet}";
                    if (!isset($activeSpyMap[$targetKey])) {
                        $origin = $findOriginWithProbes($lockedGalaxy, $lockedSystem, 30);
                        if (!empty($origin)) {
                            // Tactical probe scaling: Always send at least 30 spy probes as mandated
                            $probeCount = min($origin['available'], max(30, (int)round($this->ctx->personality->aggressionLevel * 30)));

                            if ($probeCount > 0) {
                                $fleet = array(210 => $probeCount);
                                $ok = $this->gateway->launchFleet(
                                    $fleet,
                                    6, // Mission 6: Espionage
                                    $origin['id'],
                                    (int)$targetRow['id'],
                                    (int)$targetRow['id_owner'],
                                    $lockedGalaxy,
                                    $lockedSystem,
                                    $lockedPlanet,
                                    1,
                                    array(),
                                    10
                                );

                                if ($ok) {
                                    $scouted++;
                                    $activeSpyMap[$targetKey] = true;
                                    if (isset($allEmpirePlanets[$origin['id']]['data'][$resource[210]])) {
                                        $allEmpirePlanets[$origin['id']]['data'][$resource[210]] -= $probeCount;
                                    }

                                    $this->ctx->decisionLog->record(
                                        'reconnaissance_locked_target',
                                        "Re-espionage launched from colony #{$origin['id']} to locked {$lockedType} target [{$lockedGalaxy}:{$lockedSystem}:{$lockedPlanet}] with {$probeCount} probes" . (empty($lockedIntel['defense_scouted']) ? " (Penetration Wave)" : ""),
                                        array('galaxy' => $lockedGalaxy, 'system' => $lockedSystem, 'planet' => $lockedPlanet, 'type' => $lockedType, 'probes' => $probeCount, 'origin' => $origin['id']),
                                        "spy_locked_{$lockedGalaxy}_{$lockedSystem}_{$lockedPlanet}",
                                        3000.0
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }

        // 1.5 PRE-IMPACT RE-ESPIONAGE: Targets with inbound attack armadas (Section 2.5)
        $inboundAttacks = $db->select("SELECT fleet_id, fleet_end_id, fleet_end_galaxy, fleet_end_system, fleet_end_planet, fleet_start_time, fleet_target_owner 
            FROM %%FLEETS%% 
            WHERE fleet_owner = :botId AND fleet_mission = 1 AND fleet_mess = 0;", array(
            ':botId' => $this->ctx->botId
        ));

        if (is_array($inboundAttacks)) {
            foreach ($inboundAttacks as $inAtt) {
                $remTime = (int)$inAtt['fleet_start_time'] - TIMESTAMP;
                if ($remTime >= 30 && $remTime <= 360) {
                    $tgG = (int)$inAtt['fleet_end_galaxy'];
                    $tgS = (int)$inAtt['fleet_end_system'];
                    $tgP = (int)$inAtt['fleet_end_planet'];

                    $targetKey = "{$tgG}:{$tgS}:{$tgP}";
                    if (!isset($activeSpyMap[$targetKey])) {
                        $preIntel = $world->intel->getIntel($tgG, $tgS, $tgP, 1, 0.08); // 5 min freshness
                        if ($preIntel === null || empty($preIntel['defense_scouted'])) {
                            $origin = $findOriginWithProbes($tgG, $tgS, 30);
                            if (!empty($origin) && $origin['available'] > 0) {
                                $pCount = min($origin['available'], max(30, (int)round($this->ctx->personality->aggressionLevel * 30)));
                                $fArray = array(210 => $pCount);
                                $ok = $this->gateway->launchFleet(
                                    $fArray,
                                    6, // Mission 6: Espionage
                                    $origin['id'],
                                    (int)$inAtt['fleet_end_id'],
                                    (int)$inAtt['fleet_target_owner'],
                                    $tgG,
                                    $tgS,
                                    $tgP,
                                    1,
                                    array(),
                                    10
                                );
                                if ($ok) {
                                    $scouted++;
                                    $activeSpyMap[$targetKey] = true;
                                    if (isset($allEmpirePlanets[$origin['id']]['data'][$resource[210]])) {
                                        $allEmpirePlanets[$origin['id']]['data'][$resource[210]] -= $pCount;
                                    }
                                    $this->ctx->decisionLog->record(
                                        'pre_impact_reconnaissance',
                                        "Pre-impact tactical re-espionage dispatched from colony #{$origin['id']} to [{$tgG}:{$tgS}:{$tgP}] ({$pCount} probes, T-minus {$remTime}s) to confirm target status.",
                                        array('target' => "{$tgG}:{$tgS}:{$tgP}", 'probes' => $pCount, 'rem_time' => $remTime, 'origin' => $origin['id']),
                                        "spy_preimpact_{$tgG}_{$tgS}_{$tgP}",
                                        4000.0
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }

        if (!is_array($targetCandidates) || $scouted >= $maxProbesToSend) {
            return $scouted;
        }

        // 2. CANDIDATE RECONNAISSANCE: Scout unexplored or stale targets
        foreach ($targetCandidates as $entry) {
            $t = $entry['target'];

            // Do not spy on admin accounts
            if ((int)$t['owner_id'] === 1) {
                continue;
            }

            // Skip if this is the locked target (already handled)
            if ($t['galaxy'] == $lockedGalaxy && $t['system'] == $lockedSystem && $t['planet'] == $lockedPlanet) {
                continue;
            }

            // NovaRush Anti-Bashing: Do not scout blacklisted planets/players
            $tId = isset($t['planet_id']) ? (int)$t['planet_id'] : (int)($t['id'] ?? 0);
            $ownerId = (int)$t['owner_id'];
            if (BotTargetBlacklist::isBlacklisted($this->ctx->botId, $ownerId, $tId, (int)$t['galaxy'], (int)$t['system'], (int)$t['planet'])) {
                continue;
            }

            $targetKey = "{$t['galaxy']}:{$t['system']}:{$t['planet']}";
            if (isset($activeSpyMap[$targetKey])) {
                // A spy fleet is already flying to this destination
                continue;
            }

            // Check if we already have fresh intel
            $intel = $world->intel->getIntel($t['galaxy'], $t['system'], $t['planet'], 1, $halfLife);
            if ($intel !== null && $intel['confidence'] > 0.6 && !empty($intel['defense_scouted'])) {
                // Intel is still sufficiently fresh and defenses are known
                continue;
            }

            $origin = $findOriginWithProbes((int)$t['galaxy'], (int)$t['system'], 30);
            if (empty($origin) || $origin['available'] <= 0) {
                break;
            }

            // Probes per mission: Always at least 30 spy probes as mandated
            $isHeavyRecon = isset($entry['category']) && $entry['category'] === 'recon_heavy';
            if ($isHeavyRecon) {
                $probeCount = min($origin['available'], max(30, (int)round($this->ctx->personality->aggressionLevel * 35)));
            } else {
                $probeCount = min($origin['available'], max(30, (int)round($this->ctx->personality->aggressionLevel * 30)));
            }
            if ($probeCount <= 0) break;

            $fleet = array(210 => $probeCount);

            $ok = $this->gateway->launchFleet(
                $fleet,
                6, // Mission 6: Espionage
                $origin['id'],
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
                $scouted++;
                $activeSpyMap[$targetKey] = true;
                if (isset($allEmpirePlanets[$origin['id']]['data'][$resource[210]])) {
                    $allEmpirePlanets[$origin['id']]['data'][$resource[210]] -= $probeCount;
                }

                $this->ctx->decisionLog->record(
                    'reconnaissance',
                    "Dispatched {$probeCount} spy probes from colony #{$origin['id']} to {$t['galaxy']}:{$t['system']}:{$t['planet']}",
                    array('target' => $t, 'probe_count' => $probeCount, 'origin' => $origin['id']),
                    "spy_{$t['galaxy']}_{$t['system']}_{$t['planet']}",
                    $entry['score']
                );
            }

            if ($scouted >= $maxProbesToSend) {
                break;
            }
        }

        return $scouted;
    }
}

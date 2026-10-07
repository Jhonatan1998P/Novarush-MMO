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

        $pData = $basePlanet['data'];
        $probesAvailable = isset($pData[$resource[210]]) ? (int)$pData[$resource[210]] : 0;
        if ($probesAvailable <= 0) {
            return 0;
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
                    // Tactical probe scaling: if defenses were not scouted, send tactical penetration batch (12-16 probes)
                    if (empty($lockedIntel['defense_scouted'])) {
                        $probeCount = min(16, max(8, (int)round($this->ctx->personality->aggressionLevel * 12)), $probesAvailable);
                    } else {
                        $probeCount = min(max(3, (int)round($this->ctx->personality->aggressionLevel * 4)), $probesAvailable);
                    }

                    if ($probeCount > 0) {
                        $fleet = array(210 => $probeCount);
                        $ok = $this->gateway->launchFleet(
                            $fleet,
                            6, // Mission 6: Espionage
                            $basePlanet['id'],
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
                            $probesAvailable -= $probeCount;

                            $this->ctx->decisionLog->record(
                                'reconnaissance_locked_target',
                                "Re-espionage launched to locked {$lockedType} target [{$lockedGalaxy}:{$lockedSystem}:{$lockedPlanet}] with {$probeCount} probes" . (empty($lockedIntel['defense_scouted']) ? " (Penetration Wave)" : ""),
                                array('galaxy' => $lockedGalaxy, 'system' => $lockedSystem, 'planet' => $lockedPlanet, 'type' => $lockedType, 'probes' => $probeCount),
                                "spy_locked_{$lockedGalaxy}_{$lockedSystem}_{$lockedPlanet}",
                                3000.0
                            );
                        }
                    }
                }
            }
        }

        // 1.5 PRE-IMPACT RE-ESPIONAGE: Targets with inbound attack armadas (Section 2.5)
        $db = Database::get();
        $inboundAttacks = $db->select("SELECT fleet_id, fleet_end_id, fleet_end_galaxy, fleet_end_system, fleet_end_planet, fleet_start_time, fleet_target_owner 
            FROM %%FLEETS%% 
            WHERE fleet_owner = :botId AND fleet_mission = 1 AND fleet_mess = 0;", array(
            ':botId' => $this->ctx->botId
        ));

        if (is_array($inboundAttacks)) {
            foreach ($inboundAttacks as $inAtt) {
                if ($probesAvailable <= 0) break;
                $remTime = (int)$inAtt['fleet_start_time'] - TIMESTAMP;
                if ($remTime >= 30 && $remTime <= 360) {
                    $tgG = (int)$inAtt['fleet_end_galaxy'];
                    $tgS = (int)$inAtt['fleet_end_system'];
                    $tgP = (int)$inAtt['fleet_end_planet'];

                    $preIntel = $world->intel->getIntel($tgG, $tgS, $tgP, 1, 0.08); // 5 min freshness
                    if ($preIntel === null || empty($preIntel['defense_scouted'])) {
                        $pCount = min(8, $probesAvailable);
                        $fArray = array(210 => $pCount);
                        $ok = $this->gateway->launchFleet(
                            $fArray,
                            6, // Mission 6: Espionage
                            $basePlanet['id'],
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
                            $probesAvailable -= $pCount;
                            $this->ctx->decisionLog->record(
                                'pre_impact_reconnaissance',
                                "Pre-impact tactical re-espionage dispatched to [{$tgG}:{$tgS}:{$tgP}] ({$pCount} probes, T-minus {$remTime}s) to confirm target status.",
                                array('target' => "{$tgG}:{$tgS}:{$tgP}", 'probes' => $pCount, 'rem_time' => $remTime),
                                "spy_preimpact_{$tgG}_{$tgS}_{$tgP}",
                                4000.0
                            );
                        }
                    }
                }
            }
        }

        if (!is_array($targetCandidates) || $scouted >= $maxProbesToSend || $probesAvailable <= 0) {
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

            // Check if we already have fresh intel
            $intel = $world->intel->getIntel($t['galaxy'], $t['system'], $t['planet'], 1, $halfLife);
            if ($intel !== null && $intel['confidence'] > 0.6 && !empty($intel['defense_scouted'])) {
                // Intel is still sufficiently fresh and defenses are known
                continue;
            }

            // Probes per mission: scale up to 16 for recon_heavy targets with high loot behind Fog of War
            $isHeavyRecon = isset($entry['category']) && $entry['category'] === 'recon_heavy';
            if ($isHeavyRecon) {
                $probeCount = min(16, max(8, (int)round($this->ctx->personality->aggressionLevel * 12)), $probesAvailable);
            } else {
                $probeCount = min(max(2, (int)round($this->ctx->personality->aggressionLevel * 3)), $probesAvailable);
            }
            if ($probeCount <= 0) break;

            $fleet = array(210 => $probeCount);

            $ok = $this->gateway->launchFleet(
                $fleet,
                6, // Mission 6: Espionage
                $basePlanet['id'],
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
                $probesAvailable -= $probeCount;

                $this->ctx->decisionLog->record(
                    'reconnaissance',
                    "Dispatched {$probeCount} spy probes to {$t['galaxy']}:{$t['system']}:{$t['planet']}",
                    array('target' => $t, 'probe_count' => $probeCount),
                    "spy_{$t['galaxy']}_{$t['system']}_{$t['planet']}",
                    $entry['score']
                );
            }

            if ($scouted >= $maxProbesToSend || $probesAvailable <= 0) {
                break;
            }
        }

        return $scouted;
    }
}

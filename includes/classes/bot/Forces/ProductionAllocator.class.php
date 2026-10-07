<?php

require_once 'includes/classes/bot/Evaluation/EconomyValuator.class.php';

/**
 * NovaRush Bot AI v2 - Production Allocator
 *
 * Allocates infrastructure investments (Mines, Power, Nanite, Labs) across
 * all colonies based on Hours of Production (HP) payback period.
 */
class BotProductionAllocator
{
    private $ctx;
    private $gateway;

    public function __construct(BotContext $ctx, BotActionGateway $gateway)
    {
        $this->ctx = $ctx;
        $this->gateway = $gateway;
    }

    /**
     * Run an economic investment cycle across the empire
     *
     * @param BotEmpireState $empire
     * @return int Number of building constructions queued
     */
    public function allocate(BotEmpireState $empire)
    {
        global $resource, $pricelist;

        $queued = 0;
        $candidates = array();
        $unaffordableGoals = array();
        $config = Config::get();

        $macro = (isset($this->ctx->difficulty) && method_exists($this->ctx->difficulty, 'getAdjustedBudget'))
            ? $this->ctx->difficulty->getAdjustedBudget($this->ctx->personality)
            : array('fleet' => 0.35, 'defense' => 0.15, 'mines' => 0.30, 'research' => 0.20);
        $macroMines = max(0.05, $macro['mines']);

        $planets = $empire->getPlanets();
        if (!is_array($planets) || empty($planets)) {
            return 0;
        }

        foreach ($planets as $pId => $planetEntry) {
            $pData = $planetEntry['data'];
            
            // Check if queue is already full
            if (!empty($planetEntry['building_queue']) && count($planetEntry['building_queue']) >= 2) {
                continue;
            }

            $energy = (float)$pData['energy'];
            $energyUsed = abs((float)$pData['energy_used']);
            $desiredEnergyBuffer = max(300.0, $energyUsed * 0.20);
            $freeFields = CalculateMaxPlanetFields($pData) - ((int)$pData['field_current'] + count($planetEntry['building_queue']));

            // --- 1. Proactive Energy Scaling & Graviton Satellites Push ---
            // If energy is below safety buffer, or if pushing for Graviton (199) to unlock NovaRush modules
            if (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 212, array())) {
                $hangarQueue = !empty($planetEntry['hangar_queue']) ? $planetEntry['hangar_queue'] : array();
                if (count($hangarQueue) < 2) {
                    $energyPerSat = max(1.0, (($pData['temp_max'] + 160) / 6.0));
                    $satsToQueue = 0;

                    if ($energy < $desiredEnergyBuffer) {
                        // Restore positive energy buffer so mines produce at 100% capacity
                        $energyDeficit = $desiredEnergyBuffer - $energy;
                        $satsToQueue = min(150, (int)ceil($energyDeficit / $energyPerSat));
                    } elseif (!empty($pData[$resource[31]]) && (int)$pData[$resource[31]] >= 10 && empty($this->ctx->user[$resource[199]])) {
                        // Graviton Push: Planet has high lab, push toward 300,000 energy to unlock Graviton & Modules (81-84)
                        $neededForGraviton = max(0.0, 300000.0 - $energy);
                        if ($neededForGraviton > 0) {
                            $satsToQueue = min(400, (int)ceil($neededForGraviton / $energyPerSat));
                        }
                    }

                    if ($satsToQueue > 0) {
                        $maxCanSats = BuildFunctions::getMaxConstructibleElements($this->ctx->user, $pData, 212);
                        $batchSats = min($satsToQueue, $maxCanSats);
                        if ($batchSats > 0) {
                            $this->gateway->constructUnits($pId, 212, $batchSats);
                        }
                    }
                }
            }

            // Comprehensive NovaRush Buildings:
            // 1: Metal Mine, 2: Crystal Mine, 3: Deuterium Synthesizer
            // 81: Resource Module (huge 300M/200C/100D production, no energy cost)
            // 82: Defensive Module, 83: Military Module, 84: Research Module
            // 4: Solar Plant, 12: Fusion Plant
            // 14: Robotics Factory, 15: Nanite Factory, 21: Shipyard, 31: Research Lab
            // 33: Terraformer (expands planet fields)
            // 44: Missile Silo (stores missiles)
            // 22: Metal Store, 23: Crystal Store, 24: Deuterium Store
            $buildIds = array(1, 2, 3, 81, 82, 83, 84, 4, 12, 14, 15, 21, 31, 33, 44, 22, 23, 24);

            // If planet fields are completely exhausted (<= 0), only Terraformer (33) can be queued
            if ($freeFields <= 0) {
                $buildIds = array(33);
            }

            foreach ($buildIds as $mId) {
                if (!BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, $mId, array())) {
                    continue;
                }

                $cost = BuildFunctions::getElementPrice($this->ctx->user, $pData, $mId, false);
                $canAfford = BuildFunctions::isElementBuyable($this->ctx->user, $pData, $mId, $cost);

                $costMSE = BotEconomyValuator::arrayToMSE($cost);
                $costHP  = BotEconomyValuator::toHP($costMSE, $empire->getHourlyProductionMSE());
                
                $curLevel = isset($pData[$resource[$mId]]) ? (int)$pData[$resource[$mId]] : 0;
                $nextLevel = $curLevel + 1;

                // Baseline priority score scaled by Difficulty & Personality Mine budget
                $baseScore = ($macroMines * 40.0) / max(0.1, $costHP);

                // --- 1. Terraformer (33) field expansion logic (Phase 5: <= 1: 15000, <= 3: 8000, > 5: 500) ---
                if ($mId == 33) {
                    if ($freeFields <= 1) {
                        $baseScore = 15000.0; // Critical: planet about to deadlock completely
                    } elseif ($freeFields <= 3) {
                        $baseScore = 8000.0;
                    } elseif ($freeFields > 5) {
                        $baseScore = 500.0; // Plenty of fields left
                    } else {
                        $baseScore = 2000.0;
                    }
                }

                // If fields are running low (<= 4) and Terraformer is blocked, rush its prerequisites: Nanite (15) and Robotics (14)
                if ($freeFields <= 4 && !BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 33, array())) {
                    if ($mId == 14 && $curLevel < 10) {
                        $baseScore += 4000.0;
                    } elseif ($mId == 15 && $curLevel < 1) {
                        $baseScore += 6000.0;
                    }
                }

                // --- 2. Energy Scaling (Phase 5: 5000 + |Déficit|*5 if < 0; 1500 if < buffer; 100 if surplus) ---
                if ($mId == 4 || $mId == 12) {
                    if ($energy < 0) {
                        $baseScore = 5000.0 + (abs($energy) * 5.0);
                    } elseif ($energy < $desiredEnergyBuffer) {
                        $baseScore = 1500.0 + (($desiredEnergyBuffer - $energy) * 5.0);
                    } else {
                        $baseScore = 100.0; // Energy surplus
                    }
                }

                // --- 3. Production ROI calculation for 1, 2, 3, and 81 (Resource Module) ---
                if (in_array($mId, array(1, 2, 3, 81))) {
                    $resMult = (float)$config->resource_multiplier;
                    $temp = (float)$pData['temp_max'];
                    $userFactor = isset($this->ctx->user['factor']) ? $this->ctx->user['factor'] : array();
                    $fRes = isset($userFactor['Resource']) ? (float)$userFactor['Resource'] : 0.0;
                    $fMet = isset($userFactor['Pmetal']) ? (float)$userFactor['Pmetal'] : 0.0;
                    $fCry = isset($userFactor['Pcrystal']) ? (float)$userFactor['Pcrystal'] : 0.0;
                    $fDeu = isset($userFactor['Pdeuterium']) ? (float)$userFactor['Pdeuterium'] : 0.0;

                    $deltaM = 0.0;
                    $deltaC = 0.0;
                    $deltaD = 0.0;

                    if ($mId == 1) { // Metal Mine
                        $baseCur  = 30.0 * $curLevel * pow(1.1, $curLevel);
                        $baseNext = 30.0 * $nextLevel * pow(1.1, $nextLevel);
                        $deltaM   = ($baseNext - $baseCur) * (1.0 + $fRes + $fMet) * $resMult;
                    } elseif ($mId == 2) { // Crystal Mine
                        $baseCur  = 20.0 * $curLevel * pow(1.1, $curLevel);
                        $baseNext = 20.0 * $nextLevel * pow(1.1, $nextLevel);
                        $deltaC   = ($baseNext - $baseCur) * (1.0 + $fRes + $fCry) * $resMult;
                    } elseif ($mId == 3) { // Deuterium Synthesizer
                        $tempFactor = (-0.002 * $temp + 1.28);
                        $baseCur    = 10.0 * $curLevel * pow(1.1, $curLevel) * $tempFactor;
                        $baseNext   = 10.0 * $nextLevel * pow(1.1, $nextLevel) * $tempFactor;
                        $deltaD     = ($baseNext - $baseCur) * (1.0 + $fRes + $fDeu) * $resMult;
                    } elseif ($mId == 81) { // NovaRush Resource Module: 300 M / 200 C / 100 D base, zero energy!
                        $tempFactor = (-0.002 * $temp + 1.28);
                        $deltaM     = (300.0 * $nextLevel * pow(1.1, $nextLevel) - 300.0 * $curLevel * pow(1.1, $curLevel)) * (1.0 + $fRes + $fMet) * $resMult;
                        $deltaC     = (200.0 * $nextLevel * pow(1.1, $nextLevel) - 200.0 * $curLevel * pow(1.1, $curLevel)) * (1.0 + $fRes + $fCry) * $resMult;
                        $deltaD     = (100.0 * $nextLevel * pow(1.1, $nextLevel) - 100.0 * $curLevel * pow(1.1, $curLevel)) * $tempFactor * (1.0 + $fRes + $fDeu) * $resMult;
                    }

                    $deltaMSE = BotEconomyValuator::toMSE($deltaM, $deltaC, $deltaD);
                    $roi = ($deltaMSE / max(1.0, $costMSE)) * 10000.0 * ($macroMines / 0.35);
                    $baseScore += $roi;

                    // Extra boost for Resource Module (81) due to huge production and zero energy footprint
                    if ($mId == 81) {
                        $baseScore += 1200.0 * ($macroMines / 0.35);
                    }
                }

                // --- 4. NovaRush Special Modules (82, 83, 84) ---
                if ($mId == 82) { // Defensive Module
                    $baseScore += 600.0 * ($macro['defense'] / 0.15);
                } elseif ($mId == 83) { // Military Module
                    $baseScore += 700.0 * ($macro['fleet'] / 0.30);
                } elseif ($mId == 84) { // Research Module
                    $baseScore += 800.0 * ($macro['research'] / 0.20);
                }

                // --- 5. Missile Silo (44) ---
                if ($mId == 44) {
                    if ($curLevel < 4) {
                        $baseScore += 1200.0; // Reach level 4 to unlock interplanetary missiles
                    } else {
                        $baseScore += 300.0;
                    }
                }

                // --- 6. Core Infrastructure (14 Robotics, 15 Nanite, 21 Shipyard, 31 Lab) ---
                if ($mId == 14 && $curLevel < 10) {
                    $baseScore += (10 - $curLevel) * 50.0;
                } elseif ($mId == 15 && $curLevel < 5) {
                    $baseScore += 1500.0; // Nanite factory is massive speed multiplier
                } elseif ($mId == 21 && $curLevel < 12) {
                    $baseScore += (12 - $curLevel) * 50.0;
                } elseif ($mId == 31 && $curLevel < 12) {
                    $baseScore += (12 - $curLevel) * 50.0;
                }

                // --- 7. Storage Capacity Check (22, 23, 24) ---
                if ($mId == 22 && isset($pData['metal_max']) && $pData['metal'] > (0.8 * $pData['metal_max'])) {
                    $baseScore += 1500.0;
                } elseif ($mId == 23 && isset($pData['crystal_max']) && $pData['crystal'] > (0.8 * $pData['crystal_max'])) {
                    $baseScore += 1500.0;
                } elseif ($mId == 24 && isset($pData['deuterium_max']) && $pData['deuterium'] > (0.8 * $pData['deuterium_max'])) {
                    $baseScore += 1500.0;
                }

                // Phase 5 Archetype multiplier for mines and infrastructure
                $archMineMult = $this->ctx->personality->getCategoryMultiplier('mines');
                $finalScore = $baseScore * $archMineMult;

                if ($canAfford) {
                    $candidates[] = array(
                        'planet_id' => $pId,
                        'element'   => $mId,
                        'score'     => round($finalScore, 2),
                        'cost_hp'   => round($costHP, 2),
                    );
                } else {
                    $unaffordableGoals[] = array(
                        'planet_id' => $pId,
                        'element'   => $mId,
                        'score'     => round($finalScore, 2),
                        'cost'      => $cost,
                        'data'      => $pData,
                    );
                }
            }
        }

        if (!empty($candidates)) {
            // Sort candidates by score descending
            usort($candidates, function($a, $b) {
                return $b['score'] <=> $a['score'];
            });

            // Enqueue highest viable candidate
            foreach ($candidates as $cand) {
                $ok = $this->gateway->constructBuilding($cand['planet_id'], $cand['element']);
                if ($ok) {
                    $queued++;
                    $this->ctx->decisionLog->record(
                        'mine_upgrade',
                        "Upgraded {$resource[$cand['element']]} on planet #{$cand['planet_id']}",
                        array_slice($candidates, 0, 5),
                        "build_{$cand['element']}_planet_{$cand['planet_id']}",
                        $cand['score']
                    );
                    break;
                }
            }
        }

        // Phase 3: If no construction was queued and budget manager has no active savings lock, engage savings lock for top priority goal
        if ($queued == 0 && isset($this->ctx->budgetManager) && empty($this->ctx->budgetManager->getSavingsLock()) && !empty($unaffordableGoals)) {
            usort($unaffordableGoals, function($a, $b) {
                return $b['score'] <=> $a['score'];
            });
            $topGoal = reset($unaffordableGoals);
            if ($topGoal['score'] > 0.0) {
                $this->ctx->budgetManager->setSavingsLock(
                    $topGoal['planet_id'],
                    'building',
                    $topGoal['element'],
                    $topGoal['cost'],
                    $topGoal['data']
                );
                $this->ctx->decisionLog->record(
                    'savings_lock_engaged',
                    "Engaged savings lock for {$resource[$topGoal['element']]} on planet #{$topGoal['planet_id']}",
                    array('element' => $topGoal['element'], 'cost' => $topGoal['cost']),
                    "save_{$topGoal['element']}",
                    $topGoal['score']
                );
            }
        }

        return $queued;
    }

    /**
     * Allocate technology research when lab is idle
     *
     * @param BotEmpireState $empire
     * @return int Number of researches queued (0 or 1)
     */
    public function allocateResearch(BotEmpireState $empire)
    {
        global $resource, $pricelist;

        // Verify research lab is not busy
        if (!empty($this->ctx->user['b_tech_planet']) || !empty($this->ctx->user['b_tech'])) {
            return 0;
        }

        $macro = (isset($this->ctx->difficulty) && method_exists($this->ctx->difficulty, 'getAdjustedBudget'))
            ? $this->ctx->difficulty->getAdjustedBudget($this->ctx->personality)
            : array('fleet' => 0.35, 'defense' => 0.15, 'mines' => 0.30, 'research' => 0.20);
        $macroTech = max(0.05, $macro['research']);

        $candidates = array();
        $unaffordableTechGoals = array();

        // Comprehensive NovaRush Technologies:
        // 131: Metal Processing Tech (+2% global metal)
        // 132: Crystal Processing Tech (+2% global crystal)
        // 133: Deuterium Processing Tech (+2% global deuterium)
        // 134: Fuel Optimization Tech (reduced flight consumption)
        // 113: Energy Tech (+10% energy boost)
        // 117: Impulse Motor Tech (missile range & mid-tier drives)
        // 106: Spy Tech (fog of war / intel)
        // 108: Computer Tech (fleet slots)
        // 109: Weapons Tech, 110: Shielding Tech, 111: Armor Tech
        // 115: Combustion Drive, 118: Hyperspace Drive
        // 120: Laser Tech, 121: Ion Tech, 122: Plasma / Buster Tech
        // 124: Astrophysics / Expedition Tech
        // 199: Graviton Tech
        $techWeights = array(
            131 => 4.5, // Metal Processing Tech (High ROI)
            132 => 4.5, // Crystal Processing Tech (High ROI)
            133 => 4.2, // Deuterium Processing Tech (High ROI)
            113 => 3.8, // Energy Tech (Power & unlocks)
            117 => 3.5, // Impulse Drive (Missile range!)
            106 => 3.2, // Espionage Tech
            108 => 3.2, // Computer Tech
            109 => 2.8, // Weapons Tech
            110 => 2.8, // Shielding Tech
            111 => 2.8, // Armor Tech
            118 => 3.0, // Hyperspace Drive
            122 => 2.9, // Plasma Tech
            121 => 2.4, // Ion Tech
            120 => 2.2, // Laser Tech
            124 => 3.6, // Astrophysics (Extra planets)
            134 => 2.7, // Fuel Optimization
            115 => 2.2, // Combustion Drive
            199 => 2.0, // Graviton Tech
        );

        $empireHourlyMSE = $empire->getHourlyProductionMSE();

        $planets = $empire->getPlanets();
        if (!is_array($planets) || empty($planets)) {
            return 0;
        }

        // Minimum free fields across the empire to gauge Terraformer urgency
        $minFreeFieldsEmpire = 999;
        foreach ($planets as $pCheck) {
            $f = CalculateMaxPlanetFields($pCheck['data']) - ((int)$pCheck['data']['field_current'] + count($pCheck['building_queue']));
            if ($f < $minFreeFieldsEmpire) {
                $minFreeFieldsEmpire = $f;
            }
        }

        foreach ($planets as $pId => $planetEntry) {
            $pData = $planetEntry['data'];
            // Planet must have a research lab (ID 31)
            if (empty($pData[$resource[31]]) || $pData[$resource[31]] <= 0) {
                continue;
            }

            foreach ($techWeights as $techId => $baseWeight) {
                if (!BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, $techId, array())) {
                    continue;
                }

                $cost = BuildFunctions::getElementPrice($this->ctx->user, $pData, $techId, false);
                $canAffordTech = BuildFunctions::isElementBuyable($this->ctx->user, $pData, $techId, $cost);

                $costMSE = BotEconomyValuator::arrayToMSE($cost);
                $costHP  = BotEconomyValuator::toHP($costMSE, $empireHourlyMSE);
                $diffTechFactor = ($macroTech / 0.20);
                $score   = ($baseWeight * $diffTechFactor * 20.0) / max(0.1, $costHP);

                // Military tech scaling for Hard/Nightmare difficulty
                if (in_array($techId, array(109, 110, 111, 117, 118, 122))) {
                    if (isset($this->ctx->difficulty->name) && $this->ctx->difficulty->name === BotDifficultyProfile::NIGHTMARE) {
                        $score *= 1.8;
                    } elseif (isset($this->ctx->difficulty->name) && $this->ctx->difficulty->name === BotDifficultyProfile::HARD) {
                        $score *= 1.4;
                    }
                }

                // --- A. Graviton Tech (199) Highest Priority ---
                // When 300,000 energy requirement is met, research immediately to unlock NovaRush Modules (81-84) & Titans
                if ($techId == 199) {
                    $score += 25000.0;
                }

                // --- B. Compounding ROI for NovaRush processing techs (131, 132, 133): +2% empire-wide output per level ---
                if (in_array($techId, array(131, 132, 133))) {
                    $gainMSE = $empireHourlyMSE * 0.02;
                    $score += ($gainMSE / max(1.0, $costMSE)) * 12000.0;
                }

                // --- C. Energy Tech (113) Scaling & Terraformer Prerequisite ---
                $curEnergyTech = isset($this->ctx->user[$resource[113]]) ? (int)$this->ctx->user[$resource[113]] : 0;
                if ($techId == 113) {
                    if ($pData['energy'] < 500) {
                        $score += 1500.0;
                    }
                    // If planet fields are filling up and Terraformer needs Energy Tech 12
                    if ($minFreeFieldsEmpire <= 5 && $curEnergyTech < 12) {
                        $score += 5000.0 + (12 - $curEnergyTech) * 500.0;
                    }
                }

                // --- D. Impulse Motor Tech (117) for Tactical Missile Strike Range ---
                $curImpulse = isset($this->ctx->user[$resource[117]]) ? (int)$this->ctx->user[$resource[117]] : 0;
                if ($techId == 117 && $curImpulse < 6) {
                    $score += 1200.0 + (6 - $curImpulse) * 200.0;
                }

                // --- E. Computer Tech (108) for Fleet Slots and Nanite Factory requirement ---
                $curComp = isset($this->ctx->user[$resource[108]]) ? (int)$this->ctx->user[$resource[108]] : 0;
                if ($techId == 108 && $curComp < 10) {
                    $score += 1000.0;
                }

                $archTechMult = $this->ctx->personality->getCategoryMultiplier('research');
                $finalScore = $score * $archTechMult;

                if ($canAffordTech) {
                    $candidates[] = array(
                        'planet_id' => $pId,
                        'tech_id'   => $techId,
                        'score'     => round($finalScore, 2),
                    );
                } else {
                    $unaffordableTechGoals[] = array(
                        'planet_id' => $pId,
                        'tech_id'   => $techId,
                        'score'     => round($finalScore, 2),
                        'cost'      => $cost,
                        'data'      => $pData,
                    );
                }
            }
        }

        if (!empty($candidates)) {
            usort($candidates, function($a, $b) {
                return $b['score'] <=> $a['score'];
            });

            foreach ($candidates as $cand) {
                $ok = $this->gateway->researchTech($cand['planet_id'], $cand['tech_id']);
                if ($ok) {
                    $this->ctx->decisionLog->record(
                        'research_enqueued',
                        "Queued research {$resource[$cand['tech_id']]} on planet #{$cand['planet_id']}",
                        array_slice($candidates, 0, 5),
                        "research_{$cand['tech_id']}",
                        $cand['score']
                    );
                    return 1;
                }
            }
        }

        // Phase 3: If no research was queued and budget manager has no active savings lock, engage savings lock for top tech goal
        if (isset($this->ctx->budgetManager) && empty($this->ctx->budgetManager->getSavingsLock()) && !empty($unaffordableTechGoals)) {
            usort($unaffordableTechGoals, function($a, $b) {
                return $b['score'] <=> $a['score'];
            });
            $topTech = reset($unaffordableTechGoals);
            if ($topTech['score'] > 0.0) {
                $this->ctx->budgetManager->setSavingsLock(
                    $topTech['planet_id'],
                    'research',
                    $topTech['tech_id'],
                    $topTech['cost'],
                    $topTech['data']
                );
                $this->ctx->decisionLog->record(
                    'savings_lock_engaged',
                    "Engaged savings lock for research {$resource[$topTech['tech_id']]} on planet #{$topTech['planet_id']}",
                    array('element' => $topTech['tech_id'], 'cost' => $topTech['cost'], 'target_type' => 'research'),
                    "save_research_{$topTech['tech_id']}",
                    $topTech['score']
                );
            }
        }

        return 0;
    }
}

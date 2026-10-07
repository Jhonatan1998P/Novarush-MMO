<?php

require_once 'includes/classes/bot/Evaluation/EconomyValuator.class.php';

/**
 * NovaRush Bot AI v2 - Force Planner
 *
 * Military force composition and dynamic progression planner for NovaRush.
 * Evaluates over 50 fleet units (202-267) and 60 defense units (401-460) in uni1_vars
 * using CombatCaps (attack, shield, defend, weapon class, armor class).
 *
 * Tactical Combined-Arms Doctrine:
 * 1. Dynamic Personality Budget:
 *    - Fleet, Defense, and Missiles scaled by BotPersonality (aggressive, defensive, balanced, economic).
 * 2. Strict Screening Fodder Quota (Target: 40% - 50%):
 *    - If Fodder >= 50% of combat fleet: PAUSE fodder recruitment completely and divert 100% of fodder budget to attack warships.
 *    - If Fodder < 50%: Split 60% Light Hunter (204) / 40% Heavy Hunter (205).
 * 3. Tri-Role Combined-Arms Strike Fleet:
 *    - Role 1 (Anti-Fodder Escort): Cruisers (206) - 25% warship budget
 *    - Role 2 (Main Battle Capital): Battleships (207) + Battlecruisers (215) - 45% warship budget
 *    - Role 3 (Heavy Capital / Siege): Destructors (213) / Bombers (211) / Galeons (225) - 30% warship budget
 *    - Real affordability filter: Never selects unbuildable phantom titans. Unspent budget cascades dynamically.
 * 4. Multi-Tier Layered Defense:
 *    - Shield Domes (407, 408) as absolute priority if missing.
 *    - Balanced defense recruitment between heavy turrets (Plasma/Dora/Gauss) and anti-shield (Ion/Laser).
 * 5. Silo & Anti-Missiles:
 *    - 60% Interceptors (502) / 40% Interplanetary Missiles (503).
 */
class BotForcePlanner
{
    private $ctx;
    private $gateway;

    const ROLE_ESCORT  = 'escort';   // Anti-fodder rapidfire screen killers (206, 263, 224, 245)
    const ROLE_CAPITAL = 'capital';  // Mainline battle line (207, 215, 234, 255, 235)
    const ROLE_HEAVY   = 'heavy';    // Heavy siege & capital anchors (213, 211, 225, 226, 214, 216, 246)

    public function __construct(BotContext $ctx, BotActionGateway $gateway)
    {
        $this->ctx = $ctx;
        $this->gateway = $gateway;
    }

    /**
     * Helper to safely get metal cost from $pricelist
     */
    public function getCostMetal($unitId)
    {
        global $pricelist;
        if (isset($pricelist[$unitId]['cost'][901])) {
            return max(1.0, (float)$pricelist[$unitId]['cost'][901]);
        }
        return 1000.0;
    }

    /**
     * Gets or deterministically/randomly assigns the bot's permanent specialization doctrine.
     * Heavy category: Galeón (225), Luna Negra (216), Estrella de la Muerte (214)
     * Medium category: Destructor (213), Bombardero (211), Acorazado (215)
     * Light category: Crucero (206), Nave de Batalla (207)
     * Window interval: 10 to 30 min (600 to 1800 seconds)
     */
    public function getOrAssignFleetDoctrine()
    {
        $heavy    = isset($this->ctx->botRow['doctrine_heavy']) ? (int)$this->ctx->botRow['doctrine_heavy'] : 0;
        $medium   = isset($this->ctx->botRow['doctrine_medium']) ? (int)$this->ctx->botRow['doctrine_medium'] : 0;
        $light    = isset($this->ctx->botRow['doctrine_light']) ? (int)$this->ctx->botRow['doctrine_light'] : 0;
        $interval = isset($this->ctx->botRow['recruit_interval']) ? (int)$this->ctx->botRow['recruit_interval'] : 0;

        $heavyOptions  = array(225, 216, 214); // Galeon, Luna Negra, Estrella de la Muerte
        $mediumOptions = array(213, 211, 215); // Destructor, Bombardero, Acorazado
        $lightOptions  = array(206, 207);      // Crucero, Nave de Batalla

        $updates = array();

        if (!in_array($heavy, $heavyOptions)) {
            $heavy = $heavyOptions[array_rand($heavyOptions)];
            $updates['doctrine_heavy'] = $heavy;
        }

        if (!in_array($medium, $mediumOptions)) {
            $medium = $mediumOptions[array_rand($mediumOptions)];
            $updates['doctrine_medium'] = $medium;
        }

        if (!in_array($light, $lightOptions)) {
            $light = $lightOptions[array_rand($lightOptions)];
            $updates['doctrine_light'] = $light;
        }

        if ($interval < 600 || $interval > 1800) {
            $pers = $this->ctx->personality->name;
            if ($pers === BotPersonality::AGGRESSIVE || $pers === 'raider') {
                $interval = mt_rand(600, 900); // 10 a 15 min
            } elseif ($pers === BotPersonality::DEFENSIVE || $pers === BotPersonality::ECONOMIC || $pers === 'turtle') {
                $interval = mt_rand(1200, 1800); // 20 a 30 min
            } else {
                $interval = mt_rand(900, 1200); // 15 a 20 min (balanced)
            }
            $updates['recruit_interval'] = $interval;
        }

        if (!empty($updates)) {
            $this->ctx->updateBotRow($updates);
        }

        return array(
            'heavy'    => $heavy,
            'medium'   => $medium,
            'light'    => $light,
            'interval' => $interval
        );
    }

    private function refreshPlanetData($pId, array $pData)
    {
        if (method_exists($this->gateway, 'getFreshPlanet')) {
            return $this->gateway->getFreshPlanet($pId);
        }
        $fresh = Database::get()->selectSingle("SELECT * FROM %%PLANETS%% WHERE id = :pId;", array(':pId' => $pId));
        return !empty($fresh) ? $fresh : $pData;
    }

    /**
     * Plan and queue military recruitment across the empire
     *
     * @param BotEmpireState $empire
     * @param bool $forceRun Bypass timing check (useful for manual runs / tests)
     * @return array [unitId => count recruited]
     */
    public function planForces(BotEmpireState $empire, $forceRun = false)
    {
        global $resource, $pricelist, $reslist, $CombatCaps;

        $doctrine = $this->getOrAssignFleetDoctrine();

        $lastRecruit = isset($this->ctx->botRow['last_recruit']) ? (int)$this->ctx->botRow['last_recruit'] : 0;
        $timeSinceRecruit = TIMESTAMP - $lastRecruit;

        $isSiegeActive = (!empty($this->ctx->botRow['target_type']) && $this->ctx->botRow['target_type'] === 'siege');

        // Ventana de ahorro: si aún no ha transcurrido el intervalo (10 a 30 min), la IA acumula recursos
        // EXCEPCIÓN VITAL: Durante asedios activos, la IA NO duerme; produce misiles y refuerzos en cada ciclo
        if (!$forceRun && !$isSiegeActive && $lastRecruit > 0 && $timeSinceRecruit < $doctrine['interval']) {
            return array();
        }

        $recruited = array();
        $allBodies = $empire->getAllBodies();
        if (!is_array($allBodies)) {
            return $recruited;
        }

        foreach ($allBodies as $pId => $body) {
            $pData = $body['data'];

            $queuedJobs = !empty($body['hangar_queue']) ? count($body['hangar_queue']) : 0;
            $maxQueueJobs = 5;

            // Skip if hangar queue has 5 or more jobs active
            if ($queuedJobs >= $maxQueueJobs) {
                continue;
            }

            // --- 1. Energy Stabilization: Solar Satellites (212) ---
            $energy = (float)$pData['energy'];
            if ($energy < 0 && $queuedJobs < $maxQueueJobs && BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 212, array())) {
                $energyPerSat = max(1.0, (($pData['temp_max'] + 160) / 6.0));
                $satsNeeded   = min(50, (int)ceil(abs($energy) / $energyPerSat));
                $maxSats      = BuildFunctions::getMaxConstructibleElements($this->ctx->user, $pData, 212);
                $batchSats    = min($satsNeeded, $maxSats);

                if ($batchSats > 0 && $this->gateway->constructUnits($pId, 212, $batchSats)) {
                    $recruited[212] = isset($recruited[212]) ? $recruited[212] + $batchSats : $batchSats;
                    $queuedJobs++;
                    $this->ctx->decisionLog->record(
                        'solar_satellite_build',
                        "Built {$batchSats}x Solar Satellites on body #{$pId} to resolve energy deficit ({$energy})",
                        array('deficit' => $energy, 'batch' => $batchSats),
                        "sat_{$pId}",
                        1000.0
                    );
                        $pData = $this->refreshPlanetData($pId, $pData);
                }
            }

            // --- 2. Calculate Dynamic Production Budget by Difficulty & Personality Window (10-30 min) ---
            $metalPerHour  = isset($pData['metal_perhour']) ? (float)$pData['metal_perhour'] : 0.0;
            $intervalHours = max(0.166, (float)$doctrine['interval'] / 3600.0);

            // Difficulty & Personality Macro Budget
            $macro = (isset($this->ctx->difficulty) && method_exists($this->ctx->difficulty, 'getAdjustedBudget'))
                ? $this->ctx->difficulty->getAdjustedBudget($this->ctx->personality)
                : array('fleet' => 0.35, 'defense' => 0.15, 'mines' => 0.30, 'research' => 0.20);

            $militaryShare = max(0.05, $macro['fleet'] + $macro['defense']);
            $cycleBudget   = max(250000.0, ($metalPerHour * $intervalHours) * $militaryShare);
            $liquidMetal   = (float)$pData['metal'];
            $actualBudget  = max($cycleBudget, min($liquidMetal * 0.75, $cycleBudget * 1.5));

            $ratioFleet    = $macro['fleet'] / $militaryShare;
            $ratioDef      = $macro['defense'] / $militaryShare;

            $totalDefAlloc = $actualBudget * $ratioDef;
            $mislBudget    = $totalDefAlloc * ($isSiegeActive ? 0.75 : 0.25);
            $defBudget     = $totalDefAlloc - $mislBudget;
            $fleetBudget   = $actualBudget * $ratioFleet;

            // --- 3. Missile & Silo Management ---
            $siloLevel = isset($pData[$resource[44]]) ? (int)$pData[$resource[44]] : 0;
            if ($siloLevel >= 2 && $queuedJobs < $maxQueueJobs) {
                $configSiloFactor = max(Config::get()->silo_factor, 1);
                $maxSpaces = $siloLevel * 10 * $configSiloFactor;

                $cur502 = isset($pData[$resource[502]]) ? (int)$pData[$resource[502]] : 0;
                $cur503 = isset($pData[$resource[503]]) ? (int)$pData[$resource[503]] : 0;
                $usedSpaces = ($cur502 * 1) + ($cur503 * 2);
                $freeSpaces = max(0, $maxSpaces - $usedSpaces);

                // Quota: prioritize MIPs (503) aggressively during sieges
                $mipRatio = $isSiegeActive ? 0.85 : 0.45;
                $targetSpaces503 = (int)floor($maxSpaces * $mipRatio);
                $targetCount503  = (int)floor($targetSpaces503 / 2);
                $targetSpaces502 = $maxSpaces - $targetSpaces503;
                $targetCount502  = $targetSpaces502;

                $impulse = isset($this->ctx->user[$resource[117]]) ? (int)$this->ctx->user[$resource[117]] : 0;

                // Priority: During sieges, build offensive MIPs (503) FIRST before defensive ABMs
                if ($isSiegeActive) {
                    // A. Interplanetary Missiles (503) Priority
                    if ($queuedJobs < $maxQueueJobs && $siloLevel >= 4 && $impulse >= 1 && $cur503 < $targetCount503 && BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 503, array())) {
                        $needed503    = $targetCount503 - $cur503;
                        $costMetal503 = $this->getCostMetal(503);
                        $budget503    = floor(($mislBudget * 0.85) / $costMetal503);
                        $maxCan503    = BuildFunctions::getMaxConstructibleElements($this->ctx->user, $pData, 503);
                        $maxBySpace   = (int)floor($freeSpaces / 2);
                        $batch503     = min($needed503, min($budget503, min($maxCan503, $maxBySpace)));

                        if ($batch503 > 0 && $this->gateway->constructUnits($pId, 503, $batch503)) {
                            $recruited[503] = isset($recruited[503]) ? $recruited[503] + $batch503 : $batch503;
                            $queuedJobs++;
                            $freeSpaces -= ($batch503 * 2);
                            $this->ctx->decisionLog->record(
                                'mip_missile_build',
                                "Recruited {$batch503}x Interplanetary Missiles (503) on body #{$pId}",
                                array('batch' => $batch503),
                                "mip_{$pId}",
                                900.0
                            );
                            $pData = $this->refreshPlanetData($pId, $pData);
                        }
                    }

                    // B. Interceptor Missiles (502) Secondary
                    if ($queuedJobs < $maxQueueJobs && $cur502 < $targetCount502 && $freeSpaces >= 1 && BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 502, array())) {
                        $needed502    = $targetCount502 - $cur502;
                        $costMetal502 = $this->getCostMetal(502);
                        $budget502    = floor(($mislBudget * 0.15) / $costMetal502);
                        $maxCan502    = BuildFunctions::getMaxConstructibleElements($this->ctx->user, $pData, 502);
                        $batch502     = min($needed502, min($budget502, min($maxCan502, $freeSpaces)));

                        if ($batch502 > 0 && $this->gateway->constructUnits($pId, 502, $batch502)) {
                            $recruited[502] = isset($recruited[502]) ? $recruited[502] + $batch502 : $batch502;
                            $queuedJobs++;
                            $freeSpaces -= $batch502;
                            $this->ctx->decisionLog->record(
                                'antimissile_build',
                                "Recruited {$batch502}x Interceptor Missiles (502) on body #{$pId}",
                                array('batch' => $batch502),
                                "interceptor_{$pId}",
                                1200.0
                            );
                            $pData = $this->refreshPlanetData($pId, $pData);
                        }
                    }
                } else {
                    // Standard Peace/Defensive Stance: Interceptors First
                    if ($cur502 < $targetCount502 && BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 502, array())) {
                        $needed502    = $targetCount502 - $cur502;
                        $costMetal502 = $this->getCostMetal(502);
                        $budget502    = floor(($mislBudget * 0.60) / $costMetal502);
                        $maxCan502    = BuildFunctions::getMaxConstructibleElements($this->ctx->user, $pData, 502);
                        $maxBySpace   = $freeSpaces;
                        $batch502     = min($needed502, min($budget502, min($maxCan502, $maxBySpace)));

                        if ($batch502 > 0 && $this->gateway->constructUnits($pId, 502, $batch502)) {
                            $recruited[502] = isset($recruited[502]) ? $recruited[502] + $batch502 : $batch502;
                            $queuedJobs++;
                            $freeSpaces -= $batch502;
                            $this->ctx->decisionLog->record(
                                'antimissile_build',
                                "Recruited {$batch502}x Interceptor Missiles (502) on body #{$pId}",
                                array('batch' => $batch502),
                                "interceptor_{$pId}",
                                1200.0
                            );
                            $pData = $this->refreshPlanetData($pId, $pData);
                        }
                    }

                    if ($queuedJobs < $maxQueueJobs && $siloLevel >= 4 && $impulse >= 1 && $cur503 < $targetCount503 && BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 503, array())) {
                        $needed503    = $targetCount503 - $cur503;
                        $costMetal503 = $this->getCostMetal(503);
                        $budget503    = floor(($mislBudget * 0.40) / $costMetal503);
                        $maxCan503    = BuildFunctions::getMaxConstructibleElements($this->ctx->user, $pData, 503);
                        $maxBySpace   = (int)floor($freeSpaces / 2);
                        $batch503     = min($needed503, min($budget503, min($maxCan503, $maxBySpace)));

                        if ($batch503 > 0 && $this->gateway->constructUnits($pId, 503, $batch503)) {
                            $recruited[503] = isset($recruited[503]) ? $recruited[503] + $batch503 : $batch503;
                            $queuedJobs++;
                            $freeSpaces -= ($batch503 * 2);
                            $this->ctx->decisionLog->record(
                                'mip_missile_build',
                                "Recruited {$batch503}x Interplanetary Missiles (503) on body #{$pId}",
                                array('batch' => $batch503),
                                "mip_{$pId}",
                                900.0
                            );
                            $pData = $this->refreshPlanetData($pId, $pData);
                        }
                    }
                }
            }

            // --- 4. Planetary Defense Management ---
            if ($queuedJobs < $maxQueueJobs) {
                // Priority: Shield Domes if missing
                if (!empty($reslist['domes'])) {
                    foreach ($reslist['domes'] as $domeId) {
                        if ($queuedJobs >= $maxQueueJobs) break;
                        if (!BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, $domeId, array())) continue;
                        $curDome = isset($pData[$resource[$domeId]]) ? (int)$pData[$resource[$domeId]] : 0;
                        if ($curDome == 0 && BuildFunctions::getMaxConstructibleElements($this->ctx->user, $pData, $domeId) > 0) {
                            if ($this->gateway->constructUnits($pId, $domeId, 1)) {
                                $recruited[$domeId] = isset($recruited[$domeId]) ? $recruited[$domeId] + 1 : 1;
                                $queuedJobs++;
                                $pData = $this->refreshPlanetData($pId, $pData);
                            }
                        }
                    }
                }

                // Top Quality Defense Turrets (Accessible & Affordable within Budget)
                if ($queuedJobs < $maxQueueJobs) {
                    $topDefenses = $this->evaluateDefenseUnits($pData);
                    if (!empty($topDefenses)) {
                        $chosenDef = null;
                        foreach ($topDefenses as $defCand) {
                            $defId     = $defCand['unit_id'];
                            $costMetal = $this->getCostMetal($defId);
                            $canBuild  = floor($defBudget / $costMetal);
                            $maxAfford = BuildFunctions::getMaxConstructibleElements($this->ctx->user, $pData, $defId);
                            if ($canBuild >= 1 && $maxAfford >= 1) {
                                $chosenDef = $defCand;
                                break;
                            }
                        }

                        // Fallback if none fit full budget: pick best turret with at least 1 affordable
                        if ($chosenDef === null) {
                            foreach ($topDefenses as $defCand) {
                                $defId = $defCand['unit_id'];
                                $maxAfford = BuildFunctions::getMaxConstructibleElements($this->ctx->user, $pData, $defId);
                                if ($maxAfford >= 1) {
                                    $chosenDef = $defCand;
                                    break;
                                }
                            }
                        }

                        if ($chosenDef !== null) {
                            $defId     = $chosenDef['unit_id'];
                            $costMetal = $this->getCostMetal($defId);
                            $canBuild  = max(1, floor($defBudget / $costMetal));
                            $maxAfford = BuildFunctions::getMaxConstructibleElements($this->ctx->user, $pData, $defId);
                            $batchDef  = min($canBuild, $maxAfford);

                            if ($batchDef > 0 && $this->gateway->constructUnits($pId, $defId, $batchDef)) {
                                $recruited[$defId] = isset($recruited[$defId]) ? $recruited[$defId] + $batchDef : $batchDef;
                                $queuedJobs++;
                                $this->ctx->decisionLog->record(
                                    'force_build_defense',
                                    "Recruited {$batchDef}x {$resource[$defId]} on body #{$pId} (Quality Score: {$chosenDef['score']})",
                                    array('unit' => $defId, 'batch' => $batchDef, 'score' => $chosenDef['score']),
                                    "recruit_def_{$defId}_{$pId}",
                                    $chosenDef['score']
                                );
                                $pData = $this->refreshPlanetData($pId, $pData);
                            }
                        }
                    }
                }
            }

            // --- 5. Fleet Planning (Combined-Arms Doctrine with Fodder Ratio Control) ---
            if ($queuedJobs < $maxQueueJobs) {
                // Calculate current combat fleet on this planet
                $curFodder = (isset($pData[$resource[204]]) ? (int)$pData[$resource[204]] : 0) +
                             (isset($pData[$resource[205]]) ? (int)$pData[$resource[205]] : 0);

                $curWarships = (isset($pData[$resource[206]]) ? (int)$pData[$resource[206]] : 0) +
                               (isset($pData[$resource[207]]) ? (int)$pData[$resource[207]] : 0) +
                               (isset($pData[$resource[215]]) ? (int)$pData[$resource[215]] : 0) +
                               (isset($pData[$resource[211]]) ? (int)$pData[$resource[211]] : 0) +
                               (isset($pData[$resource[213]]) ? (int)$pData[$resource[213]] : 0) +
                               (isset($pData[$resource[214]]) ? (int)$pData[$resource[214]] : 0) +
                               (isset($pData[$resource[216]]) ? (int)$pData[$resource[216]] : 0) +
                               (isset($pData[$resource[225]]) ? (int)$pData[$resource[225]] : 0) +
                               (isset($pData[$resource[226]]) ? (int)$pData[$resource[226]] : 0);

                $totalCombat = $curFodder + $curWarships;
                $fodderRatio = $totalCombat > 0 ? ($curFodder / $totalCombat) : 0.0;

                // Fodder Quota Circuit Breaker:
                // Target: 40% - 50% fodder. If >= 50%, pause fodder completely and divert 100% to attack warships!
                $fodderAllowed = ($fodderRatio < 0.50);
                $fodderBudget  = $fodderAllowed ? ($fleetBudget * 0.20) : 0.0;
                $attackBudget  = $fleetBudget * ($fodderAllowed ? 0.70 : 0.90);
                $supportBudget = $fleetBudget * 0.10;

                // A. FODDER RECRUITMENT (Screening Fleet)
                if ($fodderAllowed && $queuedJobs < $maxQueueJobs && $fodderBudget > 0) {
                    $hasLight = BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 204, array());
                    $hasHeavy = BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 205, array());

                    $fodderBatchPlan = array();
                    if ($hasLight && $hasHeavy) {
                        $fodderBatchPlan[] = array('id' => 204, 'share' => 0.60);
                        $fodderBatchPlan[] = array('id' => 205, 'share' => 0.40);
                    } elseif ($hasLight) {
                        $fodderBatchPlan[] = array('id' => 204, 'share' => 1.00);
                    } elseif ($hasHeavy) {
                        $fodderBatchPlan[] = array('id' => 205, 'share' => 1.00);
                    }

                    foreach ($fodderBatchPlan as $fPlan) {
                        if ($queuedJobs >= $maxQueueJobs) break;
                        $fId = $fPlan['id'];
                        $fCostMetal = $this->getCostMetal($fId);
                        $canFodder  = floor(($fodderBudget * $fPlan['share']) / $fCostMetal);
                        $maxAfford  = BuildFunctions::getMaxConstructibleElements($this->ctx->user, $pData, $fId);
                        $batchFodder = min($canFodder, $maxAfford);

                        if ($batchFodder > 0 && $this->gateway->constructUnits($pId, $fId, $batchFodder)) {
                            $recruited[$fId] = isset($recruited[$fId]) ? $recruited[$fId] + $batchFodder : $batchFodder;
                            $queuedJobs++;
                            $this->ctx->decisionLog->record(
                                'force_build_fodder',
                                "Recruited {$batchFodder}x {$resource[$fId]} on body #{$pId} (Screening Fodder)",
                                array('unit' => $fId, 'batch' => $batchFodder),
                                "recruit_fodder_{$fId}_{$pId}",
                                500.0
                            );
                            $pData = $this->refreshPlanetData($pId, $pData);
                        }
                    }
                }

                // B. SUPPORT FLEET (Recyclers & Transporters)
                if ($queuedJobs < $maxQueueJobs) {
                    // Recyclers (Target: at least 80 on planet)
                    $curRec = (isset($pData[$resource[209]]) ? (int)$pData[$resource[209]] : 0) +
                              (isset($pData[$resource[219]]) ? (int)$pData[$resource[219]] : 0);
                    if ($curRec < 80) {
                        $recId = 0;
                        if (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 219, array())) {
                            $recId = 219;
                        } elseif (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 209, array())) {
                            $recId = 209;
                        }

                        if ($recId > 0) {
                            $costRec   = $this->getCostMetal($recId);
                            $canRec    = floor(($supportBudget * 0.50) / $costRec);
                            $maxAfford = BuildFunctions::getMaxConstructibleElements($this->ctx->user, $pData, $recId);
                            $batchRec  = min($canRec, $maxAfford);

                            if ($batchRec > 0 && $this->gateway->constructUnits($pId, $recId, $batchRec)) {
                                $recruited[$recId] = isset($recruited[$recId]) ? $recruited[$recId] + $batchRec : $batchRec;
                                $queuedJobs++;
                                $pData = $this->refreshPlanetData($pId, $pData);
                            }
                        }
                    }

                    // Transporters (Target: at least 120 on planet)
                    $curTrans = (isset($pData[$resource[202]]) ? (int)$pData[$resource[202]] : 0) +
                                (isset($pData[$resource[203]]) ? (int)$pData[$resource[203]] : 0) +
                                (isset($pData[$resource[217]]) ? (int)$pData[$resource[217]] : 0);
                    if ($curTrans < 120 && $queuedJobs < $maxQueueJobs) {
                        $transId = 0;
                        if (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 217, array())) {
                            $transId = 217;
                        } elseif (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 203, array())) {
                            $transId = 203;
                        } elseif (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 202, array())) {
                            $transId = 202;
                        }

                        if ($transId > 0) {
                            $costTrans = $this->getCostMetal($transId);
                            $canTrans  = floor(($supportBudget * 0.50) / $costTrans);
                            $maxAfford = BuildFunctions::getMaxConstructibleElements($this->ctx->user, $pData, $transId);
                            $batchTrans = min($canTrans, $maxAfford);

                            if ($batchTrans > 0 && $this->gateway->constructUnits($pId, $transId, $batchTrans)) {
                                $recruited[$transId] = isset($recruited[$transId]) ? $recruited[$transId] + $batchTrans : $batchTrans;
                                $queuedJobs++;
                                $pData = $this->refreshPlanetData($pId, $pData);
                            }
                        }
                    }

                    // Spy Probes (Target: at least 80 on planet)
                    $curProbes = isset($pData[$resource[210]]) ? (int)$pData[$resource[210]] : 0;
                    if ($curProbes < 80 && $queuedJobs < $maxQueueJobs) {
                        if (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 210, array())) {
                            $costProbe = $this->getCostMetal(210);
                            $canProbe  = floor(($supportBudget * 0.20) / max(1.0, $costProbe));
                            $maxAfford = BuildFunctions::getMaxConstructibleElements($this->ctx->user, $pData, 210);
                            $batchProbe = min($canProbe, $maxAfford, 80 - $curProbes);

                            if ($batchProbe > 0 && $this->gateway->constructUnits($pId, 210, $batchProbe)) {
                                $recruited[210] = isset($recruited[210]) ? $recruited[210] + $batchProbe : $batchProbe;
                                $queuedJobs++;
                                $pData = $this->refreshPlanetData($pId, $pData);
                            }
                        }
                    }
                }

                // C. COMBAT WARSHIPS (Combined-Arms Multi-Role Recruitment)
                if ($queuedJobs < $maxQueueJobs) {
                    $selectedWarships = $this->selectTacticalWarships($pData, $attackBudget, $doctrine);

                    if (!empty($selectedWarships)) {
                        $cascadeCarryover = 0.0;

                        foreach ($selectedWarships as $roleConfig) {
                            if ($queuedJobs >= $maxQueueJobs) break;

                            $shipId    = $roleConfig['unit_id'];
                            $score     = $roleConfig['score'];
                            $roleShare = $roleConfig['share'];

                            $currentAlloc = ($attackBudget * $roleShare) + $cascadeCarryover;
                            $unitCostMetal = $this->getCostMetal($shipId);

                            $canBuildUnits = floor($currentAlloc / $unitCostMetal);
                            $maxAffordable = BuildFunctions::getMaxConstructibleElements($this->ctx->user, $pData, $shipId);
                            $unitsToBuild  = min($canBuildUnits, $maxAffordable);

                            if ($unitsToBuild <= 0) {
                                // Cascada de Inversión: carry over budget to next role
                                $cascadeCarryover = $currentAlloc;
                                continue;
                            }

                            if ($this->gateway->constructUnits($pId, $shipId, $unitsToBuild)) {
                                $recruited[$shipId] = isset($recruited[$shipId]) ? $recruited[$shipId] + $unitsToBuild : $unitsToBuild;
                                $queuedJobs++;
                                $spent = $unitsToBuild * $unitCostMetal;
                                $cascadeCarryover = max(0.0, $currentAlloc - $spent);

                                $this->ctx->decisionLog->record(
                                    'force_build_warship',
                                    "Recruited {$unitsToBuild}x {$resource[$shipId]} on body #{$pId} (Role: {$roleConfig['role']}, Score: {$score})",
                                    array('unit' => $shipId, 'batch' => $unitsToBuild, 'role' => $roleConfig['role'], 'score' => $score),
                                    "recruit_ship_{$shipId}_{$pId}",
                                    $score
                                );

                                $pData = $this->refreshPlanetData($pId, $pData);
                            }
                        }

                        // If carryover remains, reinvest into the best affordable warship
                        if ($cascadeCarryover > 0 && $queuedJobs < $maxQueueJobs) {
                            foreach ($selectedWarships as $roleConfig) {
                                $shipId = $roleConfig['unit_id'];
                                $unitCostMetal = $this->getCostMetal($shipId);
                                $canBuildUnits = floor($cascadeCarryover / $unitCostMetal);
                                $maxAffordable = BuildFunctions::getMaxConstructibleElements($this->ctx->user, $pData, $shipId);
                                $unitsToBuild  = min($canBuildUnits, $maxAffordable);

                                if ($unitsToBuild > 0 && $this->gateway->constructUnits($pId, $shipId, $unitsToBuild)) {
                                    $recruited[$shipId] = isset($recruited[$shipId]) ? $recruited[$shipId] + $unitsToBuild : $unitsToBuild;
                                    $queuedJobs++;
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }

        if (!empty($recruited)) {
            $this->ctx->updateBotRow(array('last_recruit' => TIMESTAMP));
        }

        return $recruited;
    }

    /**
     * Selects specialized warships according to the bot's chosen permanent doctrine:
     * - Alta (Heavy): $doctrine['heavy'] (Galeon 225 / Luna Negra 216 / Death Star 214) -> 35%
     * - Media (Medium): $doctrine['medium'] (Destructor 213 / Bomber 211 / Battlecruiser 215) -> 35%
     * - Ligera (Light): $doctrine['light'] (Cruiser 206 / Battleship 207) -> 30%
     */
    private function selectTacticalWarships(array $pData, $attackBudget, array $doctrine)
    {
        $selected = array();

        $roles = array(
            array('role' => self::ROLE_HEAVY,   'unit_id' => $doctrine['heavy'],  'share' => 0.35),
            array('role' => self::ROLE_CAPITAL, 'unit_id' => $doctrine['medium'], 'share' => 0.35),
            array('role' => self::ROLE_ESCORT,  'unit_id' => $doctrine['light'],  'share' => 0.30)
        );

        foreach ($roles as $r) {
            $uId = $r['unit_id'];
            if (!BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, $uId, array())) {
                // Fallback temporal si la tecnología aún no está desarrollada (ej. bot novato)
                if ($r['role'] === self::ROLE_HEAVY) {
                    $fallback = BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 213, array()) ? 213 : (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 215, array()) ? 215 : 206);
                } elseif ($r['role'] === self::ROLE_CAPITAL) {
                    $fallback = BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 215, array()) ? 215 : (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 206, array()) ? 206 : 204);
                } else {
                    $fallback = BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 206, array()) ? 206 : 204;
                }
                $uId = $fallback;
            }

            $score = $this->calculateShipScore($uId, $pData);
            $selected[] = array(
                'role'    => $r['role'],
                'unit_id' => $uId,
                'score'   => $score,
                'share'   => $r['share']
            );
        }

        return $selected;
    }

    /**
     * Technical scoring for warships based on firepower, defense, weapon/armor class and personality
     */
    private function calculateShipScore($shipId, array $pData)
    {
        global $CombatCaps;

        $caps = isset($CombatCaps[$shipId]) ? $CombatCaps[$shipId] : array();
        $att  = isset($caps['attack']) ? (float)$caps['attack'] : 10.0;
        $shd  = isset($caps['shield']) ? (float)$caps['shield'] : 10.0;
        $def  = isset($caps['defend']) ? (float)$caps['defend'] / 10.0 : 10.0;
        $rawPower = $att + $shd + $def;

        $weaponMult = 1.0;
        $hasIon = false;
        $hasPlasmaOrGrav = false;

        if (isset($caps['type_gun'])) {
            $gun = strtolower((string)$caps['type_gun']);
            if (strpos($gun, 'gravity') !== false) {
                $weaponMult = max($weaponMult, 1.80);
                $hasPlasmaOrGrav = true;
            }
            if (strpos($gun, 'plasma') !== false) {
                $weaponMult = max($weaponMult, 1.50);
                $hasPlasmaOrGrav = true;
            }
            if (strpos($gun, 'ion') !== false) {
                $weaponMult = max($weaponMult, 1.30);
                $hasIon = true;
            }
            if (strpos($gun, 'laser') !== false) {
                $weaponMult = max($weaponMult, 1.10);
            }
        }

        $armorMult = 1.0;
        $hasMediumOrHeavyShield = false;

        if (isset($caps['type_defend'])) {
            $arm = strtolower((string)$caps['type_defend']);
            if ($arm === 'heavy') {
                $armorMult = 1.40;
                $hasMediumOrHeavyShield = true;
            } elseif ($arm === 'medium') {
                $armorMult = 1.25;
                $hasMediumOrHeavyShield = true;
            }
        }

        $effectivePower = $rawPower * $weaponMult * $armorMult;
        $costMSE        = BotEconomyValuator::getElementPriceMSE($shipId);
        $costEfficiency = $effectivePower / max(1.0, $costMSE);

        $superPlus = 1.0;
        if (($hasIon && $hasPlasmaOrGrav) || ($rawPower >= 800.0 && $hasMediumOrHeavyShield && $costMSE <= 350000.0)) {
            $superPlus = 2.0;
        }

        $qualityScore = sqrt(max(1.0, $effectivePower)) * $costEfficiency * 60.0 * $superPlus;

        // Phase 5 Dimensional combat power: ((Ataque + Escudo + Casco/10) / Coste_MSE) * 1000 * ArchetypeMultiplier
        $dimensionalScore = ($rawPower / max(1.0, $costMSE)) * 1000.0;
        $archFleetMult    = $this->ctx->personality->getCategoryMultiplier('fleet');

        $finalScore = ($dimensionalScore + $qualityScore) * $archFleetMult;

        if ($rawPower > 5000.0)  $finalScore += 250.0;
        if ($rawPower > 25000.0) $finalScore += 500.0;

        return round($finalScore, 2);
    }

    /**
     * Dynamic evaluation of planetary defenses
     */
    private function evaluateDefenseUnits(array $pData)
    {
        global $reslist, $CombatCaps;

        $defList = (isset($reslist['defense']) && is_array($reslist['defense'])) ? $reslist['defense'] : array();
        $candidates = array();

        foreach ($defList as $defId) {
            if (in_array($defId, $reslist['domes']) || in_array($defId, $reslist['missile'])) {
                continue;
            }
            if (!BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, $defId, array())) {
                continue;
            }

            $caps = isset($CombatCaps[$defId]) ? $CombatCaps[$defId] : array();
            $att  = isset($caps['attack']) ? (float)$caps['attack'] : 10.0;
            $shd  = isset($caps['shield']) ? (float)$caps['shield'] : 10.0;
            $def  = isset($caps['defend']) ? (float)$caps['defend'] / 10.0 : 10.0;
            $rawPower = $att + $shd + $def;

            $weaponMult = 1.0;
            if (isset($caps['type_gun'])) {
                $gun = strtolower((string)$caps['type_gun']);
                if (strpos($gun, 'gravity') !== false) $weaponMult = max($weaponMult, 1.80);
                if (strpos($gun, 'plasma') !== false)  $weaponMult = max($weaponMult, 1.50);
                if (strpos($gun, 'ion') !== false)     $weaponMult = max($weaponMult, 1.30);
                if (strpos($gun, 'laser') !== false)   $weaponMult = max($weaponMult, 1.10);
            }

            $effectivePower = $rawPower * $weaponMult;
            $costMSE        = BotEconomyValuator::getElementPriceMSE($defId);
            $costEfficiency = $effectivePower / max(1.0, $costMSE);

            $qualityScore = sqrt(max(1.0, $effectivePower)) * $costEfficiency * 50.0;

            // Phase 5 Dimensional combat power: ((Ataque + Escudo + Casco/10) / Coste_MSE) * 1000 * ArchetypeMultiplier
            $dimensionalScore = ($rawPower / max(1.0, $costMSE)) * 1000.0;
            $archDefMult      = $this->ctx->personality->getCategoryMultiplier('defense');

            $finalScore = ($dimensionalScore + $qualityScore) * $archDefMult;
            if ($rawPower > 1500.0) $finalScore += 200.0;

            $candidates[] = array(
                'unit_id' => $defId,
                'power'   => $effectivePower,
                'score'   => round($finalScore, 2)
            );
        }

        usort($candidates, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return $candidates;
    }
}

<?php

require_once 'includes/classes/bot/Evaluation/EconomyValuator.class.php';
require_once 'includes/classes/bot/Forces/BotPremiumStrategist.class.php';

/**
 * NovaRush Bot AI v4.0 - Grandmaster Production & Research Engine
 *
 * Implements:
 * 1. Multi-Colony & Lunar Autonomy with independent parallel execution
 * 2. Prerequisite Recursive Resolution (Backward-Chaining Unlock Engine)
 * 3. Payback Period with real construction times & inter-colony catch-up multipliers
 * 4. High-tier science intelligence, dynamic Astro/IRN/Computer scaling & personality matrices
 * 5. Deadlock breaker for 0-field saturated planets & proactive field/warehouse management
 */
class BotProductionAllocator
{
    /**
     * Proyecta niveles de edificios y balance energético considerando la cola de construcción activa
     */
    /**
     * Proyecta los niveles tecnológicos del usuario sumando +1 por cada orden viva en b_tech_queue
     */
    public function getProjectedUserTechs(array $user = null)
    {
        global $resource;
        $u = ($user !== null) ? $user : $this->ctx->user;
        $queue = !empty($u['b_tech_queue']) ? @unserialize($u['b_tech_queue']) : array();
        if (!is_array($queue) || empty($queue)) {
            if (!empty($u['b_tech_planet']) && !empty($u['b_tech_id'])) {
                $tId = (int)$u['b_tech_id'];
                $col = $resource[$tId] ?? null;
                if ($col) {
                    $u[$col] = (int)($u[$col] ?? 0) + 1;
                }
            }
            return $u;
        }

        foreach ($queue as $qItem) {
            if (!is_array($qItem) || empty($qItem[0])) continue;
            $tId = (int)$qItem[0];
            $col = $resource[$tId] ?? null;
            if ($col) {
                $u[$col] = (int)($u[$col] ?? 0) + 1;
            }
        }
        return $u;
    }
    public function getProjectedPlanetData(array $pData, array $buildingQueue)
    {
        global $resource;
        $proj = $pData;
        if (empty($buildingQueue) || !is_array($buildingQueue)) {
            return $proj;
        }

        $config = Config::get();
        $energySpeed = (float)($config->energySpeed ?? 1.0);
        $userFactor  = isset($this->ctx->user['factor']) ? $this->ctx->user['factor'] : array();
        $fEnergy     = isset($userFactor['Energy']) ? (float)$userFactor['Energy'] : 0.0;
        $energyTech  = (int)($this->ctx->user[$resource[113]] ?? 0);

        foreach ($buildingQueue as $qItem) {
            if (!is_array($qItem) || empty($qItem[0])) continue;
            $elemId = (int)$qItem[0];
            $col = $resource[$elemId] ?? null;
            if (!$col) continue;

            $oldLvl = (int)($proj[$col] ?? 0);
            $mode   = $qItem[4] ?? 'build';
            $newLvl = ($mode === 'destroy') ? max(0, $oldLvl - 1) : ($oldLvl + 1);
            $proj[$col] = $newLvl;

            if (in_array($elemId, array(22, 23, 24), true)) {
                $storageMult = (float)($config->storage_multiplier ?? 1.0);
                $fStorage    = isset($userFactor['ResourceStorage']) ? (float)$userFactor['ResourceStorage'] : 0.0;
                $newCap = floor(2.5 * pow(1.8331954764, $newLvl)) * 5000.0 * $storageMult * (1.0 + $fStorage);
                if ($elemId === 22) {
                    $proj['metal_max'] = max((float)($proj['metal_max'] ?? 0.0), $newCap);
                } elseif ($elemId === 23) {
                    $proj['crystal_max'] = max((float)($proj['crystal_max'] ?? 0.0), $newCap);
                } elseif ($elemId === 24) {
                    $proj['deuterium_max'] = max((float)($proj['deuterium_max'] ?? 0.0), $newCap);
                }
            }

            // Proyección de energía producida (#4 Planta Solar, #12 Planta de Fusión)
            if ($elemId === 4) {
                $eOld = 20.0 * $oldLvl * pow(1.1, $oldLvl) * (0.1 * $energySpeed) * (1.0 + $fEnergy);
                $eNew = 20.0 * $newLvl * pow(1.1, $newLvl) * (0.1 * $energySpeed) * (1.0 + $fEnergy);
                $proj['energy'] = (float)($proj['energy'] ?? 0.0) + ($eNew - $eOld);
            } elseif ($elemId === 12) {
                $baseF = 1.05 + ($energyTech * 0.01);
                $eOld = 30.0 * $oldLvl * pow($baseF, $oldLvl) * (0.1 * $energySpeed) * (1.0 + $fEnergy);
                $eNew = 30.0 * $newLvl * pow($baseF, $newLvl) * (0.1 * $energySpeed) * (1.0 + $fEnergy);
                $proj['energy'] = (float)($proj['energy'] ?? 0.0) + ($eNew - $eOld);
            } elseif ($elemId === 1 || $elemId === 2) {
                $uOld = 10.0 * $oldLvl * pow(1.1, $oldLvl);
                $uNew = 10.0 * $newLvl * pow(1.1, $newLvl);
                $proj['energy_used'] = (float)($proj['energy_used'] ?? 0.0) - ($uNew - $uOld);
            } elseif ($elemId === 3) {
                $uOld = 20.0 * $oldLvl * pow(1.1, $oldLvl);
                $uNew = 20.0 * $newLvl * pow(1.1, $newLvl);
                $proj['energy_used'] = (float)($proj['energy_used'] ?? 0.0) - ($uNew - $uOld);
            }
        }

        return $proj;
    }
    const MAX_SAVINGS_LOCK_ETA = 14400; // 4 hours maximum reasonable savings lock horizon

    private $ctx;
    private $gateway;

    public function __construct(BotContext $ctx, BotActionGateway $gateway)
    {
        $this->ctx = $ctx;
        $this->gateway = $gateway;
    }

    /**
     * Compute Strategic Personality Multipliers (0.85x to 1.45x) maintaining minimum floor
     *
     * @return array ['buildings' => array, 'techs' => array]
     */
    public function getPersonalityMultipliers()
    {
        $persName = isset($this->ctx->personality->name) ? strtolower($this->ctx->personality->name) : 'balanced';

        $allBuildIds = array(1, 2, 3, 4, 6, 12, 14, 15, 21, 22, 23, 24, 31, 33, 41, 42, 43, 44, 81, 82, 83, 84);
        $allTechIds  = array(106, 108, 109, 110, 111, 113, 114, 115, 117, 118, 120, 121, 122, 123, 124, 131, 132, 133, 134, 199);

        $buildMults = array_fill_keys($allBuildIds, 0.85);
        $techMults  = array_fill_keys($allTechIds, 0.85);

        switch ($persName) {
            case 'raider':
            case 'aggressive':
            case 'warlord':
            case 'conqueror':
                // Edificios (1.35x): Astillero (21), Módulo Militar (83), Nanobots (15), Sintetizador (3), Sensor Phalanx (42), Puerta de Salto (43)
                foreach (array(21, 83, 15, 3, 42, 43) as $b) {
                    $buildMults[$b] = 1.35;
                }
                // Tecnologías (1.35x): Armas (109), Escudos (110), Blindaje (111), Motores (115, 117, 118), Computación (108), Combustible (134), Astrofísica (124), ramas desbloqueo (114, 120, 121, 122, 199)
                foreach (array(109, 110, 111, 114, 115, 117, 118, 108, 134, 124, 120, 121, 122, 199) as $t) {
                    $techMults[$t] = 1.35;
                }
                break;

            case 'bunker':
            case 'turtle':
            case 'defensive':
            case 'fortress':
            case 'guardian':
                // Edificios (1.35x): Módulo Defensivo (82), Silo (44), Módulo Recursos (81), Fusión (12), Terraformer (33), Minas (1, 2)
                foreach (array(82, 44, 81, 12, 33, 1, 2) as $b) {
                    $buildMults[$b] = 1.35;
                }
                // Tecnologías (1.35x): Escudos (110), Blindaje (111), Armas (109), Gravitón (199), Plasma (122), Iónica (121), Procesamiento (131, 132, 133)
                foreach (array(110, 111, 109, 199, 122, 121, 131, 132, 133) as $t) {
                    $techMults[$t] = 1.35;
                }
                break;

            case 'miner':
            case 'economic':
            case 'technocrat':
                // Edificios (1.40x): Módulo Recursos (81), Minas (1, 2, 3), Universidad (6), Lab (31), Módulo Inv (84), Nanobots (15), Almacenes (22, 23, 24)
                foreach (array(81, 1, 2, 3, 6, 31, 84, 15, 22, 23, 24) as $b) {
                    $buildMults[$b] = 1.40;
                }
                // Tecnologías (1.40x): Procesamiento (131, 132, 133), IRN (123), Astrofísica (124), Energía (113), Espionaje (106), Hiperespacio (114)
                foreach (array(131, 132, 133, 123, 124, 113, 106, 114) as $t) {
                    $techMults[$t] = 1.40;
                }
                break;

            case 'balanced':
            default:
                // Multiplicador 1.15x uniforme en economía (81, 1-3), aceleración (15, 6), expansión (33, 41-43)
                foreach (array(81, 1, 2, 3, 15, 6, 33, 41, 42, 43) as $b) {
                    if (isset($buildMults[$b])) {
                        $buildMults[$b] = 1.15;
                    }
                }
                // Multiplicador 1.15x en aceleración (123), expansión (124, 108), y militar/motores/desbloqueo (109-111, 114, 115, 117, 118, 120-122)
                foreach (array(123, 124, 108, 109, 110, 111, 114, 115, 117, 118, 120, 121, 122) as $t) {
                    if (isset($techMults[$t])) {
                        $techMults[$t] = 1.15;
                    }
                }
                break;
        }

        return array(
            'buildings' => $buildMults,
            'techs'     => $techMults,
        );
    }

    /**
     * Backward-Chaining Prerequisite Unlock Boost Engine
     *
     * Inspects $requeriments for high-tier strategic buildings, technologies,
     * combat units, and defenses. If a strategic goal is locked due to missing
     * requirements, recurses backwards and grants a Strategic Unlock Bonus
     * (+3500 to +9000 points) to the prerequisite element that is currently accessible.
     *
     * @param BotEmpireState $empire
     * @return array [elementId => float boost]
     */
    public function computePrerequisiteUnlockBoosts(BotEmpireState $empire)
    {
        global $requeriments, $resource;

        if (!is_array($requeriments) || empty($requeriments)) {
            return array();
        }

        $strategicTargets = array(
            // Edificios Clave
            81, 82, 83, 84, 15, 33, 6, 44, 41, 42, 43,
            // Tecnologías Clave
            199, 123, 124, 122, 121, 120, 118, 117, 114, 131, 132, 133, 134,
            // Unidades Civiles, Militares y Defensas Élite (series 200 y 400)
            208, 206, 207, 213, 214, 215, 216, 217, 219, 225, 226,
            406, 407, 408, 410, 412, 418
        );

        // Huella Militar Única v4.0: Integrar las 9 unidades exclusivas de por vida del bot
        require_once 'includes/classes/bot/Forces/ForcePlanner.class.php';
        $fp = new BotForcePlanner($this->ctx, $this->gateway);
        $fleetDoctrine = $fp->getOrAssignFleetDoctrine();
        $defDoctrine   = $fp->getOrAssignDefenseDoctrine();

        $botUniqueUnits = array_values(array_filter(array_unique(array(
            (int)($this->ctx->botRow['doctrine_fodder'] ?? $fleetDoctrine['fodder']),
            (int)($this->ctx->botRow['doctrine_light'] ?? $fleetDoctrine['light']),
            (int)($this->ctx->botRow['doctrine_medium'] ?? $fleetDoctrine['medium']),
            (int)($this->ctx->botRow['doctrine_heavy'] ?? $fleetDoctrine['heavy']),
            (int)($this->ctx->botRow['doctrine_special'] ?? $fleetDoctrine['special']),
            (int)($this->ctx->botRow['def_slot1'] ?? $defDoctrine['def_slot1']),
            (int)($this->ctx->botRow['def_slot2'] ?? $defDoctrine['def_slot2']),
            (int)($this->ctx->botRow['def_slot3'] ?? $defDoctrine['def_slot3']),
            (int)($this->ctx->botRow['def_slot4'] ?? $defDoctrine['def_slot4']),
        ))));

        foreach ($botUniqueUnits as $uId) {
            if ($uId > 0 && !in_array($uId, $strategicTargets)) {
                $strategicTargets[] = $uId;
            }
        }

        $user = $this->ctx->user;
        $planets = $empire->getPlanets();
        $moons = $empire->getMoons();
        $allBodies = (is_array($planets) ? $planets : array()) + (is_array($moons) ? $moons : array());

        // Cache max building levels across empire
        $maxBuildingLevels = array();
        for ($bId = 1; $bId <= 84; $bId++) {
            $col = $resource[$bId] ?? null;
            if ($col) {
                $maxLvl = 0;
                foreach ($allBodies as $bEntry) {
                    $lvl = (int)($bEntry['data'][$col] ?? 0);
                    if ($lvl > $maxLvl) {
                        $maxLvl = $lvl;
                    }
                }
                $maxBuildingLevels[$bId] = $maxLvl;
            }
        }

        $boosts = array();

        $resolveReqs = function($elemId, $targetImportance = 1.0, $depth = 0) use (&$resolveReqs, &$boosts, $requeriments, $resource, $user, $maxBuildingLevels) {
            if ($depth > 6 || !isset($requeriments[$elemId])) {
                return;
            }

            foreach ($requeriments[$elemId] as $reqElem => $reqLvl) {
                $reqLvl = (int)$reqLvl;
                $isTech = ($reqElem >= 100 && $reqElem < 200);
                $col = $resource[$reqElem] ?? null;
                $curLvl = 0;

                if ($isTech) {
                    $curLvl = (int)($user[$col] ?? 0);
                } else {
                    $curLvl = (int)($maxBuildingLevels[$reqElem] ?? 0);
                }

                if ($curLvl < $reqLvl) {
                    $missing = $reqLvl - $curLvl;
                    // Closeness scale: +3500 to +9000
                    $closeness = max(0.0, 1.0 - ($missing / 10.0));
                    $boostVal = (3500.0 + (5500.0 * $closeness)) * $targetImportance;

                    // Check if $reqElem itself has unmet prerequisites
                    $hasUnmetPrereq = false;
                    if (isset($requeriments[$reqElem])) {
                        foreach ($requeriments[$reqElem] as $subReq => $subLvl) {
                            $subIsTech = ($subReq >= 100 && $subReq < 200);
                            $subCol = $resource[$subReq] ?? null;
                            $subCur = $subIsTech ? (int)($user[$subCol] ?? 0) : (int)($maxBuildingLevels[$subReq] ?? 0);
                            if ($subCur < (int)$subLvl) {
                                $hasUnmetPrereq = true;
                                break;
                            }
                        }
                    }

                    if (!$hasUnmetPrereq) {
                        // $reqElem is immediately accessible to build or research!
                        if (!isset($boosts[$reqElem]) || $boosts[$reqElem] < $boostVal) {
                            $boosts[$reqElem] = round(min(9000.0, $boostVal), 2);
                        }
                    } else {
                        // Recurse backward to the accessible root prerequisite
                        $resolveReqs($reqElem, $targetImportance * 0.95, $depth + 1);
                    }
                }
            }
        };

        $hourlyEmpireMSE = max(1000.0, $empire->getHourlyProductionMSE());

        foreach ($strategicTargets as $targetId) {
            if (($targetId >= 200 && $targetId != 208) || in_array($targetId, array(6, 43, 82, 83, 84), true)) {
                $unitMSE = BotEconomyValuator::getElementPriceMSE($targetId);
                if ($unitMSE > ($hourlyEmpireMSE * 4.0)) {
                    continue;
                }
            }

            $importance = 1.0;
            if (in_array($targetId, $botUniqueUnits)) {
                $importance = 1.60;
            } elseif ($targetId == 208) {
                $maxPlanets = class_exists('PlayerUtil') ? (int)PlayerUtil::maxPlanetCount($this->ctx->user) : 9;
                $curPlanets = count($empire->getPlanets());
                $importance = ($curPlanets < min(9, $maxPlanets)) ? 2.5 : 0.8;
            } elseif (in_array($targetId, array(206, 213, 215, 216, 225, 406))) {
                $importance = 1.25;
            } elseif (in_array($targetId, array(123, 6, 120, 121, 122, 199))) {
                $importance = 1.20;
            }
            $resolveReqs($targetId, $importance, 0);
        }

        return $boosts;
    }

    /**
     * Run an economic investment cycle across all colonies and moons
     *
     * @param BotEmpireState $empire
     * @return int Number of building constructions queued
     */
    /**
     * Determina si la personalidad del bot es Equilibrada o Económica (true -> Módulo #84)
     * o si es Agresiva / Búnker (false -> Módulos #82 y #83).
     */
    public function isBalancedOrEconomicPersonality()
    {
        $persName = '';
        if (isset($this->ctx->personality) && is_object($this->ctx->personality)) {
            $persName = strtolower((string)$this->ctx->personality->name);
        } elseif (isset($this->ctx->botRow['personality'])) {
            $persName = strtolower((string)$this->ctx->botRow['personality']);
        }
        if (empty($persName)) {
            $persName = 'balanced';
        }

        if (in_array($persName, array('raider', 'aggressive', 'warlord', 'conqueror', 'bunker', 'turtle', 'defensive', 'fortress', 'guardian'), true)) {
            return false;
        }
        return true;
    }

    /**
     * Calcula los niveles meta de toda la plantilla planetaria en función del nivel de Mina de Metal ($lM)
     */
    public function getTemplateTargetLevels($lM)
    {
        $lM = max(0, (int)$lM);
        $isEcoOrBal = $this->isBalancedOrEconomicPersonality();

        $baseRobots = (int)floor($lM / 3.0);
        $hangarTarget = (int)floor($lM / 3.0);
        $storeTarget  = (int)floor($lM / 2.0);

        // Si la plantilla ya pide Hangar >= 1 (Lm >= 3), Robots necesita al menos nivel 2.
        // Si la plantilla ya pide Almacenes >= 3 (Lm >= 6), Robots necesita al menos nivel 3 para desbloquear Almacenes.
        if ($storeTarget >= 3 && $baseRobots < 3) {
            $robotsTarget = 3;
        } elseif ($hangarTarget >= 1 && $baseRobots < 2) {
            $robotsTarget = 2;
        } else {
            $robotsTarget = $baseRobots;
        }

        return array(
            4  => $lM,                                      // Por cada 1 Lm = +1 nivel Planta Solar
            2  => max(0, $lM - 1),                          // Mina de Cristal = Lm - 1
            3  => max(0, $lM - 4),                          // Mina de Deuterio = Lm - 4
            14 => $robotsTarget,                            // Fábrica de Robots adaptada a pre-requisitos
            15 => (int)floor($lM / 4.0),                    // Por cada 4 Lm = +1 nivel Fábrica de Nanobots
            81 => (int)floor($lM / 1.5),                    // Por cada 1.5 Lm = +1 nivel Módulo de Recursos
            31 => (int)floor($lM / 2.0),                    // Por cada 2 Lm = +1 nivel Laboratorio
            21 => $hangarTarget,                            // Por cada 3 Lm = +1 nivel Hangar
            84 => $isEcoOrBal ? (int)floor($lM / 2.0) : 0,  // Por cada 2 Lm = +1 Módulo Investigación (Solo Equilibrada/Económica)
            83 => !$isEcoOrBal ? (int)floor($lM / 2.0) : 0, // Por cada 2 Lm = +1 Módulo Militar (Solo Agresiva/Búnker)
            82 => !$isEcoOrBal ? (int)floor($lM / 2.0) : 0, // Por cada 2 Lm = +1 Módulo Defensa (Solo Agresiva/Búnker)
            22 => $storeTarget,                             // Por cada 2 Lm = +1 Almacén de Metal
            23 => $storeTarget,                             // Por cada 2 Lm = +1 Almacén de Cristal
            24 => $storeTarget,                             // Por cada 2 Lm = +1 Almacén de Deuterio
            44 => (int)floor($lM / 4.0),                    // Por cada 4 Lm = +1 Silo de Misiles
        );
    }

    /**
     * Calcula los niveles meta de todas las tecnologías imperiales en función del nivel líder de Mina de Metal ($lM)
     */
    public function getResearchTemplateTargetLevels($lM, $planetCount = 9)
    {
        $lM = max(0, (int)$lM);
        $isEcoOrBal = $this->isBalancedOrEconomicPersonality();

        return array(
            // Fase 1: Llaves de Infraestructura, Energía y Aceleración Científica
            113 => (int)floor($lM / 2.0),                                              // Por cada 2.0 Lm = +1 Energía
            108 => (int)floor($lM / 2.25),                                             // Por cada 2.25 Lm = +1 Informática
            123 => (int)floor($lM / 5.0),                                              // Por cada 5.0 Lm = +1 Red Intergaláctica IRN
            199 => min(7, max(0, (int)floor(($lM - 16) / 4.0))),                      // Nivel 1 desde Lm 20, +1 cada 4 Lm (tope 7)

            // Fase 2: Expansión Imperial, Motores y Reconocimiento
            106 => (int)floor($lM / 2.0),                                              // Por cada 2.0 Lm = +1 Espionaje
            115 => (int)floor($lM / 2.0),                                              // Por cada 2.0 Lm = +1 Motor de Combustión
            117 => (int)floor($lM / 2.25),                                             // Por cada 2.25 Lm = +1 Motor de Impulso
            124 => (int)floor($lM / 2.25),                                             // Por cada 2.25 Lm = +1 Investigación de Expedición (Astrofísica)
            114 => (int)floor($lM / 2.25),                                             // Por cada 2.25 Lm = +1 Tecnología Hiperespacial
            118 => (int)floor($lM / 2.5),                                              // Por cada 2.5 Lm = +1 Motor Hiperespacial

            // Fase 3: Producción Imperial y Tríada Militar (diferenciado por personalidad)
            131 => $isEcoOrBal ? (int)floor($lM / 1.25) : (int)floor($lM / 1.5),       // Procesamiento Metal
            132 => $isEcoOrBal ? (int)floor($lM / 1.25) : (int)floor($lM / 1.5),       // Procesamiento Cristal
            133 => $isEcoOrBal ? (int)floor($lM / 1.25) : (int)floor($lM / 1.5),       // Procesamiento Deuterio
            109 => $isEcoOrBal ? (int)floor($lM / 2.0)  : (int)floor($lM / 1.5),       // Tecnología de Armas
            110 => $isEcoOrBal ? (int)floor($lM / 2.0)  : (int)floor($lM / 1.5),       // Tecnología de Escudos
            111 => $isEcoOrBal ? (int)floor($lM / 2.0)  : (int)floor($lM / 1.5),       // Tecnología de Armadura

            // Fase 4: Especialización de Armamento y Eficiencia de Combustible
            120 => (int)floor($lM / 1.38),                                             // Por cada 1.38 Lm = +1 Tecnología Láser
            121 => (int)floor($lM / 1.65),                                             // Por cada 1.65 Lm = +1 Tecnología de Iones
            122 => (int)floor($lM / 2.0),                                              // Por cada 2.0 Lm = +1 Tecnología de Plasma
            134 => (int)floor($lM / 3.0),                                              // Por cada 3.0 Lm = +1 Optimización de Combustible
        );
    }

    /**
     * Determina la tecnología prioritaria que corresponde investigar según la plantilla ordenada de 4 Fases
     * anclada al mayor nivel de Mina de Metal del imperio ($maxLm).
     */
    public function getMandatoryResearchTech(BotEmpireState $empire, array $labData, array $gravitonHubData = null, $isFutureQueueSlot = false)
    {
        global $resource;

        $planets = $empire->getPlanets();
        $planetCount = is_array($planets) ? count($planets) : 1;

        $baseMaxLm = 0;
        if (is_array($planets)) {
            foreach ($planets as $pEntry) {
                $lm = (int)($pEntry['data'][$resource[1]] ?? 0);
                if (!empty($pEntry['building_queue'][0][0]) && (int)$pEntry['building_queue'][0][0] === 1) {
                    $lm++;
                }
                if ($lm > $baseMaxLm) {
                    $baseMaxLm = $lm;
                }
            }
        }
        if ($baseMaxLm < 2) {
            $baseMaxLm = max(2, (int)($labData[$resource[1]] ?? 2));
        }

        $isEcoOrBal = $this->isBalancedOrEconomicPersonality();
        $phase1 = array(113, 108, 123, 199);
        $phase2 = array(106, 115, 117, 124, 114, 118);
        $phase3 = $isEcoOrBal
            ? array(131, 132, 133, 109, 110, 111)
            : array(109, 110, 111, 131, 132, 133);
        $phase4 = array(120, 121, 122, 134);
        $orderedTechs = array_merge($phase1, $phase2, $phase3, $phase4);

        $hourlyEmpireMSE = max(1000.0, $empire->getHourlyProductionMSE());
        $tacticalDeutReserve = 25000.0;
        $projUser = $this->getProjectedUserTechs();

        $maxLookaheadLm = $isFutureQueueSlot ? ($baseMaxLm + 6) : $baseMaxLm;

        for ($simLm = 2; $simLm <= $maxLookaheadLm; $simLm++) {
            $targets = $this->getResearchTemplateTargetLevels($simLm, $planetCount);
            $firstPendingTechId = 0;

            foreach ($orderedTechs as $tId) {
                $targetLvl = (int)($targets[$tId] ?? 0);
                if ($targetLvl <= 0) {
                    continue;
                }
                $curLvl = (int)($projUser[$resource[$tId]] ?? 0);
                if ($curLvl >= $targetLvl) {
                    continue;
                }

                $evalData = ($tId === 199 && $gravitonHubData !== null) ? $gravitonHubData : $labData;
                if (!BuildFunctions::isTechnologieAccessible($projUser, $evalData, $tId, array())) {
                    continue;
                }

                if ($tId === 199) {
                    $reqEnergy = ($curLvl + 1 >= 7) ? 82500000.0 : (300000.0 * pow(2.55, $curLvl));
                    if ((float)($evalData['energy'] ?? 0.0) < $reqEnergy) {
                        continue;
                    }
                    return 199;
                }

                // En slots futuros (1..N-1), respetar el orden estricto de la plantilla sin desvio por liquidez actual
                if ($isFutureQueueSlot) {
                    return $tId;
                }

                $cost = BuildFunctions::getElementPrice($projUser, $evalData, $tId, false, $curLvl);
                $costMSE = BotEconomyValuator::arrayToMSE($cost);
                $costHP  = BotEconomyValuator::toHP($costMSE, $hourlyEmpireMSE);

                if ($firstPendingTechId === 0) {
                    $firstPendingTechId = $tId;

                    $availM = (float)($evalData['metal'] ?? 0.0);
                    $availC = (float)($evalData['crystal'] ?? 0.0);
                    $availD = max(0.0, (float)($evalData['deuterium'] ?? 0.0) - $tacticalDeutReserve);

                    $costM = (float)($cost[901] ?? $cost['metal'] ?? 0.0);
                    $costC = (float)($cost[902] ?? $cost['crystal'] ?? 0.0);
                    $costD = (float)($cost[903] ?? $cost['deuterium'] ?? 0.0);

                    if (($availM >= $costM && $availC >= $costC && $availD >= $costD) || $costHP <= 3.5) {
                        return $firstPendingTechId;
                    }
                    continue;
                } else {
                    $availM = (float)($evalData['metal'] ?? 0.0);
                    $availC = (float)($evalData['crystal'] ?? 0.0);
                    $availD = max(0.0, (float)($evalData['deuterium'] ?? 0.0) - $tacticalDeutReserve);

                    $costM = (float)($cost[901] ?? $cost['metal'] ?? 0.0);
                    $costC = (float)($cost[902] ?? $cost['crystal'] ?? 0.0);
                    $costD = (float)($cost[903] ?? $cost['deuterium'] ?? 0.0);

                    if ($availM >= $costM && $availC >= $costC && $availD >= $costD && $costHP <= 2.0) {
                        return $tId;
                    }
                }
            }

            if ($firstPendingTechId > 0) {
                return $firstPendingTechId;
            }
        }

        return 0;
    }

    /**
     * Determina si un planeta tiene pendiente algún edificio obligatorio de su Escalón de Infraestructura
     * según el nivel actual de su Mina de Metal ($lM), incluyendo las Válvulas Anti-Bloqueo:
     * - Válvula 1 (Requisito tecnológico): Solo evalúa edificios ya accesibles tecnológicamente.
     * - Válvula 2 (Capacidad de almacén): Si el edificio obligatorio cuesta > 90% del almacén actual, devuelve el Almacén (#22, #23 o #24) primero.
     * - Válvula 3 (Esfuerzo económico): Si el edificio obligatorio cuesta > 2.5 HP del imperio (y las minas están por debajo de 24), permite subir la mina del recurso deficitario.
     *
     * Desde la Etapa 5 (LM >= 20, cuando Gravitón 1 desbloquea #81), exige 1 nivel de Módulo de Recursos (#81)
     * por cada nivel de Mina de Metal a partir del 16 ($targetMod81 = $lM - 15).
     */
    public function getMandatoryMilestoneBuilding(array $pData, $isTopLabPlanet, $isBestLab, $hourlyEmpireMSE, $isPrimaryMilitaryPlanet = false, $planetHourlyMSE = 0.0, $freeFields = 99, array $rawPData = null, $isFutureQueueSlot = false)
    {
        global $resource;

        $checkData = ($rawPData !== null) ? $rawPData : $pData;

        // Fase 0 — Supervivencia Urbana: Si quedan <= 5 campos libres, Terraformador (#33) es prioridad absoluta
        if ($freeFields <= 5 && BuildFunctions::isTechnologieAccessible($this->ctx->user, $checkData, 33, array())) {
            $cost = BuildFunctions::getElementPrice($this->ctx->user, $pData, 33, false);
            $costC = (float)($cost[902] ?? $cost['crystal'] ?? 0.0);
            $costD = (float)($cost[903] ?? $cost['deuterium'] ?? 0.0);
            $maxC = max(10000.0, (float)($pData['crystal_max'] ?? 10000.0));
            $maxD = max(10000.0, (float)($pData['deuterium_max'] ?? 10000.0));
            if ($costC > 0.90 * $maxC && BuildFunctions::isTechnologieAccessible($this->ctx->user, $checkData, 23, array())) {
                return 23;
            }
            if ($costD > 0.90 * $maxD && BuildFunctions::isTechnologieAccessible($this->ctx->user, $checkData, 24, array())) {
                return 24;
            }
            return 33;
        }

        $lM = (int)($pData[$resource[1]] ?? 0);
        $solarLvl = (int)($pData[$resource[4]] ?? 0);

        if ($lM == 0) {
            if ($solarLvl == 0 && BuildFunctions::isTechnologieAccessible($this->ctx->user, $checkData, 4, array())) {
                return 4;
            }
            return 1;
        }

        $energy = (float)($pData['energy'] ?? 0.0);
        $energyUsed = abs((float)($pData['energy_used'] ?? 0.0));
        $energyNet = $energy - $energyUsed;

        $chosenId = 0;

        // Prioridad Energética Inmediata si energyNet < 0
        if ($energyNet < 0.0 && BuildFunctions::isTechnologieAccessible($this->ctx->user, $checkData, 4, array())) {
            $chosenId = 4;
        }

        // Recorrido progresivo escalón por escalón (1 .. Lm) para intercalar inteligentemente los edificios
        if ($chosenId === 0) {
            for ($stepLm = 1; $stepLm <= $lM; $stepLm++) {
                $targets = $this->getTemplateTargetLevels($stepLm);

                if ($isBestLab && $stepLm >= 20 && (int)($this->ctx->user[$resource[199]] ?? 0) === 0) {
                    $targets[31] = max((int)($targets[31] ?? 0), 12);
                    if ((int)($pData[$resource[31]] ?? 0) >= 12 && (float)($pData['energy'] ?? 0.0) < 300000.0) {
                        $energyBId = (BuildFunctions::isTechnologieAccessible($this->ctx->user, $checkData, 12, array()) && (int)($pData[$resource[3]] ?? 0) >= 10) ? 12 : 4;
                        if (BuildFunctions::isTechnologieAccessible($this->ctx->user, $checkData, $energyBId, array())) {
                            $chosenId = $energyBId;
                            break;
                        }
                    }
                }

                // Fase 0 — Energía del escalón: Planta Solar (#4) < meta del escalón
                if ($solarLvl < $targets[4] && BuildFunctions::isTechnologieAccessible($this->ctx->user, $checkData, 4, array())) {
                    $chosenId = 4;
                    break;
                }

                // Fase 1 — Aceleradores de Tiempo (#14 y #15)
                foreach (array(14, 15) as $bId) {
                    if ($targets[$bId] > 0 && (int)($pData[$resource[$bId]] ?? 0) < $targets[$bId]) {
                        if (BuildFunctions::isTechnologieAccessible($this->ctx->user, $checkData, $bId, array())) {
                            $chosenId = $bId;
                            break 2;
                        }
                    }
                }

                // Fase 2 — Motor de Ingresos (#2, #3, #81)
                foreach (array(2, 3, 81) as $bId) {
                    if ($targets[$bId] > 0 && (int)($pData[$resource[$bId]] ?? 0) < $targets[$bId]) {
                        if (BuildFunctions::isTechnologieAccessible($this->ctx->user, $checkData, $bId, array())) {
                            $chosenId = $bId;
                            break 2;
                        }
                    }
                }

                // Fase 3 — Infraestructura, Especialización y Almacenes (#31, #21, #22, #23, #24, #84, #83, #82, #44)
                foreach (array(31, 21, 22, 23, 24, 84, 83, 82, 44) as $bId) {
                    if ($targets[$bId] > 0 && (int)($pData[$resource[$bId]] ?? 0) < $targets[$bId]) {
                        if (BuildFunctions::isTechnologieAccessible($this->ctx->user, $checkData, $bId, array())) {
                            $chosenId = $bId;
                            break 2;
                        }
                    }
                }
            }
        }

        // Fase 4 — Cierre de Ciclo y Avance de Escalón (Lm -> Lm + 1)
        if ($chosenId === 0) {
            $chosenId = 1;
        }

        // --- Válvula A (Capacidad de Almacenes) ---
        $cost = BuildFunctions::getElementPrice($this->ctx->user, $pData, $chosenId, false);
        $costM = (float)($cost[901] ?? $cost['metal'] ?? 0.0);
        $costC = (float)($cost[902] ?? $cost['crystal'] ?? 0.0);
        $costD = (float)($cost[903] ?? $cost['deuterium'] ?? 0.0);

        $maxM = max(10000.0, (float)($pData['metal_max'] ?? 10000.0));
        $maxC = max(10000.0, (float)($pData['crystal_max'] ?? 10000.0));
        $maxD = max(10000.0, (float)($pData['deuterium_max'] ?? 10000.0));

        if ($costM > 0.90 * $maxM && BuildFunctions::isTechnologieAccessible($this->ctx->user, $checkData, 22, array())) {
            return 22;
        }
        if ($costC > 0.90 * $maxC && BuildFunctions::isTechnologieAccessible($this->ctx->user, $checkData, 23, array())) {
            return 23;
        }
        if ($costD > 0.90 * $maxD && BuildFunctions::isTechnologieAccessible($this->ctx->user, $checkData, 24, array())) {
            return 24;
        }

        // --- Válvula B (Esfuerzo Económico) ---
        $isValvulaBCandidate = false;
        $maxCostHPThreshold = 3.0;
        $useEmpireHPRef = false;
        if (!$isFutureQueueSlot) {
            $isGravitonLab = ($chosenId === 31 && $isBestLab && $lM >= 20 && (int)($this->ctx->user[$resource[199]] ?? 0) === 0);
            if ($isGravitonLab) {
                $isValvulaBCandidate = true;
                $maxCostHPThreshold = 6.0;
                $useEmpireHPRef = true;
            } elseif (in_array($chosenId, array(31, 21, 22, 23, 24, 84, 83, 82, 44), true)) {
                $isValvulaBCandidate = true;
            } elseif ($chosenId === 15 && (int)($pData[$resource[15]] ?? 0) >= 1) {
                $isValvulaBCandidate = true;
            } elseif ($chosenId === 14 && (int)($pData[$resource[14]] ?? 0) >= 5) {
                $isValvulaBCandidate = true;
            }
        }

        if ($isValvulaBCandidate) {
            $availM = (float)($pData['metal'] ?? 0.0);
            $availC = (float)($pData['crystal'] ?? 0.0);
            $availD = max(0.0, (float)($pData['deuterium'] ?? 0.0) - 25000.0);

            $canAffordNow = ($availM >= $costM && $availC >= $costC && $availD >= $costD);
            if (!$canAffordNow) {
                $costMSE = BotEconomyValuator::arrayToMSE($cost);
                $localRefMSE = ($planetHourlyMSE > 0.0) ? $planetHourlyMSE : ($hourlyEmpireMSE / 5.0);
                $refMSE = ($useEmpireHPRef && $hourlyEmpireMSE > 0.0) ? $hourlyEmpireMSE : max(500.0, $localRefMSE);
                $localCostHP = BotEconomyValuator::toHP($costMSE, $refMSE);

                if ($localCostHP > $maxCostHPThreshold) {
                    $defM_MSE = max(0.0, $costM - $availM) * 1.0;
                    $defC_MSE = max(0.0, $costC - $availC) * 2.0;
                    $defD_MSE = max(0.0, $costD - $availD) * 4.0;
                    if ($defC_MSE >= $defM_MSE && $defC_MSE >= $defD_MSE) {
                        return 2;
                    } elseif ($defD_MSE >= $defM_MSE && $defD_MSE >= $defC_MSE) {
                        return 3;
                    } else {
                        return 1;
                    }
                }
            }
        }

        return $chosenId;
    }

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

        // Module Priority Hierarchy by Personality
        $personality = '';
        if (isset($this->ctx->personality) && is_object($this->ctx->personality)) {
            $personality = strtolower((string)$this->ctx->personality->name);
        } elseif (isset($this->ctx->botRow['personality'])) {
            $personality = strtolower((string)$this->ctx->botRow['personality']);
        }
        if (empty($personality)) {
            $personality = 'aggressive';
        }
        if (strpos($personality, 'aggress') !== false || strpos($personality, 'raid') !== false) {
            $modHierarchy = array(81, 83, 82, 84); // 1º #81 -> 2º #83 (Military) -> 3º #82 (Defense) -> 4º #84 (Research)
        } elseif (strpos($personality, 'peace') !== false || strpos($personality, 'collect') !== false || strpos($personality, 'eco') !== false) {
            $modHierarchy = array(81, 84, 82, 83); // 1º #81 -> 2º #84 (Research) -> 3º #82 (Defense) -> 4º #83 (Military)
        } else {
            $modHierarchy = array(81, 84, 83, 82); // 1º #81 -> 2º #84 (Research) -> 3º #83 (Military) -> 4º #82 (Defense)
        }
        $m1 = $modHierarchy[0];
        $m2 = $modHierarchy[1];
        $m3 = $modHierarchy[2];
        $m4 = $modHierarchy[3];

        $planets = $empire->getPlanets();
        if (!is_array($planets) || empty($planets)) {
            return 0;
        }



        $hourlyEmpireMSE = $empire->getHourlyProductionMSE();
        $persMults = $this->getPersonalityMultipliers();
        $unlockBoosts = $this->computePrerequisiteUnlockBoosts($empire);

        $homePlanetEntry = $empire->getPlanet($this->ctx->botRow['home_planet_id'] ?? 0);
        $homePlanetData = !empty($homePlanetEntry) ? $homePlanetEntry['data'] : (!empty($planets) ? reset($planets)['data'] : array());
        $isColonizerUnlocked = BuildFunctions::isTechnologieAccessible($this->ctx->user, $homePlanetData, 208, array());
        $maxLegalPlanets = class_exists('PlayerUtil') ? (int)PlayerUtil::maxPlanetCount($this->ctx->user) : 9;

        // Compute average mine levels across empire for catch-up calculations
        $mineAverages = array(1 => 0.0, 2 => 0.0, 3 => 0.0, 81 => 0.0);
        $planetCount = count($planets);
        foreach ($planets as $pEntry) {
            foreach (array(1, 2, 3, 81) as $mCheck) {
                $col = $resource[$mCheck] ?? null;
                if ($col) {
                    $mineAverages[$mCheck] += (float)($pEntry['data'][$col] ?? 0.0);
                }
            }
        }
        foreach ($mineAverages as $mK => $mV) {
            $mineAverages[$mK] = $planetCount > 0 ? ($mV / $planetCount) : 0.0;
        }

        // Rank lab colonies by effective research speed for IRN connected network
        $irnLvl = (int)($this->ctx->user[$resource[123]] ?? 0);
        $maxConnectedLabs = 1 + $irnLvl;
        $empireLabSpeeds = array();
        foreach ($planets as $pCandId => $pCandEntry) {
            $pCandData = $pCandEntry['data'];
            $candLab = (int)($pCandData[$resource[31]] ?? 0);
            if ($candLab > 0) {
                $spd = $candLab * 10;
                if (!empty($pCandData[$resource[6]])) {
                    $spd += 50 + ((int)$pCandData[$resource[6]] * 5);
                }
                if (!empty($pCandData[$resource[84]])) {
                    $spd += 10 + ((int)$pCandData[$resource[84]] * 2);
                }
                $empireLabSpeeds[$pCandId] = $spd;
            }
        }
        arsort($empireLabSpeeds);
        $topLabPlanetIds = array_slice(array_keys($empireLabSpeeds), 0, $maxConnectedLabs, true);
        $topLabPlanetMap = array_fill_keys($topLabPlanetIds, true);

        // Designated Graviton Energy Hub: planet with lab >= 12 (or highest lab >= 10) and highest energy / satellites built
        $curGravLvl = (int)($this->ctx->user[$resource[199]] ?? 0);
        $gravitonHubPlanetId = 0;
        $bestHubScore = -1.0;
        $bestLabPlanetId = !empty($topLabPlanetIds) ? (int)reset($topLabPlanetIds) : 0;
        $hasLab12InEmpire = false;
        foreach ($planets as $pCandEntry) {
            if ((int)($pCandEntry['data'][$resource[31]] ?? 0) >= 12) {
                $hasLab12InEmpire = true;
                break;
            }
        }
        foreach ($planets as $pCandId => $pCandEntry) {
            $pCandData = $pCandEntry['data'];
            $candLab = (int)($pCandData[$resource[31]] ?? 0);
            $candHangar = (int)($pCandData[$resource[21]] ?? 0);
            $candEnergy = (float)($pCandData['energy'] ?? 0.0);
            $labEligible = $hasLab12InEmpire ? ($candLab >= 12) : ($candLab >= 10);
            if ($labEligible && ($candHangar >= 8 || $curGravLvl === 0 || $candEnergy >= 300000.0)) {
                $satE = max(1.0, (($pCandData['temp_max'] + 160) / 6.0));
                $existingSats = (int)($pCandData[$resource[212]] ?? 0);
                $hubScore = $candEnergy + ($existingSats * $satE) + ($satE * 500.0) + ($candLab * 100000.0);
                if ($hubScore > $bestHubScore) {
                    $bestHubScore = $hubScore;
                    $gravitonHubPlanetId = (int)$pCandId;
                }
            }
        }
        if ($gravitonHubPlanetId === 0 && $bestLabPlanetId > 0) {
            $gravitonHubPlanetId = $bestLabPlanetId;
        }

        foreach ($planets as $pId => $planetEntry) {
            $pData = $planetEntry['data'];
            $projData = $this->getProjectedPlanetData($pData, $planetEntry['building_queue'] ?? array());
            $isTopLabPlanet = isset($topLabPlanetMap[$pId]) || empty($empireLabSpeeds);
            $isBestLab = ($pId === $bestLabPlanetId);

            // Renombrar orgánicamente si el planeta/colonia tiene nombre por defecto
            $defaultPlanetNames = array('Colonia', 'Hauptplanet', 'Colony');
            global $LNG;
            if (isset($LNG['fcp_colony'])) {
                $defaultPlanetNames[] = $LNG['fcp_colony'];
            }
            if (in_array(trim($pData['name']), $defaultPlanetNames, true)) {
                if (file_exists('includes/classes/bot/BotNameHelper.class.php')) {
                    require_once 'includes/classes/bot/BotNameHelper.class.php';
                    $newPlanetName = BotNameHelper::getRandomPlanetName();
                    if ($pData['name'] !== $newPlanetName) {
                        Database::get()->update("UPDATE %%PLANETS%% SET name = :name WHERE id = :id;", array(
                            ':name' => $newPlanetName,
                            ':id'   => $pId,
                        ));
                        $planetEntry['data']['name'] = $newPlanetName;
                        $pData['name'] = $newPlanetName;
                    }
                }
            }

            $maxBuildSlots = (isset($this->ctx->difficulty) && method_exists($this->ctx->difficulty, 'getMaxBuildQueueSlots'))
                ? $this->ctx->difficulty->getMaxBuildQueueSlots()
                : 3;

            // Check if building queue is full
            if (!empty($planetEntry['building_queue'])) {
                continue;
            }

            $energy = (float)$projData['energy'];
            $energyUsed = abs((float)$projData['energy_used']);
            $energyNet = $energy - $energyUsed;
            $desiredEnergyBuffer = max(2000.0, $energyUsed * 0.12);
            $freeFields = CalculateMaxPlanetFields($pData) - ((int)$pData['field_current'] + count($planetEntry['building_queue']));

            // --- 1. Proactive Energy Scaling & Graviton Satellites Push (Up to Level 7) ---
            if (BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 212, array())) {
                $hangarQueue = !empty($planetEntry['hangar_queue']) ? $planetEntry['hangar_queue'] : array();
                $maxHangarLen = ($pId === $gravitonHubPlanetId && $curGravLvl < 7) ? 5 : 2;
                if (count($hangarQueue) < $maxHangarLen) {
                    $energyPerSat = max(1.0, (($pData['temp_max'] + 160) / 6.0));
                    $satsToQueue = 0;
                    $config = Config::get();
                    $energySpeed = (float)($config->energySpeed ?? 1.0);
                    $userFactor = isset($this->ctx->user['factor']) ? $this->ctx->user['factor'] : array();
                    $fEnergy = isset($userFactor['Energy']) ? (float)$userFactor['Energy'] : 0.0;
                    $realEnergyPerSat = max(1.0, floor(($pData['temp_max'] + 160) / 6.0) * $energySpeed * (1.0 + $fEnergy));

                    if ($pId === $gravitonHubPlanetId) {
                        if ($energyNet < $desiredEnergyBuffer) {
                            $energyDeficit = $desiredEnergyBuffer - $energyNet;
                            $satsToQueue = (int)ceil($energyDeficit / $realEnergyPerSat);
                        } elseif ($curGravLvl < 7) {
                            $targetGravLvl = $curGravLvl + 1;
                            $allowGravSatPush = false;
                            $empireHourlyMSE = $empire->getHourlyProductionMSE();
                            $botMoonsCount = count($empire->getMoons());
                            $planetCount = count($planets);
                            $lM = (int)($pData[$resource[1]] ?? 0);

                            if ($targetGravLvl === 1 && ($botMoonsCount >= 1 || $planetCount >= 4 || $lM >= 20)) {
                                $allowGravSatPush = true;
                            } elseif ($targetGravLvl <= 3 && $empireHourlyMSE >= 250000000 && $planetCount >= 10) {
                                $allowGravSatPush = true;
                            } elseif ($targetGravLvl <= 5 && $empireHourlyMSE >= 900000000 && $planetCount >= 14) {
                                $allowGravSatPush = true;
                            } elseif ($targetGravLvl === 6 && $empireHourlyMSE >= 1400000000 && $planetCount >= 16) {
                                $allowGravSatPush = true;
                            } elseif ($targetGravLvl === 7 && $empireHourlyMSE >= 2100000000 && $planetCount >= 16) {
                                $allowGravSatPush = true;
                            }

                            if ($allowGravSatPush) {
                                $targetEnergyReq = ($targetGravLvl >= 7) ? 82500000.0 : (300000.0 * pow(2.55, $targetGravLvl - 1));
                                $neededForGraviton = max(0.0, $targetEnergyReq - $energy);
                                if ($neededForGraviton > 0) {
                                    $satsToQueue = min(45000, (int)ceil($neededForGraviton / $realEnergyPerSat));
                                }
                            }
                        }
                    } else {
                        // Planetas que NO son Hub de Gravitón: solo fabricar satélites si energyNet < 0 y solar_plant >= 25
                        $curSolarPlant = (int)($pData[$resource[4]] ?? 0);
                        if ($energyNet < 0.0 && $curSolarPlant >= 25) {
                            $energyDeficit = abs($energyNet);
                            $satsToQueue = (int)ceil($energyDeficit / $realEnergyPerSat);
                        }
                    }

                    if ($satsToQueue > 0) {
                        if ($pId === $gravitonHubPlanetId) {
                            $neededSatCost = array(901 => 0.0, 902 => (float)($satsToQueue * 2000), 903 => (float)($satsToQueue * 500));
                            if (isset($this->ctx->budgetManager)) {
                                $this->ctx->budgetManager->tryInternalBucketSwap($pId, 'mines', $neededSatCost);
                            }
                            if (file_exists('includes/classes/bot/Forces/BotPremiumStrategist.class.php')) {
                                require_once 'includes/classes/bot/Forces/BotPremiumStrategist.class.php';
                                BotPremiumStrategist::tryTraderForCategory($this->ctx, $pId, 'mines', $neededSatCost);
                            }
                            $freshRow = Database::get()->selectSingle("SELECT * FROM %%PLANETS%% WHERE id = :pid;", array(':pid' => $pId));
                            if (!empty($freshRow)) {
                                $planetEntry['data'] = $freshRow;
                                $pData = $freshRow;
                                if (isset($this->ctx->empire) && method_exists($this->ctx->empire, 'updateBodyData')) {
                                    $this->ctx->empire->updateBodyData($pId, array(
                                        'metal'     => (float)$freshRow['metal'],
                                        'crystal'   => (float)$freshRow['crystal'],
                                        'deuterium' => (float)$freshRow['deuterium'],
                                    ));
                                }
                            }
                        }

                        $maxCanSats = BuildFunctions::getMaxConstructibleElements($this->ctx->user, $pData, 212);
                        $batchSats = min($satsToQueue, $maxCanSats);
                        if ($batchSats > 0) {
                            $this->gateway->constructUnits($pId, 212, $batchSats, 'Cubrir déficit energético / Push Gravitón');
                        }
                    }
                }
            }

            // Comprehensive NovaRush Buildings (including 6 University):
            $buildIds = array(1, 2, 3, 81, 82, 83, 84, 4, 12, 14, 15, 21, 31, 6, 33, 44, 22, 23, 24);

            // Pre-calculate Terraformer cost and storage constraints for proactive urbanism
            $curTerra = (int)($pData[$resource[33]] ?? 0);
            $nextTerraCost = BuildFunctions::getElementPrice($this->ctx->user, $pData, 33, false, $curTerra + 1);
            $nextTerra2Cost = BuildFunctions::getElementPrice($this->ctx->user, $pData, 33, false, $curTerra + 2);
            $costC = (float)($nextTerraCost[902] ?? $nextTerraCost['crystal'] ?? 0.0);
            $costD = (float)($nextTerraCost[903] ?? $nextTerraCost['deuterium'] ?? 0.0);
            $costC2 = (float)($nextTerra2Cost[902] ?? $nextTerra2Cost['crystal'] ?? 0.0);
            $costD2 = (float)($nextTerra2Cost[903] ?? $nextTerra2Cost['deuterium'] ?? 0.0);
            $maxC = (float)($pData['crystal_max'] ?? 0.0);
            $maxD = (float)($pData['deuterium_max'] ?? 0.0);
            
            // Proactive 2-level forward check when $freeFields <= 6
            $terraStorageConstrained = ($freeFields <= 6)
                ? ($costC > 0.85 * $maxC || $costD > 0.85 * $maxD || $costC2 > 0.85 * $maxC || $costD2 > 0.85 * $maxD)
                : ($costC > 0.85 * $maxC || $costD > 0.85 * $maxD);

            // Guardia Obligatoria de Infraestructura Militar en Colonias
            $curHangar = (int)($pData[$resource[21]] ?? 0);
            $curRobots = (int)($pData[$resource[14]] ?? 0);
            $curNano   = (int)($pData[$resource[15]] ?? 0);
            $curMilMod = (int)($pData[$resource[83]] ?? 0);

            // --- 2. Resolution of 0-Field Circular Deadlocks & Military Infrastructure Guard ---
            if ($freeFields <= 0) {
                if ($terraStorageConstrained) {
                    $buildIds = array(33, 23, 24);
                } else {
                    $buildIds = array(33);
                }

                $isAccessible = BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 33, array());
                $canHoldCost = ($maxC >= $costC && $maxD >= $costD);

                if (!$isAccessible || !$canHoldCost) {
                    $solarLvl = (int)($pData[$resource[4]] ?? 0);
                    if ($solarLvl > 0 && ($energy > 500000.0 || ($energy - $energyUsed) > 500000.0)) {
                        $dismantled = $this->gateway->dismantleBuilding($pId, 4, 'Resolución de bloqueo circular: liberar 1 campo para almacenes/terraformer');
                        if ($dismantled) {
                            $this->ctx->decisionLog->record(
                                'mine_dismantle',
                                "Dismantled 1 level of Solar Plant #4 on planet #{$pId} to break 0-field deadlock",
                                array('planet_id' => $pId, 'energy' => $energy),
                                "dismantle_4_planet_{$pId}",
                                25000.0
                            );
                            $freeFields = 1;
                        }
                    }
                    if ($freeFields <= 0 && !$terraStorageConstrained) {
                        continue;
                    }
                }
            } elseif ($freeFields <= 2 && ($curHangar < 8 || $curRobots < 8)) {
                $dismantleTarget = 0;
                if ((int)($pData[$resource[22]] ?? 0) > 1) {
                    $dismantleTarget = 22;
                } elseif ((int)($pData[$resource[23]] ?? 0) > 1) {
                    $dismantleTarget = 23;
                } elseif ((int)($pData[$resource[24]] ?? 0) > 1) {
                    $dismantleTarget = 24;
                } elseif ((int)($pData[$resource[4]] ?? 0) > 1 && (int)($pData[$resource[12]] ?? 0) >= 5) {
                    $dismantleTarget = 4;
                }

                if ($dismantleTarget > 0) {
                    $dismantled = $this->gateway->dismantleBuilding($pId, $dismantleTarget, 'Liberar campos para Hangar y Fábrica de Robots');
                    if ($dismantled) {
                        $this->ctx->decisionLog->record(
                            'mine_dismantle',
                            "Dismantled 1 level of building #{$dismantleTarget} on planet #{$pId} to ensure military hangar/robots infrastructure",
                            array('planet_id' => $pId, 'target' => $dismantleTarget),
                            "dismantle_{$dismantleTarget}_planet_{$pId}",
                            28000.0
                        );
                        $freeFields++;
                    }
                }
                $buildIds = array(21, 33, 14, 23, 24);
            } elseif ($freeFields <= 18 && ((string)($pData['planet_type'] ?? '1') === '1')) {
                // Whitelist en últimos 18 campos: mantiene Minas (1, 2, 3), Energía (4, 12), infraestructura esencial y solo #81 + 2º módulo ($m2)
                $buildIds = array(1, 2, 3, 4, 12, 33, 14, 15, 21, 22, 23, 24, 31, 81, $m2);
            }

            $lvlMetalMine = (int)($projData[$resource[1]] ?? 0);
            $lvlCryMine   = (int)($projData[$resource[2]] ?? 0);
            $lvlDeutMine  = (int)($projData[$resource[3]] ?? 0);
            $metalMineNextCost = BuildFunctions::getElementPrice($this->ctx->user, $projData, 1, false);
            $metalMineNextCostMSE = max(100.0, BotEconomyValuator::arrayToMSE($metalMineNextCost));
            $fobPlanetId = isset($this->ctx->botRow['fob_planet_id']) ? (int)$this->ctx->botRow['fob_planet_id'] : (int)($this->ctx->botRow['home_planet_id'] ?? 0);
            $isPrimaryMilitaryPlanet = ($pId === $fobPlanetId || $pId === (int)($this->ctx->botRow['home_planet_id'] ?? 0));
            $planetHourlyMSE = (float)($planetEntry['hourly_mse'] ?? 0.0);

            $mandatoryBuildingId = 0;
            if ($energyNet >= 0.0 && $freeFields > 0) {
                $mandatoryBuildingId = $this->getMandatoryMilestoneBuilding($projData, $isTopLabPlanet, $isBestLab, $hourlyEmpireMSE, $isPrimaryMilitaryPlanet, $planetHourlyMSE, $freeFields, $pData);
                if ($mandatoryBuildingId > 0) {
                    $buildIds = array($mandatoryBuildingId);
                }
            }

            foreach ($buildIds as $mId) {
                if (!BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, $mId, array())) {
                    continue;
                }

                // Regla estricta de personalidad para los módulos #82, #83 y #84
                if ($this->isBalancedOrEconomicPersonality() && in_array($mId, array(82, 83), true)) {
                    continue;
                }
                if (!$this->isBalancedOrEconomicPersonality() && $mId == 84) {
                    continue;
                }

                // Candado Escalonado de Módulos Planetarios (#81, #82, #83, #84)
                if ($mId !== $mandatoryBuildingId && in_array($mId, array(81, 82, 83, 84))) {
                    $lvl1 = (int)($projData[$resource[$m1]] ?? 0);
                    $lvl2 = (int)($projData[$resource[$m2]] ?? 0);
                    $lvl3 = (int)($projData[$resource[$m3]] ?? 0);
                    $lvl4 = (int)($projData[$resource[$m4]] ?? 0);

                    // Requisito de Almacenes Mínimos en Colonias:
                    // Antes de escalar módulos 82, 83, 84 por encima de nivel 4, exigir almacenes mínimos (metal_store >= 6, crystal_store >= 5, deuterium_store >= 5)
                    if (in_array($mId, array(82, 83, 84))) {
                        $curModLvl = (int)($projData[$resource[$mId]] ?? 0);
                        if ($curModLvl >= 4) {
                            $mStoreLvl = (int)($projData['metal_store'] ?? ($projData[$resource[22]] ?? 0));
                            $cStoreLvl = (int)($projData['crystal_store'] ?? ($projData[$resource[23]] ?? 0));
                            $dStoreLvl = (int)($projData['deuterium_store'] ?? ($projData[$resource[24]] ?? 0));
                            if ($mStoreLvl < 6 || $cStoreLvl < 5 || $dStoreLvl < 5) {
                                continue;
                            }
                        }
                    }

                    if ($mId === $m2 && $lvl2 >= max(1, $lvl1 - 2)) {
                        continue;
                    } elseif ($mId === $m3 && $lvl3 >= max(1, $lvl2 - 2)) {
                        continue;
                    } elseif ($mId === $m4) {
                        $lockLvl = ($isBestLab && $m4 === 84) ? max(1, $lvl1 - 2) : max(1, $lvl3 - 2);
                        if ($lvl4 >= $lockLvl) {
                            continue;
                        }
                    }
                }

                // Erradicar déficit de energía: Si $energyNet < 0, prohíbe evaluar o encolar consumidores (#1, #2, #3)
                if ($energyNet < 0.0 && in_array($mId, array(1, 2, 3))) {
                    continue;
                }

                // Desbloquear Almacenes (#23 y #24) antes de agotar campos en P3:
                // Cuando $freeFields <= 6, si el próximo Terraformer supera el 85% de capacidad de #23 o #24,
                // bloquea minas y módulos (#1, #2, #3, #81, #82, #83, #84) en esos últimos 6 campos
                if ($mId !== $mandatoryBuildingId && $freeFields <= 6 && $terraStorageConstrained && in_array($mId, array(1, 2, 3, 81, 82, 83, 84))) {
                    continue;
                }

                // --- 3. Mandatory Reservation of Last Free Field ($freeFields == 1) ---
                if ($mId !== $mandatoryBuildingId && $freeFields == 1 && in_array($mId, array(1, 2, 3))) {
                    $curTerra = (int)($pData[$resource[33]] ?? 0);
                    $terraCost = BuildFunctions::getElementPrice($this->ctx->user, $pData, 33, false, $curTerra + 1);
                    $costC = (float)($terraCost[902] ?? $terraCost['crystal'] ?? 0.0);
                    $costD = (float)($terraCost[903] ?? $terraCost['deuterium'] ?? 0.0);
                    $maxC = (float)($pData['crystal_max'] ?? 0.0);
                    $maxD = (float)($pData['deuterium_max'] ?? 0.0);

                    $storageOk = ($maxC >= $costC && $maxD >= $costD);
                    $nanoLvl = (int)($pData[$resource[15]] ?? 0);
                    $energyTechLvl = (int)($this->ctx->user[$resource[113]] ?? 0);
                    $prereqOk = ($nanoLvl >= 1 && $energyTechLvl >= 12);

                    if (!$storageOk || !$prereqOk) {
                        // Prohibit spending the last free field on common mines! Reserve for stores/nano/terraformer!
                        continue;
                    }
                }

                $curLevel = isset($projData[$resource[$mId]]) ? (int)$projData[$resource[$mId]] : ((int)($pData[$resource[$mId]] ?? 0));
                $nextLevel = $curLevel + 1;
                $cost = BuildFunctions::getElementPrice($this->ctx->user, $projData, $mId, false);

                // Protect tactical fleet deuterium reserve (metal and crystal available for internal bucket swap)
                $tacticalDeutReserve = 25000.0;
                $availPhysM = (float)$pData['metal'];
                $availPhysC = (float)$pData['crystal'];
                $availPhysD = max(0.0, (float)$pData['deuterium'] - $tacticalDeutReserve);

                $costM = (float)($cost[901] ?? $cost['metal'] ?? 0.0);
                $costC = (float)($cost[902] ?? $cost['crystal'] ?? 0.0);
                $costD = (float)($cost[903] ?? $cost['deuterium'] ?? 0.0);

                $canAffordPhysical = ($availPhysM >= $costM && $availPhysC >= $costC && $availPhysD >= $costD);
                $canAffordBudget = (!isset($this->ctx->budgetManager)) || $this->ctx->budgetManager->canAffordBudget($pId, 'mines', $cost);

                // Liquidity reconciliation via internal bucket swap
                if ($canAffordPhysical && !$canAffordBudget && isset($this->ctx->budgetManager)) {
                    $canAffordBudget = $this->ctx->budgetManager->tryInternalBucketSwap($pId, 'mines', $cost);

                }

                $canAfford = $canAffordPhysical && $canAffordBudget;
                $costMSE = BotEconomyValuator::arrayToMSE($cost);
                $costHP  = BotEconomyValuator::toHP($costMSE, $hourlyEmpireMSE);

                $baseScore = ($macroMines * 40.0) / max(0.1, $costHP);

                // --- A. Terraformer (33) Progressive Scaling & Zero-Waste ---
                if ($mId == 33) {
                    if ($freeFields > 10) {
                        continue; // Strictly prohibited to build Terraformer when surplus fields > 10
                    }
                    if ($freeFields <= 1) {
                        $baseScore = 25000.0; // Critical: colony about to deadlock
                    } elseif ($freeFields <= 3) {
                        $baseScore = 12000.0; // High priority
                    } elseif ($freeFields <= 5) {
                        $baseScore = 6000.0;  // Pro-player expansion
                    } else { // $freeFields <= 10
                        $baseScore = 2500.0;
                    }
                }

                // If fields running low (<= 4) and Terraformer is blocked, rush its prerequisites
                if ($freeFields <= 4 && !BuildFunctions::isTechnologieAccessible($this->ctx->user, $pData, 33, array())) {
                    if ($mId == 14 && $curLevel < 10) {
                        $baseScore += 4000.0;
                    } elseif ($mId == 15 && $curLevel < 1) {
                        $baseScore += 6500.0;
                    }
                }

                // --- B. Intelligent Energy (4, 12) with Zero Waste ---
                if ($mId == 4 || $mId == 12) {
                    $isGravitonPush = (empty($this->ctx->user[$resource[199]]) && (int)($pData[$resource[31]] ?? 0) >= 10 && $energy < 300000.0);

                    // Strictly zero waste: if surplus energy covers buffer and no graviton push, skip!
                    if ($mId !== $mandatoryBuildingId && $energyNet >= $desiredEnergyBuffer && !$isGravitonPush) {
                        continue;
                    }

                    // Special condition for Fusion Plant (#12):
                    if ($mId == 12) {
                        $solarLvl = (int)($projData[$resource[4]] ?? 0);
                        $fusionLvl = (int)($projData[$resource[12]] ?? 0);
                        $energyTechLvl = (int)($this->ctx->user[$resource[113]] ?? 0);

                        $isDeficitOrLow = ($energyNet < 0.0 || $energyNet < ($desiredEnergyBuffer * 0.3));
                        $solarDelta = 20.0 * ($solarLvl + 1) * pow(1.1, $solarLvl + 1) - 20.0 * $solarLvl * pow(1.1, $solarLvl);
                        $solarNextCost = BuildFunctions::getElementPrice($this->ctx->user, $projData, 4, false);
                        $solarNextMSE  = max(1.0, BotEconomyValuator::arrayToMSE($solarNextCost));
                        $solarRatio    = $solarDelta / $solarNextMSE;

                        $baseFusionFactor = 1.05 + ($energyTechLvl * 0.01);
                        $fusionDelta = 30.0 * ($fusionLvl + 1) * pow($baseFusionFactor, $fusionLvl + 1) - 30.0 * $fusionLvl * pow($baseFusionFactor, $fusionLvl);
                        $fusionRatio = $fusionDelta / max(1.0, $costMSE);

                        if (!$isDeficitOrLow || ($solarLvl < 25 && $fusionRatio <= $solarRatio && $energyNet >= 0.0)) {
                            continue; // Skip Fusion Plant to preserve Deuterium stock!
                        }
                    }

                    if ($energyNet < 0.0) {
                        $baseScore = 50000.0; // Score absoluto cuando energyNet < 0
                    } else {
                        $baseScore = 1500.0 + (($desiredEnergyBuffer - $energyNet) * 5.0);
                    }
                }

                // --- C. Mines (1, 2, 3) & Resource Module (81) Marginal Payback with Time & Catch-up ---
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

                    if ($mId == 1) {
                        $baseCur  = 30.0 * $curLevel * pow(1.1, $curLevel);
                        $baseNext = 30.0 * $nextLevel * pow(1.1, $nextLevel);
                        $deltaM   = ($baseNext - $baseCur) * (1.0 + $fRes + $fMet) * $resMult;
                    } elseif ($mId == 2) {
                        $baseCur  = 20.0 * $curLevel * pow(1.1, $curLevel);
                        $baseNext = 20.0 * $nextLevel * pow(1.1, $nextLevel);
                        $deltaC   = ($baseNext - $baseCur) * (1.0 + $fRes + $fCry) * $resMult;
                    } elseif ($mId == 3) {
                        $tempFactor = (-0.002 * $temp + 1.28);
                        $baseCur    = 10.0 * $curLevel * pow(1.1, $curLevel) * $tempFactor;
                        $baseNext   = 10.0 * $nextLevel * pow(1.1, $nextLevel) * $tempFactor;
                        $deltaD     = ($baseNext - $baseCur) * (1.0 + $fRes + $fDeu) * $resMult;
                    } elseif ($mId == 81) {
                        $tempFactor = (-0.002 * $temp + 1.28);
                        $deltaM     = (300.0 * $nextLevel * pow(1.1, $nextLevel) - 300.0 * $curLevel * pow(1.1, $curLevel)) * (1.0 + $fRes + $fMet) * $resMult;
                        $deltaC     = (200.0 * $nextLevel * pow(1.1, $nextLevel) - 200.0 * $curLevel * pow(1.1, $curLevel)) * (1.0 + $fRes + $fCry) * $resMult;
                        $deltaD     = (100.0 * $nextLevel * pow(1.1, $nextLevel) - 100.0 * $curLevel * pow(1.1, $curLevel)) * $tempFactor * (1.0 + $fRes + $fDeu) * $resMult;
                    }

                    $deltaMSE = BotEconomyValuator::toMSE($deltaM, $deltaC, $deltaD);
                    $buildTimeSec = BuildFunctions::getBuildingTime($this->ctx->user, $pData, $mId, $cost);
                    $timeFactor = 1.0 / (1.0 + log10(max(1.0, $buildTimeSec / 60.0)));
                    $normDeltaMSE = $deltaMSE / max(1.0, $resMult);
                    $paybackScore = ($normDeltaMSE / max(1.0, $costMSE)) * 120000.0 * $timeFactor;

                    if ($mId == 2) {
                        $idealCry = max(1, $lvlMetalMine - 2);
                        if ($curLevel < $idealCry) {
                            $paybackScore *= (1.0 + min(1.5, ($idealCry - $curLevel) * 0.35));
                        }
                    } elseif ($mId == 3) {
                        $deutOffset = (strpos($personality, 'aggress') !== false || strpos($personality, 'raid') !== false) ? 2 : 4;
                        $idealDeut = max(1, $lvlMetalMine - $deutOffset);
                        if ($curLevel < $idealDeut) {
                            $paybackScore *= (1.0 + min(1.5, ($idealDeut - $curLevel) * 0.35));
                        }
                    } elseif ($mId == 1) {
                        if ($curLevel > ($lvlCryMine + 4) || $curLevel > ($lvlDeutMine + 6)) {
                            $paybackScore *= 0.50;
                        }
                    }

                    // Inter-Colony Catch-Up Multiplier (1.5x to 2.5x)
                    if (isset($mineAverages[$mId]) && $curLevel <= ($mineAverages[$mId] - 3)) {
                        $lag = $mineAverages[$mId] - $curLevel;
                        $catchUpMult = min(2.5, 1.5 + (($lag - 3) * 0.25));
                        $paybackScore *= $catchUpMult;
                    }

                    $baseScore += $paybackScore;
                    if ($mId == 81) {
                        // Resource Module (#81): produces 300M + 200C + 100D, 0 energy consumption!
                        $baseScore += 8500.0 * ($macroMines / 0.35);
                    }
                }

                                // --- D. Astillero (21) y Módulos Especiales (82, 83) con Techo Relativo a Minas ---
                if ($mId == 21) {
                    if ($lvlMetalMine < 10) {
                        $hangarCap = $isPrimaryMilitaryPlanet ? 4 : 2;
                    } elseif ($lvlMetalMine < 16) {
                        $hangarCap = 6;
                    } elseif ($lvlMetalMine < 20) {
                        $hangarCap = 8;
                    } elseif ($lvlMetalMine < 26) {
                        $hangarCap = 10;
                    } else {
                        $hangarCap = min(18, 12 + (int)floor(($lvlMetalMine - 26) / 3));
                    }
                    if ($mId !== $mandatoryBuildingId) {
                        if ($curLevel >= $hangarCap) {
                            continue;
                        }
                        if ($curLevel >= 8 && $costMSE > ($metalMineNextCostMSE * 1.50)) {
                            continue;
                        }
                    }
                    if ($lvlMetalMine >= 18 && $curLevel < 8) {
                        $baseScore += 18000.0;
                    } elseif ($lvlMetalMine >= 26 && $curLevel < 12 && $costHP <= 1.5) {
                        $baseScore += 6000.0;
                    } else {
                        $baseScore += 1200.0 / max(0.2, $costHP);
                    }
                }

                if ($mId == 82 || $mId == 83) {
                    if ($mId !== $mandatoryBuildingId) {
                        if ($lvlMetalMine < 20 || $costMSE > ($metalMineNextCostMSE * 1.0) || $costHP > 1.5) {
                            continue;
                        }
                    }
                    if ($mId == 82) {
                        $baseScore += 900.0 * ($macro['defense'] / 0.15) / max(0.2, $costHP);
                    } else {
                        $baseScore += 1000.0 * ($macro['fleet'] / 0.30) / max(0.2, $costHP);
                    }
                }

                // --- E. Silo de Misiles (44) con Tope Estricto ---
                if ($mId == 44) {
                    if ($mId !== $mandatoryBuildingId) {
                        if ($lvlMetalMine < 20) {
                            continue;
                        }
                        $siloCap = (strpos($personality, 'eco') !== false || strpos($personality, 'min') !== false)
                            ? 4
                            : min(10, max(2, (int)floor(($lvlMetalMine - 16) / 3)));
                        if ($curLevel >= $siloCap || $costMSE > ($metalMineNextCostMSE * 0.60)) {
                            continue;
                        }
                    }
                    $baseScore += ($curLevel < 4) ? 1200.0 : 400.0;
                }

                // --- F. Aceleradores (14, 15, 31, 6, 84) Gobernados por Nivel de Minas y Coste ---
                if ($mId == 14) {
                    $robotsCap = min(10, (int)floor($lvlMetalMine / 2) + 2);
                    $curNano = (int)($pData[$resource[15]] ?? 0);
                    if ($mId !== $mandatoryBuildingId) {
                        if ($curLevel >= 10) {
                            if ($lvlMetalMine < 32 || $curNano < 4 || $costMSE > ($metalMineNextCostMSE * 0.50)) {
                                continue;
                            }
                        } elseif ($curLevel >= $robotsCap && $costMSE > ($metalMineNextCostMSE * 0.80)) {
                            continue;
                        }
                    }
                    if ($curLevel < 10 && $lvlMetalMine >= 18) {
                        $baseScore += 20000.0;
                    } elseif ($curLevel < $robotsCap) {
                        $baseScore += 4500.0 / max(0.2, $costHP);
                    }
                } elseif ($mId == 15) {
                    $nanoCap = max(1, (int)floor(($lvlMetalMine - 16) / 4));
                    if ($mId !== $mandatoryBuildingId) {
                        if ($lvlMetalMine < 18) {
                            continue;
                        }
                        if ($curLevel >= $nanoCap && $costMSE > ($metalMineNextCostMSE * 1.25)) {
                            continue;
                        }
                        if ($curLevel >= 1 && $costHP > 2.5) {
                            continue;
                        }
                    }
                    if ($curLevel < 1) {
                        $baseScore += 28000.0;
                    } elseif ($curLevel < $nanoCap && $costMSE <= ($metalMineNextCostMSE * 1.50)) {
                        $baseScore += 14000.0 / max(0.3, $costHP);
                    } else {
                        $baseScore += 2000.0 / max(0.5, $costHP);
                    }
                }

                // Laboratorio (31), Universidad (6) y Módulo Investigación (84)
                if ($mId == 31 || $mId == 6 || $mId == 84) {
                    if ($mId !== $mandatoryBuildingId && !$isTopLabPlanet) {
                        continue; // Prohibido malgastar campos y recursos en laboratorios fuera de la red IRN
                    }
                    if ($mId == 31) {
                        $labCap = $isBestLab ? ($lvlMetalMine + 1) : ((int)floor($lvlMetalMine * 0.70) + 2);
                        if ($mId !== $mandatoryBuildingId && ($curLevel >= $labCap || $costHP > 2.0)) {
                            continue;
                        }
                        if ($costMSE <= ($metalMineNextCostMSE * 1.25)) {
                            $baseScore += 3500.0 / max(0.2, $costHP);
                        } else {
                            $baseScore += 1200.0 / max(0.5, $costHP);
                        }
                    } elseif ($mId == 6 || $mId == 84) {
                        if ($mId !== $mandatoryBuildingId && ($lvlMetalMine < 24 || $costMSE > ($metalMineNextCostMSE * 1.20) || $costHP > 1.5)) {
                            continue;
                        }
                        $baseScore += ($mId == 6 ? 4000.0 : 2500.0) / max(0.3, $costHP);
                    }
                }

                // --- G. Almacenes (22, 23, 24): Solo por Necesidad Real (>=90% Lleno o Bloqueo de Coste) ---
                if (in_array($mId, array(22, 23, 24))) {
                    $maxM = max(1.0, (float)($pData['metal_max'] ?? 10000.0));
                    $maxC = max(1.0, (float)($pData['crystal_max'] ?? 10000.0));
                    $maxD = max(1.0, (float)($pData['deuterium_max'] ?? 10000.0));

                    $cryMineNextCost  = BuildFunctions::getElementPrice($this->ctx->user, $projData, 2, false);
                    $deutMineNextCost = BuildFunctions::getElementPrice($this->ctx->user, $projData, 3, false);
                    $mod81NextCost    = BuildFunctions::getElementPrice($this->ctx->user, $projData, 81, false);

                    $reqCapM = max((float)($metalMineNextCost[901] ?? 0.0), (float)($cryMineNextCost[901] ?? 0.0), (float)($deutMineNextCost[901] ?? 0.0), (float)($mod81NextCost[901] ?? 0.0));
                    $reqCapC = max((float)($metalMineNextCost[902] ?? 0.0), (float)($cryMineNextCost[902] ?? 0.0), (float)($deutMineNextCost[902] ?? 0.0), (float)($mod81NextCost[902] ?? 0.0));
                    $reqCapD = max((float)($deutMineNextCost[903] ?? 0.0), (float)($mod81NextCost[903] ?? 0.0));

                    $labStorageConstrained = false;
                    if ($pId === $bestLabPlanetId) {
                        $activeLock = (isset($this->ctx->budgetManager)) ? $this->ctx->budgetManager->getSavingsLock() : null;
                        if (!empty($activeLock) && ($activeLock['category'] ?? '') === 'research') {
                            $lockCost = $activeLock['cost'] ?? array();
                            if (($mId == 22 && (float)($lockCost[901] ?? 0) > 0.85 * $maxM) ||
                                ($mId == 23 && (float)($lockCost[902] ?? 0) > 0.85 * $maxC) ||
                                ($mId == 24 && (float)($lockCost[903] ?? 0) > 0.85 * $maxD)) {
                                $labStorageConstrained = true;
                            }
                        }
                    }

                    if ($labStorageConstrained) {
                        $baseScore += 65000.0;
                    } elseif ($freeFields <= 6 && $terraStorageConstrained && ($mId == 23 || $mId == 24)) {
                        $baseScore = 32000.0;
                    } else {
                        $needsStore = false;
                        if ($mId == 22 && ((float)$pData['metal'] >= 0.90 * $maxM || $reqCapM >= 0.90 * $maxM)) {
                            $needsStore = true;
                        } elseif ($mId == 23 && ((float)$pData['crystal'] >= 0.90 * $maxC || $reqCapC >= 0.90 * $maxC)) {
                            $needsStore = true;
                        } elseif ($mId == 24 && ((float)$pData['deuterium'] >= 0.90 * $maxD || $reqCapD >= 0.90 * $maxD)) {
                            $needsStore = true;
                        }
                        if ($mId !== $mandatoryBuildingId) {
                            if (!$needsStore || $freeFields <= 1) {
                                continue;
                            }
                        }
                        $baseScore += 9500.0;
                    }
                }

                // --- Kickstart para nuevas colonias recién fundadas / nivel 0 ---
                $solarLvl = (int)($pData[$resource[4]] ?? 0);
                if ($solarLvl == 0 && $mId == 4) {
                    $baseScore = max($baseScore, 40000.0); // Planta Solar de inmediato en colonia virgen
                } elseif ($solarLvl >= 1) {
                    if ($mId == 1 && (int)($pData[$resource[1]] ?? 0) == 0) {
                        $baseScore = max($baseScore, 35000.0);
                    } elseif ($mId == 2 && (int)($pData[$resource[2]] ?? 0) == 0) {
                        $baseScore = max($baseScore, 30000.0);
                    } elseif ($mId == 3 && (int)($pData[$resource[3]] ?? 0) == 0 && $energyNet > 20) {
                        $baseScore = max($baseScore, 25000.0);
                    }
                }



                // Apply Backward-Chaining Prerequisite Unlock Boost
                if (isset($unlockBoosts[$mId]) && $costHP <= 3.0) {
                    if (in_array($mId, array(31, 6, 84))) {
                        if ($isBestLab) {
                            $baseScore += $unlockBoosts[$mId];
                        }
                    } elseif (in_array($mId, array(21, 44, 82, 83))) {
                        if ($isPrimaryMilitaryPlanet) {
                            $baseScore += $unlockBoosts[$mId];
                        }
                    } else {
                        $baseScore += $unlockBoosts[$mId];
                    }
                }

                // Multiplicadores de Jerarquía de Módulos por Personalidad
                if ($mId === $m1) {
                    $baseScore *= 1.0;
                } elseif ($mId === $m2) {
                    $baseScore *= 0.85;
                } elseif ($mId === $m3) {
                    $baseScore *= 0.50;
                } elseif ($mId === $m4) {
                    $baseScore *= ($isBestLab && $m4 === 84) ? 0.85 : 0.25;
                }

                // Bono temporal de puntuación (+30%) a construcciones que consuman recursos ociosos (>65% MSE)
                $physMSE_M = (float)($pData['metal'] ?? 0.0) * 1.0;
                $physMSE_C = (float)($pData['crystal'] ?? 0.0) * 2.0;
                $physMSE_D = max(0.0, (float)($pData['deuterium'] ?? 0.0) - $tacticalDeutReserve) * 4.0;
                $totPhysMSE = $physMSE_M + $physMSE_C + $physMSE_D;

                if ($totPhysMSE > 10000000.0 && $costMSE > 0.0) {
                    $domRes = null;
                    if (($physMSE_M / $totPhysMSE) > 0.65) $domRes = 901;
                    elseif (($physMSE_C / $totPhysMSE) > 0.65) $domRes = 902;
                    elseif (($physMSE_D / $totPhysMSE) > 0.65) $domRes = 903;

                    if ($domRes !== null) {
                        $costResMSE = 0.0;
                        if ($domRes === 901) $costResMSE = ((float)($cost[901] ?? $cost['metal'] ?? 0.0)) * 1.0;
                        elseif ($domRes === 902) $costResMSE = ((float)($cost[902] ?? $cost['crystal'] ?? 0.0)) * 2.0;
                        elseif ($domRes === 903) $costResMSE = ((float)($cost[903] ?? $cost['deuterium'] ?? 0.0)) * 4.0;

                        if (($costResMSE / $costMSE) > 0.40) {
                            $baseScore *= 1.30;
                        }
                    }
                }

                if ($mandatoryBuildingId > 0 && $mId === $mandatoryBuildingId) {
                    $baseScore = max($baseScore, 95000.0);
                }

                // Apply Personality Multiplier
                $pMult = $persMults['buildings'][$mId] ?? 1.0;
                $finalScore = $baseScore * $pMult;

                $needsTrader = false;
                $tradeInfo = null;
                if (!$canAfford && class_exists('BotPremiumStrategist')) {
                    $tradeInfo = BotPremiumStrategist::canTraderResolveDeficit($this->ctx, $pId, 'mines', $cost);
                    if ($tradeInfo !== false) {
                        $needsTrader = true;
                    }
                }

                if ($canAfford || $needsTrader) {
                    $candidates[] = array(
                        'planet_id'    => $pId,
                        'element'      => $mId,
                        'score'        => round($finalScore, 2),
                        'cost_hp'      => round($costHP, 2),
                        'cost'         => $cost,
                        'needs_trader' => $needsTrader,
                        'trade_info'   => $tradeInfo,
                        'is_moon'      => false,
                    );
                } else {
                    $unaffordableGoals[] = array(
                        'planet_id' => $pId,
                        'element'   => $mId,
                        'score'     => round($finalScore, 2),
                        'cost'      => $cost,
                        'data'      => $pData,
                        'is_moon'   => false,
                    );
                }
            }
        }

        // --- 4. Lunar Urbanism Subsystem ---
        $this->allocateMoonBuildings($empire, $candidates, $unaffordableGoals);

        // --- 5. Parallel Multi-Colony & Lunar Construction Execution ---
        $queued = 0;
        if (!empty($candidates)) {
            usort($candidates, function($a, $b) {
                return $b['score'] <=> $a['score'];
            });

            $queuedBodies = array();

            foreach ($candidates as $cand) {
                $bId = (int)$cand['planet_id'];
                if (isset($queuedBodies[$bId])) {
                    continue; // Colony or moon already queued its top building in this cycle
                }

                if (!empty($cand['needs_trader'])) {
                    $tradeOk = BotPremiumStrategist::tryTraderForCategory($this->ctx, $bId, 'mines', $cand['cost']);
                    if (!$tradeOk) {
                        continue;
                    }
                    $freshRow = Database::get()->selectSingle("SELECT * FROM %%PLANETS%% WHERE id = :pid;", array(':pid' => $bId));
                    if (!empty($freshRow) && isset($this->ctx->empire) && method_exists($this->ctx->empire, 'updateBodyData')) {
                        $this->ctx->empire->updateBodyData($bId, array(
                            'metal'     => (float)$freshRow['metal'],
                            'crystal'   => (float)$freshRow['crystal'],
                            'deuterium' => (float)$freshRow['deuterium'],
                        ));
                    }
                }

                $bodyLabel = !empty($cand['is_moon']) ? "luna #{$bId}" : "planeta #{$bId}";
                $buildReason = "Inversión industrial profesional en {$bodyLabel} (Score: {$cand['score']})";
                $ok = $this->gateway->constructBuilding($bId, $cand['element'], $buildReason);
                if ($ok) {
                    $queued++;
                    $queuedBodies[$bId] = true;
                    $this->ctx->decisionLog->record(
                        'mine_upgrade',
                        "Upgraded {$resource[$cand['element']]} on {$bodyLabel} (Grandmaster Multi-Colony Parallel Execution)",
                        array_slice($candidates, 0, 5),
                        "build_{$cand['element']}_body_{$bId}",
                        $cand['score']
                    );
                }
            }
        }

        // --- 5b. Multi-Slot Forward Enqueueing (Slots 1 .. maxBuildSlots - 1) following Lm Template ---
        $maxBuildSlots = (isset($this->ctx->difficulty) && method_exists($this->ctx->difficulty, 'getMaxBuildQueueSlots'))
            ? $this->ctx->difficulty->getMaxBuildQueueSlots()
            : 3;

        $planets = $empire->getPlanets();
        foreach ($planets as $pId => $planetEntry) {
            $pData = $planetEntry['data'];
            $freeFields = CalculateMaxPlanetFields($pData) - ((int)$pData['field_current'] + count($planetEntry['building_queue'] ?? array()));
            if ($freeFields <= 0) continue;

            $isTopLabPlanet = isset($topLabPlanetMap[$pId]) || empty($empireLabSpeeds);
            $isBestLab = ($pId === $bestLabPlanetId);
            $fobPlanetId = isset($this->ctx->botRow['fob_planet_id']) ? (int)$this->ctx->botRow['fob_planet_id'] : (int)($this->ctx->botRow['home_planet_id'] ?? 0);
            $isPrimaryMilitaryPlanet = ($pId === $fobPlanetId || $pId === (int)($this->ctx->botRow['home_planet_id'] ?? 0));
            $planetHourlyMSE = (float)($planetEntry['data']['metal_perhour'] ?? 0.0)
                + ((float)($planetEntry['data']['crystal_perhour'] ?? 0.0) * 2.0)
                + ((float)($planetEntry['data']['deuterium_perhour'] ?? 0.0) * 4.0);

            while (count($planetEntry['building_queue'] ?? array()) < $maxBuildSlots && $freeFields > 0) {
                $queueCount = count($planetEntry['building_queue'] ?? array());
                if ($queueCount == 0) {
                    break; // Si el Slot 0 no pudo pagarse en el Paso 5, no se pueden programar slots futuros
                }

                $projData = $this->getProjectedPlanetData($pData, $planetEntry['building_queue']);
                $mandatoryBuildingId = $this->getMandatoryMilestoneBuilding(
                    $projData,
                    $isTopLabPlanet,
                    $isBestLab,
                    $hourlyEmpireMSE,
                    $isPrimaryMilitaryPlanet,
                    $planetHourlyMSE,
                    $freeFields,
                    $projData,
                    true // $isFutureQueueSlot = true: orden estricto de plantilla Lm sin desvio por Valvula B
                );

                if ($mandatoryBuildingId <= 0) {
                    break;
                }

                $ok = $this->gateway->constructBuilding(
                    $pId,
                    $mandatoryBuildingId,
                    "Encolado programado multi-slot (" . ($queueCount + 1) . "/{$maxBuildSlots}) segun plantilla Lm"
                );

                if (!$ok) {
                    break;
                }

                $queued++;
                $freshEntry = $empire->getPlanet($pId);
                if (!empty($freshEntry)) {
                    $planetEntry = $freshEntry;
                    $pData = $planetEntry['data'];
                } else {
                    break;
                }
                $freeFields = CalculateMaxPlanetFields($pData) - ((int)$pData['field_current'] + count($planetEntry['building_queue'] ?? array()));
            }
        }

        // --- 6. Engaging Savings Lock (Only within reasonable horizon MAX_SAVINGS_LOCK_ETA) ---
        if (isset($this->ctx->budgetManager) && !empty($unaffordableGoals)) {
            usort($unaffordableGoals, function($a, $b) {
                return $b['score'] <=> $a['score'];
            });

            foreach ($unaffordableGoals as $topGoal) {
                if ($topGoal['score'] <= 0.0) continue;
                $gPlanetId = (int)$topGoal['planet_id'];
                if (isset($queuedBodies[$gPlanetId])) {
                    continue; // Skip colonies that already queued a building in this cycle
                }
                if (!empty($this->ctx->budgetManager->getSavingsLockForCategory($gPlanetId, 'mines'))) {
                    continue;
                }

                $stockMap = array(
                    901 => (float)($topGoal['data']['metal'] ?? 0.0),
                    902 => (float)($topGoal['data']['crystal'] ?? 0.0),
                    903 => (float)($topGoal['data']['deuterium'] ?? 0.0)
                );
                $prodMap = array(
                    901 => (float)($topGoal['data']['metal_perhour'] ?? 0.0),
                    902 => (float)($topGoal['data']['crystal_perhour'] ?? 0.0),
                    903 => (float)($topGoal['data']['deuterium_perhour'] ?? 0.0)
                );
                $eta = $this->ctx->budgetManager->calculateSafeEta($topGoal['cost'], $stockMap, $prodMap);

                // If ETA exceeds MAX_SAVINGS_LOCK_ETA (14400s), check if other colonies have free fields
                if ($eta > self::MAX_SAVINGS_LOCK_ETA) {
                    $hasOtherFreeFields = false;
                    foreach ($planets as $pCheckId => $pCheck) {
                        if ((int)$pCheckId === $gPlanetId) continue;
                        $f = CalculateMaxPlanetFields($pCheck['data']) - ((int)$pCheck['data']['field_current'] + count($pCheck['building_queue']));
                        if ($f > 0) {
                            $hasOtherFreeFields = true;
                            break;
                        }
                    }
                    if ($hasOtherFreeFields) {
                        continue; // Do not freeze the empire for an unreachable goal!
                    }
                }

                $locked = $this->ctx->budgetManager->setSavingsLock(
                    $gPlanetId,
                    'building',
                    $topGoal['element'],
                    $topGoal['cost'],
                    $topGoal['data'],
                    'mines',
                    $empire
                );

                if ($locked) {
                    $this->ctx->decisionLog->record(
                        'savings_lock_engaged',
                        "Engaged savings lock for {$resource[$topGoal['element']]} on body #{$gPlanetId} (ETA: {$eta}s)",
                        array('element' => $topGoal['element'], 'cost' => $topGoal['cost'], 'eta' => $eta),
                        "save_{$topGoal['element']}",
                        $topGoal['score']
                    );
                    break;
                }
            }
        }

        return $queued;
    }

    /**
     * Lunar Urbanism Subsystem: evaluate lunar buildings on all moons
     *
     * @param BotEmpireState $empire
     * @param array &$candidates
     * @param array &$unaffordableGoals
     */
    public function allocateMoonBuildings(BotEmpireState $empire, array &$candidates, array &$unaffordableGoals)
    {
        global $resource, $pricelist;

        $moons = $empire->getMoons();
        if (!is_array($moons) || empty($moons)) {
            return;
        }

        $hourlyEmpireMSE = $empire->getHourlyProductionMSE();
        $persMults = $this->getPersonalityMultipliers();
        $unlockBoosts = $this->computePrerequisiteUnlockBoosts($empire);

        foreach ($moons as $mId => $moonEntry) {
            $mData = $moonEntry['data'];

            // Renombrar orgánicamente si la luna tiene nombre por defecto
            $defaultMoonNames = array('Luna', 'Mond', 'Moon', 'Hauptplanet', 'Colonia');
            global $LNG;
            if (isset($LNG['type_planet_3'])) {
                $defaultMoonNames[] = $LNG['type_planet_3'];
            }
            if (in_array(trim($mData['name']), $defaultMoonNames, true)) {
                if (file_exists('includes/classes/bot/BotNameHelper.class.php')) {
                    require_once 'includes/classes/bot/BotNameHelper.class.php';
                    $newMoonName = BotNameHelper::getRandomMoonName();
                    if ($mData['name'] !== $newMoonName) {
                        Database::get()->update("UPDATE %%PLANETS%% SET name = :name WHERE id = :id;", array(
                            ':name' => $newMoonName,
                            ':id'   => $mId,
                        ));
                        $moonEntry['data']['name'] = $newMoonName;
                        $mData['name'] = $newMoonName;
                    }
                }
            }

            if (!empty($moonEntry['building_queue']) && count($moonEntry['building_queue']) >= 2) {
                continue;
            }

            $freeMoonFields = CalculateMaxPlanetFields($mData) - ((int)$mData['field_current'] + count($moonEntry['building_queue']));

            // Evaluated lunar buildings: 41 Base Lunar, 14 Robotics, 15 Nanobots, 21 Shipyard, 43 Jump Gate, 42 Phalanx
            $moonBuildIds = array(41, 14, 15, 21, 43, 42);

            $curJumpGate = (int)($mData[$resource[43]] ?? 0);
            $curRobots   = (int)($mData[$resource[14]] ?? 0);
            $curNano     = (int)($mData[$resource[15]] ?? 0);
            $curHangar   = (int)($mData[$resource[21]] ?? 0);
            $curPhalanx  = (int)($mData[$resource[42]] ?? 0);

            // Regla de Oro de Campos Lunares (#41 Base Lunar y #43 Salto Cuántico):
            // Si $freeMoonFields <= 1, Base Lunar (#41) y Salto Cuántico (#43) para Nivel 1
            if ($freeMoonFields <= 1) {
                $moonBuildIds = array(41, 43);
            }

            // Geographic Deduplication of Sensor Phalanx (#42): 1 luna radar principal por sistema solar
            $isPrimaryRadarMoon = true;
            foreach ($moons as $otherMId => $otherMEntry) {
                if ($otherMId == $mId) continue;
                $oData = $otherMEntry['data'];
                if ((int)$oData['galaxy'] === (int)$mData['galaxy'] && (int)$oData['system'] === (int)$mData['system']) {
                    $otherPhalanx = (int)($oData[$resource[42]] ?? 0);
                    if ($otherPhalanx > $curPhalanx || ($otherPhalanx === $curPhalanx && $otherMId < $mId)) {
                        $isPrimaryRadarMoon = false;
                        break;
                    }
                }
            }
            $targetPhalanxCap = $isPrimaryRadarMoon ? 8 : 1;

            foreach ($moonBuildIds as $elemId) {
                if ($elemId == 43 && $curJumpGate >= 1) {
                    continue;
                }

                // Tope de Sensor Phalanx: Máximo Nivel 8 en luna principal, Nivel 1 en secundarias del mismo sistema
                if ($elemId == 42 && $curPhalanx >= $targetPhalanxCap) {
                    continue;
                }

                // Tope racional de Base Lunar (#41) y Robots (#14):
                // Si la luna ya tiene Salto Cuántico (#43) y alcanzó su tope asignado de Phalanx, no seguir subiendo si hay campos libres (>= 1)
                if ($curJumpGate >= 1 && $curPhalanx >= $targetPhalanxCap && $freeMoonFields >= 1) {
                    if ($elemId == 41 || $elemId == 14) {
                        continue;
                    }
                }

                // 1. Mientras sprungtor (#43) == 0 en la luna:
                // Tope temporal a Phalanx (#42) en Nivel 6, Hangar (#21) en Nivel 4 y Robots (#14) en Nivel 8
                if ($curJumpGate == 0) {
                    if ($elemId == 42 && $curPhalanx >= 6) {
                        continue;
                    }
                    if ($elemId == 21 && $curHangar >= 4) {
                        continue;
                    }
                    if ($elemId == 14 && $curRobots >= 8) {
                        continue;
                    }
                    // Tope temporal a Base Lunar (#41) en Nivel 8 si ya hay campos libres (>= 2)
                    // para preservar los cientos de millones de cristal para Salto Cuántico (#43)
                    if ($elemId == 41 && (int)($mData[$resource[41]] ?? 0) >= 8 && $freeMoonFields >= 2) {
                        continue;
                    }
                }

                // 2. Prioriza robot_factory (#14) hasta Nivel 5 y nano_factory (#15) a Nivel 1-2
                // antes de subir el Astillero (#21) por encima de Nivel 4
                if ($elemId == 21 && $curHangar >= 4 && ($curRobots < 5 || $curNano < 1)) {
                    continue;
                }

                if (!BuildFunctions::isTechnologieAccessible($this->ctx->user, $mData, $elemId, array())) {
                    continue;
                }

                $cost = BuildFunctions::getElementPrice($this->ctx->user, $mData, $elemId, false);
                $costM = (float)($cost[901] ?? $cost['metal'] ?? 0.0);
                $costC = (float)($cost[902] ?? $cost['crystal'] ?? 0.0);
                $costD = (float)($cost[903] ?? $cost['deuterium'] ?? 0.0);

                $curStockM = (float)($mData['metal'] ?? 0.0);
                $curStockC = (float)($mData['crystal'] ?? 0.0);
                $curStockD = (float)($mData['deuterium'] ?? 0.0);

                $canAffordPhysical = ($curStockM >= $costM && $curStockC >= $costC && $curStockD >= $costD);
                $canAfford = $canAffordPhysical;
                $costMSE = BotEconomyValuator::arrayToMSE($cost);
                $costHP  = BotEconomyValuator::toHP($costMSE, $hourlyEmpireMSE);

                $curLvl = (int)($mData[$resource[$elemId]] ?? 0);
                $score = 1000.0 / max(0.1, $costHP);

                if ($elemId == 41) { // Base Lunar (+3 campos por nivel)
                    if ($freeMoonFields <= 1) {
                        $score = 32000.0; // Score supremo absoluto para garantizar >= 25000 post-multiplicador
                    } elseif ($freeMoonFields <= 3) {
                        $score = 10000.0;
                    } else {
                        $score = 3000.0;
                    }
                } elseif ($elemId == 43) { // Puerta de Salto Cuántico
                    if ($curLvl == 0) {
                        $score = ((int)($mData[$resource[41]] ?? 0) >= 6) ? 80000.0 : 36000.0;
                    } else {
                        if ($costHP < 3.0) {
                            $score = 6000.0;
                        } else {
                            $score = 1500.0;
                        }
                    }
                } elseif ($elemId == 42) { // Sensor Phalanx
                    $isFobMoon = (isset($this->ctx->botRow['fob_planet_id']) && (int)$this->ctx->botRow['fob_planet_id'] === (int)$mId);
                    if ($curLvl < 6) {
                        $score = 7000.0 + ($isFobMoon ? 2000.0 : 0.0);
                    } elseif ($curLvl < 12) {
                        $score = 4500.0 + ($isFobMoon ? 1500.0 : 0.0);
                    } else {
                        $score = 1200.0;
                    }
                } elseif ($elemId == 14) { // Robotics Lunar
                    if ($curLvl < 6) {
                        $score = 16000.0 + (6 - $curLvl) * 500.0; // Rush Nivel 6 para acelerar obras lunares
                    } elseif ($curLvl < 8) {
                        $score = 3500.0 + (8 - $curLvl) * 200.0;
                    } else {
                        $score = 800.0;
                    }
                } elseif ($elemId == 15) { // Nanobots Lunar
                    if ($curLvl < 2) {
                        $score = 12000.0 + (2 - $curLvl) * 2000.0; // Rush Nivel 1-2
                    } else {
                        $score = 1500.0;
                    }
                } elseif ($elemId == 21) { // Astillero Lunar
                    if ($curLvl < 4) {
                        $score = 4000.0 + (4 - $curLvl) * 500.0;
                    } elseif ($curLvl < 8) {
                        $score = 3000.0 + (8 - $curLvl) * 200.0;
                    } else {
                        $score = 800.0;
                    }
                }

                if (isset($unlockBoosts[$elemId])) {
                    $score += $unlockBoosts[$elemId];
                }

                $pMult = $persMults['buildings'][$elemId] ?? 1.0;
                $finalScore = $score * $pMult;

                if ($canAfford) {
                    $candidates[] = array(
                        'planet_id'    => $mId,
                        'element'      => $elemId,
                        'score'        => round($finalScore, 2),
                        'cost_hp'      => round($costHP, 2),
                        'cost'         => $cost,
                        'is_moon'      => true,
                    );
                } else {
                    $unaffordableGoals[] = array(
                        'planet_id' => $mId,
                        'element'   => $elemId,
                        'score'     => round($finalScore, 2),
                        'cost'      => $cost,
                        'data'      => $mData,
                        'is_moon'   => true,
                    );
                }
            }
        }
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

        // Check research queue capacity by difficulty
        $maxTechSlots = (isset($this->ctx->difficulty) && method_exists($this->ctx->difficulty, 'getMaxTechQueueSlots'))
            ? $this->ctx->difficulty->getMaxTechQueueSlots()
            : 3;

        $currentTechQueue = !empty($this->ctx->user['b_tech_queue']) ? @unserialize($this->ctx->user['b_tech_queue']) : array();
        if (!is_array($currentTechQueue)) {
            $currentTechQueue = array();
        }
        $curTechQueueCount = count($currentTechQueue);
        if ($curTechQueueCount == 0 && !empty($this->ctx->user['b_tech_planet'])) {
            $curTechQueueCount = 1;
        }
        if ($curTechQueueCount >= $maxTechSlots) {
            return 0;
        }



        $macro = (isset($this->ctx->difficulty) && method_exists($this->ctx->difficulty, 'getAdjustedBudget'))
            ? $this->ctx->difficulty->getAdjustedBudget($this->ctx->personality)
            : array('fleet' => 0.35, 'defense' => 0.15, 'mines' => 0.30, 'research' => 0.20);
        $macroTech = max(0.05, $macro['research']);

        $candidates = array();
        $unaffordableTechGoals = array();

        $techWeights = array(
            131 => 4.5, // Metal Processing Tech
            132 => 4.5, // Crystal Processing Tech
            133 => 4.2, // Deuterium Processing Tech
            123 => 4.8, // IRN (Intergalactic Research Network)
            113 => 3.8, // Energy Tech
            114 => 3.4, // Hyperspace Tech
            117 => 3.5, // Impulse Drive
            106 => 3.2, // Espionage Tech
            108 => 3.5, // Computer Tech
            109 => 2.8, // Weapons Tech
            110 => 2.8, // Shielding Tech
            111 => 2.8, // Armor Tech
            118 => 3.0, // Hyperspace Drive
            122 => 2.9, // Plasma / Buster Tech
            121 => 2.4, // Ion Tech
            120 => 2.2, // Laser Tech
            124 => 4.0, // Astrophysics
            134 => 2.7, // Fuel Optimization
            115 => 2.2, // Combustion Drive
            199 => 2.0, // Graviton Tech
        );

        $empireHourlyMSE = $empire->getHourlyProductionMSE();
        $planets = $empire->getPlanets();
        if (!is_array($planets) || empty($planets)) {
            return 0;
        }



        $minFreeFieldsEmpire = 999;
        $hasFusion = false;
        $advancedLabs = 0;
        foreach ($planets as $pCheck) {
            $f = CalculateMaxPlanetFields($pCheck['data']) - ((int)$pCheck['data']['field_current'] + count($pCheck['building_queue']));
            if ($f < $minFreeFieldsEmpire) {
                $minFreeFieldsEmpire = $f;
            }
            if (!empty($pCheck['data'][$resource[12]]) && (int)$pCheck['data'][$resource[12]] > 0) {
                $hasFusion = true;
            }
            if (!empty($pCheck['data'][$resource[31]]) && (int)$pCheck['data'][$resource[31]] >= 10) {
                $advancedLabs++;
            }
        }

        // Strategic Multipliers:
        // 1. Computer Tech (108)
        $curComp = (int)($this->ctx->user[$resource[108]] ?? 0);
        $desiredComp = max(12, count($planets) + 4);
        $compBonus = ($curComp < $desiredComp) ? 3500.0 : 0.0;

        // 2. Astrophysics (124)
        $curAstro = (int)($this->ctx->user[$resource[124]] ?? 0);
        $maxColonies = (int)PlayerUtil::maxPlanetCount($this->ctx->user);
        $currentPlanetCount = count($planets);
        $astroMultiplier = 1.35;
        if ($currentPlanetCount < $maxColonies) {
            $astroMultiplier = 3.5; // Tenemos slots o estamos expandiendo activamente
        } else {
            // Cupo colonial lleno: impulsar Astrofísica para abrir el siguiente slot si es rentable
            $firstPlanet = reset($planets);
            $astroCost = BuildFunctions::getElementPrice($this->ctx->user, $firstPlanet['data'], 124, false);
            $astroCostMSE = BotEconomyValuator::arrayToMSE($astroCost);
            $astroHP = BotEconomyValuator::toHP($astroCostMSE, $empireHourlyMSE);
            if ($astroHP <= 14.0) {
                $astroMultiplier = 4.2; // Abrir nuevo slot imperial
            }
        }

        // 3. IRN (123)
        $irnLevel = (int)($this->ctx->user[$resource[123]] ?? 0);
        $labsAt10Plus = 0;
        foreach ($planets as $pEntry) {
            if ((int)($pEntry['data'][$resource[31]] ?? 0) >= 10) {
                $labsAt10Plus++;
            }
        }
        $targetIrnLevel = min(20, max(12, count($planets) - 1));
        $irnMultiplier = 2.0;
        if (($irnLevel < $targetIrnLevel && $labsAt10Plus > (1 + $irnLevel)) || ($irnLevel < 15 && count($planets) >= 16) || $irnLevel < 3) {
            $irnMultiplier = 12.0; // Dynamic coverage of imperial labs up to level 20
        }

        // 4. Energy Tech (113)
        $curEnergyTech = (int)($this->ctx->user[$resource[113]] ?? 0);
        $energyMultiplier = 1.0 + ($hasFusion ? 1.5 : 0.0) + ($minFreeFieldsEmpire <= 5 && $curEnergyTech < 12 ? 2.5 : 0.0);

        // Backward-Chaining Prerequisite Unlock Boosts
        $unlockBoosts = $this->computePrerequisiteUnlockBoosts($empire);

        // Desbloqueo Dirigido del Árbol del Colonizador (#208) en Early-Game:
        // Requisitos: 113 >= 1 (Energía), 111 >= 4 (Blindaje), 117 >= 4 (Impulso), 109 >= 2 (Militar), 110 >= 2 (Defensa)
        $homePlanetEntry = $empire->getPlanet($this->ctx->botRow['home_planet_id'] ?? 0);
        $homePlanetData = !empty($homePlanetEntry) ? $homePlanetEntry['data'] : (!empty($planets) ? reset($planets)['data'] : array());
        $isColonizerUnlocked = BuildFunctions::isTechnologieAccessible($this->ctx->user, $homePlanetData, 208, array());
        $maxLegalPlanets = class_exists('PlayerUtil') ? (int)PlayerUtil::maxPlanetCount($this->ctx->user) : 9;

        if (!$isColonizerUnlocked && $currentPlanetCount < min(9, $maxLegalPlanets)) {
            $cur113 = (int)($this->ctx->user[$resource[113]] ?? 0);
            $cur111 = (int)($this->ctx->user[$resource[111]] ?? 0);
            $cur117 = (int)($this->ctx->user[$resource[117]] ?? 0);
            $cur109 = (int)($this->ctx->user[$resource[109]] ?? 0);
            $cur110 = (int)($this->ctx->user[$resource[110]] ?? 0);

            if ($cur113 < 1) {
                $unlockBoosts[113] = max($unlockBoosts[113] ?? 0.0, 35000.0);
            }
            if ($cur111 < 4) {
                $unlockBoosts[111] = max($unlockBoosts[111] ?? 0.0, 32000.0);
            }
            if ($cur117 < 4) {
                $unlockBoosts[117] = max($unlockBoosts[117] ?? 0.0, 30000.0);
            }
            if ($cur109 < 2) {
                $unlockBoosts[109] = max($unlockBoosts[109] ?? 0.0, 28000.0);
            }
            if ($cur110 < 2) {
                if ($cur113 < 3) {
                    $unlockBoosts[113] = max($unlockBoosts[113] ?? 0.0, 35000.0);
                }
                $unlockBoosts[110] = max($unlockBoosts[110] ?? 0.0, 26000.0);
            }
        }

        // Astro (124) & Espionage (106) Directed Early Unlock Rule:
        // Astro requires Laboratory 3, Impulse Drive 3, and Espionage Tech 4.
        $curAstro = (int)($this->ctx->user[$resource[124]] ?? 0);
        $curEsp   = (int)($this->ctx->user[$resource[106]] ?? 0);
        if ($curAstro === 0 && $currentPlanetCount >= 3) {
            if ($curEsp < 4) {
                $unlockBoosts[106] = max($unlockBoosts[106] ?? 0.0, 32000.0);
            }
            $unlockBoosts[124] = max($unlockBoosts[124] ?? 0.0, 35000.0);
        }

        // Graviton Tech (199) Strategic Priority Rule:
        // $requeriments[41] requires graviton_tech >= 1 for Lunar Base (#41).
        $curGrav = (int)($this->ctx->user[$resource[199]] ?? 0);
        $botMoons = $empire->getMoons();
        if ($curGrav === 0 && (!empty($botMoons) || $currentPlanetCount >= 6)) {
            $unlockBoosts[199] = 65000.0;
        }

        // Jump Gate (#43) Strategic Priority Rule:
        // Jump Gate requires hyperspace_tech (114) >= 7 and Lunar Base (41) >= 1.
        // Once the bot has moons, rush Hyperspace Tech (114) to Level 7
        if (!empty($botMoons)) {
            $curHyper = (int)($this->ctx->user[$resource[114]] ?? 0);
            if ($curHyper < 7) {
                $curEnergy = (int)($this->ctx->user[$resource[113]] ?? 0);
                $curShield = (int)($this->ctx->user[$resource[110]] ?? 0);
                if ($curEnergy < 5) {
                    $unlockBoosts[113] = max($unlockBoosts[113] ?? 0.0, 42000.0);
                }
                if ($curShield < 5) {
                    $unlockBoosts[110] = max($unlockBoosts[110] ?? 0.0, 42000.0);
                }
                $unlockBoosts[114] = max($unlockBoosts[114] ?? 0.0, 55000.0);
            }
        }

        $persMults = $this->getPersonalityMultipliers();

        // Rank lab colonies by effective research speed (Lab level + University #6 bonus + Research Module #84)
        $rankedLabPlanets = array();
        $maxLabLvl = 0;
        $planets = $empire->getPlanets();
        foreach ($planets as $pId => $planetEntry) {
            $pData = $planetEntry['data'];
            $labLvl = (int)($pData[$resource[31]] ?? 0);
            if ($labLvl > $maxLabLvl) {
                $maxLabLvl = $labLvl;
            }
        }
        foreach ($planets as $pId => $planetEntry) {
            $pData = $planetEntry['data'];
            $labLvl = (int)($pData[$resource[31]] ?? 0);
            if ($labLvl <= 0) continue;

            $effectiveSpeed = $labLvl * 10;
            if (!empty($pData[$resource[6]])) {
                $effectiveSpeed += 50 + ((int)$pData[$resource[6]] * 5); // University bonus
            }
            if (!empty($pData[$resource[84]])) {
                $effectiveSpeed += 10 + ((int)$pData[$resource[84]] * 2); // Module 84 bonus
            }
            $storeSum = (int)($pData[$resource[22]] ?? 0) + (int)($pData[$resource[23]] ?? 0) + (int)($pData[$resource[24]] ?? 0);
            $freeF = CalculateMaxPlanetFields($pData) - ((int)$pData['field_current'] + count($planetEntry['building_queue']));

            $rankedLabPlanets[$pId] = array(
                'planet_id'       => $pId,
                'data'            => $pData,
                'lab_level'       => $labLvl,
                'effective_speed' => $effectiveSpeed,
                'store_sum'       => $storeSum,
                'free_fields'     => $freeF,
            );
        }

        if (empty($rankedLabPlanets)) {
            return 0;
        }

        usort($rankedLabPlanets, function($a, $b) use ($maxLabLvl) {
            // If labs are within 1 level of each other near the top, tie-break by warehouse capacity and free fields
            if ($a['lab_level'] >= ($maxLabLvl - 1) && $b['lab_level'] >= ($maxLabLvl - 1)) {
                $scoreA = ($a['effective_speed'] * 1000) + ($a['store_sum'] * 100) + $a['free_fields'];
                $scoreB = ($b['effective_speed'] * 1000) + ($b['store_sum'] * 100) + $b['free_fields'];
                return $scoreB <=> $scoreA;
            }
            return $b['effective_speed'] <=> $a['effective_speed'];
        });

        $bestLabPlanet = reset($rankedLabPlanets);

        // Designated Graviton Energy Hub: planet with lab >= 12 (or highest lab >= 10) and highest energy / satellites built
        $curGrav = (int)($this->ctx->user[$resource[199]] ?? 0);
        $gravitonHubPlanetId = 0;
        $bestHubScore = -1.0;
        $hasLab12InEmpire = false;
        foreach ($planets as $pCandEntry) {
            if ((int)($pCandEntry['data'][$resource[31]] ?? 0) >= 12) {
                $hasLab12InEmpire = true;
                break;
            }
        }
        foreach ($planets as $pCandId => $pCandEntry) {
            $pCandData = $pCandEntry['data'];
            $candLab = (int)($pCandData[$resource[31]] ?? 0);
            $candHangar = (int)($pCandData[$resource[21]] ?? 0);
            $candEnergy = (float)($pCandData['energy'] ?? 0.0);
            $labEligible = $hasLab12InEmpire ? ($candLab >= 12) : ($candLab >= 10);
            if ($labEligible && ($candHangar >= 8 || $curGrav === 0 || $candEnergy >= 300000.0)) {
                $satE = max(1.0, (($pCandData['temp_max'] + 160) / 6.0));
                $existingSats = (int)($pCandData[$resource[212]] ?? 0);
                $hubScore = $candEnergy + ($existingSats * $satE) + ($satE * 500.0) + ($candLab * 100000.0);
                if ($hubScore > $bestHubScore) {
                    $bestHubScore = $hubScore;
                    $gravitonHubPlanetId = (int)$pCandId;
                }
            }
        }
        if ($gravitonHubPlanetId === 0 && !empty($rankedLabPlanets)) {
            $firstLab = reset($rankedLabPlanets);
            $gravitonHubPlanetId = (int)$firstLab['planet_id'];
        }

        

        $mineAverages = array(1 => 0.0);
        foreach ($planets as $pE) {
            $mineAverages[1] += (float)($pE['data'][$resource[1]] ?? 0);
        }
        $mineAverages[1] /= max(1, count($planets));

        $maxGravitonLvl = 7;

        $gravHubData = ($gravitonHubPlanetId > 0 && isset($planets[$gravitonHubPlanetId]))
            ? $planets[$gravitonHubPlanetId]['data']
            : null;

        // Si ya hay una orden en Slot 0 corriendo, llenar directamente huecos 1 .. maxTechSlots - 1
        if ($curTechQueueCount > 0) {
            $queued = 0;
            while ($curTechQueueCount < $maxTechSlots) {
                $nextTechId = $this->getMandatoryResearchTech($empire, $bestLabPlanet['data'], $gravHubData, true);
                if ($nextTechId <= 0) {
                    break;
                }
                $targetPlanetId = ($nextTechId == 199 && $gravitonHubPlanetId > 0) ? $gravitonHubPlanetId : $bestLabPlanet['planet_id'];
                $ok = $this->gateway->researchTech($targetPlanetId, $nextTechId, "Encolado programado multi-slot (" . ($curTechQueueCount + 1) . "/{$maxTechSlots}) segun plantilla Lm");
                if (!$ok) {
                    break;
                }
                $curTechQueueCount++;
                $queued++;
            }
            return $queued;
        }
        $mandatoryTechId = $this->getMandatoryResearchTech($empire, $bestLabPlanet['data'], $gravHubData);

        // Score all technologies
        foreach ($techWeights as $techId => $baseWeight) {
            if ($techId == 199 && $curGrav >= $maxGravitonLvl) {
                continue;
            }

            $evalPlanet = ($techId == 199 && $gravitonHubPlanetId > 0 && isset($planets[$gravitonHubPlanetId]))
                ? array('planet_id' => $gravitonHubPlanetId, 'data' => $planets[$gravitonHubPlanetId]['data'])
                : $bestLabPlanet;

            if (!BuildFunctions::isTechnologieAccessible($this->ctx->user, $evalPlanet['data'], $techId, array())) {
                continue;
            }

            $cost = BuildFunctions::getElementPrice($this->ctx->user, $evalPlanet['data'], $techId, false);
            $costMSE = BotEconomyValuator::arrayToMSE($cost);
            $costHP  = BotEconomyValuator::toHP($costMSE, $empireHourlyMSE);

            $curTechLvl = (int)($this->ctx->user[$resource[$techId]] ?? 0);

            // 1. Poda de ramas de armamento / soporte que ya cumplieron su llave tecnológica
            if ($techId !== $mandatoryTechId && $techId == 120 && $curTechLvl >= 12 && $mineAverages[1] < 30 && !isset($unlockBoosts[120])) {
                continue; // Láser 12 ya desbloquea Plasma y Acorazado
            }
            if ($techId !== $mandatoryTechId && $techId == 121 && $curTechLvl >= 5 && $mineAverages[1] < 30 && !isset($unlockBoosts[121])) {
                continue; // Iones 5 ya desbloquea Plasma
            }
            if ($techId == 114 && $curTechLvl >= 8 && !isset($unlockBoosts[114])) {
                $baseWeight *= 0.35; // Hiperespacio 8 ya desbloquea Salto Cuántico e IRN
            }

            // 2. Techo de esfuerzo económico en Horas de Producción del Imperio (costHP)
            $maxAllowedCostHP = in_array($techId, array(123, 124, 199), true) ? 6.0 : 3.5;
            if ($curTechLvl === 0 && $costMSE <= 50000.0) {
                $maxAllowedCostHP = 6.0;
            }
            if ($mandatoryTechId > 0 && $techId === $mandatoryTechId) {
                $maxAllowedCostHP = 24.0;
            }
            if ($costHP > $maxAllowedCostHP) {
                continue; // Investigación demasiado cara para la producción actual del imperio
            }

            $diffTechFactor = ($macroTech / 0.20);
            $score = ($baseWeight * $diffTechFactor * 20.0) / max(0.1, $costHP);

            if ($mandatoryTechId > 0 && $techId === $mandatoryTechId) {
                $score += 250000.0;
            }

            // Graviton (199) - Staged priority boost by imperial economic maturity up to Level 7
            if ($techId == 199) {
                $targetGrav = $curGrav + 1;
                $canResearchGrav = false;
                $gravBoost = 0.0;
                $botMoonsCount = count($botMoons);
                $planetCount = count($empire->getPlanets());

                if ($targetGrav === 1 && ($botMoonsCount >= 1 || $planetCount >= 6)) {
                    $canResearchGrav = true;
                    $gravBoost = 95000.0;
                } elseif ($targetGrav <= 3 && $empireHourlyMSE >= 250000000 && $planetCount >= 10) {
                    $canResearchGrav = true;
                    $gravBoost = 95000.0;
                } elseif ($targetGrav <= 5 && $empireHourlyMSE >= 900000000 && $planetCount >= 14) {
                    $canResearchGrav = true;
                    $gravBoost = 95000.0;
                } elseif ($targetGrav === 6 && $empireHourlyMSE >= 1400000000 && $planetCount >= 16) {
                    $canResearchGrav = true;
                    $gravBoost = 95000.0;
                } elseif ($targetGrav === 7 && $empireHourlyMSE >= 2100000000 && $planetCount >= 16) {
                    $canResearchGrav = true;
                    $gravBoost = 95000.0;
                }

                if ($canResearchGrav) {
                    $score += $gravBoost;
                } else {
                    continue;
                }
            }

            // Processing Techs (131, 132, 133)
            if (in_array($techId, array(131, 132, 133))) {
                $gainMSE = $empireHourlyMSE * 0.02;
                $score += min(3500.0, ($gainMSE / max(1.0, $costMSE)) * 1200.0);
            }

            // Energy (113)
            if ($techId == 113) {
                if ($bestLabPlanet['data']['energy'] < 500) {
                    $score += 1500.0;
                }
                if ($minFreeFieldsEmpire <= 5 && $curEnergyTech < 12) {
                    $score += 5000.0 + (12 - $curEnergyTech) * 500.0;
                }
            }

            // Strategic domain multipliers
            if ($techId == 108) {
                $score += $compBonus;
            } elseif ($techId == 124) {
                $score *= $astroMultiplier;
            } elseif ($techId == 123) {
                $score *= $irnMultiplier;
            } elseif ($techId == 113) {
                $score *= $energyMultiplier;
            }

            // Combat Triad, Drives & Fuel: organic cost leveling
            if (in_array($techId, array(109, 110, 111, 114, 115, 117, 118, 134, 120, 121, 122))) {
                $score += ($baseWeight * 15.0) / max(0.5, $costMSE / max(100.0, $empireHourlyMSE));
            }

            // Apply Backward-Chaining Prerequisite Unlock Boost
            if (isset($unlockBoosts[$techId])) {
                $score += $unlockBoosts[$techId];
                $curTechLvl = (int)($this->ctx->user[$resource[$techId]] ?? 0);
                if ($curTechLvl == 0) {
                    $score += 15000.0; // Priority unblock for level-0 root prerequisites
                }
            }

            // Bono temporal de puntuación (+30%) a investigaciones que consuman recursos ociosos (>65% MSE) en el planeta evaluado
            $evalData = $evalPlanet['data'] ?? $bestLabPlanet['data'];
            $evalPhysM = (float)($evalData['metal'] ?? 0.0) * 1.0;
            $evalPhysC = (float)($evalData['crystal'] ?? 0.0) * 2.0;
            $evalPhysD = max(0.0, (float)($evalData['deuterium'] ?? 0.0) - 25000.0) * 4.0;
            $evalTotMSE = $evalPhysM + $evalPhysC + $evalPhysD;

            if ($evalTotMSE > 10000000.0 && $costMSE > 0.0) {
                $domRes = null;
                if (($evalPhysM / $evalTotMSE) > 0.65) $domRes = 901;
                elseif (($evalPhysC / $evalTotMSE) > 0.65) $domRes = 902;
                elseif (($evalPhysD / $evalTotMSE) > 0.65) $domRes = 903;

                if ($domRes !== null) {
                    $costResMSE = 0.0;
                    if ($domRes === 901) $costResMSE = ((float)($cost[901] ?? $cost['metal'] ?? 0.0)) * 1.0;
                    elseif ($domRes === 902) $costResMSE = ((float)($cost[902] ?? $cost['crystal'] ?? 0.0)) * 2.0;
                    elseif ($domRes === 903) $costResMSE = ((float)($cost[903] ?? $cost['deuterium'] ?? 0.0)) * 4.0;

                    if (($costResMSE / $costMSE) > 0.40) {
                        $score *= 1.30;
                    }
                }
            }

            // Apply Personality Multiplier
            $persMult = $persMults['techs'][$techId] ?? 1.0;
            $finalScore = $score * $persMult;

            // Check affordability on lab planets
            $techCandidate = array(
                'tech_id'     => $techId,
                'score'       => round($finalScore, 2),
                'cost'        => $cost,
                'cost_mse'    => $costMSE,
            );

            // Check if eval planet can afford (evalPlanet is gravitonHubPlanetId for Graviton 199, bestLabPlanet for other techs)
            $pId = $evalPlanet['planet_id'] ?? $bestLabPlanet['planet_id'];
            $pData = $evalPlanet['data'] ?? $bestLabPlanet['data'];

            // Protect tactical fleet deuterium reserve (metal and crystal available for research bucket swap)
            $tacticalDeutReserve = 25000.0;
            $availPhysM = (float)$pData['metal'];
            $availPhysC = (float)$pData['crystal'];
            $availPhysD = max(0.0, (float)$pData['deuterium'] - $tacticalDeutReserve);

            $costM = (float)($cost[901] ?? $cost['metal'] ?? 0.0);
            $costC = (float)($cost[902] ?? $cost['crystal'] ?? 0.0);
            $costD = (float)($cost[903] ?? $cost['deuterium'] ?? 0.0);

            $canAffordPhysical = ($availPhysM >= $costM && $availPhysC >= $costC && $availPhysD >= $costD);
            if (!$canAffordPhysical && in_array((int)$techId, array(123, 199), true)) {
                if (file_exists('includes/classes/bot/Forces/BotPremiumStrategist.class.php')) {
                    require_once 'includes/classes/bot/Forces/BotPremiumStrategist.class.php';
                    $tradeOk = BotPremiumStrategist::tryTraderForCategory($this->ctx, $pId, 'research', $cost);
                    if ($tradeOk) {
                        $freshRow = Database::get()->selectSingle("SELECT * FROM %%PLANETS%% WHERE id = :pid;", array(':pid' => $pId));
                        if (!empty($freshRow)) {
                            $pData = $freshRow;
                            $evalPlanet['data'] = $freshRow;
                            $availPhysM = (float)$pData['metal'];
                            $availPhysC = (float)$pData['crystal'];
                            $availPhysD = max(0.0, (float)$pData['deuterium'] - $tacticalDeutReserve);
                            $canAffordPhysical = ($availPhysM >= $costM && $availPhysC >= $costC && $availPhysD >= $costD);
                            if (isset($this->ctx->empire) && method_exists($this->ctx->empire, 'updateBodyData')) {
                                $this->ctx->empire->updateBodyData($pId, array(
                                    'metal'     => (float)$freshRow['metal'],
                                    'crystal'   => (float)$freshRow['crystal'],
                                    'deuterium' => (float)$freshRow['deuterium'],
                                ));
                            }
                        }
                    }
                }
            }

            $canAffordBudget = (!isset($this->ctx->budgetManager)) || $this->ctx->budgetManager->canAffordBudget($pId, 'research', $cost);

            if ($canAffordPhysical && !$canAffordBudget && isset($this->ctx->budgetManager)) {
                $canAffordBudget = $this->ctx->budgetManager->tryInternalBucketSwap($pId, 'research', $cost);
            }

            if ($canAffordPhysical && $canAffordBudget) {
                $techCandidate['planet_id'] = $pId;
                $candidates[] = $techCandidate;
            } else {
                // Check if any other high-speed lab planet can afford this tech right now
                $altPlanetFound = false;
                foreach ($rankedLabPlanets as $altLab) {
                    if ($altLab['planet_id'] === $pId) continue;
                    $altPData = $altLab['data'];
                    $altId = $altLab['planet_id'];

                    if (!BuildFunctions::isTechnologieAccessible($this->ctx->user, $altPData, $techId, array())) {
                        continue;
                    }

                    $altAvailM = (float)$altPData['metal'];
                    $altAvailC = (float)$altPData['crystal'];
                    $altAvailD = max(0.0, (float)$altPData['deuterium'] - $tacticalDeutReserve);

                    if ($altAvailM >= $costM && $altAvailC >= $costC && $altAvailD >= $costD) {
                        $altBudget = (!isset($this->ctx->budgetManager)) || $this->ctx->budgetManager->canAffordBudget($altId, 'research', $cost);
                        if (!$altBudget && isset($this->ctx->budgetManager)) {
                            $altBudget = $this->ctx->budgetManager->tryInternalBucketSwap($altId, 'research', $cost);
                        }
                        if ($altBudget) {
                            $techCandidate['planet_id'] = $altId;
                            $candidates[] = $techCandidate;
                            $altPlanetFound = true;
                            break;
                        }
                    }
                }

                if (!$altPlanetFound) {
                    $techCandidate['planet_id'] = $pId;
                    $techCandidate['data'] = $pData;
                    $unaffordableTechGoals[] = $techCandidate;
                }
            }
        }

        $queued = 0;
        if (!empty($candidates)) {
            usort($candidates, function($a, $b) {
                return $b['score'] <=> $a['score'];
            });

            foreach ($candidates as $cand) {
                $targetPlanetId = ($cand['tech_id'] == 199 && $gravitonHubPlanetId > 0) ? $gravitonHubPlanetId : $cand['planet_id'];
                $techReason = "Prioridad de investigación imperial Grandmaster (Score: {$cand['score']})";
                $ok = $this->gateway->researchTech($targetPlanetId, $cand['tech_id'], $techReason);
                if ($ok) {
                    if (isset($this->ctx->budgetManager)) {
                        $activeLock = $this->ctx->budgetManager->getSavingsLockForCategory(0, 'research');
                        if (!empty($activeLock) && (int)($activeLock['element_id'] ?? 0) === (int)$cand['tech_id']) {
                            $this->ctx->budgetManager->clearSavingsLock('research');
                        }
                    }
                    $this->ctx->decisionLog->record(
                        'research_enqueued',
                        "Queued research {$resource[$cand['tech_id']]} on planet #{$targetPlanetId} (Grandmaster Intelligence)",
                        array_slice($candidates, 0, 5),
                        "research_{$cand['tech_id']}",
                        $cand['score']
                    );
                    $queued++;
                    $curTechQueueCount++;
                    break;
                }
            }
        }

        // Si se encolo Slot 0 con exito, llenar huecos restantes 1 .. maxTechSlots - 1
        if ($curTechQueueCount > 0 && $curTechQueueCount < $maxTechSlots) {
            $gravHubData = ($gravitonHubPlanetId > 0 && isset($planets[$gravitonHubPlanetId])) ? $planets[$gravitonHubPlanetId]['data'] : null;
            while ($curTechQueueCount < $maxTechSlots) {
                $nextTechId = $this->getMandatoryResearchTech($empire, $bestLabPlanet['data'], $gravHubData, true);
                if ($nextTechId <= 0) {
                    break;
                }
                $targetPlanetId = ($nextTechId == 199 && $gravitonHubPlanetId > 0) ? $gravitonHubPlanetId : $bestLabPlanet['planet_id'];
                $ok = $this->gateway->researchTech($targetPlanetId, $nextTechId, "Encolado programado multi-slot (" . ($curTechQueueCount + 1) . "/{$maxTechSlots}) segun plantilla Lm");
                if (!$ok) {
                    break;
                }
                $curTechQueueCount++;
                $queued++;
            }
        }

        if ($queued > 0) {
            return $queued;
        }

        // Engaging Savings Lock for research only if ETA <= MAX_SAVINGS_LOCK_ETA
        if (isset($this->ctx->budgetManager) && empty($this->ctx->budgetManager->getSavingsLockForCategory(0, 'research')) && !empty($unaffordableTechGoals)) {
            usort($unaffordableTechGoals, function($a, $b) {
                return $b['score'] <=> $a['score'];
            });

            foreach ($unaffordableTechGoals as $topTech) {
                if ($topTech['score'] <= 0.0) continue;

                $stockMap = array(
                    901 => (float)($topTech['data']['metal'] ?? 0.0),
                    902 => (float)($topTech['data']['crystal'] ?? 0.0),
                    903 => (float)($topTech['data']['deuterium'] ?? 0.0)
                );
                $prodMap = array(
                    901 => (float)($topTech['data']['metal_perhour'] ?? 0.0),
                    902 => (float)($topTech['data']['crystal_perhour'] ?? 0.0),
                    903 => (float)($topTech['data']['deuterium_perhour'] ?? 0.0)
                );
                $eta = $this->ctx->budgetManager->calculateSafeEta($topTech['cost'], $stockMap, $prodMap);

                $maxAllowedEta = (in_array((int)$topTech['tech_id'], array(123, 124), true)) ? 28800 : self::MAX_SAVINGS_LOCK_ETA;
                if ($eta > $maxAllowedEta) {
                    continue; // Do not lock empire for unreachable research target!
                }

                $locked = $this->ctx->budgetManager->setSavingsLock(
                    $topTech['planet_id'],
                    'research',
                    $topTech['tech_id'],
                    $topTech['cost'],
                    $topTech['data'],
                    'research',
                    $empire
                );

                if ($locked) {
                    $this->ctx->decisionLog->record(
                        'savings_lock_engaged',
                        "Engaged research savings lock for {$resource[$topTech['tech_id']]} on planet #{$topTech['planet_id']} (ETA: {$eta}s)",
                        array('element' => $topTech['tech_id'], 'cost' => $topTech['cost'], 'target_type' => 'research', 'eta' => $eta),
                        "save_research_{$topTech['tech_id']}",
                        $topTech['score']
                    );
                    break;
                }
            }
        }

        return 0;
    }
}

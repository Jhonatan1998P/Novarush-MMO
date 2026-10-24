<?php

/**
 * NovaRush Bot AI v2 - Difficulty Profile
 *
 * Difficulty modulates cognitive ability, simulation depth, reaction latency,
 * and fog-of-war diligence. It NEVER grants illegal free resources or cheats.
 */
class BotDifficultyProfile
{
    const EASY      = 'easy';
    const NORMAL    = 'normal';
    const HARD      = 'hard';
    const NIGHTMARE = 'nightmare';
    const TITAN     = 'titan';

    public static function get($name = self::NORMAL)
    {
        return new self($name);
    }

    public $name;
    public $reactionDelayMin;       // Seconds delay before reacting to new threat
    public $reactionDelayMax;       // Seconds delay before reacting to new threat
    public $simIterations;          // Monte Carlo combat simulation rounds
    public $intelMaxAgeHours;       // Intel confidence half-life
    public $minStrikeEvHp;          // Minimum expected profit in Hours of Production
    public $fleetsaveRiskThreshold; // Loss probability threshold to trigger fleetsave
    public $stagingEnabled;         // Whether bot coordinates fleet staging
    public $targetScanRadius;       // System search radius per colony
    public $intergalacticScan;      // Whether bot scans adjacent galaxies if tech permits
    public $militaryCycleInterval;  // Dedicated interval between fleet recruitment cycles (seconds)
    public $defenseCycleInterval;   // Dedicated interval between defense recruitment cycles (seconds)
    public $maxHangarQueueDuration; // Max allowed duration for shipyard hangar queue (seconds)
    public $batchTimeTarget;        // Max target duration for single batch sizing (seconds)
    public $maxRegularSlots;        // Max allowed concurrent shipyard slots
    public $fleetEnqueueSeconds;   // Enqueue duration target for fleet recruitment (seconds)
    public $defenseEnqueueSeconds; // Enqueue duration target for defense recruitment (seconds)
    public $recyclerStrikeOffset;   // Seconds after strike impact to harvest debris (offensive)
    public $minRoiRequired;                 // Minimum required net ROI for launching attacks
    public $maxOwnFleetLossRatioAllowed;   // Max acceptable fleet attrition ratio (0.10 for Easy, 1.0 for Nightmare)
    public $minRecyclerCoverageRequired;   // Minimum required recycler capacity coverage for debris ops
    public $ninjaTrapAllowed;              // Whether bot will intentionally hold and fight profitable defense trades
    public $antiBaitEnabled;                // Whether bot actively monitors and evades in-flight ninja ambushes
    public $antiBaitWindowSeconds;          // Seconds before impact window for live tactical snapshot (e.g. 180s, 120s, 90s)
    public $antiBaitAccuracy;               // Probability of noticing and successfully recalling from ambush (0.0 to 1.0)
    public $maxBuildQueueSlots;             // Capacidad máxima de cola de construcción por dificultad
    public $maxTechQueueSlots;              // Capacidad máxima de cola de investigación por dificultad

    public function __construct($name = self::NORMAL)
    {
        $raw = is_string($name) ? $name : '';
        $mb = (function_exists('mb_strtolower')) ? mb_strtolower($raw, 'UTF-8') : $raw;
        $lower = strtolower($raw);

        if ($mb === 'titán' || $mb === "tit\xc3\xa1n" || $raw === 'titán' || $raw === "tit\xc3\xa1n" ||
            $lower === 'titán' || $lower === "tit\xc3\xa1n" || $lower === "tit\xe3\xa1n" ||
            $lower === 'titan' || $mb === 'titan' || $raw === 'titan') {
            $name = self::TITAN;
        } else {
            $name = is_string($name) ? $lower : $name;
        }

        $this->name = $name;
        $this->configure();
    }

    private function configure()
    {
        switch ($this->name) {
            case self::EASY:
                $this->reactionDelayMin              = 180;
                $this->reactionDelayMax              = 360;
                $this->simIterations                 = 1;
                $this->intelMaxAgeHours              = 1.0;
                $this->minStrikeEvHp                 = 2.5;
                $this->fleetsaveRiskThreshold        = 0.50;
                $this->stagingEnabled                = false;
                $this->targetScanRadius              = 20;
                $this->intergalacticScan             = false;
                $this->militaryCycleInterval         = 1800;
                $this->defenseCycleInterval          = 3600;
                $this->maxHangarQueueDuration        = 86400; // 24 horas
                $this->batchTimeTarget               = 3600;  // 1 hora
                $this->maxRegularSlots               = 14;
                $this->fleetEnqueueSeconds           = 180;   // 3 min
                $this->defenseEnqueueSeconds         = 300;   // 5 min
                $this->recyclerStrikeOffset          = mt_rand(1, 15);
                $this->minRoiRequired                = 0.40;  // Conservador: exige 40% ROI
                $this->maxOwnFleetLossRatioAllowed  = 0.10;  // Miedo a perder más del 10% de flota
                $this->minRecyclerCoverageRequired  = 0.50;
                $this->ninjaTrapAllowed             = false; // Siempre huye si pierde
                $this->antiBaitEnabled              = false; // Ciego ante trampas ninjas (novato)
                $this->antiBaitWindowSeconds        = 0;
                $this->antiBaitAccuracy             = 0.0;
                $this->maxBuildQueueSlots           = 2;
                $this->maxTechQueueSlots            = 2;
                break;

            case self::HARD:
                $this->reactionDelayMin              = 45;
                $this->reactionDelayMax              = 90;
                $this->simIterations                 = 10;
                $this->intelMaxAgeHours              = 1.0;
                $this->minStrikeEvHp                 = 0.8;
                $this->fleetsaveRiskThreshold        = 0.20;
                $this->stagingEnabled                = true;
                $this->targetScanRadius              = 80;
                $this->intergalacticScan             = true;
                $this->militaryCycleInterval         = 360;   // 6 min
                $this->defenseCycleInterval          = 720;   // 12 min
                $this->maxHangarQueueDuration        = 86400; // 24 horas
                $this->batchTimeTarget               = 3600;  // 1 hora
                $this->maxRegularSlots               = 14;
                $this->fleetEnqueueSeconds           = 420;   // 7 min
                $this->defenseEnqueueSeconds         = 780;   // 13 min
                $this->recyclerStrikeOffset          = mt_rand(2, 4);
                $this->minRoiRequired                = 0.30;  // Exige 30% ROI mínimo
                $this->maxOwnFleetLossRatioAllowed  = 0.75;  // Acepta desgaste fuerte si es rentable
                $this->minRecyclerCoverageRequired  = 0.85;  // 85% de reciclaje asegurado
                $this->ninjaTrapAllowed             = true;  // Hold en casa si Delta MSE > 0
                $this->antiBaitEnabled              = true;  // Monitorea y evade a T-120s
                $this->antiBaitWindowSeconds        = 120;
                $this->antiBaitAccuracy             = 0.85;  // 85% agudeza de evasión
                $this->maxBuildQueueSlots           = 5;
                $this->maxTechQueueSlots            = 5;
                break;

            case self::TITAN:
            case 'titán':
            case "tit\xc3\xa1n":
            case "tit\xe3\xa1n":
            case self::NIGHTMARE:
                $this->reactionDelayMin              = 15;
                $this->reactionDelayMax              = 35;
                $this->simIterations                 = 15;
                $this->intelMaxAgeHours              = 1.0;
                $this->minStrikeEvHp                 = 0.4;
                $this->fleetsaveRiskThreshold        = 0.10;
                $this->stagingEnabled                = true;
                $this->targetScanRadius              = 150;
                $this->intergalacticScan             = true;
                $this->militaryCycleInterval         = 180;   // 3 min
                $this->defenseCycleInterval          = 360;   // 6 min
                $this->maxHangarQueueDuration        = 86400; // 24 horas
                $this->batchTimeTarget               = 3600;  // 1 hora
                $this->maxRegularSlots               = 14;
                $this->fleetEnqueueSeconds           = 240;   // 4 min
                $this->defenseEnqueueSeconds         = 420;   // 7 min
                $this->recyclerStrikeOffset          = mt_rand(1, 2);
                $this->minRoiRequired                = 0.30;  // Exige 30% ROI mínimo en gran escala
                $this->maxOwnFleetLossRatioAllowed  = 1.00;  // Sacrificio mutuo 100% si ganancia neta es positiva
                $this->minRecyclerCoverageRequired  = 0.95;  // 95% estricto de cobertura de reciclaje
                $this->ninjaTrapAllowed             = true;  // Trampa Ninja activa en casa
                $this->antiBaitEnabled              = true;  // Paranoia táctica pre-impacto a T-90s
                $this->antiBaitWindowSeconds        = 90;
                $this->antiBaitAccuracy             = 1.00;  // 100% Infalible: imposible de estampar
                $this->maxBuildQueueSlots           = 10;
                $this->maxTechQueueSlots            = 10;
                break;

            case self::NORMAL:
            default:
                $this->name                          = self::NORMAL;
                $this->reactionDelayMin              = 90;
                $this->reactionDelayMax              = 180;
                $this->simIterations                 = 5;
                $this->intelMaxAgeHours              = 1.0;
                $this->minStrikeEvHp                 = 1.5;
                $this->fleetsaveRiskThreshold        = 0.30;
                $this->stagingEnabled                = true;
                $this->targetScanRadius              = 45;
                $this->intergalacticScan             = false;
                $this->militaryCycleInterval         = 900;
                $this->defenseCycleInterval          = 1800;
                $this->maxHangarQueueDuration        = 86400; // 24 horas
                $this->batchTimeTarget               = 3600;  // 1 hora
                $this->maxRegularSlots               = 14;
                $this->fleetEnqueueSeconds           = 240;   // 4 min
                $this->defenseEnqueueSeconds         = 360;   // 6 min
                $this->recyclerStrikeOffset          = mt_rand(6, 15);
                $this->minRoiRequired                = 0.30;  // Exige 30% ROI mínimo
                $this->maxOwnFleetLossRatioAllowed  = 0.40;  // Tolera hasta 40% de bajas
                $this->minRecyclerCoverageRequired  = 0.70;  // 70% reciclaje asegurado
                $this->ninjaTrapAllowed             = false; // Solo hold si gana sobrado
                $this->antiBaitEnabled              = true;  // Revisa a T-180s
                $this->antiBaitWindowSeconds        = 180;
                $this->antiBaitAccuracy             = 0.50;  // 50% de probabilidad de notar trampa
                $this->maxBuildQueueSlots           = 3;
                $this->maxTechQueueSlots            = 3;
                break;
        }
    }

    /**
     * Retorna el desfase aleatorio de arribo de recicladores post-impacto según dificultad (Ataque y Defensa):
     * - Pesadilla / Titán: 1 a 2 segundos
     * - Difícil: 2 a 4 segundos
     * - Normal: 6 a 15 segundos
     * - Fácil: 15 a 40 segundos
     *
     * @return int
     */
    public function getRecyclerOffset()
    {
        return $this->getRecyclerStrikeOffset();
    }

    /**
     * Retorna el desfase de reciclaje ofensivo post-impacto.
     * Easy: 15..40, Normal: 6..15, Hard: 2..4, Nightmare: 1..2
     *
     * @return int
     */
    public function getRecyclerStrikeOffset()
    {
        switch ($this->name) {
            case self::EASY:
                return mt_rand(15, 40);
            case self::HARD:
                return mt_rand(2, 4);
            case self::NIGHTMARE:
            case 'titan':
            case 'titán':
                return mt_rand(1, 2);
            case self::NORMAL:
            default:
                return mt_rand(6, 15);
        }
    }

    /**
     * Retorna el desfase de reciclaje defensivo post-impacto ("Estampadas" y Búnker).
     *
     * @return int
     */
    public function getRecyclerDefenseOffset()
    {
        return $this->getRecyclerStrikeOffset();
    }

    /**
     * Máximo de sondas de espionaje por misión
     * Easy: 6, Normal: 15, Hard: 30, Nightmare: 50
     *
     * @return int
     */
    public function getMaxSpyProbes()
    {
        switch ($this->name) {
            case self::EASY:
                return 6;
            case self::HARD:
                return 30;
            case self::NIGHTMARE:
            case 'titan':
            case 'titán':
                return 50;
            case self::NORMAL:
            default:
                return 15;
        }
    }

    /**
     * Tope estricto de ataques simultáneos en vuelo (máximo 2)
     * Easy: 1, Normal: 2, Hard: 2, Nightmare: 2
     *
     * @return int
     */
    public function getMaxConcurrentStrikes()
    {
        switch ($this->name) {
            case self::EASY:
                return 1;
            case self::HARD:
            case self::NIGHTMARE:
            case 'titan':
            case 'titán':
            case self::NORMAL:
            default:
                return 2;
        }
    }

    /**
     * Determina si se debe enviar una sonda de seguridad pre-impacto
     * Easy: false, Normal: (mt_rand(1, 100) <= 50), Hard: true, Nightmare: true
     *
     * @return bool
     */
    public function shouldSendSafetyProbe()
    {
        switch ($this->name) {
            case self::EASY:
                return false;
            case self::HARD:
            case self::NIGHTMARE:
            case 'titan':
            case 'titán':
                return true;
            case self::NORMAL:
            default:
                return (mt_rand(1, 100) <= 50);
        }
    }

    /**
     * Ventana previa al impacto para espionaje anti-bait (segundos)
     * Easy: 0, Normal: 180, Hard: 120, Nightmare: 90
     *
     * @return int
     */
    public function getSafetyProbeWindowSeconds()
    {
        switch ($this->name) {
            case self::EASY:
                return 0;
            case self::HARD:
                return 120;
            case self::NIGHTMARE:
            case 'titan':
            case 'titán':
                return 90;
            case self::NORMAL:
            default:
                return 180;
        }
    }

    /**
     * Ataques reservados sobre el límite legal de 6 para Caza de Flotas / Asedio
     * Easy: 0, Normal: 1, Hard: 2, Nightmare: 2
     *
     * @return int
     */
    public function getBashingFarmReserve()
    {
        switch ($this->name) {
            case self::EASY:
                return 0;
            case self::NORMAL:
                return 1;
            case self::HARD:
            case self::NIGHTMARE:
            case 'titan':
            case 'titán':
            default:
                return 2;
        }
    }

    /**
     * Segundos exactos antes del impacto de la flota para impacto de misiles MIP
     * Easy: 10..25, Normal: 5..12, Hard: 2..4, Nightmare: 1..2
     *
     * @return int
     */
    public function getMipImpactLeadSeconds()
    {
        switch ($this->name) {
            case self::EASY:
                return mt_rand(10, 25);
            case self::HARD:
                return mt_rand(2, 4);
            case self::NIGHTMARE:
            case 'titan':
            case 'titán':
                return mt_rand(1, 2);
            case self::NORMAL:
            default:
                return mt_rand(5, 12);
        }
    }

    /**
     * Rango de retardo en segundos para recall post-impacto enemigo en fleetsave
     * Easy: [120, 300], Normal: [60, 150], Hard: [25, 60], Nightmare: [10, 30]
     *
     * @return int[]
     */
    public function getFleetsaveRecallDelayRange()
    {
        switch ($this->name) {
            case self::EASY:
                return array(120, 300);
            case self::HARD:
                return array(25, 60);
            case self::NIGHTMARE:
            case 'titan':
            case 'titán':
                return array(10, 30);
            case self::NORMAL:
            default:
                return array(60, 150);
        }
    }

    /**
     * Cobertura mínima de recicladores exigida
     * Easy: 0.50, Normal: 0.70, Hard: 0.85, Nightmare: 0.95
     *
     * @return float
     */
    public function getMinRecyclerCoverage()
    {
        switch ($this->name) {
            case self::EASY:
                return 0.50;
            case self::HARD:
                return 0.85;
            case self::NIGHTMARE:
            case 'titan':
            case 'titán':
                return 0.95;
            case self::NORMAL:
            default:
                return 0.70;
        }
    }

    /**
     * Permite uso ofensivo de sensor phalanx
     * Easy: false, Normal: false, Hard: true, Nightmare: true
     *
     * @return bool
     */
    public function canUseOffensivePhalanx()
    {
        switch ($this->name) {
            case self::HARD:
            case self::NIGHTMARE:
            case 'titan':
            case 'titán':
                return true;
            case self::EASY:
            case self::NORMAL:
            default:
                return false;
        }
    }

    /**
     * Returns macro budget ratios governed purely by the bot personality
     *
     * @param BotPersonality|null $personality
     * @return array ['fleet' => float, 'defense' => float, 'mines' => float, 'research' => float]
     */
     public function getAdjustedBudget($personality = null)
     {
         if ($personality && method_exists($personality, 'getBudgetRatios')) {
             return $personality->getBudgetRatios();
         }

         if (class_exists('BotPersonality')) {
             return (new BotPersonality())->getBudgetRatios();
         }

         return array(
             'mines'    => 0.30,
             'fleet'    => 0.35,
             'defense'  => 0.15,
             'research' => 0.20,
         );
     }

    public function getProbeFormulaMode()
    {
        return ($this->name === self::EASY) ? 'flat_minimal' : 'canonical_delta';
    }

    public function getMaxProbesPerMission()
    {
        return $this->getMaxSpyProbes();
    }

    public function prefersMoonFOB()
    {
        return ($this->name === self::HARD || $this->name === self::NIGHTMARE || $this->name === self::TITAN);
    }

    public function canDoMoonToMoonDeployment()
    {
        return ($this->name === self::HARD || $this->name === self::NIGHTMARE || $this->name === self::TITAN);
    }

    public function canDetectOnlineActivity()
    {
        return ($this->name === self::HARD || $this->name === self::NIGHTMARE || $this->name === self::TITAN);
    }

    public function enforcesWaterfallQueue()
    {
        return true;
    }

    public function canLaunchSurgicalMIP()
    {
        return ($this->name === self::NIGHTMARE || $this->name === self::TITAN);
    }

    public function hasAntiBaitWatchdog()
    {
        return ($this->name === self::HARD || $this->name === self::NIGHTMARE || $this->name === self::TITAN);
    }

    public function getSafetyProbeWindow()
    {
        switch ($this->name) {
            case self::NIGHTMARE:
            case self::TITAN:
                return array(180, 240);
            case self::HARD:
                return array(120, 180);
            case self::NORMAL:
                return array(60, 120);
            case self::EASY:
            default:
                return array(0, 0);
        }
    }

    public function isTitan()
    {
        return $this->name === self::NIGHTMARE || $this->name === 'titan' || $this->name === 'titán';
    }

    public function isNightmare()
    {
        return $this->isTitan();
    }

    public function getMaxBuildQueueSlots()
    {
        return isset($this->maxBuildQueueSlots) ? max(1, (int)$this->maxBuildQueueSlots) : 3;
    }

    public function getMaxTechQueueSlots()
    {
        return isset($this->maxTechQueueSlots) ? max(1, (int)$this->maxTechQueueSlots) : 3;
    }

    /**
     * Maximo de planetas/lunas destino que pueden recibir convoyes logisticos simultaneamente
     * Easy: 1, Normal: 2, Hard: 3, Nightmare/Titan: 4
     */
    public function getMaxLogisticsDestinations()
    {
        switch ($this->name) {
            case self::EASY:
                return 1;
            case self::HARD:
                return 3;
            case self::NIGHTMARE:
            case self::TITAN:
            case 'titán':
                return 4;
            case self::NORMAL:
            default:
                return 2;
        }
    }

    /**
     * Maximo de colonias donantes que pueden enviar convoyes en paralelo para cubrir una misma demanda
     * Easy: 1, Normal: 2, Hard: 3, Nightmare/Titan: 4
     */
    public function getMaxDonorsPerDemand()
    {
        switch ($this->name) {
            case self::EASY:
                return 1;
            case self::HARD:
                return 3;
            case self::NIGHTMARE:
            case self::TITAN:
            case 'titán':
                return 4;
            case self::NORMAL:
            default:
                return 2;
        }
    }

    /**
     * Horizonte de slots en espera (building_queue / b_tech_queue) que la logistica pre-financia
     * Easy: 1 (Slot 1), Normal: 2 (Slots 1..2), Hard: 4 (Slots 1..4), Nightmare/Titan: 9 (Slots 1..9)
     */
    public function getLogisticsQueueHorizonSlots()
    {
        switch ($this->name) {
            case self::EASY:
                return 1;
            case self::HARD:
                return 4;
            case self::NIGHTMARE:
            case self::TITAN:
            case 'titán':
                return 9;
            case self::NORMAL:
            default:
                return 2;
        }
    }

    /**
     * Umbral de autosuficiencia local en minutos: si el planeta destino puede producir el deficit
     * de esa bolsa por si mismo en <= X minutos, no se despacha convoy.
     * Easy: 30.0 min, Normal: 15.0 min, Hard: 8.0 min, Nightmare/Titan: 5.0 min
     */
    public function getSelfSufficiencyMinutes()
    {
        switch ($this->name) {
            case self::EASY:
                return 30.0;
            case self::HARD:
                return 8.0;
            case self::NIGHTMARE:
            case self::TITAN:
            case 'titán':
                return 5.0;
            case self::NORMAL:
            default:
                return 15.0;
        }
    }

    /**
     * Permite usar la Red de Salto Cuantico (#43) para concentrar flotas en la Luna FOB a 0 Deuterio
     */
    public function canUseQuantumJumpStaging()
    {
        return ($this->name === self::HARD || $this->name === self::NIGHTMARE || $this->name === self::TITAN || $this->name === 'titán');
    }

    /**
     * Permite realizar Ascenso Orbital Corto (Planeta -> Su propia Luna en [G:S:P]) para ocultar flotas del Phalanx
     */
    public function canUseOrbitalAscent()
    {
        return ($this->name === self::HARD || $this->name === self::NIGHTMARE || $this->name === self::TITAN || $this->name === 'titán');
    }

    /**
     * Factor de velocidad (1..10) para convoyes de transporte logistico (Mision 3)
     */
    public function getLogisticsSpeedFactor()
    {
        return ($this->name === self::EASY) ? 8 : 10;
    }

    /**
     * Ratio objetivo de llenado de almacen (Kickstart) para colonias jovenes (Mina de Metal < 14)
     */
    public function getYoungColonyKickstartStorageRatio()
    {
        switch ($this->name) {
            case self::EASY:
                return 0.15;
            case self::HARD:
                return 0.45;
            case self::NIGHTMARE:
            case self::TITAN:
            case 'titán':
                return 0.65;
            case self::NORMAL:
            default:
                return 0.25;
        }
    }

    /**
     * Maximas misiones de colonizacion concurrentes (en vuelo + en cola)
     */
    public function getMaxConcurrentColonizations()
    {
        if ($this->name === self::TITAN || $this->name === self::NIGHTMARE || $this->name === 'titán') {
            return 3;
        }
        if ($this->name === self::HARD) {
            return 2;
        }
        return 1;
    }

    /**
     * Posiciones orbitales permitidas para colonizacion por orden de preferencia
     */
    public function getAllowedColonizationPositions()
    {
        if ($this->name === self::TITAN || $this->name === self::NIGHTMARE || $this->name === 'titán') {
            return array(8, 7, 9, 6, 10);
        }
        if ($this->name === self::HARD) {
            return array(8, 7, 9, 6, 10, 5, 11);
        }
        if ($this->name === self::NORMAL) {
            return array(8, 7, 9, 6, 10, 5, 11, 4, 12);
        }
        return array(6, 10, 5, 11, 4, 12, 7, 9, 8, 3, 13);
    }

    /**
     * Politica de dispersion geografica de colonias
     */
    public function getColonizationSpreadPolicy()
    {
        if ($this->name === self::TITAN || $this->name === self::NIGHTMARE || $this->name === 'titán') {
            return array('max_per_cluster' => 2, 'cluster_radius' => 2, 'node_min_dist' => 20, 'node_max_dist' => 45);
        }
        if ($this->name === self::HARD) {
            return array('max_per_cluster' => 2, 'cluster_radius' => 3, 'node_min_dist' => 15, 'node_max_dist' => 35);
        }
        if ($this->name === self::NORMAL) {
            return array('max_per_cluster' => 3, 'cluster_radius' => 5, 'node_min_dist' => 8, 'node_max_dist' => 20);
        }
        return array('max_per_cluster' => 9, 'cluster_radius' => 10, 'node_min_dist' => 1, 'node_max_dist' => 10);
    }

    /**
     * Ratio de capacidad neta de carga semilla (Seed Cargo) a transportar en Mision 7
     */
    public function getColonizationSeedCargoRatio()
    {
        switch ($this->name) {
            case self::EASY:
                return 0.0;
            case self::HARD:
                return 0.85;
            case self::NIGHTMARE:
            case self::TITAN:
            case 'titán':
                return 1.00;
            case self::NORMAL:
            default:
                return 0.50;
        }
    }

    /**
     * Ratio objetivo de cobertura de lunas respecto al total de colonias
     */
    public function getTargetMoonCoverageRatio()
    {
        switch ($this->name) {
            case self::EASY:
                return 0.0;
            case self::NORMAL:
                return 0.25;
            case self::HARD:
                return 0.50;
            case self::NIGHTMARE:
            case self::TITAN:
            case 'titán':
            default:
                return 0.80;
        }
    }

    /**
     * Cantidad minima de planetas requeridos para iniciar campana de moonshots
     */
    public function getMinPlanetsForMoonshots()
    {
        return $this->isTitan() ? 4 : 5;
    }

    /**
     * Permite reclutar escuadrones dedicados de Cazas Ligeros para moonshots (715x #204)
     */
    public function canRecruitMoonshotSquadrons()
    {
        return ($this->name === self::HARD || $this->name === self::NIGHTMARE || $this->name === self::TITAN || $this->name === 'titán');
    }

    /**
     * Cooldown entre intentos de moonshots en segundos
     */
    public function getMoonshotCooldownSeconds()
    {
        if ($this->name === self::TITAN || $this->name === self::NIGHTMARE || $this->name === 'titán') {
            return 900;
        }
        if ($this->name === self::HARD) {
            return 1800;
        }
        return 3600;
    }

    public static function getAll()
    {
        return array(self::EASY, self::NORMAL, self::HARD, self::NIGHTMARE);
    }
}

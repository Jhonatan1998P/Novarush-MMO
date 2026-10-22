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

    public function __construct($name = self::NORMAL)
    {
        $this->name = strtolower($name);
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
                break;

            case self::TITAN:
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

    public static function getAll()
    {
        return array(self::EASY, self::NORMAL, self::HARD, self::NIGHTMARE);
    }
}

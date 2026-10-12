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
    public $recyclerStrikeOffset;   // Seconds after strike impact to harvest debris (offensive)

    public function __construct($name = self::NORMAL)
    {
        $this->name = strtolower($name);
        $this->configure();
    }

    private function configure()
    {
        switch ($this->name) {
            case self::EASY:
                $this->reactionDelayMin       = 180;
                $this->reactionDelayMax       = 360;
                $this->simIterations          = 1;
                $this->intelMaxAgeHours       = 1.0;
                $this->minStrikeEvHp          = 2.5;
                $this->fleetsaveRiskThreshold = 0.50;
                $this->stagingEnabled         = false;
                $this->targetScanRadius       = 20;
                $this->intergalacticScan      = false;
                $this->militaryCycleInterval  = 1800;
                $this->defenseCycleInterval   = 3600;
                $this->maxHangarQueueDuration = 86400; // 24 horas
                $this->batchTimeTarget        = 3600;  // 1 hora
                $this->maxRegularSlots        = 6;
                $this->recyclerStrikeOffset   = mt_rand(1, 15);
                break;

            case self::HARD:
                $this->reactionDelayMin       = 45;
                $this->reactionDelayMax       = 90;
                $this->simIterations          = 10;
                $this->intelMaxAgeHours       = 1.0;
                $this->minStrikeEvHp          = 0.8;
                $this->fleetsaveRiskThreshold = 0.20;
                $this->stagingEnabled         = true;
                $this->targetScanRadius       = 80;
                $this->intergalacticScan      = true;
                $this->militaryCycleInterval  = 480;
                $this->defenseCycleInterval   = 900;
                $this->maxHangarQueueDuration = 86400; // 24 horas
                $this->batchTimeTarget        = 3600;  // 1 hora
                $this->maxRegularSlots        = 6;
                $this->recyclerStrikeOffset   = mt_rand(1, 5);
                break;

            case self::NIGHTMARE:
                $this->reactionDelayMin       = 15;
                $this->reactionDelayMax       = 35;
                $this->simIterations          = 15;
                $this->intelMaxAgeHours       = 1.0;
                $this->minStrikeEvHp          = 0.4;
                $this->fleetsaveRiskThreshold = 0.10;
                $this->stagingEnabled         = true;
                $this->targetScanRadius       = 150;
                $this->intergalacticScan      = true;
                $this->militaryCycleInterval  = 180;
                $this->defenseCycleInterval   = 360;
                $this->maxHangarQueueDuration = 86400; // 24 horas
                $this->batchTimeTarget        = 3600;  // 1 hora
                $this->maxRegularSlots        = 6;
                $this->recyclerStrikeOffset   = mt_rand(1, 5);
                break;

            case self::NORMAL:
            default:
                $this->name                   = self::NORMAL;
                $this->reactionDelayMin       = 90;
                $this->reactionDelayMax       = 180;
                $this->simIterations          = 5;
                $this->intelMaxAgeHours       = 1.0;
                $this->minStrikeEvHp          = 1.5;
                $this->fleetsaveRiskThreshold = 0.30;
                $this->stagingEnabled         = true;
                $this->targetScanRadius       = 45;
                $this->intergalacticScan      = false;
                $this->militaryCycleInterval  = 900;
                $this->defenseCycleInterval   = 1800;
                $this->maxHangarQueueDuration = 86400; // 24 horas
                $this->batchTimeTarget        = 3600;  // 1 hora
                $this->maxRegularSlots        = 6;
                $this->recyclerStrikeOffset   = mt_rand(1, 10);
                break;
        }
    }

    /**
     * Retorna el desfase aleatorio de arribo de recicladores post-impacto según dificultad (Ataque y Defensa):
     * - Pesadilla / Titán y Difícil: 1 a 5 segundos aleatorios (1..5)
     * - Normal: 1 a 10 segundos aleatorios (1..10)
     * - Fácil: 1 a 15 segundos aleatorios (1..15)
     *
     * @return int
     */
    public function getRecyclerOffset()
    {
        switch ($this->name) {
            case self::NIGHTMARE:
            case 'titan':
            case 'titán':
            case self::HARD:
                return mt_rand(1, 5);

            case self::EASY:
                return mt_rand(1, 15);

            case self::NORMAL:
            default:
                return mt_rand(1, 10);
        }
    }

    /**
     * Retorna el desfase de reciclaje ofensivo post-impacto.
     *
     * @return int
     */
    public function getRecyclerStrikeOffset()
    {
        return $this->getRecyclerOffset();
    }

    /**
     * Retorna el desfase de reciclaje defensivo post-impacto ("Estampadas" y Búnker).
     *
     * @return int
     */
    public function getRecyclerDefenseOffset()
    {
        return $this->getRecyclerOffset();
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

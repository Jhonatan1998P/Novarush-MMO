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
    public $budgetFleet;            // Base percentage for offensive fleet
    public $budgetDefense;          // Base percentage for planetary defense
    public $budgetMines;            // Base percentage for infrastructure and mines
    public $budgetResearch;         // Base percentage for technology research

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
                $this->intelMaxAgeHours       = 2.0;
                $this->minStrikeEvHp          = 2.5;
                $this->fleetsaveRiskThreshold = 0.50;
                $this->stagingEnabled         = false;
                $this->targetScanRadius       = 20;
                $this->intergalacticScan      = false;
                $this->budgetFleet            = 0.15;
                $this->budgetDefense          = 0.10;
                $this->budgetMines            = 0.55;
                $this->budgetResearch         = 0.20;
                break;

            case self::HARD:
                $this->reactionDelayMin       = 45;
                $this->reactionDelayMax       = 90;
                $this->simIterations          = 10;
                $this->intelMaxAgeHours       = 8.0;
                $this->minStrikeEvHp          = 0.8;
                $this->fleetsaveRiskThreshold = 0.20;
                $this->stagingEnabled         = true;
                $this->targetScanRadius       = 80;
                $this->intergalacticScan      = true;
                $this->budgetFleet            = 0.45;
                $this->budgetDefense          = 0.20;
                $this->budgetMines            = 0.20;
                $this->budgetResearch         = 0.15;
                break;

            case self::NIGHTMARE:
                $this->reactionDelayMin       = 15;
                $this->reactionDelayMax       = 35;
                $this->simIterations          = 15;
                $this->intelMaxAgeHours       = 16.0;
                $this->minStrikeEvHp          = 0.4;
                $this->fleetsaveRiskThreshold = 0.10;
                $this->stagingEnabled         = true;
                $this->targetScanRadius       = 150;
                $this->intergalacticScan      = true;
                $this->budgetFleet            = 0.55;
                $this->budgetDefense          = 0.25;
                $this->budgetMines            = 0.12;
                $this->budgetResearch         = 0.08;
                break;

            case self::NORMAL:
            default:
                $this->name                   = self::NORMAL;
                $this->reactionDelayMin       = 90;
                $this->reactionDelayMax       = 180;
                $this->simIterations          = 5;
                $this->intelMaxAgeHours       = 4.0;
                $this->minStrikeEvHp          = 1.5;
                $this->fleetsaveRiskThreshold = 0.30;
                $this->stagingEnabled         = true;
                $this->targetScanRadius       = 45;
                $this->intergalacticScan      = false;
                $this->budgetFleet            = 0.30;
                $this->budgetDefense          = 0.15;
                $this->budgetMines            = 0.35;
                $this->budgetResearch         = 0.20;
                break;
        }
    }

    /**
     * Returns normalized macro budget ratios combining difficulty profile and bot personality
     *
     * @param BotPersonality|null $personality
     * @return array ['fleet' => float, 'defense' => float, 'mines' => float, 'research' => float]
     */
    public function getAdjustedBudget($personality = null)
    {
        $wFleet = ($personality && isset($personality->weightFleet)) ? (float)$personality->weightFleet : 1.0;
        $wDef   = ($personality && isset($personality->weightDefense)) ? (float)$personality->weightDefense : 1.0;
        $wMines = ($personality && isset($personality->weightMines)) ? (float)$personality->weightMines : 1.0;
        $wTech  = ($personality && isset($personality->weightResearch)) ? (float)$personality->weightResearch : 1.0;

        $rawFleet = $this->budgetFleet * $wFleet;
        $rawDef   = $this->budgetDefense * $wDef;
        $rawMines = $this->budgetMines * $wMines;
        $rawTech  = $this->budgetResearch * $wTech;

        $total = max(0.01, $rawFleet + $rawDef + $rawMines + $rawTech);

        return array(
            'fleet'    => round($rawFleet / $total, 4),
            'defense'  => round($rawDef / $total, 4),
            'mines'    => round($rawMines / $total, 4),
            'research' => round($rawTech / $total, 4),
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

<?php

/**
 * NovaRush Bot AI v2 - Personality Profile
 *
 * Personality biases strategic weightings (economy vs military vs tech vs turtling).
 */
class BotPersonality
{
    const AGGRESSIVE = 'aggressive';
    const DEFENSIVE  = 'defensive';
    const ECONOMIC   = 'economic';
    const BALANCED   = 'balanced';

    public $name;
    public $weightMines;      // Multiplier for infrastructure investment
    public $weightFleet;      // Multiplier for offensive fleet building
    public $weightDefense;    // Multiplier for planetary defense
    public $weightResearch;   // Multiplier for technology
    public $aggressionLevel;  // Likelihood of initiating strikes
    public $lossAversion;     // Reluctance to accept combat losses

    public function __construct($name = self::BALANCED)
    {
        $this->name = strtolower($name);
        $this->configure();
    }

    private function configure()
    {
        switch ($this->name) {
            case self::AGGRESSIVE:
            case 'raider':
                $this->weightMines     = 0.70;
                $this->weightFleet     = 1.50;
                $this->weightDefense   = 0.60;
                $this->weightResearch  = 1.10;
                $this->aggressionLevel = 1.40;
                $this->lossAversion    = 0.70;
                break;

            case self::DEFENSIVE:
            case 'bunker':
            case 'turtle':
                $this->weightMines     = 1.10;
                $this->weightFleet     = 0.50;
                $this->weightDefense   = 1.80;
                $this->weightResearch  = 0.90;
                $this->aggressionLevel = 0.50;
                $this->lossAversion    = 1.50;
                break;

            case self::ECONOMIC:
            case 'miner':
            case 'minero':
                $this->weightMines     = 1.60;
                $this->weightFleet     = 0.60;
                $this->weightDefense   = 0.90;
                $this->weightResearch  = 1.20;
                $this->aggressionLevel = 0.60;
                $this->lossAversion    = 1.30;
                break;

            case self::BALANCED:
            case 'equilibrado':
            default:
                $this->name            = self::BALANCED;
                $this->weightMines     = 1.00;
                $this->weightFleet     = 1.00;
                $this->weightDefense   = 1.00;
                $this->weightResearch  = 1.00;
                $this->aggressionLevel = 1.00;
                $this->lossAversion    = 1.00;
                break;
        }
    }

    public function isAggressive()
    {
        return $this->name === self::AGGRESSIVE || $this->name === 'raider';
    }

    public function getCategoryMultiplier($category)
    {
        switch ($category) {
            case 'mines':
            case 'mine':
            case 'industry':
                return (float)$this->weightMines;
            case 'fleet':
                return (float)$this->weightFleet;
            case 'defense':
            case 'bunker':
                return (float)$this->weightDefense;
            case 'research':
            case 'tech':
                return (float)$this->weightResearch;
            default:
                return 1.00;
        }
    }

    public static function getAll()
    {
        return array(self::BALANCED, self::AGGRESSIVE, self::DEFENSIVE, self::ECONOMIC);
    }
}

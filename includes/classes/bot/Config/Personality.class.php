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

    /**
     * Retorna los ratios de presupuesto macro canónicos gobernados 100% por la personalidad
     *
     * @return array ['mines' => float, 'fleet' => float, 'defense' => float, 'research' => float]
     */
    public function getBudgetRatios()
    {
        switch ($this->name) {
            case self::AGGRESSIVE:
            case 'raider':
                return array('mines' => 0.15, 'fleet' => 0.55, 'defense' => 0.10, 'research' => 0.20);

            case self::DEFENSIVE:
            case 'bunker':
            case 'turtle':
                return array('mines' => 0.25, 'fleet' => 0.15, 'defense' => 0.50, 'research' => 0.10);

            case self::ECONOMIC:
            case 'miner':
            case 'minero':
                return array('mines' => 0.55, 'fleet' => 0.10, 'defense' => 0.15, 'research' => 0.20);

            case self::BALANCED:
            case 'equilibrado':
            default:
                return array('mines' => 0.30, 'fleet' => 0.35, 'defense' => 0.15, 'research' => 0.20);
        }
    }

    /**
     * Retorna el multiplicador estratégico de investigación por tecnología según la doctrina del bot
     *
     * @param int $techId
     * @return float
     */
    public function getTechMultiplier($techId)
    {
        $techId = (int)$techId;
        switch ($this->name) {
            case self::AGGRESSIVE:
            case 'raider':
                // Impulso masivo a flotas, computación, motores y armamento
                if (in_array($techId, array(108, 109, 110, 111, 115, 117, 118, 122))) {
                    return 2.20;
                }
                if (in_array($techId, array(120, 121, 124))) {
                    return 1.40;
                }
                return 1.00;

            case self::ECONOMIC:
            case 'miner':
            case 'minero':
                // Impulso masivo a procesamiento NovaRush, astrofísica, IRN y energía
                if (in_array($techId, array(131, 132, 133, 124, 123, 113, 134))) {
                    return 2.50;
                }
                return 1.00;

            case self::DEFENSIVE:
            case 'bunker':
            case 'turtle':
                // Impulso masivo a escudos, blindaje, energía y misilería
                if (in_array($techId, array(110, 111, 113, 117, 122))) {
                    return 2.20;
                }
                return 1.00;

            case self::BALANCED:
            case 'equilibrado':
            default:
                return 1.10;
        }
    }

    public static function getAll()
    {
        return array(self::BALANCED, self::AGGRESSIVE, self::DEFENSIVE, self::ECONOMIC);
    }
}

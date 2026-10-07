<?php

require_once 'includes/classes/bot/Config/DifficultyProfile.class.php';
require_once 'includes/classes/bot/Config/Personality.class.php';
require_once 'includes/classes/bot/Telemetry/DecisionLog.class.php';

/**
 * NovaRush Bot AI v2 - Execution Context
 *
 * Holds all immutable and stateful properties for a single bot turn.
 */
class BotContext
{
    public $botId;
    public $user;
    public $homePlanetId;
    public $difficulty;
    public $personality;
    public $doctrine;
    public $economicModifier;
    public $dailySeed;
    public $decisionLog;
    public $budgetManager;
    public $botRow;

    public function __construct(array $botRow, array $userRow)
    {
        $this->botRow           = $botRow;
        $this->botId            = (int)$botRow['bot_id'];
        $this->homePlanetId     = !empty($botRow['home_planet_id']) ? (int)$botRow['home_planet_id'] : (int)$userRow['id_planet'];
        $this->user             = $userRow;
        
        $diffName               = isset($botRow['difficulty']) ? $botRow['difficulty'] : BotDifficultyProfile::NORMAL;
        $this->difficulty       = new BotDifficultyProfile($diffName);

        $persName               = isset($botRow['personality']) ? $botRow['personality'] : BotPersonality::BALANCED;
        $this->personality      = new BotPersonality($persName);

        $this->doctrine         = isset($botRow['doctrine']) ? $botRow['doctrine'] : 'flexible';
        $this->economicModifier = isset($botRow['economic_modifier']) ? (float)$botRow['economic_modifier'] : 1.0;

        $this->dailySeed        = md5($this->botId . ':' . date('Y-m-d'));
        $this->decisionLog      = new BotDecisionLog($this->botId, $this->dailySeed);

        // Ensure user factor array is loaded
        if (!isset($this->user['factor'])) {
            $this->user['factor'] = getFactors($this->user, 'basic', TIMESTAMP);
        }
    }

    /**
     * Deterministic pseudo-random number generator [0.0, 1.0) seeded per day, bot, and actor/action.
     * Prevents human players from re-trying or "save-scumming" the bot's reaction within the same day.
     */
    public function seededRandom($actorKey = 'global', $actionKey = 'action')
    {
        $hash = md5($this->dailySeed . ':' . $actorKey . ':' . $actionKey);
        // Take first 8 hex characters as uint32
        $val = hexdec(substr($hash, 0, 8));
        return $val / 4294967296.0;
    }

    /**
     * Persist updates to the bot's row in database and local context
     */
    public function updateBotRow(array $fields)
    {
        $db = Database::get();
        $sets = array();
        $params = array(':bot_id' => $this->botId);
        foreach ($fields as $col => $val) {
            $sets[] = "`{$col}` = :{$col}";
            $params[":{$col}"] = $val;
            $this->botRow[$col] = $val;
        }
        if (!empty($sets)) {
            $db->update("UPDATE " . DB_PREFIX . "bots SET " . implode(', ', $sets) . " WHERE bot_id = :bot_id;", $params);
        }
    }
}


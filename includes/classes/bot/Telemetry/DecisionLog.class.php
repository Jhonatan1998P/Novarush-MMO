<?php

/**
 * NovaRush Bot AI v2 - Decision Log
 *
 * Records structured rationales for every strategic decision made by the bot.
 * Allows full explainability in admin audits: what candidates were considered,
 * their scores, the seed used, and why an action was chosen.
 */
class BotDecisionLog
{
    private $botId;
    private $dailySeed;
    private $pendingLogs = array();
    private $recordedThisTurn = array();

    public function __construct($botId, $dailySeed)
    {
        $this->botId = (int)$botId;
        $this->dailySeed = (string)$dailySeed;
    }

    /**
     * Record a decision
     *
     * @param string $decisionType e.g. 'threat_response', 'mine_upgrade', 'strike_target', 'fleetsave'
     * @param string $summary Brief human-readable explanation
     * @param array $candidates Array of evaluated alternatives with their scores
     * @param string $chosenAction Chosen alternative key or description
     * @param float $chosenScore Score achieved by the chosen option
     */
    public function record($decisionType, $summary, array $candidates, $chosenAction, $chosenScore = 0.0)
    {
        $entry = array(
            'bot_id'          => $this->botId,
            'decision_type'   => (string)$decisionType,
            'context_summary' => (string)$summary,
            'candidates_json' => json_encode($candidates),
            'chosen_action'   => (string)$chosenAction,
            'chosen_score'    => (float)$chosenScore,
            'seed_used'       => $this->dailySeed,
            'created_at'      => TIMESTAMP,
        );
        $this->pendingLogs[] = $entry;
        $this->recordedThisTurn[] = $entry;
    }

    public function getRecordedThisTurn()
    {
        return $this->recordedThisTurn;
    }

    /**
     * Flush recorded decisions to the database
     */
    public function flush()
    {
        if (empty($this->pendingLogs)) {
            return;
        }

        $db = Database::get();
        foreach ($this->pendingLogs as $log) {
            $sql = "INSERT INTO " . DB_PREFIX . "bot_decisions 
                    (bot_id, decision_type, context_summary, candidates_json, chosen_action, chosen_score, seed_used, created_at)
                    VALUES (:bot_id, :decision_type, :context_summary, :candidates_json, :chosen_action, :chosen_score, :seed_used, :created_at);";
            
            $db->insert($sql, array(
                ':bot_id'          => $log['bot_id'],
                ':decision_type'   => $log['decision_type'],
                ':context_summary' => $log['context_summary'],
                ':candidates_json' => $log['candidates_json'],
                ':chosen_action'   => $log['chosen_action'],
                ':chosen_score'    => $log['chosen_score'],
                ':seed_used'       => $log['seed_used'],
                ':created_at'      => $log['created_at'],
            ));
        }

        $this->pendingLogs = array();
    }

    public function getPending()
    {
        return $this->pendingLogs;
    }
}

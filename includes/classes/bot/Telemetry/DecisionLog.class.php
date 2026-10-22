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
    public static $disabled = false;
    public static $memoryBuffer = array();
    public static $countsByType = array();

    private $botId;
    private $dailySeed;
    private $pendingLogs = array();
    private $recordedThisTurn = array();

    public static function getMemoryBuffer()
    {
        return self::$memoryBuffer;
    }

    public static function getCountsByType()
    {
        return self::$countsByType;
    }

    public static function resetMemoryBuffer()
    {
        self::$memoryBuffer = array();
        self::$countsByType = array();
    }

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
            'created_at'      => class_exists('BotClock') ? BotClock::get() : TIMESTAMP,
        );

        self::$countsByType[$decisionType] = (self::$countsByType[$decisionType] ?? 0) + 1;
        self::$memoryBuffer[] = $entry;

        if (!self::$disabled || $decisionType === 'quantum_jump_gate' || $decisionType === 'fleet_staging_jump_gate') {
            $this->pendingLogs[] = $entry;
        }
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
        if (defined('SIMULATION_MODE') && SIMULATION_MODE) {
            $this->pendingLogs = array();
            return;
        }

        if (empty($this->pendingLogs)) {
            return;
        }

        // Whitelist check: only authorized Nightmare bots persist decisions to database
        if (!class_exists('BotTelemetryLogger') || !BotTelemetryLogger::isTelemetryAllowed($this->botId)) {
            $this->pendingLogs = array();
            return;
        }

        $db = Database::get();

        // Batch insert for performance
        $chunks = array_chunk($this->pendingLogs, 50);
        foreach ($chunks as $chunk) {
            $valuePlaceholders = array();
            $params = array();
            foreach ($chunk as $i => $log) {
                $p_bot       = ":bot_{$i}";
                $p_type      = ":type_{$i}";
                $p_summary   = ":sum_{$i}";
                $p_cand      = ":cand_{$i}";
                $p_action    = ":act_{$i}";
                $p_score     = ":score_{$i}";
                $p_seed      = ":seed_{$i}";
                $p_created   = ":created_{$i}";

                $valuePlaceholders[] = "({$p_bot}, {$p_type}, {$p_summary}, {$p_cand}, {$p_action}, {$p_score}, {$p_seed}, {$p_created})";

                $params[$p_bot]     = $log['bot_id'];
                $params[$p_type]    = $log['decision_type'];
                $params[$p_summary] = $log['context_summary'];
                $params[$p_cand]    = $log['candidates_json'];
                $params[$p_action]  = $log['chosen_action'];
                $params[$p_score]   = $log['chosen_score'];
                $params[$p_seed]    = $log['seed_used'];
                $params[$p_created] = $log['created_at'];
            }

            $sql = "INSERT INTO " . DB_PREFIX . "bot_decisions 
                    (bot_id, decision_type, context_summary, candidates_json, chosen_action, chosen_score, seed_used, created_at)
                    VALUES " . implode(', ', $valuePlaceholders) . ";";

            $db->insert($sql, $params);
        }

        $this->pendingLogs = array();

        // Probabilistic pruning (2% chance) to clean up decisions older than 7 days
        if (mt_rand(1, 50) === 1) {
            $cutoff = (class_exists('BotClock') ? BotClock::get() : time()) - (7 * 86400);
            try {
                $db->delete("DELETE FROM " . DB_PREFIX . "bot_decisions WHERE bot_id = :bid AND created_at < :cutoff;", array(
                    ':bid'    => $this->botId,
                    ':cutoff' => $cutoff
                ));
            } catch (Throwable $e) {
                // Non-blocking pruning failure
            }
        }
    }

    public function getPending()
    {
        return $this->pendingLogs;
    }
}

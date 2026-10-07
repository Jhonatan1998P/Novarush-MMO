<?php

/**
 * NovaRush Bot AI v2 - Opponent Model
 *
 * Models player behavior and retaliation propensity over time.
 */
class BotOpponentModel
{
    private $botId;

    public function __construct($botId)
    {
        $this->botId = (int)$botId;
    }

    /**
     * Get or initialize profile for a target player
     *
     * @param int $targetUserId
     * @return array
     */
    public function getProfile($targetUserId)
    {
        $db = Database::get();
        $sql = "SELECT * FROM " . DB_PREFIX . "bot_opponents 
                WHERE bot_id = :bot_id AND target_user_id = :target_id LIMIT 1;";

        $row = $db->selectSingle($sql, array(
            ':bot_id'    => $this->botId,
            ':target_id' => (int)$targetUserId,
        ));

        if (!empty($row)) {
            return $row;
        }

        return array(
            'bot_id'              => $this->botId,
            'target_user_id'      => (int)$targetUserId,
            'aggro_score'         => 0.0,
            'activity_profile'    => '{}',
            'last_attacked_at'    => 0,
            'last_retaliated_at'  => 0,
            'notes'               => '',
        );
    }

    /**
     * Record an attack launched by this opponent against the bot
     */
    public function recordOpponentAttack($targetUserId)
    {
        $db = Database::get();
        $sql = "INSERT INTO " . DB_PREFIX . "bot_opponents 
                (bot_id, target_user_id, aggro_score, last_retaliated_at)
                VALUES (:bot_id, :target_id, 1.0, :time)
                ON DUPLICATE KEY UPDATE
                aggro_score = aggro_score + 1.0,
                last_retaliated_at = :time;";

        $db->insert($sql, array(
            ':bot_id'    => $this->botId,
            ':target_id' => (int)$targetUserId,
            ':time'      => TIMESTAMP,
        ));
    }
}

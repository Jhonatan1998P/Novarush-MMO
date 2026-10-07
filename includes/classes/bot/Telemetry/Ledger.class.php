<?php

/**
 * NovaRush Bot AI v2 - Ledger
 *
 * Records economic and fleet inventory changes for auditability.
 */
class BotLedger
{
    private $botId;

    public function __construct($botId)
    {
        $this->botId = (int)$botId;
    }

    public function recordEvent($eventType, $amountMSE, $notes = '')
    {
        // Logs can be written to uni1_bot_logs for backward compatibility
        $db = Database::get();
        $sql = "INSERT INTO " . DB_PREFIX . "bot_logs 
                (bot_id, action_type, details, timestamp)
                VALUES (:bot_id, :type, :details, :time);";

        $db->insert($sql, array(
            ':bot_id'  => $this->botId,
            ':type'    => (string)$eventType,
            ':details' => json_encode(array('amount_mse' => (float)$amountMSE, 'notes' => $notes)),
            ':time'    => TIMESTAMP,
        ));
    }
}

<?php

/**
 * NovaRush Bot AI v2 - Task Scheduler
 *
 * Handles event-driven delayed reactions, timed fleetsave recalls, and scheduled attacks.
 * Replaces fixed 45-second polling with variable latency calibrated per difficulty.
 */
class BotScheduler
{
    /**
     * Schedule a future task for a bot
     *
     * @param int $botId
     * @param string $taskType e.g. 'threat_reaction', 'fleetsave_recall', 'scout_target'
     * @param array $payload Arbitrary task parameters
     * @param int $scheduledAt Unix timestamp
     * @return int Inserted task_id
     */
    public static function scheduleTask($botId, $taskType, array $payload, $scheduledAt)
    {
        $db = Database::get();
        $sql = "INSERT INTO " . DB_PREFIX . "bot_tasks 
                (bot_id, task_type, payload, scheduled_at, created_at, status)
                VALUES (:bot_id, :task_type, :payload, :scheduled_at, :created_at, 'pending');";

        return $db->insert($sql, array(
            ':bot_id'       => (int)$botId,
            ':task_type'    => (string)$taskType,
            ':payload'      => json_encode($payload),
            ':scheduled_at' => (int)$scheduledAt,
            ':created_at'   => TIMESTAMP,
        ));
    }

    /**
     * Get all due tasks for a bot up to the current timestamp
     *
     * @param int $botId
     * @param int|null $currentTime
     * @return array
     */
    public static function getDueTasks($botId, $currentTime = null)
    {
        $time = $currentTime !== null ? $currentTime : TIMESTAMP;
        $db = Database::get();

        $sql = "SELECT * FROM " . DB_PREFIX . "bot_tasks 
                WHERE bot_id = :bot_id 
                  AND status = 'pending' 
                  AND scheduled_at <= :time
                ORDER BY scheduled_at ASC;";

        return $db->select($sql, array(
            ':bot_id' => (int)$botId,
            ':time'   => (int)$time,
        ));
    }

    /**
     * Check if a pending task of a certain type already exists
     *
     * @param int $botId
     * @param string $taskType
     * @return bool
     */
    public static function hasPendingTask($botId, $taskType)
    {
        $db = Database::get();
        $sql = "SELECT COUNT(*) as cnt FROM " . DB_PREFIX . "bot_tasks 
                WHERE bot_id = :bot_id 
                  AND task_type = :task_type 
                  AND status = 'pending';";

        $res = $db->selectSingle($sql, array(
            ':bot_id'    => (int)$botId,
            ':task_type' => (string)$taskType,
        ));

        return !empty($res['cnt']);
    }

    public static function markTaskComplete($taskId)
    {
        $db = Database::get();
        $db->update("UPDATE " . DB_PREFIX . "bot_tasks SET status = 'completed' WHERE task_id = :id;", array(
            ':id' => (int)$taskId,
        ));
    }

    public static function markTaskFailed($taskId, $error)
    {
        $db = Database::get();
        $db->update("UPDATE " . DB_PREFIX . "bot_tasks SET status = 'failed' WHERE task_id = :id;", array(
            ':id' => (int)$taskId,
        ));
    }
}

<?php

require_once 'includes/classes/bot/Kernel/BotClock.class.php';

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

        $db->insert($sql, array(
            ':bot_id'       => (int)$botId,
            ':task_type'    => (string)$taskType,
            ':payload'      => json_encode($payload),
            ':scheduled_at' => (int)$scheduledAt,
            ':created_at'   => TIMESTAMP,
        ));

        return (int)$db->lastInsertId();
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
        $time = $currentTime !== null ? $currentTime : BotClock::get();
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

    /**
     * Check if a pending task of specific type(s) exists for a bot targeting specific coordinates
     *
     * @param int $botId
     * @param string|array $taskTypes
     * @param int $galaxy
     * @param int $system
     * @param int $planet
     * @return bool
     */
    public static function hasPendingTaskForTarget($botId, $taskTypes, $galaxy, $system, $planet)
    {
        $db = Database::get();
        if (!is_array($taskTypes)) {
            $taskTypes = array($taskTypes);
        }
        $placeholders = array();
        $params = array(':bot_id' => (int)$botId);
        foreach ($taskTypes as $idx => $tt) {
            $key = ":tt_{$idx}";
            $placeholders[] = $key;
            $params[$key] = (string)$tt;
        }
        $inClause = implode(',', $placeholders);
        $sql = "SELECT payload FROM " . DB_PREFIX . "bot_tasks 
                WHERE bot_id = :bot_id 
                  AND task_type IN ({$inClause}) 
                  AND status = 'pending';";

        $rows = $db->select($sql, $params);
        if (empty($rows)) {
            return false;
        }

        foreach ($rows as $row) {
            $payload = json_decode($row['payload'], true);
            if (!is_array($payload)) continue;
            $pG = $payload['target_galaxy'] ?? ($payload['galaxy'] ?? null);
            $pS = $payload['target_system'] ?? ($payload['system'] ?? null);
            $pP = $payload['target_planet'] ?? ($payload['planet'] ?? null);
            if ($pG !== null && (int)$pG === (int)$galaxy &&
                $pS !== null && (int)$pS === (int)$system &&
                $pP !== null && (int)$pP === (int)$planet) {
                return true;
            }
        }
        return false;
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

    /**
     * Cancels any pending scheduled tasks matching target coordinates or planet ID.
     */
    public static function cancelPendingTasksForTarget($botId, $galaxy, $system, $planet, $planetId = 0)
    {
        $db = Database::get();
        $botId = (int)$botId;
        $galaxy = (int)$galaxy;
        $system = (int)$system;
        $planet = (int)$planet;
        $planetId = (int)$planetId;

        $rows = $db->select("SELECT task_id, payload FROM " . DB_PREFIX . "bot_tasks 
            WHERE bot_id = :bot_id AND status = 'pending';", array(
            ':bot_id' => $botId
        ));

        if (empty($rows)) {
            return 0;
        }

        $cancelled = 0;
        foreach ($rows as $row) {
            $payload = json_decode($row['payload'], true);
            if (!is_array($payload)) continue;

            $pG = $payload['target_galaxy'] ?? ($payload['galaxy'] ?? null);
            $pS = $payload['target_system'] ?? ($payload['system'] ?? null);
            $pP = $payload['target_planet'] ?? ($payload['planet'] ?? null);
            $pId = $payload['target_planet_id'] ?? ($payload['planet_id'] ?? 0);

            $match = false;
            if ($pG !== null && (int)$pG === $galaxy &&
                $pS !== null && (int)$pS === $system &&
                $pP !== null && (int)$pP === $planet) {
                $match = true;
            } elseif ($planetId > 0 && (int)$pId === $planetId) {
                $match = true;
            }

            if ($match) {
                $db->update("UPDATE " . DB_PREFIX . "bot_tasks SET status = 'cancelled' WHERE task_id = :id;", array(
                    ':id' => (int)$row['task_id']
                ));
                $cancelled++;
            }
        }

        return $cancelled;
    }
}

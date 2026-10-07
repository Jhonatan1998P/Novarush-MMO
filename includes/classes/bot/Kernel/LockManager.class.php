<?php

/**
 * NovaRush Bot AI v2 - Lock Manager
 *
 * Prevents race conditions between bot daemon cycles, cronjobs, and human player actions.
 * Employs MySQL advisory locks per bot, and row-level locking for planetary mutations.
 */
class BotLockManager
{
    private static $acquiredLocks = array();

    /**
     * Acquire an exclusive advisory lock for a bot
     *
     * @param int $botId
     * @param int $timeoutSeconds
     * @return bool True if lock was obtained, false if timed out / busy
     */
    public static function acquireBotLock($botId, $timeoutSeconds = 5)
    {
        $db = Database::get();
        $lockName = 'novarush_bot_' . (int)$botId;

        $res = $db->selectSingle("SELECT GET_LOCK(:lockName, :timeout) as lock_ok;", array(
            ':lockName' => $lockName,
            ':timeout'  => (int)$timeoutSeconds,
        ));

        if (!empty($res['lock_ok'])) {
            self::$acquiredLocks[$botId] = $lockName;
            return true;
        }

        return false;
    }

    /**
     * Release advisory lock for a bot
     *
     * @param int $botId
     */
    public static function releaseBotLock($botId)
    {
        if (!isset(self::$acquiredLocks[$botId])) {
            return;
        }

        $db = Database::get();
        $lockName = self::$acquiredLocks[$botId];

        $db->selectSingle("SELECT RELEASE_LOCK(:lockName);", array(
            ':lockName' => $lockName,
        ));

        unset(self::$acquiredLocks[$botId]);
    }

    /**
     * Acquire a transactional lock on a planet row
     *
     * @param int $planetId
     * @return array Planet row locked with FOR UPDATE
     */
    public static function lockPlanetRow($planetId)
    {
        $db = Database::get();
        $sql = "SELECT * FROM %%PLANETS%% WHERE id = :planetId FOR UPDATE;";
        return $db->selectSingle($sql, array(':planetId' => (int)$planetId));
    }

    /**
     * Acquire a transactional lock on a user row
     *
     * @param int $userId
     * @return array User row locked with FOR UPDATE
     */
    public static function lockUserRow($userId)
    {
        $db = Database::get();
        $sql = "SELECT * FROM %%USERS%% WHERE id = :userId FOR UPDATE;";
        return $db->selectSingle($sql, array(':userId' => (int)$userId));
    }
}

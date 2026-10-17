<?php

/**
 * NovaRush - Unified Bot Clock
 *
 * Provides a central time source for the bot AI system.
 * In normal operation, returns TIMESTAMP (or time()).
 * During simulation or benchmarking, allows setting a deterministic virtual time.
 */
class BotClock
{
    private static $simulatedTime = null;

    public static function set($timestamp)
    {
        self::$simulatedTime = (int)$timestamp;
    }

    public static function setNow($timestamp)
    {
        self::set($timestamp);
    }

    public static function reset()
    {
        self::$simulatedTime = null;
    }

    public static function get()
    {
        if (self::$simulatedTime !== null) {
            return self::$simulatedTime;
        }
        return defined('TIMESTAMP') ? TIMESTAMP : time();
    }

    public static function now()
    {
        return self::get();
    }
}

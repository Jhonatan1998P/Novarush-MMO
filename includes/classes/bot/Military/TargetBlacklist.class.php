<?php

/**
 * NovaRush Bot AI - Anti-Bashing & Target Rotation Blacklist
 *
 * Rules:
 * 1. An AI bot cannot achieve more than 6 victories against a target PLAYER within a 24-hour window.
 *    Upon the 6th victory, the bot immediately releases any tactical lock on that player's planets
 *    and places the player on a strict 24-hour blacklist for THAT bot only.
 * 2. The blacklist is strictly isolated per bot: other bots have independent quotas and tracking.
 * 3. While blacklisted, the bot will not scout, lock, or attack ANY planet belonging to that player.
 * 4. All queries use atomic, non-blocking InnoDB operations to avoid deadlocks and race conditions.
 */
class BotTargetBlacklist
{
    const MAX_BOT_ATTACKS     = 6;
    const MAX_GLOBAL_ATTACKS  = 6;
    const BLACKLIST_DURATION  = 86400; // 24 Hours in seconds

    /**
     * Ensure the dedicated planet cooldown table exists for isolated 1h target discards
     */
    public static function ensureCooldownTableExists()
    {
        static $checked = false;
        if ($checked) return;
        $db = Database::get();
        $db->nativeQuery("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "bot_planet_cooldown (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            bot_id INT UNSIGNED NOT NULL DEFAULT 0,
            planet_id INT UNSIGNED NOT NULL DEFAULT 0,
            galaxy TINYINT UNSIGNED NOT NULL DEFAULT 0,
            system SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            planet TINYINT UNSIGNED NOT NULL DEFAULT 0,
            cooldown_until INT NOT NULL DEFAULT 0,
            reason VARCHAR(64) NOT NULL DEFAULT '',
            created_at INT NOT NULL DEFAULT 0,
            UNIQUE KEY idx_bot_planet_coords (bot_id, planet_id, galaxy, system, planet),
            KEY idx_bot_coords (bot_id, galaxy, system, planet),
            KEY idx_cooldown (cooldown_until)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        $checked = true;
    }

    /**
     * Checks if a target PLAYER is currently blacklisted under Anti-Bashing rules (6 wins in 24h).
     * Strictly checks player-level blacklist only, ignoring planet-specific temporary cooldowns.
     *
     * @param int $botId
     * @param int $targetUserId
     * @param int|null $now
     * @return bool
     */
    public static function isPlayerBlacklisted($botId, $targetUserId, $now = null)
    {
        $now = ($now === null) ? TIMESTAMP : (int)$now;
        $botId = (int)$botId;
        $targetUserId = (int)$targetUserId;

        if ($botId <= 0 || $targetUserId <= 0) {
            return false;
        }

        $db = Database::get();
        $row = $db->selectSingle("SELECT blacklisted_until FROM %%BOT_TARGET_BLACKLIST%% 
            WHERE target_user_id = :uId 
              AND bot_id = :botId 
              AND successful_wins >= :maxWins
              AND blacklisted_until > :now 
            LIMIT 1;", array(
            ':uId'     => $targetUserId,
            ':botId'   => $botId,
            ':maxWins' => self::MAX_BOT_ATTACKS,
            ':now'     => $now,
        ));

        return !empty($row);
    }

    /**
     * Checks if a target player (or target planet/coordinates) is currently blacklisted.
     * Uses non-blocking MVCC read.
     *
     * @param int $botId
     * @param int $targetUserId
     * @param int $planetId
     * @param int $galaxy
     * @param int $system
     * @param int $planet
     * @param int|null $now
     * @return bool
     */
    public static function isBlacklisted($botId, $targetUserId = 0, $planetId = 0, $galaxy = 0, $system = 0, $planet = 0, $now = null)
    {
        $now = ($now === null) ? TIMESTAMP : (int)$now;
        $botId = (int)$botId;
        $targetUserId = (int)$targetUserId;
        $planetId = (int)$planetId;
        $galaxy = (int)$galaxy;
        $system = (int)$system;
        $planet = (int)$planet;

        // If botId is invalid, not blacklisted
        if ($botId <= 0) {
            return false;
        }

        $db = Database::get();

        // If targetUserId is not provided, resolve it from planetId or coordinates
        if ($targetUserId <= 0) {
            if ($planetId > 0) {
                $pRow = $db->selectSingle("SELECT id_owner FROM %%PLANETS%% WHERE id = :pId LIMIT 1;", array(':pId' => $planetId));
                if (!empty($pRow)) {
                    $targetUserId = (int)$pRow['id_owner'];
                }
            } elseif ($galaxy > 0 && $system > 0 && $planet > 0) {
                $pRow = $db->selectSingle("SELECT id_owner, id FROM %%PLANETS%% WHERE galaxy = :g AND system = :s AND planet = :p AND planet_type = 1 LIMIT 1;", array(
                    ':g' => $galaxy, ':s' => $system, ':p' => $planet
                ));
                if (!empty($pRow)) {
                    $targetUserId = (int)$pRow['id_owner'];
                    $planetId = (int)$pRow['id'];
                }
            }
        }

        // 1. Primary Check: Is the target PLAYER blacklisted (reached 6 successful attacks in 24h)?
        if ($targetUserId > 0) {
            $row = $db->selectSingle("SELECT blacklisted_until FROM %%BOT_TARGET_BLACKLIST%% 
                WHERE target_user_id = :uId 
                  AND bot_id = :botId 
                  AND successful_wins >= :maxWins
                  AND blacklisted_until > :now 
                LIMIT 1;", array(
                ':uId'     => $targetUserId,
                ':botId'   => $botId,
                ':maxWins' => self::MAX_BOT_ATTACKS,
                ':now'     => $now,
            ));

            if (!empty($row)) {
                return true;
            }
        }

        // 2. Secondary Check: Is THIS SPECIFIC PLANET on cooldown in dedicated planet cooldown table?
        self::ensureCooldownTableExists();
        $cooldownTable = DB_PREFIX . 'bot_planet_cooldown';
        if ($planetId > 0) {
            $row = $db->selectSingle("SELECT cooldown_until FROM {$cooldownTable} 
                WHERE planet_id = :pId 
                  AND (bot_id = :botId OR bot_id = 0) 
                  AND cooldown_until > :now 
                LIMIT 1;", array(
                ':pId'   => $planetId,
                ':botId' => $botId,
                ':now'   => $now,
            ));

            if (!empty($row)) {
                return true;
            }
        }

        if ($galaxy > 0 && $system > 0 && $planet > 0) {
            $row = $db->selectSingle("SELECT cooldown_until FROM {$cooldownTable} 
                WHERE galaxy = :g AND system = :s AND planet = :p 
                  AND (bot_id = :botId OR bot_id = 0) 
                  AND cooldown_until > :now 
                LIMIT 1;", array(
                ':g'     => $galaxy,
                ':s'     => $system,
                ':p'     => $planet,
                ':botId' => $botId,
                ':now'   => $now,
            ));

            if (!empty($row)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the remaining number of attacks allowed for this bot against a target player
     * within the 24-hour window (NovaRush Anti-Bashing: max 6 attacks per player).
     *
     * @param int $botId
     * @param int $targetUserId
     * @param int|null $now
     * @return int Number of attacks remaining (0 to 6)
     */
    public static function getRemainingAttacks($botId, $targetUserId, $now = null)
    {
        $now = ($now === null) ? TIMESTAMP : (int)$now;
        $botId = (int)$botId;
        $targetUserId = (int)$targetUserId;

        if ($botId <= 0 || $targetUserId <= 0) {
            return 0;
        }

        $db = Database::get();
        $row = $db->selectSingle("SELECT successful_wins, total_attacks, blacklisted_until FROM %%BOT_TARGET_BLACKLIST%% 
            WHERE bot_id = :botId AND target_user_id = :uId LIMIT 1;", array(
            ':botId' => $botId,
            ':uId'   => $targetUserId,
        ));

        if (empty($row)) {
            return self::MAX_BOT_ATTACKS;
        }

        if ((int)$row['blacklisted_until'] > $now) {
            return 0; // Currently blacklisted
        }

        // If blacklist expired, quota resets to max
        if ((int)$row['blacklisted_until'] > 0 && (int)$row['blacklisted_until'] <= $now) {
            return self::MAX_BOT_ATTACKS;
        }

        $used = max((int)$row['successful_wins'], (int)$row['total_attacks']);
        return max(0, self::MAX_BOT_ATTACKS - $used);
    }

    /**
     * Records an attack (or victory) for a bot against a target player.
     * Automatically triggers a 24-hour blacklist on 3 attacks for the player,
     * and releases any active tactical locks on that player's planets.
     *
     * @param int $botId
     * @param int $targetUserId
     * @param int $planetId
     * @param int $galaxy
     * @param int $system
     * @param int $planet
     * @param int|null $now
     * @return array Status array with ['wins' => int, 'blacklisted' => bool, 'expires' => int]
     */
    public static function recordAttackVictory($botId, $targetUserId = 0, $planetId = 0, $galaxy = 0, $system = 0, $planet = 0, $now = null)
    {
        $now = ($now === null) ? TIMESTAMP : (int)$now;
        $botId = (int)$botId;
        $targetUserId = (int)$targetUserId;
        $planetId = (int)$planetId;
        $galaxy = (int)$galaxy;
        $system = (int)$system;
        $planet = (int)$planet;

        if ($botId <= 0) {
            return array('wins' => 0, 'blacklisted' => false, 'expires' => 0);
        }

        $db = Database::get();

        // If targetUserId is not provided, resolve from planet or coordinates
        if ($targetUserId <= 0) {
            if ($planetId > 0) {
                $pRow = $db->selectSingle("SELECT id_owner FROM %%PLANETS%% WHERE id = :pId LIMIT 1;", array(':pId' => $planetId));
                if (!empty($pRow)) {
                    $targetUserId = (int)$pRow['id_owner'];
                }
            } elseif ($galaxy > 0 && $system > 0 && $planet > 0) {
                $pRow = $db->selectSingle("SELECT id, id_owner FROM %%PLANETS%% WHERE galaxy = :g AND system = :s AND planet = :p AND planet_type = 1 LIMIT 1;", array(
                    ':g' => $galaxy, ':s' => $system, ':p' => $planet
                ));
                if (!empty($pRow)) {
                    $targetUserId = (int)$pRow['id_owner'];
                    $planetId = (int)$pRow['id'];
                }
            }
        }

        if ($targetUserId <= 0) {
            return array('wins' => 0, 'blacklisted' => false, 'expires' => 0);
        }

        $expireTime = $now + self::BLACKLIST_DURATION;

        // Atomic Upsert on (bot_id, target_user_id)
        $sql = "INSERT INTO %%BOT_TARGET_BLACKLIST%% (
            bot_id, target_user_id, target_planet_id, galaxy, system, planet, successful_wins, total_attacks, blacklisted_until, created_at, updated_at
        ) VALUES (
            :botId, :uId, :pId, :g, :s, :p, 1, 1, 0, :now, :now
        ) ON DUPLICATE KEY UPDATE
            target_planet_id  = :pId,
            galaxy            = :g,
            system            = :s,
            planet            = :p,
            successful_wins   = IF(blacklisted_until > 0 AND blacklisted_until <= :now, 1, successful_wins + 1),
            total_attacks     = IF(blacklisted_until > 0 AND blacklisted_until <= :now, 1, total_attacks + 1),
            blacklisted_until = IF(successful_wins >= :maxWins, :expireTime, IF(blacklisted_until <= :now, 0, blacklisted_until)),
            updated_at        = :now;";

        $db->insert($sql, array(
            ':botId'      => $botId,
            ':uId'        => $targetUserId,
            ':pId'        => $planetId,
            ':g'          => $galaxy,
            ':s'          => $system,
            ':p'          => $planet,
            ':maxWins'    => self::MAX_BOT_ATTACKS,
            ':expireTime' => $expireTime,
            ':now'        => $now,
        ));

        // Fetch current state for this bot & target user
        $status = $db->selectSingle("SELECT successful_wins, blacklisted_until FROM %%BOT_TARGET_BLACKLIST%% 
            WHERE bot_id = :botId AND target_user_id = :uId LIMIT 1;", array(
            ':botId' => $botId,
            ':uId'   => $targetUserId,
        ));

        $currentWins = !empty($status) ? (int)$status['successful_wins'] : 1;
        $blacklistedUntil = !empty($status) ? (int)$status['blacklisted_until'] : 0;

        // Ensure blacklist is set if wins reach the cap
        if ($currentWins >= self::MAX_BOT_ATTACKS && $blacklistedUntil <= $now) {
            $blacklistedUntil = $expireTime;
            $db->update("UPDATE %%BOT_TARGET_BLACKLIST%% SET blacklisted_until = :exp WHERE bot_id = :botId AND target_user_id = :uId;", array(
                ':exp'   => $expireTime,
                ':botId' => $botId,
                ':uId'   => $targetUserId,
            ));
        }

        $isBlacklisted = ($blacklistedUntil > $now);

        // Rule 1: Upon reaching cap by this bot on this player, release target locks for this bot immediately
        if ($isBlacklisted) {
            self::releaseBotTargetLockForPlayer($botId, $targetUserId);
            if ($galaxy > 0 && $system > 0 && $planet > 0) {
                self::releaseBotTargetLock($botId, $galaxy, $system, $planet);
            }

            // Log event in bot logs
            $logMsg = "Bot #{$botId} alcanzó {$currentWins} victorias contra el jugador #{$targetUserId} ([{$galaxy}:{$system}:{$planet}]). Objetivo liberado y jugador en lista negra de este bot por 24 horas.";
            $db->insert("INSERT INTO %%BOT_LOGS%% (bot_id, action_type, details, timestamp) VALUES (:botId, 'target_blacklisted', :details, :ts);", array(
                ':botId'   => $botId,
                ':details' => $logMsg,
                ':ts'      => $now
            ));
        }

        return array(
            'wins'        => $currentWins,
            'blacklisted' => $isBlacklisted,
            'expires'     => $blacklistedUntil
        );
    }

    /**
     * Sets a temporary cooldown blacklist for a bot against a target player/coordinates
     *
     * @param int $botId
     * @param int $targetUserId
     * @param int $planetId
     * @param int $galaxy
     * @param int $system
     * @param int $planet
     * @param int $duration Cooldown in seconds (default 21600 = 6 hours)
     * @param string $reason
     */
    public static function addCooldown($botId, $targetUserId = 0, $planetId = 0, $galaxy = 0, $system = 0, $planet = 0, $duration = 3600, $reason = '')
    {
        $now = TIMESTAMP;
        $botId = (int)$botId;
        $planetId = (int)$planetId;
        $galaxy = (int)$galaxy;
        $system = (int)$system;
        $planet = (int)$planet;

        if ($botId <= 0) return;

        self::ensureCooldownTableExists();
        $db = Database::get();

        if ($planetId <= 0 && $galaxy > 0 && $system > 0 && $planet > 0) {
            $pRow = $db->selectSingle("SELECT id FROM %%PLANETS%% WHERE galaxy = :g AND system = :s AND planet = :p AND planet_type = 1 LIMIT 1;", array(
                ':g' => $galaxy, ':s' => $system, ':p' => $planet
            ));
            if (!empty($pRow)) {
                $planetId = (int)$pRow['id'];
            }
        }

        $expireTime = $now + (int)$duration;

        $cooldownTable = DB_PREFIX . 'bot_planet_cooldown';
        $sql = "INSERT INTO {$cooldownTable} (
            bot_id, planet_id, galaxy, system, planet, cooldown_until, reason, created_at
        ) VALUES (
            :botId, :pId, :g, :s, :p, :expireTime, :reason, :now
        ) ON DUPLICATE KEY UPDATE
            galaxy         = :g,
            system         = :s,
            planet         = :p,
            cooldown_until = GREATEST(cooldown_until, :expireTime),
            reason         = :reason;";

        $db->insert($sql, array(
            ':botId'      => $botId,
            ':pId'        => $planetId,
            ':g'          => $galaxy,
            ':s'          => $system,
            ':p'          => $planet,
            ':expireTime' => $expireTime,
            ':reason'     => substr((string)$reason, 0, 64),
            ':now'        => $now,
        ));

        if (!empty($reason)) {
            $logMsg = "Bot #{$botId} fijó cooldown de " . round($duration / 3600, 1) . "h para planeta [{$galaxy}:{$system}:{$planet}]: {$reason}";
            $db->insert("INSERT INTO %%BOT_LOGS%% (bot_id, action_type, details, timestamp) VALUES (:botId, 'target_cooldown', :details, :ts);", array(
                ':botId'   => $botId,
                ':details' => $logMsg,
                ':ts'      => $now
            ));
        }
    }

    /**
     * Releases active tactical target locks for a specific bot targeting a specific player
     */
    public static function releaseBotTargetLockForPlayer($botId, $targetUserId)
    {
        $db = Database::get();
        $targetPlanets = $db->select("SELECT galaxy, system, planet FROM %%PLANETS%% WHERE id_owner = :uId;", array(
            ':uId' => (int)$targetUserId
        ));
        if (!empty($targetPlanets)) {
            foreach ($targetPlanets as $tp) {
                self::releaseBotTargetLock($botId, $tp['galaxy'], $tp['system'], $tp['planet']);
            }
        }
    }

    /**
     * Releases active tactical locks for all bots targeting a specific player
     */
    public static function releaseAllBotLocksOnPlayer($targetUserId)
    {
        $db = Database::get();
        $targetPlanets = $db->select("SELECT galaxy, system, planet FROM %%PLANETS%% WHERE id_owner = :uId;", array(
            ':uId' => (int)$targetUserId
        ));
        if (!empty($targetPlanets)) {
            foreach ($targetPlanets as $tp) {
                self::releaseAllBotLocksOnPlanet($tp['galaxy'], $tp['system'], $tp['planet']);
            }
        }
    }

    /**
     * Releases active tactical target lock for a specific bot by coordinates
     */
    public static function releaseBotTargetLock($botId, $galaxy, $system, $planet)
    {
        $db = Database::get();
        $db->update("UPDATE %%BOTS%% SET 
            target_galaxy = 0, 
            target_system = 0, 
            target_planet = 0, 
            target_type = '', 
            target_initial_mse = 0.0, 
            target_lock_time = 0, 
            siege_cycles = 0, 
            siege_spent_mse = 0.0 
        WHERE bot_id = :botId 
          AND target_galaxy = :g 
          AND target_system = :s 
          AND target_planet = :p;", array(
            ':botId' => (int)$botId,
            ':g'     => (int)$galaxy,
            ':s'     => (int)$system,
            ':p'     => (int)$planet,
        ));
    }

    /**
     * Releases active tactical locks for all bots targeting specific coordinates
     */
    public static function releaseAllBotLocksOnPlanet($galaxy, $system, $planet)
    {
        $db = Database::get();
        $db->update("UPDATE %%BOTS%% SET 
            target_galaxy = 0, 
            target_system = 0, 
            target_planet = 0, 
            target_type = '', 
            target_initial_mse = 0.0, 
            target_lock_time = 0, 
            siege_cycles = 0, 
            siege_spent_mse = 0.0 
        WHERE target_galaxy = :g 
          AND target_system = :s 
          AND target_planet = :p;", array(
            ':g' => (int)$galaxy,
            ':s' => (int)$system,
            ':p' => (int)$planet,
        ));
    }

    /**
     * Sets a global scan cooldown for the bot (e.g. 1 hour when all radar candidates are exhausted)
     */
    public static function setGlobalScanCooldown($botId, $duration = 3600, $reason = '')
    {
        self::addCooldown($botId, 0, 0, 0, 0, 0, $duration, $reason);
    }

    /**
     * Checks if the bot is currently under a global scan cooldown
     */
    public static function isGlobalScanOnCooldown($botId, $now = null)
    {
        $now = ($now === null) ? TIMESTAMP : (int)$now;
        $botId = (int)$botId;
        if ($botId <= 0) return false;

        self::ensureCooldownTableExists();
        $db = Database::get();
        $cooldownTable = DB_PREFIX . 'bot_planet_cooldown';
        $row = $db->selectSingle("SELECT cooldown_until FROM {$cooldownTable} 
            WHERE bot_id = :botId AND planet_id = 0 AND galaxy = 0 AND system = 0 AND planet = 0 AND cooldown_until > :now 
            LIMIT 1;", array(
            ':botId' => $botId,
            ':now'   => $now,
        ));
        return !empty($row);
    }

    /**
     * Checks if specific coordinates or planet are currently on tactical cooldown.
     *
     * @param int $botId
     * @param int $galaxy
     * @param int $system
     * @param int $planet
     * @param int|null $now
     * @return bool
     */
    public static function isOnCooldown($botId, $galaxy, $system, $planet, $now = null)
    {
        $now = ($now === null) ? TIMESTAMP : (int)$now;
        self::ensureCooldownTableExists();
        $db = Database::get();
        $cooldownTable = DB_PREFIX . 'bot_planet_cooldown';
        $row = $db->selectSingle("SELECT cooldown_until FROM {$cooldownTable} 
            WHERE galaxy = :g AND system = :s AND planet = :p 
              AND (bot_id = :botId OR bot_id = 0) 
              AND cooldown_until > :now 
            LIMIT 1;", array(
            ':g'     => (int)$galaxy,
            ':s'     => (int)$system,
            ':p'     => (int)$planet,
            ':botId' => (int)$botId,
            ':now'   => $now,
        ));
        return !empty($row);
    }
}

<?php

/**
 * NovaRush Bot AI - Anti-Bashing & Target Rotation Blacklist
 *
 * Rules:
 * 1. An AI bot cannot achieve more than 3 attacks against a target PLAYER within a 24-hour window.
 *    Upon the 3rd attack, the bot immediately releases any tactical lock on that player's planets
 *    and places the player on a strict 24-hour blacklist.
 * 2. If a target player suffers 3 total AI attacks across all bots within 24 hours (global cap = 3),
 *    the player is placed on a global 24-hour blacklist for ALL bots, and all bots release locks.
 * 3. While blacklisted, bots will not scout, lock, or attack ANY planet belonging to that player.
 * 4. All queries use atomic, non-blocking InnoDB operations to avoid deadlocks and race conditions.
 */
class BotTargetBlacklist
{
    const MAX_BOT_ATTACKS     = 3;
    const MAX_GLOBAL_ATTACKS  = 3;
    const BLACKLIST_DURATION  = 86400; // 24 Hours in seconds

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

        // 1. Primary Check: Is the target PLAYER blacklisted (bot-specific or global bot_id=0)?
        if ($targetUserId > 0) {
            $row = $db->selectSingle("SELECT blacklisted_until FROM %%BOT_TARGET_BLACKLIST%% 
                WHERE target_user_id = :uId 
                  AND (bot_id = :botId OR bot_id = 0) 
                  AND blacklisted_until > :now 
                LIMIT 1;", array(
                ':uId'   => $targetUserId,
                ':botId' => $botId,
                ':now'   => $now,
            ));

            if (!empty($row)) {
                return true;
            }
        }

        // 2. Fallback Check: Direct planet_id match
        if ($planetId > 0) {
            $row = $db->selectSingle("SELECT blacklisted_until FROM %%BOT_TARGET_BLACKLIST%% 
                WHERE target_planet_id = :pId 
                  AND (bot_id = :botId OR bot_id = 0) 
                  AND blacklisted_until > :now 
                LIMIT 1;", array(
                ':pId'   => $planetId,
                ':botId' => $botId,
                ':now'   => $now,
            ));

            if (!empty($row)) {
                return true;
            }
        }

        // 3. Fallback Check: Direct coordinates match
        if ($galaxy > 0 && $system > 0 && $planet > 0) {
            $row = $db->selectSingle("SELECT blacklisted_until FROM %%BOT_TARGET_BLACKLIST%% 
                WHERE galaxy = :g AND system = :s AND planet = :p 
                  AND (bot_id = :botId OR bot_id = 0) 
                  AND blacklisted_until > :now 
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
        $isBlacklisted = ($blacklistedUntil > $now);

        // Rule 1: Upon 3rd attack by this bot on this player, release target locks immediately
        if ($isBlacklisted) {
            self::releaseBotTargetLockForPlayer($botId, $targetUserId);
            if ($galaxy > 0 && $system > 0 && $planet > 0) {
                self::releaseBotTargetLock($botId, $galaxy, $system, $planet);
            }

            // Log event in bot logs
            $logMsg = "Bot #{$botId} alcanzó {$currentWins} ataques contra el jugador #{$targetUserId} ([{$galaxy}:{$system}:{$planet}]). Objetivo liberado y jugador en lista negra por 24 horas.";
            $db->insert("INSERT INTO %%BOT_LOGS%% (bot_id, action_type, details, timestamp) VALUES (:botId, 'target_blacklisted', :details, :ts);", array(
                ':botId'   => $botId,
                ':details' => $logMsg,
                ':ts'      => $now
            ));
        }

        // Rule 2: Global cap across all bots (3 attacks per player within 24h)
        $globalRow = $db->selectSingle("SELECT SUM(successful_wins) as total_wins FROM %%BOT_TARGET_BLACKLIST%% 
            WHERE target_user_id = :uId AND bot_id > 0 AND (blacklisted_until > :now OR updated_at >= :dayAgo);", array(
            ':uId'    => $targetUserId,
            ':now'    => $now,
            ':dayAgo' => $now - self::BLACKLIST_DURATION,
        ));

        $totalGlobalWins = !empty($globalRow['total_wins']) ? (int)$globalRow['total_wins'] : $currentWins;
        if ($totalGlobalWins >= self::MAX_GLOBAL_ATTACKS) {
            $sqlGlobal = "INSERT INTO %%BOT_TARGET_BLACKLIST%% (
                bot_id, target_user_id, target_planet_id, galaxy, system, planet, successful_wins, total_attacks, blacklisted_until, created_at, updated_at
            ) VALUES (
                0, :uId, :pId, :g, :s, :p, :gWins, :gWins, :expireTime, :now, :now
            ) ON DUPLICATE KEY UPDATE
                target_planet_id  = :pId,
                galaxy            = :g,
                system            = :s,
                planet            = :p,
                successful_wins   = :gWins,
                blacklisted_until = :expireTime,
                updated_at        = :now;";

            $db->insert($sqlGlobal, array(
                ':uId'        => $targetUserId,
                ':pId'        => $planetId,
                ':g'          => $galaxy,
                ':s'          => $system,
                ':p'          => $planet,
                ':gWins'      => $totalGlobalWins,
                ':expireTime' => $expireTime,
                ':now'        => $now,
            ));

            // Release target locks for ANY bot that targeted this player
            self::releaseAllBotLocksOnPlayer($targetUserId);
            if ($galaxy > 0 && $system > 0 && $planet > 0) {
                self::releaseAllBotLocksOnPlanet($galaxy, $system, $planet);
            }

            $gLogMsg = "Jugador #{$targetUserId} alcanzó {$totalGlobalWins} ataques globales de IA en 24h. Entra en lista negra global de 24 horas para todos los bots.";
            $db->insert("INSERT INTO %%BOT_LOGS%% (bot_id, action_type, details, timestamp) VALUES (0, 'global_blacklist', :details, :ts);", array(
                ':details' => $gLogMsg,
                ':ts'      => $now
            ));

            $isBlacklisted = true;
            $blacklistedUntil = $expireTime;
        }

        return array(
            'wins'        => $currentWins,
            'blacklisted' => $isBlacklisted,
            'expires'     => $blacklistedUntil
        );
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
}

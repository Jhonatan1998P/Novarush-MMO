<?php

class PremiumEconomy
{
    private static $settings = null;

    public static function get($key, $default = 0)
    {
        if (self::$settings === null) {
            self::$settings = array();
            $db = Database::get();
            $rows = $db->select('SELECT setting_key, setting_value FROM %%PREMIUM_SETTINGS%%;');
            foreach ($rows as $r) {
                self::$settings[$r['setting_key']] = $r['setting_value'];
            }
        }
        return isset(self::$settings[$key]) ? (float) self::$settings[$key] : (float) $default;
    }

    public static function mse($m, $c, $d)
    {
        return $m + (2 * $c) + (4 * $d);
    }

    public static function baseIncomeMSE()
    {
        $cfg = Config::get();
        return self::mse($cfg->metal_basic_income, $cfg->crystal_basic_income, $cfg->deuterium_basic_income)
             * $cfg->resource_multiplier;
    }

    public static function playerHourlyMSE($userId)
    {
        $db = Database::get();
        $r = $db->selectSingle(
            'SELECT COALESCE(SUM(metal_perhour),0) m, COALESCE(SUM(crystal_perhour),0) c,
                    COALESCE(SUM(deuterium_perhour),0) d, COUNT(*) n
             FROM %%PLANETS%% WHERE id_owner = :u AND planet_type = 1;',
            array(':u' => $userId)
        );
        return self::mse($r['m'], $r['c'], $r['d']) + ($r['n'] * self::baseIncomeMSE());
    }

    public static function playerHourlyProduction($userId)
    {
        $db = Database::get();
        $cfg = Config::get();
        $r = $db->selectSingle(
            'SELECT COALESCE(SUM(metal_perhour),0) m, COALESCE(SUM(crystal_perhour),0) c,
                    COALESCE(SUM(deuterium_perhour),0) d, COUNT(*) n
             FROM %%PLANETS%% WHERE id_owner = :u AND planet_type = 1;',
            array(':u' => $userId)
        );
        $count = (int) $r['n'];
        $baseM = (float) $cfg->metal_basic_income * (float) $cfg->resource_multiplier;
        $baseC = (float) $cfg->crystal_basic_income * (float) $cfg->resource_multiplier;
        $baseD = (float) $cfg->deuterium_basic_income * (float) $cfg->resource_multiplier;

        return array(
            'metal'     => (float) $r['m'] + ($count * $baseM),
            'crystal'   => (float) $r['c'] + ($count * $baseC),
            'deuterium' => (float) $r['d'] + ($count * $baseD)
        );
    }

    public static function indexedHourlyMSE($userId)
    {
        return max(
            self::playerHourlyMSE($userId),
            self::get('floor_alpha', 0.5) * self::get('prs', 0),
            self::baseIncomeMSE()
        );
    }

    /**
     * Tope suave asintótico: crece linealmente al inicio y satura en $cap.
     * Fórmula: Cap * (1 - e^(-raw / Cap))
     */
    public static function softCap($raw, $cap)
    {
        if ($cap <= 0 || $raw <= 0) {
            return $raw;
        }
        return $cap * (1.0 - exp(-$raw / $cap));
    }

    public static function buffValueAM(array $effects, $hours)
    {
        $v = 0;
        foreach ($effects as $cat => $mag) {
            $v += abs($mag) * 100 * self::get('w_' . $cat, 0) * self::get('v1_am', 10);
        }
        return $v * pow($hours / 24.0, self::get('dur_exp', 0.9));
    }

    public static function escalated($base, $growth, $n)
    {
        return $base * pow($growth, max(0, $n));
    }

    public static function today()
    {
        $cfg = Config::get();
        $tz = !empty($cfg->timezone) ? $cfg->timezone : 'Europe/Berlin';
        $dt = new DateTime('now', new DateTimeZone($tz));
        return $dt->format('Y-m-d');
    }

    public static function dailyCount($userId, $key)
    {
        $db = Database::get();
        $r = $db->selectSingle(
            'SELECT counter FROM %%PREMIUM_DAILY%% WHERE user_id = :u AND day = :d AND counter_key = :k;',
            array(':u' => $userId, ':d' => self::today(), ':k' => $key)
        );
        return $r ? (int) $r['counter'] : 0;
    }

    public static function dailyIncrement($userId, $key)
    {
        $db = Database::get();
        $db->insert(
            'INSERT INTO %%PREMIUM_DAILY%% (user_id, day, counter_key, counter) VALUES (:u, :d, :k, 1)
             ON DUPLICATE KEY UPDATE counter = counter + 1;',
            array(':u' => $userId, ':d' => self::today(), ':k' => $key)
        );
    }

    /**
     * Débito atómico con verificación de saldo y actualización síncrona en memoria.
     */
    public static function debit(&$USER, $currency, $amount, $source, $elementId = 0, $meta = '')
    {
        global $resource;
        if (!isset($resource[$currency])) {
            return false;
        }
        $col = $resource[$currency];
        $amount = (float) ceil($amount);
        if ($amount <= 0) {
            return true;
        }

        $db = Database::get();
        $db->update(
            "UPDATE %%USERS%% SET $col = $col - :a WHERE id = :u AND $col >= :a;",
            array(':a' => $amount, ':u' => $USER['id'])
        );

        if ($db->rowCount() < 1) {
            return false;
        }

        $USER[$col] -= $amount;
        self::ledger($USER['id'], $currency, -$amount, $USER[$col], $source, $elementId, $meta);
        return true;
    }

    public static function credit(&$USER, $currency, $amount, $source, $elementId = 0, $meta = '')
    {
        global $resource;
        if (!isset($resource[$currency])) {
            return;
        }
        $col = $resource[$currency];
        $amount = (float) floor($amount);
        if ($amount <= 0) {
            return;
        }

        $db = Database::get();
        $db->update(
            "UPDATE %%USERS%% SET $col = $col + :a WHERE id = :u;",
            array(':a' => $amount, ':u' => $USER['id'])
        );

        $USER[$col] += $amount;
        self::ledger($USER['id'], $currency, $amount, $USER[$col], $source, $elementId, $meta);
    }

    public static function ledger($uid, $cur, $delta, $after, $source, $el, $meta)
    {
        $db = Database::get();
        $db->insert(
            'INSERT INTO %%PREMIUM_LEDGER%% (user_id, currency, delta, balance_after, source, element_id, meta, created_at)
             VALUES (:u, :c, :d, :b, :s, :e, :m, :t);',
            array(
                ':u' => $uid,
                ':c' => $cur,
                ':d' => $delta,
                ':b' => $after,
                ':s' => $source,
                ':e' => $el,
                ':m' => substr($meta, 0, 255),
                ':t' => TIMESTAMP
            )
        );
    }
}

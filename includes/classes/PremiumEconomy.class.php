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
     * Retorna la producción horaria de imperio promedio de todos los jugadores activos
     * del servidor (humanos y bots) para Metal, Cristal y Deuterio.
     * Criterio de activo: no baneado (bana = 0), no vacaciones (urlaubs_modus = 0),
     * no administrador pasivo (authlevel < 3).
     *
     * @return array ['metal' => float, 'crystal' => float, 'deuterium' => float, 'count' => int]
     */
    public static function serverActiveEmpireAverageProduction()
    {
        static $cachedAvg = null;
        if ($cachedAvg !== null) {
            return $cachedAvg;
        }

        $db = Database::get();
        $cfg = Config::get();
        $rows = $db->select(
            "SELECT u.id,
                    COALESCE(SUM(p.metal_perhour), 0) as m,
                    COALESCE(SUM(p.crystal_perhour), 0) as c,
                    COALESCE(SUM(p.deuterium_perhour), 0) as d,
                    COUNT(p.id) as n
             FROM %%USERS%% u
             JOIN %%PLANETS%% p ON u.id = p.id_owner AND p.planet_type = 1 AND p.destruyed = 0
             WHERE u.bana = 0 AND u.urlaubs_modus = 0 AND u.authlevel < 3
             GROUP BY u.id;"
        );

        $baseM = (float) $cfg->metal_basic_income * (float) $cfg->resource_multiplier;
        $baseC = (float) $cfg->crystal_basic_income * (float) $cfg->resource_multiplier;
        $baseD = (float) $cfg->deuterium_basic_income * (float) $cfg->resource_multiplier;

        if (empty($rows)) {
            $cachedAvg = array(
                'metal'     => max(1000.0, $baseM),
                'crystal'   => max(500.0, $baseC),
                'deuterium' => max(250.0, $baseD),
                'count'     => 1
            );
            return $cachedAvg;
        }

        $totalM = 0.0;
        $totalC = 0.0;
        $totalD = 0.0;
        $count = count($rows);

        foreach ($rows as $r) {
            $n = (int)$r['n'];
            $totalM += (float)$r['m'] + ($n * $baseM);
            $totalC += (float)$r['c'] + ($n * $baseC);
            $totalD += (float)$r['d'] + ($n * $baseD);
        }

        $cachedAvg = array(
            'metal'     => $totalM / $count,
            'crystal'   => $totalC / $count,
            'deuterium' => $totalD / $count,
            'count'     => $count
        );
        return $cachedAvg;
    }

    /**
     * Calcula el coste de recursos para comprar 1.000 MO en la tienda Regular (Feria).
     * Fórmula oficial: 6 horas de producción horaria promedio de todos los imperios
     * activos del servidor para el tipo de recurso correspondiente.
     *
     * @param int $elementId 2301 (Metal), 2302 (Cristal), 2303 (Deuterio)
     * @return float Coste en unidades de recurso
     */
    public static function getFairResourceCost($elementId)
    {
        $avg = self::serverActiveEmpireAverageProduction();
        if ($elementId == 2301) {
            return (float) ceil(6.0 * $avg['metal']);
        } elseif ($elementId == 2302) {
            return (float) ceil(6.0 * $avg['crystal']);
        } elseif ($elementId == 2303) {
            return (float) ceil(6.0 * $avg['deuterium']);
        }
        return 0.0;
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
        $map = array(921 => 'darkmatter', 922 => 'antimatter', 923 => 'stardust', 924 => 'container');
        $col = $resource[$currency] ?? ($map[$currency] ?? null);
        if (!$col) {
            return false;
        }
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
        $map = array(921 => 'darkmatter', 922 => 'antimatter', 923 => 'stardust', 924 => 'container');
        $col = $resource[$currency] ?? ($map[$currency] ?? null);
        if (!$col) {
            return;
        }
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

        if (!class_exists('BotTelemetryLogger')) {
            $botLoggerPath = 'includes/classes/bot/Telemetry/BotTelemetryLogger.class.php';
            if (file_exists($botLoggerPath)) {
                require_once $botLoggerPath;
            }
        }
        if (class_exists('BotTelemetryLogger')) {
            BotTelemetryLogger::onLedgerEntry($uid, $cur, $delta, $after, $source, $el, $meta);
        }
    }
}

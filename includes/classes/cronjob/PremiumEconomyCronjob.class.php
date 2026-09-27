<?php

require_once 'includes/classes/cronjob/CronjobTask.interface.php';

class PremiumEconomyCronjob implements CronjobTask
{
    public function run()
    {
        $db = Database::get();
        $sevenDaysAgo = TIMESTAMP - (7 * 86400);

        // Jugadores activos en los últimos 7 días no baneados
        $users = $db->select(
            'SELECT id FROM %%USERS%% WHERE onlinetime >= :t AND bana = 0;',
            array(':t' => $sevenDaysAgo)
        );

        if (empty($users)) {
            // Fallback a todos los jugadores no baneados
            $users = $db->select('SELECT id FROM %%USERS%% WHERE bana = 0;');
        }

        $productions = array();
        foreach ($users as $u) {
            $p = PremiumEconomy::playerHourlyMSE((int)$u['id']);
            if ($p > 0) {
                $productions[] = (float)$p;
            }
        }

        if (!empty($productions)) {
            sort($productions, SORT_NUMERIC);
            $count = count($productions);
            $mid = (int)floor($count / 2);
            if ($count % 2 === 0) {
                $prs = ($productions[$mid - 1] + $productions[$mid]) / 2.0;
            } else {
                $prs = (float)$productions[$mid];
            }
        } else {
            $prs = PremiumEconomy::baseIncomeMSE();
        }

        if ($prs <= 0) {
            $prs = PremiumEconomy::baseIncomeMSE();
        }

        $db->replace(
            "REPLACE INTO %%PREMIUM_SETTINGS%% (setting_key, setting_value, updated_at)
             VALUES ('prs', :val, :ts);",
            array(
                ':val' => (string)round($prs, 2),
                ':ts'  => TIMESTAMP
            )
        );

        return $prs;
    }
}

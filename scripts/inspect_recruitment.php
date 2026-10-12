<?php
define('MODE', 'CRON');
define('ROOT_PATH', str_replace('\\', '/', getcwd()) . '/');
set_include_path(ROOT_PATH);

require 'includes/common.php';
require 'includes/vars/General.php';

$db = Database::get();
$botIds = array(1007, 1010, 1011);

foreach ($botIds as $botId) {
    $bot = $db->selectSingle("SELECT b.bot_id, u.username, b.personality, b.difficulty FROM uni1_bots b LEFT JOIN uni1_users u ON u.id = b.bot_id WHERE b.bot_id = :id;", array(':id' => $botId));
    echo "========================================================================================\n";
    echo "🤖 BOT {$bot['bot_id']} - {$bot['username']} (Personalidad: {$bot['personality']} | Dificultad: {$bot['difficulty']})\n";
    echo "========================================================================================\n";

    $planets = $db->select("SELECT * FROM uni1_planets WHERE id_owner = :uid ORDER BY id ASC;", array(':uid' => $botId));
    
    $grandTotalFleetQueue = 0;
    $grandTotalDefQueue = 0;

    foreach ($planets as $p) {
        $pId = (int)$p['id'];
        $coords = "[{$p['galaxy']}:{$p['system']}:{$p['planet']}]";
        echo "\n📍 Planeta #{$pId} — {$p['name']} {$coords}\n";

        $queue = !empty($p['b_hangar_id']) ? @unserialize($p['b_hangar_id']) : array();
        $qFleet = array();
        $qDef = array();

        if (!empty($queue)) {
            foreach ($queue as $item) {
                $uId = (int)$item[0];
                $cnt = (int)$item[1];
                $uName = $resource[$uId] ?? "ID #{$uId}";
                $isDef = in_array($uId, $reslist['defense']) || in_array($uId, $reslist['missile']);
                if ($isDef) {
                    $qDef[] = number_format($cnt) . "x {$uName}";
                    $grandTotalDefQueue += $cnt;
                } else {
                    $qFleet[] = number_format($cnt) . "x {$uName}";
                    $grandTotalFleetQueue += $cnt;
                }
            }
        }

        echo "   • Reclutando en Hangar (Flota):    " . (!empty($qFleet) ? implode(', ', $qFleet) : "Ninguna") . "\n";
        echo "   • Reclutando en Hangar (Defensa):  " . (!empty($qDef) ? implode(', ', $qDef) : "Ninguna") . "\n";

        // Conteo en superficie
        $sFleetCnt = 0;
        foreach ($reslist['fleet'] as $fId) {
            $col = $resource[$fId] ?? null;
            if ($col && !empty($p[$col])) $sFleetCnt += (int)$p[$col];
        }
        $sDefCnt = 0;
        foreach ($reslist['defense'] as $dId) {
            $col = $resource[$dId] ?? null;
            if ($col && !empty($p[$col])) $sDefCnt += (int)$p[$col];
        }
        foreach ($reslist['missile'] as $mId) {
            $col = $resource[$mId] ?? null;
            if ($col && !empty($p[$col])) $sDefCnt += (int)$p[$col];
        }
        echo "   • Total ya presente en superficie: " . number_format($sFleetCnt) . " naves / " . number_format($sDefCnt) . " defensas y misiles\n";
    }

    echo "\n>>> TOTAL IMPERIAL EN PRODUCCIÓN: " . number_format($grandTotalFleetQueue) . " naves de flota | " . number_format($grandTotalDefQueue) . " defensas y misiles\n\n";
}

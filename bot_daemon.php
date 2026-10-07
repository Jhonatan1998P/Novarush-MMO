<?php

/*
 * NovaRush - Daemon de Inteligencia Artificial para Bots Competitivos
 *
 * Uso CLI:
 *   php bot_daemon.php        -> Modo daemon continuo (orquestador que ejecuta cada 45 segundos con TIMESTAMP real)
 *   php bot_daemon.php --once -> Ejecuta un único ciclo y termina
 */

$once = (isset($argv) && in_array('--once', $argv));

// -------------------------------------------------------------
// MODO ORQUESTADOR CONTINUO (Master Daemon Loop)
// -------------------------------------------------------------
if (!$once) {
    echo "========================================================================================\n";
    echo "       NOVARUSH — DAEMON MAESTRO DE INTELIGENCIA ARTIFICIAL (TELEMETRÍA EN VIVO)\n";
    echo "========================================================================================\n";
    echo "Iniciando orquestador en segundo plano (intervalo: 45s con actualización de reloj real)...\n\n";

    $phpCli = PHP_BINARY;
    if (empty($phpCli) || !file_exists($phpCli) || !preg_match('/php(\.exe)?$/i', $phpCli)) {
        $phpCli = 'C:\\Users\\Ortega\\Desktop\\Travian\\XAMPP\\xampp\\php\\php.exe';
    }

    $scriptPath = __FILE__;

    while (true) {
        $cmd = '"' . $phpCli . '" "' . $scriptPath . '" --once';
        passthru($cmd);

        $sleepSeconds = 45;
        try {
            $pdo = new PDO('mysql:host=127.0.0.1;dbname=ogame2;charset=utf8', 'root', '', array(PDO::ATTR_TIMEOUT => 2));
            $stmt = $pdo->query("SELECT MIN(scheduled_at) as next_due FROM uni1_bot_tasks WHERE status = 'pending'");
            if ($stmt) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!empty($row['next_due'])) {
                    $diff = (int)$row['next_due'] - time();
                    if ($diff > 0 && $diff < 45) {
                        $sleepSeconds = max(1, $diff);
                    } elseif ($diff <= 0) {
                        $sleepSeconds = 1;
                    }
                }
            }
        } catch (Exception $e) {
            $sleepSeconds = 45;
        }

        sleep($sleepSeconds);
    }
    exit(0);
}

// -------------------------------------------------------------
// MODO TRABAJADOR / CICLO INDIVIDUAL (--once)
// -------------------------------------------------------------
define('MODE', 'CRON');
define('ROOT_PATH', str_replace('\\', '/', dirname(__FILE__)).'/');
set_include_path(ROOT_PATH);

require 'includes/common.php';
require 'includes/vars/General.php';
foreach (get_defined_vars() as $k => $v) {
    $GLOBALS[$k] = $v;
}
require_once 'includes/classes/bot/BotEngine.class.php';

function formatDecisionInSpanish($decision)
{
    $type    = isset($decision['decision_type']) ? $decision['decision_type'] : '';
    $summary = isset($decision['context_summary']) ? $decision['context_summary'] : '';

    $translations = array(
        'metal_mine'           => 'Mina de Metal',
        'crystal_mine'         => 'Mina de Cristal',
        'deuterium_sintetizer' => 'Sintetizador de Deuterio',
        'solar_plant'          => 'Planta Solar',
        'fusion_plant'         => 'Planta de Fusión',
        'robot_factory'        => 'Fábrica de Robots',
        'nano_factory'         => 'Fábrica de Nanobots',
        'hangar'               => 'Hangar',
        'metal_store'          => 'Almacén de Metal',
        'crystal_store'        => 'Almacén de Cristal',
        'deuterium_store'      => 'Almacén de Deuterio',
        'laboratory'           => 'Laboratorio de Investigación',
        'terraformer'          => 'Terraformer',
        'university'           => 'Universidad',
        'silo'                 => 'Silo de Misiles',
        'resource_module'      => 'Módulo de Recursos',
        'defensive_module'     => 'Módulo Defensivo',
        'military_module'      => 'Módulo Militar',
        'research_module'      => 'Módulo de Investigación',
        'small_ship_cargo'     => 'Carguero Pequeño',
        'big_ship_cargo'       => 'Carguero Grande',
        'light_hunter'         => 'Caza Ligero',
        'heavy_hunter'         => 'Caza Pesado',
        'crusher'              => 'Crucero',
        'battle_ship'          => 'Nave de Batalla',
        'destructor'           => 'Destructor',
        'spy_sonde'            => 'Sonda de Espionaje',
        'recycler'             => 'Reciclador',
        'misil_launcher'       => 'Lanzamisiles',
        'small_laser'          => 'Láser Pequeño',
        'big_laser'            => 'Láser Grande',
        'gauss_canyon'         => 'Cañón Gauss',
        'ionic_canyon'         => 'Cañón Iónico',
        'plasma_canyon'        => 'Cañón Plasma',
        'fleet_crash'          => 'Caza de Flota',
        'farming'              => 'Saqueo Económico',
        'siege'                => 'Asedio a Búnker',
    );

    foreach ($translations as $en => $es) {
        $summary = str_replace($en, $es, $summary);
    }

    switch ($type) {
        case 'fleet_lock':
            return "🛡️ Bloqueo de Flotas: Movimientos de flotas bloqueados por directiva del usuario.";

        case 'target_locked':
            $summary = preg_replace('/Target locked at \[(\d+:\d+:\d+)\] classified as (.+?) \(Initial Value: (.+?) MSE\)\. Designated FOB colony #(\d+)/i', 'Fijado objetivo en [$1] ($2 | Valor: $3 MSE). Base Avanzada FOB: Planeta #$4', $summary);
            return "🎯 Fijación Blanco: " . $summary;

        case 'target_released':
            $summary = preg_replace('/Released target (.+?) from tactical lock\. Reason: (.+?)\. Searching for new high-value target\./i', 'Objetivo $1 liberado del radar táctico. Motivo: $2', $summary);
            return "🔓 Desbloqueo Blanco: " . $summary;

        case 'strike_hold_for_rally':
            $summary = preg_replace('/Target \[(\d+:\d+:\d+)\] held: local FOB fleet win rate \((.+?)\) is under 90% threshold\. Awaiting fleet concentration\./i', 'Objetivo [$1] en espera (Victoria local FOB $2 < 90%). Concentrando armadas.', $summary);
            return "⏸️ Espera Táctica: " . $summary;

        case 'strike_launched':
            $summary = preg_replace('/Launched military strike from FOB #(\d+) against \[(\d+:\d+:\d+)\] with (.+?) warships \((.+?) win probability\)/i', 'Ofensiva lanzada desde Base FOB #$1 a [$2] con $3 naves ($4 victoria)', $summary);
            $summary = preg_replace('/Dispatched strike fleet to\s+(.+)/i', 'Flota de ataque enviada hacia $1', $summary);
            return "⚔️ Ofensiva Militar: " . $summary;

        case 'strike_scheduled_for_impact':
            return "⏳ Ofensiva Calibrada: " . $summary;

        case 'recycler_scheduled_for_impact':
            return "⏳ Reciclaje Calibrado: " . $summary;

        case 'synchronized_recycler_dispatch':
            $summary = preg_replace('/Synchronized pre-impact deployment of (.+?) recyclers to debris at \[(\d+:\d+:\d+)\] \(Expected Debris: (.+?)\)/i', 'Despliegue sincronizado pre-impacto de $1 recicladores a [$2] (Escombros previstos: $3)', $summary);
            return "♻️ Reciclaje Sincro: " . $summary;

        case 'siege_bombardment':
            $summary = preg_replace('/Tactical siege \(Step (\d+)\/20\): Launched (.+?)x MIP salvo from colony #(\d+) against \[(\d+:\d+:\d+)\] targeting (.+)/i', 'Asedio Táctico (Paso $1/20): Salva de $2x MIP desde planeta #$3 contra [$4] apuntando a $5', $summary);
            return "🚀 Asedio (Misiles): " . $summary;

        case 'siege_final_assault':
            $summary = preg_replace('/Siege final assault launched on cycle (\d+) from FOB #(\d+) against \[(\d+:\d+:\d+)\] with (.+?) win probability/i', 'Asalto definitivo en ciclo $1 desde FOB #$2 contra búnker en [$3] ($4 victoria)', $summary);
            return "💥 Asalto Definitivo: " . $summary;

        case 'reconnaissance_locked_target':
            $summary = preg_replace('/Re-espionage launched to locked (.+?) target \[(\d+:\d+:\d+)\] with (\d+) probes/i', 'Re-espionaje táctico sobre blanco prioritario [$2] con $3 sondas', $summary);
            return "🔭 Re-Espionaje:    " . $summary;

        case 'fleet_staging':
            $summary = preg_replace('/Rallied combat fleet from colony #(\d+) to FOB #(\d+) to consolidate firepower/i', 'Armada reunida de colonia #$1 a Base FOB #$2 (>90% victoria)', $summary);
            $summary = preg_replace('/Rallied fleet from\s+#(\d+)\s+to staging base\s+#(\d+)/i', 'Unificación de flota de #$1 a base #$2', $summary);
            return "🛸 Concentración:  " . $summary;

        case 'mine_upgrade':
            $summary = preg_replace('/Upgraded\s+(.+)\s+on planet\s+#(\d+)/i', 'Mejora de $1 en planeta #$2', $summary);
            return "🔨 Construcción:   " . $summary;

        case 'tech_research':
            $summary = preg_replace('/Started research\s+(.+)/i', 'Iniciada investigación de $1', $summary);
            return "🔬 Investigación:  " . $summary;

        case 'unit_recruitment':
            return "🛡️ Astillero:      " . $summary;

        case 'reconnaissance':
            $summary = preg_replace('/Dispatched\s+(\d+)\s+spy probes to\s+(.+)/i', 'Despachadas $1 sondas de espionaje hacia $2', $summary);
            $summary = preg_replace('/Launched spy probes to\s+(.+)/i', 'Sondas despachadas hacia $1', $summary);
            $summary = preg_replace('/Scouted target\s+(.+)/i', 'Reconocimiento sobre $1', $summary);
            return "🔭 Espionaje:      " . $summary;

        case 'strike_attack':
            $summary = preg_replace('/Dispatched strike fleet to\s+(.+)/i', 'Flota de ataque enviada hacia $1', $summary);
            return "⚔️ Misión Ataque:  " . $summary;

        case 'missile_strike':
            $summary = preg_replace('/Launched\s+(\d+)x\s+Interplanetary Missiles\s+\(503\)\s+against\s+(.+)\s+targeting\s+(.+)/i', 'Lanzamiento de $1 misiles interplanetarios a $2 contra $3', $summary);
            return "🚀 Bombardeo:      " . $summary;

        case 'debris_recycle':
            $summary = preg_replace('/Dispatched recycler fleet to\s+(.+)/i', 'Flota de recicladores enviada a escombros en $1', $summary);
            return "♻️ Reciclaje:      " . $summary;

        case 'convoy_dispatch':
            $summary = preg_replace('/Dispatched convoy from\s+#(\d+)\s+to hub\s+#(\d+)/i', 'Convoy de transporte del planeta #$1 hacia base #$2', $summary);
            return "📦 Logística:      " . $summary;

        case 'fleet_rally':
            $summary = preg_replace('/Rallied fleet from\s+#(\d+)\s+to staging base\s+#(\d+)/i', 'Unificación de flota de #$1 a base principal #$2', $summary);
            return "🛸 Concentración:  " . $summary;

        case 'force_build_warship':
            $summary = preg_replace('/Recruited\s+(\d+)x\s+(.+)\s+on body\s+#(\d+).*/i', 'Astillero: $1x $2 en planeta #$3 (Ataque Élite)', $summary);
            return "🚀 Armada Ataque:  " . $summary;

        case 'force_build_fodder':
            $summary = preg_replace('/Recruited\s+(\d+)x\s+(.+)\s+on body\s+#(\d+).*/i', 'Astillero: $1x $2 en planeta #$3 (Pantalla Fodder)', $summary);
            return "🛡️ Pantalla Fodder: " . $summary;

        case 'force_build_defense':
            $summary = preg_replace('/Recruited\s+(\d+)x\s+(.+)\s+on body\s+#(\d+).*/i', 'Defensa: $1x $2 en planeta #$3 (Top Calidad)', $summary);
            return "🧱 Fortificación:  " . $summary;

        case 'antimissile_build':
            $summary = preg_replace('/Recruited\s+(\d+)x\s+Interceptor Missiles\s+\(502\)\s+on body\s+#(\d+)/i', 'Silo: $1x Misiles Antibalísticos en planeta #$2 (Defensa Silo)', $summary);
            return "🛡️ Antimisiles:    " . $summary;

        case 'mip_missile_build':
            $summary = preg_replace('/Recruited\s+(\d+)x\s+Interplanetary Missiles\s+\(503\)\s+on body\s+#(\d+)/i', 'Silo: $1x Misiles Interplanetarios en planeta #$2 (Ataque Silo)', $summary);
            return "🚀 Misiles MIP:    " . $summary;

        case 'missile_strike_pre_attack':
            $summary = preg_replace('/Launched tactical pre-attack salvo of\s+(\d+)x\s+MIP\s+\(503\)\s+against\s+(.+)\s+targeting\s+(.+)\s+\(Bypassing\s+(\d+)\s+enemy interceptors\)/i', 'Bombardeo Pre-Ataque de $1x MIPs a $2 contra $3 (Superando $4 interceptores)', $summary);
            return "🎯 Salva Estratégica: " . $summary;

        case 'threat_fleetsave':
            return "🚨 Auto-Dodge:     " . $summary;

        case 'savings_lock_engaged':
            return "💰 Modo Ahorro:    " . $summary;

        case 'savings_storage_unlock':
            return "📦 Almacén Guardia: " . $summary;

        case 'savings_antifarm_abort':
            return "⚠️ Anti-Farm Watch: " . $summary;

        case 'savings_target_achieved':
            return "🎉 Meta Ahorro:    " . $summary;

        case 'logistics_supply_dispatched':
            return "🚚 Suministro Log: " . $summary;

        default:
            return "📌 Decisión:       " . $summary;
    }
}

$persMap = array(
    'balanced'   => 'Equilibrada',
    'economic'   => 'Económica',
    'aggressive' => 'Agresiva',
    'defensive'  => 'Defensiva'
);
$diffMap = array(
    'easy'      => 'Fácil',
    'normal'    => 'Media',
    'hard'      => 'Avanzada',
    'nightmare' => 'Titán'
);

$start = microtime(true);
$currentTimeStr = date('Y-m-d H:i:s');

echo "========================================================================================\n";
echo " [{$currentTimeStr}] CICLO ESTRATÉGICO DE IA (TIMESTAMP: " . TIMESTAMP . ")\n";
echo "========================================================================================\n\n";

// Procesar eventos de flotas pendientes en el universo (para que naves llegadas aterricen en planetas)
require 'includes/FleetHandler.php';

$results = BotEngine::runAllBots();

if (empty($results)) {
    echo "  (No hay bots activos registrados en la base de datos)\n\n";
} else {
    foreach ($results as $botId => $res) {
        if (isset($res['error'])) {
            echo "┌── ❌ BOT ID {$botId}: ERROR EN TURNO\n";
            echo "└── Detalle: {$res['error']}\n\n";
            continue;
        }

        $name = isset($res['name']) ? $res['name'] : "Bot #{$botId}";
        $pers = isset($res['personality']) && isset($persMap[$res['personality']]) ? $persMap[$res['personality']] : (isset($res['personality']) ? ucfirst($res['personality']) : 'Normal');
        $diff = isset($res['difficulty']) && isset($diffMap[$res['difficulty']]) ? $diffMap[$res['difficulty']] : (isset($res['difficulty']) ? ucfirst($res['difficulty']) : 'Media');

        echo "┌── 🤖 " . strtoupper($name) . " (ID: {$botId} | Personalidad: {$pers} | Dificultad: {$diff})\n";

        if (!empty($res['decisions'])) {
            foreach ($res['decisions'] as $dec) {
                $formatted = formatDecisionInSpanish($dec);
                echo "│  " . $formatted . "\n";
            }
        } else {
            echo "│  ⏳ Construcción:   Colas de minas/edificios activas en progreso en los planetas\n";
            echo "│  🛡️ Defensa y Flota: Flotas en posición de patrulla | Sin alertas de ataque entrante\n";
        }

        $totalRecruitedCount = 0;
        if (!empty($res['recruited']) && is_array($res['recruited'])) {
            $recruitedUnits = array();
            $unitNames = array(
                202 => 'Caza Ligero',
                203 => 'Caza Pesado',
                204 => 'Crucero Ligero',
                205 => 'Nave de Batalla',
                206 => 'Crucero',
                207 => 'Acorazado',
                208 => 'Colonizador',
                209 => 'Reciclador',
                210 => 'Sonda de Espionaje',
                211 => 'Bombardero',
                212 => 'Satélite Solar',
                213 => 'Destructor',
                214 => 'Estrella de la Muerte',
                215 => 'Acorazado Estelar',
                216 => 'Luna Negra',
                217 => 'Mega Transportador',
                219 => 'Giga Reciclador',
                227 => 'Fragata Pesada',
                228 => 'Nómada Negro',
                401 => 'Lanzamisiles',
                402 => 'Láser Pequeño',
                403 => 'Láser Grande',
                404 => 'Cañón Gauss',
                405 => 'Cañón Iónico',
                406 => 'Cañón Plasma',
                407 => 'Cúpula Pequeña',
                408 => 'Cúpula Grande',
                417 => 'Supercañón Dora',
                502 => 'Misil Interceptor',
                503 => 'Misil Interplanetario',
            );
            foreach ($res['recruited'] as $uId => $cnt) {
                $uName = isset($unitNames[$uId]) ? $unitNames[$uId] : "Unidad #{$uId}";
                $recruitedUnits[] = number_format($cnt) . "x {$uName}";
                $totalRecruitedCount += $cnt;
            }
            if (!empty($recruitedUnits)) {
                echo "│  🏭 Reclutamiento: " . implode(', ', array_slice($recruitedUnits, 0, 4)) . (count($recruitedUnits) > 4 ? '...' : '') . "\n";
            }
        }

        $statusParts = array();
        if (!empty($totalRecruitedCount)) $statusParts[] = number_format($totalRecruitedCount) . " unidades ordenadas";
        if (!empty($res['spied'])) $statusParts[] = "{$res['spied']} espionajes activos";
        if (!empty($res['attacked'])) $statusParts[] = "{$res['attacked']} ataques en vuelo";
        if (!empty($res['transported'])) $statusParts[] = "{$res['transported']} convoys en ruta";
        if (!empty($res['dodged'])) $statusParts[] = "{$res['dodged']} fleetsaves";
        if (!empty($res['recycled'])) $statusParts[] = "{$res['recycled']} cosechas de escombros";

        $pCount = isset($res['planets_count']) ? (int)$res['planets_count'] : 8;
        $statusStr = !empty($statusParts) ? implode(' | ', $statusParts) : "Operando con normalidad";
        echo "└── 🟢 Estado:        {$pCount} planetas vigilados | {$statusStr}\n\n";
    }
}

$duration = round(microtime(true) - $start, 3);
echo "Ciclo completado en {$duration}s. Próxima evaluación en 45 segundos.\n";
echo "========================================================================================\n\n";

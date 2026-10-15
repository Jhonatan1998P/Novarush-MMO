<?php

/*
 * NovaRush - Daemon de Inteligencia Artificial para Bots Competitivos
 *
 * Uso CLI:
 *   php bot_daemon.php        -> Modo daemon continuo (orquestador que ejecuta cada 45 segundos con TIMESTAMP real)
 *   php bot_daemon.php --once -> Ejecuta un único ciclo y termina
 */

$isMainScript = (isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__));
$once = (isset($argv) && in_array('--once', $argv));

// -------------------------------------------------------------
// MODO ORQUESTADOR CONTINUO (Master Daemon Loop)
// -------------------------------------------------------------
if ($isMainScript && !$once) {
    $lockFile = sys_get_temp_dir() . '/novarush_bot_daemon.lock';
    $lockFp = @fopen($lockFile, 'c+');
    if ($lockFp && !@flock($lockFp, LOCK_EX | LOCK_NB)) {
        echo "========================================================================================\n";
        echo " [AVISO] Ya existe otra instancia de bot_daemon.php en ejecución activa.\n";
        echo " Abortando este proceso para evitar conflictos de concurrencia y bloqueos de BD.\n";
        echo "========================================================================================\n";
        exit(0);
    }

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
if (!defined('MODE')) {
    define('MODE', 'CRON');
}
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', str_replace('\\', '/', dirname(__FILE__)).'/');
}
set_include_path(ROOT_PATH);

require_once 'includes/common.php';
require_once 'includes/vars/General.php';
foreach (get_defined_vars() as $k => $v) {
    $GLOBALS[$k] = $v;
}
require_once 'includes/classes/bot/BotEngine.class.php';

$elementNamesById = array(
    1   => 'Mina de Metal',
    2   => 'Mina de Cristal',
    3   => 'Sintetizador de Deuterio',
    4   => 'Planta Solar',
    12  => 'Planta de Fusión',
    14  => 'Fábrica de Robots',
    15  => 'Fábrica de Nanobots',
    21  => 'Hangar',
    22  => 'Almacén de Metal',
    23  => 'Almacén de Cristal',
    24  => 'Almacén de Deuterio',
    31  => 'Laboratorio de Investigación',
    33  => 'Terraformer',
    34  => 'Depósito de la Alianza',
    41  => 'Base Lunar',
    42  => 'Sensor Phalanx',
    43  => 'Salto Cuántico',
    44  => 'Silo de Misiles',
    81  => 'Módulo de Recursos',
    82  => 'Módulo Defensivo',
    83  => 'Módulo Militar',
    84  => 'Módulo de Investigación',
    106 => 'Tecnología de Espionaje',
    108 => 'Tecnología de Computación',
    109 => 'Tecnología de Armas',
    110 => 'Tecnología de Escudos',
    111 => 'Tecnología de Blindaje',
    113 => 'Tecnología de Energía',
    114 => 'Tecnología de Hiperespacio',
    115 => 'Motor de Combustión',
    117 => 'Motor de Impulso',
    118 => 'Motor de Hiperespacio',
    120 => 'Tecnología Láser',
    121 => 'Tecnología de Iones',
    122 => 'Tecnología de Plasma',
    123 => 'Red Intergaláctica (IRN)',
    124 => 'Astrofísica (Expedición)',
    131 => 'Procesamiento Mineral',
    132 => 'Procesamiento Cristal',
    133 => 'Procesamiento Deuterio',
    134 => 'Reducción de Combustible',
    199 => 'Tecnología de Gravitón',
    202 => 'Carguero Pequeño',
    203 => 'Carguero Grande',
    204 => 'Caza Ligero',
    205 => 'Caza Pesado',
    206 => 'Crucero',
    207 => 'Nave de Batalla',
    208 => 'Colonizador',
    209 => 'Reciclador',
    210 => 'Sonda de Espionaje',
    211 => 'Bombardero',
    212 => 'Satélite Solar',
    213 => 'Destructor',
    214 => 'Estrella de la Muerte',
    215 => 'Acorazado',
    216 => 'Luna Negra',
    217 => 'Mega Transportador',
    218 => 'Devastador Estelar',
    219 => 'Giga Reciclador',
    220 => 'Extractor de MO',
    222 => 'Parca Estelar',
    223 => 'Dron Señuelo',
    225 => 'Galeón Espacial',
    226 => 'Superdestructor',
    227 => 'Fragata Pesada',
    228 => 'Nómada Negro',
    401 => 'Lanzamisiles',
    402 => 'Láser Pequeño',
    403 => 'Láser Grande',
    404 => 'Cañón Gauss',
    405 => 'Cañón Iónico',
    406 => 'Cañón de Plasma',
    407 => 'Cúpula Pequeña',
    408 => 'Cúpula Grande',
    417 => 'Supercañón Dora',
    418 => 'Cañón Fotónico',
    502 => 'Misil Interceptor',
    503 => 'Misil Interplanetario',
    601 => 'Geólogo',
    602 => 'Almirante',
    603 => 'Ingeniero',
    604 => 'Tecnócrata',
    924 => 'Contenedor de Recursos',
    1201 => 'Fiesta Industrial',
    1202 => 'Fiesta Científica',
    1301 => 'Liberalismo',
    1302 => 'Anarquismo',
    1303 => 'Comunismo',
    1304 => 'Socialismo',
    1305 => 'Totalitarismo',
    1306 => 'Imperialismo',
    1501 => 'Fotorianos',
    1502 => 'Sinuria',
    1503 => 'Humanos',
    1504 => 'Kratonianos',
    1601 => 'Imperio',
    1602 => 'República',
    1603 => 'Democracia',
    1701 => 'Bien',
    1702 => 'Neutral',
    1703 => 'Maldad'
);

$elementNamesByCode = array(
    'metal_mine'             => 'Mina de Metal',
    'crystal_mine'           => 'Mina de Cristal',
    'deuterium_sintetizer'   => 'Sintetizador de Deuterio',
    'solar_plant'            => 'Planta Solar',
    'fusion_plant'           => 'Planta de Fusión',
    'robot_factory'          => 'Fábrica de Robots',
    'nano_factory'           => 'Fábrica de Nanobots',
    'hangar'                 => 'Hangar',
    'metal_store'            => 'Almacén de Metal',
    'crystal_store'          => 'Almacén de Cristal',
    'deuterium_store'        => 'Almacén de Deuterio',
    'laboratory'             => 'Laboratorio de Investigación',
    'terraformer'            => 'Terraformer',
    'university'             => 'Universidad',
    'silo'                   => 'Silo de Misiles',
    'resource_module'        => 'Módulo de Recursos',
    'defensive_module'       => 'Módulo Defensivo',
    'military_module'        => 'Módulo Militar',
    'research_module'        => 'Módulo de Investigación',
    'mondbasis'              => 'Base Lunar',
    'phalanx'                => 'Sensor Phalanx',
    'sprungtor'              => 'Salto Cuántico',
    'spy_tech'               => 'Tecnología de Espionaje',
    'computer_tech'          => 'Tecnología de Computación',
    'military_tech'          => 'Tecnología de Armas',
    'defence_tech'           => 'Tecnología de Escudos',
    'shield_tech'            => 'Tecnología de Blindaje',
    'energy_tech'            => 'Tecnología de Energía',
    'hyperspace_tech'        => 'Tecnología de Hiperespacio',
    'combustion_tech'        => 'Motor de Combustión',
    'impulse_motor_tech'     => 'Motor de Impulso',
    'hyperspace_motor_tech'  => 'Motor de Hiperespacio',
    'laser_tech'             => 'Tecnología Láser',
    'ionic_tech'             => 'Tecnología de Iones',
    'plasma_tech'            => 'Tecnología de Plasma',
    'intergalactic_tech'     => 'Red Intergaláctica (IRN)',
    'expedition_tech'        => 'Astrofísica (Expedición)',
    'astrophysics'           => 'Astrofísica',
    'mineral_research'       => 'Procesamiento Mineral',
    'semi_crystals_research' => 'Procesamiento Cristal',
    'fuel_research'          => 'Procesamiento Deuterio',
    'fuel_reduction'         => 'Reducción de Combustible',
    'graviton_tech'          => 'Tecnología de Gravitón',
    'small_ship_cargo'       => 'Carguero Pequeño',
    'big_ship_cargo'         => 'Carguero Grande',
    'light_hunter'           => 'Caza Ligero',
    'heavy_hunter'           => 'Caza Pesado',
    'crusher'                => 'Crucero',
    'battle_ship'            => 'Nave de Batalla',
    'colonizer'              => 'Colonizador',
    'recycler'               => 'Reciclador',
    'spy_sonde'              => 'Sonda de Espionaje',
    'bomber_ship'            => 'Bombardero',
    'solar_satelit'          => 'Satélite Solar',
    'destructor'             => 'Destructor',
    'dearth_star'            => 'Estrella de la Muerte',
    'battleship'             => 'Acorazado',
    'lune_noir'              => 'Luna Negra',
    'ev_transporter'         => 'Mega Transportador',
    'star_dust'              => 'Devastador Estelar',
    'giga_recykler'          => 'Giga Reciclador',
    'dm_collector'           => 'Extractor de MO',
    'death_reaper'           => 'Parca Estelar',
    'decoy'                  => 'Dron Señuelo',
    'galleon'                => 'Galeón Espacial',
    'super_destructor'       => 'Superdestructor',
    'heavy_frigate'          => 'Fragata Pesada',
    'black_wanderer'         => 'Nómada Negro',
    'misil_launcher'         => 'Lanzamisiles',
    'small_laser'            => 'Láser Pequeño',
    'big_laser'              => 'Láser Grande',
    'gauss_canyon'           => 'Cañón Gauss',
    'ionic_canyon'           => 'Cañón Iónico',
    'buster_canyon'          => 'Cañón de Plasma',
    'plasma_canyon'          => 'Cañón de Plasma',
    'small_protection_shield'=> 'Cúpula Pequeña',
    'big_protection_shield'  => 'Cúpula Grande',
    'dora_gun'               => 'Supercañón Dora',
    'photon_cannon'          => 'Cañón Fotónico',
    'interceptor_misil'      => 'Misil Interceptor',
    'interplanetary_misil'   => 'Misil Interplanetario',
    'geologe'                => 'Geólogo',
    'admiral'                => 'Almirante',
    'engineer'               => 'Ingeniero',
    'technocrat'             => 'Tecnócrata',
    'metal'                  => 'Metal',
    'crystal'                => 'Cristal',
    'deuterium'              => 'Deuterio',
    'fleet_crash'            => 'Caza de Flota',
    'farming'                => 'Saqueo de Recursos',
    'siege'                  => 'Asedio a Búnker',
);

function formatDecisionInSpanish($decision)
{
    global $elementNamesById, $elementNamesByCode;

    $type    = isset($decision['decision_type']) ? $decision['decision_type'] : '';
    $summary = isset($decision['context_summary']) ? $decision['context_summary'] : '';

    // Reemplazo de claves internas de base de datos a español
    foreach ($elementNamesByCode as $code => $name) {
        $summary = str_replace($code, $name, $summary);
    }

    switch ($type) {
        case 'fleet_lock':
            return "🛡️ Bloqueo de Flotas: Movimientos de flotas bloqueados por directiva del usuario.";

        case 'target_locked':
            $summary = preg_replace('/(?:Campaign target|Target)\s+locked at\s+\[(\d+:\d+:\d+)\]\s+classified as\s+(.+?)\s+\(Initial Value:\s+(.+?)\s+MSE(?:,\s+EV\/h:\s+(.+?))?\)\.\s+Designated FOB colony\s+#(\d+)/i', 'Fijado objetivo en [$1] ($2 | Valor inicial: $3 MSE). Base Avanzada FOB: Colonia #$5', $summary);
            return "🎯 Fijación Blanco: " . $summary;

        case 'target_released':
            $summary = preg_replace('/Released target\s+(.+?)\s+from tactical lock\.\s*Reason:\s+(.+?)\.\s*Searching for new high-value target\./i', 'Objetivo $1 liberado del radar táctico. Motivo: $2', $summary);
            return "🔓 Desbloqueo Blanco: " . $summary;

        case 'strike_hold_for_rally':
            $summary = preg_replace('/Target\s+\[(\d+:\d+:\d+)\]\s+held:\s*local FOB fleet win rate\s*\((.+?)\)\s*is under 90% threshold\.\s*Awaiting fleet concentration\./i', 'Objetivo [$1] en espera (Victoria local FOB $2 < 90%). Concentrando armadas.', $summary);
            return "⏸️ Espera Táctica: " . $summary;

        case 'strike_hold_unscouted_defense':
            $summary = preg_replace('/Target\s+\[(\d+:\d+:\d+)\]\s+held:\s*defenses are unconfirmed\s*\(Fog of War\)\.\s*Awaiting reconnaissance before launch\./i', 'Objetivo [$1] en espera: Defensas no confirmadas (Niebla de Guerra). Esperando espionaje.', $summary);
            return "⏸️ Espera Reconoc.: " . $summary;

        case 'strike_in_progress':
            return "⚔️ Ofensiva en Vuelo: " . $summary;

        case 'staging_in_transit':
            return "🛸 Refuerzo en Ruta: " . $summary;

        case 'campaign_staging_dispatched':
            return "🛸 Despliegue Refuerzo: " . $summary;

        case 'strike_launched':
            $summary = preg_replace('/Launched\s+(.+?)\s+strike from FOB\s+#(\d+)\s+against\s+\[(\d+:\d+:\d+)\]\s+with\s+(.+?)\s+warships\s+\((.+?)\s+win probability\)(?:\s+at\s+(.+?)%\s+speed)?\s+\(Sincronizado:\s+Recicladores arriban T\+(\d+)s desde base #(\d+)\)/i', 'Ofensiva lanzada desde Base FOB #$2 contra [$3] con $4 naves ($5 victoria). Recicladores arriban a T+$7s.', $summary);
            $summary = preg_replace('/Launched\s+(.+?)\s+strike from FOB\s+#(\d+)\s+against\s+\[(\d+:\d+:\d+)\]\s+with\s+(.+?)\s+warships\s+\((.+?)\s+win probability\)(?:\s+at\s+(.+?)%\s+speed)?(?:\s+\(ETA:\s+(.+?)s\))?/i', 'Ofensiva lanzada desde Base FOB #$2 contra [$3] con $4 naves ($5 victoria).', $summary);
            return "⚔️ Ofensiva Militar: " . $summary;

        case 'strike_scheduled_for_impact':
            return "⏳ Ofensiva Calibrada: " . $summary;

        case 'recycler_scheduled_for_impact':
            return "⏳ Reciclaje Calibrado: " . $summary;

        case 'synchronized_recycler_dispatch':
            $summary = preg_replace('/Synchronized pre-impact deployment of\s+(.+?)\s+recyclers(?:\s+from base\s+#(\d+))?\s+to debris at\s+\[(\d+:\d+:\d+)\](?:\s+at\s+(.+?)%\s+speed)?\s+\(ETA:\s+(.+?)s\s*\|\s*Debris:\s+(.+?)\)/i', 'Despliegue sincronizado pre-impacto de $1 recicladores a [$3] desde base #$2 ($6 escombros previstos)', $summary);
            $summary = preg_replace('/Synchronized pre-impact deployment of\s+(.+?)\s+recyclers\s+to debris at\s+\[(\d+:\d+:\d+)\]\s+\(Expected Debris:\s+(.+?)\)/i', 'Despliegue sincronizado pre-impacto de $1 recicladores a [$2] (Escombros: $3)', $summary);
            $summary = preg_replace('/Synchronized pre-impact deployment of\s+(.+?)\s+recicladores desde base\s+#(\d+)\s+to debris at\s+\[(\d+:\d+:\d+)\]\s+at\s+(.+?)%\s+speed\s+\(Arribo:\s*Impacto\s*\+\s*(\d+)s\)/i', 'Despliegue sincronizado pre-impacto de $1 recicladores desde base #$2 a [$3] al $4% vel. (Arribo: Impacto + $5s)', $summary);
            return "♻️ Reciclaje Sincro: " . $summary;

        case 'siege_bombardment':
            return "🚀 Asedio (Misiles): " . $summary;

        case 'siege_final_assault':
            $summary = preg_replace('/Siege final assault launched on cycle\s+(\d+)\s+from FOB\s+#(\d+)\s+against\s+\[(\d+:\d+:\d+)\]\s+with\s+(.+?)\s+win probability\s+\((.+?)\s+warships\)/i', 'Asalto definitivo en ciclo $1 desde FOB #$2 contra [$3] ($4 victoria, $5 naves)', $summary);
            return "💥 Asalto Definitivo: " . $summary;

        case 'siege_accumulating_mips':
            return "🚀 Acumulación Misiles: " . $summary;

        case 'siege_slots_saturated':
            return "⏸️ Silos Pausados:  " . $summary;

        case 'siege_insufficient_fuel':
            return "⛽ Combustible Silo: " . $summary;

        case 'siege_immune_defenses_detected':
            return "🛡️ Rebote Misiles:   " . $summary;

        case 'reconnaissance_locked_target':
            $summary = preg_replace('/Re-espionage launched(?:\s+from colony\s+#(\d+))?\s+to locked\s+(.+?)\s+target\s+\[(\d+:\d+:\d+)\]\s+with\s+(\d+)\s+probes(?:\s+\(Penetration Wave\))?/i', 'Re-espionaje táctico sobre blanco [$3] ($2) con $4 sondas desde colonia #$1', $summary);
            return "🔭 Re-Espionaje:    " . $summary;

        case 'pre_impact_reconnaissance':
            $summary = preg_replace('/Pre-impact tactical re-espionage dispatched from colony\s+#(\d+)\s+to\s+\[(\d+:\d+:\d+)\]\s+\((\d+)\s+probes,\s+T-minus\s+(\d+)s\)\s+to confirm target status\./i', 'Re-espionaje táctico pre-impacto a [$2] ($3 sondas, T-$4s) desde colonia #$1', $summary);
            return "🔭 Espionaje Sincro: " . $summary;

        case 'reconnaissance':
            $summary = preg_replace('/Dispatched\s+(\d+)\s+spy probes from colony\s+#(\d+)\s+to\s+(\d+:\d+:\d+)/i', 'Despachadas $1 sondas desde colonia #$2 hacia [$3]', $summary);
            $summary = preg_replace('/Dispatched\s+(\d+)\s+spy probes to\s+(.+)/i', 'Despachadas $1 sondas hacia $2', $summary);
            return "🔭 Espionaje:      " . $summary;

        case 'fleet_staging':
            $summary = preg_replace('/Rallied combat fleet from colony\s+#(\d+)\s+to FOB\s+#(\d+)\s+to consolidate firepower/i', 'Armada concentrada desde colonia #$1 a Base FOB #$2 para consolidar poder de fuego', $summary);
            return "🛸 Concentración:  " . $summary;

        case 'fleet_rally':
            $summary = preg_replace('/Rallied fleet from\s+#(\d+)\s+to staging base\s+#(\d+)/i', 'Unificación de flota de #$1 a base principal #$2', $summary);
            return "🛸 Concentración:  " . $summary;

        case 'mine_upgrade':
            $summary = preg_replace('/Upgraded\s+(.+)\s+on planet\s+#(\d+)(?:\s+\(Multi-Colony Parallel Execution\))?/i', 'Mejora de $1 en planeta #$2 (Multi-Colonia)', $summary);
            return "🔨 Construcción:   " . $summary;

        case 'research_enqueued':
            $summary = preg_replace('/Queued research\s+(.+)\s+on planet\s+#(\d+)/i', 'Investigación encolada de $1 en laboratorio #$2', $summary);
            return "🔬 Investigación:  " . $summary;

        case 'tech_research':
            $summary = preg_replace('/Started research\s+(.+)/i', 'Iniciada investigación de $1', $summary);
            return "🔬 Investigación:  " . $summary;

        case 'savings_lock_engaged':
            $summary = preg_replace('/Engaged savings lock for (?:research\s+)?(.+)\s+on planet\s+#(\d+)/i', 'Ahorro protegido activado para $1 en planeta #$2', $summary);
            return "💰 Modo Ahorro:    " . $summary;

        case 'savings_storage_unlock':
            $summary = preg_replace('/Forced warehouse unlock\s+\(#(\d+)\)\s+on planet\s+#(\d+)\s+due to savings target exceeding 95% storage capacity/i', 'Ampliación de almacén forzada (#$1) en planeta #$2 (Ahorro supera 95% de capacidad)', $summary);
            return "📦 Almacén Guardia: " . $summary;

        case 'savings_antifarm_abort':
            $summary = preg_replace('/Watchdog Anti-Farm triggered on planet\s+#(\d+):\s*3 consecutive cycles \(135s\) with non-growing stock\. Aborting savings to recruit defenses and fighters\./i', '¡Alerta Anti-Granjeo en planeta #$1! Sin crecimiento en 3 ciclos (135s). Ahorro cancelado para reclutar defensas y cazas.', $summary);
            return "⚠️ Anti-Granjeo:    " . $summary;

        case 'savings_target_achieved':
            $summary = preg_replace('/Savings Lock fulfilled!\s*Purchased\s*(building|research|shipyard)\s*#(\d+)\s+on planet\s+#(\d+)/i', '¡Meta de ahorro alcanzada! Obtenido #$2 en planeta #$3', $summary);
            return "🎉 Meta de Ahorro: " . $summary;

        case 'officer_investment':
            foreach (array(601 => 'Geólogo', 602 => 'Almirante', 603 => 'Ingeniero', 604 => 'Tecnócrata') as $oId => $oNm) {
                $summary = str_replace("#{$oId}", "{$oNm} (#{$oId})", $summary);
            }
            $summary = preg_replace('/Inversión Oficial:\s*Ascendido\s+(.+)\s+a nivel\s+(\d+)\s+\(Coste:\s+(.+?)\s+MO,\s+Saldo restante:\s+(.+?)\s+MO\)/i', 'Ascendido $1 a nivel $2 (Coste: $3 MO | Saldo restante: $4 MO)', $summary);
            return "🎖️ Oficiales:      " . $summary;

        case 'ideology_upgrade':
            foreach (array(1301 => 'Liberalismo', 1302 => 'Anarquismo', 1303 => 'Comunismo', 1304 => 'Socialismo', 1305 => 'Totalitarismo', 1306 => 'Imperialismo') as $iId => $iNm) {
                $summary = str_replace("#{$iId}", "{$iNm} (#{$iId})", $summary);
            }
            $summary = preg_replace('/Ideología Adquirida:\s*Ascendida\s+(.+)\s+a nivel\s+(\d+)\s+con Antimateria legítima\s+\(Coste:\s+(.+?)\s+AM\)/i', 'Ascendida ideología $1 a nivel $2 (Coste: $3 AM)', $summary);
            return "💡 Ideología:      " . $summary;

        case 'civics_assigned':
            return "🏛️ Matriz Cívica:  " . $summary;

        case 'planetary_party':
            return "🎉 Fiesta:         " . $summary;

        case 'fair_mining':
            return "🪙 Minado MO:      " . $summary;

        case 'container_open':
            return "🎁 Contenedor:     " . $summary;

        case 'logistics_supply_dispatched':
            $summary = str_replace(
                array('Mines - Building', 'Fleet - Unit', 'Defense - Unit', 'Research - Tech', 'Mines - Tech', 'Fleet - Building'),
                array('Minas (Edificio)', 'Flota (Astillero)', 'Defensas (Astillero)', 'Investigación (Tecnología)', 'Minas (Tecnología)', 'Flota (Edificio)'),
                $summary
            );
            return "🚚 Suministro Log: " . $summary;

        case 'expedition_dispatch':
            $summary = preg_replace('/Dispatched autonomous expedition to\s+\[(\d+:\d+:\d+)\]\s+from colony\s+#(\d+)\s+\(Fleet MSE:\s+(.+?),\s+Capacity:\s+(.+?)\)/i', 'Expedición autónoma enviada al sector [$1] desde colonia #$2 (Flota: $3 MSE, Bodega: $4)', $summary);
            return "🛸 Expedición:     " . $summary;

        case 'farming_campaign_launched':
            $summary = preg_replace('/Farming campaign strike dispatched from FOB\s+#(\d+)\s+against\s+\[(\d+:\d+:\d+)\]\s+with\s+(.+?)\s+ships\s+\(Loot capacity focused,\s+Win prob:\s+(.+?)%\)/i', 'Ataque de saqueo desde FOB #$1 contra [$2] con $3 naves ($4% victoria)', $summary);
            return "🌾 Saqueo Recursos: " . $summary;

        case 'farm_sweep_launched':
            $summary = preg_replace('/Parallel farm sweep:\s*dispatched\s+(.+?)\s+ships from colony\s+#(\d+)\s+to farm\s+\[(\d+:\d+:\d+)\]\s+\(Estimated Loot:\s+(.+?)\s+MSE\)/i', 'Barrido paralelo de saqueo: $1 naves desde planeta #$2 hacia [$3] ($4 MSE botín)', $summary);
            return "🌾 Barrido Saqueo:  " . $summary;

        case 'pre_impact_abort':
        case 'pre_impact_abort_fleet_withdrawn':
            return "⚠️ Retirada Táctica: " . $summary;

        case 'debris_recycle':
            $summary = preg_replace('/Dispatched recycler fleet to\s+(.+)/i', 'Flota de recicladores enviada a escombros en $1', $summary);
            return "♻️ Reciclaje:      " . $summary;

        case 'recycling_launched':
            $summary = preg_replace('/Dispatched\s+(.+?)\s+recyclers to harvest debris at\s+\[(\d+:\d+:\d+)\]/i', 'Despachados $1 recicladores a recolectar campo de escombros en [$2]', $summary);
            return "♻️ Cosecha Escombros: " . $summary;

        case 'achievements_sync':
            $achMap = array(
                'achievements_1'  => 'Minas de Metal',
                'achievements_2'  => 'Minas de Cristal',
                'achievements_3'  => 'Sintetizadores',
                'achievements_4'  => 'Plantas Solares',
                'achievements_5'  => 'Fábricas Robots',
                'achievements_6'  => 'Nanobots',
                'achievements_7'  => 'Hangares',
                'achievements_8'  => 'Almacenes Metal',
                'achievements_9'  => 'Almacenes Cristal',
                'achievements_10' => 'Almacenes Deuterio',
                'achievements_11' => 'Laboratorios',
                'achievements_12' => 'Terraformers',
                'achievements_13' => 'Silos Misiles',
                'achievements_14' => 'Módulos Recursos',
                'achievements_15' => 'Módulos Defensivos',
                'achievements_16' => 'Módulos Militares',
                'achievements_17' => 'Flota Combate',
                'achievements_18' => 'Flota Civil',
                'achievements_19' => 'Defensas Fijas',
                'achievements_20' => 'Tecnologías Combate',
                'achievements_21' => 'Motores Espaciales',
                'achievements_22' => 'Ciencia Avanzada',
                'achievements_23' => 'Expediciones',
            );
            foreach ($achMap as $k => $v) {
                $summary = preg_replace('/\b' . $k . '\b/', $v, $summary);
            }
            return "🏆 Logros:         " . $summary;

        case 'threat_fleetsave_triggered':
        case 'threat_fleetsave':
            $summary = preg_replace('/Evaluated threat from player #(\d+) to body #(\d+)\. Planetary defenses alone suffer (.+?)% destruction, combined garrison has (.+?)% win probability \(<80% threshold\)\. Threat is overwhelming — triggering evasive fleetsave\./i', 'Ataque hostil de jugador #$1 a planeta #$2. Defensas solas pierden $3%, victoria combinada $4% (<80%). Amenaza crítica: Activando fleetsave evasivo.', $summary);
            return "🚨 Alerta (Fleetsave): " . $summary;

        case 'fleetsave_executed':
            $summary = preg_replace('/Professional fleetsave:\s*evacuated\s+(.+?)\s+ships and 100% resources from (Moon|Planet)\s+#(\d+)\s+to (Moon|Colony)\s+#(\d+)\s+at (\d+)0% speed \(Fuel:\s+(.+?)\s+deut,\s+traded:\s+(.+?)\s+deut,\s+recall scheduled at\s+(.+?)\)/i', 'Fleetsave Profesional: Evacuadas $1 naves y 100% recursos desde $2 #$3 hacia $4 #$5 al $60% vel. (Combustible: $7 deut, tradeado: $8 deut, retorno: $9)', $summary);
            $summary = preg_replace('/Emergency Partial Fleetsave:\s*evacuated\s+(.+?)\s*\/\s*(.+?)\s+high-value ships to closest colony\s+#(\d+)\s+at 10% speed \(Fuel:\s+(.+?)\s*\/\s*(.+?)\s+deut,\s+recall scheduled at\s+(.+?)\)/i', 'Fleetsave Parcial de Emergencia: Salvadas $1 / $2 naves de alto valor hacia colonia cercana #$3 al 10% vel. (Combustible: $4 / $5 deut, retorno: $6)', $summary);
            return "🛡️ Fleetsave Ejecutado:  " . $summary;

        case 'fleetsave_recall':
            $summary = preg_replace('/Recalled fleetsave\s+#(\d+)\s+back to origin\s+#(\d+)/i', 'Retorno de flota evacuada #$1 a base originaria #$2 tras disiparse la amenaza hostil', $summary);
            return "🔄 Retorno Fleetsave: " . $summary;

        case 'threat_defense_held_passively':
            $summary = preg_replace('/Evaluated threat from player #(\d+) to body #(\d+)\. Planetary defenses absorb attack with only (.+?)% damage \(<80%\)\. Combined win prob: (.+?)%\. Fleetsaving mobile fleet so ONLY defenses fight, and deploying (?:defensive\s+)?(?:recyclers|Recicladors|recicladores) arriving at T\+(\d+)s post-impact for (.+?) projected debris\./i', 'Ataque de jugador #$1 a planeta #$2. El búnker absorbe el impacto sufriendo solo $3% de daño (<80%). Victoria combinada: $4%. Flota móvil evacuada a Fleetsave preventivo para combate exclusivo del búnker. Recicladores defensivos despachados para arribar a T+$5s post-impacto ($6 escombros previstos).', $summary);
            return "🧱 Búnker Defensivo: " . $summary;

        case 'threat_active_defense_stamp':
            $summary = preg_replace('/Tactical Estampada:\s*Defenses alone suffer (.+?)% destruction \(>=80%\), but combined garrison has (.+?)% win probability \(>=80%\)\. Holding fleet and deploying (?:defensive\s+)?(?:recyclers|Recicladors|recicladores) arriving at T\+(\d+)s post-impact\./i', '¡Estampada Táctica Activa! Defensas solas sufren $1% de bajas (>=80%), pero la guarnición combinada tiene $2% prob. de victoria (>=80%). Flota móvil mantiene posición en tierra para aplastar al invasor. Recicladores defensivos despachados para arribar a T+$3s post-impacto.', $summary);
            $summary = preg_replace('/Grounded Garrison Defense:\s*Mobile fleet could not be evacuated, fighting alongside planetary defenses\. Combined win probability:\s*(.+?)%\. Deploying (?:defensive\s+)?(?:recyclers|Recicladors|recicladores) arriving at T\+(\d+)s post-impact\./i', 'Defensa de Guarnición en Tierra: Flota móvil combate junto al búnker defensivo. Victoria combinada: $1%. Recicladores defensivos despachados para arribar a T+$2s post-impacto.', $summary);
            return "⚔️ Estampada Táctica:    " . $summary;

        case 'defense_recycler_scheduled':
        case 'recycler_defense_scheduled':
            $summary = preg_replace('/Scheduled defensive recycler dispatch for body\s+#(\d+)\s+at\s+(.+?)\s+\(T\+(\d+)s post-impact\)/i', 'Despacho programado de recicladores para planeta #$1 a las $2 (T+$3s post-impacto)', $summary);
            return "⏳ Reciclaje Defensivo:   " . $summary;

        case 'synchronized_defense_recycler_dispatch':
            $summary = preg_replace('/Synchronized defensive deployment of\s+(.+?)\s+recyclers to debris at\s+\[(\d+:\d+:\d+)\]\s+\(Expected Debris:\s+(.+?)\)/i', 'Despliegue sincronizado de $1 recicladores hacia escombros en [$2] (Escombros: $3)', $summary);
            return "♻️ Reciclaje Defensivo:   " . $summary;

        case 'trader_exchange':
            $summary = preg_replace('/Emergency Fleetsave Trader:\s*Intercambio de\s+(.+?)\s+cristal y\s+(.+?)\s+metal del presupuesto de flota por\s+(.+?)\s+deuterio en planeta\s+#(\d+)\s+\(50 MO fee\)/i', 'Mercader de Emergencia: Intercambio de $1 cristal y $2 metal del presupuesto militar por $3 deuterio en planeta #$4 para combustible de fleetsave (Coste: 50 MO)', $summary);
            $summary = preg_replace('/Traded\s+(.+?)\s+of resource\s+(\d+)\s+for\s+(.+?)\s+of\s+(\d+)\s+on planet\s+#(\d+)\s+\(Fee:\s+50 MO\)/i', 'Mercader: Intercambio de $1 recurso #$2 por $3 recurso #$4 en planeta #$5 (Coste: 50 MO)', $summary);
            return "⚖️ Mercader:       " . $summary;

        case 'convoy_dispatch':
            $summary = preg_replace('/Dispatched convoy from\s+#(\d+)\s+to hub\s+#(\d+)/i', 'Convoy de transporte desde planeta #$1 hacia base #$2', $summary);
            return "📦 Logística:      " . $summary;

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

if (!$isMainScript) {
    return;
}

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
            foreach ($res['recruited'] as $uId => $cnt) {
                if ($cnt <= 0) continue;
                $uName = isset($elementNamesById[$uId]) ? $elementNamesById[$uId] : (isset($elementNamesByCode[$uId]) ? $elementNamesByCode[$uId] : "Unidad #{$uId}");
                $recruitedUnits[] = number_format($cnt) . "x {$uName}";
                $totalRecruitedCount += $cnt;
            }
            if (!empty($recruitedUnits)) {
                echo "│  🏭 Reclutamiento Imperio: " . implode(', ', $recruitedUnits) . "\n";
            }
        }

        $statusParts = array();
        if (!empty($totalRecruitedCount)) $statusParts[] = number_format($totalRecruitedCount) . " unidades encoladas";
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

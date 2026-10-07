<?php

/*
 * NovaRush - Tactical Message & Mission Report Template Helper
 * Generates structured, responsive, emoji-free HTML cards for all game messages
 * Basis: 2Moons / New-Star
 */

class MessageTemplateHelper
{
    /**
     * Get theme directory path
     */
    public static function getThemePath()
    {
        $theme = defined('DEFAULT_THEME') ? DEFAULT_THEME : 'nsc';
        return 'styles/theme/' . $theme . '/';
    }

    /**
     * Helper to render SVG icons (pure vector, zero emojis)
     */
    public static function getSvg($icon, $width = 14, $height = 14)
    {
        switch ($icon) {
            case 'sword':
                return '<svg class="msg-svg" viewBox="0 0 24 24" width="'.$width.'" height="'.$height.'"><path d="M19.7 4.3a1 1 0 0 0-1.4 0L14 8.6 15.4 10l4.3-4.3a1 1 0 0 0 0-1.4zM4.3 19.7a1 1 0 0 0 1.4 0L10 15.4 8.6 14l-4.3 4.3a1 1 0 0 0 0 1.4zm12.3-6.9l-1.4-1.4-8.8 8.8 1.4 1.4 8.8-8.8zm-7.6-7.6l1.4 1.4 8.8-8.8-1.4-1.4-8.8 8.8z"/></svg>';
            case 'shield':
                return '<svg class="msg-svg" viewBox="0 0 24 24" width="'.$width.'" height="'.$height.'"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/></svg>';
            case 'recycle':
                return '<svg class="msg-svg" viewBox="0 0 24 24" width="'.$width.'" height="'.$height.'"><path d="M7 7h10v3l4-4-4-4v3H5v6h2V7zm10 10H7v-3l-4 4 4 4v-3h12v-6h-2v4z"/></svg>';
            case 'target':
                return '<svg class="msg-svg" viewBox="0 0 24 24" width="'.$width.'" height="'.$height.'"><circle cx="12" cy="12" r="3"/><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm0 18a8 8 0 1 1 8-8 8 8 0 0 1-8 8z"/></svg>';
            case 'crosshair':
                return '<svg class="msg-svg" viewBox="0 0 24 24" width="'.$width.'" height="'.$height.'"><path d="M11 2v2.07A8.004 8.004 0 0 0 4.07 11H2v2h2.07A8.004 8.004 0 0 0 11 19.93V22h2v-2.07A8.004 8.004 0 0 0 19.93 13H22v-2h-2.07A8.004 8.004 0 0 0 13 4.07V2h-2zm1 4a6 6 0 1 1-6 6 6 6 0 0 1 6-6zm0 4a2 2 0 1 0 2 2 2 2 0 0 0-2-2z"/></svg>';
            case 'radar':
                return '<svg class="msg-svg" viewBox="0 0 24 24" width="'.$width.'" height="'.$height.'"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm1 17.93V18a2 2 0 0 0-2-2h-1v-2h2a1 1 0 0 0 1-1V9a2 2 0 0 0-2-2h-2V5.07A8 8 0 0 1 20 12a7.9 7.9 0 0 1-7 7.93zM4 12a8 8 0 0 1 6-7.75V7h1a1 1 0 0 1 1 1v1h-2a2 2 0 0 0-2 2v2H6a2 2 0 0 0-2-2zm1.07 3A7.93 7.93 0 0 1 4 12h2a2 2 0 0 1 2 2v1h1v3a1 1 0 0 1-1 1H7a7.9 7.9 0 0 1-1.93-4z"/></svg>';
            case 'time':
                return '<svg class="msg-svg" viewBox="0 0 24 24" width="'.$width.'" height="'.$height.'"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>';
            case 'arrow-right':
                return '<svg class="msg-svg" viewBox="0 0 24 24" width="'.$width.'" height="'.$height.'"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg>';
            case 'document':
                return '<svg class="msg-svg" viewBox="0 0 24 24" width="'.$width.'" height="'.$height.'"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>';
            case 'simulate':
                return '<svg class="msg-svg" viewBox="0 0 24 24" width="'.$width.'" height="'.$height.'"><path d="M7 2v11h3v9l7-12h-4l4-8z"/></svg>';
            case 'moon':
                return '<svg class="msg-svg" viewBox="0 0 24 24" width="'.$width.'" height="'.$height.'"><path d="M12.3 2a10 10 0 0 0-1.9 19.8 10 10 0 0 0 11.6-11.6A10.02 10.02 0 0 1 12.3 2z"/></svg>';
            case 'rocket':
                return '<svg class="msg-svg" viewBox="0 0 24 24" width="'.$width.'" height="'.$height.'"><path d="M12 2.5s4 3.5 4 8.5c0 2.5-1 4.5-2 5.5l1.5 3.5-3.5-1.5-3.5 1.5 1.5-3.5c-1-1-2-3-2-5.5 0-5 4-8.5 4-8.5z"/></svg>';
            case 'box':
                return '<svg class="msg-svg" viewBox="0 0 24 24" width="'.$width.'" height="'.$height.'"><path d="M20 6h-4V4c0-1.11-.89-2-2-2h-4c-1.11 0-2 .89-2 2v2H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-6 0h-4V4h4v2zM4 19V8h16v11H4z"/><circle cx="12" cy="13" r="2"/></svg>';
            case 'warning':
                return '<svg class="msg-svg" viewBox="0 0 24 24" width="'.$width.'" height="'.$height.'"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>';
            case 'check':
                return '<svg class="msg-svg" viewBox="0 0 24 24" width="'.$width.'" height="'.$height.'"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>';
            case 'close':
                return '<svg class="msg-svg" viewBox="0 0 24 24" width="'.$width.'" height="'.$height.'"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>';
            case 'trash':
                return '<svg class="msg-svg" viewBox="0 0 24 24" width="'.$width.'" height="'.$height.'"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>';
            case 'archive':
                return '<svg class="msg-svg" viewBox="0 0 24 24" width="'.$width.'" height="'.$height.'"><path d="M20.54 5.23l-1.39-1.68C18.88 3.21 18.47 3 18 3H6c-.47 0-.88.21-1.16.55L3.46 5.23C3.17 5.57 3 6.02 3 6.5V19c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6.5c0-.48-.17-.93-.46-1.27zM12 17.5L6.5 12H10v-2h4v2h3.5L12 17.5zM5.12 5l.81-1h12l.94 1H5.12z"/></svg>';
            case 'reply':
                return '<svg class="msg-svg" viewBox="0 0 24 24" width="'.$width.'" height="'.$height.'"><path d="M10 9V5l-7 7 7 7v-4.1c5 0 8.5 1.6 11 5.1-1-5-4-10-11-11z"/></svg>';
            case 'buddy':
                return '<svg class="msg-svg" viewBox="0 0 24 24" width="'.$width.'" height="'.$height.'"><path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>';
            case 'star':
                return '<svg class="msg-svg" viewBox="0 0 24 24" width="'.$width.'" height="'.$height.'"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>';
            case 'trophy':
                return '<svg class="msg-svg" viewBox="0 0 24 24" width="'.$width.'" height="'.$height.'"><path d="M19 5h-2V3H7v2H5c-1.1 0-2 .9-2 2v1c0 2.55 1.92 4.63 4.39 4.94A5.01 5.01 0 0 0 11 15.9V19H7v2h10v-2h-4v-3.1a5.01 5.01 0 0 0 3.61-2.96C19.08 12.63 21 10.55 21 8V7c0-1.1-.9-2-2-2zM5 8V7h2v3.82C5.84 10.4 5 9.3 5 8zm14 0c0 1.3-.84 2.4-2 2.82V7h2v1z"/></svg>';
            default:
                return '';
        }
    }

    /**
     * Build Tactical Combat Report Card
     */
    public static function buildCombatCard($reportId, $won, $targetInfo, $unitLost, $stealResource, $debris, $moonInfo, $isAttacker, $LNG)
    {
        $dpath = self::getThemePath();

        // Determine result badge
        if ($won === 'a') {
            $pillClass = $isAttacker ? 'pill-success' : 'pill-danger';
            $badgeIcon = $isAttacker ? self::getSvg('check', 14, 14) : self::getSvg('close', 14, 14);
            $badgeText = $isAttacker ? ($LNG['sys_fleet_won_title'] ?? 'Victoria') : ($LNG['sys_fleet_lost_title'] ?? 'Derrota');
        } elseif ($won === 'r') {
            $pillClass = $isAttacker ? 'pill-danger' : 'pill-success';
            $badgeIcon = $isAttacker ? self::getSvg('close', 14, 14) : self::getSvg('check', 14, 14);
            $badgeText = $isAttacker ? ($LNG['sys_fleet_lost_title'] ?? 'Derrota') : ($LNG['sys_fleet_won_title'] ?? 'Victoria');
        } else {
            $pillClass = 'pill-warning';
            $badgeIcon = self::getSvg('warning', 14, 14);
            $badgeText = $LNG['sys_fleet_draw_title'] ?? 'Empate';
        }

        $targetName = htmlspecialchars($targetInfo['name'] ?? '', ENT_QUOTES, 'UTF-8');
        $galaxy     = (int) $targetInfo['galaxy'];
        $system     = (int) $targetInfo['system'];
        $planet     = (int) $targetInfo['planet'];
        $planetType = (int) ($targetInfo['planet_type'] ?? 1);
        $typeShort  = $LNG['type_planet_short_' . $planetType] ?? ($planetType == 3 ? 'Luna' : 'Planeta');
        $planetImg  = ($planetType == 3) ? 's_mond.jpg' : ('s_' . ($targetInfo['image'] ?? 'normaltempplanet01') . '.jpg');

        $coordsLink = sprintf(
            '<a href="game.php?page=galaxy&amp;galaxy=%d&amp;system=%d" class="spy-planet-link">%s <span class="spy-coords">[%d:%d:%d] (%s)</span></a>',
            $galaxy, $system, $targetName, $galaxy, $system, $planet, $typeShort
        );

        $html = '<div class="spy-report-card combat-report-card msg-card">';
        
        // 1. Header (matches spy-header layout)
        $html .= '<div class="spy-header">';
        $html .= '<div class="spy-planet-avatar-wrapper">';
        $html .= '<img src="' . $dpath . 'planeten/small/' . $planetImg . '" alt="' . $targetName . '" class="spy-planet-avatar">';
        $html .= '</div>';
        $html .= '<div class="spy-header-info">';
        $html .= '<div class="spy-title-row">' . $coordsLink . '</div>';
        $html .= '<div class="spy-meta-row">';
        $html .= '<span class="spy-meta-player">';
        $html .= self::getSvg('sword', 13, 13) . ' ' . ($LNG['sys_mess_attack_report'] ?? 'Informe de Batalla');
        $html .= '</span>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="spy-counter-wrapper">';
        $html .= '<div class="spy-counter-pill ' . $pillClass . '">' . $badgeIcon . ' ' . $badgeText . '</div>';
        $html .= '</div>';
        $html .= '</div>';

        // 2. Losses Section
        $attLost = pretty_number($unitLost['attacker'] ?? 0);
        $defLost = pretty_number($unitLost['defender'] ?? 0);
        $totalLosses = pretty_number(($unitLost['attacker'] ?? 0) + ($unitLost['defender'] ?? 0));

        $html .= '<div class="spy-section">';
        $html .= '<div class="spy-section-head">';
        $html .= '<span class="spy-section-title">';
        $html .= self::getSvg('crosshair', 14, 14) . ' ' . ($LNG['sys_attack_title'] ?? 'Bajas de Combate');
        $html .= '</span>';
        $html .= '<span class="spy-badge-count" style="border-color:#0099cc; color:#38bdf8;">Total: ' . $totalLosses . ' ' . ($LNG['sys_units'] ?? 'unidades') . '</span>';
        $html .= '</div>';
        $html .= '<div class="combat-losses-grid">';
        $html .= '<div class="combat-loss-box loss-attacker">';
        $html .= '<span class="loss-role">' . ($LNG['sys_attack_attacker_pos'] ?? 'Bajas Atacante') . '</span>';
        $html .= '<strong class="loss-num">' . $attLost . '</strong>';
        $html .= '</div>';
        $html .= '<div class="combat-loss-box loss-defender">';
        $html .= '<span class="loss-role">' . ($LNG['sys_attack_defender_pos'] ?? 'Bajas Defensor') . '</span>';
        $html .= '<strong class="loss-num">' . $defLost . '</strong>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        // 3. Loot section
        $stealMetal = (float) ($stealResource[901] ?? 0);
        $stealCryst = (float) ($stealResource[902] ?? 0);
        $stealDeut  = (float) ($stealResource[903] ?? 0);
        $totalSteal = $stealMetal + $stealCryst + $stealDeut;

        if ($totalSteal > 0) {
            $html .= '<div class="spy-section">';
            $html .= '<div class="spy-section-head">';
            $html .= '<span class="spy-section-title">';
            $html .= '<img src="' . $dpath . 'img/resources/901f.png" class="spy-head-ico" alt=""> ' . ($LNG['sys_gain'] ?? 'Botín Obtenido');
            $html .= '</span>';
            $html .= '<span class="spy-loot-sum">Botín Total: <strong>' . pretty_number($totalSteal) . '</strong></span>';
            $html .= '</div>';
            $html .= '<div class="spy-resources-grid">';
            $html .= '<div class="spy-res-chip res-metal"><img src="' . $dpath . 'img/resources/901f.png" alt="Metal"><div class="spy-res-data"><div class="spy-res-label">' . ($LNG['tech'][901] ?? 'Metal') . '</div><div class="spy-res-val">' . pretty_number($stealMetal) . '</div></div></div>';
            $html .= '<div class="spy-res-chip res-crystal"><img src="' . $dpath . 'img/resources/902f.png" alt="Cristal"><div class="spy-res-data"><div class="spy-res-label">' . ($LNG['tech'][902] ?? 'Cristal') . '</div><div class="spy-res-val">' . pretty_number($stealCryst) . '</div></div></div>';
            $html .= '<div class="spy-res-chip res-deut"><img src="' . $dpath . 'img/resources/903f.png" alt="Deuterio"><div class="spy-res-data"><div class="spy-res-label">' . ($LNG['tech'][903] ?? 'Deuterio') . '</div><div class="spy-res-val">' . pretty_number($stealDeut) . '</div></div></div>';
            $html .= '</div>';
            $html .= '</div>';
        }

        // 4. Debris section
        $debMetal = (float) ($debris[901] ?? 0);
        $debCryst = (float) ($debris[902] ?? 0);
        $totalDeb = $debMetal + $debCryst;

        if ($totalDeb > 0) {
            $html .= '<div class="spy-section" style="border-color: rgba(16, 185, 129, 0.4);">';
            $html .= '<div class="spy-section-head" style="background: rgba(9, 35, 25, 0.7);">';
            $html .= '<span class="spy-section-title" style="color: #4ade80;">';
            $html .= self::getSvg('recycle', 14, 14) . ' ' . ($LNG['sys_debris'] ?? 'Campo de Escombros');
            $html .= '</span>';
            $html .= '<span class="spy-badge-count" style="border-color:#10b981; color:#4ade80;">Total: ' . pretty_number($totalDeb) . '</span>';
            $html .= '</div>';
            $html .= '<div class="spy-resources-grid">';
            $html .= '<div class="spy-res-chip res-metal"><img src="' . $dpath . 'img/resources/901f.png" alt="Metal"><div class="spy-res-data"><div class="spy-res-label">' . ($LNG['tech'][901] ?? 'Metal') . '</div><div class="spy-res-val">' . pretty_number($debMetal) . '</div></div></div>';
            $html .= '<div class="spy-res-chip res-crystal"><img src="' . $dpath . 'img/resources/902f.png" alt="Cristal"><div class="spy-res-data"><div class="spy-res-label">' . ($LNG['tech'][902] ?? 'Cristal') . '</div><div class="spy-res-val">' . pretty_number($debCryst) . '</div></div></div>';
            $html .= '</div>';
            $html .= '</div>';
        }

        // 5. Moon creation banner
        if (!empty($moonInfo['created'])) {
            $moonChance = (int) ($moonInfo['chance'] ?? 0);
            $html .= '<div class="combat-moon-banner">';
            $html .= '<img src="' . $dpath . 'planeten/small/s_mond.jpg" alt="Luna">';
            $html .= '<div>';
            $html .= '<strong>¡Formación Lunar Detectada!</strong>';
            $html .= '<p>' . sprintf($LNG['sys_moon_created'] ?? '¡Las enormes cantidades de metal y cristal flotante se han agrupado para formar una luna (%d%% de probabilidad)!', $moonChance) . '</p>';
            $html .= '</div>';
            $html .= '</div>';
        }

        // 6. Action Buttons Bar
        $html .= '<div class="spy-actions-bar">';
        
        $html .= '<a href="game.php?page=fleetTable&amp;galaxy=' . $galaxy . '&amp;system=' . $system . '&amp;planet=' . $planet . '&amp;planettype=' . $planetType . '&amp;target_mission=1" class="spy-btn spy-btn-attack">';
        $html .= self::getSvg('sword', 15, 15) . ' ' . ($LNG['type_mission_1'] ?? 'Atacar de Nuevo');
        $html .= '</a>';

        $html .= '<a href="CombatReport.php?raport=' . $reportId . '" target="_blank" class="spy-btn spy-btn-report">';
        $html .= self::getSvg('document', 15, 15) . ' ' . ($LNG['sys_mess_attack_report'] ?? 'Informe de Batalla');
        $html .= '</a>';

        $html .= '<a href="game.php?page=battleSimulator" class="spy-btn spy-btn-sim">';
        $html .= self::getSvg('simulate', 15, 15) . ' ' . ($LNG['fl_simulate'] ?? 'Simulador');
        $html .= '</a>';

        if ($totalDeb > 0) {
            $html .= '<a href="game.php?page=fleetTable&amp;galaxy=' . $galaxy . '&amp;system=' . $system . '&amp;planet=' . $planet . '&amp;planettype=2&amp;target_mission=8" class="spy-btn spy-btn-recycle">';
            $html .= self::getSvg('recycle', 15, 15) . ' ' . ($LNG['type_mission_8'] ?? 'Enviar Recicladores');
            $html .= '</a>';
        }

        $html .= '</div>'; // spy-actions-bar
        $html .= '</div>'; // combat-report-card

        return $html;
    }

    /**
     * Build Tactical Expedition Report Card
     */
    public static function buildExpeditionCard($eventType, $narrativeText, $rewardData, $fleetCoords, $LNG)
    {
        $dpath = self::getThemePath();

        // Badge determination
        $badgeClass = 'msg-badge-info';
        $badgeIcon  = self::getSvg('radar');
        $badgeText  = 'Expedición';

        switch ($eventType) {
            case 'resources':
                $badgeClass = 'msg-badge-win';
                $badgeIcon  = self::getSvg('box');
                $badgeText  = 'Recursos Extraídos';
                break;
            case 'darkmatter':
                $badgeClass = 'msg-badge-purple';
                $badgeIcon  = self::getSvg('radar');
                $badgeText  = 'Materia Oscura';
                break;
            case 'minerals':
                $badgeClass = 'msg-badge-win';
                $badgeIcon  = self::getSvg('box');
                $badgeText  = 'Minerales Raros';
                break;
            case 'container':
                $badgeClass = 'msg-badge-warning';
                $badgeIcon  = self::getSvg('box');
                $badgeText  = 'Contenedor Táctico';
                break;
            case 'stardust':
                $badgeClass = 'msg-badge-purple';
                $badgeIcon  = self::getSvg('radar');
                $badgeText  = 'Polvo Estelar';
                break;
            case 'fleet':
                $badgeClass = 'msg-badge-win';
                $badgeIcon  = self::getSvg('check');
                $badgeText  = 'Flota Recuperada';
                break;
            case 'time_slow':
            case 'time_fast':
                $badgeClass = 'msg-badge-info';
                $badgeIcon  = self::getSvg('time');
                $badgeText  = 'Anomalía Temporal';
                break;
            case 'lost':
                $badgeClass = 'msg-badge-loss';
                $badgeIcon  = self::getSvg('close');
                $badgeText  = 'Flota Destruida';
                break;
            case 'nothing':
            default:
                $badgeClass = 'msg-badge-neutral';
                $badgeIcon  = self::getSvg('target');
                $badgeText  = 'Sector Vacío';
                break;
        }

        $galaxy = (int) ($fleetCoords['galaxy'] ?? 1);
        $system = (int) ($fleetCoords['system'] ?? 1);

        $html = '<div class="msg-card msg-card-expedition">';
        
        // Header
        $html .= '<div class="msg-card-header">';
        $html .= '<div class="msg-card-title">';
        $html .= self::getSvg('radar', 15, 15) . ' ' . ($LNG['sys_expe_report'] ?? 'Informe de Expedición') . ' &bull; Sector 16';
        $html .= '</div>';
        $html .= '<div class="msg-badge ' . $badgeClass . '">' . $badgeIcon . ' ' . $badgeText . '</div>';
        $html .= '</div>';

        $html .= '<div class="msg-card-body">';

        // Narrative box
        if (!empty($narrativeText)) {
            $html .= '<div class="msg-narrative">';
            $html .= '&ldquo;' . strip_tags($narrativeText, '<br><span><strong>') . '&rdquo;';
            $html .= '</div>';
        }

        // Rewards visual display
        if (!empty($rewardData['resources'])) {
            $html .= '<div class="msg-res-grid">';
            foreach ($rewardData['resources'] as $resId => $amount) {
                if ($amount <= 0) continue;
                $resKey = ($resId == 901) ? 'metal' : (($resId == 902) ? 'crystal' : 'deut');
                $resName = $LNG['tech'][$resId] ?? 'Recurso';
                $html .= '<div class="msg-res-chip res-' . $resKey . '">';
                $html .= '<img src="' . $dpath . 'img/resources/' . $resId . 'f.png" alt="' . $resName . '">';
                $html .= '<div class="msg-res-data">';
                $html .= '<div class="msg-res-label">' . $resName . '</div>';
                $html .= '<div class="msg-res-val">+' . pretty_number($amount) . '</div>';
                $html .= '</div>';
                $html .= '</div>';
            }
            $html .= '</div>';

            if (!empty($rewardData['capacityLost']) && $rewardData['capacityLost'] > 0) {
                $html .= '<div style="font-size:11px; color:#f87171; margin-bottom:8px; display:flex; align-items:center; gap:4px;">';
                $html .= self::getSvg('warning', 12, 12) . ' ' . sprintf($LNG['sys_expe_capacity_warning'] ?? 'Capacidad de carga insuficiente: se perdieron %s unidades por falta de bodega.', pretty_number($rewardData['capacityLost']));
                $html .= '</div>';
            }
        }

        // Dark Matter
        if (!empty($rewardData['darkmatter']) && $rewardData['darkmatter'] > 0) {
            $html .= '<div class="msg-res-grid">';
            $html .= '<div class="msg-res-chip res-dm">';
            $html .= '<img src="' . $dpath . 'img/resources/921f.png" alt="Materia Oscura">';
            $html .= '<div class="msg-res-data">';
            $html .= '<div class="msg-res-label">' . ($LNG['tech'][921] ?? 'Materia Oscura') . '</div>';
            $html .= '<div class="msg-res-val">+' . pretty_number($rewardData['darkmatter']) . '</div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
        }

        // Stardust
        if (!empty($rewardData['stardust']) && $rewardData['stardust'] > 0) {
            $html .= '<div class="msg-res-grid">';
            $html .= '<div class="msg-res-chip res-so">';
            $html .= '<img src="' . $dpath . 'img/resources/923f.png" alt="Polvo Estelar">';
            $html .= '<div class="msg-res-data">';
            $html .= '<div class="msg-res-label">' . ($LNG['tech'][923] ?? 'Polvo Estelar') . '</div>';
            $html .= '<div class="msg-res-val">+' . pretty_number($rewardData['stardust']) . '</div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
        }

        // Containers
        if (!empty($rewardData['container']) && $rewardData['container'] > 0) {
            $html .= '<div class="msg-res-grid">';
            $html .= '<div class="msg-res-chip res-cont">';
            $html .= '<img src="' . $dpath . 'img/resources/924f.png" alt="Contenedores">';
            $html .= '<div class="msg-res-data">';
            $html .= '<div class="msg-res-label">' . ($LNG['tech'][924] ?? 'Contenedores') . '</div>';
            $html .= '<div class="msg-res-val">+' . pretty_number($rewardData['container']) . '</div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
        }

        // Minerals
        if (!empty($rewardData['minerals']) && is_array($rewardData['minerals'])) {
            $html .= '<div class="msg-res-grid">';
            foreach ($rewardData['minerals'] as $mId => $mAmount) {
                $mName = $LNG['tech'][$mId] ?? 'Mineral';
                $html .= '<div class="msg-res-chip res-minerals">';
                $html .= '<img src="' . $dpath . 'img/resources/901f.png" alt="' . $mName . '">';
                $html .= '<div class="msg-res-data">';
                $html .= '<div class="msg-res-label">' . $mName . '</div>';
                $html .= '<div class="msg-res-val">+' . pretty_number($mAmount) . '</div>';
                $html .= '</div>';
                $html .= '</div>';
            }
            $html .= '</div>';
        }

        // Ships recovered as COMPACT CHIPS BAR (as explicitly chosen by user!)
        if (!empty($rewardData['fleet']) && is_array($rewardData['fleet'])) {
            $html .= '<div class="msg-panel">';
            $html .= '<div class="msg-panel-head">';
            $html .= '<span>' . self::getSvg('check', 13, 13) . ' ' . ($LNG['tech'][200] ?? 'Flota Integrada') . '</span>';
            $html .= '</div>';
            $html .= '<div class="msg-unit-chips">';
            foreach ($rewardData['fleet'] as $shipId => $shipCount) {
                if ($shipCount <= 0) continue;
                $shipName = $LNG['tech'][$shipId] ?? ('Nave ' . $shipId);
                $html .= '<span class="msg-unit-chip">';
                $html .= '<img src="' . $dpath . 'gebaeude/' . $shipId . '.gif" alt="' . $shipName . '">';
                $html .= '<span class="unit-name">' . $shipName . '</span>';
                $html .= '<strong class="unit-count">+' . pretty_number($shipCount) . '</strong>';
                $html .= '</span>';
            }
            $html .= '</div>';
            $html .= '</div>';
        }

        // Action Buttons Bar
        $html .= '<div class="msg-actions-bar">';
        $html .= '<a href="game.php?page=fleetTable&amp;galaxy=' . $galaxy . '&amp;system=' . $system . '&amp;planet=16&amp;planettype=1&amp;target_mission=15" class="msg-btn msg-btn-primary" style="background:#092942; border:1px solid #0099cc; color:#66ccff;">';
        $html .= self::getSvg('radar') . ' ' . ($LNG['type_mission_15'] ?? 'Nueva Expedición');
        $html .= '</a>';
        $html .= '<a href="game.php?page=galaxy&amp;galaxy=' . $galaxy . '&amp;system=' . $system . '" class="msg-btn msg-btn-secondary">';
        $html .= self::getSvg('target') . ' ' . ($LNG['gl_galaxy'] ?? 'Ver Galaxia');
        $html .= '</a>';
        $html .= '</div>';

        $html .= '</div>'; // msg-card-body
        $html .= '</div>'; // msg-card

        return $html;
    }

    /**
     * Build Tactical Logistics Card (Transport, Stationing, Returns)
     */
    public static function buildLogisticsCard($mode, $title, $origin, $target, $resources, $LNG)
    {
        $dpath = self::getThemePath();

        switch ($mode) {
            case 'transport_owner':
                $badgeClass = 'msg-badge-success';
                $badgeIcon  = self::getSvg('check');
                $badgeText  = 'Entrega Realizada';
                break;
            case 'transport_target':
                $badgeClass = 'msg-badge-info';
                $badgeIcon  = self::getSvg('box');
                $badgeText  = 'Suministros Recibidos';
                break;
            case 'stay':
                $badgeClass = 'msg-badge-info';
                $badgeIcon  = self::getSvg('shield');
                $badgeText  = 'Flota Estacionada';
                break;
            case 'return':
            default:
                $badgeClass = 'msg-badge-neutral';
                $badgeIcon  = self::getSvg('arrow-right');
                $badgeText  = 'Retorno a Base';
                break;
        }

        $originName   = htmlspecialchars($origin['name'] ?? '', ENT_QUOTES, 'UTF-8');
        $originCoords = sprintf(
            '<a href="game.php?page=galaxy&amp;galaxy=%d&amp;system=%d">[%d:%d:%d]</a>',
            $origin['galaxy'], $origin['system'], $origin['galaxy'], $origin['system'], $origin['planet']
        );

        $targetName   = htmlspecialchars($target['name'] ?? '', ENT_QUOTES, 'UTF-8');
        $targetCoords = sprintf(
            '<a href="game.php?page=galaxy&amp;galaxy=%d&amp;system=%d">[%d:%d:%d]</a>',
            $target['galaxy'], $target['system'], $target['galaxy'], $target['system'], $target['planet']
        );

        $html = '<div class="msg-card msg-card-logistics">';
        
        // Header
        $html .= '<div class="msg-card-header">';
        $html .= '<div class="msg-card-title">';
        $html .= self::getSvg('box', 15, 15) . ' ' . $title;
        $html .= '</div>';
        $html .= '<div class="msg-badge ' . $badgeClass . '">' . $badgeIcon . ' ' . $badgeText . '</div>';
        $html .= '</div>';

        $html .= '<div class="msg-card-body">';

        // Route row
        $html .= '<div class="msg-meta-row" style="background: rgba(9, 23, 41, 0.6); padding: 6px 10px; border-radius: 4px; margin-bottom: 10px;">';
        $html .= '<span class="msg-meta-item"><strong>Origen:</strong> ' . $originName . ' ' . $originCoords . '</span>';
        $html .= '<span style="color:#66ccff;">' . self::getSvg('arrow-right', 12, 12) . '</span>';
        $html .= '<span class="msg-meta-item"><strong>Destino:</strong> ' . $targetName . ' ' . $targetCoords . '</span>';
        $html .= '</div>';

        // Resources grid
        $metal = (float) ($resources[901] ?? 0);
        $cryst = (float) ($resources[902] ?? 0);
        $deut  = (float) ($resources[903] ?? 0);
        $dm    = (float) ($resources[921] ?? 0);

        if ($metal > 0 || $cryst > 0 || $deut > 0 || $dm > 0) {
            $html .= '<div class="msg-res-grid">';
            if ($metal > 0) {
                $html .= '<div class="msg-res-chip res-metal"><img src="' . $dpath . 'img/resources/901f.png" alt="Metal"><div class="msg-res-data"><div class="msg-res-label">' . ($LNG['tech'][901] ?? 'Metal') . '</div><div class="msg-res-val">' . pretty_number($metal) . '</div></div></div>';
            }
            if ($cryst > 0) {
                $html .= '<div class="msg-res-chip res-crystal"><img src="' . $dpath . 'img/resources/902f.png" alt="Cristal"><div class="msg-res-data"><div class="msg-res-label">' . ($LNG['tech'][902] ?? 'Cristal') . '</div><div class="msg-res-val">' . pretty_number($cryst) . '</div></div></div>';
            }
            if ($deut > 0) {
                $html .= '<div class="msg-res-chip res-deut"><img src="' . $dpath . 'img/resources/903f.png" alt="Deuterio"><div class="msg-res-data"><div class="msg-res-label">' . ($LNG['tech'][903] ?? 'Deuterio') . '</div><div class="msg-res-val">' . pretty_number($deut) . '</div></div></div>';
            }
            if ($dm > 0) {
                $html .= '<div class="msg-res-chip res-dm"><img src="' . $dpath . 'img/resources/921f.png" alt="Materia Oscura"><div class="msg-res-data"><div class="msg-res-label">' . ($LNG['tech'][921] ?? 'Materia Oscura') . '</div><div class="msg-res-val">' . pretty_number($dm) . '</div></div></div>';
            }
            $html .= '</div>';
        }

        // Action Buttons Bar
        $html .= '<div class="msg-actions-bar">';
        $html .= '<a href="game.php?page=fleetTable&amp;galaxy=' . (int)$target['galaxy'] . '&amp;system=' . (int)$target['system'] . '&amp;planet=' . (int)$target['planet'] . '&amp;planettype=' . (int)($target['planet_type'] ?? 1) . '&amp;target_mission=3" class="msg-btn msg-btn-primary" style="background:#092942; border:1px solid #0099cc; color:#66ccff;">';
        $html .= self::getSvg('box') . ' ' . ($LNG['type_mission_3'] ?? 'Transportar de Nuevo');
        $html .= '</a>';
        $html .= '<a href="game.php?page=galaxy&amp;galaxy=' . (int)$target['galaxy'] . '&amp;system=' . (int)$target['system'] . '" class="msg-btn msg-btn-secondary">';
        $html .= self::getSvg('target') . ' ' . ($LNG['gl_galaxy'] ?? 'Ver Galaxia');
        $html .= '</a>';
        $html .= '</div>';

        $html .= '</div>'; // msg-card-body
        $html .= '</div>'; // msg-card

        return $html;
    }

    /**
     * Build Tactical Recycling / Debris Harvesting Card
     */
    public static function buildRecyclingCard($targetCoords, $harvestedMetal, $harvestedCrystal, $LNG)
    {
        $dpath = self::getThemePath();

        $galaxy = (int) $targetCoords['galaxy'];
        $system = (int) $targetCoords['system'];
        $planet = (int) $targetCoords['planet'];

        $totalHarvested = (float)$harvestedMetal + (float)$harvestedCrystal;

        $coordsLink = sprintf(
            '<a href="game.php?page=galaxy&amp;galaxy=%d&amp;system=%d">[%d:%d:%d]</a>',
            $galaxy, $system, $galaxy, $system, $planet
        );

        $html = '<div class="msg-card msg-card-recycle">';
        
        // Header
        $html .= '<div class="msg-card-header">';
        $html .= '<div class="msg-card-title">';
        $html .= self::getSvg('recycle', 15, 15) . ' ' . ($LNG['sys_recy_report'] ?? 'Informe de Reciclaje') . ' &bull; ' . $coordsLink;
        $html .= '</div>';
        $html .= '<div class="msg-badge msg-badge-win">' . self::getSvg('check') . ' Cosecha Exitosa</div>';
        $html .= '</div>';

        $html .= '<div class="msg-card-body">';

        $html .= '<div class="msg-res-grid">';
        $html .= '<div class="msg-res-chip res-metal"><img src="' . $dpath . 'img/resources/901f.png" alt="Metal"><div class="msg-res-data"><div class="msg-res-label">' . ($LNG['tech'][901] ?? 'Metal') . '</div><div class="msg-res-val">+' . pretty_number($harvestedMetal) . '</div></div></div>';
        $html .= '<div class="msg-res-chip res-crystal"><img src="' . $dpath . 'img/resources/902f.png" alt="Cristal"><div class="msg-res-data"><div class="msg-res-label">' . ($LNG['tech'][902] ?? 'Cristal') . '</div><div class="msg-res-val">+' . pretty_number($harvestedCrystal) . '</div></div></div>';
        $html .= '</div>';

        // Action Buttons Bar
        $html .= '<div class="msg-actions-bar">';
        $html .= '<a href="game.php?page=fleetTable&amp;galaxy=' . $galaxy . '&amp;system=' . $system . '&amp;planet=' . $planet . '&amp;planettype=2&amp;target_mission=8" class="msg-btn msg-btn-recycle">';
        $html .= self::getSvg('recycle') . ' ' . ($LNG['type_mission_8'] ?? 'Reciclar de Nuevo');
        $html .= '</a>';
        $html .= '<a href="game.php?page=galaxy&amp;galaxy=' . $galaxy . '&amp;system=' . $system . '" class="msg-btn msg-btn-secondary">';
        $html .= self::getSvg('target') . ' ' . ($LNG['gl_galaxy'] ?? 'Ver Galaxia');
        $html .= '</a>';
        $html .= '</div>';

        $html .= '</div>'; // msg-card-body
        $html .= '</div>'; // msg-card

        return $html;
    }

    /**
     * Build Tactical Missile Attack Card
     */
    public static function buildMissileCard($origin, $target, $totalMissiles, $intercepted, $destroyedDefenses, $isAttacker, $LNG)
    {
        $dpath = self::getThemePath();

        $badgeClass = $isAttacker ? 'msg-badge-win' : 'msg-badge-loss';
        $badgeText  = $isAttacker ? 'Ataque Ejecutado' : 'Impacto Recibido';

        $html = '<div class="msg-card msg-card-missile">';
        
        // Header
        $html .= '<div class="msg-card-header">';
        $html .= '<div class="msg-card-title">';
        $html .= self::getSvg('rocket', 15, 15) . ' ' . ($LNG['sys_irak_subject'] ?? 'Ataque con Misiles Interplanetarios');
        $html .= '</div>';
        $html .= '<div class="msg-badge ' . $badgeClass . '">' . self::getSvg('rocket') . ' ' . $badgeText . '</div>';
        $html .= '</div>';

        $html .= '<div class="msg-card-body">';

        // Telemetry row
        $html .= '<div class="msg-meta-row" style="background: rgba(9, 23, 41, 0.6); padding: 6px 10px; border-radius: 4px; margin-bottom: 10px;">';
        $html .= '<span class="msg-meta-item"><strong>Lanzados:</strong> ' . pretty_number($totalMissiles) . '</span>';
        $html .= '<span class="msg-meta-item"><strong>Interceptados por ABM:</strong> ' . pretty_number($intercepted) . '</span>';
        $html .= '<span class="msg-meta-item"><strong>Impactos:</strong> ' . pretty_number(max(0, $totalMissiles - $intercepted)) . '</span>';
        $html .= '</div>';

        // Destroyed defenses grid
        if (!empty($destroyedDefenses) && is_array($destroyedDefenses)) {
            $html .= '<div class="msg-panel">';
            $html .= '<div class="msg-panel-head">';
            $html .= '<span>' . self::getSvg('shield', 13, 13) . ' ' . ($LNG['sys_destroyed_defenses'] ?? 'Estructuras Defensivas Destruidas') . '</span>';
            $html .= '</div>';
            $html .= '<div class="msg-unit-grid">';
            foreach ($destroyedDefenses as $defId => $count) {
                if ($count <= 0) continue;
                $defName = $LNG['tech'][$defId] ?? ('Defensa ' . $defId);
                $html .= '<div class="msg-unit-item">';
                $html .= '<img src="' . $dpath . 'gebaeude/' . $defId . '.gif" alt="' . $defName . '">';
                $html .= '<div class="msg-unit-item-info">';
                $html .= '<div class="msg-unit-item-name" title="' . $defName . '">' . $defName . '</div>';
                $html .= '<div class="msg-unit-item-count">-' . pretty_number($count) . '</div>';
                $html .= '</div>';
                $html .= '</div>';
            }
            $html .= '</div>';
            $html .= '</div>';
        } else {
            $html .= '<div style="font-size:12px; color:#94a3b8; font-style:italic; padding:6px 0;">' . ($LNG['sys_irak_no_def'] ?? 'El planeta no tenía defensas o todos los misiles fueron interceptados.') . '</div>';
        }

        $html .= '</div>'; // msg-card-body
        $html .= '</div>'; // msg-card

        return $html;
    }

    /**
     * Build Tactical Moon Destruction Card
     */
    public static function buildMoonDestructionCard($moonName, $coords, $moonChance, $fleetRisk, $moonSuccess, $fleetDestroyed, $LNG)
    {
        $dpath = self::getThemePath();

        if ($moonSuccess === 1) {
            $badgeClass = 'msg-badge-win';
            $badgeText  = 'Luna Destruida';
        } elseif ($moonSuccess === 0) {
            $badgeClass = 'msg-badge-loss';
            $badgeText  = 'Destrucción Fallida';
        } else {
            $badgeClass = 'msg-badge-neutral';
            $badgeText  = 'Misión Abortada';
        }

        $galaxy = (int) $coords['galaxy'];
        $system = (int) $coords['system'];
        $planet = (int) $coords['planet'];

        $html = '<div class="msg-card msg-card-destruction">';
        
        // Header
        $html .= '<div class="msg-card-header">';
        $html .= '<div class="msg-card-title">';
        $html .= self::getSvg('moon', 15, 15) . ' ' . ($LNG['type_mission_9'] ?? 'Destrucción de Luna') . ' &bull; ' . htmlspecialchars($moonName, ENT_QUOTES, 'UTF-8');
        $html .= '</div>';
        $html .= '<div class="msg-badge ' . $badgeClass . '">' . self::getSvg('moon') . ' ' . $badgeText . '</div>';
        $html .= '</div>';

        $html .= '<div class="msg-card-body">';

        // Telemetry odds
        $html .= '<div class="msg-meta-row" style="background: rgba(9, 23, 41, 0.6); padding: 6px 10px; border-radius: 4px; margin-bottom: 10px;">';
        $html .= '<span class="msg-meta-item"><strong>Probabilidad de Destruir Luna:</strong> ' . (int)$moonChance . '%</span>';
        $html .= '<span class="msg-meta-item"><strong>Riesgo de Autodestrucción Flota:</strong> ' . (int)$fleetRisk . '%</span>';
        $html .= '</div>';

        // Outcome narrative
        $html .= '<div class="msg-narrative">';
        if ($moonSuccess === 1) {
            $html .= $LNG['sys_destruc_reussi'] ?? 'Los rayos de las estrellas de la muerte alcanzaron la luna y la hicieron pedazos. La luna entera fue destruida.';
        } elseif ($fleetDestroyed) {
            $html .= $LNG['sys_destruc_echec'] ?? 'Ocurrió una catástrofe en los reactores gravitatorios: las estrellas de la muerte explotaron y la onda de choque desintegró toda la flota.';
        } else {
            $html .= $LNG['sys_destruc_null'] ?? 'Las estrellas de la muerte no pudieron cargar completamente antes de disparar e implosionaron. La luna permanece intacta.';
        }
        $html .= '</div>';

        $html .= '</div>'; // msg-card-body
        $html .= '</div>'; // msg-card

        return $html;
    }

    /**
     * Build Tactical Colonization Card
     */
    public static function buildColonizationCard($status, $coords, $colonyName, $LNG)
    {
        $dpath = self::getThemePath();

        $galaxy = (int) $coords['galaxy'];
        $system = (int) $coords['system'];
        $planet = (int) $coords['planet'];

        $coordsLink = sprintf(
            '<a href="game.php?page=galaxy&amp;galaxy=%d&amp;system=%d">[%d:%d:%d]</a>',
            $galaxy, $system, $galaxy, $system, $planet
        );

        if ($status === 'success') {
            $badgeClass = 'msg-badge-success';
            $badgeText  = 'Colonia Fundada';
            $badgeIcon  = self::getSvg('check');
            $message    = sprintf($LNG['sys_colo_allisok'] ?? 'Los colonos han tomado posesión del planeta y fundado la colonia en %s.', $coordsLink);
        } elseif ($status === 'max_colonies') {
            $badgeClass = 'msg-badge-danger';
            $badgeText  = 'Límite de Colonias';
            $badgeIcon  = self::getSvg('warning');
            $message    = sprintf($LNG['sys_colo_maxcolo'] ?? 'No se pudo fundar la colonia en %s porque alcanzaste el límite máximo permitido por tu tecnología.', $coordsLink);
        } else {
            $badgeClass = 'msg-badge-danger';
            $badgeText  = 'Posición Ocupada';
            $badgeIcon  = self::getSvg('close');
            $message    = sprintf($LNG['sys_colo_badpos'] ?? 'No se pudo fundar la colonia: la posición %s ya fue ocupada por otro imperio.', $coordsLink);
        }

        $html = '<div class="msg-card msg-card-colonization">';
        
        // Header
        $html .= '<div class="msg-card-header">';
        $html .= '<div class="msg-card-title">';
        $html .= self::getSvg('target', 15, 15) . ' ' . ($LNG['type_mission_7'] ?? 'Colonización Espacial') . ' &bull; ' . $coordsLink;
        $html .= '</div>';
        $html .= '<div class="msg-badge ' . $badgeClass . '">' . $badgeIcon . ' ' . $badgeText . '</div>';
        $html .= '</div>';

        $html .= '<div class="msg-card-body">';
        $html .= '<div class="msg-narrative">' . $message . '</div>';

        // Action Buttons Bar
        $html .= '<div class="msg-actions-bar">';
        $html .= '<a href="game.php?page=galaxy&amp;galaxy=' . $galaxy . '&amp;system=' . $system . '" class="msg-btn msg-btn-secondary">';
        $html .= self::getSvg('target') . ' ' . ($LNG['gl_galaxy'] ?? 'Ver Galaxia');
        $html .= '</a>';
        $html .= '</div>';

        $html .= '</div>'; // msg-card-body
        $html .= '</div>'; // msg-card

        return $html;
    }

    /**
     * Build Tactical Alert Card (Defensive Sensor Alert when enemy spied on player)
     */
    public static function buildSpyAlertCard($attackerCoords, $targetInfo, $LNG)
    {
        $dpath = self::getThemePath();

        $targetName = htmlspecialchars($targetInfo['name'] ?? '', ENT_QUOTES, 'UTF-8');
        $tGalaxy    = (int) $targetInfo['galaxy'];
        $tSystem    = (int) $targetInfo['system'];
        $tPlanet    = (int) $targetInfo['planet'];
        $tType      = (int) ($targetInfo['planet_type'] ?? 1);
        $typeShort  = $LNG['type_planet_short_' . $tType] ?? ($tType == 3 ? 'Luna' : 'Planeta');
        $planetImg  = ($tType == 3) ? 's_mond.jpg' : ('s_' . ($targetInfo['image'] ?? 'normaltempplanet01') . '.jpg');

        $targetCoordsLink = sprintf(
            '<a href="game.php?page=galaxy&amp;galaxy=%d&amp;system=%d" class="spy-planet-link">%s <span class="spy-coords">[%d:%d:%d] (%s)</span></a>',
            $tGalaxy, $tSystem, $targetName, $tGalaxy, $tSystem, $tPlanet, $typeShort
        );

        $aGalaxy = (int) $attackerCoords['galaxy'];
        $aSystem = (int) $attackerCoords['system'];
        $aPlanet = (int) $attackerCoords['planet'];

        $attackerCoordsLink = sprintf(
            '<a href="game.php?page=galaxy&amp;galaxy=%d&amp;system=%d" class="spy-planet-link"><span class="spy-coords">[%d:%d:%d]</span></a>',
            $aGalaxy, $aSystem, $aGalaxy, $aSystem, $aPlanet
        );

        $html = '<div class="spy-report-card combat-report-card msg-card" style="border-color: rgba(239, 68, 68, 0.4);">';

        // Header
        $html .= '<div class="spy-header" style="background: rgba(45, 12, 18, 0.7); border-color: rgba(239, 68, 68, 0.4);">';
        $html .= '<div class="spy-planet-avatar-wrapper" style="border-color: #ef4444; box-shadow: 0 0 10px rgba(239, 68, 68, 0.4);">';
        $html .= '<img src="' . $dpath . 'planeten/small/' . $planetImg . '" alt="' . $targetName . '" class="spy-planet-avatar">';
        $html .= '</div>';
        $html .= '<div class="spy-header-info">';
        $html .= '<div class="spy-title-row">' . $targetCoordsLink . '</div>';
        $html .= '<div class="spy-meta-row">';
        $html .= '<span class="spy-meta-player" style="color:#fca5a5;">';
        $html .= self::getSvg('radar', 13, 13) . ' ' . ($LNG['sys_mess_spy_control'] ?? 'Control de Espionaje');
        $html .= '</span>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="spy-counter-wrapper">';
        $html .= '<div class="spy-counter-pill pill-danger">' . self::getSvg('warning', 14, 14) . ' ' . ($LNG['sys_mess_spy_activity'] ?? 'Actividad Hostil') . '</div>';
        $html .= '</div>';
        $html .= '</div>';

        // Sensor Alert Body
        $alertDesc = sprintf(
            $LNG['sys_mess_spy_seen_at'] ?? 'Una flota enemiga de reconocimiento procedente de %s fue detectada en las proximidades de tu colonia.',
            $attackerCoordsLink
        );

        $html .= '<div class="spy-section" style="border-color: rgba(239, 68, 68, 0.3); background: rgba(20, 8, 12, 0.6);">';
        $html .= '<div class="spy-section-head" style="background: rgba(35, 10, 15, 0.8); border-bottom-color: rgba(239, 68, 68, 0.3);">';
        $html .= '<span class="spy-section-title" style="color: #fca5a5;">';
        $html .= self::getSvg('radar', 14, 14) . ' ' . ($LNG['sys_mess_spy_report'] ?? 'Informe de Sensores Planetarios');
        $html .= '</span>';
        $html .= '</div>';
        $html .= '<div style="padding: 10px 12px; font-size: 13px; line-height: 1.5; color: #f1f5f9;">' . $alertDesc . '</div>';
        $html .= '</div>';

        // Action Buttons
        $html .= '<div class="spy-actions-bar">';
        $html .= '<a href="game.php?page=fleetTable&amp;galaxy=' . $aGalaxy . '&amp;system=' . $aSystem . '&amp;planet=' . $aPlanet . '&amp;planettype=1&amp;target_mission=6" class="spy-btn spy-btn-sim">';
        $html .= self::getSvg('radar', 15, 15) . ' ' . ($LNG['type_mission_6'] ?? 'Contra-Espiar');
        $html .= '</a>';
        $html .= '<a href="game.php?page=fleetTable&amp;galaxy=' . $aGalaxy . '&amp;system=' . $aSystem . '&amp;planet=' . $aPlanet . '&amp;planettype=1&amp;target_mission=1" class="spy-btn spy-btn-attack">';
        $html .= self::getSvg('sword', 15, 15) . ' ' . ($LNG['type_mission_1'] ?? 'Atacar');
        $html .= '</a>';
        $html .= '<a href="game.php?page=galaxy&amp;galaxy=' . $aGalaxy . '&amp;system=' . $aSystem . '" class="spy-btn spy-btn-galaxy">';
        $html .= self::getSvg('target', 15, 15) . ' ' . ($LNG['gl_galaxy'] ?? 'Ver Galaxia');
        $html .= '</a>';
        $html .= '</div>';

        $html .= '</div>'; // spy-report-card

        return $html;
    }

    /**
     * Build Tactical Achievement Unlocked Card
     */
    public static function buildAchievementCard($elementId, $level, $LNG)
    {
        $dpath = self::getThemePath();
        $techName = $LNG['tech'][$elementId] ?? ('Elemento #' . $elementId);

        $html = '<div class="spy-report-card combat-report-card msg-card" style="border-color: rgba(234, 179, 8, 0.4);">';

        // Header
        $html .= '<div class="spy-header" style="background: rgba(35, 28, 10, 0.7); border-color: rgba(234, 179, 8, 0.4);">';
        $html .= '<div class="spy-planet-avatar-wrapper" style="border-color: #facc15; box-shadow: 0 0 10px rgba(250, 204, 21, 0.4);">';
        $html .= '<img src="' . $dpath . 'gebaeude/' . (int)$elementId . '.gif" alt="' . htmlspecialchars($techName, ENT_QUOTES, 'UTF-8') . '" class="spy-planet-avatar">';
        $html .= '</div>';
        $html .= '<div class="spy-header-info">';
        $html .= '<div class="spy-title-row" style="color: #facc15;">' . ($LNG['lm_achievements'] ?? 'Logro del Sistema') . '</div>';
        $html .= '<div class="spy-meta-row">';
        $html .= '<span class="spy-meta-player">';
        $html .= self::getSvg('star', 13, 13) . ' <strong>' . htmlspecialchars($techName, ENT_QUOTES, 'UTF-8') . '</strong>';
        $html .= '</span>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="spy-counter-wrapper">';
        $html .= '<div class="spy-counter-pill pill-warning">' . self::getSvg('check', 14, 14) . ' ' . ($LNG['ach_reached'] ?? 'Alcanzado') . ' ' . (int)$level . '</div>';
        $html .= '</div>';
        $html .= '</div>';

        // Content
        $html .= '<div class="spy-section" style="border-color: rgba(234, 179, 8, 0.3); background: rgba(18, 15, 6, 0.6);">';
        $html .= '<div class="spy-section-head" style="background: rgba(28, 22, 8, 0.8); border-bottom-color: rgba(234, 179, 8, 0.3);">';
        $html .= '<span class="spy-section-title" style="color: #fde047;">';
        $html .= self::getSvg('star', 14, 14) . ' ' . htmlspecialchars($techName, ENT_QUOTES, 'UTF-8') . ' &bull; ' . ($LNG['ach_reached'] ?? 'Nivel') . ' ' . (int)$level;
        $html .= '</span>';
        $html .= '</div>';
        $html .= '<div style="padding: 10px 12px; font-size: 13px; line-height: 1.5; color: #fef08a;">';
        $html .= sprintf($LNG['ach_unlocked_desc'] ?? '¡Has alcanzado exitosamente el nivel %d de este logro! Consulta tus bonificaciones militares y de producción.', (int)$level);
        $html .= '</div>';
        $html .= '</div>';

        // Action Buttons
        $html .= '<div class="spy-actions-bar">';
        $html .= '<a href="#" onclick="return Dialog.info(' . (int)$elementId . ');" class="spy-btn spy-btn-sim">';
        $html .= self::getSvg('document', 15, 15) . ' ' . ($LNG['ach_bonus'] ?? 'Ver Bonificación');
        $html .= '</a>';
        $html .= '<a href="game.php?page=achievements" class="spy-btn spy-btn-report">';
        $html .= self::getSvg('star', 15, 15) . ' ' . ($LNG['ach_go_achievements'] ?? 'Ir a Logros');
        $html .= '</a>';
        $html .= '</div>';

        $html .= '</div>'; // spy-report-card

        return $html;
    }

    /**
     * Build Tactical Queue Cancelled Card (when lack of resources halts building/research)
     */
    public static function buildQueueCancelCard($type, $elementId, $planetInfo, $LNG)
    {
        $dpath = self::getThemePath();
        $techName = $LNG['tech'][$elementId] ?? ('Elemento #' . $elementId);
        $planetName = htmlspecialchars($planetInfo['name'] ?? '', ENT_QUOTES, 'UTF-8');
        $galaxy = (int) $planetInfo['galaxy'];
        $system = (int) $planetInfo['system'];
        $planet = (int) $planetInfo['planet'];

        $coordsLink = sprintf(
            '<a href="game.php?page=galaxy&amp;galaxy=%d&amp;system=%d" class="spy-planet-link">%s <span class="spy-coords">[%d:%d:%d]</span></a>',
            $galaxy, $system, $planetName, $galaxy, $system, $planet
        );

        $typeName = ($type === 'research') ? ($LNG['lm_research'] ?? 'Investigación') : ($LNG['lm_buildings'] ?? 'Construcción');
        $targetPage = ($type === 'research') ? 'research' : 'buildings';

        $html = '<div class="spy-report-card combat-report-card msg-card" style="border-color: rgba(239, 68, 68, 0.4);">';

        // Header
        $html .= '<div class="spy-header" style="background: rgba(45, 12, 18, 0.7); border-color: rgba(239, 68, 68, 0.4);">';
        $html .= '<div class="spy-planet-avatar-wrapper" style="border-color: #ef4444;">';
        $html .= '<img src="' . $dpath . 'gebaeude/' . (int)$elementId . '.gif" alt="' . htmlspecialchars($techName, ENT_QUOTES, 'UTF-8') . '" class="spy-planet-avatar">';
        $html .= '</div>';
        $html .= '<div class="spy-header-info">';
        $html .= '<div class="spy-title-row">' . $coordsLink . '</div>';
        $html .= '<div class="spy-meta-row">';
        $html .= '<span class="spy-meta-player" style="color: #fca5a5;">';
        $html .= self::getSvg('warning', 13, 13) . ' ' . $typeName . ' &bull; ' . htmlspecialchars($techName, ENT_QUOTES, 'UTF-8');
        $html .= '</span>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="spy-counter-wrapper">';
        $html .= '<div class="spy-counter-pill pill-danger">' . self::getSvg('close', 14, 14) . ' ' . ($LNG['sys_buildlist_fail'] ?? 'Cola Interrumpida') . '</div>';
        $html .= '</div>';
        $html .= '</div>';

        // Content
        $html .= '<div class="spy-section" style="border-color: rgba(239, 68, 68, 0.3); background: rgba(20, 8, 12, 0.6);">';
        $html .= '<div class="spy-section-head" style="background: rgba(35, 10, 15, 0.8); border-bottom-color: rgba(239, 68, 68, 0.3);">';
        $html .= '<span class="spy-section-title" style="color: #fca5a5;">';
        $html .= self::getSvg('warning', 14, 14) . ' ' . ($LNG['sys_notenough_money_title'] ?? 'Recursos Insuficientes');
        $html .= '</span>';
        $html .= '</div>';
        $html .= '<div style="padding: 10px 12px; font-size: 13px; line-height: 1.5; color: #f87171;">';
        $html .= sprintf($LNG['sys_queue_cancel_desc'] ?? 'La orden de producción para <strong>%s</strong> en %s no pudo ejecutarse debido a la escasez de recursos en los almacenes.', htmlspecialchars($techName, ENT_QUOTES, 'UTF-8'), $coordsLink);
        $html .= '</div>';
        $html .= '</div>';

        // Action Buttons
        $html .= '<div class="spy-actions-bar">';
        $html .= '<a href="game.php?page=' . $targetPage . '" class="spy-btn spy-btn-sim">';
        $html .= self::getSvg('document', 15, 15) . ' ' . $typeName;
        $html .= '</a>';
        $html .= '<a href="game.php?page=trader" class="spy-btn spy-btn-report">';
        $html .= self::getSvg('box', 15, 15) . ' ' . ($LNG['lm_trader'] ?? 'Mercader');
        $html .= '</a>';
        $html .= '</div>';

        $html .= '</div>'; // spy-report-card

        return $html;
    }

    /**
     * Build Tactical ACS Invitation Card
     */
    public static function buildAcsInviteCard($senderUsername, $acsName, $targetCoords, $LNG)
    {
        $dpath = self::getThemePath();
        $sender = htmlspecialchars($senderUsername, ENT_QUOTES, 'UTF-8');
        $acs = htmlspecialchars($acsName, ENT_QUOTES, 'UTF-8');
        $galaxy = (int) ($targetCoords['galaxy'] ?? 0);
        $system = (int) ($targetCoords['system'] ?? 0);
        $planet = (int) ($targetCoords['planet'] ?? 0);

        $coordsLink = sprintf(
            '<a href="game.php?page=galaxy&amp;galaxy=%d&amp;system=%d" class="spy-planet-link"><span class="spy-coords">[%d:%d:%d]</span></a>',
            $galaxy, $system, $galaxy, $system, $planet
        );

        $html = '<div class="spy-report-card combat-report-card msg-card">';

        // Header
        $html .= '<div class="spy-header">';
        $html .= '<div class="spy-planet-avatar-wrapper" style="border-color: #38bdf8;">';
        $html .= '<img src="' . $dpath . 'planeten/small/s_mond.jpg" alt="SAC" class="spy-planet-avatar">';
        $html .= '</div>';
        $html .= '<div class="spy-header-info">';
        $html .= '<div class="spy-title-row" style="color: #38bdf8;">' . ($LNG['fl_acs_invitation_title'] ?? 'Invitación de Flota Confederada') . '</div>';
        $html .= '<div class="spy-meta-row">';
        $html .= '<span class="spy-meta-player">';
        $html .= self::getSvg('sword', 13, 13) . ' ' . ($LNG['fl_player'] ?? 'Comandante: ') . '<strong>' . $sender . '</strong>';
        $html .= '</span>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="spy-counter-wrapper">';
        $html .= '<div class="spy-counter-pill pill-success">' . self::getSvg('shield', 14, 14) . ' SAC ACTIVO</div>';
        $html .= '</div>';
        $html .= '</div>';

        // Content
        $html .= '<div class="spy-section">';
        $html .= '<div class="spy-section-head">';
        $html .= '<span class="spy-section-title">';
        $html .= self::getSvg('crosshair', 14, 14) . ' ' . ($LNG['fl_acs_title'] ?? 'Flota SAC') . ': ' . $acs;
        $html .= '</span>';
        $html .= '<span class="spy-badge-count" style="border-color:#0099cc; color:#38bdf8;">Objetivo ' . $coordsLink . '</span>';
        $html .= '</div>';
        $html .= '<div style="padding: 10px 12px; font-size: 13px; line-height: 1.5; color: #e2e8f0;">';
        $html .= sprintf($LNG['fl_acs_invitation_desc'] ?? 'Has recibido una invitación para unir tus naves a la flota confederada <strong>%s</strong> organizada por <strong>%s</strong> hacia las coordenadas %s.', $acs, $sender, $coordsLink);
        $html .= '</div>';
        $html .= '</div>';

        // Action Buttons
        $html .= '<div class="spy-actions-bar">';
        $html .= '<a href="game.php?page=fleetTable" class="spy-btn spy-btn-attack">';
        $html .= self::getSvg('sword', 15, 15) . ' ' . ($LNG['fl_continue'] ?? 'Unirse a la Misión');
        $html .= '</a>';
        $html .= '<a href="game.php?page=galaxy&amp;galaxy=' . $galaxy . '&amp;system=' . $system . '" class="spy-btn spy-btn-galaxy">';
        $html .= self::getSvg('target', 15, 15) . ' ' . ($LNG['gl_galaxy'] ?? 'Ver Galaxia');
        $html .= '</a>';
        $html .= '</div>';

        $html .= '</div>'; // spy-report-card

        return $html;
    }
}

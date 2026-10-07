{block name="title" prepend}{$LNG.lm_overview}{/block}
{block name="script" append}{/block}
{block name="content"}
<link rel="stylesheet" type="text/css" href="{$dpath}css/overview.css">
<div id="page">
    <div id="content">
        {if !empty($war_event_banner)}
        <div class="war-event-banner war-event-{$war_event_banner.state}">
            <style>
            {literal}
            .war-event-banner {
                margin: 0 0 15px 0;
                padding: 16px 20px;
                border-radius: 8px;
                position: relative;
                overflow: hidden;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.6);
                font-family: inherit;
            }
            .war-event-scheduled {
                background: linear-gradient(135deg, rgba(38, 24, 6, 0.95) 0%, rgba(20, 14, 4, 0.98) 100%);
                border: 1px solid #f39c12;
                box-shadow: 0 0 15px rgba(243, 156, 18, 0.4), inset 0 0 10px rgba(243, 156, 18, 0.1);
            }
            .war-event-active {
                background: linear-gradient(135deg, rgba(45, 10, 10, 0.95) 0%, rgba(22, 5, 5, 0.98) 100%);
                border: 1px solid #e74c3c;
                box-shadow: 0 0 20px rgba(231, 76, 60, 0.45), inset 0 0 12px rgba(231, 76, 60, 0.15);
            }
            .war-event-badge-scheduled {
                background: #e67e22;
                color: #ffffff;
                font-size: 11px;
                font-weight: bold;
                letter-spacing: 1.5px;
                padding: 4px 10px;
                border-radius: 3px;
                text-transform: uppercase;
                display: inline-block;
                margin-bottom: 8px;
            }
            .war-event-badge-active {
                background: #c0392b;
                color: #ffffff;
                font-size: 11px;
                font-weight: bold;
                letter-spacing: 1.5px;
                padding: 4px 10px;
                border-radius: 3px;
                text-transform: uppercase;
                display: inline-block;
                margin-bottom: 8px;
                box-shadow: 0 0 8px rgba(231, 76, 60, 0.8);
            }
            .war-event-title-scheduled {
                color: #f39c12;
                font-size: 18px;
                font-weight: bold;
                margin: 0 0 6px 0;
                text-shadow: 0 0 8px rgba(243, 156, 18, 0.5);
                letter-spacing: 0.5px;
            }
            .war-event-title-active {
                color: #ff5252;
                font-size: 18px;
                font-weight: bold;
                margin: 0 0 6px 0;
                text-shadow: 0 0 10px rgba(255, 82, 82, 0.6);
                letter-spacing: 0.5px;
            }
            .war-event-timer-box {
                background: rgba(0, 0, 0, 0.65);
                border: 1px solid rgba(255, 255, 255, 0.15);
                border-radius: 6px;
                padding: 8px 16px;
                display: inline-flex;
                align-items: center;
                gap: 12px;
                margin: 10px 0;
            }
            .war-event-scheduled .war-event-timer-box {
                border-color: rgba(243, 156, 18, 0.5);
            }
            .war-event-active .war-event-timer-box {
                border-color: rgba(231, 76, 60, 0.6);
            }
            .war-event-timer-val {
                font-family: 'Consolas', 'Courier New', monospace;
                font-size: 22px;
                font-weight: bold;
                letter-spacing: 2px;
            }
            .war-event-scheduled .war-event-timer-val {
                color: #f1c40f;
                text-shadow: 0 0 10px rgba(241, 196, 15, 0.7);
            }
            .war-event-active .war-event-timer-val {
                color: #ff5252;
                text-shadow: 0 0 12px rgba(255, 82, 82, 0.8);
            }
            .war-event-btn-attack {
                background: linear-gradient(180deg, #e74c3c 0%, #c0392b 100%);
                color: #ffffff !important;
                font-weight: bold;
                padding: 8px 18px;
                border-radius: 4px;
                text-decoration: none;
                display: inline-block;
                border: 1px solid #ff7675;
                box-shadow: 0 0 10px rgba(231, 76, 60, 0.5);
                transition: all 0.2s ease;
                margin-right: 8px;
            }
            .war-event-btn-attack:hover {
                background: linear-gradient(180deg, #ff6b6b 0%, #d63031 100%);
                box-shadow: 0 0 16px rgba(255, 107, 107, 0.8);
                color: #ffffff !important;
            }
            .war-event-btn-galaxy {
                background: rgba(255, 255, 255, 0.1);
                color: #ecf0f1 !important;
                font-weight: bold;
                padding: 8px 16px;
                border-radius: 4px;
                text-decoration: none;
                display: inline-block;
                border: 1px solid rgba(255, 255, 255, 0.25);
                transition: all 0.2s ease;
            }
            .war-event-btn-galaxy:hover {
                background: rgba(255, 255, 255, 0.2);
                color: #ffffff !important;
            }
            {/literal}
            </style>

            {if $war_event_banner.state == 'scheduled'}
            <div>
                <span class="war-event-badge-scheduled">⚡ PRÓXIMO EVENTO GALÁCTICO</span>
                <div class="war-event-title-scheduled">UN NUEVO EVENTO ESTÁ POR COMENZAR</div>
                <div style="color: #dcdde1; font-size: 13px; line-height: 1.5; margin-bottom: 6px;">
                    Un evento especial se aproxima al universo. Preparen sus flotas e imperios y manténganse alerta.
                </div>
                
                <div class="war-event-timer-box">
                    <span style="color: #bdc3c7; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">⏳ Inicio en:</span>
                    <span id="war_event_timer" class="war-event-timer-val">--:--:--</span>
                </div>

                <div style="font-size: 12px; color: #a4b0be; margin-top: 4px;">
                    🔒 Los detalles, objetivos y recompensas se revelarán cuando el contador llegue a cero.
                </div>
            </div>
            {elseif $war_event_banner.state == 'active'}
            <div>
                <span class="war-event-badge-active">🚨 ¡EVENTO DE GUERRA EN CURSO!</span>
                <div class="war-event-title-active">LA FORTALEZA ANCESTRAL HA DESPERTADO</div>
                
                <div style="color: #f5f6fa; font-size: 13px; margin-bottom: 8px;">
                    📍 <strong>Ubicación detectada:</strong> <span style="color: #f1c40f; font-weight: bold; font-size: 14px;">{$war_event_banner.coords}</span> (Planeta 'Fortaleza Ancestral')
                    <span style="color: #7f8fa6; margin: 0 8px;">|</span>
                    🛡️ <strong>Defensores restantes:</strong> <span style="color: #00e676; font-weight: bold; font-size: 14px;">{$war_event_banner.surviving_units|number_format}</span> unidades
                </div>

                <div class="war-event-timer-box">
                    <span style="color: #bdc3c7; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">⌛ Tiempo Límite de la Fortaleza:</span>
                    <span id="war_event_timer" class="war-event-timer-val">--:--:--</span>
                </div>

                <div style="margin-top: 8px;">
                    <a href="game.php?page=fleetTable&galaxy={$war_event_banner.galaxy}&system={$war_event_banner.system}&planet={$war_event_banner.planet}&planettype=1&target_mission=1" class="war-event-btn-attack">🚀 Lanzar Flota de Asalto</a>
                    <a href="game.php?page=galaxy&galaxy={$war_event_banner.galaxy}&system={$war_event_banner.system}" class="war-event-btn-galaxy">🔭 Ver en Galaxia</a>
                </div>

                <div style="font-size: 11px; color: #bdc3c7; margin-top: 8px;">
                    🏆 <strong>Recompensa Estocada Final:</strong> <span style="color: #f1c40f;">{$war_event_banner.containers} Contenedores</span> + <span style="color: #00e676;">{$war_event_banner.antimatter} Antimateria</span> para cada participante de la batalla que elimine la última unidad.
                </div>
            </div>
            {/if}

            <script type="text/javascript">
            {literal}
            (function() {
                var seconds = parseInt({/literal}{$war_event_banner.countdown_seconds}{literal}, 10);
                var timerEl = document.getElementById('war_event_timer');
                var isScheduled = {/literal}{if $war_event_banner.state == 'scheduled'}true{else}false{/if}{literal};

                function renderTimer(sec) {
                    if (sec <= 0) return '00:00:00';
                    var h = Math.floor(sec / 3600);
                    var m = Math.floor((sec % 3600) / 60);
                    var s = sec % 60;
                    return (h < 10 ? '0' + h : h) + ':' + (m < 10 ? '0' + m : m) + ':' + (s < 10 ? '0' + s : s);
                }

                if (timerEl && !isNaN(seconds)) {
                    timerEl.innerText = renderTimer(seconds);
                    var timerInterval = setInterval(function() {
                        seconds--;
                        if (seconds <= 0) {
                            clearInterval(timerInterval);
                            timerEl.innerText = isScheduled ? '¡INICIANDO...!' : '¡FINALIZADO!';
                            setTimeout(function() {
                                window.location.reload();
                            }, 1500);
                            return;
                        }
                        timerEl.innerText = renderTimer(seconds);
                    }, 1000);
                }
            })();
            {/literal}
            </script>
        </div>
        {/if}
        {if !empty($fleets)}
        <div id="ally_content" class="conteiner conteinership">
            <div class="fleettab10"></div>   
            <div class="gray_flettab">
                <div class="transparent">
                    {$LNG.fl_fleets} {$activeFleetSlots} / {$maxFleetSlots}
                    <div class="transparent" style="text-align:right; float:right;color: #b1b1b1;font-size: 13px;">
                        <span style="color: #8b99b0;font-weight: bold;">{$activeExpedition} / {$maxExpedition} {$LNG.fl_expeditions}</span>
                    </div> 
                </div>
            </div> 
            <div class="fleet_log">
                {foreach $fleets as $index => $fleet}
                <div class="fleet_time">
                    <div id="fleettime_{$index}" class="fleets" title="Tiempo restante" data-fleet-end-time="{$fleet.returntime}" data-fleet-time="{$fleet.resttime}">{pretty_fly_time({$fleet.resttime})}</div>
                    <div class="tooltip fleet_static_time" data-tooltip-content="Hora del reloj del servidor: {$fleet.resttime1}" style="font-size: 10px; color: #5f6d81; white-space: nowrap;">🕒 {$fleet.resttime1}</div>
                </div>
                <div class="fleet_text">
                    {$fleet.text}
                    <div class="clear"></div>
                </div>     
                <div class="separator"></div>
                {/foreach}       
            </div>
        </div>
        {/if}
        <div id="ally_content" class="conteiner">
            <div class="gray_flettab">
                <div id="online_user">
                {$LNG.ov_online_users} <span>{$UsersOnline}</span>
                </div>
                <div id="gm_linck">
                    <a title="" href="game.php?page=ticket" class="tooltip" data-tooltip-content="{$LNG.ov_ticket_tooltip}">{$LNG.ov_ticket}</a>  
                </div>
            </div> 
            <div class="row" style="padding: 7px">
                <div class="col-8">
                    <div class="card mr-1 background-border-black-blue shadow"> 
                        <div id="big_panet" style="background: url({$dpath}img/title/control_room.png) no-repeat, url({$dpath}planeten/{$planetimage}.jpg) top center no-repeat; background-size:cover;">
                            <div class="palnet_pianeta_titoloa palnet_pianeta_titolo">    
                                <a href="game.php?page=planet">
                                    <span class="planetname"><a href="game.php?page=planet" title="{$LNG.lm_planet}"><span class="planetname">{$planetname}</span></a>
                                        <img src="{$dpath}img/iconav/pencil-over.png" class="palnet_imgopa">
                                    </span>  
                                </a>
                            </div>
                            <div class="palnet_block_info palnet_luna_planeto">
                                <img src="{$dpath}planeten/planet2d/{$planetimage}.png" height="100" width="100">
                            </div>
                            <marquee behavior="alternate" direction="left" scrollamount="1" onmouseover="this.stop();" onmouseout="this.start();" style="height: 15px;width: 445px;position: absolute;bottom: 1px;font-size: 10px;left: 6px;color: #b2b2b2;text-shadow: 0px 1px 0px rgba(0,0,0,0.6);">Hello my friends!</marquee>
                            <div class="palnet_block_info palnet_big_info"> 
                                <div class="left_part">
                                    <a href="game.php?page=planet" style="color:#b2b2b2"><img src="{$dpath}img/iconav/diametr.png" class="overvieew6">{$LNG.ov_diameter}</a>
                                </div>
                                <div class="right_part">
                                    {$planet_diameter} {$LNG.ov_distance_unit} (<span title="{$LNG.ov_developed_fields}">{$planet_field_current}</span> / <span title="{$LNG.ov_max_developed_fields}">{$planet_field_max}</span> {$LNG.ov_fields})
                                </div>
                                <div class="left_part" style="top: 20px;">
                                    <a href="game.php?page=planet" style="color:#b2b2b2"><img src="{$dpath}img/iconav/temp.png" class="overvieew6">{$LNG.ov_temperature}</a>
                                </div>
                                <div class="right_part" style="top: 20px;">
                                    {$LNG.ov_aprox} {$planet_temp_min}{$LNG.ov_temp_unit} {$LNG.ov_to} {$planet_temp_max}{$LNG.ov_temp_unit}
                                </div>
                                <div class="left_part" style="top: 40px;">
                                    <a href="game.php?page=planet" style="color:#b2b2b2"><img src="{$dpath}img/iconav/position.png" class="overvieew6">{$LNG.ov_position}</a>
                                </div>
                                <div class="right_part" style="top: 40px;">
                                    <a href="game.php?page=galaxy&amp;galaxy={$galaxy}&amp;system={$system}">[{$galaxy}:{$system}:{$planet}]</a>
                                </div>
                                <div class="clear"></div>
                                <div class="left_part" style="top: 60px;">
                                    <a href="game.php?page=overview" style="color:#b2b2b2"><img src="{$dpath}img/iconav/stat.png" class="overvieew6">{$LNG.ov_points}</a>
                                </div>
                                <div class="right_part" style="top: 60px;">{$rankInfo}</div>
                            </div>	
                        </div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="card background-border-black-gray shadow">       
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card background-border-black-blue">                     
                                    <div class="gray_latdes overvieew2" style="background: url({$dpath}img/content/control_roompi.png) no-repeat, url({$dpath}img/content/mondpi.jpg) no-repeat ; background-size:cover;">
                                    {if isModuleAvailable($smarty.const.MODULE_CREATE_MOON)}
                                    {if $planet_type == 1}	
                                    {if $Moon}
                                        <div>
                                            <a href="game.php?page=overview&amp;cp={$Moon.id}&amp;re=0"><span class="overvieew5">{$Moon.name}</span></a>
                                            <a href="game.php?page=overview&amp;cp={$Moon.id}&amp;re=0" class=" ">
                                                <img src="{$dpath}img/content/moon.png" class="overvieew4 tooltip" data-tooltip-content="{$Moon.name}" style="opacity:0.8">
                                            </a>
                                        </div>
                                    {else}
                                        <div>
                                            <a href="game.php?page=createMoon"><span class="overvieew5">{$LNG.ov_create_moon}</span></a>
                                            <a href="game.php?page=createMoon" class=" ">
                                                <img src="{$dpath}img/content/moon.png" class="overvieew4 tooltip" data-tooltip-content="{$LNG.ov_create_moon}" style="opacity:0.8">
                                            </a>
                                        </div>
                                    {/if}
                                    {/if}
                                    {/if}
                                    </div>
                                </div>
                            </div>    
                            <div class="col-md-6">
                                <div class="card background-border-black-blue">  
                                    <div class="gray_latdes overvieew2" style="background: url({$dpath}img/content/control_roompi.png) no-repeat, url({$dpath}img/content/control_acc.png) no-repeat ; background-size:cover;">
                                        <div>
                                            <a href="game.php?page=market"><span class="overvieew5">{$LNG.lm_market}</span></a>
                                            <a href="game.php?page=market" class=" ">
                                                <img src="{$dpath}img/content/market.png" class="overvieew4 tooltip" data-tooltip-content="{$LNG.lm_market}" style="opacity:0.8">
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="card background-border-black-gray">
                                    <div class="ricerche">
                                        <img src="{$dpath}img/iconav/ov_tech.png" class="overvieew27">
                                        <span class="overvieew9">
                                            <a href="game.php?page=research">
                                                {if $buildInfo.tech}
                                                    <span class="timer" data-time="{$buildInfo.tech['timeleft']}">??:??:??</span>
                                                    - {$LNG.tech[$buildInfo.tech['id']]}
                                                    <span class="level">({$buildInfo.tech['level']})</span>
                                                {else}
                                                    {$LNG.ov_free}
                                                {/if}
                                            </a>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="card background-border-black-gray">
                                    <div class="costruzioni">
                                        <img src="{$dpath}img/iconav/ov_build.png" class="overvieew27">
                                        <span class="overvieew9">
                                            <a href="game.php?page=buildings">
                                                {if $buildInfo.buildings}
                                                    <span class="timer" data-time="{$buildInfo.buildings['timeleft']}">??:??:??</span>
                                                    - {$LNG.tech[$buildInfo.buildings['id']]}
                                                    <span class="level">({$buildInfo.buildings['level']})</span>
                                                {else}
                                                    {$LNG.ov_free}
                                                {/if}
                                            </a>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="card background-border-black-gray">
                                    <div class="flotte">
                                        <img src="{$dpath}img/iconav/ov_fleet.png" class="overvieew27">
                                        <span class="overvieew9">
                                            <a href="game.php?page=shipyard">
                                                {if $buildInfo.fleet}
                                                    <span class="timer" data-time="{$buildInfo.fleet['timeleft']}">??:??:??</span>
                                                    - {$LNG.tech[$buildInfo.fleet['id']]}
                                                    <span class="level">({$buildInfo.fleet['level']})</span>
                                                {else}
                                                    {$LNG.ov_free}
                                                {/if}
                                            </a>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card mt-1 background-border-black-blue shadow"> 
                        <div class="card-body">
                            <p class="card-title">{$LNG.ov_panel_root}</p>
                            <div class="row">      
                                <div class="col-1">
                                    <div class="card mr-1 background-border-black-blue"> 
                                        {if isModuleAvailable($smarty.const.MODULE_RACE)}
                                        <a href="game.php?page=race"><div class="overvieew15 overvire tooltip" style="background: rgba(0, 0, 0, 0.25) url({$dpath}gebaeude/{$race}.gif) no-repeat center; background-size: 50px;" data-tooltip-content="{$LNG.tech.$race}"></div></a>
                                        {/if}
                                    </div>
                                </div>
                                <div class="col-1">
                                    <div class="card mr-1 background-border-black-blue"> 
                                        {if isModuleAvailable($smarty.const.MODULE_FORMGOVERNMENT)}
                                        <a href="game.php?page=formgovernment"><div class="overvieew15 overvire tooltip" style="background: rgba(0, 0, 0, 0.25) url({$dpath}gebaeude/{$formgovernment}.png) no-repeat center; background-size: 50px;" data-tooltip-content="{$LNG.tech.$formgovernment}"></div></a>
                                        {/if}
                                    </div>
                                </div>
                                <div class="col-1">
                                    <div class="card mr-1 background-border-black-blue"> 
                                        {if isModuleAvailable($smarty.const.MODULE_ETHICS)}
                                        <a href="game.php?page=ethics"><div class="overvieew15 overvire tooltip" style="background: rgba(0, 0, 0, 0.25) url({$dpath}gebaeude/{$ethics}.png) no-repeat center; background-size: 50px;" data-tooltip-content="{$LNG.tech.$ethics}"></div></a>
                                        {/if}
                                    </div>
                                </div>
                                <div class="col-1">
                                    <div class="card mr-1 background-border-black-blue"> 
                                        {if isModuleAvailable($smarty.const.MODULE_INFO_BONUS)}
                                        <a href="game.php?page=infobonus"><div class="overvieew15 overvire tooltip" style="background: rgba(0, 0, 0, 0.25) url({$dpath}img/title/infobonus.png) no-repeat center; background-size: 50px;" data-tooltip-content="{$LNG.lm_infobonus}"></div></a>
                                        {/if} 
                                    </div>
                                </div>
                                <div class="col-1">
                                    <div class="card mr-1 background-border-black-blue"> 
                                        {if isModuleAvailable($smarty.const.MODULE_PARTY)}
                                        <a href="game.php?page=party"><div class="overvieew15 overvire tooltip" style="background: rgba(0, 0, 0, 0.25) url({$dpath}img/title/party.png) no-repeat center; background-size: 50px;" data-tooltip-content="{$LNG.lm_party}"></div></a>
                                        {/if} 
                                    </div>
                                </div>
                                <div class="col-1">
                                    <div class="card mr-1 background-border-black-blue"> 
                                        {if isModuleAvailable($smarty.const.MODULE_IDEOLOGIES)}
                                        <a href="game.php?page=ideologies"><div class="overvieew15 overvire tooltip" style="background: rgba(0, 0, 0, 0.25) url({$dpath}img/title/ideologies.png) no-repeat center; background-size: 50px;" data-tooltip-content="{$LNG.lm_ideologies}"></div></a>
                                        {/if}  
                                    </div>
                                </div>
                                <div class="col-1">
                                    <div class="card mr-1 background-border-black-blue"> 
                                        {if isModuleAvailable($smarty.const.MODULE_OFFICIER)}
                                        <a href="game.php?page=officier"><div class="overvieew15 overvire tooltip" style="background: rgba(0, 0, 0, 0.25) url({$dpath}img/title/officier.png) no-repeat center; background-size: 50px;" data-tooltip-content="{$LNG.lm_officiers}"></div></a>
                                        {/if}  
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                {if $is_news}
                <div class="col-12">
                    <div class="card mt-1 background-border-black-blue shadow"> 
                        <div class="card-body">
                            <p class="card-title">{$LNG.ov_news}</p>
                            <p class="card-text overflow-auto" style="max-height: 50px;">{$news}</p>
                        </div>
                    </div>
                </div>
                {/if}
            </div>
        </div>
    </div>
</div>
{/block}
{block name="script" append}
    <script src="scripts/game/overview.js?v=20261001_timecalc_v5"></script>
    <script src="scripts/game/buildlist.js?v=20261001_timecalc_v5"></script>
{/block}
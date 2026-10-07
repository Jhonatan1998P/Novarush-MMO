{block name="title" prepend}{$LNG.lm_container}{/block}
{block name="content"}
<link rel="stylesheet" type="text/css" href="{$dpath}css/conteiner.css?v=2.2">

<div id="page" class="conteiner_page_root">
    <div id="content">
        <div id="ally_content" class="conteiner_custom_bay">
            <div class="cont_full_wrapper">

                <!-- ==============================================================
                     ZONA SUPERIOR: Se mantiene a la derecha del menú lateral
                     Dimensiones compactas para bordear la navegación sin tapar nada
                     ============================================================== -->
                <div class="cont_upper_section">
                    <!-- Top Hero Banner Compacto -->
                    <div class="cont_hero_card">
                        <div class="cont_hero_banner">
                            <img src="{$dpath}img/title/conteiner.jpg" alt="{$LNG.lm_container}" />
                            <div class="cont_hero_overlay">
                                <div class="cont_hero_info">
                                    <div class="cont_title_group">
                                        <h2>
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--cont-cyan);"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
                                            {$LNG.lm_container} Estratégicos
                                        </h2>
                                        <div class="cont_dest_badge">
                                            <span>Destino:</span>
                                            <b>{$targetPlanet} {$targetCoords}</b>
                                        </div>
                                    </div>
                                    <div class="cont_stats_strip">
                                        <div class="cont_stat_pill highlight">
                                            <span class="cont_stat_label">Disponibles</span>
                                            <span class="cont_stat_val" id="userContBalance">{$conteiner|number}</span>
                                        </div>
                                        <div class="cont_stat_pill">
                                            <span class="cont_stat_label">Abiertos 24h</span>
                                            <span class="cont_stat_val" style="color:#2ecc71;">{$sum|number}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tarjeta de Rendimiento Estimado Compacta -->
                    <div class="cont_preview_card">
                        <div class="cont_section_header">
                            <h3>
                                <span>Rendimiento Estimado</span>
                                <span id="previewMultiplierBadge" style="color:#ffffff; font-weight:normal; font-size:11px; margin-left:5px;">(x1 Contenedor)</span>
                            </h3>
                            <span class="cont_section_subtitle">Indexado a tu producción ({$rewardPreview.hours * 60}m) + Escolta</span>
                        </div>

                        <div class="cont_grid_rewards">
                            <!-- Recursos -->
                            <div class="cont_group_box">
                                <div class="cont_group_label">
                                    <span>Recursos Minerales</span>
                                    <span style="color:var(--cont-cyan);">50% Producción</span>
                                </div>
                                <div class="cont_reward_row">
                                    <div class="cont_reward_left">
                                        <div class="cont_reward_icon"><img src="{$dpath}gebaeude/901.gif" alt="Metal"></div>
                                        <span class="cont_reward_name">{$LNG.tech.901}</span>
                                    </div>
                                    <span class="cont_reward_val val_metal" id="previewMetal">+ {$rewardPreview.metal|number}</span>
                                </div>
                                <div class="cont_reward_row">
                                    <div class="cont_reward_left">
                                        <div class="cont_reward_icon"><img src="{$dpath}gebaeude/902.gif" alt="Cristal"></div>
                                        <span class="cont_reward_name">{$LNG.tech.902}</span>
                                    </div>
                                    <span class="cont_reward_val val_crystal" id="previewCrystal">+ {$rewardPreview.crystal|number}</span>
                                </div>
                                <div class="cont_reward_row">
                                    <div class="cont_reward_left">
                                        <div class="cont_reward_icon"><img src="{$dpath}gebaeude/903.gif" alt="Deuterio"></div>
                                        <span class="cont_reward_name">{$LNG.tech.903}</span>
                                    </div>
                                    <span class="cont_reward_val val_deut" id="previewDeut">+ {$rewardPreview.deuterium|number}</span>
                                </div>
                            </div>

                            <!-- Escolta Militar -->
                            <div class="cont_group_box">
                                <div class="cont_group_label">
                                    <span>Escolta Militar</span>
                                    <span style="color:var(--cont-purple);">50 Unidades</span>
                                </div>
                                <div class="cont_reward_row">
                                    <div class="cont_reward_left">
                                        <div class="cont_reward_icon"><img src="{$dpath}gebaeude/202.gif" alt="Caza Ligero"></div>
                                        <span class="cont_reward_name">{$LNG.tech.202}</span>
                                    </div>
                                    <span class="cont_reward_val val_fleet" id="previewFighterL">+ {$rewardPreview.lightFighters|number}</span>
                                </div>
                                <div class="cont_reward_row">
                                    <div class="cont_reward_left">
                                        <div class="cont_reward_icon"><img src="{$dpath}gebaeude/203.gif" alt="Caza Pesado"></div>
                                        <span class="cont_reward_name">{$LNG.tech.203}</span>
                                    </div>
                                    <span class="cont_reward_val val_fleet" id="previewFighterH">+ {$rewardPreview.heavyFighters|number}</span>
                                </div>
                                <div class="cont_reward_row">
                                    <div class="cont_reward_left">
                                        <div class="cont_reward_icon"><img src="{$dpath}gebaeude/204.gif" alt="Crucero"></div>
                                        <span class="cont_reward_name">{$LNG.tech.204}</span>
                                    </div>
                                    <span class="cont_reward_val val_fleet" id="previewCruiser">+ {$rewardPreview.cruisers|number}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Terminal de Apertura / Formulario Compacto -->
                    <div class="cont_control_card">
                        <form action="game.php?page=conteiner&amp;mode=open" method="post" id="containerOpenForm" class="cont_form_layout">
                            <div class="cont_inputs_row">
                                <!-- Stepper Seguro -->
                                <div class="cont_stepper_group">
                                    <button type="button" class="cont_step_btn" id="btnMinus" aria-label="Disminuir">−</button>
                                    <input type="number" 
                                           name="conts" 
                                           id="contsInput" 
                                           class="cont_num_input" 
                                           min="1" 
                                           max="{if $conteiner > 500}500{else}{$conteiner}{/if}" 
                                           value="{if $conteiner > 0}1{else}0{/if}" 
                                           {if $conteiner == 0}disabled{/if}
                                           required />
                                    <button type="button" class="cont_step_btn" id="btnPlus" aria-label="Aumentar">+</button>
                                </div>

                                <!-- Presets Rápidos -->
                                <div class="cont_presets_group">
                                    <button type="button" class="cont_preset_btn active" data-val="1">1</button>
                                    <button type="button" class="cont_preset_btn" data-val="5" {if $conteiner < 5}disabled style="opacity:0.4;cursor:not-allowed;"{/if}>5</button>
                                    <button type="button" class="cont_preset_btn" data-val="10" {if $conteiner < 10}disabled style="opacity:0.4;cursor:not-allowed;"{/if}>10</button>
                                    <button type="button" class="cont_preset_btn" data-val="25" {if $conteiner < 25}disabled style="opacity:0.4;cursor:not-allowed;"{/if}>25</button>
                                    <button type="button" class="cont_preset_btn" data-val="{if $conteiner > 500}500{else}{$conteiner}{/if}" {if $conteiner == 0}disabled style="opacity:0.4;cursor:not-allowed;"{/if}>
                                        Máx ({if $conteiner > 500}500{else}{$conteiner}{/if})
                                    </button>
                                </div>
                            </div>

                            <!-- Botón de Envío -->
                            <div class="cont_action_row">
                                <button type="submit" class="cont_submit_btn" id="submitOpenBtn" {if $conteiner == 0}disabled{/if}>
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                                    <span id="submitBtnText">{if $conteiner > 0}Abrir 1 Contenedor{else}Sin Contenedores Disponibles{/if}</span>
                                </button>
                            </div>
                            
                            <div class="cont_notice_safety">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--cont-cyan);"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                                <span>Los suministros se depositarán instantáneamente en tu planeta activo (<b>{$targetPlanet}</b>).</span>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- ==============================================================
                     ZONA INFERIOR: Se ensancha al ancho completo por debajo del Menú
                     Aprovecha todo el ancho disponible una vez superado el menú lateral
                     ============================================================== -->
                <div class="cont_lower_section">
                    <div class="cont_history_card">
                        <div class="cont_section_header">
                            <h3>Historial Reciente de Suministros</h3>
                            <span class="cont_section_subtitle">Últimos registros de apertura</span>
                        </div>

                        <div class="cont_history_list">
                            {if !empty($groupedLogs)}
                                {foreach $groupedLogs as $batch}
                                <div class="cont_batch_item">
                                    <div class="cont_batch_header">
                                        <div class="cont_batch_time">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                            <span>{$batch.time}</span>
                                        </div>
                                        <span class="cont_batch_badge">Lote de Suministro</span>
                                    </div>

                                    <div class="cont_chips_wrap">
                                        {foreach $batch.resources as $res}
                                        <div class="cont_chip">
                                            <img src="{$dpath}gebaeude/{$res.item}.gif" alt="{$res.name}">
                                            <span class="cont_chip_name">{$res.name}:</span>
                                            <span class="cont_chip_qty {if $res.item == 901}val_metal{elseif $res.item == 902}val_crystal{else}val_deut{/if}">
                                                +{$res.count|number}
                                            </span>
                                        </div>
                                        {/foreach}

                                        {foreach $batch.fleet as $flt}
                                        <div class="cont_chip">
                                            <img src="{$dpath}gebaeude/{$flt.item}.gif" alt="{$flt.name}">
                                            <span class="cont_chip_name">{$flt.name}:</span>
                                            <span class="cont_chip_qty val_fleet">
                                                +{$flt.count|number} {if $flt.factor > 1}<b style="color:#ff4d4f;">(x{$flt.factor})</b>{/if}
                                            </span>
                                        </div>
                                        {/foreach}
                                    </div>
                                </div>
                                {/foreach}
                            {else}
                                <div class="cont_empty_state">
                                    No se registran aperturas recientes en las últimas 24 horas.
                                </div>
                            {/if}
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
(function() {
    var maxVal = {$conteiner};
    if (maxVal > 500) maxVal = 500;
    
    var baseMetal = {$rewardPreview.metal};
    var baseCrystal = {$rewardPreview.crystal};
    var baseDeut = {$rewardPreview.deuterium};
    var baseFL = {$rewardPreview.lightFighters};
    var baseFH = {$rewardPreview.heavyFighters};
    var baseCR = {$rewardPreview.cruisers};
    
    var input = document.getElementById('contsInput');
    var submitBtn = document.getElementById('submitOpenBtn');
    var submitText = document.getElementById('submitBtnText');
    var multiplierBadge = document.getElementById('previewMultiplierBadge');
    
    var elMetal = document.getElementById('previewMetal');
    var elCrystal = document.getElementById('previewCrystal');
    var elDeut = document.getElementById('previewDeut');
    var elFL = document.getElementById('previewFighterL');
    var elFH = document.getElementById('previewFighterH');
    var elCR = document.getElementById('previewCruiser');
    var form = document.getElementById('containerOpenForm');
    
    function formatNum(n) {
        return Math.floor(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }
    
    function updateUI(qty) {
        if (maxVal <= 0) {
            qty = 0;
            if (input) input.value = 0;
            if (submitBtn) submitBtn.disabled = true;
            if (submitText) submitText.textContent = "Sin Contenedores Disponibles";
            return;
        }
        
        qty = parseInt(qty, 10);
        if (isNaN(qty) || qty < 1) qty = 1;
        if (qty > maxVal) qty = maxVal;
        
        input.value = qty;
        
        if (multiplierBadge) multiplierBadge.textContent = "(x" + qty + (qty === 1 ? " Contenedor)" : " Contenedores)");
        if (submitText) submitText.textContent = "Abrir " + qty + (qty === 1 ? " Contenedor" : " Contenedores");
        
        if (elMetal) elMetal.textContent = "+ " + formatNum(baseMetal * qty);
        if (elCrystal) elCrystal.textContent = "+ " + formatNum(baseCrystal * qty);
        if (elDeut) elDeut.textContent = "+ " + formatNum(baseDeut * qty);
        if (elFL) elFL.textContent = "+ " + formatNum(baseFL * qty);
        if (elFH) elFH.textContent = "+ " + formatNum(baseFH * qty);
        if (elCR) elCR.textContent = "+ " + formatNum(baseCR * qty);
        
        var presetBtns = document.querySelectorAll('.cont_preset_btn');
        for (var i = 0; i < presetBtns.length; i++) {
            var btnVal = parseInt(presetBtns[i].getAttribute('data-val'), 10);
            if (btnVal === qty) {
                presetBtns[i].classList.add('active');
            } else {
                presetBtns[i].classList.remove('active');
            }
        }
    }
    
    if (input) {
        input.addEventListener('input', function() {
            updateUI(this.value);
        });
        input.addEventListener('change', function() {
            updateUI(this.value);
        });
    }
    
    var btnMinus = document.getElementById('btnMinus');
    var btnPlus = document.getElementById('btnPlus');
    
    if (btnMinus) {
        btnMinus.addEventListener('click', function() {
            var cur = parseInt(input.value, 10) || 1;
            updateUI(cur - 1);
        });
    }
    
    if (btnPlus) {
        btnPlus.addEventListener('click', function() {
            var cur = parseInt(input.value, 10) || 1;
            updateUI(cur + 1);
        });
    }
    
    var presetBtns = document.querySelectorAll('.cont_preset_btn');
    for (var i = 0; i < presetBtns.length; i++) {
        presetBtns[i].addEventListener('click', function() {
            var val = parseInt(this.getAttribute('data-val'), 10);
            if (!isNaN(val) && val > 0) {
                updateUI(val);
            }
        });
    }
    
    // Confirmación de seguridad si se abren más de 5 contenedores
    if (form) {
        form.addEventListener('submit', function(e) {
            var cur = parseInt(input.value, 10) || 1;
            if (cur > 5) {
                var confirmMsg = "¿Confirmas que deseas abrir " + cur + " contenedores a la vez en {$targetPlanet}?";
                if (!window.confirm(confirmMsg)) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    }
})();
</script>
{/block}
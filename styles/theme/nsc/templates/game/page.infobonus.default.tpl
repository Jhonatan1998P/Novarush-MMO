{block name="title" prepend}{$LNG.lm_infobonus}{/block}
{block name="content"}
{literal}
<style type="text/css">
.ib-main-box {
    width: 714px !important;
    max-width: 100% !important;
    box-sizing: border-box !important;
    overflow: visible !important;
    padding: 0 !important;
    margin-bottom: 20px !important;
    background: #040c18 !important;
}
.ib-nav-tabs {
    display: flex !important;
    overflow-x: auto !important;
    flex-wrap: nowrap !important;
    -webkit-overflow-scrolling: touch !important;
    scrollbar-width: none !important;
    gap: 4px !important;
    padding: 8px 8px 0 8px !important;
    background: #060e1a !important;
    border-bottom: 2px solid #1a293e !important;
}
.ib-nav-tabs::-webkit-scrollbar {
    display: none !important;
}
.ib-tab-btn {
    flex: 0 0 auto !important;
    white-space: nowrap !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 6px !important;
    padding: 7px 11px !important;
    font-size: 11.5px !important;
    font-weight: 600 !important;
    color: #94a3b8 !important;
    background: rgba(255, 255, 255, 0.03) !important;
    border: 1px solid rgba(255, 255, 255, 0.08) !important;
    border-bottom: none !important;
    border-radius: 4px 4px 0 0 !important;
    cursor: pointer !important;
    text-decoration: none !important;
    transition: all 0.2s ease !important;
}
.ib-tab-btn:hover {
    background: rgba(255, 255, 255, 0.08) !important;
    color: #f1f5f9 !important;
}
.ib-tab-btn.active {
    background: #0a172a !important;
    color: #38bdf8 !important;
    border-color: #0284c7 !important;
    border-bottom: 2px solid #0a172a !important;
    margin-bottom: -2px !important;
}
.ib-tab-svg {
    vertical-align: middle !important;
    display: inline-block !important;
    flex-shrink: 0 !important;
    color: inherit !important;
}
.ib-badge-count {
    background: rgba(0, 0, 0, 0.5) !important;
    padding: 1px 6px !important;
    border-radius: 10px !important;
    font-size: 10px !important;
    color: #cbd5e1 !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
}
.ib-tab-btn.active .ib-badge-count {
    background: rgba(2, 132, 199, 0.25) !important;
    border-color: #0284c7 !important;
    color: #7dd3fc !important;
}
.ib-toolbar {
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    padding: 8px 12px !important;
    background: #08111e !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06) !important;
    font-size: 11.5px !important;
}
.ib-filter-label {
    display: inline-flex !important;
    align-items: center !important;
    gap: 6px !important;
    cursor: pointer !important;
    color: #94a3b8 !important;
    user-select: none !important;
}
.ib-filter-label:hover {
    color: #f1f5f9 !important;
}
.ib-filter-label input[type="checkbox"] {
    cursor: pointer !important;
    margin: 0 !important;
}
.ib-table {
    width: 100% !important;
    border-collapse: collapse !important;
    table-layout: auto !important;
    margin: 0 !important;
    background: transparent !important;
}
.ib-thead-row {
    background: #060c16 !important;
    border-bottom: 2px solid #1a293e !important;
    height: auto !important;
    float: none !important;
}
.ib-thead-row th {
    color: #94a3b8 !important;
    font-size: 11px !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
    padding: 9px 8px !important;
    border: none !important;
    float: none !important;
    height: auto !important;
    line-height: normal !important;
    background: transparent !important;
}
.ib-th-icon {
    width: 38px !important;
    text-align: center !important;
}
.ib-th-name {
    text-align: left !important;
    padding-left: 8px !important;
}
.ib-th-val {
    width: 105px !important;
    text-align: center !important;
}
.ib-th-sources {
    width: 140px !important;
    text-align: center !important;
}
.ib-row {
    float: none !important;
    height: auto !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
    background: rgba(8, 17, 31, 0.75) !important;
    transition: background 0.15s ease !important;
}
.ib-row:nth-child(even) {
    background: rgba(5, 12, 22, 0.85) !important;
}
.ib-row:hover {
    background: rgba(14, 30, 54, 0.95) !important;
}
.ib-row td {
    float: none !important;
    height: auto !important;
    border: none !important;
    line-height: 1.4 !important;
    background: transparent !important;
    vertical-align: middle !important;
}
.ib-td-icon {
    width: 38px !important;
    text-align: center !important;
    padding: 7px 4px !important;
}
.ib-td-icon img {
    vertical-align: middle !important;
    border-radius: 3px !important;
}
.ib-td-info {
    text-align: left !important;
    padding: 7px 8px !important;
}
.ib-bonus-title {
    font-size: 12px !important;
    font-weight: 600 !important;
    color: #f1f5f9 !important;
    display: inline-block !important;
    cursor: help !important;
    line-height: 1.35 !important;
}
.ib-bonus-title:hover {
    color: #38bdf8 !important;
}
.ib-bonus-desc {
    font-size: 10.5px !important;
    color: #94a3b8 !important;
    margin-top: 2px !important;
    line-height: 1.25 !important;
}
.ib-td-val {
    width: 105px !important;
    text-align: center !important;
    padding: 7px 6px !important;
    font-size: 12.5px !important;
    font-weight: 700 !important;
    font-family: 'Consolas', 'Courier New', monospace !important;
    white-space: nowrap !important;
}
.ib-td-sources {
    width: 140px !important;
    text-align: center !important;
    padding: 7px 6px !important;
}
.ib-pill-active {
    display: inline-flex !important;
    align-items: center !important;
    gap: 4px !important;
    padding: 3px 9px !important;
    background: rgba(16, 185, 129, 0.12) !important;
    border: 1px solid rgba(16, 185, 129, 0.35) !important;
    color: #34d399 !important;
    border-radius: 12px !important;
    font-size: 10.5px !important;
    font-weight: 600 !important;
    cursor: help !important;
    white-space: nowrap !important;
}
.ib-pill-idle {
    display: inline-flex !important;
    align-items: center !important;
    gap: 4px !important;
    padding: 3px 8px !important;
    background: rgba(100, 116, 139, 0.1) !important;
    border: 1px solid rgba(100, 116, 139, 0.25) !important;
    color: #64748b !important;
    border-radius: 12px !important;
    font-size: 10.5px !important;
    cursor: help !important;
    white-space: nowrap !important;
}
.ib-empty-cell {
    text-align: center !important;
    padding: 30px 15px !important;
    color: #64748b !important;
    font-style: italic !important;
}
.ib-softcap-badge {
    display: inline-block !important;
    margin-left: 4px !important;
    font-size: 11px !important;
    color: #f59e0b !important;
    cursor: help !important;
    vertical-align: middle !important;
}

@media (max-width: 640px) {
    .ib-th-val, .ib-td-val {
        width: 65px !important;
        font-size: 11.5px !important;
        padding: 6px 2px !important;
    }
    .ib-th-sources, .ib-td-sources {
        width: 65px !important;
        padding: 6px 2px !important;
    }
    .ib-pill-text-long {
        display: none !important;
    }
    .ib-pill-text-short {
        display: inline !important;
    }
    .ib-bonus-desc {
        display: none !important;
    }
    .ib-bonus-title {
        font-size: 11px !important;
    }
    .ib-td-icon {
        width: 30px !important;
        padding: 6px 2px !important;
    }
    .ib-td-icon img {
        width: 18px !important;
        height: 18px !important;
    }
    .ib-td-info {
        padding: 6px 4px !important;
    }
}
@media (min-width: 641px) {
    .ib-pill-text-long {
        display: inline !important;
    }
    .ib-pill-text-short {
        display: none !important;
    }
}
</style>
{/literal}

<div id="page">
    <div id="content">
        <div id="ally_content" class="conteinership ib-main-box">
            <div class="gray_flettab" style="padding-right:0;">
                {$LNG.inb_title}
                <a href="game.php?page=overview" class="tornaindietroa">{$LNG.lm_overview}</a> 
            </div>
            <div class="fleettab8" style="margin-bottom: 0;"></div>

            <!-- Navegación por Categorías con Iconos SVG -->
            <div class="ib-nav-tabs">
                {foreach $tabs as $tab}
                <a href="#" class="ib-tab-btn{if $tab.id == 'all'} active{/if}" data-category="{$tab.id}">
                    <span class="ib-tab-svg">
                        {if $tab.id == 'all'}
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                        {elseif $tab.id == 'combat'}
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="14.5 17.5 3 6 3 3 6 3 17.5 14.5"></polyline><line x1="13" y1="19" x2="19" y2="13"></line><line x1="16" y1="16" x2="20" y2="20"></line><line x1="19" y1="21" x2="21" y2="19"></line><polyline points="14.5 6.5 18 3 21 3 21 6 17.5 9.5"></polyline><line x1="5" y1="14" x2="9" y2="18"></line><line x1="7" y1="17" x2="4" y2="20"></line><line x1="3" y1="19" x2="5" y2="21"></line></svg>
                        {elseif $tab.id == 'economy'}
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h12l4 6-10 13L2 9z"></path><path d="M2 9h20"></path><path d="M10 3l-2 6 4 13 4-13-2-6"></path></svg>
                        {elseif $tab.id == 'speed'}
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                        {elseif $tab.id == 'expansion'}
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><ellipse cx="12" cy="12" rx="10" ry="4" transform="rotate(-30 12 12)"></ellipse></svg>
                        {elseif $tab.id == 'efficiency'}
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                        {/if}
                    </span>
                    <span>{$tab.name}</span>
                    <span class="ib-badge-count">{$tab.counts.active}/{$tab.counts.total}</span>
                </a>
                {/foreach}
            </div>

            <!-- Barra de herramientas y filtros -->
            <div class="ib-toolbar">
                <div style="color: #94a3b8;">
                    <span>{$catCounts.all.active} {$LNG.inb_sources_count}</span>
                </div>
                <div>
                    <label class="ib-filter-label">
                        <input type="checkbox" id="ib-hide-zero-chk">
                        <span>{$LNG.inb_hide_zero}</span>
                    </label>
                </div>
            </div>

            <!-- Tabla de Bonificaciones Robusta y Fluida -->
            <table class="ib-table">
                <thead>
                    <tr class="ib-thead-row">
                        <th class="ib-th-icon">#</th>
                        <th class="ib-th-name">{$LNG.inb_name}</th>
                        <th class="ib-th-val">{$LNG.inb_effective_value}</th>
                        <th class="ib-th-sources">{$LNG.inb_breakdown}</th>
                    </tr>
                </thead>
                <tbody id="ib-table-body">
                    {foreach $bonusRows as $row}
                    <tr class="ib-row" data-category="{$row.category}" data-zero="{if $row.is_zero}1{else}0{/if}">
                        <td class="ib-td-icon">
                            <img src="{$dpath}gebaeude/bonus/{$row.key}.gif" alt="{$row.name}" width="22" height="22">
                        </td>
                        <td class="ib-td-info">
                            <span class="ib-bonus-title tooltip" data-tooltip-content="{$row.tooltip_html}">{$row.name}</span>
                            {if !empty($row.desc_preview)}
                            <div class="ib-bonus-desc">{$row.desc_preview}</div>
                            {/if}
                        </td>
                        <td class="ib-td-val" style="color: {$row.color};">
                            <span class="tooltip" data-tooltip-content="{$row.tooltip_html}">{$row.formatted_value}</span>
                            {if $row.is_softcapped}
                            <span class="ib-softcap-badge tooltip" data-tooltip-content="{$row.tooltip_html}">⚠️</span>
                            {/if}
                        </td>
                        <td class="ib-td-sources">
                            {if $row.source_count > 0}
                            <span class="tooltip ib-pill-active" data-tooltip-content="{$row.tooltip_html}">
                                ⚡ <span class="ib-pill-text-long">{$row.source_count} {$LNG.inb_sources_count}</span><span class="ib-pill-text-short">{$row.source_count} act.</span>
                            </span>
                            {else}
                            <span class="tooltip ib-pill-idle" data-tooltip-content="{$row.tooltip_html}">
                                • Base
                            </span>
                            {/if}
                        </td>
                    </tr>
                    {/foreach}
                    <tr id="ib-empty-msg" style="display: none;">
                        <td colspan="4" class="ib-empty-cell">
                            {$LNG.inb_no_active}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

{literal}
<script type="text/javascript">
$(document).ready(function() {
    var activeCategory = 'all';
    var hideZero = false;

    function applyFilter() {
        var visibleCount = 0;
        $('#ib-table-body tr.ib-row').each(function() {
            var rowCat = $(this).data('category');
            var isZero = $(this).data('zero') == 1;

            var matchCat = (activeCategory === 'all' || rowCat === activeCategory);
            var matchZero = !(hideZero && isZero);

            if (matchCat && matchZero) {
                $(this).show();
                visibleCount++;
            } else {
                $(this).hide();
            }
        });

        if (visibleCount === 0) {
            $('#ib-empty-msg').show();
        } else {
            $('#ib-empty-msg').hide();
        }
    }

    $('.ib-tab-btn').on('click', function(e) {
        e.preventDefault();
        $('.ib-tab-btn').removeClass('active');
        $(this).addClass('active');
        activeCategory = $(this).data('category');
        applyFilter();
    });

    $('#ib-hide-zero-chk').on('change', function() {
        hideZero = $(this).is(':checked');
        applyFilter();
    });
});
</script>
{/literal}
{/block}
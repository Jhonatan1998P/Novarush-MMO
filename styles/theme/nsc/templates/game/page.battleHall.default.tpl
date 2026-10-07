{block name="title" prepend}{$LNG.lm_topkb}{/block}
{block name="content"}
<style>
.battlehall-wrapper {
    width: 100%;
    max-width: 720px;
    margin: 0 auto 15px auto;
    box-sizing: border-box;
}

.battlehall-scroll-container {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    width: 100%;
    border-radius: 4px;
}

.battlehall-table {
    width: 100%;
    min-width: 680px;
    border-collapse: separate;
    border-spacing: 0 3px;
    table-layout: fixed;
    margin: 0;
}

.battlehall-table thead tr {
    background: rgba(0, 0, 0, 0.45);
    height: 28px;
    box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.3), 0 1px rgba(255, 255, 255, 0.08);
}

.battlehall-table th {
    color: #94a3b8;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 5px 8px;
    border: 1px solid rgba(255, 255, 255, 0.06);
    border-bottom: 1px solid rgba(0, 0, 0, 0.5);
    vertical-align: middle;
    box-sizing: border-box;
}

.battlehall-table th a {
    color: #94a3b8;
    text-decoration: none;
    transition: color 0.15s ease;
}

.battlehall-table th a:hover {
    color: #38bdf8;
}

.battlehall-table th a.active-sort {
    color: #38bdf8;
    font-weight: bold;
}

.battlehall-table tbody tr {
    background: #091527;
    background-image: linear-gradient(#061225, #020c1d);
    height: 28px;
    transition: background 0.15s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.battlehall-table tbody tr:hover {
    background: #0f223f;
}

.battlehall-table td {
    padding: 3px 8px;
    font-size: 12px;
    border-top: 1px solid rgba(10, 32, 69, 0.7);
    border-bottom: 1px solid #000;
    vertical-align: middle;
    box-sizing: border-box;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.bh-col-num {
    width: 55px;
    text-align: center;
    color: #a0aec0;
    font-family: 'BicubikRegular', Arial, sans-serif;
    font-weight: bold;
    font-size: 12px;
}

.bh-col-players {
    width: 295px;
    text-align: left;
    padding-left: 12px !important;
}

.bh-col-date {
    width: 170px;
    text-align: center;
    font-size: 11px;
    color: #94a3b8;
}

.bh-col-units {
    width: 160px;
    text-align: right;
    padding-right: 15px !important;
    font-family: Arial, sans-serif;
    font-weight: 600;
    color: #e2e8f0;
}

.bh-link {
    display: inline-flex;
    align-items: center;
    max-width: 100%;
    text-decoration: none;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 22px;
}

.bh-player {
    display: inline-block;
    max-width: 125px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    vertical-align: middle;
}

.bh-winner {
    color: #00FF00 !important;
    font-weight: bold;
    text-shadow: 0 0 6px rgba(0, 255, 0, 0.3);
}

.bh-loser {
    color: #00d4ff !important;
    font-weight: bold;
    text-shadow: 0 0 6px rgba(0, 212, 255, 0.3);
}

.bh-vs {
    margin: 0 6px;
    color: #64748b;
    font-size: 10px;
    font-weight: bold;
    flex-shrink: 0;
    letter-spacing: 0.5px;
}
</style>

<div id="page">
	<div id="content">
        <div class="battlehall-wrapper">
            <div class="gray_flettab" style="margin-bottom: 2px;">
                {$LNG.tkb_top}
                <span style="float:right;font-weight:100;font-size:11px;">
                    <b>Leyenda:</b> 
                    <span style="color:#00FF00;font-weight:bold;">Ganador</span> &nbsp;|&nbsp; 
                    <span style="color:#00d4ff;font-weight:bold;">Perdedor</span>
                </span>      
            </div>
            <div class="fleettab8" style="margin-bottom: 4px;"></div>

            <div class="battlehall-scroll-container">
                <table class="battlehall-table">
                    <thead>
                        <tr>
                            <th class="bh-col-num">Nº</th>
                            <th class="bh-col-players">Peleadores</th>
                            <th class="bh-col-date">
                                <a href="game.php?page=battleHall&amp;order=date&amp;sort={if $sort == "ASC"}DESC{else}ASC{/if}"{if $order == "date"} class="active-sort"{/if}>Fecha</a>
                            </th>
                            <th class="bh-col-units">
                                <a href="game.php?page=battleHall&amp;order=units&amp;sort={if $sort == "ASC"}DESC{else}ASC{/if}"{if $order == "units"} class="active-sort"{/if}>Pérdidas en puntos</a>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $TopKBList as $row}
                        <tr>
                            <td class="bh-col-num">{$row@iteration}</td>
                            <td class="bh-col-players">
                                <a href="game.php?page=raport&amp;mode=battlehall&amp;raport={$row.rid}" target="_blank" class="bh-link" title="{$row.full_attacker} VS {$row.full_defender}">
                                    {if $row.result == "a"}
                                    <span class="bh-player bh-winner">{$row.attacker}</span> <span class="bh-vs">VS</span> <span class="bh-player bh-loser">{$row.defender}</span>
                                    {elseif $row.result == "r"}
                                    <span class="bh-player bh-loser">{$row.attacker}</span> <span class="bh-vs">VS</span> <span class="bh-player bh-winner">{$row.defender}</span>
                                    {else}
                                    <span class="bh-player bh-winner">{$row.attacker}</span> <span class="bh-vs">VS</span> <span class="bh-player bh-loser">{$row.defender}</span>
                                    {/if}
                                </a>
                            </td>
                            <td class="bh-col-date">{$row.date}</td>
                            <td class="bh-col-units">{$row.units|number}</td>
                        </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
{/block}
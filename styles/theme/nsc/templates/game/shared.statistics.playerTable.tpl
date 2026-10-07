<tr class="barraclass">
	<th class="th-pos" colspan="2">{$LNG.st_position}</th>
	<th class="th-player">{$LNG.st_player}</th>
	<th class="th-act"></th>
	<th class="th-ally">{$LNG.st_alliance}</th>
	<th class="th-pts">{$LNG.st_points}</th>
</tr>
{foreach name=RangeList item=RangeInfo from=$RangeList}
<tr class="classificabarra player-stat-row">
    <td id="{$RangeInfo.rank}" class="classstat1">{$RangeInfo.rank}</td>
    <td class="classstat2">{if $RangeInfo.ranking == 0}<span style='color:#87CEEB'>*</span>{elseif $RangeInfo.ranking < 0}<span style='color:red'>{$RangeInfo.ranking}</span>{elseif $RangeInfo.ranking > 0}<span style='color:green'>+{$RangeInfo.ranking}</span>{/if}</td>
    <td class="classstat3">
        <div class="barracla classstat4"></div>		
        <a href="#" class="fbox-s-name classstat5" onclick="return Dialog.Playercard({$RangeInfo.id}, '{$RangeInfo.name}');">
            {if isModuleAvailable($smarty.const.MODULE_RACE)}
            <img class="tooltip" data-tooltip-content="{$LNG.tech.{$RangeInfo.race}}" src="{$dpath}gebaeude/{$RangeInfo.race}.gif" width="18" height="18" style="vertical-align:middle;">
            {/if}
            {if isModuleAvailable($smarty.const.MODULE_FORMGOVERNMENT)}
            <img class="tooltip" data-tooltip-content="{$LNG.tech.{$RangeInfo.formgovernment}}" src="{$dpath}gebaeude/{$RangeInfo.formgovernment}.png" width="18" height="18" style="vertical-align:middle;">
            {/if}
            {if isModuleAvailable($smarty.const.MODULE_ETHICS)}
            <img class="tooltip" data-tooltip-content="{$LNG.tech.{$RangeInfo.ethics}}" src="{$dpath}gebaeude/{$RangeInfo.ethics}.png" width="18" height="18" style="vertical-align:middle;">
            {/if}
            <span class="player-name-text"{if $RangeInfo.id == $CUser_id} style="color:lime"{/if}>{$RangeInfo.name}</span>	
            {if !empty($RangeInfo.class)}<span class="classstat6">{foreach $RangeInfo.class as $class}<span class='galaxy-short-{$class} galaxy-short'>{$ShortStatus.$class} </span>{/foreach}</span>{/if}
        </a>		
    </td>
    <td class="classstat7">
        <span class="stat-actions-box">
            {if !empty($canSpectate) && $RangeInfo.id != $CUser_id}
                <a href="game.php?page=spectate&amp;target={$RangeInfo.id}" class="tooltip spectate-action-link" data-tooltip-content="👁️ Espectear a {$RangeInfo.name}" title="👁️ Espectear en 1ª persona">
                    <svg viewBox="0 0 24 24" width="15" height="15" class="spectate-svg-icon"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" fill="none" stroke="#00e5ff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="3.5" fill="#00e5ff"/></svg>
                </a>
            {/if}
            {if $RangeInfo.id != $CUser_id}
                <a href="#" onclick="return Dialog.PM({$RangeInfo.id});" class="tooltip pm-action-link" data-tooltip-content="{$LNG.st_write_message}"><img src="{$dpath}img/iconav/mesages.png" title="{$LNG.st_write_message}" alt="{$LNG.st_write_message}" class="stat-pm-icon"></a>
            {/if}
        </span>
    </td>
    <td class="classstat8">{if $RangeInfo.allyid != 0}<a href="game.php?page=alliance&amp;mode=info&amp;id={$RangeInfo.allyid}">{if $RangeInfo.allyid == $CUser_ally}<span style="color:#33CCFF">{$RangeInfo.allyname}</span>{else}{$RangeInfo.allyname}{/if}</a>{else}-{/if}<div class="barracla" style=""></div></td>
    <td class="classstat10">{$RangeInfo.points}</td>
</tr>
{/foreach}
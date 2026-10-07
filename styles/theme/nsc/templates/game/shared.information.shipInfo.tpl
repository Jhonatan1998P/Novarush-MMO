{if !empty($FleetInfo.fleetgun)}
<div class="row background-border-black-gray m-1">
    {if $FleetInfo.fleetgun == 'notype'}
    <div class="col-12">
        <div class="card m-1 background-border-black-blue shadow"> 
            <div class="card-body">
                <div class="card-img-left opacity-70 tooltip" style="background:url({$dpath}img/information/notype.png)" data-tooltip-content="{$LNG.in_attack_pt}: {$LNG.in_attack_pt_desc|default:'Fuego cinetico equilibrado convencional'}"></div> 
                <p class="card-title text-align-right">{$LNG.in_attack_pt}</p>
                <p class="card-text text-align-right gradient-gray">{$FleetInfo.attack|number}</p>
            </div>
        </div>
    </div>
    {else}
    {$gunCount = count($FleetInfo.fleetgun)}
    {$gunCol = 'col-6'}
    {if $gunCount == 1}
        {$gunCol = 'col-12'}
    {/if}
    {if !empty($FleetInfo.fleetgun.laser.attack)}
    <div class="{$gunCol}">
        <div class="card m-1 background-border-black-blue shadow"> 
            <div class="card-body">
                <div class="card-img-left opacity-70 tooltip" style="background:url({$dpath}img/information/laser.jpg)" data-tooltip-content="{$LNG.in_attack_laser}: {$LNG.in_attack_laser_desc|default:'Eficaz contra blindaje ligero'}"></div> 
                <p class="card-title text-align-right">{$LNG.in_attack_laser}</p>
                <p class="card-text text-align-right gradient-gray">{$FleetInfo.fleetgun.laser.attack|number}</p>
            </div>
        </div>
    </div>
    {/if}
    {if !empty($FleetInfo.fleetgun.ion.attack)}
    <div class="{$gunCol}">
        <div class="card m-1 background-border-black-blue shadow"> 
            <div class="card-body">
                <div class="card-img-left opacity-70 tooltip" style="background:url({$dpath}img/information/ion.jpg)" data-tooltip-content="{$LNG.in_attack_ionic}: {$LNG.in_attack_ionic_desc|default:'Eficaz contra escudos'}"></div> 
                <p class="card-title text-align-right">{$LNG.in_attack_ionic}</p>
                <p class="card-text text-align-right gradient-gray">{$FleetInfo.fleetgun.ion.attack|number}</p>
            </div>
        </div>
    </div>
    {/if}
    {if !empty($FleetInfo.fleetgun.plasma.attack)}
    <div class="{$gunCol}">
        <div class="card m-1 background-border-black-blue shadow"> 
            <div class="card-body">
                <div class="card-img-left opacity-70 tooltip" style="background:url({$dpath}img/information/plasma.jpg)" data-tooltip-content="{$LNG.in_attack_buster}: {$LNG.in_attack_buster_desc|default:'Gran penetracion termica contra blindaje pesado'}"></div> 
                <p class="card-title text-align-right">{$LNG.in_attack_buster}</p>
                <p class="card-text text-align-right gradient-gray">{$FleetInfo.fleetgun.plasma.attack|number}</p>
            </div>
        </div>
    </div>        
    {/if}
    {if !empty($FleetInfo.fleetgun.gravity.attack)}
    <div class="{$gunCol}">
        <div class="card m-1 background-border-black-blue shadow"> 
            <div class="card-body">
                <div class="card-img-left opacity-70 tooltip" style="background:url({$dpath}img/information/gravity.jpg)" data-tooltip-content="{$LNG.in_attack_graviton}: {$LNG.in_attack_graviton_desc|default:'Dano destructivo masivo'}"></div> 
                <p class="card-title text-align-right">{$LNG.in_attack_graviton}</p>
                <p class="card-text text-align-right gradient-gray">{$FleetInfo.fleetgun.gravity.attack|number}</p>
            </div>
        </div>
    </div>
    {/if}
    {/if}
</div>
{/if}
<div class="row background-border-black-gray m-1">
    <div class="{if $FleetInfo.info.class_shield != 's_none' && $FleetInfo.info.class_shield != 'none'}col-6{else}col-12{/if}">
        <div class="card m-1 background-border-black-blue shadow"> 
            <div class="card-body">
                <div class="card-img-left opacity-70 tooltip" style="background:url({$dpath}img/information/d_{$FleetInfo.info.class_defend}.png)" data-tooltip-content="{$LNG["in_armor_{$FleetInfo.info.class_defend}"]}: {$LNG.in_structural_integrity|default:'Estructura'} {$FleetInfo.structure|number} | {$LNG.in_hull_points|default:'Casco'} {$FleetInfo.hull|number}"></div> 
                <p class="card-title text-align-right">{$LNG["in_armor_{$FleetInfo.info.class_defend}"]}</p>
                <p class="card-text text-align-right gradient-gray">{$FleetInfo.structure|number} <span style="font-size: 11px; color: #5ca6aa;">({$FleetInfo.hull|number} {$LNG.in_hull_points|default:'Casco'})</span></p>
            </div>
        </div>
    </div>
    {if $FleetInfo.info.class_shield != 's_none' && $FleetInfo.info.class_shield != 'none'}
    <div class="col-6">
        <div class="card m-1 background-border-black-blue shadow"> 
            <div class="card-body">
                <div class="card-img-left opacity-70 tooltip" style="background:url({$dpath}img/information/s_{$FleetInfo.info.class_shield}.png)" data-tooltip-content="{$LNG["in_shield_{$FleetInfo.info.class_shield}"]}: {$LNG.in_shield_power_desc|default:'Absorbe dano por ronda de combate'}"></div> 
                <p class="card-title text-align-right">{$LNG["in_shield_{$FleetInfo.info.class_shield}"]}</p>
                <p class="card-text text-align-right gradient-gray">{$FleetInfo.shield|number}</p>
            </div>
        </div>
    </div>
    {/if}
</div>
{if !empty($FleetInfo.is_defense)}
<div class="row background-border-black-gray m-1">
    <div class="col-12">
        <div class="card m-1 background-border-black-blue shadow"> 
            <div class="card-body">
                <div class="card-img-left opacity-70 tooltip" style="background:url({$dpath}img/information/recovery.png)" data-tooltip-content="{$LNG.in_recovery}"></div> 
                <p class="card-title text-align-right">{$LNG.in_defense_recovery_title|default:'Reconstruccion Automatica'}</p>
                <p class="card-text text-align-right gradient-gray">{$LNG.in_recovery}</p>
            </div>
        </div>
    </div>
</div>
{/if}
{if !empty($FleetInfo.tech) && !empty($FleetInfo.speed1)}
<div class="row background-border-black-gray m-1">
    <div class="col-4">
        <div class="card m-1 background-border-black-blue shadow"> 
            <div class="card-body">
                {if $FleetInfo.tech == 1 || $FleetInfo.tech == 4}
                <div class="card-img-left opacity-70 tooltip" style="background:url({$dpath}img/information/comb.png)" data-tooltip-content="{$LNG.tech.115}{if $FleetInfo.tech == 4} - {$FleetInfo.engine_upgrade_note}{/if}"></div> 
                {elseif $FleetInfo.tech == 2 || $FleetInfo.tech == 5}
                <div class="card-img-left opacity-70 tooltip" style="background:url({$dpath}img/information/imp.png)" data-tooltip-content="{$LNG.tech.117}{if $FleetInfo.tech == 5} - {$FleetInfo.engine_upgrade_note}{/if}"></div> 
                {else}
                <div class="card-img-left opacity-70 tooltip" style="background:url({$dpath}img/information/hyper.png)" data-tooltip-content="{$LNG.tech.118}"></div> 
                {/if} 
                <p class="card-title text-align-right">{$LNG.in_base_speed|default:$LNG.in_engine}</p>
                <p class="card-text text-align-right gradient-gray">{$FleetInfo.speed1|number}{if $FleetInfo.speed1 != $FleetInfo.speed2} <span style="font-size: 10px; color: #5ca6aa;">({$FleetInfo.speed2|number})</span>{/if}</p>
            </div>
        </div>
    </div>
    {if !empty($FleetInfo.consumption1)}
    <div class="col-4">
        <div class="card m-1 background-border-black-blue shadow"> 
            <div class="card-body">
                <div class="card-img-left opacity-70 tooltip" style="background:url({$dpath}img/information/consumption.png)" data-tooltip-content="{$LNG.in_fuel_consumption_desc|default:$LNG.in_consumption}"></div> 
                <p class="card-title text-align-right">{$LNG.in_fuel_consumption|default:$LNG.in_consumption}</p>
                <p class="card-text text-align-right gradient-gray">{$FleetInfo.consumption1|number}{if $FleetInfo.consumption1 != $FleetInfo.consumption2} ({$FleetInfo.consumption2|number}){/if}</p>
            </div>
        </div>
    </div>
    {/if}
    {if !empty($FleetInfo.capacity)}
    <div class="col-4">
        <div class="card m-1 background-border-black-blue shadow"> 
            <div class="card-body">
                <div class="card-img-left opacity-70 tooltip" style="background:url({$dpath}img/information/capacity.png)" data-tooltip-content="{$LNG.in_cargo_capacity_desc|default:$LNG.in_capacity}"></div> 
                <p class="card-title text-align-right">{$LNG.in_cargo_capacity|default:$LNG.in_capacity}</p>
                <p class="card-text text-align-right gradient-gray">{$FleetInfo.capacity|number}</p>
            </div>
        </div>
    </div>
    {/if}
</div>
{/if}
{if !empty($FleetInfo.rapidfire.to) || !empty($FleetInfo.rapidfire.from)}
<div class="row background-border-black-gray m-1">
    {if !empty($FleetInfo.rapidfire.to)}
    <div class="{if !empty($FleetInfo.rapidfire.from)}col-6{else}col-12{/if}">
        <div class="card m-1 background-border-black-blue shadow"> 
            <div class="card-body">
                <p class="card-title text-align-right">{$LNG.in_rf_again}</p>
                <div style="display: flex; flex-wrap: wrap; gap: 4px; justify-content: flex-start; padding: 2px 0;">
                {foreach $FleetInfo.rapidfire.to as $rapidfireID => $shoots}
                    <div class="card background-border-black-blue" style="width: 38px; height: 38px; position: relative; margin: 1px; flex-shrink: 0;"> 
                        <span class="card-img-text background-color-blue opacity-70 tooltip" data-tooltip-content="{$LNG.tech.$rapidfireID}: {$shoots|number} {$LNG.in_rf_shoots|default:'disp.'} ({round((($shoots - 1) / $shoots) * 100, 1)}% {$LNG.in_rf_prob|default:'prob.'})">{$shoots|number}</span>
                        <img src="{$dpath}gebaeude/{$rapidfireID}.gif" alt="{$LNG.tech.$rapidfireID}" class="opacity-70 tooltip" data-tooltip-content="{$LNG.tech.$rapidfireID}: {$shoots|number} {$LNG.in_rf_shoots|default:'disp.'} ({round((($shoots - 1) / $shoots) * 100, 1)}% {$LNG.in_rf_prob|default:'prob.'})" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                {/foreach}
                </div>
            </div>
        </div>
    </div>
    {/if}
    {if !empty($FleetInfo.rapidfire.from)} 
    <div class="{if !empty($FleetInfo.rapidfire.to)}col-6{else}col-12{/if}">
        <div class="card m-1 background-border-black-blue shadow"> 
            <div class="card-body">
                <p class="card-title text-align-right">{$LNG.in_rf_from}</p>
                <div style="display: flex; flex-wrap: wrap; gap: 4px; justify-content: flex-start; padding: 2px 0;">
                {foreach $FleetInfo.rapidfire.from as $rapidfireID => $shoots}
                    <div class="card background-border-black-blue" style="width: 38px; height: 38px; position: relative; margin: 1px; flex-shrink: 0;"> 
                        <span class="card-img-text background-color-red opacity-70 tooltip" data-tooltip-content="{$LNG.tech.$rapidfireID}: Recibe {$shoots|number} ({round((($shoots - 1) / $shoots) * 100, 1)}% peligro)">{$shoots|number}</span>
                        <img src="{$dpath}gebaeude/{$rapidfireID}.gif" alt="{$LNG.tech.$rapidfireID}" class="opacity-70 tooltip" data-tooltip-content="{$LNG.tech.$rapidfireID}: Recibe {$shoots|number} ({round((($shoots - 1) / $shoots) * 100, 1)}% peligro)" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                {/foreach}
                </div>
            </div>
        </div>
    </div>
    {/if}
</div>
{/if}
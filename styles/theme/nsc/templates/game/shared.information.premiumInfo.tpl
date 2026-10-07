{if !empty($PremiumInfo)}
<div class="row background-border-black-gray m-1" style="margin-top: 8px !important;">
    <!-- Tipo de Oferta -->
    <div class="col-6">
        <div class="card m-1 background-border-black-blue shadow"> 
            <div class="card-body">
                <div class="card-img-left opacity-70 tooltip" style="background:url({$dpath}img/iconav/{$PremiumInfo.category_icon}); background-size: cover; background-repeat: no-repeat;" data-tooltip-content="{$PremiumInfo.category_name}"></div> 
                <p class="card-title text-align-right">Tipo de Oferta</p>
                <p class="card-text text-align-right gradient-gray" style="color: #60a5fa; font-weight: bold;">{$PremiumInfo.category_name}</p>
            </div>
        </div>
    </div>

    <!-- Duración de la Compra -->
    <div class="col-6">
        <div class="card m-1 background-border-black-blue shadow"> 
            <div class="card-body">
                <div class="card-img-left opacity-70 tooltip" style="background:url({$dpath}img/iconav/time.png); background-size: cover; background-repeat: no-repeat;" data-tooltip-content="Duración base de la adquisición"></div> 
                <p class="card-title text-align-right">Duración de la Compra</p>
                <p class="card-text text-align-right gradient-gray" style="color: #38bdf8; font-weight: bold;">{$PremiumInfo.duration_text}</p>
            </div>
        </div>
    </div>

    <!-- Estado Actual -->
    <div class="col-6">
        <div class="card m-1 background-border-black-blue shadow"> 
            <div class="card-body">
                <div class="card-img-left opacity-70 tooltip" style="background:url({$dpath}img/iconav/stat.png); background-size: cover; background-repeat: no-repeat;" data-tooltip-content="Estado del beneficio en tu cuenta"></div> 
                <p class="card-title text-align-right">Estado Actual</p>
                {if $PremiumInfo.is_active}
                <p class="card-text text-align-right gradient-gray" style="color: #22c55e; font-weight: bold;">Activo ({$PremiumInfo.time_left_text})</p>
                {else}
                <p class="card-text text-align-right gradient-gray" style="color: #94a3b8;">Inactivo</p>
                {/if}
            </div>
        </div>
    </div>

    <!-- Coste de Compra -->
    <div class="col-6">
        <div class="card m-1 background-border-black-blue shadow"> 
            <div class="card-body">
                {foreach $PremiumInfo.costs as $Cost}
                <div class="card-img-left opacity-70 tooltip" style="background:url({$dpath}img/resources/{$Cost.id}f.png); background-size: cover; background-repeat: no-repeat;" data-tooltip-content="{$Cost.name}"></div> 
                <p class="card-title text-align-right">Coste ({$Cost.name})</p>
                {if !empty($Cost.is_min_two)}
                <p class="card-text text-align-right gradient-gray" style="color: #facc15; font-weight: bold;">
                    {$Cost.min_amount|number} <span style="font-size: 10px; color: #94a3b8; font-weight: normal;">(1 día: 2x{$Cost.amount|number})</span>
                </p>
                {else}
                <p class="card-text text-align-right gradient-gray" style="color: #facc15; font-weight: bold;">{$Cost.amount|number}</p>
                {/if}
                {/foreach}
            </div>
        </div>
    </div>
</div>

{if !empty($Bonus)}
<div class="row background-border-black-gray m-1" style="margin-top: 4px !important;">
    <div class="col-12">
        <div class="card m-1 background-border-black-blue shadow">
            <div class="card-body" style="min-height: auto; padding: 6px 10px;">
                <p class="card-title" style="color: #38bdf8; font-weight: bold; margin-bottom: 5px;">
                    <img src="{$dpath}img/iconav/star.png" style="vertical-align: middle; width: 14px; height: 14px; margin-right: 4px;"> Efectos y Bonificaciones
                </p>
                <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                    {foreach $Bonus as $BonusName => $elementBouns}
                    <div style="background: rgba(0, 20, 40, 0.65); border: 1px solid #1e3a5f; border-radius: 4px; padding: 3px 8px; font-size: 11px;">
                        <span style="color: {if $elementBouns[0] < 0}#ef4444{else}#22c55e{/if}; font-weight: bold;">
                            {if $elementBouns[0] < 0}-{else}+{/if}{if $elementBouns[1] == 0}{abs($elementBouns[0] * 100)}%{else}{floatval($elementBouns[0])}{/if}
                        </span>
                        <span style="color: #e2e8f0; margin-left: 4px;">{$LNG.bonus.$BonusName}</span>
                    </div>
                    {/foreach}
                </div>
            </div>
        </div>
    </div>
</div>
{/if}

{if !empty($PremiumInfo.affected_techs)}
<div class="row background-border-black-gray m-1" style="margin-top: 4px !important;">
    <div class="col-12">
        <div class="card m-1 background-border-black-blue shadow">
            <div class="card-body" style="min-height: auto; padding: 6px 10px;">
                <p class="card-title" style="color: #a78bfa; font-weight: bold; margin-bottom: 5px;">
                    <img src="{$dpath}img/iconav/tech.png" style="vertical-align: middle; width: 14px; height: 14px; margin-right: 4px;"> Tecnologías y Unidades Beneficiadas
                </p>
                <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                    {foreach $PremiumInfo.affected_techs as $Tech}
                    <div style="background: rgba(0, 20, 40, 0.65); border: 1px solid #1e3a5f; border-radius: 4px; padding: 3px 8px; font-size: 11px;">
                        <span style="color: #38bdf8;">{$Tech.name}</span>
                        {if $Tech.count > 1}<span style="color: #94a3b8; margin-left: 3px;">(x{$Tech.count})</span>{/if}
                    </div>
                    {/foreach}
                </div>
            </div>
        </div>
    </div>
</div>
{/if}
{/if}

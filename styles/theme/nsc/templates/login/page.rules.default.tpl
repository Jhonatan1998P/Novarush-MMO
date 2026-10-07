{block name="title" prepend}{$LNG.nav_rules}{/block}
{block name="content"}
<main role="main" class="container mt-4 mb-5">
    <div class="card bg-dark text-light border-primary shadow mb-4">
        <div class="card-header bg-primary text-white d-flex flex-wrap justify-content-between align-items-center py-3">
            <div>
                <h4 class="mb-0 font-weight-bold" style="letter-spacing: 0.5px;">
                    <img src="./styles/theme/nsc/img/iconav/rules.png" alt="Rules" style="height: 28px; width: 28px; vertical-align: middle; margin-right: 8px;">
                    {$LNG.nav_rules}
                </h4>
                <small class="text-light" style="opacity: 0.85;">
                    {if $activeLang == 'es'}
                        Reglamento oficial y normas de juego limpio del universo NovaRush
                    {else}
                        Official rules and fair play guidelines for NovaRush universe
                    {/if}
                </small>
            </div>
            
            <div class="mt-2 mt-md-0 d-flex align-items-center">
                <span class="mr-2 text-white-50" style="font-size: 13px;">
                    {if $activeLang == 'es'}Idioma:{else}Language:{/if}
                </span>
                <div class="btn-group btn-group-sm" role="group">
                    <a href="index.php?page=rules&amp;lang=es" class="btn {if $activeLang == 'es'}btn-light font-weight-bold shadow-sm{else}btn-outline-light{/if}">
                        🇪🇸 Español
                    </a>
                    <a href="index.php?page=rules&amp;lang=en" class="btn {if $activeLang == 'en'}btn-light font-weight-bold shadow-sm{else}btn-outline-light{/if}">
                        🇬🇧 English
                    </a>
                </div>
            </div>
        </div>

        {if !empty($accountLang)}
        <div class="card-body py-2 px-3 text-white" style="font-size: 13px; background: rgba(255, 255, 255, 0.05); border-top: 1px solid rgba(255, 255, 255, 0.1);">
            <span class="badge badge-success mr-2" style="font-size: 11px;">✓ Perfil</span>
            {if $activeLang == 'es'}
                Idioma detectado según tu cuenta: <strong>{if $accountLang == 'es'}Español (ES){elseif $accountLang == 'en'}English (EN){else}{$accountLang|upper}{/if}</strong>
            {else}
                Language detected based on your account profile: <strong>{if $accountLang == 'es'}Español (ES){elseif $accountLang == 'en'}English (EN){else}{$accountLang|upper}{/if}</strong>
            {/if}
        </div>
        {/if}
    </div>

    <div class="rules-content">
        {$rules}
    </div>
</main>
{/block}
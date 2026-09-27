<nav class="navbar navbar-expand-md  fixed-top bg-dark navbar-dark">
    <div class="container-xl">
        <a class="navbar-brand" href="#">
            <img src="../styles/resource/images/icons/192.png" alt="" width="30" height="30" class="d-inline-block align-text-top">
            {$LNG.game_name}
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarsExample07XL" aria-controls="navbarsExample07XL" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarsExample07XL">
            <ul class="navbar-nav mr-auto">

                {if count($languages) > 1}
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="dropdown07XL" data-toggle="dropdown" aria-expanded="false">{$LNG.nav_lang}</a>
                    <div class="dropdown-menu" aria-labelledby="dropdown07XL">
                    {foreach $languages as $langKey => $langName}
                        <a class="dropdown-item" href="?lang={$langKey}" rel="alternate" hreflang="{$langKey}" title="{$langName}">{$langName}</a>
                    {/foreach}
                    </div>
                </li>
                {/if}
            </ul>
        </div>
    </div>
</nav>
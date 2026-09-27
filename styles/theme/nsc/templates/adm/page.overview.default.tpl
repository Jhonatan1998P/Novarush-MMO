{block name="title" prepend}{$LNG.lm_overview}{/block}
{block name="script" append}{/block}
{block name="content"}
<main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
    <div class="card mb-4 shadow-sm">
        <h5 class="card-header">{$LNG.ov_header_hello}</h5>
        <div class="card-body overflow-auto" style="max-height: 415px;">
            <p class="card-text">{$LNG.ov_text_hello}</p>
        </div>
    </div>
    <div class="album">
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4 shadow-sm">
                    <h5 class="card-header">NovaRush</h5>
                    <div class="card-body overflow-auto" style="max-height: 415px;">
                        <p><strong>Proyecto:</strong> NovaRush</p>
                        <p><strong>Bifurcación:</strong> LORDA1998</p>
                        <p><strong>Año:</strong> 2026</p>
                        <p><strong>Versión:</strong> {$VERSION}</p>
                        <p class="text-success"><strong>Estado:</strong> Servidor Operativo en Línea</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card mb-4 shadow-sm">
                    <h5 class="card-header">{$LNG.ov_header_statistics}</h5>
                    <div class="list-group overflow-auto" style="max-height: 415px;">
                        <button type="button" class="list-group-item list-group-item-action">{$LNG.ov_statistics_backups}
                            <span class="badge badge-primary badge-pill">{$filecount}</span>
                        </button>
                        <button type="button" class="list-group-item list-group-item-action">{$LNG.ov_statistics_user}
                            <span class="badge badge-primary badge-pill">{$user}</span>
                        </button>
                        <button type="button" class="list-group-item list-group-item-action">{$LNG.ov_statistics_userinactive}
                            <span class="badge badge-primary badge-pill">{$userinactive}</span>
                        </button>
                        <button type="button" class="list-group-item list-group-item-action">{$LNG.ov_statistics_useronline}
                            <span class="badge badge-primary badge-pill">{$useronline}</span>
                        </button>
                        <button type="button" class="list-group-item list-group-item-action">{$LNG.ov_statistics_planet}
                            <span class="badge badge-primary badge-pill">{$planet}</span>
                        </button>
                        <button type="button" class="list-group-item list-group-item-action">{$LNG.ov_statistics_supportticks}
                            <span class="badge badge-primary badge-pill">{$supportticks}</span>
                        </button>
                        <button type="button" class="list-group-item list-group-item-action">{$LNG.ov_statistics_log}
                            <span class="badge badge-primary badge-pill">{$log}</span>
                        </button>
                        <button type="button" class="list-group-item list-group-item-action">{$LNG.ov_statistics_cron}
                            <span class="badge badge-primary badge-pill">{$cron}</span>
                        </button>
                        <button type="button" class="list-group-item list-group-item-action">{$LNG.ov_statistics_news}
                            <span class="badge badge-primary badge-pill">{$news}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card mb-3">
        <h5 class="card-header">{$LNG.ov_header_credits}</h5>
        <div class="card-body">
            <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link active" id="pills-NovaRush-tab" data-toggle="pill" href="#pills-NovaRush" role="tab" aria-controls="pills-NovaRush" aria-selected="true">NovaRush</a>
                </li>
            </ul>
            <div class="tab-content" id="pills-tabContent">
                <div class="tab-pane fade show active" id="pills-NovaRush" role="tabpanel" aria-labelledby="pills-NovaRush-tab">
                    <table class="table table-dark table-hover">
                        <thead>
                            <tr>
                                <th>{$LNG.ov_credits_name}</th>
                                <th>{$LNG.ov_credits_rights}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>LORDA1998</td>
                                <td>Fundador / Desarrollador Principal (2026)</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>
{/block}
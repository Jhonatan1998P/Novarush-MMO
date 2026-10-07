{block name="title" prepend}Gestión de Bots IA{/block}
{block name="content"}
<main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
    
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">🤖 Bots de Inteligencia Artificial (NovaRush)</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <form method="post" action="admin.php?page=bots">
                <input type="hidden" name="action" value="run_cycle">
                <button type="submit" class="btn btn-primary">⚡ Forzar Ciclo de IA Inmediato</button>
            </form>
        </div>
    </div>

    {if $cycleResults !== null}
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <h5 class="alert-heading">✅ ¡Ciclo de IA Ejecutado Exitosamente!</h5>
        <hr>
        {if empty($cycleResults)}
            <p class="mb-0">No hay bots activos para procesar.</p>
        {else}
            <ul class="mb-0">
            {foreach from=$cycleResults key=bId item=res}
                {if isset($res.error)}
                    <li><strong>Bot #{$bId}:</strong> <span class="text-danger">Error: {$res.error}</span></li>
                {else}
                    <li>
                        <strong>{$res.name} (ID: {$bId}):</strong>
                        <span class="badge badge-info">Fleetsaves/Dodges: {$res.dodged}</span> |
                        <span class="badge badge-success">Minas: {$res.mines_built}</span> |
                        <span class="badge badge-success">Investigaciones: {if !empty($res.researched)}{$res.researched}{else}0{/if}</span> |
                        <span class="badge badge-success">Reclutadas: {if !empty($res.recruited)}{array_sum($res.recruited)}{else}0{/if} naves</span> |
                        <span class="badge badge-primary">Unificadas: {$res.rallied}</span> |
                        <span class="badge badge-secondary">Convoys: {$res.transported}</span> |
                        <span class="badge badge-warning text-dark">Espionajes: {$res.spied}</span> |
                        <span class="badge badge-danger">Ataques: {$res.attacked}</span> |
                        <span class="badge badge-info">Reciclajes: {$res.recycled}</span>
                    </li>
                {/if}
            {/foreach}
            </ul>
        {/if}
    </div>
    {/if}

    <!-- CARD 1: LISTADO DE BOTS REGISTRADOS -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">📋 Bots Activos en la Galaxia</h5>
            <span class="badge badge-pill badge-primary">Total: {count($bots)}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-dark table-hover table-striped mb-0 text-center">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre de Bot</th>
                            <th>Base Principal</th>
                            <th>Colonias</th>
                            <th>Personalidad</th>
                            <th>Puntos</th>
                            <th>Última Actividad</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        {if empty($bots)}
                        <tr>
                            <td colspan="9" class="text-muted py-4">No hay bots configurados en el universo. ¡Crea el primero abajo!</td>
                        </tr>
                        {else}
                        {foreach from=$bots item=bot}
                        <tr>
                            <td><strong>#{$bot.bot_id}</strong></td>
                            <td class="text-left font-weight-bold">
                                🤖 {$bot.username}
                                <br><small class="text-muted">{$bot.email}</small>
                            </td>
                            <td><span class="badge badge-secondary">{$bot.coords}</span><br><small>{$bot.home_name}</small></td>
                            <td><span class="badge badge-info">{$bot.colony_count} planetas</span></td>
                            <td>
                                {if $bot.personality == 'aggressive'}
                                    <span class="badge badge-danger">⚔️ Agresivo / Belicista</span>
                                {elseif $bot.personality == 'raider'}
                                    <span class="badge badge-warning text-dark">🏴‍☠️ Saqueador / Raider</span>
                                {elseif $bot.personality == 'defensive'}
                                    <span class="badge badge-success">🛡️ Minero / Defensivo</span>
                                {else}
                                    <span class="badge badge-primary">⚖️ Equilibrado / General</span>
                                {/if}
                            </td>
                            <td><strong>{$bot.points_fmt}</strong></td>
                            <td>
                                {if $bot.last_activity > 0}
                                    {$bot.last_activity|date_format:"%H:%M:%S (%d/%m)"}
                                {else}
                                    <span class="text-muted">Pendiente</span>
                                {/if}
                            </td>
                            <td>
                                {if $bot.is_active == 1}
                                    <span class="badge badge-success">EN SERVICIO</span>
                                {else}
                                    <span class="badge badge-secondary">PAUSADO</span>
                                {/if}
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <form method="post" action="admin.php?page=bots" style="display:inline;">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="bot_id" value="{$bot.bot_id}">
                                        <button type="submit" class="btn {if $bot.is_active == 1}btn-warning{else}btn-success{/if} btn-sm" title="{if $bot.is_active == 1}Pausar{else}Reanudar{/if}">
                                            {if $bot.is_active == 1}⏸️ Pausar{else}▶️ Activar{/if}
                                        </button>
                                    </form>
                                    <form method="post" action="admin.php?page=bots" onsubmit="return confirm('¿Desvincular este bot de la IA?');" style="display:inline; margin-left:4px;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="bot_id" value="{$bot.bot_id}">
                                        <button type="submit" class="btn btn-danger btn-sm" title="Eliminar">🗑️</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        {/foreach}
                        {/if}
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ROW: CREACIÓN Y ASIGNACIÓN DE BOTS -->
    <div class="row">
        <!-- CREAR NUEVO BOT -->
        <div class="col-lg-7 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">➕ Desplegar Nuevo Bot Competitivo</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="admin.php?page=bots">
                        <input type="hidden" name="action" value="create">

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label><strong>Nombre del Bot:</strong></label>
                                <input type="text" name="name" class="form-control" placeholder="Dejar vacío para nombre astronómico al azar">
                            </div>
                            <div class="form-group col-md-6">
                                <label><strong>Doctrina / Personalidad:</strong></label>
                                <select name="personality" class="form-control">
                                    <option value="balanced" selected>⚖️ Equilibrado (Flota, Minas y Defensas)</option>
                                    <option value="aggressive">⚔️ Belicista (Ataques a flotas y armada pesada)</option>
                                    <option value="raider">🏴‍☠️ Saqueador (Espionaje y raids a inactivos)</option>
                                    <option value="defensive">🛡️ Defensivo (Fortaleza y contragolpe)</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label><strong>Nivel de Inicio (Tier Militar):</strong></label>
                                <select name="tier" class="form-control">
                                    <option value="novice">Novato (Flota ligera, minas nivel 12)</option>
                                    <option value="intermediate" selected>Intermedio (Flota equilibrada, minas nivel 22, 2 colonias)</option>
                                    <option value="advanced">Avanzado (Cruceros, Acorazados, Destructores, minas 28)</option>
                                    <option value="titan">Titán Galáctico (Armada masiva, Estrellas de Muerte, minas 35)</option>
                                </select>
                            </div>
                            <div class="form-group col-md-2">
                                <label><strong>Galaxia:</strong></label>
                                <input type="number" name="galaxy" class="form-control" value="1" min="1" max="9">
                            </div>
                            <div class="form-group col-md-2">
                                <label><strong>Sistema:</strong></label>
                                <input type="number" name="system" class="form-control" placeholder="Auto" min="0" max="400">
                            </div>
                            <div class="form-group col-md-2">
                                <label><strong>Planeta:</strong></label>
                                <input type="number" name="planet" class="form-control" placeholder="Auto" min="0" max="15">
                            </div>
                        </div>

                        <div class="alert alert-info py-2 mb-3">
                            <small>ℹ️ El bot se creará con cuenta propia de usuario, planeta base, colonias adicionales para reclutamiento paralelo, hangares funcionales y defensas iniciales.</small>
                        </div>

                        <button type="submit" class="btn btn-success btn-block">🚀 Desplegar Bot en el Universo</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- CONVERTIR USUARIO EXISTENTE -->
        <div class="col-lg-5 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">🔄 Asignar IA a Jugador Existente</h5>
                </div>
                <div class="card-body">
                    {if empty($existingUsers)}
                        <p class="text-muted">Todos los jugadores elegibles ya están gestionados por la IA.</p>
                    {else}
                    <form method="post" action="admin.php?page=bots">
                        <input type="hidden" name="action" value="register_existing">

                        <div class="form-group">
                            <label><strong>Seleccionar Cuenta:</strong></label>
                            <select name="user_id" class="form-control">
                                {foreach from=$existingUsers item=usr}
                                <option value="{$usr.id}">#{$usr.id} - {$usr.username}</option>
                                {/foreach}
                            </select>
                        </div>

                        <div class="form-group">
                            <label><strong>Personalidad:</strong></label>
                            <select name="personality" class="form-control">
                                <option value="balanced" selected>⚖️ Equilibrado</option>
                                <option value="aggressive">⚔️ Agresivo / Belicista</option>
                                <option value="raider">🏴‍☠️ Saqueador</option>
                                <option value="defensive">🛡️ Defensivo</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-warning btn-block">🧠 Activar Inteligencia Artificial en Cuenta</button>
                    </form>
                    {/if}
                </div>
            </div>
        </div>
    </div>

    <!-- CARD 3: LOGS EN TIEMPO REAL -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">📡 Registro de Decisiones Tácticas en Tiempo Real</h5>
            <small class="text-muted">Últimos 30 eventos</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-dark table-striped table-sm mb-0">
                    <thead>
                        <tr>
                            <th style="width: 150px;">Hora</th>
                            <th style="width: 140px;">Bot</th>
                            <th style="width: 120px;">Tipo de Acción</th>
                            <th>Detalle Táctico</th>
                        </tr>
                    </thead>
                    <tbody>
                        {if empty($logs)}
                        <tr>
                            <td colspan="4" class="text-muted text-center py-3">No hay registros de actividad aún.</td>
                        </tr>
                        {else}
                        {foreach from=$logs item=log}
                        <tr>
                            <td><small class="text-muted">{$log.timestamp|date_format:"%H:%M:%S (%d/%m)"}</small></td>
                            <td><strong>{$log.username|default:"Bot #{$log.bot_id}"}</strong></td>
                            <td>
                                {if $log.action_type == 'dodge'}
                                    <span class="badge badge-info">🛡️ FLEETSAVE</span>
                                {elseif $log.action_type == 'attack'}
                                    <span class="badge badge-danger">⚔️ ATAQUE</span>
                                {elseif $log.action_type == 'recruit'}
                                    <span class="badge badge-success">🏭 RECLUTAR</span>
                                {elseif $log.action_type == 'rally'}
                                    <span class="badge badge-primary">🚀 UNIFICAR</span>
                                {elseif $log.action_type == 'transport'}
                                    <span class="badge badge-secondary">🚚 CONVOY</span>
                                {elseif $log.action_type == 'spy'}
                                    <span class="badge badge-warning text-dark">👁️ ESPIONAJE</span>
                                {elseif $log.action_type == 'recycle'}
                                    <span class="badge badge-light text-dark">♻️ RECICLAR</span>
                                {else}
                                    <span class="badge badge-dark">⚙️ SISTEMA</span>
                                {/if}
                            </td>
                            <td>{$log.details}</td>
                        </tr>
                        {/foreach}
                        {/if}
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- CARD 4: EXPLICABILIDAD - REGISTRO ESTRUCTURADO DE DECISIONES (v2) -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">🧠 Explicabilidad de IA: Decisiones y Alternativas Evaluadas (v2)</h5>
            <small class="text-muted">Registro no explotable con semilla determinista</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-dark table-striped table-sm mb-0">
                    <thead>
                        <tr>
                            <th style="width: 140px;">Hora</th>
                            <th style="width: 130px;">Bot</th>
                            <th style="width: 140px;">Decisión</th>
                            <th>Razón / Contexto</th>
                            <th style="width: 180px;">Acción Elegida</th>
                            <th style="width: 80px;">Puntaje</th>
                        </tr>
                    </thead>
                    <tbody>
                        {if empty($decisions)}
                        <tr>
                            <td colspan="6" class="text-muted text-center py-3">No hay registros de decisiones estructuradas aún.</td>
                        </tr>
                        {else}
                        {foreach from=$decisions item=dec}
                        <tr>
                            <td><small class="text-muted">{$dec.created_at|date_format:"%H:%M:%S (%d/%m)"}</small></td>
                            <td><strong>{$dec.username|default:"Bot #{$dec.bot_id}"}</strong></td>
                            <td><span class="badge badge-info">{$dec.decision_type}</span></td>
                            <td><small>{$dec.context_summary}</small></td>
                            <td><code>{$dec.chosen_action}</code></td>
                            <td><span class="badge badge-success">{$dec.chosen_score}</span></td>
                        </tr>
                        {/foreach}
                        {/if}
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</main>
{/block}

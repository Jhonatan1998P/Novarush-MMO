{block name="title" prepend}Eventos de Guerra{/block}
{block name="content"}
<main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
    
    <!-- CARD 1: ESTADO DEL EVENTO -->
    <div class="card mb-4">
        <h5 class="card-header d-flex justify-content-between align-items-center">
            <span>⚔️ Estado del Evento de Guerra (Fortaleza Boss)</span>
            {if $status.active}
                <span class="badge badge-success">ACTIVO EN CURSO</span>
            {elseif $status.scheduled}
                <span class="badge badge-warning">PROGRAMADO / CUENTA REGRESIVA</span>
            {else}
                <span class="badge badge-secondary">INACTIVO</span>
            {/if}
        </h5>
        <div class="card-body">
            {if $status.active}
                <div class="alert alert-danger" style="border-left: 5px solid #e74c3c;">
                    <h5>🔥 ¡Fortaleza Ancestral Activa en Galaxia!</h5>
                    <p class="mb-1"><strong>Coordenadas:</strong> [{$status.event.galaxy}:{$status.event.system}:{$status.event.planet}]</p>
                    <p class="mb-1"><strong>Budget Asignado:</strong> {$status.event.calculated_budget|number_format} Metal ({$status.event.budget_ratio * 100}% del servidor)</p>
                    <p class="mb-1"><strong>Recompensa Estocada Final:</strong> {$status.event.containers_per_winner} Contenedores y {$status.event.antimatter_per_winner} Antimateria por participante.</p>
                    <p class="mb-1"><strong>Tiempo Restante:</strong> <span class="badge badge-danger" style="font-size:14px;">{math equation="floor(x / 3600)" x=$status.remaining_seconds}h {math equation="floor((x % 3600) / 60)" x=$status.remaining_seconds}m {math equation="x % 60" x=$status.remaining_seconds}s</span></p>
                    <p class="mb-0"><strong>Unidades Defensoras Restantes en Base:</strong> <span class="badge badge-info" style="font-size:14px;">{$status.total_surviving_units|number_format} unidades</span></p>
                </div>

                <div class="table-responsive mb-3">
                    <table class="table table-dark table-striped table-bordered table-sm text-center">
                        <thead>
                            <tr>
                                <th>Unidad</th>
                                <th>Cantidad Restante en Pie</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach from=$status.surviving_units key=id item=unit}
                            <tr>
                                <td>{$unit.name} ({$id})</td>
                                <td><strong>{$unit.amount|number_format}</strong></td>
                            </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>

                <form method="post" action="admin.php?page=events" onsubmit="return confirm('¿Seguro que deseas forzar la finalización de este evento? El planeta desaparecerá y las flotas en vuelo volverán.');">
                    <input type="hidden" name="action" value="stop">
                    <button type="submit" class="btn btn-danger btn-block">⛔ Cancelar / Forzar Expiración Inmediata</button>
                </form>
            {elseif $status.scheduled}
                <div class="alert alert-warning" style="border-left: 5px solid #f39c12;">
                    <h5>⏳ ¡Evento Programado en Cuenta Regresiva Previa!</h5>
                    <p class="mb-1"><strong>Tiempo para Iniciar el Evento:</strong> <span class="badge badge-warning" style="font-size:15px; color:#222;">{math equation="floor(x / 3600)" x=$status.countdown_seconds}h {math equation="floor((x % 3600) / 60)" x=$status.countdown_seconds}m {math equation="x % 60" x=$status.countdown_seconds}s</span></p>
                    <p class="mb-1"><strong>Aviso en Vivo:</strong> Visible actualmente en la pantalla principal (<code>game.php</code>) con temporizador para todos los jugadores.</p>
                    <p class="mb-1"><strong>Budget Configurado:</strong> {$status.event.budget_ratio * 100}% ({$status.event.calculated_budget|number_format} Metal)</p>
                    <p class="mb-1"><strong>Duración de Combate:</strong> {$status.event.duration_hours} horas una vez que inicie.</p>
                    <p class="mb-0"><strong>Recompensas por Ganador:</strong> {$status.event.containers_per_winner} Contenedores y {$status.event.antimatter_per_winner} Antimateria.</p>
                </div>

                <form method="post" action="admin.php?page=events" onsubmit="return confirm('¿Deseas cancelar el inicio programado de este evento?');">
                    <input type="hidden" name="action" value="cancel_scheduled">
                    <button type="submit" class="btn btn-warning btn-block">⛔ Cancelar Cuenta Regresiva / Anular Lanzamiento</button>
                </form>
            {else}
                <div class="alert alert-info">
                    <p class="mb-0">No hay ningún evento de guerra activo ni programado en este momento. Configura los parámetros a continuación y haz clic en <strong>Programar / Lanzar Fortaleza Ancestral</strong> para iniciar uno nuevo.</p>
                </div>
                {if !empty($status.last_event)}
                <p class="text-muted small">
                    Último evento registrado: <strong>{$status.last_event.event_name}</strong> en [{$status.last_event.galaxy}:{$status.last_event.system}:{$status.last_event.planet}] - Estado: <strong>{$status.last_event.status|upper}</strong>.
                </p>
                {/if}
            {/if}
        </div>
    </div>

    <!-- CARD 2: CONFIGURACIÓN Y LANZAMIENTO -->
    <div class="card mb-4">
        <h5 class="card-header">⚙️ Configuración y Lanzamiento del Evento</h5>
        <div class="card-body">
            <div class="alert alert-secondary">
                <strong>Budget Militar Total Detectado en el Servidor (sin Admin):</strong>
                <span class="badge badge-success ml-2" style="font-size: 16px;">{$server_budget_fmt} Metal Puro</span>
                <br><small class="text-muted">Calculado automáticamente a partir de todas las flotas y defensas de los jugadores activos.</small>
            </div>

            <form method="post" action="admin.php?page=events">
                <input type="hidden" name="action" value="start">
                
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="budget_ratio"><strong>Ratio de Budget para el Boss (%):</strong></label>
                        <select name="budget_ratio" id="budget_ratio" class="form-control">
                            <option value="0.375">37.5% - Dificultad Media-Alta (Derrotable en Solo al ~70% de bajas)</option>
                            <option value="0.50">50.0% - Dificultad Muy Alta (Exige Flotas Masivas o SAC)</option>
                            <option value="0.60" selected>60.0% - Predeterminado Cooperativo (Derrota total en Solo, exige SAC al ~65-70%)</option>
                            <option value="0.75">75.0% - Dificultad Pesada (SAC de 3+ jugadores)</option>
                            <option value="1.00">100.0% - Dificultad Titan (Todo el ejército del servidor duplicado)</option>
                        </select>
                        <small class="form-text text-muted">Define qué proporción del metal del servidor se invertirá en el Boss.</small>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="pre_countdown_minutes"><strong>Aviso Previo / Cuenta Regresiva para Iniciar:</strong></label>
                        <select name="pre_countdown_minutes" id="pre_countdown_minutes" class="form-control">
                            <option value="0">0 minutos - Iniciar Inmediatamente (Sin espera)</option>
                            <option value="5">5 minutos de aviso previo</option>
                            <option value="15" selected>15 minutos de aviso previo (Recomendado)</option>
                            <option value="30">30 minutos de aviso previo</option>
                            <option value="60">1 hora de aviso previo</option>
                            <option value="120">2 horas de aviso previo</option>
                        </select>
                        <small class="form-text text-muted">Durante este tiempo los jugadores verán el aviso y cuenta regresiva en game.php para alistar flotas.</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="duration_hours"><strong>Tiempo de Culminación (Horas de Duración):</strong></label>
                        <input type="number" name="duration_hours" id="duration_hours" class="form-control" value="24" min="1" max="168" required>
                        <small class="form-text text-muted">Tiempo que dura la batalla una vez que inicia la fortaleza.</small>
                    </div>

                    <div class="form-group col-md-4">
                        <label for="containers_per_winner"><strong>Contenedores por Ganador:</strong></label>
                        <input type="number" name="containers_per_winner" id="containers_per_winner" class="form-control" value="50" min="1" required>
                        <small class="form-text text-muted">Contenedores para cada participante de la estocada final.</small>
                    </div>

                    <div class="form-group col-md-4">
                        <label for="antimatter_per_winner"><strong>Antimateria por Ganador:</strong></label>
                        <input type="number" name="antimatter_per_winner" id="antimatter_per_winner" class="form-control" value="100" min="0" required>
                        <small class="form-text text-muted">Antimateria para cada participante de la estocada final.</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="min_system"><strong>Sistema Solar Mínimo:</strong></label>
                        <input type="number" name="min_system" id="min_system" class="form-control" value="1" min="1" max="400" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="max_system"><strong>Sistema Solar Máximo:</strong></label>
                        <input type="number" name="max_system" id="max_system" class="form-control" value="200" min="1" max="400" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="loot_metal"><strong>Botín Metal (40% Server: {$default_loot_metal_fmt}):</strong></label>
                        <input type="number" name="loot_metal" id="loot_metal" class="form-control" value="{$default_loot_metal}" min="0" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="loot_crystal"><strong>Botín Cristal (40% Server: {$default_loot_crystal_fmt}):</strong></label>
                        <input type="number" name="loot_crystal" id="loot_crystal" class="form-control" value="{$default_loot_crystal}" min="0" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="loot_deuterium"><strong>Botín Deuterio (40% Server: {$default_loot_deuterium_fmt}):</strong></label>
                        <input type="number" name="loot_deuterium" id="loot_deuterium" class="form-control" value="{$default_loot_deuterium}" min="0" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-success btn-lg btn-block mt-3" {if $status.active || $status.scheduled}disabled{/if}>
                    🚀 {if $status.active}Ya hay un evento en curso{elseif $status.scheduled}Ya hay un evento programado para iniciar{else}Programar / Iniciar Fortaleza Ancestral{/if}
                </button>
            </form>
        </div>
    </div>

    <!-- CARD 3: PREVISUALIZACIÓN DE UNIDADES PROYECTADAS -->
    <div class="card mb-4">
        <h5 class="card-header">📊 Previsualización de Composición al 60% de Budget</h5>
        <div class="card-body">
            <p>Con el gasto militar actual del servidor, la Fortaleza al 60% se compondrá exactamente de las siguientes unidades:</p>
            <div class="table-responsive">
                <table class="table table-dark table-striped table-bordered text-center">
                    <thead>
                        <tr>
                            <th>Unidad</th>
                            <th>ID</th>
                            <th>Costo Unitario (Metal)</th>
                            <th>% Asignado</th>
                            <th>Cantidad Proyectada</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$preview_list key=id item=u}
                        <tr>
                            <td><strong>{$u.name}</strong></td>
                            <td>{$id}</td>
                            <td>{$u.cost|number_format} Metal</td>
                            <td>{$u.ratio}</td>
                            <td><span class="badge badge-warning" style="font-size:14px;">{$u.amount|number_format}</span></td>
                        </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</main>
{/block}

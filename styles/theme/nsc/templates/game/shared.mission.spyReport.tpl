<div class="spy-report-card spyRaport">
	<!-- Encabezado Táctico del Informe -->
	<div class="spy-header">
		<div class="spy-planet-avatar-wrapper">
			<img src="{$dpath}planeten/small/{if $targetPlanet.planet_type == 3}s_mond.jpg{else}s_{$targetPlanet.image}.jpg{/if}" alt="{$targetPlanet.name}" class="spy-planet-avatar">
		</div>
		<div class="spy-header-info">
			<div class="spy-title-row">
				<a href="game.php?page=galaxy&amp;galaxy={$targetPlanet.galaxy}&amp;system={$targetPlanet.system}" class="spy-planet-link" title="{$LNG.spy_btn_galaxy}">
					{$targetPlanet.name} <span class="spy-coords">[{$targetPlanet.galaxy}:{$targetPlanet.system}:{$targetPlanet.planet}]</span>
				</a>
			</div>
			<div class="spy-meta-row">
				<span class="spy-meta-player">
					<svg class="spy-svg" viewBox="0 0 24 24" width="13" height="13"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
					{$LNG.gl_player|default:'Jugador'}: <strong>{$targetUser.username|default:'Desconocido'}</strong>{if !empty($targetUser.ally_name)} <span class="spy-ally-badge">[{$targetUser.ally_name}]</span>{/if}
				</span>
				<span class="spy-meta-date">
					<svg class="spy-svg" viewBox="0 0 24 24" width="13" height="13"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
					{$scanTime}
				</span>
			</div>
		</div>
		<div class="spy-counter-wrapper">
			{if $targetChance >= $spyChance}
				<div class="spy-counter-pill pill-danger">
					<svg class="spy-svg" viewBox="0 0 24 24" width="14" height="14"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>
					{$LNG.sys_mess_spy_destroyed}
				</div>
			{else}
				<div class="spy-counter-pill pill-success">
					<svg class="spy-svg" viewBox="0 0 24 24" width="14" height="14"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm1 17.93V18a2 2 0 0 0-2-2h-1v-2h2a1 1 0 0 0 1-1V9a2 2 0 0 0-2-2h-2V5.07A8 8 0 0 1 20 12a7.9 7.9 0 0 1-7 7.93zM4 12a8 8 0 0 1 6-7.75V7h1a1 1 0 0 1 1 1v1h-2a2 2 0 0 0-2 2v2H6a2 2 0 0 0-2-2zm1.07 3A7.93 7.93 0 0 1 4 12h2a2 2 0 0 1 2 2v1h1v3a1 1 0 0 1-1 1H7a7.9 7.9 0 0 1-1.93-4z"/></svg>
					{sprintf($LNG.spy_probes_safe, $targetChance)}
				</div>
			{/if}
		</div>
	</div>

	<!-- Sección de Recursos y Saqueo Estimado -->
	{if isset($spyData[900])}
	<div class="spy-section spy-section-resources">
		<div class="spy-section-head">
			<span class="spy-section-title">
				<img src="{$dpath}img/resources/901f.png" class="spy-head-ico" alt=""> {$LNG.tech.900}
			</span>
		</div>
		<div class="spy-resources-grid">
			<div class="spy-res-chip res-metal">
				<img src="{$dpath}img/resources/901f.png" alt="Metal">
				<div class="spy-res-data">
					<div class="spy-res-label">{$LNG.tech.901}</div>
					<div class="spy-res-val">{$spyData[900][901]|number}</div>
				</div>
			</div>
			<div class="spy-res-chip res-crystal">
				<img src="{$dpath}img/resources/902f.png" alt="Cristal">
				<div class="spy-res-data">
					<div class="spy-res-label">{$LNG.tech.902}</div>
					<div class="spy-res-val">{$spyData[900][902]|number}</div>
				</div>
			</div>
			<div class="spy-res-chip res-deut">
				<img src="{$dpath}img/resources/903f.png" alt="Deuterio">
				<div class="spy-res-data">
					<div class="spy-res-label">{$LNG.tech.903}</div>
					<div class="spy-res-val">{$spyData[900][903]|number}</div>
				</div>
			</div>
			<div class="spy-res-chip res-energy">
				<img src="{$dpath}img/resources/911f.png" alt="Energía">
				<div class="spy-res-data">
					<div class="spy-res-label">{$LNG.tech.911}</div>
					<div class="spy-res-val">{$spyData[900][911]|number}</div>
				</div>
			</div>
		</div>

		{if isset($loot) && $loot.total > 0}
		<!-- Panel de Inteligencia de Saqueo -->
		<div class="spy-loot-panel">
			<div class="spy-loot-head">
				<span class="spy-loot-title">
					<svg class="spy-svg" viewBox="0 0 24 24" width="14" height="14"><path d="M20 6h-4V4c0-1.11-.89-2-2-2h-4c-1.11 0-2 .89-2 2v2H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-6 0h-4V4h4v2zM4 19V8h16v11H4z"/><circle cx="12" cy="13" r="2"/></svg>
					{$LNG.spy_loot_title}
				</span>
				<span class="spy-loot-sum">Botín Total: <strong>{$loot.total|number}</strong></span>
			</div>
			<div class="spy-loot-body">
				<div class="spy-loot-breakdown">
					<span class="loot-tag metal-tag">Metal: {$loot.metal|number}</span>
					<span class="loot-tag crystal-tag">Cristal: {$loot.crystal|number}</span>
					<span class="loot-tag deut-tag">Deuterio: {$loot.deuterium|number}</span>
				</div>
				<div class="spy-loot-transports">
					<span class="spy-cargo-chip" title="{$LNG.spy_small_cargo}">
						<img src="{$dpath}gebaeude/202.gif" alt="PT"> <strong>{$loot.smallCargos|number}</strong> {$LNG.spy_small_cargo}
					</span>
					<span class="spy-cargo-chip" title="{$LNG.spy_large_cargo}">
						<img src="{$dpath}gebaeude/203.gif" alt="GT"> <strong>{$loot.largeCargos|number}</strong> {$LNG.spy_large_cargo}
					</span>
				</div>
			</div>
		</div>
		{/if}
	</div>
	{/if}

	<!-- Categorías Tácticas de Unidades y Estructuras -->
	{$spyCategories = [
		200 => ['key' => 'fleet', 'title' => $LNG.tech.200, 'icon' => '207.gif', 'empty' => $LNG.spy_no_fleet, 'color' => '#00e5ff'],
		400 => ['key' => 'def', 'title' => $LNG.tech.400, 'icon' => '402.gif', 'empty' => $LNG.spy_no_def, 'color' => '#ffaa00'],
		0   => ['key' => 'build', 'title' => $LNG.tech.0, 'icon' => '1.gif', 'empty' => $LNG.spy_no_build, 'color' => '#00ff88'],
		100 => ['key' => 'tech', 'title' => $LNG.tech.100, 'icon' => '106.gif', 'empty' => $LNG.spy_no_tech, 'color' => '#cc88ff']
	]}

	{foreach $spyCategories as $Class => $cat}
		{if isset($spyData[$Class])}
			{$activeItems = 0}
			{foreach $spyData[$Class] as $elementID => $amount}
				{if $amount > 0}{$activeItems = $activeItems + 1}{/if}
			{/foreach}

			<div class="spy-section spy-section-{$cat.key}">
				<div class="spy-section-head">
					<span class="spy-section-title">
						<img src="{$dpath}gebaeude/{$cat.icon}" class="spy-head-ico" alt=""> {$cat.title}
					</span>
					<span class="spy-badge-count" style="border-color: {$cat.color}; color: {$cat.color};">
						{$activeItems} {if $activeItems == 1}tipo{else}tipos{/if}
					</span>
				</div>

				{if $activeItems > 0}
					<div class="spy-unit-grid">
						{foreach $spyData[$Class] as $elementID => $amount}
							{if $amount > 0}
							<div class="spy-unit-card">
								<div class="spy-unit-thumb">
									<img src="{$dpath}gebaeude/{$elementID}.gif" alt="{$LNG.tech.$elementID}">
								</div>
								<div class="spy-unit-info">
									<div class="spy-unit-name" title="{$LNG.tech.$elementID}">{$LNG.tech.$elementID}</div>
									<div class="spy-unit-num" style="color: {$cat.color};">{$amount|number}</div>
								</div>
							</div>
							{/if}
						{/foreach}
					</div>
				{else}
					<div class="spy-empty-alert">
						{$cat.empty}
					</div>
				{/if}
			</div>
		{elseif isset($spyLevels) && !$spyLevels[$cat.key]}
			<div class="spy-section spy-section-locked">
				<div class="spy-locked-bar">
					<span class="spy-locked-title">
						<svg class="spy-svg" viewBox="0 0 24 24" width="13" height="13"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
						{$cat.title}
					</span>
					<span class="spy-locked-desc">{$LNG.spy_locked_tech}</span>
				</div>
			</div>
		{/if}
	{/foreach}

	<!-- Barra Inferior de Acciones Tácticas Rápidas -->
	<div class="spy-actions-bar">
		<a href="game.php?page=fleetTable&amp;galaxy={$targetPlanet.galaxy}&amp;system={$targetPlanet.system}&amp;planet={$targetPlanet.planet}&amp;planettype={$targetPlanet.planet_type}&amp;target_mission=1" class="spy-btn spy-btn-attack">
			<svg class="spy-svg" viewBox="0 0 24 24" width="15" height="15"><path d="M19.7 4.3a1 1 0 0 0-1.4 0L14 8.6 15.4 10l4.3-4.3a1 1 0 0 0 0-1.4zM4.3 19.7a1 1 0 0 0 1.4 0L10 15.4 8.6 14l-4.3 4.3a1 1 0 0 0 0 1.4zm12.3-6.9l-1.4-1.4-8.8 8.8 1.4 1.4 8.8-8.8zm-7.6-7.6l1.4 1.4 8.8-8.8-1.4-1.4-8.8 8.8z"/></svg>
			{$LNG.type_mission_1}
		</a>
		{if $isBattleSim}
		<a href="game.php?page=battleSimulator{$simUrlParams}" class="spy-btn spy-btn-sim">
			<svg class="spy-svg" viewBox="0 0 24 24" width="15" height="15"><path d="M7 2v11h3v9l7-12h-4l4-8z"/></svg>
			{$LNG.fl_simulate}
		</a>
		{/if}
		<a href="game.php?page=galaxy&amp;galaxy={$targetPlanet.galaxy}&amp;system={$targetPlanet.system}" class="spy-btn spy-btn-galaxy">
			<svg class="spy-svg" viewBox="0 0 24 24" width="15" height="15"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm1 17.93V18a2 2 0 0 0-2-2h-1v-2h2a1 1 0 0 0 1-1V9a2 2 0 0 0-2-2h-2V5.07A8 8 0 0 1 20 12a7.9 7.9 0 0 1-7 7.93z"/></svg>
			{$LNG.spy_btn_galaxy}
		</a>
	</div>
</div>
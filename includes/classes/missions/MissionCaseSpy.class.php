<?php

/*
 * ╔══╗╔══╗╔╗──╔╗╔═══╗╔══╗╔╗─╔╗╔╗╔╗──╔╗╔══╗╔══╗╔══╗
 * ║╔═╝║╔╗║║║──║║║╔═╗║║╔╗║║╚═╝║║║║║─╔╝║╚═╗║║╔═╝╚═╗║
 * ║║──║║║║║╚╗╔╝║║╚═╝║║╚╝║║╔╗─║║╚╝║─╚╗║╔═╝║║╚═╗──║║
 * ║║──║║║║║╔╗╔╗║║╔══╝║╔╗║║║╚╗║╚═╗║──║║╚═╗║║╔╗║──║║
 * ║╚═╗║╚╝║║║╚╝║║║║───║║║║║║─║║─╔╝║──║║╔═╝║║╚╝║──║║
 * ╚══╝╚══╝╚╝──╚╝╚╝───╚╝╚╝╚╝─╚╝─╚═╝──╚╝╚══╝╚══╝──╚╝
 *
 * @author Tsvira Yaroslav <https://github.com/Yaro2709>
 * @info ***
 * @link https://github.com/Yaro2709/New-Star
 * @Basis 2Moons: XG-Project v2.8.0
 * @Basis New-Star: 2Moons v1.8.0
 */

class MissionCaseSpy extends MissionFunctions implements Mission
{
		
	function __construct($Fleet)
	{
		$this->_fleet	= $Fleet;
	}
	
	function TargetEvent()
	{
		global $pricelist, $reslist, $resource, $USER;

		$db				= Database::get();

		$sql			= 'SELECT * FROM %%USERS%% WHERE id = :userId;';
		$senderUser		= $db->selectSingle($sql, array(
			':userId'	=> $this->_fleet['fleet_owner']
		));

		$targetUser		= $db->selectSingle($sql, array(
			':userId'	=> $this->_fleet['fleet_target_owner']
		));

		$sql			= 'SELECT * FROM %%PLANETS%% WHERE id = :planetId;';
		$targetPlanet	= $db->selectSingle($sql, array(
			':planetId'	=> $this->_fleet['fleet_end_id']
		));

		$sql				= 'SELECT name FROM %%PLANETS%% WHERE id = :planetId;';
		$senderPlanetName	= $db->selectSingle($sql, array(
			':planetId'	=> $this->_fleet['fleet_start_id']
		), 'name');

		$LNG			= $this->getLanguage($senderUser['lang']);

		$senderUser['factor']	= getFactors($senderUser, 'basic', $this->_fleet['fleet_start_time']);
		$targetUser['factor']	= getFactors($targetUser, 'basic', $this->_fleet['fleet_start_time']);

		$planetUpdater 						= new ResourceUpdate();
		list($targetUser, $targetPlanet)	= $planetUpdater->CalcResource($targetUser, $targetPlanet, true, $this->_fleet['fleet_start_time']);

		$sql	= 'SELECT * FROM %%FLEETS%%
		WHERE fleet_end_id 		= :planetId
		AND fleet_mission 		= 5
		AND fleet_start_time 	<= :time
		AND fleet_end_stay 		>= :time;';

		$targetStayFleets	= $db->select($sql, array(
			':planetId'	=> $this->_fleet['fleet_end_id'],
			':time'		=> TIMESTAMP,
		));

		foreach($targetStayFleets as $fleetRow)
		{
			$fleetData	= FleetFunctions::unserialize($fleetRow['fleet_array']);
			foreach($fleetData as $shipId => $shipAmount)
			{
				$targetPlanet[$resource[$shipId]]	+= $shipAmount;
			}
		}
		
		$fleetAmount	= $this->_fleet['fleet_amount'] * (1 + $senderUser['factor']['SpyPower']);

		$senderSpyTech	= max($senderUser['spy_tech'], 1);
		$targetSpyTech	= max($targetUser['spy_tech'], 1);

		$techDifference	= abs($senderSpyTech - $targetSpyTech);
		$MinAmount		= ($senderSpyTech > $targetSpyTech ? -1 : 1) * pow($techDifference * SPY_DIFFENCE_FACTOR, 2);
		$SpyFleet		= $fleetAmount >= $MinAmount;
		$SpyDef			= $fleetAmount >= $MinAmount + 1 * SPY_VIEW_FACTOR;
		$SpyBuild		= $fleetAmount >= $MinAmount + 3 * SPY_VIEW_FACTOR;
		$SpyTechno		= $fleetAmount >= $MinAmount + 5 * SPY_VIEW_FACTOR;
			

		$classIDs[900]	= array_merge($reslist['resstype'][1], $reslist['resstype'][2]);
				
		if($SpyFleet) 
		{
			$classIDs[200]	= $reslist['fleet'];
		}
		
		if($SpyDef) 
		{
			$classIDs[400]	= array_merge($reslist['defense'], $reslist['missile']);
		}
		
		if($SpyBuild) 
		{
			$classIDs[0]	= $reslist['build'];
		}
		
		if($SpyTechno) 
		{
			$classIDs[100]	= $reslist['tech'];
		}
		
		$targetChance 	= mt_rand(0, min(($fleetAmount/4) * ($targetSpyTech / $senderSpyTech), 100));
		$spyChance  	= mt_rand(0, 100);
		$spyData		= array();

		foreach($classIDs as $classID => $elementIDs)
		{
			foreach($elementIDs as $elementID)
			{
				if(isset($targetUser[$resource[$elementID]]))
				{
					$spyData[$classID][$elementID]	= $targetUser[$resource[$elementID]];
				}
				else 
				{
					$spyData[$classID][$elementID]	= $targetPlanet[$resource[$elementID]];
				}
			}
		
			if($senderUser['spyMessagesMode'] == 1)
			{
				$spyData[$classID]	= array_filter($spyData[$classID]);
			}
		}
		
		// I'm use template class here, because i want to exclude HTML in PHP.
		
		require_once 'includes/classes/class.template.php';
		
		$template	= new template;
		
		$template->caching		= false;
		$template->compile_id	= $senderUser['lang'];
		$template->loadFilter('output', 'trimwhitespace');
		list($tplDir)	= $template->getTemplateDir();
		$template->setTemplateDir($tplDir.'game/');

		// Cálculo de saqueo táctico (50% de recursos detectados)
		$lootMetal		= floor(max(0, (float)$targetPlanet['metal']) * 0.5);
		$lootCrystal	= floor(max(0, (float)$targetPlanet['crystal']) * 0.5);
		$lootDeut		= floor(max(0, (float)$targetPlanet['deuterium']) * 0.5);
		$totalLoot		= $lootMetal + $lootCrystal + $lootDeut;

		$smallCargoCap	= !empty($pricelist[202]['capacity']) ? (int)$pricelist[202]['capacity'] : 5000;
		$largeCargoCap	= !empty($pricelist[203]['capacity']) ? (int)$pricelist[203]['capacity'] : 25000;

		$lootData		= array(
			'metal'			=> $lootMetal,
			'crystal'		=> $lootCrystal,
			'deuterium'		=> $lootDeut,
			'total'			=> $totalLoot,
			'smallCargos'	=> ceil($totalLoot / max(1, $smallCargoCap)),
			'largeCargos'	=> ceil($totalLoot / max(1, $largeCargoCap)),
		);

		// Filtrado limpio para el simulador de combate
		$simUrlParams	= '';
		if (!empty($spyData[100])) {
			foreach ($spyData[100] as $elementID => $amount) {
				if ($amount > 0) {
					$simUrlParams .= '&im['.$elementID.']='.$amount;
				}
			}
		}
		if (!empty($spyData[200])) {
			foreach ($spyData[200] as $elementID => $amount) {
				if ($amount > 0) {
					$simUrlParams .= '&im['.$elementID.']='.$amount;
				}
			}
		}
		if (!empty($spyData[400])) {
			foreach ($spyData[400] as $elementID => $amount) {
				if ($amount > 0) {
					$simUrlParams .= '&im['.$elementID.']='.$amount;
				}
			}
		}
		if (!empty($targetPlanet['metal']) && $targetPlanet['metal'] > 0) {
			$simUrlParams .= '&im[901]='.(float)$targetPlanet['metal'];
		}
		if (!empty($targetPlanet['crystal']) && $targetPlanet['crystal'] > 0) {
			$simUrlParams .= '&im[902]='.(float)$targetPlanet['crystal'];
		}
		if (!empty($targetPlanet['deuterium']) && $targetPlanet['deuterium'] > 0) {
			$simUrlParams .= '&im[903]='.(float)$targetPlanet['deuterium'];
		}

		$themeName = !empty($senderUser['dpath']) ? $senderUser['dpath'] : DEFAULT_THEME;
		$dpath     = './styles/theme/' . $themeName . '/';

		$template->assign_vars(array(
			'dpath'			=> $dpath,
			'spyData'		=> $spyData,
			'targetPlanet'	=> $targetPlanet,
			'targetUser'	=> $targetUser,
			'targetChance'	=> $targetChance,
			'spyChance'		=> $spyChance,
			'loot'			=> $lootData,
			'simUrlParams'	=> $simUrlParams,
			'spyLevels'		=> array(
				'fleet'	=> $SpyFleet,
				'def'	=> $SpyDef,
				'build'	=> $SpyBuild,
				'tech'	=> $SpyTechno,
			),
			'scanTime'		=> _date($LNG['php_tdformat'], $this->_fleet['fleet_end_time'], $senderUser['timezone'], $LNG),
			'isBattleSim'	=> ENABLE_SIMULATOR_LINK == true && isModuleAvailable(MODULE_SIMULATOR),
			'title'			=> sprintf($LNG['sys_mess_head'], $targetPlanet['name'], $targetPlanet['galaxy'], $targetPlanet['system'], $targetPlanet['planet'], _date($LNG['php_tdformat'], $this->_fleet['fleet_end_time'], $senderUser['timezone'], $LNG)),
		));
		
		$template->assign_vars(array(
			'LNG'			=> $LNG
		), false);
				
		$spyReport	= $template->fetch('shared.mission.spyReport.tpl');

		PlayerUtil::sendMessage($this->_fleet['fleet_owner'], 0, $LNG['sys_mess_qg'], 0, $LNG['sys_mess_spy_report'],
			$spyReport, $this->_fleet['fleet_start_time'], NULL, 1, $this->_fleet['fleet_universe']);
		
		// Hook for NovaRush Bot AI: save intel to bot_intel table
		if (file_exists('includes/classes/bot/Perception/IntelStore.class.php')) {
			require_once 'includes/classes/bot/Perception/IntelStore.class.php';
			$dbInst = Database::get();
			$isBot = $dbInst->selectSingle("SELECT bot_id FROM " . DB_PREFIX . "bots WHERE bot_id = :id AND is_active = 1;", array(':id' => (int)$this->_fleet['fleet_owner']));
			if (!empty($isBot)) {
				$bStore = new BotIntelStore((int)$this->_fleet['fleet_owner']);
				$bStore->saveIntel(
					(int)$this->_fleet['fleet_end_id'],
					(int)$this->_fleet['fleet_target_owner'],
					(int)$this->_fleet['fleet_end_galaxy'],
					(int)$this->_fleet['fleet_end_system'],
					(int)$this->_fleet['fleet_end_planet'],
					(int)$this->_fleet['fleet_end_type'],
					array(
						901 => (float)$targetPlanet['metal'],
						902 => (float)$targetPlanet['crystal'],
						903 => (float)$targetPlanet['deuterium']
					),
					$SpyFleet ? (!empty($spyData[200]) ? $spyData[200] : array()) : null,
					$SpyDef   ? (!empty($spyData[400]) ? $spyData[400] : array()) : null,
					$SpyBuild ? (!empty($spyData[0])   ? $spyData[0]   : array()) : null,
					$SpyTechno ? (!empty($spyData[100]) ? $spyData[100] : array()) : null
				);
			}
		}

		$LNG			= $this->getLanguage($targetUser['lang']);
		$targetMessage  = $LNG['sys_mess_spy_ennemyfleet'] ." ". $senderPlanetName;

		if($this->_fleet['fleet_start_type'] == 3)
		{
			$targetMessage .= $LNG['sys_mess_spy_report_moon'].' ';
		}

		$attackerCoords = array(
			'galaxy' => $this->_fleet['fleet_start_galaxy'],
			'system' => $this->_fleet['fleet_start_system'],
			'planet' => $this->_fleet['fleet_start_planet']
		);
		$targetInfo = array(
			'name'        => $targetPlanet['name'] ?? '',
			'galaxy'      => $this->_fleet['fleet_end_galaxy'],
			'system'      => $this->_fleet['fleet_end_system'],
			'planet'      => $this->_fleet['fleet_end_planet'],
			'planet_type' => $this->_fleet['fleet_end_type'],
			'image'       => $targetPlanet['image'] ?? 'normaltempplanet01'
		);

		$targetMessage = MessageTemplateHelper::buildSpyAlertCard($attackerCoords, $targetInfo, $LNG);

		PlayerUtil::sendMessage($this->_fleet['fleet_target_owner'], 0, $LNG['sys_mess_spy_control'], 0,
			$LNG['sys_mess_spy_activity'], $targetMessage, $this->_fleet['fleet_start_time'], NULL, 1, $this->_fleet['fleet_universe']);

		if ($targetChance >= $spyChance)
		{
			$config		= Config::get($this->_fleet['fleet_universe']);
			$whereCol	= $this->_fleet['fleet_end_type'] == 3 ? "id_luna" : "id";

			$sql		= 'UPDATE %%PLANETS%% SET
			der_metal	= der_metal + :metal,
			der_crystal = der_crystal + :crystal
			WHERE '.$whereCol.' = :planetId;';

			$db->update($sql, array(
				':metal'	=> $fleetAmount * $pricelist[210]['cost'][901] * $config->Fleet_Cdr / 100,
				':crystal'	=> $fleetAmount * $pricelist[210]['cost'][902] * $config->Fleet_Cdr / 100,
				':planetId'	=> $this->_fleet['fleet_end_id']
			));

			$this->KillFleet();
		}
		else
		{
			$this->setState(FLEET_RETURN);
			$this->SaveFleet();
		}
	}
	
	function EndStayEvent()
	{
		return;
	}
	
	function ReturnEvent()
	{	
		$this->RestoreFleet();
	}
}

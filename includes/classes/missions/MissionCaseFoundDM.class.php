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

class MissionCaseFoundDM extends MissionFunctions implements Mission
{
	const CHANCE = 30; 
	const CHANCE_SHIP = 1; 
	const MAX_CHANCE = 100; 
		
	function __construct($Fleet)
	{
		$this->_fleet	= $Fleet;
	}
	
	function TargetEvent()
	{
		$this->setState(FLEET_HOLD);
		$this->SaveFleet();
	}
	
	function EndStayEvent()
	{
		global $pricelist, $reslist, $resource;
        
		$LNG	= $this->getLanguage(NULL, $this->_fleet['fleet_owner']);
		$config	= Config::get($this->_fleet['fleet_universe']);

		$fleetArray = FleetFunctions::unserialize($this->_fleet['fleet_array']);
		$fleetValueMSE = 0.0;

		foreach ($fleetArray as $shipId => $shipAmount)
		{
			$cost = isset($pricelist[$shipId]['cost']) ? $pricelist[$shipId]['cost'] : array();
			$m = isset($cost[901]) ? (float)$cost[901] : 0.0;
			$c = isset($cost[902]) ? (float)$cost[902] : 0.0;
			$d = isset($cost[903]) ? (float)$cost[903] : 0.0;
			$fleetValueMSE += $shipAmount * PremiumEconomy::mse($m, $c, $d);
		}

		$startTime = !empty($this->_fleet['start_time']) ? (float)$this->_fleet['start_time'] : (float)$this->_fleet['fleet_start_time'];
		$endTime   = (float)$this->_fleet['fleet_end_time'];
		$h = max(0.25, min(8.0, ($endTime - $startTime) / 3600.0));

		$prs = max(1.0, PremiumEconomy::get('prs', 1.0));
		$fleetFactor = 1.0 + min(1.0, log10(1.0 + ($fleetValueMSE / $prs)));

		$ownerId = (int) $this->_fleet['fleet_owner'];
		$n = PremiumEconomy::dailyCount($ownerId, 'founddm');
		$decay = max(0.2, pow(PremiumEconomy::get('fdm_decay', 0.9), max(0, $n - 20)));

		$chance	= mt_rand(0, 100);

		if ($chance <= min(self::MAX_CHANCE, (self::CHANCE + $this->_fleet['fleet_amount'] * self::CHANCE_SHIP))) {
			$db = Database::get();
			$targetUser = $db->selectSingle("SELECT * FROM %%USERS%% WHERE id = :userId;", array(':userId' => $ownerId));

			$eventType     = 'nothing';
			$narrativeText = '';
			$rewardData    = array();

			if (!empty($targetUser)) {
				if (mt_rand(1, 100) <= 70) {
					$dm = (int) floor(mt_rand(50, 90) * $h * $fleetFactor * $decay);
					PremiumEconomy::credit($targetUser, 921, $dm, 'founddm', 0, "h=$h;n=$n");
					$eventType     = 'darkmatter';
					$narrativeText = $LNG['sys_expe_found_dm_' . mt_rand(1, 3) . '_' . mt_rand(1, 2)];
					$rewardData['darkmatter'] = $dm;
				} else {
					$am = (int) floor(mt_rand(5, 9) * $h * $fleetFactor * $decay);
					PremiumEconomy::credit($targetUser, 922, $am, 'founddm', 0, "h=$h;n=$n");
					$eventType     = 'darkmatter';
					$narrativeText = $LNG['sys_expe_found_am_' . mt_rand(1, 3)] ?? 'Se han obtenido partículas de antimateria.';
					$rewardData['darkmatter'] = $am;
				}
				PremiumEconomy::dailyIncrement($ownerId, 'founddm');

				global $USER;
				if (isset($USER['id']) && $USER['id'] == $ownerId) {
					$USER = $targetUser;
				}
			} else {
				$eventType     = 'nothing';
				$narrativeText = $LNG['sys_expe_nothing_' . mt_rand(1, 9)];
			}
		} else {
			$eventType     = 'nothing';
			$narrativeText = $LNG['sys_expe_nothing_' . mt_rand(1, 9)];
		}
		$this->setState(FLEET_RETURN);
		$this->SaveFleet();

		$fleetCoords = array(
			'galaxy' => $this->_fleet['fleet_end_galaxy'],
			'system' => $this->_fleet['fleet_end_system']
		);

		$Message = MessageTemplateHelper::buildExpeditionCard($eventType, $narrativeText, $rewardData, $fleetCoords, $LNG);

		PlayerUtil::sendMessage($this->_fleet['fleet_owner'], 0, $LNG['sys_mess_tower'], 15,
			$LNG['sys_expe_report'], $Message, $this->_fleet['fleet_end_stay'], NULL, 1, $this->_fleet['fleet_universe']);
	}
	
	function ReturnEvent()
	{
		$LNG = $this->getLanguage(NULL, $this->_fleet['fleet_owner']);
		
		$sql = 'SELECT name FROM %%PLANETS%% WHERE id = :planetId;';
		$planetName = Database::get()->selectSingle($sql, array(
			':planetId' => $this->_fleet['fleet_start_id'],
		), 'name');

		$origin = array(
			'name'   => 'Investigación MO',
			'galaxy' => $this->_fleet['fleet_end_galaxy'],
			'system' => $this->_fleet['fleet_end_system'],
			'planet' => $this->_fleet['fleet_end_planet']
		);
		$target = array(
			'name'   => $planetName ?: 'Planeta',
			'galaxy' => $this->_fleet['fleet_start_galaxy'],
			'system' => $this->_fleet['fleet_start_system'],
			'planet' => $this->_fleet['fleet_start_planet']
		);
		$resources = array(
			921 => $this->_fleet['fleet_resource_darkmatter']
		);

		if ($this->_fleet['fleet_resource_darkmatter'] > 0) {
			$this->UpdateFleet('fleet_array', '220,0;');
		}

		$Message = MessageTemplateHelper::buildLogisticsCard(
			'return',
			$LNG['sys_mess_fleetback'],
			$origin,
			$target,
			$resources,
			$LNG
		);

		PlayerUtil::sendMessage($this->_fleet['fleet_owner'], 0, $LNG['sys_mess_tower'], 15, $LNG['sys_mess_fleetback'],
			$Message, $this->_fleet['fleet_end_time'], NULL, 1, $this->_fleet['fleet_universe']);
		$this->RestoreFleet();
	}
}
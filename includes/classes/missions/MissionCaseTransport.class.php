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

class MissionCaseTransport extends MissionFunctions implements Mission
{
	function __construct($Fleet)
	{
		$this->_fleet	= $Fleet;
	}

	function TargetEvent()
	{
		$sql = 'SELECT id, name FROM %%PLANETS%% WHERE `id` = :planetId;';

		$startPlanetRow	= Database::get()->selectSingle($sql, array(
			':planetId'	=> $this->_fleet['fleet_start_id']
		));
		$startPlanetName = !empty($startPlanetRow['name']) ? $startPlanetRow['name'] : 'Origen';

		$targetPlanetRow	= Database::get()->selectSingle($sql, array(
			':planetId'	=> $this->_fleet['fleet_end_id']
		));
		$targetPlanetName = !empty($targetPlanetRow['name']) ? $targetPlanetRow['name'] : 'Destino';

		$LNG			= $this->getLanguage(NULL, $this->_fleet['fleet_owner']);


		/**
		 * If target exists, deploy resources.
		 * If it is a destroyed moon, avoid to call StoreGoodsToPlanet()
		 */
		if (!empty($targetPlanetRow)) {
			$origin = array(
				'name'        => $startPlanetName ?: 'Origen',
				'galaxy'      => $this->_fleet['fleet_start_galaxy'],
				'system'      => $this->_fleet['fleet_start_system'],
				'planet'      => $this->_fleet['fleet_start_planet'],
				'planet_type' => $this->_fleet['fleet_start_type']
			);
			$target = array(
				'name'        => $targetPlanetName ?: 'Destino',
				'galaxy'      => $this->_fleet['fleet_end_galaxy'],
				'system'      => $this->_fleet['fleet_end_system'],
				'planet'      => $this->_fleet['fleet_end_planet'],
				'planet_type' => $this->_fleet['fleet_end_type']
			);
			$resources = array(
				901 => $this->_fleet['fleet_resource_metal'],
				902 => $this->_fleet['fleet_resource_crystal'],
				903 => $this->_fleet['fleet_resource_deuterium']
			);

			$Message = MessageTemplateHelper::buildLogisticsCard(
				'transport_owner',
				$LNG['sys_mess_transport'],
				$origin,
				$target,
				$resources,
				$LNG
			);

			PlayerUtil::sendMessage(
				$this->_fleet['fleet_owner'],
				0,
				$LNG['sys_mess_tower'],
				5,
				$LNG['sys_mess_transport'],
				$Message,
				$this->_fleet['fleet_start_time'],
				NULL,
				1,
				$this->_fleet['fleet_universe']
			);

			if ($this->_fleet['fleet_target_owner'] != $this->_fleet['fleet_owner']) {
				$LNGTarget = $this->getLanguage(NULL, $this->_fleet['fleet_target_owner']);
				$MessageTarget = MessageTemplateHelper::buildLogisticsCard(
					'transport_target',
					$LNGTarget['sys_mess_transport'],
					$origin,
					$target,
					$resources,
					$LNGTarget
				);

				PlayerUtil::sendMessage(
					$this->_fleet['fleet_target_owner'],
					0,
					$LNGTarget['sys_mess_tower'],
					5,
					$LNGTarget['sys_mess_transport'],
					$MessageTarget,
					$this->_fleet['fleet_start_time'],
					NULL,
					1,
					$this->_fleet['fleet_universe']
				);
			}

			$this->StoreGoodsToPlanet();
		}

		/**
		 * Check if returning planet exists.
		 * If is a player destroyed moon, redirect the fleet to the main planet.
		 */
		if (empty($startPlanetRow)) {
			$originUser = Database::get()->selectSingle("SELECT id_planet, galaxy, system, planet FROM %%USERS%% WHERE id = :id", array(
				':id'	=> $this->_fleet['fleet_owner']
			));

			$this->UpdateFleet('fleet_start_id', $originUser['id_planet']);
			$this->UpdateFleet('fleet_start_galaxy', $originUser['galaxy']);
			$this->UpdateFleet('fleet_start_system', $originUser['system']);
			$this->UpdateFleet('fleet_start_planet', $originUser['planet']);
			$this->UpdateFleet('fleet_start_type', 1);
		}

		$this->setState(FLEET_RETURN);
		$this->SaveFleet();
	}

	function EndStayEvent()
	{
		return;
	}

	function ReturnEvent()
	{
		$LNG		= $this->getLanguage(NULL, $this->_fleet['fleet_owner']);
		$sql		= 'SELECT name FROM %%PLANETS%% WHERE id = :planetId;';
		$planetName	= Database::get()->selectSingle($sql, array(
			':planetId'	=> $this->_fleet['fleet_start_id'],
		), 'name');

		$origin = array(
			'name'        => 'Destino',
			'galaxy'      => $this->_fleet['fleet_end_galaxy'],
			'system'      => $this->_fleet['fleet_end_system'],
			'planet'      => $this->_fleet['fleet_end_planet'],
			'planet_type' => $this->_fleet['fleet_end_type']
		);
		$target = array(
			'name'        => $planetName ?: 'Planeta',
			'galaxy'      => $this->_fleet['fleet_start_galaxy'],
			'system'      => $this->_fleet['fleet_start_system'],
			'planet'      => $this->_fleet['fleet_start_planet'],
			'planet_type' => $this->_fleet['fleet_start_type']
		);
		$resources = array();

		$Message = MessageTemplateHelper::buildLogisticsCard(
			'return',
			$LNG['sys_mess_fleetback'],
			$origin,
			$target,
			$resources,
			$LNG
		);

		PlayerUtil::sendMessage(
			$this->_fleet['fleet_owner'],
			0,
			$LNG['sys_mess_tower'],
			4,
			$LNG['sys_mess_fleetback'],
			$Message,
			$this->_fleet['fleet_end_time'],
			NULL,
			1,
			$this->_fleet['fleet_universe']
		);

		$this->RestoreFleet();
	}
}

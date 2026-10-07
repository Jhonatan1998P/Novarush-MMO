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

class MissionCaseACS extends MissionFunctions implements Mission
{
		
	function __construct($Fleet)
	{
		$this->_fleet	= $Fleet;
	}
	
	function TargetEvent()
	{
		$this->setState(FLEET_RETURN);
		$this->SaveFleet();
		return;
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
			'name'        => 'Ataque SAC',
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
		$resources = array(
			901 => $this->_fleet['fleet_resource_metal'],
			902 => $this->_fleet['fleet_resource_crystal'],
			903 => $this->_fleet['fleet_resource_deuterium']
		);

		$Message = MessageTemplateHelper::buildLogisticsCard(
			'return',
			$LNG['sys_mess_fleetback'],
			$origin,
			$target,
			$resources,
			$LNG
		);

		PlayerUtil::sendMessage($this->_fleet['fleet_owner'], 0, $LNG['sys_mess_tower'], 4, $LNG['sys_mess_fleetback'],
			$Message, $this->_fleet['fleet_end_time'], NULL, 1, $this->_fleet['fleet_universe']);
            
		$this->RestoreFleet();
	}
}

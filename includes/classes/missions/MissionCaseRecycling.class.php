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

class MissionCaseRecycling extends MissionFunctions implements Mission
{
	function __construct($Fleet)
	{
		$this->_fleet	= $Fleet;
	}
	
	function TargetEvent()
	{	
		global $pricelist, $resource;
		
		$resourceIDs	= array(901, 902, 903, 921);
		$debrisIDs		= array(901, 902);
		$resQuery		= array();
		$collectQuery	= array();
		
		$collectedGoods = array();
		foreach($debrisIDs as $debrisID)
		{
			$collectedGoods[$debrisID] = 0;
			$resQuery[]	= 'der_'.$resource[$debrisID];
		}

		$sql	= 'SELECT '.implode(',', $resQuery).', ('.implode(' + ', $resQuery).') as total
		FROM %%PLANETS%% WHERE id = :planetId FOR UPDATE;';

		$targetData	= Database::get()->selectSingle($sql, array(
			':planetId'	=> $this->_fleet['fleet_end_id']
		));

		if(!empty($targetData['total']))
		{
			$sql				= 'SELECT * FROM %%USERS%% WHERE id = :userId;';
			$targetUser			= Database::get()->selectSingle($sql, array(
				':userId'	=> $this->_fleet['fleet_owner']
			));

			$targetUserFactors	= getFactors($targetUser);
			$shipStorageFactor	= 1 + $targetUserFactors['ShipStorage'];
		
			// Get fleet capacity
			$fleetData			= FleetFunctions::unserialize($this->_fleet['fleet_array']);

			$recyclerStorage	= 0;
			$otherFleetStorage	= 0;

			foreach ($fleetData as $shipId => $shipAmount)
			{
				if ($shipId == 209 ||  $shipId == 219)
				{
					$recyclerStorage   += $pricelist[$shipId]['capacity'] * $shipAmount;
				}
				else
				{
					$otherFleetStorage += $pricelist[$shipId]['capacity'] * $shipAmount;
				}
			}
			
			$recyclerStorage	*= $shipStorageFactor;
			$otherFleetStorage	*= $shipStorageFactor;

			$incomingGoods		= 0;
			foreach($resourceIDs as $resourceID)
			{
				$incomingGoods	+= $this->_fleet['fleet_resource_'.$resource[$resourceID]];
			}

			$totalStorage = $recyclerStorage + min(0, $otherFleetStorage - $incomingGoods);

			$param	= array(
				':planetId'	=> $this->_fleet['fleet_end_id']
			);

			// fast way
			$collectFactor	= min(1, $totalStorage / $targetData['total']);
			foreach($debrisIDs as $debrisID)
			{
				$fleetColName	= 'fleet_resource_'.$resource[$debrisID];
				$debrisColName	= 'der_'.$resource[$debrisID];

				$collectedGoods[$debrisID]			= ceil($targetData[$debrisColName] * $collectFactor);
				$collectQuery[]						= $debrisColName.' = GREATEST(0, '.$debrisColName.' - :'.$resource[$debrisID].')';
				$param[':'.$resource[$debrisID]]	= $collectedGoods[$debrisID];

				$this->UpdateFleet($fleetColName, $this->_fleet[$fleetColName] + $collectedGoods[$debrisID]);
			}

			$sql	= 'UPDATE %%PLANETS%% SET '.implode(',', $collectQuery).' WHERE id = :planetId;';

			Database::get()->update($sql, $param);
		}
		
		$LNG = $this->getLanguage(NULL, $this->_fleet['fleet_owner']);
		
		$targetCoords = array(
			'galaxy' => $this->_fleet['fleet_end_galaxy'],
			'system' => $this->_fleet['fleet_end_system'],
			'planet' => $this->_fleet['fleet_end_planet']
		);

		$Message = MessageTemplateHelper::buildRecyclingCard(
			$targetCoords,
			$collectedGoods[901] ?? 0,
			$collectedGoods[902] ?? 0,
			$LNG
		);

		PlayerUtil::sendMessage($this->_fleet['fleet_owner'], 0, $LNG['sys_mess_tower'], 5,
			$LNG['sys_recy_report'], $Message, $this->_fleet['fleet_start_time'], NULL, 1, $this->_fleet['fleet_universe']);

		$this->setState(FLEET_RETURN);
		$this->SaveFleet();
	}
	
	function EndStayEvent()
	{
		return;
	}
	
	function ReturnEvent()
	{
		$LNG = $this->getLanguage(NULL, $this->_fleet['fleet_owner']);

		$sql = 'SELECT name FROM %%PLANETS%% WHERE id = :planetId;';
		$planetName = Database::get()->selectSingle($sql, array(
			':planetId' => $this->_fleet['fleet_start_id'],
		), 'name');

		$origin = array(
			'name'        => 'Campo de Escombros',
			'galaxy'      => $this->_fleet['fleet_end_galaxy'],
			'system'      => $this->_fleet['fleet_end_system'],
			'planet'      => $this->_fleet['fleet_end_planet'],
			'planet_type' => 2
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
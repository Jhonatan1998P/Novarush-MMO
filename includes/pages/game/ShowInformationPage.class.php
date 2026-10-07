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

class ShowInformationPage extends AbstractGamePage
{
	public static $requireModule = MODULE_INFORMATION;

	protected $disableEcoSystem = true;

	function __construct()
	{
		parent::__construct();
	}

	static function getNextJumpWaitTime($lastTime)
	{
		return $lastTime + Config::get()->gate_wait_time;
	}

	public function sendFleet()
	{
		global $PLANET, $USER, $resource, $LNG, $reslist;

		$db = Database::get();

		$NextJumpTime = self::getNextJumpWaitTime($PLANET['last_jump_time']);

		if (TIMESTAMP < $NextJumpTime)
		{
			$this->sendJSON(array(
				'message'	=> $LNG['in_jump_gate_already_used'].' '.pretty_time($NextJumpTime - TIMESTAMP),
				'error'		=> true
			));
		}

		$TargetPlanet = HTTP::_GP('jmpto', (int) $PLANET['id']);

		$sql = "SELECT id, last_jump_time FROM %%PLANETS%% WHERE id = :targetID AND id_owner = :userID AND sprungtor > 0;";
		$TargetGate = $db->selectSingle($sql, array(
			':targetID' => $TargetPlanet,
			':userID'   => $USER['id']
		));

		if (!isset($TargetGate) || $TargetPlanet == $PLANET['id'])
		{
			$this->sendJSON(array(
				'message' => $LNG['in_jump_gate_doesnt_have_one'],
				'error' => true
			));
		}

		$NextJumpTime   = self::getNextJumpWaitTime($TargetGate['last_jump_time']);

		if (TIMESTAMP < $NextJumpTime)
		{
			$this->sendJSON(array(
				'message' => $LNG['in_jump_gate_not_ready_target'].' '.pretty_time($NextJumpTime - TIMESTAMP),
				'error' => true
			));
		}

		$ShipArray		= array();
		$SubQueryOri	= "";
		$SubQueryDes	= "";
		$Ships			= HTTP::_GP('ship', array());
		
		foreach($reslist['fleet'] as $Ship)
		{
			if(!isset($Ships[$Ship]) || $Ship == 212)
				continue;

			$ShipArray[$Ship]	= max(0, min($Ships[$Ship], $PLANET[$resource[$Ship]]));

			if(empty($ShipArray[$Ship]))
				continue;

			$SubQueryOri 		.= $resource[$Ship]." = ".$resource[$Ship]." - ".$ShipArray[$Ship].", ";
			$SubQueryDes 		.= $resource[$Ship]." = ".$resource[$Ship]." + ".$ShipArray[$Ship].", ";
			$PLANET[$resource[$Ship]] -= $ShipArray[$Ship];
		}

		if (empty($SubQueryOri))
		{
			$this->sendJSON(array(
				'message' => $LNG['in_jump_gate_error_data'],
				'error' => true
			));
		}

		$array_merge =  array(':planetID' => $PLANET['id'],':jumptime' => TIMESTAMP);
		$sql  = "UPDATE %%PLANETS%% SET ".$SubQueryOri." `last_jump_time` = :jumptime WHERE id = :planetID;";
		$db->update($sql,$array_merge);

		$sql  = "UPDATE %%PLANETS%% SET ".$SubQueryDes." `last_jump_time` = :jumptime WHERE id = :targetID;";
		$db->update($sql, array(
			':targetID' => $TargetPlanet,
			':jumptime' => TIMESTAMP,
		));

		$PLANET['last_jump_time'] 	= TIMESTAMP;
		$NextJumpTime	= self::getNextJumpWaitTime($PLANET['last_jump_time']);
		$this->sendJSON(array(
			'message' => sprintf($LNG['in_jump_gate_done'], pretty_time($NextJumpTime - TIMESTAMP)),
			'error' => false
		));
	}

	private function getAvailableFleets()
	{
		global $reslist, $resource, $PLANET;

		$fleetList  = array();

		foreach($reslist['fleet'] as $Ship)
		{
			if ($Ship == 212 || $PLANET[$resource[$Ship]] <= 0)
				continue;

			$fleetList[$Ship]	= $PLANET[$resource[$Ship]];
		}

		return $fleetList;
	}

	public function destroyMissiles()
	{
		global $resource, $PLANET;

		$db = Database::get();

		$Missle	= HTTP::_GP('missile', array());
		$PLANET[$resource[502]]	-= max(0, min($Missle[502], $PLANET[$resource[502]]));
		$PLANET[$resource[503]]	-= max(0, min($Missle[503], $PLANET[$resource[503]]));

		$sql = "UPDATE %%PLANETS%% SET ".$resource[502]." = :resource502Val, ".$resource[503]." = :resource503Val WHERE id = :planetID;";
		$db->update($sql, array(
			':resource502Val'   => $PLANET[$resource[502]],
			':resource503Val'   => $PLANET[$resource[503]],
			':planetID'         => $PLANET['id']
		));

		$this->sendJSON(array($PLANET[$resource[502]], $PLANET[$resource[503]]));
	}

	private function getTargetGates()
	{
		global $resource, $USER, $PLANET;

		$db = Database::get();

		$Order = $USER['planet_sort_order'] == 1 ? "DESC" : "ASC" ;
		$Sort  = $USER['planet_sort'];

		switch($Sort) {
			case 1:
				$OrderBy	= "galaxy, system, planet, planet_type ". $Order;
				break;
			case 2:
				$OrderBy	= "name ". $Order;
				break;
			default:
				$OrderBy	= "id ". $Order;
				break;
		}

		$sql = "SELECT id, name, galaxy, system, planet, last_jump_time, ".$resource[43]." FROM %%PLANETS%% WHERE id != :planetID AND id_owner = :userID AND planet_type = '3' AND ".$resource[43]." > 0 ORDER BY :order;";
		$moonResult = $db->select($sql, array(
			':planetID'         => $PLANET['id'],
			':userID'           => $USER['id'],
			':order'            => $OrderBy
		));

		$moonList	= array();

		foreach($moonResult as $moonRow) {
			$NextJumpTime				= self::getNextJumpWaitTime($moonRow['last_jump_time']);
			$moonList[$moonRow['id']]	= '['.$moonRow['galaxy'].':'.$moonRow['system'].':'.$moonRow['planet'].'] '.$moonRow['name'].(TIMESTAMP < $NextJumpTime ? ' ('.pretty_time($NextJumpTime - TIMESTAMP).')':'');
		}

		return $moonList;
	}

	public function show()
	{
		global $USER, $PLANET, $LNG, $resource, $pricelist, $reslist, $CombatCaps, $ProdGrid, $requeriments;

		$elementID 	= HTTP::_GP('id', 0);

		$this->setWindow('popup');
		$this->initTemplate();

		$productionTable	= array();
		$FleetInfo			= array();
		$MissileList		= array();
		$gateData			= array();

		$CurrentLevel		= 0;

		$ressIDs			= array_merge(array(), $reslist['resstype'][1], $reslist['resstype'][2]);

		if(in_array($elementID, $reslist['prod']) && in_array($elementID, $reslist['build']))
		{
			/* Data for eval */
			$BuildTemp          = $PLANET['temp_max'];
			$BuildLevelFactor	= $PLANET[$resource[$elementID].'_porcent'];

			$CurrentLevel		= $PLANET[$resource[$elementID]];
			$BuildStartLvl   	= max($CurrentLevel - 2, 0);
			for($BuildLevel = $BuildStartLvl; $BuildLevel < $BuildStartLvl + 15; $BuildLevel++)
			{
				foreach($ressIDs as $ID)
				{

					if(!isset($ProdGrid[$elementID]['production'][$ID]))
						continue;

					$Production	= eval(ResourceUpdate::getProd($ProdGrid[$elementID]['production'][$ID]));

					if(in_array($ID, $reslist['resstype'][2]))
					{
						$Production	*= Config::get()->energySpeed;
					}
					else
					{
						$Production	*= Config::get()->resource_multiplier;
					}

					$productionTable['production'][$BuildLevel][$ID]	= $Production;
				}
			}

			$productionTable['usedResource']	= array_keys($productionTable['production'][$BuildStartLvl]);
		}
		elseif(in_array($elementID, $reslist['storage']))
		{
			$CurrentLevel		= $PLANET[$resource[$elementID]];
			$BuildStartLvl   	= max($CurrentLevel - 2, 0);

			for($BuildLevel = $BuildStartLvl; $BuildLevel < $BuildStartLvl + 15; $BuildLevel++)
			{
				foreach($ressIDs as $ID)
				{
					if(!isset($ProdGrid[$elementID]['storage'][$ID]))
						continue;

					$production = round(eval(ResourceUpdate::getProd($ProdGrid[$elementID]['storage'][$ID])));
					$production *= Config::get()->storage_multiplier;

					$productionTable['storage'][$BuildLevel][$ID]	= $production;
				}
			}

			$productionTable['usedResource']	= array_keys($productionTable['storage'][$BuildStartLvl]);
		}
		elseif(in_array($elementID, $reslist['fleet']) || in_array($elementID, $reslist['defense']))
		{
			$isFleet = in_array($elementID, $reslist['fleet']);
			$isDefense = in_array($elementID, $reslist['defense']);

			// Armamento y tipos de ataque
			$gunsList = array();
			$gunRaw = isset($CombatCaps[$elementID]['type_gun']) ? $CombatCaps[$elementID]['type_gun'] : 'notype';
			$totalAttack = isset($CombatCaps[$elementID]['attack']) ? (int)$CombatCaps[$elementID]['attack'] : 0;
			$FleetGun = array();

			if ($gunRaw === 'none') {
				$FleetGun = '';
			} elseif ($gunRaw === 'notype' || empty($gunRaw)) {
				$FleetGun = 'notype';
				$gunsList[] = array(
					'type'    => 'notype',
					'name'    => isset($LNG['in_attack_pt']) ? $LNG['in_attack_pt'] : 'Armamento Balístico Estándar',
					'desc'    => isset($LNG['in_attack_pt_desc']) ? $LNG['in_attack_pt_desc'] : 'Fuego cinético equilibrado convencional',
					'attack'  => $totalAttack,
					'percent' => 100,
					'icon'    => 'notype.png',
					'color'   => '#94a3b8',
				);
			} else {
				$type_gun = explode(';', $gunRaw);
				foreach ($type_gun as $gunItem) {
					if (empty($gunItem)) continue;
					$gunParts = explode(',', $gunItem);
					$gClass = trim($gunParts[0]);
					$gPercent = isset($gunParts[1]) ? (int)$gunParts[1] : 100;
					$gAttack = round($totalAttack * $gPercent / 100);

					$FleetGun[$gClass]['attack'] = $gAttack;

					$gName = isset($LNG['in_attack_'.$gClass]) ? $LNG['in_attack_'.$gClass] : ucfirst($gClass);
					$gDesc = isset($LNG['in_attack_'.$gClass.'_desc']) ? $LNG['in_attack_'.$gClass.'_desc'] : '';
					$gColor = ($gClass === 'laser') ? '#ef4444' : (($gClass === 'ion') ? '#3b82f6' : (($gClass === 'plasma') ? '#f97316' : '#a855f7'));
					$gIcon = ($gClass === 'notype') ? 'notype.png' : $gClass . '.jpg';

					$gunsList[] = array(
						'type'    => $gClass,
						'name'    => $gName,
						'desc'    => $gDesc,
						'attack'  => $gAttack,
						'percent' => $gPercent,
						'icon'    => $gIcon,
						'color'   => $gColor,
					);
				}
			}

			// Integridad estructural y puntos de casco
			$costMetal = isset($pricelist[$elementID]['cost'][901]) ? (int)$pricelist[$elementID]['cost'][901] : 0;
			$costCrystal = isset($pricelist[$elementID]['cost'][902]) ? (int)$pricelist[$elementID]['cost'][902] : 0;
			$structuralIntegrity = $costMetal + $costCrystal;
			if ($structuralIntegrity <= 0 && isset($CombatCaps[$elementID]['defend'])) {
				$structuralIntegrity = (int)$CombatCaps[$elementID]['defend'] * 10;
			}
			$hullPoints = max(1, round($structuralIntegrity / 10));

			// Escudos y clases
			$shieldPower = isset($CombatCaps[$elementID]['shield']) ? (int)$CombatCaps[$elementID]['shield'] : 0;
			$shieldClass = isset($CombatCaps[$elementID]['type_shield']) ? str_replace('s_', '', $CombatCaps[$elementID]['type_shield']) : 'none';
			$defendClass = isset($CombatCaps[$elementID]['type_defend']) ? str_replace('d_', '', $CombatCaps[$elementID]['type_defend']) : 'light';

			// Propulsión (para naves)
			$techVal = isset($pricelist[$elementID]['tech']) ? (int)$pricelist[$elementID]['tech'] : 0;
			$engineTechId = 115;
			$engineUpgradeNote = '';
			if ($techVal === 1) {
				$engineTechId = 115;
			} elseif ($techVal === 4) {
				$engineTechId = 115;
				$engineUpgradeNote = '(Mejora a Motor de Impulso en Nv. 5)';
			} elseif ($techVal === 2) {
				$engineTechId = 117;
			} elseif ($techVal === 5) {
				$engineTechId = 117;
				$engineUpgradeNote = '(Mejora a Motor Hiperespacial en Nv. 8)';
			} elseif ($techVal === 3) {
				$engineTechId = 118;
			}

			$engineName = isset($LNG['tech'][$engineTechId]) ? $LNG['tech'][$engineTechId] : 'Motor';

			// Fuego Rápido (Rapid Fire)
			$allCombatUnits = array_merge($reslist['fleet'], $reslist['defense']);
			$rapidfireTo = array();
			$rapidfireFrom = array();
			$rawRapidTo = array();
			$rawRapidFrom = array();

			// Fuego rápido CONTRA otros
			if (isset($CombatCaps[$elementID]['sd']) && is_array($CombatCaps[$elementID]['sd'])) {
				foreach ($CombatCaps[$elementID]['sd'] as $targetId => $shoots) {
					if ($shoots > 1 && in_array($targetId, $allCombatUnits)) {
						$rawRapidTo[$targetId] = $shoots;
						$rfProb = round((($shoots - 1) / $shoots) * 100, 1);
						$rapidfireTo[] = array(
							'id'     => $targetId,
							'name'   => isset($LNG['tech'][$targetId]) ? $LNG['tech'][$targetId] : "Unidad $targetId",
							'shoots' => $shoots,
							'prob'   => $rfProb,
						);
					}
				}
			}

			// Fuego rápido RECIBIDO DE otros
			foreach ($allCombatUnits as $attackerId) {
				if (isset($CombatCaps[$attackerId]['sd'][$elementID]) && $CombatCaps[$attackerId]['sd'][$elementID] > 1) {
					$shoots = $CombatCaps[$attackerId]['sd'][$elementID];
					$rawRapidFrom[$attackerId] = $shoots;
					$rfProb = round((($shoots - 1) / $shoots) * 100, 1);
					$rapidfireFrom[] = array(
						'id'     => $attackerId,
						'name'   => isset($LNG['tech'][$attackerId]) ? $LNG['tech'][$attackerId] : "Unidad $attackerId",
						'shoots' => $shoots,
						'prob'   => $rfProb,
					);
				}
			}

			// Costes de fabricación
			$costList = array();
			$costMeta = array(
				901 => array('key' => 'metal', 'name' => isset($LNG['tech'][901]) ? $LNG['tech'][901] : 'Metal'),
				902 => array('key' => 'crystal', 'name' => isset($LNG['tech'][902]) ? $LNG['tech'][902] : 'Cristal'),
				903 => array('key' => 'deuterium', 'name' => isset($LNG['tech'][903]) ? $LNG['tech'][903] : 'Deuterio'),
				911 => array('key' => 'energy', 'name' => isset($LNG['tech'][911]) ? $LNG['tech'][911] : 'Energía'),
				921 => array('key' => 'darkmatter', 'name' => isset($LNG['tech'][921]) ? $LNG['tech'][921] : 'Materia Oscura'),
			);
			foreach ($costMeta as $resId => $meta) {
				if (isset($pricelist[$elementID]['cost'][$resId]) && $pricelist[$elementID]['cost'][$resId] > 0) {
					$costList[] = array(
						'id'     => $resId,
						'key'    => $meta['key'],
						'name'   => $meta['name'],
						'amount' => $pricelist[$elementID]['cost'][$resId],
					);
				}
			}

			// Requisitos
			$reqList = array();
			if (isset($requeriments[$elementID]) && is_array($requeriments[$elementID])) {
				foreach ($requeriments[$elementID] as $reqId => $reqLevel) {
					$curLevel = 0;
					if (isset($resource[$reqId])) {
						$rk = $resource[$reqId];
						if (isset($PLANET[$rk])) {
							$curLevel = (int)$PLANET[$rk];
						} elseif (isset($USER[$rk])) {
							$curLevel = (int)$USER[$rk];
						}
					}
					$reqList[] = array(
						'id'       => $reqId,
						'name'     => isset($LNG['tech'][$reqId]) ? $LNG['tech'][$reqId] : "Tecnología $reqId",
						'required' => $reqLevel,
						'current'  => $curLevel,
						'met'      => ($curLevel >= $reqLevel),
					);
				}
			}

			$FleetInfo = array(
				'is_fleet'             => $isFleet,
				'is_defense'           => $isDefense,
				'total_attack'         => $totalAttack,
				'attack'               => $totalAttack,
				'guns_list'            => $gunsList,
				'structure'            => $structuralIntegrity,
				'hull'                 => $hullPoints,
				'shield'               => $shieldPower,
				'class_shield'         => $shieldClass,
				'class_defend'         => $defendClass,
				'shield_name'          => isset($LNG['in_shield_'.$shieldClass]) ? $LNG['in_shield_'.$shieldClass] : $shieldClass,
				'armor_name'           => isset($LNG['in_armor_'.$defendClass]) ? $LNG['in_armor_'.$defendClass] : $defendClass,
				'fleetgun'             => $FleetGun,
				'tech'                 => $techVal,
				'engine_tech_id'       => $engineTechId,
				'engine_name'          => $engineName,
				'engine_upgrade_note'  => $engineUpgradeNote,
				'speed1'               => isset($pricelist[$elementID]['speed']) ? $pricelist[$elementID]['speed'] : 0,
				'speed2'               => isset($pricelist[$elementID]['speed2']) ? $pricelist[$elementID]['speed2'] : 0,
				'consumption1'         => isset($pricelist[$elementID]['consumption']) ? $pricelist[$elementID]['consumption'] : 0,
				'consumption2'         => isset($pricelist[$elementID]['consumption2']) ? $pricelist[$elementID]['consumption2'] : 0,
				'capacity'             => isset($pricelist[$elementID]['capacity']) ? $pricelist[$elementID]['capacity'] : 0,
				'rapidfire_to'         => $rapidfireTo,
				'rapidfire_from'       => $rapidfireFrom,
				'costs'                => $costList,
				'requirements'         => $reqList,
				'info'                 => array(
					'class_defend'     => $defendClass,
					'class_shield'     => $shieldClass,
				),
				'rapidfire'            => array(
					'to'               => $rawRapidTo,
					'from'             => $rawRapidFrom,
				),
			);
		}

		if($elementID == 43 && $PLANET[$resource[43]] > 0)
		{
			$this->tplObj->loadscript('gate.js');
			$nextTime	= self::getNextJumpWaitTime($PLANET['last_jump_time']);
			$gateData	= array(
				'nextTime'	=> _date($LNG['php_tdformat'], $nextTime, $USER['timezone']),
				'restTime'	=> max(0, $nextTime - TIMESTAMP),
				'startLink'	=> $PLANET['name'].' '.strip_tags(BuildPlanetAddressLink($PLANET)),
				'gateList' 	=> $this->getTargetGates(),
				'fleetList'	=> $this->getAvailableFleets(),
			);
		}
		elseif($elementID == 44 && $PLANET[$resource[44]] > 0)
		{
			$MissileList	= array(
				502	=> $PLANET[$resource[502]],
				503	=> $PLANET[$resource[503]]
			);
		}

		$PremiumInfo = array();

		$premiumCategories = array(
			'artifact'    => array('ids' => $reslist['artifact'] ?? array(), 'name' => 'Artefacto Cósmico', 'icon' => 'star.png'),
			'premium'     => array('ids' => $reslist['premium'] ?? array(), 'name' => 'Paquete Premium', 'icon' => 'ico-account-premium.png'),
			'dmfunc'      => array('ids' => $reslist['dmfunc'] ?? array(), 'name' => 'Plano de Materia Oscura', 'icon' => 'tech.png'),
			'development' => array('ids' => $reslist['development'] ?? array(), 'name' => 'Desarrollo Innovador', 'icon' => 'development.png'),
			'bon'         => array('ids' => $reslist['bon'] ?? array(), 'name' => 'Contenedor de los Antiguos', 'icon' => 'storage.png'),
			'fair'        => array('ids' => $reslist['fair'] ?? array(), 'name' => 'Feria Galáctica', 'icon' => 'exchange_res.png'),
		);

		$matchedCategory = null;
		foreach ($premiumCategories as $catKey => $catData) {
			if (in_array($elementID, $catData['ids'])) {
				$matchedCategory = $catData;
				$matchedCategory['key'] = $catKey;
				break;
			}
		}

		if ($matchedCategory !== null) {
			$duration = isset($pricelist[$elementID]['time']) ? (int) $pricelist[$elementID]['time'] : 0;
			$durationText = '';
			if ($duration > 0) {
				$days = floor($duration / 86400);
				$hours = floor(($duration % 86400) / 3600);
				if ($days > 0 && $hours == 0) {
					$durationText = $days . ' ' . ($days == 1 ? 'día' : 'días');
				} elseif ($days > 0) {
					$durationText = $days . ' ' . ($days == 1 ? 'día' : 'días') . ' y ' . $hours . 'h';
				} elseif ($hours == 12) {
					$durationText = '12 horas (Mín. 1 día / 2 compras)';
				} else {
					$durationText = $hours . ' ' . ($hours == 1 ? 'hora' : 'horas');
				}
			} else {
				$durationText = 'Inmediato / Permanente';
			}

			$isActive = false;
			$timeLeftText = '';
			$resKey = isset($resource[$elementID]) ? $resource[$elementID] : null;
			if ($resKey && isset($USER[$resKey]) && $USER[$resKey] > TIMESTAMP) {
				$isActive = true;
				$timeLeftSec = $USER[$resKey] - TIMESTAMP;
				$daysLeft = floor($timeLeftSec / 86400);
				$hoursLeft = floor(($timeLeftSec % 86400) / 3600);
				$minutesLeft = floor(($timeLeftSec % 3600) / 60);
				if ($daysLeft > 0) {
					$timeLeftText = $daysLeft . 'd ' . $hoursLeft . 'h ' . $minutesLeft . 'm';
				} else {
					$timeLeftText = $hoursLeft . 'h ' . $minutesLeft . 'm';
				}
			}

			// Costes de adquisición
			$isMinTwo = in_array($matchedCategory['key'], array('artifact', 'premium', 'dmfunc', 'development'));
			$costList = array();
			if (in_array($elementID, array(2301, 2302, 2303)) && class_exists('PremiumEconomy')) {
				$H = PremiumEconomy::get('fair_hours', 6);
				$P = PremiumEconomy::indexedHourlyMSE($USER['id']);
				$n = PremiumEconomy::dailyCount($USER['id'], 'fair_res_' . $elementID);
				$mult = pow(PremiumEconomy::get('fair_growth', 1.25), $n);
				$resId = ($elementID == 2301) ? 901 : (($elementID == 2302) ? 902 : 903);
				$amount = ($elementID == 2301) ? ceil($H * $P * $mult) : (($elementID == 2302) ? ceil($H * $P * $mult / 2) : ceil($H * $P * $mult / 4));
				$costList[] = array(
					'id'         => $resId,
					'name'       => isset($LNG['tech'][$resId]) ? $LNG['tech'][$resId] : "Recurso $resId",
					'amount'     => $amount,
					'min_amount' => $amount,
					'is_min_two' => false,
				);
			} elseif (in_array($elementID, array(2307, 2308)) && class_exists('PremiumEconomy')) {
				$n = PremiumEconomy::dailyCount($USER['id'], 'fair_trade_' . $elementID);
				$mult = pow(1.25, $n);
				$resId = ($elementID == 2307) ? 924 : 922;
				$baseCost = ($elementID == 2307) ? 25 : 5000;
				$amount = (float) ceil($baseCost * $mult);
				$costList[] = array(
					'id'         => $resId,
					'name'       => isset($LNG['tech'][$resId]) ? $LNG['tech'][$resId] : "Recurso $resId",
					'amount'     => $amount,
					'min_amount' => $amount,
					'is_min_two' => false,
				);
			} else {
				$costResources = BuildFunctions::getElementPrice($USER, $PLANET, $elementID);
				foreach ($costResources as $resId => $amount) {
					$costList[] = array(
						'id'         => $resId,
						'name'       => isset($LNG['tech'][$resId]) ? $LNG['tech'][$resId] : "Recurso $resId",
						'amount'     => $amount,
						'min_amount' => $isMinTwo ? ($amount * 2) : $amount,
						'is_min_two' => $isMinTwo,
					);
				}
			}

			// Elementos o tecnologías beneficiadas
			$affectedTechs = array();
			if (in_array($elementID, array_merge($reslist['bon'] ?? array(), $reslist['fair'] ?? array()))) {
				$rawTechs = BuildFunctions::bonusElementList($elementID);
				foreach ($rawTechs as $tElId => $reqList) {
					foreach ($reqList as $reqId => $lvlData) {
						$affectedTechs[] = array(
							'id'    => $reqId,
							'name'  => isset($LNG['tech'][$reqId]) ? $LNG['tech'][$reqId] : "Tecnología $reqId",
							'count' => $lvlData['count'] ?? 1,
						);
					}
				}
			}

			$PremiumInfo = array(
				'is_premium'         => true,
				'category_key'       => $matchedCategory['key'],
				'category_name'      => $matchedCategory['name'],
				'category_icon'      => $matchedCategory['icon'],
				'duration_seconds'   => $duration,
				'duration_text'      => $durationText,
				'is_active'          => $isActive,
				'time_left_text'     => $timeLeftText,
				'max_level'          => isset($pricelist[$elementID]['max']) ? $pricelist[$elementID]['max'] : 1,
				'costs'              => $costList,
				'affected_techs'     => $affectedTechs,
			);
		}

		$this->assign(array(
			'elementID'			=> $elementID,
			'productionTable'	=> $productionTable,
			'CurrentLevel'		=> $CurrentLevel,
			'MissileList'		=> $MissileList,
			'Bonus'				=> BuildFunctions::getAvalibleBonus($elementID),
			'FleetInfo'			=> $FleetInfo,
			'gateData'			=> $gateData,
			'PremiumInfo'		=> $PremiumInfo,
		));

		$this->display('page.information.default.tpl');
	}
}

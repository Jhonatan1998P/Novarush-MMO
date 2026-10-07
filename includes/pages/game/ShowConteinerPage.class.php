<?php

/*
 * ╔══╗╔══╗╔╗──╔╗╔═══╗╔══╗╔╗─╔╗╔╗╔╗──╔╗╔══╗╔══╗╔══╗
 * ║╔═╝║╔╗║║║──║║║╔═╗║║╔╗║║╚═╝║║║║║─╔╝║╚═╗║║╔═╝╚═╗║
 * ║║──║║║║║╚╗╔╝║║╚═╝║║╚╝║║╔╗─║║╚╝║─╚╗║╔═╝║║╚═╗──║║
 * ║║──║║║║║╔╗╔╗║║╔══╝║╔╗║║║╚╗║╚═╗║──║║╚═╗║║╔╗║──║║
 * ║╚═╗║╚╝║║║╚╝║║║║───║║║║║║─║║─╔╝║──║║╔═╝║║╚╝║──║║
 * ╚══╝╚══╝╚╝──╚╝╚╝───╚╝╚╝╚╝─╚╝─╚═╝──╚╝╚══╝╚══╝──╚╝
 *
 * @author Tsvira Yaroslav <https://github.com/Yaro2709> @@ Aurum79 aka Чук
 * @info ***
 * @link https://github.com/Yaro2709/New-Star
 * @Basis 2Moons: XG-Project v2.8.0
 * @Basis New-Star: 2Moons v1.8.0
 */

class ShowConteinerPage extends AbstractGamePage
{	
	public static $requireModule = MODULE_CONTAINER;

	function __construct() 
	{
		parent::__construct(); 
	}
	
	function open()
	{
		global $PLANET, $USER, $LNG, $resource, $reslist, $pricelist;
		
		$conts = (int) HTTP::_GP('conts', 1);	

		if($conts > 500){
			$this->printMessage(''.$LNG['cont_msg_limit'].'', true, array("game.php?page=conteiner", 3));	
			return;
		}
		if($conts < 1){
			$this->redirectTo("game.php?page=conteiner");
			return;
		}

		if (!PremiumEconomy::debit($USER, 924, $conts, 'container_open')) {
			$this->printMessage(''.$LNG['cont_not_cont_user'].'', true, array("game.php?page=conteiner", 3));
			return;
		}
		
		// Resources indexed to individual player production: V = ct_open_hours * effectiveMse
		$h = PremiumEconomy::get('ct_open_hours', 0.5);
		$prod = PremiumEconomy::playerHourlyProduction((int) $USER['id']);
		
		$mseM = $prod['metal'];
		$mseC = 2.0 * $prod['crystal'];
		$mseD = 4.0 * $prod['deuterium'];
		$totalMse = $mseM + $mseC + $mseD;
		
		if ($totalMse > 0) {
			$pctM = $mseM / $totalMse;
			$pctC = $mseC / $totalMse;
			$pctD = $mseD / $totalMse;
		} else {
			$pctM = 0.5;
			$pctC = 0.3;
			$pctD = 0.2;
		}
		
		$effectiveMse = max($totalMse, (float) PremiumEconomy::baseIncomeMSE());
		$V = $h * $effectiveMse;
		
		$metalPerCont   = (float) floor($V * $pctM);
		$crystalPerCont = (float) floor(($V * $pctC) * 0.5);
		$deutPerCont    = (float) floor(($V * $pctD) * 0.25);
		
		$totalMetal   = $metalPerCont * $conts;
		$totalCrystal = $crystalPerCont * $conts;
		$totalDeut    = $deutPerCont * $conts;
		
		$PLANET['metal']     += $totalMetal;
		$PLANET['crystal']   += $totalCrystal;
		$PLANET['deuterium'] += $totalDeut;
		
		$db = Database::get();
		$db->update("UPDATE %%PLANETS%% SET metal = metal + :m, crystal = crystal + :c, deuterium = deuterium + :d WHERE id = :pid;", array(
			':m'   => $totalMetal,
			':c'   => $totalCrystal,
			':d'   => $totalDeut,
			':pid' => $PLANET['id']
		));
		
		// 50 troops per container: 25 Light Fighters (202), 15 Heavy Fighters (203), 10 Cruisers (204)
		$troopUnits = array(
			202 => 25 * $conts,
			203 => 15 * $conts,
			204 => 10 * $conts,
		);
		
		foreach ($troopUnits as $shipId => $shipQty) {
			$col = $resource[$shipId];
			$PLANET[$col] += $shipQty;
			$db->update("UPDATE %%PLANETS%% SET {$col} = {$col} + :qty WHERE id = :pid;", array(
				':qty' => $shipQty,
				':pid' => $PLANET['id']
			));
		}
		
		// Container activity logging
		$sqlLog = "INSERT INTO %%CONT%% (id_owner, time, item, count, factor) VALUES (:uid, :time, :item, :cnt, 1);";
		$db->insert($sqlLog, array(':uid' => $USER['id'], ':time' => TIMESTAMP, ':item' => 901, ':cnt' => $totalMetal));
		$db->insert($sqlLog, array(':uid' => $USER['id'], ':time' => TIMESTAMP, ':item' => 902, ':cnt' => $totalCrystal));
		$db->insert($sqlLog, array(':uid' => $USER['id'], ':time' => TIMESTAMP, ':item' => 903, ':cnt' => $totalDeut));
		foreach ($troopUnits as $shipId => $shipQty) {
			$db->insert($sqlLog, array(':uid' => $USER['id'], ':time' => TIMESTAMP, ':item' => $shipId, ':cnt' => $shipQty));
		}
		
		$this->printMessage(''.$LNG['cont_open'].' '.$conts.'', true, array("game.php?page=conteiner", 3));
	} 
	
	public function show()
	{
		global $USER, $PLANET, $LNG;
        
		$limit = !empty($USER['container_set']) ? (int) $USER['container_set'] : 50;
		$sql = "SELECT * FROM %%CONT%% WHERE id_owner = :userId ORDER BY id DESC LIMIT ".$limit.";";
		$logs = Database::get()->select($sql, array(
			':userId' => $USER['id']
		));
        
		$sql = "SELECT COUNT(*) as count FROM %%CONT%% WHERE id_owner = :userId AND time > ".(TIMESTAMP-86400).";";
		$sum = Database::get()->selectSingle($sql, array(
			':userId' => $USER['id']
		));
        
		// Calculate estimated reward for 1 container based on player's current economy
		$h = PremiumEconomy::get('ct_open_hours', 0.5);
		$prod = PremiumEconomy::playerHourlyProduction((int) $USER['id']);
		
		$mseM = $prod['metal'];
		$mseC = 2.0 * $prod['crystal'];
		$mseD = 4.0 * $prod['deuterium'];
		$totalMse = $mseM + $mseC + $mseD;
		
		if ($totalMse > 0) {
			$pctM = $mseM / $totalMse;
			$pctC = $mseC / $totalMse;
			$pctD = $mseD / $totalMse;
		} else {
			$pctM = 0.5;
			$pctC = 0.3;
			$pctD = 0.2;
		}
		
		$effectiveMse = max($totalMse, (float) PremiumEconomy::baseIncomeMSE());
		$V = $h * $effectiveMse;
		
		$metalPerCont   = (float) floor($V * $pctM);
		$crystalPerCont = (float) floor(($V * $pctC) * 0.5);
		$deutPerCont    = (float) floor(($V * $pctD) * 0.25);
		
		// Group logs by timestamp for clean presentation
		$groupedLogs = array();
		foreach ($logs as $row) {
			$timeKey = $row['time'];
			if (!isset($groupedLogs[$timeKey])) {
				$groupedLogs[$timeKey] = array(
					'time'      => _date($LNG['php_tdformat'], $row['time'], $USER['timezone']),
					'timestamp' => $row['time'],
					'resources' => array(),
					'fleet'     => array(),
				);
			}
			$itemInfo = array(
				'item'   => $row['item'],
				'name'   => isset($LNG['tech'][$row['item']]) ? $LNG['tech'][$row['item']] : 'Item #'.$row['item'],
				'count'  => $row['count'],
				'factor' => $row['factor']
			);
			if (in_array((int)$row['item'], array(901, 902, 903))) {
				$groupedLogs[$timeKey]['resources'][] = $itemInfo;
			} else {
				$groupedLogs[$timeKey]['fleet'][] = $itemInfo;
			}
		}

		$this->tplObj->assign_vars(array(
			'conteiner'     => (int) $USER['container'],
			'logs'          => $logs,
			'groupedLogs'   => $groupedLogs,
			'sum'           => (int) $sum['count'],
			'targetPlanet'  => htmlspecialchars($PLANET['name'], ENT_QUOTES, 'UTF-8'),
			'targetCoords'  => '[' . $PLANET['galaxy'] . ':' . $PLANET['system'] . ':' . $PLANET['planet'] . ']',
			'rewardPreview' => array(
				'metal'         => $metalPerCont,
				'crystal'       => $crystalPerCont,
				'deuterium'     => $deutPerCont,
				'lightFighters' => 25,
				'heavyFighters' => 15,
				'cruisers'      => 10,
				'hours'         => $h,
			),
		));
		$this->display('page.conteiner.default.tpl');
	}	
}
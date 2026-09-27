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

class ShowDetailsPage extends AbstractGamePage
{
	public static $requireModule = MODULE_DETAILS;

	function __construct() 
	{
		parent::__construct();
	}

	public function UpdateDetails($Element)
	{
		global $PLANET, $USER, $reslist, $resource, $pricelist, $LNG, $BonusElement;
		
		$base = PremiumEconomy::get('detail_base', 100);
		$growth = PremiumEconomy::get('detail_growth', 1.02);
		$maxLvl = (int) PremiumEconomy::get('detail_max', 100);
		$curLevel = (int) ($USER[$resource[$Element]] ?? 0);
		
		$reqAmount = (int) HTTP::_GP('amount', 1);
		$amount = max(1, min($reqAmount, $maxLvl - $curLevel));
		
		if ($curLevel >= $maxLvl || $amount <= 0) {
			$this->printMessage(''.$LNG['bd_maxlevel'].'', true, array('game.php?page=details', 2));
			return;
		}
		
		// Check required mineral/arsenal components
		if (isset($BonusElement[$Element])) {
			foreach ($BonusElement[$Element] as $ID => $Count) {
				$needed = abs($Count) * $amount;
				if (isset($PLANET[$resource[$ID]])) {
					if ($PLANET[$resource[$ID]] < $needed) {
						$this->printMessage(''.$LNG['bd_notres'].'', true, array('game.php?page=details', 2));
						return;
					}
				} elseif (isset($USER[$resource[$ID]])) {
					if ($USER[$resource[$ID]] < $needed) {
						$this->printMessage(''.$LNG['bd_notres'].'', true, array('game.php?page=details', 2));
						return;
					}
				}
			}
		}
		
		// Calculate total MO cost
		$totalCost = 0;
		for ($i = 0; $i < $amount; $i++) {
			$totalCost += (float) ceil($base * pow($growth, $curLevel + $i));
		}
		
		if (!PremiumEconomy::debit($USER, 921, $totalCost, 'detail_buy', $Element, "lvl={$curLevel};amt={$amount}")) {
			$this->printMessage(''.$LNG['bd_notres'].'', true, array('game.php?page=details', 2));
			return;
		}
		
		// Deduct minerals and arsenals
		$href = 'game.php?page=details';
		$bonus = 1;
		require_once('includes/subclasses/subclass.UpdateSqlBonusElementNole.php');
		
		// Increment detail level
		$USER[$resource[$Element]] += $amount;
		$db = Database::get();
		$db->update("UPDATE %%USERS%% SET {$resource[$Element]} = {$resource[$Element]} + :amt WHERE id = :u;", array(
			':amt' => $amount,
			':u'   => $USER['id']
		));
		
		$this->printMessage(''.$LNG['bd_buy_yes'].'', true, array('game.php?page=details', 2));
	}
	
	public function show()
	{
		global $USER, $PLANET, $resource, $reslist, $LNG, $pricelist, $requeriments;
		
		$updateID = HTTP::_GP('id', 0);
		$listDetails = explode(',', Config::get()->details_cron);
		
		if (!empty($updateID) && $_SERVER['REQUEST_METHOD'] === 'POST' && $USER['urlaubs_modus'] == 0)
		{
			if(in_array($updateID, $listDetails)) {
				$this->UpdateDetails($updateID);
			}
		}
		
		$this->tplObj->loadscript('officier.js');	
		
		$base = PremiumEconomy::get('detail_base', 100);
		$growth = PremiumEconomy::get('detail_growth', 1.02);
		$maxLvl = (int) PremiumEconomy::get('detail_max', 100);
		
		$detailsList = array();
		
		if(isModuleAvailable(MODULE_DETAILS)) 
		{
			foreach($listDetails as $Element)
			{
				$bonusElementList = BuildFunctions::bonusElementList($Element);
				$curLevel = (int) ($USER[$resource[$Element]] ?? 0);
				$costMO = (float) ceil($base * pow($growth, $curLevel));
				$costResources = array(921 => $costMO);
				
				$buyable      = ($curLevel < $maxLvl) && BuildFunctions::isElementBuyable($USER, $PLANET, $Element, $costResources);
				$costOverflow = BuildFunctions::getRestPrice($USER, $PLANET, $Element, $costResources);
				
				$detailsList[$Element] = array(
					'level'         => $curLevel,
					'maxLevel'      => max(0, $maxLvl - $curLevel),
					'factor'        => $growth,
					'costResources' => $costResources,
					'buyable'       => $buyable,
					'costOverflow'  => $costOverflow,
					'AllTech'       => $bonusElementList,
				);
			}
		}
		
		$detailsOverview = array();
		
		if(isModuleAvailable(MODULE_DETAILS))
		{
			foreach($reslist['details'] as $Element)
			{
				$elementBonus = BuildFunctions::getAvalibleBonus($Element);
				
				$detailsOverview[$Element] = array(
					'level'        => $USER[$resource[$Element]] ?? 0,
					'factor'       => $pricelist[$Element]['factor'],
					'elementBonus' => $elementBonus,
				);
			}
		}
		
		$sql = "SELECT nextTime FROM %%CRONJOBS%% WHERE cronjobID = :cronId;";
		$nextTime = Database::get()->selectSingle($sql, array(
			':cronId' => 9
		), 'nextTime');
		
		require_once 'includes/classes/Cronjob.class.php';
		
		$this->assign(array(	
			'detailsList'     => $detailsList,
			'detailsOverview' => $detailsOverview,
			'nextStatUpdate'  => max(0, $nextTime - TIMESTAMP),
			'stat_date'       => _date($LNG['php_tdformat'], Cronjob::getLastExecutionTime('details'), $USER['timezone']),
		));
		
		$this->display('page.details.default.tpl');
	}
}

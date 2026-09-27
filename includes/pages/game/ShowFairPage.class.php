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

class ShowFairPage extends AbstractGamePage
{
	public static $requireModule = MODULE_FAIR;

	function __construct() 
	{
		parent::__construct();
	}

	public function UpdateFair($Element)
	{
		global $PLANET, $USER, $reslist, $resource, $pricelist, $LNG, $BonusElement;
		
		$amount = max(1, min((int) ($pricelist[$Element]['max'] ?? 1), (int) HTTP::_GP('amount', 1)));
		
		if (in_array($Element, array(2301, 2302, 2303))) {
			if (TIMESTAMP <= $USER[$resource[$Element]]) {
				$this->printMessage(''.$LNG['bd_restart_no'].'', true, array('game.php?page=fair', 2));
				return;
			}
			
			$H = PremiumEconomy::get('fair_hours', 6);
			$P = PremiumEconomy::indexedHourlyMSE($USER['id']);
			$n = PremiumEconomy::dailyCount($USER['id'], 'fair_res');
			
			$totalCost = 0;
			$resId = 901;
			if ($Element == 2301) {
				$resId = 901; // Metal
				for ($i = 0; $i < $amount; $i++) {
					$mult = pow(PremiumEconomy::get('fair_growth', 1.25), $n + $i);
					$totalCost += (float) ceil($H * $P * $mult);
				}
			} elseif ($Element == 2302) {
				$resId = 902; // Cristal
				for ($i = 0; $i < $amount; $i++) {
					$mult = pow(PremiumEconomy::get('fair_growth', 1.25), $n + $i);
					$totalCost += (float) ceil($H * $P * $mult / 2);
				}
			} else {
				$resId = 903; // Deuterio
				for ($i = 0; $i < $amount; $i++) {
					$mult = pow(PremiumEconomy::get('fair_growth', 1.25), $n + $i);
					$totalCost += (float) ceil($H * $P * $mult / 4);
				}
			}
			
			$col = $resource[$resId];
			if ($PLANET[$col] < $totalCost) {
				$this->printMessage(''.$LNG['bd_notres'].'', true, array('game.php?page=fair', 2));
				return;
			}
			
			// Deduct from planet
			$PLANET[$col] -= $totalCost;
			$db = Database::get();
			$db->update("UPDATE %%PLANETS%% SET {$col} = {$col} - :cost WHERE id = :pid;", array(
				':cost' => $totalCost,
				':pid'  => $PLANET['id']
			));
			
			// Credit MO (1.000 MO per unit)
			$rewardMO = 1000 * $amount;
			PremiumEconomy::credit($USER, 921, $rewardMO, 'fair_res', $Element, "n={$n};amt={$amount}");
			
			// Increment daily counter for each conversion
			for ($k = 0; $k < $amount; $k++) {
				PremiumEconomy::dailyIncrement($USER['id'], 'fair_res');
			}
			
			// Cooldown
			$USER[$resource[$Element]] = max($USER[$resource[$Element]], TIMESTAMP) + ($pricelist[$Element]['time']) * $amount;
			$db->update("UPDATE %%USERS%% SET {$resource[$Element]} = :t WHERE id = :uid;", array(
				':t'   => $USER[$resource[$Element]],
				':uid' => $USER['id']
			));
			
			$this->printMessage(''.$LNG['bd_buy_yes'].'', true, array('game.php?page=fair', 2));
			return;
		}
		
		// Currency trades (2304-2308)
		if (TIMESTAMP <= $USER[$resource[$Element]]) {
			$this->printMessage(''.$LNG['bd_restart_no'].'', true, array('game.php?page=fair', 2));
			return;
		}
		
		$costResources = BuildFunctions::getElementPrice($USER, $PLANET, $Element);
		$costCur = 0;
		$costVal = 0;
		foreach ($costResources as $cCur => $cVal) {
			if ($cVal > 0) {
				$costCur = $cCur;
				$costVal = $cVal * $amount;
				break;
			}
		}
		
		if ($costCur > 0) {
			if (!PremiumEconomy::debit($USER, $costCur, $costVal, 'fair_trade', $Element, "amt={$amount}")) {
				$this->printMessage(''.$LNG['bd_notres'].'', true, array('game.php?page=fair', 2));
				return;
			}
		}
		
		if (isset($BonusElement[$Element])) {
			foreach ($BonusElement[$Element] as $rCur => $rVal) {
				PremiumEconomy::credit($USER, $rCur, $rVal * $amount, 'fair_trade', $Element, "amt={$amount}");
			}
		}
		
		$USER[$resource[$Element]] = max($USER[$resource[$Element]], TIMESTAMP) + ($pricelist[$Element]['time']) * $amount;
		$db = Database::get();
		$db->update("UPDATE %%USERS%% SET {$resource[$Element]} = :t WHERE id = :uid;", array(
			':t'   => $USER[$resource[$Element]],
			':uid' => $USER['id']
		));
		
		$this->printMessage(''.$LNG['bd_buy_yes'].'', true, array('game.php?page=fair', 2));
	}
	
	public function show()
	{
		global $USER, $PLANET, $resource, $reslist, $LNG, $pricelist, $requeriments;
		
		$updateID = HTTP::_GP('id', 0);
		
		if (!empty($updateID) && $_SERVER['REQUEST_METHOD'] === 'POST' && $USER['urlaubs_modus'] == 0)
		{
			if(in_array($updateID, $reslist['fair'])) {
				$this->UpdateFair($updateID);
			}
		}
		
		$this->tplObj->loadscript('officier.js');		
		
		$fairList = array();
		
		if(isModuleAvailable(MODULE_FAIR)) 
		{
			$H = PremiumEconomy::get('fair_hours', 6);
			$P = PremiumEconomy::indexedHourlyMSE($USER['id']);
			$n = PremiumEconomy::dailyCount($USER['id'], 'fair_res');
			$mult = pow(PremiumEconomy::get('fair_growth', 1.25), $n);
			
			foreach($reslist['fair'] as $Element)
			{
				if($USER[$resource[$Element]] > TIMESTAMP) {
					$this->tplObj->execscript("GetOfficerTime(".$Element.", ".($USER[$resource[$Element]] - TIMESTAMP).");");
				}
				$bonusElementList = BuildFunctions::bonusElementList($Element);
				
				if ($Element == 2301) {
					$costResources = array(901 => (float) ceil($H * $P * $mult));
				} elseif ($Element == 2302) {
					$costResources = array(902 => (float) ceil($H * $P * $mult / 2));
				} elseif ($Element == 2303) {
					$costResources = array(903 => (float) ceil($H * $P * $mult / 4));
				} else {
					$costResources = BuildFunctions::getElementPrice($USER, $PLANET, $Element);
				}
				
				$buyable       = BuildFunctions::isElementBuyable($USER, $PLANET, $Element, $costResources);
				$costOverflow  = BuildFunctions::getRestPrice($USER, $PLANET, $Element, $costResources);
				$elementBonus  = BuildFunctions::getAvalibleBonus($Element);
				
				$fairList[$Element] = array(
					'maxLevel'      => $pricelist[$Element]['max'],
					'timeLeft'      => max($USER[$resource[$Element]] - TIMESTAMP, 0),
					'costResources' => $costResources,
					'buyable'       => $buyable,
					'costOverflow'  => $costOverflow,
					'elementBonus'  => $elementBonus,
					'AllTech'       => $bonusElementList,
				);
			}
		}
		
		$this->assign(array(	
			'fairList' => $fairList,
		));
		
		$this->display('page.fair.default.tpl');
	}
}

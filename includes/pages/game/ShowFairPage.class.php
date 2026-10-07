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
			
			$dailyKey = 'fair_res_' . $Element;
			$dailyLimit = (int) PremiumEconomy::get('fair_daily_limit', 3);
			$n = PremiumEconomy::dailyCount($USER['id'], $dailyKey);
			
			if ($n >= $dailyLimit) {
				$this->printMessage("Has alcanzado el límite de {$dailyLimit} conversiones diarias para este recurso. Se reinicia a medianoche.", true, array('game.php?page=fair', 2));
				return;
			}
			
			$amount = min($amount, $dailyLimit - $n);
			
			$H = PremiumEconomy::get('fair_hours', 6);
			$P = PremiumEconomy::indexedHourlyMSE($USER['id']);
			
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
				PremiumEconomy::dailyIncrement($USER['id'], $dailyKey);
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
		
		// Intercambio de Contenedores <-> Antimateria (2307, 2308)
		if (in_array($Element, array(2307, 2308))) {
			$dailyKey = 'fair_trade_' . $Element;
			$dailyLimit = 3;
			$n = PremiumEconomy::dailyCount($USER['id'], $dailyKey);
			
			if ($n >= $dailyLimit) {
				$this->printMessage("Has alcanzado el límite de {$dailyLimit} compras diarias para esta oferta. Se reinicia a medianoche.", true, array('game.php?page=fair', 2));
				return;
			}
			
			$amount = max(1, min($amount, $dailyLimit - $n));
			
			$costCur = ($Element == 2307) ? 924 : 922; // 2307: 25 Contenedores -> 2500 Antimateria; 2308: 5000 Antimateria -> 25 Contenedores
			$baseCost = ($Element == 2307) ? 25 : 5000;
			$rewardCur = ($Element == 2307) ? 922 : 924;
			$baseReward = ($Element == 2307) ? 2500 : 25;
			
			$totalCost = 0;
			for ($i = 0; $i < $amount; $i++) {
				$mult = pow(1.25, $n + $i);
				$totalCost += (float) ceil($baseCost * $mult);
			}
			
			if (!PremiumEconomy::debit($USER, $costCur, $totalCost, 'fair_trade', $Element, "n={$n};amt={$amount}")) {
				$this->printMessage(''.$LNG['bd_notres'].'', true, array('game.php?page=fair', 2));
				return;
			}
			
			$totalReward = $baseReward * $amount;
			PremiumEconomy::credit($USER, $rewardCur, $totalReward, 'fair_trade', $Element, "amt={$amount}");
			
			for ($k = 0; $k < $amount; $k++) {
				PremiumEconomy::dailyIncrement($USER['id'], $dailyKey);
			}
			
			$this->printMessage(''.$LNG['bd_buy_yes'].'', true, array('game.php?page=fair', 2));
			return;
		}
		
		// Currency trades (2304-2306)
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
			$dailyLimit = (int) PremiumEconomy::get('fair_daily_limit', 3);
			
			foreach($reslist['fair'] as $Element)
			{
				if($USER[$resource[$Element]] > TIMESTAMP) {
					$this->tplObj->execscript("GetOfficerTime(".$Element.", ".($USER[$resource[$Element]] - TIMESTAMP).");");
				}
				$bonusElementList = BuildFunctions::bonusElementList($Element);
				
				$dailyUsed = null;
				$elementDailyLimit = null;
				$mult = 1.0;
				
				if (in_array($Element, array(2301, 2302, 2303))) {
					$n = PremiumEconomy::dailyCount($USER['id'], 'fair_res_' . $Element);
					$mult = pow(PremiumEconomy::get('fair_growth', 1.25), $n);
					
					if ($Element == 2301) {
						$costResources = array(901 => (float) ceil($H * $P * $mult));
					} elseif ($Element == 2302) {
						$costResources = array(902 => (float) ceil($H * $P * $mult / 2));
					} else {
						$costResources = array(903 => (float) ceil($H * $P * $mult / 4));
					}
					
					$remainingDaily = max(0, $dailyLimit - $n);
					$buyable = ($remainingDaily > 0) && BuildFunctions::isElementBuyable($USER, $PLANET, $Element, $costResources);
					$maxAllowed = min((int) ($pricelist[$Element]['max'] ?? 3), $remainingDaily);
					$dailyUsed = $n;
					$elementDailyLimit = $dailyLimit;
				} elseif (in_array($Element, array(2307, 2308))) {
					$dailyKey = 'fair_trade_' . $Element;
					$limitTrades = 3;
					$n = PremiumEconomy::dailyCount($USER['id'], $dailyKey);
					$mult = pow(1.25, $n);
					
					$costCur = ($Element == 2307) ? 924 : 922;
					$baseCost = ($Element == 2307) ? 25 : 5000;
					$costResources = array($costCur => (float) ceil($baseCost * $mult));
					
					$remainingDaily = max(0, $limitTrades - $n);
					$buyable = ($remainingDaily > 0) && BuildFunctions::isElementBuyable($USER, $PLANET, $Element, $costResources);
					$maxAllowed = min(1, $remainingDaily);
					$dailyUsed = $n;
					$elementDailyLimit = $limitTrades;
				} else {
					$costResources = BuildFunctions::getElementPrice($USER, $PLANET, $Element);
					$buyable       = BuildFunctions::isElementBuyable($USER, $PLANET, $Element, $costResources);
					$maxAllowed    = (int) ($pricelist[$Element]['max'] ?? 1);
				}
				
				$costOverflow  = BuildFunctions::getRestPrice($USER, $PLANET, $Element, $costResources);
				$elementBonus  = BuildFunctions::getAvalibleBonus($Element);
				
				$fairList[$Element] = array(
					'maxLevel'             => $maxAllowed,
					'timeLeft'             => in_array($Element, array(2307, 2308)) ? 0 : max($USER[$resource[$Element]] - TIMESTAMP, 0),
					'costResources'        => $costResources,
					'buyable'              => $buyable,
					'costOverflow'         => $costOverflow,
					'elementBonus'         => $elementBonus,
					'AllTech'              => $bonusElementList,
					'dailyUsed'            => $dailyUsed,
					'dailyLimit'           => $elementDailyLimit,
					'priceIncreasePercent' => round(($mult - 1) * 100),
				);
			}
		}
		
		$this->assign(array(	
			'fairList' => $fairList,
		));
		
		$this->display('page.fair.default.tpl');
	}
}

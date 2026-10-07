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

class ShowBandPage extends AbstractGamePage
{
	public static $requireModule = MODULE_BAND;

	function __construct() 
	{
		parent::__construct();
	}

	public function UpdateBand($Element)
	{
		$this->printMessage('La compra de mercenarios se encuentra actualmente desactivada.', true, array('game.php?page=overview', 3));
		return;
	}

	public function DisabledUpdateBand($Element)
	{
		global $PLANET, $USER, $reslist, $resource, $pricelist, $LNG, $BonusElement;
		
		$costAM = (float) PremiumEconomy::get('band_price_am', 100);
		$amount = max(1, min((int) ($pricelist[$Element]['max'] ?? 10), (int) HTTP::_GP('amount', 1)));
		$totalAM = $costAM * $amount;
		
		if (!PremiumEconomy::debit($USER, 922, $totalAM, 'band_buy', $Element, "amt={$amount}")) {
			$this->printMessage(''.$LNG['bd_notres'].'', true, array('game.php?page=band', 2));
			return;
		}
		
		$hours = PremiumEconomy::get('band_hours', 6);
		$prs = max((float) PremiumEconomy::get('prs', 0), (float) PremiumEconomy::baseIncomeMSE());
		$targetMSE = $hours * $prs;
		
		$basePackMSE = 0;
		if (isset($BonusElement[$Element])) {
			foreach ($BonusElement[$Element] as $uId => $uCnt) {
				$uMSE = PremiumEconomy::mse(
					$pricelist[$uId]['cost'][901] ?? 0,
					$pricelist[$uId]['cost'][902] ?? 0,
					$pricelist[$uId]['cost'][903] ?? 0
				);
				$basePackMSE += $uMSE * $uCnt;
			}
		}
		
		$scale = ($basePackMSE > 0) ? max((float) PremiumEconomy::get('pack_scale_min', 0.1), min((float) PremiumEconomy::get('pack_scale_max', 20.0), $targetMSE / $basePackMSE)) : 1.0;
		
		$db = Database::get();
		if (isset($BonusElement[$Element])) {
			foreach ($BonusElement[$Element] as $uId => $uCnt) {
				$scaledUnits = max(1, (int) round($uCnt * $scale)) * $amount;
				$col = $resource[$uId];
				$PLANET[$col] += $scaledUnits;
				$db->update("UPDATE %%PLANETS%% SET {$col} = {$col} + :qty WHERE id = :p;", array(
					':qty' => $scaledUnits,
					':p'   => $PLANET['id']
				));
			}
		}
		
		$USER[$resource[$Element]] += $amount;
		$db->update("UPDATE %%USERS%% SET {$resource[$Element]} = {$resource[$Element]} + :amt WHERE id = :u;", array(
			':amt' => $amount,
			':u'   => $USER['id']
		));
		
		$this->printMessage(''.$LNG['bd_buy_yes'].'', true, array('game.php?page=band', 2));
	}
	
	public function show()
	{
		global $USER, $PLANET, $resource, $reslist, $LNG, $pricelist, $requeriments, $BonusElement;
		
		$updateID = HTTP::_GP('id', 0);
		
		if (!empty($updateID) && $_SERVER['REQUEST_METHOD'] === 'POST' && $USER['urlaubs_modus'] == 0)
		{
			if(in_array($updateID, $reslist['band'])) {
				$this->UpdateBand($updateID);
			}
		}
		
		$this->tplObj->loadscript('officier.js');		
		
		$bandList = array();
		
		if(isModuleAvailable(MODULE_BAND)) 
		{
			$hours = PremiumEconomy::get('band_hours', 6);
			$prs = max((float) PremiumEconomy::get('prs', 0), (float) PremiumEconomy::baseIncomeMSE());
			$targetMSE = $hours * $prs;
			$costAM = (float) PremiumEconomy::get('band_price_am', 100);
			
			foreach($reslist['band'] as $Element)
			{
				$basePackMSE = 0;
				if (isset($BonusElement[$Element])) {
					foreach ($BonusElement[$Element] as $uId => $uCnt) {
						$uMSE = PremiumEconomy::mse(
							$pricelist[$uId]['cost'][901] ?? 0,
							$pricelist[$uId]['cost'][902] ?? 0,
							$pricelist[$uId]['cost'][903] ?? 0
						);
						$basePackMSE += $uMSE * $uCnt;
					}
				}
				
				$scale = ($basePackMSE > 0) ? max((float) PremiumEconomy::get('pack_scale_min', 0.1), min((float) PremiumEconomy::get('pack_scale_max', 20.0), $targetMSE / $basePackMSE)) : 1.0;
				
				$bonusElementList = array();
				if (isset($BonusElement[$Element])) {
					foreach ($BonusElement[$Element] as $uId => $uCnt) {
						$scaledUnits = max(1, (int) round($uCnt * $scale));
						$bonusElementList[$uId] = array(
							'count' => $scaledUnits,
							'own'   => $PLANET[$resource[$uId]] ?? 0
						);
					}
				}
				
				$costResources = array(922 => $costAM);
				$buyable       = false;
				$costOverflow  = BuildFunctions::getRestPrice($USER, $PLANET, $Element, $costResources);
				$elementBonus  = BuildFunctions::getAvalibleBonus($Element);
				
				$bandList[$Element] = array(
					'level'         => $USER[$resource[$Element]] ?? 0,
					'maxLevel'      => $pricelist[$Element]['max'],
					'factor'        => $pricelist[$Element]['factor'],
					'costResources' => $costResources,
					'buyable'       => $buyable,
					'costOverflow'  => $costOverflow,
					'elementBonus'  => $elementBonus,
					'AllTech'       => array($Element => $bonusElementList),
				);
			}
		}
		
		$this->assign(array(	
			'bandList' => $bandList,
		));
		
		$this->display('page.band.default.tpl');
	}
}

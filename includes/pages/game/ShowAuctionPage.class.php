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

class ShowAuctionPage extends AbstractGamePage
{
	public static $requireModule = MODULE_AUCTION;

	function __construct() 
	{
		parent::__construct();
	}

	public function UpdateAuction($Element)
	{
		global $PLANET, $USER, $reslist, $resource, $pricelist, $LNG, $BonusElement;
		
		if (TIMESTAMP <= $USER[$resource[$Element]]) {
			$this->printMessage(''.$LNG['bd_restart_no'].'', true, array('game.php?page=auction', 2));
			return;
		}
		
		$costAM = (float) PremiumEconomy::get('auction_price_am', 900);
		$amount = max(1, min((int) ($pricelist[$Element]['max'] ?? 1), (int) HTTP::_GP('amount', 1)));
		$totalAM = $costAM * $amount;
		
		if (!PremiumEconomy::debit($USER, 922, $totalAM, 'auction_buy', $Element, "amt={$amount}")) {
			$this->printMessage(''.$LNG['bd_notres'].'', true, array('game.php?page=auction', 2));
			return;
		}
		
		$hours = PremiumEconomy::get('auction_hours', 60);
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
		
		$USER[$resource[$Element]] = max($USER[$resource[$Element]], TIMESTAMP) + ($pricelist[$Element]['time']) * $amount;
		$db->update("UPDATE %%USERS%% SET {$resource[$Element]} = :t WHERE id = :u;", array(
			':t' => $USER[$resource[$Element]],
			':u' => $USER['id']
		));
		
		$this->printMessage(''.$LNG['bd_buy_yes'].'', true, array('game.php?page=auction', 2));
	}
	
	public function show()
	{
		global $USER, $PLANET, $resource, $reslist, $LNG, $pricelist, $requeriments, $BonusElement;
		
		$updateID = HTTP::_GP('id', 0);
		
		if (!empty($updateID) && $_SERVER['REQUEST_METHOD'] === 'POST' && $USER['urlaubs_modus'] == 0)
		{
			if(in_array($updateID, $reslist['auction'])) {
				$this->UpdateAuction($updateID);
			}
		}
		
		$this->tplObj->loadscript('officier.js');		
		
		$auctionList = array();
		
		if(isModuleAvailable(MODULE_AUCTION)) 
		{
			$hours = PremiumEconomy::get('auction_hours', 60);
			$prs = max((float) PremiumEconomy::get('prs', 0), (float) PremiumEconomy::baseIncomeMSE());
			$targetMSE = $hours * $prs;
			$costAM = (float) PremiumEconomy::get('auction_price_am', 900);
			
			foreach($reslist['auction'] as $Element)
			{
				if($USER[$resource[$Element]] > TIMESTAMP) {
					$this->tplObj->execscript("GetOfficerTime(".$Element.", ".($USER[$resource[$Element]] - TIMESTAMP).");");
				}
				
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
				$buyable       = BuildFunctions::isElementBuyable($USER, $PLANET, $Element, $costResources);
				$costOverflow  = BuildFunctions::getRestPrice($USER, $PLANET, $Element, $costResources);
				$elementBonus  = BuildFunctions::getAvalibleBonus($Element);
				
				$auctionList[$Element] = array(
					'maxLevel'      => $pricelist[$Element]['max'],
					'timeLeft'      => max($USER[$resource[$Element]] - TIMESTAMP, 0),
					'costResources' => $costResources,
					'buyable'       => $buyable,
					'costOverflow'  => $costOverflow,
					'elementBonus'  => $elementBonus,
					'AllTech'       => array($Element => $bonusElementList),
				);
			}
		}
		
		$this->assign(array(	
			'auctionList' => $auctionList,
		));
		
		$this->display('page.auction.default.tpl');
	}
}

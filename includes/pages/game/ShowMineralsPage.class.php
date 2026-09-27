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

class ShowMineralsPage extends AbstractGamePage
{
	public static $requireModule = MODULE_MINERALS;

	function __construct() 
	{
		parent::__construct();
	}

	public function UpdateMinerals($Element)
	{
		global $PLANET, $USER, $reslist, $resource, $pricelist, $LNG, $BonusElement;
		
		if (!in_array($Element, $reslist['minerals'])) {
			return;
		}

		$amount = (int) HTTP::_GP('amount', 0); 
		if ($amount <= 0) {
			$this->redirectTo('game.php?page=minerals');
		}

		if (isset($pricelist[$Element]['max']) && $pricelist[$Element]['max'] > 0 && $amount > $pricelist[$Element]['max']) {
			$this->printMessage($LNG['bd_limit'], true, array('game.php?page=minerals', 2));
		}

		$mineralCol = $resource[$Element];
		if (!isset($USER[$mineralCol]) || $amount > $USER[$mineralCol]) {
			$this->printMessage($LNG['bd_notres'], true, array('game.php?page=minerals', 2));
		}

		// 1. Cobra los 10 MO por mineral con PremiumEconomy::debit
		$refineCost = 10 * $amount;
		if (!PremiumEconomy::debit($USER, 921, $refineCost, 'mineral_refine_cost', $Element)) {
			$this->printMessage($LNG['bd_notres'], true, array('game.php?page=minerals', 2));
		}

		// 2. Consume el mineral del inventario de forma atómica
		$db = Database::get();
		$db->update(
			"UPDATE %%USERS%% SET $mineralCol = $mineralCol - :amt WHERE id = :u AND $mineralCol >= :amt;",
			array(
				':amt' => $amount,
				':u'   => $USER['id']
			)
		);

		if ($db->rowCount() < 1) {
			PremiumEconomy::credit($USER, 921, $refineCost, 'mineral_refine_refund', $Element);
			$this->printMessage($LNG['bd_notres'], true, array('game.php?page=minerals', 2));
		}

		$USER[$mineralCol] -= $amount;

		// 3. Acredita el producto del refinado
		if (isset($BonusElement[$Element])) {
			foreach ($BonusElement[$Element] as $bonusId => $Count) {
				if ($bonusId == 921) {
					PremiumEconomy::credit($USER, 921, $Count * $amount, 'mineral_refined_dm', $Element);
				} elseif (isset($resource[$bonusId])) {
					$resCol = $resource[$bonusId];
					$giveAmount = $Count * $amount;
					if (isset($PLANET[$resCol])) {
						$db->update("UPDATE %%PLANETS%% SET $resCol = $resCol + :amt WHERE id = :p;", array(
							':amt' => $giveAmount,
							':p'   => $PLANET['id'],
						));
						$PLANET[$resCol] += $giveAmount;
					} elseif (isset($USER[$resCol])) {
						$db->update("UPDATE %%USERS%% SET $resCol = $resCol + :amt WHERE id = :u;", array(
							':amt' => $giveAmount,
							':u'   => $USER['id'],
						));
						$USER[$resCol] += $giveAmount;
					}
				}
			}
		}

		$this->redirectTo('game.php?page=minerals');
	}
	
	public function show()
	{
		global $USER, $PLANET, $resource, $reslist, $LNG, $pricelist, $requeriments;
		
		$updateID	  = HTTP::_GP('id', 0);
		
		if (!empty($updateID) && $_SERVER['REQUEST_METHOD'] === 'POST' && $USER['urlaubs_modus'] == 0)
		{
			if(in_array($updateID, $reslist['minerals'])) {
				$this->UpdateMinerals($updateID);
			}
		}
		
		$this->tplObj->loadscript('officier.js');		
		
		$mineralsList	= array();
		
		if(isModuleAvailable(MODULE_MINERALS)) 
		{
			foreach($reslist['minerals'] as $Element)
			{
                $bonusElementList   = BuildFunctions::bonusElementList($Element);
				$costResources		= BuildFunctions::getElementPrice($USER, $PLANET, $Element);
				$buyable			= BuildFunctions::isElementBuyable($USER, $PLANET, $Element, $costResources);
				$costOverflow		= BuildFunctions::getRestPrice($USER, $PLANET, $Element, $costResources);
				$elementBonus		= BuildFunctions::getAvalibleBonus($Element);
				
				$mineralsList[$Element]	= array(
                    'level'				=> $USER[$resource[$Element]],
                    'maxLevel'			=> $pricelist[$Element]['max'],
                    'factor'		    => $pricelist[$Element]['factor'],
					'costResources'	    => $costResources,
					'buyable'			=> $buyable,
					'costOverflow'		=> $costOverflow,
					'elementBonus'		=> $elementBonus,
					'AllTech'			=> $bonusElementList,
				);
			}
		}
		
		$this->assign(array(	
			'mineralsList'	=> $mineralsList,
		));
		
		$this->display('page.minerals.default.tpl');
	}
}

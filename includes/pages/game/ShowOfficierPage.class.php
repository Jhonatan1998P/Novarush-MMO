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

class ShowOfficierPage extends AbstractGamePage
{
	public static $requireModule = MODULE_OFFICIER;

	function __construct() 
	{
		parent::__construct();
	}

	public function UpdateOfficier($Element)
	{
		global $PLANET, $USER, $reslist, $resource, $pricelist, $LNG;
		
		if (!BuildFunctions::isTechnologieAccessible($USER, $PLANET, $Element, array())) {
			return;
		}
		
		$base = PremiumEconomy::get('officer_base', 3000);
		$growth = PremiumEconomy::get('officer_growth', 1.25);
		$curLevel = (int) ($USER[$resource[$Element]] ?? 0);
		$maxLevel = (int) ($pricelist[$Element]['max'] ?? 20);
		
		$reqAmount = (int) HTTP::_GP('amount', 1);
		$amount = max(1, min($reqAmount, $maxLevel - $curLevel));
		
		if ($curLevel >= $maxLevel || $amount <= 0) {
			$this->printMessage(''.$LNG['bd_maxlevel'].'', true, array('game.php?page=officier', 2));
			return;
		}
		
		$totalCost = 0;
		for ($i = 0; $i < $amount; $i++) {
			$totalCost += (float) ceil($base * pow($growth, $curLevel + $i));
		}
		
		if (!PremiumEconomy::debit($USER, 921, $totalCost, 'officier_buy', $Element, "lvl={$curLevel};amt={$amount}")) {
			$this->printMessage(''.$LNG['bd_notres'].'', true, array('game.php?page=officier', 2));
			return;
		}
		
		$USER[$resource[$Element]] += $amount;
		$db = Database::get();
		$db->update("UPDATE %%USERS%% SET {$resource[$Element]} = {$resource[$Element]} + :amt WHERE id = :u;", array(
			':amt' => $amount,
			':u'   => $USER['id']
		));
		
		$this->printMessage(''.$LNG['bd_buy_yes'].'', true, array('game.php?page=officier', 2));
	}
	
	public function show()
	{
		global $USER, $PLANET, $resource, $reslist, $LNG, $pricelist, $requeriments;
		
		$updateID = HTTP::_GP('id', 0);
		
		if (!empty($updateID) && $_SERVER['REQUEST_METHOD'] === 'POST' && $USER['urlaubs_modus'] == 0)
		{
			if(in_array($updateID, $reslist['officier'])) {
				$this->UpdateOfficier($updateID);
			}
		}
		
		$this->tplObj->loadscript('officier.js');		
		
		$officierList = array();
		
		if(isModuleAvailable(MODULE_OFFICIER))
		{
			$base = PremiumEconomy::get('officer_base', 3000);
			$growth = PremiumEconomy::get('officer_growth', 1.25);
			
			foreach($reslist['officier'] as $Element)
			{
				$techTreeList = BuildFunctions::requirementsList($USER, $PLANET, $Element);
				$curLevel = (int) ($USER[$resource[$Element]] ?? 0);
				$costMO = (float) ceil($base * pow($growth, $curLevel));
				$costResources = array(921 => $costMO);
				$buyable       = BuildFunctions::isElementBuyable($USER, $PLANET, $Element, $costResources);
				$costOverflow  = BuildFunctions::getRestPrice($USER, $PLANET, $Element, $costResources);
				$elementBonus  = BuildFunctions::getAvalibleBonus($Element);
				
				$officierList[$Element] = array(
					'level'         => $curLevel,
					'maxLevel'      => $pricelist[$Element]['max'],
					'factor'        => $growth,
					'costResources' => $costResources,
					'buyable'       => $buyable,
					'costOverflow'  => $costOverflow,
					'elementBonus'  => $elementBonus,
					'AllTech'       => $techTreeList,
					'techacc'       => BuildFunctions::isTechnologieAccessible($USER, $PLANET, $Element, array()),
				);
			}
		}
		
		$this->assign(array(	
			'officierList' => $officierList,
		));
		
		$this->display('page.officier.default.tpl');
	}
}
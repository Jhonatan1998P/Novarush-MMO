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

class ShowBuyBuildPage extends AbstractGamePage
{
	public static $requireModule = MODULE_BUY_BUILD;

	function __construct() 
	{
		parent::__construct();
	}
    
	private function CheckLabSettingsInQueue($Element)
	{
		global $PLANET;
		if ($PLANET['b_building'] == 0)
			return true;
			
		$CurrentQueue = unserialize($PLANET['b_building_id']);
		if (empty($CurrentQueue))
			return true;

		foreach($CurrentQueue as $ListIDArray) {
			if($ListIDArray[0] == $Element)
				return false;
		}

		return true;
	}
	
	public function send()
	{
		global $USER, $PLANET, $LNG, $pricelist, $resource, $reslist, $resglobal;
        
		$Elements = $reslist['allow'][$PLANET['planet_type']];
		$CurrentMaxFields = CalculateMaxPlanetFields($PLANET);
		
		$Element = HTTP::_GP('Element', 0);
		if($Element == 0){
			$this->printMessage(''.$LNG['bd_limit'].'', true, array('game.php?page=buyBuild', 2));	
		}
		
		$Count = max(0, (int) round(HTTP::_GP('count', 0.0)));
		if($Count <= 0){
			$this->printMessage(''.$LNG['bd_limit'].'', true, array('game.php?page=buyBuild', 2));	
		}
		
		if (!$this->CheckLabSettingsInQueue($Element) || ($PLANET['field_current'] + $Count) > $CurrentMaxFields)
		{
			$this->redirectTo('game.php?page=buyBuild');
			return;
		}
		
		if(!empty($Element) && in_array($Element, $Elements) && BuildFunctions::isTechnologieAccessible($USER, $PLANET, $Element, array()) && (in_array($Element, $Elements) || in_array($Element, $reslist['not_bought'])))
		{ 
			$curLvl = (int) ($PLANET[$resource[$Element]] ?? 0);
			$totalCost = BuildFunctions::getInstantPriceTotalLevels($Element, $curLvl, $Count);
			
			if (!PremiumEconomy::debit($USER, 921, $totalCost, 'buy_build', $Element, "cnt={$Count};from={$curLvl};cost={$totalCost}")) {
				$this->printMessage("".$LNG['bd_notres']."", true, array("game.php?page=buyBuild", 1));
				return;
			}
			
			$PLANET['field_current'] += $Count;
			$PLANET[$resource[$Element]] += $Count;
			
			$sql = 'UPDATE %%PLANETS%% SET
				'.$resource[$Element].' = '.$resource[$Element].' + :cnt,
				field_current = field_current + :cnt
				WHERE id = :Id;';
				
			Database::get()->update($sql, array(
				':cnt' => $Count,
				':Id'  => $PLANET['id']
			));
            
			$this->printMessage(''.$LNG['bd_buy_yes'].'', true, array("game.php?page=buyBuild", 1));
		}
	}
	
	function show()
	{
		global $PLANET, $LNG, $pricelist, $resource, $reslist, $USER, $resglobal;
        
		$Elements = $reslist['allow'][$PLANET['planet_type']];
		$allowedElements = array();
		$Cost = array();
		
		foreach($Elements as $Element)
		{
			if(!BuildFunctions::isTechnologieAccessible($USER, $PLANET, $Element, array()) || !in_array($Element, $Elements) || in_array($Element, $reslist['not_bought']))
				continue;
				
			$allowedElements[] = $Element;
            
			$baseCostMO = BuildFunctions::getInstantMSE($Element) / 250.0;
			
			$Cost[$Element] = array(
				$PLANET[$resource[$Element]] ?? 0,
				$LNG['tech'][$Element],
				$baseCostMO,
				$pricelist[$Element]['factor'] ?? 1.0
			);
		}
		
		if(empty($Cost)) {
			$this->printMessage("".$LNG['bd_buy_no_tech']."");
		}
		
		$this->tplObj->loadscript('buy.js');
		$this->tplObj->assign_vars(array(
			'buy_instantly' => 921,
			'Elements'      => $allowedElements,
			'CostInfos'     => $Cost,
		));
		
		$this->display('page.buyBuild.default.tpl');
	}
}
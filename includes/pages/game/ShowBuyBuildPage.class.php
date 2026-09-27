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
			// Calculate instant completion cost based on remaining/build time: max(10, ceil(40 * (hRest)^0.9)) MO
			$k   = PremiumEconomy::get('instant_k', 40);
			$exp = PremiumEconomy::get('instant_exp', 0.9);
			$min = PremiumEconomy::get('instant_min', 10);
			
			$curLvl = (int) ($PLANET[$resource[$Element]] ?? 0);
			$totalCost = 0;
			for ($i = 0; $i < $Count; $i++) {
				$timeSec = BuildFunctions::getBuildingTime($USER, $PLANET, $Element, $curLvl + $i);
				$hRest = max(0.001, $timeSec / 3600.0);
				$totalCost += (float) max($min, ceil($k * pow($hRest, $exp)));
			}
			
			if (!PremiumEconomy::debit($USER, 921, $totalCost, 'buy_build_instant', $Element, "cnt={$Count};cost={$totalCost}")) {
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
		
		$k   = PremiumEconomy::get('instant_k', 40);
		$exp = PremiumEconomy::get('instant_exp', 0.9);
		$min = PremiumEconomy::get('instant_min', 10);
		
		foreach($Elements as $Element)
		{
			if(!BuildFunctions::isTechnologieAccessible($USER, $PLANET, $Element, array()) || !in_array($Element, $Elements) || in_array($Element, $reslist['not_bought']))
				continue;
				
			$allowedElements[] = $Element;
            
			$timeSec = BuildFunctions::getBuildingTime($USER, $PLANET, $Element);
			$hRest = max(0.001, $timeSec / 3600.0);
			$instantPrice = (float) max($min, ceil($k * pow($hRest, $exp)));
			
			$Cost[$Element] = array(
				$PLANET[$resource[$Element]] ?? 0,
				$LNG['tech'][$Element],
				$instantPrice,
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
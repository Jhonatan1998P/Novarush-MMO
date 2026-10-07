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

class ShowBuyTechPage extends AbstractGamePage
{
	public static $requireModule = MODULE_BUY_TECH;

	function __construct() 
	{
		parent::__construct();
	}
	
	public function send()
	{
		global $USER, $PLANET, $LNG, $pricelist, $resource, $reslist, $resglobal;
        
        //Проверка на цену покупки
		$Element			= HTTP::_GP('Element', 0);
		if($Element == 0){
			$this->printMessage(''.$LNG['bd_limit'].'',true, array('game.php?page=buyTech', 2));	
        }
        //Проверка на колличество покупки
		$Count			= max(0, round(HTTP::_GP('count', 0.0)));
        if($Count == 0){
            $this->printMessage(''.$LNG['bd_limit'].'',true, array('game.php?page=buyTech', 2));	
        }
        //Precio acumulativo calibrado (MSE 4:2:1 / 250)
		$curLvl			= (int) ($USER[$resource[$Element]] ?? 0);
		$cost			= BuildFunctions::getInstantPriceTotalLevels($Element, $curLvl, $Count);
        //Ограничение по технологиям и $reslist
		if(!empty($Element) && in_array($Element, $reslist['tech']) && BuildFunctions::isTechnologieAccessible($USER, $PLANET, $Element, array()) && (in_array($Element, $reslist['tech']) || in_array($Element, $reslist['not_bought'])))
		{ 
            //Débito vía PremiumEconomy
			if(!PremiumEconomy::debit($USER, 921, $cost, 'buy_tech', $Element, "cnt={$Count};from={$curLvl};cost={$cost}"))
			{
				$this->printMessage("".$LNG['bd_notres']."", true, array("game.php?page=buyTech", 1));
				return;
			}
			
            $sql	= 'UPDATE %%USERS%% SET
            '.$resource[$Element].' = '.$resource[$Element].' + :cnt
            WHERE id = :Id;';
                
            Database::get()->update($sql, array(
                ':cnt'  => $Count,
                ':Id'	=> $USER['id']
            ));  
            $USER[$resource[$Element]]		+= $Count;
            
			$this->printMessage(''.$LNG['bd_buy_yes'].'', true, array("game.php?page=buyTech", 1));
		}
	}
	
	function show()
	{
		global $PLANET, $LNG, $pricelist, $resource, $reslist, $USER, $resglobal;
        
        //Перебор
		$allowedElements = array();
		$Cost = array();
		foreach($reslist['tech'] as $Element)
		{
			if(!BuildFunctions::isTechnologieAccessible($USER, $PLANET, $Element, array()) || !in_array($Element, $reslist['tech']) || in_array($Element, $reslist['not_bought']))
				continue;
			$allowedElements[] = $Element;
            
			$baseCostMO = BuildFunctions::getInstantMSE($Element) / 250.0;
			$Cost[$Element]	= array($USER[$resource[$Element]], $LNG['tech'][$Element], $baseCostMO, (float)($pricelist[$Element]['factor'])) ;
		}
		//Бан, если пусто.
		if(empty($Cost)) {
			$this->printMessage("".$LNG['bd_buy_no_tech']."");
		}
		$this->tplObj->loadscript('buy.js');
		$this->tplObj->assign_vars(array(
            'buy_instantly'	=> $resglobal['buy_instantly'],
			'Elements'	    => $allowedElements,
			'CostInfos'	    => $Cost,
		));
		
		$this->display('page.buyTech.default.tpl');
	}
}
?>
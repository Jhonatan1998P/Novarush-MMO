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

class ShowCreateMoonPage extends AbstractGamePage
{
	public static $requireModule = MODULE_CREATE_MOON;

	function __construct() 
	{
		parent::__construct();
	}
    
	private function getMoonPrice()
	{
		global $USER;
		$db = Database::get();
		$moonCount = (int) $db->selectSingle(
			'SELECT COUNT(*) as cnt FROM %%PLANETS%% WHERE id_owner = :u AND planet_type = 3;',
			array(':u' => $USER['id']),
			'cnt'
		);
		$base = (int) PremiumEconomy::get('moon_base', 2);
		$step = (int) PremiumEconomy::get('moon_step', 3);
		$price = $base + ($step > 0 ? (int) floor($moonCount / $step) : 0);
		return array($price, $moonCount);
	}

	function buy()
	{
		global $LNG, $PLANET, $USER, $resource;
		
		if($PLANET['planet_type'] != 1 || $PLANET['id_luna'] != 0){
			$this->printMessage($LNG['crm_moon_is'], true, array('game.php?page=overview', 2));
			return;
		}
		
		list($pricePolvos, $moonCount) = $this->getMoonPrice();
		
		if (!PremiumEconomy::debit($USER, 923, $pricePolvos, 'create_moon', 0, "moons={$moonCount}")) {
			$this->printMessage($LNG['crm_not_res'], array('game.php?page=createMoon', 3));
			return;
		}
		
		$a = mt_rand(8000, 9000);
		$u_have_moon = PlayerUtil::createMoon($PLANET['universe'], $PLANET['galaxy'], $PLANET['system'], $PLANET['planet'], $USER['id'], '', $a, '', 'Moon');
		$this->printMessage($LNG['crm_create'], array('game.php?page=overview', 3));
	}
    
	function show()
	{
		global $LNG, $PLANET, $USER, $resource;
        
		list($pricePolvos, $moonCount) = $this->getMoonPrice();
		
		$this->tplObj->assign_vars(array(	   
			'buy_moon_price'    => $pricePolvos,
			'buy_moon_res_user' => $USER[$resource[923]] ?? 0,
			'buy_moon_res'      => 923,
		));
		$this->display('page.createmoon.default.tpl');
	}
}
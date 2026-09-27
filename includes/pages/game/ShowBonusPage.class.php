<?php

/*
 * ╔══╗╔══╗╔╗──╔╗╔═══╗╔══╗╔╗─╔╗╔╗╔╗──╔╗╔══╗╔══╗╔══╗
 * ║╔═╝║╔╗║║║──║║║╔═╗║║╔╗║║╚═╝║║║║║─╔╝║╚═╗║║╔═╝╚═╗║
 * ║║──║║║║║╚╗╔╝║║╚═╝║║╚╝║║╔╗─║║╚╝║─╚╗║╔═╝║║╚═╗──║║
 * ║║──║║║║║╔╗╔╗║║╔══╝║╔╗║║║╚╗║╚═╗║──║║╚═╗║║╔╗║──║║
 * ║╚═╗║╚╝║║║╚╝║║║║───║║║║║║─║║─╔╝║──║║╔═╝║║╚╝║──║║
 * ╚══╝╚══╝╚╝──╚╝╚╝───╚╝╚╝╚╝─╚╝─╚═╝──╚╝╚══╝╚══╝──╚╝
 *
 * @author Aurum79 aka Чук
 * @info ***
 * @link https://github.com/Yaro2709/New-Star
 * @Basis 2Moons: XG-Project v2.8.0
 * @Basis New-Star: 2Moons v1.8.0
 */

class ShowBonusPage extends AbstractGamePage 
{
	public static $requireModule = MODULE_BONUS;
    
	function __construct() 
	{
		parent::__construct();
	}
	
	function show()
	{		
		global $USER, $PLANET, $LNG, $resource;
        
		if ($USER['bonus_time'] > TIMESTAMP || $USER['urlaubs_modus'] != 0) {
			$this->redirectTo('game.php');
		}

		$time = TIMESTAMP + 86400;

		$db = Database::get();
		$sql = 'UPDATE %%USERS%% SET bonus_time = :time WHERE id = :userID AND bonus_time <= :now;';
		$db->update($sql, array(
			':time'   => $time,
			':userID' => $USER['id'],
			':now'    => TIMESTAMP,
		));

		if ($db->rowCount() < 1) {
			$this->redirectTo('game.php');
		}

		$USER['bonus_time'] = $time;

		$bonus = array(
			921	=> mt_rand(500, 1500),
			922	=> mt_rand(25, 75),
			924	=> mt_rand(2, 6),
		);

		foreach ($bonus as $id => $amount) {
			PremiumEconomy::credit($USER, $id, $amount, 'bonus_daily');
		}

		$bonusList = array();
		foreach ($bonus as $id => $amount) {
			$bonusList[$id] = array(
				'bonus' => $amount,
			);
		}

		$this->tplObj->assign_vars(array(
			'bonusList' => $bonusList,
		));
		$this->display('page.bonus.default.tpl');
	}	
}
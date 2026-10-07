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

require_once 'includes/vars/General.php';
require_once 'includes/classes/class.BuildFunctions.php';
require_once 'includes/classes/class.PlanetRessUpdate.php';
require_once 'includes/classes/class.FlyingFleetHandler.php';

$db		= Database::get();
$loops	= 0;

while($loops < 10)
{
	$token	= getRandomString();

	$fleetResult	= $db->update("UPDATE %%FLEETS_EVENT%% SET `lock` = :token WHERE `lock` IS NULL AND `time` <= :time;", array(
		':time'		=> TIMESTAMP,
		':token'	=> $token
	));

	if($db->rowCount() === 0) {
		break;
	}

	try {
		$fleetObj	= new FlyingFleetHandler();
		$fleetObj->setToken($token);
		$fleetObj->run();
	} finally {
		$db->update("UPDATE %%FLEETS_EVENT%% SET `lock` = NULL WHERE `lock` = :token;", array(
			':token' => $token
		));
	}

	$loops++;
}
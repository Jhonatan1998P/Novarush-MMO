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

class MissionCaseExpedition extends MissionFunctions implements Mission
{
	function __construct($fleet)
	{
		$this->_fleet	= $fleet;
	}
	
	function TargetEvent()
	{
		$this->setState(FLEET_HOLD);
		$this->SaveFleet();
	}
	
	function EndStayEvent()
	{
		global $pricelist, $reslist, $resource;
        
		$LNG	= $this->getLanguage(NULL, $this->_fleet['fleet_owner']);

		$config	= Config::get($this->_fleet['fleet_universe']);

        $expeditionPoints       = array();

		foreach($reslist['fleet'] as $shipId)
		{
			// Coste en Metal Puro: Metal + (Cristal * 2) + (Deuterio * 4)
			$expeditionPoints[$shipId]	= (isset($pricelist[$shipId]['cost'][901]) ? $pricelist[$shipId]['cost'][901] : 0)
										+ ((isset($pricelist[$shipId]['cost'][902]) ? $pricelist[$shipId]['cost'][902] : 0) * 2)
										+ ((isset($pricelist[$shipId]['cost'][903]) ? $pricelist[$shipId]['cost'][903] : 0) * 4);
		}
			
		$fleetArray		= FleetFunctions::unserialize($this->_fleet['fleet_array']);
		$fleetPoints 	= 0;
		$fleetCapacity	= 0;
        $fleetPoints_id = array();

		foreach ($fleetArray as $shipId => $shipAmount)
		{
			$fleetCapacity 			   += $shipAmount * $pricelist[$shipId]['capacity'];
			$fleetPoints   			   += $shipAmount * $expeditionPoints[$shipId];
            $fleetPoints_id[$shipId]   = $shipAmount * $expeditionPoints[$shipId];
		}
        
        // Cap de puntos de flota para expediciones: 100M x velocidad de producción de recursos
        $maxExpeditionPoints = 100000000 * $config->resource_multiplier;
        if($fleetPoints > $maxExpeditionPoints){
            $fleetPoints = $maxExpeditionPoints;
        }
        // Factor de rendimiento base para otros eventos (30%)
        $exp_factor = 0.30;
        $fleetPrize = $fleetPoints * $exp_factor;
        
		$fleetCapacity  -= $this->_fleet['fleet_resource_metal'] + $this->_fleet['fleet_resource_crystal']
			+ $this->_fleet['fleet_resource_deuterium'] + $this->_fleet['fleet_resource_darkmatter'];
		$fleetCapacity = max(0, $fleetCapacity);

        $GetEvent      = mt_rand(0, 100000);
        $eventType     = 'nothing';
        $narrativeText = '';
        $rewardData    = array();

        // 1. Recursos
        if ($GetEvent <= 40000) {
            $eventType   = 'resources';
            $input       = $reslist['resstype'][1];
            $rand_keys   = array_rand($input, 1);
            $resID       = $input[$rand_keys];
            $FindSize    = mt_rand(1, 100);
            $SizeInMetal = 0;

            if ($FindSize <= 2) {
                // Yacimiento Grande (2% de prob): 70% del valor de la flota en Metal Puro
                $SizeInMetal   = $fleetPoints * 0.70;
                $narrativeText = $LNG['sys_expe_found_ress_3_' . mt_rand(1, 2)];
            } elseif ($FindSize <= 22) {
                // Yacimiento Mediano (20% de prob): 40% del valor de la flota en Metal Puro
                $SizeInMetal   = $fleetPoints * 0.40;
                $narrativeText = $LNG['sys_expe_found_ress_2_' . mt_rand(1, 3)];
            } else {
                // Yacimiento Pequeño (78% de prob): 20% del valor de la flota en Metal Puro
                $SizeInMetal   = $fleetPoints * 0.20;
                $narrativeText = $LNG['sys_expe_found_ress_1_' . mt_rand(1, 4)];
            }

            // Conversión inversa según el recurso otorgado (Metal 1:1, Cristal / 2, Deuterio / 4)
            if ($resID == 901) {
                $Size = floor($SizeInMetal);
            } elseif ($resID == 902) {
                $Size = floor($SizeInMetal / 2);
            } elseif ($resID == 903) {
                $Size = floor($SizeInMetal / 4);
            } else {
                $Size = floor($SizeInMetal);
            }

            $actualSize = (int) max(0, min($Size, $fleetCapacity));
            $fleetColName = 'fleet_resource_' . $resource[$resID];
            $this->UpdateFleet($fleetColName, $this->_fleet[$fleetColName] + $actualSize);

            $rewardData['resources'] = array(
                $resID => $actualSize,
                'capacityLost' => ($actualSize < $Size) ? ($Size - $actualSize) : 0
            );

        // 2. Materia Oscura
        } elseif ($GetEvent > 40000 && $GetEvent <= 50000) {
            if ($fleetPoints < 500000) {
                $eventType     = 'nothing';
                $narrativeText = $LNG['sys_expe_nothing_' . mt_rand(1, 8)];
            } else {
                $eventType = 'darkmatter';
                $FindSize  = mt_rand(1, 100);
                $Size      = 0;

                if ($FindSize <= 2) {
                    $Size          = mt_rand(26, 32) * ($fleetPrize / 500000);
                    $narrativeText = $LNG['sys_expe_found_dm_3_' . mt_rand(1, 2)];
                } elseif ($FindSize <= 22) {
                    $Size          = mt_rand(16, 24) * ($fleetPrize / 500000);
                    $narrativeText = $LNG['sys_expe_found_dm_2_' . mt_rand(1, 3)];
                } else {
                    $Size          = mt_rand(12, 14) * ($fleetPrize / 500000);
                    $narrativeText = $LNG['sys_expe_found_dm_1_' . mt_rand(1, 5)];
                }

                $Size = max(1, min(300, (int) floor($Size)));
                if ($Size > 0) {
                    $ownerId = (int) $this->_fleet['fleet_owner'];
                    $targetUser = Database::get()->selectSingle("SELECT * FROM %%USERS%% WHERE id = :userId;", array(':userId' => $ownerId));
                    if (!empty($targetUser)) {
                        PremiumEconomy::credit($targetUser, 921, $Size, 'expedition');
                        global $USER;
                        if (isset($USER['id']) && $USER['id'] == $ownerId) {
                            $USER = $targetUser;
                        }
                    }
                    $rewardData['darkmatter'] = $Size;
                }
            }

        // 3. Minerales
        } elseif ($GetEvent > 50000 && $GetEvent <= 55000) {
            if ($fleetPoints < 500000) {
                $eventType     = 'nothing';
                $narrativeText = $LNG['sys_expe_nothing_' . mt_rand(1, 8)];
            } else {
                $eventType = 'minerals';
                $input     = $reslist['minerals'];
                $rand_keys = array_rand($input, 1);
                $Size      = 1;

                $sql = "UPDATE %%USERS%% SET " . $resource[$input[$rand_keys]] . " = " . $resource[$input[$rand_keys]] . " + " . $Size . " WHERE id = :userId;";
                Database::get()->update($sql, array(
                    ':userId' => $this->_fleet['fleet_owner'],
                ));
                $narrativeText = $LNG['sys_expe_found_minerals_' . mt_rand(1, 7)];
                $rewardData['minerals'] = array($input[$rand_keys] => $Size);
            }

        // 4. Anomalía Temporal (Aceleración / Retraso)
        } elseif ($GetEvent > 55000 && $GetEvent <= 65000) {
            $MoreTime = mt_rand(0, 100);
            $Wrapper  = array(2, 2, 2, 2, 2, 3, 3, 5);

            if ($MoreTime < 75) {
                $eventType     = 'time_slow';
                $this->UpdateFleet('fleet_end_time', ($this->_fleet['fleet_end_time'] - $this->_fleet['fleet_end_stay']) * $Wrapper[mt_rand(0, 7)] + $this->_fleet['fleet_end_stay']);
                $narrativeText = $LNG['sys_expe_time_slow_' . mt_rand(1, 6)];
            } else {
                $eventType     = 'time_fast';
                $this->UpdateFleet('fleet_end_time', ($this->_fleet['fleet_end_time'] - $this->_fleet['fleet_end_stay']) / $Wrapper[mt_rand(0, 7)] + $this->_fleet['fleet_end_stay']);
                $narrativeText = $LNG['sys_expe_time_fast_' . mt_rand(1, 3)];
            }

        // 5. Contenedores
        } elseif ($GetEvent > 65000 && $GetEvent <= 70000) {
            if ($fleetPoints < 500000) {
                $eventType     = 'nothing';
                $narrativeText = $LNG['sys_expe_nothing_' . mt_rand(1, 8)];
            } else {
                $eventType = 'container';
                $Size      = mt_rand(1, 5);
                $ownerId   = (int) $this->_fleet['fleet_owner'];
                $targetUser = Database::get()->selectSingle("SELECT * FROM %%USERS%% WHERE id = :userId;", array(':userId' => $ownerId));
                if (!empty($targetUser)) {
                    PremiumEconomy::credit($targetUser, 924, $Size, 'expedition');
                    global $USER;
                    if (isset($USER['id']) && $USER['id'] == $ownerId) {
                        $USER = $targetUser;
                    }
                }
                $narrativeText = $LNG['sys_expe_found_container_' . mt_rand(1, 5)];
                $rewardData['container'] = $Size;
            }

        // 6. Polvo Estelar / Stardust
        } elseif ($GetEvent > 70000 && $GetEvent <= 72000) {
            if ($fleetPoints < 500000) {
                $eventType     = 'nothing';
                $narrativeText = $LNG['sys_expe_nothing_' . mt_rand(1, 8)];
            } else {
                $eventType = 'stardust';
                $Size      = 1;
                $ownerId   = (int) $this->_fleet['fleet_owner'];
                $targetUser = Database::get()->selectSingle("SELECT * FROM %%USERS%% WHERE id = :userId;", array(':userId' => $ownerId));
                if (!empty($targetUser)) {
                    PremiumEconomy::credit($targetUser, 923, $Size, 'expedition');
                    global $USER;
                    if (isset($USER['id']) && $USER['id'] == $ownerId) {
                        $USER = $targetUser;
                    }
                }
                $narrativeText = $LNG['sys_expe_found_so_' . mt_rand(1, 7)];
                $rewardData['stardust'] = $Size;
            }

        // 7. Flota encontrada (Naves recuperadas)
        } elseif ($GetEvent > 72000 && $GetEvent <= 78000) {
            if ($fleetPoints < 200000) {
                $eventType     = 'nothing';
                $narrativeText = $LNG['sys_expe_nothing_' . mt_rand(1, 8)];
            } else {
                $eventType     = 'fleet';
                $narrativeText = $LNG['sys_expe_found_ships_' . mt_rand(1, 8)];

                $Found         = array();
                $NewFleetArray = "";

                foreach ($reslist['fleet'] as $ID) {
                    if (!isset($fleetArray[$ID])) {
                        continue;
                    }

                    $Found[$ID] = floor(($fleetPoints_id[$ID] / $expeditionPoints[$ID]) * ($exp_factor + (mt_rand(1, 5) / 100)));
                }

                foreach ($fleetArray as $ID => $Count) {
                    if (!empty($Found[$ID])) {
                        $Count += $Found[$ID];
                    }
                    $NewFleetArray .= $ID . "," . floatToString($Count) . ';';
                }

                $rewardData['fleet'] = $Found;
                $this->UpdateFleet('fleet_array', $NewFleetArray);
                $this->UpdateFleet('fleet_amount', array_sum($fleetArray) + array_sum($Found));
            }

        // 8. Pérdida total de flota
        } elseif ($GetEvent > 78000 && $GetEvent <= 79000) {
            if ($this->_fleet['fleet_owner'] == 4) {
                $eventType     = 'nothing';
                $narrativeText = $LNG['sys_expe_nothing_' . mt_rand(1, 8)];
            } else {
                $eventType     = 'lost';
                $this->KillFleet();
                $narrativeText = $LNG['sys_expe_lost_fleet_' . mt_rand(1, 4)];
            }

        // 9. Sector Vacío
        } else {
            $eventType     = 'nothing';
            $narrativeText = $LNG['sys_expe_nothing_' . mt_rand(1, 8)];
        }

        $fleetCoords = array(
            'galaxy' => $this->_fleet['fleet_end_galaxy'],
            'system' => $this->_fleet['fleet_end_system']
        );

        $Message = MessageTemplateHelper::buildExpeditionCard($eventType, $narrativeText, $rewardData, $fleetCoords, $LNG);

        PlayerUtil::sendMessage(
            $this->_fleet['fleet_owner'],
            0,
            $LNG['sys_mess_tower'],
            15,
            $LNG['sys_expe_report'],
            $Message,
            $this->_fleet['fleet_end_stay'],
            NULL,
            1,
            $this->_fleet['fleet_universe']
        );

        $this->setState(FLEET_RETURN);
        $this->SaveFleet();
    }

    function ReturnEvent()
    {
        $LNG = $this->getLanguage(NULL, $this->_fleet['fleet_owner']);

        $sql = 'SELECT name FROM %%PLANETS%% WHERE id = :planetId;';
        $planetName = Database::get()->selectSingle($sql, array(
            ':planetId' => $this->_fleet['fleet_start_id'],
        ), 'name');

        $origin = array(
            'name'   => 'Sector 16',
            'galaxy' => $this->_fleet['fleet_end_galaxy'],
            'system' => $this->_fleet['fleet_end_system'],
            'planet' => 16
        );
        $target = array(
            'name'   => $planetName ?: 'Planeta',
            'galaxy' => $this->_fleet['fleet_start_galaxy'],
            'system' => $this->_fleet['fleet_start_system'],
            'planet' => $this->_fleet['fleet_start_planet']
        );
        $resources = array(
            901 => $this->_fleet['fleet_resource_metal'],
            902 => $this->_fleet['fleet_resource_crystal'],
            903 => $this->_fleet['fleet_resource_deuterium'],
            921 => $this->_fleet['fleet_resource_darkmatter']
        );

        $Message = MessageTemplateHelper::buildLogisticsCard(
            'return',
            $LNG['sys_mess_fleetback'],
            $origin,
            $target,
            $resources,
            $LNG
        );

        PlayerUtil::sendMessage(
            $this->_fleet['fleet_owner'],
            0,
            $LNG['sys_mess_tower'],
            15,
            $LNG['sys_mess_fleetback'],
            $Message,
            $this->_fleet['fleet_end_time'],
            NULL,
            1,
            $this->_fleet['fleet_universe']
        );

        $this->RestoreFleet();
    }
}
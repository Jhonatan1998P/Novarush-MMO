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

class MissionCaseAttack extends MissionFunctions implements Mission
{
	function __construct($Fleet)
	{
		$this->_fleet	= $Fleet;
	}
	
	function TargetEvent()
	{	
		global $resource, $reslist;

		$db				= Database::get();
		$config			= Config::get($this->_fleet['fleet_universe']);

		$fleetAttack	= array();
		$fleetDefend	= array();
		
		$userAttack		= array();
		$userDefend		= array();

		$incomingFleets	= array();

		$stealResource	= array(
			901	=> 0,
			902	=> 0,
			903	=> 0,
		);
		
		$debris			= array();
		$planetDebris	= array();
		
		$debrisResource	= array(901, 902);

		$sql			= "SELECT * FROM %%PLANETS%% WHERE id = :planetId;";
		$targetPlanet 	= $db->selectSingle($sql, array(
			':planetId'	=> $this->_fleet['fleet_end_id']
		));

		$sql			= "SELECT * FROM %%USERS%% WHERE id = :userId;";
		$targetUser		= $db->selectSingle($sql, array(
			':userId'	=> $targetPlanet['id_owner']
		));


		require_once 'includes/classes/events/WarEventBossEngine.class.php';
		if ($targetUser['id'] == WarEventBossEngine::NPC_USER_ID) {
			$event = WarEventBossEngine::getActiveEvent();
			if (empty($event) || $event['planet_id'] != $targetPlanet['id']) {
				$this->setState(FLEET_RETURN);
				$this->SaveFleet();
				return;
			}
		}

		$targetUser['factor']	= getFactors($targetUser, 'basic', $this->_fleet['fleet_start_time']);

		$planetUpdater	= new ResourceUpdate();
		
		list($targetUser, $targetPlanet)	= $planetUpdater->CalcResource($targetUser, $targetPlanet, true, $this->_fleet['fleet_start_time']);
		
		if($this->_fleet['fleet_group'] != 0)
		{
			$sql	= "DELETE FROM %%AKS%% WHERE id = :acsId;";
			$db->delete($sql, array(
				':acsId'	=> $this->_fleet['fleet_group'],
			));

			$sql	= "SELECT * FROM %%FLEETS%% WHERE fleet_group = :acsId;";

			$incomingFleetsResult = $db->select($sql, array(
				':acsId'	=> $this->_fleet['fleet_group'],
			));
		
			foreach($incomingFleetsResult as $incomingFleetRow)
			{
				$incomingFleets[$incomingFleetRow['fleet_id']] = $incomingFleetRow;
			}
			
			unset($incomingFleetsResult);
		}
		else
		{
			$incomingFleets = array($this->_fleet['fleet_id'] => $this->_fleet);
		}
		
		foreach($incomingFleets as $fleetID => $fleetDetail)
		{
			$sql	= "SELECT * FROM %%USERS%% WHERE id = :userId;";
			$fleetAttack[$fleetID]['player']	= $db->selectSingle($sql, array(
				':userId'	=> $fleetDetail['fleet_owner']
			));

			$fleetAttack[$fleetID]['player']['factor']	= getFactors($fleetAttack[$fleetID]['player'], 'attack', $this->_fleet['fleet_start_time']);
			$fleetAttack[$fleetID]['fleetDetail']		= $fleetDetail;
			$fleetAttack[$fleetID]['unit']				= FleetFunctions::unserialize($fleetDetail['fleet_array']);
			
			$userAttack[$fleetAttack[$fleetID]['player']['id']]	= $fleetAttack[$fleetID]['player']['username'];
		}

		$sql	= "SELECT * FROM %%FLEETS%%
		WHERE fleet_mission		= :mission
		AND fleet_end_id		= :fleetEndId
		AND fleet_start_time 	<= :timeStamp
		AND fleet_end_stay 		>= :timeStamp;";

		$targetFleetsResult = $db->select($sql, array(
			':mission'		=> 5,
			':fleetEndId'	=> $this->_fleet['fleet_end_id'],
			':timeStamp'	=> TIMESTAMP
		));

		foreach($targetFleetsResult as $fleetDetail)
		{
			$fleetID	= $fleetDetail['fleet_id'];

			$sql	= "SELECT * FROM %%USERS%% WHERE id = :userId;";
			$fleetDefend[$fleetID]['player']			= $db->selectSingle($sql, array(
				':userId'	=> $fleetDetail['fleet_owner']
			));

			$fleetDefend[$fleetID]['player']['factor']	= getFactors($fleetDefend[$fleetID]['player'], 'attack', $this->_fleet['fleet_start_time']);
			$fleetDefend[$fleetID]['fleetDetail']		= $fleetDetail;
			$fleetDefend[$fleetID]['unit']				= FleetFunctions::unserialize($fleetDetail['fleet_array']);
			
			$userDefend[$fleetDefend[$fleetID]['player']['id']]	= $fleetDefend[$fleetID]['player']['username'];
		}
			
		unset($targetFleetsResult);
		
		$fleetDefend[0]['player']			= $targetUser;
		$fleetDefend[0]['player']['factor']	= getFactors($fleetDefend[0]['player'], 'attack', $this->_fleet['fleet_start_time']);
		$fleetDefend[0]['fleetDetail']		= array(
			'fleet_start_galaxy'	=> $targetPlanet['galaxy'], 
			'fleet_start_system'	=> $targetPlanet['system'], 
			'fleet_start_planet'	=> $targetPlanet['planet'], 
			'fleet_start_type'		=> $targetPlanet['planet_type'], 
		);
		
		$fleetDefend[0]['unit']				= array();
		
		foreach(array_merge($reslist['fleet'], $reslist['defense']) as $elementID)
		{
			if (empty($targetPlanet[$resource[$elementID]])) continue;

			$fleetDefend[0]['unit'][$elementID] = $targetPlanet[$resource[$elementID]];
		}
			
		$userDefend[$fleetDefend[0]['player']['id']]	= $fleetDefend[0]['player']['username'];
		
		require_once 'includes/classes/missions/functions/calculateAttack.php';

		$fleetIntoDebris	= $config->Fleet_Cdr;
		$defIntoDebris		= $config->Defs_Cdr;
		
		$combatResult 		= calculateAttack($fleetAttack, $fleetDefend, $fleetIntoDebris, $defIntoDebris);

		foreach ($fleetAttack as $fleetID => $fleetDetail)
		{
			$fleetArray = '';
			$totalCount = 0;
			
			$fleetDetail['unit']	= array_filter($fleetDetail['unit']);
			foreach ($fleetDetail['unit'] as $elementID => $amount)
			{				
				$fleetArray .= $elementID.','.floatToString($amount).';';
				$totalCount += $amount;
			}
			
			if($totalCount == 0)
			{
				if($this->_fleet['fleet_id'] == $fleetID)
				{
					$this->KillFleet();
				}
				else
				{
					$sql	= 'DELETE %%FLEETS%%, %%FLEETS_EVENT%%
					FROM %%FLEETS%%
					INNER JOIN %%FLEETS_EVENT%% ON fleetID = fleet_id
					WHERE fleet_id = :fleetId;';

					$db->delete($sql, array(
						':fleetId'	=> $fleetID
					));
				}
				
				$sql	= 'UPDATE %%LOG_FLEETS%% SET fleet_state = :fleetState WHERE fleet_id = :fleetId;';
				$db->update($sql, array(
					':fleetId'		=> $fleetID,
					':fleetState'	=> FLEET_HOLD,
				));

				unset($fleetAttack[$fleetID]);
			}
			elseif($totalCount > 0)
			{
				$sql = "UPDATE %%FLEETS%% fleet, %%LOG_FLEETS%% log SET
				fleet.fleet_array	= :fleetData,
				fleet.fleet_amount	= :fleetCount,
				log.fleet_array		= :fleetData,
				log.fleet_amount	= :fleetCount
				WHERE fleet.fleet_id = :fleetId AND log.fleet_id = :fleetId;";

				$db->update($sql, array(
					':fleetData'	=> substr($fleetArray, 0, -1),
					':fleetCount'	=> $totalCount,
					':fleetId'		=> $fleetID
			  	));
			}
			else
			{
				throw new OutOfRangeException("Negative Fleet amount ....");
			}
		}
		
		foreach ($fleetDefend as $fleetID => $fleetDetail)
		{
			if($fleetID != 0)
			{
				// Stay fleet
				$fleetArray = '';
				$totalCount = 0;
				
				$fleetDetail['unit']	= array_filter($fleetDetail['unit']);
				
				foreach ($fleetDetail['unit'] as $elementID => $amount)
				{				
					$fleetArray .= $elementID.','.floatToString($amount).';';
					$totalCount += $amount;
				}
				
				if($totalCount == 0)
				{
					$sql	= 'DELETE %%FLEETS%%, %%FLEETS_EVENT%%
					FROM %%FLEETS%%
					INNER JOIN %%FLEETS_EVENT%% ON fleetID = fleet_id
					WHERE fleet_id = :fleetId;';

					$db->delete($sql, array(
						':fleetId'	=> $fleetID
					));

					$sql	= 'UPDATE %%LOG_FLEETS%% SET fleet_state = :fleetState WHERE fleet_id = :fleetId;';
					$db->update($sql, array(
						':fleetId'		=> $fleetID,
						':fleetState'	=> FLEET_HOLD,
					));

					unset($fleetAttack[$fleetID]);
				}
				elseif($totalCount > 0)
				{
					$sql = "UPDATE %%FLEETS%% fleet, %%LOG_FLEETS%% log SET
					fleet.fleet_array	= :fleetData,
					fleet.fleet_amount	= :fleetCount,
					log.fleet_array		= :fleetData,
					log.fleet_amount	= :fleetCount
					WHERE fleet.fleet_id = :fleetId AND log.fleet_id = :fleetId;";

					$db->update($sql, array(
	   					':fleetData'	=> substr($fleetArray, 0, -1),
						':fleetCount'	=> $totalCount,
						':fleetId'		=> $fleetID
					));
				}
				else
				{
					throw new OutOfRangeException("Negative Fleet amount ....");
				}
			}
			else
			{
				$params	= array(':planetId' => $this->_fleet['fleet_end_id']);

				// Planet fleet
				$fleetArray = array();
				foreach ($fleetDetail['unit'] as $elementID => $amount)
				{				
					$fleetArray[] = '`'.$resource[$elementID].'` = :'.$resource[$elementID];
					$params[':'.$resource[$elementID]]	= $amount;
				}
				
				if(!empty($fleetArray))
				{
					$sql = 'UPDATE %%PLANETS%% SET '.implode(', ', $fleetArray).' WHERE id = :planetId;';
					$db->update($sql, $params);
				}
			}
		}
		
		if ($combatResult['won'] == "a")
		{
			require_once 'includes/classes/missions/functions/calculateSteal.php';
			$stealResource = calculateSteal($fleetAttack, $targetPlanet);
            
            foreach($reslist['decline_in_battle'] as $elementID)
            {
                if($targetPlanet[$resource[$elementID]] > 0){
                    $sql	= 'UPDATE %%PLANETS%% SET '.$resource[$elementID].' = '.$resource[$elementID].' - 1 WHERE id = :planetId;';
                    Database::get()->update($sql, array(
                        ':planetId'	=> $targetPlanet['id']
                    ));
                }
            }

            // Hook: War Event Boss Final Blow
            require_once 'includes/classes/events/WarEventBossEngine.class.php';
            $winningOwners = array();
            foreach ($incomingFleets as $f) {
                $winningOwners[] = $f['fleet_owner'];
            }
            WarEventBossEngine::checkFinalBlow($targetPlanet['id'], $winningOwners);
		}
		
		if($this->_fleet['fleet_end_type'] == 3)
		{
			// Use planet debris, if attack on moons
			$sql			= "SELECT der_metal, der_crystal FROM %%PLANETS%% WHERE id_luna = :moonId;";
			$targetDebris	= $db->selectSingle($sql, array(
				':moonId'	=> $this->_fleet['fleet_end_id']
			));
			$targetPlanet['der_metal'] += $targetDebris['der_metal'];
			$targetPlanet['der_crystal'] += $targetDebris['der_crystal'];
		}
		
		foreach($debrisResource as $elementID)
		{
			$debris[$elementID]			= $combatResult['debris']['attacker'][$elementID] + $combatResult['debris']['defender'][$elementID];
			$planetDebris[$elementID]	= $targetPlanet['der_'.$resource[$elementID]] + $debris[$elementID];
		}
		
		$debrisTotal		= array_sum($debris);
		
		$moonFactor			= $config->moon_factor;
		$maxMoonChance		= $config->moon_chance;
		
		if($targetPlanet['id_luna'] == 0 && $targetPlanet['planet_type'] == 1)
		{
			$chanceCreateMoon	= round($debrisTotal / 100000 * $moonFactor);
			$chanceCreateMoon	= min($chanceCreateMoon, $maxMoonChance);
		}
		else
		{
			$chanceCreateMoon	= 0;
		}

		$reportInfo	= array(
			'thisFleet'				=> $this->_fleet,
			'debris'				=> $debris,
			'stealResource'			=> $stealResource,
			'moonChance'			=> $chanceCreateMoon,
			'moonDestroy'			=> false,
			'moonName'				=> NULL,
			'moonDestroyChance'		=> NULL,
			'moonDestroySuccess'	=> NULL,
			'fleetDestroyChance'	=> NULL,
			'fleetDestroySuccess'	=> NULL,
		);
		
		$randChance	= mt_rand(1, 100);
		if ($randChance <= $chanceCreateMoon)
		{
			$LNG					= $this->getLanguage($targetUser['lang']);
			$reportInfo['moonName']	= $LNG['type_planet_3'];
			
			PlayerUtil::createMoon(
				$this->_fleet['fleet_universe'],
				$this->_fleet['fleet_end_galaxy'],
				$this->_fleet['fleet_end_system'],
				$this->_fleet['fleet_end_planet'],
				$targetUser['id'],
				$chanceCreateMoon
			);
			
			if(Config::get($this->_fleet['fleet_universe'])->debris_moon == 1)
			{
				foreach($debrisResource as $elementID)
				{
					$planetDebris[$elementID]	= 0;
				}
			}
		}
		
		require_once 'includes/classes/missions/functions/GenerateReport.php';
		$reportData	= GenerateReport($combatResult, $reportInfo);
		
		switch($combatResult['won'])
		{
			case "a":
				// Win
				$attackStatus	= 'wons';
				$defendStatus	= 'loos';
				$class			= array('raportWin', 'raportLose');
				break;
			case "r":
				// Lose
				$attackStatus	= 'loos';
				$defendStatus	= 'wons';
				$class			= array('raportLose', 'raportWin');
				break;
			case "w":
			default:
				// Draw
				$attackStatus	= 'draws';
				$defendStatus	= 'draws';
				$class			= array('raportDraw', 'raportDraw');
				break;
		}
		
		$reportID	= md5(uniqid('', true).TIMESTAMP);
		
		$sql	= 'INSERT INTO %%RW%% SET
		rid 		= :reportId,
		raport 		= :reportData,
		time 		= :time,
		attacker	= :attackers,
		defender	= :defenders;';

		$db->insert($sql, array(
			':reportId'		=> $reportID,
			':reportData'	=> serialize($reportData),
			':time'			=> $this->_fleet['fleet_start_time'],
			':attackers'	=> implode(',', array_keys($userAttack)),
			':defenders'	=> implode(',', array_keys($userDefend))
		));

		$i = 0;

		foreach(array($userAttack, $userDefend) as $data)
		{
			foreach($data as $userID => $userName)
			{
				$LNG = $this->getLanguage(NULL, $userID);
				$targetInfo = array(
					'name'        => $targetPlanet['name'] ?? '',
					'galaxy'      => $this->_fleet['fleet_end_galaxy'],
					'system'      => $this->_fleet['fleet_end_system'],
					'planet'      => $this->_fleet['fleet_end_planet'],
					'planet_type' => $this->_fleet['fleet_end_type'],
					'image'       => $targetPlanet['image'] ?? 'normaltempplanet01'
				);
				$isAttacker = ($i === 0);
				$message = MessageTemplateHelper::buildCombatCard(
					$reportID,
					$combatResult['won'],
					$targetInfo,
					$combatResult['unitLost'],
					$stealResource,
					$debris,
					array(
						'created' => ($randChance <= $chanceCreateMoon),
						'chance'  => $chanceCreateMoon,
						'name'    => $reportInfo['moonName'] ?? ''
					),
					$isAttacker,
					$LNG
				);

				PlayerUtil::sendMessage($userID, 0, $LNG['sys_mess_tower'], 3, $LNG['sys_mess_attack_report'],
					$message, $this->_fleet['fleet_start_time'], NULL, 1, $this->_fleet['fleet_universe']);

				$sql	= "INSERT INTO %%TOPKB_USERS%% SET
				rid			= :reportId,
				role		= :userRole,
				username	= :username,
				uid			= :userId;";

				$db->insert($sql, array(
					':reportId'	=> $reportID,
					':userRole'	=> $i + 1,
					':username'	=> $userName,
					':userId'	=> $userID
				));
			}

			$i++;
		}
		
		if($this->_fleet['fleet_end_type'] == 3)
		{
			$debrisType	= 'id_luna';
		}
		else
		{
			$debrisType	= 'id';
		}
		
		$sql = 'UPDATE %%PLANETS%% SET
		der_metal	= :metal,
		der_crystal	= :crystal
		WHERE '.$debrisType.' = :planetId;';

		$db->update($sql, array(
			':metal'	=> $planetDebris[901],
			':crystal'	=> $planetDebris[902],
			':planetId'	=> $this->_fleet['fleet_end_id']
		));

		$sql = 'UPDATE %%PLANETS%% SET
		metal		= metal - :metal,
		crystal		= crystal - :crystal,
		deuterium	= deuterium - :deuterium
		WHERE id = :planetId;';

		$db->update($sql, array(
			':metal'		=> $stealResource[901],
			':crystal'		=> $stealResource[902],
			':deuterium'	=> $stealResource[903],
			':planetId'		=> $this->_fleet['fleet_end_id']
		));

		$topkbResult = $combatResult['won'];
		if ($topkbResult === 'w') {
			$attackerLost = $combatResult['unitLost']['attacker'] ?? 0;
			$defenderLost = $combatResult['unitLost']['defender'] ?? 0;
			$topkbResult  = ($attackerLost <= $defenderLost) ? 'a' : 'r';
		}

		$sql = 'INSERT INTO %%TOPKB%% SET
		units 		= :units,
		rid			= :reportId,
		time		= :time,
		universe	= :universe,
		result		= :result;';

		$db->insert($sql, array(
			':units'	=> $combatResult['unitLost']['attacker'] + $combatResult['unitLost']['defender'],
			':reportId'	=> $reportID,
			':time'		=> $this->_fleet['fleet_start_time'],
			':universe'	=> $this->_fleet['fleet_universe'],
			':result'	=> $topkbResult
		));

		$sql = 'UPDATE %%USERS%% SET
		`'.$attackStatus.'` = `'.$attackStatus.'` + 1,
		kbmetal		= kbmetal + :debrisMetal,
		kbcrystal	= kbcrystal + :debrisCrystal,
		lostunits	= lostunits + :lostUnits,
		desunits	= desunits + :destroyedUnits
		WHERE id IN ('.implode(',', array_keys($userAttack)).');';

		$db->update($sql, array(
			':debrisMetal'		=> $debris[901],
			':debrisCrystal'	=> $debris[902],
			':lostUnits'		=> $combatResult['unitLost']['attacker'],
			':destroyedUnits'	=> $combatResult['unitLost']['defender']
	  	));

		$sql = 'UPDATE %%USERS%% SET
		`'.$defendStatus.'` = `'.$defendStatus.'` + 1,
		kbmetal		= kbmetal + :debrisMetal,
		kbcrystal	= kbcrystal + :debrisCrystal,
		lostunits	= lostunits + :lostUnits,
		desunits	= desunits + :destroyedUnits
		WHERE id IN ('.implode(',', array_keys($userDefend)).');';

		$db->update($sql, array(
			':debrisMetal'		=> $debris[901],
			':debrisCrystal'	=> $debris[902],
			':lostUnits'		=> $combatResult['unitLost']['defender'],
			':destroyedUnits'	=> $combatResult['unitLost']['attacker']
		));

		// Hook for NovaRush Bot AI: Post-Combat Learning & Telemetry
		if (file_exists('includes/classes/bot/Perception/IntelStore.class.php')) {
			try {
				$botOwnerId = (int)$this->_fleet['fleet_owner'];
				$dbInst = Database::get();
				$isBot = $dbInst->selectSingle("SELECT bot_id FROM " . DB_PREFIX . "bots WHERE bot_id = :id AND is_active = 1;", array(':id' => $botOwnerId));

				if (!empty($isBot)) {
					// 1. Extract confirmed enemy forces seen during battle
					$lastRound = !empty($reportData['rounds']) ? end($reportData['rounds']) : array();
					$observedDefenses = array();
					$observedFleets   = array();

					// Global reslist for separating fleets and defenses
					global $reslist;
					$fleetIds = (isset($reslist['fleet']) && is_array($reslist['fleet'])) ? $reslist['fleet'] : array();

					// Surviving defender units from the final round
					if (!empty($lastRound['defender'])) {
						foreach ($lastRound['defender'] as $dGroup) {
							if (empty($dGroup['ships'])) continue;
							foreach ($dGroup['ships'] as $uId => $uData) {
								$cnt = (int)$uData[0];
								if ($cnt <= 0) continue;
								if (in_array($uId, $fleetIds)) {
									$observedFleets[$uId] = ($observedFleets[$uId] ?? 0) + $cnt;
								} else {
									$observedDefenses[$uId] = ($observedDefenses[$uId] ?? 0) + $cnt;
								}
							}
						}
					}

					// Resources remaining on target (subtract looted resources)
					$remMetal = max(0, (float)$targetPlanet['metal'] - (float)($stealResource[901] ?? 0));
					$remCry   = max(0, (float)$targetPlanet['crystal'] - (float)($stealResource[902] ?? 0));
					$remDeut  = max(0, (float)$targetPlanet['deuterium'] - (float)($stealResource[903] ?? 0));

					// Save 100% accurate combat intelligence (defense_scouted = true)
					require_once 'includes/classes/bot/Perception/IntelStore.class.php';
					$bStore = new BotIntelStore($botOwnerId);
					$bStore->saveIntel(
						(int)$this->_fleet['fleet_end_id'],
						(int)$this->_fleet['fleet_target_owner'],
						(int)$this->_fleet['fleet_end_galaxy'],
						(int)$this->_fleet['fleet_end_system'],
						(int)$this->_fleet['fleet_end_planet'],
						(int)$this->_fleet['fleet_end_type'],
						array(901 => $remMetal, 902 => $remCry, 903 => $remDeut),
						$observedFleets,
						$observedDefenses,
						null,
						null
					);

					// 2. If the bot suffered a defeat / wipeout ('r'), release target lock
					if ($combatResult['won'] === 'r') {
						$tG = (int)$this->_fleet['fleet_end_galaxy'];
						$tS = (int)$this->_fleet['fleet_end_system'];
						$tP = (int)$this->_fleet['fleet_end_planet'];

						$dbInst->update("UPDATE " . DB_PREFIX . "bots SET 
							target_galaxy = 0, 
							target_system = 0, 
							target_planet = 0, 
							target_type = '', 
							target_initial_mse = 0, 
							siege_cycles = 0 
							WHERE bot_id = :id 
							  AND target_galaxy = :g 
							  AND target_system = :s 
							  AND target_planet = :p;", array(
							':id' => $botOwnerId,
							':g'  => $tG,
							':s'  => $tS,
							':p'  => $tP,
						));

						// Record telemetry in decision log if available
						if (file_exists('includes/classes/bot/Telemetry/DecisionLog.class.php')) {
							require_once 'includes/classes/bot/Telemetry/DecisionLog.class.php';
							$dLog = new BotDecisionLog($botOwnerId);
							$dLog->record(
								'combat_wipeout_release',
								"Fleet destroyed in action against [{$tG}:{$tS}:{$tP}]. Target lock released, defenses updated in intelligence store.",
								array('galaxy' => $tG, 'system' => $tS, 'planet' => $tP, 'rid' => $reportID),
								"wipeout_{$tG}_{$tS}_{$tP}",
								-5000.0
							);
						}
					}

					// 3. NovaRush Bot Anti-Bashing & Target Rotation Blacklist
					// If the bot achieved a successful victory ('a'), record victory
					if ($combatResult['won'] === 'a') {
						require_once 'includes/classes/bot/Military/TargetBlacklist.class.php';
						foreach ($userAttack as $atkUserId => $atkUserName) {
							$atkBot = $dbInst->selectSingle("SELECT bot_id FROM " . DB_PREFIX . "bots WHERE bot_id = :id AND is_active = 1;", array(':id' => (int)$atkUserId));
							if (!empty($atkBot)) {
								BotTargetBlacklist::recordAttackVictory(
									(int)$atkUserId,
									(int)$this->_fleet['fleet_target_owner'],
									(int)$this->_fleet['fleet_end_id'],
									(int)$this->_fleet['fleet_end_galaxy'],
									(int)$this->_fleet['fleet_end_system'],
									(int)$this->_fleet['fleet_end_planet'],
									TIMESTAMP
								);
							}
						}
					}
				}
			} catch (\Throwable $e) {
				// Suppress exceptions to guarantee game mission execution never fails
			}
		}

		$this->setState(FLEET_RETURN);
		$this->SaveFleet();
	}
	
	function EndStayEvent()
	{
		return;
	}
	
	function ReturnEvent()
	{
		$LNG		= $this->getLanguage(NULL, $this->_fleet['fleet_owner']);


		$sql		= 'SELECT name FROM %%PLANETS%% WHERE id = :planetId;';
		$planetName	= Database::get()->selectSingle($sql, array(
			':planetId'	=> $this->_fleet['fleet_start_id'],
		), 'name');

		$origin = array(
			'name'   => 'Destino',
			'galaxy' => $this->_fleet['fleet_end_galaxy'],
			'system' => $this->_fleet['fleet_end_system'],
			'planet' => $this->_fleet['fleet_end_planet']
		);
		$target = array(
			'name'   => $planetName,
			'galaxy' => $this->_fleet['fleet_start_galaxy'],
			'system' => $this->_fleet['fleet_start_system'],
			'planet' => $this->_fleet['fleet_start_planet']
		);
		$resources = array(
			901 => $this->_fleet['fleet_resource_metal'],
			902 => $this->_fleet['fleet_resource_crystal'],
			903 => $this->_fleet['fleet_resource_deuterium']
		);

		$Message = MessageTemplateHelper::buildLogisticsCard(
			'return',
			$LNG['sys_mess_fleetback'],
			$origin,
			$target,
			$resources,
			$LNG
		);

		PlayerUtil::sendMessage($this->_fleet['fleet_owner'], 0, $LNG['sys_mess_tower'], 4, $LNG['sys_mess_fleetback'],
			$Message, $this->_fleet['fleet_end_time'], NULL, 1, $this->_fleet['fleet_universe']);

		$this->RestoreFleet();
	}
}

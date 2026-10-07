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

class BuildFunctions
{

    static $bonusList	= array(
    	'AttackA',
		'DefensiveA', 
		'ShieldA', 	
		'AttackD',
		'DefensiveD', 
		'ShieldD',
        'Attack',
        'AttackSlaser',
		'AttackSion',
		'AttackSplasma',
		'AttackSgravity',
        'Defensive',
        'DefensiveSlight',
		'DefensiveSmedium',
		'DefensiveSheavy',
        'Shield',
        'ShieldSlight',
		'ShieldSmedium',
		'ShieldSheavy',
        'Sbuild',
		'BuildSlots',	
        'Stech',
		'ResearchSlots',
		'ResearchSlotPlanet',
        'Sfleet',
        'Sdefense',
        'Smissile',
        'Resource',
        'Pmetal',
        'Pcrystal',
        'Pdeuterium',   
        'Senergy',          
        'ResourceStorage',
        'ShipStorage',
        'FlyTime',
        'FlyTimeCom',
		'FlyTimeImp',
		'FlyTimeHyp',
        'FleetSlots',
        'Planets',
        'SpyPower',
        'Expedition',
        'GateCoolTime',
        'MoreFound',
        'CostRbuild',
        'CostRfleet',
        'CostRtech',
        'CostRdefense',
        'CostRmissile',
        'DoubleAttack',
	    'DoubleShield',
        'DoubleDefensive',
		'DoubleAttackBonus',
		'DoubleShieldBonus',
        'DoubleDefensiveBonus',
        'Debris',
        'DefRecovery',
        'Focusing',
		'AntiFocusing',
		'AccurateShots',
		'ChainReaction',
		'ExpBoost',
        'FuelConsum',
        'ShieldDome',
		'OrbitalBases',
    );

    public static function getBonusList()
    {
        return self::$bonusList;
    }

    public static function getRestPrice($USER, $PLANET, $Element, $elementPrice = NULL)
    {
        global $resource;

        if(!isset($elementPrice)) {
            $elementPrice	= self::getElementPrice($USER, $PLANET, $Element);
        }

        $overflow	= array();

        foreach ($elementPrice as $resType => $resPrice) {
            $available			= isset($PLANET[$resource[$resType]]) ? $PLANET[$resource[$resType]] : $USER[$resource[$resType]];
            $overflow[$resType] = max($resPrice - floor($available), 0);
        }

        return $overflow;
    }

    public static function getElementPrice($USER, $PLANET, $Element, $forDestroy = false, $forLevel = NULL) {
        global $pricelist, $resource, $reslist;

        if (in_array($Element, $reslist['fleet']) || in_array($Element, $reslist['defense']) || in_array($Element, $reslist['missile'])) {
            $elementLevel = $forLevel;
        } elseif (isset($forLevel)) {
            $elementLevel = $forLevel;
        } elseif (isset($PLANET[$resource[$Element]])) {
            $elementLevel = $PLANET[$resource[$Element]];
        } elseif (isset($USER[$resource[$Element]])) {
            $elementLevel = $USER[$resource[$Element]];
        } else {
            return array();
        }

        $price	= array();
        foreach ($reslist['ressources'] as $resType)
        {
            if (!isset($pricelist[$Element]['cost'][$resType])) {
                continue;
            }
            $ressourceAmount	= $pricelist[$Element]['cost'][$resType];
            
            if(in_array($Element, $reslist['build'])) {
                $ressourceAmount	= $ressourceAmount - ($ressourceAmount * $USER['factor']['CostRbuild']);
			}elseif(in_array($Element, $reslist['fleet'])) {
                $ressourceAmount	= $ressourceAmount - ($ressourceAmount * $USER['factor']['CostRfleet']);
			}elseif(in_array($Element, $reslist['tech'])) {
                $ressourceAmount	= $ressourceAmount - ($ressourceAmount * $USER['factor']['CostRtech']);
			}elseif(in_array($Element, $reslist['defense'])) {
                $ressourceAmount	= $ressourceAmount - ($ressourceAmount * $USER['factor']['CostRdefense']);
			}elseif(in_array($Element, $reslist['missile'])) {
                $ressourceAmount	= $ressourceAmount - ($ressourceAmount * $USER['factor']['CostRmissile']);
			}

            if ($ressourceAmount == 0) {
                continue;
            }

            $price[$resType]	= $ressourceAmount;

            $resFactor = isset($pricelist[$Element]['factor'.$resType]) ? $pricelist[$Element]['factor'.$resType] : ($pricelist[$Element]['factor'] ?? 1);
            if($resFactor != 0 && $resFactor != 1) {
                $price[$resType]	*= pow($resFactor, $elementLevel);
            }

            if($forLevel && (in_array($Element, $reslist['fleet']) || in_array($Element, $reslist['defense']) || in_array($Element, $reslist['missile']))) {
                $price[$resType]	*= $elementLevel;
            }

            if($forDestroy === true) {
                $price[$resType]	/= 2;
            }
        }

        return $price;
    }
    
    public static function isTechnologieAccessible($USER, $PLANET, $Element, $ReqArray)
    {
        global $requeriments, $pricelist, $resource;

        if(!isset($requeriments[$Element]))
            return true;
        
        $factor = $pricelist[$Element]['factorTechnologie'];

        foreach($requeriments[$Element] as $ReqElement => $EleLevel)
        {
            if((in_array($ReqElement, $ReqArray)) || ($ReqArray == array())){
                if (
                    (isset($USER[$resource[$ReqElement]]) && isset($USER[$resource[$Element]]) && $USER[$resource[$ReqElement]] < ceil($EleLevel * (eval("return $factor;")))) ||
                    (isset($PLANET[$resource[$ReqElement]]) && isset($USER[$resource[$Element]]) && $PLANET[$resource[$ReqElement]] < ceil($EleLevel * (eval("return $factor;")))) ||
                    (isset($USER[$resource[$ReqElement]]) && isset($PLANET[$resource[$Element]]) && $USER[$resource[$ReqElement]] < ceil($EleLevel * (eval("return $factor;")))) ||
                    (isset($PLANET[$resource[$ReqElement]]) && isset($PLANET[$resource[$Element]]) && $PLANET[$resource[$ReqElement]] < ceil($EleLevel * (eval("return $factor;"))))
                ) {
                    return false;
                }
            }
        }
        return true;
    }
    
    public static function isTechnologieSorting($USER, $PLANET, $Element, $ReqArray)
    {
        global $requeriments, $pricelist, $resource;

        if(!isset($requeriments[$Element]))
            return true;
        
        $factor = $pricelist[$Element]['factorTechnologie'];

        foreach($requeriments[$Element] as $ReqElement => $EleLevel)
        {
            if(in_array($ReqElement, $ReqArray) || $ReqArray == 0){
                if (
                    (isset($USER[$resource[$ReqElement]]) && isset($USER[$resource[$Element]]) && $USER[$resource[$ReqElement]] < ceil($EleLevel * (eval("return $factor;")))) ||
                    (isset($PLANET[$resource[$ReqElement]]) && isset($USER[$resource[$Element]]) && $PLANET[$resource[$ReqElement]] < ceil($EleLevel * (eval("return $factor;")))) ||
                    (isset($USER[$resource[$ReqElement]]) && isset($PLANET[$resource[$Element]]) && $USER[$resource[$ReqElement]] < ceil($EleLevel * (eval("return $factor;")))) ||
                    (isset($PLANET[$resource[$ReqElement]]) && isset($PLANET[$resource[$Element]]) && $PLANET[$resource[$ReqElement]] < ceil($EleLevel * (eval("return $factor;"))))
                ) {
                    return false;
                }
            }
        }
        return true;
    }

    public static function getBuildingTime($USER, $PLANET, $Element, $elementPrice = NULL, $forDestroy = false, $forLevel = NULL)
    {
        global $resource, $reslist, $requeriments, $resglobal;

        $config	        = Config::get();
        $time           = 0;
        $elementCost	= 0;
        
        if(!isset($elementPrice)) {
            $elementPrice	= self::getElementPrice($USER, $PLANET, $Element, $forDestroy, $forLevel);
        }
        
        foreach($reslist['build_speed_res'] as $res) 
        {
            if(isset($elementPrice[$res])) {
                $elementCost	+= pow($elementPrice[$res], 0.3)/0.003;
            }
        }

        if(in_array($Element, $reslist['build'])) {		
			$speedFactor = 1 + max(0, (float)(isset($USER['factor']['Sbuild']) ? $USER['factor']['Sbuild'] : 0));
			$time	= $elementCost/($config->game_speed * (1 + $PLANET[$resource[$resglobal['build_speed']]]) * $speedFactor);
		}elseif (in_array($Element, $reslist['fleet'])) {			
			$speedFactor = 1 + max(0, (float)(isset($USER['factor']['Sfleet']) ? $USER['factor']['Sfleet'] : 0));
			$time	= $elementCost/($config->game_speed * (1 + $PLANET[$resource[$resglobal['fleet_speed']]]) * $speedFactor);			
		}elseif (in_array($Element, $reslist['defense'])) {
			$speedFactor = 1 + max(0, (float)(isset($USER['factor']['Sfleet']) ? $USER['factor']['Sfleet'] : 0));
			$time	= $elementCost/($config->game_speed * (1 + $PLANET[$resource[$resglobal['defense_speed']]]) * $speedFactor);	
        }elseif (in_array($Element, $reslist['missile'])) {
			$speedFactor = 1 + max(0, (float)(isset($USER['factor']['Sfleet']) ? $USER['factor']['Sfleet'] : 0));
			$time	= $elementCost/($config->game_speed * (1 + $PLANET[$resource[$resglobal['missile_speed']]]) * $speedFactor);	
        }elseif (in_array($Element, $reslist['tech'])) {
            $interKey = $resource[$resglobal['tech_speed']].'_inter';
            if (!isset($PLANET[$interKey])) {
                $PLANET[$interKey] = ResourceUpdate::getNetworkLevel($USER, $PLANET);
            }

            if(is_numeric($PLANET[$interKey]))
            {
                $Level	= $PLANET[$resource[$resglobal['tech_speed']]];
            } else {
                $Level = 0;
                if (is_array($PLANET[$interKey])) {
                    foreach($PLANET[$interKey] as $Levels)
                    {
                        if(!isset($requeriments[$Element][$resglobal['tech_speed']]) || $Levels >= $requeriments[$Element][$resglobal['tech_speed']])
                            $Level += $Levels;
                    }
                }
            }

            $speedFactor = 1 + max(0, (float)(isset($USER['factor']['Stech']) ? $USER['factor']['Stech'] : 0));
            $time	= ($elementCost / (100 * (1 + $Level))/($config->game_speed / 2500)) / $speedFactor;
        }

        if((in_array($Element, $reslist['fleet']) || in_array($Element, $reslist['defense']) || in_array($Element, $reslist['missile']))){
			$time	= $time * 3600;
		}	
		else{
			if($forDestroy) {
				$time	= floor($time * 1300);
			} else {
				$time	= floor($time * 3600);
			}
			$time	 = max($time, $config->min_build_time);
		}		
		return $time; 
    }

    public static function isElementBuyable($USER, $PLANET, $Element, $elementPrice = NULL, $forDestroy = false, $forLevel = NULL)
    {
        $rest	= self::getRestPrice($USER, $PLANET, $Element, $elementPrice, $forDestroy, $forLevel);
        return count(array_filter($rest)) === 0;
    }

    public static function getMaxConstructibleElements($USER, $PLANET, $Element, $elementPrice = NULL)
    {
        global $resource, $reslist;

        if(!isset($elementPrice)) {
            $elementPrice	= self::getElementPrice($USER, $PLANET, $Element);
        }

        $maxElement	= array();

        foreach($elementPrice as $resourceID => $price)
        {
            if(isset($PLANET[$resource[$resourceID]]))
            {
                $maxElement[]	= floor($PLANET[$resource[$resourceID]] / $price);
            }
            elseif(isset($USER[$resource[$resourceID]]))
            {
                $maxElement[]	= floor($USER[$resource[$resourceID]] / $price);
            }
            else
            {
                throw new Exception("Unknown Ressource ".$resourceID." at element ".$Element.".");
            }
        }

        if(in_array($Element, $reslist['one'])) {
            $maxElement[]	= 1;
        }

        return min($maxElement);
    }

    public static function getMaxConstructibleRockets($USER, $PLANET, $Missiles = NULL)
    {
        global $resource, $reslist, $resglobal;

        if(!isset($Missiles)){
            $Missiles	= array();

            foreach($reslist['missile'] as $elementID){
                $Missiles[$elementID]	= $PLANET[$resource[$elementID]];
            }
        }

        $BuildArray  	  	= !empty($PLANET['b_hangar_id']) ? unserialize($PLANET['b_hangar_id']) : array();
        $MaxMissiles   		= $resglobal['missile_speed'] * max(Config::get()->silo_factor, 1);

        foreach($BuildArray as $ElementArray) {
            if(isset($Missiles[$ElementArray[0]]))
                $Missiles[$ElementArray[0]] += $ElementArray[1];
        }
        
        $ActuMissiles  = 0;
        foreach($reslist['missile'] as $elementID) {
            $ActuMissiles  += $Missiles[$elementID];
		}
        
        foreach($reslist['missile'] as $elementID) {
            $MissilesElement  = max(0, $MaxMissiles - $ActuMissiles);
		}
        
        $MissilesTotal = array();
        foreach($reslist['missile'] as $elementID) {
            $MissilesTotal	+= array(
                $elementID  => $MissilesElement,
            );
		}
        
        return $MissilesTotal;
    }
    
    public static function getMaxConstructibleDomes($USER, $PLANET, $Domes = NULL)
	{
		global $resource, $reslist;
        
		if(!isset($Domes)){		
            $Domes	= array();
            
            foreach($reslist['domes'] as $elementID){
                $Domes[$elementID]	= isset($PLANET[$resource[$elementID]]) ? $PLANET[$resource[$elementID]] : 0;
            }
		}
        
		$BuildArray = !empty($PLANET['b_hangar_id']) ? unserialize($PLANET['b_hangar_id']) : array();
		$MaxDomes = 25 + (isset($USER['factor']['ShieldDome']) ? $USER['factor']['ShieldDome'] : 0);
        
		foreach($BuildArray as $ElementArray) {
			if(isset($Domes[$ElementArray[0]]))
				$Domes[$ElementArray[0]] += $ElementArray[1];
		}
        
        foreach($reslist['domes'] as $elementID) {
            $curCount = isset($Domes[$elementID]) ? $Domes[$elementID] : 0;
            $DomesElement[$elementID]  = max(0, $MaxDomes - $curCount);
		}
        
        $DomesTotal = array();
        foreach($reslist['domes'] as $elementID) {
            $DomesTotal	+= array(
                $elementID  => $DomesElement[$elementID],
            );
		}
        
        return $DomesTotal;
	}
	
	public static function getMaxConstructibleOrbits($USER, $PLANET, $Orbits = NULL)
	{
		global $resource, $reslist;
        
		if(!isset($Orbits)){	
            $Orbits	= array();
            
            foreach($Orbits as $elementID){
                $Orbits[$elementID]	= $PLANET[$resource[$elementID]];
            }
		}
        
		$BuildArray  	  	= !empty($PLANET['b_hangar_id']) ? unserialize($PLANET['b_hangar_id']) : array();
		$MaxOrbits   		= 250 + $USER['factor']['OrbitalBases'];
        
		foreach($BuildArray as $ElementArray) {
			if(isset($Orbits[$ElementArray[0]]))
				$Orbits[$ElementArray[0]] += $ElementArray[1];
		}
        
        foreach($reslist['orbital_bases'] as $elementID) {
            $OrbitsElement[$elementID]  = max(0, $MaxOrbits - $Orbits[$elementID]);
		}
        
        $OrbitsTotal = array();
        foreach($reslist['orbital_bases'] as $elementID) {
            $OrbitsTotal	+= array(
                $elementID  => $OrbitsElement[$elementID],
            );
		}
        
        return $OrbitsTotal;
	}

    public static function getAvalibleBonus($Element)
    {
        global $pricelist;

        $elementBonus	= array();

        foreach(self::$bonusList as $bonus)
        {
            if (array_key_exists(''.$bonus.'', $pricelist[$Element]['bonus'])) {
                $temp	= (float) $pricelist[$Element]['bonus'][$bonus][0];
                if(empty($temp))
                {
                    continue;
                }

                $elementBonus[$bonus]	= $pricelist[$Element]['bonus'][$bonus];
            }
        }

        return $elementBonus;
    }
    
    public static function requirementsList($USER, $PLANET, $Element)
    {
        global $pricelist, $reslist, $resource, $requeriments;
        
        $factor = $pricelist[$Element]['factorTechnologie'];

        $requirementsList	 = array();
        if(isset($requeriments[$Element]))
        {
            foreach($requeriments[$Element] as $requireID => $RedCount)
            {
                $requirementsList[$requireID]	= array(
                    'count' => ceil($RedCount * (eval("return $factor;"))),
                    'own'   => (isset($PLANET[$resource[$requireID]]) ? $PLANET[$resource[$requireID]] : $USER[$resource[$requireID]])
                );
            }
        }
        
        $techTreeList[$Element]	= $requirementsList;

        return $techTreeList;
    }
    
    public static function bonusElementList($Element)
    {
        global $USER, $PLANET, $pricelist, $reslist, $resource, $BonusElement;

        $bonusElementList	 = array();
        if(isset($BonusElement[$Element]))
        {
            foreach($BonusElement[$Element] as $ID => $Count)
            {
                $bonusElementList[$ID]	= array(
                    'count' => $Count,
                    'own'   => (isset($PLANET[$resource[$ID]]) ? $PLANET[$resource[$ID]] : $USER[$resource[$ID]])
                );
            }
        }
        
        $bonusIDList[$Element]	= $bonusElementList;

        return $bonusIDList;
    }
    
    public static function getInstantMSE($Element)
    {
        global $pricelist;
        if (empty($pricelist)) {
            $cache = Cache::get();
            $cache->add('vars', 'VarsBuildCache');
            $vars = $cache->getData('vars');
            if (isset($vars['pricelist'])) {
                $GLOBALS['pricelist'] = $vars['pricelist'];
                $pricelist = $vars['pricelist'];
            }
        }
        $costMetal   = (float) ($pricelist[$Element]['cost'][901] ?? 0);
        $costCrystal = (float) ($pricelist[$Element]['cost'][902] ?? 0);
        $costDeut    = (float) ($pricelist[$Element]['cost'][903] ?? 0);
        
        return $costMetal + (2.0 * $costCrystal) + (4.0 * $costDeut);
    }
    
    public static function getInstantPriceFleetDefense($Element)
    {
        $mse = self::getInstantMSE($Element);
        return (int) max(1, ceil($mse / 200.0));
    }
    
    public static function getInstantPriceLevel($Element, $Level)
    {
        global $pricelist;
        if (empty($pricelist)) {
            $cache = Cache::get();
            $cache->add('vars', 'VarsBuildCache');
            $vars = $cache->getData('vars');
            if (isset($vars['pricelist'])) {
                $GLOBALS['pricelist'] = $vars['pricelist'];
                $pricelist = $vars['pricelist'];
            }
        }
        $baseMSE = self::getInstantMSE($Element);
        $baseMO  = $baseMSE / 250.0;
        $factor  = (float) ($pricelist[$Element]['factor'] ?? 1.0);
        
        $lvl = max(1, (int)$Level);
        return (int) max(15, ceil($baseMO * pow($factor, $lvl - 1)));
    }
    
    public static function getInstantPriceTotalLevels($Element, $CurrentLevel, $Count)
    {
        $count = max(1, (int)$Count);
        $curLvl = max(0, (int)$CurrentLevel);
        $total = 0;
        for ($i = 1; $i <= $count; $i++) {
            $total += self::getInstantPriceLevel($Element, $curLvl + $i);
        }
        return $total;
    }
    
    public static function instantPurchasePrice($Element)
	{
		global $reslist;
        if (in_array($Element, $reslist['fleet']) || in_array($Element, $reslist['defense'])) {
            return self::getInstantPriceFleetDefense($Element);
        }
        return self::getInstantPriceLevel($Element, 1);
	}
    
    public static function resourcesPoints($USER, $Element)
    {
        global $resource, $reslist, $pricelist;

        $resourcesPoints = 0;
        foreach($reslist['resources_points'] as $res) 
        {
            if(isset($pricelist[$Element]['cost'][$res])) {
                $resourcesPoints += $pricelist[$Element]['cost'][$res];
            }
        }

        return $resourcesPoints;
    }
}
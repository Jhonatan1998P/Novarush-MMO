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

class ResourceUpdate
{

	/**
	 * reference of the config object
	 * @var Config
	 */
	private $config			= NULL;

	private $isGlobalMode 	= NULL;
	private $TIME			= NULL;
	private $HASH			= NULL;
	private $ProductionTime	= NULL;

	private $PLANET			= array();
	private $USER			= array();
	private $Builded		= array();

	function __construct($Build = true, $Tech = true)
	{
		$this->Build	= $Build;
		$this->Tech		= $Tech;
	}

	public function setData($USER, $PLANET)
	{
		$this->USER		= $USER;
		$this->PLANET	= $PLANET;
	}

	public function getData()
	{
		return array($this->USER, $this->PLANET);
	}
	
	public function ReturnVars() {
		if($this->isGlobalMode)
		{
			$GLOBALS['USER']	= $this->USER;
			$GLOBALS['PLANET']	= $this->PLANET;
			return true;
		} else {
			return array($this->USER, $this->PLANET);
		}
	}
	
	public function CreateHash() {
		global $reslist, $resource;
		$Hash	= array();
		foreach($reslist['prod'] as $ID) {
			$Hash[]	= $this->PLANET[$resource[$ID]] ?? 0;
			$Hash[]	= $this->PLANET[$resource[$ID].'_porcent'] ?? 10;
		}
		
		$ressource	= array_merge(array(), $reslist['resstype'][1], $reslist['resstype'][2]);
		foreach($ressource as $ID) {
			$Hash[]	= $this->config->{$resource[$ID].'_basic_income'} ?? 0;
		}
		// $new_code
		foreach($reslist['resstype'][1] as $resP) {
            $Hash[]	= $this->USER['factor']['P'.$resource[$resP]] ?? 0;
		}
        
        foreach($reslist['resstype'][2] as $resS) {
            $Hash[]	= $this->USER['factor']['S'.$resource[$resS]] ?? 0;
		}
        // $new_code
		$Hash[]	= $this->config->resource_multiplier;
		$Hash[]	= $this->config->storage_multiplier;
		$Hash[]	= $this->config->energySpeed;
		$Hash[]	= $this->USER['factor']['Resource'] ?? 0;
        // $old_code
		//$Hash[]	= $this->PLANET[$resource[22]];
		//$Hash[]	= $this->PLANET[$resource[23]];
		//$Hash[]	= $this->PLANET[$resource[24]];
        // $old_code
        //$new_code
        foreach($reslist['storage'] as $storage) {
            $Hash[]	= $this->PLANET[$resource[$storage]] ?? 0;
		}
        //$new_code
		return md5(implode("::", $Hash));
	}
	
	public function CalcResource($USER = NULL, $PLANET = NULL, $SAVE = false, $TIME = NULL, $HASH = true)
	{			
		$this->isGlobalMode	= !isset($USER, $PLANET) ? true : false;
		$this->USER			= $this->isGlobalMode ? $GLOBALS['USER'] : $USER;
		$this->PLANET		= $this->isGlobalMode ? $GLOBALS['PLANET'] : $PLANET;
		$this->TIME			= is_null($TIME) ? (class_exists('BotClock') ? BotClock::now() : (defined('TIMESTAMP') ? TIMESTAMP : time())) : $TIME;
		$this->config		= Config::get($this->USER['universe']);
		
		if($this->USER['urlaubs_modus'] == 1)
			return $this->ReturnVars();
			
		if($this->Build)
		{
			$this->ShipyardQueue();
			if($this->Tech == true && !empty($this->USER['b_tech']) && $this->USER['b_tech'] < $this->TIME)
				$this->ResearchQueue();
			if(!empty($this->PLANET['b_building']))
				$this->BuildingQueue();
		}
		
		$this->UpdateResource($this->TIME, $HASH);
			
		if($SAVE === true)
			$this->SavePlanetToDB($this->USER, $this->PLANET);
			
		return $this->ReturnVars();
	}
	
	public function UpdateResource($TIME, $HASH = false)
	{
		$this->ProductionTime  			= ($TIME - $this->PLANET['last_update']);
		
		if($this->ProductionTime > 0)
		{
			$this->PLANET['last_update']	= $TIME;
			if($HASH === false) {
				$this->ReBuildCache();
			} else {
				$this->HASH		= $this->CreateHash();

				if($this->PLANET['eco_hash'] !== $this->HASH) {
					$this->PLANET['eco_hash'] = $this->HASH;
					$this->ReBuildCache();
				}
			}
			$this->ExecCalc();
		} else {
			if($HASH === false) {
				$this->ReBuildCache();
			} else {
				$this->HASH		= $this->CreateHash();
				if($this->PLANET['eco_hash'] !== $this->HASH) {
					$this->PLANET['eco_hash'] = $this->HASH;
					$this->ReBuildCache();
				}
			}
		}
	}
	
	private function ExecCalc()
	{
        global $reslist, $resource;
        
		if($this->PLANET['planet_type'] == 3)
			return;
        
        // $new_code
        foreach($reslist['resstype'][1] as $resP) //проверка всего масива элементов
		{
            $MaxStorage		    = $this->PLANET[''.$resource[$resP].'_max'] * $this->config->max_overflow;
            
            $Theoretical		= $this->ProductionTime * (($this->config->{$resource[$resP].'_basic_income'} * $this->config->resource_multiplier) + $this->PLANET[''.$resource[$resP].'_perhour']) / 3600;
            
            if($Theoretical < 0)
            {
			$this->PLANET[''.$resource[$resP].'']      = max($this->PLANET[''.$resource[$resP].''] + $Theoretical, 0);
            } 
            elseif ($this->PLANET[''.$resource[$resP].''] <= $MaxStorage)
            {
			$this->PLANET[''.$resource[$resP].'']      = min($this->PLANET[''.$resource[$resP].''] + $Theoretical, $MaxStorage);
            }
            
            $this->PLANET[''.$resource[$resP].'']	   = max($this->PLANET[''.$resource[$resP].''], 0);
        }
        // $new_code
		/* $old_code
		$MaxMetalStorage		= $this->PLANET['metal_max']     * $this->config->max_overflow;
		$MaxCristalStorage		= $this->PLANET['crystal_max']   * $this->config->max_overflow;
		$MaxDeuteriumStorage	= $this->PLANET['deuterium_max'] * $this->config->max_overflow;
		
		$MetalTheoretical		= $this->ProductionTime * (($this->config->metal_basic_income * $this->config->resource_multiplier) + $this->PLANET['metal_perhour']) / 3600;
		
		if($MetalTheoretical < 0)
		{
			$this->PLANET['metal']      = max($this->PLANET['metal'] + $MetalTheoretical, 0);
		} 
		elseif ($this->PLANET['metal'] <= $MaxMetalStorage)
		{
			$this->PLANET['metal']      = min($this->PLANET['metal'] + $MetalTheoretical, $MaxMetalStorage);
		}
		
		$CristalTheoretical	= $this->ProductionTime * (($this->config->crystal_basic_income * $this->config->resource_multiplier) + $this->PLANET['crystal_perhour']) / 3600;
		if ($CristalTheoretical < 0)
		{
			$this->PLANET['crystal']      = max($this->PLANET['crystal'] + $CristalTheoretical, 0);
		} 
		elseif ($this->PLANET['crystal'] <= $MaxCristalStorage ) 
		{
			$this->PLANET['crystal']      = min($this->PLANET['crystal'] + $CristalTheoretical, $MaxCristalStorage);
		}
		
		$DeuteriumTheoretical	= $this->ProductionTime * (($this->config->deuterium_basic_income * $this->config->resource_multiplier) + $this->PLANET['deuterium_perhour']) / 3600;
		if ($DeuteriumTheoretical < 0)
		{
			$this->PLANET['deuterium']    = max($this->PLANET['deuterium'] + $DeuteriumTheoretical, 0);
		} 
		elseif($this->PLANET['deuterium'] <= $MaxDeuteriumStorage) 
		{
			$this->PLANET['deuterium']    = min($this->PLANET['deuterium'] + $DeuteriumTheoretical, $MaxDeuteriumStorage);
		}
		
		$this->PLANET['metal']		= max($this->PLANET['metal'], 0);
		$this->PLANET['crystal']	= max($this->PLANET['crystal'], 0);
		$this->PLANET['deuterium']	= max($this->PLANET['deuterium'], 0);
        $old_code */
	}
	
	public static function getProd($Calculation)
	{
		return 'return '.$Calculation.';';
	}
    // $new_code
	public static function getNetworkLevel($USER, $PLANET)
	{   
        global $resource, $reslist, $resglobal;
        
        $techField = $resource[$resglobal['tech_speed']] ?? 'tech';
        $planetTech = (float)($PLANET[$techField] ?? ($PLANET['tech'] ?? 0.0));
        $researchLevelList = array($planetTech);

        $limit = max(0, (int)($USER['factor']['ResearchSlotPlanet'] ?? 0));
        $planetId = (int)($PLANET['id'] ?? 0);
        $userId = (int)($USER['id'] ?? 0);

        if($limit > 0 && $planetId > 0 && $userId > 0)
        {
            $sql = 'SELECT '.$techField.' FROM %%PLANETS%% WHERE id != :planetId AND id_owner = :userId AND destruyed = 0 ORDER BY '.$techField.' DESC LIMIT :limit;';
            $researchResult = Database::get()->select($sql, array(
                ':limit'	=> $limit,
                ':planetId'	=> $planetId,
                ':userId'	=> $userId
            ));

            if (!empty($researchResult)) {
                foreach($researchResult as $researchRow)
                {
                    $researchLevelList[] = (float)($researchRow[$techField] ?? 0.0);
                }
            }
        }

		return $researchLevelList;
	}
    // $new_code
    /* $old_code
    public static function getNetworkLevel($USER, $PLANET)
	{
		global $resource;

		$researchLevelList	= array($PLANET[$resource[31]]);
		if($USER[$resource[123]] > 0)
		{
			$sql = 'SELECT '.$resource[31].' FROM %%PLANETS%% WHERE id != :planetId AND id_owner = :userId AND destruyed = 0 ORDER BY '.$resource[31].' DESC LIMIT :limit;';
			$researchResult = Database::get()->select($sql, array(
				':limit'	=> (int) $USER[$resource[123]],
				':planetId'	=> $PLANET['id'],
				':userId'	=> $USER['id']
			));

			foreach($researchResult as $researchRow)
			{
				$researchLevelList[]	= $researchRow[$resource[31]];
			}
		}
		
		return $researchLevelList;
	}
    $old_code */
	public function ReBuildCache()
	{
        // $new_code
		global $ProdGrid, $resource, $reslist, $resglobal;
        
        foreach($reslist['planet_no_basic'] as $planetNoBasic) 
        {
            if ($this->PLANET['planet_type'] == $planetNoBasic)
            {
                foreach(array_merge($reslist['resstype'][1], $reslist['resstype'][2]) as $res) 
                {
                    $this->config->{$resource[$res].'_basic_income'}     	= 0;
                }
            }
        }
        
        include('includes/subclasses/subclass.Temp.php');
        
        // $new_code
		/* $old_code
        global $ProdGrid, $resource, $reslist;
		
		if ($this->PLANET['planet_type'] == 3)
		{
			$this->config->metal_basic_income     	= 0;
			$this->config->crystal_basic_income   	= 0;
			$this->config->deuterium_basic_income 	= 0;
		}
        
		$temp	= array(
			901	=> array(
				'max'	=> 0,
				'plus'	=> 0,
				'minus'	=> 0,
			),
			902	=> array(
				'max'	=> 0,
				'plus'	=> 0,
				'minus'	=> 0,
			),
			903	=> array(
				'max'	=> 0,
				'plus'	=> 0,
				'minus'	=> 0,
			),
			911	=> array(
				'plus'	=> 0,
				'minus'	=> 0,
			)
		);
		$old_code */
		$BuildTemp		= $this->PLANET['temp_max'];
        /* $old_code
		$BuildEnergy	= $this->USER[$resource[113]];
		$old_code */
		foreach($reslist['storage'] as $ProdID)
		{
			foreach($reslist['resstype'][1] as $ID) 
			{
				if(!isset($ProdGrid[$ProdID]['storage'][$ID]))
					continue;
					
				$BuildLevel 		= (int)($this->PLANET[$resource[$ProdID]] ?? 0);
				$temp[$ID]['max']	+= round(eval(self::getProd($ProdGrid[$ProdID]['storage'][$ID])));
			}
		}
		
		$ressIDs	= array_merge(array(), $reslist['resstype'][1], $reslist['resstype'][2]);
		
		foreach($reslist['prod'] as $ProdID)
		{	
			$BuildLevelFactor	= (float)($this->PLANET[$resource[$ProdID].'_porcent'] ?? 10.0);
			$BuildLevel 		= (int)($this->PLANET[$resource[$ProdID]] ?? 0);
			
			foreach($ressIDs as $ID) 
			{
				if(!isset($ProdGrid[$ProdID]['production'][$ID]))
					continue;
				
				$Production	= eval(self::getProd($ProdGrid[$ProdID]['production'][$ID]));
				
				if($Production > 0) {					
					$temp[$ID]['plus']	+= $Production;
				} else {
					if(in_array($ID, $reslist['resstype'][1]) && (float)($this->PLANET[$resource[$ID]] ?? 0.0) == 0.0) {
						 continue;
					}
					
					$temp[$ID]['minus']	+= $Production;
				}
			}
		}
        
        // $new_code ! 
        // 1. Calculate secondary resources (energy, build, tech, fleet, defense, missile)
        foreach($reslist['resstype'][2] as $resS)
        {
            $sFactor = (float)($this->USER['factor']['S'.$resource[$resS]] ?? 0.0);
            $this->PLANET[$resource[$resS]] = max(0.0, round($temp[$resS]['plus'] * $this->config->energySpeed * (1 + $sFactor))); 
            $this->PLANET[$resource[$resS].'_used'] = $temp[$resS]['minus'] * $this->config->energySpeed;
        }

        // 2. Production level based on energy consumption vs production
        $stopResource = $resource[$resglobal['stop_product']] ?? 'energy';
        $energyUsed = abs((float)($this->PLANET[$stopResource.'_used'] ?? 0.0));
        $energyAvailable = (float)($this->PLANET[$stopResource] ?? 0.0);

        if ($energyUsed == 0.0) {
            $prodLevel = 1.0;
        } else {
            $prodLevel = min(1.0, max(0.0, $energyAvailable / $energyUsed));
        }

        // 3. Calculate primary resources (metal, crystal, deuterium)
        foreach($reslist['resstype'][1] as $resP)
        {
            $pFactor = (float)($this->USER['factor']['P'.$resource[$resP]] ?? 0.0);
            $resFactor = (float)($this->USER['factor']['Resource'] ?? 0.0);
            $storageFactor = (float)($this->USER['factor']['ResourceStorage'] ?? 0.0);

            $this->PLANET[$resource[$resP].'_max'] = $temp[$resP]['max'] * $this->config->storage_multiplier * (1 + $storageFactor);
            $this->PLANET[$resource[$resP].'_perhour'] = ($temp[$resP]['plus'] * (1 + $resFactor + $pFactor) * $prodLevel + $temp[$resP]['minus']) * $this->config->resource_multiplier;  
        }
       
        // $new_code
        /* $old_code
		$this->PLANET['metal_max']			= $temp[901]['max'] * $this->config->storage_multiplier * (1 + $this->USER['factor']['ResourceStorage']);
		$this->PLANET['crystal_max']		= $temp[902]['max'] * $this->config->storage_multiplier * (1 + $this->USER['factor']['ResourceStorage']);
		$this->PLANET['deuterium_max']		= $temp[903]['max'] * $this->config->storage_multiplier * (1 + $this->USER['factor']['ResourceStorage']);

		$this->PLANET['energy']				= round($temp[911]['plus'] * $this->config->energySpeed * (1 + $this->USER['factor']['Energy']));
		$this->PLANET['energy_used']		= $temp[911]['minus'] * $this->config->energySpeed;
		if($this->PLANET['energy_used'] == 0) {
			$this->PLANET['metal_perhour']		= 0;
			$this->PLANET['crystal_perhour']	= 0;
			$this->PLANET['deuterium_perhour']	= 0;
		} else {
			$prodLevel	= min(1, $this->PLANET['energy'] / abs($this->PLANET['energy_used']));
			
			$this->PLANET['metal_perhour']		= ($temp[901]['plus'] * (1 + $this->USER['factor']['Resource'] + 0.02 * $this->USER[$resource[131]]) * $prodLevel + $temp[901]['minus']) * $this->config->resource_multiplier;
			$this->PLANET['crystal_perhour']	= ($temp[902]['plus'] * (1 + $this->USER['factor']['Resource'] + 0.02 * $this->USER[$resource[132]]) * $prodLevel + $temp[902]['minus']) * $this->config->resource_multiplier;
			$this->PLANET['deuterium_perhour']	= ($temp[903]['plus'] * (1 + $this->USER['factor']['Resource'] + 0.02 * $this->USER[$resource[133]]) * $prodLevel + $temp[903]['minus']) * $this->config->resource_multiplier;
		}
        $old_code */
	}
	
	private function ShipyardQueue()
	{
		global $resource;

		$BuildQueue 	= !empty($this->PLANET['b_hangar_id']) ? @unserialize($this->PLANET['b_hangar_id']) : false;
		if (!$BuildQueue) {
			$this->PLANET['b_hangar'] = 0;
			$this->PLANET['b_hangar_id'] = '';
			return false;
		}

		$elapsed = max(0, (int)($this->TIME - $this->PLANET['last_update']));
		$this->PLANET['b_hangar'] = max(0, (float)$this->PLANET['b_hangar'] + $elapsed);
		$BuildArray					= array();
		foreach($BuildQueue as $Item)
		{
			$AcumTime			= BuildFunctions::getBuildingTime($this->USER, $this->PLANET, $Item[0]);
			$BuildArray[] 		= array($Item[0], $Item[1], $AcumTime);
		}

		$NewQueue	= array();
		$Done		= false;
		foreach($BuildArray as $Item)
		{
			$Element   = $Item[0];
			$Count     = $Item[1];

			if($Done == false) {
				$BuildTime = $Item[2];
				$Element   = (int)$Element;
				if($BuildTime == 0) {			
					if(!isset($this->Builded[$Element]))
						$this->Builded[$Element] = 0;
						
					$this->Builded[$Element]			+= $Count;
					$this->PLANET[$resource[$Element]]	+= $Count;
					continue;					
				}
				
				$Build			= max(min(floor($this->PLANET['b_hangar'] / $BuildTime), $Count), 0);

				if($Build == 0) {
					$NewQueue[]	= array($Element, $Count);
					$Done		= true;
					continue;
				}
				
				if(!isset($this->Builded[$Element]))
					$this->Builded[$Element] = 0;
				
				$this->Builded[$Element]			+= $Build;
				$this->PLANET['b_hangar']			-= $Build * $BuildTime;
				$this->PLANET[$resource[$Element]]	+= $Build;
				$Count								-= $Build;
				
				if ($Count == 0)
					continue;
				else
					$Done	= true;
			}	
			$NewQueue[]	= array($Element, $Count);
		}
		$this->PLANET['b_hangar_id']	= !empty($NewQueue) ? serialize($NewQueue) : '';

		return true;
	}
	
	private function BuildingQueue() 
	{
		while($this->CheckPlanetBuildingQueue())
			$this->SetNextQueueElementOnTop();
	}
	
	private function CheckPlanetBuildingQueue()
	{
		global $resource, $reslist;
		
		if (empty($this->PLANET['b_building_id']) || $this->PLANET['b_building'] > $this->TIME)
			return false;
		
		$CurrentQueue	= unserialize($this->PLANET['b_building_id']);

		$Element      	= $CurrentQueue[0][0];
		$BuildEndTime 	= $CurrentQueue[0][3];
		$BuildMode    	= $CurrentQueue[0][4];
		
		if(!isset($this->Builded[$Element]))
			$this->Builded[$Element] = 0;
        
		if ($BuildMode == 'build')
		{
			$this->PLANET['field_current']		+= 1;
			$this->PLANET[$resource[$Element]]	+= 1;
			$this->Builded[$Element]			+= 1;
		}
		else
		{
			$this->PLANET['field_current'] 		-= 1;
			$this->PLANET[$resource[$Element]] 	-= 1;
			$this->Builded[$Element]			-= 1;
		}
	

		array_shift($CurrentQueue);
		$OnHash	= in_array($Element, $reslist['prod']);
		$this->UpdateResource($BuildEndTime, !$OnHash);			
			
		if (count($CurrentQueue) == 0) {
			$this->PLANET['b_building']    	= 0;
			$this->PLANET['b_building_id'] 	= '';

			return false;
		} else {
			$this->PLANET['b_building_id'] 	= serialize($CurrentQueue);
			return true;
		}
	}	

	public function SetNextQueueElementOnTop()
	{
		global $resource, $LNG, $reslist;

		if (empty($this->PLANET['b_building_id'])) {
			$this->PLANET['b_building']    = 0;
			$this->PLANET['b_building_id'] = '';
			return false;
		}

		$CurrentQueue 	= unserialize($this->PLANET['b_building_id']);
		$Loop       	= true;

		$BuildEndTime	= 0;
		$NewQueue		= '';

		while ($Loop === true)
		{
			$ListIDArray		= $CurrentQueue[0];
			$Element			= $ListIDArray[0];
			$BuildMode			= $ListIDArray[4];
			$ForDestroy			= ($BuildMode == 'destroy') ? true : false;
			$Level				= $ForDestroy ? max(0, (int)($this->PLANET[$resource[$Element]] ?? 0) - 1) : ((int)($this->PLANET[$resource[$Element]] ?? 0) + 1);
			$costResources		= BuildFunctions::getElementPrice($this->USER, $this->PLANET, $Element, $ForDestroy, $ForDestroy ? $Level : ($Level - 1));
			$BuildTime			= BuildFunctions::getBuildingTime($this->USER, $this->PLANET, $Element, $costResources, $ForDestroy, $ForDestroy ? $Level : ($Level - 1));
			$HaveResources		= BuildFunctions::isElementBuyable($this->USER, $this->PLANET, $Element, $costResources, $ForDestroy, $ForDestroy ? $Level : ($Level - 1));
			$BuildEndTime		= $this->PLANET['b_building'] + $BuildTime;
			$CurrentQueue[0]	= array($Element, $Level, $BuildTime, $BuildEndTime, $BuildMode);
			$HaveNoMoreLevel	= false;
				
			if($ForDestroy && $this->PLANET[$resource[$Element]] == 0) {
				$HaveResources  = false;
				$HaveNoMoreLevel = true;
			}

			if(!$ForDestroy && !BuildFunctions::isTechnologieAccessible($this->USER, $this->PLANET, $Element, array())) {
				$HaveResources = false;
			}

			if($HaveResources === true) {
                /*$old_code
				if(isset($costResources[901])) { $this->PLANET[$resource[901]]	-= $costResources[901]; }
				if(isset($costResources[902])) { $this->PLANET[$resource[902]]	-= $costResources[902]; }
				if(isset($costResources[903])) { $this->PLANET[$resource[903]]	-= $costResources[903]; }
				if(isset($costResources[921])) { $this->USER[$resource[921]]	-= $costResources[921]; }
                $old_code*/
                //$new_code
                include('includes/subclasses/subclass.ResThis.php');
                //$new_code
				if (!isset($this->PLANET['_auto_paid_mines'])) {
					$this->PLANET['_auto_paid_mines'] = array(901 => 0.0, 902 => 0.0, 903 => 0.0);
				}
				$this->PLANET['_auto_paid_mines'][901] += (float)($costResources[901] ?? 0.0);
				$this->PLANET['_auto_paid_mines'][902] += (float)($costResources[902] ?? 0.0);
				$this->PLANET['_auto_paid_mines'][903] += (float)($costResources[903] ?? 0.0);

				$NewQueue               	= serialize($CurrentQueue);
				$Loop                  		= false;
			} else {
				if($this->USER['hof'] == 1){
					if ($HaveNoMoreLevel) {
						$Message     = sprintf($LNG['sys_nomore_level'], $LNG['tech'][$Element]);
					} else {
                        /*$old_code
						if(!isset($costResources[901])) { $costResources[901] = 0; }
						if(!isset($costResources[902])) { $costResources[902] = 0; }
						if(!isset($costResources[903])) { $costResources[903] = 0; }
                        $old_code*/
						$Message = MessageTemplateHelper::buildQueueCancelCard('building', $Element, $this->PLANET, $LNG);
					}

					$recipientId = !empty($this->USER['id']) ? (int)$this->USER['id'] : (!empty($this->PLANET['id_owner']) ? (int)$this->PLANET['id_owner'] : 0);
					if ($recipientId > 0) {
						PlayerUtil::sendMessage($recipientId, 0, $LNG['sys_buildlist'], 99,
							$LNG['sys_buildlist_fail'], $Message, $this->TIME);
					}
				}

				$CurrentQueue  = array();
				$BuildEndTime  = 0;
				$NewQueue      = '';
				$Loop          = false;
			}
		}
			
		$this->PLANET['b_building']    = $BuildEndTime;
		$this->PLANET['b_building_id'] = $NewQueue;

		return true;
	}
		
	private function ResearchQueue()
	{
		while($this->CheckUserTechQueue())
			$this->SetNextQueueTechOnTop();
	}
	
	private function CheckUserTechQueue()
	{
		global $resource;
		
		if (empty($this->USER['b_tech_id']) || $this->USER['b_tech'] > $this->TIME)
			return false;
		
		if(!isset($this->Builded[$this->USER['b_tech_id']]))
			$this->Builded[$this->USER['b_tech_id']]	= 0;
			
		$this->Builded[$this->USER['b_tech_id']]			+= 1;
		$this->USER[$resource[$this->USER['b_tech_id']]]	+= 1;
	

		$CurrentQueue	= unserialize($this->USER['b_tech_queue']);
		array_shift($CurrentQueue);		
			
		$this->USER['b_tech_id']		= 0;
		if (count($CurrentQueue) == 0) {
			$this->USER['b_tech'] 			= 0;
			$this->USER['b_tech_id']		= 0;
			$this->USER['b_tech_planet']	= 0;
			$this->USER['b_tech_queue']		= '';
			return false;
		} else {
			$this->USER['b_tech_queue'] 	= serialize(array_values($CurrentQueue));
			return true;
		}
	}	
	
	public function SetNextQueueTechOnTop()
	{
		global $resource, $LNG, $reslist, $resglobal;

		if (empty($this->USER['b_tech_queue'])) {
			$this->USER['b_tech'] 			= 0;
			$this->USER['b_tech_id']		= 0;
			$this->USER['b_tech_planet']	= 0;
			$this->USER['b_tech_queue']		= '';
			return false;
		}

		$CurrentQueue 	= unserialize($this->USER['b_tech_queue']);
		$Loop       	= true;
		while ($Loop == true)
		{
			$ListIDArray        = $CurrentQueue[0];
			$isAnotherPlanet	= $ListIDArray[4] != $this->PLANET['id'];
			if($isAnotherPlanet)
			{
				$sql	= 'SELECT * FROM %%PLANETS%% WHERE id = :planetId;';
				$PLANET	= Database::get()->selectSingle($sql, array(
					':planetId'	=> $ListIDArray[4],
				));

				$RPLANET 		= new ResourceUpdate(true, false);
				list(, $PLANET)	= $RPLANET->CalcResource($this->USER, $PLANET, false, $this->USER['b_tech']);
			}
			else
			{
				$PLANET	= $this->PLANET;
			}
            /*$old_code
			$PLANET[$resource[31].'_inter']	= self::getNetworkLevel($this->USER, $PLANET);
            $old_code*/
            //$new_code
            //foreach($reslist['lab'] as $laba) //проверка всего масива элементов
            //{   
                $PLANET[$resource[$resglobal['tech_speed']].'_inter']	= self::getNetworkLevel($this->USER, $PLANET);
            //}
			//$new_code
			$Element            = $ListIDArray[0];
			$Level              = (int)($this->USER[$resource[$Element]] ?? 0) + 1;
			$costResources		= BuildFunctions::getElementPrice($this->USER, $PLANET, $Element, false, $Level - 1);
			$BuildTime			= BuildFunctions::getBuildingTime($this->USER, $PLANET, $Element, $costResources, false, $Level - 1);
			$HaveResources		= BuildFunctions::isElementBuyable($this->USER, $PLANET, $Element, $costResources, false, $Level - 1);
			if(!BuildFunctions::isTechnologieAccessible($this->USER, $PLANET, $Element, array())) {
				$HaveResources = false;
			}
			$BuildEndTime       = $this->USER['b_tech'] + $BuildTime;
			$CurrentQueue[0]	= array($Element, $Level, $BuildTime, $BuildEndTime, $PLANET['id']);
			
			if($HaveResources == true) {
                /*$old_code
				if(isset($costResources[901])) { $PLANET[$resource[901]]		-= $costResources[901]; }
				if(isset($costResources[902])) { $PLANET[$resource[902]]		-= $costResources[902]; }
				if(isset($costResources[903])) { $PLANET[$resource[903]]		-= $costResources[903]; }
				if(isset($costResources[921])) { $this->USER[$resource[921]]	-= $costResources[921]; }
                $old_code*/
                //$new_code
                include('includes/subclasses/subclass.ResPlanetThisUser.php');
                //$new_code
				if (!isset($this->USER['_auto_paid_research'])) {
					$this->USER['_auto_paid_research'] = array();
				}
				$this->USER['_auto_paid_research'][] = array(
					'planet_id' => (int)$PLANET['id'],
					'cost'      => array(
						901 => (float)($costResources[901] ?? 0.0),
						902 => (float)($costResources[902] ?? 0.0),
						903 => (float)($costResources[903] ?? 0.0),
					),
				);
               
				$this->USER['b_tech_id']		= $Element;
				$this->USER['b_tech']      		= $BuildEndTime;
				$this->USER['b_tech_planet']	= $PLANET['id'];
				$this->USER['b_tech_queue'] 	= serialize($CurrentQueue);

				$Loop                  			= false;
			} else {
				if($this->USER['hof'] == 1){
                    /*$old_code
					if(!isset($costResources[901])) { $costResources[901] = 0; }
					if(!isset($costResources[902])) { $costResources[902] = 0; }
					if(!isset($costResources[903])) { $costResources[903] = 0; }
                    $old_code*/
					$Message = MessageTemplateHelper::buildQueueCancelCard('research', $Element, $PLANET, $LNG);
					PlayerUtil::sendMessage($this->USER['id'], 0, $LNG['sys_techlist'], 99, $LNG['sys_buildlist_fail'], $Message, $this->TIME);
				}

				$CurrentQueue                   = array();
				$this->USER['b_tech']           = 0;
				$this->USER['b_tech_id']        = 0;
				$this->USER['b_tech_planet']    = 0;
				$this->USER['b_tech_queue']     = '';
				$Loop                           = false;
			}
				
			if($isAnotherPlanet)
			{
				$RPLANET->SavePlanetToDB($this->USER, $PLANET);
				$RPLANET		= NULL;
				unset($RPLANET);
			}
			else
			{
				$this->PLANET	= $PLANET;
			}
		}

		return true;
	}
	
	public function SavePlanetToDB($USER = NULL, $PLANET = NULL)
	{
		global $resource, $reslist;
		
		if(is_null($USER))
			global $USER;
			
		if(is_null($PLANET))
			global $PLANET;

		$buildQueries	= array();
        // $new_code
		$params	= array(
			':userId'				=> $USER['id'],
			':planetId'				=> $PLANET['id'],
			':ecoHash'				=> $PLANET['eco_hash'],
			':lastUpdateTime'		=> $PLANET['last_update'],
			':b_building'			=> $PLANET['b_building'],
			':b_building_id' 		=> $PLANET['b_building_id'],
			':field_current' 		=> $PLANET['field_current'],
			':b_hangar_id'			=> $PLANET['b_hangar_id'],
			':b_hangar'				=> $PLANET['b_hangar'],
			':b_tech'				=> $USER['b_tech'],
			':b_tech_id'			=> $USER['b_tech_id'],
			':b_tech_planet'		=> $USER['b_tech_planet'],
			':b_tech_queue'			=> $USER['b_tech_queue']
		);
        
        foreach($reslist['resstype'][1] as $resP) //проверка всего масива элементов
		{
            $params	+= array(
                ':'.$resource[$resP].''				=> $PLANET[''.$resource[$resP].''],
                ':'.$resource[$resP].'_perhour'		=> $PLANET[''.$resource[$resP].'_perhour'],
                ':'.$resource[$resP].'_max'			=> $PLANET[''.$resource[$resP].'_max']
            );
        }

        foreach($reslist['resstype'][2] as $resS) //проверка всего масива элементов
		{
            $params	+= array(
                ':'.$resource[$resS].'_used'		=> $PLANET[''.$resource[$resS].'_used'],
                ':'.$resource[$resS].''				=> $PLANET[''.$resource[$resS].'']
            );
        }
        //$new_code
        /* $old_code
		$params	= array(
			':userId'				=> $USER['id'],
			':planetId'				=> $PLANET['id'],
			':metal'				=> $PLANET['metal'],
			':crystal'				=> $PLANET['crystal'],
			':deuterium'			=> $PLANET['deuterium'],
			':ecoHash'				=> $PLANET['eco_hash'],
			':lastUpdateTime'		=> $PLANET['last_update'],
			':b_building'			=> $PLANET['b_building'],
			':b_building_id' 		=> $PLANET['b_building_id'],
			':field_current' 		=> $PLANET['field_current'],
			':b_hangar_id'			=> $PLANET['b_hangar_id'],
			':metal_perhour'		=> $PLANET['metal_perhour'],
			':crystal_perhour'		=> $PLANET['crystal_perhour'],
			':deuterium_perhour'	=> $PLANET['deuterium_perhour'],
			':metal_max'			=> $PLANET['metal_max'],
			':crystal_max'			=> $PLANET['crystal_max'],
			':deuterium_max'		=> $PLANET['deuterium_max'],
			':energy_used'			=> $PLANET['energy_used'],
			':energy'				=> $PLANET['energy'],
			':b_hangar'				=> $PLANET['b_hangar'],
			':darkmatter'			=> $USER['darkmatter'],
			':b_tech'				=> $USER['b_tech'],
			':b_tech_id'			=> $USER['b_tech_id'],
			':b_tech_planet'		=> $USER['b_tech_planet'],
			':b_tech_queue'			=> $USER['b_tech_queue']
		);
        $old_code */
		if (!empty($this->Builded))
		{
			foreach($this->Builded as $Element => $Count)
			{
				$Element	= (int) $Element;
				
				if(empty($resource[$Element]) || empty($Count)) {
					continue;
				}
				
				if(in_array($Element, $reslist['one']))
				{
					$buildQueries[]						= ', p.'.$resource[$Element].' = :'.$resource[$Element];
					$params[':'.$resource[$Element]]	= '1';
				}
				elseif(isset($PLANET[$resource[$Element]]))
				{
					$buildQueries[]						= ', p.'.$resource[$Element].' = p.'.$resource[$Element].' + :'.$resource[$Element];
					$params[':'.$resource[$Element]]	= floatToString($Count);
				}
				elseif(isset($USER[$resource[$Element]]))
				{
					$buildQueries[]						= ', u.'.$resource[$Element].' = u.'.$resource[$Element].' + :'.$resource[$Element];
					$params[':'.$resource[$Element]]	= floatToString($Count);
				}
			}
		}
        // $new_code
        $setClauses = array(
            'p.eco_hash			= :ecoHash',
            'p.last_update		= :lastUpdateTime',
            'p.b_building		= :b_building',
            'p.b_building_id 	= :b_building_id',
            'p.field_current 	= :field_current',
            'p.b_hangar_id		= :b_hangar_id',
            'p.b_hangar			= :b_hangar',
            'u.b_tech			= :b_tech',
            'u.b_tech_id			= :b_tech_id',
            'u.b_tech_planet		= :b_tech_planet',
            'u.b_tech_queue		= :b_tech_queue'
        );

        foreach($reslist['resstype'][1] as $resP)
        {
            $setClauses[] = 'p.'.$resource[$resP].' = :'.$resource[$resP];
            $setClauses[] = 'p.'.$resource[$resP].'_perhour = :'.$resource[$resP].'_perhour';
            $setClauses[] = 'p.'.$resource[$resP].'_max = :'.$resource[$resP].'_max';
        }

        foreach($reslist['resstype'][2] as $resS)
        {
            $setClauses[] = 'p.'.$resource[$resS].'_used = :'.$resource[$resS].'_used';
            $setClauses[] = 'p.'.$resource[$resS].' = :'.$resource[$resS];
        }

        $sql = 'UPDATE %%PLANETS%% as p,%%USERS%% as u SET ' . implode(",\n            ", $setClauses) . '
		'.implode("\n", $buildQueries).'
		WHERE p.id = :planetId AND u.id = :userId;';
        // resstype[3] (divisas premium) se gestionan de forma atomica en PremiumEconomy y no se sobreescriben aqui
        // $new_code
        /* $old_code
		$sql = 'UPDATE %%PLANETS%% as p,%%USERS%% as u SET
		p.metal				= :metal,
		p.crystal			= :crystal,
		p.deuterium			= :deuterium,
		p.eco_hash			= :ecoHash,
		p.last_update		= :lastUpdateTime,
		p.b_building		= :b_building,
		p.b_building_id 	= :b_building_id,
		p.field_current 	= :field_current,
		p.b_hangar_id		= :b_hangar_id,
		p.metal_perhour		= :metal_perhour,
		p.crystal_perhour	= :crystal_perhour,
		p.deuterium_perhour	= :deuterium_perhour,
		p.metal_max			= :metal_max,
		p.crystal_max		= :crystal_max,
		p.deuterium_max		= :deuterium_max,
		p.energy_used		= :energy_used,
		p.energy			= :energy,
		p.b_hangar			= :b_hangar,
		u.darkmatter		= :darkmatter,
		u.b_tech			= :b_tech,
		u.b_tech_id			= :b_tech_id,
		u.b_tech_planet		= :b_tech_planet,
		u.b_tech_queue		= :b_tech_queue
		'.implode("\n", $buildQueries).'
		WHERE p.id = :planetId AND u.id = :userId;';
        $old_code */
		Database::get()->update($sql, $params);

		$this->Builded	= array();

		return array($USER, $PLANET);
	}
}

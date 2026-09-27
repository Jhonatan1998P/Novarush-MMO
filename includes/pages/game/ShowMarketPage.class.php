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

class ShowMarketPage extends AbstractGamePage
{
	public static $requireModule = MODULE_MARKET;
    
	function __construct() 
	{
		parent::__construct();
	}
    
	function show()
	{
		global $USER, $PLANET, $LNG, $resource, $reslist, $resglobal, $THEME;
        
		$db = Database::get();
		$lot = array();
		
		$Planet_list = array_diff(array_merge($reslist['resstype'][1], $reslist['fleet'], $reslist['defense']), $reslist['not_market_send']);
		foreach($Planet_list as $sellID)
		{
			if (empty($PLANET[$resource[$sellID]]))
				continue;	
			$lot[] = array(
				'id'    => $sellID,
				'count' => $PLANET[$resource[$sellID]],
			);
		}
		
		$User_list = array_diff(array_merge($reslist['ars']), $reslist['not_market_send']);
		foreach($User_list as $sellID)
		{
			if (empty($USER[$resource[$sellID]]))
				continue;	
			$lot[] = array(
				'id'    => $sellID,
				'count' => $USER[$resource[$sellID]],
			);
		}
		
		$sql = 'SELECT * FROM %%MARKET%% WHERE id_owner != :userID;';
		$markets = $db->select($sql, array(
			':userID' => $USER['id']
		));
		
		$market = array();
		foreach($markets as $lotID)
		{	
			$Popup   = '<a href="#" data-tooltip-content="<table class=\'reducefleet_table\'>';
			$text    = '';
			$Datalot = array();
			$lotz    = explode(';', $lotID['lot']);
			foreach($lotz as $Group)
			{
				if (empty($Group)) continue;	
				$res = explode(',', $Group);
				$Popup .= '<tr><td class=\'reducefleet_img_ship\'><img src=\''.$THEME->getTheme().'gebaeude/'.$res[0].'.gif\'></td><td class=\'reducefleet_name_ship\'>'.$LNG['tech'][$res[0]].': <span class=\'reducefleet_count_ship\'>'.pretty_number($res[1]).'</span></td></tr>';
				$Datalot[] = floatToString($res[1]).' '.$LNG['tech'][$res[0]];
			}
			$text .= implode('; ', $Datalot);
			$Popup .= '</table>" class="tooltip">'.$LNG['market_lot'].'</a><span class="textForBlind"> ('.$text.')</span>';
			$market[] = array(
				'lot'   => $Popup,
				'class' => $lotID['class'],
				'id'    => $lotID['id'],
				'price' => $lotID['price'],
				'time'  => _date($LNG['php_tdformat'], $lotID['time']),
			);
		}	
		
		$sql = 'SELECT * FROM %%MARKET%% WHERE id_owner = :userID;';
		$u = $db->select($sql, array(
			':userID' => $USER['id']
		));		
		
		$u_lot = array();
		foreach($u as $lotID)
		{	
			$Popup   = '<a href="#" data-tooltip-content="<table class=\'reducefleet_table\'>';
			$text    = '';
			$Datalot = array();
			$lotz    = explode(';', $lotID['lot']);
			foreach($lotz as $Group)
			{
				if (empty($Group)) continue;	
				$res = explode(',', $Group);
				$Popup .= '<tr><td class=\'reducefleet_img_ship\'><img src=\''.$THEME->getTheme().'gebaeude/'.$res[0].'.gif\'></td><td class=\'reducefleet_name_ship\'>'.$LNG['tech'][$res[0]].': <span class=\'reducefleet_count_ship\'>'.pretty_number($res[1]).'</span></td></tr>';
				$Datalot[] = floatToString($res[1]).' '.$LNG['tech'][$res[0]];
			}
			$text .= implode('; ', $Datalot);
			$Popup .= '</table>" class="tooltip">'.$LNG['market_lot'].'</a><span class="textForBlind"> ('.$text.')</span>';
			$u_lot[] = array(
				'lot'      => $Popup,
				'class'    => $lotID['class'],
				'id'       => $lotID['id'],
				'price'    => $lotID['price'],
				'time_off' => $lotID['time'] + 172800,
				'time'     => _date($LNG['php_tdformat'], $lotID['time']),
			);
		}	
		
		$this->tplObj->loadscript("market.js");
		$cookie = isset($_COOKIE['open_market']) ? $_COOKIE['open_market'] : 1;
		$this->assign(array(
			'class_name' => array(
				1 => $LNG['market_all'], 
				2 => $LNG['tech'][900],
				3 => $LNG['tech'][200],
				4 => $LNG['tech'][400],
				5 => $LNG['tech'][2000]
			),
			'cookie'    => $cookie,
			'lot'       => $lot,
			'market'    => $market,
			'u_lot'     => $u_lot,
			'timestamp' => TIMESTAMP,
			'res'       => 922,
		));
        
		$this->display('page.market.tpl');
	}
	
	function add()
	{
		global $PLANET, $USER, $LNG, $resource, $reslist, $pricelist;
        
		$db    = Database::get();
		$lot   = array();
		$price = (float) HTTP::_GP('price', 0.0);
		$class = (int) HTTP::_GP('class', 0);
        
		$planetItems = array();
		$add_lot_planet = array_diff(array_merge($reslist['resstype'][1], $reslist['fleet'], $reslist['defense']), $reslist['not_market_send']);
		foreach ($add_lot_planet as $lotID)
		{
			$amount = max(0, floor(HTTP::_GP('lot'.$lotID, 0.0, 0.0)));
			if ($amount < 1) continue;
			if ($amount > ($PLANET[$resource[$lotID]] ?? 0)) continue;
			$planetItems[$lotID] = $amount;
			$lot[] = $lotID.','.floatToString($amount);
		}
        
		$userItems = array();
		$add_lot_user = array_diff(array_merge($reslist['ars']), $reslist['not_market_send']);
		foreach ($add_lot_user as $lotID)
		{
			$amount = max(0, floor(HTTP::_GP('lot'.$lotID, 0.0, 0.0)));
			if ($amount < 1) continue;
			if ($amount > ($USER[$resource[$lotID]] ?? 0)) continue;
			$userItems[$lotID] = $amount;
			$lot[] = $lotID.','.floatToString($amount);
		}
		
		if (empty($lot) || $price <= 0){
			$this->printMessage($LNG['market_indicated'], array(array( 
				'label' => $LNG['sys_forward'],
				'url'   => 'game.php?page=market'
			)));
			return;
		}
		
		// Anti-Pushing price band validation (0.5x to 3.0x of reference MSE / (PRS * 0.072))
		$prs = max((float) PremiumEconomy::get('prs', 0), (float) PremiumEconomy::baseIncomeMSE());
		$lotMSE = 0;
		foreach ($planetItems as $lotID => $amount) {
			if ($lotID == 901) {
				$lotMSE += 1 * $amount;
			} elseif ($lotID == 902) {
				$lotMSE += 2 * $amount;
			} elseif ($lotID == 903) {
				$lotMSE += 4 * $amount;
			} elseif (isset($pricelist[$lotID]['cost'])) {
				$uMSE = PremiumEconomy::mse(
					$pricelist[$lotID]['cost'][901] ?? 0,
					$pricelist[$lotID]['cost'][902] ?? 0,
					$pricelist[$lotID]['cost'][903] ?? 0
				);
				$lotMSE += $uMSE * $amount;
			}
		}
		foreach ($userItems as $lotID => $amount) {
			// Arsenal reference value: ~5 AM-equivalent in MSE
			$lotMSE += 5 * ($prs * 0.072) * $amount;
		}
		
		$refAM = $lotMSE / max(1.0, ($prs * 0.072));
		$refAM = max(1.0, $refAM);
		$minPrice = max(1, (int) floor(0.5 * $refAM));
		$maxPrice = max($minPrice, (int) ceil(3.0 * $refAM));
		
		if ($price < $minPrice || $price > $maxPrice) {
			$this->printMessage("Precio inválido (".pretty_number($price)." AM). El rango permitido es entre ".pretty_number($minPrice)." y ".pretty_number($maxPrice)." AM (Banda Anti-Pushing 0.5x - 3.0x).", array(array( 
				'label' => $LNG['sys_forward'],
				'url'   => 'game.php?page=market'
			)));
			return;
		}
		
		// Deduct validated lot items from planet and user
		foreach ($planetItems as $lotID => $amount) {
			$col = $resource[$lotID];
			$PLANET[$col] -= $amount;
			$db->update("UPDATE %%PLANETS%% SET {$col} = {$col} - :amt WHERE id = :pid;", array(
				':amt' => $amount,
				':pid' => $PLANET['id']
			));
		}
		foreach ($userItems as $lotID => $amount) {
			$col = $resource[$lotID];
			$USER[$col] -= $amount;
			$db->update("UPDATE %%USERS%% SET {$col} = {$col} - :amt WHERE id = :uid;", array(
				':amt' => $amount,
				':uid' => $USER['id']
			));
		}
			
		$sql = 'INSERT INTO %%MARKET%% SET class = :class, id_owner = :id_owner, id_planet = :id_planet, lot = :lot, price = :price, time = :time;';
		$db->insert($sql, array(
			':class'     => $class,
			':id_owner'  => $USER['id'],
			':id_planet' => $PLANET['id'],
			':time'      => TIMESTAMP,
			':lot'       => implode(';', $lot),
			':price'     => round($price),
		));	
		
		$this->printMessage($LNG['market_exposed'], array(array( 
			'label' => $LNG['sys_forward'],
			'url'   => 'game.php?page=market'
		)));
	}
	
	function sell() 
	{
		global $PLANET, $USER, $LNG, $resource, $reslist;
        
		$id  = (int) HTTP::_GP('id', 0);
		$pdo = Database::get()->getHandle();
		
		$pdo->beginTransaction();
		try {
			$stmt = $pdo->prepare("SELECT * FROM " . DB_PREFIX . "market WHERE id = :ID FOR UPDATE;");
			$stmt->execute(array(':ID' => $id));
			$selling = $stmt->fetch(PDO::FETCH_ASSOC);
			
			if (!$selling) {
				$pdo->rollBack();
				$this->printMessage("El lote ya no está disponible.", array(array( 
					'label' => $LNG['sys_forward'],
					'url'   => 'game.php?page=market'
				)));
				return;
			}
			
			if ($selling['id_owner'] == $USER['id']) {
				$pdo->rollBack();
				$this->printMessage("No puedes comprar tu propio lote.", array(array( 
					'label' => $LNG['sys_forward'],
					'url'   => 'game.php?page=market'
				)));
				return;
			}
			
			$price = (float) $selling['price'];
			
			// Atomic debit of buyer AM (922)
			if (!PremiumEconomy::debit($USER, 922, $price, 'market_buy', $selling['id'], "seller={$selling['id_owner']}")) {
				$pdo->rollBack();
				$this->printMessage($LNG['market_not_enough_money'], array(array( 
					'label' => $LNG['sys_forward'],
					'url'   => 'game.php?page=market'
				)));
				return;
			}
			
			// 5% tax deduction (market_tax)
			$taxRate = (float) PremiumEconomy::get('market_tax', 0.05);
			$tax = (float) ceil($price * $taxRate);
			$sellerNet = max(0, $price - $tax);
			
			// Credit seller
			$colAM = $resource[922];
			$pdo->prepare("UPDATE " . DB_PREFIX . "users SET {$colAM} = {$colAM} + :amt WHERE id = :uid;")
			    ->execute(array(':amt' => $sellerNet, ':uid' => $selling['id_owner']));
			
			$checkStmt = $pdo->prepare("SELECT {$colAM} FROM " . DB_PREFIX . "users WHERE id = :uid;");
			$checkStmt->execute(array(':uid' => $selling['id_owner']));
			$sellerAfter = (float) $checkStmt->fetchColumn();
			
			PremiumEconomy::ledger($selling['id_owner'], 922, $sellerNet, $sellerAfter, 'market_sell', $selling['id'], "gross={$price};tax={$tax}");
			if ($tax > 0) {
				PremiumEconomy::ledger($selling['id_owner'], 922, -$tax, $sellerAfter, 'market_tax', $selling['id'], "gross={$price}");
			}
			
			// Deliver purchased items to buyer
			$sell_lot = explode(';', $selling['lot']);
			foreach ($sell_lot as $itemStr)
			{
				if (empty($itemStr)) continue;
				$res = explode(',', $itemStr);
				$resId  = (int) $res[0];
				$resQty = (float) $res[1];
				
				if(in_array($resId, array_diff(array_merge($reslist['resstype'][1], $reslist['fleet'], $reslist['defense']), $reslist['not_market_send']))){
					$col = $resource[$resId];
					$PLANET[$col] += $resQty;
					$pdo->prepare("UPDATE " . DB_PREFIX . "planets SET {$col} = {$col} + :amt WHERE id = :pid;")
					    ->execute(array(':amt' => $resQty, ':pid' => $PLANET['id']));
				} elseif(in_array($resId, array_diff(array_merge($reslist['ars']), $reslist['not_market_send']))){
					$col = $resource[$resId];
					$USER[$col] += $resQty;
					$pdo->prepare("UPDATE " . DB_PREFIX . "users SET {$col} = {$col} + :amt WHERE id = :uid;")
					    ->execute(array(':amt' => $resQty, ':uid' => $USER['id']));
				}
			}
			
			// Remove lot from market
			$pdo->prepare("DELETE FROM " . DB_PREFIX . "market WHERE id = :lotId;")
			    ->execute(array(':lotId' => $selling['id']));
			
			$pdo->commit();
			
			$this->printMessage($LNG['market_buy'], array(array( 
				'label' => $LNG['sys_forward'],
				'url'   => 'game.php?page=market'
			)));
		} catch (Exception $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollBack();
			}
			$this->printMessage("Error en la transacción: " . $e->getMessage(), array(array( 
				'label' => $LNG['sys_forward'],
				'url'   => 'game.php?page=market'
			)));
		}
	}
	
	function cancel_lot() 
	{
		global $PLANET, $USER, $LNG, $resource, $reslist;
		
		$db = Database::get();
		$id = (int) HTTP::_GP('id', 0);
		$cancel = $db->selectSingle("SELECT * FROM %%MARKET%% WHERE id = :ID AND id_owner = :uid;", array(
			':ID'  => $id,
			':uid' => $USER['id']
		));
		
		if (empty($cancel)) {
			$this->printMessage("Lote no encontrado.", array(array( 
				'label' => $LNG['sys_forward'],
				'url'   => 'game.php?page=market'
			)));
			return;
		}
		
		$cancel_lot = explode(';', $cancel['lot']);
		foreach ($cancel_lot as $itemStr)
		{
			if (empty($itemStr)) continue;
			$res = explode(',', $itemStr);
			$resId  = (int) $res[0];
			$resQty = (float) $res[1];
            
			if(in_array($resId, array_diff(array_merge($reslist['resstype'][1], $reslist['fleet'], $reslist['defense']), $reslist['not_market_send']))){
				$col = $resource[$resId];
				$PLANET[$col] += $resQty;
				$db->update("UPDATE %%PLANETS%% SET {$col} = {$col} + :amt WHERE id = :pid;", array(
					':pid' => $PLANET['id'],
					':amt' => $resQty
				));
			} elseif(in_array($resId, array_diff(array_merge($reslist['ars']), $reslist['not_market_send']))){
				$col = $resource[$resId];
				$USER[$col] += $resQty;
				$db->update("UPDATE %%USERS%% SET {$col} = {$col} + :amt WHERE id = :uid;", array(
					':uid' => $USER['id'],
					':amt' => $resQty
				));
			}
		}	
        
		$db->delete("DELETE FROM %%MARKET%% WHERE id = :lotId;", array(
			':lotId' => $cancel['id']
		));
        
		$this->printMessage($LNG['market_take_off'], array(array( 
			'label' => $LNG['sys_forward'],
			'url'   => 'game.php?page=market'
		)));	
	}
}
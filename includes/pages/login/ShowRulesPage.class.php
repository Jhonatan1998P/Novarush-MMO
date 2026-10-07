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

class ShowRulesPage extends AbstractLoginPage
{
	public static $requireModule = 0;

	function __construct() 
	{
		parent::__construct();
	}
	
	function show() 
	{
		global $LNG;

		$allowedLangs = Language::getAllowedLangs(false);
		$selectedLang = null;
		$accountLang  = null;

		// 1. Detect language from active player session if logged in
		try {
			$session = Session::load();
			if ($session->isValidSession() && !empty($session->userId)) {
				$db = Database::get();
				$dbLang = $db->selectSingle("SELECT lang FROM %%USERS%% WHERE id = :userId;", array(
					':userId' => $session->userId
				), 'lang');

				if (!empty($dbLang) && array_key_exists($dbLang, $allowedLangs)) {
					$accountLang  = $dbLang;
					$selectedLang = $dbLang;
				}
			}
		} catch (Exception $e) {
			// Session load fallback
		}

		// 2. Allow user to explicitly switch language via query parameter (?lang=es or ?lang=en)
		$reqLang = HTTP::_GP('lang', '');
		if (!empty($reqLang)) {
			$reqLang = strtolower(trim($reqLang));
			if (array_key_exists($reqLang, $allowedLangs)) {
				$selectedLang = $reqLang;
			}
		}

		// 3. Fallback to current LNG or default
		if (empty($selectedLang)) {
			$selectedLang = $LNG->getLanguage();
			if (empty($selectedLang) || !array_key_exists($selectedLang, $allowedLangs)) {
				$selectedLang = 'es';
			}
		}

		// Load LNG data for the selected language
		if ($LNG->getLanguage() !== $selectedLang) {
			$LNG = new Language($selectedLang);
			$LNG->includeData(array('L18N', 'INGAME', 'PUBLIC', 'CUSTOM'));
		}

		$this->assign(array(
			'rules'			=> $LNG->getTemplate('rules'),
			'activeLang'	=> $selectedLang,
			'accountLang'	=> $accountLang,
			'allowedLangs'	=> $allowedLangs,
		));
		
		$this->display('page.rules.default.tpl');
	}
}

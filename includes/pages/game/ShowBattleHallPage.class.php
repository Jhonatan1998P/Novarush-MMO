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

class ShowBattleHallPage extends AbstractGamePage
{
	public static $requireModule = MODULE_BATTLEHALL;

	function __construct()
	{
		parent::__construct();
	}

	private function formatParticipants($namesString, $maxTotalChars = 14)
	{
		$namesString = trim((string)$namesString);
		if ($namesString === '') {
			return '---';
		}

		if (strpos($namesString, ' & ') !== false) {
			$parts = explode(' & ', $namesString);
			$formatted = array();
			foreach ($parts as $part) {
				$p = trim($part);
				if (mb_strlen($p, 'UTF-8') > 10) {
					$formatted[] = mb_substr($p, 0, 7, 'UTF-8') . '...';
				} else {
					$formatted[] = $p;
				}
			}
			$joined = implode(' & ', $formatted);
			if (mb_strlen($joined, 'UTF-8') > $maxTotalChars) {
				return mb_substr($joined, 0, $maxTotalChars - 3, 'UTF-8') . '...';
			}
			return $joined;
		}

		if (mb_strlen($namesString, 'UTF-8') > $maxTotalChars) {
			return mb_substr($namesString, 0, $maxTotalChars - 3, 'UTF-8') . '...';
		}

		return $namesString;
	}

	function show()
	{
		global $USER, $LNG;
		$order  = HTTP::_GP('order', 'units');
		$sort   = HTTP::_GP('sort', 'desc');
		$sort   = strtoupper($sort) === "DESC" ? "DESC" : "ASC";

		switch($order)
		{
			case 'date':
				$key = '%%TOPKB%%.time '.$sort.', %%TOPKB%%.units DESC';
				break;
			case 'units':
			default:
				$key = '%%TOPKB%%.units '.$sort.', %%TOPKB%%.time DESC';
				break;
		}

		$db = Database::get();
		$sql = "SELECT *, (
			SELECT DISTINCT
			IF(%%TOPKB_USERS%%.username = '', GROUP_CONCAT(%%USERS%%.username SEPARATOR ' & '), GROUP_CONCAT(%%TOPKB_USERS%%.username SEPARATOR ' & '))
			FROM %%TOPKB_USERS%%
			LEFT JOIN %%USERS%% ON uid = %%USERS%%.id
			WHERE %%TOPKB_USERS%%.rid = %%TOPKB%%.rid AND role = 1
		) as attacker,
		(
			SELECT DISTINCT
			IF(%%TOPKB_USERS%%.username = '', GROUP_CONCAT(%%USERS%%.username SEPARATOR ' & '), GROUP_CONCAT(%%TOPKB_USERS%%.username SEPARATOR ' & '))
			FROM %%TOPKB_USERS%% INNER JOIN %%USERS%% ON uid = id
			WHERE %%TOPKB_USERS%%.rid = %%TOPKB%%.`rid` AND `role` = 2
		) as defender
		FROM %%TOPKB%% WHERE universe = :universe AND units > 0 ORDER BY ".$key." LIMIT 100;";

		$top = $db->select($sql, array(
			':universe' => Universe::current()
		));

		$TopKBList	= array();
		foreach($top as $data)
		{
			$result = $data['result'];
			if ($result !== 'a' && $result !== 'r') {
				$result = 'a';
			}

			$attackerRaw = !empty($data['attacker']) ? $data['attacker'] : '---';
			$defenderRaw = !empty($data['defender']) ? $data['defender'] : '---';

			$TopKBList[]	= array(
				'result'		=> $result,
				'date'			=> _date($LNG['php_tdformat'], $data['time'], $USER['timezone']),
				'time'			=> TIMESTAMP - $data['time'],
				'units'			=> $data['units'],
				'rid'			=> $data['rid'],
				'attacker'		=> $this->formatParticipants($attackerRaw, 14),
				'defender'		=> $this->formatParticipants($defenderRaw, 14),
				'full_attacker'	=> $attackerRaw,
				'full_defender'	=> $defenderRaw,
			);
		}

		$this->assign(array(
			'TopKBList'		=> $TopKBList,
			'sort'			=> $sort,
			'order'			=> $order,
		));

		$this->display('page.battleHall.default.tpl');
	}
}
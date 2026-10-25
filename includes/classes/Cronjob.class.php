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

class Cronjob
{
	function __construct()
	{
		
	}
	
	static function cleanStaleLocks()
	{
		$db = Database::get();
		$lockedJobs = $db->select('SELECT cronjobID, `lock`, nextTime FROM %%CRONJOBS%% WHERE `lock` IS NOT NULL;');
		foreach ($lockedJobs as $job) {
			$lock = $job['lock'];
			$isStale = false;
			if (strpos($lock, '_') !== false) {
				$parts = explode('_', $lock);
				$lockTs = (int)$parts[0];
				if ($lockTs > 0 && (TIMESTAMP - $lockTs) > 300) {
					$isStale = true;
				}
			} else {
				if ($job['nextTime'] <= TIMESTAMP || (TIMESTAMP - (int)$job['nextTime']) > 300) {
					$isStale = true;
				}
			}

			if ($isStale) {
				$db->update('UPDATE %%CRONJOBS%% SET `lock` = NULL WHERE cronjobID = :id;', array(
					':id' => $job['cronjobID']
				));
			}
		}
	}

	static function execute($cronjobID)
	{
		$lockToken	= TIMESTAMP . '_' . substr(md5(uniqid()), 0, 16);

		$db	= Database::get();

		self::cleanStaleLocks();

		$sql = 'SELECT class FROM %%CRONJOBS%% WHERE isActive = :isActive AND cronjobID = :cronjobId AND `lock` IS NULL;';

		$cronjobClassName	= $db->selectSingle($sql, array(
			':isActive'		=> 1,
			':cronjobId'	=> $cronjobID
		), 'class');

		if(empty($cronjobClassName))
		{
			throw new Exception(sprintf("Unknown cronjob %s or cronjob is deactive!", $cronjobID));
		}
		
		$sql = 'UPDATE %%CRONJOBS%% SET `lock` = :lock WHERE cronjobID = :cronjobId;';

		$db->update($sql, array(
			':lock'			=> $lockToken,
			':cronjobId'	=> $cronjobID
		));
		
		$cronjobPath		= 'includes/classes/cronjob/'.$cronjobClassName.'.class.php';
		
		// die hard, if file not exists.
		require_once($cronjobPath);

		/** @var $cronjobObj CronjobTask */
		$cronjobObj			= new $cronjobClassName;
		$executionSuccess   = false;

		try {
			$cronjobObj->run();
			$executionSuccess = true;
		} catch (Exception $e) {
			error_log('Cronjob ' . $cronjobID . ' (' . $cronjobClassName . ') failed: ' . $e->getMessage());
		} catch (Throwable $t) {
			error_log('Cronjob ' . $cronjobID . ' (' . $cronjobClassName . ') error: ' . $t->getMessage());
		} finally {
			self::reCalculateCronjobs($cronjobID);
			$sql = 'UPDATE %%CRONJOBS%% SET `lock` = NULL WHERE cronjobID = :cronjobId;';

			$db->update($sql, array(
				':cronjobId'	=> $cronjobID
			));
		}

		if ($executionSuccess) {
			$sql = 'INSERT INTO %%CRONJOBS_LOG%% SET `cronjobId` = :cronjobId,
			`executionTime` = :executionTime, `lockToken` = :lockToken';

			$db->insert($sql, array(
				':cronjobId'		=> $cronjobID,
				':executionTime'	=> Database::formatDate(TIMESTAMP),
				':lockToken'		=> $lockToken
			));

			if ((int)$cronjobID === 2) {
				try {
					$config = Config::get();
					$config->stat_last_update = TIMESTAMP;
					$config->stat_last_db_update = TIMESTAMP;
					$config->save();
				} catch (Throwable $e) {}
			}
		}
	}
	
	static function getNeedTodoExecutedJobs()
	{
		$db	= Database::get();

		self::cleanStaleLocks();

		$sql			= 'SELECT cronjobID
		FROM %%CRONJOBS%%
		WHERE isActive = :isActive AND nextTime < :time AND `lock` IS NULL;';

		$cronjobResult	= $db->select($sql, array(
			':isActive'	=> 1,
			':time'		=> TIMESTAMP
 		));

		$cronjobList	= array();

		foreach($cronjobResult as $cronjobRow)
		{
			$cronjobList[]	= $cronjobRow['cronjobID'];
		}
		
		return $cronjobList;
	}

	static function getLastExecutionTime($cronjobName)
	{
		$db			= Database::get();
		$sql		= 'SELECT executionTime, lockToken FROM %%CRONJOBS_LOG%% INNER JOIN %%CRONJOBS%% USING(cronjobId) WHERE name = :cronjobName ORDER BY lockToken DESC LIMIT 1;';
		$lastRow	= $db->selectSingle($sql, array(
			':cronjobName' => $cronjobName
		));

		if(empty($lastRow))
		{
			return false;
		}

		$tokenTs = 0;
		if (!empty($lastRow['lockToken']) && strpos($lastRow['lockToken'], '_') !== false) {
			$parts = explode('_', $lastRow['lockToken']);
			$tokenTs = (int)$parts[0];
		}

		if ($tokenTs > 0) {
			return min($tokenTs, TIMESTAMP);
		}

		$time = strtotime($lastRow['executionTime']);
		return min($time, TIMESTAMP);
	}
	
	static function reCalculateCronjobs($cronjobID = NULL)
	{
		require_once 'includes/libs/tdcron/class.tdcron.php';
		require_once 'includes/libs/tdcron/class.tdcron.entry.php';

		$db	= Database::get();

		if(!empty($cronjobID))
		{
			$sql			= 'SELECT cronjobID, min, hours, dom, month, dow FROM %%CRONJOBS%% WHERE cronjobID = :cronjobId;';
			$cronjobResult	= $db->select($sql, array(
				':cronjobId' => $cronjobID
			));
		}
		else
		{
			$sql			= 'SELECT cronjobID, min, hours, dom, month, dow FROM %%CRONJOBS%%;';
			$cronjobResult	= $db->select($sql);
		}

		$sql = 'UPDATE %%CRONJOBS%% SET nextTime = :nextTime WHERE cronjobID = :cronjobId;';

		foreach($cronjobResult as $cronjobRow)
		{
			$cronTabString	= implode(' ', array($cronjobRow['min'], $cronjobRow['hours'], $cronjobRow['dom'], $cronjobRow['month'], $cronjobRow['dow']));
			$nextTime		= tdCron::getNextOccurrence($cronTabString, TIMESTAMP + 60);

			$db->update($sql, array(
				':nextTime'		=> $nextTime,
				':cronjobId'	=> $cronjobRow['cronjobID'],
			));
		}
	}
}
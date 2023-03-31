<?php 
/*
 * ARI Framework
 *
 * @package		ARI Framework
 * @version		1.0.0
 * @author		ARI Soft
 * @copyright	Copyright (c) 2009 www.ari-soft.com. All rights reserved
 * @license		GNU/GPL (http://www.gnu.org/copyleft/gpl.html)
 * 
 */

(defined('_JEXEC') && defined('ARI_FRAMEWORK_LOADED')) or die('Direct Access to this location is not allowed.');

class AriDateUtility extends AriDateUtilityBase
{
	function toDbUtcDate($date)
	{
		if (empty($date))
		{
			$db =& JFactory::getDBO();
			return $db->getNullDate();
		}

		$date = new JDate($date, AriDateUtility::getTimeZone());
		$ts = $date->toUnix();
		$ts -= $date->getOffsetFromGMT();
		
		$utcDate = new JDate($ts, 'UTC');

		return $utcDate->toSql();		
	}
	
	function toUnixUTC($date)
	{
		if (empty($date))
			return 0;
			
		$date = new JDate($date, AriDateUtility::getTimeZone());
		$ts = $date->toUnix();
		$ts -= $date->getOffsetFromGMT();

		return $ts;
	}
	
	function getDbUtcDate()
	{
		$date = new JDate();
		
		return J3_0? $date->toSql() : $date->toMySQL();
	}
	
	function toUnix($date, $local = true)
	{
		$ts = $date->toUnix();

		if ($local)
			$ts += $date->getOffsetFromGMT();
			
		return $ts;
	}
	
	function formatDate($date, $format = null, $tz = null)
	{	
		if ($date && preg_match("/([0-9]{4})\-([0-9]{2})\-([0-9]{2})[ ]([0-9]{2})\:([0-9]{2})\:([0-9]{2})/", $date, $regs)) 
		{
			$format = AriDateUtilityBase::getFormat($format);
			$d = new JDate('now', AriDateUtility::getTimeZone($tz));

			$date = mktime($regs[4], $regs[5], $regs[6], $regs[2], $regs[3], $regs[1]);
			$date = $date > -1 ? strftime($format, $date + $d->getOffsetFromGMT()) : '-';
		}
		
		return $date;
	}
	
	/*
	 * static
	 */
	function getTimeZone($tz = null)
	{
		if (!is_null($tz))
			return $tz;

		$user =& JFactory::getUser();
		$userId = $user->get('id');
		$jConfig = new JConfig();
		if ($userId > 0)
		{
			$tz = $user->getParam('timezone', null);
		}
		
		if (is_null($tz))
			if (!empty($jConfig->offset))
				$tz = $jConfig->offset;
				
		if (!is_null($tz))
		{
			$tz = new DateTimeZone($tz);
		}
			
		return $tz;
	}
}
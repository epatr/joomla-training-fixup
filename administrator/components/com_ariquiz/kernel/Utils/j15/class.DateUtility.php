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
	function toDbUtcDate($date, $tz = null)
	{
		if (empty($date))
		{
			$db =& JFactory::getDBO();
			return $db->getNullDate();
		}

		$offset = date('Z') + AriDateUtility::getTimeZone($tz) * 3600; 
		$date -= $offset;
		if ($date < 0)
			$date = 0;
			
		$date = new JDate($date);

		return $date->toMySQL();
	}
	
	function toUnixUTC($date)
	{
		if (empty($date))
			return 0;
			
		$offset = date('Z') + AriDateUtility::getTimeZone() * 3600; 
		$date -= $offset;
		if ($date < 0)
			$date = 0;

		return $date;
	}
	
	function getDbUtcDate()
	{
		$date = new JDate();

		return $date->toMySql();
	}
	
	function toUnix($date, $local = true)
	{
		$ts = $date->toUnix();
		if ($local)
		{
			$ts += (AriDateUtility::getTimeZone() * 60 * 60) + date('Z');
		} 
		
		return $ts;
	}
	
	function formatDate($date, $format = null, $tz = null)
	{	
		if ($date && preg_match("/([0-9]{4})\-([0-9]{2})\-([0-9]{2})[ ]([0-9]{2})\:([0-9]{2})\:([0-9]{2})/", $date, $regs)) 
		{
			$format = AriDateUtilityBase::getFormat($format);
			$tz = AriDateUtility::getTimeZone($tz);

			$date = mktime($regs[4], $regs[5], $regs[6], $regs[2], $regs[3], $regs[1]);
			$date = $date > -1 ? strftime($format, $date + ($tz * 60 * 60)) : '-';
		}
		
		return $date;
	}
	
	function getTimeZone($tz = null)
	{
		if (!is_null($tz))
			return $tz;

		$user =& JFactory::getUser();
		$userId = $user->get('id');
		$jConfig = new JConfig();
		if ($userId > 0)
			$tz = $user->getParam('timezone', 0);
		else
			$tz = !empty($jConfig->offset) ? $jConfig->offset : 0;

		return $tz;
	}
}
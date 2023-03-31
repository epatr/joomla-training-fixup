<?php
/*
 *
 * @package		ARI Framework
 * @author		ARI Soft
 * @copyright	Copyright (c) 2011 www.ari-soft.com. All rights reserved
 * @license		GNU/GPL (http://www.gnu.org/copyleft/gpl.html)
 * 
 */

(defined('_JEXEC') && defined('ARI_FRAMEWORK_LOADED')) or die;

if (!class_exists('Services_JSON'))
	require_once dirname(__FILE__) . DS . 'JSON_Services.php';

class AriJSONHelper
{
	function encode($data)
	{
		$jsonHandler =& AriJSONHelper::_getJSONHandler();
		
		return $jsonHandler->encode($data);
	}
	
	function decode($str)
	{
		if (empty($str)) return null;
		
		$jsonHandler =& AriJSONHelper::_getJSONHandler();
		
		return $jsonHandler->decode($str);
	}
	
	function &_getJSONHandler()
	{
		static $jsonHandler = null;
		
		if (is_null($jsonHandler))
		{
			$jsonHandler = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
		}

		return $jsonHandler;
	}
}
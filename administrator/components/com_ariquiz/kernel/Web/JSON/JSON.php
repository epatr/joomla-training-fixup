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

AriKernel::import('Web.JSON.JSONHelper');

function json_encode($data)
{
	return AriJSONHelper::encode($data);
}

function json_decode($str)
{
	return AriJSONHelper::decode($str);
}
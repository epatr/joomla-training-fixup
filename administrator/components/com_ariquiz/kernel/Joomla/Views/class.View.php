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

AriKernel::import('Joomla.Views.ViewBase');

class AriView extends AriViewBase
{
	function addScript($scriptPath)
	{
		$doc =& JFactory::getDocument();
		$doc->addScript($scriptPath);
	}

	function disableMainMenu()
	{
		JRequest::setVar('hidemainmenu', true);
	}
}
<?php
/*
 * ARI Quiz Reloader Joomla! plugin
 *
 * @package		ARI Quiz Reloader Joomla! plugin
 * @version		1.0.0
 * @author		ARI Soft
 * @copyright	Copyright (c) 2009 www.ari-soft.com. All rights reserved
 * @license		GNU/GPL (http://www.gnu.org/copyleft/gpl.html)
 * 
 */

defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');

class plgSystemAriquizreloader extends JPlugin
{ 
	function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config); 
	}
	
	function onAfterDispatch()
	{
		$mainframe = JFactory::getApplication();
		
		$document = JFactory::getDocument();
		$doctype = $document->getType();

		if ($mainframe->isAdmin() || $doctype !== 'html') return ;
		
		$option = JRequest::getCmd('option');
		$task = JRequest::getString('view');
		
		if ($option != 'com_ariquiz' || $task != 'question') 
			return ;
		
		$version = new JVersion();
		$isJ16 = version_compare($version->getShortVersion(), '1.6.0', '>=');
		
		$document->addScript('plugins/system/' . ($isJ16 ? 'ariquizreloader/' : '') . 'ariquizreloader/js/init.min.js');
	}
}
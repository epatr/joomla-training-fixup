<?php
/*
 *
 * @package		ARI Quiz MathJax plugin
 * @author		ARI Soft
 * @copyright	Copyright (c) 2011 www.ari-soft.com. All rights reserved
 * @license		GNU/GPL (http://www.gnu.org/copyleft/gpl.html)
 * 
 */

defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');

class plgSystemAriquizmathjax extends JPlugin
{ 
	function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config); 

		$this->loadLanguage('', JPATH_ADMINISTRATOR);
	}

	function onAfterDispatch()
	{
		$mainframe = JFactory::getApplication();
		
		$document = JFactory::getDocument();
		$doctype = $document->getType();

		if ($doctype !== 'html') 
			return ;

		$isAdmin = $mainframe->isAdmin();
		$option = JRequest::getCmd('option');
		$view = JRequest::getCmd('view');

		if (
			$option != 'com_ariquiz'
			||
			(!$isAdmin && !in_array($view, array('question', 'quizcomplete')))
			||
			($isAdmin && $view != 'quizresult')
			)
			return ;

		$params = $this->params;
		$version = new JVersion();
		$j15 = version_compare($version->getShortVersion(), '1.6.0', '<');	

		$loadMathJaxScript = (bool)$params->get('loadScript', 1);
		if ($loadMathJaxScript)
			$this->loadMathJax();
		
		$document->addScript(
			JURI::root(true) . '/plugins/system/ariquizmathjax/' . (!$j15 ? 'ariquizmathjax/' : '') . 'assets/js/ariquizmathjax.js'
		);
	}
	
	function loadMathJax()
	{
		$params = $this->params;
		$document = JFactory::getDocument();
		
		$useSecure = $params->get('useSecure', 'auto');
		$mathJaxUrl = '';
		if ($useSecure == 'always')
		{
			$mathJaxUrl = $params->get('secureScriptUrl');
		}
		else
		{
			$uri = JURI::getInstance();
			$protocol = strtolower($uri->toString(array('scheme')));

			$mathJaxUrl = ($protocol == 'https://')
				? $params->get('secureScriptUrl')
				: $params->get('scriptUrl');
		}

		if ($mathJaxUrl)
			$document->addScript($mathJaxUrl);
	}
}
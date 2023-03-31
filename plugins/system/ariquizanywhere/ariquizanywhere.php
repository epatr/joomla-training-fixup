<?php
/*
 *
 * @package		ARI Quiz Anywhere plugin
 * @author		ARI Soft
 * @copyright	Copyright (c) 2011 www.ari-soft.com. All rights reserved
 * @license		GNU/GPL (http://www.gnu.org/copyleft/gpl.html)
 * 
 */

defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');

require_once dirname(__FILE__) . '/ariquizanywhere/kernel/class.AriKernel.php';

AriKernel::import('Plugin.ContentPlugin2');

class plgSystemAriquizanywhere extends JPlugin
{ 
	function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config); 

		$this->loadLanguage('', JPATH_ADMINISTRATOR);
	}

	function onAfterRender()
	{
		$mainframe = JFactory::getApplication();
		
		$document = JFactory::getDocument();
		$doctype = $document->getType();

		if ($mainframe->isAdmin() || $doctype !== 'html') 
			return ;

		$option = JRequest::getString('option');
		$view = JRequest::getString('view');
		$task = JRequest::getString('task');
		if (
			($option == 'com_content' && $view == 'form' && JRequest::getString('layout') == 'edit')
			||
			($option == 'com_k2' && $view == 'item' && $task == 'edit')
			||
			($option == 'com_k2' && $view == 'item' && $task == 'save')
			||
			($option == 'com_comprofiler' && $task == 'userDetails')
			)
			return ;

		$plg = new AriQuizAnywhereContentPlugin($this->params);
		if (!$plg->isAriQuizAvailable())
			return ;
			
		require_once JPATH_ADMINISTRATOR . '/components/com_ariquiz/kernel/class.AriKernel.php';
			
		AriKernel::import('Document.DocumentIncludesManager');

		$includesManager = new AriDocumentIncludesManager();

		JResponse::setBody(
			$plg->processContentDefault(JResponse::getBody())
		);

		$includes = $includesManager->getDifferences();
		AriDocumentHelper::addCustomTagsToDocument($includes);
	}
}

class AriQuizAnywhereContentPlugin extends AriContentPlugin2
{
	var $_tag = 'ariquizanywhere';
	var $_isJsLoaded = false;
	var $_isAriQuizAvailable = null;

	function pluginParser($attrs, $content, $baseContent) 
	{
		if (!$this->isAriQuizAvailable())
			return JText::_('PLG_ARIQUIZANYWHERE_COMPONENT_NOT_INSTALLED');

		$quizId = @intval(!empty($attrs['quizId']) ? $attrs['quizId'] : 0, 10);
		$quizName = isset($attrs['name']) ? trim($attrs['name']) : null;
		if ($quizId < 1 && empty($quizName)) 
			return '';

		if ($quizId < 1)
		{
			$quizId = $this->getQuizIdByName($quizName);
			if ($quizId < 1)
				return '';
		}

		AriKernel::import('Web.HtmlHelper');

		$this->registerJs();

		$id = uniqid('aqifr_', false);
		$ifrId = $id . '_ifr';
		$scrolling = $this->_getParam('scrolling', $attrs, 'auto');
		$removeTitle = !!($this->_getParam('removeTitle', $attrs, '0'));
		$loadingMessage = $this->_getParam('loadingMessage', $attrs, '');
		$src = JURI::root(true) . '/index.php?option=com_ariquiz&view=quiz&quizId=' . $quizId . '&tmpl=component';
		$divAttrs = array(
			'id' => $id,
			'class' => 'aq-ic-wrapper aq-ic-loading'
		);
		$ifrAttrs = array(
			'id' => $ifrId,
			'name' => $ifrId,
			'frameborder' => '0',
			'vspace' => '0',
			'hspace' => '0',
			'marginwidth' => '0',
			'marginheight' => '0',
			'scrolling' => $scrolling, 
			'allowTransparency' => 'true',
			'class' => 'aq-ic'
		);
		$content = sprintf('<div%1$s><div class="aq-ic-loading-message">%3$s</div><iframe%2$s></iframe></div>',
			AriHtmlHelper::getAttrStr($divAttrs),
			AriHtmlHelper::getAttrStr($ifrAttrs),
			$loadingMessage
		);

		$document =& JFactory::getDocument();
		$document->addScriptDeclaration(
			sprintf(';(window["aqJQuery"] || jQuery)(function($){ $("#%1$s").jAriQuizInContent({scrolling: "%2$s", src: "%3$s", removeTitle: %4$s, uri: "%5$s"}); });',
				$id,
				$scrolling,
				$src,
				$removeTitle ? 'true' : 'false',
				JURI::base(true) . '/plugins/system/ariquizanywhere/' . (!J1_5 ? 'ariquizanywhere/' : '')
			)
		);
			
		return $content;
	}
	
	function registerJs()
	{
		if ($this->_isJsLoaded) 
			return ;

		$params = $this->_params;
		$isNeedJQLoad = @intval($params->get('loadJQuery', 1), 10);

		$sitePath = JURI::base();
		$document =& JFactory::getDocument();
		if ($isNeedJQLoad) 
		{
			if ($isNeedJQLoad == 1)
				$document->addScript($sitePath . '/plugins/system/ariquizanywhere/' . (!J1_5 ? 'ariquizanywhere/' : '') . 'assets/js/jquery.min.js');
			else
				$document->addScript('//ajax.googleapis.com/ajax/libs/jquery/1.8.0/jquery.min.js');

			$jQueryNoConflict = (bool)$params->get('jQueryNoConflict', true);
			if ($jQueryNoConflict) 
				$document->addScriptDeclaration(';var aqJQuery = jQuery.noConflict();'); 
		}

		$document->addStyleSheet($sitePath . '/plugins/system/' . (!J1_5 ? 'ariquizanywhere/' : '') . 'ariquizanywhere/assets/css/style.css', 'text/css');
		$document->addScript($sitePath . '/plugins/system/' . (!J1_5 ? 'ariquizanywhere/' : '') . 'ariquizanywhere/assets/js/jquery.ariquizanywhere.min.js');

		$this->_isJsLoaded = true;
	}
	
	function getQuizIdByName($quizName)
	{
		$db =& JFactory::getDBO();
		$db->setQuery('SELECT QuizId FROM #__ariquiz WHERE QuizName = ' . $db->Quote($quizName) . ' AND Status = 1 LIMIT 0,1');
		$quizId = $db->loadResult();
		if (empty($quizId) || $db->getErrorNum())
			$quizId = 0;
			
		return $quizId;
	}
	
	function _getParam($key, $attrs, $default = '')
	{
		if (isset($attrs[$key])) 
			return $attrs[$key];

		$params = $this->_params;
		
		return $params->get($key, $default);
	}
	
	function isAriQuizAvailable()
	{
		if (is_null($this->_isAriQuizAvailable))
		{
			$quizPath = JPATH_SITE . '/components/com_ariquiz';
			$this->_isAriQuizAvailable = @file_exists($quizPath); 
		}

		return $this->_isAriQuizAvailable;
	}
}
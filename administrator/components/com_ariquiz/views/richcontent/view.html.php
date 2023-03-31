<?php
/*
 *
 * @package		ARI Quiz
 * @author		ARI Soft
 * @copyright	Copyright (c) 2011 www.ari-soft.com. All rights reserved
 * @license		GNU/GPL (http://www.gnu.org/copyleft/gpl.html)
 * 
 */

(defined('_JEXEC') && defined('ARI_FRAMEWORK_LOADED')) or die;

require_once dirname(__FILE__) . DS . '..' . DS . 'view.php';

function AriQuizRichContentFixBaseUrlHandler()
{
	$body = JResponse::getBody();
	
	if (preg_match('/<base[^>]+>/i', $body))
		JResponse::setBody(preg_replace('/<base[^>]+>/i', '<base href="' . JURI::root() . '" />', $body));
	else
		JResponse::setBody(
			preg_replace('/(<\/head\s*>)/i', '<base href="' . JURI::root() . '" />' . '$1', $body, 1)
		);
}

class AriQuizViewRichcontent extends AriQuizAdminView 
{
	function display($tpl = null) 
	{
		$doc = JFactory::getDocument();

		$doc->addStyleDeclaration('HTML BODY {padding:0;margin:0;}');

		$mainframe = JFactory::getApplication();
		$mainframe->registerEvent('onAfterRender', 'AriQuizRichContentFixBaseUrlHandler');		
		
		parent::display($tpl);
	}
}
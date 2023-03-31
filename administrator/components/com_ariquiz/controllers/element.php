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

AriKernel::import('Joomla.Html.ParameterLoader');
AriKernel::import('Joomla.Controllers.Controller');

class AriQuizControllerElement extends AriController 
{
	function ajaxExecute()
	{
		$element = JRequest::getCmd('element');
		$action = JRequest::getString('action');
		
		if (empty($element) || empty($action))
		{
			JError::raiseError(
				500, 
				JText::_(
					'COM_ARIQUIZ_ERROR_ELEMENTEXECUTION'
				)
			);
			return false;
		}
		
		$action = 'ext' . ucfirst($action);
		$params = new AriJParameterBase(null);
		$params->addElementPath(JPATH_ROOT . '/administrator/components/com_ariquiz/elements');
		$el = $params->loadElement($element);
		if (empty($el) || !method_exists($el, $action))
		{
			JError::raiseError(
				500, 
				JText::_(
					'COM_ARIQUIZ_ERROR_ELEMENTEXECUTION'
				)
			);
			return false;
		}
		
		$res = $el->$action();
		
		return $res;
	}
}
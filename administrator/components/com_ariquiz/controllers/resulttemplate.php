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

AriKernel::import('Joomla.Controllers.Controller');

class AriQuizControllerResulttemplate extends AriController 
{
	function display() 
	{
		$this->redirect('index.php?option=com_ariquiz&view=resulttemplates');
	}

	function add() 
	{
		if (!AriQuizHelper::isAuthorise('texttemplate.create'))
		{
			JError::raiseWarning(500, JText::_('JLIB_APPLICATION_ERROR_CREATE_RECORD_NOT_PERMITTED'));
			$this->redirect('index.php?option=com_ariquiz&view=resulttemplates');
		}

		$model =& $this->getModel();
		$template = $model->getTable();

		$this->_display($template);
	}
	
	function edit()
	{
		if (!AriQuizHelper::isAuthorise('texttemplate.edit'))
		{
			JError::raiseWarning(500, JText::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'));
			$this->redirect('index.php?option=com_ariquiz&view=resulttemplates');
		}

		$templateId = JRequest::getInt('templateId');
		$model =& $this->getModel();
		$template = $model->getTemplate($templateId);
		if (is_null($template))
		{
			JError::raiseError(
				500, 
				JText::sprintf(
					'COM_ARIQUIZ_ERROR_LOAD_TEXTTEMPLATE', 
					__CLASS__ . '::' . __FUNCTION__ . '()', 
					$templateId
				)
			);
			
			return ;
		}

		$this->_display($template);
	}
	
	function _display($template)
	{
		$data = $this->getRequestData();
		if (!is_null($data))
			$template->bind($data);

		$view =& $this->getView();
		$view->display($template);
	}

	function apply()
	{
		JRequest::checkToken() or jexit('Invalid Token');

		$template = $this->_save();
		$this->redirect('index.php?option=com_ariquiz&view=resulttemplate&task=edit&templateId=' . $template->TemplateId . '&__MSG=COM_ARIQUIZ_COMPLETE_TEMPLATESAVE');
	}

	function save()
	{
		JRequest::checkToken() or jexit('Invalid Token');
		
		$this->_save();
		$this->redirect('index.php?option=com_ariquiz&view=resulttemplates&__MSG=COM_ARIQUIZ_COMPLETE_TEMPLATESAVE');
	}

	function cancel()
	{
		$this->redirect('index.php?option=com_ariquiz&view=resulttemplates');
	}
	
	function ajaxIsTemplateNameUnique()
	{
		$model =& $this->getModel();
		
		$templateName = JRequest::getString('templateName');
		$templateGroup = JRequest::getString('templateGroup');
		$templateId = JRequest::getInt('templateId');
		
		return $model->isUniqueTemplateName($templateName, $templateGroup, $templateId);
	}
	
	function _save($redirectOnError = true) 
	{
		$model =& $this->getModel(); 
		$data = JRequest::getVar('params', null, 'default', 'none', JREQUEST_ALLOWRAW);

		$templateId = AriUtils::getParam($data, 'TemplateId', 0);
		if ($templateId > 0)
		{
			if (!AriQuizHelper::isAuthorise('texttemplate.edit'))
			{
				JError::raiseWarning(500, JText::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'));
				$this->redirect('index.php?option=com_ariquiz&view=resulttemplates');
			}
		}
		else
		{
			if (!AriQuizHelper::isAuthorise('texttemplate.create'))
			{
				JError::raiseWarning(500, JText::_('JLIB_APPLICATION_ERROR_CREATE_RECORD_NOT_PERMITTED'));
				$this->redirect('index.php?option=com_ariquiz&view=resulttemplates');
			}			
		}
		
		AriKernel::import('Joomla.Form.Form');

		$commonSettingsForm = new AriForm('commonSettings');
		$commonSettingsForm->load(AriQuizHelper::getFormPath('resulttemplate', 'resulttemplate'));
		$commonSettingsForm->bind($data);
		$cleanData = $commonSettingsForm->toArray();
		if (!$commonSettingsForm->validate($cleanData))
		{
			if ($redirectOnError)
			{
				$this->setRequestData($data);
				if ($templateId > 0)
					$this->redirect('index.php?option=com_ariquiz&view=resulttemplate&task=edit&__MSG=COM_ARIQUIZ_ERROR_ENTITYSAVE&templateId=' . $templateId);
				else
					$this->redirect('index.php?option=com_ariquiz&view=resulttemplate&task=add&__MSG=COM_ARIQUIZ_ERROR_ENTITYSAVE');
			}

			return null;
		}

		return $model->saveTemplate($cleanData);
	}
}
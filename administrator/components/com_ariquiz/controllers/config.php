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

class AriQuizControllerConfig extends AriController 
{
	function display() 
	{
		$config = AriQuizHelper::getConfig();

		$view =& $this->getView();
		$view->display($config, JRequest::getInt('quizActiveTab'));
	}

	function save()
	{
		JRequest::checkToken() or jexit('Invalid Token');

        $activeTab = JRequest::getInt('quizActiveTab');

		$this->_save();
		$this->redirect('index.php?option=com_ariquiz&view=config' . ($activeTab ? '&quizActiveTab=' . $activeTab : '') . '&__MSG=COM_ARIQUIZ_COMPLETE_CONFIGSAVE');
	}

	function _save()
	{
		if (!AriQuizHelper::isAuthorise('core.admin'))
		{
			$this->setError(JText::_('JLIB_APPLICATION_ERROR_CREATE_RECORD_NOT_PERMITTED'));
			$this->redirect('index.php?option=com_ariquiz&view=config');
		}

		$data = JRequest::getVar('params', null, 'default', 'none', JREQUEST_ALLOWRAW);

		unset($data['DefaultCategoryId']);
		unset($data['DefaultBankCategoryId']);
		unset($data['Version']);
		unset($data['FilesPath']);
		unset($data['HelpPath']);

		$config = AriQuizHelper::getConfig();
		$group = $config->getGroups();
		$config->bind($data, $group);
		$config->save(true, $group);
	}
}
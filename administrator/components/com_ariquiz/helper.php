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

jimport('joomla.filter.filterinput');

AriKernel::import('Utils.Config');

class AriQuizHelper
{
	function getConfig()
	{
		return AriConfigFactory::getInstance('AriQuizConfig', 'Application.ARIQuiz.Utils.Config');
	}
	
	function getDataConfigPath()
	{
		return JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_ariquiz' . DS . 'config' . DS . 'data.xml';
	}
	
	function getFilesDir($group = '')
	{
		$config = AriQuizHelper::getConfig();
		$filesDir = $config->get('FilesPath') . DS;
		
		$filter =& JFilterInput::getInstance();
		$group = $filter->clean($group, 'CMD');
		if ($group)
			$filesDir .=  $group . DS;

		$filesDir = JPATH_ROOT . DS . $filesDir;

		return $filesDir;
	}
	
	function getFrontendFormPath($group, $form)
	{
		return AriQuizHelper::getFormPath($group, $form, JPATH_ROOT . DS . 'components' . DS . 'com_ariquiz' . DS . 'models' . DS . 'forms' . DS);
	}
	
	function getFormPath($group, $form, $formPath = null)
	{
		if (is_null($formPath))
			$formPath = JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_ariquiz' . DS . 'models' . DS . 'forms' . DS;

		$filter =& JFilterInput::getInstance();
		$group = $filter->clean($group, 'CMD');
		$form = $filter->clean($form, 'CMD');

		if (empty($group) || empty($form))
			return null;
			
		return $formPath . $group . DS . $form . '.xml'; 
	}
	
	function getDefaultQuestionType($questionTypes)
	{
		$config = AriQuizHelper::getConfig();
		$defQuestionType = $config->get('DefaultQuestionType');
		if (empty($defQuestionType))
		{
			if (is_array($questionTypes) && count($questionTypes) > 0)
				$defQuestionType = $questionTypes[0]->QuestionTypeId;
			else 
				$defQuestionType = null;
		}
		
		return $defQuestionType;
	}
	
	function getQuizAccessError($errorCode)
	{
		$msgKey = '';
		switch ($errorCode)
		{
			case ARIQUIZ_TAKEQUIZERROR_LAGTIME:
				$msgKey = 'COM_ARIQUIZ_ACCESSERROR_LAGTIME';
				break;
				
			case ARIQUIZ_TAKEQUIZERROR_ATTEMPTCOUNT:
				$msgKey = 'COM_ARIQUIZ_ACCESSERROR_ATTEMPTCOUNT';
				break;
				
			case ARIQUIZ_TAKEQUIZERROR_NOTREGISTERED:
				$msgKey = 'COM_ARIQUIZ_ACCESSERROR_NOTREGISTERED';
				break;
				
			case ARIQUIZ_TAKEQUIZERROR_NOTHAVEPERMISSIONS:
				$msgKey = 'COM_ARIQUIZ_ACCESSERROR_PERMISSIONS';
				break;

			case ARIQUIZ_TAKEQUIZERROR_DATEACCESS:
				$msgKey = 'COM_ARIQUIZ_ACCESSERROR_DATE';
				break;
				
			case ARIQUIZ_TAKEQUIZERROR_UNKNOWNERROR:
			case ARIQUIZ_TAKEQUIZERROR_HASPAUSEDQUIZ:
				$msgKey = 'COM_ARIQUIZ_ACCESSERROR_UNKNOWN';
				break;	
				
			case ARIQUIZ_TAKEQUIZERROR_ANOTHERUSER:
				$msgKey = 'COM_ARIQUIZ_ACCESSERROR_ANOTHERUSER';
				break;

            case ARIQUIZ_TAKEQUIZERROR_PREVQUIZ:
                $msgKey = 'COM_ARIQUIZ_ACCESSERROR_PREVQUIZ';
                break;
		}

		return $msgKey;
	}
	
	function getPaginatorOptions()
	{
		return array(
			'firstPageLinkLabel' => JText::_('COM_ARIQUIZ_LABEL_DTFIRSTPAGE'),
			'lastPageLinkLabel' => JText::_('COM_ARIQUIZ_LABEL_DTLASTPAGE'),
			'nextPageLinkLabel' => JText::_('COM_ARIQUIZ_LABEL_DTNEXTPAGE'),
			'previousPageLinkLabel' => JText::_('COM_ARIQUIZ_LABEL_DTPREVPAGE'),
			'pageReportTemplate' => JText::_('COM_ARIQUIZ_LABEL_DTPAGEREPORTTEMPLATE'),
			'template' => JText::_('COM_ARIQUIZ_LABEL_DTTEMPLATE')
		);
	}
	
	function getShortPeriods()
	{
		return array(
			JText::_('COM_ARIQUIZ_DATE_DAYSHORT') => 86400,
			JText::_('COM_ARIQUIZ_DATE_HOURSHORT') => 3600,
			JText::_('COM_ARIQUIZ_DATE_MINUTESHORT') => 60,
			JText::_('COM_ARIQUIZ_DATE_SECONDSHORT') => 1
		);
	}
	
	function addSubmenu($vName)
	{
		JSubMenuHelper::addEntry(
			JText::_('COM_ARIQUIZ_MENU_QUIZZES'),
			'index.php?option=com_ariquiz&view=quizzes',
			$vName == 'quizzes'
		);

		JSubMenuHelper::addEntry(
			JText::_('COM_ARIQUIZ_MENU_QUIZCATEGORIES'),
			'index.php?option=com_ariquiz&view=categories',
			$vName == 'categories'
		);

		JSubMenuHelper::addEntry(
			JText::_('COM_ARIQUIZ_MENU_BANKCATEGORIES'),
			'index.php?option=com_ariquiz&view=bankcategories',
			$vName == 'bankcategories'
		);

		JSubMenuHelper::addEntry(
			JText::_('COM_ARIQUIZ_MENU_QUESTIONCATEGORIES'),
			'index.php?option=com_ariquiz&view=questioncategories',
			$vName == 'questioncategories'
		);
		
		JSubMenuHelper::addEntry(
			JText::_('COM_ARIQUIZ_MENU_QUESTIONBANK'),
			'index.php?option=com_ariquiz&view=bankquestions',
			$vName == 'bankquestions'
		);
		
		JSubMenuHelper::addEntry(
			JText::_('COM_ARIQUIZ_MENU_RESULTSCALES'),
			'index.php?option=com_ariquiz&view=resultscales',
			$vName == 'resultscales'
		);
		
		JSubMenuHelper::addEntry(
			JText::_('COM_ARIQUIZ_MENU_QUESTIONTEMPLATES'),
			'index.php?option=com_ariquiz&view=questiontemplates',
			$vName == 'questiontemplates'
		);
		
		JSubMenuHelper::addEntry(
			JText::_('COM_ARIQUIZ_MENU_TEXTTEMPLATES'),
			'index.php?option=com_ariquiz&view=resulttemplates',
			$vName == 'resulttemplates'
		);
		
		JSubMenuHelper::addEntry(
			JText::_('COM_ARIQUIZ_MENU_MAILTEMPLATES'),
			'index.php?option=com_ariquiz&view=mailtemplates',
			$vName == 'mailtemplates'
		);
		
		JSubMenuHelper::addEntry(
			JText::_('COM_ARIQUIZ_MENU_QUIZZESRESULTS'),
			'index.php?option=com_ariquiz&view=quizresults',
			$vName == 'quizresults'
		);
		
		JSubMenuHelper::addEntry(
			JText::_('COM_ARIQUIZ_MENU_CONFIG'),
			'index.php?option=com_ariquiz&view=config',
			$vName == 'config'
		);
		
		JSubMenuHelper::addEntry(
			JText::_('COM_ARIQUIZ_MENU_ABOUT'),
			'index.php?option=com_ariquiz&view=about',
			$vName == 'about'
		);
	}

	function getDefaultCategoryId()
	{
		$config = AriQuizHelper::getConfig();
		$defaultCategoryId = intval($config->get('DefaultCategoryId'), 10);
		
		return $defaultCategoryId;
	}

	function getDefaultBankCategoryId()
	{
		$config = AriQuizHelper::getConfig();
		$defaultCategoryId = intval($config->get('DefaultBankCategoryId'), 10);
		
		return $defaultCategoryId;
	}
	
	function isACLEnabled()
	{
		$config = AriQuizHelper::getConfig();
		$isACLEnabled = (bool)$config->get('EnableACL');
		
		return $isACLEnabled;
	}
	
	function isAuthorise($action, $assetName = 'com_ariquiz', $checkInherit = true)
	{
		if (J1_5 || !AriQuizHelper::isACLEnabled())
			return true;
		
		$user = JFactory::getUser();
		if (!($res = $user->authorise($action, $assetName))) 
		{
			if (is_null($res) && $assetName != 'com_ariquiz')
				$res = $user->authorise($action, 'com_ariquiz');

			return $res;
		}

		return true;
	}
	
	function formatPageTitle($title)
	{
		$app = JFactory::getApplication();
	
		if ($app->getCfg('sitename_pagetitles', 0) == 1)
			$title = JText::sprintf('JPAGETITLE', $app->getCfg('sitename'), $title);
		else if ($app->getCfg('sitename_pagetitles', 0) == 2)
			$title = JText::sprintf('JPAGETITLE', $title, $app->getCfg('sitename'));
			
		return $title;
	}
}
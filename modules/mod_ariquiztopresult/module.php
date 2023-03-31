<?php
/*
 * ARI Quiz Top Result Joomla! module
 *
 * @package		ARI Quiz Top Result Joomla! module
 * @version		1.0.0
 * @author		ARI Soft
 * @copyright	Copyright (c) 2010 www.ari-soft.com. All rights reserved
 * @license		GNU/GPL (http://www.gnu.org/copyleft/gpl.html)
 * 
 */

(defined('_JEXEC') && defined('ARI_FRAMEWORK_LOADED')) or die;

jimport('joomla.application.component.model');

AriKernel::import('Utils.Utils');
AriKernel::import('Joomla.Models.Model');
AriKernel::import('Joomla.Tables.Table');

require_once JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_ariquiz' . DS . 'models' . DS . 'quizresults.php';
require_once JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_ariquiz' . DS . 'tables' . DS . 'quiz.php';
require_once JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_ariquiz' . DS . 'tables' . DS . 'userquiz.php';

AriModel::addIncludePath(JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_ariquiz' . DS . 'models');
AriTable::addIncludePath(JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_ariquiz' . DS . 'tables');

$count = intval($params->get('count', 5), 10);
if ($count < 0) 
	$count = 5;
$measureUnit = $params->get('pointUnit', 'percent');
$label = $params->get('label', '');
$nameField = ($params->get('nameField', 'username') == 'login' ? 'LoginName' : 'UserName');
$ignoreGuest = (bool)$params->get('ignoreGuest', true);
$hideQuizName = (bool)$params->get('hideQuizName', true);
$aggregateResults = (bool)$params->get('aggregateResults', true);
$aggregateUserResults = (bool)$params->get('aggregateUserResults', true);
$colspan = $hideQuizName ? 2 : 3;
$emptyMessage = trim($params->get('emptyMessage', ''));

$categoryId = $params->get('categoryId', null);
if ($categoryId || $categoryId === '0') 
	$categoryId = explode(',', $categoryId);

$tzOffset = floatval($params->get('time_zone')) * 60 * 60; 
$dateFilterType = $params->get('dateFilterType');
$startDate = null;
$endDate = null;
if ($dateFilterType == 'dateFilterType_range')
{
	$startDate = $params->get('daterange_start_date', null);
	$endDate = $params->get('daterange_end_date', null);
	
	if ($startDate)
	{
		$dateInfo = getdate(strtotime($startDate));
		$startDate = mktime(0, 0, 1, $dateInfo['mon'], $dateInfo['mday'], $dateInfo['year']);
	}
	
	if ($endDate)
	{
		$dateInfo = getdate(strtotime($endDate));
		$endDate = mktime(23, 59, 59, $dateInfo['mon'], $dateInfo['mday'], $dateInfo['year']);
	}
}
else if ($dateFilterType == 'dateFilterType_recurrence')
{
	$recurrence_type = $params->get('recurrence_type', 'month');
	switch ($recurrence_type)
	{
		case 'day':
			$startDate = gmmktime(0, 0, 1);
			$endDate = gmmktime(23, 59, 59);
			break;
			
		case 'week':
			$dateInfo = getdate(strtotime('Monday this week'));
			$startDate = gmmktime(0, 0, 1, $dateInfo['mon'], $dateInfo['mday'], $dateInfo['year']);
			
			$dateInfo = getdate(strtotime('Sunday this week'));
			$endDate = gmmktime(23, 59, 59, $dateInfo['mon'], $dateInfo['mday'], $dateInfo['year']);
			break;

		case 'month':
			$startDate = gmmktime(0, 0, 1, gmdate('n'), 1);
			$endDate = gmmktime(23, 59, 59, gmdate('n') + 1, 0);
			break;

		case 'year':
			$startDate = gmmktime(0, 0, 1, 1, 1);
			$endDate = gmmktime(23, 59, 59, 1, 0, gmdate('Y') + 1);
			break;
	}
}

if ($startDate)
	$startDate -= $tzOffset;
	
if ($endDate)
	$endDate -= $tzOffset;
	
$quizResultsModel = AriModel::getInstance('quizresults', 'AriQuizModel');
$results = $aggregateResults 
	? $quizResultsModel->getAggregateTopResults($count, $ignoreGuest, $categoryId, $startDate, $endDate)
	: $quizResultsModel->getTopResults($count, $ignoreGuest, $categoryId, $aggregateUserResults, $startDate, $endDate);

$linkItemId = 0;
$addMenuItemToLink = (bool)$params->get('addMenuItemToLink');
if ($addMenuItemToLink)
{
	$linkItemId = intval($params->get('itemId'));
	if ($linkItemId < 1)
		$addMenuItemToLink = false;
}
$showQuizLink = (bool)$params->get('showQuizLink');
	
$user = JFactory::getUser();
$userId = $user->get('id');

$baseUri = JURI::root(true) . '/modules/mod_ariquiztopresult/assets/';
$doc =& JFactory::getDocument(); 
$doc->addStyleSheet($baseUri . 'css/styles.css');

require JModuleHelper::getLayoutPath('mod_ariquiztopresult');
<?php
/*
 * ARI Quiz Result Joomla! module
 *
 * @package		ARI Quiz Result Joomla! module
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
$emptyMessage = trim($params->get('emptyMessage', ''));

$categoryId = $params->get('categoryId', null);
if ($categoryId || $categoryId === '0') 
	$categoryId = explode(',', $categoryId);

$quizResultsModel = AriModel::getInstance('Quizresults', 'AriQuizModel');
$results = $quizResultsModel->getLastResults($count, $ignoreGuest, $categoryId);

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

$baseUri = JURI::root(true) . '/modules/mod_ariquizresult/assets/';
$doc =& JFactory::getDocument(); 
$doc->addStyleSheet($baseUri . 'css/styles.css');

require JModuleHelper::getLayoutPath('mod_ariquizresult');
<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2018 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

// no direct access
defined('_JEXEC') or die;

// Require library + register autoloader
require_once JPATH_ADMINISTRATOR . '/components/com_eventbooking/libraries/rad/bootstrap.php';
require_once dirname(__FILE__) . '/helper.php';

// Load css
JFactory::getDocument()->addStyleSheet(JUri::root(true) . '/modules/mod_eb_googlemap/asset/style.css');
EventbookingHelper::loadComponentCssForModules();

JHtml::_('jquery.framework');
JHtml::_('script', 'media/com_eventbooking/assets/js/eventbookingjq.js', false, false);

// params
$width  = $params->get('width', 100);
$height = $params->get('height', 400);
$ebMap  = new modEventBookingGoogleMapHelper($module, $params);

require JModuleHelper::getLayoutPath('mod_eb_googlemap');

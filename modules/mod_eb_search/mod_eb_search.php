<?php
/**
 * @package        Joomla
 * @subpackage     Event Booking
 * @author         Tuan Pham Ngoc
 * @copyright      Copyright (C) 2010 Ossolution Team
 * @license        GNU/GPL, see LICENSE.php
 */
defined('_JEXEC') or die('');

// Require library + register autoloader
require_once JPATH_ADMINISTRATOR . '/components/com_eventbooking/libraries/rad/bootstrap.php';

EventbookingHelper::loadLanguage();

EventbookingHelper::loadComponentCssForModules();

$config = EventbookingHelper::getConfig();
$input  = JFactory::getApplication()->input;

$showCategory       = $params->get('show_category', 1);
$showLocation       = $params->get('show_location', 0);
$enableRadiusSearch = $params->get('enable_radius_search', 0);
$showFromDate       = $params->get('show_from_date', 0);
$showToDate         = $params->get('show_to_date', 0);

$categoryId     = $input->getInt('category_id', 0);
$locationId     = $input->getInt('location_id', 0);
$fromDate       = $input->getString('filter_from_date');
$toDate         = $input->getString('filter_to_date');
$text           = $input->getString('search');
$filterAddress  = $input->getString('filter_address');
$filterDistance = $input->getInt('filter_distance');

if (empty($text))
{
	$text = JText::_('EB_SEARCH_WORD');
}

if ($fromDate && !EventbookingHelper::isValidDate($fromDate))
{
	$fromDate = '';
}

if ($toDate && !EventbookingHelper::isValidDate($toDate))
{
	$toDate = '';
}

$text = htmlspecialchars($text, ENT_COMPAT, 'UTF-8');

$db          = JFactory::getDbo();
$query       = $db->getQuery(true);
$fieldSuffix = EventbookingHelper::getFieldSuffix();

//Build Category Drodown
if ($showCategory)
{
	$query->select('id, parent AS parent_id')
		->select($db->quoteName('name' . $fieldSuffix, 'title'))
		->from('#__eb_categories')
		->where('published = 1')
		->where('`access` IN (' . implode(',', JFactory::getUser()->getAuthorisedViewLevels()) . ')')
		->order('name');
	$db->setQuery($query);
	$rows = $db->loadObjectList();

	for ($i = 0, $n = count($rows); $i < $n; $i++)
	{
		$row = $rows[$i];

		if (!EventbookingHelper::getTotalEvent($row->id))
		{
			unset($rows[$i]);
		}
	}

	$children = array();

	if ($rows)
	{
		// first pass - collect children
		foreach ($rows as $v)
		{
			$pt   = $v->parent_id;
			$list = @$children[$pt] ? $children[$pt] : array();
			array_push($list, $v);
			$children[$pt] = $list;
		}
	}

	$list      = JHtml::_('menu.treerecurse', 0, '', array(), $children, 9999, 0, 0);
	$options   = array();
	$options[] = JHtml::_('select.option', 0, JText::_('EB_SELECT_CATEGORY'));

	foreach ($list as $listItem)
	{
		$options[] = JHtml::_('select.option', $listItem->id, '&nbsp;&nbsp;&nbsp;' . $listItem->treename);
	}

	$lists['category_id'] = JHtml::_('select.genericlist', $options, 'category_id', array(
		'option.text.toHtml' => false,
		'list.attr'          => 'class="inputbox category_box" ',
		'option.text'        => 'text',
		'option.key'         => 'value',
		'list.select'        => $categoryId,
	));
}

//Build location dropdown
if ($showLocation)
{
	$user   = JFactory::getUser();
	$config = EventbookingHelper::getConfig();

	$query->clear()
		->select('a.id')
		->select($db->quoteName('a.name' . $fieldSuffix, 'name'))
		->from('#__eb_locations AS a')
		->where('a.published = 1')
		->order('a.name');

	$subQuery = $db->getQuery(true);
	$subQuery->select('DISTINCT location_id')
		->from('#__eb_events AS b')
		->where('b.published = 1')
		->where('b.access IN (' . implode(',', $user->getAuthorisedViewLevels()) . ')');

	if ($config->hide_past_events)
	{
		$currentDate = $db->quote(JHtml::_('date', 'Now', 'Y-m-d'));

		if ($config->show_children_events_under_parent_event)
		{
			$subQuery->where('(DATE(b.event_date) >= ' . $currentDate . ' OR DATE(b.cut_off_date) >= ' . $currentDate . ' OR DATE(b.max_end_date) >= ' . $currentDate . ')');
		}
		else
		{
			$subQuery->where('(DATE(b.event_date) >= ' . $currentDate . ' OR DATE(b.cut_off_date) >= ' . $currentDate . ')');
		}
	}

	$query->where('a.id IN (' . $subQuery . ')');

	$db->setQuery($query);
	$options              = array();
	$options[]            = JHtml::_('select.option', 0, JText::_('EB_SELECT_LOCATION'), 'id', 'name');
	$options              = array_merge($options, $db->loadObjectList());
	$lists['location_id'] = JHtml::_('select.genericlist', $options, 'location_id', ' class="inputbox location_box" ', 'id', 'name', $locationId);
}

if ($enableRadiusSearch)
{
	$radiusOptions = $params->get('radius_options', '5,10,20,30,50,100,200');
	$options       = [];
	$radiusOptions = explode(',', $radiusOptions);
	$radiusOptions = array_map('trim', $radiusOptions);

	foreach ($radiusOptions as $option)
	{
		$options[] = JHtml::_('select.option', (int) $option, JText::sprintf('EB_WITHIN_X_KM', $option));
	}

	$lists['filter_distance'] = JHtml::_('select.genericlist', $options, 'filter_distance', ' class="inputbox location_box" ', 'value', 'text', $filterDistance);
}

$presetCategoryId = (int) $params->get('category_id');

$itemId = (int) $params->get('item_id');

if (!$itemId)
{
	$itemId = EventbookingHelper::getItemid();
}


$layout = $params->get('layout_type', 'default');

require JModuleHelper::getLayoutPath('mod_eb_search', $params->get('module_layout', 'default'));
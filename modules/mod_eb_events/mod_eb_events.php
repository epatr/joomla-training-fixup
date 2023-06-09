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

$document = JFactory::getDocument();
$user     = JFactory::getUser();
$config   = EventbookingHelper::getConfig();
$baseUrl  = JUri::base(true);

// Load component language
EventbookingHelper::loadLanguage();

// Load javascript files
JHtml::_('jquery.framework');
JHtml::_('script', JUri::root() . '/media/com_eventbooking/assets/js/eventbookingjq.js', false, false);

// Load CSS
$layout = $params->get('layout', 'default');

if ($layout == 'default')
{
	$document->addStyleSheet($baseUrl . '/modules/mod_eb_events/css/style.css');
}
elseif (strpos($layout, 'improved') !== false)
{
	$document->addStyleSheet($baseUrl . '/modules/mod_eb_events/css/improved.css');
}

EventbookingHelper::loadComponentCssForModules();

$db      = JFactory::getDbo();
$query   = $db->getQuery(true);

$fieldSuffix = EventbookingHelper::getFieldSuffix();
$currentDate = $db->quote(EventbookingHelper::getServerTimeFromGMTTime());
$nullDate    = $db->quote($db->getNullDate());

$numberEventPerRow    = $params->get('event_per_row', 2);
$numberEvents         = $params->get('number_events', 6);
$categoryIds          = trim($params->get('category_ids', ''));
$showCategory         = $params->get('show_category', 1);
$showLocation         = $params->get('show_location', 0);
$showThumb            = $params->get('show_thumb', 0);
$showShortDescription = $params->get('show_short_description', 1);
$showPrice            = $params->get('show_price', 0);
$itemId               = (int) $params->get('item_id', 0);

if (!$itemId)
{
	$itemId = EventbookingHelper::getItemid();
}

$query->select('a.*, c.address AS location_address')
	->select($db->quoteName('c.name' . $fieldSuffix, 'location_name'))
	->select("DATEDIFF(a.early_bird_discount_date, $currentDate) AS date_diff")
	->select("DATEDIFF($currentDate, a.late_fee_date) AS late_fee_date_diff")
	->select("DATEDIFF(a.event_date, $currentDate) AS number_event_dates")
	->select("TIMESTAMPDIFF(MINUTE, a.registration_start_date, $currentDate) AS registration_start_minutes")
	->select("TIMESTAMPDIFF(MINUTE, a.cut_off_date, $currentDate) AS cut_off_minutes")
	->select('IFNULL(SUM(b.number_registrants), 0) AS total_registrants')
	->from('#__eb_events AS a')
	->leftJoin('#__eb_registrants AS b ON (a.id = b.event_id AND b.group_id=0 AND (b.published = 1 OR (b.payment_method LIKE "os_offline%" AND b.published NOT IN (2,3))))')
	->leftJoin('#__eb_locations AS c ON a.location_id = c.id')
	->where('a.published = 1')
	->where('a.access IN (' . implode(',', $user->getAuthorisedViewLevels()) . ')')
	->where('(a.publish_up = ' . $nullDate . ' OR a.publish_up <= ' . $currentDate . ')')
	->where('(a.publish_down = ' . $nullDate . ' OR a.publish_down >= ' . $currentDate . ')');

if ($config->show_until_end_date)
{
	$query->where('(a.event_date >= ' . $currentDate . ' OR a.event_end_date >= ' . $currentDate . ')');
}
else
{
	$query->where('(a.event_date >= ' . $currentDate . ' OR a.cut_off_date >= ' . $currentDate . ')');
}

if ($params->get('only_show_featured_events', 0))
{
	$query->where('a.featured = 1');
}

if (!$params->get('show_children_events', 1))
{
	$query->where('a.parent_id = 0');
}

if ($locationId = $params->get('location_id', 0))
{
	$query->where('a.location_id = ' . $locationId);
}

if ($createdBy = $params->get('created_by'))
{
	$query->where('a.created_by = ' . $createdBy);
}

if ($fieldSuffix)
{
	$query->select($db->quoteName('a.title' . $fieldSuffix, 'title'))
		->select($db->quoteName('a.short_description' . $fieldSuffix, 'short_description'))
		->where($db->quoteName('a.title' . $fieldSuffix) . ' != ""')
		->where($db->quoteName('a.title' . $fieldSuffix) . ' IS NOT NULL');
}

if ($categoryIds)
{
	$query->where('a.id IN (SELECT event_id FROM #__eb_event_categories WHERE category_id IN (' . $categoryIds . '))');
}

if (JFactory::getApplication()->getLanguageFilter())
{
	$query->where('a.language IN (' . $db->quote(JFactory::getLanguage()->getTag()) . ',' . $db->quote('*') . ', "")');
}

$query->group('a.id');

// Display featured events at the top if configured
if ($config->display_featured_events_on_top)
{
	$query->order('a.featured DESC');
}

$query->order('a.event_date');

$db->setQuery($query, 0, $numberEvents);
$rows = $db->loadObjectList();

$query->clear()
	->select('a.id')
	->select($db->quoteName('a.name' . $fieldSuffix, 'name'))
	->from('#__eb_categories AS a')
	->innerJoin('#__eb_event_categories AS b ON a.id = b.category_id');

for ($i = 0, $n = count($rows); $i < $n; $i++)
{
	$row = $rows[$i];
	$query->where('b.event_id = ' . $row->id);
	$db->setQuery($query);
	$categories             = $db->loadObjectList();
	$row->number_categories = count($categories);

	if (count($categories))
	{
		$itemCategories = array();

		foreach ($categories as $category)
		{
			$itemCategories[] = '<a href="' . EventbookingHelperRoute::getCategoryRoute($category->id, $itemId) . '"><strong>' . $category->name .
				'</strong></a>';
		}

		$row->categories     = implode('&nbsp;|&nbsp;', $itemCategories);
		$row->category       = $categories[0];
		$row->itemCategories = $categories;
	}

	$query->clear('where');
}



require JModuleHelper::getLayoutPath('mod_eb_events', $layout);

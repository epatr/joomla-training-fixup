<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2018 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\Utilities\ArrayHelper;

/**
 * @property EventbookingModelRegistrantlist $model
 */
class EventbookingViewRegistrantlistHtml extends RADViewHtml
{
	public function display()
	{
		if (!EventbookingHelperAcl::canViewRegistrantList())
		{
			return;
		}

		$state   = $this->model->getState();
		$eventId = $state->id;

		if ($eventId)
		{
			$rows = $this->model->getData();

			$config = EventbookingHelper::getConfig();

			$event = EventbookingHelperDatabase::getEvent($eventId);

			$customFieldIds = trim($event->custom_field_ids);

			if (!$customFieldIds)
			{
				$customFieldIds = trim($config->registrant_list_custom_field_ids);
			}

			if ($customFieldIds)
			{
				$db             = JFactory::getDbo();
				$query          = $db->getQuery(true);
				$fields         = explode(',', $customFieldIds);
				$fields         = ArrayHelper::toInteger($fields);
				$customFieldIds = implode(',', $fields);
				$fieldTitles    = array();
				$fieldSuffix    = EventbookingHelper::getFieldSuffix();
				$query->select('id, name, is_core')
					->select($db->quoteName('title' . $fieldSuffix, 'title'))
					->from('#__eb_fields')
					->where('id IN (' . $customFieldIds . ')');
				$db->setQuery($query);
				$rowFields = $db->loadObjectList();

				foreach ($rowFields as $rowField)
				{
					$fieldTitles[$rowField->id] = $rowField->title;
				}

				$this->fieldTitles  = $fieldTitles;
				$this->fieldValues  = $this->model->getFieldsData($fields);
				$this->fields       = $fields;
				$displayCustomField = true;
			}
			else
			{
				$displayCustomField = false;
			}

			$this->items              = $rows;
			$this->pagination         = $this->model->getPagination();
			$this->config             = $config;
			$this->displayCustomField = $displayCustomField;
			$this->bootstrapHelper    = EventbookingHelperBootstrap::getInstance();
			$this->coreFields         = EventbookingHelperRegistration::getPublishedCoreFields();
			$this->event              = $event;

			parent::display();
		}
	}
}

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

use Joomla\Registry\Registry;

class plgEventBookingAcymailing extends JPlugin
{
	public function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);

		JFactory::getLanguage()->load('plg_eventbooking_acymailing', JPATH_ADMINISTRATOR);
	}

	/**
	 * Return list of custom fields in ACYMailing which will be used to map with fields in Events Booking
	 *
	 * @return array
	 */
	public function onGetNewsletterFields()
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName(['namekey', 'namekey'], ['value', 'text']))
			->from('#__acymailing_fields')
			->where('namekey NOT IN ("name", "email", "html")');
		$db->setQuery($query);

		return $db->loadObjectList();
	}

	/**
	 * Render setting form
	 *
	 * @param JTable $row
	 *
	 * @return array
	 */
	public function onEditEvent($row)
	{
		if (!is_dir(JPATH_ADMINISTRATOR . '/components/com_acymailing'))
		{
			return array('title' => JText::_('PLG_EB_ACYMAILING_LIST_SETTINGS'),
			             'form'  => JText::_('Please install component Acymailing'),
			);
		}

		ob_start();

		$this->drawSettingForm($row);

		return array('title' => JText::_('PLG_EB_ACYMAILING_LIST_SETTINGS'),
		             'form'  => ob_get_clean(),
		);
	}

	/**
	 * Store setting into database, in this case, use params field of plans table
	 *
	 * @param EventbookingTableEvent $row
	 * @param bool                   $isNew true if create new plan, false if edit
	 */
	public function onAfterSaveEvent($row, $data, $isNew)
	{
		$params = new Registry($row->params);
		$params->set('acymailing_list_ids', implode(',', $data['acymailing_list_ids']));
		$row->params = $params->toString();

		$row->store();
	}

	/**
	 * Run when a membership activated
	 *
	 * @param EventbookingTableRegistrant $row
	 */
	public function onAfterStoreRegistrant($row)
	{
		if (!file_exists(JPATH_ADMINISTRATOR . '/components/com_acymailing/acymailing.php'))
		{
			return;
		}

		$config = EventbookingHelper::getConfig();

		// In case subscriber doesn't want to subscribe to newsleter, stop
		if ($config->show_subscribe_newsletter_checkbox && empty($row->subscribe_newsletter))
		{
			return;
		}

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		// Only add subscribers to newsletter if they agree.
		if ($subscribeNewsletterField = $this->params->get('subscribe_newsletter_field'))
		{
			$query->select('name, fieldtype')
				->from('#__eb_fields')
				->where('id = ' . $db->quote((int) $subscribeNewsletterField));
			$db->setQuery($query);
			$field     = $db->loadObject();
			$fieldType = $field->fieldtype;
			$fieldName = $field->name;

			if ($fieldType == 'Checkboxes')
			{
				if (!isset($_POST[$fieldName]))
				{
					return;
				}
			}
			else
			{
				$fieldValue = strtolower(JFactory::getApplication()->input->getString($fieldName));

				if (empty($fieldValue) || $fieldValue == 'no' || $fieldValue == '0')
				{
					return;
				}
			}
		}

		$event = JTable::getInstance('EventBooking', 'Event');
		$event->load($row->event_id);
		$params  = new Registry($event->params);
		$listIds = $params->get('acymailing_list_ids', '');

		if (empty($listIds))
		{
			$listIds = $this->params->get('default_list_ids');
		}

		if ($listIds != '')
		{
			$listIds = explode(',', $listIds);

			$this->subscribeToAcyMailingLists($row, $listIds);

			if ($row->is_group_billing && $this->params->get('add_group_members_to_newsletter'))
			{
				$query->clear()
					->select('user_id, first_name, last_name, email')
					->from('#__eb_registrants')
					->where('group_id = ' . (int) $row->id);
				$db->setQuery($query);
				$groupMembers = $db->loadObjectList();

				foreach ($groupMembers as $groupMember)
				{
					$this->subscribeToAcyMailingLists($groupMember, $listIds);
				}
			}
		}
	}

	/**
	 * @param EventbookingTableRegistrant $row
	 * @param array                       $listIds
	 */
	private function subscribeToAcyMailingLists($row, $listIds)
	{
		if (!JMailHelper::isEmailAddress($row->email))
		{
			return;
		}

		require_once JPATH_ADMINISTRATOR . '/components/com_acymailing/helpers/helper.php';

		/* @var subscriberClass $userClass */
		$userClass               = acymailing_get('class.subscriber');
		$userClass->checkAccess  = false;
		$userClass->checkVisitor = false;

		$subId = $userClass->subid($row->email);

		if (!$subId)
		{
			$myUser         = new stdClass();
			$myUser->email  = $row->email;
			$myUser->name   = trim($row->first_name . ' ' . $row->last_name);
			$myUser->userid = $row->user_id;

			$config = EventbookingHelper::getConfig();

			if ($config->multiple_booking)
			{
				$rowFields = EventbookingHelperRegistration::getFormFields($row->id, 4);
			}
            elseif ($row->is_group_billing)
			{
				$rowFields = EventbookingHelperRegistration::getFormFields($row->event_id, 1);
			}
			else
			{
				$rowFields = EventbookingHelperRegistration::getFormFields($row->event_id, 0);
			}

			$data = EventbookingHelperRegistration::getRegistrantData($row, $rowFields);

			foreach ($rowFields as $rowField)
			{
				if (!$rowField->newsletter_field_mapping)
				{
					continue;
				}

				$fieldValue                                    = isset($data[$rowField->name]) ? $data[$rowField->name] : '';
				$myUser->{$rowField->newsletter_field_mapping} = $fieldValue;
			}

			$subId = $userClass->save($myUser);
		}

		$newEvent = [];

		foreach ($listIds as $listId)
		{
			$newEvent[$listId] = ['status' => 1];
		}

		$userClass->saveSubscription($subId, $newEvent);
	}

	/**
	 * Display form allows users to change settings on subscription plan add/edit screen
	 *
	 * @param object $row
	 */
	private function drawSettingForm($row)
	{
		require_once JPATH_ADMINISTRATOR . '/components/com_acymailing/helpers/helper.php';

		if ($row->id)
		{
			$params  = new Registry($row->params);
			$listIds = explode(',', $params->get('acymailing_list_ids', ''));
		}
		else
		{
			$listIds = [];
		}

		/* @var listClass $listClass */
		$listClass = acymailing_get('class.list');
		$allLists  = $listClass->getLists();
		?>
        <table class="admintable adminform" style="width: 90%;">
            <tr>
                <td width="220" class="key">
					<?php echo JText::_('PLG_EB_ACYMAILING_ASSIGN_TO_LIST_USER'); ?>
                </td>
                <td>
					<?php echo JHtml::_('select.genericlist', $allLists, 'acymailing_list_ids[]', 'class="inputbox" multiple="multiple" size="10"', 'listid', 'name', $listIds) ?>
                </td>
                <td>
					<?php echo JText::_('PLG_EB_ACYMAILING_ASSIGN_TO_LIST_USER_EXPLAIN'); ?>
                </td>
            </tr>
        </table>
		<?php
	}
}

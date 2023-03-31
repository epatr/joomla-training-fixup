<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2018 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

class EventbookingViewRegistrationcancelHtml extends RADViewHtml
{
	public $hasModel = false;

	public function display()
	{
		$layout = $this->getLayout();

		if ($layout == 'confirmation')
		{
			$this->displayConfirmationForm();

			return;
		}

		$this->setLayout('default');

		$db          = JFactory::getDbo();
		$query       = $db->getQuery(true);
		$message     = EventbookingHelper::getMessages();
		$fieldSuffix = EventbookingHelper::getFieldSuffix();
		$id          = $this->input->getInt('id', 0);
		$query->select('a.*')
			->select($db->quoteName('b.title' . $fieldSuffix, 'event_title'))
			->from('#__eb_registrants AS a')
			->innerJoin('#__eb_events AS b ON a.event_id = b.id')
			->where('a.id=' . $id);
		$db->setQuery($query);
		$rowRegistrant = $db->loadObject();

		if (!$rowRegistrant)
		{
			$app = JFactory::getApplication();
			$app->enqueueMessage(JText::_('EB_INVALID_REGISTRATION_CODE'), 'error');
			$app->redirect(JUri::root(), 404);
		}

		if ($rowRegistrant->amount > 0)
		{
			if (strlen(trim(strip_tags($message->{'registration_cancel_message_paid' . $fieldSuffix}))))
			{
				$cancelMessage = $message->{'registration_cancel_message_paid' . $fieldSuffix};
			}
			else
			{
				$cancelMessage = $message->registration_cancel_message_paid;
			}
		}
		else
		{
			if (strlen(trim(strip_tags($message->{'registration_cancel_message_free' . $fieldSuffix}))))
			{
				$cancelMessage = $message->{'registration_cancel_message_free' . $fieldSuffix};
			}
			else
			{
				$cancelMessage = $message->registration_cancel_message_free;
			}
		}

		$cancelMessage = str_replace('[EVENT_TITLE]', $rowRegistrant->event_title, $cancelMessage);
		$this->message = $cancelMessage;

		parent::display();
	}

	/**
	 * Display confirm cancel registration form
	 */
	protected function displayConfirmationForm()
	{
		$message     = EventbookingHelper::getMessages();
		$config      = EventbookingHelper::getConfig();
		$fieldSuffix = EventbookingHelper::getFieldSuffix();

		if ($fieldSuffix && EventbookingHelper::isValidMessage($message->{'registration_cancel_confirmation_message' . $fieldSuffix}))
		{
			$this->message = $message->{'registration_cancel_confirmation_message' . $fieldSuffix};
		}
		else
		{
			$this->message = $message->registration_cancel_confirmation_message;
		}

		$this->registrationCode = $this->input->getString('cancel_code');

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('a.*, b.event_date')
			->select($db->quoteName('b.title' . $fieldSuffix, 'event_title'))
			->from('#__eb_registrants AS a')
			->innerJoin('#__eb_events AS b ON a.event_id = b.id')
			->where('a.registration_code = ' . $db->quote($this->registrationCode));
		$db->setQuery($query);
		$row = $db->loadObject();

		if (!$row)
		{
			JFactory::getApplication()->redirect(JUri::root(), JText::_('EB_INVALID_REGISTRATION_CODE'));
		}

		$query->clear()
			->select('*')
			->from('#__eb_events')
			->where('id = ' . (int) $row->event_id);

		if ($fieldSuffix)
		{
			EventbookingHelperDatabase::getMultilingualFields($query, array('title'), $fieldSuffix);
		}

		$db->setQuery($query);
		$rowEvent = $db->loadObject();

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

		$form = new RADForm($rowFields);
		$data = EventbookingHelperRegistration::getRegistrantData($row, $rowFields);
		$form->bind($data);
		$form->buildFieldsDependency();

		if (is_callable('EventbookingHelperOverrideRegistration::buildTags'))
		{
			$replaces = EventbookingHelperOverrideRegistration::buildTags($row, $form, $rowEvent, $config);
		}
		elseif (is_callable('EventbookingHelperOverrideHelper::buildTags'))
		{
			$replaces = EventbookingHelperOverrideHelper::buildTags($row, $form, $rowEvent, $config);
		}
		else
		{
			$replaces = EventbookingHelperRegistration::buildTags($row, $form, $rowEvent, $config);
		}

		foreach ($replaces as $key => $value)
		{
			$this->message = str_ireplace("[$key]", $value, $this->message);
		}

		parent::display();
	}
}

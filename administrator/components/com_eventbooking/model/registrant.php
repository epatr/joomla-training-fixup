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

class EventbookingModelRegistrant extends EventbookingModelCommonRegistrant
{
	/**
	 * Instantiate the model.
	 *
	 * @param array $config configuration data for the model
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->state->insert('filter_event_id', 'int', 0);
	}

	/**
	 * Initial registrant data
	 *
	 * @see RADModelAdmin::initData()
	 */
	public function initData()
	{
		parent::initData();

		$this->data->event_id = $this->state->filter_event_id;
	}


	/**
	 * Send batch emails to selected registrants
	 *
	 * @param RADInput $input
	 *
	 * @throws Exception
	 */
	public function batchMail($input)
	{
		$cid          = $input->get('cid', array(), 'array');
		$emailSubject = $input->getString('subject');
		$emailMessage = $input->get('message', '', 'raw');
		$bccEmail     = $input->getString('bcc_email', '');

		if (empty($cid))
		{
			throw new Exception('Please select registrants to send mass mail');
		}

		if (empty($emailSubject))
		{
			throw new Exception('Please enter subject of the email');
		}

		if (empty($emailMessage))
		{
			throw new Exception('Please enter message ofthe email');
		}

		// OK, data is valid, process sending email
		$mailer = JFactory::getMailer();
		$db     = JFactory::getDbo();
		$query  = $db->getQuery(true);
		$config = EventbookingHelper::getConfig();

		if ($config->from_name)
		{
			$fromName = $config->from_name;
		}
		else
		{
			$fromName = JFactory::getConfig()->get('fromname');
		}

		if ($config->from_email)
		{
			$fromEmail = $config->from_email;
		}
		else
		{
			$fromEmail = JFactory::getConfig()->get('mailfrom');
		}

		$mailer->setSender(array($fromEmail, $fromName));

		$mailer->isHtml(true);

		if (!empty($bccEmail))
		{
			$bccEmails = explode(',', $bccEmail);

			$bccEmails = array_map('trim', $bccEmails);

			foreach ($bccEmails as $bccEmail)
			{
				if (JMailHelper::isEmailAddress($bccEmail))
				{
					$mailer->addBcc($bccEmail);
				}
			}
		}

		// Upload file
		$attachment = $input->files->get('attachment', null, 'raw');

		if ($attachment['name'])
		{
			$allowedExtensions = $config->attachment_file_types;

			if (!$allowedExtensions)
			{
				$allowedExtensions = 'doc|docx|ppt|pptx|pdf|zip|rar|bmp|gif|jpg|jepg|png|swf|zipx';
			}

			$allowedExtensions = explode('|', $allowedExtensions);
			$allowedExtensions = array_map('trim', $allowedExtensions);
			$allowedExtensions = array_map('strtolower', $allowedExtensions);
			$fileName          = $attachment['name'];
			$fileExt           = JFile::getExt($fileName);

			if (in_array(strtolower($fileExt), $allowedExtensions))
			{
				$fileName = JFile::makeSafe($fileName);
				$mailer->addAttachment($attachment['tmp_name'], $fileName);
			}
			else
			{
				throw new Exception(JText::sprintf('Attachment file type %s is not allowed', $fileExt));
			}
		}

		// Get list of registration records
		$query->select('a.*')
			->from('#__eb_registrants AS a')
			->where('a.id IN (' . implode(',', $cid) . ')');
		$db->setQuery($query);
		$rows = $db->loadObjectList();

		// Load frontend language file
		$defaultLanguage = EventbookingHelper::getDefaultLanguage();
		EventbookingHelper::loadComponentLanguage($defaultLanguage, true);
		$loadedLanguages = [$defaultLanguage];
		$loadedEvents    = [];

		foreach ($rows as $row)
		{
			$subject = $emailSubject;
			$message = $emailMessage;
			$email   = $row->email;

			// If this is not valid email address, continue
			if (!JMailHelper::isEmailAddress($email))
			{
				continue;
			}

			// Get registrant language
			if (!$row->language || $row->language == '*')
			{
				$language = $defaultLanguage;
			}
			else
			{
				$language = $row->language;
			}

			if (!in_array($language, $loadedLanguages))
			{
				EventbookingHelper::loadComponentLanguage($language, true);
				$loadedLanguages[] = $language;
			}

			if ($row->user_id > 0)
			{
				$userId = $row->user_id;
			}
			else
			{
				$userId = null;
			}

			if (!isset($loadedEvents[$language . '.' . $row->event_id]))
			{
				$query->clear()
					->select('*')
					->from('#__eb_events')
					->where('id = ' . $row->event_id);

				$fieldSuffix = EventbookingHelper::getFieldSuffix($language);

				if ($fieldSuffix)
				{
					EventbookingHelperDatabase::getMultilingualFields($query, array('title', 'short_description', 'description', 'price_text'), $fieldSuffix);
				}

				$db->setQuery($query);
				$event                                          = $db->loadObject();
				$loadedEvents[$language . '.' . $row->event_id] = $event;
			}
			else
			{
				$event = $loadedEvents[$language . '.' . $row->event_id];
			}

			// Build tags
			if ($config->multiple_booking)
			{
				$rowFields = EventbookingHelperRegistration::getFormFields($row->id, 4, $row->language, $userId);
			}
			elseif ($row->is_group_billing)
			{
				$rowFields = EventbookingHelperRegistration::getFormFields($row->event_id, 1, $row->language, $userId);
			}
			else
			{
				$rowFields = EventbookingHelperRegistration::getFormFields($row->event_id, 0, $row->language, $userId);
			}

			$form           = new RADForm($rowFields);
			$registrantData = EventbookingHelperRegistration::getRegistrantData($row, $rowFields);
			$form->bind($registrantData);
			$form->buildFieldsDependency();

			if (is_callable('EventbookingHelperOverrideRegistration::buildTags'))
			{
				$replaces = EventbookingHelperOverrideRegistration::buildTags($row, $form, $event, $config);
			}
			elseif (is_callable('EventbookingHelperOverrideHelper::buildTags'))
			{
				$replaces = EventbookingHelperOverrideHelper::buildTags($row, $form, $event, $config);
			}
			else
			{
				$replaces = EventbookingHelperRegistration::buildTags($row, $form, $event, $config);
			}

			$replaces['REGISTRATION_DETAIL'] = EventbookingHelperRegistration::getEmailContent($config, $row, true, $form);

			foreach ($replaces as $key => $value)
			{
				$key     = strtoupper($key);
				$subject = str_ireplace("[$key]", $value, $subject);
				$message = str_ireplace("[$key]", $value, $message);
			}

			$message = EventbookingHelperRegistration::processQRCODE($row, $message);
			$message = EventbookingHelper::convertImgTags($message);

			$mailer->addRecipient($email);
			$mailer->setSubject($subject)
				->setBody($message)
				->Send();

			$mailer->clearAddresses();
		}
	}

	/**
	 * @param $file
	 *
	 * @return int
	 * @throws Exception
	 */
	public function import($file)
	{
		$config      = EventbookingHelper::getConfig();
		$registrants = EventbookingHelperData::getDataFromFile($file);

		$imported  = 0;
		$todayDate = JFactory::getDate()->toSql();

		if (count($registrants))
		{
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select('name, title')
				->from('#__eb_payment_plugins');
			$db->setQuery($query);
			$plugins = $db->loadObjectList('title');

			foreach ($registrants as $registrant)
			{
				if (empty($registrant['event_id']))
				{
					continue;
				}

				/* @var EventbookingTableRegistrant $row */
				$row = $this->getTable();

				if (!empty($registrant['id']))
				{
					$row->load($registrant['id']);
				}

				if ($registrant['register_date'])
				{
					try
					{
						$registerDate                = DateTime::createFromFormat($config->date_format, $registrant['register_date']);
						$registrant['register_date'] = $registerDate->format('Y=m-d');
					}
					catch (Exception $e)
					{
						$registrant['register_date'] = $todayDate;
					}
				}
				else
				{
					$registrant ['register_date'] = $todayDate;
				}

				if ($registrant['payment_method'] && isset($plugins[$registrant['payment_method']]))
				{
					$registrant['payment_method'] = $plugins[$registrant['payment_method']]->name;
				}

				$row->bind($registrant);

				if ($row->number_registrants > 1)
				{
					$row->is_group_billing = 1;
				}

				$row->store();

				$registrantId = $row->id;

				$fields = self::getEventFields($row->event_id, $config);

				if (count($fields))
				{
					$query->clear()
						->delete('#__eb_field_values')
						->where('registrant_id = ' . $registrantId);
					$db->setQuery($query);
					$db->execute();

					foreach ($fields as $fieldName => $field)
					{
						$fieldValue = isset($registrant[$fieldName]) ? $registrant[$fieldName] : '';
						$fieldId    = $field->id;

						if ($field->fieldtype == 'Checkboxes' || $field->multiple)
						{
							$fieldValue = json_encode(explode(', ', $fieldValue));
						}

						$query->clear()
							->insert('#__eb_field_values')
							->columns('registrant_id, field_id, field_value')
							->values("$registrantId, $fieldId, " . $db->quote($fieldValue));
						$db->setQuery($query);
						$db->execute();
					}
				}

				$imported++;
			}
		}

		return $imported;
	}

	/**
	 * Get all custom fields of the given event
	 *
	 * @param int $eventId
	 *
	 * @pram RADConfig $config
	 *
	 * @return array
	 */
	public static function getEventFields($eventId, $config)
	{
		static $fields;

		if (!isset($fields[$eventId]))
		{
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select('id, name, fieldtype')
				->from('#__eb_fields')
				->where('is_core = 0')
				->where('published = 1');

			if ($config->custom_field_by_category)
			{
				//Get main category of the event
				$subQuery = $db->getQuery(true);
				$subQuery->select('category_id')
					->from('#__eb_event_categories')
					->where('event_id = ' . $eventId)
					->where('main_category = 1');
				$db->setQuery($subQuery);
				$categoryId = (int) $db->loadResult();
				$query->where('(category_id = -1 OR id IN (SELECT field_id FROM #__eb_field_categories WHERE category_id=' . $categoryId . '))');
			}
			else
			{
				$query->where(' (event_id = -1 OR id IN (SELECT field_id FROM #__eb_field_events WHERE event_id=' . $eventId . '))');
			}

			$db->setQuery($query);
			$fields[$eventId] = $db->loadObjectList('name');
		}

		return $fields[$eventId];
	}
}

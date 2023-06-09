<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2018 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

class EventbookingModelMassmail extends RADModel
{
	/**
	 * Send email to all registrants of event
	 *
	 * @param RADInput $input
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function send($input)
	{
		$data = $input->getData();

		if ($data['event_id'] >= 1)
		{
			$mailer = JFactory::getMailer();
			$config = EventbookingHelper::getConfig();
			$db     = JFactory::getDbo();
			$query  = $db->getQuery(true);

			$published                      = isset($data['published']) ? $data['published'] : -1;
			$sendToGroupBilling             = isset($data['send_to_group_billing']) ? $data['send_to_group_billing'] : 1;
			$sendToGroupMembers             = isset($data['send_to_group_members']) ? $data['send_to_group_members'] : 1;
			$onlySendToCheckedinRegistrants = isset($data['only_send_to_checked_in_registrants']) ? $data['only_send_to_checked_in_registrants'] : 0;

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

			if (!empty($data['bcc_email']))
			{
				$bccEmails = explode(',', $data['bcc_email']);

				$bccEmails = array_map('trim', $bccEmails);

				foreach ($bccEmails as $bccEmail)
				{
					if (JMailHelper::isEmailAddress($bccEmail))
					{
						$mailer->addBcc($bccEmail);
					}
				}
			}

			// Load frontend language file
			$defaultLanguage = EventbookingHelper::getDefaultLanguage();
			EventbookingHelper::loadComponentLanguage($defaultLanguage, true);
			$loadedLanguages = [$defaultLanguage];
			$loadedEvents    = [];

			$event                         = EventbookingHelperDatabase::getEvent((int) $data['event_id']);
			$replaces                      = array();
			$replaces['event_title']       = $event->title;
			$replaces['event_date']        = JHtml::_('date', $event->event_date, $config->event_date_format, null);
			$replaces['short_description'] = $event->short_description;
			$replaces['description']       = $event->description;

			if ($event->location_id)
			{
				$location = EventbookingHelperDatabase::getLocation($event->location_id);

				if ($location->address)
				{
					$replaces['event_location'] = $location->name . ' (' . $location->address . ')';
				}
				else
				{
					$replaces['event_location'] = $location->name;
				}
			}
			else
			{
				$replaces['event_location'] = '';
			}

			$query->clear()
				->select('*')
				->from('#__eb_registrants')
				->where('event_id = ' . (int) $data['event_id']);

			if ($published == -1)
			{
				$query->where('(published=1 OR (payment_method LIKE "os_offline%" AND published NOT IN (2,3)))');
			}
			else
			{
				$query->where('published = ' . $published);
			}

			if (!$sendToGroupBilling)
			{
				$query->where('is_group_billing = 0');
			}

			if (!$sendToGroupMembers)
			{
				$query->where('group_id = 0');
			}

			if ($onlySendToCheckedinRegistrants)
			{
				$query->where('checked_in = 1');
			}

			$db->setQuery($query);
			$rows = $db->loadObjectList();

			// Attach ICS file
			if ($config->send_ics_file)
			{
				$ics = new EventbookingHelperIcs();
				$ics->setName($event->title)
					->setDescription($event->short_description)
					->setOrganizer($fromEmail, $fromName)
					->setStart($event->event_date)
					->setEnd($event->event_end_date);

				if (!empty($location))
				{
					$ics->setLocation($location->name);
				}

				$fileName = JApplicationHelper::stringURLSafe($event->title) . '.ics';
				$mailer->addAttachment($ics->save(JPATH_ROOT . '/media/com_eventbooking/icsfiles/', $fileName));
			}


			foreach ($rows as $row)
			{
				$email = $row->email;

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

				$subject = $data['subject'];
				$message = $data['description'];

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

		return true;
	}
}

<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2018 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */


defined('_JEXEC') or die;

use Joomla\Registry\Registry;

class EventbookingHelperMail
{
	/**
	 * From Name
	 *
	 * @var string
	 */
	public static $fromName;

	/**
	 * From Email
	 *
	 * @var string
	 */
	public static $fromEmail;

	/**
	 * Helper function for sending emails to registrants and administrator
	 *
	 * @param EventbookingTableRegistrant $row
	 * @param object                      $config
	 */
	public static function sendEmails($row, $config)
	{
		if (EventbookingHelper::isMethodOverridden('EventbookingHelperOverrideMail', 'sendEmails'))
		{
			EventbookingHelperOverrideMail::sendEmails($row, $config);

			return;
		}

		$message     = EventbookingHelper::getMessages();
		$fieldSuffix = EventbookingHelper::getFieldSuffix($row->language);

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*')
			->from('#__eb_events')
			->where('id = ' . $row->event_id);

		if ($fieldSuffix)
		{
			EventbookingHelperDatabase::getMultilingualFields($query, array('title', 'short_description', 'description', 'price_text'), $fieldSuffix);
		}

		$db->setQuery($query);
		$event = $db->loadObject();

		if ($event->send_emails != -1)
		{
			$config->send_emails = $event->send_emails;
		}

		if ($config->send_emails == 3)
		{
			return;
		}

		if (!empty($config->log_email_types) && in_array('new_registration_emails', explode(',', $config->log_email_types)))
		{
			$logEmails = true;
		}
		else
		{
			$logEmails = false;
		}

		if ($row->user_id > 0)
		{
			$userId = $row->user_id;
		}
		else
		{
			$userId = null;
		}

		// Load frontend component language if needed
		EventbookingHelper::loadComponentLanguage($row->language);

		if (strlen(trim($event->notification_emails)) > 0)
		{
			$config->notification_emails = $event->notification_emails;
		}

		$mailer = static::getMailer($config);

		if ($event->created_by && $config->send_email_to_event_creator)
		{
			$eventCreator = JUser::getInstance($event->created_by);

			if (JMailHelper::isEmailAddress($eventCreator->email) && !$eventCreator->authorise('core.admin'))
			{
				$mailer->addReplyTo($eventCreator->email);
			}
		}

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

		$form = new RADForm($rowFields);
		$data = EventbookingHelperRegistration::getRegistrantData($row, $rowFields);
		$form->bind($data);
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

		$query->clear()
			->select('a.*')
			->from('#__eb_locations AS a')
			->innerJoin('#__eb_events AS b ON a.id = b.location_id')
			->where('b.id = ' . $row->event_id);

		if ($fieldSuffix)
		{
			EventbookingHelperDatabase::getMultilingualFields($query, ['a.name', 'a.alias', 'a.description'], $fieldSuffix);
		}

		$db->setQuery($query);
		$rowLocation = $db->loadObject();

		// Get group members data
		if ($event->collect_member_information === '')
		{
			$collectMemberInformation = $config->collect_member_information;
		}
		else
		{
			$collectMemberInformation = $event->collect_member_information;
		}

		if ($row->is_group_billing && $collectMemberInformation)
		{
			$query->clear()
				->select('*')
				->from('#__eb_registrants')
				->where('group_id = ' . $row->id);
			$db->setQuery($query);
			$rowMembers = $db->loadObjectList();
		}
		else
		{
			$rowMembers = [];
		}

		// Notification email send to user
		if ($config->send_emails == 0 || $config->send_emails == 2)
		{
			if ($fieldSuffix && strlen($message->{'user_email_subject' . $fieldSuffix}))
			{
				$subject = $message->{'user_email_subject' . $fieldSuffix};
			}
			else
			{
				$subject = $message->user_email_subject;
			}

			if (!$row->published && strpos($row->payment_method, 'os_offline') !== false)
			{
				$offlineSuffix = str_replace('os_offline', '', $row->payment_method);

				if ($offlineSuffix && $fieldSuffix && EventbookingHelper::isValidMessage($message->{'user_email_body_offline' . $offlineSuffix . $fieldSuffix}))
				{
					$body = $message->{'user_email_body_offline' . $offlineSuffix . $fieldSuffix};
				}
				elseif ($offlineSuffix && EventbookingHelper::isValidMessage($message->{'user_email_body_offline' . $offlineSuffix}))
				{
					$body = $message->{'user_email_body_offline' . $offlineSuffix};
				}
				elseif ($fieldSuffix && EventbookingHelper::isValidMessage($event->{'user_email_body_offline' . $fieldSuffix}))
				{
					$body = $event->{'user_email_body_offline' . $fieldSuffix};
				}
				elseif ($fieldSuffix && EventbookingHelper::isValidMessage($message->{'user_email_body_offline' . $fieldSuffix}))
				{
					$body = $message->{'user_email_body_offline' . $fieldSuffix};
				}
				elseif (EventbookingHelper::isValidMessage($event->user_email_body_offline))
				{
					$body = $event->user_email_body_offline;
				}
				else
				{
					$body = $message->user_email_body_offline;
				}
			}
			else
			{
				if ($fieldSuffix && EventbookingHelper::isValidMessage($event->{'user_email_body' . $fieldSuffix}))
				{
					$body = $event->{'user_email_body' . $fieldSuffix};
				}
				elseif ($fieldSuffix && EventbookingHelper::isValidMessage($message->{'user_email_body' . $fieldSuffix}))
				{
					$body = $message->{'user_email_body' . $fieldSuffix};
				}
				elseif (EventbookingHelper::isValidMessage($event->user_email_body))
				{
					$body = $event->user_email_body;
				}
				else
				{
					$body = $message->user_email_body;
				}
			}

			foreach ($replaces as $key => $value)
			{
				$key     = strtoupper($key);
				$subject = str_ireplace("[$key]", $value, $subject);
				$body    = str_ireplace("[$key]", $value, $body);
			}

			$body = EventbookingHelper::convertImgTags($body);
			$body = EventbookingHelperRegistration::processQRCODE($row, $body);

			$invoiceFilePath = '';

			if ($config->activate_invoice_feature && $config->send_invoice_to_customer && $row->invoice_number)
			{
				if (is_callable('EventbookingHelperOverrideHelper::generateInvoicePDF'))
				{
					EventbookingHelperOverrideHelper::generateInvoicePDF($row);
				}
				else
				{
					EventbookingHelper::generateInvoicePDF($row);
				}

				$invoiceFilePath = JPATH_ROOT . '/media/com_eventbooking/invoices/' . EventbookingHelper::formatInvoiceNumber($row->invoice_number, $config, $row) . '.pdf';
				$mailer->addAttachment($invoiceFilePath);
			}

			if ($config->get('activate_tickets_pdf') && $config->get('send_tickets_via_email', 1))
			{
				if ($config->get('multiple_booking'))
				{
					$query->clear()
						->select('*')
						->from('#__eb_registrants')
						->where('id = ' . $row->id . ' OR cart_id = ' . $row->id);
					$db->setQuery($query);
					$rowRegistrants = $db->loadObjectList();

					foreach ($rowRegistrants as $rowRegistrant)
					{
						if ($rowRegistrant->ticket_code)
						{
							EventbookingHelperTicket::generateTicketsPDF($rowRegistrant, $config);
							$ticketFilePath = JPATH_ROOT . '/media/com_eventbooking/tickets/ticket_' . str_pad($rowRegistrant->id, 5, '0', STR_PAD_LEFT) . '.pdf';
							$mailer->addAttachment($ticketFilePath);
						}
					}
				}
				else
				{
					if ($row->ticket_code && $row->payment_status == 1)
					{
						if (count($rowMembers))
						{
							foreach ($rowMembers as $rowMember)
							{
								EventbookingHelperTicket::generateTicketsPDF($rowMember, $config);
								$ticketFilePath = JPATH_ROOT . '/media/com_eventbooking/tickets/ticket_' . str_pad($rowMember->id, 5, '0', STR_PAD_LEFT) . '.pdf';
								$mailer->addAttachment($ticketFilePath);
							}
						}
						else
						{
							EventbookingHelperTicket::generateTicketsPDF($row, $config);
							$ticketFilePath = JPATH_ROOT . '/media/com_eventbooking/tickets/ticket_' . str_pad($row->id, 5, '0', STR_PAD_LEFT) . '.pdf';
							$mailer->addAttachment($ticketFilePath);
						}
					}
				}
			}

			$attachments = static::addEventAttachments($mailer, $row, $event, $config);

			//Generate and send ics file to registrants
			if ($config->send_ics_file)
			{
				if ($config->multiple_booking)
				{
					$query->clear()
						->select('a.title, a.event_date, a.event_end_date, a.short_description, b.name')
						->from('#__eb_events AS a')
						->leftJoin('#__eb_locations AS b ON a.location_id =  b.id')
						->innerJoin('#__eb_registrants AS c ON a.id = c.event_id')
						->where("(c.id = $row->id OR c.cart_id = $row->id)")
						->order('c.id');

					if ($fieldSuffix)
					{
						EventbookingHelperDatabase::getMultilingualFields($query, ['a.title', 'a.short_description', 'b.name'], $fieldSuffix);
					}

					$db->setQuery($query);
					$rowEvents = $db->loadObjectList();

					foreach ($rowEvents as $rowEvent)
					{
						$ics = new EventbookingHelperIcs();
						$ics->setName($rowEvent->title)
							->setDescription($rowEvent->short_description)
							->setOrganizer(static::$fromEmail, static::$fromName)
							->setStart($rowEvent->event_date)
							->setEnd($rowEvent->event_end_date);

						if ($rowEvent->name)
						{
							$ics->setLocation($rowEvent->name);
						}

						$fileName = JApplicationHelper::stringURLSafe($rowEvent->title) . '.ics';

						$mailer->addAttachment($ics->save(JPATH_ROOT . '/media/com_eventbooking/icsfiles/', $fileName));
					}
				}
				else
				{
					$ics = new EventbookingHelperIcs();
					$ics->setName($event->title)
						->setDescription($event->short_description)
						->setOrganizer(static::$fromEmail, static::$fromName)
						->setStart($event->event_date)
						->setEnd($event->event_end_date);

					if ($rowLocation)
					{
						$ics->setLocation($rowLocation->name);
					}

					$fileName = JApplicationHelper::stringURLSafe($event->title) . '.ics';
					$mailer->addAttachment($ics->save(JPATH_ROOT . '/media/com_eventbooking/icsfiles/', $fileName));

					$attachments[] = JPATH_ROOT . '/media/com_eventbooking/icsfiles/' . $fileName;
				}
			}

			if (JMailHelper::isEmailAddress($row->email))
			{
				$sendTos = array($row->email);

				foreach ($rowFields as $rowField)
				{
					if ($rowField->receive_confirmation_email && !empty($replaces[$rowField->name]) && JMailHelper::isEmailAddress($replaces[$rowField->name]))
					{
						$sendTos[] = $replaces[$rowField->name];
					}
				}

				static::send($mailer, $sendTos, $subject, $body, $logEmails, 2, 'new_registration_emails');
				$mailer->clearAllRecipients();
			}

			if ($config->send_email_to_group_members && $row->is_group_billing)
			{
				if (count($rowMembers))
				{
					$memberReplaces = array();

					$memberReplaces['registration_detail']      = $replaces['registration_detail'];
					$memberReplaces['group_billing_first_name'] = $row->first_name;
					$memberReplaces['group_billing_last_name']  = $row->last_name;
					$memberReplaces['group_billing_email']      = $row->email;

					$memberReplaces['event_title']       = $replaces['event_title'];
					$memberReplaces['event_date']        = $replaces['event_date'];
					$memberReplaces['event_end_date']    = $replaces['event_end_date'];
					$memberReplaces['transaction_id']    = $replaces['transaction_id'];
					$memberReplaces['date']              = $replaces['date'];
					$memberReplaces['short_description'] = $replaces['short_description'];
					$memberReplaces['description']       = $replaces['short_description'];
					$memberReplaces['location']          = $replaces['location'];
					$memberReplaces['event_link']        = $replaces['event_link'];

					$memberReplaces['download_certificate_link'] = $replaces['download_certificate_link'];

					$memberFormFields = EventbookingHelperRegistration::getFormFields($row->event_id, 2, $row->language, $userId);

					foreach ($rowMembers as $rowMember)
					{
						if (!JMailHelper::isEmailAddress($rowMember->email))
						{
							continue;
						}

						// Clear attachments sent to billing records
						$mailer->clearAttachments();

						if (strlen($message->{'group_member_email_subject' . $fieldSuffix}))
						{
							$subject = $message->{'group_member_email_subject' . $fieldSuffix};
						}
						else
						{
							$subject = $message->group_member_email_subject;
						}

						if (EventbookingHelper::isValidMessage($message->{'group_member_email_body' . $fieldSuffix}))
						{
							$body = $message->{'group_member_email_body' . $fieldSuffix};
						}
						else
						{
							$body = $message->group_member_email_body;
						}

						if (!$subject)
						{
							break;
						}

						if (!$body)
						{
							break;
						}

						//Build the member form
						$memberForm = new RADForm($memberFormFields);
						$memberData = EventbookingHelperRegistration::getRegistrantData($rowMember, $memberFormFields);
						$memberForm->bind($memberData);
						$memberForm->buildFieldsDependency();
						$fields = $memberForm->getFields();

						foreach ($fields as $field)
						{
							if ($field->hideOnDisplay)
							{
								$fieldValue = '';
							}
							else
							{
								if (is_string($field->value) && is_array(json_decode($field->value)))
								{
									$fieldValue = implode(', ', json_decode($field->value));
								}
								else
								{
									$fieldValue = $field->value;
								}
							}

							$memberReplaces[$field->name] = $fieldValue;
						}

						$memberReplaces['member_detail'] = EventbookingHelperRegistration::getMemberDetails($config, $rowMember, $event, $rowLocation, true, $memberForm);

						foreach ($memberReplaces as $key => $value)
						{
							$key     = strtoupper($key);
							$body    = str_ireplace("[$key]", $value, $body);
							$subject = str_ireplace("[$key]", $value, $subject);
						}

						$body = EventbookingHelper::convertImgTags($body);

						foreach ($attachments as $attachment)
						{
							$mailer->addAttachment($attachment);
						}

						// Create PDF ticket
						if ($row->ticket_code && $row->payment_status == 1)
						{
							$ticketFilePath = JPATH_ROOT . '/media/com_eventbooking/tickets/ticket_' . str_pad($rowMember->id, 5, '0', STR_PAD_LEFT) . '.pdf';

							if (!file_exists($ticketFilePath))
							{
								EventbookingHelperTicket::generateTicketsPDF($rowMember, $config);
							}

							$mailer->addAttachment($ticketFilePath);
						}

						static::send($mailer, array($rowMember->email), $subject, $body, $logEmails, 2, 'new_registration_emails');
						$mailer->clearAllRecipients();
					}
				}
			}

			// Clear attachments
			$mailer->clearAttachments();
			$mailer->clearReplyTos();
		}

		// Send notification emails to admin if needed
		if ($config->send_emails == 0 || $config->send_emails == 1)
		{
			// Send invoice PDF to admin email
			if ($config->activate_invoice_feature && $config->send_invoice_to_admin && $row->invoice_number)
			{
				// Generate invoice if it was not generated before - in case registrant not receiving invoice
				if (empty($invoiceFilePath))
				{
					if (is_callable('EventbookingHelperOverrideHelper::generateInvoicePDF'))
					{
						EventbookingHelperOverrideHelper::generateInvoicePDF($row);
					}
					else
					{
						EventbookingHelper::generateInvoicePDF($row);
					}

					$invoiceFilePath = JPATH_ROOT . '/media/com_eventbooking/invoices/' . EventbookingHelper::formatInvoiceNumber($row->invoice_number, $config, $row) . '.pdf';
				}

				$mailer->addAttachment($invoiceFilePath);
			}

			// Send attachment to admin email if needed
			if ($config->send_attachments_to_admin)
			{
				static::addRegistrationFormAttachments($mailer, $rowFields, $replaces);
			}

			$emails = $emails = explode(',', $config->notification_emails);

			if ($fieldSuffix && strlen($message->{'admin_email_subject' . $fieldSuffix}))
			{
				$subject = $message->{'admin_email_subject' . $fieldSuffix};
			}
			else
			{
				$subject = $message->admin_email_subject;
			}

			if ($fieldSuffix && EventbookingHelper::isValidMessage($message->{'admin_email_body' . $fieldSuffix}))
			{
				$body = $message->{'admin_email_body' . $fieldSuffix};
			}
			else
			{
				$body = $message->admin_email_body;
			}

			if ($row->payment_method == 'os_offline_creditcard')
			{
				$replaces['registration_detail'] = EventbookingHelperRegistration::getEmailContent($config, $row, true, $form, true);
			}

			foreach ($replaces as $key => $value)
			{
				$key     = strtoupper($key);
				$subject = str_ireplace("[$key]", $value, $subject);
				$body    = str_ireplace("[$key]", $value, $body);
			}

			$body = EventbookingHelper::convertImgTags($body);
			$body = EventbookingHelperRegistration::processQRCODE($row, $body);

			if (strpos($body, '[QRCODE]') !== false)
			{
				EventbookingHelper::generateQrcode($row->id);
				$imgTag = '<img src="' . EventbookingHelper::getSiteUrl() . 'media/com_eventbooking/qrcodes/' . $row->id . '.png" border="0" />';
				$body   = str_ireplace("[QRCODE]", $imgTag, $body);
			}

			if ($config->send_email_to_event_creator
				&& !empty($eventCreator->email)
				&& !$eventCreator->authorise('core.admin')
				&& JMailHelper::isEmailAddress($eventCreator->email)
				&& !in_array($eventCreator->email, $emails)
			)
			{
				$emails[] = $eventCreator->email;
			}

			if (JMailHelper::isEmailAddress($row->email))
			{
				$mailer->addReplyTo($row->email);
			}

			static::send($mailer, $emails, $subject, $body, $logEmails, 1, 'new_registration_emails');
		}
	}

	/**
	 * Send email to registrant when admin approves his registration
	 *
	 * @param EventbookingTableRegistrant $row
	 * @param RADConfig                   $config
	 */
	public static function sendRegistrationApprovedEmail($row, $config)
	{
		if (!JMailHelper::isEmailAddress($row->email))
		{
			return;
		}

		if (EventbookingHelper::isMethodOverridden('EventbookingHelperOverrideMail', 'sendRegistrationApprovedEmail'))
		{
			EventbookingHelperOverrideMail::sendRegistrationApprovedEmail($row, $config);

			return;
		}

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		EventbookingHelper::loadComponentLanguage($row->language, true);

		$message     = EventbookingHelper::getMessages();
		$fieldSuffix = EventbookingHelper::getFieldSuffix($row->language);
		$mailer      = static::getMailer($config);

		if ($config->multiple_booking)
		{
			$rowFields = EventbookingHelperRegistration::getFormFields($row->id, 4, $row->language);
		}
		elseif ($row->is_group_billing)
		{
			$rowFields = EventbookingHelperRegistration::getFormFields($row->event_id, 1, $row->language);
		}
		else
		{
			$rowFields = EventbookingHelperRegistration::getFormFields($row->event_id, 0, $row->language);
		}

		$form = new RADForm($rowFields);
		$data = EventbookingHelperRegistration::getRegistrantData($row, $rowFields);
		$form->bind($data);
		$form->buildFieldsDependency();

		$query->select('*')
			->from('#__eb_events')
			->where('id = ' . $row->event_id);

		if ($fieldSuffix)
		{
			EventbookingHelperDatabase::getMultilingualFields($query, array('title', 'short_description', 'description'), $fieldSuffix);
		}

		$db->setQuery($query);
		$event = $db->loadObject();

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

		if (strlen(trim($event->registration_approved_email_subject)))
		{
			$subject = $event->registration_approved_email_subject;
		}
		elseif (strlen($message->{'registration_approved_email_subject' . $fieldSuffix}))
		{
			$subject = $message->{'registration_approved_email_subject' . $fieldSuffix};
		}
		else
		{
			$subject = $message->registration_approved_email_subject;
		}

		if ($fieldSuffix && EventbookingHelper::isValidMessage($event->{'registration_approved_email_body' . $fieldSuffix}))
		{
			$body = $event->{'registration_approved_email_body' . $fieldSuffix};
		}
		elseif ($fieldSuffix && EventbookingHelper::isValidMessage($message->{'registration_approved_email_body' . $fieldSuffix}))
		{
			$body = $message->{'registration_approved_email_body' . $fieldSuffix};
		}
		elseif (EventbookingHelper::isValidMessage($event->registration_approved_email_body))
		{
			$body = $event->registration_approved_email_body;
		}
		else
		{
			$body = $message->registration_approved_email_body;
		}

		foreach ($replaces as $key => $value)
		{
			$key     = strtoupper($key);
			$subject = str_ireplace("[$key]", $value, $subject);
			$body    = str_ireplace("[$key]", $value, $body);
		}

		$body = EventbookingHelper::convertImgTags($body);
		$body = EventbookingHelperRegistration::processQRCODE($row, $body);

		if (strpos($body, '[QRCODE]') !== false)
		{
			EventbookingHelper::generateQrcode($row->id);
			$imgTag = '<img src="' . EventbookingHelper::getSiteUrl() . 'media/com_eventbooking/qrcodes/' . $row->id . '.png" border="0" />';
			$body   = str_ireplace("[QRCODE]", $imgTag, $body);
		}

		if ($config->activate_invoice_feature && $row->invoice_number && !$row->group_id)
		{
			if (is_callable('EventbookingHelperOverrideHelper::generateInvoicePDF'))
			{
				EventbookingHelperOverrideHelper::generateInvoicePDF($row);
			}
			else
			{
				EventbookingHelper::generateInvoicePDF($row);
			}

			$mailer->addAttachment(JPATH_ROOT . '/media/com_eventbooking/invoices/' . EventbookingHelper::formatInvoiceNumber($row->invoice_number, $config, $row) . '.pdf');
		}

		if ($row->ticket_code && $config->get('send_tickets_via_email', 1))
		{
			EventbookingHelperTicket::generateTicketsPDF($row, $config);
			$ticketFilePath = JPATH_ROOT . '/media/com_eventbooking/tickets/ticket_' . str_pad($row->id, 5, '0', STR_PAD_LEFT) . '.pdf';
			$mailer->addAttachment($ticketFilePath);
		}

		static::send($mailer, array($row->email), $subject, $body);
	}

	/**
	 * Send email to registrant when admin change the status to cancelled
	 *
	 * @param EventbookingTableRegistrant $row
	 * @param object                      $config
	 */
	public static function sendRegistrationCancelledEmail($row, $config)
	{
		if (!JMailHelper::isEmailAddress($row->email))
		{
			return;
		}

		if (EventbookingHelper::isMethodOverridden('EventbookingHelperOverrideMail', 'sendRegistrationCancelledEmail'))
		{
			EventbookingHelperOverrideMail::sendRegistrationCancelledEmail($row, $config);

			return;
		}

		$app   = JFactory::getApplication();
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		if ($app->isSite())
		{
			if ($row->language && $row->language != '*')
			{
				$tag = $row->language;
			}
			else
			{
				$tag = EventbookingHelper::getDefaultLanguage();
			}

			JFactory::getLanguage()->load('com_eventbooking', JPATH_ROOT, $tag);
		}

		$message     = EventbookingHelper::getMessages();
		$fieldSuffix = EventbookingHelper::getFieldSuffix($row->language);

		if ($fieldSuffix && strlen($message->{'user_registration_cancel_subject' . $fieldSuffix}))
		{
			$subject = $message->{'user_registration_cancel_subject' . $fieldSuffix};
		}
		else
		{
			$subject = $message->user_registration_cancel_subject;
		}

		if (empty($subject))
		{
			return;
		}

		if ($fieldSuffix && EventbookingHelper::isValidMessage($message->{'user_registration_cancel_message' . $fieldSuffix}))
		{
			$body = $message->{'user_registration_cancel_message' . $fieldSuffix};
		}
		else
		{
			$body = $message->user_registration_cancel_message;
		}

		if (empty($body))
		{
			return;
		}

		if (!JMailHelper::isEmailAddress($row->email))
		{
			return;
		}

		$mailer = static::getMailer($config);

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

		$query->select('*')
			->from('#__eb_events')
			->where('id=' . $row->event_id);
		$db->setQuery($query);

		if ($fieldSuffix)
		{
			EventbookingHelperDatabase::getMultilingualFields($query, array('title'), $fieldSuffix);
		}

		$event = $db->loadObject();

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

		foreach ($replaces as $key => $value)
		{
			$key     = strtoupper($key);
			$subject = str_ireplace("[$key]", $value, $subject);
			$body    = str_ireplace("[$key]", $value, $body);
		}

		$body = EventbookingHelper::convertImgTags($body);

		static::send($mailer, array($row->email), $subject, $body);
	}

	/**
	 * Send email when users fill-in waitinglist
	 *
	 * @param  object $row
	 * @param object  $config
	 */
	public static function sendWaitinglistEmail($row, $config)
	{
		if (EventbookingHelper::isMethodOverridden('EventbookingHelperOverrideMail', 'sendWaitinglistEmail'))
		{
			EventbookingHelperOverrideMail::sendWaitinglistEmail($row, $config);

			return;
		}

		$message     = EventbookingHelper::getMessages();
		$fieldSuffix = EventbookingHelper::getFieldSuffix($row->language);
		$db          = JFactory::getDbo();
		$query       = $db->getQuery(true);
		$query->select('*')
			->from('#__eb_events')
			->where('id=' . $row->event_id);
		$db->setQuery($query);
		if ($fieldSuffix)
		{
			EventbookingHelperDatabase::getMultilingualFields($query, array('title'), $fieldSuffix);
		}
		$event = $db->loadObject();

		if (strlen(trim($event->notification_emails)) > 0)
		{
			$config->notification_emails = $event->notification_emails;
		}

		$mailer = static::getMailer($config);

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

		//Notification email send to user
		if ($fieldSuffix && strlen($message->{'watinglist_confirmation_subject' . $fieldSuffix}))
		{
			$subject = $message->{'watinglist_confirmation_subject' . $fieldSuffix};
		}
		else
		{
			$subject = $message->watinglist_confirmation_subject;
		}

		if ($fieldSuffix && EventbookingHelper::isValidMessage($message->{'watinglist_confirmation_body' . $fieldSuffix}))
		{
			$body = $message->{'watinglist_confirmation_body' . $fieldSuffix};
		}
		else
		{
			$body = $message->watinglist_confirmation_body;
		}

		foreach ($replaces as $key => $value)
		{
			$key     = strtoupper($key);
			$subject = str_ireplace("[$key]", $value, $subject);
			$body    = str_ireplace("[$key]", $value, $body);
		}

		if (JMailHelper::isEmailAddress($row->email))
		{
			static::send($mailer, array($row->email), $subject, $body);
			$mailer->clearAllRecipients();
		}

		$emails = explode(',', $config->notification_emails);

		if (strlen($message->{'watinglist_notification_subject' . $fieldSuffix}))
		{
			$subject = $message->{'watinglist_notification_subject' . $fieldSuffix};
		}
		else
		{
			$subject = $message->watinglist_notification_subject;
		}

		if (EventbookingHelper::isValidMessage($message->{'watinglist_notification_body' . $fieldSuffix}))
		{
			$body = $message->{'watinglist_notification_body' . $fieldSuffix};
		}
		else
		{
			$body = $message->watinglist_notification_body;
		}

		$subject = str_ireplace('[EVENT_TITLE]', $event->title, $subject);

		foreach ($replaces as $key => $value)
		{
			$key     = strtoupper($key);
			$subject = str_ireplace("[$key]", $value, $subject);
			$body    = str_ireplace("[$key]", $value, $body);
		}

		$body = EventbookingHelper::convertImgTags($body);

		static::send($mailer, $emails, $subject, $body);
	}

	/**
	 * Send notification emails to waiting list users when a registration is cancelled
	 *
	 * @param EventbookingTableRegistrant $row
	 * @param RADConfig                   $config
	 */
	public static function sendWaitingListNotificationEmail($row, $config)
	{
		if (EventbookingHelper::isMethodOverridden('EventbookingHelperOverrideMail', 'sendWaitingListNotificationEmail'))
		{
			EventbookingHelperOverrideMail::sendWaitingListNotificationEmail($row, $config);

			return;
		}

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*')
			->from('#__eb_registrants')
			->where('event_id=' . (int) $row->event_id)
			->where('group_id = 0')
			->where('published = 3')
			->order('id');
		$db->setQuery($query);
		$registrants = $db->loadObjectList();
		$siteUrl     = JUri::root();

		if (count($registrants))
		{
			$mailer = static::getMailer($config);

			$message     = EventbookingHelper::getMessages();
			$fieldSuffix = EventbookingHelper::getFieldSuffix();

			$query->clear()
				->select('*')
				->from('#__eb_events')
				->where('id=' . $row->event_id);
			$db->setQuery($query);

			if ($fieldSuffix)
			{
				EventbookingHelperDatabase::getMultilingualFields($query, array('title'), $fieldSuffix);
			}

			$db->setQuery($query);
			$rowEvent = $db->loadObject();

			$replaces                          = array();
			$replaces['registrant_first_name'] = $row->first_name;
			$replaces['registrant_last_name']  = $row->last_name;

			if (JFactory::getApplication()->isSite())
			{
				$replaces['event_link'] = JUri::getInstance()->toString(array('scheme', 'user', 'pass', 'host')) . JRoute::_(EventbookingHelperRoute::getEventRoute($row->event_id, 0, EventbookingHelper::getItemid()));
			}
			else
			{
				$replaces['event_link'] = $siteUrl . EventbookingHelperRoute::getEventRoute($row->event_id, 0, EventbookingHelper::getItemid());
			}

			$replaces['event_title']    = $rowEvent->title;
			$replaces['event_date']     = JHtml::_('date', $rowEvent->event_date, $config->event_date_format, null);
			$replaces['event_end_date'] = JHtml::_('date', $rowEvent->event_end_date, $config->event_date_format, null);

			if (strlen(trim($message->{'registrant_waitinglist_notification_subject' . $fieldSuffix})))
			{
				$subject = $message->{'registrant_waitinglist_notification_subject' . $fieldSuffix};
			}
			else
			{
				$subject = $message->registrant_waitinglist_notification_subject;
			}

			if (empty($subject))
			{
				//Admin has not entered email subject and email message for notification yet, simply return
				return false;
			}

			if (EventbookingHelper::isValidMessage($message->{'registrant_waitinglist_notification_body' . $fieldSuffix}))
			{
				$body = $message->{'registrant_waitinglist_notification_body' . $fieldSuffix};
			}
			else
			{
				$body = $message->registrant_waitinglist_notification_body;
			}

			foreach ($registrants as $registrant)
			{
				if (!JMailHelper::isEmailAddress($registrant->email))
				{
					continue;
				}

				$message                = $body;
				$replaces['first_name'] = $registrant->first_name;
				$replaces['last_name']  = $registrant->last_name;

				foreach ($replaces as $key => $value)
				{
					$key     = strtoupper($key);
					$subject = str_replace("[$key]", $value, $subject);
					$message = str_replace("[$key]", $value, $message);
				}

				static::send($mailer, array($registrant->email), $subject, $message);

				$mailer->clearAddresses();
			}
		}
	}

	/**
	 * Send email when registrants complete deposit payment
	 *
	 * @param EventbookingTableRegistrant $row
	 * @param RADConfig                   $config
	 */
	public static function sendDepositPaymentEmail($row, $config)
	{
		if (EventbookingHelper::isMethodOverridden('EventbookingHelperOverrideMail', 'sendDepositPaymentEmail'))
		{
			EventbookingHelperOverrideMail::sendDepositPaymentEmail($row, $config);

			return;
		}

		$message     = EventbookingHelper::getMessages();
		$fieldSuffix = EventbookingHelper::getFieldSuffix($row->language);

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*')
			->from('#__eb_events')
			->where('id=' . $row->event_id);
		$db->setQuery($query);
		if ($fieldSuffix)
		{
			EventbookingHelperDatabase::getMultilingualFields($query, array('title'), $fieldSuffix);
		}
		$event = $db->loadObject();

		if (strlen(trim($event->notification_emails)) > 0)
		{
			$config->notification_emails = $event->notification_emails;
		}

		$mailer   = static::getMailer($config);
		$replaces = EventbookingHelperRegistration::buildDepositPaymentTags($row, $config);

		//Notification email send to user
		if (JMailHelper::isEmailAddress($row->email))
		{
			if ($fieldSuffix && strlen($message->{'deposit_payment_user_email_subject' . $fieldSuffix}))
			{
				$subject = $message->{'deposit_payment_user_email_subject' . $fieldSuffix};
			}
			else
			{
				$subject = $message->deposit_payment_user_email_subject;
			}

			if ($fieldSuffix && EventbookingHelper::isValidMessage($message->{'deposit_payment_user_email_body' . $fieldSuffix}))
			{
				$body = $message->{'deposit_payment_user_email_body' . $fieldSuffix};
			}
			else
			{
				$body = $message->deposit_payment_user_email_body;
			}

			foreach ($replaces as $key => $value)
			{
				$key     = strtoupper($key);
				$body    = str_ireplace("[$key]", $value, $body);
				$subject = str_ireplace("[$key]", $value, $subject);
			}

			if ($row->ticket_code)
			{
				EventbookingHelperTicket::generateTicketsPDF($row, $config);
				$ticketFilePath = JPATH_ROOT . '/media/com_eventbooking/tickets/ticket_' . str_pad($row->id, 5, '0', STR_PAD_LEFT) . '.pdf';
				$mailer->addAttachment($ticketFilePath);
			}

			static::send($mailer, array($row->email), $subject, $body);

			$mailer->clearAttachments();
			$mailer->clearAllRecipients();
		}

		$emails = explode(',', $config->notification_emails);

		if (strlen($message->{'deposit_payment_admin_email_subject' . $fieldSuffix}))
		{
			$subject = $message->{'deposit_payment_admin_email_subject' . $fieldSuffix};
		}
		else
		{
			$subject = $message->deposit_payment_admin_email_subject;
		}

		if (EventbookingHelper::isValidMessage($message->{'deposit_payment_admin_email_body' . $fieldSuffix}))
		{
			$body = $message->{'deposit_payment_admin_email_body' . $fieldSuffix};
		}
		else
		{
			$body = $message->deposit_payment_admin_email_body;
		}

		foreach ($replaces as $key => $value)
		{
			$key     = strtoupper($key);
			$subject = str_ireplace("[$key]", $value, $subject);
			$body    = str_ireplace("[$key]", $value, $body);
		}

		$body = EventbookingHelper::convertImgTags($body);

		static::send($mailer, $emails, $subject, $body);
	}

	/**
	 * Send new event notification email to admin and users when new event is submitted in the frontend
	 *
	 * @param EventbookingTableEvent $row
	 * @param RADConfig              $config
	 */
	public static function sendNewEventNotificationEmail($row, $config)
	{
		if (EventbookingHelper::isMethodOverridden('EventbookingHelperOverrideMail', 'sendNewEventNotificationEmail'))
		{
			EventbookingHelperOverrideMail::sendNewEventNotificationEmail($row, $config);

			return;
		}

		$user        = JFactory::getUser();
		$message     = EventbookingHelper::getMessages();
		$fieldSuffix = EventbookingHelper::getFieldSuffix($row->language);

		$mailer = static::getMailer($config);

		$replaces = array(
			'username'    => $user->username,
			'name'        => $user->name,
			'event_id'    => $row->id,
			'event_title' => $row->title,
			'event_date'  => JHtml::_('date', $row->event_date, $config->event_date_format, null),
			'event_link'  => JUri::root() . 'administrator/index.php?option=com_eventbooking&view=event&id=' . $row->id,
		);

		//Notification email send to user

		if ($fieldSuffix && strlen($message->{'submit_event_user_email_subject' . $fieldSuffix}))
		{
			$subject = $message->{'submit_event_user_email_subject' . $fieldSuffix};
		}
		else
		{
			$subject = $message->submit_event_user_email_subject;
		}

		if ($fieldSuffix && EventbookingHelper::isValidMessage($message->{'submit_event_user_email_body' . $fieldSuffix}))
		{
			$body = $message->{'submit_event_user_email_body' . $fieldSuffix};
		}
		else
		{
			$body = $message->submit_event_user_email_body;
		}

		if ($subject)
		{
			foreach ($replaces as $key => $value)
			{
				$key     = strtoupper($key);
				$subject = str_ireplace("[$key]", $value, $subject);
				$body    = str_ireplace("[$key]", $value, $body);
			}

			$body = EventbookingHelper::convertImgTags($body);
			if (JMailHelper::isEmailAddress($user->email))
			{
				static::send($mailer, array($user->email), $subject, $body);
				$mailer->clearAllRecipients();
			}
		}

		$emails = explode(',', $config->notification_emails);
		$emails = array_map('trim', $emails);

		if (strlen($message->{'submit_event_admin_email_subject' . $fieldSuffix}))
		{
			$subject = $message->{'submit_event_admin_email_subject' . $fieldSuffix};
		}
		else
		{
			$subject = $message->submit_event_admin_email_subject;
		}

		if (!$subject)
		{
			return;
		}

		if (EventbookingHelper::isValidMessage($message->{'submit_event_admin_email_body' . $fieldSuffix}))
		{
			$body = $message->{'submit_event_admin_email_body' . $fieldSuffix};
		}
		else
		{
			$body = $message->submit_event_admin_email_body;
		}

		foreach ($replaces as $key => $value)
		{
			$key     = strtoupper($key);
			$subject = str_ireplace("[$key]", $value, $subject);
			$body    = str_ireplace("[$key]", $value, $body);
		}

		$body = EventbookingHelper::convertImgTags($body);

		static::send($mailer, $emails, $subject, $body);
	}

	/**
	 * Send reminder email to registrants
	 *
	 * @param int      $numberEmailSendEachTime
	 * @param string   $bccEmail
	 * @param Registry $params
	 *
	 */
	public static function sendReminder($numberEmailSendEachTime = 0, $bccEmail = null, $params = null)
	{
		if (EventbookingHelper::isMethodOverridden('EventbookingHelperOverrideMail', 'sendReminder'))
		{
			EventbookingHelperOverrideMail::sendReminder($numberEmailSendEachTime, $bccEmail, $params);

			return;
		}

		$db      = JFactory::getDbo();
		$query   = $db->getQuery(true);
		$config  = EventbookingHelper::getConfig();
		$message = EventbookingHelper::getMessages();
		$mailer  = static::getMailer($config);

		if ($params == null)
		{
			$params = new Registry;
		}

		$siteUrl = EventbookingHelper::getSiteUrl();

		EventbookingHelper::loadLanguage();

		if ($bccEmail)
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

		if (!$numberEmailSendEachTime)
		{
			$numberEmailSendEachTime = 15;
		}

		$eventFields = array('b.id as event_id', 'b.event_date', 'b.title', 'b.reminder_email_body');

		if (JLanguageMultilang::isEnabled())
		{
			$languages = EventbookingHelper::getLanguages();
			if (count($languages))
			{
				foreach ($languages as $language)
				{
					$eventFields[] = 'b.title_' . $language->sef;
				}
			}
		}

		$query->select('a.*, c.id AS location_id, c.name AS location_name, c.address as location_address')
			->select(implode(',', $eventFields))
			->from('#__eb_registrants AS a')
			->innerJoin('#__eb_events AS b ON a.event_id = b.id')
			->leftJoin('#__eb_locations AS c ON b.location_id = c.id')
			->where('(a.published = 1 OR (a.payment_method LIKE "os_offline%" AND a.published = 0))')
			->where('a.is_reminder_sent = 0')
			->where('b.send_first_reminder != 0')
			->where('IF(b.send_first_reminder > 0, b.send_first_reminder >= DATEDIFF(b.event_date, NOW()) AND DATEDIFF(b.event_date, NOW()) >= 0, DATEDIFF(NOW(), b.event_date) >= ABS(b.send_first_reminder) AND DATEDIFF(NOW(), b.event_date) <= 40)')
			->order('b.event_date, a.register_date');

		if (!$params->get('send_to_group_billing', 1))
		{
			$query->where('a.is_group_billing = 0');
		}

		if (!$params->get('send_to_group_members', 1))
		{
			$query->where('a.group_id = 0');
		}

		if (!$params->get('send_to_unpublished_events', 0))
		{
			$query->where('b.published = 1');
		}

		$db->setQuery($query, 0, $numberEmailSendEachTime);

		try
		{
			$rows = $db->loadObjectList();
		}
		catch (Exception  $e)
		{
			$rows = array();
		}

		if (!empty($config->log_email_types) && in_array('reminder_emails', explode(',', $config->log_email_types)))
		{
			$logEmails = true;
		}
		else
		{
			$logEmails = false;
		}

		$Itemid = EventbookingHelper::getItemid();

		for ($i = 0, $n = count($rows); $i < $n; $i++)
		{
			$row = $rows[$i];

			if (!JMailHelper::isEmailAddress($row->email))
			{
				continue;
			}

			$fieldSuffix = EventbookingHelper::getFieldSuffix($row->language);
			if ($fieldSuffix && strlen($message->{'reminder_email_subject' . $fieldSuffix}))
			{
				$emailSubject = $message->{'reminder_email_subject' . $fieldSuffix};
			}
			else
			{
				$emailSubject = $message->reminder_email_subject;
			}

			$eventTitle = $row->{'title' . $fieldSuffix};

			$emailSubject = str_ireplace('[EVENT_TITLE]', $eventTitle, $emailSubject);

			if (EventbookingHelper::isValidMessage($row->reminder_email_body))
			{
				$emailBody = $row->reminder_email_body;
			}
			elseif ($fieldSuffix && EventbookingHelper::isValidMessage($message->{'reminder_email_body' . $fieldSuffix}))
			{
				$emailBody = $message->{'reminder_email_body' . $fieldSuffix};
			}
			else
			{
				$emailBody = $message->reminder_email_body;
			}

			$replaces                   = array();
			$replaces['event_date']     = JHtml::_('date', $row->event_date, $config->event_date_format, null);
			$replaces['first_name']     = $row->first_name;
			$replaces['last_name']      = $row->last_name;
			$replaces['transaction_id'] = $row->transaction_id;
			$replaces['event_title']    = $eventTitle;
			$replaces['event_title']    = $eventTitle;
			$replaces['location_name']  = $row->location_name;

			$locationLink         = $siteUrl . 'index.php?option=com_eventbooking&view=map&location_id=' . $row->location_id . '&Itemid=' . $Itemid;
			$locationName         = $row->location_name . ' (' . $row->location_address . ')';
			$replaces['location'] = '<a href="' . $locationLink . '">' . $locationName . '</a>';

			$replaces['download_certificate_link'] = $siteUrl . 'index.php?option=com_eventbooking&task=registrant.download_certificate&download_code=' . $row->registration_code . '&Itemid=' . $Itemid;
			$replaces['download_ticket_link']      = $siteUrl . 'index.php?option=com_eventbooking&task=registrant.download_ticket&download_code=' . $row->registration_code . '&Itemid=' . $Itemid;

			// On process [REGISTRATION_DETAIL] tag if it is available in the email message
			if (strpos($emailBody, '[REGISTRATION_DETAIL]') !== false)
			{
				// Build this tag
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

				$replaces['registration_detail'] = EventbookingHelperRegistration::getEmailContent($config, $row, true, $form);
			}

			$emailBody = EventbookingHelperRegistration::processQRCODE($row, $emailBody);

			foreach ($replaces as $key => $value)
			{
				$emailBody = str_ireplace('[' . strtoupper($key) . ']', $value, $emailBody);
			}

			$emailBody = EventbookingHelper::convertImgTags($emailBody);
			static::send($mailer, array($row->email), $emailSubject, $emailBody, $logEmails, 2, 'reminder_emails');
			$mailer->clearAddresses();

			$query->clear()
				->update('#__eb_registrants')
				->set('is_reminder_sent = 1')
				->where('id = ' . (int) $row->id);
			$db->setQuery($query);
			$db->execute();
		}
	}

	/**
	 * Send reminder email to registrants
	 *
	 * @param int      $numberEmailSendEachTime
	 * @param string   $bccEmail
	 * @param Registry $params
	 *
	 */
	public static function sendSecondReminder($numberEmailSendEachTime = 0, $bccEmail = null, $params = null)
	{
		if (EventbookingHelper::isMethodOverridden('EventbookingHelperOverrideMail', 'sendSecondReminder'))
		{
			EventbookingHelperOverrideMail::sendSecondReminder($numberEmailSendEachTime, $bccEmail, $params);

			return;
		}

		$db      = JFactory::getDbo();
		$query   = $db->getQuery(true);
		$config  = EventbookingHelper::getConfig();
		$message = EventbookingHelper::getMessages();
		$mailer  = static::getMailer($config);

		if ($params == null)
		{
			$params = new Registry;
		}

		$siteUrl = EventbookingHelper::getSiteUrl();

		EventbookingHelper::loadLanguage();

		if ($bccEmail && JMailHelper::isEmailAddress($bccEmail))
		{
			$mailer->addBcc($bccEmail);
		}

		if (!$numberEmailSendEachTime)
		{
			$numberEmailSendEachTime = 15;
		}

		$eventFields = array('b.id as event_id', 'b.event_date', 'b.title', 'b.second_reminder_email_body');

		if (JLanguageMultilang::isEnabled())
		{
			$languages = EventbookingHelper::getLanguages();

			if (count($languages))
			{
				foreach ($languages as $language)
				{
					$eventFields[] = 'b.title_' . $language->sef;
				}
			}
		}

		$query->select('a.*, c.id AS location_id, c.name AS location_name, c.address AS location_address')
			->select(implode(',', $eventFields))
			->from('#__eb_registrants AS a')
			->innerJoin('#__eb_events AS b ON a.event_id = b.id')
			->leftJoin('#__eb_locations AS c ON b.location_id = c.id')
			->where('(a.published = 1 OR (a.payment_method LIKE "os_offline%" AND a.published = 0))')
			->where('a.is_second_reminder_sent = 0')
			->where('b.send_second_reminder != 0')
			->where('IF(b.send_second_reminder > 0, b.send_second_reminder >= DATEDIFF(b.event_date, NOW()) AND DATEDIFF(b.event_date, NOW()) >= 0, DATEDIFF(NOW(), b.event_date) >= ABS(b.send_second_reminder) AND DATEDIFF(NOW(), b.event_date) <= 40)')
			->order('b.event_date, a.register_date');

		if (!$params->get('send_to_group_billing', 1))
		{
			$query->where('a.is_group_billing = 0');
		}

		if (!$params->get('send_to_group_members', 1))
		{
			$query->where('a.group_id = 0');
		}

		if (!$params->get('send_to_unpublished_events', 0))
		{
			$query->where('b.published = 1');
		}

		$db->setQuery($query, 0, $numberEmailSendEachTime);

		try
		{
			$rows = $db->loadObjectList();
		}
		catch (Exception  $e)
		{
			$rows = array();
		}

		if (!empty($config->log_email_types) && in_array('reminder_emails', explode(',', $config->log_email_types)))
		{
			$logEmails = true;
		}
		else
		{
			$logEmails = false;
		}

		$Itemid = EventbookingHelper::getItemid();

		for ($i = 0, $n = count($rows); $i < $n; $i++)
		{
			$row = $rows[$i];

			if (!JMailHelper::isEmailAddress($row->email))
			{
				continue;
			}

			$fieldSuffix = EventbookingHelper::getFieldSuffix($row->language);

			if ($fieldSuffix && strlen($message->{'second_reminder_email_subject' . $fieldSuffix}))
			{
				$emailSubject = $message->{'second_reminder_email_subject' . $fieldSuffix};
			}
			else
			{
				$emailSubject = $message->second_reminder_email_subject;
			}

			$eventTitle = $row->{'title' . $fieldSuffix};

			$emailSubject = str_ireplace('[EVENT_TITLE]', $eventTitle, $emailSubject);

			if (EventbookingHelper::isValidMessage($row->second_reminder_email_body))
			{
				$emailBody = $row->second_reminder_email_body;
			}
			elseif ($fieldSuffix && EventbookingHelper::isValidMessage($message->{'second_reminder_email_body' . $fieldSuffix}))
			{
				$emailBody = $message->{'second_reminder_email_body' . $fieldSuffix};
			}
			else
			{
				$emailBody = $message->second_reminder_email_body;
			}

			$replaces                   = array();
			$replaces['event_date']     = JHtml::_('date', $row->event_date, $config->event_date_format, null);
			$replaces['first_name']     = $row->first_name;
			$replaces['last_name']      = $row->last_name;
			$replaces['event_title']    = $eventTitle;
			$replaces['location_name']  = $row->location_name;
			$replaces['transaction_id'] = $row->transaction_id;

			$locationLink         = $siteUrl . 'index.php?option=com_eventbooking&view=map&location_id=' . $row->location_id . '&Itemid=' . $Itemid;
			$locationName         = $row->location_name . ' (' . $row->location_address . ')';
			$replaces['location'] = '<a href="' . $locationLink . '">' . $locationName . '</a>';

			$replaces['download_certificate_link'] = $siteUrl . 'index.php?option=com_eventbooking&task=registrant.download_certificate&download_code=' . $row->registration_code . '&Itemid=' . $Itemid;
			$replaces['download_ticket_link']      = $siteUrl . 'index.php?option=com_eventbooking&task=registrant.download_ticket&download_code=' . $row->registration_code . '&Itemid=' . $Itemid;

			// On process [REGISTRATION_DETAIL] tag if it is available in the email message
			if (strpos($emailBody, '[REGISTRATION_DETAIL]') !== false)
			{
				// Build this tag
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

				$replaces['registration_detail'] = EventbookingHelperRegistration::getEmailContent($config, $row, true, $form);
			}

			$emailBody = EventbookingHelperRegistration::processQRCODE($row, $emailBody);

			foreach ($replaces as $key => $value)
			{
				$emailBody = str_ireplace('[' . strtoupper($key) . ']', $value, $emailBody);
			}

			$emailBody = EventbookingHelper::convertImgTags($emailBody);
			static::send($mailer, array($row->email), $emailSubject, $emailBody, $logEmails, 2, 'reminder_emails');
			$mailer->clearAddresses();

			$query->clear()
				->update('#__eb_registrants')
				->set('is_second_reminder_sent = 1')
				->where('id = ' . (int) $row->id);
			$db->setQuery($query);
			$db->execute();
		}
	}

	/**
	 * Send deposit payment reminder email to registrants
	 *
	 * @param int  $numberDays
	 * @param int  $numberEmailSendEachTime
	 * @param null $bccEmail
	 */
	public static function sendDepositReminder($numberDays, $numberEmailSendEachTime = 0, $bccEmail = null)
	{
		if (EventbookingHelper::isMethodOverridden('EventbookingHelperOverrideMail', 'sendDepositReminder'))
		{
			EventbookingHelperOverrideMail::sendDepositReminder($numberDays, $numberEmailSendEachTime, $bccEmail);

			return;
		}

		$db      = JFactory::getDbo();
		$query   = $db->getQuery(true);
		$config  = EventbookingHelper::getConfig();
		$message = EventbookingHelper::getMessages();
		$mailer  = static::getMailer($config);
		$Itemid  = EventbookingHelper::getItemid();
		$siteUrl = EventbookingHelper::getSiteUrl();

		if ($bccEmail)
		{
			$mailer->addBcc($bccEmail);
		}

		if (!$numberDays)
		{
			$numberDays = 7;
		}

		if (!$numberEmailSendEachTime)
		{
			$numberEmailSendEachTime = 15;
		}

		$query->select('a.id, a.first_name, a.last_name, a.email, a.amount, a.deposit_amount, b.title, b.event_date, b.currency_symbol')
			->from('#__eb_registrants AS a')
			->innerJoin('#__eb_events AS b ON a.event_id = b.id')
			->where('(a.published = 1 OR (a.payment_method LIKE "os_offline%" AND a.published = 0))')
			->where('a.payment_status != 1')
			->where('a.group_id = 0')
			->where('a.is_deposit_payment_reminder_sent = 0')
			->where('b.published = 1')
			->where('DATEDIFF(b.event_date, NOW()) <= ' . $numberDays)
			->where('DATEDIFF(b.event_date, NOW()) >= 0')
			->order('b.event_date, a.register_date');

		$db->setQuery($query, 0, $numberEmailSendEachTime);

		try
		{
			$rows = $db->loadObjectList();
		}
		catch (Exception  $e)
		{
			$rows = array();
		}

		foreach ($rows as $row)
		{
			if (!JMailHelper::isEmailAddress($row->email))
			{
				continue;
			}

			$emailSubject = $message->deposit_payment_reminder_email_subject;
			$emailBody    = $message->deposit_payment_reminder_email_body;

			$replaces                         = array();
			$replaces['event_date']           = JHtml::_('date', $row->event_date, $config->event_date_format, null);
			$replaces['first_name']           = $row->first_name;
			$replaces['last_name']            = $row->last_name;
			$replaces['event_title']          = $row->title;
			$replaces['amount']               = EventbookingHelper::formatCurrency($row->amount - $row->deposit_amount, $config, $row->currency_symbol);
			$replaces['registration_id']      = $row->id;
			$replaces['deposit_payment_link'] = $siteUrl . 'index.php?option=com_eventbooking&view=payment&amp;registrant_id=' . $row->id . '&Itemid=' . $Itemid;

			foreach ($replaces as $key => $value)
			{
				$emailSubject = str_ireplace('[' . strtoupper($key) . ']', $value, $emailSubject);
				$emailBody    = str_ireplace('[' . strtoupper($key) . ']', $value, $emailBody);
			}

			$emailBody = EventbookingHelper::convertImgTags($emailBody);
			static::send($mailer, array($row->email), $emailSubject, $emailBody);
			$mailer->clearAddresses();

			$query->clear()
				->update('#__eb_registrants')
				->set('is_deposit_payment_reminder_sent = 1')
				->where('id = ' . (int) $row->id);
			$db->setQuery($query);
			$db->execute();
		}
	}

	/**
	 * Create and initialize mailer object from configuration data
	 *
	 * @param $config
	 *
	 * @return JMail
	 */
	public static function getMailer($config)
	{
		$mailer = JFactory::getMailer();

		if ($config->reply_to_email)
		{
			$mailer->addReplyTo($config->reply_to_email);
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

		if (empty($config->notification_emails))
		{
			$config->notification_emails = $fromEmail;
		}

		static::$fromName  = $fromName;
		static::$fromEmail = $fromEmail;

		return $mailer;
	}

	/**
	 * Add event's attachments to mailer object for sending emails to registrants
	 *
	 * @param JMail                       $mailer
	 * @param EventbookingTableRegistrant $row
	 * @param EventbookingTableEvent      $event
	 * @param RADConfig                   $config
	 *
	 * @return array
	 */
	public static function addEventAttachments($mailer, $row, $event, $config)
	{
		$attachments = [];

		if ($config->multiple_booking)
		{
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select('attachment')
				->from('#__eb_events')
				->where('id IN (SELECT event_id FROM #__eb_registrants AS a WHERE a.id=' . $row->id . ' OR a.cart_id=' . $row->id . ' ORDER BY a.id)');
			$db->setQuery($query);
			$attachmentFiles = $db->loadColumn();
		}
		elseif ($event->attachment)
		{
			$attachmentFiles = array($event->attachment);
		}
		else
		{
			$attachmentFiles = array();
		}

		// Remove empty value from array
		$attachmentFiles = array_filter($attachmentFiles);

		// Add all valid attachments to email
		foreach ($attachmentFiles as $attachmentFile)
		{
			$files = explode('|', $attachmentFile);

			foreach ($files as $file)
			{
				$filePath = JPATH_ROOT . '/media/com_eventbooking/' . $file;

				if ($file && file_exists($filePath))
				{
					$mailer->addAttachment($filePath);
					$attachments[] = $filePath;
				}
			}
		}

		return $attachments;
	}

	/**
	 * Add file uploads to the mailer object for sending to administrator
	 *
	 * @param JMail $mailer
	 * @param array $rowFields
	 * @param array $replaces
	 */
	public static function addRegistrationFormAttachments($mailer, $rowFields, $replaces)
	{
		$attachmentsPath = JPATH_ROOT . '/media/com_eventbooking/files/';
		for ($i = 0, $n = count($rowFields); $i < $n; $i++)
		{
			$rowField = $rowFields[$i];
			if ($rowField->fieldtype == 'File')
			{
				if (isset($replaces[$rowField->name]))
				{
					$fileName = $replaces[$rowField->name];
					if ($fileName && file_exists($attachmentsPath . '/' . $fileName))
					{
						$pos = strpos($fileName, '_');
						if ($pos !== false)
						{
							$originalFilename = substr($fileName, $pos + 1);
						}
						else
						{
							$originalFilename = $fileName;
						}
						$mailer->addAttachment($attachmentsPath . '/' . $fileName, $originalFilename);
					}
				}
			}
		}
	}

	/**
	 * Process sending after all the data has been initialized
	 *
	 * @param JMail  $mailer
	 * @param array  $emails
	 * @param string $subject
	 * @param string $body
	 * @param bool   $logEmails
	 * @param int    $sentTo
	 * @param string $emailType
	 */
	public static function send($mailer, $emails, $subject, $body, $logEmails = false, $sentTo = 0, $emailType = '')
	{
		if (empty($subject))
		{
			return;
		}

		$emails = array_map('trim', $emails);

		for ($i = 0, $n = count($emails); $i < $n; $i++)
		{
			if (!JMailHelper::isEmailAddress($emails[$i]))
			{
				unset($emails[$i]);
			}
		}

		$emails = array_unique($emails);

		if (count($emails) == 0)
		{
			return;
		}

		$email     = $emails[0];
		$bccEmails = array();
		$mailer->addRecipient($email);

		if (count($emails) > 1)
		{
			unset($emails[0]);
			$bccEmails = $emails;
			$mailer->addBcc($bccEmails);
		}

		$emailBody = EventbookingHelperHtml::loadCommonLayout('emailtemplates/tmpl/email.php', array('body' => $body, 'subject' => $subject));

		$mailer->setSubject($subject)
			->setBody($emailBody)
			->Send();

		if ($logEmails)
		{
			$row             = JTable::getInstance('Email', 'EventbookingTable');
			$row->sent_at    = JFactory::getDate()->toSql();
			$row->email      = $email;
			$row->subject    = $subject;
			$row->body       = $body;
			$row->sent_to    = $sentTo;
			$row->email_type = $emailType;
			$row->store();

			if (count($bccEmails))
			{
				foreach ($bccEmails as $email)
				{
					$row->id    = 0;
					$row->email = $email;
					$row->store();
				}
			}
		}
	}


	/**
	 * Send email to registrant to ask them to make payment for their registration
	 *
	 * @param EventbookingTableRegistrant $row
	 * @param RADConfig                   $config
	 *
	 * @return void
	 * @throws Exception
	 */
	public static function sendRequestPaymentEmail($row, $config)
	{
		if (!JMailHelper::isEmailAddress($row->email))
		{
			return;
		}

		if (EventbookingHelper::isMethodOverridden('EventbookingHelperOverrideMail', 'sendRequestPaymentEmail'))
		{
			EventbookingHelperOverrideMail::sendRequestPaymentEmail($row, $config);

			return;
		}

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		EventbookingHelper::loadComponentLanguage($row->language, true);

		$message = EventbookingHelper::getMessages();

		// Make sure subject and message is configured
		if (empty($message->request_payment_email_subject))
		{
			throw new Exception('Please configure request payment email subject in Waiting List Messages tab');
		}


		$fieldSuffix = EventbookingHelper::getFieldSuffix($row->language);
		$mailer      = static::getMailer($config);

		if ($config->multiple_booking)
		{
			$rowFields = EventbookingHelperRegistration::getFormFields($row->id, 4, $row->language);
		}
		elseif ($row->is_group_billing)
		{
			$rowFields = EventbookingHelperRegistration::getFormFields($row->event_id, 1, $row->language);
		}
		else
		{
			$rowFields = EventbookingHelperRegistration::getFormFields($row->event_id, 0, $row->language);
		}

		$form = new RADForm($rowFields);
		$data = EventbookingHelperRegistration::getRegistrantData($row, $rowFields);
		$form->bind($data);
		$form->buildFieldsDependency();

		$query->select('*')
			->from('#__eb_events')
			->where('id = ' . $row->event_id);

		if ($fieldSuffix)
		{
			EventbookingHelperDatabase::getMultilingualFields($query, array('title', 'short_description', 'description'), $fieldSuffix);
		}

		$db->setQuery($query);
		$event = $db->loadObject();

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

		$subject                  = $message->request_payment_email_subject;
		$body                     = $message->request_payment_email_body;
		$replaces['payment_link'] = JUri::root() . 'index.php?option=com_eventbooking&view=payment&layout=registration&order_number=' . $row->registration_code . '&Itemid=' . EventbookingHelper::getItemid();

		foreach ($replaces as $key => $value)
		{
			$key     = strtoupper($key);
			$subject = str_ireplace("[$key]", $value, $subject);
			$body    = str_ireplace("[$key]", $value, $body);
		}

		$body = EventbookingHelper::convertImgTags($body);

		static::send($mailer, array($row->email), $subject, $body);
	}

	/**
	 * Send email to registrant when admin approves his registration
	 *
	 * @param EventbookingTableRegistrant $row
	 * @param RADConfig                   $config
	 */
	public static function sendCertificateEmail($row, $config)
	{
		if (!JMailHelper::isEmailAddress($row->email))
		{
			return;
		}

		if (EventbookingHelper::isMethodOverridden('EventbookingHelperOverrideMail', 'sendCertificateEmail'))
		{
			EventbookingHelperOverrideMail::sendCertificateEmail($row, $config);

			return;
		}

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		EventbookingHelper::loadComponentLanguage($row->language, true);

		$message     = EventbookingHelper::getMessages();
		$fieldSuffix = EventbookingHelper::getFieldSuffix($row->language);
		$mailer      = static::getMailer($config);

		if ($config->multiple_booking)
		{
			$rowFields = EventbookingHelperRegistration::getFormFields($row->id, 4, $row->language);
		}
		elseif ($row->is_group_billing)
		{
			$rowFields = EventbookingHelperRegistration::getFormFields($row->event_id, 1, $row->language);
		}
		else
		{
			$rowFields = EventbookingHelperRegistration::getFormFields($row->event_id, 0, $row->language);
		}

		$form = new RADForm($rowFields);
		$data = EventbookingHelperRegistration::getRegistrantData($row, $rowFields);
		$form->bind($data);
		$form->buildFieldsDependency();

		$query->select('*')
			->from('#__eb_events')
			->where('id = ' . $row->event_id);

		if ($fieldSuffix)
		{
			EventbookingHelperDatabase::getMultilingualFields($query, array('title', 'short_description', 'description'), $fieldSuffix);
		}

		$db->setQuery($query);
		$event = $db->loadObject();

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

		$subject = $message->certificate_email_subject;
		$body    = $message->certificate_email_body;

		if (empty($subject))
		{
			throw new Exception('Email subject could not be empty. Go to Events Booking -> Emails & Messages and setup Certificate email subject');
		}

		if (empty($body))
		{
			throw new Exception('Email message could not be empty. Go to Events Booking -> Emails & Messages and setup Certificate email body');
		}

		foreach ($replaces as $key => $value)
		{
			$key     = strtoupper($key);
			$subject = str_ireplace("[$key]", $value, $subject);
			$body    = str_ireplace("[$key]", $value, $body);
		}

		$body = EventbookingHelper::convertImgTags($body);
		$body = EventbookingHelperRegistration::processQRCODE($row, $body);

		list($fileName, $filePath) = EventbookingHelper::generateCertificates([$row], $config);

		$mailer->addAttachment($filePath, $fileName);

		static::send($mailer, array($row->email), $subject, $body);
	}

	/**
	 * Send email to administrator and user when user cancel his registration
	 *
	 * @param EventbookingTableRegistrant $row
	 * @param RADConfig                   $config
	 */
	public static function sendUserCancelRegistrationEmail($row, $config)
	{
		if (EventbookingHelper::isMethodOverridden('EventbookingHelperOverrideMail', 'sendUserCancelRegistationEmail'))
		{
			EventbookingHelperOverrideMail::sendUserCancelRegistationEmail($row, $config);

			return;
		}

		$mailer      = static::getMailer($config);
		$message     = EventbookingHelper::getMessages();
		$fieldSuffix = EventbookingHelper::getFieldSuffix();
		$db          = JFactory::getDbo();
		$query       = $db->getQuery(true);

		if ($config->multiple_booking)
		{
			$rowFields = EventbookingHelperRegistration::getFormFields($row->id, 4, $row->language);
		}
		elseif ($row->is_group_billing)
		{
			$rowFields = EventbookingHelperRegistration::getFormFields($row->event_id, 1, $row->language);
		}
		else
		{
			$rowFields = EventbookingHelperRegistration::getFormFields($row->event_id, 0, $row->language);
		}

		$form = new RADForm($rowFields);
		$data = EventbookingHelperRegistration::getRegistrantData($row, $rowFields);
		$form->bind($data);
		$form->buildFieldsDependency();

		$query->select('*')
			->from('#__eb_events')
			->where('id = ' . $row->event_id);

		if ($fieldSuffix)
		{
			EventbookingHelperDatabase::getMultilingualFields($query, array('title', 'short_description', 'description'), $fieldSuffix);
		}

		$db->setQuery($query);
		$event = $db->loadObject();

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

		if ($fieldSuffix && strlen(trim($message->{'registration_cancel_confirmation_email_subject' . $fieldSuffix})))
		{
			$subject = $message->{'registration_cancel_confirmation_email_subject' . $fieldSuffix};
		}
		else
		{
			$subject = $message->registration_cancel_confirmation_email_subject;
		}

		if ($fieldSuffix && EventbookingHelper::isValidMessage($message->{'registration_cancel_confirmation_email_body' . $fieldSuffix}))
		{
			$body = $message->{'registration_cancel_confirmation_email_body' . $fieldSuffix};
		}
		else
		{
			$body = $message->registration_cancel_confirmation_email_body;
		}

		foreach ($replaces as $key => $value)
		{
			$key     = strtoupper($key);
			$subject = str_ireplace("[$key]", $value, $subject);
			$body    = str_ireplace("[$key]", $value, $body);
		}

		$body = EventbookingHelper::convertImgTags($body);

		static::send($mailer, array($row->email), $subject, $body);

		$mailer->clearAllRecipients();

		if ($fieldSuffix && strlen(trim($message->{'registration_cancel_email_subject' . $fieldSuffix})))
		{
			$subject = $message->{'registration_cancel_email_subject' . $fieldSuffix};
		}
		else
		{
			$subject = $message->registration_cancel_email_subject;
		}

		if ($fieldSuffix && EventbookingHelper::isValidMessage($message->{'registration_cancel_email_body' . $fieldSuffix}))
		{
			$body = $message->{'registration_cancel_email_body' . $fieldSuffix};
		}
		else
		{
			$body = $message->registration_cancel_email_body;
		}

		foreach ($replaces as $key => $value)
		{
			$key     = strtoupper($key);
			$subject = str_ireplace("[$key]", $value, $subject);
			$body    = str_ireplace("[$key]", $value, $body);
		}

		$body = EventbookingHelper::convertImgTags($body);

		$emails = $emails = explode(',', $config->notification_emails);

		if ($config->send_email_to_event_creator)
		{
			$query->clear()
				->select('email')
				->from('#__users')
				->where('id = ' . (int) $event->created_by);
			$db->setQuery($query);
			$eventCreatorEmail = $db->loadResult();

			if ($eventCreatorEmail && JMailHelper::isEmailAddress($eventCreatorEmail))
			{
				$emails[] = $eventCreatorEmail;
			}
		}

		static::send($mailer, $emails, $subject, $body);
	}
}

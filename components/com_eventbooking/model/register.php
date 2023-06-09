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

class EventbookingModelRegister extends RADModel
{
	/**
	 * Check to see whether registrant entered correct password for private event
	 *
	 * @param $eventId
	 * @param $password
	 *
	 * @return bool
	 */
	public function checkPassword($eventId, $password)
	{
		$db    = $this->getDbo();
		$query = $db->getQuery(true);
		$query->select('COUNT(*)')
			->from('#__eb_events')
			->where('id = ' . $eventId)
			->where('event_password = ' . $db->quote($password));
		$db->setQuery($query);
		$total = $db->loadResult();

		if ($total)
		{
			return true;
		}

		return false;
	}

	/**
	 * Process individual registration
	 *
	 * @param $data
	 *
	 * @return int
	 * @throws Exception
	 */
	public function processIndividualRegistration($data)
	{
		jimport('joomla.user.helper');

		$app    = JFactory::getApplication();
		$db     = JFactory::getDbo();
		$query  = $db->getQuery(true);
		$user   = JFactory::getUser();
		$config = EventbookingHelper::getConfig();

		/* @var EventbookingTableRegistrant $row */
		$row                    = JTable::getInstance('EventBooking', 'Registrant');
		$data['transaction_id'] = strtoupper(JUserHelper::genRandomPassword());

		if (!$user->id && $config->user_registration)
		{
			$userId          = EventbookingHelperRegistration::saveRegistration($data);
			$data['user_id'] = $userId;
		}

		$row->registration_code = EventbookingHelperRegistration::getRegistrationCode();
		$row->ticket_qrcode     = EventbookingHelperRegistration::getTicketCode();

		// Calculate the payment amount
		$eventId = (int) $data['event_id'];
		$event   = EventbookingHelperDatabase::getEvent($eventId);

		if (($event->event_capacity > 0) && ($event->event_capacity <= $event->total_registrants))
		{
			$waitingList = true;
		}
		else
		{
			$waitingList = false;
		}

		$paymentMethod = isset($data['payment_method']) ? $data['payment_method'] : '';
		$rowFields     = EventbookingHelperRegistration::getFormFields($eventId, 0);
		$form          = new RADForm($rowFields);
		$form->bind($data);

		if ($waitingList == true)
		{
			if (is_callable('EventbookingHelperOverrideRegistration::calculateIndividualRegistrationFees'))
			{
				$fees = EventbookingHelperOverrideRegistration::calculateIndividualRegistrationFees($event, $form, $data, $config, '');
			}
			elseif (is_callable('EventbookingHelperOverrideHelper::calculateIndividualRegistrationFees'))
			{
				$fees = EventbookingHelperOverrideHelper::calculateIndividualRegistrationFees($event, $form, $data, $config, '');
			}
			else
			{
				$fees = EventbookingHelperRegistration::calculateIndividualRegistrationFees($event, $form, $data, $config, '');
			}
		}
		else
		{
			if (is_callable('EventbookingHelperOverrideRegistration::calculateIndividualRegistrationFees'))
			{
				$fees = EventbookingHelperOverrideRegistration::calculateIndividualRegistrationFees($event, $form, $data, $config, $paymentMethod);
			}
			elseif (is_callable('EventbookingHelperOverrideHelper::calculateIndividualRegistrationFees'))
			{
				$fees = EventbookingHelperOverrideHelper::calculateIndividualRegistrationFees($event, $form, $data, $config, $paymentMethod);
			}
			else
			{
				$fees = EventbookingHelperRegistration::calculateIndividualRegistrationFees($event, $form, $data, $config, $paymentMethod);
			}
		}

		$paymentType = isset($data['payment_type']) ? (int) $data['payment_type'] : 0;

		if ($paymentType == 0)
		{
			$fees['deposit_amount'] = 0;
		}

		$data['total_amount']           = round($fees['total_amount'], 2);
		$data['discount_amount']        = round($fees['discount_amount'], 2);
		$data['late_fee']               = round($fees['late_fee'], 2);
		$data['tax_amount']             = round($fees['tax_amount'], 2);
		$data['amount']                 = round($fees['amount'], 2);
		$data['deposit_amount']         = $fees['deposit_amount'];
		$data['payment_processing_fee'] = $fees['payment_processing_fee'];
		$data['coupon_discount_amount'] = round($fees['coupon_discount_amount'], 2);

		$row->bind($data);

		if ($config->show_subscribe_newsletter_checkbox)
		{
			$row->subscribe_newsletter = empty($data['subscribe_to_newsletter']) ? 0 : 1;
		}
		else
		{
			$row->subscribe_newsletter = 1;
		}

		$row->agree_privacy_policy = 1;

		$row->group_id           = 0;
		$row->published          = 0;
		$row->register_date      = gmdate('Y-m-d H:i:s');
		$row->number_registrants = 1;

		if (isset($data['user_id']))
		{
			$row->user_id = $data['user_id'];
		}
		else
		{
			$row->user_id = $user->get('id');
		}

		if ($row->deposit_amount > 0)
		{
			$row->payment_status = 0;
		}
		else
		{
			$row->payment_status = 1;
		}

		$row->user_ip = EventbookingHelper::getUserIp();

		//Save the active language
		if (JFactory::getApplication()->getLanguageFilter())
		{
			$row->language = JFactory::getLanguage()->getTag();
		}
		else
		{
			$row->language = '*';
		}

		$couponCode = isset($data['coupon_code']) ? $data['coupon_code'] : null;

		if ($couponCode && $fees['coupon_valid'])
		{
			$coupon         = $fees['coupon'];
			$row->coupon_id = $coupon->id;
		}

		if (!empty($fees['bundle_discount_ids']))
		{
			$query->clear()
				->update('#__eb_discounts')
				->set('used = used + 1')
				->where('id IN (' . implode(',', $fees['bundle_discount_ids']) . ')');
			$db->setQuery($query);
			$db->execute();
		}

		if ($waitingList)
		{
			$row->published      = 3;
			$row->payment_method = 'os_offline';
		}

		$row->store();
		$form->storeData($row->id, $data);

		// Store registrant data
		if ($event->has_multiple_ticket_types)
		{
			$ticketTypes = EventbookingHelperData::getTicketTypes($eventId);

			foreach ($ticketTypes as $ticketType)
			{
				if (!empty($data['ticket_type_' . $ticketType->id]))
				{
					$quantity = (int) $data['ticket_type_' . $ticketType->id];
					$query->clear()
						->insert('#__eb_registrant_tickets')
						->columns('registrant_id, ticket_type_id, quantity')
						->values("$row->id, $ticketType->id, $quantity");
					$db->setQuery($query)
						->execute();
				}
			}

			$params = new Registry($event->params);

			if ($params->get('ticket_types_collect_members_information'))
			{
				// Store Members information

				$numberRegistrants = 0;
				$count             = 0;

				foreach ($ticketTypes as $ticketType)
				{
					if (!empty($data['ticket_type_' . $ticketType->id]))
					{
						$quantity          = (int) $data['ticket_type_' . $ticketType->id];
						$numberRegistrants += $quantity;

						$memberFormFields = EventbookingHelperRegistration::getFormFields($eventId, 2);

						for ($i = 0; $i < $quantity; $i++)
						{
							$rowMember                       = JTable::getInstance('EventBooking', 'Registrant');
							$rowMember->group_id             = $row->id;
							$rowMember->transaction_id       = $row->transaction_id;
							$rowMember->ticket_qrcode        = EventbookingHelperRegistration::getTicketQRCode();
							$rowMember->event_id             = $row->event_id;
							$rowMember->payment_method       = $row->payment_method;
							$rowMember->payment_status       = $row->payment_status;
							$rowMember->user_id              = $row->user_id;
							$rowMember->register_date        = $row->register_date;
							$rowMember->user_ip              = $row->user_ip;
							$rowMember->registration_code    = EventbookingHelperRegistration::getRegistrationCode();
							$rowMember->total_amount         = $ticketType->price;
							$rowMember->discount_amount      = 0;
							$rowMember->late_fee             = 0;
							$rowMember->tax_amount           = 0;
							$rowMember->amount               = $ticketType->price;
							$rowMember->number_registrants   = 1;
							$rowMember->subscribe_newsletter = $row->subscribe_newsletter;
							$rowMember->agree_privacy_policy = 1;

							$count++;

							$memberForm = new RADForm($memberFormFields);
							$memberForm->setFieldSuffix($count);
							$memberForm->bind($data, true);
							$memberForm->buildFieldsDependency();

							$memberForm->removeFieldSuffix();
							$memberData = $memberForm->getFormData();
							$rowMember->bind($memberData);
							$rowMember->store();

							$memberForm->storeData($rowMember->id, $memberData);

							// Store registrant ticket type information
							$query->clear()
								->insert('#__eb_registrant_tickets')
								->columns('registrant_id, ticket_type_id, quantity')
								->values("$rowMember->id, $ticketType->id, 1");
							$db->setQuery($query)
								->execute();
						}

						$row->is_group_billing   = 1;
						$row->number_registrants = $numberRegistrants;
						$row->store();
					}
				}
			}
		}

		$data['event_title'] = $event->title;

		JPluginHelper::importPlugin('eventbooking');
		$app->triggerEvent('onAfterStoreRegistrant', array($row));

		if ($row->deposit_amount > 0)
		{
			$data['amount'] = $row->deposit_amount;
		}

		// Store registration_code into session, use for registration complete code
		JFactory::getSession()->set('eb_registration_code', $row->registration_code);

		if ($row->amount > 0 && !$waitingList)
		{
			require_once JPATH_COMPONENT . '/payments/' . $paymentMethod . '.php';

			$itemName = JText::_('EB_EVENT_REGISTRATION');
			$itemName = str_replace('[EVENT_TITLE]', $data['event_title'], $itemName);
			$itemName = str_replace('[EVENT_DATE]', JHtml::_('date', $event->event_date, $config->date_format, null), $itemName);
			$itemName = str_replace('[FIRST_NAME]', $row->first_name, $itemName);
			$itemName = str_replace('[LAST_NAME]', $row->last_name, $itemName);
			$itemName = str_replace('[REGISTRANT_ID]', $row->id, $itemName);

			$data['item_name'] = $itemName;

			// Guess card type based on card number
			if (!empty($data['x_card_num']) && empty($data['card_type']))
			{
				$data['card_type'] = EventbookingHelperCreditcard::getCardType($data['x_card_num']);
			}

			$query->clear()
				->select('title, params')
				->from('#__eb_payment_plugins')
				->where('name = ' . $db->quote($paymentMethod));
			$db->setQuery($query);
			$plugin       = $db->loadObject();
			$params       = new Registry($plugin->params);
			$paymentClass = new $paymentMethod($params);
			$paymentClass->setTitle(JText::_($plugin->title));

			// Convert payment amount to USD if the currency is not supported by payment gateway
			$currency = $event->currency_code ? $event->currency_code : $config->currency_code;

			if (method_exists($paymentClass, 'getSupportedCurrencies'))
			{
				$currencies = $paymentClass->getSupportedCurrencies();

				if (!in_array($currency, $currencies))
				{
					$data['amount'] = EventbookingHelper::convertAmountToUSD($data['amount'], $currency);
					$currency       = 'USD';
				}
			}

			$data['currency'] = $currency;

			$country         = empty($data['country']) ? $config->default_country : $data['country'];
			$data['country'] = EventbookingHelper::getCountryCode($country);

			// Store payment amount and payment currency for future validation
			$row->payment_currency = $currency;
			$row->payment_amount   = $data['amount'];
			$row->store();

			$paymentClass->processPayment($row, $data);
		}
		else
		{
			if (!$waitingList)
			{
				$row->payment_date = gmdate('Y-m-d H:i:s');

				if ($row->total_amount == 0)
				{
					$published = $event->free_event_registration_status;
				}
				else
				{
					$published = 1;
				}

				if ($published == 0)
				{
					$row->payment_method = 'os_offline';
				}

				$row->published = $published;

				$row->store();

				if ($row->published == 1)
				{
					$app->triggerEvent('onAfterPaymentSuccess', array($row));
				}

				EventbookingHelper::sendEmails($row, $config);

				return 1;
			}
			else
			{
				EventbookingHelperMail::sendWaitinglistEmail($row, $config);

				return 2;
			}
		}
	}

	/**
	 * Process Group Registration
	 *
	 * @param $data
	 *
	 * @return int
	 * @throws Exception
	 */
	public function processGroupRegistration($data)
	{
		jimport('joomla.user.helper');

		$app     = JFactory::getApplication();
		$session = JFactory::getSession();
		$user    = JFactory::getUser();
		$db      = JFactory::getDbo();
		$query   = $db->getQuery(true);
		$config  = EventbookingHelper::getConfig();

		/* @var EventbookingTableRegistrant $row */
		$row               = JTable::getInstance('EventBooking', 'Registrant');
		$numberRegistrants = (int) $session->get('eb_number_registrants', '');
		$membersData       = $session->get('eb_group_members_data', null);

		if ($membersData)
		{
			$membersData = unserialize($membersData);
		}
		else
		{
			$membersData = array();
		}

		$data['number_registrants'] = $numberRegistrants;
		$data['transaction_id']     = strtoupper(JUserHelper::genRandomPassword());

		if (!$user->id && $config->user_registration)
		{
			$userId          = EventbookingHelperRegistration::saveRegistration($data);
			$data['user_id'] = $userId;
		}

		$eventId = (int) $data['event_id'];
		$event   = EventbookingHelperDatabase::getEvent($eventId);

		if (($event->event_capacity > 0) && ($event->event_capacity <= $event->total_registrants))
		{
			$waitingList = true;
		}
		else
		{
			$waitingList = false;
		}

		$rowFields        = EventbookingHelperRegistration::getFormFields($eventId, 1);
		$memberFormFields = EventbookingHelperRegistration::getFormFields($eventId, 2);
		$form             = new RADForm($rowFields);
		$form->bind($data);

		$paymentMethod = isset($data['payment_method']) ? $data['payment_method'] : '';

		if ($waitingList)
		{
			if (is_callable('EventbookingHelperOverrideRegistration::calculateGroupRegistrationFees'))
			{
				$fees = EventbookingHelperOverrideRegistration::calculateGroupRegistrationFees($event, $form, $data, $config, null);
			}
			elseif (is_callable('EventbookingHelperOverrideHelper::calculateGroupRegistrationFees'))
			{
				$fees = EventbookingHelperOverrideHelper::calculateGroupRegistrationFees($event, $form, $data, $config, null);
			}
			else
			{
				$fees = EventbookingHelperRegistration::calculateGroupRegistrationFees($event, $form, $data, $config, null);
			}
		}
		else
		{
			if (is_callable('EventbookingHelperOverrideRegistration::calculateGroupRegistrationFees'))
			{
				$fees = EventbookingHelperOverrideRegistration::calculateGroupRegistrationFees($event, $form, $data, $config, $paymentMethod);
			}
			elseif (is_callable('EventbookingHelperOverrideHelper::calculateGroupRegistrationFees'))
			{
				$fees = EventbookingHelperOverrideHelper::calculateGroupRegistrationFees($event, $form, $data, $config, $paymentMethod);
			}
			else
			{
				$fees = EventbookingHelperRegistration::calculateGroupRegistrationFees($event, $form, $data, $config, $paymentMethod);
			}
		}

		//Calculate members fee
		$membersForm           = $fees['members_form'];
		$membersTotalAmount    = $fees['members_total_amount'];
		$membersDiscountAmount = $fees['members_discount_amount'];
		$membersTaxAmount      = $fees['members_tax_amount'];
		$membersLateFee        = $fees['members_late_fee'];
		$membersAmount         = $fees['members_amount'];
		$paymentType           = (int) @$data['payment_type'];

		if ($paymentType == 0)
		{
			$fees['deposit_amount'] = 0;
		}

		//The data for group billing record
		$data['total_amount']           = $fees['total_amount'];
		$data['discount_amount']        = $fees['discount_amount'];
		$data['late_fee']               = $fees['late_fee'];
		$data['tax_amount']             = $fees['tax_amount'];
		$data['deposit_amount']         = $fees['deposit_amount'];
		$data['payment_processing_fee'] = $fees['payment_processing_fee'];
		$data['amount']                 = $fees['amount'];
		$data['coupon_discount_amount'] = round($fees['coupon_discount_amount'], 2);

		if (!isset($data['first_name']))
		{
			//Get data from first member
			$firstMemberForm = new RADForm($memberFormFields);
			$firstMemberForm->setFieldSuffix(1);
			$firstMemberForm->bind($membersData);
			$firstMemberForm->removeFieldSuffix();
			$data = $data + $firstMemberForm->getFormData();
		}

		$row->bind($data);

		if ($config->show_subscribe_newsletter_checkbox)
		{
			$row->subscribe_newsletter = empty($data['subscribe_to_newsletter']) ? 0 : 1;
		}
		else
		{
			$row->subscribe_newsletter = 1;
		}

		$row->agree_privacy_policy = 1;

		$row->group_id         = 0;
		$row->published        = 0;
		$row->register_date    = gmdate('Y-m-d H:i:s');
		$row->is_group_billing = 1;
		if (isset($data['user_id']))
		{
			$row->user_id = $data['user_id'];
		}
		else
		{
			$row->user_id = $user->get('id');
		}

		if ($row->deposit_amount > 0)
		{
			$row->payment_status = 0;
		}
		else
		{
			$row->payment_status = 1;
		}

		// Save the active language
		if (JFactory::getApplication()->getLanguageFilter())
		{
			$row->language = JFactory::getLanguage()->getTag();
		}
		else
		{
			$row->language = '*';
		}

		// Unique registration code for the registration
		$row->registration_code = EventbookingHelperRegistration::getRegistrationCode();
		$row->ticket_qrcode     = EventbookingHelperRegistration::getTicketCode();

		// Coupon code
		$couponCode = isset($data['coupon_code']) ? $data['coupon_code'] : null;

		if ($couponCode && $fees['coupon_valid'])
		{
			$coupon         = $fees['coupon'];
			$row->coupon_id = $coupon->id;
		}

		if (!empty($fees['bundle_discount_ids']))
		{
			$query->clear()
				->update('#__eb_discounts')
				->set('used = used + 1')
				->where('id IN (' . implode(',', $fees['bundle_discount_ids']) . ')');
			$db->setQuery($query);
			$db->execute();
		}

		if ($waitingList)
		{
			$row->published      = 3;
			$row->payment_method = 'os_offline';
		}

		$row->user_ip = EventbookingHelper::getUserIp();

		//Clear the coupon session
		$row->store();
		$form->storeData($row->id, $data);

		if ($event->collect_member_information === '')
		{
			$collectMemberInformation = $config->collect_member_information;
		}
		else
		{
			$collectMemberInformation = $event->collect_member_information;
		}

		//Store group members data
		if ($collectMemberInformation)
		{
			for ($i = 0; $i < $numberRegistrants; $i++)
			{
				/* @var EventbookingTableRegistrant $rowMember */
				$rowMember                     = JTable::getInstance('EventBooking', 'Registrant');
				$rowMember->group_id           = $row->id;
				$rowMember->transaction_id     = $row->transaction_id;
				$rowMember->ticket_qrcode      = EventbookingHelperRegistration::getTicketQRCode();
				$rowMember->event_id           = $row->event_id;
				$rowMember->payment_method     = $row->payment_method;
				$rowMember->payment_status     = $row->payment_status;
				$rowMember->user_id            = $row->user_id;
				$rowMember->register_date      = $row->register_date;
				$rowMember->user_ip            = $row->user_ip;
				$rowMember->registration_code  = EventbookingHelperRegistration::getRegistrationCode();
				$rowMember->total_amount       = $membersTotalAmount[$i];
				$rowMember->discount_amount    = $membersDiscountAmount[$i];
				$rowMember->late_fee           = $membersLateFee[$i];
				$rowMember->tax_amount         = $membersTaxAmount[$i];
				$rowMember->amount             = $membersAmount[$i];
				$rowMember->number_registrants = 1;

				$rowMember->subscribe_newsletter = $row->subscribe_newsletter;
				$rowMember->agree_privacy_policy = 1;

				$membersForm[$i]->removeFieldSuffix();
				$memberData = $membersForm[$i]->getFormData();
				$rowMember->bind($memberData);
				$rowMember->store();

				//Store members data custom field
				$membersForm[$i]->storeData($rowMember->id, $memberData);
			}
		}

		$data['event_title'] = $event->title;

		// Trigger onAfterStoreRegistrant event
		JPluginHelper::importPlugin('eventbooking');
		$app->triggerEvent('onAfterStoreRegistrant', array($row));

		// Support deposit payment
		if ($row->deposit_amount > 0)
		{
			$data['amount'] = $row->deposit_amount;
		}

		// Clear session data
		$session->clear('eb_number_registrants');
		$session->clear('eb_group_members_data');
		$session->clear('eb_group_billing_data');

		//Store registration code in session, use it for registration complete page
		$session->set('eb_registration_code', $row->registration_code);

		if ($row->amount > 0 && !$waitingList)
		{
			require_once JPATH_COMPONENT . '/payments/' . $paymentMethod . '.php';

			$itemName          = JText::_('EB_EVENT_REGISTRATION');
			$itemName          = str_replace('[EVENT_TITLE]', $data['event_title'], $itemName);
			$itemName          = str_replace('[EVENT_DATE]', JHtml::_('date', $event->event_date, $config->date_format, null), $itemName);
			$itemName          = str_replace('[FIRST_NAME]', $row->first_name, $itemName);
			$itemName          = str_replace('[LAST_NAME]', $row->last_name, $itemName);
			$itemName          = str_replace('[REGISTRANT_ID]', $row->id, $itemName);
			$data['item_name'] = $itemName;

			// Validate credit card
			if (!empty($data['x_card_num']) && empty($data['card_type']))
			{
				$data['card_type'] = EventbookingHelperCreditcard::getCardType($data['x_card_num']);
			}

			$query->clear()
				->select('title, params')
				->from('#__eb_payment_plugins')
				->where('name = ' . $db->quote($paymentMethod));
			$db->setQuery($query);
			$plugin       = $db->loadObject();
			$params       = new Registry($plugin->params);
			$paymentClass = new $paymentMethod($params);
			$paymentClass->setTitle(JText::_($plugin->title));

			// Convert payment amount to USD if the currency is not supported by payment gateway
			$currency = $event->currency_code ? $event->currency_code : $config->currency_code;

			if (method_exists($paymentClass, 'getSupportedCurrencies'))
			{
				$currencies = $paymentClass->getSupportedCurrencies();

				if (!in_array($currency, $currencies))
				{
					$data['amount'] = EventbookingHelper::convertAmountToUSD($data['amount'], $currency);
					$currency       = 'USD';
				}
			}

			$data['currency'] = $currency;

			$country         = empty($data['country']) ? $config->default_country : $data['country'];
			$data['country'] = EventbookingHelper::getCountryCode($country);

			// Store payment amount and payment currency for future validation
			$row->payment_currency = $currency;
			$row->payment_amount   = $data['amount'];
			$row->store();

			$paymentClass->processPayment($row, $data);
		}
		else
		{
			if (!$waitingList)
			{
				$row->payment_date = gmdate('Y-m-d H:i:s');

				if ($row->total_amount == 0)
				{
					$published = $event->free_event_registration_status;
				}
				else
				{
					$published = 1;
				}

				if ($published == 0)
				{
					$row->payment_method = 'os_offline';
				}

				$row->published = $published;
				$row->store();

				if ($row->is_group_billing)
				{
					EventbookingHelperRegistration::updateGroupRegistrationRecord($row->id);
				}

				if ($row->published == 1)
				{
					$app->triggerEvent('onAfterPaymentSuccess', array($row));
				}

				EventbookingHelper::sendEmails($row, $config);

				return 1;
			}
			else
			{
				if ($row->is_group_billing)
				{
					EventbookingHelperRegistration::updateGroupRegistrationRecord($row->id);
					EventbookingHelperMail::sendWaitinglistEmail($row, $config);
				}

				return 2;
			}
		}
	}

	/**
	 * Process payment confirmation, update status of the registration records, sending emails...
	 *
	 * @param string $paymentMethod
	 */
	public function paymentConfirm($paymentMethod)
	{
		$method = EventbookingHelperRegistration::loadPaymentMethod($paymentMethod);
		$method->verifyPayment();
	}

	/**
	 * Process registration cancellation
	 *
	 * @return void
	 */
	public function cancelRegistration($id)
	{
		if (!$id)
		{
			return false;
		}

		$app    = JFactory::getApplication();
		$db     = JFactory::getDbo();
		$query  = $db->getQuery(true);
		$config = EventbookingHelper::getConfig();
		$row    = JTable::getInstance('EventBooking', 'Registrant');
		$row->load($id);

		if (!$row->id)
		{
			return false;
		}

		if ($row->published == 2)
		{
			return false;
		}

		$published = $row->published;

		//Trigger the cancellation
		JPluginHelper::importPlugin('eventbooking');
		$app->triggerEvent('onRegistrationCancel', array($row));
		$row->published = 2;
		$row->store();

		// Update status of group members record to cancelled as well
		if ($row->is_group_billing)
		{
			// We will need to set group members records to be cancelled
			$query->update('#__eb_registrants')
				->set('published=2')
				->where('group_id=' . (int) $row->id);
			$db->setQuery($query);
			$db->execute();
		}
		elseif ($row->group_id > 0)
		{
			$query->update('#__eb_registrants')
				->set('published=2')
				->where('group_id=' . (int) $row->group_id . ' OR id=' . $row->group_id);
			$db->setQuery($query);
			$db->execute();
		}

		EventbookingHelperMail::sendUserCancelRegistrationEmail($row, $config);

		if (in_array($published, [0, 1]))
		{
			EventbookingHelperMail::sendWaitingListNotificationEmail($row, $config);
		}
	}
}

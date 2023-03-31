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

class os_paypal extends RADPayment
{
	/**
	 * Constructor functions, init some parameter
	 *
	 * @param object $params
	 */
	public function __construct($params, $config = array())
	{
		parent::__construct($params, $config);

		$this->mode = $params->get('paypal_mode');

		if ($this->mode)
		{
			$this->url = 'https://www.paypal.com/cgi-bin/webscr';
		}
		else
		{
			$this->url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
		}

		$this->setParameter('business', $params->get('paypal_id'));
		$this->setParameter('rm', 2);
		$this->setParameter('cmd', '_xclick');
		$this->setParameter('no_shipping', 1);
		$this->setParameter('no_note', 1);
		$this->setParameter('charset', 'utf-8');
		$this->setParameter('tax', 0);

		$locale = $params->get('paypal_locale');

		if (empty($locale))
		{
			if (JLanguageMultilang::isEnabled())
			{
				$locale = JFactory::getLanguage()->getTag();
				$locale = str_replace('-', '_', $locale);
			}
			else
			{
				$locale = 'en_US';
			}
		}

		$this->setParameter('lc', $locale);
	}

	/**
	 * Process Payment
	 *
	 * @param object $row
	 * @param array  $data
	 */
	public function processPayment($row, $data)
	{
		$Itemid  = JFactory::getApplication()->input->getInt('Itemid', 0);
		$siteUrl = JUri::base();

		$event = EventbookingHelperDatabase::getEvent($row->event_id);

		if (strlen(trim($event->paypal_email)))
		{
			$this->setParameter('business', $event->paypal_email);
		}

		$this->setParameter('currency_code', $data['currency']);
		$this->setParameter('item_name', $data['item_name']);
		$this->setParameter('amount', round($data['amount'], 2));
		$this->setParameter('custom', $row->id);

		if ($row->process_deposit_payment)
		{
			if (JPluginHelper::isEnabled('system', 'cache'))
			{
				$this->setParameter('return', $siteUrl . 'index.php?option=com_eventbooking&view=payment&layout=complete&Itemid=' . $Itemid . '&pt=' . time());
			}
			else
			{
				$this->setParameter('return', $siteUrl . 'index.php?option=com_eventbooking&view=payment&layout=complete&Itemid=' . $Itemid);
			}
		}
		else
		{
			$this->setParameter('return', $siteUrl . 'index.php?option=com_eventbooking&view=complete&Itemid=' . $Itemid);
		}

		if ($row->state)
		{
			$config  = EventbookingHelper::getConfig();
			$country = $row->country ?: $config->default_country;
			$state   = EventbookingHelper::getStateCode($country, $row->state);
		}
		else
		{
			$state = '';
		}

		$this->setParameter('cancel_return', $siteUrl . 'index.php?option=com_eventbooking&view=cancel&layout=default&id=' . $row->id . '&Itemid=' . $Itemid);
		$this->setParameter('notify_url', $siteUrl . 'index.php?option=com_eventbooking&task=payment_confirm&payment_method=os_paypal');
		$this->setParameter('address1', $row->address);
		$this->setParameter('address2', $row->address2);
		$this->setParameter('city', $row->city);
		$this->setParameter('country', $data['country']);
		$this->setParameter('first_name', $row->first_name);
		$this->setParameter('last_name', $row->last_name);
		$this->setParameter('state', $state);
		$this->setParameter('zip', $row->zip);
		$this->setParameter('email', $row->email);

		$this->renderRedirectForm();
	}

	/**
	 * Verify payment
	 *
	 * @return bool
	 */
	public function verifyPayment()
	{
		$ret = $this->validate();

		if ($ret)
		{
			$id            = $this->notificationData['custom'];
			$transactionId = $this->notificationData['txn_id'];
			$amount        = $this->notificationData['mc_gross'];

			if ($amount < 0)
			{
				return false;
			}

			$row = JTable::getInstance('Registrant', 'EventbookingTable');

			if (!$row->load($id))
			{
				return false;
			}

			if ($row->published == 1 && $row->payment_status)
			{
				return false;
			}

			// Validate receiver account
			$event = EventbookingHelperDatabase::getEvent($row->event_id);

			if (strlen(trim($event->paypal_email)))
			{
				$payPalId = $event->paypal_email;
			}
			else
			{
				$payPalId = $this->params->get('paypal_id');
			}

			$receiverEmail = strtoupper($this->notificationData['receiver_email']);
			$receiverId    = strtoupper($this->notificationData['receiver_id']);
			$business      = strtoupper($this->notificationData['business']);
			$payPalId      = strtoupper($payPalId);

			if ($receiverEmail != $payPalId && $receiverId != $payPalId && $business != $payPalId)
			{
				return false;
			}

			// Validate currency
			$receivedPaymentCurrency = strtoupper($this->notificationData['mc_currency']);

			if ($receivedPaymentCurrency != strtoupper($row->payment_currency))
			{
				return false;
			}

			// Validate payment amount
			$receivedAmount = floatval($this->notificationData['mc_gross']);

			// Accept 0.05$ difference
			if (($row->payment_amount - $receivedAmount) > 0.05)
			{
				return false;
			}

			$this->onPaymentSuccess($row, $transactionId);

			return true;
		}

		return false;
	}

	/**
	 * Get list of supported currencies
	 *
	 * @return array
	 */
	public function getSupportedCurrencies()
	{
		return array(
			'AUD',
			'BRL',
			'CAD',
			'CZK',
			'DKK',
			'EUR',
			'HKD',
			'HUF',
			'ILS',
			'JPY',
			'MYR',
			'MXN',
			'NOK',
			'NZD',
			'PHP',
			'PLN',
			'GBP',
			'RUB',
			'SGD',
			'SEK',
			'CHF',
			'TWD',
			'THB',
			'TRY',
			'USD',
		);
	}

	/**
	 * Validate the post data from paypal to our server
	 *
	 * @return string
	 */
	protected function validate()
	{
		JLoader::register('PaypalIPN', JPATH_ROOT . '/components/com_eventbooking/payments/paypal/PayPalIPN.php');

		$ipn = new PaypalIPN;

		// Use sandbox URL if test mode is configured
		if (!$this->mode)
		{
			$ipn->useSandbox();
		}

		// Disable use custom certs
		$ipn->usePHPCerts();

		$this->notificationData = $_POST;

		try
		{
			$valid = $ipn->verifyIPN();
			$this->logGatewayData($ipn->getResponse());

			if (!$this->mode || $valid)
			{
				return true;
			}

			return false;
		}
		catch (Exception $e)
		{
			$this->logGatewayData($e->getMessage());

			return false;
		}
	}
}

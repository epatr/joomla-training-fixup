<?php

/**
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2018 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

// No direct access
defined('_JEXEC') or die;

require_once __DIR__ . '/wrapper.php';

class NR_SalesForce extends NR_Wrapper
{
	/**
	 *  Service API Endpoint
	 *
	 *  @var  string
	 */
	protected $endpoint = 'https://webto.salesforce.com/servlet/servlet.WebToLead?encoding=UTF-8';

	/**
	 *  Encode data before sending the request
	 *
	 *  @var  boolean
	 */
	protected $encode = false;

	/**
	 * Create a new instance
	 * @param string $organizationID Your SalesForce Organization ID
	 * @throws \Exception
	 */
	public function __construct($organizationID)
	{
		parent::__construct();
		$this->setKey($organizationID);
		$this->options->set('headers.Content-Type', 'application/x-www-form-urlencoded');
	}

	/**
	 *  Subscribe user to SalesForce
	 *
	 *  API References:
	 *  https://developer.salesforce.com/page/Wordpress-to-lead
	 *
	 *  @param   string   $email         	  User's email address
	 *  @param   array    $params  			  All the form fields
	 *
	 *  @return  void
	 */
	public function subscribe($email, $params)
	{
		$data = array(
			"email" => $email,
			"oid"   => $this->key
		);

		if (is_array($params) && count($params))
		{
			$data = array_merge($data, $params);
		}

		$this->post('', $data);

		return true;
	}

	/**
	 *  Determine if the Lead has been stored successfully in SalesForce
	 *
	 *  @return  string
	 */
	public function determineSuccess()
	{
		$status = $this->findHTTPStatus();

		if ($status < 200 && $status > 299)
		{
			return false;
		}

		$headers = $this->last_response['headers'];

		if (isset($headers['Is-Processed']) && (strpos($headers['Is-Processed'], 'Exception') !== false))
		{
			$this->last_error = JText::_('PLG_CONVERTFORMS_SALESFORCE_ERROR');
			return false;
		}

		return ($this->request_successful = true);
	}
}

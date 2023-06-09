<?php
/**
* @package		EasySocial
* @copyright	Copyright (C) 2010 - 2017 Stack Ideas Sdn Bhd. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* EasySocial is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/
defined('_JEXEC') or die('Unauthorized Access');

ES::import('fields:/user/boolean/boolean');

class SocialFieldsEventVideos extends SocialFieldsUserBoolean
{
	/**
	 * Displays the form when user tries to create a new group
	 *
	 * @since	1.4
	 * @access	public
	 */
	public function onRegister(&$post, &$session)
	{
		// We need to know if the app is published
		if (!$this->appEnabled(SOCIAL_APPS_GROUP_EVENT)) {
			return;
		}

		$value = $this->normalize($post, 'videos', $this->params->get('videos', $this->params->get('default', true)));
		$value = (bool) $value;

		// Detect if there's any errors
		$error = $session->getErrors($this->inputName);

		$this->set('error', $error);
		$this->set('value', $value);

		return $this->display();
	}

	/**
	 * Displays the output form when someone tries to edit a group.
	 *
	 * @since	1.4
	 * @access	public
	 */
	public function onEdit(&$data, &$event, $errors)
	{
		// We need to know if the app is published
		if (!$this->appEnabled(SOCIAL_APPS_GROUP_EVENT)) {
			return;
		}

		$params	= $event->getParams();
		$value = $event->getParams()->get('videos', $this->params->get('videos', $this->params->get('default', true)));
		$error = $this->getError($errors);

		$this->set('error', $error);
		$this->set('value', $value);

		return $this->display();
	}

	/**
	 * Executes after the group is created
	 *
	 * @since	1.0
	 * @access	public
	 */
	public function onEditBeforeSave(&$data, &$event)
	{
		// We need to know if the app is published
		if (!$this->appEnabled(SOCIAL_APPS_GROUP_EVENT)) {
			return;
		}

		// Get the posted value
		$value = $this->normalize($data, 'videos', $event->getParams()->get('videos', $this->params->get('default', true)));
		$value = (bool) $value;

		$event->params = $this->setParams($event, $value);
	}

	/**
	 * Executes after the group is created
	 *
	 * @since	1.0
	 * @access	public
	 */
	public function onRegisterBeforeSave(&$data, &$event)
	{
		// We need to know if the app is published
		if (!$this->appEnabled(SOCIAL_APPS_GROUP_EVENT)) {
			return;
		}

		$value = $this->normalize($data, 'videos', $this->params->get('videos', $this->params->get('default', true)));
		$value = (bool) $value;

		$event->params = $this->setParams($event, $value);
	}

	/**
	 * Given the value, set the params to the group
	 *
	 * @since	1.4
	 * @access	public
	 */
	public function setParams($event, $value)
	{
		$params = $event->getParams();
		$params->set('videos', $value);

		return $params->toString();
	}

	/**
	 * Override the parent's onDisplay
	 *
	 * @since	1.4
	 * @access	public
	 */
	public function onDisplay($event)
	{
		return;
	}
}

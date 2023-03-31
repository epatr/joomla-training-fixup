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

class SocialFieldsPageVideos extends SocialFieldsUserBoolean
{
	/**
	 * Displays the form when user tries to create a new page
	 *
	 * @since	2.0
	 * @access	public
	 */
	public function onRegister(&$post, &$session)
	{
		// We need to know if the app is published
		if (!$this->appEnabled(SOCIAL_APPS_GROUP_PAGE)) {
			return;
		}

		// Allow author modification during creation
		$allowModify = $this->params->get('allow_modification', true);

		if (!$allowModify) {
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
	 * Displays the output form when someone tries to edit a page.
	 *
	 * @since	2.0
	 * @access	public
	 */
	public function onEdit(&$data, &$page, $errors)
	{
		// We need to know if the app is published
		if (!$this->appEnabled(SOCIAL_APPS_GROUP_PAGE)) {
			return;
		}

		$params	= $page->getParams();
		$value = $page->getParams()->get('videos', $this->params->get('videos', $this->params->get('default', true)));
		$error = $this->getError($errors);

		$this->set('error', $error);
		$this->set('value', $value);

		return $this->display();
	}

	/**
	 * Executes after the page is created
	 *
	 * @since	1.0
	 * @access	public
	 */
	public function onEditBeforeSave(&$data, &$page)
	{
		// We need to know if the app is published
		if (!$this->appEnabled(SOCIAL_APPS_GROUP_PAGE)) {
			return;
		}

		// Get the posted value
		$value = $this->normalize($data, 'videos', $page->getParams()->get('videos', $this->params->get('default', true)));
		$value = (bool) $value;

		$page->params = $this->setParams($page, $value);
	}

	/**
	 * Executes after the page is created
	 *
	 * @since	1.0
	 * @access	public
	 */
	public function onRegisterBeforeSave(&$data, &$page)
	{
		// We need to know if the app is published
		if (!$this->appEnabled(SOCIAL_APPS_GROUP_PAGE)) {
			return;
		}
				
		$value = $this->normalize($data, 'videos', $this->params->get('videos', $this->params->get('default', true)));
		$value = (bool) $value;

		$page->params = $this->setParams($page, $value);
	}

	/**
	 * Given the value, set the params to the page
	 *
	 * @since	2.0
	 * @access	public
	 */
	public function setParams($page, $value)
	{
		$params = $page->getParams();
		$params->set('videos', $value);

		return $params->toString();
	}

	/**
	 * Override the parent's onDisplay
	 *
	 * @since	2.0
	 * @access	public
	 */
	public function onDisplay($page)
	{
		return;
	}
}
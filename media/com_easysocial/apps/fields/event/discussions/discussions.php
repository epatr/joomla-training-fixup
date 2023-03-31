<?php
/**
* @package      EasySocial
* @copyright    Copyright (C) 2010 - 2017 Stack Ideas Sdn Bhd. All rights reserved.
* @license      GNU/GPL, see LICENSE.php
* EasySocial is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/
defined('_JEXEC') or die('Unauthorized Access');

ES::import('fields:/user/boolean/boolean');

class SocialFieldsEventDiscussions extends SocialFieldsUserBoolean
{
	/**
	 * Displays the field for creation.
	 *
	 * @since   1.3
	 * @access  public
	 */
	public function onRegister(&$post, &$session)
	{
		// We need to know if the app is published
		if (!$this->appEnabled(SOCIAL_APPS_GROUP_EVENT)) {
			return;
		}

		$value = isset($post[$this->inputName]) ? $post[$this->inputName] : $this->params->get('default');

		// Set the value.
		$this->set('value', $this->escape($value));

		return $this->display();
	}

	/**
	 * Displays the field for edit.
	 *
	 * @since   1.3
	 * @access  public
	 */
	public function onEdit(&$post, &$cluster , $errors)
	{
		// We need to know if the app is published
		if (!$this->appEnabled(SOCIAL_APPS_GROUP_EVENT)) {
			return;
		}

		$params = $cluster->getParams();

		// Get the real value for this item
		$value = isset($post[$this->inputName]) ? $post[$this->inputName] : $params->get('discussions', $this->params->get('default'));

		// Get the error.
		$error = $this->getError($errors);

		// Set the value.
		$this->set('value', $this->escape($value));
		$this->set('error', $error);

		return $this->display();
	}

	/**
	 * Override the parent's onDisplay to not show this field.
	 *
	 * @since   1.3
	 * @access  public
	 */
	public function onDisplay($cluster)
	{
		return;
	}

	/**
	 * Executes before the event is created.
	 *
	 * @since   1.3
	 * @access  public
	 */
	public function onRegisterBeforeSave(&$post, &$cluster)
	{
		// We need to know if the app is published
		if (!$this->appEnabled(SOCIAL_APPS_GROUP_EVENT)) {
			return;
		}

		// Get the posted value
		$value = isset($post[$this->inputName]) ? $post[$this->inputName] : $this->params->get('default');
		$value = (bool) $value;

		$registry = $cluster->getParams();
		$registry->set('discussions' , $value);

		$cluster->params = $registry->toString();

		unset($post[$this->inputName]);
	}

	/**
	 * Executes before the event is saved.
	 *
	 * @since   1.3
	 * @access  public
	 */
	public function onEditBeforeSave(&$post, &$cluster)
	{
		// We need to know if the app is published
		if (!$this->appEnabled(SOCIAL_APPS_GROUP_EVENT)) {
			return;
		}
				
		// Get the posted value
		$value = isset($post[$this->inputName]) ? $post[$this->inputName] : $params->get('discussions', $this->params->get('default'));
		$value = (bool) $value;

		$registry = $cluster->getParams();
		$registry->set('discussions' , $value);

		$cluster->params = $registry->toString();

		unset($post[$this->inputName]);
	}
}

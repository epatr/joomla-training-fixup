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

class SocialFieldsGroupPhotos extends SocialFieldsUserBoolean
{
	/**
	 * Displays the output form when someone tries to create a group.
	 *
	 * @since	1.2
	 * @access	public
	 */
	public function onRegister(&$post, &$session)
	{
		// We need to know if the app is published
		if (!$this->appEnabled(SOCIAL_APPS_GROUP_GROUP)) {
			return;
		}

		// Allow author modification during creation
		$allowModify = $this->params->get('allow_modification', true);

		if (!$allowModify) {
			return;
		}

		// Get any previously submitted data
		$value = isset($post['photo_albums']) ? $post['photo_albums'] : $this->params->get('default', true);
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
	 * @since	1.2
	 * @access	public
	 */
	public function onEdit(&$data, &$group, $errors)
	{
		// We need to know if the app is published
		if (!$this->appEnabled(SOCIAL_APPS_GROUP_GROUP)) {
			return;
		}

		$params	= $group->getParams();
		$value 	= $group->getParams()->get( 'photo.albums' , $this->params->get('default', true) );
		$error	= $this->getError( $errors );

		$this->set( 'error'	, $error );
		$this->set( 'value' , $value );

		return $this->display();
	}

	/**
	 * Executes after the group is created
	 *
	 * @since	1.0
	 * @access	public
	 */
	public function onEditBeforeSave( &$data , &$group )
	{
		// We need to know if the app is published
		if (!$this->appEnabled(SOCIAL_APPS_GROUP_GROUP)) {
			return;
		}

		// Get the posted value
		$value 	= isset($data['photo_albums']) ? $data['photo_albums'] : $group->getParams()->get('photo.albums', $this->params->get('default', true));
		$value 	= (bool) $value;

		$registry	= $group->getParams();
		$registry->set('photo.albums', $value);

		$group->params	= $registry->toString();
	}

	/**
	 * Executes after the group is created
	 *
	 * @since	1.0
	 * @access	public
	 */
	public function onRegisterBeforeSave( &$data , &$group )
	{
		// We need to know if the app is published
		if (!$this->appEnabled(SOCIAL_APPS_GROUP_GROUP)) {
			return;
		}

		// Get the posted value
		$value 	= isset( $post[ 'photo_albums' ] ) ? $post[ 'photo_albums' ] : $this->params->get('default', true);
		$value 	= (bool) $value;

		$registry	= $group->getParams();
		$registry->set( 'photo.albums' , $value );

		$group->params	= $registry->toString();
	}

	/**
	 * Override the parent's onDisplay
	 *
	 * @since	1.2
	 * @access	public
	 */
	public function onDisplay($group)
	{
		return;
	}
}

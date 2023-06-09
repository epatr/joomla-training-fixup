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

ES::import('admin:/includes/fields/dependencies');

class SocialFieldsUserVmVendor extends SocialFieldItem
{
	/**
	 * Renders the custom field on the edit profile page
	 *
	 * @since	1.0
	 * @access	public
	 */
	public function onEdit(&$post, &$user, $errors)
	{
		// Get any errors on the form.
		$error = $this->getError($errors);

		$this->set('error', $error);

		return $this->display();
	}

	public function onEditValidate($post, $user)
	{
		$value = !empty($post[$this->inputName]) ? $post[$this->inputName] : '';

		return $this->validate($value);
	}

	public function onDisplay( $user )
	{
		$value		= $this->value;

		if( !$value )
		{
			return;
		}

		$value = FD::makeObject( $value );

		if( !$this->allowedPrivacy( $user ) )
		{
			return;
		}

		$field = $this->field;
		$options = array();

		$advGroups = array(SOCIAL_FIELDS_GROUP_GROUP, SOCIAL_FIELDS_GROUP_USER);

		$addAdvLink = in_array($field->type, $advGroups) && $field->searchable;

		foreach( $value as $v )
		{
			$option = FD::table( 'fieldoptions' );
			$option->load( array( 'parent_id' => $this->field->id, 'key' => 'items', 'value' => $v ) );

			if ($addAdvLink) {
				$params = array( 'layout' => 'advanced' );

				if ($field->type != SOCIAL_FIELDS_GROUP_USER) {
					$params['type'] = $field->type;
					$params['uid'] = $field->uid;
				}

				$params['criterias[]'] = $field->unique_key . '|' . $field->element;
				$params['operators[]'] = 'contain';
				$params['conditions[]'] = $v;

				$advsearchLink = FRoute::search($params);
				$option->advancedsearchlink = $advsearchLink;
			}

			$options[] = $option;
		}

		$this->set( 'options', $options );

		return $this->display();
	}

	public function validate($value)
	{
		$selected = FD::makeObject($value);

		if ($this->isRequired() && empty($selected)) {
			$this->setError(JText::_('PLG_FIELDS_MULTILIST_VALIDATION_PLEASE_SELECT_A_VALUE'));

			return false;
		}

		return true;
	}

	/**
	 * Checks if this field is complete.
	 *
	 * @since  1.2
	 * @access public
	 */
	public function onFieldCheck($user)
	{
		return $this->validate($this->value);
	}

	/**
	 * Trigger to get this field's value for various purposes.
	 *
	 * @since  1.2
	 * @access public
	 */
	public function onGetValue($user)
	{
		return $this->getValue();
	}

	/**
	 * Checks if this field is filled in.
	 *
	 * @since  1.3
	 * @access public
	 */
	public function onProfileCompleteCheck($user)
	{
		if (!FD::config()->get('user.completeprofile.strict') && !$this->isRequired()) {
			return true;
		}

		if (empty($this->value)) {
			return false;
		}

		$obj = FD::makeObject($this->value);

		if (empty($obj)) {
			return false;
		}

		return true;
	}
}

class SocialFieldsUserMultilistValue extends SocialFieldValue
{
	public function toString()
	{
		$values = array_values($this->value);

		return implode(', ', $values);
	}
}

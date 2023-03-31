<?php

class RADFormFieldHidden extends RADFormField
{
	/**
	 * The form field type.
	 *
	 * @var string
	 */
	protected $type = 'Hidden';

	/**
	 * Method to get the field input markup.
	 *
	 * @return string The field input markup.
	 */
	protected function getInput($bootstrapHelper = null)
	{
		$user = JFactory::getUser();
		$view = JFactory::getApplication()->input->getCmd('view');

		if ($user->authorise('eventbooking.registrantsmanagement', 'com_eventbooking') && $view == 'registrant')
		{
			$attributes = $this->buildAttributes();

			return '<input type="text" name="' . $this->name . '" id="' . $this->name . '" value="' . htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8') .
				'"' . $attributes . $this->row->extra_attributes . ' />';
		}
		else
		{
			return '<input type="hidden" name="' . $this->name . '" value="' . htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8') . '" class="eb-hidden-field" />';
		}
	}

	/**
	 * Get control group used to display on form
	 *
	 * @see RADFormField::getControlGroup()
	 */
	public function getControlGroup($bootstrapHelper = null, $controlId = null)
	{
		$user = JFactory::getUser();
		$view = JFactory::getApplication()->input->getCmd('view');

		if ($user->authorise('eventbooking.registrantsmanagement', 'com_eventbooking') && $view == 'registrant')
		{
			return parent::getControlGroup($bootstrapHelper, $controlId);
		}
		else
		{
			return $this->getInput($bootstrapHelper = null);
		}
	}

	/**
	 * Get output used for displaying on email and the detail page
	 *
	 * @see RADFormField::getOutput()
	 */
	public function getOutput($tableLess = true, $bootstrapHelper = null)
	{
		return '';
	}
}

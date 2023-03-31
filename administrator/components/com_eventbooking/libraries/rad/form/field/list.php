<?php

/**
 * Form Field class for the Joomla RAD.
 * Supports a generic list of options.
 *
 * @package     Joomla.RAD
 * @subpackage  Form
 */
class RADFormFieldList extends RADFormField
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 */
	protected $type = 'List';

	/**
	 * Method to instantiate the form field object.
	 *
	 * @param   JTable $row   the table object store form field definitions
	 * @param    mixed $value the initial value of the form field
	 */
	public function __construct($row, $value)
	{
		parent::__construct($row, $value);

		if ($row->multiple)
		{
			$this->attributes['multiple'] = true;
		}
	}

	/**
	 * Method to get the field input markup for a generic list.
	 * Use the multiple attribute to enable multiselect.
	 *
	 * @return  string  The field input markup.
	 */
	protected function getInput($bootstrapHelper = null)
	{
		// Get the field options.
		$options    = (array) $this->getOptions();
		$attributes = $this->buildAttributes();

		if ($this->row->multiple)
		{
			if (is_array($this->value))
			{
				$selectedOptions = $this->value;
			}
			elseif (strpos($this->value, "\r\n"))
			{
				$selectedOptions = explode("\r\n", $this->value);
			}
			elseif (is_string($this->value) && is_array(json_decode($this->value)))
			{
				$selectedOptions = json_decode($this->value);
			}
			else
			{
				$selectedOptions = array($this->value);
			}
		}
		else
		{
			$selectedOptions = $this->value;
		}

		return JHtml::_('select.genericlist', $options, $this->name . ($this->row->multiple ? '[]' : ''), trim($attributes . $this->row->extra_attributes),
			'value', 'text', $selectedOptions);
	}

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 */
	protected function getOptions()
	{
		$user = JFactory::getUser();

		$options   = array();
		$options[] = JHtml::_('select.option', '', JText::_('EB_SELECT'));

		if (is_array($this->row->values))
		{
			$values = $this->row->values;
		}
		elseif (strpos($this->row->values, "\r\n") !== false)
		{
			$values = explode("\r\n", $this->row->values);
		}
		else
		{
			$values = explode(",", $this->row->values);
		}

		$quantityValues = explode("\r\n", $this->row->quantity_values);

		if ($this->row->quantity_field && count($values) && count($quantityValues) && $this->eventId && !$user->authorise('eventbooking.registrantsmanagement', 'com_eventbooking'))
		{
			$multilingualValues = array();

			if (JLanguageMultilang::isEnabled())
			{
				$multilingualValues = RADFormField::getMultilingualOptions($this->row->id);
			}

			for ($i = 0, $n = count($values); $i < $n; $i++)
			{
				if (isset($quantityValues[$i]))
				{
					$optionQuantity                    = $quantityValues[$i];
					$quantityValues[trim($values[$i])] = $optionQuantity;
				}
				else
				{
					$quantityValues[trim($values[$i])] = 0;
				}
			}

			$values = EventbookingHelperHtml::getAvailableQuantityOptions($values, $quantityValues, $this->eventId, $this->row->id, false, $multilingualValues);
		}

		if (count($values) == 0)
		{
			JFactory::getApplication()->enqueueMessage('There is no available option left for the field ' . $this->title, 'warning');

			return $values;
		}

		$config = EventbookingHelper::getConfig();

		foreach ($values as $value)
		{
			$optionValue = trim($value);

			if (!$config->show_available_number_for_each_quantity_option || empty($quantityValues[$optionValue]))
			{
				$optionText = $optionValue;
			}
			else
			{
				$optionText = $optionValue . ' ' . JText::sprintf('EB_QUANTITY_OPTION_AVAILABLE', $quantityValues[$optionValue]);
			}

			$options[] = JHtml::_('select.option', $optionValue, $optionText);
		}
		
		return $options;
	}
}

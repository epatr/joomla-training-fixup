<?php

/**
 * Form Field class for the Joomla RAD.
 * Supports a checkbox list custom field.
 *
 * @package     Joomla.RAD
 * @subpackage  Form
 */
class RADFormFieldCheckboxes extends RADFormField
{
	/**
	 * The form field type.
	 *
	 * @var string
	 */
	protected $type = 'Checkboxes';

	/**
	 * Method to instantiate the form field object.
	 *
	 * @param JTable $row
	 *            the table object store form field definitions
	 * @param mixed  $value
	 *            initial value of the form field
	 */
	public function __construct($row, $value)
	{
		parent::__construct($row, $value);
	}

	/**
	 * Method to get the field input markup.
	 *
	 * @return string The field input markup.
	 */
	protected function getInput($bootstrapHelper = null)
	{
		$html       = array();
		$options    = (array) $this->getOptions();
		$attributes = $this->buildAttributes();

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

		$size = (int) $this->row->size;

		if (!$size)
		{
			$size = 1;
		}

		$span          = intval(12 / $size);
		$rowFluid      = $bootstrapHelper ? $bootstrapHelper->getClassMapping('row-fluid') : 'row-fluid';
		$spanClass     = $bootstrapHelper ? $bootstrapHelper->getClassMapping('span' . $span) : 'span' . $span;
		$html[]        = '<fieldset id="' . $this->name . '" class="' . $rowFluid . ' clearfix"' . '>';
		$html[]        = '<ul class="nav clearfix">';
		$i             = 0;
		$numberOptions = count($options);

		foreach ($options as $optionValue => $optionText)
		{
			$i++;
			$checked     = in_array($optionValue, $selectedOptions) ? 'checked' : '';
			$html[]      = '<li class="' . $spanClass . '">';
			$html[]      = '<label for="' . $this->name . $i . '" ><input type="checkbox" id="' . $this->name . $i . '" name="' . $this->name . '[]" value="' .
				htmlspecialchars($optionValue, ENT_COMPAT, 'UTF-8') . '"' . $checked . $attributes . $this->row->extra_attributes . '/> ' . $optionText .
				'</label>';
			$html[]      = '</li>';

			if ($i % $size == 0 && $i < $numberOptions)
			{
				$html[] = '</ul>';
				$html[] = '<ul class="nav clearfix">';
			}
		}

		$html[] = '</ul>';

		// End the checkbox field output.
		$html[] = '</fieldset>';

		return implode($html);
	}

	protected function getOptions()
	{
		$user = JFactory::getUser();

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
			$values = array($this->row->values);
		}

		$quantityValues = explode("\r\n", $this->row->quantity_values);

		if ($this->row->quantity_field && count($values) && count($quantityValues) && $this->eventId && !$user->authorise('eventbooking.registrantsmanagement', 'com_eventbooking'))
		{
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

			$values = EventbookingHelperHtml::getAvailableQuantityOptions($values, $quantityValues, $this->eventId, $this->row->id, true);
		}

		$config  = EventbookingHelper::getConfig();
		$options = [];

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

			$options[$optionValue] = $optionText;
		}

		return $options;
	}
}

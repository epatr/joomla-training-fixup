<?php
/**
 * Form Field class for the Joomla RAD.
 * Supports a text input.
 *
 * @package     Joomla.RAD
 * @subpackage  Form
 */
class RADFormFieldNumber extends RADFormFieldText
{
	/**
	 * Field Type
	 *
	 * @var string
	 */
	protected $type = 'Number';

	/**
	 * Method to instantiate the form field object.
	 *
	 * @param   JTable $row   the table object store form field definitions
	 * @param    mixed $value the initial value of the form field
	 */
	public function __construct($row, $value = null)
	{
		parent::__construct($row, $value);

		if ($row->min)
		{
			$this->attributes['min'] = $row->min;
		}

		if ($row->max)
		{
			$this->attributes['max'] = $row->max;
		}

		if ($row->step)
		{
			$this->attributes['step'] = $row->step;
		}
	}
}

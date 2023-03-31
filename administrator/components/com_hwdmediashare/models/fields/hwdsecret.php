<?php
/**
 * @package     Joomla.administrator
 * @subpackage  Component.hwdmediashare
 *
 * @copyright   Copyright (C) 2013 Highwood Design Limited. All rights reserved.
 * @license     GNU General Public License http://www.gnu.org/copyleft/gpl.html
 * @author      Dave Horsfall
 */

defined('_JEXEC') or die;

class JFormFieldHwdSecret extends JFormField
{
	/**
	 * The name of the form field type.
         * 
         * @access  protected
	 * @var     string
	 */
 	protected $type = 'Secret';

	/**
	 * The name of the form field.
         * 
         * @access  protected
	 * @var     string
	 */
 	protected $name = 'secret';

	/**
	 * The id of the form field.
         * 
         * @access  protected
	 * @var     string
	 */
 	protected $id = 'secret';

	/**
	 * Method to get the field input markup.
	 *
	 * @access  public
	 * @return  string  The field input markup.
	 */
        public function getInput()
        {
		$allowClear = ((string) $this->element['clear'] != 'false') ? true : false;

		// Load language.
		JFactory::getLanguage()->load('com_hwdmediashare', JPATH_ADMINISTRATOR);

		// Build the script.
		$script = array();
                $script[] = '	function jRefreshSecret(id) {';
                $script[] = '		var m = Math.random().toString(36).slice(-10);';
                $script[] = '		document.getElementById(id + "_id").value = m;';
                $script[] = '		document.getElementById(id + "_name").innerHTML = m;';
                $script[] = '		return false;';
                $script[] = '	}';

		// Add the script to the document head.
		JFactory::getDocument()->addScriptDeclaration(implode("\n", $script));

		if (empty($this->value))
		{
                        $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
                        $string = '';
                        for ($i = 0; $i < 10; $i++)
                        {
                                $string .= $characters[rand(0, strlen($characters) - 1)];
                        }
			$this->value = $string;
		}

		// The current display field.
		$html[] = '<span class="input-append">';
		$html[] = '<span class="input-medium uneditable-input" id="'.$this->id.'_name">'.$this->value.'</span>';
		$html[] = '<button id="'.$this->id.'_refresh" class="btn hasTooltip" title="'.JHtml::tooltipText('COM_HWDMS_REFRESH_SECRET').'" onclick="return jRefreshSecret(\''.$this->id.'\')"><i class="icon-refresh"></i></button>';
		$html[] = '</span>';
		$html[] = '<input type="hidden" id="'.$this->id.'_id" name="'.$this->name.'" value="'.$this->value.'" />';

		return implode("\n", $html);
        }
}

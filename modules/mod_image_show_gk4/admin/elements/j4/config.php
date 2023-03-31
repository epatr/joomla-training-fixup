<?php

defined('JPATH_BASE') or die;

class JFormFieldConfig extends JFormField {
	protected $type = 'Config';

	protected function getLabel() {
		return '';
	}

	protected function getInput() {
		JHtml::_('behavior.modal', 'a.modal');
		$catalog_path = JPATH_SITE.DS.'modules'.DS.'mod_image_show_gk4'.DS.'styles';
		
		$folders = JFolder::folders($catalog_path);
		$options = array();
		
		$final_output = '';
		
		if(count($folders) > 0) {
			foreach($folders as $folder) {
				$output = '';
				// read XML file 
				 
				//$xml=JFactory::getXML($catalog_path.DS.$folder.DS.'info.xml'); 

				$xml=simplexml_load_file($catalog_path.DS.$folder.DS.'info.xml'); // compatible joomla 4

				//
				foreach($xml->config[0]->field as $field) {
					$type = $field->attributes()->type;
					
					$output .= '<div class="control-group">' . $this->generateField($type, $field, $folder) . '</div>';
				}
				//
				$final_output .= '<div id="module_style_'.$folder.'" class="module_style"><div class="adminformlist">' . $output . '</div></div>';
			}
		} else {
			$final_output = 'Module have no styles. Please install some style package.';
		}
		
		$option_value = str_replace("\'", "'", $this->value);
		$option_value = str_replace(array('{\"', '\":', ':\"', '\",', ',\"', '\"}'), '"', $option_value);
		$final_output .= '<textarea name="'.$this->name.'" id="'.$this->id.'" rows="20" cols="50">'.$option_value.'</textarea>';
		
		return $final_output;
	}
	//
	protected function generateField($type, $field, $style) {
		$id = $style . '_' . $field->attributes()->name;
		
		switch($type) {
			case 'text': {
			 		// 
			 		$output = '<div class="control-label"><label id="'.$id.'-lbl" for="'.$id.'" class="hasTip" title="'.$field->attributes()->desc.'">'.$field->attributes()->label.'</label></div>';
			 		
			 		$unit_span = '';
			 		if($field->attributes()->unit != '') {
			 			$unit_span = '<span class="input-group-addon">'.$field->attributes()->unit.'</span>';
			 		}
			 		$output .= '<div class="controls"><div class="input-group"><input type="text" id="'.$id.'" value="'.$field->attributes()->default.'" class="form-control field">' . $unit_span . '</div></div>';
			 		// 
			 		return $output;
				}
				break;
			case 'switch': {
					//
					$output = '<div class="control-label"><label id="'.$id.'-lbl" for="'.$id.'" class="hasTip" title="'.$field->attributes()->desc.'">'.$field->attributes()->label.'</label></div>';
					
					$output .= '<div class="controls"><select id="'.$id.'" class="form-control custom-select input-medium field '.$field->attributes()->class.'">';
					
					$selected0 = '';
					$selected1 = '';
						
					if($field->attributes()->default == 0) {
						$selected0 = ' selected="selected"';
					} else {
						$selected1 = ' selected="selected"';
					}
						
					$output .= '<option value="0" '.$selected0.'>'.JText::_('MOD_IMAGE_SHOW_DISABLED').'</option>';
					$output .= '<option value="1" '.$selected1.'>'.JText::_('MOD_IMAGE_SHOW_ENABLED').'</option>';
					
					$output .= '</select></div>';
					//
					return $output;
				}
				break;
			case 'textarea': {
			 		$output = '<div class="control-label"><label id="'.$id.'-lbl" for="'.$id.'" class="hasTip" title="'.$field->attributes()->desc.'">'.$field->attributes()->label.'</label></div>';
			 		$output .= '<div class="controls"><textarea id="'.$id.'" class="form-control field '.$field->attributes()->class.'" rows="'.$field->attributes()->rows.'" cols="'.$field->attributes()->cols.'"></textarea></div>';
			 		
			 		return $output;
				}
				break;
			case 'select': {
					$output = '<div class="control-label"><label id="'.$id.'-lbl" for="'.$id.'" class="hasTip" title="'.$field->attributes()->desc.'">'.$field->attributes()->label.'</label></div>';
					
					$output .= '<div class="controls"><select id="'.$id.'" class="form-control custom-select input-medium field '.$field->attributes()->class.'">';
					
					foreach($field->option as $option) {
						$selected = '';
						
						if($option->attributes()->value == $field->attributes()->value) {
							$selected = ' selected="selected"';
						}
						
						$output .= '<option value="'.$option->attributes()->value.'" '.$selected.'>'.$option.'</option>';
					}
					
					$output .= '</select></div>';
					//
					return $output;
				}
				break;
			default: 
				return ''; 
				break;
		}
	}
}

/* eof */

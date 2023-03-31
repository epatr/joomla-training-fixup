<?php
/**
 * @package     UltimateUser for Joomla!
 * @author      Stéphane Bouey <stephane.bouey@faboba.com> - http://www.faboba.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @copyright   Copyright (C) 2012-2013. All rights reserved.
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');

class plgAcymailingUltimateUser extends JPlugin
{
	function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);

	}

	//This function will enable you to display a new tab in the tag interface (when you click Newsletter->create->tags)
	//If you don't want an interface on the tag system, just remove this function, this is not mandatory
	function acymailing_getPluginType(){
		$app = JFactory::getApplication();
		if($this->params->get('frontendaccess') == 'none' && !$app->isAdmin()) return;

		$onePlugin = new stdClass();
		$onePlugin->name = 'UltimateUser';
		$onePlugin->function = 'acymailingultimateuser_show';
		$onePlugin->help = 'plugin-ultimateuser';

		return $onePlugin;
	}


	function acymailingultimateuser_show(){
		?>
		<script language="javascript" type="text/javascript">
            <!--
            function applyTag(tagname){
                var string = '{ultimateuser:' + tagname;
                for(var i = 0, len = document.adminForm.typeinfo.length; i < len; i++){
                    if(document.adminForm.typeinfo[i].checked){
                        string += '|info:' + document.adminForm.typeinfo[i].value;
                    }
                }
                string += '}';
                setTag(string);
                insertTag();
            }
            -->
		</script>
		<input type="radio" name="typeinfo" id="receiverinfo" checked="checked" value="receiver"/><label for="receiverinfo"><?php echo JText::_('RECEIVER_INFORMATION'); ?></label>
		<input type="radio" name="typeinfo" id="senderinfo" value="sender"/><label for="senderinfo"><?php echo JText::_('SENDER_INFORMATIONS'); ?></label>
		<?php

		$text = '<table class="adminlist table table-striped table-hover" cellpadding="1">';

		// Get custom fields
		$db = JFactory::getDBO();
		$query = 'SELECT fieldcode,name, type, description
			FROM #__uu_fields
			WHERE published = "1" AND type != "group"' ;
		$db->setQuery($query);
		$fields = $db->loadObjectList();

		if(empty($fields)){
			return;
		}

		$k = 0;
		foreach($fields as $field){
			$text .= "	<tr style=\"cursor:pointer\" class=\"row$k\" onclick=\"applyTag( '$field->fieldcode|type:$field->type' );\">";
			$text .= "		<td class=\"acytdcheckbox\"></td>";
			$text .= "		<td>$field->name</td>";
			$text .= "		<td>$field->description</td>";
			$text .= "	</tr>";
			$k = 1 - $k;
		}

		$text .= '</table>';
		echo $text;
	}


	function acymailing_replaceusertags(&$email, &$user, $send = true){

		$match = '#(?:{|%7B)ultimateuser:(.*)(?:}|%7D)#Ui';
		$variables = array('subject', 'body', 'altbody');
		$found = false;
		foreach($variables as $var){
			if(empty($email->$var)){
				continue;
			}
			$found = preg_match_all($match, $email->$var, $results[$var]) || $found;
			// We unset the results so that we won't handle it later... it will save some memory and processing
			if(empty($results[$var][0])){
				unset($results[$var]);
			}
		}

		// If we did not find anything...
		if(!$found){
			return;
		}

		// We get the list of existing fields
		$db = JFactory::getDBO();
		$db->setQuery('SELECT fieldcode FROM #__uu_fields WHERE type != "group"');

		// Joomla 1.6+?
		if(ACYMAILING_J16){
			$allowedFields = $db->loadColumn();
			// Joomla 1.5
		}else{
			$allowedFields = $db->loadObjectList();
			foreach($allowedFields as $key => $value){
				$allowedFields[$key] = $value->name;
			}
		}

		// No fields found?
		if(empty($allowedFields)){
			return;
		}

		$tags = array();
		$sendervalues = array();
		$receivervalues = new stdClass();
		foreach($results as $var => $allresults){
			foreach($allresults[0] as $i => $oneTag){

				// No need to process twice a tag we already have!
				if(isset($tags[$oneTag])){
					continue;
				}

				// We explode each argument of the tag
				$arguments = explode('|', $allresults[1][$i]);
				$field = $arguments[0];
				unset($arguments[0]);

				// Non-allowed field?
				if(!in_array($field, $allowedFields)){
					continue;
				}

				// We get the default value with the params plugin
				$mytag = new stdClass();
				$mytag->default = $this->params->get("default_$field", '');

				// We clean the arguments
				if(!empty($arguments)){
					foreach($arguments as $onearg){
						$args = explode(':', $onearg);
						if(isset($args[1])){
							$mytag->{$args[0]} = $args[1];
						}else{
							$mytag->{$args[0]} = 1;
						}
					}
				}

				// We retrieve the content of the tag
				$values = new stdClass();

				// Do we have to use the sender information?
				if(!empty($mytag->info) && $mytag->info == 'sender'){
					if(empty($this->sendervalues[$email->mailid]) && !empty($email->userid)){
						$db->setQuery("SELECT * FROM #__uu_users WHERE user_id = '$email->userid' LIMIT 1");
						$this->sendervalues[$email->mailid] = $db->loadObject();
					}
					if(!empty($this->sendervalues[$email->mailid])){
						$values = $this->sendervalues[$email->mailid];
					}
					// We use the receiver information
				}else{
					if(empty($receivervalues->user_id) && !empty($user->userid)){
						$db->setQuery("SELECT * FROM #__uu_users WHERE user_id = '$user->userid' LIMIT 1");
						$receivervalues = $db->loadObject();
					}
					if(!empty($receivervalues->user_id)){
						$values = $receivervalues;
					}
				}

				// If there is no content, we use the default value
				$replaceme = isset($values->$field) ? $values->$field : $mytag->default;

				// Use a special format for some data types
				if(!empty($mytag->type)){
					switch($mytag->type){
						case 'date':
							if(empty($mytag->format)){
								$mytag->format = JText::_('DATE_FORMAT_LC3');
							}
							$replaceme = acymailing_getDate(acymailing_getTime($replaceme), $mytag->format);
							break;
						case 'radio':
						case 'select':
						case 'checkbox':
							$query = $db->getQuery(true);
							$query->select('ufv.title')
								->from('#__uu_fields_values ufv')
								->join('INNER','#__uu_fields uf ON  uf.id = ufv.id_field')
								->where('uf.fieldcode = '.$db->quote($field))
								->where('ufv.value = '.$db->quote($values->$field));
							$db->setQuery($query);
						    $title = $db->loadResult();
							if (!empty($title)){$replaceme = JText::_($title);}
							break;
						case 'country':
							$country = $this->findCountryByCode($values->$field);
							if (!empty($country)){$replaceme = $country;};
						default:
							break;
					}
				}

				// Do we have to split the content into parts?
				if(!empty($mytag->part)){
					$parts = explode(' ', $replaceme);
					if($mytag->part == 'last'){
						$replaceme = count($parts) > 1 ? end($parts) : '';
					}else{
						$replaceme = reset($parts);
					}
				}

				// Some other parameters
				if(!empty($mytag->lower)) $replaceme = strtolower($replaceme);
				if(!empty($mytag->ucwords)) $replaceme = ucwords($replaceme);
				if(!empty($mytag->ucfirst)) $replaceme = ucfirst($replaceme);
				if(!empty($mytag->urlencode)) $replaceme = urlencode($replaceme);

				// Alternative template
				if(file_exists(ACYMAILING_MEDIA.'plugins'.DS.'ultimateuserfield.php')){
					ob_start();
					require ACYMAILING_MEDIA.'plugins'.DS.'ultimateuserfield.php';
					$oneElement = ob_get_clean();
				}

				$tags[$oneTag] = $replaceme;
			}
		}

		// We now replace the tags
		foreach($results as $var => $allresults){
			$email->$var = str_replace(array_keys($tags), $tags, $email->$var);
		}
	}//endfct

	function findCountryByCode($countryCode){
		//get language code
		$langTag = JFactory::getLanguage()->getTag();

		jimport( 'joomla.filesystem.file' );

		//load first a specific country language files
		$file	= JPATH_ROOT .'/components/com_uu/libraries/fields/countries_'.$langTag.'.xml';
		//detect language and set it in the country list.
		if( !JFile::exists( $file ) )
		{
			//default country list file
			$file = JPATH_ROOT .'/components/com_uu/libraries/fields/countries.xml';
		}

		$contents	= JFile::read( $file );
		$parser		= new SimpleXMLElement($file,NULL,true);
		$document	= $parser->document;
		$countries		= $parser->countries;

		for($a=0;$a<count($countries->country);$a++ ) {
			$code = $countries->country[$a]->code;
			$name = $countries->country[$a]->name;

			if ($code == $countryCode){return $name;}

		}

		//if not found return empty country
		return '';

	}

	function onAcyDisplayFilters(&$type, $context = 'massactions'){
		if($this->params->get('displayfilter_'.$context, true) == false){
			return;
		}

		$fields = acymailing_getColumns('#__uu_users');
		if(empty($fields)) return;

		$type['ultimateuserfield'] = 'UltimateUser';

		$field = array();
		foreach($fields as $oneField => $fieldType){
			$field[] = JHTML::_('select.option', $oneField, $oneField);
		}

		$operators = acymailing_get('type.operators');
		$operators->extra = 'onchange="countresults(__num__)"';

		$return = '<div id="filter__num__ultimateuserfield">';
		$return .= JHTML::_('select.genericlist', $field, "filter[__num__][ultimateuserfield][map]", 'class="inputbox" size="1" onchange="countresults(__num__)"', 'value', 'text');
		$return .= ' '.$operators->display("filter[__num__][ultimateuserfield][operator]");
		$return .= ' <input onchange="countresults(__num__)" class="inputbox" type="text" name="filter[__num__][ultimateuserfield][value]" style="width:200px" value=""></div>';

		return $return;
	}

	function onAcyProcessFilterCount_ultimateuserfield(&$query, $filter, $num){
		$this->onAcyProcessFilter_ultimateuserfield($query, $filter, $num);
		return JText::sprintf('SELECTED_USERS', $query->count());
	}


	function onAcyProcessFilter_ultimateuserfield(&$query, $filter, $num){
		$query->leftjoin['ultimateuseruser'] = '#__uu_users AS uu ON uu.user_id = sub.userid';
		$query->where[] = $query->convertQuery('ultimateuseruser', $filter['map'], $filter['operator'], $filter['value']);
	}

}
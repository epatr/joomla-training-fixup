<?php
/*
 *
 * @package		ARI Framework
 * @author		ARI Soft
 * @copyright	Copyright (c) 2011 www.ari-soft.com. All rights reserved
 * @license		GNU/GPL (http://www.gnu.org/copyleft/gpl.html)
 * 
 */

defined('_JEXEC') or die ('Restricted access');

require_once JPATH_ADMINISTRATOR . '/components/com_ariquiz/kernel/class.AriKernel.php';

AriKernel::import('Xml.XmlHelper');

class JElementUsergroups extends JElement
{
	var	$_name = 'Usergroups';
	
	function fetchElement($name, $value, &$node, $control_name)
	{
		$size = intval(AriXmlHelper::getAttribute($node, 'size'), 10);
		$multiple = (bool)AriXmlHelper::getAttribute($node, 'multiple');
		$rootGroup = AriXmlHelper::getAttribute($node, 'root_group');
		if (is_null($rootGroup))
			$rootGroup = 'USERS'; 

		$groupTree = array();
		if (J1_5)
		{
			$acl = JFactory::getAcl();
			$groupTree = $acl->get_group_children_tree(null, $rootGroup, true);
			
			$guest_label = AriXmlHelper::getAttribute($node, 'guest_label');
			if ($guest_label)
			{
				$guestItem = new stdClass();
				$guestItem->value = 0;
				$guestItem->text = JText::_($guest_label);
				
				array_unshift($groupTree, $guestItem);
			}
		}
		else
		{
			$groupTree = array();
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select('a.id AS value, a.title AS text, COUNT(DISTINCT b.id) AS level');
			$query->from('#__usergroups AS a');
			$query->join('LEFT', '#__usergroups AS b ON a.lft > b.lft AND a.rgt < b.rgt');
			$query->group('a.id, a.title, a.lft, a.rgt');
			$query->order('a.lft ASC');
			$db->setQuery($query);
			$groupTree = $db->loadObjectList();

			// Check for a database error.
			if ($db->getErrorNum())
			{
				JError::raiseNotice(500, $db->getErrorMsg());
				return null;
			}

			for ($i = 0, $n = count($groupTree); $i < $n; $i++)
			{
				$groupTree[$i]->text = str_repeat('- ', $groupTree[$i]->level) . $groupTree[$i]->text;
			} 
		}
		
		return JHTML::_(
			'select.genericlist', 
			$groupTree, 
			$control_name . '[' . $name . ']' . ($multiple ? '[]' : ''), 
			'class="inputbox"' . ($multiple ? ' multiple="multiple"' : '') . ($size ? ' size="' . $size . '"' : ''), 
			'value', 
			'text', 
			$value,
			$control_name . $name);	
	}
}
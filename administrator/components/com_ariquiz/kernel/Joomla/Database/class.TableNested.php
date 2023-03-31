<?php
/*
 *
 * @package		ARI Framework
 * @author		ARI Soft
 * @copyright	Copyright (c) 2011 www.ari-soft.com. All rights reserved
 * @license		GNU/GPL (http://www.gnu.org/copyleft/gpl.html)
 * 
 */

(defined('_JEXEC') && defined('ARI_FRAMEWORK_LOADED')) or die;

if (J3_3)
	require_once dirname(__FILE__) . '/j33/aritablenested.php';
else if (J1_5)
	require_once dirname(__FILE__) . '/j15/aritablenested.php';
else
	require_once dirname(__FILE__) . '/j16_32/aritablenested.php';

define('ARI_NESTEDTABLE_ROOT', 'root');
	
class AriTableNested extends AriTableNestedInt
{
	var $id = null;
	var $title = null;
	var $access = 1;
	var $path = null;

	function addRoot()
	{
		$rootId = $this->getRootId();
		if ($rootId !== false)
			return $rootId;

    	$db = $this->getDBO();
    	$query = 'INSERT INTO ' . $this->getTableName()
	        . ' SET parent_id = 0'
	        . ', lft = 0'
	        . ', rgt = 0'
	        . ', level = 0'
	        . ', title = ' . $db->quote(ARI_NESTEDTABLE_ROOT)
	        . ', alias = ' . $db->quote(ARI_NESTEDTABLE_ROOT)
	        . ', access = 1'
	        . ', path = '.$db->quote('');
    	$db->setQuery($query);
    	$db->query();

    	return $db->insertid();
	}
}
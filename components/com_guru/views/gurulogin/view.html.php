<?php
/*------------------------------------------------------------------------
# com_guru
# ------------------------------------------------------------------------
# author    iJoomla
# copyright Copyright (C) 2013 ijoomla.com. All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Websites: http://www.ijoomla.com
# Technical Support:  Forum - http://www.ijoomla.com.com/forum/index/
-------------------------------------------------------------------------*/

defined( '_JEXEC' ) or die( 'Restricted access' );

jimport ("joomla.application.component.view");

class guruViewguruLogin extends JViewLegacy {

	function display($tpl = null){
		parent::display($tpl);
	}
	
	function editForm($tpl = null) {
	
		$db = JFactory::getDBO();
		$sql = "select * from #__guru_config";
		$db->setQuery($sql);
		$db->execute();
		$configs = $db->loadAssocList();
		$this->configs = $configs;
		
			
		parent::display($tpl);
	}
	
	function terms($tpl = null) {
		$db = JFactory::getDBO();
		$sql = "select * from #__guru_config";
		$db->setQuery($sql);
		$db->execute();
		$configs = $db->loadAssocList();
		$this->configs = $configs;
		
		parent::display($tpl);
	}
	
}

?>
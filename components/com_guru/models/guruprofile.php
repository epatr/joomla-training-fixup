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

jimport ("joomla.aplication.component.model");


class guruModelguruProfile extends JModelLegacy {
	
	function __construct () {
		parent::__construct();		
	}
	
	function encriptPassword($password){
		$salt = "";
		for($i=0; $i<=32; $i++) {
			$d = rand(1,30)%2;
		  	$salt .= $d ? chr(rand(65,90)) : chr(rand(48,57));
	   	}		
		$hashed = md5($password.$salt);
		$encrypted = $hashed.':'.$salt;
		return $encrypted;
	}

	function store(){		
		jimport("joomla.database.table.user");
		$my = JFactory::getUser();
		$new_user = "0";
		
		if(!$my->id){
			$new_user = 1;
		}
		else{
			$new_user = 0;
		}	
		
		$data = JFactory::getApplication()->input->post->getArray();
		$id = JFactory::getApplication()->input->get("id", "0", "raw");
		$db = JFactory::getDBO();
		$returnpage = JFactory::getApplication()->input->get("returnpage", "", "raw");
		
		if(intval($id) != 0){
			// user alreadi logged, make sure that user id is the same with the logged user
			$user_logged = JFactory::getUser();
			$id = intval($user_logged->id);
			
			if(intval($id) == 0){
				return false;
			}
		}
		
		if($returnpage != "checkout"){
			if(trim($data["password"]) != ""){
				$password = trim($data["password"]);
				$password = $this->encriptPassword($password);
				$sql = "update #__users set password='".trim($password)."' where id=".intval($id);
				$db->setQuery($sql);
				$db->execute();
				$user = new JUser();
				$user->bind($data);
				$user->gid = 18;
				if(!$user->save()){
					$reg = JSession::getInstance("none", array());
					$reg->set("tmp_profile", $data);
					$error = $user->getError();
					$res = false;
				}
			}
						
			$data['name'] = $data['firstname'];
			$res = true;
		}
		
		$first_name = JFactory::getApplication()->input->get("firstname", "", "raw");
		$last_name = JFactory::getApplication()->input->get("lastname", "", "raw");
		$company = JFactory::getApplication()->input->get("company", "", "raw");
		$image = JFactory::getApplication()->input->get("image", "", "raw");
		
		if(!$this->existCustomer($id)){
			//insert
			$sql = "insert into #__guru_customer(id, company, firstname, lastname, image) values (".intval($id).", '".addslashes(trim($company))."', '".addslashes(trim($first_name))."', '".addslashes(trim($last_name))."', '".addslashes(trim($image))."')";
		}
		else{
			//update
			$sql = "update #__guru_customer set company='".addslashes(trim($company))."', firstname='".addslashes(trim($first_name))."', lastname='".addslashes(trim($last_name))."', image='".addslashes(trim($image))."' where id=".intval($id);
		}		
		$db->setQuery($sql);
		if($db->execute()){
			return true;
		}
		return false;
	}
	
	function existCustomer($id){
		$db = JFactory::getDBO();
		$sql = "select count(*) from #__guru_customer where id=".intval($id);
		$db->setQuery($sql);
		$db->execute();
		$result = $db->loadResult();
		if($result == "0"){
			return false;
		}
		return true;
	}
};
?>
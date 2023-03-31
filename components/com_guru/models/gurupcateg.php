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

class guruModelguruPcateg extends JModelList {
	protected $_context = 'com_guru.gurupcategs';
	var $_promos;
	var $_promo;
	var $_id = null;
	var $_total = null;

	function __construct () {
		parent::__construct();
		
		$cids = JFactory::getApplication()->input->get('cid', 0, "raw");
		$itemid = JFactory::getApplication()->input->get('Itemid', 0, "raw");
		
		if(($cids == 0) && (isset($itemid) && ($itemid != 0))){
			$db = JFactory::getDBO();	
			$sql = "SELECT params from #__menu where id=".intval($itemid);
			$db->setQuery($sql);
			$db->execute();
			$params = $db->loadColumn();
			$params = $params["0"];
			$params = json_decode($params);
			
			if(isset($params->cid) && ($params->cid != "")){
				$cids = $params->cid;
				JFactory::getApplication()->input->set("cid", $cids);
			}
		}
		
		$this->setId((int)$cids);
	}


	function setId($id) {
		$this->_id = $id;
		$this->_promo = null;
	}

	function getlistPcategs(){
		$lang = JFactory::getApplication()->input->get("lang", "", "raw");

	    if(strpos($lang, "-") !== false){
	        $lang = explode("-", $lang);
	        $lang = $lang["0"];
	    }

		if(empty($this->_promos)){
			$query = "SELECT c.*, count( parent_id ) AS copii FROM #__guru_category c LEFT OUTER JOIN #__guru_categoryrel r ON c.id = r.child_id WHERE c.id IN (select child_id from #__guru_categoryrel) and r.parent_id = 0 and c.published=1 AND (c.language='' OR c.language='*' OR c.language='".$lang."') GROUP BY c.id, c.name, c.alias, c.published, c.description, c.image, c.ordering, c.icon, c.groups order by c.ordering asc";
			$this->_promos = $this->_getList($query);
		}

		$return_groups = array();
		$user = JFactory::getUser();

		for($i=0; $i<count($this->_promos); $i++){
			if(trim($this->_promos[$i]->image) != ""){
				$path=explode("/",$this->_promos[$i]->image);
				$this->_promos[$i]->imageName=$path[count($path)-1];
			}

			if(isset($this->_promos[$i]->groups) && trim($this->_promos[$i]->groups) != ""){
				if(intval($user->id) == 0){
					$acl_groups = json_decode(trim($this->_promos[$i]->groups), true);
					if(in_array("1", $acl_groups) || in_array("9", $acl_groups)){ // Public or Guest
						$return_groups[] = $this->_promos[$i];
					}
				}
				else{
					// user logged and category ACL added
					$user_groups = $user->groups;
					$acl_groups = json_decode(trim($this->_promos[$i]->groups), true);
					$intersect = array_intersect($user_groups, $acl_groups);

					if(isset($intersect) && is_array($intersect) && count($intersect) > 0){
						$return_groups[] = $this->_promos[$i];
					}
				}
			}
			else{
				$return_groups[] = $this->_promos[$i];
			}
		}

		$this->_promos = $return_groups;

		return $this->_promos;
	}	
	
	function getchildren(){
		$cid = JFactory::getApplication()->input->get("cid", "0", "raw");
		$idu = 0;
		
		if(intval($cid) != 0){
			$idu = intval($cid);
		}
		else{
			$db = JFactory::getDBO();
			$item = JFactory::getApplication()->input->get('Itemid', 0, "raw");
			
			if(isset($item) && $item != 0){
				$sql = "SELECT params FROM #__menu WHERE id=".intval($item);
				$db->setQuery($sql);
				$db->execute();
				$params = $db->loadColumn();
				$params = json_decode($params["0"]);
				
				if(isset($params->cid)){
					$idu = $params->cid;
				}
			}
		}

		$lang = JFactory::getApplication()->input->get("lang", "", "raw");

	    if(strpos($lang, "-") !== false){
	        $lang = explode("-", $lang);
	        $lang = $lang["0"];
	    }
		
		$query  = "SELECT c.*, count(catid) as copii FROM #__guru_category c LEFT JOIN #__guru_program t ON t.catid=c.id WHERE c.id in (select child_id from #__guru_categoryrel where parent_id=".intval($idu).") and c.published=1 AND (c.language='' OR c.language='*' OR c.language='".$lang."') GROUP BY c.id, c.name, c.alias, c.published, c.description, c.image, c.ordering, c.icon order by c.ordering asc";
		$this->_promos = $this->_getList($query);
		return $this->_promos;
	}
	
	function getchildren_of_subcategory ($id) {
		$db = JFactory::getDBO();

		$lang = JFactory::getApplication()->input->get("lang", "", "raw");

	    if(strpos($lang, "-") !== false){
	        $lang = explode("-", $lang);
	        $lang = $lang["0"];
	    }

		$query  = "SELECT c.*, count(catid) as copii FROM #__guru_category c LEFT JOIN #__guru_program t ON t.catid=c.id WHERE c.id in (select child_id from #__guru_categoryrel where parent_id=".$id.") and c.published=1 AND (c.language='' OR c.language='*' OR c.language='".$lang."') GROUP BY c.id";
		$db->setQuery($query);
		$kkk = $db->loadObjectList();
		return $kkk;
	}	
	
	function getprograms(){
		$id = JFactory::getApplication()->input->get("cid", "0", "raw");
		$jnow 	= new JDate('now');
		$date 	= $jnow->toSQL();
		
		$config = new JConfig();	
		$app = JFactory::getApplication('site');
		$limit		= $app->getUserStateFromRequest('limit', 'limit', $app->getCfg('list_limit'), 'int');
		$limitstart = $app->getUserStateFromRequest('limitstart', 'limitstart', 0, 'int' );
		
		
		$and = "";
		$guru_search = JFactory::getApplication()->input->get("guru_search", "", "raw");
		if(trim($guru_search) != ""){
			$and .= " AND p.name like '%".addslashes(trim($guru_search))."%'";
		}
		
		$guru_configs = $this->getConfigSettings();
		$psgpage = json_decode($guru_configs->psgpage, true);
		$course_price_type = $psgpage["course_price_type"];
		
		$query = "";
		
		if($course_price_type == "0"){
			// lowest plan
			$query = "SELECT DISTINCT(p.id) as course_id, p.*, A.price FROM #__guru_program p LEFT OUTER JOIN (select pp.product_id, min(price) as price from #__guru_program_plans pp GROUP BY pp.product_id) as A on p.id=A.product_id WHERE p.published='1' AND p.status='1' AND p.startpublish <='".$date."' AND (p.endpublish >='".$date."' OR p.endpublish = '0000-00-00 00:00:00' ) AND p.catid=".intval($id)." and 1=1 ".$and." GROUP BY course_id, p.catid, p.name, p.alias, p.description, p.introtext, p.image, p.image_avatar, p.emails, p.published, p.startpublish, p.endpublish, p.metatitle, p.metakwd, p.metadesc, p.ordering, p.pre_req, p.pre_req_books, p.reqmts, p.author, p.level, p.priceformat, p.skip_module, p.chb_free_courses, p.step_access_courses, p.selected_course, p.course_type, p.lesson_release, p.lessons_show, p.start_release, p.id_final_exam, p.certificate_term, p.hasquiz, p.updated, p.certificate_course_msg, p.avg_certc, p.status, p.groups_access, p.split_commissions, p.after_hours, p.reset_on_renew, p.custom_page_url, p.record_hour, p.record_min, p.lesson_view_confirm, p.course_completed_term, p.avg_certificate_course_term, p.record_hour_course_term, p.record_min_course_term, p.lessons_per_release, A.price order by p.ordering asc";
		}
		else{
			$query = "SELECT DISTINCT(p.id) as course_id, p.*, A.price FROM #__guru_program p LEFT OUTER JOIN (select pp.product_id, GROUP_CONCAT(price SEPARATOR '-') as price from #__guru_program_plans pp GROUP BY pp.product_id) as A on p.id=A.product_id WHERE p.published='1' AND p.status='1' AND p.startpublish <='".$date."' AND (p.endpublish >='".$date."' OR p.endpublish = '0000-00-00 00:00:00' ) AND p.catid=".intval($id)." and 1=1 ".$and." GROUP BY course_id order by p.ordering asc";
		}
		$this->_promos = $this->_getList($query);
		
		for($i=0;$i<count($this->_promos);$i++){
			if(trim($this->_promos[$i]->image)!=""){
				$path=explode("/",$this->_promos[$i]->image);
				$this->_promos[$i]->imageName=$path[count($path)-1];
			}
		}
		
		$pagination = $this->getPagination();
		$this->_total = count($this->_promos);
		
		$pages_total = ceil($this->_total / $pagination->limit);
		$current_page = ($limitstart / $limit + 1);
		
		if($current_page > $pages_total){
			$limitstart = 0;
			$current_page = 1;
		}
		
		if(isset($this->_promos) && count($this->_promos) > 0 && $limit != 0){
			$this->_promos = array_slice($this->_promos, (int)($limitstart), (int)($limit));
		}
		
		$pagination->limitstart = $limitstart;
		$pagination->total = $this->_total;
		@$pagination->pagesTotal = $pages_total;
		@$pagination->pagesStop = ceil($this->_total / $pagination->limit);
		@$pagination->pagesCurrent = $current_page;
		$this->set("Pagination", $pagination);
		
		return $this->_promos;
	}
	
	function getnoprograms ($idu) {
		$db = JFactory::getDBO();
		$query  = "SELECT count(id) FROM #__guru_program WHERE published='1' AND catid=".$idu;
		$db->setQuery($query);
		$how_many = $db->loadResult();
		return $how_many;
	}	
	
	function no_of_programs_for_category_children($id) {
			$no_of_cats_with_programs = 0;
			$db = JFactory::getDBO();
			$query  = "SELECT child_id FROM #__guru_categoryrel WHERE parent_id=".$id;
			//$this->_promos = $this->_getList($query);
			$db->setQuery($query);
			$child_id_object = $db->loadObject();
			
			if(isset($child_id_object))
			foreach($child_id_object as $child_id)
				{
					$db = JFactory::getDBO();
					$query  = "SELECT count(id) FROM #__guru_program WHERE published='1' AND status='1' AND catid=".$child_id;
					$db->setQuery($query);
					$how_many = $db->loadColumn();
					$how_many = $how_many["0"];
					$no_of_programs_for_cat = $how_many;
					if($no_of_programs_for_cat>0)
						$no_of_cats_with_programs++;
				}
		return $no_of_cats_with_programs;
	}	
	
	function no_of_programs_for_category_recursive($id) {
			//global $no_of_cats_with_programs;
			
			$no_of_cats_with_programs = 0;
			
			$db = JFactory::getDBO();
			$query  = "SELECT child_id FROM #__guru_categoryrel WHERE parent_id=".$id;
			//$this->_promos = $this->_getList($query);
			$db->setQuery($query);
			$child_id_object = $db->loadColumn();
			
			if(isset($child_id_object))
			foreach($child_id_object as $child_id)
				{
					$more = guruModelguruPcateg::no_of_programs_for_category_recursive($child_id);
					$no_of_cats_with_programs = $no_of_cats_with_programs + $more;
					
					$db = JFactory::getDBO();
					$query  = "SELECT count(id) FROM #__guru_program WHERE published='1' AND status='1' AND catid=".$child_id;
					$db->setQuery($query);
					$how_many = $db->loadColumn();
					$how_many = $how_many["0"];

					if($how_many>0)
						{
							$no_of_cats_with_programs = $no_of_cats_with_programs + $how_many;
						}	
				}
		//echo $no_of_cats_with_programs.' - '.$id.'<br />';
		return $no_of_cats_with_programs;
	}				
	
	function getpdays ($pid) {
			$database = JFactory::getDBO();
			$sql = "SELECT count(id) as how_many FROM #__guru_days WHERE pid='".$pid."' ";
			$database->setQuery($sql);
			$rows = $database->loadObjectList();
			return $rows;
	}	

	function getConfigSettings(){
		$sql = "SELECT * FROM #__guru_config WHERE id=1";
		$ConfigSettings = $this->_getList($sql);
		return $ConfigSettings[0];
	}

	function getsum_points_and_time ($pid) {
			$database = JFactory::getDBO();
			$sql = "SELECT sum(points) as s_points, sum(time) as s_time FROM #__guru_task WHERE id in (SELECT media_id FROM #__guru_mediarel WHERE type='dtask' AND type_id in ( SELECT id FROM #__guru_days WHERE pid=".$pid." )  ) ";  
			$database->setQuery($sql);
			$rows = $database->loadObjectList();
			return $rows;
	}

	function getCateg() {
		if (empty ($this->_promo)) { 
			$this->_promo = $this->getTable("guruPcateg");
			$this->_promo->load($this->_id);
		}		
		if(trim($this->_promo->image)!=""){
			$path=explode("/",$this->_promo->image);
			$this->_promo->imageName=$path[count($path)-1];
		}		
		return $this->_promo;
	}
		
	function store () {			
		$db = JFactory::getDBO();
		$item = $this->getTable('guruPcateg');
		
		$data = JFactory::getApplication()->input->post->getArray();
		if (!$item->bind($data)){
			JFactory::getApplication()->enqueueMessage($db->stderr(), 'error');
			return false;
		}
		if (!$item->check()) {
			JFactory::getApplication()->enqueueMessage($db->stderr(), 'error');
			return false;
		}
		if (!$item->store()) {
			JFactory::getApplication()->enqueueMessage($db->stderr(), 'error');
			return false;
		}
		
		if (intval($data['id']) > 0) {
			//we need to delete the old parent and create new relationship
		} else {
			//inseram in tabela de relatii cu categoriile
			if (intval($newid) == 0) {
				//check for the latest category added
				$ask = "SELECT id FROM #__guru_category ORDER BY id DESC LIMIT 1 ";
				$db->setQuery( $ask );
				$newid = $db->loadColumn();	
				$newid = $newid["0"];			
			}
			//let's do the insert into the relationship table
			$theparent = intval($data['parentcategory_id']);
			$sql = "INSERT INTO #__guru_categoryrel (parent_id, child_id) VALUES ({$theparent},{$newid})";
			$db->setQuery($sql);
			$db->execute();
		}
		return true;
	}	

	function find_if_rogram_was_bought($userid, $progid){
		$database = JFactory::getDBO();
		$sql = "SELECT payment FROM #__guru_order WHERE userid = '".$userid."' AND programid = ".$progid;
		$database->setQuery($sql);
		$result = $database->loadColumn();
		$result = $result["0"];
		if (strtolower($result) == 'trial' || !isset($result) || strtolower($result) == 'not_paid')	
			return 0;
		else return 1;	
	}
	
	function program_status($userid, $progid){
		$database = JFactory::getDBO();
		$sql = "SELECT status FROM #__guru_programstatus WHERE userid = '".$userid."' AND pid = ".$progid;
		$database->setQuery($sql);
		$result = $database->loadColumn();
		$result = $result["0"];
		return $result;
	}
	
	function getCourseAuthors($author){
		$db = JFactory::getDbo();
		$authors = explode("|", $author);
		$return = "";
		$list_authors = array();
		$item_id = JFactory::getApplication()->input->get("Itemid", "0", "raw");
		
		if(is_array($authors) && count($authors) > 0){
			foreach($authors as $key=>$id){
				$sql = "select t.id as teacher_id, u.name from #__users u, #__guru_authors t where u.id=".intval($id)." and u.id=t.userid";
				$db->setQuery($sql);
				$db->execute();
				$author_details = $db->loadAssocList();
				
				if(isset($author_details) && count($author_details) > 0){
					$author_url = '<a href="'.JRoute::_('index.php?option=com_guru&view=guruauthor&layout=view&cid='.$author_details["0"]["teacher_id"]."-".JFilterOutput::stringURLSafe($author_details["0"]["name"])."&Itemid=".$item_id).'">'.$author_details["0"]["name"].'</a>';
					$list_authors[] = $author_url;
				}
			}

		}
		
		$return = implode(", ", $list_authors);
		
		return $return;
	}
	
	function getCourseStudentsCount($id){
		$db = JFactory::getDBO();
		$sql = "SELECT count(distinct bc.userid) FROM #__guru_buy_courses bc, #__users u , #__guru_customer c, #__guru_order o WHERE c.id=bc.userid and bc.userid=u.id and bc.course_id=".intval($id)." and o.userid=c.id and o.userid=bc.userid and o.status='Paid'";
		$db->setQuery($sql);
		$db->execute();
		$result = $db->loadColumn();
		
		return @$result["0"];
	}
};
?>
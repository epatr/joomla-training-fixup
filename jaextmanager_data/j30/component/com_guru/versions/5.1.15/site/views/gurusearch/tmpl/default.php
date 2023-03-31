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

$courses = $this->courses;

if(isset($courses) && count($courses) > 0){
	$document = JFactory::getDocument();
    $document->addStyleSheet(JURI::root().'modules/mod_guru_search/mod_guru_search.css' );

	$item_id = JFactory::getApplication()->input->get("Itemid", "0", "raw");
	$module = JModuleHelper::getModule('mod_guru_search', null);
	$params = new JRegistry;
	$params->loadString($module->params);

	$lang = JFactory::getLanguage();
	$extension = 'mod_guru_search';
	$base_dir = JPATH_SITE;
	$language_tag = '*';
	$lang->load($extension, $base_dir, $language_tag, true);

	echo '<ul class="uk-list uk-list-line">';
	
	foreach($courses as $key=>$course){
?>
		<li class="guru-mod-search_item">
        <?php
            // course thumbnail
            if($this->showCourseImage($params)){
                $image_url = $course["image_avatar"];
                $image_url = str_replace("thumbs/", "", $image_url);
                $img_size = array();
                $host = $_SERVER['HTTP_HOST'];
                $myImg = str_replace("http://", "", $image_url);
                $myImg = str_replace($host, "", $myImg);
                
                if($myImg != $image_url){
                    $myImg =str_replace("/", DS."", $myImg);            
                    $img_size = @getimagesize(JPATH_SITE.DS.$myImg);                    
                }
                else{
                    $img_size = @getimagesize(urldecode($image_url));
                }
                
                $width_old = $img_size["0"];
                $height_old = $img_size["1"];

                $width_th = "0";
                $height_th = "0";
                    
                if($params->get("thumbsizetype", "1") == 0 && isset($img_size)){
                    if($width_old > $params->get("thumbsize", "0") && $params->get("thumbsize", "0") > 0){
                        //proportional by width
                        $raport = $width_old/$height_old;
                        $width_th = $params->get("thumbsize", "0");
                        $height_th = intval($params->get("thumbsize", "0") / $raport);
                        $width_bullet_margin = $params->get("thumbsize", "0");                  
                    }
                    else{
                        $width_th = $width_old;
                        $height_th = $height_old;                   
                    }
                }
                else{
                    if($height_old > $params->get("thumbsize", "0") && $params->get("thumbsize", "0") > 0){
                        //proportional by height            
	                    $raport = $height_old/$width_old;
	                    $height_th = $params->get("thumbsize", "0");                        
	                    $width_th  = intval($params->get("thumbsize", "0") / $raport);
	                    $width_bullet_margin = intval($params->get("thumbsize", "0") / $raport);                    
	                }
                	else{
	                    $width_th = $width_old;
	                    $height_th = $height_old;                   
                    }
                }

                if(trim($course["image_avatar"])){
                    $src =  $this->create_module_thumbnails($image_url, 400, 150, 400, 150);
                    echo '<a class="guru-mod-search_item-thumb" href="'.JRoute::_('index.php?option=com_guru&view=guruPrograms&task=view&cid='.$course["id"]."-".$course["alias"]."&Itemid=".intval($item_id)).'"><img src="'.$src.'" alt="" title=""></a>';
                }
                else{
                    echo '';
                }
            }

            // course title
            echo '<a class="guru-mod-search_item-title" href="'.JRoute::_('index.php?option=com_guru&view=guruPrograms&task=view&cid='.$course["id"]."-".$course["alias"]."&Itemid=".intval($item_id)).'">'.$course["name"].'</a>';

            // course details
            if(($params->get("teachername", "1") == 1) || ($params->get("showamountstud", "1") == 1)){
                echo '<div class="guru-mod-search_item-details">';
                    if($params->get("teachername", "1") == 1){
                        $authors_urls = $this->getAuthor($course, $params);
                        
                        echo "<span><i class='uk-icon-user'></i> ".implode(", ", $authors_urls)."</span>";
                    }

                    if($params->get("showamountstud", "1") == 1){
                        $nr_students = $this->getStudentsNumber($course, $params);

                        echo "<span><i class='uk-icon-users'></i>"." ".$nr_students." ".JText::_('GURU_MODULE_AMOUNT_STUDENTS_FRONT')."</span>";
                    }                                                       
                echo '</div>';
            }

            // course description
            if(($params->get("showdescription", "1") == 1)){
                echo '<div class="guru-mod-search_item-desc">';
                    if($params->get("showdescription", "1") == 1){
                        $description = $this->getDescription($course, $params);
                        echo '<p>'.$description.'</p>';
                    }
                    else{
                        echo '';
                    }
                echo'</div>';
            }
        ?>
        </li>	
<?php
	}

	echo '</ul>';
}
else{
?>
    <div class="uk-alert-primary uk-alert" style="text-align: center;"><?php echo JText::_("GURU_NO_RESULTS"); ?></div>
<?php
}
?>
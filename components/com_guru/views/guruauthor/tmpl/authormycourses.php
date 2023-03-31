<?php

/*------------------------------------------------------------------------
# com_guru
# ------------------------------------------------------------------------
# author    iJoomla
# copyright Copyright (C) 2013 ijoomla.com. All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Websites: http://www.ijoomla.com
# Technical Support:  Forum - http://www.ijoomla.com/forum/index/
-------------------------------------------------------------------------*/

defined( '_JEXEC' ) or die( 'Restricted access' );
JHTML::_('behavior.tooltip');
$db = JFactory::getDBO();
$div_menu = $this->authorGuruMenuBar();
$my_courses = $this->mycoursesth;
$config = $this->config;
$allow_teacher_action = json_decode($config->st_authorpage);//take all the allowed action from administator settings
@$teacher_add_courses = $allow_teacher_action->teacher_add_courses; //allow or not action Add courses
@$teacher_edit_courses = $allow_teacher_action->teacher_edit_courses; //allow or not action Add courses
$Itemid_programs = JFactory::getApplication()->input->get("Itemid", "0", "raw");
$Itemid_author = JFactory::getApplication()->input->get("Itemid", "0", "raw");
$doc = JFactory::getDocument();
$doc->setTitle(trim(JText::_('GURU_AUTHOR'))." ".trim(JText::_('GURU_AUTHOR_MY_COURSE')));

$helper = new guruHelper();
$itemid_seo = $helper->getSeoItemid();
$itemid_seo = @$itemid_seo["guruauthor"];

if(intval($itemid_seo) > 0){
	$Itemid_author = intval($itemid_seo);
}

$helper = new guruHelper();
$itemid_seo = $helper->getSeoItemid();
$itemid_seo = @$itemid_seo["guruprograms"];

if(intval($itemid_seo) > 0){
	$Itemid_programs = intval($itemid_seo);
}

?>

<script type="text/javascript" language="javascript">
	document.body.className = document.body.className.replace("modal", "");
</script>

<script language="javascript" type="application/javascript">
	function deleteAuthorCourse(){
		if (document.adminForm['boxchecked'].value == 0) {
			alert( "<?php echo JText::_("GURU_MAKE_SELECTION_FIRT");?>" );
		} 
		else{
			if(confirm("<?php echo JText::_("GURU_REMOVE_AUTHOR_COURSES"); ?>")){
				document.adminForm.task.value='removeCourse';
				document.adminForm.submit();
			}
		}	
	}
	
	function newAuthorCourse(){
		document.adminForm.task.value='addCourse';
		document.adminForm.action = '<?php echo JRoute::_("index.php?option=com_guru&view=guruauthor&task=addCourse&layout=addCourse&Itemid=".intval($Itemid_author)); ?>';
		document.adminForm.submit();	
	}
	
	function duplicateCourse(){
		if (document.adminForm['boxchecked'].value == 0) {
			alert( "<?php echo JText::_("GURU_MAKE_SELECTION_FIRT");?>" );
		} 
		else{
			document.adminForm.task.value='duplicateCourse';
			document.adminForm.submit();
		}	
	}
	
	function unpublishCourse(){
		if (document.adminForm['boxchecked'].value == 0) {
			alert( "<?php echo JText::_("GURU_MAKE_SELECTION_FIRT");?>" );
		} 
		else{
			document.adminForm.task.value='unpublishCourse';
			document.adminForm.submit();
		}	
	}
	
	function publishCourse(){
		if (document.adminForm['boxchecked'].value == 0) {
			alert( "<?php echo JText::_("GURU_MAKE_SELECTION_FIRT");?>" );
		} 
		else{
			document.adminForm.task.value='publishCourse';
			document.adminForm.submit();
		}	
	}
</script>	

<div class="gru-mycoursesauthor">
    <?php 	echo $div_menu; //MENU TOP OF AUTHORS?>
    <!--BUTTONS -->
    <div class="uk-grid uk-margin">
        <div class="uk-width-1-1 uk-width-medium-1-3"><h2 class="gru-page-title"><?php echo JText::_('GURU_AUTHOR_MY_COURSE');?></h2></div>
        <div class="uk-width-1-1 uk-width-2-3 uk-text-right uk-hidden-small">
            <div class="uk-button-group">
               <?php if($teacher_add_courses == 0){?>
                        <input type="button" class="uk-button uk-button-success" value="<?php echo JText::_('GURU_NEW'); ?>" onclick="newAuthorCourse();"/>
                        <input type="button" class="uk-button" value="<?php echo JText::_('GURU_DUPLICATE'); ?>" onclick="duplicateCourse();"/>
               <?php }?> 
                 <input type="button" class="uk-button" value="<?php echo JText::_('GURU_UNPUBLISH'); ?>" onclick="unpublishCourse();"/>
                 <input type="button" class="uk-button uk-button-primary" value="<?php echo JText::_('GURU_PUBLISH'); ?>" onclick="publishCourse();"/>
                 <input type="button" class="uk-button uk-button-danger" value="<?php echo JText::_('GURU_DELETE'); ?>" onclick="deleteAuthorCourse();"/>
            </div>
        </div>

        <div class="uk-width-1-1 uk-visible-small">
           <?php if($teacher_add_courses == 0){?>
                    <input type="button" class="uk-button uk-button-success uk-width-1-1" value="<?php echo JText::_('GURU_NEW'); ?>" onclick="newAuthorCourse();"/>
                    <input type="button" class="uk-button uk-width-1-1" value="<?php echo JText::_('GURU_DUPLICATE'); ?>" onclick="duplicateCourse();"/>
           <?php }?> 
             <input type="button" class="uk-button uk-width-1-1" value="<?php echo JText::_('GURU_UNPUBLISH'); ?>" onclick="unpublishCourse();"/>
             <input type="button" class="uk-button uk-button-primary uk-width-1-1" value="<?php echo JText::_('GURU_PUBLISH'); ?>" onclick="publishCourse();"/>
             <input type="button" class="uk-button uk-button-danger uk-width-1-1" value="<?php echo JText::_('GURU_DELETE'); ?>" onclick="deleteAuthorCourse();"/>
        </div>
    <!-- -->
    </div>
    
    <div id="g_mycoursesauthorcontent" class="g_sect clearfix">
        <form  action="index.php" class="form-horizontal" id="adminForm" method="post" name="adminForm" enctype="multipart/form-data">
            <!-- Start Search -->
            
            <div class="gru-page-filters">
				<div class="gru-filter-item">
                    <input type="text" class="form-control" name="filter_search" placeholder="<?php echo JText::_("GURU_SEARCH"); ?>" value="<?php echo $this->escape($this->state->get('filter.search')); ?>" />
                    <button class="uk-button uk-button-primary hidden-phone" type="submit"><?php echo JText::_("GURU_SEARCH"); ?></button>
				</div>
			</div>
            
            <!-- End Search -->
			<div class="clearfix"></div>
            <div class="g_table_wrap">
                <table id="g_authorcourse" class="uk-table uk-table-striped">
                    <tr>
                        <th class="g_cell_1"><input type="checkbox" name="checkall-toggle" value="" onclick="Joomla.checkAll(this);" /></th>
                        <th class="g_cell_2 hidden-phone"><?php echo JText::_('GURU_TI_VIEW_COURSE'); ?></th>
                        <th class="g_cell_3 hidden-phone"><?php echo JText::_('GURU_COURSE_CURRICULUM'); ?></th>
                        <th class="g_cell_4 hidden-phone"><?php echo JText::_("GURU_COURSE_DETAILS"); ?></th>
                        <th class="g_cell_5 hidden-phone"><?php echo JText::_("GURU_STATS"); ?></th>
                        <th class="g_cell_6 hidden-phone">
                            <span class="hidden-phone"><?php echo JText::_("GURU_PUBL"); ?></span>
                            <span class="uk-hidden-large uk-hidden-medium">&nbsp;&nbsp;&nbsp;&nbsp;</span>
                        </th>
                        <th class="g_cell_3 hidden-phone">
                            <span><?php echo JText::_("GURU_STATUS"); ?></span>
                        </th>
                        <th class="g_cell_3 hidden-phone center">
                            <span><?php echo JText::_("GURU_TO_MARK"); ?></span>
                        </th>
                        <th class="uk-visible-small"><?php echo JText::_("GURU_INFO"); ?></th>
                    </tr>
    
        
                    <?php 
                    $n =  count($my_courses);
                    for ($i = 0; $i < $n; $i++):
                        $id = $my_courses[$i]->id;
                        $checked = JHTML::_('grid.id', $i, $id);
                        //$published = JHTML::_('grid.published', $my_courses[$i], $i );
                        $alias = isset($my_courses[$i]->alias) ? trim($my_courses[$i]->alias) : JFilterOutput::stringURLSafe($my_courses[$i]->course_name);
                    ?>
                        <tr class="guru_row">	
                            <td class="g_cell_1"><?php echo $checked;?></td>
                            <td class="g_cell_2 hidden-phone">
                            	<a href="<?php echo JRoute::_("index.php?option=com_guru&view=guruPrograms&task=view&cid=".$id."-".$alias."&Itemid=".$Itemid_programs); ?>"><i class="fa fa-eye"></i></a>
                            </td>
                            <td class="guru_product_name g_cell_3 hidden-phone">
                            <?php
								if($teacher_edit_courses == 0){
							?>
                            		<a href="index.php?option=com_guru&view=guruauthor&task=treeCourse&pid=<?php echo intval($my_courses[$i]->id); ?>"><?php echo $my_courses[$i]->name; ?></a>	
                            <?php
                            	}
								else{
									echo $my_courses[$i]->name;
								}
							?>
                            </td>
                            <td class="g_cell_4 hidden-phone">
                                <?php if($teacher_edit_courses == 0){
                                ?>        
                                <a href="index.php?option=com_guru&view=guruauthor&task=addCourse&id=<?php echo intval($my_courses[$i]->id); ?>"><i class="fa fa-pencil-square-o"></i></a>
                                <?php 
                                }
                                else{
                                ?>
                                	<i class="fa fa-pencil-square-o"></i>
                                <?php
                                }
                                ?>
                            </td>
                            <td class="g_cell_5 hidden-phone">          
                                <a href="index.php?option=com_guru&view=guruauthor&task=course_stats&id=<?php echo intval($my_courses[$i]->id); ?>"><i class="fa fa-list"></i></a>
                            </td>
                            <td class="g_cell_6 hidden-phone">       
                                <?php
                                    if($my_courses[$i]->published == 0){
                                        echo '<a title="Publish Item" onclick="return listItemTask(\'cb'.$i.'\', \'publishCourse\')" href="#">
                                                <i class="fa fa-times-circle"></i>
                                              </a>';
                                    }
                                    else{
                                        echo '<a title="Unpublish Item" onclick="return listItemTask(\'cb'.$i.'\',\'unpublishCourse\')" href="#">
                                                <i class="fa fa-check-circle-o"></i>
                                              </a>';
                                    }
                                ?>
                          </td>
                          <td class="g_cell_3 hidden-phone">
                                <?php
                                    if($my_courses[$i]->status == "0"){
                                        echo '<div class="pending">'.JText::_("GURU_PENDING").'</div>';
                                    }
                                    elseif($my_courses[$i]->status == "1"){
                                        echo '<div class="approved">'.JText::_("GURU_APPROVED").'</div>';
                                    }
                                ?>
                          </td>
                          <td class="g_cell_3 hidden-phone center">
                                <?php
                                    $need_mark = $this->getNeedMark($my_courses[$i]->id);
                                    
                                    if($need_mark){
                                ?>
                                        <a href="<?php echo "index.php?option=com_guru&view=guruauthor&task=mark&id=".intval($my_courses[$i]->id); ?>"><i class="fa fa-book"></i></a>
                                <?php
                                    }
                                ?>
                          </td>
                          <td class="uk-visible-small">
                            <span class="table-label"><?php echo JText::_('GURU_TI_VIEW_COURSE'); ?></span>&nbsp;
                            <a href="<?php echo JRoute::_("index.php?option=com_guru&view=guruPrograms&task=view&cid=".$id."-".$alias."&Itemid=".$Itemid_programs); ?>"><i class="fa fa-eye"></i></a>
                            <br />
                            
                            <span class="table-label"><?php echo JText::_('GURU_COURSE_CURRICULUM'); ?></span>&nbsp;
                            <a href="index.php?option=com_guru&view=guruauthor&task=treeCourse&pid=<?php echo intval($my_courses[$i]->id); ?>"><?php echo $my_courses[$i]->name; ?></a>
                            <br />
                            
                            <span class="table-label"><?php echo JText::_('GURU_COURSE_DETAILS'); ?></span>&nbsp;
                            <?php if($teacher_edit_courses == 0){
                            ?>        
                            <a href="index.php?option=com_guru&view=guruauthor&task=addCourse&id=<?php echo intval($my_courses[$i]->id); ?>"><i class="fa fa-pencil-square-o"></i></a>
                            <?php 
                            }
                            else{
                                echo JText::_("GURU_INFO");
                            }
                            ?>
                            <br />
                            
                            <span class="table-label"><?php echo JText::_('GURU_STATS'); ?></span>&nbsp;
                            <a href="index.php?option=com_guru&view=guruauthor&task=course_stats&id=<?php echo intval($my_courses[$i]->id); ?>"><i class="fa fa-list"></i></a>
                            <br />
                            
                            <span class="table-label"><?php echo JText::_('GURU_PUBL'); ?></span>&nbsp;
                            <?php
                                if($my_courses[$i]->published == 0){
                                    echo '<a title="Publish Item" onclick="return listItemTask(\'cb'.$i.'\', \'publishCourse\')" href="#">
                                            <i class="fa fa-times-circle"></i>
                                          </a>';
                                }
                                else{
                                    echo '<a title="Unpublish Item" onclick="return listItemTask(\'cb'.$i.'\',\'unpublishCourse\')" href="#">
                                            <i class="fa fa-check-circle-o"></i>
                                          </a>';
                                }
                            ?>
                            <br />
                            
                            <span class="table-label"><?php echo JText::_('GURU_STATUS'); ?></span>&nbsp;
                            <?php
                                if($my_courses[$i]->status == "0"){
                                    echo '<div class="pending" style="float:left;">'.JText::_("GURU_PENDING").'</div>';
                                }
                                elseif($my_courses[$i]->status == "1"){
                                    echo '<div class="approved" style="float:left;">'.JText::_("GURU_APPROVED").'</div>';
                                }
                            ?>
                            <br />
                            
                            <?php
                                $need_mark = $this->getNeedMark($my_courses[$i]->id);
                                
                                if($need_mark){
                            ?>
                            <span class="table-label"><?php echo JText::_('GURU_TO_MARK'); ?></span>&nbsp;
                                    <a href="<?php echo "index.php?option=com_guru&view=guruauthor&task=mark&id=".intval($my_courses[$i]->id); ?>"><i class="fa fa-book"></i></a>
                            <?php
                                }
                            ?>
                            
                          </td>
                       </tr>             
                    <?php
                        endfor;
                    ?>	
                </table>
        </div>
       
       <?php
            echo $this->pagination->getLimitBox();
            $pages = $this->pagination->getPagesLinks();
            include_once(JPATH_SITE.DIRECTORY_SEPARATOR."components".DIRECTORY_SEPARATOR."com_guru".DIRECTORY_SEPARATOR."helpers".DIRECTORY_SEPARATOR."helper.php");
            $helper = new guruHelper();
            $pages = $helper->transformPagination($pages);
            echo $pages;
        ?>
            
            <input type="hidden" name="task" value="<?php echo JFactory::getApplication()->input->get("task", "authormycourses"); ?>" />
            <input type="hidden" name="option" value="com_guru" />
            <input type="hidden" name="controller" value="guruAuthor" />
            <input type="hidden" name="boxchecked" value="" />
        </form>
   </div>  
</div>
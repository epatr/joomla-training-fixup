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
$div_menu = $this->authorGuruMenuBar();

$quizes = $this->quizes;
$userid = JFactory::getApplication()->input->get("userid", "0");
$pid = JFactory::getApplication()->input->get("pid", "0");
$user_name = $this->userName($userid);
$user_email = $this->userEmail($userid);
$doc = JFactory::getDocument();
$doc->setTitle(trim(JText::_('GURU_AUTHOR'))." ".trim(JText::_('GURU_COU_STUDENTS'))." ".trim(JText::_('GURU_DETAILS')));
$doc = JFactory::getDocument();
$image = $this->userImage($userid);

$doc->addStyleSheet("components/com_guru/css/uikit.almost-flat.min.css");

?>

<script type="text/javascript" language="javascript">
	document.body.className = document.body.className.replace("modal", "");
</script>

<div id="g_myquizzesstats" class="gru-myquizzesstats">
    <?php echo $div_menu; //MENU TOP OF AUTHORS ?>
    
    <div class="uk-grid">
    	<div class="uk-width-1-1">
        	<button class="uk-button uk-button-primary uk-float-right" onclick="window.location='index.php?option=com_guru&view=guruauthor&&layout=studentdetails&userid=<?php echo $userid;?>&tmpl=component'"><?php echo JText::_("GURU_BACK"); ?></button>
        </div>
    </div>
    
    <form action="index.php" class="form-horizontal" id="adminForm" method="post" name="adminForm" enctype="multipart/form-data">
            <h4>
                <?php
                    if(trim($image) == ""){
                        $grav_url = "http://www.gravatar.com/avatar/".md5(strtolower(trim($user_email)))."?d=mm&s=40";
                        echo '<img src="'.$grav_url.'" alt="'.$user_name.'" title="'.$user_name.'"/>&nbsp;';
                    }
                    else{
                        echo '<img src="'.JURI::root().trim($image).'" style="width:40px;" alt="'.$user_name.'" title="'.$user_name.'" />&nbsp;';
                    }
                    echo $user_name;
                ?>
            </h4>
            <table class="uk-table uk-table-striped">
                <tr>
                    <th class="g_cell_1"><?php echo JText::_("GURU_QUIZ_NAME"); ?></th>
                    <th class="g_cell_2 hidden-phone"><?php echo JText::_("GURU_AUTHOR_ATTEP"); ?></th>
                    <th class="g_cell_3 hidden-phone"><?php echo JText::_('GURU_CORRECT_ANSWERS'); ?></th>
                    <th class="g_cell_4 hidden-phone"><?php echo JText::_("GURU_WRONG_ANSWERS"); ?></th>
                    <th class="g_cell_5"><?php echo JText::_("GURU_FINAL_SCORE"); ?></th>
                    <th class="g_cell_6"><?php echo JText::_("GURU_DETAILS"); ?></th>
                </tr>
                <?php
                    if(isset($quizes) && count($quizes) > 0){
                        foreach($quizes as $key=>$quiz){
                ?>
                            <tr class="guru_row">
                                <td class="g_cell_1">
                                    <?php echo $quiz["name"]; ?>
                                </td>
                                <td class="g_cell_2 hidden-phone">
                                    <?php echo intval($quiz["timequizuser"])."/".$quiz["time_quiz_taken"]; ?>
                                </td>
                                <td class="g_cell_3 hidden-phone">
                                    <?php echo $quiz["correct"]; ?>
                                </td>
                                <td class="g_cell_4 hidden-phone">
                                    <?php echo $quiz["wrong"]; ?>
                                </td>
                                <td class="g_cell_5">
                                    <?php
                                        //if(trim($quiz["final_score"]) != "" && intval($quiz["final_score"]) != 0){
                                            echo $quiz["final_score"]."% / (".$quiz["max_score"]."%)";
                                        //}
                                    ?>
                                </td>
                                <td class="g_cell_6">
                                    <?php
                                        //if(trim($quiz["final_score"]) != "" && intval($quiz["final_score"]) != 0){
                                    ?>
                                            <input type="button" class="uk-button uk-button-success" onclick="window.location='<?php echo JURI::root()."index.php?option=com_guru&view=guruauthor&task=quizdetails&layout=quizdetails&pid=".intval($pid)."&userid=".intval($userid)."&quiz=".intval($quiz["quiz_id"]); ?>&tmpl=component&step_quiz=<?php echo intval($quiz["id"]); ?>'" value="<?php echo JText::_("GURU_DETAILS"); ?>" />
                                    <?php
                                        //}
                                    ?>        
                                </td>
                            </tr>
                <?php
                        }
                    }
                ?>
        </table>
                 
        <input type="hidden" name="task" value="<?php echo JFactory::getApplication()->input->get("task", ""); ?>" />
        <input type="hidden" name="option" value="com_guru" />
        <input type="hidden" name="controller" value="guruAuthor" />
    </form> 
</div>                  
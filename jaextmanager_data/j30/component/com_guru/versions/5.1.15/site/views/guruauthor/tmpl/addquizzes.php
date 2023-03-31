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
$doc =JFactory::getDocument();
$doc->addScript('components/com_guru/js/open_modal.js');
$data_get = JFactory::getApplication()->input->get->getArray();
$list_quizzes = $this->list_quizzes;
$n = count($list_quizzes);

?>

<script type="text/javascript" language="javascript">
	function savequizzes() {
		var chks = document.getElementsByName('cb[]');
		var hasChecked = false;
		for (var i = 0; i < chks.length; i++){
			if (chks[i].checked){
				old_value = document.getElementById('quizzes_ids').value;
				new_value = old_value+","+chks[i].value;
				document.getElementById('quizzes_ids').value = new_value;
				hasChecked = true;
			}
		}
		if (hasChecked == false){
			alert("Please select at least one quiz.");
			return false;
		}
			document.adminForm.submit();
	}
	
</script>

<script type="text/javascript" language="javascript">
	document.body.className = document.body.className.replace("modal", "");
</script>

<h2 class="gru-page-title"><?php echo JText::_("GURU_ADD_QUIZZES_TO_FINAL_EXAM"). " ".@$quiz_name ; ?></h2>
		
<form class="g_modal_search" name="form1" action="index.php?option=com_guru&controller=guruAuthor&task=addquizzes&no_html=1&cid=<?php echo $data_get['cid'];?>&tmpl=<?php echo JFactory::getApplication()->input->get("tmpl", ""); ?>" method="post">

    <div class="gru-page-filters">
        <input style="margin:0px;" type="text" name="search_text"  value="<?php echo JFactory::getApplication()->input->get("search_text", "");?>" />
        <button type="submit" name="submit_search"  class="uk-button uk-button-primary hidden-phone"><?php echo JText::_('GURU_SEARCHTXT');?></button>
    </div>        
</form>

<form method="post" name="adminForm" id="adminForm" action="index.php">
    <div id="editcell" class="clearfix">
        <table class="uk-table uk-table-striped">
                <th width="1%"></th>
                <th width="1%"><?php echo JText::_("GURU_ID");?></th>
                <th><?php echo JText::_("VIEWPLUGTITLE");?></th>
        <?php
        $k = 0;
        for($i = 0; $i < count($list_quizzes); $i++){
            $id =  $list_quizzes[$i]["id"];
        ?>
            <tr class="row<?php echo $k;?>">
                <td><input type="checkbox" name="cb[]"  id="cb[]" value= "<?php echo $id;?>"><span class="lbl"></span></td>	
                <td><?php echo $list_quizzes[$i]["id"];?></td>
                <td><?php echo $list_quizzes[$i]["name"];?></td>
            </tr> 
        <?php
            $k = 1 - $k;
        }
        ?> 
        </table>
    </div>  
    <div>
        <input type="button" class="uk-button uk-button-success" onclick="savequizzes();" value="<?php echo JText::_("GURU_SAVE_PROGRAM_BTN"); ?>"> 
    </div> 
    
	<input type="hidden" value="com_guru" name="option"/>
	<input type="hidden" value="savequizzes" name="task"/>
	<input type="hidden" value="<?php echo intval($data_get['cid']);?>" name="quizid"/>
    <input id="quizzes_ids" name="quizzes_ids" type="hidden" value="" />
	<input type="hidden" value="guruAuthor" name="controller"/>
    <input type="hidden" value="<?php echo JFactory::getApplication()->input->get("tmpl", ""); ?>" name="tmpl"/>
</form>
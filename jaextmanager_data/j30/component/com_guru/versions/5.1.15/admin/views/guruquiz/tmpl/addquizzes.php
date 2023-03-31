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
//These scripts are already been included from the administrator\components\com_guru\guru.php file
//$doc->addScript('components/com_guru/js/jquery.DOMWindow.js');
//$doc->addScript('components/com_guru/js/open_modal.js');

$list_quizzes = $this->list_quizzes;
$n = count($list_quizzes);

$data_post = JFactory::getApplication()->input->post->getArray();
$data_get = JFactory::getApplication()->input->get->getArray();
 
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


<h2 class="g_modal_title"><?php echo JText::_("GURU_ADD_QUIZZES_TO_FINAL_EXAM"). " ".@$quiz_name ; ?></h2>
		

<form class="g_modal_search" name="form1" action="index.php?option=com_guru&controller=guruQuiz&task=addquizzes&no_html=1&cid[]=<?php echo $data_get['cid'][0];?>&tmpl=<?php echo JFactory::getApplication()->input->get("tmpl", ""); ?>" method="post">
	<table style="padding-left:2px; font-size:11px;">
       	 <td>
				<input  type="text" name="search_text"  style="height:18px; margin-bottom:0px !important;" value="<?php if(isset($data_post['search_text'])) echo $data_post['search_text'];?>" />
				<input  type="submit"  class="btn" name="submit_search"  style="height:26px;" value="<?php echo JText::_('GURU_SEARCHTXT');?>" />
			</td>
       </table>
</form >
<input type="hidden" value="search_text" name="search_text"/>
<form method="post" name="adminForm" id="adminForm" action="index.php">
     
	    <div id="editcell">
       <br/>
        	
            <table class="table table-striped">
                	<th></th>
                	<th><?php echo JText::_("GURU_ID");?></th>
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
        <br/>
        <div style="margin-left:3px;">
        	<input type="button" class="btn" onclick="savequizzes();" value="<?php echo JText::_("GURU_SAVE_PROGRAM_BTN"); ?>"> 
        </div> 
	<input type="hidden" value="com_guru" name="option"/>
	<input type="hidden" value="savequizzes" name="task"/>
	<input type="hidden" value="<?php echo intval($data_get['cid'][0]);?>" name="quizid"/>
    <input id="quizzes_ids" name="quizzes_ids" type="hidden" value="" />
	<input type="hidden" value="guruQuiz" name="controller"/>
    <input type="hidden" value="<?php echo JFactory::getApplication()->input->get("tmpl", ""); ?>" name="tmpl"/>
</form>
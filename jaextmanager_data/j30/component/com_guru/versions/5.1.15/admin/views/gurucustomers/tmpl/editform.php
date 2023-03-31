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
jimport('joomla.utilities.date');
require_once JPATH_ADMINISTRATOR.'/components/com_users/helpers/users.php';

$id = "0";
$username = "";
$first_name = "";
$last_name = "";
$company = "";
$email = "";
$id = 0;
$disable = "";
$sequential_courses = "[]";

$cid = (JFactory::getApplication()->input->get("cid", "", "raw")) ?: array() ;

if(isset($cid) && count($cid) > 0){
	@$id = intval($cid["0"]);
}
$customer = $this->getCustomerDetails($id);
$customer_courses = $this->getStudentCourses($id);

$groups = array();

if(isset($customer) && is_array($customer) && count($customer) > 0){
	$username = $customer["0"]["username"];
	$first_name = $customer["0"]["firstname"];
	$last_name = $customer["0"]["lastname"];
	$company = $customer["0"]["company"];
	$email = $customer["0"]["email"];

	if(trim($customer["0"]["sequential_courses"]) != ""){
		$sequential_courses = $customer["0"]["sequential_courses"];
	}
	
	$disable = 'disabled=""';
	
	$db = JFactory::getDbo();
	$sql = "select group_id from #__user_usergroup_map where user_id=".intval($id);
	$db->setQuery($sql);
	$db->execute();
	$groups = $db->loadColumn();
}

?>

<script language="javascript" type="text/javascript">	
	
	Joomla.submitbutton = function(pressbutton){
	//function submitbutton(pressbutton) {
		var form = document.adminForm;		
		id = <?php echo $id; ?>;	
		//var reg = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;
		var reg = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,5})$/;
		
		if (pressbutton=='save' || pressbutton=='apply'){
			if (id == 0){
				if (form['password'].value == "") {
					alert( "<?php echo JText::_("GURU_INSERT_PASS");?>" );
					return false;
				}
				else if(form['password'].value != form['password_confirm'].value){
					alert( "<?php echo JText::_("GURU_MATCH_PASS");?>" );
					return false;
				}
			}	
			
			if (form['username'].value == "") {
				alert("<?php echo JText::_("GURU_INSERT_USERNAME");?>");
				return false;
			}
			else if (form['firstname'].value == "") {
				alert( "<?php echo JText::_("GURU_INSERT_FIRSTNAME");?>" );
				return false;
			}
			else if (form['lastname'].value == "") {
				alert( "<?php echo JText::_("GURU_INSERT_LASTNAME");?>" );
				return false;
			}
			else if (form['email'].value == "") {
				alert( "<?php echo JText::_("GURU_INSERT_EMAIL");?>" );				
				return false;
			}
			else if(reg.test(form['email'].value) == false){
				alert('<?php echo JText::_("GURU_INSERT_VALIDMAIL"); ?>');
				return false;
			}
			else if (form.gid.value == "") {
				alert("<?php echo JText::_("GURU_ASSIGN_USER_TO_GROUP"); ?>");
				return false;
			} 
			else if (form.gid.value == "1") {		
				alert("<?php echo JText::_("GURU_NOT_PUBLIC_FRONTEND"); ?>");
				return false;
			} 
			else if (form.gid.value == "30") {
				alert("<?php echo JText::_("GURU_NOT_PUBLIC_BACKEND"); ?>");
				return false;
			}		
			//else{
				//submitform(pressbutton);
			//}
			checkGuruExistingUser();
			setTimeout(submitIFOk(pressbutton), 1000);
		}
		else{ 
			submitform(pressbutton);
		}	
	}
	
	function submitIFOk(pressbutton){
		check_return = document.getElementById("ajax_response").innerHTML;
		if(check_return != 0){
			if(trimString(check_return) == '111'){// not validate email
				alert("<?php echo JText::_("GURU_EMAIL_IN_USE"); ?>");
				return false;
			}
			else if(trimString(check_return) == '222'){// not validate username
				alert("<?php echo JText::_("GURU_USERNAME_IN_USE"); ?>");
				return false;
			}
		}
		else{
			document.adminForm.task.value = pressbutton;
			document.adminForm.submit();
		}
	}
	function checkGuruExistingUser(){
		username = document.getElementById("username").value;
		email = document.getElementById("email").value;
		
		if(username != ""){
			htmlvalue = "0";
			/*var req = jQuery.ajax({
				async: false,
				method: 'get',
				url: 'index.php?option=com_guru&controller=guruPrograms&tmpl=component&format=raw&task=checkExistingUser&username='+username+'&email='+email+'&id=<?php echo $id; ?>',
				data: { 'do' : '1' },
				onComplete: function(response){
					document.getElementById("ajax_response").empty().adopt(response);
				}
			})*/

			jQuery.ajax({
				async: false,
				url: 'index.php?option=com_guru&controller=guruPrograms&tmpl=component&format=raw&task=checkExistingUser&username='+username+'&email='+email+'&id=<?php echo $id; ?>',
				success: function(response) {
		            jQuery("#ajax_response").empty().html(response);
				}
		    });
		}

		if(email != ""){
			htmlvalue = "0";
			/*var req = jQuery.ajax({
				async: false,
				method: 'get',
				url: 'index.php?option=com_guru&controller=guruPrograms&tmpl=component&format=raw&task=checkExistingUser&username='+username+'&email='+email+'&id=<?php echo $id; ?>',
				data: { 'do' : '1' },
				onComplete: function(response){
					document.getElementById("ajax_response").empty().adopt(response);
				}
			})*/

			jQuery.ajax({
				async: false,
				url: 'index.php?option=com_guru&controller=guruPrograms&tmpl=component&format=raw&task=checkExistingUser&username='+username+'&email='+email+'&id=<?php echo $id; ?>',
				success: function(response) {
		            jQuery("#ajax_response").empty().html(response);
				}
		    });
		}
		
		check_return = document.getElementById("ajax_response").innerHTML;
	}
	
	function trimString(str){
		str = str.toString();
		var begin = 0;
		var end = str.length - 1;
		while (begin <= end && str.charCodeAt(begin) < 33) { ++begin; }
		while (end > begin && str.charCodeAt(end) < 33) { --end; }
		return str.substr(begin, end - begin + 1);
	}

</script>	

<form method="post" name="adminForm" id="adminForm">
	<fieldset class="adminform">
			<table class="admintable">
				<tr>
					<td width="250"><?php echo JText::_("GURU_USERNAME"); ?><span class="error" style="color:#FF0000;">*</span></td>
					<td width="45%"><input type="text" value="<?php echo $username; ?>" size="30" id="username" <?php echo $disable; ?> name="username"><b>&nbsp;</b>
						<span class="editlinktip hasTip" title="<?php echo JText::_("GURU_TIP_USERNAME"); ?>" >
							<img border="0" src="components/com_guru/images/icons/tooltip.png">
						</span>
					</td>
					
                    <?php
                    	if(intval($id) > 0){
					?>
                        <td>
                            <select name="course">
                                <option value="0"><?php echo JText::_("GURU_SELECT_COURSE"); ?></option>
                            <?php
                                if(isset($customer_courses) && count($customer_courses) > 0){
                                    foreach($customer_courses as $key=>$course){
                                        echo '<option value="'.$course["id"].'">'.$course["name"].'</option>';
                                    }
                                }
                            ?>
                            </select>
                        </td>
                        <td>
                            <input type="submit" class="btn btn-success" value="<?php echo JText::_("GURU_RESET"); ?>" name="reset" onclick="document.adminForm.task.value='reset'" />
                        </td>
                        <td>
                            <input type="submit" class="btn btn-warning" value="<?php echo JText::_("GURU_REMOVE_FROM_COURSE"); ?>" onclick="document.adminForm.task.value='remove_from_course'" />
                        </td>
					<?php
                    	}
					?>
				</tr>
				
				<?php if($id == "0"){ ?>
						<tr>
							<td><?php echo JText::_("GURU_PASSWORD"); ?><span class="error" style="color:#FF0000;">*</span></td>
							<td><input type="password" size="30" id="password" name="password"><b>&nbsp;</b>
                            	<span class="editlinktip hasTip" title="<?php echo JText::_("GURU_INSERT_PASS"); ?>" >
                                    <img border="0" src="components/com_guru/images/icons/tooltip.png">
                                </span>
                            </td>
						</tr>
						
						<tr>
							<td><?php echo JText::_("GURU_CONFIRM_PASSWORD"); ?><span class="error" style="color:#FF0000;">*</span></td>
							<td><input type="password" size="30" id="password_confirm" name="password_confirm"><b>&nbsp;</b>
                            	<span class="editlinktip hasTip" title="<?php echo JText::_("GURU_MATCH_PASS"); ?>" >
                                    <img border="0" src="components/com_guru/images/icons/tooltip.png">
                                </span>
                            </td>
						</tr>
				<?php 
					}
				?>
				<tr>
					<td width="31%"><?php echo JText::_("GURU_FIRS_NAME"); ?><span class="error" style="color:#FF0000;">*</span></td>
					<td><input type="text" value="<?php echo $first_name; ?>" size="30" id="firstname" name="firstname"><b>&nbsp;</b>
					<span class="editlinktip hasTip" title="<?php echo JText::_("GURU_TIP_FIRS_NAME"); ?>" >
						<img border="0" src="components/com_guru/images/icons/tooltip.png">
					</span>
					</td>
				</tr>
				
				<tr>
					<td><?php echo JText::_("GURU_LAST_NAME"); ?><span class="error" style="color:#FF0000;">*</span></td>
					<td><input type="text" value="<?php echo $last_name; ?>" size="30" id="lastname" name="lastname"><b>&nbsp;</b>
					<span class="editlinktip hasTip" title="<?php echo JText::_("GURU_TIP_LAST_NAME"); ?>" >
						<img border="0" src="components/com_guru/images/icons/tooltip.png">
					</span>
					</td>
				</tr>
				
				<tr>
					<td><?php echo JText::_("GURU_COMPANY"); ?><b></b></td>
					<td><input type="text" value="<?php echo $company; ?>" size="30" id="company" name="company"><b>&nbsp;</b>
					<span class="editlinktip hasTip" title="<?php echo JText::_("GURU_TIP_COMPANY"); ?>" >
						<img border="0" src="components/com_guru/images/icons/tooltip.png">
					</span>
					</td>
				</tr>
				
				<tr>
					<td width="31%"><?php echo JText::_("GURU_PLUG_EMAIL"); ?><span class="error" style="color:#FF0000;">*</span></td>
					<td><input type="text" value="<?php echo $email; ?>" size="30" id="email" <?php echo $disable; ?> name="email"><b>&nbsp;</b>
					<span class="editlinktip hasTip" title="<?php echo JText::_("GURU_TIP_EMAIL"); ?>" >
						<img border="0" src="components/com_guru/images/icons/tooltip.png">
					</span>
					</td>
				</tr>
                
                <tr>
					<td width="31%"><?php echo JText::_("GURU_AUTHOR_GROUP"); ?><span class="error" style="color:#FF0000;">*</span></td>
					<td>
                    	<?php
                    		$groups_selected = JHTML::_('select.genericlist', UsersHelper::getGroups(), 'gid[]', 'size="10" multiple="multiple"', 'value', 'text', $groups);

                    		$groups_selected = str_replace("- Super Users", "", $groups_selected);
							$groups_selected = str_replace('value="8"', 'value="0"', $groups_selected);

							echo $groups_selected;
                    	?>
                        
                        <b>&nbsp;</b>
                        <span class="editlinktip hasTip" title="<?php echo JText::_("GURU_TIP_AU_STUDENT_GROUP"); ?>" >
                            <img border="0" src="components/com_guru/images/icons/tooltip.png">
                        </span>
					</td>
				</tr>
                				
			</table>


			<?php
				$user_sequential_courses = $this->getSequentialCourses($id);

            	if(intval($id) > 0 && is_array($user_sequential_courses) && count($user_sequential_courses) > 0){
            		$sequential_courses = json_decode($sequential_courses, true);
			?>
					<br /> <br />
					<table class="admintable" width="100%">
						<tr>
							<td width="28%" valign="top"><b><?php echo JText::_("GURU_ENABLE_SEQUENTIAL_LESSONS"); ?></b></td>

							<td  valign="top">
								<div id="sequential-courses">
									<ul>
										<?php
											foreach($user_sequential_courses as $key_course=>$course_details){
												$checked = "";

												if(in_array(intval($course_details["id"]), $sequential_courses)){
													$checked = 'checked="checked"';
												}
										?>
												<li>
													<input type="checkbox" name="sequential_courses[]" value="<?php echo intval($course_details["id"]); ?>" <?php echo $checked; ?> />
													<span class="lbl"></span>
													<?php echo trim($course_details["name"]); ?>	
												</li>
										<?php
											}
										?>
									</ul>
								</div>
							</td>
						</tr>
					</table>
			<?php
				}
			?>
		</fieldset>
	       
    <div id="ajax_response" style="display:none;"></div>
	<input type="hidden" name="option" value="com_guru" />
	<input type="hidden" name="id" value="<?php echo $id; ?>" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="controller" value="guruCustomers" />
</form>
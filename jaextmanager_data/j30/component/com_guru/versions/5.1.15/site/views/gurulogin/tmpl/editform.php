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
	$Itemid = JFactory::getApplication()->input->get("Itemid", "0", "raw");
	$document =JFactory::getDocument();
	$document->setTitle(JText::_("GURU_ALREADY_MEMBER"));
	$username = "";
	$email = "";
	$firstname = "";
	$lastname = "";
	$company = "";
	$id = 0;
	
	$session = JFactory::getSession();
	$registry = $session->get('registry');
	
	$username_session = $registry->get('username', NULL);
	$email_session = $registry->get('email', NULL);
	$firstname_session = $registry->get('firstname', NULL);
	$lastname_session = $registry->get('lastname', NULL);
	$company_session = $registry->get('company', NULL);
	
	if(isset($username_session)){
		$username = $username_session;
	}
	if(isset($email_session)){
		$email = $email_session;
	}
	if(isset($firstname_session)){
		$firstname = $firstname_session;
	}
	if(isset($lastname_session)){
		$lastname = $lastname_session;
	}
	if(isset($company_session)){
		$company = $company_session;
	}
	
	$configs = $this->configs;
	
	$terms_cond_student = $configs["0"]["terms_cond_student"];
	$terms_cond_student_content = $configs["0"]["terms_cond_student_content"];
	
	$charset = '0123456789AaBbCcDdEeFfGgHhIiJjKkLlMmNnOoPpQqRrSsTtUuVvWwXxYyZz';
	$code = '';
	$code_length = 5;

	for($i=0; $i < $code_length; $i++){
    	$code = $code.substr($charset, mt_rand(0, strlen($charset) - 1), 1);
	}
	
	$user = JFactory::getUser();
	$user_id = $user->id;
	$params = JComponentHelper::getParams('com_users');
	$allowUserRegistration = $params->get('allowUserRegistration');
	
	if($user_id == 0 && $allowUserRegistration == 0){
		$app = JFactory::getApplication();
		
		$helper = new guruHelper();
		$itemid_seo = $helper->getSeoItemid();
		$itemid_seo = @$itemid_seo["gurulogin"];
		
		if(intval($itemid_seo) > 0){
			$Itemid = intval($itemid_seo);
		}
		
		$app->redirect(JRoute::_("index.php?option=com_guru&view=guruLogin&Itemid=".intval($Itemid)));
		return true;
	}
?>

<script type="text/javascript" language="javascript">
	document.body.className = document.body.className.replace("modal", "");
</script>

<!-- <script type="text/javascript" src="<?php //echo JURI::root(); ?>media/jui/js/jquery.min.js"></script>
<script type="text/javascript" src="<?php //echo JURI::root(); ?>media/jui/js/bootstrap.min.js"></script>
<script type="text/javascript" src="<?php //echo JURI::root(); ?>media/system/js/mootools-core.js"></script>
<script type="text/javascript" src="<?php //echo JURI::root(); ?>media/system/js/core.js"></script>
<script type="text/javascript" src="<?php //echo JURI::root(); ?>media/system/js/mootools-more.js"></script> -->

<script language="javascript" type="text/javascript">
	function isEmail(string) {
		var str = string;
		return (str.search(/^\w+((-\w+)|(\.\w+))*\@[A-Za-z0-9]+((\.|-)[A-Za-z0-9]+)*\.[A-Za-z0-9]+$/) != -1);
	}
	
	function validateForm(field){ 
		var valid_entry = true;
		
		// validate firstname --------------------------------------------------------------
		if(field == "firstname"){
			if(document.adminForm.firstname.value==""){
				//document.getElementById("text_red_f").style.display="block";
				valid_entry = false;
			}
			else if(document.adminForm.firstname.value!=""){
				//document.getElementById("text_red_f").style.display="none";
			}
		}
		
		// validate lastname --------------------------------------------------------------
		if(field == "lastname"){
			if(document.adminForm.lastname.value==""){
				//document.getElementById("text_red_l").style.display="block";
				valid_entry = false;
			}
			else if(document.adminForm.lastname.value!=""){
				//document.getElementById("text_red_l").style.display="none";
			}
		}
		
		// validate email --------------------------------------------------------------
		if(field == "email"){
			if (document.adminForm.email.value==""){
				document.getElementById("text_red_reqr").style.display="block";
				valid_entry = false;
			}
			else if(document.adminForm.email.value!=""){
				document.getElementById("text_red_reqr").style.display="none";
			}
		}
		
		<?php
			if(JFactory::getApplication()->input->get('id', '0') == "0"){
		?>
				// validate username --------------------------------------------------------------
				if(field == "username"){
					if(document.getElementById('g_username').value  == ""){
						document.getElementById("text_red_requ").style.display="block";
						valid_entry = false;
					}
					else if(document.getElementById('g_username').value !=""){
						document.getElementById("text_red_requ").style.display="none";
					}
				}
				
				// validate password --------------------------------------------------------------
				if(field == "password"){
					if (document.adminForm.password.value==""){
						//document.getElementById("text_red_reqp").style.display="block";
						valid_entry = false;
					}
					else if(document.adminForm.password.value!=""){
						document.getElementById("text_red_reqp").style.display="none";
					}
				}
				
				// validate password_confirm --------------------------------------------------------------
				if(field == "password_confirm"){
					if (document.adminForm.password.value != document.adminForm.password_confirm.value) {
						//document.getElementById("text_red_reqp").style.display="block";
						document.getElementById("text_red_reqp2").style.display="block";
						valid_entry = false;
					}
					else if(document.adminForm.password.value == document.adminForm.password_confirm.value){
						document.getElementById("text_red_reqp").style.display="none";
						document.getElementById("text_red_reqp2").style.display="none";
					}
				}
		<?php 
			}
		?>
		
		// validate email --------------------------------------------------------------
		if(field == "email"){
			if (!isEmail(document.adminForm.email.value)){
				document.getElementById("text_red_req").style.display="block";
				valid_entry = false;
			}
			else if(isEmail(document.adminForm.email.value)){
				document.getElementById("text_red_req").style.display="none";
			}
		}
		
		
		username = document.getElementById("g_username").value;
		if(username != ""){
			htmlvalue = "0";
			var req = jQuery.ajax({
				method: 'get',
				url: '<?php echo JURI::root()."index.php?option=com_guru&controller=guruAuthor&tmpl=component&task=checkExistingUserU";?>&username='+username+'&email='+email+'&id=<?php echo intval($id); ?>',
				data: { 'do' : '1' },
				success: function(response){
					jQuery("#ajax_response_u").empty().append(response);
					
					check_return_u = document.getElementById("ajax_response_u").textContent;
					
					if(check_return_u != 0){
						if(trimString(check_return_u) == '222'){// not validate username
							document.getElementById("text_red_u").style.display="block";
							valid_entry = false;
						}
						else if(trimString(check_return_u) == '333'){// validate username
							document.getElementById("text_red_u").style.display="none";
						}
					}
					else if(trimString(check_return_u) == ''){// empty input
						document.getElementById("text_red_u").style.display="none";
						return false;
					}
				}
			});
		}
		
		email = document.getElementById("email").value;
		if(email != ""){
			htmlvalue = "0";
			var req = jQuery.ajax({
				method: 'get',
				url: '<?php echo JURI::root()."index.php?option=com_guru&controller=guruAuthor&tmpl=component&task=checkExistingUserE";?>&username='+username+'&email='+email+'&id=<?php echo intval($id); ?>',
				data: { 'do' : '1' },
				success: function(response){
					jQuery("#ajax_response_e").empty().append(response);
					
					check_return_e = document.getElementById("ajax_response_e").textContent;
		
					if(check_return_e != 0){
						if(trimString(check_return_e) == '111'){// not validate email
							document.getElementById("text_red").style.display="block";
							valid_entry = false;
						}
						else if(trimString(check_return_e) == '222'){// validate email
							document.getElementById("text_red").style.display="none";
						}
					}
					else if(trimString(check_return_e) == ''){// empty input
						document.getElementById("text_red").style.display="none";
					}
				}
			});
		}
		
		
		valid_entry = checkAllFields();
		
		document.adminForm.name.value = document.adminForm.firstname.value+" "+document.adminForm.lastname.value;
		return true;
	}
	
	function validateFormButton(){
		if(document.adminForm.firstname.value==""){
			alert("<?php echo JText::_("GURU_INSERT_FIRSTNAME"); ?>");
			return false;
		}
		
		if(document.adminForm.lastname.value==""){
			alert("<?php echo JText::_("GURU_INSERT_LASTNAME"); ?>");
			return false;
		}
		
		if(document.adminForm.email.value==""){
			alert("<?php echo JText::_("GURU_INSERT_EMAIL"); ?>");
			return false;
		}
		
		if (!isEmail(document.adminForm.email.value)){
			alert("<?php echo JText::_("GURU_PROVIDE_VALID_EMAIL"); ?>");
			return false;
		}
		
		<?php
			if(JFactory::getApplication()->input->get('id', '0') == "0"){
		?>
				if(document.getElementById('g_username').value==""){
					alert("<?php echo JText::_("GURU_INSERT_USERNAME"); ?>");
					return false;
				}
				
				if (document.adminForm.password.value==""){
					alert("<?php echo JText::_("GURU_INSERT_PASS"); ?>");
					return false;
				}
				
				if (document.adminForm.password.value != document.adminForm.password_confirm.value) {
					alert("<?php echo JText::_("GURU_MATCH_PASS"); ?>");
					return false;
				}
		<?php
			}
		?>
		
		username = document.getElementById("g_username").value;
		if(username != ""){
			htmlvalue = "0";
			var req = jQuery.ajax({
				method: 'get',
				url: '<?php echo JURI::root()."index.php?option=com_guru&controller=guruAuthor&tmpl=component&task=checkExistingUserU"; ?>&username='+username+'&email='+email+'&id=<?php echo intval($id); ?>',
				data: { 'do' : '1' },
				success: function(response){
					jQuery("#ajax_response_u").empty().append(response);
				}
			});
		}
		check_return_u = document.getElementById("ajax_response_u").innerHTML;
		
		
		email = document.getElementById("email").value;
		if(email != ""){
			htmlvalue = "0";
			var req = jQuery.ajax({
				method: 'get',
				url: '<?php echo JURI::root()."index.php?option=com_guru&controller=guruAuthor&tmpl=component&task=checkExistingUserE"; ?>&username='+username+'&email='+email+'&id=<?php echo intval($id); ?>',
				data: { 'do' : '1' },
				success: function(response){
					jQuery("#ajax_response_e").empty().append(response);
				}
			});
		}
		check_return_e = document.getElementById("ajax_response_e").innerHTML;
		
		if(check_return_e != 0){
			if(trimString(check_return_e) == '111'){// not validate email
				document.getElementById("text_red").style.display="block";
				alert("<?php echo JText::_("GURU_EMAIL_IN_USE"); ?>");
				valid_entry = false;
				return false;
			}
			else if(trimString(check_return_e) == '222'){// validate email
				document.getElementById("text_red").style.display="none";
			}
		}
		else if(trimString(check_return_e) == ''){// empty input
			document.getElementById("text_red").style.display="none";
		}
		
		check_return_u = document.getElementById("ajax_response_u").innerHTML;
		if(check_return_u != 0){
			if(trimString(check_return_u) == '222'){// not validate username
				document.getElementById("text_red_u").style.display="block";
				alert("<?php echo JText::_("GURU_USERNAME_IN_USE"); ?>");
				valid_entry = false;
				return false;
			}
			else if(trimString(check_return_u) == '333'){// validate username
				document.getElementById("text_red_u").style.display="none";
			}
		}
		else if(trimString(check_return_u) == ''){// empty input
			document.getElementById("text_red_u").style.display="none";
			return false;
		}
		
		if(!validateTerms()){
			return false;
		}
		
		document.adminForm.submit();
	}
	
	function checkAllFields(){
		// validate firstname --------------------------------------------------------------
		if(document.adminForm.firstname.value==""){
			return false;
		}
		
		// validate lastname --------------------------------------------------------------
		if(document.adminForm.lastname.value==""){
			return false;
		}
		
		// validate email --------------------------------------------------------------
		if(document.adminForm.email.value==""){
			return false;
		}
		
		<?php
			if(JFactory::getApplication()->input->get('id', '0') == "0"){
		?>
				// validate username --------------------------------------------------------------
				if(document.getElementById("g_username").value ==""){
					return false;
				}
				
				// validate password --------------------------------------------------------------
				if(document.adminForm.password.value==""){
					return false;
				}
				
				// validate password_confirm --------------------------------------------------------------
				if(document.adminForm.password.value != document.adminForm.password_confirm.value){
					return false;
				}
		<?php
			}
		?>
		
		// validate email --------------------------------------------------------------
		if(!isEmail(document.adminForm.email.value)){
			return false;
		}
		
		return true;
	}
	
	var request_processed = 0;        
	
	function trimString(str){
		str = str.toString();
		var begin = 0;
		var end = str.length - 1;
		while (begin <= end && str.charCodeAt(begin) < 33) { ++begin; }
		while (end > begin && str.charCodeAt(end) < 33) { --end; }
		return str.substr(begin, end - begin + 1);
	}
	
	function validateTerms(){
		<?php
			if($terms_cond_student == 1){
		?>
				terms_cond_student = document.getElementById("terms_cond_student");
				if(terms_cond_student.checked == false){
					alert("<?php echo JText::_("GURU_SELECT_TERMS_AND_COND"); ?>");
					return false;
				}

				terms_cond_student_gdpr = document.getElementById("terms_cond_student_gdpr");
				if(terms_cond_student_gdpr.checked == false){
					alert("<?php echo JText::_("GURU_SELECT_TERMS_AND_COND_GDPR"); ?>");
					return false;
				}
		<?php
			}
		?>
		return true;
	}
	
	function randomString() {
		var chars = "0123456789AaBbCcDdEeFfGgHhIiJjKkLlMmNnOoPpQqRrSsTtUuVvWwXxYyZz";
		var string_length = 5;
		var randomstring = '';
		for (var i=0; i<string_length; i++) {
			var rnum = Math.floor(Math.random() * chars.length);
			randomstring += chars.substring(rnum,rnum+1);
		}
		return randomstring;
	}
</script>

<div class="gru-register">
	<form class="uk-form uk-form-horizontal"  method="post" name="adminForm" id="adminForm" onsubmit="return validateTerms()">
        <fieldset>
            <!-- Basic info -->
            <legend><?php echo JText::_('GURU_PROFILE_SETTINGS');?></legend>
        
            <div class="uk-form-row">
                <label class="uk-form-label" for="name">
                    <?php echo JText::_("GURU_FIRS_NAME");?>:
                    <span class="uk-text-danger">*</span>
                </label>
                <div class="uk-form-controls">
                    <input onchange="validateForm('firstname');" type="text" class="inputbox" size="30" id="firstname" name="firstname" value="<?php echo $firstname; ?>" />
                </div>
            </div>
            
            <div class="uk-form-row">
                <label class="uk-form-label" for="name">
                    <?php echo JText::_("GURU_LAST_NAME");?>:
                    <span class="uk-text-danger">*</span>
                </label>
                <div class="uk-form-controls">
                    <input onchange="validateForm('lastname');" type="text" class="inputbox" size="30" id="lastname" name="lastname" value="<?php echo $lastname; ?>"/>
                </div>
            </div>
            
            <div class="uk-form-row">
                <label class="uk-form-label" for="name">
                    <?php echo JText::_("GURU_COMPANY");?>:
                </label>
                <div class="uk-form-controls">
                    <input  onchange="validateForm('company');" type="text" class="inputbox" size="30" id="company" name="company" value="<?php echo $company; ?>"/>
                </div>
            </div>
            
            <div class="uk-form-row">
                <label class="uk-form-label" for="name">
                    <?php echo JText::_("GURU_EMAIL");?>:
                    <span class="uk-text-danger">*</span>
                </label>
                <div class="uk-form-controls">
                    <input onchange="validateForm('email');" type="text" <?php if (isset($cust) && isset($cust->id)){?> disabled <?php }?> class="inputbox" size="30" id="email" name="email" value="<?php echo $email; ?>"/>
                    <span class="g_level_remark" style="font-size:0.8em"><?php echo JText::_('DSEMAILNOTE'); ?></span>
                </div>
            </div>
            
            <div class="uk-form-row">
                <label class="uk-form-label" for="name">
                    &nbsp;
                </label>
                <div class="uk-form-controls">
                    <div class="pull-left alert alert-warning" style="display:none;" id="text_red"><?php echo JText::_("GURU_EMAIL_IN_USE"); ?></div>
                    <div class="pull-left alert alert-warning" style="display:none;" id="text_red_req"><?php echo JText::_("DSINVALID_EMAIL"); ?></div>
                    <div class="pull-left alert alert-warning" style="display:none;" id="text_red_reqr"><?php echo JText::_("DSALL_REQUIRED_FIELDS"); ?></div>
                </div>
            </div>
            
            <?php
                $config = $this->configs;
                $max_upload = (int)(ini_get('upload_max_filesize'));
                $max_post = (int)(ini_get('post_max_size'));
                $memory_limit = (int)(ini_get('memory_limit'));
                $upload_mb = min($max_upload, $max_post, $memory_limit);
                if($upload_mb == 0){
                    $upload_mb = 10;
                }
                $upload_mb *= 1048576; //transform in bytes
                $doc = JFactory::getDocument();
                
                $config_author = json_decode($config["0"]["authorpage"]);
                $author_t_prop = $config_author->author_image_size_type == "0" ? "width" : "heigth";
    
                $doc->addScriptDeclaration('
                    jQuery.noConflict();
                    jQuery(function(){
                        function createUploader(){
                            var uploader = new qq.FileUploader({
                                element: document.getElementById(\'fileUploader\'),
                                action: \''.JURI::root().'index.php?option=com_guru&controller=guruLogin&tmpl=component&format=raw&task=upload_ajax_image\',
                                params:{
                                    folder:\'customers\',
                                    mediaType:\'image\',
                                    size: '.$config_author->author_image_size.',
                                    type: \''.$author_t_prop.'\'
                                },
                                onSubmit: function(id,fileName){
                                    jQuery(\'.qq-upload-list li\').css(\'display\',\'none\');
                                },
                                onComplete: function(id,fileName,responseJSON){
                                    //alert(\'id: \'+ id + \'; filename:\' + fileName);
                                    if(responseJSON.success == true){						
                                        jQuery(\'.qq-upload-success\').append(\'- <span style="color:#387C44;">Upload successful</span>\');
                                        if(responseJSON.locate) {
                                            jQuery(\'#view_imagelist23\').attr("src", \''.JURI::root().'\'+responseJSON.locate +"/"+ fileName+"?timestamp=" + new Date().getTime());
                                            jQuery(\'#image\').val("/"+responseJSON.locate +"/"+ fileName);
                                        }
                                    }
                                },
                                allowedExtensions: [\'jpg\', \'jpeg\', \'png\', \'gif\', \'JPG\', \'JPEG\', \'PNG\', \'GIF\', \'xls\', \'XLS\'],
                                sizeLimit: '.$upload_mb.',
                                multiple: false,
                                maxConnections: 1
                            });           
                        }
                        createUploader();
                    });
                ');
                //$doc->addScript('components/com_guru/js/fileuploader.js');
                $doc->addStyleSheet('components/com_guru/css/fileuploader.css');
            ?>
            
            <div class="uk-form-row">
                <label class="uk-form-label" for="name">
                    <?php echo JText::_("GURU_UPLOAD_IMAGE");?>
                </label>
                <div class="uk-form-controls">
                    <span>
                       <div id="fileUploader"></div>
                       <input type="hidden" name="image" id="image" value="" />
                    </span>    
                    <div id='authorImageSelected'>
                        <img id='view_imagelist23' name='view_imagelist' src='<?php echo JURI::root()."components/com_guru/images/blank.png"; ?>'/>
                    </div>
                </div>
            </div>
		
            <!-- Login info -->
            <legend><?php echo JText::_('GURU_LOGIN_INFORMATIONS');?></legend>
            
            <div class="uk-form-row">
                <label class="uk-form-label" for="name">
                    <?php echo JText::_("GURU_PROFILE_USERNAME");?>:
                    <span class="uk-text-danger">*</span>
                </label>
                <div class="uk-form-controls">
                    <input onchange="validateForm('username');" type="text" <?php if (isset($cust) && isset($cust->id)){?> disabled <?php } ?> class="inputbox" size="30" id="g_username" name="username" value="<?php echo $username; ?>" />
                </div>
            </div>
            
            <div class="uk-form-row">
                <label class="uk-form-label" for="name">
					&nbsp;
                </label>
                <div class="uk-form-controls">
					<div class="pull-left alert alert-warning" style="display:none;" id="text_red_u"><?php echo JText::_("GURU_USERNAME_IN_USE"); ?></div>
					<div class="pull-left alert alert-warning" style="display:none;" id="text_red_requ"><?php echo JText::_("DSALL_REQUIRED_FIELDS"); ?></div>
                </div>
            </div>
            
            <?php
				if(!isset($cust) && !isset($cust->id)){
			?>
            		<div class="uk-form-row">
                        <label class="uk-form-label" for="name">
                            <?php echo JText::_("GURU_PROFILE_REG_PSW");?>:
                            <span class="uk-text-danger">*</span>
                        </label>
                        <div class="uk-form-controls input-group">
                        	<span class="input-group-addon"><i class="fa fa-eye"></i></span>
                            <input onchange="validateForm('password');" type="password" class="inputbox" size="30" id="password" name="password" />
                        </div>
                    </div>
                    <div class="uk-form-row">
                        <label class="uk-form-label" for="name">
                            &nbsp;
                        </label>
                        <div class="uk-form-controls">
                            <div class="pull-left alert alert-warning" style="display:none;" id="text_red_reqp"><?php echo JText::_("DSCONFIRM_PASSWORD_MSG"); ?></div>
                        </div>
                    </div>
                    
                    <div class="uk-form-row">
                        <label class="uk-form-label" for="name">
                            <?php echo JText::_("GURU_PROFILE_REG_PSW2");?>:
                            <span class="uk-text-danger">*</span>
                        </label>
                        <div class="uk-form-controls input-group">
                        	<span class="input-group-addon"><i class="fa fa-eye"></i></span>
                            <input onchange="validateForm('password_confirm');" type="password" class="inputbox" size="30" id="password_confirm" name="password_confirm"/>
                        </div>
                    </div>
                    <div class="uk-form-row">
                        <label class="uk-form-label" for="name">
                            &nbsp;
                        </label>
                        <div class="uk-form-controls">
                            <div class="pull-left alert alert-warning" style="display:none;" id="text_red_reqp2"><?php echo JText::_("DSCONFIRM_PASSWORD_MSG"); ?></div>
                        </div>
                    </div>
            <?php
				}
			?>
            
            <?php
				if($terms_cond_student == 1){
					$config = JFactory::getConfig();
			?>
            		<div class="uk-form-row">
                        <label class="uk-form-label" for="name">
                            &nbsp;
                        </label>
                        <div class="uk-form-controls">
                            <input type="checkbox" value="1" name="terms_cond_student_gdpr" id="terms_cond_student_gdpr" />
                            <span class="lbl"></span>
                            <div class="gdpr-cond-message"> <?php echo JText::_("GURU_TERMS_AND_COND_GDPR_1")." '".$config->get('sitename')."' ".JText::_("GURU_TERMS_AND_COND_GDPR_2"); ?> </div>
                        </div>
                    </div>

            		<div class="uk-form-row">
                        <label class="uk-form-label" for="name">
                            &nbsp;
                        </label>
                        <div class="uk-form-controls">
                            <input type="checkbox" value="1" name="terms_cond_student" id="terms_cond_student" />
                            <span class="lbl"></span>
                            <a href="#" onclick='window.open("<?php echo JURI::root()."index.php?option=com_guru&controller=guruLogin&task=terms&tmpl=component"; ?>", "", "width=500, height=400")'><?php echo JText::_("GURU_TERMS_AND_COND"); ?></a>
                        </div>
                    </div>
            <?php
            	}
			?>
            
            <?php
				$show_captcha = $configs["0"]["captcha"];
				
				if($show_captcha == "1"){
					$plugin = JPluginHelper::getPlugin('captcha', 'recaptcha');
					
					if(isset($plugin->params)){
						$params = new JRegistry($plugin->params);
						$public_key = $params->get("public_key", "");
						$private_key = $params->get("private_key", "");
						
						if(trim($public_key) != "" && trim($private_key) != ""){  
 							JPluginHelper::importPlugin('captcha', 'recaptcha');
       						JFactory::getApplication()->triggerEvent('onInit');
   			?>

       						<div class="uk-form-row">
                                <label class="uk-form-label" for="name">
                                </label>
                                <div class="uk-form-controls">
                                    <?php echo JFactory::getApplication()->triggerEvent('onDisplay', array('', '', 'class="g-recaptcha"'))[0] ?>
                                </div>
                            </div>
			<?php
						}
					}
            	}
			?>
            
		</fieldset>
        
        <div class="uk-form-row uk-margin-large-top">
            <label class="uk-form-label" for=""></label>
            <div class="uk-form-controls">
                <input type="button" onclick="history.go(-1);" class="uk-button uk-button-danger" value="<?php echo JText::_("GURU_CANCEL")?>" />
                <?php 
                	if($configs["0"]["gurujomsocialregstudent"]== 0){
                ?>
						<input id="guru_create_account" type="button" class="uk-button uk-button-primary" onclick="javascript:validateFormButton();" value="<?php echo JText::_("GURU_CREATE_ACCOUNT")?>" />
				<?php
					}
					else{
				?>
						<input type="submit" class="uk-button uk-button-primary" value="<?php echo JText::_("GURU_NEXT")?>" />
				<?php
					}
				?>
			</div>
		</div>
        
        <?php
			$x = intval(JFactory::getApplication()->input->get("cid", ""));
			if( $x == 0){
				$course_id = intval(JFactory::getApplication()->input->get("course_id", ""));
			}
			else{
				$course_id = intval(JFactory::getApplication()->input->get("cid", ""));
			}
		?>
         
        <div id="ajax_response_u" style="display:none;"></div>  
        <div id="ajax_response_e" style="display:none;"></div>    
        <input type="hidden" name="Itemid" value="<?php echo $Itemid;?>" />
        <input type="hidden" name="images" value="" />                
        <input type="hidden" name="option" value="com_guru" />
        <input type="hidden" name="id" value="" />
        <input type="hidden" name="task" value="saveCustomer" />
        <input type="hidden" name="returnpage" value="<?php echo (JFactory::getApplication()->input->get("returnpage", ""));?>" />		
        <input type="hidden" name="controller" value="guruLogin" />
        <input type="hidden" name="course_id" value="<?php echo $course_id; ?>" />
        <input type="hidden" name="lesson_id" value="<?php echo JFactory::getApplication()->input->get("lesson_id", "0"); ?>" />  
        <input type="hidden" name="registered_user" value="1" />  
        <input type="hidden" name="guru_teacher" value="1" />  
        <input type="hidden" name="studentpage" value="studentpage" />
	</form>
</div>
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
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.html.pagination' );
JHtml::_('bootstrap.framework');
JHTML::_('behavior.tooltip');
JHTML::_('behavior.framework');
JHtml::_('behavior.calendar');
JHtml::_('behavior.modal');

$doc =JFactory::getDocument();
require_once (JPATH_COMPONENT_ADMINISTRATOR.DIRECTORY_SEPARATOR."models".DIRECTORY_SEPARATOR."gurucertificate.php");

$program = $this->program;

if(isset($program->id) && $program->id != NULL ){
	$chb_free_courses = $this->selectedCoursesforFree();
	$step_access_courses = $this->getStepAccessCourses();
	$selected_course = $this->getSelectedCourse();
}

$lists = $program->lists;

$free_limit = "";

if(isset($program->free_limit) && trim($program->free_limit) != ""){
	$free_limit = intval($program->free_limit);
}

if($free_limit == 0){
	$free_limit = "";
}

//$editorul  = JFactory::getEditor(); 
$editorul  = new JEditor(JFactory::getConfig()->get("editor"));
//echo'<pre>';print_r($editorul);echo'</pre>';die;
$certificates_details = guruAdminModelguruCertificate::getCertificatesDetails();
$mmediam = ((array)$this->mmediam) ? $this->mmediam : array();

if(isset($this->mmediam_preq)){
	foreach($this->mmediam_preq as $element){
		$vect[] = $element->id;
	}
}
$config = guruAdminModelguruProgram::getConfigs();
$config_courses = json_decode($config->psgspage);
$courses_t_prop = $config_courses->courses_image_size_type == "0" ? "width" : "heigth";

$list_authors = $this->listAuthors();

$dateformat = $this->gurudateformat;

$listDirn = "asc";
$listOrder = "ordering";
$saveOrderingUrl = 'index.php?option=com_guru&controller=guruPrograms&task=saveOrderExercices&tmpl=component';
JHtml::_('sortablelist.sortable', 'articleList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('dropdown.init');

$max_upload = (int)(ini_get('upload_max_filesize'));
$max_post = (int)(ini_get('post_max_size'));
$memory_limit = (int)(ini_get('memory_limit'));
$upload_mb = min($max_upload, $max_post, $memory_limit);
if($upload_mb == 0) {$upload_mb = 10;}
$upload_mb*=1048576; //transform in bytes


$cid = JFactory::getApplication()->input->get("cid", array(), "raw");
$id = intval(@$cid["0"]);

$doc->addScript('components/com_guru/js/freecourse.js');
$doc->addStyleSheet('components/com_guru/css/fileuploader.css');

?>

<style>
	#toolbar-publish .btn-warning{
		background-color: #faa732;
		background-image: linear-gradient(to bottom, #f89406, #c67605) !important;
		background-repeat: repeat-x;
		border: 0 none;
		border-radius: 4px;
		color: #ffffff;
		padding: 3px 12px;
		text-shadow: unset;
	}
	
	#toolbar-publish .btn-warning .icon-publish::before{
		color:#FFFFFF !important;
	}
</style>

<script type="text/javascript" src="components/com_guru/js/fileuploader.js"></script>
<script type="text/javascript" language="javascript">
	jQuery(function(){
		function createUploader(){            
			var uploader = new qq.FileUploader({
				element: document.getElementById('fileUploader'),
				action: '<?php echo JURI::root().'administrator/index.php?option=com_guru&controller=guruConfigs&tmpl=component&format=raw&task=guru_file_uploader'; ?>',
				params:{
					folder:'courses',
					mediaType:'image',
					size: '<?php echo $config_courses->courses_image_size; ?>',
					type: '<?php echo $courses_t_prop; ?>'
				},
				onSubmit: function(id,fileName){
					jQuery('.qq-upload-list li').css('display','none');
				},
				onComplete: function(id,fileName,responseJSON){
					if(responseJSON.success == true){
						jQuery('.qq-upload-success').append('- <span style="color:#387C44;">Upload successful</span>');
						if(responseJSON.locate) {
							jQuery('#view_imagelist23').attr("src", "../"+responseJSON.locate +"/"+ fileName+"?timestamp=" + new Date().getTime());
							jQuery('#image').val(responseJSON.locate +"/"+ fileName);
						}
					}
				},
				allowedExtensions: ['jpg', 'jpeg', 'png', 'gif', 'JPG', 'JPEG', 'PNG', 'GIF', 'xls', 'XLS'],
				sizeLimit: '<?php echo $upload_mb; ?>',
				multiple: false,
				maxConnections: 1
			});           
		}
		createUploader();
	});
	
	jQuery(document).ready(function($) {
		document.getElementById("free_courses").style.display = '<?php echo (@$step_access_courses == 0 ? "block" : "none"); ?>';
		
		jQuery('#toolbar-publish .btn').attr('class', 'btn-warning');
	});
	
	
	jQuery(function(){
		function createUploader1(){            
			var uploader = new qq.FileUploader({
				element: document.getElementById('fileUploader1'),
				action: '<?php echo JURI::root().'administrator/index.php?option=com_guru&controller=guruConfigs&tmpl=component&format=raw&task=guru_file_uploader'; ?>',
				params:{
					folder:'courses',
					mediaType:'image',
					size: '<?php echo $config_courses->courses_image_size; ?>',
					type: '<?php echo $courses_t_prop; ?>'
				},
				onSubmit: function(id,fileName){
					jQuery('.qq-upload-list li').css('display','none');
				},
				onComplete: function(id,fileName,responseJSON){
					if(responseJSON.success == true){
						jQuery('.qq-upload-success').append('- <span style="color:#387C44;">Upload successful</span>');
						if(responseJSON.locate) {
							jQuery('#view_imagelist24').attr("src", "../"+responseJSON.locate +"/"+ fileName+"?timestamp=" + new Date().getTime());
							jQuery('#image_avatar').val(responseJSON.locate +"/"+ fileName);
						}
					}
				},
				allowedExtensions: ['jpg', 'jpeg', 'png', 'gif', 'JPG', 'JPEG', 'PNG', 'GIF', 'xls', 'XLS'],
				sizeLimit: '<?php echo $upload_mb; ?>',
				multiple: false,
				maxConnections: 1
			});           
		}
		createUploader1();
	});
	jQuery(document).ready(function($) {
		document.getElementById("free_courses").style.display = '<?php echo (@$step_access_courses == 0 ? "block" : "none"); ?>';
	});
</script>

<style>
	#rowsmedia {
		background-color:#eeeeee;
	}
	#rowsmedia tr{
		background-color:white;
	}
	#rowsmainmedia {
		background-color:#eeeeee;
	}
	#rowsmainmedia tr{
		background-color:#eeeeee;
	}
</style>
<script language="JavaScript" type="text/javascript" src="<?php echo JURI::base(); ?>components/com_guru/js/freecourse.js"></script>
<script language="javascript" type="text/javascript">
	function checkIfCertificateIsSet(){
		chb_free_courses = document.getElementById("chb_free_courses").checked;
		if(chb_free_courses == true){
			if(document.getElementById("step_access_courses").value == 2){// free for guest
				if(document.getElementById("certificate_setts").value != 1){ // not selected "no certificate"
					document.getElementById("certificate_setts").value = 1;
					alert("<?php echo JText::_("COM_GURU_NO_CERTIFICATE_2"); ?>");
					return true;
				}
			}
		}
	}
	
	function ChangeTermCourse(nb, id){
		chb_free_courses = document.getElementById("chb_free_courses").checked;
		if(chb_free_courses == true){
			if(document.getElementById("step_access_courses").value == 2){// free for guest
				if(nb != 1){ // select certificate
					document.getElementById("certificate_setts").value = 1;
					alert("<?php echo JText::_("COM_GURU_NO_CERTIFICATE_1"); ?>");
					return false;
				}
			}
		}
		
		if(nb == 4 || nb == 6 ){
			document.getElementById('avg_certificate').style.display = 'table-row';
			document.getElementById('recording_certificate').style.display = 'none';
			
			jQuery.ajax({
  				 url: 'index.php?option=com_guru&controller=guruPrograms&task=savenbquizzes&id='+id,
   			});
		}
		else if(nb == 7){
			document.getElementById('avg_certificate').style.display = 'none';
			document.getElementById('recording_certificate').style.display = 'inline-block';
			
			jQuery.ajax({
  				 url: 'index.php?option=com_guru&controller=guruPrograms&task=savenbquizzes&id='+id,
   			});
		}
		else{
			document.getElementById('avg_certificate').style.display = 'none';
			document.getElementById('recording_certificate').style.display = 'none';
		}

		if(nb == 1){
			document.getElementById('coursecertifiactemsg').style.display = 'none';
		}
		else{
			document.getElementById('coursecertifiactemsg').style.display = 'table-row';
		}
	}

	function ChangeTermCourseCompleted(nb){
		if(nb == 4 || nb == 6 ){
			document.getElementById('avg_certificate_course_term').style.display = 'table-row';
			document.getElementById('recording_course_term').style.display = 'none';
		}
		else if(nb == 7){
			document.getElementById('avg_certificate_course_term').style.display = 'none';
			document.getElementById('recording_course_term').style.display = 'inline-block';
		}
		else{
			document.getElementById('avg_certificate_course_term').style.display = 'none';
			document.getElementById('recording_course_term').style.display = 'none';
		}
	}
		
	function IsNumeric(sText){
		var ValidChars = "0123456789.";
		var IsNumber=true;
		var Char;
		for (i = 0; i < sText.length && IsNumber == true; i++) { 
			Char = sText.charAt(i); 
			if (ValidChars.indexOf(Char) == -1)  { 
				IsNumber = false;
			}
		}
		return IsNumber;
	}
		
	function isFloat(nr){
		return parseFloat(nr.match(/^-?\d*(\.\d+)?$/)) > 0;
	}
		
	Joomla.submitbutton = function(pressbutton){
	//function submitbutton(pressbutton) {
		var form = document.adminForm;
		var prisfinalq = document.getElementById('final_quizzes').value;
		var hasquiz = <?php if(isset($program->hasquiz)){echo $program->hasquiz;} else{ echo 0;}?>;
		var certificateterm = document.getElementById('certificate_setts').value;
		var avgval = document.getElementById('avg_cert').value;
		
		var sDate = document.getElementById('startpublish').value;
		sDate = sDate.split(" ");
		sDate = sDate[0];
		
		var eDate = document.getElementById('endpublish').value;
		eDate = eDate.split(" ");
		eDate = eDate[0];
		
		sDate = new Date(sDate+"");
		eDate = new Date(eDate+"");
		
		sDate = sDate.getTime();
		eDate = eDate.getTime();
		
		if (pressbutton == 'save' || pressbutton == 'apply' || pressbutton == 'save2new') {
			name = form["name"].value;
			alias = form["alias"].value;
			same = false;

			/*var req = jQuery.ajax({
				async: false,
				method: 'get',
				url: 'index.php?option=com_guru&controller=guruPrograms&tmpl=component&format=raw&task=check_values&action=check_values&name='+name+'&alias='+alias+'&id='+<?php echo $id; ?>,
				data: { 'do' : '1' },
				onComplete: function(response){
					if(response[0].textContent.trim() == "exist"){
						same = true;
						alert("<?php echo JText::_("GURU_ALERT_EXISTING_NAME_ALIAS"); ?>");
						return false;
					}
				}
			})*/

			jQuery.ajax({
				async: false,
				url: 'index.php?option=com_guru&controller=guruPrograms&tmpl=component&format=raw&task=check_values&action=check_values&name='+name+'&alias='+alias+'&id='+<?php echo $id; ?>,
				success: function(response) {
		            if(response == "exist"){
						same = true;
						alert("<?php echo JText::_("GURU_ALERT_EXISTING_NAME_ALIAS"); ?>");
						return false;
					}
				}
		    });
			
			if(same){
				return false;
			}
			
			for_all_students = true;
			
			if(document.getElementById("step_access_courses").value == 1){ // for members
				groups = document.getElementsByName("groups[]");
				nr_checked_groups = 0;
				
				for(var i=0; i<groups.length; i++){
					group = groups[i];
					
					if(group.checked){
						nr_checked_groups ++;
					}
				}
				
				if(nr_checked_groups > 0 && nr_checked_groups < groups.length){
					for_all_students = false;
				}
			}
			else if(document.getElementById("step_access_courses").value == 0){ // for students
				for_all_students = false;
			}
			
			chb_free_courses = document.getElementById("chb_free_courses").checked;
			step_access_courses = document.getElementById("step_access_courses").value;
			course_type = document.getElementById("course_type").value;
			
			if(chb_free_courses == true && step_access_courses == "2" && course_type == "1"){
				alert("<?php echo JText::_("GURU_NOT_FREE_SEQUENTIAL_FOR_GUESTS"); ?>");
				return false;
			}
			
			if(chb_free_courses == false || (chb_free_courses == true && !for_all_students) ){
				 // start if check if price is correct
				 k=0;
				 subscription_added = false;
				 while(eval(document.getElementById("subscription_price_"+k))){
				 	if(document.getElementById("subscriptions_"+k).checked == true){
						subscription_added = true;
						subscription_price = document.getElementById("subscription_price_"+k).value;
						if(subscription_price != ""){
							/*if(!isFloat(subscription_price) || subscription_price <= 0){
								alert("<?php echo JText::_("GURU_ALERT_INVALID_PRICE"); ?>");
								return false;
							}*/
						}
						else{
							/*alert("<?php echo JText::_("GURU_ALERT_INVALID_PRICE"); ?>");
							return false;*/
						}
					}
					k++;
				 }
				 
				 if(!subscription_added){
				 	if(chb_free_courses == true){
						/*alert("<?php echo JText::_("GURU_ALERT_ADD_PRICE_FOR_NO_GROUPS"); ?>");
						return false;*/
					}
					else{
						alert("<?php echo JText::_("GURU_ALERT_ADD_PRICE"); ?>");
						return false;
					}
				 }
				 
				 // chekc if default plan is selected
				subscriptions = document.getElementsByName("subscription_default");
				checked = false;
				for(i=0; i<subscriptions.length; i++){
					if(subscriptions[i].checked){
						checked = true;
						break;
					}
				}
				
				if(!checked){
					alert("<?php echo JText::_("GURU_ALERT_ADD_DEFAULT_PLAN"); ?>");
					return false;
				}
				 
				 k = 0;
				 while(eval(document.getElementById("renewal_price_"+k))){
				 	if(document.getElementById("renewals_"+k).checked == true){
						renewal_price = document.getElementById("renewal_price_"+k).value;
						if(renewal_price != ""){
							/*if(!isFloat(renewal_price) || renewal_price <= 0){
								alert("<?php echo JText::_("GURU_ALERT_INVALID_PRICE"); ?>");
								return false;
							}*/
						}
						else{
							/*alert("<?php echo JText::_("GURU_ALERT_INVALID_PRICE"); ?>");
							return false;*/
						}
					}
					k++;
				 }
				 // stop if check if price is correct
			 }
			 
			 
			var cboxes = document.getElementsByName('author[]');
			var len = cboxes.length;
			nr_selected_authors = 0;
			same_plan = 0;
			same_commission = true;
			
			for (var i=0; i<len; i++) {
				if(cboxes[i].checked){
					nr_selected_authors ++;
					author_id = cboxes[i].value;
					
					if(same_plan == 0){
						same_plan = document.getElementById('commission-'+author_id).value;
					}
					else{
						next_plan = document.getElementById('commission-'+author_id).value;
						if(same_plan != next_plan){
							same_commission = false;
						}
					}
				}
			}
			 
			 if((certificateterm == 3 || certificateterm == 5) && prisfinalq == 0 && 0 != <?php echo intval($program->id); ?>){
				alert("<?php echo JText::_("GURU_ALERT_CERT_FEX");?>");
				return false;
			 }
			 else if((certificateterm == 4 || certificateterm == 6) && hasquiz == 0 && 0 != <?php echo intval($program->id); ?>){
			 	alert("<?php echo JText::_("GURU_ALERT_CERT_QAVG");?>");
				return false;
			 }
			 else if((isNaN(avgval) || parseInt(avgval) < 0) && pressbutton != 'cancel' ){
			 	alert("<?php echo JText::_("GURU_INVALID_NUMBER_AVG");?>");
				return false;
			 }
			 else if (form['name'].value == "") {
					alert( "<?php echo JText::_("GURU_CS_PLSINSNAME");?>" );
			 }
			 else if (nr_selected_authors == 0) {
					alert( "<?php echo JText::_("GURU_CS_PLSINSAUTHOR");?>" );				
			 }
			 else if(!same_commission){
			 	alert("<?php echo JText::_("GURU_SAME_COMMISSION_FOR_AUTHORS");?>");
			 }
			 else if (form['catid'].value == -1) {
				alert("<?php echo JText::_("GURU_PR_PLSINSCATEG");?>");				 
			 }
			else if(document.getElementById('startpublish').value == ''  ){
				alert("<?php echo JText::_("GURU_INVALID_DATE");?>");
				document.getElementById('startdate').value="";
				return false;
			}
			else if(document.getElementById('startpublish').value != '' && document.getElementById('endpublish').value != '' && sDate > eDate)
			{
				alert("<?php echo JText::_("GURU_DATE_GRATER");?>");
				return false;
			}	
			else if (document.getElementById('chb_free_courses').checked == true){
			 	if (form['step_access_courses'].value == 0 && form['selected_course[]'].value == 0 )
				{
					alert("<?php echo JText::_("GURU_SELECTED_COURSE_ALERT");?>");		
				}
				else {
					//submitform( pressbutton );

					form.task.value = pressbutton;
					form.submit();
				}
			}
			else if(certificateterm == 7){
			 	record_hour = document.getElementById("record_hour").value;
			 	record_min = document.getElementById("record_min").value;

			 	if(record_hour == "" && record_min == ""){
			 		alert("<?php echo addslashes(JText::_("GURU_ALERT_NO_COURSE_RECORD")); ?>");
			 		return false;
			 	}

			 	if(record_hour != "" && parseInt(record_hour) < 0){
			 		alert("<?php echo addslashes(JText::_("GURU_ALERT_INVALID_RECORD")); ?>");
			 		return false;
			 	}

			 	if(record_min != "" && parseInt(record_min) < 0){
			 		alert("<?php echo addslashes(JText::_("GURU_ALERT_INVALID_RECORD")); ?>");
			 		return false;
				}

				if(pressbutton=='apply') {
					//submitform('apply32');

					form.task.value = 'apply32';
					form.submit();
				}
				else{
					//submitform( pressbutton );

					form.task.value = pressbutton;
					form.submit();
				}
			}
			else { 
				if(pressbutton=='apply'){
					//submitform('apply32');

					form.task.value = 'apply32';
					form.submit();
				}
				else{
					//submitform( pressbutton );

					form.task.value = pressbutton;
					form.submit();
				}
			}
		}
		else{  
			if(pressbutton=='apply') {
				//submitform('apply32');

				form.task.value = 'apply32';
				form.submit();
			} 
			else {
				//submitform( pressbutton );

				form.task.value = pressbutton;
				form.submit();
			}
		}
	}

	function typevar( variable ){
		return( typeof( variable ) );
	}

	function delete_temp(i){
		document.getElementById('tr'+i).style.display = 'none';
		document.getElementById('mediafiletodel').value =  document.getElementById('mediafiletodel').value+','+i;
	}

	function delete_temp_preq(i){
		var liveadded = document.getElementById('liveadded').value;
		var temp = new Array();
		var ok = true;
		temp = liveadded.split('|');
		document.getElementById('tr'+i).style.display = 'none';
		document.getElementById('preqfiletodel').value =  document.getElementById('preqfiletodel').value+','+i;
		for(var j=0;j<=temp.length-1;j++){
			if (document.getElementById('tr'+temp[j]).style.display != 'none') {
				ok = false;
			}
		}
	<?php 
		if(isset($vect)){
			echo "var exist = new Array();";
			$count=0;
			foreach($vect as $element){
				echo "exist[".$count."]=".$element.";";
				$count++;
			}
		} 
		else {
			echo "var exist=0;";
		}
	 ?> 	
		if(typevar(exist)!='number'){
			for(var j=0;j<=exist.length-1;j++){
				if (document.getElementById('2tr'+exist[j]).style.display != 'none') {
					ok = false;
				}
			}
		}
		if(ok == true) {
			document.getElementById('rowspreq').style.display = 'none';
		}
	}

	function delete_temp2_preq(i){
		var liveadded = document.getElementById('liveadded').value;
		var temp = new Array();
		var ok = true;
		temp = liveadded.split('|'); 
		temp['test']='b';
		document.getElementById('2tr'+i).style.display = 'none';
		document.getElementById('preqfiletodel').value =  document.getElementById('preqfiletodel').value+','+i;
		if(temp.length>1){
			for(var j=0;j<=temp.length-1;j++){
				if (document.getElementById('tr'+temp[j]).style.display != 'none') {
					ok = false;
				}
			}
		}
		<?php 
		if(isset($vect)){
			echo "var exist = new Array();";
			$count=0;
			foreach($vect as $element){
				echo "exist[".$count."]=".$element.";";
				$count++;
			}
		} 
		else {
			echo "var exist=0;";
		}
		?>
		if(typevar(exist)!='number'){
			for(var j=0;j<=exist.length-1;j++){
				if (document.getElementById('2tr'+exist[j]).style.display != 'none') {
					ok = false;
				}
			}
		}
		if(ok == true) {
			document.getElementById('rowspreq').style.display = 'none';
		}
	}

	function deleteMedia(id){
		var mediafiles;
		document.getElementById('tr'+id).style.display="none";
		mediafiles=document.getElementById('mediafiles').value;
		array=new Array();
		array=mediafiles.split(",");
		for(i=0;i<array.length;i++){
   			if(array[i]==id){
     			array.splice(i,1);
			}
		}
		document.getElementById('mediafiles').value=array.toString();
	}
	
	function deleteCourse(id){
		var mediafiles;
		document.getElementById('tr_'+id).style.display="none";
		mediafiles=document.getElementById('preqfiles').value;
		array=new Array();
		array=mediafiles.split(",");
		for(i=0;i<array.length;i++){
   			if(array[i]==id){
     			array.splice(i,1);
			}
		}
		document.getElementById('preqfiles').value=array.toString();
	}
	
	function deleteImage(id){
		var url = 'index.php?option=com_guru&controller=guruPrograms&tmpl=component&format=raw&task=delete_course_image&id='+id+"&avatar=0";		
		/*var req = jQuery.ajax({
			method: 'get',
			asynchronous: 'true',
			url: url,
			data: { 'do' : '1' },
			success: function() {
					document.getElementById('view_imagelist23').src="<?php echo JURI::base(); ?>components/com_guru/images/blank.png";
					document.getElementById('deletebtn').style.display="none";
					document.getElementById('img_name').value="";
					document.getElementById('image').value="";
			},
					
		})*/	

		jQuery.ajax({
			async: false,
			url: 'index.php?option=com_guru&controller=guruPrograms&tmpl=component&format=raw&task=delete_course_image&id='+id+"&avatar=0",
			success: function(response) {
	            document.getElementById('view_imagelist23').src="<?php echo JURI::base(); ?>components/com_guru/images/blank.png";
				document.getElementById('deletebtn').style.display="none";
				document.getElementById('img_name').value="";
				document.getElementById('image').value="";
			}
	    });

		return true;	
	}
	function deleteImageA(id){
		var url = 'index.php?option=com_guru&controller=guruPrograms&tmpl=component&format=raw&task=delete_course_image&id='+id+"&avatar=1";		
		/*var req = jQuery.ajax({
			method: 'get',
			asynchronous: 'true',
			url: url,
			data: { 'do' : '1' },
			success: function() {
					document.getElementById('view_imagelist24').src="<?php echo JURI::base(); ?>components/com_guru/images/blank.png";
					document.getElementById('deletebtn2').style.display="none";
					document.getElementById('image_avatar2').value="";
					document.getElementById('image_avatar').value="";
			},
					
		})*/

		jQuery.ajax({
			async: false,
			url: 'index.php?option=com_guru&controller=guruPrograms&tmpl=component&format=raw&task=delete_course_image&id='+id+"&avatar=1",
			success: function(response) {
	            document.getElementById('view_imagelist24').src="<?php echo JURI::base(); ?>components/com_guru/images/blank.png";
				document.getElementById('deletebtn2').style.display="none";
				document.getElementById('image_avatar2').value="";
				document.getElementById('image_avatar').value="";
			}
	    });

		return true;	
	}
    
    function checkPlans(id) {
        var $checked = jQuery('#' + id + ' .plain:checkbox:checked');
        var $total = jQuery('#' + id + ' .plain:checkbox');
		
        if ($checked.length < $total.length) {
            //console.log('Not all boxes are checked');
            $total.each(function(){
                jQuery(this).prop('checked', true);
            });
        } 
		else {
            //console.log('All boxes are checked');
            $total.each(function(){
                jQuery(this).prop('checked', false);
            });
        }
    }
	function publishUn(i){
		var url = 'index.php?option=com_guru&controller=guruPrograms&tmpl=component&format=raw&task=publish_un_ajax&id='+i;
		/*var myAjax = jQuery.ajax({
			method: 'get',
			asynchronous: 'true',
			url: url,
			data: { 'do' : '1' },
			success: function(data) {
				if(data[0].textContent == 'publish'){
					element_id = "g_publish"+i;
					document.getElementById(element_id).className = "icon-ok";
				}
				else if(data[0].textContent == 'unpublish'){
					element_id = "g_publish"+i;
					document.getElementById(element_id).className = "icon-remove";
				}
			},
					
		})*/

		jQuery.ajax({
			async: false,
			url: 'index.php?option=com_guru&controller=guruPrograms&tmpl=component&format=raw&task=publish_un_ajax&id='+i,
			success: function(data) {
	            if(data == 'publish'){
					element_id = "g_publish"+i;
					document.getElementById(element_id).className = "icon-ok";
				}
				else if(data == 'unpublish'){
					element_id = "g_publish"+i;
					document.getElementById(element_id).className = "icon-remove";
				}
			}
	    });

		return true;	
	}
	function checkNegativeNb(nb){
		if(parseInt(nb)<0 || isNaN(nb)){
			alert("<?php echo JText::_("GURU_INVALID_NUMBER_AVG");?>");
			return false;
		}
		else{
			return  true;
		}
	}	
</script>
<form action="index.php" method="post" name="adminForm" id="adminForm" enctype="multipart/form-data">
	<div class="well"><?php if ($program->id<1) echo JText::_('GURU_NEWCOURSE'); else echo JText::_('GURU_EDITCOURSE');?></div>
	<ul class="nav nav-tabs">
	    <li class="nav-item active"><a class="nav-link" href="#general" data-toggle="tab"><?php echo JText::_('GURU_GENERAL');?></a></li>
	    <li class="nav-item"><a class="nav-link" href="#prog_description" data-toggle="tab"><?php echo JText::_('GURU_PRODDESC');?></a></li>
	    <li class="nav-item"><a class="nav-link" href="#prog_image" data-toggle="tab"><?php echo JText::_('GURU_IMAGE_COVER');?></a></li>
	    <li class="nav-item"><a class="nav-link" href="#prog_exerc" data-toggle="tab"><?php echo JText::_('GURU_EXERCISE_FILES');?></a></li>
	    <li class="nav-item"><a class="nav-link" href="#prog_pricing" data-toggle="tab"><?php echo JText::_('GURU_PRICING_PLANS');?></a></li>
	    <li class="nav-item"><a class="nav-link" href="#prog_publishing" data-toggle="tab"><?php echo JText::_('GURU_PUBLISHING');?></a></li>
	    <li class="nav-item"><a class="nav-link" href="#prog_metatags" data-toggle="tab"><?php echo JText::_('GURU_METATAGS');?></a></li>
	    <li class="nav-item"><a class="nav-link" href="#prog_day_req" data-toggle="tab"><?php echo JText::_('GURU_DAY_REQ');?></a></li>
	    <li class="nav-item"><a class="nav-link" href="#prog_mailchimp" data-toggle="tab"><?php echo JText::_('GURU_MAILCHIMP');?></a></li>
	</ul>
	<div class="tab-content">
	    <div class="tab-pane active show" id="general">
	        <table class="adminform">
	            <tr>
	                <td style="width:40%;">
	                    <?php echo JText::_('GURU_PRODNAME'); ?>:<font color="#ff0000">*</font>
	                </td>
	                <td>
	                    <input class="inputbox" type="text" name="name" size="40" maxlength="255" value="<?php echo str_replace('"', '&quot;', $program->name); ?>" />
	                    <span class="editlinktip hasTip" title="<?php echo JText::_("GURU_TIP_PRODNAME"); ?>" >
	                    <img border="0" src="components/com_guru/images/icons/tooltip.png">
	                    </span>
	                </td>
	            </tr>
	            <tr>
	                <td >
	                    <?php echo JText::_('GURU_ALIAS'); ?>:
	                </td>
	                <td>
	                    <input class="inputbox" type="text" name="alias" size="40" maxlength="255" value="<?php echo $program->alias; ?>" />
	                    <span class="editlinktip hasTip" title="<?php echo JText::_("GURU_TIP_ALIAS"); ?>" >
	                    <img border="0" src="components/com_guru/images/icons/tooltip.png">
	                    </span>
	                </td>
	            </tr>
	            <tr>
	                <td >
	                    <?php echo JText::_('GURU_CATEGPARENT'); ?>:<font color="#ff0000">*</font>
	                </td>
	                <td>
	                    <?php $lists['treecateg']=$this->list_all(0, "catid", $program->catid, $program->catid); ?>
	                    <span class="editlinktip hasTip" title="<?php echo JText::_("GURU_TIP_CATEGPARENT"); ?>" >
	                    <img border="0" src="components/com_guru/images/icons/tooltip.png">
	                    </span>
	                </td>
	            </tr>
	            <tr>
	                <td >
	                    <?php echo JText::_('GURU_AUTHOR'); ?>:<font color="#ff0000">*</font>
	                </td>
	                <td>
	                    <?php echo $lists['author']; ?>
	                    <span class="editlinktip hasTip" title="<?php echo JText::_("GURU_TIP_AUTHOR"); ?>" >
	                    <img border="0" src="components/com_guru/images/icons/tooltip.png">
	                    </span>
	                </td>
	            </tr>
	            <tr style="display:none;">
	                <td >
	                    <?php echo JText::_('GURU_SPLIT_COMMISSIONS'); ?>:
	                </td>
	                <td>
	                    <input type="hidden" name="split_commissions" value="0">
	                    <?php
	                        $split_commissions = @$program->split_commissions;
	                        $checked = '';
	                        if($split_commissions == 1){
	                        	$checked = 'checked="checked"';
	                        }
	                        ?>
	                    <input type="checkbox" <?php echo $checked; ?> value="1" class="ace-switch ace-switch-5" name="split_commissions">
	                    <span class="lbl"></span>
	                    <span class="editlinktip hasTip" title="<?php echo JText::_("GURU_SPLIT_COMMISSIONS_TIP"); ?>" >
	                    <img border="0" src="components/com_guru/images/icons/tooltip.png">
	                    </span>
	                </td>
	            </tr>
	            <tr>
	                <td >
	                    <?php echo JText::_('GURU_LEVEL'); ?>:
	                </td>
	                <td> 
	                    <?php echo $lists['level']; ?>
	                    <span class="editlinktip hasTip" title="<?php echo JText::_("GURU_TIP_LEVEL"); ?>" >
	                    <img border="0" src="components/com_guru/images/icons/tooltip.png">
	                    </span>
	                </td>
	            </tr>
	            <tr>
	                <td ><?php echo JText::_('GURU_SKIP_MODULE_PAGE');?>:</td>
	                <?php
	                    $skip_module = $program->skip_module;
	                    if(!isset($skip_module) || $skip_module == ""){
	                        $skip_module = 0;
	                    }
	                    ?>	
	                <td>            	
	                    <input type="hidden" name="skip_module" value="0">
	                    <?php
	                        $checked = '';
	                        if($skip_module == 1){
	                        	$checked = 'checked="checked"';
	                        }
	                        ?>
	                    <input type="checkbox" <?php echo $checked; ?> value="1" class="ace-switch ace-switch-5" name="skip_module">
	                    <span class="lbl"></span>                             
	                </td>
	            </tr>
	            <tr>
	                <td ><?php echo JText::_('GURU_RESET_ON_RENEW');?>:</td>
	                <?php
	                    $reset_on_renew = $program->reset_on_renew;
	                    if(!isset($reset_on_renew) || $reset_on_renew == ""){
	                        $reset_on_renew = 0;
	                    }
	                    ?>
	                <td>            	
	                    <input type="hidden" name="reset_on_renew" value="0">
	                    <?php
	                        $checked = '';
	                        if($reset_on_renew == 1){
	                        	$checked = 'checked="checked"';
	                        }
	                        ?>
	                    <input type="checkbox" <?php echo $checked; ?> value="1" class="ace-switch ace-switch-5" name="reset_on_renew">
	                    <span class="lbl"></span>                             
	                </td>
	            </tr>
	            <tr>
	                <td>
	                    <?php echo JText::_("GURU_COURSE_TYPE");?>:
	                </td>
	                <td>
	                    <?php
	                        $disabled = "";
	                        if(intval($id) != 0){
	                        	$disabled = 'disabled="disabled"';
	                        	echo '<input type="hidden" name="course_type" value="'.$program->course_type.'" />';
	                        }
	                        ?>
	                    <select id="course_type" <?php echo $disabled; ?> name="course_type" onchange="javascript:guruShowLessons(value)">
	                        <option value="0" <?php if($program->course_type == "0"){echo 'selected="selected"';} ?>><?php echo  JText::_("GURU_NON_SEQ"); ?></option>
	                        <option value="1" <?php if($program->course_type == "1"){echo 'selected="selected"';} ?>><?php echo  JText::_("GURU_SEQ");  ?></option>
	                    </select>
	                    <span class="editlinktip hasTip" title="<?php echo JText::_("GURU_TIP_SEQ"); ?>" >
	                    <img border="0" src="components/com_guru/images/icons/tooltip.png">
	                    </span>
	                </td>
	            </tr>
	            <tr id="lessons_release_td"  <?php if ($program->course_type ==1){$style="style=display:table-row;"; echo $style;} else{$style="style=display:none;"; echo $style;} ?>>
	                <td >
	                    <?php echo JText::_("GURU_LESSON_RELEASE"); ?>:
	                </td>
	                <td>
	                    <?php
	                        $disabled = "";
	                        if(intval($id) != 0){
	                        	$disabled = 'disabled="disabled"';
	                        	echo '<input type="hidden" name="lesson_release" value="'.$program->lesson_release.'" />';
	                        }
	                        // start changes for lessons per release
	                        ?>
	                        
						<select id="lesson_release" <?php echo $disabled; ?> name="lesson_release" onchange="javascript:guruPopupChangeOption()">
							<option value="1" <?php if($program->lesson_release == "1"){echo 'selected="selected"';} ?>><?php echo JText::_("GURU_LESSONS_PER_DAY");?></option>
							<option value="2" <?php if($program->lesson_release == "2"){echo 'selected="selected"';} ?>><?php echo JText::_("GURU_LESSONS_PER_W"); ?></option>
							<option value="3" <?php if($program->lesson_release == "3"){echo 'selected="selected"';} ?>><?php echo JText::_("GURU_LESSONS_PER_M"); ?></option>
							<option value="4" <?php if($program->lesson_release == "4"){echo 'selected="selected"';} ?>><?php echo JText::_("GURU_BASED_ON_HOURS"); ?></option>
							<option value="0" <?php if($program->lesson_release == "0"){echo 'selected="selected"';} ?>><?php echo JText::_("GURU_ALL_AT_ONCE"); ?></option>
							<option value="5" <?php if($program->lesson_release == "5"){echo 'selected="selected"';} ?>><?php echo JText::_("GURU_SERIAL_ORDER"); ?></option>
						</select>

	                    <span class="editlinktip hasTip" title="<?php echo JText::_("GURU_TIP_LESSON_RELEASE"); ?>" >
							<img border="0" src="components/com_guru/images/icons/tooltip.png">
                        </span>

                        <?php
                        	$display_hour = "none";

                        	if($program->lesson_release == "4"){
                        		$display_hour = "block";
                        	}
                        	
                        	$display_lessons_per_release = "none";

                        	if($program->lesson_release != "4" && $program->lesson_release != "0"){
                        		$display_lessons_per_release = "block";
                        	}
                        ?>

                        <div id="based-on-hour" style="display: <?php echo $display_hour; ?>;">
                        	<br />
                        	<?php echo JText::_("GURU_AFTER_EACH_HOUR"); ?>
                        	<select style="width:50px;" name="after_hours" <?php echo $disabled; ?>>
                        		<?php
                        			for($h=1; $h<=12; $h++){
                        				$selected = "";

                        				if($program->after_hours == $h){
                        					$selected = 'selected="selected"';
                        				}
                        		?>
                        				<option value="<?php echo $h; ?>" <?php echo $selected; ?> > <?php echo $h; ?> </option>
                        		<?php
                        			}
                        		?>
                        	</select>
                        	<?php echo JText::_("GURU_HOURS"); ?>
                        </div>

                        <div id="based-on-lesson-per-release" style="display: <?php echo $display_lessons_per_release; ?>;">
                        	<br />
                        	<?php echo JText::_("GURU_LESSONS_SELECT");?>

                        	<select style="width:50px;" name="lessons_per_release" <?php echo $disabled; ?>>
                        		<?php
                        			for($h=1; $h<=12; $h++){
                        				$selected = "";

                        				if($program->lessons_per_release == $h){
                        					$selected = 'selected="selected"';
                        				}
                        		?>
                        				<option value="<?php echo $h; ?>" <?php echo $selected; ?> > <?php echo $h; ?> </option>
                        		<?php
                        			}
                        		?>
                        	</select>
                        	<?php echo JText::_("GURU_LESSONS"); ?>
                        </div>
                        <?php 	// end changes for lessons per release ?>
	                </td>
	            </tr>
	            <tr id="lessons_show_td" <?php if ($program->course_type ==1){$style="style=display:table-row;"; echo $style;} else{$style="style=display:none;"; echo $style;} ?>>
	                <td >
	                    <?php echo JText::_("GURU_SHOW_UNRELEASED_LESSONS"); ?>:
	                </td>
	                <td>
	                    <select id="lessons_show" name="lessons_show">
	                        <option value="1" <?php if($program->lessons_show == "1"){echo 'selected="selected"';} ?>><?php echo JText::_("GURU_GRAYED_TEXT");?></option>
	                        <option value="2" <?php if($program->lessons_show == "2"){echo 'selected="selected"';} ?>><?php echo JText::_("GURU_SHOULD_NOT_SHOW"); ?></option>
	                    </select>
	                    <span class="editlinktip hasTip" title="<?php echo JText::_("GURU_TIP_SHOW_UNRELEASED_LESSONS"); ?>" >
	                    <img border="0" src="components/com_guru/images/icons/tooltip.png">
	                    </span>
	                </td>
	            </tr>
	            <tr>
	                <td >
	                    <?php echo JText::_('GURU_FINAL_EXAM'); ?>
	                </td>
	                <td>
	                    <select id="final_quizzes" name="final_quizzes">
	                        <option value="0" <?php if($program->id_final_exam == "0"){echo 'selected="selected"';} ?>><?php echo JText::_("GURU_NO_FINAL_EXAM");?></option>
	                        <?php
	                            $cid = "0";
	                            $db = JFactory::getDBO(); 
	                            $sql = "SELECT id as cid , name as name FROM #__guru_quiz WHERE is_final = 1 ORDER by name asc";		
	                            $db->setQuery($sql);
	                            $db->execute();
	                            $result = $db->loadAssocList();
	                            
	                            if(is_array($result) && count($result) > 0){		
	                                foreach($result as $key => $values){						
	                                    $quizid = $values["cid"];
	                                    $quizname = $values["name"];			
	                                    
	                                    ?>			
	                        <option value="<?php echo $quizid;?>"<?php if($program->id_final_exam == $quizid ){echo 'selected="selected"';} ?>><?php echo $quizname; ?></option>
	                        <?php
	                            }
	                            }			
	                            ?>
	                    </select>
	                    <span class="editlinktip hasTip" title="<?php echo JText::_("GURU_TIP_FINAL_EXAM"); ?>" >
	                    <img border="0" src="components/com_guru/images/icons/tooltip.png">
	                    </span>
	                </td>
	            </tr>
	            <tr>
	                <td><?php echo JText::_('GURU_CERTIFICATE_TERM');?> </td>
	                :
	                <td>
	                    <select id="certificate_setts" name="certificate_setts" onchange="javascript:ChangeTermCourse(this.value,'<?php echo $program->id;?>' )">
	                        <option value="1" <?php if($program->certificate_term  == "1"){echo 'selected="selected"';} ?>><?php echo JText::_("GURU_NO_CERTIFICATE"); ?></option>
	                        <option value="2" <?php if($program->certificate_term  == "2"){echo 'selected="selected"';} ?>><?php echo JText::_("GURU_COMPLETE_ALL_LESSONS");?></option>
	                        <option value="3" <?php if($program->certificate_term  == "3"){echo 'selected="selected"';} ?>><?php echo JText::_("GURU_PASS_FINAL_EXAM"); ?></option>
	                        <option value="4" <?php if($program->certificate_term  == "4"){echo 'selected="selected"';} ?>><?php echo JText::_("GURU_PASS_THE_QUIZZES_IN_AVG"); ?></option>
	                        <option value="5" <?php if($program->certificate_term  == "5"){echo 'selected="selected"';} ?>><?php echo JText::_("GURU_CERT_TERM_FALFE"); ?></option>
	                        <option value="6" <?php if($program->certificate_term  == "6"){echo 'selected="selected"';} ?>><?php echo JText::_("GURU_CERT_TERM_FALPQAVG"); ?></option>
	                        <option value="7" <?php if($program->certificate_term  == "7"){echo 'selected="selected"';} ?>><?php echo JText::_("GURU_CERTIFICATE_COURSE_TIME_RECORDING"); ?></option>
	                    </select>
	                    <span class="editlinktip hasTip" title="<?php echo JText::_("GURU_TIP_CERTIFICATE_TERM"); ?>" >
	                    <img border="0" src="components/com_guru/images/icons/tooltip.png">
	                    </span>
	                    <div id="recording_certificate" style=" <?php if($program->certificate_term  == "7" ){echo 'display:inline-block;';}else{echo 'display:none; ';}?>">&nbsp;&nbsp;&nbsp;
	                        <input onchange="javascript:checkNegativeNb(this.value);" style="width: 50px;" type="text" name="record_hour" id="record_hour" value="<?php if($program->id == ""){echo '';} else { echo $program->record_hour;} ?>" placeholder="<?php echo JText::_("GURU_HOURS"); ?>" /> &nbsp; : &nbsp;
	                        <input onchange="javascript:checkNegativeNb(this.value);" style="width: 50px;" type="text" name="record_min" id="record_min" value="<?php if($program->id == ""){echo '';} else { echo $program->record_min;} ?>" placeholder="<?php echo JText::_("GURU_MINUTES"); ?>" />
	                    </div>
	                </td>
	                <td  id="avg_certificate" style=" <?php if($program->certificate_term  == "4" || $program->certificate_term  == "6" ){echo 'display:table-row;';}else{echo 'display:none; ';}?>">&nbsp;&nbsp;&nbsp;
	                    <input onchange="javascript:checkNegativeNb(this.value);" size="5px;" type="text" name="avg_cert" id="avg_cert" value="<?php if($program->id ==""){echo '70';} else{echo $program->avg_certc;} ?>" /> &nbsp;%
	                </td>
	            </tr>
	            <tr id="coursecertifiactemsg" style=" <?php if($program->certificate_term  != "1" && $program->certificate_term  != "0"){echo 'display:table-row;';}else{echo 'display:none;';}?>" >
	                <td><?php echo JText::_('GURU_CERTIFICATE_COURSE_MSG');?></td>
	                <td><textarea id="coursemessage" name="coursemessage" cols="50" rows="4" maxlength="7000"><?php echo stripslashes($program->certificate_course_msg); ?></textarea> 
	                    <span class="editlinktip hasTip" title="<?php echo JText::_("GURU_CERTIFICATE_COURSE_MSG1"); ?>" >
	                    <img border="0" src="components/com_guru/images/icons/tooltip.png">
	                    </span>
	                </td>
	            </tr>
	            <tr>
	                <td><?php echo JText::_('GURU_COURSE_COMPLETED_TERM');?> </td>
	                :
	                <td>
	                    <?php
	                        if(!isset($program->course_completed_term)){
	                        	$program->course_completed_term = 2;
	                        }
	                        ?>
	                    <select id="course_completed_term" name="course_completed_term" onchange="javascript:ChangeTermCourseCompleted(this.value)">
	                        <option value="2" <?php if($program->course_completed_term  == "2"){echo 'selected="selected"';} ?>><?php echo JText::_("GURU_COMPLETE_ALL_LESSONS");?></option>
	                        <option value="3" <?php if($program->course_completed_term  == "3"){echo 'selected="selected"';} ?>><?php echo JText::_("GURU_PASS_FINAL_EXAM"); ?></option>
	                        <option value="4" <?php if($program->course_completed_term  == "4"){echo 'selected="selected"';} ?>><?php echo JText::_("GURU_PASS_THE_QUIZZES_IN_AVG"); ?></option>
	                        <option value="5" <?php if($program->course_completed_term  == "5"){echo 'selected="selected"';} ?>><?php echo JText::_("GURU_CERT_TERM_FALFE"); ?></option>
	                        <option value="6" <?php if($program->course_completed_term  == "6"){echo 'selected="selected"';} ?>><?php echo JText::_("GURU_CERT_TERM_FALPQAVG"); ?></option>
	                        <option value="7" <?php if($program->course_completed_term  == "7"){echo 'selected="selected"';} ?>><?php echo JText::_("GURU_CERTIFICATE_COURSE_TIME_RECORDING"); ?></option>
	                    </select>
	                    <span class="editlinktip hasTip" title="<?php echo JText::_("GURU_TIP_COURSE_COMPLETED_TERM"); ?>" >
	                    <img border="0" src="components/com_guru/images/icons/tooltip.png">
	                    </span>
	                    <div id="recording_course_term" style=" <?php if($program->course_completed_term  == "7" ){echo 'display:inline-block;';}else{echo 'display:none; ';}?>">&nbsp;&nbsp;&nbsp;
	                        <input onchange="javascript:checkNegativeNb(this.value);" style="width: 50px;" type="text" name="record_hour_course_term" id="record_hour_course_term" value="<?php if($program->id == ""){echo '';} else { echo $program->record_hour_course_term;} ?>" placeholder="<?php echo JText::_("GURU_HOURS"); ?>" /> &nbsp; : &nbsp;
	                        <input onchange="javascript:checkNegativeNb(this.value);" style="width: 50px;" type="text" name="record_min_course_term" id="record_min_course_term" value="<?php if($program->id == ""){echo '';} else { echo $program->record_min_course_term;} ?>" placeholder="<?php echo JText::_("GURU_MINUTES"); ?>" />
	                    </div>
	                </td>
	                <td  id="avg_certificate_course_term" style=" <?php if($program->course_completed_term  == "4" || $program->course_completed_term  == "6" ){echo 'display:table-row;';}else{echo 'display:none; ';}?>">&nbsp;&nbsp;&nbsp;
	                    <input onchange="javascript:checkNegativeNb(this.value);" size="5px;" type="text" name="avg_certificate_course_term" id="avg_certificate_course_term" value="<?php if($program->id ==""){echo '70';} else{echo $program->avg_certificate_course_term;} ?>" /> &nbsp;%
	                </td>
	            </tr>
	            <tr>
	                <td colspan="2">
	                    <br />
	                    <?php echo JText::_("GURU_REDIRECT_AFTER_COMPLETE"); ?>
	                </td>
	            </tr>
	            <tr>
	                <td>
	                    <?php echo JText::_("GURU_CUSTOM_PAGE_URL"); ?>:
	                </td>
	                <td>
	                    <input style="width: 350px;" class="inputbox" type="text" name="custom_page_url" value="<?php echo str_replace('"', '&quot;', $program->custom_page_url); ?>" />
	                    <span class="editlinktip hasTip" title="<?php echo JText::_("GURU_REDIRECT_AFTER_COMPLETE"); ?>" >
	                    <img border="0" src="components/com_guru/images/icons/tooltip.png">
	                    </span>
	                </td>
	            </tr>
	            <tr>
	                <td>
	                    <?php echo JText::_("GURU_AUTO_CONFIRM_LESSON_VIEW"); ?>:
	                </td>
	                <td style="padding-top: 10px;">
	                    <?php
	                        $lesson_view_confirm = 0;
	                        
	                        if(isset($program->lesson_view_confirm)){
	                        	$lesson_view_confirm = $program->lesson_view_confirm;
	                        }
	                        ?>
	                    <ul id="list-confirm-options" style="margin: 0px; padding: 0px; list-style-type: none;">
	                        <li style=" margin-bottom: 10px; line-height: 20px">
	                            <input type="radio" id="confirm-options-no" style="margin: 0px;" name="lesson_view_confirm" value="0" <?php if(intval($lesson_view_confirm) == 0){ echo 'checked="checked"';} ?> ><span class="lbl"></span>  <?php echo JText::_("GURU_AUTO_CONFIRMATION"); ?>
	                        </li>
	                        <li style="line-height: 20px;">
	                            <input type="radio" id="confirm-options-yes" style="margin: 0px;" name="lesson_view_confirm" value="1" <?php if(intval($lesson_view_confirm) == 1){ echo 'checked="checked"';} ?> ><span class="lbl"></span>  <?php echo JText::_("GURU_MANUAL_LESSON_CONFIRMATION"); ?>
	                        </li>
	                    </ul>
	                </td>
	            </tr>
	        </table>
	    </div>
	    <div class="tab-pane" id="prog_description">
	        <table class="adminform">
	            <tr>
	                <td>
	                    <?php echo JText::_('GURU_PRODDESC');?>: 
	                </td>
	                <td>
	                    <table>
	                        <tr>
	                            <td>
	                                <div onmouseover="document.getElementById('editor_clicked').value = 'description';">
	                                    <?php 
	                                        echo $editorul->display( 'description', ''.stripslashes($program->description),'100%', '220px', '20', '50' );
	                                        ?>
	                                </div>
	                            </td>
	                            <td valign="top">
	                                <span class="editlinktip hasTip" title="<?php echo JText::_("GURU_TIP_PRODDESC"); ?>" >
	                                <img border="0" src="components/com_guru/images/icons/tooltip.png">
	                                </span>
	                            </td>
	                        </tr>
	                    </table>
	                </td>
	            </tr>
	        </table>
	    </div>
	    <div class="tab-pane" id="prog_image">
	    	<?php
	    		$og_title = "";
	    		$og_type = "";
	    		$og_image = "";
	    		$og_url = "";
	    		$og_desc = "";

	    		if(isset($program->og_tags) && trim($program->og_tags) != ""){
	    			$og_tags = json_decode($program->og_tags, true);
	    			$og_title = $og_tags["og_title"];
		    		$og_type = $og_tags["og_type"];
		    		$og_image = $og_tags["og_image"];
		    		$og_url = $og_tags["og_url"];
		    		$og_desc = $og_tags["og_desc"];
	    		}
	    	?>
	    	<table class="adminform">
	    		<tr>
	    			<td width="20%">
	    				<?php echo JText::_("GURU_OG_TITLE"); ?>
	    			</td>
	    			<td width="80%">
	    				<input type="text" name="og_title" value="<?php echo $og_title; ?>" />
	    			</td>
	    		</tr>

	    		<tr>
	    			<td width="20%">
	    				<?php echo JText::_("GURU_OG_TYPE"); ?>
	    			</td>
	    			<td width="80%">
	    				<input type="text" name="og_type" value="<?php echo $og_type; ?>" />
	    			</td>
	    		</tr>

	    		<tr>
	    			<td width="20%">
	    				<?php echo JText::_("GURU_OG_IMAGE"); ?>
	    			</td>
	    			<td width="80%">
	    				<input type="text" name="og_image" value="<?php echo $og_image; ?>" />
	    			</td>
	    		</tr>

	    		<tr>
	    			<td width="20%">
	    				<?php echo JText::_("GURU_OG_URL"); ?>
	    			</td>
	    			<td width="80%">
	    				<input type="text" name="og_url" value="<?php echo $og_url; ?>" />
	    			</td>
	    		</tr>

	    		<tr>
	    			<td width="20%">
	    				<?php echo JText::_("GURU_OG_DESC"); ?>
	    			</td>
	    			<td width="80%">
	    				<textarea name="og_desc"><?php echo $og_desc; ?></textarea>
	    			</td>
	    		</tr>
	    	</table>

	        <table class="adminform">
	            <div class="widget-header widget-header-flat">
	                <h5><?php echo JText::_('GURU_IMAGE_COVER2');?></h5>
	            </div>
	            <div class="well ijmargin_top" >
	                <?php echo JText::_('GURU_IMAGE_COVER_USAGE');?>
	            </div>
	            <tr>
	                <td width="100" align="left">
	                    <?php echo JText::_('GURU_IMAGE_COVER2');?>:
	                </td>
	                <td>
	                    <div style="float:left;">
	                        <div id="fileUploader"></div>
	                    </div>
	                    <div style="float:left; padding-left:10px;">
	                        <span class="editlinktip hasTip" title="<?php echo JText::_("GURU_TIP_IMAGE"); ?>" >
	                        <img border="0" src="components/com_guru/images/icons/tooltip.png">
	                        </span>
	                    </div>
	                    <div style="float:left; padding-left:10px;">
	                        <?php echo JText::_('GURU_RECOMMENDED_SIZE');?>
	                    </div>
	                    <input type="hidden" name="image" id="image" value="<?php echo $program->image; ?>" />&nbsp; 
	                </td>
	            </tr>
	            <tr>
	                <td>
	                    <?php echo JText::_('GURU_PRODCIMG_COVER');?> 
	                </td>
	                <td>
	                    <?php
	                        if(isset($program->image) && $program->image!=""){ 
	                        
	                        ?>
	                    <img id="view_imagelist23" name="view_imagelist" src='../<?php echo $program->image;?>'/>
	                    <br />
	                    <input style="margin-top:10px;" type="button" class="btn" value="<?php echo JText::_('GURU_REMOVE'); ?>" onclick="deleteImage('<?php echo $program->id; ?>');" id="deletebtn" />
	                    <input type="hidden" value="<?php echo $program->image; ?>" name="img_name" id="img_name" />		
	                    <?php 
	                        } 
	                        else {
	                        	echo "<img id='view_imagelist23' name='view_imagelist' src='".JURI::base()."components/com_guru/images/blank.png'/>";
	                        }
	                        ?>
	                </td>
	            </tr>
	        </table>
	        <table class="adminform">
	            <div class="widget-header widget-header-flat">
	                <h5><?php echo JText::_('GURU_IMAGE_AVATAR');?></h5>
	            </div>
	            <div class="well ijmargin_top" >
	                <?php echo JText::_('GURU_IMAGE_AVATAR_USAGE');?>
	            </div>
	            <tr>
	                <td width="100" align="left">
	                    <?php echo JText::_('GURU_IMAGE_AVATAR');?>:
	                </td>
	                <td>
	                    <div style="float:left;">
	                        <div id="fileUploader1"></div>
	                    </div>
	                    <div style="float:left; padding-left:10px;">
	                        <span class="editlinktip hasTip" title="<?php echo JText::_("GURU_TIP_IMAGE"); ?>" >
	                        <img border="0" src="components/com_guru/images/icons/tooltip.png">
	                        </span>
	                    </div>
	                    <input type="hidden" name="image_avatar" id="image_avatar" value="<?php echo $program->image_avatar; ?>" />&nbsp; 
	                </td>
	            </tr>
	            <tr>
	                <td>
	                    <?php echo JText::_('GURU_PRODCIMG_AVATAR');?> 
	                </td>
	                <td>
	                    <?php
	                        if(isset($program->image_avatar) && $program->image_avatar!=""){ 
	                        ?>
	                    <img id="view_imagelist24" name="view_imagelist1" src='../<?php echo $program->image_avatar;?>'/>
	                    <br />
	                    <input style="margin-top:10px;" type="button" class="btn" value="<?php echo JText::_('GURU_REMOVE'); ?>" onclick="deleteImageA('<?php echo $program->id; ?>');" id="deletebtn2" />
	                    <input type="hidden" value="<?php echo $program->image_avatar; ?>" name="image_avatar2" id="image_avatar2" />		
	                    <?php 
	                        } 
	                        else {
	                        	echo "<img id='view_imagelist24' name='view_imagelist' src='".JURI::base()."components/com_guru/images/blank.png'/>";
	                        }
	                        ?>
	                </td>
	            </tr>
	        </table>
	    </div>
	    <div class="tab-pane" id="prog_exerc">
	        <table class="table">
	            <tr>
	                <td>
	                    <div style="float:left;">
	                        <a rel="{handler: 'iframe', size: {x: 700, y: 450}}" href="index.php?option=com_guru&controller=guruPrograms&task=addmedia&tmpl=component&cid[]=<?php echo $program->id;?>" class="openModal modal modal-button" data-toggle="modal" data-target="#GuruModal"><?php echo JText::_("GURU_EXERCISE_FILE"); ?></a>&nbsp;
	                    </div>
	                    <div style="float:left;">
	                        <span class="editlinktip hasTip" title="<?php echo JText::_("GURU_TIP_EXERCISE_FILE"); ?>" >
	                        <img border="0" src="components/com_guru/images/icons/tooltip.png">
	                        </span>
	                    </div>
	                </td>
	            </tr>
	            <tr>
	                <td>
	                    <table id="articleList" class="table" width="100%">
	                        <thead>
	                            <tr>
	                                <th></th>
	                                <th></th>
	                                <th style="text-align:center; width:5%;">#</th>
	                                <th></th>
	                                <th width="30%" style="text-align:left;">
	                                    <strong><?php echo JText::_("GURU_FILE_MEDIA_NAME"); ?></strong>
	                                </th>
	                                <th width="9%" style="text-align:center;">
	                                    <strong><?php echo JText::_("GURU_PUBLISHED"); ?></strong>
	                                </th>
	                                <th width="12%" style="text-align:center;">
	                                    <strong><?php echo JText::_("GURU_GUEST_ACCESS"); ?></strong>
	                                </th>
	                                <th width="14%" style="text-align:center;">
	                                    <strong><?php echo JText::_("GURU_REMOVE"); ?></strong>
	                                </th>
	                            </tr>
	                        </thead>
	                        <tbody id="rowsmedia">
	                            <?php 
	                                $oldids = "";
	                                $display_none = '';
	                                if($program->id != NULL){
	                                	$existing_ids = guruAdminModelguruProgram::existing_ids($program->id);
	                                }
	                                
	                                $tt = array();
	                                if(is_array(@$existing_ids) && count(@$existing_ids) > 0){
	                                foreach($existing_ids as $ex_idz){
	                                $tt[] = $ex_idz->media_id;
	                                }		
	                                }
	                                $more_media_files=new stdClass();
	                                
	                                $i=0;
	                                $mmediam_nr = count($mmediam);
	                                $pageNav = new JPagination( $mmediam_nr, 0, $mmediam_nr);
	                                $n = $mmediam_nr;
	                                
	                                $ordered = false;
	                                $order_value = 1;
	                                foreach ($mmediam as $mmedial) {
	                                if(intval($mmedial->order) != 0){
	                                $ordered = true;
	                                }
	                                }
	                                
	                                foreach ($mmediam as $mmedial) {
	                                if(!$ordered){
	                                $mmedial->order = $order_value;
	                                $order_value++;
	                                }
	                                ?>
	                            <tr class="row<?php echo $i%2; ?>" id="tr<?php echo $mmedial->media_id;?>" <?php echo $display_none; ?>>
	                                <td>
	                                    <span class="sortable-handler active" style="cursor: move;">
	                                    <i class="icon-menu"></i>
	                                    </span>
	                                    <input type="text" class="width-20 text-area-order " value="<?php echo $mmedial->order; ?>" size="5" name="order[]" style="display:none;">
	                                </td>
	                                <td  width="5%" style="text-align:center; visibility:hidden;"><?php $checked = JHTML::_('grid.id', $i, $mmedial->media_id); echo $checked;?></td>
	                                <td width="5%" style="text-align:center;"><?php echo $i+1; ?></td>
	                                <td width="5%" style="text-align:center;">
	                                    <?php
	                                        switch($mmedial->type){
	                                            case "video":{						
	                                                         $img_path = JURI::root()."administrator/components/com_guru/images/video.gif";
	                                                         echo '<img src="'.$img_path.'" alt="video type"/>';									
	                                                        }
	                                                break;
	                                            case "docs":{						
	                                                         $img_path = JURI::root()."administrator/components/com_guru/images/doc.gif";
	                                                         echo '<img src="'.$img_path.'" alt="video type"/>';									
	                                                        }
	                                                break;
	                                            case "url":{						
	                                                         $img_path = JURI::root()."administrator/components/com_guru/images/url.gif";
	                                                         echo '<img src="'.$img_path.'" alt="video type"/>';									
	                                                        }
	                                                break;
	                                            case "image":{						
	                                                         $img_path = JURI::root()."administrator/components/com_guru/images/image.jpg";
	                                                         echo '<img src="'.$img_path.'" alt="video type"/>';									
	                                                        }
	                                                break;
	                                            case "audio":{						
	                                                         $img_path = JURI::root()."administrator/components/com_guru/images/audio.gif";
	                                                         echo '<img src="'.$img_path.'" alt="audio type"/>';									
	                                                        }
	                                                break;											
	                                            case "quiz":{						
	                                                         $img_path = JURI::root()."administrator/components/com_guru/images/quiz.gif";
	                                                         echo '<img src="'.$img_path.'" alt="quiz type"/>';									
	                                                        } 
	                                                break;
	                                            case "text":{						
	                                                         $img_path = JURI::root()."administrator/components/com_guru/images/doc.gif";
	                                                         echo '<img src="'.$img_path.'" alt="doc type"/>';									
	                                                        } 
	                                                break;
	                                            case "file":{						
	                                                         $img_path = JURI::root()."administrator/components/com_guru/images/file.gif";
	                                                         echo '<img src="'.$img_path.'" alt="doc type"/>';									
	                                                        } 
	                                                break;
	                                        }
	                                        ?>		  
	                                </td>
	                                <td width="30%">
	                                    <a class="a_guru" href="index.php?option=com_guru&controller=guruMedia&task=edit&cid[]=<?php echo $mmedial->media_id;?>" target="_blank"><?php echo $mmedial->name;?></a>
	                                </td>
	                                <td width="9%" style="text-align:center;">
	                                    <?php
	                                        if($mmedial->published ==1){
	                                        ?>
	                                    <a id="g_publish<?php echo $mmedial->media_id;?>" style="cursor: pointer;" class="icon-ok" title="Publish Item" onclick="javascript:publishUn(<?php echo $mmedial->media_id;?>)"></a>
	                                    <?php
	                                        }
	                                        else{?>
	                                    <a id="g_publish<?php echo $mmedial->media_id;?>" style="cursor: pointer;" class="icon-remove" title="Unpublish Item" onclick="javascript:publishUn(<?php echo $mmedial->media_id;?>)"></a>
	                                    <?php
	                                        }
	                                        ?>      
	                                </td>
	                                <td width="12%" style="text-align:center;">
	                                    <select name="<?php echo "access".$mmedial->media_id ?>">
	                                        <option value="0" <?php if($mmedial->access == 0) echo "selected"; ?> ><?php echo JText::_("GURU_COU_STUDENTS"); ?></option>
	                                        <option value="1" <?php if($mmedial->access == 1) echo "selected"; ?> ><?php echo JText::_("GURU_REG_MEMBERS"); ?></option>
	                                        <option value="2" <?php if($mmedial->access == 2) echo "selected"; ?> ><?php echo JText::_("GURU_REG_GUESTS"); ?></option>
	                                    </select>
	                                </td>
	                                <td width="14%" style="text-align:center;"><?php 
	                                    //$img_path = JURI::root().DIRECTORY_SEPARATOR."administrator".DIRECTORY_SEPARATOR."components".DIRECTORY_SEPARATOR."com_guru".DIRECTORY_SEPARATOR."images".DIRECTORY_SEPARATOR."delete2.gif";
	                                    $img_path = "components/com_guru/images/delete2.gif";
	                                    echo '<img onclick="deleteMedia(\''.$mmedial->media_id.'\');" src="'.$img_path.'" alt="delete"/>';?>
	                                </td>
	                            </tr>
	                            <?php $i++; 
	                                $oldids = $oldids.$mmedial->media_id.',';
	                                }
	                                if(is_array($more_media_files)){
	                                	if(count($more_media_files)>0){
	                                		foreach($more_media_files as $more_media_files_val) {
		                                    	$display_none = '';
		                                    	if(!in_array($more_media_files_val->media_id,$tt)) { // if start ?>}
					                            <tr id="1tr<?php echo $more_media_files_val->media_id;?>" <?php echo $display_none; ?>>
					                                <td width="8%"></td>
					                                <td width="39%">
					                                    <a class="a_guru" href="index.php?option=com_guru&controller=guruMedia&task=edit&cid[]=<?php echo $mmedial->media_id;?>" target="_blank"><?php echo $more_media_files_val->name;?></a>
					                                </td>
					                                <td width="9%">
					                                    <?php echo $more_media_files_val->type;?>
					                                </td>
					                                <td width="18%">--</td>
					                                <td width="12%">
					                                    <?php echo $link2_remove; ?>
					                                </td>
					                                <td width="14%">
					                                    <?php echo $more_media_files_val->published;?>
					                                </td>
					                            </tr>
	                            <?php $oldids = $oldids.$more_media_files_val->media_id.',';
	                                		} // foreach end
	                                	} // if count end
	                                }// if is_array end
	                        	}
	                            ?>	
	                        </tbody>
	                    </table>
	                    <input type="hidden" value="<?php echo $oldids;?>" name="mediafiles" id="mediafiles">
	                    <input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
	                    <input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
	                    <?php echo JHtml::_('form.token'); ?>
	                </td>
	            </tr>
	        </table>
	    </div>
	    <div class="tab-pane" id="prog_pricing">
	        <div class="clearfix">
	            <div class="pull-left"><input onclick="javascript:checkIfCertificateIsSet();" type="checkbox" id= "chb_free_courses" name="chb_free_courses" <?php if(@$chb_free_courses == "1") echo 'checked="checked"'; else { }?>> <span class="lbl"></span></div>
	            <div class="pull-left" style=" float:left; padding:0.2em"> <?php echo trim(JText::_('GURU_FREE_COURSES')); ?></div>
	            <div class="pull-left">
	                <select id="step_access_courses" name="step_access_courses" onchange="javascript:selectrealtime(this.value); checkIfCertificateIsSet();">
	                    <option value="0" <?php if(@$step_access_courses == 0) echo "selected"; ?> ><?php echo JText::_("GURU_COU_STUDENTS"); ?></option>
	                    <option value="1" <?php if(@$step_access_courses == 1) echo "selected"; ?> ><?php echo JText::_("GURU_REG_MEMBERS"); ?></option>
	                    <option value="2" <?php if(@$step_access_courses == 2) echo "selected"; ?> ><?php echo JText::_("GURU_REG_GUESTS"); ?></option>
	                </select>
	            </div>
	            <div class="pull-left" id="free_courses"> 
	                <span style="float: left; padding:5px 10px 0 10px;">
	                <?php echo trim(JText::_('GURU_OF'));?>
	                </span> 
	                <?php $this->getCourseListForStudents(); ?>                       
	            </div>
	            <?php
	                $display = "none";
	                if(@$step_access_courses == "1"){
	                	$display = "block";
	                }
	                ?>
	            <div class="pull-left" id="members-list" style="display:<?php echo $display; ?>;">
	                <style>
	                    #members-list input[type="checkbox"], #members-list input[type="radio"]{
	                    opacity: 1;
	                    }
	                </style>
	                <?php
	                    $groups = array();
	                    if(isset($program->groups_access) && trim($program->groups_access) != ""){
	                    	$groups = explode(",", trim($program->groups_access));
	                    }
	                    echo JHtml::_('access.usergroups', 'groups', $groups, true);
	                    ?>
	            </div>
	        </div>

	        <div style="margin-top: 10px; margin-bottom: 10px;">
	        	<?php echo JText::_("GURU_LIMIT_FREE_NUMBER"); ?> <br />
	        	<span class="muted"><?php echo JText::_("GURU_LIMIT_FREE_NUMBER_INFO"); ?></span>
	        	<input type="text" class="span1" name="free_limit" value="<?php echo $free_limit; ?>">
	        </div>

	        <?php
	            $styleheader = " style='font-size:1.2em;font-weight:bold;padding:0.5em;' ";
	            $stylerow = " style='padding:0.5em;' ";
			?>
			
	        <table class="table">
	            <tr>
	                <td <?php echo $styleheader; ?>>
	                    <div style="float:left;">
	                        <?php echo trim(JText::_('GURU_SUBSCRIPTIONS')) . ' ' . trim(strtolower(JText::_('GURU_SUBS_PLANS'))); ?>&nbsp;
	                    </div>
	                    <div style="float:left;">
	                        <span class="editlinktip hasTip" title="<?php echo JText::_("GURU_TIP_SUBSCRIPTIONS"); ?>" >
	                        <img border="0" src="components/com_guru/images/icons/tooltip.png">
	                        </span>
	                    </div>
	                </td>
	            </tr>
	            <tr>
	                <td>
	                    <?php echo $this->plans; ?>
	                </td>
	            </tr>
	            <tr>
	                <td <?php echo $styleheader; ?>>
	                    <div style="float:left;">
	                        <?php echo JText::_('GURU_RENW_PLANS'); ?>&nbsp;
	                    </div>
	                    <div style="float:left;">
	                        <span class="editlinktip hasTip" title="<?php echo JText::_("GURU_TIP_RENW_PLANS"); ?>" >
	                        <img border="0" src="components/com_guru/images/icons/tooltip.png">
	                        </span>
	                    </div>
	                </td>
	            </tr>
	            <tr>
	                <td>
	                    <?php echo $this->renewals; ?>
	                </td>
	            </tr>
	            <tr>
	                <td <?php echo $styleheader; ?>>
	                    <div style="float:left;">
	                        <?php echo JText::_('GURU_EMAIL_PLANS'); ?>&nbsp;
	                    </div>
	                    <div style="float:left;">
	                        <span class="editlinktip hasTip" title="<?php echo JText::_("GURU_TIP_EMAIL_PLANS"); ?>" >
	                        <img border="0" src="components/com_guru/images/icons/tooltip.png">
	                        </span>
	                    </div>
	                </td>
	            </tr>
	            <tr>
	                <td>
	                    <?php echo $this->emails; ?>
	                </td>
	            </tr>
	        </table>
	    </div>
	    <div class="tab-pane" id="prog_publishing">
	        <table>
	            <tr>
	                <td >
	                    <?php echo JText::_('GURU_PRODLPBS'); ?>
	                </td>
	                <td width="50%">
	                    <?php echo $lists['published']; ?>&nbsp;
	                    <span class="editlinktip hasTip" title="<?php echo JText::_("GURU_TIP_PRODLPBS"); ?>" >
	                    <img border="0" src="components/com_guru/images/icons/tooltip.png">
	                    </span>
	                </td>
	            </tr>
	            <tr>
	                <td valign="top" align="right">
	                    <?php echo JText::_('GURU_PRODLSPUB'); ?>
	                </td>
	                <td>
	                    <?php 
	                        $format = "%m-%d-%Y";
	                        switch($dateformat){
	                        	case "d-m-Y H:i:s": $format = "%d-%m-%Y %H:%M:%S";
	                        		  break;
	                        	case "d/m/Y H:i:s": $format = "%d/%m/%Y %H:%M:%S"; 
	                        		  break;
	                        	case "m-d-Y H:i:s": $format = "%m-%d-%Y %H:%M:%S"; 
	                        		  break;
	                        	case "m/d/Y H:i:s": $format = "%m/%d/%Y %H:%M:%S"; 
	                        		  break;
	                        	case "Y-m-d H:i:s": $format = "%Y-%m-%d %H:%M:%S"; 
	                        		  break;
	                        	case "Y/m/d H:i:s": $format = "%Y/%m/%d %H:%M:%S"; 
	                        		  break;
	                        	case "d-m-Y": $format = "%d-%m-%Y"; 
	                        		  break;
	                        	case "d/m/Y": $format = "%d/%m/%Y"; 
	                        		  break;
	                        	case "m-d-Y": $format = "%m-%d-%Y"; 
	                        		  break;
	                        	case "m/d/Y": $format = "%m/%d/%Y"; 
	                        		  break;
	                        	case "Y-m-d": $format = "%Y-%m-%d"; 
	                        		  break;
	                        	case "Y/m/d": $format = "%Y/%m/%d";		
	                        		  break;  	  	  	  	  	  	  	  	  	  
	                        }
	                        $jnow 	= new JDate('now');
	                        $now 	= $jnow->toSQL();
	                        
	                        if ($program->id<1) $start_publish =  date("".$dateformat."", strtotime($now)); else $start_publish = date("".$dateformat."", strtotime($program->startpublish)) ;
	                                    
	                                    echo JHTML::_('calendar', $start_publish, 'startpublish', 'startpublish', $format, array("showTime"=>"", "todayBtn"=>"", "weekNumbers"=>"", "fillTable"=>"", "singleHeader"=>"")); // calendar change
	                        
	                                    ?>
	                    <span class="editlinktip hasTip" title="<?php echo JText::_("GURU_TIP_PRODLSPUB"); ?>" >
	                    <img border="0" src="components/com_guru/images/icons/tooltip.png">
	                    </span>
	                </td>
	            </tr>
	            <tr>
	                <td valign="top" align="right">
	                    <?php echo JText::_('GURU_PRODLEPUB');?>
	                </td>
	                <td>
	                    <?php
	                        if(substr($program->endpublish, 0, 4) == '0000' || $program->id < 1){
	                        	$end_publish = "";
	                        }
	                        else{
	                        	$end_publish = date("".$dateformat."", strtotime($program->endpublish));
	                        }
	                        
	                        echo JHTML::_('calendar', $end_publish, 'endpublish', 'endpublish', $format, array("showTime"=>"", "todayBtn"=>"", "weekNumbers"=>"", "fillTable"=>"", "singleHeader"=>"")); // calendar change
	                        ?>
	                    <span class="editlinktip hasTip" title="<?php echo JText::_("GURU_TIP_PRODLEPUB"); ?>" >
	                    <img border="0" src="components/com_guru/images/icons/tooltip.png">
	                    </span>
	                </td>
	            </tr>
	        </table>
	    </div>
	    <div class="tab-pane" id="prog_metatags">
	        <table class="adminform">
	            <tr>
	                <td >
	                    <?php echo JText::_('GURU_TITLE'); ?>:
	                </td>
	                <td>
	                    <input class="inputbox" type="text" name="metatitle" size="40" maxlength="255" value="<?php echo $program->metatitle; ?>" />
	                    <span class="editlinktip hasTip" title="<?php echo JText::_("GURU_TIP_TITLE"); ?>" >
	                    <img border="0" src="components/com_guru/images/icons/tooltip.png">
	                    </span>
	                </td>
	            </tr>
	            <tr>
	                <td>
	                    <?php echo JText::_('GURU_KWDS');?>:
	                </td>
	                <td>
	                    <textarea cols="40" name="metakwd" class="inputbox"><?php echo $program->metakwd; ?></textarea>
	                    <span class="editlinktip hasTip" title="<?php echo JText::_("GURU_TIP_KWDS"); ?>" >
	                    <img border="0" src="components/com_guru/images/icons/tooltip.png">
	                    </span>
	                    <br>
	                </td>
	            </tr>
	            <tr>
	                <td>
	                    <?php echo JText::_('GURU_DSCS');?>:
	                </td>
	                <td>
	                    <textarea cols="40" name="metadesc" class="inputbox"><?php echo $program->metadesc; ?></textarea>
	                    <span class="editlinktip hasTip" title="<?php echo JText::_("GURU_TIP_DSCS"); ?>" >
	                    <img border="0" src="components/com_guru/images/icons/tooltip.png">
	                    </span>
	                    <br>
	                </td>
	            </tr>
	        </table>
	    </div>
	    <div class="tab-pane" id="prog_day_req">
	        <table class="adminform">
	            <tr>
	                <td  valign="middle">
	                    <?php echo JText::_('GURU_PRERQSC'); ?>:
	                </td>
	                <td>
	                    <div style="float:left;">
	                        <a rel="{handler: 'iframe', size: {x: 700, y: 450}}" href="index.php?option=com_guru&controller=guruPrograms&task=addcourse&tmpl=component&cid[]=<?php echo $program->id;?>" class="openModal modal modal-button" data-toggle="modal" data-target="#GuruModal"><span title="Parameters" class="icon-32-config"></span><?php echo JText::_("GURU_ADD_COURS_BUTTON"); ?></a>
	                    </div>
	                    <div style="float:left;">
	                        <span class="editlinktip hasTip" title="<?php echo JText::_("GURU_TIP_PRERQSC"); ?>" >
	                        <img border="0" src="components/com_guru/images/icons/tooltip.png">
	                        </span>
	                    </div>
	                    <br /><br />
	                    <?php
	                        $mmediam_preq=((array)$this->mmediam_preq) ? $this->mmediam_preq: array();
	                        $table_display = "table";
	                        
	                        if($mmediam_preq == NULL || (is_array($mmediam_preq) && count($mmediam_preq) <= 0)){
	                            $table_display = "none";
	                        }
	                        ?>
	                    <table id="table_courses_id" cellspacing="1" cellpadding="5" border="0" bgcolor="#cccccc" width="100%" style="display:<?php echo $table_display; ?>;">
	                        <?php
	                            $preq_existing_ids = array();
	                            if($program->id !="" || $program->id != NULL){
	                            	$preq_existing_ids = guruAdminModelguruProgram::preq_existing_ids($program->id);	
	                            }											
	                            ?>	
	                        <tbody id="rowspreq">
	                            <tr>
	                                <td width="8%"><strong><?php echo JText::_("GURU_ID"); ?></strong></td>
	                                <td width="56%"><strong><?php echo JText::_("GURU_NAME"); ?></strong></td>
	                                <td width="12%"><strong><?php echo JText::_("GURU_REMOVE"); ?></strong></td>
	                            </tr>
	                            <?php
	                                $table_rows = "";
	                                if($mmediam_preq!=NULL){
	                                    foreach($mmediam_preq as $element){
	                                        $table_rows .= "<tr id='tr_".$element->media_id."'>";
	                                        $table_rows .= 		"<td>".$element->media_id."</td>";
	                                        $table_rows .= 		'<td><a class="a_guru" target="_blank" href="index.php?option=com_guru&controller=guruTasks&task=edit&cid[]='.$element->media_id.'">'.$element->name."</a></td>";
	                                        $table_rows .= 		'<td><img onclick="deleteCourse(\''.$element->media_id.'\');" alt="delete" src="'.JURI::root()."administrator/components/com_guru/images/delete2.gif".'" /></td>';
	                                        $table_rows .= "</tr>";
	                                    }
	                                    echo $table_rows;
	                                }
	                                
	                                ?>
	                        </tbody>
	                    </table>
	                </td>
	            </tr>
	            <tr>
	                <td >
	                    <?php echo JText::_('GURU_DAY_OPREQ'); ?>:
	                </td>
	                <td>
	                    <div class= "tinymce-editor-container" onmouseover="document.getElementById('editor_clicked').value = 'pre_req';">
	                        <?php echo $editorul->display( 'pre_req', ''.stripslashes($program->pre_req),'100%', '220px', '20', '50' );?>
	                    </div>
	                </td>
	                <td>
	                    <span class="editlinktip hasTip" title="<?php echo JText::_("GURU_TIP_DAY_OPREQ"); ?>" >
	                    <img border="0" src="components/com_guru/images/icons/tooltip.png">
	                    </span>
	                </td>
	            </tr>
	            <tr>
	                <td>
	                    <?php echo JText::_('GURU_DAY_PREQBK');?>:
	                </td>
	                <td>
	                    <div class= "tinymce-editor-container" onmouseover="document.getElementById('editor_clicked').value = 'pre_req_books';">
	                        <?php echo $editorul->display( 'pre_req_books', ''.stripslashes($program->pre_req_books),'100%', '220px', '20', '50' );?>
	                    </div>
	                    <br>
	                </td>
	                <td>
	                    <span class="editlinktip hasTip" title="<?php echo JText::_("GURU_TIP_DAY_PREQBK"); ?>" >
	                    <img border="0" src="components/com_guru/images/icons/tooltip.png">
	                    </span>
	                </td>
	            </tr>
	            <tr>
	                <td>
	                    <?php echo JText::_('GURU_DAY_OREQ');?>:
	                </td>
	                <td>
	                    <div class= "tinymce-editor-container" onmouseover="document.getElementById('editor_clicked').value = 'reqmts';">
	                        <?php echo $editorul->display( 'reqmts', ''.stripslashes($program->reqmts),'100%', '220px', '20', '50' );?>
	                    </div>
	                    <br>
	                    <input type="hidden" value="<?php echo implode(",", $preq_existing_ids); ?>," name="preqfiles" id="preqfiles">			
	                </td>
	                <td>
	                    <span class="editlinktip hasTip" title="<?php echo JText::_("GURU_TIP_DAY_OREQ"); ?>" >
	                    <img border="0" src="components/com_guru/images/icons/tooltip.png">
	                    </span>
	                </td>
	            </tr>
	        </table>
	    </div>

	    <div class="tab-pane" id="prog_mailchimp">
	    	<?php
	    		$mailchimp_api = "";
				$mailchimp_list_id = "";
				$mailchimp_auto = "1";

				if(isset($program->mailchimp_api) && trim($program->mailchimp_api) != ""){
	    			$mailchimp_api = $program->mailchimp_api;
	    		}

	    		if(isset($program->mailchimp_list_id) && trim($program->mailchimp_list_id) != ""){
					$mailchimp_list_id = $program->mailchimp_list_id;
				}

				if(isset($program->mailchimp_auto)){
					$mailchimp_auto = $program->mailchimp_auto;
				}
	    	?>

	    	<table class="adminform">
	    		<tr>
	    			<td>
	    				<?php echo JText::_('GURU_MAILCHIMP_API');?>
	    			</td>
	    			<td>
	    				<input type="text" name="mailchimp_api" value="<?php echo $mailchimp_api; ?>" />
		                <span class="editlinktip hasTip" title="<?php echo JText::_("GURU_MAILCHIMP_API_TIP"); ?>" >
		                    <img border="0" src="components/com_guru/images/icons/tooltip.png">
		                </span>
	    			</td>
	    		</tr>

	    		<tr>
	    			<td>
	    				<?php echo JText::_('GURU_MAILCHIMP_LIST_ID');?>
	    			</td>
	    			<td>
	    				<input type="text" name="mailchimp_list_id" value="<?php echo $mailchimp_list_id; ?>" />
		                <span class="editlinktip hasTip" title="<?php echo JText::_("GURU_MAILCHIMP_LIST_ID_TIP"); ?>" >
		                    <img border="0" src="components/com_guru/images/icons/tooltip.png">
		                </span>
	    			</td>
	    		</tr>

	    		<tr>
	    			<td>
	    				<?php echo JText::_('GURU_MAILCHIMP_AUTO_REGISTER');?>
	    			</td>
	    			<td>
	    				<fieldset class="radio btn-group" id="single_video_p_show_length">
		                    <?php
		                        $yes_cheched = "";
		                        $no_checked = "";
		                        
		                        if($mailchimp_auto == "0"){
		                            $no_checked = 'checked="checked"';
		                        }
		                        else{
		                            $yes_cheched = 'checked="checked"';
		                        }
		                    ?>
		                    <input type="hidden" name="mailchimp_auto" value="0">
		                    <input type="checkbox" <?php echo $yes_cheched; ?> value="1" name="mailchimp_auto" class="ace-switch ace-switch-5">
		                    <span class="lbl"></span>
		                </fieldset>
		                <span class="editlinktip hasTip" title="<?php echo JText::_("GURU_MAILCHIMP_AUTO_REGISTER_TIP"); ?>" >
		                    <img border="0" src="components/com_guru/images/icons/tooltip.png">
		                </span>
	    			</td>
	    		</tr>
	    	</table>

	    </div>
	</div>
   <input type="hidden" name="id" value="<?php echo $program->id; ?>" />
    <input type="hidden" name="task" value="" />
    <input type="hidden" name="option" value="com_guru" />
    <input type="hidden" name="controller" value="guruPrograms" />
    <input type="hidden" name="boxchecked" value="" />
    <input type="hidden" name="task2" value="edit" />
    <input type="hidden" name="media_number" value="<?php echo @$n; ?>" id="media_number"/>	
    <input type="hidden" id="editor_clicked" name="editor_clicked" value="" />
    
    <script type="text/javascript" language="javascript">
		function jSelectArticle(id, title, catid, object, link, lang){
			editor_clicked = document.getElementById("editor_clicked").value;
			
			var hreflang = '';
			if (lang !== '')
			{
				var hreflang = ' hreflang = "' + lang + '"';
			}
			var tag = '<a' + hreflang + ' href="' + link + '">' + title + '</a>';
			jInsertEditorText(tag, editor_clicked);
			SqueezeBox.close();
		}
	</script>	
</form>
<?php
include(JPATH_SITE.'/administrator/components/com_guru/views/modals/modal_with_iframe.php');
?>
<script type="text/javascript" language="javascript" src="components/com_guru/js/modal_with_iframe.js"> </script>
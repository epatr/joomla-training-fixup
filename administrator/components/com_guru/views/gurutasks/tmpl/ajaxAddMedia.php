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

$database = JFactory::getDBO();
$type = JFactory::getApplication()->input->get("type", "");
$idd = JFactory::getApplication()->input->get("id");

$session = JFactory::getSession();
$registry = $session->get('registry');
$registry->set('neededid', $idd);

$typeart = "SELECT type from #__guru_media where id =".$idd;
$database->setQuery($typeart);
$result = $database->loadColumn();
@$the_media->type = $result[0];

if($type == "quiz"){
	$q  = "SELECT * FROM #__guru_quiz WHERE id = ".$idd;
	$database->setQuery($q);
	$result = $database->loadObjectList();
	$the_media = $result["0"];
	$the_media->type = "quiz";	
}
elseif($type == "project"){
	$q = "SELECT * FROM #__guru_projects WHERE id = ".$idd;
	$database->setQuery($q);
	$result = $database->loadObjectList();
	$the_media = $result["0"];
	$the_media->type = "project";	
}
else{
	$q  = "SELECT * FROM #__guru_media WHERE id = ".$idd;
	$database->setQuery($q);
	$result = $database->loadObjectList();
	$the_media = $result;
	$the_media = $the_media["0"];		
}

$q  = "SELECT * FROM #__guru_config WHERE id = '1' ";
$database->setQuery($q);
$configs = $database->loadObjectList();
$configs = $configs["0"];

if(isset($the_media->code)){
	$the_media->code = stripslashes($the_media->code);
}

$no_plugin_for_code = 0;
$aheight=0; $awidth=0; $vheight=0; $vwidth=0;

$default_size = $configs->default_video_size;
$default_width = "";
$default_height = "";

if(trim($default_size) != ""){
	$default_size = explode("x", $default_size);
	$default_width = $default_size["1"];
	$default_height = $default_size["0"];
}

if($the_media->type=='video'){
	if($the_media->source=='url' || $the_media->source=='local'){
		if(($the_media->width == 0 || $the_media->height == 0) && $the_media->option_video_size == 1){
			$vheight=300; 
			$vwidth=400;
		}
		elseif(($the_media->width != 0 && $the_media->height != 0) && $the_media->option_video_size == 1){
			$vheight = $the_media->height; 
			$vwidth = $the_media->width;
		}
		elseif($the_media->option_video_size == 0){
			$vheight = $default_height; 
			$vwidth = $default_width;
		}					
	}
	elseif($the_media->source=='code'){			
		if(($the_media->width == 0 || $the_media->height == 0) && $the_media->option_video_size == 1){
			$begin_tag = strpos($the_media->code, 'width="');
			if($begin_tag!==false){
				$remaining_code = substr($the_media->code, $begin_tag+7, strlen($the_media->code));
				$end_tag = strpos($remaining_code, '"');
				$vwidth = substr($remaining_code, 0, $end_tag);
				$begin_tag = strpos($the_media->code, 'height="');
				if($begin_tag!==false){
					$remaining_code = substr($the_media->code, $begin_tag+8, strlen($the_media->code));
					$end_tag = strpos($remaining_code, '"');
					$vheight = substr($remaining_code, 0, $end_tag);
					$no_plugin_for_code = 1;
				}
				else{
					$vheight=300;
					$vwidth=400;
				}	
			}	
			else{
				$vheight=300;
				$vwidth=400;
			}	
		}
		elseif(($the_media->width != 0 || $the_media->height != 0) && $the_media->option_video_size == 1){
			$replace_with = 'width="'.$the_media->width.'"';
			$the_media->code = preg_replace('#width="[0-9]+"#', $replace_with, $the_media->code);
			$replace_with = 'height="'.$the_media->height.'"';
			$the_media->code = preg_replace('#height="[0-9]+"#', $replace_with, $the_media->code);
			$replace_with = 'name="width" value="'.$the_media->width.'"';
			$the_media->code = preg_replace('#name="width" value="[0-9]+"#', $replace_with, $the_media->code);
			$replace_with = 'name="height" value="'.$the_media->height.'"';
			$the_media->code = preg_replace('#name="height" value="[0-9]+"#', $replace_with, $the_media->code);	
			$vheight=$the_media->height; $vwidth=$the_media->width;	
		}
		elseif($the_media->option_video_size == 0){
			$replace_with = 'width="'.$default_width.'"';
			$the_media->code = preg_replace('#width="[0-9]+"#', $replace_with, $the_media->code);
			$replace_with = 'height="'.$default_height.'"';
			$the_media->code = preg_replace('#height="[0-9]+"#', $replace_with, $the_media->code);
			
			$replace_with = 'name="width" value="'.$default_width.'"';
			$the_media->code = preg_replace('#value="[0-9]+" name="width"#', $replace_with, $the_media->code);
			$replace_with = 'name="height" value="'.$default_height.'"';
			$the_media->code = preg_replace('#value="[0-9]+" name="height"#', $replace_with, $the_media->code);
			
			$replace_with = 'name="width" value="'.$default_width.'"';
			$the_media->code = preg_replace('/name="width" value="[0-9]+"/', $replace_with, $the_media->code);
			$replace_with = 'name="height" value="'.$default_height.'"';
			$the_media->code = preg_replace('/name="height" value="[0-9]+"/', $replace_with, $the_media->code);
			
			$vheight = $default_height;
			$vwidth = $default_width;
		}
	}
}		
elseif($the_media->type=='audio'){
	if ($the_media->source=='url' || $the_media->source=='local'){	
		if ($the_media->width == 0 || $the_media->height == 0){
			$aheight=20; $awidth=300;
		}
		else{
			$aheight=$the_media->height; $awidth=$the_media->width;
		}
	}		
	elseif ($the_media->source=='code'){				
		if ($the_media->width == 0 || $the_media->height == 0){
			$begin_tag = strpos($the_media->code, 'width="');
			if ($begin_tag!==false){
				$remaining_code = substr($the_media->code, $begin_tag+7, strlen($the_media->code));
				$end_tag = strpos($remaining_code, '"');
				$awidth = substr($remaining_code, 0, $end_tag);			
				$begin_tag = strpos($the_media->code, 'height="');
				if ($begin_tag!==false){
					$remaining_code = substr($the_media->code, $begin_tag+8, strlen($the_media->code));
					$end_tag = strpos($remaining_code, '"');
					$aheight = substr($remaining_code, 0, $end_tag);
					$no_plugin_for_code = 1;
				}
				else{
					$aheight=20; 
					$awidth=300;
				}	
			}else{
				$aheight=20; $awidth=300;
			}							
		}				
		else{
			$replace_with = 'width="'.$the_media->width.'"';
			$the_media->code = preg_replace('#width="[0-9]+"#', $replace_with, $the_media->code);
			$replace_with = 'height="'.$the_media->height.'"';
			$the_media->code = preg_replace('#height="[0-9]+"#', $replace_with, $the_media->code);
			$aheight=$the_media->height; $awidth=$the_media->width;
		}
	}	
}

//--------------------------------
if($the_media->type == 'video' && $the_media->source == "url"){
	require_once(JPATH_ROOT .'/components/com_guru/helpers/videos/helper.php');
	$parsedVideoLink = parse_url($the_media->url);
	preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $parsedVideoLink['host'], $matches);
	$domain	= $matches['domain'];
	
	if(!empty($domain)){
		$provider		= explode('.', $domain);
		$providerName	= strtolower($provider[0]);
		
		if($providerName == "youtu"){
			$providerName = "youtube";
		}
		
		$libraryPath = JPATH_ROOT .'/components/com_guru/helpers/videos' .'/'. $providerName . '.php';

		if(!file_exists($libraryPath)){
			$the_media->source = 'local';
			$the_media->local = $the_media->url;
			$the_media->exception = "1";
		}
	}
}
//--------------------------------

$parts = explode(".", @$the_media->local);
$extension = $parts[count($parts)-1];

if($the_media->type=="video" || $the_media->type=="audio"){
	if($the_media->type=='video' && $extension=="avi"){
		$server=$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];
		$root=explode("administrator",$server);
		$root="http://".$root[0];
		
		$media = '<object width="'.$vwidth.'" height="'.$vheight.'" type="application/x-oleobject" codebase="http://activex.microsoft.com/activex/controls/mplayer/en/nsmp2inf.cab#Version=5,1,52,701" classid="CLSID:22d6f312-b0f6-11d0-94ab-0080c74c7e95" id="MediaPlayer1">
<param value="'.$root.$configs->videoin."/".$the_media->local.'" name="fileName">
<param value="true" name="animationatStart">
<param value="true" name="transparentatStart">
<param value="true" name="autoStart">
<param value="true" name="showControls">
<param value="10" name="Volume">
<param value="false" name="autoplay">
<embed width="'.$vwidth.'" height="'.$vheight.'" type="video/x-msvideo" src="'.$root.$configs->videoin."/".$the_media->local.'" name="plugin">
</object>';
	}
	elseif($the_media->type=='video' && $extension=="mp4"){
		$server=$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];
		$root=explode("administrator",$server);
		$root = "http://".$root[0];
		
		if($the_media->auto_play == 0){
			$auto_play_object = 'autoplay="false"';
		}
		else{
			$auto_play_object = 'autoplay="true"';
		}
		
		$video_path = $root.$configs->videoin."/".$the_media->local;
		
		if(isset($the_media->exception) && intval($the_media->exception) == 1){
			$video_path = $the_media->local;
		}
		
		$media = '<video width="670" height="400" controls><source src="'.$video_path.'" type="video/mp4" /></video>';
	}
	elseif($no_plugin_for_code == 0){
		if($the_media->type == "video" && $the_media->source == "url"){
			$configs = getConfig();
			$video_size = $configs->default_video_size;
			
			if(trim($video_size) != ""){
				$temp = explode("x", trim($video_size));
				$the_media->width = $temp["1"];
				$the_media->height = $temp["0"];
			}
			
			if($the_media->width==0){
				$the_media->width=400;
			}
			
			require_once(JPATH_ROOT .'/components/com_guru/helpers/videos/helper.php');
			$parsedVideoLink = parse_url($the_media->url);
			preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $parsedVideoLink['host'], $matches);
			$domain	= $matches['domain'];
			
			if (!empty($domain)){
				$provider		= explode('.', $domain);
				$providerName	= strtolower($provider[0]);
				
				if($providerName == "youtu"){
					$providerName = "youtube";
				}
				
				$libraryPath	= JPATH_ROOT .'/components/com_guru/helpers/videos' .'/'. $providerName . '.php';
				require_once($libraryPath);
				$className		= 'PTableVideo' . ucfirst($providerName);
				$videoObj		= new $className();
				$videoObj->init($the_media->url);
				$video_id		= $videoObj->getId();
				$videoPlayer	= $videoObj->getViewHTML($video_id, $the_media->width, $the_media->height);
				$media = $videoPlayer;
			}
		}
		else{
			$media = create_media_using_plugin($the_media, $configs, $awidth, $aheight, $vwidth, $vheight);
		}
	}	
	else{
		$media = $the_media->code;
	}
}	

if(($the_media->type == 'quiz')||($the_media->type == 'docs')){
	$media = parse_quiz($the_media->id,$the_media->type);
	$media = str_replace('type="submit"','type="button"',$media);
	$media = str_replace("type='submit'","type='button'",$media);
}	

if($the_media->type=='url'){
	if($the_media->width==1){
		$src = $the_media->url;
		$media = '<a href="'.$src.'" target="_blank">'.$src.'</a>';
	}
	else{
		$media = '<div class="contentpane">
			<iframe id="blockrandom" name="iframe" src="'.$the_media->url.'" width="100%"
scrolling="auto" align="top"
							frameborder="2"
							class="wrapper">
							This option will not work correctly. Unfortunately, your browser does not support inline frames.</iframe>
						</div>';
	}	
}
if(@$the_media->type == "file"){			
	$media = '<a target="_blank" href="'.JURI::ROOT().$configs->filesin.'/'.$the_media->local.'">'.$the_media->name.'</a><br/><br/>'.$the_media->instructions;
}
if($the_media->type == 'image')	{
	$url=$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	$url="http://".substr($url,0,strpos($url,"administrator"));
	$media = "<img src='".$url.$configs->imagesin."/media/thumbs".$the_media->local."' />";
}

if($the_media->type == 'Article'){
	$code = "SELECT `introtext` , `fulltext` FROM `#__content` WHERE `id`=".$the_media->code;
	$database->setQuery($code);
	$result = $database->loadAssocList();
	$the_media = $result[0]["introtext"].$result[0]["fulltext"];

	$url=$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	$url="http://".substr($url,0,strpos($url,"administrator"));
	$pattern = '/src="([^"]*)"/';
		preg_match($pattern,$the_media, $matches);
		$src = $matches[1];
		if(count($src) >0){
			for($i=0; $i<count($src); $i++){
				
				$src1 = $url.$src;
				$media = str_replace($src, $src1, $the_media);
			}
		}
		else {
			$media = $the_media;
		}
}

if($the_media->type=='project'){
	$db = JFactory::getDbo();
	
	$sql = "select c.`name` from #__guru_program c, #__guru_projects p where c.`id`=p.`course_id` and p.`id`=".intval($the_media->id);
	$db->setQuery($sql);
	$db->execute();
	$course_name = $db->loadColumn();
	$course_name = @$course_name["0"];

	$sql = "select u.`name` from #__users u, #__guru_projects p where u.`id`=p.`author_id` and p.`id`=".intval($the_media->id);
	$db->setQuery($sql);
	$db->execute();
	$user_name = $db->loadColumn();
	$user_name = @$user_name["0"];

	$media = '
		<table style="margin:auto;">
			<tr>
				<th style="text-align:right; padding-right:15px;">'.JText::_("GURU_TITLE").':</th>
				<td style="text-align:left; padding:0px !important;">'.$the_media->title.'</td>
			</tr>
			<tr>
				<th style="text-align:right; padding-right:15px;">'.JText::_("GURU_COURSE").':</th>
				<td style="text-align:left; padding:0px !important;">'.$course_name.'</td>
			</tr>
			<tr>
				<th style="text-align:right; padding-right:15px;">'.JText::_("GURU_AUTHOR_CERTIFICATE").':</th>
				<td style="text-align:left; padding:0px !important;">'.$user_name.'</td>
			</tr>
		</table>
	';
}

if(isset($media)){
	echo $media;
}
else{
	echo NULL;
}

function parse_quiz ($id,$type){
	$database = JFactory::getDBO();		
	$q = "SELECT * FROM #__guru_config WHERE id = '1' ";
	$database->setQuery($q);
	$configs = $database->loadObject();
		
	if($type=="quiz"){
		$q  = "SELECT * FROM #__guru_quiz WHERE id = ".$id;
		$database->setQuery( $q );
		$result = $database->loadObject();
		$the_media = $result;
		$the_media->type="quiz";
	}
	else{
		$q  = "SELECT * FROM #__guru_media WHERE id = ".$id;
		$database->setQuery( $q );
		$result = $database->loadObject();
		$the_media = $result;	
	}
	
	if($the_media->type=='text'){
		$media = $the_media->code;
	}
	if($the_media->type=='Article'){
		$media = $the_media->code;
	}
	if($the_media->type=='docs'){
		$the_base_link = explode('administrator/', $_SERVER['HTTP_REFERER']);
		$the_base_link = $the_base_link[0];				

		$media = 'The selected element is a text file that can\'t have a preview';
			
		if($the_media->source == 'local' && (substr($the_media->local,(strlen($the_media->local)-3),3) == 'txt' || substr($the_media->local,(strlen($the_media->local)-3),3) == 'pdf') && $the_media->width > 1) {
			$media='<div class="contentpane">
					<iframe id="blockrandom"
						name="iframe"
						src="'.$the_base_link.'/'.$configs->docsin.'/'.$the_media->local.'"
						width="'.$the_media->width.'"
						height="'.$the_media->height.'"
						scrolling="auto"
						align="top"
						frameborder="2"
						class="wrapper">
						This option will not work correctly. Unfortunately, your browser does not support inline frames.</iframe>
					</div>';
		}
		elseif($the_media->source == 'url' && (substr($the_media->url,(strlen($the_media->url)-3),3) == 'txt' || substr($the_media->url,(strlen($the_media->url)-3),3) == 'pdf') && $the_media->width > 1) {
			$media='<div class="contentpane">
					<iframe id="blockrandom"
						name="iframe"
						src="'.$the_media->url.'"
						width="'.$the_media->width.'"
						height="'.$the_media->height.'"
						scrolling="auto"
						align="top"
						frameborder="2"
						class="wrapper">
						This option will not work correctly. Unfortunately, your browser does not support inline frames.</iframe>
					</div>';
		}
							
		if($the_media->source == 'local' && $the_media->width == 1)
			$media='<br /><a href="'.$the_base_link.'/'.$configs->docsin.'/'.$the_media->local.'" target="_blank">'.$the_media->name.'</a>';
	
		if($the_media->source == 'url'  && $the_media->width == 0)
			$media='<div class="contentpane">
						<iframe id="blockrandom"
						name="iframe"
						src="'.$the_media->url.'"
						width="100%"
						height="600"
						scrolling="auto"
						align="top"
						frameborder="2"
						class="wrapper">
						This option will not work correctly. Unfortunately, your browser does not support inline frames.</iframe>
					</div>';		
							
		if($the_media->source == 'url'  && $the_media->width == 1)
			$media='<a href="'.$the_media->url.'" target="_blank">'.$the_media->name.'</a>';								
	}	
	
	if($the_media->type=='quiz'){		
		$the_media->source=$the_media->id;
		
		$media = '';
			
		$q  = "SELECT * FROM #__guru_quiz WHERE id = ".$the_media->source;
		$database->setQuery( $q );
		$result_quiz = $database->loadObject();	
		$media .= '<span class="guru-quiz__title">'.$result_quiz->name.'</span>';
		$media .= '<span class="guru-quiz__desc">'.$result_quiz->description.'</span>';
				
		if($result_quiz->is_final == 1){
			$sql = "SELECT 	quizzes_ids FROM #__guru_quizzes_final WHERE qid=".$the_media->source;
			$database->setQuery($sql);
			$database->execute();
			$result=$database->loadResult();	
			$result_qids = explode(",",trim($result,","));
		
		
			$q  = "SELECT * FROM #__guru_questions_v3 WHERE qid IN (".implode(",", $result_qids).") and published=1 order by question_order";
			
		}
		else{
			$q  = "SELECT * FROM #__guru_questions_v3 WHERE qid =".$the_media->source." and published=1 order by question_order";
		}	
		
			$database->setQuery( $q );
			$database->execute();
			$quiz_questions = $database->loadObjectList();			
				
			$media = $media.'<div id="the_quiz">';
				
			$question_number = 1;
			
			for($i=0;$i<count($quiz_questions);$i++){
				$question_answers_number = 0;
				$media_associated_question = json_decode($quiz_questions[$i]->media_ids);
				$media_content = "";
				$result_media = array();
				
				$q  = "SELECT * FROM #__guru_question_answers WHERE question_id = ".intval($quiz_questions[$i]->id)." ORDER BY id";
				$database->setQuery( $q );
				$question_answers = $database->loadObjectList();	
				
								
				for($j=0; $j<count($media_associated_question); $j++){
					@$media_that_needs_to_be_sent = getMediaFromId($media_associated_question[$j]);
					if(isset($media_that_needs_to_be_sent) && count($media_that_needs_to_be_sent) > 0){
						$result_media[] = create_media_using_plugin($media_that_needs_to_be_sent["0"], $configs, '', '', '100px', 100);
					}
				}
				
				$media .= '<div class="guru-quiz__question guru-question">';
				$media .= 	'<div class="guru-quiz__media">'.implode("",$result_media).'</div>';
				$media .= 	'<div class="guru-quiz__question-title">';
				$media .= 		$quiz_questions[$i]->question_content."";
				$media .= 	'</div>';
					
				$media .= '<div class="guru-quiz__answers-wrapper">';
				$media .= '<div class="guru-quiz__answers uk-grid uk-grid-small" data-uk-grid-match data-uk-grid-margin>';
				
				if($quiz_questions[$i]->type == "true_false"){
					foreach($question_answers as $question_answer){
						if($question_answer->answer_content_text == "True"){
							$question_answer->answer_content_text = JText::_("GURU_QUESTION_OPTION_TRUE");
						}
						elseif($question_answer->answer_content_text == "False"){
							$question_answer->answer_content_text = JText::_("GURU_QUESTION_OPTION_FALSE");
						}
						
						$questions_html_ids["true"][$question_answer->question_id][] = $question_answer->question_id . intval($question_answer->id);
						
						$media .= '<div class="guru-quiz__answer-wrapper uk-width-1-1 uk-width-medium-1-3" id="guru-question-answer-'.$question_answer->question_id.'-'.$question_answer->id.'">
											 <div class="guru-quiz__answer">
												 <div class="uk-float-left">
													<input type="radio" id="'.$question_answer->question_id.intval($question_answer->id).'"  name="truefs_ans['.intval($question_answer->question_id).']" value="'.$question_answer->id.'" />
													<label for="'.$question_answer->question_id.intval($question_answer->id).'" class="guru-quiz__check-box"></label>
												 </div>
												 <div class="uk-float-left">
													'.$question_answer->answer_content_text.'
												 </div>
											 </div>
										 </div>';
					}
				}
				elseif($quiz_questions[$i]->type == "single"){
					if(isset($question_answers) && count($question_answers) > 0){
						foreach($question_answers as $question_answer){
							$media_associated_answers = json_decode($question_answer->media_ids);
							$media_content = "";
							$result_media_answers = array();
							
							if(isset($media_associated_answers) && count($media_associated_answers) > 0){
								foreach($media_associated_answers as $media_key=>$answer_media_id){
									$media_that_needs_to_be_sent = getMediaFromId($answer_media_id);
									
									if(isset($media_that_needs_to_be_sent) && count($media_that_needs_to_be_sent) > 0){
										$result_media_answers[] = create_media_using_plugin($media_that_needs_to_be_sent["0"], $configs, '', '', '100px', 100);
									}
								}
							}
							
							$questions_html_ids["simple"][$question_answer->question_id][] = 'ans' . $question_answer->question_id . intval($question_answer->id);
							
							$option_value = '<input type="radio" id="ans'.$question_answer->question_id.intval($question_answer->id).'" name="answers_single" value="'.$question_answer->id.'"/><label for="ans'.$question_answer->question_id.intval($question_answer->id).'" class="guru-quiz__check-box"></label> <span>'.$question_answer->answer_content_text.'</span><div class="guru-quiz__answer-media">'.implode("",$result_media_answers).'</div>';
							
							$media .= '<div class="guru-quiz__answer-wrapper uk-width-1-1 uk-width-medium-1-3" id="guru-question-answer-'.$question_answer->question_id.'-'.$question_answer->id.'"><div class="guru-quiz__answer">'.$option_value.'</div></div>';
						}
					}						
				}
				elseif($quiz_questions[$i]->type == "multiple"){
					if(isset($question_answers) && count($question_answers) > 0){
						foreach($question_answers as $question_answer){
							$media_associated_answers = json_decode($question_answer->media_ids);
							$media_content = "";
							$result_media_answers = array();
							
							if(isset($media_associated_answers) && count($media_associated_answers) > 0){
								foreach($media_associated_answers as $media_key=>$answer_media_id){
									$media_that_needs_to_be_sent = getMediaFromId($answer_media_id);
									
									if(isset($media_that_needs_to_be_sent) && count($media_that_needs_to_be_sent) > 0){
										$result_media_answers[] = create_media_using_plugin($media_that_needs_to_be_sent["0"], $configs, '', '', '100px', 100);
									}
								}
							}
							
							$questions_html_ids["multiple"][$question_answer->question_id][] = intval($question_answer->id);
							
							$option_value = '<input type="checkbox" name="multiple_ans['.intval($quiz_questions[$i]->id).'][]" id="'.$question_answer->id.'" value="'.$question_answer->id.'"/><label for="'.$question_answer->id.'" class="guru-quiz__check-box"></label> <span>'.$question_answer->answer_content_text.'</span><div class="guru-quiz__answer-media">'.implode("",$result_media_answers).'</div>';
							
							$media .= '<div class="guru-quiz__answer-wrapper uk-width-1-1 uk-width-medium-1-3" id="guru-question-answer-'.$question_answer->question_id.'-'.$question_answer->id.'"><div class="guru-quiz__answer">'.$option_value.'</div></div>';
						}
					}		
				}
				$media .= '</div>';					
				$media .= '</div>';
				$media .= '</div>';
			}
			
			$media = $media.'<input type="hidden" value="'.($question_number-1).'" name="question_number" id="question_number" />';
			$media = $media.'<input type="hidden" value="'.$result_quiz->name.'" id="quize_name" name="quize_name"/>';
			//$media = $media.'<br /><div align="left" style="clear:both;"><input type="submit" class ="btn" value="Submit" onclick="get_quiz_result()" /></div>';	
			$media = $media.'</div>';
		}	
		
	return $media;	
}	
	
function jwAllVideos( &$row, $parawidth=300, $paraheight=20, $parvwidth=400, $parvheight=300,$layout_id="") {
		$final_autoplay2 = FALSE;
		$auto_play = FALSE;
		$app = JFactory::getApplication('administrator');
		$plg_name					= "jw_allvideos";
		$plg_tag					= "";
		$plg_copyrights_start		= "\n\n<!-- JoomlaWorks \"AllVideos\" Plugin (v4.5.0) starts here -->\n";
		$plg_copyrights_end			= "\n<!-- JoomlaWorks \"AllVideos\" Plugin (v4.5.0) ends here -->\n\n";
		$mosConfig_live_site = JURI::root();
		$document  = JFactory::getDocument();
		
    	if(substr($mosConfig_live_site, -1)=="/"){
			$mosConfig_live_site = substr($mosConfig_live_site, 0, -1);
		}
		jimport('joomla.filesystem.file');

		if(JPluginHelper::isEnabled('content',$plg_name)==false) return;
		

		//include(JPATH_SITE.DIRECTORY_SEPARATOR."plugins".DIRECTORY_SEPARATOR."content".DIRECTORY_SEPARATOR."jw_allvideos".DIRECTORY_SEPARATOR."includes".DIRECTORY_SEPARATOR."helper.php");
		include(JPATH_SITE.DIRECTORY_SEPARATOR."plugins".DIRECTORY_SEPARATOR."content".DIRECTORY_SEPARATOR."jw_allvideos".DIRECTORY_SEPARATOR."jw_allvideos".DIRECTORY_SEPARATOR."includes".DIRECTORY_SEPARATOR."sources.php");
		$grabTags = str_replace("(","",str_replace(")","",implode(array_keys($tagReplace),"|")));
		
		if(preg_match("#{(".$grabTags.")}#s",$row)==false) return;
			// add CSS/JS to the head
		
		$plugin = JPluginHelper::getPlugin('content', $plg_name);
		$pluginParams = new JRegistry($plugin->params);

		/* Preset Parameters */
		/*$skin										= 'bekle';
		/* Video Parameters */
		//$playerTemplate					= ($params->get('playerTemplate')) ? $params->get('playerTemplate') : $pluginParams->get('playerTemplate','Classic');
		//$vfolder 								= ($params->get('vfolder')) ? $params->get('vfolder') : $pluginParams->get('vfolder','images/stories/videos');
		//$vwidth 								= ($params->get('vwidth')) ? $params->get('vwidth') : $pluginParams->get('vwidth',400);
		//$vheight 								= ($params->get('vheight')) ? $params->get('vheight') : $pluginParams->get('vheight',300);
		//$transparency 					= $pluginParams->get('transparency','transparent');
		//$background 						= $pluginParams->get('background','#010101');
		//$backgroundQT						= $pluginParams->get('backgroundQT','black');
		//$controlBarLocation 		= $pluginParams->get('controlBarLocation','bottom');
		
		/* Audio Parameters */
		//$afolder 								= $pluginParams->get('afolder','images/stories/audio');
		//$awidth 								= ($params->get('awidth')) ? $params->get('awidth') : $pluginParams->get('awidth',480);
		//$aheight 								= ($params->get('aheight')) ? $params->get('aheight') : $pluginParams->get('aheight',24);
		$abackground 						= $pluginParams->get('abackground','#010101');
		$afrontcolor 						= $pluginParams->get('afrontcolor','#FFFFFF');
		$alightcolor 						= $pluginParams->get('alightcolor','#00ADE3');
		//$allowAudioDownloading	= $pluginParams->get('allowAudioDownloading',0);
		/* Global Parameters */
		//$autoplay 							= ($params->get('autoplay')) ? $params->get('autoplay') : $pluginParams->get('autoplay',0);
		/* Performance Parameters */
		//$gzipScripts						= $pluginParams->get('gzipScripts',0);
		
		$av_template			= 'getault';
		$av_compressjs			= 0;
		// video
		$vfolder 				= 'images/stories/videos';
		$vwidth 				= $parvwidth;
		$vheight 				= $parvheight;
		// audio
		$afolder 				= '';
		$awidth 				= $parawidth;
		$aheight 				= $paraheight;
		// global
		$autoplay 				= FALSE;
		$transparency 			= 'transparent';
		$background 			= '';
		// FLV playback
		$av_flvcontroller 		= 'bottom';	
	
		if($av_flvcontroller == "over"){
			$av_flvcontroller = "&controlbar=over";
		} else {
			$av_flvcontroller = "";
		}

		// Variable cleanups for K2
		if(JFactory::getApplication()->input->get('format')=='raw'){
			$plg_copyrights_start = '';
			$plg_copyrights_end = '';
		}

		// Assign the AllVideos helper class
		//$AllVideosHelper = new AllVideosHelper;

		// ----------------------------------- Render the output -----------------------------------
		// Append head includes only when the document is in HTML mode
		if(JFactory::getApplication()->input->get('format')=='html' || JFactory::getApplication()->input->get('format')==''|| JFactory::getApplication()->input->get('format')=='raw'){

				// CSS
				//$avCSS = $AllVideosHelper->getTemplatePath($this->plg_name,'css/template.css',$playerTemplate);
				$avCSS = @$avCSS->http;
				$document->addStyleSheet($avCSS);
				// JS
				
			  //JHtml::_('behavior.framework');
			
			if(0){
				$document->addScript($mosConfig_live_site.'/plugins/content/jw_allvideos/jw_allvideos/includes/js/jwp.js.php');
			} 
			else{
				$document->addScript($mosConfig_live_site.'/plugins/content/jw_allvideos/jw_allvideos/includes/js/behaviour.js');
				//$document->addScript($mosConfig_live_site.'/plugins/content/jw_allvideos/jw_allvideos/includes/js/mediaplayer/jwplayer.min.js');
				$document->addScript($mosConfig_live_site.'/plugins/content/jw_allvideos/jw_allvideos/includes/js/wmvplayer/silverlight.js');
				$document->addScript($mosConfig_live_site.'/plugins/content/jw_allvideos/jw_allvideos/includes/js/wmvplayer/wmvplayer.js');
				$document->addScript($mosConfig_live_site.'/plugins/content/jw_allvideos/jw_allvideos/includes/js/quicktimeplayer/ac_quicktime.js');
			}			
		}

		$document  = JFactory::getDocument();
		$document->addScript('https://cdn.jsdelivr.net/gh/clappr/clappr@latest/dist/clappr.min.js');
		$document->addScript($mosConfig_live_site.'/plugins/content/jw_allvideos/jw_allvideos/includes/js/jwp.js.php');
		$document->addScript($mosConfig_live_site.'/plugins/content/jw_allvideos/jw_allvideos/includes/js/behaviour.js');

		// START ALLVIDEOS LOOP	
		foreach ($tagReplace as $plg_tag => $value) {
			// expression to search for
			$regex = "#{".$plg_tag."}.*?{/".$plg_tag."}#s";
			// process tags
			
			if(preg_match_all($regex, $row, $matches, PREG_PATTERN_ORDER)) {
				// start the replace loop
				foreach ($matches[0] as $key => $match) {

					$tagcontent 		= preg_replace("/{.+?}/", "", $match);
					$tagparams 			= explode('|',$tagcontent);
					$tagsource 			= trim(strip_tags($tagparams[0]));

					// Prepare the HTML
					$output = new JObject;

					// Width/height/source folder split per media type

					if(in_array($plg_tag, array(
						'mp3',
						'mp3remote',
						'aac',
						'aacremote',
						'm4a',
						'm4aremote',
						'ogg',
						'oggremote',
						'wma',
						'wmaremote',
						'soundcloud'
					))){
						$final_awidth 	= (@$tagparams[1]) ? $tagparams[1] : $awidth;
						$final_aheight 	= (@$tagparams[2]) ? $tagparams[2] : $aheight;

						$output->playerWidth = $final_awidth;
						$output->playerHeight = $final_aheight;
						$output->folder = $afolder;


						if($plg_tag=='soundcloud'){
							if(strpos($tagsource,'/sets/')!==false){
								$output->mediaTypeClass = ' avSoundCloudSet';
							} else {
								$output->mediaTypeClass = ' avSoundCloudSong';
							}
							$output->mediaType = '';
						} else {
							$output->mediaTypeClass = ' avAudio';
							$output->mediaType = 'audio';
						}

						if(in_array($plg_tag, array('mp3','aac','m4a','ogg','wma'))){
							$output->source = "$siteUrl/$afolder/$tagsource.$plg_tag";
						} elseif(in_array($plg_tag, array('mp3remote','aacremote','m4aremote','oggremote','wmaremote'))){
							$output->source = $tagsource;

						} else {
							$output->source = '';
						}
					} else {
						$final_vwidth 	= (@$tagparams[1]) ? $tagparams[1] : $vwidth;
						$final_vheight 	= (@$tagparams[2]) ? $tagparams[2] : $vheight;

						$output->playerWidth = $final_vwidth;
						$output->playerHeight = $final_vheight;
						$output->folder = $vfolder;
						$output->mediaType = 'video';
						$output->mediaTypeClass = ' avVideo';
					}

					// Autoplay
					$final_autoplay = (@$tagparams[3]) ? $tagparams[3] : $auto_play;
					$final_autoplay	= ($final_autoplay) ? 'TRUE' : 'FALSE';
				

					// Special treatment for specific video providers
					if($plg_tag=="dailymotion"){
						$tagsource = preg_replace("~(http|https):(.+?)dailymotion.com\/video\/~s","",$tagsource);
						$tagsourceDailymotion = explode('_',$tagsource);
						$tagsource = $tagsourceDailymotion[0];
						if($final_autoplay=='true'){
							if(strpos($tagsource,'?')!==false){
								$tagsource = $tagsource.'&amp;autoPlay='.$auto_play.'';
							} else {
								$tagsource = $tagsource.'?autoPlay='.$auto_play.'';
							}
						}
					}

					if($plg_tag=="ku6"){
						$tagsource = str_replace('.html','',$tagsource);
					}

					if($plg_tag=="metacafe" && substr($tagsource,-1,1)=='/'){
						$tagsource = substr($tagsource,0,-1);
					}

					if($plg_tag=="tnaondemand"){
						$tagsource = parse_url($tagsource);
						$tagsource = explode('&',$tagsource['query']);
						$tagsource = str_replace('vidid=','',$tagsource[0]);
					}

					if($plg_tag=="twitvid"){
						$tagsource = preg_replace("~(http|https):(.+?)twitvid.com\/~s","",$tagsource);
						if($final_autoplay=='true'){
							$tagsource = $tagsource.'&amp;autoplay='.$auto_play.'';
						}
					}

					if($plg_tag=="vidiac"){
						$tagsourceVidiac = explode(';',$tagsource);
						$tagsource = $tagsourceVidiac[0];
					}

					if($plg_tag=="vimeo"){
				
						$tagsource = preg_replace("~(http|https):(.+?)vimeo.com\/~s","",$tagsource);
							
						if(strpos($tagsource,'?')!==false){
							$tagsource = $tagsource.'&amp;portrait=0&amp;autoplay='.$auto_play.'';
						} else {
							$tagsource = $tagsource.'?portrait=0&amp;autoplay='.$auto_play.'';
						}
						if($final_autoplay=='true'){
							$tagsource = $tagsource.'&amp;autoplay='.$auto_play.'';
						}
					}

					if($plg_tag=="yahoo"){
						$tagsourceYahoo = explode('-',str_replace('.html','',$tagsource));
						$tagsourceYahoo = array_reverse($tagsourceYahoo);
						$tagsource = $tagsourceYahoo[0];
					}

					if($plg_tag=="yfrog"){
						$tagsource = preg_replace("~(http|https):(.+?)yfrog.com\/~s","",$tagsource);
					}

					if($plg_tag=="youmaker"){
						$tagsourceYoumaker = explode('-',str_replace('.html','',$tagsource));
						$tagsource = $tagsourceYoumaker[1];
					}

					if($plg_tag=="youku"){
						$tagsource = str_replace('.html','',$tagsource);
						$tagsource = substr($tagsource,3);
					}

					if($plg_tag=="youtube"){
						$tagsource = preg_replace("~(http|https):(.+?)youtube.com\/watch\?v=~s","",$tagsource);
						$tagsourceYoutube = explode('&',$tagsource);
						$tagsource = $tagsourceYoutube[0];

						if(strpos($tagsource,'?')!==false){
							$tagsource = $tagsource.'&amp;rel=0&amp;fs=1&amp;wmode=transparent&amp;autoplay='.$auto_play.'';
						} else {
							$tagsource = $tagsource.'?rel=0&amp;fs=1&amp;wmode=transparent&amp;autoplay='.$auto_play.'';
						}
						if($final_autoplay=='true'){
							$tagsource = $tagsource.'&amp;autoplay='.$auto_play.'';
						}
					}

					
				// Set a unique ID
				$output->playerID = 'AVPlayerID_'.substr(md5($tagsource),1,8).'_'.rand();

				if($auto_play != ""){
				// is audio	
					$final_autoplay = (@$tagparams[3]) ? $tagparams[3] : $auto_play;
					$final_autoplay1 = (@$tagparams[3]) ? $tagparams[3] : $auto_play;
					//$final_autoplay = TRUE;
				}
				else{
				// is video
					$final_autoplay = (@$tagparams[3]) ? $tagparams[3] : $auto_play;
					$final_autoplay1 = (@$tagparams[3]) ? $tagparams[3] : $auto_play;
				}				
				// replacements
				$findAVparams = array(
					"{SITEURL}",
					"{SOURCE}",
					"{SOURCEID}",
					"{FOLDER}",
					"{WIDTH}",
					"{HEIGHT}",		
					"{AUTOPLAY}",
					"{PLAYER_AUTOPLAY}",
					"{PLAYER_AUTOPLAY1}",
					"{TRANSPARENCY}",
					"{PLAYER_BACKGROUND}",
					"{PLAYER_ABACKGROUND}",
					"{CONTROLBAR}",
					"{PLAYER_POSTER_FRAME_REMOTE}",
					"{PLAYER_POSTER_FRAME}",
					"{PLAYER_LOOP}"
				);
				
				// special treatment
				if($plg_tag=="yahoo"){
					$tagsourceyahoo = explode('/',$tagsource);
					$tagsource = 'id='.$tagsourceyahoo[1].'&amp;vid='.$tagsourceyahoo[0];
				}
				if($plg_tag=="youku"){
					$tagsource = substr($tagsource,3);
				}				
				
				if(trim($final_autoplay) == ""){
					$final_autoplay = "0";
				}

				if(trim($final_autoplay1) == ""){
					$final_autoplay1 = "0";
				}

				if(trim($final_autoplay2) == ""){
					$final_autoplay2 = "0";
				}

				// replacement elements
					if(in_array($plg_tag, array("mp3","mp3remote", "m4a","m4aremote","wma","wmaremote"))){
						$plugin = JPluginHelper::getPlugin('content', 'jw_allvideos');
						$plugin_params = $plugin->params;
						$plugin_params = json_decode($plugin_params, true);

						if(isset($plugin_params["allowAudioDownloading"]) && $plugin_params["allowAudioDownloading"] == 1){
							$add_download_button = true;
						}

						if($awidth == 0){
							$awidth = "80%";
						}
						
						if($aheight == 0){
							$aheight = "20px";
						}

						$aheight = 50;

						$replaceAVparams = array(
							JURI::root(),
							$tagsource,
							substr(md5($tagsource),1,8),
							$afolder,
							$awidth,
							$aheight,
							$final_autoplay,
							$final_autoplay1,
							$final_autoplay2,
							$transparency,
							$background,
							$background,
							@$controlBarLocation,
							"",
							"",
							0
						);
						
						$output->playerWidth = $awidth;
						$output->playerHeight = $aheight;
						
					} else {
						$replaceAVparams = array(
							JURI::root(),
							$tagsource,
							substr(md5($tagsource),1,8),
							$vfolder,
							$final_vwidth,
							$final_vheight,
							$final_autoplay,
							$final_autoplay1,
							$final_autoplay2,
							$transparency,
							$background,
							$background,
							@$controlBarLocation,
							"",
							"",
							0
						);
					}
					
					//$plg_html = JFilterOutput::ampReplace($wrapstart.str_replace($findAVparams, $replaceAVparams, $tagReplace[$plg_tag]).$wrapend);
					$plg_html = str_replace($findAVparams, $replaceAVparams, $tagReplace[$plg_tag]);				
				
					// Do the replace
					$row = preg_replace("#{".$plg_tag."}".preg_quote($tagcontent)."{/".$plg_tag."}#s", $plg_html , $row);
					
					$site_url = substr(substr($_SERVER["HTTP_REFERER"], 0, strpos($_SERVER["HTTP_REFERER"], "administra")),0,-1);
					
					$pluginLivePath = $site_url.'/plugins/content/jw_allvideos/jw_allvideos';
					$row = str_replace("{PLUGIN_PATH}", $pluginLivePath, $row);
					
				} // end foreach
			} // end if
		} // END ALLVIDEOS LOOP
		
		$extension_array = explode(".", $tagsource);
		$extension = $extension_array[count($extension_array) - 1];
		return $row;
	} // END FUNCTION



function create_media_using_plugin($main_media, $configs, $aheight, $awidth, $vheight, $vwidth){
	$the_base_link = explode('administrator/', $_SERVER['HTTP_REFERER']);
	$the_base_link = $the_base_link[0];	
	//$the_base_link = "";
	$database = JFactory::getDBO();			
	
	if($main_media->type=='video'){
		if($main_media->source=='code')
			$media = $main_media->code;
		if($main_media->source=='url'){
			//$position_watch = strpos($main_media->url, 'www.youtube.com/watch');
			if (strpos($main_media->url, 'www.youtube.com/watch')!==false){ 
				// youtube link - begin
				$link_array = explode('=',$main_media->url);
				$link_ = $link_array[1]; 	
				$media = '{youtube}'.$link_.'{/youtube}';			
			} // youtube link - end
			elseif (strpos($main_media->url, 'www.123video.nl')!==false){ 
				// 123video.nl link - begin
				$link_array = explode('=',$main_media->url);
				$link_ = $link_array[1]; 	
				$media = '{123video}'.$link_.'{/123video}';			
			} // 123video.nl link - end
			elseif (strpos($main_media->url, 'www.aniboom.com')!==false){ 
				// aniboom.com link - begin
				$begin_tag = strpos($main_media->url, 'video');
				$remaining_link = substr($main_media->url, $begin_tag + 6, strlen($main_media->url));
				$end_tag = strpos($remaining_link, '/');
				if($end_tag===false) $end_tag = strlen($remaining_link);
				$link_ = substr($remaining_link, 0, $end_tag);	
					$media = '{aniboom}'.$link_.'{/aniboom}';	
			} // aniboom.com link - end
			elseif (strpos($main_media->url, 'www.badjojo.com')!==false){ 
				// badjojo.com [adult] link - begin
				$link_array = explode('=',$main_media->url);
				$link_ = $link_array[1]; 	
				$media = '{badjojo}'.$link_.'{/badjojo}';
				//echo $media;			
			} // badjojo.com [adult] link - end
			elseif (strpos($main_media->url, 'www.brightcove.tv')!==false){ 
				// brightcove.tv link - begin
				$begin_tag = strpos($main_media->url, 'title=');
				$remaining_link = substr($main_media->url, $begin_tag + 6, strlen($main_media->url));
				$end_tag = strpos($remaining_link, '&');
				if($end_tag===false) $end_tag = strlen($remaining_link);
				$link_ = substr($remaining_link, 0, $end_tag);	
				$media = '{brightcove}'.$link_.'{/brightcove}';	
			} // brightcove.tv link - end
			elseif (strpos($main_media->url, 'www.collegehumor.com')!==false){ 
				// collegehumor.com link - begin
				$link_array = explode(':',$main_media->url);
				$link_ = $link_array[2]; 	
				$media = '{collegehumor}'.$link_.'{/collegehumor}';
			} // collegehumor.com link - end
			elseif (strpos($main_media->url, 'current.com')!==false){ 
				// current.com link - begin
				$begin_tag = strpos($main_media->url, 'items/');
				$remaining_link = substr($main_media->url, $begin_tag + 6, strlen($main_media->url));
				$end_tag = strpos($remaining_link, '_');
				if($end_tag===false) 
					$end_tag = strlen($remaining_link);
				$link_ = substr($remaining_link, 0, $end_tag);	
				$media = '{current}'.$link_.'{/current}';	
			} // current.com link - end
			elseif (strpos($main_media->url, 'dailymotion.com')!==false){ 
				// dailymotion.com link - begin
				$begin_tag = strpos($main_media->url, 'video/');
				$remaining_link = substr($main_media->url, $begin_tag + 6, strlen($main_media->url));
				$end_tag = strpos($remaining_link, '_');
				if($end_tag===false) 
					$end_tag = strlen($remaining_link);
				$link_ = substr($remaining_link, 0, $end_tag);	
				$media = '{dailymotion}'.$link_.'{/dailymotion}';	
			} // dailymotion.com link - end
			elseif (strpos($main_media->url, 'espn')!==false){ 
				// video.espn.com link - begin
				$begin_tag = strpos($main_media->url, 'videoId=');
				$remaining_link = substr($main_media->url, $begin_tag + 8, strlen($main_media->url));
				$end_tag = strpos($remaining_link, '&');
				if($end_tag===false) 
					$end_tag = strlen($remaining_link);
				$link_ = substr($remaining_link, 0, $end_tag);	
				$media = '{espn}'.$link_.'{/espn}';	
			} // video.espn.com link - end
			elseif (strpos($main_media->url, 'eyespot.com')!==false){ 
				// eyespot.com link - begin
				$link_array = explode('r=',$main_media->url);
				$link_ = $link_array[1]; 	
				$media = '{eyespot}'.$link_.'{/eyespot}';
			} // eyespot.com link - end
			elseif (strpos($main_media->url, 'flurl.com')!==false){ 
				// flurl.com link - begin
				$begin_tag = strpos($main_media->url, 'video/');
				$remaining_link = substr($main_media->url, $begin_tag + 6, strlen($main_media->url));
				$end_tag = strpos($remaining_link, '_');
				if($end_tag===false) 
					$end_tag = strlen($remaining_link);
				$link_ = substr($remaining_link, 0, $end_tag);	
				$media = '{flurl}'.$link_.'{/flurl}';	
			} // flurl.com link - end
			elseif (strpos($main_media->url, 'funnyordie.com')!==false){ 
				// funnyordie.com link - begin
				$link_array = explode('videos/',$main_media->url);
				$link_ = $link_array[1]; 	
				$media = '{funnyordie}'.$link_.'{/funnyordie}';
			} // funnyordie.com link - end
			elseif (strpos($main_media->url, 'gametrailers.com')!==false){ 
				// gametrailers.com link - begin
				$begin_tag = strpos($main_media->url, 'player/');
				$remaining_link = substr($main_media->url, $begin_tag + 7, strlen($main_media->url));
				$end_tag = strpos($remaining_link, '.');
				if($end_tag===false) 
					$end_tag = strlen($remaining_link);
				$link_ = substr($remaining_link, 0, $end_tag);	
				$media = '{gametrailers}'.$link_.'{/gametrailers}';	
			} // gametrailers.com link - end
			elseif (strpos($main_media->url, 'godtube.com')!==false){ 
				// godtube.com link - begin
				$link_array = explode('viewkey=',$main_media->url);
				$link_ = $link_array[1]; 	
				$media = '{godtube}'.$link_.'{/godtube}';
			} // godtube.com link - end
			elseif (strpos($main_media->url, 'gofish.com')!==false){ 
				// gofish.com link - begin
				$link_array = explode('gfid=',$main_media->url);
				$link_ = $link_array[1]; 	
				$media = '{gofish}'.$link_.'{/gofish}';
			} // gofish.com link - end
			elseif (strpos($main_media->url, 'google.com')!==false){ 
				// Google Video link - begin
				$link_array = explode('docid=',$main_media->url);
				$link_ = $link_array[1]; 	
				$media = '{google}'.$link_.'{/google}';
			} // Google Video link - end
			elseif (strpos($main_media->url, 'guba.com')!==false){ 
				// guba.com link - begin
				$link_array = explode('watch/',$main_media->url);
				$link_ = $link_array[1]; 	
				$media = '{guba}'.$link_.'{/guba}';
			} // guba.com link - end
			elseif (strpos($main_media->url, 'hook.tv')!==false){ 
				// hook.tv link - begin
				$link_array = explode('key=',$main_media->url);
				$link_ = $link_array[1]; 	
				$media = '{hook}'.$link_.'{/hook}';
			} // hook.tv link - end
			elseif (strpos($main_media->url, 'jumpcut.com')!==false){ 
				// jumpcut.com link - begin
				$link_array = explode('id=',$main_media->url);
				$link_ = $link_array[1]; 	
				$media = '{jumpcut}'.$link_.'{/jumpcut}';
			} // jumpcut.com link - end
			elseif (strpos($main_media->url, 'kewego.com')!==false){ 
				// kewego.com link - begin
				$begin_tag = strpos($main_media->url, 'video/');
				$remaining_link = substr($main_media->url, $begin_tag + 6, strlen($main_media->url));
				$end_tag = strpos($remaining_link, '.');
				if($end_tag===false)
					$end_tag = strlen($remaining_link);
				$link_ = substr($remaining_link, 0, $end_tag);	
				$media = '{kewego}'.$link_.'{/kewego}';	
			} // kewego.com link - end
			elseif (strpos($main_media->url, 'krazyshow.com')!==false){ 
				// krazyshow.com [adult] link - begin
				$link_array = explode('cid=',$main_media->url);
				$link_ = $link_array[1]; 	
				$media = '{krazyshow}'.$link_.'{/krazyshow}';
			} // krazyshow.com [adult] link - end
			elseif (strpos($main_media->url, 'ku6.com')!==false){ 
				// ku6.com link - begin
				$begin_tag = strpos($main_media->url, 'show/');
				$remaining_link = substr($main_media->url, $begin_tag + 5, strlen($main_media->url));
				$end_tag = strpos($remaining_link, '.');
				if($end_tag===false) 
					$end_tag = strlen($remaining_link);
				$link_ = substr($remaining_link, 0, $end_tag);	
				$media = '{ku6}'.$link_.'{/ku6}';	
			} // ku6.com link - end
			elseif (strpos($main_media->url, 'liveleak.com')!==false){ 
				// liveleak.com link - begin
				$link_array = explode('i=',$main_media->url);
				$link_ = $link_array[1]; 	
				$media = '{liveleak}'.$link_.'{/liveleak}';
			} // liveleak.com link - end
			elseif (strpos($main_media->url, 'metacafe.com')!==false){ 
				// metacafe.com link - begin
				$begin_tag = strpos($main_media->url, 'watch/');
				$remaining_link = substr($main_media->url, $begin_tag + 6, strlen($main_media->url));
				$end_tag = strlen($remaining_link);
				$link_ = substr($remaining_link, 0, $end_tag);	
				$media = '{metacafe}'.$link_.'{/metacafe}';	
			} // metacafe.com link - end
			elseif (strpos($main_media->url, 'mofile.com')!==false){ 
				// mofile.com link - begin
				$begin_tag = strpos($main_media->url, 'com/');
				$remaining_link = substr($main_media->url, $begin_tag + 4, strlen($main_media->url));
				$end_tag = strpos($remaining_link, '/');
				if($end_tag===false) $end_tag = strlen($remaining_link);
				$link_ = substr($remaining_link, 0, $end_tag);	
				$media = '{mofile}'.$link_.'{/mofile}';	
			} // mofile.com link - end
			elseif (strpos($main_media->url, 'myspace.com')!==false){ 
				// myspace.com link - begin
				$link_array = explode('VideoID=',$main_media->url);
				$link_ = $link_array[1]; 	
				$media = '{myspace}'.$link_.'{/myspace}';
			} // myspace.com link - end
			elseif (strpos($main_media->url, 'myvideo.de')!==false){ 
				// myvideo.de link - begin
				$begin_tag = strpos($main_media->url, 'watch/');
				$remaining_link = substr($main_media->url, $begin_tag + 6, strlen($main_media->url));
				$end_tag = strpos($remaining_link, '/');
				if($end_tag===false) 
					$end_tag = strlen($remaining_link);
				$link_ = substr($remaining_link, 0, $end_tag);	
				$media = '{myvideo}'.$link_.'{/myvideo}';	
			} // myvideo.de link - end
			elseif (strpos($main_media->url, 'redtube.com')!==false){ 
				// redtube.com [adult] link - begin
				$link_array = explode('/',$main_media->url);
				$link_ = $link_array[1]; 	
				$media = '{redtube}'.$link_.'{/redtube}';
			} // redtube.com [adult] - end
			elseif (strpos($main_media->url, 'revver.com')!==false){ 
				// revver.com link - begin
				$begin_tag = strpos($main_media->url, 'video/');
				$remaining_link = substr($main_media->url, $begin_tag + 6, strlen($main_media->url));
				$end_tag = strpos($remaining_link, '/');
				if($end_tag===false) 
					$end_tag = strlen($remaining_link);
				$link_ = substr($remaining_link, 0, $end_tag);	
				$media = '{revver}'.$link_.'{/revver}';	
			} // revver.com link - end
			elseif (strpos($main_media->url, 'sapo.pt')!==false){ 
				// sapo.pt link - begin
				$link_array = explode('pt/',$main_media->url);
				$link_ = $link_array[1]; 	
				$media = '{sapo}'.$link_.'{/sapo}';
			} // sapo.pt - end
			elseif (strpos($main_media->url, 'sevenload.com')!==false){ 
				// sevenload.com link - begin
				$begin_tag = strpos($main_media->url, 'videos/');
				$remaining_link = substr($main_media->url, $begin_tag + 7, strlen($main_media->url));
				$end_tag = strpos($remaining_link, '-');
				if($end_tag===false) 
					$end_tag = strlen($remaining_link);
				$link_ = substr($remaining_link, 0, $end_tag);	
				$media = '{sevenload}'.$link_.'{/sevenload}';	
			} // sevenload.com link - end
			elseif (strpos($main_media->url, 'sohu.com')!==false){ 
				// sohu.com link - begin
				$link_array = explode('/',$main_media->url);
				$link_ = $link_array[count($link_array)-1]; 	
				$media = '{sohu}'.$link_.'{/sohu}';
			} // sohu.com - end
			elseif (strpos($main_media->url, 'southparkstudios.com')!==false){ 
				// southparkstudios.com link - begin
				$begin_tag = strpos($main_media->url, 'clips/');
				$remaining_link = substr($main_media->url, $begin_tag + 6, strlen($main_media->url));
				$end_tag = strpos($remaining_link, '/');
				if($end_tag===false) 
					$end_tag = strlen($remaining_link);
				$link_ = substr($remaining_link, 0, $end_tag);	
				$media = '{southpark}'.$link_.'{/southpark}';	
			} // southparkstudios.com link - end
			elseif (strpos($main_media->url, 'spike.com')!==false){ 
				// spike.com link - begin
				$link_array = explode('video/',$main_media->url);
				$link_ = $link_array[1]; 	
				$media = '{spike}'.$link_.'{/spike}';
			} // spike.com - end
			elseif (strpos($main_media->url, 'stickam.com')!==false){ // stickam.com link - begin
				$link_array = explode('mId=',$main_media->url);
				$link_ = $link_array[1]; 	
				$media = '{stickam}'.$link_.'{/stickam}';
			} // stickam.com - end
			elseif (strpos($main_media->url, 'stupidvideos.com')!==false){ 
				// stupidvideos.com link - begin
				$link_array = explode('#',$main_media->url);
				$link_ = $link_array[1]; 	
				$media = '{stupidvideos}'.$link_.'{/stupidvideos}';
			} // stupidvideos.com - end
			elseif (strpos($main_media->url, 'tudou.com')!==false){ 
				// tudou.com link - begin
				$begin_tag = strpos($main_media->url, 'view/');
				$remaining_link = substr($main_media->url, $begin_tag + 5, strlen($main_media->url));
				$end_tag = strpos($remaining_link, '/');
				if($end_tag===false) 
					$end_tag = strlen($remaining_link);
				$link_ = substr($remaining_link, 0, $end_tag);	
				$media = '{tudou}'.$link_.'{/tudou}';	
			} // tudou.com link - end
			elseif (strpos($main_media->url, 'ustream.tv')!==false){ 
				// ustream.tv link - begin
				$link_array = explode('recorded/',$main_media->url);
				$link_ = $link_array[1]; 	
				$media = '{ustream}'.$link_.'{/ustream}';
			} // ustream.tv - end
			elseif (strpos($main_media->url, 'veoh.com')!==false){ 
				// veoh.com link - begin
				$link_array = explode('videos/',$main_media->url);
				$link_ = $link_array[1]; 	
				$media = '{veoh}'.$link_.'{/veoh}';
			} // veoh.com - end
			elseif (strpos($main_media->url, 'videotube.de')!==false){
				 // videotube.de link - begin
				$link_array = explode('watch/',$main_media->url);
				$link_ = $link_array[1]; 	
				$media = '{videotube}'.$link_.'{/videotube}';
			} // videotube.de - end
			elseif (strpos($main_media->url, 'vidiac.com')!==false){
				 // vidiac.com link - begin
				$begin_tag = strpos($main_media->url, 'video/');
				$remaining_link = substr($main_media->url, $begin_tag + 6, strlen($main_media->url));
				$end_tag = strpos($remaining_link, '.');
				if($end_tag===false) 
					$end_tag = strlen($remaining_link);
				$link_ = substr($remaining_link, 0, $end_tag);	
				$media = '{vidiac}'.$link_.'{/vidiac}';	
			} // vidiac.com link - end
			elseif (strpos($main_media->url, 'vimeo.com')!==false){ 
				// vimeo.com link - begin
				$link_array = explode('.com/',$main_media->url);
				$link_ = $link_array[1]; 	
				$media = '{vimeo}'.$link_.'{/vimeo}';
			} // vimeo.com - end
			elseif (strpos($main_media->url, 'yahoo.com')!==false){ 
				// video.yahoo.com link - begin
				$link_array = explode('watch/',$main_media->url);
				$link_ = $link_array[1]; 	
				$media = '{yahoo}'.$link_.'{/yahoo}';			
			} // video.yahoo.com - end
			elseif (strpos($main_media->url, 'youare.tv')!==false){ 
				// youare.tv link - begin
				$link_array = explode('id=',$main_media->url);
				$link_ = $link_array[1]; 	
				$media = '{youare}'.$link_.'{/youare}';			
			} // youare.tv - end
			elseif (strpos($main_media->url, 'youku.com')!==false){ 
				// youku.com link - begin
				$begin_tag = strpos($main_media->url, 'v_show/');
				$remaining_link = substr($main_media->url, $begin_tag + 7, strlen($main_media->url));
				$end_tag = strpos($remaining_link, '.');
				if($end_tag===false) 
					$end_tag = strlen($remaining_link);
				$link_ = substr($remaining_link, 0, $end_tag);	
				$media = '{youku}'.$link_.'{/youku}';	
			} // youku.com link - end
			elseif (strpos($main_media->url, 'youmaker.com')!==false){ 
				// youmaker.com  link - begin
				$link_array = explode('id=',$main_media->url);
				$link_ = $link_array[1]; 	
				$media = '{youmaker}'.$link_.'{/youmaker}';			
			} // youmaker.com  - end
			else{
				//----------- not special link - begin
				$extension_array=explode('.',$main_media->url);
				$extension = $extension_array[count($extension_array)-1];					
				if(strtolower($extension)=='flv' || strtolower($extension)=='swf' || strtolower($extension)=='mov' || strtolower($extension)=='wmv' || strtolower($extension)=='mp4' || strtolower($extension)=='divx'){
					$tag_begin = '{'.strtolower($extension).'remote}';
					$tag_end = '{/'.strtolower($extension).'remote}';
				}	
				if(!isset($tag_begin)) {
					$tag_begin=NULL;
				}
				if(!isset($tag_end)) {
					$tag_end=NULL;
				}	
				$media = $tag_begin.$main_media->url.$tag_end;										
				//----------- not special link - begin										
			}
			$media = jwAllVideos( $media, $aheight, $awidth, $vheight, $vwidth);				
		}		
		//$media = '<a target="_blank" href="'.$main_media->url.'">'.$main_media->name.'</a>';	
		if($main_media->source=='local'){
			$extension_array=explode('.',$main_media->local);
			$extension = $extension_array[count($extension_array)-1];
			//echo $extension;
			if(strtolower($extension)=='flv' || strtolower($extension)=='swf' || strtolower($extension)=='mov' || strtolower($extension)=='wmv' || strtolower($extension)=='mp4' || strtolower($extension)=='divx'){
				$tag_begin = '{'.strtolower($extension).'remote}';
				$tag_end = '{/'.strtolower($extension).'remote}';
			}		
			if(!isset($tag_begin)){
				$tag_begin=NULL;
			}			
			if(!isset($tag_end)){
				$tag_end=NULL;
			}			
			$media = $tag_begin.$the_base_link.$configs->videoin.'/'.$main_media->local.$tag_end;
			$media = jwAllVideos( $media, $aheight, $awidth, $vheight, $vwidth);
		}	
	}	
	
	if($main_media->type=='audio'){
		if($main_media->source=='code')
			$media = $main_media->code;
		if($main_media->source=='url'){
			$extension_array=explode('.',$main_media->url);
			$extension = $extension_array[count($extension_array)-1];
			if(strtolower($extension)=='mp3' || strtolower($extension)=='wma' || strtolower($extension)=='m4a'){
				$tag_begin = '{'.strtolower($extension).'remote}';
				$tag_end = '{/'.strtolower($extension).'remote}';
			}	
			$media = $tag_begin.$main_media->url.$tag_end; 
			$media = jwAllVideos( $media, $aheight, $awidth, $vheight, $vwidth);
		
		}
		if($main_media->source=='local'){
			$extension_array=explode('.',$main_media->local);
			$extension = $extension_array[count($extension_array)-1];
			
			if(strtolower($extension)=='mp3' || strtolower($extension)=='m4a' || strtolower($extension)=='wma'){
				$tag_begin = '{'.strtolower($extension).'remote}';
				$tag_end = '{/'.strtolower($extension).'remote}';
			}
			
			$media = $tag_begin.$the_base_link.$configs->audioin.'/'.$main_media->local.$tag_end;
			$media = jwAllVideos( $media, $aheight, $awidth, $vheight, $vwidth);
		}	
	}		
	
	if($main_media->type=='url')
		{
			$media = '<a target="_blank" href="'.$main_media->url.'">'.$main_media->name.'</a>';	
		}			
	if($main_media->type=='docs')
		{
			if($main_media->source=='url')
				$media = '<a target="_blank" href="'.$main_media->url.'">'.$main_media->name.'</a>';
		}
	if($main_media->type=='image'){
		$img_size = @getimagesize(JPATH_SITE.DIRECTORY_SEPARATOR.$configs->imagesin.DIRECTORY_SEPARATOR.'media'.DIRECTORY_SEPARATOR.'thumbs'.$main_media->local);
		if(isset($img_size[0]) && isset($img_size[1])){
			$img_width = $img_size[0];
			$img_height = $img_size[1];
			if($img_width>0 && $img_height>0){ 
				if($main_media->width > 0){
					$thumb_width = $main_media->width;
					$thumb_height = $img_height / ($img_width/$main_media->width);
				}
				elseif($main_media->height > 0)	{
					$thumb_height = $main_media->height;
					$thumb_width = $img_width / ($img_height/$main_media->height);		
				}
				else{
					$thumb_height = 200;
					$thumb_width = $img_width / ($img_height/200);									
				}
			}
			
			if(isset($thumb_width) && isset($thumb_height)) {
				$media = '<img width="'.$thumb_width.'" height="'.$thumb_height.'" src="';
			} else {
				$media = '<img src="';
			}
			$media .= JURI::root()."/".$configs->imagesin.'/media/thumbs'.$main_media->local.'" />';	
		}
	}
	if($main_media->type =='text'){
		$media = $main_media->code;
	}
	if($main_media->type=='Article'){
		$media = getArticleById($main_media->code);
	}
	if($main_media->type == "file"){			
		$media = '<a target="_blank" href="'.JURI::ROOT().$configs->filesin.'/'.$main_media->local.'">'.$main_media->name.'</a><br/><br/>'.$main_media->instructions;
	}
		
	if(!isset($media)) { 
		return NULL;
	}
	return $media;
}

function getArticleById($id) {
	$db = JFactory::getDBO();
	$sql = "SELECT jc.introtext, jc.fulltext FROM #__content jc WHERE id = ".$id;
	$db->setQuery($sql);
	$row = $db->loadAssoc();
	$fullArticle = $row['introtext'].$row['fulltext'];
	if(!strlen(trim($fullArticle))) $fullArticle = "Article is empty ";
	return $fullArticle; 
}

function getConfig(){
	$db = JFactory::getDBO();
	$sql = "SELECT * FROM #__guru_config LIMIT 1";
	$db->setQuery($sql);
	if (!$db->execute() ){
		//$this->setError($db->getErrorMsg());
		return false;
	}	
	$result = $db->loadObject();	
	return $result;
}
function getMediaFromId($id_media){
	$db = JFactory::getDBO();
	$sql = "SELECT * FROM #__guru_media WHERE id =".intval($id_media);
	$db->setQuery($sql);
	$db->execute();
	$result = $db->loadObjectList();
	return $result;
}
?>
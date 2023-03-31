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
require_once JPATH_SITE . '/components/com_users/helpers/route.php';
JHtml::_('behavior.keepalive');

$lang = JFactory::getLanguage();
$extension = 'mod_login';
$base_dir = JPATH_SITE;
$language_tag = 'en-GB';
$lang->load($extension, $base_dir, '', true);

$Itemid = JFactory::getApplication()->input->get("Itemid", "0");
$document = JFactory::getDocument();
$document->addStyleSheet("components/com_guru/css/guru_style.css");
$document->setMetaData( 'viewport', 'width=device-width, initial-scale=1.0' );

require_once(JPATH_BASE . "/components/com_guru/helpers/Mobile_Detect.php");

$detect = new Mobile_Detect;
$deviceType = ($detect->isMobile() ? ($detect->isTablet() ? 'tablet' : 'phone') : 'computer');  
$document->setTitle(JText::_("GURU_ALREADY_MEMBER"));
$returnpageoR = JFactory::getApplication()->input->get("returnpage", "");

if($returnpageoR == 'authorprofile' || $returnpageoR == "authormymedia" || $returnpageoR == "authormymediacategories" || $returnpageoR == "mystudents" || $returnpageoR == "authormycourses"){
	$returnpageo = 'authorprofile';
}
else{
	$returnpageo = $returnpageoR;
}

?>

<script type="text/javascript" language="javascript">
  clicked_icon = "0";

  document.body.className = document.body.className.replace("modal", "");

  function showPassword(){
    if(clicked_icon == "0"){
      document.getElementById('passwd').type = 'text';
      document.getElementById('show-password').className = 'fa fa-eye-slash';
      document.getElementById('sr-only').innerHTML = "<?php echo JText::_('GURU_HIDE'); ?>";
      clicked_icon = "1";
    }
    else if(clicked_icon == "1"){
      document.getElementById('passwd').type = 'password';
      document.getElementById('show-password').className = 'fa fa-eye';
      document.getElementById('sr-only').innerHTML = "<?php echo JText::_('GURU_SHOW'); ?>";
      clicked_icon = "0";
    }
  }
  
</script>

<!-- Login Page -->
<div class="gru-login gru-page">
  <div class="gru-heading">
    <h2 class="gru-page-title"><?php echo JText::_("GURU_ALREADY_MEMBER");?></h2>
  </div>

  <!-- Grid -->
  <ul class="uk-grid uk-grid-width-medium-1-2 uk-grid-fix">
    <!-- Login box -->
    <li>
      <form name="loginForm" method="post" class="uk-form" action="index.php">
        <div class="gru-login-box uk-panel uk-panel-box uk-panel-header">
          <h3 class="uk-panel-title"><?php echo JText::_("GURU_HAVE_ACCOUNT"); ?></h3>

          <div class="uk-form-row">
            <label for="username" class="uk-form-label">
              <?php echo JText::_("GURU_PROFILE_USERNAME");?>:
              <span class="uk-text-danger">*</span>
            </label>
            <div class="uk-form-controls">
              <input type="text" id="username" name="username" placeholder="<?php echo JText::_("GURU_USERNAME"); ?>" />
            </div>
          </div>

          <div class="uk-form-row">
            <label for="passwd" class="uk-form-label">
              <?php echo JText::_("GURU_PROFILE_PSW");?>:
              <span class="uk-text-danger">*</span>
            </label>
            <div class="uk-form-controls input-group">
              <input type="password" class="form-control" id="passwd" name="passwd" placeholder="<?php echo JText::_("GURU_PASSWORD"); ?>" />
              <span class="input-group-addon">
                <span id="show-password" onclick="javascript:showPassword(); return false;" class="fa fa-eye" aria-hidden="true"></span>
                <span id="sr-only" class="sr-only"><?php echo JText::_("GURU_SHOW"); ?></span>
              </span>
            </div>
          </div>

          <div class="uk-form-row">
            <label for="remember">
              <input type="checkbox" name="remember" value="1" /> &nbsp; <?php echo JText::_("GURU_PROFILE_REMEMBER_ME");?>
            </label>
          </div>
          
          <div class="uk-form-row">
            <ul class="uk-list unstyled list-unstyled">
                <li>
                    <a href="<?php echo JRoute::_('index.php?option=com_users&view=remind&Itemid=' . UsersHelperRoute::getRemindRoute()); ?>">
                    <?php echo JText::_('MOD_LOGIN_FORGOT_YOUR_USERNAME'); ?></a>
                </li>
                <li>
                    <a href="<?php echo JRoute::_('index.php?option=com_users&view=reset&Itemid=' . UsersHelperRoute::getResetRoute()); ?>">
                    <?php echo JText::_('MOD_LOGIN_FORGOT_YOUR_PASSWORD'); ?></a>
                </li>
            </ul>
          </div>

          <div class="uk-form-row">
            <input type="submit" onclick="document.loginForm.submit();" class="uk-button uk-button-primary" name="submit_button" value="<?php echo JText::_("GURU_LOGIN_AND_CONTINUE"); ?>" />
          </div>
        </div>
        <input type="hidden" name="option" value="com_guru" />
        <input type="hidden" name="controller" value="guruLogin" />
        <input type="hidden" name="Itemid" value="<?php echo $Itemid;?>" />
        <input type="hidden" name="task" value="log_in_user" />
        <input type="hidden" name="returnpage" value="<?php echo JFactory::getApplication()->input->get("returnpage", ""); ?>" />
        <input type="hidden" name="lesson_id" value="<?php echo JFactory::getApplication()->input->get("lesson_id", "0"); ?>" /> 
        <input type="hidden" name="cid" value="<?php echo JFactory::getApplication()->input->get("cid", "0"); ?>" />
      </form>
    </li>
    <!-- Register box -->
    
    <?php
    	$params = JComponentHelper::getParams('com_users');
		$allowUserRegistration = $params->get('allowUserRegistration');

		if($allowUserRegistration){
	?>
            <li>
              <div class="uk-panel uk-panel-box uk-panel-box-primary uk-panel-header">
                <h3 class="uk-panel-title"><?php echo JText::_("GURU_CREATE_NEW_ACCOUNT"); ?></h3>
        
                <?php if($returnpageo != "authorprofile"){ ?>
                  <form name="register" method="post" class="uk-form">
                    <input type="hidden" name="option" value="com_guru" />
                    <input type="hidden" name="controller" value="guruLogin" />
                    <input type="hidden" name="Itemid" value="<?php echo JFactory::getApplication()->input->get("Itemid", "0"); ?>" />
                    <input type="hidden" name="task" value="register" />
                    <input type="hidden" name="returnpage" value="<?php echo JFactory::getApplication()->input->get("returnpage", ""); ?>" />
                    <input type="hidden" name="lesson_id" value="<?php echo JFactory::getApplication()->input->get("lesson_id", "0"); ?>" /> 
                    <input type="hidden" name="cid" value="<?php echo JFactory::getApplication()->input->get("cid", "0"); ?>" />  
                    <!--start case of student rgistration -->
                    <p>
                      <?php echo JText::_("GURU_REGISTRATION_EASY_STUDENT"); ?>
                    </p>
                    <a href="http://training.inspectiondepot.co/index.php/registration" class="btn btn-success" role="button">Register</a>
                   <!-- <input type="submit" class="uk-button uk-button-primary" value="<?php echo JText::_("GURU_REGISTER_AS_STUDENT");?>" /> -->
                   <!-- end of student registration -->
                  </form>
                <?php }
                elseif($returnpageo == "authorprofile"){?>
                  <form name="register" method="post" class="uk-form">
                    <input type="hidden" name="option" value="com_guru" />
                    <input type="hidden" name="controller" value="guruAuthor" />
                    <input type="hidden" name="Itemid" value="<?php echo JFactory::getApplication()->input->get("Itemid", "0"); ?>" />
                    <input type="hidden" name="task" value="authorregister" />
                    <input type="hidden" name="returnpage" value="<?php echo JFactory::getApplication()->input->get("returnpage", ""); ?>" />
                    <input type="hidden" name="lesson_id" value="<?php echo JFactory::getApplication()->input->get("lesson_id", "0"); ?>" />
                    <input type="hidden" name="cid" value="<?php echo JFactory::getApplication()->input->get("cid", "0"); ?>" />
                    <!-- start case of teacher registration -->
                    <p><?php echo JText::_("GURU_REGISTRATION_EASY_TEACHER"); ?></p>
                    <input type="submit" class="uk-button uk-button-primary" value="<?php echo JText::_("GURU_REGISTER_AS_TEACHER");?>" />
                    <!-- end of teacher registration -->
                  </form>
                <?php } ?>
              </div>
            </li>
    <?php
    	}
	?>
  </ul>
</div>

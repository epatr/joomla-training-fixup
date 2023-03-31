<?php
/**
* @author Douglas Machado <douglas@focalizar.com.br>
* @copyright 2009 - Douglas Machado <douglas@focalizar.com.br>
**/

defined( '_VALID_MOS' ) or defined('_JEXEC') or die('Direct Access to this location is not allowed.');
if(!defined('DS')){
	define('DS', DIRECTORY_SEPARATOR);
}
$config		= JFactory::getConfig();
/**
 * @var int	Will set error report to maximum if global settings is set to maximum, otherwise set error reporting to none this will avoid problems with Joom!Fish and some SEF extensions
 */
$error_reporting_level	= $config->get('config.error_reporting');
if($error_reporting_level != 6143){
	$error_reporting_level = 0;
}
error_reporting($error_reporting_level);

// Import library dependencies
jimport('joomla.event.plugin');
jimport('joomla.plugin.plugin');


class plgContentCaptcha extends JPlugin
{

	function __construct(&$subject)
    {
            // load plugin parameters
            $this->_plugin = JPluginHelper::getPlugin( 'content', 'captcha' );
            $this->_params = new JRegistry( $this->_plugin->params );
            $lang = JFactory::getLanguage(); 
			$lang->load('captcha');

			parent::__construct( $subject );
    }

		
	function onAfterDisplayForm() {
		//echo '<pre>'; print_r(func_get_arg(1)); echo '</pre>'; exit;
		if(func_num_args() > 0){
			$args	= func_get_arg(0);
			if(is_object($args)){
				$this->_params->merge($args);
			}
		}
		$html	='';
		$lang = JFactory::getLanguage(); 
		$lang->load('captcha','administrator');
		if($this->_params->get('captcha_systems') == 'secureimage'){
			$html = $this->displaySecureImageForm();
		}else if($this->_params->get('captcha_systems') == 'securImage2'){
			$html = $this->displaySecurImage2Form();
		}else if($this->_params->get('captcha_systems') == 'recaptcha'){
			$html = $this->displayRecaptchaForm();
		}else if($this->_params->get('captcha_systems') == 'mathguard'){
			$html = $this->displayMathGuardForm();
		}else if($this->_params->get('captcha_systems') == 'mollom'){
			$html = $this->displayMollom();
		}
		//echo '<pre>'; print_r($this->_params); echo '</pre>';
		if(func_num_args() > 1){
			if(func_get_arg(1) == 'return'){
				return $html;
			}
		} 
		echo $html;
	}
	
	function onValidateForm() {
		if(func_num_args() > 0){
			$args	= func_get_arg(0);
			if(is_object($args)){
				$this->_params->merge($args);
			}
		}
		//echo 'Args: <pre>'; print_r(func_get_args()); echo '</pre>'; exit;
		$html	= '';
		$lang = JFactory::getLanguage(); 
		$lang->load('captcha','administrator');
		if($this->_params->get('captcha_systems') == 'secureimage'){
			$html = $this->validateSecureImage();
		}else if($this->_params->get('captcha_systems') == 'securImage2'){
			$html = $this->validateSecurImage2();
		}else if($this->_params->get('captcha_systems') == 'recaptcha'){
			$html = $this->validateRecaptcha();
		}else if($this->_params->get('captcha_systems') == 'mathguard'){
			$html = $this->validateMathGuard();
		}else if($this->_params->get('captcha_systems') == 'mollom'){
			$html = $this->validateMollom();
		}
		return $html;
	}
	// gampt add
	function onValidateCaptcha($captcha_code)
	{
		$captcha_systems = $this->_params->get('captcha_systems');
		$check = false;
		if ($captcha_systems == 'securImage2')
		    $captcha_systems = 'securimage3';
		switch ($captcha_systems)
		{
			case 'secureimage':
			case 'securImage2':
			case 'securimage3':
			
				require_once(JPATH_PLUGINS.DS.'content'.DS.'captcha'.DS.'captcha_systems'.DS.$captcha_systems.DS.'securimage.php');
				
				$securimage = new Securimage();
				
		// the code was incorrect
				$check = $securimage->check($captcha_code) ;
				break;
			case 'recaptcha':
				$challenge =JRequest::getString("recaptcha_challenge_field");
				$response =JRequest::getString("recaptcha_response_field");
				if(!defined('RECAPTCHA_API_SERVER'))
				{
					require_once(JPATH_PLUGINS.DS.'content'.DS.'captcha'.DS.'captcha_systems'.DS.'recaptchalib.php'); /**    reCAPTCHA Library   **/
				}
				$resp = recaptcha_check_answer ($this->_params->get('captcha_systems-recaptcha-PriKey',''),$_SERVER["REMOTE_ADDR"],$challenge,$response);		
				if(!$resp->is_valid) {
					$check = false;
				}
				else $check = true;
				break;
			case 'mathguard':
				require_once(JPATH_PLUGINS.DS.'content'.DS.'captcha'.DS.'captcha_systems'.DS.'mathguard'.DS.'mathGuard.class.php');
		// the code was incorrect
			if(mathGuardImproved::checkResult($captcha_code,$this->_params->get('suffix')) == false) 
			{
				$check = false;
			}
			else $check= true;
			break;
			default:$check = false;
		}
		return $check;
	}
	
	function validateSecureImage(){
		require_once(JPATH_PLUGINS.DS.'content'.DS.'captcha'.DS.'captcha_systems'.DS.'secureimage'.DS.'securimage.php');
		$securimage = new Securimage();
		// the code was incorrect
		$captcha_code = (JRequest::getVar('captcha_code','','POST'))?JRequest::getVar('captcha_code','','POST'):JRequest::getVar('captcha','','POST');
		if($securimage->check($captcha_code) == false) {
			return false;
		}
		return true;
	}
	
	function validateSecurImage2(){
		require_once(JPATH_PLUGINS.DS.'content'.DS.'captcha'.DS.'captcha_systems'.DS.'securimage3'.DS.'securimage.php');
		$securimage = new Securimage();
		// the code was incorrect
		$captcha_code = (JRequest::getVar('captcha_code','','POST'))?JRequest::getVar('captcha_code','','POST'):JRequest::getVar('captcha','','POST');
		if($securimage->check($captcha_code) == false) {
			return false;
		}
		return true;
	}
	
	function validateMathGuard(){
		require_once(JPATH_PLUGINS.DS.'content'.DS.'captcha'.DS.'captcha_systems'.DS.'mathguard'.DS.'mathGuard.class.php');
		// the code was incorrect
		$captcha_code = (JRequest::getVar('captcha_code','','POST'))?JRequest::getVar('captcha_code','','POST'):JRequest::getVar('captcha','','POST');
		if(mathGuardImproved::checkResult($captcha_code,$this->_params->get('suffix')) == false) {
			return false;
		}
		return true;
	}

	function validateMollom(){
		$return = 'index.php?option=com_contact_enhanced&view=contact&id='.JRequest::getInt('id');
		return CaptchaMoovur::checkCaptcha(JRoute::_($return));
	}
	
	function validateRecaptcha(){
		if(!defined('RECAPTCHA_API_SERVER')){
			require_once(JPATH_PLUGINS.DS.'content'.DS.'captcha'.DS.'captcha_systems'.DS.'recaptchalib.php'); /**    reCAPTCHA Library   **/
		}
		$resp = recaptcha_check_answer ($this->_params->get('captcha_systems-recaptcha-PriKey',''),
										$_SERVER["REMOTE_ADDR"],
										JRequest::getString("recaptcha_challenge_field"),
										JRequest::getString("recaptcha_response_field") );		
		if(!$resp->is_valid) {
			return false;
		}
		return true;
	}
	
	function displaySecureImageForm(){
		$urlPath = JURI::root().'/plugins/content/captcha/captcha_systems/secureimage/';
		$html = '';
		$html .= '<img id="captcha" name="captcha" src="'.$urlPath.'securimage_show.php?sid='.md5(uniqid(time())).'" alt="'.JText::_('CAPTCHA Image').'" />';
		$linkAttributes = array();
		$linkAttributes['onclick']	= "document.getElementById('captcha').src = '".$urlPath."securimage_show.php?' + Math.random(); return false";
		$linkAttributes['id']		= 'reloadImage';
		$image	= JHTML::_('image',$urlPath.'images/refresh.png',JText::_('Reload Image') );
		$html .= JHTML::_('link', '#',$image,$linkAttributes ); 
		//$html .= '<a href="#'.JText::_('Reload Image').'" id="reloadImage" onclick="document.'."getElementById('captcha').src = '".$urlPath."securimage_show.php?' + Math.random(); return false'>".JText::_('Reload Image').'</a>'; 
		$html .=  "<input type=\"text\" name=\"captcha_code\" id=\"captcha_code\" class=\"inputbox captchacode required\" size=\"22\" onblur=\"if(this.value=='')this.value='Type the code shown';\" onfocus=\"if(this.value=='Type the code shown')this.value='';\" />";
		return $html;
	}
	
	function displaySecurImage2Form(){
		$urlPath = JURI::root().'plugins/content/captcha/captcha_systems/securimage3/';
		//$html = '';

		$html = '<div class="captcha-container">';
			$html .= '<img id="captcha" name="captcha" src="'.$urlPath.'securimage_show.php?sid='.md5(uniqid(time())).'" alt="'.JText::_('CAPTCHA Image').'" />';

			$linkAttributes = array();
			$linkAttributes['onclick']	= "document.getElementById('captcha').src = '".$urlPath."securimage_show.php?' + Math.random(); return false";
			$linkAttributes['id']		= 'reloadImage';
			$image	= JHTML::_('image',$urlPath.'images/refresh.png',JText::_('Reload Image') );
			$html .= JHTML::_('link', '#'.JText::_('Reload Image'),$image,$linkAttributes ); 
			//$html .= '<a href="#'.JText::_('Reload Image').'" id="reloadImage" onclick="document.'."getElementById('captcha').src = '".$urlPath."securimage_show.php?' + Math.random(); return false'>".JText::_('Reload Image').'</a>'; 
			$html .=  "<input type=\"text\" name=\"captcha_code\" id=\"captcha_code\" class=\"inputbox captchacode required\" size=\"22\" onblur=\"if(this.value=='')this.value='Type the code shown';\" onfocus=\"if(this.value=='Type the code shown')this.value='';\" value=\"Type the code shown\"/>";
		$html .= '</div>';
		return $html;
	}

	function displayMathGuardForm(){
		require_once(JPATH_PLUGINS.DS.'content'.DS.'captcha'.DS.'captcha_systems'.DS.'mathguard'.DS.'mathGuard.class.php');
		
		$attributes		= array();
		$attributes['id']	= 'mathguard_answer'.$this->_params->get('suffix');
		
		$html = '';
		$html .= '<label for="mathguard_answer'.$this->_params->get('suffix').'" class="requiredField" id="label-mathguard_answer" >'.JText::_("Security question").'</label>';
		$html .= mathGuardImproved::returnQuestion($attributes,$this->_params->get('suffix'));
							

		return $html;
	}
	
	function displayMollom(){
		
		return CaptchaMoovur::getCaptcha('com_contact_enhanced.submit');
	}
	//This function has been disabled for now
	function displayRecaptchaForm_NON_AJAX(){
		$document= JFactory::getDocument();
		$config		= JFactory::getConfig();
		if($config->getValue('config.force_ssl') > 1 ){
			$use_ssl = true;
		}else{
			$use_ssl = false;
		}
		$script	= "<script type=\"text/javascript\">var RecaptchaOptions = {theme : '" . $this->_params->get('recaptchaTheme','red') . "'};</script>";
		
		$document->addCustomTag($script);

		require_once(JPATH_PLUGINS.DS.'content'.DS.'captcha'.DS.'captcha_systems'.DS.'recaptchalib.php'); /**    reCAPTCHA Library   **/
		/**   Generating CORE reCAPTCHA form   **/
		return recaptcha_get_html($this->_params->get('recaptchaPubKey',''),null,$use_ssl); 
	}
	
	function displayRecaptchaForm(){
		$document	= JFactory::getDocument();
		if(!defined('RECAPTCHA_API_SERVER')){
			require_once(JPATH_PLUGINS.DS.'content'.DS.'captcha'.DS.'captcha_systems'.DS.'recaptchalib.php'); /**    reCAPTCHA Library   **/
		}
		JHtml::_('script', RECAPTCHA_API_SERVER . '/js/recaptcha_ajax.js');
		
		$script		= 	"
						window.addEvent('domready', function() {
		
							Recaptcha.create('".$this->_params->get('captcha_systems-recaptcha-PubKey','')."',
								'recaptcha_div', 
								{	lang:'" . $this->_params->get('captcha_systems-recaptcha-Lang','en') . "',
								  	 theme:'" . $this->_params->get('captcha_systems-recaptcha-Theme','red') . "'
								});
						});
						";
		$document->addScriptDeclaration($script);
		
		
		/**   Generating CORE reCAPTCHA form   **/
		return '<div id="recaptcha_div" name="recaptcha_div"></div>'; 
	}
}
?>
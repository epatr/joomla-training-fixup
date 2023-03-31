<?php
/**
 * @package     Joomla.administrator
 * @subpackage  Component.hwdmediashare
 *
 * @copyright   Copyright (C) 2013 Highwood Design Limited. All rights reserved.
 * @license     GNU General Public License http://www.gnu.org/copyleft/gpl.html
 * @author      Dave Horsfall
 */

defined('_JEXEC') or die;

abstract class JHtmlHwdPopup
{
	/**
	 * Method to load assets for the popup window to display an alert.
	 *
	 * @access  public
         * @static 
	 * @return  void
	 */
	public static function alert() 
	{
		$document = JFactory::getDocument();
                
                // Load jQuery.
                JHtml::_('jquery.framework');
                        
                // Load MagnificPopup CSS file.
                $document->addStyleSheet(JURI::root() . "media/com_hwdmediashare/assets/css/magnific-popup.css");
                
                // Load MagnificPopup JS file.
                $document->addScript(JURI::root() . "media/com_hwdmediashare/assets/javascript/jquery.magnific-popup.js");
                
                $script = "
jQuery(document).ready(function(){
  jQuery('.media-popup-alert').magnificPopup({
    type: 'iframe',
    closeOnBgClick: false,
    mainClass: 'mfp-alert',
    closeOnContentClick: true,
    closeMarkup: '<span title=\"%title%\" class=\"mfp-close\">Dismiss</span>',
    removalDelay: 0
  });
});
";
                
                // Add the script to the document head.
                $document->addScriptDeclaration($script);
	}

	/**
	 * Method to load assets for the popup window to display an iframe.
	 *
	 * @access  public
         * @static 
	 * @return  void
	 */
	public static function general()
	{
		$document = JFactory::getDocument();
                
                // Load jQuery.
                JHtml::_('jquery.framework');
                        
                // Load MagnificPopup CSS file.
                $document->addStyleSheet(JURI::root() . "media/com_hwdmediashare/assets/css/magnific-popup.css");
                
                // Load MagnificPopup JS file (this file also includes the javascript to initialize the popup).
                $document->addScript(JURI::root() . "media/com_hwdmediashare/assets/javascript/jquery.magnific-popup.js");
	}
        
	/**
	 * Method to load assets for the popup window to display an iframe.
	 *
	 * @access  public
         * @static 
	 * @return  void
	 */
	public static function iframe($type, $class = null)
	{
		$document = JFactory::getDocument();
                
                // Load jQuery.
                JHtml::_('jquery.framework');
                        
                // Load MagnificPopup CSS file.
                $document->addStyleSheet(JURI::root() . "media/com_hwdmediashare/assets/css/magnific-popup.css");
                
                // Load MagnificPopup JS file.
                $document->addScript(JURI::root() . "media/com_hwdmediashare/assets/javascript/jquery.magnific-popup.js");
                
                $element = 'media-popup-iframe-' . $type;
                $element.= $class ? '-' . $class : '';

                $mainClass = 'mfp-iframe-' . $type;
                $mainClass.= $class ? '-' . $class : '';
                
// http://codepen.io/dimsemenov/pen/zjtbr
// 
// This class is used only by the buttons which don't need the gallery arrows,
// so the gallery feature is not enabled. All media popups use 'media-popup-general'
//                
                $script = "
jQuery(document).ready(function(){
  jQuery('." . $element . "').magnificPopup({
    type: 'iframe',
    mainClass: '" . $mainClass . "',
    removalDelay: 0,
    iframe:{
      markup: '<div class=\"mfp-iframe-scaler\">'+
                '<div class=\"mfp-close\"></div>'+
                '<iframe class=\"mfp-iframe\" frameborder=\"0\" allowfullscreen></iframe>'+
                '<div class=\"mfp-title\"></div>'+
              '</div>',
      patterns:{
        youtube:{
          id: null,
          src: '%id%'
        }   
      }
    }     
  });
});
";
                
                // Add the script to the document head.
                $document->addScriptDeclaration($script);
	}

	/**
	 * Method to load assets for the popup window to display an image.
	 *
	 * @access  public
         * @static 
	 * @return  void
	 */
	public static function image($type, $class = null)
	{
		$document = JFactory::getDocument();
                
                // Load jQuery.
                JHtml::_('jquery.framework');
                        
                // Load MagnificPopup CSS file.
                $document->addStyleSheet(JURI::root() . "media/com_hwdmediashare/assets/css/magnific-popup.css");
                
                // Load MagnificPopup JS file.
                $document->addScript(JURI::root() . "media/com_hwdmediashare/assets/javascript/jquery.magnific-popup.js");
                
                $element = 'media-popup-' . $type;
                $element.= $class ? '-' . $class : '';

                $mainClass = 'mfp-' . $type;
                $mainClass.= $class ? '-' . $class : '';

                $script = "
jQuery(document).ready(function(){
  jQuery('." . $element . "').magnificPopup({
    type: 'image',
    mainClass: '" . $mainClass . "',
    removalDelay: 0
  });
});
";
                
                // Add the script to the document head.
                $document->addScriptDeclaration($script);
	}      

	/**
	 * Method to load assets for the popup window to display an image.
	 *
	 * @access  public
         * @static 
	 * @return  void
	 */
	public static function ajax($type, $class = null)
	{
		$document = JFactory::getDocument();
                
                // Load jQuery.
                JHtml::_('jquery.framework');
                        
                // Load MagnificPopup CSS file.
                $document->addStyleSheet(JURI::root() . "media/com_hwdmediashare/assets/css/magnific-popup.css");
                
                // Load MagnificPopup JS file.
                $document->addScript(JURI::root() . "media/com_hwdmediashare/assets/javascript/jquery.magnific-popup.js");
                
                $element = 'media-popup-ajax-' . $type;
                $element.= $class ? '-' . $class : '';

                $mainClass = 'mfp-ajax-' . $type;
                $mainClass.= $class ? '-' . $class : '';

                $script = "
jQuery(document).ready(function(){
  jQuery('." . $element . "').magnificPopup({
    type: 'ajax',
    mainClass: '" . $mainClass . "',
    removalDelay: 0,
    closeOnBgClick: false,
    gallery:{
      enabled: true
    }      
  });
});
";
                
                // Add the script to the document head.
                $document->addScriptDeclaration($script);
	}
        
	/**
	 * Write an <a></a> element.
	 *
         * @access   public
         * @static
	 * @param    string  $url      The relative URL to use for the href attribute.
	 * @param    string  $text     The target attribute to use.
	 * @param    array   $attribs  An associative array of attributes to add.
	 * @return   string  The rendered <a> string.
	 */
	public static function link($item, $text, $attribs = array())
	{
                // Initialise variables.
		$app = JFactory::getApplication();
                $user = JFactory::getUser();
                
                // Load HWD config.
                $hwdms = hwdMediaShareFactory::getInstance();
                $config = $hwdms->getConfig();

                // Load HWD utilities.
                hwdMediaShareFactory::load('utilities');
                $utilities = hwdMediaShareUtilities::getInstance();
                
                // If media has access restriction then check if these have been passed
                if ($app->isSite())
                {
                        $denied = false;
                        $associate = false;

                        // Check group access level and access permissions.
                        $groups = $user->getAuthorisedViewLevels();
                        if (!in_array($item->access, $groups)) 
                        { 
                                $denied = true;
                        }
                
                        if (isset($item->params) && is_string($item->params))
                        {
                                $params	= new JRegistry($item->params);
                                if ($params->get('author_only') == 1 && $user->id != $item->created_user_id)
                                {
                                        $denied = true;
                                }

                                if ($params->get('age_restriction') == 1)
                                {
                                        $denied = true;
                                }

                                if ($params->get('password_protect') == 1)
                                {
                                        $denied = true;
                                }
                        }   
                        
                        if ($denied)
                        {
                                return '<a href="' . JRoute::_(hwdMediaShareHelperRoute::getMediaItemRoute($item->slug, array(), $associate)) . '">' . $text . '</a>';
                        }
                }
                
                // Load HWD libraries.
                hwdMediaShareFactory::load('audio');
                hwdMediaShareFactory::load('documents');
                hwdMediaShareFactory::load('downloads');
                hwdMediaShareFactory::load('images');
                hwdMediaShareFactory::load('remote');
                hwdMediaShareFactory::load('videos');

                // Set an empty URL.
                $popup_src = '';
                $popup_type = 'ajax';
                $popup_id = $item->id;
                $popup_link = $app->isAdmin() ? '#' : $utilities->relToAbs(JRoute::_(hwdMediaShareHelperRoute::getMediaItemRoute($item->slug, array(), false)));
                $popup_class = 'media-popup-general';

                // Get our required view.
                $view = $app->isAdmin() ? 'editmedia' : 'mediaitem';
                
                if(!isset($item->media_type) || $item->media_type == 0)
                {
                        $item->media_type = hwdMediaShareMedia::loadMediaType($item);
                }
                        
                switch ($item->type) 
                {
                        case 1: // Local
                        case 4: // RTMP
                        case 5: // CDN
                        case 7: // Linked file
                                switch ($item->media_type) 
                                {
                                        case 1: // Audio
                                                $popup_src = 'index.php?option=com_hwdmediashare&view=' . $view . '&layout=modal&tmpl=component&id=' . $item->id;
                                                $popup_type = 'ajax';
//                                                $class = 'media-popup-ajax-audio';
//                                                JHtml::_('HwdPopup.ajax', 'audio'); 
                                        break;
                                        case 2: // Document
                                                $popup_src = 'index.php?option=com_hwdmediashare&view=' . $view . '&layout=modal&tmpl=component&id=' . $item->id;
                                                $popup_type = 'ajax';
//                                                $class = 'media-popup-ajax-document';
//                                                JHtml::_('HwdPopup.ajax', 'document'); 
                                        break;
                                        case 3: // Image
                                                // Check original for a native image format less than 2000.
                                                $folders = hwdMediaShareFiles::getFolders($item->key);
                                                $filename = hwdMediaShareFiles::getFilename($item->key, 1);
                                                $ext = hwdMediaShareFiles::getExtension($item, 1);
                                                $path = hwdMediaShareFiles::getPath($folders, $filename, $ext);                                                
                                                if (file_exists($path) && hwdMediaShareImages::isNativeImage($ext))
                                                {
                                                        list($width, $height, $type, $attr) = getimagesize($path);
                                                        $width = (int) $width;
                                                        $height = (int) $height;
                
                                                        if ($width < 2000)
                                                        {                                                        
                                                                $popup_src = hwdMediaShareDownloads::url($item, 1); 
                                                                $popup_type = 'image';
//                                                                $class = 'media-popup-image';
//                                                                JHtml::_('HwdPopup.image', 'image'); 
                                                        }
                                                }
                                                
                                                if (empty($popup_src) && $image = hwdMediaShareImages::getJpg($item))
                                                {
                                                        $popup_src = $image->url;    
                                                        $popup_type = 'image';
//                                                        $class = 'media-popup-image';
//                                                        JHtml::_('HwdPopup.image', 'image');  
                                                }
                                        break;
                                        case 4: // Video
                                                $popup_src = 'index.php?option=com_hwdmediashare&view=' . $view . '&layout=modal&tmpl=component&id=' . $item->id;
                                                $popup_type = 'ajax';
//                                                $class = 'media-popup-ajax-video';
//                                                JHtml::_('HwdPopup.ajax', 'video'); 
                                        break;
                                }
                                break;
                        case 2: // Remote
                                $lib = hwdMediaShareRemote::getInstance();
                                $lib->_url = $item->source;
                                $host = $lib->getHost();                       
                                $remotePluginClass = $lib->getRemotePluginClass($host);
                                $remotePluginPath = $lib->getRemotePluginPath($host);
                                $remotePluginHost = preg_replace('/[^a-zA-Z0-9\s]/', '', $host);
                                $type = 0;

                                // Import HWD plugins.
                                JLoader::register($remotePluginClass, $remotePluginPath);
                                if (class_exists($remotePluginClass))
                                {
                                        $HWDremote = call_user_func(array($remotePluginClass, 'getInstance'));
                                        if (method_exists($HWDremote, 'getDirectDisplayLocation'))
                                        {
                                                $popup_src = $HWDremote->getDirectDisplayLocation($item);
                                        }
                                        if ($popup_src && method_exists($HWDremote, 'getDirectDisplayType'))
                                        {
                                                $type = $HWDremote->getDirectDisplayType($item);
                                        }  
                                }
                                else
                                {
                                        $remotePluginClass = $lib->getRemotePluginClass($lib->getDomain());
                                        $remotePluginPath = $lib->getRemotePluginPath($lib->getDomain());
                                        JLoader::register($remotePluginClass, $remotePluginPath);
                                        if (class_exists($remotePluginClass))
                                        {
                                                $HWDremote = call_user_func(array($remotePluginClass, 'getInstance'));
                                                if (method_exists($HWDremote, 'getDirectDisplayLocation'))
                                                {
                                                        $popup_src = $HWDremote->getDirectDisplayLocation($item);
                                                }
                                                if ($popup_src && method_exists($HWDremote, 'getDirectDisplayType'))
                                                {
                                                        $type = $HWDremote->getDirectDisplayType($item);
                                                }  
                                        }                          
                                }                               
                                switch ($type) 
                                {
                                        case 1: // Audio
                                                $popup_type = 'iframe';
//                                                $class = 'media-popup-iframe-audio-' . $remotePluginHost;
//                                                JHtml::_('HwdPopup.iframe', 'audio', $remotePluginHost);
                                        break; 
                                        case 2: // Document
                                                $popup_type = 'iframe';
//                                                $class = 'media-popup-iframe-document-' . $remotePluginHost;
//                                                JHtml::_('HwdPopup.iframe', 'document', $remotePluginHost);
                                        break; 
                                        case 3: // Image                                            
                                                $popup_type = 'image';
                                                // $class = 'media-popup-iframe-image-' . $remotePluginHost;
                                                // JHtml::_('HwdPopup.image', 'image', $remotePluginHost);
                                                $class = 'media-popup-image';
                                                JHtml::_('HwdPopup.image', 'image'); 
                                        break; 
                                        case 4: // Video
                                                $popup_type = 'iframe';
//                                                $class = 'media-popup-iframe-video-' . $remotePluginHost;
//                                                JHtml::_('HwdPopup.iframe', 'video', $remotePluginHost);
                                        break; 
                                }
                                break;
                        case 3: // Embed code
                                $popup_src = 'index.php?option=com_hwdmediashare&view=' . $view . '&layout=modal&tmpl=component&id=' . $item->id;
                                $popup_type = 'ajax';
//                                $class = 'media-popup-ajax-embed';
//                                JHtml::_('HwdPopup.ajax', 'embed'); 
                        break;
                        case 6: // Platform
                        break;
                }
        
                // If we still don't have an URL then we use the default modal display.
                if (!$popup_src) 
                {
                        $popup_src = 'index.php?option=com_hwdmediashare&view=' . $view . '&layout=modal&tmpl=component&id=' . $item->id;
                }
                
                // Route the resource.
                if (!$app->isAdmin() && JURI::isInternal($popup_src))
                {
                        // JRoute::_(hwdMediaShareHelperRoute::getMediaItemRoute($item->slug, array(), $associate))
                        // $popup_src = 'index.php?option=com_hwdmediashare&view=' . $view . '&layout=modal&tmpl=component&id=' . $item->id;
                        // $popup_src = JRoute::_('index.php?option=com_hwdmediashare&view=' . $view . '&layout=modal&tmpl=component&id=' . $item->id);
                        $popup_src = JRoute::_(hwdMediaShareHelperRoute::getMediaItemRoute($item->id, array('layout' => 'modal', 'tmpl' => 'component'), $associate));
                }

                // Insert our popup javascript.
                JHtml::_('HwdPopup.general');
                        
		if (is_array($attribs))
		{
                        !isset($attribs['class']) ? $attribs['class'] = $popup_class : $attribs['class'] .= ' ' . $popup_class;
                        !isset($attribs['title']) ? $attribs['title'] = htmlspecialchars($item->title) : $attribs['title'] .= htmlspecialchars($item->title);

			$attribs = JArrayHelper::toString($attribs);
		}

		return '<a href="' . $popup_src . '" ' . $attribs . ' data-popup-type="' . $popup_type . '" data-popup-id="' . $popup_id . '" data-popup-link="' .  urlencode($popup_link) . '">' . $text . '</a>';
	}        
}

<?php
/**
 * @package     Joomla.site
 * @subpackage  Component.hwdmediashare
 *
 * @copyright   Copyright (C) 2013 Highwood Design Limited. All rights reserved.
 * @license     GNU General Public License http://www.gnu.org/copyleft/gpl.html
 * @author      Dave Horsfall
 */

defined('_JEXEC') or die;

class JHtmlHwdHead
{
	/**
	 * Method to insert core head tags into a view.
         * 
	 * @access  public
         * @param   object  $params  The parameter object.
	 * @return  void
	 */
	public static function core($params)
	{               
                // Initialise variable.
                $app = JFactory::getApplication();
                $doc = JFactory::getDocument();

                // Load HWD config.
                $hwdms = hwdMediaShareFactory::getInstance();
                $config = $hwdms->getConfig();

                // Add page assets.
                JHtml::_('bootstrap.framework');
                $doc->addStyleSheet(JURI::root( true ) . '/media/com_hwdmediashare/assets/css/hwd.css');
                $doc->addScript(JURI::root( true ) . '/media/com_hwdmediashare/assets/javascript/hwd.min.js');
                if ($params->get('load_lite_css') != 0)           $doc->addStyleSheet(JURI::root( true ) . '/media/com_hwdmediashare/assets/css/lite.css');
                if ($params->get('load_joomla_css') != 0)         $doc->addStyleSheet(JURI::root( true ) . '/media/com_hwdmediashare/assets/css/joomla.css');
                if ($params->get('list_thumbnail_aspect') != 0)   $doc->addStyleSheet(JURI::root( true ) . '/media/com_hwdmediashare/assets/css/aspect.css');
                if ($params->get('list_thumbnail_aspect') != 0)   $doc->addScript(JURI::root( true ) . '/media/com_hwdmediashare/assets/javascript/aspect.js'); 
                if ($doc->direction == 'rtl')                     $doc->addStyleSheet(JURI::root( true ) . '/media/com_hwdmediashare/assets/css/rtl.css');
                if (file_exists(JPATH_ROOT . '/media/com_hwdmediashare/assets/css/custom.css')) $doc->addStyleSheet(JURI::root( true ) . '/media/com_hwdmediashare/assets/css/custom.css');
                                
                // Insert variables.
                if (!defined('_HWDVARS'))
                {
                        define('_HWDVARS', 1);
                        
                        $js   = array();
                        $js[] = 'var hwdms_live_site = "' . JURI::root() . 'index.php";';
                        $js[] = 'var hwdms_text_subscribe = "' . JText::_('COM_HWDMS_SUBSCRIBE') . '";';
                        $js[] = 'var hwdms_text_subscribed = "' . JText::_('COM_HWDMS_SUBSCRIBED') . '";';
                        $js[] = 'var hwdms_text_error_occured = "' . JText::_('COM_HWDMS_ERROR_OCCURED_JS') . '";';
                        $doc->addScriptDeclaration(implode("\n", $js));  
                }                              
        }

	/**
	 * Method to insert head tags into a mediaitem view.
         * 
	 * @access  public
         * @param   object  $params  The parameter object.
         * @param   object  $item    The media object.
	 * @return  void
	 */
	public static function mediaitem($params, $item)
	{
                // Initialise variable.
                $app = JFactory::getApplication();
                $doc = JFactory::getDocument();

                // Load HWD config.
                $hwdms = hwdMediaShareFactory::getInstance();
                $config = $hwdms->getConfig();

                // Load HWD utilities.
                hwdMediaShareFactory::load('utilities');
                $utilities = hwdMediaShareUtilities::getInstance();

                // Add google canonical link: https://support.google.com/webmasters/answer/139066?hl=en
                // $doc->addCustomTag('<link rel="canonical" href="'.hwdMediaShareMedia::getPermalink($item->id).'" />');
                $doc->addCustomTag('<link rel="canonical" href="'.$utilities->relToAbs(JRoute::_(hwdMediaShareHelperRoute::getMediaItemRoute($item->id, array(), false))).'" />');
        
                // Although probably redundant, we'll include this too.
                $doc->addCustomTag('<link rel="image_src" href="'.$utilities->relToAbs(JRoute::_(hwdMediaShareThumbnails::thumbnail($item))).'"/>');
                
                // Add Facebook AppId: https://developers.facebook.com/docs/insights
                $doc->addCustomTag('<meta property="fb:app_id" content="'.$config->get('facebook_appid').'"/>');
                
                // Add basic metadata: http://ogp.me/#metadata
                $doc->addCustomTag('<meta property="og:title" content="'.$utilities->escape($item->title).'"/>');
                $doc->addCustomTag('<meta property="og:image" content="'.$utilities->relToAbs(JRoute::_(hwdMediaShareThumbnails::thumbnail($item))).'"/>');
                $doc->addCustomTag('<meta property="og:url" content="'.hwdMediaShareMedia::getPermalink($item->id).'"/>');
                
                // Add optional metadata: http://ogp.me/#optional
                $doc->addCustomTag('<meta property="og:site_name" content="'.$utilities->escape($app->getCfg('sitename')).'"/>');
                $doc->addCustomTag('<meta property="og:description" content="'.$utilities->escape(JHtml::_('string.truncate', $item->description, $config->get('list_desc_truncate'), true, false)).'"/>');

                // Add Twitter summary card.
                $doc->addCustomTag('<meta property="twitter:card" content="summary_large_image"/>');
                $doc->addCustomTag('<meta property="twitter:site" content="@'.$config->get('twitter_username').'"/>');
                $doc->addCustomTag('<meta property="twitter:creator" content="@'.$config->get('twitter_username').'"/>');
                $doc->addCustomTag('<meta property="twitter:title" content="'.$utilities->escape($item->title).'"/>');
                $doc->addCustomTag('<meta property="twitter:description" content="'.$utilities->escape(JHtml::_('string.truncate', $item->description, $config->get('list_desc_truncate'), true, false)).'"/>');
                $doc->addCustomTag('<meta property="twitter:image" content="'.$utilities->relToAbs(JRoute::_(hwdMediaShareThumbnails::thumbnail($item))).'"/>');
        
                // Check if og:type support is enabled.
                if ($config->get('facebook_og_type', 1) == 0)
                {
                        $doc->addCustomTag('<meta property="og:type" content="article"/>');
                        return;
                }
                
                // Add content specific data.
                switch ($item->type)
                {
                        case 2: // Remote
                                hwdMediaShareFactory::load('remote');
                                $lib = hwdMediaShareRemote::getInstance();
                                $lib->_url = $item->source;
                                $host = $lib->getHost();
                                $remotePluginClass = $lib->getRemotePluginClass($host);
                                $remotePluginPath = $lib->getRemotePluginPath($host);

                                // Import HWD remote plugin.
                                JLoader::register($remotePluginClass, $remotePluginPath);
                                if (class_exists($remotePluginClass))
                                {
                                        $remote = call_user_func(array($remotePluginClass, 'getInstance'));
                                        if (method_exists($remote, 'getDirectDisplayLocation'))
                                        {
                                                $url = $remote->getDirectDisplayLocation($item);
                                                $url = str_replace('youtube.com/embed/', 'youtube.com/v/', $url);

                                                $width = 640;
                                                $height = (int) ($width*$config->get('video_aspect', 0.75));
                                                $doc->addCustomTag('<meta property="og:type" content="video.movie"/>');
                                                $doc->addCustomTag('<meta property="og:video" content="' . $url . '"/>');
                                                //$doc->addCustomTag('<meta property="og:video.secure_url" content="' . $remote->getDirectDisplayLocation($item) . '"/>');
                                                $doc->addCustomTag('<meta property="og:video:width" content="' . $width . '"/>');
                                                $doc->addCustomTag('<meta property="og:video:height" content="' . $height . '"/>');
                                                $doc->addCustomTag('<meta property="og:video:type" content="application/x-shockwave-flash"/>');
                                                return;
                                        }        
                                }
                                else
                                {
                                        // If we can't find a suitable plugin, then look for higher level domain plugins.                            
                                        $host = parse_url($lib->_url, PHP_URL_HOST);
                                        $parts = explode('.', $host);

                                        for($i = 1; $i <= count($parts) - 2; $i++)
                                        {
                                                $subdomains = array_slice($parts, $i, count($parts));
                                                $lookup = implode($subdomains, '.');

                                                // If we can't find a suitable plugin, then look for top level domain plugin.
                                                $remotePluginClass = $lib->getRemotePluginClass($lookup);
                                                $remotePluginPath = $lib->getRemotePluginPath($lookup);

                                                JLoader::register($remotePluginClass, $remotePluginPath);
                                                if (class_exists($remotePluginClass))
                                                {    
                                                        $remote = call_user_func(array($remotePluginClass, 'getInstance'));
                                                        if (method_exists($remote, 'getDirectDisplayLocation'))
                                                        {
                                                                $url = $remote->getDirectDisplayLocation($item);
                                                                $url = str_replace('youtube.com/embed/', 'youtube.com/v/', $url);

                                                                $width = 640;
                                                                $height = (int) ($width*$config->get('video_aspect', 0.75));
                                                                $doc->addCustomTag('<meta property="og:type" content="video.movie"/>');
                                                                $doc->addCustomTag('<meta property="og:video" content="' . $url . '"/>');
                                                                //$doc->addCustomTag('<meta property="og:video.secure_url" content="' . $remote->getDirectDisplayLocation($item) . '"/>');
                                                                $doc->addCustomTag('<meta property="og:video:width" content="' . $width . '"/>');
                                                                $doc->addCustomTag('<meta property="og:video:height" content="' . $height . '"/>');
                                                                $doc->addCustomTag('<meta property="og:video:type" content="application/x-shockwave-flash"/>');
                                                                return;
                                                        }
                                                }
                                        }
                                }
                                
                                // Default.
                                $doc->addCustomTag('<meta property="og:type" content="article"/>');
                                return;
                        break;
                }

                switch ($item->media_type)
                {
                        case 1: // Audio
                                hwdMediaShareFactory::load('audio');

                                // Add object type: http://ogp.me/#types
                                $doc->addCustomTag('<meta property="og:type" content="article"/>');
                                $doc->addCustomTag('<meta property="og:duration" content="' . (int) $item->duration . '"/>');
                                return;
                                
                                // Add structured property: http://ogp.me/#structured
                                if ($mp3 = hwdMediaShareAudio::getMp3($item))
                                {
                                        $doc->addCustomTag('<meta property="og:audio" content="' . $utilities->relToAbs($mp3->url) . '"/>');
                                        $doc->addCustomTag('<meta property="og:audio:secure_url" content="' . $utilities->relToAbs($mp3->url) . '"/>');
                                        $doc->addCustomTag('<meta property="og:audio:type" content="' . $mp3->type . '"/>');
                                }
                                
                                // Default.
                                $doc->addCustomTag('<meta property="og:type" content="article"/>');
                                return;
                        break;
                        case 2: // Document
                                $doc->addCustomTag('<meta property="og:type" content="article"/>');
                                return;
                        break;
                        case 3: // Image
                                $doc->addCustomTag('<meta property="og:type" content="article"/>');
                                return;
                        break;
                        case 4: // Video
                                hwdMediaShareFactory::load('videos');

                                // Add object type: http://ogp.me/#types
                                $doc->addCustomTag('<meta property="og:type" content="video.movie"/>');
                                $doc->addCustomTag('<meta property="og:duration" content="' . (int) $item->duration . '"/>');
                                
                                $mp4 = hwdMediaShareVideos::getMp4($item);
                                if ($mp4)
                                {
                                        // Add structured property: http://ogp.me/#structured
                                        $width = 640;
                                        $height = (int) ($width*$config->get('video_aspect', 0.75));
                                        // Flash video
                                        $doc->addCustomTag('<meta property="og:video" content="' . str_replace('https:', 'http:', JURI::root()) . 'media/com_hwdmediashare/assets/swf/player.swf?swfMovie=' . urlencode($utilities->relToAbs($mp4->url)) . '&autoplay=true"/>');
                                        $doc->addCustomTag('<meta property="og:video:secure_url" content="' . str_replace('http:', 'https:', JURI::root()) . 'media/com_hwdmediashare/assets/swf/player.swf?swfMovie=' . urlencode($utilities->relToAbs($mp4->url)) . '&autoplay=true"/>');
                                        // We use a very basic Flash player to provide playback functionality within Facebook using the OpenGraph protocol. 
                                        // You can switch this player for JWPlayer 5, which was a very popular Flash based player, but is no longer supported
                                        // or maintained. 
                                        // $doc->addCustomTag('<meta property="og:video" content="' . str_replace('https:', 'http:', JURI::root()) . 'media/com_hwdmediashare/assets/swf/jwplayer.swf?file=' . urlencode($utilities->relToAbs($mp4->url)) . '&autostart=true"/>');
                                        // $doc->addCustomTag('<meta property="og:video:secure_url" content="' . str_replace('http:', 'https:', JURI::root()) . 'media/com_hwdmediashare/assets/swf/jwplayer.swf?file=' . urlencode($utilities->relToAbs($mp4->url)) . '&autostart=true"/>');
                                        $doc->addCustomTag('<meta property="og:video:width" content="' . $width . '"/>');
                                        $doc->addCustomTag('<meta property="og:video:height" content="' . $height . '"/>');
                                        $doc->addCustomTag('<meta property="og:video:type" content="application/x-shockwave-flash"/>');            
                                        // HTML5 (MP4)
                                        $doc->addCustomTag('<meta property="og:video" content="' . $utilities->relToAbs($mp4->url) . '"/>');
                                        $doc->addCustomTag('<meta property="og:video:width" content="' . $width . '"/>');
                                        $doc->addCustomTag('<meta property="og:video:height" content="' . $height . '"/>');
                                        $doc->addCustomTag('<meta property="og:video:type" content="video/mp4"/>');
                                        return;
                                }
                                
                                // Default.
                                $doc->addCustomTag('<meta property="og:type" content="article"/>');
                                return;
                        break;
                }
	}
}        
                
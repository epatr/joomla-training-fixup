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

class hwdMediaShareMedia
{
	/**
	 * An array of medai type data.
         * 
         * @access      protected
	 * @var         array
	 */ 
	protected static $loadedMediaTypes = array();
        
	/**
	 * Class constructor.
	 *
	 * @access  public
	 * @param   mixed   $properties  Associative array to set the initial properties of the object.
         * @return  void
	 */
	public function __construct($config = array())
	{
	}

	/**
	 * Returns the hwdMediaShareMedia object, only creating it if it
	 * doesn't already exist.
	 *
	 * @return  hwdMediaShareMedia A hwdMediaShareMedia object.
	 * @since   0.1
	 */
	public static function getInstance()
	{
		static $instance;

		if (!isset ($instance))
                {
			$c = 'hwdMediaShareMedia';
                        $instance = new $c;
		}

		return $instance;
	}
        
	/**
	 * Method to render a media item
         * 
	 * @since   0.1
	 **/
	public static function get($item)
	{
                // Initialise variables.            
                $app = JFactory::getApplication();

                // Load HWD config.
                $hwdms = hwdMediaShareFactory::getInstance();
                $config = $hwdms->getConfig();

                // Load HWD utilities.
                hwdMediaShareFactory::load('utilities');
                $utilities = hwdMediaShareUtilities::getInstance();
                
                switch ($item->type) 
                {
                        case 2: // Remote
                                hwdMediaShareFactory::load('remote');
                                $lib = hwdMediaShareRemote::getInstance();
                                $lib->_url = $item->source;
                                $host = $lib->getHost();                       
                                $remotePluginClass = $lib->getRemotePluginClass($host);
                                $remotePluginPath = $lib->getRemotePluginPath($host);

                                // Import hwdMediaShare plugins
                                JLoader::register($remotePluginClass, $remotePluginPath);
                                if (class_exists($remotePluginClass))
                                {
                                        $HWDremote = call_user_func(array($remotePluginClass, 'getInstance'));
                                        if ($display = $HWDremote->display($item))
                                        {
                                                return $display;
                                        }
                                        else
                                        {
                                                return $utilities->printNotice($HWDremote->getError(), '', 'info', true);
                                                
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
                                                        $HWDremote = call_user_func(array($remotePluginClass, 'getInstance'));
                                                        if ($display = $HWDremote->display($item))
                                                        {
                                                                return $display;
                                                        }
                                                        else
                                                        {
                                                                return $utilities->printNotice($HWDremote->getError(), '', 'info', true);

                                                        }
                                                }
                                        }                          
                                }
                                
                                return $utilities->printNotice(JText::_('COM_HWDMS_ERROR_UNABLE_TO_LOAD_REMOTE_PLUGIN'), '', 'info', true);
                        break;
                        case 3:
                                // Embed code
                                return $item->embed_code;
                        break;
                        case 4:
                                // RTMP
                                hwdMediaShareFactory::load('rtmp');
                                return hwdMediaShareRtmp::display($item);
                        break;
                        case 6:
                                // Platform
                                $pluginClass = 'plgHwdmediashare'.$config->get('platform');
                                $pluginPath = JPATH_ROOT.'/plugins/hwdmediashare/'.$config->get('platform').'/'.$config->get('platform').'.php';
                                if (file_exists($pluginPath))
                                {
                                        JLoader::register($pluginClass, $pluginPath);
                                        if (class_exists($pluginClass))
                                        {
                                                $HWDplatform = call_user_func(array($pluginClass, 'getInstance'));
                                                if ($display = $HWDplatform->display($item))
                                                {
                                                        return $display;
                                                }
                                                else
                                                {
                                                        return $utilities->printNotice($HWDplatform->getError(), '', 'info', true);
                                                }
                                        }
                                        else
                                        {
                                                return $utilities->printNotice(JText::_('COM_HWDMS_ERROR_UNABLE_TO_LOAD_PLATFORM_PLUGIN'), '', 'info', true);
                                        }
                                }
                        break;
                        case 7:
                                // Linked file
                                hwdMediaShareFactory::load('documents');
                                return hwdMediaShareDocuments::display($item);
                        break;
                        case 8:
                                // Stream
                                hwdMediaShareFactory::load('streaming');
                                return hwdMediaShareStreaming::display($item);
                        break;                    
                }
                
                if(!isset($item->media_type) || $item->media_type == 0)
                {
                        $item->media_type = hwdMediaShareMedia::loadMediaType($item);
                }

                if ($config->get('process_warning') > 0 && (!defined('_JCLI') && $app->isSite()))
                {
                        // Check for queued processes
                        hwdMediaShareFactory::load('processes');
                        $HWDprocesses = hwdMediaShareProcesses::getInstance();                       
                        $queued = $HWDprocesses->countQueue($item->id);
                        if ($queued)
                        {
                                if ($config->get('process_warning') == 1)
                                {
                                        echo $utilities->printNotice(JText::_('COM_HWDMS_NOTICE_PROCESSING_WARNING'), '', 'info', true);
                                }
                                else
                                {
                                        return $utilities->printNotice(JText::_('COM_HWDMS_NOTICE_PROCESSING_ERROR'), JText::_('COM_HWDMS_NOTICE_PROCESSING_ERROR_DESC'), 'info', true);
                                }
                        }
                        elseif ($item->type == 1 && $config->get('cdn') && $config->get('process_warning') == 2)
                        {
                                // Check for CDN transfer.
                                return $utilities->printNotice(JText::_('COM_HWDMS_NOTICE_PROCESSING_ERROR'), JText::_('COM_HWDMS_NOTICE_PROCESSING_ERROR_DESC_CDN'), 'info', true);
                        }                        
                }

                switch ($item->media_type)
                {
                        case 1: // Audio
                                hwdMediaShareFactory::load('audio');
                                return hwdMediaShareAudio::display($item);
                        break;
                        case 2: // Document
                                hwdMediaShareFactory::load('documents');
                                return hwdMediaShareDocuments::display($item);
                        break;
                        case 3: // Image
                                hwdMediaShareFactory::load('images');
                                return hwdMediaShareImages::display($item);
                        break;
                        case 4: // Video
                                hwdMediaShareFactory::load('videos');
                                return hwdMediaShareVideos::display($item);
                        break;
                }

                return false;
	}

        /**
	 * Method to get the type of media from the media extension
         * 
	 * @since   0.1
	 **/
	public static function loadMediaType($item)
	{
                $item->id = (int) $item->id;
		if (!isset(self::$loadedMediaTypes[$item->id])) 
                {
                        // Initialise variables.
                        $db = JFactory::getDBO();
                        
                        if (isset($item->ext_id) && $item->ext_id > 0)
                        {
                                $query = $db->getQuery(true)
                                        ->select('media_type')
                                        ->from('#__hwdms_ext')
                                        ->where($db->quoteName('id').' = '.$item->ext_id);
                                try
                                {                
                                        $db->setQuery($query);
                                        $result = $db->loadResult();
                                }
                                catch (Exception $e)
                                {
                                        $this->setError($e->getMessage());
                                        return false;
                                }

                                if ($result)
                                {
                                        // If we get a result then it is a local media type
                                        self::$loadedMediaTypes[$item->id] = $db->loadResult();
                                        return self::$loadedMediaTypes[$item->id];
                                }
                        }
                        elseif (isset($item->media_type) && $item->media_type > 0)
                        {
                                self::$loadedMediaTypes[$item->id] = $item->media_type;
                                return self::$loadedMediaTypes[$item->id];
                        }
                        elseif (isset($item->type) && $item->type == 7)
                        {
                                $ext = strtolower(JFile::getExt(basename(parse_url($item->source, PHP_URL_PATH))));

                                $query = $db->getQuery(true)
                                        ->select('media_type')
                                        ->from('#__hwdms_ext')
                                        ->where($db->quoteName('ext').' = '.$db->quote($ext));
                                try
                                {                
                                        $db->setQuery($query);
                                        $result = $db->loadResult();
                                }
                                catch (Exception $e)
                                {
                                        $this->setError($e->getMessage());
                                        return false;
                                }

                                if ($result)
                                {
                                        // If we get a result then it is a local media type
                                        self::$loadedMediaTypes[$item->id] = $db->loadResult();
                                        return self::$loadedMediaTypes[$item->id];
                                }
                        }                
		}

		if (isset(self::$loadedMediaTypes[$item->id]) && self::$loadedMediaTypes[$item->id] > 0) 
                {
                        return self::$loadedMediaTypes[$item->id];
                }
                    
                // Don't know the media type.
                return 0;                
	}
        
        /**
	 * Method to get the media icon for administrator
         * 
	 * @since   0.1
	 **/
	public function getMediaTypeIcon( &$item )
	{
                if (!isset($item->media_type)) return;

                switch ($item->media_type)
                {
                        case 1:
                                return JURI::root(true).'/media/com_hwdmediashare/assets/images/audio.png';
                        break;
                        case 2:
                                return JURI::root(true).'/media/com_hwdmediashare/assets/images/document.png';
                        break;
                        case 3:
                                return JURI::root(true).'/media/com_hwdmediashare/assets/images/image.png';
                        break;
                        case 4:
                                return JURI::root(true).'/media/com_hwdmediashare/assets/images/video.png';
                        break;
                }
	}
        
        /**
	 * Method to get permenant link to a media item
         * 
	 * @since   0.1
	 **/
	public static function getPermalink($id, $xhtml = true, $ssl = null)
	{
                // Set the protocol.
                if(@$_SERVER['HTTPS'])
                {
                        $link = 'https://';
                }
                else
                {
                        $link = 'http://';
                } 
                
                // Set the host.
                $link.= $_SERVER['SERVER_NAME'];
                
                // Set the query without association so we get a single canonical URL.
                $link.= JRoute::_(hwdMediaShareHelperRoute::getMediaItemRoute($id, array(), false), $xhtml, $ssl);
                //$link.= JRoute::_('index.php?option=com_hwdmediashare&view=mediaitem&id='.$id, $xhtml, $ssl);
                
                return $link;
	}
        
        /**
	 * Method to get embed code for a media item
         * 
	 * @since   0.1
	 **/
	public static function getEmbedCode( $id )
	{
                $width = 560;
                $height = 315;
                if(@$_SERVER['HTTPS'])
                {
                        $link = 'https://';
                }
                else
                {
                        $link = 'http://';
                } 
                $link.= $_SERVER['SERVER_NAME'];
                $link.= JRoute::_('index.php?option=com_hwdmediashare&task=get.embed&id='.$id.'&width='.$width.'&height='.$height);
                $code = '<iframe width="'.$width.'" height="'.$height.'" src="'.$link.'" frameborder="0" scrolling="no"></iframe>';
                return $code;
	}
        
	/**
	 * Method to get human readable media type
         * 
         * @since   0.1
	 **/
	public static function getType($item)
	{
                switch ($item->type) 
                {
                        case 1:
                                return JText::_('COM_HWDMS_LOCAL_MEDIA');
                        break;
                        case 2:
                                return JText::_('COM_HWDMS_REMOTE_MEDIA');
                        break;
                        case 3:
                                return JText::_('COM_HWDMS_EMBEDDED_MEDIA');
                        break;
                        case 4:
                                return JText::_('COM_HWDMS_RTMP');
                        break;
                        case 5:
                                return JText::_('COM_HWDMS_CDN');
                        break;
                        case 6:
                                return JText::_('COM_HWDMS_MEDIA_HOSTING_PLATFORM');
                        break;
                        case 7:
                                return JText::_('COM_HWDMS_REMOTE_FILE');
                        break;   
                        case 8:
                                return JText::_('COM_HWDMS_REMOTE_STREAM');
                        break;                      
                }
	}
        
        /**
	 * Method to get human readable media type
         * 
         * @since   0.1
	 **/
	public static function getMediaType($item)
	{
                switch ($item->media_type)
                {
                        case 1:
                                return JText::_('COM_HWDMS_AUDIO');
                        break;
                        case 2:
                                return JText::_('COM_HWDMS_DOCUMENT');
                        break;
                        case 3:
                                return JText::_('COM_HWDMS_IMAGE');
                        break;
                        case 4:
                                return JText::_('COM_HWDMS_VIDEO');
                        break;
                }
	}
        
	/**
	 * Method to render a media item
         * 
	 * @since   0.1
	 **/
	public function getMeta($item)
	{
                if($item->type != 1) return false;
                
                if(!$item->media_type)
                {
                        $item->media_type = hwdMediaShareMedia::loadMediaType($item);
                }
                
                switch ($item->media_type)
                {
                        case 1: // Audio
                                hwdMediaShareFactory::load('audio');
                                return hwdMediaShareAudio::getMeta($item);
                        break;
                        case 2: // Document
                                hwdMediaShareFactory::load('documents');
                                return hwdMediaShareDocuments::getMeta($item);
                        break;
                        case 3: // Image
                                hwdMediaShareFactory::load('images');
                                return hwdMediaShareImages::getMeta($item);
                        break;
                        case 4: // Video
                                hwdMediaShareFactory::load('videos');
                                return hwdMediaShareVideos::getMeta($item);
                        break;
                }

                return false;
	}
        
        /**
         * Convert number of seconds into hours, minutes and seconds
         * and return an array containing those values
         *
         * @param integer $seconds Number of seconds to parse
         * @return array
         */
        public static function secondsToTime($seconds, $returnObject = false)
        {
                // Extract hours
                $hours = floor($seconds / (60 * 60));

                // Extract minutes
                $divisor_for_minutes = $seconds % (60 * 60);
                $minutes = floor($divisor_for_minutes / 60);

                // Extract the remaining seconds
                $divisor_for_seconds = $divisor_for_minutes % 60;
                $seconds = ceil($divisor_for_seconds);

                if ($returnObject)
                {
                        $object = new stdClass();
                        $object->h = sprintf("%02s", (int) $hours);
                        $object->m = sprintf("%02s", (int) $minutes);
                        $object->s = sprintf("%02s", (int) $seconds);
                        return $object;
                }
                else
                {
                        // Prepent seconds with zero if necessary
                        if ($seconds < 10)
                        {
                                $seconds = '0'.$seconds;
                        }
                        
                        if ($hours > 0)
                        {
                                return "$hours:$minutes:$seconds";
                        }
                        else
                        {
                                return "$minutes:$seconds";
                        }
                }
        }

        /**
         * Convert time to seconds.
         *
         * @param Str $time   Time str (format HH:MM:SS)
         * @return Int Amount of seconds
         */
        public static function timeToSeconds($time){
    $split_time = explode(':', $time);
    $modifier = pow(60, count($split_time) - 1);
    $seconds = 0;
    foreach($split_time as $time_part){
        $seconds += ($time_part * $modifier);
        $modifier /= 60;
    }
    return $seconds;
}   
        
	/**
	 * Method to check if an original file has been generated and return file data.
         * 
         * @access  public
         * @static
         * @param   object  $item   The media item.
         * @return  mixed   The original file object, false on fail.
	 */
	public static function getOriginal($item)
	{
                // Check if the original exists.
                $fileType = 1;

                hwdMediaShareFactory::load('files');
                hwdMediaShareFactory::load('documents');
                $folders = hwdMediaShareFiles::getFolders($item->key);
                $filename = hwdMediaShareFiles::getFilename($item->key, $fileType);
                $ext = hwdMediaShareFiles::getExtension($item, $fileType);
                $path = hwdMediaShareFiles::getPath($folders, $filename, $ext);
 
                if (file_exists($path))
                {
                        // Create file object.
                        $file = new JObject;
                        $file->local = true;
                        $file->path = $path;
                        $file->url = hwdMediaShareDownloads::url($item, $fileType);
                        $file->size = filesize($path);
                        $file->ext = $ext;
                        $file->type = hwdMediaShareDocuments::getContentType($ext);
                  
                        return $file;
                }

                return false;
	}         
}
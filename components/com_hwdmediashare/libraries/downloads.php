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

class hwdMediaShareDownloads extends JObject
{
	/**
	 * Class constructor.
	 *
	 * @access  public
	 * @param   mixed   $properties  Associative array to set the initial properties of the object.
         * @return  void
	 */
	public function __construct($properties = null)
	{
		parent::__construct($properties);
	}

	/**
	 * Returns the hwdMediaShareDownloads object, only creating it if it
	 * doesn't already exist.
	 *
	 * @access  public
         * @static
	 * @return  hwdMediaShareDownloads Object.
	 */ 
	public static function getInstance()
	{
		static $instance;

		if (!isset ($instance))
                {
			$c = 'hwdMediaShareDownloads';
                        $instance = new $c;
		}

		return $instance;
	}
        
	/**
	 * Method to push deliver a file using php
	 *
	 * @access  public
         * @static
	 * @return  void
	 */ 
	public static function push()
	{
                // Initialise variables.
                $db = JFactory::getDBO();
                $app = JFactory::getApplication();

                // Load HWD config.
                $hwdms = hwdMediaShareFactory::getInstance();
                $config = $hwdms->getConfig();
                
                // Get query parameters.
                $mediaId = $app->input->get('id', '', 'int');
                $elementType = $app->input->get('element_type', '1', 'int');
                $fileType = $app->input->get('file_type', '', 'int');
                $time = $app->input->get('time', '', 'cmd');
                $expire = $app->input->get('expire', '', 'cmd');

                $timeIsValid = (md5($config->get('secret_word') . $time) == $expire ? true : false);

                // Expire url after the cache lifetime.
                $lifetime = intval(($app->getCfg('cachetime') + 1) * 60);               
                
                // Die if url has expired.
                if (!$timeIsValid || (time() - $time > $lifetime))
                {
                        jexit('COM_HWDMS_ERROR_LINK_EXPIRED');
                }

                // Load HWD table path.
                JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_hwdmediashare/tables');               

                switch ($elementType)
                {
                        case 1: // Media
                                $table = JTable::getInstance('Media', 'hwdMediaShareTable');
                        break;
                        case 2: // Album
                                $table = JTable::getInstance('Album', 'hwdMediaShareTable');
                        break;
                        case 3: // Group
                                $table = JTable::getInstance('Group', 'hwdMediaShareTable');
                        break;
                        case 4: // Playlist
                                $table = JTable::getInstance('Playlist', 'hwdMediaShareTable');
                        break;
                        case 5: // Channel
                                $table = JTable::getInstance('Channel', 'hwdMediaShareTable');
                        break;
                        default: // Media
                                $table = JTable::getInstance('Media', 'hwdMediaShareTable');
                        break;
                }  
                
                // Attempt to load the table row.
                $return = $table->load($mediaId);

                // Check for a table object error.
                if ($return === false && $table->getError())
                {
                        $this->setError($table->getError());
                        return false;
                } 

                $properties = $table->getProperties(1);
                $item = JArrayHelper::toObject($properties, 'JObject');          
          
                hwdMediaShareFactory::load('files');
                hwdMediaShareFiles::getLocalStoragePath();

                $folders = hwdMediaShareFiles::getFolders($item->key);
                $filename = hwdMediaShareFiles::getFilename($item->key, $fileType);
                $ext = hwdMediaShareFiles::getExtension($item, $fileType);

                $path = hwdMediaShareFiles::getPath($folders, $filename, $ext);

                // If the file doesn't exist, then fail.
                if (!file_exists($path) || filesize($path) == 0)
                {
                        jexit('COM_HWDMS_ERROR_CAN_NOT_FIND_MEDIA_FILE');
		}
               
                // Define a more friendly filename from the alias.
                $fileTitle = $item->alias . '.' . $ext;

                // Set disposition based on request.
                $download = $app->input->get('download', 0, 'int');
                $disposition = $download ? 'attachment' : 'inline';
                
                hwdMediaShareFactory::load('files');
                $ext = hwdMediaShareFiles::getExtension($item, $fileType);
                hwdMediaShareFactory::load('documents');
                $type = hwdMediaShareDocuments::getContentType($ext);
                
                // Transfer file in chunks to preserve memory on the server.
                header('Content-Type: ' . $type);
                header('Content-Disposition: '.$disposition.'; filename="' . $fileTitle . '"');
                header('Content-Length: ' . filesize($path));
                hwdMediaShareDownloads::readfile_chunked($path, true);
                jexit();
	}
   
	/**
	 * Method to generate the dynamically delivered php url of a media file.
	 *
	 * @access  public
         * @static
         * @param   integer  $mediaId      The media ID.
         * @param   integer  $fileType     The file type.
         * @param   integer  $elementType  The element type.
         * @param   integer  $download     Flag to deliver file as a download (instead of inline).
	 * @return  string   The URL to deliver the file.
	 */ 
        public static function protectedUrl($mediaId, $fileType = 1, $elementType = 1, $download = 0)
        {
                // Initialise variables.
                $db = JFactory::getDBO();
                $app = JFactory::getApplication();
                
                // Load HWD config.
                $hwdms = hwdMediaShareFactory::getInstance();
                $config = $hwdms->getConfig();

                if ($mediaId == 0)
		{
			return false;
		}

                $width = $app->input->get('width', '', 'int');
                $time = time();
                $expire = md5($config->get('secret_word') . time());
		$width = min($width, $config->get('mediaitem_size'));

                // If trying to get an image, check for optimal size.
                if ($width > 0 && in_array($fileType, array(3,4,5,6,7)))
                {
                        if ($width <= 100)
                        {
                                $fileType = 3;
                        }
                        elseif ($width <= 240)
                        {
                                $fileType = 4;
                        }
                        elseif ($width <= 500)
                        {
                                $fileType = 5;
                        }
                        elseif ($width <= 640)
                        {
                                $fileType = 6;
                        }
                        else
                        {
                                $fileType = 7;
                        }
                }

                return JRoute::_('index.php?option=com_hwdmediashare&task=get.file&id=' . $mediaId . '&file_type=' . $fileType . '&time=' . $time . '&expire=' . $expire . '&format=raw&element_type=' . $elementType . ($download ? '&download=1' : ''), false);
        }
        
	/**
	 * Method to get the static url of a media file.
	 *
	 * @access  public
         * @static
         * @param   object   $media     The media object.
         * @param   integer  $fileType  The file type.
	 * @return  string   The URL to deilver the file.
	 */ 
        public static function publicUrl($media, $fileType = 1)
        {
                // Initialise variables.
                $db = JFactory::getDBO();
                $app = JFactory::getApplication();
                
                // Load HWD config.
                $hwdms = hwdMediaShareFactory::getInstance();
                $config = $hwdms->getConfig();

                if (!isset($media->id) || !isset($media->key) || !isset($media->ext_id))
		{
                        return '#';
		}

                // Check CDN framework.
                if ($media->type == 5)
		{
                        if (empty($media->storage)) $media->storage = $config->get('cdn','cdn_amazons3');
                        $pluginClass = 'plgHwdmediashare'.$media->storage;
                        $pluginPath = JPATH_ROOT.'/plugins/hwdmediashare/'.$media->storage.'/'.$media->storage.'.php';
                        if (file_exists($pluginPath))
                        {
                                JLoader::register($pluginClass, $pluginPath);
                                $HWDcdn = call_user_func(array($pluginClass, 'getInstance'));
                                if (method_exists($HWDcdn, 'getFile'))
                                {
                                        if ($file = $HWDcdn->getFile($media, array($fileType)))
                                        {
                                                return $file->url;
                                        }                                    
                                }                                
                        }                  
                }
                
                hwdMediaShareFactory::load('files');
                hwdMediaShareFiles::getLocalStoragePath();

                $folders = hwdMediaShareFiles::getFolders($media->key);
                $filename = hwdMediaShareFiles::getFilename($media->key, $fileType);
                $ext = hwdMediaShareFiles::getExtension($media, $fileType);

                $path = hwdMediaShareFiles::getPath($folders, $filename, $ext);
                
                // If the file doesn't exist, then fail.
                if (!file_exists($path) || filesize($path) == 0)
                {
                        return '#';
		}
                
                return hwdMediaShareFiles::getUrl($folders, $filename, $ext);
        }
        
	/**
	 * Method to get the url of a media file.
	 *
	 * @access  public
         * @static
         * @param   object   $media     The media object.
         * @param   integer  $fileType  The file type.
         * @param   integer  $download  Flag to deliver file as a download (instead of inline).
	 * @return  string   The URL to deliver the file.
	 */ 
        public static function url($media, $fileType = 1, $download = 0)
        {
                // Load HWD config.
                $hwdms = hwdMediaShareFactory::getInstance();
                $config = $hwdms->getConfig();

                // Load cache object.
                $cache = JFactory::getCache('com_hwdmediashare');
                $cache->setCaching(1);
                
                if (($config->get('protect_media') == 1 || $download) && $media->type == 1)
                {
                        if ($config->get('caching'))
                        {
                                return $cache->call(array('hwdMediaShareDownloads', 'protectedUrl'), $media->id, $fileType, 1, $download);
                        }
                        else 
                        {
                                return hwdMediaShareDownloads::protectedUrl($media->id, $fileType, 1, $download);
                        }
                }
                else
                {
                        if ($config->get('caching'))
                        {
                                return $cache->call(array('hwdMediaShareDownloads', 'publicUrl'), $media, $fileType);
                        }
                        else 
                        {
                                return hwdMediaShareDownloads::publicUrl($media, $fileType);
                        }
                }
        }

        /**
         * Method to read and deliver the contents of a file in chunks.
	 *
	 * @access  public
         * @static
         * @param   string   $path      Path of file.
         * @param   boolean  $retbytes  Flag to return number of bytes delivered like readfile() does.
	 * @return  mixed
	 */ 
        public static function readfile_chunked($path, $retbytes = true)
        {
                //https://github.com/tuxxin/MP4Streaming
                //http://www.tuxxin.com/php-mp4-streaming/

                $chunksize = 1*(1024*1024); // Define bytes per chunk.
                $buffer = '';
                $cnt = 0;

                $handle = fopen($path, 'rb');
                if ($handle === false)
                {
                       return false;
                }

                // Support pseudo-streaming (seeking) in players.
                $size = filesize($path); // File size
                $length = $size; // Content length
                $start = 0; // Start byte
                $end = $size - 1; // End byte  
                
                header("Accept-Ranges: bytes");

                if (isset($_SERVER['HTTP_RANGE']))
                {
                        $c_start = $start;
                        $c_end = $end;
                        list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
                        if (strpos($range, ',') !== false) 
                        {
                                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                                header("Content-Range: bytes $start-$end/$size");
                                exit;
                        }

                        if ($range == '-')
                        {
                                $c_start = $size - substr($range, 1);
                        }
                        else
                        {
                                $range = explode('-', $range);
                                $c_start = $range[0];
                                $c_end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size;
                        }
                        $c_end = ($c_end > $end) ? $end : $c_end;

                        if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) 
                        {
                                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                                header("Content-Range: bytes $start-$end/$size");
                                exit;
                        }
                        $start = $c_start;
                        $end = $c_end;
                        $length = $end - $start + 1;
                        fseek($handle, $start);
                        header('HTTP/1.1 206 Partial Content');
                }
                
                header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
                header('Content-Length: ' . $length);

                while (!feof($handle))
                {
                       $buffer = fread($handle, $chunksize);
                       echo $buffer;
                       ob_flush();
                       flush();
                       if ($retbytes)
                       {
                               $cnt += strlen($buffer);
                       }
                }

                $status = fclose($handle);
                if ($retbytes && $status)
                {
                       return $cnt; // Return number of bytes delivered like readfile() does.
                }
                return $status;
        }
}

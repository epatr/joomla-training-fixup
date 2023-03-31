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

class hwdMediaShareStreaming extends JObject
{        
	/**
	 * Holds the new item details.
         * 
         * @access  public
	 * @var     object
	 */
	public $_item;

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
	 * Returns the hwdMediaShareStreaming object, only creating it if it
	 * doesn't already exist.
	 *
	 * @access  public
         * @static
	 * @return  hwdMediaShareStreaming Object.
	 */ 
	public static function getInstance()
	{
		static $instance;

		if (!isset ($instance))
                {
			$c = 'hwdMediaShareStreaming';
                        $instance = new $c;
		}

		return $instance;
	}
        
	/**
	 * Method to process an rtmp stream.
         * 
         * @access  public
         * @return  boolean  True on success.
	 */
	public function addStream()
	{
                // Initialise variables.            
                $db = JFactory::getDBO();
                $user = JFactory::getUser();
                $app = JFactory::getApplication();
                $date = JFactory::getDate();
                
                // Load HWD config.
                $hwdms = hwdMediaShareFactory::getInstance();
                $config = $hwdms->getConfig();
                
                // Load HWD utilities.
                hwdMediaShareFactory::load('utilities');
                $utilities = hwdMediaShareUtilities::getInstance();               
                
                // Check authorised.
                if (!$user->authorise('hwdmediashare.import', 'com_hwdmediashare'))
                {
                        $this->setError(JText::_('COM_HWDMS_ERROR_NOAUTHORISED'));
                        return false;
                }   
                
                // We will apply the safeHtml filter to the variable, but define additional allowed tags.
                jimport('joomla.filter.filterinput');
                $noHtmlFilter = JFilterInput::getInstance();
                
                // Retrieve filtered jform data.
                hwdMediaShareFactory::load('upload');
                $data = hwdMediaShareUpload::getProcessedUploadData();
                
                // Setup sources array.
                $sources = array();

                foreach ($data['source_type'] as $key => $source_type)
                {
                        // Setup source array.
                        $source = array();
                        
                        // Validate data.
                        $type = isset($data['source_type'][$key]) ? (int) $data['source_type'][$key] : false;
                        $quality = isset($data['source_quality'][$key]) ? (int) $data['source_quality'][$key] : false;
                        $url = isset($data['source_url'][$key]) ? trim($data['source_url'][$key]) : false;

                        // Validate none rtmp streams.
                        if ($type != 1 && $url && !filter_var($url, FILTER_VALIDATE_URL))
                        {
                                $this->setError(JText::_('COM_HWDMS_ERROR_NOT_VALID_SOURCE'));
                                continue;
                        }  
                        
                        // Validate rtmp streams.                        
                        if ($type == 1 && $url)
                        {
                                $pattern = '~^rtmp://~';
                                preg_match($pattern, $url, $matches);

                                if (!isset($matches[0]) || empty($matches[0]))
                                {
                                        $this->setError(JText::_('COM_HWDMS_ERROR_NOT_VALID_SOURCE'));
                                        continue;                                    
                                }
                        }                          

                        if ($type && $quality && $url)
                        {
                                $source['type'] = $type;
                                $source['quality'] = $quality;
                                $source['url'] = $url;
                                
                                // Add to sources array.
                                $sources[] = $source; 
                        }
                }
                
                if (!count($sources))
                {
                        $this->setError(JText::_('COM_HWDMS_ERROR_NO_VALID_SOURCES_FOUND'));
                        return false; 
                }

                // Define a key so we can copy the file into the storage directory.
                if (!$key = $utilities->generateKey(1))
                {
                        $this->setError($utilities->getError());
                        return false;
                }  
                                
                // Set approved/pending.
                (!$app->isAdmin() && $config->get('approve_new_media')) == 1 ? $status = 2 : $status = 1; 
                $config->get('approve_new_media') == 1 ? $status = 2 : $status = 1; 

                JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_hwdmediashare/tables');
                $table = JTable::getInstance('media', 'hwdMediaShareTable');

                $post = array();
                
                // Check if we need to replace an existing media item.
                if (isset($data['id']) && $data['id'] > 0 && $app->isAdmin() && $user->authorise('core.edit', 'com_hwdmediashare'))
                {
                        // Attempt to load the existing table row.
                        $return = $table->load($data['id']);

                        // Check for a table object error.
                        if ($return === false && $table->getError())
                        {
                                $this->setError($table->getError());
                                return false;
                        }

                        $properties = $table->getProperties(1);
                        $replace = JArrayHelper::toObject($properties, 'JObject');

                        // Here, we need to remove all files already associated with this media item
                        hwdMediaShareFactory::load('files');
                        $HWDfiles = hwdMediaShareFiles::getInstance();
                        $HWDfiles->deleteMediaFiles($replace);

                        //$post['id']                   = '';
                        //$post['asset_id']             = '';
                        //$post['ext_id']               = '';
                        //$post['media_type']           = '';
                        //$post['key']                  = '';
                        //$post['title']                = '';
                        //$post['alias']                = '';
                        //$post['description']          = '';
                        $post['type']                   = 8; // Stream
                        $post['source']                 = json_encode($sources);
                        $post['storage']                = '';
                        //$post['duration']             = '';
                        $post['streamer']               = '';
                        $post['file']                   = '';
                        $post['embed_code']             = '';
                        //$post['thumbnail']            = '';
                        //$post['thumbnail_ext_id']     = '';
                        //$post['location']             = '';
                        //$post['viewed']               = '';
                        //$post['private']              = '';
                        //$post['likes']                = '';
                        //$post['dislikes']             = '';
                        //$post['status']               = '';
                        //$post['published']            = '';
                        //$post['featured']             = '';
                        //$post['checked_out']          = '';
                        //$post['checked_out_time']     = '';
                        //$post['access']               = '';
                        //$post['download']             = '';
                        //$post['params']               = '';
                        //$post['ordering']             = '';
                        //$post['created_user_id']      = '';
                        //$post['created_user_id_alias']= '';
                        //$post['created']              = '';
                        //$post['publish_up']           = '';
                        //$post['publish_down']         = '';
                        $post['modified_user_id']       = $user->id;
                        $post['modified']               = $date->toSql();
                        //$post['hits']                 = '';
                        //$post['language']             = '';              
                }
                else
                {
                        //$post['id']                   = '';
                        //$post['asset_id']             = '';
                        //$post['ext_id']               = '';
                        //$post['media_type']           = '';
                        $post['key']                    = $key;
                        $post['title']                  = (isset($data['title']) ? $data['title'] : JText::_('COM_HWDMS_STREAMED_MEDIA'));
                        $post['alias']                  = (isset($data['alias']) ? JFilterOutput::stringURLSafe($data['alias']) : JFilterOutput::stringURLSafe($post['title']));
                        $post['description']            = (isset($data['description']) ? $data['description'] : '');
                        $post['type']                   = 8; // Stream
                        $post['source']                 = json_encode($sources);
                        $post['storage']                = '';
                        //$post['duration']             = '';
                        $post['streamer']               = '';
                        $post['file']                   = '';
                        $post['embed_code']             = '';
                        //$post['thumbnail']            = '';
                        //$post['thumbnail_ext_id']     = '';
                        //$post['location']             = '';
                        //$post['viewed']               = '';
                        //$post['private']              = '';
                        //$post['likes']                = '';
                        //$post['dislikes']             = '';
                        $post['status']                 = $status;
                        $post['published']              = (isset($data['published']) ? $data['published'] : 1);
                        $post['featured']               = (isset($data['featured']) ? $data['featured'] : 0);
                        //$post['checked_out']          = '';
                        //$post['checked_out_time']     = '';
                        $post['access']                 = (isset($data['access']) ? $data['access'] : $config->get('default_access', 1));
                        $post['download']               = $config->get('default_download', 1);
                        //$post['params']               = '';
                        //$post['ordering']             = '';
                        $post['created_user_id']        = $user->id;
                        //$post['created_user_id_alias']= '';
                        $post['created']                = $date->toSql();
                        $post['publish_up']             = $date->toSql();
                        $post['publish_down']           = '0000-00-00 00:00:00';
                        $post['modified_user_id']       = $user->id;
                        $post['modified']               = $date->toSql();
                        $post['hits']                   = 0;
                        $post['language']               = (isset($data['language']) ? $data['language'] : '*');
                }

                // Save the data to the database.
                if (!$table->save($post))
                {
                        $this->setError($table->getError());
                        return false; 
                }

                $properties = $table->getProperties(1);
                $this->_item = JArrayHelper::toObject($properties, 'JObject');

                return true;
        }
        
	/**
	 * Method to render an rtmp stream in a player.
         * 
         * @access  public
         * @static
         * @param   object  $item  The object holding the media details.
         * @return  boolean True on success.
	 */
	public static function display($item)
	{
                // Load HWD config.
                $hwdms = hwdMediaShareFactory::getInstance();
                $config = $hwdms->getConfig();
                
                // Load HWD utilities.
                hwdMediaShareFactory::load('utilities');
                $utilities = hwdMediaShareUtilities::getInstance();

                // Check for cloudfront services.
                if (strpos($item->streamer, '.cloudfront.net') !== false)
                {
                        hwdMediaShareFactory::load('aws.cloudfront');
                        $player = call_user_func(array('hwdMediaShareCloudfront', 'getInstance'));
                        $item->file = urldecode($player->update_stream_name($item));
                }

                // Decode stream data.
                $sources = json_decode($item->source, false);  
                if (is_array($sources) && count($sources))
                {
                        $pluginClass = 'plgHwdmediashare'.$config->get('media_player');
                        $pluginPath = JPATH_ROOT.'/plugins/hwdmediashare/'.$config->get('media_player').'/'.$config->get('media_player').'.php';

                        // Import HWD player plugin.
                        if (file_exists($pluginPath))
                        {
                                JLoader::register($pluginClass, $pluginPath);
                                $HWDplayer = call_user_func(array($pluginClass, 'getInstance'));

                                if ($player = $HWDplayer->getStreamPlayer($item, $sources))
                                {
                                        return $player;
                                }
                                else
                                {
                                        return $utilities->printNotice($HWDplayer->getError(), '', 'info', true);
                                }
                        }
                }
	} 
        
	/**
	 * Method to get human readable media type
         * 
         * @since   0.1
	 **/
	public static function getStreamType($stream)
	{
                switch ($stream->type) 
                {
                        case 1:
                                return JText::_('COM_HWDMS_STREAMTYPE_RTMP');
                        break;
                        case 2:
                                return JText::_('COM_HWDMS_STREAMTYPE_HLS');
                        break;
                        case 3:
                                return JText::_('COM_HWDMS_STREAMTYPE_MP4');
                        break;                     
                }
	}  
        
        /**
	 * Method to parse an RTMP stream into the application and the stream.
         * 
	 * @access  public
         * @static
	 * @param   string  $stream  The RTMP stream.
	 * @return  object  The RTMP application and stream.
	 **/
	public static function parseRtmpStream($stream)
	{
		// Initialise variables.
                $app = JFactory::getApplication();
                $doc = JFactory::getDocument();

                $pos = strpos($stream, 'mp4:');
                if ($pos !== false)
                {
                        $return = new StdClass;
                        $return->application = substr($stream, 0, $pos);
                        $return->stream = substr($stream, $pos);
                        return $return;
                }
                
                $pos = strpos($stream, 'flv:');
                if ($pos !== false)
                {
                        $return = new StdClass;
                        $return->application = substr($stream, 0, $pos);
                        $return->stream = substr($stream, $pos);
                        return $return;
                }  

                $pos = strpos($stream, 'mp3:');
                if ($pos !== false)
                {
                        $return = new StdClass;
                        $return->application = substr($stream, 0, $pos);
                        $return->stream = substr($stream, $pos);
                        return $return;
                } 
                
                $pos = strpos($stream, '/');
                if ($pos !== false)
                {
                        $return = new StdClass;
                        $return->application = substr($stream, 0, strrpos($stream, '/' ) + 1);
                        $return->stream = substr($stream, strrpos($stream, '/' ) + 1);
                        return $return;
                } 
        }   
}

<?php
/**
 * @package     Joomla.site
 * @subpackage  Component.hwdmediashare
 *
 * @copyright   Copyright (C) 2013 Highwood Design Limited. All rights reserved.
 * @license     GNU General Public License http://www.gnu.org/copyleft/gpl.html
 * @author      Dave Horsfall
 * @see         https://github.com/justquick/django-activity-stream
 */

defined('_JEXEC') or die;

class hwdMediaShareAPI extends JObject
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
	 * Returns the hwdMediaShareAPI object, only creating it if it
	 * doesn't already exist.
	 *
	 * @access  public
         * @static
	 * @return  hwdMediaShareAPI Object.
	 */
	public static function getInstance()
	{
		static $instance;

		if (!isset ($instance))
                {
			$c = 'hwdMediaShareAPI';
                        $instance = new $c;
		}

		return $instance;
	}
        
	/**
	 * Method to add a media file from the server.
         * 
         * @access  public
         * @param   string   $source  The server location of the source file.
         * @param   object   $data    JReg object containing information to bind to the media.
         * @return  boolean  True on success.
	 */
	public function addMediaFromServer($source, $data)
	{
                // Initialise variables.            
                $db = JFactory::getDBO();
                $user = JFactory::getUser();
                $app = JFactory::getApplication();
                $date = JFactory::getDate();
                
                // Load HWD assets.
                JLoader::register('hwdMediaShareFactory', JPATH_ROOT.'/components/com_hwdmediashare/libraries/factory.php');
                JLoader::register('hwdMediaShareHelperRoute', JPATH_ROOT.'/components/com_hwdmediashare/helpers/route.php');

                // Include JHtml helpers.
                JHtml::addIncludePath(JPATH_ROOT.'/administrator/components/com_hwdmediashare/helpers/html');
                JHtml::addIncludePath(JPATH_ROOT.'/components/com_hwdmediashare/helpers/html');
                
                // Import HWD libraries.                
                hwdMediaShareFactory::load('activities');
                hwdMediaShareFactory::load('category');
                hwdMediaShareFactory::load('downloads');
                hwdMediaShareFactory::load('files');
                hwdMediaShareFactory::load('media');
                hwdMediaShareFactory::load('thumbnails');
		hwdMediaShareFactory::load('upload');
		hwdMediaShareFactory::load('utilities');

                // Load HWD config, merge with module parameters (and force reset).
                $hwdms = hwdMediaShareFactory::getInstance();
                $config = $hwdms->getConfig(null, true);

                // Load HWD utilities.
                hwdMediaShareFactory::load('utilities');
                $utilities = hwdMediaShareUtilities::getInstance();
                
                // Check the source file exists.
                if (empty($source) || !file_exists($source))
                {
                        $this->setError(JText::_('COM_HWDMS_ERROR_PHP_UPLOAD_ERROR'));
                        return false;
                }
                        
                // Retrieve file details.
                $ext = strtolower(JFile::getExt($source));

                // Check if the file has an allowed extension.
                $query = $db->getQuery(true)
                        ->select('id')
                        ->from('#__hwdms_ext')
                        ->where($db->quoteName('ext') . ' = ' . $db->quote($ext))
                        ->where($db->quoteName('published') . ' = ' . $db->quote(1));
                try
                {
                        $db->setQuery($query);
                        $db->execute(); 
                        $ext_id = $db->loadResult();                   
                }
                catch (RuntimeException $e)
                {
                        $this->setError($e->getMessage());
                        return false;                            
                }
                        
                if ($ext_id > 0)
                {
                        $maxUploadFileSize = $config->get('max_upload_filesize', 30) * 1024 * 1024;                       
                        if (filesize($source) > $maxUploadFileSize)
                        {
                                $this->setError(JText::sprintf('COM_HWDMS_FILE_N_EXCEEDS_THE_MAX_UPLOAD_LIMIT', basename($source)));
                                return false;                            
                        }

                        // Define a key so we can copy the file into the storage directory.
                        if (!$key = $utilities->generateKey(1))
                        {
                                $this->setError($utilities->getError());
                                return false;
                        }  

                        if (empty($source) || empty($ext) || !file_exists($source))
                        {
                                $this->setError(JText::_('COM_HWDMS_ERROR_PHP_UPLOAD_ERROR'));
                                return false;
                        }
                        else
                        {
                                hwdMediaShareFiles::getLocalStoragePath();

                                $folders = hwdMediaShareFiles::getFolders($key);
                                hwdMediaShareFiles::setupFolders($folders);

                                // Get the filename.
                                $filename = hwdMediaShareFiles::getFilename($key, 1);

                                // Get the destination location, and copy the uploaded file.
                                $dest = hwdMediaShareFiles::getPath($folders, $filename, $ext);
                                if (!JFile::copy($source, $dest))
                                {
                                        $this->setError(JText::_('COM_HWDMS_ERROR_FILE_COULD_NOT_BE_COPIED_TO_UPLOAD_DIRECTORY'));
                                        return false;
                                }

                                // Set approved/pending.
                                (!defined('_JCLI') && !$app->isAdmin() && $config->get('approve_new_media')) == 1 ? $status = 2 : $status = 1; 
                                $config->get('approve_new_media') == 1 ? $status = 2 : $status = 1; 

                                // Check encoding of original filename to prevent problems in the title.
                                if(mb_detect_encoding($source, 'iso-8859-1', true))
                                {
                                        $title = hwdMediaShareUpload::removeExtension(basename(utf8_encode($source)));
                                }
                                else
                                {
                                        $title = hwdMediaShareUpload::removeExtension(basename($source));
                                }
                                
                                JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_hwdmediashare/tables');
                                $table = JTable::getInstance('media', 'hwdMediaShareTable');

                                $post                          = array();
                                $post['key']                   = $key;
                                $post['media_type']            = '';
                                $post['title']                 = $data->get('title', $title);
                                $post['alias']                 = JFilterOutput::stringURLSafe($post['title']);
                                $post['ext_id']                = $ext_id;
                                $post['description']           = $data->get('description');
                                $post['type']                  = 1; // Local.
                                $post['status']                = $status;
                                $post['published']             = $data->get('published', 1);
                                $post['featured']              = $data->get('featured', 0);
                                $post['access']                = $data->get('access', $config->get('default_access', 1));
                                $post['download']              = $data->get('download', $config->get('default_download', 1));
                                $post['created_user_id']       = $data->get('created_user_id', $user->id);
                                $post['created_user_id_alias'] = $data->get('created_user_id', '');
                                $post['created']               = $data->get('created', $date->toSql());
                                $post['publish_up']            = $data->get('publish_up', $date->toSql());
                                $post['publish_down']          = $data->get('publish_down', '0000-00-00 00:00:00');
                                $post['hits']                  = $data->get('hits', 0);
                                $post['language']              = $data->get('language', '*');

                                // Save data to the database.
                                if (!$table->save($post))
                                {
                                        $this->setError($row->getError());
                                        return false;
                                }                                         
                        }

                        $properties = $table->getProperties(1);
                        $this->_item = JArrayHelper::toObject($properties, 'JObject');

                        hwdMediaShareFactory::load('files');
                        $HWDfiles = hwdMediaShareFiles::getInstance();
                        $HWDfiles->addFile($this->_item, 1);

                        hwdMediaShareUpload::addProcesses($this->_item);
                }
                else
                {
                        $this->setError(JText::_('COM_HWDMS_ERROR_EXTENSION_NOT_ALLOWED'));
                        return false;
                }     

                return true;
	}

	/**
	 * Method to get a media item.
         * 
         * @access  public
         * @param   integer   $id  The iD of the media to get.
         * @return  object    The media item.
	 */
	public function getMedia($id)
	{
                // Initialise variables.            
                $db = JFactory::getDBO();
                $user = JFactory::getUser();
                $app = JFactory::getApplication();
                $date = JFactory::getDate();
                
                // Load HWD assets.
                JLoader::register('hwdMediaShareFactory', JPATH_ROOT.'/components/com_hwdmediashare/libraries/factory.php');
                JLoader::register('hwdMediaShareHelperRoute', JPATH_ROOT.'/components/com_hwdmediashare/helpers/route.php');
                JLoader::register('hwdMediaShareHelperNavigation', JPATH_ROOT.'/components/com_hwdmediashare/helpers/navigation.php');

                // Include JHtml helpers.
                JHtml::addIncludePath(JPATH_ROOT.'/administrator/components/com_hwdmediashare/helpers/html');
                JHtml::addIncludePath(JPATH_ROOT.'/components/com_hwdmediashare/helpers/html');
                
                JModelLegacy::addIncludePath(JPATH_ROOT.'/components/com_hwdmediashare/models');
                $model = JModelLegacy::getInstance('MediaItem', 'hwdMediaShareModel', array('ignore_request' => true));
                $model->populateState();
                $model->setState('media.id', (int) 1030);
                $this->_item = $model->getItem();
                var_dump($this->_item);
                exit;
	}        
}

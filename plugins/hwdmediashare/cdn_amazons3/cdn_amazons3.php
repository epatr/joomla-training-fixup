<?php
/**
 * @package     Joomla.site
 * @subpackage  Plugin.hwdmediashare.cdn_amazons3
 *
 * @copyright   Copyright (C) 2013 Highwood Design Limited. All rights reserved.
 * @license     GNU General Public License http://www.gnu.org/copyleft/gpl.html
 * @author      Dave Horsfall
 */

defined('_JEXEC') or die;

// Include the AWS SDK : https://github.com/awslabs/aws-php-sample/blob/master/sample.php
// require 'autoload.php';
// use Aws\S3\S3Client;

class plgHwdmediashareCdn_amazons3 extends JObject
{
	/**
	 * Class constructor.
	 *
	 * @access  public
         * @return  void
	 */
	public function __construct()
	{
		parent::__construct();
	}
        
	/**
	 * Returns the plgHwdmediashareCdn_amazons3 object, only creating it if it
	 * doesn't already exist.
	 *
	 * @access  public
	 * @return  object  The plgHwdmediashareCdn_amazons3 object.
	 */
	public static function getInstance()
	{
		static $instance;

		if (!isset ($instance))
                {
			$c = 'plgHwdmediashareCdn_amazons3';
                        $instance = new $c;
		}

		return $instance;
	}

	/**
	 * Perfoms the transfer of local media to the remote CDN.
         * 
         * @access  public
	 * @return  void
	 */
	public function maintenance()
	{
		// Initialise variables.
                $app = JFactory::getApplication();
                $db = JFactory::getDBO();

                // Load plugin.
		$plugin = JPluginHelper::getPlugin('hwdmediashare', 'cdn_amazons3');
		
                // Load the language file.
                $lang = JFactory::getLanguage();
                $lang->load('plg_hwdmediashare_cdn_amazons3', JPATH_ADMINISTRATOR, $lang->getTag());

                // Setup logger.
                JLog::addLogger(array('text_file' => 'hwd.log.php'), JLog::ALL, array('plg_hwdmediashare_cdn_amazons3'));           
                
                if (!$plugin)
                {
                        JLog::add(JText::_('PLG_HWDMEDIASHARE_CDN_AMAZONS3_ERROR_NOT_PUBLISHED'), JLog::CRITICAL, 'plg_hwdmediashare_cdn_amazons3');
                        return false;
                }
                
                // Load parameters.
                $params = new JRegistry($plugin->params);

                if (class_exists('hwdS3'))
                {
                        JLog::add(JText::_('PLG_HWDMEDIASHARE_CDN_AMAZONS3_ERROR_S3_CLASS_ALREADY_LOADED'), JLog::CRITICAL, 'plg_hwdmediashare_cdn_amazons3');
                        return false;
                }

                // Require Amazon S3 PHP class.
                JLoader::register('hwdS3', JPATH_BASE.'/plugins/hwdmediashare/cdn_amazons3/assets/S3.php');

		// AWS access info.
                $awsAccessKey       = trim($params->get('awsAccessKey', ''));
                $awsSecretKey       = trim($params->get('awsSecretKey', ''));
		$bucketName         = trim($params->get('awsBucket', ''));
		$reducedRedundancy  = $params->get('awsRrs' , 0);
		$location           = $params->get('awsRegion', 'us-west-1');

		// marpada-S

                $endpoint = 's3.amazonaws.com';
		switch ($location)
                {
			case "us-west-1":
				$endpoint='s3-us-west-1.amazonaws.com';
				break;
			case "us-west-2":
				$endpoint='s3-us-west-2.amazonaws.com';
				break;                            
			case "EU":
				$endpoint='s3-eu-west-1.amazonaws.com';
				break;
			case "ap-southeast-1":
				$endpoint="s3-ap-southeast-1.amazonaws.com";
				break;
			case "ap-northeast-1":
				$endpoint="s3-ap-northeast-1.amazonaws.com";
				break;
			default:
				$endpoint='s3.amazonaws.com';
		}

		// Windows curl extension has trouble with SSL connections, so we won't use it.
		if (substr(PHP_OS, 0, 3) == "WIN")
                {
			$useSSL = 0;
		}
		else
                {
			$useSSL = 1;
		}

		// marpada-E

		// Check for CURL.
		if (!extension_loaded('curl') && !@dl(PHP_SHLIB_SUFFIX == 'so' ? 'curl.so' : 'php_curl.dll')) 
                {
                        JLog::add(JText::_('PLG_HWDMEDIASHARE_CDN_AMAZONS3_ERROR_CURL_EXT_NOT_LOADED'), JLog::CRITICAL, 'plg_hwdmediashare_cdn_amazons3');
                        return false;
                }

		// Pointless without keys!
		if ($awsAccessKey == '' || $awsSecretKey == '')
                {
                        JLog::add(JText::_('PLG_HWDMEDIASHARE_CDN_AMAZONS3_ERROR_AWS_CREDENTIALS_MISSING'), JLog::CRITICAL, 'plg_hwdmediashare_cdn_amazons3');
                        return false;
                }

                // Instantiate the class.
		$s3 = new hwdS3($awsAccessKey, $awsSecretKey, $useSSL, $endpoint);

                // Set exception usage.
                $s3->setExceptions();
                
                if ($reducedRedundancy)
                {
			$storage = hwdS3::STORAGE_CLASS_RRS ;
		}
		else
                {
			$storage = hwdS3::STORAGE_CLASS_STANDARD ;
		}

                // Check if bucket exists and if it belongs to the defaut region.
                try
                {
                        $bucketlocation = $s3->getBucketLocation($bucketName);
                }
                catch(Exception $e)
                {
                        JLog::add($e->getMessage(), JLog::NOTICE, 'plg_hwdmediashare_cdn_amazons3');
                }
		
		if ($bucketlocation && ($bucketlocation <> $location))
                {
                        JLog::add(JText::sprintf('PLG_HWDMEDIASHARE_CDN_AMAZONS3_LOG_BUCKET_ALREADY_EXISTS_IN_REGION', $bucketlocation), JLog::NOTICE, 'plg_hwdmediashare_cdn_amazons3');
                        
                        // Redefine the location.
			$location = $bucketlocation;
			switch ($location)
                        {
                                case "us-west-1":
                                        $s3->setEndpoint('s3-us-west-1.amazonaws.com');
                                        break;
                                case "us-west-2":
                                        $s3->setEndpoint('s3-us-west-2.amazonaws.com');
                                        break;                                    
                                case "EU":
                                        $s3->setEndpoint('s3-eu-west-1.amazonaws.com');
                                        break;
                                case "ap-southeast-1":
                                        $s3->setEndpoint('s3-ap-southeast-1.amazonaws.com');
                                        break;
                                case "ap-northeast-1":
                                        $s3->setEndpoint('s3-ap-northeast-1.amazonaws.com');
                                        break;
                                default:
                                        $s3->setEndpoint('s3.amazonaws.com');
			}
                }

		if (!$bucketlocation)
                {
                        try
                        {
                                // Create a bucket with public read access
                                $s3->putBucket($bucketName, hwdS3::ACL_PUBLIC_READ, $location); 
                        }
                        catch(Exception $e)
                        {
                                JLog::add($e->getMessage(), JLog::CRITICAL, 'plg_hwdmediashare_cdn_amazons3');
                                return false;
                        }
                }
                
                try
                {
                        // Get the contents of our bucket
                        $cdnContents = $s3->getBucket($bucketName);
                }
                catch(Exception $e)
                {
                        JLog::add($e->getMessage(), JLog::CRITICAL, 'plg_hwdmediashare_cdn_amazons3');
                        return false;
                }

                // Load HWD files library.
                hwdMediaShareFactory::load('files');
                hwdMediaShareFiles::getLocalStoragePath();

                // Get local queue.
                if ($queued = $this->getLocalQueue())
                {                
                        JLog::add(JText::sprintf('PLG_HWDMEDIASHARE_CDN_AMAZONS3_LOG_PROCESSING_X_MEDIA', count($queued)), JLog::NOTICE, 'plg_hwdmediashare_cdn_amazons3');

                        foreach ($queued as $media)
                        {
                                // Define flag to track errors with this media.
                                $errors = false;

                                $query = $db->getQuery(true)
                                        ->select('1')
                                        ->from('#__hwdms_processes')
                                        ->where('media_id = ' . $db->quote($media->id))
                                        ->where('(status = ' . $db->quote(1) . ' || status = ' . $db->quote(3) . ')');
                                try
                                {                
                                        $db->setQuery($query);
                                        $result = $db->loadResult();
                                }
                                catch (Exception $e)
                                {
                                        JLog::add($e->getMessage(), JLog::CRITICAL, 'plg_hwdmediashare_cdn_amazons3');
                                        return false;
                                }

                                if ($result)
                                {
                                        JLog::add(JText::sprintf('PLG_HWDMEDIASHARE_CDN_AMAZONS3_ERROR_MEDIA_X_HAS_QUEUED_PROCESSES', $media->id), JLog::WARNING, 'plg_hwdmediashare_cdn_amazons3');
                                        $errors = true;
                                        continue;                                
                                } 

                                // Get files for local media item.
                                $HWDfiles = hwdMediaShareFiles::getInstance();
                                $files = $HWDfiles->getMediaFiles($media);

                                // Check files exist for the item.
                                if (count($files) == 0)
                                {
                                        JLog::add(JText::sprintf('PLG_HWDMEDIASHARE_CDN_AMAZONS3_ERROR_MEDIA_X_HAS_NO_MEDIA_FILES', $media->id), JLog::WARNING, 'plg_hwdmediashare_cdn_amazons3');
                                        $errors = true;
                                        continue; 
                                }

                                // Transfer each file.
                                foreach ($files as $file)
                                {
                                        $folders = hwdMediaShareFiles::getFolders($media->key);
                                        $filename = hwdMediaShareFiles::getFilename($media->key, $file->file_type);
                                        $ext = hwdMediaShareFiles::getExtension($media, $file->file_type);
                                        $relativePath = hwdMediaShareFiles::getPath($folders, $filename, $ext, false);
                                        $path = hwdMediaShareFiles::getPath($folders, $filename, $ext);

                                        // Check local file exists.
                                        if (!file_exists($path))
                                        {
                                                JLog::add(JText::sprintf('PLG_HWDMEDIASHARE_CDN_AMAZONS3_ERROR_MEDIA_FILE_X_FILE_DOES_NOT_EXIST', $media->id, $path), JLog::WARNING, 'plg_hwdmediashare_cdn_amazons3');
                                                $errors = true;
                                                continue; 
                                        }

                                        // Check local file below limit (might struggle to transfer large files before timeout).
                                        if (filesize($path) > 1073741824)
                                        {
                                                JLog::add(JText::sprintf('PLG_HWDMEDIASHARE_CDN_AMAZONS3_ERROR_MEDIA_FILE_X_FILE_OVER_LIMIT', $media->id, $path), JLog::WARNING, 'plg_hwdmediashare_cdn_amazons3');
                                                $errors = true;
                                                continue; 
                                        }
                                        elseif (filesize($path) < 1)
                                        {
                                                JLog::add(JText::sprintf('PLG_HWDMEDIASHARE_CDN_AMAZONS3_ERROR_MEDIA_FILE_X_FILESIZE_ZERO', $media->id, $path), JLog::WARNING, 'plg_hwdmediashare_cdn_amazons3');
                                                $errors = true;
                                                continue; 
                                        }
                                        
                                        // Check if file already exists on CDN with same filesize.
                                        if (isset($cdnContents[$relativePath]))
                                        {
                                                if ($cdnContents[$relativePath]['size'] == filesize($path) )
                                                {
                                                        JLog::add(JText::sprintf('PLG_HWDMEDIASHARE_CDN_AMAZONS3_LOG_MEDIA_FILE_X_FILE_EXISTS_CDN', $media->id, $relativePath), JLog::NOTICE, 'plg_hwdmediashare_cdn_amazons3');
                                                        continue;
                                                }
                                                else
                                                {
                                                        JLog::add(JText::sprintf('PLG_HWDMEDIASHARE_CDN_AMAZONS3_LOG_MEDIA_FILE_X_FILE_EXISTS_CDN_DIFF', $media->id, $relativePath), JLog::NOTICE, 'plg_hwdmediashare_cdn_amazons3');
                                                }
                                        }

                                        try
                                        {
                                                // Put our file (also with public read access)
                                                $transfer = $s3->putObject($s3->inputFile($path,false), $bucketName, $relativePath, hwdS3::ACL_PUBLIC_READ, array(), array(), $storage);
                                        }
                                        catch(Exception $e)
                                        {
                                                JLog::add($e->getMessage(), JLog::CRITICAL, 'plg_hwdmediashare_cdn_amazons3');
                                                return false;
                                        }

                                        if ($transfer)
                                        {
                                                JLog::add(JText::sprintf('PLG_HWDMEDIASHARE_CDN_AMAZONS3_LOG_MEDIA_FILE_X_FILE_TRANSFER_SUCCESS', $media->id, $relativePath), JLog::NOTICE, 'plg_hwdmediashare_cdn_amazons3');
                                        }
                                        else
                                        {
                                                JLog::add(JText::sprintf('PLG_HWDMEDIASHARE_CDN_AMAZONS3_ERROR_MEDIA_FILE_X_FILE_TRANSFER_FAIL', $media->id, $relativePath), JLog::NOTICE, 'plg_hwdmediashare_cdn_amazons3');
                                                $errors = true;
                                                continue;
                                        }                                
                                }

                                // If no errors, and all local files exist on CDN then modify database so media
                                // is marked as CDN, delete all local files
                                if (!$errors)
                                {
                                        // Create an object for updating the record.
                                        $object = new stdClass();
                                        $object->id = $media->id;
                                        $object->type = 5;
                                        $object->storage = 'cdn_amazons3';
                                        $object->access = $media->access;

                                        try
                                        {                
                                                $result = $db->updateObject('#__hwdms_media', $object, 'id');
                                        }
                                        catch (Exception $e)
                                        {
                                                $this->setError($e->getMessage());
                                                return false;
                                        }                

                                        // Now that we have copied all the files for the media, and updated the database,
                                        // we can remove all the local media files
                                        foreach ($files as $file)
                                        {          
                                                jimport( 'joomla.filesystem.file' );

                                                $folders = hwdMediaShareFiles::getFolders($media->key);
                                                $filename = hwdMediaShareFiles::getFilename($media->key, $file->file_type);
                                                $ext = hwdMediaShareFiles::getExtension($media, $file->file_type);
                                                $relativePath = hwdMediaShareFiles::getPath($folders, $filename, $ext, false);
                                                $path = hwdMediaShareFiles::getPath($folders, $filename, $ext);

                                                if (JFile::delete($path))
                                                {
                                                        JLog::add(JText::sprintf('PLG_HWDMEDIASHARE_CDN_AMAZONS3_LOG_MEDIA_FILE_X_LOCAL_DELETE_SUCCESS', $media->id, $relativePath), JLog::NOTICE, 'plg_hwdmediashare_cdn_amazons3');
                                                }
                                                else
                                                {
                                                        JLog::add(JText::sprintf('PLG_HWDMEDIASHARE_CDN_AMAZONS3_LOG_MEDIA_FILE_X_LOCAL_DELETE_FAIL', $media->id, $relativePath), JLog::NOTICE, 'plg_hwdmediashare_cdn_amazons3');
                                                }
                                        }
                                }
                        }
                }
	}

        /**
	 * Get local media to be transferred to the CDN.
         * 
         * @access  public
         * @return  mixed   Array of media objects on success, false on fail.
	 **/
	public function getLocalQueue()
	{
                // Initialise variables.
                $db = JFactory::getDBO();

                $query = $db->getQuery(true)
                        ->select('*')
                        ->from('#__hwdms_media')
                        ->where('type = ' . $db->quote(1))
                        ->where('status = ' . $db->quote(1))
                        ->order('created ASC');
                try
                {                
                        $db->setQuery($query);
                        $result = $db->loadObjectList();
                }
                catch (Exception $e)
                {
                        $this->setError($e->getMessage());
                        return false;
                }
                
		if ($result)
		{
                        return $result;
		}
        }

        /**
	 * Get the CDN location.
         * 
         * @access  public
         * @return  void
	 **/
	public function getCdnLocation()
	{
        }

        /**
	 * Create the CDN location.
         * 
         * @access  public
         * @return  void
	 **/
	public function createCdnLocation()
	{
        }

        /**
	 * Get the CDN contents.
         * 
         * @access  public
         * @return  void
	 **/
	public function getCdnContents()
	{
        }

        /**
	 * Put a file to the CDN.
         * 
         * @access  public
         * @return  void
	 **/
	public function putFile()
	{
        }

        /**
	 * Method to get CDN file details.
         * 
	 * @access  public
	 * @param   object  $media      The media object.
	 * @param   array   $fileTypes  The array of file types to be requested. First successful match returned.
	 * @return  object  The file object
	 **/
	public function getFile($media, $fileTypes = array())
	{
		// Initialise variables.
                $app = JFactory::getApplication();

                // Load plugin.
		$plugin = JPluginHelper::getPlugin('hwdmediashare', 'cdn_amazons3');
		
                // Load the language file.
                $lang = JFactory::getLanguage();
                $lang->load('plg_hwdmediashare_cdn_amazons3', JPATH_ADMINISTRATOR, $lang->getTag());

                if (!$plugin)
                {
                        $this->setError(JText::_('PLG_HWDMEDIASHARE_CDN_AMAZONS3_ERROR_NOT_PUBLISHED'));
                        return false;
                }

                // Load parameters.
                $params = new JRegistry($plugin->params);

                // Load HWD libraries.
                hwdMediaShareFactory::load('documents');
                hwdMediaShareFactory::load('files');

		// AWS information.
		$bucketName         = $params->get('awsBucket', '');
		$http_cloud         = $params->get('http_cloud', '');
		$http_url           = $params->get('http_url', '');
		$rtmp_cloud         = $params->get('rtmp_cloud', '');
		$rtmp_url           = $params->get('rtmp_url', '');

                if (!empty($bucketAlias))
                {
                        $baseUrl  = $bucketAlias;
                }
                else
                {
                        $baseUrl  = 'http://' . $bucketName . '.s3.amazonaws.com';
                }

                if ($http_cloud && !empty($http_url))
                {
                        $baseUrl = strpos($http_url,'http') === 0 ? '' : 'http://';
                        $baseUrl.= $http_url;
                        $baseUrl = rtrim($baseUrl, "/");
                }

                // If user has requested that a streaming cloudfront distribution is used, then set the streamer for the item 
                // if ($rtmp_cloud && !empty($rtmp_cloud)) $media->streamer = $rtmp_url;

                // Get media files.
                hwdMediaShareFiles::getLocalStoragePath();
                $hwdmsFiles = hwdMediaShareFiles::getInstance();
                $files = $hwdmsFiles->getMediaFiles($media);
                
                // Define available files.
		$available = array();
                foreach ($files as $file)
                {
                        $available[$file->file_type] = $file;
                }

                // Loop requested file types and return the first positive match.
                foreach ($fileTypes as $fileType)
                {
                        if (isset($available[$fileType]))
                        {
                                $folders = hwdMediaShareFiles::getFolders($media->key);
                                $filename = hwdMediaShareFiles::getFilename($media->key, $fileType);
                                $ext = hwdMediaShareFiles::getExtension($media, $fileType);

                                $relativePath = hwdMediaShareFiles::getPath($folders, $filename, $ext, false);

                                // If user has requested that a streaming cloudfront distribution is used, and we are serving a Flash compatible file, then set the file for the item 
                                if (in_array($fileType, array(11,12,13,14,15,16,17)) && $rtmp_cloud && !empty($rtmp_cloud)) $media->file = $relativePath;

                                // Create file object.
                                $file = new JObject;
                                $file->local = false;
                                $file->path = '';
                                $file->url = $baseUrl.'/'.$relativePath;
                                $file->size = $available[$fileType]->size;
                                $file->ext = $ext;
                                $file->type = hwdMediaShareDocuments::getContentType($ext);

                                return $file;
                        }
                }

                return false;
        }

        /**
	 * Method to get thumbnail of CDN media item.
         * 
	 * @access  public
	 * @param   object  $media      The media object.
	 * @return  object  The file object
         **/
	public function getThumbnail($media)
	{
		// Initialise variables.
                $app = JFactory::getApplication();

                // Load plugin.
		$plugin = JPluginHelper::getPlugin('hwdmediashare', 'cdn_amazons3');
		
                // Load the language file.
                $lang = JFactory::getLanguage();
                $lang->load('plg_hwdmediashare_cdn_amazons3', JPATH_ADMINISTRATOR, $lang->getTag());

                if (!$plugin)
                {
                        $this->setError(JText::_('PLG_HWDMEDIASHARE_CDN_AMAZONS3_ERROR_NOT_PUBLISHED'));
                        return false;
                }

                // Load parameters.
                $params = new JRegistry($plugin->params);
                
                // Load HWD libraries.
                hwdMediaShareFactory::load('documents');
                hwdMediaShareFactory::load('files');

		// AWS information.
		$bucketName         = trim($params->get('awsBucket', ''));
		$http_cloud         = $params->get('http_cloud', '');
		$http_url           = $params->get('http_url', '');
		$rtmp_cloud         = $params->get('rtmp_cloud', '');
		$rtmp_url           = $params->get('rtmp_url', '');

                if (!empty($bucketAlias))
                {
                        $baseUrl  = $bucketAlias;
                }
                else
                {
                        $baseUrl  = 'http://' . $bucketName . '.s3.amazonaws.com';
                }

                if ($http_cloud && !empty($http_url))
                {
                        $baseUrl = strpos($http_url,'http') === 0 ? '' : 'http://';
                        $baseUrl.= $http_url;
                        $baseUrl = rtrim($baseUrl, "/");
                }

                // Get media files.
                hwdMediaShareFiles::getLocalStoragePath();
                $hwdmsFiles = hwdMediaShareFiles::getInstance();
                $files = $hwdmsFiles->getMediaFiles($media);
                
                // Define available files.
		$available = array();
                foreach ($files as $file)
                {
                        $available[$file->file_type] = $file;
                }

                // Search generated images.
                $images = array(7,6,5,4,3,10);
                foreach ($images as $image)
                {
                        if (isset($available[$image]))
                        {
                                $folders = hwdMediaShareFiles::getFolders($media->key);
                                $filename = hwdMediaShareFiles::getFilename($media->key, $image);
                                $ext = hwdMediaShareFiles::getExtension($media, $image);

                                $relativePath = hwdMediaShareFiles::getPath($folders, $filename, $ext, false);

                                // Create file object.
                                $file = new JObject;
                                $file->local = false;
                                $file->path = '';
                                $file->url = $baseUrl.'/'.$relativePath;
                                $file->size = $available[$image]->size;
                                $file->ext = $ext;
                                $file->type = hwdMediaShareDocuments::getContentType($ext);

                                return $file;
                        }
                }

                // Is original a native image format?
                if (isset($available[1]))
                {
                        $folders = hwdMediaShareFiles::getFolders($media->key);
                        $filename = hwdMediaShareFiles::getFilename($media->key, 1);
                        $ext = hwdMediaShareFiles::getExtension($media, 1);                            
                        hwdMediaShareFactory::load('images');
                        if (hwdMediaShareImages::isNativeImage($ext))
                        {
                                $relativePath = hwdMediaShareFiles::getPath($folders, $filename, $ext, false);

                                // Create file object.
                                $file = new JObject;
                                $file->local = false;
                                $file->path = '';
                                $file->url = $baseUrl.'/'.$relativePath;
                                $file->size = $available[1]->size;
                                $file->ext = $ext;
                                $file->type = hwdMediaShareDocuments::getContentType($ext);

                                return $file;                                        
                        }
                }

                // We got nowt!
		return false;
        }
}
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

class hwdMediaShareModelMaintenance extends JModelLegacy
{
        /**
	 * Method to clean the category map. We remove empty maps, invalid maps 
         * and check language reference is correct.
         * 
         * @access  public
	 * @return  boolean  True if successful, false if an error occurs.
	 */
	public function cleanCategoryMap()
	{
                // Initialise variables.
                $db = JFactory::getDbo();
                $status = true;
                
                $query = $db->getQuery(true);

                $conditions = array(
                    $db->quoteName('element_type') . ' = ' . $db->quote(0),
                    $db->quoteName('element_id') . ' = ' . $db->quote(0),
                    $db->quoteName('category_id') . ' = ' . $db->quote(0)
                );

                $query->delete($db->quoteName('#__hwdms_category_map'));
                $query->where($conditions, 'OR');

                try
                {
                        $db->setQuery($query);
                        $result1 = $db->execute();                 
                }
                catch (RuntimeException $e)
                {
                        $this->setError($e->getMessage());
                        return false;                            
                }

                if (!$result1)
                {
                        $status = false;
                }
                
                // Load all mapped categories.
                $query = $db->getQuery(true)
                        ->select('map.*, category.language as category_language, media.id as media_id')
                        ->from('#__hwdms_category_map AS map')
                        ->join('LEFT', '#__categories AS category ON category.id = map.category_id')
                        ->join('LEFT', '#__hwdms_media AS media ON media.id = map.element_id')
                        ->where('element_type = ' . $db->quote(1));
                try
                {                
                        $db->setQuery($query);
                        $maps = $db->loadObjectList();
                }
                catch (Exception $e)
                {
                        $this->setError($e->getMessage());
                        return false;
                }
                
                foreach ($maps as $map)
                {
                        // Skip good maps.
                        if ($map->media_id > 0 && $map->language == $map->category_language)
                        {
                                continue;
                        }

                        // Media or category no longer exists.
                        if (!$map->media_id || !$map->category_language)
                        {
                                // Remove the category map.
                                $query = $db->getQuery(true);
                                $query->delete($db->quoteName('#__hwdms_category_map'));
                                $query->where($db->quoteName('id') . ' = ' . $db->quote($map->id));
                                try
                                {
                                        $db->setQuery($query);
                                        $result2 = $db->execute();
                                }
                                catch (Exception $e)
                                {
                                        $this->setError($e->getMessage());
                                        return false;
                                }
                                
                                if (!$result2)
                                {
                                        $status = false;
                                }
                                
                                // If media doesn't exist, no point in continuing.
                                continue;
                        }

                        // Language needs updating.
                        if ($map->language != $map->category_language)
                        {
                                $query = $db->getQuery(true);
                                $query->update($db->quoteName('#__hwdms_category_map'))
                                      ->set($db->quoteName('language') . ' = ' . $db->quote($map->category_language))
                                      ->where($db->quoteName('id') . ' = ' . $db->quote($map->id));
                                
                                try
                                {
                                        $db->setQuery($query);
                                        $result3 = $db->execute();
                                }
                                catch (Exception $e)
                                {
                                        $this->setError($e->getMessage());
                                        return false;
                                }
                                
                                if (!$result3)
                                {
                                        $status = false;
                                }                                
                        }                        
                }

                return $status;
        }
                
        /**
	 * Method to empty old upload tokens
         * 
         * @access  public
	 * @return  boolean  True if successful, false if an error occurs.
	 */
	public function emptyUploadTokens()
	{
                $db = JFactory::getDbo();

                $query = $db->getQuery(true);

                $conditions = array(
                    $db->quoteName('datetime') . ' < (NOW() - INTERVAL 10 MINUTE)'
                );

                $query->delete($db->quoteName('#__hwdms_upload_tokens'));
                $query->where($conditions);

                try
                {
                        $db->setQuery($query);
                        $result = $db->execute();                 
                }
                catch (RuntimeException $e)
                {
                        $this->setError($e->getMessage());
                        return false;                            
                }
                
                return $result;
        }    
        
        /**
	 * Method to purge old processes
         * 
         * @access  public
	 * @return  boolean  True if successful, false if an error occurs.
	 */
	public function purgeOldProcesses()
	{
                $db = JFactory::getDbo();

                $query = $db->getQuery(true);

                $conditions = array(
                    $db->quoteName('created') . ' < (NOW() - INTERVAL 90 DAY)'
                );

                $query->delete($db->quoteName('#__hwdms_process_log'));
                $query->where($conditions);

                try
                {
                        $db->setQuery($query);
                        $result = $db->execute();                 
                }
                catch (RuntimeException $e)
                {
                        $this->setError($e->getMessage());
                        return false;                            
                }
                
                return $result;
        }   
        
        /**
	 * Method to purge old processes
         * 
         * @access  public
	 * @return  boolean  True if successful, false if an error occurs.
	 */
	public function uninstallOldExtensions()
	{
                // Initialise variables.
                $app = JFactory::getApplication();
                $db = JFactory::getDbo();
                $installer = JInstaller::getInstance();
                
                // Find player_hwdjwplayer.
		$row = JTable::getInstance('extension');
		$eid = $row->find(array('type' => 'plugin', 'folder' => 'hwdmediashare', 'element' => 'player_hwdjwplayer'));
                if ($eid)
		{
                        $result = $installer->uninstall('plugin', $eid);               
                        if ($result === false)
                        {
                                $this->setError($installer->getError());
                                return false;                               
                        }
		}
                
                // Find player_flowplayerreloaded.
		$row = JTable::getInstance('extension');
		$eid = $row->find(array('type' => 'plugin', 'folder' => 'hwdmediashare', 'element' => 'player_flowplayerreloaded'));
                if ($eid)
		{
                        $result = $installer->uninstall('plugin', $eid);               
                        if ($result === false)
                        {
                                $this->setError($installer->getError());
                                return false;                               
                        }
		}
                
                // Find player_hwdflowplayer.
		$row = JTable::getInstance('extension');
		$eid = $row->find(array('type' => 'plugin', 'folder' => 'hwdmediashare', 'element' => 'player_hwdflowplayer'));
                if ($eid)
		{
                        $result = $installer->uninstall('plugin', $eid);               
                        if ($result === false)
                        {
                                $this->setError($installer->getError());
                                return false;                               
                        }
		}
                
                // Find player_bo_videojs.
		$row = JTable::getInstance('extension');
		$eid = $row->find(array('type' => 'plugin', 'folder' => 'hwdmediashare', 'element' => 'player_bo_videojs'));
                if ($eid)
		{
                        $result = $installer->uninstall('plugin', $eid);               
                        if ($result === false)
                        {
                                $this->setError($installer->getError());
                                return false;                               
                        }
		}
                
                // Find remote_videogooglecom.
		$row = JTable::getInstance('extension');
		$eid = $row->find(array('type' => 'plugin', 'folder' => 'hwdmediashare', 'element' => 'remote_videogooglecom'));
                if ($eid)
		{
                        $result = $installer->uninstall('plugin', $eid);               
                        if ($result === false)
                        {
                                $this->setError($installer->getError());
                                return false;                               
                        }
		}
                
                // Find remote_ukextremecom.
		$row = JTable::getInstance('extension');
		$eid = $row->find(array('type' => 'plugin', 'folder' => 'hwdmediashare', 'element' => 'remote_ukextremecom'));
                if ($eid)
		{
                        $result = $installer->uninstall('plugin', $eid);               
                        if ($result === false)
                        {
                                $this->setError($installer->getError());
                                return false;                               
                        }
		}
                
                // Find mod_media_tags.
		$row = JTable::getInstance('extension');
		$eid = $row->find(array('type' => 'module', 'element' => 'mod_media_tags'));
                if ($eid)
		{
                        $result = $installer->uninstall('module', $eid);               
                        if ($result === false)
                        {
                                $this->setError($installer->getError());
                                return false;                               
                        }
		}
                
                // Find legacy "hwdMediaShare Addons" package.
		$row = JTable::getInstance('extension');
		$eid = $row->find(array('type' => 'package', 'element' => 'pkg_hwdmediashare'));
                if ($eid)
		{
                        $query = $db->getQuery(true);

                        $conditions = array(
                            $db->quoteName('type') . ' = ' . $db->quote('package'), 
                            $db->quoteName('element') . ' = ' . $db->quote('pkg_hwdmediashare')
                        );

                        $query->delete($db->quoteName('#__extensions'));
                        $query->where($conditions);

                        try
                        {
                                $db->setQuery($query);
                                $result = $db->execute();                 
                        }
                        catch (RuntimeException $e)
                        {
                                $this->setError($e->getMessage());
                                return false;                            
                        }

                        if ($result)
                        {
                                $file = JPATH_SITE.'/administrator/manifests/packages/pkg_hwdmediashare.xml';
                                if (!JFile::delete($file))
                                {
                                        $this->setError(JText::sprintf('FILES_JOOMLA_ERROR_FILE_FOLDER', $file));
                                }
                        }
		}

                return true;
        }   
        
        /**
	 * Method to optimise database indexes
         * 
         * @access  public
	 * @return  boolean  True if successful, false if an error occurs.
	 */
	public function databaseIndexOptimisation()
	{
                // Initialise variables.
                $app = JFactory::getApplication();
                $db = JFactory::getDbo();

                // Fulltext idx_searches #__hwdms_media
                $query = 'SHOW INDEX FROM ' . $db->quoteName('#__hwdms_media') . ' WHERE ' . $db->quoteName('Key_name') . ' = ' . $db->quote('idx_searches');
                try
                {
                        $db->setQuery($query);
                        $result = $db->loadAssoc();                        
                }
                catch (RuntimeException $e)
                {
                        $app->enqueueMessage($e->getMessage());
                        return false;                         
                }

                if (!isset($result['Key_name']) || $result['Key_name'] != 'idx_searches')
                {
                        $query = 'ALTER TABLE ' . $db->quoteName('#__hwdms_media') . ' ADD FULLTEXT idx_searches (' . $db->quoteName('title') . ', ' . $db->quoteName('description') . ')';
                        try
                        {
                                $db->setQuery($query);
                                $result = $db->execute();                        
                        }
                        catch (RuntimeException $e)
                        {
                                $app->enqueueMessage($e->getMessage());
                                return false;                         
                        }
                }

                // Index idx_element_id #__hwdms_category_map
                $query = 'SHOW INDEX FROM ' . $db->quoteName('#__hwdms_category_map') . ' WHERE ' . $db->quoteName('Key_name') . ' = ' . $db->quote('idx_element_id');
                try
                {
                        $db->setQuery($query);
                        $result = $db->loadAssoc();                        
                }
                catch (RuntimeException $e)
                {
                        $app->enqueueMessage($e->getMessage());
                        return false;                         
                }

                if (!isset($result['Key_name']) || $result['Key_name'] != 'idx_element_id')
                {
                        $query = 'ALTER TABLE ' . $db->quoteName('#__hwdms_category_map') . ' ADD KEY idx_element_id  (' . $db->quoteName('element_id') . ')';
                        try
                        {
                                $db->setQuery($query);
                                $result = $db->execute();                        
                        }
                        catch (RuntimeException $e)
                        {
                                $app->enqueueMessage($e->getMessage());
                                return false;                         
                        }
                }
                
                // Index idx_element_type #__hwdms_category_map
                $query = 'SHOW INDEX FROM ' . $db->quoteName('#__hwdms_category_map') . ' WHERE ' . $db->quoteName('Key_name') . ' = ' . $db->quote('idx_element_type');
                try
                {
                        $db->setQuery($query);
                        $result = $db->loadAssoc();                        
                }
                catch (RuntimeException $e)
                {
                        $app->enqueueMessage($e->getMessage());
                        return false;                         
                }

                if (!isset($result['Key_name']) || $result['Key_name'] != 'idx_element_type')
                {
                        $query = 'ALTER TABLE ' . $db->quoteName('#__hwdms_category_map') . ' ADD KEY idx_element_type  (' . $db->quoteName('element_type') . ')';
                        try
                        {
                                $db->setQuery($query);
                                $result = $db->execute();                        
                        }
                        catch (RuntimeException $e)
                        {
                                $app->enqueueMessage($e->getMessage());
                                return false;                         
                        }
                }
                
                return true;                
        }   

        /**
	 * Method to migrate legacy tags to native Joomla tags.
         * 
         * @access  public
	 * @return  boolean  True if successful, false if an error occurs.
	 */
	public function migrateLegacyTags()
	{
		// Initialiase variables.
                $user = JFactory::getUser();
                $db = JFactory::getDBO();
                $date = JFactory::getDate();
                $app = JFactory::getApplication();

                $db->setQuery('SHOW TABLES');
                $tables = $db->loadColumn();

                // Check the legacy tag tables exist.
                if (in_array($app->getCfg( 'dbprefix' ).'hwdms_tag_map', $tables) && in_array($app->getCfg( 'dbprefix' ).'hwdms_tags', $tables)) 
                {
                        // Load all media tags.
                        $query = $db->getQuery(true)
                                ->select('map.*, tags.tag')
                                ->from('#__hwdms_tag_map AS map')
                                ->join('LEFT', '#__hwdms_tags AS tags ON tags.id = map.tag_id')
                                ->where('element_type = ' . $db->quote(1));
                        try
                        {                
                                $db->setQuery($query);
                                $tags = $db->loadObjectList();
                        }
                        catch (Exception $e)
                        {
                                $this->setError($e->getMessage());
                                return false;
                        }

                        foreach ($tags as $i => $tag)
                        {
                                if (empty($tag->tag))
                                {
                                        continue;
                                }
                            
                                if ($user->authorise('core.edit', 'com_hwdmediashare.media.'. (int) $tag->id))
                                {
                                        // Get a table instance.
                                        JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_hwdmediashare/tables');
                                        JObserverMapper::addObserverClassToClass('JTableObserverTags', 'hwdMediaShareTableMedia', array('typeAlias' => 'com_hwdmediashare.media'));                
                                        $table = JTable::getInstance('Media', 'hwdMediaShareTable');

                                        // Attempt to load the table row.
                                        $return = $table->load($tag->element_id);

                                        // Check for a table object error.
                                        if ($return === false && $table->getError())
                                        {
                                                $this->setError($table->getError());
                                                return false;
                                        }

                                        // Trim the tag to avoid problems with empty tags.
                                        $tag->tag = trim($tag->tag);
                                        
                                        // Insert tag into native Joomla tagging system.
					if ($table->id > 0 && !empty($tag->tag))
                                        {											
                                                $jtags = array($tag->tag);
                                                array_walk($jtags, function(&$value, $key) { $value = '#new#' . $value; });
                                                $tagsHelper = new JHelperTags;													
                                                $tagsHelper->createTagsFromField($jtags); 

                                                if (count($tagsHelper->tags))
                                                {
                                                        $tagsObserver = $table->getObserverOfClass('JTableObserverTags');   
                                                        $result = $tagsObserver->setNewTags($tagsHelper->tags, false);

                                                        if (!$result)
                                                        {
                                                                $this->setError($table->getError());
                                                                return false;
                                                        }
                                                }
                                        }

                                        // Remove legacy tag.
                                        $query = $db->getQuery(true);
                                        $conditions = array(
                                            $db->quoteName('element_type') . ' = ' . $db->quote(1),
                                            $db->quoteName('element_id') . ' = ' . $db->quote($tag->element_id),
                                            $db->quoteName('tag_id') . ' = ' . $db->quote($tag->tag_id)
                                        );
                                        $query->delete($db->quoteName('#__hwdms_tag_map'));
                                        $query->where($conditions);

                                        try
                                        {
                                                $db->setQuery($query);
                                                $db->execute();                 
                                        }
                                        catch (RuntimeException $e)
                                        {
                                                $this->setError($e->getMessage());
                                                return false;                            
                                        }
                                }
                        }
                }               

                return true;
        }   

        /**
	 * Method to cleanup activities.
         * 
         * @access  public
	 * @return  boolean  True if successful, false if an error occurs.
	 */
	public function cleanActivities()
	{
                // Initialise variables.
                $db = JFactory::getDbo();
                $status = true;
                
                // Load all activities (verb: 2).
                $query = $db->getQuery(true)
                        ->select('a.*, media.id as media_id, media.created_user_id as media_author, media.created as media_created')
                        ->from('#__hwdms_activity AS a')
                        ->join('LEFT', '#__hwdms_media AS media ON media.id = a.action')
                        ->where('verb = ' . $db->quote(2));
                try
                {                
                        $db->setQuery($query);
                        $activities = $db->loadObjectList();
                }
                catch (Exception $e)
                {
                        $this->setError($e->getMessage());
                        return false;
                }
                
                foreach ($activities as $activity)
                {
                        // Skip good maps.
                        if ($activity->media_id > 0 && ($activity->actor == $activity->media_author) && ($activity->created == $activity->media_created))
                        {
                                continue;
                        }

                        // Media or category no longer exists.
                        if (!$activity->media_id)
                        {
                                // Remove the category map.
                                $query = $db->getQuery(true);
                                $query->delete($db->quoteName('#__hwdms_activity'));
                                $query->where($db->quoteName('id') . ' = ' . $db->quote($activity->id));
                                try
                                {
                                        $db->setQuery($query);
                                        $result1 = $db->execute();
                                }
                                catch (Exception $e)
                                {
                                        $this->setError($e->getMessage());
                                        return false;
                                }
                                
                                if (!$result1)
                                {
                                        $status = false;
                                }
                                
                                // If media doesn't exist, no point in continuing.
                                continue;
                        }

                        // Activity details needs updating.
                        if (($activity->actor != $activity->media_author) || ($activity->created != $activity->media_created))
                        {      
                                $query = $db->getQuery(true);
                                $query->update($db->quoteName('#__hwdms_activity'))
                                      ->set(array(
                                            $db->quoteName('actor') . ' = ' . $db->quote($activity->media_author),
                                            $db->quoteName('created') . ' = ' . $db->quote($activity->media_created)
                                        ))
                                      ->where($db->quoteName('id') . ' = ' . $db->quote($activity->id));
                                
                                try
                                {
                                        $db->setQuery($query);
                                        $result2 = $db->execute();
                                }
                                catch (Exception $e)
                                {
                                        $this->setError($e->getMessage());
                                        return false;
                                }
                                
                                if (!$result2)
                                {
                                        $status = false;
                                }                                
                        }                        
                }

                return $status;
        }

        /**
	 * Method to cleanup channels.
         * 
         * @access  public
	 * @return  boolean  True if successful, false if an error occurs.
	 */
	public function cleanChannels()
	{
                // Initialise variables.
                $db = JFactory::getDbo();
                $status = true;
                
                // Load all activities (verb: 2).
                $query = $db->getQuery(true)
                        ->select('a.*, u.name')
                        ->from('#__hwdms_users AS a')
                        ->join('LEFT', '#__users AS u ON u.id = a.id')                        
                        ->where('alias = ' . $db->quote(''), 'OR')
                        ->where('title = ' . $db->quote(''))
                        ->where('language = ' . $db->quote(''));
                try
                {                
                        $db->setQuery($query);
                        $channels = $db->loadObjectList();
                }
                catch (Exception $e)
                {
                        $this->setError($e->getMessage());
                        return false;
                }

                foreach ($channels as $channel)
                {
                        $update = array();

                        if (empty($channel->alias))    $update[] = $db->quoteName('alias') . ' = ' . $db->quote(JFilterOutput::stringURLSafe($channel->name));
                        if (empty($channel->title))    $update[] = $db->quoteName('title') . ' = ' . $db->quote($channel->name);
                        if (empty($channel->language)) $update[] = $db->quoteName('language') . ' = ' . $db->quote('*');

                        $query = $db->getQuery(true);
                        $query->update($db->quoteName('#__hwdms_users'))
                              ->set($update)
                              ->where($db->quoteName('id') . ' = ' . $db->quote($channel->id));

                        try
                        {
                                $db->setQuery($query);
                                $result = $db->execute();
                        }
                        catch (Exception $e)
                        {
                                $this->setError($e->getMessage());
                                return false;
                        }

                        if (!$result) $status = false; 
                }

                return $status;
        }  

        /**
	 * Method to cleanup channels.
         * 
         * @access  public
	 * @return  boolean  True if successful, false if an error occurs.
	 */
	public function cleanStorage()
	{
                // Get HWD config.
                $hwdms = hwdMediaShareFactory::getInstance();
                $config = $hwdms->getConfig();
                
                hwdMediaShareFactory::load('files');
                $HWDfiles = hwdMediaShareFiles::getInstance();
                hwdMediaShareFiles::getLocalStoragePath();    
                
                // Remove empty subdirectories
                $this->RemoveEmptySubFolders(HWDMS_PATH_MEDIA_FILES);

                // Inspect files.
                $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(HWDMS_PATH_MEDIA_FILES));
                $files = array(); 
                foreach ($rii as $file) 
                {
                    if ($file->isDir())
                    { 
                        continue;
                    }
                    $files[] = $file->getPathname(); 
                }

                foreach ($files as $f) 
                {
                        $basename = pathinfo($f, PATHINFO_FILENAME);
                        $result = $HWDfiles->getFileByBasename($basename);   
                        
                        if (!$result)
                        {
                                echo "The file located at <strong>" . $f . "</strong> may not be associated with any media.<br />";
                        }
                        elseif ($result->media_type == 5)
                        {
                                echo "The file located at <strong> " . $f . "</strong> is a CDN media, and may need removing.<br />";
                        }                    
                }
        }          
        
        /**
	 * Method to cleanup channels.
         * 
         * @access  public
	 * @return  boolean  True if successful, false if an error occurs.
	 */
	public function RemoveEmptySubFolders($path)
        {
                $empty=true;
                foreach (glob($path.DIRECTORY_SEPARATOR."*") as $file)
                {
                   if (is_dir($file))
                   {
                      if (!$this->RemoveEmptySubFolders($file)) $empty=false;
                   }
                   else
                   {
                      $empty=false;
                   }
                }
                if ($empty)
                {
                        rmdir($path);
                }
                return $empty;
        }      

        /**
	 * Method to bulkallowcommenting
         * 
         * @access  public
	 * @return  boolean  True if successful, false if an error occurs.
	 */
	public function bulkChangeMediaParameter($key, $value)
	{
                $db = JFactory::getDbo();

                // Load all media.
                $query = $db->getQuery(true)
                        ->select('*')
                        ->from('#__hwdms_media');
                try
                {                
                        $db->setQuery($query);
                        $rows = $db->loadObjectList();
                }
                catch (Exception $e)
                {
                        $this->setError($e->getMessage());
                        return false;
                }

                foreach ($rows as $row)
                {
                        $params = json_decode($row->params);
                        if (!isset($params->{$key}) || (isset($params->{$key}) && $params->{$key} != $value))
                        {
                                // If params is not an objec tthen no parameters currently exist and we must define the new object.
                                if (!is_object($params)) 
                                {
                                        $params = new stdClass();
                                }
                                $params->{$key} = $value;
                                $params = json_encode($params);
                                
                                // Update media.
                                $query = $db->getQuery(true);
                                $query->update($db->quoteName('#__hwdms_media'))
                                      ->set(array($db->quoteName('params') . ' = ' . $db->quote($params)))
                                      ->where($db->quoteName('id') . ' = ' . $db->quote($row->id));

                                try
                                {
                                        $db->setQuery($query);
                                        $result = $db->execute();
                                }
                                catch (Exception $e)
                                {
                                        $this->setError($e->getMessage());
                                        return false;
                                }
                        }
                }

                return true;
        }         

        /**
	 * Proxy for bulkallowcommenting
         * 
         * @access  public
	 * @return  boolean  True if successful, false if an error occurs.
	 */
	public function bulkallowcommenting()
	{
                return $this->bulkChangeMediaParameter('allow_comments', 1);
        }  
        
        /**
	 * Proxy for bulkdenycommenting
         * 
         * @access  public
	 * @return  boolean  True if successful, false if an error occurs.
	 */       
	public function bulkdenycommenting()
	{
                return $this->bulkChangeMediaParameter('allow_comments', 0);
        }  

        /**
	 * Proxy for bulkallowcommenting
         * 
         * @access  public
	 * @return  boolean  True if successful, false if an error occurs.
	 */
	public function bulkallowlikes()
	{
                return $this->bulkChangeMediaParameter('allow_likes', 1);
        }  
        
        /**
	 * Proxy for bulkdenycommenting
         * 
         * @access  public
	 * @return  boolean  True if successful, false if an error occurs.
	 */       
	public function bulkdenylikes()
	{
                return $this->bulkChangeMediaParameter('allow_likes', 0);
        }    

        /**
	 * Proxy for bulkallowcommenting
         * 
         * @access  public
	 * @return  boolean  True if successful, false if an error occurs.
	 */
	public function bulkallowembedding()
	{
                return $this->bulkChangeMediaParameter('allow_embedding', 1);
        }  
        
        /**
	 * Proxy for bulkdenycommenting
         * 
         * @access  public
	 * @return  boolean  True if successful, false if an error occurs.
	 */       
	public function bulkdenyembedding()
	{
                return $this->bulkChangeMediaParameter('allow_embedding', 0);
        }       
}

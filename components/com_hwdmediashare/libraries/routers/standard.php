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

class hwdMediaShareRouterStandard extends JComponentRouterBase
{
	/**
	 * An array of loaded data.
         * 
         * @access      protected
	 * @var         array
	 */ 
	protected static $loadedAliases = array();
	protected static $loadedPlaylists = array();
	protected static $loadedAlbums = array();
	protected static $loadedGroups = array();

	/**
	 * Build the route for the com_hwdmediashare component.
	 *
         * @access  public
	 * @param   array  &$query  An array of URL arguments
	 * @return  array  The URL arguments to use to assemble the subsequent URL.
	 */
	public function build(&$query)
        {
		$segments = array();

                // Register HWD library factory.
                JLoader::register('hwdMediaShareFactory', JPATH_ROOT.'/components/com_hwdmediashare/libraries/factory.php');

                // Load HWD config.
                $hwdms = hwdMediaShareFactory::getInstance();
                $config = $hwdms->getConfig();

		// Get a menu item based on Itemid or currently active.
		$app = JFactory::getApplication();
		$menu = $app->getMenu();
		$params = JComponentHelper::getParams('com_hwdmediashare');
                $advanced = $config->get('sef_advanced', 0);
		$db = JFactory::getDbo();

		// We need a menu item.  Either the one specified in the query, or the current active one if none specified.
		if (empty($query['Itemid']))
		{
			$menuItem = $menu->getActive();
			$menuItemGiven = false;
		}
		else
		{
			$menuItem = $menu->getItem($query['Itemid']);
			$menuItemGiven = true;
		}

		// Check menu points to com_hwdmedishare.
		if ($menuItemGiven && isset($menuItem) && $menuItem->component != 'com_hwdmediashare')
		{
			$menuItemGiven = false;
			unset($query['Itemid']);
		}

		if (isset($query['view']))
		{
			$view = $query['view'];
		}
		else
		{
			// We need to have a view in the query or it is an invalid URL.
			return $segments;
		}

		// Are we dealing with a link that is attached directly to a menu item?
		if (($menuItem instanceof stdClass) && $menuItem->query['view'] == $query['view'] && isset($menuItem->query['id']) && isset($query['id']) && $menuItem->query['id'] == (int) $query['id'])
		{
			unset($query['view']);

			if (isset($query['layout']))
			{
				unset($query['layout']);
			}

			unset($query['id']);

			return $segments;
		}

		if ($view == 'mediaitem')
		{
                        $category_id = isset($query['category_id']) ? (int) $query['category_id'] : 0;
                        $playlist_id = isset($query['playlist_id']) ? (int) $query['playlist_id'] : 0;
                        $album_id = isset($query['album_id']) ? (int) $query['album_id'] : 0;
                        $group_id = isset($query['group_id']) ? (int) $query['group_id'] : 0;
                        
                        // Process the view.
			if (!$menuItemGiven)
			{
				$segments[] = $view;
                                unset($query['view']);
			}
                        elseif ($menuItemGiven && isset($menuItem->query['view']) && (
                                $menuItem->query['view'] == 'mediaitem' || 
                                $menuItem->query['view'] == 'media' || 
                                ($menuItem->query['view'] == 'categories' && $category_id) || 
                                ($menuItem->query['view'] == 'category' && $category_id) || 
                                ($menuItem->query['view'] == 'playlists' && $playlist_id) || 
                                ($menuItem->query['view'] == 'playlist' && $playlist_id) || 
                                ($menuItem->query['view'] == 'albums' && $album_id) || 
                                ($menuItem->query['view'] == 'album' && $album_id) || 
                                ($menuItem->query['view'] == 'groups' && $group_id) || 
                                ($menuItem->query['view'] == 'group' && $group_id)))
                        {
                                unset($query['view']);
                        }
                        else
                        {
                                $segments[] = $view;
                                unset($query['view']);
                        }

                        if (isset($query['id']))
                        {
                                // Make sure we have the id and the alias
                                if (strpos($query['id'], ':') === false)
                                {
                                        $hash = md5(intval($query['id']) . ':#__hwdms_media');
                                        if (!isset(self::$loadedAliases[$hash])) 
                                        {
                                                $dbQuery = $db->getQuery(true)
                                                        ->select($db->quoteName(array('alias')))
                                                        ->from($db->quoteName('#__hwdms_media'))
                                                        ->where($db->quoteName('id') . ' = ' . (int) $query['id']);
                                                $db->setQuery($dbQuery);
                                                self::$loadedAliases[$hash] = $db->loadResult();
                                        }                                    

                                        $query['id'] = $query['id'] . ':' . self::$loadedAliases[$hash];                                
                                }
                        }
                        else
                        {
                                // We should have these two set for this view.  If we don't, it is an error
                                return $segments;
                        }
                        
                        // Add category segments.
                        if ($category_id)
                        {
                                $categories = JCategories::getInstance('hwdMediaShare');
                                $category = $categories->get($category_id);

                                if (!$category)
                                {
                                        // We couldn't find the category we were given.  Bail.
                                        return $segments;
                                }

                                // Check current menu item 
                                if ($menuItemGiven && isset($menuItem->query['view']) && $menuItem->query['view'] == 'category' && isset($menuItem->query['id']))
                                {
                                        $mCatid = $menuItem->query['id'];
                                }
                                else
                                {
                                        $mCatid = 0;
                                }

                                $path = array_reverse($category->getPath());

                                $array = array();

                                foreach ($path as $id)
                                {
                                        if ((int) $id == (int) $mCatid)
                                        {
                                                break;
                                        }

                                        list($tmp, $id) = explode(':', $id, 2);

                                        $array[] = $id;
                                }

                                $array = array_reverse($array);

                                if (!$advanced && count($array))
                                {
                                        $array[0] = (int) $category_id . ':' . $array[0];
                                }

                                // Check current menu item to see if we need to specify what element we are viewing.
                                if ($menuItemGiven && isset($menuItem->query['view']) && $menuItem->query['view'] != 'categories')
                                {
                                        if (count($array) && !$mCatid)
                                        {
                                                array_unshift($array, 'category');
                                        }   
                                }                                

                                $segments = array_merge($segments, $array);
                                
                                unset($query['category_id']);
                        }
                        elseif ($playlist_id)
                        {
                                if (!isset(self::$loadedPlaylists[$playlist_id])) 
                                {
                                        // Get a playlist table instance.
                                        $table = JTable::getInstance('Playlist', 'hwdMediaShareTable');    

                                        // Attempt to load the table row.
                                        $return = $table->load($playlist_id);

                                        // Check for a table object error.
                                        if ($return === false && $table->getError())
                                        {
                                                // We couldn't find the playlist we were given.  Bail.
                                                return $segments;
                                        }

                                        $properties = $table->getProperties(1);
                                        self::$loadedPlaylists[$playlist_id] = JArrayHelper::toObject($properties, 'JObject');
                                }                                    

                                $playlist = self::$loadedPlaylists[$playlist_id];
                                
                                // Setup 'playlist' view.
                                if (isset($menuItem->query['view']) && $menuItem->query['view'] == 'playlist' && $menuItem->query['id'] == $playlist_id)
                                {
                                        unset($query['playlist_id']);
                                } 
                                else
                                {
                                        // Check current menu item to see if we need to specify what element we are viewing.
                                        if ($menuItemGiven && isset($menuItem->query['view']) && $menuItem->query['view'] != 'playlists')
                                        {
                                                $segments[] = 'playlist';
                                        } 

                                        // Add media segment.        
                                        if ($advanced)
                                        {
                                                $segments[] = $playlist->alias;
                                        }
                                        else
                                        {
                                                $segments[] = (int) $playlist_id . ':' . $playlist->alias;
                                        }
                                        
                                        unset($query['playlist_id']);
                                }
                        }
                        elseif ($album_id)
                        {
                                if (!isset(self::$loadedAlbums[$album_id])) 
                                {
                                        // Get a playlist table instance.
                                        $table = JTable::getInstance('Album', 'hwdMediaShareTable');    

                                        // Attempt to load the table row.
                                        $return = $table->load($album_id);

                                        // Check for a table object error.
                                        if ($return === false && $table->getError())
                                        {
                                                // We couldn't find the playlist we were given.  Bail.
                                                return $segments;
                                        }

                                        $properties = $table->getProperties(1);
                                        self::$loadedAlbums[$album_id] = JArrayHelper::toObject($properties, 'JObject');
                                }                                    

                                $album = self::$loadedAlbums[$album_id];

                                // Check current menu item to see if we need to specify what element we are viewing.
                                if ($menuItemGiven && isset($menuItem->query['view']) && $menuItem->query['view'] != 'albums')
                                {
                                        $segments[] = 'album';
                                }  
                                
                                // Add media segment.        
                                if ($advanced)
                                {
                                        $segments[] = $album->alias;
                                }
                                else
                                {
                                        $segments[] = (int) $album_id . ':' . $album->alias;
                                }

                                unset($query['album_id']);
                        }
                        elseif ($group_id)
                        {
                                if (!isset(self::$loadedGroups[$group_id])) 
                                {
                                        // Get a playlist table instance.
                                        $table = JTable::getInstance('Group', 'hwdMediaShareTable');    

                                        // Attempt to load the table row.
                                        $return = $table->load($group_id);

                                        // Check for a table object error.
                                        if ($return === false && $table->getError())
                                        {
                                                // We couldn't find the playlist we were given.  Bail.
                                                return $segments;
                                        }

                                        $properties = $table->getProperties(1);
                                        self::$loadedGroups[$group_id] = JArrayHelper::toObject($properties, 'JObject');
                                }                                    

                                $group = self::$loadedGroups[$group_id];

                                // Check current menu item to see if we need to specify what element we are viewing.
                                if ($menuItemGiven && isset($menuItem->query['view']) && $menuItem->query['view'] != 'groups')
                                {
                                        $segments[] = 'group';
                                }  

                                // Add media segment.        
                                if ($advanced)
                                {
                                        $segments[] = $group->alias;
                                }
                                else
                                {
                                        $segments[] = (int) $group_id . ':' . $group->alias;
                                }

                                unset($query['group_id']);
                        }

                        // Add media segment.        
                        if ($advanced)
                        {
                                list($tmp, $id) = explode(':', $query['id'], 2);
                        }
                        else
                        {
                                $id = $query['id'];
                        }

                        $segments[] = $id;

			unset($query['id']);
		}
                elseif ($view == 'category')
		{
                        // Process the view.
			if (!$menuItemGiven)
			{
				$segments[] = $view;
                                unset($query['view']);
			}
                        elseif ($menuItemGiven && isset($menuItem->query['view']) && ($menuItem->query['view'] == 'category' || $menuItem->query['view'] == 'categories'))
                        {
                                unset($query['view']);
                        }
                        else
                        {
                                $segments[] = $view;
                                unset($query['view']);
                        }

                        if (isset($query['id']))
                        {
                                // Make sure we have the id and the alias
                                if (strpos($query['id'], ':') === false)
                                {
                                        $dbQuery = $db->getQuery(true)
                                                ->select('alias')
                                                ->from('#__categories')
                                                ->where('id=' . (int) $query['id']);
                                        $db->setQuery($dbQuery);
                                        $alias = $db->loadResult();
                                        $query['id'] = $query['id'] . ':' . $alias;
                                }
                        }
                        else
                        {
                                // We should have these two set for this view.  If we don't, it is an error
                                return $segments;
                        }

                        $category_id = (int) $query['id'];
                        $categories = JCategories::getInstance('hwdMediaShare');
                        $category = $categories->get($category_id);

                        if (!$category)
                        {
                                // We couldn't find the category we were given.  Bail.
                                return $segments;
                        }

                        // Check current menu item 
                        if ($menuItemGiven && isset($menuItem->query['view']) && $menuItem->query['view'] == 'category' && isset($menuItem->query['id']))
                        {
                                $mCatid = $menuItem->query['id'];
                        }
                        else
                        {
                                $mCatid = 0;
                        }

                        $path = array_reverse($category->getPath());

                        $array = array();

                        foreach ($path as $id)
                        {
                                if ((int) $id == (int) $mCatid)
                                {
                                        break;
                                }

                                list($tmp, $id) = explode(':', $id, 2);

                                $array[] = $id;
                        }

                        $array = array_reverse($array);

                        if (!$advanced && count($array))
                        {
                                $array[0] = (int) $category_id . ':' . $array[0];
                        }             

                        $segments = array_merge($segments, $array);

			unset($query['id']);
		}
                else
                {
                        $map = array(
                            'album'    => 'albums',
                            'channel'  => 'channels',
                            'group'    => 'groups',
                            'playlist' => 'playlists',
                        );

                        // Process the view.
                        if ($menuItemGiven && isset($menuItem->query['view']) && $menuItem->query['view'] == $view)
                        {
                                unset($query['view']);
                        }
                        elseif ($menuItemGiven && isset($menuItem->query['view']) && isset($map[$view]) && $menuItem->query['view'] == $map[$view])
                        {
                                unset($query['view']);
                        }
                        else
                        {
                                $segments[] = $view;
                                unset($query['view']);
                        }

                        // Process the id.
                        if (isset($query['id']))
                        {                        
                                if ($advanced && strpos($query['id'], ':') !== false)
                                {
                                        list($tmp, $id) = explode(':', $query['id'], 2);
                                }
                                else
                                {
                                        $id = $query['id'];
                                }

                                $segments[] = $id;
                                unset($query['id']);                        
                        }
                }
                
		/*
		 * If the layout is specified and it is the same as the layout in the menu item, we
		 * unset it so it doesn't go into the query string.
		 */
		if (isset($query['layout']))
		{
			if ($menuItemGiven && isset($menuItem->query['layout']))
			{
				if ($query['layout'] == $menuItem->query['layout'])
				{
					unset($query['layout']);
				}
			}
			else
			{
				if ($query['layout'] == 'default')
				{
					unset($query['layout']);
				}
			}
		}

		$total = count($segments);

		for ($i = 0; $i < $total; $i++)
		{
			$segments[$i] = str_replace(':', '-', $segments[$i]);
		}

		return $segments;
        }

	/**
	 * Parse the segments of a URL.
	 *
         * @access  public
	 * @param   array  &$segments  The segments of the URL to parse.
	 * @return  array  The URL attributes to be used by the application.
	 */
	public function parse(&$segments)
	{
                // Register HWD library factory.
                JLoader::register('hwdMediaShareFactory', JPATH_ROOT.'/components/com_hwdmediashare/libraries/factory.php');

                // Load HWD config.
                $hwdms = hwdMediaShareFactory::getInstance();
                $config = $hwdms->getConfig();
                
		$total = count($segments);
		$vars = array();

		for ($i = 0; $i < $total; $i++)
		{
			$segments[$i] = preg_replace('/-/', ':', $segments[$i], 1);
		}

		// Get the active menu item.
		$app = JFactory::getApplication();
		$menu = $app->getMenu();
		$item = $menu->getActive();
		$params = JComponentHelper::getParams('com_hwdmediashare');
		$advanced = $config->get('sef_advanced', 0);
		$db = JFactory::getDbo();

		// Count route segments
		$count = count($segments);

                // Define routing map.
                $routing = array(
                    'account'       => array(),
                    'album'         => array('table' => '#__hwdms_albums',                      'listview' => 'albums', 'itemview' => 'album', 'formview' => 'albumform'),
                    'albumform'     => array('table' => '#__hwdms_albums',                      'listview' => 'albums', 'itemview' => 'album', 'formview' => 'albumform'),
                    'albummedia'    => array(),
                    'albums'        => array('table' => '#__hwdms_albums',    'isList' => true, 'listview' => 'albums', 'itemview' => 'album', 'formview' => 'albumform'),
                    'categories'    => array('table' => '#__categories',      'isList' => true, 'listview' => 'categories', 'itemview' => 'category', 'formview' => 'categoryform'),
                    'category'      => array('table' => '#__categories',                        'listview' => 'categories', 'itemview' => 'category', 'formview' => 'categoryform'),
                    'categoryform'  => array('table' => '#__categories',                        'listview' => 'categories', 'itemview' => 'category', 'formview' => 'categoryform'),
                    'discover'      => array(),
                    'group'         => array('table' => '#__hwdms_groups',                      'listview' => 'groups', 'itemview' => 'group', 'formview' => 'groupform'),
                    'groupform'     => array('table' => '#__hwdms_groups',                      'listview' => 'groups', 'itemview' => 'group', 'formview' => 'groupform'),
                    'groupmedia'    => array(),
                    'groupmembers'  => array(),
                    'groups'        => array('table' => '#__hwdms_groups',    'isList' => true, 'listview' => 'groups', 'itemview' => 'group', 'formview' => 'groupform'),
                    'media'         => array('table' => '#__hwdms_media',     'isList' => true, 'listview' => 'media', 'itemview' => 'mediaitem', 'formview' => 'mediaform'),
                    'mediaform'     => array('table' => '#__hwdms_media',                       'listview' => 'media', 'itemview' => 'mediaitem', 'formview' => 'mediaform'),
                    'mediaitem'     => array('table' => '#__hwdms_media',                       'listview' => 'media', 'itemview' => 'mediaitem', 'formview' => 'mediaform'),
                    'playlist'      => array('table' => '#__hwdms_media',     'isItem' => true, 'listview' => '',      'itemview' => 'mediaitem', 'formview' => ''),
                    'playlistform'  => array('table' => '#__hwdms_playlists',                   'listview' => 'playlists', 'itemview' => 'playlist', 'formview' => 'playlistform'),
                    'playlistmedia' => array(),
                    'playlists'     => array('table' => '#__hwdms_playlists', 'isList' => true, 'listview' => 'playlists', 'itemview' => 'playlist', 'formview' => 'playlistform'),
                    'search'        => array(),
                    'upload'        => array(),
                    'channel'       => array('table' => '#__hwdms_users',                       'listview' => 'channels', 'itemview' => 'channel', 'formview' => 'channelform'),
                    'channelform'   => array('table' => '#__hwdms_users',                       'listview' => 'channels', 'itemview' => 'channel', 'formview' => 'channelform'),
                    'channels'      => array('table' => '#__hwdms_users',     'isList' => true, 'listview' => 'channels', 'itemview' => 'channel', 'formview' => 'channelform'),                      
                );

                /*
		 * Standard routing for articles.  If we don't pick up an Itemid then we get the view from the segments
		 * the first segment is the view and the last segment is the id of the article or category.
		 */
		if (!isset($item))
		{
			$vars['view'] = $segments[0];
                        
                        if (isset($routing[$segments[0]]['table']))
                        {
                                if ($advanced)
                                {
                                        $query = $db->getQuery(true)
                                                ->select($db->quoteName(array('id')))
                                                ->from($db->quoteName($routing[$segments[0]]['table']))
                                                ->where($db->quoteName('alias') . ' = ' . $db->quote(JApplication::stringURLSafe($segments[$count - 1])));
                                        $db->setQuery($query);
                                        $row = $db->loadObject();

                                        if ($row)
                                        {
                                                $vars['id'] = $row->id;

                                                return $vars;
                                        }
                                }

                                $vars['id'] = (int) $segments[$count - 1];
                        }

			return $vars;
		}

		/*
		 * If there is only one segment, and it matches a view name, then we'll assume that is the view.
		 */
		if ($count == 1 && isset($routing[$segments[0]]))
		{
			$vars['view'] = $segments[0];
			return $vars;
                }
           
		/*
		 * If there is only one segment, and it is bound to a list view, then this will probably be an item view for the list.
		 * If there is only one segment, and it is bound to an item view, then this will probably be an mediaitem view that is associated with the item.
		 */
		if ($count == 1 && (isset($routing[$item->query['view']]['isList']) || isset($routing[$item->query['view']]['isItem'])))
		{           
                        if ($advanced)
                        {
                                $query = $db->getQuery(true)
                                        ->select($db->quoteName(array('id')))
                                        ->from($db->quoteName($routing[$item->query['view']]['table']))
                                        ->where($db->quoteName('alias') . ' = ' . $db->quote(JApplication::stringURLSafe($segments[0])));
                                $db->setQuery($query);
                                $row = $db->loadObject();

                                if ($row)
                                {
                                        $vars['view'] = $routing[$item->query['view']]['itemview'];
                                        $vars['id'] = $row->id;

                                        if (isset($item->query['view']) && isset($item->query['id']))
                                        {
                                                switch($item->query['view'])
                                                {
                                                        case 'playlist':
                                                                $vars['playlist_id'] = $item->query['id'];
                                                        break;
                                                        case 'album':
                                                                $vars['album_id'] = $item->query['id'];
                                                        break;
                                                        case 'group':
                                                                $vars['group_id'] = $item->query['id'];
                                                        break;                                   
                                                }  
                                        }     
                                        
                                        return $vars;
                                } 
                                elseif ((int) $segments[0] == $segments[0]) // If the segment is an integer, then it may be the ID.
                                {
                                        $query = $db->getQuery(true)
                                                ->select($db->quoteName(array('id')))
                                                ->from($db->quoteName($routing[$item->query['view']]['table']))
                                                ->where($db->quoteName('id') . ' = ' . (int) $segments[0]);
                                        $db->setQuery($query);
                                        $row = $db->loadObject();

                                        if ($row)
                                        {
                                                $vars['view'] = $routing[$item->query['view']]['itemview'];
                                                $vars['id'] = $row->id;
                                                
                                                if (isset($item->query['view']) && isset($item->query['id']))
                                                {
                                                        switch($item->query['view'])
                                                        {
                                                                case 'playlist':
                                                                        $vars['playlist_id'] = $item->query['id'];
                                                                break;
                                                                case 'album':
                                                                        $vars['album_id'] = $item->query['id'];
                                                                break;
                                                                case 'group':
                                                                        $vars['group_id'] = $item->query['id'];
                                                                break;                                   
                                                        }  
                                                }
                                                
                                                return $vars;
                                        }                                             
                                }   
                        }
                        else
                        {
                                if (strpos($segments[0], ':') === false)
                                {
                                        $id = (int) $segments[0];
                                        $alias = false;
                                }
                                else
                                {
                                        list($id, $alias) = explode(':', $segments[0], 2);
                                }

                                $hash = md5(intval($id) . ':' . $routing[$item->query['view']]['table']);
                                if (!isset(self::$loadedAliases[$hash])) 
                                {
                                        $dbQuery = $db->getQuery(true)
                                                 ->select($db->quoteName(array('alias')))
                                                 ->from($db->quoteName($routing[$item->query['view']]['table']))
                                                 ->where($db->quoteName('id') . ' = ' . (int) $id);
                                        $db->setQuery($dbQuery);
                                        self::$loadedAliases[$hash] = $db->loadResult();
                                }                                    

                                if (self::$loadedAliases[$hash])
                                {
                                        if (!$alias || self::$loadedAliases[$hash] == $alias)
                                        {
                                                $vars['view'] = $routing[$item->query['view']]['itemview'];
                                                $vars['id'] = (int) $id;

                                                if (isset($item->query['view']) && isset($item->query['id']))
                                                {
                                                        switch($item->query['view'])
                                                        {
                                                                case 'playlist':
                                                                        $vars['playlist_id'] = $item->query['id'];
                                                                break;
                                                                case 'album':
                                                                        $vars['album_id'] = $item->query['id'];
                                                                break;
                                                                case 'group':
                                                                        $vars['group_id'] = $item->query['id'];
                                                                break;                                   
                                                        }  
                                                }
        
                                                return $vars;
                                        }
                                }                                                
                        } 
		}
                
		/*
		 * If there are two segments, then this is probably standard routing, so we'll check.
		 */
		if ($count == 2 && isset($routing[$segments[0]]['table']))
		{
                        if ($advanced)
                        {
                                $query = $db->getQuery(true)
                                        ->select($db->quoteName(array('id')))
                                        ->from($db->quoteName($routing[$segments[0]]['table']))
                                        ->where($db->quoteName('alias') . ' = ' . $db->quote(JApplication::stringURLSafe($segments[$count - 1])));
                                $db->setQuery($query);
                                $row = $db->loadObject();

                                if ($row)
                                {
                                        $vars['view'] = $segments[0];
                                        $vars['id'] = $row->id;

                                        return $vars;
                                }  
                                elseif ((int) $segments[$count - 1] == $segments[$count - 1]) // If the segment is an integer, then it may be the ID.
                                {
                                        $query = $db->getQuery(true)
                                                ->select($db->quoteName(array('id')))
                                                ->from($db->quoteName($routing[$segments[0]]['table']))
                                                ->where($db->quoteName('id') . ' = ' . (int) $segments[$count - 1]);
                                        $db->setQuery($query);
                                        $row = $db->loadObject();

                                        if ($row)
                                        {
                                                $vars['view'] = $segments[0];
                                                $vars['id'] = $row->id;

                                                return $vars;
                                        }                                             
                                } 
                                
                        }
                        else
                        {
                                if (strpos($segments[0], ':') === false)
                                {
                                        $id = (int) $segments[$count - 1];
                                        $alias = false;
                                }
                                else
                                {
                                        list($id, $alias) = explode(':', $segments[$count - 1], 2);
                                }

                                $query = $db->getQuery(true)
                                        ->select($db->quoteName(array('alias')))
                                        ->from($db->quoteName($routing[$segments[0]]['table']))
                                        ->where($db->quoteName('id') . ' = ' . (int) $id);
                                $db->setQuery($query);
                                $row = $db->loadObject();

                                if ($row)
                                {
                                        if (!$alias || $row->alias == $alias)
                                        {
                                                $vars['view'] = $segments[0];
                                                $vars['id'] = (int) $id;

                                                return $vars;
                                        }
                                }                                                 
                        } 
                } 

                // Now we're at a mediaitem or a category

                // Check for media items bound to a "playlists", "albums" or "groups" menu item.
		if ($count == 2 && ($item->query['view'] == 'playlists' || $item->query['view'] == 'albums' || $item->query['view'] == 'groups'))
		{
                        if ($advanced)
                        {
                                $query = $db->getQuery(true)
                                        ->select($db->quoteName(array('id')))
                                        ->from($db->quoteName('#__hwdms_media'))
                                        ->where($db->quoteName('alias') . ' = ' . $db->quote(JApplication::stringURLSafe($segments[$count - 1])));
                                $db->setQuery($query);
                                $row = $db->loadObject();

                                if ($row)
                                {
                                        $vars['view'] = 'mediaitem';
                                        $vars['id'] = $row->id;

                                        $query = $db->getQuery(true)
                                                ->select($db->quoteName(array('id')))
                                                ->from($db->quoteName($routing[$item->query['view']]['table']))
                                                ->where($db->quoteName('alias') . ' = ' . $db->quote(JApplication::stringURLSafe($segments[0])));
                                        $db->setQuery($query);
                                        $row = $db->loadObject();

                                        if ($row)
                                        {
                                                switch($item->query['view'])
                                                {
                                                        case 'playlists':
                                                                $vars['playlist_id'] = $row->id;
                                                        break;
                                                        case 'albums':
                                                                $vars['album_id'] = $row->id;
                                                        break;
                                                        case 'groups':
                                                                $vars['group_id'] = $row->id;
                                                        break;                                   
                                                } 
                                        }
 
                                        return $vars;
                                }  
                                elseif ((int) $segments[$count - 1] == $segments[$count - 1]) // If the segment is an integer, then it may be the ID.
                                {
                                        $query = $db->getQuery(true)
                                                ->select($db->quoteName(array('id')))
                                                ->from($db->quoteName('#__hwdms_media'))
                                                ->where($db->quoteName('id') . ' = ' . (int) $segments[$count - 1]);
                                        $db->setQuery($query);
                                        $row = $db->loadObject();

                                        if ($row)
                                        {
                                                $vars['view'] = 'mediaitem';
                                                $vars['id'] = $row->id;

                                                $query = $db->getQuery(true)
                                                        ->select($db->quoteName(array('id')))
                                                        ->from($db->quoteName($routing[$item->query['view']]['table']))
                                                        ->where($db->quoteName('alias') . ' = ' . $db->quote(JApplication::stringURLSafe($segments[0])));
                                                $db->setQuery($query);
                                                $row = $db->loadObject();

                                                if ($row)
                                                {
                                                        switch($item->query['view'])
                                                        {
                                                                case 'playlists':
                                                                        $vars['playlist_id'] = $row->id;
                                                                break;
                                                                case 'albums':
                                                                        $vars['album_id'] = $row->id;
                                                                break;
                                                                case 'groups':
                                                                        $vars['group_id'] = $row->id;
                                                                break;                                   
                                                        }
                                                }
                                        
                                                return $vars;
                                        }                                             
                                } 
                                
                        }
                        else
                        {
                                if (strpos($segments[0], ':') === false)
                                {
                                        $id = (int) $segments[$count - 1];
                                        $alias = false;
                                }
                                else
                                {
                                        list($id, $alias) = explode(':', $segments[$count - 1], 2);
                                }

                                $query = $db->getQuery(true)
                                        ->select($db->quoteName(array('alias')))
                                        ->from($db->quoteName('#__hwdms_media'))
                                        ->where($db->quoteName('id') . ' = ' . (int) $id);
                                $db->setQuery($query);
                                $row = $db->loadObject();

                                if ($row)
                                {
                                        if (!$alias || $row->alias == $alias)
                                        {
                                                $vars['view'] = 'mediaitem';
                                                $vars['id'] = (int) $id;
                                                
                                                // Don't crosscheck the ID here.
                                                switch($item->query['view'])
                                                {
                                                        case 'playlists':
                                                                $vars['playlist_id'] = (int) $segments[0];
                                                        break;
                                                        case 'albums':
                                                                $vars['album_id'] = (int) $segments[0];
                                                        break;
                                                        case 'groups':
                                                                $vars['group_id'] = (int) $segments[0];
                                                        break;                                   
                                                }                                                

                                                return $vars;
                                        }
                                }                                                 
                        } 
                }

                // First check for a direct category menu view  
		if ($count == 1 && $item->query['view'] == 'category' && isset($item->query['id']) && $item->query['id'])
                {
                        if ($advanced)
                        {
                                $query = $db->getQuery(true)
                                        ->select($db->quoteName(array('id')))
                                        ->from($db->quoteName('#__hwdms_media'))
                                        ->where($db->quoteName('alias') . ' = ' . $db->quote(JApplication::stringURLSafe($segments[$count - 1])));
                                $db->setQuery($query);
                                $row = $db->loadObject();

                                if ($row)
                                {
                                        $vars['view'] = 'mediaitem';
                                        $vars['id'] = $row->id;
                                        $vars['category_id'] = $item->query['id'];

                                        return $vars;
                                }                  
                        }
                        else
                        {
                                list($id, $alias) = explode(':', $segments[$count - 1], 2);

                                $query = $db->getQuery(true)
                                        ->select($db->quoteName(array('alias')))
                                        ->from($db->quoteName('#__hwdms_media'))
                                        ->where($db->quoteName('id') . ' = ' . (int) $id);
                                $db->setQuery($query);
                                $row = $db->loadObject();

                                if ($row)
                                {
                                        if ($row->alias == $alias)
                                        {
                                                $vars['view'] = 'mediaitem';
                                                $vars['id'] = (int) $segments[$count - 1];
                                                $vars['category_id'] = $item->query['id'];

                                                return $vars;
                                        }
                                }
                        }                    
                }

                // Next we'll check for a category view.
		if ($segments[0] != 'mediaitem' && $item->query['view'] == 'categories' || $item->query['view'] == 'category' || $segments[0] == 'category')
                {
                        if ($advanced)
                        {
                                // Jump into the category tree at the current menu view if necessary.
                                $activecategory = (isset($item->query['view']) && isset($item->query['id']) && $item->query['view'] == 'category') ? $item->query['id'] : 0;
                                $category = JCategories::getInstance('hwdMediaShare')->get($activecategory);

                                if (!$category)
                                {
                                        JError::raiseError(404, JText::_('COM_CONTENT_ERROR_PARENT_CATEGORY_NOT_FOUND'));
                                        return $vars;
                                }

                                $categories = $category->getChildren();

                                foreach ($segments as $key => $segment)
                                {
                                        $segment = str_replace(':', '-', $segment);

                                        foreach ($categories as $category)
                                        {
                                                if ($category->alias == $segment)
                                                {                                                    
                                                        // Only validate when reaching the last segment.
                                                        if ($key == ($count - 1))
                                                        {
                                                                $vars['view'] = 'category';
                                                                $vars['id'] = $category->id;
                                                                
                                                                return $vars;
                                                        }
                                                        $categories = $category->getChildren();
                                                        break;
                                                }
                                        }
                                }
                        } 
                        elseif (!$advanced && strpos(($segments[0] == 'category' ? $segments[1] : $segments[0]), ':') !== false)
                        {
                                // Get the category ID.
                                list($id, $alias) = explode(':', ($segments[0] == 'category' ? $segments[1] : $segments[0]), 2);
                                $category_id = (int) $id;   
                                
                                // Get the category alias (if only one segment, then this will contain the ID also)
                                if (count($segments) == 1)
                                {
                                        $category_alias = $alias;
                                }
                                else
                                {
                                        $category_alias = str_replace(':', '-', $segments[$count - 1]);
                                }
                                
                                $categories = JCategories::getInstance('hwdMediaShare');
                                $category = $categories->get($category_id);
                                
                                if ($category->alias == $category_alias)
                                {
                                        $vars['view'] = 'category';
                                        $vars['id'] = (int) $category_id;

                                        return $vars;
                                }
                        }
                }

                // Check for media items bound to a "categories" or "category" menu item.
		if ($item->query['view'] == 'categories' || $item->query['view'] == 'category')
		{
                        if ($advanced)
                        {
                                $query = $db->getQuery(true)
                                        ->select($db->quoteName(array('id')))
                                        ->from($db->quoteName('#__hwdms_media'))
                                        ->where($db->quoteName('alias') . ' = ' . $db->quote(JApplication::stringURLSafe($segments[$count - 1])));
                                $db->setQuery($query);
                                $row = $db->loadObject();

                                if ($row)
                                {
                                        $vars['view'] = 'mediaitem';
                                        $vars['id'] = $row->id;
                                        
                                        // Jump into the category tree at the current menu view if necessary.
                                        $activecategory = (isset($item->query['view']) && isset($item->query['id']) && $item->query['view'] == 'category') ? $item->query['id'] : 0;
                                        $category = JCategories::getInstance('hwdMediaShare')->get($activecategory);

                                        if (!$category)
                                        {
                                                JError::raiseError(404, JText::_('COM_CONTENT_ERROR_PARENT_CATEGORY_NOT_FOUND'));
                                                return $vars;
                                        }

                                        $categories = $category->getChildren();

                                        foreach ($segments as $key => $segment)
                                        {
                                                $segment = str_replace(':', '-', $segment);

                                                foreach ($categories as $category)
                                                {              
                                                        if ($category->alias == $segment)
                                                        {                                        
                                                                // Only validate when reaching the last segment.
                                                                if ($key == ($count - 2))
                                                                {
                                                                        $vars['category_id'] = $category->id;
                                                                        
                                                                        return $vars;
                                                                }
                                                                $categories = $category->getChildren();
                                                                break;
                                                        }
                                                }
                                        }
                                
                                        return $vars;
                                }  
                                elseif ((int) $segments[$count - 1] == $segments[$count - 1]) // If the segment is an integer, then it may be the ID.
                                {
                                        $query = $db->getQuery(true)
                                                ->select($db->quoteName(array('id')))
                                                ->from($db->quoteName('#__hwdms_media'))
                                                ->where($db->quoteName('id') . ' = ' . (int) $segments[$count - 1]);
                                        $db->setQuery($query);
                                        $row = $db->loadObject();

                                        if ($row)
                                        {
                                                $vars['view'] = 'mediaitem';
                                                $vars['id'] = $row->id;
                                        
                                                return $vars;
                                        }                                             
                                } 
                        }
                        else
                        {
                                if (strpos($segments[0], ':') === false)
                                {
                                        $id = (int) $segments[$count - 1];
                                        $alias = false;
                                }
                                else
                                {
                                        list($id, $alias) = explode(':', $segments[$count - 1], 2);
                                }

                                $query = $db->getQuery(true)
                                        ->select($db->quoteName(array('alias')))
                                        ->from($db->quoteName('#__hwdms_media'))
                                        ->where($db->quoteName('id') . ' = ' . (int) $id);
                                $db->setQuery($query);
                                $row = $db->loadObject();

                                if ($row)
                                {
                                        if (!$alias || $row->alias == $alias)
                                        {
                                                $vars['view'] = 'mediaitem';
                                                $vars['id'] = (int) $id;
                                                
                                                // Don't crosscheck the ID here.
                                                $vars['category_id'] = (int) $segments[0];

                                                return $vars;
                                        }
                                }                                                 
                        } 
                }

                
                // If the first segment is the mediaitem view name, then we remove it and shift the array before continuing.
                if ($segments[0] == 'mediaitem') 
                {
                        array_shift($segments);
                        $count--;
                }
        
                // Finally, we assume we are looking at an advanced mediaitem view.
		if (in_array($segments[0], array('album', 'category', 'group', 'playlist')))
                {     
                        if (!$advanced)
                        {
                                switch($segments[0])
                                {
                                        case 'album':
                                                $vars['album_id'] = (int) $segments[1];
                                        break;
                                        case 'category':
                                                $vars['category_id'] = (int) $segments[1];
                                        break;
                                        case 'group':
                                                $vars['group_id'] = (int) $segments[1];
                                        break;
                                        case 'playlist':
                                                $vars['playlist_id'] = (int) $segments[1];
                                        break;                                    
                                }
                                
                                $vars['view'] = 'mediaitem';
                                $vars['id'] = (int) $segments[$count - 1];

                                return $vars;
                        }
                        else
                        {
                                switch($segments[0])
                                {
                                        case 'album':
                                                $query = $db->getQuery(true)
                                                        ->select($db->quoteName(array('id')))
                                                        ->from($db->quoteName('#__hwdms_albums'))
                                                        ->where($db->quoteName('alias') . ' = ' . $db->quote(JApplication::stringURLSafe($segments[1])));
                                                $db->setQuery($query);
                                                $row = $db->loadObject();
                                            
                                                if ($row)
                                                {
                                                        $vars['album_id'] = $row->id;
                                                } 
                                        break;
                                        case 'category':
                                                $category = JCategories::getInstance('hwdMediaShare')->get();

                                                if (!$category)
                                                {
                                                        JError::raiseError(404, JText::_('COM_CONTENT_ERROR_PARENT_CATEGORY_NOT_FOUND'));
                                                        return $vars;
                                                }

                                                $categories = $category->getChildren();

                                                foreach ($segments as $segment)
                                                {
                                                        $segment = str_replace(':', '-', $segment);

                                                        foreach ($categories as $category)
                                                        {
                                                                if ($category->alias == $segment)
                                                                {
                                                                        $vars['category_id'] = $category->id;
                                                                        $categories = $category->getChildren();
                                                                        break;
                                                                }
                                                        }
                                                }
                                        break;
                                        case 'group':
                                                $query = $db->getQuery(true)
                                                        ->select($db->quoteName(array('id')))
                                                        ->from($db->quoteName('#__hwdms_groups'))
                                                        ->where($db->quoteName('alias') . ' = ' . $db->quote(JApplication::stringURLSafe($segments[1])));
                                                $db->setQuery($query);
                                                $row = $db->loadObject();
                                            
                                                if ($row)
                                                {
                                                        $vars['group_id'] = $row->id;
                                                }
                                        break;
                                        case 'playlist':
                                                $query = $db->getQuery(true)
                                                        ->select($db->quoteName(array('id')))
                                                        ->from($db->quoteName('#__hwdms_playlists'))
                                                        ->where($db->quoteName('alias') . ' = ' . $db->quote(JApplication::stringURLSafe($segments[1])));
                                                $db->setQuery($query);
                                                $row = $db->loadObject();
                                            
                                                if ($row)
                                                {
                                                        $vars['playlist_id'] = $row->id;
                                                }
                                        break;                                    
                                }
                                
                                $query = $db->getQuery(true)
                                        ->select($db->quoteName(array('id')))
                                        ->from($db->quoteName('#__hwdms_media'))
                                        ->where($db->quoteName('alias') . ' = ' . $db->quote(JApplication::stringURLSafe($segments[$count - 1])));
                                $db->setQuery($query);
                                $row = $db->loadObject();

                                if ($row)
                                {
                                        $vars['view'] = 'mediaitem';
                                        $vars['id'] = $row->id;

                                        return $vars;
                                }  
                        }
                }
                
                // Unable to parse, so redirect to homepage.
                require_once JPATH_ROOT . '/components/com_hwdmediashare/helpers/route.php';
                $app->redirect(JRoute::_('index.php?option=com_hwdmediashare'));               
        }
        
	/**
	 * Translates a term for use with SEF.
	 *
         * @access  public
	 * @param   array    $string   The string to translate.
	 * @param   boolean  $inverse  Inverse trace.
	 * @return  array    The translated string.
	 */
	public function translate($string, $inverse = false)
	{
                // Translate URL strings here.
                $segments = array(
                //  Original           Translated
                    'account'       => 'account',
                    'album'         => 'album',
                    'albumform'     => 'albumform',
                    'albummedia'    => 'albummedia',
                    'albums'        => 'albums',
                    'categories'    => 'categories',
                    'category'      => 'category',
                    'categoryform'  => 'categoryform',
                    'discover'      => 'discover',
                    'group'         => 'group',
                    'groupform'     => 'groupform',
                    'groupmedia'    => 'groupmedia',
                    'groupmembers'  => 'groupmembers',
                    'groups'        => 'groups',
                    'media'         => 'media',
                    'mediaform'     => 'mediaform',
                    'mediaitem'     => 'view',
                    'playlist'      => 'playlist',
                    'playlistform'  => 'playlistform',
                    'playlistmedia' => 'playlistmedia',
                    'playlists'     => 'playlists',
                    'search'        => 'search',
                    'slideshow'     => 'slideshow',
                    'upload'        => 'upload',
                    'channel'       => 'channel',
                    'channelform'   => 'channelform',
                    'channels'      => 'channels',                   
                );

                if (!$inverse && isset($segments[$string]))
                {
                        return JApplication::stringURLSafe($segments[$string]);
                }
                elseif ($inverse && in_array($string, $segments))
                {
                        if ($key = array_search($string, $segments))
                        {
                                return JApplication::stringURLSafe($key);
                        }
                }
                
                return $string;
        }        
}


<?php

/**
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2018 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
*/

namespace NRFramework;

defined( '_JEXEC' ) or die( 'Restricted access' );

class Extension
{
	/**
	 * Array including already loaded extensions
	 *
	 * @var array
	 */
	public static $cache = [];

	/**
	 * Get extension ID
	 *
	 * @param	string	$element	The extension element name
	 * @param	string	$type		The extension type: component, plugin, library e.t.c
	 * @param	mixed	$folder		The plugin folder: system, content e.t.c
	 *
	 * @return	mixed 	False on failure, Integer on success
	 */
	public static function getID($element, $type = 'component', $folder = null)
	{
		if (!$extension = self::get($element, $type, $folder))
		{
			return false;
		}

		return (int) $extension['extension_id'];
	}

	/**
	 * Get extension information from database
	 *
	 * @param	string	$element	The extension element name
	 * @param	string	$type		The extension type: component, plugin, library e.t.c
	 * @param	mixed	$folder		The plugin folder: system, content e.t.c
	 *
	 * @return	array
	 */
    public static function get($element, $type = 'component', $folder = null)
    {
		// Check if element is already cached
		$hash = md5($element . '_' . $type . '_' . $folder);
		if (isset(self::$cache[$hash]))
		{
			return self::$cache[$hash];
		}

		// Let's call the database
		$db = \JFactory::getDBO();

		switch ($type)
		{
			case 'component':
				$element = 'com_' . str_replace('com_', '', $element);
				break;
			case 'module':
				$element = 'mod_' . str_replace('mod_', '', $element);
				break;
		}
		
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('element') . ' = ' . $db->quote($element))
            ->where($db->quoteName('type') . ' = ' . $db->quote($type));

        if (!is_null($folder))
        {
            $query->where($db->quoteName('folder') . ' = ' . $db->quote($folder));
        }

		$db->setQuery($query);

		return self::$cache[$hash] = $db->loadAssoc();
	}

	/**
	 * Helper method to check if a plugin is enabled
	 *
	 * @param	string	$element	The extension element name
	 * @param	string	$type		The extension type: component, plugin, library e.t.c
	 *
	 * @return  boolean
	 */
	public static function pluginIsEnabled($element, $folder = 'system') 
	{
		return self::isEnabled($element, 'plugin', $folder);
	}

	/**
	 * Helper method to check if a component is enabled
	 *
	 * @param	string	$element	The component element name
	 *
	 * @return	boolean
	 */
	public static function componentIsEnabled($element) 
	{
		return self::isEnabled($element);
	}

	/**
	 * Checks if an extension is enabled
	 *
	 * @param	string	$element	The extension element name
	 * @param	string	$type		The extension type: component, plugin, library e.t.c
	 * @param	mixed	$folder		The plugin folder: system, content e.t.c
	 *
	 * @return	boolean
	 */
	public static function isEnabled($element, $type = 'component', $folder = 'system')
	{
		switch ($type)
		{
			case 'component':
				if (!$extension = self::get($element))
				{
					return false;
				}

				return (bool) $extension['enabled'];
				break;

			case 'plugin':
				if (!$extension = self::get($element, $type = 'plugin', $folder))
				{
					return false;
				}
		
				return (bool) $extension['enabled'];
				break;
		}
	}

	/**
     *  Checks if an extension is installed
     *
     *  @param   string  $extension  The extension element name
     *  @param   string  $type       The extension's type 
     *  @param   string  $folder     Plugin folder
     *
     *  @return  boolean             Returns true if extension is installed
     */
    public static function isInstalled($extension, $type = 'component', $folder = 'system')
    {
        $db = \JFactory::getDbo();

        switch ($type)
        {
			case 'component':
				$extension_data = self::get('com_' . str_replace('com_', '', $extension));
				return isset($extension_data['extension_id']);
                break;

            case 'plugin':
                return \JFile::exists(JPATH_PLUGINS . '/' . $folder . '/' . $extension . '/' . $extension . '.php');

            case 'module':
                return (\JFile::exists(JPATH_ADMINISTRATOR . '/modules/mod_' . $extension . '/' . $extension . '.php')
                    || \JFile::exists(JPATH_ADMINISTRATOR . '/modules/mod_' . $extension . '/mod_' . $extension . '.php')
                    || \JFile::exists(JPATH_SITE . '/modules/mod_' . $extension . '/' . $extension . '.php')
                    || \JFile::exists(JPATH_SITE . '/modules/mod_' . $extension . '/mod_' . $extension . '.php')
                );

            case 'library':
                return \JFolder::exists(JPATH_LIBRARIES . '/' . $extension);
        }

        return false;
    }
}
<?php
/**
 * @package     Joomla.site
 * @subpackage  Module.mod_media_images
 *
 * @copyright   Copyright (C) 2013 Highwood Design Limited. All rights reserved.
 * @license     GNU General Public License http://www.gnu.org/copyleft/gpl.html
 * @author      Dave Horsfall
 */

defined('_JEXEC') or die;

// Validate HWD dependencies and compatibility.
JLog::addLogger(array('text_file' => 'hwd.log.php'), JLog::ALL, array('mod_media_images'));           
if(JFile::exists(JPATH_ROOT . '/components/com_hwdmediashare/libraries/version.php'))
{
        JLoader::register('hwdMediaShareVersion', JPATH_ROOT . '/components/com_hwdmediashare/libraries/version.php');
        $HWDversion = new hwdMediaShareVersion();
        if (version_compare($HWDversion->RELEASE, '2', '<') || version_compare($HWDversion->RELEASE, '3', '>=')) 
        {
                JLog::add(JText::_('HWDMediaShare not compatible'), JLog::WARNING, 'mod_media_images');
                return;
        }
}
else
{               
        JLog::add(JText::_('HWDMediaShare not installed'), JLog::WARNING, 'mod_media_images');
        return;
}

JLoader::register('modMediaImagesHelper', dirname(__FILE__).'/helper.php');

$helper = new modMediaImagesHelper($module, $params);

require JModuleHelper::getLayoutPath('mod_media_images', $params->get('layout', 'default'));

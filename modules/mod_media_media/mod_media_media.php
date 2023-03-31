<?php
/**
 * @package     Joomla.site
 * @subpackage  Module.mod_media_media
 *
 * @copyright   Copyright (C) 2013 Highwood Design Limited. All rights reserved.
 * @license     GNU General Public License http://www.gnu.org/copyleft/gpl.html
 * @author      Dave Horsfall
 */

defined('_JEXEC') or die;

// Check for media item only display
$app = JFactory::getApplication();
if ($params->get('page_assignment', 1) == 0 && ($app->input->get('option') != 'com_hwdmediashare' || $app->input->get('view') != 'mediaitem')) return false;
    
JLoader::register('modMediaMediaHelper', dirname(__FILE__).'/helper.php');

$helper = new modMediaMediaHelper($module, $params);

require JModuleHelper::getLayoutPath('mod_media_media', $params->get('layout', 'default'));

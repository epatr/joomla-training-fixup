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

$document = JFactory::getDocument();
$document->addStyleSheet(JURI::root() . "media/com_hwdmediashare/assets/css/hwd.css");

// Load HWD config.
$hwdms = hwdMediaShareFactory::getInstance();
$config = $hwdms->getConfig();
$config->set('mediaitem_size', 900);
?>  
<div id="hwd-container">
  <?php echo hwdMediaShareMedia::get($this->item); ?>
</div>
<?php 
// This modal window is loaded through AJAX in the administrator. Duplicating 
// the load on some of the core JS frameworks causes issues after the modal 
// has been closed, so we remove unwanted framework files. 
$dontInclude = array(
JURI::root(true) . '/media/jui/js/jquery.js',
JURI::root(true) . '/media/jui/js/jquery.min.js',
JURI::root(true) . '/media/jui/js/jquery-noconflict.js',
JURI::root(true) . '/media/jui/js/jquery-migrate.js',
JURI::root(true) . '/media/jui/js/jquery-migrate.min.js',
JURI::root(true) . '/media/jui/js/bootstrap.js',
JURI::root(true) . '/media/system/js/core-uncompressed.js',
JURI::root(true) . '/media/system/js/core.js',
JURI::root(true) . '/media/system/js/mootools-core.js',
JURI::root(true) . '/media/system/js/mootools-core-uncompressed.js',
);

foreach($document->_scripts as $key => $script)
{
    if(in_array($key, $dontInclude))
    {
        unset($document->_scripts[$key]);
    }
}
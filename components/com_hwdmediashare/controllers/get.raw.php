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

class hwdMediaShareControllerGet extends JControllerForm
{        
	/**
	 * Method to deliver a media file through HWD.
	 * @return void
	 */
        public static function file()
        {
                hwdMediaShareFactory::load('files');
                hwdMediaShareFactory::load('downloads');
                hwdMediaShareDownloads::push();
		JFactory::getApplication()->close();
        }
        
	/**
	 * Method to render the display html of a media item
	 * @since	0.1
	 */
        function html()
        {
                $id = JRequest::getInt( 'id' , '' );
                if ($id > 0)
                {
                        // Load group
                        JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_hwdmediashare/tables');
                        $table =& JTable::getInstance('Media', 'hwdMediaShareTable');
                        $table->load( $id );

                        $properties = $table->getProperties(1);
                        $row = JArrayHelper::toObject($properties, 'JObject');
                }
                else
                {
			return;
                }
                
                hwdMediaShareFactory::load('media');
                hwdMediaShareFactory::load('downloads');

                $row->media_type = hwdMediaShareMedia::loadMediaType($row);

                $html = hwdMediaShareMedia::get($row);
                print $html;
                
                // Exit the application.
                return;
        }
}

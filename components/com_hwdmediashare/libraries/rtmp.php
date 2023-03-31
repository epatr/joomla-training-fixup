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

class hwdMediaShareRtmp extends JObject
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
	 * Returns the hwdMediaShareRtmp object, only creating it if it
	 * doesn't already exist.
	 *
	 * @access  public
         * @static
	 * @return  hwdMediaShareRtmp Object.
	 */ 
	public static function getInstance()
	{
		static $instance;

		if (!isset ($instance))
                {
			$c = 'hwdMediaShareRtmp';
                        $instance = new $c;
		}

		return $instance;
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

                // Load streaming library.
                hwdMediaShareFactory::load('streaming');

                if ($item->streamer && $item->file)
                {
                        // Create an RTMP object.
                        $object = new StdClass;
                        $object->type = 1;
                        $object->quality = 240;
                        if (substr($item->streamer, -1) != '/' && substr($item->streamer, 0, 1) != '/')
                        {
                                $object->url = $item->streamer . '/' . $item->file;
                        }
                        else
                        {
                                $object->url = $item->streamer . $item->file;
                        }
                        
                        // Create streaming source.
                        $sources = array();
                        $sources[] = $object;

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
}

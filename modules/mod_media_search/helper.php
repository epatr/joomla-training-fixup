<?php
/**
 * @package     Joomla.site
 * @subpackage  Module.mod_media_search
 *
 * @copyright   Copyright (C) 2013 Highwood Design Limited. All rights reserved.
 * @license     GNU General Public License http://www.gnu.org/copyleft/gpl.html
 * @author      Dave Horsfall
 */

defined('_JEXEC') or die;

class modMediaSearchHelper
{
	/**
	 * Class constructor.
	 *
	 * @access  public
	 * @param   array  $module  The module object.
	 * @param   array  $params  The module parameters.
         * @return  void
	 */       
	public function __construct($module, $params)
	{
                // Load HWD assets.
                JLoader::register('hwdMediaShareFactory', JPATH_ROOT.'/components/com_hwdmediashare/libraries/factory.php');
                JLoader::register('hwdMediaShareHelperRoute', JPATH_ROOT.'/components/com_hwdmediashare/helpers/route.php');

                // Include JHtml helpers.
                JHtml::addIncludePath(JPATH_ROOT.'/administrator/components/com_hwdmediashare/helpers/html');
                JHtml::addIncludePath(JPATH_ROOT.'/components/com_hwdmediashare/helpers/html');
                
                // Import HWD libraries.                
                hwdMediaShareFactory::load('activities');
                hwdMediaShareFactory::load('downloads');
                hwdMediaShareFactory::load('files');
                hwdMediaShareFactory::load('media');
                hwdMediaShareFactory::load('thumbnails');
		hwdMediaShareFactory::load('utilities');

                // Load HWD config, merge with module parameters (and force reset).
                $hwdms = hwdMediaShareFactory::getInstance();
                $config = $hwdms->getConfig($params, true);

                // Load lite CSS.
                $config->set('load_lite_css', 1);
                
                // Load the HWD language file.
                $lang = JFactory::getLanguage();
                $lang->load('com_hwdmediashare', JPATH_SITE, $lang->getTag());
                
                // Get data.
                $this->module = $module;                
                $this->params = $config;                
                $this->utilities = hwdMediaShareUtilities::getInstance();
                $this->return = base64_encode(JFactory::getURI()->toString());

                // Add assets to the head tag.
                $this->addHead();
	}

	/**
	 * Method to add page assets to the <head> tags.
	 *
	 * @access  public
         * @return  void
	 */        
	public function addHead()
	{
                // Initialise variables.
		$doc = JFactory::getDocument();
                
                // Add page assets.
                JHtml::_('hwdhead.core', $this->params);
	}       
}

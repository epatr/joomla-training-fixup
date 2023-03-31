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

class hwdMediaShareControllerSearch extends JControllerLegacy
{
	/**
	 * Method to validate and sanitise data from the request and redirect.
	 *
	 * @access  public
	 * @return  void
	 */
        public function processForm()
	{          
                // Initialise variables.
                $app = JFactory::getApplication();

                // Retrieve filtered jform data.
                $jform = $app->input->getArray(array(
                    'jform' => array(
                        'keywords' => 'string',
                        'ordering' => 'string',
                        'limit' => 'int',
                        'area' => 'int',
                        'catid' => 'int',
                    )
                ));
                
                $data = $jform['jform'];

                // Slashes cause errors, <> get stripped anyway later on. # causes problems.
		$badchars = array('#','>','<','\\');
                
                // Further checks.
                $data['keywords'] = trim(str_replace($badchars, '', $data['keywords']));

                // Validate custom fields.

                
                // Check for valid Itemid.
                if ($app->input->get('Itemid', 0, 'integer') == 0)
                {
                        $menus = $app->getMenu('site');
                        $items = $menus->getItems('link', 'index.php?option=com_hwdmediashare&view=search');
                        $item = reset($items);
                        $Itemid = isset($item->id) ? $item->id : 0;
                        $app->input->set('Itemid', $Itemid);
                }
                
                // Save the data in the session.
                $app->setUserState('com_hwdmediashare.search.data', $data);
                
                // Construct redirect.
		$uri = JURI::getInstance();
		$uri->setVar('option', 'com_hwdmediashare');
		$uri->setVar('view', 'search');
		$uri->setVar('Itemid', $app->input->get('Itemid', '', 'integer'));
		$uri->setVar('type', $app->input->get('type', 'media', 'word'));
		$uri->setVar('mode', $app->input->get('mode', 'advanced', 'word'));
		$uri->setVar('keywords', $data['keywords']);
		$uri->setVar('uid', md5(serialize($data)));
                                
		$this->setRedirect(JRoute::_('index.php'.$uri->toString(array('query', 'fragment')), false));
	}
        
	/**
	 * Method to clear the search form.
	 *
	 * @access  public
	 * @return  void
	 */
        public function clear()
	{          
                // Initialise variables.
                $app = JFactory::getApplication();
                
                // Save the data in the session.
                $app->setUserState('com_hwdmediashare.search.data', array());
                
                // Construct redirect.
		$uri = JURI::getInstance();
		$uri->setVar('option', 'com_hwdmediashare');
		$uri->setVar('view', 'search');
		$uri->setVar('type', $app->input->get('type', 'media', 'word'));
		$uri->setVar('mode', $app->input->get('mode', 'standard', 'word'));
		$uri->setVar('keywords', '');
                                
		$this->setRedirect(JRoute::_('index.php'.$uri->toString(array('query', 'fragment')), false));
	}     

	/**
	 * Method to toggle advanced search.
	 *
	 * @access  public
	 * @return  void
	 */
        public function advanced()
	{          
                // Initialise variables.
                $app = JFactory::getApplication();
                $app->input->set('mode', 'advanced');
                $this->processForm();
	}           
}

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

class hwdMediaShareViewLinkedPages extends JViewLegacy
{
	/**
	 * Display the view.
	 *
	 * @access  public
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 * @return  void
	 */
	public function display($tpl = null)
	{
                // Get data from the model.
                $this->items = $this->get('Items');
                $this->pagination = $this->get('Pagination');
		$this->state = $this->get('State');
                $this->mediaId = JFactory::getApplication()->input->get('media_id', '', 'int');

                // Import HWD libraries.
                hwdMediaShareFactory::load('downloads');
                hwdMediaShareFactory::load('thumbnails');

                // Check for errors.
                if (count($errors = $this->get('Errors')))
                {
                        JError::raiseError(500, implode('<br />', $errors));
                        return false;
                }

		// Display the template.
		parent::display($tpl);

		$this->document->addStyleSheet(JURI::root() . "media/com_hwdmediashare/assets/css/administrator.css");                  
	}
}

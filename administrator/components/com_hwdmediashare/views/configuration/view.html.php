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

class hwdMediaShareViewConfiguration extends JViewLegacy 
{
    	protected $state;

	protected $item;

	protected $form;
        
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
		$this->state	= $this->get('State');
		$this->item	= $this->get('Item');
		$this->form	= $this->get('Form');
                
                // Check for errors.
                if (count($errors = $this->get('Errors')))
                {
                        JError::raiseError(500, implode('<br />', $errors));
                        return false;
                }

		// We don't need toolbar in the modal window.
		if ($this->getLayout() !== 'modal')
		{
			$this->addToolbar();
			$this->sidebar = JHtmlSidebar::render();
		}
                
		// Display the template.
		parent::display($tpl);
                
		$this->document->addStyleSheet(JURI::root() . "media/com_hwdmediashare/assets/css/administrator.css");  
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @access  protected
	 * @return  void
	 */
	protected function addToolBar()
	{
		$canDo = hwdMediaShareHelper::getActions();

		JToolBarHelper::title(JText::_('COM_HWDMS_CONFIGURATION'), 'cog');

		if ($canDo->get('core.edit'))
		{
			JToolbarHelper::apply('configuration.apply');
			JToolbarHelper::save('configuration.save');
		}
		JToolbarHelper::cancel('configuration.cancel', 'JTOOLBAR_CLOSE');
		JToolbarHelper::help('HWD', false, 'http://hwdmediashare.co.uk/learn/docs'); 
	}
}

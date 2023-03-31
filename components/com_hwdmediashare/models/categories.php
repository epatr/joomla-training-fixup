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

class hwdMediaShareModelCategories extends JModelList
{
	/**
	 * Model context string.
         * 
         * @access      public
	 * @var         string
	 */    
	public $context = 'com_hwdmediashare.categories';

	/**
	 * Model extension string.
         * 
         * @access      public
	 * @var         string
	 */ 
	public $extension = 'com_hwdmediashare';
        
	/**
	 * The node items.
         * 
         * @access      protected
	 * @var         object
	 */ 
	protected $_items = null;
        
	/**
	 * The parent of this node.
         * 
         * @access      protected
	 * @var         object
	 */ 
	protected $_parent = null;

	/**
	 * Class constructor.
	 *
	 * @access	public
	 * @param       array       $config     An optional associative array of configuration settings.
         * @return      void
	 */  
	public function __construct($config = array())
	{
		parent::__construct($config);
	}
        
	/**
	 * Method to get a list of items.
	 *
	 * @access  public
	 * @return  mixed  An array of data items on success, false on failure.
	 */
	public function getItems()
	{
		// Initialise variables.
		$app = JFactory::getApplication();
                                
                // Get HWD config.
                $hwdms = hwdMediaShareFactory::getInstance();
                $config = $hwdms->getConfig();
                
		if(!count($this->_items))
		{
			$options = array();
                        $options['countItems'] = $config->get('category_list_meta_media_count') != 0 ? 1 : 0;
                        $categories = JCategories::getInstance('hwdMediaShare', $options);
			$this->_parent = $categories->get($this->getState('filter.parentId'));
                        if(is_object($this->_parent))
			{
				$this->_items = $this->_parent->getChildren();
			}
                        else
                        {
				$this->_items = false;
			}
		}

                // Exclude categories from the top level.
                $this->exclude = $app->getParams()->get('exclude', array());
                if (is_array($this->exclude))
                {
			// Make sure the ids are integers.
			jimport('joomla.utilities.arrayhelper');
			JArrayHelper::toInteger($this->exclude);
                        foreach($this->_items as $id => $item)
                        {       
                                if (in_array($item->id, $this->exclude))
                                {                            
                                        unset($this->_items[$id]);
                                        $this->_items = array_values($this->_items); // 'reindex' array so columns are displayed correctly in the template.
                                        continue;
                                }

                                if (count($item->getChildren()) > 0)
                                {
                                        $this->removeExcludedChildren($item->getChildren());
                                }                        
                        }
                }

		return $this->_items;
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @access  protected
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 * @return  void
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// Initialise variables.
		$app = JFactory::getApplication();
                $user = JFactory::getUser();

		// Load the parameters.
                $hwdms = hwdMediaShareFactory::getInstance();
                $config = $hwdms->getConfig();
                $this->setState('params', $config);
                
		// Define the extension filter.
		$this->setState('filter.extension', $this->extension);

		// Get the parent id if defined.
		$this->setState('filter.parentId', $app->getParams()->get('parent', 0));
                
		if ((!$user->authorise('core.edit.state', 'com_hwdmediashare')) && (!$user->authorise('core.edit', 'com_hwdmediashare')))
                {
			// Limit to published for people who can't edit or edit.state.
			$this->setState('filter.published',	1);
			$this->setState('filter.status',	1);

			// Filter by start and end dates.
			$this->setState('filter.publish_date', true);
		}
                else
                {
			// Allow access to unpublished and unapproved items.
			$this->setState('filter.published',	array(0,1));
			$this->setState('filter.status',	array(0,1,2,3));
                }

                if (!defined('_JCLI') && $app->isSite())
                {
                        $this->setState('filter.language', $app->getLanguageFilter());
                }
                
		// Load the display state.
		$display = $this->getUserStateFromRequest('media.display_categories', 'display', $config->get('category_list_default_display', 'tree'), 'word', false);
                if (!in_array(strtolower($display), array('details', 'tree'))) $display = 'tree';
		$this->setState('media.display_categories', $display);
      
		// List state information.
		parent::populateState($ordering, $direction);
	}

	/**
	 * Get the parent of this node
	 *
         * @access  public
	 * @return  void
	 */
	public function getParent()
	{
		if(!is_object($this->_parent))
		{
			$this->getItems();
		}
		return $this->_parent;
	}

	/**
	 * Get the parent of this node
	 *
         * @access  public
	 * @return  void
	 */
	public function removeExcludedChildren($items)
	{
		// Initialise variables.
		$app = JFactory::getApplication();
                
                foreach($items as $id => $item)
                {       
                        if (in_array($item->id, $this->exclude))
                        {                            
                                $item->removeChild($item->id);
                                continue;
                        }
                        
                        if (count($item->getChildren()) > 0)
                        {
                                $this->removeExcludedChildren($item->getChildren());
                        }                         
                      
                }
	}   
}

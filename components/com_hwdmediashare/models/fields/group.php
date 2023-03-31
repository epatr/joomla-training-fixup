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

JFormHelper::loadFieldClass('list');

class JFormFieldGroup extends JFormFieldList
{
	/**
	 * The name of the form field type.
         * 
         * @access  protected
	 * @var     string
	 */
	protected $type = 'Group';

	/**
	 * Method to get the field options.
	 *
	 * @access  protected
	 * @return  array      The field option objects.
	 */
	protected function getOptions()
	{
                // Initialise variables.
                $user = JFactory::getUser();

                // Get HWD config.
                $hwdms = hwdMediaShareFactory::getInstance();
                $config = $hwdms->getConfig();
                
                // Initialise variables.
		$options = array();
                $options[] = JHtml::_('select.option', '', JText::_('COM_HWDMS_LIST_SELECT_GROUP'));
   
                JModelLegacy::addIncludePath(JPATH_ROOT.'/components/com_hwdmediashare/models');
                $model = JModelLegacy::getInstance('Groups', 'hwdMediaShareModel', array('ignore_request' => true));
                $model->populateState();
                $model->setState('filter.author_id', $user->id);
                $model->setState('filter.author_id.include', 1);

                if ($groups = $model->getItems())
                {
                        foreach ($groups as $group)
                        {
                                $options[] = JHtml::_('select.option', $group->id, $group->title);  
                        }
                }
                
                // Merge any additional options in the XML definition.
		$options = array_merge(parent::getOptions(), $options);

		return $options;
	}
}

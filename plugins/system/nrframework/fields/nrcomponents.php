<?php
/**
 * @author          Tassos.gr <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2018 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die('Restricted access');

jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

class JFormFieldNRComponents extends JFormFieldList
{
    protected function getOptions()
    {
        return array_merge(parent::getOptions(), $this->getInstalledComponents());
    }

	/**
	 *  Method to get field parameters
	 *
	 *  @param   string  $val      Field parameter
	 *  @param   string  $default  The default value
	 *
	 *  @return  string
	 */
	public function get($val, $default = '')
	{
		return (isset($this->element[$val]) && (string) $this->element[$val] != '') ? (string) $this->element[$val] : $default;
	}
    
    /**
     *  Creates a list of installed components
     *
     *  @return array
     */
    protected function getInstalledComponents()
    {
        $lang = JFactory::getLanguage();
        $db   = JFactory::getDbo();

        $components = $db->setQuery(
            $db->getQuery(true)
                ->select('name, element')
                ->from('#__extensions')
                ->where('type = ' . $db->quote('component'))
                ->where('name != ""')
                ->where('element != ""')
                ->where('enabled = 1')
                ->order('element, name')
        )->loadObjectList();

        $comps = array();
        
        foreach ($components as $component)
        {
            // Make sure we have a valid element
            if (empty($component->element))
            {
                continue;
            }

            // Skip backend-based only components
            if ($this->get('frontend', false))
            {
                $component_folder = JPATH_SITE . '/components/' . $component->element;

                if (!\JFolder::exists($component_folder))
                {
                    continue;
                }

                if (!\JFolder::exists($component_folder . '/views') && !\JFolder::exists($component_folder . '/View'))
                {
                    continue;
                }
            }

            // Try loading component's system language file in order to display a user friendly component name
            // Runs only if the component's name is not translated already.
            if (strpos($component->name, ' ') === false)
            {   
                $filename  = $component->element . '.sys';
                $adminpath = JPATH_ADMINISTRATOR . '/components/' . $component->element;

                // Discover component's language file 
                $lang->load($filename, JPATH_BASE, null)
                || $lang->load($filename, $adminpath, null)
                || $lang->load($filename, JPATH_BASE, $lang->getDefault())
                || $lang->load($filename, $adminpath, $lang->getDefault());

                // Translate component's name
                $component->name = JText::_(strtoupper($component->name));
            }

            $comps[strtolower($component->element)] = $component->name;
        }

        asort($comps);

        $options = array();

        foreach ($comps as $key => $name)
        {
            $options[] = JHtml::_('select.option', $key, $name);
        }

        return $options;
    }
}

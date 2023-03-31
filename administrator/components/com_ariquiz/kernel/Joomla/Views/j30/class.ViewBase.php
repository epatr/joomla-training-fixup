<?php 
/*
 *
 * @package		ARI Framework
 * @author		ARI Soft
 * @copyright	Copyright (c) 2011 www.ari-soft.com. All rights reserved
 * @license		GNU/GPL (http://www.gnu.org/copyleft/gpl.html)
 * 
 */

(defined('_JEXEC') && defined('ARI_FRAMEWORK_LOADED')) or die;

class AriViewBase extends JViewLegacy
{
	public function getName()
	{
		if (empty($this->_name))
		{
			$classname = get_class($this);
			$viewpos = strpos($classname, 'View');

			if ($viewpos === false)
			{
				throw new Exception(JText::_('JLIB_APPLICATION_ERROR_VIEW_GET_NAME'), 500);
			}

			$this->_name = strtolower(substr($classname, $viewpos + 4));
		}

		return $this->_name;
	}

}
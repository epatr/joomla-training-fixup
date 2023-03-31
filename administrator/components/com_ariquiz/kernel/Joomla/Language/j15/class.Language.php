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

jimport('joomla.language.language');

class AriLanguage extends JLanguage
{
	function __construct($lang = null)
	{
		if (is_null($lang))
		{
			$lang =& JFactory::getLanguage(); 
			$lang = $lang->get('tag');
		}

		parent::__construct($lang);
		
		$this->_strings = array();
	}
	
	function getMessages()
	{
		return $this->_strings;
	}
}
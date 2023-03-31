<?php
/*
 * ARI Framework
 *
 * @package		ARI Framework
 * @version		1.0.0
 * @author		ARI Soft
 * @copyright	Copyright (c) 2009 www.ari-soft.com. All rights reserved
 * @license		GNU/GPL (http://www.gnu.org/copyleft/gpl.html)
 * 
 */

(defined('_JEXEC') && defined('ARI_FRAMEWORK_LOADED')) or die('Direct Access to this location is not allowed.');

class AriPluginProcessHelper
{
	function processTags($content, $addOutputContent = false, $exclude = array())
	{
		if (empty($content)) 
			return $content;
		
		$preContent = '';

		// Hack
		if (!class_exists('JDate')) 
			$d = JFactory::getDate(); 

		$oldHeadData = null;
		$document=& JFactory::getDocument();
		if ($addOutputContent)
		{
			@ob_start();

			if ($document->getType() == 'html') 
				$oldHeadData = $document->getHeadData();		
		}
			
		$content = AriPluginProcessHelper::processPlugins($content);
			
		if ($addOutputContent)
		{
			$document=& JFactory::getDocument();
			if($document->getType() == 'html') 
			{
				$newHeadData = $document->getHeadData();
				$newScript = isset($newHeadData['script']) && !in_array('script', $exclude) 
					? $newHeadData['script'] 
					: array();
				$newScripts = isset($newHeadData['scripts']) && !in_array('scripts', $exclude) 
					? $newHeadData['scripts'] 
					: array();
				$newCustom = isset($newHeadData['custom']) && !in_array('custom', $exclude) 
					? $newHeadData['custom'] 
					: array();

				if (!empty($newScript) || !empty($newScripts) || !empty($newCustom))
				{
					if (empty($oldHeadData)) 
						$oldHeadData = array();
					
					$oldScript = isset($oldHeadData['script']) 
						? $oldHeadData['script'] 
						: array();
					$oldScripts = isset($oldHeadData['scripts']) 
						? $oldHeadData['scripts'] 
						: array();
					$oldCustom = isset($oldCustom['custom']) 
						? $oldCustom['custom'] 
						: array();
					foreach ($newScripts as $script => $scriptType)
					{
						if (!array_key_exists($script, $oldScripts))
							$preContent .= sprintf('<script type="%s" src="%s"></script>', $scriptType, $script);
					}
						
					foreach ($newScript as $script)
					{
						if (!in_array($script, $oldScript))
							$preContent .= sprintf('<script type="text/javascript">%s</script>', $script);
					}
					
					foreach ($newCustom as $customTag)
					{
						if (preg_match('~(<script.+?</script>)~si', $customTag) && !in_array($customTag, $oldCustom))
							$preContent .= $customTag;
					}
				}
			}

			$content = @ob_get_contents() . $content;
			@ob_end_clean();
		}

		$content = $preContent . $content;

		return $content;
	}

	function processPlugins($content, $params = null)
	{
		$dispatcher	=& JDispatcher::getInstance(); 
		JPluginHelper::importPlugin('content', null, true);
		if (is_null($params) && J1_5)   
    {                                     
      AriKernel::import('Joomla.Html.ParameterLoader');
			$params = new AriJParameterBase('');
    }
		
		$isObject = is_object($content);
		
		$row = $content;
		if (!$isObject)
		{
			$row = new stdClass();
			$row->title = '';
			$row->text = $content;
		} 

		if (!J1_6)
			$dispatcher->trigger('onPrepareContent', array(&$row, &$params, 0), true);
		else 
			$dispatcher->trigger('onContentPrepare', array('com_ariquiz.question', &$row, &$params, 0), true);
		
		return $isObject ? $row : $row->text;
	}	
}
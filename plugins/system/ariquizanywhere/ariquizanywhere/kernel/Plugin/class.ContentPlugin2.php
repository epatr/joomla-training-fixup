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

AriKernel::import('Mambot.MambotHelper');

class AriContentPlugin2 extends JObject 
{
	var $_tag;
	var $_params;
	var $_parser;

	function __construct($params = null)
	{
		$this->_params = $params;
	}
	
	function processContentDefault($content) 
	{
		$parser = array(&$this, 'pluginParser');
		
		return $this->processContent($content, $parser);
	}

	function processContent($content, &$parser) 
	{
		if (empty($parser)) 
			$parser = array(&$this, 'pluginParser');

		$this->_parser = $parser;
		$tag = $this->_tag;
		$content = preg_replace_callback(
			'/\{' . $tag . '(\s+[a-z\_0-9]+=(?:"[^"]*"|&quot;.*?&quot;|[^\s}]*)*)\s*\}(?:(.*?)\{\/' . $tag . '\})?/si', 
			array(&$this, 'replacePlugin'),  
			$content
		);

		$this->_parser = null;

		return $content;
	}
	
	function replacePlugin($matches) 
	{
		if (empty($matches[0])) 
			return '';

		return call_user_func(
			$this->_parser, 
			$this->extractAttrs($matches[1]), 
			(!empty($matches[2]) ? $matches[2] : ''), 
			$matches[0]
		);
	}

	function extractAttrs($code) 
	{
		$attrs = array();
		if (empty($code))
			return $attrs;

		$matches = null;
		preg_match_all(
			'/([a-z\_0-9]+)=("[^"]*"|&quot;.*?&quot;|[^\s]*)/i', 
			$code, 
			$matches, 
			PREG_SET_ORDER
		);

		if (is_array($matches))
		{
			foreach ($matches as $match)
			{
				if (!empty($match[2]) && !empty($match[1])) 
					$attrs[$match[1]] = trim(html_entity_decode($match[2]), '"');
			}
		}

		return $attrs;
	}

	function pluginParser($attrs, $content, $baseContent) 
	{
		return $baseContent;	
	}	
}
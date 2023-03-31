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

class JDocumentRendererXSPF extends JDocumentRenderer
{
	/**
	 * Renderer mime type.
	 *
         * @access  portected
	 * @var     string
	 */
	protected $_mime = "application/xspf+xml";

	/**
	 * Render the XSPF version 1 Specification feed.
	 *
         * @access  public
	 * @param   string  $name     The name of the element to render
	 * @param   array   $params   Array of values
	 * @param   string  $content  Override the output of the renderer
	 * @return  string  The output.
	 */
	public function render($name = 'mrss', $params = null, $content = null)
	{
		$app	= JFactory::getApplication();

		// Gets and sets timezone offset from site configuration
		$tz	= new DateTimeZone($app->getCfg('offset'));
		$now	= JFactory::getDate();
		$now->setTimeZone($tz);

		$data	= &$this->_doc;

		$uri = JFactory::getURI();
		$url = $uri->toString(array('scheme', 'user', 'pass', 'host', 'port'));
		$syndicationURL = JRoute::_('&format=feed&type=xspf');

		if ($app->getCfg('sitename_pagetitles', 0) == 1) {
			$title = JText::sprintf('JPAGETITLE', $app->getCfg('sitename'), $data->title);
		}
		elseif ($app->getCfg('sitename_pagetitles', 0) == 2) {
			$title = JText::sprintf('JPAGETITLE', $data->title, $app->getCfg('sitename'));
		}
		else {
			$title = $data->title;
		}

		$feed_title = htmlspecialchars($title, ENT_COMPAT, 'UTF-8');

		$feed = "<playlist version=\"1\" xmlns=\"http://xspf.org/ns/0/\">\n";
		$feed.= "	<title>".$feed_title."</title>\n";
		$feed.= "	<trackList>\n";

		for ($i=0, $count = count($data->items); $i < $count; $i++)
		{
			if ($data->items[$i]->enclosure != NULL)
			{                        
                                if ((strpos($data->items[$i]->link, 'http://') === false) and (strpos($data->items[$i]->link, 'https://') === false)) {
                                        $data->items[$i]->link = str_replace(' ','%20',$url.$data->items[$i]->link);
                                }
                                $feed.= "		<track>\n";
                                $feed.= "			<title>".htmlspecialchars(strip_tags($data->items[$i]->title), ENT_COMPAT, 'UTF-8')."</title>\n";
                                $feed.= "			<creator></creator>\n";
                                $feed.= "			<info>".str_replace(' ','%20',$data->items[$i]->link)."</info>\n";
                                $feed.= "			<annotation><![CDATA[".$this->_relToAbs($data->items[$i]->description)."]]></annotation>\n";
                                $feed.= "                       <location>".$this->_relToAbs($data->items[$i]->enclosure->url)."</location>\n";
                                $feed.= "                       <image>".$this->_relToAbs($data->items[$i]->image)."</image>\n";
                                $feed.= "		</track>\n";
                        }
		}                         
		$feed.= "	</trackList>\n";
		$feed.= "</playlist>\n";
		return $feed;
	}

	/**
	 * Convert links in a text from relative to absolute
	 *
	 * @param   string  $text  The text processed
	 *
	 * @return  string   Text with converted links
	 *
	 * @since   11.1
	 */
	public function _relToAbs($text)
	{
		$base = JURI::base();
		$text = preg_replace("/(href|src)=\"(?!http|ftp|https|mailto|data)([^\"]*)\"/", "$1=\"$base\$2\"", $text);

		return $text;
	}
}

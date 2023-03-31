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

class JDocumentRendererRSSGEO extends JDocumentRenderer
{
	/**
	 * Renderer mime type.
	 *
         * @access  portected
	 * @var     string
	 */
	protected $_mime = "application/rss+xml";

	/**
	 * Render the RSSGEO feed, that implements WGS84 Geo Positioning
	 *
         * @access  public
	 * @param   string  $name     The name of the element to render
	 * @param   array   $params   Array of values
	 * @param   string  $content  Override the output of the renderer
	 * @return  string  The output.
	 */
	public function render($name = 'rssgeo', $params = null, $content = null)
	{
                // Initialise variables.
		$app = JFactory::getApplication();
                $data = $this->_doc;
                
		// Gets and sets timezone offset from site configuration
		$tz = new DateTimeZone($app->getCfg('offset'));
		$now = JFactory::getDate();

                // Define the feed URL.
		$uri = JFactory::getURI();
		$url = $uri->toString(array('scheme', 'user', 'pass', 'host', 'port'));
		$syndicationURL = JRoute::_('&format=feed&type=mrss');

                // Define the title.
		$feed_title = htmlspecialchars($data->title, ENT_COMPAT, 'UTF-8');

		$feed = "<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\" xmlns:media=\"http://search.yahoo.com/mrss/\" xmlns:jwplayer=\"http://rss.jwpcdn.com/\" xmlns:georss=\"http://www.georss.org/georss\" xmlns:gml=\"http://www.opengis.net/gml\">\n";
		$feed.= "	<channel>\n";
		$feed.= "		<title>".$feed_title."</title>\n";
		$feed.= "		<description>".$data->description."</description>\n";
		$feed.= "		<link>".str_replace(' ','%20',$url.$data->link)."</link>\n";
		$feed.= "		<lastBuildDate>".htmlspecialchars($now->toRFC822(true), ENT_COMPAT, 'UTF-8')."</lastBuildDate>\n";
		$feed.= "		<generator>".$data->getGenerator()."</generator>\n";
		$feed.= '		<atom:link rel="self" type="application/rss+xml" href="'.str_replace(' ','%20',$url.$syndicationURL)."\"/>\n";

		if ($data->image!=null) {
			$feed.= "		<image>\n";
			$feed.= "			<url>".$data->image->url."</url>\n";
			$feed.= "			<title>".htmlspecialchars($data->image->title, ENT_COMPAT, 'UTF-8')."</title>\n";
			$feed.= "			<link>".str_replace(' ','%20',$data->image->link)."</link>\n";
			if ($data->image->width != "") {
				$feed.= "			<width>".$data->image->width."</width>\n";
			}
			if ($data->image->height!="") {
				$feed.= "			<height>".$data->image->height."</height>\n";
			}
			if ($data->image->description!="") {
				$feed.= "			<description><![CDATA[".$data->image->description."]]></description>\n";
			}
			$feed.= "		</image>\n";
		}
		if ($data->language!="") {
			$feed.= "		<language>".$data->language."</language>\n";
		}
		if ($data->copyright!="") {
			$feed.= "		<copyright>".htmlspecialchars($data->copyright,ENT_COMPAT, 'UTF-8')."</copyright>\n";
		}
		if ($data->editorEmail!="") {
			$feed.= "		<managingEditor>".htmlspecialchars($data->editorEmail, ENT_COMPAT, 'UTF-8').' ('.
				htmlspecialchars($data->editor, ENT_COMPAT, 'UTF-8').")</managingEditor>\n";
		}
		if ($data->webmaster!="") {
			$feed.= "		<webMaster>".htmlspecialchars($data->webmaster, ENT_COMPAT, 'UTF-8')."</webMaster>\n";
		}
		if ($data->pubDate!="") {
			$pubDate = JFactory::getDate($data->pubDate);
			$pubDate->setTimeZone($tz);
			$feed.= "		<pubDate>".htmlspecialchars($pubDate->toRFC822(true), ENT_COMPAT, 'UTF-8')."</pubDate>\n";
		}
		if (empty($data->category) === false) {
			if (is_array($data->category)) {
				foreach ($data->category as $cat) {
					$feed.= "		<category>".htmlspecialchars($cat, ENT_COMPAT, 'UTF-8')."</category>\n";
				}
			}
			else {
				$feed.= "		<category>".htmlspecialchars($data->category, ENT_COMPAT, 'UTF-8')."</category>\n";
			}
		}
		if ($data->docs!="") {
			$feed.= "		<docs>".htmlspecialchars($data->docs, ENT_COMPAT, 'UTF-8')."</docs>\n";
		}
		if ($data->ttl!="") {
			$feed.= "		<ttl>".htmlspecialchars($data->ttl, ENT_COMPAT, 'UTF-8')."</ttl>\n";
		}
		if ($data->rating!="") {
			$feed.= "		<rating>".htmlspecialchars($data->rating, ENT_COMPAT, 'UTF-8')."</rating>\n";
		}
		if ($data->skipHours!="") {
			$feed.= "		<skipHours>".htmlspecialchars($data->skipHours, ENT_COMPAT, 'UTF-8')."</skipHours>\n";
		}
		if ($data->skipDays!="") {
			$feed.= "		<skipDays>".htmlspecialchars($data->skipDays, ENT_COMPAT, 'UTF-8')."</skipDays>\n";
		}

		for ($i=0, $count = count($data->items); $i < $count; $i++)
		{
			if ((strpos($data->items[$i]->link, 'http://') === false) and (strpos($data->items[$i]->link, 'https://') === false)) {
				$data->items[$i]->link = str_replace(' ','%20',$url.$data->items[$i]->link);
			}
			$feed.= "		<item>\n";
			$feed.= "			<title>".htmlspecialchars(strip_tags($data->items[$i]->title), ENT_COMPAT, 'UTF-8')."</title>\n";
			$feed.= "			<link>".str_replace(' ','%20',$data->items[$i]->link)."</link>\n";

			if (empty($data->items[$i]->guid) === true) {
				$feed.= "			<guid isPermaLink=\"true\">".str_replace(' ','%20',$data->items[$i]->link)."</guid>\n";
			}
			else {
				$feed.= "			<guid isPermaLink=\"false\">".htmlspecialchars($data->items[$i]->guid, ENT_COMPAT, 'UTF-8')."</guid>\n";
			}

			$feed.= "			<description><![CDATA[".$this->_relToAbs($data->items[$i]->description)."]]></description>\n";

			if ($data->items[$i]->authorEmail!="") {
				$feed.= "			<author>".htmlspecialchars($data->items[$i]->authorEmail . ' (' .
										$data->items[$i]->author . ')', ENT_COMPAT, 'UTF-8')."</author>\n";
			}
			/*
			// On hold
			if ($data->items[$i]->source!="") {
					$data.= "			<source>".htmlspecialchars($data->items[$i]->source, ENT_COMPAT, 'UTF-8')."</source>\n";
			}
			*/
			if (empty($data->items[$i]->category) === false) {
				if (is_array($data->items[$i]->category)) {
					foreach ($data->items[$i]->category as $cat) {
						$feed.= "			<category>".htmlspecialchars($cat, ENT_COMPAT, 'UTF-8')."</category>\n";
					}
				}
				else {
					$feed.= "			<category>".htmlspecialchars($data->items[$i]->category, ENT_COMPAT, 'UTF-8')."</category>\n";
				}
			}
			if ($data->items[$i]->comments!="") {
				$feed.= "			<comments>".htmlspecialchars($data->items[$i]->comments, ENT_COMPAT, 'UTF-8')."</comments>\n";
			}
			if ($data->items[$i]->date!="") {
				$itemDate = JFactory::getDate($data->items[$i]->date);
				$itemDate->setTimeZone($tz);
				$feed.= "			<pubDate>".htmlspecialchars($itemDate->toRFC822(true), ENT_COMPAT, 'UTF-8')."</pubDate>\n";
			}
			
                        // Add internal media files.
                        if (count($data->items[$i]->mediafiles))
			{
                                hwdMediaShareFactory::load('files');

                                // For labels (JWPlayer)
                                $label_array = array(
                                    11 => '240p', 12 => '360p', 13 => '480p', 
                                    14 => '360p', 15 => '480p', 16 => '720p', 17 => '1080p',
                                    18 => '360p', 19 => '480p', 20 => '720p', 21 => '1080p',
                                    22 => '360p', 23 => '480p', 24 => '720p', 25 => '1080p',
                                );
                                
                                // For types 
                                $type_array = array(
                                    11 => 'video', 12 => 'video', 13 => 'video', 
                                    14 => 'video', 15 => 'video', 16 => 'video', 17 => 'video',
                                    18 => 'video', 19 => 'video', 20 => 'video', 21 => 'video',
                                    22 => 'video', 23 => 'video', 24 => 'video', 25 => 'video',
                                );
                                
                                foreach ($data->items[$i]->mediafiles as $mediafile)
                                {
                                        if ($file = hwdMediaShareFiles::getFileData($data->items[$i]->_media, $mediafile->file_type))
                                        {
                                                $feed.= '			<media:content ';
                                                $feed.= 'url = "' . $file->url . '" ';
                                                $feed.= 'type = "' . $file->type . '" ';
                                                $feed.= isset($label_array[$mediafile->file_type]) ? 'medium = "' . $type_array[$mediafile->file_type] . '" ': 'medium = "image" ';
                                                $feed.= 'duration = "1" ';
                                                $feed.= isset($label_array[$mediafile->file_type]) ? 'label = "' . $label_array[$mediafile->file_type] . '"': '';
                                                $feed.= '>';
                                                $feed.= "</media:content>\n";  
                                        }        
                                }
			}                      
                        
                        // Add remote media files.                        
			if ($data->items[$i]->_media->type == 7 && $data->items[$i]->_media->source)
                        {
                                hwdMediaShareFactory::load('documents');
                                $feed.= '			<media:content ';
                                $feed.= 'url="' . $data->items[$i]->_media->source . '" ';
                                $feed.= 'type="' . hwdMediaShareDocuments::getContentType(JFile::getExt($data->items[$i]->_media->source)) . '" ';
                                $feed.= 'medium="video">';
                                $feed.= "</media:content>\n";  
                        } 

                        // Add media thumbnail
                        if ($data->items[$i]->image)
                        {
                                hwdMediaShareFactory::load('documents');
                                $feed.= '			<media:thumbnail ';
                                $feed.= 'url="' . $this->_relToAbs($data->items[$i]->image) . '">';
                                $feed.= "</media:thumbnail>\n";  
                        }
                        
                        // Add geo data.
                        if ($data->items[$i]->location)
                        {
                                $geocode = file_get_contents('http://maps.google.com/maps/api/geocode/json?address='.urlencode($data->items[$i]->location).'&sensor=false');
                                $output = json_decode($geocode);
                                $lat = (isset($output->results[0]->geometry->location->lat) ? $output->results[0]->geometry->location->lat : null);
                                $long = (isset($output->results[0]->geometry->location->lng) ? $output->results[0]->geometry->location->lng : null);
                                if ($lat && $long) 
                                {
                                        $feed.= "			<georss:point>$lat $long</georss:point>\n";
                                        $feed.= "			<geo:lat>$lat</geo:lat>\n";
                                        $feed.= "                       <geo:long>$long</geo:long>\n";
										
					// Include linked thumbnail in content box of Google Maps.
                                        $feed.= "                       <content type=\"html\">&lt;p&gt;&lt;/p&gt; &lt;p&gt;&lt;a href=&quot;" . htmlspecialchars(htmlspecialchars($data->items[$i]->link)) . "&quot; title=&quot;" . htmlspecialchars(htmlspecialchars($data->items[$i]->title)) . "&quot;&gt;&lt;img src=&quot;" . htmlspecialchars(htmlspecialchars($this->_relToAbs($data->items[$i]->image))) . "&quot; width=&quot;240&quot; height=&quot;auto&quot; alt=&quot;" . htmlspecialchars(htmlspecialchars($data->items[$i]->title)) . "&quot; /&gt;&lt;/a&gt;&lt;/p&gt; &lt;p&gt;&lt;/p&gt;</content>\n";
										
                                } 
                        }

			$feed.= "		</item>\n";
		}
		$feed.= "	</channel>\n";
		$feed.= "</rss>\n";
		return $feed;
	}

	/**
	 * Convert links in a text from relative to absolute
	 *
         * @access  public
	 * @param   string  $text  The text to be processed.
	 * @return  string  Text with converted links.
	 */
	public function _relToAbs($text)
	{
		$base = JURI::base();
		$text = preg_replace("/(href|src)=\"(?!http|ftp|https|mailto|data)([^\"]*)\"/", "$1=\"$base\$2\"", $text);

		return $text;
	}
}

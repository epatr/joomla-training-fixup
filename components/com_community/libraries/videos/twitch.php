<?php
/**
* @copyright (C) 2013 iJoomla, Inc. - All rights reserved.
* @license GNU General Public License, version 2 (http://www.gnu.org/licenses/gpl-2.0.html)
* @author iJoomla.com <webmaster@ijoomla.com>
* @url https://www.jomsocial.com/license-agreement
* The PHP code portions are distributed under the GPL license. If not otherwise stated, all images, manuals, cascading style sheets, and included JavaScript *are NOT GPL, and are released under the IJOOMLA Proprietary Use License v1.0
* More info at https://www.jomsocial.com/license-agreement
*/

defined('_JEXEC') or die('Restricted access');

require_once (COMMUNITY_COM_PATH.'/models/videos.php');

/**
 * Class to manipulate data from Twitch
 *
 * @access	public
 */
class CTableVideoTwitch extends CVideoProvider
{
	var $xmlContent = null;
	var $url 		= '';
	var $videoId	= '';
	var $data 		= false;

	/**
	 * Return feedUrl of the video
	 */
	public function getFeedUrl()
	{  
		return 'https://player.twitch.tv/?autoplay=false&video=' . $this->videoId;
	}

	public function getData() {
		if ($this->data) {
			return $this->data;
		}

		if (preg_match('/clips\.twitch\.tv/', $this->url)) {
			preg_match('/clips\.twitch\.tv\/[A-z]*/', $this->url, $matches);
			$this->url = 'https://' . $matches[0];
		}

		$url = 'https://api.twitch.tv/v5/oembed?url=' . $this->url;
		$json = @file_get_contents($url);
		$this->data = json_decode($json);
		return $this->data;
	}

	public function isValid()
	{	
		if ($this->getId()) {
			return true;
		}

		return false;
	}

	/**
	 * Extract VK video id from the video url submitted by the user
	 *
	 * @access	public
	 * @param	video url
	 * @return videoid
	 */
	public function getId()
	{	
		if (!$this->data) {
			$this->data = $this->getData();
		}

		if (!$this->data) {
			return false;
		}

		if ($this->data->twitch_type === 'clip') {
			preg_match('/clips\.twitch\.tv\/([A-z]*)/', $this->data->request_url, $matches);
			return !empty($matches[1]) ? $matches[1] . '|' . $this->data->twitch_type : '';
		} else {
			return $this->data->twitch_content_id . '|' . $this->data->twitch_type;
		}
	}

	/**
	 * Return the video provider's name
	 *
	 */
	public function getType()
	{
		return 'twitch';
	}

	public function getTitle()
	{	
		$this->data = $this->getData();
		return !empty($this->data->title) ? $this->data->title : ''; 
	}

	public function getDescription()
	{	
		return '';
	}

	public function getDuration()
	{	
		$this->data = $this->getData();
        return !empty($this->data->video_length) ? $this->data->video_length : '';
	}

	public function getThumbnail()
	{	
		$this->data = $this->getData();
		return !empty($this->data->thumbnail_url) ? $this->data->thumbnail_url : '';
	}

	/**
	 *
	 *
	 * @return $embedvideo specific embeded code to play the video
	 */
	public function getViewHTML( $videoId, $videoWidth, $videoHeight )
	{
		if (!$videoId) {
            $videoId = $this->videoId;
        }
		$exploded = explode('|', $videoId);
		$id = $exploded[0];
		$type = $exploded[1];

		if ($type === 'clip') {
			$path ='https://clips.twitch.tv/embed?autoplay=true&clip=' . $id;
		} else {
			$path ='https://player.twitch.tv/?autoplay=true&video=' . $id;
		}

        $embedCode = '<iframe width="700" height="'.$videoHeight.'" src="'.$path.'" frameborder="0" allowtransparency="true" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';
        return $embedCode;
	}
}

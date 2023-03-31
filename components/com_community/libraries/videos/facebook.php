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
 * Class to manipulate data from Daily Motion
 *
 * @access	public
 */
class CTableVideoFacebook extends CVideoProvider
{
	var $xmlContent = null;
	var $url = '';
	var $data = false;

	public function fetchContent($url) {
		$option = new JRegistry();
		$option->set('headers', array(
			'user-agent'=> 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.62 Safari/537.36'
		));

		$http = new JHttp($option);

		try {
			$data = $http->get($url)->body;
			return $data;
		} catch ( JException $e  ) {
			return false;
		}
	}

	public function getData() {
		if ($this->data) {
			return $this->data;
		}
		$url = "https://www.facebook.com/plugins/video/oembed.json/?url=".$this->url;
		$data = json_decode($this->fetchContent($url));
		
		if (is_object($data)) {
			$this->data = $data;
			$params = new JRegistry();
			$params->set('width', $this->data->width);
			$params->set('height', $this->data->height);
			$this->params = $params->toString();
		} else {
			$this->data = false;
		}

		return $this->data;
	}

	public function isValid()
	{	
		$this->url = str_replace('http:', 'https:', $this->url);

		$this->data = $this->getData();

		if ($this->data) {
			if (isset($this->data->provider_name) && $this->data->provider_name == 'Facebook') {
				$this->xmlContent = $this->fetchContent($this->url);
				return true;
			}
		}
		return false;
	}

	/**
	 * Extract video id from the video url submitted by the user
	 *
	 * @access	public
	 * @param	video url
	 * @return videoid
	 */
	public function getId()
	{	
        $pattern    = '/facebook\.com\/?(.*)/';
        preg_match( $pattern, $this->url, $matches);

        return !empty($matches[1]) ? $matches[1] : '';
	}


	/**
	 * Return the video provider's name
	 *
	 */
	public function getType()
	{
		return 'facebook';
	}

	public function getTitle()
	{
		if (!$this->xmlContent) {
			$this->xmlContent = $this->fetchContent($this->url);
		}

		$content = str_replace( "\n", ' ', $this->xmlContent);
		$pattern = '/<title(.*?)>(.*?)<\/title>/';
        preg_match( $pattern, $content, $matches );
        return !empty( $matches[2] ) ? $matches[2] : '';
	}

	public function getDescription()
	{	
		return '';
	}

	public function getDuration()
	{
		return 0;
	}

	/**
	 * Get video's thumbnail URL from videoid
	 *
	 * @access 	public
	 * @param 	videoid
	 * @return url
	 */
	public function getThumbnail()
	{
		if (!$this->xmlContent) {
			$this->xmlContent = $this->fetchContent($this->url);
		}

		$content = str_replace( "\n", ' ', $this->xmlContent);
		$pattern = '/<meta property="og:image" content="(.*?)" \/>/';
		preg_match( $pattern, $content, $matches );
		if (!empty($matches[1])) {
			$image = htmlspecialchars_decode($matches[1]);
			return $image;
		} else {
			return JURI::root(true) . '/components/com_community/assets/videoicons/facebook_thumb.png';
		}
	}

	public function getParams($videoid) {
		$db = JFactory::getDbo();
		$query = 'SELECT params FROM `#__community_videos` WHERE video_id = ' . $db->quote($videoid) . ' AND `status`="ready" AND `type`="facebook"';
		$db->setQuery($query);
		$result = $db->loadResult();
		return new JRegistry($result);
	}

	/**
	 *
	 *
	 * @return $embedvideo specific embeded code to play the video
	 */
	public function getViewHTML($videoId, $videoWidth, $videoHeight)
	{
		if (!$videoId)
		{
			$videoId = $this->videoId;
		}
		
		$params = $this->getParams($videoId);
		$original_width = $params->get('width', 1);
		$original_height = $params->get('height', 1);

		$path = 'https://www.facebook.com/plugins/video.php?href=' . urlencode('https://www.facebook.com/' . $videoId);
		$embedCode = '<iframe style="border:none;overflow:hidden" scrolling="no" frameborder="0" allowTransparency="true"></iframe>';
		$embedCode .= '
<script>
	var popupIframe, videoIframe, videoWrapper, ratio, src, oWidth, oHeight, fWidth, fHeight, width, height, maxHeight;
	popupIframe = jQuery(\'.joms-popup__video\').find(\'iframe\');
	videoWrapper = jQuery(\'.video-player\');
	oWidth = '.$original_width.';
	oHeight = '.$original_height.';
	ratio = oWidth / oHeight;
	src = \''.$path.'\';

	if (popupIframe.length) {
		fWidth = ratio * popupIframe.height();

		if (fWidth > popupIframe.width()) {
			width = popupIframe.width();
		} else {
			width = fWidth;
		}

		popupIframe.attr(\'style\', \'width: \' + width + \'px !important;\');
		popupIframe.attr(\'src\', src);
	} else if(videoWrapper.length) {
		videoIframe = videoWrapper.find(\'iframe\');
		maxHeight = 500;
		fHeight = videoWrapper.width() / ratio;

		if (fHeight > maxHeight) {
			width = ratio * maxHeight;
		} else {
			width = ratio * fHeight;
		}

		height = width / ratio;

		videoIframe.attr(\'width\', width);
		videoIframe.attr(\'height\', height);
		videoIframe.attr(\'src\', src);
	}
</script>
';
		return $embedCode;
	}

}
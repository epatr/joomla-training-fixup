<?php
/**
* @package		EasySocial
* @copyright	Copyright (C) 2010 - 2018 Stack Ideas Sdn Bhd. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* EasySocial is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/
defined('_JEXEC') or die('Unauthorized Access');

require_once(__DIR__ . '/abstract.php');

class SocialCrawlerTwitch extends SocialCrawlerAbstract
{
	public function process(&$result)
	{
		// Check if the url should be processed here.
		if (stristr($this->url, 'twitch.tv') === false) {
			return;
		}

		// Generate the oembed url
		$oembed = $this->crawl($this->url);

		// if this type of twitch vdeo doesn't have any oembed data 
		if (!$oembed) {

			// Reads this URL html content
			$htmlContent = @file_get_contents($this->url);

			// Retrieve the opengraph data
			$ogContent = $this->getOpengraphData($htmlContent);

			// If the opengraph data doesn't have og:video tag then just return here
			if (!$ogContent->video) {
				return;
			}

			// Manually generate embed codes.
			$embedCodes = $this->generateVideoEmbed($ogContent->video);

			if ($embedCodes !== false) {
				$result->title = $ogContent->title;
				$result->oembed->type = 'embed';
				$result->oembed->thumbnail = $ogContent->image;
				$result->oembed->html = $embedCodes;

				if (isset($ogContent->video_duration)) {
					$result->oembed->duration = $ogContent->video_duration;
				}
			}

			return $result;
		}

		// Try to get the duration from the contents
		$oembed->duration = $oembed->video_length;
		$oembed->thumbnail = $oembed->thumbnail_url;

		$result->oembed = $oembed;

		// Do not use the opengraph data if there is an oembed data
		if ($result->oembed) {
			unset($result->opengraph);
		}
	}

	public function crawl($url)
	{
		// Need to ensure the URL is always twitch.tv
		$url = str_ireplace('go.twitch.tv', 'twitch.tv', $url);
		$endpoint = 'https://api.twitch.tv/v5/oembed?url=' . urlencode($url);

		$connector = ES::connector();
		$connector->addUrl($endpoint);
		$connector->connect();

		$contents = $connector->getResult($endpoint);

		$oembed = json_decode($contents);

		return $oembed;
	}

	/**
	 * Since some of the Twitch video doesn't have the consistence data, we need to extract these data ourselves
	 *
	 * @since	2.1.8
	 * @access	public
	 */
	private function generateVideoEmbed($video)
	{
		$tmp = explode('&', $video);

		// do not allow to generate the video embed if doesn't have any value
		if (count($tmp) < 1) {
			return false;
		}

		$channelName = explode('?', $tmp[0]);
		$channelName = $channelName[1];

		$output = '<iframe src="https://player.twitch.tv/?' . $channelName . '&autoplay=false' . ' width="853" height="480" frameborder="0" allowfullscreen></iframe>';

		return $output;
	}	
}

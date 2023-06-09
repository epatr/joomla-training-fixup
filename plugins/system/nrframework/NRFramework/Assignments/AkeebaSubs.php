<?php

/**
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2018 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
*/

namespace NRFramework\Assignments;

defined('_JEXEC') or die;

use NRFramework\Assignment;

class AkeebaSubs extends Assignment
{
	/**
	 *  Checks if user has access to certain subscription levels
	 *
	 *  @return  bool
	 */
	function passAkeebaSubs()
	{
    	return $this->passSimple($this->getLevels(), $this->selection);
	}

	/**
	 *  Returns all user's active subscriptions
	 *
	 *  @param   int     $userid  User's id
	 *
	 *  @return  array   Akeeba Subscriptions
	 */
	private function getLevels()
	{
		if (!$user = $this->user->id)
		{
			return false;
		}

		if (!defined('FOF30_INCLUDED') && !@include_once(JPATH_LIBRARIES . '/fof30/include.php'))
		{
			return false;
		}

		// Get the Akeeba Subscriptions container. Also includes the autoloader.
		$container = \FOF30\Container\Container::getInstance('com_akeebasubs');

		$subscriptionsModel = $container->factory->model('Subscriptions')->tmpInstance();

		$items = $subscriptionsModel
			->user_id($user)
			->enabled(1)
			->get();

		if (!$items->count())
		{
			return false;
		}

		$levels = array();

		foreach ($items as $subscription)
		{
			$levels[] = $subscription->akeebasubs_level_id;
		}

		return array_unique($levels);
	}

}

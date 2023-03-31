<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2018 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */
// no direct access
defined('_JEXEC') or die;

class EventbookingModelSearch extends EventbookingModelList
{
	/**
	 * Instantiate the model.
	 *
	 * @param array $config configuration data for the model
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->state->remove('id')
			->insert('category_id', 'int', 0)
			->insert('created_by', 'int', 0)
			->insert('filter_city', 'string', '')
			->insert('filter_state', 'string', '')
			->insert('filter_from_date', 'string', '')
			->insert('filter_to_date', 'string', '')
			->insert('filter_address', 'string', '')
			->insert('filter_distance', 'int', 0);
	}

	/**
	 * Builds a WHERE clause for the query
	 *
	 * @param JDatabaseQuery $query
	 *
	 * @return $this
	 */
	protected function buildQueryWhere(JDatabaseQuery $query)
	{
		$config = EventbookingHelper::getConfig();

		if ($config->hide_past_events)
		{
			$this->applyHidePastEventsFilter($query);
		}

		if (EventbookingHelper::isValidDate($this->state->filter_from_date))
		{
			$query->where('DATE(tbl.event_date) >= ' . $this->getDbo()->quote($this->state->filter_from_date));
		}

		if (EventbookingHelper::isValidDate($this->state->filter_to_date))
		{
			$query->where('DATE(tbl.event_date) <= ' . $this->getDbo()->quote($this->state->filter_to_date));
		}

		if ($this->state->filter_address && $this->state->filter_distance)
		{
			$this->applyRadiusFilter($query, $this->state->filter_address, $this->state->filter_distance);
		}

		return parent::buildQueryWhere($query);
	}

	/**
	 * Get list of all locations within a distance from given address
	 *
	 * @param JDatabaseQuery $query
	 * @param string         $address
	 * @param int            $distance
	 *
	 * @return void
	 */
	protected function applyRadiusFilter($query, $address, $distance)
	{
		$coordinates = $this->getCoordinatesFromAddress($address);

		if ($coordinates !== null)
		{
			$lat  = (float) $coordinates[0];
			$long = (float) $coordinates[1];

			$db            = $this->getDbo();
			$locationQuery = $db->getQuery(true)
				->select("id, (6371 * acos(cos(radians($lat))* cos(radians(c.lat)) * cos(radians(c.long) - radians($long)) + sin ( radians($lat) )* sin( radians(c.lat)))) AS distance")
				->from('#__eb_locations AS c')
				->where('c.published = 1')
				->having('distance < ' . (int) $distance);
			$db->setQuery($locationQuery);
			$locationIds = $db->loadColumn();
		}

		if (empty($locationIds))
		{
			$locationIds = [0];
		}

		$query->where('tbl.location_id IN (' . implode(',', $locationIds) . ')');
	}

	/**
	 * Method to get coordinates from
	 *
	 * @param $address
	 *
	 * @return array
	 */
	protected function getCoordinatesFromAddress($address)
	{
		$config      = EventbookingHelper::getConfig();
		$mapApiKye   = $config->get('map_api_key', 'AIzaSyDIq19TVV4qOX2sDBxQofrWfjeA7pebqy4');
		$coordinates = null;

		$http     = JHttpFactory::getHttp();
		$url      = "https://maps.googleapis.com/maps/api/geocode/json?address=" . str_replace(' ', '+', $address) . "&key=" . $mapApiKye;
		$response = $http->get($url);

		if ($response->code == 200)
		{
			$output_deals = json_decode($response->body);
			$latLng       = $output_deals->results[0]->geometry->location;
			$coordinates  = [$latLng->lat, $latLng->lng];
		}

		return $coordinates;
	}
}

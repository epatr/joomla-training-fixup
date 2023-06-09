<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2018 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

class EventbookingModelLocation extends EventbookingModelList
{
	/**
	 * Instantiate the model.
	 *
	 * @param array $config configuration data for the model
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);
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

		$hidePastEventsParam = $this->params->get('hide_past_events', 2);

		if ($hidePastEventsParam == 1 || ($hidePastEventsParam == 2 && $config->hide_past_events))
		{
			$this->applyHidePastEventsFilter($query);
		}

		return parent::buildQueryWhere($query);
	}

	/**
	 * Get location information from database, using for add/edit page
	 *
	 * @return JTable|mixed
	 */
	public function getLocationData()
	{
		if ($this->state->id)
		{
			return EventbookingHelperDatabase::getLocation($this->state->id);
		}
		else
		{
			$row          = $this->getTable();
			$config       = EventbookingHelper::getConfig();
			$row->country = $config->default_country;

			return $row;
		}
	}

	/**
	 * Method to store a location
	 *
	 * @access    public
	 * @return    boolean    True on success
	 */
	public function store(&$data)
	{
		$row          = $this->getTable();
		$user         = JFactory::getUser();
		$coordinates  = explode(',', $data['coordinates']);
		
		if ($data['id'])
		{
			$row->load($data['id']);
		}
		
		$row->lat     = $coordinates[0];
		$row->long    = $coordinates[1];
		$row->user_id = $user->id;
		$row->bind($data);

		if (empty($row->alias))
		{
			$row->alias = JApplicationHelper::stringURLSafe($row->name);
		}

		$row->store();

		// Check and make sure this alias is valid
		$db    = $this->getDbo();
		$query = $db->getQuery(true);
		$query->select('COUNT(*)')
			->from('#__eb_locations')
			->where('id != ' . $row->id)
			->where($db->quoteName('alias') . ' = ' . $db->quote($row->alias));
		$db->setQuery($query);
		$count = $db->loadResult();

		if ($count)
		{
			$row->alias = $row->id . '-' . $row->alias;
			$row->store();
		}

		$data['id'] = $row->id;

		return $row->id;
	}

	/**
	 * Delete the selected location
	 *
	 * @param array $cid
	 *
	 * @return boolean
	 */
	public function delete($cid = array())
	{
		if (count($cid))
		{
			$db    = $this->getDbo();
			$query = $db->getQuery(true);
			$cids  = implode(',', $cid);
			$query->delete('#__eb_locations')
				->where('id IN (' . $cids . ')')
				->where('user_id = ' . (int) JFactory::getUser()->id);
			$db->setQuery($query);

			if (!$db->execute())
			{
				return false;
			}
		}

		return true;
	}
}

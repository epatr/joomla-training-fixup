<?php

/**
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2018 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
*/

namespace NRFramework;

defined('_JEXEC') or die('Restricted access');

class Updatesites
{
	/**
	 *  Joomla Database Class
	 *
	 *  @var  object
	 */
	private $db;

	/**
	 *  Consturction method
	 *
	 *  @param  string  $key  Download Key
	 */
	function __construct($key = null)
	{
		$this->db  = \JFactory::getDBO();
		$this->key = ($key) ? $key : $this->getDownloadKey();
   	}

   	/**
   	 *  Main method
   	 */
   	public function update()
   	{
   		$this->updateDownloadKey();
		$this->purgeUpdateSites();
   	}

	/**
	 *  Reads the Download Key saved in the Novarain Framework system plugin parameters
	 *
	 *  @return  string  The Download Key
	 */
	public function getDownloadKey()
	{
		$query = $this->db->getQuery(true)
			->select('e.params')
			->from('#__extensions as e')
			->where('e.element = ' . $this->db->quote('nrframework'));

		$this->db->setQuery($query);

		if (!$params = $this->db->loadResult())
		{
			return;
		}

		$params = json_decode($params);

		if (!isset($params->key))
		{
			return;
		}

		return trim($params->key);
	}

	/**
	 *  Adds the user's Download Key as an extra query parameter to all entries
	 *
	 *  @param   string  $key  Download Key
	 */
	private function updateDownloadKey()
	{
		$query = $this->db->getQuery(true)
			->update('#__update_sites')
			->set($this->db->qn('extra_query') . ' = ' . $this->db->q('dlid=' . $this->key))
			->set($this->db->qn('enabled') . ' = 1')
			->where($this->db->qn('location') . ' LIKE ' . $this->db->q('%tassos.gr%'));

		$this->db->setQuery($query);
		$this->db->execute();
	}

	/**
	 *  Removes all of the updates from the table
	 *
	 *  @return  void
	 */
	private function purgeUpdateSites()
	{
        \JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_installer/models');

        if ($model = \JModelLegacy::getInstance('Update', 'InstallerModel', array('ignore_request' => true)))
        {
	        $model->purge();
        }
	}
}
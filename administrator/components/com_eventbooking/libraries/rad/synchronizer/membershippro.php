<?php
class RADSynchronizerMembershippro
{
	public function getData($userId, $mappings)
	{
		$db = JFactory::getDbo();
		$data = array();
		$query = $db->getQuery(true);
		$query->select('*')
			->from('#__osmembership_subscribers')
			->where('user_id=' . $userId . ' AND is_profile=1');
		$db->setQuery($query);
		$rowProfile = $db->loadObject();
		if ($rowProfile)
		{
			$sql = 'SELECT a.name, b.field_value FROM #__osmembership_fields AS a INNER JOIN #__osmembership_field_value AS b ON a.id = b.field_id' .
				' WHERE b.subscriber_id=' . $rowProfile->id;
			$db->setQuery($sql);
			$fieldValues = $db->loadObjectList('name');

			foreach ($mappings as $fieldName => $mappingFieldName)
			{
				if ($mappingFieldName)
				{
					if (isset($rowProfile->{$mappingFieldName}))
					{
						$data[$fieldName] = $rowProfile->{$mappingFieldName};
					}
					elseif (isset($fieldValues[$mappingFieldName]))
					{
						$data[$fieldName] = $fieldValues[$mappingFieldName]->field_value;
					}
				}
			}
		}

		return $data;
	}
}

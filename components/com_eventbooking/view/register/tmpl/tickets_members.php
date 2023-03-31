<?php
$count = 0;

foreach ($ticketTypes as $item)
{
    if (empty($item->quantity))
    {
        continue;
    }
	$eventHeading = JText::sprintf('EB_EVENT_REGISTRANTS_INFORMATION', $item->title);
	?>
    <h3 class="eb-heading"><?php echo $eventHeading; ?></h3>
	<?php
	for ($i = 0; $i < $item->quantity; $i++)
	{
		$count++;
		$rowFields = EventbookingHelperRegistration::getFormFields($eventId, 2);
		$form      = new RADForm($rowFields);
		$form->setFieldSuffix($count);
		$form->bind($formData, true);
		$form->prepareFormFields('calculateIndividualRegistrationFee();');
		$form->buildFieldsDependency();
		$fields = $form->getFields();

		//We don't need to use ajax validation for email field for group members
		if (isset($fields['email']))
		{
			$emailField = $fields['email'];
			$cssClass   = $emailField->getAttribute('class');
			$cssClass   = str_replace(',ajax[ajaxEmailCall]', '', $cssClass);
			$emailField->setAttribute('class', $cssClass);
		}
		?>
        <h4 class="eb-heading"><?php echo JText::sprintf('EB_MEMBER_INFORMATION', $i + 1); ?></h4>
		<?php

		/* @var RADFormField $field */
		foreach ($fields as $field)
		{
			if ($i > 1 && $field->row->only_show_for_first_member)
			{
				continue;
			}

			if ($i > 1 && $field->row->only_require_for_first_member)
			{
				$field->makeFieldOptional();
			}

			echo $field->getControlGroup($bootstrapHelper);
		}
	}
}

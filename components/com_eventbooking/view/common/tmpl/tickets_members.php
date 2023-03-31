<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2018 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

$count           = 0;
$config          = EventbookingHelper::getConfig();
$bootstrapHelper = EventbookingHelperBootstrap::getInstance();

foreach ($ticketTypes as $item)
{
    if (empty($item->quantity))
    {
        continue;
    }

	$eventHeading = JText::sprintf('EB_TICKET_MEMBERS_INFORMATION', $item->title);
	?>
    <h3 class="eb-heading"><?php echo $eventHeading; ?></h3>
	<?php

    if (isset($formData['use_field_default_value']))
    {
        $useDefault = $formData['use_field_default_value'];
    }
    else
    {
        $useDefault = true;
    }

	$rowFields = EventbookingHelperRegistration::getFormFields($eventId, 2);

    for ($i = 0; $i < $item->quantity; $i++)
	{
		$count++;
		$form      = new RADForm($rowFields);
		$form->setFieldSuffix($count);
		$form->bind($formData, $useDefault);
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

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
?>
<h3 class="eb-event-tickets-heading"><?php echo JText::_('EB_TICKET_INFORMATION'); ?></h3>
<table class="table table-striped table-bordered table-condensed eb-ticket-information">
	<thead>
		<tr>
			<th>
				<?php echo JText::_('EB_TICKET_TYPE'); ?>
			</th>
			<th class="eb-text-right">
				<?php echo JText::_('EB_PRICE'); ?>
			</th>
			<?php
			if ($config->show_available_place)
			{
			?>
				<th class="center">
					<?php echo JText::_('EB_AVAILABLE_PLACE'); ?>
				</th>
			<?php
			}
			?>
		</tr>
	</thead>
	<tbody>
	<?php
	$currencySymbol = empty($event->currency_symbol) ? null : $event->currency_symbol;

	foreach ($ticketTypes as $ticketType)
	{
	?>
	<tr>
		<td class="eb-ticket-type-title">
			<?php
				echo JText::_($ticketType->title);

				if ($ticketType->description)
				{
				?>
					<p class="eb-ticket-type-description"><?php echo JText::_($ticketType->description); ?></p>
				<?php
				}
			?>
		</td>
		<td class="eb-text-right">
			<?php echo EventbookingHelper::formatCurrency($ticketType->price, $config, $currencySymbol); ?>
		</td>
		<?php
		if ($config->show_available_place)
		{
			if ($ticketType->capacity)
			{
				$available = max($ticketType->capacity - $ticketType->registered, 0);
			}
            elseif ($event->event_capacity > 0)
			{
				$available = max($event->event_capacity - $event->total_registrants, 0);
			}
			else
			{
				$available = JText::_('EB_UNLIMITED');
			}
		?>
			<td class="center">
				<?php echo $available; ?>
			</td>
		<?php
		}
		?>
	</tr>
	<?php
	}
	?>
	</tbody>
</table>

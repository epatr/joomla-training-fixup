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
<div id="eb-event-info-right" class="<?php echo $this->bootstrapHelper->getClassMapping('span4'); ?>">
	<h3 id="eb-event-group-rates-heading">
		<?php echo JText::_('EB_GROUP_RATE'); ?>
	</h3>
	<table class="table table-bordered table-striped">
		<thead>
		<tr>
			<th class="eb_number_registrant_column">
				<?php echo JText::_('EB_NUMBER_REGISTRANTS'); ?>
			</th>
			<th class="sectiontableheader eb_rate_column">
				<?php echo JText::_('EB_RATE_PERSON'); ?>(<?php echo $this->item->currency_symbol ? $this->item->currency_symbol : $this->config->currency_symbol; ?>)
			</th>
		</tr>
		</thead>
		<tbody>
		<?php
		$i = 0 ;

		if ($this->config->show_price_including_tax && !$this->config->get('setup_price'))
		{
			$taxRate = $this->item->tax_rate;
		}
		else
		{
			$taxRate = 0;
		}

		foreach ($this->rowGroupRates as $rowRate)
		{
			$groupRate = round($rowRate->price * (1 + $taxRate / 100), 2);
		?>
			<tr>
				<td class="eb_number_registrant_column">
					<?php echo $rowRate->registrant_number ; ?>
				</td>
				<td class="eb_rate_column">
					<?php echo EventbookingHelper::formatAmount($groupRate, $this->config); ?>
				</td>
			</tr>
		<?php
		}
		?>
		</tbody>
	</table>
</div>

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

/**
 * Layout variables
 * -----------------
 * @var   string  $onCouponChange
 * @var   string  $currencySymbol
 * @var   boolean $showDiscountAmount
 * @var   boolean $showTaxAmount
 * @var   boolean $showGrossAmount
 * @var   string  $addOnClass
 * @var   string  $inputAppendClass
 * @var   string  $inputPrependClass
 * @var   string  $controlGroupClass
 * @var   string  $controlLabelClass
 * @var   string  $controlsClass
 */

/* @var EventbookingHelperBootstrap $bootstrapHelper */
$bootstrapHelper = $this->bootstrapHelper;

if ($this->enableCoupon)
{
?>
	<div class="<?php echo $controlGroupClass;  ?>">
		<label class="<?php echo $controlLabelClass; ?>" for="coupon_code"><?php echo  JText::_('EB_COUPON') ?></label>
		<div class="<?php echo $controlsClass; ?>">
			<input type="text" class="input-medium" name="coupon_code" id="coupon_code" value="<?php echo $this->escape($this->input->getString('coupon_code')); ?>" onchange="<?php echo $onCouponChange; ?>" />
			<span class="invalid" id="coupon_validate_msg" style="display: none;"><?php echo JText::_('EB_INVALID_COUPON'); ?></span>
		</div>
	</div>
<?php
}
?>
<div class="<?php echo $controlGroupClass;  ?>">
	<label class="<?php echo $controlLabelClass; ?>">
		<?php echo JText::_('EB_AMOUNT'); ?>
	</label>
	<div class="<?php echo $controlsClass; ?>">
		<?php
            $input = '<input id="total_amount" type="text" readonly="readonly" class="input-small" value="' . EventbookingHelper::formatAmount($this->totalAmount, $this->config) . '" />';

            if ($this->config->currency_position == 0)
            {
                echo $bootstrapHelper->getPrependAddon($input, $currencySymbol);
            }
            else
            {
                echo $bootstrapHelper->getAppendAddon($input, $currencySymbol);
            }
		?>
	</div>
</div>
<?php
if ($showDiscountAmount)
{
?>
	<div class="<?php echo $controlGroupClass;  ?>">
		<label class="<?php echo $controlLabelClass; ?>">
			<?php echo JText::_('EB_DISCOUNT_AMOUNT'); ?>
		</label>
		<div class="<?php echo $controlsClass; ?>">
			<?php
			$input = '<input id="discount_amount" type="text" readonly="readonly" class="input-small" value="' . EventbookingHelper::formatAmount($this->discountAmount, $this->config) . '" />';

			if ($this->config->currency_position == 0)
			{
				echo $bootstrapHelper->getPrependAddon($input, $currencySymbol);
			}
			else
			{
				echo $bootstrapHelper->getAppendAddon($input, $currencySymbol);
			}
			?>
		</div>
	</div>
<?php
}

if($this->lateFee > 0)
{
?>
	<div class="<?php echo $controlGroupClass;  ?>">
		<label class="<?php echo $controlLabelClass; ?>">
			<?php echo JText::_('EB_LATE_FEE'); ?>
		</label>
		<div class="<?php echo $controlsClass; ?>">
			<?php
			$input = '<input id="late_fee" type="text" readonly="readonly" class="input-small" value="' . EventbookingHelper::formatAmount($this->lateFee, $this->config) . '" />';

			if ($this->config->currency_position == 0)
			{
				echo $bootstrapHelper->getPrependAddon($input, $currencySymbol);
			}
			else
			{
				echo $bootstrapHelper->getAppendAddon($input, $currencySymbol);
			}
			?>
		</div>
	</div>
<?php
}

if($showTaxAmount)
{
?>
	<div class="<?php echo $controlGroupClass;  ?>">
		<label class="<?php echo $controlLabelClass; ?>">
			<?php echo JText::_('EB_TAX_AMOUNT'); ?>
		</label>
		<div class="<?php echo $controlsClass; ?>">
			<?php
			$input = '<input id="tax_amount" type="text" readonly="readonly" class="input-small" value="' . EventbookingHelper::formatAmount($this->taxAmount, $this->config) . '" />';

			if ($this->config->currency_position == 0)
			{
				echo $bootstrapHelper->getPrependAddon($input, $currencySymbol);
			}
			else
			{
				echo $bootstrapHelper->getAppendAddon($input, $currencySymbol);
			}
			?>
		</div>
	</div>
<?php
}

if ($this->showPaymentFee)
{
?>
	<div class="<?php echo $controlGroupClass;  ?>">
		<label class="<?php echo $controlLabelClass; ?>">
			<?php echo JText::_('EB_PAYMENT_FEE'); ?>
		</label>
		<div class="<?php echo $controlsClass; ?>">
			<?php
			$input = '<input id="payment_processing_fee" type="text" readonly="readonly" class="input-small" value="' . EventbookingHelper::formatAmount($this->paymentProcessingFee, $this->config) . '" />';

			if ($this->config->currency_position == 0)
			{
				echo $bootstrapHelper->getPrependAddon($input, $currencySymbol);
			}
			else
			{
				echo $bootstrapHelper->getAppendAddon($input, $currencySymbol);
			}
			?>
		</div>
	</div>
<?php
}

if ($showGrossAmount)
{
?>
	<div class="<?php echo $controlGroupClass;  ?>">
		<label class="<?php echo $controlLabelClass; ?>">
			<?php echo JText::_('EB_GROSS_AMOUNT'); ?>
		</label>
		<div class="<?php echo $controlsClass; ?>">
			<?php
			$input = '<input id="amount" type="text" readonly="readonly" class="input-small" value="' . EventbookingHelper::formatAmount($this->amount, $this->config) . '" />';

			if ($this->config->currency_position == 0)
			{
				echo $bootstrapHelper->getPrependAddon($input, $currencySymbol);
			}
			else
			{
				echo $bootstrapHelper->getAppendAddon($input, $currencySymbol);
			}
			?>
		</div>
	</div>
<?php
}

if ($this->depositPayment)
{
	if ($this->paymentType == 1)
	{
		$style = '';
	}
	else
	{
		$style = 'style = "display:none"';
	}
	?>
	<div id="deposit_amount_container" class="<?php echo $controlGroupClass; ?>"<?php echo $style; ?>>
		<label class="<?php echo $controlLabelClass; ?>" for="payment_type">
			<?php echo JText::_('EB_DEPOSIT_AMOUNT'); ?>
		</label>
		<div class="<?php echo $controlsClass; ?>">
			<?php
			$input = '<input id="deposit_amount" type="text" readonly="readonly" class="input-small" value="' . EventbookingHelper::formatAmount($this->depositAmount, $this->config) . '" />';

			if ($this->config->currency_position == 0)
			{
				echo $bootstrapHelper->getPrependAddon($input, $currencySymbol);
			}
			else
			{
				echo $bootstrapHelper->getAppendAddon($input, $currencySymbol);
			}
			?>
		</div>
	</div>
	<div class="<?php echo $controlGroupClass; ?> payment-calculation">
		<label class="<?php echo $controlLabelClass; ?>" for="payment_type">
			<?php echo JText::_('EB_PAYMENT_TYPE'); ?>
		</label>
		<div class="<?php echo $controlsClass; ?>">
			<?php echo $this->lists['payment_type']; ?>
		</div>
	</div>
<?php
}
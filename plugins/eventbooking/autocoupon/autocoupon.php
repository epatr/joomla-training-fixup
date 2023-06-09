<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2018 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

class plgEventbookingAutocoupon extends JPlugin
{
	/**
	 * Render setting form
	 *
	 * @param JTable $row
	 *
	 * @return array
	 */
	public function onEditEvent($row)
	{
		if (JFactory::getApplication()->isSite() && !$this->params->get('show_on_frontend'))
		{
			return;
		}

		ob_start();
		$this->drawSettingForm($row);

		return array(
			'title' => JText::_('EB_AUTO_COUPON'),
			'form'  => ob_get_clean(),
		);
	}

	/**
	 * Store setting into database, in this case, use params field of plans table
	 *
	 * @param EventbookingTableEvent $row
	 * @param Boolean                $isNew true if create new plan, false if edit
	 */
	public function onAfterSaveEvent($row, $data, $isNew)
	{
		// The plugin will only be available in the backend

		if (JFactory::getApplication()->isSite() && !$this->params->get('show_on_frontend'))
		{
			return;
		}

		$params = new Registry($row->params);
		$params->set('auto_coupon_discount', trim($data['auto_coupon_discount']));
		$params->set('auto_coupon_coupon_type', $data['auto_coupon_coupon_type']);
		$params->set('auto_coupon_event_ids', trim($data['auto_coupon_event_ids']));
		$params->set('auto_coupon_times', trim($data['auto_coupon_times']));
		$params->set('auto_coupon_valid_from', trim($data['auto_coupon_valid_from']));
		$params->set('auto_coupon_valid_to', trim($data['auto_coupon_valid_to']));

		$row->params = $params->toString();
		$row->store();
	}

	/**
	 * Generate invoice number after registrant complete payment for registration
	 *
	 * @param EventbookingTableRegistrant $row
	 *
	 * @return bool
	 */
	public function onAfterPaymentSuccess($row)
	{
		jimport('joomla.user.helper');

		// Coupon code was generated for this registration before, don't generate again
		if ($row->auto_coupon_coupon_id > 0)
		{
			return;
		}


		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$event  = EventbookingHelperDatabase::getEvent($row->event_id);
		$params = new Registry($event->params);

		$discount = $params->get('auto_coupon_discount');

		// This event is not configured to generate coupon for registrants, return
		if (empty($discount))
		{
			return;
		}


		$couponType = $params->get('auto_coupon_coupon_type', 0);
		$applyTo    = $params->get('auto_coupon_apply_to', 1);
		$enableFor  = $params->get('auto_coupon_enable_for', 0);
		$validFrom  = $params->get('auto_coupon_valid_from');
		$validTo    = $params->get('auto_coupon_valid_to');
		$eventIds   = trim($params->get('auto_coupon_event_ids'));

		if ($eventIds)
		{
			$eventIds = array_filter(ArrayHelper::toInteger(explode(',', $eventIds)));
		}
		else
		{
			$eventIds = [];
		}

		while (true)
		{
			$couponCode = strtoupper(JUserHelper::genRandomPassword());
			$query->clear()
				->select('COUNT(*)')
				->from('#__eb_coupons')
				->where($db->quoteName('code') . '=' . $db->quote($couponCode));
			$db->setQuery($query);
			$total = $db->loadResult();

			if (!$total)
			{
				break;
			}
		}


		/* @var EventbookingTableCoupon $coupon */
		$coupon              = JTable::getInstance('Coupon', 'EventbookingTable');
		$coupon->code        = $couponCode;
		$coupon->discount    = $discount;
		$coupon->coupon_type = $couponType;
		$coupon->apply_to    = $applyTo;
		$coupon->enable_for  = $enableFor;
		$coupon->valid_from  = $validFrom;
		$coupon->valid_to    = $validTo;
		$coupon->access      = 1;
		$coupon->published   = 1;

		if (count($eventIds))
		{
			$coupon->event_id = 1;
		}
		else
		{
			$coupon->event_id = -1;
		}

		if ($row->user_id > 0)
		{
			$coupon->user_id = $row->user_id;
		}

		$coupon->store();

		// Store in registrant table
		$row->auto_coupon_coupon_id = $coupon->id;
		$row->store();

		if (count($eventIds))
		{
			$couponId = $coupon->id;
			$query->clear();
			$query->insert('#__eb_coupon_events')->columns('coupon_id, event_id');

			for ($j = 0, $n = count($eventIds); $j < $n; $j++)
			{
				$eventId = (int) $eventIds[$j];

				if ($eventId > 0)
				{
					$query->values("$couponId, $eventId");
				}
			}

			$db->setQuery($query);
			$db->execute();
		}
	}

	/**
	 * Display form allows users to change settings on subscription plan add/edit screen
	 *
	 * @param object $row
	 */
	private function drawSettingForm($row)
	{
		$params   = new Registry($row->params);
		$config   = EventbookingHelper::getConfig();
		$lists    = [];
		$nullDate = JFactory::getDbo()->getNullDate();

		$options                          = array();
		$options[]                        = JHtml::_('select.option', 0, JText::_('%'));
		$options[]                        = JHtml::_('select.option', 1, $config->currency_symbol);
		$options[]                        = JHtml::_('select.option', 2, JText::_('EB_VOUCHER'));
		$lists['auto_coupon_coupon_type'] = JHtml::_('select.genericlist', $options, 'auto_coupon_coupon_type', 'class="input-medium"', 'value', 'text', $params->get('auto_coupon_coupon_type', 0));

		$options                       = array();
		$options[]                     = JHtml::_('select.option', 0, JText::_('EB_EACH_MEMBER'));
		$options[]                     = JHtml::_('select.option', 1, JText::_('EB_EACH_REGISTRATION'));
		$lists['auto_coupon_apply_to'] = JHtml::_('select.genericlist', $options, 'auto_coupon_apply_to', '', 'value', 'text', $params->get('auto_coupon_apply_to', 1));

		$options                         = array();
		$options[]                       = JHtml::_('select.option', 0, JText::_('EB_BOTH'));
		$options[]                       = JHtml::_('select.option', 1, JText::_('EB_INDIVIDUAL_REGISTRATION'));
		$options[]                       = JHtml::_('select.option', 2, JText::_('EB_GROUP_REGISTRATION'));
		$lists['auto_coupon_enable_for'] = JHtml::_('select.genericlist', $options, 'auto_coupon_enable_for', '', 'value', 'text', $params->get('auto_coupon_enable_for', 0));

		$validFrom = $params->get('auto_coupon_valid_from');
		$validTo   = $params->get('auto_coupon_valid_to');

		if (empty($validFrom))
		{
			$validFrom = $nullDate;
		}

		if (empty($validTo))
		{
			$validTo = $nullDate;
		}
		?>
        <div class="control-group">
            <label class="control-label">
				<?php echo JText::_('EB_DISCOUNT'); ?>
            </label>
            <div class="controls">
                <input class="input-small" type="text" name="auto_coupon_discount" id="auto_coupon_discount" size="10"
                       maxlength="250"
                       value="<?php echo $params->get('auto_coupon_discount'); ?>"/>&nbsp;&nbsp;<?php echo $lists['auto_coupon_coupon_type']; ?>
            </div>
        </div>
        <div class="control-group">
            <label class="control-label">
				<?php echo EventbookingHelperHtml::getFieldLabel('auto_coupon_event_ids', JText::_('EB_AUTO_COUPON_EVENT_IDS'), JText::_('EB_AUTO_COUPON_EVENT_IDS_EXPLAIN')); ?>
            </label>
            <div class="controls">
                <input class="input-xxlarge" type="text" name="auto_coupon_event_ids" id="auto_coupon_event_ids"
                       maxlength="250"
                       value="<?php echo $params->get('auto_coupon_event_ids'); ?>"/>
            </div>
        </div>
        <div class="control-group">
            <label class="control-label">
				<?php echo JText::_('EB_TIMES'); ?>
            </label>
            <div class="controls">
                <input class="input-small" type="text" name="auto_coupon_times" id="auto_coupon_times" size="5"
                       maxlength="250"
                       value="<?php echo $params->get('auto_coupon_times', 1); ?>"/>
            </div>
        </div>
        <div class="control-group">
            <label class="control-label">
				<?php echo JText::_('EB_VALID_FROM_DATE'); ?>
            </label>
            <div class="controls">
				<?php echo JHtml::_('calendar', $validFrom != $nullDate ? $validFrom : '', 'auto_coupon_valid_from', 'auto_coupon_valid_from'); ?>
            </div>
        </div>
        <div class="control-group">
            <label class="control-label">
				<?php echo JText::_('EB_VALID_TO_DATE'); ?>
            </label>
            <div class="controls">
				<?php echo JHtml::_('calendar', $validTo != $nullDate ? $validTo : '', 'auto_coupon_valid_to', 'auto_coupon_valid_to'); ?>
            </div>
        </div>
		<?php
	}
}

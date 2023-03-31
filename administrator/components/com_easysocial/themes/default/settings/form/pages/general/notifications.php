<?php
/**
* @package		EasySocial
* @copyright	Copyright (C) 2010 - 2016 Stack Ideas Sdn Bhd. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* EasySocial is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/
defined('_JEXEC') or die('Unauthorized Access');
?>
<div class="row">
	<div class="col-md-6">
		<div class="panel">
			<?php echo $this->html('panel.heading', 'COM_EASYSOCIAL_GENERAL_SETTINGS_NOTIFICATIONS'); ?>

			<div class="panel-body">
				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_NOTIFICATIONS_SETTINGS_ENABLE_SYSTEM_NOTIFICATION'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'notifications.system.enabled', $this->config->get('notifications.system.enabled')); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_NOTIFICATIONS_SETTINGS_SYSTEM_AUTOMATICALLY_MARK_AS_READ'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'notifications.system.autoread', $this->config->get('notifications.system.autoread')); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_NOTIFICATIONS_SETTINGS_ENABLE_FRIEND_NOTIFICATION'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'notifications.friends.enabled', $this->config->get('notifications.friends.enabled')); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_NOTIFICATIONS_SETTINGS_ENABLE_CONVERSATION_NOTIFICATION'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'notifications.conversation.enabled', $this->config->get('notifications.conversation.enabled')); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_NOTIFICATIONS_SETTINGS_CONVERSATIONS_AUTOMATICALLY_MARK_AS_READ'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'notifications.conversation.autoread', $this->config->get('notifications.conversation.autoread')); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_ES_NOTIFICATIONS_SETTINGS_POLLING_INTERVAL'); ?>

					<div class="col-md-7">
						<?php echo $this->html('grid.inputbox', 'notifications.polling.interval', $this->config->get('notifications.polling.interval'), '', array('class' => 'o-form-control input-short text-center')); ?>
						&nbsp; <?php echo JText::_('COM_EASYSOCIAL_SECONDS'); ?>
					</div>
				</div>

			</div>
		</div>
	</div>

	<div class="col-md-6">
		<div class="panel">
			<?php echo $this->html('panel.heading', 'COM_EASYSOCIAL_NOTIFICATIONS_SETTINGS_BROADCAST_SETTINGS'); ?>

			<div class="panel-body">
				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_NOTIFICATIONS_SETTINGS_ENABLE_BROADCAST_POPUP'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'notifications.broadcast.popup', $this->config->get('notifications.broadcast.popup')); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_NOTIFICATIONS_SETTINGS_BROADCAST_PERIOD'); ?>

					<div class="col-md-7">
						<?php echo $this->html('grid.inputbox', 'notifications.broadcast.period', $this->config->get('notifications.broadcast.period'), '', array('class' => 'input-short text-center')); ?>
						&nbsp; <?php echo JText::_('COM_EASYSOCIAL_SECONDS'); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_NOTIFICATIONS_SETTINGS_STICKY_POPUP'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'notifications.broadcast.sticky', $this->config->get('notifications.broadcast.sticky')); ?>
					</div>
				</div>
			</div>
		</div>

		<div class="panel">
			<?php echo $this->html('panel.heading', 'COM_EASYSOCIAL_NOTIFICATIONS_SETTINGS_CLEANUP'); ?>

			<div class="panel-body">
				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_NOTIFICATIONS_SETTINGS_CLEANUP_ENABLE'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'notifications.cleanup.enabled', $this->config->get('notifications.cleanup.enabled')); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_NOTIFICATIONS_SETTINGS_CLEANUP_DURATION'); ?>

					<div class="col-md-7">
						<?php echo $this->html('grid.selectlist', 'notifications.cleanup.duration', $this->config->get('notifications.cleanup.duration'), array(
								array('value' => '3', 'text' => 'COM_EASYSOCIAL_NOTIFICATIONS_SETTINGS_CLEANUP_3_MONTHS'),
								array('value' => '6', 'text' => 'COM_EASYSOCIAL_NOTIFICATIONS_SETTINGS_CLEANUP_6_MONTHS'),
								array('value' => '12', 'text' => 'COM_EASYSOCIAL_NOTIFICATIONS_SETTINGS_CLEANUP_12_MONTHS'),
								array('value' => '18', 'text' => 'COM_EASYSOCIAL_NOTIFICATIONS_SETTINGS_CLEANUP_18_MONTHS'),
								array('value' => '24', 'text' => 'COM_EASYSOCIAL_NOTIFICATIONS_SETTINGS_CLEANUP_24_MONTHS')
							)); ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

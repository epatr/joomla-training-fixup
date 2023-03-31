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
			<?php echo $this->html('panel.heading', 'COM_EASYSOCIAL_GENERAL_SETTINGS_SYSTEM_SETTINGS'); ?>

			<div class="panel-body">
				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_GENERAL_SETTINGS_USE_INDEX_FOR_AJAX_URLS'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'general.ajaxindex', $this->config->get('general.ajaxindex')); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_GENERAL_SETTINGS_RENDER_JQUERY'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'general.jquery', $this->config->get('general.jquery')); ?>
						
						<div class="t-fs-sm">
							<?php echo JText::_('COM_EASYSOCIAL_GENERAL_SETTINGS_RENDER_JQUERY_NOTICE');?>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="panel">
			<?php echo $this->html('panel.heading', 'COM_EASYSOCIAL_GENERAL_SETTINGS_CDN_SETTINGS'); ?>

			<div class="panel-body">
				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_GENERAL_SETTINGS_CDN_URL'); ?>

					<div class="col-md-7">
						<?php echo $this->html('grid.inputbox', 'general.cdn.url', $this->config->get('general.cdn.url')); ?>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="col-md-6">
		<div class="panel">
			<?php echo $this->html('panel.heading', 'COM_EASYSOCIAL_GENERAL_SETTINGS_CRONJOB_SETTINGS'); ?>

			<div class="panel-body">
				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_GENERAL_SETTINGS_ENABLE_SECURE_CRON_URL'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'general.cron.secure', $this->config->get('general.cron.secure'), '', array('data-secure-cron')); ?>
					</div>
				</div>
				
				<div class="form-group <?php echo $this->config->get('general.cron.secure') ? '' : 't-hidden';?>" data-secure-cron-settings>
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_GENERAL_SETTINGS_SECURE_CRON_KEY'); ?>

					<div class="col-md-7">
						<?php echo $this->html('grid.inputbox', 'general.cron.key', $this->config->get('general.cron.key')); ?>

						<div class="help-block">
							<?php echo JText::_('COM_EASYSOCIAL_GENERAL_SETTINGS_SECURE_CRON_KEY_INFO'); ?>
						</div>

						<?php if ($this->config->get('general.cron.key')) { ?>
						<div class="o-input-group">
							<div class="o-input-group__addon">
								<i class="fa fa-globe"></i>
							</div>
							<input type="text" class="o-form-control" value="<?php echo JURI::root() . 'index.php?option=com_easysocial&cron=true&phrase=' . $this->config->get('general.cron.key');?>" />
						</div>
						<?php } ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_GENERAL_SETTINGS_SEND_EMAIL_ON_PAGE_LOAD'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'email.pageload', $this->config->get('email.pageload')); ?>
					</div>
				</div>
			</div>
		</div>

	</div>
</div>
<?php
/**
* @package		EasySocial
* @copyright	Copyright (C) 2010 - 2017 Stack Ideas Sdn Bhd. All rights reserved.
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
			<?php echo $this->html('panel.heading', 'COM_EASYSOCIAL_GENERAL_SETTINGS_FEATURES'); ?>

			<div class="panel-body">
				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_GENERAL_SETTINGS_ENABLE_FRIENDS_SYSTEM'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'friends.enabled', $this->config->get('friends.enabled'), 'friends.enabled'); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_GENERAL_SETTINGS_ALLOW_FRIEND_INVITES'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'friends.invites.enabled', $this->config->get('friends.invites.enabled')); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_GENERAL_SETTINGS_ENABLE_FOLLOWERS_SYSTEM'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'followers.enabled', $this->config->get('followers.enabled')); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_ES_ENABLE_PRIVACY'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'privacy.enabled', $this->config->get('privacy.enabled')); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_GENERAL_SETTINGS_ENABLE_POINTS'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'points.enabled', $this->config->get('points.enabled')); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_ES_ENABLE_ACTIVITY_LOGS'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'activity.logs.enabled', $this->config->get('activity.logs.enabled')); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_GENERAL_SETTINGS_ENABLE_POLLS'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'polls.enabled', $this->config->get('polls.enabled')); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_GENERAL_SETTINGS_ENABLE_ACHIEVEMENT_SYSTEM'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'badges.enabled', $this->config->get('badges.enabled'), 'badges.enabled'); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_GENERAL_SETTINGS_ENABLE_APPLICATIONS'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'apps.browser', $this->config->get('apps.browser')); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_GENERAL_SETTINGS_TERMS_AND_CONDITIONS_MESSAGE'); ?>

					<div class="col-md-7">
						<?php echo $this->html('grid.textarea', 'apps.tnc.message', $this->config->get('apps.tnc.message')); ?>
					</div>
				</div>
				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_GENERAL_SETTINGS_ENABLE_WELCOME_MESSAGE'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'welcome.enabled', $this->config->get('welcome.enabled')); ?>
					</div>
				</div>
				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_GENERAL_SETTINGS_MESSAGE'); ?>

					<div class="col-md-7">
						<?php echo $this->html('grid.textarea', 'welcome.text', $this->config->get('welcome.text', 'COM_EASYSOCIAL_WELCOME_MESSAGE')); ?>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="col-md-6">
		<div class="panel">
			<?php echo $this->html('panel.heading', 'COM_ES_SETTINGS_LOCKDOWN'); ?>

			<div class="panel-body">
				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_GENERAL_SETTINGS_ENABLE_LOCKDOWN_MODE'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'general.site.lockdown.enabled', $this->config->get('general.site.lockdown.enabled')); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_GENERAL_SETTINGS_ALLOW_REGISTRATIONS_IN_LOCKDOWN_MODE'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'general.site.lockdown.registration', $this->config->get('general.site.lockdown.registration'), 'general.site.lockdown.registration'); ?>
					</div>
				</div>
			</div>
		</div>

		<div class="panel">
			<?php echo $this->html('panel.heading', 'COM_EASYSOCIAL_SOCIAL_SETTINGS_SOCIALBOOKMARKS_GENERAL'); ?>

			<div class="panel-body">
				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_SHARING_SETTINGS_ENABLE_SHARING'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'sharing.enabled', $this->config->get('sharing.enabled')); ?>
					</div>
				</div>
				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_SHARING_SETTINGS_ENABLE_EMAIL'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'sharing.vendors.email', $this->config->get('sharing.vendors.email')); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_SHARING_SETTINGS_LIMIT_PER_HOUR'); ?>

					<div class="col-md-7">
						<?php echo $this->html('grid.inputbox', 'sharing.email.limit', $this->config->get('sharing.email.limit'), '', array('class' => 'input-short text-center')); ?>
					</div>
				</div>
			</div>
		</div>

		<div class="panel">
			<?php echo $this->html('panel.heading', 'COM_EASYSOCIAL_GENERAL_SETTINGS_REPORTS_GENERAL'); ?>

			<div class="panel-body">
				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_REPORTS_SETTINGS_ENABLE_REPORTING'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'reports.enabled', $this->config->get('reports.enabled')); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_REPORTS_SETTINGS_ALLOW_GUESTS'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'reports.guests', $this->config->get('reports.guests')); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_REPORTS_SETTINGS_CUSTOM_EMAIL_ADDRESSES'); ?>

					<div class="col-md-7">
						<?php echo $this->html('grid.inputbox', 'reports.notifications.emails', $this->config->get('reports.notifications.emails')); ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

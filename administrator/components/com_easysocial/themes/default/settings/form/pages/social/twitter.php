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
			<?php echo $this->html('panel.heading', 'COM_EASYSOCIAL_SOCIAL_SETTINGS_TWITTER_GENERAL'); ?>

			<div class="panel-body">
				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_TWITTER_SETTINGS_ALLOW_REGISTRATION'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'oauth.twitter.registration.enabled', $this->config->get('oauth.twitter.registration.enabled')); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_TWITTER_SETTINGS_CONSUMER_KEY'); ?>

					<div class="col-md-7">
						<?php echo $this->html('grid.inputbox', 'oauth.twitter.app', $this->config->get('oauth.twitter.app')); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_TWITTER_SETTINGS_CONSUMER_SECRET'); ?>

					<div class="col-md-7">
						<?php echo $this->html('grid.inputbox', 'oauth.twitter.secret', $this->config->get('oauth.twitter.secret')); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_TWITTER_SETTINGS_REGISTRATION_TYPE'); ?>

					<div class="col-md-7">
						<?php echo $this->html('grid.selectlist', 'oauth.twitter.registration.type', $this->config->get('oauth.twitter.registration.type'), array(
							array('value' => 'simplified', 'text' => 'COM_EASYSOCIAL_FACEBOOK_SETTINGS_SIMPLIFIED'),
							array('value' => 'normal', 'text' => 'COM_EASYSOCIAL_FACEBOOK_SETTINGS_NORMAL')
						)); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_TWITTER_SETTINGS_PROFILE_TYPE'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.profiles', 'oauth.twitter.profile', 'oauth.twitter.profile', $this->config->get('oauth.twitter.profile')); ?>
					</div>
				</div>				

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_TWITTER_SETTINGS_ENABLE_CARDS'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'oauth.twitter.card.enabled', $this->config->get('oauth.twitter.card.enabled')); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_TWITTER_SETTINGS_CARD_TYPE'); ?>

					<div class="col-md-7">
						<?php echo $this->html('grid.selectlist', 'oauth.twitter.card.type', $this->config->get('oauth.twitter.card.type'), array(
							array('value' => 'summary_large_image', 'text' => 'COM_EASYSOCIAL_TWITTER_SETTINGS_CARD_TYPE_SUMMARY'),
							array('value' => 'summary', 'text' => 'COM_EASYSOCIAL_TWITTER_SETTINGS_CARD_TYPE_SUMMARY_LARGE')
						)); ?>
					</div>
				</div>				

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_TWITTER_SETTINGS_IMPORT_AVATAR'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'oauth.twitter.registration.avatar', $this->config->get('oauth.twitter.registration.avatar')); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_TWITTER_SETTINGS_IMPORT_COVER'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'oauth.twitter.registration.cover', $this->config->get('oauth.twitter.registration.cover')); ?>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="col-md-6">
	</div>
</div>
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
			<?php echo $this->html('panel.heading', 'COM_EASYSOCIAL_GENERAL_SETTINGS_CONVERSATIONS'); ?>

			<div class="panel-body">
				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_CONVERSATIONS_SETTINGS_ENABLE_CONVERSATIONS'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'conversations.enabled', $this->config->get('conversations.enabled')); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_ES_CONVERSATIONS_ENTER_SUBMIT'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'conversations.entersubmit', $this->config->get('conversations.entersubmit')); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_CONVERSATIONS_SETTINGS_ALLOW_COMPOSE_TO_NONFRIEND_USERS'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'conversations.nonfriend', $this->config->get('conversations.nonfriend')); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_ES_DISPLAY_USERS_TYPING_STATE'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'conversations.typing', $this->config->get('conversations.typing')); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_CONVERSATIONS_SETTINGS_ENABLE_LOCATION'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'conversations.location', $this->config->get('conversations.location')); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_THEMES_WIREFRAME_FIELD_CONVERSATION_SORTING'); ?>

					<div class="col-md-7">
						<?php echo $this->html('grid.selectlist', 'conversations.sorting', $this->config->get('conversations.sorting'), array(
								array('value' => 'created', 'text' => 'Creation Date'),
								array('value' => 'lastreplied', 'text' => 'Last Replied Date')
							)); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_THEMES_WIREFRAME_FIELD_CONVERSATION_ORDERING'); ?>

					<div class="col-md-7">
						<?php echo $this->html('grid.selectlist', 'conversations.ordering', $this->config->get('conversations.ordering'), array(
								array('value' => 'desc', 'text' => 'Descending (Higher to Lower)'),
								array('value' => 'asc', 'text' => 'Ascending (Lower to Higher)')
							)); ?>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="col-md-6">
		<?php if (JPluginHelper::isEnabled('system', 'conversekit')) { ?>
		<div class="panel">
			<?php echo $this->html('panel.heading', 'COM_ES_CK_INTEGRATIONS'); ?>

			<div class="panel-body">
				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_ES_CK_INTEGRATIONS_MESSAGE_LINKS'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'conversations.conversekit.links', $this->config->get('conversations.conversekit.links')); ?>
					</div>
				</div>
			</div>
		</div>
		<?php } ?>

		<div class="panel">
			<?php echo $this->html('panel.heading', 'COM_EASYSOCIAL_CONVERSATIONS_SETTINGS_ATTACHMENTS'); ?>

			<div class="panel-body">
				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_CONVERSATIONS_SETTINGS_ALLOW_ATTACHMENTS'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'conversations.attachments.enabled', $this->config->get('conversations.attachments.enabled')); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_CONVERSATIONS_SETTINGS_ATTACHMENT_TYPES'); ?>

					<div class="col-md-7">
						<?php echo $this->html('grid.inputbox', 'conversations.attachments.types', $this->config->get('conversations.attachments.types')); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_CONVERSATIONS_SETTINGS_ATTACHMENT_MAX_SIZE'); ?>

					<div class="col-md-7">
						<?php echo $this->html('grid.inputbox', 'conversations.attachments.maxsize', $this->config->get('conversations.attachments.maxsize'), '', array('class' => 'input-short text-center')); ?>
						<?php echo JText::_('COM_EASYSOCIAL_CONVERSATIONS_SETTINGS_ATTACHMENT_MAX_SIZE_UNIT'); ?>
					</div>
				</div>

			</div>
		</div>
	</div>
</div>

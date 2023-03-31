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
			<?php echo $this->html('panel.heading', 'COM_EASYSOCIAL_GENERAL_SETTINGS_COMMENTS'); ?>

			<div class="panel-body">
				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_STREAM_SETTINGS_ALLOW_GUEST_VIEW_COMMENTS'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'stream.comments.guestview', $this->config->get('stream.comments.guestview')); ?>
					</div>
				</div>
				
				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_COMMENTS_SETTINGS_ALLOW_SMILEYS'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'comments.smileys', $this->config->get('comments.smileys')); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_COMMENTS_SETTINGS_PAGINATION_LIMIT'); ?>

					<div class="col-md-7">
						<?php echo $this->html('grid.inputbox', 'comments.limit', $this->config->get('comments.limit'), '', array('class' => 'input-short text-center')); ?>
						<?php echo JText::_('COM_EASYSOCIAL_COMMENTS_SETTINGS_PAGINATION_LIMIT_UNIT');?>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="col-md-6">
		<div class="panel">
			<?php echo $this->html('panel.heading', 'COM_EASYSOCIAL_COMMENTS_SETTINGS_ATTACHMENTS'); ?>

			<div class="panel-body">
				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_COMMENTS_SETTINGS_ENABLE_ATTACHMENTS'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'comments.attachments', $this->config->get('comments.attachments')); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_COMMENTS_SETTINGS_RESIZE_IMAGES'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'comments.resize.enabled', $this->config->get('comments.resize.enabled')); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_COMMENTS_SETTINGS_RESIZE_IMAGES_MAX_WIDTH'); ?>

					<div class="col-md-7">
						<?php echo $this->html('grid.inputbox', 'comments.resize.width', $this->config->get('comments.resize.width'), '', array('class' => 'input-short text-center')); ?>
						<?php echo JText::_('COM_EASYSOCIAL_PIXELS'); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_COMMENTS_SETTINGS_RESIZE_IMAGES_MAX_HEIGHT'); ?>

					<div class="col-md-7">
						<?php echo $this->html('grid.inputbox', 'comments.resize.height', $this->config->get('comments.resize.height'), '', array('class' => 'input-short text-center')); ?>
						<?php echo JText::_('COM_EASYSOCIAL_PIXELS'); ?>
					</div>
				</div>

			</div>
		</div>
	</div>
</div>
<?php
/**
* @package		EasySocial
* @copyright	Copyright (C) 2010 - 2018 Stack Ideas Sdn Bhd. All rights reserved.
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
			<?php echo $this->html('panel.heading', 'COM_EASYSOCIAL_GENERAL_SETTINGS_LINKS_GENERAL'); ?>

			<div class="panel-body">
				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_LINKS_SETTINGS_CACHE_LINKS_DATA'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'links.cache.data', $this->config->get('links.cache.data')); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_LINKS_SETTINGS_CACHE_SHARED_IMAGES'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'links.cache.images', $this->config->get('links.cache.images')); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_LINKS_SETTINGS_CHECK_LINK_BEFORE_PARSING'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'links.parser.validate', $this->config->get('links.parser.validate')); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_ES_LINKS_SETTINGS_SUPPORTED_TLD'); ?>

					<div class="col-md-7">
						<textarea name="links.parser.tld" class="o-form-control" style="min-height: 200px;"><?php echo $this->config->get('links.parser.tld');?></textarea>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="col-md-6">
		<div class="panel">
			<?php echo $this->html('panel.heading', 'COM_EASYSOCIAL_LINKS_SETTINGS_CACHE_CLEANUP'); ?>

			<div class="panel-body">
				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_LINKS_SETTINGS_CACHE_AUTOMATED_CLEANUP'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'links.cache.cleanup.enabled', $this->config->get('links.cache.cleanup.enabled')); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_LINKS_SETTINGS_CACHE_AUTOMATED_CLEANUP_DURATION'); ?>

					<div class="col-md-7">
						<?php echo $this->html('grid.selectlist', 'links.cache.cleanup.duration', $this->config->get('links.cache.cleanup.duration'), array(
								array('value' => '1', 'text' => 'COM_EASYSOCIAL_LINKS_SETTINGS_CACHE_CLEANUP_1_MONTHS'),
								array('value' => '3', 'text' => 'COM_EASYSOCIAL_LINKS_SETTINGS_CACHE_CLEANUP_3_MONTHS'),
								array('value' => '6', 'text' => 'COM_EASYSOCIAL_LINKS_SETTINGS_CACHE_CLEANUP_6_MONTHS'),
								array('value' => '12', 'text' => 'COM_EASYSOCIAL_LINKS_SETTINGS_CACHE_CLEANUP_12_MONTHS')
							)); ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
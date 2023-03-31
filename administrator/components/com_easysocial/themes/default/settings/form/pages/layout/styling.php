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
			<?php echo $this->html('panel.heading', 'COM_ES_BUTTON_COLOURS'); ?>

			<div class="panel-body">
				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_ES_PRIMARY_BUTTON'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.colorpicker', 'buttons.primary', $this->config->get('buttons.primary'), '#428bca'); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_ES_SUCCESS_BUTTON'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.colorpicker', 'buttons.success', $this->config->get('buttons.success'), '#39b54a'); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_ES_STANDARD_BUTTON'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.colorpicker', 'buttons.standard', $this->config->get('buttons.standard'), '#333333'); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_ES_DANGER_BUTTON'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.colorpicker', 'buttons.danger', $this->config->get('buttons.danger'), '#d9534f'); ?>
					</div>
				</div>
			</div>
		</div>
		
	</div>

	<div class="col-md-6">

	</div>
</div>

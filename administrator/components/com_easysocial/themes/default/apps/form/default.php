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
<form name="ez-fields" id="adminForm" method="post" action="index.php">
	<div class="row">
		<div class="col-md-6">
			<div class="panel">
				<?php echo $this->html('panel.heading', 'COM_EASYSOCIAL_APP_CONFIGURATION'); ?>

				<div class="panel-body">
					<div class="accordion-body in">
						<div class="wbody wbody-padding">
							<div class="o-form-horizontal">

								<?php echo $this->html('panel.formInput', 'COM_EASYSOCIAL_APP_TITLE', 'title', $app->_('title')); ?>

								<?php $readonly = ($app->system) ? true : false; ?>
								<?php echo $this->html('panel.formBoolean', 'COM_EASYSOCIAL_APP_STATE', 'state', $app->state, '', $readonly); ?>

								<?php if ($showDefaultSetting) { ?>
									<?php echo $this->html('panel.formBoolean', 'COM_EASYSOCIAL_APP_DEFAULT', 'default', $app->default); ?>
								<?php } ?>

								<?php if ($app->type != SOCIAL_APPS_TYPE_FIELDS) { ?>
								<div class="form-group">
									<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_APP_ACCESS_CONTROL', true, '', 3); ?>

									<div class="col-md-9">
										<?php echo $this->html('form.' . $app->getAclType(), 'access[]', 'access', $selectedAccess, array('multiple' => true)); ?>
										
										<div class="help-block small">
											<?php echo JText::_('COM_EASYSOCIAL_APP_ACCESS_CONTROL_HELP');?>
										</div>
									</div>
								</div>
								<?php } ?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="col-md-6">
			<div class="panel">
				<?php echo $app->renderForm('admin', $app->getParams(), 'params', true);?>
			</div>
		</div>
	</div>

	<?php echo $this->html('form.action', 'apps'); ?>
	
	<input type="hidden" name="id" value="<?php echo $app->id;?>" />
	<input type="hidden" name="boxchecked" value="0" />
</form>

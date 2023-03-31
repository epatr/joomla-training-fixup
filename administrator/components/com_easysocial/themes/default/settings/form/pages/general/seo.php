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
			<?php echo $this->html('panel.heading', 'COM_ES_GENERAL_SETTINGS_SEO_GENERAL'); ?>

			<div class="panel-body">
				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_ES_CLUSTERS_ALLOW_DUPLICATE_TITLE'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'seo.clusters.allowduplicatetitle', $this->config->get('seo.clusters.allowduplicatetitle')); ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
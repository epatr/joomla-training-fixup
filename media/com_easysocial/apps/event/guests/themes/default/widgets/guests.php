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
<div class="es-side-widget is-module">
	<div class="es-side-widget__hd">
		<div class="es-side-widget__title">
			<?php echo JText::_('APP_EVENT_GUESTS_WIDGET_GUESTS_TITLE'); ?>

			<span class="es-side-widget__label">(<?php echo $event->getTotalGoing(); ?>)</span>
		</div>
	</div>

	<div class="es-side-widget__bd">
		<?php echo $this->html('widget.users', $guests, 'APP_EVENT_GUESTS_WIDGET_GUESTS_NO_GUESTS_YET'); ?>
	</div>

	<?php if (!empty($guests)) { ?>
	<div class="es-side-widget__ft">
		<?php echo $this->html('widget.viewAll', 'APP_EVENT_GUESTS_VIEW_ALL_GUESTS', $link); ?>
	</div>
	<?php } ?>
</div>

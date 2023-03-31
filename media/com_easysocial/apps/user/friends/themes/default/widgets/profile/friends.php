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
<div class="es-side-widget">
	<div class="es-side-widget__hd">
		<div class="es-side-widget__title">
			<?php echo JText::_('APP_FRIENDS_USER_FRIENDS'); ?>
			<span>(<?php echo $total; ?>)</span>
		</div>
	</div>

	<div class="es-side-widget__bd">
		<?php echo $this->html('widget.users', $friends, 'APP_FRIENDS_WIDGET_PROFILE_USER_NO_FRIENDS_CURRENTLY'); ?>
	</div>

	<?php if ($friends) { ?>
	<div class="es-side-widget__ft">
		<?php echo $this->html('widget.viewAll', 'COM_ES_VIEW_ALL', ESR::friends(array('userid' => $activeUser->getAlias()))); ?>
	</div>
	<?php } ?>
</div>
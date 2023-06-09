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
<dialog>
	<width>400</width>
	<height>150</height>
	<selectors type="json">
	{
		"{submitButton}": "[data-submit-button]",
		"{form}": "[data-purge-form]",
		"{cancelButton}": "[data-cancel-button]"
	}
	</selectors>
	<bindings type="javascript">
	{
		"{cancelButton} click": function() {
			this.parent.close();
		}
	}
	</bindings>
	<title><?php echo JText::_('COM_ES_PURGE_TEXT_AVATAR_CONFIRMATION'); ?></title>
	<content>
		<form method="post" action="index.php" data-purge-form>
			<p><?php echo JText::_('COM_ES_PURGE_TEXT_AVATAR_CONTENT'); ?></p>

			<?php echo $this->html('form.action', 'settings', 'purgeTextAvatars'); ?>
		</form>

	</content>
	<buttons>
		<button data-cancel-button type="button" class="btn btn-es"><?php echo JText::_('COM_ES_CANCEL'); ?></button>
		<button data-submit-button type="button" class="btn btn-es-primary"><?php echo JText::_('COM_ES_PURGE'); ?></button>
	</buttons>
</dialog>

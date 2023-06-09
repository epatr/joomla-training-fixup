<?php
/**
* @package		EasySocial
* @copyright	Copyright (C) 2010 - 2014 Stack Ideas Sdn Bhd. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* EasySocial is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/
defined( '_JEXEC' ) or die( 'Unauthorized Access' );
?>
<dialog>
	<width>400</width>
	<height>150</height>
	<selectors type="json">
	{
		"{resetButton}"		: "[data-reset-button]",
		"{resetForm}"		: "[data-reset-settings-form]",
		"{cancelButton}"	: "[data-cancel-button]"
	}
	</selectors>
	<bindings type="javascript">
	{
		"{cancelButton} click": function() {
			this.parent.close();
		}
	}
	</bindings>
	<title><?php echo JText::_( 'COM_EASYSOCIAL_SETTINGS_RESET_SETTINGS_DIALOG_TITLE' ); ?></title>
	<content>
		<form data-reset-settings-form>
			<p><?php echo JText::_( 'COM_EASYSOCIAL_SETTINGS_RESET_SETTINGS_DIALOG_CONFIRMATION' ); ?></p>
			<input type="hidden" name="option" value="com_easysocial" />
			<input type="hidden" name="controller" value="settings" />
			<input type="hidden" name="task" value="reset" />
			<input type="hidden" name="section" value="<?php echo $section;?>" />
			<?php echo $this->html( 'form.token' ); ?>
		</form>

	</content>
	<buttons>
		<button data-cancel-button type="button" class="btn btn-es"><?php echo JText::_('COM_ES_CANCEL'); ?></button>
		<button data-reset-button type="button" class="btn btn-es-primary"><?php echo JText::_('COM_EASYSOCIAL_RESET_SETTINGS_BUTTON'); ?></button>
	</buttons>
</dialog>

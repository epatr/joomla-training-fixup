<?php
/**
 * @package        	Joomla
 * @subpackage		Event Booking
 * @author  		Tuan Pham Ngoc
 * @copyright    	Copyright (C) 2010 - 2018 Ossolution Team
 * @license        	GNU/GPL, see LICENSE.php
 */

defined( '_JEXEC' ) or die ;

?>
<h1 class="eb_title"><?php echo JText::_('EB_REGISTRATION_CANCELLATION_CONFIRMATION'); ?></h1>
<?php echo $this->message; ?>
<form method="post" action="<?php echo JRoute::_('index.php?option=com_eventbooking&task=registrant.cancel&cancel_code='.$this->registrationCode.'&Itemid='.$this->Itemid, false) ?>" name="adminForm" id="adminForm" class="form form-horizontal">
	<input type="submit" value="<?php echo JText::_('EB_PROCESS');; ?>" id="btn-submit" name="btn-submit" class="btn btn-primary">
	<input type="hidden" value="0" name="id" />
	<?php echo JHtml::_( 'form.token' ); ?>
</form>
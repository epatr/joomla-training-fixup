<?php
/**
 * @package     Joomla.site
 * @subpackage  Component.hwdmediashare
 *
 * @copyright   Copyright (C) 2013 Highwood Design Limited. All rights reserved.
 * @license     GNU General Public License http://www.gnu.org/copyleft/gpl.html
 * @author      Dave Horsfall
 */

defined('_JEXEC') or die;

JHtml::_('bootstrap.tooltip');
?>
<div class="edit">
<form action="<?php echo htmlspecialchars(JFactory::getURI()->toString()); ?>" method="post" name="adminForm" id="adminForm" class="form-validate" enctype="multipart/form-data">
  <div id="hwd-container" class="<?php echo $this->pageclass_sfx;?>"> <a name="top" id="top"></a>
    <!-- Media Navigation -->
    <?php echo hwdMediaShareHelperNavigation::getInternalNavigation(); ?>
    <!-- Media Header -->
    <div class="media-header">
      <h2 class="media-album-title"><?php echo JText::_('COM_HWDMS_AGE_RESTRICTED_MEDIA'); ?></h2>
    </div>
    <div class="clear"></div>
    <p><?php echo JText::sprintf( 'COM_HWDMS_YOU_MUST_BE_OVER_X_YEARS', $this->item->params->get('age')); ?></p>
    <div class="control-group">
      <div class="control-label">
        <label id="jform_dob-lbl" for="jform_dob" class="hasTooltip" title="<?php echo JHtml::tooltipText(JText::_('COM_HWDMS_DOB_LABEL'), JText::_('COM_HWDMS_DOB_DESC')); ?>"><?php echo JText::_('COM_HWDMS_DOB_LABEL'); ?></label>
      </div>
      <div class="controls">
        <?php echo JHtml::_('calendar', $this->dob, "jform[dob]", "jform_dob", '%Y-%m-%d'); ?>
      </div>
    </div>     
    <div class="btn-toolbar">
      <div class="btn-group">
        <button type="submit" class="btn btn-primary">
          <span class="icon-checkmark"></span>&#160;<?php echo JText::_('COM_HWDMS_BUTTON_CONTINUE') ?>
        </button>
      </div>
    </div>      
    <input type="hidden" name="task" value="media.dob" />
    <input type="hidden" name="return" value="<?php echo $this->return;?>" />
    <?php echo JHtml::_( 'form.token' ); ?>
  </div>
</form>
</div>

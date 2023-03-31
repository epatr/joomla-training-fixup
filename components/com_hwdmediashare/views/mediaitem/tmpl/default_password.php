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
      <h2 class="media-album-title"><?php echo JText::_('COM_HWDMS_THIS_IS_A_PASSWORD_PROTECTED_MEDIA'); ?></h2>
    </div>
    <div class="clear"></div>
    <p><?php echo JText::_('COM_HWDMS_DO_YOU_HAVE_THE_PASSWORD'); ?></p>
    <div class="control-group">
      <div class="control-label hide">
        <label id="jform_password-lbl" for="jform_password" class="hasTooltip" title="<?php echo JHtml::tooltipText(JText::_('COM_HWDMS_PASSWORD_LABEL'), JText::_('COM_HWDMS_PASSWORD_DESC')); ?>"><?php echo JText::_('COM_HWDMS_PASSWORD_LABEL'); ?></label>
      </div>
      <div class="controls">
        <input type="password" name="jform[password]" id="jform_password" value="" class="required" />
      </div>
    </div>     
    <div class="btn-toolbar">
      <div class="btn-group">
        <button type="submit" class="btn btn-primary">
          <span class="icon-lock"></span>&#160;<?php echo JText::_('COM_HWDMS_BUTTON_CONTINUE') ?>
        </button>
      </div>
    </div>      
    <input type="hidden" name="task" value="media.password" />
    <input type="hidden" name="return" value="<?php echo $this->return;?>" />
    <?php echo JHtml::_( 'form.token' ); ?>
  </div>
</form>
</div>





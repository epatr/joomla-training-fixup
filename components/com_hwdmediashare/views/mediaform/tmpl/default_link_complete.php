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

JHtml::_('behavior.modal');
JHtml::_('behavior.framework', true);
JHtml::_('formbehavior.chosen', 'select');
$user = JFactory::getUser();
$app = JFactory::getApplication();
$canAdd = $user->authorise('core.create', 'com_hwdmediashare');
?>
<form action="<?php echo htmlspecialchars(JFactory::getURI()->toString()); ?>" method="post" name="adminForm" id="adminForm">
  <div id="hwd-container" class="hwd-modal <?php echo $this->pageclass_sfx;?> text-center"> <a name="top" id="top"></a>
    <!-- Media Header -->
    <div class="media-header">
      <?php if ($this->params->get('item_meta_title') != '0') :?>
        <h2 class="media-modal-title"><?php echo JText::_('COM_HWDMS_MEDIA_LINK_TASK_COMPLETE_TITLE'); ?></h2>
      <?php endif; ?> 
    </div>
    <div class="row-fluid">
      <div class="span12">
        <p><?php echo JText::_('COM_HWDMS_MEDIA_LINK_TASK_COMPLETE_DESC'); ?></p>
        <div class="btn-group">
          <a title="<?php echo JText::_('COM_HWDMS_BTN_FINISH'); ?>" href="<?php echo JRoute::_(base64_decode($app->input->get('return', null, 'base64'))); ?>" class="btn btn-large" target="_top"> <?php echo JText::_('COM_HWDMS_BTN_FINISH'); ?></a>
          <a title="<?php echo JText::_('COM_HWDMS_BTN_ADD_MORE'); ?>" href="<?php echo JRoute::_('index.php?option=com_hwdmediashare&task=mediaform.link&id=' . $app->input->get('id', 0, 'int') . '&return=' . $app->input->get('return', null, 'base64') . '&tmpl=component'); ?>" class="btn btn-large"> <?php echo JText::_('COM_HWDMS_BTN_ADD_MORE'); ?></a>
        </div>
      </div>
    </div>
    <input type="hidden" name="task" value="" />
    <?php echo JHtml::_('form.token'); ?>
  </div>
</form>  

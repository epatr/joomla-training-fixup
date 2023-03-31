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

// Load tooltip behavior.
JHtml::_('behavior.tooltip');
JHtml::_('behavior.framework');

// Include the component HTML helpers.
JHtml::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR . '/helpers/html');
JHtml::_('HwdPopup.iframe', 'page');

JHtml::_('behavior.keepalive');
JHtml::_('behavior.tooltip');
JHtml::_('behavior.calendar');
JHtml::_('behavior.formvalidation');
JHtml::_('formbehavior.chosen', '.hwd-form-catid', null, array('placeholder_text_multiple' => JText::_('COM_HWDMS_UPLOADS_SELECT_CATEGORY')));
JHtml::_('formbehavior.chosen', '.hwd-form-tags', null, array('placeholder_text_multiple' => JText::_('COM_HWDMS_UPLOADS_SELECT_TAGS')));
JHtml::_('formbehavior.chosen', 'select');

$user = JFactory::getUser();
?>
<form action="<?php echo JRoute::_('index.php?option=com_hwdmediashare'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate" enctype="multipart/form-data">
  <div id="hwd-container" class="<?php echo $this->pageclass_sfx;?>"> <a name="top" id="top"></a>
    <!-- Media Navigation -->
    <?php echo hwdMediaShareHelperNavigation::getInternalNavigation(); ?>
    <!-- Media Header -->
    <div class="media-header">
      <h2 class="media-upload-title"></h2>
    </div>   
    <?php if ($user->authorise('hwdmediashare.upload', 'com_hwdmediashare') && $this->params->get('enable_uploads_platform') == "1") : ?>
    <?php echo $this->getPlatformUploadForm($this); ?>
    <?php endif; ?>
    <input type="hidden" name="task" value="" />
    <input type="hidden" name="return" value="<?php echo $this->return; ?>" />
    <?php foreach($this->jformdata as $name => $value): ?>
      <?php if (is_array($value)) : ?>
        <?php foreach($value as $key => $id): ?>
          <?php if (!empty($id)) : ?><input type="hidden" name="jform[<?php echo $name; ?>][]" value="<?php echo $id; ?>" /><?php endif; ?>
        <?php endforeach; ?>
      <?php elseif(!empty($value)): ?>
        <input type="hidden" name="jform[<?php echo $name; ?>]" value="<?php echo $value; ?>" />
      <?php endif; ?>
    <?php endforeach; ?> 
    <?php // The token breaks the XML redirect file for uber, so it is removed by the uber javascript ?>
    <input type="hidden" name="<?php echo JSession::getFormToken(); ?>" id="formtoken" value="1" />
  </div>
</form>

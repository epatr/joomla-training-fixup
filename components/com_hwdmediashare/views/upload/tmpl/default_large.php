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

// Load Mootools JavaScript Framework.
JHtml::_('behavior.framework');
JHtml::_('behavior.core');

// Load tooltip behavior.
JHtml::_('behavior.tooltip');

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
    <?php if ($user->authorise('hwdmediashare.upload', 'com_hwdmediashare') && $this->params->get('enable_uploads_file') == "1") : ?>
<div id="ubr_alert_container" class="hide alert">
        <span id="ubr_alert"></span>
</div>
<!-- Start Progress Bar -->
<div id="progress_bar" class="hide">
        <div class="bar1" id="upload_status_wrap">
                <div class="bar2" id="upload_status"></div>
        </div>
        <table class="table table-striped table-bordered table-hover">
            <tbody>
            <tr>
                <th scope="row">
                    <?php echo JText::_('COM_HWDMS_PERCENT_COMPLETE'); ?>
                </th>
                <td class="center">
                    <span id="percent">0%</span>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <?php echo JText::_('COM_HWDMS_FILES_UPLOADED'); ?>
                </th>
                <td class="center">
                    <span id="uploaded_files">0</span> of <span id="total_uploads"></span>
                </td>
            </tr>
            <?php // HWD Modification: changed name of ID to avoid conflicts ?>
            <tr>
                <th scope="row">
                    <?php echo JText::_('COM_HWDMS_CURRENT_POSITION'); ?>
                </th>
                <td class="center">
                    <span id="currentupld">0</span> / <span id="total_kbytes"></span> KBs
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <?php echo JText::_('COM_HWDMS_ELAPSED_TIME'); ?>
                </th>
                <td class="center">
                    <span id="time">0</span>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <?php echo JText::_('COM_HWDMS_EST_TIME_LEFT'); ?>
                </th>
                <td class="center">
                    <span id="remain">0</span>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <?php echo JText::_('COM_HWDMS_EST_SPEED'); ?>
                </th>
                <td class="center">
                    <span id="speed">0</span> KB/s.
                </td>
            </tr>
            </tbody>
          </table>
</div>
    <!-- End Progress Bar -->
    <noscript><p><?php echo JText::_('COM_HWDMS_PLEASE_ENABLE_JAVASCRIPT'); ?></p></noscript>    
    <fieldset>
      <div class="btn-toolbar row-fluid">   
        <div class="span12">
          <div class="control-group">
            <div class="control-label hide">
              <?php echo $this->form->getLabel('Filedata'); ?>
            </div>                  
            <div class="controls">
              <?php echo $this->config->get('upload_workflow') == 1 ? '<div class="btn-group" id="upload_slots">' : ''; ?>
                <input type="file" name="upfile_0" <?php echo $this->config->get('upload_workflow') == 1 ? 'onChange="addUploadSlot(1)"' : ''; ?> onkeypress="return handleKey(event)" value="" class="hwd-form-filedata <?php echo $this->config->get('upload_workflow') == 1 ? '' : 'span12'; ?>">
              <?php echo $this->config->get('upload_workflow') == 1 ? '</div>' : ''; ?>
            </div>
          </div> 
        </div>
      </div>
      <div class="row-fluid">
        <div class="span8">
          <div class="control-group">
            <div class="control-label hide">
              <?php echo $this->form->getLabel('title'); ?>
            </div>                  
            <div class="controls">
              <?php echo $this->form->getInput('title'); ?>
            </div>
          </div>        
          <?php if ($this->params->get('enable_categories')): ?>
          <div class="control-group">
            <div class="control-label hide">
              <?php echo $this->form->getLabel('catid'); ?>
            </div>              
            <div class="controls">
              <?php echo $this->form->getInput('catid'); ?>
            </div>
          </div>                          
          <?php endif; ?>            
          <?php if ($this->params->get('enable_tags')): ?>
          <div class="control-group">
            <div class="control-label hide">
              <?php echo $this->form->getLabel('tags'); ?>
            </div>              
            <div class="controls">
              <?php echo $this->form->getInput('tags'); ?>
            </div>
          </div>    
          <?php endif; ?>             
        </div>
        <div class="span4">
          <div class="control-group">
            <div class="controls">
              <?php echo $this->form->getInput('private'); ?>
            </div>
          </div>            
          <div class="btn-toolbar row-fluid">
            <button type="button" class="btn btn-primary btn-large span12" id="upload_button" name="upload_button" onClick="linkUpload();">
              <?php echo JText::_('COM_HWDMS_BUTTON_UPLOAD') ?>
            </button>              
          </div> 
          <div class="btn-toolbar row-fluid">
            <a title="<?php echo JText::_('COM_HWDMS_ADD_MEDIA'); ?>" href="<?php echo JRoute::_(hwdMediaShareHelperRoute::getUploadRoute(array('method' => 'remote'))); ?>" class="btn span12">or add remote media</a>
          </div>             
        </div>
      </div>  
      <?php echo $this->form->getInput('description'); ?>
      <div class="clearfix"></div>
      <div class="well well-small">
        <h3><?php echo JText::_('COM_HWDMS_HELP_AND_SUGGESTIONS'); ?></h3>
        <?php if ($this->params->get('upload_terms_id')): ?>
          <p><?php echo JText::sprintf('COM_HWDMS_ACKNOWLEDGE_TERMS_AND_CONDITIONS', '<a href="' . JRoute::_('index.php?option=com_content&view=article&id=' . $this->params->get('upload_terms_id') . '&tmpl=component') . '" class="media-popup-iframe-page">' . JText::_('COM_HWDMS_TERMS_AND_CONDITIONS_LINK') . '</a>'); ?></p>      
        <?php endif; ?>
        <p><?php echo JText::sprintf('COM_HWDMS_SUPPORTED_FORMATS_LIST_X', implode(', ', $this->localExtensions)); ?> <?php echo JText::sprintf('COM_HWDMS_MAXIMUM_UPLOAD_SIZE_X', (hwdMediaShareUpload::getMaximumUploadSize('large')/1048576)); ?></p>
      </div> 
    </fieldset> 
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

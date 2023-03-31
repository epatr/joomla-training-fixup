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
$canAdd = $user->authorise('core.create', 'com_hwdmediashare');
?>
<form action="<?php echo htmlspecialchars(JFactory::getURI()->toString()); ?>" method="post" name="adminForm" id="adminForm">
  <div id="hwd-container" class="hwd-modal <?php echo $this->pageclass_sfx;?>"> <a name="top" id="top"></a>
    <fieldset>
      <div class="row-fluid">
        <div class="span12">
          <?php if ($this->params->get('enable_categories') && $this->item->created_user_id == $user->id): ?>
          <div class="control-group">
            <div class="control-label">
              <?php echo $this->form->getLabel('category_id'); ?>
            </div>                  
            <div class="controls">
              <?php echo $this->form->getInput('category_id'); ?>
              <button onclick="Joomla.submitbutton('media.link')" type="button" class="btn"><?php echo JText::_('COM_HWDMS_ADD'); ?></button>
            </div>
          </div>          
          <?php endif; ?>  
          <?php if ($this->params->get('enable_playlists')): ?>
          <div class="control-group">
            <div class="control-label">
              <?php echo $this->form->getLabel('playlist_id'); ?>
            </div>                  
            <div class="controls">
              <?php echo $this->form->getInput('playlist_id'); ?>
              <button onclick="Joomla.submitbutton('media.link')" type="button" class="btn"><?php echo JText::_('COM_HWDMS_ADD'); ?></button>
              <?php if ($canAdd): ?>
                <a title="<?php echo JText::_('COM_HWDMS_ADD_PLAYLIST'); ?>" href="<?php echo JRoute::_('index.php?option=com_hwdmediashare&view=playlistform&layout=edit&return=' . $this->return); ?>" class="btn" target="_top"><i class="icon-plus"></i> <?php echo JText::_('COM_HWDMS_ADD_PLAYLIST'); ?></a>
              <?php endif; ?>              
            </div>
          </div>          
          <?php endif; ?> 
          <?php if ($this->params->get('enable_albums') && $this->item->created_user_id == $user->id): ?>
          <div class="control-group">
            <div class="control-label">
              <?php echo $this->form->getLabel('album_id'); ?>
            </div>                  
            <div class="controls">
              <?php echo $this->form->getInput('album_id'); ?>
              <button onclick="Joomla.submitbutton('media.link')" type="button" class="btn"><?php echo JText::_('COM_HWDMS_ADD'); ?></button>
              <?php if ($canAdd): ?>
                <a title="<?php echo JText::_('COM_HWDMS_ADD_ALBUM'); ?>" href="<?php echo JRoute::_('index.php?option=com_hwdmediashare&view=albumform&layout=edit&return=' . $this->return); ?>" class="btn" target="_top"><i class="icon-plus"></i> <?php echo JText::_('COM_HWDMS_ADD_ALBUM'); ?></a>
              <?php endif; ?>                  
            </div>
          </div>          
          <?php endif; ?>
          <?php if ($this->params->get('enable_groups')): ?>
          <div class="control-group">
            <div class="control-label">
              <?php echo $this->form->getLabel('group_id'); ?>
            </div>                  
            <div class="controls">
              <?php echo $this->form->getInput('group_id'); ?>
              <button onclick="Joomla.submitbutton('media.link')" type="button" class="btn"><?php echo JText::_('COM_HWDMS_ADD'); ?></button>
              <?php if ($canAdd): ?>
                <a title="<?php echo JText::_('COM_HWDMS_ADD_GROUP'); ?>" href="<?php echo JRoute::_('index.php?option=com_hwdmediashare&view=groupform&layout=edit&return=' . $this->return); ?>" class="btn" target="_top"><i class="icon-plus"></i> <?php echo JText::_('COM_HWDMS_ADD_GROUP'); ?></a>
              <?php endif; ?>                  
            </div>
          </div>          
          <?php endif; ?>
        </div>
      </div>
    </fieldset>
    <input type="hidden" name="task" value="" />
    <?php echo JHtml::_('form.token'); ?>
  </div>
</form>  

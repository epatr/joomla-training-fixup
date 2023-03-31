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

// Include the component HTML helpers.
JHtml::addIncludePath(JPATH_ROOT . '/administrator/components/com_hwdmediashare/helpers/html');

$user = JFactory::getUser();
JHtml::_('bootstrap.tooltip');

// The channel page is designed for a single column layout, but can be adapted here.
$displayData->columns = 1;
?>
<?php foreach ($displayData->items as $id => $item) :
$rowcount = (((int)$id) % (int) $displayData->columns) + 1;
$item->slug = $item->alias ? ($item->id . ':' . $item->alias) : $item->id;
$canEdit = ($user->authorise('core.edit', 'com_hwdmediashare.channel.'.$item->id) || ($user->authorise('core.edit.own', 'com_hwdmediashare.channel.'.$item->id) && ($item->created_user_id == $user->id)));
$canEditState = $user->authorise('core.edit.state', 'com_hwdmediashare.channel.'.$item->id);
$canDelete = ($user->authorise('core.delete', 'com_hwdmediashare.channel.'.$item->id) || ($user->authorise('core.edit.own', 'com_hwdmediashare.channel.'.$item->id) && ($item->created_user_id == $user->id)));
?>
<?php if ($rowcount == 1) : ?>
<!-- Row -->
<div class="row-fluid">
<?php endif; ?>
  <!-- Cell -->
  <div class="span<?php echo intval(12/$displayData->columns); ?><?php echo $displayData->columns == 5 ? ' fivecolumns' : ''; ?>">
    <div class="media-item">
      <div class="media-aspect31"></div>        
      <!-- Avatar -->
      <?php if ($displayData->params->get('community_avatar') != 'none') :?>
      <div class="media-channel-avatar">
        <a href="<?php echo JRoute::_(hwdMediaShareHelperRoute::getChannelRoute($item->slug)); ?>">
          <?php echo JHtml::_('hwdimage.avatar', $item->id, 75); ?>
        </a>
      </div>
      <?php endif; ?>
      <?php if ($canEdit || $canDelete): ?>
        <div class="media-channel-nav">              
          <div class="btn-group pull-right">
          <?php
          // Create dropdown items
          if ($canEdit) : 
            JHtml::_('hwddropdown.edit', $item->id, 'channelform'); 
          endif;    
          if ($canEditState) :
            $action = $item->published ? 'unpublish' : 'publish';
            JHtml::_('hwddropdown.' . $action, $item->id, 'channels'); 
          endif; 
          if ($canDelete) : 
            JHtml::_('hwddropdown.delete', $item->id, 'channels'); 
          endif;         
          // Render dropdown list
          echo JHtml::_('hwddropdown.render', $displayData->escape($item->title), ' btn-micro');
          ?>                    
          </div>
        </div>
      <?php endif; ?>              
      <div class="media-channel-overlay">
        <?php if ($item->featured && $displayData->params->get('list_meta_featured_status') != '0'): ?>
          <span class="label label-info pull-right"><?php echo JText::_('COM_HWDMS_FEATURED'); ?></span>
        <?php endif; ?>
        <?php if ($item->status != 1) : ?>
          <span class="label pull-right"><?php echo $displayData->utilities->getReadableStatus($item); ?></span>
        <?php endif; ?>
        <?php if ($item->published != 1) : ?>
          <span class="label pull-right"><?php echo JText::_('COM_HWDMS_UNPUBLISHED'); ?></span>
        <?php endif; ?> 
        <!-- Title -->
        <?php if ($displayData->params->get('list_meta_title') != '0') :?>
          <h<?php echo $displayData->params->get('list_item_heading'); ?> class="contentheading">
            <?php if ($displayData->params->get('list_link_titles') == 1) :?><a href="<?php echo JRoute::_(hwdMediaShareHelperRoute::getChannelRoute($item->slug)); ?>"><?php endif; ?>
              <?php echo $displayData->escape(JHtml::_('string.truncate', $item->title, $displayData->params->get('list_title_truncate'), false, false)); ?> 
            <?php if ($displayData->params->get('list_link_titles') == 1) :?></a><?php endif; ?>
          </h<?php echo $displayData->params->get('list_item_heading'); ?>>
        <?php endif; ?> 
        <div class="clearfix"></div>
        <?php if ($displayData->params->get('list_meta_likes') != '0' || $displayData->params->get('list_meta_dislikes') != '0') :?>
        <div class="pull-right">
          <div class="media-info-likes pull-right">      
            <?php if ($displayData->params->get('list_meta_likes') != '0') :?><i class="icon-thumbs-up"></i> <span id="media-likes"><?php echo (int) $item->likes; ?></span><?php endif; ?>
            <?php if ($displayData->params->get('list_meta_dislikes') != '0') :?><i class="icon-thumbs-down"></i> <span id="media-dislikes"><?php echo (int) $item->dislikes; ?></span><?php endif; ?>
          </div>
        </div>       
        <?php endif; ?>   
        <?php if ($displayData->params->get('list_meta_upload_count') != '0' || $displayData->params->get('list_meta_subscriber_count') != '0') : ?>
        <!-- Item Meta -->
        <dl class="media-info">
          <dt class="media-info-term"><?php echo JText::_('COM_HWDMS_DETAILS'); ?> </dt>
            <?php if ($displayData->params->get('list_meta_upload_count') != '0'): ?><dd class="media-info-count"><?php echo (int) $item->nummedia; ?> uploads</dd><?php endif; ?>   
            <?php if ($displayData->params->get('list_meta_subscriber_count') != '0'): ?><dd class="media-info-count"><?php echo (int) $item->numsubscribers; ?> subscribers</dd><?php endif; ?>   
        </dl>
        <?php endif; ?>   
        <?php if ($displayData->params->get('list_meta_description') != '0') :?>
          <div class="clearfix"></div>
          <p class="media-info-description"><?php echo $displayData->escape(JHtml::_('string.truncate', $item->description, $displayData->params->get('list_desc_truncate'), false, false)); ?></p>
        <?php endif; ?> 
      </div>              
      <img src="<?php echo JRoute::_(hwdMediaShareThumbnails::getChannelArt($item)); ?>" border="0" alt="<?php echo $displayData->escape($item->title); ?>" class="media-thumb" />
    </div>
  </div>
<?php if (($rowcount == $displayData->columns) or (($id + 1) == count($displayData->items))): ?>
</div>
<?php endif; ?>
<?php endforeach; ?>
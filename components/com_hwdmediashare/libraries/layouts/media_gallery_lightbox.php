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
$associate = isset($displayData->module) ? false : true;
JHtml::_('bootstrap.tooltip');
?>
<?php foreach ($displayData->items as $id => $item) :
$rowcount = (((int)$id) % (int) $displayData->columns) + 1;
$item->slug = $item->alias ? ($item->id . ':' . $item->alias) : $item->id;
$canEdit = ($user->authorise('core.edit', 'com_hwdmediashare.media.'.$item->id) || ($user->authorise('core.edit.own', 'com_hwdmediashare.media.'.$item->id) && ($item->created_user_id == $user->id)));
$canEditState = $user->authorise('core.edit.state', 'com_hwdmediashare.media.'.$item->id);
$canDelete = ($user->authorise('core.delete', 'com_hwdmediashare.media.'.$item->id) || ($user->authorise('core.edit.own', 'com_hwdmediashare.media.'.$item->id) && ($item->created_user_id == $user->id)));
?>
<?php if ($rowcount == 1) : ?>
<!-- Row -->
<div class="row-fluid">
<?php endif; ?>
  <!-- Cell -->
  <div class="span<?php echo intval(12/$displayData->columns); ?><?php echo $displayData->columns == 5 ? ' fivecolumns' : ''; ?>">
    <!-- Thumbnail Image -->
    <div class="media-item<?php echo ($displayData->params->get('list_thumbnail_aspect') == 0 ? ' originalaspect' : ''); ?><?php echo ($displayData->params->get('list_tooltip_location') > '0' ? ' hasTooltip' : ''); ?>" title="<?php echo ($displayData->params->get('list_tooltip_location') > '0' ? JHtml::tooltipText($item->title, ($displayData->params->get('list_tooltip_contents') != '0' ? JHtml::_('string.truncate', $item->description, $displayData->params->get('list_desc_truncate'), false, false) : '')) : $item->title); ?>">
      <div class="media-aspect<?php echo $displayData->params->get('list_thumbnail_aspect'); ?>"></div>        
      <!-- Media Type -->
      <?php if ($displayData->params->get('list_meta_type_icon') != '0') :?>
      <div class="media-item-format-1-<?php echo $item->media_type; ?>">
        <img src="<?php echo JHtml::_('hwdicon.overlay', '1-'.$item->media_type, $item); ?>" alt="<?php echo JText::_('COM_HWDMS_MEDIA_TYPE'); ?>" />
      </div>
      <?php endif; ?>
      <?php if ($displayData->params->get('list_meta_duration') != '0' && $item->duration > 0) :?>
      <div class="media-duration">
        <?php echo hwdMediaShareMedia::secondsToTime($item->duration); ?>
      </div>
      <?php endif; ?>
      <?php if ($displayData->params->get('list_link_thumbnails') == 1) :?>
        <?php echo JHtml::_('HwdPopup.link', $item, '<img src="' . JRoute::_(hwdMediaShareThumbnails::thumbnail($item)) . '" border="0" alt="' . $displayData->escape($item->title) . '" class="media-thumb" />'); ?>                                      
      <?php else: ?>
        <img src="<?php echo JRoute::_(hwdMediaShareThumbnails::thumbnail($item)); ?>" border="0" alt="<?php echo $displayData->escape($item->title); ?>" class="media-thumb" />
      <?php endif; ?>
    </div>
    <?php if ($canEdit || $canDelete): ?>
    <div class="btn-group pull-right">
      <?php
      // Create dropdown items
      if ($canEdit) : 
        JHtml::_('hwddropdown.edit', $item->id, 'mediaform'); 
      endif;    
      if ($canEditState) :
        $action = $item->published == 1 ? 'unpublish' : 'publish';
        JHtml::_('hwddropdown.' . $action, $item->id, 'media'); 
      endif; 
      if ($canDelete && $item->published != -2) : 
        JHtml::_('hwddropdown.delete', $item->id, 'media'); 
      endif;         
      // Render dropdown list
      echo JHtml::_('hwddropdown.render', $displayData->escape($item->title), ' btn-micro');
      ?>                    
    </div>
    <?php endif; ?>       
    <!-- Clears Item and Information -->
    <div class="clear"></div>
    <?php if ($item->featured && $displayData->params->get('list_meta_featured_status') != '0'): ?>
      <span class="label label-info"><?php echo JText::_('COM_HWDMS_FEATURED'); ?></span>
    <?php endif; ?>
    <?php if ($item->status != 1) : ?>
      <span class="label"><?php echo $displayData->utilities->getReadableStatus($item); ?></span>
    <?php endif; ?>
    <?php if ($item->published != 1) : ?>
      <span class="label"><?php echo JText::_('COM_HWDMS_UNPUBLISHED'); ?></span>
    <?php endif; ?>        
  </div>
<?php if (($rowcount == $displayData->columns) or (($id + 1) == count($displayData->items))): ?>
</div>
<?php endif; ?>
<?php endforeach; ?>

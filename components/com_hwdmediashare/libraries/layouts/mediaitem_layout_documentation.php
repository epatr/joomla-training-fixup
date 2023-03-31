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
JHtml::_('HwdPopup.iframe', 'page');

$user = JFactory::getUser();
JHtml::_('bootstrap.tooltip');

$displayData->item->slug = $displayData->item->alias ? ($displayData->item->id . ':' . $displayData->item->alias) : $displayData->item->id;
$canEdit = ($user->authorise('core.edit', 'com_hwdmediashare.media.'.$displayData->item->id) || ($user->authorise('core.edit.own', 'com_hwdmediashare.media.'.$displayData->item->id) && ($displayData->item->created_user_id == $user->id)));
$canEditState = $user->authorise('core.edit.state', 'com_hwdmediashare.media.'.$displayData->item->id);
$canDelete = ($user->authorise('core.delete', 'com_hwdmediashare.media.'.$displayData->item->id) || ($user->authorise('core.edit.own', 'com_hwdmediashare.media.'.$displayData->item->id) && ($displayData->item->created_user_id == $user->id)));
?>
<div class="hwd-container text-center">
  <div class="media-details-view media-layout-documentation">
    <div class="row-fluid">
      <div class="span6 offset3">
        <?php if ($displayData->params->get('list_meta_thumbnail') != '0') :?>
        <div class="media-item<?php echo ($displayData->params->get('list_thumbnail_aspect') == 0 ? ' originalaspect' : ''); ?>">
          <div class="media-aspect<?php echo $displayData->params->get('list_thumbnail_aspect'); ?>"></div>        
          <?php if ($displayData->params->get('list_meta_type_icon') != '0') :?>
          <div class="media-item-format-1-<?php echo $displayData->item->media_type; ?>">
             <img src="<?php echo JHtml::_('hwdicon.overlay', '1-'.$displayData->item->media_type, $displayData->item); ?>" alt="<?php echo JText::_('COM_HWDMS_MEDIA_TYPE'); ?>" />
          </div>
          <?php endif; ?>
          <?php if ($displayData->params->get('list_meta_duration') != '0' && $displayData->item->duration > 0) :?>
          <div class="media-duration">
             <?php echo hwdMediaShareMedia::secondsToTime($displayData->item->duration); ?>
          </div>
          <?php endif; ?>
          <?php echo JHtml::_('HwdPopup.link', $displayData->item, '<img src="' . JRoute::_(hwdMediaShareThumbnails::thumbnail($displayData->item)) . '" border="0" alt="' . $displayData->escape($displayData->item->title) . '" class="media-thumb" />'); ?>                                      
        </div>
        <?php endif; ?>
        <?php if ($canEdit || $canDelete): ?>
        <div class="btn-group pull-right">
          <?php
            // Create dropdown items
            if ($canEdit) : 
              JHtml::_('hwddropdown.edit', $displayData->item->id, 'mediaform'); 
            endif;    
            if ($canEditState) :
              $action = $displayData->item->published == 1 ? 'unpublish' : 'publish';
              JHtml::_('hwddropdown.' . $action, $displayData->item->id, 'media'); 
            endif; 
            if ($canDelete && $displayData->item->published != -2) : 
              JHtml::_('hwddropdown.delete', $displayData->item->id, 'media'); 
            endif;         
            // Render dropdown list
            echo JHtml::_('hwddropdown.render', $displayData->escape($displayData->item->title), ' btn-micro');
          ?>                    
        </div>
        <?php endif; ?>
        <?php if ($displayData->item->featured && $displayData->params->get('list_meta_featured_status') != '0'): ?>
          <span class="label label-info pull-right"><?php echo JText::_('COM_HWDMS_FEATURED'); ?></span>
        <?php endif; ?>
        <!-- Title -->
        <h3 class="contentheading<?php echo ($displayData->params->get('list_tooltip_location') > '1' ? ' hasTooltip' : ''); ?>" title="<?php echo ($displayData->params->get('list_tooltip_location') > '1' ? JHtml::tooltipText($displayData->item->title, ($displayData->params->get('list_tooltip_contents') != '0' ? JHtml::_('string.truncate', $displayData->item->description, $displayData->params->get('list_desc_truncate'), false, false) : '')) : $displayData->item->title); ?>">
          <?php echo JHtml::_('HwdPopup.link', $displayData->item, $displayData->escape(JHtml::_('string.truncate', $displayData->item->title, $displayData->params->get('list_title_truncate'), false, false))); ?>
        </h3>
        <p class="media-info-description"><?php echo $displayData->escape(JHtml::_('string.truncate', $displayData->item->description, $displayData->params->get('list_desc_truncate'), false, false)); ?></p>
        <div class="btn-group">
          <a href="<?php echo JRoute::_(hwdMediaShareHelperRoute::getMediaItemRoute($displayData->item->id)); ?>" class="btn">Details</a>
          <?php echo JHtml::_('HwdPopup.link', $displayData->item, '<i class="icon-expand"></i>', array('class' => 'btn')); ?>
        </div>
      </div>
    </div>
  </div> 
</div>
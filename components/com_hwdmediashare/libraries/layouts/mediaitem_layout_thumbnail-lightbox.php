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
<div class="hwd-container media-layout-details">
  <div class="media-details-view media-layout-details" style="width:<?php echo $displayData->params->get('width', 300); ?>px">
    <!-- Thumbnail Image -->
    <?php if ($displayData->params->get('list_meta_thumbnail') != '0') :?>
    <div class="media-item<?php echo ($displayData->params->get('list_thumbnail_aspect') == 0 ? ' originalaspect' : ''); ?><?php echo ($displayData->params->get('list_tooltip_location') > '2' ? ' hasTooltip' : ''); ?>" title="<?php echo ($displayData->params->get('list_tooltip_location') > '1' ? JHtml::tooltipText($displayData->item->title, ($displayData->params->get('list_tooltip_contents') != '0' ? JHtml::_('string.truncate', $displayData->item->description, $displayData->params->get('list_desc_truncate'), false, false) : '')) : $displayData->item->title); ?>">
      <div class="media-aspect<?php echo $displayData->params->get('list_thumbnail_aspect'); ?>"></div>        
      <!-- Media Type -->
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
      <?php if ($displayData->params->get('list_link_thumbnails') == 1) :?>
        <?php echo JHtml::_('HwdPopup.link', $displayData->item, '<img src="' . JRoute::_(hwdMediaShareThumbnails::thumbnail($displayData->item)) . '" border="0" alt="' . $displayData->escape($displayData->item->title) . '" class="media-thumb" />'); ?>                                      
      <?php else: ?>
        <img src="<?php echo JRoute::_(hwdMediaShareThumbnails::thumbnail($displayData->item)); ?>" border="0" alt="<?php echo $displayData->escape($displayData->item->title); ?>" class="media-thumb" />
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div> 
</div>
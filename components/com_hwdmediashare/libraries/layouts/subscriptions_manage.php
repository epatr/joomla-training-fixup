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
?>
<div class="btn-toolbar">
  <div class="btn-group">
    <button type="button" class="btn btn-primary" onclick="Joomla.submitbutton('channels.unsubscribe')">
      <i class="icon-checkmark"></i> <?php echo JText::_('COM_HWDMS_UNSUBSCRIBE'); ?>
    </button>
  </div>  
</div>
<table class="table table-striped table-hover">
  <thead>
    <th><?php echo JHtml::_('grid.checkall'); ?></th>
  </thead>       
  <tbody>
    <?php foreach ($displayData->items as $id => $item) :
    $rowcount = (((int)$id) % (int) $displayData->columns) + 1;
    $item->slug = $item->alias ? ($item->id . ':' . $item->alias) : $item->id;
    $canEdit = ($user->authorise('core.edit', 'com_hwdmediashare.channel.'.$item->id) || ($user->authorise('core.edit.own', 'com_hwdmediashare.channel.'.$item->id) && ($item->created_user_id == $user->id)));
    $canEditState = $user->authorise('core.edit.state', 'com_hwdmediashare.channel.'.$item->id);
    $canDelete = ($user->authorise('core.delete', 'com_hwdmediashare.channel.'.$item->id) || ($user->authorise('core.edit.own', 'com_hwdmediashare.channel.'.$item->id) && ($item->created_user_id == $user->id)));
    ?>
    <tr>
      <td>
        <div class="row-fluid">
          <div class="span1">
            <?php echo JHtml::_('grid.id', $id, $item->id); ?>  
          </div>              
          <?php if ($displayData->params->get('list_meta_thumbnail') != '0') :?>
          <div class="span2">
            <div class="media-item<?php echo ($displayData->params->get('list_thumbnail_aspect') == 0 ? ' originalaspect' : ''); ?><?php echo ($displayData->params->get('list_tooltip_location') > '2' ? ' hasTooltip' : ''); ?>" title="<?php echo ($displayData->params->get('list_tooltip_location') > '1' ? JHtml::tooltipText($item->title, ($displayData->params->get('list_tooltip_contents') != '0' ? JHtml::_('string.truncate', $item->description, $displayData->params->get('list_desc_truncate'), false, false) : '')) : $item->title); ?>">
              <div class="media-aspect<?php echo $displayData->params->get('list_thumbnail_aspect'); ?>"></div>        
              <!-- Media Type -->
              <?php if ($displayData->params->get('list_meta_type_icon') != '0') :?>
              <div class="media-item-format-2">
                <img src="<?php echo JHtml::_('hwdicon.overlay', 6, $item); ?>" alt="<?php echo JText::_('COM_HWDMS_CHANNEL'); ?>" />
              </div>
              <?php endif; ?>
              <?php if ($displayData->params->get('list_link_thumbnails') == 1) :?><a href="<?php echo JRoute::_(hwdMediaShareHelperRoute::getChannelRoute($item->slug)); ?>"><?php endif; ?>
                <img src="<?php echo JRoute::_(hwdMediaShareThumbnails::thumbnail($item, 2)); ?>" border="0" alt="<?php echo $displayData->escape($item->title); ?>" class="media-thumb" />
              <?php if ($displayData->params->get('list_link_thumbnails') == 1) :?></a><?php endif; ?>
            </div>
          </div>
          <?php endif; ?>
          <div class="span<?php echo $displayData->params->get('list_meta_thumbnail') != '0' ? 9: 11; ?>">
            <?php if ($canEdit || $canDelete): ?>
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
            <?php endif; ?>
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
              <h<?php echo $displayData->params->get('list_item_heading'); ?> class="contentheading<?php echo ($displayData->params->get('list_tooltip_location') > '1' ? ' hasTooltip' : ''); ?>" title="<?php echo ($displayData->params->get('list_tooltip_location') > '1' ? JHtml::tooltipText($item->title, ($displayData->params->get('list_tooltip_contents') != '0' ? JHtml::_('string.truncate', $item->description, $displayData->params->get('list_desc_truncate'), false, false) : '')) : $item->title); ?>">
                <?php if ($displayData->params->get('list_link_titles') == 1) :?><a href="<?php echo JRoute::_(hwdMediaShareHelperRoute::getChannelRoute($item->slug)); ?>"><?php endif; ?>
                  <?php echo $displayData->escape(JHtml::_('string.truncate', $item->title, $displayData->params->get('list_title_truncate'), false, false)); ?> 
                <?php if ($displayData->params->get('list_link_titles') == 1) :?></a><?php endif; ?>
              </h<?php echo $displayData->params->get('list_item_heading'); ?>>
            <?php endif; ?> 
            <div class="clearfix"></div>
            <?php if ($displayData->params->get('list_meta_hits') != '0' || $displayData->params->get('list_meta_likes') != '0' || $displayData->params->get('list_meta_dislikes') != '0') :?>
            <div class="pull-right">
              <?php if ($displayData->params->get('list_meta_hits') != '0') :?>
                <div class="media-info-hits pull-right"><?php echo JText::sprintf('COM_HWDMS_X_VIEWS', number_format((int) $item->hits)); ?></div>
              <?php endif; ?>
              <?php if ($displayData->params->get('list_meta_likes') != '0' || $displayData->params->get('list_meta_dislikes') != '0') :?>
                <div class="media-info-likes pull-right">      
	          <?php if ($displayData->params->get('list_meta_likes') != '0') :?><i class="icon-thumbs-up"></i> <span id="media-likes"><?php echo (int) $item->likes; ?></span><?php endif; ?>
	          <?php if ($displayData->params->get('list_meta_dislikes') != '0') :?><i class="icon-thumbs-down"></i> <span id="media-dislikes"><?php echo (int) $item->dislikes; ?></span><?php endif; ?>
                </div>
              <?php endif; ?>
            </div>       
            <?php endif; ?>    
            <!-- Item Meta -->
            <dl class="media-info">
              <dt class="media-info-term"><?php echo JText::_('COM_HWDMS_DETAILS'); ?> </dt>     
              <?php if ($displayData->params->get('list_meta_description') != '0') :?>
                <dd class="media-info-description"><?php echo $displayData->escape(JHtml::_('string.truncate', $item->description, $displayData->params->get('list_desc_truncate'), false, false)); ?></dd>
              <?php endif; ?>      
            </dl>
          </div>
        </div>  
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

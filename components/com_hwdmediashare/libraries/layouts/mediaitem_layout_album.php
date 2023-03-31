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
JHtml::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR . '/helpers/html');
JHtml::_('HwdPopup.iframe', 'form');
JHtml::_('HwdPopup.iframe', 'page');

$user = JFactory::getUser();
$displayData->album->slug = $displayData->album->alias ? ($displayData->album->id . ':' . $displayData->album->alias) : $displayData->album->id;
$canEdit = ($user->authorise('core.edit', 'com_hwdmediashare.album.'.$displayData->album->id) || ($user->authorise('core.edit.own', 'com_hwdmediashare.album.'.$displayData->album->id) && ($displayData->album->created_user_id == $user->id)));
$canEditState = $user->authorise('core.edit.state', 'com_hwdmediashare.album.'.$displayData->album->id);
$canDelete = ($user->authorise('core.delete', 'com_hwdmediashare.album.'.$displayData->album->id) || ($user->authorise('core.edit.own', 'com_hwdmediashare.album.'.$displayData->album->id) && ($displayData->album->created_user_id == $user->id)));
$canAddMedia = (($user->authorise('hwdmediashare.upload', 'com_hwdmediashare') || $user->authorise('hwdmediashare.import', 'com_hwdmediashare')) && ($displayData->album->created_user_id == $user->id));
$canManageMedia = ($displayData->album->created_user_id == $user->id);
$layout = $displayData->params->get('lightbox_mode') != 0 ? 'media_' . $displayData->display . '_lightbox' :  'media_' . $displayData->display;
?>
<div class="hwd-container">
    <div class="media-<?php echo $displayData->display; ?>-view">
      <?php if (empty($displayData->items)) : ?>
        <div class="alert alert-no-items">
          <?php echo JText::_('COM_HWDMS_NOTHING_TO_SHOW'); ?>
        </div>
      <?php else : ?>
        <?php echo JLayoutHelper::render($layout, $displayData, JPATH_ROOT.'/components/com_hwdmediashare/libraries/layouts'); ?>
      <?php endif; ?>        
    </div>    
    <div class="clear"></div>
    <!-- Description -->
    <div class="well media-album-description">
      <!-- Media Header -->
      <div class="media-header">
        <!-- Buttons -->        
        <h2 class="media-album-title">
          <a href="<?php echo JRoute::_(hwdMediaShareHelperRoute::getAlbumRoute($displayData->album->slug)); ?>">
            <?php echo $displayData->escape($displayData->album->title); ?>
          </a>
        </h2>  
      </div>
      <?php if ($displayData->params->get('item_meta_description') != '0') :?>
        <?php echo JHtml::_('content.prepare', $displayData->album->description); ?>
      <?php endif; ?> 
      <?php if ($displayData->params->get('item_meta_likes') != '0' || $displayData->params->get('item_meta_dislikes') != '0') :?>
      <div class="pull-right">
        <div class="media-info-likes pull-right">      
          <?php if ($displayData->params->get('item_meta_likes') != '0') :?><i class="icon-thumbs-up"></i> <span id="media-likes"><?php echo (int) $displayData->album->likes; ?></span><?php endif; ?>
          <?php if ($displayData->params->get('item_meta_dislikes') != '0') :?><i class="icon-thumbs-down"></i> <span id="media-dislikes"><?php echo (int) $displayData->album->dislikes; ?></span><?php endif; ?>
        </div>
      </div>       
      <?php endif; ?>        
      <?php if ($displayData->params->get('item_meta_media_count') != '0' || $displayData->params->get('item_meta_author') != '0' || $displayData->params->get('item_meta_created') != '0' || $displayData->params->get('item_meta_hits') != '0') :?>
      <dl class="media-info">
        <dt class="media-info-term"><?php echo JText::_('COM_HWDMS_DETAILS'); ?> </dt>      
        <?php if ($displayData->params->get('item_meta_author') != '0' || $displayData->params->get('item_meta_created') != '0') : ?>
        <dd class="media-info-meta">
          <?php if ($displayData->params->get('item_meta_author') != '0') : ?>
            <span class="media-info-createdby">
              <?php echo JText::sprintf('COM_HWDMS_BY_X_USER', '<a href="'.JRoute::_(hwdMediaShareHelperRoute::getUserRoute($displayData->album->created_user_id)).'">'.htmlspecialchars($displayData->album->author, ENT_COMPAT, 'UTF-8').'</a>'); ?>
            </span>
          <?php endif; ?>
          <?php if ($displayData->params->get('item_meta_created') != '0') : ?>
            <span class="media-info-created">
              <?php echo JHtml::_('hwddate.relative', $displayData->album->created); ?>
            </span>
          <?php endif; ?>
        </dd>
        <?php endif; ?>   
      </dl>
      <?php endif; ?>  
      <div class="clear"></div>        
    </div>
</div>

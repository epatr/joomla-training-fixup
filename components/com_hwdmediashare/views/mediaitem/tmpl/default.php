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

$user = JFactory::getUser();
$canEdit = ($user->authorise('core.edit', 'com_hwdmediashare.media.'.$this->item->id) || ($user->authorise('core.edit.own', 'com_hwdmediashare.media.'.$this->item->id) && ($this->item->created_user_id == $user->id)));
$canEditState = $user->authorise('core.edit.state', 'com_hwdmediashare.media.'.$this->item->id);
$canDelete = ($user->authorise('core.delete', 'com_hwdmediashare.media.'.$this->item->id) || ($user->authorise('core.edit.own', 'com_hwdmediashare.media.'.$this->item->id) && ($this->item->created_user_id == $user->id)));

// Testing numbers. Replace with your own.
$value = $this->item->likes;
$max = $this->item->likes + $this->item->dislikes;
$scale = 1.0;
// Get Percentage out of 100
if ( !empty($max) ) { $percent = round(($value * 100) / $max); } 
else { $percent = 0; }
// Limit to 100 percent (if more than the max is allowed)
if ( $percent > 100 ) { $percent = 100; } 
?>
  <div id="hwd-container" class="<?php echo $this->pageclass_sfx;?>"> <a name="top" id="top"></a>
    <!-- Media Navigation -->
    <?php echo hwdMediaShareHelperNavigation::getInternalNavigation(); ?>
    <!-- Media Header -->
    <div class="media-header">
      <?php if ($this->params->get('mediaitem_meta_title') != '0') :?>
        <h2 class="media-media-title"><?php echo $this->escape($this->params->get('page_heading')); ?></h2>
      <?php endif; ?>     
      <!-- Buttons -->
      <div class="btn-group pull-right">
        <?php if ($this->params->get('enable_subscriptions') && $this->params->get('mediaitem_subscribe_button') != '0' && $this->item->created_user_id != 0) : ?>
          <?php if ($this->item->isSubscribed) : ?>
              <a title="<?php echo JText::_('COM_HWDMS_SUBSCRIBE'); ?>" href="<?php echo JRoute::_('index.php?option=com_hwdmediashare&task=channels.unsubscribe&id=' . $this->item->created_user_id . '&return=' . $this->return . '&tmpl=component&'.JSession::getFormToken().'=1'); ?>" class="btn active" id="media-subscribe-btn" data-media-task="unsubscribe" data-media-id="<?php echo $this->item->created_user_id; ?>" data-media-return="<?php echo $this->return; ?>" data-media-token="<?php echo JSession::getFormToken(); ?>"><i class="icon-checkmark"></i> <?php echo JText::_('COM_HWDMS_SUBSCRIBED'); ?></a>
          <?php else : ?>
              <a title="<?php echo JText::_('COM_HWDMS_SUBSCRIBE'); ?>" href="<?php echo JRoute::_('index.php?option=com_hwdmediashare&task=channels.subscribe&id=' . $this->item->created_user_id . '&return=' . $this->return . '&tmpl=component&'.JSession::getFormToken().'=1'); ?>" class="btn" id="media-subscribe-btn" data-media-task="subscribe" data-media-id="<?php echo $this->item->created_user_id; ?>" data-media-return="<?php echo $this->return; ?>" data-media-token="<?php echo JSession::getFormToken(); ?>"><i class="icon-user"></i> <?php echo JText::_('COM_HWDMS_SUBSCRIBE'); ?></a>
          <?php endif; ?>
        <?php endif; ?>
        <?php if ($this->item->featured && $this->params->get('item_meta_featured_status') != '0') : ?>
          <div class="btn btn-info btn-noevent"><?php echo JText::_('COM_HWDMS_FEATURED'); ?></div>
        <?php endif; ?>
        <?php if ($this->item->status != 1) : ?>
          <div class="btn btn-danger btn-noevent"><?php echo $this->utilities->getReadableStatus($this->item); ?></div>
        <?php endif; ?>  
        <?php if ($canEdit || $canDelete || ($this->hasMeta() && $this->params->get('mediaitem_metadata_button') != '0') || ($this->hasDownloads() && $this->item->type != 7 && $this->params->get('mediaitem_allsizes_button') != '0')): ?>
        <?php
        // Create dropdown items
        if ($canEdit) : 
          JHtml::_('hwddropdown.edit', $this->item->id, 'mediaform'); 
        endif;    
        if ($canEditState) :
          $action = $this->item->published == 1 ? 'unpublish' : 'publish';
          JHtml::_('hwddropdown.' . $action, $this->item->id, 'media'); 
        endif; 
        if ($canDelete && $this->item->published != -2) : 
          JHtml::_('hwddropdown.delete', $this->item->id, 'media'); 
        endif;   
        if ($this->hasMeta() && $this->params->get('mediaitem_metadata_button') != '0') : 
          JHtml::_('hwddropdown.meta', $this->item->id, 'mediaform'); 
        endif;  
        if ($this->hasDownloads() && $this->item->type != 7 && $this->params->get('mediaitem_allsizes_button') != '0') :
          JHtml::_('hwddropdown.downloads', $this->item->id, 'mediaform'); 
        endif;  
        // Render dropdown list       
        echo JHtml::_('hwddropdown.render', $this->escape($this->item->title));
        ?>
        <?php endif; ?>
      </div>
      <div class="clear"></div>
      <!-- Search Filters -->
      <?php //echo JLayoutHelper::render('search_tools', array('view' => $this), JPATH_ROOT.'/components/com_hwdmediashare/libraries/layouts'); ?>
      <div class="clear"></div>
    </div>
    <div class="media-<?php echo $this->display; ?>-view">
        <?php //echo JLayoutHelper::render('media_' . $this->display, $this, JPATH_ROOT.'/components/com_hwdmediashare/libraries/layouts'); ?>
    </div> 
    <div class="clear"></div>

  <div id="media-item-container" class="media-item-container">
    <!-- Load module position above the player -->
    <?php echo hwdMediaShareHelperModule::_loadpos('media-above-player'); ?>
    <!-- Item Media -->
    <div class="media-item-full" id="media-item-<?php echo $this->item->id; ?>">
      <?php echo hwdMediaShareMedia::get($this->item); ?>
      <!-- Navigation -->
      <?php echo $this->loadTemplate('navigation'); ?>
    </div>
    <!-- Load module position below the player -->
    <?php echo hwdMediaShareHelperModule::_loadpos('media-below-player'); ?>      
    <div class="clear"></div>
    <!-- Media Actions -->
    <div class="media-actions-container">
      <div class="btn-group">
      <?php if (($this->params->get('mediaitem_like_button') != '0' || $this->params->get('mediaitem_dislike_button') != '0') && $this->item->params->get('allow_likes') != '0') : ?>
        <?php if ($this->params->get('mediaitem_like_button') != '0') : ?>
          <a title="<?php echo JText::_('COM_HWDMS_LIKE'); ?>" href="<?php echo JRoute::_('index.php?option=com_hwdmediashare&task=media.like&id=' . $this->item->id . '&return=' . $this->return . '&tmpl=component&'.JSession::getFormToken().'=1'); ?>" class="btn btn-mini" id="media-like-btn" data-media-task="like" data-media-id="<?php echo $this->item->id; ?>" data-media-return="<?php echo $this->return; ?>" data-media-token="<?php echo JSession::getFormToken(); ?>"><i class="icon-thumbs-up"></i> <?php echo JText::_('COM_HWDMS_LIKE'); ?></a>
        <?php endif; ?>
        <?php if ($this->params->get('mediaitem_dislike_button') != '0') : ?>
          <a title="<?php echo JText::_('COM_HWDMS_DISLIKE'); ?>" href="<?php echo JRoute::_('index.php?option=com_hwdmediashare&task=media.dislike&id=' . $this->item->id . '&return=' . $this->return . '&tmpl=component&'.JSession::getFormToken().'=1'); ?>" class="btn btn-mini" id="media-dislike-btn" data-media-task="dislike" data-media-id="<?php echo $this->item->id; ?>" data-media-return="<?php echo $this->return; ?>" data-media-token="<?php echo JSession::getFormToken(); ?>"><i class="icon-thumbs-down"></i> <?php echo JText::_('COM_HWDMS_DISLIKE'); ?></a>
        <?php endif; ?>
      <?php endif; ?>
      <?php if ($this->params->get('mediaitem_favourite_button') != '0') : ?>
        <?php if ($this->item->isFavourite) : ?>
          <a title="<?php echo JText::_('COM_HWDMS_FAVOURITE'); ?>" href="<?php echo JRoute::_('index.php?option=com_hwdmediashare&task=media.unfavourite&id=' . $this->item->id . '&return=' . $this->return . '&tmpl=component&'.JSession::getFormToken().'=1'); ?>" class="btn btn-mini active" id="media-favourite-btn" data-media-task="unfavourite" data-media-id="<?php echo $this->item->id; ?>" data-media-return="<?php echo $this->return; ?>" data-media-token="<?php echo JSession::getFormToken(); ?>"><i class="icon-heart red"></i> <?php echo JText::_('COM_HWDMS_FAVOURITE'); ?></a>
        <?php else : ?>
          <a title="<?php echo JText::_('COM_HWDMS_FAVOURITE'); ?>" href="<?php echo JRoute::_('index.php?option=com_hwdmediashare&task=media.favourite&id=' . $this->item->id . '&return=' . $this->return . '&tmpl=component&'.JSession::getFormToken().'=1'); ?>" class="btn btn-mini" id="media-favourite-btn" data-media-task="favourite" data-media-id="<?php echo $this->item->id; ?>" data-media-return="<?php echo $this->return; ?>" data-media-token="<?php echo JSession::getFormToken(); ?>"><i class="icon-heart"></i> <?php echo JText::_('COM_HWDMS_FAVOURITE'); ?></a>
        <?php endif; ?>
      <?php endif; ?>
      <?php if (($this->params->get('enable_categories') || $this->params->get('enable_albums') || $this->params->get('enable_groups') || $this->params->get('enable_playlists')) && $this->params->get('mediaitem_add_button') != '0' && $user->id) : ?>
          <a title="<?php echo JText::_('COM_HWDMS_ADD_TO'); ?>" href="<?php echo JRoute::_('index.php?option=com_hwdmediashare&task=mediaform.link&id=' . $this->item->id . '&return=' . $this->return . '&tmpl=component'); ?>" class="btn btn-mini media-add-link media-popup-iframe-form" id="media-favadd-link"><i class="icon-plus"></i> <?php echo JText::_('COM_HWDMS_ADD'); ?></a>
      <?php endif; ?>
      <?php if ($this->params->get('mediaitem_share_button') != '0') : ?>
          <a title="<?php echo JText::_('COM_HWDMS_SHARE'); ?>" href="<?php echo JRoute::_('index.php?option=com_hwdmediashare&task=mediaform.share&id=' . $this->item->id . '&return=' . $this->return . '&tmpl=component'); ?>" class="btn btn-mini media-share-link media-popup-iframe-form" id="media-favadd-link"><i class="icon-share"></i> <?php echo JText::_('COM_HWDMS_SHARE'); ?></a>
      <?php endif; ?>
      <?php if ($this->params->get('mediaitem_location_button') != '0' && !empty($this->item->location)) : ?>
          <a title="<?php echo JText::_('COM_HWDMS_LOCATION'); ?>" href="https://www.google.com/maps/embed/v1/place?key=<?php echo $this->params->get('google_maps_api_v3_key', 'AIzaSyAyxMefjQ8yajKyvBTMD9KJq-M6n80CGsw'); ?>&q=<?php echo urlencode($this->item->location); ?>" class="btn btn-mini media-location-link media-popup-iframe-form" id="media-favadd-link"><i class="icon-location"></i> <?php echo JText::_('COM_HWDMS_LOCATION'); ?></a>
      <?php endif; ?>
      <?php if ($this->params->get('mediaitem_report_button') != '0') : ?>
          <a title="<?php echo JText::_('COM_HWDMS_REPORT'); ?>" href="<?php echo JRoute::_('index.php?option=com_hwdmediashare&task=mediaform.report&id=' . $this->item->id . '&return=' . $this->return . '&tmpl=component'); ?>" class="btn btn-mini media-share-link media-popup-iframe-form" id="media-favadd-link"><i class="icon-flag"></i> <?php echo JText::_('COM_HWDMS_REPORT'); ?></a>
      <?php endif; ?>  
      <?php if ($this->params->get('mediaitem_download_button') != '0' && $downloadLink = $this->hasDownloads()) : ?>
          <a title="<?php echo JText::_('COM_HWDMS_DOWNLOAD'); ?>" href="<?php echo $downloadLink; ?>" class="btn btn-mini media-download-link<?php echo strpos($downloadLink, 'mediaform.download') === false ? '' : ' media-popup-iframe-form'; ?>" <?php echo $this->item->type == '7' ? 'target="_blank"' : ''; ?> id="media-favadd-link"><i class="icon-download"></i> <?php echo JText::_('COM_HWDMS_DOWNLOAD'); ?></a>
      <?php endif; ?>
      <?php if ($this->params->get('mediaitem_quality_button') != '0' && $qualities = $this->hasQualities()): ?>
        <?php foreach($qualities as $quality) : ?>
            <?php JHtml::_('hwddropdown.quality', $this->item, $quality); ?>
        <?php endforeach; ?>    
        <?php echo JHtml::_('hwddropdown.renderQualities', $this->escape($this->item->title), ' btn-mini'); ?>
      <?php endif; ?>
      </div>          
      <div class="clear"></div>
    </div>
    <!-- Item Meta -->
    <?php if ($this->item->media_type == 1) : ?>
      <img src="<?php echo JRoute::_(hwdMediaShareThumbnails::thumbnail($this->item)); ?>" border="0" alt="<?php echo $this->escape($this->item->title); ?>" class="media-info-thumbnail" title="<?php echo JHtml::tooltipText($this->item->title, ($this->params->get('list_tooltip_contents') != '0' ? JHtml::_('string.truncate', $this->item->description, $this->params->get('list_desc_truncate'), false, false) : '')); ?>" />
    <?php endif; ?>
    <div class="media-info-container">
      <?php if ($this->params->get('mediaitem_meta_hits') != '0') : ?><div class="media-count"><?php echo number_format((int) $this->item->hits); ?></div><?php endif; ?>
      <?php if ($this->params->get('mediaitem_meta_author') != '0' || $this->params->get('mediaitem_meta_created') != '0') : ?>
        <div class="media-maker">
          <?php if ($this->params->get('mediaitem_meta_author') != '0' && $this->params->get('mediaitem_meta_created') != '0') : ?>
            <?php echo JText::sprintf('COM_HWDMS_BY_X_USER_XAGO', '<a href="'.JRoute::_(hwdMediaShareHelperRoute::getUserRoute($this->item->created_user_id)).'">'.htmlspecialchars($this->item->author, ENT_COMPAT, 'UTF-8').'</a>', JHtml::_('hwddate.relative', $this->item->created)); ?>
          <?php elseif ($this->params->get('mediaitem_meta_author') != '0'): ?>
            <?php echo JText::sprintf('COM_HWDMS_BY_X_USER', '<a href="'.JRoute::_(hwdMediaShareHelperRoute::getUserRoute($this->item->created_user_id)).'">'.htmlspecialchars($this->item->author, ENT_COMPAT, 'UTF-8').'</a>'); ?>
          <?php elseif ($this->params->get('mediaitem_meta_created') != '0'): ?>
            <?php echo JHtml::_('hwddate.relative', $this->item->created); ?>
          <?php endif; ?>
        </div>
      <?php endif; ?>
      <?php if ($this->params->get('mediaitem_meta_likes') != '0' && ($this->params->get('mediaitem_like_button') != '0' || $this->params->get('mediaitem_dislike_button') != '0')) : ?>
      <div class="media-rating-stats">          
        <div class="percentbar">
          <div id="percentbar-active" style="width:<?php echo round($percent * $scale); ?>px;"></div>
        </div>
        <div class="rating-stats">
          <?php if ($this->params->get('mediaitem_like_button') != '0') : ?>
            <i class="icon-thumbs-up"></i> <span id="media-likes"><?php echo (int) $this->item->likes; ?></span>
          <?php endif; ?>
          <?php if ($this->params->get('mediaitem_dislike_button') != '0') : ?>
            <i class="icon-thumbs-down"></i> <span id="media-dislikes"><?php echo (int) $this->item->dislikes; ?></span>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
      <div class="clear"></div>
    </div>
    <div class="clear"></div>
    <!-- Load module position below the buttons-->
    <?php echo hwdMediaShareHelperModule::_loadpos('media-below-buttons'); ?>
    <div class="clear"></div>
    <!-- Tabs --> 
    <?php // If we insert the tab panel but no tab panes we'll get a Javascript error so we check if we need to show it first
    if ((($this->params->get('mediaitem_description') != '0' && !empty($this->item->description)) || ($this->params->get('mediaitem_tags') != '0' && count($this->item->tags->itemTags) > 0)) || 
        ($this->params->get('mediaitem_activity') != '0' && count($this->activities) > 0) || 
        ($this->media_tab_modules)) : ?>
    <?php echo JHtml::_('bootstrap.startTabSet', 'pane', array('active' => 'description')); ?>
      <?php if (($this->params->get('mediaitem_description') != '0' && !empty($this->item->description)) || ($this->params->get('mediaitem_tags') != '0' && count($this->item->tags->itemTags) > 0)) : ?>
        <?php echo JHtml::_('bootstrap.addTab', 'pane', 'description', JText::_('COM_HWDMS_ABOUT', true)); ?>
          <?php if ($this->params->get('mediaitem_tags') != '0'): ?>
            <?php $this->item->tagLayout = new JLayoutFile('joomla.content.tags');
            echo $this->item->tagLayout->render($this->item->tags->itemTags); ?>
          <?php endif; ?>    
          <?php if ($this->params->get('mediaitem_description') != '0') : ?>
            <?php echo JHtml::_('content.prepare',$this->item->description); ?>
          <?php endif; ?>    
          <div class="clear"></div>
        <?php echo JHtml::_('bootstrap.endTab'); ?>
      <?php endif; ?> 
      <?php if ($this->params->get('mediaitem_activity') != '0' && count($this->activities) > 0) : ?>
        <?php echo JHtml::_('bootstrap.addTab', 'pane', 'activity', JText::_('COM_HWDMS_ACTIVITY', true)); ?>
          <ul class="media-activity-list">
            <?php foreach ($this->activities as $activity) : ?>
              <li class="media-activity-item">
                <a href="<?php echo JRoute::_(hwdMediaShareHelperRoute::getUserRoute($activity->actor)); ?>" class="media-activity-avatar">
                  <?php echo JHtml::_('hwdimage.avatar', $activity->actor, 50); ?></a>
                <p class="media-activity-desc"><?php echo hwdMediaShareActivities::renderActivityHtml($activity); ?></p>
                <p class="media-activity-date"><?php echo JHtml::_('hwddate.relative', $activity->created); ?></p>
              </li>
            <?php endforeach; ?>
          </ul>
          <div class="clear"></div>
        <?php echo JHtml::_('bootstrap.endTab'); ?>
      <?php endif; ?> 
      <?php echo hwdMediaShareHelperModule::_loadtab('media-tabs'); ?>    
    <?php echo JHtml::_('bootstrap.endTabSet'); ?>
    <?php endif; ?>
    <div class="clear"></div>
      <!-- Custom fields -->
      <?php // echo $this->item->customfields->values->get(''); // Optional method to obtain custom field data ?>
      <dl class="media-info">
        <?php foreach($this->item->customfields->fields as $group => $fields) : ?>
          <dt class="media-info-group"><?php echo JText::_($group); ?> </dt>
          <?php foreach($fields as $field) : ?>
            <dd class="media-info-<?php echo $field->fieldcode; ?>"><?php echo $field->name; ?>: <?php echo $this->item->customfields->display($field); ?></dd>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </dl>
    <div class="clear"></div>
    <!-- Load module position below the buttons-->
    <?php echo hwdMediaShareHelperModule::_loadpos('media-above-comments'); ?>
    <div class="clear"></div>
    <?php echo $this->getComments($this->item); ?>
  </div>
  <div class="clear"></div>
  <!-- Clears Top Link -->
  <div class="clear"></div>
  </div>
<div class="clear"></div>

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
$canEdit = ($user->authorise('core.edit', 'com_hwdmediashare.channel.'.$this->channel->id) || ($user->authorise('core.edit.own', 'com_hwdmediashare.channel.'.$this->channel->id) && ($this->channel->created_user_id == $user->id)));
$canEditState = $user->authorise('core.edit.state', 'com_hwdmediashare.channel.'.$this->channel->id);
$canDelete = ($user->authorise('core.delete', 'com_hwdmediashare.channel.'.$this->channel->id) || ($user->authorise('core.edit.own', 'com_hwdmediashare.channel.'.$this->channel->id) && ($this->channel->created_user_id == $user->id)));
$canAdd = ($user->authorise('hwdmediashare.upload', 'com_hwdmediashare') || $user->authorise('hwdmediashare.import', 'com_hwdmediashare'));
?>
<form action="<?php echo htmlspecialchars(JFactory::getURI()->toString()); ?>" method="post" name="adminForm" id="adminForm">
  <div id="hwd-container" class="<?php echo $this->pageclass_sfx;?>"> <a name="top" id="top"></a>
    <!-- Media Navigation --> 
    <?php echo hwdMediaShareHelperNavigation::getInternalNavigation(); ?>
    <!-- Art Header -->
    <div class="row-fluid media-details-view">
      <div class="span12">
        <div class="media-item">
          <div class="media-aspect31"></div>        
          <!-- Avatar -->
          <?php if ($this->params->get('community_avatar') != 'none') :?>
          <div class="media-channel-avatar">
            <?php echo JHtml::_('hwdimage.avatar', $this->channel->id, 75); ?>
          </div>
          <?php endif; ?>
          <div class="media-channel-overlay">
            <?php if ($this->channel->featured && $this->params->get('item_meta_featured_status') != '0'): ?>
              <span class="label label-info pull-right"><?php echo JText::_('COM_HWDMS_FEATURED'); ?></span>
            <?php endif; ?>
            <?php if ($this->channel->status != 1) : ?>
              <span class="label pull-right"><?php echo $this->utilities->getReadableStatus($this->channel); ?></span>
            <?php endif; ?>
            <?php if ($this->channel->published != 1) : ?>
              <span class="label pull-right"><?php echo JText::_('COM_HWDMS_UNPUBLISHED'); ?></span>
            <?php endif; ?> 
            <!-- Metadata -->
            <?php if ($this->params->get('item_meta_hits') != '0' || $this->params->get('item_meta_likes') != '0' || $this->params->get('item_meta_dislikes') != '0') :?>
            <div class="pull-right">
              <?php if ($this->params->get('item_meta_hits') != '0') :?>
                <div class="media-info-hits pull-right"><?php echo JText::sprintf('COM_HWDMS_X_VIEWS', number_format((int) $this->channel->hits)); ?></div>
              <?php endif; ?>
              <?php if ($this->params->get('item_meta_likes') != '0' || $this->params->get('item_meta_dislikes') != '0') :?>
                <div class="media-info-likes pull-right">      
	          <?php if ($this->params->get('item_meta_likes') != '0') :?><i class="icon-thumbs-up"></i> <span id="media-likes"><?php echo (int) $this->channel->likes; ?></span><?php endif; ?>
	          <?php if ($this->params->get('item_meta_dislikes') != '0') :?><i class="icon-thumbs-down"></i> <span id="media-dislikes"><?php echo (int) $this->channel->dislikes; ?></span><?php endif; ?>
                </div>
              <?php endif; ?>
            </div>       
            <?php endif; ?>              
            <!-- Title -->
            <?php if ($this->params->get('item_meta_title') != '0') :?>
              <h2 class="media-channel-title">
                  <?php echo $this->escape(JHtml::_('string.truncate', $this->channel->title, $this->params->get('list_title_truncate'), false, false)); ?> 
              </h2>
            <?php endif; ?> 
            <?php if ($this->params->get('item_meta_description') != '0') :?>
              <?php echo JHtml::_('string.truncate', $this->channel->description, $this->params->get('list_desc_truncate'), false, false); ?>
            <?php endif; ?>              
            <div class="clearfix"></div>  
          </div>              
          <img src="<?php echo JRoute::_(hwdMediaShareThumbnails::getChannelArt($this->channel)); ?>" border="0" alt="<?php echo $this->escape($this->channel->title); ?>" class="media-thumb" />
        </div>
      </div>
    </div>
    <!-- Media Header -->
    <div class="media-header">
      <!-- Buttons -->
      <div class="btn-group pull-right">
        <?php if ($this->params->get('enable_subscriptions') && $this->channel->id != 0) : ?>
          <?php if ($this->channel->isSubscribed) : ?>
          <a title="<?php echo JText::_('COM_HWDMS_UNSUBSCRIBE'); ?>" href="<?php echo JRoute::_('index.php?option=com_hwdmediashare&task=channels.unsubscribe&id=' . $this->channel->id . '&return=' . $this->return . '&tmpl=component&'.JSession::getFormToken().'=1'); ?>" class="btn active"><i class="icon-user"></i> <?php echo JText::_('COM_HWDMS_UNSUBSCRIBE'); ?></a>
          <?php else : ?>
          <a title="<?php echo JText::_('COM_HWDMS_SUBSCRIBE'); ?>" href="<?php echo JRoute::_('index.php?option=com_hwdmediashare&task=channels.subscribe&id=' . $this->channel->id . '&return=' . $this->return . '&tmpl=component&'.JSession::getFormToken().'=1'); ?>" class="btn"><i class="icon-user"></i> <?php echo JText::_('COM_HWDMS_SUBSCRIBE'); ?></a>
          <?php endif; ?>
        <?php endif; ?>          
        <?php if ($this->params->get('item_meta_report') != '0'): ?>  
          <a title="<?php echo JText::_('COM_HWDMS_REPORT'); ?>" href="<?php echo JRoute::_('index.php?option=com_hwdmediashare&task=channelform.report&id=' . $this->channel->id . '&return=' . $this->return . '&tmpl=component'); ?>" class="btn media-popup-iframe-form"><i class="icon-flag"></i> <?php echo JText::_('COM_HWDMS_REPORT'); ?></a>
        <?php endif; ?>    
        <?php if ($canEdit || $canDelete): ?>
        <?php
        // Create dropdown items
        if ($canEdit) : 
          JHtml::_('hwddropdown.edit', $this->channel->id, 'channelform'); 
        endif;    
        if ($canEditState) :
          $action = $this->channel->published ? 'unpublish' : 'publish';
          JHtml::_('hwddropdown.' . $action, $this->channel->id, 'channels'); 
        endif; 
        if ($canEdit) : 
          JHtml::_('hwddropdown.delete', $this->channel->id, 'channels'); 
        endif;         
        // Render dropdown list       
        echo JHtml::_('hwddropdown.render', $this->escape($this->channel->title));
        ?>
        <?php endif; ?>
      </div>        
      <div class="clear"></div>
      <!-- Media Main Navigation -->
      <div class="media-tabmenu">
        <ul>
          <?php if ($this->channel->nummedia > 0) : ?><li class="<?php echo ($this->layout == 'media' ? 'active ' : false); ?>media-tabmenu-media"><a href="<?php echo JRoute::_('index.php?option=com_hwdmediashare&view=channel&id=' . $this->channel->id . '&layout=media&display='.$this->display); ?>"><?php echo (int) $this->channel->nummedia; ?> Media</a></li><?php endif; ?>
          <?php if ($this->channel->numfavourites > 0) : ?><li class="<?php echo ($this->layout == 'favourites' ? 'active ' : false); ?>media-tabmenu-favourites"><a href="<?php echo JRoute::_('index.php?option=com_hwdmediashare&view=channel&id=' . $this->channel->id . '&layout=favourites&display='.$this->display); ?>"><?php echo (int) $this->channel->numfavourites; ?> Favourites</a></li><?php endif; ?>
          <?php if ($this->channel->numgroups > 0) : ?><li class="<?php echo ($this->layout == 'groups' ? 'active ' : false); ?>media-tabmenu-groups"><a href="<?php echo JRoute::_('index.php?option=com_hwdmediashare&view=channel&id=' . $this->channel->id . '&layout=groups&display='.$this->display); ?>"><?php echo (int) $this->channel->numgroups; ?> Groups</a></li><?php endif; ?>
          <?php if ($this->channel->numplaylists > 0) : ?><li class="<?php echo ($this->layout == 'playlists' ? 'active ' : false); ?>media-tabmenu-playlists"><a href="<?php echo JRoute::_('index.php?option=com_hwdmediashare&view=channel&id=' . $this->channel->id . '&layout=playlists&display='.$this->display); ?>"><?php echo (int) $this->channel->numplaylists; ?> Playlists</a></li><?php endif; ?>
          <?php if ($this->channel->numalbums > 0) : ?><li class="<?php echo ($this->layout == 'albums' ? 'active ' : false); ?>media-tabmenu-albums"><a href="<?php echo JRoute::_('index.php?option=com_hwdmediashare&view=channel&id=' . $this->channel->id . '&layout=albums&display='.$this->display); ?>"><?php echo (int) $this->channel->numalbums; ?> Albums</a></li><?php endif; ?>
          <?php if ($this->channel->numsubscribers > 0) : ?><li class="<?php echo ($this->layout == 'subscribers' ? 'active ' : false); ?>media-tabmenu-subscribers"><a href="<?php echo JRoute::_('index.php?option=com_hwdmediashare&view=channel&id=' . $this->channel->id . '&layout=subscribers&display='.$this->display); ?>"><?php echo (int) $this->channel->numsubscribers; ?> Subscribers</a></li><?php endif; ?>
        </ul>
      </div>
      <div class="clear"></div>
      <!-- Search Filters -->
      <?php echo JLayoutHelper::render('search_tools', array('view' => $this), JPATH_ROOT.'/components/com_hwdmediashare/libraries/layouts'); ?>
      <div class="clear"></div>       
    </div>
    <div class="row-fluid">
      <!-- Main Column -->  
      <div class="span<?php echo $this->params->get('item_meta_activities') != '0' ? 8 : 12; ?>">
        <?php if (count($this->items)) : ?>   
          <div class="media-details-view">
            <?php echo JLayoutHelper::render($this->layout . '_details', $this, JPATH_ROOT.'/components/com_hwdmediashare/libraries/layouts'); ?>
          </div> 
          <!-- Pagination -->
          <div class="pagination"> <?php echo $this->pagination->getPagesLinks(); ?> </div>
          <div class="clear"></div>
        <?php endif; ?>
      </div>
      <!-- Side Column -->        
      <?php if ($this->params->get('item_meta_activities') != '0'): ?>   
      <div class="span4">
        <?php if (count($this->activities)) : ?> 
          <ul class="media-activity-list">
            <?php foreach ($this->activities as $activity) : ?>
              <li class="media-activity-item">
                <?php if ($this->params->get('community_avatar') != 'none') :?>
                  <a href="<?php echo JRoute::_(hwdMediaShareHelperRoute::getUserRoute($activity->actor)); ?>" class="media-activity-avatar">
                    <?php echo JHtml::_('hwdimage.avatar', $activity->actor, 50); ?>
                  </a>
                <?php endif; ?>                  
                <p class="media-activity-desc"><?php echo hwdMediaShareActivities::renderActivityHtml($activity); ?></p>
                <p class="media-activity-date"><?php echo JHtml::_('hwddate.relative', $activity->created); ?></p>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>         
      </div>        
      <?php endif; ?>
    </div>
    <!-- Description -->
    <div class="well media-channel-description">
      <?php if ($this->params->get('item_meta_description') != '0') :?>
        <?php echo JHtml::_('content.prepare', $this->channel->description); ?>
      <?php endif; ?> 
      <?php if ($this->params->get('item_meta_likes') != '0' || $this->params->get('item_meta_dislikes') != '0') :?>
      <div class="pull-right">
        <div class="media-info-likes pull-right">      
          <?php if ($this->params->get('item_meta_likes') != '0') :?><i class="icon-thumbs-up"></i> <span id="media-likes"><?php echo (int) $this->channel->likes; ?></span><?php endif; ?>
          <?php if ($this->params->get('item_meta_dislikes') != '0') :?><i class="icon-thumbs-down"></i> <span id="media-dislikes"><?php echo (int) $this->channel->dislikes; ?></span><?php endif; ?>
        </div>
      </div>       
      <?php endif; ?>        
      <?php if ($this->params->get('item_meta_media_count') != '0' || $this->params->get('item_meta_author') != '0' || $this->params->get('item_meta_created') != '0' || $this->params->get('item_meta_hits') != '0') :?>
      <dl class="media-info">
        <dt class="media-info-term"><?php echo JText::_('COM_HWDMS_DETAILS'); ?> </dt>      
        <?php if ($this->params->get('item_meta_created') != '0') : ?>
        <dd class="media-info-meta">
          <span class="media-info-created">
            <?php echo JHtml::_('hwddate.relative', $this->channel->created); ?>
          </span>
        </dd>
        <?php endif; ?>
        <?php if ($this->params->get('item_meta_hits') != '0') :?>
          <dd class="media-info-hits label"><?php echo JText::sprintf('COM_HWDMS_X_VIEWS', number_format((int) $this->channel->hits)); ?></dd>
        <?php endif; ?>      
        <?php if ($this->params->get('item_meta_media_count') != '0') :?>
          <dd class="media-info-count label"><?php echo JText::plural('COM_HWDMS_X_MEDIA_COUNT', (int) $this->channel->nummedia); ?></dd>
        <?php endif; ?>        
        <div class="clearfix"></div>         
      </dl>
      <?php endif; ?>  
      <?php if ($this->params->get('item_meta_likes') != '0' || $this->params->get('item_meta_dislikes') != '0' || $this->params->get('item_meta_report') != '0') : ?>
        <div class="btn-group">
          <?php if ($this->params->get('item_meta_likes') != '0') : ?>
            <a title="<?php echo JText::_('COM_HWDMS_LIKE'); ?>" href="<?php echo JRoute::_('index.php?option=com_hwdmediashare&task=channels.like&id=' . $this->channel->id . '&return=' . $this->return . '&tmpl=component&'.JSession::getFormToken().'=1'); ?>" class="btn btn-mini" id="album-like-btn" data-media-task="like" data-media-id="<?php echo $this->channel->id; ?>" data-media-return="<?php echo $this->return; ?>" data-media-token="<?php echo JSession::getFormToken(); ?>"><i class="icon-thumbs-up"></i> <?php echo JText::_('COM_HWDMS_LIKE'); ?></a>
          <?php endif; ?>
          <?php if ($this->params->get('item_meta_dislikes') != '0') : ?>
            <a title="<?php echo JText::_('COM_HWDMS_DISLIKE'); ?>" href="<?php echo JRoute::_('index.php?option=com_hwdmediashare&task=channels.dislike&id=' . $this->channel->id . '&return=' . $this->return . '&tmpl=component&'.JSession::getFormToken().'=1'); ?>" class="btn btn-mini" id="album-dislike-btn" data-media-task="dislike" data-media-id="<?php echo $this->channel->id; ?>" data-media-return="<?php echo $this->return; ?>" data-media-token="<?php echo JSession::getFormToken(); ?>"><i class="icon-thumbs-down"></i> <?php echo JText::_('COM_HWDMS_DISLIKE'); ?></a>
          <?php endif; ?>
          <?php if ($this->params->get('item_meta_report') != '0') : ?>
            <a title="<?php echo JText::_('COM_HWDMS_REPORT'); ?>" href="<?php echo JRoute::_('index.php?option=com_hwdmediashare&task=channelform.report&id=' . $this->channel->id . '&return=' . $this->return . '&tmpl=component'); ?>" class="btn btn-mini media-popup-iframe-form" id="album-report-btn"><i class="icon-flag"></i> <?php echo JText::_('COM_HWDMS_REPORT'); ?></a>
          <?php endif; ?>  
        </div>          
      <?php endif; ?>
      <!-- Tags -->
      <?php if ($this->params->get('show_tags', 1) && !empty($this->channel->tags)) : ?>
	<?php $this->channel->tagLayout = new JLayoutFile('joomla.content.tags'); ?>
	<?php echo $this->channel->tagLayout->render($this->channel->tags->itemTags); ?>
      <?php endif; ?>
      <!-- Custom fields -->
      <dl class="media-info">
        <?php foreach($this->channel->customfields->fields as $group => $fields) : ?>
          <dt class="media-info-group"><?php echo JText::_($group); ?> </dt>
          <?php foreach($fields as $field) : ?>
            <dd class="media-info-field-<?php echo $field->fieldcode; ?>"><?php echo $field->name; ?>: <?php echo $this->channel->customfields->display($field); ?></dd>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </dl>
      <div class="clear"></div>       
    </div>
  </div>
</form>

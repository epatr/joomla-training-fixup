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
?>
<?php if ($this->params->get('mediaitem_navigation') != '0' && $this->item->navigation->hasNav) : ?>  
<div class="media-item-navigation row-fluid">
  <div class="span4 navigation-prev">
    <?php if (isset($this->item->navigation->prev->id)): ?>
      <h3>
        <a title="<?php echo JText::_('JPREV'); ?>" href="<?php echo JRoute::_(hwdMediaShareHelperRoute::getMediaItemRoute($this->item->navigation->prev->id . ':' . $this->item->navigation->prev->alias)); ?>" class="navigation-prev-title">
          <?php echo $this->item->navigation->prev->title; ?>            
        </a> 
      </h3>
    <?php endif; ?>
  </div>  
  <?php if ($this->item->navigation->category) : ?>  
    <div class="span4 navigation-category">
      <h3>
        <a href="<?php echo JRoute::_(hwdMediaShareHelperRoute::getCategoryRoute($this->item->navigation->category->id . ':' . $this->item->navigation->category->alias)); ?>">
          <?php echo $this->item->navigation->category->title; ?>
        </a>
      </h3>
      <p>
        <?php echo JText::sprintf('COM_HWDMS_CATEGORY_X_HAS_X_MEDIA', '<a href="'.JRoute::_(hwdMediaShareHelperRoute::getCategoryRoute($this->item->navigation->category->id . ':' . $this->item->navigation->category->alias)).'">'.JText::_('COM_HWDMS_CATEGORY').'</a>', $this->item->navigation->category->numitems); ?>
      </p>
    </div>  
  <?php elseif ($this->item->navigation->playlist) : ?>      
    <div class="span4 navigation-playlist">
      <h3>
        <a href="<?php echo JRoute::_(hwdMediaShareHelperRoute::getPlaylistRoute($this->item->navigation->playlist->id . ':' . $this->item->navigation->playlist->alias)); ?>">
          <?php echo $this->item->navigation->playlist->title; ?>
        </a>
      </h3>
      <p>
        <?php echo JText::sprintf('COM_HWDMS_PLAYLIST_X_BY_USER_X', '<a href="'.JRoute::_(hwdMediaShareHelperRoute::getPlaylistRoute($this->item->navigation->playlist->id . ':' . $this->item->navigation->playlist->alias)).'">'.JText::_('COM_HWDMS_PLAYLIST').'</a>', '<a href="'.JRoute::_(hwdMediaShareHelperRoute::getUserRoute($this->item->navigation->playlist->created_user_id)).'">'.htmlspecialchars($this->item->navigation->playlist->author, ENT_COMPAT, 'UTF-8').'</a>'); ?>
      </p>
    </div>  
  <?php elseif ($this->item->navigation->album) : ?>  
    <div class="span4 navigation-album">
      <h3>
        <a href="<?php echo JRoute::_(hwdMediaShareHelperRoute::getAlbumRoute($this->item->navigation->album->id . ':' . $this->item->navigation->album->alias)); ?>">
          <?php echo $this->item->navigation->album->title; ?>
        </a>
      </h3>
      <p>
        <?php echo JText::sprintf('COM_HWDMS_ALBUM_X_BY_USER_X', '<a href="'.JRoute::_(hwdMediaShareHelperRoute::getAlbumRoute($this->item->navigation->album->id . ':' . $this->item->navigation->album->alias)).'">'.JText::_('COM_HWDMS_ALBUM').'</a>', '<a href="'.JRoute::_(hwdMediaShareHelperRoute::getUserRoute($this->item->navigation->album->created_user_id)).'">'.htmlspecialchars($this->item->navigation->album->author, ENT_COMPAT, 'UTF-8').'</a>'); ?>
      </p>
    </div>  
  <?php elseif ($this->item->navigation->group) : ?>  
    <div class="span4 navigation-group">
      <h3>
        <a href="<?php echo JRoute::_(hwdMediaShareHelperRoute::getGroupRoute($this->item->navigation->group->id . ':' . $this->item->navigation->group->alias)); ?>">
          <?php echo $this->item->navigation->group->title; ?>
        </a>
      </h3>
      <p>
        <?php echo JText::sprintf('COM_HWDMS_GROUP_X_BY_USER_X', '<a href="'.JRoute::_(hwdMediaShareHelperRoute::getGroupRoute($this->item->navigation->group->id . ':' . $this->item->navigation->group->alias)).'">'.JText::_('COM_HWDMS_GROUP').'</a>', '<a href="'.JRoute::_(hwdMediaShareHelperRoute::getUserRoute($this->item->navigation->group->created_user_id)).'">'.htmlspecialchars($this->item->navigation->group->author, ENT_COMPAT, 'UTF-8').'</a>'); ?>
      </p>
    </div>  
  <?php endif; ?>
  <div class="span4 navigation-next">
    <?php if (isset($this->item->navigation->next->id)): ?>
      <h3>
        <a title="<?php echo JText::_('JNEXT'); ?>" href="<?php echo JRoute::_(hwdMediaShareHelperRoute::getMediaItemRoute($this->item->navigation->next->id . ':' . $this->item->navigation->next->alias)); ?>" class="navigation-next-title">
          <?php echo $this->item->navigation->next->title; ?>       
        </a>
      </h3>      
    <?php endif; ?>
  </div> 
  <?php if (isset($this->item->navigation->prev->id)): ?>
    <a title="<?php echo JText::_('JPREV'); ?>" href="<?php echo JRoute::_(hwdMediaShareHelperRoute::getMediaItemRoute($this->item->navigation->prev->id . ':' . $this->item->navigation->prev->alias)); ?>" class="navigation-btn prev">
      <i class="icon-arrow-left"></i>
    </a>
  <?php endif; ?>    
  <?php if (isset($this->item->navigation->next->id)): ?>
    <a title="<?php echo JText::_('JNEXT'); ?>" href="<?php echo JRoute::_(hwdMediaShareHelperRoute::getMediaItemRoute($this->item->navigation->next->id . ':' . $this->item->navigation->next->alias)); ?>" class="navigation-btn next">
      <i class="icon-arrow-right"></i>
    </a>          
  <?php endif; ?>    
</div>
<?php endif; ?>
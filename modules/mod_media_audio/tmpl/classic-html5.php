<?php
/**
 * @package     Joomla.site
 * @subpackage  Module.mod_media_audio
 *
 * @copyright   Copyright (C) 2013 Highwood Design Limited. All rights reserved.
 * @license     GNU General Public License http://www.gnu.org/copyleft/gpl.html
 * @author      Dave Horsfall
 */

defined('_JEXEC') or die;

$app = JFactory::getApplication();
$user = JFactory::getUser();
$doc = JFactory::getDocument();
$menu = $app->getMenu();

// Filter out playable MP3 audio
foreach ($helper->items as $id => $item)
{
        // We need to obtain the MP3 for this audio track
        if ($item->type == 1)
        {
                hwdMediaShareFactory::load('audio');
                $mp3 = hwdMediaShareAudio::getMp3($item);
                if ($mp3) $item->mp3 = $mp3->url;
        }
        elseif ($item->type == 7)
        {
                if (isset($item->source)) $item->mp3 = $item->source;
        }

        if (!isset($item->mp3))
        {
                print_r($item);
                exit;
                continue;
        }
        
        if (!isset($leading))
        {
                $leading = $item->mp3;
        }
}

$doc->addScriptDeclaration("
jQuery(document).ready(function(){
    var audio;
    var playlist;
    var tracks;
    var current;

    init();
    function init(){
        current = 0;
        audio = jQuery('audio');
        playlist = jQuery('#media-audio-playlist');
        tracks = playlist.find('a.media-track');
        len = tracks.length - 1;
        audio[0].volume = .10;
        audio[0].play();
        playlist.find('a').click(function(e){
            e.preventDefault();
            link = jQuery(this);
            current = link.parent().index();
            run(link, audio[0]);
        });
        audio[0].addEventListener('ended',function(e){
            current++;
            if(current == len){
                current = 0;
                link = playlist.find('a')[0];
            }else{
                link = playlist.find('a')[current];    
            }
            run(jQuery(link),audio[0]);
        });
    }
    function run(link, player){
            player.src = link.attr('data-mp3');
            link.addClass('active').siblings().removeClass('active');
            audio[0].load();
            audio[0].play();
    }
});
");  
//http://jsfiddle.net/WsXX3/33/
?>
<div class="hwd-container">
  <div class="media-audio-view">
    <?php if (empty($helper->items)): ?>
      <div class="alert alert-no-items">
        <?php echo JText::_('COM_HWDMS_NOTHING_TO_SHOW'); ?>
      </div>
    <?php else: ?>
      <audio id="audio" preload="auto" tabindex="0" controls="" type="audio/mpeg">
        <source type="audio/mp3" src="<?php echo $leading ? $leading : null; ?>">
        Sorry, your browser does not support HTML5 audio.
      </audio> 
      <div id="media-audio-playlist">      
        <?php foreach ($helper->items as $id => $item) :
        $item->slug = $item->alias ? ($item->id . ':' . $item->alias) : $item->id;
        $canEdit = ($user->authorise('core.edit', 'com_hwdmediashare.media.'.$item->id) || ($user->authorise('core.edit.own', 'com_hwdmediashare.media.'.$item->id) && ($item->created_user_id == $user->id)));
        $canEditState = $user->authorise('core.edit.state', 'com_hwdmediashare.media.'.$item->id);
        $canDelete = ($user->authorise('core.delete', 'com_hwdmediashare.media.'.$item->id) || ($user->authorise('core.edit.own', 'com_hwdmediashare.media.'.$item->id) && ($item->created_user_id == $user->id))); ?>
        <?php if (isset($item->mp3)) : ?>
        <a class="media-track" href="<?php echo JRoute::_(hwdMediaShareHelperRoute::getMediaItemRoute($item->slug, array(), false)); ?>" data-mp3="<?php echo $item->mp3; ?>">
          <div class="hasTooltip" title="<?php echo ($helper->params->get('list_tooltip_location') > '1' ? JHtml::tooltipText($item->title, ($helper->params->get('list_tooltip_contents') != '0' ? JHtml::_('string.truncate', $item->description, $helper->params->get('list_desc_truncate'), false, false) : '')) : $item->title); ?>">
            <div class="pull-right">
              <?php echo hwdMediaShareMedia::secondsToTime($item->duration); ?>                    
            </div>              
            <span><i class="icon-play"></i><?php echo $item->title; ?></span>  
          </div>                     
        </a>
        <?php endif; ?>
        <?php endforeach; ?>
      </div>        
    <?php endif; ?>
  </div> 
  <?php if ($params->get('show_more_link') != 'hide') :?><p><a href="<?php echo ((intval($params->get('show_more_link')) > 0) ? JRoute::_($menu->getItem($params->get('show_more_link'))->link.'&Itemid='.$params->get('show_more_link')) : JRoute::_(hwdMediaShareHelperRoute::getMediaRoute())); ?>" class="btn"><?php echo JText::_($params->get('more_link_text', 'MOD_MEDIA_AUDIO_VIEW_MORE')); ?></a></p><?php endif; ?>  
</div>

<?php
/**
 * ------------------------------------------------------------------------
 * JA Company Template
 * ------------------------------------------------------------------------
 * Copyright (C) 2004-2018 J.O.O.M Solutions Co., Ltd. All Rights Reserved.
 * @license - Copyrighted Commercial Software
 * Author: J.O.O.M Solutions Co., Ltd
 * Websites:  http://www.joomlart.com -  http://www.joomlancers.com
 * This file may not be redistributed in whole or significant part.
 * ------------------------------------------------------------------------
*/
defined('_JEXEC') or die;

$count = $helper->getRows('data.title');
?>

<div id="acm-slideshow-<?php echo $module->id; ?>" class="slideshow">
  <div class="swiper-container slideshow-top">
    <div class="swiper-wrapper">
      <?php for ($i=0; $i<$count; $i++) : ?>
      <div class="swiper-slide">

        <div class="item <?php if($i<1) echo "active"; ?>">
          <?php if($helper->get('data.image', $i)): ?>
            <div class="img-bg <?php if($helper->get('mask')) echo "has-mask" ;?>">
            <img alt="<?php echo $helper->get('data.title', $i) ?>" src="<?php echo $helper->get('data.image', $i); ?>" />
            </div>
          <?php endif; ?>
        
          <div class="slider-content container">
            <div class="slider-content-inner" <?php if($helper->get('data.animation', $i)): ?>data-type-animation="<?php echo $helper->get('data.animation', $i) ?>"<?php endif;?>>
              <?php if($helper->get('data.group', $i)): ?>
                <div class="hidden-xs item-group label-small ja-animation" data-delay-transtion="1">
                   <?php echo $helper->get('data.group', $i) ?>
                </div>
              <?php endif; ?>

              <?php if($helper->get('data.title', $i)): ?>
                <h1 class="item-title ja-animation" data-delay-transtion="2">
                   <?php echo $helper->get('data.title', $i) ?>
                </h1>
              <?php endif; ?>

              <?php if($helper->get('data.desc', $i)): ?>
                <p class="hidden-xs item-desc ja-animation" data-delay-transtion="3"><?php echo $helper->get('data.desc', $i) ?></p>
              <?php endif; ?>

              <div class="group-action ja-animation" data-delay-transtion="4">
                <?php if($helper->get('data.button1', $i)): ?>
                  <a href="<?php echo $helper->get('data.btn-link1', $i); ?>" class="btn btn-primary btn-lg" data-delay-transtion="4"><?php echo $helper->get('data.button1', $i) ?></a>
                <?php endif; ?>

                <?php if($helper->get('data.button2', $i)): ?>
                  <a href="<?php echo $helper->get('data.btn-link2', $i); ?>" class="btn btn-transparent btn-lg"><?php echo $helper->get('data.button2', $i) ?></a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

      </div>
      <?php endfor ;?>
    </div>
  </div>

  <div class="swiper-thumbs-wraper">
    <div class="container">
      <div class="swiper-container slideshow-thumbs hidden-xs hidden-sm hidden-md">
        <div class="swiper-wrapper">
          <!-- Wrapper for slides -->
          <?php for ($i=0; $i<$count; $i++) : ?>
            <div class="swiper-slide">
                <div class="swiper-thumbs">
                  <?php if($helper->get('data.group', $i)): ?>
                    <span class="item-title label-small">
                       <?php echo $helper->get('data.group', $i) ?>
                    </span>
                  <?php endif; ?>

                  <div class="item-image">
                  <img alt="<?php echo $helper->get('data.title', $i) ?>" src="<?php echo $helper->get('data.image', $i); ?>" />
                  </div>
                </div>
            </div>
          <?php endfor ;?>
          </div>
      </div>

      <!-- Add Arrows -->
      <div class="swiper-button-next swiper-button-white"><span class="fa fa-angle-right" aria-hidden="true"></span></div>
      <div class="swiper-button-prev swiper-button-white"><span class="fa fa-angle-left" aria-hidden="true"></span></div>
    </div>

    <div class="swiper-pagination"></div>
  </div>

  <!-- VIDEO PLAY -->
  <?php if($helper->get('image-video')):?>
  <div class="video-action">
    <div class="videoWrapper">
      <div id="player"></div>
    </div>
  </div>
  <?php endif;?>
</div>

<?php if($helper->get('image-video')):?>
  
<div id="video-intro-<?php echo $module->id; ?>" class="video-intro">
  <div class="container">
    <h3><?php echo $helper->get('title-action');?></h3>
    <a id="resume" href="javascript:void();">
      <img src="<?php echo $helper->get('image-video');?>" alt="<?php echo $helper->get('title-action');?>" />
      <span class="fa fa-play" aria-hidden="true"></span>
    </a>

    <a id="pause"  class="hidden" href="javascript:void();">
      <img src="<?php echo $helper->get('image-video');?>" alt="pause" />
      <span class="fa fa-pause" aria-hidden="true"></span>
    </a>
  </div>  
</div>
<?php endif;?>


<script>
jQuery(document).ready(function(){
  var galleryTop = new Swiper('.slideshow-top', {
      nextButton: '.swiper-button-next',
      prevButton: '.swiper-button-prev',
      spaceBetween: 0,
      effect: 'fade',
      pagination: '.swiper-pagination',
      loop: true,
      paginationClickable: true,
      loopedSlides: 3, //looped slides should be the same
      <?php if($helper->get('auto-play')) echo "autoplay: '3500'," ;?>
  });
  var galleryThumbs = new Swiper('.slideshow-thumbs', {
      spaceBetween: 30,
      slidesPerView: 3,
      touchRatio: 0.2,
      loop: true,
      loopedSlides: 3, //looped slides should be the same
      slideToClickedSlide: true,
  });

  galleryTop.params.control = galleryThumbs;
  galleryThumbs.params.control = galleryTop;
});
</script>

<?php if($helper->get('image-video')):?>
<script>
  // This code loads the IFrame Player API code asynchronously.
  var tag = document.createElement('script');
  tag.src = "https://www.youtube.com/player_api";
  var firstScriptTag = document.getElementsByTagName('script')[0];
  firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

  // This function creates an <iframe> (and YouTube player)
  // after the API code downloads.
  var player;
  function onYouTubePlayerAPIReady() {
    player = new YT.Player('player', {
        height: '315',
        width: '560',
        videoId: '<?php echo $helper->get('link-video');?>',
        playerVars: {
            'showinfo': 0
        },
        events: {
          'onStateChange': onPlayerStateChange
        }
    });

    document.getElementById('resume').onclick = function() {
        player.playVideo();
        jQuery('#acm-slideshow-<?php echo $module->id; ?>').addClass('play');
        jQuery(this).addClass('hidden');
        jQuery('#video-intro-<?php echo $module->id; ?> #pause').removeClass('hidden');
    };
    document.getElementById('pause').onclick = function() {
        player.pauseVideo();
        jQuery('#acm-slideshow-<?php echo $module->id; ?>').removeClass('play');
        jQuery(this).addClass('hidden');
        jQuery('#video-intro-<?php echo $module->id; ?> #resume').removeClass('hidden');
    };
  }

  // when video ends
  function onPlayerStateChange(event) {        
      if(event.data === 0) {          
        jQuery('#acm-slideshow-<?php echo $module->id; ?>').removeClass('play');
        jQuery('#pause').addClass('hidden');
        jQuery('#video-intro-<?php echo $module->id; ?> #resume').removeClass('hidden');
      }
  }
</script>
<?php endif;?>
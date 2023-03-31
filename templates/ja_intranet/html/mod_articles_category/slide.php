<?php
/**
 * ------------------------------------------------------------------------
 * JA Intranet Template
 * ------------------------------------------------------------------------
 * Copyright (C) 2004-2018 J.O.O.M Solutions Co., Ltd. All Rights Reserved.
 * @license - Copyrighted Commercial Software
 * Author: J.O.O.M Solutions Co., Ltd
 * Websites:  http://www.joomlart.com -  http://www.joomlancers.com
 * This file may not be redistributed in whole or significant part.
 * ------------------------------------------------------------------------
*/

defined('_JEXEC') or die;

?>
<div class="category-module article-slide slider-for">

<?php foreach ($list as $item) : ?>
	<div class="item item-intro col">	

		<article class="image"> 
			<?php echo JLayoutHelper::render('joomla.content.intro_image', $item); ?>
      
			<div class="item-content">
        <?php if ($item->displayCategoryTitle) : ?>
        <span class="category-name">
          <?php echo $item->displayCategoryTitle; ?>              
        </span>     
        <?php endif; ?>
        
				<header class="article-header clearfix">
					<h3 class="article-title">
						<?php if ($params->get('link_titles') == 1) : ?>
						<a title="<?php echo $item->title; ?>"  href="<?php echo $item->link; ?>">
						<?php endif; ?>
							<?php echo $item->title; ?>
						<?php if ($params->get('link_titles') == 1) : ?>
						</a>
						<?php endif; ?>
					</h3>
				</header>
	
				<?php if ($params->get('show_introtext')) : ?>
					<section class="article-intro clearfix">
						<?php echo $item->displayIntrotext; ?>
					</section>
				<?php endif; ?>

				<?php if ($params->get('show_readmore')) : ?>
					<p class="mod-articles-category-readmore">
						<a class="mod-articles-category-title <?php echo $item->active; ?>" href="<?php echo $item->link; ?>">
							<?php if ($item->params->get('access-view') == false) : ?>
								<?php echo JText::_('MOD_ARTICLES_CATEGORY_REGISTER_TO_READ_MORE'); ?>
							<?php elseif ($readmore = $item->alternative_readmore) : ?>
								<?php echo $readmore; ?>
								<?php echo JHtml::_('string.truncate', $item->title, $params->get('readmore_limit')); ?>
							<?php elseif ($params->get('show_readmore_title', 0) == 0) : ?>
								<?php echo JText::sprintf('MOD_ARTICLES_CATEGORY_READ_MORE_TITLE'); ?>
							<?php else : ?>
								<?php echo JText::_('MOD_ARTICLES_CATEGORY_READ_MORE'); ?>
								<?php echo JHtml::_('string.truncate', $item->title, $params->get('readmore_limit')); ?>
							<?php endif; ?>
						</a>
					</p>
				<?php endif; ?>

				<aside class="article-aside clearfix">
          <?php if ($item->displayHits || $item->displayDate || $params->get('show_author')) : ?>
          <dl class="article-info  muted">
          	<dt class="article-info-term"></dt>

          	<?php if ($item->displayHits) : ?>
						<dd class="mod-articles-category-hits">
							<?php echo $item->displayHits; ?>
						</dd>
						<?php endif; ?>
      
						<?php if ($item->displayDate) : ?>
						<dd title="" class="published hasTooltip" data-original-title="Published: ">
							<i class="fa fa-clock-o"></i><time> <?php echo JATemplateHelper::relTime($item->displayDate); ?></time>
						</dd>
						<?php endif; ?>

						<?php if ($params->get('show_author')) : ?>
						<dd title="" class="author hasTooltip" data-original-title="Author: ">
							<?php echo $item->displayAuthorName; ?>
						</dd>
						<?php endif; ?>
					</dl>
          <?php endif; ?>
       </aside>
			</div>
		</article>
	</div>
<?php endforeach; ?>
</div>

<div class="article-slide-thumbs slider-nav">
<?php foreach ($list as $item) : ?>
  <div class="thumb">
  <?php echo JLayoutHelper::render('joomla.content.intro_image', $item); ?>
  </div>
<?php endforeach; ?>
</div>

<script>
(function($) {
    var $slidefor = $('html[dir="ltr"] .slider-for');
    $slidefor.slick({
      slidesToShow: 1,
      slidesToScroll: 1,
      arrows: false,
      fade: true,
      asNavFor: '.slider-nav'
    });
	var $slidenav = $('html[dir="ltr"] .slider-nav');
    $slidenav.slick({
      slidesToShow: 7,
      slidesToScroll: 1,
      asNavFor: '.slider-for',
      dots: false,
      centerMode: true,
      focusOnSelect: true,
      variableWidth: false
    });
	var $rslidefor = $('html[dir="rtl"] .slider-for');
    $rslidefor.slick({
      slidesToShow: 1,
      slidesToScroll: 1,
      arrows: false,
      fade: true,
      asNavFor: '.slider-nav',
      rtl: true
    });
	var $rslidenav = $('html[dir="rtl"] .slider-nav');
    $rslidenav.slick({
      slidesToShow: 7,
      slidesToScroll: 1,
      asNavFor: '.slider-for',
      dots: false,
      centerMode: true,
      focusOnSelect: true,
      variableWidth: false,
      rtl: true
    });
	
	$(window).on('resize orientationChange', function(event) { 
		setTimeout(function(){
			$slidefor.slick('reinit'); 
//  			$slidenav.slick('reinit'); 
			$rslidefor.slick('reinit'); 
//  			$rslidenav.slick('reinit'); 
		}, 1000);
	});
  $(window).resize(function() {
    $('.slider-for').slick('resize');
    $('.slider-nav').slick('resize');
  });

  $(window).on('orientationchange', function() {
    $('.slider-for').slick('resize');
    $('.slider-nav').slick('resize');
  });

})(jQuery);
</script>


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
	$modTitle       = $module->title;
	$testimonialTitle 	= $helper->get('block-title');
	$testimonialIntro 	= $helper->get('block-intro');
	$count 					= $helper->getRows('data.title');
	$column 				= $helper->get('columns');
?>

<div class="acm-testimonial style-1">
	<div class="container">
		<div class="testimonial-item">
			<div class="testimonial-intro">
				<?php if($modTitle) :?>
					<div class="label-small">
						<?php echo $modTitle;?>
					</div>
				<?php endif;?>
				<?php if($testimonialTitle): ?>
				<h2 class="acm-testimonial-title">
					<?php echo $testimonialTitle; ?>
				</h2>
				<?php endif; ?>
			</div>

			<div class="testimonial-content">
				<div id="acm-testimonial-<?php echo $module->id; ?>">
					<div class="owl-carousel owl-theme">
						<?php 
							for ($i=0; $i<$count; $i++) : 
						?>
							<div class="testimonial-item col">
								<div class="testimonial-item-inner owl-height">
									<?php if($helper->get('data.img', $i)) : ?>
										<div class="testimonial-img">
											<img src="<?php echo $helper->get('data.img', $i) ?>" alt="" />

											<?php if($helper->get('data.label', $i)) : ?>
												<div class="label-highlight"><?php echo $helper->get('data.label', $i) ?></div>
											<?php endif ; ?>
										</div>
									<?php endif ; ?>
									
									<?php if($helper->get('data.title', $i)) : ?>
										<h3><?php echo $helper->get('data.title', $i) ?></h3>
									<?php endif ; ?>
								</div>
							</div>
						<?php endfor ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
(function($){
  jQuery(document).ready(function($) {
    $("#acm-testimonial-<?php echo $module->id; ?> .owl-carousel").owlCarousel({
      addClassActive: true,
      items: <?php echo $column; ?>,
      itemsDesktop : [1199, 2],
      itemsDesktopSmall : [979, 2],
      itemsTablet : [768, 2],
      itemsTabletSmall : false,
      itemsMobile : [479, 1],
      itemsScaleUp : true,
      navigation : true,
      navigationText : ["<span class='fa fa-angle-left'></span>", "<span class='fa fa-angle-right'></span>"],
      pagination: true,
      paginationNumbers : false,
      autoPlay: false
    });
  });
})(jQuery);
</script>
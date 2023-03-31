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
	$featuresTitle 	= $helper->get('block-title');
	$featuresIntro 	= $helper->get('block-intro');
	$count 					= $helper->getRows('data.title');
	$column 				= $helper->get('columns');
?>

<div class="acm-features style-1">
	<div class="container">
		<div class="row">
			<div class="col-md-4 col-lg-3">
				<div class="features-intro">
					<?php if($modTitle) :?>
						<div class="label-small">
							<?php echo $modTitle;?>
						</div>
					<?php endif;?>
					<?php if($featuresIntro): ?>
					<h2 class="acm-features-title">
						<?php echo $featuresTitle; ?>
					</h2>
					<?php endif; ?>

					<?php if($featuresIntro): ?>
					<p class="acm-features-intro">
						<?php echo $featuresIntro; ?>
					</p>
					<?php endif; ?>
				</div>
			</div>

			<div class="col-md-8 col-lg-9">
				<div id="acm-feature-<?php echo $module->id; ?>" class="acm-feature-slide">
					<div class="owl-carousel owl-theme">
						<?php 
							for ($i=0; $i<$count; $i++) : 
						?>
							<div class="features-item col">
								<div class="features-item-inner">
									<?php if($helper->get('data.img', $i)) : ?>
										<div class="features-img">
											<img src="<?php echo $helper->get('data.img', $i) ?>" alt="<?php echo $helper->get('data.title', $i) ?>" />
										</div>
									<?php endif ; ?>
									
									<?php if($helper->get('data.title', $i)) : ?>
										<h3><?php echo $helper->get('data.title', $i) ?></h3>
									<?php endif ; ?>
									
									<?php if($helper->get('data.description', $i)) : ?>
										<p><?php echo $helper->get('data.description', $i) ?></p>
									<?php endif ; ?>

									<?php if($helper->get('data.link', $i) && $helper->get('data.btn-value', $i)) : ?>
										<div class="feature-action">
											<a href="<?php echo $helper->get('data.link', $i) ?>"><?php echo $helper->get('data.btn-value', $i) ?></a>
											</div>
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
    $("#acm-feature-<?php echo $module->id; ?> .owl-carousel").owlCarousel({
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
      pagination: false,
      paginationNumbers : false,
      autoPlay: false
    });
  });
})(jQuery);
</script>
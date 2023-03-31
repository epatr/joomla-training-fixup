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

	$modTitle   = $module->title;
	$count		= $helper->getRows('data.content');
?>

<div id="acm-testimonial-<?php echo $module->id; ?>" class="acm-testimonial style-3">
	<div class="owl-carousel owl-theme">
		<?php 
			for ($i=0; $i<$count; $i++) : 
		?>
			<div class="testimonial-item col">
				<div class="testimonial-item-inner">
					<?php if($helper->get('data.content', $i)) : ?>
						<div class="testimonial-content"><?php echo $helper->get('data.content', $i) ?></div>
					<?php endif ; ?>

					<?php if($helper->get('data.name', $i)) : ?>
						<div class="testimonial-info">
							<?php if($helper->get('data.img', $i)) : ?>
								<div class="testimonial-img">
									<img src="<?php echo $helper->get('data.img', $i) ?>" alt="<?php echo $helper->get('data.name', $i) ?>" />
								</div>
							<?php endif ; ?>

							<div class="testimonial-detail">
								<?php if($helper->get('data.name', $i)) : ?>
									<div class="h4"><?php echo $helper->get('data.name', $i) ?></div>
								<?php endif ; ?>
								<?php if($helper->get('data.name-position', $i)) : ?>
									<div class="h5"><?php echo $helper->get('data.name-position', $i) ?></div>
								<?php endif ; ?>
							</div>
						</div>
					<?php endif ; ?>
				</div>
			</div>
		<?php endfor ?>
	</div>
</div>


<script>
(function($){
  jQuery(document).ready(function($) {
    $("#acm-testimonial-<?php echo $module->id; ?> .owl-carousel").owlCarousel({
      addClassActive: true,
      items: 1,
      itemsDesktop : [1199, 1],
      itemsDesktopSmall : [979, 1],
      itemsTablet : [768, 1],
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
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
?>

<?php
  $ctaImg 				= $helper->get('img');
  $ctaBackground  = 'background-image: url('.$ctaImg.');';
?>
<div class="acm-testimonial style-2 <?php if($ctaImg): echo 'bg-image'; endif; ?>" <?php if($ctaImg): echo 'style="'.$ctaBackground.'"'; endif; ?> >
	<div class="container">
		<div class="row">
			<div class="<?php echo $helper->get('item-align'); ?>">
				<div class="testimonial-showcase-item">
				
					<?php if($module->showtitle): ?>
						<div class="label-small"><?php echo $module->title ?></div>
					<?php endif; ?>

					<?php if($helper->get('desc')): ?>
						<h2 class="testimonial-showcase-intro"><?php echo $helper->get('desc'); ?></h2>
					<?php endif; ?>

					<?php if($helper->get('name-author')) :?>
						<div class="author-info">
							<div class="image-author">
								<img src="<?php echo $helper->get('image-author');?>" alt="<?php echo $helper->get('name-author') ;?>" />
							</div>

							<div class="detail-author">
								<h4><?php echo $helper->get('name-author') ;?></h4>
								<h5><?php echo $helper->get('position-author') ;?></h5>
							</div>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</div>

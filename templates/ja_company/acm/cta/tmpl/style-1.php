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

$ctaImg 				= $helper->get('img');
$ctaBackground  = 'background-image: url('.$ctaImg.');';
?>

<div class="acm-cta style-1" <?php if($ctaImg) echo 'style="'.$ctaBackground.'"'; ?>>
	<div class="container">
		<div class="cta-content">
			<h2><?php echo $helper->get('data.block-intro') ?></h2>
			<?php if($helper->get('data.button') && $helper->get('data.link')): ?>
				<a href="<?php echo $helper->get('data.link') ?>" class="btn <?php if($helper->get('data.button_class')): echo $helper->get('data.button_class'); else: echo 'btn-default'; endif; ?>">
					<?php echo $helper->get('data.button') ?>
			</a>
			<?php endif; ?>
		</div>
		</div>
</div>
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

<div class="section-inner">
  <div class="acm-stats has-parallax style-1">
  	<div class="row"><div class="col-md-12 col-xs-12 <?php echo $helper->get('acm-col-offset'); ?>">
  	  
  		<?php if ($helper->get ('acm-title') || $helper->get ('acm-description')) : ?>
  		<header class="stats-header">
  	    <?php if ($helper->get ('acm-title')) : ?>
  	       <h3 class="acm-title"><?php echo $helper->get ('acm-title') ?></h3>
  	    <?php endif; ?>
  	    <?php if ($helper->get ('acm-description')) : ?>
  	        <p class="acm-description"><?php echo $helper->get ('acm-description') ?></p>
  	    <?php endif; ?>
  	  </header>
  	  <?php endif; ?>
  	
  	  <ul class="stats-list">
  	    <?php $count=$helper->getRows('data.stats-count'); ?>
  	    <?php for ($i=0; $i<$count; $i++) : ?>
  	    <?php if ($helper->get ('data.stats-count', $i)) : ?>
  	    <li class="stats-asset">
  		   	<div>
  			  
  			    <?php if ($helper->get ('data.stats-name', $i)) : ?>
  			      <span class="stats-item-subject"><?php echo $helper->get ('data.stats-name', $i) ?></span>
  			    <?php endif; ?>
  			    
  			    <span class="stats-item-counter">
  			    	<span class="progressbar" style="width: <?php echo $helper->get ('data.stats-count', $i) ?>%">
  							<span <?php if($helper->get ('data.stats-color', $i)): ?> style="background-color: <?php echo $helper->get ('data.stats-color', $i) ?>;" <?php endif; ?>></span>
  						</span>
  					</span>
  					
  		  </div>
  	    </li>
  	    <?php endif; ?>
  	  <?php endfor;?>
  	  </ul>
    </div></div>
  </div>
</div>
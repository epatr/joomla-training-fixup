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
  $count = $helper->count('member-name');
  $col = $helper->get('number_col');
?>

<div class="acm-teams">
	<div class="style-1 team-items">
		<?php
      for ($i=0; $i < $count; $i++) :
        if ($i%$col==0) echo '<div class="row">'; 
    ?>
		<div class="item col-sm-6 col-md-<?php echo (12/$col); ?>">
    
      <div class="member-image">
        <img src="<?php echo $helper->get('member-image', $i); ?>" alt="<?php echo $helper->get('member-name', $i); ?>" />
      </div>
      <h4><?php echo $helper->get('member-name', $i); ?></h4>
      <p class="member-title"><?php echo $helper->get('member-position', $i); ?></p>
      <p class="member-desc"><?php echo $helper->get('member-desc', $i); ?></p>
        
		</div>
    
    <?php if ( ($i%$col==($col-1)) || $i==($count-1) )  echo '</div>'; ?>
		<?php endfor; ?>
	</div>
</div>

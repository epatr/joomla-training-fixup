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
	if($helper->getRows('data.title') >= $helper->getRows('data.description')) {
		$count = $helper->getRows('data.title');
	} else {
		$count = $helper->getRows('data.description');
	}
  $defaultWidth = $helper->get('block-size');

// sort by date.
$array=array();
for ($i=0; $i<$count; $i++) {
	$array[$i] = $helper->get('data.date', $i);
}
arsort($array);
$j=0;
?>

<div class="acm-timeline style-1" id="acm-timeline-<?php echo $module->id; ?>">
  <div class="timeline-list">
    <?php foreach ($array AS $i => $v) :
      $icon = $helper->get('data.icon', $i);
      $rowcount = (($j%2) == 0);

      $pullClass = '';
      $alignClass = '';
      if($rowcount) {
        $pullClass = "pull-left";
        $alignClass = 'text-left';
      } else {
        $pullClass = "pull-right";
        $alignClass = 'text-right';
      }
	$j++;
    ?>
      <div class="item-row">
        <div class="timeline-item item-<?php echo $i ;?>">
          <div class="item-icon">
            <span class="fa <?php if($icon): echo $icon; else: echo 'fa-clock-o' ; endif; ?>"></span>
          </div>

          <?php if($helper->get('data.date', $i)): ?><div class="item-date"><?php echo $helper->get('data.date', $i) ?></div><?php endif; ?>
          <div class="item-content">

            <div class="item-body media <?php echo $alignClass; ?>">
              <?php if($helper->get('data.image', $i)): ?>
                <img src="<?php echo $helper->get('data.image', $i) ?>" class="media-object" alt="<?php echo $helper->get('data.title', $i) ?>">
              <?php endif; ?>
              <div class="media-body">
                <?php if($helper->get('data.title', $i)): ?><h3 class="item-title"><?php echo $helper->get('data.title', $i) ?></h3><?php endif; ?>
                <p><?php echo $helper->get('data.description', $i) ?></p>
              </div>

            </div>

          </div>
        </div>
      </div>
      <?php endforeach ;?>
  </div>
</div>





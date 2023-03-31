<?php
/*
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

/**
 * Mainbody 2 columns: content - sidebar
 */
$menu = JFactory::getApplication()->getMenu();
$active = $menu->getActive() ? $menu->getActive() : $menu->getDefault();
$params = $active->params; 
$maingrid = $params->get('maingrid',2);
$menuPosition = $this->params->get('menu_position','horizontal');
?>

<div id="t3-mainbody" class="container t3-mainbody ja-masonry-wrap isotope-layout">
  <div class="isotope grid clearfix grid-xs-1 grid-smx-2 grid-sm-2 grid-md-3 grid-lg-4 <?php if($menuPosition=="vertical") echo "grid-hd-5" ;?>">
    <div class="isotope-item grid-sizer"></div>

    <?php if ($this->countModules('alert')) : ?>
      <jdoc:include type="modules" name="<?php $this->_p('alert') ?>" style="raw"/>
    <?php endif ?>

    <?php if($this->hasMessage()) : ?>
    <div class="item-4 isotope-item">
      <jdoc:include type="message" />
    </div>
    <?php endif ?>

    <!-- MAIN CONTENT -->
    <div id="t3-content" class="t3-content item-<?php echo $maingrid; ?> isotope-item">
      
      <?php if ($this->countModules('slideshow')) : ?>
        <!-- SLIDESHOW -->
        <jdoc:include type="modules" name="<?php $this->_p('slideshow') ?>" style="T3xhtml"/>
        <!-- //SLIDESHOW -->
      <?php endif ?>

      <?php $this->loadBlock('mainbody/dashboard'); ?>
    </div>
    <!-- //MAIN CONTENT -->

    <?php if ($this->countModules('sidebar')) : ?>
    <jdoc:include type="modules" name="<?php $this->_p('sidebar') ?>" style="isotope" />
    <?php endif ?>

  </div>
</div> 
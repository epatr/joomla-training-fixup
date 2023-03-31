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

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers');
JHtml::_('behavior.caption');
?>
<div class="categories-list<?php echo $this->pageclass_sfx; ?> ">
  <!-- Load Modules Positions -->
  <?php 
  $navhelper_modules = 'navhelper';
  $attrs = array();
  $result = null;
  $renderer = JFactory::getDocument()->loadRenderer('modules');
  $modules = $renderer->render($navhelper_modules, $attrs, $result);
  ?>
  <!-- Load Modules Positions End -->

  <?php if ($this->params->get('show_page_heading', 1) || $modules) : ?>
  <div class="page-header clearfix">
    <h1 class="page-title"> <?php echo $this->escape($this->params->get('page_heading')); ?> </h1>

    <!-- Load Modules Positions -->
    <div class="hidden-xs hidden-sm"><?php echo $modules ?></div>
    <!-- Load Modules Positions End -->

  </div>
  <?php endif; ?>

  <div class="ja-masonry-wrap isotope-layout">
    <div class="isotope grid clearfix grid-xs-1 grid-smx-2 grid-sm-2 grid-md-2 grid-lg-3">
     <div class="isotope-item grid-sizer"></div>
      <?php
      echo JLayoutHelper::render('joomla.content.categories_default', $this);
      echo $this->loadTemplate('items');
      ?>
    </div>
  </div>
</div>
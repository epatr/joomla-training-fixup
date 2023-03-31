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

// get params
$sitename  = $this->params->get('sitename');
$slogan    = $this->params->get('slogan', '');
$logotype  = $this->params->get('logotype', 'text');
$logoimage = $logotype == 'image' ? $this->params->get('logoimage', T3Path::getUrl('images/logo.png', '', true)) : '';
$logoimgsm = ($logotype == 'image' && $this->params->get('enable_logoimage_sm', 0)) ? $this->params->get('logoimage_sm', T3Path::getUrl('images/logo-sm.png', '', true)) : false;
$logolink  = $this->params->get('logolink');

if (!$sitename) {
  $sitename = JFactory::getConfig()->get('sitename');
}

// get logo url
$logourl = JURI::base(true);
if ($logolink == 'page') {
  $logopageid = $this->params->get('logolink_id');
  $_item = JFactory::getApplication()->getMenu()->getItem ($logopageid);
  if ($_item) {
    $logourl = JRoute::_('index.php?Itemid=' . $logopageid);
  }
}

$menu = JFactory::getApplication()->getMenu();
$active = $menu->getActive() ? $menu->getActive() : $menu->getDefault();
$params = $active->params; 
$maingrid = $params->get('maingrid',2);

?>


<!-- LOGIN -->
<div class="t3-login container <?php $this->_c('login') ?>">

  <?php if($this->hasMessage()) : ?>
  <jdoc:include type="message" />
  <?php endif ?>

  <!-- LOGO -->
  <div class="row">
    <div class="logo text-center col-xs-12 col-md-8 col-lg-<?php echo 12/$maingrid; ?>"  style="float: none; margin-left: auto; margin-right: auto;">
      <div class="logo-<?php echo $logotype, ($logoimgsm ? ' logo-control' : '') ?>">
        <a href="<?php echo $logourl ?>" title="<?php echo strip_tags($sitename) ?>">
          <?php if($logotype == 'image'): ?>
            <img class="logo-img" src="<?php echo JURI::base(true) . '/' . $logoimage ?>" alt="<?php echo strip_tags($sitename) ?>" />
          <?php endif ?>
          <?php if($logoimgsm) : ?>
            <img class="logo-img-sm" src="<?php echo JURI::base(true) . '/' . $logoimgsm ?>" alt="<?php echo strip_tags($sitename) ?>" />
          <?php endif ?>
          <span><?php echo $sitename ?></span>
        </a>
        <small class="site-slogan"><?php echo $slogan ?></small>
      </div>
    </div>
  </div>
  <!-- //LOGO -->

  <?php if ($this->countModules('login')) : ?>
  <div class="row">
    <div class="login-form-module col-xs-12 col-md-8 col-lg-<?php echo 12/$maingrid; ?>"  style="float: none; margin-left: auto; margin-right: auto;">
  	  <jdoc:include type="modules" name="<?php $this->_p('login') ?>" style="T3Xhtml"/>
    </div>
  </div>
  <?php endif ?>

  <?php $this->loadBlock('mainbody-login'); ?>
</div>
<!-- //LOGIN -->
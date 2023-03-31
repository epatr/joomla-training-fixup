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

$logosize = 'col-sm-4';
if ($headright = $this->countModules('head-search or languageswitcherload or notification')) {
	$logosize = 'col-lg-4';
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

?>

<!-- HEADER -->
<header id="t3-header" class="t3-header">
	<div class="row">

		<!-- LOGO -->
		<div class="col-xs-1 <?php echo $logosize ?> logo">
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
		<!-- //LOGO -->

		<?php if ($headright): ?>
			<div class="col-xs-11 col-lg-8">

				<?php if ($this->getParam('addon_offcanvas_enable')) : ?>
		      <?php $this->loadBlock ('off-canvas') ?>
		    <?php endif ?>
				
				<?php if ($this->countModules('notification')) : ?>
					<!-- NOTIFICATION -->
					<div class="notification pull-right <?php $this->_c('notification') ?>">
						<jdoc:include type="modules" name="<?php $this->_p('notification') ?>" style="raw" />
					</div>
					<!-- //NOTIFICATION -->
				<?php endif ?>

				<?php if ($this->countModules('languageswitcherload')) : ?>
					<!-- LANGUAGE SWITCHER -->
					<div class="languageswitcherload pull-right <?php $this->_c('languageswitcherload') ?>">
						<jdoc:include type="modules" name="<?php $this->_p('languageswitcherload') ?>" style="raw" />
					</div>
					<!-- //LANGUAGE SWITCHER -->
				<?php endif ?>

				<?php if ($this->countModules('head-search')) : ?>
					<!-- HEAD SEARCH -->
					<div class="head-search pull-right <?php $this->_c('head-search') ?>">
						<jdoc:include type="modules" name="<?php $this->_p('head-search') ?>" style="raw" />
					</div>
					<!-- //HEAD SEARCH -->
				<?php endif ?>
			</div>
		<?php endif ?>

	</div>
</header>
<!-- //HEADER -->

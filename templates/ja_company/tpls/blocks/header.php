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

// get params
$sitename  = $this->params->get('sitename');
$slogan    = $this->params->get('slogan', '');
$logotype  = $this->params->get('logotype', 'text');
$logoimage = $logotype == 'image' ? $this->params->get('logoimage', T3Path::getUrl('images/logo.png', '', true)) : '';
$logoimgsm = ($logotype == 'image' && $this->params->get('enable_logoimage_sm', 0)) ? $this->params->get('logoimage_sm', T3Path::getUrl('images/logo-sm.png', '', true)) : false;

if (!$sitename) {
	$sitename = JFactory::getConfig()->get('sitename');
}

$logosize = 'col-lg-12';
if ($headright = $this->countModules('head-right')) {
	$logosize = 'col-lg-6';
}

?>

<!-- HEADER -->
<header id="t3-header" class="t3-header <?php if ($this->getParam('navigation_collapse_enable')) : ?>has-collapse<?php endif; ?>">
	<div class="container">
		<div class="row">

			<!-- LOGO -->
			<div class="col-xs-12 col-sm-3 <?php echo $logosize ?> logo">
				<div class="logo-<?php echo $logotype, ($logoimgsm ? ' logo-control' : '') ?>">
					<a href="<?php echo JURI::base(true) ?>" title="<?php echo strip_tags($sitename) ?>">
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
				<div class="head-right hidden-xs col-sm-9 col-lg-6">
					<?php if ($this->countModules('head-right')) : ?>
						<!-- HEAD RIGHT -->
						<div class="head-right <?php $this->_c('head-right') ?>">
							<jdoc:include type="modules" name="<?php $this->_p('head-right') ?>" style="raw" />
						</div>
						<!-- //HEAD RIGHT -->
					<?php endif ?>
				</div>
			<?php endif ?>

		</div>
	</div>
</header>
<!-- //HEADER -->

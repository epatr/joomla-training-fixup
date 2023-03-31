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

$menuPosition = $this->params->get('menu_position','horizontal');
?>

<!-- MAIN NAVIGATION -->
<nav id="t3-mainnav" class="wrap navbar navbar-default t3-mainnav navbar-default-<?php echo $menuPosition;?> <?php if (!$this->getParam('navigation_collapse_enable', 1) && $menuPosition=="horizontal") echo 'hidden-xs hidden-sm hidden-md' ; ?>">
	<!-- LOGO -->
	<div class="logo hidden-sm hidden-md hidden-lg">
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

	<?php if (!$this->getParam('navigation_collapse_enable', 1) && $menuPosition=="horizontal") : ?>
	<div class="navbar-control <?php if($menuPosition=="horizontal") echo 'hidden-lg'; ?>" ><i class="ion-ios-settings"></i><?php echo JText::_('TPL_SHOW_ICON_ONLY');?></div>
  <?php else: ?>
  <div class="navbar-control hidden-lg"><i class="ion-ios-settings"></i><?php echo JText::_('TPL_SHOW_ICON_ONLY');?></div>
  <?php endif; ?>

	<div class="container">
		<div class="t3-navbar">
			<jdoc:include type="<?php echo $this->getParam('navigation_type', 'megamenu') ?>" name="<?php echo $this->getParam('mm_type', 'mainmenu') ?>" />
		</div>
	</div>
</nav>
<!-- //MAIN NAVIGATION -->

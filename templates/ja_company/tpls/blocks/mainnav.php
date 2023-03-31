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

<!-- MAIN NAVIGATION -->
<nav id="t3-mainnav" class="wrap navbar navbar-default t3-mainnav">
	<div class="container">
		<!-- Brand and toggle get grouped for better mobile display -->
		<div class="navbar-header">
			<div class="t3-navbar clearfix">
				<?php if ($this->getParam('navigation_collapse_enable', 1) && $this->getParam('responsive', 1)) : ?>
					<?php $this->addScript(T3_URL.'/js/nav-collapse.js'); ?>
					<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".t3-navbar-collapse" aria-label="navbar-toggle">
						<span class="fa fa-bars"></span>
					</button>
				<?php endif ?>

				<?php if ($this->getParam('addon_offcanvas_enable')) : ?>
					<?php $this->loadBlock ('off-canvas') ?>
				<?php endif ?>

				<?php if ($this->getParam('navigation_collapse_enable')) : ?>
					<div class="t3-navbar-collapse navbar-collapse collapse"></div>
				<?php endif ?>

				<div class="pull-left navbar-collapse collapse">
					<jdoc:include type="<?php echo $this->getParam('navigation_type', 'megamenu') ?>" name="<?php echo $this->getParam('mm_type', 'mainmenu') ?>" />
				</div>

				<?php if ($this->countModules('nav-right')) : ?>
				<div class="nav-right pull-right">
					<!-- HEAD RIGHT -->
					<jdoc:include type="modules" name="<?php $this->_p('nav-right') ?>" style="raw" />
					<!-- //HEAD RIGHT -->
				</div>
				<?php endif ?>
			</div>
		</div>
	</div>
</nav>
<!-- //MAIN NAVIGATION -->
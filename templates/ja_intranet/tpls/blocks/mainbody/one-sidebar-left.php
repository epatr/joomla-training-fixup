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
 * Mainbody 2 columns: sidebar - content
 */
?>

<div id="t3-mainbody" class="container t3-mainbody ja-masonry-wrap isotope-layout">
	<div class="isotope grid clearfix grid-xs-1 grid-smx-2 grid-sm-2 grid-md-4 grid-lg-5">
		<div class="isotope-item grid-sizer"></div>

		<jdoc:include type="modules" name="<?php $this->_p($vars['sidebar']) ?>" style="isotope" />

		<!-- MAIN CONTENT -->
		<div id="t3-content" class="t3-content item-3 isotope-item">
			<?php if($this->hasMessage()) : ?>
			<jdoc:include type="message" />
			<?php endif ?>
			<jdoc:include type="component" />
		</div>
		<!-- //MAIN CONTENT -->

	</div>
</div> 
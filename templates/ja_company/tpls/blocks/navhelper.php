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

<?php if ($this->countModules('t3-cta')) : ?>
	<!-- NAV CTA -->
	<div class="wrap t3-cta <?php $this->_c('t3-cta') ?>">
		<jdoc:include type="modules" name="<?php $this->_p('t3-cta') ?>" />
	</div>
	<!-- //NAV CTA -->
<?php endif ?>

<?php if ($this->countModules('navhelper')) : ?>
	<!-- NAV HELPER -->
	<div class="wrap t3-bottombar <?php $this->_c('navhelper') ?>">
		<div class="container">
			<jdoc:include type="modules" name="<?php $this->_p('navhelper') ?>" />
		</div>
	</div>
	<!-- //NAV HELPER -->
<?php endif ?>

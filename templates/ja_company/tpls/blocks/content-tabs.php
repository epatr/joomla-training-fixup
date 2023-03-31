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
<?php if ($this->countModules('t3-content-tabs')) : ?>
	<div class="section-wrap t3-content-tabs<?php $this->_c('t3-content-tabs') ?>">
		<div class="container">
	  	<jdoc:include type="modules" name="<?php $this->_p('t3-content-tabs') ?>" style="t3tabs" />
	  </div>
	</div>
<?php endif ?>

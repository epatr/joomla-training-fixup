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
<?php if ($this->countModules('content-mast-bottom')) : ?>
	<div class="section-wrap t3-content-bottom <?php $this->_c('topbar') ?>">
    <jdoc:include type="modules" name="<?php $this->_p('content-mast-bottom') ?>" style="t3section" />
	</div>
<?php endif ?>

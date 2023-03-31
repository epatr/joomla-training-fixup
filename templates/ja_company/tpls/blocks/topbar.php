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

<?php if ($this->countModules('topbar-left') || $this->countModules('topbar-right')) : ?>
	<!-- TOPBAR -->
	<div class="wrap t3-topbar">
    <div class="container">
      <div class="row">

        <?php if ($this->countModules('topbar-left')) : ?>
        <div class="topbar-left hidden-xs col-sm-6" >
          <jdoc:include type="modules" name="<?php $this->_p('topbar-left') ?>" style="raw" />
        </div>
        <?php endif ?>

        <?php if ($this->countModules('topbar-right') || $this->countModules('languageswitcherload')) : ?>
        <div class="topbar-right pull-right col-xs-12 col-sm-6">
          <div class="topbar-right">
            <jdoc:include type="modules" name="<?php $this->_p('languageswitcherload') ?>" style="raw" />

            <jdoc:include type="modules" name="<?php $this->_p('topbar-right') ?>" style="raw" />
          </div>
        </div>
        <?php endif ?>

      </div>
    </div>
	</div>
	<!-- //TOPBAR -->
<?php endif ?>

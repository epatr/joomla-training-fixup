<?php
/**
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

  $alertMsg       = $helper->get('alert-message');
  $alertStatus       = $helper->get('alert-status');
?>
<div class="acm-alert item-4 isotope-item">
  <div class="alert alert-<?php echo $alertStatus; ?>">
    <a class="close" data-dismiss="alert">×</a>
    <p><?php echo $alertMsg; ?></p>
  </div>
</div>
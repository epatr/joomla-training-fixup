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
 * Mainbody 2 columns: content - sidebar
 */
$menu = JFactory::getApplication()->getMenu();
$active = $menu->getActive() ? $menu->getActive() : $menu->getDefault();
$params = $active->params; 
$maingrid = $params->get('maingrid',2);
$menuPosition = $this->params->get('menu_position','horizontal');
?>

<div id="t3-mainbody" class="container t3-mainbody">

    <!-- MAIN CONTENT -->
    <div id="t3-content" class="t3-content">
      <?php if($this->hasMessage()) : ?>
        <jdoc:include type="message" />
      <?php endif ?>

      <jdoc:include type="component" />
    </div>
    <!-- //MAIN CONTENT -->

</div> 
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

if (is_array($this->getParam('skip_component_content')) && 
    in_array(JFactory::getApplication()->input->getInt('Itemid'), $this->getParam('skip_component_content'))) 
  return;

$menu = JFactory::getApplication()->getMenu();
$active = $menu->getActive() ? $menu->getActive() : $menu->getDefault();
$params = $active->params; 
$maingrid = $params->get('maingrid',2);

?>
<div class="row">
  <div class="login-form-module col-xs-12 col-md-8 col-lg-<?php echo 12/$maingrid; ?>" style="float: none; margin-left: auto; margin-right: auto;">
    <jdoc:include type="component" />
  </div>
</div>
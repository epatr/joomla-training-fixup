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

JHtml::_('formbehavior.chosen', 'select');
?>

<div class="search<?php echo $this->pageclass_sfx; ?>">
	<div class="search-box-border">
		<?php if ($this->params->get('show_page_heading')) : ?>
			<h1 class="page-title">
				<?php if ($this->escape($this->params->get('page_heading'))) : ?>
					<?php echo $this->escape($this->params->get('page_heading')); ?>
				<?php else : ?>
					<?php echo $this->escape($this->params->get('page_title')); ?>
				<?php endif; ?>
			</h1>
		<?php endif; ?>
		<?php echo $this->loadTemplate('form'); ?>
	</div>
	<div class="searchintro<?php echo $this->params->get('pageclass_sfx'); ?>">
		<?php if (!empty($this->searchword)): ?>
			<p><?php echo JText::plural('COM_SEARCH_SEARCH_KEYWORD_N_RESULTS', '<span class="badge badge-info">' . $this->total . '</span>'); ?></p>
		<?php endif; ?>
	</div>
	<?php if ($this->error == null && count($this->results) > 0) : ?>
		<?php echo $this->loadTemplate('results'); ?>
	<?php else : ?>
		<?php echo $this->loadTemplate('error'); ?>
	<?php endif; ?>
</div>

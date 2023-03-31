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
?>
<ul class="latestnews<?php echo $moduleclass_sfx; ?>">
<?php foreach ($list as $item) :  ?>
	<li class="clearfix">
		<?php echo JLayoutHelper::render('joomla.content.intro_image', $item); ?>
		<div class="content-item">
			<a class="title" href="<?php echo $item->link; ?>">
				<span>
					<?php echo $item->title; ?>
				</span>
			</a>
			<div class="content-meta">
			<span><?php echo JHtml::_('date', $item->displayDate, JText::_('DATE_FORMAT_LC3')); ?></span>
			<span><?php echo JText::sprintf('TPL_COM_CONTENT_WRITTEN_BY', ''); ?> <?php echo $item->author; ?></span>
			</div>
		</div>
	</li>
<?php endforeach; ?>
</ul>

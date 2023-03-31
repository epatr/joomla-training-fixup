<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_articles_latest
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
?>
<div class="news-update <?php echo $params->get ('moduleclass_sfx') ?>">
<?php foreach ($list as $item) :  ?>
	<div itemscope itemtype="https://schema.org/Article">
		<?php $Image = json_decode($item->images);
					$introImage = $Image->image_intro;?>
		<div class="intro-image">
			<img src="<?php echo $introImage ;?>" alt="" />
		</div>
		
		<h4>
			<a href="<?php echo $item->link; ?>" itemprop="url">
				<span itemprop="name">
					<?php echo $item->title; ?>
				</span>
			</a>
		</h4>

		<dd class="modified">
			<time datetime="<?php echo JHtml::_('date', $item->modified, 'c'); ?>" itemprop="dateModified">
				<?php echo JText::sprintf(JHtml::_('date', $item->modified, JText::_('DATE_FORMAT_LC3'))); ?>
			</time>
		</dd>
	</div>
<?php endforeach; ?>
</div>

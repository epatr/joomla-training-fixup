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

// Create a shortcut for params.
$params  = & $this->item->params;
$images  = json_decode($this->item->images);
$canEdit = $this->item->params->get('access-edit');
$info    = $params->get('info_block_position', 2);
$aInfo1 = ($params->get('show_publish_date') || $params->get('show_category') || $params->get('show_parent_category') || $params->get('show_author'));
$aInfo2 = ($params->get('show_create_date') || $params->get('show_modify_date') || $params->get('show_hits'));
$topInfo = ($aInfo1 && $info != 1) || ($aInfo2 && $info == 0);
$botInfo = ($aInfo1 && $info == 1) || ($aInfo2 && $info != 0);
$icons = $params->get('access-edit') || $params->get('show_print_icon') || $params->get('show_email_icon');

?>

  <?php if ($this->item->state == 0 || strtotime($this->item->publish_up) > strtotime(JFactory::getDate())
	|| ((strtotime($this->item->publish_down) < strtotime(JFactory::getDate())) && $this->item->publish_down != '0000-00-00 00:00:00' )) : ?>
<div class="system-unpublished">
	<?php endif; ?>

	<!-- Article -->
	<article>

			<?php echo JLayoutHelper::render('joomla.content.intro_image', $this->item); ?>

			<div class="item-ct">

		    <!-- Aside -->
		    <?php if ($topInfo || $icons) : ?>
		    <aside class="article-aside clearfix">
		      <?php if ($topInfo): ?>
		      <?php echo JLayoutHelper::render('joomla.content.info_block.block', array('item' => $this->item, 'params' => $params, 'position' => 'above')); ?>
		      <?php endif; ?>
		      
		      <?php if ($icons): ?>
		      <?php echo JLayoutHelper::render('joomla.content.icons', array('item' => $this->item, 'params' => $params)); ?>
		      <?php endif; ?>
		    </aside>  
		    <?php endif; ?>
		    <!-- //Aside -->
		    
				<?php if ($params->get('show_title')) : ?>
					<?php echo JLayoutHelper::render('joomla.content.item_title', array('item' => $this->item, 'params' => $params, 'title-tag'=>'h2')); ?>
				<?php endif; ?>

				<?php echo $this->item->event->afterDisplayTitle; ?>

				<?php echo $this->item->event->beforeDisplayContent; ?>

				<section class="article-intro clearfix" itemprop="articleBody">
					<?php echo $this->item->introtext; ?>

				</section>
			    <!-- footer -->
			    <?php if ($botInfo) : ?>
			    <footer class="article-footer clearfix">
			      <?php echo JLayoutHelper::render('joomla.content.info_block.block', array('item' => $this->item, 'params' => $params, 'position' => 'below')); ?>
			    </footer>
			    <?php endif; ?>
			    <!-- //footer -->

			    <?php if ($params->get('show_tags', 1) && !empty($this->item->tags)) : ?>
			      <?php echo JLayoutHelper::render('joomla.content.tags', $this->item->tags->itemTags); ?>
			    <?php endif; ?>

				<?php if ($params->get('show_readmore') && $this->item->readmore) :
					if ($params->get('access-view')) :
						$link = JRoute::_(ContentHelperRoute::getArticleRoute($this->item->slug, $this->item->catid));
					else :
						$menu      = JFactory::getApplication()->getMenu();
						$active    = $menu->getActive();
						$itemId    = $active->id;
						$link1     = JRoute::_('index.php?option=com_users&view=login&Itemid=' . $itemId);
						$returnURL = JRoute::_(ContentHelperRoute::getArticleRoute($this->item->slug, $this->item->catid));
						$link      = new JURI($link1);
						$link->setVar('return', base64_encode($returnURL));
					endif;
					?>
					<section class="readmore">
						<a class="btn btn-primary" href="<?php echo $link; ?>" itemprop="url">
							<span>
							<?php if (!$params->get('access-view')) :
								echo JText::_('COM_CONTENT_REGISTER_TO_READ_MORE');
							elseif ($readmore = $this->item->alternative_readmore) :
								echo $readmore;
								if ($params->get('show_readmore_title', 0) != 0) :
									echo JHtml::_('string.truncate', ($this->item->title), $params->get('readmore_limit'));
								endif;
							elseif ($params->get('show_readmore_title', 0) == 0) :
								echo JText::sprintf('COM_CONTENT_READ_MORE_TITLE');
							else :
								echo JText::_('COM_CONTENT_READ_MORE');
								echo JHtml::_('string.truncate', ($this->item->title), $params->get('readmore_limit'));
							endif; ?>
							</span>
						</a>
				<?php endif; ?>
			</div>
		</section>
	</article>
	<!-- //Article -->

  <?php if ($this->item->state == 0 || strtotime($this->item->publish_up) > strtotime(JFactory::getDate())
	|| ((strtotime($this->item->publish_down) < strtotime(JFactory::getDate())) && $this->item->publish_down != '0000-00-00 00:00:00' )) : ?>
</div>
<?php endif; ?>
<?php echo $this->item->event->afterDisplayContent; ?>

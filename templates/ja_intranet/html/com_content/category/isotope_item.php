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

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.framework');

// Create a shortcut for params.
$params  = & $this->item->params;
$images  = json_decode($this->item->images);
$info    = $params->get('info_block_position', 2);
$aInfo1 = ($params->get('show_publish_date') || $params->get('show_category') || $params->get('show_parent_category') || $params->get('show_author'));
$aInfo2 = ($params->get('show_create_date') || $params->get('show_modify_date') || $params->get('show_hits'));
$topInfo = ($aInfo1 && $info != 1) || ($aInfo2 && $info == 0);
$botInfo = ($aInfo1 && $info == 1) || ($aInfo2 && $info != 0);
$icons = $params->get('access-edit') || $params->get('show_print_icon') || $params->get('show_email_icon');

// update catslug if not exists - compatible with 2.5
if (empty ($this->item->catslug)) {
  $this->item->catslug = $this->item->category_alias ? ($this->item->catid.':'.$this->item->category_alias) : $this->item->catid;
}
?>

<?php 
// Article Link
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

<?php 
  static $category_classes = array();
  $catid = $this->item->catid;

  if (!isset($category_classes[$catid])) {
    $cat = JCategories::getInstance('Content')->get($catid);
    $category_classes[$catid] = $cat->getParams()->get('category_class');
  }

  $category_class = $category_classes[$catid];
?>

<?php if ($this->item->state == 0 || strtotime($this->item->publish_up) > strtotime(JFactory::getDate())
|| ((strtotime($this->item->publish_down) < strtotime(JFactory::getDate())) && $this->item->publish_down != '0000-00-00 00:00:00' )) : ?>
<div class="system-unpublished">
<?php endif; ?>

	<!-- Article -->
	<article>
    <a href="<?php echo $link; ?>" title="<?php echo $this->item->title; ?>">
      <?php echo JLayoutHelper::render('joomla.content.intro_image', $this->item); ?>
    </a>

    <?php if($category_class !='') { ?>
      <span class="ico_type fa <?php echo $category_class; ?>"></span>
    <?php } ?>
    
    <?php if ($params->get('show_title')) : ?>
			<?php echo JLayoutHelper::render('joomla.content.item_title', array('item' => $this->item, 'params' => $params, 'title-tag'=>'h2')); ?>
    <?php endif; ?>

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

		<section class="article-intro clearfix">
			<?php if (!$params->get('show_intro')) : ?>
				<?php echo $this->item->event->afterDisplayTitle; ?>
			<?php endif; ?>

			<?php echo $this->item->event->beforeDisplayContent; ?>

			<?php echo $this->item->introtext; ?>
		</section>

    <!-- footer -->
    <?php if ($botInfo || ($params->get('show_readmore') && $this->item->readmore)) : ?>
    <footer class="article-footer clearfix">
      <?php echo JLayoutHelper::render('joomla.content.info_block.block', array('item' => $this->item, 'params' => $params, 'position' => 'below')); ?>

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
        <section class="readmore <?php echo $params->get('show_readmore_title', 0) ? 'readmore-title' : ''; ?>">
          <a class="btn btn-primary" href="<?php echo $link; ?>">
            <span>
              <?php echo JText::_('TPL_ISOTOPE_READ_MORE_TITLE'); ?>
            </span>
          </a>
        </section>
      <?php endif; ?>
    </footer>
    <?php endif; ?>
    <!-- //footer -->

	</article>
	<!-- //Article -->

<?php if ($this->item->state == 0 || strtotime($this->item->publish_up) > strtotime(JFactory::getDate())
|| ((strtotime($this->item->publish_down) < strtotime(JFactory::getDate())) && $this->item->publish_down != JFactory::getDbo()->getNullDate())) : ?>
</div>
<?php endif; ?>

<?php echo $this->item->event->afterDisplayContent; ?> 

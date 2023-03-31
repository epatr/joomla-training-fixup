<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_content
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT.'/helpers');
JHtml::addIncludePath(T3_PATH.'/html/com_content');
JHtml::addIncludePath(dirname(dirname(__FILE__)));
JHtml::_('behavior.caption');
?>
<div class="blog<?php echo $this->pageclass_sfx;?>">
	<!-- Load Modules Positions -->
    <?php 
    $navhelper_modules = 'navhelper';
    $attrs = array();
    $result = null;
    $renderer = JFactory::getDocument()->loadRenderer('modules');
    $modules = $renderer->render($navhelper_modules, $attrs, $result);
    ?>
	<!-- Load Modules Positions End -->

	<?php if ($this->params->get('show_page_heading', 1) || $modules) : ?>
	<div class="page-header clearfix">
		<h1 class="page-title"> <?php echo $this->escape($this->params->get('page_heading')); ?> </h1>

		<!-- Load Modules Positions -->
    <div class="hidden-xs hidden-sm"><?php echo $modules ?></div>
		<!-- Load Modules Positions End -->

	</div>
	<?php endif; ?>

	<?php if ($this->params->get('show_category_title', 1) or $this->params->get('page_subheading') or ($this->params->get('show_tags', 1) && !empty($this->category->tags->itemTags)) or ($this->params->get('show_description', 1) || $this->params->def('show_description_image', 1)) or (empty($this->lead_items) && empty($this->link_items) && empty($this->intro_items))) : ?>
	<div class="category-info">
		<?php if ($this->params->get('show_category_title', 1) or $this->params->get('page_subheading')) : ?>
	  	<div class="page-subheader clearfix">
	  		<h2 class="page-subtitle"><?php echo $this->escape($this->params->get('page_subheading')); ?>
				<?php if ($this->params->get('show_category_title')) : ?>
				<small class="subheading-category"><?php echo $this->category->title;?></small>
				<?php endif; ?>
	  		</h2>
		</div>
		<?php endif; ?>
		
		<?php if ($this->params->get('show_description', 1) || $this->params->def('show_description_image', 1) || ($this->params->get('show_tags', 1) && !empty($this->category->tags->itemTags))) : ?>
		<div class="category-desc clearfix"><div class="row">
			<?php if ($this->params->get('show_description_image') && $this->category->getParams()->get('image')) : ?>
				<div class="col-md-4">
					<img src="<?php echo $this->category->getParams()->get('image'); ?>"/>
				</div>
			<?php endif; ?>
			<?php if ($this->params->get('show_description') && $this->category->description) : ?>
				<div class="col-md-8">
					<?php echo JHtml::_('content.prepare', $this->category->description, '', 'com_content.category'); ?>

					<?php if ($this->params->get('show_tags', 1) && !empty($this->category->tags->itemTags)) : ?>
						<?php echo JLayoutHelper::render('joomla.content.tags', $this->category->tags->itemTags); ?>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div></div>
		<?php endif; ?>

		<?php if (empty($this->lead_items) && empty($this->link_items) && empty($this->intro_items)) : ?>
			<?php if ($this->params->get('show_no_articles', 1)) : ?>
				<p><?php echo JText::_('COM_CONTENT_NO_ARTICLES'); ?></p>
			<?php endif; ?>
		<?php endif; ?>
	</div>
	<?php endif; ?>

	<?php $leadingcount = 0; ?>
	<?php if (!empty($this->lead_items)) : ?>
	<div class="items-leading">
		<?php foreach ($this->lead_items as &$item) : ?>
		<div class="leading leading-<?php echo $leadingcount; ?><?php echo $item->state == 0 ? ' system-unpublished' : null; ?>">
			<?php
				$this->item = &$item;
				echo $this->loadTemplate('item');
			?>
		</div>
		<?php $leadingcount++; ?>
		<?php endforeach; ?>
	</div><!-- end items-leading -->
	<?php endif; ?>

	<?php
		$introcount = (count($this->intro_items));
		$counter = 0;
	?>

	<?php if (!empty($this->intro_items)) : ?>
	<?php foreach ($this->intro_items as $key => &$item) : ?>
		<?php $rowcount = ((int) $counter % (int) $this->columns) + 1; ?>
		<?php if ($rowcount == 1) : ?>
			<?php $row = $counter / $this->columns; ?>
		<div class="items-row cols-<?php echo (int) $this->columns;?> <?php echo 'row-'.$row; ?> row">
		<?php endif; ?>
			<div class="col-sm-<?php echo round((12 / $this->columns));?>">
				<div class="item column-<?php echo $rowcount;?><?php echo $item->state == 0 ? ' system-unpublished' : null; ?>">
					<?php
					$this->item = &$item;
					echo $this->loadTemplate('item');
				?>
				</div><!-- end item -->
				<?php $counter++; ?>
			</div><!-- end span -->
			<?php if (($rowcount == $this->columns) or ($counter == $introcount)) : ?>			
		</div><!-- end row -->
			<?php endif; ?>
	<?php endforeach; ?>
	<?php endif; ?>
	
	<?php if (!empty($this->link_items)) : ?>
	<div class="items-more">
	<?php echo $this->loadTemplate('links'); ?>
	</div>
	<?php endif; ?>
	
	<?php if (!empty($this->children[$this->category->id])&& $this->maxLevel != 0) : ?>
	<div class="cat-children">
		<?php if ($this->params->get('show_category_heading_title_text', 1) == 1) : ?>
		<h3> <?php echo JTEXT::_('JGLOBAL_SUBCATEGORIES'); ?> </h3>
		<?php endif; ?>
		<?php echo $this->loadTemplate('children'); ?> </div>
	<?php endif; ?>
	
	<?php 
  $pagesTotal = isset($this->pagination->pagesTotal) ? $this->pagination->pagesTotal : $this->pagination->get('pages.total');
  if (($this->params->def('show_pagination', 1) == 1  || ($this->params->get('show_pagination') == 2)) && ($pagesTotal > 1)) : ?>
	<div class="pagination-wrap">
		<?php  if ($this->params->def('show_pagination_results', 1)) : ?>
		<div class="counter"> <?php echo $this->pagination->getPagesCounter(); ?></div>
		<?php endif; ?>
		<?php echo $this->pagination->getPagesLinks(); ?> </div>
	<?php  endif; ?>
</div>

<?php
/**
 * @package     Joomla.site
 * @subpackage  Component.hwdmediashare
 *
 * @copyright   Copyright (C) 2013 Highwood Design Limited. All rights reserved.
 * @license     GNU General Public License http://www.gnu.org/copyleft/gpl.html
 * @author      Dave Horsfall
 */

defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR . '/helpers/html');
JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.framework', true);

$user = JFactory::getUser();

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$saveOrder	= $listOrder == 'map.ordering';
if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_hwdmediashare&task=groupmembers.saveOrderAjax&tmpl=component';
	JHtml::_('sortablelist.sortable', 'mediaList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}
?>
<form action="<?php echo JRoute::_('index.php?option=com_hwdmediashare&view=groupmembers&tmpl=component&group_id='.$this->groupId.'&add=0'); ?>" method="post" name="adminForm" id="adminForm" class="form-inline">
        <fieldset class="filter clearfix">
		<div class="btn-toolbar">
			<div class="btn-group pull-left">
				<input type="text" name="filter[search]" id="filter_search" value="<?php echo $this->escape($this->state->get('filter.search')); ?>" size="30" title="<?php echo JText::_('COM_HWDMS_FILTER_SEARCH_DESC'); ?>" />
			</div>
			<div class="btn-group pull-left">
				<button type="submit" class="btn hasTooltip" title="<?php echo JHtml::tooltipText('JSEARCH_FILTER_SUBMIT'); ?>" data-placement="bottom">
					<span class="icon-search"></span><?php echo '&#160;' . JText::_('JSEARCH_FILTER_SUBMIT'); ?></button>
				<button type="button" class="btn hasTooltip" title="<?php echo JHtml::tooltipText('JSEARCH_FILTER_CLEAR'); ?>" data-placement="bottom" onclick="document.id('filter_search').value='';this.form.submit();">
					<span class="icon-remove"></span><?php echo '&#160;' . JText::_('JSEARCH_FILTER_CLEAR'); ?></button>
			</div>
			<div class="btn-group pull-left">
                                <a class="btn btn-info" href="<?php echo JRoute::_('index.php?option=com_hwdmediashare&view=groupmembers&tmpl=component&group_id='.$this->groupId.'&add=1'); ?>">
					<i class="icon-plus"></i><?php echo '&#160;' . JText::_('COM_HWDMS_BTN_ADD_MORE_MEMBERS'); ?></a>
			</div>
			<div class="clearfix"></div>
		</div>
	</fieldset>
	<table class="table table-striped table-condensed" id="mediaList">
		<tfoot>
			<tr>
				<td>
					<?php echo $this->pagination->getListFooter(); ?>
				</td>
			</tr>
		</tfoot>
		<tbody>
		<?php foreach ($this->items as $i => $item) : 
                $ordering   = ($listOrder == 'a.ordering');
                $canChange  = $user->authorise('core.edit.state', 'com_hwdmediashare'); ?>                    
			<tr class="row<?php echo $i % 2; ?>">                      
				<td>
                                        <?php echo $this->getButton($item, $i); ?>                                                                                                           
                                        <?php echo JHtml::_('HwdGrid.id', $i, $item->id, 'cb', false, 'cid', 'hide'); ?>
                                        <div class="pull-left thumb-wrapper">
                                                <img src="<?php echo JRoute::_(hwdMediaShareThumbnails::thumbnail($item)); ?>" width="75" />
                                        </div>
                                        <p><strong><?php echo $this->escape($item->name); ?></strong></p>
                                        <?php echo $item->username; ?>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<div>
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
		<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
		<?php echo JHtml::_('form.token'); ?>
	</div>
</form>

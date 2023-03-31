<?php
/**
 * @package     Joomla.administrator
 * @subpackage  Component.hwdmediashare
 *
 * @copyright   Copyright (C) 2013 Highwood Design Limited. All rights reserved.
 * @license     GNU General Public License http://www.gnu.org/copyleft/gpl.html
 * @author      Dave Horsfall
 */

defined('_JEXEC') or die;

//JHtml::_('behavior.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('dropdown.init');
JHtml::_('formbehavior.chosen', 'select');

$user		= JFactory::getUser();
$listOrder	= $this->escape($this->state->get('list.ordering'));
$listDirn	= $this->escape($this->state->get('list.direction'));
$saveOrder	= $listOrder == 'a.ordering';
if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_hwdmediashare&task=media.saveOrderAjax&tmpl=component';
	JHtml::_('sortablelist.sortable', 'mediaList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}
?>
<script type="text/javascript">
	Joomla.orderTable = function()
	{
		table = document.getElementById("sortTable");
		direction = document.getElementById("directionTable");
		order = table.options[table.selectedIndex].value;
		if (order != '<?php echo $listOrder; ?>')
		{
			dirn = 'asc';
		}
		else
		{
			dirn = direction.options[direction.selectedIndex].value;
		}
		Joomla.tableOrdering(order, dirn, '');
	}
</script>
<form action="<?php echo JRoute::_('index.php?option=com_hwdmediashare&view=media'); ?>" method="post" name="adminForm" id="adminForm">
<?php if (!empty($this->sidebar)) : ?>
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
<?php else : ?>
	<div id="j-main-container">
<?php endif;?>
		<?php echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>  
		<?php if (empty($this->items)) : ?>
			<div class="alert alert-no-items">
				<?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
			</div>
		<?php else : ?>
                        <table class="table table-striped" id="mediaList">
                                <thead><?php echo $this->loadTemplate('head');?></thead>
                                <tbody><?php echo $this->loadTemplate('body');?></tbody>
                        </table>
                <?php endif; ?>
		<?php echo $this->pagination->getListFooter(); ?>
		<?php //Load the batch processing form. ?>
		<?php //echo $this->loadTemplate('batch'); ?>
            
            
                <?php // Load the batch processing form. ?>
                <?php if ($user->authorise('core.create', 'com_hwdmediashare')
                        && $user->authorise('core.edit', 'com_hwdmediashare')
                        && $user->authorise('core.edit.state', 'com_hwdmediashare')) : ?>
                        <?php echo JHtml::_(
                                'bootstrap.renderModal',
                                'collapseModal',
                                array(
                                        'title' => JText::_('COM_HWDMS_BATCH_OPTIONS'),
                                        'footer' => '<button class="btn" type="button" onclick="document.id(\'batch-access\').value=\'\';document.id(\'batch-language-id\').value=\'\';document.id(\'batch-tag-id\').value=\'\'" data-dismiss="modal">' . JText::_('JCANCEL') . '</button>
                                                     <button class="btn btn-primary" type="submit" onclick="Joomla.submitbutton(\'editmedia.batch\');">' . JText::_('JGLOBAL_BATCH_PROCESS') . '</button>'
                                ),
                                $this->loadTemplate('batch')
                        ); ?>
                <?php endif; ?>
            
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<?php echo JHtml::_('form.token'); ?>
        </div>
</form>

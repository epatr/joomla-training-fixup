<?php
/**
 * @package        Joomla
 * @subpackage     Event Booking
 * @author         Tuan Pham Ngoc
 * @copyright      Copyright (C) 2010 Ossolution Team
 * @license        GNU/GPL, see LICENSE.php
 */
defined('_JEXEC') or die;
$output = '<input name="search" id="search_eb_box" maxlength="50"  class="inputbox" type="text" size="50" value="'.$text.'"  onblur="if(this.value==\'\') this.value=\''.$text.'\';" onfocus="if(this.value==\''.$text.'\') this.value=\'\';" />';

$bootstrapHelper   = EventbookingHelperBootstrap::getInstance();
$btnToolbarClass   = $bootstrapHelper->getClassMapping('btn-toolbar');
$btnGroupClass     = $bootstrapHelper->getClassMapping('btn-group');
$pullLeftClass     = $bootstrapHelper->getClassMapping('pull-left');
$controlGroupClass = $bootstrapHelper->getClassMapping('control-group');
$controlLabelClass = $bootstrapHelper->getClassMapping('control-label');
$controlsClass     = $bootstrapHelper->getClassMapping('controls');
?>
<form method="post" name="eb_search_form" id="eb_search_form" action="<?php echo JRoute::_('index.php?option=com_eventbooking&task=search&&Itemid='.$itemId);  ?>">
    <div class="filters <?php echo $btnToolbarClass; ?> eb-search-bar-container clearfix">
        <div class="filter-search <?php echo $btnGroupClass . ' ' . $pullLeftClass;;?>">
	        <?php echo $output ; ?>
        </div>
        <?php
            if ($showFromDate)
            {
            ?>
                <div class="<?php echo $btnGroupClass . ' ' . $pullLeftClass; ?>">
	                <?php echo JHtml::_('calendar', $fromDate, 'filter_from_date', 'filter_from_date', '%Y-%m-%d', ['class' => 'input-medium', 'placeholder' => JText::_('EB_SEARCH_FROM_DATE')]); ?>
                </div>
            <?php
            }

            if ($showToDate)
            {
            ?>
                <div class="<?php echo $btnGroupClass . ' ' . $pullLeftClass; ?>">
                    <?php echo JHtml::_('calendar', $toDate, 'filter_to_date', 'filter_to_date', '%Y-%m-%d', ['class' => 'input-medium', 'placeholder' => JText::_('EB_SEARCH_TO_DATE')]); ?>
                </div>
            <?php
            }
        ?>
        <div class="<?php echo $btnGroupClass . ' ' . $pullLeftClass; ?>">
			<?php
            if ($showCategory)
            {
	            echo $lists['category_id'];
            }

			if ($showLocation)
            {
	            echo $lists['location_id'];
            }
			?>
        </div>

        <?php
        if ($enableRadiusSearch)
        {
        ?>
        <div class="<?php echo $btnGroupClass . ' ' . $pullLeftClass; ?>">
            <input type="text" name="filter_address" placeholder="<?php echo JText::_('EB_ADDRESS_CITY_POSTCODE'); ?>" value="<?php echo htmlspecialchars($filterAddress, ENT_COMPAT, 'UTF-8'); ?>" />
            <?php echo $lists['filter_distance']; ?>
        </div>
        <?php
        }
        ?>

        <div class="<?php echo $btnGroupClass . ' ' . $pullLeftClass; ?>">
            <input type="button" class="btn btn-primary button search_button" value="<?php echo JText::_('EB_SEARCH'); ?>" onclick="searchData(this.form);" />
        </div>
    </div>
    <script language="javascript">
    	function searchData(form)
	    {
        	if (form.search.value == '<?php echo $text ?>')
	        {
            	form.search.value = '' ;
        	}
        	form.submit();
    	}
    </script>
	<input type="hidden" name="layout" value="<?php echo $layout; ?>" />
</form>
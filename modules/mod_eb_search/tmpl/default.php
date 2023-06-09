<?php
/**
 * @package        Joomla
 * @subpackage     Event Booking
 * @author         Tuan Pham Ngoc
 * @copyright      Copyright (C) 2010 Ossolution Team
 * @license        GNU/GPL, see LICENSE.php
 */
defined('_JEXEC') or die;
$output = '<input name="search" id="search_eb_box" maxlength="50"  class="inputbox" type="text" size="20" value="'.$text.'"  onblur="if(this.value==\'\') this.value=\''.$text.'\';" onfocus="if(this.value==\''.$text.'\') this.value=\'\';" />';

$bootstrapHelper = new EventbookingHelperBootstrap($config->twitter_bootstrap_version);
$controlGroupClass = $bootstrapHelper->getClassMapping('control-group');
$controlLabelClass = $bootstrapHelper->getClassMapping('control-label');
$controlsClass     = $bootstrapHelper->getClassMapping('controls');
?>					
<form method="post" name="eb_search_form" id="eb_search_form" action="<?php echo JRoute::_('index.php?option=com_eventbooking&task=search&&Itemid='.$itemId);  ?>" class="form">
    <div class="<?php echo $controlGroupClass; ?>">
        <div class="<?php echo $controlsClass; ?>"><?php echo $output; ?></div>
    </div>
    <?php
    if ($showFromDate)
    {
    ?>
        <div class="<?php echo $controlGroupClass; ?>">
            <div class="<?php echo $controlLabelClass; ?>"><?php echo JText::_('EB_SEARCH_FROM_DATE'); ?></div>
            <div class="<?php echo $controlsClass; ?>"><?php echo JHtml::_('calendar', $fromDate, 'filter_from_date', 'filter_from_date', '%Y-%m-%d', ['class' => 'input-medium']); ?></div>
        </div>
    <?php
    }

    if ($showToDate)
    {
    ?>
        <div class="<?php echo $controlGroupClass; ?>">
            <div class="<?php echo $controlLabelClass; ?>"><?php echo JText::_('EB_SEARCH_TO_DATE'); ?></div>
            <div class="<?php echo $controlsClass; ?>"><?php echo JHtml::_('calendar', $toDate, 'filter_to_date', 'filter_to_date', '%Y-%m-%d', ['class' => 'input-medium']); ?></div>
        </div>
    <?php
    }

    if ($showCategory && !$presetCategoryId)
    {
    ?>
        <div class="<?php echo $controlGroupClass; ?>">
            <div class="<?php echo $controlLabelClass; ?>"><?php echo JText::_('EB_CATEGORY'); ?></div>
            <div class="<?php echo $controlsClass; ?>"><?php echo $lists['category_id']; ?></div>
        </div>
    <?php
    }

    if ($showLocation)
    {
	?>
        <div class="<?php echo $controlGroupClass; ?>">
            <div class="<?php echo $controlLabelClass; ?>"><?php echo JText::_('EB_LOCATION'); ?></div>
            <div class="<?php echo $controlsClass; ?>"><?php echo $lists['location_id']; ?></div>
        </div>
	 <?php
    }

    if ($enableRadiusSearch)
    {
    ?>
        <div class="<?php echo $controlGroupClass; ?>">
            <div class="<?php echo $controlsClass; ?>">
                <input type="text" name="filter_address" placeholder="<?php echo JText::_('EB_ADDRESS_CITY_POSTCODE'); ?>" value="<?php echo htmlspecialchars($filterAddress, ENT_COMPAT, 'UTF-8'); ?>">
            </div>
        </div>
        <div class="<?php echo $controlGroupClass; ?>">
            <div class="<?php echo $controlsClass; ?>">
                <div class="<?php echo $controlsClass; ?>"><?php echo $lists['filter_distance']; ?></div>
            </div>
        </div>
    <?php
    }
    ?>

    <div class="form-actions">
        <input type="button" class="btn btn-primary button search_button" value="<?php echo JText::_('EB_SEARCH'); ?>" onclick="searchData(this.form);" />
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
    <?php
        if ($presetCategoryId)
        {
        ?>
            <input type="hidden" name="category_id" value="<?php echo $presetCategoryId; ?>" />
        <?php
        }
    ?>
</form>

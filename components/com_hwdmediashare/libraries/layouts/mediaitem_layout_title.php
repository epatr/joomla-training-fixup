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

// Include the component HTML helpers.
JHtml::_('HwdPopup.iframe', 'page');
JHtml::_('bootstrap.tooltip');
$displayData->item->slug = $displayData->item->alias ? ($displayData->item->id . ':' . $displayData->item->alias) : $displayData->item->id;
?>
<a href="<?php echo JRoute::_(hwdMediaShareHelperRoute::getMediaItemRoute($displayData->item->slug)); ?>" class="hasTooltip" title="<?php echo JHtml::tooltipText($displayData->item->title, ($displayData->params->get('list_tooltip_contents') != '0' ? JHtml::_('string.truncate', $displayData->item->description, $displayData->params->get('list_desc_truncate'), false, false) : '')); ?>">
  <?php echo $displayData->escape(JHtml::_('string.truncate', $displayData->item->title, $displayData->params->get('list_title_truncate'), false, false)); ?>
</a>

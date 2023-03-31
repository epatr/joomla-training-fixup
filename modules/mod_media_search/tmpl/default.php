<?php
/**
 * @package     Joomla.site
 * @subpackage  Module.mod_media_search
 *
 * @copyright   (C) 2014 Joomlabuzz.com
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @author      Dave Horsfall
 */

defined('_JEXEC') or die;

$lang = JFactory::getLanguage();

// Including fallback code for the placeholder attribute in the search field.
JHtml::_('jquery.framework');
JHtml::_('script', 'system/html5fallback.js', false, true);
?>
<div class="hwd-container">
	<form action="<?php echo JRoute::_('index.php');?>" method="post" class="form-inline">
                <div class="btn-wrapper<?php echo $params->get('button_show', 1) ? ' input-append' : ''; ?>">
                  <input type="text" maxlength="<?php $lang->getUpperLimitSearchWord(); ?>" placeholder="<?php echo htmlspecialchars($params->get('input_hint', JText::_('MOD_MEDIA_SEARCH_SEARCHBOX_HINT'))); ?>" style="max-width:100%;" size="<?php echo (int) $params->get('input_width', 20); ?>" class="" value="" id="jform_hwd_keywords" name="jform[keywords]">  
                  <?php if ($params->get('button_show', 1)): ?>
                        <button type="submit" class="btn" title="<?php echo htmlspecialchars($params->get('button_text', JText::_('MOD_MEDIA_SEARCH_BUTTON_TEXT'))); ?>">
                              <?php if ($params->get('button_icon', 1)): ?>
                                    <i class="icon-search"></i>
                              <?php else: ?>
                                    <?php echo htmlspecialchars($params->get('button_text', JText::_('MOD_MEDIA_SEARCH_BUTTON_TEXT'))); ?>
                              <?php endif; ?>
                        </button>
                  <?php endif; ?>
                </div>
		<input type="hidden" name="task" value="search.processform" />
		<input type="hidden" name="option" value="com_hwdmediashare" />
		<input type="hidden" name="Itemid" value="<?php echo (int) $params->get('itemid', 0); ?>" />
	</form>
</div>
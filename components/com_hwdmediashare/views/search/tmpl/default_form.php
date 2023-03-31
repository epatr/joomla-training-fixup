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
$fieldset = $this->form->getFieldset($this->type);
?>
<div class="btn-wrapper input-append">
  <?php echo $this->form->getInput('keywords'); ?>
  <button title="<?php echo JHtml::tooltipText('COM_HWDMS_SEARCH'); ?>" class="btn" onclick="Joomla.submitbutton('search.processform')">
    <i class="icon-search"></i>
  </button>
</div>
<?php if ($this->mode == 'advanced'): ?>
<fieldset class="form-horizontal">
  <?php foreach($fieldset as $field): ?>
    <div class="control-group">
      <div class="control-label"><?php echo $field->label; ?></div>
      <div class="controls"><?php echo $field->input; ?></div>
    </div>
  <?php endforeach; ?>
</fieldset>
<?php endif; ?>
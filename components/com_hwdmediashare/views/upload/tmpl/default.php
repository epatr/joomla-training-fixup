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
// This is a dummy template that is not used.

// Custom fields can be added to any of the other templates in this directory 
// by using the following code. 
?>   
<?php foreach($this->customfields->fields as $group => $fields) : ?>
    <fieldset>
    <?php foreach($fields as $field) : ?>
      <div class="control-group">
        <div class="control-label">
          <?php echo $this->customfields->getLabel($field); ?>
        </div>
        <div class="controls">
          <?php echo $this->customfields->getInput($field); ?>
        </div>
      </div>
    <?php endforeach; ?>
    </fieldset>
<?php endforeach; ?> 
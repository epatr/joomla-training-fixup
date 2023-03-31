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
?>
<div class="hwd-container text-center">
  <div class="media-details-view media-layout-solo">
    <div class="media-item-container">
      <div class="media-item-full" id="media-item-<?php echo $displayData->item->id; ?>">
        <?php echo hwdMediaShareMedia::get($displayData->item); ?>
      </div>
    </div>    
  </div> 
</div>
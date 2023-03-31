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
JHtml::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR . '/helpers/html');

$user = JFactory::getUser();
?>
<table class="category table table-striped table-bordered table-hover">
  <tbody>
    <?php foreach ($displayData->items as $id => $item) : ?>
    <tr>
      <td>       
        <a href="<?php echo JRoute::_(hwdMediaShareHelperRoute::getUserRoute($item->id)); ?>" class="media-member-avatar">
          <?php echo JHtml::_('hwdimage.avatar', $item->id, 50); ?></a>
          <?php echo $item->username ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

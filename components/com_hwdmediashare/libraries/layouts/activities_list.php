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
JHtml::addIncludePath(JPATH_ROOT . '/administrator/components/com_hwdmediashare/helpers/html');
?>
<ul class="media-activity-list">
<?php foreach ($displayData->items as $id => $item) : ?>
  <li class="media-activity-item">
    <?php if ($displayData->params->get('community_avatar') != 'none') :?>
      <a href="<?php echo JRoute::_(hwdMediaShareHelperRoute::getUserRoute($item->actor)); ?>" class="media-activity-avatar">
        <?php echo JHtml::_('hwdimage.avatar', $item->actor, 50); ?>
      </a>
    <?php endif; ?>
    <p class="media-activity-desc"><?php echo hwdMediaShareActivities::renderActivityHtml($item); ?></p>
    <p class="media-activity-date"><?php echo JHtml::_('hwddate.relative', $item->created); ?></p>
  </li>
<?php endforeach; ?>
</ul>

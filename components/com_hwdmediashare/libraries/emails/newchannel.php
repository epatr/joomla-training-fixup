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
<font face="calibri" size="4">
<br>
This is an automated message from <?php echo $app->getCfg('sitename'); ?>. 
<br>
<br>
A new user channel was <?php echo JText::sprintf('COM_HWDMS_CREATED_ON', JHtml::_('date', $row->created, $config->get('list_date_format'))); ?>.<br>
<br>
Channel name: <?php echo $row->title; ?><br>
Description: <?php echo $row->description; ?><br>
<?php echo JText::sprintf('COM_HWDMS_CREATED_BY', $user->username);?><br>
<br>
View this user channel in the front end:<br>
<?php echo $linkFront; ?><br>
<br>
View this user channel in the administrator:<br>
<?php echo $linkAdmin; ?><br>
<br>
<br>
<br>
<i><small>To stop receiving these notifications set notifications to no
in the hwdMediaShare configuration.</small></i>
</font> 

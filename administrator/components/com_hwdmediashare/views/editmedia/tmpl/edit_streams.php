<?php
/**
 * @package     Joomla.administrator
 * @subpackage  Component.hwdmediashare
 *
 * @copyright   Copyright (C) 2013 Highwood Design Limited. All rights reserved.
 * @license     GNU General Public License http://www.gnu.org/copyleft/gpl.html
 * @author      Dave Horsfall
 */

defined('_JEXEC') or die;

jimport('joomla.html.html.number');

$streams = json_decode($this->item->source, false);
?>
<table class="table table-striped" id="filesList">
        <thead>
                <tr>
                        <th width="10%" class="nowrap">
                                <?php echo JText::_('COM_HWDMS_STREAM_TYPE'); ?>
                        </th>
                        <th>
                                <?php echo JText::_('COM_HWDMS_STREAM_URL'); ?>
                        </th>   
                        <th width="10%" class="nowrap hidden-phone">
                                <?php echo JText::_('COM_HWDMS_STREAM_QUALITY'); ?>
                        </th>
                </tr>
        </thead>
        <tbody>
        <?php foreach($streams as $i => $stream): ?>
                <tr class="row<?php echo $i % 2; ?>">
                        <td>
                                <?php echo hwdMediaShareStreaming::getStreamType($stream); ?>
                        </td>
                        <td>
                                <?php echo $stream->url; ?>
                        </td>
                        <td>
                                <?php echo $stream->quality; ?>
                        </td>              
                </tr>
        <?php endforeach; ?>
        </tbody>
</table>
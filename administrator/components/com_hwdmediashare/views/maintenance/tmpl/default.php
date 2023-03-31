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

JHtml::_('behavior.framework', true);
?>
<form action="<?php echo JRoute::_('index.php?option=com_hwdmediashare&view=maintenance'); ?>" method="post" name="adminForm" id="adminForm">
<?php if (!empty($this->sidebar)) : ?>
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
<?php else : ?>
	<div id="j-main-container">
<?php endif;?>
        <table class="table table-striped" id="maintenanceList">
                <thead>
                        <tr>
                                <th>
                                        Maintenance Tasks
                                </th>
                        </tr>
                </thead>        
                <tbody>
                        <tr>
                                <td>
                                        <div id="ajax-container-cleancategorymap" class="pull-right"></div>
                                        <?php echo JText::_('COM_HWDMS_CLEAN_CATEGORY_MAP'); ?>
                                </td>
                        </tr>
                        <tr>
                                <td>
                                        <div id="ajax-container-emptyuploadtokens" class="pull-right"></div>
                                        <?php echo JText::_('COM_HWDMS_EMPTY_UPLOAD_TOKENS'); ?>
                                </td>
                        </tr>
                        <tr>
                                <td>
                                        <div id="ajax-container-purgeoldprocesses" class="pull-right"></div>
                                        <?php echo JText::_('COM_HWDMS_PURGE_OLD_PROCESSES'); ?>
                                </td>
                        </tr>
                        <tr>
                                <td>
                                        <div id="ajax-container-uninstalloldextensions" class="pull-right"></div>
                                        <?php echo JText::_('COM_HWDMS_UNINSTALL_OLD_EXTENSIONS'); ?>
                                </td>
                        </tr>
                        <tr>
                                <td>
                                        <div id="ajax-container-databaseindexoptimisation" class="pull-right"></div>
                                        <?php echo JText::_('COM_HWDMS_DATABASE_INDEX_OPTIMISATION'); ?>
                                </td>
                        </tr>
                        <tr>
                                <td>
                                        <div id="ajax-container-migratelegacytags" class="pull-right"></div>
                                        <?php echo JText::_('COM_HWDMS_MIGRATE_LEGACY_TAGS'); ?>
                                </td>
                        </tr>
                        <tr>
                                <td>
                                        <div id="ajax-container-cleanactivities" class="pull-right"></div>
                                        <?php echo JText::_('COM_HWDMS_CLEAN_ACTIVITIES'); ?>
                                </td>
                        </tr>
                        <tr>
                                <td>
                                        <div id="ajax-container-cleanchannels" class="pull-right"></div>
                                        <?php echo JText::_('COM_HWDMS_CLEAN_CHANNELS'); ?>
                                </td>
                        </tr>
                        <!--
                        <tr>
                                <td>
                                        <div id="ajax-container-cleanstorage" class="pull-right"></div>
                                        <?php echo JText::_('COM_HWDMS_CLEAN_STORAGE'); ?>
                                </td>
                        </tr>
                        -->
                </tbody>
        </table>
        <table class="table table-striped" id="maintenanceList">
                <thead>
                        <tr>
                                <th>
                                        Bulk Maintenance Actions
                                </th>
                        </tr>
                </thead>        
                <tbody>
                        <tr>
                                <td>
                                        <div id="ajax-container-bulkallowcommenting" class="pull-right"></div>
                                        <div id="ajax-container-bulkdenycommenting" class="pull-right"></div>
                                        <p>Change All Media Commenting Options<br /><small>This tool will change the option for commenting in all individual media</small></p>
                                        <div class="btn-group" role="group" aria-label="...">
                                          <button type="button" class="btn btn-default" onclick="Joomla.submitbutton('maintenance.bulkallowcommenting')">Allowed</button>
                                          <button type="button" class="btn btn-default" onclick="Joomla.submitbutton('maintenance.bulkdenycommenting')">Denied</button>
                                        </div>
                                </td>
                        </tr>
                        <tr>
                                <td>
                                        <div id="ajax-container-bulkallowlikes" class="pull-right"></div>
                                        <div id="ajax-container-bulkdenylikes" class="pull-right"></div>
                                        <p>Change All Media Likes Options<br /><small>This tool will change the option for commenting in all individual media</small></p>
                                        <div class="btn-group" role="group" aria-label="...">
                                          <button type="button" class="btn btn-default" onclick="Joomla.submitbutton('maintenance.bulkallowlikes')">Allowed</button>
                                          <button type="button" class="btn btn-default" onclick="Joomla.submitbutton('maintenance.bulkdenylikes')">Denied</button>
                                        </div>
                                </td>
                        </tr>
                        <tr>
                                <td>
                                        <div id="ajax-container-bulkallowembedding" class="pull-right"></div>
                                        <div id="ajax-container-bulkdenyembedding" class="pull-right"></div>
                                        <p>Change All Media Embedding Options<br /><small>This tool will change the option for embedding in all individual media</small></p>
                                        <div class="btn-group" role="group" aria-label="...">
                                          <button type="button" class="btn btn-default" onclick="Joomla.submitbutton('maintenance.bulkallowembedding')">Allowed</button>
                                          <button type="button" class="btn btn-default" onclick="Joomla.submitbutton('maintenance.bulkdenyembedding')">Denied</button>
                                        </div>
                                </td>
                        </tr>
                </tbody>
        </table>            
        <input type="hidden" name="task" value="" />
        <?php echo JHtml::_('form.token'); ?>
        </div>
</form>

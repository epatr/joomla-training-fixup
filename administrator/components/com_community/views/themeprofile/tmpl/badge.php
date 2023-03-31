<?php
/**
 * @copyright (C) 2013 iJoomla, Inc. - All rights reserved.
 * @license GNU General Public License, version 2 (http://www.gnu.org/licenses/gpl-2.0.html)
 * @author iJoomla.com <webmaster@ijoomla.com>
 * @url https://www.jomsocial.com/license-agreement
 * The PHP code portions are distributed under the GPL license. If not otherwise stated, all images, manuals, cascading style sheets, and included JavaScript *are NOT GPL, and are released under the IJOOMLA Proprietary Use License v1.0
 * More info at https://www.jomsocial.com/license-agreement
 */
// Disallow direct access to this file
defined('_JEXEC') or die('Restricted access');
?>


<div class="widget-box">
    <div class="well">
        <strong><?php echo JText::_('COM_COMMUNITY_MULTIPROFILE_NOTE');?></strong>:
        <p><?php echo JText::_('COM_COMMUNITY_THEMEPROFILE_NAME_BADGE_INFO');?></p>
    </div>

    <div class="widget-header widget-header-flat">
        <h5><?php echo JText::_('COM_COMMUNITY_THEMEPROFILE_USER_NAME_BADGE');?></h5>
    </div>
    <div class="widget-body">
        <div class="widget-main">
            <table>
                <?php
                    $groups = JHelperUsergroups::getInstance()->getAll();
                    foreach ($groups as $group) {
                        if (isset($imageExist)) unset($imageExist);
                        
                        $idname = 'profile-group-badge-' . $group->id;
                ?>      
                        <tr>
                            <td width="100"><?php echo $group->title; ?></td>
                            <td width="300">
                            <?php 
                                if(isset($this->settings['profile'][$idname])) {
                                    $image = JURI::root() . 'components/com_community/assets/' . $idname . '-thumb.' . $this->settings['profile'][$idname];
                                    $imagepath = COMMUNITY_PATH_ASSETS . $idname . '.' . $this->settings['profile'][$idname];

                                    if (JFile::exists($imagepath)) {
                                        $imageExist = true;
                                ?> 
                                        <img src="<?php echo $image;?>" class="badgeImage" /><br/>
                                <?php
                                    } else {
                                        echo '<br/>';
                                        unset($this->settings['profile'][$idname]);
                                    }
                                ?>
                            <?php
                                } else {
                                    echo '<br/>';
                                }
                            ?>
                                <input type="file" name="<?php echo $idname; ?>" id="<?php echo $idname; ?>" class="profile-group-badge">
                                <input type="hidden" name="settings[<?php echo $idname; ?>]" value="<?php echo isset($this->settings['profile'][$idname]) ? $this->settings['profile'][$idname] : "";?>" />
                            </td>
                            <td>
                                <?php if (isset($imageExist)) { ?>
                                    &nbsp;&nbsp;
                                    <button type="submit" form="adminForm" value="<?php echo isset($this->settings['profile'][$idname]) ? $this->settings['profile'][$idname] : "";?>" class="btn btn-small button-apply btn-danger" name="remove-<?php echo $idname; ?>" onclick="return confirm('<?php echo JText::_('COM_COMMUNITY_REMOVE_NAME_BADGE_CONFIRM'); ?>');"><?php echo JText::_('COM_COMMUNITY_REMOVE'); ?></button>
                                <?php } ?>
                            </td>
                        </tr>
                <?php } ?>
            </table>
        </div>
    </div>
</div>
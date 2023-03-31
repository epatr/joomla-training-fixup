<?php
/**
* @package      EasySocial
* @copyright    Copyright (C) 2010 - 2016 Stack Ideas Sdn Bhd. All rights reserved.
* @license      GNU/GPL, see LICENSE.php
* EasySocial is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/
defined('_JEXEC') or die('Unauthorized Access');
?>
<div class="row">
    <div class="col-md-6">

        <div class="panel">
            <?php echo $this->html('panel.heading', 'COM_EASYSOCIAL_STORAGEPATH_SETTINGS_HEADER'); ?>


            <div class="panel-body">
                <div class="form-group">
                    <?php echo $this->html('panel.label', 'COM_EASYSOCIAL_STORAGEPATH_SETTINGS_PHOTO_STORAGE_PATH'); ?>

                    <div class="col-md-7">
                        <?php echo $this->html('grid.inputbox', 'photos.storage.container', $this->config->get('photos.storage.container')); ?>
                        <div class="help-block">
                            <?php echo JText::_('COM_EASYSOCIAL_STORAGE_SETTINGS_STORAGE_PATH_INFO'); ?>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <?php echo $this->html('panel.label', 'COM_EASYSOCIAL_STORAGEPATH_SETTINGS_AVATAR_STORAGE_PATH'); ?>

                    <div class="col-md-7">
                        <?php echo $this->html('grid.inputbox', 'avatars.storage.container', $this->config->get('avatars.storage.container')); ?>
                        <div class="help-block">
                            <?php echo JText::_('COM_EASYSOCIAL_STORAGE_SETTINGS_STORAGE_PATH_INFO'); ?>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <?php echo $this->html('panel.label', 'COM_EASYSOCIAL_VIDEOS_SETTINGS_STORAGE_PATH'); ?>

                    <div class="col-md-7">
                        <?php echo $this->html('grid.inputbox', 'video.storage.container', $this->config->get('video.storage.container')); ?>
                        
                        <div class="help-block"><?php echo JText::_('COM_EASYSOCIAL_STORAGE_SETTINGS_STORAGE_PATH_INFO'); ?></div>
                    </div>
                </div>

                <div class="form-group">
                    <?php echo $this->html('panel.label', 'COM_ES_AUDIO_SETTINGS_STORAGE_PATH'); ?>

                    <div class="col-md-7">
                        <?php echo $this->html('grid.inputbox', 'audio.storage.container', $this->config->get('audio.storage.container')); ?>
                        
                        <div class="help-block"><?php echo JText::_('COM_EASYSOCIAL_STORAGE_SETTINGS_STORAGE_PATH_INFO'); ?></div>
                    </div>
                </div>

                <div class="form-group">
                    <?php echo $this->html('panel.label', 'COM_EASYSOCIAL_COMMENTS_SETTINGS_STORAGE'); ?>

                    <div class="col-md-7">
                        <?php echo $this->html('grid.inputbox', 'comments.storage', $this->config->get('comments.storage')); ?>
                        <div class="help-block"><?php echo JText::_('COM_EASYSOCIAL_STORAGE_SETTINGS_STORAGE_PATH_INFO'); ?></div>
                    </div>
                </div>

                <div class="form-group">
                    <?php echo $this->html('panel.label', 'COM_EASYSOCIAL_CONVERSATIONS_SETTINGS_STORAGE_PATH'); ?>

                    <div class="col-md-7">
                        <?php echo $this->html('grid.inputbox', 'conversations.attachments.storage', $this->config->get('conversations.attachments.storage')); ?>
                        <div class="help-block"><?php echo JText::_('COM_EASYSOCIAL_STORAGE_SETTINGS_STORAGE_PATH_INFO'); ?></div>
                    </div>
                </div>


                <div class="form-group">
                    <?php echo $this->html('panel.label', 'COM_EASYSOCIAL_LINKS_SETTINGS_CACHE_LOCATION'); ?>

                    <div class="col-md-7">
                        <?php echo $this->html('grid.inputbox', 'links.cache.location', $this->config->get('links.cache.location')); ?>
                    </div>
                </div>

                <div class="form-group">
                    <?php echo $this->html('panel.label', 'COM_EASYSOCIAL_STORAGEPATH_SETTINGS_FILE_STORAGE_PATH'); ?>

                    <div class="col-md-7">
                        <?php echo $this->html('grid.inputbox', 'files.storage.container', $this->config->get('files.storage.container')); ?>
                        <div class="help-block">
                            <?php echo JText::_('COM_EASYSOCIAL_STORAGE_SETTINGS_STORAGE_PATH_INFO'); ?>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>
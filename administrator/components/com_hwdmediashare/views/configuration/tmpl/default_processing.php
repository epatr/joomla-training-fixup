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

JHtml::addIncludePath(JPATH_COMPONENT.'/helpers/html');
JHtml::_('HwdPopup.alert');
?>
<div class="row-fluid">
        <div class="span6">
                <fieldset class="form-horizontal">
                        <legend><?php echo JText::_('COM_HWDMS_PROCESSING'); ?></legend>
                        <div class="control-group">
                                <div class="control-label"><?php echo $this->form->getLabel('process'); ?></div>
                                <div class="controls"><?php echo $this->form->getInput('process'); ?></div>
                        </div>
                        <div class="alert alert-info">
                                <p>Automatic processing allows media to be processed immediately after upload. This is achieved by requesting the server to perform tasks in the background. Most servers can support this feature. See documentation below for help if your server fails the test.</p>
                                <p><a href="index.php?option=com_hwdmediashare&task=configuration.background&tmpl=component" class="media-popup-alert btn btn-info"><?php echo JText::_('COM_HWDMS_BTN_TEST_AUTO_PROCESS'); ?></a> <a href="http://hwdmediashare.co.uk/blog/231-php-cli-automatic-conversion" class="btn" target="_blank"><?php echo JText::_('COM_HWDMS_BTN_DOCUMENTATION'); ?></a></p>
                        </div>
                        <div class="control-group">
                                <div class="control-label"><?php echo $this->form->getLabel('process_auto'); ?></div>
                                <div class="controls"><?php echo $this->form->getInput('process_auto'); ?></div>
                        </div>
                        <div class="control-group">
                                <div class="control-label"><?php echo $this->form->getLabel('process_warning'); ?></div>
                                <div class="controls"><?php echo $this->form->getInput('process_warning'); ?></div>
                        </div>                        
                        <div class="control-group">
                                <div class="control-label"><?php echo $this->form->getLabel('process_watermark'); ?></div>
                                <div class="controls"><?php echo $this->form->getInput('process_watermark'); ?></div>
                        </div>
                        <div class="control-group">
                                <div class="control-label"><?php echo $this->form->getLabel('watermark_path'); ?></div>
                                <div class="controls"><?php echo $this->form->getInput('watermark_path'); ?></div>
                        </div>                        
                        <div class="control-group">
                                <div class="control-label"><?php echo $this->form->getLabel('watermark_position'); ?></div>
                                <div class="controls"><?php echo $this->form->getInput('watermark_position'); ?></div>
                        </div>                        
                </fieldset>
                <fieldset class="form-horizontal">
                        <legend><?php echo JText::_('COM_HWDMS_IMAGES'); ?></legend>
                        <div class="control-group">
                                <div class="control-label"><?php echo $this->form->getLabel('process_image_quality'); ?></div>
                                <div class="controls"><?php echo $this->form->getInput('process_image_quality'); ?></div>
                        </div>
                        <div class="control-group">
                                <div class="control-label"><?php echo $this->form->getLabel('process_jpeg_75'); ?></div>
                                <div class="controls"><?php echo $this->form->getInput('process_jpeg_75'); ?></div>
                        </div>
                        <div class="control-group">
                                <div class="control-label"><?php echo $this->form->getLabel('process_jpeg_100'); ?></div>
                                <div class="controls"><?php echo $this->form->getInput('process_jpeg_100'); ?></div>
                        </div>
                        <div class="control-group">
                                <div class="control-label"><?php echo $this->form->getLabel('process_jpeg_240'); ?></div>
                                <div class="controls"><?php echo $this->form->getInput('process_jpeg_240'); ?></div>
                        </div>                        
                        <div class="control-group">
                                <div class="control-label"><?php echo $this->form->getLabel('process_jpeg_500'); ?></div>
                                <div class="controls"><?php echo $this->form->getInput('process_jpeg_500'); ?></div>
                        </div>    
                        <div class="control-group">
                                <div class="control-label"><?php echo $this->form->getLabel('process_jpeg_640'); ?></div>
                                <div class="controls"><?php echo $this->form->getInput('process_jpeg_640'); ?></div>
                        </div>  
                        <div class="control-group">
                                <div class="control-label"><?php echo $this->form->getLabel('process_jpeg_1024'); ?></div>
                                <div class="controls"><?php echo $this->form->getInput('process_jpeg_1024'); ?></div>
                        </div>  
                        <div class="control-group">
                                <div class="control-label"><?php echo $this->form->getLabel('optimise_jpeg'); ?></div>
                                <div class="controls"><?php echo $this->form->getInput('optimise_jpeg'); ?></div>
                        </div>  
                </fieldset>
                <fieldset class="form-horizontal">
                        <legend><?php echo JText::_('COM_HWDMS_AUDIO'); ?></legend>
                        <div class="control-group">
                                <div class="control-label"><?php echo $this->form->getLabel('process_audio_mp3'); ?></div>
                                <div class="controls"><?php echo $this->form->getInput('process_audio_mp3'); ?></div>
                        </div>
                        <div class="control-group">
                                <div class="control-label"><?php echo $this->form->getLabel('process_audio_ogg'); ?></div>
                                <div class="controls"><?php echo $this->form->getInput('process_audio_ogg'); ?></div>
                        </div>                   
                </fieldset>
        </div>
        <div class="span6">
                <fieldset class="form-horizontal">
                        <legend><?php echo JText::_('COM_HWDMS_VIDEO_SETTINGS'); ?></legend>
                        <div class="control-group">
                                <div class="control-label"><?php echo $this->form->getLabel('process_vpreset'); ?></div>
                                <div class="controls"><?php echo $this->form->getInput('process_vpreset'); ?></div>
                        </div>
                        <div class="control-group">
                                <div class="control-label"><?php echo $this->form->getLabel('metadata_injector'); ?></div>
                                <div class="controls"><?php echo $this->form->getInput('metadata_injector'); ?></div>
                        </div>
                        <div class="control-group">
                                <div class="control-label"><?php echo $this->form->getLabel('process_max_vbitrate_240'); ?></div>
                                <div class="controls"><?php echo $this->form->getInput('process_max_vbitrate_240'); ?></div>
                        </div>
                        <div class="control-group">
                                <div class="control-label"><?php echo $this->form->getLabel('process_max_abitrate_240'); ?></div>
                                <div class="controls"><?php echo $this->form->getInput('process_max_abitrate_240'); ?></div>
                        </div>
                        <div class="control-group">
                                <div class="control-label"><?php echo $this->form->getLabel('process_max_vbitrate_360'); ?></div>
                                <div class="controls"><?php echo $this->form->getInput('process_max_vbitrate_360'); ?></div>
                        </div>
                        <div class="control-group">
                                <div class="control-label"><?php echo $this->form->getLabel('process_max_abitrate_360'); ?></div>
                                <div class="controls"><?php echo $this->form->getInput('process_max_abitrate_360'); ?></div>
                        </div>
                        <div class="control-group">
                                <div class="control-label"><?php echo $this->form->getLabel('process_max_vbitrate_480'); ?></div>
                                <div class="controls"><?php echo $this->form->getInput('process_max_vbitrate_480'); ?></div>
                        </div>
                        <div class="control-group">
                                <div class="control-label"><?php echo $this->form->getLabel('process_max_abitrate_480'); ?></div>
                                <div class="controls"><?php echo $this->form->getInput('process_max_abitrate_480'); ?></div>
                        </div>
                        <div class="control-group">
                                <div class="control-label"><?php echo $this->form->getLabel('process_max_vbitrate_720'); ?></div>
                                <div class="controls"><?php echo $this->form->getInput('process_max_vbitrate_720'); ?></div>
                        </div>
                        <div class="control-group">
                                <div class="control-label"><?php echo $this->form->getLabel('process_max_abitrate_720'); ?></div>
                                <div class="controls"><?php echo $this->form->getInput('process_max_abitrate_720'); ?></div>
                        </div>                        
                        <div class="control-group">
                                <div class="control-label"><?php echo $this->form->getLabel('process_max_vbitrate_1080'); ?></div>
                                <div class="controls"><?php echo $this->form->getInput('process_max_vbitrate_1080'); ?></div>
                        </div>
                        <div class="control-group">
                                <div class="control-label"><?php echo $this->form->getLabel('process_max_abitrate_1080'); ?></div>
                                <div class="controls"><?php echo $this->form->getInput('process_max_abitrate_1080'); ?></div>
                        </div>     
                </fieldset>
                <fieldset class="form-horizontal">
                        <legend><?php echo JText::_('COM_HWDMS_VIDEO'); ?></legend>
                        <div class="control-group">
                                <div class="control-label"><?php echo $this->form->getLabel('process_flv_240'); ?></div>
                                <div class="controls"><?php echo $this->form->getInput('process_flv_240'); ?></div>
                        </div>
                        <div class="control-group">
                                <div class="control-label"><?php echo $this->form->getLabel('process_flv_360'); ?></div>
                                <div class="controls"><?php echo $this->form->getInput('process_flv_360'); ?></div>
                        </div>
                        <div class="control-group">
                                <div class="control-label"><?php echo $this->form->getLabel('process_flv_480'); ?></div>
                                <div class="controls"><?php echo $this->form->getInput('process_flv_480'); ?></div>
                        </div>
                        <div class="control-group">
                                <div class="control-label"><?php echo $this->form->getLabel('process_mp4_360'); ?></div>
                                <div class="controls"><?php echo $this->form->getInput('process_mp4_360'); ?></div>
                        </div>
                        <div class="control-group">
                                <div class="control-label"><?php echo $this->form->getLabel('process_mp4_480'); ?></div>
                                <div class="controls"><?php echo $this->form->getInput('process_mp4_480'); ?></div>
                        </div>
                        <div class="control-group">
                                <div class="control-label"><?php echo $this->form->getLabel('process_mp4_720'); ?></div>
                                <div class="controls"><?php echo $this->form->getInput('process_mp4_720'); ?></div>
                        </div>
                        <div class="control-group">
                                <div class="control-label"><?php echo $this->form->getLabel('process_mp4_1080'); ?></div>
                                <div class="controls"><?php echo $this->form->getInput('process_mp4_1080'); ?></div>
                        </div>
                        <div class="control-group">
                                <div class="control-label"><?php echo $this->form->getLabel('process_webm_360'); ?></div>
                                <div class="controls"><?php echo $this->form->getInput('process_webm_360'); ?></div>
                        </div>
                        <div class="control-group">
                                <div class="control-label"><?php echo $this->form->getLabel('process_webm_480'); ?></div>
                                <div class="controls"><?php echo $this->form->getInput('process_webm_480'); ?></div>
                        </div>
                        <div class="control-group">
                                <div class="control-label"><?php echo $this->form->getLabel('process_webm_720'); ?></div>
                                <div class="controls"><?php echo $this->form->getInput('process_webm_720'); ?></div>
                        </div>                        
                        <div class="control-group">
                                <div class="control-label"><?php echo $this->form->getLabel('process_webm_1080'); ?></div>
                                <div class="controls"><?php echo $this->form->getInput('process_webm_1080'); ?></div>
                        </div>
                        <div class="control-group">
                                <div class="control-label"><?php echo $this->form->getLabel('process_ogg_360'); ?></div>
                                <div class="controls"><?php echo $this->form->getInput('process_ogg_360'); ?></div>
                        </div>     
                        <div class="control-group">
                                <div class="control-label"><?php echo $this->form->getLabel('process_ogg_480'); ?></div>
                                <div class="controls"><?php echo $this->form->getInput('process_ogg_480'); ?></div>
                        </div> 
                        <div class="control-group">
                                <div class="control-label"><?php echo $this->form->getLabel('process_ogg_720'); ?></div>
                                <div class="controls"><?php echo $this->form->getInput('process_ogg_720'); ?></div>
                        </div> 
                        <div class="control-group">
                                <div class="control-label"><?php echo $this->form->getLabel('process_ogg_1080'); ?></div>
                                <div class="controls"><?php echo $this->form->getInput('process_ogg_1080'); ?></div>
                        </div> 
                </fieldset>
        </div>
</div>

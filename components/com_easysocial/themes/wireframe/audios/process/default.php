<?php
/**
* @package      EasySocial
* @copyright    Copyright (C) 2010 - 2017 Stack Ideas Sdn Bhd. All rights reserved.
* @license      GNU/GPL, see LICENSE.php
* EasySocial is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/
defined('_JEXEC') or die('Unauthorized Access');
?>
<?php if ($cluster) { ?>
    <?php echo $this->html('html.miniheader', $cluster); ?>
<?php } ?>

<form action="<?php echo JRoute::_('index.php');?>" method="post" enctype="multipart/form-data">

    <div class="es-container es-audios es-audio-form" data-audio-process data-id="<?php echo $audio->getItem()->id;?>">
        <div class="es-content">
            <div class="es-audios-content-wrapper">

                <div class="es-snackbar">
                    <?php echo JText::_("COM_ES_AUDIO_PROCESSING_AUDIO");?>
                </div>

                <div class="es-audio-progress-area t-lg-mb--sm">
                    <div class="es-progress-wrap">
                        <div class="progress progress-success">
                            <div style="width: 1%" class="bar" data-audio-progress-bar></div>
                        </div>
                        <div class="progress-result" data-audio-progress-result>0%</div>
                    </div>
                    
                    <div class="t-lg-mt--xl t-text--muted"><?php echo JText::_('COM_ES_AUDIO_PROCESSING_AUDIO_DESC');?></div>
                </div>

            </div>

        </div>
    </div>

</form>
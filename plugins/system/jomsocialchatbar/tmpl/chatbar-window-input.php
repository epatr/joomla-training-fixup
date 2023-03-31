<?php defined('_JEXEC') or die; ?>

<div class="joms-chat__input-container">
    <div class="joms-chat__input-wrapper">
        <div v-if="attachment" class="joms-chat__input-preview">
            <img v-if="attachment.type === 'image'" v-bind:src="attachment.url" alt="placeholder" />
            <div v-if="attachment.type === 'file'"><strong>{{ attachment.name }}</strong></div>
            <i class="fa fa-times" v-on:click.prevent.stop="removeAttachment"></i>
        </div>
        <textarea 
            rows="1" 
            placeholder="<?php echo JText::_('COM_COMMUNITY_CHAT_TYPE_YOUR_MESSAGE_HERE'); ?>" 
            v-on:keydown.enter.exact.prevent
            v-on:keyup.enter.exact="submit"
            v-bind:disabled="chat.blocked" ></textarea>
        <div class="joms-chat__input-actions" v-if="!chat.blocked">
            <a href="#" v-on:click.prevent.stop="attachFile">
                <i class="fa fa-file-archive-o"></i>
            </a>
            <a href="#" v-on:click.prevent.stop="attachImage">
                <i class="fa fa-camera"></i>
            </a>
        </div>
    </div>
</div>

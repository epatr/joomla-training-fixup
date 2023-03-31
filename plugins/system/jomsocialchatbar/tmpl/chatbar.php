<?php defined('_JEXEC') or die; ?>

<div id="joms-chatbar" class="joms-chatbar joms-chat__wrapper joms-chat--window" v-bind:class="position">
    <chatbar-sidebar></chatbar-sidebar>
    <div class="joms-chat__windows clearfix">
        <chatbar-window v-for="chat in openedChats"
            v-bind:key="chat.id"
            v-bind:data-id="chat.id"
            v-bind:chat="chat"></chatbar-window>
    </div>
</div>

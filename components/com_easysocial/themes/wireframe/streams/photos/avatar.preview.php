<?php
/**
* @package		EasySocial
* @copyright	Copyright (C) 2010 - 2014 Stack Ideas Sdn Bhd. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* EasySocial is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/
defined( '_JEXEC' ) or die( 'Unauthorized Access' );
?>
<div data-es-photo-group="album:<?php echo $photo->album_id;?>">
	<a class="es-photo-small" <?php echo $this->my->getPrivacy()->validate('photos.view', $photo->id, SOCIAL_TYPE_PHOTO, $photo->user_id) ? 'data-es-photo="' . $photo->id . '"' : ''; ?> href="<?php echo $photo->getPermalink(); ?>">
		<img src="<?php echo $photo->getSource('thumbnail'); ?>" class="es-stream-content-avatar" />
	</a>
</div>

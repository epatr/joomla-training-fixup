<?php
/**
* @package		EasySocial
* @copyright	Copyright (C) 2010 - 2016 Stack Ideas Sdn Bhd. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* EasySocial is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/
defined('_JEXEC') or die('Unauthorized Access');
?>
<?php if ($actor->getType() == SOCIAL_TYPE_PAGE) { ?>
	<?php echo JText::sprintf('APP_PAGE_POLLS_PAGE_NEW_POLLS', $this->html('html.cluster', $actor));?>
<?php } else { ?>
	<?php echo $this->html('html.user', $actor->id); ?> <i class="fa fa-caret-right"></i> <?php echo $this->html('html.cluster', $cluster); ?>
<?php } ?>
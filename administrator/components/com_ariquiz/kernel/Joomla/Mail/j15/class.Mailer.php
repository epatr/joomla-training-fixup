<?php 
/*
 *
 * @package		ARI Framework
 * @author		ARI Soft
 * @copyright	Copyright (c) 2011 www.ari-soft.com. All rights reserved
 * @license		GNU/GPL (http://www.gnu.org/copyleft/gpl.html)
 * 
 */

(defined('_JEXEC') && defined('ARI_FRAMEWORK_LOADED')) or die;

class AriMailer
{
	function send(
		$from, 
		$fromName, 
		$recipient, 
		$subject, 
		$body, 
		$mode = 0, 
		$cc = null, 
		$bcc = null, 
		$attachment = null,
		$replyTo = null, 
		$replyToName = null)
	{
		if ($subject)
			$subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
			
		if (!is_array($recipient))
			$recipient = explode(';', $recipient);
	
		return JUtility::sendMail(
			$from,
			$fromName, 
			$recipient,
			$subject,
			$body, 
			$mode,
			$cc,
			$bcc,
			$attachment,
			$replyTo,
			$replyToName
		);
	}
}
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

class SocialConversation extends EasySocial
{
	public $conversation = null;
	public $message = null;

	public $content = null;

	public $recipients = array();
	public $sender = null;

	public $isReply = null;
	public $retainAttachment = null;

	public $tags = array();
	public $attachments = array();
	public $location = array();
	public $post = null;

	private $saveOptions = array();
	static $defaultSaveOptions = array(
		'triggerPlugins' => true,
		'validateData' => true
	);

	public function __construct($id = null, $options = array())
	{
		parent::__construct();

		// By default the sender would always be the current logged in user.
		$this->sender = $this->my;

		$conversation = ES::table('Conversation');

		if ($id instanceof SocialTableConversation) {
			$conversation = $id;
		} else {
			$conversation->load($id);
		}

		$this->isReply = false;

		// If conversation id is exists, we need to set isReply as true.
		if (isset($conversation->id) && $conversation->id) {
			$this->isReply = true;
		}

		$this->conversation = $conversation;
	}

	/**
	 * Magic method to get properties which don't exist on this object but on the table
	 *
	 * @since   2.0
	 * @access  public
	 */
	public function __get($key)
	{
		if (isset($this->conversation->$key)) {
			return $this->conversation->$key;
		}

		if (isset($this->$key)) {
			return $this->$key;
		}

		return $this->conversation->$key;
	}

	/**
	 * Determines if the current user can upload videos
	 *
	 * @since	2.0
	 * @access	public
	 */
	public function bind($data)
	{
		// Bind users
		$this->setSender($this->my);

		// Bind content
		$message = isset($data['message']) ? $data['message'] : null;
		$this->setContent($data['message']);

		// Bind recipients
		$ids = isset($data['uid']) ? $data['uid'] : null;
		$lists = isset($data['list_id']) ? $data['list_id'] : null;
		$this->setRecipients($ids, $lists);

		$address = isset($data['address']) ? $data['address'] : null;
		$latitude = isset($data['latitude']) ? $data['latitude'] : null;
		$longitude = isset($data['longitude']) ? $data['longitude'] : null;

		$location = '';

		if ($address && $latitude && $longitude) {
			$location = array('address' => $address, 'latitude' => $latitude, 'longitude' => $longitude);
		}

		// Bind locations
		$this->setLocation($location);

		// Bind Tags
		$tags = isset($data['tags']) ? $data['tags'] : null;
		$this->setTags($tags);

		// Bind attachments
		$attachments = isset($data['upload-id']) ? $data['upload-id'] : null;
		$files = array();
		if ($attachments) {
			foreach ($attachments as $attachment) {
				if ($attachment) {
					$files[] = $attachment;
				}
			}
		}
		$this->setAttachments($files);

		// If the conversation already exists, means user are trying to reply to this conversation.
		if (isset($this->conversation->id) && $this->conversation->id) {
			$this->isReply = true;
		}

		$this->deleteTmpAttachments = isset($data['deleteTmpAttachments']) ? $data['deleteTmpAttachments'] : true;

		// Bind the rest of the post data.
		$this->post = $data;
	}

	/**
	 * Sets the sender of this conversation
	 *
	 * @since	1.3
	 * @access	public
	 * @param	string
	 * @return
	 */
	public function setSender(SocialUser $user)
	{
		$this->sender = $user;
	}

	/**
	 * Defines a list of friend lists for this conversations
	 *
	 * @since	1.3
	 * @access	public
	 * @param	string
	 * @return
	 */
	public function setFriendList($lists = array())
	{
		$model = FD::model('Lists');
		$ids = array();

		foreach ($lists as $id) {

			// Get a list of users from the friend list
			$users = $model->getMembers($id, true);

			// Merge the result set
			$ids = array_merge($ids, $users);
		}

		$this->addRecipient($ids);
	}

	/**
	 * Adds a recipient to the recipient list
	 *
	 * @since	2.0
	 * @access	private
	 * @param	string
	 * @return	null
	 */
	public function setRecipients($ids, $lists)
	{
		$recipients = FD::makeArray($ids);

		// Go through each of the list and find the member id's.
		if ($lists) {

			$ids = array();
			$listModel = FD::model('Lists');

			foreach ($lists as $listId) {

				$members = $listModel->getMembers($listId, true, true);

				// Merge the result set.
				$ids = array_merge($ids, $members);
			}

			if ($recipients === false) {
				$recipients = array();
			}

			$recipients = array_merge($ids , $recipients);
			$recipients = array_unique($recipients);
		}

		$this->recipients = $recipients;
	}

	/**
	 * Sets the message
	 *
	 * @since	1.3
	 * @access	public
	 * @param	string
	 * @return
	 */
	public function setContent($content)
	{
		// Normalize CRLF (\r\n) to just LF (\n)
		$this->content = str_ireplace("\r\n", "\n", $content);
	}

	/**
	 * Sets any tags associated with this conversation
	 *
	 * @since	1.3
	 * @access	public
	 * @param	string
	 * @return
	 */
	public function setTags($tags = array())
	{
		$this->tags = $tags;
	}

	/**
	 * Sets location for this conversation
	 *
	 * @since	1.3
	 * @access	public
	 * @param	string
	 * @return
	 */
	public function setLocation($location)
	{
		$this->location = $location;
	}

	/**
	 * Sets any attachments
	 *
	 * @since	1.3
	 * @access	public
	 * @param	string
	 * @return
	 */
	public function setAttachments($attachments = array())
	{
		$this->attachments = $attachments;
	}

	public function isReply()
	{
		return $this->isReply;
	}

	/**
	 * Sends a new message
	 *
	 * @since	1.3
	 * @access	public
	 * @param	string
	 * @return
	 */
	public function save($options = array())
	{
		// Get any save options if available
		$options = array_merge(array(), self::$defaultSaveOptions, $options);

		$this->saveOptions = $options;

		// Perform validation here.
		$state = $this->validate();

		if (!$state) {
			return false;
		}

		// Trigger plugins
		$this->triggerBeforeSave();

		// send the conversation
		$this->send();

		// process reply if necessary
		$this->saveReply();

		// Process Tags
		$this->saveTags();

		// Process Attachments
		$this->saveAttachments();

		// Process Locations
		$this->saveLocation();

		// Trigger plugins
		$this->triggerAfterSave();

		// Notify participants for the new message
		$this->notify();
		$this->replyNotify();

		return true;
	}

	/**
	 * Validate conversations
	 *
	 * @since	2.0
	 * @access	public
	 * @param	string
	 * @return
	 */
	public function validate()
	{
		if (!$this->saveOptions['validateData']) {
			return true;
		}

		// Validate reciepients
		if (!$this->validateReciepients()) {
			return false;
		}

		// Validate content
		if (!$this->validateContent()) {
			return false;
		}

		// Validate limit
		if ($this->limitExceeded()) {
			return false;
		}

		return true;
	}

	/**
	 * Validate Reciepients
	 *
	 * @since	2.0
	 * @access	public
	 * @param	string
	 * @return
	 */
	public function validateReciepients()
	{
		// If this is reply, we do not need to validate the recipients
		if ($this->isReply()) {
			return true;
		}

		if (empty($this->recipients)) {
			return false;
		}

		// Filter recipients and ensure all the user id's are proper!
		$total = count($this->recipients);

		// Go through all the recipient and make sure that they are valid.
		for ($i = 0; $i < $total; $i++) {
			$userId = $this->recipients[$i];
			$user = ES::user($userId);

			if (!$user || empty($userId)) {
				unset($this->recipients[$i]);
			}
		}

		// After processing the recipients list, and no longer has any recipients, stop the user.
		if (!$this->recipients) {
			$this->setError(JText::_('COM_EASYSOCIAL_CONVERSATIONS_ERROR_EMPTY_RECIPIENTS'));
			return false;
		}

		// Check if the creator is allowed to send a message to the target
		$privacy = $this->my->getPrivacy();

		// Ensure that the recipients is not only itself.
		foreach ($this->recipients as $recipient) {

			// When user tries to enter it's own id, we should just break out of this function.
			if ($recipient == $this->my->id) {
				$this->setError(JText::_('COM_EASYSOCIAL_CONVERSATIONS_ERROR_CANNOT_SEND_TO_SELF'));
				return false;
			}

			// Check if the creator is allowed
			if (!$privacy->validate( 'profiles.post.message', $recipient, SOCIAL_TYPE_USER)) {
				$this->setError(JText::_('COM_EASYSOCIAL_CONVERSATIONS_ERROR_CANNOT_SEND_TO_USER_DUE_TO_PRIVACY'));
				return false;
			}
		}

		// Check for the recipients again
		if (empty($this->recipients)) {
			return false;
		}

		return true;
	}

	/**
	 * Validate message
	 *
	 * @since	2.0
	 * @access	public
	 */
	public function validateContent()
	{
		// Get the message that is being posted.
		$msg = $this->input->get('message', '', 'raw');

		// Normalize CRLF (\r\n) to just LF (\n)
		$msg = str_ireplace("\r\n", "\n", $msg);

		// Message should not be empty.
		if (empty($msg) && !$this->attachments) {
			$this->setError(JText::_('COM_EASYSOCIAL_CONVERSATIONS_ERROR_EMPTY_MESSAGE'), ES_ERROR);
			return false;
		}

		return true;
	}

	/**
	 * logic to save a conversation reply.
	 *
	 * @since	2.0
	 * @access	public
	 */
	public function saveReply()
	{
		// Do not proceed if this is not reply.
		if (!$this->isReply()) {
			return;
		}

		// Load the conversation model.
		$model = ES::model('Conversations');

		// Let's try to store the message now.
		$this->message = $model->addReply($this->id, $this->content, $this->my->id);
	}

	/**
	 * logic to send the conversation
	 *
	 * @since	2.0
	 * @access	public
	 * @param	string
	 * @return
	 */
	public function send()
	{
		// Do not proceed if this is a reply.
		if ($this->isReply()) {
			return;
		}

		// Get the conversation table.
		$conversation = ES::table('Conversation');

		// Determine the type of message this is by the number of recipients.
		$type = count($this->recipients) > 1 ? SOCIAL_CONVERSATION_MULTIPLE : SOCIAL_CONVERSATION_SINGLE;

		// For single recipients, we try to reuse back previous conversations
		// so that it will be like a long chat of history.
		if ($type == SOCIAL_CONVERSATION_SINGLE) {
			// We know that the $recipients[0] is always the target user.
			$state = $conversation->loadByRelation($this->my->id, $this->recipients[0], SOCIAL_CONVERSATION_SINGLE);
		}

		// @points: conversation.create.group
		// Assign points when user starts new group conversation
		if (count($this->recipients) > 1) {
			$points = ES::points();
			$points->assign('conversation.create.group', 'com_easysocial', $this->my->id);
		}

		// Set the conversation creator.
		$conversation->created_by = $this->my->id;
		$conversation->lastreplied = ES::date()->toMySQL();
		$conversation->type = $type;

		// Let's try to create the conversation now.
		$state = $conversation->store();

		// If there's an error storing the conversation, break.
		if (!$state) {
			$this->setError(JText::_($conversation->getError()), ES_ERROR);
			return false;
		}

		// Store conversation message
		$message = ES::table('ConversationMessage');

		$message->bind($this->post);

		$message->message = $this->content;
		$message->conversation_id = $conversation->id;
		$message->type = SOCIAL_CONVERSATION_TYPE_MESSAGE;
		$message->created = ES::date()->toMySQL();
		$message->created_by = $this->my->id;

		// Try to store the message now.
		$state = $message->store();

		if (!$state) {
			$view->setMessage(JText::_($message->getError()), ES_ERROR);
			return $view->call(__FUNCTION__);
		}

		// Add users to the message maps.
		array_unshift($this->recipients, $this->my->id);

		$model = FD::model('Conversations');

		// Add the recipient as a participant of this conversation.
		$model->addParticipants($conversation->id, $this->recipients);

		// Add the message maps so that the recipient can view the message
		$model->addMessageMaps($conversation->id, $message->id, $this->recipients, $this->my->id);

		// bind conversation maps
		$this->message = $message;

		// bind conversation
		$this->conversation = $conversation;
	}

	/**
	 * Determines if the current user can upload videos
	 *
	 * @since	2.0
	 * @access	public
	 * @param	string
	 * @return
	 */
	public function saveAttachments()
	{
		// Process attachments here.
		$attachments = $this->attachments;

		// Determine if we should delete the tmp files or not.
		// We must not delete the tmp file during mass conversations.
		$deleteSource = $this->deleteTmpAttachments;

		if ($this->config->get('conversations.attachments.enabled') && $attachments) {

			// If there are attachments, store them appropriately
			$this->message->bindTemporaryFiles($attachments, $deleteSource);
		}
	}

	/**
	 * Determines if the current user can upload videos
	 *
	 * @since	2.0
	 * @access	public
	 * @param	string
	 * @return
	 */
	public function saveLocation()
	{
		// Bind message location if necessary.
		if ($this->config->get('conversations.location') && $this->location) {

			// $address = $this->input->get('address', '', 'default');
			// $latitude = $this->input->get('latitude', '', 'default');
			// $longitude = $this->input->get('longitude', '', 'default');

			$location = $this->location;

			if (is_array($location)) {
				$location = ES::makeObject($location);
			}

			if ($location->address && $location->latitude && $location->longitude) {

				$location = FD::table('Location');
				$location->loadByType($this->message->id, SOCIAL_TYPE_CONVERSATIONS, $this->my->id);

				$location->address = $location->address;
				$location->latitude = $location->latitude;
				$location->longitude = $location->longitude;
				$location->user_id = $this->my->id;
				$location->type = SOCIAL_TYPE_CONVERSATIONS;
				$location->uid = $this->message->id;

				$state = $location->store();
			}
		}
	}

	/**
	 * Process Tags in Conversations
	 *
	 * @since	2.0
	 * @access	public
	 * @param	string
	 * @return
	 */
	public function saveTags()
	{
		// Once the message is saved, we need to process any tags
		// $tags = $this->input->get('tags', array(), 'array');
		$tags = $this->tags;

		if ($tags) {

			foreach ($tags as $raw) {
				$object = FD::makeObject($raw);

				// var_dump($object);

				$tag = FD::table('Tag');
				$tag->offset = $object->start;
				$tag->length = $object->length;
				$tag->type = $object->type;

				if ($object->length <= 1) {
					// propably an invalid data. skip this item.
					continue;
				}

				if ($tag->type == 'hashtag') {

					if (!$object->value) {
						// propably an invalid data. skip this item.
						continue;
					}

					$tag->title = $object->value;
				}

				if ($tag->type == 'entity') {
					if (strpos($object->value, ':') === false) {
						// propably an invalid data. skip this item.
						continue;
					}

					list($entityType, $entityId) = explode(':', $object->value);
					$tag->item_id = $entityId;
					$tag->item_type = $entityType;
				}

				$tag->creator_id = $this->my->id;
				$tag->creator_type = SOCIAL_TYPE_USER;

				$tag->target_id = $this->message->id;
				$tag->target_type = 'conversations';

				// var_dump($tag);exit;

				$tag->store();
			}
		}
	}

	/**
	 * Upload title for conversation
	 * @since	2.0
	 * @access	public
	 * @param	string
	 * @return
	 */
	public function saveTitle($title)
	{
		$conversationTable = ES::table('Conversation');
		$conversationTable->load($this->id);

		// update the conversation title.
		$conversationTable->title = $title;
		$state = $conversationTable->store();

		if ($state) {
			$this->conversation->title = $title;
		}

		return $state;
	}


	/**
	 * Sends a notification to the recipients
	 *
	 * @since	2.0
	 * @access	public
	 * @param	string
	 * @return
	 */
	public function notify()
	{
		if ($this->isReply()) {
			return;
		}

		// Send notification email to recipients
		foreach ($this->recipients as $recipientId) {

			// We should not send a notification to ourself.
			if ($recipientId == $this->my->id) {
				continue;
			}

			$recipient = ES::user($recipientId);

			// Add new notification item
			$mailParams = array();

			$mailParams['title'] = 'COM_EASYSOCIAL_EMAILS_NEW_CONVERSATION_SUBJECT';
			$mailparams['actor'] = $this->my->getName();
			$mailParams['authorName'] = $this->my->getName();
			$mailParams['authorAvatar']	= $this->my->getAvatar();
			$mailParams['authorLink'] = $this->my->getPermalink(true, true);
			$mailParams['message'] = $this->message->getContents();
			$mailParams['messageDate'] = $this->message->created;
			$mailParams['conversationLink']	= $this->getPermalink(true, true);

			// Send a notification for all participants in this thread.
			$state = ES::notify('conversations.new', array($recipientId) , $mailParams, false );
		}
	}

	/**
	 * Process Reply notifications
	 *
	 * @since	2.0
	 * @access	public
	 * @param	string
	 * @return
	 */
	public function replyNotify()
	{
		if (!$this->isReply()) {
			return;
		}

		// Get recipients of this conversation.
		$recipients = $this->getParticipants(array($this->my->id), true);

		foreach ($recipients as $recipient) {

			if ( !$recipient->hasCommunityAccess()) {
				// skip sending email notification to this ESAD user.
				continue;
			}

			if ($recipient->isOnline()) {
				// skip sending email notification if user is online.
				continue;
			}

			// Add new notification item
			$title = 'COM_EASYSOCIAL_EMAILS_NEW_REPLY_RECEIVED_SUBJECT';
			$mailParams = array();

			$mailParams['title'] = $title;
			$mailParams['actor'] = $this->my->getName();
			$mailParams['name'] = $recipient->getName();
			$mailParams['authorName'] = $this->my->getName();
			$mailParams['authorAvatar'] = $this->my->getAvatar();
			$mailParams['authorLink'] = $this->my->getPermalink(true, true);
			$mailParams['message'] = $this->message->message;
			$mailParams['messageDate'] = $this->message->created;
			$mailParams['conversationLink']	= $this->getPermalink(true, true);

			// Send a notification for all participants in this thread.
			ES::notify('conversations.reply', array($recipient), $mailParams, false);
		}
	}

	/**
	 * Determines if the current user compose a conversations
	 *
	 * @since	2.0
	 * @access	public
	 * @param	string
	 * @return
	 */
	public function canCreate()
	{
		// Check if user is allowed to create new conversations
		$access = ES::access();

		// Do not proceed if feature is disabled
		if (!$this->config->get('conversations.enabled')) {
			return false;
		}

		if ($this->my->isSiteAdmin()) {
			return true;
		}

		if (!$access->allowed('conversations.create')) {
			return false;
		}

		if ($this->limitExceeded()) {
			return false;
		}

		return true;
	}

	/**
	 * Determines if the current user can reply to this conversation
	 *
	 * @since	2.0
	 * @access	public
	 * @param	string
	 * @return
	 */
	public function canReply()
	{
		// Get a list of participants for this particular conversation except myself.
		$participants = $this->getParticipants();

		// this flag is to indicate if there is only one participant and the participant is a ESAD.
		$isESADuser = false;

		if (count($participants) == 2) {
			foreach($participants as $pUser) {
				if ($pUser->id != $this->my->id && !$pUser->hasCommunityAccess()) {
					$isESADuser = true;
				}
			}
		}

		// ESAD user does not have access here.
		if ($isESADuser) {
			return false;
		}

		// Ensure that user are actually particapte in the conversation.
		if (!$this->isWritable()) {
			return false;
		}

		if ($this->limitExceeded()) {
			return false;
		}

		return true;
	}


	/**
	 * Determines if the current user can leave from to this conversation
	 *
	 * @since	2.0
	 * @access	public
	 * @param	string
	 * @return
	 */
	public function canLeave($userId = null)
	{
		if ($this->isParticipant($userId) && $this->isWritable($userId)){
			return true;
		}

		return false;
	}

	/**
	 * Determines if the current user has exceed conversation limit
	 *
	 * @since	2.0
	 * @access	public
	 * @param	string
	 * @return
	 */
	public function limitExceeded()
	{
		$access = ES::access();

		// if this is an admin, always allow.
		if ($this->my->isSiteAdmin()) {
			return false;
		}

		// Get the total number of message sent in a day
		$model = FD::model('Conversations');
		$totalSent = $model->getTotalSentDaily($this->my->id);


		// We need to calculate the amount of users they are sending to
		if (!empty($this->recipients)) {
			$totalSent = $totalSent + count($this->recipients);
		}

		if ($access->exceeded('conversations.send.daily', $totalSent)) {
			$this->setError(JText::_( 'COM_EASYSOCIAL_CONVERSATIONS_ERROR_EXCEEDED_SENDING_LIMIT'), ES_ERROR);
			return true;
		}

		return false;
	}

	/**
	 * Determines if the current node really has access to the specific conversation
	 *
	 * @since	2.0
	 * @access	public
	 * @param	int			The current viewer's id.
	 * @return	boolean		True if the user has access to the conversation. False otherwise
	 */
	public function hasAccess($userId = null)
	{
		if (!$userId) {
			$userId = $this->my->id;
		}

		$model = ES::model('Conversations');

		return $model->hasAccess($this->id, $userId);
	}

	/**
	 * Override the parent's delete method as we don't really delete the conversation
	 * only when there's no one left in the system, we should delete the conversation.
	 *
	 * @param	int		The nodeid that requested this deletion.
	 * @param
	 * @param	boolean	True on success, false otherwise
	 */
	public function delete($userId = null)
	{
		if (!$userId) {
			$userId = $this->my->id;
		}

		// Delete all message map for this particular node
		$model = ES::model('Conversations');
		$state = $model->delete($this->id, $userId);

		if (!$state) {
			$this->setError($model->getError());
		}

		return $state;
	}

	/**
	 * Unarchives the entire conversation for specific node.
	 *
	 * @since	2.0
	 * @access	public
	 * @param	int		$userId		The user's id that requested to unarchive the conversation.
	 *
	 * @param	boolean	True on success, false otherwise
	 */
	public function unarchive($userId = null)
	{
		if (!$userId) {
			$userId = $this->my->id;
		}

		$model = ES::model('Conversations');

		return $model->unarchive($this->id, $userId);
	}

	/**
	 * Archives the entire conversation for specific node.
	 *
	 * @since	2.0
	 * @access	public
	 * @param	int		$userId		The nodeid that requested this deletion.
	 * @param	boolean	True on success, false otherwise
	 */
	public function archive($userId = null)
	{
		if (!$userId) {
			$userId = $this->my->id;
		}

		$model = ES::model('Conversations');

		$state = $model->archive($this->id, $userId);

		return $state;
	}

	 /**
	 * mark a conversation as read
	 *
	 * @since   2.0
	 * @access  public
	 */
	 public function markAsRead($userId, $conversationId = null)
	 {
		if ($userId) {
			$userId = $this->my->id;
		}

		$id = 0;
		if ($conversationId) {

			$id = $conversationId;
		} else {
			$id = $this->id;
		}

		$model = ES::model('Conversations');
		return $model->markAsRead($id, $userId);
	 }

	/**
	 * Mark a particular conversation to new.
	 *
	 * @since	1.0
	 * @access	public
	 * @param	int			$userId		The user's id.
	 * @return	boolean
	 */
	public function markAsUnread($userId)
	{
		if ($userId) {
			$userId = $this->my->id;
		}

		$model = ES::model('Conversations');

		return $model->markAsUnread($this->id, $userId);
	}

	/**
	 * Responsible to make a user leave the conversation.
	 * No deletion should occur unless there's no more participants at all.
	 *
	 * @since	1.0
	 * @access	public
	 * @param	int		The user's id that request to leave the conversation.
	 * @return	bool	True on success, false otherwise.
	 */
	public function leave($userId)
	{
		if (!$userId) {
			$userId = $this->my->id;
		}

		$user = ES::user($userId);

		$model = ES::model('Conversations');
		$state = $model->leave($this->id, $userId);

		if (!$state) {
			$this->setError($model->getError());
		}

		// @badge: conversation.leave
		$badge = ES::badges();
		$badge->log('com_easysocial', 'conversation.leave', $userId, JText::_('COM_EASYSOCIAL_CONVERSATIONS_BADGE_LEFT_A_CONVERSATION'));

		// Now we need to send notification to existing participants
		$participants = $this->getParticipants(array($user->id));

		if ($participants) {

			foreach ($participants as $participant) {
				$title = 'COM_EASYSOCIAL_EMAILS_USER_LEFT_CONVERSATION_SUBJECT';

				// Add new notification item
				$mailParams = ES::registry();
				$mailParams->set('actor', $user->getName());
				$mailParams->set('name', $participant->getName());
				$mailParams->set('authorName', $user->getName());
				$mailParams->set('authorAvatar', $user->getAvatar());
				$mailParams->set('authorLink', $user->getPermalink(true, true));
				$mailParams->set('conversationLink', $this->getPermalink(true, true));

				// Send a notification for all participants in this thread.
				$state = ES::notify('conversations.leave', array($participant->id), array('title' => $title, 'params' => $mailParams), false);
			}
		}

		return $state;
	}

	/**
	 * Determines if the current user is a participant in the current conversation.
	 *
	 * @param	int			The user's id.
	 *
	 * @return	boolean		True if participant, false otherwise.
	 */
	public function isParticipant($userId = null)
	{
		$user = ES::user($userId);

		$model = ES::model('Conversations');

		$isParticipant = $model->isParticipant($this->id, $user->id);

		return $isParticipant;
	}

	/**
	 * Adds a participant into an existing conversation
	 *
	 * @since	1.0
	 * @access	public
	 */
	public function addParticipant($created_by, $userId)
	{
		// Create a new participant record.
		$participant = ES::table('ConversationParticipant');

		// Try to load and see if the participant has already been added to the system.
		$participant->load(array('user_id' => $userId, 'conversation_id' => $this->id));

		$participant->conversation_id = $this->id;
		$participant->user_id = $userId;
		$participant->state = SOCIAL_STATE_PUBLISHED;

		// Try to save the participant
		$state = $participant->store();

		if (!$state) {
			$this->setError($participant->getError());
			return $state;
		}

		// @badge: conversation.invite
		$badge = ES::badges();
		$badge->log( 'com_easysocial', 'conversation.invite', $created_by, JText::_('COM_EASYSOCIAL_CONVERSATIONS_BADGE_INVITED_USER_TO_CONVERSATION'));

		// @points: conversation.invite
		// Assign points when user starts new conversation
		$points = ES::points();
		$points->assign('conversation.invite', 'com_easysocial', $created_by);

		// Once the participant is created, we need to create a
		// a new conversation message with the type of join so that others would know
		// that a new user is added to the conversation.
		$message = ES::table('ConversationMessage');
		$message->conversation_id = $this->id;
		$message->message = $userId;
		$message->type = SOCIAL_CONVERSATION_TYPE_JOIN;
		$message->created_by = $created_by;

		// Try to store the new message
		$state = $message->store();

		if (!$state) {
			$this->setError($message->getError());

			return $state;
		}

		// Get conversation model
		$model = ES::model('Conversations');

		// Get existing participants.
		$participants = $model->getParticipants($this->id);

		// Finally, we need to add the message maps
		$model->addMessageMaps($this->id, $message->id, $participants, $created_by);

		return true;
	}

	/**
	 * Retrieves a list of participants in this conversation.
	 *
	 * @since	1.0
	 * @access	public
	 */
	public function getParticipants($exclusions = array(), $use4send = false)
	{
		static $_cache = array();

		// If the "exclusion" is null we assume that they are referring to the current viewer.
		if (empty($exclusions)) {
			$exclusions = $this->my->id;
		}

		$key = $this->id . '_';

		if (is_array($exclusions)) {
			$key .= implode('_', $exclusions);
		} else {
			$key .= $exclusions;
		}

		if (!isset($_cache[$key])) {

			$model = ES::model('Conversations');
			$result	= $model->getParticipants($this->id, $exclusions, false, $use4send);

			if (!$result) {
				$creator = ES::user($this->created_by);
				$result = array($creator);
			}

			$_cache[$key] = $result;
		}

		return $_cache[$key];
	}

	/**
	 * Determines if the current conversation is readable by the given user id.
	 *
	 * @since	1.0
	 * @access	public
	 * @param	int	$userId		The user's id.
	 * @return	boolean			True if it's new, false otherwise.
	 */
	public function isReadable($userId)
	{
		$participant = ES::table('ConversationParticipant');
		$state = $participant->load(array('conversation_id' => $this->id, 'user_id' => $userId));

		if (!$state) {
			return false;
		}

		// If there's a participant record, it's definitely readable.
		return true;
	}

	/**
	 * Centralized method to retrieve a conversation's link.
	 * This is where all the magic happens.
	 *
	 * @access	public
	 * @param	null
	 *
	 * @return	string	The url for the person
	 */
	public function getPermalink($xhtml = true, $external = false, $sef = true)
	{
		$url = ESR::conversations(array('id' => $this->id, 'external' => $external, 'sef' => $sef), $xhtml);

		return $url;
	}

	/**
	 * Determines if the current conversation has been read.
	 *
	 * @since	1.0
	 * @access	public
	 * @param	int	$userId		The user's id.
	 * @return	boolean			True if it's new, false otherwise.
	 */
	public function isNew($userId = null)
	{
		$user = FD::user($userId);
		$model = FD::model('Conversations');

		return $model->isNew($this->id, $user->id);
	}

	/**
	 * Get the participant user id's.
	 *
	 * @since	1.0
	 * @access	public
	 */
	public function getParticipantsId($exclusions = array())
	{
		$participants = $this->getParticipants($exclusions);

		$ids = array();

		foreach ($participants as $participant) {
			$ids[] = $participant->id;
		}

		return $ids;
	}

	/**
	 * Get's the participant's avatar.
	 *
	 * @since	1.0
	 * @access	public
	 */
	public function getParticipantAvatar($exclusions = array(), $avatarSize = SOCIAL_AVATAR_SMALL)
	{
		$model = ES::model('Conversations');
		$user = $model->getParticipants($this->id, $exclusions);

		return $user[0]->getAvatar($avatarSize);
	}

	/**
	 * Retrieves the last replied date
	 *
	 * @since	2.0
	 * @access	public
	 */
	public function getLastRepliedDate($lapsed = false)
	{
		$date = ES::date($this->conversation->lastreplied);

		if ($lapsed) {
			return $date->toLapsed();
		}

		return $date;
	}

	/**
	 * Gets the last replier from this discussion.
	 *
	 * @since	1.0
	 * @access	public
	 * @param	array 	$exclusions		A list of user id's that should be excluded.
	 * @return 	SocialUser 				A SocialUser object.
	 */
	public function getLastParticipant($exclusions = array())
	{
		$model = ES::model('Conversations');
		$participants = $model->getParticipants($this->id, $exclusions);

		if (!is_array($participants)) {
			return $participants;
		}

		if (count($participants) <= 0) {
			return false;
		}

		// Only return the first participant
		return $participants[0];
	}

	/**
	 * Retrieves the last message for this specific conversation.
	 *
	 * @since	1.0
	 * @access	public
	 * @param	int 	The current viewer's id.
	 * @return	SocialTableConversationMessage
	 */
	public function getLastMessage($userId = null)
	{
		static $messages = array();

		if (is_null($userId)) {
			$userId = $this->my->id;
		}

		$key = $this->id . $userId;

		if (!isset($messages[$key])) {
			$model = ES::model('Conversations');
			$messages[$key] = $model->getLastMessage($this->id, $userId);
		}

		return $messages[$key];
	}

	/**
	 * Retrieves the last message type
	 *
	 * @since	2.0
	 * @access	public
	 */
	public function getLastMessageType($userId = null)
	{
		if (is_null($userId)) {
			$userId = $this->my->id;
		}

		$message = $this->getLastMessage($userId);

		return $message->type;
	}

	/**
	 * Determines if the current conversation contains any attachments
	 * @param	null
	 * @return	boolean		True if contain attachments, false otherwise
	 */
	public function hasAttachments()
	{
		$model = ES::model('Conversations');

		return $model->hasAttachments($this->id);
	}

	/**
	 * Determines if the current conversation is writable by the given user id.
	 *
	 * @since	1.0
	 * @access	public
	 * @param	int	$userId		The user's id.
	 * @return	boolean			True if it's new, false otherwise.
	 */
	public function isWritable($userId = null)
	{
		static $writable = array();

		if ($userId == null) {
			$userId = ES::user()->id;
		}

		if (!isset($writable[$userId])) {
			$participant = ES::table('ConversationParticipant');
			$participant->load( array('conversation_id' => $this->id, 'user_id' => $userId));

			// Default value.
			$writable[$userId] = false;

			// Check if the state is still participating.
			if ($participant->state == SOCIAL_CONVERSATION_STATE_PARTICIPATING) {
				$writable[$userId] = true;
			}
		}

		return $writable[$userId];
	}

	/**
	 * Determines if the current conversation is archived or not.
	 *
	 * @since	1.0
	 * @access	public
	 * @param	int 	$userId
	 * @return	boolean
	 */
	public function isArchived($userId = null)
	{
		static $archives = array();

		$user = FD::user($userId);
		$userId = $user->id;

		if (!isset($archives[$this->id . $userId])) {
			$model = ES::model('Conversations');

			$archives[$this->id . $userId] = $model->isArchived($this->id, $userId);
		}

		return $archives[$this->id . $userId];
	}

	/**
	 * Determines if the current conversation is multiple recipients or not.
	 *
	 * @return	boolean
	 */
	public function isMultiple()
	{
		return $this->type == SOCIAL_CONVERSATION_MULTIPLE;
	}

	/**
	 * Determines if the current conversation is multiple recipients or not.
	 *
	 * @return	boolean
	 */
	public function isSingle()
	{
		return $this->type == SOCIAL_CONVERSATION_SINGLE;
	}

	/**
	 * Retrieves a list of messages in a particular conversation
	 *
	 * @since	1.5
	 * @access	public
	 */
	public function getMessages($options = array())
	{
		$model = ES::model("Conversations");

		$messages = $model->getMessages($this->id, $this->my->id, $options);
		return $messages;
	}

	/**
	 * Retrieves a list of messages in a particular conversation
	 *
	 * @since	1.5
	 * @access	public
	 */
	public function getMessagesHtml($options = array())
	{
		$showPagination = isset($options['pagination']) ? $options['pagination'] : false;

		$model = ES::model("Conversations");

		// lets reset the pagination 1st.
		$model->resetPagination();

		$messages = $model->getMessages($this->id, $this->my->id, $options);
		$pagination = $model->getPagination();

		$contents = '';

		if ($messages) {
			foreach($messages as $message) {
				$theme = ES::themes();
				$theme->set('conversation', $this);
				$theme->set('message', $message);
				$contents .= $theme->output('site/conversations/message/default');
			}
		}

		if ($showPagination) {

			if ($pagination->pagesTotal > $pagination->pagesCurrent) {
				$nextlimit = $pagination->pagesCurrent * $pagination->limit;

				$theme = ES::themes();
				$theme->set('nextlimit', $nextlimit);
				$theme->set('id', $this->id);
				$paginationHtml = $theme->output('site/conversations/message/pagination');

				// prepand to contents.
				$contents = $paginationHtml . $contents;
			}
		}

		return $contents;
	}


	/**
	 * Retrieve conversation title
	 *
	 * @since	2.0
	 * @access	public
	 */
	public function getTitle($initUpper = true, $allLowerCase = false)
	{
		$title = '';

		if ($this->title) {
			$title = $this->title;
		} else {
			// the title basically are the participants names
			$names = $this->getParticipants(null);
			$title = ES::string()->namesToStream($names, false, 3);
		}

		if ($allLowerCase) {
			$title = JString::strtolower($title);
		}

		return $title;
	}

	/**
	 * Retrieve conversation avatar
	 *
	 * @since	2.0
	 * @access	public
	 */
	public function getAvatar($isHtml = true)
	{
		static $_cache = array();

		$idx = $this->id . (int) $isHtml;

		if (!isset($_cache[$idx])) {
			// the title basically are the participants names
			$users = $this->getParticipants(null);

			// we only want 3 avatars.
			$users = array_slice($users, 0, 3);

			if ($isHtml) {
				$themes = ES::themes();
				$html = $themes->html('avatar.conversation', $users);

				$_cache[$idx] = $html;
			} else {
				// we just want th avatar urls.
				$arr = array();

				foreach ($users as $user) {
					$arr[] = $user->getAvatar();
				}

				$_cache[$idx] = $arr;
			}
		}

		return $_cache[$idx];
	}


	/**
	 * Trigger event before saving process occur
	 *
	 * @since	2.0
	 * @access	public
	 * @param	string
	 * @return
	 */
	public function triggerBeforeSave()
	{
		if (!$this->saveOptions['triggerPlugins']) {
			return true;
		}

		$dispatcher = ES::dispatcher();

		// Set the arguments
		$args = array(&$this);

		// @trigger
		$dispatcher->trigger(SOCIAL_TYPE_USER, 'onConversationBeforeSave', $args);
	}

	/**
	 * Trigger event after saving process occur
	 *
	 * @since	2.0
	 * @access	public
	 * @param	string
	 * @return
	 */
	public function triggerAfterSave()
	{
		if (!$this->saveOptions['triggerPlugins']) {
			return true;
		}

		if (! $this->recipients) {
			// Exclude the current user from being notified
			$exclusion = array($this->my->id);
			$this->recipients = $this->getParticipants($exclusion);
		}

		$dispatcher = ES::dispatcher();

		$recipients = $this->recipients;

		// Set the arguments
		$args = array(&$this, &$recipients);

		// @trigger
		$dispatcher->trigger(SOCIAL_TYPE_USER, 'onConversationAfterSave', $args);
	}

	/**
	 * Generate specific css class for each participant
	 *
	 * @since	2.1
	 * @access	public
	 */
	public function getUserClassName($message)
	{
		$class = '';

		if (!$message instanceof SocialTableConversationMessage) {
			return $class;
		}

		$creator = $message->getCreator();

		if ($creator->id == $this->my->id) {
			$class .= ' is-me';
		}

		// Check for group conversation.
		if ($this->isMultiple()) {
			if ($creator->id == $this->created_by) {
				$class .= ' is-admin';
			}
		}

		return $class;
	}
}
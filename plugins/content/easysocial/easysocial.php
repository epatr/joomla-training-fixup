<?php
/**
* @package		EasySocial
* @copyright	Copyright (C) 2010 - 2017 Stack Ideas Sdn Bhd. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* EasySocial is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/
defined('_JEXEC') or die('Unauthorized Access');

jimport('joomla.filesystem.file');

$file = JPATH_ADMINISTRATOR . '/components/com_easysocial/includes/plugins.php';

if (!JFile::exists($file)) {
	return;
}

require_once($file);
require_once(JPATH_ROOT . '/components/com_content/helpers/route.php');

class PlgContentEasySocial extends EasySocialPlugins
{
	public $group = 'content';
	public $element = 'easysocial';

	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
	}

	/**
	 * Renders EasySocial dependencies
	 *
	 * @since	2.1.0
	 * @access	public
	 */
	public function renderDependencies()
	{
		static $loaded = null;

		if (is_null($loaded)) {
			// We need stuffs from EasySocial library
			ES::initialize();

			// Load front end's language file
			ES::language()->loadSite();

			$loaded = true;
		}
		return $loaded;
	}

	/**
	 * Check the user session for the award points.
	 *
	 * @since	1.2
	 * @access	public
	 */
	public function sessionExists()
	{
		// Get the IP address from the current user
		$ip	= $_SERVER['REMOTE_ADDR'];

		// Check the article item view
		$this->app = JFactory::getApplication();
		$view = $this->app->input->get('view');

		// Get the current article item id
		$itemId = $this->app->input->get('id', 0, 'int');

		if (!empty($ip) && !empty($itemId) && $view == 'article') {

			$token = md5($ip . $itemId);
			$session = JFactory::getSession();
			$exists	= $session->get($token , false);

			// If the session existed return true
			if ($exists) {
				return true;
			}

			// Set the token so that the next time the same visitor visits the page, it wouldn't get executed again.
			$session->set($token , 1);
		}

		return false;
	}

	/**
	 * Triggered when preparing an article for display
	 *
	 * @since	2.0
	 * @access	public
	 */
	public function onContentPrepare($context, &$article, &$params)
	{
		$allowed = array('com_content.article');

		if (!in_array($context, $allowed)) {
			return;
		}
				
		// Return if we don't have a valid article id
		if (!isset($article->id) || !(int) $article->id) {
			return true;
		}

		$placement = $this->params->get('placement', 1);

		// Attach the info box
		if ($placement == 1) {
			$contents = $this->renderAuthorBox($context, $article, $params);
			$article->text .= $contents;
		}

		// Only assign points to viewer when they are not a guest and not the owner of the article
		if (!$this->my->id) {
			return;
		}

		// Get the current view
		$view = $this->input->get('view', '', 'cmd');

		if ($this->my->id != $article->created_by && $view == 'article' && !$this->sessionExists()) {

			// Assign points to viewer
			$this->assignPoints('read.article', $this->my->id);

			// Assign badge to the viewer
			$this->assignBadge('read.article', JText::_('PLG_CONTENT_EASYSOCIAL_UPDATED_EXISTING_ARTICLE'));

			// Assign points to author when their article is being read
			$this->assignPoints('author.read.article', $article->created_by);

			// Create a new stream item when an article is being read
			$appParams = $this->getAppParams('article', 'user');

			if ($appParams && $appParams->get('stream_read', false)) {
				$this->createStream($article, 'read', $this->my->id);
			}
		}
	}

	/**
	 * Places the attached data on the event afterDisplayTitle
	 *
	 * @since	2.0
	 * @access	public
	 */
	public function onContentAfterTitle($context, &$article, &$params)
	{
		if ($context != 'com_content.article') {
			return;
		}

		if ($this->params->get('placement', 1) != 2) {
			return;
		}

		$contents = $this->renderAuthorBox($context, $article, $params);

		return $contents;
	}

	/**
	 * Places the attached data on the event beforeDisplayContent
	 *
	 * @since	2.0
	 * @access	public
	 */
	public function onContentBeforeDisplay($context, &$article, &$params)
	{
		if ($context != 'com_content.article') {
			return;
		}

		if ($this->params->get('placement', 1) != 3) {
			return;
		}

		$contents = $this->renderAuthorBox($context, $article, $params);

		return $contents;
	}

	/**
	 * Renders the author's box at the end of the article
	 *
	 * @since	1.4
	 * @access	public
	 */
	public function onContentAfterDisplay($context, &$article, &$params)
	{
		if ($context != 'com_content.article') {
			return;
		}

		// Get application params
		$appParams = $this->getAppParams('article', 'user');

		if ($this->params->get('modify_contact_link', true)) {

			// If dont have set creator alias
			if (isset($article->created_by_alias) && empty($article->created_by_alias)) {
				
				$author = ES::user($article->created_by);

				// Update the author link
				$article->contact_link = $author->getPermalink();
			}

			// Determine whether this author got created any contact or not
			$contact = $this->contactExist($article->created_by);

			if ($contact && (isset($article->created_by_alias) && !empty($article->created_by_alias))) {
				$article->contact_link = '';
			}
		}

		$contents = '';

		// If author box is configured to appear at the end of the article
		if ($this->params->get('placement', 1) == 4) {
			$contents .= $this->renderAuthorBox($context, $article, $params);
		}

		if ($this->params->get('load_reactions', false)) {
			$contents .= $this->renderReactions($article);
		}

		// Render the comments here
		if ($this->params->get('load_comments', false)) {
			$contents .= $this->renderComments($article);
		}

		return $contents;
	}	

	/**
	 * Determine whether this user did created contact
	 *
	 * @since	2.1
	 * @access	public
	 */
	public function contactExist($userId)
	{
		$db = ES::db();

		$contactEnabled = JPluginHelper::isEnabled('content', 'contact');

		if (!$contactEnabled) {
			return false;
		}

		$query = 'SELECT `id`,`catid`,`alias`, MAX(contact.`id`) AS `contactid`, contact.`alias`, contact.`catid`';
		$query .= ' FROM ' . $db->nameQuote('#__contact_details') . ' AS `contact`';
		$query .= ' WHERE contact.' . $db->nameQuote('published') . ' = ' . $db->Quote('1');
		$query .= ' AND contact.' . $db->nameQuote('user_id') . ' = ' . $db->Quote($userId);

        $db->setQuery($query);
        $result = $db->loadObject();

        if (!$result->id) {
        	return false;
        }

        return $result;
	}

	/**
	 * Renders the author box in the content
	 *
	 * @since	2.0
	 * @access	public
	 */
	public function renderAuthorBox($context, &$article, &$params)
	{
		if (!$this->params->get('display_info', false)) {
			return;
		}
		
		// Get category exclusions
		$exclusions = $this->params->get('category_exclusion');

		// If this category is excluded, skip this
		if ($exclusions && in_array($article->catid, $exclusions)) {
			return;
		}

		$this->renderDependencies();

		// Get the author of the article
		if (!isset($article->created_by)) {
			return;
		}

		$author = ES::user($article->created_by);
		$this->assign('author', $author);
		$contents = $this->output('article');

		return $contents;
	}

	/**
	 * Renders reactions on articles
	 *
	 * @since	2.1.0
	 * @access	public
	 */
	public function renderReactions($article)
	{
		$this->renderDependencies();
		
		$likes = ES::likes($article->id, 'article', 'create');
		
		$this->assign('likes', $likes);
		$output = $this->output('reactions');

		return $output;
	}

	/**
	 * Renders comments section on articles
	 *
	 * @since	2.0
	 * @access	public
	 */
	public function renderComments($article)
	{
		$this->renderDependencies();
		
		$canViewComments = ($this->my->id || !$this->my->id && $this->params->get('guest_viewcomments', true));
		$comments = '';

		// If configured to display comemnts
		if ($canViewComments) {
			$url = ContentHelperRoute::getArticleRoute($article->id . ':' . $article->alias, $article->catid);

			$comments = ES::comments($article->id, 'article', 'create', SOCIAL_APPS_GROUP_USER, array('url' => $url));
			$comments = $comments->getHtml();

			$this->assign('comments', $comments);
			$comments = $this->output('comments');
		}

		return $comments;
	}

	/**
	 * Triggered when an article is stored.
	 *
	 * @since	1.0
	 * @access	public
	 */
	public function onContentAfterSave($context, $article, $isNew)
	{
		if ($context != 'com_content.article' && $context != 'com_content.form') {
			return;
		}

		// Set the verb according to the state of the article
		$verb = $isNew ? 'create' : 'update';

		// Get application params
		$appParams = $this->getAppParams('article', 'user');

		// If app does not exist, skip this altogether.
		if (!$appParams) {
			return;
		}

		// If plugin is disabled to create new stream, skip this
		if ($isNew && !$appParams->get('stream_create', true)) {
			return;
		}

		// If plugin is disabled to create update stream, skip this.
		if (!$isNew && !$appParams->get('stream_update', true)) {
			return;
		}

		// Create stream record.
		$this->createStream($article, $verb);

		// Command to assign points and badge
		$command = $verb . '.article';

		// Assign points
		$this->assignPoints($command, $article->created_by);

		$badgeMessage = JText::_('PLG_CONTENT_EASYSOCIAL_UPDATED_EXISTING_ARTICLE');

		if ($new) {
			$badgeMessage = JText::_('PLG_CONTENT_EASYSOCIAL_CREATED_NEW_ARTICLE');
		}

		// Assign badge for the user
		$this->assignBadge($command, $badgeMessage);
	}

	/**
	 * Assign points
	 *
	 * @since	1.0
	 * @access	public
	 */
	private function assignPoints($command, $userId = null)
	{
		$userId = ES::user($userId)->id;

		return ES::points()->assign($command, 'com_content', $userId);
	}

	/**
	 * Assign badges
	 *
	 * @since	1.0
	 * @access	public
	 */
	private function assignBadge($rule , $message , $creatorId = null)
	{
		$creator 	= FD::user( $creatorId );

		$badge 	= FD::badges();
		$state 	= $badge->log( 'com_content' , $rule , $creator->id , $message );

		return $state;
	}

	/**
	 * Perform cleanup when an article is being deleted
	 *
	 * @since	1.2
	 * @access	public
	 */
	public function onContentBeforeDelete( $context , $data )
	{
		if ($context != 'com_content.article') {
			return;
		}

		// Delete the items from the stream.
		$stream = ES::stream();
		$stream->delete($data->id, 'article');
	}

	/**
	 * Generate new stream activity.
	 *
	 * @since	1.0
	 * @access	public
	 */
	private function createStream($article, $verb, $actor = null)
	{
		$tmpl = ES::stream()->getTemplate();

		if (is_null($actor)) {
			$actor = $article->created_by;
		}

		// Set the creator of this article.
		$tmpl->setActor($actor, SOCIAL_TYPE_USER);
		$tmpl->setContext($article->id, 'article');
		$tmpl->setVerb($verb);

		// Load up the category dataset
		$category = JTable::getInstance('Category');
		$category->load( $article->catid );

		// Get the permalink
		$permalink = ContentHelperRoute::getArticleRoute($article->id . ':' . $article->alias, $article->catid . ':' . $category->alias);

		// Get the category permalink
		$categoryPermalink 	= ContentHelperRoute::getCategoryRoute($category->id . ':' . $category->alias);

		// Store the article in the params
		$registry = ES::registry();
		$registry->set('article', $article);
		$registry->set('category', $category);
		$registry->set('permalink', $permalink);
		$registry->set('categoryPermalink', $categoryPermalink);

		// We need to tell the stream that this uses the core.view privacy.
		$tmpl->setAccess('core.view');

		// Set the template params
		$tmpl->setParams($registry);

		ES::stream()->add($tmpl);
	}
}
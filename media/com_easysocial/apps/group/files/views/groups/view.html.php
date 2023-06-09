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

class FilesViewGroups extends SocialAppsView
{
	/**
	 * Renders the file manager
	 *
	 * @since	2.0
	 * @access	public
	 */
	public function display( $groupId = null , $docType = null )
	{
		$this->setTitle('APP_FILES_APP_TITLE');
		
		$group = ES::group($groupId);

		// Check for the permission to view the files
		if (!$group->canViewItem() || !$group->canAccessFiles()) {
			return $this->redirect($group->getPermalink(false));
		}

		// Load up the explorer library.
		$explorer = ES::explorer($group->id, SOCIAL_TYPE_GROUP);

		// Get total number of files that are already uploaded in the group
		$model = ES::model('Files');
		$total = (int) $model->getTotalFiles($group->id, SOCIAL_TYPE_GROUP);

		$access = $group->getAccess();
		$allowUpload = $access->get('files.max') == 0 || $total < $access->get('files.max') ? true : false;
		$uploadLimit = $access->get('files.maxsize');

		// Ensure that they really can upload
		if (!$group->canCreateFiles()) {
			$allowUpload = false;
		}

		$params = $this->getParams();
		$allowedExtensions = $params->get('allowed_extensions', 'zip,txt,pdf,gz,php,doc,docx,ppt,xls');
		
		$this->set('uploadLimit', $uploadLimit);
		$this->set('allowUpload', $allowUpload);
		$this->set('allowedExtensions', $allowedExtensions);
		$this->set('explorer', $explorer);
		$this->set('group', $group);

		echo parent::display('groups/default');
	}
}

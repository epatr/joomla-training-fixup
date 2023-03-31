<?php
/**
 * @package     Joomla.site
 * @subpackage  Plugin.hwdmediashare.event_social
 *
 * @copyright   Copyright (C) 2013 Highwood Design Limited. All rights reserved.
 * @license     GNU General Public License http://www.gnu.org/copyleft/gpl.html
 * @author      Dave Horsfall
 */

defined('_JEXEC') or die;

class plgHwdmediashareEvent_Social extends JPlugin
{
        /**
	 * Method to insert activity and add points after the onAfterMediaAdd event.
         * 
	 * @access  public
	 * @param   object   $action  The item being discussed.
	 * @param   object   $target  The element type being discussed: http://hwdmediashare.co.uk/learn/api/68-api-definitions
	 * @return  boolean  True on success, false on fail.
	 **/
	public function onAfterMediaAdd($action, $target)
	{          
                /**
                 * 
                 * EASYSOCIAL
                 * 
                 **/
            
                if (file_exists(JPATH_ROOT . '/administrator/components/com_easysocial/includes/foundry.php'))
                {
                        // This is the main engine file of EasySocial.
                        require_once( JPATH_ROOT . '/administrator/components/com_easysocial/includes/foundry.php' );

                        // Activity logging.
                        if ($this->params->get('easysocial_mediaadd_stream', 1))
                        {
                                $stream			= Foundry::stream();
                                $streamTemplate		= $stream->getTemplate();

                                // Set the actor.
                                $streamTemplate->setActor($action->created_user_id, SOCIAL_TYPE_USER);

                                // Set the context.
                                $streamTemplate->setContext($action->id, 'media');

                                // Set the verb.
                                $streamTemplate->setVerb('create');

                                // Set the access.
                                $streamTemplate->setPublicStream('core.view');

                                // Create the stream data.
                                $stream->add($streamTemplate); 
                        }
                        
                        // Points logging.
                        Foundry::points()->assign('media.add', 'com_hwdmediashare', $action->created_user_id); 
                }
            
                /**
                 * 
                 * JOMSOCIAL
                 * 
                 **/
                
                if (file_exists(JPATH_ROOT . '/components/com_community/libraries/core.php'))
                {
                        require_once(JPATH_ROOT . '/components/com_community/libraries/core.php');
                        require_once(JPATH_ROOT . '/components/com_community/libraries/error.php');

                        if ($this->params->get('easysocial_mediaadd_stream', 1))
                        {
                                $HWDact = new stdClass();
                                $HWDact->actor = $action->created_user_id;
                                $HWDact->author = JFactory::getUser($action->created_user_id)->name;
                                $HWDact->action = $action->id;
                                $HWDact->target = 0;
                                $HWDact->verb = 2;

                                $act = new stdClass();
                                $act->cmd       = 'media.add';
                                $act->actor     = $action->created_user_id;
                                $act->target    = 0; // No target
                                $act->title 	= hwdMediaShareActivities::renderActivityHtml($HWDact);
                                $act->content   = 'content';
                                $act->app       = 'media.add';
                                $act->cid       = $action->id;
                                $act->params	= '';                                    

                                CFactory::load('libraries', 'activities');
                                $act->comment_type = $act->app;
                                $act->comment_id   = CActivities::COMMENT_SELF;

                                $act->like_type    = $act->app;
                                $act->like_id      = CActivities::LIKE_SELF;

                                // Insert into activity stream.
                                CActivityStream::add($act); 
                        }

                        // Points logging.
                        CFactory::load('libraries', 'userpoints');
			CUserPoints::assignPoint('com_hwdmediashare.media.add');
                }
                
		return true;
	}
}
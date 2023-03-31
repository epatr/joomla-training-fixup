<?php
/**
 * @package     Joomla.site
 * @subpackage  Component.hwdmediashare
 *
 * @copyright   Copyright (C) 2013 Highwood Design Limited. All rights reserved.
 * @license     GNU General Public License http://www.gnu.org/copyleft/gpl.html
 * @author      Dave Horsfall
 */

defined('_JEXEC') or die;

class HwdMediaShareControllerGet extends JControllerLegacy
{
	/**
	 * The prefix to use with controller messages.
         * 
         * @access  protected
	 * @var     string
	 */
	protected $text_prefix = 'COM_HWDMS';
        
	/**
	 * Class constructor.
	 *
	 * @access  public
	 * @param   array  $config  An optional associative array of configuration settings.
         * @return  void
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);
                
		// Define standard task mappings.                
                $this->registerTask('dislike', 'like');
                $this->registerTask('unfavourite', 'favourite');
                $this->registerTask('unsubscribe', 'subscribe');

		// Check if the cid array exists, otherwise populate with the id.
		$cid = JFactory::getApplication()->input->get('cid', array(), 'array');
                $id = JFactory::getApplication()->input->get('id', 0, 'int');
                if (empty($cid) && $id) JFactory::getApplication()->input->set('cid', array($id));
	}
        
	/**
	 * Method to add and remove a media from favourite list.
	 *
	 * @access  public
         * @return  void
	 */
	public function favourite()
        {
		// Check for request forgeries.
		JSession::checkToken('request') or die(JText::_('JINVALID_TOKEN'));

		// Initialise variables.
                $document = JFactory::getDocument();
                
		// Get items to like from the request.
		$cid = JFactory::getApplication()->input->get('id', 0, 'int');

		// Initialise variables.
		$values	= array('favourite' => 'addFavourite', 'unfavourite' => 'removeFavourite');
		$task	= $this->getTask();
		$value	= JArrayHelper::getValue($values, $task, 'addFavourite', 'word');
                
		if (!is_numeric($cid) || $cid < 1)
		{
                        // Set JSON output in JSEND spec http://labs.omniti.com/labs/jsend
                        $return = array(
                                'status' => 'fail',
                                'data' => array(
                                        'task' => $task
                                ),
                                'message' => $this->text_prefix . '_NO_ITEM_SELECTED'
                        );  
                        
			JLog::add(JText::_($this->text_prefix . '_NO_ITEM_SELECTED'), JLog::WARNING, 'jerror');                        
		}
		else
		{
			// Get the model.
                        hwdMediaShareFactory::load('favourites');
                        $model = hwdMediaShareFavourites::getInstance();                        
                        $model->elementType = 1;
                        
			// Add/remove media to favourites.
			if ($model->$value($cid))
			{
                                // Set JSON output in JSEND spec http://labs.omniti.com/labs/jsend
                                $return = array(
                                        'status' => 'success',
                                        'data' => array(
                                                'task' => $task
                                        ),
                                        'message' => null
                                );
			}
			else
			{
                                // Set JSON output in JSEND spec http://labs.omniti.com/labs/jsend
                                $return = array(
                                        'status' => 'fail',
                                        'data' => array(
                                                'task' => $task
                                        ),
                                        'message' => $model->getError()
                                );
			}
		}
                
                // Set the MIME type for JSON output.
                $document->setMimeEncoding( 'application/json' );

                // Output the JSON data.      
                echo json_encode($return);
                
		JFactory::getApplication()->close();
        }
        
	/**
	 * Method to like or dislike a single media.
	 *
	 * @access  public
         * @return  void
	 */
	public function like()
	{
		// Check for request forgeries.
		JSession::checkToken('request') or die(JText::_('JINVALID_TOKEN'));

		// Initialise variables.
                $document = JFactory::getDocument();
                
		// Get items to like from the request.
		$cid = JFactory::getApplication()->input->get('id', 0, 'int');

		// Initialise variables.
		$values	= array('like' => 1, 'dislike' => 0);
		$task	= $this->getTask();
		$value	= JArrayHelper::getValue($values, $task, 0, 'int');

		if (!is_numeric($cid) || $cid < 1)
		{
                        // Set JSON output in JSEND spec http://labs.omniti.com/labs/jsend
                        $return = array(
                                'status' => 'fail',
                                'data' => array(
                                        'task' => $task
                                ),
                                'message' => $this->text_prefix . '_NO_ITEM_SELECTED'
                        );  
                        
			JLog::add(JText::_($this->text_prefix . '_NO_ITEM_SELECTED'), JLog::WARNING, 'jerror');  
		}
		else
		{
			// Get the model.
			$model = parent::getModel('MediaItem', 'hwdMediaShareModel', array('ignore_request' => true));

			// Like/dislike the media.
			if ($model->like($cid, $value))
			{
                                // Set JSON output in JSEND spec http://labs.omniti.com/labs/jsend
                                $return = array(
                                        'status' => 'success',
                                        'data' => array(
                                                'task' => $task
                                        ),
                                        'message' => null
                                );
			}
			else
			{
                                // Set JSON output in JSEND spec http://labs.omniti.com/labs/jsend
                                $return = array(
                                        'status' => 'fail',
                                        'data' => array(
                                                'task' => $task
                                        ),
                                        'message' => $model->getError()
                                );
			}
		}
                
                // Set the MIME type for JSON output.
                $document->setMimeEncoding( 'application/json' );

                // Output the JSON data.      
                echo json_encode($return);
                
		JFactory::getApplication()->close();
	}
        
	/**
	 * Method to subscribe/unsubscribe to a single channel.
	 *
	 * @access  public
         * @return  void
	 */
	public function subscribe()
	{
		// Check for request forgeries.
		JSession::checkToken('request') or die(JText::_('JINVALID_TOKEN'));

		// Initialise variables.
                $document = JFactory::getDocument();
                
		// Get items to subscribe from the request.
		$cid = JFactory::getApplication()->input->get('cid', array(), 'array');

		// Initialise variables.
		$values	= array('subscribe' => 'subscribe', 'unsubscribe' => 'unsubscribe');
		$task	= $this->getTask();
		$value	= JArrayHelper::getValue($values, $task, 'subscribe', 'word');

		if (!is_array($cid) || count($cid) < 1)
		{
			JLog::add(JText::_($this->text_prefix . '_NO_ITEM_SELECTED'), JLog::WARNING, 'jerror');
		}
		else
		{
			// Get the model.
                        hwdMediaShareFactory::load('subscriptions');
                        $model = hwdMediaShareSubscriptions::getInstance();                     
                        $model->elementType = 5;

			// Make sure the item ids are integers.
			jimport('joomla.utilities.arrayhelper');
			JArrayHelper::toInteger($cid);

			// Subscribe to the channel.
			if ($model->$value($cid))
			{
                                // Set JSON output in JSEND spec http://labs.omniti.com/labs/jsend
                                $return = array(
                                        'status' => 'success',
                                        'data' => array(
                                                'task' => $task
                                        ),
                                        'message' => null
                                );
			}
			else
			{
                                // Set JSON output in JSEND spec http://labs.omniti.com/labs/jsend
                                $return = array(
                                        'status' => 'fail',
                                        'data' => array(
                                                'task' => $task
                                        ),
                                        'message' => $model->getError()
                                );
			}
		}
                
                // Set the MIME type for JSON output.
                $document->setMimeEncoding( 'application/json' );

                // Output the JSON data.      
                echo json_encode($return);
                
		JFactory::getApplication()->close();
	}        
}
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

class hwdMediaShareViewMediaItem extends JViewLegacy
{
        public $item;

	public $state;
        
	public $params;
        
	/**
	 * Display the view.
	 *
	 * @access  public
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 * @return  void
	 */
	public function display($tpl = null)
	{
		// Initialise variables.
                $app = JFactory::getApplication();
                
                // Get data from the model.
                $this->item = $this->get('Item');
		$this->state = $this->get('State');
		$this->params = $this->state->params;
                $this->activities = $this->get('Activities');
                
                // Include JHtml helpers.
                JHtml::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR . '/helpers/html');
                JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

                // Register classes.
                JLoader::register('hwdMediaShareHelperModule', JPATH_COMPONENT . '/helpers/module.php');  
                
                // Import HWD libraries.                
                hwdMediaShareFactory::load('activities');
                hwdMediaShareFactory::load('downloads');
                hwdMediaShareFactory::load('files');
                hwdMediaShareFactory::load('media');
                hwdMediaShareFactory::load('mobile'); 
                hwdMediaShareFactory::load('thumbnails');
		hwdMediaShareFactory::load('utilities');
                
                $this->utilities = hwdMediaShareUtilities::getInstance();
                $this->mobile = hwdMediaShareMobile::getInstance();
		$this->pageclass_sfx = htmlspecialchars($this->params->get('pageclass_sfx'));
                $this->columns = $this->params->get('list_columns', 3);
                $this->return = base64_encode(JFactory::getURI()->toString());
                $this->display = $this->state->get('media.display', 'details');
                $this->media_tab_modules = $this->document->countModules('media-tabs') ? true : false;     

                // Check for errors.
                if (count($errors = $this->get('Errors')))
                {
                        JError::raiseError(500, implode('<br />', $errors));
                        return false;
                }

                // Check for age restrictions.
		if (isset($this->item->agerestricted))
		{
			$this->assign('dob', $app->getUserState( "media.dob" ));
                        $tpl = 'dob';                        
		}
                
                // Check for password protection.
		if (isset($this->item->passwordprotected))
		{
			$tpl = 'password';                        
		}
                
                $model = $this->getModel();
		$model->hit();

		$this->_prepareDocument();
                
		// Display the template.
		parent::display($tpl);
	}
        
	/**
	 * Prepares the document.
	 *
         * @access  protected
	 * @return  void
	 */
	protected function _prepareDocument()
	{
		$app = JFactory::getApplication();
		$menus = $app->getMenu();
		$pathway = $app->getPathway();
		$title = null;

                // Add page assets.
                JHtml::_('hwdhead.core', $this->params);
                JHtml::_('hwdhead.mediaitem', $this->params, $this->item);

		// Define the page title and headings. 
		$menu = $menus->getActive();
		if ($menu && $menu->query['option'] == 'com_hwdmediashare' && $menu->query['view'] == 'mediaitem' && (int) @$menu->query['id'] == $this->item->id)
		{
                        $title = $this->params->get('page_title');
                        $heading = $this->params->get('page_heading') ? $this->params->get('page_heading') : ($this->item->title ? $this->item->title : JText::_('COM_HWDMS_MEDIA'));
		}
		else
		{
			if ($this->item->title) 
                        {
				$title = $this->item->title;
                                $heading = $this->item->title;   
			} 
                        else
                        {
                                $title = JText::_('COM_HWDMS_MEDIA');
                                $heading = JText::_('COM_HWDMS_MEDIA');
                        }
		}
                
		// If the menu item does not concern this view then add a breadcrumb.
		if ($menu && ($menu->query['option'] != 'com_hwdmediashare' || $menu->query['view'] != 'mediaitem' || (int) @$menu->query['id'] != $this->item->id))
		{
                        // Breadcrumb support.
			$path = array(array('title' => $this->item->title, 'link' => ''));

                        /**
                         * Category breadcrumb support.
                         * 
                         * We check if the media is associated with one category, then we add 
                         * category breadcrumbs. However, we also check if the menu item is 
                         * associated with that the category, in which case we don't include 
                         * additional breadcrumbs because the Joomla menu breadcrumbs are likely 
                         * to be sufficient. This is difficult to predict. 
                         */
                        $category_id = $app->input->get('category_id', '', 'int');
                        $playlist_id = $app->input->get('playlist_id', '', 'int');
                        $album_id = $app->input->get('album_id', '', 'int');
                        $group_id = $app->input->get('group_id', '', 'int');

                        if ($category_id)
                        {
                                $categories = JCategories::getInstance('hwdMediaShare');
                                $category = $categories->get($category_id);

                                if ($category)
                                {
                                        while ($category)
                                        {
                                                $path[] = array('title' => $category->title, 'link' => hwdMediaShareHelperRoute::getCategoryRoute($category->id));
                                                
                                                // Bail at menu category.
                                                if ($menu && $menu->query['option'] == 'com_hwdmediashare' && $menu->query['view'] == 'category' && isset($menu->query['id']))
                                                {
                                                        if ($category->id == $menu->query['id'])
                                                        {
                                                                break;
                                                        }
                                                }   
                                                
                                                $category = $category->getParent();                      
                                        }
                                        
                                        // Remove the last element, which will be the ROOT category.
                                        array_pop($path); 
                                }
                                elseif ($playlist_id)
                                {
                                    
                                }                                
                                elseif ($album_id)
                                {
                                    
                                }
                                elseif ($group_id)
                                {
                                    
                                }
                        }

			$path = array_reverse($path);
			foreach($path as $item)
			{
				$pathway->addItem($item['title'], $item['link']);
			}                    
		}
                
		// Redefine the page title and headings. 
                $this->params->set('page_title', $title);
                $this->params->set('page_heading', $heading); 
                
		// Check for empty title and add site name when configured.
		if (empty($title))
                {
			$title = $app->getCfg('sitename');
		}
		elseif ($app->getCfg('sitename_pagetitles', 0) == 1)
                {
			$title = JText::sprintf('JPAGETITLE', $app->getCfg('sitename'), $title);
		}
		elseif ($app->getCfg('sitename_pagetitles', 0) == 2)
                {
			$title = JText::sprintf('JPAGETITLE', $title, $app->getCfg('sitename'));
		}
		if (empty($title))
		{
			$title = $this->item->title;
		}
                
                // Set metadata.
		$this->document->setTitle($title);

                if ($this->item->params->get('meta_desc'))
		{
			$this->document->setDescription($this->item->params->get('meta_desc'));
		}
                elseif ($this->item->description)
                {                        
			$this->document->setDescription($this->escape(JHtml::_('string.truncate', $this->item->description, 160, true, false)));   
                }                 
                elseif ($menu && $menu->query['option'] == 'com_hwdmediashare' && $menu->query['view'] == 'mediaitem' && (int) @$menu->query['id'] == $this->item->id && $this->params->get('menu-meta_description'))
                {
			$this->document->setDescription($this->params->get('menu-meta_description'));
                } 
                elseif ($this->params->get('meta_desc'))
                {
			$this->document->setDescription($this->params->get('meta_desc'));
                }                

		if ($this->item->params->get('meta_keys'))
		{
			$this->document->setMetadata('keywords', $this->item->params->get('meta_keys'));
		}
                elseif (isset($this->item->tags) && count($this->item->tags->itemTags))
                {
                        $metaTags = array();
			foreach($this->item->tags->itemTags as $tag)
			{
				$metaTags[] = $tag->title;
			}   
			$this->document->setMetadata('keywords', $this->escape(JHtml::_('string.truncate', implode(',', $metaTags), 160, true, false)));   
                } 
                elseif ($menu && $menu->query['option'] == 'com_hwdmediashare' && $menu->query['view'] == 'mediaitem' && (int) @$menu->query['id'] == $this->item->id && $this->params->get('menu-meta_keywords'))
                {
			$this->document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
                } 
		elseif ($this->params->get('meta_keys'))
                {
			$this->document->setMetadata('keywords', $this->params->get('meta_keys'));
                }

		if ($this->item->params->get('meta_rights'))
		{
			$this->document->setMetadata('keywords', $this->item->params->get('meta_rights'));
		}
                elseif ($this->params->get('meta_rights'))
                {
			$this->document->setMetadata('copyright', $this->params->get('meta_rights'));
                }             
                
		if ($this->params->get('meta_author') != 0 && $this->item->params->get('meta_author') != 0 && isset($this->item->author))
		{
			$this->document->setMetadata('author', $this->item->author);
                }      
	}

	/**
	 * Method to check if current item has any downloads.
	 *
         * @access  public
	 * @return  boolean True if downloads, false if none.
	 */
	public function hasDownloads()
	{
                // Initialise variables.
		$user = JFactory::getUser();
                $groups	= $user->getAuthorisedViewLevels();
                            
                // Load HWD config.
                $hwdms = hwdMediaShareFactory::getInstance();
                $config = $hwdms->getConfig();
                
                // Check download access.
                if (!in_array($config->get('default_download'), $groups) || ($this->item->download > 0 && !in_array($this->item->download, $groups))) 
                {
                        return false;
                }
                        
                // Remote file
                if ($this->item->type == 7 && !empty($this->item->source))
                {
                        return $this->item->source;
                }

                if ($this->item->type == 1 || $this->item->type == 5)
                {
                        if (count($this->item->mediafiles))
                        {
                                if ($config->get('download_action') != '0')
                                {
                                        return JRoute::_('index.php?option=com_hwdmediashare&task=mediaform.download&id=' . $this->item->id . '&return=' . $this->return . '&tmpl=component');
                                }
                                else
                                {
                                        return hwdMediaShareDownloads::url($this->item, 1, 1);
                                }
                        }
                }
                
                return false;
	}
        
	/**
	 * Method to check if current item has meta potential.
	 *
         * @access  public
	 * @return  boolean True if downloads, false if none.
	 */
	public function hasMeta()
	{
                if ($this->item->type == 1 && ($this->item->media_type == 1 || $this->item->media_type == 3 || $this->item->media_type == 4))
                {
                        return true;
                }
                
                return false;
	}
        
	/**
	 * Method to check and return video qualities.
	 *
         * @access  public
	 * @return  mixed   Array of available qualities or false if none.
	 */
	public function hasQualities()
	{
                if ($this->item->media_type == 4 && ($this->item->type == 1 || $this->item->type == 5))
                {
                        if (count($this->item->mediafiles))
                        {
                                $types = array();
                                foreach($this->item->mediafiles as $file)
                                {
                                        $types[] = $file->file_type;
                                }  

                                $qualities = array();
                                if (array_intersect(array(11), $types)) {
                                    $qualities[] = '240';
                                }
                                if (array_intersect(array(12, 14, 18, 22), $types)) {
                                    $qualities[] = '360';
                                }
                                if (array_intersect(array(13, 15, 19, 23), $types)) {
                                    $qualities[] = '480';
                                }     
                                if (array_intersect(array(16, 20, 24), $types)) {
                                    $qualities[] = '720';
                                }     
                                if (array_intersect(array(17, 21, 25), $types)) {
                                    $qualities[] = '1080';
                                }    

                                return $qualities;
                        }
                }
                
                return false;
	}  

	/**
	 * Prepares the commenting framework.
	 *
         * @access  public
         * @param   object  $media  The media object.
	 * @return  string  The markup to show the commenting framework.
	 */
	public function getComments($media)
	{
                // Load HWD config.
                $hwdms = hwdMediaShareFactory::getInstance();
                $config = $hwdms->getConfig();
                
                // Load HWD utilities.
                hwdMediaShareFactory::load('utilities');
                $utilities = hwdMediaShareUtilities::getInstance();

                $pluginClass = 'plgHwdmediashare' . $config->get('commenting');
                $pluginPath = JPATH_ROOT.'/plugins/hwdmediashare/' . $config->get('commenting') . '/' . $config->get('commenting') . '.php';
                if (file_exists($pluginPath) && $config->get('commenting') && $media->params->get('allow_comments', 1) != 0)
                {
                        JLoader::register($pluginClass, $pluginPath);
                        $HWDcomments = call_user_func(array($pluginClass, 'getInstance'));
                        if ($comments = $HWDcomments->getComments($media))
                        {
                                return $comments;
                        }
                        else
                        {
                                return $utilities->printNotice($HWDcomments->getError());
                        }
                }
	} 
}
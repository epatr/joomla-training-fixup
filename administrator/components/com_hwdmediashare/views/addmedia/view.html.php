<?php
/**
 * @package     Joomla.administrator
 * @subpackage  Component.hwdmediashare
 *
 * @copyright   Copyright (C) 2013 Highwood Design Limited. All rights reserved.
 * @license     GNU General Public License http://www.gnu.org/copyleft/gpl.html
 * @author      Dave Horsfall
 */

defined('_JEXEC') or die;

class hwdMediaShareViewAddMedia extends JViewLegacy
{
	/**
	 * The show form view variable.
         * 
         * @access  public
	 * @var     boolean
	 */
	public $show_form = true;

	/**
	 * The upload method view variable.
         * 
         * @access  public
	 * @var     mixed
	 */
	public $method = false;
        
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
                $lang = JFactory::getLanguage();
                $document = JFactory::getDocument();

                // Load HWD config.
                $hwdms = hwdMediaShareFactory::getInstance();
                $config = $hwdms->getConfig();
                
                // Import HWD libraries.
		hwdMediaShareFactory::load('upload');
		hwdMediaShareFactory::load('remote');
                
                // Get data from the model.
		$this->config = $config;
                $this->state = $this->get('State');
		$this->form = $this->get('Form');
                $this->replace = ($app->input->get('id', '', 'int') > 0 ? $app->input->get('id', '0', 'int'): false);
                $this->method = ($app->input->get('method', '', 'word') ? $app->input->get('method', '', 'word') : false);
                $this->return = base64_encode(JFactory::getURI()->toString());

		// Determine if we need to show the form.
		if ($this->config->get('upload_workflow') == 0 && $this->show_form && !$this->replace && $this->method != 'remote') 
		{
			$tpl = 'form';
		}             
                else
                {
                        $this->jformdata = hwdMediaShareUpload::getProcessedUploadData(); 
                        $this->jformreg = new JRegistry($this->jformdata);

                        if ($this->method == 'remote' && $this->config->get('enable_uploads_remote') == 1 && !$this->replace) 
                        {
                                $tpl = 'remote';
                        }  
                        else
                        {
                                $this->localExtensions = $this->get('localExtensions');

                                if ($this->config->get('enable_uploads_file') == 1) 
                                {
                                        if ($this->config->get('upload_tool_perl') == 1) 
                                        {
                                                $this->get('UberUploadScript');
                                        }
                                        elseif ($this->config->get('upload_workflow') == 1) 
                                        {
                                                $this->get('FancyUploadScript');
                                        }
                                }

                                // Bulk import from server (unless we are updating an existing media).
                                if (!$this->replace) 
                                {
                                        $style = $app->getUserStateFromRequest('media.list.layout', 'layout', 'thumbs', 'word');

                                        JHtml::_('behavior.framework', true);

                                        $document->addScript(JURI::root() . "/media/com_hwdmediashare/assets/javascript/MooTree.js");

                                        JHtml::_('script', 'system/mootree.js', true, true, false, false);
                                        JHtml::_('stylesheet', 'system/mootree.css', array(), true);
                                        if ($lang->isRTL()) :
                                                JHtml::_('stylesheet', 'media/mootree_rtl.css', array(), true);
                                        endif;

                                        $base = JPATH_SITE.'/media';

                                        $js = "
                                                var basepath = '';
                                                var viewstyle = 'thumbs';
                                        " ;
                                        $document->addScriptDeclaration($js);

                                        $session = JFactory::getSession();
                                        $state = $this->get('state');
                                        $this->state = $state;
                                        $this->folders = $this->get('folderTree');
                                        $this->folders_id = ' id="media-tree"';
                                } 
                        }
                }
                
                // Check for errors.
                if (count($errors = $this->get('Errors')))
                {
                        JError::raiseError(500, implode('<br />', $errors));
                        return false;
                }

		// We don't need toolbar in the modal window.
		if ($this->getLayout() !== 'modal')
		{
			$this->addToolbar();
			$this->sidebar = JHtmlSidebar::render();
		}
                
		// Display the template.
		parent::display($tpl);
                
		$this->document->addStyleSheet(JURI::root() . "media/com_hwdmediashare/assets/css/administrator.css");

                // Load file input script.
		$this->document->addScript(JURI::root() . "media/com_hwdmediashare/assets/javascript/bootstrap-file-input.js");
		$this->document->addScript(JURI::root() . "media/com_hwdmediashare/assets/javascript/hwd.min.js");
		$this->document->addScriptDeclaration("
                    var buttonWord = 'Select File...';
                ");  
                
                // Add JavaScript for remote streams
                // http://www.sanwebe.com/2013/03/addremove-input-fields-dynamically-with-jquery
		$this->document->addScriptDeclaration("
jQuery(document).ready(function() {
    var max_fields      = 10; //maximum input boxes allowed
    var wrapper         = jQuery('.stream_fields'); //Fields wrapper
    var add_button      = jQuery('.add_field_button'); //Add button ID

    var x = 1; //initlal text box count
    jQuery(add_button).click(function(e){ //on add input button click
        e.preventDefault();
        if(x < max_fields){ //max input box allowed
            x++; //text box increment
            
    var fields_html      = '<div>' +
'<div class=\"control-group\">' +
    '<div class=\"control-label\">' +
            '<label id=\"jform_source_type_' + x + '-lbl\" for=\"jform_source_type_' + x + '\" class=\"hide\">" . JText::_('COM_HWDMS_STREAM_TYPE') . "</label>' +
    '</div>' +
    '<div class=\"controls\">' +
            '<select id=\"jform_source_type_' + x + '\" name=\"jform[source_type][' + x + ']\">' +
                    '<option value=\"1\">" . JText::_('COM_HWDMS_STREAMTYPE_RTMP') . "</option>' +
                    '<option value=\"2\">" . JText::_('COM_HWDMS_STREAMTYPE_HLS') . "</option>' +
                    '<option value=\"3\">" . JText::_('COM_HWDMS_STREAMTYPE_MP4') . "</option>' +                                         
            '</select>' +                          
    '</div>' +
'</div>' +
'<div class=\"control-group\">' +
    '<div class=\"control-label\">' +
            '<label id=\"jform_source_quality_' + x + '-lbl\" for=\"jform_source_quality_' + x + '\" class=\"hide\">" . JText::_('COM_HWDMS_STREAM_QUALITY') . "</label>' +
    '</div>' +
    '<div class=\"controls\">' +
            '<select id=\"jform_source_quality_' + x + '\" name=\"jform[source_quality][' + x + ']\">' +
                    '<option value=\"240\">240p</option>' +
                    '<option value=\"360\">360p</option>' +
                    '<option value=\"480\">480p</option>' +
                    '<option value=\"720\">720p</option>' +
                    '<option value=\"1080\">1080p</option>' +
            '</select>' +
    '</div>' +
'</div>' +
'<div class=\"control-group\">' +
    '<div class=\"control-label\">' +
            '<label id=\"jform_source_url_' + x + '-lbl\" for=\"jform_source_url_' + x + '\" class=\"hide\">" . JText::_('COM_HWDMS_STREAM_URL') . "</label>' +
    '</div>' +
    '<div class=\"controls\">' +
            '<input type=\"text\" name=\"jform[source_url][' + x + ']\" id=\"jform_source_url_' + x + '\" value=\"\" size=\"40\" placeholder=\"" . JText::_('COM_HWDMS_STREAM_URL') . "\" />' +
    '</div>' +
'</div>' +
'<a href=\"#\" class=\"remove_field btn btn-info\">" . JText::_('COM_HWDMS_REMOVE') . "</a>' +
'<hr />' +
'</div>';

            jQuery(wrapper).append(fields_html); //add input box
            jQuery('.stream_fields select').chosen();
        }
    });
   
    jQuery(wrapper).on('click','.remove_field', function(e){ //user click on remove text
        e.preventDefault(); jQuery(this).parent('div').remove(); x--;
    })
});
                ");  
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @access  protected
	 * @return  void
	 */
	protected function addToolBar()
	{
		JFactory::getApplication()->input->set('hidemainmenu', true);
                $canDo = hwdMediaShareHelper::getActions();
		$user  = JFactory::getUser();
                
		JToolBarHelper::title($this->method == 'remote' && $this->config->get('enable_uploads_remote') == 1 && !$this->replace ? JText::_('COM_HWDMS_ADD_REMOTE_MEDIA') : JText::_('COM_HWDMS_ADD_NEW_MEDIA'), 'upload');
          
                if ($this->config->get('upload_workflow') == 0 && $this->show_form && $this->method != 'remote' && $canDo->get('core.create'))
		{
                        JToolBarHelper::custom('addmedia.processform', 'save-new', 'save-new', 'COM_HWDMS_TOOLBAR_SAVE_AND_UPLOAD', false);
		}                    
                JToolbarHelper::cancel('addmedia.cancel');
		JToolbarHelper::help('HWD', false, 'http://hwdmediashare.co.uk/learn/docs');
	}

 	/**
	 * Method to render the platform upload title.
	 *
	 * @access  protected
	 * @return  void
	 */
	protected function getPlatformUploadTitle()
	{
                // Load HWD config.
                $hwdms = hwdMediaShareFactory::getInstance();
                $config = $hwdms->getConfig();

                $pluginClass = 'plgHwdmediashare' . $config->get('platform');
                $pluginPath = JPATH_ROOT . '/plugins/hwdmediashare/' . $config->get('platform') . '/' . $config->get('platform') . '.php';
                if (file_exists($pluginPath))
                {
                        // Load the language file.
                        $lang = JFactory::getLanguage();
                        $lang->load('plg_hwdmediashare_' .  $config->get('platform'), JPATH_ADMINISTRATOR, $lang->getTag());

                        return JText::_('PLG_HWDMEDIASHARE_' . $config->get('platform') . '_UPLOAD_TO_PLATFORM');
                }
                else
                {
                        return JText::_('COM_HWDMS_UPLOAD_TO_PLATFORM');
                }
	}
        
 	/**
	 * Method to render the platform upload form.
	 *
	 * @access  protected
	 * @return  void
	 */
	protected function getPlatformUploadForm()
	{
                // Load HWD config.
                $hwdms = hwdMediaShareFactory::getInstance();
                $config = $hwdms->getConfig();

                // Load HWD utilities.
                hwdMediaShareFactory::load('utilities');
                $utilities = hwdMediaShareUtilities::getInstance();
                
                $pluginClass = 'plgHwdmediashare'.$config->get('platform');
                $pluginPath = JPATH_ROOT . '/plugins/hwdmediashare/' . $config->get('platform') . '/' . $config->get('platform') . '.php';
                if (file_exists($pluginPath))
                {
                        JLoader::register($pluginClass, $pluginPath);
                        $HWDplatform = call_user_func(array($pluginClass, 'getInstance'));
                        if ($form = $HWDplatform->getUploadForm())
                        {
                                return $form;
                        }
                        else
                        {
                                return $utilities->printNotice($HWDplatform->getError());
                        }
                }
	}
        
 	/**
	 * Method to get the folder level for the server driectory scan tool.
	 *
	 * @access  protected
	 * @return  void
	 */      
	protected function getFolderLevel($folder)
	{
		$this->folders_id = null;
		$txt = null;
		if (isset($folder['children']) && count($folder['children'])) 
                {
			$tmp = $this->folders;
			$this->folders = $folder;
			$txt = $this->loadTemplate('folders');
			$this->folders = $tmp;
		}
		return $txt;
	}
        
	/**
	 * Display the scan view.
	 *
	 * @access  public
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 * @return  void
	 */
	function scan($tpl = null)
	{
                // Initialise variables.
                $app = JFactory::getApplication();            
                $document = JFactory::getDocument();
                $db = JFactory::getDBO();

                // Import Joomla libraries.
		jimport('joomla.filesystem.folder');
		jimport('joomla.filesystem.file');
                
		// Required to initiate the MooTree functionality.
                $document->addScriptDeclaration("
		window.addEvent('domready', function() {
			window.parent.document.updateUploader();
		});");
                
		// Check for invalid folder name

//			$dirname = JRequest::getVar('folder', '', '', 'string');
//
//			if (!empty($dirname))
//			{
//				$dirname = htmlspecialchars($dirname, ENT_COMPAT, 'UTF-8');
//				JError::raiseWarning(100, JText::sprintf('COM_MEDIA_ERROR_UNABLE_TO_BROWSE_FOLDER_WARNDIRNAME', $dirname));
//                                return;
//			}


                
                
                $folder = $app->input->get('folder', '', 'path');
                        
		// Get some paths from the request.
		$base = JPATH_SITE.'/media/'.$folder;

		// Get the list of folders.
		$files = JFolder::files($base, '.', false, true);

		$count = 0;

		foreach ($files as $file)
		{
                        // Retrieve file details.
                        $ext = strtolower(JFile::getExt($file));

                        // Check if the file has an allowed extension.
                        $query = $db->getQuery(true)
                                ->select('id')
                                ->from('#__hwdms_ext')
                                ->where($db->quoteName('ext') . ' = ' . $db->quote($ext))
                                ->where($db->quoteName('published') . ' = ' . $db->quote(1));
                        $db->setQuery($query);
                        try
                        {
                                $db->execute(); 
                                $ext_id = $db->loadResult();                   
                        }
                        catch (RuntimeException $e)
                        {
                                $this->setError($e->getMessage());
                                return false;                            
                        }

                        // If the extension is allowed, then count it.
                        if ($ext_id > 0)
                        {
                                $count++;
                        }
		}

                $this->count = $count;
                $this->folder = $folder;

		// Display the template.
                parent::display('scan');
	}        
}

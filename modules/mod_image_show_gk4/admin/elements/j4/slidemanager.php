<?php

defined('JPATH_BASE') or die;

use Joomla\CMS\Component\ComponentHelper;

class JFormFieldSlidemanager extends JFormField {
	protected $type = 'Slidemanager';

	protected function getInput() {
		$add_form = '<div id="gk_tab_manager"><div id="gk_tab_add_header">'.JText::_('MOD_IMAGE_SHOW_GK4_FORM_ITEMS_CREATED').'<a href="#add">'.JText::_('MOD_IMAGE_SHOW_GK4_FORM_ITEM_ADD').'</a></div><div id="gk_tab_add_form">'.$this->getForm('add').'</div></div>';
		
		$edit_form = $this->getForm('edit');
		
		$item_form = '<div id="invisible"><div class="gk_tab_item"><div class="gk_tab_item_desc"><span class="gk_tab_item_name"></span><span class="gk_tab_item_order_down" title="'.JText::_('MOD_IMAGE_SHOW_GK4_FORM_ITEM_ORDER_DOWN').'"></span><span class="gk_tab_item_order_up" title="'.JText::_('MOD_IMAGE_SHOW_GK4_FORM_ITEM_ORDER_UP').'"></span><a href="#remove" class="gk_tab_item_remove" title="'.JText::_('MOD_IMAGE_SHOW_GK4_FORM_ITEM_REMOVE').'">'.JText::_('MOD_IMAGE_SHOW_GK4_FORM_ITEM_REMOVE').'</a><a href="#edit" class="gk_tab_item_edit" title="'.JText::_('MOD_IMAGE_SHOW_GK4_FORM_ITEM_EDIT').'">'.JText::_('MOD_IMAGE_SHOW_GK4_FORM_ITEM_EDIT').'</a><span class="gk_tab_item_type"></span><span class="gk_tab_item_access"></span><span class="gk_tab_item_state published"><span>'.JText::_('MOD_IMAGE_SHOW_GK4_FORM_ITEM_PUBLISHED').'</span><span>'.JText::_('MOD_IMAGE_SHOW_GK4_FORM_ITEM_UNPUBLISHED').'</span></span><a rel="{handler:\'image\'}" class="gk-modal modal-img" title="'.JText::_('MOD_IMAGE_SHOW_GK4_FORM_ITEM_PREVIEW').'">'.JText::_('MOD_IMAGE_SHOW_GK4_FORM_ITEM_PREVIEW').'</a></div><div class="gk_tab_editor_scroll"><div class="gk_tab_item_editor">'.$edit_form.'</div></div></div></div>';
		
		$tabs_list = '<div id="tabs_list"></div>';
		$option_value = str_replace("\'", "'", $this->value);
		$option_value = str_replace(array('{\"', '\":', ':\"', '\",', ',\"', '\"}'), '"', $option_value);
		$textarea = '<textarea class="form-control" name="'.$this->name.'" id="'.$this->id.'" rows="20" cols="50">'.$option_value.'</textarea>';
		return $item_form . $add_form . $tabs_list . $textarea;
	}
	
	private function getForm($type = 'add') {
	
        // form_type
        $form_type_tooltip = ($type == 'add') ? ' class="hasTip" title="' . JText::_('MOD_IMAGE_SHOW_GK4_FORM_TYPE_TOOLTIP') . '"' : '';
        $form_type = '<div class="control-group"><div class="control-label"><label'.$form_type_tooltip.'>'.JText::_('MOD_IMAGE_SHOW_GK4_FORM_TYPE').'</label></div><div class="controls"><select class="form-control custom-select gk_tab_'.$type.'_type"><option value="article">'.JText::_('MOD_IMAGE_SHOW_GK4_TYPE_ARTICLE').'</option><option value="text" selected="selected">'.JText::_('MOD_IMAGE_SHOW_GK4_TYPE_TEXT').'</option><option value="k2">'.JText::_('MOD_IMAGE_SHOW_GK4_TYPE_K2').'</option></select></div></div>';
        
        // form_image
        $form_image_tooltip = ($type == 'add') ? ' class="hasTip" title="' . JText::_('MOD_IMAGE_SHOW_GK4_FORM_IMAGE_TOOLTIP') . '"' : '';
        $form_image = '';
        if($type == 'add') {
    		// Build the script.
    		$script = array();
    		$script[] = '	function jInsertFieldValue(value,id) {';
    		$script[] = '		var old_id = document.getElementById(id).value;';
    		$script[] = '		if (old_id != id) {';
    		$script[] = '			document.getElementById(id).value = value;';
    		$script[] = '		}';
    		$script[] = '	}';
    		// Add the script to the document head.
    		JFactory::getDocument()->addScriptDeclaration(implode("\n", $script));
        	// label
        	$form_image .= '<div class="control-group"><div class="control-label"><label'.$form_image_tooltip.'>'.JText::_('MOD_IMAGE_SHOW_GK4_IMAGE_TYPE').'</label></div><div class="controls">';
        	// The text field.
        	$form_image .= '
          <joomla-field-media class="field-media-wrapper" type="image" base-path="'.Juri::root().'" root-folder="images" url="index.php?option=com_media&amp;tmpl=component&amp;asset=com_templates&amp;author=&amp;fieldid={field-media-id}&amp;path=" modal-container=".modal" modal-width="100%" modal-height="400px" input=".field-media-input" button-select=".button-select" button-clear=".button-clear" button-save-selected=".button-save-selected" preview="static" preview-container=".field-media-preview" preview-width="200" preview-height="200">
          <div id="imageModal_gk_tab_'.$type.'_image" role="dialog" tabindex="-1" class="joomla-modal modal fade" data-url="index.php?option=com_media&amp;tmpl=component&amp;asset=com_templates&amp;author=&amp;fieldid={field-media-id}&amp;path=" data-iframe="<iframe class=&quot;iframe&quot; src=&quot;index.php?option=com_media&amp;amp;tmpl=component&amp;amp;asset=com_templates&amp;amp;author=&amp;amp;fieldid={field-media-id}&amp;amp;path=&quot; name=&quot;Change Image&quot; height=&quot;100%&quot; width=&quot;100%&quot;></iframe>">
          <div class="modal-dialog modal-lg jviewport-width80" role="document">
            <div class="modal-content">
              <div class="modal-header">
              <h3 class="modal-title">Change Image</h3>
                <button type="button" class="close novalidate" data-dismiss="modal">×</button>
          </div>
        <div class="modal-body jviewport-height60">
          </div>
        <div class="modal-footer">
          <button class="btn btn-secondary button-save-selected">Select</button><button class="btn btn-secondary" data-dismiss="modal">Cancel</button></div>
            </div>
          </div>
        </div>
              <div class="field-media-preview" style="height:auto">
               <div id="gk_tab_'.$type.'_image_preview_empty">No image selected.</div>      
               <div id="gk_tab_'.$type.'_image_preview_img"><img src="'.JPATH_ROOT.'." alt="Selected image." id="gk_tab_'.$type.'_image_preview" class="media-preview" style="max-width:200px;max-height:200px;"></div>   </div>
            <div class="input-group">
            <input name="jform[params][img]" id="jform_params_img" value="" readonly="readonly" class="gk_tab_'.$type.'_image form-control hasTooltip field-media-input" type="text">
                  <div class="input-group-btn">
                <a class="btn btn-secondary button-select">Select</a>
                <a class="btn btn-secondary hasTooltip button-clear" title="Clear"><span class="icon-remove" aria-hidden="true"></span></a>
              </div>
              </div>
        </joomla-field-media></div></div>';
        } else {
        	$form_image = '';
        	// label
        	$form_image .= '<div class="control-group"><div class="control-label"><label'.$form_image_tooltip.'>'.JText::_('MOD_IMAGE_SHOW_GK4_IMAGE_TYPE').'</label></div><div class="controls">';
        	// The text field.
        	$form_image .= '
            <joomla-field-media class="field-media-wrapper" type="image" base-path="'.Juri::root().'" root-folder="images" url="index.php?option=com_media&amp;tmpl=component&amp;asset=com_templates&amp;author=&amp;fieldid={field-media-id}&amp;path=" modal-container=".modal" modal-width="100%" modal-height="400px" input=".field-media-input" button-select=".button-select" button-clear=".button-clear" button-save-selected=".button-save-selected" preview="static" preview-container=".field-media-preview" preview-width="200" preview-height="200">
              <div id="imageModal_gk_tab_'.$type.'_image" role="dialog" tabindex="-1" class="joomla-modal modal fade" data-url="index.php?option=com_media&amp;tmpl=component&amp;asset=com_templates&amp;author=&amp;fieldid={field-media-id}&amp;path=" data-iframe="<iframe class=&quot;iframe&quot; src=&quot;index.php?option=com_media&amp;amp;tmpl=component&amp;amp;asset=com_templates&amp;amp;author=&amp;amp;fieldid={field-media-id}&amp;amp;path=&quot; name=&quot;Change Image&quot; height=&quot;100%&quot; width=&quot;100%&quot;></iframe>">
              <div class="modal-dialog modal-lg jviewport-width80" role="document">
                <div class="modal-content">
                  <div class="modal-header">
                  <h3 class="modal-title">Change Image</h3>
                    <button type="button" class="close novalidate" data-dismiss="modal">×</button>
              </div>
            <div class="modal-body jviewport-height60">
              </div>
            <div class="modal-footer">
              <button class="btn btn-secondary button-save-selected">Select</button><button class="btn btn-secondary" data-dismiss="modal">Cancel</button></div>
                </div>
              </div>
            </div>
                  <div class="field-media-preview" style="height:auto">
                   <div id="gk_tab_'.$type.'_image_preview_empty">No image selected.</div>      <div id="gk_tab_'.$type.'_image_preview_img"><img src="'.JPATH_ROOT.'." alt="Selected image." id="gk_tab_'.$type.'_image_preview" class="media-preview" style="max-width:200px;max-height:200px;"></div>   </div>
                <div class="input-group">
                <input name="jform_params_edit_img" id="jform_params_edit_img" value="" readonly="readonly" class="gk_tab_'.$type.'_image form-control hasTooltip field-media-input" type="text">
                      <div class="input-group-btn">
                    <a class="btn btn-secondary button-select">Select</a>
                    <a class="btn btn-secondary hasTooltip button-clear" title="Clear"><span class="icon-remove" aria-hidden="true"></span></a>
                  </div>
                  </div>
            </joomla-field-media></div></div>';
        }
        
        // form_stretch
        $form_stretch_tooltip = ($type == 'add') ? ' class="hasTip" title="' . JText::_('MOD_IMAGE_SHOW_GK4_FORM_STRETCH_TOOLTIP') . '"' : '';
        $form_stretch = '<div class="control-group"><div class="control-label"><label'.$form_stretch_tooltip.'>'.JText::_('MOD_IMAGE_SHOW_GK4_FORM_STRETCH').'</label></div><div class="controls"><select class="form-control custom-select gk_tab_'.$type.'_stretch"><option value="nostretch" selected="selected">'.JText::_('MOD_IMAGE_SHOW_GK4_FORM_OPTION_NOSTRETCH').'</option><option value="stretch">'.JText::_('MOD_IMAGE_SHOW_GK4_FORM_OPTION_STRETCH').'</option></select></div></div>';
        
        // form_access_level
        $form_access_level_tooltip = ($type == 'add') ? ' class="hasTip" title="' . JText::_('MOD_IMAGE_SHOW_GK4_FORM_ACCESS_TOOLTIP') . '"' : '';
        $form_access_level = '<div class="control-group"><div class="control-label"><label'.$form_access_level_tooltip.'>'.JText::_('MOD_IMAGE_SHOW_GK4_FORM_ACCESS').'</label></div><div class="controls"><select class="form-control custom-select gk_tab_'.$type.'_content_access"><option value="public">'.JText::_('MOD_IMAGE_SHOW_GK4_FORM_ACCESS_PUBLIC').'</option><option value="registered">'.JText::_('MOD_IMAGE_SHOW_GK4_FORM_ACCESS_REGISTERED').'</option></select></div></div>';
        
        // form_published
        $form_published_tooltip = ($type == 'add') ? ' class="hasTip" title="' . JText::_('MOD_IMAGE_SHOW_GK4_FORM_PUBLISHED_TOOLTIP') . '"' : '';
        $form_published = '<div class="control-group"><div class="control-label"><label'.$form_published_tooltip.'>'.JText::_('MOD_IMAGE_SHOW_GK4_FORM_PUBLISHED').'</label></div><div class="controls"><select class="form-control custom-select gk_tab_'.$type.'_published"><option value="1">'.JText::_('MOD_IMAGE_SHOW_GK4_PUBLISHED').'</option><option value="0">'.JText::_('MOD_IMAGE_SHOW_GK4_UNPUBLISHED').'</option></select></div></div>';
        
        // form_name
        $form_name_tooltip = ($type == 'add') ? ' class="hasTip" title="' . JText::_('MOD_IMAGE_SHOW_GK4_FORM_NAME_TOOLTIP') . '"' : '';
        $form_name = '<div class="control-group"><div class="control-label"><label'.$form_name_tooltip.'>'.JText::_('MOD_IMAGE_SHOW_GK4_FORM_NAME').'</label></div><div class="controls"><input type="text" class="form-control gk_tab_'.$type.'_name" /></div></div>';
        
        // form_content
        $form_content_tooltip = ($type == 'add') ? ' class="hasTip" title="' . JText::_('MOD_IMAGE_SHOW_GK4_FORM_CONTENT_TOOLTIP') . '"' : '';
        $form_content = '<div class="control-group"><div class="control-label"><label'.$form_content_tooltip.'>'.JText::_('MOD_IMAGE_SHOW_GK4_FORM_CONTENT').'</label></div><div class="controls"><textarea class="form-control gk_tab_'.$type.'_content"></textarea></div></div>';
        
        // form_alt
        $form_alt_tooltip = ($type == 'add') ? ' class="hasTip" title="' . JText::_('MOD_IMAGE_SHOW_GK4_FORM_ALT_TOOLTIP') . '"' : '';
        $form_alt = '<div class="control-group"><div class="control-label"><label'.$form_alt_tooltip.'>'.JText::_('MOD_IMAGE_SHOW_GK4_FORM_ALT').'</label></div><div class="controls"><input type="text" class="form-control gk_tab_'.$type.'_alt" /></div></div>';         
        
        // form_url
        $form_url_tooltip = ($type == 'add') ? ' class="hasTip" title="' . JText::_('MOD_IMAGE_SHOW_GK4_FORM_URL_TOOLTIP') . '"' : '';
        $form_url = '<div class="control-group"><div class="control-label"><label'.$form_url_tooltip.'>'.JText::_('MOD_IMAGE_SHOW_GK4_FORM_URL').'</label></div><div class="controls"><input type="text" class="form-control gk_tab_'.$type.'_url" /></div></div>';
         // form_article K2
        if($type == 'add') {
            $form_articleK2_tooltip = ($type == 'add') ? ' class="hasTip" title="' . JText::_('MOD_IMAGE_SHOW_GK4_FORM_ARTICLEK2_TOOLTIP') . '"' : '';
        	$form_articleK2 = '<div class="control-group gk_tab_add_artK2"><div class="control-label"><label'.$form_articleK2_tooltip.'>'.JText::_('MOD_IMAGE_SHOW_GK4_FORM_ARTICLEK2').'</label></div><div class="controls"><input class="form-control" type="text" id="jform_request_artK2_name" value="" disabled="disabled" size="25"><a class="btn gk-modal modal-art" title="Select or Change article" href="index.php?option=com_k2&amp;view=items&amp;task=element&amp;tmpl=component&amp;object=jform_request_artK2_add";function=jSelectItem_add" rel="{handler: \'iframe\', size: {x: 800, y: 450}}">Select / Change</a><input type="hidden" id="jform_request_artK2_add" class="modal-value" name="jform[request][id]" value="" /></div></div>';
        } else {
        $form_articleK2 = '<div class="control-group gk_tab_edit_artK2"><div class="control-label"><label>'.JText::_('MOD_IMAGE_SHOW_GK4_FORM_ARTICLEK2').'</label></div><div class="controls"><input class="form-control" type="text" id="jform_request_edit_artK2_name" class="modal-artK2-name" value="" disabled="disabled" size="25"><a class="btn gk-modal modal-art" title="Select or Change article" href="index.php?option=com_k2&amp;view=items&amp;task=element&amp;tmpl=component&amp;object=jform_request_edit_artK2";function=jSelectItem2" rel="{handler: \'iframe\', size: {x: 800, y: 450}}">Select / Change</a><input type="hidden" id="jform_request_edit_artK2" class="modal-value jform_request_edit_artK2" name="jform[request][id]" value="" /></div></div>';
        }
        JFactory::getDocument()->addScriptDeclaration('function jSelectItem(id, title, object) { 
                if($currently_opened != 0) { 
                document.id("jform_request_edit_artK2_" +  $currently_opened).value = id; 
                document.id("jform_request_edit_artK2_name_" + $currently_opened).value = title; 
                } else {
                document.id("jform_request_artK2_add").value = id; 
                document.id("jform_request_artK2_name").value = title; 
                }
                SqueezeBox.close();}');
       // href="index.php?option=com_k2&amp;view=items&amp;task=element&amp;tmpl=component&amp;object=jform_artK2"
        // form_articlestyles
         if($type == 'add') {
        	$form_article_tooltip = ($type == 'add') ? ' class="hasTip" title="' . JText::_('MOD_IMAGE_SHOW_GK4_FORM_ARTICLE_TOOLTIP') . '"' : '';
        	$form_article = '<div class="control-group gk_tab_add_art"><div class="control-label"><label'.$form_article_tooltip.'>'.JText::_('MOD_IMAGE_SHOW_GK4_FORM_ARTICLE').'</label></div><div class="controls"><input class="form-control" type="text" id="jform_request_art_name" value="" disabled="disabled" size="25"><a class="btn gk-modal modal-art" title="Select or Change article" href="index.php?option=com_content&amp;view=articles&amp;layout=modal&amp;tmpl=component&amp;function=jSelectArticle_jform_request_art_add" rel="{handler: \'iframe\', size: {x: 800, y: 450}}">Select / Change</a><input type="hidden" id="jform_request_art_add" class="modal-value" name="jform[request][id]" value="" /></div></div>';
        
        	JFactory::getDocument()->addScriptDeclaration('function jSelectArticle_jform_request_art_add(id, title, catid, object) { document.id("jform_request_art_add").value = id; document.id("jform_request_art_name").value = title; SqueezeBox.close(); }');
        } else {
        	$form_article = '<div class="control-group gk_tab_edit_art"><div class="control-label"><label>'.JText::_('MOD_IMAGE_SHOW_GK4_FORM_ARTICLE').'</label></div><div class="controls"><input type="text" id="jform_request_edit_art_name" class="form-control modal-art-name" value="" disabled="disabled" size="25"><a class="btn gk-modal modal-art" title="Select or Change article" href="index.php?option=com_content&amp;view=articles&amp;layout=modal&amp;tmpl=component&amp;function=jSelectArticle_jform_request_edit_art" rel="{handler: \'iframe\', size: {x: 800, y: 450}}">Select / Change</a><input type="hidden" id="jform_request_edit_art" class="modal-value jform_request_edit_art" name="jform[request][id]" value="" /></div></div>';
        	
        	JFactory::getDocument()->addScriptDeclaration('function jSelectArticle_jform_request_edit_art(id, title, catid, object) { document.id("jform_request_edit_art_" + $currently_opened).value = id; document.id("jform_request_edit_art_name_" + $currently_opened).value = title; SqueezeBox.close(); }');
        }
        
        // form_buttons
        $form_buttons = '<div class="control-group gk_tab_'.$type.'_submit"><div class="controls"><a href="#save" class="btn btn-success">'.JText::_('MOD_IMAGE_SHOW_GK4_FORM_SAVE').'</a><a href="#cancel" class="btn">'.JText::_('MOD_IMAGE_SHOW_GK4_FORM_CANCEL').'</a></div></div>';
        
        
		// create the form
		$form = '<div class="height_scroll"><div class="gk_tab_'.$type.'">'.$form_type.$form_image.$form_stretch.$form_access_level.$form_published.$form_name.$form_alt.$form_content.$form_url.$form_article.$form_articleK2.$form_buttons.'</div></div>';
		
		return $form;
	}
}
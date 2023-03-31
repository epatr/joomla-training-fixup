<?php
/**
* @copyright (C) 2013 iJoomla, Inc. - All rights reserved.
* @license GNU General Public License, version 2 (http://www.gnu.org/licenses/gpl-2.0.html)
* @author iJoomla.com <webmaster@ijoomla.com>
* @url https://www.jomsocial.com/license-agreement
* The PHP code portions are distributed under the GPL license. If not otherwise stated, all images, manuals, cascading style sheets, and included JavaScript *are NOT GPL, and are released under the IJOOMLA Proprietary Use License v1.0
* More info at https://www.jomsocial.com/license-agreement
*/
defined( '_JEXEC' ) or die( 'Restricted access' );
require_once(JPATH_ROOT . '/components/com_community/libraries/core.php');

class plgSystemForceFields extends JPlugin
{	
	public function onInstallerBeforePackageDownload(&$url, &$headers)
	{	
        if (strpos($url, 'https://www.jomsocial.com/') !== 0) {
            return true;
        }
        
	    $lang = JFactory::getLanguage();
        $extension = 'com_community';
        $base_dir = JPATH_ADMINISTRATOR;
        $language_tag = '';
        $lang->load($extension, $base_dir, $language_tag, true);

        $domain = $_SERVER['HTTP_HOST'];
        $domain = str_replace("https://", "", $domain);
        $domain = str_replace("http://", "", $domain);
        $component = "community";
        $valid_license = false;

        $config = CFactory::getConfig();
        $license_number = $config->get('registerlicense');

        if(trim($license_number) == ""){
            $app = JFactory::getApplication();
            $app->redirect("index.php?option=com_community&view=configuration&cfgSection=license", JText::_("COM_COMMUNITY_LICENSE_EMPTY_MESSAGE"), "error");
        } else {
            // start check license on jomsocial.com
            $check_url = "https://www.jomsocial.com/index.php?option=com_digistore&controller=digistoreAutoinstaller&task=get_license_number_details&tmpl=component&format=raw&component=".$component."&domain=".urlencode($domain)."&license=".trim($license_number);
            $extensions = get_loaded_extensions();
            $text = "";

            $license_details = file_get_contents($check_url);
            
            if (isset($license_details) && trim($license_details) != "") {
                $license_details = json_decode($license_details, true);

                if (isset($license_details["0"])) {
                    $license_details = $license_details["0"];
                    $productid = $license_details['productid'];
                } else {
                    // license not exists
                    $app = JFactory::getApplication();
                    $app->redirect("index.php?option=com_community&view=configuration&cfgSection=license", JText::sprintf('COM_COMMUNITY_GET_LICENSE_HERE', 'https://www.jomsocial.com/component/digistore/licenses?Itemid=209'), "error");
                    die();
                }
                
                if (isset($license_details["expires"]) && trim($license_details["expires"]) != "" && trim($license_details["expires"]) == "0000-00-00 00:00:00") {
                    $valid_license = true;
                } elseif (isset($license_details["expires"]) && trim($license_details["expires"]) != "" && trim($license_details["expires"]) != "0000-00-00 00:00:00") {
                    $now = strtotime(date("Y-m-d H:i:s"));
                    $license_expires = strtotime(trim($license_details["expires"]));

                    if ($license_expires >= $now) {
                        $valid_license = true;
                    } else {
                        $app = JFactory::getApplication();
                        $app->redirect("index.php?option=com_community&view=configuration&cfgSection=license", JText::sprintf('COM_COMMUNITY_EXPIRED_LICENSE_NUMBER', 'https://www.jomsocial.com/component/digistore/licenses?Itemid=209'), "error");
                        die();
                    }
                }
            }
        }

        if(!$valid_license){
            $app = JFactory::getApplication();
            $app->redirect("index.php?option=com_community&view=configuration&cfgSection=license", JText::sprintf('COM_COMMUNITY_GET_LICENSE_HERE', 'https://www.jomsocial.com/component/digistore/licenses?Itemid=209'), "error");
        } else {
            $itspro = 0;
            if (COMMUNITY_PRO_VERSION) $itspro = 1;
            // get download URL
            $url_request = "https://www.jomsocial.com/index.php?option=com_digistore&controller=digistoreAutoinstaller&task=update_extension&tmpl=component&format=raw&component=community_plugin&site=".urlencode($domain)."&license=".trim($license_number)."&itspro=".$itspro."&filename=plg_forcefields";
            $page_content = file_get_contents($url_request);

            if($page_content === FALSE || trim($page_content) == ""){
                $curl = curl_init();
                curl_setopt ($curl, CURLOPT_URL, $url_request);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                $page_content = curl_exec ($curl);
                curl_close ($curl);
            }

            if(isset($page_content) && trim($page_content) != ""){
                $url = $page_content;
            }
            
            if(!isset($url) || trim($url) == "" || trim($url) == "https://www.jomsocial.com/" ){
                $app = JFactory::getApplication();
                $app->redirect("index.php?option=com_installer&view=update", JText::sprintf('COM_COMMUNITY_PACKAGE_DOWNLOAD_UPDATE', 'https://www.jomsocial.com/component/digistore/licenses?Itemid=209'), "JomSocial Update");
            }
        }

        return true;
	}

	function __construct($subject, $config)
	{
		parent::__construct($subject, $config);
	}

	function onAfterDispatch()
	{		
		$mainframe = JFactory::getApplication();
		if($mainframe->isAdmin()) return;

		//If its frontend, include JomSocial Core Libraries only if they exist
		$jscore = JPATH_ROOT .'/components/com_community/libraries/core.php' ;
			if (file_exists ( $jscore )) {
				include_once ($jscore);
			} else {
				return true;
			}

		// Return if visitor is not registered
		$user = CFactory::getUser();
		if(empty($user) || $user->id == 0){
			return true;
		}
		
		// Force Admin
		$forceadmin = $this->params->get( 'forceadmin' );
		if($forceadmin == 0) {
			if (COwnerHelper::isCommunityAdmin()){
				return true;
			}
		}
		
		//Load the language file - we might dont need this anymore for Joomla 3.1
		JPlugin::loadLanguage( 'plg_system_forcefields', JPATH_ADMINISTRATOR );
		
		// Let Alex figure this out
		// set the jinput
		$jinput 	= $mainframe->input;
		$uri = 		JRequest::getURI();
		$option	=	$jinput->get('option','','GET');
		$task	=	$jinput->get('task','','GET');
		$view	=	$jinput->get('view','','GET');
		
		//Compatibility fix with Force AVATAR - Just a redirection thing
		if($option=='com_community' && $task=='uploadAvatar' && $view=='profile'){
			return true;
		}
		
		if($option=='com_community' && $task=='edit' && $view=='profile'){
			return true;
		}
		if($option=='com_community' && $task=='changeprofile' && $view=='multiprofile'){
			return true;
		}
		if($option=='com_community' && $task=='updateProfile' && $view=='multiprofile'){
			return true;
		}
		if($option=='com_community' && $task=='profileupdated' && $view=='multiprofile'){
			return true;
		}
		
		$model = CFactory::getModel( 'profile' );
		$profile = $model->getEditableProfile($user->id , $user->getProfileType() );		
		$fields = $profile ['fields'];
		
		//verify that required custom profile fields are existing
		foreach ( $fields as $name => $fieldGroup )
		{
			if ($name != 'ungrouped'){
				foreach ( $fieldGroup as $field ){
					if ($field['required']){
						if( !CProfileLibrary::validateField( $field['id'], $field['type'] , $field['value'] , $field['required']) ){
							$url = 'index.php?option=com_community&view=profile&task=edit';
							$message = JText::_('PLG_FORCEFIELDS_MSG');
							$mainframe->enqueueMessage( CTemplate::quote($message) , 'error' );
							$mainframe->redirect($url);
						}
					}
				}
			}
		}
		
		return true;
	}
	
}
<?php
/*------------------------------------------------------------------------
# com_guru
# ------------------------------------------------------------------------
# author    iJoomla
# copyright Copyright (C) 2013 ijoomla.com. All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Websites: http://www.ijoomla.com
# Technical Support:  Forum - http://www.ijoomla.com.com/forum/index/
-------------------------------------------------------------------------*/
defined( '_JEXEC' ) or die( 'Restricted access' );
jimport ("joomla.aplication.component.model");


class guruAdminModelguruCertificate extends JModelLegacy {
	var $_packages;
	var $_package;
	var $_tid = null;
	var $_total = 0;
	var $_pagination = null;

	
	function savedesigncert($t) {
		//$post_value = JFactory::getApplication()->input->post->getArray();
		$db = JFactory::getDBO();
		
		$library_pdf = JFactory::getApplication()->input->get("library_pdf", "", "raw");
		$certificate_sett = JFactory::getApplication()->input->get("certificate_sett", "", "raw");
		$image = JFactory::getApplication()->input->get("image", "", "raw");
		$st_donecolor1 = JFactory::getApplication()->input->get("st_donecolor1", "", "raw");
		$st_donecolor2 = JFactory::getApplication()->input->get("st_donecolor2", "", "raw");
		$avg_cert = JFactory::getApplication()->input->get("avg_cert", "70", "raw");
		$certificate = JFactory::getApplication()->input->get("certificate", "", "raw");
		$certificate_page = JFactory::getApplication()->input->get("certificate_page", "", "raw");
		$email_template = JFactory::getApplication()->input->get("email_template", "", "raw");
		$email_mycertificate = JFactory::getApplication()->input->get("email_mycertificate", "", "raw");
		$subjectt3 = JFactory::getApplication()->input->get("subjectt3", "", "raw");
		$subjectt4 = JFactory::getApplication()->input->get("subjectt4", "", "raw");
		$font = JFactory::getApplication()->input->get("font", "", "raw");
		$library_pdf = JFactory::getApplication()->input->get("library_pdf", "", "raw");
		$certificatepdf = JFactory::getApplication()->input->get("certificatepdf", "", "raw");
		
		if(!file_exists(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_guru'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'MPDF') && $post_value["library_pdf"] == 1){
			$app = JFactory::getApplication('administrator');
			$msg = JText::_('GURU_NO_MPDF_MSG');

			if($t == 'a'){
				$app->enqueueMessage($msg, "error");
				$app->redirect('index.php?option=com_guru&controller=guruCertificate');
			}
			elseif($t == 's'){
				$app->enqueueMessage($msg, "error");
				$app->redirect('index.php?option=com_guru');
			}
		}
		
		$sql = "UPDATE #__guru_certificates set general_settings='".addslashes(trim($certificate_sett))."', design_background= '".addslashes(trim($image))."',	design_background_color ='".addslashes(trim($st_donecolor1))."', design_text_color='".addslashes(trim($st_donecolor2))."', avg_cert='".addslashes(trim($avg_cert))."', templates1='".addslashes(trim($certificate))."', templates2='".addslashes(trim($certificate_page))."', templates3='".addslashes(trim($email_template))."', templates4='".addslashes(trim($email_mycertificate))."', subjectt3='".addslashes(trim($subjectt3))."', subjectt4='".addslashes(trim($subjectt4))."', font_certificate='".addslashes(trim($font))."' , library_pdf = '".addslashes(trim($library_pdf))."', templatespdf='".addslashes($certificatepdf)."' ";
		$db->setQuery($sql);
		$db->execute();
		return true;
	}	

   public static function getCertificatesDetails(){
  		$db = JFactory::getDBO();
		$sql = "SELECT * from #__guru_certificates where id='1'";
		$db->setQuery($sql);
		$db->execute();
		$result = $db->loadObject();
		return $result;
   }
};
?>
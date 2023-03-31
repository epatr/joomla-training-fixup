<?php
// Set flag that this is a parent file
define( '_JEXEC', 1 );

// no direct access
defined ( '_JEXEC' ) or die ( 'Restricted access' ); 

error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE);

define('JPATH_BASE', realpath(dirname(__FILE__).'/../../../../..') );
define( 'DS', DIRECTORY_SEPARATOR );

require_once ( JPATH_BASE .DS.'includes'.DS.'defines.php' );
require_once ( JPATH_BASE .DS.'includes'.DS.'framework.php' );

JDEBUG ? $_PROFILER->mark( 'afterLoad' ) : null;

$mainframe = JFactory::getApplication('site');

$mainframe->initialise();
$db			= JFactory::getDBO();

$query = 'SELECT params ' 
			. ' FROM #__extensions '
			. ' WHERE element ='.$db->Quote('captcha')
			;
/*			
$query = 'SELECT params ' 
			. ' FROM #__plugins '
			. ' WHERE element ='.$db->Quote('captcha')
			;*/
$db->setQuery($query);

$params = $db->loadResult();
$params = new JRegistry($params);
		
		
include 'securimage.php';

$img = new Securimage();

// Change some settings

$img->image_width		= (int) $params->get('captcha_systems-securImage2-width', 	150);
$img->image_height		= (int) $params->get('captcha_systems-securImage2-height', 	70);
$img->perturbation		= (float) $params->get('captcha_systems-securImage2-perturbation',	0.7); // 1.0 = high distortion, higher numbers = more distortion
$img->code_length		= (int) $params->get('captcha_systems-securImage2-length',	4);
$img->image_bg_color	= new Securimage_Color("#".$params->get('captcha_systems-securImage2-bg_color',		'FFFFFF'));
$img->text_color		= new Securimage_Color("#".$params->get('captcha_systems-securImage2-text_color', 	'3D3D3D'));
$img->line_color		= new Securimage_Color("#".$params->get('captcha_systems-securImage2-line_color', 	'3D3D3D'));
$img->signature_color	= new Securimage_Color("#".$params->get('captcha_systems-securImage2-signature_color', 	'FFFFFF'));
$img->image_signature	= $params->get('captcha_systems-securImage2-image_signature', 	'');
$img->num_lines			= (int) $params->get('captcha_systems-securImage2-number_lines', 	8);

$img->text_angle_minimum	= $params->get('captcha_systems-securImage2-text_angle_minimum',	0);
$img->text_angle_maximum	= $params->get('captcha_systems-securImage2-text_angle_maximum',	0);


$img->show(); // alternate use:  $img->show('/path/to/background_image.jpg');

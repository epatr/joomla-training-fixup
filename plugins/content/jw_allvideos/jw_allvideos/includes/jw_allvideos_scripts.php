<?php
/*
// JoomlaWorks "AllVideos" Plugin for Joomla! 1.5.x - Version 3.3
// Copyright (c) 2006 - 2010 JoomlaWorks Ltd. All rights reserved.
// Released under the GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
// More info at http://www.joomlaworks.gr
// Designed and developed by the JoomlaWorks team
// *** Last update: February 18th, 2010 ***
*/

define('_JEXEC',1);
defined( '_JEXEC' ) or die( 'Restricted access' );

if(!defined('DS')) define('DS', DIRECTORY_SEPARATOR);

ob_start ("ob_gzhandler"); 
header("Content-type: text/javascript; charset: UTF-8"); 
header("Cache-Control: must-revalidate"); 
header("Expires: ".gmdate("D, d M Y H:i:s", time() + 60 * 60)." GMT");

// Includes
include(dirname( __FILE__ ).DIRECTORY_SEPARATOR."players".DIRECTORY_SEPARATOR."wmvplayer".DIRECTORY_SEPARATOR."silverlight.js");
echo "\n\n";
include(dirname( __FILE__ ).DIRECTORY_SEPARATOR."players".DIRECTORY_SEPARATOR."wmvplayer".DIRECTORY_SEPARATOR."wmvplayer.js");
echo "\n\n";
include(dirname( __FILE__ ).DIRECTORY_SEPARATOR."players".DIRECTORY_SEPARATOR."quicktimeplayer".DIRECTORY_SEPARATOR."AC_QuickTime.js");
echo "\n\n";
include(dirname( __FILE__ ).DIRECTORY_SEPARATOR."jw_allvideos.js");
echo "\n\n";

ob_flush();

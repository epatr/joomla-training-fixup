<?php
// Set flag that this is a parent file
define( '_JEXEC', 1 );

// no direct access
defined ( '_JEXEC' ) or die ( 'Restricted access' ); 

///error_reporting(0);
include 'securimage.php';

$img = new Securimage();

$img->show(); // alternate use:  $img->show('/path/to/background.jpg');

?>

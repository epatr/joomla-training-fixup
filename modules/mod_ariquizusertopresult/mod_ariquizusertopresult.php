<?php
/*
 * ARI Quiz User Top Result Joomla! module
 *
 * @package		ARI Quiz User Top Result Joomla! module
 * @version		1.0.0
 * @author		ARI Soft
 * @copyright	Copyright (c) 2010 www.ari-soft.com. All rights reserved
 * @license		GNU/GPL (http://www.gnu.org/copyleft/gpl.html)
 * 
 */

defined('_JEXEC') or die('Restricted access');

$kernelPath = JPATH_ROOT . '/administrator/components/com_ariquiz/kernel/class.AriKernel.php';
if (file_exists($kernelPath))
{
	require_once $kernelPath;
	require dirname(__FILE__) . '/module.php';
}
else
	echo '<div style="color: red;">"ARI Quiz User Top Results" module requires "ARI Quiz" extension.</div>';
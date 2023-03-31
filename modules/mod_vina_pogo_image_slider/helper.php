<?php
/*
# ------------------------------------------------------------------------
# Vina Pogo Image Slider for Joomla 3
# ------------------------------------------------------------------------
# Copyright(C) 2015 www.VinaGecko.com. All Rights Reserved.
# @license http://www.gnu.org/licenseses/gpl-3.0.html GNU/GPL
# Author: VinaGecko.com
# Websites: http://vinagecko.com
# Forum:    http://vinagecko.com/forum/
# ------------------------------------------------------------------------
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.filesystem.file');
class modVinaPogoImageSliderHelper
{
    public static function getSildes($slider)
	{
        switch($slider->src)
		{
			case "dir":
				$rows = self::getDataFromDirectory($slider);
			break;
			default:
				$rows = $slider->list;
			break;
		}
		
		return $rows;
    }
	
	public static function getDataFromDirectory($slider)
    {
        $dir = $slider->dir->path;
		
        if(strrpos($dir,'/') != strlen($dir) -1) $dir .= '/';
        
		$files 		= JFolder::files($dir);
        $accept 	= explode(',', strtolower($slider->dir->ext));
        $outFiles 	= array();
        $i = 0;
		
        if(count($files))
		{
            foreach($files as $file)
            {
                $lastDot 	= strrpos($file, '.');
                $ext 		= substr($file, $lastDot);
            
                if(in_array(strtolower($ext), $accept))
                {
                    @$outFiles[$i]->img = $dir . $file;
                    $i++;
                }
            }
		}
		
        return $outFiles;
    }
	
	public static function resizeImage($type, $file, $prefix, $width, $height, $module)
	{
		jimport('joomla.filesystem.folder');
		jimport('joomla.filesystem.file');

		// Set noimage if the image isn't exists
		if(!JFile::exists($file)) {
			$file = JPATH_BASE . "/modules/". $module->module ."/assets/images/noimage.jpg";
		}

		// Check if new image is exists
		$newFile = JPATH_BASE . "/cache/". $module->module ."/" . $module->id . "/" . date("Y") ."/". $prefix . basename($file);
		$urlFile = JURI::base() . "cache/". $module->module ."/" . $module->id . "/" . date("Y") ."/". $prefix . basename($file);

		if(JFile::exists($newFile)) {
			return $urlFile;
		}
		else {
			JFolder::create(dirname($newFile));
		}

		// Instantiate our JImage object
		$image 			= new JImage($file);
		$resizedImage 	= $image->resize($width, $height, true, $type);

		$properties 	= JImage::getImageFileProperties($file);
		$mime 			= $properties->mime;

		if($mime == 'image/jpeg') {
			$type = IMAGETYPE_JPEG;
		}
		elseif($mime = 'image/png') {
			$type = IMAGETYPE_PNG;
		}
		elseif($mime = 'image/gif') {
			$type = IMAGETYPE_GIF;
		}

		// Store the resized image to a new file
		$resizedImage->toFile($newFile, $type);

		return $urlFile;
	}
}
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

$doc = JFactory::getDocument();
$doc->addScript('modules/'.$module->module.'/assets/js/jquery.pogo-slider.js', 'text/javascript');
$doc->addStyleSheet('modules/'.$module->module.'/assets/css/pogo-slider.css');
?>
<!-- style block -->
<style type="text/css">
#vina-pogo-slider<?php echo $module->id; ?> {
	<?php echo (!empty($moduleWidth)) ? "max-width: {$moduleWidth};" :""; ?>
	<?php echo (!empty($moduleHeight)) ? "max-height: {$moduleHeight};" :""; ?>
}
#vina-pogo-slider<?php echo $module->id; ?> .caption-block {
	<?php echo (!empty($captionStyle)) ? $captionStyle : ""; ?>
}
</style>

<!-- slideshow block -->
<div id="vina-pogo-slider<?php echo $module->id; ?>" class="vina-pogo-slider pogoSlider">
	<?php foreach($slides as $slide) : ?>
	<?php
		$image 	= $slide->img;
		
		if($resizeImage) {
			$image = modVinaPogoImageSliderHelper::resizeImage($resizeType, $image, '', $imageWidth, $imageHeight, $module);
		}
		else {
			$image = (strpos($image, 'http://') === false && strpos($image, 'https://') === false) ? JURI::base() . $image : $image;
		}
		
		if($slider->src == "dir") {
			$name = $slide->img;
			$text = "";
		}
		else {
			$name = $slide->name;
			$text = $slide->text;
		}
	?>
	<div class="pogoSlider-slide">
		<!-- Image Block -->
		<?php if(!empty($image)): ?>
		<img src="<?php echo $image; ?>" alt="<?php echo $name; ?>">
		<?php endif; ?>
		
		<!-- Caption Block -->
		<?php if(!empty($text) && $captionBlock): ?>
			<?php echo $text; ?>
		<?php endif; ?>
	</div>
	<?php endforeach; ?>
</div>


<!-- javascript block -->
<script type="text/javascript">
jQuery(document).ready(function ($) {
	$('#vina-pogo-slider<?php echo $module->id; ?>').pogoSlider({
		autoplay:					<?php echo $autoplay ? "true" : "false"; ?>,
		autoplayTimeout:			<?php echo $autoplayTimeout; ?>,
		displayProgess:				<?php echo $displayProgess ? "true" : "false"; ?>,
		slideTransition:			'<?php echo $slideTransition; ?>',
		slideTransitionDuration:	<?php echo $slideTransitionDuration; ?>,
		elementTransitionIn:		'<?php echo $elementTransitionIn; ?>',
		elementTransitionOut:		'<?php echo $elementTransitionOut; ?>',
		elementTransitionStart:		<?php echo $elementTransitionStart; ?>,
		elementTransitionDuration:	<?php echo $elementTransitionDuration; ?>,
		generateButtons:			<?php echo $generateButtons ? "true" : "false"; ?>,
		buttonPosition:				'<?php echo $buttonPosition; ?>',
		generateNav:				<?php echo $generateNav ? "true" : "false"; ?>,
		navPosition:				'<?php echo $navPosition; ?>',
		pauseOnHover:				<?php echo $pauseOnHover ? "true" : "false"; ?>,
		targetWidth:				<?php echo (!empty($moduleWidth)) ? intval($moduleWidth) : 940; ?>,
		targetHeight:				<?php echo (!empty($moduleHeight)) ? intval($moduleHeight) : 420; ?>,
		responsive: 				true,
	});
});
</script>
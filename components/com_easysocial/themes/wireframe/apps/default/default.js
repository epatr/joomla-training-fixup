
EasySocial.require()
.script('site/apps/apps')
.done(function($) {
	
	$('[data-es-apps]').implement(EasySocial.Controller.Apps, {
		"filter": "<?php echo $filter; ?>"
	});
});

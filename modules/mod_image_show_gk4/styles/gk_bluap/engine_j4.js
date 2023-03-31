jQuery(window).on("load",function(){
	jQuery(".gkIsWrapper-gk_bluap").each(function(i, el){
		var elID = jQuery(el).attr("id");
		var wrapper = jQuery('#'+elID);
		var $G = $Gavick[elID];
		var slides = [];
		var links = [];
		var imagesToLoad = [];
		var swipe_min_move = 30;
		var swipe_max_time = 500;
		// animation variables
		$G['animation_timer'] = false;
		// text block position
		$G['text_pos'] = wrapper.attr('data-textpos');
		// blank flag
		$G['blank'] = false;
		// load the images
		wrapper.find('.figure').each(function(i, _elm){
			let elm = jQuery(_elm);
			var newImg = jQuery('<img/>',{
				"title": elm.attr('data-title'),
				"alt": elm.attr('data-alt'),
				"class": 'gkIsSlide',
				"style": 'z-index: ' + elm.attr('data-zindex') + ';',
				"src": elm.attr('data-url')
			});
			links[i] = elm.attr('data-link');
			imagesToLoad.push(newImg);
			newImg.prependTo(elm.find('.figure-img'));
		});

		setTimeout(function(){
			wrapper.find('.gkIsPreloader').css('position', 'absolute');
			wrapper.find('.gkIsPreloader').fadeOut();
		}, 400);

		$G['actual_slide'] = 0;

		wrapper.animate({'height': wrapper.find('.figure').height()}, 350, function(){
			wrapper.find('.figure').fadeIn();
			wrapper.find('.figure').eq(0).addClass('active animated');
			wrapper.css('height', 'auto');
		});

		wrapper.addClass('loaded');

		wrapper.find(".gkIsSlide").each(function(i, elmt){
			slides[i] = jQuery(elmt);
		});

		setTimeout(function() {
			var initfig = slides[0].parent().parent().find('.figcaption');

			if(initfig) {
				if($G['text_pos'] == 'left') {
					initfig.animate({
						'opacity': 1,
						'left': '0%'
					}, 250);
				} else {
					initfig.animate({
						'opacity': 1,
						'right': '0%'
					}, 250);
				}
			}
		}, 250);

		if($G['slide_links']){
			wrapper.find('.gkIsSlide').on("click", function(e){
				window.location = links[$G['actual_slide']];
			});
			wrapper.find('.gkIsSlide').css('cursor', 'pointer');
		}

		wrapper.find('.gkIsPagination li').each(function(i, item) {
			jQuery(item).on('click', function() {
				if(i != $G['actual_slide']) {
					$G['blank'] = true;
					gk_bluap_autoanimate($G, wrapper, 'next', i);
				}
			});
		});

		// auto-animation
		if($G['autoanim'] == 1) {
			$G['animation_timer'] = setTimeout(function() {
				gk_bluap_autoanimate($G, wrapper, 'next', null);
			}, $G['anim_interval']);
		}

		// pagination
		var slide_pos_start_x = 0;
		var slide_pos_start_y = 0;
		var slide_time_start = 0;
		var slide_swipe = false;

		wrapper.on('touchstart', function(e) {
			slide_swipe = true;

			if(e.changedTouches.length > 0) {
				slide_pos_start_x = e.changedTouches[0].pageX;
				slide_pos_start_y = e.changedTouches[0].pageY;
				slide_time_start = new Date().getTime();
			}
		});

		wrapper.on('touchmove', function(e) {
			if(e.changedTouches.length > 0 && slide_swipe) {
				if(
					Math.abs(e.changedTouches[0].pageX - slide_pos_start_x) > Math.abs(e.changedTouches[0].pageY - slide_pos_start_y)
				) {
					e.preventDefault();
				} else {
					slide_swipe = false;
				}
			}
		});

		wrapper.on('touchend', function(e) {
			if(e.changedTouches.length > 0 && slide_swipe) {
				if(
					Math.abs(e.changedTouches[0].pageX - slide_pos_start_x) >= swipe_min_move &&
					new Date().getTime() - slide_time_start <= swipe_max_time
				) {
					if(e.changedTouches[0].pageX - slide_pos_start_x > 0) {
						$G['blank'] = true;
						gk_bluap_autoanimate($G, wrapper, 'prev', null);
					} else {
						$G['blank'] = true;
						gk_bluap_autoanimate($G, wrapper, 'next', null);
					}
				}
			}
		});
	});
});

var gk_bluap_animate = function($G, wrapper, imgPrev, imgNext) {
	var prevfig = imgPrev.find('.figcaption');
	//
	if(prevfig) {
		if($G['text_pos'] == 'left') {
			prevfig.animate({
				'opacity': 0,
				'left': '-20%'
			}, 150);
		} else {
			prevfig.animate({
				'opacity': 0,
				'right': '-20%'
			}, 150);
		}
	}
	//
	imgNext.addClass('animated');

	imgPrev.animate({'opacity': 0}, $G['anim_speed'], function(){
		imgPrev.removeClass('animated');
		imgPrev.removeClass('active');
	});

	imgNext.animate({'opacity': 1}, $G['anim_speed'], function(){

			imgNext.addClass('active');
			var nextfig = imgNext.find('.figcaption');
			if(nextfig) {
				if($G['text_pos'] == 'left') {
					nextfig.animate({
						'opacity': 1,
						'left': '0%'
					}, 150);
				} else {
					nextfig.animate({
						'opacity': 1,
						'right': '0%'
					}, 150);
				}
			}
			if($G['autoanim'] == 1) {
				clearTimeout($G['animation_timer']);

				$G['animation_timer'] = setTimeout(function() {
					if($G['blank']) {
						$G['blank'] = false;
						clearTimeout($G['animation_timer']);

						$G['animation_timer'] = setTimeout(function() {
							gk_bluap_autoanimate($G, wrapper, 'next', null);
						}, $G['anim_interval']);
					} else {
						gk_bluap_autoanimate($G, wrapper, 'next', null);
					}
				}, $G['anim_interval']);
			}

	});
};

var gk_bluap_autoanimate = function($G, wrapper, dir, next) {
	var i = $G['actual_slide'];
	var imgs = wrapper.find('.figure');

	if(next == null) {
		next = (dir == 'next') ? ((i < imgs.length - 1) ? i+1 : 0) : ((i == 0) ? imgs.length - 1 : i - 1); // dir: next|prev
	}

	gk_bluap_animate($G, wrapper, imgs.eq(i), imgs.eq(next));
	$G['actual_slide'] = next;

	wrapper.find('.gkIsPagination li').removeClass('active');
	wrapper.find('.gkIsPagination li').eq(next).addClass('active');
};
$(window).on("load",function(){
	$(".gkIsWrapper-gk_photo").each(function(i, _el){
		let el = $(_el);
		var elID = el.attr("id");
		var wrapper = $('#'+elID);
		var $G = $Gavick[elID];
		var slides = [];
		var links = [];
		var imagesToLoad = [];
		var swipe_min_move = 30;
		var swipe_max_time = 500;
		// animation variables
		$G['animation_timer'] = false;
		// blank flag
		$G['blank'] = false;
		// load the images
		wrapper.find('figure').each(function(i, _el) {
			let el = $(_el);
			var newImg = jQuery('<img/>', {
				"title": el.attr('data-title'),
				"class": 'gkIsSlide',
				"style": 'z-index: ' + el.attr('data-zindex') + ';',
				"src": el.attr('data-url')
			});
			links[i] = el.attr('data-link');
			imagesToLoad.push(newImg);
			newImg.prependTo(el);
		});
		//
		var time = setInterval(function() {
			var process = 0;
			for (let el of imagesToLoad) {
// 	            if(el.complete) // find a way to check if images loaded.
					process++;
			}
			if(process == imagesToLoad.length) {
				clearInterval(time);
				
				wrapper.find('img.gkIsSlide').each(function(i, _el) {
					let el = $(_el);
					var newDiv = jQuery('<div/>', {
						"class": 'gkIsSlide',
						"style": 'z-index: ' + el.attr('style') + '; background-image: url(\'' +  el.attr('src') + '\');', 
					});
					newDiv.prependTo(el.parent());
				});
				
				 wrapper.find('img.gkIsSlide').each(function(i, el){
					$(el).remove();
				 });

				setTimeout(function(){
					wrapper.find('.gkIsPreloader').css('position', 'absolute');
					wrapper.find('.gkIsPreloader').fadeOut();
					wrapper.find('figure').fadeIn();
					wrapper.find('figure').addClass('active');
					wrapper.addClass('loaded');
					
					setTimeout(function() {
						wrapper.find('figure').addClass('activated');
					}, 50);
					
					setTimeout(function() {
						wrapper.find('.gkIsPreloader').remove();
					}, 300);
				}, 400);
				
				$G['actual_slide'] = 0;
				
				var arrow_element = jQuery('<div/>', {
					'class': 'gkIsWrapper-arrow'
				});
				
				arrow_element.appendTo(wrapper);
				
				wrapper.find(".gkIsSlide").each(function(i, _elmt){
					elmt = $(_elmt);
					slides[i] = elmt;
					
//         		        if(!Modernizr || (Modernizr && !Modernizr.touch)) {
	        		        elmt.on('mouseover', function() {
	        		        	if(!wrapper.hasClass('gk-arrow-visible')) {
	        		        		wrapper.addClass('gk-arrow-visible');
	        		        	}
	        		        });

	        		        elmt.on('mouseout', function() {
	        		        	if(wrapper.hasClass('gk-arrow-visible')) {
	        		        		wrapper.removeClass('gk-arrow-visible');
	        		        	}
	        		        });

	        		        elmt.on('mousemove', function(e) {
	        		        	var w = wrapper.width();
	        		        	if(e.clientX > w/2 && !arrow_element.hasClass('inverse')) {
	        		        		arrow_element.addClass('inverse');
	        		        	} else if(e.clientX < w/2 && arrow_element.hasClass('inverse')) {
	        		        		arrow_element.removeClass('inverse');
	        		        	}

	        		        	arrow_element.css({
	        		        		'top': e.clientY - 64 + "px",
	        		        		'left': e.clientX - 32 + "px"
	        		        	});
	        		        });
//         		        }
				});
				
				// IE detection script
				function IE(v) {
				  return RegExp('msie' + (!isNaN(v)?('\\s'+v):''), 'i').test(navigator.userAgent);
				}
				
				if(IE(9) || IE(10)) {
					wrapper.addClass('ie10-cursor-normal');
					arrow_element.addClass('ie10-hide');
				}
				
				setTimeout(function() {
					if(slides && slides[0]) {
						var initfig = slides[0].parent().find('figcaption');
						if(initfig) {
							initfig.animate({opacity: 1}, 250);
						}
					}
				}, 250);
				
				var pagination = wrapper.find('.gkIsPagination');
				
				if(pagination) {
					pagination.attr('data-id', wrapper.attr('id'));
					pagination.addClass('gkIsPhotoPagination');
					
					if($('#page-nav')) {
						pagination.prependTo($('#page-nav'));
					}
					
					pagination.find('li').each(function(i, item) {
						$(item).on('click', function() {
							if(i != $G['actual_slide']) {
								$G['blank'] = true;
								gk_photo_autoanimate($G, wrapper, 'next', i);
							}
						});
					});
				}
				
				// auto-animation
				if($G['autoanim'] == 1) {	                	
					$G['animation_timer'] = setTimeout(function() {
						gk_photo_autoanimate($G, wrapper, 'next', null);
					}, $G['anim_interval']);
				}
				
				// navigation
				wrapper.find('.gkIsSlide').on('click', function() {
					$G['blank'] = true;
					
					if(arrow_element.hasClass('inverse')) {
						gk_photo_autoanimate($G, wrapper, 'next', null);
					} else {
						gk_photo_autoanimate($G, wrapper, 'prev', null);
					}
				});
				
				// pagination
				var slide_pos_start_x = 0;
				var slide_pos_start_y = 0;
				var slide_time_start = 0;
				var slide_swipe = false;
				
// 				wrapper.on('touchstart', function(e) {
// 					slide_swipe = true;
					
// 					if(e.changedTouches.length > 0) {
// 						slide_pos_start_x = e.changedTouches[0].pageX;
// 						slide_pos_start_y = e.changedTouches[0].pageY;
// 						slide_time_start = new Date().getTime();
// 					}
// 				});
				
// 				wrapper.on('touchmove', function(e) {
// 					if(e.changedTouches.length > 0 && slide_swipe) {
// 						if(
// 							Math.abs(e.changedTouches[0].pageX - slide_pos_start_x) > Math.abs(e.changedTouches[0].pageY - slide_pos_start_y)
// 						) {
// 							e.preventDefault();
// 						} else {
// 							slide_swipe = false;
// 						}
// 					}
// 				});
				
// 				wrapper.on('touchend', function(e) {
// 					if(e.changedTouches.length > 0 && slide_swipe) {					
// 						if(
// 							Math.abs(e.changedTouches[0].pageX - slide_pos_start_x) >= swipe_min_move && 
// 							new Date().getTime() - slide_time_start <= swipe_max_time
// 						) {
// 							if(e.changedTouches[0].pageX - slide_pos_start_x > 0) {
// 								$G['blank'] = true;
// 								gk_photo_autoanimate($G, wrapper, 'prev', null);
// 							} else {
// 								$G['blank'] = true;
// 								gk_photo_autoanimate($G, wrapper, 'next', null);
// 							}
// 						}
// 					}
// 				});
			}
		}, 500);
	});
});

var gk_photo_animate = function($G, wrapper, _imgPrev, _imgNext) {
	let imgNext = $(_imgNext);
	let imgPrev = $(_imgPrev);
	var prevfig = imgPrev.find('figcaption');
	//
	if(prevfig) {
		prevfig.animate({opacity: 0}, 150);
	}
	//
	imgNext.attr('class', 'animated');
	
  imgPrev.animate({
    opacity: 0
  }, $G['anim_speed'], function() {
    // Animation complete.
    imgPrev.attr('class', '');
  });

  imgNext.animate({
    opacity: 1
  }, $G['anim_speed'], function() {
	imgNext.attr('class', 'active');
	
	setTimeout(function() {
		imgNext.attr('class', 'active activated');
	}, 50);
	
	var nextfig = imgNext.find('figcaption');
	if(nextfig) {
		  nextfig.animate({opacity: 1}, 150);
	}
	if($G['autoanim'] == 1) {
		clearTimeout($G['animation_timer']);
		
		$G['animation_timer'] = setTimeout(function() {
			if($G['blank']) {
				$G['blank'] = false;
				clearTimeout($G['animation_timer']);
				
				$G['animation_timer'] = setTimeout(function() {
					gk_photo_autoanimate($G, wrapper, 'next', null);
				}, $G['anim_interval']);
			} else {
				gk_photo_autoanimate($G, wrapper, 'next', null);
			}
		}, $G['anim_interval']);
	}
  });
}; 

var gk_photo_autoanimate = function($G, wrapper, dir, next) {
	var i = $G['actual_slide'];
	var imgs = wrapper.find('figure');
	
	if(next == null) {
		next = (dir == 'next') ? ((i < imgs.length - 1) ? i+1 : 0) : ((i == 0) ? imgs.length - 1 : i - 1); // dir: next|prev
	}
	
	gk_photo_animate($G, wrapper, imgs[i], imgs[next]);
	$G['actual_slide'] = next;
	if($('.gkIsPagination[data-id="'+wrapper.attr('id')+'"] li').length) {
 	    $('.gkIsPagination[data-id="'+wrapper.attr('id')+'"] li').removeClass('active');
	    $('.gkIsPagination[data-id="'+wrapper.attr('id')+'"] li').eq(next).addClass('active');
	}
};

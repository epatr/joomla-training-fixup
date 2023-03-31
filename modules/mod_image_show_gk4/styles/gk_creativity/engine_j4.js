jQuery(window).on('load',function() {
	setTimeout(function() {
	    jQuery(".gkIsWrapper-gk_creativity").each(function(i, _el){
		    let el = jQuery(_el);
	        var elID = el.attr("id");
		    var wrapper = jQuery('#'+elID);
	        var $G = $Gavick[elID];
	        var slides = [];
	        var links = [];
	        var imagesToLoad = [];
	        var swipe_min_move = 30;
	        var swipe_max_time = 500;
	        var figcaptions = [];
	        // animation progress flag
	        $G['animating'] = false;
	        // animation variables
	        $G['animation_timer'] = false;
	        // blank flag
	        $G['blank'] = false;
	        // load the images
		    $G['actual_slide'] = 0;
		    wrapper.find('.figure').fadeIn();
		    wrapper.find('.figure').addClass('active');
		    wrapper.removeClass('notloaded');
		    
		    wrapper.find(".gkIsSlide").each(function(i, elmt){
				let el = jQuery(elmt);
					slides[i] = el;
					links[i] = el.attr('data-link');
		    }); 
		    
// 		    slides[0].find('.figcaption').css('opacity', 0);
		    
		    for (let i in slides) {
				let slide = slides[i];
        
	    		let fig = slide.find('.figcaption');
	    		fig.appendTo(wrapper);
	    		figcaptions.push(fig);
	    		
	    		if(i == 0) {	
	    			fig.addClass('gkIsNextTextLayer');
	    			setTimeout(function() {
	    				fig.find('h1').addClass('loaded');
	    				fig.find('h2').addClass('loaded');
	    				fig.find('.gkLearnMore').addClass('loaded');
	    			}, 500);
	    			
	    			fig.fadeIn();
	    		} else {
	    			fig.find('.gkLearnMore').addClass('loaded');
	    		}
		    };    
		    // handler for figcaptions array
		    $G['figcaptions'] = figcaptions;		    
		    // auto-animation
		    if($G['autoanim'] == 1) {	                	
		    	$G['animation_timer'] = setTimeout(function() {
		    		gk_creativity_autoanimate($G, wrapper, 'next', null);
		    	}, $G['anim_interval']);
		    }
		    
		    // pagination
	    	var slide_pos_start_x = 0;
	    	var slide_pos_start_y = 0;
	    	var slide_time_start = 0;
	    	var slide_swipe = false;
	    	
	    	wrapper.on('touchstart', function(e) {
	    		if(!$G['animating']) {
		    		slide_swipe = true;
		    		
		    		if(e.changedTouches.length > 0) {
		    			slide_pos_start_x = e.changedTouches[0].pageX;
		    			slide_pos_start_y = e.changedTouches[0].pageY;
		    			slide_time_start = new Date().getTime();
		    		}
	    		}
	    	});
	    	
	    	wrapper.on('touchmove', function(e) {
	    		if(!$G['animating']) {
		    		if(e.changedTouches.length > 0 && slide_swipe) {
		    			if(
		    				Math.abs(e.changedTouches[0].pageX - slide_pos_start_x) > Math.abs(e.changedTouches[0].pageY - slide_pos_start_y)
		    			) {
		    				e.preventDefault();
		    			} else {
		    				slide_swipe = false;
		    			}
		    		}
	    		}
	    	});
	    	
	    	wrapper.on('touchend', function(e) {
	    		if(!$G['animating']) {
		    		if(e.changedTouches.length > 0 && slide_swipe) {					
		    			if(
		    				Math.abs(e.changedTouches[0].pageX - slide_pos_start_x) >= swipe_min_move && 
		    				new Date().getTime() - slide_time_start <= swipe_max_time
		    			) {
		    				if(e.changedTouches[0].pageX - slide_pos_start_x > 0) {
		    					$G['blank'] = true;
		    					gk_creativity_autoanimate($G, wrapper, 'prev', null);
		    				} else {
		    					$G['blank'] = true;
		    					gk_creativity_autoanimate($G, wrapper, 'next', null);
		    				}
		    			}
		    		}
	    		}
	    	});
	    	
	    	// nav buttons
	    	if(wrapper.find('.gkIsPrevBtn')) {
	    		wrapper.find('.gkIsPrevBtn').on('click', function() {
	    			if(!$G['animating']) {
	    				$G['blank'] = true;
	    				gk_creativity_autoanimate($G, wrapper, 'prev', null);
	    			}
	    		});
	    		
	    		wrapper.find('.gkIsNextBtn').on('click', function() {
	    			if(!$G['animating']) {
	    				$G['blank'] = true;
	    				gk_creativity_autoanimate($G, wrapper, 'next', null);
	    			}
	    		});
	    		
	    		wrapper.on('mouseenter', function() {
	    			wrapper.addClass('hover');
	    		});
	    		
	    		wrapper.on('mouseleave', function() {
	    			wrapper.removeClass('hover');
	    		});
	    	}
	    });
    }, 1000);
});

var gk_creativity_animate = function($G, wrapper, imgPrev, imgNext, prev, next) {	
	let animtype = wrapper.attr('data-bganim');
	imgNext = jQuery(imgNext);
	imgPrev = jQuery(imgPrev);
	//
	imgNext.addClass('animated');
	$G['animating'] = true;
	//
	if(animtype != 'opacity') {
		imgPrev.css('opacity', 1);
		imgNext.css('opacity', 1);
		
		if(animtype == 'vertical') {
			imgNext.css('top', '100%');
		}
		
		if(animtype == 'horizontal') {
			imgNext.css('left', '100%');
		}
	}
	
	let prev_prop = '';
	let prev_val = '';
	let next_prop = '';
	let next_val = '';
	let effectPrev = {};
	let effectNext = {};
	if(animtype == 'opacity') {
		prev_prop = 'opacity';
		prev_val = '0';
		next_prop = 'opacity';
		next_val = '1';
		effectPrev = {'opacity': 0};
		effectNext = {'opacity': 1};
	} else if(animtype == 'vertical') {
		prev_prop = 'top';
		prev_val = '-100%';
		next_prop = 'top';
		next_val = '0%';
		effectPrev = {'top': '-100%'};
		effectNext = {'top': '0%'};
	} else {
		prev_prop = 'left';
		prev_val = '-100%';
		next_prop = 'left';
		next_val = '0%';
		effectPrev = {'left': '-100%'};
		effectNext = {'left': '0%'};
	}

	imgPrev.animate(effectPrev, $G['anim_speed'], function() {
    // Animation complete.
    imgPrev.removeClass('active');
			
		if(animtype == 'vertical') {
			imgPrev.css('top', '100%');
		}
		
		if(animtype == 'horizontal') {
			imgPrev.css('left', '100%');
		}
  });
	
	$G['figcaptions'][prev].removeClass('gkIsNextTextLayer').addClass('gkIsPrevTextLayer');
	$G['figcaptions'][prev].find('h1').removeClass('loaded');
	$G['figcaptions'][prev].find('h2').removeClass('loaded');
	
	setTimeout(function() {
		$G['figcaptions'][next].addClass('gkIsNextTextLayer');	
		$G['figcaptions'][prev].removeClass('gkIsPrevTextLayer');
		
		$G['figcaptions'][next].find('h1').addClass('loaded');
		$G['figcaptions'][next].find('h2').addClass('loaded');
	}, $G['anim_speed']);
	
	//
	imgNext.animate(effectNext, $G['anim_speed'], function() {
    // Animation complete.
    imgNext.addClass('active');
		$G['animating'] = false;
		
		if($G['autoanim'] == 1) {
			clearTimeout($G['animation_timer']);
			
			$G['animation_timer'] = setTimeout(function() {
				if($G['blank']) {
					$G['blank'] = false;
					clearTimeout($G['animation_timer']);
					
					$G['animation_timer'] = setTimeout(function() {
						gk_creativity_autoanimate($G, wrapper, 'next', null);
					}, $G['anim_interval']);
				} else {
					gk_creativity_autoanimate($G, wrapper, 'next', null);
				}
			}, $G['anim_interval']);
		}
  });
}; 

var gk_creativity_autoanimate = function($G, wrapper, dir, next) {
	let i = $G['actual_slide'];
	let imgs = wrapper.find('.figure');
	
	if(next == null) {
		next = (dir == 'next') ? ((i < imgs.length - 1) ? i+1 : 0) : ((i == 0) ? imgs.length - 1 : i - 1); // dir: next|prev
	}
	
	gk_creativity_animate($G, wrapper, imgs[i], imgs[next], i, next);
	$G['actual_slide'] = next;
};

jQuery(document).on('keyup', function(e){
    switch(e.key) {
    	case 'ArrowLeft':
        case 'left': // left key
        	jQuery('.gkIsPrevBtn').trigger('click');
        break;
        case 'ArrowRight':
        case 'right': // right key
        	jQuery('.gkIsNextBtn').trigger('click');
        break;
        
        default: 
        	return;
    }
});
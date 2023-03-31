jQuery(window).on("load",function(){
	setTimeout(function() {
	    jQuery(".gkIsWrapper-gk_university").each(function(i, _el){
		      let el = jQuery(_el);
		var elID = el.attr("id");
		var wrapper = jQuery('#'+elID);
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
			        let el = jQuery(_el);
			        var newImg = jQuery('<img/>', {
				        "title": el.data('title'),
	              "class": 'gkIsSlide',
				        "style": 'z-index: ' + el.data('zindex') + ';',
				        "src": el.data('url')
	            });
			        links[i] = el.data('link');
	            imagesToLoad.push(newImg);
			        newImg.prependTo(el);
	        });
	        //
	        var time = setInterval(function(){
	            var process = 0;                
			        for (let el of imagesToLoad) {
					      process++;
			        }
	            
	            if(process == imagesToLoad.length){
	                clearInterval(time);
					
					wrapper.find('figure').each(function(i, _el) {
            let fig = jQuery(_el);
						var img = fig.find('img');
						var newDiv = jQuery('<div/>', {
							'class': 'gkIsSlide',
							'style': img.attr('style'),
							'title': img.attr('title')
						});
						newDiv.css('background-image', "url('" + img.attr('src') + "')");
						fig.css('opacity', 0);
						newDiv.prependTo(img.parent());
						img.remove();
					});
					
	            setTimeout(function(){
	        			wrapper.addClass('loaded');
	        			wrapper.find('.gkIsPreloader').animate({opacity: 1}, 250);
	        			wrapper.find('.gkIsPreloader').css('opacity', 0);
        				
        				wrapper.find('figure').each(function(i,_el) {
        					let fig = jQuery(_el);
        					fig.css('opacity', 1);
        				});
        				
        				setTimeout(function() {
        					wrapper.find('.gkIsPreloader').remove();
        				}, 1000);
        				
        				wrapper.find('figure').css('opacity', 1);
        				wrapper.find('figure').addClass('active');
        				
        				if(
        					wrapper.data('one-slide') != 'true' &&
        					wrapper.find('figure').find('figcaption')
        				) {
        					wrapper.find('figure').find('.gkProgressBar').addClass('active');
        				}
	        		}, 400);
	        		
        		    $G['actual_slide'] = 0;
        		    
        		    wrapper.find(".gkIsSlide").each(function(elmt,i){
        		        slides[i] = elmt;
        		    });
        		    
        		    setTimeout(function() {
									if(slides && slides[0]) {
										var initfig = slides[0].parent().find('figcaption');
										if(initfig) {
											initfig.animate({opacity: 1}, 250);
										}
									}
								}, 250);
        		    
        		    if($G['slide_links']){
        		        wrapper.find('.gkIsSlide').on("click", function(e){ 
        		            window.location = links[$G['actual_slide']]; 
        		        });
        		        wrapper.find('.gkIsSlide').css('cursor', 'pointer');
        		    }
        		    
        		    // auto-animation	
        		    if(wrapper.data('one-slide') != 'true') {                	
    		    		$G['animation_timer'] = setTimeout(function() {
    		    			gk_university_autoanimate($G, wrapper, 'next', null);
    		    		}, $G['anim_interval']);
    		    	}
	            }
	        }, 500);
	    });
    }, 1000);
});

var gk_university_animate = function($G, wrapper, _imgPrev, _imgNext) {	
	var imgPrev = jQuery(_imgPrev);
	var imgNext = jQuery(_imgNext);
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
		if(imgPrev.find('.gkProgressBar')) {
			imgPrev.find('.gkProgressBar').removeClass('active');
		}
  });

  imgNext.animate({
    opacity: 1
  }, $G['anim_speed'], function() {
	imgNext.attr('class', 'active');

	var nextfig = imgNext.find('figcaption');
	if(nextfig) {
		  nextfig.animate({opacity: 1}, 150);
	}

	clearTimeout($G['animation_timer']);

	if(!(
		wrapper.data('no-loop') == 'true' && 
		$G['actual_slide'] == wrapper.find('figure').length - 1
	)) {
	$G['animation_timer'] = setTimeout(function() {
		if($G['blank']) {
			$G['blank'] = false;
			clearTimeout($G['animation_timer']);
			
			$G['animation_timer'] = setTimeout(function() {
				gk_university_autoanimate($G, wrapper, 'next', null);
			}, $G['anim_interval']);
		} else {
			gk_university_autoanimate($G, wrapper, 'next', null);
		}
	}, $G['anim_interval']);
	} else {
		setTimeout(function() {
  		if(wrapper.find('figure')) {
  			wrapper.find('.gkProgressBar').parent().fade('out');
  		}
		}, $G['anim_interval']);
	}
  });
}; 

var gk_university_autoanimate = function($G, _wrapper, dir, next) {
	var wrapper = jQuery(_wrapper);
	var i = $G['actual_slide'];
	var imgs = wrapper.find('figure');
	
	if(next == null) {
		next = (dir == 'next') ? ((i < imgs.length - 1) ? i+1 : 0) : ((i == 0) ? imgs.length - 1 : i - 1); // dir: next|prev
	}
	
	gk_university_animate($G, wrapper, imgs[i], imgs[next]);
	$G['actual_slide'] = next;
};


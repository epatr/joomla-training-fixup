/**
 * ------------------------------------------------------------------------
 * JA Intranet Template
 * ------------------------------------------------------------------------
 * Copyright (C) 2004-2011 J.O.O.M Solutions Co., Ltd. All Rights Reserved.
 * @license - Copyrighted Commercial Software
 * Author: J.O.O.M Solutions Co., Ltd
 * Websites:  http://www.joomlart.com -  http://www.joomlancers.com
 * This file may not be redistributed in whole or significant part.
 * ------------------------------------------------------------------------
 */


// Isotope
(function($){
  $(document).ready(function(){
  	var $ww = $(window).width();
	var $container = $('.isotope-layout .isotope');

	// if (!$container.length) return ;

	$container.isotope({
	  itemSelector: '.isotope-item',
	  masonry: {
		columnWidth: '.grid-sizer',
	  }
	});
	
	// re-order when images loaded
	$container.imagesLoaded(function(){
	  $container.isotope();
	
	  /* fix for IE-8 */
	  setTimeout (function() {
		$('.isotope-layout .isotope').isotope();
	  }, 3000);  
	});

	$('.t3-module ul.nav.nav-pills.nav-stacked>li').off().unbind();
	$('.t3-module ul.nav.nav-pills.nav-stacked>li>a.dropdown-toggle').unbind().off().dropdown();
	$('.t3-module ul.nav.nav-pills.nav-stacked>li>a.dropdown-toggle').click(function(){
		$('.dropdown-backdrop').remove();
		$container.isotope();
	});

	// Navbar Vertical
	$('.navbar-control').click(function() {
		$('.layout-vertical').toggleClass('navbar-hidden');
		if($(window).width() >= 992) {
			setTimeout (function() {$container.isotope();}, 400);
		}
	});

	$('.navbar-nav > li.dropdown > a').click(function() {
		$('.layout-vertical').removeClass('navbar-hidden');
		setTimeout (function() {$container.isotope();}, 450);
	});
	
	navbarResponsive();
	
	$(window).resize(function() {
		navbarResponsive();
	});

	function navbarResponsive() {
		if($(window).width() < 991) {
			$('.layout-vertical').addClass('navbar-hidden');
			setTimeout (function() {$container.isotope();}, 450);
		} else {
			if ($ww !== $(window).width()) {
				$('.layout-vertical').removeClass('navbar-hidden');
				setTimeout (function() {$container.isotope();}, 450);
			}
		}
		if ($ww !== $(window).width())
			$ww=$(window).width();
	}

	$(".navbar-default-vertical").mCustomScrollbar();
	
	$(document).on('click mouseup touchend', function (e){
		var cont = $(".t3-module ul.nav.nav-pills.nav-stacked>li.open"); // Give you class or ID

		if (!cont.is(e.target) // if the target of the click is not the desired div or section
			&& cont.has(e.target).length === 0) // ... nor a descendant-child of the container
		{
			if ($(".t3-module ul.nav.nav-pills.nav-stacked>li.open").length) {
				setTimeout (function() {$container.isotope();}, 30);
			}
		}
		
	});

	$(document).mouseup(function (e){
		var cont2 = $(".popbox.es.popbox-notifications");
		if (!cont2.is(e.target) && cont2.has(e.target).length === 0)
		{
			$('html').removeClass('ja-notice');
		}
	});


	if($('.alert .close').length) {
		$('.alert .close').click(function() {
			setTimeout (function() {$container.isotope();}, 450);
		});
	}
	
	$('#t3-header div.es-notification div.es-menu-item').
		click(function(){
			$('html').addClass('ja-notice');
		});
	
  });
  $(window).on('load', function(){
  	  $('.accordion').on('shown.bs.collapse hidden.bs.collapse', function () {
				  	 
				setTimeout(function(){
				let $container = $('.isotope-layout .isotope');
					$container.isotope({
					  itemSelector: '.isotope-item',
					  masonry: {
						columnWidth: '.grid-sizer',
					  }
					});
				   	  	$container.isotope();
				}, 1000);

			});
  });
})(jQuery);


// Header Scroll
(function($) {

	var scrollLastPos = 0,
		scrollDir = 0, // -1: up, 1: down
		scrollTimeout = 0;
  $(window).on ('scroll', function (e) {
		var st = $(this).scrollTop();
		//Determines up-or-down scrolling
		if (st < 1) {
			if (scrollDir != 0) {
				scrollDir = 0;
				scrollToggle();
			}
		} else if (st > scrollLastPos){
			//Replace this with your function call for downward-scrolling
			if (scrollDir != 1) {
				scrollDir = 1;
				scrollToggle();
			}
		} else if (st < scrollLastPos){
			//Replace this with your function call for upward-scrolling
			if (scrollDir != -1) {
				scrollDir = -1;
				scrollToggle();
			}
		}
		//Updates scroll position
		scrollLastPos = st;
	});

	$('.ja-header').on ('hover', function () {
		$('html').removeClass ('scrollDown scrollUp').addClass ('hover');
		scrollDir = 0;
	})

	scrollToggle = function () {
		$('html').removeClass ('hover');
		if (scrollDir == 1) {
			$('html').addClass ('scrollDown').removeClass ('scrollUp');
		} else if (scrollDir == -1) {
			$('html').addClass ('scrollUp').removeClass ('scrollDown');
		} else {
			$('html').removeClass ('scrollUp scrollDown');
		}
		
		$('html').addClass ('animating');
		setTimeout(function(){ $('html').removeClass ('animating'); }, 1000);
	}

})(jQuery);


// TAB
// -----------------
(function($){
  $(document).ready(function(){
  if($('.nav.nav-tabs').length > 0 && !$('.nav.nav-tabs').hasClass('nav-stacked')){
	$('.nav.nav-tabs li').click(function (e) {
	e.preventDefault();
	$(this).find('a').tab('show');
	})
  }
  
  });
})(jQuery);


// Navigation
// --------------
(function($) {
	$(document).ready(function(){
		var $window = $(window);

		// Collapse Menu when click outsite.
		function navCollapse() {
			
			$(document).on('click touchend', function (e) {
			  var navVertical = $('.navbar-default-vertical');

				if ($window.width() < 992) {
				  if (!navVertical.is(e.target) && navVertical.has(e.target).length === 0) {
					$('.layout-vertical').addClass('navbar-hidden');
					navVertical.find('ul.nav.navbar-nav li.open').removeClass('open');
				  };
				};
			});
			
		}

		// Execute on load
		navCollapse();
		bindNavMenu();
		
		// Bind event listener
		$window.resize(function(){
			bindNavMenu();
		});
		
		function bindNavMenu() {
			if ($window.width() < 1200) {
				$('.layout-horizontal .navbar-control').show();
				$('.layout-horizontal').addClass('layout-vertical');

				if($window.width() < 992) {
					$('.layout-horizontal').addClass('navbar-hidden');
				}
				
				$('.layout-horizontal .navbar.navbar-default.t3-mainnav.navbar-default-horizontal').addClass('navbar-default-vertical');
				$('.layout-horizontal .navbar.navbar-default.t3-mainnav.navbar-default-horizontal').removeClass('navbar-default-horizontal');
			} else {
				$('.layout-horizontal .navbar-control').hide();
				$('.layout-horizontal').removeClass('layout-vertical');
				$('.layout-horizontal .navbar.navbar-default.t3-mainnav.navbar-default-vertical').addClass('navbar-default-horizontal');
				$('.layout-horizontal .navbar.navbar-default.t3-mainnav.navbar-default-vertical').removeClass('navbar-default-vertical');
			}
		}

		// fix mega menu 
	 	$('.t3-navbar .t3-megamenu ul li > a').click(function(e){
    	if ($(this).hasClass('dropdown-toggle') && $(this).parent().hasClass('open')) {
    		window.location.href = $(this).attr('href');
    	}
    });
	});

})(jQuery);
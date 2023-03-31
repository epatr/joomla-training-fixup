jQuery(document).ready(function(){
  // Only run when transform not supported by browser.
  var prefixes = 'transform WebkitTransform MozTransform msTransform OTransform'.split(' ');
  var div = document.createElement('div');
  for(var i = 0; i < prefixes.length; i++) {
    if(div && div.style[prefixes[i]] !== undefined) {
      return;
    }
  }
  jQuery('.media-thumb').each(function(){
    jQuery(this).load(function(){
      var margin = jQuery(this).parents('.media-item').height()/2 - jQuery(this).height()/2;
      jQuery(this).css({
        'top': '0',
        '-webkit-transform': 'none',
        '-moz-transform': 'none',
        '-ms-transform': 'none',
        '-o-transform': 'none',
        'transform': 'none',
        'margin-top': margin + 'px'
      });
    });        
  })
});

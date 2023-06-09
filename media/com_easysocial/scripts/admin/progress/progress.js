EasySocial.module('admin/progress/progress', function($) {

var module = this;

EasySocial.Controller('Progress', {
	// A list of selectors we define
	// and expect template makers to follow.
	defaultOptions: {
		// Controller Properties.
		current : 0,
		eachWidth : null,
		total : null,

		// Controller Elements
		"{progressBar}" : ".progress-bar",
		"{progressResult}" : ".progress-result"
	}
}, function(self, opts){ return {

	reset: function() {
		self.options.current 	= 0;
		self.eachWidth 			= null;
		self.total 				= null;

		self.progressBar().css( 'width' , '0%' ).html( '' );
	},

	begin: function(total) {
		// Set the total number of items
		self.options.total 	= total;

		// Set the width of each item.
		self.options.eachWidth	= 100 / total;


		// Only show progress bar when the there's more than 1 item.
		if (total > 0) {
			self.element
				.removeClass('t-hidden');
		}
	},

	touch : function(message) {
		self.options.current 	+= self.options.eachWidth;

		//ensure the progress bar do not exceed 100%
		if( self.options.current > 100 )
		{
			self.options.current = 100;
		}

		self.progressBar().css( 'width' , self.options.current + '%' );
		self.progressResult().html( Math.round( self.options.current ) + '%' );
	},

	completed: function(message) {
		self.options.current 	= 100;

		self.progressBar().css( 'width' , self.options.current + '%' );
		self.progressResult().html( Math.round( self.options.current ) + '%' );
	}
}});

module.resolve();

});

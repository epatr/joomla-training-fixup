/**
 * Fx.ProgressBar
 *
 * @version		1.1
 *
 * @license		MIT License
 *
 * @author		Harald Kirschner <mail [at] digitarald [dot] de>
 * @copyright	Authors
 */

Fx.ProgressBar = new Class({

	Extends: Fx,

	options: {
		text: null,
		url: null,
		transition: Fx.Transitions.Circ.easeOut,
		fit: true,
		link: 'cancel',
		active: 'overall-progress-active'
	},

	initialize: function(element, options) {
		this.element = $(element);
		this.parent(options);
	},

	start: function(to, total) {
		return this.parent(this.now, (arguments.length == 1) ? to.limit(0, 100) : to / total * 100);
	},

	set: function(to) {
		this.now = to;

                $(this.options.active).style.width = Math.round(to) + '%';

		return this;
	}

});
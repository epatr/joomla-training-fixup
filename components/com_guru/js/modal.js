/**
 * SqueezeBox - Expandable Lightbox
 *
 * Allows to open various content as modal,
 * centered and animated box.
 *
 * Inspired by
 *  ... Lokesh Dhakar	- The original Lightbox v2
 *  ... Cody Lindley	- ThickBox
 *
 * @version		1.0rc1
 *
 * @license		MIT-style license
 * @author		Harald Kirschner <mail [at] digitarald.de>
 * @copyright	Author
 */
/***var SqueezeBox = {

	presets: {
		size: {x: 600, y: 450},
		sizeLoading: {x: 200, y: 150},
		marginInner: {x: 20, y: 20},
		marginImage: {x: 150, y: 200},
		handler: false,
		adopt: null,
		closeWithOverlay: true,
		zIndex: 65555,
		overlayOpacity: 0.7,
		classWindow: '',
		classOverlay: '',
		disableFx: false,
		//onOpen: Class.empty,
		//onClose: Class.empty,
		//onUpdate: Class.empty,
		//onResize: Class.empty,
		//onMove: Class.empty,
		//onShow: Class.empty,
		//onHide: Class.empty,
		fxOverlayDuration: 250,
		fxResizeDuration: 750,
		fxContentDuration: 250,
		ajaxOptions: {}
	},

	initialize: function(options) {
		if (this.options) return this;
		this.presets = $merge(this.presets, options)
		this.setOptions(this.presets);
		this.build();
		this.listeners = {
			window: this.reposition.bind(this, [null]),
			close: this.close.bind(this),
			key: this.onkeypress.bind(this)};
		this.isOpen = this.isLoading = false;
		this.window.close = this.listeners.close;
		return this;
	},

	build: function() {
		this.overlay = new Element('div', {
			id: 'sbox-overlay',
			styles: {
				display: 'none',
				zIndex: this.options.zIndex
			}
		});
		this.content = new Element('div', {
			id: 'sbox-content'
		});
		this.btnClose = new Element('a', {
			id: 'sbox-btn-close',
			href: '#',
		});
		this.window = new Element('div', {
			id: 'sbox-window',
			styles: {
				display: 'none',
				zIndex: this.options.zIndex + 2
			}
		}).adopt(this.btnClose, this.content);

		if (!window.ie6) {
			this.overlay.setStyles({
				position: 'fixed',
				top: 0,
				left: 0
			});
			this.window.setStyles({
				position: 'fixed',
				top: '50%',
				left: '50%'
			});
		} else {
			this.overlay.style.setExpression('marginTop', 'document.documentElement.scrollTop + "px"');
			this.window.style.setExpression('marginTop', '0 - parseInt(this.offsetHeight / 2) + document.documentElement.scrollTop + "px"');

			this.overlay.setStyles({
				position: 'absolute',
				top: '0%',
				left: '0%'
				//,marginTop: "expression(document.documentElement.scrollTop + 'px')"
			});

			this.window.setStyles({
				position: 'absolute',
				top: '0%',
				left: '0%'
				//,marginTop: "(expression(0 - parseInt(this.offsetHeight / 2) + document.documentElement.scrollTop + 'px')"
			});
		}

		$(document.body).adopt(this.overlay, this.window);

		this.fx = {
			overlay: this.overlay.effect('opacity', {
				duration: this.options.fxOverlayDuration,
				wait: false}).set(0),
			window: this.window.effects({
				duration: this.options.fxResizeDuration,
				wait: false}),
			content: this.content.effect('opacity', {
				duration: this.options.fxContentDuration,
				wait: false}).set(0)
		};
	},

	addClick: function(el) {
		return el.addEvent('click', function() {
			if (this.fromElement(el)) return false;
		}.bind(this));
	},

	fromElement: function(el, options) {
		this.initialize();
		this.element = $(el);
		if (this.element && this.element.rel) options = $merge(options || {}, Json.evaluate(this.element.rel));
		this.setOptions(this.presets, options);
		this.assignOptions();
		this.url = (this.element ? (this.options.url || this.element.href) : el) || '';

		if (this.options.handler) {
			var handler = this.options.handler;
			return this.setContent(handler, this.parsers[handler].call(this, true));
		}
		var res = false;
		for (var key in this.parsers) {
			if ((res = this.parsers[key].call(this))) return this.setContent(key, res);
		}
		return this;
	},

	assignOptions: function() {
		this.overlay.setProperty('class', this.options.classOverlay);
		this.window.setProperty('class', this.options.classWindow);
	},

	close: function(e) {
		if (e) new Event(e).stop();
		if (!this.isOpen) return this;
		this.fx.overlay.start(0).chain(this.toggleOverlay.bind(this));
		this.window.setStyle('display', 'none');
		this.trashImage();
		this.toggleListeners();
		this.isOpen = null;
		this.fireEvent('onClose', [this.content]).removeEvents();
		this.options = {};
		this.setOptions(this.presets).callChain();
		return this;
	},

	onError: function() {
		if (this.image) this.trashImage();
		this.setContent('Error during loading');
	},

	trashImage: function() {
		if (this.image) this.image = this.image.onload = this.image.onerror = this.image.onabort = null;
	},

	setContent: function(handler, content) {
		this.content.setProperty('class', 'sbox-content-' + handler);
		this.applyTimer = this.applyContent.delay(this.fx.overlay.options.duration, this, [this.handlers[handler].call(this, content)]);
		if (this.overlay.opacity) return this;
		this.toggleOverlay(true);
		this.fx.overlay.start(this.options.overlayOpacity);
		this.reposition();
		return this;
	},

	applyContent: function(content, size) {
		this.applyTimer = $clear(this.applyTimer);
		this.hideContent();
		if (!content) this.toggleLoading(true);
		else {
			if (this.isLoading) this.toggleLoading(false);
			this.fireEvent('onUpdate', [this.content], 20);
		}
		this.content.empty()[['string', 'array', false].contains($type(content)) ? 'setHTML' : 'adopt'](content || '');
		this.callChain();
		if (!this.isOpen) {
			this.toggleListeners(true);
			this.resize(size, true);
			this.isOpen = true;
			this.fireEvent('onOpen', [this.content]);
		} else this.resize(size);
	},

	resize: function(size, instantly) {
		var sizes = window.getSize();
		this.size = $merge(this.isLoading ? this.options.sizeLoading : this.options.size, size);
		var to = {
			width: this.size.x,
			height: this.size.y,
			marginLeft: - this.size.x / 2,
			marginTop: - this.size.y / 2
			//left: (sizes.scroll.x + (sizes.size.x - this.size.x - this.options.marginInner.x) / 2).toInt(),
			//top: (sizes.scroll.y + (sizes.size.y - this.size.y - this.options.marginInner.y) / 2).toInt()
		};
		$clear(this.showTimer || null);
		this.hideContent();
		if (!instantly) this.fx.window.start(to).chain(this.showContent.bind(this));
		else {
			this.window.setStyles(to).setStyle('display', '');
			this.showTimer = this.showContent.delay(50, this);
		}
		this.reposition(sizes);
	},

	toggleListeners: function(state) {
		var task = state ? 'addEvent' : 'removeEvent';
		this.btnClose[task]('click', this.listeners.close);
		if (this.options.closeWithOverlay) this.overlay[task]('click', this.listeners.close);
		document[task]('keydown', this.listeners.key);
		window[task]('resize', this.listeners.window);
		window[task]('scroll', this.listeners.window);
	},

	toggleLoading: function(state) {
		this.isLoading = state;
		this.window[state ? 'addClass' : 'removeClass']('sbox-loading');
		if (state) this.fireEvent('onLoading', [this.window]);
	},

	toggleOverlay: function(state) {
		this.overlay.setStyle('display', state ? '' : 'none');
		$(document.body)[state ? 'addClass' : 'removeClass']('body-overlayed');
	},

	showContent: function() {
		if (this.content.opacity) this.fireEvent('onShow', [this.window]);
		this.fx.content.start(1);
	},

	hideContent: function() {
		if (!this.content.opacity) this.fireEvent('onHide', [this.window]);
		this.fx.content.stop().set(0);
	},

	onkeypress: function(e) {
		switch (e.key) {
			case 'esc':
			case 'x':
				this.close();
				break;
		}
	},

	reposition: function(sizes) {
		sizes = sizes || window.getSize();
		this.overlay.setStyles({
			//'left': sizes.scroll.x, 'top': sizes.scroll.y,
			width: sizes.size.x,
			height: sizes.size.y
		});
		/*
		this.window.setStyles({
			left: (sizes.scroll.x + (sizes.size.x - this.window.offsetWidth) / 2).toInt(),
			top: (sizes.scroll.y + (sizes.size.y - this.window.offsetHeight) / 2).toInt()
		});
		*/
/***		this.fireEvent('onMove', [this.overlay, this.window, sizes]);
	},

	removeEvents: function(type){
		if (!this.$events) return this;
		if (!type) this.$events = null;
		else if (this.$events[type]) this.$events[type] = null;
		return this;
	},

	parsers: {
		'image': function(preset) {
			return (preset || this.url.test(/\.(jpg|jpeg|png|gif|bmp)$/i)) ? this.url : false;
		},
		'adopt': function(preset) {
			if ($(this.options.adopt)) return $(this.options.adopt);
			if (preset || ($(this.element) && !this.element.parentNode)) return $(this.element);
			var bits = this.url.match(/#([\w-]+)$/);
			return bits ? $(bits[1]) : false;
		},
		'url': function(preset) {
			return (preset || (this.url && !this.url.test(/^javascript:/i))) ? this.url: false;
		},
		'iframe': function(preset) {
			return (preset || this.url) ? this.url: false;
		},
		'string': function(preset) {
			return true;
		}
	},

	handlers: {
		'image': function(url) {
			this.image = new Image();
			var events = {
				loaded: function() {
					var win = {x: window.getWidth() - this.options.marginImage.x, y: window.getHeight() - this.options.marginImage.y};
					var size = {x: this.image.width, y: this.image.height};
					for (var i = 0; i < 2; i++)
						if (size.x > win.x) {
							size.y *= win.x / size.x;
							size.x = win.x;
						} else if (size.y > win.y) {
							size.x *= win.y / size.y;
							size.y = win.y;
						}
					size = {x: parseInt(size.x), y: parseInt(size.y)};
					if (window.webkit419) this.image = new Element('img', {'src': this.image.src});
					else $(this.image);
					this.image.setProperties({
						'width': size.x,
						'height': size.y});
					this.applyContent(this.image, size);
				}.bind(this),
				failed: this.onError.bind(this)
			};
			(function() {
				this.src = url;
			}).delay(10, this.image);
			this.image.onload = events.loaded;
			this.image.onerror = this.image.onabort = events.failed;
		},
		'adopt': function(el) {
			return el.clone();
		},
		'url': function(url) {
			this.ajax = new Ajax(url, this.options.ajaxOptions);
			this.ajax.addEvent('onSuccess', function(resp) {
				this.applyContent(resp);
				this.ajax = null;
			}.bind(this));
			this.ajax.addEvent('onFailure', this.onError.bind(this));
			this.ajax.request.delay(10, this.ajax);
		},
		'iframe': function(url) {
			return new Element('iframe', {
				'src': url,
				'frameBorder': 0,
				'width': this.options.size.x,
				'height': this.options.size.y
			});
		},
		'string': function(str) {
			return str;
		}
	},

	//extend: $extend
};

//SqueezeBox.extend(Events.prototype);
//SqueezeBox.extend(Options.prototype);
//SqueezeBox.extend(Chain.prototype);
***/

var SqueezeBox = {
 
         presets: {
                 //onOpen: $empty,
                 //onClose: $empty,
                 //onUpdate: $empty,
                 //onResize: $empty,
                 //onMove: $empty,
                 //onShow: $empty,
                 //onHide: $empty,
                 size: {x: 600, y: 450},
                 sizeLoading: {x: 200, y: 150},
                 marginInner: {x: 20, y: 20},
                 marginImage: {x: 50, y: 75},
                 handler: false,
                 target: null,
                 closable: true,
                 closeBtn: true,
                 zIndex: 65555,
                 overlayOpacity: 0.7,
                 classWindow: '',
                 classOverlay: '',
                 overlayFx: {},
                 resizeFx: {},
                 contentFx: {},
                 parse: false, // 'rel'
                 parseSecure: false,
                 shadow: true,
                 document: null,
                 ajaxOptions: {}
         },
 
         initialize: function(presets) {
                 if (this.options) return this;
 
                 this.presets = $merge(this.presets, presets);
                 this.doc = this.presets.document || document;
                 this.options = {};
                 this.setOptions(this.presets).build();
                 this.bound = {
                         window: this.reposition.bind(this, [null]),
                         scroll: this.checkTarget.bind(this),
                         close: this.close.bind(this),
                         key: this.onKey.bind(this)
                 };
                 this.isOpen = this.isLoading = false;
                 return this;
         },
 
         build: function() {
                 this.overlay = new Element('div', {
                         id: 'sbox-overlay',
                         styles: {display: 'none', zIndex: this.options.zIndex}
                 });
                 this.win = new Element('div', {
                         id: 'sbox-window',
                         styles: {display: 'none', zIndex: this.options.zIndex + 2}
                 });
                 if (this.options.shadow) {
                         if (Browser.Engine.webkit420) {
                                 this.win.setStyle('-webkit-box-shadow', '0 0 10px rgba(0, 0, 0, 0.7)');
                         } else if (!Browser.Engine.trident4) {
                                 var shadow = new Element('div', {'class': 'sbox-bg-wrap'}).inject(this.win);
                                 var relay = function(e) {
                                         this.overlay.fireEvent('click', [e]);
                                 }.bind(this);
                                 ['n', 'ne', 'e', 'se', 's', 'sw', 'w', 'nw'].each(function(dir) {
                                         new Element('div', {'class': 'sbox-bg sbox-bg-' + dir}).inject(shadow).addEvent('click', relay);
                                 });
                         }
                 }
                 this.content = new Element('div', {id: 'sbox-content'}).inject(this.win);
                 this.closeBtn = new Element('a', {id: 'sbox-btn-close', href: '#'}).inject(this.win);
                 this.fx = {
                         overlay: new Fx.Tween(this.overlay, $merge({
                                 property: 'opacity',
                                 onStart: Events.prototype.clearChain,
                                 duration: 250,
                                 link: 'cancel'
                         }, this.options.overlayFx)).set(0),
                         win: new Fx.Morph(this.win, $merge({
                                 onStart: Events.prototype.clearChain,
                                 unit: 'px',
                                 duration: 750,
                                 transition: Fx.Transitions.Quint.easeOut,
                                 link: 'cancel',
                                 unit: 'px'
                         }, this.options.resizeFx)),
                         content: new Fx.Tween(this.content, $merge({
                                 property: 'opacity',
                                 duration: 250,
                                 link: 'cancel'
                         }, this.options.contentFx)).set(0)
                 };
                 $(this.doc.body).adopt(this.overlay, this.win);
         },
 
         assign: function(to, options) {
                 return ($(to) || $$(to)).addEvent('click', function() {
                         return !SqueezeBox.fromElement(this, options);
                 });
         },
 
         open: function(subject, options) {
                 this.initialize();
 
                 if (this.element != null) this.trash();
                 this.element = $(subject) || false;
 
                 this.setOptions($merge(this.presets, options || {}));
 
                 if (this.element && this.options.parse) {
                         var obj = this.element.getProperty(this.options.parse);
                         if (obj && (obj = JSON.decode(obj, this.options.parseSecure))) this.setOptions(obj);
                 }
                 this.url = ((this.element) ? (this.element.get('href')) : subject) || this.options.url || '';
 
                 this.assignOptions();
 
                 var handler = handler || this.options.handler;
                 if (handler) return this.setContent(handler, this.parsers[handler].call(this, true));
                 var ret = false;
                 return this.parsers.some(function(parser, key) {
                         var content = parser.call(this);
                         if (content) {
                                 ret = this.setContent(key, content);
                                 return true;
                         }
                         return false;
                 }, this);
         },
 
         fromElement: function(from, options) {
                 return this.open(from, options);
         },
 
         assignOptions: function() {
                 this.overlay.set('class', this.options.classOverlay);
                 this.win.set('class', this.options.classWindow);
                 if (Browser.Engine.trident4) this.win.addClass('sbox-window-ie6');
         },
 
         close: function(e) {
                 var stoppable = ($type(e) == 'event');
                 if (stoppable) e.stop();
                 if (!this.isOpen || (stoppable && !$lambda(this.options.closable).call(this, e))) return this;
                 this.fx.overlay.start(0).chain(this.toggleOverlay.bind(this));
                 this.win.setStyle('display', 'none');
                 this.fireEvent('onClose', [this.content]);
                 this.trash();
                 this.toggleListeners();
                 this.isOpen = false;
                 return this;
         },
 
         trash: function() {
                 this.element = this.asset = null;
                 this.content.empty();
                 this.options = {};
                 this.removeEvents().setOptions(this.presets).callChain();
         },
 
         onError: function() {
                 this.asset = null;
                 this.setContent('string', this.options.errorMsg || 'An error occurred');
         },
 
         setContent: function(handler, content) {
                 if (!this.handlers[handler]) return false;
                 this.content.className = 'sbox-content-' + handler;
                 this.applyTimer = this.applyContent.delay(this.fx.overlay.options.duration, this, this.handlers[handler].call(this, content));
                 if (this.overlay.retrieve('opacity')) return this;
                 this.toggleOverlay(true);
                 this.fx.overlay.start(this.options.overlayOpacity);
                 return this.reposition();
         },
 
         applyContent: function(content, size) {
                 if (!this.isOpen && !this.applyTimer) return;
                 this.applyTimer = $clear(this.applyTimer);
                 this.hideContent();
                 if (!content) {
                         this.toggleLoading(true);
                 } else {
                         if (this.isLoading) this.toggleLoading(false);
                         this.fireEvent('onUpdate', [this.content], 20);
                 }
                 if (content) {
                         if (['string', 'array'].contains($type(content))) this.content.set('html', content);
                         else if (!this.content.hasChild(content)) this.content.adopt(content);
                 }
                 this.callChain();
                 if (!this.isOpen) {
                         this.toggleListeners(true);
                         this.resize(size, true);
                         this.isOpen = true;
                         this.fireEvent('onOpen', [this.content]);
                 } else {
                         this.resize(size);
                 }
         },
 
         resize: function(size, instantly) {
                 this.showTimer = $clear(this.showTimer || null);
                 var box = this.doc.getSize(), scroll = this.doc.getScroll();
                 this.size = $merge((this.isLoading) ? this.options.sizeLoading : this.options.size, size);
                 var parentSize = self.getSize();
                 if(this.size.x == parentSize.x){
                         this.size.y = this.size.y - 50;
                         this.size.x = this.size.x - 20;
                 }
                 var to = {
                         width: this.size.x,
                         height: this.size.y,
                         left: (scroll.x + (box.x - this.size.x - this.options.marginInner.x) / 2).toInt(),
                         top: (scroll.y + (box.y - this.size.y - this.options.marginInner.y) / 2).toInt()
                 };
                 this.hideContent();
                 if (!instantly) {
                         this.fx.win.start(to).chain(this.showContent.bind(this));
                 } else {
                         this.win.setStyles(to).setStyle('display', '');
                         this.showTimer = this.showContent.delay(50, this);
                 }
                 return this.reposition();
         },
 
         toggleListeners: function(state) {
                 var fn = (state) ? 'addEvent' : 'removeEvent';
                 this.closeBtn[fn]('click', this.bound.close);
                 this.overlay[fn]('click', this.bound.close);
                 this.doc[fn]('keydown', this.bound.key)[fn]('mousewheel', this.bound.scroll);
                 this.doc.getWindow()[fn]('resize', this.bound.window)[fn]('scroll', this.bound.window);
         },
 
         toggleLoading: function(state) {
                 this.isLoading = state;
                 this.win[(state) ? 'addClass' : 'removeClass']('sbox-loading');
                 if (state) this.fireEvent('onLoading', [this.win]);
         },
 
         toggleOverlay: function(state) {
                 var full = this.doc.getSize().x;
                 this.overlay.setStyle('display', (state) ? '' : 'none');
                 this.doc.body[(state) ? 'addClass' : 'removeClass']('body-overlayed');
                 if (state) {
                         this.scrollOffset = this.doc.getWindow().getSize().x - full;
                 } else {
                         this.doc.body.setStyle('margin-right', '');
                 }
         },
 
         showContent: function() {
                 if (this.content.get('opacity')) this.fireEvent('onShow', [this.win]);
                 this.fx.content.start(1);
         },
 
         hideContent: function() {
                 if (!this.content.get('opacity')) this.fireEvent('onHide', [this.win]);
                 this.fx.content.cancel().set(0);
         },
 
         onKey: function(e) {
                 switch (e.key) {
                         case 'esc': this.close(e);
                         case 'up': case 'down': return false;
                 }
         },
 
         checkTarget: function(e) {
                 return this.content.hasChild(e.target);
         },
 
         reposition: function() {
                 var size = this.doc.getSize(), scroll = this.doc.getScroll(), ssize = this.doc.getScrollSize();
                 var over = this.overlay.getStyles('height');
                 var j =parseInt(over.height);
                 if( ssize.y > j && size.y >= j){
                         this.overlay.setStyles({
                                 width: ssize.x + 'px',
                                 height: ssize.y + 'px'
                         });
                         this.win.setStyles({
                                 left: (scroll.x + (size.x - this.win.offsetWidth) / 2 - this.scrollOffset).toInt() + 'px',
                                 top: (scroll.y + (size.y - this.win.offsetHeight) / 2).toInt() + 'px'
                                 
                         });
                 }
                 return this.fireEvent('onMove', [this.overlay, this.win]);
         },
 
         removeEvents: function(type){
                 if (!this.$events) return this;
                 if (!type) this.$events = null;
                 else if (this.$events[type]) this.$events[type] = null;
                 return this;
         },
 
         extend: function(properties) {
                 return $extend(this, properties);
         },
 
         handlers: new Hash(),
 
         parsers: new Hash()
 
 };
 
 //SqueezeBox.extend(new Events($empty)).extend(new Options($empty)).extend(new Chain($empty));
 
 SqueezeBox.parsers.extend({
 
         image: function(preset) {
                 return (preset || (/\.(?:jpg|png|gif)$/i).test(this.url)) ? this.url : false;
         },
 
         clone: function(preset) {
                 if ($(this.options.target)) return $(this.options.target);
                 if (this.element && !this.element.parentNode) return this.element;
                 var bits = this.url.match(/#([\w-]+)$/);
                 return (bits) ? $(bits[1]) : (preset ? this.element : false);
         },
 
         ajax: function(preset) {
                 return (preset || (this.url && !(/^(?:javascript|#)/i).test(this.url))) ? this.url : false;
         },
 
         iframe: function(preset) {
                 return (preset || this.url) ? this.url : false;
         },
 
         string: function(preset) {
                 return true;
         }
 });
 
 SqueezeBox.handlers.extend({
 
         image: function(url) {
                 var size, tmp = new Image();
                 this.asset = null;
                 tmp.onload = tmp.onabort = tmp.onerror = (function() {
                         tmp.onload = tmp.onabort = tmp.onerror = null;
                         if (!tmp.width) {
                                 this.onError.delay(10, this);
                                 return;
                         }
                         var box = this.doc.getSize();
                         box.x -= this.options.marginImage.x;
                         box.y -= this.options.marginImage.y;
                         size = {x: tmp.width, y: tmp.height};
                         for (var i = 2; i--;) {
                                 if (size.x > box.x) {
                                         size.y *= box.x / size.x;
                                         size.x = box.x;
                                 } else if (size.y > box.y) {
                                         size.x *= box.y / size.y;
                                         size.y = box.y;
                                 }
                         }
                         size.x = size.x.toInt();
                         size.y = size.y.toInt();
                         this.asset = $(tmp);
                         tmp = null;
                         this.asset.width = size.x;
                         this.asset.height = size.y;
                         this.applyContent(this.asset, size);
                 }).bind(this);
                 tmp.src = url;
                 if (tmp && tmp.onload && tmp.complete) tmp.onload();
                 return (this.asset) ? [this.asset, size] : null;
         },
 
         clone: function(el) {
                 if (el) return el.clone();
                 return this.onError();
         },
 
         adopt: function(el) {
                 if (el) return el;
                 return this.onError();
         },
 
         ajax: function(url) {
                 var options = this.options.ajaxOptions || {};
                 this.asset = new Request.HTML($merge({
                         method: 'get',
                         evalScripts: false
                 }, this.options.ajaxOptions)).addEvents({
                         onSuccess: function(resp) {
                                 this.applyContent(resp);
                                 if (options.evalScripts !== null && !options.evalScripts) $exec(this.asset.response.javascript);
                                 this.fireEvent('onAjax', [resp, this.asset]);
                                 this.asset = null;
                         }.bind(this),
                         onFailure: this.onError.bind(this)
                 });
                 this.asset.send.delay(10, this.asset, [{url: url}]);
         },
 
         iframe: function(url) {
                 this.asset = new Element('iframe', $merge({
                         src: url,
                         frameBorder: 0,
                         width: this.options.size.x,
                         height: this.options.size.y
                 }, this.options.iframeOptions));
                 if (this.options.iframePreload) {
                         this.asset.addEvent('load', function() {
                                 this.applyContent(this.asset.setStyle('display', ''));
                         }.bind(this));
                         this.asset.setStyle('display', 'none').inject(this.content);
                         return false;
                 }
                 return this.asset;
         },
 
         string: function(str) {
                 return str;
         }
 
 });
 
 SqueezeBox.handlers.url = SqueezeBox.handlers.ajax;
 SqueezeBox.parsers.url = SqueezeBox.parsers.ajax;
 SqueezeBox.parsers.adopt = SqueezeBox.parsers.clone;

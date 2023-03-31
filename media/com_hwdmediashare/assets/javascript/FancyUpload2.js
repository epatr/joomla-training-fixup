/**
 * FancyUpload - Flash meets Ajax for powerful and elegant uploads.
 * 
 * Updated to latest 3.0 API. Hopefully 100% compat!
 *
 * @version		3.0
 *
 * @license		MIT License
 *
 * @author		Harald Kirschner <http://digitarald.de>
 * @copyright	Authors
 */

var FancyUpload2 = new Class({

	Extends: Swiff.Uploader,
	
	options: {
		queued: 1,
		// compat
		limitSize: 0,
		limitFiles: 0,
		validateFile: Function.from(true)
	},

	initialize: function(status, list, options) {
		this.status = $(status);
		this.list = $(list);

		// compat
		options.fileClass = options.fileClass || FancyUpload2.File;
		options.fileSizeMax = options.limitSize || options.fileSizeMax;
		options.fileListMax = options.limitFiles || options.fileListMax;

		this.parent(options);

		this.addEvents({
			'load': this.render,
			'select': this.onSelect,
			'cancel': this.onCancel,
			'start': this.onStart,
			'queue': this.onQueue,
			'complete': this.onComplete
		});
	},

	render: function() {
		this.overallTitle = this.status.getElement('.overall-title');
		this.currentTitle = this.status.getElement('.current-title');
		this.currentText = this.status.getElement('.current-text');

		var progress = this.status.getElement('.overall-progress');
		this.overallProgress = new Fx.ProgressBar(progress, {
			text: new Element('span', {'class': 'progress-text'}).inject(progress, 'after'),
                        active: 'overall-progress-active'
		});
		progress = this.status.getElement('.current-progress')
		this.currentProgress = new Fx.ProgressBar(progress, {
			text: new Element('span', {'class': 'progress-text'}).inject(progress, 'after'),
                        active: 'current-progress-active'
		});
				
		this.updateOverall();
	},

	onSelect: function() {
		this.status.removeClass('status-browsing');
	},

	onCancel: function() {
		this.status.removeClass('file-browsing');
	},

	onStart: function() {
		this.status.addClass('file-uploading');
		this.overallProgress.set(0);
	},

	onQueue: function() {
		this.updateOverall();
	},

	onComplete: function() {
		this.status.removeClass('file-uploading');
		if (this.size) {
			this.overallProgress.start(100);
		} else {
			this.overallProgress.set(0);
			this.currentProgress.set(0);
		}
		
	},

	updateOverall: function() {
		this.overallTitle.set('html', window.hwdms_text_progressOverall.substitute({
			total: Swiff.Uploader.formatUnit(this.size, 'b')
		}));
		if (!this.size) {
			this.currentTitle.set('html', window.hwdms_text_currentTitle);
			this.currentText.set('html', '');
		}
	},
	
	/**
	 * compat
	 */
	upload: function() {
		this.start();
	},
	
	removeFile: function() {
		return this.remove();
	}

});

FancyUpload2.File = new Class({
	
	Extends: Swiff.Uploader.File,

	render: function() {
		if (this.invalid) {
			if (this.validationError) {
				var msg = window.hwdms_text_validationErrors[this.validationError] || this.validationError;
				this.validationErrorMessage = msg.substitute({
					name: this.name,
					size: Swiff.Uploader.formatUnit(this.size, 'b'),
					fileSizeMin: Swiff.Uploader.formatUnit(this.base.options.fileSizeMin || 0, 'b'),
					fileSizeMax: Swiff.Uploader.formatUnit(this.base.options.fileSizeMax || 0, 'b'),
					fileListMax: this.base.options.fileListMax || 0,
					fileListSizeMax: Swiff.Uploader.formatUnit(this.base.options.fileListSizeMax || 0, 'b')
				});
			}
			this.remove();
			return;
		}
		
		this.addEvents({
			'start': this.onStart,
			'progress': this.onProgress,
			'complete': this.onComplete,
			'error': this.onError,
			'remove': this.onRemove
		});
		
		this.info = new Element('span', {'class': 'file-info'});
		this.element = new Element('li', {'class': 'file'}).adopt(
			new Element('span', {'class': 'file-size', 'html': Swiff.Uploader.formatUnit(this.size, 'b')}),
			new Element('a', {
				'class': 'file-remove',
				href: '#',
				html: window.hwdms_text_remove,
				title: window.hwdms_text_removeTitle,
				events: {
					click: function() {
						this.remove();
						return false;
					}.bind(this)
				}
			}),
			new Element('span', {'class': 'file-name', 'html': window.hwdms_text_fileName.substitute(this)}),
			this.info
		).inject(this.base.list);
	},
	
	validate: function() {
		return (this.parent() && this.base.options.validateFile(this));
	},
	
	onStart: function() {
		this.element.addClass('file-uploading');
		this.base.currentProgress.cancel().set(0);
		this.base.currentTitle.set('html', window.hwdms_text_currentTitle.substitute(this));
	},

	onProgress: function() {
		this.base.overallProgress.start(this.base.percentLoaded);
		this.base.currentText.set('html', window.hwdms_text_currentProgress.substitute({
			rate: (this.progress.rate) ? Swiff.Uploader.formatUnit(this.progress.rate, 'bps') : '- B',
			bytesLoaded: Swiff.Uploader.formatUnit(this.progress.bytesLoaded, 'b'),
			timeRemaining: (this.progress.timeRemaining) ? Swiff.Uploader.formatUnit(this.progress.timeRemaining, 's') : '-'
		}));
		this.base.currentProgress.start(this.progress.percentLoaded);
	},
	
	onComplete: function() {
		this.element.removeClass('file-uploading');
		
		this.base.currentText.set('html', window.hwdms_text_uploadComplete);
		this.base.currentProgress.start(100);
		
		if (this.response.error) {
			var msg = MooTools.lang.get('FancyUpload', 'errors')[this.response.error] || '{error} #{code}';
			this.errorMessage = msg.substitute(Object.append({
				name: this.name,
				size: Swiff.Uploader.formatUnit(this.size, 'b')
			}, this.response));
			var args = [this, this.errorMessage, this.response];
			
			this.fireEvent('error', args).base.fireEvent('fileError', args);
		} else {
			this.base.fireEvent('fileSuccess', [this, this.response.text || '']);
		}
	},

	onError: function() {
		this.element.addClass('file-failed');
		var error = window.hwdms_text_fileError.substitute(this);
		this.info.set('html', '<strong>' + error + ':</strong> ' + this.errorMessage);
	},

	onRemove: function() {
		this.element.getElements('a').setStyle('visibility', 'hidden');
		this.element.fade('out').retrieve('tween').chain(Element.destroy.bind(Element, this.element));
	}
	
});

// Avoiding MooTools.lang dependency
(function() {
	var phrases = {
		'progressOverall': window.hwdms_text_progressOverall,
		'currentTitle': window.hwdms_text_currentTitle,
		'currentFile': window.hwdms_text_currentFile,
		'currentProgress': window.hwdms_text_currentProgress,
		'fileName': window.hwdms_text_fileName,
		'remove': window.hwdms_text_remove,
		'removeTitle': window.hwdms_text_removeTitle,
		'fileError': window.hwdms_text_fileError,
		'validationErrors': {
			'duplicate': 'File <em>{name}</em> is already added, duplicates are not allowed.',
			'sizeLimitMin': 'File <em>{name}</em> (<em>{size}</em>) is too small, the minimal file size is {fileSizeMin}.',
			'sizeLimitMax': window.hwdms_text_sizeLimitMax,
			'fileListMax': 'File <em>{name}</em> could not be added, amount of <em>{fileListMax} files</em> exceeded.',
			'fileListSizeMax': 'File <em>{name}</em> (<em>{size}</em>) is too big, overall filesize of <em>{fileListSizeMax}</em> exceeded.'
		},
		'errors': {
			'httpStatus': 'Server returned HTTP-Status <code>#{code}</code>',
			'securityError': 'Security error occured ({text})',
			'ioError': 'Error caused a send or load operation to fail ({text})'
		}
	};
	
	if (MooTools.lang) {
		MooTools.lang.set('en-US', 'FancyUpload', phrases);
	} else {
		MooTools.lang = {
			get: function(from, key) {
				return phrases[key];
			}
		};
	}
})();

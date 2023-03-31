/*
 * ARI Quiz Reloader Joomla! plugin
 *
 * @package		ARI Quiz Reloader Joomla! plugin
 * @version		1.0.0
 * @author		ARI Soft
 * @copyright	Copyright (c) 2009 www.ari-soft.com. All rights reserved
 * @license		GNU/GPL (http://www.gnu.org/copyleft/gpl.html)
 * 
 */

(function() {
	function attachEvent(el, ev, handler) {
		var eventHandler = function(e) {
			return handler(e || window.event);
		};

		if (el.addEventListener)  { 
			el.addEventListener(ev, eventHandler, false); 
		} else if (el.attachEvent)  { 
			el.attachEvent("on" + ev, eventHandler); 
		}
	};

	function extendQuizAjaxManager() {
		var quizAjaxManager = typeof(window.ariQuizQueManager) != 'undefined'
			? window.ariQuizQueManager.ajaxManager
			: YAHOO.ARISoft.ariQuiz.ajaxQuestionManager.prototype,
			quizQuestionManager = typeof(window.ariQuizQueManager) != 'undefined'
				? window.ariQuizQueManager
				: YAHOO.ARISoft.ariQuiz.questionManager.prototype;

		if (typeof(quizAjaxManager.prevPage) != 'undefined') {
			var prevPageHandler = quizAjaxManager.prevPage;
			
			quizAjaxManager.prevPage = function(questionData, callback) {
				callback = callback || {};
				var successHandler = callback['success'];

				callback['success'] = function(response) {
					var responseText = response.responseText;
					var result = YAHOO.lang.JSON.parse(responseText);
					
					if (result)
					{
						this._reload();
						return ;
					};

					if (successHandler) successHandler.apply(this, arguments);
				};
				
				prevPageHandler.apply(this, arguments);
			};
		};

		if (typeof(quizAjaxManager.nextPage) != 'undefined') {
			var nextPageHandler = quizAjaxManager.nextPage;
			
			quizAjaxManager.nextPage = function(questionData, callback) {
				callback = callback || {};
				var successHandler = callback['success'];

				callback['success'] = function(response) {
					var responseText = response.responseText;
					var result = YAHOO.lang.JSON.parse(responseText);
					
					if (result)
					{
						this._reload();
						return ;
					};

					if (successHandler) successHandler.apply(this, arguments);
				};
				
				nextPageHandler.apply(this, arguments);
			};
		};
		
		if (typeof(quizAjaxManager.savePage) != 'undefined') {
			var savePageHandler = quizAjaxManager.savePage;

			quizAjaxManager.savePage = function(questionData, callback) {
				callback = callback || {};
				var successHandler = callback['success'];
				
				callback['success'] = function(response) {
					var responseText = response.responseText;
					var result = YAHOO.lang.JSON.parse(responseText);
					
					if (result && 
						(typeof(result["moveToNext"]) == "undefined" || result["moveToNext"]) && 
						(typeof(result["showExplanation"]) == "undefined" || !result["showExplanation"]) &&
						(typeof(result["tryAgain"]) == "undefined" || !result["tryAgain"])
						)
					{
						this._reload();
						return ;
					};
					
					if (successHandler) successHandler.apply(this, arguments);
				};
				
				savePageHandler.apply(this, arguments);
			};
		};
		
		if (typeof(quizAjaxManager.skipQuestion) != 'undefined') {
			var skipQuestionHandler = quizAjaxManager.skipQuestion;
			
			quizAjaxManager.skipQuestion = function(questionData, callback) {
				callback = callback || {};
				var successHandler = callback['success'];
				
				callback['success'] = function(response)
				{
					var responseText = response.responseText;
					var result = YAHOO.lang.JSON.parse(responseText);
					
					if (result)
					{
						this._reload();
						return ;
					};
					
					if (successHandler) successHandler.apply(this, arguments);
				};
				
				skipQuestionHandler.apply(this, arguments);
			};
		};
		
		if (typeof(quizAjaxManager.saveQuestion) != 'undefined') {
			var saveQuestionHandler = quizAjaxManager.saveQuestion;

			quizAjaxManager.saveQuestion = function(questionData, callback) {
				callback = callback || {};
				var successHandler = callback['success'];
				
				callback['success'] = function(response) {
					var responseText = response.responseText;
					var result = YAHOO.lang.JSON.parse(responseText);
					
					if (result && 
						(typeof(result["moveToNext"]) == "undefined" || result["moveToNext"]) && 
						(typeof(result["showExplanation"]) == "undefined" || !result["showExplanation"]) &&
						(typeof(result["tryAgain"]) == "undefined" || !result["tryAgain"])
						)
					{
						this._reload();
						return ;
					};
					
					if (successHandler) successHandler.apply(this, arguments);
				};
				
				saveQuestionHandler.apply(this, arguments);
			};
		};

		if (typeof(quizQuestionManager.hideExplanationQuestion) != 'undefined') {
			var hideExplanationQuestionHandler = quizQuestionManager.hideExplanationQuestion;

			quizQuestionManager.hideExplanationQuestion = function() {
				this._reload();
				return ;
			}
		};
	};

	attachEvent(window, 'load', function() {
		extendQuizAjaxManager();
	});
})();
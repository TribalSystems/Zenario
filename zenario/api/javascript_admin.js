/*
 * Copyright (c) 2014, Tribal Limited
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of Zenario, Tribal Limited nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL TRIBAL LTD BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

/*
	This file contains JavaScript source code.
	The code here is not the code you see in your browser. Before this file is downloaded:
	
		1. Compilation macros are applied (e.g. "foreach" is a macro for "for .. in ... hasOwnProperty").
		2. It is minified (e.g. using Google Closure Compiler).
		3. It may be wrapped togther with other files (this is to reduce the number of http requests on a page).
	
	For more information, see js_minify.shell.php for steps (1) and (2), and "inc.js.php" files for step (3).
*/


(function(
	URLBasePath,
	window, document,
	zenario, zenarioA, zenarioTab, zenarioAT,
	get, phrase,
	undefined
) {
	"use strict";

	/**
	  * This section lists important JavaScript functions from the core CMS in Admin Mode
	  * Other functions are tucked away in the /js folder
	 */
	//Wrapper to the underscore.js function's template library
	zenarioA.microTemplate = function(template, data, i) {
		if (template === undefined || !data) {
			return '';
	
		} else if (_.isArray(data)) {
			var l = data.length,
				html = '';
			for (var j = 0; j < l; ++j) {
				html += zenarioA.microTemplate(template, data[j], j);
			}
			return html;
		}
	
		if (data.i === undefined && i !== undefined) {
			data.i = 1*i;
		}
	
		if (zenarioA.microTemplates[template]) {
			//Named templates from an existing list
			if (typeof zenarioA.microTemplates[template] == 'string') {
				try {
					var tmp = $.extend({}, _.templateSettings, true);
					//_.templateSettings = {variable: 'm', escape: /\[\[(.+?)\]\]/g, interpolate: /\{\{(.+?)\}\}/g, evaluate: /<%([\s\S]+?)%>/g};
					_.templateSettings = {variable: 'm', escape: false, interpolate: /\{\{(.+?)\}\}/g, evaluate: /[<\{]%([\s\S]+?)%[>\}]/g, twigStyleSyntax: true};
			
						zenarioA.microTemplates[template] = _.template(zenarioA.microTemplates[template]);
			
					_.templateSettings = tmp;
				} catch (e) {
					console.log('Error in template ' + template + ': \n\n' + zenarioA.microTemplates[template]);
					throw e;
				}
			}
		
			return zenarioA.microTemplates[template](data);
	
		} else {
			//Custom/one-off bispoke templates
			//return _.template(template, data, {variable: 'm', escape: /\[\[(.+?)\]\]/g, interpolate: /\{\{([\s\S]+?)\}\}/g});
			return _.template(template, data, {variable: 'm', escape: false, interpolate: /\{\{(.+?)\}\}/g, evaluate: /\{%([\s\S]+?)%\}/g, twigStyleSyntax: true});
		}
	};


	zenarioA.showAJAXLoader = function() {
		$(document.body).addClass('zenario_adminAJAXLoaderOpen');
		zenarioA.openBox('<div></div>', 'zenario_fbAdminAJAXLoader', 'AdminAJAXLoader', false, false, 50, 1, true, true, false, false);
	};

	zenarioA.hideAJAXLoader = function() {
		$(document.body).removeClass('zenario_adminAJAXLoaderOpen');
		zenarioA.closeBox('AdminAJAXLoader', false);
	};

	zenarioA.nowDoingSomething = function(something, showImmediately) {
	
		$('#zenario_now_loading').clearQueue().hide();
		$('#zenario_now_saving').clearQueue().hide();
		if (zenarioAB.welcome) {
			$('#zenario_now_installing').clearQueue().hide();
		}
	
		if (something) {
			if (showImmediately) {
				$('#zenario_now_' + something)
					.fadeIn(1000);
			} else {
				$('#zenario_now_' + something)
					.delay(900)
					.fadeIn(2000);
			}
		}
	};



	zenarioA.infoBox = function() {
		var html,
			moduleClassName = 'zenario_common_features',
			requests = {infoBox: 1},
			url = URLBasePath + 'zenario/ajax.php?__pluginClassName__=' + moduleClassName + '&method_call=handleAJAX' + zenario.urlRequest(requests);
	
		if (html = zenario.checkSessionStorage(url)) {
			zenarioA.infoBox2(html);
	
		} else {
			zenarioA.showAJAXLoader();
			$.get(url, function(html) {
				zenarioA.hideAJAXLoader();
				if (!zenarioA.loggedOut(html)) {
					zenario.setSessionStorage(html, url);
					zenarioA.infoBox2(html);
				}
			}, 'text');
		}
	};

	zenarioA.closeInfoBox = function() {
		zenarioA.closeBox('AdminInfoBox');
	};

	zenarioA.showMessage = function(message, buttonsHTML, messageType, modal, htmlEscapeMessage) {
		var end = false,
			hadCommand = false;
	
		if (message) {
			message = '' + message;
		} else {
			message = (zenario.showDevTools? phrase.errorTimedOutDev : phrase.errorTimedOut);
		}
	
		if (buttonsHTML === undefined) {
			buttonsHTML = true;
		}
	
		//Message types
		if (messageType === undefined) {
			messageType = 'none';
		}
	
		if		  (message.substr(0, 24) == '<!--Message_Type:None-->') {
			message = message.substr(24);
			messageType = false;
		} else if (message.substr(0, 25) == '<!--Message_Type:Error-->') {
			message = message.substr(25);
			messageType = 'error';
		} else if (message.substr(0, 27) == '<!--Message_Type:Success-->') {
			message = message.substr(27);
			messageType = 'success';
		} else if (message.substr(0, 27) == '<!--Message_Type:Warning-->') {
			message = message.substr(27);
			messageType = 'warning';
		} else if (message.substr(0, 28) == '<!--Message_Type:Question-->') {
			message = message.substr(28);
			messageType = 'question';
		}
	
	
		//Commands
	
		//Reload Storekeeper
		if (message.substr(0, 25) == '<!--Reload_Storekeeper-->' && zenarioO.init && !window.zenarioOQuickMode && !window.zenarioOSelectMode) {
			//Still show the Admin the contents of the message via an alert, if there was a message
			message = message.substr(25);
			if (message) {
				alert(message);
			}
		
			zenarioA.uploading = false;
			zenarioO.setWrapperClass('uploading', zenarioA.uploading);
		
			zenarioO.reload();
		
			return false;
	
		//Refresh Storekeeper
		} else if (message.substr(0, 26) == '<!--Refresh_Storekeeper-->' && zenarioO.init && !window.zenarioOQuickMode && !window.zenarioOSelectMode) {
			zenarioO.refresh();
			message = message.substr(26);
			hadCommand = true;
	
		//Go somewhere in Storekeeper
		} else if (message.substr(0, 28) == '<!--Go_To_Storekeeper_Panel:' && (end = message.indexOf('-->')) != -1) {
			zenarioO.go(message.substr(28, end - 28), -1);
			message = message.substr(end + 3);
			hadCommand = true;
	
		//Open an Admin Box
		} else if (message.substr(0, 19) == '<!--Open_Admin_Box:' && (end = message.indexOf('-->')) != -1) {
			zenarioAB.open(message.substr(19, end - 19));
			message = message.substr(end + 3);
			hadCommand = true;
	
		//Go somewhere
		} else if (message.substr(0, 14) == '<!--Go_To_URL:' && (end = message.indexOf('-->')) != -1) {
			zenario.goToURL(zenario.addBasePath(message.substr(14, end - 14)));
			message = message.substr(end + 3);
			hadCommand = true;
		}
	
		if (hadCommand && !message) {
			return false;
		}
		
		if (message.substr(0, 12) == '<!--Modal-->') {
			message = message.substr(12);
			modal = true;
		}
	
		//Set some custom buttons
		if (message.substr(0, 16) == '<!--Button_HTML:' && (end = message.indexOf('-->')) != -1) {
			buttonsHTML = message.substr(16, end - 16);
			message = message.substr(end + 3);
	
		} else if (message.substr(0, 18) == '<!--Reload_Button:' && (end = message.indexOf('-->')) != -1) {
			buttonsHTML = '<input class="submit_selected" type="button" onclick="document.location.href = document.location.href; return false;" value="' + message.substr(18, end - 18) + '">';
			message = message.substr(end + 3);
	
		} else if (message.substr(0, 17) == '<!--Logged_Out-->') {
			
			//If the admin has been logged out, check to see whether this window is in an iframe, and show the login window in the iframe if possible.
			if (zenarioA.loggedOutIframeCheck(message, messageType)) {
				return;
			}
			
			if (!zenarioO.init) {
				buttonsHTML = 
					'<input type="button" value="' + phrase.logIn + '" class="submit_selected"' +
					' onclick="zenario.goToURL(zenario.linkToItem(zenario.cID, zenario.cType, \'\', true));">' +
					'<input type="button" class="submit" value="' + phrase.cancel + '" onclick="zenario.goToURL(zenario.linkToItem(zenario.cID, zenario.cType));"/>';
		
			} else {
				buttonsHTML =
					'<input type="button" value="' + phrase.logIn + '" class="submit_selected" onclick="zenarioO.reload(undefined, true);">' +
					'<input type="button" class="submit" value="' + phrase.cancel + '" onclick="zenario.goToURL(URLBasePath);"/>';
			}
			
			message = message.substr(17);
			modal = true;
		}
	
		//Attempt to strip out JSON encoded data from error messages that would cause confusion
		if (messageType == 'error' && (end = message.indexOf('{"')) != -1) {
			try {
				var data = JSON.parse(message.substr(end));
			
				if (typeof data != 'object') {
					throw 0;
				}
			
				//If there was JSON encoded data, remove it and show the message without it
				message = message.substr(0, end);
			} catch (e) {
			}
		}
	
		zenarioA.floatingBox(message, buttonsHTML, messageType, modal, htmlEscapeMessage);
		return true;
	};


	zenarioA.notification = function(message) {
	
		get('zenario_notification').style.display = '';
		get('zenario_notification').style.top = zenario.scrollTop() + 'px';
		get('zenario_notification').innerHTML = '<div><h1>' + zenario.htmlspecialchars(message) + '</h1></div>';
	
		$('#zenario_notification div')
			.clearQueue()
			.show({effect: 'drop', duration: 500, direction: 'up'})
			.delay(2500)
			.hide({effect: 'drop', duration: 500, direction: 'up', complete: function() {
				get('zenario_notification').style.display = 'none';
			}});
	};


	zenarioA.showHelp = function(selector) {
		var intro = introJs(),
			$fbsk = $('#zenario_fbsk'),
			$els = $(selector),
			steps = [],
			data;
	
		//Hack to get intro.js working with Organizer
		$fbsk.addClass('zenario_introjs_fixPosition');
	
		//For each element, convert it into the format needed by intro.js
		$els.each(function(i, el) {
			data = $(el).data();
			data.element = el;
			steps.push(data);
		});
	
		//Sort by step number
		steps.sort(function(a, b) {
			if (a.step == b.step) {
				return 0;
			} else  {
				return a.step < b.step? -1 : 1;
			}
		});
	
		//Open intro.js
		intro.setOptions({steps: steps});
		intro.start();
	
		intro.onexit(function() {
			//Hack to get intro.js working with Organizer
			$fbsk.removeClass('zenario_introjs_fixPosition');
		});
	};

	//Get information on a single item from Storekeeper 
	zenarioA.getSKItem = function(path, id) {
		var i,
			data,
			first = false,
			url =
				URLBasePath +
				'zenario/admin/ajax.php?_json=1&_start=0&_get_item_name=1&path=' + encodeURIComponent(path);
		
		if (id !== undefined) {
			url += '&_item=';
			
			if (typeof id == 'object') {
				foreach (id as i) {
					if (first) {
						first = false;
					} else {
						url += ',';
					}
					url += encodeURIComponent(id[i]);
				}
			} else {
				url += encodeURIComponent(id) + '&_limit=1';
			}
		}
	
		if (data = zenario.checkSessionStorage(url, {}, true)) {
			return data;
		} else {
			data = zenario.nonAsyncAJAX(url, false, true);
			zenario.setSessionStorage(data, url, {}, true);
			return data;
		}
	};

	//Get information on a file
	zenarioA.lookupFileDetails = function(id) {
		var i,
			data,
			first = false,
			url =
				URLBasePath +
				'zenario/admin/quick_ajax.php?lookupFileDetails=' + encodeURIComponent(id);
	
		if (data = zenario.checkSessionStorage(url, '', true)) {
			return data;
		} else {
			data = zenario.nonAsyncAJAX(url, false, true);
			zenario.setSessionStorage(data, url, '', true);
			return data;
		}
	};


	zenarioA.toggleSlotWand = function() {
		if (zenarioA.checkForEdits()) {
			zenarioA.slotWandOn = !zenarioA.slotWandOn;
			zenarioAT.clickTab(zenarioA.toolbar);
		}
	};


	zenarioA.toggleShowGrid = function() {
		if (zenarioA.checkForEdits()) {
			zenarioA.showGridOn = !zenarioA.showGridOn;
			zenarioAT.clickTab(zenarioA.toolbar);
		}
	};

})(
	URLBasePath,
	window, document,
	zenario, zenarioA, zenarioTab, zenarioAT,
	zenario.get, zenarioA.phrase);
/*
 * Copyright (c) 2017, Tribal Limited
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
	
	For more information, see js_minify.shell.php for steps (1) and (2), and visitor.wrapper.js.php for step (3).
*/


zenario.lib(function(
	undefined,
	URLBasePath,
	document, window, windowOpener, windowParent,
	zenario, zenarioA, zenarioAB, zenarioAT, zenarioO, strings,
	encodeURIComponent
	//N.b. the rest of the shortcut functions that normally go here haven't been defined yet!
	//They are actually defined below.
) {
	"use strict";
	
	var userAgent = navigator.userAgent,
		scrollBody = (
			/Edge\//.test(userAgent)
		 || (/Safari/.test(userAgent)
		  && !/Chrome\//.test(userAgent))
		)?
			'body'
		  : 'html, body';
	

	/**
	  * This section lists important JavaScript functions from the core CMS in Visitor Mode
	  * Other functions are tucked away in the /js folder
	 */

	
	//This is a shortcut function for initialising a new class.
	//It just uses normal JavaScript class inheritance, but it makes the syntax
	//a little more readable and friendly when creating a new class
//	zenario.extensionOf = function(parent, initFun) {}
	
	//This is a shortcut function for accessing the prototype of a class so you
	//can then define its methods.
	//This is more of a longcut than a shortcut function, but it makes the syntax
	//a little more readable and friendly when setting the methods of a class.
	window.methodsOf =
	zenario.methodsOf = function(thisClass) {
		return thisClass.prototype;
	};
	
	//JavaScript version of our PHP in() function, which itself is similar to MySQL's IN()
	//Most of our functions use camelCase, but "in" is a reserved word in JavaScript.
	//However as JavaScript is case-sensitive we have just made this upper-case as a work-around.
	zenario.IN = function(value) {
		return _.contains(arguments, value, 1);
	};
	
	//Shortcut to document.getElementById()
	window.get = 
	zenario.get = function(el) {
		return document.getElementById(el);
	};

	//Shortcut to hasOwnProperty()
	window.has =
	zenario.has = function(o, k) {
		return o !== undefined && o.hasOwnProperty && o.hasOwnProperty(k);
	};

	//Given a string, this window.makes = function it safe to use in the URL after a hash (i.e. a safe id for Storekeeper)
	window.encodeItemIdForOrganizer =
	zenario.encodeItemIdForOrganizer =
	//Deprecated aliases
	window.encodeItemIdForStorekeeper =
	zenario.encodeItemIdForStorekeeper =
		function(id) {
			if (1*id == id) {
				return id;
			} else {
				return '~' + encodeURIComponent('' + id).replace(/~/g, '%7E').replace(/%/g, '~');
			}
		};

	//Reverses encodeItemIdForOrganizer()
	window.decodeItemIdForOrganizer =
	zenario.decodeItemIdForOrganizer =
	//Deprecated aliases
	window.decodeItemIdForStorekeeper =
	zenario.decodeItemIdForStorekeeper =
		function(id) {
			if (('' + id).substr(0, 1) == '~') {
				return decodeURIComponent(('' + id).substr(1).replace(/~/g, '%'));
			} else {
				return id;
			}
		};

	window.engToBoolean =
	zenario.engToBoolean = function(text) {
		
		var ty = typeof text;
		
		if (ty == 'function') {
			text = text();
			ty = typeof text;
		}
		
		switch (ty) {
			case 'object':
				return (text && text.length !== 0)? 1 : 0;
			case 'string':
				text = text.trim().toLowerCase();
				return (text && text != '0' && text != 'false' && text != 'no' && text != 'off')? 1 : 0;
			default:
				return text? 1 : 0;
		}
	};

	window.htmlspecialchars =
	zenario.htmlspecialchars = function(text, preserveLineBreaks, preserveSpaces) {
		
		if (_.isFunction(text)) {
			text = text();
		}
		
		if (text === undefined || text === null || text === false) {
			return '';
		}
	
		if (typeof text == 'object') {
			text = text.label || text.default_label || text.name || text.field_name;
		}
		
		text = ('' + text).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/\</g, '&lt;').replace(/>/g, '&gt;');
		
		if (preserveSpaces) {
			if (preserveSpaces !== 'asis') {
				text = text.replace(/ /g, '&nbsp;');
			}
		} else {
			text = $.trim(text);
		}
		
		if (preserveLineBreaks) {
			text = text.replace(/\n/g, '<br/>');
		}
		
		return text;
	};
	
	//Deprecated, please don't use this!
	window.ifNull =
	zenario.ifNull = function(a, b, c) {
		return a || b || c;
	};

	window.jsEscape =
	zenario.jsEscape = function(text) {
		return escape(text).replace(/\%u/g, '\\u').replace(/\%/g, '\\x');
	};
	
	//Fallback for browsers *cough IE* that don't have a CSS escape function,
	//as per http://stackoverflow.com/questions/2786538/need-to-escape-a-special-character-in-a-jquery-selector-string
	window.cssEscape =
	zenario.cssEscape = function(text) {
		if (window.CSS && CSS.escape) {
			return CSS.escape(text);
		} else {
			return text.replace(/[!"#$%&'()*+,.\/:;<=>?@[\\\]^`{|}~]/g, '\\$&');
		}
	};
	
	

	zenario.addBasePath = function(url) {
		if (url === undefined) {
			return undefined;
	
		} else if (url.indexOf('://') == -1 && url.substr(0, 1) != '/') {
			return URLBasePath + url;
	
		} else {
			return url;
		}
	};
	
	
//	zenario.refreshPluginSlot = function(slotName, instanceId, additionalRequests, recordInURL, scrollToTopOfSlot, fadeOutAndIn, useCache, post) {};

//	zenario.getSlotnameFromEl = function(el, getContainerId) {};

	zenario.getContainerIdFromEl = function(el) {
		return zenario.getSlotnameFromEl(el, true);
	};

	zenario.getEggIdFromEl = function(el) {
		var containerId = zenario.getContainerIdFromEl(el);
		
		return containerId
			&& typeof containerId == 'string'
			&& 1 * containerId.split('-')[1];
	};

	zenario.getContainerIdFromSlotName = function(slotName) {
		return 'plgslt_' + slotName;
	};

//	zenario.microTemplate = function(template, data, filter) {};
	

	//Make a non-asyncornous AJAX call.
	//Note that this is deprecated!
//	zenario.nonAsyncAJAX = function(url, post, json, useCache) {};
	
	//An easy-as-possible drop-in replacement for zenario.nonAsyncAJAX(), which is now deprecated.
	//It returns a zenario.callback object.
		//url: The URL of the request
		//post: Pass some POST requests in here to use POST. Or set to true to use POST without any POST requests.
		//json: Set to true to decode a JSON response
		//useCache: Store the response in the session cache, and use the cached results next time.
			//Won't apply to POST requests.
			//The cache results are cleared automatically if the data_rev in the database changes.
		//retry: If there's an error, show a "retry" button on the error message.
			//Only works in admin mode.
			//Can be a function to call, or true to recall this function
		//timeout: If set, the request will be automatically retried or cancelled after this amount of time.
//	zenario.ajax = function(url, post, json, useCache, retry, timeout, settings) {};
	
	
	
	
	

	zenario.phrases = {};
	zenario.loadPhrases = function(vlpClass, code) {
	
		var url = URLBasePath + 'zenario/ajax.php'
			+ '?method_call=loadPhrase'
			+ '&__class__=' + encodeURIComponent(vlpClass)
			+ '&langId=' + encodeURIComponent(zenario.langId);
	
		if (code !== undefined) {
			url += '&__code__=';
			
			if (_.isArray(code)) {
				url += _.map(code, zenario.encodeItemIdForOrganizer).join(',');
			} else {
				url += zenario.encodeItemIdForOrganizer(code);
			}
		}
	
		var phrases = zenario.nonAsyncAJAX(url, false, true, true);
		
		zenario.registerPhrases(vlpClass, phrases);
	
		return phrases;
	};
	
	zenario.registerPhrases = function(vlpClass, phrases) {
		if (!zenario.phrases[vlpClass]) {
			zenario.phrases[vlpClass] = phrases;
		} else {
			$.extend(zenario.phrases[vlpClass], phrases);
		}
	};

	//Look up a Plugin's VLP Phrase
	zenario.phrase = function(vlpClass, text, mrg) {
	
		if (zenario.phrases[vlpClass] === undefined
		 || zenario.phrases[vlpClass][text] === undefined) {
			zenario.loadPhrases(vlpClass, text);
		}
		if (zenario.phrases[vlpClass][text] === null
		 || zenario.phrases[vlpClass][text] === undefined) {
			zenario.phrases[vlpClass][text] = text;
		}
	
		return zenario.applyMergeFields(zenario.phrases[vlpClass][text], mrg);
	};

	zenario.nphrase = function(vlpClass, text, pluralText, n, mrg) {
		if (pluralText !== undefined && (1*n) !== 1) {
			return zenario.phrase(vlpClass, pluralText, mrg);
		} else {
			return zenario.phrase(vlpClass, text, mrg);
		}
	};
	
//	zenario.linkToItem = function(cID, cType, request, adminlogin) {};

	//Call a signal/event on all included Modules, if they have it defined
	
//	zenario.sendSignal = function(signalName, data, dontUseCachedSignalHandlers) {};


	zenario.getMouseX = function(e) {
		if (e.pageX != undefined) {
			return e.pageX;
		} else {
			return e.clientX + document.body.scrollLeft + document.documentElement.scrollLeft;
		}
	};

	zenario.getMouseY = function(e) {
		if (e.pageY != undefined) {
			return e.pageY;
		} else {
			return e.clientY + document.body.scrollTop + document.documentElement.scrollTop;
		}
	};

	zenario.scrollTop = function(value, time, el) {
	
		if (el === undefined) {
			el = scrollBody;
		}
	
		if (value === undefined) {
			return $(el).scrollTop();
		} else if (!time) {
			return $(el).scrollTop(value);
		} else {
			$(el).animate({ scrollTop: value }, time);
		}
	};

	zenario.scrollLeft = function(value) {
		var $body = $(scrollBody);
	
		if (value === undefined) {
			return $body.scrollLeft();
		} else {
			return $body.scrollLeft(value);
		}
	};

	zenario.versionOfIE = function(n) {
		if (/opera|OPERA/.test(userAgent)) {
			return false;
		}
		var ver = /MSIE ([0-9]{1,}[\.0-9]{0,})/.exec(userAgent);
		return ver && ver[1] && 1*ver[1];
	};

	zenario.browserIsIE = function(n) {
		var ver = zenario.versionOfIE();
		
		return ver && (n? ver <= n: true);
	};

	zenario.browserIsChrome = function() {
		return /Chrome/.test(userAgent);
	};

	zenario.browserIsFirefox = function() {
		return /Firefox/.test(userAgent);
	};

	zenario.browserIsEdge = function() {
		return /Edge\//.test(userAgent);
	};

	zenario.browserIsRetina = function() {
		return window.devicePixelRatio > 1;
	};

	zenario.browserIsSafari = function() {
		return /Safari/.test(userAgent);
	};

	zenario.browserIsWebKit = function() {
		return /WebKit/.test(userAgent);
	};

	zenario.browserIsOpera = function() {
		return /Opera/.test(userAgent);
	};

	zenario.browserIsiPad = function() {
		return /iPad/.test(userAgent);
	};

	zenario.browserIsiPhone = function() {
		return /iPhone/.test(userAgent);
	};

	zenario.browserIsMobile = function() {
		return zenario.browserIsiPad() || zenario.browserIsiPhone();
	};

	zenario.ishttps = function() {
		return window.location
			&& window.location.protocol === 'https:';
	};

	zenario.httpOrhttps = function() {
		return zenario.ishttps()? 'https://' : 'http://';
	};
	
	
	
	
	zenario.AJAXLink = function(moduleClassName, requests) {
		return URLBasePath + 'zenario/ajax.php?moduleClassName=' + encodeURIComponent(moduleClassName) + '&method_call=handleAJAX' + zenario.urlRequest(requests);
	};
	
	zenario.pluginAJAXLink = function(moduleClassName, slotNameOrContainedElement, requests) {
		var slotName = zenario.getSlotnameFromEl(slotNameOrContainedElement),
			eggId = zenario.getEggIdFromEl(slotNameOrContainedElement),
			slot = zenario.slots[slotName],
			instanceId = slot && slot.instanceId,
			moduleClassName = moduleClassName || (slot && slot.moduleClassName);
		
		return URLBasePath + 
			'zenario/ajax.php?moduleClassName=' + encodeURIComponent(moduleClassName) + '&method_call=handlePluginAJAX' +
			'&cID=' + zenario.cID +
			'&cType=' + zenario.cType +
		  (zenario.adminId?
			'&cVersion=' + zenario.cVersion : '') +
			'&instanceId=' + instanceId +
			'&slotName=' + slotName +
		  (eggId?
			'&eggId=' + eggId : '') +
			zenario.urlRequest(requests);
	};
	
	zenario.showFileLink = function(moduleClassName, requests) {
		return URLBasePath + 
			'zenario/ajax.php?moduleClassName=' + encodeURIComponent(moduleClassName) + '&method_call=showFile' +
			zenario.urlRequest(requests);
	};
	
	zenario.showFloatingBoxLink = function(moduleClassName, slotNameOrContainedElement, requests) {
		var slotName = zenario.getSlotnameFromEl(slotNameOrContainedElement),
			instanceId = zenario.slots[slotName] && zenario.slots[slotName].instanceId;
		
		return URLBasePath + 
			'zenario/ajax.php?moduleClassName=' + encodeURIComponent(moduleClassName) + '&method_call=showFloatingBox' +
			'&cID=' + zenario.cID +
			'&cType=' + zenario.cType +
		  (zenario.adminId?
			'&cVersion=' + zenario.cVersion : '') +
			'&instanceId=' + instanceId +
			'&slotName=' + slotName +
			zenario.urlRequest(requests);
	};
	
	zenario.showSingleSlotLink = function(moduleClassName, slotNameOrContainedElement, requests, hideLayout, cID, cType) {
		var slotName = zenario.getSlotnameFromEl(slotNameOrContainedElement),
			instanceId = zenario.slots[slotName] && zenario.slots[slotName].instanceId;
		
		if (hideLayout === undefined) {
			hideLayout = true;
		}
		
		return zenario.linkToItem(cID || zenario.cID, cType || zenario.cType,
			'moduleClassName=' + encodeURIComponent(moduleClassName) + '&method_call=showSingleSlot' +
			(hideLayout? '&hideLayout=1' : '') +
		  (zenario.adminId?
			'&cVersion=' + zenario.cVersion : '') +
			'&instanceId=' + instanceId +
			'&slotName=' + slotName +
			zenario.urlRequest(requests));
	};
	
	zenario.showImageLink = function(moduleClassName, requests) {
		return URLBasePath + 
			'zenario/ajax.php?moduleClassName=' + encodeURIComponent(moduleClassName) + '&method_call=showImage' +
			zenario.urlRequest(requests);
	};
	
	zenario.showStandalonePageLink = function(moduleClassName, requests) {
		return URLBasePath + 
			'zenario/ajax.php?moduleClassName=' + encodeURIComponent(moduleClassName) + '&method_call=showStandalonePage' +
			zenario.urlRequest(requests);
	};
	
	
	zenario.visitorTUIXLink = function(moduleClassName, path, customisationName, requests, mode) {
		
		return URLBasePath +
			'zenario/ajax.php?moduleClassName=' + encodeURIComponent(moduleClassName) +
			'&path=' + encodeURIComponent(path) +
			'&_cn=' + encodeURIComponent(customisationName || '') +
			'&method_call=' + (mode == 'format' || mode == 'validate' || mode == 'save'? mode : 'fill') + 'VisitorTUIX' +
			zenario.urlRequest(requests);
	};
	
	zenario.pluginVisitorTUIXLink = function(moduleClassName, slotNameOrContainedElement, path, customisationName, requests, mode, useSync) {
		var slotName = zenario.getSlotnameFromEl(slotNameOrContainedElement),
			eggId = zenario.getEggIdFromEl(slotNameOrContainedElement),
			instanceId = zenario.slots[slotName] && zenario.slots[slotName].instanceId;
		
		return zenario.visitorTUIXLink(moduleClassName, path, customisationName, undefined, mode) +
			'&cID=' + zenario.cID +
			'&cType=' + zenario.cType +
		  (zenario.adminId?
			'&cVersion=' + zenario.cVersion : '') +
			'&instanceId=' + instanceId +
			'&slotName=' + slotName +
		  (eggId?
			'&eggId=' + eggId : '') +
			'&_useSync=' + zenario.engToBoolean(useSync) +
			zenario.urlRequest(requests);
	};
});
/*
 * Copyright (c) 2016, Tribal Limited
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


/**
  * This section lists all of the JavaScript functions and variables available to modules on the client side.
 */


zenario.moduleBaseClass = function(
	moduleId, moduleClassName, moduleClassNameForPhrases,
	zenario, undefined
) {
	"use strict";
	
	this.moduleId = moduleId;
	this.moduleClassName = moduleClassName;
	this.moduleClassNameForPhrases = moduleClassNameForPhrases;
	
	/*
		Variables
		(See environment_variables.inc.php for their descriptions)
	*/
	
	this.cID = zenario.cID;
	this.cType = zenario.cType;
	this.cVersion = zenario.cVersion;
	this.languageId = zenario.langId;
	
	
	/*
		Utility Functions
	*/
	
	//Launch an AJAX request to your Plugin's handleAJAX placeholder method
	//Warning: This uses zenario.nonAsyncAJAX() so is deprecated!
	this.AJAX = function(requests, post, useCache) {
		return zenario.moduleNonAsyncAJAX(this.moduleClassName, requests, post, false, useCache);
	};
	 
	//Launch a JSON request to your Plugin's handleAJAX placeholder method
	//Warning: This uses zenario.nonAsyncAJAX() so is deprecated!
	this.JSON = function(requests, post, useCache) {
		return zenario.moduleNonAsyncAJAX(this.moduleClassName, requests, post, true, useCache);
	};
	
	
	

	
	this.AJAXLink = function(requests) {
		return URLBasePath + 'zenario/ajax.php?moduleClassName=' + encodeURIComponent(this.moduleClassName) + '&method_call=handleAJAX' + zenario.urlRequest(requests);
	}
	
	this.pluginAJAXLink = function(slotNameOrContainedElement, requests) {
		var slotName = zenario.getSlotnameFromEl(slotNameOrContainedElement),
			instanceId = zenario.slots[slotName] && zenario.slots[slotName].instanceId;
		
		return URLBasePath + 
			'zenario/ajax.php?moduleClassName=' + encodeURIComponent(this.moduleClassName) + '&method_call=handlePluginAJAX' +
			'&cID=' + zenario.cID +
			'&cType=' + zenario.cType +
		  (zenario.adminId?
			'&cVersion=' + zenario.cVersion
		   : '') +
			'&instanceId=' + instanceId +
			'&slotName=' + slotName +
			zenario.urlRequest(requests);
	}
	
	this.showFileLink = function(requests) {
		return URLBasePath + 
			'zenario/ajax.php?moduleClassName=' + encodeURIComponent(this.moduleClassName) + '&method_call=showFile' +
			zenario.urlRequest(requests);
	}
	
	this.showFloatingBoxLink = function(slotNameOrContainedElement, requests) {
		var slotName = zenario.getSlotnameFromEl(slotNameOrContainedElement),
			instanceId = zenario.slots[slotName] && zenario.slots[slotName].instanceId;
		
		return URLBasePath + 
			'zenario/ajax.php?moduleClassName=' + encodeURIComponent(this.moduleClassName) + '&method_call=showFloatingBox' +
			'&cID=' + zenario.cID +
			'&cType=' + zenario.cType +
		  (zenario.adminId?
			'&cVersion=' + zenario.cVersion
		   : '') +
			'&instanceId=' + instanceId +
			'&slotName=' + slotName +
			zenario.urlRequest(requests);
	}
	
	this.showSingleSlotLink = function(slotNameOrContainedElement, requests, hideLayout) {
		var slotName = zenario.getSlotnameFromEl(slotNameOrContainedElement),
			instanceId = zenario.slots[slotName] && zenario.slots[slotName].instanceId;
		
		if (hideLayout === undefined) {
			hideLayout = true;
		}
		
		return zenario.linkToItem(zenario.cID, zenario.cType,
			'moduleClassName=' + encodeURIComponent(this.moduleClassName) + '&method_call=showSingleSlot' +
			(hideLayout? '&hideLayout=1' : '') +
		  (zenario.adminId?
			'&cVersion=' + zenario.cVersion
		   : '') +
			'&instanceId=' + instanceId +
			'&slotName=' + slotName +
			zenario.urlRequest(requests));
	}
	
	this.showImageLink = function(requests) {
		return URLBasePath + 
			'zenario/ajax.php?moduleClassName=' + encodeURIComponent(this.moduleClassName) + '&method_call=showImage' +
			zenario.urlRequest(requests);
	}
	
	this.showStandalonePageLink = function(requests) {
		return URLBasePath + 
			'zenario/ajax.php?moduleClassName=' + encodeURIComponent(this.moduleClassName) + '&method_call=showStandalonePage' +
			zenario.urlRequest(requests);
	}
	
	
	
	
	
	
	
	
	/**
	* The floatingBoxAnchor() method allows Plugin Developers to create a hyperlink which calls a
	* zenario-themed alert box to appear when an Admin clicks on it.
	* 
	* If a Plugin Developer wishes to have a confirmation box and not an alert box, then the $buttons
	* input can be passed the HTML needed for the buttons they wish to appear.
	* 
	* Note that this will only work in Admin mode.
	*/
	this.floatingMessage = function(html, buttonsHTML, warning) {
		if (zenarioA.init) {
			zenarioA.floatingBox(html, buttonsHTML, warning);
		}
	};
	
	//Returns the instance id of this Plugin.
	//Warning: If you have more than one instance on a page then it's not defined which one will be returned
	this.instanceId = function() {
		foreach (this.slots as i) {
			return this.slots[i].instanceId;
		}
		return false;
	};
	
	//Fetch your visitor phrases via AJAX.
	this.loadPhrases = function(code) {
		return zenario.loadPhrases(this.moduleClassNameForPhrases, code);
	};
	
	//Fetch one of your visitor phrases via AJAX.
	this.phrase = function(text, mrg) {
		return zenario.phrase(this.moduleClassNameForPhrases, text, mrg);
	};
	
	this.nphrase = function(text, pluralText, n, mrg) {
		return zenario.nphrase(this.moduleClassNameForPhrases, text, pluralText, n, mrg);
	};
	 
	//Go to a content item
	this.goToItem = function(cID, cType, request) {
		zenario.goToURL(zenario.linkToItem(cID, cType, request));
	};
	 
	//Go to a URL
	this.goToURL = function(url) {
		zenario.goToURL(url);
	};
	
	//Reload the contents of a slot.
	this.refreshPluginSlot = function(slotNameOrContainedElement, requests, scrollToTopOfSlot, fadeOutAndIn) {
		var slotName = zenario.getSlotnameFromEl(slotNameOrContainedElement);
		
		if (slotName === false) {
			return;
		}
		
		if (scrollToTopOfSlot === undefined) {
			scrollToTopOfSlot = true;
		}
		
		if (fadeOutAndIn === undefined) {
			fadeOutAndIn = true;
		}
		
		zenario.refreshPluginSlot(slotName, 'lookup', requests, true, scrollToTopOfSlot, fadeOutAndIn);
	};
	
	//Scroll to the top of a slot
	this.scrollToSlotTop = function(slotNameOrContainedElement, neverScrollDown) {
		var slotName = zenario.getSlotnameFromEl(slotNameOrContainedElement);
		zenario.scrollToSlotTop(slotName, neverScrollDown);
	};
	
	//Returns the slot name that this Plugin is in
	//Warning: If this Plugin is in more than one slot on a page then it's not defined which one be returned
	this.slotName = function() {
		foreach (this.slots as i) {
			return this.slots[i].slotName;
		}
		return false;
	};
	
	this.slots = new Object();
};

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

zenario.lib(function(
	undefined,
	URLBasePath,
	document, window, windowOpener, windowParent,
	zenario, zenarioA, zenarioAB, zenarioAT, zenarioO,
	get, engToBoolean, htmlspecialchars, ifNull, jsEscape, phrase,
	extensionOf, methodsOf, has
) {
	"use strict";

//
// Utility plugins for nests
//

//Ensure that the JavaScript libraries is there for modules on reloads
zenario_plugin_nest.addJavaScript = function(moduleClassName, moduleId) {
	if (!window[moduleClassName]) {
		zenario.addPluginJavaScript(moduleId);
		
		zenario_plugin_nest.needSleep = true;
	}
};

//If we're adding JavaScript, add a short delay to the tab switching to cover for the browser loading things in
zenario_plugin_nest.needSleep = false;
zenario_plugin_nest.sleep = function() {
	if (zenario_plugin_nest.needSleep) {
		zenario_plugin_nest.AJAX({sleep: true});
	}
	
	zenario_plugin_nest.needSleep = false;
};

zenario_conductor.cleanRequests = function(requests) {
	
	var key, value;
	
	if (!_.isEmpty(requests)) {
		foreach (requests as key => value) {
			if (value === ''
			 || value === null
			 || value === false
			 || value === undefined) {
				delete requests[key];
			}
		}
	}
};



//
// The conductor, internal functions
//

var slots = zenario_conductor.slots = {},
	
	getSlot = zenario_conductor.getSlot = function(slot, checkExists) {
		
		var slotName =
				slot && slot.slotName
			 || zenario.getSlotnameFromEl(slot);
	
		if (!slots[slotName]) {
			
			if (checkExists) {
				return false;
			}
			
			slots[slotName] = {
				slotName: slotName,
				key: {},
				commands: {}
			};
		}
	
		return slots[slotName];
	};


zenario_conductor.setCommands = function(slot, commands) {
	slot = getSlot(slot);
	
	slot.commands = commands || {};
};

zenario_conductor.calcRequests = function(slot, command, requests) {
	var key, value,
		toState = slot.commands[command];
	
	requests = zenario.toObject(requests, true);
	
	foreach (slot.key as key => value) {
		if (requests[key] === undefined) {
			requests[key] = value;
		}
	}
	requests.state = toState || '';
	requests.tab = '';
	
	return requests;
};


//
// The conductor, external/API functions
//
zenario_conductor.getRegisteredGetRequest = function(slot, key) {
	slot = getSlot(slot);
	
	if (key) {
		return slot.key[key];
	} else {
		return slot.key;
	}
};

zenario_conductor.clearRegisteredGetRequest = function(slot, key) {
	slot = getSlot(slot);
	
	var key, value;
	
	if (_.isString(key)) {
		delete slot.key[key];
	
	} else if (!_.isEmpty(key)) {
		foreach (key as key) {
			delete slot.key[key];
		}
	}
};

zenario_conductor.registerGetRequest = function(slot, requests, value) {
	slot = getSlot(slot);
	
	if (_.isString(requests)) {
		requests = {requests: value};
	}
	
	if (!_.isEmpty(requests)) {
		foreach (requests as key => value) {
			slot.key[key] = value;
		}
	}
	
	zenario_conductor.cleanRequests(slot.key);
};


zenario_conductor.go = function(slot, command, requests, clearRegisteredGetRequests/*, recordInURL, scrollToTopOfSlot, fadeOutAndIn, useCache, post*/) {
	slot = getSlot(slot);
	
	if (clearRegisteredGetRequests) {
		zenario_conductor.clearRegisteredGetRequest(slot, clearRegisteredGetRequests);
	}
	
	requests = zenario_conductor.calcRequests(slot, command, requests);
	zenario_conductor.cleanRequests(requests);

	//zenario.refreshPluginSlot(slotName, instanceId, additionalRequests, recordInURL, scrollToTopOfSlot, fadeOutAndIn, useCache, post)
	zenario.refreshPluginSlot(slot.slotName, 'lookup', requests, true);
};

zenario_conductor.enabled = function(slot) {
	return !!getSlot(slot, true);
};

zenario_conductor.commandEnabled = function(slot, command) {
	slot = getSlot(slot);
	return !!slot.commands[command];
};

zenario_conductor.link = function(slot, command, requests) {
	slot = getSlot(slot);
	
	if (slot.commands[command]) {
		requests = zenario_conductor.calcRequests(slot, command, requests);
		zenario_conductor.cleanRequests(requests);
		return zenario.linkToItem(zenario.cID, zenario.cType, requests);
	} else {
		return false;
	}
};


}, zenario_plugin_nest, zenario_conductor);
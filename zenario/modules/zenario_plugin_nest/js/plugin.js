
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
	zenario, zenarioA, zenarioT, zenarioAB, zenarioAT, zenarioO,
	encodeURIComponent, get, engToBoolean, htmlspecialchars, jsEscape, phrase,
	extensionOf, methodsOf, has,
	zenario_plugin_nest, zenario_conductor, s$s
) {
	"use strict";

//
// Utility plugins for nests
//

//Ensure that the JavaScript libraries is there for modules on reloads
zenario_plugin_nest.addJavaScript = function(moduleClassName, moduleId) {
	if (!window[moduleClassName] || _.isEmpty(window[moduleClassName].slots)) {
		zenario.addPluginJavaScript(moduleId, true);
	}
};

zenario_conductor.cleanRequests = function(requests) {
	
	var key, value;
	
	if (!_.isEmpty(requests)) {
		foreach (requests as key => value) {
			if (value === ''
			 || value === 0
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
	
	getSlot = zenario_conductor.getSlot = function(slot) {
		
		var slotName =
				slot && slot.slotName
			 || zenario.getSlotnameFromEl(slot);
	
		if (!slots[slotName]) {
			
			slots[slotName] = {
				slotName: slotName,
				key: {},
				commands: {},
				exists: false
			};
		}
	
		return slots[slotName];
	};


zenario_conductor.setCommands = function(slot, commands, coreVars) {
	slot = getSlot(slot);
	
	slot.key = {};
	slot.exists = true;
	slot.commands = commands || {};
	slot.coreVars = coreVars || {};
	slot.checkChangedOnClose =
	slot.confirmOnClose =
	slot.confirmOnCloseMessage = false;
};

zenario_conductor.linkToOtherContentItem = function(slot, commandDetails, requests) {
	var key, value,
		toState = commandDetails[0],
		cID = commandDetails[2],
		cType = commandDetails[3];
	
	//Make sure that the requests are an object
	requests = zenario.toObject(requests, true);
	
	//Set the state or slide that we're linking to
	delete requests.state;
	delete requests.slideId;
	delete requests.slideNum;
	
	if (toState == 1*toState) {
		requests.slideNum = toState;
	} else {
		requests.state = toState;
	}
	
	return zenario.linkToItem(cID, cType, requests);
};

zenario_conductor.calcRequests = function(slot, commandDetails, requests) {
	var key, value,
		toState = commandDetails[0],
		reqVars = commandDetails[1];
	
	//Make sure that the requests are an object
	requests = zenario.toObject(requests, true);
	
	//If we're generating a link to the current state, keep all of the registered get requests
	if (toState == slot.key.state) {
		foreach (slot.key as key => value) {
			if (requests[key] === undefined) {
				requests[key] = value;
			}
		}
	}
	
	//Loop through each of the variables needed by the destination
	foreach (reqVars as key) {
		//Check the settings on the destination to see if it needs that variable.
		//If so then try to add it from the core variables.
		if (!requests[key] && slot.coreVars[key]) {
			requests[key] = slot.coreVars[key];
		}
	}
	
	requests.state = toState || '';
	requests.tab = '';
	
	return requests;
};

zenario_conductor.confirmOnCloseMessage = function(slot, command, requests) {
	var s, slot;
	
	foreach (slots as s => slot) {
		if (slot.exists
		 && _.isFunction(slot.checkChangedOnClose)
		 && slot.checkChangedOnClose()) {
			return slot.confirmOnCloseMessage;
		}
	}
	
	return undefined;
};






zenario_conductor.transitionIn = function(slot, transition_in) {
	slot = getSlot(slot);
	
	var $slot = $('#plgslt_' + slot.slotName),
		$eggs = $slot.find('.nest_plugins_wrap')
			.css('position', 'relative'),
		options = transition_in.options || {};
	
	if (_.isString(options.duration)) {
		options.duration *= 1;
	}
	if (_.isArray(options.easing)) {
		options.easing = $.bez(options.easing);
	}
	
	if (transition_in.initial) {
		$eggs.css(transition_in.initial);
	}
	
	if (transition_in.animate) {
		$eggs.animate(transition_in.animate, options);
	}
};

//Play a transition out, when the current view is removed and replaced with something else
zenario_conductor.transitionOut = function(slot, transition_out) {
	slot = getSlot(slot);
	
	if (!transition_out
	 || !transition_out.animate) {
		return;
	}
	
	var $slot = $('#plgslt_' + slot.slotName),
		$cloneS = $slot.cloneProperly()
			.attr('id', '')
			.css('position', 'absolute')
			.css('z-index', 2)
			.addClass('zenario_conductor_slot_dummy_for_transition')
			.width($slot.width())
			.height($slot.height())
			.insertBefore($slot),
		$eggs = $slot.find('.nest_plugins_wrap'),
		$cloneZ = $cloneS.find('.nest_plugins_wrap')
			.css('position', 'relative')
			.addClass('zenario_conductor_slide_dummy_for_transition'),
		
		options = transition_out.options || {},
		animate = transition_out.animate,
		slotOriginalHeightPixels = $slot.height(),
		slotOriginalHeightCSS = $slot.css('min-height');
	
	if (_.isString(options.duration)) {
		options.duration *= 1;
	}
	if (_.isArray(options.easing)) {
		options.easing = $.bez(options.easing);
	}
	
	//Remove certain CSS rules from the previous transition-in that may mess up the transition-out
	if (animate.left !== undefined) {
		$cloneZ.css('right', '');
	}
	if (animate.right !== undefined) {
		$cloneZ.css('left', '');
	}
	if (animate.top !== undefined) {
		$cloneZ.css('bottom', '');
	}
	if (animate.bottom !== undefined) {
		$cloneZ.css('top', '');
	}
	
	options.complete = function() {
		$cloneS.remove();
		$slot.css('min-height', slotOriginalHeightCSS);
	};
	$slot.css('min-height', slotOriginalHeightPixels + 'px');
	
	$cloneZ.animate(animate, options);
	$eggs.hide();
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

//Set an "are you sure" message when closing
	//checkChangedOnClose: a function that should return true or false
	//confirmOnClose: a function that should show a confirm box, then call it's input if the user presses "okay"
	//confirmOnCloseMessage: a message as a fallback if confirmOnClose cannot be used
zenario_conductor.confirmOnClose = function(slot, checkChangedOnClose, confirmOnClose, confirmOnCloseMessage) {
	slot = getSlot(slot);
	
	if (slot.exists) {
		slot.checkChangedOnClose = checkChangedOnClose;
		slot.confirmOnClose = confirmOnClose;
		slot.confirmOnCloseMessage = confirmOnCloseMessage;
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

zenario_conductor.registerGetRequest = function(slot, currentRequests) {
	slot = getSlot(slot);
	
	slot.key = currentRequests;
	
	zenario_conductor.cleanRequests(slot.key);
};


zenario_conductor.go = function(slot, command, requests, clearRegisteredGetRequests/*, recordInURL, scrollToTopOfSlot, fadeOutAndIn, useCache, post*/) {
	slot = getSlot(slot);
	
	if (slot.exists) {
		if (clearRegisteredGetRequests) {
			zenario_conductor.clearRegisteredGetRequest(slot, clearRegisteredGetRequests);
		}
		
		var commandDetails = slot.commands[command];
		
		//Handle links to other content items
		if (commandDetails[2]) {
			if (command == 'submit') {
				slot.checkChangedOnClose = false;
			}
			zenario.goToURL(zenario_conductor.linkToOtherContentItem(slot, commandDetails, requests));
		
		//Handle links to other slides
		} else {
			requests = zenario_conductor.calcRequests(slot, commandDetails, requests);
			zenario_conductor.cleanRequests(requests);
	
			if (command == 'back') {
				zenario_conductor.transitionOut(slot, {
					animate: {
						opacity: 0
					},
					options: {
						duration: 400
					}
				});
		
			} else {
				zenario_conductor.transitionOut(slot, {
					animate: {
						opacity: 0,
						right: '150%'
					},
					options: {
						duration: 400
					}
				});
			}
		
			//zenario.refreshPluginSlot(slotName, instanceId, additionalRequests, recordInURL, scrollToTopOfSlot, fadeOutAndIn, useCache, post)
			zenario.refreshPluginSlot(slot.slotName, 'lookup', requests, true).after(function() {
				if (command == 'back') {
					zenario_conductor.transitionIn(slot, {
						initial: {
							opacity: 0
						},
						animate: {
							opacity: 1,
							right: 0
						},
						options: {
							duration: 1000
						}
					});
				} else {
					zenario_conductor.transitionIn(slot, {
						initial: {
							opacity: 0,
							left: '150%'
						},
						animate: {
							opacity: 1,
							left: 0
						},
						options: {
							duration: 800,
							easing: [.3, .7, 0, 1.05]
						}
					});
				}
			});
		}
	}
};

zenario_conductor.autoRefreshTimer = false;
zenario_conductor.autoRefresh = function(slotName, interval) {
	$('div.slot.' + slotName).addClass('auto_refreshing');
	if (this.autoRefreshTimer === false) {
		zenario_conductor.refresh(slotName);
		this.autoRefreshTimer = setInterval(function() {
			zenario_conductor.refresh(slotName);
		}, interval * 1000);
	}
};

zenario_conductor.stopAutoRefresh = function(slotName) {
	clearInterval(this.autoRefreshTimer);
	this.autoRefreshTimer = false;
	$('div.slot.' + slotName).removeClass('auto_refreshing');
};

zenario_conductor.refresh = function(slot) {
	if (slot = getSlot(slot)) {
		var requests = _.extend({no_cache: 1}, slot.key, slot.coreVars);
		zenario_conductor.cleanRequests(requests);
		
		zenario.refreshPluginSlot(slot.slotName, 'lookup', requests, undefined, false);
		return true;
	}
};

zenario_conductor.enabled = function(slot) {
	slot = getSlot(slot);
	return slot.exists;
};

zenario_conductor.commandEnabled = function(slot, commands) {
	slot = getSlot(slot);
	
	if (slot.exists) {
		var ci, command,
			commands = zenarioT.tuixToArray(commands);
	
		foreach (commands as ci => command) {
			if (slot.commands[command]) {
				return command;
			}
		}
	}
	
	return false;
};

zenario_conductor.link = function(slot, command, requests) {
	slot = getSlot(slot);
	
	var commandDetails;
	
	if (slot.exists && (commandDetails = slot.commands[command])) {
		
		//Handle links to other content items
		if (commandDetails[2]) {
			return zenario_conductor.linkToOtherContentItem(slot, commandDetails, requests);
		
		//Handle links to other slides
		} else {
			requests = zenario_conductor.calcRequests(slot, commandDetails, requests);
			zenario_conductor.cleanRequests(requests);
			return zenario.linkToItem(zenario.cID, zenario.cType, requests);
		}
	} else {
		return false;
	}
};

zenario_conductor.backLink = function(slot) {
	slot = getSlot(slot);
	return slot.exists && zenario_conductor.link(slot, 'back');
};

zenario_conductor.goBack = function(slot, confirmed) {
	slot = getSlot(slot);
	if (slot.exists) {
		
		if (!confirmed
		 && _.isFunction(slot.checkChangedOnClose)
		 && _.isFunction(slot.confirmOnClose)
		 && slot.checkChangedOnClose()) {
			slot.confirmOnClose(function() {
				zenario_conductor.goBack(slot, true);
			});
		} else {
			zenario_conductor.go(slot, 'back', {}, true);
		}
	}
};


}, zenario_plugin_nest, zenario_conductor);
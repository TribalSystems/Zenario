
/*
 * Copyright (c) 2018, Tribal Limited
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
	encodeURIComponent, defined, engToBoolean, get, htmlspecialchars, jsEscape, phrase,
	extensionOf, methodsOf, has,
	zenario_plugin_nest, zenario_conductor
) {
	"use strict";

//
// Show a pop-out box with embed options
//


zenario_plugin_nest.embed = function(mergefields) {

	$.colorbox({
		transition: 'none',
		//closeButton: false,
		html: zenario.microTemplate('embed_slide', mergefields),
		className: 'embed_slide_popout'
	});
};




//
// Utility plugins for nests
//

//Ensure that the JavaScript libraries is there for modules on reloads
zenario_plugin_nest.addJavaScript = function(moduleClassName, moduleId) {
	if (!window[moduleClassName] || _.isEmpty(window[moduleClassName].slots)) {
		zenario.addPluginJavaScript(moduleId, true);
	}
};



//
// The conductor, internal functions
//

var slots = zenario_conductor.slots = {},
	
	isString = function(string) {
		return 'string' == typeof string;
	},
	
	getSlot = zenario_conductor.getSlot = function(slot) {
		
		if (!defined(slot)) {
			var si, s;
			foreach (slots as si => s) {
				return s;
			}
			return {exists: false};
		}
		
		var slotName =
				slot && slot.slotName
			 || zenario.getSlotnameFromEl(slot);
	
		if (!slots[slotName]) {
			
			slots[slotName] = {
				slotName: slotName,
				commands: {},
				exists: false,
				vars: {}
			};
		}
	
		return slots[slotName];
	},
	
	getCommand = zenario_conductor.getCommand = function(slot, command) {
		if (isString(slot)) {
			slot = getSlot(slot);
		}
		
		return isString(command)? slot.commands[command] : command;
	};



zenario_conductor.setCommands = function(slot, commands, state, vars) {
	slot = getSlot(slot);
	
	slot.exists = true;
	slot.state = state;
	slot.commands = commands || {};
	slot.checkChangedOnClose =
	slot.confirmOnClose =
	slot.confirmOnCloseMessage = false;
	
	zenario_conductor.setVars(slot, vars);
};



zenario_conductor.setVars = function(slot, vars) {
	slot = getSlot(slot);
	
	vars = vars || {};
	
	zenario_conductor.cleanRequests(vars || {});
	
	slot.vars[slot.state] = vars;
};



zenario_conductor.confirmOnCloseMessage = function() {
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
	if (defined(animate.left)) {
		$cloneZ.css('right', '');
	}
	if (defined(animate.right)) {
		$cloneZ.css('left', '');
	}
	if (defined(animate.top)) {
		$cloneZ.css('bottom', '');
	}
	if (defined(animate.bottom)) {
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


zenario_conductor.go = function(slot, command, requests) {
	slot = getSlot(slot);
	
	if (slot.exists) {
		
		if (this.autoRefreshTimer) {
			zenario_conductor.stopAutoRefresh(slot.slotName);
		}
		
		var commandDetails = slot.commands[command],
			containerId = 'plgslt_' + slot.slotName,
			di, ds;
		
		//Remove any code editors from the page, as a work-around to prevent
		//a bug where the editors sometimes fail to display correctly when displayed a second
		//time due to the first not being unloaded correctly
		$('#' + containerId + ' .zenario_embedded_ace_editor').remove();
		
		//Handle links to other content items
		if (commandDetails.cID) {
			if (command == 'submit') {
				slot.checkChangedOnClose = false;
			}
			zenario.goToURL(zenario_conductor.linkToOtherContentItem(slot, commandDetails, requests));
		
		//Handle links to other slides
		} else {
	
			if (command == 'back') {
				
				//If this is a back-link, wipe clear all variables from previous states
				foreach (commandDetails.descendants as di => ds) {
					delete slot.vars[ds];
				}
				delete slot.vars[slot.state];
				
				//Show a fade-out and back in tranisition for pressing the back link
				zenario_conductor.transitionOut(slot, {
					animate: {
						opacity: 0
					},
					options: {
						duration: 400
					}
				});
		
			} else if (command == 'refresh') {
				//Don't run any animations for pressing the refresh button, just use the usual animation in zenario.refreshPluginSlot()
				
				//(Also, for some reason I can't work out yet, putting a fade-out animation here causes a problem where the
				// new content does not reappear in!)
				
			} else {
				//Show a fade-out, right scroll, and back in tranisition for any other type of command
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
			zenario.refreshPluginSlot(slot.slotName, 'lookup', zenario_conductor.request(slot, commandDetails, requests), true).after(function() {
				if (command == 'back') {
					//Show a fade-out and back in tranisition for pressing the back link
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
				
				} else if (command == 'refresh') {
					//Don't run any animations for pressing the refresh button, just use the usual animation in zenario.refreshPluginSlot()
				
				} else {
					//Show a fade-out, right scroll, and back in tranisition for any other type of command
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
		
		return true;
	}
};

zenario_conductor.autoRefreshTimer = false;
zenario_conductor.autoRefresh = function(slotName, interval) {
	$('div.slot.' + slotName).addClass('auto_refreshing');
	if (this.autoRefreshTimer === false) {
		zenario.refreshSlot(slotName);
		this.autoRefreshTimer = setInterval(function() {
			zenario.refreshSlot(slotName);
		}, interval * 1000);
	}
};

zenario_conductor.stopAutoRefresh = function(slotName) {
	clearInterval(this.autoRefreshTimer);
	this.autoRefreshTimer = false;
	$('div.slot.' + slotName).removeClass('auto_refreshing');
};

zenario_conductor.refresh = function(slot) {
	return zenario_conductor.go(slot, 'refresh');
};

zenario_conductor.reloadAfterDelay = function(delay, slot) {
	zenario.actAfterDelayIfNotSuperseded(zenario_conductor, function() {
		zenario_conductor.refresh(slot);
	}, delay);
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

zenario_conductor.linkToOtherContentItem = function(slot, commandDetails, requests) {
	var key, value,
		toState = commandDetails.toState,
		cID = commandDetails.cID,
		cType = commandDetails.cType;
	
	//Make sure that the requests are an object
	requests = zenario.toObject(requests, true);
	
	zenario_conductor.cleanRequests(requests);
	
	//Set the state or slide that we're linking to
	if (toState == 1*toState) {
		requests.slideNum = toState;
	} else {
		requests.state = toState;
	}
	
	return zenario.linkToItem(cID, cType, requests);
};

zenario_conductor.request = function(slot, commandDetails, newRequests) {
	slot = getSlot(slot);
	
	commandDetails = getCommand(slot, commandDetails);
	
	//The rules for requests should be as follows:
		//If we're reloading the current state, allow any request variables.
		//If we're going to a different state, only variables that are registered by one of the plugins on that state are allowed.
		//If we're going to a different state, that we've been to before, restore all of the variables that it used.
	
	//Make sure that the requests are an object
	newRequests = zenario.toObject(newRequests, true);
	
	var i,
		reqVar,
		fromState = slot.state,
		toState = commandDetails.toState,
		requests = slot.vars[toState],
		defaultRequests = commandDetails.dRequests,
		bVar = commandDetails.bVar,
		hVar = commandDetails.hVar;
	
	//If we've been to this state before, try to look up any previous requests that were there.
	//Then add the requests that were calculated in zenario_plugin_nest/module_code.php.
	requests = _.extend({}, requests || {}, defaultRequests);
	
	//If this is a link to the same slide & state, allow any variables to be added.
	if (toState == fromState) {
		_.extend(requests, newRequests);
	
	//Otherwise only allow variables that were registered by a plugin there.
	} else {
		
		//Ignore any requests if this is a back link
		if (!_.isEmpty(newRequests)
		 && commandDetails.command != 'back') {
			
			//Look through the requests this slide takes, and override the defaults
			//with any specific values set here.
			foreach (defaultRequests as reqVar) {
				if (defined(newRequests[reqVar])) {
					requests[reqVar] = newRequests[reqVar];
				}
			}
		
			//Catch the case where a basic variable (e.g. dataPoolId) is in the request,
			//but we need a hierarchical variable (e.g. dataPoolId3).
			if (bVar !== ''
			 && !(requests[bVar])
			 && defined(newRequests[hVar])) {
				requests[bVar] = newRequests[hVar];
			}
		}
	}
	
	//If we're using hierarchical variables, try to only include the specific variable
	//for this level, and remove variables for all other levels
	if (hVar
	 && bVar
	 && requests[bVar]) {
		
		delete requests[hVar];
		
		for (i = 1; i < 9; ++i) {
			reqVar = hVar + i;
			if (reqVar != bVar) {
				delete requests[reqVar];
			}
		}
	}
	
	zenario_conductor.cleanRequests(requests);
	
	requests.state = toState;
	
	return requests;
};


zenario_conductor.cleanRequests = function(requests) {
	
	var key, value;
	
	//Automatically unset any empty requests
	if (!_.isEmpty(requests)) {
		foreach (requests as key => value) {
			if (value === ''
			 || value === 0
			 || value === false
			 || !defined(value)) {
				delete requests[key];
			}
		}
	}
	
	//Clear any standard content item variables
	delete requests.cID;
	delete requests.cType;
	delete requests.cVersion;
	delete requests.visLang;
	
	//Clear any standard plugin variables, unless this is a link to the showSingleSlot() method
	if (requests.method_call != 'showSingleSlot') {
		delete requests.method_call;
		delete requests.slotName;
		delete requests.instanceId;
	}
	
	//Clear any requests that point to this nest/slide/state
	delete requests.state;
	delete requests.slideId;
	delete requests.slideNum;
	
	//Clear some FEA variables
	delete requests.mode;
	delete requests.path;
};




	//To do - rewrite this!
	zenario_conductor.mergeRequests = function(slot, requests) {
		slot = getSlot(slot);
	
		//if (slot.exists) {
		//	foreach (slot.key as key => value) {
		//		if (key !== 'state'
		//		 && !defined(requests[key])) {
		//			requests[key] = value;
		//		}
		//	}
		//}
	};




zenario_conductor.link = function(slot, command, requests) {
	slot = getSlot(slot);
	
	var commandDetails;
	
	if (slot.exists && (commandDetails = slot.commands[command])) {
		
		//Handle links to other content items
		if (commandDetails.cID) {
			return zenario_conductor.linkToOtherContentItem(slot, commandDetails, requests);
		
		//Handle links to other slides
		} else {
			return zenario.linkToItem(zenario.cID, zenario.cType, zenario_conductor.request(slot, commandDetails, requests));
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


zenario.shrtNms(zenario_conductor, true);



}, zenario_plugin_nest, zenario_conductor);
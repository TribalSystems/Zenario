
/*
 * Copyright (c) 2015, Tribal Limited
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
	extensionOf, methodsOf
) {
	"use strict";


zenario.slots = new Object();
zenario.modules = new Object();
zenario.instances = new Object();
zenario.mainSlot = false;

zenario.signalsInProgress = {};
zenario.adinsActions = {};


zenario.getEl = false;

//Note that page caching may cause the wrong user id to be set.
//As with session('extranetUserID'), anything that changes behaviour by Extranet User should not allow the page to be cached.
zenario.userId = 0;
zenario.adminId = 0;





//WiP callback class	
zenario.callback = function() {
	this.isOwnCallback = false;
	this.isWrapper = false;
	this.results = [undefined];
	this.completes = [false];
	this.funs = [];
};
var methods = methodsOf(zenario.callback);

//Register a function to call afterwards.
//Your function will be called with the result of the callback as its arguement
//(Or the results of the callbacks as its arguements, if you have chained multiple callbacks together)
methods.after = function(fun, that) {
	this.funs.push([fun, that || this]);
	return this;
};

//Complete the callback with a result
//The result you give will be added as an arguement to the callback function
methods.call = function(result) {
	this.isOwnCallback = true;
	this.completes[0] = true;
	this.results[0] = result;
	this.checkComplete();
	
	return this;
};

//Turn this callback into a wrapper for other callbacks
//Your callback function will be called after all of the callback functions you've added are called,
//and you'll get multiple arguements passed to your callback function (one per callback).
methods.add = function(cb) {
	this.isWrapper = true;
	this.completes[0] = true;
	
	var i = this.results.length;
	
	this.results.push(undefined);
	this.completes.push(false);
	
	cb.after(function(result) {
		this.results[i] = result;
		this.completes[i] = true;
		this.checkComplete();
	}, this);
	
	return this;
};

//Check to see if the callback is complete and trigger the callback function if so.
//An internal function, no need to call it.
methods.checkComplete = function() {
	var i, link, fun;
	
	if (!this.isOwnCallback && !this.isWrapper) {
		return;
	}
	
	for (i = 0; i < this.completes.length; ++i) {
		if (!this.completes[i]) {
			return;
		}
	}
	
	if (this.funs.length) {
		//If this was just used as a wrapper, don't include an empty first parameter
		//But if this was used as a wrapper *and* a callback, we need to keep the first parameter
		if (!this.isOwnCallback && this.isWrapper) {
			this.results.splice(0, 1);
		}
		foreach (this.funs as i => fun) {
			fun[0].apply(fun[1], this.results);
		}
	}
};

//Some different examples of how to use the callback function above
//window.test = function() {
//	var url1 = URLBasePath + 'zenario/admin/ajax.php?_json=1&_start=0&_get_item_name=1&path=zenario__content%2Fpanels%2Flanguage_equivs&_item=html_1&_limit=1',
//		url2 = URLBasePath + 'zenario/admin/ajax.php?_json=1&_start=0&_get_item_name=1&path=zenario__content%2Fpanels%2Flanguage_equivs&_item=html_2&_limit=1',
//		url3 = URLBasePath + 'zenario/admin/ajax.php?_json=1&_start=0&_get_item_name=1&path=zenario__content%2Fpanels%2Flanguage_equivs&_item=html_3&_limit=1';
//	
//	zenario.ajax(url1, false, true).after(function(data) {
//		console.log(1, data.items.html_1.tag);
//	});
//	zenario.ajax(url2, false, true).after(function(data) {
//		console.log(2, data.items.html_2.tag);
//	});
//	zenario.ajax(url3, false, true).after(function(data) {
//		console.log(3, data.items.html_3.tag);
//	});
//	
//	var cb = new zenario.callback;
//	cb.add(zenario.ajax(url1, false, true));
//	cb.add(zenario.ajax(url2, false, true));
//	cb.add(zenario.ajax(url3, false, true));
//	cb.after(function(data1, data2, data3) {
//		console.log(4, data1.items.html_1.tag, data2.items.html_2.tag, data3.items.html_3.tag);
//	});
//
//	
//	zenario.ajax(url1, false, true)
//	.add(zenario.ajax(url2, false, true))
//	.add(zenario.ajax(url3, false, true))
//	.after(function(data1, data2, data3) {
//		console.log(5, data1.items.html_1.tag, data2.items.html_2.tag, data3.items.html_3.tag);
//	})
//	.after(function(data1, data2, data3) {
//		console.log(6, data1.items.html_1.tag, data2.items.html_2.tag, data3.items.html_3.tag);
//	});
//};



zenario.loadedLibraries = {};
zenario.loadLibrary = function(path, callback) {
	
	var library;
	
	if (library = zenario.loadedLibraries[path]) {
		if (library.loaded) {
			callback();
		} else {
			library.cb.after(callback);
		}
	
	} else {
		library = zenario.loadedLibraries[path] = {cb: new zenario.callback, loaded: false};
	
		library.cb.after(callback);
	
		$.getScript(path, function() {
			library.loaded = true;
			library.cb.call();
		});
	}
};



//Redirect the user to a URL using JavaScript
zenario.goToURL = function(URL, useChromeFix) {
	document.location.href = URL;
	
	if (useChromeFix) {
		//Hack to fix a bug with Chrome :(
		setTimeout(
			function() {
				document.location.href = URL;
			}, 500);
	}
	
	return false;
};

//A version of nonAsyncAJAX for modules
//As this uses zenario.nonAsyncAJAX() we should start to avoid using this from now on...
zenario.moduleNonAsyncAJAX =

//Some old deprecated names
zenario.handlePluginAJAX =
zenario.pluginClassAJAX = function(moduleClassName, requests, post, json, useCache) {
	return zenario.nonAsyncAJAX(URLBasePath + 'zenario/ajax.php?moduleClassName=' + moduleClassName + '&method_call=handleAJAX' + zenario.urlRequest(requests), post, json, useCache);
};

//Listen out for changes to the hash on the URL, to add support for browser back buttons and AJAX reloading
zenario.currentHash = '';
zenario.currentHashSlot = false;
zenario.watchingForHashChanges = false;
zenario.checkForHashChanges = function(timed) {
	if (zenario.watchingForHashChanges) {
		clearTimeout(zenario.watchingForHashChanges);
	}
	
	//Don't do anything if the hash has not changed since the last time, or if an Admin Box is currently open
	if (!zenarioAB.isOpen && (!timed || zenario.currentHash !== document.location.hash)) {
		//Extract the instance id and the request from the hash
		var hash = document.location.hash.substr(1);
		
		//If this is an Organizer window, go to that location
		if (zenarioA.isFullOrganizerWindow && zenarioO.init) {
			
			if (hash.substr(0, 1) == '/') {
				hash = hash.substr(1);
			}
			
			//Check if there is an editor open
			var message = zenarioA.onbeforeunload();
			//If there was, give the Admin a chance to stop leaving the page
			if (message === undefined || confirm(message)) {
				zenarioAB.close();
				zenarioO.go(hash, -1);
			} else {
				//If the admin doesn't want to leave, put the hash back to how it was
				zenarioO.saveSelection();
			}
		
		//If this is the dev tools, go to that location
		} else if (window.devTools && window.devTools.init) {
			devTools.editorSelectFullPath(hash, true);
		
		//Give an Admin the ability to cancel navigation if they were editing something.
		} else
		if (zenarioA.init
		 && zenarioA.checkSlotsBeingEdited()
		 && !confirm(zenarioA.phrase.leavePageWarning)
		) {
			document.location.hash = zenario.currentHash;
		
		//Otherwise is this is the front-end, see if we can find which Plugin it mentions and then change that Plugin to use that request
		} else {
			if (zenarioAT.init) {
				if (hash.split('__zenario_reload_at__=')[1]) {
					zenarioAT.init();
				}
			}
			
			var pos;
			if ((pos = hash.lastIndexOf('!')) !== -1) {
				var key = hash.substr(0, pos),
					req = hash.substr(pos+1);
				
				//Use an empty string as a shortcut to the first Main Slot
				key = ifNull(key, zenario.mainSlot);
				
				//Check if this is a request to reload a Plugin via (numeric) instance id(s)
				if (key == key.replace(/[^0-9,]/g, '')) {
					var keys = key.split(',');
					foreach (keys as var k) {
						key = 1 * keys[k];
						if (zenario.instances[key]) {
							zenario.currentHashSlot = zenario.instances[key].slotName;
							zenario.refreshPluginSlot(zenario.currentHashSlot, key, req);
						}
					}
				
				//Otherwise if this is a slot, reload that slot
				} else if (zenario.slots[key]) {
					zenario.currentHashSlot = key;
					zenario.refreshPluginSlot(key, '', req);
				}
			
			} else {
				//If there was an empty hash or no hash, and we just changed a Plugin, reset it.
				zenario.refreshPluginSlot(zenario.currentHashSlot, '', '', undefined, false, false);
				//zenario.refreshPluginSlot = function(slotName, instanceId, additionalRequests, recordInURL, scrollToTopOfSlot, fadeOutAndIn, useCache) {
			}
		}
	}
	
	//Remember the hash that we've just used
	zenario.currentHash = document.location.hash;
	
	//Keep watching for hash changes
	zenario.watchingForHashChanges =
		setTimeout(
			function() {
				zenario.checkForHashChanges(true);
			}, 500);
};

//Watch for the recorded URLs on page load
$(zenario.checkForHashChanges);


//New, HTML 5 friendly alternative to changing hashes that uses history popstates instead.
//(Note that even for browsers that do support HTML 5, the old functionality is included as well
//in order to support the older format of links with hashes in.)
if (window.history && history.pushState) {
	window.addEventListener('popstate', function(event) {
		if (event.state && event.state.slotName) {
			//Check if there is an editor open
			var message = zenarioA.onbeforeunload();
			//If there was, give the Admin a chance to stop leaving the page
			if (message === undefined || confirm(message)) {
				zenario.refreshPluginSlot(event.state.slotName, 'lookup', event.state.request);
				
				//Refresh the admin toolbar to remove the Editor controls if needed
				if (message !== undefined && zenarioAT.init) {
					zenarioAT.init();
				}
			} else {
				return false;
			}
		}
	});
};



zenario.pluginAJAXURL = function(slotName, additionalRequests, instanceId) {

	if (typeof slotName != 'string') {
		slotName = zenario.getSlotnameFromEl(slotName);
	}

	//Allow a slot to be refreshed by name only, in which case we'll check its current instance id
	if (instanceId == 'lookup') {
		instanceId = zenario.slots[slotName].instanceId;
	}

	if (!instanceId) {
		instanceId = '';
	}

	return URLBasePath + 'zenario/ajax.php?method_call=refreshPlugin'
		+ '&cID=' + zenario.cID + '&cType=' + zenario.cType + (zenario.cVersion? '&cVersion=' + zenario.cVersion : '')
		+ '&instanceId=' + instanceId
		+ '&slotName=' + slotName
		+ zenario.urlRequest(additionalRequests); 
};

zenario.submitFormReturningHtml = function(el, successCallBack) {
	$.ajax({
		type: 'POST',
		dataType: 'text',
		url: zenario.pluginAJAXURL(el, undefined, 'lookup'),
		data: $(el).serialize(),
		success: successCallBack
	});
};


//Attempt to submit a form via AJAX using jQuery serialize
zenario.formSubmit = function(el, scrollToTopOfSlot, fadeOutAndIn, slotName) {
	if (slotName === undefined) {
		slotName = zenario.getSlotnameFromEl(el);
	}
	
	if (fadeOutAndIn === undefined) {
		fadeOutAndIn = true;
	}
	
	if (zenarioA.init) {
		zenarioA.closeSlotControls();
		zenarioA.cancelMovePlugin();
	}
	
	if (zenario.slotFormSubmissions[slotName]) {
		return false;
	} else {
		zenario.slotFormSubmissions[slotName] = true;
		
		var $el = $(el); 
		
		//Check to see if there is a file upload in this slot
		if ($el.find(':file').length > 0) {
			//If so, don't attempt to use jQuery serialize(), and do a normal form submission
			return true;
		}
		
		var url = URLBasePath + 'zenario/ajax.php?method_call=refreshPlugin&inIframe=true',
			post = $el.serialize(),
			instanceId = zenario.slots[slotName].instanceId;
		
		if (zenario.lastButtonClickedName) {
			post += '&' + encodeURIComponent(zenario.lastButtonClickedName) + '=' + encodeURIComponent(zenario.lastButtonClickedValue);
			delete zenario.lastButtonClickedName;
		}
		
		if (scrollToTopOfSlot && !zenarioAB.isOpen) {
			//Scroll to the top of a slot if needed
			zenario.scrollToSlotTop(slotName, true);
			
			//Don't scroll to the top later if we've already done it now
			scrollToTopOfSlot = false;
		}
		
		if (fadeOutAndIn && !zenario.colorboxOpen) {
			//Fade the slot out to give a graphical hint that something is happening
			$('#plgslt_' + slotName).stop(true, true).animate({opacity: .5}, 200);
		}
		
		if ($el.attr('method') == 'post') {
			$.ajax({
				type: 'POST',
				dataType: 'text',
				url: url,
				data: post,
				complete: function(XMLHttpRequest, textStatus) {
					delete zenario.slotFormSubmissions[slotName];
				},
				success: function(html) {
					zenario.replacePluginSlotContents(slotName, instanceId, html, undefined, undefined, scrollToTopOfSlot, true);
				}
			});
		} else {
			zenario.refreshPluginSlot(slotName, instanceId, post, true, scrollToTopOfSlot, fadeOutAndIn);
		}
		
		return false;
	}
};


zenario.uneschyp = function(string) {
	return string.replace(/`r/g, "\r").replace(/`n/g, "\n").replace(/`h/g, "-").replace(/`t/g, "`");
};

//Set up a new encapsulated object for Plugins
zenario.enc = function(id, className, moduleClassNameForPhrases) {
	if (typeof window[className] != 'object') {
		window[className] = new zenario.moduleBaseClass(
			id, className, moduleClassNameForPhrases,
			zenario);
		
		window[className].slots = new Object();
		
		zenario.modules[id] = function() {};
		zenario.modules[id].moduleClassName = className;
		zenario.modules[id].moduleClassNameForPhrases = moduleClassNameForPhrases;
	}
};

//Create encapculated objects for slots/instances
zenario.slot = function(pluginInstances) {
	foreach (pluginInstances as var i) {
		
		var p = new Object();
		
		p.slotName = pluginInstances[i][0];
		p.instanceId = pluginInstances[i][1];
		p.moduleId = pluginInstances[i][2];
		p.level = pluginInstances[i][3];
		
		if (pluginInstances[i][4]) {
			p.tabId = pluginInstances[i][4];
		} else {
			p.tabId = 0;
		}
		
		//Record the name of the first main slot
		if (pluginInstances[i][5]) {
			zenario.mainSlot = p.slotName;
		}
		
		//Record if this slot is being edited (note: only appears in admin mode)
		if (pluginInstances[i][6]) {
			p.beingEdited = true;
		}
		
		//Record if this plugin is version controlled (note: only set in admin mode)
		if (pluginInstances[i][7]) {
			p.isVersionControlled = true;
		}
		
		//If we are replacing an existing instance in admin mode, delete that first
		var old;
		if (old = zenario.slots[p.slotName]) {
			if (old.instanceId) {
				delete zenario.instances[old.instanceId];
				delete window[old.moduleClassName].slots[old.slotName];
			}
		}
		
		//If this slot is non-empty, get info on the Plugin inside
		if (p.instanceId) {
			//Check if the Plugin is running properly..
			if (!p.moduleId || !zenario.modules[p.moduleId]) {
				//...and remove the instance if not.
				p.instanceId = 0;
				p.moduleId = 0;
			
			//Otherwise get info on the Plugin inside
			} else {
				p.moduleClassName = zenario.modules[p.moduleId].moduleClassName;
				p.moduleClassNameForPhrases = zenario.modules[p.moduleId].moduleClassNameForPhrases;
				
				//Make info on this Plugin Instance availible to the core and to the Plugin
				zenario.instances[p.instanceId] = p;
				window[p.moduleClassName].slots[p.slotName] = p;
			}
		}
		
		//Record which instance is in which slot
		zenario.slots[p.slotName] = p;
		
		
		//Could do with expanding later, if/when we implement client events
	}
	
	if (zenarioA.checkSlotsBeingEdited) {
		zenarioA.checkSlotsBeingEdited();
	}
};



//Callback function for refreshPluginSlot()
zenario.slotFormSubmissions = {};
zenario.replacePluginSlotContents = function(slotName, instanceId, contents, additionalRequests, recordInURL, scrollToTopOfSlot, isFormPost) {
	
	delete zenario.slotFormSubmissions[slotName];
	zenario.currentHashSlot = slotName;

	//Look for the settings at the start
	var forceReloadHref = false,
		level = false,
		tabId = 0,
		cutoff = contents.indexOf('<!--/INFO-->'),
		info = false,
		beingEdited = false,
		isVersionControlled = false,
		scriptsToRun = new Array(),
		scriptsToRunBefore = new Array(),
		showInFloatingBox = false;
	
	//Don't try and do an AJAX reload if text has <script> or <styles> tags in
		//However, if this was a POST submission, ignore this check as we don't want to re-submit the post data
	contents = '' + contents;
	if (!isFormPost && contents.match(/<(link|script|style)/i)) {
		forceReloadHref = zenario.linkToItem(zenario.cID, zenario.cType, additionalRequests);
	}
	
	if (cutoff != -1) {
		//Get each tag from the info, then chop the tags off of the start of the content
		info = contents.substr(0, cutoff+4).split('--><!--');
		contents = contents.substr(cutoff+12);
		
		//Look through the info at the top of the AJAX return
		foreach (info as var i) {
			var details = info[i].split('--');
			
			//Allow modules to reject the AJAX reload and request an entire page reload
			if (details[0] == 'FORCE_PAGE_RELOAD') {
				forceReloadHref = zenario.uneschyp(details[1]);
			
			//Watch out for the "In Edit Mode" tag from modules in their edit modes
			} else if (details[0] == 'IN_EDIT_MODE') {
				beingEdited = true;
			
			} else if (details[0] == 'WIREFRAME') {
				isVersionControlled = true;
			
			//Watch out for the instance id
			} else if (details[0] == 'INSTANCE_ID') {
				instanceId = zenario.uneschyp(details[1]);
			
			//Watch out for the slot's level
			} else if (details[0] == 'LEVEL') {
				level = 1*details[1];
			
			//Watch out for Tab Ids from nested modules
			} else if (details[0] == 'TAB_ID') {
				tabId = zenario.uneschyp(details[1]);
			
			//Allow modules to name JavaScript function(s) they wish to be run
			} else if (details[0] == 'SCRIPT') {
				scriptsToRun[scriptsToRun.length] = zenario.uneschyp(details[1]);
			
			} else if (details[0] == 'SCRIPT_BEFORE') {
				scriptsToRunBefore[scriptsToRunBefore.length] = zenario.uneschyp(details[1]);
			
			} else if (details[0] == 'SCROLL_TO_TOP' && scrollToTopOfSlot === undefined) {
				scrollToTopOfSlot = true;
			
			//Allow modules to open themselves in a floating box
			} else if (details[0] == 'SHOW_IN_FLOATING_BOX') {
				showInFloatingBox = true;
			}
		}
	}
	
	if (forceReloadHref) {
		//Update the beingEdited flag for this slot
		if (zenario.slots[slotName]) {
			zenario.slots[slotName].beingEdited = beingEdited;
		}
		
		if (zenarioA.init && (zenarioA.floatingBoxOpen || zenarioA.checkSlotsBeingEdited())) {
			contents =
				'<div><em>(' +
					zenarioA.phrase.pluginNeedsReload.replace('[[href]]', htmlspecialchars(forceReloadHref)) +
				')</em></div>' +
				contents;
		} else {
			document.location.href = forceReloadHref;
			return;
		}
	}
	
	//Stop any animations currently on the slot
	$('#plgslt_' + slotName).stop(true, true).animate({opacity: 1}, 200, function() {
		if (zenario.browserIsIE()) {
			this.style.removeAttribute('filter');
		}
		
		//Fix a problem where the fading in/out can leave an inline style 
		this.style.opacity = '';
	});
	
	
	if (showInFloatingBox) {
		zenario.colorboxOpen = slotName;
		
		$.colorbox({
			transition: 'none',
			html: contents,
			onOpen: function() {
				var cb = get('colorbox');
				cb.className = get('plgslt_' + slotName).className;
				$(cb).hide().fadeIn();
			},
			onComplete: function() {
				zenario.addJQueryElements('#colorbox ');
				
				//Allow modules to call JavaScript function(s) after they have been refreshed
				foreach (scriptsToRun as var script) {
					if (zenario.slots[slotName]) {
						zenario.callScript(scriptsToRun[script], zenario.slots[slotName].moduleClassName);
					}
				}
			},
			onClosed: function() {
				get('colorbox').className = '';
				zenario.colorboxOpen = false;
			}
		});
		zenario.resizeFancyBox();
	
	} else {
		if (zenario.colorboxOpen) {
			$.colorbox.close();
			zenario.colorboxOpen = false;
		}
		
		if (!window.zenario_inIframe && zenarioA.init && info !== false) {
			//Admins need some more tasks, other than just changing the innerHTML
			zenarioA.replacePluginSlot(slotName, instanceId, level, tabId, contents, info, scriptsToRunBefore);
		} else {
			foreach (scriptsToRunBefore as var script) {
				if (zenario.slots[slotName]) {
					zenario.callScript(scriptsToRunBefore[script], zenario.slots[slotName].moduleClassName);
				}
			}
			
			//If we're not in admin mode, just refresh the slot's innerHTML
			zenario.slot([[slotName, instanceId, zenario.slots[slotName].moduleId, level, tabId, undefined, beingEdited, isVersionControlled]]);
			get('plgslt_' + slotName).innerHTML = contents;
		}
		
		//Allow modules to call JavaScript function(s) after they have been refreshed
		foreach (scriptsToRun as var script) {
			if (zenario.slots[slotName]) {
				zenario.callScript(scriptsToRun[script], zenario.slots[slotName].moduleClassName);
			}
		}
		
		zenario.addJQueryElements('#plgslt_' + slotName + ' ');
		
		
		if (scrollToTopOfSlot && !zenarioAB.isOpen) {
			//Scroll to the top of a slot if needed
			zenario.scrollToSlotTop(slotName, true);
		}
		
		
		//Attempt to record the current AJAX reload in the URL bar
		if (recordInURL) {
			
			//If the browser support HTML 5, we can use URL rewriting
			if (window.history && history.pushState) {
				
				//If this is the first AJAX request we've had, be sure to save the initial load-state of the page
				if (!zenario.previouslyPushedState) {
					zenario.previouslyPushedState = true;
					
					//Get variables from the URL
					var url = document.location.href,
						qMark = url.indexOf('?'),
						request = '';
					
					if (qMark != -1) {
						request = url.substr(qMark+1);
					}
					
					//Replace the current state - don't change the URL or the title,
					//but save the slot name that is being changed, and the initial request
					history.replaceState({slotName: slotName, request: request}, document.title, document.location.href);
				}
				
				//Work out the new URL to the page, then place this along with the slot name and requests into the history
				history.pushState({slotName: slotName, request: additionalRequests}, document.title, zenario.linkToItem(zenario.cID, zenario.cType, additionalRequests));
			
			//Old functionality using hashes in the URL, for browsers that don't support HTML 5.
			//And by that I mean Internet Explorer.
			} else {
				if (slotName == zenario.mainSlot) {
					document.location.hash = '!' + additionalRequests.substr(1);
				} else {
					document.location.hash = slotName + '!' + additionalRequests.substr(1);
				}
			}
			
			zenario.currentHash = document.location.hash;
		}
	}
	
	if (zenarioA.checkSlotsBeingEdited) {
		zenarioA.checkSlotsBeingEdited();
	}
};


zenario.callScript = function(script, className) {
	var functionName;
	
	if (typeof script == 'string') {
		script = JSON.parse(script);
	}
	
	if (script && script[0]) {
		if (window[script[0]] && typeof window[script[0]][script[1]] == 'function') {
			className = script.shift();
			functionName = script.shift();
		
		} else if (window[className] && typeof window[className][script[0]] == 'function') {
			functionName = script.shift();
		
		} else {
			return;
		}
		
		window[className][functionName].apply(null, script);
	}
};

zenario.resizeFancyBox = function() {
	setTimeout($.colorbox.resize, 5);
};

zenario.addClassesToColorbox = function(cssClasses) {
	if (typeof cssClasses == 'string') {
		cssClasses = cssClasses.split(/\s+/g);
	}
	foreach (cssClasses as var i) {
		$('#colorbox').addClass(cssClasses[i]);
	}
};

zenario.removeClassesToColorbox = function(cssClasses) {
	if (typeof cssClasses == 'string') {
		cssClasses = cssClasses.split(/\s+/g);
	}
	foreach (cssClasses as var i) {
		$('#colorbox').removeClass(cssClasses[i]);
	}
};

//Add jQuery elements automatically by class name
zenario.addJQueryElements = function(path, adminFacing) {
	
	if (!path || path === undefined) {
		path = '';
	}
	
	//Fancybox/Lightbox replacement
	$(path + "a[rel^='colorbox'], a[rel^='fancybox'], a[rel^='lightbox']").colorbox({
		title: function() { return $(this).attr('data-box-title'); }
	});
	
	if (zenario.browserIsIE(9)) {
		$(path + 'input[placeholder]').placeholder();
		$(path + 'textarea[placeholder]').placeholder();
	}
	
	//jQuery datepickers
	$(path + 'input.jquery_datepicker').each(function(i, el) {
		zenario.loadDatePicker();
		
		//Flexible Form functionality for date pickers that degrade gracefully into three select lists
		//if JavaScript is not enabled.
		if (el.id && get(el.id + '__0') && get(el.id + '__1') && get(el.id + '__2') && get(el.id + '__3')) {
			el.value = $.datepicker.formatDate(zenario.dpf, $.datepicker.parseDate('yy-mm-dd', get(el.id + '__0').value));
			
			$('#' + el.id).datepicker({
				dateFormat: zenario.dpf,
				altField: '#' + el.id + '__0',
				altFormat: 'yy-mm-dd',
				showOn: 'focus'
			});
			
			$('#' + el.id + '__1').remove();
			$('#' + el.id + '__2').remove();
			$('#' + el.id + '__3').remove();
		
		} else {
			$(el).datepicker({
				dateFormat: zenario.dpf,
				showOn: 'focus'
			});
		}
	});
	
	//Tooltips
	var tooltips;
	if (adminFacing && zenarioA.init) {
		tooltips = zenarioA.tooltips;
	} else {
		tooltips = zenario.tooltips;
	}
	
	tooltips(path + 'div[title]');
	tooltips(path + 'input[title]');
		
	if (!/android|iphone|ipod|series60|symbian|windows ce|blackberry/i.test(navigator.userAgent)) {
		tooltips(path + 'a[title]');
		tooltips(path + 'img[title]');
		tooltips(path + 'area[title]');
		
		if (zenarioA.init) {
			zenarioA.addJQueryElements(path);
		}
	}
	
	//clickablebox class
	$('.clickablebox').click(function() {
		var $a = $(this).find('a');
		
		if ($a && $a.length) {
			if ($a.attr('target') == '_blank') {
				window.open($a.attr('href'));
			} else {
				window.location = $a.attr('href');
			}
		}
		
		return false;
	});
	
	$(path + ':submit').click(function() {zenario.buttonClick(this)});
	$(path + ' submit').click(function() {zenario.buttonClick(this)});
};
$(document).ready(function() { zenario.addJQueryElements(); });


//Lazy-load the datepicker library when needed
zenario.loadDatePicker = function() {
	if (!$.datepicker) {
		$.ajax({
			async: false,
			url: URLBasePath + 'zenario/libraries/mit/jquery/jquery-ui.datepicker.min.js?v=' + zenarioCSSJSVersionNumber,
			dataType: 'script'
		});
	}
};

//The User presses a key on date field
zenario.dateFieldKeyUp = function(el, event, adminBoxId) {
	//Check to see if it was a space, delete or backspace key
	if (event.keyCode == 8 || event.keyCode == 32 || event.keyCode == 46) {
		//If so, clear the date that has been set
		if (adminBoxId) {
			zenarioAB.blankField(adminBoxId);
			zenarioAB.fieldChange(adminBoxId);
		}
		
		zenario.clearDateField(el);
	}
};


zenario.clearDateField = function(el) {
	if (typeof el === 'string') {
		el = get(el);
	}
	
	if (el) {
		zenario.loadDatePicker();
		$(el).datepicker('hide');
		
		el.value = '';
		if (el.id && get(el.id + '__0')) {
			get(el.id + '__0').value = '';
		}
		
		el.blur();
	}
};


zenario.lastButtonClickedName = false;
zenario.lastButtonClickedValue = false;
zenario.buttonClick = function(el) {
	if (el.name) {
		zenario.lastButtonClickedName = el.name;
		zenario.lastButtonClickedValue = el.value;
	} else {
		zenario.lastButtonClickedName = false;
		zenario.lastButtonClickedValue = false;
	}
};


zenario.formatDate = function(date, showTime, format) {
	if (!date) {
		return '';
	}
	
	var out,
		unix = false;
	
	try {
		if (date && (typeof date == 'number' || typeof date == 'string')) {
			
			var length = ('' + date).length;
			
			if (typeof date == 'number' || 1*date == 1*date) {
				//Format a date as a UNIX time (seconds since 1st Jan 1970)
				date = new Date(1000 * date);
				unix = true;
			
			} else if (typeof date == 'string' && (length == 10 || length == 19)) {
				//Format a date (in raw MySQL format) using the MySQL's syntax
				var dates = date.replace(/[:-]/g, ' ').split(' ');
				if (showTime && length == 19) {
					date = new Date(1*dates[0], 1*dates[1]-1, 1*dates[2], 1*dates[3], 1*dates[4], 1*dates[5]);
				} else {
					date = new Date(1*dates[0], 1*dates[1]-1, 1*dates[2]);
				}
			
			} else {
				return '';
			}
		}
		
		zenario.loadDatePicker();
		if (format) {
			out = $.datepicker.formatDate(format, date);
		
		} else if (zenarioA.siteSettings && zenarioA.siteSettings.organizer_date_format) {
			out = $.datepicker.formatDate(zenarioA.siteSettings.organizer_date_format, date);
		
		} else {
			out = $.datepicker.formatDate('d M yy', date);
		}
		
		if (showTime) {
			
			var hours = date.getHours(),
				minutes = date.getMinutes(),
				isPM = hours > 11,
				amOrPM = '',
				timeformat = (zenarioA.siteSettings && zenarioA.siteSettings.vis_time_format) || '%H:%i';
			
			switch (timeformat) {
				case '%k:%i': //'9:00 - 17:00'
					break;
				case '%H:%i': //'09:00 - 17:00'
					hours = zenario.rightHandedSubStr('0' + hours, 2);
					break;
				case '%l:%i %p': //'9:00 AM - 5:00 PM'
					hours = (hours % 12) || 12;
					amOrPM = isPM? zenarioA.phrase.pm : zenarioA.phrase.am;
					break;
				case '%h:%i %p': //'09:00 AM - 05:00 PM'
					hours = (hours % 12) || 12;
					amOrPM = isPM? zenarioA.phrase.pm : zenarioA.phrase.am;
					hours = zenario.rightHandedSubStr('0' + hours, 2);
					break;
			}
			
			
			out += ' ' + hours + ':' + zenario.rightHandedSubStr('0' + date.getMinutes(), 2);
			
			if (showTime == 'datetime_with_seconds') {
				out += ':' + zenario.rightHandedSubStr('0' + date.getSeconds(), 2);
			}
			
			if (amOrPM) {
				out += ' ' + amOrPM;
			}
			
			if (unix && date.getTimezoneOffset() != (new Date()).getTimezoneOffset()) {
				if (date.getTimezoneOffset() > 0) {
					out += ' (UCT -' + (date.getTimezoneOffset() / 60) + ')';
				} else {
					out += ' (UCT +' + (date.getTimezoneOffset() / -60) + ')';
				}
			}
		}
		
		return out;
		
	} catch (e) {
		return '';
	}
};


//Wrapper function for jQuery tooltips
zenario.tooltips = function(target, options) {
	zenario.closeTooltip();
	if (target === undefined) {
		//Add tooltips to an entire page after it has been loaded
		zenario.tooltips('a[title]', options);
		zenario.tooltips('img[title]', options);
		zenario.tooltips('area[title]', options);
		zenario.tooltips('input[title]', options);
	} else {
		if (!options) {
			options = {};
		}
		
		if (options.tooltipClass === undefined) {
			options.tooltipClass = 'zenario_visitor_tooltip';
		}
		if (options.show === undefined) {
			options.show = {duration: 750, easing: 'zenarioLinearWithBigDelay'};
		}
		if (options.hide === undefined) {
			options.hide = 100;
		}
		if (options.position === undefined) {
			options.position = {my: 'center top+2', at: 'center bottom', collision: 'flipfit'};
		}
		if (options.content === undefined) {
			options.content = function() {
				var title = this.title,
					pos = this.title.indexOf('|');
				
				if (pos != -1) {
					return '<h3>' + this.title.substr(0, pos) + '</h3><p>' + this.title.substr(pos+1) + '</p>';
				} else {
					return title;
				}
			};
		}
		
		$('.ui-tooltip').remove();
		
		$(target).each(function(i, el) {
			var thisOptions,
				$el = $(el),
				tooltipOptions = $el.attr('data-tooltip-options');
			
			if (tooltipOptions) {
				try {
					thisOptions = $.extend(true, {}, options, JSON.parse(tooltipOptions));
				} catch (error) {
					try {
						thisOptions = $.extend(true, {}, options, JSON.parse(zenario.fixJSON(tooltipOptions)));
					} catch (error) {
						thisOptions = options;
					}
				}
			} else {
				thisOptions = options;
			}
			
			if (thisOptions.position && thisOptions.position.using === undefined) {
				thisOptions.position.using = zenario.tooltipsUsing;
			}
					
			$el.jQueryTooltip(thisOptions);
		})
		
	}
};

//Adjusted from http://jqueryui.com/tooltip/#custom-style
zenario.tooltipsUsing = function(position, feedback) {
	$(this)
		.css(position)
		.addClass('tooltip_' + feedback.vertical + '_' + feedback.horizontal);
	$('<span>')
		.addClass('tooltip_arrow')
		.appendTo(this);
};

zenario.closeTooltip = function() {
	if (get('tiptip_holder')) {
		get('tiptip_holder').style.display = 'none';
	}
	$('body > .mce-tooltip').remove();
};


//Attempt to convert a JSON string into the exact format required by the very picky JSON parser.
//All strings and even labels should be quoted with double quotes and not single quotes.
zenario.fixJSON = function(json) {
	var a,
		b,
		len,
		t = json;
	
	//Split up the string by "{"s, "}"s, ":"s and ","s
	t = t.split('{');
	foreach (t as var i) {
		t[i] = t[i].split(',');
		foreach (t[i] as var j) {
			t[i][j] = t[i][j].split('}');
			foreach (t[i][j] as var k) {
				t[i][j][k] = t[i][j][k].split(':');
				foreach (t[i][j][k] as var l) {
					t[i][j][k][l] = t[i][j][k][l].replace(/^\s+/, '').replace(/\s+$/, '');
					
					if ((len = t[i][j][k][l].length) > 0) {
						//Check to see what type of quotes are being used
						var a = t[i][j][k][l].substr(0, 1),
							b = t[i][j][k][l].substr(len-1, 1);
						
						//Leave double quoted text alone
						if (a == '"' && b == '"') {
						
						//Leave numbers alone
						} else if (1 * t[i][j][k][l] == t[i][j][k][l]) {
						
						//Attempt convert single quotes to double quotes
						//(This logic is a bit simplistic; it might not work in all cases)
						} else if (a == "'" && b == "'") {
							t[i][j][k][l] = '"' + t[i][j][k][l].substr(1, len-2).replace(/\"/g, '\\"') + '"';
						
						//Add double quotes if not present
						} else {
							t[i][j][k][l] = '"' + t[i][j][k][l] + '"';
						}
					}
				}
				
				//Combine the string up again
				t[i][j][k] = t[i][j][k].join(':');
			}
			t[i][j] = t[i][j].join('}');
		}
		t[i] = t[i].join(',');
	}
	t = t.join('{');
	
	return t;
};



//Add a new plugin stylesheet to the page dynamically
//This is so that if we add a new plugin or switch swatches, the new JS can be added
//without needing a page reload
zenario.javaScriptOnPage = new Object();
zenario.addPluginJavaScript = function(moduleId, alwaysAdd) {
	
	//Work out the path from the plugin name and the swatch name
	filePath = 'zenario/js/plugin.wrapper.js.php?ids=' + moduleId + '&v=' + zenarioCSSJSVersionNumber;
	
	if (zenarioA.init) {
		filePath += '&admin=1';
	}
	
	//Make sure that a script file is only included once, unless the "alwaysAdd" override is used
	if (!alwaysAdd && zenario.javaScriptOnPage[filePath]) {
		return;
	}
	
	eval(zenario.nonAsyncAJAX(URLBasePath + filePath));
	
	zenario.javaScriptOnPage[filePath] = true;
};


zenario.captcha = function(publicKey, divId, hideAudio) {
	if (hideAudio) {
		RecaptchaOptions.callback = function() {zenario.captchaHideAudio()}
	}
	
	if (!window.Recaptcha) {
		$.getScript(
			zenario.httpOrhttps() + 'www.google.com/recaptcha/api/js/recaptcha_ajax.js',
			function() {
				Recaptcha.create(publicKey, divId, RecaptchaOptions);
			}
		);
	} else {
		Recaptcha.create(publicKey, divId, RecaptchaOptions);
	}
};

zenario.captchaHideAudio = function() {
	$('.recaptcha_only_if_image').attr('href', '#').attr('title', '').css('cursor', 'default');
	$('.recaptcha_only_if_image img').attr('alt', '').attr('tooltip', '').attr('src', $('.recaptcha_only_if_audio img').attr('src'));
};

zenario.rightHandedSubStr = function(string, ammount) {
	if (zenario.browserIsIE()) {
		var len = string.length;
		return string.substr(len - ammount, ammount);
	} else {
		return string.substr(-ammount);
	}
};


zenario.fireChangeEvent = function(el) {
	//http://stackoverflow.com/questions/16016870/
	if (el.fireEvent) {
		el.fireEvent('onchange');
	} else {
		var evt = document.createEvent('HTMLEvents');
		evt.initEvent('change', false, true);
		el.dispatchEvent(evt);
	}
};


//Stop event propigation
zenario.stop = function(e) {
	if (e = (e || window.event)) {
		if (e.stopPropagation) {
			e.stopPropagation();
		} else {
			e.cancelBubble = true;
		}
	}
	
	return false;
};


//Local Storage
zenario.rev = '';
zenario.lastLoadNum = false;

zenario.checkSessionStorage = function(url, requests, isObject, loadNum) {
	return zenario.checkLocalStorage(url, requests, isObject, loadNum, true);
};

zenario.checkLocalStorage = function(url, requests, isObject, loadNum, session) {
	zenario.checkLastUrl = url;
	
	//Don't do anything for IE 6 and 7
	if (zenario.browserIsIE(7)) {
		return false;
	}
	
	url += zenario.urlRequest(requests);
	
	//Don't do anything if no_cache is set in the request string
	if (url.indexOf('no_cache') != -1) {
		var test = url.split(/&|\?/g);
		foreach (test as var t) {
			if (test[t] == 'no_cache') {
				return false;
			} else if (test[t].substr(0, 9) == 'no_cache=' && engToBoolean(test[t].substr(9))) {
				return false;
			}
		}
	}
	
	
	//Check if the requested item is actually in the local storage
	var name = zenario.userId + '_' + zenario.adminId + '_' + url;
	var store = zenario.sGetItem(session, name, isObject);
	
	if (!store) {
		return false;
	}
	
	
	//Get the latest data revision number and code hash
	var oldRev = zenario.sGetItem(session, 'rev');
	
	//If this is part of the same "load" as a previous request, we do not need to keep checking the data revision number with each load
	if (oldRev && loadNum && zenario.lastLoadNum === loadNum) {
		zenario.rev = oldRev;
	} else {
		zenario.checkDataRevisionNumber(false);
	}
	zenario.lastLoadNum = loadNum;
	
	
	//Clear the storage if the data is out of date
	if (!oldRev || !zenario.rev || oldRev != zenario.rev) {
		zenario.sClear(session);
		zenario.sSetItem(session, 'rev', zenario.rev);
		return false;
	
	//Otherwise return the data
	} else {
		return store;
	}
};

zenario.setSessionStorage = function(merge, url, requests, isObject) {
	zenario.setLocalStorage(merge, url, requests, isObject, true);
};

zenario.setLocalStorage = function(merge, url, requests, isObject, session) {
	
	//Don't do anything for IE 6 and 7
	if (zenario.browserIsIE(7)) {
		return false;
	}
	
	if (!zenario.rev) {
		zenario.checkDataRevisionNumber(false);
	}
	
	if (zenario.rev && merge) {
		url += zenario.urlRequest(requests);
		var name = zenario.userId + '_' + zenario.adminId + '_' + url;
		zenario.sSetItem(session, name, merge, isObject);
	}
};

zenario.checkDataRevisionNumber = function(async) {
	
	if (zenarioAB.tuix && zenarioAB.tuix.path == 'install') {
		return;
	}
	
	var url = URLBasePath + 'zenario/quick_ajax.php',
		data = {_get_data_revision: 1, admin: !!zenarioA.init};
	
	if (async) {
		$.ajax({
			type: 'POST',
			url: url,
			data: data,
			success: function(data) {
				zenario.rev = data;
			},
			dataType: 'text'
		});
	} else {
		zenario.rev = zenario.nonAsyncAJAX(url, data);
	}
};


//Functions to check local storage.
//If a browser doesn't support local storage then as a hack some just cache the data in memory
zenario.ls = {l: {}, s: {}};
zenario.sClear = function(session) {
	var storage, type;
	if (session) {
		type = 's';
		storage = window.sessionStorage;
	} else {
		type = 'l';
		storage = window.localStorage;
	}
	
	if (!zenario.canSetCookie || !storage) {
		delete zenario.ls[type];
		zenario.ls[type] = {};
	} else {
		storage.clear();
	}
};

zenario.sSetItem = function(session, name, data, isObject, retry) {
	var storage, type;
	if (session) {
		type = 's';
		storage = window.sessionStorage;
	} else {
		type = 'l';
		storage = window.localStorage;
	}
	
	if (!zenario.canSetCookie || !storage) {
		zenario.ls[type][name] = data;
	
	} else {
		try {
			if (!isObject) {
				storage.setItem(name, data);
			
			} else {
				storage.setItem(name, JSON.stringify(data));
			}
		} catch (e) {
			if (!retry) {
				storage.clear();
				zenario.sSetItem(session, name, data, isObject, true);
			}
		}
	}
};

zenario.sGetItem = function(session, name, isObject) {
	var storage, type;
	if (session) {
		type = 's';
		storage = window.sessionStorage;
	} else {
		type = 'l';
		storage = window.localStorage;
	}
	
	if (!zenario.canSetCookie || !storage) {
		return zenario.ls[type][name];
	
	} else {
		var data;
		if (data = storage.getItem(name)) {
			if (!isObject) {
				return data;
			
			} else if (data = JSON.parse(data)) {
				return data;
			}
		}
		
		return false;
	}
};

//Include JSON library, if it's not defined natively
if (!window.JSON) {
	$.ajax({url: URLBasePath + 'zenario/libraries/public_domain/json/json2.min.js', dataType: "script", async: false});
}

//Add the Math.log10() function, if it's not defined natively
if (!Math.log10) {
	Math.log10 = function(n) {
		return Math.log(n) / Math.log(10);
	};
}



});
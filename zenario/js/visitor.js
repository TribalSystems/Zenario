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
	encodeURIComponent, get, engToBoolean, htmlspecialchars, jsEscape, phrase,
	extensionOf, methodsOf, has,
	extraVar1, extraVar2, s$s
) {
	"use strict";


zenario.slots = new Object();
zenario.modules = new Object();
zenario.instances = new Object();
zenario.mainSlot = false;

zenario.signalsInProgress = {};
zenario.signalHandlers = {};
zenario.adinsActions = {};
zenario.jsLibs = {};


zenario.getEl = false;

//Note that page caching may cause the wrong user id to be set.
//As with session('extranetUserID'), anything that changes behaviour by Extranet User should not allow the page to be cached.
zenario.userId = 0;
zenario.adminId = 0;






//Create a library with some dummy functions for the conductor,
//so plugins do not crash if the full conductor library is not loaded
var zenario_conductor = createZenarioLibrary('_conductor');
zenario_conductor.slots = {};
zenario_conductor.setCommands =
zenario_conductor.mergeRequests =
zenario_conductor.registerGetRequest =
zenario_conductor.clearRegisteredGetRequest =
zenario_conductor.getRegisteredGetRequest =
zenario_conductor.confirmOnCloseMessage =
zenario_conductor.confirmOnClose =
zenario_conductor.commandEnabled =
zenario_conductor.enabled =
zenario_conductor.refresh =
zenario_conductor.backLink =
zenario_conductor.goBack =
zenario_conductor.link =
zenario_conductor.go ==> { return undefined; };


//Callback class	
zenario.callback = function() {
	this.isOwnCallback = false;
	this.isWrapper = false;
	this.wasPoked = false;
	this.results = [undefined];
	this.completes = [false];
	this.done = false;
	this.funs = [];
};
var methods = methodsOf(zenario.callback);

//Register a function to call afterwards.
//Your function will be called with the result of the callback as its arguement
//(Or the results of the callbacks as its arguements, if you have chained multiple callbacks together)
methods.after = function(fun, that) {
	if (_.isFunction(fun)) {
		this.funs.push([fun, that || this]);
		
		//Catch the case where an after() was added after call() was called
		//Immediately run 
		if (this.done) {
			setTimeout(this.checkComplete, 0);
		}
	}
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
	this.done = false;
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

//Force a callback to run it's functions even if nothing has been registered yet
//(If something has been registered, this will do nothing.)
methods.poke = function() {
	this.wasPoked = true;
	this.completes[0] = true;
	this.checkComplete();
};

//Check to see if the callback is complete and trigger the callback function if so.
//An internal function, no need to call it.
methods.checkComplete = function() {
	var i, link, fun;
	
	if (!this.isOwnCallback && !this.isWrapper && !this.wasPoked) {
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
		
		this.funs = [];
	}
	this.done = true;
};

//Some different examples of how to use the callback function above
//window.test = function() {
//	var url1 = URLBasePath + 'zenario/admin/organizer.ajax.php?_start=0&_get_item_name=1&path=zenario__content%2Fpanels%2Flanguage_equivs&_item=html_1&_limit=1',
//		url2 = URLBasePath + 'zenario/admin/organizer.ajax.php?_start=0&_get_item_name=1&path=zenario__content%2Fpanels%2Flanguage_equivs&_item=html_2&_limit=1',
//		url3 = URLBasePath + 'zenario/admin/organizer.ajax.php?_start=0&_get_item_name=1&path=zenario__content%2Fpanels%2Flanguage_equivs&_item=html_3&_limit=1';
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

//A version of nonAsyncAJAX for modules
//As this uses zenario.nonAsyncAJAX() we should start to avoid using this from now on...
zenario.moduleNonAsyncAJAX =

//Some old deprecated names
zenario.handlePluginAJAX =
zenario.pluginClassAJAX = function(moduleClassName, requests, post, json, useCache) {
	return zenario.nonAsyncAJAX(URLBasePath + 'zenario/ajax.php?moduleClassName=' + moduleClassName + '&method_call=handleAJAX' + zenario.urlRequest(requests), post, json, useCache);
};





//Make a non-asyncornous AJAX call.
//Note that this is deprecated!
zenario.nonAsyncAJAX = function(url, post, json, useCache) {
	
	//if (zenarioA.adminSettings.show_dev_tools
	// && window.console
	// && console.trace) {
	//	console.trace('Synchronous AJAX request made');
	//	//or
	//	var e = new Error();
	//	console.log(e.stack);
	//}
	
	url = zenario.addBasePath(url);
	
	var xmlHttp = {};
	
	//If this isn't a post request, only launch this request if it cannot be found in the storage
	if (post
	 || !useCache
	 || !(xmlHttp.responseText = zenario.checkSessionStorage(url))) {
		xmlHttp = new Object();
	
		if (window.ActiveXObject) {
			xmlHttp = new ActiveXObject('Microsoft.XMLHTTP');
		} else if (window.XMLHttpRequest) {
			xmlHttp = new XMLHttpRequest();
		}
	
		//Use GET or POST, as requested.
		if (!post) {
			//If you're using GET then any variables need to be set in the URL
			xmlHttp.open('GET', url, false);
			xmlHttp.send(null);
		
			if (useCache) {
				zenario.setSessionStorage(xmlHttp.responseText, url);
			}
	
		} else {
			//If you're using POST then variables need to be set in the POST.
			// (This uses the same format as GET, however without the initial ?)
		
			//Check to see if the caller took the time to seperate the two different inputs out,
			//or if they have dumped them all into the url
			if (post === true) {
				//If post has just been set to true, try to check the url for the actual inputs!
				var qMark = url.indexOf('?');
			
				if (qMark == -1) {
					//Case where POST must be used, but there are not actually any requests
					post = '';
				} else {
					//Get variables from the URL and put them in the POST
					post = url.substr(qMark+1);
					url = url.substr(0, qMark);
				}
		
			} else if (typeof post != 'string') {
				post = zenario.urlRequest(post);
			}
		
			xmlHttp.open('POST', url, false);
			xmlHttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			xmlHttp.send(post);
		}
	}

	//Either return the response as-is, of if JSON was set, do JSON.parse on it first.
	if (!json) {
		return xmlHttp.responseText;
	} else {
		try {
			return JSON.parse(xmlHttp.responseText);
		} catch (e) {
			if (xmlHttp.responseText) {
				if (zenario.inAdminMode) {
					zenarioA.floatingBox(xmlHttp.responseText, true, 'error');
				} else {
					alert(xmlHttp.responseText);
				}
			}
		}
	}
};

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
zenario.ajax = function(url, post, json, useCache, retry, continueAnyway, settings, timeout, AJAXErrorHandler, onRetry, onCancel) {

	url = zenario.addBasePath(url);
	
	var qMark, name, setting, options,
		type = post? 'POST' : 'GET',
		result = false,
		aborted = false,
		retryFun,
		hadErrorAndHandledIt = false,
		cb = new zenario.callback,
		oldDataRevisionNumber = zenario.dataRev(),
		
		//If the request is a success, note down the data.
		success = function(data) {
			if (aborted) return;
			result = data;
		},
		
		//If there was an error, attempt to handle it
		error = function(resp, statusType, statusText) {
			if (aborted) return;
			
			if (AJAXErrorHandler = AJAXErrorHandler || zenarioA.AJAXErrorHandler) {
				
				AJAXErrorHandler(resp, statusType, statusText);
				hadErrorAndHandledIt = true;
			}
		},
		
		//Call this function when we have the data and need to return it
		complete = function(resp, statusType, statusText) {
			if (aborted || hadErrorAndHandledIt) return;
			
			var parsedResult = false;
			
			//Either return the response as-is, of if JSON was set, do JSON.parse on it first.
			if (json) {
				try {
					parsedResult = JSON.parse(result);
				} catch (e) {
					if (result) {
						//Try to see if an error-handler has been set to show the error
						if (AJAXErrorHandler = AJAXErrorHandler || zenarioA.AJAXErrorHandler) {
							resp.responseText = result;
							AJAXErrorHandler(resp, statusType, statusText);
						
						//Otherwise just use alert() to handle the errors
						} else {
							alert(result);
						}
					}
					return;
				}
		
				cb.call(parsedResult);
			} else {
				cb.call(result);
			}
			
			//If we were supposed to be using the cache, remember this result for next time
			if (useCache) {
				zenario.setSessionStorage(result, url);
			}
		},
		
		//Call this function to trigger the AJAX request
		doRequest = function() {
			result = false;
			aborted = false;
			hadErrorAndHandledIt = false;
			
			//Check to see if the caller took the time to seperate the two different inputs out,
			//or if they have dumped them all into the url
			if (post === true) {
				//If post has just been set to true, try to check the url for the actual inputs!
				qMark = url.indexOf('?');
	
				if (qMark == -1) {
					//Case where POST must be used, but there are not actually any requests
					post = '';
				} else {
					//Get variables from the URL and put them in the POST
					post = url.substr(qMark+1);
					url = url.substr(0, qMark);
				}
			}
			
			options = {
				data: post,
				type: type,
				dataType: 'text',
				success: success,
				error: error,
				complete: complete
			}
			
			// Add any extra settings
			if (settings) {
				foreach (settings as name => setting) {
					options[name] = setting;
				}
			}
			
			//Do the AJAX request
			var req = $.ajax(url, options);
			
			//Some error handlers show a retry button that relaunches the request when pressed
			//If this is available here, pass on the function they need to call
			if (retry) {
				req.zenario_retry = retry;
			}
			
			//Notices, error messages, print_r()s and var_dump()s cause anything that uses JSON to valid
			//validation. However it's possible that the JSON afterwards may still be valid.
			//Allow an error handler to continue anyway in this case.
			if (continueAnyway) {
				req.zenario_continueAnyway = function(data) {
					cb.call(data);
				};
			}
			
			//Allow a script to be called neither the cancel button was pressed,
			//and not the retry/continue buttons
			if (onCancel) {
				req.zenario_onCancel = onCancel;
			}
			
			
			//Set a timeout on the request. If the timeout expires, we'll either retry or just give in
			//if retry is not specified.
			if (timeout) {
				setTimeout( => {
					if (req.readyState < 4) {
						if (retry) {
							aborted = true;
							req.abort();
							timeout *= 2;
							retry();
						} else {
							req.abort();
						}
					}
				}, timeout);
			}
		};
	
	if (retry === true) {
		retry = doRequest;
	}
	if (onRetry) {
		retryFun = retry;
		retry ==> {
			onRetry();
			retryFun();
		};
	}
	
	//For GET requests, should we try using the cache in the session storage?
	if (useCache && oldDataRevisionNumber && !post) {
		//Don't do anything if no_cache is set in the URL
		if (url.indexOf('no_cache') != -1) {
			var test = url.split(/&|\?/g);
			foreach (test as var t) {
				if (test[t] == 'no_cache') {
					useCache = false;
				} else if (test[t].substr(0, 9) == 'no_cache=' && engToBoolean(test[t].substr(9))) {
					useCache = false;
				}
			}
		}
	
		//Look for this request in the session storage
		if (useCache) {
			var name = zenario.userId + '_' + zenario.adminId + '_' + url,
				store = zenario.sGetItem(true, name);
			
			//If we found it then we'll need to look up the current data revision number to see if it was in-date.
			//(We'll also need to look it up if we never knew it in the first place!)
			if (store || !oldDataRevisionNumber) {
				zenario.checkDataRevisionNumber(true, => {
					var currentDataRevisionNumber = zenario.dataRev();
					
					//If we didn't find it, or what we found was out of date, we still need to look it up again.
					//Also, if it was out of date, we need to clear everything else out!
					if (oldDataRevisionNumber !== currentDataRevisionNumber) {
						doRequest();
					
					} else if (!store) {
						doRequest();
					
					//Otherwise we can use the cached value!
					} else {
						result = store;
						useCache = false;
						complete();
					}
				
				});
				return cb;
			}
		}
	}
	
	//If we didn't use the cache above, run the function now
	doRequest();
	return cb;
};







var loadingScripts = {},
	loadedScripts = {},
	scriptsLoadedCallback = new zenario.callback;

zenario.loadScript =
zenario.loadLibrary = function(path, callback, alreadyLoaded, stylesheet) {

	var library =
			loadedScripts[path] =
				loadedScripts[path] || {
					cb: new zenario.callback
				};
	
	library.cb.after(callback);
	
	if (alreadyLoaded || library.loaded) {
		library.loaded = true;
		library.cb.call();
	
	} else if (!library.loading) {
		library.loading = true;
		
		loadingScripts[path] = true;
		
		if (stylesheet) {
			$('head').append($('<link rel="stylesheet" type="text/css" href="' + htmlspecialchars(stylesheet) + '">'));
		}
		
		$.ajax({
			url: path,
			async: !!callback,
			cache: true,
			dataType: 'script',
			success: function() {
				library.loaded = true;
				library.cb.call();
			},
		
			complete: => {
				delete loadingScripts[path];
		
				if (_.isEmpty(loadingScripts)) {
					zenario.sendSignal('eventLoadedScripts');
				}
			}
		});
	}
};

zenario.eventLoadedScripts = function() {
	scriptsLoadedCallback.call();
	scriptsLoadedCallback = new zenario.callback; 
};


//Lazy-load the datepicker library when needed
zenario.loadDatePicker = function(async) {
	return zenario.loadLibrary(URLBasePath + 'zenario/libraries/mit/jquery/jquery-ui.datepicker.min.js?v=' + zenarioCSSJSVersionNumber,
		async, $.datepicker);
};




zenario.sendSignal = function(signalName, data, dontUseCachedSignalHandlers) {

	var id,
		module,
		moduleClass,
		returnValue,
		returnValues = [],
		signalHandler,
		signalHandlers = zenario.signalHandlers,
		signalsInProgress = zenario.signalsInProgress;
	
	if (signalsInProgress[signalName]) {
		return;
	}
	signalsInProgress[signalName] = true;
	
	if (!signalHandlers[signalName] || dontUseCachedSignalHandlers) {
		signalHandlers[signalName] = [];
		
		foreach (zenario.modules as id => module) {
			if (moduleClass = window[module.moduleClassName]) {
				if (_.isFunction(moduleClass[signalName])) {
					signalHandlers[signalName].push(moduleClass[signalName]);
				}
			}
		}
		
		foreach (zenario.libs as id => moduleClass) {
			if (_.isFunction(moduleClass[signalName])) {
				signalHandlers[signalName].push(moduleClass[signalName]);
			}
		}
	}

	foreach (signalHandlers[signalName] as id => signalHandler) {
		returnValue = signalHandler(data);
	
		if (returnValue !== undefined) {
			returnValues.push(returnValue);
		}
	}

	delete signalsInProgress[signalName];
	return returnValues;
};


zenario.inList = function(list, val) {
	
	if (list && typeof list == 'object') {
		//N.b. using "list[0] !== undefined" should catch both arrays
		//and arrays that were accidentally converted to objects using json_encode.
		return (list[0] !== undefined && _.contains(list, val))
			|| (_.isObject(list) && engToBoolean(list[val]));
	
	} else {
		return list == val;
	}
};


zenario.applyMergeFields = function(text, mrg) {
	mrg = mrg || {};

	var trans = '',
		b,
		bits = ('' + text).split(/\[\[(.*?)\]\]/g);

	foreach (bits as b) {
		if (b % 2) {
			if (mrg[bits[b]] !== undefined) {
				trans += mrg[bits[b]];
			}
		} else {
			trans += bits[b];
		}
	}
	
	return trans;
};



//Take a request string, and check it's formatted correctly
zenario.addAmp = function(request) {

	//For backwards compatability purposes, we'll accept a string with a URL already set, and strip the requests out
	var pos = request.indexOf('?');
	if (pos != -1) {
		request = request.substr(pos+1);
	}

	//Add an & to the beginning if needed
	if (request != '' && request.substr(0, 1) != '&') {
		return '&' + request;
	} else {
		return request;
	}
};

//Convert an array into a string for a URL if needed
zenario.urlRequest = function(arr) {

	//Don't run if this is already a string!
	if (_.isString(arr)) {
		return zenario.addAmp(arr);
	}

	var request = '';

	if (arr) {
		foreach (arr as var i) {
			if (typeof arr[i] != 'object') {
				request += '&' + encodeURIComponent(i) + '=';
			
				if (arr[i] !== undefined && arr[i] !== false && arr[i] !== null) {
					request += encodeURIComponent(arr[i]);
				}
			}
		}
	}

	return request;
};


//Reverse of the above, as per http://stackoverflow.com/questions/8648892/convert-url-parameters-to-a-javascript-object
zenario.toObject = function(object, clone) {

	if (!object) {
		return {};
	
	//Convert URL strings to objects
	} else if (_.isString(object)) {
		return JSON.parse('{"' + decodeURI(object.replace(/&/g, "\",\"").replace(/=/g,"\":\"")) + '"}') || {};
	
	} else if (clone) {
		return zenario.clone(object);
	
	} else {
		return object;
	}
};

zenario.clone = function(a, b, c) {
	return $.extend(true, {}, a, b, c);
};





//Functions for managing plugin slots

//Attempt to get the name of a slot from an element within the slot
zenario.getSlotnameFromEl = function(el, getContainerId) {
	if (_.isString(el)) {
		if (!getContainerId) {
			el = el.replace(/plgslt_/, '').split('-')[0];
		}
		return el;

	} else if (_.isObject(el)) {
		do {
			if (el.id && el.id == 'colorbox') {
				return zenario.colorboxOpen;
		
			} else if (el.id && el.id.substr(0, 7) == 'plgslt_') {
			
				var hyphen = el.id.indexOf('-'),
					slotName;
			
				//Extract the slot name out from the container id
				if (hyphen == -1) {
					slotName = el.id.substr(7);
				} else {
					slotName = el.id.substr(7, hyphen - 7);
				
					//Check that this matches the correct pattern
					var eggId = el.id.substr(hyphen + 1);
					if (eggId != 1*eggId) {
						continue;
					}
				}
			
				//Check if this is a name of a slot that exists!
				if (!zenario.slots[slotName]) {
					continue;
				}
			
				if (getContainerId) {
					return el.id;
				} else {
					return slotName;
				}
			}
		} while (el = el.parentNode)
	}
	return false;
};

//Scroll to the top of a slot if needed
zenario.scrollToSlotTop = function(containerIdSlotNameOrEl, neverScrollDown, time, el, offset) {
	if (typeof containerIdSlotNameOrEl == 'string') {
		containerIdSlotNameOrEl = get('plgslt_' + containerIdSlotNameOrEl) || get(containerIdSlotNameOrEl);
	}

	if (!containerIdSlotNameOrEl) {
		return;
	}

	var scrollTop = zenario.scrollTop(undefined, undefined, el);
	var slotTop = $(containerIdSlotNameOrEl).offset().top;

	if (offset === undefined) {
		offset = -80;
	}
	
	//For hacks where something like a sticky menu takes up space at the top so an offset is needed
	if (window._scrollToTopOffset) {
		offset = (offset || 0) - window._scrollToTopOffset;
	}

	//Check that the top of the slot is actually visible
	slotTop = Math.max(0, slotTop  + offset);

	//Have an option to only scroll up, and never down
	if (neverScrollDown && scrollTop < slotTop) {
		return;
	}

	if (time === undefined) {
		time = 700;
	}

	//Scroll to the correct place
	zenario.scrollTop(slotTop, time, el);
};

//Refresh a plugin in a slot
zenario.refreshPluginSlot = function(slotName, instanceId, additionalRequests, recordInURL, scrollToTopOfSlot, fadeOutAndIn, useCache, post) {
	
	if (scrollToTopOfSlot === undefined) {
		scrollToTopOfSlot = true;
	}

	if (fadeOutAndIn === undefined) {
		fadeOutAndIn = true;
	}
	
	slotName = zenario.getSlotnameFromEl(slotName);
	if (!slotName) {
		return;
	}

	if (zenario.inAdminMode) {
		zenarioA.closeSlotControls();
		zenarioA.cancelMovePlugin();
	}

	//Remove the Nested Plugin id from the slotname if needed
	slotName = slotName.split('-')[0];

	if (!zenario.slots[slotName]) {
		return;
	}

	if (!additionalRequests) {
		additionalRequests = '';
	} else {
		additionalRequests = zenario.urlRequest(additionalRequests);
	}
	
	additionalRequests = zenario.addTabIdToURL(additionalRequests, slotName);

	//Allow a slot to be refreshed by name only, in which case we'll check its current instance id
	if (instanceId == 'lookup') {
		instanceId = zenario.slots[slotName].instanceId;
	}

	if (scrollToTopOfSlot && !zenarioAB.isOpen) {
		//Scroll to the top of a slot if needed
		zenario.scrollToSlotTop(slotName, true);
	
		//Don't scroll to the top later if we've already done it now
		scrollToTopOfSlot = false;
	}

	//Fade the slot out to give a graphical hint that something is happening
	if (fadeOutAndIn) {
		var fadeOutAndInSelector = (fadeOutAndIn === 1 || fadeOutAndIn === true) ? ('#plgslt_' + slotName) : fadeOutAndIn;
		$(fadeOutAndInSelector).stop(true, true).animate({opacity: .5}, 150);
	}

	//Run an AJAX request to reload the contents
	var html,
		url = zenario.pluginAJAXURL(slotName, additionalRequests, instanceId); 

	//if (!post && useCache && (html = zenario.checkSessionStorage(url))) {
	//	zenario.replacePluginSlotContents(slotName, instanceId, html, additionalRequests, recordInURL, scrollToTopOfSlot);
	//} else {
	//	//(I'm using jQuery so that this is done asyncronously)
	//	var method = 'GET';
	//	if (post) {
	//		method = 'POST';
	//	}
	//	
	//	$.ajax({
	//		dataType: 'text',
	//		data: post,
	//		method: method,
	//		url: url,
	//		success: function(html) {
	//			if (useCache) {
	//				zenario.setSessionStorage(html, url);
	//			}
	//		
	//			zenario.replacePluginSlotContents(slotName, instanceId, html, additionalRequests, recordInURL, scrollToTopOfSlot);
	//		}
	//	});
	//}
	
	var cb = new zenario.callback;
	
	zenario.ajax(url, post, false, true).after(function(html) {
		zenario.replacePluginSlotContents(slotName, instanceId, html, additionalRequests, recordInURL, scrollToTopOfSlot);
		cb.call();
	});
	
	return cb;
};


//Link to a content item
zenario.linkToItem = function(cID, cType, request, adminlogin) {

	//Accept an input in the form of a Plugin Setting, e.g. "html_123"
	if (!cType && ('' + cID).indexOf('_') !== -1) {
		//Only accept the input if it's in the correct form
		var split = cID.split('_');
			//There should be only one underscore
		if (split[2] === undefined
			//The second part should be a number
		 && split[1] == 1 * split[1]
			//The first part must be a-z
		 && split[0].replace(/\w/g, '') === '') {
			cID = split[1];
			cType = split[0];
		}
	}

	if (!cType) {
		cType = 'html';
	}

	if (!request) {
		request = '';
	}

	var pos,
		canonicalURL,
		basePath = URLBasePath;
	if (adminlogin) {
		basePath += 'zenario/admin/welcome.php';
	} else {
		basePath += zenario.indexDotPHP;
	}
	
	//If we're linking to the content item that we're currently on...
	if (!adminlogin
	 && !zenario.adminId
	 && cID === zenario.cID) {
		//...check to see if it is using a friendly URL...
		if ((canonicalURL = $('link[rel="canonical"]').attr('href'))
		 && (!canonicalURL.match(/\bcID=/))) {
			//..and try to keep it if possible
			
			//Get rid of any existing requests
			pos = canonicalURL.indexOf('?');
			if (pos != -1) {
				canonicalURL = canonicalURL.substr(0, pos);
			}
			
			if (request) {
				return canonicalURL + '?' + zenario.urlRequest(request).substr(1);
			} else {
				return canonicalURL;
			}
		}
	}
	
	
	if (cID != 1*cID) {
		return basePath + '?cID=' + cID + zenario.urlRequest(request);

	} else {
		return basePath + '?cID=' + cID + '&cType=' + cType + zenario.urlRequest(request);
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


zenario.actAfterDelayIfNotSuperseded = function(type, fun, delay) {
	if (!delay) {
		delay = 900;
	}

	if (!zenario.adinsActions[type]) {
		zenario.adinsActions[type] = 0;
	}
	var thisAttemptNum = ++zenario.adinsActions[type];
	
	if (fun !== undefined) {
		setTimeout(
			function() {
				//Catch to stop outdated/spammed requests
				if (thisAttemptNum == zenario.adinsActions[type]) {
					fun();
				}
			}, delay);
	}
};

zenario.clearAllDelays = function(type) {
	if (type) {
		delete zenario.adinsActions[type];
	} else {
		zenario.adinsActions = {};
	}
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
		var pos, k, key, keys, req, message,
			hash = document.location.hash.substr(1),
			addImportantGetRequests = false,
			isRefreshSlotsCommand = hash.match(/^\d*\!_refresh\b/);
		
		//If this is an Organizer window, go to that location
		if (!isRefreshSlotsCommand
		 && zenarioA.isFullOrganizerWindow
		 && zenarioO.init) {
			
			if (hash.substr(0, 1) == '/') {
				hash = hash.substr(1);
			}
			
			//Check if there is an editor open
			message = zenarioT.onbeforeunload();
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
		if (zenario.inAdminMode
		 && zenarioA.checkSlotsBeingEdited()
		 && !confirm(zenarioA.phrase.leavePageWarning)
		) {
			document.location.hash = zenario.currentHash;
		
		//Same check for a FEA plugin in a nest conductor
		} else
		if ((undefined !== (message = zenario_conductor.confirmOnCloseMessage()))
		 && (!confirm(message))
		) {
			document.location.hash = zenario.currentHash;
		
		//Otherwise is this is the front-end, see if we can find which Plugin it mentions and then change that Plugin to use that request
		} else {
			if (zenarioAT.init) {
				if (hash.match(/\b__zenario_reload_at__\b/)) {
					zenarioAT.init();
					addImportantGetRequests = true;
				
				} else if (isRefreshSlotsCommand) {
					addImportantGetRequests = true;
				}
			}
			
			if ((pos = hash.lastIndexOf('!')) !== -1) {
				key = hash.substr(0, pos),
				req = hash.substr(pos+1);
				
				//Use an empty string as a shortcut to the first Main Slot
				key = key || zenario.mainSlot;
				
				if (addImportantGetRequests) {
					key += zenario.urlRequest(zenarioA.importantGetRequests);
				}
				
				//Check if this is a request to reload a Plugin via (numeric) instance id(s)
				if (key == key.replace(/[^0-9,]/g, '')) {
					keys = key.split(',');
					foreach (keys as k) {
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
				zenario.refreshPluginSlot(zenario.currentHashSlot, '', addImportantGetRequests && zenarioA.importantGetRequests || '', undefined, false, false);
				//zenario.refreshPluginSlot = function(slotName, instanceId, additionalRequests, recordInURL, scrollToTopOfSlot, fadeOutAndIn, useCache) {
			}
		}
	}
	
	//Remember the hash that we've just used
	zenario.currentHash = document.location.hash;
	
	//Keep watching for hash changes
	zenario.watchingForHashChanges =
		setTimeout( => {
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
		
		var message, slotName;
		
		if (slotName = event.state && event.state.slotName) {
			
			//Check if there is an editor open
			message = zenarioT.onbeforeunload();
			
			//If there was, give the Admin a chance to stop leaving the page
			if (message === undefined || confirm(message)) {
				
				
				zenario.refreshPluginSlot(slotName, 'lookup',
					
					//If there is no slideId in the URL bar, add one as an empty string,
					//to prevent a bug where the current slideId is added when going back to the first one
					zenario.addTabIdToURL(event.state.request, slotName, ''));
				
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


//For AJAX reloads for nests, check if a state, slideId or slide number is in a URL,
//and add one if not.
zenario.addTabIdToURL = function(url, slotName, specificSlideId) {
	
	//Check if this slot is a nest, and if so, which slide it is on
	var slideId = zenario.slots[slotName]
			 && zenario.slots[slotName].slideId;
	
	//Check if the URL contains no slide or state information.
	//If so, try to add the last slide/state used
	if (slideId && !url.match(/\&(state|slideId|slideNum)\=/)) {
		
		if (specificSlideId !== undefined) {
			url += '&slideId=' + specificSlideId;
		
		} else
		if (zenario_conductor.slots[slotName]
		 && zenario_conductor.slots[slotName].key) {
			url += zenario.urlRequest(zenario_conductor.slots[slotName].key);
		
		} else {
			url += '&slideId=' + slideId;
		}
	}
	
	return url;
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
	
	if (zenario.blockScrollToTop) {
		scrollToTopOfSlot = false;
		zenario.blockScrollToTop = false;
	}
	
	if (fadeOutAndIn === undefined) {
		fadeOutAndIn = true;
	}
	
	if (zenario.inAdminMode) {
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
			var fadeOutAndInSelector = (fadeOutAndIn === 1 || fadeOutAndIn === true) ? ('#plgslt_' + slotName) : fadeOutAndIn;
			$(fadeOutAndInSelector).stop(true, true).animate({opacity: .5}, 200);
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


zenario.unpackAndMerge = function(target, string) {
	var i,
		a = string.split('~'),
		m = a.length - 1;
	
	for (i = 0; i < m; i += 2) {
		target[a[i]] = a[i+1].replace(/`s/g, "~").replace(/`t/g, "`");
	}
};


zenario.uneschyp = function(string) {
	return string.replace(/`r/g, "\r").replace(/`n/g, "\n").replace(/`h/g, "-").replace(/`c/g, ":").replace(/`t/g, "`");
};

//Given a message that might have flags in it, parse the flags then strip them from the messages
//Flags can look like this: <!--Flag-->
//...or like this: <!--Flag:Value-->
zenario.splitFlagsFromMessage = function(resp) {
	
	var flag;
	
	if (typeof resp != 'object') {
		resp = {responseText: resp? '' + resp : ''};
	}
	
	if (typeof resp.flags != 'object') {
		resp.flags = {};
	}
	
	if (resp.responseText = resp.responseText || '') {
		//Strip the flags off of from start
		while ((flag = resp.responseText.split(/^\<\!--([^\:-]*?)(|\:([^\:-]*?))--\>/)) && (flag.length > 1)) {
			resp.flags[flag[1]] = flag[3] === undefined? true : zenario.uneschyp(flag[3]);
			resp.responseText = flag[4];
		}
	
		//Strip the flags off from the end
		while ((flag = resp.responseText.split(/\<\!--([^\:-]*?)(|\:([^\:-]*?))--\>$/)) && (flag.length > 1)) {
			resp.flags[flag[1]] = flag[3] === undefined? true : zenario.uneschyp(flag[3]);
			resp.responseText = flag[0];
		}
	}
	
	return resp;
};

//Set up a new encapsulated object for Plugins
zenario.enc = function(id, className, moduleClassNameForPhrases) {
	
	//Little shortcut to save space in definitions
	if (moduleClassNameForPhrases === 1) {
		moduleClassNameForPhrases = className;
	}
	
	if (typeof window[className] != 'object') {
		window[className] = new zenario.moduleBaseClass(
			id, className, moduleClassNameForPhrases,
			zenario);
		
		window[className].globalName = className;
		window[className].slots = {};
		
		zenario.modules[id] ==> {};
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
			p.slideId = pluginInstances[i][4];
		} else {
			p.slideId = 0;
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
				delete window[old.moduleClassName].outerSlots[old.slotName.split('-')[0]];
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
				
				//Note down which slots this plugin is in
				window[p.moduleClassName].slots[p.slotName] = p;
				
				//For nested plugins, specifically note down the outer slot that the nested plugin is in
				window[p.moduleClassName].outerSlots[p.slotName.split('-')[0]] = p;
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
zenario.replacePluginSlotContents = function(slotName, instanceId, resp, additionalRequests, recordInURL, scrollToTopOfSlot, isFormPost) {
	
	delete zenario.slotFormSubmissions[slotName];
	zenario.currentHashSlot = slotName;

	//Look for the settings at the start
	var forceReloadHref = false,
		level = false,
		slideId = 0,
		cutoff,
		info = [],
		beingEdited = false,
		isVersionControlled = false,
		scriptsToRun = new Array(),
		scriptsToRunBefore = new Array(),
		showInFloatingBox = false,
		floatingBoxExtraParams = {},
		containerId = 'plgslt_' + slotName,
		domSlot = get(containerId),
		flags, flagVal,
		ocb = new zenario.callback;
	
	//Look through the flags at the top of the AJAX return
	resp = zenario.splitFlagsFromMessage(resp);
	flags = resp.flags;
	
	//Allow modules to reject the AJAX reload and request an entire page reload
	forceReloadHref = flags.FORCE_PAGE_RELOAD;
	
	//Don't try and do an AJAX reload if text has <script> or <styles> tags in
		//However, if this was a POST submission, ignore this check as we don't want to re-submit the post data
	if (!isFormPost && resp.responseText.match(/<(link|script|style)/i)) {
		forceReloadHref = zenario.linkToItem(zenario.cID, zenario.cType, additionalRequests);
	}
	
	if (forceReloadHref) {
		//Update the beingEdited flag for this slot
		if (zenario.slots[slotName]) {
			zenario.slots[slotName].beingEdited = beingEdited;
		}
		
		if (zenario.inAdminMode && (zenarioA.floatingBoxOpen || zenarioA.checkSlotsBeingEdited())) {
			resp.responseText =
				'<div><em>(' +
					zenarioA.phrase.pluginNeedsReload.replace('[[href]]', htmlspecialchars(forceReloadHref)) +
				')</em></div>' +
				resp.responseText;
		} else {
			document.location.href = forceReloadHref;
			return;
		}
	}
	
	if (flags.PAGE_TITLE) {
		document.title = flags.PAGE_TITLE;
	}
	
	//Watch out for the "In Edit Mode" tag from modules in their edit modes
	beingEdited = flags.IN_EDIT_MODE;
	isVersionControlled = flags.WIREFRAME;
	instanceId = flags.INSTANCE_ID;
	slideId = flags.TAB_ID;
	level = 1*flags.LEVEL;
	
	domSlot.className = 'zenario_slot ' + (flags.CSS_CLASS || '');
	
	//Add any libraries needed
	var libInfo, i = 1;
	while ((libInfo = flags['JS_LIB' + i++]) && (libInfo = JSON.parse(libInfo)))	 {
		(function(script, stylesheet) {
			var cb = new zenario.callback;
			ocb.add(cb);
			
			zenario.loadLibrary(
				URLBasePath + 'zenario/' + script,
				function() {
					zenario.jsLibs[script] = true;
					cb.call();
				},
				zenario.jsLibs[script],
				stylesheet && (URLBasePath + 'zenario/' + stylesheet)
			);
		})(libInfo[0], libInfo[1]);
	}
	
	
	ocb.after(function() {
	
		//Allow modules to name JavaScript function(s) they wish to be run
		i = 1;
		while (script = flags['SCRIPT' + i++]) {
			scriptsToRun.push(script);
		}
		i = 1;
		while (script = flags['SCRIPT_BEFORE' + i++]) {
			scriptsToRunBefore.push(script);
		}
	
		if (scrollToTopOfSlot === undefined) {
			scrollToTopOfSlot = flags.SCROLL_TO_TOP;
		}
	
		//Allow modules to open themselves in a floating box
		if (showInFloatingBox = flags.SHOW_IN_FLOATING_BOX) {
			floatingBoxExtraParams = (flags.FLOATING_BOX_PARAMS && JSON.parse(flags.FLOATING_BOX_PARAMS)) || {};
		}
	
		//Stop any animations currently on the slot
		$(domSlot).stop(true, true).animate({opacity: 1}, 200, function() {
			if (zenario.browserIsIE()) {
				this.style.removeAttribute('filter');
			}
		
			//Fix a problem where the fading in/out can leave an inline style 
			this.style.opacity = '';
		});
	
	
		if (showInFloatingBox) {
			zenario.colorboxOpen = slotName;
			zenario.colorboxFormChanged = false;
			var params = {
				transition: 'none',
				html: resp.responseText,
				onOpen: => {
					var cb = get('colorbox');
					cb.className = domSlot.className;
					$(cb).hide().fadeIn();
				},
				onComplete: => {
					zenario.addJQueryElements('#colorbox ');
				
					//Allow modules to call JavaScript function(s) after they have been refreshed
					foreach (scriptsToRun as var script) {
						if (zenario.slots[slotName]) {
							zenario.callScript(scriptsToRun[script], zenario.slots[slotName].moduleClassName);
						}
					}
				
					$('#cboxLoadedContent').find('form').on('keyup change', 'input, select, textarea', function() {
						zenario.colorboxFormChanged = true;
					});
				},
				onClosed: => {
					get('colorbox').className = '';
					zenario.colorboxOpen = false;
				}
			};
			// Add any extra parsed parameters
			for (var i in floatingBoxExtraParams) {
				if (!$.inArray(i, ['closeConfirmMessage', 'alwaysShowConfirmMessage']) && (params[i] === undefined)) {
					params[i] = floatingBoxExtraParams[i];
				}
			}
			$.colorbox(params);
		
			zenario.colorboxAlwaysShowConfirmMessage = floatingBoxExtraParams.alwaysShowConfirmMessage;
		
			if (floatingBoxExtraParams.closeConfirmMessage && !zenario.colorboxCloseConfirmMessageSet) {
				zenario.colorboxCloseConfirmMessageSet = true;
				var originalClose = $.colorbox.close;
				$.colorbox.close = function() {
					var response;
					if (zenario.colorboxFormChanged || zenario.colorboxAlwaysShowConfirmMessage) {
						response = confirm(floatingBoxExtraParams.closeConfirmMessage);
						if(!response){
							return; // Do nothing.
						}
					}
					zenario.colorboxFormChanged = false;
					originalClose();
				};
			}
		
			zenario.resizeColorbox();
	
		} else {
			if (zenario.colorboxOpen) {
				$.colorbox.close();
				zenario.colorboxOpen = false;
			}
		
			if (!window.zenario_inIframe && zenario.inAdminMode) {
				//Admins need some more tasks, other than just changing the innerHTML
				zenarioA.replacePluginSlot(slotName, instanceId, level, slideId, resp, scriptsToRunBefore);
			} else {
				foreach (scriptsToRunBefore as var script) {
					if (zenario.slots[slotName]) {
						zenario.callScript(scriptsToRunBefore[script], zenario.slots[slotName].moduleClassName);
					}
				}
			
				//If we're not in admin mode, just refresh the slot's innerHTML
				zenario.slot([[slotName, instanceId, zenario.slots[slotName].moduleId, level, slideId, undefined, beingEdited, isVersionControlled]]);
				domSlot.innerHTML = resp.responseText;
			}
		
			//Allow modules to call JavaScript function(s) after they have been refreshed
			foreach (scriptsToRun as var script) {
				if (zenario.slots[slotName]) {
					zenario.callScript(scriptsToRun[script], zenario.slots[slotName].moduleClassName);
				}
			}
		
			zenario.addJQueryElements('#' + containerId + ' ');
		
		
			if (scrollToTopOfSlot && !zenarioAB.isOpen) {
				//Scroll to the top of a slot if needed
				zenario.scrollToSlotTop(slotName, true);
			}
		
		
			//Attempt to record the current AJAX reload in the URL bar
			if (recordInURL) {
				zenario.recordRequestsInURL(slotName, additionalRequests);
			}
		}
	
		if (zenario.inAdminMode) {
			zenarioA.checkSlotsBeingEdited();
			zenarioA.scanHyperlinksAndDisplayStatus(containerId);
		}
	
		zenario.sendSignal('eventSlotUpdated', {slotName: slotName, instanceId: instanceId, flags: flags}, zenario.inAdminMode);
	});
	
	ocb.poke();
};

zenario.recordRequestsInURL = function(slotName, requests) {
	
	slotName = zenario.getSlotnameFromEl(slotName);
	requests = zenario.urlRequest(requests);
			
	//If the browser support HTML 5, we can use URL rewriting
	if (window.history && history.pushState) {
		
		//If this is the first AJAX request we've had, be sure to save the initial load-state of the page
		if (!zenario.previouslyPushedState) {
			zenario.previouslyPushedState = true;
			
			//Get variables from the URL
			var url = document.location.href,
				qMark = url.indexOf('?'),
				initialRequests = '';
			
			if (qMark != -1) {
				initialRequests = url.substr(qMark+1);
			}
			
			//Replace the current state - don't change the URL or the title,
			//but save the slot name that is being changed, and the initial request
			history.replaceState({slotName: slotName, request: initialRequests}, document.title, document.location.href);
		}
		
		//Work out the new URL to the page, then place this along with the slot name and requests into the history
		history.pushState({slotName: slotName, request: requests}, document.title, zenario.linkToItem(zenario.cID, zenario.cType, requests));
	
	//Old functionality using hashes in the URL, for browsers that don't support HTML 5.
	//And by that I mean Internet Explorer.
	} else {
		if (slotName == zenario.mainSlot) {
			document.location.hash = '!' + requests.substr(1);
		} else {
			document.location.hash = slotName + '!' + requests.substr(1);
		}
	}
	
	zenario.currentHash = document.location.hash;
};


zenario.callScript = function(script, className, secondTime) {
	var functionName,
		encapObject;
	
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
			//If the library wasn't on the page, but there are scripts still loading,
			//then try again after they have loaded
			if (!secondTime && !_.isEmpty(loadingScripts)) {
				
				scriptsLoadedCallback.after( => {
					zenario.callScript(script, className, true);
				});
			}
			
			return;
		}
		
		encapObject = window[className];
		
		encapObject[functionName].apply(encapObject, script);
	}
};




//Apply compilation macros in a microtemplate
//(Note that this is the same logic from zenario/js/js_minify.shell.php)
zenario.applyCompilationMacros = function(code) {
	
	var forLoop = 'for ($2$3 in $1) { if (!zenario.has($1, $3)) continue;',
		funct = 'function ($1) {';
	
	//"foreach" is a macro for "for .. in ... hasOwnProperty"
	return code.replace(
		/\bforeach\b\s*\(\s*(.+?)\s*\bas\b\s*(\bvar\b |)\s*(.+?)\s*\=\>\s*(\bvar\b |)\s*(.+?)\s*\)\s*\{/gi,
		forLoop + ' $4 $5 = $1[$3];'
	).replace(
		/\bforeach\b\s*\(\s*(.+?)\s*\bas\b\s*(\bvar\b |)\s*(.+?)\s*\)\s*\{/gi,
		forLoop
	
	//Some babel style function definitions
	).replace(
		/\(([\w\s,]*)\)\s*\=\>\s*\{/g,
		funct
	).replace(
		/(\b\w+\b)\s*\=\>\s*\{/g,
		funct
	
	//Catch the case where whitespace would appear in the middle of a switch statement and cause an error
	).replace(
		/(\{|\:|\bbreak\s*\;)\s*%[\>\}]\s*[<\{]%\s*(case|default|\})/g,
		'$1 $2'
	);
};

//Define a standard pool of micro-templates
zenario.microTemplates = {};

//A wrapper function to the underscore.js function's template library
window.microTemplate =
zenario.microTemplate = (template, data, filter, microTemplates, i) => {
	
	var j, l, html;
	
	//Have the option to use a different pool of micro-templates than usual
	microTemplates = microTemplates || zenario.microTemplates;
	
	
	if (template === undefined || !data) {
		return '';
	
	} else if (_.isArray(data)) {
		l = data.length,
		html = '';
		for (j = 0; j < l; ++j) {
			if (filter === undefined || filter(data[j])) {
				html += zenario.microTemplate(template, data[j], undefined, microTemplates, j);
			}
		}
		return html;
	}
	
	if (data.i === undefined && i !== undefined) {
		data.i = 1*i;
	}
	
	if (template.length < 255 && microTemplates[template]) {
		//Named templates from one of the js/microtemplate directories
		//The template name is taken from the filename
		if (typeof microTemplates[template] == 'string') {
			//Parse and compile the microtemplate if this hasn't already happened
			microTemplates[template] = zenario.generateMicroTemplate(microTemplates[template], template);
		}
	
		return microTemplates[template](data);

	} else {
		//Custom/one-off templates
		var checksum = 'microtemplate_' + hex_md5(template);
		
		if (microTemplates[checksum] === undefined) {
			microTemplates[checksum] = template;
		}
		
		return zenario.microTemplate(checksum, data, filter, microTemplates, i);
	}
};

zenario.drawMicroTemplate = function(htmlId, template, data, filter, microTemplates) {
	var dom;
	if (dom = get(htmlId)) {
		dom.innerHTML = zenario.microTemplate(template, data, filter, microTemplates);
		zenario.addJQueryElements('#' + htmlId + ' ');
	}
};

zenario.generateMicroTemplate = function(source, name) {
	
	var microTemplate,
		tmp = $.extend({}, _.templateSettings, true);
	
	_.templateSettings = zenario.mtSettings;
	
	try {
		microTemplate = _.template(zenario.applyCompilationMacros(source));
		_.templateSettings = tmp;
		
		return microTemplate;
	
	} catch (e) {
		_.templateSettings = tmp;
		
		console.log('Error in microtemplate' + (name? ' ' + name : '') + ': \n\n' + microTemplate);
		throw e;
	}
};

zenario.unfun = function(text) {
	if (_.isFunction(text)) {
		return text();
	} else {
		return text;
	}
};

zenario.num = function(text) {
	if (typeof text == 'function') {
		return 1*text();
	} else {
		return 1*text;
	}
};



var scrollingContexts = {};

zenario.disableScrolling = function(context) {
	context = context || 'default';
	
	if (!scrollingContexts[context]) {
		$('body').css({
			overflow: 'hidden'
		}); 

		scrollingContexts[context || 'default'] = true;
	}
};

zenario.enableScrolling = function(context) {
	context = context || 'default';
	
	if (scrollingContexts[context]) {
		delete scrollingContexts[context];

		if (_.isEmpty(scrollingContexts)) {
			$('body').css({
				overflow: 'auto'
			});
		}
	}
};



zenario.resizeColorbox = function() {
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

//Check whether a DOM element is still in the document, or if it has been removed
zenario.inDoc = function(el) {
	if (document.contains) {
		return document.contains(el);
	} else {
		return !!$(el).closest('body').length;
	}
};

//Add jQuery elements automatically by class name
zenario.addJQueryElements = function(path, adminFacing) {
	
	if (!path || path === undefined) {
		path = '';
	}
	
	//Initiate the jQuery plugin for lazy-loading images
	$("img.lazy").Lazy();
	
	//Fancybox/Lightbox replacement
	$(path + "a[rel^='colorbox'], a[rel^='lightbox']").colorbox({
		title: function() { return $(this).attr('data-box-title'); },
		maxWidth: '100%',
		maxHeight: '100%'
	});
	
	$(path + "a[rel^='colorbox_no_arrows']").colorbox({
		title: function() { return $(this).attr('data-box-title'); },
		maxWidth: '100%',
		maxHeight: '100%',
		rel: false
	});
	
	if (zenario.browserIsIE(9)) {
		$(path + 'input[placeholder]').placeholder();
		$(path + 'textarea[placeholder]').placeholder();
	}
	
	//jQuery datepickers (plugin frameworks version)
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
	if (adminFacing && zenario.inAdminMode) {
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
		
		if (zenario.inAdminMode) {
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




var tooltipContent = function() {
		var title = this.title,
			pos = this.title.indexOf('|');
	
		if (pos != -1) {
			return '<h3>' + this.title.substr(0, pos) + '</h3><p>' + this.title.substr(pos+1) + '</p>';
		} else {
			return title;
		}
	};


//Wrapper function for jQuery tooltips
zenario.tooltips = function(target, options) {
	zenario.closeTooltip();
	if (target === undefined) {
		//Add tooltips to an entire page after it has been loaded
		zenario.tooltips('a[title]', options);
		zenario.tooltips('div[title]', options);
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
			options.content = tooltipContent;
		}
		
		$('.ui-tooltip').remove();
		
		$(target).each(function(i, el) {
			var thisOptions,
				$el = $(el),
				tooltipOptions = $el.attr('data-tooltip-options');
			
			if (tooltipOptions) {
				try {
					thisOptions = zenario.clone(options, JSON.parse(tooltipOptions));
				} catch (error) {
					try {
						thisOptions = zenario.clone(options, JSON.parse(zenario.fixJSON(tooltipOptions)));
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
zenario.addPluginJavaScript = function(moduleId, callback) {
	
	//Work out the path from the plugin name and the swatch name
	filePath = 'zenario/js/plugin.wrapper.js.php?ids=' + moduleId + '&v=' + zenarioCSSJSVersionNumber;
	
	if (zenario.inAdminMode) {
		filePath += '&admin=1';
	}
	
	return zenario.loadLibrary(URLBasePath + filePath, callback);
};


zenario.captcha = function(publicKey, divId, hideAudio) {
	if (hideAudio) {
		RecaptchaOptions.callback ==> { zenario.captchaHideAudio(); }
	}
	
	if (!window.Recaptcha) {
		$.getScript(
			zenario.httpOrhttps() + 'www.google.com/recaptcha/api/js/recaptcha_ajax.js',
			=> {
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

var $copy = false;
zenario.canCopy = function(text) {
	if (!document.execCommand
	 || !document.queryCommandSupported('copy')) {
		return false;
	}
	if (!$copy) {
		$("body").append($copy = $('<textarea style="position:absolute;left:-999px;top:-999px;">'));
	}
	return !!$copy[0].select;
};
zenario.copy = function(text) {
	
	var textarea,
		rv = false;
	
	if (zenario.canCopy()) {
		
		if (typeof text == 'object') {
			text.focus();
			text.select();
			rv = document.execCommand('copy');
			text.setSelectionRange(0, 0);
			text.blur();
		
		} else {
	
			var textarea = $copy[0];
	
			if (textarea) {
				textarea.value = text;
				textarea.select();
				rv = document.execCommand('copy');
			}
		}
		
		return true;
	}
	
	return false;
};


//Local Storage
zenario.rev = '';
zenario.lastLoadNum = false;

//Warning: zenario.checkSessionStorage() and zenario.checkLocalStorage() use are deprecated!
zenario.checkSessionStorage = function(url, requests, isObject, loadNum) {
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
	
	//Clear the storage if the data is out of date
	zenario.checkDataRevisionNumber(false);
	
	
	//Check if the requested item is actually in the local storage
	var name = zenario.userId + '_' + zenario.adminId + '_' + url;
	var store = zenario.sGetItem(true, name, isObject);
	
	if (!store) {
		return false;
	} else {
		return store;
	}
};

zenario.setSessionStorage = function(merge, url, requests, isObject) {
	
	//Don't do anything for IE 6 and 7
	if (zenario.browserIsIE(7)) {
		return false;
	}
	
	if (!zenario.dataRev()) {
		zenario.checkDataRevisionNumber(false);
	}
	
	if (zenario.dataRev() && merge) {
		url += zenario.urlRequest(requests);
		var name = zenario.userId + '_' + zenario.adminId + '_' + url;
		zenario.sSetItem(true, name, merge, isObject);
	}
};

zenario.checkDataRevisionNumber = function(async, cb) {
	
	if (zenarioAB.tuix && zenarioAB.tuix.path == 'install') {
		if (cb) cb();
		return;
	}
	
	var url = URLBasePath + 'zenario/has_database_changed_and_is_cache_out_of_date.php';
	
	if (async) {
		//zenario.ajax(url, post, json, useCache, retry, continueAnyway, settings, timeout, AJAXErrorHandler, onRetry, onCancel)
		zenario.ajax(url, true, undefined, undefined, true, false, undefined, 2500).after(function(rev) {
			zenario.outdateCachedData(rev);
			if (cb) cb();
		});
	} else {
		zenario.outdateCachedData(zenario.nonAsyncAJAX(url, true));
	}
};

zenario.dataRev = function() {
	return zenario.rev = zenario.rev || zenario.sGetItem(true, 'rev');
};

zenario.outdateCachedData = function(rev) {
	rev = rev || '';
	
	if (zenario.dataRev() != rev) {
		zenario.rev = rev;
		zenario.sClear(true);
		zenario.sSetItem(true, 'rev', rev);
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



zenario.init = function(
	zenarioCSSJSVersionNumber,
	userId,
	langId,
	recaptchaTheme,
	datePickerFormat,
	indexDotPHP,
	canSetCookie,
	
	equivId,
	cID,
	cType,
	skinId
) {

	window.zenarioCSSJSVersionNumber = zenarioCSSJSVersionNumber;
	
	window.RecaptchaOptions = {
		lang: langId.substr(0, 2),
		theme: recaptchaTheme
	};
	
	zenario.userId = userId;
	zenario.langId = langId;
	zenario.dpf = datePickerFormat;
	zenario.indexDotPHP = indexDotPHP;
	zenario.canSetCookie = canSetCookie;

	zenario.equivId = equivId || undefined;
	zenario.cID = cID || undefined;
	zenario.cType = cType || undefined;
	zenario.skinId = skinId || undefined;
	
	


	//Include JSON library, if it's not defined natively
	//Note I am loading this syncronously, which is deprecated on modern browsers,
	//but it's not deprecated on any browser old enough not to have the JSON library built in as standard!
	if (!window.JSON) {
		zenario.loadLibrary(URLBasePath + 'zenario/libraries/public_domain/json/json2.min.js', false);
	}

	//Add the Math.log10() function, if it's not defined natively
	if (!Math.log10) {
		Math.log10 = function(n) {
			return Math.log(n) / Math.log(10);
		};
	}

	//Add the starts-with function if it's not defined natively
	if (!String.prototype.startsWith) {
		String.prototype.startsWith = function(prefix) {
			return this.indexOf(prefix) === 0;
		};
	}


	//Check to see whether we have a grid with a min-width, and whether the browser supports checking the min-width
	var minWidth = window.matchMedia && zenarioGrid.responsive && 1 * zenarioGrid.minWidth,
		wasAResize = false;

	zenario.mobile = false;
	zenario.desktop = true;

	//Add a call to enquire to switch the CSS class on the body between mobile and desktop
	//whenever the visitor resizes their window
	if (minWidth) {
		enquire.register(
			'screen and (min-width: ' + minWidth + 'px)',
			{
				match : => {
					zenarioSBC(zenario.mobile = false, 'mobile', 'desktop');
					zenario.desktop = true;
				
					if (wasAResize) {
						zenario.sendSignal('eventResizedToDesktop');
					}
					wasAResize = true;
				},
				doesntMatch : => {
					zenarioSBC(zenario.mobile = true, 'mobile', 'desktop');
					zenario.desktop = false;
				
					if (wasAResize) {
						zenario.sendSignal('eventResizedToMobile');
					}
					wasAResize = true;
				}
			}
		);
	}
};


//Functions for TinyMCE

//A hack to try and remove some of the bad/repeated html that TinyMCE sometimes generates,
//e.g. duplicate id/style tags
zenario.tinyMCEGetContent = function(editor) {
	var html,
		$html = $('<div/>').html(editor.getContent());
	
	//Bugfix for task #9380 "Problem where some of the CMS' <div>s had been entered into a WYSIWYG Editor"
	$html.find('[id^="plgslt_"]').each(function(i, el) {
		 $(el).removeAttr('id class style');
	});
	
	//Remove any of the link-status icons that get in the HTML
	zenario.removeLinkStatus($html);
	
	html = $html.html();
	
	//Fix for request #2908 "Bug with WYSIWYG Editor: You can't delete an empty <h1> tag"
	if (html == '<h1>&nbsp;</h1>') {
		html = '';
	}
	
	return html;
};

zenario.removeLinkStatus = function($el) {
	$el.find('del.zenario_link_status').remove();
};

//Functions for browser fullscreen

zenario.fullScreenChangeEvent = 'fullscreenchange msfullscreenchange mozfullscreenchange webkitfullscreenchange';

zenario.isFullScreenAvailable = function(element) {
	return !!(element.requestFullscreen 
		|| element.mozRequestFullScreen 
		|| element.webkitRequestFullScreen
		|| element.msRequestFullscreen
	);
};

zenario.isFullScreen = function() {
	return !!(document.fullscreenElement 
		|| document.mozFullScreenElement 
		|| document.webkitFullscreenElement 
		|| document.msFullscreenElement
	);
};

zenario.enableFullScreen = function(element) {
	if (element.requestFullscreen) {
		element.requestFullscreen();
	} else if (element.msRequestFullscreen) {
		element.msRequestFullscreen();
	} else if (element.mozRequestFullScreen) {
		element.mozRequestFullScreen();
	} else if (element.webkitRequestFullscreen) {
		element.webkitRequestFullscreen(Element.ALLOW_KEYBOARD_INPUT);
	}
};

zenario.exitFullScreen = function() {
	if (document.exitFullscreen) {
		document.exitFullscreen();
	} else if (document.msExitFullscreen) {
		document.msExitFullscreen();
	} else if (document.mozCancelFullScreen) {
		document.mozCancelFullScreen();
	} else if (document.webkitExitFullscreen) {
		document.webkitExitFullscreen();
	}
};


//Replace the zenarioSBC() function in body.js (which was specifically written for a small filesize)
//with a jQuery-based version (which should be a little more efficient)
window.zenarioSBC = function(condition, metClassName, notMetClassName) {
	if (condition) {
		$('body').removeClass(notMetClassName).addClass(metClassName);
	} else {
		$('body').removeClass(metClassName).addClass(notMetClassName);
	}
};








var shrtNms = zenario.shrtNms = function(lib, libName) {
	var funs = [],
		f, fun,
		newNames = {},
		newName, name;
	
	foreach (lib as name => fun) {
		if (name != 'has'
		 && name != 'lib'
		 && typeof fun == 'function') {
			funs.push(name);
		}
	}
	
	funs.sort();
	
	foreach (funs as f => name) {
		newName = '_' + name.substr(0, 1) + name.substr(1).replace(/([A-Z])[A-Z]*([A-Z][a-z])/g, '$1$2').replace(/([A-Z])[A-Z]+$/g, '$1').replace(/[a-z]/g, '');
		
		if (lib[newName] !== undefined) {
			newName = '_' + name.substr(0, 1) + name.substr(1).replace(/([A-Z][A-Z])[A-Z]*([A-Z][a-z])/g, '$1$2').replace(/([A-Z][A-Z])[A-Z]+$/g, '$1').replace(/([a-z])[a-z]*/g, '$1');
		}
		
		if (lib[newName] === undefined) {
			lib[newName] = lib[name];
			
			//This code would list out the shortnames,
			//so they can be copy-pasted into zenario/includes/js_minify.inc.php
			//if/when there are changes/new functions
			/*
			if (newName.length < name.length) {
				libName = libName || lib.globalName;
				console.log("'" + libName + "." + name + "(' => '" + libName + "." + newName + "(',");
			}
			*/
		}
	}
};

shrtNms(_, '_');
shrtNms(zenario);


});
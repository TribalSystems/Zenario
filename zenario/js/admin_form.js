/*
 * Copyright (c) 2024, Tribal Limited
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
	The code here is not the code you see in your browser. Before thus file is downloaded:
	
		1. Compilation macros are applied (e.g. "foreach" is a macro for "for .. in ... hasOwnProperty").
		2. It is minified (e.g. using Google Closure Compiler).
		3. It may be wrapped togther with other files (thus is to reduce the number of http requests on a page).
	
	For more information, see js_minify.shell.php for steps (1) and (2), and admin.wrapper.js.php for step (3).
*/

zenario.lib(function(
	undefined,
	URLBasePath,
	document, window, windowOpener, windowParent,
	zenario, zenarioA, zenarioT, zenarioAB, zenarioAT, zenarioO,
	encodeURIComponent, defined, engToBoolean, get, htmlspecialchars, jsEscape, phrase,
	extensionOf, methodsOf, has,
	zenarioAF
) {
	"use strict";

	var methods = methodsOf(zenarioAF);







methods.microTemplate = function(template, data, filter) {

	var needsTidying = zenario.addLibPointers(data, thus),
		html = zenarioT.microTemplate(template, data, filter);
	
	if (needsTidying) {
		zenario.tidyLibPointers(data);
	}

	return html;
};

methods.start = function(path, key, tab, values) {	
	thus.tabHidden = true;
	thus.differentTab = false;
	
	thus.path = path || '';
	
	thus.tuix = {};
		//Backwards compatability for any old code
		thus.focus = thus.tuix;
	
	thus.key = key || {};
	thus.tab = tab;
	thus.shownTab = false;
	thus.url = thus.getURL('start');
	
	thus.retryAJAX(
		thus.url,
		{_fill: true, _values: values? JSON.stringify(values) : ''},
		true,
		function(data) {
			if (thus.load(data)) {
				if (thus.tab) {
					if (!thus.tuix.disable_selecting_tab_from_url) {
						thus.tuix.tab = thus.tab;
					}
				}
			
				delete thus.key;
				delete thus.tab;
			
				thus.sortTabs();
				thus.initFields();
				thus.draw();
			} else {
				thus.close(true);
			}
		},
		'loading',
		function() {
			thus.close(true);
		}
	);
};

methods.load = function(data) {
	thus.loaded = true;
	
	if (data.toast
	 && zenarioA.toast) {
		zenarioA.toast(data.toast);
	}
	
	if (thus.callFunctionOnEditors('isDirty')) {
		thus.markAsChanged();
	}
	
	thus.callFunctionOnEditors('remove');
	thus.setData(data);
	
	if (!thus.tuix || (!thus.tuix.tabs && !defined(thus.tuix.go_to_url))) {
		zenarioA.showMessage(phrase.couldNotOpenBox, true, 'error');
		return false;
	}
	
	return true;
};


methods.animateInTab = function(html, cb, $shakeme) {
	var needToShake = (!defined(thus.tuix.shake)? thus.differentTab && thus.errorOnTab(thus.tuix.tab) : engToBoolean(thus.tuix.shake));

	//If this is the current tab...
	if (thus.shownTab == thus.tuix.tab) {
	
		//...shake the tab if there are errors...
		if (needToShake
		//Bugfix - dont attempt to shake if there are any iframes on the old page
		 && (!$shakeme.find('iframe').length)) {
			$shakeme.effect({
				effect: 'bounce',
				duration: 125,
				direction: 'right',
				times: 2,
				distance: 5,
				mode: 'effect',
				complete: function() {
					thus.insertHTML(html, cb);
					thus.addJQueryElementsToTab();
					
					$('#zenario_abtab').clearQueue().show().animate({opacity: 1}, 75, function() {
						if (zenario.browserIsIE()) {
							this.style.removeAttribute('filter');
						}
						
						thus.focusFirstField();
					});
					
					thus.hideShowFields();
				}
			});
			
		//...otherwise don't show any animation
		} else {
			//Fade in a tab if it was hidden
			//(It's probably not hidden but just in case)
			thus.insertHTML(html, cb);
			thus.addJQueryElementsToTab();
			
			var lastScrollTop = thus.lastScrollTop;
			
			$('#zenario_abtab').clearQueue().show().animate({opacity: 1}, 150, function() {
				if (zenario.browserIsIE()) {
					this.style.removeAttribute('filter');
				}
				
				if (!needToShake
				 && defined(lastScrollTop)) {
				} else {
					thus.focusFirstField();
				}
			});
			
			//Attempt to preserve the previous scroll height if this is the same tab as last time
			$('#zenario_fbAdminInner').scrollTop(lastScrollTop);
			
			thus.hideShowFields();
		}
		
		delete thus.tuix.shake;
	
	//A new/different tab - fade it in
	} else {
		thus.insertHTML(html, cb, true);
		thus.addJQueryElementsToTab();
		
		$('#zenario_abtab').clearQueue().show().animate({opacity: 1}, 150, function() {
			if (zenario.browserIsIE()) {
				this.style.removeAttribute('filter');
			}
			
			thus.focusFirstField();
		});
	}
};

//Add any special jQuery objects to the tab
methods.addJQueryElementsToTab = function() {
	thus.addJQueryElements('#zenario_abtab');
};


//In admin mode, use the standard admin-mode version when copy-pasting
methods.copyField = function(copyButtonEl, fieldId) {
	var fieldValue = thus.readField(fieldId);
	
	if (defined(fieldValue)) {
		zenarioA.copy(fieldValue);
	}	
};



}, zenarioAF);
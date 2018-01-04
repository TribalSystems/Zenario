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
	
	For more information, see js_minify.shell.php for steps (1) and (2), and admin.wrapper.js.php for step (3).
*/

zenario.lib(function(
	undefined,
	URLBasePath,
	document, window, windowOpener, windowParent,
	zenario, zenarioA, zenarioT, zenarioAB, zenarioAT, zenarioO,
	encodeURIComponent, get, engToBoolean, htmlspecialchars, jsEscape, phrase,
	extensionOf, methodsOf, has,
	zenarioAF, extraVar2, s$s
) {
	"use strict";

	var methods = methodsOf(zenarioAF);







methods.start = function(path, key, tab, values) {
	var that = this;
	
	this.tabHidden = true;
	this.differentTab = false;
	
	this.path = path || '';
	
	this.tuix = {};
		//Backwards compatability for any old code
		this.focus = this.tuix;
	
	this.key = key || {};
	this.tab = tab;
	this.shownTab = false;
	this.url = this.getURL('start');
	
	that.retryAJAX(
		that.url,
		{_fill: true, _values: values? JSON.stringify(values) : ''},
		true,
		function(data) {
			if (that.load(data)) {
				if (that.tab) {
					that.tuix.tab = that.tab;
				}
			
				delete that.key;
				delete that.tab;
			
				that.sortTabs();
				that.initFields();
				that.draw();
			} else {
				that.close(true);
			}
		},
		'loading',
		function() {
			that.close(true);
		}
	);
};

methods.load = function(data) {
	this.loaded = true;
	
	if (data.toast
	 && zenarioA.toast) {
		zenarioA.toast(data.toast);
	}
	
	if (this.callFunctionOnEditors('isDirty')) {
		this.markAsChanged();
	}
	
	this.callFunctionOnEditors('remove');
	this.setData(data);
	
	if (!this.tuix || (!this.tuix.tabs && this.tuix.go_to_url === undefined)) {
		zenarioA.showMessage(phrase.couldNotOpenBox, true, 'error');
		return false;
	}
	
	return true;
};


methods.animateInTab = function(html, cb, $shakeme) {
	var that = this,
		needToShake = (this.tuix.shake === undefined? this.differentTab && this.errorOnTab(this.tuix.tab) : engToBoolean(this.tuix.shake));

	//If this is the current tab...
	if (this.shownTab == this.tuix.tab) {
	
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
					that.insertHTML(html, cb);
					
					$('#zenario_abtab').clearQueue().show().animate({opacity: 1}, 75, function() {
						if (zenario.browserIsIE()) {
							this.style.removeAttribute('filter');
						}
						
						that.addJQueryElementsToTabAndFocusFirstField();
					});
					
					that.hideShowFields();
				}
			});
			
		//...otherwise don't show any animation
		} else {
			//Fade in a tab if it was hidden
			//(It's probably not hidden but just in case)
			this.insertHTML(html, cb);
			
			var lastScrollTop = this.lastScrollTop;
			
			$('#zenario_abtab').clearQueue().show().animate({opacity: 1}, 150, function() {
				if (zenario.browserIsIE()) {
					this.style.removeAttribute('filter');
				}
				
				if (!needToShake
				 && lastScrollTop !== undefined) {
					that.addJQueryElementsToTab();
				} else {
					that.addJQueryElementsToTabAndFocusFirstField();
				}
			});
			
			//Attempt to preserve the previous scroll height if this is the same tab as last time
			$('#zenario_fbAdminInner').scrollTop(lastScrollTop);
			
			this.hideShowFields();
		}
		
		delete this.tuix.shake;
	
	//A new/different tab - fade it in
	} else {
		this.insertHTML(html, cb, true);
		$('#zenario_abtab').clearQueue().show().animate({opacity: 1}, 150, function() {
			if (zenario.browserIsIE()) {
				this.style.removeAttribute('filter');
			}
			
			that.addJQueryElementsToTabAndFocusFirstField();
		});
	}
};






}, zenarioAF);
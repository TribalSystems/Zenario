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
	
	For more information, see js_minify.shell.php for steps (1) and (2), and admin.wrapper.js.php for step (3).
*/


zenario.lib(function(
	undefined,
	URLBasePath,
	document, window, windowOpener, windowParent,
	zenario, zenarioA, zenarioAB, zenarioAT, zenarioO,
	get, engToBoolean, htmlspecialchars, ifNull, jsEscape, phrase,
	extensionOf, methodsOf,
	zenarioAF
) {
	"use strict";
	
	var zenarioW = window.zenarioW = new zenarioAF();
	zenarioW.init('zenarioW');
	zenarioW.mtPrefix = 'zenario_welcome';


zenarioW.draw = function() {
	if (zenarioW.tuix) {
		
		if (zenarioW.tuix.go_to_url !== undefined) {
			document.location.href = zenario.addBasePath(zenarioW.tuix.go_to_url);
			return;
		}
	}
	
	if (zenarioW.loaded && zenarioW.tabHidden) {
		zenarioW.draw2();
	}
};


zenarioW.draw2 = function() {
	zenarioA.nowDoingSomething(false);
	$('#zenario_abtab input').add('#zenario_abtab select').add('#zenario_abtab textarea').clearQueue();
	
	
	var cb = new zenario.callback,
		html = zenarioW.drawFields(cb);
	
	
	//On the Admin Login screen, drop in the tab if this is the first time we're showing the box
	if (zenarioW.shownTab === false) {
		zenarioW.insertHTML(html, cb);
		
		$('#welcome').show({effect: 'drop', direction: 'up', duration: 300, complete: function() {
			zenarioW.addJQueryElementsToTabAndFocusFirstField();
		}});
		zenario.addJQueryElements('#zenario_abtab ', true);	
	
	} else {
		zenarioW.animateInTab(html, cb, $('#welcome'));
	}
	document.title = zenarioW.tuix.title;
	zenarioW.shownTab = zenarioW.tuix.tab;
	delete zenarioW.lastScrollTop;
};


//Get a URL needed for an AJAX request
zenarioW.returnAJAXURL = function() {
	return URLBasePath + 'zenario/wizard_ajax.php?' + zenario.urlRequest(zenarioW.getRequest).substr(1);
};	



//Get a URL needed for an AJAX request
zenarioW.getKey = function() {
	return {};
};	




},
	zenarioAF
);
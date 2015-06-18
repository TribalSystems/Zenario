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
	
	For more information, see js_minify.shell.php for steps (1) and (2), and inc-organizer.js.php for step (3).
*/

zenario.lib(function(
	undefined,
	URLBasePath,
	document, window, windowOpener, windowParent,
	zenario, zenarioA, zenarioAB, zenarioAT, zenarioO,
	get, engToBoolean, htmlspecialchars, ifNull, jsEscape, phrase,
	extensionOf, methodsOf,
	panelTypes
) {
	"use strict";

//Note: extensionOf() and methodsOf() are our shortcut functions for class extension in JavaScript.
	//extensionOf() creates a new class (optionally as an extension of another class).
	//methodsOf() allows you to get to the methods of a class.

var methods = methodsOf(
	panelTypes.google_map_or_list = extensionOf(panelTypes.grid_or_list)
);

methods.parentMethods = function() {
	if (this.altView) {
		return methodsOf(panelTypes.google_map);
	} else {
		return methodsOf(panelTypes.list);
	}
};

methods.init = function() {
	methodsOf(panelTypes.google_map).init.call(this);
	methodsOf(panelTypes.grid_or_list).init.call(this);
};

methods.updatePanelAfterChangingView = function($header, $panel, $footer) {
	//zenarioO.reload();
	zenarioO.goToPage(1);
};



methods.returnPageSize = function() {
	return this.parentMethods().returnPageSize.apply(this, arguments);
};

methods.returnPanelTitle = function() {
	return this.parentMethods().returnPanelTitle.apply(this, arguments);
};

methods.showPanel = function($header, $panel, $footer) {
	var that = this;
	
	//hack to fix a bug I can't work out how to fix properly right now - Chris
	setTimeout(function() {
		that.parentMethods().showPanel.call(that, $header, $panel, $footer);
		that.setSwitchButton($header, $panel, $footer);
	}, 0);
};

methods.makeOpenAdminBoxCallback = function() {
	return this.parentMethods().makeOpenAdminBoxCallback.apply(this, arguments);
};

methods.makeSelectItemCallback = function() {
	return this.parentMethods().makeSelectItemCallback.apply(this, arguments);
};

methods.itemClick = function() {
	return this.parentMethods().itemClick.apply(this, arguments);
};

methods.showButtons = function() {
	return this.parentMethods().showButtons.apply(this, arguments);
};


}, zenarioO.panelTypes);
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
	panelTypes.grid_or_list = extensionOf(panelTypes.list)
);


methods.init = function() {
	//Check the local storage to check whether grid or list view was last used.
	this.altView = zenario.sGetItem(true, this.sessionVarName());
};


methods.sessionVarName = function() {
	return 'view_for_' + this.path;
};

methods.parentMethods = function() {
	if (this.altView) {
		return methodsOf(panelTypes.grid);
	} else {
		return methodsOf(panelTypes.list);
	}
};


methods.showPanel = function($header, $panel, $footer) {
	//Call the showPanel() method of either grid or list view, depending on what was chosen.
	//Also display and wire up a button to switch views between the two
	this.parentMethods().showPanel.apply(this, arguments);

	this.setSwitchButton($header, $panel, $footer);
	
};

methods.setSwitchButton = function($header, $panel, $footer) {
	var that = this;
	
	if (this.altView) {
		$header.find('#organizer_switch_to_list_view').show().click(function() {
			that.changeViewMode('');
			that.updatePanelAfterChangingView($header, $panel, $footer);
		});
	} else {
		$header.find('#organizer_switch_to_grid_view').show().click(function() {
			that.changeViewMode('1');
			that.updatePanelAfterChangingView($header, $panel, $footer);
		});
	}
};

methods.returnInspectionViewEnabled = function() {
	return this.parentMethods().returnInspectionViewEnabled.call(this);
};


//If the view mode is changed, remember the last value in the local storage
methods.changeViewMode = function(altView) {
	this.closeInspectionView();
	zenario.sSetItem(true, this.sessionVarName(), this.altView = altView);
};

methods.updatePanelAfterChangingView = function($header, $panel, $footer) {
	this.showPanel($header, $panel, $footer);
};







}, zenarioO.panelTypes);
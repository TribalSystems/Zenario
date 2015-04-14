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



methods.sessionVarName = function() {
	return 'view_for_' + zenarioO.path;
};


methods.showPanel = function($header, $panel, $footer) {
	var panelType,
		that = this;
	
	//Is the first time this panel has been displayed since it was initialised?
	if (this.view === undefined) {
		//Check the local storage to check whether grid or list view was last used.
		this.view = zenario.sGetItem(true, this.sessionVarName());
	}
	
	//Default to list if we can't find anything, or the variable was not set to list or grid
	if (this.view !== 'grid'
	 && this.view !== 'list') {
		this.view = 'list';
	}
	
	//Call the showPanel() method of either grid or list view, depending on what was chosen.
	//Also display and wire up a button to switch views between the two
	if (this.view == 'list') {
		methodsOf(panelTypes.list).showPanel.apply(this, arguments);
		
		$header.find('#organizer_switch_to_grid_view').show().click(function() {
			that.changeViewMode('grid');
			that.showPanel($header, $panel, $footer);
		});
	} else {
		methodsOf(panelTypes.grid).showPanel.apply(this, arguments);
		
		$header.find('#organizer_switch_to_list_view').show().click(function() {
			that.changeViewMode('list');
			that.showPanel($header, $panel, $footer);
		});
	}
	
};

methods.returnInspectionViewEnabled = function() {
	return this.view == 'list';
};


//If the view mode is changed, remember the last value in the local storage
methods.changeViewMode = function(mode) {
	this.closeInspectionView();
	zenario.sSetItem(true, this.sessionVarName(), this.view = mode);
};







}, zenarioO.panelTypes);
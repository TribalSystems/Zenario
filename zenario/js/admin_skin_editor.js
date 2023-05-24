/*
 * Copyright (c) 2023, Tribal Limited
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
	encodeURIComponent, defined, engToBoolean, get, htmlspecialchars, jsEscape, phrase,
	extensionOf, methodsOf, has,
	zenarioABToolkit
) {
	"use strict";

	var zenarioSE = window.zenarioSE = new zenarioABToolkit();
	zenarioSE.init('zenarioSE', 'zenario_skin_editor', 'zenarioSE_Controls');


var FAB_NAME = 'AdminFloatingBox',
	FAB_LEFT = 3,
	FAB_TOP = 2,
	FAB_PADDING_HEIGHT = 188,
	FAB_TAB_BAR_HEIGHT = 53,
	FAB_WIDTH = 960,
	PLUGIN_SETTINGS_WIDTH = 800,
	PLUGIN_SETTINGS_MIN_WIDTH_FOR_PREVIEW = 1100,
	PLUGIN_SETTINGS_BORDER_WIDTH = 4;



zenarioSE.open = function(path, skinId, callBack) {
	
	zenarioSE.canUpdatePreview = false;
	
	methodsOf(zenarioABToolkit).open.call(zenarioSE, 'zenario_skin_editor', {
		cID: zenario.cID,
		cType: zenario.cType,
		cVersion: zenario.cVersion,
		skinId: skinId || zenarioL.skinId
	}, undefined, undefined, callBack);
	//methods.open = function(path, key, tab, values, callBack, createAnotherObject, reopening, passMatchedIds) {
};


zenarioSE.openBox = function(html) {
	//zenarioA.adjustBox = function(n, e, width, left, top, html, padding, maxHeight, rightCornerOfElement, bottomCornerOfElement) {
	//zenarioA.openBox = function(html, className, n, e, width, left, top, disablePageBelow, overlay, draggable, resizable, padding, maxHeight, rightCornerOfElement, bottomCornerOfElement) {
	zenarioA.openBox(html, zenarioSE.baseCSSClass, FAB_NAME, false, false, false, false, true, true);
	
	
	var $zenarioSE = $('#zenarioSE'),
		$zenarioSE_Preview = $('#zenarioSE_Preview'),
		$zenarioSE_Controls = $('#zenarioSE_Controls'),
		availableHeight = $zenarioSE.height(),
		winHeight = Math.floor($(window).height()),
		options = {
			containment: 'document',
			minHeight: 100,
			maxHeight: winHeight - 150,
			handles: 's',
			start: ()=>{
		        $('#zenarioSE_Preview iframe.zenarioSE_PreviewFrame').css('pointer-events','none');
				zenarioSE.size(true, true);
			},
			resize: ()=>{
				zenarioSE.size(true, true);
			},
			stop: ()=> {
		        $('#zenarioSE_Preview iframe.zenarioSE_PreviewFrame').css('pointer-events','auto');
				zenarioSE.size(true);
			}
		};
	
	$zenarioSE_Preview.resizable(options);
	
	//...but hide the box itself, so only the overlay shows
	get('zenario_fbAdminFloatingBox').style.display = 'none';
};

zenarioSE.closeBox = function() {
	zenarioA.closeBox(FAB_NAME);
};



zenarioSE.setTitle = function(isReadOnly) {
	//...no title..?
};

zenarioSE.insertHTML = function(html, cb, isNewTab) {
	this.get('zenario_abtab').innerHTML = html;
	this.tabHidden = false;
	
	cb.done();
	zenarioSE.size(true);
	
	if (zenarioT.showDevTools()) {
		this.__lastFormHTML = html;
	}
};

zenarioSE.addJQueryElementsToTab = function() {
	//Add any special jQuery objects to the tab
	zenario.addJQueryElements('#zenarioSE ', true);
};


zenarioSE.markAsChanged = function(tab) {
	if (!zenarioSE.tuix) {
		return;
	}
	
	if (!defined(tab)) {
		tab = zenarioSE.tuix.tab;
	}
	
	if (!tab) {
		return;
	}
	
	zenarioSE.changed[tab] = true;
	
	//Liven "update preview" and "save" buttons
	zenarioSE.canUpdatePreview = true;
	$('#zenario_fabUpdatePreview')
		.removeClass('submit_disabled')
		.addClass('zenario_preview_enabled');
	$('#zenario_fabSaveAndContinue')
		.removeClass('submit_disabled')
		.addClass('submit_selected');
	$('#zenario_fabSave')
		.removeClass('submit_disabled')
		.addClass('submit_selected');
};




//Automatically set the box to the correct height for the users screen, or the maximum height requested, whichever is smaller
zenarioSE.lastSize = false;
zenarioSE.previewHidden = true;
zenarioSE.size = function(refresh, resizing) {
	if (zenarioSE.sizing) {
		clearTimeout(zenarioSE.sizing);
	}
	
	var width = Math.floor($(window).width()),
		height = Math.floor($(window).height()),
		windowSizedChanged;
	
	if (width && height && !zenarioSE.isSlidUp) {
		
		windowSizedChanged = zenarioSE.lastSize != width + 'x' + height;
		
		if (windowSizedChanged || refresh) {
			zenarioSE.lastSize = width + 'x' + height;
			
			var $zenarioSE = $('#zenarioSE'),
				$zenarioSE_Preview = $('#zenarioSE_Preview'),
				$zenarioSE_Controls = $('#zenarioSE_Controls'),
				availableHeight = $zenarioSE.height(),
				previewHeight = $zenarioSE_Preview.height(),
				controlsHeight = $zenarioSE_Controls.height();
			
			if (availableHeight - previewHeight > 150) {
				$zenarioSE_Controls.height(availableHeight - previewHeight);
			} else {
				$zenarioSE_Controls.height(150);
				$zenarioSE_Preview.height(availableHeight - 150);
			}
			
			$('#zenario_abtab').css('height', 'auto');
			$('#css_source').height(
				$zenarioSE_Controls.outerHeight()
				- $('#zenario_fabTabs').outerHeight()
				- $('#zenario_fbButtons').outerHeight()
			);
			
			if (this.get('css_source')) {
				var editor = ace.edit('css_source');
				editor.resize();
			}
			
			//zenarioA.adjustBox(FAB_NAME, false, newWidth, FAB_LEFT, FAB_TOP);
		}
	}
	
	zenarioSE.sizing = setTimeout(zenarioSE.size, 250);
	
	if (zenarioSE.previewHidden) {
		zenarioSE.previewHidden = false;
		zenarioSE.updatePreview();
	}
};

zenarioSE.submitPreview = function(preview, $parent, cssClassName) {
	
	$parent = $parent || $('#zenarioSE_Preview');
	cssClassName = cssClassName || 'zenarioSE_PreviewFrame';
	
	methodsOf(zenarioABToolkit).submitPreview.call(zenarioSE, preview, $parent, cssClassName);
	
	//fade "update preview" button
	zenarioSE.canUpdatePreview = false;
	$('#zenario_fabUpdatePreview')
		.removeClass('zenario_preview_enabled')
		.addClass('submit_disabled');
};






},
	zenarioABToolkit
);
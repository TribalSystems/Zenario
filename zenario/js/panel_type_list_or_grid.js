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
	panelTypes.grid_or_list = function() {}
);



methods.init = function() {
	//This class works by taking two other classes and switching between them on a toggle
	var methodName, type,
		that = this,
		panelTypeA = this.returnPanelTypeA(),
		panelTypeB = this.returnPanelTypeB(),
		pia = this.pia = new panelTypeA,
		pib = this.pib = new panelTypeB,
		methods = {};
	
	//Get a combined list of all of the methods and properties from the parent classes
	//Note: we must NOT use "hasOwnProperty" (or any shortcut function to hasOwnProperty) because we
	//want all of the methods the class has, including its parent methods
	for (methodName in pia) {
		methods[methodName] = typeof pia[methodName];
	}
	for (methodName in pib) {
		methods[methodName] = typeof pib[methodName];
	}
	
	//Loop through each of them, setting up pointers to them
	foreach (methods as methodName => type) {
		(function(methodName, type) {
			var childMethod;
		
			switch (methodName) {
				//Some special cases, don't create pointers for init() or the constructor method
				case 'init':
				case 'constructor':
					break;
			
				default:
					//Ignore everything but functions
					switch (type) {
						case 'function':
							
							//If this class has a method, and the parent classes also have that method,
							//link them all together so that one of the parent methods is called and then the
							//child method is called.
							if (childMethod = that[methodName]) {
								that[methodName] = function() {
									var rv;
								
									if (that.altView) {
										rv = pib[methodName].apply(pib, arguments);
									} else {
										rv = pia[methodName].apply(pia, arguments);
									}
								
									childMethod.apply(that, arguments);
								
									return rv;
								};
							
							//For the "cmsSets" methods, always call both of the parent methods
							} else if (methodName.match(/^cmsSets/)) {
								that[methodName] = function() {
									pib[methodName].apply(pib, arguments);
									pia[methodName].apply(pia, arguments);
								};
							
							//If this class does not have a method, just call one of the parent methods
							} else {
								that[methodName] = function() {
									if (that.altView) {
										return pib[methodName].apply(pib, arguments);
									} else {
										return pia[methodName].apply(pia, arguments);
									}
								};
							}
							break;
					}
			}
		})(methodName, type);
	}
	
	
	
	//Check the local storage to check whether grid or list view was last used.
	this.altView = zenario.sGetItem(true, 'view_for_' + this.path);
	
	//Init both parent classes, as zenarioO.initNewPanelInstance() would
	this.pia.cmsSetsPath(this.path);
	this.pib.cmsSetsPath(this.path);
	this.pia.cmsSetsRefiner(this.refiner);
	this.pib.cmsSetsRefiner(this.refiner);
	this.pia.init();
	this.pib.init();
};

methods.cmsSetsPath = function(path) {
	this.path = path;
};

methods.cmsSetsRefiner = function(refiner) {
	this.refiner = refiner;
};


//Every time the panel is shown, we also need to set up the switch button
methods.showPanel = function($header, $panel, $footer) {
	this.setSwitchButton($header, $panel, $footer);
};

methods.changeViewMode = function(altView) {
	//If the view mode is changed, remember the last value in the local storage
	zenario.sSetItem(true, 'view_for_' + this.path, this.altView = altView);
	
	
	var selectedItems;
	if (altView) {
		selectedItems = this.pia.returnSelectedItems();
		this.pib.cmsSetsSelectedItems(selectedItems);
	} else {
		selectedItems = this.pib.returnSelectedItems();
		this.pia.cmsSetsSelectedItems(selectedItems);
	}
	
	//Refresh the panel to show things in the new view
	zenarioO.refresh();
};

//Setup the switch view button at the top right of Organizer
methods.setSwitchButton = function($header, $panel, $footer) {
	var that = this,
		$switchButton = $header.find('#organizer_switch_view'),
		tooltip,
		cssClass,
		selectedItems;
	
	if (this.altView) {
		$switchButton.click(function() {
			that.changeViewMode('');
		});
		tooltip = this.returnSwitchButtonTooltipA();
		cssClass = this.returnSwitchButtonCSSClassA();
		
	} else {
		$switchButton.click(function() {
			that.changeViewMode('1');
		});
		tooltip = this.returnSwitchButtonTooltipB();
		cssClass = this.returnSwitchButtonCSSClassB();
	}
	
	$switchButton.show().attr('class', cssClass);
	zenarioA.tooltips($switchButton, {items: '*', content: tooltip});
};






//You can redefine these methods when extending this class to easily change which
//panel types are switched between, and to customise the button
methods.returnPanelTypeA = function() {
	return panelTypes.list;
};

methods.returnPanelTypeB = function() {
	return panelTypes.grid;
};

methods.returnSwitchButtonCSSClassA = function() {
	return 'organizer_switch_to_list_view';
};

methods.returnSwitchButtonCSSClassB = function() {
	return 'organizer_switch_to_grid_view';
};

methods.returnSwitchButtonTooltipA = function() {
	return 'List view';
};

methods.returnSwitchButtonTooltipB = function() {
	return 'Grid view';
};


}, zenarioO.panelTypes);
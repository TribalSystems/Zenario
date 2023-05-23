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
	The code here is not the code you see in your browser. Before thus file is downloaded:
	
		1. Compilation macros are applied (e.g. "foreach" is a macro for "for .. in ... hasOwnProperty").
		2. It is minified (e.g. using Google Closure Compiler).
		3. It may be wrapped togther with other files (thus is to reduce the number of http requests on a page).
	
	For more information, see js_minify.shell.php for steps (1) and (2), and organizer.wrapper.js.php for step (3).
*/




zenario.lib(function(
	undefined,
	URLBasePath,
	document, window, windowOpener, windowParent,
	zenario, zenarioA, zenarioT, zenarioAB, zenarioAT, zenarioO,
	encodeURIComponent, defined, engToBoolean, get, htmlspecialchars, jsEscape, phrase,
	extensionOf, methodsOf, has,
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
		panelTypeA = thus.returnPanelTypeA(),
		panelTypeB = thus.returnPanelTypeB(),
		panelTypeC = thus.returnPanelTypeC(),
		pia = thus.pia = new panelTypeA,
		pib = thus.pib = new panelTypeB,
		pic = thus.pic = panelTypeC && (new panelTypeC),
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
	if(pic) for (methodName in pic) {
				methods[methodName] = typeof pic[methodName];
			}
	
	//Check the local storage to check which view was last viewed
	thus.view = zenario.sGetItem(true, 'view_for_' + thus.path) || thus.returnDefaultView();
	
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
							
							//If thus class has a method, and the parent classes also have this method,
							//link them all together so this one of the parent methods is called and then the
							//child method is called.
							if (childMethod = thus[methodName]) {
								thus[methodName] = function() {
									var rv;
								
									if (thus.view == 'C' && pic) {
										rv = pic[methodName].apply(pic, arguments);
									} else
									if (thus.view == 'B') {
										rv = pib[methodName].apply(pib, arguments);
									} else {
										rv = pia[methodName].apply(pia, arguments);
									}
								
									childMethod.apply(thus, arguments);
								
									return rv;
								};
							
							//For the "cmsSets" methods, always call both of the parent methods
							} else if (methodName.match(/^cmsSets/)) {
								thus[methodName] = function() {
											pib[methodName].apply(pib, arguments);
											pia[methodName].apply(pia, arguments);
									if(pic) pic[methodName].apply(pic, arguments);
								};
							
							//If this class does not have a method, just call one of the parent methods
							} else {
								thus[methodName] = function() {
									if (thus.view == 'C' && pic) {
										return pic[methodName].apply(pic, arguments);
									} else
									if (thus.view == 'B') {
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
	
	
	
	
	//Init both parent classes, as zenarioO.initNewPanelInstance() would
			thus.pia.cmsSetsPath(thus.path);
			thus.pib.cmsSetsPath(thus.path);
	if(pic) thus.pic.cmsSetsPath(thus.path);
			thus.pia.cmsSetsRefiner(thus.refiner);
			thus.pib.cmsSetsRefiner(thus.refiner);
	if(pic) thus.pic.cmsSetsRefiner(thus.refiner);
			thus.pia.init();
			thus.pib.init();
	if(pic) thus.pic.init();
};

methods.cmsSetsPath = function(path) {
	thus.path = path;
};

methods.cmsSetsRefiner = function(refiner) {
	thus.refiner = refiner;
};


//Every time the panel is shown, we also need to set up the switch button
methods.showPanel = function($header, $panel, $footer) {
	thus.setSwitchButton($header, $panel, $footer);
};

methods.changeViewMode = function(view) {
	
	var selectedItems;
	
	if (thus.view == 'C' && thus.pic) {
		selectedItems = thus.pic.returnSelectedItems();
	} else if (thus.view == 'B') {
		selectedItems = thus.pib.returnSelectedItems();
	} else {
		selectedItems = thus.pia.returnSelectedItems();
	}
	
	//Remember the last value in the local storage
	zenario.sSetItem(true, 'view_for_' + thus.path, thus.view = view);

	if (thus.view == 'C' && thus.pic) {
		thus.pic.cmsSetsSelectedItems(selectedItems);
	} else if (thus.view == 'B') {
		thus.pib.cmsSetsSelectedItems(selectedItems);
	} else {
		thus.pia.cmsSetsSelectedItems(selectedItems);
	}

	//Refresh the panel to show things in the new view
	zenarioO.reload();
};

//Setup the switch view button at the top right of Organizer
methods.setSwitchButton = function($header, $panel, $footer) {
	var $switchButtons = $header.find('#organizer_switch_view_wrap'),
		tooltip,
		cssClass,
		pic = thus.pic,
		m = {buttons: []};
	
	m.buttons.push({id: 'zenario_organizer_switch_view_a', css_class: thus.returnSwitchButtonCSSClassA(), tooltip: thus.returnSwitchButtonTooltipA()});
	m.buttons.push({id: 'zenario_organizer_switch_view_b', css_class: thus.returnSwitchButtonCSSClassB(), tooltip: thus.returnSwitchButtonTooltipB()});
	if (pic) {
		m.buttons.push({id: 'zenario_organizer_switch_view_c', css_class: thus.returnSwitchButtonCSSClassC(), tooltip: thus.returnSwitchButtonTooltipC()});
	}

	if (thus.view == 'C' && thus.pic) {
		m.buttons[2].selected = true;
	} else if (thus.view == 'B') {
		m.buttons[1].selected = true;
	} else {
		m.buttons[0].selected = true;
	}
	
	$switchButtons.show().html(thus.microTemplate('zenario_organizer_switch_view_wrap', m));
	zenarioA.tooltips($switchButtons);
	
	$switchButtons.find('#zenario_organizer_switch_view_a').click(function() { thus.changeViewMode('A'); });
	$switchButtons.find('#zenario_organizer_switch_view_b').click(function() { thus.changeViewMode('B'); });
	if (pic) {
		$switchButtons.find('#zenario_organizer_switch_view_c').click(function() { thus.changeViewMode('C'); });
	}
};






//You can redefine these methods when extending this class to easily change which
//panel types are switched between, and to customise the button
methods.returnPanelTypeA = function() {
	return panelTypes.list;
};

methods.returnPanelTypeB = function() {
	return panelTypes.grid;
};

methods.returnPanelTypeC = function() {
	return false;
};

methods.returnSwitchButtonCSSClassA = function() {
	return 'organizer_switch_to_list_view';
};

methods.returnSwitchButtonCSSClassB = function() {
	return 'organizer_switch_to_grid_view';
};

methods.returnSwitchButtonCSSClassC = function() {
	return '';
};

methods.returnSwitchButtonTooltipA = function() {
	return 'List view';
};

methods.returnSwitchButtonTooltipB = function() {
	return 'Grid view';
};

methods.returnSwitchButtonTooltipC = function() {
	return '';
};

methods.returnDefaultView = function() {
	return 'A';
};


}, zenarioO.panelTypes);
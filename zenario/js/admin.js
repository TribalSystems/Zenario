/*
 * Copyright (c) 2016, Tribal Limited
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
	extensionOf, methodsOf
) {
	"use strict";


zenarioA.init = true;
zenarioA.menuWandOn = true;
zenarioA.slotWandOn = false;
zenarioA.showGridOn = false;
zenarioA.storekeeperInitTime = 5000;
zenarioA.adminSettings = {};

zenarioA.tooltipLengthThresholds = {
	adminBoxTitle: 120,
	adminToolbarTitle: 60,
	organizerBackButton: 70,
	organizerPanelTitle: 100
};




zenarioA.microTemplates = {};




//Code to add the "zenario_slotParent" class to the elements just above slots
	//When you hover over a slot, the control box for that slot, or the zenario_slotControlsWrap for that slot,
	//the CSS class zenario_slotParent should be added just above that slot.
	//When you move your mouse to another slot, the CSS class should be immediately removed from that slot, and its Plugin Options dropdown should be immediately closed.
	//When you move your mouse away from the slot, but not over another slot, it should be removed after a short delay.
zenarioA.slotParentMouseOverLastId = false;
zenarioA.slotParentMouseOver = function(event) {
	if (zenarioA.slotControlHide) {
		clearTimeout(zenarioA.slotControlHide);
	}
	
	$('.zenario_slotParent').removeClass('zenario_slotParent');
	
	var id;
	if (event) {
		if (this.className != 'zenario_slotControlsWrap') {
			id = $(this).attr('id').replace('-control_box', '');
			$(this).parent().addClass('zenario_slotParent');
		} else {
			id = 'plgslt_' + (this.id + '').replace('zenario_fbAdminPluginOptionsWrap-', '');
			$('#' + id).parent().addClass('zenario_slotParent');
		}
		
		if (zenarioA.slotParentMouseOverLastId && zenarioA.slotParentMouseOverLastId != id) {
			zenarioA.closeSlotControls();
		}
		zenarioA.slotParentMouseOverLastId = id;
	} else {
		zenarioA.closeSlotControls();
		zenarioA.slotParentMouseOverLastId = false;
	}
};

zenarioA.slotParentMouseOut = function(a) {
	if (zenarioA.slotControlHide) {
		clearTimeout(zenarioA.slotControlHide);
	}
	
	zenarioA.slotControlHide = setTimeout(zenarioA.slotParentMouseOver, zenarioA.slotControlHoverInterval);
};

zenarioA.setSlotParents = function() {
	$('.zenario_slotAdminControlBox').parent().children().mouseenter(zenarioA.slotParentMouseOver);
	$('.zenario_slotAdminControlBox').parent().children().mouseleave(zenarioA.slotParentMouseOut);
	$('#zenario_afb_container .zenario_slotControlsWrap').mouseenter(zenarioA.slotParentMouseOver);
	$('#zenario_afb_container .zenario_slotControlsWrap').mouseleave(zenarioA.slotParentMouseOut);
};

zenarioA.slotControlHide = false;
zenarioA.slotControlHoverInterval = 1500;
zenarioA.slotControlCloseInterval = 100;
$(document).ready(zenarioA.setSlotParents);


zenarioA.getGridSlotDetails = function(slotName) {
	//Get the grid span from the slot name
	var $gridspan = $('.' + slotName + '.span.slot'),
		grid = {
			container: false,
			cssClass: false,
			columns: false,
			width: false,
			widthInfo: false
		};
	
	if ($gridspan.length) {
		//Attempt to get the CSS class names of the wrapper of the slot
		//(it's easier to look this up using JavaScript than it is to work it out in fillAllAdminSlotControls() in php).
		grid.cssClass = $gridspan.attr('class'),
		
		//Strip out "alpha" and "omega" from the class names
		grid.cssClass = grid.cssClass.replace(' alpha ', ' ').replace(' omega ', ' ');
		
		//Get the actual width of the slot
		var fluidWidth = false,
			widthInfo = '',
			pxWidth = $gridspan.width(),
			container,
		
			//Try and read the number of columns from the css class names, e.g. "span3"
			css = $gridspan.attr('class') || '',
			columns = css.match(/\bspan\d+\b/);
	
		if (columns) {
			columns = 1 * columns[0].match(/\d+/);
		}
	
		try {
			//Loop through each stylesheet/rule, checking to see if there is a grid and a "span" rule that matches this span
			//Adapted from http://stackoverflow.com/questions/324486/how-do-you-read-css-rule-values-with-javascript
			outerLoop:
			foreach (document.styleSheets as var i => var s) {
				var rules = s.rules || s.cssRules;
		
				innerLoop:
				foreach (rules as var j => var rule) {
					if (rule.selectorText
					 && rule.style.width
					 && ('' + rule.selectorText).match(/\.span/)
					 && $gridspan.is(rule.selectorText)) {
						widthInfo = rule.style.width;
						break outerLoop;
					}
				}
			}
		} catch (e) {
			widthInfo = '';
		}
	
		if (!widthInfo) {
			widthInfo = pxWidth + 'px';
		} else if (!widthInfo.match(/\d+px/)) {
			fluidWidth = true;
		}
	
		if (fluidWidth) {
			widthInfo += ' (' + pxWidth + ' px ' + phrase.atCurrentSize + ')';
		}
	
		if (columns) {
			if (columns == 1) {
				widthInfo = '1 column, ' + widthInfo;
			} else {
				widthInfo = columns + ' columns, ' + widthInfo;
			}
		}
		
		grid.widthInfo = widthInfo;
		grid.pxWidth = pxWidth;
		grid.columns = columns;
		
		
		//Work out the size of the container
		if ((container = $gridspan.closest('div.container'))
		 && (container = container.attr('class'))
		 && (container = container.match(/container_(\d+)/))
		 && (container[1])) {
			grid.container = 1*container[1];
		}
	}
	
	return grid;
};




/*  Functions for managing plugin slots  */

zenarioA.openSlotControlsBox = false;
zenarioA.openSlotControls = function(el, e, slotName) {
	
	var closeAsIsAlreadyOpen = $('#zenario_fbAdminSlotControls-' + slotName).is(':visible');
	
	el.blur();
	zenarioA.closeSlotControls();
	
	if (!closeAsIsAlreadyOpen && zenarioA.checkForEdits()) {
		var width,
			section,
			sections = {info: false, notes: false, actions: false, overridden_info: false, overridden_actions: false},
			instanceId = zenario.slots[slotName].instanceId,
			grid = zenarioA.getGridSlotDetails(slotName);
		
		if (get('zenario_fbAdminSlotControls-' + slotName).innerHTML.indexOf('zenario_long_option') == -1) {
			width = 255;
		} else {
			width = 280;
		}
		
		
		var left = -width + 34;
		var top = 32;
		
		zenarioA.openBox(
			undefined,
			get('plgslt_' + slotName + '-wrap').className + ' zenario_fbAdminSlotControls',
			zenarioA.openSlotControlsBox = 'AdminSlotControls-' + slotName,
			e, width, left, top, false, false, false, false);
		
		//Check that each section has at least one label or button in it. If not, hide that section
		foreach (sections as section) {
			$('#zenario_fbAdminSlotControls-' + slotName + ' .zenario_slotControlsWrap_' + section).show();
			
			$('#zenario_fbAdminSlotControls-' + slotName + ' .zenario_slotControlsWrap_' + section + ' .zenario_sc:visible').each(function(i, el) {
				sections[section] = true;
			});
		
			if (!sections[section]) {
				$('#zenario_fbAdminSlotControls-' + slotName + ' .zenario_slotControlsWrap_' + section).hide();
			}
		}
		
		//Set the CSS class that the grid is using
		if (grid.cssClass) {
			//Strip out some technical class-names that make the grid work but designers don't need to see
			grid.cssClass = grid.cssClass.replace(/\bspan\d*_?\d*\s/g, '');
			
			$('#zenario_slot_control__' + slotName + '__info__' + 'grid_css_class').show();
			$('#zenario_slot_control__' + slotName + '__info__' + 'grid_css_class > span').text(grid.cssClass);
		} else {
			$('#zenario_slot_control__' + slotName + '__info__' + 'grid_css_class').hide();
		}
		
		//Set the width of this slot
		if (grid.widthInfo) {
			$('#zenario_slot_control__' + slotName + '__info__' + 'grid_width').show();
			$('#zenario_slot_control__' + slotName + '__info__' + 'grid_width > span').text(grid.widthInfo);
		} else {
			$('#zenario_slot_control__' + slotName + '__info__' + 'grid_width').hide();
		}
		
		$('#plgslt_' + slotName + '-control_box').addClass('zenario_adminSlotControlsOpen');
	}
	
	return false;
};

zenarioA.dontCloseSlotControls = function() {
	if (zenarioA.slotControlClose) {
		clearTimeout(zenarioA.slotControlClose);
	}
};

zenarioA.closeSlotControlsAfterDelay = function() {
	zenarioA.dontCloseSlotControls();
	zenarioA.slotControlClose = setTimeout(zenarioA.closeSlotControls, zenarioA.slotControlCloseInterval);
};

zenarioA.closeSlotControls = function() {
	zenarioA.dontCloseSlotControls();
	if (zenarioA.openSlotControlsBox) {
		zenarioA.closeBox(zenarioA.openSlotControlsBox, true, {effect: 'fade', duration: 200});
		$('.zenario_slotAdminControlBox').removeClass('zenario_adminSlotControlsOpen');
	}
};




zenarioA.pickNewPluginSlotName = false;
zenarioA.pickNewPlugin = function(el, slotName, level, showADifferentPlugin) {
	el.blur();
	
	zenarioA.pickNewPluginSlotName = slotName;
	zenarioA.pickNewPluginLevel = level;
	
	var path = 'zenario__modules/panels/modules/refiners/slotable_only////';
	
	//Select the existing module and plugin if possible
	if (!showADifferentPlugin
	 && zenario.slots[slotName]
	 && zenario.slots[slotName].moduleId
	 && zenario.slots[slotName].instanceId) {
		path += 'item//' + zenario.slots[slotName].moduleId + '//' + zenario.slots[slotName].instanceId;
	}
	
	zenarioA.organizerSelect('zenarioA', 'addNewReusablePlugin', false, path, 'zenario__modules/panels/plugins', 'zenario__modules/panels/modules', 'zenario__modules/panels/plugins', true, true, phrase.insertReusablePlugin);
	
	return false;
};

zenarioA.addNewReusablePlugin = function(path, key, row) {
	var instanceId = key.id, slotName = zenarioA.pickNewPluginSlotName, level = zenarioA.pickNewPluginLevel;
	
	var error = 
		zenario.moduleNonAsyncAJAX('zenario_common_features', {addPluginInstance: instanceId, slotName: slotName, level: level, cID: zenario.cID, cType: zenario.cType, cVersion: zenario.cVersion}, true);
	
	if (error) {
		zenarioA.showMessage(error);
	} else {
		zenario.refreshPluginSlot(slotName, '');
	}
};


zenarioA.addNewWireframePlugin = function(el, slotName, moduleId) {
	el.blur();
	
	var req = {
			addPlugin: moduleId,
			slotName: slotName,
			level: 2,
			cID: zenario.cID,
			cType: zenario.cType,
			cVersion: zenario.cVersion
		},
		html = zenario.moduleNonAsyncAJAX('zenario_common_features', req, false);
	
	if (zenarioA.loggedOut(html)) {
		return;
	}
	
	zenarioA.floatingBox(html, $(el).text(), 'warning', false, false, function() {
	
		var error = zenario.moduleNonAsyncAJAX('zenario_common_features', req, true);
	
		if (error) {
			zenarioA.showMessage(error);
		} else {
			zenario.refreshPluginSlot(slotName, '');
		}
	});
	
	return false;
};


//Show the thickbox for editing the instance in the slot, if there is one
zenarioA.pluginSlotEditSettings = function(el, slotName, fabPath) {
	el.blur();
	var instanceId = zenario.slots[slotName].instanceId;
	
	if (!get('zenario_theme_name_' + slotName + '__0') && instanceId) {
		zenarioAB.open(fabPath || 'plugin_settings', {cID: zenario.cID, cType: zenario.cType, cVersion: zenario.cVersion, slotName: slotName, instanceId: instanceId, frontEnd: 1});
	}
	
	return false;
};

//Moving modules
zenarioA.movePlugin = function(el, slotName) {
	el.blur();
	
	zenarioA.floatingBox(phrase.movePluginDesc, true, 'question', true, true, function() {
		zenarioA.moveSource = slotName;
		$('.zenario_slotAdminControlBox').addClass('zenario_moveDestination');
		$('#plgslt_' + slotName + '-control_box').removeClass('zenario_moveDestination').addClass('zenario_moveSource');
	});
	
	return false;
};

zenarioA.doMovePlugin = function(el, moveDestination) {
	el.blur();
	
	var moveSource = zenarioA.moveSource;
	zenarioA.cancelMovePlugin(el);
	
	if (moveSource && moveDestination) {
		if (zenarioA.toolbar == 'edit') {
			zenarioA.doMovePlugin2(moveSource, moveDestination, 1);
		} else if (zenarioA.toolbar == 'template') {
			var html = zenario.moduleNonAsyncAJAX('zenario_common_features', {movePlugin: 1, level: 2, cID: zenario.cID, cType: zenario.cType, cVersion: zenario.cVersion}, false);
			
			if (zenarioA.loggedOut(html)) {
				return;
			}
			
			zenarioA.floatingBox(html, phrase.movePlugin, 'warning', false, false, function() {
				zenarioA.doMovePlugin2(moveSource, moveDestination, 2);
			});
		}
	}
	
	return false;
};

zenarioA.doMovePlugin2 = function(moveSource, moveDestination, level) {
	var error = 
		zenario.moduleNonAsyncAJAX('zenario_common_features', {movePlugin: 1, level: level, slotNameSource: moveSource, slotNameDestination: moveDestination, cID: zenario.cID, cType: zenario.cType, cVersion: zenario.cVersion}, true);
	
	if (error) {
		zenarioA.showMessage(error);
	} else {
		zenario.refreshPluginSlot(moveSource, '', zenarioA.importantGetRequests);
		zenario.refreshPluginSlot(moveDestination, '', zenarioA.importantGetRequests);
	}
};

zenarioA.cancelMovePlugin = function(el) {
	if (el) el.blur();
	
	delete zenarioA.moveSource;
	$('.zenario_slotAdminControlBox').removeClass('zenario_moveDestination').removeClass('zenario_moveSource');
	
	return false;
};




zenarioA.refreshAllSlotsWithCutCopyPaste = function(allowedModules) {
	
	//Try to get a list of every type of plugin affected
	var slotName, m, module, modules = {};
	
	allowedModules = allowedModules.split(',');
	
	foreach (allowedModules as m => module) {
		if (module) {
			modules[module] = true;
		}
	}
	
	modules.zenario_banner = true;
	modules.zenario_html_snippet = true;
	modules.zenario_wysiwyg_editor = true;
	
	//Reload the contents of every slot these plugins are in (version controlled plugins only)
	foreach (modules as module) {
		if (window[module]
		 && window[module].slots) {
			foreach (window[module].slots as slotName) {
				if (zenario.slots[slotName]
				 && zenario.slots[slotName].isVersionControlled) {
					window[module].refreshPluginSlot(slotName, false, false, false);
				}
			}	
		}
	};
};
	
zenarioA.copyContents = function(el, slotName, allowedModules) {
	el.blur();
	
	var error = 
		zenario.moduleNonAsyncAJAX('zenario_common_features', {copyContents: 1, allowedModules: allowedModules, slotName: slotName, cID: zenario.cID, cType: zenario.cType, cVersion: zenario.cVersion}, true);
	
	if (error) {
		zenarioA.showMessage(error);
	} else {
		zenarioA.refreshAllSlotsWithCutCopyPaste(allowedModules);
	}
};

zenarioA.cutContents = function(el, slotName, allowedModules) {
	el.blur();
	
	var error = 
		zenario.moduleNonAsyncAJAX('zenario_common_features', {cutContents: 1, allowedModules: allowedModules, slotName: slotName, cID: zenario.cID, cType: zenario.cType, cVersion: zenario.cVersion}, true);
	
	if (error) {
		zenarioA.showMessage(error);
	} else {
		zenarioA.refreshAllSlotsWithCutCopyPaste(allowedModules);
	}
};

zenarioA.pasteContents = function(el, slotName) {
	el.blur();
	
	var error = 
		zenario.moduleNonAsyncAJAX('zenario_common_features', {pasteContents: 1, slotName: slotName, cID: zenario.cID, cType: zenario.cType, cVersion: zenario.cVersion}, true);
	
	if (error) {
		zenarioA.showMessage(error);
	} else {
		zenario.refreshPluginSlot(slotName, '', undefined, false, false, false, false);
	}
};

zenarioA.overwriteContents = function(el, slotName) {
	el.blur();
	
	zenarioA.floatingBox(phrase.overwriteContentsConfirm, $(el).text(), 'warning', false, false, function() {
		var error = 
			zenario.moduleNonAsyncAJAX('zenario_common_features', {overwriteContents: 1, slotName: slotName, cID: zenario.cID, cType: zenario.cType, cVersion: zenario.cVersion}, true);
	
		if (error) {
			zenarioA.showMessage(error);
		} else {
			zenario.refreshPluginSlot(slotName, '', undefined, false, false, false, false);
		}
	});
	
	return false;
};

zenarioA.swapContents = function(el, slotName) {
	el.blur();
	
	zenarioA.floatingBox(phrase.swapContentsConfirm, $(el).text(), 'warning', false, false, function() {
		var error = 
			zenario.moduleNonAsyncAJAX('zenario_common_features', {swapContents: 1, slotName: slotName, cID: zenario.cID, cType: zenario.cType, cVersion: zenario.cVersion}, true);
	
		if (error) {
			zenarioA.showMessage(error);
		} else {
			zenario.refreshPluginSlot(slotName, '', undefined, false, false, false, false);
		}
	});
	
	return false;
};




zenarioA.removePlugin = function(el, slotName, level) {
	el.blur();
	
	var req = {
			removePlugin: 1,
			level: level,
			slotName: slotName,
			cID: zenario.cID,
			cType: zenario.cType,
			cVersion: zenario.cVersion
		},
		doRemovePlugin = function() {
			var error = 
				zenario.moduleNonAsyncAJAX('zenario_common_features', req, true);
	
			if (error) {
				zenarioA.showMessage(error);
			} else {
				zenario.refreshPluginSlot(slotName, '', undefined, false, false, false, false);
			}
		};
	
	if (level > 1) {
		html = zenario.moduleNonAsyncAJAX('zenario_common_features', req, false);
		
		if (zenarioA.loggedOut(html)) {
			return;
		}
	
		zenarioA.floatingBox(html, phrase.remove, true, false, false, doRemovePlugin);
	} else {
		doRemovePlugin();
	}
	
	return false;
};

	
zenarioA.hidePlugin = function(el, slotName) {
	el.blur();
	
	var error = 
		zenario.moduleNonAsyncAJAX('zenario_common_features', {hidePlugin: 1, slotName: slotName, cID: zenario.cID, cType: zenario.cType, cVersion: zenario.cVersion}, true);
	
	if (error) {
		zenarioA.showMessage(error);
	} else {
		zenario.refreshPluginSlot(slotName, '', undefined, false, false, false, false);
	}
};

	
zenarioA.showPlugin = function(el, slotName) {
	el.blur();
	
	var error = 
		zenario.moduleNonAsyncAJAX('zenario_common_features', {showPlugin: 1, slotName: slotName, cID: zenario.cID, cType: zenario.cType, cVersion: zenario.cVersion}, true);
	
	if (error) {
		zenarioA.showMessage(error);
	} else {
		zenario.refreshPluginSlot(slotName, '', undefined, false, false, false, false);
	}
};



/*  Reloading Slots  */

//Callback function for refreshPluginSlot()
zenarioA.replacePluginSlot = function(slotName, instanceId, level, tabId, contents, info, scriptsToRunBefore) {
	
	var moduleId = false,
		isVersionControlled = false,
		beingEdited = false,
		slotControls = '',
		slotControlsCSSClass = '';
	
	//Look through the info at the top of the AJAX return
	if (info) {
		foreach (info as var i) {
			var details = info[i].split('--');
			
			//Watch out for the "In Edit Mode" tag from modules in their edit modes
			if (details[0] == 'IN_EDIT_MODE') {
				beingEdited = true;
			
			//Watch out for the Plugin id
			} else if (details[0] == 'MODULE_ID') {
				moduleId = 1*details[1];
			
			} else if (details[0] == 'WIREFRAME') {
				isVersionControlled = true;
			
			//Change the Class name of the slot
			} else if (details[0] == 'CSS_CLASS') {
				get('plgslt_' + slotName).className = 'zenario_slot ' + zenario.uneschyp(details[1])
			
			//Add a JavaScript namespace for a Plugin if one is not already present
			//Also watch out for modules requesting JavaScript files to be added
			} else if (details[0] == 'NAMESPACE') {
				if (moduleId && !window[zenario.uneschyp(details[1])]) {
					zenario.addPluginJavaScript(moduleId);
				}
			
			} else if (details[0] == 'SLOT_CONTROLS') {
				slotControls = zenario.uneschyp(details[1]);
			
			} else if (details[0] == 'SLOT_CONTROLS_CSS_CLASS') {
				slotControlsCSSClass = zenario.uneschyp(details[1]);
			}
		}
	}
	
	if (!moduleId) {
		instanceId = 0;
	}
	
	//Set the current instance id
	zenario.slot([[slotName, instanceId, moduleId, level, tabId, undefined, beingEdited, isVersionControlled]]);
	
	//Add a css class around slots that are being edited using the WYSIWYG Editor
	if (beingEdited) {
		slotControlsCSSClass += ' zenario_slot_being_edited';
	}
	
	//Set the CSS class for the slot's admin wrapper/slot controls
	get('plgslt_' + slotName + '-wrap').className = slotControlsCSSClass;
	
	//If any slots are being edited, set a warning message for if an admin tries to leave the page 
	window.onbeforeunload = zenarioA.onbeforeunload;
	
	//Remember that this slot is being edited
	zenario.slots[slotName].beingEdited = beingEdited;
	
	foreach (scriptsToRunBefore as var script) {
		if (zenario.slots[slotName]) {
			zenario.callScript(scriptsToRunBefore[script], zenario.slots[slotName].moduleClassName);
		}
	}
	
	//Refresh the slot's innerHTML
	get('plgslt_' + slotName).innerHTML = contents;
	get('zenario_fbAdminSlotControlsContents-' + slotName).innerHTML = slotControls;
	
	
	//Set tooltips for the area, if we are using tooltips
	zenario.tooltips('#plgslt_' + slotName + ' a');
	zenario.tooltips('#plgslt_' + slotName + ' img');
	zenario.tooltips('#plgslt_' + slotName + ' input');
};


zenarioA.checkSlotsBeingEdited = function() {
	if (zenario.slots) {
		foreach (zenario.slots as var slotName => var slot) {
			if (slot.beingEdited) {
				$('body').addClass('zenario_being_edited');
				return true;
			}
		}
	}
	
	$('body').removeClass('zenario_being_edited');
	return false
};

zenarioA.checkForEdits = function() {
	//Don't allow something to be changed whilst in edit mode
	if (zenarioA.toolbar == 'edit' && zenarioA.checkSlotsBeingEdited()) {
		zenarioA.showMessage(phrase.editorOpen);
		return false;
	} else {
		return true;
	}
};

zenarioA.onbeforeunload = function() {
	//If any Admin Boxes are open, and look like they might have been changed, set a warning message for if an admin tries to leave the page 
	if (zenarioAB.isOpen && zenarioAB.editModeOnBox() && (zenarioAB.changes() || zenarioAB.callFunctionOnEditors('isDirty'))) {
		return phrase.leaveAdminBoxWarning;
	
	//Set a warning if any slots are being edited
	} else if (zenarioA.checkSlotsBeingEdited()) {
		return phrase.leavePageWarning;
	
	} else {
		return undefined;
	}
};





//Pop up messages/boxes


zenarioA.boxesOpen = {};
zenarioA.openBox = function(html, className, n, e, width, left, top, disablePageBelow, overlay, draggable, resizable, padding, maxHeight, rightCornerOfElement, bottomCornerOfElement) {
	
	if (!n) {
		n = 1;
	}
	
	if (className === undefined) {
		className = '';
	}
	
	var $box,
		$overlay,
		zIndex;
	
	if (disablePageBelow || overlay) {
		if (!get('zenario_fb' + n + '__overlay')) {
			var s, split, plit, overlayClassName = 'zenario_overlay';
			
			if (className) {
				split = className.split(' ');
				foreach (split as s => plit) {
					if (plit) {
						overlayClassName += ' ' + plit + '__overlay';
					}
				}
			}
			
			$('body').append('<div class="' + overlayClassName + '" id="zenario_fb' + n + '__overlay" style="display: none;"></div>');
		}
		$overlay = $('#zenario_fb' + n + '__overlay');
	}
	
	if (!get('zenario_fb' + n)) {
		$('body').append('<div id="zenario_fb' + n + '"></div>');
	}
	get('zenario_fb' + n).className = className;
	$box = $('#zenario_fb' + n);
	
	if (draggable === true) {
		$box.draggable({handle: '.zenario_drag', cancel: '.zenario_no_drag', containment: 'window'});
	
	} else if (typeof draggable == 'string') {
		$box.draggable({handle: draggable, cancel: '.zenario_no_drag', containment: 'window'});
	}
	
	
	zenarioA.adjustBox(n, e, width, left, top, html, padding, maxHeight, rightCornerOfElement, bottomCornerOfElement);
	
	
	if (disablePageBelow || overlay) {
		zIndex = $box.css('z-index');
		$overlay.css('z-index', ifNull(1 * zIndex - 1, 0, 0)).show().unbind('click');
		
		if (!disablePageBelow) {
			$overlay.click(function() {
				zenarioA.closeBox(n);
			});
		}
		
		$overlay.show();
	}
	
	if (resizable) {
		if (resizable === true) {
			resizable = {};
		}
		
		if (resizable.containment === undefined) {
			resizable.containment = 'document';
		}
		if (resizable.minWidth === undefined) {
			resizable.minWidth = width;
		}
		
		$box.resizable(resizable);
	}
	
	$('body').addClass('zenario_fb' + n + '__isOpen');
	$box.show();
};

zenarioA.adjustBox = function(n, e, width, left, top, html, padding, maxHeight, rightCornerOfElement, bottomCornerOfElement) {
	
	var wrapper = get('zenario_fb' + n);
	
	
	//var fromElement = false;
	
	if (!n) {
		n = 1;
	}
	
	if (top === undefined) {
		top = 15;
	}
	
	if (left === undefined) {
		left = 50;
	}
	
	if (padding === undefined) {
		padding = 15;
	}
	
	if (!width) {
		left = 0;
		padding = 0;
		wrapper.style.left = '0';
		wrapper.style.width = '100%';
	} else {
		wrapper.style.width = width + 'px';
	}
	
	
	if (html !== false && html !== undefined) {
		wrapper.innerHTML = html;
		zenario.addJQueryElements('#zenario_fb' + n + ' ', true);
	}
	
	if (maxHeight === undefined) {
		maxHeight = ifNull($('#zenario_fb' + n).height(), 50);
	}
	
	
	//Position the floating box
	//e can be a mouse event, or an object that was clicked on
	//If e is provided, then position it relative to the mouse/object. Otherwise position it relative to the screen.
	if (e) {
		var y;
		if (e.clientY !== undefined || e.pageY !== undefined) {
			y = zenario.getMouseY(e) + top;
		} else {
			y = $(e).offset().top + top;
			
			if (bottomCornerOfElement) {
				y += $(e).height();
			}
		}
		
		//Check the box is not off the screen, and move it back if so
		var minY = 1 + zenario.scrollTop();
		if (y < minY) {
			y = minY;
		} else {
			
			var windowHeight = $(window).height();
			
			if (zenarioA.pageEl) {
				windowHeight = Math.min(windowHeight, $(zenarioA.pageEl).height());
			}
			
			var maxY = windowHeight - maxHeight - padding + zenario.scrollTop();
			if (y > maxY) {
				y = maxY;
			}
		}
		
		wrapper.style.bottom = null;
		wrapper.style.top = y + 'px';
		wrapper.style.position = 'absolute';
	
	} else {
		if (top < 1) {
			top = 0;
		} else if (top > 100) {
			top = 100;
		}
		
		if (zenario.browserIsIE(6)) {
			wrapper.style.position = 'absolute';
			
			var className = wrapper.className;
			className = className.replace(/zenario_fbIE6Hack\d+/, '');
			className += ' zenario_fbIE6Hack' + (top < 4? top : 5 * Math.round(top / 5));
			
			wrapper.className = className;
			
		} else {
			wrapper.style.position = 'fixed';
			
			if (top <= 50) {
				wrapper.style.bottom = null;
				wrapper.style.top = top + '%';
			} else {
				wrapper.style.top = null;
				wrapper.style.bottom = (100 - top) + '%';
			}
		}
	}
	
	if (width) {
		if (e) {
			var x;
			if (e.clientX !== undefined || e.pageX !== undefined) {
				x = zenario.getMouseX(e) + top;
			} else {
				x = $(e).offset().left + left;
				
				if (rightCornerOfElement) {
					x += $(e).width();
				}
			}
			
			//Check the box is not off the screen, and move it back if so
			var minX = 1 + zenario.scrollLeft();
			if (x < minX) {
				x = minX;
			} else {
				var maxX = $(window).width() - width - padding + zenario.scrollLeft();
				if (x > maxX) {
					x = maxX;
				}
			}
			
			wrapper.style.right = null;
			wrapper.style.marginRight = 0;
			wrapper.style.marginLeft = 0;
			wrapper.style.left = x + 'px';
		} else {
			if (left < 1) {
				left = 0;
			} else if (left > 100) {
				left = 100;
			}
			
			var space = Math.max(0, $(window).width() - width);
			
			wrapper.style.right = null;
			wrapper.style.left = Math.round(space * left / 100) + 'px';
		}
	}
};

zenarioA.checkIfBoxIsOpen = function(n) {
	return $('#zenario_fb' + n).is(':visible');
};

zenarioA.closeBox = function(n, keepHTML, options) {
	if (!n) {
		n = 1;
	}
	
	if (keepHTML) {
		$('#zenario_fb' + n).hide(options);
	} else {
		$('#zenario_fb' + n).remove();
	}
	
	$('#zenario_fb' + n + '__overlay').remove();
	$('body').removeClass('zenario_fb' + n + '__isOpen');
	
	if (zenarioA.checkIfBoxIsOpen(n)) {
		document.body.focus();
	}
	
	return false;
};

zenarioA.closeBoxHandler = function(box) {
	if (box.w) {
		delete zenarioA.boxesOpen[box.w.attr('id')];
		box.w.hide();
	}
	
	if (box.o) {
		box.o.remove();
	}
	
	foreach (zenarioA.boxesOpen as var n) {
		return;
	}
	
	setTimeout(
		function() {
			zenarioA.floatingBoxOpen = false;
			foreach (zenarioA.boxesOpen as var n) {
				zenarioA.floatingBoxOpen = true;
			}
		}, 100);
};

//Fix a bug where the directory path does not wrap in Webkit/IE
zenarioA.forcePathWrap = function(html, pattern, replacement) {
	
	if (zenario.browserIsFirefox() && pattern === undefined) {
		return html;
	
	} else {
		if (pattern === undefined) {
			pattern = /\//g;
		}
		if (replacement === undefined) {
			replacement = '/';
		}
		
		return html.replace(pattern, replacement + '<span style="font-size: 1px;"> </span>');
	}
};






zenarioA.generateRandomString = function(length) {
	var chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
		charsLength = chars.length,
		string = '',
		i = 0;
	
	for (i = 0; i < length; ++i) { 
		string += chars.charAt(Math.floor(Math.random() * charsLength));
	}
	
	return string;
};


zenarioA.loggedOut = function(message) {
	if (message.substr(0, 17) == '<!--Logged_Out-->') {
		zenarioA.showMessage(message);
		return true;
	} else {
		return false;
	}
};

//If the admin has been logged out, check to see whether this window is in an iframe, and show the login window in the iframe if possible.
zenarioA.loggedOutIframeCheck = function(message, messageType) {
	var parent = (self && self != self.parent && self.parent);
	
	try {
		if (parent
		 && parent.zenarioA
		 && parent.zenarioA.showMessage) {
			parent.zenarioA.closeBox('AdminOrganizer');
			parent.zenarioA.showMessage(message, true, messageType);
			
			return true;
		}
	} catch (e) {
		return false;
	}
	
	return false;
};

zenarioA.floatingBox = function(message, buttonsHTML, messageType, modal, htmlEscapeMessage, onOkay) {
	var defaultModalValue = false,
		html,
		m;
	
	
	if (htmlEscapeMessage) {
		message = htmlspecialchars(message, true);
	}
	
	if (buttonsHTML === true) {
		buttonsHTML =
			'<input type="button" class="submit" value="' + phrase.OK + '"/>';
	
	} else if (buttonsHTML && buttonsHTML.indexOf('<input ') === -1) {
		buttonsHTML =
			'<input class="submit_selected" type="button" value="' + htmlspecialchars(buttonsHTML) + '"/>' +
			'<input type="button" class="submit" value="' + phrase.cancel + '"/>';
	}
	
	if (messageType == 'success' || messageType == 4) {
		messageType = 'zenario_fbSuccess';
	
	} else if (messageType == 'question' || messageType == 3) {
		messageType = 'zenario_fbQuestion';
	
	} else if (messageType == 'error' || messageType == 2) {
		messageType = 'zenario_fbError';
	
	} else if (messageType && messageType != 'none') {
		messageType = 'zenario_fbWarning';
		defaultModalValue = true;
	
	} else {
		messageType = '';
	}
	
	if (modal === undefined) {
		modal = defaultModalValue;
	}
	
	
	m = {
		message: message,
		messageType: messageType,
		buttonsHTML: buttonsHTML
	};
	
	html = zenarioA.microTemplate('zenario_popout_message', m);

	zenarioA.openBox(html, 'zenario_fbAdmin zenario_prompt', 'AdminMessage', undefined, 550, 50, 17, modal, true, false, false);
	
	zenario.addJQueryElements('#zenario_fbAdminMessage ', true);
	
	
	if (onOkay) {
		var $button = $('#zenario_fbMessageButtons .submit_selected');
		
		if (!$button.length) {
			$button = $('#zenario_fbMessageButtons .submit');
		}
		
		$button.click(
			function() {
				setTimeout(onOkay, 1);
			}
		);
	}
};

zenarioA.currentlyClosingFloatingBox = false;
zenarioA.closeFloatingBox = function(stopBoxClosingTwice) {
	if (zenarioA.currentlyClosingFloatingBox) {
		zenarioA.currentlyClosingFloatingBox = false;
	} else {
		if (zenarioA.checkIfBoxIsOpen('AdminMessage')) {
			zenarioA.closeBox('AdminMessage');
			
			if (stopBoxClosingTwice) {
				zenarioA.currentlyClosingFloatingBox = true;
			}
		}
	}
};



//Add jQuery elements automatically by class name
zenarioA.addJQueryElements = function(path) {
	
	
	//jQuery datepickers (Admin mode version)
	$(path + 'input.zenario_datepicker').each(function(i, el) {
		if (el.id && zenarioA.siteSettings && get('_value_for__' + el.id)) {
			var changeMonthAndYear = $(el).hasClass('zenario_datepicker_change_month_and_year');
			$(el).datepicker({
				changeMonth: changeMonthAndYear,
				changeYear: changeMonthAndYear,
				dateFormat: zenarioA.siteSettings.organizer_date_format,
				altField: '#_value_for__' + el.id,
				altFormat: 'yy-mm-dd',
				showOn: 'focus',
				onSelect: function(dateText, inst) {
					$(el).change();
					//zenarioAB.fieldChange(this.name);
				}
			});
		}
	});
	
	//Admin mode tooltips
	zenarioA.tooltips(path + 'span[title]');
	zenarioA.tooltips(path + '.pluginAdminMenuButton[title]', {position: {my: 'left top+2', at: 'left bottom', collision: 'flipfit'}});
};

zenarioA.tooltips = function(target, options) {
	if (!options) {
		options = {};
	}
	
	if (options.tooltipClass === undefined) {
		options.tooltipClass = 'zenario_admin_tooltip';
	}
	
	zenario.tooltips(target, options);
};

zenarioA.setTooltipIfTooLarge = function(target, title, sizeThreshold) {
	
	$(target).each(function(i, el) {
		var tooltip;
		
		if (title === undefined) {
			tooltip = el.innerHTML;
		} else {
			tooltip = title;
		}
		
		try {
			$(el).jQueryTooltip('destroy');
		} catch (e) {
		}
		
		if (tooltip.replace(/\&\w+;/g, '-').length > sizeThreshold) {
			zenarioA.tooltips(el, {content: tooltip, items: '*'});
		} else {
			$(el).attr('title', '');
		}
	});
};



//Functions for TinyMCE

//A hack to try and remove some of the bad/repeated html that TinyMCE sometimes generates,
//e.g. duplicate id/style tags
zenarioA.tinyMCEGetContent = function(editor) {
	var html,
		$html = $('<div/>').html(editor.getContent());
	
	//Bugfix for task #9380 "Problem where some of the CMS' <div>s had been entered into a WYSIWYG Editor"
	$html.find('[id^="plgslt_"]').each(function(i, el) {
		 $(el).removeAttr('id class style');
	});
	
	html = $html.html();
	
	//Fix for request #2908 "Bug with WYSIWYG Editor: You can't delete an empty <h1> tag"
	if (html == '<h1>&nbsp;</h1>') {
		html = '';
	}
	
	return html;
};

zenarioA.tinyMCEPasteRreprocess = function(pl, o) {
	o.content = o.content.replace(
		/<\/?font\b[^>]*?>/gi, '').replace(
		/<b\b[^>]*?>/gi, '<strong>').replace(
		/<\/b>/gi, '</strong>').replace(
		/<i\b[^>]*?>/gi, '<em>').replace(
		/<\/i>/gi, '</em>').replace(
		/<u\b[^>]*?>/gi, '<u>').replace(
		/<\/u>/gi, '</u>').replace(
		/<u>\s*<p\b[^>]*?>/gi, '<p><u>').replace(
		/<\/p>\s*<\/u>/gi, '</u></p>').replace(
		/<em>\s*<p\b[^>]*?>/gi, '<p><em>').replace(
		/<\/p>\s*<\/em>/gi, '</em></p>').replace(
		/<strong>\s*<p\b[^>]*?>/gi, '<p><strong>').replace(
		/<\/p>\s*<\/strong>/gi, '</strong></p>').replace(
		/<u\b[^>]*?>/gi, '<span style="text-decoration: underline;">').replace(
		/<\/u>/gi, '</span>');
};

//Enable an Admin to upload an image or an animation by draging and dropping it onto the WYSIWYG Editor
//The file will be uploaded using a call to the handleOrganizerPanelAJAX() function of the Common Features Module
zenarioA.enableDragDropUploadInTinyMCE = function(enableImages, prefix, el) {
	
	if (typeof el == 'string') {
		el = get(el);
	}
	
	if (el) {
		zenarioA.disableFileDragDrop(el);
		
		if (enableImages && zenarioA.canDoHTML5Upload()) {
			var url = URLBasePath + 'zenario/ajax.php',
				request = {
					method_call: 'handleOrganizerPanelAJAX',
					__pluginClassName__: 'zenario_common_features',
					__path__: 'editor_temp_file',
					upload: 1};
			
			zenarioA.setHTML5UploadFromDragDrop(
				url,
				request,
				false,
				function() {
					zenarioA.addMediaToTinyMCE(prefix);
				},
				el);
		}
	}
};

//Add a file or files uploaded above into the editor
zenarioA.addMediaToTinyMCE = function(prefix) {
	var files,
		html = '';
	
	if (files = zenario.nonAsyncAJAX(URLBasePath + 'zenario/ajax.php?method_call=getNewEditorTempFiles', true, true)) {
		foreach (files as var f => var file) {
			if (file && file.checksum && file.filename) {
				
				var url = prefix + 'zenario/file.php?c=' + file.checksum + '&filename=' + encodeURIComponent(file.filename);
				
				html += '\n' +
					'<img src="' + htmlspecialchars(url) + '" alt="' + htmlspecialchars(file.filename) + '"' +
					' width="' + file.width + '" height="' + file.height + '"/>';
			}
		}
	}
	tinyMCE.execCommand('mceInsertContent', false, html);
};


//This function will open Organizer if the user clicks on one of the "file browser" buttons in tinyMCE
zenarioA.fileBrowser = function(field_name, url, type, win) {
	
	//If this is a field in a FAB, try to load the definition of the field
	var editorId =
			window.tinyMCE
		 && tinyMCE.activeEditor
		 && tinyMCE.activeEditor.id,
		fabField =
			editorId
		 && zenarioAB.tuix
		 && zenarioAB.tuix.tab
		 && zenarioAB.tuix.tabs
		 && zenarioAB.tuix.tabs[zenarioAB.tuix.tab]
		 && zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields
		 && zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields[editorId];
	
	//Remember the open window, the name of the file browser's URL field (this will be something like "mceu_48-inp),
	//and whether we found the FAB field above or not.
	zenarioA.tinyMCE_win = win;
	zenarioA.tinyMCE_field = field_name;
	zenarioA.tinyMCE_fromFAB = !!fabField;
	
	//Links to content items. Open the zenario__content/panels/content panel by default,
	//but if this is a field in a FAB then allow this to be overridden
	if (type == 'file') {
		
		if (fabField
		 && fabField.insert_link_button
		 && fabField.insert_link_button.pick_items) {
			pick_items = fabField.insert_link_button.pick_items;
		} else {
			pick_items = {
				path: 'zenario__content/panels/content',
				target_path: 'zenario__content/panels/content',
				min_path: 'zenario__content/panels/content',
				max_path: 'zenario__content/panels/content',
				disallow_refiners_looping_on_min_path: false};
		}

		zenarioA.organizerSelect('zenarioA', 'setLinkURL', false,
						pick_items.path,
						pick_items.target_path,
						pick_items.min_path,
						pick_items.max_path,
						pick_items.disallow_refiners_looping_on_min_path,
						undefined,
						pick_items.one_to_one_choose_phrase,
						undefined,
						true);
	
	//Insert an image.
	//As with links, allow FAB fields to override the destination.
	//Otherwise, if this if for a WYSIWYG Editor, how the content item's images.
	//Otherwise, show the image library by default.
	} else if (type == 'image') {
		
		if (fabField
		 && fabField.insert_link_button
		 && fabField.insert_link_button.pick_items) {
			pick_items = fabField.insert_link_button.pick_items;
		
		} else
		if (!fabField
		 && zenario.cID
		 && zenario.cType
		 && window.zenario_wysiwyg_editor
		 && zenario_wysiwyg_editor.poking) {
			pick_items = {
				path: 'zenario__content/panels/content/item_buttons/images//' + zenario.cType + '_' + zenario.cID + '//',
				target_path: 'zenario__content/panels/inline_images_for_content',
				min_path: 'zenario__content/panels/inline_images_for_content',
				max_path: 'zenario__content/panels/inline_images_for_content',
				disallow_refiners_looping_on_min_path: false};
		
		} else {
			pick_items = {
				path: 'zenario__content/panels/image_library',
				target_path: 'zenario__content/panels/image_library',
				min_path: 'zenario__content/panels/image_library',
				max_path: false,
				disallow_refiners_looping_on_min_path: false};
		}

		zenarioA.organizerSelect('zenarioA', 'setImageURL', false,
						pick_items.path,
						pick_items.target_path,
						pick_items.min_path,
						pick_items.max_path,
						pick_items.disallow_refiners_looping_on_min_path,
						undefined,
						pick_items.one_to_one_choose_phrase,
						undefined,
						true);
	
	//Link to a document (currently the link must be to a public document).
	} else if (type == 'zenario_document') {
		zenarioA.organizerSelect('zenarioA', 'setDocumentURL', false,
						'zenario__content/panels/documents',
						'zenario__content/panels/documents',
						'zenario__content/panels/documents',
						'zenario__content/panels/documents',
						false, undefined, undefined, undefined, true,
						undefined, undefined, undefined, undefined, undefined, undefined, undefined, undefined, undefined,
						{disabled_if: 'item && item.privacy != "public" && item.privacy != "Public"'});
	}
};


//By default there is only one file browser button. But for links, we want two;
//the first should be to content items and the second should be to documents.
//This function hacks about and replaces the single button with two buttons.
zenarioA.setLinkPickerOnTinyMCE = function() {
	var $urlField = $('.mce-zenario_link_picker input.mce-textbox'),
		$picker = $('.mce-zenario_link_picker .mce-open'),
		$newPicker = $(zenarioA.microTemplate('zenario_tinymce_link_picker', {urlFieldId: $urlField.attr('id')})),
		urlFieldWidth = $urlField.width(),
		pickerWidth = $picker.width(),
		newPickerWidth;
	
	$picker.replaceWith($newPicker);
	
	newPickerWidth = $newPicker.width();
	
	$urlField.width(urlFieldWidth + pickerWidth - newPickerWidth);
	
	zenarioA.tooltips(
		$newPicker.find('button'),
		{tooltipClass: 'zenario_admin_tooltip zenario_admin_tooltip_over_tinymce'}
	);
};


//This function sets the value of one of the fields in the TinyMCE forms.
//It's used after picking something from the file browser
zenarioA.lastFieldValue = '';
zenarioA.setEditorField = function(value, el, onlyIfEmpty) {
	if (el === undefined) {
		el = (zenarioA.tinyMCE_win || window).document.getElementById(zenarioA.tinyMCE_field);
	}
	
	if (onlyIfEmpty) {
		if (el.value !== '' && el.value != zenarioA.lastFieldValue) {
			return;
		}
		zenarioA.lastFieldValue = value;
	}
	
	el.value = value;
	zenario.fireChangeEvent(el);
};

//This handles the return results of the file browser for a link to a content item
zenarioA.setLinkURL = function(path, key, row) {
	
	//Get the URL via an AJAX program
	key.getItemURL = 1;
	var URL = zenario.moduleNonAsyncAJAX('zenario_common_features', key, true);
	
	if (zenarioA.loggedOut(URL)) {
		return;
	}
	
	//For admin boxes, make sure the full URL is used as a workaround for any relative path problems
	//The stripAbsURLsFromAdminBoxField() function can be used later to strip these off if this is not desirable.
	if (zenarioA.tinyMCE_fromFAB
	 && URL.indexOf('://') === -1) {
		URL = URLBasePath + URL;
	}
	
	zenarioA.setEditorField(row.title, $('.mce-panel input.mce-link_text_to_display')[0], true);
	zenarioA.setEditorField(URL);
};

//This handles the return results of the file browser for a link to a public document
zenarioA.setDocumentURL = function(path, key, row) {
	var documentURL = row.frontend_link;
	
	if (zenarioA.tinyMCE_fromFAB) {
		documentURL = URLBasePath + documentURL;
	}
	
	zenarioA.setEditorField(row.name, $('.mce-panel input.mce-link_text_to_display')[0]);
	zenarioA.setEditorField(documentURL);
}

//This handles the return results of the file browser for an image
zenarioA.setImageURL = function(path, key, row) {

	var imageURL = 'zenario/file.php?c=' + row.checksum;
	
	if (key.usage && key.usage != 'image') {
		imageURL += '&usage=' + encodeURIComponent(key.usage);
	}
	
	imageURL += '&filename=' + encodeURIComponent(row.filename);
	
	if (zenarioA.tinyMCE_fromFAB) {
		imageURL = URLBasePath + imageURL;
	}
	
	zenarioA.setEditorField(row.alt_tag, $('.mce-panel input.mce-image_alt')[0]);
	zenarioA.setEditorField(imageURL);
};









zenarioA.skinDesc = undefined;
zenarioA.getSkinDesc = function() {
	if (zenarioA.skinDesc === undefined) {
		zenarioA.skinDesc = {};
		
		var desc;
		if (zenario.skinId
		 && (desc = zenario.moduleNonAsyncAJAX('zenario_common_features', {skinId: zenario.skinId}, false, true))
		 && (typeof desc == 'object')) {
			zenarioA.skinDesc = desc;
		}
	}
	
	return zenarioA.skinDesc;
};


zenarioA.formatFilesizeNicely = function(size, precision) {
	
	//Return 0 without formating if the size is 0.
	if (size <= 0) {
		return '0';
	}
	
	//Define labels to use
	var labels = ['_BYTES', '_KBYTES', '_MBYTES', '_GBYTES', '_TBYTES'];
	
	//Work out which of the labels to use, based on how many powers of 1024 go into the size, and
	//how many labels we have
	var order = Math.min(4, 
					Math.floor(
						Math.log(size) / Math.log(1024)
					));
	
	precision = Math.pow(10, precision);
	
	return (Math.round(precision * size / Math.pow(1024, order)) / precision) + phrase[labels[order]];

};



zenarioA.makeTimeFromParts = function(hours,minutes,seconds) {
	var outputTime;
	
	if (seconds == undefined) {
		seconds = "00";
	}
	
	outputTime = hours + ":" + minutes + ":" + seconds;
	
	return outputTime;
};



zenarioA.showPagePreview = function(tuix, id) {
	var width = tuix.custom_width,
		height = tuix.custom_height,
		title = '';
	
	if (zenarioO.tuix !== undefined) {
		var item = zenarioO.tuix.items[id];
		switch (item.admin_version_status) {
			case 'first_draft':
			case 'published_with_draft':
			case 'hidden_with_draft':
			case 'trashed_with_draft':
				adminVersionStatus = 'Draft';
				break;
			case 'published':
				adminVersionStatus = 'Published';
				break;
			case 'hidden':
				adminVersionStatus = 'Hidden';
				break;
			case 'trashed':
				adminVersionStatus = 'Trashed';
				break;
		}
		title = item.tag + ' Version ' + item.version + ' [' + adminVersionStatus + '] '+width+' x '+height+' ('+tuix.custom_description+')';
	} else {
		title = tuix.custom_description
	}
	
	$.colorbox({
		innerWidth: width+'px',
		innerHeight: height+'px',
		maxWidth: false,
		maxHeight: false,
		iframe: true,
		preloading: false,
		open: true,
		title: title,
		href: URLBasePath + 'index.php?cID=' + id + '&_sk_preview=1',
		className: 'zenario_page_preview_colorbox'
	});
};







/* Organizer launch functions */

//Format the name of an item from Organizer appropriately
zenarioA.formatSKItemName = function(panel, i) {
	var string = undefined,
		string2,
		value;
	
	if (panel.items
	 && panel.items[i]) {
	
		if (string = string2 = ifNull(panel.label_format_for_picked_items, panel.label_format_for_grid_view)) {
			foreach (panel.items[i] as var c) {
				if (string.indexOf('[[' + c + ']]') != -1) {
					value = panel.items[i][c];
					
					if (panel.columns
					 && panel.columns[c]
					 && (panel.columns[c].format || panel.columns[c].empty_value)) {
						value = zenarioA.formatSKItemField(value, panel.columns[c]);
					}
					
					while (string != (string2 = string.replace('[[' + c + ']]', value))) {
						string = string2;
					}
				}
			}
		
		} else {
			string = panel.items[i][ifNull(panel.default_sort_column, 'name')];
		}
	}
	
	if (string === undefined) {
		return i;
	} else {
		return string.replace(/\s+/g, ' ');
	}
};

zenarioA.formatSKItemField = function(value, column) {

	if (column && column.format) {
		var format = column.format,
			//Most formats allow additional text seperated by a space
			extra = '';
		
		if (value && (format != 'date' && format != 'datetime' && format != 'datetime_with_seconds' && format != 'remove_zero_padding')) {
			var pos = value.indexOf(' ');
			if (pos != -1) {
				extra = value.substr(pos);
				value = value.substr(0, pos);
			}
		}
		
		if (format == 'true_or_false') {
			value = engToBoolean(value)? phrase.tru : phrase.fal;
			
		} else if (format == 'yes_or_no') {
			if (engToBoolean(value)) {
				if (column.yes_phrase !== undefined) {
					value = column.yes_phrase
				} else {
					value = phrase.yes;
				}
			} else {
				if (column.no_phrase !== undefined) {
					value = column.no_phrase
				} else {
					value = phrase.no;
				}
			}
			
		} else if (format == 'remove_zero_padding') {
			value = value.replace(/\b0+/g, '');
			
		} else if (format == 'enum' && column.values && column.values[value] !== undefined) {
			
			if (typeof column.values[value] == 'object') {
				if (column.values[value].label !== undefined) {
					value = column.values[value].label;
				}
			} else {
				value = column.values[value];
			}
			
		} else if ((format == 'module_name' || format == 'module_class_name') && zenarioO.init) {
			if (!value) {
				value = phrase.core;
			} else if (zenarioA.pluginNames[value]) {
				value = zenarioA.pluginNames[value];
			}
			
		} else if (format == 'filesize' && value == 1*value) {
			value = zenarioA.formatFilesizeNicely(value, 1);
			
		} else if ((format == 'language_english_name' || format == 'language_local_name') && zenarioA.lang[value]) {
			value = zenarioA.lang[value].name;
			
		} else if ((format == 'language_english_name_with_id' || format == 'language_local_name_with_id') && zenarioA.lang[value]) {
			value = zenarioA.lang[value].name + ' (' + value + ')';
			
		} else if (format == 'date' || format == 'datetime' || format == 'datetime_with_seconds') {
			value = zenario.formatDate(value, format == 'date'? false : format);
		}
		
		value += extra;
	}
	
	if (!value && column && column.empty_value) {
		value = column.empty_value;
	}
	
	return value;
};

//Open Organizer in quick mode
zenarioA.organizerQuick = function(path, targetPath, minPath, maxPath, disallowRefinersLoopingOnMinPath, slotName, instanceId, reloadOnChanges, wrapperCSSClass) {
	
	zenarioA.organizerSelect(
		false, false, false,
		path, false, minPath, maxPath, disallowRefinersLoopingOnMinPath,
		false, false, false,
		false, false,
		true, targetPath, instanceId,
		undefined,
		undefined, undefined,
		reloadOnChanges, wrapperCSSClass);
};

//Get the correct CSS class name to put around Organizer
zenarioA.getSKBodyClass = function(win) {
	if (win === undefined) {
		win = window;
	}
	
	return 'zenario_og ' + (
		win.zenarioONotFull || win.zenarioA.openedInIframe?
			(
				(win.zenarioOQuickMode? 'zenario_organizer_quick' : 'zenario_organizer_select') +
				' ' + 
				(win.zenarioOOpenOverAdminBox? 'zenario_organizer_over_admin_box' : 'zenario_organizer_under_admin_box')
			)
		  : 'zenario_organizer_full'
	) + ' ' + (win.zenarioOWrapperCSSClass || '');
};

//Open Organizer in select mode
zenarioA.organizerSelect = function(
	callbackObject, callbackFunction, enableMultipleSelect,
	path, targetPath, minPath, maxPath, disallowRefinersLoopingOnMinPath,
	chooseButtonActiveClass, choosePhrase, chooseMultiplePhrase,
	tinyMCE,
	openOverAdminBox,
	skQuick, openingPath, openingInstance,
	combineItem,
	allowNoSelection, noSelectionChoosePhrase,
	reloadOnChanges, wrapperCSSClass,
	object
) {
	
	var win,
		useIframe = !skQuick || zenarioA.isFullOrganizerWindow || zenarioA.checkIfBoxIsOpen('og');
	
	if (!object) {
		object = {};
	}
	
	if (!useIframe) {
		win = window;
	
	} else {
		//If we've already got Organizer open, we'll need to load this one new in an iFrame
		
		//Initialise it if it's not been preloaded yet
		zenarioA.SKInit();
		
		win = get('zenario_sk_iframe').contentWindow;
		
		//The "openOverAdminBox" variable should be false if we're opening an iframe,
		//as this new Organizer will be the first on the page and won't need to open over anything.
		openOverAdminBox = false;
	}
	
	//Close the tooltip if it is open
	zenario.closeTooltip();
	
	var overlayOpacity;
	if (skQuick) {
		overlayOpacity = 0;
	} else if (tinyMCE) {
		overlayOpacity = 10;
	} else if (!zenarioA.openedInIframe) {
		overlayOpacity = 75;
	} else {
		overlayOpacity = 35;
	}

	win.zenarioOQueue = [{path: path, branch: -1, selectedItems: {}}];
	
	//minPath and targetPath should default to path, as long as it is not a navigation path
	if (path.indexOf('//') === -1) {
		if (!minPath) {
			minPath = path;
		}
		if (!targetPath) {
			targetPath = path;
		}
	}
	
	//Max path should default to the target path if not set
	if (maxPath === undefined) {
		if (targetPath && targetPath.indexOf('//') === -1) {
			maxPath = targetPath;
		}
		
	//The max path variable can be set to false to turn those features off.
	//Convert strings such as "No" or "False" to false.
	} else if (!engToBoolean(maxPath)) {
		maxPath = false;
	}
	
	
	win.zenarioOTargetPath = targetPath;
	win.zenarioOMinPath = minPath;
	win.zenarioOMaxPath = maxPath;
	win.zenarioODisallowRefinersLoopingOnMinPath = engToBoolean(disallowRefinersLoopingOnMinPath);
	
	win.zenarioOCallbackObject = callbackObject;
	win.zenarioOCallbackFunction = callbackFunction;
	win.zenarioOChoosePhrase = choosePhrase;
	win.zenarioOChooseButtonActiveClass = chooseButtonActiveClass;
	win.zenarioOChooseMultiplePhrase = chooseMultiplePhrase;
	win.zenarioOMultipleSelect = engToBoolean(enableMultipleSelect);
	win.zenarioOAllowNoSelection = engToBoolean(allowNoSelection);
	win.zenarioONoSelectionChoosePhrase = noSelectionChoosePhrase;
	
	win.zenarioONotFull = true;
	win.zenarioOQuickMode = skQuick;
	win.zenarioOSelectMode = !skQuick;
	win.zenarioOSelectObject = object;
	
	win.zenarioOOpeningPath = openingPath;
	win.zenarioOOpeningInstance = openingInstance;
	win.zenarioOCombineItem = combineItem;
	win.zenarioOReloadOnChanges = reloadOnChanges;
	win.zenarioOWrapperCSSClass = wrapperCSSClass || '';
	win.zenarioOOpenOverAdminBox = openOverAdminBox;
	win.zenarioOFirstLoad = true;
	
	if (!useIframe) {
		//If we've not currently got an existing full-Organizer instance in this frame, set Organizer up in a <div>
		
		zenarioO.open(zenarioA.getSKBodyClass(win), undefined, $(window).width() * 0.8, 50, 10, !skQuick, true, true, {minWidth: 550});
		//zenarioO.open = function(className, e, width, left, top, disablePageBelow, overlay, draggable, resizable, padding, maxHeight, rightCornerOfElement, bottomCornerOfElement) {
		
		zenarioO.init();
	}
	
	//If Organizer has been already pre-loaded, we can use the navigation functions to go to the correct path
	if (win.zenarioO) {
		win.zenarioO.go(path, -1);
	}
	//Otherwise store the requested path in zenarioOGoToPathWhenLoaded and wait for the it to catch up
		
	if (useIframe) {
		//Show the Organizer window
		zenarioA.openBox(false, zenarioA.getSKBodyClass(win), 'AdminOrganizer', false, false, 50, 2, !skQuick, true, false, false);
		
		get('zenario_sk_iframe').style.width = zenario.browserIsIE(6)? '600px' : '96%';
		if (skQuick) {
			get('zenario_sk_iframe').style.height = '100%';
		} else {
			get('zenario_sk_iframe').style.height = '96%';
		}
		
		if (get('zenario_sk_iframe').contentWindow.zenarioO) {
			get('zenario_sk_iframe').contentWindow.zenarioO.lastSize = false;
		}
		
		get('zenario_fbAdminOrganizer').style.left = '0px';
	}
};

zenarioA.SKInitted = false;
zenarioA.SKInit = function() {
	if (zenarioA.SKInitted) {
		return;
	}
	
	get('zenario_fbAdminOrganizer').innerHTML =
		'<iframe id="zenario_sk_iframe" src="' + URLBasePath + 'zenario/admin/organizer.php?openedInIframe=1&amp;rand=' + (new Date).getTime() + '"></iframe>';
	
	zenarioA.SKInitted = true;
};

//Attempt to preload Organizer ten seconds after the current page has finished loading
zenarioA.SKStartInit = function() {
	
	//Don't do anything for IE 6 and 7
	if (zenario.browserIsIE(7)) {
		return false;
	}
	
	if (!window.zenario_inIframe && !zenarioA.openedInIframe) {
		setTimeout(zenarioA.SKInit, zenarioA.storekeeperInitTime);
	}
};


zenarioA.getCustomFromSK = function(field, path, targetPath, minPath, maxPath, disallowRefinersLoopingOnMinPath) {
	zenarioA.SKTarget = field;
	zenarioA.organizerSelect('zenarioA', 'setCustom', false, path, targetPath, minPath, maxPath, disallowRefinersLoopingOnMinPath);
};

//Handle callback for custom things
zenarioA.setCustom = function(path, key, row) {
	get(zenarioA.SKTarget).value = key.id;
	get('name_for_' + zenarioA.SKTarget).value = row[row.__label_tag__];
};

//Handle callback for a Group
zenarioA.setGroupNameAndId = function(path, key, row) {
	get(zenarioA.SKTarget).value = key.id;
	get('name_for_' + zenarioA.SKTarget).value = row.name;
};

//Handle callback for a user
zenarioA.setUserNameAndId = function(path, key, row) {
	get(zenarioA.SKTarget).value = key.id;
	get('name_for_' + zenarioA.SKTarget).value = row.username;
};

zenarioA.setContentIdAndCType = function(path, key, row, panel) {
	get(zenarioA.SKTarget).value = key.id;
	get('name_for_' + zenarioA.SKTarget).value = zenarioA.formatSKItemName(panel, key.id);
};

zenarioA.removeNameAndId = function(field) {
	get(field).value = '';
	get('name_for_' + field).value = get('nothing_selected_phrase_for_' + field).value;
};



zenarioA.multipleLanguagesEnabled = function() {
	var lang,
		langs = 0;
	
	if (zenarioA.lang) {
		foreach (zenarioA.lang as lang) {
			if (zenarioA.lang[lang].enabled) {
				if (langs++) {
					return true;
				}
			}
		}
	}
	
	return false;
};



//Functions for enabling HTML 5 file-uploads in WYSIWYG Editors and in Organizer Panels

zenarioA.uploading = false;
zenarioA.canDoHTML5Upload = function() {
	return window.FileReader;
};

zenarioA.clearHTML5UploadFromDragDrop = function() {
	delete zenarioA.uploadPathFromDragDrop;
	delete zenarioA.uploadRequestFromDragDrop;
	delete zenarioA.uploadCallBackFromDragDrop;
};

zenarioA.setHTML5UploadFromDragDrop = function(path, request, preCall, callBack, el) {
	if (!zenarioA.canDoHTML5Upload()) {
		return false;
	
	} else {
		zenarioA.uploadPathFromDragDrop = path;
		zenarioA.uploadRequestFromDragDrop = request;
		zenarioA.uploadCallBackFromDragDrop = callBack;
		
		ifNull(el, document.body).addEventListener(
			'drop',
			function(e) {
				if (preCall) {
					preCall();
				}
				zenarioA.doHTML5UploadFromDragDrop(e);
			},
			false);
		
		return true;
	}
};

zenarioA.doHTML5UploadFromDragDrop = function(e) {
	zenarioA.stopFileDragDrop(e);
	zenarioA.doHTML5Upload(
		e.target.files || e.dataTransfer.files,
		zenarioA.uploadPathFromDragDrop,
		zenarioA.uploadRequestFromDragDrop,
		zenarioA.uploadCallBackFromDragDrop);
};

zenarioA.doHTML5Upload = function(files, path, request, callBack) {
	if (!path || !request || zenarioA.uploading) {
		return;
	}
	
	zenarioA.uploadPath = path;
	zenarioA.uploadRequest = request;
	zenarioA.uploadCallBack = callBack;
	zenarioA.uploadResponses = [];
	
	zenarioA.uploadFile = -1;
	zenarioA.uploadFiles = files;
	zenarioA.doNextUpload();
};

zenarioA.doNextUpload = function() {
	
	var $zenario_progress_wrap = $('#zenario_progress_wrap'),
		$zenario_progress_name = $('#zenario_progress_name'),
		$zenario_progress_stop = $('#zenario_progress_stop');
	
	$zenario_progress_name.text('');
	$zenario_progress_stop.unbind('click');
	
	zenarioA.uploading = !!zenarioA.uploadFiles[++zenarioA.uploadFile];
	
	if (zenarioO.init) {
		zenarioO.setWrapperClass('uploading', zenarioA.uploading);
	}
	
	if (zenarioA.uploading) {
		
		$zenario_progress_wrap.show().removeClass('zenario_progress_cancelled');
		
		get('zenario_progressbar').style.width = '0%';
		
		if (!zenarioA.uploadFiles[zenarioA.uploadFile].size || !zenarioA.uploadFiles[zenarioA.uploadFile].name) {
			zenarioA.doNextUpload();
		
		} else if (zenarioA.uploadFiles[zenarioA.uploadFile].size > zenarioA.maxUpload) {
			zenarioA.showMessage(phrase.uploadTooLarge.replace('[[maxUploadF]]', zenarioA.maxUploadF), true, 'error');
			zenarioA.doNextUpload();
		
		} else {
			$zenario_progress_name.text(zenarioA.uploadFiles[zenarioA.uploadFile].name);
			
			zenarioA.uploader = new XMLHttpRequest();  
			zenarioA.uploader.open('POST', zenarioA.uploadPath, true);
			
			try {
				//Try to add a header for the filename
				zenarioA.uploader.setRequestHeader('X_FILENAME', zenarioA.uploadFiles[zenarioA.uploadFile].name);
			} catch (e) {
				//Don't worry if it coudln't be added, it's optional
			}
			
			var data = new FormData();
			foreach (zenarioA.uploadRequest as var k) {
				data.append(k, '' + zenarioA.uploadRequest[k]);
			}
			
			data.append('Filedata', zenarioA.uploadFiles[zenarioA.uploadFile]);
			
			zenarioA.uploader.upload.addEventListener('progress', zenarioA.uploadProgress, false);
			
			zenarioA.uploader.addEventListener('load', zenarioA.uploadDone, false);
			zenarioA.uploader.addEventListener('error', zenarioA.uploadDone, false);
			zenarioA.uploader.addEventListener('abort', zenarioA.uploadDone, false);
			
			$zenario_progress_stop.click(function() {
				$zenario_progress_wrap.show().removeClass('zenario_progress_cancelled');
				zenarioA.uploader.abort();
			});
			
			zenarioA.uploader.send(data);
		}
	
	} else {
		$zenario_progress_wrap.hide();
		
		if (zenarioA.uploadCallBack) {
			zenarioA.uploadCallBack(zenarioA.uploadResponses);
		}
	}
};

zenarioA.uploadProgress = function(e) {
	var completion = 0;
	if (e.lengthComputable) {  
		completion = 100 * e.loaded / e.total;  
	}
	
	get('zenario_progress_wrap').style.display = 'block';
	get('zenario_progressbar').style.width = completion + '%';
};

zenarioA.uploadDone = function(e) {
	
	if (zenarioA.uploader.responseText && zenarioA.uploader.responseText != 1) {
		try {
			data = JSON.parse(zenarioA.uploader.responseText);
		
			if (typeof data != 'object') {
				throw 0;
			}
			
			zenarioA.uploadResponses.push(data);
		
		} catch (e) {
			zenarioA.showMessage(zenarioA.uploader.responseText, true, 'error', false, true);
		}
	}
	
	zenarioA.doNextUpload();
};



zenarioA.debug = function(mode, skMap) {
	window.open(URLBasePath + 'zenario/admin/dev_tools/dev_tools.php?mode=' + encodeURIComponent(mode) + (skMap? '&skMap=1' : '')); return false;
};

//Open the current admin's profile
zenarioA.openProfile = function() {
	//zenarioAB.open = function(path, key, tab, values, callBack, templatePrefix)
	zenarioAB.open(
		'zenario_admin',
		{id: zenario.adminId},
		undefined, undefined,
		function() {
			document.location = document.location;
		}
	);
};


//Functionality for clicking on Menu Nodes. They should:
	//Follow their hyperlinks in preview mode
	//Open a FAB in menu mode
	//Do nothing otherwise
zenarioA.openMenuAdminBox = function(key, openSpecificBox) {
	if (!key) {
		key = {};
	}
	
	if (openSpecificBox) {
		//continue
	
	} else if (zenarioA.pageMode == 'preview') {
		return true;
	
	} else if (zenarioA.pageMode == 'menu' && zenarioA.menuWandOn) {
		openSpecificBox = 'zenario_menu_text';
	
	} else {
		return false;
	}
	
	if (zenario.cID) {
		key.cID = zenario.cID;
		key.cType = zenario.cType;
		key.languageId = zenario.langId;
	}
	
	if (openSpecificBox == 'organizer') {
		//Open an existing Menu Item in Organizer Quick
		var path = zenario.moduleNonAsyncAJAX('zenario_common_features', {getMenuItemStorekeeperDeepLink: key.id, languageId: key.languageId}, true);
		
		if (zenarioA.loggedOut(path)) {
			return;
		}
		
		var object = {
			organizer_quick: {
				path: path,
				target_path: 'zenario__menu/panels/menu_nodes',
				min_path: 'zenario__menu/panels/menu_nodes',
				max_path: 'zenario__menu/panels/menu_nodes',
				disallow_refiners_looping_on_min_path: false,
				reload_menu_slots: true,
				reload_admin_toolbar: true}};
		
		zenarioAT.action(object);
		
	} else {
		//Otherwise open an Admin Box
		zenarioAB.open(openSpecificBox, key);
	}
	
	return false;
};

zenarioA.reloadMenuPlugins = function() {
	$('.zenario_slotShownInMenuMode .zenario_slot').each(function(i, el) {
		if (el.id && el.id.substr(0, 7) == 'plgslt_') {
			var slotName = el.id.substr(7);
			
			//zenario.refreshPluginSlot = function(slotName, instanceId, additionalRequests, recordInURL, scrollToTopOfSlot, fadeOutAndIn, useCache, post) {
			zenario.refreshPluginSlot(slotName, 'lookup', undefined, false, false, false, false, false);
		}
	});
};



//If there is an entry (e.g. "Edit Content") in the actions dropdown that needs to be on a draft,
//this function will create a draft of a published page (after a confirm prompt),
//reload the page, then click the entry again.
zenarioA.draft = function(aId, justView, confirmMessage, confirmButtonText) {
	
	if (zenarioA.draftDoingCallback) {
		delete zenarioA.draftDoingCallback;
		return true;
	
	} else {
		delete zenarioA.draftDoingCallback;
	}
	
	var buttonsHTML,
		object;
	
	//Look for the "create a draft" button on the admin toolbar
	//If we see it, we know this is a published item and we need to create a draft
	if (zenarioAT.tuix
	 && zenarioAT.tuix.sections
	 && zenarioAT.tuix.sections.edit
	 && zenarioAT.tuix.sections.edit.buttons
	 && zenarioAT.tuix.sections.edit.buttons.start_editing
	 && zenarioAT.tuix.sections.edit.buttons.start_editing.ajax
	 && !zenarioA.hidden(zenarioAT.tuix.sections.edit.buttons.start_editing)) {
		
		//Create a copy of it
		object = $.extend(true, {}, zenarioAT.tuix.sections.edit.buttons.start_editing);
		
		delete object.ajax.request.switch_to_edit_mode;
		
		//Should we show someone a warning before creating a draft?
		if (zenarioA.checkSpecificPermsOnThisPage()
		 && zenarioAT.tuix.sections.edit.buttons.start_editing.ajax.confirm) {
			
			//If so, show a confirmation box with up to three options:
			if (confirmMessage) {
				confirmMessage += '\n\n' + object.ajax.confirm.message__editing_published;
			} else {
				confirmMessage = object.ajax.confirm.message__editing_published;
				confirmButtonText = object.ajax.confirm.button_message;
			}
			
			//1. Create the draft, then when the draft has been created press this option again
			buttonsHTML =
				'<input type="button" class="submit_selected" value="' + htmlspecialchars(confirmButtonText) + '" onclick="zenarioA.draftSetCallback(\'' + htmlspecialchars(aId) + '\'); zenarioAT.action2();"/>';
			
			//2. Don't create a draft, press this option again and just view in read-only mode
			if (justView) {
				buttonsHTML +=
					'<input type="button" class="submit" value="' + htmlspecialchars(object.ajax.confirm.button_message__just_view) + '" onclick="zenarioA.draftDoCallback(\'' + htmlspecialchars(aId) + '\');"/>';
			}
			
			//3. Cancel
			buttonsHTML +=
				'<input type="button" class="submit" value="' + htmlspecialchars(object.ajax.confirm.cancel_button_message) + '"/>';
			
			object.ajax.confirm.message = '<!--Button_HTML:' + buttonsHTML + '-->' + confirmMessage;
		
		//Handle the case where we wouldn't normally show a warning before creating a draft,
		//but there was still a confirm message to show
		} else if (confirmMessage) {
			//Note down which button was clicked on.
			//This button will be automatically clicked again after the page is reloaded
			zenarioA.draftSetCallback(aId);
		
			//Remove set a confirm prompt
			object.ajax.confirm = {
				message: confirmMessage,
				button_message: confirmButtonText,
				cancel_button_message: zenarioA.phrase.cancel,
				message_type: 'warning'
			};
			
		
		//If not, create the draft straight away
		} else {
			//Note down which button was clicked on.
			//This button will be automatically clicked again after the page is reloaded
			zenarioA.draftSetCallback(aId);
			
			//Remove any confirm prompt on the button
			delete object.ajax.confirm;
		}
		
		//"Press" the copy of the button we just made
		zenarioA.action(zenarioAT, object);
		return false;
	
	
	//Handle the case where we're already on a draft, but there was still a confirm message to show
	} else if (confirmMessage) {
		buttonsHTML =
			'<input type="button" class="submit_selected" value="' + htmlspecialchars(confirmButtonText) + '" onclick="zenarioA.draftDoCallback(\'' + htmlspecialchars(aId) + '\');"/>';
		buttonsHTML +=
			'<input type="button" class="submit" value="' + zenarioA.phrase.cancel + '"/>';
		
		zenarioA.showMessage(confirmMessage, buttonsHTML, 'warning');
		return false;
	}
	
	return true;
};

zenarioA.savePageMode = function(async, data) {
	if (!data) {
		data = {};
	}
	data._save_page_mode = zenarioA.pageMode;
	data._save_page_toolbar = zenarioA.toolbar;
	data._save_page_slot_wand = zenarioA.slotWandOn? 1 : '';
	data._save_page_show_grid = zenarioA.showGridOn? 1 : '';
	
	$.ajax({
		type: 'POST',
		url: URLBasePath + 'zenario/admin/quick_ajax.php',
		data: data,
		async: async
	});
};

zenarioA.draftSetCallback = function(aId) {
	zenarioA.savePageMode(false, {_draft_set_callback: aId});
};

zenarioA.draftDoCallback = function(aId) {
	zenarioA.draftDoingCallback = true;
	$('#' + aId).click();
	delete zenarioA.draftDoingCallback;
};



//Admin Actions


zenarioA.hidden = function(tuixObject, checkJsFunction, item, id) {
	var c;
	//tuixObject._was_hidden_before = true;
	
	if (engToBoolean(tuixObject.hidden)) {
		return true;
	
	//Don't show a <call_js_function> button or toolbar that calls a function that does not exist
	} else if (checkJsFunction && (tuixObject.call_js_function && !zenarioA.checkFunctionExists(tuixObject.call_js_function['function'], tuixObject.call_js_function.encapsulated_object))) {
		return true;
	
	//Check a JavaScript condition (which used to be called "js_condition" but is now called "visible_if")
	} else if (c = tuixObject.visible_if || tuixObject.js_condition) {
		//try {
			if (!zenarioA.eval(c, tuixObject, item, id)) {
				return true;
			}
		//} catch (e) {
		//	alert(tuixObject.visible_if + '\n\nwas not a valid JavaScript expression.'); 
		//	tuixObject.visible_if = false;
		//}
	}
	
	//delete tuixObject._was_hidden_before;
	return false;
};


//A shortcut to the toastr library
zenarioA.lastToast = false;
zenarioA.clearLastToast = false;
zenarioA.toast = function(object) {
	if (object !== undefined
	 && _.isObject(object)) {
		
		//Remember this toast that we had for the next 60 seconds,
		//or until another toast comes in
		if (zenarioA.clearLastToast) {
			clearTimeout(zenarioA.clearLastToast);
		}
		zenarioA.lastToast = JSON.stringify(object);
		setTimeout(function () {
			zenarioA.lastToast = false;
		}, 60000);
		
		//Work out what type of toast this is
		var mt = object.message_type,
			toast = toastr.info;
		switch (object.message_type) {
			case 'error':
			case 'warning':
			case 'success':
				toast = toastr[mt];
		}
		
		//display the toast
		toast(object.message, object.title, object.options);
		
		//Remember to self: the toast function returns a $jQuery element with the toaster,
		//just in case we ever wanted to do something like add a click event...
	}
};

zenarioA.rememberToast = function() {
	//Check if we just displayed a toast. If so, remember it for next time.
	if (zenarioA.lastToast) {
		zenario.nonAsyncAJAX(URLBasePath + 'zenario/admin/quick_ajax.php', {_remember_toast: zenarioA.lastToast});
	}
};




zenarioA.checkActionUnique = function(object) {
	var actions = [],
		test,
		tests = [
			'admin_box',
			'ajax',
			'call_js_function',
			'combine_items',
			'navigation_path',
			'frontend_link',
			'help',
			'link',
			'panel',
			'pick_items',
			'popout',
			'organizer_quick',
			'upload'];
	
	foreach (tests as test) {
		if (object[tests[test]]) {
			actions.push(tests[test]);
		}
	}
	
	switch (actions.length) {
		case 1:
			return true;
		
		case 0:
			return false;
		
		default:
			console.log(object);
			alert('This navigation or button has multiple actions associated with it:\n\n' + actions + '\n\n(See the console log for the faulty definition.)');
			return false;
	}
};

//Handle what happens when an admin clicks on something that will cause an action; e.g. a button on the admin toolbar or a button on a Organizer Panel's Toolbar
zenarioA.action = function(zenarioCallingLibrary, object, itemLevel, branch, link, extraRequests, specificItemRequested) {
	if (zenarioCallingLibrary.uploading) {
		return false;
	}
	
	//Check to see if there is a unique action on this button
		//(But skip this if we're overriding the functionality with a link)
	if (!link) {
		if (!zenarioA.checkActionUnique(object)) {
			return false;
		}
	}
	
	var ajaxMethodCall;
	if (zenarioCallingLibrary.encapName == 'zenarioO') {
		ajaxMethodCall = 'handleOrganizerPanelAJAX';
	} else if (zenarioCallingLibrary.encapName == 'zenarioAT') {
		ajaxMethodCall = 'handleAdminToolbarAJAX';
	} else if (zenarioCallingLibrary.encapName == 'zenarioW') {
		ajaxMethodCall = 'handleWizardAJAX';
	} else {
		ajaxMethodCall = 'handleAdminBoxAJAX';
	}
	
	if (!link && object.link) {
		link = object.link;
	
	} else if (!link && object.panel && object.panel._path_here) {
		link = {path: object.panel._path_here};
	}
	
	//In select mode, don't let an admin navigate past a certain point using double-click actions on items
	if (link && zenarioO.pathNotAllowed(link)) {
		return;
	}
	
	//Clear the yourWorkInProgressLastUpdated flag so that the list will be immediately updated after any change
	zenarioO.yourWorkInProgressLastUpdated = 0;
	
	zenarioCallingLibrary.pickItemsItemLevel = itemLevel;
	zenarioCallingLibrary.postPickItemsObject = false;
	
	
	//Uploads and AJAX requests need a path and a requests object
	var url, requests, thing;
	if ((thing = object.upload)
	 || (thing = object.ajax)) {
		
		//If an AJAX button requests all of the ids that are currently matched in Organizer,
		//we'll need to get the details of the last Organizer panel accessed (the requests needed
		//should be stored in zenarioO.lastRequests) and fire up the Organizer Panel to get the list of
		//ids.
		if (object.ajax
		 && object.ajax.pass_matched_ids
		 && zenarioCallingLibrary.encapName == 'zenarioO') {
			url =
				'zenario/admin/ajax.php?' +
					'__pluginClassName__=' + thing.class_name +
					'&path=' + zenarioCallingLibrary.path +
					'&_get_ids_for_ajax_request=1' +
					'&method_call=' + ajaxMethodCall +
					zenario.urlRequest(zenarioO.lastRequests)
			//requests = zenarioO.lastRequests;
			requests = {};
		
		//If not then we don't need to use the whole the Organizer Panel logic, we can just use
		//the normal ajax file.
		} else {
			url =
				'zenario/ajax.php?' +
					'__pluginClassName__=' + thing.class_name +
					'&__path__=' + zenarioCallingLibrary.path +
					'&method_call=' + ajaxMethodCall;
		
			requests = zenarioCallingLibrary.getKey(itemLevel);
		}
		
		if (thing.request) {
			$.extend(requests, thing.request);
		}
		
		if (extraRequests) {
			$.extend(requests, extraRequests);
		}
	}
	
	//Handle uploads first, as they need converting to ajax pop-ups for browsers without html5
	if (object.upload) {
		var fallback = !zenarioA.canDoHTML5Upload(),
			html = '<input type="file" name="Filedata"';
		
		if (object.upload.accept) {
			html += ' accept="' + htmlspecialchars(object.upload.accept) + '"';
		
		//Backwards compatibility with old versions
		} else if (object.upload.fileDesc == 'Images') {
			html += ' accept="image/*"';
		}
		
		if (fallback) {
			html += ' id="zenario_fallback_fileupload"';
		}
		
		if (!fallback && engToBoolean(object.upload.multi)) {
			html += ' multiple';
		}
		html += '/>';
		
		if (!fallback) {
			var $input = $(html);
			$input.change(function() {
				
				if (zenarioCallingLibrary.uploadStart) {
					zenarioCallingLibrary.uploadStart();
				}
				
				zenarioA.doHTML5Upload(this.files, URLBasePath + url, requests, function(responses) {
					zenarioCallingLibrary.uploadComplete(responses);
				});
				
			});
			$input.click();
			return;
			
		} else {
			//For backwards compatability for browsers without flash, attempt to convert file upload tags into ajax->confirm->form tags
			object = $.extend(true, {}, object);
			requests._html5_backwards_compatibility_hack = 1;
			
			object.ajax = {
				class_name: object.upload.class_name,
				confirm: {
					message: html,
					html: true,
					button_message: phrase.upload,
					cancel_button_message: phrase.cancel,
					form: true
				},
				request: requests
			};
		}
	}
	
	
	if (object.call_js_function) {
		if (object.call_js_function.encapsulated_object) {
			window[object.call_js_function.encapsulated_object][object.call_js_function['function']](object, zenarioCallingLibrary.getKeyId());
		} else {
			window[object.call_js_function['function']](object, zenarioCallingLibrary.getKeyId());
		}
	
	} else if (link) {
		//If a link is set, go to the link
		if (engToBoolean(link.unselect_items)) {
			zenarioO.selectedItems = {};
			zenarioO.saveSelection();
		}
		
		zenarioO.go(
			link.path,
			branch,
			link.refiner? {'id': itemLevel? zenarioO.getKeyId() : '', 'name': link.refiner} : undefined,
			undefined, undefined, undefined, undefined, specificItemRequested);
	
	} else if (object.frontend_link) {
		var id, item,
			frontend_link = object.frontend_link,
			sameWindow = false;
		
		if (itemLevel && (id = zenarioCallingLibrary.getKeyId(true)) && (item = zenarioCallingLibrary.tuix.items[id]) && item.frontend_link) {
			frontend_link = item.frontend_link;
		}
		
		//For Organizer, attempt to insert the return link,
		//and then open it in the same window if we successfully inserted it
		if (zenarioCallingLibrary.encapName == 'zenarioO') {
			frontend_link = zenarioO.parseReturnLink(frontend_link);
			
			if (frontend_link.indexOf('&zenario_sk_return=') != -1) {
				sameWindow = true;
			}
		}
		
		if (sameWindow || frontend_link.substr(0, 25) == 'zenario/admin/welcome.php') {
			zenario.goToURL(zenario.addBasePath(frontend_link));
		
		//If there is a prototal (e.g. http://) in the URL, open it in a new window, unless it is a link
		//to the current site
		} else if (frontend_link.indexOf('://') !== -1 && frontend_link.indexOf(URLBasePath) !== 0) {
			window.open(frontend_link);
		
		} else if (zenarioCallingLibrary.encapName == 'zenarioAT') {
			zenario.goToURL(zenario.addBasePath(frontend_link));
		
		} else if (windowOpener && !windowOpener.zenarioO) {
			window.opener.location = zenario.addBasePath(frontend_link);
		
		} else if (window.storekeeperChildWindow && !window.storekeeperChildWindow.closed) {
			window.storekeeperChildWindow.location = zenario.addBasePath(frontend_link);
		
		} else {
			window.storekeeperChildWindow = window.open(zenario.addBasePath(frontend_link));
		}
	
	} else if (object.navigation_path) {
		var id, item, pos,
			navigation_path = object.navigation_path;
		
		if (itemLevel && (id = zenarioCallingLibrary.getKeyId(true)) && (item = zenarioCallingLibrary.tuix.items[id]) && item.navigation_path) {
			navigation_path = item.navigation_path;
		}
		
		if (zenarioCallingLibrary.encapName == 'zenarioO') {
			zenarioO.go(navigation_path, -1);
		} else {
			zenario.goToURL(zenario.addBasePath(
				(window.zenarioATLinks && window.zenarioATLinks.organizer || 'zenario/admin/organizer.php') +
				'#' +
				navigation_path
			));
		}
	
	} else if (object.admin_box) {
		zenarioA.nowDoingSomething('loading');
		var key = ifNull(zenarioCallingLibrary.getKey(itemLevel), {});
		
		if (object.admin_box.key) {
			foreach (object.admin_box.key as var r) {
				key[r] = object.admin_box.key[r];
			}
		}
		
		zenarioAB.open(
			object.admin_box.path,
			key,
			object.admin_box.tab,
			object.admin_box.values,
			undefined,
			engToBoolean(object.admin_box.create_another)? object.admin_box : false);
	
	} else if (object.popout) {
		var id, item, title, usage,
			filename, match,
			popout = $.extend(true, {}, object.popout);
		
		if (itemLevel && (id = zenarioCallingLibrary.getKeyId(true)) && (item = zenarioCallingLibrary.tuix.items[id])) {
			if (item.popout) {
				popout = $.extend(popout, item.popout);
			}
			
			if (!popout.title && zenarioCallingLibrary.popoutLabelFormat) {
				popout.title = zenarioCallingLibrary.applyMergeFields(zenarioCallingLibrary.popoutLabelFormat, false, id);
			}
		}
			
		if (item && item.href && popout.href === undefined) {
			popout.href = item.href;
		
		} else if (item && item.frontend_link && popout.href === undefined) {
			popout.href = zenarioO.parseReturnLink(item.frontend_link, '_sk_preview=1');
		
		} else if (item && popout.href) {
			popout.href += popout.href.indexOf('?') === -1? '?' : '&';
			
			if (item.checksum) {
				popout.href += 'c=' + encodeURIComponent(item.checksum);
				
				if (id == 1*id) {
					popout.href += '&id=' + id;
				}
			
			} else if (id == 1*id) {
				popout.href += 'id=' + id;
			
			} else {
				popout.href += 'c=' + encodeURIComponent(id);
			}
			
			if (usage = ifNull(zenarioCallingLibrary.getKey().usage, item.usage)) {
				popout.href += '&usage=' + encodeURIComponent(usage);
			}
		}
		
		if (popout.href) {
			if (filename = (item && item.filename) || (popout.options && popout.options.filename) || popout.filename) {
				
				//If the short checksum has been added to the end of the filename, we need to strip it off
				//as colorbox uses the filename to detect the mimetype of the object
				if (match = filename.match(/(.*) \[.*\]/)) {
					filename = match[1];
				}
				
				popout.href += '&filename=' + encodeURIComponent(filename);
			}
			
			popout.href = zenario.addBasePath(popout.href);
		}
		
		if (item && item.width) {
			popout.initialWidth = item.width;
			//popout.innerWidth = item.width;
			//popout.initialWidth = 1*item.width + (zenarioA.colorboxInterfaceWidth = 72)
		}
		if (item && item.height) {
			popout.initialHeight = item.height;
			//popout.innerHeight = item.height;
			//popout.initialHeight = 1*item.height + (zenarioA.colorboxInterfaceHeight = 72)
		}
		
		if (popout.css_class) {
			var cssClasses = ('' + popout.css_class).split(' ');
			popout.onOpen = function() { zenario.addClassesToColorbox(cssClasses); };
			popout.onClosed = function() { zenario.removeClassesToColorbox(cssClasses); };
		}
		
		if (popout.iframe && popout.width === undefined) {
			popout.width = Math.min(Math.floor($(window).width() * 0.93), 1200);
		}
		if (popout.iframe && popout.height === undefined) {
			popout.height = Math.floor($(window).height() * 0.9);
		}
		
		if (popout.preloading === undefined) {
			popout.preloading = false;
		}
		
		$.colorbox(popout);
	
	} else if (object.organizer_quick) {
		var id,
			path = object.organizer_quick.path;
		
		if (itemLevel && (id = zenarioCallingLibrary.getKeyId(true))) {
			path = path.replace(/\[\[id\]\]/g, id);
		}
		
		//zenarioA.organizerQuick = function(path, targetPath, minPath, maxPath, disallowRefinersLoopingOnMinPath, slotName, instanceId, reloadOnChanges, wrapperCSSClass)
		zenarioA.organizerQuick(
			path,
			
			//We don't need the target path variable when opening like this, but as a little hack I'll
			//fill it with something anyway.
			object.organizer_quick.min_path || object.organizer_quick.max_path || object.organizer_quick.path,
			
			object.organizer_quick.min_path, object.organizer_quick.max_path,
			engToBoolean(object.organizer_quick.disallow_refiners_looping_on_min_path),
			undefined, undefined, zenarioCallingLibrary.encapName);
	
	} else if (object.pick_items && !itemLevel) {
		
		if (object.pick_items.ajax) {
			zenarioCallingLibrary.postPickItemsObject = object.pick_items;
		}
		
		//Use Organizer in select mode to combine two items
		zenarioCallingLibrary.actionTarget =
			'zenario/ajax.php?' +
				'__pluginClassName__=' + object.pick_items.class_name +
				'&__path__=' + zenarioCallingLibrary.path +
				'&method_call=' + ajaxMethodCall;
		zenarioCallingLibrary.actionRequests = zenarioCallingLibrary.getKey(itemLevel);
		
		if (object.pick_items.request) {
			$.extend(zenarioCallingLibrary.actionRequests, object.pick_items.request);
		}
		
		zenarioA.organizerSelect(
			zenarioCallingLibrary.encapName, 'pickItems',
			object.pick_items.multiple_select,
			object.pick_items.path,
			object.pick_items.target_path,
			object.pick_items.min_path,
			object.pick_items.max_path,
			object.pick_items.disallow_refiners_looping_on_min_path,
			true,
			object.pick_items.one_to_one_choose_phrase,
			object.pick_items.one_to_many_choose_phrase,
			undefined, undefined, undefined, undefined, undefined,
			zenarioCallingLibrary.getLastKeyId(true),
			object.pick_items.allow_no_selection,
			object.pick_items.one_to_no_selection_choose_phrase,
			undefined, undefined,
			object.pick_items);
	
	} else if (object.combine_items && itemLevel) {
		
		if (object.combine_items.ajax) {
			zenarioCallingLibrary.postPickItemsObject = object.combine_items;
		}
		
		//Use Organizer in select mode to combine two items
		zenarioCallingLibrary.actionTarget =
			'zenario/ajax.php?' +
				'__pluginClassName__=' + object.combine_items.class_name +
				'&__path__=' + zenarioCallingLibrary.path +
				'&method_call=' + ajaxMethodCall;
		zenarioCallingLibrary.actionRequests = zenarioCallingLibrary.getKey(itemLevel);
		
		if (object.combine_items.request) {
			$.extend(zenarioCallingLibrary.actionRequests, object.combine_items.request);
		}
		
		zenarioA.organizerSelect(
			zenarioCallingLibrary.encapName, 'pickItems',
			object.combine_items.multiple_select,
			object.combine_items.path,
			object.combine_items.target_path,
			object.combine_items.min_path,
			object.combine_items.max_path,
			object.combine_items.disallow_refiners_looping_on_min_path,
			true,
			zenarioCallingLibrary.itemsSelected > 1 && object.combine_items.many_to_one_choose_phrase?
				object.combine_items.many_to_one_choose_phrase
			  : object.combine_items.one_to_one_choose_phrase,
			zenarioCallingLibrary.itemsSelected > 1 && object.combine_items.many_to_many_choose_phrase?
				object.combine_items.many_to_many_choose_phrase
			  : object.combine_items.one_to_many_choose_phrase,
			undefined, undefined, undefined, undefined, undefined,
			zenarioCallingLibrary.getKeyId(false),
			object.combine_items.allow_no_selection,
			zenarioCallingLibrary.itemsSelected > 1 && object.combine_items.many_to_no_selection_choose_phrase?
				object.combine_items.many_to_no_selection_choose_phrase
			  : object.combine_items.one_to_no_selection_choose_phrase,
			undefined, undefined,
			object.combine_items);
	
	} else if (object.ajax) {
		
		//Run an AJAX function
		zenarioCallingLibrary.actionTarget = url;
		zenarioCallingLibrary.actionRequests = requests;
		
		//If a confirm is set, set up a pop-up floating box box to ask the admin before carrying out the action
		if (object.ajax.confirm) {
			
			var isHTML = engToBoolean(object.ajax.confirm.html);
			var isDownload = engToBoolean(object.ajax.confirm.download);
			
			//Get the message/html for this box
			var message;
			
			if (object.ajax.confirm.message) {
				if (object.upload) {
					//Part of the backwards compatability hack for browsers without HTML 5 uploads above
					message = object.ajax.confirm.message;
			
				} else {
					//Otherwise apply any merge fields to the label from the calling library
					message = zenarioCallingLibrary.applyMergeFieldsToLabel(
						object.ajax.confirm.message,
						isHTML, itemLevel,
						object.ajax.confirm.multiple_select_message
					);
				}
			
			//If no message is set, try and get one using an AJAX call
			} else {
				message = zenario.nonAsyncAJAX(URLBasePath + zenarioCallingLibrary.actionTarget + zenario.urlRequest(zenarioCallingLibrary.actionRequests), false);
			}
			
			//If this is a download, add the current search/sorting information in,
			//just in case the download should differ depending on the current view
			if (isDownload) {
				zenarioCallingLibrary.actionRequests._download = 1;
				
				if (zenarioCallingLibrary.searchTerm !== undefined) {
					zenarioCallingLibrary.actionRequests._search = zenarioCallingLibrary.searchTerm;
				}
				
				if (zenarioCallingLibrary.prefs[zenarioCallingLibrary.path] && zenarioCallingLibrary.prefs[zenarioCallingLibrary.path].sortBy) {
					zenarioCallingLibrary.actionRequests._sort_col = zenarioCallingLibrary.prefs[zenarioCallingLibrary.path].sortBy;
					zenarioCallingLibrary.actionRequests._sort_desc = zenarioCallingLibrary.prefs[zenarioCallingLibrary.path].sortDesc? 1 : 0;
				} else {
					zenarioCallingLibrary.actionRequests._sort_col = zenarioCallingLibrary.labelTag;
				}
			}
			
			//Start generating the box.
			//If there is a form, the message should be surrounded by <form></form> tags.
			var html = '';
			var buttonsHTML = '';
			if (isDownload || object.upload) {
				html += 
					'<form id="zenario_bc_form" action="' + htmlspecialchars(URLBasePath + zenarioCallingLibrary.actionTarget + '&_sk_form_submission=1') + '"' +
						' onsubmit="get(\'preloader_circle\').style.visibility = \'visible\';"' +
						' target="zenario_iframe" method="post"' + (object.upload? ' enctype="multipart/form-data"' : '') + '>';
				
				foreach (zenarioCallingLibrary.actionRequests as var r) {
					html += '<input type="hidden" value="' + htmlspecialchars(zenarioCallingLibrary.actionRequests[r]) + '" name="' + htmlspecialchars(r) + '"/>';
				}
				
				if (!isHTML) {
					message = htmlspecialchars(message, true);
					isHTML = true;
				}
			}
			
			html += message;
			
			//If there is a form, the confirm button should submit the form...
			if (isDownload || object.upload) {
				html += '</form>';
				
				buttonsHTML =
					'<input type="button" class="submit_selected" value="' + htmlspecialchars(object.ajax.confirm.button_message) + '" onclick="get(\'zenario_bc_form\').submit();"/>';
			//...otherwise it should launch a syncronous AJAX request.
			} else {
				buttonsHTML =
					'<input type="button" class="submit_selected" value="' + htmlspecialchars(object.ajax.confirm.button_message) + '" onclick="' + zenarioCallingLibrary.encapName + '.action2();"/>';
			}
			
			buttonsHTML +=
				'<input type="button" class="submit" value="' + htmlspecialchars(object.ajax.confirm.cancel_button_message) + '"/>';
			
			
			zenarioA.showMessage(html, buttonsHTML, object.ajax.confirm.message_type, undefined, !isHTML);
			
			//If this was a fileupload in fallback mode, try to click the fileupload prompt straight away...
			if (object.upload) {
				//...except on IE, where this causes an error :(
				if (!zenario.browserIsIE()) {
					$('#zenario_fallback_fileupload').click();
				}
			}
		
		//If there is no confirmation then do the action straight away
		} else {
			zenarioCallingLibrary.action2();
		}
		
	
	} else if (object.help) {
		var messageType = ifNull(object.help.message_type, 'question'),
			htmlEscapeMessage = !engToBoolean(object.help.html);
		
		if (object.help.message) {
			zenarioA.showMessage(object.help.message, true, messageType, false, htmlEscapeMessage);
		}
	}
};

zenarioA.isHtaccessWorking = function() {
	return zenario.nonAsyncAJAX(URLBasePath + 'zenario/includes/test_files/is_htaccess_working.txt', true) == 'Yes';
};

zenarioA.checkFunctionExists = function(functionName, encapName) {
	if (encapName) {
		return window[encapName] && typeof window[encapName][functionName] == 'function';
	} else {
		return typeof window[functionName] == 'function';
	}
};


//Given an image size and a target size, resize the image (maintaining aspect ratio).
//This is a copy of the resizeImage() function in cms.inc.php, to ensure consistent logic
//when generating a thumbnail in JavaScript
zenarioA.resizeImage = function(image_width, image_height, constraint_width, constraint_height, out, allowUpscale) {
	out.width = image_width;
	out.height = image_height;
	image_width = 1*image_width;
	image_height = 1*image_height;
	
	if (image_width == constraint_width && image_height == constraint_height) {
		return;
	}
	
	if (!allowUpscale && (image_width <= constraint_width) && (image_height <= constraint_height)) {
		return;
	}

	if ((constraint_width / image_width) < (constraint_height / image_height)) {
		out.width = constraint_width;
		out.height = Math.floor(image_height * constraint_width / image_width);
	} else {
		out.height = constraint_height;
		out.width = Math.floor(image_width * constraint_height / image_height);
	}

	return;
};




//Utility function for the zenarioXX.sortYYY() series of function
//Given two arrays (each of which represents an element), say which should be first
zenarioA.sortArray = function(a, b) {
	return zenarioA.sortLogic(a, b, 1);
};

zenarioA.sortArrayByOrd = function(a, b) {
	return zenarioA.sortLogic(a, b, 'ord');
};

zenarioA.sortArrayByOrdinal = function(a, b) {
	return zenarioA.sortLogic(a, b, 'ordinal');
};

zenarioA.sortArrayWithGrouping = function(a, b) {
	
	//Both fields are in the same grouping, or neither field is in a grouping.
	if (a[2] === b[2]) {
		//Check their ordinal normally
		return zenarioA.sortLogic(a, b, 1);
	
	//Field a is not in a grouping, but field b is
	} else if (a[2] === undefined) {
		//a's ordinal should be checked against the ordinal of b's grouping
		return zenarioA.sortLogic(a, b, 1, 2);
	
	//Field b is not in a grouping, but field a is
	} else {
		//b's ordinal should be checked against the ordinal of a's grouping
		return zenarioA.sortLogic(a, b, 2, 1);
	}
};

zenarioA.sortLogic = function(a, b, propA, propB) {
	
	var vA = a[propA], vB;
	
	if (propB === undefined) {
		vB = b[propA];
	} else {
		vB = b[propB];
	}
	
	//Check to see if they're identical
	if (vA === vB) {
		return 0;
	
	} else {
		var aNumeric = vA == 1*vA,
			bNumeric = vB == 1*vB;
	
		//Try a numeric comparision
		if (aNumeric && bNumeric) {
			return 1*vA < 1*vB? -1 : 1;
	
		//Put any numeric values before strings
		} else if (aNumeric || bNumeric) {
			return aNumeric? -1 : 1;
		
		//Otherwise try a string comparision
		} else {
			return ('' + vA).toUpperCase() < ('' + vB).toUpperCase()? -1 : 1;
		}
	}
};



zenarioA.readData = function(data, setSessionStorageURL, setSessionStorageRequest, retry) {
	try {
		data = JSON.parse(data);
		
		if (typeof data != 'object') {
			throw 0;
		}
	} catch (e) {
		//Display an error message if the data couldn't be parsed
		
		if (retry) {
			var buttonsHTML =
				'<input id="zenario_retry" class="submit_selected" type="button" value="' + phrase.retry + '"/>';
			
			zenarioA.nowDoingSomething();
			zenarioA.showMessage(data, buttonsHTML, 'error', true, true)
			
			$('#zenario_retry').click(function() {
				setTimeout(retry, 1);
			});
			
		} else {
			zenarioA.showMessage(data, true, 'error', false, true);
			//zenarioA.showMessage(message, buttonsHTML, messageType, modal, htmlEscapeMessage)
		}
		
		//Close the AJAX loader if it was open
		zenarioA.hideAJAXLoader();
		
		return false;
	}
	
	if (setSessionStorageURL) {
		zenario.setSessionStorage(data, setSessionStorageURL, setSessionStorageRequest, true);
	}
	
	return data;
};


//Default error handler for lost AJAX requests
	//Note that there have been some issues with AJAX request submitted just before a page navigation being counted as timeouts
	//To get round this, firstly we try to detect page navigation and then don't show a message after this has happened
	//Secondly there's a slight time delay inserted on the 404 errors using setTimeout(), as a work around to try and prevent any race-conditions

zenarioA.AJAXErrorHandler = function(resp, statusType, statusText) {
	
	if (!zenarioA.unloaded) {
		var msg = '',
			fun,
			isDev = zenarioA.adminSettings.show_dev_tools;
		
		if (statusText) {
			msg += '<h1><b>' + htmlspecialchars(resp.status + ' ' + statusText) + '</b></h1>';
		}
		
		if (resp.status == 404) {
			msg += '<p>' +  (isDev? phrase.error404Dev : phrase.error404) + '</p>';
		
		} else if (resp.status == 500) {
			msg += '<p>' +  (isDev? phrase.error500Dev : phrase.error500) + '</p>';
		
		} else if (resp.status == 0 || statusType == 'timeout') {
			msg += '<p>' +  (isDev? phrase.errorTimedOutDev : phrase.errorTimedOut) + '</p>';
		}
		
		if (resp.responseText) {
			msg += '<div>' + resp.responseText + '</div>';
		}
		
		msg +=
			'<p style="display: none;">' +
				'Last URL accessed: ' +
				htmlspecialchars(zenario.checkLastUrl) +
			'</p>';
		
		
		showErrorMessage = function() {
			if (resp.zenario_retry) {
				var buttonsHTML =
					'<input id="zenario_retry" class="submit_selected" type="button" value="' + phrase.retry + '"/>';
				
				zenarioA.nowDoingSomething();
				zenarioA.showMessage(msg, buttonsHTML, 'error', true)
				
				$('#zenario_retry').click(function() {
					setTimeout(resp.zenario_retry, 1);
				});
				
			} else {
				zenarioA.showMessage(msg, '', 'error');
			}
		}
		
		if (resp.status == 0 || statusType == 'timeout') {
			setTimeout(showErrorMessage, 750);
		} else {
			showErrorMessage();
		}
	}
};
$.ajaxSetup({error: zenarioA.AJAXErrorHandler});

zenarioA.attempts = {};
zenarioA.attemptNum = 0;
zenarioA.keepTrying = function(fun, attempt) {
	if (attempt === undefined) {
		attempt = ++zenarioA.attemptNum;
		zenarioA.attempts[attempt] = true;
	
	} else if (!zenarioA.attempts[attempt]) {
		return;
	}
	
	fun(attempt);
	
	setTimeout(function() {
		zenarioA.keepTrying(fun, attempt);
	}, 30000);
};

zenarioA.stopTrying = function(attempt) {
	if (!zenarioA.attempts[attempt]) {
		return false;
	
	} else {
		delete zenarioA.attempts[attempt];
		return true;
	}
};



zenarioA.onunload = function(e) {
	if (!window.onpagehide || !e.persisted) {
		zenarioA.unloaded = true;
		zenarioA.closeFloatingBox();
	}
};

if (window.onpagehide) {
	window.addEventListener("pagehide", zenarioA.onunload, false);
} else {
	window.onunload = zenarioA.onunload;
}


zenarioA.stopDefault = function(e) {
	e = (e || event);
	
	if (e && e.stopPropagation) {
		e.stopPropagation();
	}
	if (e && e.preventDefault) {
		e.preventDefault();
	}
	
	return false;
};


//Try to disable the ability to navigate away from the page by dragging a file or an image into the browser from the filesystem
zenarioA.stopFileDragDrop = function(e) {
	var fileUpload = false;
	if (e.dataTransfer && e.dataTransfer.types) {
		if (typeof e.dataTransfer.types.contains == 'function') {
			fileUpload = e.dataTransfer.types.contains('Files');
		
		} else {
			fileUpload = (('' + e.dataTransfer.types) == 'Files') || (e.dataTransfer.types[0] && ('' + e.dataTransfer.types[0]) == 'Files');
		}
	}
	
	if (fileUpload) {
		e.stopPropagation();
		e.preventDefault();
	}
};

zenarioA.disableFileDragDrop = function(el) {
	if (zenarioA.canDoHTML5Upload()) {
		el.addEventListener('drop', zenarioA.stopDefault, false);
		el.addEventListener('dragenter', zenarioA.stopDefault, false);
		el.addEventListener('dragover', zenarioA.stopDefault, false);
		el.addEventListener('dragexit', zenarioA.stopDefault, false);
	}
};

//Check whether cookies are enabled and able to be used.
//Because of the way PHP/browsers/cookies work, if this is the first access it might take one
//attempt to initialise, and then only on the second attempt will the result return true.
//To get round this we'll try up to two times.
zenarioA.checkCookiesEnabled = function() {
	var url = URLBasePath + 'zenario/cookies.php?check_cookies_enabled=1&no_cache=1',
		cb = new zenario.callback;
	
	zenario.ajax(url).after(function(result) {
		if (result) {
			cb.call(result);
		} else {
			zenario.ajax(url).after(function(result) {
				cb.call(result);
			});
		}
	});
	
	return cb;
};

$(document).ready(function() {
	zenarioA.disableFileDragDrop(document.body);
});

//If any slots are being edited, set a warning message for if an admin tries to leave the page 
window.onbeforeunload = zenarioA.onbeforeunload;




});
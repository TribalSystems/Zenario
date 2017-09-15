/*
 * Copyright (c) 2017, Tribal Limited
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
	extraVar1, extraVar2, s$s
) {
	"use strict";




zenarioA.toolbar = 'preview';


zenarioAT.setURL = function() {
	return zenarioAT.url = URLBasePath +
		'zenario/admin/admin_toolbar.ajax.php' +
		'?get=' + encodeURIComponent(JSON.stringify(zenarioA.importantGetRequests)) +
		zenario.urlRequest(zenarioAT.getKey());
};

zenarioAT.runOnInit = [];
zenarioAT.init = function(firstLoad) {
	
	if (firstLoad && zenarioAT.loadedBefore) {
		return;
	}
	zenarioAT.loaded = false;
	zenarioAT.loadedBefore = true;
	
	//zenario.ajax(url, post, json, useCache, retry, continueAnyway, settings, timeout, AJAXErrorHandler, onRetry, onCancel)
	zenario.ajax(zenarioAT.setURL(), false, true, true, true, true, undefined, 7500).after(zenarioAT.init2);
};


zenarioAT.init2 = function(tuix) {
	
	//This $(document).ready() is to ensure that this function runs after everything in visitor.ready.js
	$(document).ready(function() {
		
		zenarioAT.setURL();
		zenarioAT.tuix = tuix;
	
	
		zenarioAT.sort();
		/*zenarioAT.drawToolbarTabs();
	
		$('#zenario_toolbar').clearQueue();
		$('#zenario_toolbar').fadeOut(0, function() {
			zenarioAT.drawToolbar();
			$('#zenario_toolbar').clearQueue();
			$('#zenario_toolbar').fadeIn(100);
		});
		*/
		zenarioAT.draw();
		zenarioAT.loaded = true;
		zenarioAT.loadedBefore = true;
	
		foreach (zenarioAT.runOnInit as var i) {
			zenarioAT.runOnInit[i]();
		}
		zenarioAT.runOnInit = [];
		
		if (tuix.lock_warning) {
			zenarioA.lockWarning(tuix.lock_warning);
		}
	});
};

//This used to send an AJAX request to load the Admin Toolbar when the page was ready
//It was replaced by a script tag in CMSWritePageFoot().
//$(document).ready(function() {
//	zenarioAT.init(true);
//});


zenarioAT.clickTab = function(toolbar) {
	if (zenarioA.checkForEdits()) {
		if (zenarioAT.tuix && zenarioAT.tuix.toolbars && zenarioAT.tuix.toolbars[toolbar]) {
			zenarioA.closeSlotControls();
			zenarioA.cancelMovePlugin();
			
			zenarioAT.action(zenarioAT.tuix.toolbars[toolbar]);
			
			var oldPageMode = zenarioAT.tuix.toolbars[zenarioA.toolbar] && zenarioAT.tuix.toolbars[zenarioA.toolbar].page_mode || zenarioA.toolbar,
				newPageMode = zenarioAT.tuix.toolbars[toolbar].page_mode || toolbar,
				toolbarSubstr = toolbar.substr(0, 4),
				sbcFun = zenarioSBC,
				testPageMode,
				possiblePageModes = {
					preview: 0,
					edit_disabled: 0,
					edit: 0,
					rollback: 0,
					item: 0,
					menu: 0,
					layout: 0
				};
			
			for (testPageMode in possiblePageModes) {
				sbcFun(newPageMode == testPageMode, 'zenario_pageMode_' + testPageMode, 'zenario_pageModeIsnt_' + testPageMode);
			}
			
			//Show empty slots on the item/layout tabs
			sbcFun(newPageMode == 'item' || newPageMode == 'layout', 'zenario_slotWand_on', 'zenario_slotWand_off');
			
			//For layouts, add the old class name for this for backwards compatability
			sbcFun(newPageMode == 'layout', 'zenario_pageMode_template', 'zenario_pageModeIsnt_template');
			
			//Show empty slots on the item/layout tabs
			if (newPageMode == 'item' || newPageMode == 'layout') {
				$('body').addClass('zenario_slotWand_on').removeClass('zenario_slotWand_off');
			} else {
				$('body').addClass('zenario_slotWand_off').removeClass('zenario_slotWand_on');
			}
			
			//Toggle the Grid on the item/layout tabs
			if ((newPageMode == 'item' || newPageMode == 'layout') && zenarioA.showGridOn) {
				zenarioAT.showGridOnOff(true);
			} else {
				zenarioAT.showGridOnOff(false);
			}
			
			zenarioA.toolbar = toolbar;
			zenarioA.pageMode = newPageMode;
			zenarioA.savePageMode(true);
			
			//zenarioAT.drawToolbarTabs();
			//zenarioAT.drawToolbar();
			zenarioAT.draw();
		}
	}
};

zenarioAT.gridOverlayDiv = null;

zenarioAT.showGridOnOff = function(modeOn) {
	if(modeOn){
		if(!zenarioAT.gridOverlayDiv) {
			var base_grid_el = false; 
			$('.container').each(function(i, el) { base_grid_el = el; });
			if(base_grid_el) {
				var base_grid_class = base_grid_el.className;
				var col_count = base_grid_class.match(/container_(\d+)/);
				if(col_count) {
					col_count = col_count[1];
				} else {
					col_count = 0;
				}
				var myroot = document.getElementById('zenario_citem');
				var bootstrapRow = myroot.firstElementChild;
				bootstrapRow = bootstrapRow ? bootstrapRow.firstElementChild : false;
				var isBootstrap = (bootstrapRow && bootstrapRow.className == 'row') ? true : false;
				
				var overlay_grid = isBootstrap ? '<div class="row">' : '';
				for(var i=1; i <= col_count; ++i){
					overlay_grid += '<div class="span span1 span1_' + col_count;
					if(i==1) overlay_grid += ' alpha';
					else if(i==col_count) overlay_grid += ' omega';
					overlay_grid += ' slot"></div>';
				}
				overlay_grid += isBootstrap ? '</div>' : '';
				
				var overlay_div = document.createElement('div');
				overlay_div.innerHTML = overlay_grid;
				overlay_div.style.position = 'relative';
				overlay_div.className = 'zenario_grid_overlay_view ' + base_grid_class;
				overlay_div.style.top = '-' + myroot.offsetHeight+"px";
				overlay_div.style.height = myroot.offsetHeight+"px";
	
				myroot.style.position = 'relative';
				myroot.appendChild(overlay_div);
				myroot.offsetHeight = overlay_div.offsetHeight + 'px';
				zenarioAT.gridOverlayDiv = overlay_div;
			}
		}
	} else {
		if(zenarioAT.gridOverlayDiv) {
			zenarioAT.gridOverlayDiv.remove();
			delete zenarioAT.gridOverlayDiv;
		}
	}
};

zenarioAT.clickButton = function(sectionId, button) {
	if (zenarioA.checkForEdits()) {
		zenarioAT.action(zenarioAT.tuix.sections[sectionId].buttons[button]);
	}
};

zenarioAT.action = function(object) {
	zenarioA.closeSlotControls();
	zenarioA.cancelMovePlugin();
	
	if (!zenarioT.checkActionUnique(object)) {
		return false;
	}
	
	if (object.organizer_quick) {
		var instances = '', foundInstances = {}, firstSlotName = false;
		
		if (object.organizer_quick.reload_slot
		 && zenario.slots[object.organizer_quick.reload_slot]
		 && zenario.slots[object.organizer_quick.reload_slot].instanceId) {
			firstSlotName = object.organizer_quick.reload_slot;
			instances += zenario.slots[object.organizer_quick.reload_slot].instanceId;
			foundInstances[zenario.slots[object.organizer_quick.reload_slot].instanceId] = true;
		}
		
		if (engToBoolean(object.organizer_quick.reload_menu_slots)) {
			$('.zenario_showSlotInMenuMode .zenario_slot').each(function(i, el) {
				if (el.id && el.id.substr(0, 7) == 'plgslt_') {
					var slotName = el.id.substr(7);
					if (zenario.slots[slotName] && zenario.slots[slotName].instanceId) {
						if (!foundInstances[zenario.slots[slotName].instanceId]) {
						
							if (!firstSlotName) {
								firstSlotName = slotName;
							} else {
								instances += ',';
							}
							
							instances += zenario.slots[slotName].instanceId;
							foundInstances[zenario.slots[slotName].instanceId] = true;
						}
					}
				}
			});
		}
		
		zenarioA.organizerQuick(
			object.organizer_quick.path, object.organizer_quick.target_path,
			object.organizer_quick.min_path, object.organizer_quick.max_path,
			engToBoolean(object.organizer_quick.disallow_refiners_looping_on_min_path),
			firstSlotName, instances,
			engToBoolean(object.organizer_quick.reload_admin_toolbar)? 'zenarioAT' : false);
		
	} else {
		zenarioT.action(zenarioAT, object);
	}
};


zenarioAT.getKey = function(itemLevel) {
	return {
		id: zenario.cType + '_' + zenario.cID,
		cID: zenario.cID,
		cType: zenario.cType,
		cVersion: zenario.cVersion
	};
};

zenarioAT.getKeyId = function(limitOfOne) {
	return zenario.cType + '_' + zenario.cID;
};

zenarioAT.getLastKeyId = function(limitOfOne) {
	return zenarioAT.getKeyId(limitOfOne);
};

zenarioAT.applyMergeFields = function(string, escapeHTML, i, keepNewLines) {
	return string;
};

zenarioAT.applyMergeFieldsToLabel = function(label, isHTML, itemLevel, multiSelectLabel) {
	return label;
};



zenarioAT.pickItems = function(path, keyIn, row) {
	
	//Remove any "parent__" paramaters from the key, which are going to be just dupicated here
	var key = {};
	foreach (keyIn as var i) {
		if (i == 'id') {
			key[i] = keyIn[i];
		} else if (i.substr(0, 8) != 'parent__') {
			key['child__' + i] = keyIn[i];
		}
	}
	
	if (zenarioAT.postPickItemsObject) {
		zenarioT.action(zenarioAT, zenarioAT.postPickItemsObject, true, undefined, undefined, key);
	
	} else if (zenarioAT.actionTarget) {
		foreach (key as var k) {
			zenarioAT.actionRequests[k] = key[k];
		}
		
		zenarioAT.action2();
	}
	
	zenarioAT.postPickItemsObject = false;
};

zenarioAT.goNum = 0;
zenarioAT.action2 = function() {
	if (zenarioAT.actionTarget) {
		//Number each request that is made, so we can tell which ones are outdated
		var goNum = ++zenarioAT.goNum;
		zenarioA.nowDoingSomething('saving');
		
		
		zenario.ajax(zenarioAT.actionTarget, zenarioAT.actionRequests, false, false, true).after(function(message) {
			//Check that this isn't an out-of-date request that has come in syncronously via AJAX
			if (goNum != zenarioAT.goNum) {
				return;
			}
			
			zenarioA.nowDoingSomething(false);

			if (message) {
				//Either show a message...
				zenarioA.showMessage(message);
			} else {
				//...or refresh the page
				zenario.goToURL(zenario.linkToItem(zenario.cID, zenario.cType, zenarioA.importantGetRequests));
			}
		});
	}
	zenarioAT.actionTarget = false;
	delete zenarioAT.actionRequests;
};

zenarioAT.uploadComplete = function() {
	//Do nothing..?
};





zenarioAT.draw = function() {
	var html = '',
		toolbar = {
			tabs: [],
			sections: {}
		},
		ti = -1,
		tuix = zenarioAT.tuix,
		sectionId,
		section;
	
	//Loop through the toolbars, adding a tab for each
	foreach (zenarioAT.sortedToolbars as var i) {
		var id = zenarioAT.sortedToolbars[i],
			tab = tuix.toolbars[id];
		
		//zenarioT.hidden(tuixObject, lib, item, id, button, column, field, section, tab, tuix)
		if (!zenarioT.hidden(undefined, zenarioAT, undefined, id, undefined, undefined, undefined, undefined, tab)) {
			
			toolbar.tabs[++ti] = {
				id: id,
				parent: tab.parent,
				css_class: tab.css_class,
				label: tab.label,
				warning_icon: tab.warning_icon,
				tooltip: tab.tooltip,
				toolbar_microtemplate: tab.toolbar_microtemplate,
				selected: id == zenarioA.toolbar
			};
			
			if (id == zenarioA.toolbar) {
				toolbar.toolbar_microtemplate = tab.toolbar_microtemplate;
			}
		}
	}
	
	//Add parent/child relationships for sub-tabs within tabs
	zenarioT.setKin(toolbar.tabs, 'zenario_at_tab_with_children');
	
	//Loop through each section
	foreach (tuix.sections as sectionId => section) {
		if (section) {
			var bi = -1,
				buttons = [],
				buttonsPos = {},
				parent,
				buttonOrdinal,
				buttonId, button;
			
			if (zenarioAT.sortedButtons[sectionId]
			 && !zenarioT.hidden(undefined, zenarioAT, undefined, sectionId, undefined, undefined, undefined, section)) {
				
				foreach (zenarioAT.sortedButtons[sectionId] as buttonOrdinal) {
					buttonId = zenarioAT.sortedButtons[sectionId][buttonOrdinal],
					button = section.buttons[buttonId];
					
					if (zenarioT.hidden(undefined, zenarioAT, undefined, buttonId, button, undefined, undefined, section)) {
						continue;
					}
					if (button.appears_in_toolbars
					 && !engToBoolean(button.appears_in_toolbars[zenarioA.toolbar])) {
						continue;
					}
					
					buttons[++bi] = {
							id: buttonId,
							css_class: button.css_class || 'label_without_icon',
							label: button.label || button.name,
							parent: button.parent,
							tuix: button
						};
					buttonsPos[buttonId] = bi;
					
					buttons[bi].tooltip = button.tooltip;
					
					if (button.navigation_path) {
						buttons[bi].href = zenario.addBasePath(window.zenarioATLinks.organizer + '#' + button.navigation_path);
					
					} else if (button.frontend_link) {
						buttons[bi].href = zenario.addBasePath(button.frontend_link);
					}
					
					if (button.onclick) {
						buttons[bi].onclick = button.onclick;
					
					} else if (!buttons[bi].href) {
						buttons[bi].onclick = "zenarioAT.clickButton('" + jsEscape(sectionId) + "', '" + jsEscape(buttonId) + "'); return false;";
					}
				}
				
				//Add parent/child relationships
				zenarioT.setKin(buttons, 'zenario_at_button_with_children');
			}
			
			toolbar.sections[sectionId] = buttons;
		}
	}
	
	get('zenario_at_wrap').innerHTML = zenarioT.microTemplate('zenario_toolbar', toolbar);
	zenarioA.tooltips('#zenario_at_wrap a[title]');
	zenarioA.tooltips('#zenario_at_wrap div[title]');
	zenarioA.tooltips('#zenario_at_wrap ul ul a[title]', {position: {my: 'left+2 center', at: 'right center', collision: 'flipfit'}});
	zenarioA.setTooltipIfTooLarge('#zenario_at_lower_section .zenario_at_infobar', undefined, zenarioA.tooltipLengthThresholds.adminToolbarTitle);
};



/*
zenarioAT.setTitle = function(title, className) {
	get('zenario_message_content').innerHTML = htmlspecialchars(title);
	get('zenario_message_content').style.fontSize = Math.min(12, Math.round(1015 / ('' + title).length)) + 'px';
	
	get('zenario_admin_toolbar').className = 'zenario_admin_toolbar zenario_toolbar_header ' + htmlspecialchars(className);
};
*/


//  Sorting Functions  //

zenarioAT.sort = function() {
	//Build arrays to sort, containing:
		//0: actual index
		//1: The value to sort by
	zenarioAT.sortedToolbars = [];
	if (zenarioAT.tuix.toolbars) {
		foreach (zenarioAT.tuix.toolbars as var i => var thisToolbar) {
			if (thisToolbar) {
				zenarioAT.sortedToolbars.push([i, thisToolbar.ord]);
			}
		}
	}
	
	//Sort this array
	zenarioAT.sortedToolbars.sort(zenarioT.sortArray);
	
	//Remove fields that were just there to help sort
	foreach (zenarioAT.sortedToolbars as var i) {
		zenarioAT.sortedToolbars[i] = zenarioAT.sortedToolbars[i][0];
	}
	
	zenarioAT.sortedButtons = {};
	foreach (zenarioAT.tuix.sections as var sectionId => var section) {
		if (section) {
			zenarioAT.sortButtons(sectionId);
		}
	}
};

zenarioAT.sortButtons = function(sectionId) {
	//Build an array to sort, containing:
		//0: The item's actual index
		//1: The value to sort by
	zenarioAT.sortedButtons[sectionId] = [];
	if (zenarioAT.tuix.sections[sectionId].buttons) {
		foreach (zenarioAT.tuix.sections[sectionId].buttons as var i => var button) {
			if (button) {
				zenarioAT.sortedButtons[sectionId].push([i, button.ord]);
			}
		}
	}
	
	//Sort this array
	zenarioAT.sortedButtons[sectionId].sort(zenarioT.sortArray);
	
	//Remove fields that were just there to help sort
	foreach (zenarioAT.sortedButtons[sectionId] as var i) {
		zenarioAT.sortedButtons[sectionId][i] = zenarioAT.sortedButtons[sectionId][i][0];
	}
};

//Customise some of the Organizer links, depending on where we just were
zenarioAT.customiseOrganizerLink = function(path, secondLevel) {
	
	if (path) {
		if (path.substr(0, 1) != '#') {
			path = '#' + path;
		}
	
		var zenario__content_panels_content_refiners_content_type =
			'#zenario__content/panels/content/refiners/content_type//';
	
		if (secondLevel) {
			if (path == zenario__content_panels_content_refiners_content_type + zenario.cType + '//') {
				return zenario__content_panels_content_refiners_content_type + zenario.cType + '//' + zenario.cType + '_' + zenario.cID;
			}
		} else {
			if (path == zenario__content_panels_content_refiners_content_type + 'html//') {
				return zenario__content_panels_content_refiners_content_type + zenario.cType + '//' + zenario.cType + '_' + zenario.cID;
			}
			if (path == '#zenario__menu/nav/main_section_menu/panel'
			 && zenarioAT.tuix.meta_info.menu_organizer_path) {
				return '#' + zenarioAT.tuix.meta_info.menu_organizer_path;
			}
		}
	}
	
	return path;
};


zenario.shrtNms(zenarioAT);


});
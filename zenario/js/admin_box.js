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
	
	For more information, see js_minify.shell.php for steps (1) and (2), and inc-admin.js.php for step (3).
*/

zenario.lib(function(
	undefined,
	URLBasePath,
	document, window, windowOpener, windowParent,
	zenario, zenarioA, zenarioAB, zenarioAT, zenarioO,
	get, engToBoolean, htmlspecialchars, ifNull, jsEscape, phrase,
	extensionOf, methodsOf,
	boxNum
) {
	"use strict";




zenarioAB.init = true;
zenarioAB.welcome = false;
zenarioAB.baseCSSClass = '';

zenarioAB.onKeyUpNum = 0;
zenarioAB.sizing = false;
zenarioAB.documentScrollTop = false;
zenarioAB.cachedAJAXSnippets = {};



zenarioAB.getKey = function(itemLevel) {
	return zenarioAB.tuix.key;
};

zenarioAB.getKeyId = function(limitOfOne) {
	if (zenarioAB.tuix && zenarioAB.tuix.key) {
		return zenarioAB.tuix.key.id;
	} else {
		return false;
	}
};

zenarioAB.getLastKeyId = function(limitOfOne) {
	return zenarioAB.getKeyId(limitOfOne);
};


zenarioAB.openSiteSettings = function(settingGroup, tab) {
	zenarioAB.open(
		'site_settings',
		{
			id: settingGroup
		},
		tab,
		undefined,
		function() {
			zenarioO.reload();
		}
	);
};
zenarioAB.enableOrDisableSite = function() {
	zenarioAB.open(
		'zenario_enable_site',
		undefined,
		undefined,
		undefined,
		function() {
			zenarioO.reload();
		}
	);
};

//Open an admin floating box
zenarioAB.open = function(path, key, tab, values, callBack, templatePrefix) {
	
	//Don't allow a box to be opened if Storekeeper is opened and covering the screen
	if (zenarioA.checkIfBoxIsOpen('AdminOrganizer')) {
		return false;
	}
	
	zenarioAB.templatePrefix = templatePrefix || 'zenario_admin_box';
	
	//Record the current scroll location of the page behind the box
	if (zenarioAB.documentScrollTop === false) {
		zenarioAB.documentScrollTop = $(zenario.browserIsSafari()? 'body' : 'html').scrollTop();
	}
	
	//Allow admin boxes to be opened in a simmilar format to Storekeeper panels; e.g. tag/path//id
	if (!key) {
		key = {};
	}
	path = ('' + path).split('//', 2);
	if (path[1] && !key.id) {
		key.id = path[1];
	}
	path = path[0];
	
	
	//Open the box...
	zenarioAB.isOpen = true;
	zenarioAB.callBack = callBack;
	zenarioAB.getRequestKey = key;
	zenarioAB.changed = {};
	zenarioAB.SKColPicker = false;

	var html = zenarioA.microTemplate(zenarioAB.templatePrefix, {});
	
	zenarioAB.baseCSSClass = 'zenario_fbAdmin zenario_admin_box zab_' + path;
	zenarioA.openBox(html, zenarioAB.baseCSSClass, 'AdminFloatingBox' + boxNum, false, 800, 50, 2, true, true, '.zenario_jqmHead', false);
	
	//...but hide the box itself, so only the overlay shows
	get('zenario_fbAdminFloatingBox').style.display = 'none';
	
	//If any Admin Boxes are open, set a warning message for if an admin tries to leave the page 
	window.onbeforeunload = zenarioA.onbeforeunload;
	
	
	zenarioAB.start(path, key, tab, values);
};


zenarioAB.start = function(path, key, tab, values) {
	zenarioAB.tabHidden = true;
	zenarioAB.differentTab = false;
	
	zenarioAB.path = ifNull(path, '');
	
	zenarioAB.tuix = {};
		//Backwards compatability for any old code
		zenarioAB.focus = zenarioAB.tuix;
	
	zenarioAB.key = ifNull(key, {});
	zenarioAB.tab = tab;
	zenarioAB.shownTab = false;
	zenarioAB.url = zenarioAB.getURL();
	
	$.post(zenarioAB.url,
		{_fill: true, _values: values? JSON.stringify(values) : ''},
		function(data) {
			if (zenarioAB.load(data)) {
				if (zenarioAB.tab) {
					zenarioAB.tuix.tab = zenarioAB.tab;
				}
				
				delete zenarioAB.key;
				delete zenarioAB.tab;
				
				zenarioAB.sortTabs();
				zenarioAB.initFields();
				zenarioAB.draw();
			} else {
				zenarioAB.close(true);
			}
		},
	'text');
};

zenarioAB.load = function(data) {
	zenarioAB.loaded = true;
	
	if (!(data = zenarioA.readData(data))) {
		return false;
	}
	
	if (zenarioAB.callFunctionOnEditors('isDirty')) {
		zenarioAB.markAsChanged();
	}
	
	zenarioAB.callFunctionOnEditors('remove');
	if (zenarioAB.welcome) {
		zenarioAB.tuix = data;
			//Backwards compatability for any old code
			zenarioAB.focus = zenarioAB.tuix;
	} else {
		zenarioAB.syncAdminBoxFromServerToClient(data, zenarioAB.tuix);
	}
	
	if (!zenarioAB.tuix || (!zenarioAB.tuix.tabs && zenarioAB.tuix._location === undefined)) {
		zenarioA.showMessage(phrase.couldNotOpenBox, true, 'error');
		return false;
	}
	
	return true;
};

//Setup some fields when the Admin Box is first loaded/displayed
zenarioAB.initFields = function() {
	
	var tab, id, i, panel, field;
	
	if (zenarioAB.tuix.tabs) {
		foreach (zenarioAB.tuix.tabs as tab) {
			if (!zenarioAB.isInfoTag(tab) && zenarioAB.tuix.tabs[tab] && zenarioAB.tuix.tabs[tab].fields) {
				foreach (zenarioAB.tuix.tabs[tab].fields as id) {
					if (!zenarioAB.isInfoTag(id) && (field = zenarioAB.tuix.tabs[tab].fields[id])) {
						
						//Ensure that the display values for <use_value_for_plugin_name> fields are always looked up,
						//even if that field is never actually shown
						if (field.pick_items
						 && field.plugin_setting
						 && field.plugin_setting.use_value_for_plugin_name) {
							zenarioAB.pickedItemsArray(id, zenarioAB.value(id, tab, false));
						
						} else
						if (field.load_values_from_organizer_path && !field.values) {
							zenarioAB.loadValuesFromOrganizerPath(field);
						}
					}
				}
			}
		}
	}
};

zenarioAB.loadValuesFromOrganizerPath = function(field) {
	
	var i, panel;
	
	if (field.load_values_from_organizer_path && !field.values) {
		field.values = {};
		if (panel = zenarioA.getSKItem(field.load_values_from_organizer_path)) {
			if (panel.items) {
				foreach (panel.items as i) {
					if (!zenarioAB.isInfoTag(i)) {
						field.values[zenario.decodeItemIdForStorekeeper(i)] = zenarioA.formatSKItemName(panel, i);
					}
				}
			}
		}
	}
};

zenarioAB.changeMode = function() {
	if (zenarioAB.editCancelEnabled(zenarioAB.tuix.tab)) {
		if (zenarioAB.editModeOn()) {
			zenarioAB.editCancelOrRevert('cancel', zenarioAB.tuix.tab);
		} else {
			zenarioAB.editCancelOrRevert('edit', zenarioAB.tuix.tab);
		}
	}
};

zenarioAB.revertTab = function() {
	if (zenarioAB.changes(zenarioAB.tuix.tab) && zenarioAB.revertEnabled(zenarioAB.tuix.tab)) {
		zenarioAB.editCancelOrRevert('revert', zenarioAB.tuix.tab);
	}
};

zenarioAB.editCancelOrRevert = function(action, tab) {
	
	if (!zenarioAB.tuix.tabs[tab].edit_mode) {
		return;
	}
	
	var value,
		needToFormat,
		needToValidate;
	
	if (action == 'edit') {
		needToFormat = engToBoolean(zenarioAB.tuix.tabs[tab].edit_mode.format_on_edit);
		needToValidate = engToBoolean(zenarioAB.tuix.tabs[tab].edit_mode.validate_on_edit);
	
	} else if (action == 'cancel') {
		needToFormat = engToBoolean(zenarioAB.tuix.tabs[tab].edit_mode.format_on_cancel_edit);
		needToValidate = engToBoolean(zenarioAB.tuix.tabs[tab].edit_mode.validate_on_cancel_edit);
	
	} else if (action == 'revert') {
		needToFormat = engToBoolean(zenarioAB.tuix.tabs[tab].edit_mode.format_on_revert);
		needToValidate = zenarioAB.errorOnTab(tab) || engToBoolean(zenarioAB.tuix.tabs[tab].edit_mode.validate_on_revert);
	}
	
	if (zenarioAB.tuix.tabs[tab].fields && !needToValidate) {
		foreach (zenarioAB.tuix.tabs[tab].fields as var f) {
			if (!zenarioAB.isInfoTag(f)) {
				if (engToBoolean(zenarioAB.tuix.tabs[tab].fields[f].validate_onchange)
				 && ((value = zenarioAB.readField(f)) !== undefined)
				 && (value != zenarioAB.tuix.tabs[tab].fields[f].value)) {
					needToValidate = true;
					break;
				
				} else
				if (!needToFormat
				 && engToBoolean(zenarioAB.tuix.tabs[tab].fields[f].format_onchange)
				 && ((value = zenarioAB.readField(f)) !== undefined)
				 && (value != zenarioAB.tuix.tabs[tab].fields[f].value)) {
					needToFormat = true;
				}
			}
		}
	}
	
	zenarioAB.tuix.tabs[tab].edit_mode.on = action != 'cancel';
	zenarioAB.changed[tab] = false;
	
	if (needToValidate) {
		zenarioAB.validate(undefined, undefined, true);
	
	} else if (needToFormat) {
		zenarioAB.format(true);
	
	} else {
		zenarioAB.wipeTab();
		zenarioAB.redrawTab(true);
	}
	
	if (zenarioAB.tuix.tab == tab) {
		$('#zenario_abtab').removeClass('zenario_abtab_changed');
	}
};


zenarioAB.clickTab = function(tab) {
	if (zenarioAB.loaded) {
		zenarioAB.validate(tab != zenarioAB.tuix.tab, tab);
	}
};

zenarioAB.clickButton = function(el, id) {
	if (zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields[id].type == 'submit') {
		zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields[id].pressed = true;
		zenarioAB.validate(true);
	
	} else if (zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields[id].type == 'toggle') {
		if (zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields[id].pressed = !engToBoolean(zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields[id].pressed)) {
			$('#' + id).removeClass('not_pressed').addClass('pressed');
		} else {
			$('#' + id).removeClass('pressed').addClass('not_pressed');
		}
		
		zenarioAB.validateFormatOrRedrawForField(zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields[id]);
	}
};


zenarioAB.toggleLevelsPressed = {};
zenarioAB.togglePressed = function(toggleLevel, tuixObject) {
	
	var show;
	
	if (!toggleLevel) {
		show = zenarioAB.toggleLevelsPressed.last;
	
	} else if (toggleLevel > 1) {
		show = zenarioAB.toggleLevelsPressed[toggleLevel - 1];
	
	} else {
		show = true;
	}
	
	if (show === undefined) {
		show = true;
	}
	
	if (tuixObject && tuixObject.type == 'toggle') {
		zenarioAB.toggleLevelsPressed.last = show && engToBoolean(tuixObject.pressed);
		
		if (toggleLevel) {
			zenarioAB.toggleLevelsPressed[toggleLevel] = zenarioAB.toggleLevelsPressed.last;
		}
	}
	
	return show;
};

zenarioAB.toggleLevel = function(tuixObject) {
	return zenarioAB.toggleLevelsPressed.last;
};



zenarioAB.format = function(wipeValues) {
	if (zenarioAB.SKColPicker || !zenarioAB.loaded) {
		return;
	}
	
	zenarioAB.loaded = false;
	zenarioAB.hideTab();
	
	zenarioAB.checkValues(wipeValues);
	
	$.post(zenarioAB.getURL(),
		{_format: true, _box: zenarioAB.syncAdminBoxFromClientToServer()},
		function(data) {
			zenarioAB.load(data);
			zenarioAB.sortTabs();
			zenarioAB.draw();
		},
	'text');
	
	zenarioA.nowDoingSomething('loading');
};

zenarioAB.validate = function(differentTab, tab, wipeValues, callBack) {
	if (zenarioAB.SKColPicker || !zenarioAB.loaded) {
		return;
	}
	
	zenarioAB.differentTab = differentTab;
	zenarioAB.loaded = false;
	zenarioAB.hideTab(differentTab);
	
	zenarioAB.checkValues(wipeValues);
	
	$.post(zenarioAB.getURL(),
		{_validate: true, _box: zenarioAB.syncAdminBoxFromClientToServer()},
		function(data) {
			if (zenarioAB.load(data)) {
				
				if (!zenarioAB.errorOnTab(zenarioAB.tuix.tab) && tab !== undefined) {
					zenarioAB.tuix.tab = tab;
				}
			}
			
			zenarioAB.sortTabs();
			zenarioAB.switchToATabWithErrors();
			zenarioAB.draw();
			
			if (callBack) {
				callBack();
			}
		},
	'text');
	
	zenarioA.nowDoingSomething('loading');
};

zenarioAB.switchToATabWithErrors = function() {
	if (zenarioAB.tuix && zenarioAB.tuix.tabs) {
		if (!zenarioAB.errorOnTab(zenarioAB.tuix.tab)) {
			foreach (zenarioAB.sortedTabs as var i) {
				var tab = zenarioAB.sortedTabs[i];
				if (zenarioAB.tuix.tabs[tab] && !zenarioA.hidden(zenarioAB.tuix.tabs[tab])) {
					if (zenarioAB.errorOnTab(tab)) {
						zenarioAB.tuix.tab = tab;
						return true;
					}
				}
			}
		}
	}
	
	return false;
};

zenarioAB.save = function(confirm, saveAndContinue) {
	if (zenarioAB.SKColPicker || !zenarioAB.loaded) {
		return;
	}
	
	if (zenarioAB.saving) {
		return;
	} else {
		zenarioAB.saving = true;
	}
	
	zenarioAB.differentTab = true;
	zenarioAB.loaded = false;
	zenarioAB.hideTab(true);
	
	zenarioAB.checkValues();
	
	var post = {
		_save: true,
		_confirm: confirm? 1 : '',
		_save_and_continue: saveAndContinue,
		_box: zenarioAB.syncAdminBoxFromClientToServer()};
	
	if (engToBoolean(zenarioAB.tuix.download) || (zenarioAB.tuix.confirm && engToBoolean(zenarioAB.tuix.confirm.download))) {
		zenarioAB.save2(zenario.nonAsyncAJAX(zenarioAB.getURL(), zenario.urlRequest(post)), saveAndContinue);
	} else {
		$.post(zenarioAB.getURL(), post, function(data) {
			zenarioAB.save2(data, saveAndContinue);
		}, 'text');
		zenarioA.nowDoingSomething('saving');
	}
};

zenarioAB.save2 = function(data, saveAndContinue) {
	delete zenarioAB.saving;
	
	if (('' + data).substr(0, 12) == '<!--Valid-->') {
		data = data.substr(12);
		
		if (('' + data).substr(0, 14) == '<!--Confirm-->') {
			data = data.substr(14);
			zenarioAB.load(data);
			zenarioAB.sortTabs();
			zenarioAB.draw();
			zenarioAB.showConfirm(saveAndContinue);
		
		} else if (('' + data).substr(0, 15) == '<!--Download-->') {
			get('zenario_iframe_form').action = zenarioAB.getURL();
			get('zenario_iframe_form').innerHTML =
				'<input type="hidden" name="_download" value="1"/>' +
				'<input type="hidden" name="_box" value="' + htmlspecialchars(zenarioAB.syncAdminBoxFromClientToServer()) + '"/>';
			get('zenario_iframe_form').submit();
			
			if (saveAndContinue) {
				data = data.substr(15);
				zenarioAB.load(data);
			}
			
			zenarioAB.refreshParentAndClose(true, saveAndContinue);
		
		} else if (('' + data).substr(0, 12) == '<!--Saved-->') {
			data = data.substr(12);
			zenarioAB.load(data);
			zenarioAB.refreshParentAndClose(false, saveAndContinue);
		
		} else {
			zenarioAB.close();
			zenarioA.showMessage(data, true, 'error');
		}
	} else {
		zenarioAB.load(data);
		zenarioAB.sortTabs();
		zenarioAB.switchToATabWithErrors();
		zenarioAB.draw();
	}
};


zenarioAB.showConfirm = function(saveAndContinue) {
	if (zenarioAB.tuix && zenarioAB.tuix.confirm && engToBoolean(zenarioAB.tuix.confirm.show)) {
		
		var message = zenarioAB.tuix.confirm.message;
		
		if (!engToBoolean(zenarioAB.tuix.confirm.html)) {
			message = htmlspecialchars(message, true);
		}
		
		var buttons =
			'<input type="button" class="submit_selected" value="' + zenarioAB.tuix.confirm.button_message + '" onclick="zenarioAB.save(true, ' + engToBoolean(saveAndContinue) + ');"/>' +
			'<input type="button" class="submit" value="' + zenarioAB.tuix.confirm.cancel_button_message + '" onclick="zenarioA.closeFloatingBox();"/>';
		
		zenarioA.floatingBox(message, buttons, ifNull(zenarioAB.tuix.confirm.message_type, 'none'));
	}
};


zenarioAB.hideTab = function(differentTab) {
	if (differentTab) {
		$('#zenario_abtab input').add('#zenario_abtab select').add('#zenario_abtab textarea').attr('disabled', 'disabled');
		$('#zenario_abtab').clearQueue().show().animate({opacity: .8}, 200, function() {
			zenarioAB.tabHidden = true;
			zenarioAB.draw();
		});
	} else {
		$('#zenario_abtab input').add('#zenario_abtab select').add('#zenario_abtab textarea').attr('disabled', 'disabled').animate({opacity: .9}, 100);
		zenarioAB.tabHidden = true;
		zenarioAB.draw();
	}
};

zenarioAB.draw = function() {
	if (zenarioAB.welcome && zenarioAB.tuix) {
		
		if (zenarioAB.tuix._clear_local_storage) {
			delete zenarioAB.tuix._clear_local_storage;
			zenario.sClear(true);
		}
		
		if (zenarioAB.tuix._location !== undefined) {
			document.location.href = zenario.addBasePath(zenarioAB.tuix._location);
			return;
		} else if (zenarioAB.tuix._task !== undefined) {
			zenarioAB.task = zenarioAB.tuix._task;
			delete zenarioAB.tuix._task;
		}
	}
	
	if ((zenarioAB.isOpen || zenarioAB.SKColPicker || zenarioAB.welcome) && zenarioAB.loaded && zenarioAB.tabHidden) {
		zenarioAB.draw2();
	}
};

zenarioAB.draw2 = function(SKColPicker) {
	zenarioAB.SKColPicker = SKColPicker;
	
	if (!zenarioAB.SKColPicker && !zenarioAB.welcome) {
		
		//Add wrapper CSS classes
		get('zenario_fbAdminFloatingBox').className =
			zenarioAB.baseCSSClass +
			' ' +
			(zenarioAB.tuix.css_class || '') + 
			' ' +
			(engToBoolean(zenarioAB.tuix.hide_tab_bar)? 'zenario_admin_box_with_tabs_hidden' : 'zenario_admin_box_with_tabs_shown');
		
		//Don't show the requested tab if it has been hidden
		if (zenarioAB.tuix.tab && (!zenarioAB.tuix.tabs[zenarioAB.tuix.tab] || zenarioA.hidden(zenarioAB.tuix.tabs[zenarioAB.tuix.tab]))) {
			zenarioAB.tuix.tab = false;
		}
		
		//Generate the HTML for the tabs
		var data = [];
		foreach (zenarioAB.sortedTabs as var i => var tab) {
			if (zenarioAB.tuix.tabs[tab] && !zenarioA.hidden(zenarioAB.tuix.tabs[tab])) {
				//Show the first tab we find, if a tab has not yet been set
				if (!zenarioAB.tuix.tab) {
					zenarioAB.tuix.tab = tab;
				}
				
				data.push({
					tabId: tab,
					current: zenarioAB.tuix.tab == tab,
					label: zenarioAB.tuix.tabs[tab].label
				});
			}
		}
		
		//Set the HTML for the floating boxes tabs and title
		get('zenario_jqmTabs').innerHTML = zenarioA.microTemplate(zenarioAB.templatePrefix + '_tab', data);
		zenarioAB.setTitle(zenarioAB.tuix.title);
		zenarioAB.showCloseButton();
		
		
		html = '';
		
		//Set the html for the save and continue button
		if (zenarioAB.tuix.save_and_continue_button_message) {
			html += '<input id="zenarioAFB_save_and_continue"  type="button" value="' + htmlspecialchars(zenarioAB.tuix.save_and_continue_button_message) + '"';
			
			if (!zenarioAB.editModeOnBox()) {
				html += ' class="submit_disabled"/>';
			} else {
				html += ' class="submit_selected" onclick="zenarioAB.save(undefined, true);"/>';
			}
		}

		
		//Set the html for the save button
		html += '<input id="zenarioAFB_save"  type="button" value="' + ifNull(htmlspecialchars(zenarioAB.tuix.save_button_message), phrase.save) + '"';
		
		if (!zenarioAB.editModeOnBox()) {
			html += ' class="submit_disabled"/>';
		} else {
			html += ' class="submit_selected" onclick="zenarioAB.save();"/>';
		}

		
		//Set the html for the cancel button, if enabled
		if (zenarioAB.tuix.cancel_button_message) {
			html += '<input type="button" value="' + htmlspecialchars(zenarioAB.tuix.cancel_button_message) + '" onclick="zenarioAB.close();">';
		}
		
		get('zenario_fbButtons').innerHTML = html;
		
		//Set the floating box to the max height for the user's screen
		zenarioAB.size();
		
		//Show the box
		get('zenario_fbAdminFloatingBox').style.display = 'block';
	}
	
	zenarioA.nowDoingSomething(false);
	if (zenarioAB.welcome) {
		$('#zenario_abtab input').add('#zenario_abtab select').add('#zenario_abtab textarea').clearQueue();
	}
	
	
	var html = zenarioAB.drawFields();
	
	
	//Special logic if we're generating the column picker
	if (zenarioAB.SKColPicker) {
		return html;
	
	//On the Admin Login screen, drop in the tab if this is the first time we're showing the box
	} else if (zenarioAB.welcome && zenarioAB.shownTab === false) {
		zenarioAB.insertHTML(html);
		
		$('#welcome').show({effect: 'drop', direction: 'up', duration: 300, complete: zenarioAB.addJQueryElementsToTabAndFocusFirstField});
		zenario.addJQueryElements('#zenario_abtab ', true);	
	
	//If this is the current tab...
	} else if (zenarioAB.shownTab == zenarioAB.tuix.tab) {
	
		//...shake the tab if there are errors...
		if (zenarioAB.tuix.shake === undefined? zenarioAB.differentTab && zenarioAB.errorOnTab(zenarioAB.tuix.tab) : engToBoolean(zenarioAB.tuix.shake)) {
			$(zenarioAB.welcome? '#welcome' : '#zenario_abtab').effect({
				effect: 'bounce',
				duration: 125,
				direction: 'right',
				times: 2,
				distance: 5,
				mode: 'effect',
				complete: function() {
					zenarioAB.insertHTML(html);
					
					$('#zenario_abtab').clearQueue().show().animate({opacity: 1}, 75, function() {
						if (zenario.browserIsIE()) {
							this.style.removeAttribute('filter');
						}
						
						zenarioAB.addJQueryElementsToTabAndFocusFirstField();
					});
					
					zenarioAB.hideShowFields();
				}
			});
			
		//...otherwise don't show any animation
		} else {
			//Fade in a tab if it was hidden
			//(It's probably not hidden but just in case)
			zenarioAB.insertHTML(html);
			
			var lastScrollTop = zenarioAB.lastScrollTop;
			
			$('#zenario_abtab').clearQueue().show().animate({opacity: 1}, 150, function() {
				if (zenario.browserIsIE()) {
					this.style.removeAttribute('filter');
				}
				
				if (lastScrollTop !== undefined) {
					zenarioAB.addJQueryElementsToTab();
				} else {
					zenarioAB.addJQueryElementsToTabAndFocusFirstField();
				}
			});
			
			//Attempt to preserve the previous scroll height if this is the same tab as last time
			$('#zenario_fbAdminInner').scrollTop(lastScrollTop);
			
			zenarioAB.hideShowFields();
		}
		
		delete zenarioAB.tuix.shake;
	
	//A new/different tab - fade it in
	} else {
		zenarioAB.insertHTML(html);
		$('#zenario_abtab').clearQueue().show().animate({opacity: 1}, 150, function() {
			if (zenario.browserIsIE()) {
				this.style.removeAttribute('filter');
			}
			
			zenarioAB.addJQueryElementsToTabAndFocusFirstField();
		});
	}

	zenarioAB.shownTab = zenarioAB.tuix.tab;
	delete zenarioAB.lastScrollTop;
};

zenarioAB.drawFields = function() {
	
	zenarioA.clearHTML5UploadFromDragDrop();
	
	zenarioAB.sliders = {};
	zenarioAB.codeEditors = {};
	zenarioAB.colourPickers = {};
	
	var tab = zenarioAB.tuix.tab,
		html = '',
		buttonHTML = '';
	
	if (!zenarioAB.savedAndContinued(tab) && zenarioAB.editCancelEnabled(tab)) {
		buttonHTML =
			'<div class="zenario_editCancelButton">' +
				'<input class="submit" type="button" onclick="zenarioAB.changeMode(); return false;" value="' +
					(zenarioAB.editModeOn()? phrase.cancel : phrase.edit) +
				'">' +
			'</div>';
	}
	
	
	var microTemplate = ifNull(zenarioAB.tuix.tabs[tab].template, zenarioAB.templatePrefix + '_current_tab'),
		errorsDrawn = false,
		data = {
			fields: {},
			rows: [],
			tabId: tab,
			path: zenarioAB.path,
			revert: buttonHTML,
			errors: [],
			notices: {}
		};
	
	if (zenarioAB.editModeOn()) {
		if (zenarioAB.tuix.tabs[tab].errors) {
			foreach (zenarioAB.tuix.tabs[tab].errors as var e) {
				if (!zenarioAB.isInfoTag(e)) {
					data.errors.push({message: zenarioAB.tuix.tabs[tab].errors[e]});
				}
			}
		}
		
		//Temporary code - for now, we'll just display field errors at the top of the tab with the others
		if (zenarioAB.tuix.tabs[tab].fields) {
			foreach (zenarioAB.tuix.tabs[tab].fields as var f) {
				if (!zenarioAB.isInfoTag(f) && zenarioAB.tuix.tabs[tab].fields[f].error) {
					data.errors.push({message: zenarioAB.tuix.tabs[tab].fields[f].error});
				}
			}
		}
	}
	
	if (zenarioAB.tuix.tabs[tab].notices) {
		foreach (zenarioAB.tuix.tabs[tab].notices as var n) {
			if (!zenarioAB.isInfoTag(n)
			 && engToBoolean(zenarioAB.tuix.tabs[tab].notices[n].show)
			 && {error: 1, warning: 1, question: 1, success: 1}[zenarioAB.tuix.tabs[tab].notices[n].type]) {
				data.notices[n] = zenarioAB.tuix.tabs[tab].notices[n];
			}
		}
	}	
	
	foreach (zenarioAB.sortedFields[tab] as var f) {
		var fieldId = zenarioAB.sortedFields[tab][f],
			field = zenarioAB.tuix.tabs[tab].fields[fieldId];
		
		field._id = fieldId;
		field._html = zenarioAB.drawField(tab, fieldId, true);
		
		//Don't add completely hidden fields
		if (field._html === false) {
			continue;
		}
		
		if (field._startNewRow || ! data.rows.length) {
			data.rows.push({fields: []});
		}
		
		if (!errorsDrawn && zenarioAB.tuix.tabs[tab].show_errors_after_field == fieldId) {
			data.rows[data.rows.length-1].errors = data.errors;
			data.rows[data.rows.length-1].notices = data.notices;
			errorsDrawn = true;
		}
		
		data.rows[data.rows.length-1].fields.push(field);
		data.fields[fieldId] = field;
	}
	
	if (!errorsDrawn) {
		//If there wasn't a field specified to show the errors before,
		//show the errors at the very start by inserting a dummy field at the beginning
		data.rows.splice(0, 0, {errors: data.errors, notices: data.notices});
	}
	
	html += zenarioA.microTemplate(microTemplate, data);
	
	foreach (zenarioAB.sortedFields[tab] as var f) {
		var fieldId = zenarioAB.sortedFields[tab][f];
		
		delete zenarioAB.tuix.tabs[tab].fields[fieldId]._startNewRow;
		delete zenarioAB.tuix.tabs[tab].fields[fieldId]._hideOnOpen;
		delete zenarioAB.tuix.tabs[tab].fields[fieldId]._showOnOpen;
		delete zenarioAB.tuix.tabs[tab].fields[fieldId]._html;
		delete zenarioAB.tuix.tabs[tab].fields[fieldId]._id;
	}
	
	return html;
};

zenarioAB.insertHTML = function(html) {
	var id,
		tab = get('zenario_abtab'),
		details,
		language;
	
	tab.innerHTML = html;
	zenarioAB.tabHidden = false;
	
	if (zenarioAB.changes(zenarioAB.tuix.tab)) {
		$(tab).addClass('zenario_abtab_changed');
	} else {
		$(tab).removeClass('zenario_abtab_changed');
	}
	
	if (zenarioAB.sliders) {
		foreach (zenarioAB.sliders as id) {
			$(get(id)).each(function(i, el) {
				var slider;
				if (slider = get('zenario_slider_for__' + id)) {
					
					if (zenarioAB.sliders[id].min !== undefined) zenarioAB.sliders[id].min *= 1;
					if (zenarioAB.sliders[id].max !== undefined) zenarioAB.sliders[id].max *= 1;
					if (zenarioAB.sliders[id].step !== undefined) zenarioAB.sliders[id].step *= 1;
					
					zenarioAB.sliders[id].disabled = !zenarioAB.editModeOn();
					zenarioAB.sliders[id].value = $(el).val();
					zenarioAB.sliders[id].slide =
						function(event, ui) {
							$(el).val(ui.value);
						};
					
					zenarioAB.sliders[id].change = function(event, ui) {
						if (this && this.id) {
							zenarioAB.fieldChange(this.id.replace('zenario_slider_for__', ''));
						}
					};
					
					$(slider).slider(zenarioAB.sliders[id]);
				}
			});
		}
	}
	
	//Set up code editors
	if (zenarioAB.codeEditors) {
		foreach (zenarioAB.codeEditors as id => details) {
			var codeEditor = ace.edit(id);
			codeEditor.session.setUseSoftTabs(false);
			codeEditor.setShowPrintMargin(false);
			
			if (details.readonly) {
				codeEditor.setReadOnly(true);
				codeEditor.setBehavioursEnabled(false);
				//codeEditor.session.setOption("useWorker", false);
			}
			codeEditor.session.setOption("useWorker", false);
			
			//Attempt to set the correct language
			if (language = zenarioAB.codeEditors[id].language) {
				try {
					//Attempt to detect the language from the filename
					if (language.match(/\.twig\.html$/i)) {
						language = 'ace/mode/twig';
					} else if (language.match(/\./)) {
						language = ace.require('ace/ext/modelist').getModeForPath(language).mode;
					} else {
						language = 'ace/mode/' + language;
					}
					
					codeEditor.session.setMode(language);
				} catch (e) {
					console.log('Ace editor could not load that language', language);
				}
			}
			
		}
	}
	
	if (zenarioAB.colourPickers) {
		foreach (zenarioAB.colourPickers as id => details) {
			$(get(id)).spectrum(details);
		}
	}
};

zenarioAB.hideShowFields = function(onShowFunction) {
	//Open/close newly hidden sections with a blind animation
	var hideDivsOnOpen = $('.zenario_hide_on_open'),
		hideRowsOnOpen = $('.zenario_hide_row_on_open'),
		showDivsOnOpen = $('.zenario_show_on_open'),
		hiderFun = function(i, e) {e.style.display = 'none'};
	
	if (showDivsOnOpen.length && !hideDivsOnOpen.length) {
		showDivsOnOpen.show('blind', 200, onShowFunction);
	
	} else if (!showDivsOnOpen.length && hideDivsOnOpen.length) {
		hideDivsOnOpen.hide('blind', 200, function() {
			hideRowsOnOpen.each(hiderFun);
		});
	
	} else if (showDivsOnOpen.length && hideDivsOnOpen.length) {
		hideDivsOnOpen.each(hiderFun);
		hideRowsOnOpen.each(hiderFun);
		showDivsOnOpen.show();
	}
};


zenarioAB.addJQueryElementsToTab = function() {
	//Add any special jQuery objects to the tab
	zenario.addJQueryElements('#zenario_abtab ', true);
};

zenarioAB.addJQueryElementsToTabAndFocusFirstField = function() {
	//Add any special jQuery objects to the tab
	zenario.addJQueryElements('#zenario_abtab ', true);
	
	zenarioAB.focusFirstField();
};


//Focus either the first field, or if the first field is filled in and the second field is a password then focus that instead
zenarioAB.focusFirstField = function() {
	
	if (!zenarioAB.tuix
	 || engToBoolean(zenarioAB.tuix.tabs[zenarioAB.tuix.tab].disable_autofocus)) {
		return;
	}
	
	//Loop through the text-fields on a tab, looking for the first few fields
	var i = -1,
		fields = [],
		focusField = undefined;
	
	foreach (zenarioAB.sortedFields[zenarioAB.tuix.tab] as var f) {
		var fieldId = zenarioAB.sortedFields[zenarioAB.tuix.tab][f],
			field = zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields[fieldId];
		if (get(fieldId) && $(get(fieldId)).is(':visible')) {
			fields[++i] = {
				id: fieldId,
				type: field.type,
				empty: get(fieldId).value == '',
				focusable:
					field.type && {password:1, checkbox:1, select:1, text:1, textarea:1}[field.type]
				 && !engToBoolean(get(fieldId).disabled)
				 && !engToBoolean(get(fieldId).readonly)};
			
			if (i > 1) {
				break;
			}
		}
	}
	
	//If the first field is filled in and the second field is a password then focus that instead
	if (fields[0] && !fields[0].empty && fields[1] && fields[1].focusable && fields[1].empty && fields[1].type == 'password') {
		focusField = 1;
	
	//Otherwise try to focus the first text field or select list
	} else if (fields[0] && fields[0].focusable) {
		focusField = 0;
	
	//If that didn't work, try the second
	} else if (fields[1] && fields[1].focusable) {
		focusField = 1;
	
	//Otherwise don't focus anything
	} else {
		return;
	}
	
	setTimeout(function() {
		get(fields[focusField].id).focus();
	}, 50);
};

zenarioAB.focusField = function() {
	if (zenarioAB.fieldToFocus && get(zenarioAB.fieldToFocus) && $(get(zenarioAB.fieldToFocus)).is(':visible')) {
		get(zenarioAB.fieldToFocus).focus();
	}
	delete zenarioAB.fieldToFocus;
};

zenarioAB.setTitle = function(title, isHTML) {
	
	if (!title) {
		$('#zenario_fbAdminFloatingBox .zenario_jqmTitle').css('display', 'none');
	} else {
		$('#zenario_fbAdminFloatingBox .zenario_jqmTitle').css('display', 'block');
		$('#zenario_fbAdminFloatingBox .zenario_jqmTitle').addClass(' zenario_no_drag');
		
		var showTooltip = title.length > 80;
		
		if (!isHTML) {
			title = htmlspecialchars(title);
		}
		
		
		get('zenario_jqmTitle').innerHTML = title;
		zenarioA.setTooltipIfTooLarge('#zenario_jqmTitle', title, zenarioA.tooltipLengthThresholds.adminBoxTitle);
		//get('zenario_jqmTitle').style.fontSize = Math.min(12, Math.round(1200 / ('' + title).length)) + 'px';
	}
};

zenarioAB.showCloseButton = function() {
	if (zenarioAB.tuix.cancel_button_message) {
		$('#zenario_fbAdminFloatingBox .zenario_jqmClose').css('display', 'none');
	} else {
		$('#zenario_fbAdminFloatingBox .zenario_jqmClose').css('display', 'block');
	}
};

zenarioAB.errorOnBox = function() {
	if (zenarioAB.tuix && zenarioAB.tuix.tabs) {
		foreach (zenarioAB.tuix.tabs as tab) {
			if (zenarioAB.errorOnTab(tab)) {
				return true;
			}
		}
	}
	
	return false;
};

zenarioAB.errorOnTab = function(tab) {
	if (zenarioAB.tuix.tabs[tab] && zenarioAB.editModeOn(tab)) {
		if (zenarioAB.tuix.tabs[tab].errors) {
			foreach (zenarioAB.tuix.tabs[tab].errors as var e) {
				if (!zenarioAB.isInfoTag(e)) {
					return true;
				}
			}
		}
		if (zenarioAB.tuix.tabs[tab].fields) {
			foreach (zenarioAB.tuix.tabs[tab].fields as var f) {
				if (!zenarioAB.isInfoTag(f) && zenarioAB.tuix.tabs[tab].fields[f].error) {
					return true;
				}
			}
		}
	}
};

zenarioAB.checkValues = function(wipeValues) {
	
	if (wipeValues) {
		zenarioAB.wipeTab();
	} else {
		zenarioAB.readTab();
	}
	
	foreach (zenarioAB.tuix.tabs as var tab) {
		if (!zenarioAB.isInfoTag(tab)) {
			
			//Workaround for a problem where initial values do not get submitted if a tab is never visited.
			//This script loops through all of the tabs and all of the fields on this admin boxes, and ensures
			//that their values are set correctly.
			var editing = zenarioAB.editModeOn(tab);
			if (zenarioAB.tuix.tabs[tab].fields) {
				foreach (zenarioAB.tuix.tabs[tab].fields as var f) {
					if (!zenarioAB.isInfoTag(f)) {
						
						var field = zenarioAB.tuix.tabs[tab].fields[f],
							nonFieldType = field.snippet || field.type == 'submit' || field.type == 'toggle',
							multi = field.pick_items || field.type == 'checkboxes' || field.type == 'radios';
						
						//Ignore non-field types
						if (!nonFieldType) {
							
							if (field.current_value === undefined) {
								field.current_value = zenarioAB.value(f, tab, true);
							}
							
							if (editing) {
								if (field.value === undefined) {
									field.value = zenarioAB.value(f, tab, false);
								}
							
								if (field.multiple_edit
								 && field.multiple_edit._changed === undefined
								 && field.multiple_edit.changed !== undefined) {
									field.multiple_edit._changed =
									field.multiple_edit.changed;
								}
							}
						}
					}
				}
			}
		}
	}
};

			
//Most attributes that are part of the HTML spec we'll pass on directly
zenarioAB.allowedAtt = {
	'id': true,
	'name': true, //radio groups only
	'size': true,
	'maxlength': true,
	'accesskey': true,
	'class': true,
	'cols': true,
	'dir': true,
	'readonly': true,
	'rows': true,
	'style': true,
	'tabindex': true,
	'title': true,
	'disabled': true,
	'onblur': true,
	'onchange': true,
	'onclick': true,
	'ondblclick': true,
	'onfocus': true,
	'onmousedown': true,
	'onmousemove': true,
	'onmouseout': true,
	'onmouseover': true,
	'onmouseup': true,
	'onkeydown': true,
	'onkeypress': true,
	'onkeyup': true,
	'onselect': true,
	
	//New HTML 5 attributes
	'autocomplete': true,
	'autofocus': true,
	'list': true,
	'max': true,
	'min': true,
	'multiple': true,
	'pattern': true,
	'placeholder': true,
	'required': true,
	'step': true};

zenarioAB.drawField = function(tab, id, customTemplate, lov, field, value, readOnly, tempReadOnly, sortOrder, lovField) {
	
	var html = '',
		hasSlider = false,
		extraAtt = {'class': ''},
		extraAttAfter = {},
		color_picker_options;
	
	if (field === undefined) {
		field = zenarioAB.tuix.tabs[tab].fields[id];
	}
	
	if (readOnly === undefined) {
		readOnly = !zenarioAB.editModeOn() || engToBoolean(field.read_only);
	}
	
	//Currently date-time fields are readonly
	if (field.type && field.type == 'datetime') {
		readOnly = true;
	}
	
	if (sortOrder === undefined && typeof field.values == 'object') {
		//Build an array to sort, containing:
			//0: The item's actual index
			//1: The value to sort by
		sortOrder = [];
		foreach (field.values as var v) {
			if (typeof field.values[v] == 'object') {
				if (zenarioA.hidden(field.values[v])) {
					continue;
				} else if (field.values[v].ord !== undefined) {
					sortOrder.push([v, field.values[v].ord]);
				} else if (field.values[v].label !== undefined) {
					sortOrder.push([v, field.values[v].label]);
				} else {
					sortOrder.push([v, v]);
				}
			} else {
				sortOrder.push([v, field.values[v]]);
			}
		}
	
		sortOrder.sort(zenarioA.sortArray);
		
		//Remove fields that were just there to help sort
		foreach (sortOrder as var i) {
			sortOrder[i] = sortOrder[i][0];
		}
	}
	
	if (lov === undefined) {
		if (field.load_values_from_organizer_path && !field.values) {
			zenarioAB.loadValuesFromOrganizerPath(field);
		}
		
		//Close the last row if it was left open, unless this field should be on the same line
		field._startNewRow = !engToBoolean(field.same_row);
		
		//Include an animation to show newly unhidden fields
		if (field._startNewRow
		 && zenarioAB.shownTab !== false
		 && zenarioAB.shownTab == zenarioAB.tuix.tab
		 && field.type != 'editor'
		 && field.type != 'code_editor'
		 && zenarioAB.tuix.tabs[tab].fields[id]._h
		 && !zenarioA.hidden(field)) {
			field._showOnOpen = true;
			delete zenarioAB.tuix.tabs[tab].fields[id]._h;	
		
		//Include an animation to hide newly hidden fields
		} else
		if (field._startNewRow
		 && zenarioAB.shownTab !== false
		 && zenarioAB.shownTab == zenarioAB.tuix.tab
		 && field.type != 'editor'
		 && field.type != 'code_editor'
		 && !zenarioAB.tuix.tabs[tab].fields[id]._h
		 && zenarioA.hidden(field)) {
			field._hideOnOpen = true;
			zenarioAB.tuix.tabs[tab].fields[id]._h = true;
		
		//Don't show hidden fields
		} else if (zenarioA.hidden(field)) {
			zenarioAB.tuix.tabs[tab].fields[id]._h = true;
			return false;
		
		} else {
			delete zenarioAB.tuix.tabs[tab].fields[id]._h;	
		}
		
		
	
		var meHTML = '',
			meId,
			changed = false;
		if (field.multiple_edit) {
			if (!readOnly && field.multiple_edit._changed !== undefined) {
				changed = engToBoolean(field.multiple_edit._changed);
			} else {
				changed = field.multiple_edit.changed;
			}
			
			if (engToBoolean(field.multiple_edit.disable_when_unchanged) && !changed) {
				tempReadOnly = true;
			}
			
			if (engToBoolean(field.multiple_edit.hide_ui) || !field.multiple_edit.select_list) {
				meHTML += '<input type="checkbox" class="multiple_edit" id="multiple_edit__' + htmlspecialchars(id) + '"' + (changed? ' checked="checked"' : '');
				
				if (engToBoolean(field.multiple_edit.hide_ui)) {
					meHTML += ' style="display: none;"';
				
				} else if (readOnly) {
					meHTML += ' disabled="disabled"';
				
				} else {
					meHTML += ' onchange="zenarioAB.meChange(this.checked, \'' + htmlspecialchars(id) + '\');"';
					meId = 'multiple_edit__' + id;
				}
				
				meHTML += '/> ';
			
			} else {
				meHTML += '<select class="multiple_edit" id="multiple_edit__' + htmlspecialchars(id) + '"';
				
				if (readOnly) {
					meHTML += ' disabled="disabled"';
				} else {
					meHTML += ' onchange="zenarioAB.meChange(this.value == 1, \'' + htmlspecialchars(id) + '\');"';
				}
				
				meHTML += '>' +
					'<option value=""' + (changed? '' : ' selected="selected"') + '>' +
						ifNull(field.multiple_edit.select_list.not_changed_label, phrase.notChanged) +
					'</option>' +
					'<option value="1"' + (changed? ' selected="selected"' : '') + '>' +
						ifNull(field.multiple_edit.select_list.changed_label, phrase.changed) +
					'</option>' +
				'</select> ';
			}
		}
		
		html += meHTML;
		
		//delete meHTML;
		meHTML = undefined;
		
		if (field.pre_field_html !== undefined) {
			html += field.pre_field_html;
		}
	}
	
	
	if (!field.snippet && value === undefined) {
		value = zenarioAB.value(id, tab, undefined, true);
	}
	
	if (value === undefined) {
		value = '';
	}
	
	
	if (readOnly || tempReadOnly) {
		extraAtt.disabled = 'disabled';
	}
	
	//Draw HTML snippets
	if (field.snippet) {
		if (field.snippet.html) {
			html += '<span id="snippet__' + htmlspecialchars(id) + '">' + field.snippet.html + '</span>';
		
		} else if (field.snippet.url) {
			if (!engToBoolean(field.snippet.cache)) {
				html += zenario.nonAsyncAJAX(zenario.addBasePath(field.snippet.url));
			
			} else if (!zenarioAB.cachedAJAXSnippets[field.snippet.url]) {
				html += (zenarioAB.cachedAJAXSnippets[field.snippet.url] = zenario.nonAsyncAJAX(zenario.addBasePath(field.snippet.url)));
			
			} else {
				html += zenarioAB.cachedAJAXSnippets[field.snippet.url];
			}
		
		} else if (field.snippet.separator) {
			html += '<hr class="input_separator"';
			
			if (field.snippet.separator.style) {
				html += ' style="' + htmlspecialchars(field.snippet.separator.style) + '"';
			}
			
			html += '/>';
		}
	
	//Draw multiple checkboxes/radiogroups
	} else if ((field.type == 'checkboxes' || field.type == 'radios') && lov === undefined) {
		if (field.values) {
			var picked_items = zenarioAB.pickedItemsArray(field, value),
				thisField = $.extend(true, {}, field);
			
			thisField.name = ifNull(field.name, id);
			thisField.type = field.type == 'checkboxes'? 'checkbox' : 'radio';
			
			if (readOnly) {
				thisField.disabled = true;
			}
			
			html += zenarioAB.hierarchicalBoxes(tab, id, value, field, thisField, picked_items, tempReadOnly, sortOrder);
		}
	
	} else if (field.upload || field.pick_items) {
		var mergeFields = {
			id: id,
			wrappedId: 'name_for_' + id,
			readOnly: readOnly,
			tempReadOnly: tempReadOnly};
		
		if (readOnly) {
			mergeFields.pickedItems = zenarioAB.drawPickedItems(id, true);
		
		} else {
			mergeFields.pickedItems = zenarioAB.drawPickedItems(id, false, tempReadOnly);
			
			if (field.pick_items
			 && field.pick_items.path
			 && field.pick_items.min_path
			 && field.pick_items.target_path
			 && !engToBoolean(field.pick_items.hide_select_button)) {
				mergeFields.select = {
					onclick: "zenarioAB.pickItems('" + htmlspecialchars(id) + "');",
					phrase: field.pick_items.select_phrase || phrase.selectDotDotDot
				};
			}
			
			if (field.upload) {
				mergeFields.upload = {
					onclick: "zenarioAB.upload('" + htmlspecialchars(id) + "');",
					phrase: field.upload.upload_phrase || phrase.uploadDotDotDot
				};
				
				if (engToBoolean(field.upload.drag_and_drop)) {
					zenarioAB.upload(id, true);
				}
				
				if (window.Dropbox && Dropbox.isBrowserSupported()) {
					mergeFields.dropbox = {
						onclick: "zenarioAB.chooseFromDropbox('" + htmlspecialchars(id) + "');",
						phrase: field.upload.dropbox_phrase || phrase.dropboxDotDotDot
					};
				}
			}
		}
		
		html += zenarioA.microTemplate(zenarioAB.templatePrefix + '_picked_items', mergeFields);
	
	} else if (field.type) {
		extraAtt.onchange = "zenarioAB.fieldChange('" + htmlspecialchars(id) + "');";
		
		if (field.disabled) {
			extraAtt['class'] += 'disabled ';
		}
		
		if (field.return_key_presses_button && !readOnly) {
			extraAtt.onkeyup =
				ifNull(extraAtt.onkeyup, '', '') +
				"if (event.keyCode == 13) {" +
					"$('#" + htmlspecialchars(field.return_key_presses_button) + "').click();" +
				"}";
		}
		
		if (engToBoolean(field.multiple_edit) && !readOnly) {
			extraAtt.onkeyup =
				ifNull(extraAtt.onkeyup, '', '') +
				"if (event && event.keyCode == 9) return true; zenarioAB.meMarkChanged('" + htmlspecialchars(id) + "', this.value, '" + htmlspecialchars(field.value) + "');";
				//Note keyCode 9 is the tab key; a field should not be marked as changed if the Admin is just tabbing through them
		}
		
		extraAtt['class'] += 'input_' + field.type;
		
		//Open the field's tag
		if (field.type == 'select') {
			if (field.slider) {
				hasSlider = true;
				html += zenarioAB.drawSlider(id, field, readOnly, true);
			}
			
			html += '<select';
		
		} else if (field.type == 'textarea') {
			html += '<textarea';
		
		} else if (field.type == 'code_editor') {
			html += '<div';
			extraAtt['class'] = ' zenario_embedded_ace_editor';
			zenarioAB.codeEditors[id] = {readonly: readOnly, language: field.language};
			
		} else if (field.type == 'color_picker' || field.type == 'colour_picker') {
			html += '<input';
			extraAtt['class'] = ' zenario_color_picker';
			color_picker_options = field.color_picker_options || field.colour_picker_options || {};
			color_picker_options.disabled = readOnly;
			color_picker_options.preferredFormat = color_picker_options.preferredFormat || 'hex';
			zenarioAB.colourPickers[id] = color_picker_options;
			
		} else if (field.type == 'editor') {
			html += '<textarea';
			
			extraAtt.style = 'visibility: hidden;';
			
			if (readOnly) {
				extraAtt['class'] = ' tinymce_readonly';
			
			} else {
				if (field.insert_image_button) {
					if (field.insert_link_button) {
						extraAtt['class'] = ' tinymce_with_images_and_links';
					} else {
						extraAtt['class'] = ' tinymce_with_images';
					}
				} else {
					if (field.insert_link_button) {
						extraAtt['class'] = ' tinymce_with_links';
					} else {
						extraAtt['class'] = ' tinymce';
					}
				}
			}
		
		} else if (field.type == 'submit' || field.type == 'toggle') {
			html += '<input';
			extraAtt.type = 'button';
			
			if (!readOnly || engToBoolean(field.can_be_pressed_in_view_mode)) {
				extraAttAfter.onclick = 'zenarioAB.clickButton(this, \'' + id + '\');';
				delete extraAtt.disabled;
			}
			
			if (field.type == 'submit') {
				zenarioAB.tuix.tabs[tab].fields[id].pressed = false;
			}
			
			if (field.type == 'toggle') {
				extraAtt['class'] += zenarioAB.tuix.tabs[tab].fields[id].pressed? ' pressed' : ' not_pressed';
			}
		
		} else if (field.type == 'date' || field.type == 'datetime') {
			html += '<input';
			extraAtt.type = 'text';
			extraAtt['class'] += ' zenario_datepicker';
			
			if (engToBoolean(field.change_month_and_year)) {
				extraAtt['class'] += ' zenario_datepicker_change_month_and_year';
			}
			
			extraAtt.readonly = 'readonly';
			
			if (!readOnly) {
				extraAtt.onkeyup =
					ifNull(extraAtt.onkeyup, '', '') +
					"zenario.dateFieldKeyUp(this, event, '" + htmlspecialchars(id) + "');";
			}
		
		} else if (field.type == 'url') {
			html += '<input';
			extraAtt.type = 'url';
			extraAtt.onfocus =
				ifNull(extraAtt.onfocus, '', '') +
				"if(!this.value) this.value = 'http://';";
			extraAtt.onblur =
				ifNull(extraAtt.onblur, '', '') +
				"if(this.value == 'http://') this.value = '';";
		
		} else {
			if (field.slider) {
				hasSlider = true;
				html += zenarioAB.drawSlider(id, field, readOnly, true);
			}
			
			html += '<input';
			extraAtt.type = field.type;
		}
		
		//Checkboxes/Radiogroups only: If the form has already been submitted, overwrite the "checked" attribute depending on whether the checkbox/radiogroup was chosen
		if (field.type == 'checkbox' || field.type == 'radio') {
			if (engToBoolean(value)) {
				extraAtt.checked = 'checked';
			}
			value = undefined;
		}
		
		if (hasSlider) {
			extraAtt.onchange =
				ifNull(extraAtt.onchange, '', '') +
				"$('#zenario_slider_for__" + id + "').slider('value', $(this).val());";
			extraAtt.onkeyup =
				ifNull(extraAtt.onkeyup, '', '') +
				"$('#zenario_slider_for__" + id + "').slider('value', $(this).val());";
		}
		
		if (lov === undefined) {
			field.id = id;
		} else {
			field.id = id + '___' + lov;
			extraAtt['class'] += ' control_for__' + id;
		}
		
		if (field.type != 'radio' || !field.name) {
			field.name = id;
		}
		
		
		//Add attributes
		var atts = field;
		if (lovField) {
			atts = $.extend({}, field, lovField);
		}
		
		foreach (atts as var att) {
			if (zenarioAB.allowedAtt[att]) {
				if ((att == 'disabled' || att == 'readonly')) {
					if (engToBoolean(extraAtt[att]) || engToBoolean(atts[att])) {
						html += ' ' + att + '="' + att + '"';
					}
				
				} else {
					html += ' ' + att + '="';
					
					if (extraAtt[att]) {
						html += extraAtt[att] + ' ';
						delete extraAtt[att];
					}
					
					html += htmlspecialchars(atts[att]);
					
					if (extraAttAfter[att]) {
						html += ' ' + extraAttAfter[att];
						delete extraAttAfter[att];
					}
					
					html += '"';
				}
			}
		}
		foreach (extraAtt as var att) {
			html += ' ' + att + '="' + htmlspecialchars(extraAtt[att]);
			
			if (extraAttAfter[att]) {
				html += ' ' + extraAttAfter[att];
				delete extraAttAfter[att];
			}
			
			html += '"';
		}
		foreach (extraAttAfter as var att) {
			html += ' ' + att + '="' + htmlspecialchars(extraAttAfter[att]) + '"';
		}
		
		
		//Add the value (which happens slightly differently for textareas)
		if (field.type == 'select') {
			html += '>';
		
		} else if (field.type == 'textarea' || field.type == 'editor') {
			html += '>' + htmlspecialchars(value, false, 'asis') + '</textarea>';
		
		} else if (field.type == 'code_editor') {
			html += '>' + htmlspecialchars(value, false, 'asis') + '</div>';
		
		} else if (field.type == 'date' || field.type == 'datetime') {
			html += ' value="' + htmlspecialchars(zenario.formatDate(value, field.type == 'datetime')) + '"/>';
			html += '<input type="hidden" id="_value_for__' + htmlspecialchars(id) + '" value="' + htmlspecialchars(value) + '"/>';
			
			if (!readOnly && !zenarioAB.SKColPicker) {
				html += '<input type="button" class="zenario_remove_date" value="x" onclick="zenarioAB.blankField(\'' + htmlspecialchars(id) + '\'); zenarioAB.fieldChange(\'' + htmlspecialchars(id) + '\');"/>';
			}
		
		} else if (value !== undefined) {
			html += ' value="' + htmlspecialchars(value, false, 'asis') + '"/>';
		
		} else {
			html += '/>';
		}
		
		if (field.type == 'select') {
			if (field.empty_value) {
				html += '<option value="">' + htmlspecialchars(field.empty_value) + '</option>';
			}
			
			if (field.values) {
				foreach (sortOrder as var i) {
					var v = sortOrder[i];
					
					html +=
						'<option value="' + htmlspecialchars(v) + '"' + (v == value? ' selected="selected"' : '') + '>' +
							htmlspecialchars(field.values[v], false, true) +
						'</option>';
				}
			}
			html += '</select>';
		}
	}
	
	if (lov === undefined) {
		
		if (field.type == 'url' && !readOnly) {
			html +=
				'&nbsp; ' +
				'<input type="button" class="submit" value="Test" onclick="' +
					"if (get('" + htmlspecialchars(id) + "').value) window.open(zenario.addBasePath(get('" + htmlspecialchars(id) + "').value));" +
				'"/>';
		}
		
		if (field.post_field_label !== undefined) {
			html +=
				'<label for="' + htmlspecialchars(id) + '" id="label_for__' + htmlspecialchars(id) + '"> ' +
					htmlspecialchars(field.post_field_label) +
				'</label>';
		}
		
		if (field.post_field_html !== undefined) {
			html += field.post_field_html;
		}
		
		if (hasSlider) {
			html += zenarioAB.drawSlider(id, field, readOnly, false);
		}
	}
	
	return html;
};

zenarioAB.chooseFromDropbox = function(id) {
				
	var field, options;

	if (!zenarioAB.tuix.tabs[zenarioAB.tuix.tab]
	 || !(field = zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields[id])
	 || !(field.upload)) {
		return false;
	}
	
	options = {

		// Optional. Called when the user closes the dialog without selecting a file
		// and does not include any parameters.
		cancel: function() {
			zenarioA.hideAJAXLoader();
		},

		// Optional. "preview" (default) is a preview link to the document for sharing,
		// "direct" is an expiring link to download the contents of the file. For more
		// information about link types, see Link types below.
		//linkType: "preview",
		linkType: "direct",

		// Optional. A value of false (default) limits selection to a single file, while
		// true enables multiple file selection.
		multiselect: !!engToBoolean(field.upload.multi),

		// Optional. This is a list of file extensions. If specified, the user will
		// only be able to select files with these extensions. You may also specify
		// file types, such as "video" or "images" in the list. For more information,
		// see File types below. By default, all extensions are allowed.
		extensions: field.upload.extensions? _.toArray(field.upload.extensions) : undefined,

		// Required. Called when a user selects an item in the Chooser.
		success: function(files) {
			
			zenarioA.showAJAXLoader();
			
			var f,
				file,
				cb = new zenario.callback;
			
			foreach (files as f => file) {
				cb.add(zenario.ajax('zenario/ajax.php?method_call=handleAdminBoxAJAX&fetchFromDropbox=1', file, true));
			}
			
			cb.after(function() {
				var i,
					file,
					field,
					values,
					picked_items,
					multiple_select;
		
				if (!zenarioAB.tuix.tabs[zenarioAB.tuix.tab]
				 || !(field = zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields[id])
				 || !(field.upload)) {
					return false;
				}
			
				values = (field.current_value === undefined? field.value : field.current_value);
				multiple_select = engToBoolean(field.upload.multi);
				
				foreach (arguments as i) {
					file = arguments[i];
					
					if (file && file.id) {
						if (!multiple_select || !values) {
							values = file.id;
						} else {
							values += ',' + file.id;
						}
					}
				}
				
				zenarioA.hideAJAXLoader();
				zenarioAB.redrawPickedItems(id, field, values);
			});
		}
	};
	
	zenarioA.showAJAXLoader();
	Dropbox.choose(options);
};

zenarioAB.upload = function(id, setUpDragDrop) {
	var field, object;
	if (!zenarioAB.tuix.tabs[zenarioAB.tuix.tab]
	 || !(field = zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields[id])
	 || !(field.upload)) {
		return false;
	}
	
	object = {
		class_name: field.class_name,
		upload: field.upload
	};
	
	zenarioAB.uploadCallback = function(responses) {
		if (responses) {
			var i,
				file,
				field,
				values,
				multiple_select;
		
			if (!zenarioAB.tuix.tabs[zenarioAB.tuix.tab]
			 || !(field = zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields[id])
			 || !(field.upload)) {
				return false;
			}
			
			values = (field.current_value === undefined? field.value : field.current_value);
			
			multiple_select = engToBoolean(field.upload.multi);
			
			foreach (responses as i) {
				file = responses[i];
				
				if (file && file.id) {
					if (!multiple_select || !values) {
						values = file.id;
					} else {
						values += ',' + file.id;
					}
				}
			}
			
			zenarioAB.redrawPickedItems(id, field, values);
		}
	};
	
	if (setUpDragDrop) {
		zenarioA.setHTML5UploadFromDragDrop(
			URLBasePath + 'zenario/ajax.php?method_call=handleAdminBoxAJAX',
			{
				fileUpload: 1
			},
			false,
			zenarioAB.uploadCallback,
			get('zenario_fbAdminInner')
		);
	} else {
		zenarioA.action(zenarioAB, object, undefined, undefined, undefined, {fileUpload: 1});
	}
};

zenarioAB.uploadComplete = function(responses) {
	if (zenarioAB.uploadCallback !== undefined) {
		zenarioAB.uploadCallback(responses);
	}
	delete zenarioAB.uploadCallback;
};

zenarioAB.drawSlider = function(id, field, readOnly, before) {
	html = '';
	
	if (engToBoolean(field.slider.before_field)? before : !before) {
		if (readOnly) {
			html +=
				'<div class="ui-disabled">';
		}
		
		html +=
			'<div id="zenario_slider_for__' + htmlspecialchars(id) + '"' +
			' class="' + htmlspecialchars(field.slider['class']) + '"' +
			' style="' + htmlspecialchars(field.slider.style) + '"></div>';
		zenarioAB.sliders[id] = field.slider;
		
		if (readOnly) {
			html +=
				'</div>';
		}
	}
	
	return html;
};

zenarioAB.pickedItemsArray = function(field, value) {
	
	if (value === false || value === null || value === undefined) {
		return {};
	}
	
	//Some people use 0 as a value for radio-groups.
	//Other people use 0 when they mean null.
	//If a zero-value is given, try to work out which was intended.
	if ((value === 0 || value === '0')
	 && !(field.values && (field.values[0] || field.values['0']))) {
		return {};
	}
	
	var items = (value + '').split(','),
		picked_items = {},
		panel,
		file,
		k, i;
	
	field._display_value = false;
	
	foreach (items as k) {
		if (i = items[k]) {
			//Format uploaded files - these are encoded, and in the form "checksum/filename/width/height"
			//We want to try and display the filename
			if (field.upload
			 && (file = zenario.decodeItemIdForStorekeeper(i))
			 && (file = file.split('/'))
			 && (file[1])) {
				picked_items[i] = file[1];
				
				if (engToBoolean(file[2]) && engToBoolean(file[3])) {
					picked_items[i] += ' [' + file[2] + ' × ' + file[3] + 'px]';
				}
				
			} else
			if (field.values && field.values[i] && typeof field.values[i] == 'string') {
				picked_items[i] = field.values[i];
			
			} else
			if (field.values && field.values[i] && field.values[i].label) {
				picked_items[i] = field.values[i].label;
			
			//If an id was set but no label, and this is a <pick_items> field,
			//then attempt to look up the label from Storekeeper
			} else
			if (field.pick_items
			 && ((field.pick_items.target_path
			   && (panel = zenarioA.getSKItem(field.pick_items.target_path, i)))
			  || (field.pick_items.path
			   && field.pick_items.path != field.pick_items.target_path
			   && field.pick_items.path.indexOf('//') == -1
			   && (panel = zenarioA.getSKItem(field.pick_items.path, i)))
			)) {
				if (!field.values) {
					field.values = {};
				}
				
				field.values[i] =
				picked_items[i] =
					zenarioA.formatSKItemName(panel, i);
			
			//If an id was set but no label, and this is an upload field,
			//then attempt to look up the filename
			} else
			if (field.upload
			 && (i == 1 * i)
			 && (file = zenarioA.lookupFileDetails(i))) {
				if (!field.values) {
					field.values = {};
				}
				
				picked_items[i] = file.filename;
				
				if (engToBoolean(file.width) && engToBoolean(file.height)) {
					picked_items[i] += ' [' + file.width + ' × ' + file.height + 'px]';
				}
				
				field.values[i] = picked_items[i];
			
			} else {
				picked_items[i] = i;
			}
			
			if (field._display_value === false) {
				field._display_value = picked_items[i];
			}
		}
	}
	
	return picked_items;
};

zenarioAB.pickedItemsValue = function(picked_items) {
	var i, value = '';
	
	if (picked_items) {
		foreach (picked_items as i) {
			value += (value === ''? '' : ',') + i;
		}
	}
	
	return value;
};

//Draw hierarchical checkboxes or radiogroups
zenarioAB.hierarchicalBoxes = function(tab, id, value, field, thisField, picked_items, tempReadOnly, sortOrder, existingParents, parent, parents, level) {
	var cols = 1;
	
	//Set a depth limit to try and prevent infinite loops
	if (!level) {
		level = 0;
		cols = 1*field.cols || 1;
	
	} else if (level > 10) {
		return '';
	}
	
	//Create a list of ids that have children, so we don't waste time looping through looking for children for checkboxes which have none
	if (existingParents === undefined) {
		existingParents = {};
		
		foreach (field.values as var v) {
			if (typeof field.values[v] == 'object' && field.values[v].parent) {
				existingParents[field.values[v].parent] = true;
			}
		}
	}
	
	//Set up a list of parents that the current level of items will have
	if (!parents) {
		parents = {};
	}
	if (parent) {
		parents[parent] = true;
	}
	
	
	var col = 0,
		html = '',
		m, v,
		lovField;
	
	if (level) {
		html += '<div class="zenario_hierarchical_box_children" id="' + htmlspecialchars('children_for___' + id + '___' + parent) + '">';
	}
	
	foreach (sortOrder as var i) {
		m = {};
		v = sortOrder[i];
		
		//Make sure the number is numeric if it looks numeric
		if (v == 1*v) {
			v = 1*v;
		}
		
		var thisParent = undefined;
		if (typeof field.values[v] == 'object') {
			lovField = field.values[v];
		} else {
			lovField = {label: field.values[v]};
		}
		
		thisParent = lovField.parent;
		if (thisParent === '0') {
			thisParent = false;
		}
		
		if ((!parent && !thisParent) || (parent == thisParent)) {
			if (m.newRow = (++col > cols)) {
				col = 1;
			}
			
			//Work out whether this should be checked
			var thisValue;
			
			//If the field has no value set, it should not be checked unless the radiogroup had no value either
				//(i.e. the "not set" option)
			if (!value) {
				thisValue = !v;
			
			} else if (value === true || value === 1 || value === '1') {
				thisValue = v === true || v === 1 || v === '1';
			
			//Otherwise, check or don't check the checkbox/radiogroup depending on the current value of the field
			//Attempt to get the values from picked_items if we can, otherwise use the value.
			} else if (!picked_items) {
				thisValue = v == value;
			} else if (picked_items[v] !== undefined) {
				thisValue = true;
			} else {
				thisValue = false;
			}
			
			//Include logic for checking parents/unchecking children on selection/deselection of checkboxes
			if (field.type == 'checkboxes' && engToBoolean(field.checking_child_checks_parents)) {
				var onchange = '';
				
				//Include logic for checking parents/unchecking children on selection/deselection of checkboxes
				if (parent) {
					onchange += "if (this.checked) { for (var cb in " + JSON.stringify(parents) + ") { get('" + htmlspecialchars(id) + "___' + cb).checked = true; } } "
				}
				
				//Include logic for unchecking children on deselection
				if (existingParents[v]) {
					onchange += "if (!this.checked) { $('#children_for___' + this.id + ' input').attr('checked', false); } ";
				}
				
				if (onchange) {
					if (field.onchange) {
						thisField.onchange = onchange + ' ' + field.onchange;
					} else {
						thisField.onchange = onchange;
					}
				} else {
					if (field.onchange) {
						thisField.onchange = field.onchange;
					} else {
						delete thisField.onchange;
					}
				}
			
			} else if (field.type == 'checkboxes' && engToBoolean(field.checking_parent_checks_children)) {
				var onchange = '';
				
				//Include logic for checking children on selection
				if (existingParents[v]) {
					onchange += "if (this.checked) { $('#children_for___' + this.id + ' input').each(function(i, el) {el.checked = true;}) }; ";
				}
				
				if (onchange) {
					if (field.onchange) {
						thisField.onchange = onchange + ' ' + field.onchange;
					} else {
						thisField.onchange = onchange;
					}
				} else {
					if (field.onchange) {
						thisField.onchange = field.onchange;
					} else {
						delete thisField.onchange;
					}
				}
			}
			
			//If I need different html in different places I might need to use:
			//zenarioA.microTemplate(zenarioAB.templatePrefix + '_radio_or_checkbox', m)
			
			m.lovId = id + '___' + v;
			m.lovField = lovField;
			m.lovHTML = zenarioAB.drawField(tab, id, true, v, thisField, thisValue, false, tempReadOnly, sortOrder, lovField);
			
			m.childrenHTML = '';
			if (existingParents[v]) {
				m.childrenHTML = zenarioAB.hierarchicalBoxes(tab, id, value, field, thisField, picked_items, tempReadOnly, sortOrder, existingParents, v, $.extend(true, {}, parents), level + 1);
				col = 0;
			}
			
			html += zenarioA.microTemplate('zenario_admin_box_radio_or_checkbox', m);
		}
	}
	
	if (level) {
		html += '</div>';
	}
	
	return html;
};

zenarioAB.drawPickedItems = function(id, readOnly, tempReadOnly, tab) {
	
	if (tab === undefined) {
		tab = zenarioAB.tuix.tab;
	}
	
	var field = zenarioAB.tuix.tabs[tab].fields[id],
		itemSelected = false,
		panel,
		m, i,
		value = zenarioAB.value(id, tab, readOnly),
		values,
		picked_items = zenarioAB.pickedItemsArray(field, value),
		reorder_items = field.upload && engToBoolean(field.upload.reorder_items);
	
	foreach (picked_items as var i) {
		itemSelected = true;
		break;
	}
	
	
	if (!itemSelected) {
		m = {
			readOnly: readOnly,
			tempReadOnly: tempReadOnly,
			noItemSelected: true,
			nothing_selected_phrase: ifNull(field.pick_items && field.pick_items.nothing_selected_phrase, phrase.nothing_selected)};
	
	} else {
		m = [];
		
		//Build an array to sort, containing:
			//0: The item's actual id
			//1: The value to sort by
		var sortedPickedItems = [];
		
		//Unless the nudge buttons/ordering is enabled, display things alphabetically by label
		if (reorder_items) {
			values = value.split(',');
			
			foreach (values as i) {
				sortedPickedItems.push([values[i], picked_items[values[i]]]);
			}
			
		} else {
			foreach (picked_items as i) {
				sortedPickedItems.push([i, picked_items[i]]);
			}
			
			sortedPickedItems.sort(zenarioA.sortArray);
		}
		
		for (var i = 0; i < sortedPickedItems.length; ++i) {
			var item = sortedPickedItems[i][0],
				label = sortedPickedItems[i][1],
				numeric = item == 1 * item,
				extension,
				widthAndHeight,
				path,
				src,
				mi = {
					id: id,
					item: item,
					label: label,
					first: i == 0,
					last: i == sortedPickedItems.length - 1,
					readOnly: readOnly,
					tempReadOnly: tempReadOnly};
			
			//Attempt to work out the path to the item in Organizer, and include an "info" button there
			//If this is a file upload, the info button shouldn't be shown for newly uploaded files;
			//only files with an id should show the info button.
			if (field.pick_items
			 && (!field.upload || numeric)
			 && (path = field.pick_items.path)
			 && (path == field.pick_items.target_path || field.pick_items.min_path == field.pick_items.target_path)) {
				
				//No matter what the generated path was, there should always be two slashes between the selected item and the path
				if (zenario.rightHandedSubStr(path, 2) == '//') {
					path += item;
				} else if (zenario.rightHandedSubStr(path, 1) == '/') {
					path += '/' + item;
				} else {
					path += '//' + item;
				}
				
				mi.organizerPath = path;
			}
			
			
			if (field.upload) {
				mi.isUpload = true;
				
				extension = (('' + label).match(/(.*?)\.(\w+)$/)) || (('' + label).match(/(.*?)\.(\w+) \[.*\]$/));
			
				//Attempt to get the extension of the file that is chosen
				if (extension && extension[2]) {
					extension = extension[2].toLowerCase();
				} else {
					extension = 'unknown';
				}
				
				mi.extension = extension;
				
				//Generate a link to the selected file
				if (numeric) {
					//If this is an existing file (with a numeric id), link by id
					src = URLBasePath + 'zenario/file.php?id=' + item;
				} else {
					//Otherwise try to display it from the cache/uploads/ directory
					src = URLBasePath + 'zenario/file.php?getUploadedFileInCacheDir=' + encodeURIComponent(item);
				}
			
				//Check if this is an image that has been chosen
				if (extension.match(/gif|jpg|jpeg|png/)) {
					//For images, display a thumbnail that opens a colorbox when clicked
					mi.thumbnail = {
						onclick: "zenarioAB.showPickedItemInPopout('" + src + "&popout=1&dummy_filename=" + encodeURIComponent("image." + extension) + "');",
						src: src + "&sk=1"
					};
					
					//Attempt to get the width and height from the label, and work out the correct
					//width and height for the thumbnail.
					//(The max is 180 by 120; this is the size of Organizer thumbnails and
					// is also set in zenario/file.php)
					widthAndHeight = ('' + label).match(/.*\[\s*(\d+)p?x?\s*[×x]\s*(\d+)p?x?\s*\]$/);
					if (widthAndHeight && widthAndHeight[1] && widthAndHeight[2]) {
						zenarioA.resizeImage(widthAndHeight[1], widthAndHeight[2], 180, 120, mi.thumbnail);
					}
					
					
				} else {
					//Otherwise display a downlaod link
					mi.adminDownload = src + "&adminDownload=1";
				}
				
				if (reorder_items) {
					if (!mi.first) {
						mi.nudgeUp = {
							onclick: "zenarioAB.nudge('" + jsEscape(id) + "', " + (i-1) + ", " + (i) + ");"
						};
					}
					if (!mi.last) {
						mi.nudgeDown = {
							onclick: "zenarioAB.nudge('" + jsEscape(id) + "', " + (i) + ", " + (i+1) + ");"
						};
					}
				}
			}
			
			if (!readOnly && !(field.pick_items && engToBoolean(field.pick_items.hide_remove_button))) {
				mi.removeButton = {
					onclick: "zenarioAB.removePickedItem('" + jsEscape(id) + "', '" + jsEscape(item) + "');"
				};
			}
			
			m.push(mi);
		}
	}
	
	return zenarioA.microTemplate(zenarioAB.templatePrefix + '_picked_item', m);
};

zenarioAB.showPickedItemInPopout = function(href) {
	zenarioA.action(zenarioAB, {popout: {href: href, css_class: 'zenario_show_colobox_above_fab'}});
};

zenarioAB.pickItems = function(id) {
	if (!zenarioAB.tuix.tabs[zenarioAB.tuix.tab] || !zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields[id]) {
		return false;
	}
	
	var field = zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields[id],
		selectedItem,
		path;
	
	if (!field.pick_items || !field.pick_items.path || !field.pick_items.min_path || !field.pick_items.target_path) {
		return false;
	}
	
	
	//Attempt to pre-select the currently selected item
	path = field.pick_items.path;
	if (!engToBoolean(field.pick_items.multiple_select)
	 && (path == field.pick_items.target_path || field.pick_items.min_path == field.pick_items.target_path)
	 && (selectedItem = zenarioAB.readField(id))
	 && (selectedItem.indexOf(',') == -1)) {
		//No matter what the generated path was, there should always be two slashes between the selected item and the path
		if (zenario.rightHandedSubStr(path, 2) == '//') {
			path += selectedItem;
		} else if (zenario.rightHandedSubStr(path, 1) == '/') {
			path += '/' + selectedItem;
		} else {
			path += '//' + selectedItem;
		}
	}
	
	
	zenarioAB.SKTarget = id;
	zenarioA.SK('zenarioAB', 'setPickedItems', field.pick_items.multiple_select,
				path, field.pick_items.target_path, field.pick_items.min_path, field.pick_items.max_path, field.pick_items.disallow_refiners_looping_on_min_path,
				undefined, field.pick_items.one_to_one_choose_phrase, field.pick_items.one_to_many_choose_phrase,
				undefined,
				true,
				undefined, undefined, undefined,
				undefined,
				undefined, undefined,
				undefined, undefined,
				field.pick_items);
};

zenarioAB.setPickedItems = function(path, key, row, panel) {
	var id = zenarioAB.SKTarget,
		tab = zenarioAB.tuix.tab,
		field = zenarioAB.tuix.tabs[tab].fields[id],
		value = (field.current_value === undefined? field.value : field.current_value),
		picked_items,
		item, 
		ditem;
	
	if (!engToBoolean(field.pick_items.multiple_select) || !value) {
		picked_items = {};
	} else {
		picked_items = zenarioAB.pickedItemsArray(field, value);
	}
	
	foreach (key._items as item) {
		ditem = zenario.decodeItemIdForStorekeeper(item);
		
		if (!field.values) {
			field.values = {};
		}
		if (!field.values[ditem]) {
			field.values[ditem] = zenarioA.formatSKItemName(panel, item);
		}
		
		picked_items[ditem] = true;
		
		if (!engToBoolean(field.pick_items.multiple_select)) {
			break;
		}
	}
	
	zenarioAB.redrawPickedItems(id, field, picked_items);
};

zenarioAB.removePickedItem = function(id, item) {
	var tab = zenarioAB.tuix.tab,
		field = zenarioAB.tuix.tabs[tab].fields[id],
		value = (field.current_value === undefined? field.value : field.current_value),
		values = value.split(',');
	
	foreach (values as var j) {
		if (values[j] == item) {
			values.splice(j, 1);
			break;
		}
	}
	
	value = values.join(',');
	
	zenarioAB.redrawPickedItems(id, field, value);
};

//nudgeUp
zenarioAB.nudge = function(id, i, j) {
	i *= 1;
	j *= 1;
	
	var tab = zenarioAB.tuix.tab,
		field = zenarioAB.tuix.tabs[tab].fields[id],
		value = (field.current_value === undefined? field.value : field.current_value);
	
	value = value.split(',');
	
	if (value[i] !== undefined
	 && value[j] !== undefined) {
		var tmp = value[i];
		value[i] = value[j];
		value[j] = tmp;
	}
	
	value = value.join(',');
	
	zenarioAB.redrawPickedItems(id, field, value);
};

zenarioAB.redrawPickedItems = function(id, field, value) {
	
	if (typeof value == 'object') {
		value = zenarioAB.pickedItemsValue(value);
	}
	
	field.current_value = value;
	get('name_for_' + id).innerHTML = zenarioAB.drawPickedItems(id);
	zenarioAB.fieldChange(id);
};

zenarioAB.changed = {};
zenarioAB.changes = function(tab) {
	if (!zenarioAB.tuix || !zenarioAB.tuix.tab) {
		return false;
	
	} else if (tab == undefined) {
		foreach (zenarioAB.tuix.tabs as tab) {
			if (zenarioAB.changed[tab]) {
				return true;
			}
		}
		
		return false;
	
	} else {
		return zenarioAB.changed[tab];
	}
};

zenarioAB.markAsChanged = function(tab, dontUpdateButton) {
	if (!zenarioAB.tuix) {
		return;
	}
	
	if (tab === undefined) {
		tab = zenarioAB.tuix.tab;
	}
	
	if (!tab) {
		return;
	}
	
	if (!zenarioAB.changed[tab]) {
		zenarioAB.changed[tab] = true;
		
	}
	
	if (zenarioAB.tuix.tab == tab) {
		$('#zenario_abtab').addClass('zenario_abtab_changed');
	}
};

zenarioAB.fieldChange = function(id) {
	
	if (!zenarioAB.tuix || !zenarioAB.tuix.tab || !zenarioAB.tuix.tabs[zenarioAB.tuix.tab] || !zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields[id]) {
		return;
	}
	
	var tab = zenarioAB.tuix.tab,
		field = zenarioAB.tuix.tabs[tab].fields[id];
	
	zenarioAB.markAsChanged(tab);
	
	if (engToBoolean(field.multiple_edit)) {
		zenarioAB.meMarkChanged(id);
	}
	
	zenarioAB.validateFormatOrRedrawForField(field);
	
	if (zenarioAB.SKColPicker && field._change_filter_on_change) {
		zenarioO.changeFilters();
	}
};

zenarioAB.validateFormatOrRedrawForField = function(field) {
	
	if (typeof field != 'object') {
		field = zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields[field];
	}
	
	var validate = engToBoolean(field.validate_onchange),
		format = engToBoolean(field.format_onchange),
		redraw = engToBoolean(field.redraw_onchange);
	validate = validate || (zenarioAB.errorOnTab(zenarioAB.tuix.tab) && (format || redraw));
		
	if (validate) {
		zenarioAB.validate();
		return true;
	
	} else if (format) {
		zenarioAB.format();
		return true;
	
	} else {
		if (redraw) {
			zenarioAB.readTab();
			zenarioAB.redrawTab();
		}
		
		return redraw;
	}
};

zenarioAB.meMarkChanged = function(id, current_value, value) {
	
	if (current_value !== undefined) {
		if (current_value == value) {
			return;
		}
	}
	
	zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields[id].multiple_edit._changed = true;
	zenarioAB.meSetCheckbox(id, true);
};

zenarioAB.meSetCheckbox = function(id, changed) {
	if (get('multiple_edit__' + id).type == 'checkbox') {
		get('multiple_edit__' + id).checked = changed;
	} else {
		get('multiple_edit__' + id).value = changed? 1 : '';
	}
};

//The admin changes a multiple-edit checkbox
zenarioAB.meChange = function(changed, id, confirm) {
	
	//Update its state in the schema
	if (changed) {
		zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields[id].multiple_edit._changed = true;
	} else {
		
		//Require a confirm prompt if this will lose any changes
		if (!confirm
		 && engToBoolean(zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields[id].multiple_edit.warn_when_abandoning_changes)
		 && zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields[id].multiple_edit.original_value !== undefined
		 && zenarioAB.readField(id) != zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields[id].multiple_edit.original_value) {
		 	
			var buttonsHTML =
				'<input type="button" class="submit_selected" value="' + phrase.abandonChanges + '" onclick="zenarioAB.meChange(false, \'' + htmlspecialchars(id) + '\', true);"/>' + 
				'<input type="button" class="submit" value="' + phrase.cancel + '"/>';
			
			zenarioA.floatingBox(phrase.abandonChangesConfirm, buttonsHTML, 'warning');
			zenarioAB.meSetCheckbox(id, true);
			return;
		}
		
		zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields[id].multiple_edit._changed = false;
		
		//If it is now off, revert the field's value back to the default.
		delete zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields[id].current_value;
		
		var field = zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields[id];
		
		var value = field.value;
		if (field.multiple_edit.original_value !== undefined) {
			value =
			zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields[id].current_value =
			field.multiple_edit.original_value;
		}
		
		if (get(id) && !field.pick_items) {
			if (get(id).type == 'checkbox') {
				get(id).checked = value? true : false;
			} else {
				$(get(id)).val(ifNull(value, '', ''));
			}
		} else {
			//Some non-standard fields - i.e. fields that couldn't be changed using $().val() - will need a complete redraw of the tab to achieve
			zenarioAB.insertHTML(zenarioAB.drawFields());
		}
	}
	
	if (engToBoolean(zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields[id].multiple_edit.disable_when_unchanged)) {
		if (get(id)) {
			get(id).disabled = !changed;
		}
		$('.control_for__' + id).each(function(i, e) {
			e.disabled = !changed;
			
			if (e.type == 'button') {
				if (changed) {
					$(e).removeClass('submit_disabled');
				} else {
					$(e).addClass('submit_disabled');
				}
			}
		});
	}
	
	zenarioAB.meSetCheckbox(id, changed);
	
	zenarioAB.validateFormatOrRedrawForField(id);
};


zenarioAB.value = function(f, tab, readOnly, getButtonLabelsAsValues) {
	if (!tab) {
		tab = zenarioAB.tuix.tab;
	}
	
	var value = '',
		first = true,
		field = zenarioAB.tuix.tabs[tab].fields[f];
	
	if (readOnly === undefined) {
		readOnly = !zenarioAB.editModeOn(tab);
	}
	
	if (!field) {
		return undefined;
	
	} else if (!getButtonLabelsAsValues && (field.type == 'submit' || field.type == 'toggle')) {
		return field.pressed;
	
	} else if (!readOnly && field.current_value !== undefined) {
		return field.current_value;
	
	} else if (field.value !== undefined) {
		return field.value;
	
	} else if (field.multiple_edit && field.multiple_edit.original_value !== undefined) {
		return field.multiple_edit.original_value;
	
	} else {
		return undefined;
	}
};


zenarioAB.readField = function(f) {
	var value = undefined,
		tab = zenarioAB.tuix.tab,
		field = zenarioAB.tuix.tabs[tab].fields[f],
		el;
	
	//Non-field types
	if (!field || field.snippet || field.type == 'submit' || field.type == 'toggle') {
		return undefined;
	}
	
	var readOnly = !zenarioAB.editModeOn() || engToBoolean(field.read_only),
		hidden = zenarioAB.tuix.tabs[tab].fields[f]._h;
	
	//Update logic for multiple edit fields
	if (field.multiple_edit) {
		if (readOnly) {
			delete zenarioAB.tuix.tabs[tab].fields[f].multiple_edit._changed;
		}
	}
	
	//Multiple Checkboxes/Radiogroups have values stored in several different places
	if (field.type == 'checkboxes' || field.type == 'radios') {
		if (!readOnly && !hidden && field.values) {
			var v, current_value = '', first = true;
			
			foreach (field.values as var v) {
				if (get(f + '___' + v) && get(f + '___' + v).checked) {
					current_value += (first? '' : ',') + v;
					first = false;
				}
			}
			field.current_value = current_value;
		}
		hidden = true;
	
	//Pick items/upload fields always store their values in the data model, and never in the page
	} else if (field.pick_items || field.upload) {
		hidden = true;
	}
	
	//If a field was hidden or not on the page, return its stored value and don't do any further manipulations to the data model
	if (hidden) {
		if (readOnly || field.current_value === undefined) {
			return field.value;
		} else {
			return field.current_value;
		}
	}
	

	//Fields with seperate values to display values
	if (get('_value_for__' + f)) {
		zenarioAB.tuix.tabs[tab].fields[f].current_value = value = get('_value_for__' + f).value;
	
	//Editors
	} else if ((field.type == 'editor' || field.type == 'code_editor') && !readOnly) {
		var content = undefined;
		
		if (field.type == 'editor') {
			if (window.tinyMCE) {
				if (tinyMCE.get(f)) {
					content = zenarioA.tinyMCEGetContent(tinyMCE.get(f));
				}
			}
		} else if (field.type == 'code_editor') {
			var codeEditor;
			if (codeEditor = ace.edit(f)) {
				content = codeEditor.getValue();
			}
		}
		
		if (content !== undefined && content !== false) {
			value = zenarioAB.tuix.tabs[tab].fields[f].current_value = content;
		
		//If due to some bug we couldn't get the content from the editor,
		//return the stored value and don't do any further manipulations to the data model.
		} else if (field.current_value === undefined) {
			return field.value;
		} else {
			return field.current_value;
		}
	
	//Normal fields
	} else {
		if (!readOnly && (el = get(f))) {
			if (field.type == 'checkbox' || field.type == 'radio') {
				value = el.checked? true : false;
			
			} else if (field.type == 'color_picker' || field.type == 'colour_picker') {
				//For colour pickers, make sure we get the colour in hex, as for some reason spectrum often
				//loves to output values in hsv which isn't a supported format!
				try {
					value = $(el).spectrum('get').toHexString();
				} catch (e) {
					value = $(el).val();
				}
			
			} else {
				value = $(el).val();
			}
			zenarioAB.tuix.tabs[tab].fields[f].current_value = value;
		
		} else {
			delete zenarioAB.tuix.tabs[tab].fields[f].current_value;
			value = field.value;
		}
	}
	
	return value;
};

zenarioAB.blankField = function(f) {
	var tab = zenarioAB.tuix.tab;
	
	if (get(f)) {
		$(get(f)).val('');
	}
	
	if (get('_value_for__' + f)) {
		get('_value_for__' + f).value = '';
	}
	
	zenarioAB.tuix.tabs[tab].fields[f].current_value = '';
};


//zenarioAB.lastScrollTop = undefined;

zenarioAB.readTab = function() {
	var tab = zenarioAB.tuix.tab,
		value,
		values = {};
	
	zenarioAB.lastScrollTop = $('#zenario_fbAdminInner').scrollTop();
	
	foreach (zenarioAB.tuix.tabs[tab].fields as var f) {
		if (!zenarioAB.isInfoTag(f)) {
			if ((value = zenarioAB.readField(f)) !== undefined) {
				values[f] = value;
			}
		}
	}
	
	return values;
};

zenarioAB.wipeTab = function() {
	var tab = zenarioAB.tuix.tab,
		value,
		values = {};
	
	foreach (zenarioAB.tuix.tabs[tab].fields as var f) {
		if (!zenarioAB.isInfoTag(f)) {
			delete zenarioAB.tuix.tabs[tab].fields[f].current_value;
			
			if (zenarioAB.tuix.tabs[tab].fields[f].multiple_edit) {
				delete zenarioAB.tuix.tabs[tab].fields[f].multiple_edit._changed;
			}
		}
	}
};

zenarioAB.redrawTab = function() {
	zenarioAB.tuix.shake = false;
	zenarioAB.draw2();
};






//Automatically set the box to the correct height for the users screen, or the maximum height requested, whichever is smaller
zenarioAB.size = function() {
	if (zenarioAB.sizing) {
		clearTimeout(zenarioAB.sizing);
	}
	
	if (get('zenario_fbMain')) {
		if (zenarioAB.tuix && engToBoolean(zenarioAB.tuix.hide_tab_bar)) {
			get('zenario_fbMain').style.top = '0px';
			get('zenario_fbButtons').style.paddingBottom = '7px';
			get('zenario_jqmTabs').style.display = 'none';
		} else {
			get('zenario_fbMain').style.top = '24px';
			get('zenario_fbButtons').style.paddingBottom = '31px';
			get('zenario_jqmTabs').style.display = zenario.browserIsIE()? '' : 'inherit';
		}
	}
	
	if (get('zenario_fbAdminInner')) {
		var height = Math.floor($(window).height() * 0.96 - (zenario.browserIsIE()? 210 : zenario.browserIsSafari()? 202 : 205));
		
		if (zenarioAB.tuix.max_height && height > zenarioAB.tuix.max_height) {
			height = zenarioAB.tuix.max_height;
		}
		
		if (zenarioAB.tuix && engToBoolean(zenarioAB.tuix.hide_tab_bar)) {
			height = 1*height + 24;
		}
		
		if ((height = 1*height) > 0) {
			get('zenario_fbAdminInner').style.height = height + 'px';
		}
	}
	
	//Keep the page below at the top of the page, to prevent a bug with TinyMCE
	if (zenarioAB.isOpen && zenarioAB.documentScrollTop !== false) {
		$(zenario.browserIsSafari()? 'body' : 'html').scrollTop(0);
	}
	
	zenarioAB.sizing = setTimeout(zenarioAB.size, 250);
};



//Get a URL needed for an AJAX request
zenarioAB.getURL = function() {
	//Outdate any validation attempts
	++zenarioAB.onKeyUpNum;
	
	if (zenarioAB.welcome) {
		return URLBasePath + 'zenario/admin/welcome_ajax.php' +
										'?_json=1&_ab=1' +
										'&task=' + encodeURIComponent(zenarioAB.task) +
										'&get=' + encodeURIComponent(JSON.stringify(zenarioAB.getRequest)) +
										zenario.urlRequest(ifNull(zenarioAB.key, zenarioAB.tuix.key));
	} else {
		return URLBasePath + 'zenario/admin/ajax.php' +
										'?_json=1&_ab=1' +
										'&path=' + encodeURIComponent(zenarioAB.path) +
										zenario.urlRequest(zenarioAB.getRequestKey);
	}
};


//Some shortcuts
zenarioAB.editModeOn = function(tab) {
	
	if (!zenarioAB.tuix || !zenarioAB.tuix.tabs) {
		return false;
	}
	
	if (!tab) {
		tab = zenarioAB.tuix.tab;
	}
	
	if (zenarioAB.tuix.tabs[tab].edit_mode) {
		return zenarioAB.tuix.tabs[tab].edit_mode.on =
			engToBoolean(zenarioAB.tuix.tabs[tab].edit_mode.enabled)
		 && (engToBoolean(zenarioAB.tuix.tabs[tab].edit_mode.on)
		  || zenarioAB.editModeAlwaysOn(tab));
	
	} else {
		return false;
	}
};

zenarioAB.editModeAlwaysOn = function(tab) {
	return zenarioAB.tuix.tabs[tab].edit_mode.always_on === undefined
		|| engToBoolean(zenarioAB.tuix.tabs[tab].edit_mode.always_on)
		|| zenarioAB.savedAndContinued(tab);
};

zenarioAB.editCancelEnabled = function(tab) {
	return zenarioAB.tuix.tabs[tab].edit_mode
		&& engToBoolean(zenarioAB.tuix.tabs[tab].edit_mode.enabled)
		&& !zenarioAB.editModeAlwaysOn(tab);
};

zenarioAB.revertEnabled = function(tab) {
	return zenarioAB.tuix.tabs[tab].edit_mode
		&& engToBoolean(zenarioAB.tuix.tabs[tab].edit_mode.enabled)
		&& zenarioAB.editModeAlwaysOn(tab)
		&& !zenarioAB.savedAndContinued(tab)
		&& ((zenarioAB.tuix.tabs[tab].edit_mode.enable_revert === undefined && zenarioAB.tuix.key && zenarioAB.tuix.key.id)
		 || engToBoolean(zenarioAB.tuix.tabs[tab].edit_mode.enable_revert));
};

zenarioAB.savedAndContinued = function(tab) {
	return zenarioAB.tuix.tabs[tab]._saved_and_continued;
};

zenarioAB.editModeOnBox = function() {
	if (zenarioAB.tuix && zenarioAB.tuix.tabs && zenarioAB.sortedTabs) {
		foreach (zenarioAB.sortedTabs as var i) {
			var tab = zenarioAB.sortedTabs[i];
			
			if (zenarioAB.editModeOn(tab)) {
				return true;
			}
		}
	}
	
	return false;
};



//  Sorting Functions  //

//refine tags added by the CMS for location/tracking purposes
zenarioAB.isInfoTag = function(i) {
	return i == 'class_name'
		|| i == 'count'
		|| i == 'ord';
};

zenarioAB.sortTabs = function() {
	//Build an array to sort, containing:
		//0: The item's actual index
		//1: The value to sort by
	zenarioAB.sortedTabs = [];
	if (zenarioAB.tuix.tabs) {
		foreach (zenarioAB.tuix.tabs as var i) {
			if (!zenarioAB.isInfoTag(i) && zenarioAB.tuix.tabs[i]) {
				zenarioAB.sortedTabs.push([i, zenarioAB.tuix.tabs[i].ord]);
			}
		}
	}
	
	//Sort this array
	zenarioAB.sortedTabs.sort(zenarioA.sortArray);
	
	//Remove fields that were just there to help sort
	zenarioAB.sortedFields = {};
	
	foreach (zenarioAB.sortedTabs as var i) {
		var tab = zenarioAB.sortedTabs[i] = zenarioAB.sortedTabs[i][0];
		zenarioAB.sortFields(tab);
		//zenarioAB.sortedTabOrders[tab] = i;
	}
};

zenarioAB.sortFields = function(tab) {
	//Build an array to sort, containing:
		//0: The item's actual index
		//1: The value to sort by
	zenarioAB.sortedFields[tab] = [];
	if (zenarioAB.tuix.tabs[tab].fields) {
		foreach (zenarioAB.tuix.tabs[tab].fields as var i) {
			if (!zenarioAB.isInfoTag(i) && zenarioAB.tuix.tabs[tab].fields[i]) {
				zenarioAB.sortedFields[tab].push([i, zenarioAB.tuix.tabs[tab].fields[i].ord]);
			}
		}
	}
	
	//Sort this array
	zenarioAB.sortedFields[tab].sort(zenarioA.sortArray);
	
	//Remove fields that were just there to help sort
	foreach (zenarioAB.sortedFields[tab] as var i) {
		zenarioAB.sortedFields[tab][i] = zenarioAB.sortedFields[tab][i][0];
	}
};





/*	Saving/Closing  */

zenarioAB.syncAdminBoxFromClientToServer = function() {
	if (zenarioAB.welcome) {
		return JSON.stringify(zenarioAB.tuix);
	
	} else {
		var $serverTags = {};
		zenarioAB.syncAdminBoxFromClientToServerR($serverTags, zenarioAB.tuix);
		
		return JSON.stringify($serverTags);
	}
};

//Sync updates from the client to the array stored on the server
zenarioAB.syncAdminBoxFromClientToServerR = function($serverTags, $clientTags, $key1, $key2, $key3, $key4, $key5, $key6) {
	
	if ('object' != typeof $clientTags) {
		return;
	}
	
	var $type, $key0;
	
	for ($key0 in $clientTags) {
		//Only allow certain tags in certain places to be merged in
		if ((($type = 'array') && $key1 === undefined && $key0 == '_sync')
		 || (($type = 'value') && $key2 === undefined && $key1 == '_sync' && $key0 == 'session')
		 || (($type = 'value') && $key2 === undefined && $key1 == '_sync' && $key0 == 'cache_dir')
		 || (($type = 'array') && $key1 === undefined && $key0 == 'key')
		 || (($type = 'value') && $key2 === undefined && $key1 == 'key')
		 || (($type = 'value') && $key1 === undefined && $key0 == 'shake')
		 || (($type = 'value') && $key1 === undefined && $key0 == 'download')
		 || (($type = 'array') && $key1 === undefined && $key0 == 'tabs')
		 || (($type = 'array') && $key2 === undefined && $key1 == 'tabs')
		 || (($type = 'array') && $key3 === undefined && $key2 == 'tabs' && $key0 == 'edit_mode')
		 || (($type = 'value') && $key4 === undefined && $key3 == 'tabs' && $key1 == 'edit_mode' && $key0 == 'on')
		 || (($type = 'array') && $key3 === undefined && $key2 == 'tabs' && $key0 == 'fields')
		 || (($type = 'array') && $key4 === undefined && $key3 == 'tabs' && $key1 == 'fields')
		 || (($type = 'value') && $key5 === undefined && $key4 == 'tabs' && $key2 == 'fields' && $key0 == '_h')
		 || (($type = 'value') && $key5 === undefined && $key4 == 'tabs' && $key2 == 'fields' && $key0 == '_h_js')
		 || (($type = 'value') && $key5 === undefined && $key4 == 'tabs' && $key2 == 'fields' && $key0 == 'current_value')
		 || (($type = 'value') && $key5 === undefined && $key4 == 'tabs' && $key2 == 'fields' && $key0 == '_display_value')
		 || (($type = 'value') && $key5 === undefined && $key4 == 'tabs' && $key2 == 'fields' && $key0 == 'pressed')
		 || (($type = 'array') && $key5 === undefined && $key4 == 'tabs' && $key2 == 'fields' && $key0 == 'multiple_edit')
		 || (($type = 'value') && $key6 === undefined && $key5 == 'tabs' && $key3 == 'fields' && $key1 == 'multiple_edit' && $key0 == '_changed')) {
			
			//Update any values from the client on the server's copy
			if ($type == 'value') {
				if (('function' != typeof $clientTags[$key0]) && ('object' != typeof $clientTags[$key0])) {
					$serverTags[$key0] = $clientTags[$key0];
				}
			
			//For arrays, check them recursively
			} else if ($type == 'array') {
				if ('object' == typeof $clientTags[$key0]) {
					$serverTags[$key0] = {};
					zenarioAB.syncAdminBoxFromClientToServerR($serverTags[$key0], $clientTags[$key0], $key0, $key1, $key2, $key3, $key4, $key5);
				}
			}
		}
	}
};

//Sync updates from the server to the array stored on the client
zenarioAB.syncAdminBoxFromServerToClient = function($serverTags, $clientTags) {
	for (var $key0 in $serverTags) {
		if ($serverTags[$key0]['[[__unset__]]']) {
			delete $clientTags[$key0];
		
		} else
		if ($clientTags[$key0] === undefined || $clientTags[$key0] === null) {
			$clientTags[$key0] = $serverTags[$key0];
		
		} else
		if ($serverTags[$key0]['[[__replace__]]']) {
			delete $serverTags[$key0]['[[__replace__]]'];
			$clientTags[$key0] = $serverTags[$key0];
		
		} else
		if ('object' != typeof $clientTags[$key0]) {
			$clientTags[$key0] = $serverTags[$key0];
		
		} else
		if (('object' == typeof $clientTags[$key0]) && ('object' != typeof $serverTags[$key0])) {
			$clientTags[$key0] = $serverTags[$key0];
		
		} else {
			zenarioAB.syncAdminBoxFromServerToClient($serverTags[$key0], $clientTags[$key0]);
		}
	}
};


zenarioAB.getValueArrayofArrays = function(leaveAsJSONString) {
	return zenario.nonAsyncAJAX(zenarioAB.getURL(), zenario.urlRequest({_read_values: true, _box: zenarioAB.syncAdminBoxFromClientToServer()}), !leaveAsJSONString);
};

zenarioAB.refreshParentAndClose = function(disallowNavigation, saveAndContinue) {
	zenarioA.nowDoingSomething(false);
	
	if (!saveAndContinue) {
		zenarioAB.isOpen = false;
	}
	
	//Attempt to work out what to do next.
	if (zenarioAB.callBack && !saveAndContinue) {
		var values;
		if (values = zenarioAB.getValueArrayofArrays()) {
			zenarioAB.callBack(zenarioAB.tuix.key, values);
		}
		
	} else if (zenarioO.init && (zenarioA.storekeeperWindow || zenarioA.checkIfBoxIsOpen('sk'))) {
		//Reload the storekeeper if this window is a storekeeper window (new storekeeper)
		var id = false;
		
		if (zenarioAB.tuix.key.id !== undefined) {
			id = zenarioAB.tuix.key.id;
		} else {
			foreach (zenarioAB.tuix.key as var i) {
				id = zenarioAB.tuix.key[i];
				break;
			}
		}
		
		zenarioO.refreshToShowItem(id);
		
	} else if (zenarioAB.tuix.key.slotName) {
		//Refresh the slot if this was one of the slot settings thickboxes
		zenario.refreshPluginSlot(zenarioAB.tuix.key.slotName, '');
		
		if (zenarioAT.init) {
			zenarioAT.init();
		}
	
	} else if (disallowNavigation || saveAndContinue) {
		//Don't allow any of the actions below, as they involve navigation
	
	//Otherwise build up a URL from the primary key, if it looks valid
	} else if (zenarioAB.tuix.key.cID) {
		//Go to a specific content item
		zenario.goToURL(zenario.linkToItem(zenarioAB.tuix.key.cID, zenarioAB.tuix.key.cType));
	
	//For any other Admin Toolbar changes, reload the page
	} else if (zenarioAT.init) {
		zenario.goToURL(zenario.linkToItem(zenario.cID, zenario.cType, 'cVersion=' + zenario.cVersion));
		
	}
	
	var popout_message = zenarioAB.tuix.popout_message;
	
	if (!saveAndContinue) {
		zenarioAB.close();
	}
	
	if (popout_message) {
		zenarioA.showMessage(popout_message, true, false);
	}
	
	if (saveAndContinue) {
		zenarioAB.changed = {};
		
		if (zenarioAB.tuix.tabs) {
			foreach (zenarioAB.tuix.tabs as var i) {
				if (!zenarioAB.isInfoTag(i) && zenarioAB.tuix.tabs[i]) {
					if (zenarioAB.editModeOn(i)) {
						zenarioAB.tuix.tabs[i]._saved_and_continued = true;
					}
				}
			}
		}
		
		zenarioAB.sortTabs();
		zenarioAB.draw();
	}
};


zenarioAB.close = function(keepMessageWindowOpen) {
	//Close TinyMCE if it is open
	zenarioAB.callFunctionOnEditors('remove');
	zenarioA.nowDoingSomething(false);
	
	if (zenarioAB.sizing) {
		clearTimeout(zenarioAB.sizing);
	}
	
	if (!keepMessageWindowOpen) {
		zenarioA.closeFloatingBox();
	}
	
	zenarioA.closeBox('AdminFloatingBox' + boxNum);
	zenarioAB.isOpen = false;
	
	//Return the page to it's original scroll position, before the box was opened
	if (zenarioAB.documentScrollTop !== false) {
		$(zenario.browserIsSafari()? 'body' : 'html').scrollTop(zenarioAB.documentScrollTop);
		zenarioAB.documentScrollTop = false;
	}
	
	delete zenarioAB.tuix;
	
	return false;
};

zenarioAB.closeButton = function(onlyCloseIfNoChanges) {
	//Check if there is an editor open
	var message = zenarioA.onbeforeunload();
	
	//If there was, give the Admin a chance to stop leaving the page
	if (message === undefined || (!onlyCloseIfNoChanges && confirm(message))) {
		if (zenarioAB.isOpen) {
			zenarioAB.close();
		}
	}
	
	return false;
};

zenarioAB.callFunctionOnEditors = function(action) {
	if (zenarioAB.tuix && zenarioAB.tuix.tab && zenarioAB.tuix.tabs[zenarioAB.tuix.tab] && zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields && window.tinyMCE) {
		foreach (zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields as var f) {
			var field = zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields[f];
			
			if (field.type == 'editor') {
				if (tinyMCE.get(f)) {
					if (action == 'remove') {
						tinyMCE.get(f).remove();
					
					} else if (action == 'isDirty') {
						if (tinyMCE.get(f).isDirty()) {
							return true;
						}
					}
				}
			}
		}
	}
	
	return false;
};


//Specific bespoke functions for a few cases. These could have been on onkeyup/onchange events, but this way is more efficient.
//Add the alias validation functions from the meta-data tab
zenarioAB.validateAlias = function() {
	zenario.actAfterDelayIfNotSuperseded('validateAlias', zenarioAB.validateAliasGo);
};

zenarioAB.validateAliasGo = function() {
	
	var req = {
		_validate_alias: 1,
		alias: get('alias').value
	}
	
	if (zenarioAB.tuix.key.cID) {
		req.cID = zenarioAB.tuix.key.cID;
	}
	if (zenarioAB.tuix.key.cType) {
		req.cType = zenarioAB.tuix.key.cType;
	}
	if (zenarioAB.tuix.key.equivId) {
		req.equivId = zenarioAB.tuix.key.equivId;
	}
	if (get('language_id')) {
		req.langId = get('language_id').value;
	}
	
	if (get('update_translations')) {
		req.lang_code_in_url = 'show';
		if (get('update_translations').value == 'update_this' && get('lang_code_in_url')) {
			req.lang_code_in_url = get('lang_code_in_url').value;
		}
	}

	$.post(
		URLBasePath + 'zenario/admin/quick_ajax.php',
		req,
		function(data) {
			if (!(data = zenarioA.readData(data))) {
				return false;
			}
			
			var html = '';
			
			if (data) {
				foreach (data as var error) {
					html += (html? '<br />' : '') + data[error];
				}
			}
			
			get('alias_warning_display').innerHTML =  html;
	}, 'text');
};

//bespoke functions for the Content Tab
zenarioAB.generateAlias = function(text) {
	return text
			.toLowerCase()
			.replace(
				/[áÁàÀâÂåÅäÄãÃÆæçÇðÐéÉèÈêÊëËíÍìÌîÎïÏñÑóÓòÒôÔöÖõÕøØšŠúÚùÙûÛüÜýÝžŽ]/g,
				function(chr) {
					return {
							'á':'a', 'Á':'a', 'à':'a', 'À':'a', 'â':'a', 'Â':'a', 'å':'a', 'Å':'a', 'ä':'a', 'Ä':'a', 'ã':'a', 'Ã':'a',
							'Æ':'ae', 'æ':'ae',
							'ç':'c', 'Ç':'c', 'ð':'d', 'Ð':'d', 'é':'e', 'É':'e', 'è':'e', 'È':'e', 'ê':'e', 'Ê':'e', 'ë':'e', 'Ë':'e',
							'í':'i', 'Í':'i', 'ì':'i', 'Ì':'i', 'î':'i', 'Î':'i', 'ï':'i', 'Ï':'i', 'ñ':'n', 'Ñ':'n',
							'ó':'o', 'Ó':'o', 'ò':'o', 'Ò':'o', 'ô':'o', 'Ô':'o', 'ö':'o', 'Ö':'o', 'õ':'o', 'Õ':'o', 'ø':'o', 'Ø':'o',
							'š':'s', 'Š':'s', 'ú':'u', 'Ú':'u', 'ù':'u', 'Ù':'u', 'û':'u', 'Û':'u', 'ü':'u', 'Ü':'u', 'ý':'y', 'Ý':'y', 'ž':'z', 'Ž':'z'
						}[chr];
				})
			.replace(/&/g, 'and')
			.replace(/[^a-z0-9\s_-]/g, '')
			.replace(/\s+/g, '-')
			.replace(/^-+/, '')
			.replace(/-+$/, '')
			.replace(/-+/g, '-')
			.substr(0, 50);
};

zenarioAB.contentTitleChange = function() {
	
	if (get('menu_title') && !zenarioAB.tuix.___menu_title_changed) {
		get('menu_title').value = get('title').value.replace(/\s+/g, ' ');
		get('menu_title').onkeyup();
	}
	
	if (get('alias') && !zenarioAB.tuix.___alias_changed) {
		get('alias').value = zenarioAB.generateAlias(get('title').value);
		zenarioAB.validateAlias();
	}
};



//bespoke functions for Plugin Settings
zenarioAB.viewFrameworkSource = function() {
	var url =
		URLBasePath +
		'zenario/admin/organizer.php' +
		'#zenario__modules/show_frameworks//' + zenarioAB.tuix.key.moduleId + '//' + zenario.encodeItemIdForStorekeeper(zenarioAB.readField('framework'));
	window.open(url);
	
	return false;
};



//bespoke functions for Admin Perms

//Change a child-checkbox
zenarioAB.adminPermChange = function(parentName, childrenName, toggleName, n, c) {
	var parentChecked = true,
		parentClass;
	
	//Count how many checkboxes are on the page, and how many of these are checked
	if (n === undefined) {
		c = 0;
		n = $('input[name=' + childrenName + ']').each(function(i, e) {if (e.checked) ++c;}).size();
	}
	
	//Check or uncheck the parent, depending on if at least one child is checked.
	//Also set a CSS class on the row around the parent depending on how many were checked.
	if (c == 0) {
		parentChecked = false;
		parentClass = 'zenario_permgroup_empty';
	} else if (c < n) {
		parentClass = 'zenario_permgroup_half_full';
	} else {
		parentClass = 'zenario_permgroup_full';
	}
	
	get(parentName).checked =
	zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields[parentName].current_value = parentChecked;
	
	$(get('row__' + parentName))
		.removeClass('zenario_permgroup_empty')
		.removeClass('zenario_permgroup_half_full')
		.removeClass('zenario_permgroup_full')
		.addClass(parentClass);
	
	//Set the "X / Y" display on the toggle
	get(toggleName).value =
	zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields[toggleName].value =
	zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields[toggleName].current_value = c + '/' + n;
};

//Change the parent checkbox
zenarioAB.adminParentPermChange = function(parentName, childrenName, toggleName) {
	var n = 0,
		c = 0,
		current_value = '',
		checked = get(parentName).checked,
		$children = $('input[name=' + childrenName + ']');
	
	//Loop through each value for the child checkboxes.
	//Count them, and either turn them all on or all off, depending on whether the parent was checked
	foreach (zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields[childrenName].values as var v) {
		if (!zenarioAB.isInfoTag(v)) {
			++n;
			if (checked) {
				current_value += (current_value? ',' : '') + v;
				++c;
			}
		}
	}
	
	//If the $children are currently drawn on the page, update them on the page
	if ($children.size()) {
		$children.each(function(i, el) {
			el.checked = checked;
			//$children.attr('checked', checked? 'checked' : false);
		});
	}
	
	//Update them in the data
	zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields[childrenName].current_value = current_value;
	
	//Call the function above to update the count and the CSS
	zenarioAB.adminPermChange(parentName, childrenName, toggleName, n, c);
};

//Date Previews in the Site Settings
zenarioAB.previewDateFormat = function(formatField, previewField) {
	zenario.actAfterDelayIfNotSuperseded(
		formatField,
		function() {
			zenarioAB.previewDateFormatGo(formatField, previewField);
		});
};

zenarioAB.previewDateFormatGo = function(formatField, previewField) {
	if ((formatField = get(formatField))
	 && (previewField = get(previewField))) {
		previewField.value = zenario.pluginClassAJAX('zenario_common_features', {previewDateFormat: formatField.value});
	}
};


//Quickly add validation for a few things on the Welcome page as the user types.
//Also used for directories in the Site Settings
zenarioAB.quickValidateWelcomePage = function(delay) {
	zenario.actAfterDelayIfNotSuperseded('quickValidateWelcomePage', zenarioAB.quickValidateWelcomePageGo, delay);
};

zenarioAB.quickValidateWelcomePageGo = function() {
	
	var rowClasses = {};
	
	foreach (zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields as var field) {
		if (!zenarioAB.isInfoTag(field)) {
			rowClasses[field] = ifNull(zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields[field].row_class, '', '');
		}
	}

	
	var url = URLBasePath + 'zenario/admin/welcome_ajax.php?quickValidate=1';
	
	$.post(url,
		{
			tab: zenarioAB.tuix.tab,
			path: zenarioAB.welcome? '' : zenarioAB.path,
			values: JSON.stringify(zenarioAB.readTab()),
			row_classes: JSON.stringify(rowClasses)
		},
		function(data) {
			if (!(data = zenarioA.readData(data))) {
				return false;
			}
			
			if (data && data.row_classes) {
				foreach (data.row_classes as var field) {
					if (zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields[field] && get('row__' + field)) {
						zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields[field].row_class = data.row_classes[field];
						get('row__' + field).className = 'zenario_ab_row zenario_ab_row__' + field + ' ' + data.row_classes[field];
					}
				}
			}
			
			if (data && data.snippets) {
				foreach (data.snippets as var field) {
					if (zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields[field]
					 && zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields[field].snippet
					 && zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields[field].snippet.html && get('snippet__' + field)) {
						zenarioAB.tuix.tabs[zenarioAB.tuix.tab].fields[field].snippet.html = data.snippets[field];
						get('snippet__' + field).innerHTML = data.snippets[field];
					}
				}
			}
		},
	'text');
	
	return true;
};






},
	''
);
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
	zenarioF, extraVar2, s$s
) {
	zenarioT.lib(function(
		_$html,
		_$div,
		_$span,
		_$input,
		_$select,
		_$option,
		_$h1,
		_$p,
		__$type,
		__$button,
		__$class,
		__$submit,
		__$value,
		__$onclick,
		__$style
	) {
		"use strict";
		
		var methods = methodsOf(zenarioF);



methods.init = function(globalName, microtemplatePrefix, containerId) {
	
	this.globalName = globalName;
	this.mtPrefix = microtemplatePrefix;
	this.containerId = containerId;
	
	this.baseCSSClass = '';
	this.onKeyUpNum = 0;
	zenarioAB.sizing = false;
	this.cachedAJAXSnippets = {};
	this.changed = {};
	this.toggleLevelsPressed = {};
	this.lastFieldInFocus = false;
	this.editingPositions = {};
};

//When looking for an element by id, if a container id is set, try to find an element in that container.
//Otherwise just call get(), which looks for elements anywhere on the page
methods.get = function(el) {
	return (this.containerId && $('#' + this.containerId + ' #' + zenario.cssEscape(el))[0]) || get(el);
};

methods.microTemplate = function(template, data, filter) {
	return zenarioT.microTemplate(template, data, filter);
};




methods.onThisRow = function(name, id) {
	return name + id.replace(/(.*_|\D)/g, '');
};

methods.valueOnThisRow = function(name, id) {
	return this.value(this.onThisRow(name, id));
};

methods.fieldOnThisRow = function(name, id) {
	return this.field(this.onThisRow(name, id));
};


methods.getKey = function(itemLevel) {
	return this.tuix && this.tuix.key;
};

methods.getKeyId = function(limitOfOne) {
	return this.tuix && this.tuix.key && this.tuix.key.id;
};

methods.getLastKeyId = function(limitOfOne) {
	return this.getKeyId(limitOfOne);
};


methods.getTitle = function() {
	
	var title, values;
	
	if (this.tuix.key
	 && this.tuix.key.id
	 && (title = this.tuix.title_for_existing_records)) {
		values = this.getValues1D(false, undefined, true);
	
		foreach (values as c => v) {
			if (title.indexOf('[[' + c + ']]') != -1) {
			
				while (title != (string2 = title.replace('[[' + c + ']]', v))) {
					title = string2;
				}
			}
		}
		
		return title;
		
	} else {
		return this.tuix.title;
	}
};


methods.field = function(id, tab) {
	if (_.isObject(id)) {
		return id;
	} else {
		return (tabs = this.tuix && this.tuix.tabs)
			&& (tab = tab || this.tuix.tab)
			&& (tabs[tab]
			 && tabs[tab].fields
			 && tabs[tab].fields[id]);
	}
};

methods.fields = function(tab) {
	var tabs;
	return (tabs = this.tuix && this.tuix.tabs)
		&& (tab = tab || this.tuix.tab)
		&& (tabs[tab]
		 && tabs[tab].fields);
};


//Setup some fields when the Admin Box is first loaded/displayed
methods.initFields = function() {
	
	var currentTab = this.tuix.tab,
		tab, id, i, panel, fields, f, field, fieldId,
		tabHasRequiredIfNotHiddenFields, hiddenFieldsByIndent, fieldValuesByIndent,
		lovs = this.tuix.lovs || {};
	
	if (this.tuix.tabs) {
		foreach (this.tuix.tabs as tab) {
			
			if (fields = this.fields(tab)) {
				tabHasRequiredIfNotHiddenFields = false;
				
				foreach (fields as id => field) {
					if (field) {
						
						//Ensure that the display values for <use_value_for_plugin_name> fields are always looked up,
						//even if that field is never actually shown
						if (field.pick_items
						 && field.plugin_setting
						 && field.plugin_setting.use_value_for_plugin_name) {
							this.pickedItemsArray(id, this.value(id, tab, false));
						
						} else
						if (field.values
						 && _.isString(field.values)
						 && lovs[field.values]) {
							field.values = lovs[field.values];
						
						} else
						if (field.load_values_from_organizer_path && !field.values) {
							this.loadValuesFromOrganizerPath(field);
						}
						
						//Look through all of the fields on all of the tabs, looking for the "required_if_not_hidden" property.
						if (field.validation
						 && field.validation.required_if_not_hidden !== undefined) {
							tabHasRequiredIfNotHiddenFields = true;
						}
					}
				}
				
				//This is a work-around to avoid a bug with the "required_if_not_hidden" property.
				//If a field was never shown, the system does not correctly track whether it was hidden, and raises
				//and error. To work around this, we'll do a fake "display" of each tab with one of these fields on it
				//when we first open the FAB.
				//There's no need to look in the tab that's about to be drawn, this bug doesn't apply there
				if (tabHasRequiredIfNotHiddenFields && tab != currentTab) {
					hiddenFieldsByIndent = {};
					fieldValuesByIndent = {};
					
					//Set the current tab in TUIX to the tab we're currently checking
					this.tuix.tab = tab;
					
					//If we find it, then we need to call drawField() for each field on the tab, and just
					//run the first part of the function (that sets meta-data for hidden fields).
					this.drawFields(undefined, undefined, true);
					
					//Set the tab back to what it was before
					this.tuix.tab = currentTab;
				}
			}
		}
	}
};


methods.loadValuesFromOrganizerPath = function(field) {
	
	var i, panel, item;
	
	if (field.load_values_from_organizer_path && !field.values) {
		field.values = {};
		if (panel = zenarioA.getItemFromOrganizer(field.load_values_from_organizer_path)) {
			if (panel.items) {
				foreach (panel.items as i => item) {
					field.values[zenario.decodeItemIdForOrganizer(i)] = {
						list_image: item.list_image,
						css_class: item.css_class || (panel.item && panel.item.css_class),
						label: zenarioA.formatOrganizerItemName(panel, i)
					};
				}
			}
		}
	}
};

methods.changeMode = function() {
	if (this.editCancelEnabled(this.tuix.tab)) {
		if (this.editModeOn()) {
			this.editCancelOrRevert('cancel', this.tuix.tab);
		} else {
			this.editCancelOrRevert('edit', this.tuix.tab);
		}
	}
};

methods.revertTab = function() {
	if (this.changes(this.tuix.tab) && this.revertEnabled(this.tuix.tab)) {
		this.editCancelOrRevert('revert', this.tuix.tab);
	}
};

methods.editCancelOrRevert = function(action, tab) {
	
	if (!this.tuix.tabs[tab].edit_mode) {
		return;
	}
	
	var value,
		fields, f, field,
		needToFormat,
		needToValidate;
	
	if (action == 'edit') {
		needToFormat = engToBoolean(this.tuix.tabs[tab].edit_mode.format_on_edit);
		needToValidate = engToBoolean(this.tuix.tabs[tab].edit_mode.validate_on_edit);
	
	} else if (action == 'cancel') {
		needToFormat = engToBoolean(this.tuix.tabs[tab].edit_mode.format_on_cancel_edit);
		needToValidate = engToBoolean(this.tuix.tabs[tab].edit_mode.validate_on_cancel_edit);
	
	} else if (action == 'revert') {
		needToFormat = engToBoolean(this.tuix.tabs[tab].edit_mode.format_on_revert);
		needToValidate = this.errorOnTab(tab) || engToBoolean(this.tuix.tabs[tab].edit_mode.validate_on_revert);
	}
	
	if (!needToValidate
	 && (fields = this.fields(tab))) {
		foreach (fields as var f => field) {
			if (engToBoolean(field.validate_onchange)
			 && ((value = this.readField(f)) !== undefined)
			 && (value != field.value)) {
				needToValidate = true;
				break;
			
			} else
			if (!needToFormat
			 && engToBoolean(field.format_onchange)
			 && ((value = this.readField(f)) !== undefined)
			 && (value != field.value)) {
				needToFormat = true;
			}
		}
	}
	
	this.tuix.tabs[tab].edit_mode.on = action != 'cancel';
	this.changed[tab] = false;
	
	if (needToValidate) {
		this.validate(undefined, undefined, true);
	
	} else if (needToFormat) {
		this.format(true);
	
	} else {
		this.wipeTab();
		this.redrawTab(true);
	}
	
	if (this.tuix.tab == tab) {
		$('#zenario_abtab').removeClass('zenario_abtab_changed');
	}
};


methods.clickTab = function(tab) {
	if (this.loaded) {
		this.validate(tab != this.tuix.tab, tab);
	}
};

methods.clickButton = function(el, id) {
	
	var button;
	
	if (button = this.field(id)) {
		if (button.type == __$submit) {
			button.pressed = true;
			this.validate(true);
	
		} else if (button.type == 'toggle') {
			if (button.pressed = !engToBoolean(button.pressed)) {
				$('#' + id).removeClass('not_pressed').addClass('pressed');
			} else {
				$('#' + id).removeClass('pressed').addClass('not_pressed');
			}
		
			this.validateFormatOrRedrawForField(button);
	
		} else if (button.type == __$button) {
			button.pressed = true;
			this.validateFormatOrRedrawForField(button);
		}
	}
};


methods.togglePressed = function(toggleLevel, tuixObject) {
	
	var show;
	
	if (!toggleLevel) {
		show = this.toggleLevelsPressed.last;
	
	} else if (toggleLevel > 1) {
		show = this.toggleLevelsPressed[toggleLevel - 1];
	
	} else {
		show = true;
	}
	
	if (show === undefined) {
		show = true;
	}
	
	if (tuixObject && tuixObject.type == 'toggle') {
		this.toggleLevelsPressed.last = show && engToBoolean(tuixObject.pressed);
		
		if (toggleLevel) {
			this.toggleLevelsPressed[toggleLevel] = this.toggleLevelsPressed.last;
		}
	}
	
	return show;
};

methods.toggleLevel = function(tuixObject) {
	return this.toggleLevelsPressed.last;
};

//A wrapper for zenario.ajax(), that sets the retry and continueAnyway options,
//and also sets the "now loading" message.
methods.retryAJAX = function(url, post, json, done, nowDoingSomething, onCancel) {
	var doAJAX = function() {
		zenario.ajax(url, post, json, undefined, doAJAX, true, undefined, undefined, undefined, undefined, onCancel).after(done);
		//(url, post, json, useCache, retry, continueAnyway, settings, timeout, AJAXErrorHandler, onRetry, onCancel)
		
		if (nowDoingSomething
		 && zenarioA.nowDoingSomething) {
			zenarioA.nowDoingSomething(nowDoingSomething);
		}
	}
	
	doAJAX();
};


methods.switchToATabWithErrors = function() {
	
	var i, tab, tuix = this.tuix
	
	if (tuix && tuix.tabs) {
		if (!this.errorOnTab(tuix.tab)) {
			foreach (this.sortedTabs as i => tab) {
				
				if (tuix.tabs[tab]
					//zenarioT.hidden(tuixObject, lib, item, id, button, column, field, section, tab, tuix)
				 && !zenarioT.hidden(undefined, this, undefined, tab, undefined, undefined, undefined, undefined, tuix.tabs[tab])) {
					if (this.errorOnTab(tab)) {
						tuix.tab = tab;
						return true;
					}
				}
			}
		}
	}
	
	return false;
};


methods.applyMergeFields = function(string, escapeHTML, i, keepNewLines) {
	return string;
};

methods.applyMergeFieldsToLabel = function(label, isHTML, itemLevel, multiSelectLabel) {
	return label;
};


methods.hideTab = function(differentTab) {
	var that = this;
	
	if (differentTab) {
		$('#zenario_abtab input').add('#zenario_abtab select').add('#zenario_abtab textarea').attr('disabled', 'disabled');
		$('#zenario_abtab').clearQueue().show().animate({opacity: .8}, 200, function() {
			that.tabHidden = true;
			that.draw();
		});
	} else {
		$('#zenario_abtab input').add('#zenario_abtab select').add('#zenario_abtab textarea').attr('disabled', 'disabled').animate({opacity: .9}, 100);
		this.tabHidden = true;
		this.draw();
	}
};

methods.draw = function() {
	if (this.loaded && this.tabHidden) {
		this.draw2();
	}
};


methods.draw2 = function() {
	//var cb = new zenario.callback,
	//	html = zenarioAB.drawFields(cb);
	//...
};






methods.drawTabs = function(microTemplate) {
	
	if (!microTemplate) {
		microTemplate = this.mtPrefix + '_tab';
	}
	
	//Generate the HTML for the tabs
	var tabTUIX,
		onclick,
		data = [],
		i, tab;
	
	foreach (this.sortedTabs as i => tab) {
		tabTUIX = this.tuix.tabs[tab];
		if (tabTUIX
			//zenarioT.hidden(tuixObject, lib, item, id, button, column, field, section, tab, tuix)
		 && !zenarioT.hidden(undefined, this, undefined, tab, undefined, undefined, undefined, undefined, tabTUIX)) {
			
			//Only allow this tab to be clicked if it looks like there's something on it
			//Dummy tabs that only exist to be the parents in drop-down menus should not be clickable
			onclick = '';
			if ((tabTUIX.fields && !_.isEmpty(tabTUIX.fields))
			 || (tabTUIX.fields && !_.isEmpty(tabTUIX.errors))
			 || (tabTUIX.fields && !_.isEmpty(tabTUIX.notices))) {
				onclick = this.globalName + ".clickTab('" + jsEscape(tab) + "');";
			}
			
			//Show the first (clickable) tab we find, if a tab has not yet been set
			if (!this.tuix.tab && onclick) {
				this.tuix.tab = tab;
			}
			
			data.push({
				id: tab,
				tabId: tab,
				tuix: tabTUIX,
				onclick: onclick,
				current: this.tuix.tab == tab,
				label: tabTUIX.label
			});
		}
	}
	
	zenarioT.setButtonKin(data, 'zenario_fab_tab_with_children');
	
	return this.microTemplate(microTemplate, data);
};


methods.drawFields = function(cb, microTemplate, scanForHiddenFieldsWithoutDrawingThem) {
	
	var f,
		fieldId,
		field,
		tab = this.tuix.tab,
		tabs = this.tuix.tabs[tab],
		groupings = this.groupings[tab],
		sortedFields = this.sortedFields[tab],
		html = '',
		groupingIsHidden = false,
		forceNewRowForNewGrouping,
		fields = this.fields(tab),
		hiddenFieldsByIndent = {},
		fieldValuesByIndent = {},
		errorsDrawn = false,
		i, error, field, notice,
		groupingName, lastGrouping, lastVisibleGrouping, groupingField = false, groupingsDrawn = {},
		data = {
			fields: {},
			rows: [],
			tabId: tab,
			path: this.path,
			tuix: this.tuix,
			revert: '',
			errors: [],
			notices: {}
		};
	
	
	if (!scanForHiddenFieldsWithoutDrawingThem) {
		this.ffoving = 0;
		this.splitValues = {};
		zenarioT.clearHTML5UploadFromDragDrop();
	
		if (!this.savedAndContinued(tab) && this.editCancelEnabled(tab)) {
			data.revert =
				_$div(__$class, 'zenario_editCancelButton',
					zenarioT.input(
						__$class, __$submit,
						__$type, __$button,
						__$onclick, this.globalName + '.changeMode(); return false;',
						__$value, this.editModeOn()? phrase.cancel : phrase.edit
					)
				);
		}
	
		if (this.editModeOn()) {
			if (tabs.errors) {
				foreach (tabs.errors as i => error) {
					data.errors.push({message: error});
				}
			}
		
			//Errors can be linked to fields, but we don't have any way of displaying this so
			//we'll just display field errors at the top of the tab with the others.
			if (fields) {
				foreach (fields as i => field) {
					if (field.error) {
						data.errors.push({message: field.error});
					}
				}
			}
		}
	
		if (tabs.notices) {
			foreach (tabs.notices as i => notice) {
				if (engToBoolean(notice.show)
				 && {error: 1, warning: 1, question: 1, success: 1}[notice.type]) {
					data.notices[i] = notice;
				}
			}
		}
	}
	
	
	foreach (sortedFields as f => fieldId) {
		field = fields[fieldId];
		
		//Note down if the last field was in a grouping
		forceNewRowForNewGrouping = false;
		groupingName = field.grouping;
		
		if (lastGrouping !== groupingName) {
			lastGrouping = groupingName;
			
			groupingField = groupings[groupingName] !== undefined && fields[groupings[groupingName]];
			
			//Fields in a grouping should be hidden if the grouping is hidden, or does not actually exist
			groupingIsHidden = groupingName && (
				!groupingField || zenarioT.hidden(undefined, this, undefined, groupings[groupingName], undefined, undefined, groupingField)
			);
		}
			
		if (lastVisibleGrouping !== groupingName) {
			//Force different groupings to be on a new row, even if the same_row property is set
			forceNewRowForNewGrouping = true;
		}
		
		field._id = fieldId;
		field._html = this.drawField(cb, tab, fieldId, field, hiddenFieldsByIndent, fieldValuesByIndent, scanForHiddenFieldsWithoutDrawingThem, groupingIsHidden);
		
		//Don't add completely hidden fields, or if we're just scanning fields and not drawing them
		if (scanForHiddenFieldsWithoutDrawingThem
		 || groupingIsHidden
		 || field._html === false) {
			continue;
		}
		
		if (forceNewRowForNewGrouping
		 || field._startNewRow
		 || !data.rows.length) {
			data.rows.push({fields: []});
		}
		
		//If the grouping field has a legend or any HTML at the top, include it
		if (forceNewRowForNewGrouping
		 && groupingField
		 && (groupingField.legend || groupingField.snippet)) {
			groupingField = _.extend({grouping: groupingName}, groupingField);
			groupingField._id = groupingName;
			groupingField._html = this.drawField(cb, tab, groupingName, groupingField);
			groupingField._hideOnOpen = true;
			groupingField._showOnOpen = true;
			
			groupingField._lastVisibleGrouping = lastVisibleGrouping;
			lastVisibleGrouping = groupingName;
			
			data.rows[data.rows.length-1].fields.push(groupingField);
			data.fields[groupingName] = groupingField;
			groupingsDrawn[groupingName] = groupingField;
			
			data.rows.push({fields: []});
		}
		
		if (!errorsDrawn && tabs.show_errors_after_field == fieldId) {
			data.rows[data.rows.length-1].errors = data.errors;
			data.rows[data.rows.length-1].notices = data.notices;
			errorsDrawn = true;
		}
		
		field._lastVisibleGrouping = lastVisibleGrouping;
		lastVisibleGrouping = groupingName;
		
		data.rows[data.rows.length-1].fields.push(field);
		data.fields[fieldId] = field;
	}
	
	//If we displayed any grouping labels, they may need to be animated,
	//depending on how the fields in them are animated:
		//If any fields in the grouping were previously visible, then no animations are needed on the grouping
		//If all of the visible fields in a grouping are animating open, then the grouping should also animate open
		//If all of the visible fields in a grouping are animating closed, then the grouping should also animate closed
	foreach (data.fields as fieldId => field) {
		if (groupingName = field.grouping) {
			if (groupingField = groupingsDrawn[groupingName]) {
				groupingField._hideOnOpen &= field._hideOnOpen;
				groupingField._showOnOpen &= field._showOnOpen;
			}
		}
	}
	
	
	if (!scanForHiddenFieldsWithoutDrawingThem) {
	
		if (data.rows.length) {
			data.rows[data.rows.length-1].fields[0]._isLastRow = true;
		}
	
		if (!errorsDrawn) {
			//If there wasn't a field specified to show the errors before,
			//show the errors at the very start by inserting a dummy field at the beginning
			data.rows.splice(0, 0, {errors: data.errors, notices: data.notices});
		}
	
		microTemplate = microTemplate || tabs.template || this.mtPrefix + '_current_tab';
		html += this.microTemplate(microTemplate, data);
	}
	
	
	foreach (sortedFields as f => fieldId) {
		
		delete fields[fieldId]._lastVisibleGrouping;
		delete fields[fieldId]._startNewRow;
		delete fields[fieldId]._hideOnOpen;
		delete fields[fieldId]._showOnOpen;
		delete fields[fieldId]._isLastRow;
		delete fields[fieldId]._html;
		delete fields[fieldId]._id;
	}
	
	return html;
};

methods.insertHTML = function(html, cb, isNewTab) {
	var id,
		tab = this.get('zenario_abtab'),
		details,
		language,
		DOMlastFieldInFocus;
	
	tab.innerHTML = html;
	this.tabHidden = false;
	
	if (this.changes(this.tuix.tab)) {
		$(tab).addClass('zenario_abtab_changed');
	} else {
		$(tab).removeClass('zenario_abtab_changed');
	}
	
	cb.call();
	
	if (!isNewTab
	 && this.lastFieldInFocus
	 && (DOMlastFieldInFocus = this.get(this.lastFieldInFocus))) {
		DOMlastFieldInFocus.focus();
	}
};

methods.hideShowFields = function(onShowFunction) {
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


methods.addJQueryElementsToTab = function() {
	//Add any special jQuery objects to the tab
	zenario.addJQueryElements('#zenario_abtab ', true);
};

methods.addJQueryElementsToTabAndFocusFirstField = function() {
	//Add any special jQuery objects to the tab
	zenario.addJQueryElements('#zenario_abtab ', true);
	
	this.focusFirstField();
};


//Focus either the first field, or if the first field is filled in and the second field is a password then focus that instead
methods.focusFirstField = function() {
	
	if (!this.tuix
	 || engToBoolean(this.tuix.tabs[this.tuix.tab].disable_autofocus)) {
		return;
	}
	
	//Loop through the text-fields on a tab, looking for the first few fields
	var that = this,
		i = -1,
		fields = [],
		focusField = undefined,
		f, domField, field, fieldId,
		isPickerField;
	
	foreach (this.sortedFields[this.tuix.tab] as f => fieldId) {
		
		if ((domField = this.get(fieldId))
		 && (field = this.field(fieldId))
		 && ((isPickerField = field.pick_items || field.upload) || $(domField).is(':visible'))) {
			
			fields[++i] = {
				id: fieldId,
				type: field.type,
				empty: domField.value == '',
				focusable:
					(isPickerField || zenario.IN(field.type, 'password', 'checkbox', 'select', 'text', 'textarea'))
				 && !engToBoolean(domField.disabled)
				 && !engToBoolean(domField.readonly)
				 && !engToBoolean(field.read_only)
				 && !engToBoolean(field.readonly)
			};
			
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
		field = fields[focusField];
		if (field.type) {
			that.get(field.id).focus();
		} else {
			that.$getPickItemsInput(field.id).focus();
		}
	}, 50);
};

methods.$getPickItemsInput = function(id) {
	return $(this.get('name_for_' + id)).find('.TokenSearch input');
};

methods.focusField = function() {
	if (this.fieldToFocus && this.get(this.fieldToFocus) && $(this.get(this.fieldToFocus)).is(':visible')) {
		this.get(this.fieldToFocus).focus();
	}
	delete this.fieldToFocus;
};

methods.errorOnBox = function() {
	if (this.tuix && this.tuix.tabs) {
		foreach (this.tuix.tabs as tab) {
			if (this.errorOnTab(tab)) {
				return true;
			}
		}
	}
	
	return false;
};

methods.errorOnTab = function(tab) {
	var i, fields;
	
	if (this.tuix.tabs[tab] && this.editModeOn(tab)) {
		if (this.tuix.tabs[tab].errors) {
			foreach (this.tuix.tabs[tab].errors as i) {
				return true;
			}
		}
		if (fields = this.fields(tab)) {
			foreach (fields as i) {
				if (fields[i].error) {
					return true;
				}
			}
		}
	}
};

methods.checkValues = function(wipeValues) {
	
	if (wipeValues) {
		this.wipeTab();
	} else {
		this.readTab();
	}
	
	foreach (this.tuix.tabs as var tab => var thisTab) {
		
		//Workaround for a problem where initial values do not get submitted if a tab is never visited.
		//This script loops through all of the tabs and all of the fields on this admin boxes, and ensures
		//that their values are set correctly.
		var editing = this.editModeOn(tab);
		if (thisTab.fields) {
			foreach (thisTab.fields as var f) {
				
				var field = thisTab.fields[f],
					multi = field.pick_items || field.type == 'checkboxes' || field.type == 'radios';
				
				//Ignore non-field types
				if (this.isFormField(field)) {
					
					if (field.type == 'code_editor') {
						zenario.clearAllDelays('code_editor_' + f);
					}
					
					if (field.current_value === undefined) {
						field.current_value = this.value(f, tab, true);
					}
					
					if (editing) {
						if (field.value === undefined) {
							field.value = this.value(f, tab, false);
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
	
	if (document
	 && document.activeElement) {
		this.lastFieldInFocus = document.activeElement.id;
	}
};

	

methods.enableMicroTemplates = function(field, object) {
	
	var that = this,
		key;
	
	foreach (object as key) {
		switch (typeof object[key]) {
			case 'object':
				if (key !== 'that'
				 && key !== 'tuix') {
					this.enableMicroTemplates(field, object[key]);
				}
				break;
			
			case 'string':
				if (key !== __$type
				 && (key !== __$value || !this.isFormField(object))
				 && key !== 'current_value'
				 && object[key].match(/(\{\{|\{\%|\<\%)/)) {
					(function(object, key, fun) {
						fun = zenario.generateMicroTemplate('<% ' + that.defineLibVarBeforeCode() + ' %>' + object[key], undefined);
						object[key] = function() {
							return fun(object);
						}
					})(object, key);
				}
		}
	}
	
};

methods.drawField = function(cb, tab, id, field, hiddenFieldsByIndent, fieldValuesByIndent, scanForHiddenFieldsWithoutDrawingThem, groupingIsHidden, lov, value, readOnly, tempReadOnly, sortOrder, existingParents, lovField) {
	
	if (field === undefined) {
		field = this.field(id, tab);
	}
	if (field.id === undefined) {
		field.id = id;
	}
	
	if (engToBoolean(field.enable_microtemplates_in_properties)) {
		this.enableMicroTemplates(field, field);
	}
	
	if (readOnly === undefined) {
		readOnly = !this.editModeOn(tab) || engToBoolean(field.read_only) || engToBoolean(field.readonly);
	}
	
	var fieldType = field.type;
	
	//Currently date-time fields are readonly
	if (fieldType && fieldType == 'datetime') {
		readOnly = true;
	}
	
	if (lovField) {
		if (lovField.disabled_if !== undefined
			//zenarioT.eval(c, lib, tuixObject, item, id, button, column, field, section, tab, tuix)
		 && zenarioT.eval(lovField.disabled_if, this, lovField, undefined, lov, undefined, undefined, field)) {
			readOnly = true;
		}
	} else
	if (field.disabled_if !== undefined
	 && zenarioT.eval(field.disabled_if, this, undefined, undefined, id, undefined, undefined, field)) {
		readOnly = true;
	}
	
		
	var that = this,
		//Most attributes that are part of the HTML spec we'll pass on directly
		allowedAtt = {
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
			'max': true,
			'min': true,
			'pattern': true,
			'placeholder': !readOnly,	//Only allow placeholders if fields are editable
			'required': true,
			'step': true
		},
		hidden,
		hideOnOpen,
		indent,
		newRow,
		snippet,
		html = '',
		i, v, val, displayVal,
		splitValues,
		picked_items = {},
		hasSlider = false,
		extraAtt = {'class': ''},
		extraAttAfter = {},
		isNormalTextField = true,
		parentsValuesExist = false,
		selected_option,
		prop, match,
		useButtonTag = false,
		startOfTable = false,
		middleOfTable = false,
		hasPreFieldTags = false,
		hasPostFieldTags = false,
		preFieldTags,
		postFieldTags;
	
	
	if (!field[__$class] && field.css_class) {
		field[__$class] = field.css_class;
	}
	
	if (!field.snippet && value === undefined) {
		value = this.value(id, tab, undefined, true);
	}
	
	if (value === undefined) {
		value = '';
	}

	
	//If this is the first call and not a sub-call
	if (lov === undefined) {
	
		//Set up a shortcut to the selected value.
		//This makes some visible-if statements simplier to write
		delete field.selected_option;
		if (value && field.values) {
			selected_option = _.isObject(field.values)?
				field.values[value]
			  : this.tuix.lovs
			 && this.tuix.lovs[field.values]
			 && this.tuix.lovs[field.values][value];
			
			//Catch the case where an option was visible, but is now hidden.
			//In this case it shouldn't count as selected.
			if (_.isObject(selected_option)
				//zenarioT.hidden(tuixObject, lib, item, id, button, column, field, section, tab, tuix)
			 && zenarioT.hidden(selected_option, this, undefined, value, undefined, undefined, field, undefined, this.tuix.tabs[tab])) {
				selected_option = false;
			}
			
			if (selected_option) {
				field.selected_option = selected_option;
		
			//Catch the case where an option was removed from a LOV for a select list or radio-group.
			//In this case, reset the value!
			} else {
				if (fieldType == 'radio'
				 || fieldType == 'select') {
					field.current_value = value = '';
				}
			}
		}
		
		//If this is a picker-type field, and one or more values are selected, ensure that the values object is set up
		if ((field.upload || field.pick_items) && value != '') {
			picked_items = this.pickedItemsArray(field, value);
		}
		
		if (field.values
		 && _.isString(field.values)
		 && this.tuix.lovs
		 && this.tuix.lovs[field.values]) {
			field.values = this.tuix.lovs[field.values];
		
		} else
		if (field.load_values_from_organizer_path && !field.values) {
			this.loadValuesFromOrganizerPath(field);
		}
		
		
		
		
		//Allow people writing YAML files to put HTML tags in as property names
		//as a tidier shortcut to using pre/post field HTML.
		foreach (field as prop) {
			if (match = prop.match(/^<(\/?)(\w+)/)) {
				
				//Add any HTML in the value on as well
				if (field[prop] && _.isString(field[prop])) {
					prop += field[prop];
				}
				
				//We can't rely on JavaScript maintaining the order of the tags from PHP.
				//To work round this problem we'll sort them into a consistant order
				i = {table: 1, tr: 2, td: 3, th: 4, div: 5, p: 6, span: 7}[match[2]] || 8;
				
				if (i < 5) {
					middleOfTable = true;
				}
				
				//If the string starts with an opening tag, put it before the field
				//If the string starts with a closing tag, put it after the field in reverse order.
				if (match[1]) {
					if (!hasPostFieldTags) {
						hasPostFieldTags = true;
						postFieldTags = ',,,,,,,'.split(',');
					}
					postFieldTags[8 - i] += prop;
				} else {
					if (!hasPreFieldTags) {
						hasPreFieldTags = true;
						preFieldTags = ',,,,,,,'.split(',');
					}
					preFieldTags[i - 1] += prop;
					
					if (i == 1) {
						startOfTable = true;
					}
				}
			}
		}
	}
	
	//Buttons and submit buttons should lose their "pressed" status when redrawn
	if (!scanForHiddenFieldsWithoutDrawingThem && (fieldType == __$button || fieldType == __$submit)) {
		field.pressed = false;
	}
	
	if (scanForHiddenFieldsWithoutDrawingThem || lov === undefined) {
		//Close the last row if it was left open, unless this field should be on the same line
		//Either check the same_row property to work this out, or if same_row was not set,
		//try to set it automatically if someone is using a table-layout.
		if (field.same_row === undefined)  {
			newRow = startOfTable || !middleOfTable;
		} else {
			newRow = !engToBoolean(field.same_row);
		}
		field._startNewRow = newRow;
		
		//Check if this field should be hidden
		indent = 1 * field.indent || 0;
		hidden = groupingIsHidden
		
			  || zenarioT.hidden(undefined, this, undefined, id, undefined, undefined, field)
			
			  || (engToBoolean(hiddenFieldsByIndent && hiddenFieldsByIndent.last? field.hide_with_previous_field : field.hide_if_previous_field_is_not_hidden))
		
			  || (engToBoolean(field.hide_with_previous_indented_field)
			   && hiddenFieldsByIndent
			   && hiddenFieldsByIndent[indent])
		
			  || (engToBoolean(field.hide_with_previous_outdented_field)
			   && indent > 0
			   && hiddenFieldsByIndent
			   && hiddenFieldsByIndent[indent - 1])
		
			  || (field.hide_if_previous_value_isnt !== undefined
			   && !zenario.inList(field.hide_if_previous_value_isnt, fieldValuesByIndent && fieldValuesByIndent.last))
		
			  || (field.hide_if_previous_outdented_value_isnt !== undefined
			   && indent > 0
			   && !zenario.inList(field.hide_if_previous_outdented_value_isnt, fieldValuesByIndent && fieldValuesByIndent[indent - 1]));
		
		if (fieldValuesByIndent) {
			fieldValuesByIndent.last = value;
			
			if (newRow) {
				fieldValuesByIndent[indent] = value;
			}
		}
		if (hiddenFieldsByIndent) {
			hiddenFieldsByIndent.last = hidden;
			
			if (newRow) {
				hiddenFieldsByIndent[indent] = hidden;
			}
		}
		
		//Include an animation to show newly unhidden fields
		if (field._startNewRow
		 && this.shownTab !== false
		 && this.shownTab == this.tuix.tab
		 && fieldType != 'editor'
		 && fieldType != 'code_editor'
		 && field._was_hidden_before
		 && !hidden) {
			field._showOnOpen = true;
		
		//Include an animation to hide newly hidden fields
		} else
		if (field._startNewRow
		 && this.shownTab !== false
		 && this.shownTab == this.tuix.tab
		 && fieldType != 'editor'
		 && fieldType != 'code_editor'
		 && !field._was_hidden_before
		 && hidden) {
			field._hideOnOpen = hideOnOpen = true;
			field._was_hidden_before = true;
		}
		
		if (hidden) {
			field._was_hidden_before = true;
			
			//Don't show hidden fields, unless we need to draw them for the "hiding" animation
			if (!hideOnOpen) {
				return false;
			}
		} else {
			delete field._was_hidden_before;	
		}
		
		if (scanForHiddenFieldsWithoutDrawingThem) {
			return;
		}
		
		
	
		if (field.multiple_edit) {
			var meHTML = '',
				meId,
				changed,
				hideUI;
		
			if (!readOnly && field.multiple_edit._changed !== undefined) {
				changed = engToBoolean(field.multiple_edit._changed);
			} else {
				changed = field.multiple_edit.changed;
			}
			
			if (engToBoolean(field.multiple_edit.disable_when_unchanged) && !changed) {
				tempReadOnly = true;
			}
			
			if (engToBoolean(field.multiple_edit.hide_ui) || !field.multiple_edit.select_list) {
				
				hideUI = engToBoolean(field.multiple_edit.hide_ui);
				
				meHTML +=
					_$input(__$type, 'checkbox', __$class, 'multiple_edit', 'id', 'multiple_edit__' + id, 'checked', changed,
						'style', hideUI? 'display: none;' : '',
						'disabled', readOnly,
						'onchange', readOnly || hideUI? '' : this.globalName + '.meChange(this.checked, \'' + htmlspecialchars(id) + '\');', 
					'>');
			
			} else {
				meHTML += _$select(
					__$class, 'multiple_edit', 'id', 'multiple_edit__' + id, 'disabled', readOnly,
					'onchange', this.globalName + '.meChange(this.value == 1, "' + jsEscape(id) + '");',
						_$option(__$value, '', 'selected', !changed, field.multiple_edit.select_list.not_changed_label || phrase.notChanged) +
						_$option(__$value, 1, 'selected', changed, field.multiple_edit.select_list.changed_label || phrase.changed)
				);
			}
		
			html += meHTML;
		
			//delete meHTML;
			meHTML = undefined;
		}
		
		
		if (hasPreFieldTags) {
			html += preFieldTags.join('');
		}
		if (field.pre_field_html !== undefined) {
			html += zenario.unfun(field.pre_field_html);
		}
	}
	
	
	//Ensure that fields with a list of values (e.g. select lists, checboxes, radios) have the values sorted
	//in the correct order.
	if (!(sortOrder && existingParents)
	 && typeof field.values == 'object') {
		
		sortOrder = [];
		existingParents = {};
		
		//if (field.upload || field.pick_items) {
		if (field.upload && engToBoolean(field.upload.reorder_items)) {
			//For pickers with the reorder_items property set, the sort order needs to be in the order the values are entered
			splitValues = value.split(',');
			
			foreach (splitValues as i => v) {
				sortOrder.push(v);
			}
			
		} else {
		
			//Build an array to sort, containing:
				//0: The item's actual index
				//1: The value to sort by
		
			foreach (field.values as v => val) {
				if (typeof val == 'object') {
				
					if (val.parent !== undefined) {
						parentsValuesExist = true;
						existingParents[val.parent] = true;
					}
					
					//zenarioT.hidden(tuixObject, lib, item, id, button, column, field, section, tab, tuix)
					if (zenarioT.hidden(val, this, undefined, v, undefined, undefined, field, undefined, this.tuix.tabs[tab])) {
						continue;
					} else if (val.ord !== undefined) {
						sortOrder.push([v, val.ord]);
					} else if (val.label !== undefined) {
						sortOrder.push([v, val.label]);
					} else {
						sortOrder.push([v, v]);
					}
				} else {
					sortOrder.push([v, val]);
				}
			}
	
			sortOrder.sort(zenarioT.sortArray);
		
			//Remove fields that were just there to help sort
			foreach (sortOrder as var i) {
				sortOrder[i] = sortOrder[i][0];
			}
		}
	}
	
	
	//Set the "readonly" flag for fields that are read-only
	if ((readOnly || tempReadOnly)) {
		extraAtt.readonly = true;
		
		//If this is a button, also set the disabled flag so we get the "disabled" styling
		//(We don't want to set this for text fields though, as that stops people from copy-pasting the values.)
		if (this.isButton(field)) {
			extraAtt.disabled = true;
			extraAtt[__$class] += 'disabled ';
		}
	}
	
	
	if (readOnly
	 && engToBoolean(field.show_as_a_label_when_readonly)
	 && fieldType != 'checkboxes') {
		
		displayVal = htmlspecialchars(value);
		
		if (fieldType == 'select' || fieldType == 'radios') {
			if (field.values && field.values[value]) {
				displayVal = htmlspecialchars(field.values[value]);
			
			} else if (field.empty_value) {
				displayVal = htmlspecialchars(field.empty_value);
			}
		}
		
		html += _$html('label', 'id', 'label__' + id, __$class, field.css_class, displayVal);
	
	//Draw HTML snippets
	} else if (snippet = field.snippet) {
		if (snippet.separator) {
			html += _$html('hr', __$class, 'input_separator', 'style', snippet.separator.style);
		}
		if (snippet.label) {
			html += _$html('label', 'id', 'label__' + id, __$class, snippet.label_class, htmlspecialchars(snippet.label));
		}
		
		//Draw the second part of a "type: radios" field that was split using the split_values_if_selected property
		if (snippet.show_split_values_from) {
			if (this.splitValues
			 && this.splitValues[snippet.show_split_values_from]) {
				html += this.splitValues[snippet.show_split_values_from];
				delete this.splitValues[snippet.show_split_values_from];
			}
		}
		
		if (snippet.html) {
			html += _$html('span', 'id', 'snippet__' + id, zenario.unfun(snippet.html));
		
		} else if (snippet.url) {
			if (!engToBoolean(snippet.cache)) {
				html += zenario.nonAsyncAJAX(zenario.addBasePath(snippet.url));
			
			} else if (!this.cachedAJAXSnippets[snippet.url]) {
				html += (this.cachedAJAXSnippets[snippet.url] = zenario.nonAsyncAJAX(zenario.addBasePath(snippet.url)));
			
			} else {
				html += this.cachedAJAXSnippets[snippet.url];
			}
		
		}
	
	//Draw multiple checkboxes/radiogroups
	} else if ((fieldType == 'checkboxes' || fieldType == 'radios') && lov === undefined) {
		if (field.values) {
			var picked_items = this.pickedItemsArray(field, value),
				thisField = _.extend({}, field);
			
			thisField.name = field.name || id;
			thisField.type = fieldType == 'checkboxes'? 'checkbox' : 'radio';
			
			if (readOnly) {
				thisField.disabled = true;
			}
			
			if (_.isEmpty(sortOrder)) {
				html += _$html('label', __$class, 'zenario_no_values_message', htmlspecialchars(field.no_values_message));
			} else {
				html += this.hierarchicalBoxes(cb, tab, id, value, field, thisField, picked_items, tempReadOnly, sortOrder, existingParents);
			}
		}
	
	} else if (field.upload || field.pick_items) {
		
		var multiple_select = ((field.pick_items && engToBoolean(field.pick_items.multiple_select))
						   || (field.upload && engToBoolean(field.upload.multi))),
			mergeFields = {
				id: id,
				pickerHTML: '',
				wrappedId: 'name_for_' + id,
				readOnly: readOnly,
				tempReadOnly: tempReadOnly
			};
		
		if (readOnly) {
			//mergeFields.pickedItems = this.drawPickedItems(id, true);
		
		} else {
			//mergeFields.pickedItems = this.drawPickedItems(id, false, tempReadOnly);
			
			if (field.pick_items
			 && (field.pick_items.target_path || field.pick_items.path)
			 && !engToBoolean(field.pick_items.hide_select_button)) {
				mergeFields.select = {
					onclick: this.globalName + ".pickItems('" + htmlspecialchars(id) + "');",
					phrase: field.pick_items.select_phrase || phrase.selectDotDotDot
				};
			}
			
			if (field.upload) {
				mergeFields.upload = {
					onclick: this.globalName + ".upload('" + htmlspecialchars(id) + "');",
					phrase: field.upload.upload_phrase || phrase.uploadDotDotDot
				};
				
				if (engToBoolean(field.upload.drag_and_drop)) {
					this.upload(id, true);
				}
				
				if (window.Dropbox && Dropbox.isBrowserSupported()) {
					mergeFields.dropbox = {
						onclick: this.globalName + ".chooseFromDropbox('" + htmlspecialchars(id) + "');",
						phrase: field.upload.dropbox_phrase || phrase.dropboxDotDotDot
					};
				}
			}
		}
		
	
		mergeFields.pickerHTML += _$select('id', id, 'multiple', multiple_select, '>');
		
		//If there are selected values, draw them in so that the tokenize library initialises correctly
		if (field.values && !_.isEmpty(picked_items)) {
			mergeFields.pickerHTML += this.hierarchicalSelect(picked_items, field, sortOrder, parentsValuesExist, existingParents);
		}
		
		mergeFields.pickerHTML += '</select>';
		html += this.microTemplate(this.mtPrefix + '_picked_items', mergeFields);
		
		cb.after(function() {
			that.setupPickedItems(id, tab, field, readOnly, multiple_select);
		});
		
		
		
	
	} else if (fieldType) {
		if (lov) {
			extraAttAfter.onchange = "lib.fieldChange('" + htmlspecialchars(id) + "', '" + htmlspecialchars(lov) + "');";
		} else {
			extraAttAfter.onchange = "lib.fieldChange('" + htmlspecialchars(id) + "');";
		}
		
		if (field.disabled) {
			extraAtt[__$class] += 'disabled ';
		}
		
		if (field.validation && field.validation.numeric) {
			extraAtt.onkeyup =
				(extraAtt.onkeyup || '') +
				"lib.keepNumeric(this)";
		}
		
		if (field.return_key_presses_button && !readOnly) {
			extraAtt.onkeyup =
				(extraAtt.onkeyup || '') +
				"if (event.keyCode == 13) {" +
					"$('#" + htmlspecialchars(field.return_key_presses_button) + "').click();" +
				"}";
		}
		
		if (engToBoolean(field.multiple_edit) && !readOnly) {
			extraAtt.onkeyup =
				(extraAtt.onkeyup || '') +
				"if (event && event.keyCode == 9) return true; lib.meMarkChanged('" + htmlspecialchars(id) + "', this.value, '" + htmlspecialchars(field.value) + "');";
				//Note keyCode 9 is the tab key; a field should not be marked as changed if the Admin is just tabbing through them
		}
		
		extraAtt[__$class] += 'input_' + fieldType;
		
		//Open the field's tag
		switch (fieldType) {
			case 'select':
				if (field.slider) {
					hasSlider = true;
					html += this.drawSlider(cb, id, field, readOnly, true);
				}
				
				//Most browsers still let you change select-lists if they are flagged as "readonly"
				//So set the "disabled" flag as well
				if (readOnly) {
					extraAtt.disabled = true;
				}
			
				html += '<select';
				
				break;
				
		
			case 'code_editor':
				html += '<div';
				extraAtt[__$class] = ' zenario_embedded_ace_editor';
			
				//Set up code editors after the HTML is drawn
				cb.after(function() {
					var codeEditor = ace.edit(id),
						editorJustChanged = false,
						editingPositions;
					codeEditor.session.setUseSoftTabs(false);
					codeEditor.setShowPrintMargin(false);
				
					if (readOnly) {
						codeEditor.setReadOnly(true);
						codeEditor.setBehavioursEnabled(false);
						//codeEditor.session.setOption("useWorker", false);
					}
				
					if (engToBoolean(field.wrap_text)) {
						codeEditor.session.setUseWrapMode(true);
					}
				
					codeEditor.session.setOption("useWorker", false);
				
					//Ace doesn't have the concept of an "on change" event, it only has something it fires
					//after every keystroke.
					//But we'll take this and the blur event and try and fake it as best we can!
					codeEditor.on('blur', function(e) {
						if (editorJustChanged) {
							zenario.actAfterDelayIfNotSuperseded('code_editor_' + id, function() {
								that.fieldChange(id);
							}, 0);
						}
						editorJustChanged = false;
					});
					codeEditor.on('change', function(e) {
						that.markAsChanged();
						editorJustChanged = true;
						
						zenario.actAfterDelayIfNotSuperseded('code_editor_' + id, function() {
							that.fieldChange(id);
						}, 5000);
					});
		
					//Attempt to set the correct language
					if (language = field.language) {
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
							codeEditor.setOptions({
								enableBasicAutocompletion: true,
								enableLiveAutocompletion: true
							});
				
						} catch (e) {
							console.log('Ace editor could not load that language', language);
						}
					}
					
					codeEditor.$blockScrolling = Infinity;
					
					if (editingPositions = that.editingPositions[tab + '/' + id]) {
						codeEditor.session.setScrollTop(editingPositions.top);
						codeEditor.session.setScrollLeft(editingPositions.left);
						codeEditor.selection.setSelectionRange(editingPositions.range);
					}
				});
				
				break;
			
			
			case 'color_picker':
			case 'colour_picker':
				html += '<input';
				extraAtt[__$class] = ' zenario_color_picker';
			
	
				//Set up the colour picker after the html is on the page
				cb.after(function() {
					var color_picker_options = field.color_picker_options || field.colour_picker_options || {};
				
					color_picker_options.disabled = readOnly;
					color_picker_options.preferredFormat = color_picker_options.preferredFormat || 'hex';
				
					$(that.get(id)).spectrum(color_picker_options);
				});
				
				break;
			
			
			case 'editor':
				html += '<textarea';
			
				zenarioA.getSkinDesc();
			
				var content_css = undefined,
					onchange_callback = function(inst) {
							that.fieldChange(inst.id);
						},
					options,
					readonlyOptions = {
							script_url: URLBasePath + zenario.tinyMCEPath,
		
							inline: false,
							menubar: false,
							statusbar: false,
							plugins: "autoresize",
							document_base_url: undefined,
							convert_urls: false,
							readonly: true,
		
							inline_styles: false,
							allow_events: true,
							allow_script_urls: true,
		
							autoresize_max_height: Math.max(Math.floor(($(window).height()) * 0.5), 300),
							autoresize_bottom_margin: 10,
		
							onchange_callback: onchange_callback,
							init_instance_callback: function(instance) {
								zenarioA.enableDragDropUploadInTinyMCE(false, undefined, that.get('row__' + (instance.editorId || instance.id)));
								var el;
								if ((el = instance.editorContainer)
								 && (el = $('#' + instance.editorContainer.id + ' iframe'))
								 && (el = el[0])
								 && (el = el.contentWindow)) {
									zenarioA.enableDragDropUploadInTinyMCE(false, undefined, el);
								}
							}
						},
					normalOptions = _.extend({}, readonlyOptions, {
							plugins: [
								"advlist autolink lists link image charmap hr anchor",
								"searchreplace code fullscreen",
								"nonbreaking table contextmenu directionality",
								"paste autoresize"],
		
							image_advtab: true,
							visual_table_class: ' ',
							browser_spellcheck: true,
		
							paste_preprocess: zenarioA.tinyMCEPasteRreprocess,

							readonly: false,
						
							convert_urls: true,
							relative_urls: false,
		
							content_css: content_css,
							toolbar: 'undo redo | bold italic | removeformat | fontsizeselect | formatselect | numlist bullist | blockquote outdent indent | code',
							style_formats: zenarioA.skinDesc.style_formats,
							oninit: undefined
						}),
					optionsWithImagesAndLinks = _.extend({}, normalOptions, {
							toolbar: 'undo redo | image link unlink | bold italic | removeformat | fontsizeselect | formatselect | numlist bullist | blockquote outdent indent | code',
		
							file_browser_callback: zenarioA.fileBrowser,
							init_instance_callback: function(instance) {
								zenarioA.enableDragDropUploadInTinyMCE(true, URLBasePath, that.get('row__' + (instance.editorId || instance.id)));
								var el;
								if ((el = instance.editorContainer)
								 && (el = $('#' + instance.editorContainer.id + ' iframe'))
								 && (el = el[0])
								 && (el = el.contentWindow)) {
									zenarioA.enableDragDropUploadInTinyMCE(true, URLBasePath, el);
								}
							}
						}),
					optionsWithImages = _.extend({}, optionsWithImagesAndLinks, {
							toolbar: 'undo redo | image | bold italic | removeformat | fontsizeselect | formatselect | numlist bullist | blockquote outdent indent | code'
						}),
					optionsWithLinks = _.extend({}, optionsWithImages, {
							toolbar: 'undo redo | link unlink | bold italic | removeformat | fontsizeselect | formatselect | numlist bullist | blockquote outdent indent | code'
						});
			
				if (readOnly) {
					options = readonlyOptions;
					extraAtt[__$class] = ' tinymce_readonly';
			
				} else {
					if (field.insert_image_button) {
						if (field.insert_link_button) {
							options = optionsWithImagesAndLinks;
							extraAtt[__$class] = ' tinymce_with_images_and_links';
						} else {
							options = optionsWithImages;
							extraAtt[__$class] = ' tinymce_with_images';
						}
					} else {
						if (field.insert_link_button) {
							options = optionsWithLinks;
							extraAtt[__$class] = ' tinymce_with_links';
						} else {
							options = normalOptions;
							extraAtt[__$class] = ' tinymce';
						}
					}
				}
			
				extraAtt.style = 'visibility: hidden;';
			
				if (_.isObject(field.editor_options) ) {
					options = _.extend(options, field.editor_options);
				}
			
				options.setup = function (editor) {
					editor.on('change', 
						function(inst) {
							that.fieldChange(inst.id);
						});
				};
			
				cb.after(function() {
					var $field = $(that.get(id)),
						domTab = that.get('zenario_abtab'),
						tabDisplay = domTab.style.display;
				
					//Temporarily set the tab's display to be visible, even if an animation was hiding it.
					//This is a little hack to make sure that TinyMCE can get the correct width and height
					//of the textarea, even when it's not yet visible
					domTab.style.display = 'block';
				
					$field.tinymce(options);
				
					//Hide the tab again if it was hidden
					domTab.style.display = tabDisplay;
				});
				
				break;
			
			
			case __$button:
			case __$submit:
			case 'toggle':
				
				if (engToBoolean(field.use_button_tag)) {
					html += '<' + __$button;
					useButtonTag = true;
				} else {
					html += '<input';
					extraAtt.type = __$button;
				}
			
				if (!readOnly || engToBoolean(field.can_be_pressed_in_view_mode)) {
					extraAttAfter.onclick = "lib.clickButton(this, '" + id + "');";
					delete extraAtt.disabled;
				}
			
				if (fieldType == 'toggle') {
					extraAtt[__$class] += field.pressed? ' pressed' : ' not_pressed';
				}
				
				break;
			
			
			default:
			//Various text fields
				if (fieldType == 'textarea') {
					html += '<textarea';
		
				} else if (fieldType == 'date' || fieldType == 'datetime') {
					html += '<input';
					extraAtt.type = 'text';
					extraAtt[__$class] += ' zenario_datepicker';
			
					if (engToBoolean(field.change_month_and_year)) {
						extraAtt[__$class] += ' zenario_datepicker_change_month_and_year';
					}
			
					extraAtt.readonly = 'readonly';
			
					if (!readOnly) {
						extraAtt.onkeyup =
							(extraAtt.onkeyup || '') +
							"zenario.dateFieldKeyUp(this, event, '" + htmlspecialchars(id) + "');";
					}
		
				} else if (fieldType == 'url') {
					html += '<input';
					extraAtt.type = 'url';
					extraAtt.onfocus =
						(extraAtt.onfocus || '') +
						"if(!this.value) this.value = 'http://';";
					extraAtt.onblur =
						(extraAtt.onblur || '') +
						"if(this.value == 'http://') this.value = '';";
		
				} else {
					if (field.slider) {
						hasSlider = true;
						html += this.drawSlider(cb, id, field, readOnly, true);
					}
			
					html += '<input';
					extraAtt.type = fieldType;
					isNormalTextField = true;
				}
			
				this.addExtraAttsForTextFields(field, extraAtt);
		}
		
		//Checkboxes/Radiogroups only: If the form has already been submitted, overwrite the "checked" attribute depending on whether the checkbox/radiogroup was chosen
		if (fieldType == 'checkbox' || fieldType == 'radio') {
			if (engToBoolean(value)) {
				extraAtt.checked = 'checked';
			}
			value = undefined;
			
			//Most browsers still let you change checkboxes/radiogroups if they are flagged as "readonly"
			//So set the "disabled" flag as well
			if (readOnly) {
				extraAtt.disabled = true;
			}
			
	
			//If the indeterminate option is set in TUIX, set that property in the DOM after the html is drawn
			if (engToBoolean(field.indeterminate)) {
				cb.after(function() {
					var checkbox;
					if (checkbox = this.get(id)) {
						checkbox.indeterminate = true;
					}
				});
			}
		}
		
		if (hasSlider) {
			extraAtt.onchange =
				'$("#zenario_slider_for__' + jsEscape(id) + '").slider("value", $(this).val());';
			extraAtt.onkeyup =
				(extraAtt.onkeyup || '') +
				'$("#zenario_slider_for__' + jsEscape(id) + '").slider("value", $(this).val());';
		}
		
		//Add attributes
		var atts = field;
		if (lov === undefined) {
			atts.id = id;
		} else {
			atts = $.extend({}, atts, lovField);
			atts.id = id + '___' + lov;
			extraAtt[__$class] += ' control_for__' + id;
		}
		
		if (atts.type != 'radio' || !atts.name) {
			atts.name = id;
		}
		
		
		
		foreach (atts as var att) {
			if (allowedAtt[att]) {
				if (att == 'disabled' || att == 'readonly') {
					if (engToBoolean(extraAtt[att]) || engToBoolean(atts[att])) {
						html += ' ' + att + '="' + att + '"';
					}
					delete extraAtt[att];
				
				} else {
					html += ' ' + att + '="' + this.defineLibVarBeforeCode(att);
					
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
			if (att == 'disabled' || att == 'readonly') {
				if (engToBoolean(extraAtt[att])) {
					html += ' ' + att + '="' + att + '"';
				}
			} else {
				html += ' ' + att + '="' + this.defineLibVarBeforeCode(att) + htmlspecialchars(extraAtt[att]);
			
				if (extraAttAfter[att]) {
					html += ' ' + extraAttAfter[att];
					delete extraAttAfter[att];
				}
			
				html += '"';
			}
		}
		foreach (extraAttAfter as var att) {
			html += ' ' + att + '="' + this.defineLibVarBeforeCode(att) + htmlspecialchars(extraAttAfter[att]) + '"';
		}
		
		
		//Add the value (which happens slightly differently for textareas)
		if (fieldType == 'select') {
			html += '>';
		
		} else if (useButtonTag) {
			html += '>';
				if (field.icon_left) {
					html += '<i class="' + htmlspecialchars(field.icon_left) + '" aria-hidden="true"></i>';
				}
			
				html += htmlspecialchars(value, false, 'asis');
			
				if (field.icon_right) {
					html += '<i class="' + htmlspecialchars(field.icon_right) + '" aria-hidden="true"></i>';
				}
			html += '</button>';
		
		} else if (fieldType == 'textarea' || fieldType == 'editor') {
			html += '>' + htmlspecialchars(value, false, 'asis') + '</textarea>';
		
		} else if (fieldType == 'code_editor') {
			html += '>' + htmlspecialchars(value, false, 'asis') + '</div>';
		
		} else if (fieldType == 'date' || fieldType == 'datetime') {
			html += ' value="' + htmlspecialchars(zenario.formatDate(value, fieldType == 'datetime')) + '"/>';
			html += _$input(__$type, 'hidden', 'id', '_value_for__' + id, __$value, value);
			
			if (!readOnly) {
				html += _$input(__$type, __$button, __$class, 'zenario_remove_date', __$value, 'x', __$onclick, this.globalName + '.blankField("' + jsEscape(id) + '"); $(' + this.globalName + '.get("' + jsEscape(id) + '")).change();');
			}
		
		} else if (value !== undefined) {
			html += ' value="' + htmlspecialchars(value, false, 'asis') + '"/>';
		
		} else {
			html += '/>';
		}
		
		if (fieldType == 'select') {
			if (field.empty_value) {
				html += _$option(__$value, '', htmlspecialchars(field.empty_value));
			}
			
			if (field.values) {
				picked_items = {};
				picked_items[value] = true;
				html += this.hierarchicalSelect(picked_items, field, sortOrder, parentsValuesExist, existingParents);
			}
			html += '</select>';
			
			
			//Playing around with adding the select2 jQuery plugin to the CMS so we can fancy up the select lists
			if (field.select_options) {
				cb.after(function() {
					var $field = $(that.get(id)),
						chosenOptions = _.extend({}, field.select_options, {
							//https://harvesthq.github.io/chosen/options.html
							width: $field.css('width') || $field.width(),
							inherit_select_classes: true
						});
					
					if (field.empty_value
					 && chosenOptions.dont_show_empty_value
					 && !chosenOptions.placeholder_text_single) {
						chosenOptions.placeholder_text_single = field.empty_value;
					}
					
					$field.chosen(chosenOptions);
				});
			}
		
		} else if (isNormalTextField) {
			
			//If any other type of field has values, turn it into a jquery auto-complete
			if (field.values && !readOnly) {
				var i, v, source = [];
				
				foreach (sortOrder as i => v) {
					source.push({label: field.values[v], value: v});
				}
				
				cb.after(function() {
					var $field = $(that.get(id)),
						options = {
							source: source,
							minLength: field.autocomplete_min_length || 0,
							appendTo: $field.parent()
						};
					
					//If the return_key_presses_button option is set for a field, also
					//honor that choice if someone selects something
					if (field.return_key_presses_button) {
						options.select = function() {
							setTimeout(function() {
								$('#' + field.return_key_presses_button).click();
								//$field.autocomplete('widget').hide();
							}, 0);
						};
					}
					
					$field.autocomplete(options);
					
					//Show the autocomplete when the admin clicks or focuses into the field rather
					//than waiting for them to type something
					$field.focus(function() {
						if (!$field.autocomplete('widget').is(':visible')) {
							$field.autocomplete('search', '');
						}
					});
				});
			}
			
		}
	}
	
	if (lov === undefined) {
		
		if (fieldType == 'url' && !readOnly) {
			html +=
				'&nbsp; ' +
				_$input(__$type, __$button, __$class, __$submit, __$value, phrase.test, __$onclick, this.globalName + '.testURL("' + jsEscape(id) + '");');
		}
		
		//Checkboxes/radios/toggle buttons always have their labels directly after their fields 
		if (field.label && (fieldType == 'checkbox' || fieldType == 'radio' || fieldType == 'toggle')) {
			html += ' ' + _$html('label', __$class, field.label_class, 'for', id, 'id', 'label_for__' + id, htmlspecialchars(field.label));
		}
		//Other fields only have this if they specifically use the post_field_label property
		if (field.post_field_label !== undefined) {
			html += ' ' + _$html('label', 'for', id, 'id', 'label_for__' + id, htmlspecialchars(field.post_field_label));
		}
		
		if (field.post_field_html !== undefined) {
			html += zenario.unfun(field.post_field_html);
		}
		if (hasPostFieldTags) {
			html += postFieldTags.join('');
		}
		
		if (hasSlider) {
			html += this.drawSlider(cb, id, field, readOnly, false);
		}
	}
	
	return html;
};

methods.addExtraAttsForTextFields = function(field, extraAtt) {
};

//Make sure that the "lib" variable points towards this library
methods.defineLibVarBeforeCode = function(att) {
	
	//Attributes such as onclick/onchange/onkeyup/etc. always need the lib var defined,
	//but other attributes that aren't for JavaScript (e.g. id, class, value...) shouldn't have it.
	if (att === undefined || att.substr(0, 2) == 'on') {
		
		//Catch the case where someone makes a library without a global name and then calls this function.
		//If this library doesn't have a global name, come up with one now.
		if (!this.globalName) {
			for (var i = 1; window[this.globalName = 'zenarioLib' + i]; ++i) {};
			window[globalName] = this;
		}
		
		return 'var lib = ' + htmlspecialchars(this.globalName) + '; '
	} else {
		return '';
	}
};


//Test a URL typed into a URL field by opening it
methods.testURL = function(id) {
	
	//Get the value of the field
	var link = this.get(id).value,
		domTargetBlank = this.get(this.onThisRow('target_blank_', id)) || this.get(this.onThisRow('target_blank', id));
	
	if (!link) {
		return;
	
	//Small hack here - if there is a "target_blank" field, and it's set to show in a colorbox,
	//then show the test in a colorbox as well.
	} else if (domTargetBlank && domTargetBlank.value == 2) {
		$.colorbox({
			href: link,
			iframe: true,
			width: '95%',
			height: '95%',
			className: 'zenario_url_test_colorbox'
		});
	
	} else {
		window.open(zenario.addBasePath(link));
	}
};


//Ensure a numeric field stays numeric
methods.keepNumeric = function(el) {
	var val = el.value.replace(/[^\d\.]/g, '').replace(/\.(.*)\./g, '.$1')
	
	if (el.value != val) {
		el.value = val;
	};
};




methods.hierarchicalSelect = function(picked_items, field, sortOrder, parentsValuesExist, existingParents, parent) {
	
	var html = '',
		disabled,
		selected,
		hideWithoutChildren,
		val, i, v;
	
	foreach (sortOrder as i => v) {
		val = field.values[v];
		disabled = false;
		selected = false;
		
		if (_.isString(val)) {
			val = {label: val};
		
		} else
		if (engToBoolean(val.disabled)
		 || (val.disabled_if !== undefined
			//zenarioT.eval(c, lib, tuixObject, item, id, button, column, field, section, tab, tuix)
		  && zenarioT.eval(val.disabled_if, this, val, undefined, v, undefined, undefined, field))) {
			disabled = true;
		}
		
		if (picked_items[v]) {
			selected = true;
		}
		
		showWithoutChildren = !engToBoolean(val.hide_when_children_are_not_visible);
		
		if (parent === undefined
		 && parentsValuesExist
		 && existingParents[v]) {
			
			childrenHTML = this.hierarchicalSelect(picked_items, field, sortOrder, parentsValuesExist, existingParents, v);
			
			if (childrenHTML || showWithoutChildren) {
				html += _$html('optgroup', 'label', val, 'disabled', !childrenHTML, childrenHTML);
			}
		
		} else
		if (showWithoutChildren
		 && parent === val.parent) {
			
			html += _$option(__$value, v, 'selected', selected, 'disabled', disabled, htmlspecialchars(val, false, true));
		}
	}
	
	return html;
};




methods.setupPickedItems = function(id, tab, field, readOnly, multiple_select) {	
	
	var that = this,
		noRecurse = false,
		allow_typing_anything = false,
		pAndR,
		datas,
		searchParam,
		$tokenize,
		pathDetails,
		targetPathDetails,
		pick_items = field.pick_items || {},
		upload = field.upload || {};
	
	if (field.pick_items
	 && !engToBoolean(pick_items.hide_select_button)
	 && !engToBoolean(pick_items.disable_type_ahead_search)) {
		
		pathDetails = pick_items.path && zenarioO.convertNavPathToTagPathAndRefiners(pick_items.path);
		targetPathDetails = pick_items.target_path && zenarioO.convertNavPathToTagPathAndRefiners(pick_items.target_path);
		
		//If pick_items.path leads to the same place as pick_items.target_path,
		//prefer pick_items.path as this is more likely to have a refiner set on it
		if (pathDetails
		 && targetPathDetails
		 && pathDetails.path == targetPathDetails.path) {
			pAndR = pathDetails;
		} else {
			pAndR = targetPathDetails || pathDetails;
		}
		
		if (pAndR) {
			datas = URLBasePath + 'zenario/admin/organizer.ajax.php?_typeahead_search=1&path=' + encodeURIComponent(pAndR.path) + zenario.urlRequest(pAndR.request);
			searchParam = '_search';
		}
		
		allow_typing_anything = engToBoolean(field.pick_items.allow_typing_anything);
	}
	
	$tokenize = $(this.get(id)).tokenize({
		
		datas: datas,
		searchParam: searchParam,
		
		//Leave 200ms between repeated AJAX requests
		debounce: 200,
		
		sortable: engToBoolean(upload.reorder_items),
		placeholder: pick_items.nothing_selected_phrase || upload.nothing_selected_phrase || phrase.nothing_selected,
		
		//If multiple select is not enabled, every time a value is added it should replace what is already there.
		//(Note that I don't want to use {maxElements: 1} to stop people selecting more than one because I want
		// them to still be able to replace what's there by typing in the box, so instead I'll call the
		// addToPickedItems() function which will auto-remove the previously selected value.)
		onAddToken: function(value, text, e) {
			if (noRecurse) {
				return;
			}
			
			if (!multiple_select) {
				noRecurse = true;
				that.addToPickedItems(value, id, tab);
				noRecurse = false;
			}
			that.$getPickItemsInput(id).focus();
		},
		maxElements: multiple_select? 0 : 1,
		
		parseData: function(panel) {
			
			var valueId, item, label,
				data = [],
				field = that.tuix.tabs[tab].fields[id];
			
			if (panel.items) {
				foreach (panel.items as valueId => item) {
					
					label = zenarioA.formatOrganizerItemName(panel, valueId)
					
					field.values = field.values || {};
					field.values[valueId] = {
						list_image: item.list_image,
						css_class: item.css_class || (panel.item && panel.item.css_class),
						label: label
					};
					
					data.push({value: valueId, text: label, html: that.drawPickedItem(valueId, id, field, readOnly)});
				}
			}
			
			return data;
		},
		
		formatTokenHTML: function(valueId, text) {
			
			var field = that.tuix.tabs[tab].fields[id];
			
			if (field.values
			 && field.values[valueId]) {
				return that.drawPickedItem(valueId, id, field, readOnly);
			
			} else if (allow_typing_anything) {
				field.values = field.values || {};
				field.values[valueId] = text;
				return that.drawPickedItem(valueId, id, field, readOnly);
			
			} else {
				return false;
			}
		},
		
		onRemoveToken: function(value, e) {
			that.fieldChange(id);
		}
	});
	
	//Don't allow any changes if the field is in read-only mode
	if (readOnly) {
		$tokenize.disable();
	
	//Don't allow anything to be removed if the hide_remove_button property is set
	//} else if (engToBoolean(pick_items.hide_remove_button)) {
	//	$tokenize.container.find('.Close').hide();
	
	//If there is no AJAX URL then no type-ahead is possible, so we need to disable it.
	//But we still need the field to look editable, and the "remove" button should still work!
	} else if (!datas && !allow_typing_anything) {
		$tokenize.disableTypeAhead();
	
	} else {
		$tokenize.container.addClass('zenario_picker_with_typeahead');
		
		//Catch the case where the user starts typing something then blurs the field
		if (allow_typing_anything) {
			this.$getPickItemsInput(id).blur(function() {
				var value, obj;
				if (value = that.$getPickItemsInput(id).val()) {
					obj = {};
					obj[value] = value;
					that.addToPickedItems(obj, id, tab);
				}
			});
		}
	}
	
	return;
};


methods.chooseFromDropbox = function(id) {
				
	var that = this,
		field,
		options,
		e, extension, extensions, split;

	if (!(field = this.field(id))
	 || !(field.upload)) {
		return false;
	}
	
	if (extensions = field.upload.extensions || field.upload.accept) {
		if (_.isString(extensions)) {
			extensions = extensions.split(',');
		} else {
			extensions = _.toArray(extensions);
		}
	}
	
	//Dropbox has a set format that it uses.
	//Attempt to automatically convert a few common things to the correct format
	foreach (extensions as e => extension) {
		
		//Look for expressions such as "image/*", and convert them into the dropbox equivalents
		split = extension.split('/');
		if (split[1] !== undefined) {
			if (split[0] == 'images') {
				extensions[e] = 'image';
			} else {
				extensions[e] = split[0];
			}
		
		//Look for file extensions without a "." in front of them, and automatically add the "."
		} else
		if (extension != 'text'
		 && extension != 'documents'
		 && extension != 'images'
		 && extension != 'video'
		 && extension != 'audio'
		 && extension.substr(0, 1) != '.') {
			extensions[e] = '.' + extension;
		}
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
		extensions: extensions,

		// Required. Called when a user selects an item in the Chooser.
		success: function(files) {
			
			zenarioA.showAJAXLoader();
			
			var f,
				file,
				cb = new zenario.callback;
			
			foreach (files as f => file) {
				cb.add(zenario.ajax(this.ajaxURL() + '&fetchFromDropbox=1', file, true));
			}
			
			cb.after(function() {
				var i,
					file,
					field,
					values = '';
		
				if (!that.tuix.tabs[that.tuix.tab]
				 || !(field = that.tuix.tabs[that.tuix.tab].fields[id])
				 || !(field.upload)) {
					return false;
				}
				
				foreach (arguments as i) {
					file = arguments[i];
					
					if (file && file.id) {
						values += ',' + file.id;
					}
					
					that.setFileDetails(field, file);
				}
				
				if (values !== '') {
					that.addToPickedItems(values, id);
				}
				
				zenarioA.hideAJAXLoader();
			});
		}
	};
	
	zenarioA.showAJAXLoader();
	Dropbox.choose(options);
};

methods.setFileDetails = function(field, file) {
	
	if (!field.values) {
		field.values = {};
	}
	if (!field.values[file.id]) {
		field.values[file.id] = {
			file: file,
			label: this.formatLabelFromFile(file)
		};
	}
};

methods.formatLabelFromFile = function(file) {
	var label;					
	
	//Format uploaded files - these are encoded, and in the form "checksum/filename/width/height"
	//We want to try and display the filename
	if (file.filename !== undefined) {
		label = file.filename;

		if (engToBoolean(file.width) && engToBoolean(file.height)) {
			label += ' [' + file.width + ' × ' + file.height + 'px]';
		}
	
	} else if ((fileDetails = zenario.decodeItemIdForOrganizer(file.id))
	 && (fileDetails = fileDetails.split('/'))
	 && (fileDetails[1])) {
		label = fileDetails[1];

		if (engToBoolean(fileDetails[2]) && engToBoolean(fileDetails[3])) {
			label += ' [' + fileDetails[2] + ' × ' + fileDetails[3] + 'px]';
		}
	
	} else {
		label = file.id;
	}
	
	return label;
};

methods.upload = function(id, setUpDragDrop) {
	
	var that = this,
		field, object;
	
	if (!(field = this.field(id))
	 || !(field.upload)) {
		return false;
	}
	
	object = {
		class_name: field.class_name,
		upload: field.upload
	};
	
	this.uploadCallback = function(responses) {
		if (responses) {
			var i,
				file,
				fileDetails,
				field,
				values = '';
		
			if (!that.tuix.tabs[that.tuix.tab]
			 || !(field = that.tuix.tabs[that.tuix.tab].fields[id])
			 || !(field.upload)) {
				return false;
			}
			
			foreach (responses as i) {
				file = responses[i];
				
				if (file && file.id) {
					values += ',' + file.id;
					that.setFileDetails(field, file);
				}
			}
			
			if (values !== '') {
				that.addToPickedItems(values, id);
			}
		}
	};
	
	if (setUpDragDrop) {
		zenarioT.setHTML5UploadFromDragDrop(
			that.ajaxURL(),
			{
				fileUpload: 1
			},
			false,
			this.uploadCallback,
			that.get('zenario_fbAdminInner')
		);
	} else {
		zenarioT.action(this, object, undefined, undefined, undefined, {fileUpload: 1}, undefined, this.ajaxURL());
	}
};

methods.ajaxURL = function() {
	return URLBasePath + 'zenario/ajax.php?method_call=handleAdminBoxAJAX&path=' + encodeURIComponent(this.path);
};

methods.uploadComplete = function(responses) {
	if (this.uploadCallback !== undefined) {
		this.uploadCallback(responses);
	}
	delete this.uploadCallback;
};

methods.drawSlider = function(cb, id, field, readOnly, before) {
	var that = this,
		options = _.clone(field.slider),
		html = '';
	
	if (engToBoolean(options.before_field)? before : !before) {
		if (readOnly) {
			html +=
				'<div class="ui-disabled">';
		}
		
		html +=
			_$div('id', 'zenario_slider_for__' + id, __$class, options[__$class], __$style, options.style);
		
		if (readOnly) {
			html +=
				'</div>';
		}
		
		//Set up the slider after the html is drawn
		cb.after(function() {
			var domSlider;
			if (domSlider = that.get('zenario_slider_for__' + id)) {
				
				if (options.min !== undefined) options.min = zenario.num(options.min);
				if (options.max !== undefined) options.max = zenario.num(options.max);
				if (options.step !== undefined) options.step = zenario.num(options.step);
				
				options.disabled = !that.editModeOn();
				options.value = $(that.get(id)).val();
				options.slide =
					function(event, ui) {
						$(that.get(id)).val(ui.value);
					};
				
				options.change = function(event, ui) {
					that.fieldChange(id);
				};
				
				$(domSlider).slider(options);
			}
		});
	}
	
	return html;
};

methods.pickedItemsArray = function(field, value) {
	
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
		label,
		path,
		item,
		file,
		k, i;
	
	field._display_value = false;
	
	foreach (items as k) {
		if (i = items[k]) {
			//Format uploaded files - these are encoded, and in the form "checksum/filename/width/height"
			//We want to try and display the filename
			if (field.upload
			 && (file = zenario.decodeItemIdForOrganizer(i))
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
			
			//If an id was set but no label, and this is a pick_items field,
			//then attempt to look up the label from Organizer
			} else
			if (((path = field.pick_items && field.pick_items.target_path)
			  || (path = field.pick_items && field.pick_items.path))
			 && ((path.indexOf('//') == -1)
			  || (zenarioO.map && (path = zenarioO.convertNavPathToTagPath(path))))
			 && ((panel = zenarioA.getItemFromOrganizer(path, i)))
			) {
				if (!field.values) {
					field.values = {};
				}
				
				label = zenarioA.formatOrganizerItemName(panel, i);
				item = panel.items && panel.items[i] || {};
				
				picked_items[i] = label;
				field.values[i] = {
					list_image: item.list_image,
					css_class: item.css_class || (panel.item && panel.item.css_class),
					label: label
				};
			
			//If an id was set but no label, and this is an upload field,
			//then attempt to look up the filename
			} else
			if (field.upload
			 && (i == 1 * i)
			 && (file = zenarioA.lookupFileDetails(i))) {
				
				this.setFileDetails(field, file);
				picked_items[i] = field.values[i];
			
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

methods.pickedItemsValue = function(picked_items) {
	var i, value = '';
	
	if (picked_items) {
		foreach (picked_items as i) {
			value += (value === ''? '' : ',') + i;
		}
	}
	
	return value;
};

//Draw hierarchical checkboxes or radiogroups
methods.hierarchicalBoxes = function(cb, tab, id, value, field, thisField, picked_items, tempReadOnly, sortOrder, existingParents, parent, parents, level) {
	
	var cols = 1,
		col = 0,
		html = '',
		m, v,
		lovField,
		isSelected,
		hadSelected = false,
		splitValuesAfterSelected = false;
	
	
	//Set a depth limit to try and prevent infinite loops
	if (!level) {
		level = 0;
		cols = 1*field.cols || 1;
	
	} else if (level > 10) {
		return '';
	}
	
	//Create a list of ids that have children, so we don't waste time looping through looking for children for checkboxes which have none
	//if (existingParents === undefined) {
	//	existingParents = {};
	//	
	//	foreach (field.values as var v) {
	//		if (typeof field.values[v] == 'object' && field.values[v].parent) {
	//			existingParents[field.values[v].parent] = true;
	//		}
	//	}
	//}
	
	//Set up a list of parents that the current level of items will have
	if (!parents) {
		parents = {};
	}
	if (parent) {
		parents[parent] = true;
	}
	
	
	if (level) {
		html += _$div(__$class, 'zenario_hierarchical_box_children', 'id', 'children_for___' + id + '___' + parent, '>');
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
		
		if (field.tag_colors) {
			m.tag_color = field.tag_colors[v] || 'blue';
		}
		
		thisParent = lovField.parent;
		if (thisParent === '0') {
			thisParent = false;
		}
		
		if ((!parent && !thisParent) || (parent == thisParent)) {
			if (m.newRow = (++col > cols)) {
				col = 1;
			}
			m.col = col;
			m.cols = cols;
			
			//Work out whether this should be checked
			//If the field has no value set, it should not be checked unless the radiogroup had no value either
				//(i.e. the "not set" option)
			if (!value) {
				isSelected = !v;
			
			} else if (value === true || value === 1 || value === '1') {
				isSelected = v === true || v === 1 || v === '1';
			
			//Otherwise, check or don't check the checkbox/radiogroup depending on the current value of the field
			//Attempt to get the values from picked_items if we can, otherwise use the value.
			} else if (!picked_items) {
				isSelected = v == value;
			} else if (picked_items[v] !== undefined) {
				isSelected = true;
			} else {
				isSelected = false;
			}
			
			m.checked = isSelected;
			
			//Include logic for checking parents/unchecking children on selection/deselection of checkboxes
			if (field.type == 'checkboxes' && engToBoolean(field.checking_child_checks_parents)) {
				var onchange = '';
			
				//Include logic for checking parents/unchecking children on selection/deselection of checkboxes
				if (parent) {
					onchange += "if (this.checked) { for (var cb in " + JSON.stringify(parents) + ") { " + this.globalName + ".get('" + htmlspecialchars(id) + "___' + cb).checked = true; } } "
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
		
			m.lovId = id + '___' + v;
			m.lovField = lovField;
		
			if (field.indeterminates
			 && engToBoolean(field.indeterminates[v])) {
				(function(id) {
					cb.after(function() {
						var checkbox;
						if (checkbox = this.get(id)) {
							checkbox.indeterminate = true;
						}
					});
				})(m.lovId);
			}
		
			m.lovHTML = this.drawField(cb, tab, id, thisField, undefined, undefined, undefined, undefined, v, isSelected, false, tempReadOnly, sortOrder, existingParents, lovField);
		
			m.childrenHTML = '';
			if (existingParents[v]) {
				m.childrenHTML = this.hierarchicalBoxes(cb, tab, id, value, field, thisField, picked_items, tempReadOnly, sortOrder, existingParents, v, _.extend({}, parents), level + 1);
				col = 0;
			}
			
			
			if (splitValuesAfterSelected) {
				if (!this.splitValues) {
					this.splitValues = {};
				}
				if (!this.splitValues[id]) {
					this.splitValues[id] = '';
				}
				this.splitValues[id] += this.microTemplate(this.mtPrefix + '_radio_or_checkbox', m);; 
			
			} else {
				html += this.microTemplate(this.mtPrefix + '_radio_or_checkbox', m);; 
			}
			
			if (isSelected
			 && lovField.split_values_if_selected
			 && !level
			 && field.type === 'radios') {
				splitValuesAfterSelected = true;
			}
			//hadSelected = hadSelected || isSelected;
		}
	}
	
	if (level) {
		html += '</div>';
	}
	
	return html;
};

//Return the HTML for a picked item
methods.drawPickedItem = function(item, id, field, readOnly) {
	
	if (field === undefined) {
		field = this.field(id);
	}
	//if (value === undefined) {
	//	value = this.value(id, this.tuix.tab);
	//}
	
	var panel,
		//m, i,
		valueObject = {},
		label = field.values && field.values[item];
	
	
	if (_.isObject(label)) {
		valueObject = label;
		label = label.label;
	} else {
		if (!label) {
			label = item;
		}
		valueObject.label = label;
	}
	
	var numeric = item == 1 * item,
		extension,
		widthAndHeight,
		file,
		path,
		src,
		mi = {
			id: id,
			item: item,
			label: label,
			//first: i == 0,
			//last: i == sortedPickedItems.length - 1,
			readOnly: readOnly,
			//tempReadOnly: tempReadOnly,
			css_class: valueObject.css_class,
			list_image: valueObject.list_image
		};
	
	if (field.tag_colors) {
		mi.tag_color = field.tag_colors[item] || 'blue';
	}
	
	//Attempt to work out the path to the item in Organizer, and include an "info" button there
	//If this is a file upload, the info button shouldn't be shown for newly uploaded files;
	//only files with an id should show the info button.
	if (field.pick_items
	 && !engToBoolean(field.pick_items.hide_info_button)
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
			if ((file = field.values && field.values[item] && field.values[item].file)
			 && (file.checksum)) {
				//If this is an existing file (with a numeric id), link by id
				src = URLBasePath + 'zenario/file.php?c=' + encodeURIComponent(file.checksum);
			
				if (file.usage) {
					src += '&usage=' + encodeURIComponent(file.usage);
				}
			} else {
				//Otherwise if this looks like a numeric id, try to link by id
				src = URLBasePath + 'zenario/file.php?id=' + item;
			}
		} else {
			//Otherwise try to display it from the cache/uploads/ directory
			src = URLBasePath + 'zenario/file.php?getUploadedFileInCacheDir=' + encodeURIComponent(item);
		}
	
		//Check if this is an image that has been chosen
		if (extension.match(/gif|jpg|jpeg|png|svg/)) {
			//For images, display a thumbnail that opens a colorbox when clicked
			mi.thumbnail = {
				onclick: this.globalName + ".showPickedItemInPopout('" + src + "&popout=1&dummy_filename=" + encodeURIComponent("image." + extension) + "', '" + label + "');",
				src: src + "&og=1"
			};
			
			//Attempt to get the width and height from the label, and work out the correct
			//width and height for the thumbnail.
			//(The max is 180 by 120; this is the size of Organizer thumbnails and
			// is also set in zenario/file.php)
			widthAndHeight = ('' + label).match(/.*\[\s*(\d+)p?x?\s*[×x]\s*(\d+)p?x?\s*\]$/);
			if (widthAndHeight && widthAndHeight[1] && widthAndHeight[2]) {
				zenarioT.resizeImage(widthAndHeight[1], widthAndHeight[2], 180, 120, mi.thumbnail);
			}
			
			
		} else {
			//Otherwise display a downlaod link
			mi.adminDownload = src + "&adminDownload=1";
		}
	}
	
	return this.microTemplate(this.mtPrefix + '_picked_item', mi);
};


methods.showPickedItemInPopout = function(href, title) {
	zenarioT.action(this, {popout: {href: href, title: title, css_class: 'zenario_show_colobox_above_fab'}});
};

methods.pickItems = function(id) {
	
	var field,
		pick_items,
		selectedItem,
		path;
	
	if ((field = this.field(id))
	 && (pick_items = field.pick_items)
	 && (pick_items.path || pick_items.target_path)) {
	
		//Attempt to pre-select the currently selected item
		path = pick_items.path;
		if (!engToBoolean(pick_items.multiple_select)
		 && (path == pick_items.target_path || pick_items.min_path == pick_items.target_path)
		 && (selectedItem = this.readField(id))
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
	
		this.SKTarget = id;
		zenarioA.organizerSelect(this.globalName, 'setPickedItems', pick_items.multiple_select,
					path, pick_items.target_path, pick_items.min_path, pick_items.max_path, pick_items.disallow_refiners_looping_on_min_path,
					undefined, pick_items.one_to_one_choose_phrase, pick_items.one_to_many_choose_phrase,
					undefined,
					true,
					undefined, undefined, undefined,
					undefined,
					undefined, undefined,
					undefined, undefined,
					pick_items);
	}
};

methods.setPickedItems = function(path, key, row, panel) {
	var id = this.SKTarget,
		i, eni, item,
		values = {};
	
	foreach (key._items as eni) {
		i = zenario.decodeItemIdForOrganizer(eni);
		item = panel.items && panel.items[i] || {};
		
		values[i] = {
			list_image: item.list_image,
			css_class: item.css_class || (panel.item && panel.item.css_class),
			label: zenarioA.formatOrganizerItemName(panel, i)
		};
	}
	
	this.addToPickedItems(values, id);
};

methods.removeFromPickedItems = function(values, id, tab) {
	this.addToPickedItems(values, id, tab, true);
};

methods.addToPickedItems = function(values, id, tab, remove) {
	
	var field = this.field(id, tab),
		current_value = this.readField(id),	//(field.current_value === undefined? field.value : field.current_value),
		multiple_select = (field.pick_items && engToBoolean(field.pick_items.multiple_select)) || (field.upload && engToBoolean(field.upload.multi)),
		i, value, display, arrayOfValues, picked_items;
	
	values = zenarioT.csvToObject(values);
	
	if (multiple_select && current_value) {
		picked_items = this.pickedItemsArray(field, current_value);
	} else {
		picked_items = {};
	}
	
	foreach (values as value => display) {
		
		if (!field.values) {
			field.values = {};
		}
		if (remove) {
			delete field.values[value];
		} else {
			if (!field.values[value]) {
				field.values[value] = display;
			}
		
			picked_items[value] = true;
		
			if (!multiple_select) {
				break;
			}
		}
	}
	
	this.redrawPickedItems(id, field, picked_items);
};

methods.redrawPickedItems = function(id, field, picked_items) {
	
	var i, item, label,
		value = this.pickedItemsValue(picked_items),
		currently_picked_items = {},
		$tokenize = $(this.get(id)).tokenize(),
		items = $tokenize.toArray();
	
	picked_items = zenarioT.csvToObject(picked_items);
	
	foreach (items as i => item) {
		if (picked_items[item]) {
			currently_picked_items[item] = true;
		} else {
			$tokenize.tokenRemove(item);
		}
	}
	
	foreach (picked_items as item) {
		if (!currently_picked_items[item]) {
			
			label = field.values && field.values[item];
	
			if (_.isObject(label)) {
				label = label.label;
			} else {
				if (!label) {
					label = item;
				}
			}
			
			$tokenize.tokenAdd(item, label);
		}
	}
	
	field.current_value = value;
	this.fieldChange(id);
};

methods.changes = function(tab) {
	if (!this.tuix || !this.tuix.tab) {
		return false;
	
	} else if (tab == undefined) {
		foreach (this.tuix.tabs as tab) {
			if (this.changed[tab]) {
				return true;
			}
		}
		
		return false;
	
	} else {
		return this.changed[tab];
	}
};

methods.markAsChanged = function(tab) {
	if (!this.tuix) {
		return;
	}
	
	if (tab === undefined) {
		tab = this.tuix.tab;
	}
	
	if (!tab) {
		return;
	}
	
	if (!this.changed[tab]) {
		this.changed[tab] = true;
		
		if (this.tuix.tab == tab) {
			$('#zenario_abtab').addClass('zenario_abtab_changed');
		}
	}
	
};

methods.fieldChange = function(id, lov) {
	
	var that = this,
		field = this.field(id);
	
	if (!field) {
		return;
	}
	
	if (field.indeterminate) {
		field.indeterminate = false;
	}
	
	if (lov !== undefined
	 && field.indeterminates
	 && field.indeterminates[lov]) {
		field.indeterminates[lov] = false;
	}
	
	
	this.markAsChanged();
	
	if (engToBoolean(field.multiple_edit)) {
		this.meMarkChanged(id);
	}
	
	//If a field has changed, check whether we need to redraw, format or validate the FAB.
	//However, if this was done immediately it would mess up people's tab-switching, as the fields
	//would be destroyed mid-tab-select.
	//I'm using setTimeout() as little hack to allow the tab switching to finish first.
	setTimeout(function() {
		that.validateFormatOrRedrawForField(field);
	}, 1);
};

methods.validateFormatOrRedrawForField = function(field) {
	
	field = this.field(field);
	
	var validate = engToBoolean(field.validate_onchange),
		format = engToBoolean(field.format_onchange),
		redraw = engToBoolean(field.redraw_onchange);
	validate = validate;

		
	if (validate) {
		if (this.ffoving < 3) {
			this.ffoving = 3;
			this.validate();
		}
		return true;
	
	} else if (format) {
		if (this.ffoving < 2) {
			this.ffoving = 2;
			this.format();
		}
		return true;
	
	} else {
		if (redraw) {
			if (this.ffoving < 1) {
				this.ffoving = 1;
				this.readTab();
				this.redrawTab();
			}
		}
		
		return redraw;
	}
};

methods.meMarkChanged = function(id, current_value, value) {
	
	if (current_value !== undefined) {
		if (current_value == value) {
			return;
		}
	}
	
	this.field(id).multiple_edit._changed = true;
	this.meSetCheckbox(id, true);
};

methods.meSetCheckbox = function(id, changed) {
	if (this.get('multiple_edit__' + id).type == 'checkbox') {
		this.get('multiple_edit__' + id).checked = changed;
	} else {
		this.get('multiple_edit__' + id).value = changed? 1 : '';
	}
};

//The admin changes a multiple-edit checkbox
methods.meChange = function(changed, id, confirm) {
	
	var field = this.field(id);
	
	//Update its state in the schema
	if (changed) {
		field.multiple_edit._changed = true;
	} else {
		
		//Require a confirm prompt if this will lose any changes
		if (!confirm
		 && engToBoolean(field.multiple_edit.warn_when_abandoning_changes)
		 && field.multiple_edit.original_value !== undefined
		 && this.readField(id) != field.multiple_edit.original_value) {
		 	
			var buttonsHTML =
				_$input(__$type, __$button, __$class, 'submit_selected', __$value, phrase.abandonChanges, __$onclick, this.globalName + '.meChange(false, "' + jsEscape(id) + '", true);') + 
				_$input(__$type, __$button, __$class, __$submit, __$value, phrase.cancel);
			
			zenarioA.floatingBox(phrase.abandonChangesConfirm, buttonsHTML, 'warning');
			this.meSetCheckbox(id, true);
			return;
		}
		
		field.multiple_edit._changed = false;
		
		//If it is now off, revert the field's value back to the default.
		delete field.current_value;
		
		var value = field.value;
		if (field.multiple_edit.original_value !== undefined) {
			value =
			field.current_value =
			field.multiple_edit.original_value;
		}
		
		if (this.get(id) && !field.pick_items) {
			if (this.get(id).type == 'checkbox') {
				this.get(id).checked = value? true : false;
			} else {
				$(this.get(id)).val(value || '');
			}
		} else {
			//Some non-standard fields - i.e. fields that couldn't be changed using $().val() - will need a complete redraw of the tab to achieve
			var cb = new zenario.callback,
				html = this.drawFields(cb);
			this.insertHTML(html, cb);
		}
	}
	
	if (engToBoolean(field.multiple_edit.disable_when_unchanged)) {
		if (this.get(id)) {
			this.get(id).disabled = !changed;
		}
		$('.control_for__' + id).each(function(i, e) {
			e.disabled = !changed;
			
			if (e.type == __$button) {
				if (changed) {
					$(e).removeClass('submit_disabled');
				} else {
					$(e).addClass('submit_disabled');
				}
			}
		});
	}
	
	this.meSetCheckbox(id, changed);
	
	this.validateFormatOrRedrawForField(id);
};


methods.currentValue = function(f, tab, readOnly) {
	
	if (!readOnly && tab == this.tuix.tab) {
		return this.readField(f);
	} else {
		return this.value(f, tab, readOnly);
	}
};

//Get the value of a field
methods.value = function(f, tab, readOnly, getButtonLabelsAsValues) {
	if (!tab) {
		tab = this.tuix.tab;
	}
	
	var value = '',
		first = true,
		field = this.field(f, tab);
	
	if (!field) {
		return '';
	}
	
	if (readOnly === undefined) {
		readOnly = !this.editModeOn(tab) || field.read_only || field.readonly;
	}
	
	if (!field
	 || field.snippet
	 || field.type == 'grouping') {
	
	} else if (!getButtonLabelsAsValues && this.isButton(field)) {
		value = !!field.pressed;
	
	} else if (!readOnly && field.current_value !== undefined) {
		value = field.current_value;
	
	} else if (field.value !== undefined) {
		value = field.value;
	
	} else if (field.multiple_edit && field.multiple_edit.original_value !== undefined) {
		value = field.multiple_edit.original_value;
	}
	
	//Sometimes numbers might be returned as strings.
	//This is usually fine, except for '0', which evaluates to true when we want it to evaluate to false,
	//so correct that here!
	if (value === '0') {
		return 0;
	} else {
		return value;
	}
};

methods.keyIn = function(k) {
	return _.contains(arguments, this.tuix.key[k], 1);
};

methods.keyIs = function(k, v) {
	return this.tuix.key[k] == v;
};

methods.key = function(k) {
	return this.tuix.key[k];
};

methods.modeIn = function() {
	return _.contains(arguments, this.value('mode'));
};

methods.modeIs = function(m) {
	return this.value('mode') == m;
};

methods.valueIn = function(f) {
	return _.contains(arguments, this.value(f), 1);
};

methods.valueIs = function(f, v) {
	return this.value(f) == v;
};

methods.valueIsNot = function(f, v) {
	return this.value(f) != v;
};

methods.isButton = function(field) {
	return field && (field.type == __$submit || field.type == 'toggle' || field.type == __$button);
};

methods.isFormField = function(field) {
	return !(!field
			|| field.snippet
			|| field.type == 'grouping'
			|| field.type == __$submit
			|| field.type == 'toggle'
			|| field.type == __$button);
};


methods.readField = function(f) {
	var value = undefined,
		tab = this.tuix.tab,
		field = this.field(f, tab),
		el;
	
	//Non-field types
	if (!this.isFormField(field)) {
		return undefined;
	}
	
	var readOnly = !this.editModeOn() || engToBoolean(field.read_only) || engToBoolean(field.readonly),
		hidden = field._was_hidden_before;
	
	//Update logic for multiple edit fields
	if (field.multiple_edit) {
		if (readOnly) {
			delete field.multiple_edit._changed;
		}
	}
	
	//Multiple Checkboxes/Radiogroups have values stored in several different places
	if (field.type == 'checkboxes' || field.type == 'radios') {
		if (!readOnly && !hidden && field.values) {
			var v, current_value = '', first = true;
			
			foreach (field.values as var v) {
				if (this.get(f + '___' + v) && this.get(f + '___' + v).checked) {
					current_value += (first? '' : ',') + v;
					first = false;
				}
			}
			field.current_value = current_value;
		}
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
	if (this.get('_value_for__' + f)) {
		field.current_value = value = this.get('_value_for__' + f).value;
	
	//Editors
	} else if ((field.type == 'editor' || field.type == 'code_editor') && !readOnly) {
		var content = undefined;
		
		if (field.type == 'editor') {
			if (window.tinyMCE) {
				if (tinyMCE.get(f)) {
					content = zenario.tinyMCEGetContent(tinyMCE.get(f));
				}
			}
		} else if (field.type == 'code_editor') {
			var codeEditor;
			if (codeEditor = ace.edit(f)) {
				content = codeEditor.getValue();
				
				this.editingPositions[tab + '/' + f] = {
					top: codeEditor.session.getScrollTop(),
					left: codeEditor.session.getScrollLeft(),
					range: codeEditor.getSelectionRange()
				};
			}
		}
		
		if (content !== undefined && content !== false) {
			value = field.current_value = content;
		
		//If due to some bug we couldn't get the content from the editor,
		//return the stored value and don't do any further manipulations to the data model.
		} else if (field.current_value === undefined) {
			return field.value;
		} else {
			return field.current_value;
		}
	
	//Normal fields
	} else {
		if (!readOnly && (el = this.get(f))) {
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
			
			if (_.isArray(value)) {
				value = value.join(',');
			}
			
			field.current_value = value;
		
		} else {
			delete field.current_value;
			value = field.value;
		}
	}
	
	return value;
};

methods.blankField = function(f) {
	this.setFieldValue(f, '');
};

methods.setFieldValue = function(f, val) {
	var tab = this.tuix.tab
		field = this.field(f, tab);
	
	if (this.get(f)) {
		$(this.get(f)).val(val);
	}
	
	if (this.get('_value_for__' + f)) {
		this.get('_value_for__' + f).value = val;
	}
	
	field.current_value = val;
};


//this.lastScrollTop = undefined;

methods.readTab = function() {
	var value,
		values = {},
		fields = this.fields(),
		f;
	
	this.lastScrollTop = $('#zenario_fbAdminInner').scrollTop();
	
	foreach (fields as f) {
		if ((value = this.readField(f)) !== undefined) {
			values[f] = value;
		}
	}
	
	if (document
	 && document.activeElement) {
		this.lastFieldInFocus = document.activeElement.id;
	}
	
	return values;
};

methods.wipeTab = function() {
	var tab = this.tuix.tab,
		value,
		values = {},
		fields = this.fields(),
		f, field;
	
	foreach (fields as f => field) {
		delete field.current_value;
		
		if (field.multiple_edit) {
			delete field.multiple_edit._changed;
		}
	}
};

methods.redrawTab = function() {
	this.tuix.shake = false;
	this.draw2();
};









//Get a URL needed for an AJAX request
methods.getURL = function(action) {
	//Outdate any validation attempts
	++this.onKeyUpNum;
	
	return this.returnAJAXURL(action);
};

methods.returnAJAXURL = function(action) {
	return '...';
};


//Some shortcuts
methods.editModeOn = function(tab) {
	return true;
};

methods.editModeAlwaysOn = function(tab) {
	return true
};

methods.editCancelEnabled = function(tab) {
	return false;
};

methods.revertEnabled = function(tab) {
	return false
};

methods.savedAndContinued = function(tab) {
	return false;
};

methods.editModeOnBox = function() {
	return true;
};



//  Sorting Functions  //

methods.sortTabs = function() {
	//Build an array to sort, containing:
		//0: The item's actual index
		//1: The value to sort by
	this.sortedTabs = [];
	if (this.tuix.tabs) {
		foreach (this.tuix.tabs as var i => var thisTab) {
			if (thisTab) {
				this.sortedTabs.push([i, thisTab.ord]);
			}
		}
	}
	
	//Sort this array
	this.sortedTabs.sort(zenarioT.sortArray);
	
	this.groupings = {};
	this.sortedFields = {};
	
	foreach (this.sortedTabs as var i) {
		var tab = this.sortedTabs[i] = this.sortedTabs[i][0];
		this.sortFields(tab);
		//this.sortedTabOrders[tab] = i;
	}
};

methods.sortFields = function(tab) {
	
	var i, field, fields, groupingOrd,
		groupingOrds = {};
	
	//Build an array to sort, containing:
		//0: The item's actual index
		//1: The value to sort by
	this.groupings[tab] = {};
	this.sortedFields[tab] = [];
	if (fields = this.fields(tab)) {
		
		//Look for groupings among the fields.
		//Groupings work like placeholders; the fields in the grouping should all have
		//the position of the placeholder.
		foreach (fields as i => field) {
			if (field.type
			 && field.type == 'grouping') {
				groupingOrds[field.name || i] = 1*field.ord;
				this.groupings[tab][field.name || i] = i;
			}
		}
		
		foreach (fields as i => field) {
			if (field) {
				
				if (field.type
				 && field.type == 'grouping') {
					//Groupings work like placeholders; they help sort the fields
					//but shouldn't count as sorted fields
				} else {
					if (field.grouping) {
						groupingOrd = groupingOrds[field.grouping];
					} else {
						groupingOrd = undefined;
					}
				
					this.sortedFields[tab].push([i, field.ord, groupingOrd]);
				}
			}
		}
	
		//Sort this array
		this.sortedFields[tab].sort(zenarioT.sortArrayWithGrouping);
	
		//Remove fields that were just there to help sort
		foreach (this.sortedFields[tab] as i) {
			this.sortedFields[tab][i] = this.sortedFields[tab][i][0];
		}
	}
};







methods.setData = function(data) {
	this.tuix = data;
};
 

methods.setDataDiff = function(data) {
	this.syncAdminBoxFromServerToClient(data, this.tuix);
};

//Sync updates from the server to the array stored on the client
methods.syncAdminBoxFromServerToClient = function($serverTags, $clientTags) {
	
	var $key, $val;
	
	foreach ($serverTags as $key => $val) {
		if ($val === null || $val['[[__unset__]]']) {
			delete $clientTags[$key];
		
		} else
		if ($clientTags[$key] === undefined || $clientTags[$key] === null) {
			$clientTags[$key] = $val;
		
		} else
		if ($val['[[__replace__]]']) {
			delete $val['[[__replace__]]'];
			$clientTags[$key] = $val;
		
		} else
		if ('object' != typeof $clientTags[$key]) {
			$clientTags[$key] = $val;
		
		} else
		if (('object' == typeof $clientTags[$key]) && ('object' != typeof $val)) {
			$clientTags[$key] = $val;
		
		} else {
			this.syncAdminBoxFromServerToClient($val, $clientTags[$key]);
		}
	}
};



methods.sendStateToServer = function() {
	return JSON.stringify(this.tuix);
};

methods.sendStateToServerDiff = function() {
	var $serverTags = {};
	this.syncAdminBoxFromClientToServerR($serverTags, this.tuix);
	
	return JSON.stringify($serverTags);
};

//Sync updates from the client to the array stored on the server
methods.syncAdminBoxFromClientToServerR = function($serverTags, $clientTags, $key1, $key2, $key3, $key4, $key5, $key6) {
	
	if ('object' != typeof $clientTags) {
		return;
	}
	
	var $type, $key0,
		__$array = 'array',
		__$tabs = 'tabs',
		__$fields = 'fields';
	
	for ($key0 in $clientTags) {
		//Only allow certain tags in certain places to be merged in
		if (
			($key1 === undefined && zenario.IN($key0, 'download', 'path', 'shake', 'tab') && ($type = __$value))
		 || ($key1 === undefined && zenario.IN($key0, '_sync', __$tabs) && ($type = __$array))
			 || ($key2 === undefined && $key1 == '_sync' && zenario.IN($key0, 'cache_dir', 'password', 'session', 'iv') && ($type = __$value))
			 || ($key2 === undefined && $key1 == __$tabs && ($type = __$array))
				 || ($key3 === undefined && $key2 == __$tabs && zenario.IN($key0, 'edit_mode', __$fields) && ($type = __$array))
					 || ($key4 === undefined && $key3 == __$tabs && $key1 == 'edit_mode' && $key0 == 'on' && ($type = __$value))
					 || ($key4 === undefined && $key3 == __$tabs && $key1 == __$fields && ($type = __$array))
						 || ($key5 === undefined && $key4 == __$tabs && $key2 == __$fields && zenario.IN($key0, '_display_value', '_was_hidden_before', 'current_value', 'pressed') && ($type = __$value))
						 || ($key5 === undefined && $key4 == __$tabs && $key2 == __$fields && $key0 == 'multiple_edit' && ($type = __$array))
							 || ($key6 === undefined && $key5 == __$tabs && $key3 == __$fields && $key1 == 'multiple_edit' && $key0 == '_changed' && ($type = __$value))
		) {
			
			//Update any values from the client on the server's copy
			if ($type == __$value) {
				if (('function' != typeof $clientTags[$key0]) && ('object' != typeof $clientTags[$key0])) {
					$serverTags[$key0] = $clientTags[$key0];
				}
			
			//For arrays, check them recursively
			} else if ($type == __$array) {
				if ('object' == typeof $clientTags[$key0]) {
					$serverTags[$key0] = {};
					this.syncAdminBoxFromClientToServerR($serverTags[$key0], $clientTags[$key0], $key0, $key1, $key2, $key3, $key4, $key5);
				}
			}
		}
	}
};











methods.getValueArrayofArrays = function(leaveAsJSONString) {
	return zenario.nonAsyncAJAX(this.getURL(), zenario.urlRequest({_read_values: true, _box: this.sendStateToServer()}), !leaveAsJSONString);
};

methods.getValues1D = function(pluginSettingsOnly, useTabNames, getInitialValues, ignoreReadonlyFields, ignoreHiddenFields) {
	
	var t, tab, f, field, name, value, values = {};
	
	if (this.tuix
	 && this.tuix.tabs) {
		foreach (this.tuix.tabs as t => tab) {
			if (tab.fields) {
				foreach (tab.fields as f => field) {
					
					name = f;
					
					if (!this.isFormField(field)
					 || (ignoreReadonlyFields && (!this.editModeOn(t) || engToBoolean(field.read_only) || engToBoolean(field.readonly)))
					 || (ignoreHiddenFields && (field._was_hidden_before || engToBoolean(field.read_only) || engToBoolean(field.readonly)))
					 || (pluginSettingsOnly && !(name = field.plugin_setting && field.plugin_setting.name))) {
						continue;
					}
					
					value = this.currentValue(f, t, getInitialValues);
					
					if (!useTabNames) {
						values[name] = value;
					}
					if (useTabNames || useTabNames === undefined) {
						values[t + '/' + f] = value;
					}
				}
			}
		}
	}
	
	return values;
};

//Inserts text at the cursors' position in a field
//Inspired by a few of the answers on http://stackoverflow.com/questions/1064089/inserting-a-text-where-cursor-is-using-javascript-jquery
methods.insertText = function(el, text) {
	
	if (_.isString(el)) {
		el = this.get(el);
	}
	
	if (el) {
		var $el = $(el),
			selectionStart = el.selectionStart,
			selectionEnd = el.selectionEnd,
			currentValue = $el.val();

		$el.val(currentValue.substring(0, selectionStart) + text + currentValue.substring(selectionEnd));
		el.selectionStart =
		el.selectionEnd = selectionStart + text.length;
		el.focus();
	}
};

methods.callFunctionOnEditors = function(action) {
	
	var fields, f, field;
	
	if (window.tinyMCE && (fields = this.fields())) {
		foreach (fields as var f => field) {
			
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



methods.format = function(wipeValues) {
	var url;
	if (!this.loaded || !(url = this.getURL('format'))) {
		return;
	}
	
	var that = this;
	
	this.loaded = false;
	this.hideTab();
	
	this.checkValues(wipeValues);
	
	this.retryAJAX(
		url,
		{_format: true, _box: this.sendStateToServer()},
		true,
		function(data) {
			that.load(data);
			that.sortTabs();
			that.draw();
		},
		'loading'
	);
};

methods.validate = function(differentTab, tab, wipeValues, callBack) {
	var url;
	if (!this.loaded || !(url = this.getURL('validate'))) {
		return;
	}
	
	this.differentTab = differentTab;
	this.loaded = false;
	this.hideTab(differentTab);
	
	this.checkValues(wipeValues);
	
	var that = this;
	
	this.retryAJAX(
		url,
		{_validate: true, _box: this.sendStateToServer()},
		true,
		function(data) {
			if (that.load(data)) {
		
				if (!that.errorOnTab(that.tuix.tab) && tab !== undefined) {
					that.tuix.tab = tab;
				}
			}
	
			that.sortTabs();
			that.switchToATabWithErrors();
			that.draw();
	
			if (callBack) {
				callBack();
			}
		},
		'loading'
	);
};






});
}, zenarioF);
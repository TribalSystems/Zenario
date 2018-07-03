/*
 * Copyright (c) 2018, Tribal Limited
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
	
	For more information, see js_minify.shell.php for steps (1) and (2), and tuix.wrapper.js.php for step (3).
*/

zenario.lib(function(
	undefined,
	URLBasePath,
	document, window, windowOpener, windowParent,
	zenario, zenarioA, zenarioT, zenarioAB, zenarioAT, zenarioO,
	encodeURIComponent, defined, engToBoolean, get, htmlspecialchars, jsEscape, phrase,
	extensionOf, methodsOf, has,
	zenarioF
) {
	zenarioT.lib(function(
		_$html,
		_$div,
		_$input,
		_$select,
		_$option,
		_$span,
		_$label
	) {
		"use strict";
		
		var methods = methodsOf(zenarioF);



methods.init = function(globalName, microtemplatePrefix, containerId) {
	
	thus.globalName = globalName;
	thus.mtPrefix = microtemplatePrefix;
	thus.containerId = containerId;
	
	thus.baseCSSClass = '';
	thus.onKeyUpNum = 0;
	zenarioAB.sizing = false;
	thus.cachedAJAXSnippets = {};
	thus.changed = {};
	thus.toggleLevelsPressed = {};
	thus.lastFocus = false;
	thus.fieldThatTriggeredRedraw = false;
	thus.editingPositions = {};
	//this.editingHistory = {};
};

methods.enableInlineErrors = function() {
	return true;
};


//When looking for an element by id, if a container id is set, try to find an element in this container.
//Otherwise just call get(), which looks for elements anywhere on the page
methods.get = function(el) {
	return (thus.containerId && $('#' + thus.containerId + ' #' + zenario.cssEscape(el))[0]) || get(el);
};

methods.microTemplate = function(template, data, filter) {
	
	var needsTidying = zenario.addLibPointers(data, thus),
		html = zenario.microTemplate(template, data, filter);
	
	if (needsTidying) {
		zenario.tidyLibPointers(data);
	}
	
	return html;
};

methods.pMicroTemplate = function(template, data, filter) {
	return thus.microTemplate(thus.mtPrefix + '_' + template, data, filter);
};

methods.fun = function(functionName) {
	return thus.globalName + '.' + functionName;
};




methods.onThisRow = function(name, id) {
	return name + id.replace(/(.*_|\D)/g, '');
};

methods.valueOnThisRow = function(name, id) {
	return thus.value(thus.onThisRow(name, id));
};

methods.fieldOnThisRow = function(name, id) {
	return thus.field(thus.onThisRow(name, id));
};


methods.getKey = function(itemLevel) {
	return thus.tuix && thus.tuix.key;
};

methods.getKeyId = function(limitOfOne) {
	return thus.tuix && thus.tuix.key && thus.tuix.key.id;
};

methods.getLastKeyId = function(limitOfOne) {
	return thus.getKeyId(limitOfOne);
};


methods.getTitle = function() {
	
	var title, values;
	
	if (thus.tuix.key
	 && thus.tuix.key.id
	 && (title = thus.tuix.title_for_existing_records)) {
		values = thus.getValues1D(false, undefined, true);
	
		foreach (values as c => v) {
			if (title.indexOf('[[' + c + ']]') != -1) {
			
				while (title != (string2 = title.replace('[[' + c + ']]', v))) {
					title = string2;
				}
			}
		}
		
		return title;
		
	} else {
		return thus.tuix.title;
	}
};


methods.field = function(id, tab) {
	if (_.isObject(id)) {
		return id;
	} else {
		return (tabs = thus.tuix && thus.tuix.tabs)
			&& (tab = tab || thus.tuix.tab)
			&& (tabs[tab]
			 && tabs[tab].fields
			 && tabs[tab].fields[id]);
	}
};

methods.fields = function(tab) {
	var tabs;
	return (tabs = thus.tuix && thus.tuix.tabs)
		&& (tab = tab || thus.tuix.tab)
		&& (tabs[tab]
		 && tabs[tab].fields);
};


//Setup some fields when the Admin Box is first loaded/displayed
methods.initFields = function() {
	
	var currentTab = thus.tuix.tab,
		tab, id, i, panel, fields, f, field, fieldId,
		tabHasRequiredIfNotHiddenFields, visibleFieldsOnIndent, hiddenFieldsByIndent, fieldValuesByIndent,
		lovs = thus.tuix.lovs || {};
	
	if (thus.tuix.tabs) {
		foreach (thus.tuix.tabs as tab) {
			
			if (fields = thus.fields(tab)) {
				tabHasRequiredIfNotHiddenFields = false;
				
				foreach (fields as id => field) {
					if (field) {
						
						//Ensure this the display values for <use_value_for_plugin_name> fields are always looked up,
						//even if this field is never actually shown
						if (field.pick_items
						 && field.plugin_setting
						 && field.plugin_setting.use_value_for_plugin_name) {
							thus.pickedItemsArray(id, thus.value(id, tab, false));
						
						} else
						if (field.values
						 && _.isString(field.values)
						 && lovs[field.values]) {
							field.values = lovs[field.values];
						
						} else
						if (field.load_values_from_organizer_path && !field.values) {
							thus.loadValuesFromOrganizerPath(field);
						}
						
						//Look through all of the fields on all of the tabs, looking for the "required_if_not_hidden" property.
						if (field.validation
						 && defined(field.validation.required_if_not_hidden)) {
							tabHasRequiredIfNotHiddenFields = true;
						}
					}
				}
				
				//This is a work-around to avoid a bug with the "required_if_not_hidden" property.
				//If a field was never shown, the system does not correctly track whether it was hidden, and raises
				//and error. To work around this, we'll do a fake "display" of each tab with one of these fields on it
				//when we first open the FAB.
				//There's no need to look in the tab thus's about to be drawn, this bug doesn't apply there
				if (tabHasRequiredIfNotHiddenFields && tab != currentTab) {
					visibleFieldsOnIndent = {};
					hiddenFieldsByIndent = {};
					fieldValuesByIndent = {};
					
					//Set the current tab in TUIX to the tab we're currently checking
					thus.tuix.tab = tab;
					
					//If we find it, then we need to call drawField() for each field on the tab, and just
					//run the first part of the function (this sets meta-data for hidden fields).
					thus.drawFields(undefined, undefined, true);
					
					//Set the tab back to what it was before
					thus.tuix.tab = currentTab;
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
	if (thus.editCancelEnabled(thus.tuix.tab)) {
		if (thus.editModeOn()) {
			thus.editCancelOrRevert('cancel', thus.tuix.tab);
		} else {
			thus.editCancelOrRevert('edit', thus.tuix.tab);
		}
	}
};

methods.revertTab = function() {
	if (thus.changes(thus.tuix.tab) && thus.revertEnabled(thus.tuix.tab)) {
		thus.editCancelOrRevert('revert', thus.tuix.tab);
	}
};

methods.editCancelOrRevert = function(action, tab) {
	
	if (!thus.tuix.tabs[tab].edit_mode) {
		return;
	}
	
	var value,
		fields, f, field,
		needToFormat,
		needToValidate;
	
	if (action == 'edit') {
		needToFormat = engToBoolean(thus.tuix.tabs[tab].edit_mode.format_on_edit);
		needToValidate = engToBoolean(thus.tuix.tabs[tab].edit_mode.validate_on_edit);
	
	} else if (action == 'cancel') {
		needToFormat = engToBoolean(thus.tuix.tabs[tab].edit_mode.format_on_cancel_edit);
		needToValidate = engToBoolean(thus.tuix.tabs[tab].edit_mode.validate_on_cancel_edit);
	
	} else if (action == 'revert') {
		needToFormat = engToBoolean(thus.tuix.tabs[tab].edit_mode.format_on_revert);
		needToValidate = thus.errorOnTab(tab) || engToBoolean(thus.tuix.tabs[tab].edit_mode.validate_on_revert);
	}
	
	if (!needToValidate
	 && (fields = thus.fields(tab))) {
		foreach (fields as var f => field) {
			if (engToBoolean(field.validate_onchange)
			 && (defined(value = thus.readField(f)))
			 && (value != field.value)) {
				needToValidate = true;
				break;
			
			} else
			if (!needToFormat
			 && engToBoolean(field.format_onchange)
			 && (defined(value = thus.readField(f)))
			 && (value != field.value)) {
				needToFormat = true;
			}
		}
	}
	
	thus.tuix.tabs[tab].edit_mode.on = action != 'cancel';
	thus.changed[tab] = false;
	
	if (needToValidate) {
		thus.validate(undefined, undefined, true);
	
	} else if (needToFormat) {
		thus.format(true);
	
	} else {
		thus.wipeTab();
		thus.redrawTab(true);
	}
	
	if (thus.tuix.tab == tab) {
		$('#zenario_abtab').removeClass('zenario_abtab_changed');
	}
};


methods.clickTab = function(tab) {
	if (thus.loaded) {
		thus.validate(tab != thus.tuix.tab, tab);
	}
};

methods.clickButton = function(el, id) {
	
	var button;
	
	if (button = thus.field(id)) {
		if (button.type == 'submit') {
			button.pressed = true;
			thus.validate(true);
	
		} else if (button.type == 'toggle') {
			
			var $el = $(thus.get(id)),
				$rowEl = $(thus.get('row__' + id));
			
			if (button.pressed = !engToBoolean(button.pressed)) {
				$el.removeClass('not_pressed').addClass('pressed');
				$rowEl.addClass('zfea_row_pressed');
			} else {
				$el.removeClass('pressed').addClass('not_pressed');
				$rowEl.removeClass('zfea_row_pressed');
			}
			
			thus.markAsChanged();
			
			thus.validateFormatOrRedrawForField(button, true);
	
		} else if (button.type == 'button') {
			button.pressed = true;
			thus.validateFormatOrRedrawForField(button);
		}
	}
};


methods.togglePressed = function(toggleLevel, tuixObject) {
	
	var show;
	
	if (!toggleLevel) {
		show = thus.toggleLevelsPressed.last;
	
	} else if (toggleLevel > 1) {
		show = thus.toggleLevelsPressed[toggleLevel - 1];
	
	} else {
		show = true;
	}
	
	if (!defined(show)) {
		show = true;
	}
	
	if (tuixObject && tuixObject.type == 'toggle') {
		thus.toggleLevelsPressed.last = show && engToBoolean(tuixObject.pressed);
		
		if (toggleLevel) {
			thus.toggleLevelsPressed[toggleLevel] = thus.toggleLevelsPressed.last;
		}
	}
	
	return show;
};

methods.toggleLevel = function(tuixObject) {
	return thus.toggleLevelsPressed.last;
};

//A wrapper for zenario.ajax(), this sets the retry and continueAnyway options,
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
	
	var i, tab, tuix = thus.tuix
	
	if (tuix && tuix.tabs) {
		if (!thus.errorOnTab(tuix.tab)) {
			foreach (thus.sortedTabs as i => tab) {
				
				if (tuix.tabs[tab]
					//zenarioT.hidden(tuixObject, lib, item, id, button, column, field, section, tab, tuix)
				 && !zenarioT.hidden(undefined, thus, undefined, tab, undefined, undefined, undefined, undefined, tuix.tabs[tab])) {
					if (thus.errorOnTab(tab)) {
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
	if (differentTab) {
		$('#zenario_abtab input').add('#zenario_abtab select').add('#zenario_abtab textarea').attr('disabled', 'disabled');
		$('#zenario_abtab').clearQueue().show().animate({opacity: .8}, 200, function() {
			thus.tabHidden = true;
			thus.draw();
		});
	} else {
		$('#zenario_abtab input').add('#zenario_abtab select').add('#zenario_abtab textarea').attr('disabled', 'disabled').animate({opacity: .9}, 100);
		thus.tabHidden = true;
		thus.draw();
	}
};

methods.draw = function() {
	if (thus.loaded && thus.tabHidden) {
		thus.draw2();
	}
};


methods.draw2 = function() {
	//var cb = new zenario.callback,
	//	html = zenarioAB.drawFields(cb);
	//...
};
methods.size = function() {
	//...
};
methods.makeFieldAsTallAsPossible = function() {
	//...
};






methods.drawTabs = function(microTemplate) {
	
	if (!microTemplate) {
		microTemplate = thus.mtPrefix + '_tab';
	}
	
	//Generate the HTML for the tabs
	var tabTUIX,
		onclick,
		data = [],
		
		last, lastWasCurrent, hadCurrent,
		prevTab, nextTab,
		
		setPrevNextTabs = function(data, i, tab) {
			
			foreach (data as i => tab) {
				if (tab.onclick) {
					if (lastWasCurrent) {
						nextTab = tab.id;
					}
					if (lastWasCurrent = tab.current) {
						prevTab = last;
					}
					last = tab.id;
				}
				if (tab.children) {
					setPrevNextTabs(tab.children);
				}
			}
		};
	
	foreach (thus.sortedTabs as i => tab) {
		if (tabTUIX = thus.tuix.tabs[tab]) {
											 //zenarioT.hidden(tuixObject, lib, item, id, button, column, field, section, tab, tuix)
			if (!(tabTUIX._was_hidden_before = zenarioT.hidden(undefined, thus, undefined, tab, undefined, undefined, undefined, undefined, tabTUIX))) {
			
				//Only allow this tab to be clicked if it looks like there's something on it
				//Dummy tabs this only exist to be the parents in drop-down menus should not be clickable
				onclick = '';
				if ((tabTUIX.fields && !_.isEmpty(tabTUIX.fields))
				 || (tabTUIX.fields && !_.isEmpty(tabTUIX.errors))
				 || (tabTUIX.fields && !_.isEmpty(tabTUIX.notices))) {
					onclick = thus.globalName + ".clickTab('" + jsEscape(tab) + "');";
				}
			
				//Show the first (clickable) tab we find, if a tab has not yet been set
				if (!thus.tuix.tab && onclick) {
					thus.tuix.tab = tab;
				}
			
				data.push({
					id: tab,
					tabId: tab,
					tuix: tabTUIX,
					onclick: onclick,
					current: thus.tuix.tab == tab,
					label: tabTUIX.label
				});
			}
		}
	}
	
	zenarioT.setButtonKin(data, 'zenario_fab_tab_with_children');
	
	setPrevNextTabs(data);
	thus.prevTab = prevTab;
	thus.nextTab = nextTab;
	
	return thus.microTemplate(microTemplate, data);
};


methods.drawFields = function(cb, microTemplate, scanForHiddenFieldsWithoutDrawingThem) {
	
	var f,
		fieldId,
		field,
		tab = thus.tuix.tab,
		tabs = thus.tuix.tabs[tab],
		groupings = thus.groupings[tab],
		sortedFields = thus.sortedFields[tab],
		errorOnFormMessage,
		html = '',
		groupingIsHidden = false,
		forceNewRowForNewGrouping,
		fields = thus.fields(tab),
		visibleFieldsOnIndent = {},
		hiddenFieldsByIndent = {},
		fieldValuesByIndent = {},
		errorsDrawn = false,
		i, error, field, notice,
		groupingName, lastGrouping, lastVisibleGrouping, groupingField = false, groupingsDrawn = {},
		data = {
			fields: {},
			rows: [],
			tabId: tab,
			path: thus.path,
			tuix: thus.tuix,
			revert: '',
			errors: [],
			notices: {}
		};
	
	thus.drawingFields = true;
	
	if (!scanForHiddenFieldsWithoutDrawingThem) {
		thus.ffoving = 0;
		thus.splitValues = {};
		thus.tallAsPossibleField = undefined;
		thus.errorOnForm = false;
		thus.disableDragDropUpload();
	
		if (!thus.savedAndContinued(tab) && thus.editCancelEnabled(tab)) {
			data.revert =
				_$div('class', 'zenario_editCancelButton',
					zenarioT.input(
						'class', 'submit',
						'type', 'button',
						'onclick', thus.globalName + '.changeMode(); return false;',
						'value', thus.editModeOn()? phrase.cancel : phrase.edit
					)
				);
		}
	
		if (thus.editModeOn()) {
			if (tabs.errors) {
				foreach (tabs.errors as i => error) {
					thus.errorOnForm = true;
					data.errors.push({message: error});
				}
			}
		
			if (fields) {
				foreach (fields as i => field) {
					if (field.error) {
						thus.errorOnForm = true;
						
						//If inline errors are enabled, just have one generic error message
						if (thus.enableInlineErrors() && (errorOnFormMessage = thus.tuix.error_on_form_message || phrase.errorOnForm)) {
							data.errors.push({message: errorOnFormMessage});
							break;
						
						} else {
							//Errors can be linked to fields, but we don't have any way of displaying this so
							//we'll just display field errors at the top of the tab with the others.
							data.errors.push({message: field.error});
						}
					}
				}
			}
		}
	
		if (tabs.notices) {
			foreach (tabs.notices as i => notice) {
				if (engToBoolean(notice.show)
				 && {error: 1, warning: 1, question: 1, information: 1, success: 1}[notice.type]) {
					data.notices[i] = notice;
				}
			}
		}
		
		thus.setPhiVariables();
	}
	
	
	foreach (sortedFields as f => fieldId) {
		field = fields[fieldId];
		
		//Note down if the last field was in a grouping
		forceNewRowForNewGrouping = false;
		groupingName = field.grouping;
		
		if (lastGrouping !== groupingName) {
			lastGrouping = groupingName;
			
			groupingField = defined(groupings[groupingName]) && fields[groupings[groupingName]];
			
			//Fields in a grouping should be hidden if the grouping is hidden, or does not actually exist
			groupingIsHidden = groupingName && (
				!groupingField || zenarioT.hidden(undefined, thus, undefined, groupings[groupingName], undefined, undefined, groupingField)
			);
		}
			
		if (lastVisibleGrouping !== groupingName) {
			//Force different groupings to be on a new row, even if the same_row property is set
			forceNewRowForNewGrouping = true;
		}
		
		field._id = fieldId;
		field._html = thus.drawField(cb, tab, fieldId, field, visibleFieldsOnIndent, hiddenFieldsByIndent, fieldValuesByIndent, scanForHiddenFieldsWithoutDrawingThem, groupingIsHidden);
		
		//Don't add completely hidden fields, or if we're just scanning fields and not drawing them
		if (scanForHiddenFieldsWithoutDrawingThem
		 || groupingIsHidden
		 || field._html === false) {
			continue;
		}
		
		if (forceNewRowForNewGrouping
		 || field._startNewRow
		 || !data.rows.length) {
			data.rows.push({fields: [], grouping: groupingField});
		}
		
		//If the grouping field has a legend or any HTML at the top, include it
		if (forceNewRowForNewGrouping
		 && groupingField
		 && (groupingField.legend || groupingField.snippet)) {
			groupingField = _.extend({grouping: groupingName}, groupingField);
			groupingField._id = groupingName;
			groupingField._html = thus.drawField(cb, tab, groupingName, groupingField);
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
	
		microTemplate = microTemplate || tabs.template || thus.mtPrefix + '_current_tab';
		html += thus.microTemplate(microTemplate, data);
	}
	
	thus.drawingFields = false;
	
	
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




//Given some raw TUIX, draw it
methods.drawTUIX = function(tuix, template, cb) {
	thus.tuix = {
		tab: 'details',
		tabs: {
			details: {
				template: template,
				edit_mode: {
					on: true,
					enabled: true,
					always_on: true
				},
				fields: tuix
			}
		}
	};
	
	thus.sortTabs();
	return thus.drawFields(cb);
};

methods.insertHTML = function(html, cb, isNewTab) {
	var tab = thus.get('zenario_abtab'),
		lastFocus = thus.lastFocus || thus.fieldThatTriggeredRedraw,
		field;
	
	tab.innerHTML = html;
	thus.tabHidden = false;
	
	if (thus.changes(thus.tuix.tab)) {
		$(tab).addClass('zenario_abtab_changed');
	} else {
		$(tab).removeClass('zenario_abtab_changed');
	}
	
	cb.call();
	
	if (field = !isNewTab && lastFocus && thus.field(lastFocus.id)) {
		thus.focusField(field, lastFocus.ss, lastFocus.se);
	}
	
	thus.fieldThatTriggeredRedraw = false;
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


//Add any special jQuery objects to the tab
methods.addJQueryElements = function(sel) {
	zenario.addJQueryElements(sel + ' ', true);
		
	//Some setup needed for iconselectmenu-type select lists
	$(sel + ' select.iconselectmenu').iconselectmenu({
		width: 'auto',
		change: function() {
			$(this).change();
		}
	});
};

//Add any special jQuery objects to the tab
methods.addJQueryElementsToTab = function() {
	thus.addJQueryElements('#zenario_abtab');
};

methods.addJQueryElementsToTabAndFocusFirstField = function() {
	thus.addJQueryElementsToTab();
	thus.focusFirstField();
};

methods.fieldIsReadonly = function(id, field, tab) {
	return field.show_as_a_label
		|| field.show_as_a_span
		|| engToBoolean(field.read_only)
		|| engToBoolean(field.readonly)
		|| (defined(tab) && !thus.editModeOn(tab))
		|| (defined(field.readonly_if)
		 && zenarioT.eval(field.readonly_if, thus, field, undefined, id, undefined, undefined, field));
		  //zenarioT.eval(condition, lib, tuixObject, item, id, button, column, field, section, tab, tuix)
};


//Focus either the first field, or if the first field is filled in and the second field is a password then focus this instead
methods.focusFirstField = function() {
	
	if (!thus.tuix
	 || engToBoolean(thus.tuix.tabs[thus.tuix.tab].disable_autofocus)) {
		return;
	}
	
	//Loop through the text-fields on a tab, looking for the first few fields
	var i = -1,
		fields = [],
		focusField = undefined,
		f, domField, field, fieldId,
		isPickerField;
	
	foreach (thus.sortedFields[thus.tuix.tab] as f => fieldId) {
		
		if ((domField = thus.get(fieldId))
		 && (field = thus.field(fieldId))
		 && ((isPickerField = field.pick_items || field.upload) || $(domField).is(':visible'))) {
			
			fields[++i] = {
				id: fieldId,
				type: field.type,
				empty: domField.value == '',
				focusable:
					(isPickerField || zenario.IN(field.type, 'password', 'checkbox', 'select', 'text', 'textarea'))
				 && !engToBoolean(domField.disabled)
				 && !engToBoolean(domField.readonly)
				 && !thus.fieldIsReadonly(fieldId, field)
			};
			
			if (i > 1) {
				break;
			}
		}
	}
	
	//If the first field is filled in and the second field is a password then focus this instead
	if (fields[0] && !fields[0].empty && fields[1] && fields[1].focusable && fields[1].empty && fields[1].type == 'password') {
		focusField = 1;
	
	//Otherwise try to focus the first text field or select list
	} else if (fields[0] && fields[0].focusable) {
		focusField = 0;
	
	//If this didn't work, try the second
	} else if (fields[1] && fields[1].focusable) {
		focusField = 1;
	
	//Otherwise don't focus anything
	} else {
		return;
	}
	
	setTimeout(function() {
		thus.focusField(fields[focusField]);
	}, 50);
};

methods.focusField = function(field, selectionStart, selectionEnd) {
	var domField;
	
	if (field.type) {
		
		//Don't attempt to focus a date picker as that will just cause it to re-open,
		//which isn't what we intend
		if (field.type == 'date'
		 || field.type == 'datetime') {
			return;
		}
		
		domField = thus.get(field.id);
	} else {
		domField = thus.$getPickItemsInput(field.id);
	}
	
	if (domField) {
		domField.focus();
		
		//Set the cursor position/selection start/end if recorded.
		if (defined(selectionStart)) {
			//(N.b. this can cause a crash on some fields/browsers if the selection is set on the wrong type of field,
			// so I've put a try/catch here out of paranoia.
			try {
				domField.selectionStart = selectionStart;
				domField.selectionEnd = selectionEnd;
			} catch (e) {
			}
		}
	}
};

methods.$getPickItemsInput = function(id) {
	return $(thus.get('name_for_' + id)).find('.TokenSearch input');
};

methods.setPhiVariables = function() {
	zenario.phiVariables = undefined;
};

methods.errorOnBox = function() {
	if (thus.tuix && thus.tuix.tabs) {
		foreach (thus.tuix.tabs as tab) {
			if (thus.errorOnTab(tab)) {
				return true;
			}
		}
	}
	
	return false;
};

methods.errorOnTab = function(tab) {
	var i, fields;
	
	if (thus.tuix.tabs[tab] && thus.editModeOn(tab)) {
		if (thus.tuix.tabs[tab].errors) {
			foreach (thus.tuix.tabs[tab].errors as i) {
				return true;
			}
		}
		if (fields = thus.fields(tab)) {
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
		thus.wipeTab();
	} else {
		thus.readTab();
	}
	
	foreach (thus.tuix.tabs as var tab => var thisTab) {
		
		//Workaround for a problem where initial values do not get submitted if a tab is never visited.
		//This script loops through all of the tabs and all of the fields on this admin boxes, and ensures
		//this their values are set correctly.
		var editing = thus.editModeOn(tab);
		if (thisTab.fields) {
			foreach (thisTab.fields as var f) {
				
				var field = thisTab.fields[f],
					multi = field.pick_items || field.type == 'checkboxes' || field.type == 'radios' || field.type == 'multiselect';
				
				//Ignore non-field types
				if (thus.isFormField(field)) {
					
					if (field.type == 'code_editor') {
						zenario.clearAllDelays('code_editor_' + f);
					}
					
					if (!defined(field.current_value)) {
						field.current_value = thus.value(f, tab, true);
					}
					
					if (editing) {
						if (!defined(field.value)) {
							field.value = thus.value(f, tab, false);
						}
					
						if (field.multiple_edit
						 && !defined(field.multiple_edit._changed)
						 && defined(field.multiple_edit.changed)) {
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
		thus.lastFocus = thus.setLastFocus(document.activeElement);
	}
};

	

methods.setLastFocus = function(id) {
	
	var dom,
		details;
	
	if (typeof id === 'object') {
		dom = id;
		id = dom.id
	} else {
		dom = thus.get(id);
	}
	
	details = {id: id};
	
	if (dom) {
		//(N.b. this can cause a crash on some fields/browsers if the selection is set on the wrong type of field,
		// so I've put a try/catch here out of paranoia.
		try {
			details.ss = dom.selectionStart;
			details.se = dom.selectionEnd;
		} catch (e) {
			details.ss =
			details.se = undefined;
		}
	}
	
	return details;
};

	

methods.enableMicroTemplates = function(field, object) {
	
	var key;
	
	foreach (object as key) {
		switch (typeof object[key]) {
			case 'object':
				if (key !== 'lib'
				 && key !== 'thus'
				 && key !== 'tuix') {
					thus.enableMicroTemplates(field, object[key]);
				}
				break;
			
			case 'string':
				if (key !== 'type'
				 && (key !== 'value' || !thus.isFormField(object))
				 && key !== 'current_value'
				 && object[key].match(/(\{\{|\{\%|\<\%)/)) {
					(function(object, key, fun) {
						fun = zenario.generateMicroTemplate('<% ' + thus.defineLibVarBeforeCode() + ' %>' + object[key], undefined);
						object[key] = function() {
							return fun(object);
						}
					})(object, key);
				}
		}
	}
};

	

methods.displayAsTag = function(field, readOnly) {
	
	if (field.show_as_a_label || (readOnly && field.show_as_a_label_when_readonly)) {
		return 'label';
	
	} else if (field.show_as_a_span || (readOnly && field.show_as_a_span_when_readonly)) {
		return 'span';
	
	} else {
		return false;
	}
};

methods.drawField = function(cb, tab, id, field, visibleFieldsOnIndent, hiddenFieldsByIndent, fieldValuesByIndent, scanForHiddenFieldsWithoutDrawingThem, groupingIsHidden, lov, value, readOnly, sortOrder, existingParents, lovField) {
	
	if (!defined(field)) {
		field = thus.field(id, tab);
	}
	if (!defined(field.id)) {
		field.id = id;
	}
	
	if (engToBoolean(field.enable_microtemplates_in_properties)) {
		thus.enableMicroTemplates(field, field);
	}
	
	var fieldType = field.type,
		upload = field.upload,
		pick_items = field.pick_items,
		disabled = field.disabled,
		validation = field.validation || {},
		isDatePicker = fieldType == 'date' || fieldType == 'datetime',
		hidden,
		hideOnOpen,
		indent,
		newRow,
		snippet,
		tag,
		atts,
		html = '',
		i, si, v, val, displayVal,
		splitValues,
		picked_items = {},
		isMultiSelectList = fieldType == 'multiselect',
		hasSlider = false,
		extraAtt = {'class': ''},
		extraAttAfter = {},
		overrides = {},
		isTextFieldWithAutocomplete = false,
		parentsValuesExist = false,
		selected_option,
		prop, match,
		addWidgetWrap = false,
		isButton = false,
		useButtonTag = false,
		hasBR = false,
		startOfTable = false,
		middleOfTable = false,
		hasPreFieldTags = false,
		hasPostFieldTags = false,
		preFieldTags,
		postFieldTags,
		tabTUIX = thus.tuix.tabs[tab];
	
	
	if (!defined(readOnly)) {
		readOnly = thus.fieldIsReadonly(id, field, tab);
	}
	
	//Auto set the readonly-flag if a dev sets a picker to disabled
	if (pick_items && disabled) {
		readOnly = true;
	}
	
	//Currently date-time fields are readonly
	if (fieldType && fieldType == 'datetime') {
		readOnly = true;
	}
	
	if (lovField) {
		if (defined(lovField.disabled_if)
			//zenarioT.eval(c, lib, tuixObject, item, id, button, column, field, section, tab, tuix)
		 && zenarioT.eval(lovField.disabled_if, thus, lovField, undefined, lov, undefined, undefined, field)) {
			readOnly = true;
		}
	} else
	if (defined(field.disabled_if)
	 && zenarioT.eval(field.disabled_if, thus, undefined, undefined, id, undefined, undefined, field)) {
		readOnly = true;
	}
	
	
	
	if (!field['class'] && field.css_class) {
		field['class'] = field.css_class;
	}
	
	if (!field.snippet && !defined(value)) {
		value = thus.value(id, tab);
	}
	
	if (!defined(value)) {
		value = '';
	}

	
	//If this is the first call and not a sub-call
	if (!defined(lov)) {
	
		//Set up a shortcut to the selected value.
		//This makes some visible-if statements simplier to write
		delete field.selected_option;
		if (value && field.values) {
			selected_option = _.isObject(field.values)?
				field.values[value]
			  : thus.tuix.lovs
			 && thus.tuix.lovs[field.values]
			 && thus.tuix.lovs[field.values][value];
			
			//Catch the case where an option was visible, but is now hidden.
			//In this case it shouldn't count as selected.
			if (_.isObject(selected_option)
				//zenarioT.hidden(tuixObject, lib, item, id, button, column, field, section, tab, tuix)
			 && zenarioT.hidden(selected_option, thus, undefined, value, undefined, undefined, field, undefined, tabTUIX)) {
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
		
		//If thus is a picker-type field, and one or more values are selected, ensure this the values object is set up
		if ((upload || pick_items || isMultiSelectList) && value != '') {
			picked_items = thus.pickedItemsArray(field, value);
		}
		
		if (field.values
		 && _.isString(field.values)
		 && thus.tuix.lovs
		 && thus.tuix.lovs[field.values]) {
			field.values = thus.tuix.lovs[field.values];
		
		} else
		if (field.load_values_from_organizer_path && !field.values) {
			thus.loadValuesFromOrganizerPath(field);
		}
		
		
		
		
		//Allow people writing YAML files to put HTML tags in as property names
		//as a tidier shortcut to using pre/post field HTML.
		foreach (field as prop) {
			if (prop[0] == '<'
			 && (match = prop.match(/^<(\/?)(\w+)/))) {
				
				//Add any HTML in the value on as well
				if (field[prop] && _.isString(field[prop])) {
					prop += field[prop];
				}
				
				//We can't rely on JavaScript maintaining the order of the tags from PHP.
				//To work round this problem we'll sort them into a consistant order
				i = {table: 1, tr: 2, td: 3, th: 4, br: 5, div: 6, p: 7, span: 8}[match[2]] || 9;
				
				if (i < 5) {
					middleOfTable = true;
				}
				
				//If the string starts with an opening tag, put it before the field
				//If the string starts with a closing tag, put it after the field in reverse order.
				if (match[1]) {
					if (!hasPostFieldTags) {
						hasPostFieldTags = true;
						postFieldTags = ['', '', '', '', '', '', '', '', ''];
					}
					postFieldTags[9 - i] += prop;
				} else {
					if (!hasPreFieldTags) {
						hasPreFieldTags = true;
						preFieldTags = ['', '', '', '', '', '', '', '', ''];
					}
					preFieldTags[i - 1] += prop;
					
					if (i == 1) {
						startOfTable = true;
					}
					
					if (i == 5) {
						hasBR = true;
					}
				}
			}
		}
	}
	
	//Buttons and submit buttons should lose their "pressed" status when redrawn
	if (!scanForHiddenFieldsWithoutDrawingThem && (fieldType == 'button' || fieldType == 'submit')) {
		field.pressed = false;
	}
	
	if (scanForHiddenFieldsWithoutDrawingThem || !defined(lov)) {
		//Close the last row if it was left open, unless this field should be on the same line
		//Either check the same_row property to work this out, or if same_row was not set,
		//try to set it automatically if someone is using a table-layout.
		if (!defined(field.same_row))  {
			newRow = !hasBR && (startOfTable || !middleOfTable);
		} else {
			newRow = !engToBoolean(field.same_row);
		}
		field._startNewRow = newRow;
		
		//Check if this field should be hidden
		indent = 1 * field.indent || 0;
		hidden = groupingIsHidden
		
			  || zenarioT.hidden(undefined, thus, undefined, id, undefined, undefined, field)
			  
			  || readOnly && field.hide_when_readonly
			
			  || (engToBoolean(hiddenFieldsByIndent && hiddenFieldsByIndent.last? field.hide_with_previous_field : field.hide_if_previous_field_is_not_hidden))
			  || (engToBoolean(hiddenFieldsByIndent && hiddenFieldsByIndent.beforeLast? field.hide_with_previous_previous_field : field.hide_if_previous_previous_field_is_not_hidden))
		
			  || (engToBoolean(field.hide_with_previous_indented_field)
			   && hiddenFieldsByIndent
			   && hiddenFieldsByIndent[indent])
		
			  || (engToBoolean(field.hide_with_previous_outdented_field)
			   && indent > 0
			   && hiddenFieldsByIndent
			   && hiddenFieldsByIndent[indent - 1])
		
			  || (engToBoolean(field.hide_with_previous_indented_fields)
			   && !visibleFieldsOnIndent[indent])
		
			  || (defined(field.hide_if_previous_value_isnt)
			   && !zenario.inList(field.hide_if_previous_value_isnt, fieldValuesByIndent && fieldValuesByIndent.last))
		
			  || (defined(field.hide_if_previous_outdented_value_isnt)
			   && indent > 0
			   && !zenario.inList(field.hide_if_previous_outdented_value_isnt, fieldValuesByIndent && fieldValuesByIndent[indent - 1]));
		
		if (fieldValuesByIndent) {
			fieldValuesByIndent.last = value;
			
			if (newRow) {
				fieldValuesByIndent[indent] = value;
			}
		}
		if (hiddenFieldsByIndent) {
			hiddenFieldsByIndent.beforeLast = hiddenFieldsByIndent.last;
			hiddenFieldsByIndent.last = hidden;
			
			if (newRow) {
				hiddenFieldsByIndent[indent] = hidden;
			}
			
			if (!defined(visibleFieldsOnIndent.last)
			 || visibleFieldsOnIndent.last !== indent) {
				
				if (visibleFieldsOnIndent.last < indent) {
					visibleFieldsOnIndent[indent] = false;
				}
				
				visibleFieldsOnIndent.last = indent;
			}
			
			if (!hidden) {
				visibleFieldsOnIndent[indent] = true;
			}
		}
		
		//Include an animation to show newly unhidden fields
		if (field._startNewRow
		 && thus.shownTab !== false
		 && thus.shownTab == thus.tuix.tab
		 && fieldType != 'editor'
		 && fieldType != 'code_editor'
		 && field._was_hidden_before
		 && !hidden) {
			field._showOnOpen = true;
		
		//Include an animation to hide newly hidden fields
		} else
		if (field._startNewRow
		 && thus.shownTab !== false
		 && thus.shownTab == thus.tuix.tab
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
		
		//Look out for the "tall_as_possible" property and note down which field has it.
		//(Only allow a max of one field per tab).
		if (!hidden
		 && field.tall_as_possible
		 && !defined(thus.tallAsPossibleField)) {
			thus.tallAsPossibleField = id;
			thus.tallAsPossibleFieldType = field.type;
		}
		
		
	
		if (field.multiple_edit) {
			var meHTML = '',
				meId,
				changed,
				hideUI;
		
			if (!readOnly && defined(field.multiple_edit._changed)) {
				changed = engToBoolean(field.multiple_edit._changed);
			} else {
				changed = field.multiple_edit.changed;
			}
			
			if (engToBoolean(field.multiple_edit.hide_ui) || !field.multiple_edit.select_list) {
				
				hideUI = engToBoolean(field.multiple_edit.hide_ui);
				
				meHTML +=
					_$input('type', 'checkbox', 'class', 'multiple_edit', 'id', 'multiple_edit__' + id, 'checked', changed,
						'style', hideUI? 'display: none;' : '',
						'disabled', readOnly,
						'onchange', readOnly || hideUI? '' : thus.globalName + '.meChange(this.checked, \'' + htmlspecialchars(id) + '\');', 
					'>');
			
			} else {
				meHTML += _$select(
					'class', 'multiple_edit', 'id', 'multiple_edit__' + id, 'disabled', readOnly,
					'onchange', thus.globalName + '.meChange(this.value == 1, "' + jsEscape(id) + '");',
						_$option('value', '', 'selected', !changed, field.multiple_edit.select_list.not_changed_label || phrase.notChanged) +
						_$option('value', 1, 'selected', changed, field.multiple_edit.select_list.changed_label || phrase.changed)
				);
			}
		
			html += meHTML;
		
			//delete meHTML;
			meHTML = undefined;
		}
	}
	
	
	//Ensure this fields with a list of values (e.g. select lists, checboxes, radios) have the values sorted
	//in the correct order.
	if (!(sortOrder && existingParents)
	 && typeof field.values == 'object') {
		
		sortOrder = [];
		existingParents = {};
		
		if ((upload && engToBoolean(upload.reorder_items))
		 || (pick_items && engToBoolean(pick_items.reorder_items))) {
			
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
				
					if (defined(val.parent)) {
						parentsValuesExist = true;
						existingParents[val.parent] = true;
					}
					
					//zenarioT.hidden(tuixObject, lib, item, id, button, column, field, section, tab, tuix)
					if (zenarioT.hidden(val, thus, undefined, v, undefined, undefined, field, undefined, tabTUIX)) {
						continue;
					
					} else if (field.values_visible_if && !zenarioT.eval(field.values_visible_if, thus, val, undefined, v, undefined, undefined, field, undefined, tabTUIX)) {
						continue;
					
					} else if (defined(val.ord)) {
						sortOrder.push([v, val.ord]);
					} else if (defined(val.label)) {
						sortOrder.push([v, val.label]);
					} else {
						sortOrder.push([v, v]);
					}
				} else {
					sortOrder.push([v, val]);
				}
			}
	
			sortOrder.sort(zenarioT.sortArray);
		
			//Remove fields this were just there to help sort
			foreach (sortOrder as var i) {
				sortOrder[i] = sortOrder[i][0];
			}
		}
	}
	
	
	//Set the "readonly" flag for fields this are read-only
	if (readOnly) {
		extraAtt.readonly = true;
		
		//If this is a button, also set the disabled flag so we get the "disabled" styling
		//(We don't want to set thus for text fields though, as this stops people from copy-pasting the values.)
		if (thus.isButton(field)) {
			extraAtt.disabled = true;
			extraAtt['class'] += 'disabled ';
		}
	
	} else {
		if (field.error) {
			extraAtt['class'] += 'zenario_field_with_error ';
		}
		if (validation.required || validation.required_if_not_hidden) {
			extraAtt['class'] += 'zenario_required_field ';
		}
	}
	
	
	if (fieldType != 'checkboxes'
	 && (tag = thus.displayAsTag(field, readOnly))) {
		
		displayVal = htmlspecialchars(value);
		
		if (fieldType == 'select' || fieldType == 'radios') {
			if (field.values && field.values[value]) {
				displayVal = htmlspecialchars(field.values[value]);
			
			} else if (field.empty_value) {
				displayVal = htmlspecialchars(field.empty_value);
			}
		}
		
		html += _$html(tag, 'id', tag + '__' + id, 'class', field.css_class, displayVal);
	
	//Draw HTML snippets
	} else if (snippet = field.snippet) {
		
		//Draw hr/label/h1/h2/h3/h4s if set
		for (si in snippetTags) {
			tag = snippetTags[si];
			
			if (snippet[tag]) {
				html += _$html(tag,
					'id', tag + '__' + id,
					'class', snippet[tag + '_class'],
					'style', snippet[tag + '_style'],
						htmlspecialchars(snippet[tag])
				);
			}
		}
		
		//Draw the second part of a "type: radios" field this was split using the split_values_if_selected property
		if (snippet.show_split_values_from) {
			if (thus.splitValues
			 && thus.splitValues[snippet.show_split_values_from]) {
				html += thus.splitValues[snippet.show_split_values_from];
				delete thus.splitValues[snippet.show_split_values_from];
			}
		}
		
		if (snippet.html) {
			html += _$span('id', 'snippet__' + id, zenario.unfun(snippet.html));
		}
		
		if (snippet.microtemplate) {
			html += _$div('id', 'microtemplate__' + id, thus.microTemplate(snippet.microtemplate, field));
		}
		
		if (snippet.url) {
			if (!engToBoolean(snippet.cache)) {
				html += zenario.nonAsyncAJAX(zenario.addBasePath(snippet.url));
			
			} else if (!thus.cachedAJAXSnippets[snippet.url]) {
				html += (thus.cachedAJAXSnippets[snippet.url] = zenario.nonAsyncAJAX(zenario.addBasePath(snippet.url)));
			
			} else {
				html += thus.cachedAJAXSnippets[snippet.url];
			}
		
		}
	
	//Draw multiple checkboxes/radiogroups
	} else if ((fieldType == 'checkboxes' || fieldType == 'radios') && !defined(lov)) {
		
		if (readOnly && field.display_as_text_when_readonly) {
			html += htmlspecialchars(thus.displaySelectedItems(id, field, value, tab));
			
		} else if (field.values) {
			var thisField = _.extend({}, field);
			picked_items = thus.pickedItemsArray(field, value);
			
			thisField.name = field.name || id;
			thisField.type = fieldType == 'checkboxes'? 'checkbox' : 'radio';
			
			if (readOnly) {
				thisField.disabled = true;
			}
			
			if (_.isEmpty(sortOrder)) {
				html += _$label('class', 'zenario_no_values_message', htmlspecialchars(field.no_values_message));
			} else {
				html += thus.hierarchicalBoxes(cb, tab, id, value, field, thisField, readOnly, picked_items, sortOrder, existingParents);
			}
		}
	
	} else if (upload || pick_items || isMultiSelectList) {
		
		var multiple_select = isMultiSelectList
						   || ((pick_items && engToBoolean(pick_items.multiple_select))
						   || (upload && engToBoolean(upload.multi))),
			mergeFields = {
				id: id,
				pickerHTML: '',
				wrappedId: 'name_for_' + id,
				readOnly: readOnly
			};
		
		if (readOnly) {
			//mergeFields.pickedItems = this.drawPickedItems(id, true);
		
		} else {
			//mergeFields.pickedItems = this.drawPickedItems(id, false);
			
			if (pick_items
			 && (pick_items.target_path || pick_items.path)
			 && !engToBoolean(pick_items.hide_select_button)) {
				mergeFields.select = {
					onclick: thus.globalName + ".pickItems('" + htmlspecialchars(id) + "');",
					phrase: pick_items.select_phrase || phrase.selectDotDotDot
				};
			}
			
			if (upload) {
				mergeFields.upload = {
					onclick: thus.globalName + ".upload('" + htmlspecialchars(id) + "');",
					phrase: upload.upload_phrase || phrase.uploadDotDotDot
				};
				
				if (engToBoolean(upload.drag_and_drop)) {
					thus.upload(id, true);
				}
				
				if (window.Dropbox && Dropbox.isBrowserSupported()) {
					mergeFields.dropbox = {
						onclick: thus.globalName + ".chooseFromDropbox('" + htmlspecialchars(id) + "');",
						phrase: upload.dropbox_phrase || phrase.dropboxDotDotDot
					};
				}
			}
		}
		
	
		mergeFields.pickerHTML += _$select('id', id, 'multiple', multiple_select, 'class', field.css_class, 'style', field.style, '>');
		
		//If there are selected values, draw them in so this the tokenize library initialises correctly
		if (field.values && (!_.isEmpty(picked_items) || isMultiSelectList)) {
			mergeFields.pickerHTML += thus.hierarchicalSelect(picked_items, field, tabTUIX, sortOrder, parentsValuesExist, existingParents);
		}
		
		mergeFields.pickerHTML += '</select>';
		
		html += thus.microTemplate(thus.mtPrefix + '_picked_items', mergeFields);
		
		cb.after(function() {
			if (isMultiSelectList) {
				thus.setupMultipleSelect(field, id, tab, readOnly || disabled);
			} else {
				thus.setupPickedItems(field, id, tab, readOnly, multiple_select);
			}
		});
		
		
		
	
	} else if (fieldType) {
		if (lov) {
			extraAttAfter.onchange = "lib.fieldChange('" + htmlspecialchars(id) + "', '" + htmlspecialchars(lov) + "');";
		} else {
			extraAttAfter.onchange = "lib.fieldChange('" + htmlspecialchars(id) + "');";
		}
		
		if (disabled) {
			extraAtt['class'] += 'disabled ';
		}
		
		if (validation.numeric) {
			extraAtt.onkeyup =
				(extraAtt.onkeyup || '') +
				"lib.keepNumeric(this)";
		}
		
		if (field.return_key_presses_button && !readOnly) {
			extraAtt.onkeyup =
				(extraAtt.onkeyup || '') +
				"if (event.keyCode == 13) {" +
					"zenario.stop(event);" +
					"$('#" + htmlspecialchars(field.return_key_presses_button) + "').click();" +
					"return false;" +
				"}";
		}
		
		if (engToBoolean(field.multiple_edit) && !readOnly) {
			extraAtt.onkeyup =
				(extraAtt.onkeyup || '') +
				"if (event && event.keyCode == 9) return true; lib.meMarkChanged('" + htmlspecialchars(id) + "', this.value, '" + htmlspecialchars(field.value) + "');";
				//Note keyCode 9 is the tab key; a field should not be marked as changed if the Admin is just tabbing through them
		}
		
		extraAtt['class'] += 'input_' + fieldType;
		
		
		//Open the field's tag
		switch (fieldType) {
			case 'select':
				if (field.slider) {
					hasSlider = true;
					html += thus.drawSlider(cb, id, field, readOnly, true);
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
				extraAtt['class'] = ' zenario_embedded_ace_editor';
			
				//Set up code editors after the HTML is drawn
				cb.after(function() {
					var codeEditor = ace.edit(id),
						editorJustChanged = false;
					
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
								thus.fieldChange(id);
							}, 0);
						}
						editorJustChanged = false;
					});
					codeEditor.on('change', function(e) {
						thus.markAsChanged();
						editorJustChanged = true;
						
						zenario.actAfterDelayIfNotSuperseded('code_editor_' + id, function() {
							thus.fieldChange(id);
						}, 5000);
					});
					
					var language, langTools, isHTML;
		
					//Attempt to set the correct language
					if (language = field.language) {
						try {
							isHTML = language == 'html';
							
							//Attempt to detect the language from the filename
							if (language.match(/\.twig\.html$/i)) {
								language = 'ace/mode/twig';
							} else if (language.match(/\./)) {
								language = ace.require('ace/ext/modelist').getModeForPath(language).mode;
							} else {
								language = 'ace/mode/' + language;
							}
							
							langTools = ace.require("ace/ext/language_tools");
							codeEditor.session.setMode(language);
							
							if (isHTML) {
								langTools.setCompleters([]);
								codeEditor.setOptions({
									enableBasicAutocompletion: false,
									enableLiveAutocompletion: false
								});
							} else {
								langTools.setCompleters([langTools.keyWordCompleter]);
								codeEditor.setOptions({
									enableBasicAutocompletion: true,
									enableLiveAutocompletion: true
								});
							}
				
						} catch (e) {
							console.log('Ace editor could not load this language', language);
						}
					}
					
					codeEditor.$blockScrolling = Infinity;
					
					thus.setCodeEditorPosition(codeEditor, thus.editingPositions[tab + '/' + id]);
					
					//I've been experiementing with trying to save the undo history, but can't get it working :(
					//var editingHistory, undoManager;
					//if (editingHistory = this.editingHistory[tab + '/' + id]) {
					//	codeEditor.session.setUndoManager(editingHistory);
					//	//if (undoManager = codeEditor.getSession().getUndoManager()) {
					//	//	undoManager.reset();
					//	//	undoManager.$doc = editingHistory.$doc;
					//	//	undoManager.$redoStack = editingHistory.$redoStack;
					//	//	undoManager.$undoStack = editingHistory.$undoStack;
					//	//	undoManager.dirtyCounter = editingHistory.dirtyCounter;
					//	//}
					//}
				});
				
				break;
			
			
			case 'color_picker':
			case 'colour_picker':
				html += '<input';
				extraAtt['class'] = ' zenario_color_picker';
			
	
				//Set up the colour picker after the html is on the page
				cb.after(function() {
					var color_picker_options = field.color_picker_options || field.colour_picker_options || {};
				
					color_picker_options.disabled = readOnly;
					color_picker_options.preferredFormat = color_picker_options.preferredFormat || 'hex';
				
					$(thus.get(id)).spectrum(color_picker_options);
				});
				
				break;
			
			
			case 'editor':
				html += '<textarea';
			
				zenarioA.getSkinDesc();
			
				var dummyTextArea = '<textarea ' + thus.outputAtts(field) + '></textarea>',
					minHeight = $(dummyTextArea).height(),
					maxHeight = Math.max(Math.floor(($(window).height()) * 0.5), 300),
					content_css = undefined,
					onchange_callback = function(inst) {
							thus.fieldChange(inst.id);
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
							
							min_height: minHeight,
							max_height: maxHeight,
							autoresize_min_height: minHeight,
							autoresize_max_height: maxHeight,
							autoresize_bottom_margin: 10,
		
							onchange_callback: onchange_callback,
							init_instance_callback: function(instance) {
								zenarioA.enableDragDropUploadInTinyMCE(false, undefined, thus.get('row__' + (instance.editorId || instance.id)));
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
								"paste autoresize",
						        "colorpicker textcolor fullscreen"],
		
							image_advtab: true,
							visual_table_class: ' ',
							browser_spellcheck: true,
		
							paste_preprocess: zenarioA.tinyMCEPasteRreprocess,

							readonly: false,
						
							convert_urls: true,
							relative_urls: false,
		
							content_css: content_css,
							toolbar: 'undo redo | bold italic underline strikethrough forecolor backcolor | removeformat | fontsizeselect | formatselect | numlist bullist | blockquote outdent indent | code fullscreen',
							style_formats: zenarioA.skinDesc.style_formats,
							oninit: undefined
						}),
					optionsWithImagesAndLinks = _.extend({}, normalOptions, {
							toolbar: 'undo redo | image link unlink | bold italic underline strikethrough forecolor backcolor | removeformat | fontsizeselect | formatselect | numlist bullist | blockquote outdent indent | code fullscreen',
		
							file_browser_callback: zenarioA.fileBrowser,
							init_instance_callback: function(instance) {
								zenarioA.enableDragDropUploadInTinyMCE(true, URLBasePath, thus.get('row__' + (instance.editorId || instance.id)));
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
							toolbar: 'undo redo | image | bold italic underline strikethrough forecolor backcolor | removeformat | fontsizeselect | formatselect | numlist bullist | blockquote outdent indent | code fullscreen'
						}),
					optionsWithLinks = _.extend({}, optionsWithImages, {
							toolbar: 'undo redo | link unlink | bold italic underline strikethrough forecolor backcolor | removeformat | fontsizeselect | formatselect | numlist bullist | blockquote outdent indent | code fullscreen'
						});
				
				if (readOnly) {
					options = normalOptions;
					options.readonly = true;
					extraAtt['class'] = ' tinymce_readonly';
			
				} else {
					if (field.insert_image_button) {
						if (field.insert_link_button) {
							options = optionsWithImagesAndLinks;
							extraAtt['class'] = ' tinymce_with_images_and_links';
						} else {
							options = optionsWithImages;
							extraAtt['class'] = ' tinymce_with_images';
						}
					} else {
						if (field.insert_link_button) {
							options = optionsWithLinks;
							extraAtt['class'] = ' tinymce_with_links';
						} else {
							options = normalOptions;
							extraAtt['class'] = ' tinymce';
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
							thus.fieldChange(inst.id);
						});
				};
			
				cb.after(function() {
					var $field = $(thus.get(id)),
						domTab = thus.get('zenario_abtab'),
						tabDisplay = domTab.style.display;
					
					//TinyMCE can fail to load if there was already an editor on the page with the same name.
					//Attempt to try and tidy this up as a work-around
					try {
						$field.tinymce().remove();
					} catch (e) {
					}
				
					//Temporarily set the tab's display to be visible, even if an animation was hiding it.
					//This is a little hack to make sure this TinyMCE can get the correct width and height
					//of the textarea, even when it's not yet visible
					domTab.style.display = 'block';
				
					$field.tinymce(options);
				
					//Hide the tab again if it was hidden
					domTab.style.display = tabDisplay;
				});
				
				break;
			
			
			case 'button':
			case 'submit':
			case 'toggle':
				isButton = true;
				
				if (engToBoolean(field.use_button_tag)) {
					html += '<' + 'button';
					useButtonTag = true;
				} else {
					html += '<input';
					extraAtt.type = 'button';
				}
			
				if (!readOnly || engToBoolean(field.can_be_pressed_in_view_mode)) {
					extraAttAfter.onclick = "lib.clickButton(this, '" + id + "');";
					delete extraAtt.disabled;
				}
			
				if (fieldType == 'toggle') {
					extraAtt['class'] += field.pressed? ' pressed' : ' not_pressed';
				}
				
				break;
			
			
			default:
			//Various text fields
				if (fieldType == 'textarea') {
					html += '<textarea';
		
				} else if (isDatePicker) {
					addWidgetWrap = true;
					html += '<input';
					extraAtt.type = 'text';
					extraAtt['class'] += ' zenario_datepicker';
			
					extraAtt.readonly = 'readonly';
			
					if (!readOnly) {
						extraAtt.onkeyup =
							(extraAtt.onkeyup || '') +
							"zenario.dateFieldKeyUp(this, event, '" + htmlspecialchars(id) + "');";
					}
					
					cb.after(function() {
						var $field = $(thus.get(id)),
							changeMonthAndYear = !!engToBoolean(field.change_month_and_year);
						
						$field.datepicker({
							changeMonth: changeMonthAndYear,
							changeYear: changeMonthAndYear,
							dateFormat: (zenarioA.siteSettings && zenarioA.siteSettings.organizer_date_format) || zenario.dpf,
							altField: '#_value_for__' + id,
							altFormat: 'yy-mm-dd',
							showOn: 'focus',
							disabled: readOnly,
							onSelect: function(dateText, inst) {
								$field.change();
								//zenarioAB.fieldChange(this.name);
							}
						});
					});
		
				} else if (fieldType == 'url') {
					html += '<input';
					extraAtt.type = 'url';
					extraAtt.placeholder = 'http://example.com';
					extraAtt.onblur =
						(extraAtt.onblur || '') + 
						"if(this.value && !this.value.match('://') && $.trim(this.value)[0] != '#') this.value = 'http://' + this.value;";
		
				} else {
					if (field.slider) {
						hasSlider = true;
						html += thus.drawSlider(cb, id, field, readOnly, true);
					}
					
					if (fieldType == 'text' && field.values && !readOnly) {
						html += '<span class="zenario_textbox_with_autocomplete">';
						isTextFieldWithAutocomplete = true;
					}
			
					html += '<input';
					extraAtt.type = fieldType;
				}
				
				if (field.redraw_immediately_onchange) {
					extraAtt.onkeyup =
						(extraAtt.onkeyup || '') +
						"if (lib.redrawImmediatelyWhenChanged(this, event, '" + htmlspecialchars(id) + "', '" + htmlspecialchars(field.value) + "', " + (field.value == value? 'false' : 'true') + ")) return false;";
				}
			
				thus.addExtraAttsForTextFields(field, extraAtt);
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
			
	
			//If the indeterminate option is set in TUIX, set this property in the DOM after the html is drawn
			if (engToBoolean(field.indeterminate)) {
				cb.after(function() {
					var checkbox;
					if (checkbox = thus.get(id)) {
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
		
		//Add set the name and id
		if (!defined(lov)) {
			atts = field;
			overrides.id = id;
		} else {
			atts = $.extend({}, field, lovField);
			overrides.id = id + '___' + lov;
			extraAtt['class'] += ' control_for__' + id;
		}
		
		if (fieldType != 'radio' || !field.name) {
			overrides.name = id;
		}
		
		//Only allow placeholders if fields are editable
		if (readOnly) {
			overrides.placeholder = '';
		}
		
		
		html += thus.outputAtts(atts, extraAtt, extraAttAfter, overrides);
		
		var valAttribute = htmlspecialchars(isButton? field.value : value, false, 'asis'),
			emptyValue = '';
		
		
		//Add the value (which happens slightly differently for textareas)
		if (fieldType == 'select') {
			html += '>';
		
		} else if (useButtonTag) {
			html += '>';
				if (field.icon_left) {
					html += '<i class="' + htmlspecialchars(field.icon_left) + '" aria-hidden="true"></i>';
				}
			
				html += valAttribute;
			
				if (field.icon_right) {
					html += '<i class="' + htmlspecialchars(field.icon_right) + '" aria-hidden="true"></i>';
				}
			html += '</button>';
		
		} else if (fieldType == 'textarea' || fieldType == 'editor') {
			html += '>' + valAttribute + '</textarea>';
		
		} else if (fieldType == 'code_editor') {
			html += '>' + valAttribute + '</div>';
		
		} else if (isDatePicker) {
			html += ' value="' + htmlspecialchars(zenario.formatDate(value, fieldType == 'datetime')) + '"/>';
			html += _$input('type', 'hidden', 'id', '_value_for__' + id, 'value', value);
			
			if (!readOnly) {
				html += _$input('type', 'button', 'class', 'zenario_remove_date', 'value', 'x', 'onclick', thus.globalName + '.blankField("' + jsEscape(id) + '"); $(' + thus.globalName + '.get("' + jsEscape(id) + '")).change();');
			}
		
		} else if (defined(value)) {
			html += ' value="' + valAttribute + '"/>';
		
		} else {
			html += '/>';
		}
		
		if (fieldType == 'select') {
			if (field.empty_value) {
				emptyValue = _$option('value', '', 'data-hide_in_iconselectmenu', 1, 'selected', !value, htmlspecialchars(field.empty_value));
				
				if (!field.show_empty_value_at_end) {
					html += emptyValue;
					emptyValue = '';
				}
			}
			
			if (field.values) {
				picked_items = {};
				picked_items[value] = true;
				html += thus.hierarchicalSelect(picked_items, field, tabTUIX, sortOrder, parentsValuesExist, existingParents);
			}
			html += emptyValue + '</select>';
		
		}
		if (isTextFieldWithAutocomplete) {
			
			var i, v, source = [];
			
			foreach (sortOrder as i => v) {
				source.push({label: field.values[v], value: v});
			}
			
			cb.after(function() {
				var $field = $(thus.get(id)),
					options = {
						source: source,
						minLength: field.autocomplete_min_length || 0,
						appendTo: $field.parent()
					};
				
				//If the return_key_presses_button option is set for a field, also
				//honor this choice if someone selects something
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
			
			//▼▾ˑ
			html += _$input('type', 'button', 'class', 'zenario_open_autocomplete', 'value', '▾', 'onclick', '$(' + thus.globalName + '.get("' + jsEscape(id) + '")).autocomplete("search", "");');
			
		}
		if (isTextFieldWithAutocomplete) {
			html += '</span>';
		}
	}
	
	if (!defined(lov)) {
		
		if (fieldType == 'url' && !readOnly) {
			html +=
				'&nbsp; ' +
				_$input('type', 'button', 'class', 'submit', 'value', phrase.test, 'onclick', thus.globalName + '.testURL("' + jsEscape(id) + '");');
			
			addWidgetWrap = true;
		}
		
		//Checkbox/radio buttons always have their labels directly after their fields 
		if (field.label && (fieldType == 'checkbox' || fieldType == 'radio')) {
			html += ' ' + _$label('class', field.label_class, 'for', id, 'id', 'label_for__' + id, htmlspecialchars(field.label));
			addWidgetWrap = true;
		}
		//Other fields only have this if they specifically use the post_field_label property
		if (defined(field.post_field_label)) {
			html += ' ' + _$label('for', id, 'id', 'label_for__' + id, htmlspecialchars(field.post_field_label));
			addWidgetWrap = true;
		}
		
		
		if (hasPreFieldTags) {
			html = preFieldTags.join('') + html;
		}
		if (defined(field.pre_field_html)) {
			html = zenario.unfun(field.pre_field_html) + html;
		}
		
		if (addWidgetWrap) {
			html = _$span('class', 'zenario_field_widget_wrap', html);
		}
		
		
		
		if (defined(field.post_field_html)) {
			html += zenario.unfun(field.post_field_html);
		}
		
		if (field.tooltip) {
			html += _$div("class", "zenario_field_tooltip", "title", field.tooltip, '?');
		}
		
		if (hasPostFieldTags) {
			html += postFieldTags.join('');
		}
		
		if (hasSlider) {
			html += thus.drawSlider(cb, id, field, readOnly, false);
		}
	}
	
	return html;
};

methods.addExtraAttsForTextFields = function(field, extraAtt) {
};


//A list of allowed attributes for form fields
var allowedAtt = {
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
		'placeholder': true,
		'required': true,
		'step': true
	},
	//A list of allowed tags for snippets
	snippetTags = [
		'hr',
		'h1',
		'h2',
		'h3',
		'h4',
		'label',
		'div',
		'p',
		'span'
	];

methods.outputAtts = function(atts, extraAtt, extraAttAfter, overrides, allowEverything) {
	
	var att,
		html = '';
	
	foreach (atts as att) {
		if ((!overrides || !defined(overrides[att]))
		 && (allowEverything || allowedAtt[att] || att.substr(0, 5) == 'data-')) {
			if (att == 'disabled' || att == 'readonly' || att == 'selected') {
				if (engToBoolean(atts[att]) 
				 || (extraAtt && engToBoolean(extraAtt[att]) && (delete extraAtt[att]))) {
					html += ' ' + att + '="' + att + '"';
				}
			
			} else {
				html += ' ' + att + '="' + thus.defineLibVarBeforeCode(att);
				
				if (extraAtt && extraAtt[att]) {
					html += extraAtt[att] + ' ';
					delete extraAtt[att];
				}
				
				html += htmlspecialchars(atts[att]);
				
				if (extraAttAfter && extraAttAfter[att]) {
					html += ' ' + extraAttAfter[att];
					delete extraAttAfter[att];
				}
				
				html += '"';
			}
		}
	}
	
	if (overrides) {
		html += thus.outputAtts(overrides, false, false, false, true);
	}
	if (extraAtt) {
		html += thus.outputAtts(extraAtt, false, false, false, true);
	}
	if (extraAttAfter) {
		html += thus.outputAtts(extraAttAfter, false, false, false, true);
	}
	
	return html;
};

//Make sure thus the "lib" variable points towards this library
methods.defineLibVarBeforeCode = function(att) {
	
	//Attributes such as onclick/onchange/onkeyup/etc. always need the lib var defined,
	//but other attributes this aren't for JavaScript (e.g. id, class, value...) shouldn't have it.
	if (!defined(att) || att.substr(0, 2) == 'on') {
		
		//Catch the case where someone makes a library without a global name and then calls this function.
		//If this library doesn't have a global name, come up with one now.
		if (!thus.globalName) {
			for (var i = 1; window[thus.globalName = 'zenarioLib' + i]; ++i) {};
			window[globalName] = thus;
		}
		
		return 'var lib = ' + htmlspecialchars(thus.globalName) + '; '
	} else {
		return '';
	}
};


//Test a URL typed into a URL field by opening it
methods.testURL = function(id) {
	
	//Get the value of the field
	var link = thus.get(id).value,
		domTargetBlank = thus.get(thus.onThisRow('target_blank_', id)) || thus.get(thus.onThisRow('target_blank', id));
	
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




methods.hierarchicalSelect = function(picked_items, field, tabTUIX, sortOrder, parentsValuesExist, existingParents, parent) {
	
	var html = '',
		disabled,
		selected,
		showWithoutChildren,
		val, i, v;
	
	foreach (sortOrder as i => v) {
		val = field.values[v];
		disabled = false;
		selected = false;
		
		if (_.isString(val)) {
			val = {label: val};
		
		} else
		if (engToBoolean(val.disabled)
		 || (val.disabled_if
		  && zenarioT.eval(val.disabled_if, thus, val, undefined, v, undefined, undefined, field, undefined, tabTUIX))
		 || (field.values_disabled_if
		  && zenarioT.eval(field.values_disabled_if, thus, val, undefined, v, undefined, undefined, field, undefined, tabTUIX))) {
			//zenarioT.eval(c, lib, tuixObject, item, id, button, column, field, section, tab, tuix)
			disabled = true;
		}
		
		if (picked_items[v]) {
			selected = true;
		}
		
		showWithoutChildren = !engToBoolean(val.hide_when_children_are_not_visible);
		
		if (!defined(parent)
		 && parentsValuesExist
		 && existingParents[v]) {
			
			childrenHTML = thus.hierarchicalSelect(picked_items, field, tabTUIX, sortOrder, parentsValuesExist, existingParents, v);
			
			if (childrenHTML || showWithoutChildren) {
				html += _$html('optgroup', 'label', val, 'disabled', !childrenHTML, childrenHTML);
			}
		
		} else
		if (showWithoutChildren
		 && parent === val.parent) {
			
			//html += _$option('value', v, 'selected', selected, 'disabled', disabled, htmlspecialchars(val, false, true));
			
			html += '<option ' + thus.outputAtts(val, false, false, {disabled: disabled, selected: selected, value: v}) + '>' +
						htmlspecialchars(val, false, true) +
					'</option>';
		}
	}
	
	return html;
};


methods.setupMultipleSelect = function(field, id, tab, disable) {
	
	var changed = false,
		$field = $(thus.get(id));
	
	//https://github.com/nobleclem/jQuery-MultiSelect
	$field.multiselect({
		columns: field.cols,
		
		texts: {
			placeholder: field.empty_value || phrase.selectListSelect,
			overridePlaceholder: field.placeholder
		},
		
		onOptionClick: function() {
			changed = true;
		},
		onControlClose: function() {
			if (changed) {
				thus.fieldChange(id);
			}
		}
	});
	
	if (disable) {
		$field.multiselect('disable');
	}
};


methods.typeaheadSearchEnabled = function(field, id, tab) {
};

methods.typeaheadSearchAJAXURL = function(field, id, tab) {
};

methods.parseTypeaheadSearch = function(field, id, tab, readOnly, data) {
};

methods.setupPickedItems = function(field, id, tab, readOnly, multiple_select) {
	
	var noRecurse = false,
		searchURL,
		searchParam,
		$tokenize,
		pick_items = field.pick_items || {},
		upload = field.upload || {},
		allow_typing_anything = engToBoolean(pick_items.allow_typing_anything),
		reorder_items = engToBoolean(upload.reorder_items || pick_items.reorder_items);
	
	if (thus.typeaheadSearchEnabled(field, id, tab)) {
		if (searchURL = thus.typeaheadSearchAJAXURL(field, id, tab)) {
			searchParam = '_search';
		}
	}
	
	$tokenize = $(thus.get(id)).tokenize({
		
		datas: searchURL,
		searchParam: searchParam,
		
		//Leave 200ms between repeated AJAX requests
		debounce: 200,
		
		sortable: reorder_items,
		placeholder: pick_items.nothing_selected_phrase || upload.nothing_selected_phrase || phrase.nothing_selected,
		
		//If multiple select is not enabled, every time a value is added it should replace what is already there.
		//(Note this I don't want to use {maxElements: 1} to stop people selecting more than one because I want
		// them to still be able to replace what's there by typing in the box, so instead I'll call the
		// addToPickedItems() function which will auto-remove the previously selected value.)
		onAddToken: function(value, text, e) {
			if (noRecurse) {
				return;
			}
			
			if (!multiple_select) {
				noRecurse = true;
				thus.addToPickedItems(value, id, tab);
				noRecurse = false;
			}
			thus.$getPickItemsInput(id).focus();
		},
		maxElements: multiple_select? 0 : 1,
		
		parseData: function(data) {
			return thus.parseTypeaheadSearch(thus.tuix.tabs[tab].fields[id], id, tab, readOnly, data);
		},
		
		formatTokenHTML: function(valueId, text) {
			
			var field = thus.tuix.tabs[tab].fields[id];
			
			if (field.values
			 && field.values[valueId]) {
				return thus.drawPickedItem(valueId, id, field, readOnly);
			
			} else if (allow_typing_anything) {
				field.values = field.values || {};
				field.values[valueId] = text;
				return thus.drawPickedItem(valueId, id, field, readOnly);
			
			} else {
				return false;
			}
		},
		
		onRemoveToken: function(value, e) {
			thus.fieldChange(id);
		}
	});
	
	//Don't allow any changes if the field is in read-only mode
	if (readOnly) {
		$tokenize.disable();
	
	} else {
		
		if (reorder_items) {
			$tokenize.container.addClass('zenario_reorder_items');
		}
	
		//Don't allow anything to be removed if the hide_remove_button property is set
		//} else if (engToBoolean(pick_items.hide_remove_button)) {
		//	$tokenize.container.find('.Close').hide();
	
		//If there is no AJAX URL then no type-ahead is possible, so we need to disable it.
		//But we still need the field to look editable, and the "remove" button should still work!
		if (!searchURL && !allow_typing_anything) {
			$tokenize.disableTypeAhead();
	
		} else {
			$tokenize.container.addClass('zenario_picker_with_typeahead');
		
			//Catch the case where the user starts typing something then blurs the field
			if (allow_typing_anything) {
				thus.$getPickItemsInput(id).blur(function() {
					var value, obj;
					if (value = thus.$getPickItemsInput(id).val()) {
						obj = {};
						obj[value] = value;
						thus.addToPickedItems(obj, id, tab);
					}
				});
			}
		}
	}
	
	return;
};


methods.chooseFromDropbox = function(id) {
				
	var field,
		options,
		e, extension, extensions, split;

	if (!(field = thus.field(id))
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
	
	//Dropbox has a set format this it uses.
	//Attempt to automatically convert a few common things to the correct format
	foreach (extensions as e => extension) {
		
		//Look for expressions such as "image/*", and convert them into the dropbox equivalents
		split = extension.split('/');
		if (defined(split[1])) {
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
				cb.add(zenario.ajax(thus.ajaxURL() + '&fetchFromDropbox=1', file, true));
			}
			
			cb.after(function() {
				var i,
					file,
					field,
					values = '';
		
				if (!(field = thus.field(id))
				 || !(field.upload)) {
					return false;
				}
				
				foreach (arguments as i) {
					file = arguments[i];
					
					if (file && file.id) {
						values += ',' + file.id;
					}
					
					thus.setFileDetails(field, file);
				}
				
				if (values !== '') {
					thus.addToPickedItems(values, id);
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
	
	file.label = thus.formatLabelFromFile(file);
	field.values[file.id] = file;
};

methods.formatLabelFromFile = function(file) {
	var label;					
	
	//Format uploaded files - these are encoded, and in the form "checksum/filename/width/height"
	//We want to try and display the filename
	if (defined(file.filename)) {
		label = file.filename;
		
		if (file.ssc) {
			label += ' [checksum ' + file.short_checksum + ']';
		}

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
	
	var field, object;
	
	if (!(field = thus.field(id))
	 || !(field.upload)) {
		return false;
	}
	
	object = {
		class_name: field.class_name,
		upload: field.upload
	};
	
	thus.uploadCallback = function(responses) {
		if (responses) {
			var i,
				file,
				fileDetails,
				field,
				values = '';
		
			if (!(field = thus.field(id))
			 || !(field.upload)) {
				return false;
			}
			
			foreach (responses as i) {
				file = responses[i];
				
				if (file && file.id) {
					values += ',' + file.id;
					thus.setFileDetails(field, file);
				}
			}
			
			if (values !== '') {
				thus.addToPickedItems(values, id);
			}
		}
	};
	
	if (setUpDragDrop) {
		thus.enableDragDropUpload();
	} else {
		zenarioT.action(thus, object, undefined, undefined, undefined, {fileUpload: 1}, undefined, thus.ajaxURL());
	}
};

methods.ajaxURL = function() {
	return URLBasePath + 'zenario/ajax.php?method_call=handleAdminBoxAJAX&path=' + encodeURIComponent(thus.path);
};


methods.enableDragDropUpload = function() {
};

methods.disableDragDropUpload = function() {
};

methods.uploadComplete = function(responses) {
	if (defined(thus.uploadCallback)) {
		thus.uploadCallback(responses);
	}
	delete thus.uploadCallback;
};

methods.drawSlider = function(cb, id, field, readOnly, before) {
	var options = _.clone(field.slider),
		html = '';
	
	if (engToBoolean(options.before_field)? before : !before) {
		if (readOnly) {
			html +=
				'<div class="ui-disabled">';
		}
		
		html +=
			_$div('id', 'zenario_slider_for__' + id, 'class', options['class'], 'style', options.style);
		
		if (readOnly) {
			html +=
				'</div>';
		}
		
		//Set up the slider after the html is drawn
		cb.after(function() {
			var domSlider;
			if (domSlider = thus.get('zenario_slider_for__' + id)) {
				
				if (defined(options.min)) options.min = zenario.num(options.min);
				if (defined(options.max)) options.max = zenario.num(options.max);
				if (defined(options.step)) options.step = zenario.num(options.step);
				
				options.disabled = !thus.editModeOn();
				options.value = $(thus.get(id)).val();
				options.slide =
					function(event, ui) {
						$(thus.get(id)).val(ui.value);
					};
				
				options.change = function(event, ui) {
					thus.fieldChange(id);
				};
				
				$(domSlider).slider(options);
			}
		});
	}
	
	return html;
};

methods.lookupFileDetails = function(fileId) {
	return zenarioA.lookupFileDetails(fileId);
};

methods.pickedItemsArray = function(field, value) {
	
	if (value === false || !defined(value)) {
		return {};
	}
	
	//I need to repeat the check for LOVs here, just in case initFields()
	//hasn't been called (e.g. the FEA toolkit doesn't call it).
	if (field.values
	 && _.isString(field.values)
	 && thus.tuix.lovs
	 && thus.tuix.lovs[field.values]) {
		field.values = thus.tuix.lovs[field.values];
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
			 && (panel = zenarioA.getItemFromOrganizer(path, i))
			) {
				if (!field.values) {
					field.values = {};
				}
				
				label = zenarioA.formatOrganizerItemName(panel, i);
				item = panel.items && panel.items[i] || {missing: true};
				
				picked_items[i] = label;
				field.values[i] = {
					missing: item.missing,
					list_image: item.list_image,
					css_class: item.css_class || (panel.item && panel.item.css_class),
					label: label
				};
			
			//If an id was set but no label, and this is an upload field,
			//then attempt to look up the filename
			} else
			if (field.upload
			 && (i == 1 * i)
			 && (file = thus.lookupFileDetails(i))) {
				
				thus.setFileDetails(field, file);
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

methods.displaySelectedItems = function(id, field, value, tab) {
	if (!defined(field)) {
		field = thus.field(id, tab);
	}
	if (!defined(value)) {
		value = thus.value(id, tab);
	}
	
	var pickedItems = thus.pickedItemsArray(field, value);
	
	if (_.isEmpty(pickedItems)) {
		if (engToBoolean(value)) {
			return value;
		
		} else if (defined(field.empty_value)) {
			return field.empty_value;
		
		} else {
			return '';
		}
	
	} else {
		return _.toArray(pickedItems).join(', ');
	}
};

//Draw hierarchical checkboxes or radiogroups
methods.hierarchicalBoxes = function(cb, tab, id, value, field, thisField, readOnly, picked_items, sortOrder, existingParents, parent, parents, level) {
	
	var cols = 1*field.cols || 1,
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
		cols = 1*field.cols_at_top_level || cols;
	
	} else if (level > 10) {
		return '';
	}
	
	//Create a list of ids this have children, so we don't waste time looping through looking for children for checkboxes which have none
	//if (!defined(existingParents)) {
	//	existingParents = {};
	//	
	//	foreach (field.values as var v) {
	//		if (typeof field.values[v] == 'object' && field.values[v].parent) {
	//			existingParents[field.values[v].parent] = true;
	//		}
	//	}
	//}
	
	//Set up a list of parents this the current level of items will have
	if (!parents) {
		parents = {};
	}
	if (parent) {
		parents[parent] = true;
	}
	
	
	if (level) {
		html += _$div('class', 'zenario_hierarchical_box_children', 'id', 'children_for___' + id + '___' + parent, '>');
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
			} else if (defined(picked_items[v])) {
				isSelected = true;
			} else {
				isSelected = false;
			}
			
			if (!readOnly
			 || isSelected
			 || !field.hide_unselected_values_when_readonly
			 || existingParents[v]) {
			
				m.checked = isSelected;
			
				if (m.newRow = (++col > cols)) {
					col = 1;
				}
				m.col = col;
				m.cols = cols;
				
				//Include logic for checking parents/unchecking children on selection/deselection of checkboxes
				if (field.type == 'checkboxes' && engToBoolean(field.checking_child_checks_parents)) {
					var onchange = '';
			
					//Include logic for checking parents/unchecking children on selection/deselection of checkboxes
					if (parent) {
						onchange += "if (this.checked) { for (var cb in " + JSON.stringify(parents) + ") { " + thus.globalName + ".get('" + htmlspecialchars(id) + "___' + cb).checked = true; } } "
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
							if (checkbox = thus.get(id)) {
								checkbox.indeterminate = true;
							}
						});
					})(m.lovId);
				}
				
				//drawField(cb, tab, id, field, visibleFieldsOnIndent, hiddenFieldsByIndent, fieldValuesByIndent, scanForHiddenFieldsWithoutDrawingThem, groupingIsHidden, lov, value, readOnly, sortOrder, existingParents, lovField)
				m.lovHTML = thus.drawField(cb, tab, id, thisField, undefined, undefined, undefined, undefined, undefined, v, isSelected, false, sortOrder, existingParents, lovField);
				
				
				m.childrenHTML = '';
				if (existingParents[v]) {
					m.childrenHTML = thus.hierarchicalBoxes(cb, tab, id, value, field, thisField, readOnly, picked_items, sortOrder, existingParents, v, _.extend({}, parents), level + 1);
					col = 0;
				}
			
			
				if (splitValuesAfterSelected) {
					if (!thus.splitValues) {
						thus.splitValues = {};
					}
					if (!thus.splitValues[id]) {
						thus.splitValues[id] = '';
					}
					thus.splitValues[id] += thus.microTemplate(thus.mtPrefix + '_radio_or_checkbox', m);; 
			
				} else {
					html += thus.microTemplate(thus.mtPrefix + '_radio_or_checkbox', m);; 
				}
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
methods.drawPickedItem = function(item, id, field, readOnly, inDropDown) {
	
	if (!defined(field)) {
		field = thus.field(id);
	}
	
	var c,
		file
		label = field.values && field.values[item],
		pick_items = field.pick_items || {},
		thumbnail = {},
		mi = {};
	
	if (_.isObject(label)) {
		_.extend(mi, label);
	
	} else if (label) {
		mi.label = label;
	
	} else {
		mi.label = item;
		mi.missing = true;
	}
	
	mi.id = id;
	mi.item = item;
	mi.readOnly = readOnly;
	
	
	if (mi.width
	 && mi.height
	 && (c = mi.checksum || mi.short_checksum)) {
		thumbnail.src = URLBasePath + 'zenario/file.php?c=' + encodeURIComponent(c) + '&og=1';
	
		if (mi.usage) {
			thumbnail.src += '&usage=' + encodeURIComponent(mi.usage);
		}
		
		//Attempt to get the width and height from the label, and work out the correct
		//width and height for the thumbnail.
		//(The max is 180 by 120; this is the size of Organizer thumbnails and
		// is also set in zenario/file.php)
		zenarioT.resizeImage(mi.width, mi.height, 180, 120, thumbnail);
		
		mi.thumbnail = thumbnail;
	}
	
	if (inDropDown) {
		return thus.microTemplate(pick_items.dropdown_item_microtemplate || thus.mtPrefix + '_dropdown_item', mi);
	} else {
		return thus.microTemplate(pick_items.picked_item_microtemplate || thus.mtPrefix + '_picked_item', mi);
	}
};


methods.showPickedItemInPopout = function(href, title) {
	zenarioT.action(thus, {popout: {href: href, title: title, css_class: 'zenario_show_colobox_above_fab'}});
};

methods.pickItems = function(id) {
	
	var field,
		pick_items,
		selectedItem,
		path,
		tagPath;
	
	if ((field = thus.field(id))
	 && (pick_items = field.pick_items)
	 && (pick_items.path || pick_items.target_path)) {
	
		path = tagPath = pick_items.path;
	
		if (zenarioO.map) {
			tagPath = zenarioO.convertNavPathToTagPath(pick_items.path).path;
		}
		
		//Attempt to pre-select the currently selected item
		if (!engToBoolean(pick_items.multiple_select)
		 && (!pick_items.target_path || pick_items.target_path == tagPath)
		 && (selectedItem = thus.readField(id))
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
		
	
		thus.SKTarget = id;
		zenarioA.organizerSelect(thus.globalName, 'setPickedItems', pick_items.multiple_select,
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
	var id = thus.SKTarget,
		i, eni, item,
		values = {};
	
	foreach (key._items as eni) {
		i = zenario.decodeItemIdForOrganizer(eni);
		item = panel.items && panel.items[i] || {missing: true};
		
		values[i] = {
			missing: item.missing,
			list_image: item.list_image,
			css_class: item.css_class || (panel.item && panel.item.css_class),
			label: zenarioA.formatOrganizerItemName(panel, i)
		};
	}
	
	thus.addToPickedItems(values, id);
};

methods.removeFromPickedItems = function(values, id, tab) {
	thus.addToPickedItems(values, id, tab, true);
};

methods.addToPickedItems = function(values, id, tab, remove) {
	
	var field = thus.field(id, tab),
		current_value = thus.readField(id),	//(!defined(field.current_value)? field.value : field.current_value),
		multiple_select = (field.pick_items && engToBoolean(field.pick_items.multiple_select)) || (field.upload && engToBoolean(field.upload.multi)),
		i, value, display, arrayOfValues, picked_items,
		reselectedItems = {};
	
	values = zenarioT.csvToObject(values);
	
	if (current_value) {
		picked_items = thus.pickedItemsArray(field, current_value);
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
			
			if (picked_items[value]) {
				reselectedItems[value] = true;
			}
			
			if (multiple_select) {
				picked_items[value] = true;
			} else {
				picked_items = {};
				picked_items[value] = true;
				break;
			}
		}
	}
	
	thus.redrawPickedItems(id, field, picked_items, reselectedItems);
};

methods.redrawPickedItems = function(id, field, picked_items, reselectedItems) {
	
	var i, item, label,
		value = thus.pickedItemsValue(picked_items),
		currently_picked_items = {},
		$tokenize = $(thus.get(id)).tokenize(),
		items = $tokenize.toArray();
	
	picked_items = zenarioT.csvToObject(picked_items);
	
	foreach (items as i => item) {
		if (picked_items[item]
		 && !reselectedItems[item]) {
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
	thus.fieldChange(id);
};

methods.changes = function(tab) {
	if (!thus.tuix || !thus.tuix.tab) {
		return false;
	
	} else if (!defined(tab)) {
		foreach (thus.tuix.tabs as tab) {
			if (thus.changed[tab]) {
				return true;
			}
		}
		
		return false;
	
	} else {
		return thus.changed[tab];
	}
};

methods.markAsChanged = function(tab) {
	if (!thus.tuix) {
		return;
	}
	
	if (!defined(tab)) {
		tab = thus.tuix.tab;
	}
	
	if (!tab) {
		return;
	}
	
	if (!thus.changed[tab]) {
		thus.changed[tab] = true;
		
		if (thus.tuix.tab == tab) {
			$('#zenario_abtab').addClass('zenario_abtab_changed');
		}
	}
	
};

methods.fieldChange = function(id, lov) {
	
	var field = thus.field(id);
	
	if (!field) {
		return;
	}
	
	if (field.indeterminate) {
		field.indeterminate = false;
	}
	
	if (defined(lov)
	 && field.indeterminates
	 && field.indeterminates[lov]) {
		field.indeterminates[lov] = false;
	}
	
	
	thus.markAsChanged();
	
	if (engToBoolean(field.multiple_edit)) {
		thus.meMarkChanged(id);
	}
	
	//If a field has changed, check whether we need to redraw, format or validate the FAB.
	//However, if this was done immediately it would mess up people's tab-switching, as the fields
	//would be destroyed mid-tab-select.
	//I'm using setTimeout() as little hack to allow the tab switching to finish first.
	setTimeout(function() {
		thus.validateFormatOrRedrawForField(field);
	}, 1);
};

methods.validateFormatOrRedrawForField = function(field, isToggleButton) {
	
	if (typeof field == 'string') {
		thus.fieldThatTriggeredRedraw = thus.setLastFocus(field);
		field = thus.field(field);
	
	} else if (field.id) {
		thus.fieldThatTriggeredRedraw = thus.setLastFocus(field.id);
	}
	
	var validate = engToBoolean(field.validate_onchange),
		format = engToBoolean(field.format_onchange) || isToggleButton && (field.pressed? field.format_on_toggle_on : field.format_on_toggle_off),
		redraw = engToBoolean(field.redraw_onchange);
	validate = validate;

		
	if (validate) {
		if (thus.ffoving < 3) {
			thus.ffoving = 3;
			thus.validate();
		}
		return true;
	
	} else if (format) {
		if (thus.ffoving < 2) {
			thus.ffoving = 2;
			thus.format();
		}
		return true;
	
	} else {
		if (redraw) {
			if (thus.ffoving < 1) {
				thus.ffoving = 1;
				thus.readTab();
				thus.redrawTab();
			}
		}
		
		return redraw;
	}
};

methods.redrawImmediatelyWhenChanged = function(el, event, id, originalValue, back) {
	
	var changed;
	
	if (back) {
		changed = el.value === originalValue;
	} else {
		changed = el.value !== originalValue;
	}
	
	if (changed) {
		
		zenario.stop(event);
		
		thus.fieldThatTriggeredRedraw = thus.setLastFocus(id);
	
		if (thus.ffoving < 1) {
			thus.ffoving = 1;
			thus.readTab();
			thus.redrawTab();
		}
	}
	
	return changed;
};

methods.meMarkChanged = function(id, current_value, value) {
	
	if (defined(current_value)) {
		if (current_value == value) {
			return;
		}
	}
	
	thus.field(id).multiple_edit._changed = true;
	thus.meSetCheckbox(id, true);
};

methods.meSetCheckbox = function(id, changed) {
	if (thus.get('multiple_edit__' + id).type == 'checkbox') {
		thus.get('multiple_edit__' + id).checked = changed;
	} else {
		thus.get('multiple_edit__' + id).value = changed? 1 : '';
	}
};

//The admin changes a multiple-edit checkbox
methods.meChange = function(changed, id, confirm) {
	
	var field = thus.field(id);
	
	//Update its state in the schema
	if (changed) {
		field.multiple_edit._changed = true;
	} else {
		
		//Require a confirm prompt if this will lose any changes
		if (!confirm
		 && engToBoolean(field.multiple_edit.warn_when_abandoning_changes)
		 && defined(field.multiple_edit.original_value)
		 && thus.readField(id) != field.multiple_edit.original_value) {
		 	
			var buttonsHTML =
				_$input('type', 'button', 'class', 'submit_selected', 'value', phrase.abandonChanges, 'onclick', thus.globalName + '.meChange(false, "' + jsEscape(id) + '", true);') + 
				_$input('type', 'button', 'class', 'submit', 'value', phrase.cancel);
			
			zenarioA.floatingBox(phrase.abandonChangesConfirm, buttonsHTML, 'warning');
			thus.meSetCheckbox(id, true);
			return;
		}
		
		field.multiple_edit._changed = false;
		
		//If it is now off, revert the field's value back to the default.
		delete field.current_value;
		
		var value = field.value;
		if (defined(field.multiple_edit.original_value)) {
			value =
			field.current_value =
			field.multiple_edit.original_value;
		}
		
		if (thus.get(id) && !field.pick_items) {
			if (thus.get(id).type == 'checkbox') {
				thus.get(id).checked = value? true : false;
			} else {
				$(thus.get(id)).val(value || '');
			}
		} else {
			//Some non-standard fields - i.e. fields this couldn't be changed using $().val() - will need a complete redraw of the tab to achieve
			var cb = new zenario.callback,
				html = thus.drawFields(cb);
			thus.insertHTML(html, cb);
		}
	}
	
	thus.meSetCheckbox(id, changed);
	
	thus.validateFormatOrRedrawForField(id);
};


methods.currentValue = function(f, tab, readOnly) {
	
	if (!readOnly
	 && !thus.drawingFields
	 && (!defined(tab)
	  || tab == thus.tuix.tab)) {
		return thus.readField(f);
	} else {
		return thus.value(f, tab, readOnly);
	}
};

//Get the value of a field
methods.value = function(f, tab, readOnly) {
	if (!tab) {
		tab = thus.tuix.tab;
	}
	
	var value = '',
		first = true,
		field = thus.field(f, tab);
	
	if (!field) {
		return '';
	}
	
	if (!defined(readOnly)) {
		readOnly = thus.fieldIsReadonly(f, field, tab);
	}
	
	if (!field
	 || field.snippet
	 || field.type == 'grouping') {
	
	} else if (thus.isButton(field)) {
		value = !!field.pressed;
	
	} else if (!readOnly && defined(field.current_value)) {
		value = field.current_value;
	
	} else if (defined(field.value)) {
		value = field.value;
	
	} else if (field.multiple_edit && defined(field.multiple_edit.original_value)) {
		value = field.multiple_edit.original_value;
	}
	
	//Sometimes numbers might be returned as strings.
	//This is usually fine, except for '0', which evaluates to true when we want it to evaluate to false,
	//so correct this here!
	if (value === '0') {
		return 0;
	
	//Return an empty string rather than undefined or null
	} else if (!defined(value)) {
		return '';
	
	} else {
		return value;
	}
};

methods.mode = function(k) {
	return thus.value('mode');
};

methods.keyIn = function(k) {
	return _.contains(arguments, thus.tuix.key[k], 1);
};

methods.keyIs = function(k, v) {
	return thus.tuix.key[k] == v;
};

methods.key = function(k) {
	return thus.tuix.key[k];
};

methods.modeIn = function() {
	return _.contains(arguments, thus.mode());
};

methods.modeIs = function(m) {
	return thus.mode() == m;
};

methods.valueIn = function(f) {
	return _.contains(arguments, thus.value(f), 1);
};

methods.valueIs = function(f, v) {
	return thus.value(f) == v;
};

methods.valueIsNot = function(f, v) {
	return thus.value(f) != v;
};

methods.isButton = function(field) {
	return field && (field.type == 'submit' || field.type == 'toggle' || field.type == 'button');
};

methods.isFormField = function(field) {
	return !(!field
			|| field.snippet
			|| field.type == 'grouping'
			|| field.type == 'submit'
			|| field.type == 'toggle'
			|| field.type == 'button');
};


methods.getCodeEditorPosition = function(codeEditor) {
	return {
		top: codeEditor.session.getScrollTop(),
		left: codeEditor.session.getScrollLeft(),
		range: codeEditor.getSelectionRange()
	};
};

methods.setCodeEditorPosition = function(codeEditor, editingPositions) {
	if (editingPositions) {
		codeEditor.session.setScrollTop(editingPositions.top);
		codeEditor.session.setScrollLeft(editingPositions.left);
		codeEditor.selection.setSelectionRange(editingPositions.range);
	}
};

methods.appendCodeEditorValue = function(editorId, value) {
	
	var codeEditor = ace.edit(editorId),
		pos = thus.getCodeEditorPosition(codeEditor);
	
	codeEditor.setValue(codeEditor.getValue() + value);
	
	thus.setCodeEditorPosition(codeEditor, pos);
};



methods.readField = function(f) {
	var value = undefined,
		tab = thus.tuix.tab,
		field = thus.field(f, tab),
		el;
	
	//Non-field types
	if (!thus.isFormField(field)) {
		return undefined;
	}
	
	var readOnly = thus.fieldIsReadonly(f, field, tab),
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
				if (thus.get(f + '___' + v) && thus.get(f + '___' + v).checked) {
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
		if (readOnly || !defined(field.current_value)) {
			return field.value;
		} else {
			return field.current_value;
		}
	}
	

	//Fields with seperate values to display values
	if (thus.get('_value_for__' + f)) {
		field.current_value = value = thus.get('_value_for__' + f).value;
	
	//Editors
	} else if ((field.type == 'editor' || field.type == 'code_editor') && !readOnly) {
		
		var content,
			editor,
			codeEditor;
		
		if (field.type == 'editor') {
			if (editor = window.tinyMCE && tinyMCE.get(f)) {
				content = zenario.tinyMCEGetContent(editor);
			}
		
		} else if (field.type == 'code_editor') {
			if (codeEditor = ace.edit(f)) {
				content = codeEditor.getValue();
				
				thus.editingPositions[tab + '/' + f] = thus.getCodeEditorPosition(codeEditor);
				
				//I've been experiementing with trying to save the undo history, but can't get it working :(
				//var undoManager;
				//if (undoManager = codeEditor.session.getUndoManager()) {
				//	this.editingHistory[tab + '/' + f] = undoManager;
				//	//this.editingHistory[tab + '/' + f] = {
				//	//	$doc: undoManager.$doc,
				//	//	$redoStack: undoManager.$redoStack,
				//	//	$undoStack: undoManager.$undoStack,
				//	//	dirtyCounter: undoManager.dirtyCounter
				//	//};
				//}
			}
		}
		
		if (defined(content) && content !== false) {
			value = field.current_value = content;
		
		//If due to some bug we couldn't get the content from the editor,
		//return the stored value and don't do any further manipulations to the data model.
		} else if (!defined(field.current_value)) {
			return field.value;
		} else {
			return field.current_value;
		}
	
	//Normal fields
	} else {
		if (!readOnly && (el = thus.get(f))) {
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
			
			//Don't allow null/undefined values from jQuery; convert these into an empty string.
			if (!defined(value)) {
				value = '';
			}
			//Don't allow arrays from jQuery; convert these into CSV
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
	thus.setFieldValue(f, '');
};

methods.setFieldValue = function(f, val) {
	var tab = thus.tuix.tab
		field = thus.field(f, tab);
	
	if (thus.get(f)) {
		$(thus.get(f)).val(val);
	}
	
	if (thus.get('_value_for__' + f)) {
		thus.get('_value_for__' + f).value = val;
	}
	
	field.current_value = val;
};


//this.lastScrollTop = undefined;

methods.readTab = function() {
	var value,
		values = {},
		fields = thus.fields(),
		f;
	
	thus.lastScrollTop = $('#zenario_fbAdminInner').scrollTop();
	
	foreach (fields as f) {
		if (defined(value = thus.readField(f))) {
			values[f] = value;
		}
	}
	
	if (document
	 && document.activeElement) {
		thus.lastFocus = thus.setLastFocus(document.activeElement);
	}
	
	return values;
};

methods.wipeTab = function() {
	var tab = thus.tuix.tab,
		value,
		values = {},
		fields = thus.fields(),
		f, field;
	
	foreach (fields as f => field) {
		delete field.current_value;
		
		if (field.multiple_edit) {
			delete field.multiple_edit._changed;
		}
	}
};

methods.redrawTab = function() {
	thus.tuix.shake = false;
	thus.draw2();
};









//Get a URL needed for an AJAX request
methods.getURL = function(action) {
	//Outdate any validation attempts
	++thus.onKeyUpNum;
	
	return thus.returnAJAXURL(action);
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
	thus.sortedTabs = [];
	if (thus.tuix.tabs) {
		foreach (thus.tuix.tabs as var i => var thisTab) {
			if (thisTab) {
				thus.sortedTabs.push([i, thisTab.ord]);
			}
		}
	}
	
	//Sort this array
	thus.sortedTabs.sort(zenarioT.sortArray);
	
	thus.groupings = {};
	thus.sortedFields = {};
	
	foreach (thus.sortedTabs as var i) {
		var tab = thus.sortedTabs[i] = thus.sortedTabs[i][0];
		thus.sortFields(tab);
		//this.sortedTabOrders[tab] = i;
	}
};

methods.sortFields = function(tab) {
	
	var i, field, fields, groupingOrd,
		groupingOrds = {};
	
	//Build an array to sort, containing:
		//0: The item's actual index
		//1: The value to sort by
	thus.groupings[tab] = {};
	thus.sortedFields[tab] = [];
	if (fields = thus.fields(tab)) {
		
		//Look for groupings among the fields.
		//Groupings work like placeholders; the fields in the grouping should all have
		//the position of the placeholder.
		foreach (fields as i => field) {
			if (field.type
			 && field.type == 'grouping') {
				groupingOrds[field.name || i] = 1*field.ord;
				thus.groupings[tab][field.name || i] = i;
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
				
					thus.sortedFields[tab].push([i, field.ord, groupingOrd]);
				}
			}
		}
	
		//Sort this array
		thus.sortedFields[tab].sort(zenarioT.sortArrayWithGrouping);
	
		//Remove fields this were just there to help sort
		foreach (thus.sortedFields[tab] as i) {
			thus.sortedFields[tab][i] = thus.sortedFields[tab][i][0];
		}
	}
};







methods.setData = function(data) {
	thus.tuix = data;
};
 

methods.setDataDiff = function(data) {
	thus.syncAdminBoxFromServerToClient(data, thus.tuix);
};

//Sync updates from the server to the array stored on the client
methods.syncAdminBoxFromServerToClient = function($serverTags, $clientTags) {
	
	var $key, $val;
	
	foreach ($serverTags as $key => $val) {
		if (!defined($val) || $val['[[__unset__]]']) {
			delete $clientTags[$key];
		
		} else
		if (!defined($clientTags[$key])) {
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
			thus.syncAdminBoxFromServerToClient($val, $clientTags[$key]);
		}
	}
};



methods.sendStateToServer = function() {
	return JSON.stringify(thus.tuix);
};

methods.sendStateToServerDiff = function() {
	var $serverTags = {};
	thus.syncAdminBoxFromClientToServerR($serverTags, thus.tuix);
	
	return JSON.stringify($serverTags);
};

//Sync updates from the client to the array stored on the server
methods.syncAdminBoxFromClientToServerR = function($serverTags, $clientTags, $key1, $key2, $key3, $key4, $key5, $key6) {
	
	if ('object' != typeof $clientTags) {
		return;
	}
	
	var $type, $key0, to, val;
	
	for ($key0 in $clientTags) {
		//Only allow certain tags in certain places to be merged in
		if (
			(!defined($key1) && zenario.IN($key0, 'download', 'path', 'shake', 'tab', 'switchToTab') && ($type = 'value'))
		 || (!defined($key1) && zenario.IN($key0, '_sync', 'tabs') && ($type = 'array'))
			 || (!defined($key2) && $key1 == '_sync' && zenario.IN($key0, 'cache_dir', 'password', 'session', 'iv') && ($type = 'value'))
			 || (!defined($key2) && $key1 == 'tabs' && ($type = 'array'))
				 || (!defined($key3) && $key2 == 'tabs' && $key0 == '_was_hidden_before' && ($type = 'value'))
				 || (!defined($key3) && $key2 == 'tabs' && zenario.IN($key0, 'edit_mode', 'fields') && ($type = 'array'))
					 || (!defined($key4) && $key3 == 'tabs' && $key1 == 'edit_mode' && $key0 == 'on' && ($type = 'value'))
					 || (!defined($key4) && $key3 == 'tabs' && $key1 == 'fields' && ($type = 'array'))
						 || (!defined($key5) && $key4 == 'tabs' && $key2 == 'fields' && zenario.IN($key0, '_display_value', '_was_hidden_before', 'current_value', 'pressed') && ($type = 'value'))
						 || (!defined($key5) && $key4 == 'tabs' && $key2 == 'fields' && $key0 == 'multiple_edit' && ($type = 'array'))
							 || (!defined($key6) && $key5 == 'tabs' && $key3 == 'fields' && $key1 == 'multiple_edit' && $key0 == '_changed' && ($type = 'value'))
		) {
			
			//Update any values from the client on the server's copy
			if ($type == 'value') {
				val = $clientTags[$key0];
				switch (typeof val) {
					case 'function':
					case 'object':
						//Ignore any objects if we were expecting just a simple variable
						break;
					case 'string':
						val = zenario.encodeItemIdForOrganizer(val);
						//N.b. Cloudflare sometimes blocks the values of strings in JSON objects, e.g. if it sees HTML code in them.
						//We're attempting to work around this by calling encodeItemIdForOrganizer() to mask any HTML.
					default:
						$serverTags[$key0] = val;
				}
			
			//For arrays, check them recursively
			} else if ($type == 'array') {
				if ('object' == typeof $clientTags[$key0]) {
					$serverTags[$key0] = {};
					thus.syncAdminBoxFromClientToServerR($serverTags[$key0], $clientTags[$key0], $key0, $key1, $key2, $key3, $key4, $key5);
				}
			}
		}
	}
};











methods.getValueArrayofArrays = function(leaveAsJSONString) {
	return zenario.nonAsyncAJAX(thus.getURL(), zenario.urlRequest({_read_values: true, _box: thus.sendStateToServer()}), !leaveAsJSONString);
};

methods.getValues1D = function(pluginSettingsOnly, useTabNames, getInitialValues, ignoreReadonlyFields, ignoreHiddenFields) {
	
	var t, tab, f, field, name, value, values = {};
	
	if (thus.tuix
	 && thus.tuix.tabs) {
		foreach (thus.tuix.tabs as t => tab) {
			if (tab.fields) {
				foreach (tab.fields as f => field) {
					
					name = f;
					
					if (!thus.isFormField(field)
					 || (ignoreReadonlyFields && thus.fieldIsReadonly(f, field, t))
					 || (ignoreHiddenFields && (tab._was_hidden_before || field._was_hidden_before))
					 || (pluginSettingsOnly && !(name = field.plugin_setting && field.plugin_setting.name))) {
						continue;
					}
					
					value = thus.currentValue(f, t, getInitialValues);
					
					if (!useTabNames) {
						values[name] = value;
					}
					if (useTabNames || !defined(useTabNames)) {
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
		el = thus.get(el);
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
	
	if (window.tinyMCE && (fields = thus.fields())) {
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
	if (!thus.loaded || !(url = thus.getURL('format'))) {
		return;
	}
	
	var currentTab = thus.tuix && thus.tuix.tab;
	
	thus.loaded = false;
	thus.hideTab();
	
	thus.checkValues(wipeValues);
	
	thus.retryAJAX(
		url,
		{_format: true, _box: thus.sendStateToServer()},
		true,
		function(data) {
			thus.load(data);
			thus.switchTabIfRequested(currentTab);
			thus.sortTabs();
			thus.draw();
		},
		'loading'
	);
};

methods.validate = function(differentTab, tab, wipeValues, callBack) {
	var url;
	if (!thus.loaded || !(url = thus.getURL('validate'))) {
		return;
	}
	
	thus.differentTab = differentTab;
	thus.loaded = false;
	thus.hideTab(differentTab);
	
	thus.checkValues(wipeValues);
	
	var currentTab = thus.tuix && thus.tuix.tab;
	
	if (differentTab) {
		thus.tuix.switchToTab = tab;
	}
	
	thus.retryAJAX(
		url,
		{_validate: true, _box: thus.sendStateToServer()},
		true,
		function(data) {
			if (thus.load(data)) {
				thus.switchTabIfRequested(currentTab);
			}
	
			thus.sortTabs();
			thus.switchToATabWithErrors();
			thus.draw();
	
			if (callBack) {
				callBack();
			}
		},
		'loading'
	);
};

methods.switchTabIfRequested = function(currentTab) {
	//Switch to another tab if requested
	if (thus.tuix.switchToTab) {
		
		currentTab = currentTab || thus.tuix && thus.tuix.tab;
		
		//Don't switch to another tab if there were errors on the current tab
		if (thus.errorOnTab(currentTab)) {
			thus.tuix.tab = currentTab;
		} else {
			thus.tuix.tab = thus.tuix.switchToTab;
		}
		
		delete thus.tuix.switchToTab;
	}
};






});
}, zenarioF);
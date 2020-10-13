/*
 * Copyright (c) 2020, Tribal Limited
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
	panelTypes.form_builder_base_class = extensionOf(panelTypes.base)
);

//Don't show the "where was that thing" search box when a form builder is open
methods.returnShowWWTTSearch = function() {
	return false;
};


//Misc

methods.getOrderedPages = function() {
	var orderedPages = [];
	foreach (thus.tuix.pages as var pageId => var page) {
		var pageClone = _.clone(page);
		orderedPages.push(pageClone);
	}
	orderedPages.sort(thus.sortByOrd);
	return orderedPages;
};

methods.getOrderedFields = function(pageId) {
	var orderedFields = [];
	
	//Fields on a page
	if (pageId) {
		foreach (thus.tuix.pages[pageId].fields as var fieldId => var x) {
			var fieldClone = _.clone(thus.tuix.items[fieldId]);
			orderedFields.push(fieldClone);
		}
	//All fields
	} else {
		var pages = thus.getOrderedPages();
		for (var i = 0; i < pages.length; i++) {
			var orderedFields = orderedFields.concat(thus.getOrderedFields(pages[i].id));
		}
	}
	orderedFields.sort(thus.sortByOrd);
	
	return orderedFields;
};

methods.getOrderedFieldValues = function(fieldId) {
	var orderedValues = [];
	if (thus.tuix.items[fieldId].lov) {
		foreach (thus.tuix.items[fieldId].lov as var valueId => var value) {
			var valueClone = _.clone(value);
			valueClone.id = valueId;
			orderedValues.push(valueClone);
		}
		orderedValues.sort(thus.sortByOrd);
	}
	return orderedValues;
};

methods.sortByOrd = function(a, b) {
	if (a.ord < b.ord) 
		return -1;
	if (a.ord > b.ord)
		return 1;
	return 0;
};

methods.getMatchingRepeatEnd = function(fieldId) {
	var repeatEndId = false,
		fieldIndex = false,
		pageId = thus.currentPageId,
		fields = thus.getOrderedFields(pageId),
		i;
	for (i = 0; i < fields.length; i++) {
		if (fields[i].type == 'repeat_end') {
			repeatEndId = fields[i].id;
		}
		if (fields[i].id == fieldId) {
			fieldIndex = i;
		} else if (fieldIndex !== false && fields[i].type == 'repeat_end') {
			repeatEndId = fields[i].id;
			break;
		}
	}
	return repeatEndId;
};

methods.addFieldValue = function(item, label, ord) {
	if (!label) {
		label = 'Untitled';
	}
	if (typeof item.lov === 'undefined') {
		item.lov = {};
	}
	newValueId = 't' + (++thus.newItemCount);
	item.lov[newValueId] = {
		label: label,
		ord: ord || _.size(item['lov']) + 100
	};
};

methods.saveFieldListOfValues = function(fieldId) {
	var field = thus.getItem('field', fieldId);
	$('#field_values_list div.field_value').each(function(i, value) {
		var id = $(this).data('id');
		if (!field.lov) {
			field.lov = {};
		}
		if (field.lov[id]) {
			field.lov[id].label = $(value).find('input').val();
			field.lov[id].ord = i + 1;
		}
	});
};

methods.openPageEdit = function(pageId, tuixTabId, stopAnimation) {
	thus.openEdit('page', pageId, tuixTabId, stopAnimation);
};

methods.openFieldEdit = function(fieldId, tuixTabId, stopAnimation) {
	thus.openEdit('field', fieldId, tuixTabId, stopAnimation);
};

methods.getItem = function(itemType, itemId) {
	if (itemType == 'page') {
		return thus.tuix.pages[itemId];
	} else if (itemType == 'field') {
		return thus.tuix.items[itemId];
	} else {
		return false;
	}
};

methods.loadFieldValuesListPreview = function(fieldId) {
	var field = thus.getItem('field', fieldId);
	var values = thus.getOrderedFieldValues(fieldId);
	var html = false;
	if (field.type == 'select' || field.type == 'centralised_select') {
		values.unshift({label: '-- Select --'});
		html = thus.microTemplate('zenario_organizer_admin_box_builder_select_values', values);
		$('#organizer_form_field_' + fieldId + ' select').html(html);
	} else if (field.type == 'radios' || field.type == 'centralised_radios') {
		html = thus.microTemplate('zenario_organizer_admin_box_builder_radio_values_preview', values);
		$('#organizer_form_field_values_' + fieldId).html(html);
	} else if (field.type == 'checkboxes') {
		html = thus.microTemplate('zenario_organizer_admin_box_builder_checkbox_values_preview', values);
		$('#organizer_form_field_values_' + fieldId).html(html);
	}
};

methods.formatPageLabel = function(label) {
	if (!label || label.trim() === '') {
		label = 'Untitled';
	}
	return label;
};

methods.moveFieldToPage = function(sourcePageId, targetPageId, fieldId, addToStart) {
	//Check if invalid move
	if (sourcePageId == targetPageId
		|| thus.tuix.items[fieldId].page_id != sourcePageId
		|| thus.tuix.items[fieldId].type == 'repeat_end'
	) {
		return;
	}
	
	thus.tuix.pages[targetPageId].fields_reordered = true;
	thus.tuix.pages[sourcePageId].fields_reordered = true;
	
	//Get ordinal to place field from source page
	var ord = 1;
	var fieldsOnTargetPage = thus.getOrderedFields(targetPageId);
	if (fieldsOnTargetPage.length) {
		if (addToStart) {
			ord = fieldsOnTargetPage[0].ord - 1;
		} else {
			ord = fieldsOnTargetPage[fieldsOnTargetPage.length - 1].ord + 1;
		}
	}
	
	//Move matching repeat_end for repeat_start
	if (thus.tuix.items[fieldId].type == 'repeat_start') {
		var repeatEndId = thus.getMatchingRepeatEnd(fieldId);
		thus.tuix.items[repeatEndId].ord = ord + 0.001;
		thus.tuix.items[repeatEndId].page_id = targetPageId;
		thus.tuix.pages[targetPageId].fields[repeatEndId] = 1;
		delete(thus.tuix.pages[sourcePageId].fields[repeatEndId]);
	}
	
	thus.tuix.items[fieldId].ord = ord;
	thus.tuix.items[fieldId].page_id = targetPageId;
	thus.tuix.pages[targetPageId].fields[fieldId] = 1;
	delete(thus.tuix.pages[sourcePageId].fields[fieldId]);
	
	thus.changeMadeToPanel();
};


//TUIX 


methods.getTUIXTabs = function(tuix, tuixTabId, item) {
	var sortedTabs = [];
	var currentTabFound = false;
	foreach (tuix.tabs as var tabId => var tab) {
		var tabClone = _.clone(tab);
		tabClone.id = tabId;
		
		//Don't show this tab if hidden
		if (tabClone.hidden || (tabClone.visible_if && !zenarioT.doEval(tabClone.visible_if, thus, undefined, item))) {
			continue;
		}
		
		//Select a tab
		if (!currentTabFound && (tabId == tuixTabId || !tuix.tabs[tuixTabId])) {
			currentTabFound = true;
			tabClone._is_current_tab = true;
			thus.editingThingTUIXTabId = tabId;
		}
		
		sortedTabs.push(tabClone);
	}
	return sortedTabs;
};

methods.getTUIXTabsHTML = function(tabs) {
	var html = '';
	for (var i = 0; i < tabs.length; i++) {
		html += thus.getTUIXTabHTML(tabs[i]);
	}
	return html;
};

methods.getTUIXTabHTML = function(tab) {
	return thus.microTemplate('zenario_organizer_admin_box_builder_tuix_tab', tab);
};

methods.getTUIXTags = function(tuixMode, item) {
	var tags = JSON.parse(JSON.stringify(thus.tuix[tuixMode]));
	foreach (tags.tabs as var tabId => var tab) {
		foreach (tab.fields as var fieldId => var field) {
			
			//Load tuix values from item
			if (item[fieldId] !== null && item[fieldId] !== undefined) {
				field.value = item[fieldId];
			//Save default values to item
			} else if (field.value !== undefined) {
				item[fieldId] = field.value;
			}
			
			if (field.values) {
				if (typeof field.values == 'string') {
					field.values = thus.getTUIXFieldCustomValues(field.values);
				}
			}
			
		}
	}
	return tags;
};

methods.getTUIXFieldsHTML = function(tags, tuixTabId, item, itemType) {
	var sortedFields = [];
	
	foreach (tags.tabs as var tabId => var tab) {
		var previousFieldHidden = false;
		var hiddenFieldsByIndent = {};
		foreach (tab.fields as var fieldId => var field) {
			var currentFieldHidden = false;
			var indent = 1 * field.indent || 0;
			if (field.hidden 
				|| (field.hide_with_previous_field && previousFieldHidden) 
				|| (field.hide_with_previous_outdented_field && hiddenFieldsByIndent[indent - 1])
				|| (field.visible_if && !zenarioT.doEval(field.visible_if, thus, undefined, item))
			) {
				currentFieldHidden = true;
				previousFieldHidden = true;
				
				field.hidden = true;
				
			} else {
				previousFieldHidden = false;
			}
			hiddenFieldsByIndent[indent] = currentFieldHidden;
		}
	}
	
	foreach (tags.tabs[tuixTabId].fields as var fieldId => var field) {
		//Don't show this field if hidden
		if (field.hidden) {
			continue;
		}
		
		field.id = fieldId;
		field.itemType = itemType;
		field.itemId = item.id;
		field.tuixTabId = tuixTabId;
				
		//Format the values as mergefields
		if (field.values) {
			var sortedValues = [];
			var usesOptGroups = false;
			foreach (field.values as var valueId => var value) {
				value.name = fieldId;
				value.value = valueId;
				value.itemType = itemType;
				value.itemId = item.id;
				value.tuixTabId = tuixTabId;
				
				if (field.readonly) {
					value.readonly = field.readonly;
				}
				
				if (field.type == 'checkboxes') {
					value.selected = field.value && field.value.indexOf(valueId) !== -1;
				} else {
					value.selected = (field.value == valueId);
				}
				
				//Check whether to use optgroups
				if (value.parent && field.values[value.parent]) {
				    usesOptGroups =true;
					var parent = field.values[value.parent];
					if (!parent.hasChildren) {
						parent.hasChildren = true;
						parent.options = [];
					}
					parent.options.push(value);
				} else {
					sortedValues.push(value);
				}
			}
			
			
			if (usesOptGroups) {
				for (var i = 0; i < sortedValues.length; i++) {
					if (sortedValues[i].options) {
						sortedValues[i].options.sort(thus.sortByOrd);
					}
				}
			}
			sortedValues.sort(thus.sortByOrd);
			
			//Add empty value
			if (field.empty_value) {
				sortedValues.unshift({
					label: field.empty_value,
					value: ''
				});
			}
			
			field.values = sortedValues;
		}
		
		//Mergefields for CRM values
		if (field.type == 'values_list') {
			field.lov = thus.getOrderedFieldValues(item.id);
		} else if (field.type == 'crm_values') {
			var mergeFields = {};
			mergeFields.values = [];
			if (item.crm_lov === undefined) {
				item.crm_lov = {};
			}
			if (item.type == 'checkbox' || item.type == 'group') {
				var values = {
					'unchecked': {
						label: 0, 
						ord: 1
					}, 
					'checked': {
						label: 1, 
						ord: 2
					}
				};
				foreach (values as var valueId => var value) {
					value.id = valueId;
					value.value = item.crm_lov[valueId];
					mergeFields.values.push(value);
				}
			} else {
				var values = thus.getOrderedFieldValues(item.id);
				for (var i = 0; i < values.length; i++) {
					values[i].value = item.crm_lov[values[i].id];
					mergeFields.values.push(values[i]);
				}
			}
			mergeFields.values.sort(thus.sortByOrd);
			field.crm_values = mergeFields;
			
		//Mergefields for translations
		} else if (field.type == 'translations') {
			field.translation_fields = [];
			
			var languages = [];
			foreach (thus.tuix.languages as var languageId => var language) {
				languages.push(language)
			}
			languages.sort(thus.sortByOrd);
			
			foreach (tags.tabs as var tabId => var tab) {
				foreach (tab.fields as var tFieldId => var tField) {
					if (!tField.hidden && engToBoolean(tField.is_phrase)) {
						var translationField = {};
						translationField.phrases = [];
						translationField.label = tField.label;
						if (item[tFieldId]) {
							translationField.value = item[tFieldId];
						} else {
							translationField.value = '(No text is defined in the default language)';
							translationField.disabled = true;
						}
						
						for (var i = 0; i < languages.length; i++) {
							if (engToBoolean(languages[i].translate_phrases)) {
								translationField.phrases.push({
									field_column: tFieldId,
									language_id: languages[i].id,
									language_name: languages[i].english_name,
									phrase: item.translations[tFieldId][languages[i].id],
									disabled: translationField.disabled
								});
							}
						}
						
						if (translationField.phrases.length) {
							field.translation_fields.push(translationField);
						}
					}
				}
			}
		}
		
		sortedFields.push(field);
	}
	
	var html = '';
	for (var i = 0; i < sortedFields.length; i++) {
		html += thus.getTUIXFieldHTML(sortedFields[i]);
	}
	return html;
};



methods.getTUIXFieldHTML = function(field) {
	var html = thus.microTemplate('zenario_organizer_admin_box_builder_tuix_field', field);
	return html;
};


methods.saveTUIXTab = function(tuix, tuixTabId, item) {
	foreach (tuix.tabs[tuixTabId].fields as var tuixFieldId => var tuixField) {
		thus.saveTUIXField(tuixField, tuixFieldId, item);
	}
};

methods.saveTUIXField = function(tuixField, tuixFieldId, item) {
	var oldValue = item[tuixFieldId];
	
	if (tuixField.type == 'values_list') {
		thus.saveFieldListOfValues(item.id);
		item._changed = true;
		
	} else if (tuixField.type == 'translations') {
		$('#organizer_field_translations input.translation').map(function(index, input) {
			var languageId = $(this).data('language_id');
			var column = $(this).data('field_column');
			item.translations[column][languageId] = input.value;
		});
		item._changed = true;
		
	} else if (tuixField.type == 'crm_values') {
		$('#organizer_field_crm_values input.crm_value_input').map(function(index, input) {
			var valueId = $(this).data('id');
			item.crm_lov[valueId] = $(this).val();
		});
		item._changed = true;
	} else {
		var value = thus.getTUIXFieldValue(tuixField, tuixFieldId);
		
		if (value !== null && value !== undefined) {
			item[tuixFieldId] = value;
		}
	}
	
	if (oldValue != item[tuixFieldId]) {
		item._changed = true;
	}
};

methods.getTUIXFieldValue = function(tuixField, tuixFieldId) {
	var value = null;
	
	if (tuixField.type == 'select' || tuixField.type == 'text' || tuixField.type == 'textarea') {
		value = $('#field__' + tuixFieldId).val();
		if ((tuixField.type == 'text' || tuixField.type == 'textarea') && value) {
			value = value.trim();
		}
		
	} else if (tuixField.type == 'checkbox') {
		value = $('#field__' + tuixFieldId).is(':checked');
	
	} else if (tuixField.type == 'radios') {
		value = $('#organizer_field_details_form input[name="' + tuixFieldId + '"]:checked').val();
	
	} else if (tuixField.type == 'checkboxes') {
		value = [];
		$('#organizer_field_details_form input[name="' + tuixFieldId + '"]:checked').each(function() {
			value.push($(this).val());
		});
	}
	
	return value;
};

methods.openItemTUIXTab = function(itemType, itemId, tuixTabId, errors, changedFieldId, notices) {
	var tuixMode = thus.getTUIXModeForItemType(itemType);
	var tuix = thus.tuix[tuixMode];
	var item = thus.getItem(itemType, itemId);
	var tags = thus.getTUIXTags(tuixMode, item, itemType);
	
	thus.formatTUIX(itemType, item, tuixTabId, tags, changedFieldId);
	
	//Display any errors
	var html = '';
	if (errors && errors.length > 0) {
		for (var i = 0; i < errors.length; i++) {
			html += '<p class="error">' + errors[i] + '</p>';
		}
		
		$('#organizer_field_details_form').effect({
			effect: 'bounce',
			duration: 125,
			direction: 'right',
			times: 2,
			distance: 5,
			mode: 'effect'
		});
	}
	
	if (notices) {
		for (var i = 0; i < notices.length; i++) {
			html += '<p class="success">' + notices[i] + '</p>';
		}
	}
	
	html += thus.getTUIXFieldsHTML(tags, tuixTabId, item, itemType);
	$('#organizer_field_details').html(html);
	
	thus.addTUIXTabEvents(itemType, itemId, tuixTabId);
};

methods.tuixFieldDetailsChanged = function(itemType, itemId, tuixTabId, tuixFieldId) {
	var tuixMode = thus.getTUIXModeForItemType(itemType);
	var tuixField = thus.tuix[tuixMode].tabs[tuixTabId].fields[tuixFieldId];
	var item = thus.getItem(itemType, itemId);
	
	var value = thus.getTUIXFieldValue(tuixField, tuixFieldId);
	if (value !== null && value != item[tuixFieldId]) {
		thus.changeMadeToPanel();
	}
	
	if (engToBoolean(tuixField.format_onchange)) {
		//Save changed values
		var tuix = thus.tuix[tuixMode];
		thus.saveTUIXTab(tuix, tuixTabId, item);
		
		//Format tuix fields and redraw
		thus.openItemTUIXTab(itemType, itemId, tuixTabId, undefined, tuixFieldId);
	}
};



//Draw (or hide) the button toolbar
//This is called every time different items are selected, the panel is loaded, refreshed or when something in the header toolbar is changed.
methods.showButtons = function($buttons) {	
	if (thus.changeMadeOnPanel) {
		//Change the buttons to apply/cancel buttons
		var mergeFields = {
			confirm_text: 'Save changes',
			confirm_css: 'form_editor',
			cancel_text: 'Reset'
		};
		$buttons.html(thus.microTemplate('zenario_organizer_apply_cancel_buttons', mergeFields));
		
		//Add an event to the Apply button to save the changes
		$buttons.find('#organizer_applyButton')
			.click(function() {
				if (thus.saveCurrentOpenDetails() && thus.displayPageFieldOrderErrors()) {
					thus.saveChanges();
				}
			});
		
		$buttons.find('#organizer_cancelButton')
			.click(function() {
				if (confirm('Are you sure you want to discard all your changes?')) {
					window.onbeforeunload = false;
					zenarioO.enableInteraction();
					
					thus.editingThing = false;
					thus.currentPageId = false;
					
					thus.changeMadeOnPanel = false;
					zenarioO.reload();
				}
			});
		
	} else {
		//Remove the buttons, but don't actually hide them as we want to keep some placeholder space there
		$buttons.html('').show();
	}
};

methods.displayPageFieldOrderErrors = function() {
	return false;
};

//Wrapper for an AJAX request
methods.sendAJAXRequest = function(requests, after) {
	var actionRequests = zenarioO.getKey(),
		actionTarget = 
		'zenario/ajax.php?' +
			'__pluginClassName__=' + thus.tuix.class_name +
			'&__path__=' + zenarioO.path +
			'&method_call=handleOrganizerPanelAJAX',
		clearPreloader = function() {
			get('organizer_preloader_circle').style.display = 'none';
			zenarioA.nowDoingSomething();
			window.onbeforeunload = false;
		};
	
	$.extend(actionRequests, requests);
	
	get('organizer_preloader_circle').style.display = 'block';
	//zenario.ajax(url, post, json, useCache, retry, continueAnyway, settings, timeout, AJAXErrorHandler, onRetry, onCancel, onError)
	var result = zenario.ajax(
		URLBasePath + actionTarget,
		actionRequests,
		true,
		false,
		true,
		true,
		undefined,
		undefined,
		undefined,
		undefined,
		undefined,
		clearPreloader
	).after(function(result) {
		clearPreloader();
		if (after !== undefined) {
			after(result);
		}
	});
	return result;
};


// Base methods


//Called whenever Organizer has saved an item and wants to display a toast message to the administrator
methods.displayToastMessage = function(message, itemId) {
	//Do nothing, don't show the message!
};

//Never show the left hand nav; always show this panel using the full width
methods.returnShowLeftColumn = function() {
	return false;
};

//Sets the title shown above the panel.
//This is also shown in the back button when the back button would take you back to this panel.
methods.returnPanelTitle = function() {
	return methodsOf(panelTypes.grid).returnPanelTitle.call(thus);
};

//Return whether you are allowing multiple items to be selected in full and quick mode.
//(In select mode the opening picker will determine whether multiple select is allowed.)
methods.returnMultipleSelectEnabled = function() {
	return false;
};

//Whether to enable searching on a panel
methods.returnSearchingEnabled = function() {
	return false;
};

//Return whether you want to enable inspection view
methods.returnInspectionViewEnabled = function() {
	return false;
};

}, zenarioO.panelTypes);

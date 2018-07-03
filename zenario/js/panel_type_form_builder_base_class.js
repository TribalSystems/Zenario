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

//Custom methods

methods.sortByOrd = function(a, b) {
	if (a.ord < b.ord) 
		return -1;
	if (a.ord > b.ord)
		return 1;
	return 0;
};

// Send an AJAX request
methods.sendAJAXRequest = function(requests) {
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
	).after(clearPreloader);
	return result;
	
	//zenario.ajax(url, post, json, useCache, retry, continueAnyway, settings, timeout, AJAXErrorHandler, onRetry, onCancel, onError)

};

methods.sortErrors = function(errors) {
	var sortedErrors = [];
	foreach (errors as var i => var error) {
		sortedErrors.push(error);
	}
	return sortedErrors;
};

methods.loadFields = function(path, fields, values) {
	var loadedFields = {};
	foreach (fields as var i => var _field) {
		if (_field) {
			var field = _.clone(_field);
			field.id = i;
			field.path = path;
			
			if (field.value && (!defined(values[i]))) {
				values[i] = field.value;
			}
			
			//Get value
			if (values && defined(values[i])) {
				field._value = values[i];
			}
			
			if (field.hidden) {
				field._hidden = true;
			}
			loadedFields[i] = field;
		}
	}
	return loadedFields;
};

methods.sortFields = function(fields, item) {
	var sortedFields = [];
	
	foreach (fields as var i => var field) {
		//Sort field value list
		if (field.type == 'select' || field.type == 'radios' || field.type == 'checkboxes') {
			field._values = thus.getFieldValuesList(field);
			if (field.empty_value) {
				field._values.unshift({
					ord: 0,
					label: field.empty_value,
					value: ''
				});
			}	
		}
		
		if (field.visible_if && typeof(field._hidden) === 'undefined') {
			if (!zenarioT.doEval(field.visible_if, undefined, undefined, item, undefined, undefined, undefined, undefined, undefined, undefined, thus.tuix)) {
				field._hidden = true;
			}
		}
		if (field.readonly_if && typeof(field._readonly) === 'undefined') {
			if (zenarioT.doEval(field.readonly_if, undefined, undefined, item, undefined, undefined, undefined, undefined, undefined, undefined, thus.tuix)) {
				field._readonly = true;
			}
		}
		
		sortedFields.push(field);
	}
	return sortedFields;
};

methods.saveFieldListOfValues = function(field) {
	if (field) {
		$('#field_values_list div.field_value').each(function(i, value) {
			var id = $(this).data('id');
			if (!field.lov) {
				field.lov = {};
			}
			if (field.lov[id]) {
				field.lov[id].id = id;
				field.lov[id].label = $(value).find('input').val();
				field.lov[id].ord = i + 1;
			}
		});
	}
};

methods.getTUIXFieldsHTML = function(fields) {
	var html = '';
	for (var i = 0; i < fields.length; i++) {
		html += thus.getTUIXFieldHTML(fields[i]);
	}
	return html;
};
methods.getTUIXFieldHTML = function(field) {
	var html = thus.microTemplate('zenario_organizer_admin_box_builder_tuix_field', field);
	return html;
};


methods.sortAndLoadTUIXPages = function(pages, item, selectedPage) {
	var sortedPages = [];
	var selected = false;
	foreach (pages as var i => var page) {
		if (page) {
			page.id = i;
			if (page.visible_if) {
				if (!zenarioT.doEval(page.visible_if, undefined, undefined, item)) {
					continue;
				}
			}
			if (!selected && (!selectedPage || (selectedPage == i))) {
				selected = true;
				page._selected = true;
				thus.selectedDetailsPage = i;
			}
			sortedPages.push(page);
		}
	}
	return sortedPages;
};

methods.getTUIXPagesHTML = function(pages) {
	var html = '';
	for (var i = 0; i < pages.length; i++) {
		html += thus.getTUIXPageHTML(pages[i]);
	}
	return html;
};
methods.getTUIXPageHTML = function(page) {
	var html = thus.microTemplate('zenario_organizer_admin_box_builder_tuix_tab', page);
	return html;
};


methods.saveItemTUIXFields = function(item, fields, tuixPath, errors) {
	foreach (fields as var prop => var field) {
		if ($('#tuix_field__' + prop).length) {
			if (field.type == 'text' || field.type == 'select' || field.type == 'textarea') {
				item[prop] = $('#field__' + prop).val();
			} else if (field.type == 'checkbox') {
				item[prop] = $('#field__' + prop).is(':checked');
			} else if (field.type == 'radios') {
				item[prop] = $('#organizer_field_details_form input[name="' + prop + '"]:checked').val();
			} else if (field.type == 'checkboxes') {
				item[prop] = [];
				$('#organizer_field_details_form input[name="' + prop + '"]:checked').each(function() {
					item[prop].push($(this).val());
				});
			} else if (field.type == 'values_list') {
				thus.saveFieldListOfValues(item);
			} else if (field.type == 'translations') {
				item._translations = {};
				$('#organizer_field_translations input.translation').map(function(index, input) {
					var languageId = $(this).data('language_id');
					var fieldName = $(this).data('field_column');
					if (!item._translations[fieldName]) {
						item._translations[fieldName] = {phrases: {}};
					}
					item._translations[fieldName].phrases[languageId] = input.value;
				});
				
			} else if (field.type == 'crm_values') {
				var prefix = 'crm';
				if (field.crm_type == 'salesforce') {
					prefix = 'salesforce';
				}
				if (item.type == 'checkbox' || item.type == 'group' || item.type == 'consent') {
					item['_' + prefix + '_data'] = {};
					item['_' + prefix + '_data'].values = {
						'unchecked': {
							ord: 1,
							label: 0
						},
						'checked': {
							ord: 2,
							label: 1
						}
					};
				}
				$('#organizer_field_crm_values input.crm_value_input').map(function(index, input) {
					var id = $(this).data('id');
					if (item.type == 'checkbox' || item.type == 'group' || item.type == 'consent') {
						item['_' + prefix + '_data'].values[id][prefix + '_value'] = $(this).val();
					} else if (item.lov && item.lov[id]) {
						item.lov[id][prefix + '_value'] = $(this).val();
					}
				});
			}
		
			if (field.validation && field.validation.required) {
				if (!item[prop]) {
					errors[prop] = field.validation.required;
				}
			}
		}
	}
	
	thus.validateFieldDetails(fields, item, tuixPath, thus.selectedDetailsPage, errors);
};


methods.saveItemTUIXPage = function(tuixPath, page, item) {
	var errors = {};
	if (thus.tuix[tuixPath] && thus.tuix[tuixPath].tabs && thus.tuix[tuixPath].tabs[page]) {
		var fields = thus.tuix[tuixPath].tabs[page].fields;
		thus.saveItemTUIXFields(item, fields, tuixPath, errors);
		item._changed = true;
	}
	return errors;
};

methods.addFieldValue = function(item, label, ord) {
	if (!label) {
		label = 'Untitled';
	}
	if (typeof item.lov === 'undefined') {
		item.lov = {};
	}
	newValueId = 't' + thus.maxNewCustomFieldValue++;
	item.lov[newValueId] = {
		id: newValueId,
		label: label,
		ord: ord || _.size(item['lov']) + 100,
		_is_new: true
	};
};

methods.getPageLabel = function(label) {
	if (label && label.trim() === '') {
		label = 'Untitled';
	}
	return label;
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


//Draw (or hide) the button toolbar
//This is called every time different items are selected, the panel is loaded, refreshed or when something in the header toolbar is changed.
methods.showButtons = function($buttons) {	
	if (thus.changeDetected) {
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
				var errors = thus.saveCurrentOpenDetails();
				if (!errors) {
					errors = thus.displayPageFieldStructureErrors(thus.currentPageId);
					if (!errors) {
						thus.saveChanges();
					}
				}
			});
		
		$buttons.find('#organizer_cancelButton')
			.click(function() {
				if (confirm('Are you sure you want to discard all your changes?')) {
					window.onbeforeunload = false;
					zenarioO.enableInteraction();
					
					thus.selectedFieldId = false;
					thus.selectedPageId = false;
					thus.currentPageId = false;
					
					thus.changeDetected = false;
					zenarioO.reload();
				}
			});
		
	} else {
		//Remove the buttons, but don't actually hide them as we want to keep some placeholder space there
		$buttons.html('').show();
	}
};

methods.displayPageFieldStructureErrors = function() {
	return false;
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

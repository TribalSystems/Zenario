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
	
	For more information, see js_minify.shell.php for steps (1) and (2), and organizer.wrapper.js.php for step (3).
*/


zenario.lib(function(
	undefined,
	URLBasePath,
	document, window, windowOpener, windowParent,
	zenario, zenarioA, zenarioAB, zenarioAT, zenarioO,
	get, engToBoolean, htmlspecialchars, ifNull, jsEscape, phrase,
	extensionOf, methodsOf, has,
	panelTypes
) {
	"use strict";


//Note: extensionOf() and methodsOf() are our shortcut functions for class extension in JavaScript.
	//extensionOf() creates a new class (optionally as an extension of another class).
	//methodsOf() allows you to get to the methods of a class.
var methods = methodsOf(
	panelTypes.form_builder = extensionOf(panelTypes.form_builder_base_class)
);

//Called by Organizer upon the first initialisation of this panel.
//It is not recalled if Organizer's refresh button is pressed, or the administrator changes page
methods.init = function() {
	// Warning message when trying to leave without saving changes
	this.saveChangesWarningMessage = 'You are currently editing this form. If you leave now you will lose any unsaved changes.';
	
	// Whether there are any local changes
	this.changingForm = false;
	
	// Whether the values list of a field has been changed
	this.valuesChanged = false;
	
	// Fields which cause details panel to update on change
	this.formatOnChange = [
		'values_source',
		'readonly_or_mandatory',
		'mandatory_condition_field_id',
		'visibility',
		'visible_condition_field_id',
		'validation',
		'default_value_options',
		'send_to_crm'
	];
	
	// Top level div for this editor
	this.formEditorSelector = '#organizer_form_builder';
	
	// Selector for all form fields
	this.formFieldsSelector = '#organizer_form_fields .form_field';
	
	// Selector for form fields inline buttons
	this.formFieldInlineButtonsSelector = '#organizer_form_fields .form_field_inline_buttons';
	
	this.pageBreakCount = 0;
	
	// Save buttons text
	this.saveButtonText = 'Save changes';
	this.cancelButtonText = 'Reset';
};

// Change objects into ordered array for microtemplate
methods.getOrderedItems = function(items) {
	var fieldId, field, valueId, value,
		orderedItems = [];
	
	// Loop through each field
	foreach (items as fieldId => field) {
		var fieldClone = _.clone(field);
		if (!field.remove) {
			// Check if field has a list of values
			if (fieldClone.lov) {
				var values = [];
				// If so store them in array
				foreach (fieldClone.lov as valueId => value) {
					values.push(value);
				}
				// Sort field lov
				values.sort(this.sortByOrd);
				fieldClone.lov = values;
			}
			// Store fields in array
			orderedItems.push(fieldClone);
		}
	}
	// Sort fields
	orderedItems.sort(this.sortByOrd);
	return orderedItems;
};

// Change objects into ordered array for microtemplate
methods.getOrderedItemCRMLOV = function(items, useLabelsForNewValues) {
	var id, item, 
		orderedItems = [];
	
	foreach (items as id => item) {
		if (!item.remove) {
			var itemClone = _.clone(item);
			
			itemClone.id = id;
			
			if (useLabelsForNewValues) {
				if (isNaN(parseInt(id))) {
					itemClone.crm_value = itemClone.label;
				}
			}
			
			orderedItems.push(itemClone);
		}
	}
	// Sort fields
	orderedItems.sort(this.sortByOrd);
	return orderedItems;
};

methods.getOrderedTranslations = function(translatableFields) {
	var id, item,
		orderedItems = [],
		field = this.tuix.items[this.current.id];
	
	// Get ordered list of languages
	var languages = this.getOrderedItems(this.tuix.languages);
	
	foreach (translatableFields as id => item) {
		
		var itemClone = this.tuix.translatable_fields[id],
			disabled = false;
		
		// Get current field value
		itemClone.value = field[itemClone.column];
		
		if (!itemClone.value) {
			itemClone.value = '(No text is defined in the default language)';
			disabled = true;
		}
		
		// Get list of phrases for translatable languages
		itemClone.phrases = [];
		
		var index, language;
		foreach (languages as index => language) {
			if (parseInt(language.translate_phrases)) {
				var phrase = (item.phrases[language.id] && !disabled) ? item.phrases[language.id] : '';
				itemClone.phrases.push({
					field_column: itemClone.column,
					language_id: language.id,
					language_name: language.english_name,
					phrase: phrase,
					disabled: disabled
				});
			}
		}
		
		orderedItems.push(itemClone);
	}
	// Sort fields
	orderedItems.sort(this.sortByOrd);
	return orderedItems;
};

methods.getOrderedDatasetFields = function(items) {
	// Loop through current form fields and find all used dataset fields
	var datasetFieldsOnForm = {};
	foreach (this.tuix.items as id => field) {
		if (field.field_id) {
			datasetFieldsOnForm[field.field_id] = true;
		}
	}
	
	var item, tabName, tab, key, value, id, field,
		orderedItems = [];
	// Loop through each tab
	foreach (items as tabName => tab) {
		var item = {};
		// Store tab metadata except fields
		foreach (tab as key => value) {
			if (key != 'fields') {
				item[key] = value;
			}
		}
		// If tab has fields create array
		if (tab.fields) {
			item.fields = [];
			// Loop through each field
			foreach (tab.fields as id => field) {
				// Store fields in array if not used on form already
				if (datasetFieldsOnForm[id] !== true) {
					item.fields.push(field);
				}
			}
			// Sort fields
			item.fields.sort(this.sortByOrd)
		}
		// Store tabs in array
		orderedItems.push(item);
	}
	// Sort tabs
	orderedItems.sort(this.sortByOrd);
	
	
	// Split fields into 2 columns
	var index, fields, column1, column2;
	foreach (orderedItems as index => tab) {
		fields = tab.fields;
		tab.fields = [];
		
		column1 = {
			index: 1, 
			fields: fields.splice(0, Math.ceil(fields.length / 2))
		};
		column2 = {
			index: 2,
			fields: fields
		};
		
		tab.columns = [column1, column2];
	}
	return orderedItems;
};


//Draw the panel, as well as the header at the top and the footer at the bottom
//This is called every time the panel is loaded, refreshed or when something in the header toolbar is changed.
methods.showPanel = function($header, $panel, $footer) {
	$header.html('').hide();
	
	// Show growl message if saved changes
	if (this.changesSaved) {
		this.changesSaved = false;
		var toast = {
			message_type: 'success',
			message: 'Your changes have been saved!'
		};
		zenarioA.toast(toast);
	}
	
	var that = this,
		mergeItems = _.clone(this.tuix.items),
		mergeFields = {
			items: {},
			field: {},
			details_section: 'field_type_list',
			form_title: this.tuix.form_title
		};
	
	
	this.currentFieldTab = this.currentFieldTab ? this.currentFieldTab : 'details';
	if (this.currentFieldTab == 'translations' && !this.tuix.show_translation_tab) {
		this.currentFieldTab = 'details';
	}
	
	if (this.current && this.current.id && this.tuix.items[this.current.id]) {
		
		mergeFields.items = this.getOrderedItems(mergeItems);
		
		// Handle case where new field is selected after Id has been changed from, e.g. t4 => 6
		if (!this.tuix.items[this.current.id]
			&& this.currentFieldOrd !== undefined
			&& mergeFields.items[this.currentFieldOrd]
		) {
			this.current.id = mergeFields.items[this.currentFieldOrd].id;
		}
		
		// Try to select last selected field
		mergeFields.details_section = 'field_details';
		mergeFields.field = this.getCurrentFieldDetailsMergeFields();
		mergeItems[this.current.id].selected = true;
	} else {
		this.current = {id: false, type: false};
	}
	
	mergeFields.items = this.getOrderedItems(mergeItems);
	
	var html = zenarioA.microTemplate('zenario_organizer_form_builder', mergeFields);
	$panel.html(html).show();
	
	// Add JS events
	if (this.current) {
		this.initCurrentFieldDetails();
	}
	
	this.maxNewCustomTab = 1;
	this.maxNewCustomField = 1;
	this.maxNewCustomFieldValue = 1;
	this.current_db_columns = this.tuix.existing_db_columns;
	this.deleting = false;
	this.pageBreakCount = this.tuix.pageBreakCount;
	
	this.initSection();
	
	// Make unlinked fields palette draggable
	$('#organizer_field_type_list div.field_type, #organizer_centralised_field_type_list div.field_type').draggable({
		connectToSortable: '#organizer_form_fields',
		helper: 'clone'
	});
	
	this.initLinkedFieldsAdder();
	
	// Edit a form field
	$(this.formFieldsSelector).on('click', function() {
		that.fieldClick($(this));
	});
	
	this.initDeleteButtons();
	
	this.initFormSettingsButton();
	
	$footer.html('').show();
};

methods.initLinkedFieldsAdder = function() {
	var that = this;
	
	// Filter out fields already included on the form and order by ordinal
	var dataset_fields = this.getOrderedDatasetFields(this.tuix.dataset_fields);
	
	// Set linked fields HTML
	var html = zenarioA.microTemplate('zenario_organizer_form_builder_dataset_tab', dataset_fields);
	$('#organizer_linked_field_type_list').html(html);
	
	// Make linked fields palette draggable
	$('#organizer_linked_field_type_list div.dataset_field').draggable({
		connectToSortable: '#organizer_form_fields',
		helper: 'clone'
	});
};

methods.initDeleteButtons = function() {
	var that = this;
	$('#organizer_form_fields .delete_icon').off().on('click', function(e) {
		
		var that2 = this;
		
		// Get user responses saved by this field
		var actionRequests = {
			mode: 'get_field_response_count',
			id: $(this).data('id')
		};
		that.sendAJAXRequest(actionRequests).after(function(data) {
			
			var data = JSON.parse(data),
				message = '',
				plural = '';
			
			if (!data.count) {
				message += '<p>There are no user responses for this field.</p>';
			} else {
				plural = data.count == 1 ? '' : 's';
				message += '<p>This field has ' + data.count + ' user response' + plural + ' recorded against it.</p>';
				message += '<p>When you save changes to this form, that data will be deleted.</p>';
			}
			message += '<p>Delete this form field?</p>';
			
			that.deleting = true;
			zenarioA.showMessage(message, 'Delete', 'warning', true);
			that.listenForDelete($(that2).data('id'));
		});
		e.stopPropagation();
	});
};

methods.listenForDelete = function(id) {
	// Listen for field delete
	var that = this;
	$('#zenario_fbMessageButtons input.submit_selected').on('click', function() { 
		setTimeout(function() {
			if (that.deleting == true) {
				that.deleteField(id);
			}
			that.deleting = false;
		});
	});
};

methods.deleteField = function(id) {
	var that = this,
		afterValidate = function(errors) {
			if (_.isEmpty(errors)) {
				var $field, $next, field;
				
				if (id === undefined) {
					id = that.current.id;
				}
				
				// Field to delete
				field = that.tuix.items[id];
				
				if (field.type == 'page_break') {
					that.pageBreakCount--;
				}
				
				// Mark for deletion
				that.tuix.items[id] = {remove: true};
				
				$field = $('#organizer_form_field_' + id);
				
				// Update linked fields list if dataset field
				if (field.field_id) {
					that.initLinkedFieldsAdder();
				}
				
				if ($field) {
					// Select the previous field that isn't already being removed (because of the animation), otherwise look for the next field
					// othereise show add fields panel
					
					$next = that.selectNextField($field);
					that.changeMadeToPanel();
					
					$field.animate({height: 0, opacity: 0}, 500, function() {
						$field.remove();
						if ($next === false) {
							that.showNoFieldsMessage();
						}
					});
				}
			}
		};
	
	if (cb = this.validate(true)) {
		cb.after(afterValidate);
	} else {
		afterValidate([]);
	}
};

methods.selectNextField = function($field) {
	$next = $field.prev();
	while ($next.length == 1) {
		if (this.tuix.items[$next.data('id')] && this.tuix.items[$next.data('id')].remove) {
			$next = $next.prev();
		} else {
			break;
		}
	}
	if ($next.length == 0) {
		$next = $field.next();
		while ($next.length == 1) {
			if (this.tuix.items[$next.data('id')] && this.tuix.items[$next.data('id')].remove) {
				$next = $next.next();
			} else {
				break;
			}
		}
	}
	if ($next.length == 1) {
		this.fieldClick($next);
		return $next;
	}
	return false;
};

methods.showNoFieldsMessage = function() {
	$('#organizer_section_' + this.currentTab).addClass('empty').html(
		'<span class="no_fields_message">There are no fields on this form</span>');
	this.showDetailsSection('organizer_field_type_list');
};


methods.initSection = function() {
	var that = this;
	$('#organizer_form_fields').sortable({
		items: 'div.form_field',
		placeholder: 'preview',
		// Add new field to the form
		receive: function(event, ui) {
			$(this).find('div.field_type, div.dataset_field').each(function() {
				that.addNewField($(this));
			});
		},
		start: function(event, ui) {
			that.startIndex = ui.item.index();
		},
		// Detect reorder/new/deleted fields
		stop: function(event, ui) {
			if (that.startIndex != ui.item.index()) {
				
				// Update current field ord
				if (that.current.type == 'field') {
					that.currentFieldOrd = $('#organizer_form_field_' + that.current.id).index();
				}
				
				that.changeMadeToPanel();
			}
			
		}
	});
	/*
	$("#organizer_form_fields :input").on('mousedown', function (e) {
		var mdown = document.createEvent("MouseEvents");
		mdown.initMouseEvent("mousedown", true, true, window, 0, e.screenX, e.screenY, e.clientX, e.clientY, true, false, false, true, 0, null);
		$(this).closest('div.form_field')[0].dispatchEvent(mdown);
	});
	*/
};

// Remove no items message if exists
methods.removeNoItemsMessage = function(sectionName) {
	$('#organizer_section_' + sectionName)
		.removeClass('empty')
		.find('span.no_fields_message')
		.remove();
}

// Add a new field to the current section
methods.addNewField = function($field) {
	var form_field_id = 't' + this.maxNewCustomField++,
		dataset_field_id = $field.data('id'),
		dataset_field_tab_name = $field.data('tab_name'),
		field_label = 'Untitled',
		values_source = '',
		_translations = {};
	
	var newField = {
		form_field_id: form_field_id,
		field_label: field_label
	};
	
	if (dataset_field_id && dataset_field_tab_name) {
		newField.type = this.tuix.dataset_fields[dataset_field_tab_name].fields[dataset_field_id].type;
	} else {
		newField.type = $field.data('type');
		newField.name = 'Untitled ' + (this.getFieldReadableType(newField.type)).toLowerCase();
	}
	
	// Add initial details if dataset field
	if (dataset_field_id && dataset_field_tab_name) {
		datasetField = this.tuix.dataset_fields[dataset_field_tab_name].fields[dataset_field_id];
		newField.field_label = datasetField.label;
		newField.name = datasetField.label;
		newField.field_id = dataset_field_id;
		newField.lov = datasetField.lov;
	}
	
	// Remove no items message from tab
	this.removeNoItemsMessage(this.currentTab);
	
	// If new field is a multivalue, add an inital list of values
	if (!newField.lov && ($.inArray(newField.type, ['checkboxes', 'radios', 'select']) > -1)) {
		newField.lov = {};
		for (var i = 1; i <= 3; i++) {
			var valueId = 't' + (this.maxNewCustomFieldValue++),
				value = {
					is_new_value: true,
					id: valueId,
					ord: i,
					label: 'Option ' + i
				};
			newField.lov[valueId] = value;
		}
	
	// Load centralised LOV for preview
	} else if (!dataset_field_id && (newField.type == 'centralised_radios' || newField.type == 'centralised_select')) {
		foreach (this.tuix.centralised_lists.values as method) {
			values_source = method;
			break;
		}
		newField.lov = _.clone(this.tuix.centralised_lists.initial_lov);
	
	// Add a page breaks initial values
	} else if (newField.type== 'page_break') {
		newField.next_button_text = 'Next';
		newField.previous_button_text = 'Previous';
		newField.name = 'Page break ' + (++this.pageBreakCount);
	}
	
	mergeFields = _.clone(newField);
	if (newField.lov) {
		mergeFields.lov = this.getOrderedItemCRMLOV(newField.lov);
	}
	
	
	if (this.tuix.show_translation_tab) {
		_translations.field_label = {phrases: {}};
		if (newField.type == 'section_description') {
			_translations.description = {phrases: {}};
		}
		if (newField.type == 'text' || newField.type == 'textarea') {
			if (newField.type == 'text') {
				_translations.validation_error_message = {phrases: {}};
			}
			_translations.placeholder = {phrases: {}};
		}
		if (newField.type != 'page_break' || newField.type != 'section_description') {
			_translations.note_to_user = {phrases: {}};
			_translations.required_error_message = {phrases: {}};
		}
	}
	
	
	// Set HTML
	var html = zenarioA.microTemplate('zenario_organizer_form_builder_field', mergeFields);
	$field.replaceWith(html);
	
	// Add other properties to field
	var otherProperties = {
		is_new_field: true,
		just_added: true,
		values_source: values_source,
		readonly_or_mandatory: 'none',
		default_value_mode: 'none',
		visibility: 'visible',
		remove: false,
		_crm_data: {},
		_translations: _translations
	};
	$.extend(newField, otherProperties);
	
	// Add new field to list
	this.tuix.items[form_field_id] = newField;
	var $newField = $('#organizer_form_field_' + form_field_id);
	$newField.effect({effect: 'highlight', duration: 1000});
	
	// Init field
	this.initField($newField);
	
	// Open properties for new field
	this.fieldClick($newField);
	
	// Update list of linked fields
	if (dataset_field_id !== undefined && dataset_field_tab_name !== undefined) {
		this.initLinkedFieldsAdder();
	}
};


methods.fieldClick = function($field, tab) {
	var that = this,
		afterValidate = function(errors) {
			if (_.isEmpty(errors)) {
				var id = $field.data('id');
				
				// Clear values changed
				that.valuesChanged = false;
				
				// Select clicked field
				that.current = {type: 'field', id: id};
				that.currentFieldOrd = $field.index();
				
				// Add class to selected field
				$(that.formFieldsSelector).removeClass('selected');
				$field.addClass('selected');
				
				that.currentFieldTab = tab ? tab : 'details';
				
				that.setCurrentFieldDetails();
				
				// Show selected field delete button if exists
				$(that.formFieldInlineButtonsSelector).hide();
				$('#organizer_form_field_inline_buttons_' + that.current.id).show();
				
				// Show details panel
				that.showDetailsSection('organizer_field_details');
			}
		};
	
	this.save();
	if (cb = this.validate()) {
		cb.after(afterValidate);
	} else {
		afterValidate([]);
	}
};


methods.setCurrentFieldDetails = function() {
	var that = this,
		mergeFields = this.getCurrentFieldDetailsMergeFields(),
		html = zenarioA.microTemplate('zenario_organizer_form_builder_field_details', mergeFields);
	
	// Set HTML
	$('#organizer_field_details_inner').html(html);
	
	// Add JS events
	this.initCurrentFieldDetails();
};

methods.getCurrentFieldDetailsMergeFields = function() {
	var field = (this.tuix.items[this.current.id] || {}),
		mergeFields =  _.clone(field);
	
	// Pass the current tab
	mergeFields.currentFieldTab = this.currentFieldTab;
	
	// Get readable type
	mergeFields.formattedType = this.getFieldReadableType(mergeFields.type)
	if (mergeFields.field_id) {
		mergeFields.formattedType += ', dataset field';
	}
	
	
	// Check if CRM is enabled for this form, if so show tab
	mergeFields.crm_enabled = this.tuix.crm_enabled;
	
	var fieldId, fieldDetails,
		floatingPointFields = {},
		mirroredFields = {},
		filterOnFieldFields = {},
		conditionalFields = {};
		
	foreach (this.tuix.items as fieldId => fieldDetails) {
		// Get list of floating point fields
		if (['integer', 'number', 'floating_point'].indexOf(fieldDetails.field_validation) != -1) {
			floatingPointFields[fieldId] = _.clone(fieldDetails);
		}
		// Get fields that can be mirrored by mirror fields (restatement)
		if (
			[
				'checkbox',
				'checkboxes',
				'date',
				'editor',
				'radios',
				'centralised_radios',
				'select',
				'centralised_select',
				'text',
				'textarea',
				'url',
				'attachment',
				'calculated'
			].indexOf(fieldDetails.type) != -1
		) {
			mirroredFields[fieldId] = _.clone(fieldDetails);
		}
		// Get fields that can be used to filter centralised lists
		if (fieldDetails.type == 'centralised_select' && (fieldId != this.current.id)) {
			filterOnFieldFields[fieldId] = _.clone(fieldDetails);
		}
		
		// Get conditional fields
		if (
			([
				'checkbox', 
				'group',
				'radios', 
				'select', 
				'centralised_radios', 
				'centralised_select'
			].indexOf(fieldDetails.type) != -1)
			&& (fieldId != this.current.id)
		) {
			conditionalFields[fieldId] = {
				label: fieldDetails.name,
				ord: fieldDetails.ord
			}
		}
		
		
	}
	
	// Details tab
	
	// Create object to create a select list of centralised lists
	var centralised_lists = {},
		ord = 1;
	foreach (this.tuix.centralised_lists.values as func => details) {
		centralised_lists[func] = {
			ord: ord++,
			label: details.label
		}
	}
	mergeFields.values_source_options = this.createSelectList(centralised_lists, mergeFields.values_source);
	
	// Show filter if selected list can be filtered
	if (this.tuix.centralised_lists.values[mergeFields.values_source] 
		&& this.tuix.centralised_lists.values[mergeFields.values_source].info.can_filter
	) { 
		mergeFields.show_source_filter = true;
		mergeFields.source_filter_label = this.tuix.centralised_lists.values[mergeFields.values_source].info.filter_label;
	}
	
	var filter_on_field_options = {};
	foreach (filterOnFieldFields as fieldId => fieldDetails) {
		filter_on_field_options[fieldId] = {
			ord: fieldDetails.ord,
			label: fieldDetails.name
		};
	}
	mergeFields.filter_on_field_options = this.createSelectList(filter_on_field_options, mergeFields.filter_on_field, true);
	
	
	var readonly_or_mandatory_options = {
		none: {
			ord: 1,
			label: 'None'
		},
		mandatory: {
			ord: 2,
			label: 'Mandatory'
		},
		readonly: {
			ord: 3,
			label: 'Read-only'
		},
		conditional_mandatory: {
			ord: 4,
			label: 'Mandatory on condition'
		}
	};
	mergeFields.readonly_or_mandatory_options = this.createSelectList(readonly_or_mandatory_options, mergeFields.readonly_or_mandatory);
	
	
	// Mandatory on condition field options
	mergeFields.mandatory_condition_field_id_options = this.createSelectList(conditionalFields, mergeFields.mandatory_condition_field_id, true);
	
	
	// Get conditional field values
	var conditionField = this.tuix.items[mergeFields.mandatory_condition_field_id],
		conditionalFieldValues = {},
		emptyValue = true;
	if (conditionField) {
		if (conditionField.type == 'checkbox' || conditionField.type == 'group') {
			conditionalFieldValues = {
				0: {
					label: 'Unchecked',
					ord: 1
				},
				1: {
					label: 'Checked',
					ord: 2
				}
			};
		} else {
			conditionalFieldValues = conditionField.lov;
			emptyValue = '-- Any value --';
		}
	}
	
	// Mandatory on condition field value options
	if (mergeFields.mandatory_condition_field_id !== '' && mergeFields.mandatory_condition_field_id !== undefined) {
		mergeFields.mandatory_condition_field_value_options = this.createSelectList(
			conditionalFieldValues, 
			mergeFields.mandatory_condition_field_value,
			emptyValue
		);
	}
	
	// Visibility options
	var visibility_options = {
		visible: {
			ord: 1,
			label: 'Visible'
		},
		hidden: {
			ord: 2,
			label: 'Hidden'
		},
		visible_on_condition: {
			ord: 3,
			label: 'Visible on condition'
		}
	};
	mergeFields.visibility_options = this.createSelectList(visibility_options, mergeFields.visibility);
	
	// Visible on condition field options
	mergeFields.visible_condition_field_id_options = this.createSelectList(conditionalFields, mergeFields.visible_condition_field_id, true);
	
	// Get conditional field values
	var conditionField = this.tuix.items[mergeFields.visible_condition_field_id],
		conditionalFieldValues = {};
	emptyValue = true;
	if (conditionField) {
		if (conditionField.type == 'checkbox' || conditionField.type == 'group') {
			conditionalFieldValues = {
				0: {
					label: 'Unchecked',
					ord: 1
				},
				1: {
					label: 'Checked',
					ord: 2
				}
			};
		} else {
			conditionalFieldValues = conditionField.lov;
			emptyValue = '-- Any value --';
		}
	}
	
	// Visible if options
	if (mergeFields.visible_condition_field_id !== '' && mergeFields.visible_condition_field_id !== undefined) {
		mergeFields.visible_condition_field_value_options = this.createSelectList(
			conditionalFieldValues, 
			mergeFields.visible_condition_field_value,
			emptyValue
		);
	}
	
	// Validation options
	var validation_options = {
		none: {
			ord: 1,
			label: 'None'
		},
		email: {
			ord: 2,
			label: 'Email'
		},
		URL: {
			ord: 3,
			label: 'URL'
		},
		number: {
			ord: 4,
			label: 'Number'
		},
		integer: {
			ord: 5,
			label: 'Integer'
		},
		floating_point: {
			ord: 6,
			label: 'Floating point'
		}
	};
	mergeFields.validation_options = this.createSelectList(validation_options, mergeFields.field_validation);
	
	// Size options
	var size_options = {
		small: {
			ord: 1,
			label: 'Small'
		},
		medium: {
			ord: 2,
			label: 'Medium'
		},
		large: {
			ord: 3,
			label: 'Large'
		}
	};
	mergeFields.size_options = this.createSelectList(size_options, mergeFields.size, true);
	
	// Numeric field A options
	// Numeric field B options
	var numeric_field_options = {};
	foreach (floatingPointFields as fieldId => fieldDetails) {
		numeric_field_options[fieldId] = {
			ord: fieldDetails.ord,
			label: fieldDetails.name
		};
	}
	mergeFields.numeric_field_1_options = this.createSelectList(numeric_field_options, mergeFields.numeric_field_1, true);
	mergeFields.numeric_field_2_options = this.createSelectList(numeric_field_options, mergeFields.numeric_field_2, true);
	
	// Calculation type options
	var calculation_type_options = {
		'+': {
			ord: 1,
			label: '+'
		},
		'-': {
			ord: 2,
			label: '-'
		}
	};
	mergeFields.calculation_type_options = this.createSelectList(calculation_type_options, mergeFields.calculation_type);
	// Field to mirror options
	var restatement_field_options = {};
	foreach (mirroredFields as fieldId => fieldDetails) {
		restatement_field_options[fieldId] = {
			ord: fieldDetails.ord,
			label: fieldDetails.name
		};
	}
	mergeFields.restatement_field_options = this.createSelectList(restatement_field_options, mergeFields.restatement_field);
	
	// Advanced tab
	
	// Default value mode options
	var default_value_mode_options = {
		none: {
			ord: 1,
			label: 'No default value'
		},
		value: {
			ord: 2,
			label: 'Enter a default value'
		},
		method: {
			ord: 3,
			label: 'Call a modules static method to get the default value'
		}
	};
	mergeFields.default_value_mode_options = this.createRadioList(default_value_mode_options, mergeFields.default_value_mode, 'default_value_mode');
	
	// Default value lov options
	var default_value_lov_options = {};
	if (mergeFields.type == 'checkbox' || mergeFields.type == 'group') {
		default_value_lov_options = {
			0: {
				ord: 1,
				label: 0
			},
			1: {
				ord: 2,
				label: 1
			}
		};
	} else if (['radios', 'centralised_radios', 'select', 'centralised_select'].indexOf(mergeFields.type) != -1) {
		default_value_lov_options = mergeFields.lov;
	}
	mergeFields.default_value_lov_options = this.createSelectList(default_value_lov_options, mergeFields.default_value);
	
	
	// Translations tab
	mergeFields.translatable_fields = this.getOrderedTranslations(mergeFields._translations);
	
	// CRM tab
	mergeFields.hasCRMValues = this.fieldCanHaveCRMValues(mergeFields.type);
	if (mergeFields.hasCRMValues) {
		if (mergeFields.type == 'checkbox') {
			var crm_values = {
				0: {
					ord: 1,
					label: 0,
					crm_value: 0
				},
				1: {
					ord: 2,
					label: 1,
					crm_value: 1
				}
			};
			if (mergeFields._crm_data && mergeFields._crm_data.values) {
				crm_values = mergeFields._crm_data.values;
			}
			mergeFields.crm_values = this.getOrderedItemCRMLOV(crm_values);
		} else {
			var useLabelsForNewValues = !mergeFields.field_id && (mergeFields.type == 'select' || mergeFields.type == 'radios');
			mergeFields.crm_values = this.getOrderedItemCRMLOV(field.lov, useLabelsForNewValues);
		}
	}
	
	// find what detail tabs to show for this field
	mergeFields.showDetailsTab = true;
	mergeFields.showAdvancedTab = (mergeFields.type != 'page_break' && mergeFields.type != 'section_description');
	mergeFields.showValuesTab = (['select', 'radios', 'checkboxes'].indexOf(mergeFields.type) != -1);
	mergeFields.showTranslationsTab = this.tuix.show_translation_tab && mergeFields.type != 'page_break';
	mergeFields.showCRMTab = (mergeFields.crm_enabled && mergeFields.type != 'page_break' && mergeFields.type != 'section_description');
	
	
	return mergeFields;
};

methods.initCurrentFieldDetails = function() {
	
	var that = this,
		field = (this.tuix.items[this.current.id] || {});
	
	// Listen for changes
	$('#organizer_field_details :input').off().on('change', function() {
		that.changeMadeToPanel();
	});
	$('#organizer_field_details input[type="text"], #organizer_field_details textarea, #zenario_field_details_header_content input[type="text"]').on('keyup', function() {
		that.changeMadeToPanel();
	});
	
	// Update preview label
	$('#field__field_label').on('keyup', function() {
		var val = $(this).val();
		$('#organizer_form_field_' + that.current.id + ' .label').text(val);
		
		// Update name if new field
		if (field.just_added) {
			$('#field__name').val(val);
		}
		
	});
	
	if (!field.field_id) {
		// Show editable field name when clicking edit button
		$('#zenario_field_details_header_content .edit_field_name_button').off().on('click', function() {
			$('#zenario_field_details_header_content .view_mode').hide();
			$('#zenario_field_details_header_content .edit_mode').show();
		});
		
		// Back to HTML view of field name
		$('#zenario_field_details_header_content .done_field_name_button').off().on('click', function() {
			$('#zenario_field_details_header_content .edit_mode').hide();
			$('#zenario_field_details_header_content .view_mode').show();
			
			var name = $('#zenario_field_details_header_content .edit_mode input[name="name"]').val();
			$('#zenario_field_details_header_content .view_mode h5').text(name);
		});
	}
	
	// Update note to user
	$('#field__note_to_user').on('keyup', function() {
		var value = $(this).val(),
			$note_to_user = $('#organizer_form_field_note_below_' + that.current.id),
			$note_to_user_content = $('#organizer_form_field_note_below_' + that.current.id + ' div.zenario_note_content');
			
		$note_to_user_content.html(value);
		$note_to_user.toggle(value !== '');
	});
	
	// Update preview placeholder
	if (field.type == 'text' || field.type == 'textarea') {
		$('#field__placeholder').on('keyup', function() {
			$('#organizer_form_field_' + that.current.id + ' :input').prop('placeholder', $(this).val());
		});
	} else if (field.type == 'section_description') {
		$('#field__description').on('keyup', function() {
			$('#organizer_form_field_' + that.current.id + ' .description').text($(this).val());
		});
	}
	
	// Update centralised radio list preview when changing source, set filter to blank
	$('#field_container__values_source :input').on('change', function() {
		
		var $source_filter = $('#field_container__values_source_filter :input');
		$source_filter.val('');
		
		var method = $(this).val(),
			filter = '';
		that.centralisedListUpdated(that.current.id, method, filter);
	});
	
	var delay = (function() {
		var timer = 0;
		return function(callback, ms) {
			clearTimeout(timer)
			timer = setTimeout(callback, ms);
		};
	})();
	
	$('#field_container__values_source_filter :input').on('keyup', function() {
		var $source = $('#field_container__values_source :input');
		
		var method = $source.val(),
			filter = $(this).val();
			
		delay(function() {
			that.centralisedListUpdated(that.current.id, method, filter);
		}, 1000);
	});
	
	
	
	// formatFieldDetails onchange
	var formatOnChangeSelector = '';
	for (var i = 0; i < this.formatOnChange.length; i++) {
		if (i != 0) {
			formatOnChangeSelector += ', ';
		}
		formatOnChangeSelector += '#field_container__' + this.formatOnChange[i] + ' :input';
	}
	$(formatOnChangeSelector).on('change', function() {
		that.formatFieldDetails();
	});
	
	// Sort LOV by ordinal
	var lov = this.getOrderedItems(field.lov);
	
	// Dataset fields values tab should not be editable
	if (field.field_id) {
		for (var i in lov) {
			lov[i].disabled = true;
		}
	}
	
	// Place LOV on page
	var html = zenarioA.microTemplate('zenario_organizer_admin_box_builder_field_value', lov),
		$field_values_list = $('#field_values_list');
	
	$field_values_list.html(html);
	
	// Dataset fields values tab should not be editable
	if (!field.field_id) {
		$field_values_list.sortable({
			axis: 'y',
			start: function(event, ui) {
				that.startIndex = ui.item.index();
			},
			stop: function(event, ui) {
				if (that.startIndex != ui.item.index()) {
					that.save();
					that.setCurrentFieldValues(field);
					that.changeMadeToPanel();
				}
			}
		});
		
		// Bind events
		this.initFieldValues(field);
		
		// Setup add value button
		this.initAddNewFieldValuesButton(field);
	}
	
	
	if (field.type == 'centralised_select' || field.type == 'centralised_radios') {
		// Set all CRM values to labels
		$('#organizer_crm_button__set_labels').on('click', function() {
			$('#field_section__crm input.crm_value_input').each(function(i, e) {
				var id = $(this).data('id'),
					value = '';
				
				if (that.tuix.items[that.current.id].lov && that.tuix.items[that.current.id].lov[id]) {
					value = that.tuix.items[that.current.id].lov[id].label;
				}
				
				$(this).val(value);
			});
		});
		// Set all CRM values to values
		$('#organizer_crm_button__set_values').on('click', function() {
			$('#field_section__crm input.crm_value_input').each(function(i, e) {
				var id = $(this).data('id');
				$(this).val(id);
			});
		});
	}
	
	
	// Init new fields adder
	this.initAddNewFieldsButton();
	
	this.initFormSettingsButton();
};

methods.fieldCanHaveCRMValues = function(type) {
	return (['select', 'radios', 'checkboxes', 'centralised_select', 'centralised_radios', 'checkbox'].indexOf(type) != -1);
};

methods.getFieldReadableType = function(type) {
	switch (type) {
		case 'checkbox':
			return 'Checkbox';
		case 'checkboxes':
			return 'Checkboxes';
		case 'date':
			return 'Date';
		case 'editor':
			return 'Editor';
		case 'radios':
			return 'Radios';
		case 'centralised_radios':
			return 'Centralised radios';
		case 'select':
			return 'Select';
		case 'centralised_select':
			return 'Centralised select';
		case 'text':
			return 'Text';
		case 'textarea':
			return 'Textarea';
		case 'url':
			return 'URL';
		case 'attachment':
			return 'Attachment';
		case 'page_break':
			return 'Page break';
		case 'section_description':
			return 'Subheading';
		case 'calculated':
			return 'Calculated';
		case 'restatement':
			return 'Mirror';
		// Groups unique to dataset fields
		case 'group':
			return 'Group';
		case 'file_picker':
			return 'File picker';
		default:
			return 'Unknown';
	}
};

// Called when a centralised list is updated for a field
methods.centralisedListUpdated = function(fieldId, method, filter) {
	
	// Get the new values of the centralised list
	var that = this;
	var actionRequests = {
		mode: 'get_centralised_lov',
		method: method,
		filter: filter,
		type: 'object'
	};
	this.sendAJAXRequest(actionRequests).after(function(data) {
		
		
		var lov = JSON.parse(data)
		that.tuix.items[fieldId].lov = lov;
		that.valuesChanged = true;
		
		// Update the field preview
		lov = that.getOrderedItemCRMLOV(lov);
		var html = zenarioA.microTemplate('zenario_organizer_admin_box_builder_radio_values_preview', lov);
		$('#organizer_form_field_values_' + fieldId).html(html);
		
		
	});
};

// Get the current values for a field
methods.getCurrentFieldDetails = function() {
	var field = {},
		current = this.tuix.items[this.current.id];
	
	// Get input values from details form (ignore disabled, button and crm inputs)
	$.each($('#organizer_field_details_form :input:not([disabled], [type=button], .crm_value_input, .translation)').serializeArray(), function(index, input) {
		if (input.name) {
			field[input.name] = input.value;
		}
	});
	
	// Because serializeArray() ignores unset checkboxes and radio buttons
	$('#organizer_field_details_form input[type=checkbox]').map(function(index, input) {
		field[input.name] = input.checked ? 1 : 0;
	});
	
	if (field.default_value_lov !== undefined) {
		field.default_value = field.default_value_lov;
	} else if (field.default_value_text !== undefined) {
		field.default_value = field.default_value_text;
	}
	
	// Save multi value fields lovs
	if ((['select', 'radios', 'checkboxes'].indexOf(current.type) != -1) && !current.field_id) {
		field.lov = this.getCurrentFieldValues(current);
	}
	
	// Save any translations
	if (this.tuix.show_translation_tab) {
		$('#field_section__translations input.translation').map(function(index, input) {
			var language_id = $(this).data('language_id'),
				field_column = $(this).data('field_column');
			
			if (current._translations && current._translations[field_column]) {
				current._translations[field_column].phrases[language_id] = input.value;
			}
		});
	}
	
	// Save any CRM values
	if (this.fieldCanHaveCRMValues(current.type)) {
		
		if (!field.lov) {
			field.lov = current.lov ? _.clone(current.lov) : {};
		}
		
		if (current.type == 'checkbox') {
			current._crm_data.values = {
				0: {
					ord: 1,
					label: 0,
					crm_value: 0
				},
				1: {
					ord: 2,
					label: 1,
					crm_value: 1
				}
			};
		}
		
		$('#field_section__crm input.crm_value_input').map(function(index, input) {
			var id = $(this).data('id');
			
			if (current.type == 'checkbox') {
				current._crm_data.values[id].crm_value = $(this).val();
			} else {
				if (!field.lov[id] || field.lov[id].remove) {
					return;
				}
				field.lov[id].crm_value = $(this).val();
			}
		});
	}
	
	return field;
};



// Called whenever the properties of a field needs to be saved
methods.save = function() {
	var values = {};
	if (this.current.id) {
		values = this.getCurrentFieldDetails();
		if (!this.tuix.items[this.current.id]) {
			this.tuix.items[this.current.id] = {};
		} else if (this.tuix.items[this.current.id].remove) {
			return;
		}
		foreach (values as id => value) {
			if (id == 'field_crm_name' || id == 'send_to_crm') {
				this.tuix.items[this.current.id]['_crm_data'][id] = value;
			} else {
				this.tuix.items[this.current.id][id] = value;
			}
		}
	}
};

// Called to validate properties of a field/tab when moving off current item
methods.validate = function(removingField) {
	var that = this,
		cb = new zenario.callback,
		actionRequests = {
			mode: 'validate_field',
			id: this.current.id,
			field_tab: this.currentFieldTab,
			items: JSON.stringify(this.tuix.items)
		};
		
	if (removingField === true) {
		actionRequests.removingField = true;
	}
	
	
	if (this.current.id && (!this.tuix.items[this.current.id].remove)) {
		
		this.sendAJAXRequest(actionRequests).after(function(errors) {
			errors = JSON.parse(errors);
			
			// Remove previous errors
			$('#organizer_form_field_error').remove();
			
			if (errors.length > 0) {
				
				// Display errors
				var $errorDiv = $('<div id="organizer_form_field_error"></div>');
				foreach (errors as index => message) {
					$errorDiv.append('<p class="error">' + message + '</p>');
				}
				
				$('#organizer_field_details').prepend($errorDiv);
				
				that.shakeBox('#organizer_form_builder .form_fields_palette');
				
			} else {
				delete(that.tuix.items[that.current.id].just_added);
			}
			
			cb.call(errors);
		});
		return cb;
	}
	return false;
};


methods.saveItemsOrder = function() {
	var that = this;
	$(this.formFieldsSelector).each(function(j, field) {
		var id = $(field).data('id');
		that.tuix.items[id].ord = (j + 1);
	});
};

methods.initFormSettingsButton = function() {
	var that = this;
	$('input.form_settings').off().on('click', function() {
		
		// Open form settings adminbox
		zenarioAB.open(
			'zenario_user_admin_box_form', 
			{id: zenarioO.pi.refiner.id},
			undefined, undefined,
			function(key, values) {
				// Update form title (note if lots of changes might be better to redraw entire form)
				var title = values.details.title;
				this.tuix.title = title;
				$('#organizer_form_builder .form_outer .form_header h5').text(title);
			}
		);
	});
};

methods.showFieldDetailsSection = function(section, noValidation) {
	var that = this,
		cb,
		afterValidate = function(errors) {
			if (_.isEmpty(errors)) {
				that.currentFieldTab = section;
				
				// Mark current tab
				$('#organizer_field_details_tabs div.tab').removeClass('on');
				$('#field_tab__' + section).addClass('on');
				
				// Show current section
				$('#organizer_field_details div.section').hide();
				$('#field_section__' + section).show();
			}
		};
	
	if (this.valuesChanged && section == 'crm') {
		this.fieldClick($('#organizer_form_field_' + this.current.id), 'crm');
	} else if (this.changingForm && section == 'translations') {
		this.fieldClick($('#organizer_form_field_' + this.current.id), 'translations');
	} else {
		if (!noValidation) {
			this.save();
			if (cb = this.validate()) {
				cb.after(afterValidate);
				return;
			}
		}
		afterValidate([]);
	}
};


}, zenarioO.panelTypes);
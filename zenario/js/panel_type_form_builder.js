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
	
	For more information, see js_minify.shell.php for steps (1) and (2), and organizer.wrapper.js.php for step (3).
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
	panelTypes.form_builder = extensionOf(panelTypes.form_builder_base_class)
);

//Called by Organizer upon the first initialisation of this panel.
//It is not recalled if Organizer's refresh button is pressed, or the administrator changes page
methods.init = function() {
	// Warning message when trying to leave without saving changes
	this.saveChangesWarningMessage = 'You are currently editing this form. If you leave now you will lose any unsaved changes.';
	
	// Whether there are any local changes
	this.changingForm = false;
	
	// Fields which cause details panel to update on change
	this.formatOnChange = [
		'readonly_or_mandatory',
		'mandatory_condition_field_id',
		'visibility',
		'visible_condition_field_id',
		'validation',
		'default_value_options'
	];
	
	// Selector for all form fields
	this.formFieldsSelector = '#organizer_form_fields .form_field';
	
	// Selector for form fields inline buttons
	this.formFieldInlineButtonsSelector = '#organizer_form_fields .form_field_inline_buttons';
};

// Change objects into ordered array
methods.getOrderedItems = function(items) {
	var item, id, field,
		orderedItems = [];
	
	// Loop through each field
	foreach (items as id => field) {
		var fieldClone = _.clone(field);
		if (!field.remove) {
			// Check if field has a list of values
			if (fieldClone['lov']) {
				var values = [];
				// If so store them in array
				foreach (fieldClone['lov'] as id => value) {
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
	orderedItems.sort(this.sortByOrd)
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
	
	var item,
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
		if (tab['fields']) {
			item['fields'] = [];
			// Loop through each field
			foreach (tab['fields'] as id => field) {
				// Store fields in array if not used on form already
				if (datasetFieldsOnForm[id] !== true) {
					item['fields'].push(field);
				}
			}
			// Sort fields
			item['fields'].sort(this.sortByOrd)
		}
		// Store tabs in array
		orderedItems.push(item);
	}
	// Sort tabs
	orderedItems.sort(this.sortByOrd);
	return orderedItems;
};


//Draw the panel, as well as the header at the top and the footer at the bottom
//This is called every time the panel is loaded, refreshed or when something in the header toolbar is changed.
methods.showPanel = function($header, $panel, $footer) {
	
	var that = this,
		items = this.getOrderedItems(this.tuix.items),
		mergeFields = {
			items: items
		},
		html = zenarioA.microTemplate('zenario_organizer_form_builder', mergeFields);
	
	this.currentFieldTab = 'details';
	this.currentFieldTypeTab = 'unlinked';
	this.maxNewCustomTab = 1;
	this.maxNewCustomField = 1;
	this.maxNewCustomFieldValue = 1;
	this.current = {id: false, type: false};
	this.current_db_columns = this.tuix.existing_db_columns;
	this.deleting = false;
	
	$header.html('').hide();
	$panel.html(html).show();
	$footer.html('').show();
	
	this.initSection();
	
	this.orderedItems = items;
	
	// Make unlinked fields palette draggable
	$('#organizer_field_type_list div.field_type, #organizer_centralised_field_type_list div.field_type').draggable({
		connectToSortable: '#organizer_form_fields',
		helper: function() {
			var type = $(this).data('type');
			return $('<div>[---PLACEHOLDER---]</div>').data('type', type).addClass('preview preview_' + type);
		}
	});
	
	this.initLinkedFieldsAdder();
	
	// Edit a form field
	$(this.formFieldsSelector).on('click', function() {
		that.fieldClick($(this));
	});
	
	this.initDeleteButtons();
};

methods.initLinkedFieldsAdder = function() {
	var that = this;
	
	// Filter out fields already included on the form and order by ordinal
	dataset_fields = this.getOrderedDatasetFields(this.tuix.dataset_fields);
	
	// Set linked fields HTML
	var html = zenarioA.microTemplate('zenario_organizer_form_builder_dataset_tab', dataset_fields);
	$('#organizer_linked_field_type_list').html(html);
	
	// Make linked fields palette draggable
	$('#organizer_linked_field_type_list div.dataset_field').draggable({
		connectToSortable: '#organizer_form_fields',
		helper: function() {
			var id = $(this).data('id'),
				tab_name = $(this).data('tab_name'),
				type = that.tuix.dataset_fields[tab_name].fields[id].type;
			
			return $('<div>[---PLACEHOLDER---]</div>')
				.data('type', type)
				.data('id', id)
				.data('tab_name', tab_name)
				.addClass('preview preview_' + type);
		}
	});
};

methods.initDeleteButtons = function() {
	var that = this;
	$('#organizer_form_fields .delete_icon').off().on('click', function(e) {
		that.deleting = true;
		zenarioA.showMessage('Are you sure you want to delete this Field?', 'Delete', 'warning', true);
		that.listenForDelete($(this).data('id'));
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
	//TODO is validation nessesary here??
	var that = this,
		afterValidate = function(errors) {
			if (_.isEmpty(errors)) {
				var $field, $next, field_id;
				
				if (id === undefined) {
					id = that.current.id;
				}
				
				// Remember dataset field ID
				field_id = that.tuix.items[id].field_id;
				
				// Mark for deletion
				that.tuix.items[id] = {remove: true};
				$field = $('#organizer_form_field_' + id);
				
				// Update linked fields list if dataset field
				if (field_id) {
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
		// Add new field to the form
		receive: function(event, ui) {
			$(this).find('div.preview').each(function() {
				that.addNewField($(this));
			});
		},
		start: function(event, ui) {
			that.startIndex = ui.item.index();
		},
		// Detect reorder/new/deleted fields
		stop: function(event, ui) {
			if (that.startIndex != ui.item.index()) {
				that.changeMadeToPanel();
			}
			
		}
	});
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
		type = $field.data('type'),
		dataset_field_id = $field.data('id'),
		dataset_field_tab_name = $field.data('tab_name'),
		field_label = 'Untitled',
		mergeFields = {
			form_field_id: form_field_id,
			type: type,
			field_label: field_label
		},
		values_source = '';
	
	// Add initial details if dataset field
	if (dataset_field_id !== undefined && dataset_field_tab_name !== undefined) {
		datasetField = this.tuix.dataset_fields[dataset_field_tab_name].fields[dataset_field_id];
		mergeFields['field_label'] = datasetField['label'];
		mergeFields['name'] = datasetField['label'];
		mergeFields['field_id'] = dataset_field_id;
	}
	
	// Remove no items message from tab
	this.removeNoItemsMessage(this.currentTab);
	
	// If new field is a multivalue, add an inital list of values
	if ($.inArray(type, ['checkboxes', 'radios', 'select']) > -1) {
		mergeFields.lov = [];
		for (var i = 1; i <= 3; i++) {
			var valueId = this.maxNewCustomFieldValue++,
				value = {
					is_new_value: true,
					id: 't' + valueId,
					ord: i,
					label: 'Option ' + i
				};
			mergeFields.lov[i] = value;
		}
	}
	
	// Load centralised LOV for preview
	if (type == 'centralised_radios') {
		foreach (this.tuix.centralised_lists.values as method) {
			values_source = method;
			break;
		}
		mergeFields.lov = _.toArray(this.tuix.centralised_lists.initial_lov);
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
		remove: false
	};
	$.extend(mergeFields, otherProperties);
	
	// Add new field to list
	this.tuix.items[form_field_id] = mergeFields;
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


methods.fieldClick = function($field) {
	var that = this,
		afterValidate = function(errors) {
			if (_.isEmpty(errors)) {
				var id = $field.data('id');
				
				// Select clicked field
				that.current = {type: 'field', id: id};
				
				// Add class to selected field
				$(that.formFieldsSelector).removeClass('selected');
				$field.addClass('selected');
				
				that.currentFieldTab = 'details';
				
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
		values = (this.tuix.items[this.current.id] || {}),
		field = _.clone(values);
	
	// Pass the current tab
	field.currentFieldTab = this.currentFieldTab;
	
	field.centralised_lists = this.tuix.centralised_lists;
	
	//TODO add all fields
	
	// Details tab
	
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
	field.readonly_or_mandatory_options = this.createSelectList(readonly_or_mandatory_options, field.readonly_or_mandatory);
	
	// Mandatory on condition field options
	field.mandatory_condition_field_id_options = this.createSelectList(this.tuix.conditional_fields, field.mandatory_condition_field_id, true);
	
	// Mandatory on condition field value options
	if (field.mandatory_condition_field_id && this.tuix.conditional_fields_values[field.mandatory_condition_field_id]) {
		field.mandatory_condition_field_value_options = this.createSelectList(
			this.tuix.conditional_fields_values[field.mandatory_condition_field_id], 
			field.mandatory_condition_field_value,
			true
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
	field.visibility_options = this.createSelectList(visibility_options, field.visibility);
	
	// Visible on condition field options
	field.visible_condition_field_id_options = this.createSelectList(this.tuix.conditional_fields, field.visible_condition_field_id, true);
	
	// Visible if options
	if (field.visible_condition_field_id && this.tuix.conditional_fields_values[field.visible_condition_field_id]) {
		field.visible_condition_field_value_options = this.createSelectList(
			this.tuix.conditional_fields_values[field.visible_condition_field_id], 
			field.visible_condition_field_value,
			true
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
	field.validation_options = this.createSelectList(validation_options, field.field_validation);
	
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
	field.size_options = this.createSelectList(size_options, field.size, true);
	
	// Numeric field A options
	// Numeric field B options
	var floatingPointFields = this.getFloatingPointFields();
	var numeric_field_options = {};
	foreach (floatingPointFields as id => floatingPointField) {
		numeric_field_options[id] = {
			ord: floatingPointField.ord,
			label: floatingPointField.name
		};
	}
	field.numeric_field_1_options = this.createSelectList(numeric_field_options, field.numeric_field_1, true);
	field.numeric_field_2_options = this.createSelectList(numeric_field_options, field.numeric_field_2, true);
	
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
	field.calculation_type_options = this.createSelectList(calculation_type_options, field.calculation_type);
	// Field to mirror options
	var mirroredFields = this.getMirroredFields();
	var restatement_field_options = {};
	foreach (mirroredFields as id => mirroredField) {
		restatement_field_options[id] = {
			ord: mirroredField.ord,
			label: mirroredField.name
		};
	}
	field.restatement_field_options = this.createSelectList(restatement_field_options, field.restatement_field);
	
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
	field.default_value_mode_options = this.createRadioList(default_value_mode_options, field.default_value_mode, 'default_value_mode');
	
	// Default value lov options
	field.default_value_lov_options = this.createSelectList(field.lov, field.default_value);
	
	
	
	
	// Get HTML
	var html = zenarioA.microTemplate('zenario_organizer_form_builder_field_details', field);
	$('#organizer_field_details_inner').html(html);
	
	
	
	
	// Listeners
	// Listen for changes
	$('#organizer_field_details :input').off().on('change', function() {
		that.changeMadeToPanel();
	});
	$('#organizer_field_details input[type="text"]').on('keyup', function() {
		that.changeMadeToPanel();
	});
	
	// Update label and code name
	$('#field__field_label').on('keyup', function() {
		$('#organizer_form_field_' + that.current.id + ' .label').text($(this).val());
	});
	
	
	
	// formatFieldDetails onchange
	var formatOnChangeSelector = '';
	for (var i = 0; i < this.formatOnChange.length; i++) {
		if (i != 0) {
			formatOnChangeSelector += ', ';
		}
		formatOnChangeSelector += '#field__' + this.formatOnChange[i] + ' :input';
	}
	$(formatOnChangeSelector).on('change', function() {
		that.formatFieldDetails();
	});
	
	// Init new fields adder
	this.initAddNewFieldsButton();
	
	// Sort LOV by ordinal
	var lov = this.getOrderedItems(field.lov);
	
	// Place LOV on page
	var html = zenarioA.microTemplate('zenario_organizer_admin_box_builder_field_value', lov);
	$('#field_values_list')
		.html(html)
		.sortable({
			start: function(event, ui) {
				that.startIndex = ui.item.index();
			},
			stop: function(event, ui) {
				that.save();
				that.setCurrentFieldValues();
				if (that.startIndex != ui.item.index()) {
					that.changeMadeToPanel();
				}
			}
		});
	
	// Bind events
	this.initFieldValues();
	
	// Setup add value button
	this.initAddNewFieldValuesButton();
};


methods.initAddNewFieldValuesButton = function() {
	var that = this;
	$('#organizer_add_a_field_value').on('click', function() {
		
		// Save current values
		that.save();
		
		// Save new value
		var field = that.tuix.items[that.current.id],
			id = 't' + that.maxNewCustomFieldValue++,
			value = {
				id: id,
				label: 'Untitled',
				ord: _.size(field['lov']) + 100,
				is_new_value: true
			};
		field.lov[id] = value;
		
		// Redraw list to include new field
		that.setCurrentFieldValues();
		that.initFieldValues();
		that.changeMadeToPanel();
	});
};


methods.getFloatingPointFields = function() {
	var floatingPointFields = {};
	foreach (this.tuix.items as id => field) {
		if (['integer', 'number', 'floating_point'].indexOf(field.field_validation) != -1) {
			floatingPointFields[id] = _.clone(field);
		}
	}
	return floatingPointFields;
};

methods.getMirroredFields = function() {
	var mirroredFields = {};
	foreach (this.tuix.items as id => field) {
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
			].indexOf(field.type) != -1
		) {
			mirroredFields[id] = _.clone(field);
		}
	}
	return mirroredFields;
};


methods.updateCentralisedRadioValues = function($values_source, $values_source_filter, $values_source_filter_container, values) {
	
	var actionRequests = {
		mode: 'get_centralised_lov',
		method: $values_source.val()
	};
	
	if (actionRequests.method && $values_source_filter_container.is(':visible')) {
		actionRequests.filter = $values_source_filter.val();
	}
	
	this.sendAJAXRequest(actionRequests).after(function(data) {
		var lov = JSON.parse(data),
			html = zenarioA.microTemplate('zenario_organizer_admin_box_builder_radio_values_disabled', lov);
		$('#organizer_form_field_values_' + values.id).html(html);
	});
};

methods.setCurrentFieldValues = function() {
	
	var id = this.current.id,
		field = this.tuix.items[id],
		items = field.lov,
		mergeFields = this.getOrderedItems(items),
		html = '';
	
	var html = zenarioA.microTemplate('zenario_organizer_admin_box_builder_field_value', mergeFields);
	$('#field_values_list').html(html);
	
	if ($.inArray(field.type, ['checkboxes', 'radios']) > -1) {
		if (field.type == 'checkboxes') {
			html = zenarioA.microTemplate('zenario_organizer_admin_box_builder_checkbox_values', mergeFields);
		} else if (field.type == 'radios') {
			html = zenarioA.microTemplate('zenario_organizer_admin_box_builder_radio_values_disabled', mergeFields);
		}
		$('#organizer_form_field_values_' + id).html(html);
	}
};

methods.initFieldValues = function() {
	var that = this;
	// Handle remove button
	$('#field_values_list div.remove').off().on('click', function() {
		var id = $(this).data('id');
		// Remove from stored values
		that.tuix.items[that.current.id].lov[id] = {remove: true};
		// Remove from preview
		$('#organizer_field_value_' + id).remove();
		// Remove from details section
		$(this).parent().remove();
		that.changeMadeToPanel();
	});
	
	// Update form labels
	$('#field_values_list input').off().on('keyup', function() {
		var id = $(this).data('id');
		$('#organizer_field_value_' + id + ' label').text($(this).val());
		that.changeMadeToPanel();
	});
};

methods.getCurrentFieldValues = function() {
	var that = this,
		field = this.tuix.items[that.current.id];
		lov = field.lov;
	$('#field_values_list div.field_value').each(function(i, value) {
		var id = $(this).data('id');
		lov[id] = {
			id: id,
			label: $(value).find('input').val(),
			ord: i + 1
		}
		if (field.lov[id]
			&& field.lov[id].is_new_value
		) {
			lov[id].is_new_value = true;
		}
	});
	return lov;
};

// Get the current values for a field
methods.getCurrentFieldDetails = function() {
	var field = {},
		current = this.tuix.items[this.current.id];
	
	
	$.each($('#organizer_field_details_form').serializeArray(), function(index, input) {
		field[input.name] = input.value;
	});
	
	if (field.default_value_lov !== undefined) {
		field.default_value = field.default_value_lov;
	} else if (field.default_value_text !== undefined) {
		field.default_value = field.default_value_text;
	}
	
	// Values tab
	if ((['select', 'radios', 'checkboxes'].indexOf(current.type) != -1) && !current.field_id) {
		field.lov = this.getCurrentFieldValues();
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
		}
		foreach (values as id => value) {
			this.tuix.items[this.current.id][id] = value;
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
			items: JSON.stringify(this.tuix.items)
		};
		
	if (removingField === true) {
		actionRequests.removingField = true;
	}
	
	
	if (this.current.id && (!this.tuix.items[this.current.id].remove)) {
		//TODO
		//stop adding html manually and call microtemplate function
		//do individual tabs? this.currentFieldTab
		this.sendAJAXRequest(actionRequests).after(function(errors) {
			errors = JSON.parse(errors);
			if (errors.length > 0) {
				
				// Display errors
				var $errorDiv = $('<div id="organizer_form_field_error" class="error"></div>');
				foreach (errors as index => message) {
					$errorDiv.append('<p>' + message + '</p>');
				}
				
				$('#organizer_form_field_error').html('');
				$('#organizer_field_details').prepend($errorDiv);
				
			} else {
				
				// Remove errors
				$('#organizer_form_field_error').remove();
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


}, zenarioO.panelTypes);
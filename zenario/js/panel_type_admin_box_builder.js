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
	panelTypes.admin_box_builder = extensionOf(panelTypes.form_builder_base_class)
);

//Called by Organizer upon the first initialisation of this panel.
//It is not recalled if Organizer's refresh button is pressed, or the administrator changes page
methods.init = function() {
	// Warning message when trying to leave without saving changes
	this.saveChangesWarningMessage = 'You are currently editing this dataset. If you leave now you will lose any unsaved changes.';
	
	// Whether there are any local changes
	this.changingForm = false;
	
	// Fields which cause details panel to update on change
	this.formatOnChange = [
		'values_source'
	];
	
	// Selector for all form fields
	this.formFieldsSelector = '#organizer_form_sections .form_field';
	
	// Selector for form fields inline buttons
	this.formFieldInlineButtonsSelector = '#organizer_form_sections .form_field_inline_buttons';
};


// Change objects into ordered array
methods.getOrderedItems = function(items) {
	var ID, item, itemClone, 
		fieldID, field, fieldClone, fields, 
		valueID, value, values,
		orderedItems = [];
	
	// Loop through each field
	foreach (items as ID => item) {
		if (!item.remove) {
			itemClone = _.clone(item);
			
			// Ordering a list of tabs, so also order the fields then the lovs
			if (itemClone.fields) {
				fields = [];
				foreach (itemClone.fields as fieldID => field) {
					if (!item.remove) {
						fieldClone = _.clone(field);
						
						if (fieldClone.lov) {
							values = [];
							foreach (fieldClone.lov as valueID => value) {
								if (!value.remove) {
									values.push(value);
								}
							}
							values.sort(this.sortByOrd);
							fieldClone.lov = values;
						}
						
						fields.push(field);
					}
				}
				fields.sort(this.sortByOrd);
				itemClone.fields = fields;
			
			// Ordering a list of values so order the lov
			} else if (itemClone.lov) {
				values = [];
				foreach (itemClone.lov as valueID => value) {
					if (!value.remove) {
						values.push(value);
					}
				}
				values.sort(this.sortByOrd);
				itemClone.lov = values;
			}
			
			orderedItems.push(itemClone);
		}
	}
	
	orderedItems.sort(this.sortByOrd);
	return orderedItems;
	
	/*
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
				// Check if field has a list of values
				if (field['lov']) {
					var values = [];
					// If so store them in array
					foreach (field['lov'] as id => value) {
						values.push(value);
					}
					// Sort field lov
					values.sort(this.sortByOrd);
					field['lov'] = values;
				}
				// Store fields in array
				item['fields'].push(field);
			}
			// Sort fields
			item['fields'].sort(this.sortByOrd)
		} else {
			this.tuix.items[tabName].fields = {};
		}
		// Store tabs in array
		orderedItems.push(item);
	}
	
	// Sort tabs
	orderedItems.sort(this.sortByOrd);
	return orderedItems;
	*/
};


//Draw the panel, as well as the header at the top and the footer at the bottom
//This is called every time the panel is loaded, refreshed or when something in the header toolbar is changed.
methods.showPanel = function($header, $panel, $footer) {
	$header.html('').hide();
	var that = this,
		items = this.getOrderedItems(this.tuix.items),
		mergeFields = {
			items: items
		},
		html = zenarioA.microTemplate('zenario_organizer_admin_box_builder', mergeFields),
		currentTab = false;
	
	this.currentTab = this.currentTab ? this.currentTab : false;
	this.currentFieldTab = 'details';
	this.maxNewCustomTab = 1;
	this.maxNewCustomField = 1;
	this.maxNewCustomFieldValue = 1;
	this.current = {id: false, type: false};
	this.current_db_columns = this.tuix.existing_db_columns;
	this.deleting = false;
	
	$panel.html(html).show();
	
	// Handle case where new tab is selected after ID has been changed from, e.g. t4 => 6
	if (this.currentTab && !this.tuix.items[this.currentTab] && this.currentTabOrd && items[this.currentTabOrd]) {
		this.currentTab = items[this.currentTabOrd].name;
	}
	
	foreach (items as i => item) {
		var $tab = $('#organizer_form_tab_' + item['name']);
			$section = $('#organizer_section_' + item['name']);
		
		// Init new tab
		this.initTab($tab);
		
		// Init new section
		this.initSection($section);
		
		if (this.currentTab != false) {
			if (item['name'] != this.currentTab) {
				$section.hide().sortable('disable');
			} else {
				$tab.addClass('on');
			}
		} else {
			if (item['ord'] != 1) {
				$section.hide().sortable('disable');
			} else {
				$tab.addClass('on');
				currentTab = item['name'];
			}
		}
	}
	
	if (currentTab) {
		this.currentTab = currentTab;
	}
	
	this.orderedItems = items;
	
	// Make new fields palette draggable
	$('#organizer_field_type_list div.field_type, #organizer_centralised_field_type_list div.field_type').draggable({
		connectToSortable: '#organizer_form_sections div.form_section',
		helper: function() {
			var type = $(this).data('type');
			return $('<div>[---PLACEHOLDER---]</div>').data('type', type).addClass('preview preview_' + type);
		}
	});
	
	// Make tabs sortable
	$('#organizer_form_tabs').sortable({
		axis: 'x',
		containment: 'parent',
		items: 'div.sort',
		start: function(event, ui) {
			that.startIndex = ui.item.index();
		},
		// Change tab order
		stop: function(event, ui) {
			if (that.startIndex != ui.item.index()) {
				that.currentTabOrd = ui.item.index();
				that.changeMadeToPanel();
			}
		}
	});
	
	// Add a new tab
	$('#organizer_add_new_tab').on('click', function() {
		var afterValidate = function(errors) {
			if (_.isEmpty(errors)) {
				that.addNewTab();
			}
		};
		
		if (cb = that.validate()) {
			cb.after(afterValidate);
		} else {
			afterValidate([]);
		}
	});
	
	// Edit a form field
	$('#organizer_form_sections div.form_field').on('click', function() {
		that.fieldClick($(this));
	});
	
	this.initDeleteButtons();
	
	$footer.html('').show();
};


methods.initDeleteButtons = function() {
	var that = this;
	$('#organizer_form_sections .delete_icon').off().on('click', function(e) {
		that.deleting = 'field';
		zenarioA.showMessage('Are you sure you want to delete this Field?', 'Delete', 'warning', true);
		that.listenForDelete($(this).data('id'));
		e.stopPropagation();
	});
};

// Listen for field/tab delete
methods.listenForDelete = function(id) {
	
	var that = this;
	$('#zenario_fbMessageButtons input.submit_selected').on('click', function() { 
		setTimeout(function() {
			if (that.deleting == 'tab') {
				that.deleteTab();
			} else if (that.deleting = 'field') {
				that.deleteField(id);
			}
			that.deleting = false;
		});
	});
};

methods.deleteTab = function() {
	var $tab, $next;
	
	this.tuix.items[this.current.id] = {remove: true};
	$tab = $('#organizer_form_tab_' + this.current.id);
	
	// Select another tab
	$next = $tab.prev();
	if ($next.length == 0) {
		$next = $tab.next();
	}
	
	// Remove tab element
	$tab.remove();
	this.changeMadeToPanel();
	if ($next.length == 1) {
		this.tabClick($next);
	}
};

methods.deleteField = function(id) {
	var that = this,
		$field,
		$next;
		
	if (id === undefined) {
		id = this.current.id;
	}
	
	if (!this.tuix.items[this.currentTab].fields[id] || (this.tuix.items[this.currentTab].fields[id].is_system_field == 0)) {
		// Mark for deletion
		this.tuix.items[this.currentTab].fields[id] = {remove: true};
		
		$field = $('#organizer_form_field_' + id);
		
		if ($field) {
			// Select the previous field that isn't already being removed (because of the animation), otherwise look for the next field
			// othereise show add fields panel
			
			$next = this.selectNextField($field);
			
			this.changeMadeToPanel();
			
			$field.animate({height: 0, opacity: 0}, 500, function() {
				$field.remove();
				if ($next === false) {
					that.showNoFieldsMessage();
				}
			});
		}
	}
};

methods.selectNextField = function($field) {
	$next = $field.prev();
	while ($next.length == 1) {
		if (this.tuix.items[this.currentTab].fields[$next.data('id')] && this.tuix.items[this.currentTab].fields[$next.data('id')].remove) {
			$next = $next.prev();
		} else {
			break;
		}
	}
	if ($next.length == 0) {
		$next = $field.next();
		while ($next.length == 1) {
			if (this.tuix.items[this.currentTab].fields[$next.data('id')] && this.tuix.items[this.currentTab].fields[$next.data('id')].remove) {
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
		'<span class="no_fields_message">There are no fields on this tab</span>');
	this.showDetailsSection('organizer_field_type_list');
};

methods.addNewTab = function() {
	var tabName = '__custom_tab_t' + this.maxNewCustomTab++,
		label = 'Untitled tab',
		mergeFields = {
			name: tabName,
			label: label
		},
		html = zenarioA.microTemplate('zenario_organizer_admin_box_builder_tab', mergeFields);
	$('#organizer_add_new_tab').before(html);
	this.tuix.items[tabName] = {
		label: label,
		is_new_tab: true,
		is_system_field: 0,
		fields: {}
	};
	// Create new section for fields
	html = zenarioA.microTemplate('zenario_organizer_admin_box_builder_section', mergeFields);
	$('#organizer_form_sections').append(html);
	var $newSection = $('#organizer_section_' + tabName);
	this.initSection($newSection);
	// Init new tab
	var $newTab = $('#organizer_form_tab_' + tabName);
	this.initTab($newTab);
	// Open properties for new tab
	this.tabClick($newTab);
	// Show apply/cancel buttons
	this.changeMadeToPanel();
};

methods.initTab = function($tab) {
	var that = this;
	// Change form tab
	$tab.on('click', function() {
		that.tabClick($(this));
	});
	$tab.droppable({
		accept: 'div.form_field:not(.system_field)',
		greedy: true,
		hoverClass: 'ui-state-hover',
		tolerance: 'pointer',
		drop: function(event, ui) {
			var tab = $(this).data('name'),
				id = $(ui.draggable).data('id'),
				canDrop = true,
				$next,
				afterValidate = function() {
					if (!_.isEmpty) {
						canDrop = false;
					}
				};
			
			if ((that.current.type == 'field') && (id == that.current.id)) {
				that.save();
				if (cb = that.validate()) {
					cb.after(afterValidate);
				} else {
					afterValidate([]);
				}
			}
			
			if (canDrop) {
				//Use a setTimeout() as a hack to make sure that this code is run after jQuery droppable has finished processing the element
				setTimeout(function() {
					// Move element to new tab
					$(ui.draggable).detach().appendTo('#organizer_section_' + tab);
					// Update items array
					that.tuix.items[tab].fields[id] = that.tuix.items[that.currentTab].fields[id];
					delete(that.tuix.items[that.currentTab].fields[id]);
					if (that.current.type == 'field') {
						that.current = {type: 'tab', id: that.currentTab};
						that.tabClick($('#organizer_form_tab_' + that.currentTab));
					}
					
					if (_.size(that.tuix.items[that.currentTab].fields) == 0) {
						that.showNoFieldsMessage();
					}
					
					that.removeNoItemsMessage(tab);
				});
			}
		}
	});
};


methods.initSection = function($section) {
	var that = this;
	$section.sortable({
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
	var id = 't' + this.maxNewCustomField++,
		type = $field.data('type'),
		label = 'Untitled',
		mergeFields = {
			id: id,
			type: type,
			label: label
		},
		values_source = '';
	
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
	var html = zenarioA.microTemplate('zenario_organizer_admin_box_builder_field', mergeFields);
	$field.replaceWith(html);
	
	// Add other properties to field
	var otherProperties = {
		is_protected: 0,
		include_in_export: 0,
		is_new_field: true,
		just_added: true,
		db_column: '',
		is_system_field: 0,
		validation: 'none',
		required: 0,
		show_in_organizer: 0,
		create_index: 0,
		values_source: values_source,
		remove: false
	}
	$.extend(mergeFields, otherProperties);
	
	// Add new field to list
	this.tuix.items[this.currentTab].fields[id] = mergeFields;
	var $newField = $('#organizer_form_field_' + id);
	$newField.effect({effect: 'highlight', duration: 1000});
	
	// Init field
	this.initField($newField);
	
	// Open properties for new field
	this.fieldClick($newField);
};


methods.tabClick = function($tab) {
	
	var that = this,
		afterValidate = function(errors) {
			if (_.isEmpty(errors)) {
				
				// Disable old section
				$('#organizer_section_' + that.currentTab).sortable('disable');
				
				// Select new tab
				that.currentTab = $tab.data('name');
				that.currentTabOrd = $tab.index();
				that.current = {id: that.currentTab, type: 'tab'};
				
				// Enable new section
				$('#organizer_section_' + that.currentTab).sortable('enable');
				
				$('#organizer_form_tabs div.tab').removeClass('on');
				$tab.addClass('on');
				$('#organizer_form_sections div.form_section').hide();
				
				// Load selected tabs current properties
				values = that.tuix.items[that.currentTab] || {};
				that.setCurrentTabDetails(values);
				
				// Hide remove button if system field
				if (that.tuix.items[that.currentTab] && (that.tuix.items[that.currentTab].is_system_field == 1)) {
					$('#organizer_remove_form_tab').hide();
				} else {
					$('#organizer_remove_form_tab').show();
				}
				
				// Show details panel
				that.showDetailsSection('organizer_tab_details');
				
				// Show tab fields
				$('#organizer_section_' + that.currentTab).show();
			}
		};
	
	this.save();
	if (cb = this.validate()) {
		cb.after(afterValidate);
	} else {
		afterValidate([]);
	}
};

methods.setCurrentTabDetails = function(values) {
	var values = _.clone(values);
	values.parent_id_select_list = this.orderedItems;
	var that = this;
		html = zenarioA.microTemplate('zenario_organizer_admin_box_builder_tab_details', values)
	$('#organizer_tab_details_outer').html(html);
	
	$('#organizer_tab_details :input').off().on('change', function() {
		that.changeMadeToPanel();
	});
	
	$('#organizer_tab_details input[type="text"]').on('keyup', function() {
		that.changeMadeToPanel();
	});
	
	$('#tab__label').on('keyup', function() {
		$('#organizer_form_tab_' + that.currentTab + ' span').text($(this).val());
	});
	
	// Init remove button
	$('#organizer_remove_form_tab').on('click', function() {
		if (that.tuix.items[that.current.id].is_system_field == 0) { 
			that.deleting = 'tab';
			zenarioA.showMessage('Are you sure you want to delete this Tab?<br><br>All fields on this tab will also be deleted.', 'Delete', 'warning', true);
			that.listenForDelete();
		}
	});
	
	this.initAddNewFieldsButton();
};

methods.getCurrentTabDetails = function() {
	var tab = {};
	tab.label = $('#tab__label').val();
	tab.parent_field_id = $('#tab__parent_field_id').val();
	return tab;
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
				
				that.setCurrentFieldDetails();
				that.showFieldDetailsSection('details', true);
				
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
		values = (this.tuix.items[this.currentTab].fields[this.current.id] || {}),
		field = _.clone(values);
	
	//TODO review if these are needed
	field.dataset_label = this.tuix.dataset_label;
	field.is_text_field = ($.inArray(field.type, ['date', 'editor', 'text', 'textarea', 'url']) > -1);
	field.is_checkbox_field = ($.inArray(field.type, ['group', 'checkbox']) > -1);
	field.is_list_field = ($.inArray(field.type, ['checkboxes', 'radios', 'centralised_radios', 'select', 'centralised_select']) > -1);
	field.show_searchable_field = field.is_text_field ? field.show_in_organizer : field.show_in_organizer && field.create_index;
	
	// Create object to create a select list of centralised lists
	var centralised_lists = {},
		ord = 1;
	foreach (this.tuix.centralised_lists as func => details) {
		centralised_lists[func] = {
			ord: ord++,
			label: details.label
		}
	}
	
	field.values_source_options = this.createSelectList(centralised_lists, field.values_source);
	
	// Create object to create a select list of checkbox/group fields
	var booleanFields = {};
	foreach (this.tuix.items as tabName => tab) {
		
		var childOptions = {};
		
		foreach (tab.fields as fieldID => field) {
			if (field.type === 'group' || field.type === 'checkbox') {
				childOptions[fieldID] = {
					ord: field.ord,
					label: field.label
				}
			}
		}
		
		booleanFields[tabName] = {
			label: tab.label,
			ord: tab.ord,
			childOptions: childOptions
		}
	}
	field.parent_id_options = this.createSelectList(booleanFields, field.parent_id, ' -- No conditional display --', true);
	
	var width_options = {
		1: {
			ord: 1,
			label: '1 character (em)'
		},
		5: {
			ord: 2,
			label: '5 characters (em)'
		},
		10: {
			ord: 3,
			label: '10 characters (em)'
		},
		25: {
			ord: 4,
			label: '25 characters (em)'
		},
		40: {
			ord: 5,
			label: '40 characters (em)'
		},
		56: {
			ord: 6,
			label: '56 characters (em)'
		}
	};
	field.width_options = this.createSelectList(width_options, field.width, ' -- Default width -- ');
	
	var height_options = {
		3: {
			ord: 1,
			label: '3 rows'
		},
		5: {
			ord: 2,
			label: '5 rows'
		},
		10: { 
			ord: 3,
			label: '10 rows'
		},
		20: { 
			ord: 4,
			label: '20 rows'
		}
	};
	field.height_options = this.createSelectList(height_options, field.height, ' -- Default rows -- ');
	
	var visibility_options = {
		1: {
			ord: 1,
			label: 'Show by default'
		},
		2: {
			ord: 1,
			label: 'Always show'
		}
	}
	field.visibility_options = this.createSelectList(visibility_options, field.visibility, 'Hide by default');
	
	
	
	
	
	var html = zenarioA.microTemplate('zenario_organizer_admin_box_builder_field_details', field);
	$('#organizer_field_details_outer').html(html);
	
	
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
	
	
	
	// Listen for changes
	$('#organizer_field_details :input').off().on('change', function() {
		that.changeMadeToPanel();
	});
	$('#organizer_field_details input[type="text"]').on('keyup', function() {
		that.changeMadeToPanel();
	});
	
	// Update label and code name
	$('#field__label').on('keyup', function() {
		$('#organizer_form_field_' + that.current.id + ' .label').text($(this).val());
		if (field.just_added) {
			var db_column = $(this).val().toLowerCase().replace(/[\s-]+/g, '_').replace(/[^a-z_0-9]/g, '');
			$('#field__db_column').val(db_column);
		}
	});
	
	// Disable code name if protected and existing field
	$('#field__is_protected').on('change', function() {
		if (!field.is_new_field) {
			$('#field__db_column').prop('readonly', $(this).prop('checked'));
		}
	});
	
	$('input:radio[name="field__create_index"]').filter('[value="' +  field.create_index + '"]').prop('checked', true);
	
	// Disable code name for system fields
	if (field.is_system_field) {
		$('#field__db_column').prop('readonly', true);
	} else {
		var visibility = (field.always_show ? 2 : (field.show_by_default ? 1 : ''));
		
		$('#field__required').prop('checked', field.required);
		if (field.required) {
			$('#field__required_message').val(field.required_message);
		}
		$('#field__required').on('change', function() {
			$('#field_container__required_message').toggle(this.checked);
		});
		$('#field__show_in_organizer').prop('checked', field.show_in_organizer);
		$('#field__show_in_organizer').on('change', function() {
			$('#field_container__visibility').toggle(this.checked);
			
			var create_index = ($('input:radio[name="field__create_index"]:checked').val() == true);
				searchable = field.is_text_field ? this.checked : (create_index && this.checked);
			$('#field_container__searchable').toggle(searchable);
			$('#field_container__sortable').toggle(create_index && this.checked);
		});
		
		$('input:radio[name="field__create_index"]').on('change', function() {
			var create_index = $('input:radio[name="field__create_index"]:checked').val() == true,
				show_in_organizer = $('#field__show_in_organizer').prop('checked'),
				searchable = field.is_text_field ? show_in_organizer : (create_index && show_in_organizer);
			
			$('#field_container__searchable').toggle(searchable);
			$('#field_container__sortable').toggle(create_index && show_in_organizer);
		});
		
		$('#field__visibility').val(visibility);
	}
	
	if (field.db_column) {
		$('#field__include_in_export').prop('checked', (field.include_in_export == true));
	}
	
	switch (field.type) {
		case 'select':
		case 'checkboxes':
		case 'radios':
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
				})
			
			// Bind events
			this.initFieldValues();
			
			// Setup add value button
			this.initAddNewFieldValuesButton();
			
			break;
		case 'centralised_select':
		case 'centralised_radios':
			if (!field.is_system_field) {
				var $values_source = $('#field__values_source'),
					$values_source_filter = $('#field__values_source_filter'),
					$values_source_filter_label = $('#field_label__source_filter'),
					$values_source_filter_container = $('#field_container__values_source_filter'),
					label, can_filter;
				
				// Set values
				$values_source.val(field.values_source);
				label = $values_source.find(':selected').data('label');
				if (field.values_source && label) {
					$values_source_filter_label.html(label);
				}
				if (field.values_source_filter) {
					$values_source_filter.val(field.values_source_filter);
				}
				can_filter = $values_source.find(':selected').data('filter');
				if (can_filter) {
					$values_source_filter_container.show();
				}
				
				if (field.type == 'centralised_radios') {
					$values_source.on('change', function() {
						
						// Show/hide filter option and change label
						var $option = $(this).find(':selected');
						$values_source_filter_container.toggle($option.data('filter') == true);
						if ($option.data('label')) {
							$values_source_filter_label.html($option.data('label'));
						}
						
						that.updateCentralisedRadioValues($values_source, $values_source_filter, $values_source_filter_container, field);
					});
					
					$values_source_filter.on('blur', function() {
						that.updateCentralisedRadioValues($values_source, $values_source_filter, $values_source_filter_container, field);
					});
				}
			}
			break;
		case 'text':
			if (!field.is_system_field) {
				var validation = (field.validation == false) ? 'none' : field.validation;
				
				$('#field__validation__' + validation).prop('checked', true);
				if (validation != 'none') {
					$('#field__validation_message').val(field.validation_message);
				}
				
				// Show/hide validation message box
				$('#field_section__validation input:radio[name="field__validation"]').on('change', function() {
					$('#field_container__validation_message').toggle($(this).prop('id') != 'field__validation__none');
				});
			}
			break;
	}
	
	this.initAddNewFieldsButton();
};

methods.initAddNewFieldValuesButton = function() {
	var that = this;
	$('#organizer_add_a_field_value').on('click', function() {
		
		// Save current values
		that.save();
		
		// Save new value
		var field = that.tuix.items[that.currentTab].fields[that.current.id],
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


methods.updateCentralisedRadioValues = function($values_source, $values_source_filter, $values_source_filter_container, values) {
	var that = this,
		actionRequests = zenarioO.getKey(),
		actionTarget = 
		'zenario/ajax.php?' +
			'__pluginClassName__=' + this.tuix.class_name +
			'&__path__=' + zenarioO.path +
			'&method_call=handleOrganizerPanelAJAX';
	
	actionRequests.mode = 'get_centralised_lov';
	actionRequests.method = $values_source.val();
	if (actionRequests.method && $values_source_filter_container.is(':visible')) {
		actionRequests.filter = $values_source_filter.val();
	}
	
	zenario.ajax(
		URLBasePath + actionTarget,
		actionRequests
	).after(function(data) {
		var lov = JSON.parse(data),
			html = zenarioA.microTemplate('zenario_organizer_admin_box_builder_radio_values_disabled', lov);
		$('#organizer_form_field_values_' + values.id).html(html);
	});
};

methods.setCurrentFieldValues = function() {
	
	var id = this.current.id,
		field = this.tuix.items[this.currentTab].fields[id],
		items = field.lov,
		mergeFields = this.getOrderedItems(items),
		html = '';
	
	html = zenarioA.microTemplate('zenario_organizer_admin_box_builder_field_value', mergeFields);
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
		that.tuix.items[that.currentTab].fields[that.current.id].lov[id] = {remove: true};
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

// Get the current values for a field
methods.getCurrentFieldDetails = function() {
	
	var field = {},
		current = this.tuix.items[this.currentTab].fields[this.current.id];
	
	$.each($('#organizer_field_details_form').serializeArray(), function(index, input) {
		field[input.name] = input.value;
	});
	
	// Values tab
	if ((['select', 'radios', 'checkboxes'].indexOf(current.type) != -1) && !current.field_id) {
		field.lov = this.getCurrentFieldValues();
	}
	
	return field;
};

methods.getCurrentFieldValues = function(field) {
	
	var that = this;
		field = this.tuix.items[this.currentTab].fields[that.current.id];
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


// Called whenever the properties of a field/tab needs to be saved
methods.save = function() {
	var values = {};
	if (this.current.id && this.current.type) {
		if (this.current.type == 'tab') {
			values = this.getCurrentTabDetails();
			if (!this.tuix.items[this.current.id]) {
				this.tuix.items[this.current.id] = {};
			}
			foreach (values as id => value) {
				this.tuix.items[this.current.id][id] = value;
			}
		} else if (this.current.type == 'field') {
			values = this.getCurrentFieldDetails();
			if (!this.tuix.items[this.currentTab].fields[this.current.id]) {
				this.tuix.items[this.currentTab].fields[this.current.id] = {};
			}
			foreach (values as id => value) {
				this.tuix.items[this.currentTab].fields[this.current.id][id] = value;
			}
		}
	}
};

// Called to validate properties of a field/tab when moving off current item
methods.validate = function() {
	
	var that = this,
		cb = new zenario.callback,
		actionRequests = {
			mode: 'validate',
			id: this.current.id,
			type: this.current.type,
			tab: this.currentTab,
			field_tab: this.currentFieldTab,
			items: JSON.stringify(this.tuix.items)
		};
	
	if (this.current.id && this.current.type ) {
		if (this.current.type == 'tab') {
			return false;
		} else if (this.current.type == 'field') {
			if (!this.tuix.items[this.currentTab].fields[this.current.id].remove) {
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
						delete(that.tuix.items[that.currentTab].fields[that.current.id].just_added);
					}
					
					cb.call(errors);
				});
				return cb;
			}
		}
	}
	return false;
};

methods.saveItemsOrder = function() {
	var that = this;
	$('#organizer_form_tabs div.tab.sort').each(function(i, tab) {
		var name = $(tab).data('name');
		that.tuix.items[name].ord = (i + 1);
		$('#organizer_section_' + name + ' .form_field').each(function(j, field) {
			var id = $(field).data('id');
			that.tuix.items[name].fields[id].ord = (j + 1);
			that.tuix.items[name].fields[id].tab_name = name;
		});
	});
};


}, zenarioO.panelTypes);
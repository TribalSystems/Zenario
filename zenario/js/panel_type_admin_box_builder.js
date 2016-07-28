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
		'values_source',
		'is_protected',
		'required',
		'validation',
		'show_in_organizer',
		'create_index',
		'admin_box_visibility'
	];
	
	// Top level div for this editor
	this.formEditorSelector = '#organizer_admin_form_builder';
	
	// Selector for all form fields
	this.formFieldsSelector = '#organizer_form_sections .form_field';
	
	// Selector for form fields inline buttons
	this.formFieldInlineButtonsSelector = '#organizer_form_sections .form_field_inline_buttons';
	
	// Save buttons text
	this.saveButtonText = 'Save changes';
	this.cancelButtonText = 'Reset';
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
						
						fields.push(fieldClone);
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
			tab: {},
			details_section: 'field_type_list',
			dataset_label: this.tuix.dataset_label
		};
	
	this.currentFieldTab = this.currentFieldTab ? this.currentFieldTab : 'details';
	this.currentTab = this.currentTab ? this.currentTab : false;
	this.currentTabOrd = this.currentTabOrd ? this.currentTabOrd : 0;
	
	// Get details page mergefields
	if (this.currentTab) {
		
		// Get ordered list of tabs and fields
		mergeFields.items = this.getOrderedItems(mergeItems);
		
		// Handle case where new tab is selected after ID has been changed from, e.g. custom_tab__t1 => custom_tab__2
		if (!this.tuix.items[this.currentTab]) {
			if (this.currentTabOrd !== undefined 
				&& mergeFields.items[this.currentTabOrd]
			) {
				this.currentTab = mergeFields.items[this.currentTabOrd].name;
				if (this.current && this.current.type == 'tab') {
					this.current.id = mergeFields.items[this.currentTabOrd].name;
				}
			} else {
				this.current = false;
				this.currentTab = false;
			}
		}
		
		if (this.current) {
			// Handle case where new field is selected after ID has been changed from, e.g. t4 => 6
			if (this.current.type == 'field'
				&& !this.tuix.items[this.currentTab].fields[this.current.id]
				&& this.currentFieldOrd !== undefined
				&& this.currentTabOrd !== undefined
				&& mergeFields.items[this.currentTabOrd]
				&& mergeFields.items[this.currentTabOrd].fields[this.currentFieldOrd]
			) {
				this.current.id = mergeFields.items[this.currentTabOrd].fields[this.currentFieldOrd].id;
			}
		
			// Try to select last selected tab/field
			if (this.current.type == 'field') {
				mergeFields.details_section = 'field_details';
				mergeFields.field = this.getCurrentFieldDetailsMergeFields();
				mergeItems[this.currentTab].fields[this.current.id].selected = true;
			} else if (this.current.type == 'tab') {
				mergeFields.details_section = 'tab_details';
				mergeFields.tab = this.getCurrentTabDetailsMergeFields();
				mergeItems[this.currentTab].selected = true;
			}
		}
	}
	
	mergeFields.items = this.getOrderedItems(mergeItems);
	
	mergeFields.use_groups_field = this.tuix.use_groups_field;
	
	// Set HTML
	var html = this.microTemplate('zenario_organizer_admin_box_builder', mergeFields);
	$panel.html(html).show();
	
	// Add JS events
	if (this.current) {
		if (this.current.type == 'field') {
			this.initCurrentFieldDetails();
		} else if (this.current.type == 'tab') {
			this.initCurrentTabDetails();
		}
	} else {
		this.current = {id: false, type: false};
	}
	
	var currentTab = false;
	this.maxNewCustomTab = 1;
	this.maxNewCustomField = 1;
	this.maxNewCustomFieldValue = 1;
	this.current_db_columns = this.tuix.existing_db_columns;
	this.deleting = false;
	
	
	foreach (mergeFields.items as var i => var item) {
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
	
	// Make new fields palette draggable
	$('#organizer_field_type_list div.field_type, #organizer_centralised_field_type_list div.field_type').draggable({
		connectToSortable: '#organizer_form_sections div.form_section',
		helper: 'clone'
		/*function() {
			var type = $(this).data('type');
			return $('<div>[---PLACEHOLDER---]</div>').data('type', type).addClass('preview preview_' + type);
		}*/
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
				
				// Update current tab ord
				if (that.current.type == 'tab') {
					that.currentTabOrd = $('#organizer_form_tab_' + that.current.id).index();
				}
				
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
		
		var fieldID = $(this).data('id'),
			field = that.tuix.items[that.currentTab].fields[fieldID],
			message = '',
			plural = '',
			deleteButtonText = true;
		
		if (!field.is_protected) {			
			var actionRequests = {
				mode: 'get_field_record_count',
				field_id: fieldID
			};
			that.sendAJAXRequest(actionRequests).after(function(data) {
				var field = JSON.parse(data);
				
				deleteButtonText = 'Delete';
				if (field.record_count && field.record_count >= 1) {
					plural = field.record_count == 1 ? '' : 's';
					message = "<p>This field contains data on " + field.record_count + " record" + plural + ".</p>";
					message += "<p>When you save changes to this dataset, that data will be deleted.</p>";
				} else {
					message = "<p>This field doesn't contain any data for any user/contact records.</p>";
				}
				message += "<p>Delete this dataset field?</p>";
				
				zenarioA.showMessage(message, deleteButtonText, 'warning', true);
				that.listenForDelete(fieldID);
				e.stopPropagation();
			});
		} else {
			message = '<p>This field is protected, and might be important to your site!</p>';
			message += '<p>If you\'re sure you want to delete this field then first unprotect it.</p>';
			
			zenarioA.showMessage(message, deleteButtonText, 'warning', true);
			that.listenForDelete(fieldID);
			e.stopPropagation();
		}
		
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
		html = this.microTemplate('zenario_organizer_admin_box_builder_tab', mergeFields);
	$('#organizer_add_new_tab').before(html);
	this.tuix.items[tabName] = {
		label: label,
		is_new_tab: true,
		is_system_field: 0,
		fields: {}
	};
	// Create new section for fields
	html = this.microTemplate('zenario_organizer_admin_box_builder_section', mergeFields);
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
				afterValidate = function(errors) {
					if (_.isEmpty(errors)) {
						
						if (tab != that.currentTab) {
							//Use a setTimeout() as a hack to make sure that this code is run after jQuery droppable has finished processing the element
							setTimeout(function() {
								// Move element to new tab
								$(ui.draggable).detach().appendTo('#organizer_section_' + tab);
								
								// If field we just moved was currently selected unselect it
								if (that.current.type == 'field' && that.current.id == id) {
									that.unselectField();
								}
								
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
								that.changeMadeToPanel();
							});
						}
					} else {
						var $field = $('#organizer_form_field_' + id);
						that.fieldClick($field);
					}
				};
			
			that.save();
			if (cb = that.validate()) {
				cb.after(afterValidate);
			} else {
				afterValidate([]);
			}
		}
	});
};


methods.initSection = function($section) {
	var that = this;
	$section.sortable({
		items: 'div.form_field',
		placeholder: 'preview',
		// Add new field to the form
		receive: function(event, ui) {
			$(this).find('div.field_type').each(function() {
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
		newField = {
			id: id,
			type: type,
			label: label
		},
		values_source = '';
	
	// Remove no items message from tab
	this.removeNoItemsMessage(this.currentTab);
	
	// If new field is a multivalue, add an inital list of values
	if ($.inArray(type, ['checkboxes', 'radios', 'select']) > -1) {
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
	}
	
	// Load centralised LOV for preview
	if (type == 'centralised_radios' || type == 'centralised_select') {
		foreach (this.tuix.centralised_lists.values as method) {
			values_source = method;
			break;
		}
		newField.lov = _.toArray(this.tuix.centralised_lists.initial_lov);
	}
	
	mergeFields = _.clone(newField);
	if (newField.lov) {
		mergeFields.lov = this.getOrderedItems(newField.lov);
	}
	
	// Set HTML
	var html = this.microTemplate('zenario_organizer_admin_box_builder_field', mergeFields);
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
		organizer_visibility: 'hide',
		values_source: values_source,
		values_source_filter: '',
		remove: false
	}
	$.extend(newField, otherProperties);
	
	// Add new field to list
	this.tuix.items[this.currentTab].fields[id] = newField;
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
				
				// Unselect any selected field
				that.unselectField();
				
				// Select new tab
				that.currentTab = $tab.data('name');
				that.currentTabOrd = $tab.index();
				that.current = {id: that.currentTab, type: 'tab'};
				
				// Enable new section
				$('#organizer_section_' + that.currentTab).sortable('enable');
				
				// Update class and show new section
				$('#organizer_form_tabs div.tab').removeClass('on');
				$tab.addClass('on');
				$('#organizer_form_sections div.form_section').hide();
				
				// Load selected tabs current properties
				that.setCurrentTabDetails();
				
				// Hide remove button if system tab
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

methods.setCurrentTabDetails = function() {
	var that = this,
		tab = (this.tuix.items[this.currentTab] || {}),
		mergeFields = this.getCurrentTabDetailsMergeFields(),
		html = this.microTemplate('zenario_organizer_admin_box_builder_tab_details', mergeFields);
	
	// Set HTML
	$('#organizer_tab_details_outer').html(html);
	
	// Add JS events
	this.initCurrentTabDetails();
};

methods.getCurrentTabDetailsMergeFields = function() {
	var tab = (this.tuix.items[this.currentTab] || {}),
		mergeFields =  _.clone(tab);
	
	var booleanFields = {};
	foreach (this.tuix.items as tabName => tabDetails) {
		
		var childOptions = {};
		
		foreach (tabDetails.fields as fieldID => fieldDetails) {
			if (fieldDetails.type === 'group' || fieldDetails.type === 'checkbox') {
				childOptions[fieldID] = {
					ord: fieldDetails.ord,
					label: fieldDetails.label
				}
			}
		}
		
		booleanFields[tabName] = {
			label: tabDetails.label,
			ord: tabDetails.ord,
			childOptions: childOptions
		}
	}
	mergeFields.parent_field_id_options = this.createSelectList(booleanFields, tab.parent_field_id, ' -- No conditional display --', true);
	
	return mergeFields;
};

methods.initCurrentTabDetails = function() {
	
	var that = this;
	
	// Detect changes to panel
	$('#organizer_tab_details :input').off().on('change', function() {
		that.changeMadeToPanel();
	});
	$('#organizer_tab_details input[type="text"], #organizer_tab_details textarea').on('keyup', function() {
		that.changeMadeToPanel();
	});
	
	// Update label
	$('#tab__label').on('keyup', function() {
		$('#organizer_form_tab_' + that.currentTab + ' span, #zenario_tab_details_header_content h5').text($(this).val());
	});
	
	// Init remove button
	$('#organizer_remove_form_tab').on('click', function() {
		if (that.tuix.items[that.current.id].is_system_field == 0) { 
			that.deleting = 'tab';
			zenarioA.showMessage('Are you sure you want to delete this Tab?<br><br>All fields on this tab will also be deleted.', 'Delete', 'warning', true);
			that.listenForDelete();
		}
	});
	
	// Init new fields adder
	this.initAddNewFieldsButton();
	
};

methods.getCurrentTabDetails = function() {
	var tab = {};
	
	$.each($('#organizer_tab_details_form').serializeArray(), function(index, input) {
		tab[input.name] = input.value;
	});
	
	return tab;
};


methods.fieldClick = function($field) {
	
	var that = this,
		afterValidate = function(errors) {
			
			if (_.isEmpty(errors)) {
				var id = $field.data('id');
				
				// Select clicked field
				that.current = {type: 'field', id: id};
				that.currentFieldOrd = $field.index();
				
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
		mergeFields = this.getCurrentFieldDetailsMergeFields(),
		html = this.microTemplate('zenario_organizer_admin_box_builder_field_details', mergeFields);
	
	// Set HTML
	$('#organizer_field_details_outer').html(html);
	
	// Add JS events
	this.initCurrentFieldDetails();
};

methods.getCurrentFieldDetailsMergeFields = function() {
	var field = (this.tuix.items[this.currentTab].fields[this.current.id] || {}),
		mergeFields =  _.clone(field);
	
	// Pass the current tab
	mergeFields.currentFieldTab = this.currentFieldTab;
	
	mergeFields.dataset_label = this.tuix.dataset_label;
	mergeFields.is_text_field = ($.inArray(mergeFields.type, ['date', 'editor', 'text', 'textarea', 'url']) > -1);
	mergeFields.is_checkbox_field = ($.inArray(mergeFields.type, ['group', 'checkbox']) > -1);
	mergeFields.is_list_field = ($.inArray(mergeFields.type, ['checkboxes', 'radios', 'centralised_radios', 'select', 'centralised_select']) > -1);
	
	mergeFields.cannot_export = ($.inArray(mergeFields.type, ['editor', 'textarea', 'file_picker']) > -1);
	
	switch (mergeFields.type) {
		case 'group':
			mergeFields.formattedType = 'Group';
			break;
		case 'checkbox':
			mergeFields.formattedType = 'Checkbox';
			break;
		case 'checkboxes':
			mergeFields.formattedType = 'Checkboxes';
			break;
		case 'date':
			mergeFields.formattedType = 'Date';
			break;
		case 'editor':
			mergeFields.formattedType = 'Editor';
			break;
		case 'radios':
			mergeFields.formattedType = 'Radios';
			break;
		case 'centralised_radios':
			mergeFields.formattedType = 'Centralised radios';
			break;
		case 'select':
			mergeFields.formattedType = 'Select';
			break;
		case 'centralised_select':
			mergeFields.formattedType = 'Centralised select';
			break;
		case 'text':
			mergeFields.formattedType = 'Text';
			break;
		case 'textarea':
			mergeFields.formattedType = 'Textarea';
			break;
		case 'url':
			mergeFields.formattedType = 'URL';
			break;
		case 'other_system_field':
			mergeFields.formattedType = 'Other system field';
			break;
		case 'dataset_select':
			mergeFields.formattedType = 'Dataset select';
			break;
		case 'dataset_picker':
			mergeFields.formattedType = 'Dataset picker';
			break;
		case 'file_picker':
			mergeFields.formattedType = 'File picker';
			break;
		default:
			mergeFields.formattedType = 'Unknown';
			break;
	}
	if (mergeFields.is_system_field) {
		mergeFields.formattedType += ', system field';
	}
	
	
	// Details tab
	
	// Create object to create a select list of centralised lists
	var centralised_lists = {},
		ord = 1,
		func, details;
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
	
	
	mergeFields.dataset_foreign_key_id_options = this.createSelectList(this.tuix.datasets, mergeFields.dataset_foreign_key_id);
	
	
	// Create object to create a select list of checkbox/group fields
	var booleanFields = {};
	foreach (this.tuix.items as tabName => tab) {
		
		var childOptions = {};
		
		foreach (tab.fields as fieldID => fieldDetails) {
			if (fieldDetails.type === 'group' || fieldDetails.type === 'checkbox') {
				childOptions[fieldID] = {
					ord: fieldDetails.ord,
					label: fieldDetails.label
				}
			}
		}
		
		booleanFields[tabName] = {
			label: tab.label,
			ord: tab.ord,
			childOptions: childOptions
		}
	}
	
	// Display tab
	
	mergeFields.parent_id_options = this.createSelectList(booleanFields, mergeFields.parent_id, ' -- Select --', true);
	
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
	mergeFields.width_options = this.createSelectList(width_options, mergeFields.width, ' -- Default width -- ');
	
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
	mergeFields.height_options = this.createSelectList(height_options, mergeFields.height, ' -- Default rows -- ');
	
	var admin_box_visibility_options = {
		show: {
			ord: 1,
			label: 'Show'
		},
		show_on_condition: {
			ord: 2,
			label: 'Show on condition'
		},
		hide: {
			ord: 3,
			label: 'Hide'
		}
	}
	mergeFields.admin_box_visibility_options = this.createSelectList(admin_box_visibility_options, mergeFields.admin_box_visibility);
	
	var store_file_options = {
		in_docstore: {
			ord: 1,
			label: 'Docstore'
		},
		in_database: {
			ord: 2,
			label: 'Database'
		}
	};
	mergeFields.store_file_options = this.createRadioList(store_file_options, mergeFields.store_file, 'store_file');
	
	// Validation tab
	var disabled = mergeFields.is_system_field ? true : false;
	var validation_options = {
		none: {
			ord: 1,
			label: 'None'
		},
		email: {
			ord: 2,
			label: 'Email'
		},
		emails: {
			ord: 3,
			label: 'Multiple emails'
		},
		no_spaces: {
			ord: 4,
			label: 'No spaces allowed'
		},
		numeric: {
			ord: 5,
			label: 'Numeric'
		},
		screen_name: {
			ord: 6,
			label: 'Screen name'
		}
	};
	mergeFields.validation_options = this.createRadioList(validation_options, mergeFields.validation, 'validation', disabled);
	
	// Organizer tab
	var create_index_options = {
		0: {
			ord: 1,
			label: "Don't index"
		},
		1: {
			ord: 2,
			label: 'Index'
		}
	};
	
	mergeFields.create_index_options = this.createRadioList(create_index_options, mergeFields.create_index, 'create_index', disabled);
	
	mergeFields.show_searchable_field = mergeFields.is_text_field ? mergeFields.show_in_organizer : mergeFields.show_in_organizer && mergeFields.create_index;
	
	var organizer_visibility_options = {
		'hide': {
			ord: 1,
			label: 'Hide by default'
		},
		'show_by_default': {
			ord: 2,
			label: 'Show by default'
		},
		'always_show': {
			ord: 3,
			label: 'Always show'
		}
	};
	
	mergeFields.organizer_visibility_options = this.createSelectList(organizer_visibility_options, mergeFields.organizer_visibility);
	
	// find what detail tabs to show for this field
	mergeFields.showDetailsTab = true;
	mergeFields.showValidationTab = (mergeFields.type != 'other_system_field');
	mergeFields.showOrganizerTab = (mergeFields.type != 'other_system_field');
	mergeFields.showValuesTab = (mergeFields.type != 'other_system_field') && ($.inArray(mergeFields.type, ['select', 'radios', 'checkboxes', 'centralised_radios', 'centralised_select']) != -1);
	
	return mergeFields;
};

methods.initCurrentFieldDetails = function() {
	
	var that = this,
		field = (this.tuix.items[this.currentTab].fields[this.current.id] || {});
	
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
	
	
	// Listen for changes
	$('#organizer_field_details :input').on('change', function() {
		that.changeMadeToPanel();
	});
	$('#organizer_field_details input[type="text"], #organizer_field_details textarea').on('keyup', function() {
		that.changeMadeToPanel();
	});
	
	
	// Update label and code name
	$('#field__label').on('keyup', function() {
		$('#organizer_form_field_' + that.current.id + ' .label, #zenario_field_details_header_content h5').text($(this).val());
		if (field.just_added) {
			var db_column = $(this).val().toLowerCase().replace(/[\s-]+/g, '_').replace(/[^a-z_0-9]/g, '');
			$('#field__db_column').val(db_column);
		}
	});
	
	
	// Update note below
	$('#field__note_below').on('keyup', function() {
		var value = $(this).val(),
			$note_below = $('#organizer_form_field_note_below_' + that.current.id),
			$note_below_content = $('#organizer_form_field_note_below_' + that.current.id + ' div.zenario_note_content');
			
		$note_below_content.html(value);
		$note_below.toggle(value !== '');
	});
	
	// Update site note
	$('#field__side_note').on('keyup', function() {
		var value = $(this).val(),
			$side_note = $('#organizer_form_field_side_note_' + that.current.id),
			$side_note_content = $('#organizer_form_field_side_note_' + that.current.id + ' div.zenario_note_content');
		
		$side_note_content.html(value);
		$side_note.toggle(value !== '');
	});
	
	// Init indent slider
	var options = {
		min: 0,
		max: 5,
		slide: function(event, ui) {
			// Add/remove class depending on indent
			
			var $form_field = $('#organizer_form_field_' + that.current.id),
				classes = $form_field.prop('class').split(/\s+/),
				i;
			
			for (i = 0; i < classes.length; i++) {
				if (classes[i].lastIndexOf('zenario_indent_level_', 0) >= 0) {
					$form_field.removeClass(classes[i]);
					break;
				}
			}
			
			if (ui.value > 0) {
				$form_field.addClass('zenario_indent_level zenario_indent_level_' + ui.value);
			} else {
				$form_field.removeClass('zenario_indent_level');
			}
		},
		change: function(event, ui) {
			that.changeMadeToPanel();
			$('#field__indent').val(ui.value);
		}
	};
	if (field.indent) {
		options.value = field.indent;
	}
	$('#field__indent_slider').slider(options);
	
	// Update centralised radio list preview when changing source, set filter to blank
	$('#field_container__values_source :input').on('change', function() {
		
		var $source_filter = $('#field_container__values_source_filter :input');
		$source_filter.val('');
		
		var method = $(this).val(),
			filter = '';
		that.updateCentralisedRadioValues(that.current.id, method, filter);
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
			that.updateCentralisedRadioValues(that.current.id, method, filter);
		}, 1000);
	});
	
	
	// Sort LOV by ordinal
	var lov = this.getOrderedItems(field.lov);
	
	// Place LOV on page
	var html = this.microTemplate('zenario_organizer_admin_box_builder_field_value', lov);
	$('#field_values_list')
		.html(html)
		.sortable({
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
	
	// Init new fields adder
	this.initAddNewFieldsButton();
};

methods.updateCentralisedRadioValues = function(fieldID, method, filter) {
	var actionRequests = {
		mode: 'get_centralised_lov',
		method: method,
		filter: filter
	};
	
	this.sendAJAXRequest(actionRequests).after(function(data) {
		
		var lov = JSON.parse(data),
			html = this.microTemplate('zenario_organizer_admin_box_builder_radio_values_preview', lov);
		
		$('#organizer_form_field_values_' + fieldID).html(html);
	});
};


// Get the current values for a field
methods.getCurrentFieldDetails = function() {
	var field = {},
		current = this.tuix.items[this.currentTab].fields[this.current.id];
	
	// Get input values from details form
	$.each($('#organizer_field_details_form').serializeArray(), function(index, input) {
		field[input.name] = input.value;
	});
	
	// Because serializeArray() ignores unset checkboxes and radio buttons
	$('#organizer_field_details_form input[type=checkbox]').map(function(index, input) {
		field[input.name] = input.checked ? 1 : 0;
	});
	
	// Save multi value fields lovs
	if ((['select', 'radios', 'checkboxes'].indexOf(current.type) != -1) && !current.field_id) {
		field.lov = this.getCurrentFieldValues(current);
	}
	
	// Make sure create_index is saved as an integer (asked to be a radio button rather than a checkbox)
	if (field.create_index) {
		field.create_index = parseInt(field.create_index);
	}
	
	return field;
};



// Called whenever the properties of a field/tab needs to be saved
methods.save = function() {
	var id, value,
		values = {};
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
		
		// Get errors for tab
		if (this.current.type == 'tab') {
			if (!this.tuix.items[this.currentTab].remove) {
				this.sendAJAXRequest(actionRequests).after(function(errors) {
					errors = JSON.parse(errors);
					
					// Remove errors
					$('#organizer_tab_field_error').remove();
					
					if (errors.length > 0) {
						
						// Display errors
						var $errorDiv = $('<div id="organizer_tab_field_error"></div>');
						foreach (errors as index => message) {
							$errorDiv.append('<p class="error">' + message + '</p>');
						}
						
						$('#organizer_tab_details').prepend($errorDiv);
						
						// Shake box
						that.shakeBox('#organizer_admin_form_builder .form_fields_palette');
						
					} else {
						
						// Field is valid, remove just_added parameter
						if (that.tuix.items[that.currentTab].just_added) {
							delete(that.tuix.items[that.currentTab].just_added);
						}
						
					}
					
					cb.call(errors);
				});
				return cb;
			}
		
		// Get errors for field
		} else if (this.current.type == 'field') {
			if (!this.tuix.items[this.currentTab].fields[this.current.id].remove) {
				this.sendAJAXRequest(actionRequests).after(function(errors) {
					errors = JSON.parse(errors);
					
					// Remove errors
						$('#organizer_form_field_error').remove();
					
					if (errors.length > 0) {
						
						// Display errors
						var $errorDiv = $('<div id="organizer_form_field_error"></div>');
						foreach (errors as index => message) {
							$errorDiv.append('<p class="error">' + message + '</p>');
						}
						
						$('#organizer_field_details').prepend($errorDiv);
						
						// Shake box
						that.shakeBox('#organizer_admin_form_builder .form_fields_palette');
						
					} else {
						
						// Field is valid, remove just_added parameter
						if (that.tuix.items[that.currentTab].fields[that.current.id].just_added) {
							delete(that.tuix.items[that.currentTab].fields[that.current.id].just_added);
						}
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
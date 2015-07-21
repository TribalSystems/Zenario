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
	
	For more information, see js_minify.shell.php for steps (1) and (2), and inc-organizer.js.php for step (3).
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
	panelTypes.form_builder = extensionOf(panelTypes.base)
);


//Methods

//Called by Organizer upon the first initialisation of this panel.
//It is not recalled if Organizer's refresh button is pressed, or the administrator changes page
methods.init = function() {
	
};

//Called by Organizer whenever it needs to set the panel data.
methods.cmsSetsPanelTUIX = function(tuix) {
	this.tuix = tuix;
};

//Called by Organizer whenever it needs to set the current tag-path
methods.cmsSetsPath = function(path) {
	this.path = path;
};

//Called by Organizer whenever a panel is first loaded with a specific item requested
methods.cmsSetsRequestedItem = function(requestedItem) {
	this.lastItemClicked =
	this.requestedItem = requestedItem;
};


//If searching is enabled (i.e. your returnSearchingEnabled() method returns true)
//then the CMS will call this method to tell you what the search term was
methods.cmsSetsSearchTerm = function(searchTerm) {
	this.searchTerm = searchTerm;
};

//Use this function to set AJAX URL you want to use to load the panel.
//Initally the this.tuix variable will just contain a few important TUIX properties
//and not your the panel definition from TUIX.
//The default value here is a PHP script that will:
	//Load all of the TUIX properties
	//Call your preFillOrganizerPanel() method
	//Populate items from the database if you set the db_items property in TUIX
	//Call your fillOrganizerPanel() method
//You can skip these steps and not do an AJAX request by returning false instead,
//or do something different by returning a URL to a different PHP script
methods.returnAJAXURL = function() {
	return URLBasePath
		+ 'zenario/admin/ajax.php?_json=1&path='
		+ encodeURIComponent(this.path)
		+ zenario.urlRequest(this.returnAJAXRequests());
};

//Returns the URL that the dev tools will use to load debug information.
//Don't override this function!
methods.returnDevToolsAJAXURL = function() {
	return methods.returnAJAXURL.call(this);
};

//Use this to add any requests you need to the AJAX URL used to call your panel
methods.returnAJAXRequests = function() {
	return {};
};

//You should return the page size you wish to use, or false to disable pagination
methods.returnPageSize = function() {
	return Math.max(20, Math.min(1*zenarioA.siteSettings.organizer_page_size, 500));
};

//Sets the title shown above the panel.
//This is also shown in the back button when the back button would take you back to this panel.
methods.returnPanelTitle = function() {
	var title = this.tuix.title;
	
	if (window.zenarioOSelectMode && (this.path == window.zenarioOTargetPath || window.zenarioOTargetPath === false)) {
		if (window.zenarioOMultipleSelect && this.tuix.multiple_select_mode_title) {
			title = this.tuix.multiple_select_mode_title;
		} else if (this.tuix.select_mode_title) {
			title = this.tuix.select_mode_title;
		}
	}
	
	if (zenarioO.filteredView) {
		title += phrase.refined;
	}
	
	return title;
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


methods.putValuesIntoTUIX = function(tuix, values) {
	var t, tab, f, field;
	
	if (tuix.tabs) {
		foreach (tuix.tabs as t => tab) {
			if (tab.fields) {
				foreach (tab.fields as f => field) {
					
					delete field.value;
					delete field.current_value;
					
					if (values[f] !== undefined) {
						field.current_value = values[f];
					}
				}
			}
		}
	}
};

methods.getValuesFromTUIX = function(tuix) {
	var t, tab, f, field, values= {};
	
	if (tuix.tabs) {
		foreach (tuix.tabs as t => tab) {
			if (tab.fields) {
				foreach (tab.fields as f => field) {
					values[f] = field.current_value;
				}
			}
		}
	}
	
	return values;
};


methods.getOrderedItems = function(items) {
	var item,
		orderedItems = [];
	
	foreach (items as tabName => tab) {
		item = {}
		foreach (tab as key => value) {
			if (key != 'fields') {
				item[key] = value;
			}
		}
		if (tab['fields']) {
			item['fields'] = [];
			foreach (tab['fields'] as id => field) {
				if (field['lov']) {
					var values = [];
					foreach (field['lov'] as id => value) {
						values.splice(value['ord'] - 1, 0, value);
					}
					field['lov'] = values;
				}
				item['fields'].splice(field['ord'] - 1, 0, field);
			}
		} else {
			this.tuix.items[tabName].fields = {};
		}
		orderedItems.splice(item['ord'] - 1, 0, item);
	}
	return orderedItems;
};


//Draw the panel, as well as the header at the top and the footer at the bottom
//This is called every time the panel is loaded, refreshed or when something in the header toolbar is changed.
methods.showPanel = function($header, $panel, $footer) {
	$header.html('').show();
	var that = this,
		items = this.getOrderedItems(this.tuix.items),
		mergeFields = {
			items: items
		},
		html = zenarioA.microTemplate('zenario_organizer_form_builder', mergeFields),
		currentTab = false;
	
	this.changingForm = false;
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
	$('#organizer_field_type_list div.field_type').draggable({
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
		stop: function(event, ui) {
			if (that.startIndex != ui.item.index()) {
				that.currentTabOrd = ui.item.index() + 1;
				that.changeMadeToPanel();
			}
		}
	});
	
	// Add a new tab
	$('#organizer_add_new_tab').on('click', function() {
		that.save();
		if (that.validate() !== false) {
			return false;
		}
		that.addNewTab();
	});
	
	// Show new fields adder
	$('#organizer_add_new_field').on('click', function() {
		that.save();
		if (that.validate() !== false) {
			return false;
		}
		that.current.type = that.current.id = false;
		that.showDetailsSection('organizer_field_type_list');
	});
	
	// Edit a form field
	$('#organizer_form_sections div.form_field').on('click', function() {
		that.fieldClick($(this));
	});
	
	
	var listenForDelete = function() {
		// Listen for field/tab delete
		$('#zenario_fbMessageButtons input.submit_selected').on('click', function() { 
			setTimeout(function() {
				if (that.deleting == 'tab') {
					var $tab, $next;
					
					that.tuix.items[that.current.id] = {remove: true};
					$tab = $('#organizer_form_tab_' + that.current.id);
					
					// Select another tab
					$next = $tab.prev();
					if ($next.length == 0) {
						$next = $tab.next();
					}
					
					// Remove tab element
					$tab.remove();
					that.changeMadeToPanel();
					if ($next.length == 1) {
						that.tabClick($next);
					}
				} else if (that.deleting = 'field') {
					that.removeField();
				}
				that.deleting = false;
			});
		});
	}
	
	// Init remove buttons
	$('#organizer_remove_form_tab').on('click', function() {
		if (that.tuix.items[that.current.id].is_system_field == 0) { 
			that.deleting = 'tab';
			zenarioA.showMessage('Are you sure you want to delete this Tab?<br><br>All fields on this tab will also be deleted.', 'Delete', 'warning', true);
			listenForDelete();
		}
	});
	
	$('#organizer_remove_form_field').on('click', function() {
		that.deleting = 'field';
		zenarioA.showMessage('Are you sure you want to delete this Field?', 'Delete', 'warning', true);
		listenForDelete();
	});
	
	$footer.html('').show();
};


methods.addNewTab = function() {
	var tabName = '__custom_tab_t' + this.maxNewCustomTab++,
		label = 'Untitled tab',
		mergeFields = {
			name: tabName,
			label: label
		},
		html = zenarioA.microTemplate('zenario_organizer_form_builder_tab', mergeFields);
	$('#organizer_add_new_tab').before(html);
	this.tuix.items[tabName] = {
		label: label,
		is_new_tab: true,
		is_system_field: 0,
		fields: {}
	};
	// Create new section for fields
	html = zenarioA.microTemplate('zenario_organizer_form_builder_section', mergeFields);
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


methods.removeField = function() {
	var that = this,
		id = that.current.id,
		$field,
		$next;
	
	if (!this.tuix.items[this.currentTab].fields[id] || (this.tuix.items[this.currentTab].fields[id].is_system_field == 0)) {
		// Mark for deletion
		this.tuix.items[this.currentTab].fields[id] = {remove: true};
		$field = $('#organizer_form_field_' + id);
		
		if ($field) {
			// Select the previous field that isn't already being removed (because of the animation), otherwise look for the next field
			// othereise show add fields panel
			
			$next = this.selectNextField($field);
			that.changeMadeToPanel();
			
			$field.animate({height: 0, opacity: 0}, 500, function() {
				$field.remove();
				if ($next === false) {
					that.showNoFieldsMessage();
				}
			});
		}
	}
}

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
}

methods.showNoFieldsMessage = function() {
	$('#organizer_section_' + this.currentTab).addClass('empty').html(
		'<span class="no_fields_message">There are no fields on this tab</span>');
	this.showDetailsSection('organizer_field_type_list');
}

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
				$next;
			
			if ((that.current.type == 'field') && (id == that.current.id)) {
				that.save();
				if (that.validate() !== false) {
					canDrop = false;
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
	
	this.removeNoItemsMessage(this.currentTab);
	
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
	
	if (type == 'centralised_radios') {
		foreach (this.tuix.centralised_lists.values as method) {
			values_source = method;
			break;
		}
		mergeFields.lov = _.toArray(this.tuix.centralised_lists.initial_lov);
	}
	
	var html = zenarioA.microTemplate('zenario_organizer_form_builder_field', mergeFields);
	
	$field.replaceWith(html);
	this.tuix.items[this.currentTab].fields[id] = {
		id: id,
		label: label,
		type: type,
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
		values_source: values_source
	}
	
	if (mergeFields.lov) {
		this.tuix.items[this.currentTab].fields[id].lov = mergeFields.lov;
	}
	
	var $newField = $('#organizer_form_field_' + id);
	$newField.effect({effect: 'highlight', duration: 1000});
	
	// Init field
	this.initField($newField);
	
	// Open properties for new field
	this.fieldClick($newField);
};


methods.initField = function($field) {
	var that = this;
	$field.on('click', function() {
		that.fieldClick($(this));
	});
};


methods.tabClick = function($tab) {
	
	var that = this,
		values = {};
	
	this.save();
	if (this.validate() !== false) {
		return false;
	}
	
	// Disable old section
	$('#organizer_section_' + this.currentTab).sortable('disable');
	
	this.currentTab = $tab.data('name');
	this.currentTabOrd = $tab.index() + 1;
	this.current = {id: this.currentTab, type: 'tab'};
	
	// Enable new section
	$('#organizer_section_' + this.currentTab).sortable('enable');
	
	$('#organizer_form_tabs div.tab').removeClass('on');
	$tab.addClass('on');
	$('#organizer_form_sections div.form_section').hide();
	
	// Load selected tabs current properties
	values = this.tuix.items[this.currentTab] || {};
	this.setCurrentTabDetails(values);
	
	// Hide remove button if system field
	if (this.tuix.items[this.currentTab] && (this.tuix.items[this.currentTab].is_system_field == 1)) {
		$('#organizer_remove_form_tab').hide();
	} else {
		$('#organizer_remove_form_tab').show();
	}
	
	// Show details panel
	this.showDetailsSection('organizer_tab_details');
	
	// Show tab fields
	$('#organizer_section_' + this.currentTab).show();
};

methods.setCurrentTabDetails = function(values) {
	var values = _.clone(values);
	values.parent_id_select_list = this.orderedItems;
	var that = this;
		html = zenarioA.microTemplate('zenario_organizer_form_builder_tab_details', values)
	$('#organizer_tab_details_inner').html(html);
	
	$('#organizer_tab_details :input').off().on('change', function() {
		that.changeMadeToPanel();
	});
	
	$('#organizer_tab_details input[type="text"]').on('keyup', function() {
		that.changeMadeToPanel();
	});
	
	$('#tab__label').on('keyup', function() {
		$('#organizer_form_tab_' + that.currentTab + ' span').text($(this).val());
	});
};

methods.getCurrentTabDetails = function() {
	var tab = {};
	tab.label = $('#tab__label').val();
	tab.parent_field_id = $('#tab__parent_field_id').val();
	return tab;
};

methods.fieldClick = function($field) {
	
	var id = $field.data('id');
	
	this.save();
	if (this.validate() !== false) {
		return false;
	}
	this.current = {type: 'field', id: id};
	
	// Add class to selected field
	$('#organizer_form_sections .form_field').removeClass('selected');
	$field.addClass('selected');
	
	
	var values = (this.tuix.items[this.currentTab]['fields'][id] || {});
	this.setCurrentFieldDetails(values);
	this.showFieldDetailsSection('details', true);
	
	// Hide remove button if system field
	if (this.tuix.items[this.currentTab].fields[id] && (this.tuix.items[this.currentTab].fields[id].is_system_field == 1)) {
		$('#organizer_remove_form_field').hide();
	} else {
		$('#organizer_remove_form_field').show();
	}
	
	// Show details panel
	this.showDetailsSection('organizer_field_details');
};

methods.setCurrentFieldDetails = function(values) {
	
	var values = _.clone(values);
	values.parent_id_select_list = this.orderedItems;
	values.centralised_lists = this.tuix.centralised_lists;
	values.dataset_label = this.tuix.dataset_label;
	values.is_text_field = ($.inArray(values.type, ['date', 'editor', 'text', 'textarea', 'url']) > -1);
	values.is_checkbox_field = ($.inArray(values.type, ['group', 'checkbox']) > -1);
	values.is_list_field = ($.inArray(values.type, ['checkboxes', 'radios', 'centralised_radios', 'select', 'centralised_select']) > -1);
	values.show_searchable_field = values.is_text_field ? values.show_in_organizer : values.show_in_organizer && values.create_index;
	var that = this,
		html = zenarioA.microTemplate('zenario_organizer_form_builder_field_details', values);
	
	$('#organizer_field_details_inner').html(html);
	
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
		if (values.just_added) {
			var db_column = $(this).val().toLowerCase().replace(/[\s-]+/g, '_').replace(/[^a-z_0-9]/g, '');
			$('#field__db_column').val(db_column);
		}
	});
	
	// Disable code name if protected and existing field
	$('#field__is_protected').on('change', function() {
		if (!values.is_new_field) {
			$('#field__db_column').prop('readonly', $(this).prop('checked'));
		}
	});
	
	
	$('#field__label').val(values.label);
	$('#field__db_column').val(values.db_column);
	$('#field__note_below').val(values.note_below);
	$('#field__is_protected').prop('checked', values.is_protected);
	$('input:radio[name="field__create_index"]').filter('[value="' +  values.create_index + '"]').prop('checked', true);
	
	// Disable code name for system fields
	if (values.is_system_field) {
		$('#field__db_column').prop('readonly', true);
	} else {
		var visibility = (values.always_show ? 2 : (values.show_by_default ? 1 : ''));
		
		$('#field__required').prop('checked', values.required);
		if (values.required) {
			$('#field__required_message').val(values.required_message);
		}
		$('#field__required').on('change', function() {
			$('#field_container__required_message').toggle(this.checked);
		});
		$('#field__show_in_organizer').prop('checked', values.show_in_organizer);
		$('#field__show_in_organizer').on('change', function() {
			$('#field_container__visibility').toggle(this.checked);
			
			var create_index = ($('input:radio[name="field__create_index"]:checked').val() == true);
				searchable = values.is_text_field ? this.checked : (create_index && this.checked);
			$('#field_container__searchable').toggle(searchable);
			$('#field_container__sortable').toggle(create_index && this.checked);
		});
		
		$('input:radio[name="field__create_index"]').on('change', function() {
			var create_index = $('input:radio[name="field__create_index"]:checked').val() == true,
				show_in_organizer = $('#field__show_in_organizer').prop('checked'),
				searchable = values.is_text_field ? show_in_organizer : (create_index && show_in_organizer);
			
			$('#field_container__searchable').toggle(searchable);
			$('#field_container__sortable').toggle(create_index && show_in_organizer);
		});
		
		$('#field__visibility').val(visibility);
	}
	
	if (values.db_column) {
		$('#field__include_in_export').prop('checked', (values.include_in_export == true));
	}
	
	switch (values.type) {
		case 'select':
		case 'checkboxes':
		case 'radios':
			// Order LOV by ordinal
			var lov = [];
			foreach (values.lov as id => value) {
				if (!values.remove) {
					lov[value.ord] = value
				}
			}
			
			// Place LOV on page
			var html = zenarioA.microTemplate('zenario_organizer_form_builder_field_value', lov);
			$('#field_values_list')
				.html(html)
				.sortable({
					start: function(event, ui) {
						that.startIndex = ui.item.index();
					},
					stop: function(event, ui) {
						that.redrawCurrentFieldValues();
						if (that.startIndex != ui.item.index()) {
							that.changeMadeToPanel();
						}
					}
				})
			this.initFieldValues();
			
			// Setup add button
			$('#organizer_add_a_field_value').on('click', function() {
				var id = that.maxNewCustomFieldValue++,
					mergeFields = {
						id: 't' + id,
						label: 'Untitled'
					},
					html = zenarioA.microTemplate('zenario_organizer_form_builder_field_value', mergeFields);
				$('#field_values_list').append(html);
				that.redrawCurrentFieldValues();
				that.initFieldValues();
			});
			break;
		case 'centralised_select':
		case 'centralised_radios':
			if (!values.is_system_field) {
				var $values_source = $('#field__values_source'),
					$values_source_filter = $('#field__values_source_filter'),
					$values_source_filter_label = $('#field_label__source_filter'),
					$values_source_filter_container = $('#field_container__values_source_filter'),
					label, can_filter;
				
				// Set values
				$values_source.val(values.values_source);
				label = $values_source.find(':selected').data('label');
				if (values.values_source && label) {
					$values_source_filter_label.html(label);
				}
				if (values.values_source_filter) {
					$values_source_filter.val(values.values_source_filter);
				}
				can_filter = $values_source.find(':selected').data('filter');
				if (can_filter) {
					$values_source_filter_container.show();
				}
				
				if (values.type == 'centralised_radios') {
					$values_source.on('change', function() {
						
						// Show/hide filter option and change label
						var $option = $(this).find(':selected');
						$values_source_filter_container.toggle($option.data('filter') == true);
						if ($option.data('label')) {
							$values_source_filter_label.html($option.data('label'));
						}
						
						that.updateCentralisedRadioValues($values_source, $values_source_filter, $values_source_filter_container, values);
					});
					
					$values_source_filter.on('blur', function() {
						that.updateCentralisedRadioValues($values_source, $values_source_filter, $values_source_filter_container, values);
					});
				}
			}
			break;
		case 'textarea':
		case 'editor':
			var height = (values.height == false) ? '' : values.height,
				width = (values.width == false) ? '' : values.width;
			$('#field__height').val(height);
			$('#field__width').val(width);
			break;
		case 'text':
			if (!values.is_system_field) {
				var validation = (values.validation == false) ? 'none' : values.validation;
				
				$('#field__validation__' + validation).prop('checked', true);
				if (validation != 'none') {
					$('#field__validation_message').val(values.validation_message);
				}
				
				// Show/hide validation message box
				$('#field_section__validation input:radio[name="field__validation"]').on('change', function() {
					$('#field_container__validation_message').toggle($(this).prop('id') != 'field__validation__none');
				});
			}
			break;
	}
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
			html = zenarioA.microTemplate('zenario_organizer_form_builder_radio_values', lov);
		$('#organizer_form_field_values_' + values.id).html(html);
	});
};

methods.redrawCurrentFieldValues = function() {
	
	var id = this.current.id,
		field = this.tuix.items[this.currentTab].fields[id],
		mergeFields = this.getCurrentFieldValues(field),
		html = '';
	if ($.inArray(field.type, ['checkboxes', 'radios']) > -1) {
		if (field.type == 'checkboxes') {
			html = zenarioA.microTemplate('zenario_organizer_form_builder_checkbox_values', mergeFields);
		} else if (field.type == 'radios') {
			html = zenarioA.microTemplate('zenario_organizer_form_builder_radio_values', mergeFields);
		}
		$('#organizer_form_field_values_' + id).html(html);
	}
};

methods.initFieldValues = function() {
	var that = this;
	// Handle remove button
	$('#field_values_list div.remove').off().on('click', function() {
		var id = $(this).data('id');
		$('#organizer_field_value_' + id).remove();
		that.changeMadeToPanel();
		$(this).parent().remove();
	});
	
	// Update form labels
	$('#field_values_list input').off().on('keyup', function() {
		var id = $(this).data('id');
		$('#organizer_field_value_' + id + ' label').text($(this).val());
	});
};

methods.getCurrentFieldDetails = function() {
	var field = {},
		current = this.tuix.items[this.currentTab].fields[this.current.id],
		is_system_field = (current.is_system_field == true);
	
	field.label = $('#field__label').val();
	field.db_column = $('#field__db_column').val();
	field.is_protected = $('#field__is_protected').prop('checked');
	field.note_below = $('#field__note_below').val();
	
	if (!is_system_field) {
		field.parent_id = $('#field__parent_id').val();
		field.required = $('#field__required').prop('checked');
		field.required_message = (field.required == true) ? $('#field__required_message').val() : '';
		field.show_in_organizer = $('#field__show_in_organizer').prop('checked');
		field.create_index = ($('input:radio[name="field__create_index"]:checked').val() == true);
		
		if (field.show_in_organizer) {
			var visibility =  $('#field__visibility').val();
			field.always_show = (visibility == 2);
			field.show_by_default = (visibility == 1);
			field.searchable = $('#field__searchable').prop('checked');
			if (field.create_index) {
				field.sortable = $('#field__sortable').prop('checked');
			}
		}
	}
	
	if (current.db_column) {
		field.include_in_export = $('#field__include_in_export').prop('checked');
	}
	
	switch (current.type) {
		case 'select':
		case 'checkboxes':
		case 'radios':
			field.lov = this.getCurrentFieldValues(current);
			break;
		case 'centralised_select':
		case 'centralised_radios':
			if (!is_system_field) {
				field.values_source = $('#field__values_source').val();
				field.values_source_filter = (field.values_source) ? $('#field__values_source_filter').val() : '';
			}
			break;
		case 'editor':
		case 'textarea':
			field.height = $('#field__height').val();
			field.width = $('#field__width').val();
			break;
		case 'text':
			if (!is_system_field) {
				field.validation = $('#field_section__validation input:radio[name="field__validation"]:checked').val();
				field.validation_message = (field.validation != 'none') ? $('#field__validation_message').val() : '';
			}
			break;
	}
	return field;
};

methods.getCurrentFieldValues = function(field) {
	var values = [];
	foreach (field.lov as i => value) {
		field.lov[i] = {remove: true};
	}
	$('#field_values_list div.field_value').each(function(i, value) {
		values[++i] = {
			id: $(this).data('id'),
			label: $(value).find('input').val(),
			ord: i
		};
	});
	return values;
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
	
	var error = [];
	if (this.current.id && this.current.type) {
		if (this.current.type == 'tab') {
			// validate tab
		} else if (this.current.type == 'field') {
			var field = this.tuix.items[this.currentTab].fields[this.current.id],
				id;
			
			// Find errors on current tab
			switch (this.currentFieldTab) {
				case 'details':
					if (field.is_system_field == false) {
						
						id = (_.invert(this.current_db_columns))[field.db_column] 
						  || (_.invert(this.tuix.existing_db_columns[field.db_column]))[field.db_column] 
						  || false;
						
						if (field.db_column === '') {
							error.push('Please enter a code name');
						} else if (id && (id != field.id)) {
							error.push('The code name "' + field.db_column + '" is already in use in this dataset.');
						} else {
							this.current_db_columns[field.id] = field.db_column;
						}
					}
					break
				case 'display':
					break;
				case 'validation':
					if (field.required == true && field.required_message === '') {
						error.push('Please enter a message if not complete.');
					}
					
					if (field.validation != 'none' && field.validation_message === '') {
						error.push('Please enter a message if not valid.');
					}
					break;
				case 'values':
					break;
			}
			
			// Display any errors
			if (error.length > 0) {
				
				foreach (error as index => message) {
					error[index] = '<p>' + message + '</p>';
				}
				
				var $errorDiv = $('<div id="organizer_form_field_error" class="error"></div>');
				$errorDiv.append(error);
				
				$('#organizer_form_field_error').html('');
				$('#organizer_field_details').prepend($errorDiv);
				
			} else {
				$('#organizer_form_field_error').remove();
				delete(this.tuix.items[this.currentTab].fields[this.current.id].just_added);
			}
		}
	}
	return (error.length > 0);
};

methods.showFieldDetailsSection = function(section, noValidation) {
	
	if (!noValidation) {
		this.save();
		if (this.validate() !== false) {
			return false;
		}
	}
	
	this.currentFieldTab = section;
	
	// Mark current tab
	$('#organizer_field_details_tabs div.tab').removeClass('on');
	$('#field_tab__' + section).addClass('on');
	
	// Show current section
	$('#organizer_field_details div.section').hide();
	$('#field_section__' + section).show();
};

methods.showDetailsSection = function(section) {
	var sections = ['organizer_field_type_list', 'organizer_field_details', 'organizer_tab_details'],
		index = $.inArray(section, sections);
	if (index > -1) {
		
		// Show selected sections and destroy other forms
		$('#' + section + '_outer').show();
		
		if (section == 'organizer_field_type_list') {
			$('#organizer_add_new_field').hide();
			this.current = false;
		} else {
			$('#organizer_add_new_field').show();
		}
		
		delete(sections[index]);
		
		// Hide other sections
		foreach (sections as key => section) {
			if (section) {
				$('#' + section + '_outer').hide();
			}
		}
	}
};

methods.changeMadeToPanel = function() {
	if (!this.changingForm) {
		this.changingForm = true;
		window.onbeforeunload = function() {
			return 'You are currently editing this dataset. If you leave now you will lose any unsaved changes.'
		}
		zenarioO.disableInteraction();
		zenarioO.setButtons();
	}
};


//Draw (or hide) the button toolbar
//This is called every time different items are selected, the panel is loaded, refreshed or when something in the header toolbar is changed.
methods.showButtons = function($buttons) {
	var that = this,
		html = '';
	
	if (this.changingForm) {
		//Change the buttons to apply/cancel buttons
		$buttons.html(zenarioA.microTemplate('zenario_organizer_apply_cancel_buttons', {}));
		
		//Add an event to the Apply button to save the changes
		var lock = false
		$buttons.find('#organizer_applyButton')
			.click(function() {
				that.save();
				if (that.validate() !== false) {
					return false;
				}
				if (lock == true) {
					return false;
				}
				zenarioA.nowDoingSomething('saving', true);
				lock = true;
				that.saveChanges();
			});
		
		$buttons.find('#organizer_cancelButton')
			.click(function() {
				window.onbeforeunload = false;
				zenarioO.enableInteraction();
				zenarioO.reload();
			});
		
	} else {
		$buttons.html(html).show();
	}
};

methods.saveChanges = function() {
	
	var that = this,
		actionRequests = zenarioO.getKey(),
		actionTarget = 
		'zenario/ajax.php?' +
			'__pluginClassName__=' + this.tuix.class_name +
			'&__path__=' + zenarioO.path +
			'&method_call=handleOrganizerPanelAJAX';
	
	// Get order of tabs and fields
	$('#organizer_form_tabs div.tab.sort').each(function(i, tab) {
		var name = $(tab).data('name');
		that.tuix.items[name].ord = (i + 1);
		$('#organizer_section_' + name + ' .form_field').each(function(j, field) {
			var id = $(field).data('id');
			that.tuix.items[name].fields[id].ord = (j + 1);
			that.tuix.items[name].fields[id].tab_name = name;
		});
	});
	
	actionRequests.mode = 'save';
	actionRequests.data = JSON.stringify(this.tuix.items);
	
	zenario.ajax(
		URLBasePath + actionTarget,
		actionRequests
	).after(function(message) {
		if (message) {
			zenarioA.showMessage(message);
		}
		zenarioA.nowDoingSomething();
		window.onbeforeunload = false;
		zenarioO.enableInteraction();
		zenarioO.reload();
	});
};

//Called whenever Organizer is resized - i.e. when the administrator resizes their window.
//It's also called on the first load of your panel after your showPanel() and setButtons() methods have been called.
methods.sizePanel = function($header, $panel, $footer, $buttons) {
	//...
};

//This is called when an admin navigates away from your panel, or your panel is about to be refreshed/reloaded.
methods.onUnload = function($header, $panel, $footer) {
	this.saveScrollPosition($panel);
};

//Remember where the admin had scrolled to.
//If we ever draw this panel again it would be nice to restore this to how it was
methods.saveScrollPosition = function($panel) {
	this.scrollTop = $panel.scrollTop();
	this.scrollLeft = $panel.scrollLeft();
};

//If this panel has been displayed before, try to restore the admin's previous scroll
//Otherwise show the top left (i.e. (0, 0))
methods.restoreScrollPosition = function($panel) {
	$panel
		.scrollTop(this.scrollTop || 0)
		.scrollLeft(this.scrollLeft || 0)
		.trigger('scroll');
};





methods.checkboxClick = function(id, e) {
	zenario.stop(e);
	
	var that = this;
	
	setTimeout(function() {
		that.itemClick(id, undefined, true);
	}, 0);
};


methods.itemClick = function(id, e, isCheckbox) {
	if (!this.tuix || !this.tuix.items[id]) {
		return false;
	}
	
	//If the admin is holding down the shift key...
	if (zenarioO.multipleSelectEnabled && !isCheckbox && (e || event).shiftKey && this.lastItemClicked) {
		//...select everything between the current item and the last item that they clicked on
		zenarioO.selectItemRange(id, this.lastItemClicked);
	
	//If multiple select is enabled and the checkbox was clicked...
	} else if (zenarioO.multipleSelectEnabled && isCheckbox) {
		//...toogle the item that they've clicked on
		if (this.selectedItems[id]) {
			this.deselectItem(id);
		} else {
			this.selectItem(id);
		}
		zenarioO.closeInspectionView();
		this.lastItemClicked = id;
	
	//If multiple select is not enabled and the checkbox was clicked
	} else if (!zenarioO.multipleSelectEnabled && isCheckbox && this.selectedItems[id]) {
		//...deselect everything if this row was already selected
		zenarioO.deselectAllItems();
		zenarioO.closeInspectionView();
		this.lastItemClicked = id;
	
	//Otherwise select the item that they've just clicked on, and nothing else
	} else {
		zenarioO.closeInspectionView();
		zenarioO.deselectAllItems();
		this.selectItem(id);
		this.lastItemClicked = id;
	}
	
	
	zenarioO.setButtons();
	zenarioO.setHash();
	
	return false;
};





//Return an object of currently selected item ids
//This should be an object in the format {1: true, 6: true, 18: true}
methods.returnSelectedItems = function() {
	return this.selectedItems;
};

//This method will be called when the CMS sets the items that are selected,
//e.g. when your panel is initially loaded.
//This is an object in the format {1: true, 6: true, 18: true}
//It is usually called before your panel is drawn so you do not need to update the state
//of the items on the page.
methods.cmsSetsSelectedItems = function(selectedItems) {
	this.selectedItems = selectedItems;
};

//This method should cause an item to be selected.
//It is called after your panel is drawn so you should update the state of your items
//on the page.
methods.selectItem = function(id) {
	this.selectedItems[id] = true;
	$(get('organizer_item_' + id)).addClass('organizer_selected');
	this.updateItemCheckbox(id, true);
};

//This method should cause an item to be deselected
//It is called after your panel is drawn so you should update the state of your items
//on the page.
methods.deselectItem = function(id) {
	delete this.selectedItems[id];
	$(get('organizer_item_' + id)).removeClass('organizer_selected');
	this.updateItemCheckbox(id, false);
};

//This updates the checkbox for an item, if you are showing checkboxes next to items,
//and the "all items selected" checkbox, if it is on the page.
methods.updateItemCheckbox = function(id, checked) {
	
	//Check to see if there is a checkbox next to this item first.
	var checkbox = get('organizer_itemcheckbox_' + id);
	
	if (checkbox) {
		$(get('organizer_itemcheckbox_' + id)).prop('checked', checked);
	}
	
	//Change the "all items selected" checkbox, if it is on the page.
	if (zenarioO.allItemsSelected()) {
		$('#organizer_toggle_all_items_checkbox').prop('checked', true);
	} else {
		$('#organizer_toggle_all_items_checkbox').prop('checked', false);
	}
};

//Return whether you want to enable inspection view
methods.returnInspectionViewEnabled = function() {
	return false;
};

//Toggle inspection view
methods.toggleInspectionView = function(id) {
	if (id == zenarioO.inspectionViewItemId()) {
		this.closeInspectionView(id);
	
	} else {
		this.openInspectionView(id);
	}
};

//This method should open inspection view
methods.openInspectionView = function(id) {
	//...
};

//This method should close inspection view
methods.closeInspectionView = function(id) {
	//...
};




}, zenarioO.panelTypes);
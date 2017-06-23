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
	
	For more information, see js_minify.shell.php for steps (1) and (2), and organizer.wrapper.js.php for step (3).
*/


zenario.lib(function(
	undefined,
	URLBasePath,
	document, window, windowOpener, windowParent,
	zenario, zenarioA, zenarioT, zenarioAB, zenarioAT, zenarioO,
	encodeURIComponent, get, engToBoolean, htmlspecialchars, jsEscape, phrase,
	extensionOf, methodsOf, has,
	panelTypes, extraVar2, s$s
) {
	"use strict";


//Note: extensionOf() and methodsOf() are our shortcut functions for class extension in JavaScript.
	//extensionOf() creates a new class (optionally as an extension of another class).
	//methodsOf() allows you to get to the methods of a class.
var methods = methodsOf(
	panelTypes.admin_box_builder = extensionOf(panelTypes.form_builder_base_class)
);

//Draw the panel, as well as the header at the top and the footer at the bottom
//This is called every time the panel is loaded, refreshed or when something in the header toolbar is changed.
methods.showPanel = function($header, $panel, $footer) {
	$header.html('').hide();
	$footer.html('').show();
	
	//Load main panel
	var html = this.loadPanelHTML();
	$panel.html(html).show();
	
	//On initial load select the first tab
	if (!this.currentTabId) {
		var tabs = this.getOrderedTabs();
		if (tabs.length > 0) {
			this.currentTabId = tabs[0].name;
		}
	}
	
	this.deletedTabs = [];
	this.deletedFields = [];
	this.changeDetected = false;
	this.maxNewCustomTab = 1;
	this.maxNewCustomField = 1;
	this.maxNewCustomFieldValue = 1;
	
	this.selectedDetailsTab = this.selectedDetailsTab ? this.selectedDetailsTab : false;
	this.selectedFieldId = this.selectedFieldId ? this.selectedFieldId : false;
	this.selectedTabId = this.selectedTabId ? this.selectedTabId : false;
	
	//Load right panel
	this.loadTabsList(this.currentTabId);
	this.loadFieldsList(this.currentTabId);
	
	//Load left panel
	if (this.selectedFieldId) {
		this.loadFieldDetailsPanel(this.selectedFieldId, true);
		this.highlightField(this.selectedFieldId);
	} else if (this.selectedTabId) {
		this.loadTabDetailsPanel(this.selectedTabId, true);
	} else {
		this.loadNewFieldsPanel(true);
	}
	
	//Show Growl message if saved changes
	if (this.changesSaved) {
		this.changesSaved = false;
		var toast = {
			message_type: 'success',
			message: 'Your changes have been saved!'
		};
		zenarioA.toast(toast);
	}
};

methods.loadPanelHTML = function() {
	var mergeFields = {
		dataset_label: this.tuix.dataset.label
	};
	var html = this.microTemplate('zenario_organizer_admin_box_builder', mergeFields);
	return html;
};

methods.loadFieldDetailsPanel = function(fieldId, stopEffect) {
	var field = this.tuix.items[this.currentTabId].fields[fieldId];
	
	//Load HTML
	var mergeFields = {
		mode: 'field_details',
		field_label: field.field_label,
		hide_tab_bar: this.tuix.field_details.hide_tab_bar
	};
	
	var plural = field.record_count == 1 ? '' : 's';
	var record_count = field.record_count ? field.record_count : 0;
	mergeFields.record_count = '(' + record_count + ' record' + plural + ')';
	
	mergeFields.type = this.getFieldReadableType(this.currentTabId, fieldId);
	if (field.is_system_field) {
		mergeFields.type += ', system field';
	}
	
	var html = this.microTemplate('zenario_organizer_admin_box_builder_left_panel', mergeFields);
	var $div = $('#organizer_admin_form_builder .form_fields_palette .form_fields_palette_outer').html(html);
	
	if (!stopEffect) {
		$div.hide().show('drop', {direction: 'left'}, 200);
	}
	
	//Load TUIX tabs HTML
	var tuix = this.tuix.field_details.tabs;
	var tabs = this.sortAndLoadTUIXTabs(tuix, field, this.selectedDetailsTab);
	var html = this.getTUIXTabsHTML(tabs);
	$('#organizer_field_details_tabs').html(html);
	
	//Add field details tab events
	var that = this;
	$('#organizer_field_details_tabs .tab').on('click', function() {
		var tab = $(this).data('name');
		if (tab && tab != that.selectedDetailsTab) {
			var errors = that.saveCurrentOpenDetails();
			if (!errors) {
				that.loadFieldDetailsTab(tab, fieldId);
			}
		}
	});
	
	//Load TUIX fields html
	this.loadFieldDetailsTab(this.selectedDetailsTab, fieldId);
	
	//Add panel events
	$('#organizer_field_details_inner span.add_new_field').on('click', function() {
		var errors = that.saveCurrentOpenDetails();
		if (!errors) {
			that.selectedFieldId = false;
			that.loadNewFieldsPanel();
			that.highlightField(false);
		}
	});
};

methods.getFieldReadableType = function(tab, fieldId, getOtherSystemFieldTUIXType) {
	var field = this.tuix.items[tab].fields[fieldId];
	switch (field.type) {
		case 'group':
			return 'Group';
		case 'checkbox':
			return 'Flag';
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
		case 'other_system_field':
			if (!getOtherSystemFieldTUIXType) {
				return 'Other system field';
			} else {
				switch (field.tuix_type) {
					case 'html_snippet':
						return 'HTML snippet';
					case 'pick_items':
						return 'Item picker';
					case 'toggle':
						return 'Toggle';
					case 'grouping':
						return 'Grouping';
					default:
						return 'Unknown';
				}
			}
		case 'dataset_select':
			return 'Dataset select';
		case 'dataset_picker':
			return 'Dataset picker';
		case 'file_picker':
			return 'File picker';
		default:
			return 'Unknown';
	}
};

methods.highlightField = function(fieldId) {
	$('#organizer_form_fields .form_field .form_field_inline_buttons').hide();
	$('#organizer_form_fields .form_field').removeClass('selected');
	if (fieldId) {
		$('#organizer_form_field_' + fieldId).addClass('selected');
		$('#organizer_form_field_' + fieldId + ' .form_field_inline_buttons').show();
	}
};

methods.loadFieldDetailsTab = function(tab, fieldId, errors) {
	this.selectedDetailsTab = tab;
	var item = this.tuix.items[this.currentTabId].fields[fieldId];
	
	var path = 'field_details/' + tab;
	var tuix = this.tuix.field_details.tabs[tab].fields;
	var fields = this.loadFields(path, tuix, item);
	var formattedFields = this.formatFieldDetails(fields, item, 'field', this.selectedDetailsTab);
	var sortedFields = this.sortFields(formattedFields, item);
	var html = '';
	
	//Add any errors
	if (!_.isEmpty(errors)) {
		errors = this.sortErrors(errors);
		for (var i = 0; i < errors.length; i++) {
			html += '<p class="error">' + errors[i] + '</p>';
		}
	}
	
	html += this.getTUIXFieldsHTML(sortedFields);
	
	$('#organizer_field_details_form').html(html);
	$('#organizer_field_details_tabs .tab').removeClass('on');
	$('#field_tab__' + tab).addClass('on');
	if (!_.isEmpty(errors)) {
		$('#organizer_field_details_form').effect({
			effect: 'bounce',
			duration: 125,
			direction: 'right',
			times: 2,
			distance: 5,
			mode: 'effect'
		});
	}
	
	//Add events
	
	//Init values list editor
	var that = this;
	if (tab == 'details') {
		$('#field__field_label').on('keyup', function() {
			$('#organizer_form_field_' + fieldId + ' .label, #zenario_field_details_header_content .field_name h5').text($(this).val());
			if (item.is_new_field) {
				var db_column = $(this).val().toLowerCase().replace(/[\s-]+/g, '_').replace(/[^a-z_0-9]/g, '');
				$('#field__db_column').val(db_column);
			}
		});
		
		$('#field__note_below').on('keyup', function() {
			var value = $(this).val().trim();
			$('#organizer_form_field_note_below_' + fieldId + ' div.zenario_note_content').text(value);
			$('#organizer_form_field_note_below_' + fieldId).toggle(value !== '');
		});
		
		$('#field__side_note').on('keyup', function() {
			var value = $(this).val().trim();
			$('#organizer_form_field_side_note_' + fieldId + ' div.zenario_note_content').text(value);
			$('#organizer_form_field_side_note_' + fieldId).toggle(value !== '');
		});
		
	} else if (tab == 'values') {
		if (formattedFields.values && !formattedFields.values._hidden) {
			var values = this.getOrderedFieldValues(this.currentTabId, fieldId);
			html = this.microTemplate('zenario_organizer_admin_box_builder_field_value', values);
		
			$('#field_values_list').html(html).sortable({
				containment: 'parent',
				tolerance: 'pointer',
				axis: 'y',
				start: function(event, ui) {
					that.startIndex = ui.item.index();
				},
				stop: function(event, ui) {
					if (that.startIndex != ui.item.index()) {
						that.saveFieldListOfValues(fieldId);
						that.loadFieldValuesListPreview(fieldId);
						that.changeMadeToPanel();
					}
				}
			});
			
			$('#field_values_list input').on('keyup', function() {
				var id = $(this).data('id');
				$('#organizer_field_value_' + id + ' label').text($(this).val());
				that.changeMadeToPanel();
			});
		
			//Add new field value
			$('#organizer_add_a_field_value').on('click', function() {
				that.addFieldValue(item);
				that.saveItemTUIXTab('field_details', tab, item);
				that.loadFieldDetailsTab(tab, fieldId);
				that.loadFieldValuesListPreview(fieldId);
				that.changeMadeToPanel();
			});
		
			//Delete field value
			$('#field_values_list .delete_icon').on('click', function() {
				var id = $(this).data('id');
				if (item.lov[id]) {
					if (!item._deleted_lov) {
						item._deleted_lov = [];
					}
					item._deleted_lov.push(id);
					delete(item.lov[id]);
				}
				that.saveItemTUIXTab('field_details', tab, item);
				that.loadFieldDetailsTab(tab, fieldId);
				that.loadFieldValuesListPreview(fieldId);
				that.changeMadeToPanel();
			});
		}
	}
};

methods.loadFieldValuesListPreview = function(fieldId) {
	if (this.tuix.items[this.currentTabId] && this.tuix.items[this.currentTabId].fields[fieldId]) {
		var field = this.tuix.items[this.currentTabId].fields[fieldId];
		var values = this.getOrderedFieldValues(this.currentTabId, fieldId, true);
		var html = false;
		if (field.type == 'select' || field.type == 'centralised_select') {
			html = this.microTemplate('zenario_organizer_admin_box_builder_select_values', values);
			$('#organizer_form_field_' + fieldId + ' select').html(html);
		} else if (field.type == 'radios' || field.type == 'centralised_radios') {
			html = this.microTemplate('zenario_organizer_admin_box_builder_radio_values_preview', values);
			$('#organizer_form_field_values_' + fieldId).html(html);
		} else if (field.type == 'checkboxes') {
			html = this.microTemplate('zenario_organizer_admin_box_builder_checkbox_values_preview', values);
			$('#organizer_form_field_values_' + fieldId).html(html);
		}
	}
};



methods.saveCurrentOpenDetails = function(hideErrors) {
	var errors = false, 
		item;
	if (this.selectedFieldId 
		&& this.tuix.items[this.currentTabId] 
		&& this.tuix.items[this.currentTabId].fields[this.selectedFieldId]
	) {
		item = this.tuix.items[this.currentTabId].fields[this.selectedFieldId];
		errors = this.saveItemTUIXTab('field_details', this.selectedDetailsTab, item);
		if (!hideErrors && !_.isEmpty(errors)) {
			this.loadFieldDetailsTab(this.selectedDetailsTab, this.selectedFieldId, errors);
		}
		
	} else if (this.selectedTabId && this.tuix.items[this.selectedTabId]) {
		item = this.tuix.items[this.selectedTabId];
		errors = this.saveItemTUIXTab('tab_details', this.selectedDetailsTab, item);
		if (!hideErrors && !_.isEmpty(errors)) {
			this.loadTabDetailsTab(this.selectedDetailsTab, this.selectedTabId, errors);
		}
	}
	return !_.isEmpty(errors);
};


methods.validateFieldDetails = function(fields, item, mode, tab, errors) {
	if (mode == 'field_details') {
		if (tab == 'details') {
			if (!item.is_system_field) {
				if (!item.db_column) {
					errors.db_column = 'Please enter a code name.';
				} else if (item.db_column.match(/[^a-z0-9_-]/)) {
					errors.db_column = 'Code name can only use characters a-z 0-9 _-.';
				} else {
					var isUnique = true;
					foreach (this.tuix.items as var tabId => var tab) {
						if (tab.fields) {
							foreach (tab.fields as var fieldId => var field) {
								if (fieldId != item.id && (field.db_column == item.db_column)) {
									isUnique = false;
									break;
								}
							}
						}
					}
					if (!isUnique) {
						errors.db_column = 'The code name "' + item.db_column + '" is already in use.';
					}
				}
			
				if (item.admin_box_visibility && item.admin_box_visibility == 'show_on_condition' && !item.parent_id) {
					errors.parent_id = 'Please select a conditional display field.';
				}
			
				if (item.type == 'file_picker' && !item.store_file) {
					errors.store_file = 'Please select a file storage method.';
				}
				
				if ((item.type == 'centralised_radios' || item.type == 'centralised_select') && !item.values_source) {
					errors.values_source = 'Please select a source for this list.';
				}
			}
		} else if (tab == 'validation') {
			if (!item.is_system_field) {
				if (item.required && !item.required_message) {
					errors.required_message = 'Please enter a message if not complete.';
				}
				if (item.validation && item.validation != 'none' && !item.validation_message) {
					errors.validation_message = 'Please enter a message if not valid.';
				}
			}
		}
	} /*else if (mode == 'tab_details') {
		//Tab TUIX validation goes here
	}*/
};

methods.loadTabDetailsPanel = function(tabId, stopEffect) {
	var that = this;
	var label = this.getTabLabel(this.tuix.items[tabId].tab_label);
	
	//Load HTML
	var mergeFields = {
		mode: 'tab_details',
		tab_label: label,
		is_system_field: this.tuix.items[tabId].is_system_field,
		hide_tab_bar: this.tuix.tab_details.hide_tab_bar
	};
	
	var html = this.microTemplate('zenario_organizer_admin_box_builder_left_panel', mergeFields);
	var $div = $('#organizer_admin_form_builder .form_fields_palette .form_fields_palette_outer').html(html);
	
	if (!stopEffect) {
		$div.hide().show('drop', {direction: 'left'}, 200);
	}
	
	//Load TUIX tabs HTML
	var tuix = this.tuix.tab_details.tabs;
	var item = this.tuix.items[tabId];
	var tabs = this.sortAndLoadTUIXTabs(tuix, item, this.selectedDetailsTab);
	var html = this.getTUIXTabsHTML(tabs);
	$('#organizer_tab_details_tabs').html(html);
	
	//Add tab details tab events
	$('#organizer_tab_details_tabs .tab').on('click', function() {
		var tab = $(this).data('id');
		if (tab && tab != that.selectedDetailsTab) {
			var errors = that.saveCurrentOpenDetails();
			if (!errors) {
				that.loadTabDetailsTab(tab, tabId);
			}
		}
	});
	
	//Load TUIX fields html
	this.loadTabDetailsTab(this.selectedDetailsTab, tabId);
	
	
	//Add panel events
	
	$('#organizer_tab_details_inner span.add_new_field').on('click', function() {
		var errors = that.saveCurrentOpenDetails();
		if (!errors) {
			that.loadNewFieldsPanel();
		}
	});
	
	$('#organizer_remove_form_tab').on('click', function(e) {
		var tab = that.tuix.items[that.selectedTabId];
		if (tab && !tab.is_system_field) {
			var firstProtectedField = false;
			var hasProtectedFieldsCount = 0;
			if (tab.fields) {
				foreach (tab.fields as var fieldId => var field) {
					if (field.is_protected) {
						if (!firstProtectedField) {
							firstProtectedField = field;
						}
						hasProtectedFieldsCount++;
					}
				}
			}
			
			var message;
			if (hasProtectedFieldsCount) {
				message = '<p>This tab has the protected field "' + firstProtectedField.field_label + '"';
				hasProtectedFieldsCount--;
				if (hasProtectedFieldsCount > 0) {
					var plural = hasProtectedFieldsCount == 1 ? '' : 's';
					message += ' and ' + hasProtectedFieldsCount + ' other protected field' + plural;
				}
				message += ' on it and cannot be deleted.</p>';
				zenarioA.floatingBox(message, true, 'warning', true);
			} else {
				message = '<p>Are you sure you want to delete this Tab?</p>';
				if (!_.isEmpty(tab.fields)) {
					message += '<p>All fields on this tab will also be deleted.</p>';
				}
				zenarioA.floatingBox(message, 'Delete', 'warning', true, false, function() {
					that.deleteTab(that.selectedTabId);
					that.changeMadeToPanel();
				});
			}
		}
	});
};

methods.loadTabDetailsTab = function(tab, tabId, errors) {
	this.selectedDetailsTab = tab;
	
	var path = 'tab_details/' + tab;
	var tuix = this.tuix.tab_details.tabs[tab].fields;
	var item = this.tuix.items[tabId];
	var fields = this.loadFields(path, tuix, item);
	formattedFields = this.formatFieldDetails(fields, item, 'tab', this.selectedDetailsTab);
	sortedFields = this.sortFields(formattedFields, item);
	var html = '';
	
	//Add any errors
	if (!_.isEmpty(errors)) {
		errors = this.sortErrors(errors);
		for (var i = 0; i < errors.length; i++) {
			html += '<p class="error">' + errors[i] + '</p>';
		}
	}
	
	//Load TUIX fields HTML
	html += this.getTUIXFieldsHTML(sortedFields);
	
	$('#organizer_tab_details_form').html(html)
	$('#organizer_tab_details_tabs .tab').removeClass('on');
	$('#field_tab__' + tab).addClass('on');
	
	if (!_.isEmpty(errors)) {
		$('#organizer_tab_details_form').effect({
			effect: 'bounce',
			duration: 125,
			direction: 'right',
			times: 2,
			distance: 5,
			mode: 'effect'
		});
	}
	
	var that = this;
	if (tab == 'details') {
		$('#field__tab_label').on('keyup', function() {
			var label = that.getTabLabel($(this).val());
			$('#zenario_tab_details_header_content .field_name h5, #organizer_form_tab_' + tabId + ' span').text(label);
		});
	}
};

//Custom formatting logic for TUIX fields
methods.formatFieldDetails = function(fields, item, mode, tab) {
	if (mode == 'field') {
		if (tab == 'details') {
			if (item.tuix_type) {
				var type = this.getFieldReadableType(this.currentTabId, item.id, true);
				fields.message.snippet.html = 'This is a field of type "' + type + '". You cannot edit this field\'s details.';
			}
			
			var values_source = fields.values_source._value;
			if (values_source && this.tuix.centralised_lists.values[values_source]) {
				var list = this.tuix.centralised_lists.values[values_source];
				if (!list.info.can_filter) {
					fields.values_source_filter._hidden = true;
				} else {
					fields.values_source_filter.label = list.info.filter_label;
				}
			}
		
			if (item.is_system_field) {
				fields.is_protected.note_below = 'System fields cannot be deleted';
				fields.is_protected._disabled = true;
				fields.is_protected._value = true;
			}
		
			if (['editor', 'textarea', 'file_picker'].indexOf(item.type) != -1) {
				fields.include_in_export.note_below = 'You cannot export this kind of field';
				fields.include_in_export._disabled = true;
				fields.include_in_export._value = false;
			}
		
			if (item.is_system_field && !item.allow_admin_to_change_visibility) {
				fields.admin_box_visibility.note_below = 'Only specific system fields marked with a special property can have their visibility changed.';
				fields.admin_box_visibility._disabled = true;
			}
	
		} else if (tab == 'organizer') {
			if (!item.allow_admin_to_change_visibility) {
				fields.hide_in_organizer.note_below = 'Only specific system fields marked with a special property can have their visibility changed.'
				fields.hide_in_organizer._disabled = true;
				fields.hide_in_organizer._value = false;
			}
			
			if (['checkbox', 'group', 'radios', 'select', 'dataset_select', 'dataset_picker', 'file_picker', 'centralised_radios', 'centralised_select'].indexOf(item.type) != -1) {
				fields.create_index._value = 'index';
				fields.create_index._disabled = true;
			}
			
		} else if (tab == 'values') {
			if (item.is_system_field) {
				fields.message.snippet.html = 'You cannot change the values of a system field.';
			} else if (item.type == 'centralised_radios' || item.type == 'centralised_select') {
				fields.message.snippet.html = 'You cannot change the values of a centralised list.';
			}
		}
	} /*else if (mode == 'tab') {
		//Tab TUIX formatting goes here
	}*/
	return fields;
};

methods.fieldDetailsChanged = function(path, field) {
	path = path.split('/');
	var mode = path[0],
		tab = path[1],
		tuixField, valuesSource, actionRequests, that, item;
	
	if (tab && mode && this.tuix[mode] && this.tuix[mode].tabs[tab] && this.tuix[mode].tabs[tab].fields[field]) {
		this.changeMadeToPanel();
		tuixField = this.tuix[mode].tabs[tab].fields[field];
		if (tuixField.format_onchange) {
			if (mode == 'field_details') {
				item = this.tuix.items[this.currentTabId].fields[this.selectedFieldId];
				this.saveItemTUIXTab(mode, this.selectedDetailsTab, item);
				this.loadFieldDetailsTab(this.selectedDetailsTab, this.selectedFieldId);
				
				if (tab == 'details' && field == 'values_source') {
					valuesSource = $('#field__values_source').val();
					if (valuesSource) {
						actionRequests = {
							mode: 'get_centralised_lov',
							method: valuesSource,
							filter: $('#field__values_source_filter').val()
						};
						that = this;
						this.sendAJAXRequest(actionRequests).after(function(lov) {
							lov = JSON.parse(lov);
							that.tuix.items[that.currentTabId].fields[that.selectedFieldId].lov = lov;
							that.loadFieldValuesListPreview(that.selectedFieldId);
						});
					} else {
						this.tuix.items[this.currentTabId].fields[this.selectedFieldId].lov = {};
						this.loadFieldValuesListPreview(this.selectedFieldId);
					}
				}
				
			} else if (mode == 'tab_details') {
				item = this.tuix.items[this.selectedTabId]
				this.saveItemTUIXTab(mode, this.selectedDetailsTab, item);
				this.loadTabDetailsTab(this.selectedDetailsTab, this.selectedTabId);
			}
		}
	}
};

//To do: Make this more like the form editor function...
methods.getFieldValuesList = function(field) {
	var sortedValues = [];
	var fieldName = field.id;
	var values = field.values;
	var value = field._value;
	var disabled = field._disabled;
	
	if (typeof(values) == 'object') {
		var i = 0;
		foreach (values as var id => var val) {
			var option = {
				ord: ++i,
				name: fieldName,
				label: val.label,
				value: id,
				path: field.path
			};
			if (id == value) {
				option.selected = true;
			}
			if (disabled) {
				option.disabled = true;
			}
			sortedValues.push(option);
		}
	} else if (values == 'boolean_fields') {
		foreach (this.tuix.items as var tabName => var tab) {
			if (tab.fields) {
				var options = [];
				foreach (tab.fields as var fieldId => var tabField) {
					if (tabField.type && (tabField.type == 'checkbox' || tabField.type == 'group') && tabField.id != this.selectedFieldId) {
						var option = {
							ord: tabField.ord,
							label: tabField.field_label,
							value: fieldId
						};
						if (fieldId == value) {
							option.selected = true;
						}
						options.push(option);
					}	
				}
				options.sort(this.sortByOrd);
				
				if (options.length) {
					sortedValues.push({
						ord: tab.ord,
						label: tab.tab_label,
						hasChildren: true,
						options: options
					});
				}
			}
		}
	} else if (values == 'centralised_lists') {
		var i = 0;
		foreach (this.tuix.centralised_lists.values as var func => var details) {
			var option = {
				ord: ++i,
				label: details.label,
				value: func
			};
			if (func == value) {
				option.selected = true;
			}
			sortedValues.push(option);
		}
	} else if (values == 'datasets') {
		var i = 0;
		foreach (this.tuix.datasets as var datasetId => var dataset) {
			var option = {
				ord: ++i,
				label: dataset.label,
				value: datasetId
			};
			if (datasetId == value) {
				option.selected = true;
			}
			sortedValues.push(option);
		}
	}
	
	sortedValues.sort(this.sortByOrd);
	return sortedValues;
};

methods.loadNewFieldsPanel = function(stopEffect) {
	this.selectedTabId = false;
	this.selectedFieldId = false;
	
	//Load HTML
	var mergeFields = {
		mode: 'new_fields',
		use_groups_field: this.tuix.use_groups_field
	};
	var html = this.microTemplate('zenario_organizer_admin_box_builder_left_panel', mergeFields);
	var $div = $('#organizer_admin_form_builder .form_fields_palette .form_fields_palette_outer').html(html);
	
	if (!stopEffect) {
		$div.hide().show('drop', {direction: 'right'}, 200);
	}
	
	//Add events
	
	//Allow fields to be dragged onto list
	$('#organizer_field_type_list_outer div.field_type_list div.field_type').draggable({
		connectToSortable: '#organizer_form_fields .form_section',
		appendTo: '#organizer_admin_form_builder',
		helper: 'clone'
	});
};

methods.loadTabsList = function(tabId) {
	foreach (this.tuix.items as var tabName => var tab) {
		if (tabName == tabId) {
			tab._selected = true;
		} else {
			delete(tab._selected);
		}
	}
	//Load HTML
	var mergeFields = {
		tabs: this.getOrderedTabs()
	};
	var html = this.microTemplate('zenario_organizer_admin_box_builder_tabs', mergeFields);
	$('#organizer_form_tabs').html(html);
	
	//Add events
	
	var that = this;
	//Click on a tab
	$('#organizer_form_tabs .tab').on('click', function() {
		var tabId = $(this).data('id');
		if (that.tuix.items[tabId]) {
			that.clickTab(tabId);
		}
	});
	//Add a new tab
	$('#organizer_add_new_tab').on('click', function() {
		var errors = that.saveCurrentOpenDetails();
		if (!errors) {
			that.addNewTab();
			that.changeMadeToPanel();
		}
	});
	//Tab sorting
	$('#organizer_form_tabs').sortable({
		axis: 'x',
		containment: 'parent',
		tolerance: 'pointer',
		items: 'div.sort',
		start: function(event, ui) {
			that.startIndex = ui.item.index();
		},
		stop: function(event, ui) {
			if (that.startIndex != ui.item.index()) {
				//Update ordinals
				$('#organizer_form_tabs .tab.sort').each(function(i) {
					var tabId = $(this).data('id');
					that.tuix.items[tabId].ord = (i + 1);
				});
				that.changeMadeToPanel();
			}
		}
	});
	
	//Moving fields between tabs
	$('#organizer_form_tabs .tab').droppable({
		accept: 'div.is_sortable:not(.system_field)',
		greedy: true,
		hoverClass: 'ui-state-hover',
		tolerance: 'pointer',
		drop: function(event, ui) {
			var tab = $(this).data('id');
			var fieldId = $(ui.draggable).data('id');
			if ((tab != that.currentTabId) && !that.tuix.items[that.currentTabId].fields[fieldId].is_system_field) {
				var ord = 1;
				var orderedFields = that.getOrderedFields(tab);
				if (orderedFields.length) {
					ord = orderedFields[orderedFields.length - 1].ord + 1;
				}
				
				that.tuix.items[tab].fields[fieldId] = that.tuix.items[that.currentTabId].fields[fieldId];
				that.tuix.items[tab].fields[fieldId].ord = ord;
				delete(that.tuix.items[that.currentTabId].fields[fieldId]);
				
				if (fieldId == that.selectedFieldId) {
					that.loadNewFieldsPanel();
				}
				that.loadFieldsList(that.currentTabId);
				that.changeMadeToPanel();
			}
		}
	});
};

methods.addNewTab = function() {
	var tabId = '__custom_tab_t' + (this.maxNewCustomTab++);
	var nextTabOrd = 1;
	foreach (this.tuix.items as name) {
		++nextTabOrd;
	}
	
	this.tuix.items[tabId] = {
		ord: nextTabOrd,
		name: tabId,
		tab_label: 'Untitled tab',
		fields: {},
		is_new_tab: true
	};
	
	this.clickTab(tabId, true);
};

methods.clickTab = function(tabId, isNewTab) {
	if (this.selectedTabId != tabId) {
		var errors = this.saveCurrentOpenDetails();
		if (!errors) {
			//By clicking on the current tab you can access it's details
			if (this.currentTabId == tabId) {
				this.selectedFieldId = false;
				this.selectedTabId = tabId;
				this.selectedDetailsTab = false;
				this.loadTabDetailsPanel(tabId);
				return;
			}

			var tab = this.tuix.items[tabId];
			if (tab.record_counts_fetched || tab.is_new_tab) {
				this.clickTab2(tabId, isNewTab);
			} else {
				var that = this;
				var actionRequests = {
					mode: 'get_tab_field_record_counts',
					tabId: tabId
				};
				this.sendAJAXRequest(actionRequests).after(function(recordCounts) {
					tab.record_counts_fetched = true;
					var recordCounts = JSON.parse(recordCounts);
					if (recordCounts) {
						foreach (recordCounts as fieldId => recordCount) {
							if (tab.fields && tab.fields[fieldId]) {
								tab.fields[fieldId].record_count = recordCount;
							}
						}
					}
					that.clickTab2(tabId, isNewTab);
				});
			}
		}
	}
};

methods.clickTab2 = function(tabId, isNewTab) {	
	this.currentTabId = tabId;
	if (isNewTab) {
		this.selectedTabId = tabId;
		this.loadTabDetailsPanel(tabId);
	} else {
		var stopEffect = false;
		if (!this.selectedFieldId && !this.selectedTabId) {
			stopEffect = true;
		}
		this.loadNewFieldsPanel(stopEffect);
	}
	this.selectedFieldId = false;
	this.loadTabsList(tabId);
	this.loadFieldsList(tabId);
};

methods.clickField = function(fieldId) {
	if (this.selectedFieldId != fieldId) {
		var errors = this.saveCurrentOpenDetails();
		if (!errors) {
			this.highlightField(fieldId);
			this.selectedFieldId = fieldId;
			this.selectedTabId = false;
			this.selectedDetailsTab = false;
			this.loadFieldDetailsPanel(fieldId);
		}
	}
};

methods.loadFieldsList = function(tabId) {
	var orderedFields = this.getOrderedFields(tabId, true, true);
	
	//Load HTML
	var mergeFields = {
		fields: orderedFields
	};
	var html = this.microTemplate('zenario_organizer_admin_box_builder_section', mergeFields);
	$('#organizer_form_fields').html(html);
	
	//Add events
	
	var that = this;
	//Select a field
	$('#organizer_form_fields div.form_field').on('click', function() {
		var fieldId = $(this).data('id');
		if (that.tuix.items[that.currentTabId].fields[fieldId]) {
			that.clickField(fieldId);
		}
	});
	//Make fields sortable
	$('#organizer_form_fields .form_section').sortable({
		items: 'div.is_sortable',
		tolerance: 'pointer',
		placeholder: 'preview',
		//Add new field to the form
		receive: function(event, ui) {
			$(this).find('div.field_type').each(function() {
				var fieldType = $(this).data('type');
				var ord = 1;
				var previousFieldId = $(this).prev().data('id');
				if (previousFieldId && that.tuix.items[that.currentTabId].fields[previousFieldId]) {
					ord = that.tuix.items[that.currentTabId].fields[previousFieldId].ord + 1;
				}
				that.addNewField(fieldType, ord);
				that.changeMadeToPanel();
			});
		},
		start: function(event, ui) {
			that.startIndex = ui.item.index();
		},
		//Detect reorder/new/deleted fields
		stop: function(event, ui) {
			if (that.startIndex != ui.item.index()) {
				//Update ordinals
				$('#organizer_form_fields div.is_sortable').each(function(i) {
					var fieldId = $(this).data('id');
					that.tuix.items[that.currentTabId].fields[fieldId].ord = (i + 1);
				});
				that.changeMadeToPanel();
			}
		}
	});
	//Delete a field
	$('#organizer_form_fields .delete_icon').on('click', function(e) {
		e.stopPropagation();
		var fieldId = $(this).data('id');
		if (that.tuix.items[that.currentTabId].fields[fieldId]) {
			that.saveCurrentOpenDetails(true);
			
			var field = that.tuix.items[that.currentTabId].fields[fieldId];
			if (field.is_protected) {
				message = "<p>This field is protected, and might be important to your site!</p>";
				message += "<p>If you're sure you want to delete this field then first unprotect it.</p>";
				zenarioA.floatingBox(message, true, 'warning', true);
			} else {
				if (field.record_count && field.record_count >= 1) {
					var plural = field.record_count == 1 ? '' : 's';
					message = "<p>This field contains data on " + field.record_count + " record" + plural + ".</p>";
					message += "<p>When you save changes to this dataset, that data will be deleted.</p>";
				} else {
					message = "<p>This field doesn't contain any data for any user/contact records.</p>";
				}
				message += "<p>Delete this dataset field?</p>";
				
				zenarioA.floatingBox(message, 'Delete', 'warning', true, false, function() {
					that.deleteField(fieldId);
				});
			}
		}
	});
};

methods.deleteTab = function(tabId) {
	//To do: Handle case where there are NO system tabs so no tabs could be shown.
	//Select next tab
	var tabs = this.getOrderedTabs();
	var j = 0;
	for (var i = 0; i < tabs.length; i++) {
		if (tabs[i].name == tabId) {
			if (i > 0) {
				j = i - 1;
			} else {
				j = 1;
			}
			this.deletedTabs.push(tabId);
			
			if (this.tuix.items[tabId].fields) {
				foreach (this.tuix.items[tabId].fields as var fieldId => field) {
					this.deletedFields.push(fieldId);
				}
			}
			delete(this.tuix.items[tabId]);
			this.clickTab(tabs[j].name);
		}
	}
};

methods.deleteField = function(fieldId) {
	tabId = this.currentTabId;
	if (this.tuix.items[tabId].fields[fieldId]) {
		this.changeMadeToPanel();
		this.deletedFields.push(fieldId);
		
		//Select next field if one exists
		var field = this.tuix.items[tabId].fields[fieldId];
		var fields = this.getOrderedFields(tabId, false, true);
		var deletedFieldIndex = false;
		var nextFieldId = false;
		var nextField = false;
		var nextFieldIndex = false;
		
		for (var i = 0; i < fields.length; i++) {
			if (fields[i].id == field.id) {
				deletedFieldIndex = i;
			}
			if (deletedFieldIndex !== false) {
				if (deletedFieldIndex > 0) {
					nextFieldIndex = deletedFieldIndex - 1;
					nextField = fields[deletedFieldIndex - 1];
					break;
				} else if (fieldId != fields[i].id) {
					nextFieldIndex = i;
					nextField = fields[i];
					break;
				}
			}
		}
		delete(this.tuix.items[tabId].fields[fieldId]);
		
		//If the next field is a grouping, select one of the fields inside if one exists
		if (nextField._fields && nextField._fields.length) {
			if (nextFieldIndex < deletedFieldIndex) {
				nextFieldId = nextField._fields[nextField._fields.length - 1].id;
			} else {
				nextFieldId = nextField._fields[0].id;
			}
		} else {
			nextFieldId = nextField.id;
		}
		
		this.loadFieldsList(tabId);
		if (nextFieldId) {
			this.clickField(nextFieldId);
		} else {
			this.loadNewFieldsPanel();
		}
	}
};

methods.addNewField = function(type, ord) {
	var newFieldId = 't' + this.maxNewCustomField++;
	var newField = {
		id: newFieldId,
		type: type,
		ord: ord,
		field_label: 'Untitled',
		is_new_field: true
	};
	
	if (type == 'checkboxes' || type == 'radios' || type == 'select') {
		newField.lov = {};
		for (var i = 1; i <= 3; i++) {
			this.addFieldValue(newField, 'Option ' + i);
		}
	}
	
	foreach (this.tuix.items[this.currentTabId].fields as var fieldId => var field) {
		if (field.ord >= ord) {
			field.ord++;
		}
	}
	
	this.tuix.items[this.currentTabId].fields[newFieldId] = newField;
	this.loadFieldsList(this.currentTabId);
	this.clickField(newFieldId);
};


methods.getOrderedTabs = function() {
	var sortedTabs = [];
	foreach (this.tuix.items as var tabName => var tab) {
		sortedTabs.push(tab);
	}
	sortedTabs.sort(this.sortByOrd);
	return sortedTabs;
};

methods.getOrderedFields = function(tabId, orderValues, groupGroupedFields) {
	var sortedFields = [],
	    groupedFields = [],
	    groups = {},
	    i, fieldId, field, fieldClone, length, index, groupName, group;
	    
	if (this.tuix.items[tabId] && this.tuix.items[tabId].fields) {
		foreach (this.tuix.items[tabId].fields as fieldId => field) {
			fieldClone = _.clone(field);
			if (orderValues) {
				fieldClone.lov = this.getOrderedFieldValues(tabId, fieldId, true);
			}
			
			if (groupGroupedFields && field.grouping_name) {
				fieldClone._fields = [];
				groups[field.grouping_name] = fieldClone;
			} else if (groupGroupedFields && field.grouping) {
				groupedFields.push(fieldClone);
			} else {
				fieldClone._isSortable = true;
				sortedFields.push(fieldClone);
			}
		}
	}
	
	if (groupedFields.length > 0) {
		for (i = 0; i < groupedFields.length; i++) {
			if (groups[groupedFields[i].grouping]) {
				groups[groupedFields[i].grouping]._fields.push(groupedFields[i]);
			}
		}
	}
	
	foreach (groups as groupName => group) {
		group._fields.sort(this.sortByOrd);
		if (group._fields.length) {
			group._isSortable = true;
			sortedFields.push(group);
		}
	}
	
	sortedFields.sort(this.sortByOrd);
	return sortedFields;
};

methods.getOrderedFieldValues = function(tabId, fieldId, includeEmptyValue) {
	var sortedValues = [],
		valueId, value;
	if (this.tuix.items[tabId]
		&& this.tuix.items[tabId].fields
		&& this.tuix.items[tabId].fields[fieldId]
		&& this.tuix.items[tabId].fields[fieldId].lov
	) {
		foreach (this.tuix.items[tabId].fields[fieldId].lov as valueId => value) {
			sortedValues.push(value);
		}
		var type = this.tuix.items[tabId].fields[fieldId].type;
		if (includeEmptyValue && (type == 'select' || type == 'centralised_select')) {
			sortedValues.push({
				id: 'empty_value',
				label: '-- Select --',
				ord: -1
			});
		}
	}
	sortedValues.sort(this.sortByOrd);
	return sortedValues;
};

methods.changeMadeToPanel = function() {
	var that = this;
	if (!this.changeDetected) {
		this.changeDetected = true;
		window.onbeforeunload = function() {
			return 'You are currently editing this dataset. If you leave now you will lose any unsaved changes.';
		}
		var warningMessage = 'Please either save your changes, or click Reset to discard them, before exiting the form editor.';
		zenarioO.disableInteraction(warningMessage);
		zenarioO.setButtons();
	}
};

methods.saveChanges = function() {
	var that = this;
	var actionRequests = {
		mode: 'save',
		data: JSON.stringify(this.tuix.items),
		deletedTabs: JSON.stringify(this.deletedTabs),
		deletedFields: JSON.stringify(this.deletedFields),
		currentTabId: this.currentTabId
	};
	if (this.selectedTabId) {
		actionRequests.selectedTabId = this.selectedTabId;
	}
	if (this.selectedFieldId) {
		actionRequests.selectedFieldId = this.selectedFieldId;
	}
	
	this.sendAJAXRequest(actionRequests).after(function(info) {
		if (info) {
			info = JSON.parse(info);
			if (info.errors) {
				var message = '';
				for (var i = 0; i < info.errors.length; i++) {
					message += info.errors[i] + '<br>';
				}
				if (message) {
					zenarioA.floatingBox(message);
				}
			}
			that.currentTabId = info.currentTabId;
			that.selectedTabId = info.selectedTabId;
			that.selectedFieldId = info.selectedFieldId;
		}
		
		zenarioA.nowDoingSomething();
		window.onbeforeunload = false;
		zenarioO.enableInteraction();
		
		that.changeDetected = false;
		that.changesSaved = true;
		
		zenarioO.reload();
	});
};



}, zenarioO.panelTypes);
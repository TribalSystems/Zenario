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
	encodeURIComponent, defined, engToBoolean, get, htmlspecialchars, jsEscape, phrase,
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

//Draw the panel, as well as the header at the top and the footer at the bottom
//This is called every time the panel is loaded, refreshed or when something in the header toolbar is changed.
methods.showPanel = function($header, $panel, $footer) {
	$header.html('').hide();
	$footer.html('').show();
	
	//Load main panel
	var html = thus.getMainPanelHTML();
	$panel.html(html).show();
	
	//Page currently on
	thus.currentPageId = thus.currentPageId ? thus.currentPageId : false;
	//Thing currently being edited (page/field)
	thus.editingThing = thus.editingThing ? thus.editingThing : false;
	thus.editingThingId = thus.editingThingId ? thus.editingThingId : false;
	thus.editingThingTUIXTabId = thus.editingThingTUIXTabId ? thus.editingThingTUIXTabId : false;
	thus.deletedPages = [];
	thus.deletedFields = [];
	thus.deletedValues = [];
	thus.changeMadeOnPanel = false;
	thus.pagesReordered = false;
	thus.newItemCount = 0;
	
	thus.fixTUIXData()
	
	//Load right panel
	thus.loadPagesList();
	thus.loadFieldsList(thus.currentPageId);
	
	//Load left panel
	var stopAnimation = true;
	if (thus.editingThing == 'field') {
		thus.openFieldEdit(thus.editingThingId, thus.editingThingTUIXTabId, stopAnimation);
	} else if (thus.editingThing == 'page') {
		thus.openPageEdit(thus.editingThingId, thus.editingThingTUIXTabId, stopAnimation);
	} else {
		thus.loadNewFieldsPanel(true);
	}
	
	//Show Growl message if saved changes
	if (thus.changesSaved) {
		thus.changesSaved = false;
		var toast = {
			message_type: 'success',
			message: 'Your changes have been saved!'
		};
		zenarioA.toast(toast);
	}
};

methods.fixTUIXData = function() {
	//Arrays are turned into objects, fix these here.
	for (var fieldId in thus.tuix.dataset_fields_in_forms) {
		thus.tuix.dataset_fields_in_forms[fieldId] = zenarioT.tuixToArray(thus.tuix.dataset_fields_in_forms[fieldId]);
	}
};

methods.getMainPanelHTML = function() {
	var mergeFields = {
		dataset_label: thus.tuix.dataset.label
	};
	var html = thus.microTemplate('zenario_organizer_admin_box_builder', mergeFields);
	return html;
};

methods.openEdit = function(itemType, itemId, tuixTabId, stopAnimation) {
	//Set left panel HTML
	var item = thus.getItem(itemType, itemId);
	var tuixMode = thus.getTUIXModeForItemType(itemType);
	var tuix = thus.tuix[tuixMode];
	var mergeFields = {
		hide_tab_bar: tuix.hide_tab_bar,
		is_system_field: item.is_system_field
	};
	
	if (itemType == 'page') {
		mergeFields.mode = 'edit_page';
		mergeFields.label = thus.formatPageLabel(item.label);
		
	} else if (itemType == 'field') {
		mergeFields.mode = 'edit_field';
		mergeFields.label = item.label;
		
		var plural = item.record_count == 1 ? '' : 's';
		var record_count = item.record_count ? item.record_count : 0;
		mergeFields.record_count = '(' + record_count + ' record' + plural + ')';
		
		mergeFields.type = thus.getFieldReadableType(item.type);
		if (item.is_system_field) {
			mergeFields.type += ', system field';
		}
	}
	
	var html = thus.microTemplate('zenario_organizer_admin_box_builder_left_panel', mergeFields);
	var $div = $('#organizer_admin_form_builder .form_fields_palette .form_fields_palette_outer').html(html);
	
	//Set TUIX tabs HTML
	var tabs = thus.getTUIXTabs(tuix, tuixTabId, item);
	tuixTabId = thus.editingThingTUIXTabId; //Update current tab
	var html = thus.getTUIXTabsHTML(tabs);	
	$('#organizer_field_details_tabs').html(html);
	
	thus.openItemTUIXTab(itemType, itemId, tuixTabId);
	
	//Animation
	if (!stopAnimation) {
		$div.hide().show('drop', {direction: 'left'}, 200);
	}
	
	//Events
	//Click a TUIX tab
	$('#organizer_field_details_tabs .tab').on('click', function() {
		var newTUIXTabId = $(this).data('id');
		if (newTUIXTabId && newTUIXTabId != tuixTabId) {
			if (thus.saveCurrentOpenDetails()) {
				thus.openEdit(itemType, itemId, newTUIXTabId, true);
			}
		}
	});
	
	//Click "Done" button
	$('#organizer_field_details_inner span.add_new_field').on('click', function() {
		if (thus.saveCurrentOpenDetails()) {
			thus.loadNewFieldsPanel();
			thus.loadFieldsList(thus.currentPageId);
		}
	});
};

methods.addTUIXTabEvents = function(itemType, itemId, tuixTabId) {
	var item = thus.getItem(itemType, itemId);
	if (itemType == 'page') {
		$('#organizer_remove_form_tab').on('click', function(e) {
			if (item.is_system_field) {
				return;
			}
			
			//Get protected fields on this page
			var firstProtectedField = false;
			var protectedFieldsCount = 0;
			var fields = thus.getOrderedFields(itemId);
			for (var i = 0; i < fields.length; i++) {
				if (fields[i].is_protected) {
					if (!firstProtectedField) {
						firstProtectedField = fields[i];
					}
					protectedFieldsCount++;
				}
			}
			
			//Show a warning if there are any protected fields on the page
			var message;
			if (protectedFieldsCount) {
				message = '<p>This tab has the protected field "' + firstProtectedField.label + '"';
				protectedFieldsCount--;
				if (protectedFieldsCount > 0) {
					var plural = protectedFieldsCount == 1 ? '' : 's';
					message += ' and ' + protectedFieldsCount + ' other protected field' + plural;
				}
				message += ' on it and cannot be deleted.</p>';
				zenarioA.floatingBox(message, true, 'warning', true);
			
			//...Otherwise delete the page
			} else {
				message = '<p>Are you sure you want to delete this tab?</p>';
				if (fields.length > 0) {
					message += '<p>All fields on this tab will also be deleted.</p>';
				}
				zenarioA.floatingBox(message, 'Delete tab', 'warning', true, false, undefined, undefined, function() {
					thus.deletePage(itemId);
					thus.changeMadeToPanel();
				});
			}
		});
		
		$('#field__label').on('keyup', function() {
			var label = thus.formatPageLabel($(this).val());
			$('#zenario_field_details_header_content .field_name h5, #organizer_form_tab_' + itemId + ' span').text(label);
		});
	} else if (itemType == 'field') {
		if (tuixTabId == 'details') {
			$('#field__label').on('keyup', function() {
				$('#organizer_form_field_' + itemId + ' .label, #zenario_field_details_header_content .field_name h5').text($(this).val());
				if (item._just_added) {
					var dbColumn = $(this).val().toLowerCase().replace(/[\s-]+/g, '_').replace(/[^a-z_0-9]/g, '');
					$('#field__db_column').val(dbColumn);
				}
			});
		
			$('#field__note_below').on('keyup', function() {
				var value = $(this).val().trim();
				$('#organizer_form_field_note_below_' + itemId + ' div.zenario_note_content').text(value);
				$('#organizer_form_field_note_below_' + itemId).toggle(value !== '');
			});
		
			$('#field__side_note').on('keyup', function() {
				var value = $(this).val().trim();
				$('#organizer_form_field_side_note_' + itemId + ' div.zenario_note_content').text(value);
				$('#organizer_form_field_side_note_' + itemId).toggle(value !== '');
			});
		
		} else if (tuixTabId == 'values') {
			var tuix = thus.tuix[thus.getTUIXModeForItemType(itemType)];
			
			//Sorting
			$('#field_values_list').sortable({
				containment: 'parent',
				tolerance: 'pointer',
				axis: 'y',
				start: function(event, ui) {
					thus.startIndex = ui.item.index();
				},
				stop: function(event, ui) {
					if (thus.startIndex != ui.item.index()) {
						thus.saveFieldListOfValues(itemId);
						thus.loadFieldValuesListPreview(itemId);
						thus.changeMadeToPanel();
					}
				}
			});
		
			//Update labels on preview
			$('#field_values_list input').on('keyup', function() {
				var valueId = $(this).data('id');
				$('#organizer_field_value_' + valueId + ' label').text($(this).val());
				thus.changeMadeToPanel();
			});
	
			//Add new field value
			$('#organizer_add_a_field_value').on('click', function() {
				thus.addFieldValue(item);
				thus.saveTUIXTab(tuix, tuixTabId, item);
				thus.openItemTUIXTab(itemType, itemId, tuixTabId);
				thus.loadFieldValuesListPreview(itemId);
				thus.changeMadeToPanel();
			});
	
			//Delete field value
			$('#field_values_list .delete_icon').on('click', function() {
				var valueId = $(this).data('id');
				if (item.lov[valueId]) {
					thus.deletedValues.push(valueId);
					delete(item.lov[valueId]);
				}
				thus.saveTUIXTab(tuix, tuixTabId, item);
				thus.openItemTUIXTab(itemType, itemId, tuixTabId);
				thus.loadFieldValuesListPreview(itemId);
				thus.changeMadeToPanel();
			});
		}
	}
};

methods.loadNewFieldsPanel = function(stopAnimation) {
	thus.editingThing = false;
	
	//Load HTML
	var mergeFields = {
		mode: 'new_fields',
		use_groups_field: thus.tuix.use_groups_field
	};
	
	var html = thus.microTemplate('zenario_organizer_admin_box_builder_left_panel', mergeFields);
	var $div = $('#organizer_admin_form_builder .form_fields_palette .form_fields_palette_outer').html(html);
	
	if (!stopAnimation) {
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





methods.getTUIXModeForItemType = function(itemType) {
	if (itemType == 'page') {
		return 'dataset_page_details';
	} else if (itemType == 'field') {
		return 'dataset_field_details';
	} else {
		return false;
	}
};

methods.getFieldReadableType = function(type, tuixType, getOtherSystemFieldTUIXType) {
	switch (type) {
		case 'group':
			return 'Group';
		case 'checkbox':
			return 'Checkbox';
		case 'consent':
			return 'Consent';
		case 'checkboxes':
			return 'Checkbox group';
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
				switch (tuixType) {
					case 'html_snippet':
						return 'HTML snippet';
					case 'pick_items':
						return 'Item picker';
					case 'upload':
						return 'Upload';
					case 'toggle':
						return 'Toggle';
					case 'grouping':
						return 'Grouping';
					case 'submit':
						return 'Submit';
					case 'hidden':
						return 'Hidden';
					default:
						return this.getFieldReadableType(tuixType);
				}
			}
		case 'dataset_select':
			return 'Dataset select';
		case 'dataset_picker':
			return 'Dataset picker';
		case 'file_picker':
			return 'File picker';
		case 'repeat_start':
			return 'Start of repeating section';
		case 'repeat_end':
			return 'End of repeating section';
		default:
			return 'Unknown';
	}
};


methods.saveCurrentOpenDetails = function(deleted) {
	var errors = [];
	var itemType = thus.editingThing;
	var itemId = thus.editingThingId;
	if (itemType && itemId && thus.editingThingTUIXTabId) {
		var tuixMode = thus.getTUIXModeForItemType(itemType);
		var tuix = thus.tuix[tuixMode];
		var item = thus.getItem(itemType, itemId);
		
		if (item) {
			thus.saveTUIXTab(tuix, thus.editingThingTUIXTabId, item);
			
			//Get errors if trying to delete
			if (deleted) {
				errors = thus.getDeleteErrors(itemType, itemId);
				
			//Get regular errors
			} else {
				//Validate tuix fields
				var tags = thus.getTUIXTags(tuixMode, item, itemType);
				thus.validateTUIX(itemType, item, thus.editingThingTUIXTabId, tags);
				
				foreach (tags.tabs as var tuixTabId => var tuixTab) {
					foreach (tuixTab.fields as var tuixFieldId => tuixField) {
						if (tuixField.error) {
							errors.push(tuixField.error);
						}
					}
				}
			}
			
			//Reload tab to show errors
			if (errors.length) {
				thus.openItemTUIXTab(itemType, itemId, thus.editingThingTUIXTabId, errors);
			}
		}
	}
	return errors.length == 0;
};

methods.displayPageFieldOrderErrors = function() {
	if (!thus.getItem('page', thus.currentPageId)) {
		return true;
	}
	
	var errors = [];
	var fields = thus.getOrderedFields(thus.currentPageId);
	var fieldsAllowedInRepeatBlock = [
		'checkbox', 
		'group',
		'date', 
		'radios', 
		'select', 
		'text', 
		'textarea', 
		'url'
	];
	
	var inRepeatBlock = false;
	var repeatStartFieldId = false;
	for (var i = 0; i < fields.length; i++) {
		field = fields[i];
		if (field.type == 'repeat_start' && !inRepeatBlock) {
			repeatStartFieldId = field.id;
			inRepeatBlock = true;
		} else if (field.type == 'repeat_end') {
			//Validate repeat end positioning
			if (inRepeatBlock) {
				repeatStartFieldId = false;
				inRepeatBlock = false;
			} else {
				errors.push('Repeat ends must be placed after repeat starts.');
			}
		} else {
			//Validate field types in repeat block
			if (inRepeatBlock && fieldsAllowedInRepeatBlock.indexOf(field.type) == -1) {
				errors.push('Field type "' + thus.getFieldReadableType(field.type).toLowerCase() + '" is not allowed in a repeat block.');
			}
			
			//Validate repeat fields used on forms, they cannot be removed without first removing from the form
			if (thus.tuix.dataset_fields_in_forms[field.id] && !inRepeatBlock && field.repeat_start_id && field.repeat_start_id != repeatStartFieldId) {
				var formIds = thus.tuix.dataset_fields_in_forms[field.id];
				var formNames = [];
				for (var j = 0; j < formIds.length; j++) {
					if (thus.tuix.forms_with_dataset_fields[formIds[j]]) {
						formNames.push(thus.tuix.forms_with_dataset_fields[formIds[j]]);
					}
				}
				errors.push('The field "' + field.label + '" is used on ' + formIds.length + ' form' + (formIds.length == 1 ? '' : 's') + ' (' + formNames.join(', ') + ') in a dataset repeat block. You must remove this repeat block from forms before this field can be moved out.');
			}
		}
	}
	
	//Display errors
	var foundErrors = errors.length > 0;
	var $errorDiv = $('#organizer_fields_error');
	$errorDiv.html('');
	for (i = 0; i < errors.length; i++) {
		$errorDiv.append('<p class="error">' + errors[i] + '</p>');
	}
	$errorDiv.toggle(foundErrors);
	
	return !foundErrors;
};

methods.getDeleteErrors = function(itemType, itemId) {
	var errors = [];
	if (itemType == 'field') {
		var fieldToDelete = thus.getItem(itemType, itemId);
		var pages = thus.getOrderedPages();
		
		//Check if field is referenced by pages
		for (var i = 0; i < pages.length; i++) {
			if (pages[i].parent_field_id == itemId) {
				errors.push('Unable to delete the field because the tab "' + pages[i].label + '" depends on it.');
			}
		}
		
		//Check if field is referenced by fields
		var fields = thus.getOrderedFields();
		for (var i = 0; i < fields.length; i++) {
			if (itemId != fields[i].id) {
				if (itemId == fields[i].parent_id) {
					errors.push('Unable to delete the field because the field "' + fields[i].label + '" depends on it.');
				}
			}
		}
		
		//Check if field is used on user forms
		if (thus.tuix.dataset_fields_in_forms[itemId]) {
			var formIds = thus.tuix.dataset_fields_in_forms[itemId];
			var formNames = [];
			for (var i = 0; i < formIds.length; i++) {
				if (thus.tuix.forms_with_dataset_fields[formIds[i]]) {
					formNames.push(thus.tuix.forms_with_dataset_fields[formIds[i]]);
				}
			}
			errors.push('This field is used on ' + formIds.length + ' form' + (formIds.length == 1 ? '' : 's') + ' (' + formNames.join(', ') + '). You must remove this field from all forms before you can delete it.');
		}
		
	}
	return errors;
};

//Similar to PHP method formatAdminBox
methods.formatTUIX = function(itemType, item, tab, tags, changedFieldId) {
	if (itemType == 'field') {
		if (tab == 'details') {
			if (item.tuix_type) {
				var type = thus.getFieldReadableType(item.type, item.tuix_type, true);
				tags.tabs[tab].fields.message.snippet.html = 'This is a field of type "' + type + '". You cannot edit this field\'s details.';
			}
			
			if (item.is_protected || item.is_system_field) {
				tags.tabs[tab].fields.db_column.readonly = true;
			}
			
			if (item.values_source && thus.tuix.centralised_lists.values[item.values_source]) {
				var list = thus.tuix.centralised_lists.values[item.values_source];
				if (!list.info.can_filter) {
					tags.tabs[tab].fields.values_source_filter.hidden = true;
				} else {
					tags.tabs[tab].fields.values_source_filter.label = list.info.filter_label;
				}
			}
			
			if (changedFieldId == 'values_source') {
				if (item.values_source) {
					actionRequests = {
						mode: 'get_centralised_lov',
						method: item.values_source
					};
					if (thus.tuix.centralised_lists.values[item.values_source].info.can_filter) {
						actionRequests.filter = item.values_source_filter;
					}
					thus.sendAJAXRequest(actionRequests, function(lov) {
						item.lov = lov;
						thus.loadFieldValuesListPreview(item.id);
					});
				} else {
					item.lov = {};
					thus.loadFieldValuesListPreview(item.id);
				}
			}
		
			if (item.is_system_field) {
				tags.tabs[tab].fields.is_protected.note_below = 'System fields cannot be deleted';
				tags.tabs[tab].fields.is_protected.readonly = true;
				tags.tabs[tab].fields.is_protected.value = true;
			}
		
			if (['editor', 'textarea', 'file_picker'].indexOf(item.type) != -1) {
				tags.tabs[tab].fields.include_in_export.note_below = 'You cannot export this kind of field';
				tags.tabs[tab].fields.include_in_export.readonly = true;
				tags.tabs[tab].fields.include_in_export.value = false;
			}
		
			if (item.is_system_field && !item.allow_admin_to_change_visibility) {
				tags.tabs[tab].fields.admin_box_visibility.note_below = 'Only specific system fields marked with a special property can have their visibility changed.';
				tags.tabs[tab].fields.admin_box_visibility.hidden = true;
			}
			
			if (item.type == 'repeat_start') {
				tags.tabs[tab].fields.label.label = 'Section heading:';
				tags.tabs[tab].fields.label.note_below = 'This will be the heading at the start of the repeating section.';
			}
			
		    thus.loadFieldsList(thus.currentPageId);
	
		} else if (tab == 'organizer') {
			if (!item.allow_admin_to_change_visibility) {
				tags.tabs[tab].fields.hide_in_organizer.note_below = 'Only specific system fields marked with a special property can have their visibility changed.'
				tags.tabs[tab].fields.hide_in_organizer.readonly = true;
				tags.tabs[tab].fields.hide_in_organizer.value = false;
			}
			
			if (['checkbox', 'group', 'radios', 'select', 'dataset_select', 'dataset_picker', 'file_picker', 'centralised_radios', 'centralised_select'].indexOf(item.type) != -1) {
				tags.tabs[tab].fields.create_index.value = 'index';
				tags.tabs[tab].fields.create_index.readonly = true;
			}
			
		} else if (tab == 'values') {
			if (item.is_system_field) {
				tags.tabs[tab].fields.message.snippet.html = 'You cannot change the values of a system field.';
			} else if (item.type == 'centralised_radios' || item.type == 'centralised_select') {
				tags.tabs[tab].fields.message.snippet.html = 'You cannot change the values of a centralised list.';
			}
		}
	}
};

//Similar to PHP method validateAdminBox
methods.validateTUIX = function(itemType, item, tab, tags) {
	if (itemType == 'page') {
		if (tab == 'details') {
			if (!item.label.trim()) {
				tags.tabs[tab].fields.label.error = 'Please enter a label';
			}
		}
	} else if (itemType == 'field') {
		if (tab == 'details') {
			if (!item.is_system_field) {
				if (item.type != 'repeat_start') {
					if (!item.db_column) {
						tags.tabs[tab].fields.db_column.error = 'Please enter a code name.';
					} else if (item.db_column.match(/[^a-z0-9_-]/)) {
						tags.tabs[tab].fields.db_column.error = 'Code name can only use characters a-z 0-9 _-.';
					} else {
						var isUnique = true;
						var fields = thus.getOrderedFields();
						for (var i = 0; i < fields.length; i++) {
							if (fields[i].id != item.id && fields[i].db_column == item.db_column) {
								isUnique = false;
								break;
							}
						}
						if (!isUnique) {
							tags.tabs[tab].fields.db_column.error = 'The code name "' + item.db_column + '" is already in use.';
						} else if (item.db_column.match(/\_\_(\d+|rows)$/)) {
							tags.tabs[tab].fields.db_column.error = 'That code name is invalid.';
						}
					}
				}
			
				if (item.admin_box_visibility && item.admin_box_visibility == 'show_on_condition' && !item.parent_id) {
					tags.tabs[tab].fields.parent_id.error = 'Please select a conditional display field.';
				}
			
				if (item.type == 'file_picker' && !item.store_file) {
					tags.tabs[tab].fields.store_file.error = 'Please select a file storage method.';
				}
				
				if ((item.type == 'centralised_radios' || item.type == 'centralised_select') && !item.values_source) {
					
					tags.tabs[tab].fields.values_source.error = 'Please select a source for this list.';
				}
				
				if (item.type == 'repeat_start') {
					if (!item.min_rows) {
						tags.tabs[tab].fields.min_rows.error = 'Please enter the minimum rows.';
					} else if (+item.min_rows != item.min_rows) {
						tags.tabs[tab].fields.min_rows.error = 'Please a valid number for mininum rows.';
					} else if (item.min_rows < 1 || item.min_rows > 10) {
						tags.tabs[tab].fields.min_rows.error = 'Mininum rows must be between 1 and 10.';
					} else if (+item.min_rows > +item.max_rows) {
						tags.tabs[tab].fields.min_rows.error = 'Minimum rows cannot be greater than maximum rows.';
					}
				
					if (!item.max_rows) {
						tags.tabs[tab].fields.max_rows.error = 'Please enter the maximum rows.';
					} else if (+item.max_rows != item.max_rows) {
						tags.tabs[tab].fields.max_rows.error = 'Please a valid number for maximum rows.';
					} else if (item.max_rows < 2 || item.max_rows > 20) {
						tags.tabs[tab].fields.max_rows.error = 'Maximum rows must be between 2 and 20.';
					}
				}
			}
		} else if (tab == 'validation') {
			if (!item.is_system_field) {
				if (item.required && !item.required_message) {
					tags.tabs[tab].fields.required_message.error = 'Please enter a message if not complete.';
				}
				if (item.validation && item.validation != 'none' && !item.validation_message) {
					tags.tabs[tab].fields.validation_message.error = 'Please enter a message if not valid.';
				}
			}
		}
	}
};


methods.getTUIXFieldCustomValues = function(type) {
	var values = {};
	
	//A list of centralised lists
	if (type == 'centralised_lists') {
		values = JSON.parse(JSON.stringify(thus.tuix.centralised_lists.values));
		
	//A list of datasets
	} else if (type == 'datasets') {
		values = JSON.parse(JSON.stringify(thus.tuix.datasets));
	
	//A list of boolean dataset fields
	} else if (type == 'boolean_fields') {
		var pages = thus.getOrderedPages();
		var useOptGroups = pages.length > 1;
		var ord = 0;
		for (var i = 0; i < pages.length; i++) {
			var page = pages[i];
			var fields = thus.getOrderedFields(pages[i].id);
			for (var j = 0; j < fields.length; j++) {
				var field = fields[j];
				if ((field.type != 'checkbox' && field.type != 'group') || (thus.editingThing == 'field' && field.id == thus.editingThingId)) {
					continue;
				}
				
				values[field.id] = {
					label: field.label,
					ord: ++ord
				};
				if (useOptGroups) {
					var parentId = 'page_' + page.id;
					values[field.id].parent = parentId;
					if (!values[parentId]) {
						values[parentId] = {
							label: page.label,
							ord: ++ord
						};
					}
				}
			}
		}
	}
	
	return values;
};

methods.loadPagesList = function() {
	//Find the current page
	var pages = thus.getOrderedPages();
	if (!thus.currentPageId && pages.length > 0) {
		thus.currentPageId = pages[0].id;
	}
	
	for (var i = 0; i < pages.length; i++) {
		if (thus.currentPageId == pages[i].id) {
			pages[i]._is_current_page = true;
		}
		pages[i].label = thus.formatPageLabel(pages[i].label);
	}
	
	//Set HTML
	var mergeFields = {pages: pages};
	var html = thus.microTemplate('zenario_organizer_admin_box_builder_tabs', mergeFields);
	$('#organizer_form_tabs').html(html);
	
	
	//Add events
	
	//Click on a page
	$('#organizer_form_tabs .tab').on('click', function() {
		var pageId = $(this).data('id');
		if (thus.tuix.pages[pageId]) {
			thus.clickPage(pageId);
		}
	});
	
	//Add a new page
	$('#organizer_add_new_tab').on('click', function() {
		if (thus.saveCurrentOpenDetails()) {
			var pageId = thus.createPage();
			thus.clickPage(pageId, true);
			thus.changeMadeToPanel();
		}
	});
	
	//Page sorting
	$('#organizer_form_tabs').sortable({
		containment: 'parent',
		tolerance: 'pointer',
		items: 'div.sort',
		start: function(event, ui) {
			thus.startIndex = ui.item.index();
		},
		stop: function(event, ui) {
			if (thus.startIndex == ui.item.index()) {
				return;
			}
			//Update ordinals
			$('#organizer_form_tabs .tab.sort').each(function(i) {
				var pageId = $(this).data('id');
				thus.tuix.pages[pageId].ord = (i + 1);
			});
			thus.pagesReordered = true;
			thus.changeMadeToPanel();
		}
	});
	
	//Moving fields between pages
	$('#organizer_form_tabs .tab').droppable({
		accept: 'div.is_sortable:not(.system_field)',
		greedy: true,
		hoverClass: 'ui-state-hover',
		tolerance: 'pointer',
		drop: function(event, ui) {
			if (thus.saveCurrentOpenDetails()) {
				var pageId = $(this).data('id');
				var fieldId = $(ui.draggable).data('id');
				
				thus.moveFieldToPage(thus.currentPageId, pageId, fieldId);
				
				if (thus.editingThing == 'field' && thus.editingThingId == fieldId) {
					thus.loadNewFieldsPanel();
				}
				thus.loadFieldsList(thus.currentPageId);
			}
		}
	});
};


methods.createPage = function() {
	var pageId = 't' + (thus.newItemCount++);
	var ord = 1;
	var pages = thus.getOrderedPages();
	if (pages.length) {
		ord = pages[pages.length - 1].ord + 1;
	}
	
	var page = {};
	page.id = pageId;
	page.ord = ord;
	page._just_added = true;
	page.fields = {};
	page.label = 'Untitled page';
	
	//Load default values for fields
	foreach (thus.tuix.dataset_page_details.tabs as var tuixTabName => var tuixTab) {
		if (tuixTab.fields) {
			foreach (tuixTab.fields as var tuixFieldName => var tuixField) {
				if (defined(tuixField.value) && !defined(page[tuixFieldName])) {
					page[tuixFieldName] = tuixField.value;
				}	
			}
		}
	}
	
	thus.tuix.pages[pageId] = page;
	return pageId;
};

methods.clickPage = function(pageId, isNewPage) {
	if (thus.editingThing == 'page' && thus.editingThingId == pageId) {
		return;
	}
	
	if (!thus.saveCurrentOpenDetails() || !thus.displayPageFieldOrderErrors()) {
		return;
	}
	
	//By clicking a page, we first show the fields on the page
	if (thus.currentPageId != pageId) {
		var stopAnimation = !thus.editingThing;
		thus.currentPageId = pageId;
		thus.editingThing = false;
		
		thus.loadPagesList();
		thus.loadFieldsList(pageId);
		thus.loadNewFieldsPanel(stopAnimation);
		
		var page = thus.getItem('page', pageId);
		
		//Complete both steps for new page
		if (isNewPage) {
			thus.clickPage(pageId);
		//Get record counts for fields on this page. This is page by page to stop long load times with
		//large datasets.
		} else if (!page.record_counts_fetched) {
			var actionRequests = {
				mode: 'get_tab_field_record_counts',
				pageId: pageId
			};
			thus.sendAJAXRequest(actionRequests, function(recordCounts) {
				page.record_counts_fetched = true;
				if (recordCounts) {
					foreach (recordCounts as fieldId => recordCount) {
						var field = thus.getItem('field', fieldId);
						field.record_count = recordCount;
					}
				}
			});
		}
		
	//If we click the page again then we start editing the page
	} else {
		thus.editingThing = 'page';
		thus.editingThingId = pageId;
		thus.editingThingTUIXTabId = false;
		
		thus.openPageEdit(pageId);
	}
};

methods.clickField = function(fieldId, justAdded) {
	if ((thus.editingThing == 'field' && thus.editingThingId == fieldId) || thus.tuix.items[fieldId].type == 'repeat_end') {
		return;
	}
	
	if (!thus.saveCurrentOpenDetails()) {
		return;
	}
	
	var field = thus.getItem('field', fieldId);
	if (!justAdded && field._just_added) {
		delete(field._just_added);
	}
	
	thus.editingThing = 'field';
	thus.editingThingId = fieldId;
	thus.editingThingTUIXTabId = false;
	
	thus.loadFieldsList(thus.currentPageId);
	thus.openFieldEdit(fieldId);
};

methods.loadFieldsList = function(pageId) {
	//Load HTML
	var fields = thus.getOrderedMergeFieldsForFields(pageId);
	var mergeFields = {fields: fields};
	var html = thus.microTemplate('zenario_organizer_admin_box_builder_section', mergeFields);
	$('#organizer_form_fields').html(html);
	
	//Add events
	
	//Select a field
	$('#organizer_form_fields div.form_field').on('click', function() {
		var fieldId = $(this).data('id');
		if (thus.tuix.items[fieldId]) {
			thus.clickField(fieldId);
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
				var ord = 0.1;
				var previousFieldId = $(this).prev().data('id');
				if (previousFieldId && thus.tuix.items[previousFieldId]) {
					ord = thus.tuix.items[previousFieldId].ord + 0.1;
				} else {
					var nextFieldId = $(this).next().data('id');
					if (nextFieldId && thus.tuix.items[nextFieldId]) {
						ord = thus.tuix.items[nextFieldId].ord - 0.1;
					}
				}
				
				var fieldId = thus.createField(fieldType, ord);
				thus.clickField(fieldId, true);
				thus.updateFieldOrds();
			});
		},
		start: function(event, ui) {
			thus.startIndex = ui.item.index();
		},
		//Detect reorder/new/deleted fields
		stop: function(event, ui) {
			if (thus.startIndex != ui.item.index()) {
				thus.tuix.pages[pageId].fields_reordered = true;
				//Update ordinals
				thus.updateFieldOrds();
				//Redraw fields for indenting
				thus.loadFieldsList(pageId);
			}
		}
	});
	
	//Delete a field
	$('#organizer_form_fields .delete_icon').on('click', function(e) {
		e.stopPropagation();
		var fieldId = $(this).data('id');
		
		//Make sure we can delete this field
		if (!thus.saveCurrentOpenDetails(true)) {
			return;
		}
		
		var field = thus.tuix.items[fieldId];
		if (field.is_protected) {
			message = "<p>This field is protected, and might be important to your site!</p>";
			message += "<p>If you're sure you want to delete this field then first unprotect it.</p>";
			zenarioA.floatingBox(message, true, 'warning', true);
		} else {
			if (field.record_count && field.record_count >= 1) {
				var plural = field.record_count == 1 ? '' : 's';
				message = "<p><strong>This field contains data on " + field.record_count + " record" + plural + ".</strong></p>";
				message += "<p>When you save changes to this dataset, this data will be deleted.</p>";
			} else {
				message = "<p>This field isn't populated in any data records.</p>";
			}
			
			message += "<p>Delete this dataset field?</p>";
		
			zenarioA.floatingBox(message, 'Delete dataset field', 'warning', true, false, undefined, undefined, function() {
				thus.deleteField(fieldId, true);
			});
		}
	});
};
methods.checkParentHasParent = function(parentId,indent) {
    var fieldClone = _.clone(thus.tuix.items[parentId]);
    var x = indent;
    if(fieldClone.parent_id){ 
        indent++;
        
    }
    if(x == indent){  
        return indent;
    } else {
        indent = thus.checkParentHasParent(fieldClone.parent_id,indent);
    }
    
return indent;
};

methods.getOrderedMergeFieldsForFields = function(pageId) {
	var mergeFields = [];
	var groupedFields = [];
	var groups = {};
	
	foreach (thus.tuix.pages[pageId].fields as var fieldId => var x) {
		var fieldClone = _.clone(thus.tuix.items[fieldId]);
		fieldClone.lov = thus.getOrderedFieldValues(fieldId);
		
		if(fieldClone.admin_box_visibility == "show"){
		    fieldClone.indent = 0;
		} else if(fieldClone.parent_id && fieldClone.admin_box_visibility == "show_on_condition"){
		    fieldClone.indent = 1;
		    fieldClone.indent = thus.checkParentHasParent(fieldClone.parent_id, fieldClone.indent);
		   
		} else {
		    
		}
		
		//Highlight the current field
		if (thus.editingThing == 'field') {
			if (thus.editingThingId == fieldClone.id) {
				fieldClone._is_current_field = true;
			}
		}
		
		if (fieldClone.grouping_name) {
			fieldClone.fields = [];
			groups[fieldClone.grouping_name] = fieldClone;
		} else if (fieldClone.grouping) {
			groupedFields.push(fieldClone);
		} else {
			fieldClone._is_sortable = true;
			mergeFields.push(fieldClone);
		}
	}
	
	if (groupedFields.length > 0) {
		for (i = 0; i < groupedFields.length; i++) {
			if (groups[groupedFields[i].grouping]) {
				groups[groupedFields[i].grouping].fields.push(groupedFields[i]);
			}
		}
	}
	
	foreach (groups as groupName => group) {
		group.fields.sort(thus.sortByOrd);
		if (group.fields.length) {
			group._is_sortable = true;
			group.fields[0].first_in_grouping = true;
			mergeFields.push(group);
		}
	}
	
	mergeFields.sort(thus.sortByOrd);
	
	//Remember which fields have a repeat above them for indenting
	var inRepeat = false;
	for (var i = 0; i < mergeFields.length; i++) {
		if (mergeFields[i].type == 'repeat_start') {
			inRepeat = true;
		} else if (mergeFields[i].type == 'repeat_end') {
			inRepeat = false;
		} else if (inRepeat) {
			mergeFields[i]._is_repeat_field = true;
		}
	}
	
	return mergeFields;
};

methods.updateFieldOrds = function() {	
	$('#organizer_form_fields div.form_field').each(function(i) {
		var fieldId = $(this).data('id');
		thus.tuix.items[fieldId].ord = (i + 1);
	});
	thus.changeMadeToPanel();
};

methods.deletePage = function(pageId) {
	var pages = thus.getOrderedPages();
	
	//Do not allow the final page to be deleted
	if (pages.length < 2) {
		return;
	}
	
	var fields = thus.getOrderedFields(pageId);
	for (var i = 0; i < pages.length; i++) {
		if (pages[i].id == pageId) {
			//Delete all fields on deleted page
			for (var j = 0; j < fields.length; j++) {
				thus.deleteField(fields[j].id)
			}
			
			if (pages[i - 1]) {
				var nextPageId = pages[i - 1].id;
			} else {
				var nextPageId = pages[i + 1].id;
			}
			thus.deletedPages.push(pageId);
			delete(thus.tuix.pages[pageId]);
			
			thus.clickPage(nextPageId);
			thus.changeMadeToPanel();
			break;
		}
	}
};

methods.deleteField = function(fieldId, selectNextField) {
	var field = thus.tuix.items[fieldId];
	if (!field) {
		return;
	}
	
	var fields = thus.getOrderedFields(field.page_id);
	
	//Delete a repeat start's matching repeat end
	if (field.type == 'repeat_start') {
		var repeatEndId = thus.getMatchingRepeatEnd(fieldId);
		if (repeatEndId) {
			var repeatEndField = thus.tuix.items[repeatEndId];
			thus.deletedFields.push(repeatEndId);
			delete(thus.tuix.pages[repeatEndField.page_id].fields[repeatEndId]);
			delete(thus.tuix.items[repeatEndId]);
		}
	}
	
	//Select the next field if one exists
	var deletedFieldIndex = false;
	var nextFieldId = false;
	for (var i = 0; i < fields.length; i++) {
		if (fields[i].id == fieldId) {
			deletedFieldIndex = i;
		}
		
		if (deletedFieldIndex !== false) {
			if (deletedFieldIndex > 0) {
				nextFieldId = fields[deletedFieldIndex - 1].id;
				break;
			} else if (fieldId != fields[i].id) {
				nextFieldId = fields[i].id;
				break;
			}
		}
	}
	if (nextFieldId && thus.tuix.items[nextFieldId].type == 'repeat_end') {
		nextFieldId = false;
	}
	
	thus.deletedFields.push(fieldId);
	delete(thus.tuix.pages[field.page_id].fields[fieldId]);
	delete(thus.tuix.items[fieldId]);
	
	thus.changeMadeToPanel();
	
	if (selectNextField) {
		thus.loadFieldsList(field.page_id);
		if (nextFieldId) {
			thus.clickField(nextFieldId);
		} else {
			thus.loadNewFieldsPanel();
		}
	}
};

methods.createField = function(type, ord) {
	var fieldId = 't' + (++thus.newItemCount);
	var field = {};
	field.id = fieldId;
	field.page_id = thus.currentPageId;
	field.type = type;
	field.ord = ord;
	field.label = 'Untitled';
	field._just_added = true;
	
	if (type == 'checkboxes' || type == 'radios' || type == 'select') {
		field.lov = {};
		for (var i = 1; i <= 3; i++) {
			thus.addFieldValue(field, 'Option ' + i);
		}
	} else if (type == 'repeat_start') {
		thus.createField('repeat_end', ord + 0.01);
	}
	
	thus.tuix.pages[thus.currentPageId].fields[fieldId] = 1;
	thus.tuix.items[fieldId] = field;
	
	thus.loadFieldsList(thus.currentPageId);
	return fieldId;
};

methods.changeMadeToPanel = function() {	
	if (!thus.changeMadeOnPanel) {
		thus.changeMadeOnPanel = true;
		window.onbeforeunload = function() {
			return 'You are currently editing this dataset. If you leave now you will lose any unsaved changes.';
		}
		var warningMessage = 'Please either save your changes, or click Reset to discard them, before exiting the form editor.';
		zenarioO.disableInteraction(warningMessage);
		zenarioO.setButtons();
	}
};

methods.saveChanges = function() {	
	var actionRequests = {
		mode: 'save',
		pages: JSON.stringify(thus.tuix.pages),
		fields: JSON.stringify(thus.tuix.items),
		pagesReordered: thus.pagesReordered,
		deletedPages: JSON.stringify(thus.deletedPages),
		deletedFields: JSON.stringify(thus.deletedFields),
		deletedValues: JSON.stringify(thus.deletedValues),
		currentPageId: thus.currentPageId,
		editingThing: thus.editingThing,
		editingThingId: thus.editingThingId
	};
	
	zenarioA.nowDoingSomething('saving', true);
	
	thus.sendAJAXRequest(actionRequests, function(info) {
		zenarioA.nowDoingSomething();
		
		if (info) {
			if (info.errors) {
				var message = '';
				for (var i = 0; i < info.errors.length; i++) {
					message += info.errors[i] + '<br>';
				}
				if (message) {
					zenarioA.floatingBox(message);
				}
			}
			thus.currentPageId = info.currentPageId;
			thus.editingThing = info.editingThing
			thus.editingThingId = info.editingThingId;
		}
		
		window.onbeforeunload = false;
		zenarioO.enableInteraction();

		thus.changeMadeOnPanel = false;
		thus.changesSaved = true;
		
		zenarioO.reload();
	});
};



}, zenarioO.panelTypes);

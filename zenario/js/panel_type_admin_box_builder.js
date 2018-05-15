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
	var html = thus.loadPanelHTML();
	$panel.html(html).show();
	
	//On initial load select the first page
	if (!thus.currentPageId) {
		var pages = thus.getOrderedPages();
		if (pages.length > 0) {
			thus.currentPageId = pages[0].name;
		}
	}
	
	thus.pagesReordered = false;
	thus.deletedPages = [];
	thus.deletedFields = [];
	thus.changeDetected = false;
	thus.maxNewCustomPage = 1;
	thus.maxNewCustomField = 1;
	thus.maxNewCustomFieldValue = 1;
	
	thus.selectedDetailsPage = thus.selectedDetailsPage ? thus.selectedDetailsPage : false;
	thus.selectedFieldId = thus.selectedFieldId ? thus.selectedFieldId : false;
	thus.selectedPageId = thus.selectedPageId ? thus.selectedPageId : false;
	
	//Load right panel
	thus.loadPagesList(thus.currentPageId);
	thus.loadFieldsList(thus.currentPageId);
	
	//Load left panel
	if (thus.selectedFieldId) {
		thus.loadFieldDetailsPanel(thus.selectedFieldId, true);
	} else if (thus.selectedPageId) {
		thus.loadPageDetailsPanel(thus.selectedPageId, true);
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

methods.loadPanelHTML = function() {
	var mergeFields = {
		dataset_label: thus.tuix.dataset.label
	};
	var html = thus.microTemplate('zenario_organizer_admin_box_builder', mergeFields);
	return html;
};

methods.loadFieldDetailsPanel = function(fieldId, stopEffect) {
	var field = thus.tuix.items[thus.currentPageId].fields[fieldId];
	
	//Load HTML
	var mergeFields = {
		mode: 'field_details',
		field_label: field.field_label,
		hide_tab_bar: thus.tuix.field_details.hide_tab_bar
	};
	
	var plural = field.record_count == 1 ? '' : 's';
	var record_count = field.record_count ? field.record_count : 0;
	mergeFields.record_count = '(' + record_count + ' record' + plural + ')';
	
	mergeFields.type = thus.getFieldReadableType(field.type);
	if (field.is_system_field) {
		mergeFields.type += ', system field';
	}
	
	var html = thus.microTemplate('zenario_organizer_admin_box_builder_left_panel', mergeFields);
	var $div = $('#organizer_admin_form_builder .form_fields_palette .form_fields_palette_outer').html(html);
	
	if (!stopEffect) {
		$div.hide().show('drop', {direction: 'left'}, 200);
	}
	
	//Load TUIX pages HTML
	var tuix = thus.tuix.field_details.tabs;
	var pages = thus.sortAndLoadTUIXPages(tuix, field, thus.selectedDetailsPage);
	var html = thus.getTUIXPagesHTML(pages);
	$('#organizer_field_details_tabs').html(html);
	
	//Add field details page events
	$('#organizer_field_details_tabs .tab').on('click', function() {
		var page = $(this).data('name');
		if (page && page != thus.selectedDetailsPage) {
			var errors = thus.saveCurrentOpenDetails();
			if (!errors) {
				thus.loadFieldDetailsPage(page, fieldId);
			}
		}
	});
	
	//Load TUIX fields html
	thus.loadFieldDetailsPage(thus.selectedDetailsPage, fieldId);
	
	//Add panel events
	$('#organizer_field_details_inner span.add_new_field').on('click', function() {
		var errors = thus.saveCurrentOpenDetails();
		if (!errors) {
			thus.selectedFieldId = false;
			thus.loadNewFieldsPanel();
			thus.highlightField(false);
		}
	});
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

methods.highlightField = function(fieldId) {
	$('#organizer_form_fields .form_field .form_field_inline_buttons').hide();
	$('#organizer_form_fields .form_field').removeClass('selected');
	if (fieldId) {
		$('#organizer_form_field_' + fieldId).addClass('selected');
		$('#organizer_form_field_' + fieldId + ' .form_field_inline_buttons').show();
	}
};

methods.loadFieldDetailsPage = function(page, fieldId, errors) {
	thus.selectedDetailsPage = page;
	var item = thus.tuix.items[thus.currentPageId].fields[fieldId];
	
	var path = 'field_details/' + page;
	var tuix = thus.tuix.field_details.tabs[page].fields;
	var fields = thus.loadFields(path, tuix, item);
	var formattedFields = thus.formatFieldDetails(fields, item, 'field', thus.selectedDetailsPage);
	var sortedFields = thus.sortFields(formattedFields, item);
	var html = '';
	
	//Add any errors
	if (!_.isEmpty(errors)) {
		errors = thus.sortErrors(errors);
		for (var i = 0; i < errors.length; i++) {
			html += '<p class="error">' + errors[i] + '</p>';
		}
	}
	
	html += thus.getTUIXFieldsHTML(sortedFields);
	
	$('#organizer_field_details_form').html(html);
	$('#organizer_field_details_tabs .tab').removeClass('on');
	$('#field_tab__' + page).addClass('on');
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
	if (page == 'details') {
		$('#field__field_label').on('keyup', function() {
			$('#organizer_form_field_' + fieldId + ' .label, #zenario_field_details_header_content .field_name h5').text($(this).val());
			if (item._is_new) {
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
		
	} else if (page == 'values') {
		if (formattedFields.values && !formattedFields.values._hidden) {
			var values = thus.getOrderedFieldValues(thus.currentPageId, fieldId);
			html = thus.microTemplate('zenario_organizer_admin_box_builder_field_value', values);
		
			$('#field_values_list').html(html).sortable({
				containment: 'parent',
				tolerance: 'pointer',
				axis: 'y',
				start: function(event, ui) {
					thus.startIndex = ui.item.index();
				},
				stop: function(event, ui) {
					if (thus.startIndex != ui.item.index()) {
						thus.saveFieldListOfValues(thus.tuix.fields[fieldId]);
						thus.loadFieldValuesListPreview(fieldId);
						thus.changeMadeToPanel();
					}
				}
			});
			
			$('#field_values_list input').on('keyup', function() {
				var id = $(this).data('id');
				$('#organizer_field_value_' + id + ' label').text($(this).val());
				thus.changeMadeToPanel();
			});
		
			//Add new field value
			$('#organizer_add_a_field_value').on('click', function() {
				thus.addFieldValue(item);
				thus.saveItemTUIXPage('field_details', page, item);
				thus.loadFieldDetailsPage(page, fieldId);
				thus.loadFieldValuesListPreview(fieldId);
				thus.changeMadeToPanel();
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
				thus.saveItemTUIXPage('field_details', page, item);
				thus.loadFieldDetailsPage(page, fieldId);
				thus.loadFieldValuesListPreview(fieldId);
				thus.changeMadeToPanel();
			});
		}
	}
};

methods.loadFieldValuesListPreview = function(fieldId) {
	if (thus.tuix.items[thus.currentPageId] && thus.tuix.items[thus.currentPageId].fields[fieldId]) {
		var field = thus.tuix.items[thus.currentPageId].fields[fieldId];
		var values = thus.getOrderedFieldValues(thus.currentPageId, fieldId, true);
		var html = false;
		if (field.type == 'select' || field.type == 'centralised_select') {
			html = thus.microTemplate('zenario_organizer_admin_box_builder_select_values', values);
			$('#organizer_form_field_' + fieldId + ' select').html(html);
		} else if (field.type == 'radios' || field.type == 'centralised_radios') {
			html = thus.microTemplate('zenario_organizer_admin_box_builder_radio_values_preview', values);
			$('#organizer_form_field_values_' + fieldId).html(html);
		} else if (field.type == 'checkboxes') {
			html = thus.microTemplate('zenario_organizer_admin_box_builder_checkbox_values_preview', values);
			$('#organizer_form_field_values_' + fieldId).html(html);
		}
	}
};



methods.saveCurrentOpenDetails = function(deletingField) {
	var errors = false, 
		item;
	if (thus.selectedFieldId 
		&& thus.tuix.items[thus.currentPageId] 
		&& thus.tuix.items[thus.currentPageId].fields[thus.selectedFieldId]
	) {
		
		if (deletingField) {
			errors = thus.getDeleteFieldErrors(thus.selectedFieldId);
		} else {
			item = thus.tuix.items[thus.currentPageId].fields[thus.selectedFieldId];
			errors = thus.saveItemTUIXPage('field_details', thus.selectedDetailsPage, item);
		}
		
		if (!_.isEmpty(errors)) {
			thus.loadFieldDetailsPage(thus.selectedDetailsPage, thus.selectedFieldId, errors);
		}
		
	} else if (thus.selectedPageId && thus.tuix.items[thus.selectedPageId]) {
		item = thus.tuix.items[thus.selectedPageId];
		errors = thus.saveItemTUIXPage('tab_details', thus.selectedDetailsPage, item);
		if (!_.isEmpty(errors)) {
			thus.loadPageDetailsPage(thus.selectedDetailsPage, thus.selectedPageId, errors);
		}
	}
	return !_.isEmpty(errors);
};

methods.displayPageFieldStructureErrors = function(pageId) {
	var errors = [],
		fields = thus.getOrderedFields(pageId),
		repeatStartField = false,
		inRepeatBlock = false, 
		repeatBlockDepth = 0,
		i, field;
	
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
	
	for (i = 0; i < fields.length; i++) {
		field = fields[i];
		
		if (field.type == 'repeat_start' && !inRepeatBlock) {
			repeatStartFieldId = field.id;
			inRepeatBlock = true;
		} else if (field.type == 'repeat_end') {
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
			
			
			if (thus.tuix.dataset_fields_in_forms[field.id] && !inRepeatBlock && field.repeat_start_id && field.repeat_start_id != repeatStartFieldId) {
				formIds = _.toArray(thus.tuix.dataset_fields_in_forms[field.id]);
				plural = formIds.length == 1 ? '' : 's';
				var error = 'The field "' + field.field_label + '" is used on ' + formIds.length + ' form' + plural + ' (';
				var formNames = [];
				for (var j = 0; j < formIds.length; j++) {
					if (thus.tuix.forms_with_dataset_fields[formIds[j]]) {
						formNames.push(thus.tuix.forms_with_dataset_fields[formIds[j]]);
					}
				}
				error += formNames.join(', ') + ') in a dataset repeat block. You must remove this repeat block from forms before this field can be moved out.';
				errors.push(error);
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
	
	return foundErrors;
};

methods.getDeleteFieldErrors = function(fieldId) {
	var errors = {},
		tPageId, tPage, tFieldId, tField;
	
	foreach (thus.tuix.items as tPageId => tPage) {
		if (tPage.parent_field_id == fieldId) {
			errors[tPageId] = 'Unable to delete the field because the tab "' + tPage.tab_label + '" depends on it.';
		}
		
		foreach (tPage.fields as tFieldId => tField) {
			if (fieldId != tFieldId) {
				if (tField.parent_id == fieldId) {
					errors[tFieldId] = 'Unable to delete the field because the field "' + tField.field_label + '" depends on it.';
				}
			}
		}
	}
	
	if (thus.tuix.dataset_fields_in_forms[fieldId]) {
		formIds = _.toArray(thus.tuix.dataset_fields_in_forms[fieldId]);
		plural = formIds.length == 1 ? '' : 's';
		errors[fieldId] = 'This field is used on ' + formIds.length + ' form' + plural + ' (';
		formNames = [];
		for (var i = 0; i < formIds.length; i++) {
			if (thus.tuix.forms_with_dataset_fields[formIds[i]]) {
				formNames.push(thus.tuix.forms_with_dataset_fields[formIds[i]]);
			}
		}
		errors[fieldId] += formNames.join(', ') + '). You must remove this field from all forms before you can delete it.';
	}
	
	return errors;
};


methods.validateFieldDetails = function(fields, item, mode, page, errors) {
	if (mode == 'field_details') {
		if (page == 'details') {
			if (!item.is_system_field) {
				if (item.type != 'repeat_start') {
					if (!item.db_column) {
						errors.db_column = 'Please enter a code name.';
					} else if (item.db_column.match(/[^a-z0-9_-]/)) {
						errors.db_column = 'Code name can only use characters a-z 0-9 _-.';
					} else {
						var isUnique = true;
						foreach (thus.tuix.items as var pageId => var page) {
							if (page.fields) {
								foreach (page.fields as var fieldId => var field) {
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
						if (item.db_column.match(/\_\_(\d+|rows)$/)) {
							errors.db_column = 'That code name is invalid.';
						}
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
				
				if (item.type == 'repeat_start') {
					if (!item.min_rows) {
						errors.min_rows = 'Please enter the minimum rows.';
					} else if (+item.min_rows != item.min_rows) {
						errors.min_rows = 'Please a valid number for mininum rows.';
					} else if (item.min_rows < 1 || item.min_rows > 10) {
						errors.min_rows = 'Mininum rows must be between 1 and 10.';
					}
				
					if (!item.max_rows) {
						errors.max_rows = 'Please enter the maximum rows.';
					} else if (+item.max_rows != item.max_rows) {
						errors.max_rows = 'Please a valid number for maximum rows.';
					} else if (item.max_rows < 2 || item.max_rows > 20) {
						errors.max_rows = 'Maximum rows must be between 2 and 20.';
					}
				
					if (!errors.min_rows && !errors.max_rows && (+item.min_rows > +item.max_rows)) {
						errors.min_rows = 'Minimum rows cannot be greater than maximum rows.';
					}
				}
			}
		} else if (page == 'validation') {
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
		//Page TUIX validation goes here
	}*/
};

methods.loadPageDetailsPanel = function(pageId, stopEffect) {	var label = thus.getPageLabel(thus.tuix.items[pageId].tab_label);
	
	//Load HTML
	var mergeFields = {
		mode: 'tab_details',
		tab_label: label,
		is_system_field: thus.tuix.items[pageId].is_system_field,
		hide_tab_bar: thus.tuix.tab_details.hide_tab_bar
	};
	
	var html = thus.microTemplate('zenario_organizer_admin_box_builder_left_panel', mergeFields);
	var $div = $('#organizer_admin_form_builder .form_fields_palette .form_fields_palette_outer').html(html);
	
	if (!stopEffect) {
		$div.hide().show('drop', {direction: 'left'}, 200);
	}
	
	//Load TUIX pages HTML
	var tuix = thus.tuix.tab_details.tabs;
	var item = thus.tuix.items[pageId];
	var pages = thus.sortAndLoadTUIXPages(tuix, item, thus.selectedDetailsPage);
	var html = thus.getTUIXPagesHTML(pages);
	$('#organizer_tab_details_tabs').html(html);
	
	//Add page details page events
	$('#organizer_tab_details_tabs .tab').on('click', function() {
		var page = $(this).data('id');
		if (page && page != thus.selectedDetailsPage) {
			var errors = thus.saveCurrentOpenDetails();
			if (!errors) {
				thus.loadPageDetailsPage(page, pageId);
			}
		}
	});
	
	//Load TUIX fields html
	thus.loadPageDetailsPage(thus.selectedDetailsPage, pageId);
	
	
	//Add panel events
	
	$('#organizer_tab_details_inner span.add_new_field').on('click', function() {
		var errors = thus.saveCurrentOpenDetails();
		if (!errors) {
			thus.loadNewFieldsPanel();
		}
	});
	
	$('#organizer_remove_form_tab').on('click', function(e) {
		var page = thus.tuix.items[thus.selectedPageId];
		if (page && !page.is_system_field) {
			var firstProtectedField = false;
			var hasProtectedFieldsCount = 0;
			if (page.fields) {
				foreach (page.fields as var fieldId => var field) {
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
				message = '<p>Are you sure you want to delete this tab?</p>';
				if (!_.isEmpty(page.fields)) {
					message += '<p>All fields on this tab will also be deleted.</p>';
				}
				zenarioA.floatingBox(message, 'Delete tab', 'warning', true, false, function() {
					thus.deletePage(thus.selectedPageId);
					thus.changeMadeToPanel();
				});
			}
		}
	});
};

methods.loadPageDetailsPage = function(page, pageId, errors) {
	thus.selectedDetailsPage = page;
	
	var path = 'tab_details/' + page;
	var tuix = thus.tuix.tab_details.tabs[page].fields;
	var item = thus.tuix.items[pageId];
	var fields = thus.loadFields(path, tuix, item);
	formattedFields = thus.formatFieldDetails(fields, item, 'page', thus.selectedDetailsPage);
	sortedFields = thus.sortFields(formattedFields, item);
	var html = '';
	
	//Add any errors
	if (!_.isEmpty(errors)) {
		errors = thus.sortErrors(errors);
		for (var i = 0; i < errors.length; i++) {
			html += '<p class="error">' + errors[i] + '</p>';
		}
	}
	
	//Load TUIX fields HTML
	html += thus.getTUIXFieldsHTML(sortedFields);
	
	$('#organizer_tab_details_form').html(html)
	$('#organizer_tab_details_tabs .tab').removeClass('on');
	$('#field_tab__' + page).addClass('on');
	
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
		if (page == 'details') {
		$('#field__tab_label').on('keyup', function() {
			var label = thus.getPageLabel($(this).val());
			$('#zenario_tab_details_header_content .field_name h5, #organizer_form_tab_' + pageId + ' span').text(label);
		});
	}
};

//Custom formatting logic for TUIX fields
methods.formatFieldDetails = function(fields, item, mode, page) {
	if (mode == 'field') {
		if (page == 'details') {
			if (item.tuix_type) {
				var type = thus.getFieldReadableType(item.type, item.tuix_type, true);
				fields.message.snippet.html = 'This is a field of type "' + type + '". You cannot edit this field\'s details.';
			}
			
			var values_source = fields.values_source._value;
			if (values_source && thus.tuix.centralised_lists.values[values_source]) {
				var list = thus.tuix.centralised_lists.values[values_source];
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
			
			if (item.type == 'repeat_start') {
				fields.field_label.label = 'Section heading:';
				fields.field_label.note_below = 'This will be the heading at the start of the repeating section.';
			}
	
		} else if (page == 'organizer') {
			if (!item.allow_admin_to_change_visibility) {
				fields.hide_in_organizer.note_below = 'Only specific system fields marked with a special property can have their visibility changed.'
				fields.hide_in_organizer._disabled = true;
				fields.hide_in_organizer._value = false;
			}
			
			if (['checkbox', 'group', 'radios', 'select', 'dataset_select', 'dataset_picker', 'file_picker', 'centralised_radios', 'centralised_select'].indexOf(item.type) != -1) {
				fields.create_index._value = 'index';
				fields.create_index._disabled = true;
			}
			
		} else if (page == 'values') {
			if (item.is_system_field) {
				fields.message.snippet.html = 'You cannot change the values of a system field.';
			} else if (item.type == 'centralised_radios' || item.type == 'centralised_select') {
				fields.message.snippet.html = 'You cannot change the values of a centralised list.';
			}
		}
	} /*else if (mode == 'page') {
		//Page TUIX formatting goes here
	}*/
	return fields;
};

methods.fieldDetailsChanged = function(path, field) {
	path = path.split('/');
	var mode = path[0],
		page = path[1],
		tuixField, valuesSource, actionRequests, thus, item;
	
	if (page && mode && thus.tuix[mode] && thus.tuix[mode].tabs[page] && thus.tuix[mode].tabs[page].fields[field]) {
		thus.changeMadeToPanel();
		tuixField = thus.tuix[mode].tabs[page].fields[field];
		if (tuixField.format_onchange) {
			if (mode == 'field_details') {
				item = thus.tuix.items[thus.currentPageId].fields[thus.selectedFieldId];
				thus.saveItemTUIXPage(mode, thus.selectedDetailsPage, item);
				thus.loadFieldDetailsPage(thus.selectedDetailsPage, thus.selectedFieldId);
				
				if (page == 'details' && field == 'values_source') {
					valuesSource = $('#field__values_source').val();
					if (valuesSource) {
						actionRequests = {
							mode: 'get_centralised_lov',
							method: valuesSource,
							filter: $('#field__values_source_filter').val()
						};
						thus.sendAJAXRequest(actionRequests).after(function(lov) {
							thus.tuix.items[thus.currentPageId].fields[thus.selectedFieldId].lov = lov;
							thus.loadFieldValuesListPreview(thus.selectedFieldId);
						});
					} else {
						thus.tuix.items[thus.currentPageId].fields[thus.selectedFieldId].lov = {};
						thus.loadFieldValuesListPreview(thus.selectedFieldId);
					}
				}
				
			} else if (mode == 'tab_details') {
				item = thus.tuix.items[thus.selectedPageId]
				thus.saveItemTUIXPage(mode, thus.selectedDetailsPage, item);
				thus.loadPageDetailsPage(thus.selectedDetailsPage, thus.selectedPageId);
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
		foreach (thus.tuix.items as var pageName => var page) {
			if (page.fields) {
				var options = [];
				foreach (page.fields as var fieldId => var pageField) {
					if (pageField.type && (pageField.type == 'checkbox' || pageField.type == 'group') && pageField.id != thus.selectedFieldId) {
						var option = {
							ord: pageField.ord,
							label: pageField.field_label,
							value: fieldId
						};
						if (fieldId == value) {
							option.selected = true;
						}
						options.push(option);
					}	
				}
				options.sort(thus.sortByOrd);
				
				if (options.length) {
					sortedValues.push({
						ord: page.ord,
						label: page.tab_label,
						hasChildren: true,
						options: options
					});
				}
			}
		}
	} else if (values == 'centralised_lists') {
		var i = 0;
		foreach (thus.tuix.centralised_lists.values as var func => var details) {
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
		foreach (thus.tuix.datasets as var datasetId => var dataset) {
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
	
	sortedValues.sort(thus.sortByOrd);
	return sortedValues;
};

methods.loadNewFieldsPanel = function(stopEffect) {
	thus.selectedPageId = false;
	thus.selectedFieldId = false;
	
	//Load HTML
	var mergeFields = {
		mode: 'new_fields',
		use_groups_field: thus.tuix.use_groups_field
	};
	var html = thus.microTemplate('zenario_organizer_admin_box_builder_left_panel', mergeFields);
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

methods.loadPagesList = function(pageId) {
	foreach (thus.tuix.items as var pageName => var page) {
		if (pageName == pageId) {
			page._selected = true;
		} else {
			delete(page._selected);
		}
	}
	//Load HTML
	var mergeFields = {
		tabs: thus.getOrderedPages()
	};
	var html = thus.microTemplate('zenario_organizer_admin_box_builder_tabs', mergeFields);
	$('#organizer_form_tabs').html(html);
	
	//Add events
		//Click on a page
	$('#organizer_form_tabs .tab').on('click', function() {
		var pageId = $(this).data('id');
		if (thus.tuix.items[pageId]) {
			thus.clickPage(pageId);
		}
	});
	//Add a new page
	$('#organizer_add_new_tab').on('click', function() {
		var errors = thus.saveCurrentOpenDetails();
		if (!errors) {
			thus.addNewPage();
			thus.changeMadeToPanel();
		}
	});
	//Page sorting
	$('#organizer_form_tabs').sortable({
		axis: 'x',
		containment: 'parent',
		tolerance: 'pointer',
		items: 'div.sort',
		start: function(event, ui) {
			thus.startIndex = ui.item.index();
		},
		stop: function(event, ui) {
			if (thus.startIndex != ui.item.index()) {
				//Update ordinals
				$('#organizer_form_tabs .tab.sort').each(function(i) {
					var pageId = $(this).data('id');
					thus.tuix.items[pageId].ord = (i + 1);
				});
				thus.pagesReordered = true;
				thus.changeMadeToPanel();
			}
		}
	});
	
	//Moving fields between pages
	$('#organizer_form_tabs .tab').droppable({
		accept: 'div.is_sortable:not(.system_field)',
		greedy: true,
		hoverClass: 'ui-state-hover',
		tolerance: 'pointer',
		drop: function(event, ui) {
			var errors = thus.saveCurrentOpenDetails();
			if (!errors) {
				var tPageId = $(this).data('id');
				var fieldId = $(ui.draggable).data('id');
				if ((tPageId != thus.currentPageId) && !thus.tuix.items[thus.currentPageId].fields[fieldId].is_system_field) {
				
					thus.moveFieldToPage(thus.currentPageId, tPageId, fieldId);
				
					if (fieldId == thus.selectedFieldId) {
						thus.loadNewFieldsPanel();
					}
					thus.loadFieldsList(thus.currentPageId);
				}
			}
		}
	});
};


methods.moveFieldToPage = function(fromPageId, toPageId, fieldId, addToStart) {
	if (fromPageId != toPageId 
		&& thus.tuix.items[toPageId] 
		&& thus.tuix.items[fromPageId] 
		&& thus.tuix.items[fromPageId].fields[fieldId]
		&& thus.tuix.items[fromPageId].fields[fieldId].type != 'repeat_end'
	) {
		thus.tuix.items[toPageId]._tabFieldsReordered = true;
		thus.tuix.items[fromPageId]._tabFieldsReordered = true;
		
		var ord = 1;
		var toFields = thus.getOrderedFields(toPageId);
		if (toFields.length) {
			if (addToStart) {
				ord = toFields[0].ord - 1;
			} else {
				ord = toFields[toFields.length - 1].ord + 1;
			}
		}
		
		//Move matching repeat_end for repeat_start
		if (thus.tuix.items[fromPageId].fields[fieldId].type == 'repeat_start') {
			var repeatEndId = thus.getMatchingRepeatEnd(fieldId);
			thus.tuix.items[toPageId].fields[repeatEndId] = thus.tuix.items[fromPageId].fields[repeatEndId];
			thus.tuix.items[toPageId].fields[repeatEndId].ord = ord + 0.001;
			delete(thus.tuix.items[fromPageId].fields[repeatEndId]);
		}
		
		thus.tuix.items[toPageId].fields[fieldId] = thus.tuix.items[fromPageId].fields[fieldId];
		thus.tuix.items[toPageId].fields[fieldId].ord = ord;
		delete(thus.tuix.items[fromPageId].fields[fieldId]);
		
		thus.changeMadeToPanel();
	}
};


methods.addNewPage = function() {
	var pageId = '__custom_tab_t' + (thus.maxNewCustomPage++);
	var nextPageOrd = 1;
	foreach (thus.tuix.items as name) {
		++nextPageOrd;
	}
	
	thus.tuix.items[pageId] = {
		ord: nextPageOrd,
		name: pageId,
		tab_label: 'Untitled page',
		fields: {},
		_is_new: true
	};
	
	thus.clickPage(pageId, true);
};

methods.clickPage = function(pageId, isNewPage) {
	if (thus.selectedPageId != pageId) {
		var detailErrors = thus.saveCurrentOpenDetails();
		var structureErrors = thus.displayPageFieldStructureErrors(thus.currentPageId);
		
		if (!detailErrors && !structureErrors) {
			//By clicking on the current page you can access it's details
			if (thus.currentPageId == pageId) {
				thus.selectedFieldId = false;
				thus.selectedPageId = pageId;
				thus.selectedDetailsPage = false;
				thus.loadPageDetailsPanel(pageId);
				return;
			}

			var page = thus.tuix.items[pageId];
			if (page.record_counts_fetched || page._is_new) {
				thus.clickPage2(pageId, isNewPage);
			} else {
				var actionRequests = {
					mode: 'get_tab_field_record_counts',
					pageId: pageId
				};
				thus.sendAJAXRequest(actionRequests).after(function(recordCounts) {
					page.record_counts_fetched = true;
					if (recordCounts) {
						foreach (recordCounts as fieldId => recordCount) {
							if (page.fields && page.fields[fieldId]) {
								page.fields[fieldId].record_count = recordCount;
							}
						}
					}
					thus.clickPage2(pageId, isNewPage);
				});
			}
		}
	}
};

methods.clickPage2 = function(pageId, isNewPage) {	
	thus.currentPageId = pageId;
	if (isNewPage) {
		thus.selectedPageId = pageId;
		thus.loadPageDetailsPanel(pageId);
	} else {
		var stopEffect = false;
		if (!thus.selectedFieldId && !thus.selectedPageId) {
			stopEffect = true;
		}
		thus.loadNewFieldsPanel(stopEffect);
	}
	thus.selectedFieldId = false;
	thus.loadPagesList(pageId);
	thus.loadFieldsList(pageId);
};

methods.clickField = function(fieldId) {
	if (thus.selectedFieldId != fieldId
		&& thus.tuix.items[thus.currentPageId]
		&& thus.tuix.items[thus.currentPageId].fields[fieldId]
		&& thus.tuix.items[thus.currentPageId].fields[fieldId].type != 'repeat_end'
	) {
		var errors = thus.saveCurrentOpenDetails();
		if (!errors) {
			thus.highlightField(fieldId);
			thus.selectedFieldId = fieldId;
			thus.selectedPageId = false;
			thus.selectedDetailsPage = false;
			thus.loadFieldDetailsPanel(fieldId);
		}
	}
};


methods.loadFieldsList = function(pageId) {
	var orderedFields = thus.getOrderedFields(pageId, true, true);
	
	//Load HTML
	var mergeFields = {
		fields: orderedFields
	};
	var html = thus.microTemplate('zenario_organizer_admin_box_builder_section', mergeFields);
	$('#organizer_form_fields').html(html);
	
	if (thus.selectedFieldId) {
		thus.highlightField(thus.selectedFieldId);
	}
	
	//Add events
		//Select a field
	$('#organizer_form_fields div.form_field').on('click', function() {
		var fieldId = $(this).data('id');
		if (thus.tuix.items[thus.currentPageId].fields[fieldId]) {
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
				var ord = 1;
				var previousFieldId = $(this).prev().data('id');
				if (previousFieldId && thus.tuix.items[thus.currentPageId].fields[previousFieldId]) {
					ord = thus.tuix.items[thus.currentPageId].fields[previousFieldId].ord + 1;
				}
				thus.addNewField(fieldType, ord);
				thus.updateFieldOrds();
			});
		},
		start: function(event, ui) {
			thus.startIndex = ui.item.index();
		},
		//Detect reorder/new/deleted fields
		stop: function(event, ui) {
			if (thus.startIndex != ui.item.index()) {
				thus.tuix.items[pageId]._tabFieldsReordered = true;
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
		if (thus.tuix.items[thus.currentPageId].fields[fieldId]) {
			var errors = thus.saveCurrentOpenDetails(true);
			if (!errors) {
				var field = thus.tuix.items[thus.currentPageId].fields[fieldId],
					plural, formIds, i, formNames;
				if (field.is_protected) {
					message = "<p>This field is protected, and might be important to your site!</p>";
					message += "<p>If you're sure you want to delete this field then first unprotect it.</p>";
					zenarioA.floatingBox(message, true, 'warning', true);
				} else {
					if (field.record_count && field.record_count >= 1) {
						plural = field.record_count == 1 ? '' : 's';
						message = "<p><strong>This field contains data on " + field.record_count + " record" + plural + ".</strong></p>";
						message += "<p>When you save changes to this dataset, this data will be deleted.</p>";
					} else {
						message = "<p>This field doesn't contain any data for any user/contact records.</p>";
					}
					
					message += "<p>Delete this dataset field?</p>";
				
					zenarioA.floatingBox(message, 'Delete dataset field', 'warning', true, false, function() {
						thus.deleteField(fieldId);
					});
				}
			}
		}
	});
};

methods.updateFieldOrds = function() {	$('#organizer_form_fields div.is_sortable').each(function(i) {
		var fieldId = $(this).data('id');
		thus.tuix.items[thus.currentPageId].fields[fieldId].ord = (i + 1);
	});
	thus.changeMadeToPanel();
};

methods.deletePage = function(pageId) {
	//To do: Handle case where there are NO system pages so no pages could be shown.
	//Select next page
	var pages = thus.getOrderedPages();
	var j = 0;
	for (var i = 0; i < pages.length; i++) {
		if (pages[i].name == pageId) {
			if (i > 0) {
				j = i - 1;
			} else {
				j = 1;
			}
			thus.deletedPages.push(pageId);
			
			if (thus.tuix.items[pageId].fields) {
				foreach (thus.tuix.items[pageId].fields as var fieldId => field) {
					thus.deletedFields.push(fieldId);
				}
			}
			delete(thus.tuix.items[pageId]);
			thus.clickPage(pages[j].name);
		}
	}
};

methods.deleteField = function(fieldId) {
	pageId = thus.currentPageId;
	if (thus.tuix.items[pageId].fields[fieldId]) {
		thus.changeMadeToPanel();
		thus.deletedFields.push(fieldId);
		
		var field = thus.tuix.items[pageId].fields[fieldId];
		//Delete a repeat start's matching repeat end
		if (field.type == 'repeat_start') {
			var repeatEndId = thus.getMatchingRepeatEnd(fieldId);
			if (repeatEndId) {
				thus.deletedFields.push(repeatEndId);
				delete(thus.tuix.items[pageId].fields[repeatEndId]);
			}
		}
		
		var fields = thus.getOrderedFields(pageId, false, true);
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
		delete(thus.tuix.items[pageId].fields[fieldId]);
		
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
		if (nextFieldId && thus.tuix.items[pageId].fields[nextFieldId].type == 'repeat_end') {
			nextFieldId = false;
		}
		
		thus.loadFieldsList(pageId);
		if (nextFieldId) {
			thus.clickField(nextFieldId);
		} else {
			thus.loadNewFieldsPanel();
		}
	}
};

methods.addNewField = function(type, ord) {
	var newFieldId = 't' + thus.maxNewCustomField++;
	var newField = {
		id: newFieldId,
		type: type,
		ord: ord,
		field_label: 'Untitled',
		_is_new: true
	};
	
	foreach (thus.tuix.items[thus.currentPageId].fields as var fieldId => var field) {
		if (field.ord >= ord) {
			field.ord++;
		}
	}
	
	if (type == 'checkboxes' || type == 'radios' || type == 'select') {
		newField.lov = {};
		for (var i = 1; i <= 3; i++) {
			thus.addFieldValue(newField, 'Option ' + i);
		}
	} else if (type == 'repeat_start') {
		thus.addNewField('repeat_end', ord + 0.01);
	}
	
	thus.tuix.items[thus.currentPageId].fields[newFieldId] = newField;
	thus.loadFieldsList(thus.currentPageId);
	thus.clickField(newFieldId);
};


methods.getOrderedPages = function() {
	var sortedPages = [];
	foreach (thus.tuix.items as var pageName => var page) {
		sortedPages.push(page);
	}
	sortedPages.sort(thus.sortByOrd);
	return sortedPages;
};

methods.getOrderedFields = function(pageId, orderValues, groupGroupedFields) {
	var sortedFields = [],
	    groupedFields = [],
	    groups = {},
	    i, fieldId, field, fieldClone, length, index, groupName, group,
	    inRepeat = false;
	    
	if (thus.tuix.items[pageId] && thus.tuix.items[pageId].fields) {
		foreach (thus.tuix.items[pageId].fields as fieldId => field) {
			fieldClone = _.clone(field);
			if (orderValues) {
				fieldClone.lov = thus.getOrderedFieldValues(pageId, fieldId, true);
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
		group._fields.sort(thus.sortByOrd);
		if (group._fields.length) {
			group._isSortable = true;
			group._fields[0].first_in_grouping = true;
			sortedFields.push(group);
		}
	}
	
	sortedFields.sort(thus.sortByOrd);
	
	//Remember which fields have a repeat above them for indenting
	for (var i = 0; i < sortedFields.length; i++) {
		if (sortedFields[i].type == 'repeat_start') {
			inRepeat = true;
		} else if (sortedFields[i].type == 'repeat_end') {
			inRepeat = false;
		} else if (inRepeat) {
			sortedFields[i]._inRepeat = true;
		}
	}
	
	return sortedFields;
};

methods.getOrderedFieldValues = function(pageId, fieldId, includeEmptyValue) {
	var sortedValues = [],
		valueId, value;
	if (thus.tuix.items[pageId]
		&& thus.tuix.items[pageId].fields
		&& thus.tuix.items[pageId].fields[fieldId]
		&& thus.tuix.items[pageId].fields[fieldId].lov
	) {
		foreach (thus.tuix.items[pageId].fields[fieldId].lov as valueId => value) {
			sortedValues.push(value);
		}
		var type = thus.tuix.items[pageId].fields[fieldId].type;
		if (includeEmptyValue && (type == 'select' || type == 'centralised_select')) {
			sortedValues.push({
				id: 'empty_value',
				label: '-- Select --',
				ord: -1
			});
		}
	}
	sortedValues.sort(thus.sortByOrd);
	return sortedValues;
};

methods.changeMadeToPanel = function() {	if (!thus.changeDetected) {
		thus.changeDetected = true;
		window.onbeforeunload = function() {
			return 'You are currently editing this dataset. If you leave now you will lose any unsaved changes.';
		}
		var warningMessage = 'Please either save your changes, or click Reset to discard them, before exiting the form editor.';
		zenarioO.disableInteraction(warningMessage);
		zenarioO.setButtons();
	}
};

methods.saveChanges = function() {	var actionRequests = {
		mode: 'save',
		data: JSON.stringify(thus.tuix.items),
		pagesReordered: thus.pagesReordered,
		deletedPages: JSON.stringify(thus.deletedPages),
		deletedFields: JSON.stringify(thus.deletedFields),
		currentPageId: thus.currentPageId
	};
	if (thus.selectedPageId) {
		actionRequests.selectedPageId = thus.selectedPageId;
	}
	if (thus.selectedFieldId) {
		actionRequests.selectedFieldId = thus.selectedFieldId;
	}
	
	function save() {
		zenarioA.nowDoingSomething('saving', true);
		thus.sendAJAXRequest(actionRequests).after(function(info) {
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
				thus.selectedPageId = info.selectedPageId;
				thus.selectedFieldId = info.selectedFieldId;
			}
	
			zenarioA.nowDoingSomething();
			window.onbeforeunload = false;
			zenarioO.enableInteraction();
	
			thus.changeDetected = false;
			thus.changesSaved = true;
	
			zenarioO.reload();
		});
	};
	
	//zenarioA.floatingBox('rly?', 'Save', 'warning', true, false, save);
	save();
};



}, zenarioO.panelTypes);
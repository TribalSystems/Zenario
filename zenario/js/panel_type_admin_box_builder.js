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
	
	//On initial load select the first page
	if (!this.currentPageId) {
		var pages = this.getOrderedPages();
		if (pages.length > 0) {
			this.currentPageId = pages[0].name;
		}
	}
	
	this.pagesReordered = false;
	this.deletedPages = [];
	this.deletedFields = [];
	this.changeDetected = false;
	this.maxNewCustomPage = 1;
	this.maxNewCustomField = 1;
	this.maxNewCustomFieldValue = 1;
	
	this.selectedDetailsPage = this.selectedDetailsPage ? this.selectedDetailsPage : false;
	this.selectedFieldId = this.selectedFieldId ? this.selectedFieldId : false;
	this.selectedPageId = this.selectedPageId ? this.selectedPageId : false;
	
	//Load right panel
	this.loadPagesList(this.currentPageId);
	this.loadFieldsList(this.currentPageId);
	
	//Load left panel
	if (this.selectedFieldId) {
		this.loadFieldDetailsPanel(this.selectedFieldId, true);
	} else if (this.selectedPageId) {
		this.loadPageDetailsPanel(this.selectedPageId, true);
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
	var field = this.tuix.items[this.currentPageId].fields[fieldId];
	
	//Load HTML
	var mergeFields = {
		mode: 'field_details',
		field_label: field.field_label,
		hide_tab_bar: this.tuix.field_details.hide_tab_bar
	};
	
	var plural = field.record_count == 1 ? '' : 's';
	var record_count = field.record_count ? field.record_count : 0;
	mergeFields.record_count = '(' + record_count + ' record' + plural + ')';
	
	mergeFields.type = this.getFieldReadableType(this.currentPageId, fieldId);
	if (field.is_system_field) {
		mergeFields.type += ', system field';
	}
	
	var html = this.microTemplate('zenario_organizer_admin_box_builder_left_panel', mergeFields);
	var $div = $('#organizer_admin_form_builder .form_fields_palette .form_fields_palette_outer').html(html);
	
	if (!stopEffect) {
		$div.hide().show('drop', {direction: 'left'}, 200);
	}
	
	//Load TUIX pages HTML
	var tuix = this.tuix.field_details.tabs;
	var pages = this.sortAndLoadTUIXPages(tuix, field, this.selectedDetailsPage);
	var html = this.getTUIXPagesHTML(pages);
	$('#organizer_field_details_tabs').html(html);
	
	//Add field details page events
	var that = this;
	$('#organizer_field_details_tabs .tab').on('click', function() {
		var page = $(this).data('name');
		if (page && page != that.selectedDetailsPage) {
			var errors = that.saveCurrentOpenDetails();
			if (!errors) {
				that.loadFieldDetailsPage(page, fieldId);
			}
		}
	});
	
	//Load TUIX fields html
	this.loadFieldDetailsPage(this.selectedDetailsPage, fieldId);
	
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

methods.getFieldReadableType = function(page, fieldId, getOtherSystemFieldTUIXType) {
	var field = this.tuix.items[page].fields[fieldId];
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
	this.selectedDetailsPage = page;
	var item = this.tuix.items[this.currentPageId].fields[fieldId];
	
	var path = 'field_details/' + page;
	var tuix = this.tuix.field_details.tabs[page].fields;
	var fields = this.loadFields(path, tuix, item);
	var formattedFields = this.formatFieldDetails(fields, item, 'field', this.selectedDetailsPage);
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
	var that = this;
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
			var values = this.getOrderedFieldValues(this.currentPageId, fieldId);
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
						that.saveFieldListOfValues(that.tuix.fields[fieldId]);
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
				that.saveItemTUIXPage('field_details', page, item);
				that.loadFieldDetailsPage(page, fieldId);
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
				that.saveItemTUIXPage('field_details', page, item);
				that.loadFieldDetailsPage(page, fieldId);
				that.loadFieldValuesListPreview(fieldId);
				that.changeMadeToPanel();
			});
		}
	}
};

methods.loadFieldValuesListPreview = function(fieldId) {
	if (this.tuix.items[this.currentPageId] && this.tuix.items[this.currentPageId].fields[fieldId]) {
		var field = this.tuix.items[this.currentPageId].fields[fieldId];
		var values = this.getOrderedFieldValues(this.currentPageId, fieldId, true);
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



methods.saveCurrentOpenDetails = function(deletingField) {
	var errors = false, 
		item;
	if (this.selectedFieldId 
		&& this.tuix.items[this.currentPageId] 
		&& this.tuix.items[this.currentPageId].fields[this.selectedFieldId]
	) {
		
		if (deletingField) {
			errors = this.getDeleteFieldErrors(this.selectedFieldId);
		} else {
			item = this.tuix.items[this.currentPageId].fields[this.selectedFieldId];
			errors = this.saveItemTUIXPage('field_details', this.selectedDetailsPage, item);
		}
		
		if (!_.isEmpty(errors)) {
			this.loadFieldDetailsPage(this.selectedDetailsPage, this.selectedFieldId, errors);
		}
		
	} else if (this.selectedPageId && this.tuix.items[this.selectedPageId]) {
		item = this.tuix.items[this.selectedPageId];
		errors = this.saveItemTUIXPage('tab_details', this.selectedDetailsPage, item);
		if (!_.isEmpty(errors)) {
			this.loadPageDetailsPage(this.selectedDetailsPage, this.selectedPageId, errors);
		}
	}
	return !_.isEmpty(errors);
};

methods.displayPageFieldStructureErrors = function(pageId) {
	var errors = [],
		fields = this.getOrderedFields(pageId),
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
				errors.push('Field type "' + this.getFieldReadableType(field.type).toLowerCase() + '" is not allowed in a repeat block.');
			}
			
			
			if (this.tuix.dataset_fields_in_forms[field.id] && !inRepeatBlock && field.repeat_start_id && field.repeat_start_id != repeatStartFieldId) {
				formIds = _.toArray(this.tuix.dataset_fields_in_forms[field.id]);
				plural = formIds.length == 1 ? '' : 's';
				var error = 'The field "' + field.field_label + '" is used on ' + formIds.length + ' form' + plural + ' (';
				var formNames = [];
				for (var j = 0; j < formIds.length; j++) {
					if (this.tuix.forms_with_dataset_fields[formIds[j]]) {
						formNames.push(this.tuix.forms_with_dataset_fields[formIds[j]]);
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
	
	foreach (this.tuix.items as tPageId => tPage) {
		if (tPage.parent_field_id == fieldId) {
			errors[tPageId] = 'Unable to delete the field because the page "' + tPage.tab_label + '" depends on it.';
		}
		
		foreach (tPage.fields as tFieldId => tField) {
			if (fieldId != tFieldId) {
				if (tField.parent_id == fieldId) {
					errors[tFieldId] = 'Unable to delete the field because the field "' + tField.field_label + '" depends on it.';
				}
			}
		}
	}
	
	if (this.tuix.dataset_fields_in_forms[fieldId]) {
		formIds = _.toArray(this.tuix.dataset_fields_in_forms[fieldId]);
		plural = formIds.length == 1 ? '' : 's';
		errors[fieldId] = 'This field is used on ' + formIds.length + ' form' + plural + ' (';
		formNames = [];
		for (var i = 0; i < formIds.length; i++) {
			if (this.tuix.forms_with_dataset_fields[formIds[i]]) {
				formNames.push(this.tuix.forms_with_dataset_fields[formIds[i]]);
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
						foreach (this.tuix.items as var pageId => var page) {
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

methods.loadPageDetailsPanel = function(pageId, stopEffect) {
	var that = this;
	var label = this.getPageLabel(this.tuix.items[pageId].tab_label);
	
	//Load HTML
	var mergeFields = {
		mode: 'tab_details',
		tab_label: label,
		is_system_field: this.tuix.items[pageId].is_system_field,
		hide_tab_bar: this.tuix.tab_details.hide_tab_bar
	};
	
	var html = this.microTemplate('zenario_organizer_admin_box_builder_left_panel', mergeFields);
	var $div = $('#organizer_admin_form_builder .form_fields_palette .form_fields_palette_outer').html(html);
	
	if (!stopEffect) {
		$div.hide().show('drop', {direction: 'left'}, 200);
	}
	
	//Load TUIX pages HTML
	var tuix = this.tuix.tab_details.tabs;
	var item = this.tuix.items[pageId];
	var pages = this.sortAndLoadTUIXPages(tuix, item, this.selectedDetailsPage);
	var html = this.getTUIXPagesHTML(pages);
	$('#organizer_tab_details_tabs').html(html);
	
	//Add page details page events
	$('#organizer_tab_details_tabs .tab').on('click', function() {
		var page = $(this).data('id');
		if (page && page != that.selectedDetailsPage) {
			var errors = that.saveCurrentOpenDetails();
			if (!errors) {
				that.loadPageDetailsPage(page, pageId);
			}
		}
	});
	
	//Load TUIX fields html
	this.loadPageDetailsPage(this.selectedDetailsPage, pageId);
	
	
	//Add panel events
	
	$('#organizer_tab_details_inner span.add_new_field').on('click', function() {
		var errors = that.saveCurrentOpenDetails();
		if (!errors) {
			that.loadNewFieldsPanel();
		}
	});
	
	$('#organizer_remove_form_tab').on('click', function(e) {
		var page = that.tuix.items[that.selectedPageId];
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
				message = '<p>This page has the protected field "' + firstProtectedField.field_label + '"';
				hasProtectedFieldsCount--;
				if (hasProtectedFieldsCount > 0) {
					var plural = hasProtectedFieldsCount == 1 ? '' : 's';
					message += ' and ' + hasProtectedFieldsCount + ' other protected field' + plural;
				}
				message += ' on it and cannot be deleted.</p>';
				zenarioA.floatingBox(message, true, 'warning', true);
			} else {
				message = '<p>Are you sure you want to delete this Page?</p>';
				if (!_.isEmpty(page.fields)) {
					message += '<p>All fields on this page will also be deleted.</p>';
				}
				zenarioA.floatingBox(message, 'Delete', 'warning', true, false, function() {
					that.deletePage(that.selectedPageId);
					that.changeMadeToPanel();
				});
			}
		}
	});
};

methods.loadPageDetailsPage = function(page, pageId, errors) {
	this.selectedDetailsPage = page;
	
	var path = 'tab_details/' + page;
	var tuix = this.tuix.tab_details.tabs[page].fields;
	var item = this.tuix.items[pageId];
	var fields = this.loadFields(path, tuix, item);
	formattedFields = this.formatFieldDetails(fields, item, 'page', this.selectedDetailsPage);
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
	
	var that = this;
	if (page == 'details') {
		$('#field__tab_label').on('keyup', function() {
			var label = that.getPageLabel($(this).val());
			$('#zenario_tab_details_header_content .field_name h5, #organizer_form_tab_' + pageId + ' span').text(label);
		});
	}
};

//Custom formatting logic for TUIX fields
methods.formatFieldDetails = function(fields, item, mode, page) {
	if (mode == 'field') {
		if (page == 'details') {
			if (item.tuix_type) {
				var type = this.getFieldReadableType(this.currentPageId, item.id, true);
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
		tuixField, valuesSource, actionRequests, that, item;
	
	if (page && mode && this.tuix[mode] && this.tuix[mode].tabs[page] && this.tuix[mode].tabs[page].fields[field]) {
		this.changeMadeToPanel();
		tuixField = this.tuix[mode].tabs[page].fields[field];
		if (tuixField.format_onchange) {
			if (mode == 'field_details') {
				item = this.tuix.items[this.currentPageId].fields[this.selectedFieldId];
				this.saveItemTUIXPage(mode, this.selectedDetailsPage, item);
				this.loadFieldDetailsPage(this.selectedDetailsPage, this.selectedFieldId);
				
				if (page == 'details' && field == 'values_source') {
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
							that.tuix.items[that.currentPageId].fields[that.selectedFieldId].lov = lov;
							that.loadFieldValuesListPreview(that.selectedFieldId);
						});
					} else {
						this.tuix.items[this.currentPageId].fields[this.selectedFieldId].lov = {};
						this.loadFieldValuesListPreview(this.selectedFieldId);
					}
				}
				
			} else if (mode == 'tab_details') {
				item = this.tuix.items[this.selectedPageId]
				this.saveItemTUIXPage(mode, this.selectedDetailsPage, item);
				this.loadPageDetailsPage(this.selectedDetailsPage, this.selectedPageId);
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
		foreach (this.tuix.items as var pageName => var page) {
			if (page.fields) {
				var options = [];
				foreach (page.fields as var fieldId => var pageField) {
					if (pageField.type && (pageField.type == 'checkbox' || pageField.type == 'group') && pageField.id != this.selectedFieldId) {
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
				options.sort(this.sortByOrd);
				
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
	this.selectedPageId = false;
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

methods.loadPagesList = function(pageId) {
	foreach (this.tuix.items as var pageName => var page) {
		if (pageName == pageId) {
			page._selected = true;
		} else {
			delete(page._selected);
		}
	}
	//Load HTML
	var mergeFields = {
		tabs: this.getOrderedPages()
	};
	var html = this.microTemplate('zenario_organizer_admin_box_builder_tabs', mergeFields);
	$('#organizer_form_tabs').html(html);
	
	//Add events
	
	var that = this;
	//Click on a page
	$('#organizer_form_tabs .tab').on('click', function() {
		var pageId = $(this).data('id');
		if (that.tuix.items[pageId]) {
			that.clickPage(pageId);
		}
	});
	//Add a new page
	$('#organizer_add_new_tab').on('click', function() {
		var errors = that.saveCurrentOpenDetails();
		if (!errors) {
			that.addNewPage();
			that.changeMadeToPanel();
		}
	});
	//Page sorting
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
					var pageId = $(this).data('id');
					that.tuix.items[pageId].ord = (i + 1);
				});
				that.pagesReordered = true;
				that.changeMadeToPanel();
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
			var errors = that.saveCurrentOpenDetails();
			if (!errors) {
				var tPageId = $(this).data('id');
				var fieldId = $(ui.draggable).data('id');
				if ((tPageId != that.currentPageId) && !that.tuix.items[that.currentPageId].fields[fieldId].is_system_field) {
				
					that.moveFieldToPage(that.currentPageId, tPageId, fieldId);
				
					if (fieldId == that.selectedFieldId) {
						that.loadNewFieldsPanel();
					}
					that.loadFieldsList(that.currentPageId);
				}
			}
		}
	});
};


methods.moveFieldToPage = function(fromPageId, toPageId, fieldId, addToStart) {
	if (fromPageId != toPageId 
		&& this.tuix.items[toPageId] 
		&& this.tuix.items[fromPageId] 
		&& this.tuix.items[fromPageId].fields[fieldId]
		&& this.tuix.items[fromPageId].fields[fieldId].type != 'repeat_end'
	) {
		this.tuix.items[toPageId]._tabFieldsReordered = true;
		this.tuix.items[fromPageId]._tabFieldsReordered = true;
		
		var ord = 1;
		var toFields = this.getOrderedFields(toPageId);
		if (toFields.length) {
			if (addToStart) {
				ord = toFields[0].ord - 1;
			} else {
				ord = toFields[toFields.length - 1].ord + 1;
			}
		}
		
		//Move matching repeat_end for repeat_start
		if (this.tuix.items[fromPageId].fields[fieldId].type == 'repeat_start') {
			var repeatEndId = this.getMatchingRepeatEnd(fieldId);
			this.tuix.items[toPageId].fields[repeatEndId] = this.tuix.items[fromPageId].fields[repeatEndId];
			this.tuix.items[toPageId].fields[repeatEndId].ord = ord + 0.001;
			delete(this.tuix.items[fromPageId].fields[repeatEndId]);
		}
		
		this.tuix.items[toPageId].fields[fieldId] = this.tuix.items[fromPageId].fields[fieldId];
		this.tuix.items[toPageId].fields[fieldId].ord = ord;
		delete(this.tuix.items[fromPageId].fields[fieldId]);
		
		this.changeMadeToPanel();
	}
};


methods.addNewPage = function() {
	var pageId = '__custom_tab_t' + (this.maxNewCustomPage++);
	var nextPageOrd = 1;
	foreach (this.tuix.items as name) {
		++nextPageOrd;
	}
	
	this.tuix.items[pageId] = {
		ord: nextPageOrd,
		name: pageId,
		tab_label: 'Untitled page',
		fields: {},
		_is_new: true
	};
	
	this.clickPage(pageId, true);
};

methods.clickPage = function(pageId, isNewPage) {
	if (this.selectedPageId != pageId) {
		var detailErrors = this.saveCurrentOpenDetails();
		var structureErrors = this.displayPageFieldStructureErrors(this.currentPageId);
		
		if (!detailErrors && !structureErrors) {
			//By clicking on the current page you can access it's details
			if (this.currentPageId == pageId) {
				this.selectedFieldId = false;
				this.selectedPageId = pageId;
				this.selectedDetailsPage = false;
				this.loadPageDetailsPanel(pageId);
				return;
			}

			var page = this.tuix.items[pageId];
			if (page.record_counts_fetched || page._is_new) {
				this.clickPage2(pageId, isNewPage);
			} else {
				var that = this;
				var actionRequests = {
					mode: 'get_tab_field_record_counts',
					pageId: pageId
				};
				this.sendAJAXRequest(actionRequests).after(function(recordCounts) {
					page.record_counts_fetched = true;
					var recordCounts = JSON.parse(recordCounts);
					if (recordCounts) {
						foreach (recordCounts as fieldId => recordCount) {
							if (page.fields && page.fields[fieldId]) {
								page.fields[fieldId].record_count = recordCount;
							}
						}
					}
					that.clickPage2(pageId, isNewPage);
				});
			}
		}
	}
};

methods.clickPage2 = function(pageId, isNewPage) {	
	this.currentPageId = pageId;
	if (isNewPage) {
		this.selectedPageId = pageId;
		this.loadPageDetailsPanel(pageId);
	} else {
		var stopEffect = false;
		if (!this.selectedFieldId && !this.selectedPageId) {
			stopEffect = true;
		}
		this.loadNewFieldsPanel(stopEffect);
	}
	this.selectedFieldId = false;
	this.loadPagesList(pageId);
	this.loadFieldsList(pageId);
};

methods.clickField = function(fieldId) {
	if (this.selectedFieldId != fieldId
		&& this.tuix.items[this.currentPageId]
		&& this.tuix.items[this.currentPageId].fields[fieldId]
		&& this.tuix.items[this.currentPageId].fields[fieldId].type != 'repeat_end'
	) {
		var errors = this.saveCurrentOpenDetails();
		if (!errors) {
			this.highlightField(fieldId);
			this.selectedFieldId = fieldId;
			this.selectedPageId = false;
			this.selectedDetailsPage = false;
			this.loadFieldDetailsPanel(fieldId);
		}
	}
};


methods.loadFieldsList = function(pageId) {
	var orderedFields = this.getOrderedFields(pageId, true, true);
	
	//Load HTML
	var mergeFields = {
		fields: orderedFields
	};
	var html = this.microTemplate('zenario_organizer_admin_box_builder_section', mergeFields);
	$('#organizer_form_fields').html(html);
	
	if (this.selectedFieldId) {
		this.highlightField(this.selectedFieldId);
	}
	
	//Add events
	
	var that = this;
	//Select a field
	$('#organizer_form_fields div.form_field').on('click', function() {
		var fieldId = $(this).data('id');
		if (that.tuix.items[that.currentPageId].fields[fieldId]) {
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
				if (previousFieldId && that.tuix.items[that.currentPageId].fields[previousFieldId]) {
					ord = that.tuix.items[that.currentPageId].fields[previousFieldId].ord + 1;
				}
				that.addNewField(fieldType, ord);
				that.updateFieldOrds();
			});
		},
		start: function(event, ui) {
			that.startIndex = ui.item.index();
		},
		//Detect reorder/new/deleted fields
		stop: function(event, ui) {
			if (that.startIndex != ui.item.index()) {
				that.tuix.items[pageId]._tabFieldsReordered = true;
				//Update ordinals
				that.updateFieldOrds();
				//Redraw fields for indenting
				that.loadFieldsList(pageId);
			}
		}
	});
	//Delete a field
	$('#organizer_form_fields .delete_icon').on('click', function(e) {
		e.stopPropagation();
		var fieldId = $(this).data('id');
		if (that.tuix.items[that.currentPageId].fields[fieldId]) {
			var errors = that.saveCurrentOpenDetails(true);
			if (!errors) {
				var field = that.tuix.items[that.currentPageId].fields[fieldId],
					plural, formIds, i, formNames;
				if (field.is_protected) {
					message = "<p>This field is protected, and might be important to your site!</p>";
					message += "<p>If you're sure you want to delete this field then first unprotect it.</p>";
					zenarioA.floatingBox(message, true, 'warning', true);
				} else {
					if (field.record_count && field.record_count >= 1) {
						plural = field.record_count == 1 ? '' : 's';
						message = "<p><strong>This field contains data on " + field.record_count + " record" + plural + ".</strong></p>";
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
		}
	});
};

methods.updateFieldOrds = function() {
	var that = this;
	$('#organizer_form_fields div.is_sortable').each(function(i) {
		var fieldId = $(this).data('id');
		that.tuix.items[that.currentPageId].fields[fieldId].ord = (i + 1);
	});
	this.changeMadeToPanel();
};

methods.deletePage = function(pageId) {
	//To do: Handle case where there are NO system pages so no pages could be shown.
	//Select next page
	var pages = this.getOrderedPages();
	var j = 0;
	for (var i = 0; i < pages.length; i++) {
		if (pages[i].name == pageId) {
			if (i > 0) {
				j = i - 1;
			} else {
				j = 1;
			}
			this.deletedPages.push(pageId);
			
			if (this.tuix.items[pageId].fields) {
				foreach (this.tuix.items[pageId].fields as var fieldId => field) {
					this.deletedFields.push(fieldId);
				}
			}
			delete(this.tuix.items[pageId]);
			this.clickPage(pages[j].name);
		}
	}
};

methods.deleteField = function(fieldId) {
	pageId = this.currentPageId;
	if (this.tuix.items[pageId].fields[fieldId]) {
		this.changeMadeToPanel();
		this.deletedFields.push(fieldId);
		
		var field = this.tuix.items[pageId].fields[fieldId];
		//Delete a repeat start's matching repeat end
		if (field.type == 'repeat_start') {
			var repeatEndId = this.getMatchingRepeatEnd(fieldId);
			if (repeatEndId) {
				this.deletedFields.push(repeatEndId);
				delete(this.tuix.items[pageId].fields[repeatEndId]);
			}
		}
		
		var fields = this.getOrderedFields(pageId, false, true);
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
		delete(this.tuix.items[pageId].fields[fieldId]);
		
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
		if (nextFieldId && this.tuix.items[pageId].fields[nextFieldId].type == 'repeat_end') {
			nextFieldId = false;
		}
		
		this.loadFieldsList(pageId);
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
		_is_new: true
	};
	
	foreach (this.tuix.items[this.currentPageId].fields as var fieldId => var field) {
		if (field.ord >= ord) {
			field.ord++;
		}
	}
	
	if (type == 'checkboxes' || type == 'radios' || type == 'select') {
		newField.lov = {};
		for (var i = 1; i <= 3; i++) {
			this.addFieldValue(newField, 'Option ' + i);
		}
	} else if (type == 'repeat_start') {
		this.addNewField('repeat_end', ord + 0.01);
	}
	
	this.tuix.items[this.currentPageId].fields[newFieldId] = newField;
	this.loadFieldsList(this.currentPageId);
	this.clickField(newFieldId);
};


methods.getOrderedPages = function() {
	var sortedPages = [];
	foreach (this.tuix.items as var pageName => var page) {
		sortedPages.push(page);
	}
	sortedPages.sort(this.sortByOrd);
	return sortedPages;
};

methods.getOrderedFields = function(pageId, orderValues, groupGroupedFields) {
	var sortedFields = [],
	    groupedFields = [],
	    groups = {},
	    i, fieldId, field, fieldClone, length, index, groupName, group,
	    inRepeat = false;
	    
	if (this.tuix.items[pageId] && this.tuix.items[pageId].fields) {
		foreach (this.tuix.items[pageId].fields as fieldId => field) {
			fieldClone = _.clone(field);
			if (orderValues) {
				fieldClone.lov = this.getOrderedFieldValues(pageId, fieldId, true);
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
	if (this.tuix.items[pageId]
		&& this.tuix.items[pageId].fields
		&& this.tuix.items[pageId].fields[fieldId]
		&& this.tuix.items[pageId].fields[fieldId].lov
	) {
		foreach (this.tuix.items[pageId].fields[fieldId].lov as valueId => value) {
			sortedValues.push(value);
		}
		var type = this.tuix.items[pageId].fields[fieldId].type;
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
		pagesReordered: this.pagesReordered,
		deletedPages: JSON.stringify(this.deletedPages),
		deletedFields: JSON.stringify(this.deletedFields),
		currentPageId: this.currentPageId
	};
	if (this.selectedPageId) {
		actionRequests.selectedPageId = this.selectedPageId;
	}
	if (this.selectedFieldId) {
		actionRequests.selectedFieldId = this.selectedFieldId;
	}
	
	function save() {
		zenarioA.nowDoingSomething('saving', true);
		that.sendAJAXRequest(actionRequests).after(function(info) {
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
				that.currentPageId = info.currentPageId;
				that.selectedPageId = info.selectedPageId;
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
	
	//zenarioA.floatingBox('rly?', 'Save', 'warning', true, false, save);
	save();
};



}, zenarioO.panelTypes);
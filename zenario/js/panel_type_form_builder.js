/*
 * Copyright (c) 2019, Tribal Limited
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
	panelTypes.form_builder = extensionOf(panelTypes.form_builder_base_class)
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
	thus.editingNameField = false;
	thus.editingNameFieldComplete = false;
	thus.deletedPages = [];
	thus.deletedFields = [];
	thus.deletedValues = [];
	thus.changeMadeOnPanel = false;
	thus.pagesReordered = false;
	thus.newItemCount = 0;
	
	//On initial load create the first page
	var pages = thus.getOrderedPages();
	if (pages.length == 0) {
		thus.createPage();
	}
	
	thus.fixTUIXData();
	
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
	var pages = thus.getOrderedPages();
	for (var i = 0; i < pages.length; i++) {
		var page = thus.getItem('page', pages[i].id);
		
		if (page.visible_condition_checkboxes_field_value) {
			page.visible_condition_checkboxes_field_value = zenarioT.tuixToArray(page.visible_condition_checkboxes_field_value);
		}
		
		var fields = thus.getOrderedFields(page.id);
		for (var j = 0; j < fields.length; j++) {
			var field = thus.getItem('field', fields[j].id);
			if (field.type == 'calculated' && field.calculation_code) {
				field.calculation_code = zenarioT.tuixToArray(field.calculation_code);
			}
			
			if (field.invalid_responses) {
				field.invalid_responses = zenarioT.tuixToArray(field.invalid_responses);
			}
			
			if (field.visible_condition_checkboxes_field_value) {
				field.visible_condition_checkboxes_field_value = zenarioT.tuixToArray(field.visible_condition_checkboxes_field_value);
			}
			if (field.mandatory_condition_checkboxes_field_value) {
				field.mandatory_condition_checkboxes_field_value = zenarioT.tuixToArray(field.mandatory_condition_checkboxes_field_value);
			}
		}
	}
};

methods.getMainPanelHTML = function() {
	var mergeFields = {
		form_title: thus.tuix.form_title
	};
	var html = thus.microTemplate('zenario_organizer_form_builder', mergeFields);
	return html;
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
		pages[i].name = thus.formatPageLabel(pages[i].name);
	}
	//Only show page pages if there are at least 2
	if (pages.length <= 1) {
		pages = false;
	}

	//Set HTML
	var mergeFields = {pages: pages};
	var html = thus.microTemplate('zenario_organizer_form_builder_tabs', mergeFields);
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
		
			//If currently viewing a pages details, reload because the content changes depending on position
			if (thus.editingThing == 'page') {
				thus.saveCurrentOpenDetails();
				thus.openPageEdit(thus.editingThingId, thus.editingThingTUIXTabId, true);
			}
		}
	});

	//Moving fields between pages
	$('#organizer_form_tabs .tab').droppable({
		accept: 'div.is_sortable:not(.repeat_end)',
		greedy: true,
		hoverClass: 'ui-state-hover',
		tolerance: 'pointer',
		drop: function(event, ui) {
			if (thus.saveCurrentOpenDetails()) {
				var pageId = $(this).data('id');
				var fieldId = $(ui.draggable).data('id');
				
				if (pageId && fieldId) {
					thus.moveFieldToPage(thus.currentPageId, pageId, fieldId);
	
					if (thus.editingThing == 'field' && thus.editingThingId == fieldId) {
						thus.loadNewFieldsPanel();
					}
					thus.loadFieldsList(thus.currentPageId);
				}
			}
		}
	});
};

methods.getOrderedMergeFieldsForFields = function(pageId) {
	var mergeFields = [];
	var groupedFields = [];
	var groups = {};
	
	foreach (thus.tuix.pages[pageId].fields as var fieldId => var x) {
		var fieldClone = _.clone(thus.tuix.items[fieldId]);
		
		fieldClone.lov = thus.getOrderedFieldValues(fieldId);
		
		if (fieldClone.is_consent) {
			fieldClone.type = 'consent';
		}
		
		//Highlight the current field
		if (thus.editingThing == 'field') {
			if (thus.editingThingId == fieldClone.id) {
				fieldClone._is_current_field = true;
			}
		}
		
		//Hide duplicate button for dataset fields
		if (fieldClone.dataset_field_id) {
			fieldClone._hide_duplicate_button = true;
		}
		
		//Group together dataset repeat fields
		if (fieldClone.dataset_repeat_grouping) {
			if (!groups[fieldClone.dataset_repeat_grouping]) {
				groups[fieldClone.dataset_repeat_grouping] = {};
				groups[fieldClone.dataset_repeat_grouping].fields = [];
			}
			if (fieldClone.type != 'repeat_start') {
				fieldClone._hide_drag_button = true;
				fieldClone._hide_delete_button = true;
				fieldClone._hide_duplicate_button = true;
			}
			groupedFields.push(fieldClone);
		} else {
			fieldClone._is_sortable = true;
			mergeFields.push(fieldClone);
		}
	}
	
	if (groupedFields.length > 0) {
		for (var i = 0; i < groupedFields.length; i++) {
			groups[groupedFields[i].dataset_repeat_grouping].fields.push(groupedFields[i]);
		}
	}
	
	foreach (groups as var groupName => var group) {
		group.fields.sort(thus.sortByOrd);
		if (group.fields.length) {
			group.ord = group.fields[0].ord;
			group._is_sortable = true;
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

methods.getOrderedMergeFieldsForDatasetFields = function() {
	var mergeFields = [];
	var usedDatasetFields = {};
	var fields = thus.getOrderedFields();
	for (var i = 0; i < fields.length; i++) {
		if (fields[i].dataset_field_id) {
			usedDatasetFields[fields[i].dataset_field_id] = true;
		}
	}
	
	foreach (thus.tuix.dataset.tabs as var tabName => var tab) {
		var tabClone = _.clone(tab);
		tabClone.fields = [];
		foreach (tab.fields as var fieldId => var x) {
			var fieldClone = _.clone(thus.tuix.dataset.fields[fieldId]);
			if (usedDatasetFields[fieldId] || fieldClone.repeat_start_id != 0) {
				continue;
			}
			fieldClone.tab_label = tabClone.label ? tabClone.label : tabClone.default_label;
			tabClone.fields.push(fieldClone);
		}
		tabClone.fields.sort(thus.sortByOrd);
		mergeFields.push(tabClone);
	}
	mergeFields.sort(thus.sortByOrd)
	
	return mergeFields;
};

methods.getFormFieldMergeName = function(fieldId) {
	var field = thus.getItem('field', fieldId);
	return field.db_column ? field.db_column : 'unlinked_' + field.type + '_' + fieldId;
};

methods.createPage = function() {
	var pageId = 't' + (++thus.newItemCount);
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
	page.name = 'Page ' + (pages.length + 1);
	
	//Load default values for fields
	foreach (thus.tuix.form_page_details.tabs as var tuixTabName => var tuixTab) {
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

methods.createField = function(type, ord, datasetFieldId, copyFromFieldId) {
	var fieldId = 't' + (++thus.newItemCount);
	var field = {};
	
	//Create for a dataset field
	if (datasetFieldId) {
		var datasetField = thus.tuix.dataset.fields[datasetFieldId];
		field.type = datasetField.type;
		if (field.type == 'consent') {
			field.type = 'checkbox';
			field.is_consent = true;
		}
		
		field.name = datasetField.label;
		field.label = datasetField.label;
		field.dataset_field_id = datasetFieldId;
		field.values_source = datasetField.values_source;
		field.values_source_filter = datasetField.values_source_filter;
		field.db_column = datasetField.db_column;
		
		//Special logic to set terms and conditions label
		if (datasetField.db_column == 'terms_and_conditions_accepted') {
			field.label = 'By submitting your details you are agreeing that we can store your data for legitimate business purposes and contact you to inform you about our products and services.';
			field.note_to_user = 'Full details can be found in our <a href="privacy" target="_blank">privacy policy</a>.';
		}
		
		if (datasetField.repeat_start_id) {
			field.dataset_repeat_grouping = datasetField.repeat_start_id;
		}
		
		if (datasetField.lov) {
			field.lov = datasetField.lov;
		}
		
		if (datasetField.type == 'repeat_start') {
			field.dataset_repeat_grouping = datasetField.id;
			
			var orderedFields = [];
			foreach (thus.tuix.dataset.tabs[datasetField.tab_name].fields as var datasetTabFieldId => var x) {
				var datasetTabField = thus.tuix.dataset.fields[datasetTabFieldId];
				if (datasetTabField.repeat_start_id == datasetFieldId) {
					var datasetTabFieldClone = _.clone(datasetTabField);
					orderedFields.push(datasetTabFieldClone);
				}
			}
			orderedFields.sort(thus.sortByOrd);
			
			for (var i = 0; i < orderedFields.length; i++) {
				thus.createField(undefined, ord + (0.001 * (i + 1)), orderedFields[i].id);
			}
		}
		
	//Copy from existing field
	} else if (copyFromFieldId) {
		field = JSON.parse(JSON.stringify(thus.getItem('field', copyFromFieldId)));
		if (field.type == 'checkboxes' || field.type == 'select' || field.type == 'radios') {
			var lov = field.lov;
			delete(field.lov);
			
			field.lov = {};
			for (var valueId in lov) {
				thus.addFieldValue(field, lov[valueId].label, lov[valueId].ord);
			}
		}
		
	//Create new blank field
	} else {
		field.type = type;
		field.label = 'Untitled';
		field.name = 'Untitled ' + thus.getFieldReadableType(type).toLowerCase();
		
		if (type == 'checkboxes' || type == 'radios' || type == 'select') {
			field.lov = {};
			for (var i = 1; i <= 3; i++) {
				thus.addFieldValue(field, 'Option ' + i);
			}
		} else if (type == 'repeat_start') {
			thus.createField('repeat_end', ord + 0.001);
		}
	}
	
	//Load default values for fields
	foreach (thus.tuix.form_field_details.tabs as var tuixTabName => var tuixTab) {
		if (tuixTab.fields) {
			foreach (tuixTab.fields as var tuixFieldName => var tuixField) {
				if (defined(tuixField.value) && !defined(field[tuixFieldName])) {
					field[tuixFieldName] = tuixField.value;
				}	
			}
		}
	}
	
	field.id = fieldId;
	field.page_id = thus.currentPageId;
	field.ord = ord;
	field._just_added = true;
	
	thus.tuix.pages[thus.currentPageId].fields[fieldId] = 1;
	thus.tuix.items[fieldId] = field;
	
	thus.loadFieldsList(thus.currentPageId);
	return fieldId;
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
		
		//Complete both steps for new page
		if (isNewPage) {
			thus.clickPage(pageId);
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

methods.openEdit = function(itemType, itemId, tuixTabId, stopAnimation) {
	thus.editingNameField = false;
	thus.editingNameFieldComplete = false;
	
	//Set left panel HTML
	var item = thus.getItem(itemType, itemId);
	var tuixMode = thus.getTUIXModeForItemType(itemType);
	var tuix = thus.tuix[tuixMode];
	var mergeFields = {
		name: item.name,
		hide_tab_bar: tuix.hide_tab_bar
	};
	
	if (itemType == 'page') {
		mergeFields.type = 'page_break';
		mergeFields.mode = 'edit_page';
		item.type = mergeFields.type  ;
	} else if (itemType == 'field') {
		mergeFields.type = item.type;
		mergeFields.mode = 'edit_field';
	}
	
	mergeFields.type_phrase = thus.getFieldReadableType(item);
	if (item.dataset_field_id) {
		mergeFields.type_phrase += ', linked field → ' + item.db_column;
	}
	
	var html = thus.microTemplate('zenario_organizer_form_builder_left_panel', mergeFields);
	var $div = $('#organizer_form_builder .form_fields_palette .form_fields_palette_outer').html(html);
	
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
	
	//Edit form settings
	$('input.form_settings').on('click', function() {
		thus.openFormSettings();
	});
	
	//Edit name field
	$('#zenario_field_details_header_content .edit_field_name_button').on('click', function() {
		$('#zenario_field_details_header_content .view_mode').hide();
		$('#zenario_field_details_header_content .edit_mode').show();
		thus.editingNameField = true;
	});
	$('#zenario_field_details_header_content .done_field_name_button').on('click', function() {
		$('#zenario_field_details_header_content .edit_mode').hide();
		$('#zenario_field_details_header_content .view_mode').show();
		
		thus.editingNameField = false;
		thus.editingNameFieldComplete = true;
		
		var name = $('#field__name').val();
		$('#zenario_field_details_header_content .view_mode h5').text(name);
	});
	$('#field__name').on('keyup', function() {
		thus.changeMadeToPanel();
		if (itemType == 'page') {
			var name = thus.formatPageLabel($(this).val());
			$('#organizer_form_tab_' + thus.currentPageId + ' span').text(name);
		}
	});
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
			//Move all fields on deleted page to another
			if (pages[i - 1]) {
				var nextPageId = pages[i - 1].id;
				for (var j = 0; j < fields.length; j++) {
					thus.moveFieldToPage(pageId, nextPageId, fields[j].id);
				}
			} else {
				var nextPageId = pages[i + 1].id;
				for (var j = fields.length - 1; j >= 0; j--) {
					thus.moveFieldToPage(pageId, nextPageId, fields[j].id, true);
				}
			}
			thus.deletedPages.push(pageId);
			delete(thus.tuix.pages[pageId]);
			
			thus.clickPage(nextPageId);
			thus.changeMadeToPanel();
			break;
		}
	}
};

methods.deleteField = function(fieldId) {
	var field = thus.tuix.items[fieldId];
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
	
	//When deleting a dataset repeat, remove all fields inside as well.
	if (field.dataset_repeat_grouping && field.type == 'repeat_start') {
		for (var i = 0; i < fields.length; i++) {
			if (fields[i].dataset_repeat_grouping && fields[i].dataset_repeat_grouping == field.dataset_repeat_grouping && fields[i].type != 'repeat_start') {
				thus.deletedFields.push(fields[i].id);
				delete(thus.tuix.pages[field.page_id].fields[fields[i].id]);
				delete(thus.tuix.items[fields[i].id]);
			}
		}
	}
	
	//Select the next field if one exists
	var deletedFieldIndex = false;
	var nextFieldId = false;
	var fields = thus.getOrderedFields(field.page_id);
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
	thus.loadFieldsList(field.page_id);
	
	if (nextFieldId) {
		thus.clickField(nextFieldId);
	} else {
		thus.loadNewFieldsPanel();
	}
};

methods.addTUIXTabEvents = function(itemType, itemId, tuixTabId) {
	var item = thus.getItem(itemType, itemId);
	var tuix = thus.tuix[thus.getTUIXModeForItemType(itemType)];
	
	if (itemType == 'page') {
		$('#organizer_remove_form_page').on('click', function(e) {
			var message = '<p>Are you sure you want to delete this page?</p>';
			if (item.fields.length) {
				message += '<p>All fields on this page will be moved onto the previous page.</p>';
			}
			zenarioA.floatingBox(message, 'Delete', 'warning', true, false, function() {
				thus.deletePage(itemId);
			});
		});
		
	} else if (itemType == 'field') {
		if (tuixTabId == 'details') {
			$('#field__label').on('keyup', function() {
				$('#organizer_form_field_' + itemId + ' .label').text($(this).val());
				if (item._just_added && !thus.editingNameField && !thus.editingNameFieldComplete) {
					var fieldName = $(this).val().replace(/:/g, '');
					$('#field__name').val(fieldName);
					$('#field_display__name').text(fieldName);
				}
			});
			
			$('#field__note_to_user').on('keyup', function() {
				var value = $(this).val().trim();
				$('#organizer_form_field_note_below_' + itemId + ' div.zenario_note_content').text(value);
				$('#organizer_form_field_note_below_' + itemId).toggle(value !== '');
			});
			
			$('#field__placeholder').on('keyup', function() {
				$('#organizer_form_field_' + itemId + ' :input').prop('placeholder', $(this).val());
			});
			
			$('#field__description').on('keyup', function() {
				$('#organizer_form_field_' + itemId + ' .description').text($(this).val());
			});
			
			$('#edit_field_calculation').on('click', function() {
				//Get all numeric text fields
				var numericFields = {};
				var fields = thus.getOrderedFields();
				for (var i = 0; i < fields.length; i++) {
					var field = fields[i];
					if ((field.type == 'text' && ['number', 'integer', 'floating_point'].indexOf(field.field_validation) != -1) || (field.type == 'calculated' && field.id != itemId)) {
						numericFields[field.id] = {label: field.name, ord: i};
					}
				}
				
				var key = {
					id: itemId, 
					title: 'Editing the calculation for the field "' + item.name + '"',
					calculation_code: JSON.stringify(item.calculation_code)
				};
				var values = {
					details: {
						dummy_field: JSON.stringify(numericFields)
					}
				};
				
				zenarioAB.open(
					'zenario_field_calculation', 
					key,
					undefined, values,
					function(key, values) {
						if (values.details.calculation_code) {
							item.calculation_code = JSON.parse(values.details.calculation_code);
						} else {
							item.calculation_code = '';
						}
						thus.tuixFieldDetailsChanged(itemType, itemId, tuixTabId, 'calculation_code');
					}
				);
			});
			
		} else if (tuixTabId == 'advanced') {
			$('#field__css_classes').on('keyup', function() {
				$('#organizer_form_field_' + itemId + ' .form_field_classes .css').toggle(!!$(this).val()).prop('title', $(this).val());
			});
			$('#field__div_wrap_class').on('keyup', function() {
				$('#organizer_form_field_' + itemId + ' .form_field_classes .div').toggle(!!$(this).val()).prop('title', $(this).val());
			});
			
		} else if (tuixTabId == 'values') {
			
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
					delete(item.crm_lov[valueId]);
				}
				thus.saveTUIXTab(tuix, tuixTabId, item);
				thus.openItemTUIXTab(itemType, itemId, tuixTabId);
				thus.loadFieldValuesListPreview(itemId);
				thus.changeMadeToPanel();
			});
			
		} else if (tuixTabId == 'translations') {
			$('#organizer_field_translations input').on('keyup', function() {
				thus.changeMadeToPanel();
			});
			
		} else if (tuixTabId == 'crm') {
			$('#field__crm_validate_test').on('click', function() {
				//Save any changes
				thus.saveTUIXTab(tuix, tuixTabId, item);
				var itemJSON = JSON.stringify(item);
				
				//Send item details to be validated by salesforce
				actionRequests = {
					mode: 'validate_salesforce_field',
					item: itemJSON
				};
				thus.sendAJAXRequest(actionRequests).after(function(response) {
					//Validation successfull
					if (response === true) {
						var notices = ['Field is valid.'];
						thus.openItemTUIXTab(itemType, itemId, thus.editingThingTUIXTabId, undefined, undefined, notices);
						
					//Validation could not be completed
					} else if (response === false) {
						var errors = ['Something went wrong. Validation could not be completed.'];
						thus.openItemTUIXTab(itemType, itemId, thus.editingThingTUIXTabId, errors);
						
					//Validation failed
					} else if (response.error) {
						var errors = [];
						if (response.error == 'missing_values') {
							for (var i = 0; i < response.values.length; i++) {
								errors.push('Field value not found: "' + response.values[i] + '"');
							}
						} else if (response.error == 'not_found') {
							errors.push('Field not found.');
						} else if (response.error == 'too_many_values') {
							errors.push('Too many values to load from Salesforce.')
						} else {
							errors.push('Unknown error.');
						}
						thus.openItemTUIXTab(itemType, itemId, thus.editingThingTUIXTabId, errors);
					}
				});
			});
			
			//Swap CRM values and labels around for centralised lists
			$('#organizer_crm_button__set_labels').on('click', function() {
				thus.changeMadeToPanel();
				$('#organizer_field_crm_values input.crm_value_input').each(function(i, e) {
					var valueId = $(this).data('id');
					var value = '';
					if (item.lov && item.lov[valueId]) {
						value = item.lov[valueId].label;
					}
					$(this).val(value);
				});
			});
			$('#organizer_crm_button__set_values').on('click', function() {
				thus.changeMadeToPanel();
				$('#organizer_field_crm_values input.crm_value_input').each(function(i, e) {
					$(this).val($(this).data('id'));
				});
			});
			
			$('#organizer_field_crm_values input.crm_value_input').on('keyup', function() {
				thus.changeMadeToPanel();
			});
		}
	}
};

methods.loadFieldsList = function(pageId) {
	//Load HTML
	var fields = thus.getOrderedMergeFieldsForFields(pageId);
	var mergeFields = {fields: fields};
	var html = thus.microTemplate('zenario_organizer_form_builder_section', mergeFields);
	$('#organizer_form_fields').html(html);
	
	//Add events
	
	//Click on a field
	$('#organizer_form_fields div.form_field').on('click', function() {
		var fieldId = $(this).data('id');
		if (thus.tuix.items[fieldId]) {
			thus.clickField(fieldId);
		}
	});
	
	//Show information on hover
	var hoverTimeoutId;
	$('#organizer_form_fields div.form_field').hover(
		//mouseover
		function() {
			var that = this;
			if (!hoverTimeoutId) {
				hoverTimeoutId = setTimeout(function() {
					hoverTimeoutId = null;
					var fieldId = $(that).data('id');
					$('#organizer_form_field_' + fieldId + ' .form_field_classes').show();
				}, 200)
			}
		//mouseout
		}, function() {
			if (hoverTimeoutId) {
				clearTimeout(hoverTimeoutId);
				hoverTimeoutId = null;
			} else {
				var fieldId = $(this).data('id');
				if (thus.editingThing == 'field' && fieldId != thus.editingThingId) {
					$('#organizer_form_field_' + fieldId + ' .form_field_classes').hide();
				}
			}
		}
	);
	$('#organizer_form_builder').tooltip();
	
	//Make fields sortable
	$('#organizer_form_fields .form_section').sortable({
		items: 'div.is_sortable',
		tolerance: 'pointer',
		placeholder: 'preview',
		//Add new field to the form
		receive: function(event, ui) {
			$(this).find('div.field_type, div.dataset_field').each(function() {
				var fieldType = $(this).data('type');
				var datasetFieldId = $(this).data('id');
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
				
				var fieldId = thus.createField(fieldType, ord, datasetFieldId);
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
		var transferFields = {};
		
		//Data can be transfered to fields of the same type
		//Groups can have their data moved into checkbox fields also
		var fields = thus.getOrderedFields();
		for (var i = 0; i < fields.length; i++) {
			if ((field.type == fields[i].type || (field.type == 'group' && fields[i].type == 'checkbox')) && fieldId != fields[i].id ) {
				transferFields[fields[i].id] = fields[i].name;
			}
		}
		
		var keys = {
			id: fieldId,
			field_name: field.name,
			field_type: field.type,
			field_english_type: thus.getFieldReadableType(field)
		};
		//Pass the fields this responses can be transfered to a dummy field via values because this may be too large for the key
		//which is passed in the URL
		var values = {details: {dummy_field: JSON.stringify(transferFields)}};
		zenarioAB.open(
			'zenario_delete_form_field',
			keys,
			undefined, values,
			function(key, values) {
				//Migrate data to another field
				var migrateResponsesTo = undefined;
				if (values.details.delete_field_options == 'delete_field_but_migrate_data' 
					&& values.details.migration_field
				) {
					if (thus.tuix.items[values.details.migration_field]) {
						thus.tuix.items[values.details.migration_field]._migrate_responses_from = fieldId;
					}
				}
				thus.deleteField(fieldId);
			}
		);
	});
	//Duplicate a field
	$('#organizer_form_fields .duplicate_icon').on('click', function(e) {
		e.stopPropagation();
		var fieldId = $(this).data('id');
		if (thus.saveCurrentOpenDetails()) {
			var field = thus.getItem('field', fieldId);
			message = '<p>Are you sure you want to duplicate the field "' + field.name + '"?</p>';
			zenarioA.floatingBox(message, 'Duplicate', 'warning', true, false, function() {
				var newFieldId = thus.createField(undefined, field.ord + 0.1, undefined, fieldId);
				thus.clickField(newFieldId, true);
				thus.updateFieldOrds();
			});
		}
	});
	
	//Update a dataset repeat field
	$('#organizer_form_fields .update_repeat_field_icon').on('click', function(e) {
		e.stopPropagation();
		var repeatFieldId = $(this).data('id');
		var repeatField = thus.getItem('field', repeatFieldId);
		
		if (!thus.saveCurrentOpenDetails()) {
			return;
		}
		
		message = '<p>Are you sure you want to update this dataset repeat field?</p>';
		zenarioA.floatingBox(message, 'Update', 'warning', true, false, function() {
			
			var datasetRepeatFieldId = repeatField.dataset_field_id;
			var datasetRepeatField = thus.tuix.dataset.fields[datasetRepeatFieldId];
			
			//Add / remove / reorder fields
			var formRepeatFields = [];
			var datasetRepeatFields = [];
			
			//Get form fields inside dataset repeat
			var fields = thus.getOrderedFields(thus.currentPageId);
			for (var i = 0; i < fields.length; i++) {
				if (fields[i].dataset_repeat_grouping == datasetRepeatFieldId && fields[i].type != 'repeat_end' && fields[i].type != 'repeat_start') {
					formRepeatFields.push(fields[i].id);
				}
			}
			
			//Get dataset fields inside dataset repeat
			foreach (thus.tuix.dataset.tabs[datasetRepeatField.tab_name].fields as var datasetFieldId => var x) {
				var datasetField = thus.tuix.dataset.fields[datasetFieldId];
				if (datasetField.repeat_start_id == datasetRepeatFieldId && datasetField.type != 'repeat_end') {
					datasetRepeatFields.push(datasetField);
				}
			}
			datasetRepeatFields.sort(thus.sortByOrd);
			
			//Add new fields and update ordinals
			var ord = repeatField.ord;
			for (var i = 0; i < datasetRepeatFields.length; i++) {
				var found = false;
				for (var j = 0; j < formRepeatFields.length; j++) {
					var formRepeatField = thus.getItem('field', formRepeatFields[j]);
					if (datasetRepeatFields[i].id == formRepeatField.dataset_field_id) {
						thus.tuix.items[formRepeatFields[j]].ord = ord + (datasetRepeatFields[i].ord / 1000);
						found = true;
						break;
					}
				}
				if (!found) {
					datasetRepeatFields[i].dataset_repeat_grouping = datasetRepeatField.id;
					thus.createField(undefined, ord + (datasetRepeatFields[i].ord / 1000), datasetRepeatFields[i].id);
				}
			}
	
			//Remove fields this no longer exist
			for (var i = 0; i < formRepeatFields.length; i++) {
				var found = false;
				for (var j = 0; j < datasetRepeatFields.length; j++) {
					if (thus.tuix.items[formRepeatFields[i]].dataset_field_id == datasetRepeatFields[j].id) {
						found = true;
					}
				}
				if (!found) {
					thus.deleteField(formRepeatFields[i]);
				}
			}
			
			//Update display
			thus.tuix.pages[thus.currentPageId].fields_reordered = true;
			thus.loadFieldsList(thus.currentPageId);
			thus.updateFieldOrds();
			thus.changeMadeToPanel();
		});
	});
};

methods.updateFieldOrds = function() {	
	$('#organizer_form_fields div.form_field').each(function(i) {
		var fieldId = $(this).data('id');
		if (fieldId) {
			thus.tuix.items[fieldId].ord = (i + 1);
		}
	});
	thus.changeMadeToPanel();
};

methods.loadNewFieldsPanel = function(stopAnimation) {
	thus.editingThing = false;
	
	//Load HTML
	var mergeFields = {
		mode: 'new_fields',
		link_to_dataset: thus.tuix.link_to_dataset,
		datasetTabs: thus.getOrderedMergeFieldsForDatasetFields()
	};
	
	var html = thus.microTemplate('zenario_organizer_form_builder_left_panel', mergeFields);
	var $div = $('#organizer_form_builder .form_fields_palette .form_fields_palette_outer').html(html);
	
	if (!stopAnimation) {
		$div.hide().show('drop', {direction: 'right'}, 200);
	}
	
	//Add events
	
	//Allow fields to be dragged onto list
	$('#organizer_field_type_list div.field_type, #organizer_centralised_field_type_list div.field_type, #organizer_linked_field_type_list div.dataset_field').draggable({
		connectToSortable: '#organizer_form_fields .form_section',
		appendTo: '#organizer_form_builder',
		helper: 'clone'
	});
	
	//Edit form settings
	$('input.form_settings').on('click', function() {
		thus.openFormSettings();
	});
};


//Similar to PHP method formatAdminBox
methods.formatTUIX = function(itemType, item, tab, tags, changedFieldId) {
	
	//Visibility selector (available to both pages and fields)
	if (tab == 'details' ) {
		if (item.visibility && item.visibility == 'visible_on_condition' && item.visible_condition_field_id) {
			var conditionField = thus.getItem('field', item.visible_condition_field_id);
			if (conditionField) {
				if (conditionField.type != 'checkboxes' && conditionField.type != 'checkbox' && conditionField.type != 'group') {
					tags.tabs[tab].fields.visible_condition_field_type.values.visible_if_one_of = {label: 'Visible if one of...'};
					tags.tabs[tab].fields.visible_condition_field_value.empty_value = '-- Any value --';
					tags.tabs[tab].fields.visible_condition_field_value.values = conditionField.lov;
				
					if (item.visible_condition_field_type == 'visible_if_one_of') {
						tags.tabs[tab].fields.visible_condition_field_value.hidden = true;
						tags.tabs[tab].fields.visible_condition_checkboxes_field_value.hidden = false;
					}
				}
			
				if (conditionField.type == 'checkboxes') {
					tags.tabs[tab].fields.visible_condition_field_value.hidden = true;
					tags.tabs[tab].fields.visible_condition_checkboxes_operator.hidden = false;
					tags.tabs[tab].fields.visible_condition_checkboxes_field_value.hidden = false;
				}
			
				if (conditionField.type != 'checkbox' && conditionField.type != 'group') {
					tags.tabs[tab].fields.visible_condition_checkboxes_field_value.values = conditionField.lov;
				}
			}
		}
	}
	
	if (itemType == 'page') {
		switch (tab) {
			case 'details':
				//Hide previous/next button fields on first/last pages
				var pages = thus.getOrderedPages();
				for (var i = 0; i < pages.length; i++) {
					if (pages[i].id == item.id) {
						if (i == 0) {
							tags.tabs[tab].fields.previous_button_text.hidden = true;
						} else if (i == (pages.length - 1) && !thus.tuix.form_enable_summary_page) {
							tags.tabs[tab].fields.next_button_text.hidden = true;
							tags.tabs[tab].fields.visibility.readonly = true;
							tags.tabs[tab].fields.visibility.value = 'visible';
						}
						break;
					}
				}
				
				//Reload pages list to get visibility styles
				thus.loadPagesList();
				break;
		}
	} else if (itemType == 'field') {
		switch (tab) {
			case 'details':
				//Show and update values source label
				if (item.values_source && thus.tuix.centralised_lists.values[item.values_source]) {
					var list = thus.tuix.centralised_lists.values[item.values_source];
					if (!list.info.can_filter) {
						tags.tabs[tab].fields.values_source_filter.hidden = true;
					} else {
						tags.tabs[tab].fields.values_source_filter.label = list.info.filter_label;
					}
				}
				
				//Readonly / mandatory selector
				if (item.readonly_or_mandatory && item.readonly_or_mandatory == 'conditional_mandatory' && item.mandatory_condition_field_id) {
					var conditionField = thus.getItem('field', item.mandatory_condition_field_id);
					if (conditionField) {
						if (conditionField.type == 'checkboxes') {
							tags.tabs[tab].fields.mandatory_condition_checkboxes_field_value.values = conditionField.lov;
							tags.tabs[tab].fields.mandatory_condition_field_value.hidden = true;
							tags.tabs[tab].fields.mandatory_condition_checkboxes_operator.hidden = false;
						} else {
							tags.tabs[tab].fields.mandatory_condition_checkboxes_field_value.hidden = true;
							if (conditionField.type != 'checkbox' && conditionField.type != 'group') {
								tags.tabs[tab].fields.mandatory_condition_field_value.values = conditionField.lov;
								tags.tabs[tab].fields.mandatory_condition_field_value.empty_value = '-- Any value --';
							}
						}
					}
				}
				
				//Default validation error messages
				if (changedFieldId == 'field_validation') {
					if (item.field_validation == 'email') {
						tags.tabs[tab].fields.field_validation_error_message.value = 'The email address you have entered is not valid, please enter a valid email address.';
					} else if (item.field_validation == 'URL') {
						tags.tabs[tab].fields.field_validation_error_message.value = 'Please enter a valid URL.';
					} else if (item.field_validation == 'number') {
						tags.tabs[tab].fields.field_validation_error_message.value = 'Please enter a valid number.';
					} else if (item.field_validation == 'integer') {
						tags.tabs[tab].fields.field_validation_error_message.value = 'Please enter a valid integer.';
					} else if (item.field_validation == 'floating_point') {
						tags.tabs[tab].fields.field_validation_error_message.value = 'Please enter a valid floating point number.';
					}
					
					$('#organizer_form_field_' + item.id + ' .form_field_classes .validation').toggle(item.field_validation && item.field_validation != 'none');
					
				} else if (changedFieldId == 'visibility') {
					var title = '';
					if (item.visibility == 'hidden') {
						title = 'Hidden';
					} else if (item.visibility == 'visible_on_condition') {
						title = 'Visible on condition';
					}
					$('#organizer_form_field_' + item.id + ' .form_field_classes .not_visible').toggle(item.visibility != 'visible').prop('title', title);
					
				} else if (changedFieldId == 'values_source') {
					if (item.values_source) {
						actionRequests = {
							mode: 'get_centralised_lov',
							method: item.values_source
						};
						if (thus.tuix.centralised_lists.values[item.values_source].info.can_filter) {
							actionRequests.filter = item.values_source_filter;
						}
						thus.sendAJAXRequest(actionRequests).after(function(lov) {
							item.lov = lov;
							thus.loadFieldValuesListPreview(item.id);
						});
					} else {
						item.lov = {};
						thus.loadFieldValuesListPreview(item.id);
					}
				}
				
				//Update calculation code preview
				if (item.type == 'calculated') {
					if (item.calculation_code) {
						tags.tabs[tab].fields.calculation_code.value = thus.getCalculationCodeDisplay(item.calculation_code);
					}
				}
				
				//Disable some options for dataset fields
				if (item.dataset_field_id) {
					tags.tabs[tab].fields.values_source.readonly = true;
					tags.tabs[tab].fields.values_source_filter.readonly = true;
					tags.tabs[tab].fields.min_rows.readonly = true;
					tags.tabs[tab].fields.max_rows.readonly = true;
				}
				
				if (item.type == 'repeat_start') {
					tags.tabs[tab].fields.label.label = 'Section heading:';
					tags.tabs[tab].fields.label.note_below = 'This will be the heading at the start of the repeating section.';
				}
				break;
				
			case 'advanced':
				if (item.type == 'select') {
					tags.tabs[tab].fields.default_value_options.label = 'Pre-select field:';
				}
				
				//Update default values list for list type fields (defaults to checkbox values)
				if (['radios', 'centralised_radios', 'select', 'centralised_select'].indexOf(item.type) != -1) {
					tags.tabs[tab].fields.default_value_lov.values = JSON.parse(JSON.stringify(item.lov));
				}
				
				tags.tabs[tab].fields.merge_name.snippet.html = '<div><b>Merge name:</b> ' + thus.getFormFieldMergeName(item.id) + '</div>'
				
				break;
			
			case 'values':
				if (item.dataset_field_id && item.type != 'text') {
					tags.tabs[tab].fields.values.readonly = true;
				}
				
				if (item.db_column == 'salutation') {
					for (var i in tags.tabs[tab].fields.suggested_values_source.values) {
						if (i != 'zenario_common_features::getSalutations') {
							delete(tags.tabs[tab].fields.suggested_values_source.values[i]);
						}
					}
					
					tags.tabs[tab].fields.suggested_values_source.note_below = '<a href="' + thus.tuix.link + 'zenario/admin/organizer.php?#zenario__languages/panels/salutations" target="_blank">Manage list of salutations</a>';
				}
				break;
		}
	}
};

//Similar to PHP method validateAdminBox
methods.validateTUIX = function(itemType, item, tab, tags) {
	if (!item.name) {
		tags.tabs[tab].fields.name.error = 'Please enter a name.';
	}
	
	//Visibility selector validation (available to both pages and fields)
	if (tab == 'details') {
		if (item.visibility && item.visibility == 'visible_on_condition') {
			if (!item.visible_condition_field_id) {
				tags.tabs[tab].fields.visible_condition_field_id.error = 'Please select a visible on conditional form field.';
			} else if (!item.visible_condition_field_value) {
				var conditionField = thus.getItem('field', item.visible_condition_field_id);
				if (conditionField && (conditionField.type == 'checkbox' || conditionField.type == 'group')) {
					tags.tabs[tab].fields.visible_condition_field_value.error = 'Please select a visible on condition form field value.';
				}
			}
		}
	}
	
	if (itemType == 'field') {
		switch (tab) {
			case 'details':
				if (item.type == 'checkbox' || item.type == 'group') {
					if (!item.label) {
						tags.tabs[tab].fields.label.error = 'Please enter a label for this checkbox.';
					}
				} else if (item.type == 'repeat_start') {
					if (!item.min_rows) {
						tags.tabs[tab].fields.min_rows.error = 'Please enter the minimum rows.';
					} else if (+item.min_rows != item.min_rows) {
						tags.tabs[tab].fields.min_rows.error = 'Please a valid number for mininum rows.';
					} else if (item.min_rows < 1 || item.min_rows > 10) {
						tags.tabs[tab].fields.min_rows.error = 'Mininum rows must be between 1 and 10.';
					} else if (+item.min_rows > +item.max_rows) {
						tags.tabs[tab].fields.min_rows.error = 'Minimum rows cannot be greater than maximum rows.'
					}
				
					if (!item.max_rows) {
						tags.tabs[tab].fields.max_rows.error = 'Please enter the maximum rows.';
					} else if (+item.max_rows != item.max_rows) {
						tags.tabs[tab].fields.max_rows.error = 'Please a valid number for maximum rows.';
					} else if (item.max_rows < 1 || item.max_rows > 20) {
						tags.tabs[tab].fields.max_rows.error = 'Maximum rows must be between 1 and 20.';
					}
				} else if (item.type == 'restatement') {
					if (!item.restatement_field) {
						tags.tabs[tab].fields.restatement_field.error = 'Please select the field to mirror.';
					}
				} else if (item.type == 'centralised_radios' || item.type == 'centralised_select') {
					if (!item.values_source) {
						tags.tabs[tab].fields.values_source.error = 'Please select a values source.';
					}
				}
				
				if (item.readonly_or_mandatory) {
					if ((item.readonly_or_mandatory == 'mandatory' || item.readonly_or_mandatory == 'conditional_mandatory' || item.readonly_or_mandatory == 'mandatory_if_visible') && !item.required_error_message) {
						tags.tabs[tab].fields.required_error_message.error = 'Please enter an error message when this field is incomplete.';
					}
				
					if (item.readonly_or_mandatory == 'mandatory') {
						if (item.visibility == 'hidden') {
							tags.tabs[tab].fields.readonly_or_mandatory.error = 'A field cannot be mandatory while hidden.';
						} else if (item.visibility == 'visible_on_condition') {
							tags.tabs[tab].fields.readonly_or_mandatory.error = "This field is always mandatory but is conditionally visible. You must change this field to be either visible all the time or conditionally mandatory with the same condition as it's visibility.";
						}
					} else if (item.readonly_or_mandatory == 'conditional_mandatory') {
						if (!item.mandatory_condition_field_id) {
							tags.tabs[tab].fields.mandatory_condition_field_id.error = 'Please select a mandatory on condition form field.';
						} else if (!item.mandatory_condition_field_value) {
							var conditionField = thus.getItem('field', item.mandatory_condition_field_id);
							if (conditionField && ['checkbox', 'group'].indexOf(conditionField.type) != -1) {
								tags.tabs[tab].fields.mandatory_condition_field_value.error = 'Please select a mandatory on condition form field value.';
							}
						}
					
						if (item.visibility == 'hidden') {
							tags.tabs[tab].fields.visibility.error = 'A field cannot be mandatory while hidden.';
						} else if (item.visibility == 'visible_on_condition' && item.readonly_or_mandatory == 'conditional_mandatory'
							&& item.visible_condition_field_id && item.mandatory_condition_field_id
							&& ((item.visible_condition_field_id != item.mandatory_condition_field_id)
								|| item.visible_condition_field_type == 'visible_if' && item.mandatory_condition_field_type == 'mandatory_if_not'
								|| item.visible_condition_field_type == 'visible_if_not' && item.mandatory_condition_field_type == 'mandatory_if'
								|| ((conditionField = thus.getItem('field', item.mandatory_condition_field_id))
									&& (conditionField.type != 'checkboxes'
										&& (item.visible_condition_field_value != item.mandatory_condition_field_value)
									)
									|| (conditionField.type == 'checkboxes'
										&& (item.visible_condition_checkboxes_operator != item.mandatory_condition_checkboxes_operator
											|| !_.isEqual(item.visible_condition_checkboxes_field_value, item.mandatory_condition_checkboxes_field_value)
										)
									)
								)
							)
						) {
							tags.tabs[tab].fields.visibility.error = 'A field cannot be mandatory while hidden. If this field is both mandatory and visible on a condition, both fields and values must be the same.';
						}
					}
				}
				
				if (item.field_validation && item.field_validation != 'none') {
					if (!item.field_validation_error_message) {
						tags.tabs[tab].fields.field_validation_error_message.error = 'Please enter a validation error message for this field.';
					}
				}
				
				if (item.type == 'text' && item.field_validation && ['number', 'integer', 'floating_point'].indexOf(item.field_validation) == -1) {
					var formFields = thus.getOrderedFields(); 
					for (var i = 0; i < formFields.length; i++) {
						var field = formFields[i];
						if (field.type == 'calculated' && field.calculation_code) {
							for (j = 0; j < field.calculation_code.length; j++) {
								if (field.calculation_code[j].type == 'field' && field.calculation_code[j].value == item.id) {
									tags.tabs[tab].fields.field_validation.error = 'The field "' + field.name + '" requires this field to be numeric. You must first remove this from this field.';
									break;
								}
							}
						}
					}
				}
				break;
			
			case 'values':
				if (item.enable_suggested_values) {
					if (item.suggested_values_type == 'pre_defined' && !item.suggested_values_source) {
						tags.tabs[tab].fields.suggested_values_source.error = 'Please select a values source.';
					} else if (item.suggested_values_type == 'custom' && (!item.lov || Object.keys(item.lov).length == 0)) {
						tags.tabs[tab].fields.values.error = 'Please add at least one value.';
					}
				}
				break;
			
			case 'advanced':
				if (item.default_value_options) {
					if (item.default_value_options == 'value') {
						if (!item.default_value_lov && !item.default_value_text) {
							field.default_value.error = 'Please enter a default value.';
						}
					} else if (item.default_value_options == 'method') {
						if (!item.default_value_class_name) {
							field.default_value_class_name.error = 'Please enter a class name.';
						}
						if (!item.default_value_method_name) {
							field.default_value_method_name.error = 'Please enter the name of a static method.';
						}
					}
				}
				
				//Custom code names must be unique
				if (item.custom_code_name) {
					var formFields = thus.getOrderedFields();
					for (var i = 0; i < formFields.length; i++) {
						var field = formFields[i];
						if (field.id != item.id && (item.custom_code_name == field.custom_code_name)) {
							tags.tabs[tab].fields.custom_code_name.error = 'Another field already has this code name on this form.';
						}
					}
				}
				
				if (item.invalid_responses && item.invalid_responses.length) {
					if (!item.invalid_field_value_error_message) {
						tags.tabs[tab].fields.invalid_field_value_error_message.error = 'Please enter an error message when an invalid response is chosen.';
					}
				}
				
				if (item.type == 'textarea') {
					if (item.word_count_max) {
						if (+item.word_count_max != item.word_count_max) {
							tags.tabs[tab].fields.word_count_max.error = 'Please enter a valid number for the max word count.';
						} else if (item.word_count_max < 1) {
							tags.tabs[tab].fields.word_count_max.error = 'Word count must be greater than 0.';
						}
					}
					if (item.word_count_min) {
						if (+item.word_count_min != item.word_count_min) {
							tags.tabs[tab].fields.word_count_min.error = 'Please enter a valid number for the min word count.';
						} else if (item.word_count_min < 1) {
							tags.tabs[tab].fields.word_count_min.error = 'Word count must be greater than 0.';
						}
					}
				}
				break;
			
			case 'crm':
				if (item.send_to_crm && !item.field_crm_name) {
					tags.tabs[tab].fields.field_crm_name.error = 'Please enter a CRM field name.';
				}
				break;
		}
	}
};

//Values are saved automatically on client and then passed to server to save to the database
//methods.saveTUIX = function() {};


methods.getTUIXFieldCustomValues = function(type) {
	var values = {};
	//A list of centralised lists
	if (type == 'centralised_lists') {
		values = JSON.parse(JSON.stringify(thus.tuix.centralised_lists.values));
	//Lists of fields on this form that meet a certain criteria
	} else if (type == 'centralised_list_filter_fields' || type == 'conditional_fields' || type == 'mirror_fields') {
		var pages = thus.getOrderedPages();
		var useOptGroups = pages.length > 1;
		var ord = 0;
		for (var i = 0; i < pages.length; i++) {
			var page = pages[i];
			var fields = thus.getOrderedFields(pages[i].id);
			for (var j = 0; j < fields.length; j++) {
				var field = fields[j];
				if (!thus.canAddFieldToList(type, field.id)) {
					continue;
				}
				
				values[field.id] = {
					label: field.name,
					ord: ++ord
				};
				if (useOptGroups) {
					var parentId = 'page_' + page.id;
					values[field.id].parent = parentId;
					if (!values[parentId]) {
						values[parentId] = {
							label: page.name,
							ord: ++ord
						};
					}
				}
			}
		}
	//A list of this fields values
	} else if (type == 'field_lov') {
		if (thus.editingThing == 'field' && thus.editingThingId) {
			var field = thus.getItem(thus.editingThing, thus.editingThingId);
			if (field && field.lov) {
				values = JSON.parse(JSON.stringify(field.lov));
			}
		}
	}
	return values;
};

methods.canAddFieldToList = function(type, fieldId) {
	var field = thus.tuix.items[fieldId];
	if (type == 'centralised_list_filter_fields') {
		return (thus.editingThing != 'field' || field.id != thus.editingThingId) && (field.type == 'centralised_select' || (field.type == 'text' && field.enable_suggested_values && field.suggested_values == 'pre_defined' && field.suggested_values_source));
	} else if (type == 'conditional_fields') {
		return (thus.editingThing != 'field' || field.id != thus.editingThingId) && (['checkbox', 'group', 'radios', 'select', 'centralised_radios', 'centralised_select', 'checkboxes'].indexOf(field.type) != -1);
	} else if (type == 'mirror_fields') {
		return (thus.editingThing != 'field' || field.id != thus.editingThingId) && (['text', 'calculated', 'select', 'centralised_select'].indexOf(field.type) != -1);
	}
	return false;
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
				//Save name field
				item.name = $('#field__name').val().trim();
		
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

methods.getDeleteErrors = function(itemType, itemId) {
	var errors = [];
	if (itemType == 'field') {
		var fieldToDelete = thus.getItem(itemType, itemId);
		var fields = thus.getOrderedFields();
		for (var i = 0; i < fields.length; i++) {
			var field = fields[i];
			var required = false;
			
			//Check if this field is used in a calculated field
			if (field.type == 'calculated' && field.calculation_code) {
				for (var j = 0; j < field.calculation_code.length; j++) {
					var step = field.calculation_code[j];
					if (step.type == 'field' && step.value == itemId) {
						required = true;
						break;
					}
				}
			}
			
			//Check if this field is used in a conditional
			if ((field.visibility && field.visibility == 'visible_on_condition' && field.visible_condition_field_id == itemId) || (field.readonly_or_mandatory && field.readonly_or_mandatory == 'conditional_mandatory' && field.mandatory_condition_field_id == itemId)
			) {
				required = true;
			}
			
			if (required) {
				errors.push('Unable to delete field because the field "' + field.name + '" depends on it.');
			}
			
		}
	}
	return errors;
};

methods.displayPageFieldOrderErrors = function() {
	if (!thus.getItem('page', thus.currentPageId)) {
		return true;
	}
	
	var errors = [];
	var fields = thus.getOrderedFields(thus.currentPageId);
	var fieldsAllowedInRepeatBlock = [
		//Valid form field types
		'checkbox', 
		'checkboxes', 
		'date', 
		'radios', 
		'centralised_radios', 
		'select', 
		'centralised_select', 
		'text', 
		'textarea', 
		'url', 
		'attachment', 
		'section_description', 
		'calculated',
		//Valid dataset field types not included above
		'group'
	];
	
	var inRepeatBlock = false;
	for (var i = 0; i < fields.length; i++) {
		field = fields[i];
		if (field.type == 'repeat_start' && !inRepeatBlock) {
			inRepeatBlock = true;
		} else if (field.type == 'repeat_end') {
			//Validate repeat end positioning
			if (inRepeatBlock) {
				inRepeatBlock = false;
			} else {
				errors.push('Repeat ends must be placed after repeat starts.');
			}
		} else {
			//Validate field types in repeat block
			if (inRepeatBlock && fieldsAllowedInRepeatBlock.indexOf(field.type) == -1) {
				errors.push('Field type "' + thus.getFieldReadableType(field).toLowerCase() + '" is not allowed in a repeat block.');
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

methods.getTUIXModeForItemType = function(itemType) {
	if (itemType == 'page') {
		return 'form_page_details';
	} else if (itemType == 'field') {
		return 'form_field_details';
	} else {
		return false;
	}
};

methods.getFieldReadableType = function(item) {
	if (typeof item == 'string') {
		var type = item;
	} else {
		var type = item.type;
		if (item.is_consent) {
			type = 'consent'
		}
	}
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
			return 'Page';
		case 'section_description':
			return 'Subheading';
		case 'calculated':
			return 'Calculated';
		case 'restatement':
			return 'Mirror';
		case 'repeat_start':
			return 'Start of repeating section';
		case 'repeat_end':
			return 'End of repeating section';
		case 'document_upload':
			return 'Multi-upload';
		default:
			return 'Unknown';
		
		//Types unique to dataset fields
		case 'group':
			return 'Group';
		case 'consent':
			return 'Consent';
		case 'file_picker':
			return 'File picker';
	}
};

methods.getCalculationCodeDisplay = function(calculationCode) {
	var calculationDisplay = '';
	if (calculationCode) {
		var lastIsParenthesisOpen = false;
		for (var i = 0; i < calculationCode.length; i++) {
			if (!lastIsParenthesisOpen && calculationCode[i].type != 'parentheses_close') {
				calculationDisplay += ' ';
			} else if (lastIsParenthesisOpen) {
				lastIsParenthesisOpen = false;
			}
			
			switch (calculationCode[i].type) {
				case 'operation_addition':
					calculationDisplay += '+';
					break;
				case 'operation_subtraction':
					calculationDisplay += '-';
					break;
				case 'operation_multiplication':
					calculationDisplay += '×';
					break;
				case 'operation_division':
					calculationDisplay += '÷';
					break;
				case 'parentheses_open':
					calculationDisplay += '(';
					lastIsParenthesisOpen = true;
					break;
				case 'parentheses_close':
					calculationDisplay += ')';
					break;
				case 'static_value':
					calculationDisplay += calculationCode[i].value;
					break;
				case 'field':
					var name = '';
					var field = thus.getItem('field', calculationCode[i].value);
					if (field) {
						name = field.name;
					} else {
						name = 'UNKNOWN FIELD';
					}
					calculationDisplay += '"' + name + '"';
					break;
			}
			calculationDisplay = calculationDisplay.trim();
		}
	}
	
	return calculationDisplay;
};

//Calculation admin box methods
methods.calculationAdminBoxAddSomthing = function(type, value) {
	var code = false;
	switch (type) {
		case 'operation_addition':
		case 'operation_subtraction':
		case 'operation_multiplication':
		case 'operation_division':
		case 'parentheses_open':
		case 'parentheses_close':
			code = {type: type};
			break;
		case 'static_value':
			if (value !== '' && !isNaN(+value)) {
				code = {type: type, value: +value};
				$('#static_value').val('');
			}
			break;
		case 'field':
			code = {type: type, value: value};
			$('#numeric_field').val('');
			break;
	}
	if (code) {
		var calculationCode = [];
		if ($('#calculation_code').val()) {
			calculationCode = JSON.parse($('#calculation_code').val());
		}
		calculationCode.push(code);
		$('#calculation_code').val(JSON.stringify(calculationCode));
		
		thus.calculationAdminBoxUpdateDisplay(calculationCode);
	}
};
methods.calculationAdminBoxDelete = function() {
	var calculationCode = [];
	if ($('#calculation_code').val()) {
		calculationCode = JSON.parse($('#calculation_code').val());
		if (calculationCode) {
			calculationCode.pop();
			$('#calculation_code').val(JSON.stringify(calculationCode));
		}
	}
	thus.calculationAdminBoxUpdateDisplay(calculationCode);
};
methods.calculationAdminBoxUpdateDisplay = function(calculationCode) {
	var calculationDisplay = thus.getCalculationCodeDisplay(calculationCode);
	$('#zenario_calculation_display').text(calculationDisplay);
};


methods.openFormSettings = function() {
	zenarioAB.open(
		'zenario_user_form', 
		{id: zenarioO.pi.refiner.id},
		undefined, undefined,
		function(key, values) {
			//Update form title (note if lots of changes might be better to redraw entire form)
			var title = '';
			if (values.details.show_title) {
				title = values.details.title;
			}
			thus.tuix.title = title;
			$('#organizer_form_builder .form_outer .form_header h5').text(title);
		}
	);
};

methods.changeMadeToPanel = function() {	
	if (!thus.changeMadeOnPanel) {
		thus.changeMadeOnPanel = true;
		window.onbeforeunload = function() {
			return 'You are currently editing this form. If you leave now you will lose any unsaved changes.';
		}
		var warningMessage = 'Please either save your changes, or click Reset to discard them, before exiting the form editor.';
		zenarioO.disableInteraction(warningMessage);
		zenarioO.setButtons();
	}
};


methods.saveChanges = function() {
	//Show warning
	if (thus.tuix.not_used_or_on_public_page) {
		var hasNonEmailDatasetFields = false;
		var hasEmailDatasetField = false;
		var fields = thus.getOrderedFields();
		for (var i = 0; i < fields.length; i++) {
			if (fields[i].dataset_field_id) {
				if (fields[i].db_column == 'email') {
					hasEmailDatasetField = true;
				} else {
					hasNonEmailDatasetFields = true;
				}
			}
		}
				
		if (hasNonEmailDatasetFields && !hasEmailDatasetField && !confirm("Warning: this form contains fields that are linked to the Users Dataset, but doesn\'t contain the Email field from the Users Dataset.\n\nIf this form is used on a public web page by anonymous visitors, form responses will not be stored in the Users & Contacts database table.\n\nTo overcome this, please add the Email field linked to the Users Dataset.\n\nSave anyway?")) {
			return;
		}
	}
	
	var actionRequests = {
		mode: 'save',
		pages: JSON.stringify(thus.tuix.pages),
		fields: JSON.stringify(thus.tuix.items),
		fieldsTUIX: JSON.stringify(thus.tuix.form_field_details),
		pagesReordered: thus.pagesReordered,
		deletedPages: JSON.stringify(thus.deletedPages),
		deletedFields: JSON.stringify(thus.deletedFields),
		deletedValues: JSON.stringify(thus.deletedValues),
		currentPageId: thus.currentPageId,
		editingThing: thus.editingThing,
		editingThingId: thus.editingThingId
	};
	
	zenarioA.nowDoingSomething('saving', true);
	
	thus.sendAJAXRequest(actionRequests).after(function(info) {
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

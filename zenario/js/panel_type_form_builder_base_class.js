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
	panelTypes.form_builder_base_class = extensionOf(panelTypes.base)
);

//Custom methods

methods.sortByOrd = function(a, b) {
	if (a.ord < b.ord) 
		return -1;
	if (a.ord > b.ord)
		return 1;
	return 0;
};

// Send an AJAX request
methods.sendAJAXRequest = function(requests) {
	var that = this,
		actionRequests = zenarioO.getKey(),
		actionTarget = 
		'zenario/ajax.php?' +
			'__pluginClassName__=' + this.tuix.class_name +
			'&__path__=' + zenarioO.path +
			'&method_call=handleOrganizerPanelAJAX';
	
	$.extend(actionRequests, requests);
	
	get('organizer_preloader_circle').style.display = 'block';
	var result = zenario.ajax(
		URLBasePath + actionTarget,
		actionRequests
	).after(function() {
		get('organizer_preloader_circle').style.display = 'none';
	});
	return result;
};

methods.sortErrors = function(errors) {
	var sortedErrors = [];
	foreach (errors as var i => var error) {
		sortedErrors.push(error);
	}
	return sortedErrors;
};

methods.loadFields = function(path, fields, values) {
	var loadedFields = {};
	foreach (fields as var i => var _field) {
		if (_field) {
			var field = _.clone(_field);
			field.id = i;
			field.path = path;
			
			if (field.value && (!defined(values[i]))) {
				values[i] = field.value;
			}
			
			//Get value
			if (values && defined(values[i])) {
				field._value = values[i];
			}
			
			if (field.hidden) {
				field._hidden = true;
			}
			loadedFields[i] = field;
		}
	}
	return loadedFields;
};

methods.sortFields = function(fields, item) {
	var sortedFields = [];
	
	foreach (fields as var i => var field) {
		//Sort field value list
		if (field.type == 'select' || field.type == 'radios' || field.type == 'checkboxes') {
			field._values = this.getFieldValuesList(field);
			if (field.empty_value) {
				field._values.unshift({
					ord: 0,
					label: field.empty_value,
					value: ''
				});
			}	
		}
		
		if (field.visible_if && typeof(field._hidden) === 'undefined') {
			if (!zenarioT.doEval(field.visible_if, undefined, undefined, item)) {
				field._hidden = true;
			}
		}
		if (field.readonly_if && typeof(field._readonly) === 'undefined') {
			if (zenarioT.doEval(field.readonly_if, undefined, undefined, item)) {
				field._readonly = true;
			}
		}
		
		sortedFields.push(field);
	}
	return sortedFields;
};

methods.saveFieldListOfValues = function(field) {
	if (field) {
		$('#field_values_list div.field_value').each(function(i, value) {
			var id = $(this).data('id');
			if (!field.lov) {
				field.lov = {};
			}
			if (field.lov[id]) {
				field.lov[id].id = id;
				field.lov[id].label = $(value).find('input').val();
				field.lov[id].ord = i + 1;
			}
		});
	}
};

methods.getTUIXFieldsHTML = function(fields) {
	var html = '';
	for (var i = 0; i < fields.length; i++) {
		html += this.getTUIXFieldHTML(fields[i]);
	}
	return html;
};
methods.getTUIXFieldHTML = function(field) {
	var html = this.microTemplate('zenario_organizer_admin_box_builder_tuix_field', field);
	return html;
};


methods.sortAndLoadTUIXPages = function(pages, item, selectedPage) {
	var sortedPages = [];
	var selected = false;
	foreach (pages as var i => var page) {
		if (page) {
			page.id = i;
			if (page.visible_if) {
				if (!zenarioT.doEval(page.visible_if, undefined, undefined, item)) {
					continue;
				}
			}
			if (!selected && (!selectedPage || (selectedPage == i))) {
				selected = true;
				page._selected = true;
				this.selectedDetailsPage = i;
			}
			sortedPages.push(page);
		}
	}
	return sortedPages;
};

methods.getTUIXPagesHTML = function(pages) {
	var html = '';
	for (var i = 0; i < pages.length; i++) {
		html += this.getTUIXPageHTML(pages[i]);
	}
	return html;
};
methods.getTUIXPageHTML = function(page) {
	var html = this.microTemplate('zenario_organizer_admin_box_builder_tuix_tab', page);
	return html;
};


methods.saveItemTUIXFields = function(item, fields, tuixPath, errors) {
	foreach (fields as var prop => var field) {
		if ($('#tuix_field__' + prop).length) {
			if (field.type == 'text' || field.type == 'select' || field.type == 'textarea') {
				item[prop] = $('#field__' + prop).val();
			} else if (field.type == 'checkbox') {
				item[prop] = $('#field__' + prop).is(':checked');
			} else if (field.type == 'radios') {
				item[prop] = $('#organizer_field_details_form input[name="' + prop + '"]:checked').val();
			} else if (field.type == 'checkboxes') {
				item[prop] = [];
				$('#organizer_field_details_form input[name="' + prop + '"]:checked').each(function() {
					item[prop].push($(this).val());
				});
			} else if (field.type == 'values_list') {
				this.saveFieldListOfValues(item);
			} else if (field.type == 'translations') {
				item._translations = {};
				$('#organizer_field_translations input.translation').map(function(index, input) {
					var languageId = $(this).data('language_id');
					var fieldName = $(this).data('field_column');
					if (!item._translations[fieldName]) {
						item._translations[fieldName] = {phrases: {}};
					}
					item._translations[fieldName].phrases[languageId] = input.value;
				});
				
			} else if (field.type == 'crm_values') {
				var prefix = 'crm';
				if (field.crm_type == 'salesforce') {
					prefix = 'salesforce';
				}
				if (item.type == 'checkbox' || item.type == 'group') {
					item['_' + prefix + '_data'] = {};
					item['_' + prefix + '_data'].values = {
						'unchecked': {
							ord: 1,
							label: 0
						},
						'checked': {
							ord: 2,
							label: 1
						}
					};
				}
				$('#organizer_field_crm_values input.crm_value_input').map(function(index, input) {
					var id = $(this).data('id');
					if (item.type == 'checkbox' || item.type == 'group') {
						item['_' + prefix + '_data'].values[id][prefix + '_value'] = $(this).val();
					} else if (item.lov && item.lov[id]) {
						item.lov[id][prefix + '_value'] = $(this).val();
					}
				});
			}
		
			if (field.validation && field.validation.required) {
				if (!item[prop]) {
					errors[prop] = field.validation.required;
				}
			}
		}
	}
	
	this.validateFieldDetails(fields, item, tuixPath, this.selectedDetailsPage, errors);
};


methods.saveItemTUIXPage = function(tuixPath, page, item) {
	var errors = {};
	if (this.tuix[tuixPath] && this.tuix[tuixPath].tabs && this.tuix[tuixPath].tabs[page]) {
		var fields = this.tuix[tuixPath].tabs[page].fields;
		this.saveItemTUIXFields(item, fields, tuixPath, errors);
		item._changed = true;
	}
	return errors;
};

methods.addFieldValue = function(item, label, ord) {
	if (!label) {
		label = 'Untitled';
	}
	if (typeof item.lov === 'undefined') {
		item.lov = {};
	}
	newValueId = 't' + this.maxNewCustomFieldValue++;
	item.lov[newValueId] = {
		id: newValueId,
		label: label,
		ord: ord || _.size(item['lov']) + 100,
		_is_new: true
	};
};

methods.getPageLabel = function(label) {
	if (label && label.trim() === '') {
		label = 'Untitled';
	}
	return label;
};

methods.getMatchingRepeatEnd = function(fieldId) {
	var repeatEndId = false,
		fieldIndex = false,
		pageId = this.currentPageId,
		fields = this.getOrderedFields(pageId),
		i;
	for (i = 0; i < fields.length; i++) {
		if (fields[i].type == 'repeat_end') {
			repeatEndId = fields[i].id;
		}
		if (fields[i].id == fieldId) {
			fieldIndex = i;
		} else if (fieldIndex !== false && fields[i].type == 'repeat_end') {
			repeatEndId = fields[i].id;
			break;
		}
	}
	return repeatEndId;
};


//Draw (or hide) the button toolbar
//This is called every time different items are selected, the panel is loaded, refreshed or when something in the header toolbar is changed.
methods.showButtons = function($buttons) {
	var that = this;
	
	if (this.changeDetected) {
		//Change the buttons to apply/cancel buttons
		var mergeFields = {
			confirm_text: 'Save changes',
			confirm_css: 'form_editor',
			cancel_text: 'Reset'
		};
		$buttons.html(this.microTemplate('zenario_organizer_apply_cancel_buttons', mergeFields));
		
		//Add an event to the Apply button to save the changes
		$buttons.find('#organizer_applyButton')
			.click(function() {
				var errors = that.saveCurrentOpenDetails();
				if (!errors) {
					errors = that.displayPageFieldStructureErrors(that.currentPageId);
					if (!errors) {
						that.saveChanges();
					}
				}
			});
		
		$buttons.find('#organizer_cancelButton')
			.click(function() {
				if (confirm('Are you sure you want to discard all your changes?')) {
					window.onbeforeunload = false;
					zenarioO.enableInteraction();
					
					that.selectedFieldId = false;
					that.selectedPageId = false;
					that.currentPageId = false;
					
					that.changeDetected = false;
					zenarioO.reload();
				}
			});
		
	} else {
		//Remove the buttons, but don't actually hide them as we want to keep some placeholder space there
		$buttons.html('').show();
	}
};

methods.displayPageFieldStructureErrors = function() {
	return false;
};


// Base methods

//Called whenever Organizer has saved an item and wants to display a toast message to the administrator
methods.displayToastMessage = function(message, itemId) {
	//Do nothing, don't show the message!
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

//Never show the left hand nav; always show this panel using the full width
methods.returnShowLeftColumn = function() {
	return false;
};

//Use this function to set AJAX URL you want to use to load the panel.
//Initally the this.tuix variable will just contain a few important TUIX properties
//and not your the panel definition from TUIX.
//The default value here is a PHP script that will:
	//Load all of the TUIX properties
	//Call your preFillOrganizerPanel() method
	//Populate items from the dapagease if you set the db_items property in TUIX
	//Call your fillOrganizerPanel() method
//You can skip these steps and not do an AJAX request by returning false instead,
//or do something different by returning a URL to a different PHP script
methods.returnAJAXURL = function() {
	return URLBasePath
		+ 'zenario/admin/organizer.ajax.php?path='
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

//Sets the title shown above the panel.
//This is also shown in the back button when the back button would take you back to this panel.
methods.returnPanelTitle = function() {
	return methodsOf(panelTypes.grid).returnPanelTitle.call(this);
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


//Return whether you are allowing multiple items to be selected in full and quick mode.
//(In select mode the opening picker will determine whether multiple select is allowed.)
methods.returnMultipleSelectEnabled = function() {
	return false;
};


//Whether to enable searching on a panel
methods.returnSearchingEnabled = function() {
	return false;
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

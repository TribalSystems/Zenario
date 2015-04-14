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
	panelTypes.base = extensionOf(undefined, function() {
		this.tuix = {};
		this.scrollTop = 0;
		this.scrollLeft = 0;
		this.searchTerm = '';
		this.selectedItems = {};
	})
);


//Methods

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
	return '';
};

//Whether to enable searching on a panel
methods.returnSearchingEnabled = function() {
	
	//Look to see if there's a column marked as searchable, and if so return true
	if (this.tuix && this.tuix.columns) {
		foreach (this.tuix.columns as var c) {
			if (!zenarioO.isInfoTag(c) && engToBoolean(this.tuix.columns[c].searchable)) {
				return true;
			}
		}
	}
	
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

//Return whether you want searching/sorting/pagination to be done server-side.
//If you return true, sorting and pagination will be applied on the server.
//If you return false, your sortAndSearchItems() method will be called instead.
methods.returnDoSortingAndSearchingOnServer = function() {
	return this.tuix.db_items && !engToBoolean(this.tuix.db_items.client_side);
};

//Function to search and sort the items on the client side.
//You should return an array of matching ids in the correct order.
methods.sortAndSearchItems = function() {
	return this.searchItems(this.sortItems(), this.searchTerm);
};

//Part one of sortAndSearchItems(), this is broken up into two halves for easier overriding
methods.sortItems = function() {
	zenarioO.createSortArray('sortedItems', 'items', zenarioO.sortBy, zenarioO.sortDesc);
	return zenarioO.sortedItems;
};

//Part two of sortAndSearchItems(), this is broken up into two halves for easier overriding
methods.searchItems = function(items, searchTerm) {
	
	if (searchTerm !== undefined) {
		var lowerCaseSearchTerm = searchTerm.toLowerCase(),
			itemNo, id, c;
		
		for (itemNo = items.length - 1; itemNo >= 0; --itemNo) {
			id = items[itemNo];
			
			matches = false;
			foreach (this.tuix.columns as c) {
				if (!zenarioO.isInfoTag(c) && engToBoolean(this.tuix.columns[c].searchable)) {
					value = zenarioO.columnValue(id, c, true);
					
					if (value == (numeric = 1*value)) {
						matches = numeric == searchTerm;
					} else if (value) {
						matches = value.toLowerCase().indexOf(lowerCaseSearchTerm) !== -1;
					}
					
					if (matches) {
						break;
					}
				}
			}
			
			if (!matches) {
				items.splice(itemNo, 1);
			}
		}
	}
	
	return items;
};


//Draw the panel, as well as the header at the top and the footer at the bottom
//This is called every time the panel is loaded, refreshed or when something in the header toolbar is changed.
methods.showPanel = function($header, $panel, $footer) {
	$header.html('').show();
	$panel.html('').show();
	$footer.html('').show();
	//...
};

//Draw (or hide) the button toolbar
//This is called every time different items are selected, the panel is loaded, refreshed or when something in the header toolbar is changed.
methods.showButtons = function($buttons) {
	$buttons.html('').show();
	//...
};

//This is called when an admin navigates away from your panel, or your panel is about to be refreshed/reloaded.
methods.onUnload = function($header, $panel, $footer) {
	//Remember where the admin had scrolled to.
	//If we ever draw this panel again it would be nice to restore this to how it was
	this.scrollTop = $panel.scrollTop();
	this.scrollLeft = $panel.scrollLeft();
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
	var $checkbox,
		checkbox = get('organizer_itemcheckbox_' + id);
	
	if (checkbox) {
		$checkbox = $(get('organizer_itemcheckbox_' + id));
	
		//setTimeout() is used as a workaround for a bug where a checkbox's checked
		//property can get out of sync when clicking directly on it
		setTimeout(function() {
			$checkbox.prop('checked', checked);
		}, 1);
	}
	
	//Change the "all items selected" checkbox, if it is on the page.
	if (zenarioO.allItemsSelected()) {
		$('#organizer_checkbox_col input').prop('checked', true);
	} else {
		$('#organizer_checkbox_col input').prop('checked', false);
	}
};




}, zenarioO.panelTypes);
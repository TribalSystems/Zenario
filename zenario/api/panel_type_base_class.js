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
	panelTypes.base = extensionOf(undefined, function() {
		this.tuix = {};
		this.scrollTop = 0;
		this.scrollLeft = 0;
		this.searchTerm = '';
		this.selectedItems = {};
		this.lastItemClicked = false;
	})
);


//Methods

//Called by Organizer upon the first initialisation of this panel.
//It is not recalled if Organizer's refresh button is pressed, or the administrator changes page
methods.init = function() {
};

//Called by Organizer whenever it needs to set the panel data.
methods.cmsSetsPanelTUIX = function(tuix) {
	thus.tuix = tuix;
};

//Called by Organizer whenever it needs to set the current tag-path
methods.cmsSetsPath = function(path) {
	thus.path = path;
};

//Called by Organizer to give information on the current refiner
methods.cmsSetsRefiner = function(refiner) {
	thus.refiner = refiner;
};

//Called by Organizer whenever a panel is first loaded with a specific item requested
methods.cmsSetsRequestedItem = function(requestedItem) {
	thus.lastItemClicked =
	thus.requestedItem = requestedItem;
};

//Called by Organizer to set the sort column and direction
methods.cmsSetsSortColumn = function(sortBy, sortDesc) {
	thus.sortBy = sortBy;
	thus.sortDesc = sortDesc;
};


//If searching is enabled (i.e. your returnSearchingEnabled() method returns true)
//then the CMS will call this method to tell you what the search term was
methods.cmsSetsSearchTerm = function(searchTerm) {
	thus.searchTerm = searchTerm;
};

//Use this function to set AJAX URL you want to use to load the panel.
//Initally the thus.tuix variable will just contain a few important TUIX properties
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
		+ 'zenario/admin/organizer.ajax.php?path='
		+ encodeURIComponent(thus.path)
		+ zenario.urlRequest(thus.returnAJAXRequests());
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
	
	var pageSize = (zenarioO.prefs[thus.path] && zenarioO.prefs[thus.path].pageSize);
	
	return Math.max(20, Math.min(500, 1*pageSize || zenarioO.defaultPageSize));
};

//Sets the title shown above the panel.
//This is also shown in the back button when the back button would take you back to this panel.
methods.returnPanelTitle = function() {
	return '';
};

//Return whether you are allowing multiple items to be selected in full and quick mode.
//(In select mode the opening picker will determine whether multiple select is allowed.)
methods.returnMultipleSelectEnabled = function() {
	if (thus.tuix && thus.tuix.item_buttons) {
		foreach (thus.tuix.item_buttons as var i => var button) {
			if (button
			 && engToBoolean(button.multiple_select)
			 && !zenarioO.checkButtonHidden(button)) {
				return true;
			}
		}
	}
	
	return false;
};


//Whether to enable searching on a panel
methods.returnSearchingEnabled = function() {
	
	//Look to see if there's a column marked as searchable, and if so return true
	if (thus.tuix && thus.tuix.columns) {
		foreach (thus.tuix.columns as var c => var column) {
			if (column && engToBoolean(column.searchable)) {
				return true;
			}
		}
	}
	
	return false;
};

//Return whether you want searching/sorting/pagination to be done server-side.
//If you return true, sorting and pagination will be applied on the server.
//If you return false, your sortAndSearchItems() method will be called instead.
methods.returnDoSortingAndSearchingOnServer = function() {
	return thus.tuix.db_items && !engToBoolean(thus.tuix.db_items.client_side);
};

//Return whether to show the left hand nav.
//By default, if this is full mode then show the left hand nav, and if not, then hide it.
methods.returnShowLeftColumn = function() {
	return !window.zenarioONotFull;
};

methods.returnIsMultiLineList = function() {
	return false;
};

//Function to search and sort the items on the client side.
//You should return an array of matching ids in the correct order.
methods.sortAndSearchItems = function() {
	return thus.searchItems(thus.sortItems(), thus.searchTerm);
};

//Part one of sortAndSearchItems(), this is broken up into two halves for easier overriding
methods.sortItems = function() {
	return zenarioT.getSortedIdsOfTUIXElements(thus.tuix, 'items', thus.sortBy, thus.sortDesc);
};

//Part two of sortAndSearchItems(), this is broken up into two halves for easier overriding
methods.searchItems = function(items, searchTerm) {
	
	if (defined(searchTerm)) {
		var lowerCaseSearchTerm = searchTerm.toLowerCase(),
			itemNo, id, c, column;
		
		for (itemNo = items.length - 1; itemNo >= 0; --itemNo) {
			id = items[itemNo];
			
			matches = false;
			foreach (thus.tuix.columns as c => column) {
				if (column && engToBoolean(column.searchable)) {
					
					if (zenarioO.columnValue) {
						value = zenarioO.columnValue(id, c, true);
					} else {
						value = thus.tuix.items && thus.tuix.items[id] && thus.tuix.items[id][c];
					}
					
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

//Called whenever Organizer is resized - i.e. when the administrator resizes their window.
//It's also called on the first load of your panel after your showPanel() and setButtons() methods have been called.
methods.sizePanel = function($header, $panel, $footer, $buttons) {
	//...
};

//Called whenever Organizer has saved an item and wants to display a toast message to the administrator
methods.displayToastMessage = function(message, itemId) {
	toastr.success(message);
};

//This is called when an admin navigates away from your panel, or your panel is about to be refreshed/reloaded.
methods.onUnload = function($header, $panel, $footer) {
	thus.saveScrollPosition($panel);
};

//Remember where the admin had scrolled to.
//If we ever draw this panel again it would be nice to restore this to how it was
methods.saveScrollPosition = function($panel) {
	$panel = $panel || zenarioO.getPanel();
	
	thus.scrollTop = $panel.scrollTop();
	thus.scrollLeft = $panel.scrollLeft();
};

//If this panel has been displayed before, try to restore the admin's previous scroll
//Otherwise show the top left (i.e. (0, 0))
methods.restoreScrollPosition = function($panel) {
	$panel = $panel || zenarioO.getPanel();
	
	if (thus._rsp) {
		thus.scrollTop = thus.scrollLeft = 0;
		delete thus._rsp;
	}
	
	$panel
		.scrollTop(thus.scrollTop || 0)
		.scrollLeft(thus.scrollLeft || 0)
		.trigger('scroll');
};

//Set the scroll back to the top of the panel on the next page load, e.g. if a page button was pressed.
methods.resetScrollPosition = function() {
	thus._rsp = true;
};





methods.checkboxClick = function(id, e) {
	zenario.stop(e);
	
	setTimeout(function() {
		thus.itemClick(id, undefined, true);
	}, 0);
};


methods.itemClick = function(id, e, isCheckbox) {
	if (!thus.tuix || !thus.tuix.items[id]) {
		return false;
	}
	
	//If the admin is holding down the shift key...
	if (zenarioO.multipleSelectEnabled && !isCheckbox && (e || event).shiftKey && thus.lastItemClicked) {
		//...select everything between the current item and the last item that they clicked on
		zenarioO.selectItemRange(id, thus.lastItemClicked);
	
	//If multiple select is enabled and the checkbox was clicked...
	} else if (zenarioO.multipleSelectEnabled && isCheckbox) {
		//...toogle the item that they've clicked on
		if (thus.selectedItems[id]) {
			thus.deselectItem(id);
		} else {
			thus.selectItem(id);
		}
		zenarioO.closeInspectionView();
		thus.lastItemClicked = id;
	
	//If multiple select is not enabled and the checkbox was clicked
	} else if (!zenarioO.multipleSelectEnabled && isCheckbox && thus.selectedItems[id]) {
		//...deselect everything if this row was already selected
		zenarioO.deselectAllItems();
		zenarioO.closeInspectionView();
		thus.lastItemClicked = id;
	
	//Otherwise select the item that they've just clicked on, and nothing else
	} else {
		zenarioO.closeInspectionView();
		zenarioO.deselectAllItems();
		thus.selectItem(id);
		thus.lastItemClicked = id;
	}
	
	
	zenarioO.setButtons();
	zenarioO.setHash();
	
	return false;
};





//Return an object of currently selected item ids
//This should be an object in the format {1: true, 6: true, 18: true}
methods.returnSelectedItems = function() {
	return thus.selectedItems;
};

//This method will be called when the CMS sets the items that are selected,
//e.g. when your panel is initially loaded.
//This is an object in the format {1: true, 6: true, 18: true}
//It is usually called before your panel is drawn so you do not need to update the state
//of the items on the page.
methods.cmsSetsSelectedItems = function(selectedItems) {
	thus.selectedItems = selectedItems;
};

//This method should cause an item to be selected.
//It is called after your panel is drawn so you should update the state of your items
//on the page.
methods.selectItem = function(id) {
	thus.selectedItems[id] = true;
	$(get('organizer_item_' + id)).addClass('organizer_selected');
	thus.updateItemCheckbox(id, true);
};

//This method should cause an item to be deselected
//It is called after your panel is drawn so you should update the state of your items
//on the page.
methods.deselectItem = function(id) {
	delete thus.selectedItems[id];
	$(get('organizer_item_' + id)).removeClass('organizer_selected');
	thus.updateItemCheckbox(id, false);
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
		thus.closeInspectionView(id);
	
	} else {
		thus.openInspectionView(id);
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


//Function to use for microtemplates
methods.microTemplate = function(template, data, filter) {
	return zenarioT.microTemplate(template, data, filter);
};



}, zenarioO.panelTypes);
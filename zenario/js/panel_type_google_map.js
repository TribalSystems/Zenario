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

var methods = methodsOf(
	panelTypes.google_map = extensionOf(panelTypes.base)
);


methods.init = function() {
	this.onIconURL = URLBasePath+'zenario/admin/images/google_map/yellow-map-pin-with-tick.png';
	this.offIconURL = URLBasePath+'zenario/admin/images/google_map/red-map-pin.png';
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
		+ 'zenario/admin/organizer.ajax.php?path='
		+ encodeURIComponent(this.path)
		+ zenario.urlRequest(this.returnAJAXRequests())
		+ zenario.urlRequest('panel_type=google_map');
};

//You should return the page size you wish to use, or false to disable pagination
methods.returnPageSize = function() {
	return false;
};


methods.returnPanelTitle = function() {
	var title = this.tuix.title;
	
	if (window.zenarioOSelectMode && (zenarioO.path == window.zenarioOTargetPath || window.zenarioOTargetPath === false)) {
		if (window.zenarioOMultipleSelect && this.tuix.multiple_select_mode_title) {
			title = this.tuix.multiple_select_mode_title;
		} else if (this.tuix.select_mode_title) {
			title = this.tuix.select_mode_title;
		}
	}
	
	if (zenarioO.filteredView) {
		title += phrase.refined;
	}
	
	return title + ' (Map)';
};

methods.showPanel = function($header, $panel, $footer) {
	$header.html(this.microTemplate('zenario_organizer_panel_header', {})).show();
	var html = this.microTemplate('zenario_organizer_google_map', {});
	$panel.html(html).show();
	
	var 
		map,
		mapOptions = {
			center: {
				lat: 0, 
				lng: 0
			},
			zoom: 2
		},
		bounds = new google.maps.LatLngBounds(),
		marker,
		position,
		lat = this.tuix.lat_column,
		lng = this.tuix.lng_column,
		dblClickItemButton = this.tuix.double_click_item_button,
		items = this.tuix.items,
		itemsCount = 0,
		itemsWithLatLng = 0;
	
	// Create google map
	map = new google.maps.Map(document.getElementById('organizer_google_map'), mapOptions);
	
	// Add locations to google map
	foreach (items as var key => var item) {
		itemsCount++;
		if (item[lat] && item[lng]) {
			itemsWithLatLng++;
			position = new google.maps.LatLng(item[lat], item[lng]);
			marker = new google.maps.Marker({
				position: position,
				map: map,
				icon: this.offIconURL
			});
			
			if (this.selectedItems[key]) {
				marker.icon = this.onIconURL;
			}
			
			bounds.extend(position);
			
			// Select a location on single click
			google.maps.event.addListener(marker, 'click', this.makeSelectItemCallback(item, marker));
			
			// Open properties admin box on double click
			google.maps.event.addListener(marker, 'dblclick', this.makeOpenAdminBoxCallback(item, marker, dblClickItemButton));
		}
	}
	if (itemsWithLatLng) {
		map.fitBounds(bounds);
	}
	google.maps.event.addDomListener(window, "resize", function() {
		var center = map.getCenter();
		google.maps.event.trigger(map, "resize");
		map.setCenter(center); 
	});
	html = this.microTemplate('zenario_organizer_google_map_footer', {items: itemsWithLatLng, total: itemsCount});
	$footer.html(html).show();
};

methods.showButtons = function($buttons) {
	var buttons, html,
		m = {
			itemButtons: false,
			collectionButtons: false
		};
	
	//If there is at least one item selected, show the item buttons, otherwise show the collection buttons.
	//Never show both the item buttons and the collection buttons at the same time
	if (zenarioO.itemsSelected > 0) {
		m.itemButtons = zenarioO.getItemButtons();
	} else {
		m.collectionButtons = zenarioO.getCollectionButtons();
	}
	html = this.microTemplate('zenario_organizer_panel_buttons', m);
	$buttons.html(html).show();
};

methods.makeOpenAdminBoxCallback = function(item, marker, dblClickItemButton) {
	var instance = this;
	return function() {
		instance.itemClick(item.id, marker, true);
		zenarioO.itemButtonClick(dblClickItemButton);
	};
};

methods.makeSelectItemCallback = function(item, marker) {
	var instance = this;
	return function() {
		instance.itemClick(item.id, marker);
	};
};

methods.itemClick = function(id, marker, select) {
	if (zenarioO.multipleSelectEnabled) {
		if (!select && this.selectedItems[id]) {
			this.deselectItem(id, marker);
		} else {
			this.selectItem(id, marker);
		}
		this.lastItemClicked = id;
	} else if (!zenarioO.multipleSelectEnabled && this.selectedItems[id] && !select) {
		zenarioO.deselectAllItems();
		this.lastItemClicked = id;
	} else {
		zenarioO.deselectAllItems();
		this.selectItem(id, marker);
		this.lastItemClicked = id;
	}
	zenarioO.setHash();
	zenarioO.setButtons();
}

methods.selectItem = function(id, marker) {
	marker.setIcon(this.onIconURL);
	methodsOf(panelTypes.base).selectItem.call(this, id);
};

methods.deselectItem = function(id, marker) {
	marker.setIcon(this.offIconURL);
	methodsOf(panelTypes.base).deselectItem.call(this, id);
};

methods.updateItemCheckbox = function(id, checked) {
	
	//No checkboxes on the map, so we do nothing
};


}, zenarioO.panelTypes);
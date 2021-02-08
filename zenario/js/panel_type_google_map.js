/*
 * Copyright (c) 2021, Tribal Limited
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
	zenario, zenarioA, zenarioT, zenarioAB, zenarioAT, zenarioO,
	encodeURIComponent, defined, engToBoolean, get, htmlspecialchars, jsEscape, phrase,
	extensionOf, methodsOf, has,
	panelTypes
) {
	"use strict";

var methods = methodsOf(
	panelTypes.google_map = extensionOf(panelTypes.base)
);


methods.init = function() {
	thus.onIconURL = URLBasePath+'zenario/admin/images/google_map/yellow-map-pin-with-tick.png';
	thus.offIconURL = URLBasePath+'zenario/admin/images/google_map/red-map-pin.png';
};

//Use this to add any requests you need to the AJAX URL used to call your panel
methods.returnAJAXRequests = function() {
	return {
		panel_type: 'google_map'
	};
};

//You should return the page size you wish to use, or false to disable pagination
methods.returnPageSize = function() {
	return false;
};


methods.returnPanelTitle = function() {
	return methodsOf(panelTypes.grid).returnPanelTitle.call(thus) + ' (Map)';
};

methods.showPanel = function($header, $panel, $footer) {
	$header.html(thus.microTemplate('zenario_organizer_panel_header', {})).show();
	var html = thus.microTemplate('zenario_organizer_google_map', {});
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
		lat = thus.tuix.lat_column,
		lng = thus.tuix.lng_column,
		dblClickItemButton = thus.tuix.double_click_item_button,
		items = thus.tuix.items,
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
				icon: thus.offIconURL
			});
			
			if (thus.selectedItems[key]) {
				marker.icon = thus.onIconURL;
			}
			
			bounds.extend(position);
			
			// Select a location on single click
			google.maps.event.addListener(marker, 'click', thus.makeSelectItemCallback(item, marker));
			
			// Open properties admin box on double click
			google.maps.event.addListener(marker, 'dblclick', thus.makeOpenAdminBoxCallback(item, marker, dblClickItemButton));
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
	html = thus.microTemplate('zenario_organizer_google_map_footer', {items: itemsWithLatLng, total: itemsCount});
	$footer.html(html).show();
};

methods.showButtons = function($buttons) {
	var buttons, html,
		m = {};
	
	//If there is at least one item selected, show the item buttons.
	if (zenarioO.itemsSelected > 0) {
		zenarioO.getItemButtons(m);
	}
	zenarioO.getCollectionButtons(m);
	
	html = thus.microTemplate('zenario_organizer_panel_buttons', m);
	$buttons.html(html).show();
};

methods.makeOpenAdminBoxCallback = function(item, marker, dblClickItemButton) {
	var instance = thus;
	return function() {
		instance.itemClick(item.id, marker, true);
		zenarioO.itemButtonClick(dblClickItemButton);
	};
};

methods.makeSelectItemCallback = function(item, marker) {
	var instance = thus;
	return function() {
		instance.itemClick(item.id, marker);
	};
};

methods.itemClick = function(id, marker, select) {
	if (zenarioO.multipleSelectEnabled) {
		if (!select && thus.selectedItems[id]) {
			thus.deselectItem(id, marker);
		} else {
			thus.selectItem(id, marker);
		}
		thus.lastItemClicked = id;
	} else if (!zenarioO.multipleSelectEnabled && thus.selectedItems[id] && !select) {
		zenarioO.deselectAllItems();
		thus.lastItemClicked = id;
	} else {
		zenarioO.deselectAllItems();
		thus.selectItem(id, marker);
		thus.lastItemClicked = id;
	}
	zenarioO.setHash();
	zenarioO.setButtons();
}

methods.selectItem = function(id, marker) {
	marker.setIcon(thus.onIconURL);
	methodsOf(panelTypes.base).selectItem.call(thus, id);
};

methods.deselectItem = function(id, marker) {
	marker.setIcon(thus.offIconURL);
	methodsOf(panelTypes.base).deselectItem.call(thus, id);
};

methods.updateItemCheckbox = function(id, checked) {
	
	//No checkboxes on the map, so we do nothing
};


}, zenarioO.panelTypes);
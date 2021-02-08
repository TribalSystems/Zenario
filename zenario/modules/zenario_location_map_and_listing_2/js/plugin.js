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
(function(zenario, zenario_location_map_and_listing_2, undefined) {

	$(document).ready(function () {
		if (zenario_location_map_and_listing_2 && zenario_location_map_and_listing_2.setDefaultMode) {
			zenario_location_map_and_listing_2.setDefaultMode();
			zenario_location_map_and_listing_2.setDefaultFilterVisibility();
		}
	});

	$(document).ajaxComplete(function () {
		if (zenario_location_map_and_listing_2 && zenario_location_map_and_listing_2.setDefaultMode) {
			zenario_location_map_and_listing_2.setDefaultMode();
			zenario_location_map_and_listing_2.setDefaultFilterVisibility();
		}
	});
	
	window.onload = function(){
		//If a user clicks anywhere outside the filters dropdown and the "Filter by" button,
		//the filters dropdown should close.
		
		//Polyfill for IE...
		if (zenario.browserIsIE()) {
			//Source: https://gomakethings.com/checking-event-target-selectors-with-event-bubbling-in-vanilla-javascript/#browser-compatibility-for-closest
			if (!Element.prototype.closest) {
				if (!Element.prototype.matches) {
					Element.prototype.matches = Element.prototype.msMatchesSelector || Element.prototype.webkitMatchesSelector;
				}
				
				Element.prototype.closest = function (s) {
					var el = this;
					var ancestor = this;
					if (!document.documentElement.contains(el)) return null;
					do {
						if (ancestor.matches(s)) return ancestor;
						ancestor = ancestor.parentElement;
					} while (ancestor !== null);
					return null;
				};
			}
		}
		
		//... and the logic for closing.
		document.onclick = function(e) {
		   	if ((zenario_location_map_and_listing_2 && !e.target.closest("#filter_dropdown") && e.target.id != "filter_button") || e.target.id == "apply_filters_button") {
			  	//Clicked outside, or clicked "apply filters"
			  	zenario_location_map_and_listing_2.setFilterVisibilityTo('filters_hidden');
			  	window.parent.zenario_location_map_and_listing_2.setFilterVisibilityTo('filters_hidden');
		   	}
		};
	};

	zenario_location_map_and_listing_2.setDefaultMode = function () {
		var body = $(document.body);
	
		//By default, set the plugin to list mode.
		if (!body.hasClass('mode_map') && !body.hasClass('mode_list')) {
			if (zenario_location_map_and_listing_2.pluginSettings.show_location_list == true) {
				zenario_location_map_and_listing_2.setModeTo('mode_list');
			} else {
				zenario_location_map_and_listing_2.setModeTo('mode_map');
			}
		} else if (body.hasClass('mode_list')) {
			//If the mode was set already, make sure the button CSS classes are set up correctly.
			//Also catch the case where an admin has disabled the map/list in the plugin settings.
			if (zenario_location_map_and_listing_2.pluginSettings.show_location_list == true) {
				zenario_location_map_and_listing_2.setModeTo('mode_list');
			} else if (zenario_location_map_and_listing_2.pluginSettings.show_map == true) {
				zenario_location_map_and_listing_2.setModeTo('mode_map');
			}
		} else if (body.hasClass('mode_map')) {
			if (zenario_location_map_and_listing_2.pluginSettings.show_map == true) {
				zenario_location_map_and_listing_2.setModeTo('mode_map');
			} else if (zenario_location_map_and_listing_2.pluginSettings.show_location_list == true) {
				zenario_location_map_and_listing_2.setModeTo('mode_list');
			}
		}
	}

	zenario_location_map_and_listing_2.setModeTo = function(modeName) {
		var mapModeButton = $('#mode_map_button');
		var listModeButton = $('#mode_list_button');
	
		if (modeName == 'mode_map') {
			zenarioL.set(1, 'mode_map', 'mode_list');
			mapModeButton.addClass('on');
			listModeButton.removeClass('on');
		} else if (modeName == 'mode_list') {
			zenarioL.set(1, 'mode_list', 'mode_map');
			listModeButton.addClass('on');
			mapModeButton.removeClass('on');
		}
	};
	
	zenario_location_map_and_listing_2.setDefaultFilterVisibility = function () {
		var body = $(document.body);
		
		if (!body.hasClass('filters_visible') && !body.hasClass('filters_hidden')) {
			zenario_location_map_and_listing_2.setFilterVisibilityTo('filters_hidden');
		} else if (body.hasClass('filters_visible')) {
			//If the filter visibility was set already, make sure the button CSS classes are set up correctly.
			zenario_location_map_and_listing_2.setFilterVisibilityTo('filters_visible');
		} else if (body.hasClass('filters_hidden')) {
			zenario_location_map_and_listing_2.setFilterVisibilityTo('filters_hidden');
		}
	}
	
	zenario_location_map_and_listing_2.setFilterVisibilityTo = function(visibilityName) {
		var filterDropdown = $('#filter_dropdown');
		
		if (visibilityName == 'filters_visible') {
			zenarioL.set(1, 'filters_visible', 'filters_hidden');
			filterDropdown.addClass("active");
		} else if (visibilityName == 'filters_hidden') {
			zenarioL.set(1, 'filters_hidden', 'filters_visible');
			var doNotSubmit = !filterDropdown.hasClass("active");
			
			filterDropdown.removeClass("active");
			
			if (!doNotSubmit) {
				var form = $('form');
			  	form.submit();
			}
		}
	};
	
	zenario_location_map_and_listing_2.filterOnclick = function() {
		var filterDropdown = $('#filter_dropdown');
		if (filterDropdown.hasClass("active")) {
			zenario_location_map_and_listing_2.setFilterVisibilityTo('filters_hidden');
		} else {
			zenario_location_map_and_listing_2.setFilterVisibilityTo('filters_visible');
		}
	};
	
	zenario_location_map_and_listing_2.selectedFiltersElementOnclick = function(targetElString) {
		targetEl = $(targetElString);
		targetEl.removeAttr('checked');
		zenario_location_map_and_listing_2.filterOnclick();
	};
	
	zenario_location_map_and_listing_2.pluginSettings = {};
	
	zenario_location_map_and_listing_2.savePluginSettings = function(show_location_list, show_map) {
		zenario_location_map_and_listing_2.pluginSettings = {};
		zenario_location_map_and_listing_2.pluginSettings['show_location_list'] = show_location_list;
		zenario_location_map_and_listing_2.pluginSettings['show_map'] = show_map;
	}
	
	//Variable to remember the variables needed for the google maps
	zenario_location_map_and_listing_2.mapVars = {};
	
	//Variable to store all of the google.maps.InfoWindows created
	zenario_location_map_and_listing_2.infoWindows = {};

	//Set up the map.
	//This function is called from the showSlot() function.
	zenario_location_map_and_listing_2.saveMapVars = function(pageLoadNum, locations, allowScrolling, areas, polygonStrokeOpacity, polygonFillOpacity, imagesFolder) {
		zenario_location_map_and_listing_2.mapVars = {};
		if (pageLoadNum !== undefined) {
			zenario_location_map_and_listing_2.mapVars[pageLoadNum] = [locations, allowScrolling, areas, polygonStrokeOpacity, polygonFillOpacity, imagesFolder];
		}
	}
	
	zenario_location_map_and_listing_2.initMap = function(pageLoadNum, containerId, zoomControl, zoomLevel) {
		
		var parent = (window.parent || window.opener);
		
		//Wait until the parent has loaded
		if (!parent
		 || !parent.zenario_location_map_and_listing_2
		 || !parent.zenario_location_map_and_listing_2.mapVars
		 || _.isEmpty(parent.zenario_location_map_and_listing_2.mapVars)) {
			setTimeout(function() {
				zenario_location_map_and_listing_2.initMap(pageLoadNum, containerId);
			}, 100);
			return;
		}
		
		var mapVariables, locations, allowScrolling, areas, polygonStrokeOpacity, polygonFillOpacity;
		
		//Load the map variables
		mapVariables = {};
		if (pageLoadNum
		 && pageLoadNum > 0
		 && window
		 && parent
		 && parent.zenario_location_map_and_listing_2
		 && parent.zenario_location_map_and_listing_2.mapVars
		 && parent.zenario_location_map_and_listing_2.mapVars[pageLoadNum]) {
			
			mapVariables = parent.zenario_location_map_and_listing_2.mapVars[pageLoadNum] || {};
		}
		
		locations = mapVariables[0] || {};
		allowScrolling = mapVariables[1] || false;
		areas = mapVariables[2] || {};
		polygonStrokeOpacity = mapVariables[3] || 0.8;
		polygonFillOpacity = mapVariables[4] || 0.35;
		imagesFolder = mapVariables[5] || "";
		
	
		//Create a new map object
		var l, numL = 0,
			mapId = containerId + '_map',
			mapIframeId = containerId + '_map_iframe',
			mapOptions, map,
			bounds = new google.maps.LatLngBounds(),
			mainWindow = (window && window.mainWindow) || (self && self.parent),
			minZoom = false;
		
		if (containerId && mainWindow) {
			//Create shortcuts to the listing functions in the window above
			zenario_location_map_and_listing_2.iframeHighlightItemOnList = function(htmlId) {
				mainWindow.zenario_location_map_and_listing_2.listingHighlightItemOnMap(containerId, htmlId);
			};
			
			zenario_location_map_and_listing_2.iframeItemRemoveHighlightOnList = function(htmlId) {
				mainWindow.zenario_location_map_and_listing_2.listingItemRemoveHighlight(containerId, htmlId);
			}
		}
		
		//Loop through all of the locations
		zenario_location_map_and_listing_2.infoWindows = {};
		if (locations && locations.length > 0) {
			for (l in locations) {
				//Create a position, marker and infoWindow for each location.
				var location = locations[l],
					pos = new google.maps.LatLng(location.latitude, location.longitude),
					marker,
					infoWindow,
					i, image, icon = undefined;
			
				//Count the locations
				++numL;
				if (numL === 1) {
					//Use the first location we come to for the default position of the map
					mapOptions = {
						center: new google.maps.LatLng(location.latitude, location.longitude),
						zoom: (1*location.map_zoom) || 7,
						mapTypeId: google.maps.MapTypeId.ROADMAP,
						scrollwheel: allowScrolling
					};
					map = new google.maps.Map(zenario.get(mapId), mapOptions);
				}
			
				if (!minZoom || (location.map_zoom < minZoom)) {
					minZoom = 1*location.map_zoom;
				}
				
				//Create a marker, using the image we found if possible.
				//The optimized: false parameter helps with certain bugs where on Chrome, the markers
				//appear trimmed, and on Firefox don't appear at all.
				if (location.icon_name) {
					var width = 31, height = 48;
					iconData = {
						url: imagesFolder + "/icon_" + location.icon_name + ".svg",
						size: new google.maps.Size(width, height),
						scaledSize: new google.maps.Size(width, height)
					};
				} else {
					var width = 27, height = 43;
					iconData = {
						url: "https://maps.gstatic.com/mapfiles/api-3/images/spotlight-poi2.png",
						size: new google.maps.Size(width, height)
					};
				}
				
				marker = new google.maps.Marker({
					position: pos,
					map: map,
					title: location.name,
					icon: iconData,
					optimized: false
				});
			
				//Add this marker to the bounds of the map
				bounds.extend(marker.position);
			
				//The contents of the infoWindow are stored on the page.
				//All infoWindows appear above the marker.
				infoWindow = new google.maps.InfoWindow({
					content: parent.$('#' + location.htmlId + "_basic_details").html(),
					pixelOffset: new google.maps.Size(0, (-height))
				});
		
				//This anonymous function is used to break the variable scope and preserve the values
				//of the location, pos, marker and infoWindow variables
				(function(location, pos, marker, infoWindow) {
			
					//Set the info window to open at the position
					infoWindow.setPosition(pos);
			
					//Create a wrapper function that will open the info window on the map
					infoWindow._open = function() {
						zenario_location_map_and_listing_2.closeInfoWindow();
						infoWindow.open(map);
					};
			
					//When the visitor clicks the pin, open the info window for the location they've just clicked on
					google.maps.event.addListener(marker, 'click', function() {
						infoWindow._open();
				
						//Attempt to highlight and scroll to the item in the parent window
						zenario_location_map_and_listing_2.iframeHighlightItemOnList(location.htmlId);
						zenario_location_map_and_listing_2.iframeScrollToItem(location.htmlId);
					});
					
					google.maps.event.addListener(marker, 'mouseout', function() {
						//Attempt to remove highlight from the item in the parent window
						//when a user stops hovering over the pin on the map.
						
						if (!zenario_location_map_and_listing_2.isInfoWindowOpen() && zenario_location_map_and_listing_2.pluginSettings.show_location_list == true) {
							zenario_location_map_and_listing_2.iframeItemRemoveHighlightOnList(location.htmlId);
						}
					});
					
					google.maps.event.addListener(marker, 'mouseover', function() {
						//Attempt to highlight the item in the parent window
						//when a user hovers over the pin on the map.
						
						if (!zenario_location_map_and_listing_2.isInfoWindowOpen() && zenario_location_map_and_listing_2.pluginSettings.show_location_list == true) {
							zenario_location_map_and_listing_2.iframeHighlightItemOnList(location.htmlId);
						}
					});
					
					google.maps.event.addListener(infoWindow, 'closeclick', function() {
						zenario_location_map_and_listing_2.closeInfoWindow();
					});
			
					//Store the infoWindow in an array so we can call it via other means
					zenario_location_map_and_listing_2.infoWindows[location.id] = infoWindow;
		
				//End of the anonymous function
				})(location, pos, marker, infoWindow);
			}
		} else {
			//If there are no locations to display, show a part of a city.
			if (zoomControl == 'auto_include_all_locations') {
				//Make sure the zoom level isn't something silly.
				zoom = 15;
			} else if (zoomControl == 'set_manually' && typeof zoomLevel == 'number') {
				zoom = zoomLevel;
			}
			
			mapOptions = {
				center: new google.maps.LatLng(51.4542645, -0.9781302999999753),
				zoom: zoom,
				mapTypeId: google.maps.MapTypeId.ROADMAP,
				scrollwheel: allowScrolling
			};
			map = new google.maps.Map(zenario.get(mapId), mapOptions);
		}
		
		//Catch the case where the map isn't defined
		if (!map) {
			mapOptions = {
				mapTypeId: google.maps.MapTypeId.ROADMAP,
				center: {lat: 0, lng: 0},
				zoom: 1
			};
			map = new google.maps.Map(zenario.get(mapId), mapOptions);
		}
		
		//Clicking on the map should close any open info windows.
		google.maps.event.addListener(map, "click", function(event) {
			zenario_location_map_and_listing_2.closeInfoWindow();
		});
		
		//Display any polygons on the map
		if (areas) {
			for (a in areas) {
				coords = [];
				for (l in areas[a]['coords']) {
					var coordLat = areas[a]['coords'][l][0];
					var coordLng = areas[a]['coords'][l][1];
					coords.push(new google.maps.LatLng(coordLat, coordLng));
				}
				
				var polygon = new google.maps.Polygon({
					paths: coords,
					strokeColor: areas[a]['polygon_colour'],
					strokeOpacity: polygonStrokeOpacity,
					strokeWeight: 2,
					fillColor: areas[a]['polygon_colour'],
					fillOpacity: polygonFillOpacity,
					content: areas[a]['name']
				});
				polygon.setMap(map);
			}
		}
		
		if (numL > 1) {
			
			//Find the centre first before setting the zoom.
			map.setCenter(bounds.getCenter());
			map.fitBounds(bounds);
			
			google.maps.event.addListenerOnce(map, 'bounds_changed', function() { 
				minZoom = map.getZoom();
				
				if (zoomControl == 'auto_include_all_locations') {
					//Make sure the zoom level isn't something silly.
					if (minZoom < 10) {
						minZoom = 10;
					} else if (minZoom > 13) {
						minZoom = 13;
					}
				} else if (zoomControl == 'set_manually' && typeof zoomLevel == 'number') {
					minZoom = zoomLevel;
				}
				
				this.setZoom(minZoom);
			});
		}
		
		if (zenario_location_map_and_listing_2.pluginSettings.show_location_list == false) {
			parent.$('#interface_list_container').remove();
		}
	};
	
	zenario_location_map_and_listing_2.isInfoWindowOpen = function() {
		for (var i in zenario_location_map_and_listing_2.infoWindows) {
			var infoWindow = zenario_location_map_and_listing_2.infoWindows[i];
			var infoWindowMap = infoWindow.getMap();
			if (infoWindowMap !== null && typeof infoWindowMap !== "undefined") {
				return true;
			}
		}
		return false;
    }
	
	//Close all open info windows
	zenario_location_map_and_listing_2.closeInfoWindow = function() {
		for (var i in zenario_location_map_and_listing_2.infoWindows) {
			zenario_location_map_and_listing_2.infoWindows[i].close();
		}
	
		zenario_location_map_and_listing_2.iframeItemRemoveHighlightOnList();
	};

	//If the visitor clicks on the listing, try to find the corresponding infoWindow on the map.
	//If we can find it, open it, and highlight this item in the listing.
	zenario_location_map_and_listing_2.listingClick = function(el, locationId) {
	
		var containerId = zenario.getContainerIdFromEl(el),
			mapIframeId = containerId + '_map_iframe',
			htmlId = containerId + '_loc_' + locationId,
			iframe = zenario.get(mapIframeId);
	
		if (iframe
		 && iframe.contentWindow
		 && iframe.contentWindow.zenario_location_map_and_listing_2
		 && iframe.contentWindow.zenario_location_map_and_listing_2.infoWindows
		 && iframe.contentWindow.zenario_location_map_and_listing_2.infoWindows[locationId]) {
			
			iframe.contentWindow.zenario_location_map_and_listing_2.infoWindows[locationId]._open();
		
		}
	};
	
	zenario_location_map_and_listing_2.listingMouseover = function(el, locationId) {
	
		var containerId = zenario.getContainerIdFromEl(el),
			mapIframeId = containerId + '_map_iframe',
			htmlId = containerId + '_loc_' + locationId,
			iframe = zenario.get(mapIframeId);
	
		if (iframe
		 && iframe.contentWindow
		 && iframe.contentWindow.zenario_location_map_and_listing_2
		 && !iframe.contentWindow.zenario_location_map_and_listing_2.isInfoWindowOpen()) {
			zenario_location_map_and_listing_2.listingHighlightItemOnMap(containerId, htmlId);
		}
	};
	
	zenario_location_map_and_listing_2.listingMouseout = function(el, locationId) {
	
		var containerId = zenario.getContainerIdFromEl(el),
			mapIframeId = containerId + '_map_iframe',
			htmlId = containerId + '_loc_' + locationId,
			iframe = zenario.get(mapIframeId);
	
		if (iframe
		 && iframe.contentWindow
		 && iframe.contentWindow.zenario_location_map_and_listing_2
		 && !iframe.contentWindow.zenario_location_map_and_listing_2.isInfoWindowOpen()) {
			zenario_location_map_and_listing_2.listingItemRemoveHighlight(containerId, htmlId);
		}
	};

	//Highlight an item in the listing (making sure to unhighlight everything else first)
	//If no item is passed, then this will just unhighlight everything
	zenario_location_map_and_listing_2.listingHighlightItemOnMap = function(containerId, htmlId) {
		$('#' + containerId + ' .zenario_lmal_highlighted_location').removeClass('zenario_lmal_highlighted_location');
	
		if (htmlId) {
			$('#' + containerId + ' .' + htmlId).addClass('zenario_lmal_highlighted_location');
		}
		
		//Highlight the map marker
		mapIframeId = containerId + '_map_iframe';
		iframe = zenario.get(mapIframeId);
		
		if (iframe
		 && iframe.contentWindow
		 && iframe.contentWindow.zenario_location_map_and_listing_2
		) {
		
			el = parent.$('#' + containerId + ' .' + htmlId).find('h3#location_title')[0];
			if (el) {
				var title = el.innerText;
				var marker = iframe.contentWindow.$('div[title|="' + title + '"]');
				marker.addClass('highlighted_pin');
				
				//Fallback icon logic - maintain sharpness
				var src = marker.find('img').attr('src');
				if (src == "https://maps.gstatic.com/mapfiles/api-3/images/spotlight-poi2.png") {
					marker.addClass('fallback_icon');
				}
			}
		}
	};
	
	zenario_location_map_and_listing_2.listingItemRemoveHighlight = function(containerId, htmlId) {
		$('#' + containerId + ' .zenario_lmal_highlighted_location').removeClass('zenario_lmal_highlighted_location');
	
		if (htmlId) {
			$('#' + containerId + ' .' + htmlId).removeClass('zenario_lmal_highlighted_location');
		}
		
		//Remove the highlight from the map marker
		mapIframeId = containerId + '_map_iframe';
		iframe = zenario.get(mapIframeId);
		
		if (iframe
		 && iframe.contentWindow
		 && iframe.contentWindow.zenario_location_map_and_listing_2
		) {
			el = iframe.contentWindow.$('.highlighted_pin');
			el.removeClass('fallback_icon');
			el.removeClass('highlighted_pin');
		}
	};

	//Dummy functions that will be created later
	zenario_location_map_and_listing_2.iframeHighlightItem = function(htmlId) {
	};
	zenario_location_map_and_listing_2.iframeScrollToItem = function(htmlId) {
	};
	
})(zenario, zenario_location_map_and_listing_2);
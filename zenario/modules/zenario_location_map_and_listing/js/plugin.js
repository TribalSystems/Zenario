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
(function(zenario, zenario_location_map_and_listing, undefined) {

	//Variable to store all of the google.maps.InfoWindows created
	zenario_location_map_and_listing.infoWindows = {};

	//Set up the map
	//This function is called from the showSlot() function
	zenario_location_map_and_listing.initMap = function(containerId, locations, allowScrolling, showAnyCountry) {
	
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
			zenario_location_map_and_listing.iframeHighlightItem = function(htmlId) {
				mainWindow.zenario_location_map_and_listing.listingHighlightItem(containerId, htmlId);
			};
			zenario_location_map_and_listing.iframeScrollToItem = function(htmlId) {
				mainWindow.zenario_location_map_and_listing.listingScrollToItem(containerId, htmlId);
			};
		}
	
		//Loop through all of the locations
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
			
			if (zenario.engToBoolean(location.hide_pin)) {
				icon = URLBasePath + 'zenario/admin/images/trans.png';
			
			//attempt to find the correct icon for this location - i.e. one that matches all of the CSS rules
			} else if (location.css_class) {
				if ((image = $('#' + location.css_class.replace(/\s+/g, '-')).css('background-image'))
				 && (image = image.match(/url\(['"]?(.*?)['"]?\)/i))
				 && (image = image[1])) {
					icon = image;
				}
			}
		
			//Create a marker, using the image we found if possible
			marker = new google.maps.Marker({
				position: pos,
				map: map,
				title: location.name,
				icon: icon
			});
			
			//Add this marker to the bounds of the map
			bounds.extend(marker.position);
			
			if (location.id != 'postcode') {
				//The contents of the infoWindow are stored on the page
				infoWindow = new google.maps.InfoWindow({content: $('#' + location.htmlId).html()});
			
				//This anonymous function is used to break the variable scope and preserve the values
				//of the location, pos, marker and infoWindow variables
				(function(location, pos, marker, infoWindow) {
				
					//Set the info window to open at the position
					infoWindow.setPosition(pos);
				
					//Create a wrapper function that will open the info window on the map
					infoWindow._open = function() {
						zenario_location_map_and_listing.closeInfoWindow();
						infoWindow.open(map);
					};
				
					//When the visitor clicks the pin, open the info window for the location they've just clicked on
					google.maps.event.addListener(marker, 'click', function() {
						infoWindow._open();
					
						//Attempt to highlight and scroll to the item in the parent window
						zenario_location_map_and_listing.iframeHighlightItem(location.htmlId);
						zenario_location_map_and_listing.iframeScrollToItem(location.htmlId);
					});
				
					//Store the infoWindow in an array so we can call it via other means
					zenario_location_map_and_listing.infoWindows[location.id] = infoWindow;
			
				//End of the anonymous function
				})(location, pos, marker, infoWindow);
			}
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
		
		if (numL > 1) {
			google.maps.event.addListenerOnce(map, 'bounds_changed', function() { 
				if (showAnyCountry) {
					this.setZoom(2);
				} else if (minZoom < this.getZoom()) {
					this.setZoom(minZoom);
				}
			});
			
			//If there were more than one location,
			//change the map position/zoom so that all of the markers are visible
			map.fitBounds(bounds);
		}
	};

	//Close all open info windows
	zenario_location_map_and_listing.closeInfoWindow = function() {
		for (var i in zenario_location_map_and_listing.infoWindows) {
			zenario_location_map_and_listing.infoWindows[i].close();
		}
	
		zenario_location_map_and_listing.iframeHighlightItem();
	};

	//If the visitor clicks on the listing, try to find the corresponding infoWindow on the map.
	//If we can find it, open it, and highlight this item in the listing.
	zenario_location_map_and_listing.listingClick = function(el, locationId) {
	
		var containerId = zenario.getContainerIdFromEl(el),
			mapIframeId = containerId + '_map_iframe',
			htmlId = containerId + '_loc_' + locationId,
			iframe = zenario.get(mapIframeId);
	
		if (iframe
		 && iframe.contentWindow
		 && iframe.contentWindow.zenario_location_map_and_listing
		 && iframe.contentWindow.zenario_location_map_and_listing.infoWindows
		 && iframe.contentWindow.zenario_location_map_and_listing.infoWindows[locationId]) {
			iframe.contentWindow.zenario_location_map_and_listing.infoWindows[locationId]._open();
		
			zenario_location_map_and_listing.listingHighlightItem(containerId, htmlId);
		}
	};

	//Highlight an item in the listing (making sure to unhighlight everything else first)
	//If no item is passed, then this will just unhighlight everything
	zenario_location_map_and_listing.listingHighlightItem = function(containerId, htmlId) {
		$('#' + containerId + ' .zenario_lmal_highlighted_location').removeClass('zenario_lmal_highlighted_location');
	
		if (htmlId) {
			$('#' + containerId + ' .' + htmlId).addClass('zenario_lmal_highlighted_location');
		}
	};

	zenario_location_map_and_listing.listingScrollToItem = function(containerId, htmlId) {
		
		var time = 400,
			offset, 
			$scrollableArea = $('#' + containerId + ' .zenario_lmal_scrollable_area'),
			$el = $('#' + containerId + ' .' + htmlId);
		if ($el.length) {
			var offset = 1*$el.offset().top - 1*$scrollableArea.offset().top;
	
			offset = Math.max(0, offset - Math.floor($scrollableArea.height() / 4));
	
			$scrollableArea.stop().animate({scrollTop: offset}, time);
		}
	};

	//Dummy functions that will be created later
	zenario_location_map_and_listing.iframeHighlightItem = function(htmlId) {
	};
	zenario_location_map_and_listing.iframeScrollToItem = function(htmlId) {
	};
	
})(zenario, zenario_location_map_and_listing);
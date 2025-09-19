var defaultMapCentreLat;
var defaultMapCentreLng;
var defaultMapZoom;
var map;
var mapZoom;
var mapCenter;
var mapCenterLat;
var mapCenterLng;
var marker;
var markerLat;
var markerLng;
var pinPlacementMethod;
var editMode;
var initialized = false;

function init(lib) {
	var mapOptions = {
		center: new google.maps.LatLng(defaultMapCentreLat, defaultMapCentreLng),
		zoom: defaultMapZoom,
		mapTypeId: google.maps.MapTypeId.ROADMAP,
		mapId: 'my_map'
	}
	
	map = new google.maps.Map(document.getElementById("map"),mapOptions);
	
	if (markerLat && markerLng) {
		placeMarkerAction(lib, new google.maps.LatLng(markerLat,markerLng),true);
	}
	
	google.maps.event.addListener(map,"bounds_changed",function () {
		mapZoom = map.getZoom();
		mapCenter = map.getCenter();
		mapCenterLat = mapCenter.lat();
		mapCenterLng = mapCenter.lng();
		updateMapLatLngZoomFields(lib);
	});

	map.setOptions({
		scrollwheel: false
	});
	
	if (!editMode) {
		if (marker!=undefined) {
			marker.setOptions({
				draggable: false
			});
		}
		
		map.setOptions({
			disableDefaultUI: true,
			draggable: false,
			disableDoubleClickZoom: true
		});
	}
}

function placeMarker (lib, method) {
	var markerLatLng;
	var address = "";
	
	pinPlacementMethod = method;

	if (pinPlacementMethod=="postcode_country") {
		if (lib.get("postcode").value!="") {
			address += lib.get("postcode").value.replace(" ","") + ',';
		}
		
		if (lib.get("country").value!="") {
			var country = parent.zenario.moduleNonAsyncAJAX("zenario_location_manager","&mode=get_country_name&country_id=" + lib.get("country").value);
			address += country + ',';
		}
		
		address = trimTrailingComma(address);
		
		geoCode(lib, address);
	} else if (pinPlacementMethod=="street_postcode_country") {
		if (lib.get("address_line_1").value!="") {
			address += lib.get("address_line_1").value + ',';
		}

		if (lib.get("postcode").value!="") {
			address += lib.get("postcode").value.replace(" ","") + ',';
		}
		
		if (lib.get("country").value!="") {
			var country = parent.zenario.moduleNonAsyncAJAX("zenario_location_manager","&mode=get_country_name&country_id=" + lib.get("country").value);
			address += country + ',';
		}
		
		address = trimTrailingComma(address);
		
		geoCode(lib, address);
	} else if (pinPlacementMethod=="street_city_country") {
		if (lib.get("address_line_1").value!="") {
			address += lib.get("address_line_1").value + ',';
		}

		if (lib.get("city").value!="") {
			address += lib.get("city").value + ',';
		}
		
		if (lib.get("country").value!="") {
			var country = parent.zenario.moduleNonAsyncAJAX("zenario_location_manager","&mode=get_country_name&country_id=" + lib.get("country").value);
			address += country + ',';
		}
		
		address = trimTrailingComma(address);
		
		geoCode(lib, address);
	} else if (pinPlacementMethod=="locality_postcode_country") {
		if (lib.get("locality").value!="") {
			address += lib.get("locality").value + ',';
		}

		if (lib.get("postcode").value!="") {
			address += lib.get("postcode").value.replace(" ","") + ',';
		}
		
		if (lib.get("country").value!="") {
			var country = parent.zenario.moduleNonAsyncAJAX("zenario_location_manager","&mode=get_country_name&country_id=" + lib.get("country").value);
			address += country + ',';
		}
		
		address = trimTrailingComma(address);
		
		geoCode(lib, address);
	}
}

function latLngInRange(min,number,max){
	if (!isNaN(number) && (number >= min) && (number <= max)) {
		return true;
	} else {
		return false;
	};
}

function placeLatLng(lib, lat, lng) {
	//validate lat lng
	if (latLngInRange(-90, lat, 90) && latLngInRange(-180, lng, 180)) {
		var point = new google.maps.LatLng(lat, lng);
		placeMarkerAction(lib, point);
	}
	else { 
		alert('Please enter valid latitude/longitude values.');
	}
}

function geoCode (lib, address) {
	var geocoder = new google.maps.Geocoder();

    if (geocoder) {
      geocoder.geocode( { 'address': address}, function(results, status) {
        if (status == google.maps.GeocoderStatus.OK) {
			placeMarkerAction(lib, results[0].geometry.location);
			map.fitBounds(results[0].geometry.viewport);
        } else {
          	alert("Geocoding was not successful.  Google returned the following error code: " + status);
        }
      });
	}
}

function trimTrailingComma (str) {
	if (str.substr(-1) == ",") {
		str = str.substr(0,str.length-1);
	}
	
	return str;
}

function placeMarkerAction (lib, point,initialize) {
	if (!initialize) {
		if (marker != undefined) {
			marker.setMap(null);
		}
		
		marker = undefined;
	}
	
	markerLatLng = point;
	markerLat = markerLatLng.lat();
	markerLng = markerLatLng.lng();
	
	marker = new google.maps.marker.AdvancedMarkerElement({
		map: map,
		position: markerLatLng,
		gmpDraggable: true
	});
	marker.element.style.outline = 'none';
	
	updateMarkerLatLngFields(lib);
	
	google.maps.event.addListener(marker, "dragend", function (event) {
		point = event.latLng;
		markerLat = point.lat();
		markerLng = point.lng();
		updateMarkerLatLngFields(lib);
	});
	
	if (!initialize) {
		map.setCenter(marker.position);
	}
}

function clearMap (lib) {
	defaultMapCentreLat = 0;
	defaultMapCentreLng = 0;
	defaultMapZoom = 1;
	marker = undefined;
	markerLat = undefined;
	markerLng = undefined;
	updateMarkerLatLngFields(lib);
	init(lib);
}

function updateMarkerLatLngFields (lib) {
	if (markerLat == undefined) {
		markerLat = "";
	}

	if (markerLng == undefined) {
		markerLng = "";
	}

	if (lib) {
		var markerLatEl = lib.get("marker_lat");
		var markerLngEl = lib.get("marker_lng");
		
		if (markerLatEl) markerLatEl.value = markerLat;
		if (markerLngEl) markerLngEl.value = markerLng;
	}
	
	if (initialized) {
		window.parent.zenarioAB.fieldChange('map_edit');
	}
}

function updateMapLatLngZoomFields (lib) {
	if (lib) {
		var mapCenterLatEl = lib.get("map_center_lat");
		var mapCenterLngEl = lib.get("map_center_lng");
		var zoomEl = lib.get("zoom");
		
		if (mapCenterLatEl) mapCenterLatEl.value = mapCenterLat;
		if (mapCenterLngEl) mapCenterLngEl.value = mapCenterLng;
		if (zoomEl) zoomEl.value = mapZoom;
	}
	
	if (initialized) {
		window.parent.zenarioAB.fieldChange('map_edit');
	}

	initialized = true;
}

function success (position) {
	defaultMapCentreLat = position.coords.latitude;
	defaultMapCentreLng = position.coords.longitude;
	markerLat = position.coords.latitude;
	markerLng = position.coords.longitude;
	defaultMapZoom = 16;
	
	map.setCenter(new google.maps.LatLng(defaultMapCentreLat, defaultMapCentreLng));
	map.setZoom(defaultMapZoom);
	placeMarkerAction(lib, new google.maps.LatLng(defaultMapCentreLat, defaultMapCentreLng));
}

function error () {
	alert("Your browser does not support this method");
}
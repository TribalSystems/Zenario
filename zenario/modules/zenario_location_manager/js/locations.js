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

function init() {
	var mapOptions = {
		center: new google.maps.LatLng(defaultMapCentreLat, defaultMapCentreLng),
		zoom: defaultMapZoom,
		mapTypeId: google.maps.MapTypeId.ROADMAP
	}
	
	map = new google.maps.Map(document.getElementById("map"),mapOptions);
	
	if (markerLat && markerLng) {
		placeMarkerAction(new google.maps.LatLng(markerLat,markerLng),true);
	}
	
	google.maps.event.addListener(map,"bounds_changed",function () {
		mapZoom = map.getZoom();
		mapCenter = map.getCenter();
		mapCenterLat = mapCenter.lat();
		mapCenterLng = mapCenter.lng();
		updateMapLatLngZoomFields();
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

function placeMarker (method) {
	var markerLatLng;
	var address = "";
	
	pinPlacementMethod = method;

	if (pinPlacementMethod=="postcode_country") {
		if (parent.document.getElementById("postcode").value!="") {
			address += parent.document.getElementById("postcode").value.replace(" ","") + ',';
		}
		
		if (parent.document.getElementById("country").value!="") {
			var country = parent.zenario.moduleNonAsyncAJAX("zenario_location_manager","&mode=get_country_name&country_id=" + parent.document.getElementById("country").value);
			address += country + ',';
		}
		
		address = trimTrailingComma(address);
		
		geoCode(address);
	} else if (pinPlacementMethod=="street_postcode_country") {
		if (parent.document.getElementById("address_line_1").value!="") {
			address += parent.document.getElementById("address_line_1").value + ',';
		}

		if (parent.document.getElementById("postcode").value!="") {
			address += parent.document.getElementById("postcode").value.replace(" ","") + ',';
		}
		
		if (parent.document.getElementById("country").value!="") {
			var country = parent.zenario.moduleNonAsyncAJAX("zenario_location_manager","&mode=get_country_name&country_id=" + parent.document.getElementById("country").value);
			address += country + ',';
		}
		
		address = trimTrailingComma(address);
		
		geoCode(address);
	} else if (pinPlacementMethod=="street_city_country") {
		if (parent.document.getElementById("address_line_1").value!="") {
			address += parent.document.getElementById("address_line_1").value + ',';
		}

		if (parent.document.getElementById("city").value!="") {
			address += parent.document.getElementById("city").value + ',';
		}
		
		if (parent.document.getElementById("country").value!="") {
			var country = parent.zenario.moduleNonAsyncAJAX("zenario_location_manager","&mode=get_country_name&country_id=" + parent.document.getElementById("country").value);
			address += country + ',';
		}
		
		address = trimTrailingComma(address);
		
		geoCode(address);
	} else if (pinPlacementMethod=="my_location") {
		if (navigator.geolocation) {
			navigator.geolocation.getCurrentPosition(success, error);
		}
	}
}

function latLngInRange(min,number,max){
	if ( !isNaN(number) && (number >= min) && (number <= max) ){
		return true;
	} else {
		return false;
	};
}

function placeLatLng(lat, lng) {
	//validate lat lng
	if (latLngInRange(-90,lat,90) && latLngInRange(-180,lng,180)) {
		var point = new google.maps.LatLng(lat, lng);
		placeMarkerAction(point);
	}
	else { 
		alert('Please enter valid latitude/longitude values.');
	}

}

function geoCode (address) {
	var geocoder = new google.maps.Geocoder();

    if (geocoder) {
      geocoder.geocode( { 'address': address}, function(results, status) {
        if (status == google.maps.GeocoderStatus.OK) {
			placeMarkerAction(results[0].geometry.location);
			map.fitBounds(results[0].geometry.viewport);
        } else {
          	alert("Geocoding was not successful.  Google returned the following error code: " + status);
        }
      });
	}
}

function trimTrailingComma (str) {
	if (str.substr(-1)==",") {
		str = str.substr(0,str.length-1);
	}
	
	return str;
}

function placeMarkerAction (point,initialize) {
	if (!initialize) {
		if (marker!=undefined) {
			marker.setMap(null);
		}
		
		marker = undefined;
	}
	
	markerLatLng = point;
	markerLat = markerLatLng.lat();
	markerLng = markerLatLng.lng();
	marker = new google.maps.Marker({
		position: markerLatLng,
		map: map,
		draggable: true
	});
	
	updateMarkerLatLngFields();
	
	google.maps.event.addListener(marker,"dragend",function (event) {
		point = event.latLng;
		markerLat = point.lat();
		markerLng = point.lng();
		updateMarkerLatLngFields();
	});
	
	if (!initialize) {
		map.setCenter(marker.getPosition());
	}
}

function clearMap () {
	defaultMapCentreLat = 0;
	defaultMapCentreLng = 0;
	defaultMapZoom = 1;
	marker = undefined;
	markerLat = undefined;
	markerLng = undefined;
	updateMarkerLatLngFields();
	init();
}

function updateMarkerLatLngFields () {
	if (markerLat==undefined) {
		markerLat = "";
	}

	if (markerLng==undefined) {
		markerLng = "";
	}

	parent.document.getElementById("marker_lat").value = markerLat;
	parent.document.getElementById("marker_lng").value = markerLng;
	
	if (initialized) {
		window.parent.zenarioAB.fieldChange('map_edit');
	}
}

function updateMapLatLngZoomFields () {
	parent.document.getElementById("map_center_lat").value = mapCenterLat;
	parent.document.getElementById("map_center_lng").value = mapCenterLng;
	parent.document.getElementById("zoom").value = mapZoom;	
	
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
	placeMarkerAction(new google.maps.LatLng(defaultMapCentreLat, defaultMapCentreLng));
}

function error () {
	alert("Your browser does not support this method");
}
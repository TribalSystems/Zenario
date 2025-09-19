zenario_location_viewer.initMap = function (elId,lat,lng, mapZoom) {
	var mapOptions;
	var map;
	var marker;
	var zoom = 12;
	
	if (mapZoom) {
		zoom = mapZoom;
	}
	
	if (zenario.get(elId)) {
		mapOptions = {
			center: new google.maps.LatLng(lat,lng),
			zoom: (zoom*1),
			mapTypeId: google.maps.MapTypeId.ROADMAP,
			mapId: 'my_map',
			scrollwheel: false
		}
		
		map = new google.maps.Map(zenario.get(elId),mapOptions);
	
		marker = new google.maps.marker.AdvancedMarkerElement({
			position: new google.maps.LatLng(lat,lng),
			map: map
		});
	}
}

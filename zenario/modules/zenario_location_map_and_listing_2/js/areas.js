var defaultMapCentreLat;
var defaultMapCentreLng;
var defaultMapZoom;
var ne_lat;
var ne_lng;
var sw_lat;
var sw_lng;
var polygon_colour;
var map;
var editMode;
var drawingManager;
var polygons = [];
var selectedShape;
var all_overlays = [];
var flightPath;
var drawingManager;
var selectedShape;



function init() {
	/*
	function init(editModeIn) {
	editMode = editModeIn
	*/
	var latlng = new google.maps.LatLng(-34.397, 150.644);
		
	if (ne_lat && ne_lng && sw_lat && sw_lng && zoom) {
		var mapNe = new google.maps.LatLng(ne_lat, ne_lng);
		var mapSw = new google.maps.LatLng(sw_lat, sw_lng);
		var mapBounds = new google.maps.LatLngBounds(mapSw,mapNe);
		var options = {
		  mapTypeId: google.maps.MapTypeId.ROADMAP
		};
	} else {
		var options = {
			center: new google.maps.LatLng(defaultMapCentreLat, defaultMapCentreLng),
			zoom: 1,
		 	mapTypeId: google.maps.MapTypeId.ROADMAP
		}
	}
	
	map = new google.maps.Map(document.getElementById("map"),options);
	
	if (ne_lat && ne_lng && sw_lat && sw_lng && zoom) {
		map.setCenter(mapBounds.getCenter());
		map.setZoom(zoom);
	}

	if (ne_lat && ne_lng && sw_lat && sw_lng && zoom) {
		map.setCenter(mapBounds.getCenter());
		map.setZoom(zoom);
	}

	drawingManager = new google.maps.drawing.DrawingManager({
		drawingMode: google.maps.drawing.OverlayType.POLYGON,
		drawingControlOptions: {
			drawingModes: [
				google.maps.drawing.OverlayType.POLYGON
			]
		},
		//Settings for "Create an area" admin box
		polygonOptions: {
			strokeColor: '#' + polygon_colour,
			strokeOpacity: 1.0,
			strokeWeight: 2,
			fillColor: '#' + polygon_colour,
			fillOpacity: 0.45,
			draggable: true,
			editable: true
		}
	});
		
	 drawingManager.setMap(map);

	var polygonPointsSaved = parent.document.getElementById("polygon_points").value;

	if (polygonPointsSaved) {
		//paint
		paintPath();
		drawingManager.setDrawingMode(null);
			drawingManager.setOptions({
			drawingControl: false
		});
	}

	google.maps.event.addListener(drawingManager, 'overlaycomplete', function(e) {
		all_overlays.push(e);
		if (e.type != google.maps.drawing.OverlayType.MARKER) {
			var newShape = e.overlay;
			newShape.type = e.type;
			google.maps.event.addListener(newShape, 'click', function() {
				setSelection(newShape);
			});
			setSelection(newShape);
		}
	
	});

	google.maps.event.addListener(drawingManager, 'polygoncomplete', function (polygon) {
		var stringPoints;
		var arrayOfPoints = (polygon.getPath().getArray());
		
		stringPoints = getStringPoints(arrayOfPoints);
		parent.document.getElementById("polygon_points").value = stringPoints;
	
		google.maps.event.addListener(polygon.getPath(), 'set_at', function() {
			arrayOfPoints = (polygon.getPath().getArray());
			stringPoints = getStringPoints(arrayOfPoints);
			parent.document.getElementById("polygon_points").value = stringPoints;
		});

		google.maps.event.addListener(polygon.getPath(), 'insert_at', function() {
			arrayOfPoints = (polygon.getPath().getArray());
			stringPoints = getStringPoints(arrayOfPoints);
			parent.document.getElementById("polygon_points").value = stringPoints;
		});
		
		drawingManager.setMap(null);
	});

	google.maps.event.addListener(map, "bounds_changed", function () {
	var bounds = map.getBounds();
	var ne = bounds.getNorthEast();
	var sw = bounds.getSouthWest();

	ne_lat = ne.lat();
	ne_lng = ne.lng();
	sw_lat = sw.lat();
	sw_lng = sw.lng();

	parent.document.getElementById("ne_lat").value = ne_lat;
	parent.document.getElementById("ne_lng").value = ne_lng;
	parent.document.getElementById("sw_lat").value = sw_lat;
	parent.document.getElementById("sw_lng").value = sw_lng;
	parent.document.getElementById("zoom").value = map.getZoom();
});
 
	google.maps.event.addDomListener(document.getElementById('delete-button'), 'click', deleteAllShape);
}



function deleteAllShape() {
	// To show drawing buttons:
	drawingManager.setOptions({drawingControl: true});
	deleteSelectedShape();
	parent.document.getElementById("polygon_points").value = null;
	for (var i=0; i < all_overlays.length; i++){
		all_overlays[i].overlay.setMap(null);
	}
	
	all_overlays = [];
	removeLine(); 
	// To show drawing buttons:
	drawingManager.setMap(map);
	drawingManager.setDrawingMode(google.maps.drawing.OverlayType.POLYGON);
}

function setSelection(shape) {
	selectedShape = shape;
	shape.setEditable(true);
}

function deleteSelectedShape() {
	if (selectedShape) {
		selectedShape.setMap(null);
	}
	removeLine(); 
	// To show:
	drawingManager.setOptions({drawingControl: true});
}

function removeLine() {
	var polygonPointsSaved = parent.document.getElementById("polygon_points").value;

	if (polygonPointsSaved && flightPath) {
		flightPath.setMap(null);
	}
}

function getStringPoints(arrayOfPoints) {
	var i;
	var polygonPointsString;
	var numOfPoints = arrayOfPoints.length;
	for (i = 0; i < numOfPoints; i++) {
		if (!polygonPointsString) {
			polygonPointsString = arrayOfPoints[i].lat() + "_" + arrayOfPoints[i].lng();
		} else {
			polygonPointsString = polygonPointsString + "," + arrayOfPoints[i].lat() + "_" + arrayOfPoints[i].lng();
		}
	}

	return polygonPointsString; 
}

function paintPath() {
	var pathSaved = new Array();
	var pathLat = new Array();
	var pathlng = new Array();
	var flightPlanCoordinates = new Array();
	var polygonPointsSaved = parent.document.getElementById("polygon_points").value;
	var pointsSaved = polygonPointsSaved.split(",");
	var j;
	var k;
	
	for (j = 0; j < pointsSaved.length; j++) {
		var latLngSaved = pointsSaved[j].split("_");
		var latSaved = latLngSaved[0];
		var lngSaved = latLngSaved[1];
		pathLat.push(latSaved);
		pathlng.push(lngSaved);
	}
	
	//path array 
	for (k=0; k < pathLat.length; k++){
		flightPlanCoordinates.push(new google.maps.LatLng(pathLat[k],pathlng[k]));
	}
	flightPlanCoordinates.push(new google.maps.LatLng(pathLat[0],pathlng[0]));

	flightPath = new google.maps.Polygon({
		path: flightPlanCoordinates,
		geodesic: true,
		draggable: true,
		editable: true,
		//Settings for "Edit an area" admin box
		strokeColor: '#' + polygon_colour,
		strokeOpacity: 1.0,
		strokeWeight: 2,
		fillColor: '#' + polygon_colour,
		fillOpacity: 0.45
	});

	flightPath.setMap(map);
	
	google.maps.event.addListener(flightPath.getPath(), 'set_at', function() {
		arrayOfPoints = (flightPath.getPath().getArray());
		stringPoints = getStringPoints(arrayOfPoints);
		parent.document.getElementById("polygon_points").value = stringPoints;
	});
	
	google.maps.event.addListener(flightPath.getPath(), 'insert_at', function() {
		arrayOfPoints = (flightPath.getPath().getArray());
		stringPoints = getStringPoints(arrayOfPoints);
		parent.document.getElementById("polygon_points").value = stringPoints;
	});
}

function geocodeAddress () {
	var geocoder = new google.maps.Geocoder();
	
	if (geocoder) {
		geocoder.geocode({'address': document.getElementById("address_to_geocode").value}, function(results, status) {
			if (status == google.maps.GeocoderStatus.OK) {
				map.setCenter(results[0].geometry.location);
				map.fitBounds(results[0].geometry.viewport);
			} else {
				alert("Geocode was not successful for the following reason: " + status);
			}
		});
	}
}


function geoCodeViewport () {
	var geocoder = new google.maps.Geocoder();
	
	if (geocoder) {
		geocoder.geocode({'address': document.getElementById("address").value}, function(results, status) {
			if (status == google.maps.GeocoderStatus.OK) {
				map.setCenter(results[0].geometry.location);
				map.fitBounds(results[0].geometry.viewport);
			} else {
				alert("Geocode was not successful for the following reason: " + status);
			}
		});
	}
}
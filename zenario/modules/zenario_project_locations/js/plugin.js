zenario_project_locations.initMap = function(map_canvas_id, map_cluster_grid_size, 
        map_cluster_zoom_click_info, points_array, all_info_array){
    // map options
    var options = {
        center: new google.maps.LatLng(0, 0),
        mapTypeId: google.maps.MapTypeId.TERRAIN,
        mapTypeControl: false
    };

    // init map
    var map = new google.maps.Map(document.getElementById(map_canvas_id), options);
    var infowindow = new google.maps.InfoWindow();
    
    var map_info_window_template = document.getElementById('map-info-window-template'); 
    
    //create empty LatLngBounds object
    var bounds = new google.maps.LatLngBounds();
    // set multiple marker
    var markers = [];
    var tpl = map_info_window_template.innerHTML;
    
    for (var i = 0, len=points_array.length; i < len; i++) {
    	var point = points_array[i];
        // init markers
        var rec = all_info_array[i]; 
        var marker_content = tpl.replace(/\{\{([^}]+)\}\}/g, function(all_m, m1){
                    switch(m1){
                        case 'project_link':
                            return rec[m1];
                            break;
                        case 'location':
                            return rec[m1];
                            break;
                        case 'client_name':
                            return rec[m1];
                            break;
                        case 'Sticky_image_HTML_tag':
                            return rec[m1];
                            break;
                        case 'content_summary':
                            return rec[m1];
                            break;
                    }
                    return ''; //zzz' + m1 + 'xxx';
                });

        //console.log(rec);
        
        var image;
        
        if (rec.content_summary.replace(/\s/g, '')) {
            image = URLBasePath + 'zenario/modules/zenario_project_locations/js/images/blue_pin.png';
            
        } else {
            //No description!
            image = URLBasePath + 'zenario/modules/zenario_project_locations/js/images/red_pin.png';
            
        }
        
       // console.log(image);

        
        var marker = new google.maps.Marker({
            position: new google.maps.LatLng(point[0], point[1]),
            map: map,
            icon: image,
            title: point[2],
            //content: $('#' + point[3]).html() //#id of an html tag to be used to display info
            content: marker_content //#id of an html tag to be used to display info
        });

        // process multiple info windows
        (function(marker) {
            // add click event
            google.maps.event.addListener(marker, 'click', function() {
            	infowindow.setContent(marker.content);
            	infowindow.open(map, marker);
            });
        })(marker); 

/*
        // process multiple info windows
        (function(marker, i) {
            // add click event
            google.maps.event.addListener(marker, 'mouseout', function() {
            	if(infowindow){
            		infowindow.close();
            	}
            });
        })(marker, i);
*/
        markers.push(marker);
        //extend the bounds to include each marker's position
        bounds.extend(marker.position);
    }
    var mc = new MarkerClusterer(map, markers, {gridSize: map_cluster_grid_size, 
    		infoOnClickZoom:  map_cluster_zoom_click_info, 
    		reusableInfoWindow: infowindow});

    //now fit the map to the newly inclusive bounds
    mc.map.fitBounds(bounds);
};
//slotName, instanceId, additionalRequests, recordInURL, scrollToTopOfSlot, fadeOutAndIn
zenario_project_locations.refreshListSection = function(slotNameOrContainedElement, requests, scrollToTopOfSlot, fadeOutAndIn){
	
        if (scrollToTopOfSlot && !zenario.AFBOpen) {
		//Scroll to the top of a slot if needed
		zenario.scrollToSlotTop(slotNameOrContainedElement, true);
		
		//Don't scroll to the top later if we've already done it now
		scrollToTopOfSlot = false;
	}
	
	//Fade the slot out to give a graphical hint that something is happening
	if (fadeOutAndIn) {
		$('#plgslt_' + slotNameOrContainedElement).stop(true, true).animate({opacity: .5}, 150);
	}
        
	//Run an AJAX request to reload the contents
	var url = URLBasePath + 'zenario/ajax.php?method_call=refreshPlugin'
                                + '&cID=' + zenario.cID + '&cType=' + zenario.cType + (zenario.cVersion? '&cVersion=' + zenario.cVersion : '')
                                + '&slotName=' + slotNameOrContainedElement
                                + requests; 
	
        //(I'm using jQuery so that this is done asyncronously)
        $.ajax({
                dataType: 'text',
                url: url,
                success: function(html) {
                    var mark_start = '<!--ListResults-start-->';
                    var start_pos = html.indexOf(mark_start);
                    var end_pos = html.indexOf('<!--ListResults-end-->');
                    var list_results = html.substring(start_pos + mark_start.length, end_pos);
                    $('#project_search_results_only_list').html(list_results);
                    $('#plgslt_' + slotNameOrContainedElement).stop(true, true).animate({opacity: 1}, 150);
                } 
        });
    
};
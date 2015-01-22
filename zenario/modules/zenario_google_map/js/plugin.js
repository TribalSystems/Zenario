zenario_google_map.initMap = function (address,elId,errPhrase) {
					var geocoder;
					var mapOptions;
					var map;
					var marker;
					
					geocoder = new google.maps.Geocoder();
					
					if (geocoder) {
					  geocoder.geocode( { 'address': address}, function(results, status) {
						if (status == google.maps.GeocoderStatus.OK) {
							mapOptions = {
								center: results[0].geometry.location,
								zoom: 3,
								mapTypeId: google.maps.MapTypeId.ROADMAP
							}
	
							map = new google.maps.Map(zenario.get(elId),mapOptions);
							
							map.fitBounds(results[0].geometry.viewport);
							
							marker = new google.maps.Marker({
								position: results[0].geometry.location,
								map: map
							});
						} else {
							zenario.get(elId).innerHTML = zenario.htmlspecialchars(errPhrase);
						}
					  });
					}
				}

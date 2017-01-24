<?php
/*
 * Copyright (c) 2017, Tribal Limited
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
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');

echo '<!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-us" lang="en-us">
<head id="head">
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
	<title>Google Map</title>
	<style type="text/css">
		html,
		body,
		body > div {
			height: 100%;
			min-height: 100%;
			padding: 0;
			margin: 0;
		}
	</style>
	<script src="' . httpOrhttps() . 'maps.googleapis.com/maps/api/js?v=3" type="text/javascript"></script>
</head>
<body>
	<div id="map">
	</div>
	
	<script type="text/javascript">
		var opener = (self && self.parent) || (window && window.opener),
			getFromOpener = function(name) {
				name = ("" + name).split(".");
				return (name[1] && opener && opener[name[0]] && opener[name[0]][name[1]])
					|| (name[0] && opener && opener[name[0]]);
			},
			el = document.getElementById("map"),
			lat = "', jsEscape(get('lat')), '",
			lng = "', jsEscape(get('lng')), '",
			zoom = "', jsEscape(get('zoom')), '",
			addMarkerAtCentre = "', jsEscape(get('addMarkerAtCentre')), '",
			options = getFromOpener("', jsEscape(get('options')), '") || {},
			callback = getFromOpener("', jsEscape(get('callback')), '"),
			map,
			marker,
			stylesheet = "', jsEscape(get('stylesheet')), '",
			customIcon = "', jsEscape(get('customIcon')), '",
			addStylesheet = function(href) {
				var sheet = document.createElement("link");
				sheet.type = "text/css";
				sheet.rel = "stylesheet";
				sheet.href = href;
				document.getElementById("head").appendChild(sheet);
			};
			
			lat = getFromOpener(lat) || lat || options.lat;
			lng = getFromOpener(lng) || lng || options.lng;
			stylesheet = getFromOpener(stylesheet) || stylesheet || options.stylesheet;
			customIcon = getFromOpener(customIcon) || customIcon || options.customIcon;
			
			if (stylesheet) {
				if (typeof stylesheet == "string") {
					stylesheet = [stylesheet];
				}
				
				for (var s in stylesheet) {
					addStylesheet(stylesheet[s]);
				}
					
			}
			if (lat && lng) {
				options.center = new google.maps.LatLng(1*lat, 1*lng);
				options.zoom = options.zoom || 1*zoom || 2;
			} else {
				options.center = new google.maps.LatLng(0,0);
				options.zoom = 1;
			}
			
			options.mapTypeId = options.mapTypeId || google.maps.MapTypeId.ROADMAP;
			//options.scrollwheel = false;
			if(typeof options.scrollwheel != "undefined"){
				options.scrollwheel = options.scrollwheel;
			}
			
			map = new google.maps.Map(el, options);
			if (lat && lng) {
				if (addMarkerAtCentre && customIcon) {
					marker = new google.maps.Marker({
						position: options.center,
						map: map,
						zoom: options.zoom,
						icon: customIcon
					});
				} else if (addMarkerAtCentre) {
					marker = new google.maps.Marker({
						position: options.center,
						zoom: options.zoom,
						map: map
					});
				}
			}
			
			if (typeof callback == "function") {
				callback(map, google);
			}
	</script>
</body>';
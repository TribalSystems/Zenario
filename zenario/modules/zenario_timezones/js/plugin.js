/*
 * Copyright (c) 2020, Tribal Limited
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

(function(zenario, zenario_timezones) {
	"use strict";

	zenario_timezones.init = function(containerId, locationId, dataPoolId1) {
	
		zenario.ajax(zenario_timezones.AJAXLink(), {locationId: locationId, dataPoolId1: dataPoolId1}).after(function(data) {
		
			if (data) {
				var now = new Date(),
					times = data.split('~'),
					serverTimeHoursOffset = 1*times[0] - now.getHours(),
					serverTimeMinsOffset = 1*times[1] - now.getMinutes(),
					serverTimeSecsOffset = 1*times[2] - now.getSeconds(),
					serverTimezone = times[3],
					domCurrentTime = zenario.get('current_time_' + containerId),
				
					fun = function() {
						var d = new Date();
						d = 1*d + 1000 * (serverTimeSecsOffset + 60 * (serverTimeMinsOffset + 60 * serverTimeHoursOffset));
						d = new Date(d);
	
						if (zenario.browserIsIE()) {
							domCurrentTime.innerHTML = zenario.htmlspecialchars(d.toLocaleTimeString());
						} else {
							domCurrentTime.innerHTML =
								('0' + d.getHours()).substr(-2) + ':' +
								('0' + d.getMinutes()).substr(-2) + ':' +
								('0' + d.getSeconds()).substr(-2);
						}
					};
				
				$('#current_timezone_' + containerId).text(serverTimezone);
				$('#current_time_and_timezone_' + containerId).show();
			
				fun();
				setInterval(fun, 250);
			}
		});
	}

})(zenario, zenario_timezones);
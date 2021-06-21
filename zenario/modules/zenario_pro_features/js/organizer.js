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

zenario.lib(function(
	undefined,
	URLBasePath,
	document, window, windowOpener, windowParent,
	zenario, zenarioA, zenarioT, zenarioAB, zenarioAT, zenarioO,
	encodeURIComponent, defined, engToBoolean, get, htmlspecialchars, jsEscape, phrase,
	extensionOf, methodsOf, has,
	zenario_pro_features, zenario_server_time
) {
	"use strict";


zenario_pro_features.serverTime = function() {
	var el = get(zenario_server_time);
	
	if (!el) {
		return;
	}
	
	var d = new Date();
	d = 1*d + 1000 * (zenario_pro_features.serverTimeSecsOffset + 60 * (zenario_pro_features.serverTimeMinsOffset + 60 * zenario_pro_features.serverTimeHoursOffset));
	d = new Date(d);
	
	if (zenario.browserIsIE()) {
		el.innerHTML = htmlspecialchars(d.toLocaleTimeString());
	} else {
		el.innerHTML =
			('0' + d.getHours()).substr(-2) + ':' +
			('0' + d.getMinutes()).substr(-2) + ':' +
			('0' + d.getSeconds()).substr(-2);
	}
};


zenario.on('', '', 'eventSetOrganizerIcons', function() {
	var times = zenario.moduleNonAsyncAJAX('zenario_pro_features', 'getBottomLeftInfo=1', true).split('~'),
		now = new Date(),
		htmlST = '',
		htmlSC = '',
		_$div = zenarioT.div,
		
		zenario_scheduled_tasks_status = 'zenario_scheduled_tasks_status';
	
	
	zenario_pro_features.serverTimeHoursOffset = 1*times[2] - now.getHours();
	zenario_pro_features.serverTimeMinsOffset = 1*times[3] - now.getMinutes();
	zenario_pro_features.serverTimeSecsOffset = 1*times[4] - now.getSeconds();
	
	htmlST = _$div(
		'id', zenario_scheduled_tasks_status,
		'class', times[5],
		'onclick', times[7] && ("zenarioO.go('" + jsEscape(times[7]) + "', -1);")
	);
	
	htmlSC = _$div(
		'id', zenario_server_time
	);
	
	
	setTimeout(function() {
		
		var tooltipOptions = {
			position: {my: 'center bottom', at: 'center top', collision: 'flipfit'},
			items: '*'
		};
		
		tooltipOptions.content = phrase.serverTime;
		zenarioA.tooltips('#' + zenario_server_time, tooltipOptions);
	
		if (tooltipOptions.content = times[6]) {
			zenarioA.tooltips('#' + zenario_scheduled_tasks_status, tooltipOptions);
		}
	}, 200);
	
	if (zenario_pro_features.serverTimeInterval) {
		clearInterval(zenario_pro_features.serverTimeInterval);
	}
	zenario_pro_features.serverTimeInterval = setInterval(zenario_pro_features.serverTime, 250);
	
	
	return {'↙': [[htmlST, 1], [htmlSC, 1.1]]};
});




}, zenario_pro_features, 'zenario_server_time');
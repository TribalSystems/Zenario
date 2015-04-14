/*
 * Copyright (c) 2015, Tribal Limited
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
	zenario, zenarioA, zenarioAB, zenarioAT, zenarioO,
	get, engToBoolean, htmlspecialchars, ifNull, jsEscape, phrase,
	extensionOf, methodsOf,
	zenario_pro_features
) {
	"use strict";


zenario_pro_features.serverTime = function() {
	var el = get('zenario_server_time');
	
	if (!el) {
		return;
	}
	
	var d = new Date();
	d = 1*d + 1000 * (zenario_pro_features.serverTimeSecsOffset + 60 * (zenario_pro_features.serverTimeMinsOffset + 60 * zenario_pro_features.serverTimeHoursOffset));
	d = new Date(d);
	
	if (zenario.browserIsIE()) {
		el.innerHTML = phrase.serverTime + htmlspecialchars(d.toLocaleTimeString());
	} else {
		el.innerHTML =
			phrase.serverTime +
			('0' + d.getHours()).substr(-2) + ':' +
			('0' + d.getMinutes()).substr(-2) + ':' +
			('0' + d.getSeconds()).substr(-2);
	}
	
	if (zenario_pro_features.pageCachingTooltip) {
		zenarioA.tooltips('#zenario_page_caching', {
			position: {my: 'left+2 center', at: 'right center', collision: 'flipfit'},
			items: '*',
			content: zenario_pro_features.pageCachingTooltip});
	}
	delete zenario_pro_features.pageCachingTooltip;
	
	if (zenario_pro_features.serverTimeTooltip) {
		zenarioA.tooltips('#zenario_server_time', {
			position: {my: 'left+2 center', at: 'right center', collision: 'flipfit'},
			items: '*',
			content: zenario_pro_features.serverTimeTooltip});
	}
	delete zenario_pro_features.serverTimeTooltip;
};


zenario_pro_features.fillOrganizerLowerLeft = function() {
	var times = zenario.pluginClassAJAX('zenario_pro_features', 'getBottomLeftInfo=1', true).split('~'),
		now = new Date(),
		htmlPC = '',
		htmlSC = '';
	
	
	htmlPC +=
		'<div id="zenario_page_caching"' +
		' class="' + (times[0]? 'zenario_page_caching_on' : 'zenario_page_caching_off') + '"' +
		' style="cursor: pointer;" onclick="zenarioAB.openSiteSettings(\'web_pages\', \'zenario_pro_features__caching\');"></div>';
	
	times[1] = times[1].replace(
		'is_htaccess_working',
		zenarioA.isHtaccessWorking()?
			phrase.compressed
		 :	phrase.notCompressed
	);
	
	zenario_pro_features.serverTimeHoursOffset = 1*times[2] - now.getHours();
	zenario_pro_features.serverTimeMinsOffset = 1*times[3] - now.getMinutes();
	zenario_pro_features.serverTimeSecsOffset = 1*times[4] - now.getSeconds();
	
	htmlSC += '<div id="zenario_server_time" class="' + htmlspecialchars(times[5]) + '"';
	
	if (times[7]) {
		htmlSC += ' style="cursor: pointer;" onclick="zenarioO.go(\'' + htmlspecialchars(times[7]) + '\', -1);"';
	}
	
	htmlSC += '></div>';
	
	//Old 6.1.0 logic:
	//$('#leftColumn').after(html);
	
	zenario_pro_features.pageCachingTooltip = times[1];
	zenario_pro_features.serverTimeTooltip = times[6];
	
	if (zenario_pro_features.serverTimeInterval) {
		clearInterval(zenario_pro_features.serverTimeInterval);
	}
	
	zenario_pro_features.serverTimeInterval = setInterval(zenario_pro_features.serverTime, 250);
	
	return [[htmlPC, 10], [htmlSC, 20]];
};




}, zenario_pro_features);
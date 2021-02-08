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
window.zenarioCI = function() {};

(function(
	zenario, zenarioCI,
	undefined) {


zenarioCI.box = function(slotName, type, cantCache, settingEnabled) {
	var css = '',
		html = '',
		cache_if = {u: 'User', g: 'GET', p: 'POST', s: 'SESSION', c: 'COOKIE'},
		clear_cache_by = {};
	
	for (var slotNameNestId in zenarioCD.slots) {
		if (!slotName
		 || slotNameNestId == slotName
		 || slotNameNestId.substr(0, slotName.length + 1) == slotName + '-'
		 || slotNameNestId == slotName.split('-')[0]) {
			
			if (cantCache || !zenarioCD.slots[slotNameNestId].cache_if || !zenarioCD.slots[slotNameNestId].cache_if.a || zenarioCD.slots[slotNameNestId].disallow_caching) {
				cantCache = true;
				break;
			
			} else {
				for (var i in zenarioCD.slots[slotNameNestId].cache_if) {
					if (!zenarioCD.slots[slotNameNestId].cache_if[i]) {
						delete cache_if[i];
					}
				}
				if (zenarioCD.slots[slotNameNestId].clear_cache_by) {
					for (var i in zenarioCD.slots[slotNameNestId].clear_cache_by) {
						if (zenarioCD.slots[slotNameNestId].clear_cache_by[i]) {
							clear_cache_by[i] = true;
						}
					}
				}
			}
		}
	}
	
	
	if (cantCache) {
		css = 'zenario_cache_disabled';
		html += '<h1 class="' + css + '">This ' + type + ' does not support caching.</h1><h2>The developer did not enable support for caching for this ' + type + '.</h2>';
	
	} else {
		
		css = 'zenario_cache_in_use';
		if (zenarioCD.served_from_cache || (slotName && zenarioCD.slots[slotName] && zenarioCD.slots[slotName].served_from_cache)) {
			css = 'zenario_from_cache';
		}
		
		var ruleNotMet = false,
			load = {u: 'User', g: 'GET', p: 'POST', s: 'SESSION', c: 'COOKIE'},
			by = {content: 'Content', menu: 'Menu', user: 'User', file: 'File', module: 'Module'},
			
			key = {u: 'An Extranet User is logged in', g: 'A non-canonical GET request', p: 'A POST request', s: 'A session variable', c: 'A cookie from this site',
					l: 'Location dependant logic',
					content: 'Other Content Items, any change to Categories', menu: 'Menu Nodes', user: "Users, their Characteristics, Groups and Group memberships",
					file: 'Images, animations and documents stored in the CMS', module: 'Any data added by a Module'};
		
		
		if (type == 'Page') {
			load.l = 'LOCATION';
		}
		
		html += '<h2>The caching rules for this ' + type + ' are as follows:</h2>';
		html += '<table><tr><td></td><th>Current</th><th>' + type + ' Req\'t</th><th>Conflicts</th></tr>';
		for (var i in load) {
			ruleNotMet = zenarioCD.load[i] && !cache_if[i];
			
			if (ruleNotMet) {
				css = 'zenario_not_cached';
			}
			
			html += '<tr><th>' + zenario.htmlspecialchars(key[i]) + '</th>';
			html += 	'<td><span class="' + (zenarioCD.load[i]? 'zenario_cache_one' : 'zenario_cache_zero') + '">&nbsp;</span></td>';
			html += 	'<td class="zenario_cache_req"><span class="zenario_cache_zero">&nbsp;</span>' + (cache_if[i]? ' or <span class="zenario_cache_one">&nbsp;</span>' : '') + '</td>';
			html += 	'<td>' + (ruleNotMet? '<span class="zenario_cache_cross">&nbsp;</span>' : '&nbsp;') + '</td></tr>';
		}
		html += '</table>';
		
		
		var found = false;
		for (var i in by) {
			if (clear_cache_by[i]) {
				if (!found) {
					html += '<h2>This ' + type + '\ will be cleared from the cache when any of the following change:</h2><ul>';
					found = true;
				}
				html += '<li>' + zenario.htmlspecialchars(key[i]) + '</li>';
			}
		}
		if (found) {
			html += '</ul>';
		}
		
		
		if (css == 'zenario_not_cached') {
			html = '<h1 class="' + css + '">This ' + type + ' can be cached, but not in the current situation due to a conflict.</h1>' + html;
		
		} else if (css == 'zenario_from_cache') {
			html = '<h1 class="' + css + '">This ' + type + ' can be cached and was just served from the cache.</h1>' + html;
		
		} else if (settingEnabled) {
			html = '<h1 class="' + css + '">This ' + type + ' can be cached and was just written to the cache.</h1>' + html;
		
		} else {
			html = '<h1 class="' + css + '">This ' + type + ' could be cached if plugin caching was enabled in Configuration->Site Settings, Optimisation interface.</h1>' + html;
		}
	}
	
	return '<x-zenario-cache-info class="' + css + '" title="' + zenario.htmlspecialchars('<div class="zenario_cache_box">' + html + '</div>') + '"></x-zenario-cache-info>';
}






zenarioCI.init = function(canCache) {
	if (!zenarioCI.inited) {
		var slotName,
			html,
			options = {
				tooltipClass: 'zenario_cache_info_tooltip',
				position: {my: 'right-2 center', at: 'left center', collision: 'flipfit'}};
		
		$('div.zenario_slot').each(function(i, el) {
			
			if (el.id && (slotName = el.id.replace('plgslt_', '')) && (zenarioCD.slots[slotName])) {
				
				$(el).prepend('<x-zenario-cache-info class="zenario_cache_info">' + zenarioCI.box(slotName, 'Plugin', false, zenarioCD.cache_plugins) + '</x-zenario-cache-info>');
			}
		});
		
		zenario.get('zenario_cache_info').innerHTML = zenarioCI.box('', 'Page', !canCache, true);
		
		zenario.tooltips('x-zenario-cache-info.zenario_cache_info *', options);
		
		zenarioCI.inited = true;
	}
}



})(
	zenario, zenarioCI);
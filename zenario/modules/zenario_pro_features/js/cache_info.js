/*
 * Copyright (c) 2022, Tribal Limited
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


zenarioCI.box = function(slotName, type, cantCache) {
	var css = '',
		html = '',
		cache_if = {u: 'User', g: 'GET', p: 'POST', s: 'SESSION', c: 'COOKIE'},
		clear_cache_by = {},
		lType = type.toLowerCase(),
		pluginDesc = '',
		si, slot;
	
	for (var slotNameNestId in zenarioCD.slots) {
		if (!slotName
		 || slotNameNestId == slotName
		 || slotNameNestId.substr(0, slotName.length + 1) == slotName + '-'
		 || slotNameNestId == slotName.split('-')[0]) {
			
			slot = zenarioCD.slots[slotNameNestId];
			
			if (cantCache || !slot.cache_if || !slot.cache_if.a || slot.disallow_caching) {
				cantCache = true;
				break;
			
			} else {
				for (var i in slot.cache_if) {
					if (!slot.cache_if[i]) {
						delete cache_if[i];
					}
				}
				if (slot.clear_cache_by) {
					for (var i in slot.clear_cache_by) {
						if (slot.clear_cache_by[i]) {
							clear_cache_by[i] = true;
						}
					}
				}
			}
		}
	}
	
	if (slotName && (slot = zenario.slots[slotName])) {
		
		//N.b. similar logic to the following is also used inline in zenario/js/admin_grid_maker.js
		pluginDesc = ', ';
		
		if (slot.isVersionControlled) {
			pluginDesc += slot.moduleClassName + ',';
		} else {
			switch (slot.moduleClassName) {
				case 'zenario_plugin_nest':
					pluginDesc += 'N';
					break;
				case 'zenario_slideshow':
				case 'zenario_slideshow_simple':
					pluginDesc += 'S';
					break;
				default:
					pluginDesc += 'P';
			}
			pluginDesc += ('' + slot.instanceId).padStart(2, '0') + ' (' + slot.moduleClassName + '),';
		}
	}
	
	
	if (cantCache) {
		css = 'zenario_cache_disabled';
		html += '<h1 class="' + css + '">This ' + lType + zenario.htmlspecialchars(pluginDesc) + ' does not support caching.</h1><h2>The developer did not enable support for caching for this ' + lType + '.</h2>';
	
	} else {
		
		css = 'zenario_cache_in_use';
		if (zenarioCD.served_from_cache || (slotName && zenarioCD.slots[slotName] && zenarioCD.slots[slotName].served_from_cache)) {
			css = 'zenario_from_cache';
		}
		
		var ruleNotMet = false,
			load = {u: 'User', g: 'GET', p: 'POST', s: 'SESSION', c: 'COOKIE'},
			by = {content: 'Content', menu: 'Menu', user: 'User', file: 'File', module: 'Module'},
			
			key = {u: 'Extranet user logged in', g: 'GET request*', p: 'POST request', s: 'Session variable†', c: 'Cookie (from this site)‡',
					l: 'Location dependant logic',
					content: 'Other content items, any changes to categories', menu: 'Menu nodes', user: "Users, and their group memberships",
					file: 'Images and documents stored in Zenario', module: 'Any data added by a module'};
		
		
		if (type == 'Page') {
			load.l = 'LOCATION';
		}
		
		html += '<h2>The caching rules for this ' + lType + ' are as follows:</h2>';
		html += '<table><tr><th>&nbsp;</th><th>' + type + ' requires</th><th>This request</th><th>Result</th></tr>';
		for (var i in load) {
			ruleNotMet = zenarioCD.load[i] && !cache_if[i];
			
			if (ruleNotMet) {
				css = 'zenario_not_cached';
			}
			
			html += '<tr><th>' + zenario.htmlspecialchars(key[i]) + '</th>';
			html += 	'<td class="zenario_cache_req">' + (cache_if[i]? 'Any' : '0') + '</td>';
			html += 	'<td>' + (zenarioCD.load[i]? '1' : '0') + '</td>';
			html += 	'<td>' + (ruleNotMet? 'Can\'t cache' : 'OK to cache') + '</td></tr>';
		}
		html += '<tr class="zenario_cache_table_last_row"><th>Result</th><td>&nbsp;</td><td>&nbsp;</td><td>' + (css == 'zenario_not_cached'? 'Can\'t cache' : 'OK to cache') +  '</td></tr>';
		html += '</table>';
		
		
		var found = false;
		for (var i in by) {
			if (clear_cache_by[i]) {
				if (!found) {
					html += '<h2>This ' + lType + '\ will be cleared from the cache when any of the following change:</h2><ul>';
					found = true;
				}
				html += '<li>' + zenario.htmlspecialchars(key[i]) + '</li>';
			}
		}
		if (found) {
			html += '</ul>';
		}
		
		
		if (css == 'zenario_not_cached') {
			html = '<h1 class="' + css + '">This ' + lType + zenario.htmlspecialchars(pluginDesc) + ' can be cached, but not in the current situation due to a conflict.</h1>' + html;
		
		} else if (css == 'zenario_from_cache') {
			html = '<h1 class="' + css + '">This ' + lType + zenario.htmlspecialchars(pluginDesc) + ' can be cached and was just served from the cache.</h1>' + html;
		
		} else {
			html = '<h1 class="' + css + '">This ' + lType + zenario.htmlspecialchars(pluginDesc) + ' can be cached and was just written to the cache.</h1>' + html;
		}
		
		html += '<p class="zenario_cache_footnote"><span class="zenario_cache_footnote_character">(*)</span> <em>A page is considered unique for caching purposes according to its alias (or its <code>cID</code> and <code>cType</code>), and a &ldquo;<code>page</code>&rdquo; number when pagination is used, and any additional GET parameters such as <code>searchString</code>.</em></p>';
		html += '<p class="zenario_cache_footnote"><span class="zenario_cache_footnote_character">(†)</span> <em>Some session variables do not affect caching and are ignored in this check. Check the <code>ze::cacheFriendlySessionVar()</code> function in <code>zenario/basicheader.inc.php</code> to see the logic used.</em></p>';
		html += '<p class="zenario_cache_footnote"><span class="zenario_cache_footnote_character">(‡)</span> <em>Some cookies do not affect caching and are ignored in this check. Check the <code>ze::cacheFriendlyCookieVar()</code> function in <code>zenario/basicheader.inc.php</code> to see the logic used.</em></p>';
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
				
				$(el).prepend('<x-zenario-cache-info class="zenario_cache_info">' + zenarioCI.box(slotName, 'Plugin', false) + '</x-zenario-cache-info>');
			}
		});
		
		zenario.get('zenario_cache_info').innerHTML = zenarioCI.box('', 'Page', !canCache);
		
		zenario.tooltips('x-zenario-cache-info.zenario_cache_info *', options);
		
		zenarioCI.inited = true;
	}
}



})(
	zenario, zenarioCI);
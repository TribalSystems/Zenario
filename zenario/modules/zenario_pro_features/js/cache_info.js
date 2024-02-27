/*
 * Copyright (c) 2024, Tribal Limited
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
		cache_if = {u: true, g: true, s: true},
		clear_cache_by = {},
		lType = type.toLowerCase(),
		pluginDesc = '',
		si, slot,
		i, slotNameNestId;
	
	for (slotNameNestId in zenarioCD.slots) {
		if (!slotName
		 || slotNameNestId == slotName
		 || slotNameNestId.substr(0, slotName.length + 1) == slotName + '-'
		 || slotNameNestId == slotName.split('-')[0]) {
			
			slot = zenarioCD.slots[slotNameNestId];
			
			if (cantCache || !slot.cache_if || !slot.cache_if.a || slot.disallow_caching) {
				cantCache = true;
				break;
			
			} else {
				for (i in slot.cache_if) {
					if (!slot.cache_if[i]) {
						cache_if[i] = false;
					}
				}
				if (slot.clear_cache_by) {
					for (i in slot.clear_cache_by) {
						if (slot.clear_cache_by[i]) {
							clear_cache_by[i] = true;
						}
					}
				}
			}
		}
	}
	
	if (slotName) {
		pluginDesc = zenarioCI.pluginDesc(slotName);
		
		if (pluginDesc) {
			pluginDesc = ',' + pluginDesc + ',';
		}
	}
	
	
	if (cantCache) {
		css = 'zenario_cache_disabled';
		html += '<h1 class="' + css + '">This ' + lType + zenario.htmlspecialchars(pluginDesc) + ' does not support caching.</h1><h2>The developer did not enable support for caching for this ' + lType + '.</h2>';
		
		if (!slotName) {
			html += '<ul>';
			for (slotNameNestId in zenarioCD.slots) {
			
				slot = zenarioCD.slots[slotNameNestId];
			
				if (!slot.cache_if || !slot.cache_if.a || slot.disallow_caching) {
					var blockingPlugin = zenarioCI.pluginDesc(slotNameNestId);
					
					if (blockingPlugin) {
						blockingPlugin = 'Blocked by ' + blockingPlugin + ' in slot ' + slotNameNestId;
					} else {
						blockingPlugin = 'Blocked by slot ' + slotNameNestId;
					}
					html += '<li>' + zenario.htmlspecialchars(blockingPlugin) + '.</li>';
				}
			}
			html += '</ul>';
		}
	
	} else if (type == 'Page' && zenarioCD.load.l) {
		css = 'zenario_cache_disabled';
		html += '<h1 class="' + css + '">Location-dependant redirect logic in use on this ' + lType + '.</h1><h2>This ' + lType + ' cannot be cached but plugins on the ' + lType + ' may still be cached.</h2>';
	
	} else {
		
		css = 'zenario_cache_in_use';
		if (zenarioCD.served_from_cache || (slotName && zenarioCD.slots[slotName] && zenarioCD.slots[slotName].served_from_cache)) {
			css = 'zenario_from_cache';
		}
		
		var ruleNotMet = false,
			conditions = {s: 's', g: 'g', u: 'u'},
			by = {content: 'content', menu: 'menu', file: 'file', module: 'module'},
			
			key = {
				g: 'Additional GET parameters or a POST request†',
				s: 'A cookie or session variable*',
				u: 'A logged in extranet user',
				content: 'There are changes to content items or categories',
				menu: 'A menu node is added/moved/renamed/deleted',
				file: 'A file or image is uploaded to the database, cropped, deleted or renamed',
				module: 'Data is added/updated/deleted by a module'
			};
		
		html += '<h2>The caching rules for this ' + lType + ' are as follows:</h2>';
		html += '<table><tr><th>Condition</th><th>Can the ' + lType + ' be cached with this?</th><th>Does this request have this?</th><th>Result</th></tr>';
		for (i in conditions) {
			ruleNotMet = zenarioCD.load[i] && !cache_if[i];
			
			if (ruleNotMet) {
				css = 'zenario_not_cached';
			}
			
			html += '<tr><th>' + zenario.htmlspecialchars(key[i]) + '</th>';
			html += 	'<td class="zenario_cache_req">' + (cache_if[i]? 'Yes' : 'No') + '</td>';
			html += 	'<td>' + (zenarioCD.load[i]? 'Yes' : 'No') + '</td>';
			html += 	'<td>' + (ruleNotMet? 'Can\'t cache' : 'OK to cache') + '</td></tr>';
		}
		html += '<tr class="zenario_cache_table_last_row"><th>Result</th><td>&nbsp;</td><td>&nbsp;</td><td>' + (css == 'zenario_not_cached'? 'Can\'t cache' : 'OK to cache') +  '</td></tr>';
		html += '</table>';
		
		
		var found = false;
		for (i in by) {
			if (clear_cache_by[i]) {
				if (!found) {
					html += '<h2>This ' + lType + '\ will be cleared from the cache when:</h2><ul>';
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
			html = '<h1 class="' + css + '">This ' + lType + zenario.htmlspecialchars(pluginDesc) + ' can be cached. It was served from the database but has now been written to the cache for further requests.</h1>' + html;
		}
		
		html += '<p class="zenario_cache_footnote"><span class="zenario_cache_footnote_character">(*)</span> <em>Some session variables and cookies do not affect caching and are ignored in this check. Check the <code>ze::cacheFriendlySessionVar()</code> and <code>ze::cacheFriendlyCookieVar()</code> functions in <code>zenario/basicheader.inc.php</code> to see the logic used.</em></p>';
		html += '<p class="zenario_cache_footnote"><span class="zenario_cache_footnote_character">(†)</span> <em>A page is considered unique for caching purposes according to its alias (or its <code>cID</code> and <code>cType</code>). Some plugins also add additional parameters, for example a <code>page</code> number or a <code>search</code> string.</em></p>';
	}
	
	return '<x-zenario-cache-info class="' + css + '" title="' + zenario.htmlspecialchars('<div class="zenario_cache_box">' + html + '</div>') + '"></x-zenario-cache-info>';
};


zenarioCI.pluginDesc = function(slotName) {
	
	var slot, pluginDesc = '';
	
	if (slot = zenario.slots[slotName]) {
		
		//N.b. similar logic to the following is also used inline in zenario/js/admin_grid_maker.js
		if (slot.isVersionControlled) {
			pluginDesc += slot.moduleClassName;
		
		} else if (slotName.split('-')[1]) {
			pluginDesc += 'nested ' + slot.moduleClassName;
		
		} else {
			//N.b. this logic is a copy of the zenarioA.pluginCodeName() function!
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
			pluginDesc += ('' + slot.instanceId).padStart(2, '0') + ' (' + slot.moduleClassName + ')';
		}
		
		pluginDesc = ' ' + pluginDesc;
	}
	
	return pluginDesc;
};



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
};



})(
	zenario, zenarioCI);
/*
 * Copyright (c) 2025, Tribal Limited
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

zenario.lib(function(
	undefined,
	URLBasePath,
	document, window, windowOpener, windowParent,
	zenario, zenarioA, zenarioT, zenarioAB, zenarioAT, zenarioO,
	encodeURIComponent, defined, engToBoolean, get, htmlspecialchars, jsEscape, phrase,
	extensionOf, methodsOf, has,
	zenarioCI, zenarioCD
) {
	"use strict";


//Show a debug tooltip for either a plugin, a nested plugin, or the entire page
zenarioCI.box = function(slotNameNestId, type, cantCache) {
	var css = '',
		html = '',
		blocking = false,
		cache_if = {u: true, g: true, s: true},
		clear_cache_by = {},
		lType = type.toLowerCase(),
		pluginDesc = '',
		si, slot, thisSlotName,
		i, thisSlotNameNestId,
		slotName = slotNameNestId.split('-')[0];	//Remove the Nested Plugin id from the slotNameNestId if needed
	
	
	//If showing info for a nest or a slideshow, don't call it a "Plugin"
	if (slotNameNestId && (slot = zenario.slots[slotNameNestId])) {
		switch (slot.moduleClassName) {
			case 'zenario_nest':
			case 'zenario_ajax_nest':
				type = 'Nest';
				break;
			case 'zenario_slideshow':
				type = 'Slideshow';
		}
	}
	lType = type.toLowerCase();
	
	
	//Work out what the rules are for whatever thing this is.
	//Rules for normal plugins and nested plugins can be simply read off of their entry in the zenarioCD.slots object.
	//Rules for nests, slideshows and the entire page need to be calculated by combining all of the rules from the plugins that make them up.
	for (thisSlotNameNestId in zenarioCD.slots) {
		
		//Remove the Nested Plugin id from the slotNameNestId if needed
		thisSlotName = thisSlotNameNestId.split('-')[0];
		
		if (!slotNameNestId || thisSlotName == slotName) {
			
			slot = zenarioCD.slots[thisSlotNameNestId];
			
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
	
	if (slotNameNestId) {
		pluginDesc = zenarioCI.pluginDesc(slotNameNestId);
		
		if (pluginDesc) {
			pluginDesc = ',' + pluginDesc + ',';
		}
	}
	
	
	if ($('#plgslt_' + slotName).hasClass('zenario_slot_reloaded')) {
		css = 'zenario_cache_disabled zenario_cache_reloaded';
		html += '<h1 class="' + css + '">' + type + htmlspecialchars(pluginDesc) + ' was reloaded via AJAX after the page was displayed, so the caching info for it is out of date.</h1>';
	
	} else if (cantCache) {
		//Show a message if something doesn't support caching
		
		//It can be a bit confusing what's actually stopping a nest/slideshow/page from being cached.
		//To try and fix this, we'll always specifically mention which plugin it actually is.
		html += '<ul>';
		foreach (zenarioCD.slots as thisSlotNameNestId => slot) {
			
			//Remove the Nested Plugin id from the slotNameNestId if needed
			thisSlotName = thisSlotNameNestId.split('-')[0];
			
			if (!slot.cache_if || !slot.cache_if.a || slot.disallow_caching) {
				if (slotNameNestId && slotNameNestId == thisSlotNameNestId) {
					html += '<li>This ' + lType + ' is blocking caching.</li>';
					blocking = true;
				
				} else if (!slotNameNestId || thisSlotName == slotName) {
			
					var blockingPlugin = zenarioCI.pluginDesc(thisSlotNameNestId);
					
					if (blockingPlugin) {
						blockingPlugin = 'Blocked by ' + blockingPlugin + ' in slot ' + thisSlotNameNestId;
					} else {
						blockingPlugin = 'Blocked by slot ' + thisSlotNameNestId;
					}
					html += '<li>' + htmlspecialchars(blockingPlugin) + '.</li>';
				}
			}
			
			if (slot.cache_msg) {
				html += '<li><em>' + htmlspecialchars(slot.cache_msg) + '</em></li>';
			}
		}
		html += '</ul>';
		
		css = 'zenario_cache_disabled';
		if (blocking) {
			html = '<h1 class="' + css + '">' + type + htmlspecialchars(pluginDesc) + ' caching info: does not support caching.</h1>' + html;
		} else {
			css += ' zenario_cache_blocked';
			html = '<h1 class="' + css + '">' + type + htmlspecialchars(pluginDesc) + ' caching info: caching is blocked.</h1>' + html;
		}
	
	} else if (type == 'Page' && zenarioCD.load.l) {
		css = 'zenario_cache_disabled';
		html += '<h1 class="' + css + '">Location-dependent redirect logic in use on this ' + lType + '.</h1><h2>' + type + 'caching info: cannot be cached but plugins on the ' + lType + ' may still be cached.</h2>';
	
	} else {
		
		css = 'zenario_cache_in_use';
		if (zenarioCD.served_from_cache || (slotNameNestId && zenarioCD.slots[slotNameNestId] && zenarioCD.slots[slotNameNestId].served_from_cache)) {
			css = 'zenario_from_cache';
		}
		
		var ruleNotMet = false,
			aRuleNotMet = false,
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
				aRuleNotMet = true;
				css = 'zenario_not_cached';
			}
			
			html += '<tr><th>' + htmlspecialchars(key[i]) + '</th>';
			html += 	'<td>' + (cache_if[i]? 'Yes' : 'No') + '</td>';
			html += 	'<td>' + (zenarioCD.load[i]? 'Yes' : 'No') + '</td>';
			
			if (ruleNotMet) {
				html +='<td class="zenario_cache_result zenario_cache_not_met">Can\'t cache</td></tr>';
			} else {
				html +='<td class="zenario_cache_result zenario_cache_met">OK to cache</td></tr>';
			}
		}
		html += '<tr class="zenario_cache_table_last_row ';
		
		if (aRuleNotMet) {
			html +='zenario_cache_not_met"><th>Result</th><td>&nbsp;</td><td>&nbsp;</td><td>Can\'t cache</td>';
		} else {
			html +='zenario_cache_met"><th>Result</th><td>&nbsp;</td><td>&nbsp;</td><td>OK to cache</td>';
		}
		
		html += '</tr></table>';
		
		
		var cookies, sessionVars, varName,
			found = false;
		
		for (i in by) {
			if (clear_cache_by[i]) {
				if (!found) {
					html += '<h2>' + type + '\ caching info: will be cleared from the cache when:</h2><ul>';
					found = true;
				}
				html += '<li>' + htmlspecialchars(key[i]) + '</li>';
			}
		}
		if (found) {
			html += '</ul>';
		}
		
		if (!_.isEmpty(cookies = zenarioCD.cookies)) {
			html += '<h2>This request has the following cookies:</h2><ul>';
			foreach (cookies as i => varName) {
				html += '<li>' + htmlspecialchars(varName) + '</li>';
			}
			html += '</ul>';
		}
		if (!_.isEmpty(sessionVars = zenarioCD.sessionVars)) {
			html += '<h2>This request has the following session variables:</h2><ul>';
			foreach (sessionVars as i => varName) {
				html += '<li>' + htmlspecialchars(varName) + '</li>';
			}
			html += '</ul>';
		}
		
		
		if (css == 'zenario_not_cached') {
			html = '<h1 class="' + css + '">' + type + htmlspecialchars(pluginDesc) + ' caching info: can be cached, but not in the current situation due to a conflict.</h1>' + html;
		
		} else if (css == 'zenario_from_cache') {
			html = '<h1 class="' + css + '">' + type + htmlspecialchars(pluginDesc) + ' caching info: can be cached and was just served from the cache.</h1>' + html;
		
		} else {
			html = '<h1 class="' + css + '">' + type + htmlspecialchars(pluginDesc) + ' caching info: can be cached. It was served from the database but has now been written to the cache for further requests.</h1>' + html;
		}
		
		html +=
			'<p class="zenario_cache_footnote">' +
				'<span class="zenario_cache_footnote_character">(*)</span> ' +
				'<em>Some session variables and cookies do not affect caching and are ignored in this check.<br/>' +
					'(E.g. <code>z_cookies_accepted</code>, <code>z_user_lang</code>, <code>destCID</code>, <code>extranetUserID</code>.)<br/>' +
					'Check the <code>ze\cache::friendlySessionVar()</code> and <code>ze\\cache::friendlyCookieVar()</code> functions ' +
					'in <code>zenario/autoload/cache.php</code> to see the logic used.' +
				'</em>' +
			'</p>' +
			'<p class="zenario_cache_footnote">' +
				'<span class="zenario_cache_footnote_character">(†)</span> ' +
				'<em>A page is considered unique for caching purposes according to its alias (or its <code>cID</code> and <code>cType</code>). ' +
					'Some plugins also add additional parameters, for example a <code>page</code> number or a <code>search</code> string.' +
				'</em>' +
			'</p>';
	}
	
	html = '<div class="' + ('zenario ' + css).replace(/zenario/g, 'zenario_cache_box') + '">' + html + '</div>';
	return '<x-zenario-cache-info class="' + css + '" title="' + htmlspecialchars(html) + '"></x-zenario-cache-info>';
};


//Return a brief name/description of a plugin/nested plugin to show on a tooltip
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
				case 'zenario_nest':
				case 'zenario_ajax_nest':
					pluginDesc += 'N';
					break;
				case 'zenario_slideshow':
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


//The first time the visitor clicks on the main caching debug icon, add debug icons to every slot/plugin
//on the page.
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
		
		get('zenario_cache_info').innerHTML = zenarioCI.box('', 'Page', !canCache);
		
		zenario.tooltips('x-zenario-cache-info.zenario_cache_info *', options);
		
		zenarioCI.inited = true;
	}
};



}, zenarioCI, zenarioCD);

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
	zenario_users
) {
	"use strict";



zenario_users.saveAsSmartGroup = function(confirm) {
	
	zenarioAB.validate(undefined, undefined, undefined, function() {
		if (!zenarioAB.errorOnBox()) {
			var buttonsHTML = undefined,
				json = zenario_users.JSON('', {
					confirm: confirm,
					save_smart_group: true,
					name: get('name').value,
					values: zenarioAB.getValueArrayofArrays(true)}, true);
			
			if (json && json.message) {
				if (json.confirm_button_message) {
					buttonsHTML = 
						'<input type="button" value="' + htmlspecialchars(json.confirm_button_message) + '" class="submit_selected" onclick="zenario_users.saveAsSmartGroup(true);">' +
						'<input type="button" class="submit" value="' + phrase.cancel + '"/>';
				}
					
				if (json.message) {
					zenarioA.showMessage(json.message, buttonsHTML, json.message_type, false, true);
				}
			}
		}
	});
}


zenario_users.showCreateSmartGroupFromAdvancedSearch = function() {
	return zenarioO.advancedSearch
		&& zenarioO.prefs[zenarioO.voPath]
		&& zenarioO.prefs[zenarioO.voPath].adv_searches[zenarioO.advancedSearch];
}

zenario_users.createSmartGroupFromAdvancedSearch = function() {
	zenario_users.manageSmartGroup(false, zenarioO.prefs[zenarioO.voPath].adv_searches[zenarioO.advancedSearch]);
}

zenario_users.createSmartGroup = function() {
	zenario_users.manageSmartGroup(false);
}

zenario_users.editSmartGroup = function() {
	zenario_users.manageSmartGroup(true);
}

zenario_users.manageSmartGroup = function(itemLevel, values) {
	var id = false;
	
	if (values === undefined) {
		if (itemLevel
		 && (id = zenarioO.getKeyId(true))
		 && (values = zenario_users.JSON('', {load_smart_group: true, id: id}))) {
		} else {
			values = {}
		}
	}
	
	zenarioAB.open(
		'advanced_search',
		{
			storekeeper_path: 'zenario__users/panels/users',
			zenario_pro_features__editing_smart_group: true,
			zenario_pro_features__smart_group_id: id
		},
		undefined,
		values,
		function(key, values) {
			json = zenario_users.JSON('', {
				confirm: true,
				save_smart_group: true,
				id: id,
				name: values.first_tab.name,
				values: JSON.stringify(values)}, true);
			
			if (json && json.id) {
				id = json.id;
			} else {
				id = false;
			}
			
			if (id) {
				if (zenarioO.path == 'zenario__users/panels/smart_groups') {
					zenarioO.refreshToShowItem(id);
				} else {
					zenarioO.go('zenario__users/panels/smart_groups/group_members//' + id + '//');
				}
			}
		});
}




}, zenario_users);
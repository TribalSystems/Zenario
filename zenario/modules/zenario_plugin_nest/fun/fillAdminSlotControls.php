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

if (isset($controls['actions']['settings']['onclick'])) {
	$controls['actions']['settings']['onclick'] = "
		var isVersionControlled = ". engToBoolean($this->isVersionControlled). ";
		if (!isVersionControlled || zenarioA.draft(this.id, true)) {
			var object = {
				organizer_quick: {
					path: 'zenario__modules/panels/modules/item_buttons/view_instances//". $this->moduleId. "//item_buttons/zenario_plugin_nest__view_nested_plugins//". $this->instanceId. "//',
					target_path: 'zenario__modules/hidden_nav/zenario_plugin_nest/panel',
					min_path: 'zenario__modules/hidden_nav/zenario_plugin_nest/panel',
					max_path: 'zenario__modules/hidden_nav/zenario_plugin_nest/panel',
					disallow_refiners_looping_on_min_path: true,
					reload_slot: '". $this->slotName. "',
					reload_admin_toolbar: true}};
			
			zenarioAT.action(object);
		}
		return false;
	";
}

if ($this->setting('author_advice')) {
	$controls['notes']['author_advice']['label'] = nl2br(htmlspecialchars($this->setting('author_advice')));
}
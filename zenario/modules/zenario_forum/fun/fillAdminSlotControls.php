<?php
/*
 * Copyright (c) 2019, Tribal Limited
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


if ($this->forumNotSetUp && ze\priv::check('_PRIV_MANAGE_ITEM_SLOT')) {
	$controls['actions']['create_forum'] = [
		'ord' => 50,
		'label' => ze\admin::phrase('Create a Forum here'),
		'page_modes' => ['edit' => true, 'item' => true, 'layout' => true],
		'onclick' => "
			zenarioA.floatingBox(
				'". ze\admin::phrase('Are you sure you wish to create a new Forum on this Content Item?'). "',
				'". ze\escape::js('
					<input type="button" class="submit_selected" value="'. ze\admin::phrase('Create a Forum here'). '" onclick="
						zenario_forum.AJAX({cID: zenario.cID, cType: zenario.cType, create_new_forum: 1}, true);
						zenario_forum.refreshPluginSlot(\''. $this->slotName. '\');
					"/>
					<input type="button" class="submit" value="'. ze\admin::phrase('Cancel'). '"/>
				'). "',
				'warning');
			return false;"];
	
	if (ze\row::exists(ZENARIO_FORUM_PREFIX. "forums", ['thread_content_id' => 0])
	 || ze\row::exists(ZENARIO_FORUM_PREFIX. "forums", ['new_thread_content_id' => 0])) {
		$controls['actions']['setup_forum'] = [
			'ord' => 51,
			'label' => ze\admin::phrase('Use this page as part of an existing Forum'),
			'page_modes' => ['edit' => true, 'item' => true, 'layout' => true],
			'onclick' => "
				zenarioAB.open('zenario_forum_setup', {cID: zenario.cID, cType: zenario.cType}); return false;"];
	}
}

if (!$this->forumNotSetUp && (ze\priv::check('_PRIV_MANAGE_ITEM_SLOT') || ze\priv::check('_PRIV_MODERATE_USER_COMMENTS'))) {
	$controls['actions']['manage_forum'] = [
		'ord' => 50,
		'label' => ze\admin::phrase('Manage Forum'),
		'page_modes' => ['edit' => true, 'item' => true, 'layout' => true],
		'onclick' => "
			window.open(URLBasePath + 'zenario/admin/organizer.php#zenario__social/nav/forums/panel//". $this->forumId. "'); return false;"];
}
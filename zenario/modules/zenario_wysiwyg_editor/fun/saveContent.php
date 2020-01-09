<?php
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
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');


if (!ze\priv::check('_PRIV_EDIT_DRAFT', ze::$cID, ze::$cType)) {
	echo ze\admin::phrase('Your changes were not saved, as you have been logged out.');

} elseif (!ze::$isDraft) {
	echo ze\admin::phrase('Your changes were not saved, as this version has already been Published.');

} else {
	
	$html = ze\ring::decodeIdForOrganizer($_POST['content__content'] ?? '');
	//N.b. encodeItemIdForOrganizer() was called on the HTML, to avoid sending RAW HTML over post and potentially
	//triggering Cloudflare to blocks it, so we need to call decodeIdForOrganizer() to decode it.
	
	ze\file::addImageDataURIsToDatabase($html);
	
	//Save the field in the plugin_settings table.
	ze\row::set(
		'plugin_settings',
		['is_content' => 'version_controlled_content', 'format' => 'translatable_html', 'value' => $html],
		['name' => 'html', 'instance_id' => $this->instanceId, 'egg_id' => $this->eggId]);
	
	ze\contentAdm::syncInlineFileContentLink($this->cID, $this->cType, $this->cVersion);
	
	$v = [];
	$v['last_modified_datetime'] = ze\date::now();
	$v['last_author_id'] = $_SESSION['admin_userid'] ?? false;
	ze\row::update('content_item_versions', $v, ['id' => ze::$cID, 'type' => ze::$cType, 'version' => ze::$cVersion]);

	
	if (($_POST['_sync_summary'] ?? false) && !$this->summaryLocked($this->cID, $this->cType, $this->cVersion)) {
		$this->syncSummary($this->cID, $this->cType, $this->cVersion, zenario_wysiwyg_editor::generateSummary($html));
	}
}
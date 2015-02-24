<?php
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
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');


if (!checkPriv('_PRIV_EDIT_DRAFT', cms_core::$cID, cms_core::$cType)) {
	echo adminPhrase('Your changes were not saved, as you have been logged out.');

} elseif (!cms_core::$isDraft) {
	echo adminPhrase('Your changes were not saved, as this version has already been Published.');

} else {
	
	addImageDataURIsToDatabase($_POST['content__content']);
	
	//Save the field in the plugin_settings table.
	setRow(
		'plugin_settings',
		array('is_content' => 'version_controlled_content', 'format' => 'translatable_html', 'value' => $_POST['content__content']),
		array('name' => 'html', 'instance_id' => $this->instanceId, 'nest' => $this->eggId));
	
	syncInlineFileContentLink($this->cID, $this->cType, $this->cVersion);
	
	$v = array();
	$v['last_modified_datetime'] = now();
	$v['last_author_id'] = session('admin_userid');
	updateRow('versions', $v, array('id' => cms_core::$cID, 'type' => cms_core::$cType, 'version' => cms_core::$cVersion));

	
	if (post('_sync_summary') && !$this->summaryLocked($this->cID, $this->cType, $this->cVersion)) {
		$this->syncSummary($this->cID, $this->cType, $this->cVersion, zenario_wysiwyg_editor::generateSummary($_POST['content__content']));
	}
}
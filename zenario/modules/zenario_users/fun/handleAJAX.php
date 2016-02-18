<?php
/*
 * Copyright (c) 2016, Tribal Limited
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


if (post('load_smart_group') && checkPriv('_PRIV_VIEW_USER')) {
	header('Content-Type: text/javascript; charset=UTF-8');
	echo getRow('smart_groups', 'values', post('id'));
	exit;

} elseif (post('save_smart_group') && checkPriv('_PRIV_MANAGE_GROUP')) {
	$json = array();
	$key = array();
	$values = array();
			
	if (post('id')) {
			$key = array('id' => post('id'));
			$json['exists'] = true;
			$values['name'] = post('name');

	} else {
		$key = array('name' => post('name'));
		$json['exists'] = checkRowExists('smart_groups', $key);
	}

	if ($json['exists'] && !post('confirm')) {
		$json['message'] = adminPhrase('The Smart Group "[[name]]" already exists, do you want to overwrite it?', $key);
		$json['message_type'] = 'warning';
		$json['confirm_button_message'] = adminPhrase('Overwrite Smart Group');

	} else {
		exitIfNotCheckPriv('_PRIV_MANAGE_GROUP');
		$values['values'] = post('values');
		$values['last_modified_on'] = now();
		$values['last_modified_by'] = adminId();

		if ($json['exists']) {
			$json['message'] = adminPhrase('Updated the Smart Group named "[[name]]".', $key);
			$json['message_type'] = 'success';
					
		} else {
			$values['created_on'] = now();
			$values['created_by'] = adminId();

			$json['message'] = adminPhrase('Created a Smart Group named "[[name]]".', $key);
			$json['message_type'] = 'success';
		}
				
		$json['id'] = setRow('smart_groups', $values, $key);
	}

	header('Content-Type: text/javascript; charset=UTF-8');
	echo json_encode($json);
	exit;


} elseif (post('delete') && checkPriv('_PRIV_MANAGE_GROUP')) {
	foreach (explode(',', $ids) as $id) {
		deleteRow('smart_groups', $id);
		sendSignal("eventSmartGroupDeleted",array("smartGroupId" => $id));
	}
}




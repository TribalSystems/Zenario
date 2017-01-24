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

switch ($path) {
	case 'zenario_email_template':
		
		addAbsURLsToAdminBoxField($box['tabs']['body']['fields']['body']);
		
		
		$files = array();
		$columns = array();
		
		if (engToBooleanArray($box['tabs']['meta_data'], 'edit_mode', 'on')) {
			exitIfNotCheckPriv('_PRIV_MANAGE_EMAIL_TEMPLATE');
			$columns['template_name'] = $values['meta_data/template_name'];
			$columns['subject'] = $values['meta_data/subject'];
			$columns['email_address_from'] = $values['meta_data/email_address_from'];
			$columns['email_name_from'] = $values['meta_data/email_name_from'];
			
			$columns['debug_email_address'] =
				($columns['debug_override'] = $values['meta_data/debug_override'])? $values['meta_data/debug_email_address'] : '';

			$columns['cc_email_address'] =
				($columns['send_cc'] = $values['meta_data/send_cc'])? $values['meta_data/cc_email_address'] : '';

			$columns['bcc_email_address'] =
				($columns['send_bcc'] = $values['meta_data/send_bcc'])? $values['meta_data/bcc_email_address'] : '';
		}
		
		if (engToBooleanArray($box['tabs']['body'], 'edit_mode', 'on')) {
			exitIfNotCheckPriv('_PRIV_MANAGE_EMAIL_TEMPLATE');
			$columns['body'] = $values['body/body'];
			$htmlChanged = false;
			addImageDataURIsToDatabase($columns['body'], absCMSDirURL());
			syncInlineFileLinks($files, $columns['body'], $htmlChanged);
		}
		
		if (engToBooleanArray($box['tabs']['advanced'], 'edit_mode', 'on')) {
			exitIfNotCheckPriv('_PRIV_MANAGE_EMAIL_TEMPLATE');
			$columns['head'] = $values['advanced/head'];
		}
		
		if (!empty($columns)) {
			$columns['date_modified'] = now();
			$columns['modified_by_id'] = adminId();
			
			if ($box['key']['id'] && !$box['key']['duplicate']) {
				updateRow('email_templates', $columns, array('code' => $box['key']['id']));
			} else {
				$columns['date_created'] = now();
				$columns['created_by_id'] = adminId();
				$columns['code'] = microtime(). session_id();
				$box['key']['id'] = $box['key']['numeric_id'] = insertRow('email_templates', $columns);
				updateRow('email_templates', array('code' => $box['key']['id']), array('id' => $box['key']['id']));
			}
		}
		
		//Record the images used in this email template.
		$key = array('foreign_key_to' => 'email_template', 'foreign_key_id' => $box['key']['numeric_id'], 'foreign_key_char' => $box['key']['id']);
		syncInlineFiles($files, $key, $keepOldImagesThatAreNotInUse = false);
		
		break;
}

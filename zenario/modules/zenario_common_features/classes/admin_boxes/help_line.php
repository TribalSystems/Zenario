<?php
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
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');


class zenario_common_features__admin_boxes__help_line extends ze\moduleBaseClass {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		$box['key']['isHead'] = ZENARIO_IS_HEAD;
		
		
		$adminId = ze\admin::id();
        $details = ze\row::get('admins', true, $adminId);
		$box['tabs']['site']['fields']['name']['value'] = $details['first_name'].' '.$details['last_name'];
		$box['tabs']['site']['fields']['email']['value'] = $details['email'];
		
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
	}


	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		
		
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		
		if(defined('EMAIL_ADDRESS_GLOBAL_SUPPORT')){
			$addressToOverriddenBy = '';
			$subject = 'Zenario help query';
			$currUrl = $box['key']['currentUrl'];
			$body ='';
			$body .= '<p>Dear Admin,</p>';
			$body .= '<p>Please find below query.</p>';
			$body .= '<p>Name: '.$values['site/name'].'</p>';
			$body .= '<p>Email: '.$values['site/email'].'</p>';
			if($values['site/current_url'] == 'current_location')
			{
				$body .= '<p>Current Url: '.$currUrl.'</p>';
			}else {
				
				$body .= '<p>General Question</p>';
			}
			$body .= '<p>Query: '.nl2br($values['site/query_message']).'</p>';
			$toaddress = EMAIL_ADDRESS_GLOBAL_SUPPORT;
		
			ze\server::sendEmail(
				$subject,
				$body,
				$toaddress,
				$addressToOverriddenBy
			);
			$msg = '<!--Message_Type:Success-->';
			$msg .= '<p>'. ze\admin::phrase("Thanks for your message, we'll be in touch in the next 1 working day."). '</p>';
			
			ze\tuix::closeWithFlags(['close_with_message' => $msg]);
			exit;
			
		}
		
		
	}
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		if(!$_GET['refinerName'])
		{
			ze\tuix::closeWithFlags(['go_to_url' => $box['key']['currentUrl'] ]);
			exit;
		}
	}
}

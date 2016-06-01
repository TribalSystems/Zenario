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

switch ($path) {
	case 'plugin_settings':
		$box['tabs']['first_tab']['fields']['login_with']['read_only'] = !setting('user_use_screen_name');
		$defaultLangId = setting('default_language');
		$values['invalid_email_error_text'] = 
			getRow("visitor_phrases", "local_text", array("code" => "_ERROR_INVALID_EXTRANET_EMAIL", "language_id" => $defaultLangId));
		
		$values['screen_name_required_error_text'] = 
			getRow("visitor_phrases", "local_text", array("code" => "_ERROR_EXTRANET_SCREEN_NAME", "language_id" => $defaultLangId));
		
		$values['email_address_required_error_text'] = 
			getRow("visitor_phrases", "local_text", array("code" => "_ERROR_EXTRANET_EMAIL", "language_id" => $defaultLangId));
			
		$values['password_required_error_text'] = 
			getRow("visitor_phrases", "local_text", array("code" => "_ERROR_EXTRANET_PASSWORD", "language_id" => $defaultLangId));
		
		$values['no_new_password_error_text'] = 
			getRow("visitor_phrases", "local_text", array("code" => "_ERROR_NEW_PASSWORD", "language_id" => $defaultLangId));
		$values['no_new_repeat_password_error_text'] = 
			getRow("visitor_phrases", "local_text", array("code" => "_ERROR_REPEAT_NEW_PASSWORD", "language_id" => $defaultLangId));
			
		break;
}

?>
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
	case 'zenario_forum_setup':
		$box['tabs']['forum']['fields']['view_thread_forum']['hidden'] =
			!$values['forum/view_thread'];
		$box['tabs']['forum']['fields']['new_thread_forum']['hidden'] =
			!$values['forum/new_thread'];
		
		break;
	
	case 'plugin_settings':
		$box['tabs']['posting']['fields']['restrict_new_thread_to_group']['hidden'] = 
			!$values['posting/enable_new_thread_restrictions'];
		
		$box['tabs']['notification']['fields']['notification_email_address']['hidden'] = 
		$box['tabs']['notification']['fields']['new_thread_notification_email_template']['hidden'] = 
		$box['tabs']['notification']['fields']['post_notification_email_template']['hidden'] = 
			!$values['notification/send_notification_email'];
		
		$box['tabs']['notification']['fields']['new_thread_subs_email_template']['hidden'] = 
			!$values['notification/enable_forum_subs'];
		
		$box['tabs']['notification']['fields']['post_subs_email_template']['hidden'] = 
			!$values['notification/enable_thread_subs'];
		
		$this->showHideImageOptions($fields, $values, 'first_tab', false, 'image_thumbnail_', false, 'Image thumbnail size (width × height):');
		
		zenario_comments::formatAdminBox($path, $settingGroup, $box, $fields, $values, $changes);
		break;
}

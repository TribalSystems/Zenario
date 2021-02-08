<?php
/*
 * Copyright (c) 2021, Tribal Limited
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

$mergeFields = []; 

if (!empty($_SESSION['extranetUserID']) && ze\module::inc('zenario_users')) {
	$user = ze\user::details($_SESSION['extranetUserID']);
} else {
	$user = ['email' => '', 'screen_name' => ze\admin::phrase('Anonymous')];
}

$request = '';
if ($_POST['forum_thread'] ?? false) {
   $request = '&forum_thread='. (int) ($_POST['forum_thread'] ?? false);
}

$mergeFields['link'] = $this->linkToItem($this->cID, $this->cType, true, $request) ;
$mergeFields['poster_screen_name'] = $this->getUserScreenNameLink($this->post['poster_id']);

if ($this->post['updater_id'] !== null) {
	$mergeFields['last_editor_screen_name'] = $this->getUserScreenNameLink($this->post['updater_id']);
}

$mergeFields['reporter_screen_name'] =  $this->getUserScreenNameLink($user['id'] ?? false);
$mergeFields['text_message'] = $this->post['message_text'];
$mergeFields['report_message'] = zenario_anonymous_comments::sanitiseHTML($_POST['comm_message'] ?? false, true, true);
$mergeFields['cms_url'] = ze\link::absolute();


zenario_email_template_manager::sendEmailsUsingTemplate(
	$this->setting('email_address_for_reports'),
	$this->setting('email_template_for_reports'),
	$mergeFields,
	[],
	[],
	['text_message' => true, 'report_message' => true, 'poster_screen_name' => true, 'last_editor_screen_name' => true, 'reporter_screen_name' => true]);

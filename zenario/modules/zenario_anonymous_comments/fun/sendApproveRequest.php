<?php
/*
 * Copyright (c) 2014, Tribal Limited
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

$mergeFields = array(); 

if (!$userId && $name && $email) {
	$formFields['poster_username'] =
	$mergeFields['poster_screen_name'] = htmlspecialchars($name. ' ('. $email. ')');

} elseif (!$userId && $name) {
	$formFields['poster_username'] =
	$mergeFields['poster_screen_name'] = htmlspecialchars($name);

} elseif (!$userId && $email) {
	$formFields['poster_username'] =
	$mergeFields['poster_screen_name'] = htmlspecialchars($email);

} else {
	$mergeFields['poster_screen_name'] = $this->getUserScreenNameLink($userId);
}

$mergeFields['link'] = $this->linkToItem($this->cID, $this->cType, true);
$mergeFields['message'] = htmlspecialchars($messageText);
$mergeFields['page_title'] = getItemTitle($this->cID, $this->cType);
$mergeFields['cms_url'] = absCMSDirURL();



if (inc('zenario_email_template_manager')) {
	zenario_email_template_manager::sendEmailsUsingTemplate(
		$this->setting('email_address_for_reports'),
		$this->setting('email_template_for_approve_requests'),
		$mergeFields,
		array(),
		array(),
		true);
}
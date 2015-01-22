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


//IE6 doesn't like AJAX reloads with PunyMCE in them; give it a full page load
if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 6') !== false) {
	foreach ($_GET as $var => $value) {
		$this->registerGetRequest($var);
	}
	$this->forcePageReload();
}


//Show any errors from a previous submissin
if (!empty($this->postingErrors)) {
	$this->sections['Post_Errors'] = true;
	$this->sections['Post_Error'] = $this->postingErrors;
}


$this->sections['Post_Message'] = array();
$this->showPostScreenTopFields($titleText);


$this->sections['Post_Message']['Label_Text'] = $labelText;
$this->sections['Post_Message']['Submit_Button_Text'] = $submitButtonText;


if ($titleText !== false) {
	$this->sections['Post_Message']['Cancel_Link'] = $this->linkToItemAnchor($this->forum['forum_content_id'], $this->forum['forum_content_type'], false);
} else {
	$this->sections['Post_Message']['Cancel_Link'] = $this->refreshPluginSlotAnchor('&comm_page='. $this->page. '&forum_thread='. $this->thread['id']);
}

$this->showUserInfo($this->sections['Post_Message'], $this->sections, userId());


if (isset($_POST['comm_message'])) {
	$this->sections['Post_Message']['Post_Text'] = htmlspecialchars($_POST['comm_message']);

} elseif ($quoteMode == 'edit' && !empty($this->post['id'])) {
	$this->sections['Post_Message']['Post_Text'] =  htmlspecialchars($this->post['message_text']);
	
} elseif ($quoteMode == 'quote' && $this->canQuotePost() && !empty($this->post['id'])) {
	$this->sections['Post_Message']['Post_Text'] =
		htmlspecialchars(
			'[quote]'.
				'[b]'. $this->phrase('_SAID', array('user' => $this->getUserScreenName($this->post['poster_id']))). '[/b]'. "\n".
				$this->post['message_text'].
			"[/quote]\n\n"
		);

} else {
	$this->sections['Post_Message']['Post_Text'] = '';
}

if (!$this->sections['Post_Message']['Post_Text'] && (strpos($_SERVER['HTTP_USER_AGENT'], 'Firefox') !== false)) {
	$this->sections['Post_Message']['Post_Text'] = '[[HIDE_ME]]';
}

$this->sections['Post_Message']['Post_Text'] = '<textarea id="editor__'. $this->containerId. '" name="comm_message">'. $this->sections['Post_Message']['Post_Text']. '</textarea>';

$form_attributes = ' class="'. htmlspecialchars(request('comm_request')). '"';
if($this->form_encode_type){
	$form_attributes .= ' enctype="' . $this->form_encode_type . '"';
}

$this->sections['Post_Message']['Open_Form'] = $this->openForm($onSubmit, $form_attributes). 
	$this->remember('comm_request').
	$this->remember('comm_page').
	$this->remember('comm_enter_text').
	$this->remember('comm_post').
	$this->remember('forum_thread');
$this->sections['Post_Message']['Close_Form'] = $this->closeForm();


if (request('comm_request') != 'report_post') {
	if ($this->setting('comments_require_approval') && !$this->modPrivs) {
		$this->sections['Show_Post_Screening_Notice'] = true;
	}
}

if ($quoteMode != 'quote'){
	$this->getExtraPostInfo($this->post, $this->sections['Post_Message'], $this->sections , true);
}

if (post('comm_enter_text')) {
	//Hack for a bug in Firefox and reloading TinyMCE via AJAX
	$this->callScript(
		'zenario_anonymous_comments', 'loadWithDelay',
		$this->containerId,
		(int) $this->setting('enable_colours'),
		(int) $this->setting('enable_images'),
		(int) $this->setting('enable_emoticons'),
		(int) $this->setting('enable_links'));
} else {
	$this->callScript(
		'zenario_anonymous_comments', 'load',
		$this->containerId,
		(int) $this->setting('enable_colours'),
		(int) $this->setting('enable_images'),
		(int) $this->setting('enable_emoticons'),
		(int) $this->setting('enable_links'));
}


if (request('comm_request') == 'post_reply') {
	if ($this->setting('enable_captcha')) {
		$this->sections['Captcha'] = true;
		$this->sections['Post_Message']['Captcha'] = $this->captcha();
	}
}

<?php
/*
 * Copyright (c) 2018, Tribal Limited
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
	$this->sections['Post_Message']['Post_Text'] = zenario_anonymous_comments::sanitiseHTML($_POST['comm_message'], $this->setting('enable_images'), $this->setting('enable_links'));

} elseif ($quoteMode == 'edit' && !empty($this->post['id'])) {
	$this->sections['Post_Message']['Post_Text'] =  zenario_anonymous_comments::sanitiseHTML($this->post['message_text'], $this->setting('enable_images'), $this->setting('enable_links'));
	
} elseif ($quoteMode == 'quote' && $this->canQuotePost() && !empty($this->post['id'])) {
	$this->sections['Post_Message']['Post_Text'] =
		zenario_anonymous_comments::sanitiseHTML(
			'<blockquote>'.
				'<b>'. $this->phrase('_SAID', array('user' => $this->getUserScreenName($this->post['poster_id']))). '</b>'.
				"<br/>".
				$this->post['message_text'].
			"</blockquote><p>&nbsp;</p>"
		, $this->setting('enable_images'), $this->setting('enable_links'));

} else {
	$this->sections['Post_Message']['Post_Text'] = '';
}

$this->sections['Post_Message']['Post_Text'] = '
	<textarea id="value_for_'. $this->getEditorId(). '" name="comm_message" style="display: none;"></textarea>
	<div id="toolbar_container_for_'. $this->getEditorId(). '" class="zenario_tinymce_toolbar_container"></div>
	<div id="'. $this->getEditorId(). '" name="comm_message">'. $this->sections['Post_Message']['Post_Text']. '</div>';

$form_attributes = ' class="'. htmlspecialchars($_REQUEST['comm_request'] ?? false). '"';
if($this->form_encode_type){
	$form_attributes .= ' enctype="' . $this->form_encode_type . '"';
}


$onSubmit .= "
	zenario.get('value_for_". jsEscape($this->getEditorId()). "').value =
		zenario.tinyMCEGetContent(tinyMCE.get('". jsEscape($this->getEditorId()). "'));";

$this->sections['Post_Message']['Open_Form'] = $this->openForm($onSubmit, $form_attributes). 
	$this->remember('comm_request').
	$this->remember('comm_page').
	$this->remember('comm_enter_text').
	$this->remember('comm_post').
	$this->remember('forum_thread');
$this->sections['Post_Message']['Close_Form'] = $this->closeForm();


if (($_REQUEST['comm_request'] ?? false) != 'report_post') {
	if ($this->setting('comments_require_approval') && !$this->modPrivs) {
		$this->sections['Show_Post_Screening_Notice'] = true;
	}
}

if (!empty($this->post) && $quoteMode != 'quote') {
	$this->getExtraPostInfo($this->post, $this->sections['Post_Message'], $this->sections , true);
}

//if ($_POST['comm_enter_text'] ?? false) {
//	//Hack for a bug in Firefox and reloading TinyMCE via AJAX
//	$this->callScript(
//		'zenario_anonymous_comments', 'loadWithDelay',
//		$this->getEditorId(),
//		(int) $this->setting('enable_images'),
//		(int) $this->setting('enable_links'));
//} else {
	$this->callScript(
		'zenario_anonymous_comments', 'load',
		$this->getEditorId(),
		(int) $this->setting('enable_images'),
		(int) $this->setting('enable_links'));
//}


if (($_REQUEST['comm_request'] ?? false) == 'post_reply') {
	if ($this->setting('enable_captcha')) {
		$this->sections['Captcha'] = true;
		$this->sections['Post_Message']['Captcha'] = $this->captcha();
	}
}

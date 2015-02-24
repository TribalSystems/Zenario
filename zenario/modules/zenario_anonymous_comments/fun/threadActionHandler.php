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


$failure = false;

if (post('comm_request') == 'post_reply' && $this->canMakePost()) {
	$name = '';
	$email = '';
	
	if ($this->setting('enable_captcha')) {
		if (!$this->checkCaptcha()) {
			$failure = true;
			$this->postingErrors[] = array('Error' => $this->phrase('_CAPTCHA_INVALID'));
		}
	}
	
	if ($this->setting('show_name')) {
		if (empty($_POST['comm_name'])) {
			$failure = true;
			$this->postingErrors[] = array('Error' => $this->phrase('_ERROR_NAME'));
		} else {
			$name = $_POST['comm_name'];
		}
	}
	
	if ($this->setting('show_email')) {
		if (empty($_POST['comm_email'])) {
			$failure = true;
			$this->postingErrors[] = array('Error' => $this->phrase('_ERROR_EMAIL'));
		
		} elseif (!validateEmailAddress($_POST['comm_email'])) {
			$failure = true;
			$this->postingErrors[] = array('Error' => $this->phrase('_ERROR_INVALID_EMAIL'));
		
		} else {
			$email = $_POST['comm_email'];
		}
	}
	
	if (empty($_POST['comm_message'])) {
		$failure = true;
		$this->postingErrors[] = array('Error' => $this->phrase('_ERROR_MESSAGE'));
	}
	
	if (!$failure) {
		$this->addReply(userId(), $_POST['comm_message'], 0, $name, $email);
	}

} elseif (post('comm_request') == 'delete_post' && $this->canDeletePost($this->post)) {
	$this->deletePost();
	
} elseif (post('comm_request') == 'approve_post' && $this->canApprovePost()) {
	if (request('checksum') == md5($this->post['message_text'])) {
		$this->approvePost();
	} else {
		$failure = true;
	}
	
} elseif (post('comm_request') == 'edit_first_post' && $this->canEditFirstPost($this->post)) {
	
	if (empty($_POST['comm_title'])) {
		//complain about required fields
		$failure = true;
		$this->postingErrors[] = array('Error' => $this->phrase('_ERROR_TITLE'));
	}
	if (empty($_POST['comm_message'])) {
		//complain about required fields
		$failure = true;
		$this->postingErrors[] = array('Error' => $this->phrase('_ERROR_MESSAGE'));
	}
	
	if (!$failure) {
		$this->editFirstPost(userId(), $_POST['comm_message'], $_POST['comm_title']);
	}
	
} elseif (post('comm_request') == 'edit_post' && $this->canEditPost($this->post)) {
	if ($_POST['comm_message']) {
		$this->editPost(userId(), $_POST['comm_message']);
	} else {
		//complain about required fields
		$failure = true;
		$this->postingErrors[] = array('Error' => $this->phrase('_ERROR_MESSAGE'));
	}
	
} elseif (post('comm_request') == 'lock_thread' && $this->canLockThread()) {
	$this->lockThread(1);
	
} elseif (post('comm_request') == 'unlock_thread' && $this->canUnlockThread()) {
	$this->lockThread(0);
	
} elseif (post('comm_request') == 'delete_thread' && $this->canDeleteThread()) {
	$this->deleteThread();
	return;

} elseif (post('comm_request') == 'subs_thread' && $this->canSubsThread() && !$this->hasSubsThread()) {
	$this->subs(true);

} elseif (post('comm_request') == 'unsubs_thread' && $this->canSubsThread()) {
	$this->subs(false);
	
} elseif (post('comm_request') == 'report_post' && $this->canReportPost()) {
	if (post('comm_message')) {
		$this->reportPost();
	} else {
		//complain about required fields
		$failure = true;
		$this->postingErrors[] = array('Error' => $this->phrase('_ERROR_MESSAGE'));
	}
	
}

//Clear the requests made and reload the thread info if the action was successful
if (!$failure) {
	$this->clearRequest('comm_post');
	$this->clearRequest('comm_request');
	$this->clearRequest('comm_confirm');
	$this->clearRequest('comm_enter_text');
	$this->clearRequest('checksum');
	
	$this->loadThreadInfo();
	$this->loadPagination();
	$this->loadPosts();
}

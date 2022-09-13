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


$failure = false;

if (($_POST['comm_request'] ?? false) == 'post_reply' && $this->canMakePost()) {
	$name = '';
	$email = '';
	
	if ($this->enableCaptcha()) {
		if (!$this->checkCaptcha2()) {
			$failure = true;
			$this->postingErrors[] = ['Error' => $this->phrase('Please correctly verify that you are human.')];
		}
	}

	if ($this->setting('enable_links') && $this->setting('add_nofollow_to_hyperlinks')) {
		$this->addNofollowToHyperlinks($_POST['comm_message']);
	}

	if ($this->setting('enable_links') && $this->setting('limit_hyperlinks_or_not')) {
		$hyperlinkLimit = $this->setting('hyperlink_limit');
		if ($this->postHasTooManyHyperlinks($_POST['comm_message'], $hyperlinkLimit)) {
			$failure = true;
			$this->postingErrors[] = ['Error' => $this->phrase('Too many hyperlinks in post (max allowed: [[hyperlink_limit]])', ['hyperlink_limit' => (int) $hyperlinkLimit])];
		}
	}
	
	if ($this->setting('show_name')) {
		if (empty($_POST['comm_name'])) {
			$failure = true;
			$this->postingErrors[] = ['Error' => $this->phrase('Please enter your name.')];
		} else {
			$name = $_POST['comm_name'];
		}
	}
	
	if ($this->setting('show_email')) {
		if (empty($_POST['comm_email'])) {
			$failure = true;
			$this->postingErrors[] = ['Error' => $this->phrase('Please enter your email address.')];
		
		} elseif (!ze\ring::validateEmailAddress($_POST['comm_email'])) {
			$failure = true;
			$this->postingErrors[] = ['Error' => $this->phrase('Please enter a valid email address.')];
		
		} else {
			$email = $_POST['comm_email'];
		}
	}
	
	if (empty($_POST['comm_message'])) {
		$failure = true;
		$this->postingErrors[] = ['Error' => $this->phrase('Please enter a message.')];
	} else {
		$sanitisedMessage = ze\escape::sql(zenario_anonymous_comments::sanitiseHTML($_POST['comm_message'], $this->setting('enable_images'), $this->setting('enable_links')));
		if (strlen($sanitisedMessage) > 65535) {
			$failure = true;
			$this->postingErrors[] = ['Error' => $this->phrase('The message is too long.')];
		}
	}
	
	if (!$failure) {
		$this->addReply(ze\user::id(), $_POST['comm_message'], 0, $name, $email);
	}

} elseif (($_POST['comm_request'] ?? false) == 'delete_post' && $this->canDeletePost($this->post)) {
	$this->deletePost();
	
} elseif (($_POST['comm_request'] ?? false) == 'approve_post' && $this->canApprovePost()) {
	if (($_REQUEST['checksum'] ?? false) == md5($this->post['message_text'])) {
		$this->approvePost();
	} else {
		$failure = true;
	}
	
} elseif (($_POST['comm_request'] ?? false) == 'edit_first_post' && $this->canEditFirstPost($this->post)) {
	
	if (empty($_POST['comm_title'])) {
		//complain about required fields
		$failure = true;
		$this->postingErrors[] = ['Error' => $this->phrase('Please enter a title.')];
	}
	
	if (empty($_POST['comm_message'])) {
		//complain about required fields
		$failure = true;
		$this->postingErrors[] = ['Error' => $this->phrase('Please enter a message.')];
	} else {
		if ($this->setting('enable_links') && $this->setting('add_nofollow_to_hyperlinks')) {
			$this->addNofollowToHyperlinks($_POST['comm_message']);
		}

		$sanitisedMessage = ze\escape::sql(zenario_anonymous_comments::sanitiseHTML($_POST['comm_message'], $this->setting('enable_images'), $this->setting('enable_links')));
		if (strlen($sanitisedMessage) > 65535) {
			$failure = true;
			$this->postingErrors[] = ['Error' => $this->phrase('The message is too long.')];
		}
	}

	if ($this->setting('enable_links') && $this->setting('limit_hyperlinks_or_not')) {
		$hyperlinkLimit = $this->setting('hyperlink_limit');
		if ($this->postHasTooManyHyperlinks($_POST['comm_message'], $hyperlinkLimit)) {
			$failure = true;
			$this->postingErrors[] = ['Error' => $this->phrase('Too many hyperlinks in post (max allowed: [[hyperlink_limit]])', ['hyperlink_limit' => (int) $hyperlinkLimit])];
		}
	}
	
	if (!$failure) {
		$this->editFirstPost(ze\user::id(), $_POST['comm_message'], $_POST['comm_title']);
	}
	
} elseif (($_POST['comm_request'] ?? false) == 'edit_post' && $this->canEditPost($this->post)) {
	if ($_POST['comm_message']) {
		if ($this->setting('enable_links') && $this->setting('add_nofollow_to_hyperlinks')) {
			$this->addNofollowToHyperlinks($_POST['comm_message']);
		}

		if ($this->setting('enable_links') && $this->setting('limit_hyperlinks_or_not')) {
			$hyperlinkLimit = $this->setting('hyperlink_limit');
			if ($this->postHasTooManyHyperlinks($_POST['comm_message'], $hyperlinkLimit)) {
				$failure = true;
				$this->postingErrors[] = ['Error' => $this->phrase('Too many hyperlinks in post (max allowed: [[hyperlink_limit]])', ['hyperlink_limit' => (int) $hyperlinkLimit])];
			}
		}

		$sanitisedMessage = ze\escape::sql(zenario_anonymous_comments::sanitiseHTML($_POST['comm_message'], $this->setting('enable_images'), $this->setting('enable_links')));
		if (strlen($sanitisedMessage) > 65535) {
			$failure = true;
			$this->postingErrors[] = ['Error' => $this->phrase('The message is too long.')];
		} elseif (!$failure) {
			$this->editPost(ze\user::id(), $_POST['comm_message'], $_POST['comm_name'] ?? '');
		}
	} else {
		//complain about required fields
		$failure = true;
		$this->postingErrors[] = ['Error' => $this->phrase('Please enter a message.')];
	}
	
} elseif (($_POST['comm_request'] ?? false) == 'lock_thread' && $this->canLockThread()) {
	$this->lockThread(1);
	
} elseif (($_POST['comm_request'] ?? false) == 'unlock_thread' && $this->canUnlockThread()) {
	$this->lockThread(0);
	
} elseif (($_POST['comm_request'] ?? false) == 'delete_thread' && $this->canDeleteThread()) {
	$this->deleteThread();
	return;

} elseif (($_POST['comm_request'] ?? false) == 'subs_thread' && $this->canSubsThread() && !$this->hasSubsThread()) {
	$this->subs(true);

} elseif (($_POST['comm_request'] ?? false) == 'unsubs_thread' && $this->canSubsThread()) {
	$this->subs(false);
	
} elseif (($_POST['comm_request'] ?? false) == 'report_post' && $this->canReportPost()) {
	if ($_POST['comm_message'] ?? false) {
		$sanitisedMessage = ze\escape::sql(zenario_anonymous_comments::sanitiseHTML($_POST['comm_message'], $this->setting('enable_images'), $this->setting('enable_links')));
		if (strlen($sanitisedMessage) > 65535) {
			$failure = true;
			$this->postingErrors[] = ['Error' => $this->phrase('The message is too long.')];
		} else {
			$this->reportPost();
		}
	} else {
		//complain about required fields
		$failure = true;
		$this->postingErrors[] = ['Error' => $this->phrase('Please enter a message.')];
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

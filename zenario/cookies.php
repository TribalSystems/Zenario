<?php
/*
 * Copyright (c) 2025, Tribal Limited
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


header('Content-Type: text/plain; charset=UTF-8');
require 'basicheader.inc.php';


//Check if cookies are enabled. Note this might need to be called twice in some cases
if (isset($_REQUEST['check_cookies_enabled'])) {
	
	if (empty($_COOKIE[ze\cookie::sessionName()])) {
		ze\cookie::startSession();
	} else {
		echo 1;
	}
	
	exit;
}

ze\cookie::startSession();
if (!empty($_REQUEST['clear_admin_cookie'])) {
	
	//Clear the cookies that remember that an administrator previously logged into the site.
	ze\cookie::clear('z_admin_last_username');
	ze\cookie::clear('z_admin_captcha_completed');
	ze\cookie::clear('z_admin_login_shown');
	
	//Also clear every 2FA code stored on the machine
	foreach (array_keys($_COOKIE) as $name) {
		if (ze\ring::chopPrefix('z_admin_2fa_', $name) !== false) {
			ze\cookie::clear($name);
		}
	}

} elseif (!empty($_REQUEST['accept_cookies']) || !empty($_REQUEST['cookie_accept_all'])) {
	ze\cookie::clear('z_cookies_accepted');
	ze\cookie::setConsent();

} elseif (!empty($_REQUEST['cookie_save_preferences'])) {
	ze\cookie::clear('z_cookies_accepted');

	$cookieTypes = [];
	if (isset($_REQUEST['functionality'])) {
		$cookieTypes[] = 'functionality';
	}
	if (isset($_REQUEST['analytics'])) {
		$cookieTypes[] = 'analytics';
	}
	if (isset($_REQUEST['social_media'])) {
		$cookieTypes[] = 'social_media';
	}
	
	if (!empty($cookieTypes)) {
		ze\cookie::setConsent(implode(',', $cookieTypes));
	} else {
		ze\cookie::setNoConsent();
	}

} else {
	//Also covers the accept_necessary_cookies_only case
	ze\cookie::setNoConsent();
}

if (empty($_REQUEST['ajax'])) {
	//Try to send the visitor back to where they just came from using the referer.
	//If the hash variable is in the request, then also try to restore the anchor they had in their URL too.
	if ($returnLink = $_SERVER['HTTP_REFERER'] ?? null) {
		
		if ($hash = $_REQUEST['hash'] ?? null) {
			if ($hash[0] == '#') {
				$returnLink .= $hash;
			}
		}
		
		header('location: '. $returnLink);
	} else {
		header('location: ../');
	}
}
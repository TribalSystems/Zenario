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
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');

//This function does one of two mutually exclusive things.


//1. If the admin has hidden the admin toolbar, add a button to get it back.
if ($includeAdminLinks && $isAdmin && !$includeAdminToolbar) {
	
	$offset = (int) ze::setting('admin_link_logo_offset', $useCache = true, $default = 30);
	$pos = ze::setting('admin_link_logo_pos', $useCache = true, $default = 'allt allr');
	
	if (substr($pos, 0, 4) == 'allb') {
		$style = 'bottom:'. $offset. 'px;';
	} else {
		$style = 'top:'. $offset. 'px;';
	}

	echo '
		<div
			class="admin_login_link restore_admin_toolbar ', htmlspecialchars($pos), '"
			style="', htmlspecialchars($style), '"
			onclick="zenarioA.toggleAdminToolbar(false);"
			title="', \ze\admin::phrase('Show admin toolbar'), '"
		></div>';


//2. Show the cookie consent box
//(Note that this should never been shown to admins, logged in or logged out, no matter the settings.)
} else {

	switch (ze::setting('cookie_require_consent')) {
		case 'implied':
			//Implied consent - show the cookie message, just once. Continuing to use the site counts as acceptance.
			if (!empty($_COOKIE['z_cookies_accepted']) || ($_SESSION['z_cookies_accepted'] ?? false)) {
				return;
			} else {
				echo '
<!--googleoff: all-->
<div id="zenario_cookie_consent" class="zenario_cookie_consent cookies_implied"></div>
<script type="text/javascript" src="zenario/cookie_message.php?type=implied"></script>
<!--googleon: all-->';
	
				$_SESSION['z_cookies_accepted'] = true;
			}
			break;
	
		case 'explicit':
			//Explicit consent - show the cookie message until it is accepted or rejected, if the reject button is enabled.
			//If cookies are rejected but something on the page needs then, also reopen the box.
			if (
				(\ze\cookie::isDecided() || ze::$cookieConsent == 'hide')
			 && ze::$cookieConsent != 'require'
			) {
				return;
			} else {
				echo '
<!--googleoff: all-->
<div id="zenario_cookie_consent" class="zenario_cookie_consent cookies_explicit"></div>
<div id="zenario_cookie_consent_manage_popup" class="zenario_cookie_consent_manage_popup" style="opacity:0; visibility:hidden;"></div>
<script type="text/javascript" src="zenario/cookie_message.php?type=accept"></script>
<!--googleon: all-->';
			}
			break;
	}
}
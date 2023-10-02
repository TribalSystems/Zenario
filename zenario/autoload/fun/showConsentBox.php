<?php
/*
 * Copyright (c) 2023, Tribal Limited
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

//This function does one of three mutually exclusive things.


//1. Add the login link for admins if this looks like a logged out admin
if ($includeAdminLinks
  && !$isAdmin
  && isset($_COOKIE['COOKIE_LAST_ADMIN_USER'])
  && !\ze\priv::check()
  && !\ze\link::adminDomainIsPrivate()) {
	
	$url =
		\ze\link::protocol().
		\ze\link::adminDomain(). SUBDIRECTORY.
		'admin.php?';
	$importantGetRequests = \ze\link::importantGetRequests(true);

	//If this is a 401/403/404 page, include the requested cID and cType,
	//not the actual cID/cType of the 401/403/404 page
	switch (\ze\content::isSpecialPage(ze::$cID, ze::$cType)) {
		case 'zenario_login':
		case 'zenario_no_access':
		case 'zenario_not_found':
			$importantGetRequests['cID'] = $_REQUEST['cID'] ?? false;
			if (!($importantGetRequests['cType'] = $_REQUEST['cType'] ?? false)) {
				unset($importantGetRequests['cType']);
			}
	}

	//Add the logo
	$logoURL = $logoWidth = $logoHeight = false;
	if (ze::setting('admin_link_logo') == 'custom'
	 && (ze\file::imageLink($logoWidth, $logoHeight, $logoURL, ze::setting('admin_link_custom_logo'), 50, 50, $mode = 'resize', $offset = 0, $retina = true))) {

		if (strpos($logoURL, '://') === false) {
			$logoURL = \ze\link::absolute(). $logoURL;
		}
	} else {
		$logoURL = \ze\link::absolute(). 'zenario/admin/images/zenario-logo-diamond.svg';
		$logoWidth = 25;
		$logoHeight = 19;
	}
	
	$offset = (int) ze::setting('admin_link_logo_offset', $useCache = true, $default = 30);
	$pos = ze::setting('admin_link_logo_pos', $useCache = true, $default = 'allt allr');
	
	if (substr($pos, 0, 4) == 'allb') {
		$style = 'bottom:'. $offset. 'px';
	} else {
		$style = 'top:'. $offset. 'px';
	}

	echo '
		<div class="admin_login_link ', htmlspecialchars($pos), '" style="', htmlspecialchars($style), '">
			<a
				class="clear_admin_cookie"
				href="zenario/cookies.php?clear_admin_cookie=1"
				onclick="
					return confirm(\'', (\ze\admin::phrase('Remove your admin login link?\n\nThis will delete your admin cookie.\n\nGo to /admin to sign in next time!')), '\');
				"
			></a>
			<a
				class="admin_login_link"
				href="', htmlspecialchars($url. http_build_query($importantGetRequests)), '"
				onclick="
					var requests,
						conductorSlot = zenario_conductor.getSlot();
					if (conductorSlot && conductorSlot.exists) {
						requests = zenario_conductor.request(conductorSlot, \'refresh\');
						zenario.goToURL(zenario.linkToItem(zenario.cID, zenario.cType, requests, true));
						return false;
					}
					return true;
				"
			>
				<img src="', htmlspecialchars($logoURL), '" width="', (int) $logoWidth, '" height="', (int) $logoHeight, '" alt="', \ze\admin::phrase('Admin login logo'), '"/><br/>
				', \ze\admin::phrase('Login'), '
			</a>
		</div>';

	//Never allow a page with an "Admin" link to be cached...
	ze::$canCache = false;

//2. If the admin has hidden the admin toolbar, add a button to get it back.
} elseif ($includeAdminLinks && $isAdmin && !$includeAdminToolbar) {
	
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


//3. Show the cookie consent box
//(Note that this should never been shown to admins, logged in or logged out, no matter the settings.)
} else {

	switch (ze::setting('cookie_require_consent')) {
		case 'implied':
			//Implied consent - show the cookie message, just once. Continuing to use the site counts as acceptance.
			if (!empty($_COOKIE['cookies_accepted']) || ($_SESSION['cookies_accepted'] ?? false)) {
				return;
			} else {
				echo '
<!--googleoff: all-->
<div id="zenario_cookie_consent" class="zenario_cookie_consent cookies_implied"></div>
<script type="text/javascript" src="zenario/cookie_message.php?type=implied"></script>
<!--googleon: all-->';
	
				$_SESSION['cookies_accepted'] = true;
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
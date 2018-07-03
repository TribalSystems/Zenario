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


//Add the login link for admins if this looks like a logged out admin
if (isset($_COOKIE['COOKIE_LAST_ADMIN_USER'])
 && !\ze\priv::check()
 && !\ze\link::adminDomainIsPrivate()) {

	$url =
		(\ze::setting('admin_use_ssl')? 'https://' : \ze\link::protocol()).
		\ze\link::adminDomain(). SUBDIRECTORY.
		'zenario/admin/welcome.php?';
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
	if (\ze::setting('admin_link_logo') == 'custom'
	 && (ze\file::imageLink($logoWidth, $logoHeight, $logoURL, \ze::setting('admin_link_custom_logo'), 50, 50, $mode = 'resize', $offset = 0, $retina = true))) {

		if (strpos($logoURL, '://') === false) {
			$logoURL = \ze\link::absolute(). $logoURL;
		}
	} else {
		$logoURL = \ze\link::absolute(). 'zenario/admin/images/zenario_admin_link_logo.png';
		$logoWidth = 25;
		$logoHeight = 19;
	}
	
	$offset = (int) \ze::setting('admin_link_logo_offset', $useCache = true, $default = 30);
	$pos = \ze::setting('admin_link_logo_pos', $useCache = true, $default = 'allt allr');
	
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
					return confirm(\'', (\ze\admin::phrase('Are you sure you wish to remove the admin login link?\n\nGo to /admin to login if the admin link is not visible.')), '\');
				"
			></a>
			<a class="admin_login_link" href="', htmlspecialchars($url. http_build_query($importantGetRequests)), '">
				<img src="', htmlspecialchars($logoURL), '" width="', (int) $logoWidth, '" height="', (int) $logoHeight, '" alt="', \ze\admin::phrase('Admin login logo'), '"/><br/>
				', \ze\admin::phrase('Login'), '
			</a>
		</div>';

	//Never allow a page with an "Admin" link to be cached...
	ze::$canCache = false;

	//Note that this should override showing the cookie consent box, no matter the settings
	return;
}

switch (\ze::setting('cookie_require_consent')) {
	case 'implied':
		//Implied consent - show the cookie message, just once. Continuing to use the site counts as acceptance.
		if (!empty($_COOKIE['cookies_accepted']) || ($_SESSION['cookies_accepted'] ?? false)) {
			return;
		}
	
		echo '
<!--googleoff: all-->
<script type="text/javascript" src="zenario/cookie_message.php?type=implied"></script>
<!--googleon: all-->';
	
		$_SESSION['cookies_accepted'] = true;
		break;
	
	
	case 'explicit':
		//Explicit consent - show the cookie message until it is accepted or rejected, if the reject button is enabled.
		if (ze::$cookieConsent == 'hide'
		 || \ze\cookie::canSet()
		 || (ze::$cookieConsent != 'require') && ($_SESSION['cookies_rejected'] ?? false)) {
			return;
		}
	
		if (\ze::setting('cookie_consent_type') == 'message_accept_reject' && ze::$cookieConsent != 'require') {
			echo '
<!--googleoff: all-->
<script type="text/javascript" src="zenario/cookie_message.php?type=accept_reject"></script>
<!--googleon: all-->';
		} else {
			echo '
<!--googleoff: all-->
<script type="text/javascript" src="zenario/cookie_message.php?type=accept"></script>
<!--googleon: all-->';
		}
	
		break;
}

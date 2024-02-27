<?php
/*
 * Copyright (c) 2024, Tribal Limited
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

if (empty($_GET['type'])) {
	exit;
}


header('Content-Type: text/javascript; charset=UTF-8');
require 'basicheader.inc.php';

//Ensure that the site name and subdirectory are part of the ETag, as modules can have different ids on different servers
$ETag = 'zenario-cookie_message-'. LATEST_REVISION_NO. '--'. $_SERVER["HTTP_HOST"]. '-'. preg_replace('@[^\w\.-]@', '', $_GET['type']);

//Cache this combination of running Plugin JavaScript
ze\cache::useBrowserCache($ETag);


ze\db::loadSiteConfig();

//Show a manage button if visitors can manage thier cookies individually
$manageButtonHTML = '';
if (in_array($_GET['type'], ['accept', 'accept_reject'])) {
	$manageButtonHTML =  '
		<div class="zenario_cc_manage">
			<a
				onclick="
					document.getElementById(\'zenario_cookie_consent_manage_popup\').style.cssText = \'opacity:1; visibility:visible;\';
					document.getElementById(\'zenario_cookie_consent\').style.display = \'none\';
				"
			>
				'. htmlspecialchars(\ze::setting('cookie_box1_04_manage_btn')). '
			</a>
		</div>';
}

switch ($_GET['type']) {
	//Implied consent - show the cookie message, just once. Continuing to use the site counts as acceptance.
	case 'implied':
		echo '
document.getElementById("zenario_cookie_consent").innerHTML = \'', ze\escape::js('
	<div class="zenario_cookie_consent_wrap">
		<div class="zenario_cc_message">'. \ze::setting('cookie_box1_01_implied_msg'). '</div>
		<div class="zenario_cc_buttons">
			<div class="zenario_cc_continue">
				<a href="" onclick="$(\'div.zenario_cookie_consent\').slideUp(\'slow\'); return false;">'.
					htmlspecialchars(\ze::setting('cookie_box1_02_continue_btn')).
				'</a>
			</div>
		</div>
	</div>
'), '\';';
		break;
		
	//Explicit consent - show the cookie message until it is accepted
	case 'accept':
		echo '
document.getElementById("zenario_cookie_consent").innerHTML = \'', ze\escape::js('
	<div class="zenario_cookie_consent_wrap">
		<div class="zenario_cc_message">'. \ze::setting('cookie_box1_03_cookie_consent_msg'). '</div>
		<div class="zenario_cc_buttons">
			' . $manageButtonHTML . '
			<div class="zenario_cc_accept">
				<a href="zenario/cookies.php?accept_cookies=1">'. htmlspecialchars(\ze::setting('cookie_box1_05_accept_btn')). '</a>
			</div>
		</div>
	</div>
'), '\';';

		break;
}

if (ze::in($_GET['type'], 'accept', 'popup_only')) {
	$cancelButtonOnclick = '';
	if ($_GET['type'] == 'accept') {
		$cancelButtonOnclick .= '
			document.getElementById(\'zenario_cookie_consent\').style.display = \'block\';';
	}
	$cancelButtonOnclick .= '
		document.getElementById(\'zenario_cookie_consent_manage_popup\').style.display = \'none\';';

	echo '
cookieConsentPopup = document.getElementById("zenario_cookie_consent_manage_popup");
if (!cookieConsentPopup) {
	cookieConsentPopup = document.createElement(\'div\');
	cookieConsentPopup.setAttribute(\'id\', \'zenario_cookie_consent_manage_popup\');
	cookieConsentPopup.setAttribute(\'class\', \'zenario_cookie_consent_manage_popup\');
	document.body.append(cookieConsentPopup);
}';

if ($_GET['type'] == 'popup_only') {
	echo '
		cookieConsentPopup.setAttribute(\'style\', \'opacity:1; visibility:visible;\');';
}
	
	//If the zenario.manageCookies() function put the previous choices in the URL, use those.
	//Otherwise default all of the options to the site setting default.
	$defaultStateSiteSetting = \ze::setting('popup_cookie_type_switches_initial_state');
	
	if (isset($_REQUEST['funOn'])) {
		$funOn = !empty($_REQUEST['funOn']);
	} else {
		$funOn = ($defaultStateSiteSetting == 'on');
	}
	if (isset($_REQUEST['anOn'])) {
		$anOn = !empty($_REQUEST['anOn']);
	} else {
		$anOn = ($defaultStateSiteSetting == 'on');
	}
	if (isset($_REQUEST['socialOn'])) {
		$socialOn = !empty($_REQUEST['socialOn']);
	} else {
		$socialOn = ($defaultStateSiteSetting == 'on');
	}
	
	$cookieImageHtml = '';
	if (\ze::setting('cookie_show_image') && ($cookieImageId = \ze::setting('cookie_image'))) {
		$width = $height = $url = false;
		\ze\file::imageLink($width, $height, $url, $cookieImageId, \ze::setting('cookie_image_width'), \ze::setting('cookie_image_height'), \ze::setting('cookie_image_canvas'));
		
		$cookieImageHtml = '
		<div class="cookie_consent_image">
			<img src="' . htmlspecialchars($url) . '" width="' . (int) $width . '" height="' . (int) $height . '">
		</div>';
	}

	echo '
cookieConsentPopup.innerHTML = \'', ze\escape::js('
	<div class="zenario_cookie_consent_manage_popup_wrap">
	
		<div class="cookie_title_text">' . 
			$cookieImageHtml . '
			' . \ze::setting('cookie_box2_01_intro_msg') . '
		</div>
		<form method="post" action="zenario/cookies.php">
			<button type="button" class="cancel"
					onclick="
						' . $cancelButtonOnclick . '
					"
				>' . \ze\lang::phrase('Cancel') . '</button>

			<div class="cookies_buttons top">
				<input type="submit" name="cookie_accept_all" value="' . htmlspecialchars(\ze::setting('cookie_box2_02_accept_all_btn')) . '">
			</div>

			<div class="cookie">
				<label class="switch">
					<input type="checkbox" checked disabled>
					<span class="slider round"></span>
				</label>
				<div class="cookie_info">
					<h5>' . htmlspecialchars("Necessary cookies") . '</h5>
					<p>' . htmlspecialchars("Essential cookies needed by this site. Without these cookies services cannot be provided.") . '</p>
				</div>
			</div>

			<div class="cookie">
				<label class="switch">
					<input type="checkbox" name="functionality" ' . ($funOn ? 'checked' : '') . '>
					<span class="slider round"></span>
				</label>
				<div class="cookie_info">
					<h5>' . htmlspecialchars("Functionality") . '</h5>
					<p>' . htmlspecialchars("These cookies are used for usability features of the site, such as to remember choices you have made.") . '</p>
				</div>
			</div>

			<div class="cookie">
				<label class="switch">
					<input type="checkbox" name="analytics" ' . ($anOn ? 'checked' : '') . '>
					<span class="slider round"></span>
				</label>
				<div class="cookie_info">
					<h5>' . htmlspecialchars("Analytics") . '</h5>
					<p>' . htmlspecialchars("These cookies are used to anonymously monitor traffic levels on our site.") . '</p>
				</div>
			</div>

			<div class="cookie">
				<label class="switch">
					<input type="checkbox" name="social_media" ' . ($socialOn ? 'checked' : '') . '>
					<span class="slider round"></span>
				</label>
				<div class="cookie_info">
					<h5>' . htmlspecialchars("Social Media") . '</h5>
					<p>' . htmlspecialchars("These cookies allow easy sharing of pages via social media, and allow tracking of shares.") . '</p>
				</div>
			</div>

			<div class="cookies_buttons">
				<input type="submit" name="cookie_save_preferences" class="cookie_save_preferences" value="' . \ze::setting('cookie_box2_11_save_preferences_btn') . '">
			</div>
		</form>
	</div>
'), '\';';
}
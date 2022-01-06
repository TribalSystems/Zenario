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

if (empty($_GET['type'])) {
	exit;
}


header('Content-Type: text/javascript; charset=UTF-8');
require 'basicheader.inc.php';

//Ensure that the site name and subdirectory are part of the ETag, as modules can have different ids on different servers
$ETag = 'zenario-cookie_message-'. LATEST_REVISION_NO. '--'. $_SERVER["HTTP_HOST"]. '-'. preg_replace('@[^\w\.-]@', '', $_GET['type']);

//Cache this combination of running Plugin JavaScript
ze\cache::useBrowserCache($ETag);
ze\cache::start();


ze\db::loadSiteConfig();

//Show a manage button if visitors can manage thier cookies individually
$manageButtonHTML = '';
if (in_array($_GET['type'], ['accept', 'accept_reject']) 
	&& ze\module::inc('zenario_cookie_consent_status')
	&& ze::setting('individual_cookie_consent') 
	&& ($tagId = ze::setting('manage_cookie_consent_content_item'))
) {
	$cID = $cType = false;
	ze\content::getCIDAndCTypeFromTagId($cID, $cType, $tagId);
	ze\content::langEquivalentItem($cID, $cType);
	$link = ze\link::toItem($cID, $cType);
	$manageButtonHTML =  '
		<div class="zenario_cc_manage">
			<a href="' . htmlspecialchars($link) . '">'. ze\lang::phrase('_COOKIE_CONSENT_MANAGE'). '</a>
		</div>';
}

switch ($_GET['type']) {
	//Implied consent - show the cookie message, just once. Continuing to use the site counts as acceptance.
	case 'implied':
		echo '
document.write(\'', ze\escape::js('
	<div class="zenario_cookie_consent">
		<div class="zenario_cookie_consent_wrap">
			<div class="zenario_cc_message">'. ze\lang::phrase('_COOKIE_CONSENT_IMPLIED_MESSAGE'). '</div>
			<div class="zenario_cc_buttons">
				<div class="zenario_cc_continue">
					<a href="" onclick="$(\'div.zenario_cookie_consent\').slideUp(\'slow\'); return false;">'.
						ze\lang::phrase('_COOKIE_CONSENT_CONTINUE').
					'</a>
				</div>
			</div>
		</div>
	</div>
'), '\');';
		break;
		
	//Explicit consent - show the cookie message until it is accepted
	case 'accept':
		echo '
document.write(\'', ze\escape::js('
	<div class="zenario_cookie_consent">
		<div class="zenario_cookie_consent_wrap">
		<div class="zenario_cc_message">'. ze\lang::phrase('_COOKIE_CONSENT_MESSAGE'). '</div>
		<div class="zenario_cc_close">
			<a href="#" onclick="$(\'div.zenario_cookie_consent\').fadeOut(\'slow\'); return false;">'.
				ze\lang::phrase('_COOKIE_CONSENT_CLOSE').
			'</a>
		</div>
		<div class="zenario_cc_buttons">
			<div class="zenario_cc_accept">
				<a href="zenario/cookies.php?accept_cookies=1">'. ze\lang::phrase('_COOKIE_CONSENT_ACCEPT'). '</a>
			</div>
			' . $manageButtonHTML . '
		</div>
	</div>
</div>
'), '\');';
		break;
		
	//Explicit consent - show the cookie message until it is accepted or rejected
	case 'accept_reject':
		echo '
document.write(\'', ze\escape::js('
	<div class="zenario_cookie_consent">
		<div class="zenario_cookie_consent_wrap">
		<div class="zenario_cc_message">'. ze\lang::phrase('_COOKIE_CONSENT_MESSAGE'). '</div>
		<div class="zenario_cc_close">
			<a href="#" onclick="$(\'div.zenario_cookie_consent\').fadeOut(\'slow\'); return false;">'.
				ze\lang::phrase('_COOKIE_CONSENT_CLOSE').
			'</a>
		</div>
		<div class="zenario_cc_buttons">
			<div class="zenario_cc_reject">
				<a href="zenario/cookies.php?accept_cookies=0">'. ze\lang::phrase('_COOKIE_CONSENT_REJECT'). '</a>
			</div>
			<div class="zenario_cc_accept">
				<a href="zenario/cookies.php?accept_cookies=1">'. ze\lang::phrase('_COOKIE_CONSENT_ACCEPT'). '</a>
			</div>
			' . $manageButtonHTML . '
		</div>
	</div>
</div>
'), '\');';
		break;
}
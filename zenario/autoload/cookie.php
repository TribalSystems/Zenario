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

namespace ze;

class cookie {



	//Formerly "setCookieOnCookieDomain()"
	public static function set($name, $value, $expire = COOKIE_TIMEOUT) {
	
		if ($expire > 1) {
			$expire += time();
		}
	
		setcookie($name, $value, $expire, SUBDIRECTORY, COOKIE_DOMAIN, \ze\link::isHttps(), true);
		$_COOKIE[$name] = $value;
	}

	//Formerly "clearCookie()"
	public static function clear($name) {
		\ze\cookie::set($name, '', 1);
	
		//Attempt to fix a bug where any cookies that were set with the wrong domain and/or path
		//will stay still stay on the visitor's browser.
		if (function_exists('httpHostWithoutPort')) {
			setcookie($name, '', 1, '/', '.'. \ze\link::hostWithoutPort());
		}
		setcookie($name, '', 1, '/');
		setcookie($name, '', 1);
	
		unset($_COOKIE[$name]);
	}

	//Formerly "setCookieConsent()"
	public static function setConsent($types = false) {
		\ze\cookie::set('cookies_accepted', $types ? $types : 1);
		unset($_SESSION['unnecessary_cookies_rejected']);
	}

	//Formerly "setCookieNoConsent()"
	public static function setNoConsent() {
		if (isset($_COOKIE['cookies_accepted'])) {
			\ze\cookie::clear('cookies_accepted');
		}
		$_SESSION['unnecessary_cookies_rejected'] = true;
	}
	
	

	//Formerly "requireCookieConsent()"
	public static function requireConsent() {
		\ze::$cookieConsent = 'require';
	}
	
	public static function setSensitiveContentMessageConsent() {
		//Set a 7 day cookie
		\ze\cookie::set('sensitive_content_message_accepted', 1, 604800);
		unset($_SESSION['sensitive_content_message_accepted']);
	}
	
	public static function setCountryAndLanguage($country_id, $user_lang) {
		//Set a 7 day cookie
		if (isset($_COOKIE['cookies_accepted'])) {
			\ze\cookie::set('country_id', $country_id, 604800);
			\ze\cookie::set('user_lang', $user_lang, 604800);
		}
		
		$_SESSION['country_id'] = $country_id;
		$_SESSION['user_lang'] = $user_lang;
	}


	public static function canSetAll() {
		//Always accept cookies on sites that only notify about cookies,
		//for administrators who are logged in,
		//or for visitors who have pressed the "Accept all" button (stored as a "1" in the "cookies_accepted" cookie).
		return \ze::setting('cookie_require_consent') != 'explicit'
			|| \ze::isAdmin()
			|| 1 == ($cookiesAccepted = $_COOKIE['cookies_accepted'] ?? '');
	}



	const canSetFromTwig = true;
	//Formerly "canSetCookie()"
	public static function canSet($type = false) {
		
		//Always accept cookies on sites that only notify about cookies,
		//for administrators who are logged in,
		//or for visitors who have pressed the "Accept all" button (stored as a "1" in the "cookies_accepted" cookie).
		if (\ze::setting('cookie_require_consent') != 'explicit'
		 || \ze::isAdmin()
		 || 1 == ($cookiesAccepted = $_COOKIE['cookies_accepted'] ?? '')) {
			return true;
		}
		
		//"Necessary" cookies are a special case.
		//We don't allow you to opt out of then; the checkbox is disabled and jammed on.
		//So as long as the visitor has at least pressed the save button on the cookie prompt,
		//they've accepted necessary cookies.
		if ($type === 'necessary') {
			return \ze\cookie::isDecided();
		}
		
		//Otherwise the list of cookie types will be stored as a comma separated list
		$cookiesAccepted = array_flip(explode(',', $cookiesAccepted));
		
		if ($type) {
			//If the caller is requesting a specific type, check that
			return isset($cookiesAccepted[$type]);
		} else {
			//For backwards compatibility, if the caller just wants to know if all
			//cookies are accepted, return true if all three specific options
			//were selected.
			return isset(
				$cookiesAccepted['functionality'],
				$cookiesAccepted['analytics'],
				$cookiesAccepted['social_media']
			);
		}
	}
	
	//This returns true if the visitor hasn't yet responded to the cookie prompt
	public static function isDecided() {
		return !empty($_COOKIE['cookies_accepted']) || !empty($_SESSION['unnecessary_cookies_rejected']);
	}

	//Formerly "hideCookieConsent()"
	public static function hideConsent() {
		if (\ze::$cookieConsent != 'require') {
			\ze::$cookieConsent = 'hide';
		}
	}



	//Formerly "showCookieConsentBox()"
	public static function showConsentBox() {
		require \ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	

	//Formerly "zenarioSessionName()"
	public static function sessionName() {
		return 'PHPSESSID'.
			(COOKIE_DOMAIN? ('-'. preg_replace('@\W@', '_', COOKIE_DOMAIN)) : '').
			(SUBDIRECTORY && SUBDIRECTORY != '/'? ('-'. preg_replace('@\W@', '_', str_replace('/', '', SUBDIRECTORY))) : '');
	}

	//Formerly "startSession()"
	public static function startSession() {
		if (!isset($_SESSION)) {
			$sessionName = \ze\cookie::sessionName();
			session_name($sessionName);
		
			if (COOKIE_DOMAIN) {
				session_set_cookie_params(SESSION_TIMEOUT, SUBDIRECTORY, COOKIE_DOMAIN);
			} else {
				session_set_cookie_params(SESSION_TIMEOUT, SUBDIRECTORY);
			}
			
			//Make sure the session_id is valid, and if not create a new one. This stops people
			//manually creating a bad session id on their client to cause a warning.
			$sessionId = false;
			if (ini_get('session.use_cookies') && isset($_COOKIE[$sessionName])) {
				$sessionId = $_COOKIE[$sessionName];
			} elseif (!ini_get('session.use_only_cookies') && isset($_GET[$sessionName])) {
				$sessionId = $_GET[$sessionName];
			}
			if ($sessionId && !preg_match('/^[-,a-zA-Z0-9]{1,128}$/', $sessionId)) {
				session_regenerate_id();
			}
			
			session_start();
		
			//Fix for a bug with the $lifetime option in session_set_cookie_params()
			//as mentioned on http://php.net/manual/en/function.session-set-cookie-params.php
			\ze\cookie::set(session_name(), session_id(), SESSION_TIMEOUT);
		}
	}
}
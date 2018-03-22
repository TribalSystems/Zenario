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

namespace ze;

class admin {


	//Formerly "adminId()"
	public static function id() {
		if (\ze::isAdmin()) {
			return $_SESSION['admin_userid'] ?? false;
		} else {
			return false;
		}
	}

	//Formerly "getAdminDetails()"
	public static function details($admin_id) {
	
		if ($details = \ze\row::get('admins', true, $admin_id)) {
			//Old key/value format for backwards compatability with old code
			foreach ($details as $key => $value) {
				$details['admin_'. $key] = $value;
			}
			$details['disable'] = (int) empty($row['perm_manage']);
		}
	
		return $details;
	}
	

	//Formerly "adminHasSpecificPerms()"
	public static function hasSpecificPerms() {
		return !empty($_SESSION['admin_permissions'])
		 && ($_SESSION['admin_permissions'] == 'specific_languages'
		  || $_SESSION['admin_permissions'] == 'specific_menu_areas');
	}
	

	const settingFromTwig = true;
	//Get the value of an admin's setting
	//Formerly "adminSetting()"
	public static function setting($settingName) {
		if (!isset(\ze::$adminSettings[$settingName])) {
			\ze::$adminSettings[$settingName] =
				\ze\row::get('admin_settings', 'value', ['name' => $settingName, 'admin_id' => \ze::session('admin_userid')]);
		
			if (\ze::$adminSettings[$settingName] === false) {
				\ze::$adminSettings[$settingName] =
					\ze\row::get('admin_setting_defaults', 'default_value', $settingName);
			}
		}
		return \ze::$adminSettings[$settingName];
	}

	//Change an admin's setting
	//Formerly "setAdminSetting()"
	public static function setSetting($settingName, $value) {
	
		if (!isset($_SESSION['admin_userid'])) {
			return;
		}
	
		\ze::$adminSettings[$settingName] = $value;
	
		\ze\row::set('admin_settings', ['value' => $value], array('name' => $settingName, 'admin_id' => $_SESSION['admin_userid']));
	}









	//Read a line from admin_phrase_codes/en.txt
	//Either read the next full line after a given position, or if $pos is not specified, the next line from the last position
	private static function phraseLine($lang, &$f, &$code, &$text, $pos = false) {
		$code = $text = '';
	
		if ($pos !== false) {
			fseek($f, $pos);
			fgets($f);
		}
	
		if ($line = fgets($f)) {
			$line = explode('|', $line, 2);
			$code = $line[0];
			$text = trim($line[1], "\t\n\r\0\x0B");
		
			return true;
		} else {
			return false;
		}
	}

	private static function getAdminPhraseCode($target, $lang) {
		return require \ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	//Formerly "adminPhrase()", "getPhrase()"
	public static function phrase($code, $replace = false, $moduleClass = false) {
	
		if ($moduleClass) {
			return \ze\lang::phrase($code, $replace, $moduleClass);
		}
	
		//No support for a multilingual admin interface yet.
		$lang = 'en';
	
		if ($replace === false) {
			$replace = [];
		}
	
		//Some phrases are now being simply hard-coded. However we still wish to call adminPhrase on these
		//hardcoded phrases for tracking purposes
		//Ignore anything that does not start with an underscore
		if ($lang == 'en' && substr($code, 0, 1) != '_') {
			$phrase = $code;
		} else {
			$phrase = self::getAdminPhraseCode($code, $lang);
		}
		
		if (!empty($replace)) {
			\ze\lang::applyMergeFields($phrase, $replace);
		}
		return $phrase;
	}

	//Formerly "nAdminPhrase()"
	public static function nPhrase($text, $pluralText = false, $n = 1, $replace = [], $zeroText = false) {
	
		if (!is_array($replace)) {
			$replace = [];
		}
		if (!isset($replace['count'])) {
			$replace['count'] = $n;
		}
	
		if ($zeroText !== false && $n === 0) {
			return \ze\admin::phrase($zeroText, $replace);
	
		} elseif ($pluralText !== false && $n !== 1) {
			return \ze\admin::phrase($pluralText, $replace);
	
		} else {
			return \ze\admin::phrase($text, $replace);
		}
	}





	//Formerly "CMSWritePageBodyAdminClass()"
	public static function pageBodyAdminClass(&$class, &$toolbars) {
		require \ze::funIncPath(__FILE__, __FUNCTION__);
	}

	//Formerly "CMSWritePageBodyAdminToolbar()"
	public static function pageBodyAdminToolbar(&$toolbars, $toolbarAttr = '') {
		require \ze::funIncPath(__FILE__, __FUNCTION__);
	}








	//Formerly "timeDiff()"
	public static function timeDiff($a, $b, $lowerLimit = false) {
		$sql = "
			SELECT
				datediff(a, b),
				hour(timediff(a, b)),
				minute(timediff(a, b)),
				second(timediff(a, b))
			FROM (
				SELECT
					'". \ze\escape::sql($a). "' AS a,
					'". \ze\escape::sql($b). "' AS b
			) AS ab";
	
		$result = \ze\sql::select($sql);
		$row = \ze\sql::fetchRow($result);
	
		if ($lowerLimit && $lowerLimit > ((int) $row[3] + 60 * ((int) $row[2] + 60 * ((int) $row[1] + 24 * (int) $row[0])))) {
			return true;
		}
	
		$singular = [\ze\admin::phrase('1 day'), \ze\admin::phrase('1 hour'), \ze\admin::phrase('1 minute'), \ze\admin::phrase('1 second')];
		$plural = [\ze\admin::phrase('[[n]] days'), \ze\admin::phrase('[[n]] hours'), \ze\admin::phrase('[[n]] minutes'), \ze\admin::phrase('[[n]] seconds')];
	
		foreach ($singular as $i => $phrase) {
			if ($row[$i] || $i == 3) {
				if ($row[$i] == 1) {
					return $phrase;
				} else {
					return \ze\admin::phrase($plural[$i], ['n' => $row[$i]]);
				}
			}
		}
	}






	//Takes a row from an admin table, and returns the admin's name in the standard format
	//Formerly "formatAdminName()"
	public static function formatName($adminDetails = false) {
	
		if (!$adminDetails) {
			$adminDetails = $_SESSION['admin_userid'] ?? false;
		}
	
		if (!is_array($adminDetails)) {
			$adminDetails = \ze\row::get('admins', ['first_name', 'last_name', 'username', 'authtype'], $adminDetails);
		}
	
		if ($adminDetails['authtype'] == 'super') {
			return $adminDetails['first_name']. ' '. $adminDetails['last_name']. ' ('. $adminDetails['username']. ', multi-site)';
		} else {
			return $adminDetails['first_name']. ' '. $adminDetails['last_name']. ' ('. $adminDetails['username']. ')';
		}
	}


	//Check to see if an admin exists and if the supplied password matches their password
	// 0 means they didn't exist
	// false means they exist but their password wasn't correct
	// true means they exist and that password was right

	//If the admin exists, then the details are returned even if the password was wrong.
	//Also, if you're only after their details and not the password check, then you can
	//set the password to false to avoid checking passwords.
	//Formerly "checkPasswordAdmin()"
	public static function checkPassword($adminUsernameOrEmail, &$details, $password, $checkViaEmail = false) {
		return require \ze::funIncPath(__FILE__, __FUNCTION__);
	}

	//Formerly "cancelPasswordChange()"
	public static function cancelPasswordChange($adminId) {
	
		$sql = "
			UPDATE ". DB_NAME_PREFIX. "admins SET
				password_needs_changing = 0
			WHERE id = ". (int) $adminId;
		$result = \ze\sql::update($sql);
	}

	//Reset someone's password, returning the reset password
	//A randomly generated string is used
	//Formerly "resetPasswordAdmin()"
	public static function resetPassword($adminId) {
		$newPassword = \ze\ring::random();
		\ze\adminAdm::setPassword($adminId, $newPassword, 1, true);
		return $newPassword;
	}


	//Formerly "adminLogoutOnclick()"
	public static function logoutOnclick() {

		if (!\ze::setting('site_enabled') && \ze\row::exists('languages', [])) {
			$logoutMsg =
				\ze\admin::phrase('Are you sure you want to logout? Visitors will not be able to see your site as it is not enabled.');
		} else {
			$logoutMsg =
				\ze\admin::phrase('Are you sure you want to logout?');
		}
	
		$url = 'zenario/admin/welcome.php?task=logout&'. http_build_query(\ze\link::importantGetRequests(true));
	
		return 
	
		'onclick="'. 
			\ze\admin::floatingBoxJS(
				$logoutMsg,
				'<input type="button" class="submit_selected" value="'. \ze\admin::phrase('Logout'). '" onclick="document.location.href = URLBasePath + \''. htmlspecialchars($url). '\';"/>',
				true, true).
		' return false;" href="'. htmlspecialchars(\ze\link::absolute(). $url). '"';
	}

	//Write the JavaScript command needed to use the floating box above
	//Formerly "floatingBoxJS()"
	public static function floatingBoxJS($message, $buttons = false, $showWarning = false, $addCancelButton = false) {
	
		if (!$buttons) {
			$buttons = '<input type="button" value="'. \ze\admin::phrase('_OK'). '" />';
		}
	
		if ($addCancelButton) {
			$buttons .= '<input type="button" value="'. \ze\admin::phrase('_CANCEL'). '" />';
		}
	
		return 'zenarioA.floatingBox(\''. \ze\escape::jsOnClick($message). '\', \''. \ze\escape::jsOnClick($buttons). '\', '. ($showWarning ===  2 || $showWarning === 'error'? '2' : ($showWarning? '1' : '0')). ');';
	}






	//Formerly "loadAdminPerms()"
	public static function loadPerms($adminId) {
		return \ze\ray::valuesToKeys(\ze\row::getArray('action_admin_link', 'action_name', ['admin_id' => $adminId]));
	}

	//Set an admin's session
	//Formerly "setAdminSession()"
	public static function setSession($adminIdL, $adminIdG = false) {
		return require \ze::funIncPath(__FILE__, __FUNCTION__);
	}


	//Log an Admin Out
	//Formerly "unsetAdminSession()"
	public static function unsetSession($destorySession = true) {
	
		unset(
			$_SESSION['admin_first_name'],
			$_SESSION['admin_last_name'],
			$_SESSION['admin_logged_in'],
			$_SESSION['admin_logged_into_site'],
			$_SESSION['admin_server_host'],
			$_SESSION['admin_userid'],
			$_SESSION['admin_global_id'],
			$_SESSION['admin_username'],
			$_SESSION['admin_box_sync'],
			$_SESSION['admin_copied_contents'],
			$_SESSION['admin_permissions'],
			$_SESSION['admin_specific_content_items'],
			$_SESSION['admin_specific_languages'],
			$_SESSION['admin_specific_menu_nodes'],
			$_SESSION['admin_specific_menu_sections'],
			$_SESSION['privs']
		);
	
		if ($destorySession) {
			if (\ze::isAdmin()) {
				if (isset($_COOKIE[session_name()])) {
					\ze\cookie::clear(session_name());
				}
			}
		
			session_destroy();
		}
	}



	//Formerly "languageIdForDatesInAdminMode()"
	public static function languageIdForDatesInAdminMode() {
		return \ze::ifNull(
			\ze\row::get('visitor_phrases', 'language_id', ['code' => '_WEEKDAY_0', 'language_id' => ['en', 'en-gb', 'en-us']]),
			\ze::$defaultLang,
			'en-us');
	}




	//Formerly "adminPermissionsForTranslators()"
	public static function privsForTranslators() {
		return [
			'perm_author' => true,
			'perm_editmenu' => true,
			'perm_publish' => true,
			'_PRIV_VIEW_SITE_SETTING' => true,
			'_PRIV_VIEW_CONTENT_ITEM_SETTINGS' => true,
			'_PRIV_VIEW_MENU_ITEM' => true,
			'_PRIV_EDIT_MENU_TEXT' => true,
			'_PRIV_CREATE_TRANSLATION_FIRST_DRAFT' => true,
			'_PRIV_EDIT_DRAFT' => true,
			'_PRIV_CREATE_REVISION_DRAFT' => true,
			'_PRIV_DELETE_DRAFT' => true,
			'_PRIV_EDIT_CONTENT_ITEM_TEMPLATE' => true,
			'_PRIV_SET_CONTENT_ITEM_STICKY_IMAGE' => true,
			'_PRIV_IMPORT_CONTENT_ITEM' => true,
			'_PRIV_EXPORT_CONTENT_ITEM' => true,
			'_PRIV_HIDE_CONTENT_ITEM' => true,
			'_PRIV_PUBLISH_CONTENT_ITEM' => true,
			'_PRIV_VIEW_LANGUAGE' => true,
			'_PRIV_MANAGE_LANGUAGE_PHRASE' => true];
	}
	
	public static function logIn($adminId, $rememberMe = false) {
		$admin = \ze\row::get('admins', ['username', 'authtype', 'global_id'], $adminId);
		
		if ($rememberMe) {
			\ze\cookie::set('COOKIE_LAST_ADMIN_USER', $admin['username']);
			\ze\cookie::clear('COOKIE_DONT_REMEMBER_LAST_ADMIN_USER');
		} else {
			\ze\cookie::set('COOKIE_DONT_REMEMBER_LAST_ADMIN_USER', '1');
			\ze\cookie::clear('COOKIE_LAST_ADMIN_USER');
		}

		if ($admin['authtype'] == 'super') {
			\ze\admin::setSession($adminId, $admin['global_id']);
		} else {
			\ze\admin::setSession($adminId);
		}
		\ze\cookie::setConsent();

		//Note the time this admin last logged in
			//This might fail if this site needs a db_update and the last_login_ip column does not exist.

		require_once CMS_ROOT. 'zenario/libs/manually_maintained/mit/browser/lib/browser.php';
		$browser = new \Browser();

		$sql = "
			UPDATE ". DB_NAME_PREFIX. "admins SET
				last_login = NOW(),
				last_login_ip = '". \ze\escape::sql(\ze\user::ip()). "',
				last_browser = '". \ze\escape::sql($browser->getBrowser()). "',
				last_browser_version = '". \ze\escape::sql($browser->getVersion()). "',
				last_platform = '". \ze\escape::sql($browser->getPlatform()). "'
			WHERE id = ". (int) $adminId;
		@\ze\sql::select($sql);

		// Update last domain, so primaryDomain can return a domain name if the primary domain site setting is not set.
		if (!\ze\link::adminDomainIsPrivate()) {
			\ze\site::setSetting('last_primary_domain', \ze\link::primaryDomain());
		}

		//Don't offically mark the admin as "logged in" until they've passed all of the
		//checks in the admin login screen
		$_SESSION['admin_logged_in'] = false;
	}
	
	
}
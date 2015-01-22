<?php
/*
 * Copyright (c) 2014, Tribal Limited
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


//Check if cookies are enabled. Note this might need to be called twice in some cases
if (isset($_REQUEST['_check_cookies_enabled'])) {
	
	if (empty($_COOKIE['PHPSESSID'])) {
		session_start();
	} else {
		echo 1;
	}


} elseif (isset($_POST['_draft_set_callback'])) {
	
	session_start();
	
	$_SESSION['zenario_draft_callback'] = $_POST['_draft_set_callback'];
	$_SESSION['page_toolbar'] = $_POST['_save_page_toolbar'];
	$_SESSION['page_mode'] = $_POST['_save_page_mode'];
	$_SESSION['admin_slot_wand'] = $_POST['_save_page_slot_wand'];


} elseif (isset($_POST['_save_page_mode'])) {
	
	session_start();
	
	$_SESSION['page_toolbar'] = $_POST['_save_page_toolbar'];
	$_SESSION['page_mode'] = $_POST['_save_page_mode'];
	$_SESSION['admin_slot_wand'] = $_POST['_save_page_slot_wand'];


//Quickly look up the name of a file
} elseif (!empty($_REQUEST['lookupFileDetails'])) {
	
	require '../liteheader.inc.php';
	connectLocalDB();
	session_start();
	$checkPriv =
		!empty($_SERVER['HTTP_HOST'])
	 && !empty($_SESSION['admin_userid'])
	 && !empty($_SESSION['admin_logged_into_site'])
	 && $_SESSION['admin_logged_into_site'] == $_SERVER['HTTP_HOST']. SUBDIRECTORY. setting('site_id');
	
	if ($checkPriv) {
		$sql = "
			SELECT filename, width, height
			FROM ". DB_NAME_PREFIX. "files
			WHERE id = ". (int) $_REQUEST['lookupFileDetails'];
		
		if ($result = sqlQuery($sql)) {
			header('Content-Type: text/javascript; charset=UTF-8');
			echo json_encode(sqlFetchAssoc($result));
		}
	}


//Check, load or save an admin's Storekeeper preferences
} elseif (!empty($_REQUEST['_manage_prefs'])) {
	
	require '../liteheader.inc.php';
	connectLocalDB();
	session_start();
	$checkPriv =
		!empty($_SERVER['HTTP_HOST'])
	 && !empty($_SESSION['admin_userid'])
	 && !empty($_SESSION['admin_logged_into_site'])
	 && $_SESSION['admin_logged_into_site'] == $_SERVER['HTTP_HOST']. SUBDIRECTORY. setting('site_id');
	
	if ($checkPriv) {
		if (!empty($_POST['_save_prefs']) && !empty($_POST['prefs'])) {
			$sql = "
				REPLACE INTO ". DB_NAME_PREFIX. "admin_storekeeper_prefs SET
					prefs = '". sqlEscape($_POST['prefs']). "',
					checksum = '". sqlEscape(substr($_POST['checksum'], 0, 22)). "',
					admin_id = ". (int) $_SESSION['admin_userid'];
			sqlQuery($sql);
		
		} else {
			if (!empty($_REQUEST['_get_checksum'])) {
				$sql = "SELECT checksum";
			
			} elseif (!empty($_REQUEST['_load_prefs'])) {
				$sql = "SELECT prefs";
			
			} else {
				exit;
			}
			
			$sql .= "
				FROM ". DB_NAME_PREFIX. "admin_storekeeper_prefs
				WHERE admin_id = ". (int) $_SESSION['admin_userid'];
			$result = sqlQuery($sql);
			
			if ($row = sqlFetchRow($result)) {
				echo $row[0];
			} else {
				echo '{}';
			}
		}
	}


} elseif (isset($_REQUEST['password_suggestion'])) {
	
	require '../visitorheader.inc.php';
	echo randomString(8);


} elseif (isset($_POST['screen_name_suggestion'])) {
	
	require '../adminheader.inc.php';
	echo getNextScreenName();


} elseif (isset($_POST['_validate_alias'])) {
	
	require '../adminheader.inc.php';
	
	
	if ($alias = post('alias')) {
		
		$lines = validateAlias($alias, post('cID'), post('cType'), post('equivId'));
		
		if (!$equivId = post('equivId')) {
			$equivId = equivId(post('cID'), post('cType'));
		}
		
		if (empty($lines) && $alias) {
			$sql = "
				SELECT lang_code_in_url, language_id, alias
				FROM ". DB_NAME_PREFIX. "content
				WHERE alias != ''
				  AND alias < '". sqlEscape($alias). "'
				  AND (equiv_id, type) NOT IN ((". (int) $equivId. ", '". sqlEscape(post('cType')). "'))
				ORDER BY alias DESC, language_id DESC";
			$result = sqlQuery($sql);
			$lastAlias = sqlFetchRow($result);
			
			if (post('cID') && post('cType')) {
				$sql = "
					SELECT lang_code_in_url, language_id, alias
					FROM ". DB_NAME_PREFIX. "content
					WHERE id = ". (int) post('cID'). "
					  AND type = '". sqlEscape(post('cType')). "'";
				$result = sqlQuery($sql);
				$thisAlias = sqlFetchRow($result);
				$thisAlias[2] = $alias;
			
			} else {
				$thisAlias = array('default', post('langId'), $alias);
			}
			
			if (post('lang_code_in_url')) {
				$thisAlias[0] = post('lang_code_in_url');
			}
			
			$sql = "
				SELECT lang_code_in_url, language_id, alias
				FROM ". DB_NAME_PREFIX. "content
				WHERE alias != ''
				  AND alias > '". sqlEscape($alias). "'
				  AND (equiv_id, type) NOT IN ((". (int) $equivId. ", '". sqlEscape(post('cType')). "'))
				ORDER BY alias, language_id";
			$result = sqlQuery($sql);
			$nextAlias = sqlFetchRow($result);
			
			
			$lines = array();
			$i = 0;
			foreach (array(adminPhrase('Prev:') => $lastAlias, adminPhrase('This:') => $thisAlias, adminPhrase('Next:') => $nextAlias) as $phrase => $content) {
				++$i;
				if ($content) {
					$line = $phrase. ' '. ($i == 2? '<i>' : '');
					
					//If multiple languages are enabled on this site, check to see if we need to add the language code to the alias.
					if (getNumLanguages() > 1) {
						//We will need to add the language code if the alias is used more than once,
						//the settings for that Content Item say so, or if the settings for the Content Item are left on
						//default and the Site Settings say so.
						if ($content[0] == 'show' || ($content[0] == 'default' && !setting('translations_hide_language_code'))) {
							$needToUseLangCode = true;
						} else {
							$sql = "
								SELECT 1
								FROM ". DB_NAME_PREFIX. "content
								WHERE alias = '". sqlEscape($content[2]). "'
								LIMIT 2";
							$result = sqlQuery($sql);
							$needToUseLangCode = sqlFetchRow($result) && ($i == 2 || sqlFetchRow($result));
						}
						
						if ($needToUseLangCode) {
							$aliasOrCID = $content[2]. ','. $content[1];
							if (setting('mod_rewrite_enabled')) {
								$link = $aliasOrCID. setting('mod_rewrite_suffix');
							} else {
								$link = indexDotPHP(). "?cID=". $aliasOrCID;
							}
							$line .= '<u>'. htmlspecialchars($link). '</u>, ';
						}
					}
					
					$aliasOrCID = $content[2];
					if (setting('mod_rewrite_enabled')) {
						$link = $aliasOrCID. setting('mod_rewrite_suffix');
					} else {
						$link = indexDotPHP(). "?cID=". $aliasOrCID;
					}
					$line .= '<u>'. htmlspecialchars($link). '</u>';
				
					$lines[] = $line. ($i == 2? '</i>' : '');
					
				} else {
					$lines[] =
						$phrase. ' '. adminPhrase('[none]');
				}
			}
		}
	
	} else {
		$lines = array();
	}
	
	
	echo json_encode($lines);
}




exit;
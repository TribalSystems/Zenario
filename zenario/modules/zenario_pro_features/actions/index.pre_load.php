<?php
/*
 * Copyright (c) 2015, Tribal Limited
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


function pageCacheDir(&$requests, $type = 'index') {
	
	$text = json_encode($requests);
	
	return
		$type. '-'. substr(preg_replace('/[^\w_]+/', '-', $text), 1, 33).
		hash64($text. $_SERVER['HTTP_HOST'], 16). '-'.
		(empty($_COOKIE['cookies_accepted'])? (empty($_SESSION['cookies_rejected'])? '' : 'r') : 'a');
}


function zenario_page_caching__logStats($stats) {
	
	if (is_dir($dir = 'cache/stats/page_caching/') && is_writeable($dir)) {
	} elseif (function_exists('createCacheDir') && $dir = createCacheDir('page_caching', 'stats', true, false)) {
	} else {
		return false;
	}
	
	touch($dir. 'to');
	touch($dir. 'accessed');
	if (!file_exists($dir. 'from')) {
		touch($dir. 'from');
		chmod($dir. 'to', 0666);
		chmod($dir. 'accessed', 0666);
		chmod($dir. 'from', 0666);
	}
	
	foreach ($stats as $stat) {
		if (file_exists($dir. $stat)) {
			$hits = (int) trim(file_get_contents($dir. $stat));
			file_put_contents($dir. $stat, ++$hits);
		} else {
			file_put_contents($dir. $stat, 1);
			chmod($dir. $stat, 0666);
		}
	}
	
	return true;
}



//Set the cookie consent cookie if we see cookies_accepted in the visitor's session
	//This code is usually done in zenario_common_features/actions/index.pre_load.php.
	//but this file will be run first and we need it complete to use caching properly.
if (!empty($_SESSION['cookies_accepted'])) {
	setCookieConsent();
	unset($_SESSION['cookies_accepted']);
}


//Don't allow page caching if an Admin is logged in
//Also don't allow it if a Visitor is not logged in as an Extranet User, but has the LOG_ME_IN_COOKIE Cookie set, as they're probably about to be automatically logged in
if (!isset($_SESSION['admin_logged_into_site'])
 && !(empty($_SESSION['extranetUserID']) && isset($_COOKIE['LOG_ME_IN_COOKIE']))) {
	
	$chToSaveStatus = array();
	$chToSaveStatus['u'] = 'u';
	$chToSaveStatus['g'] = 'g';
	$chToSaveStatus['p'] = 'p';
	$chToSaveStatus['s'] = 's';
	$chToSaveStatus['c'] = 'c';
	
	$chToLoadStatus = array();
	$chToLoadStatus['u'] = '';
	$chToLoadStatus['g'] = '';
	$chToLoadStatus['p'] = '';
	$chToLoadStatus['s'] = '';
	$chToLoadStatus['c'] = '';
	
	if (!empty($_SESSION['extranetUserID']) || isset($_COOKIE['LOG_ME_IN_COOKIE'])) {
		$chToLoadStatus['u'] = 'u';
	}
	if (!empty($_POST)) {
		$chToLoadStatus['p'] = 'p';
	}
	
	$chKnownRequests = array();
	if (!empty($_REQUEST['cID'])) {
		$chKnownRequests[''] = $_REQUEST['cID'];
	}
	foreach (array('cType', 'slotName', 'instanceId', 'method_call') as $request) {
		if (!empty($_REQUEST[$request])) {
			$chKnownRequests[$request] = $_REQUEST[$request];
		}
	}
	
	ksort($_GET);
	$chAllRequests = $chKnownRequests;
	foreach ($_GET as $request => &$value) {
		if ($request != 'cID' && $request != 'cType' && $request != 'instanceId' && $request != 'slotName') {
			$chAllRequests[$request] = $value;
		}
	}
	
	foreach ($_SESSION as $request => &$value) {
		if (substr($request, 0, 11) != 'can_cache__'
		 && !in($request, 'extranetUserID', 'extranetUser_firstname', 'user_lang', 'destCID', 'destCType', 'destURL', 'destTitle', 'cookies_rejected', 'tinymcePasteText')) {
			$chToLoadStatus['s'] = 's';
			break;
		}
	}
	
	foreach ($_COOKIE as $request => &$value) {
		if (substr($request, 0, 11) != 'can_cache__' && substr($request, 0, 2) != '__'
		 && !in($request, 'cookies_accepted', '_ga', '_gat', 'is_returning', 'PHPSESSID', 'tinymcePasteText')) {
			$chToLoadStatus['c'] = 'c';
			break;
		}
	}
	
	
	
	$chDirAllRequests = pageCacheDir($chAllRequests);
	$chDirKnownRequests = pageCacheDir($chKnownRequests);

	//Don't allow a page to be served from the cache if an Admin has
	//set the "remember me" option to show the admin login link
	if (!isset($_COOKIE['COOKIE_LAST_ADMIN_USER'])) {
		
		for ($chS = 's';; $chS = $chToLoadStatus['s']) {
				for ($chC = 'c';; $chC = $chToLoadStatus['c']) {
						for ($chP = 'p';; $chP = $chToLoadStatus['p']) {
								for ($chG = 'g';; $chG = $chToLoadStatus['g']) {
										for ($chU = 'u';; $chU = $chToLoadStatus['u']) {
											
												if ($chG) {
													$chFile = $chDirKnownRequests. $chU. $chG. $chP. $chS. $chC;
												} else {
													$chFile = $chDirAllRequests. $chU. $chG. $chP. $chS. $chC;
												}
											
												if (file_exists(($chPath = 'cache/pages/'. $chFile. '/'). 'page.html')) {
													zenario_page_caching__logStats(array('hits', 'total'));
													touch($chPath. 'accessed');
												
												
													//If there are cached images on this page, mark that they've been accessed
													if (file_exists($chPath. 'cached_files')) {
														foreach (file($chPath. 'cached_files', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $cachedImage) {
															if (is_dir($cachedImage)) {
																touch($cachedImage. 'accessed');
															} else {
																//Delete the cached copy as its images are missing
																deleteCacheDir($chPath);
															
																//Continue the loop looking for any more cached copies of this page.
																//Most likely if any exist they will need deleting because their images will be missing too,
																//and it's a good idea to clean up.
																continue 2;
															}
														}
													}
												
												
													useGZIP();
													$page = file_get_contents($chPath. 'page.html');
													if (false !== $pos = strpos($page, '<body class="no_js [[%browser%]]')) {
														echo substr($page, 0, $pos), '<body class="no_js '. browserBodyClass(), substr($page, $pos + 32);
													} else {
														echo $page;
													}
												
													if (file_exists($chPath. 'show_cache_info')) {
														echo '
															<script type="text/javascript">
																window.zenarioCD.load = ', json_encode($chToLoadStatus), ';
																window.zenarioCD.served_from_cache = true;
															</script>';
													
														echo "\n</body>\n</html>";
													}
												
													exit;
												}
											
											if ($chU == $chToLoadStatus['u']) break;
										}
									if ($chG == $chToLoadStatus['g']) break;
								}
							if ($chP == $chToLoadStatus['p']) break;
						}
					if ($chC == $chToLoadStatus['c']) break;
				}
			if ($chS == $chToLoadStatus['s']) break;
		}
	}
}
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


function pageCacheDir(&$requests, $type = 'ajax') {
	$text = json_encode($requests);
	
	return
		$type. '-'.
		substr(preg_replace('/[^\w_]+/', '-', $text), 1, 33).
		hash64($text, 16). '-'.
		(empty($_COOKIE['cookies_accepted'])? '' : 'a');
}


//Don't allow page caching if an Admin is logged in
//Also don't allow it if a Visitor is not logged in as an Extranet User, but has the LOG_ME_IN_COOKIE Cookie set, as they're probably about to be automatically logged in
if (!isset($_SESSION['admin_logged_into_site'])
 && !(empty($_SESSION['extranetUserID']) && isset($_COOKIE['LOG_ME_IN_COOKIE']))
 && in(request('method_call'), 'refreshPlugin', 'showFloatingBox', 'showRSS', 'showSlot')) {
 	
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
	foreach (array('cID', 'cType', 'slotName', 'instanceId', 'method_call') as $req) {
		if (!empty($_REQUEST[$req])) {
			$chKnownRequests[$req] = $_REQUEST[$req];
		}
	}
	
	ksort($_GET);
	$chAllRequests = $chKnownRequests;
	foreach ($_GET as $request => &$value) {
		if (!in($request, 'cID', 'cType', 'slotName', 'instanceId', 'method_call')) {
			$chAllRequests[$request] = $value;
		}
	}
	
	foreach ($_SESSION as $request => &$value) {
		if (substr($request, 0, 11) != 'can_cache__'
		 && !in($request, 'cookies_rejected', 'extranetUserID', 'extranetUser_firstname', 'user_lang', 'destCID', 'destCType', 'destURL', 'destTitle', 'tinymcePasteText')) {
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
											
											if (file_exists(($chPath = 'cache/pages/'. $chFile. '/'). 'plugin.html')) {
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
												
												if (!empty($_REQUEST['method_call'])
												 && $_REQUEST['method_call'] == 'showRSS') {
													header('Content-Type: application/xml; charset=UTF-8');
												} else {
													header('Content-Type: text/html; charset=UTF-8');
												}
												
												useGZIP();
												echo file_get_contents($chPath. 'plugin.html');
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

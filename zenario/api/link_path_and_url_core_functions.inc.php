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

cms_core::$whitelist[] = 'absCMSDirURL';
function absCMSDirURL() {
	return httpOrHttps(). httpHost(). SUBDIRECTORY;
}

function absURLIfNeeded($cookieFree = true) {
	
	if ($cookieFree && $cookieFreeDomain = cookieFreeDomain()) {
		return $cookieFreeDomain;
	
	} elseif (cms_core::$mustUseFullPath) {
		return httpOrHttps(). httpHost(). SUBDIRECTORY;
	
	} else {
		return '';
	}
}

cms_core::$whitelist[] = 'httpHost';
function httpHost() {
	if (!empty($_SERVER['HTTP_HOST'])) {
		return $_SERVER['HTTP_HOST'];
	} else {
		return primaryDomain();
	}
}

function httpHostWithoutPort($host = false) {
	if ($host === false) {
		$host = httpHost();
	}
	
	if (($pos = strpos($host, ':')) !== false) {
		$host = substr($host, 0, $pos);
	}
	
	return $host;
}

//Attempt to check whether we are in http or https, and return a value appropriately
//If the USE_FORWARDED_IP constant is set we should try to check the HTTP_X_FORWARDED_PROTO variable.
cms_core::$whitelist[] = 'httpOrhttps';
function httpOrhttps() {
	if (isHttps()) {
		return 'https://';
	} else {
		return 'http://';
	}
}

//Deprecated, please just use DIRECTORY_INDEX_FILENAME instead!
function indexDotPHP($noBasePath = false) {
	if (defined('DIRECTORY_INDEX_FILENAME')) {
		$indexFile = DIRECTORY_INDEX_FILENAME;
	} else {
		$indexFile = 'index.php';
	}
	
	if ($indexFile) {
		return $indexFile;
	} elseif ($noBasePath) {
		return SUBDIRECTORY;
	} else {
		return '';
	}
}

cms_core::$whitelist[] = 'moduleDir';
//	function moduleDir($moduleName, $subDir = '') {}

cms_core::$whitelist[] = 'adminDomain';
function adminDomain() {
	if (setting('admin_domain')) {
		return setting('admin_domain');
	
	} elseif (!empty($_SERVER['HTTP_HOST'])) {
		return $_SERVER['HTTP_HOST'];
	
	} else {
		return primaryDomain();
	}
}

function adminDomainIsPrivate() {
	return setting('admin_domain') && !setting('admin_domain_is_public');
}

cms_core::$whitelist[] = 'primaryDomain';
function primaryDomain() {
	if (setting('primary_domain')) {
		return setting('primary_domain');
	
	} elseif (!empty($_SERVER['HTTP_HOST'])) {
		return $_SERVER['HTTP_HOST'];
	
	} elseif (setting('last_primary_domain')) {
		return setting('last_primary_domain');
	
	} else {
		return false;
	}
}

//Warning: this is deprecated, please use setCookieOnCookieDomain() instead!
function cookieDomain() {
	if (COOKIE_DOMAIN) {
		return COOKIE_DOMAIN;
	
	} else {
		return httpHost();
	}
}

//Warning: this is deprecated, please use the SUBDIRECTORY constant instead!
function CMSDir() {
	return SUBDIRECTORY;
}

//Warning: this is deprecated, please use the CMS_ROOT constant instead!
function absCMSDir() {
	return CMS_ROOT;
}

cms_core::$whitelist[] = 'linkToItem';
/*	function linkToItem(
		$cID, $cType = 'html', $fullPath = false, $request = '', $alias = false,
		$autoAddImportantRequests = false, $useAliasInAdminMode = false
	) {}
*/



cms_core::$whitelist[] = 'linkToEquivalentItem';
//	function linkToEquivalentItem($cID, $cType = 'html', $languageId = false, $fullPath = false, $request = '', $useAliasInAdminMode = false) {}

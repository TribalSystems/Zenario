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

function absCMSDir() {
	return CMS_ROOT;
}

function absCMSDirURL() {
	return httpOrHttps(). httpHost(). SUBDIRECTORY;
}

function CMSDir() {
	return SUBDIRECTORY;
}

function httpHost() {
	if (!empty($_SERVER['HTTP_HOST'])) {
		return $_SERVER['HTTP_HOST'];
	} else {
		return primaryDomain();
	}
}

function httpHostWithoutPort() {
	$host = httpHost();
	if (($pos = strpos($host, ':')) !== false) {
		$host = substr($host, 0, $pos);
	}
	return $host;
}

//Attempt to check whether we are in http or https, and return a value appropriately
//If the USE_FORWARDED_IP constant is set we should try to check the HTTP_X_FORWARDED_PROTO variable.
function httpOrhttps() {
	if ((isset($_SERVER['HTTPS'])
	  && engToBoolean($_SERVER['HTTPS']))
	 || (defined('USE_FORWARDED_IP')
	  && constant('USE_FORWARDED_IP')
	  && !empty($_SERVER['HTTP_X_FORWARDED_PROTO'])
	  && substr($_SERVER['HTTP_X_FORWARDED_PROTO'], 0, 5) == 'https')
	 || (!empty($_SERVER['SCRIPT_URI'])
	  && substr($_SERVER['SCRIPT_URI'], 0, 5) == 'https')) {
		return 'https://';
	} else {
		return 'http://';
	}
}

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

//	function moduleDir($moduleName, $subDir = '') {}

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

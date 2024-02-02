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

$dirname = dirname($cwd = $argv[0] ?? $_SERVER['SCRIPT_FILENAME'] ?? '.');
$cwd = $cwd[0] === '/'? $dirname. '/' : getcwd(). '/'. ($dirname === '.'? '' : $dirname. '/');

define('CMS_ROOT', dirname($cwd). '/');


//Try to include the siteconfig to work out what the subdirectory should be.
if (is_file(CMS_ROOT. 'zenario_siteconfig.php')) {
	require CMS_ROOT. 'zenario_siteconfig.php';
}

//If we couldn't work it out, try and keep going with a relative path
if (!defined('SUBDIRECTORY')) {
	define('SUBDIRECTORY', dirname(dirname($_SERVER['PHP_SELF'])). '/');
}


//Work around the problem where the .htaccess file can't do redirects properly without
//hardcoded the subdirectory in by handling them in PHP
if (!empty($_GET['redirect'])) {
	
	
	# Redirect anyone trying to access the admin login using "admin/" to just "admin", without the slash.
	# This should not be done silently, we want the visitor to see their URL changing in their browser,
	# so we need to use a PHP script for this.
	if ($_GET['redirect'] == 'admin') {
		$query = $_GET;
		unset($query['redirect']);
		
		if (empty($query)) {
			header(
				'location: '. SUBDIRECTORY. 'admin',
				true, 301);
		} else {
			header(
				'location: '. SUBDIRECTORY. 'admin?'. http_build_query($query),
				true, 301);
		}
		exit;
	}
	
	
	//
	// Correct any bad relative-URLs
	//
	
	//Only allow this script to redirect to certain directories, it's not a free-for-all
	$dir = 'zenario';
	if (!empty($_GET['redirectdir'])) {
		switch ($_GET['redirectdir']) {
			case 'cache':
			case 'public':
			case 'private':
			case 'zenario_custom':
			case 'zenario_extra_modules':
				$dir = $_GET['redirectdir'];
		}
	}
	
	//Only accept absolute paths from the URL.
	//(This is a bit paranoid, but I don't want someone using this as part of a XSS attack
	// if they find some other weakness they can exploit.)
	if (false !== strpos($_GET['redirect'], '?')
	 || false !== strpos($_GET['redirect'], '&')
	 || false !== strpos($_GET['redirect'], '../')) {
		exit;
	}
	
	
	$query = $_GET;
	unset($query['redirect']);
	unset($query['redirectdir']);
	
	header(
		'location: '. SUBDIRECTORY. $dir. '/'. $_GET['redirect']. '?'. http_build_query($query),
		true, 301);
}
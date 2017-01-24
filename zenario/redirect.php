<?php
/*
 * Copyright (c) 2017, Tribal Limited
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

//Cut down version of our standard header that works out where we are and where we are being accessed from
if (!defined('NOT_ACCESSED_DIRECTLY')) {
	//if (extension_loaded('mbstring')) mb_internal_encoding('UTF-8');
	
	//Attempt to set the working directory to the CMS Root, if it is not already
	//Note that I am using $_SERVER['SCRIPT_FILENAME'] and not __DIR__ to do this
	//because I want to resolve the relative URL when symlinks are used.
	$isFilelimit = 0;
	$dirname = $_SERVER['SCRIPT_FILENAME'];
	
	do {
		if (is_null($dirname) 
		 || ++$isFilelimit > 9) {
			echo
				'Could not resolve where the "zenario/" directory is.', "\n",
				'This could be a problem with a symlink, ',
				'or could be because you are trying to call this file from terminal ',
				'(which it is not designed to do).', "\n";
			exit;
		} else {
			chdir($dirname = dirname($dirname));
		}
	} while (!is_file('zenario/visitorheader.inc.php'));
	
	define('CMS_ROOT', $dirname. '/');
}




//Try to include the siteconfig to work out what the subdirectory should be.
if (is_file(CMS_ROOT. 'zenario_siteconfig.php')) {
	require CMS_ROOT. 'zenario_siteconfig.php';
}

//If we couldn't work it out, try and keep going with a relative path
if (!defined('SUBDIRECTORY')) {
	define('SUBDIRECTORY', '');
}


//Work around the problem where the .htaccess file can't do redirects properly without
//hardcoded the subdirectory in by handling them in PHP
if (!empty($_GET['redirect'])) {
	
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
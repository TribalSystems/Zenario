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

require 'cacheheader.inc.php';

//Get the requested file from the URL
//Exit if the request was not valid
if (!($path = preg_replace('@p=(.*)\&e=(\w+)@', '\1.\2', $_SERVER['QUERY_STRING'], 1))
 || ($path == $_SERVER['QUERY_STRING'])
 || (strpos($path, '..') !== false)
 || (!is_file($path))) {
	header('HTTP/1.0 404 Not Found');
	exit;
}

//A test option
if ($path == 'zenario/includes/test_files/is_htaccess_working.css') {
	echo 'Yes it is';
	exit;
}

//If this is a cached image, mark that it's been accessed
if (substr($path, 0, 8) == 'private/'
 || substr($path, 0, 7) == 'public/') {
	touch(dirname($path). '/accessed');
}



$ETag = 'zenario-loose_image--'. $_SERVER['HTTP_HOST']. '--'. $path;
useCache($ETag);
useGZIP();

//This is written for images, but use it to cover loose .js, .css and .ico files too
switch ($_GET['e']) {
	case 'css':
	case 'CSS':
		$mimeType = 'text/css';
		break;

	case 'ico':
	case 'ICO':
		$mimeType = 'image/vnd.microsoft.icon';
		break;

	case 'js':
	case 'JS':
		$mimeType = 'text/javascript';
		break;

	case 'woff':
	case 'WOFF':
		header('Access-Control-Allow-Origin: *');
		$mimeType = 'application/font-woff';
		break;
	
	default:
		$details = getimagesize($path);
		$mimeType = $details['mime'];
}

header('Content-Type: '. $mimeType);
readfile($path);
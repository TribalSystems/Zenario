<?php
/*
 * Copyright (c) 2020, Tribal Limited
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

//Only allow this to be run from the command line
if (empty($argv[0])) {
	exit;
}

if (!defined('CMS_ROOT')) {
	$dirname = dirname($cwd = $argv[0] ?? $_SERVER['SCRIPT_FILENAME'] ?? '.');
	$cwd = $cwd[0] === '/'? $dirname. '/' : getcwd(). '/'. ($dirname === '.'? '' : $dirname. '/');

	define('CMS_ROOT', dirname(dirname(dirname($cwd))). '/');
}

require CMS_ROOT. 'zenario/basicheader.inc.php';


clearstatcache();
function removeUnwantedCode($path) {
	$check = CMS_ROOT. 'zenario/libs/yarn/'. $path;
	if (is_dir($check)) {
		exec('svn del --force '. escapeshellarg($check));
		
		clearstatcache();
		if (is_dir($check)) {
			exec('rm -r '. escapeshellarg($check));
		}
	}
}

removeUnwantedCode('jquery/src');
removeUnwantedCode('jquery-lazy/node_modules');
removeUnwantedCode('respond.js/cross-domain');
removeUnwantedCode('respond.js/src');
removeUnwantedCode('respond.js/test');
removeUnwantedCode('spectrum-colorpicker/build');
removeUnwantedCode('spectrum-colorpicker/docs');
removeUnwantedCode('spectrum-colorpicker/example');
removeUnwantedCode('spectrum-colorpicker/test');
removeUnwantedCode('toastr/node_modules');
removeUnwantedCode('toastr/nuget');
removeUnwantedCode('toastr/tests');


$RecursiveDirectoryIterator = new \RecursiveDirectoryIterator(CMS_ROOT. 'zenario/libs/');
$RecursiveIteratorIterator = new \RecursiveIteratorIterator($RecursiveDirectoryIterator);

foreach ($RecursiveIteratorIterator as $dir) {
	if ($dir->isDir()
	 && ($pos = strpos($dir->getPathname(), '.git/.'))
	 && ($pos > strlen($dir->getPathname()) - 7)) {
		exec('svn del --force '. escapeshellarg($dir->getPathname()));
	}
}
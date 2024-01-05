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

//Only allow this to be run from the command line
if (empty($argv[0])) {
	exit;
}

if (!defined('CMS_ROOT')) {
	$dirname = dirname($cwd = $argv[0] ?? $_SERVER['SCRIPT_FILENAME'] ?? '.');
	$cwd = $cwd[0] === '/'? $dirname. '/' : getcwd(). '/'. ($dirname === '.'? '' : $dirname. '/');

	define('CMS_ROOT', dirname(dirname($cwd)). '/');
}

require CMS_ROOT. 'zenario/basicheader.inc.php';
clearstatcache();

//Remove a file or directory that a package manager downloaded for us, but we don't want
//and wish to remove to tidy up clutter and use less space on the disk.
function removeUnwantedCode($path, $packageManager = 'yarn') {
	$check = CMS_ROOT. 'zenario/libs/'. $packageManager. '/'. $path;
	if (file_exists($check)) {
		exec('svn del --force '. escapeshellarg($check));
		
		clearstatcache();
		if (is_dir($check)) {
			exec('rm -r '. escapeshellarg($check));
		
		} elseif (is_file($check)) {
			exec('rm '. escapeshellarg($check));
		}
	}
}

//Given a repo downloaded using yarn, go through and remove everything but the things we
//actually want.
function filterWantedCode($path, ...$keeps) {
	
	//Hard-coded to only work for yarn repos for now; generally composer repos are much
	//tidier and don't need this!
	$packageManager = 'yarn';
	$dir = CMS_ROOT. 'zenario/libs/'. $packageManager. '/'. $path;
	
	foreach (scandir($dir) as $actualName) {
		
		//To make the rules easier to specify, all logic is done using lower-case.
		$name = strtolower($actualName);
		
		//To make the rules easier to specify, exclude some known filetypes from the logic
		//so we don't need to specify them.
		foreach ([
			'.txt', '.md', '.markdown',
			'.map', '.js', '.css', '.min'
		] as $removeExtension) {
			$name = ze\ring::chopSuffix($name, $removeExtension, $returnStringOnFailure = true);
		}
		
		if ($name == '.'
		 || $name == '..'
			
			//Have some exceptions that are always kept and never filtered
		 || $name == 'authors'
		 || $name == 'bower.json'
		 || $name == 'changelog'
		 || $name == 'code_of_conduct'
		 || $name == 'composer.json'
		 || $name == 'contributing'
		 || $name == 'license'
		 || $name == 'package.json'
		 || $name == 'readme'
		
			//Keep anything named in the inputs
		 || in_array($name, $keeps)) {
			//Keep this file
		} else {
			removeUnwantedCode($path. '/'. $actualName, $packageManager);
		}
	}
}

function removeUnwantedComposerCode($path) {
	removeUnwantedCode($path, 'composer_dist');
	removeUnwantedCode($path, 'composer_no_dist');
}

function copyMod($path, $packageManager = 'yarn') {
	$from = CMS_ROOT. 'zenario/libs/mods/'. $path;
	$to = CMS_ROOT. 'zenario/libs/'. $packageManager. '/'. $path;
	
	if (file_exists($to)) {
		exec('rm '. escapeshellarg($to));
	}
	
	exec('cp '. escapeshellarg($from). ' '. escapeshellarg($to));
}


//Add our custom mod files to some third-party libraries
copyMod('ace-builds/src-min-noconflict/mode-phi.js');


//Remove some clutter from composer libraries that we don't want
filterWantedCode('ace-builds', 'src-min-noconflict');
removeUnwantedCode('ace-builds/src-min-noconflict/mode-xquery.js');		//Remove the xquery language as those files are quite large
removeUnwantedCode('ace-builds/src-min-noconflict/worker-xquery.js');
filterWantedCode('animate.css', 'animate');
filterWantedCode('cytoscape', 'dist');
filterWantedCode('@fortawesome/fontawesome-free', 'attribution.js', 'css', 'webfonts');
filterWantedCode('@fortawesome/fontawesome-free/css', 'all', 'v4-shims');
removeUnwantedCode('jquery/src');
filterWantedCode('jquery/dist', 'core', 'jquery');
filterWantedCode('jquery-cycle2', 'build');
filterWantedCode('jquery-cycle2/build', 'jquery.cycle2');
filterWantedCode('jquery-doubletaptogo', 'dist');
filterWantedCode('jquery-lazy', 'jquery.lazy');
filterWantedCode('jquery-multiselect', 'jquery-multiselect');
filterWantedCode('js-crc', 'src');
filterWantedCode('moment', 'moment');
filterWantedCode('moment-timezone', 'builds');
filterWantedCode('moment-timezone/builds', 'moment-timezone-with-data-10-year-range');
removeUnwantedCode('moment-timezone/builds/moment-timezone-with-data-10-year-range.js');
filterWantedCode('rcrop', 'dist');
filterWantedCode('spectrum-colorpicker', 'i18n', 'spectrum', 'themes');
filterWantedCode('swiper', 'swiper-bundle');
filterWantedCode('toastr', 'build', 'toastr');
filterWantedCode('underscore.string', 'dist');
filterWantedCode('wow.js', 'dist');
filterWantedCode('wow.js/dist', 'wow');
filterWantedCode('zxcvbn', 'dist');

//Remove some optional dependancies that the yarn packages we've asked for installed,
//but I don't actually think we need
removeUnwantedCode('classlist-polyfill');
removeUnwantedCode('lodash');		//Unwanted (dev?) dependancy from cytoscape and a few others?
removeUnwantedCode('lodash.get');
removeUnwantedCode('lodash.set');
removeUnwantedCode('lodash.topath');
removeUnwantedCode('lodash.debounce');
removeUnwantedCode('lodash.throttle');
removeUnwantedCode('heap');
removeUnwantedCode('sprintf-js');
removeUnwantedCode('ssr-window');
removeUnwantedCode('util-deprecate');


//Doing all of this will mean that Yarn's cache meta info of what files it has downloaded
//is out of date. We'll need to delete this so Yarn knows it has been modified.
removeUnwantedCode('.yarn-integrity');


//Remove some clutter from composer libraries that we don't want
removeUnwantedComposerCode('bin');
//removeUnwantedComposerCode('aws/aws-sdk-php/src/data');	# Actually this seems to be needed...
removeUnwantedComposerCode('geoip2/geoip2/examples');
removeUnwantedComposerCode('guzzlehttp/psr7/.github');
removeUnwantedComposerCode('matthiasmullie/minify/bin');
removeUnwantedComposerCode('maxmind/web-service-common/dev-bin');
removeUnwantedComposerCode('maxmind-db/reader/ext');
removeUnwantedComposerCode('mustangostang/spyc/examples');
removeUnwantedComposerCode('mustangostang/spyc/php4');
removeUnwantedComposerCode('mustangostang/spyc/tests');
removeUnwantedComposerCode('powder96/numbers.php/examples');
removeUnwantedComposerCode('smottt/wideimage/demo');
removeUnwantedComposerCode('smottt/wideimage/test');
removeUnwantedComposerCode('twig/twig/doc');
removeUnwantedComposerCode('twig/twig/.github');



$RecursiveDirectoryIterator = new \RecursiveDirectoryIterator(CMS_ROOT. 'zenario/libs/');
$RecursiveIteratorIterator = new \RecursiveIteratorIterator($RecursiveDirectoryIterator);

foreach ($RecursiveIteratorIterator as $dir) {
	if ($dir->isDir()
	 && ($pos = strpos($dir->getPathname(), '.git/.'))
	 && ($pos > strlen($dir->getPathname()) - 7)) {
		exec('svn del --force '. escapeshellarg($dir->getPathname()));
	}
}
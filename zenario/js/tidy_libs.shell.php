<?php
/*
 * Copyright (c) 2023, Tribal Limited
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

function removeUnwantedComposerCode($path) {
	removeUnwantedCode($path, 'composer_dist');
	removeUnwantedCode($path, 'composer_no_dist');
}

removeUnwantedCode('classlist-polyfill');
removeUnwantedCode('lodash');		//Unwanted (dev?) dependancy from cytoscape and a few others?
removeUnwantedCode('lodash.get');
removeUnwantedCode('lodash.set');
removeUnwantedCode('lodash.topath');
removeUnwantedCode('lodash.debounce');
removeUnwantedCode('lodash.throttle');
removeUnwantedCode('heap');
removeUnwantedCode('cytoscape/.browserslist');
removeUnwantedCode('cytoscape/.github');
removeUnwantedCode('cytoscape/.size-snapshot.json');
removeUnwantedCode('cytoscape/license-update.js');
removeUnwantedCode('cytoscape/rollup.config.js');
removeUnwantedCode('cytoscape/src');
removeUnwantedCode('jquery/src');
removeUnwantedCode('jquery/dist/core.js');
removeUnwantedCode('jquery/dist/jquery.slim.js');
removeUnwantedCode('jquery/dist/jquery.slim.min.js');
removeUnwantedCode('jquery/dist/jquery.slim.min.map');
removeUnwantedCode('jquery-cycle2/build/core');
removeUnwantedCode('jquery-cycle2/build/plugin');
removeUnwantedCode('jquery-cycle2/src');
removeUnwantedCode('jquery-doubletaptogo/src');
removeUnwantedCode('jquery-doubletaptogo/yarn.lock');
removeUnwantedCode('jquery-lazy/node_modules');
removeUnwantedCode('js-crc/build');
removeUnwantedCode('js-crc/tests');
removeUnwantedCode('moment/dist');
removeUnwantedCode('moment/locale');
removeUnwantedCode('moment/min');
removeUnwantedCode('moment/src');
removeUnwantedCode('moment-timezone/.github');
removeUnwantedCode('moment-timezone/builds/moment-timezone-with-data-10-year-range.js');
//removeUnwantedCode('moment-timezone/builds/moment-timezone-with-data-10-year-range.min.js');
removeUnwantedCode('moment-timezone/builds/moment-timezone-with-data-1970-2030.js');
removeUnwantedCode('moment-timezone/builds/moment-timezone-with-data-1970-2030.min.js');
removeUnwantedCode('moment-timezone/builds/moment-timezone-with-data-2012-2022.js');
removeUnwantedCode('moment-timezone/builds/moment-timezone-with-data-2012-2022.min.js');
removeUnwantedCode('moment-timezone/builds/moment-timezone-with-data.js');
removeUnwantedCode('moment-timezone/builds/moment-timezone-with-data.min.js');
removeUnwantedCode('moment-timezone/builds/moment-timezone.min.js');
removeUnwantedCode('moment-timezone/blah.tar.gz');
removeUnwantedCode('moment-timezone/curlxx.tar.gz');
removeUnwantedCode('moment-timezone/data');
removeUnwantedCode('moment-timezone/tzdata-latest.tar.gz');
removeUnwantedCode('rcrop/demos');
removeUnwantedCode('rcrop/libs');
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
removeUnwantedCode('wow.js/css');
removeUnwantedCode('wow.js/spec');

//Remove some optional dependancies that the yarn packages we've asked for installed, but I don't actuall think we need
removeUnwantedCode('sprintf-js');
removeUnwantedCode('util-deprecate');


//Remove some clutter from composer libraries that we don't want
removeUnwantedComposerCode('bin');
removeUnwantedComposerCode('geoip2/geoip2/examples');
removeUnwantedComposerCode('guzzlehttp/psr7/.github');
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
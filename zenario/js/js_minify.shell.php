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
require 'zenario/includes/js_minify.inc.php';







if (!isset($argv[1])) {
	$arg1 = '';
} else {
	$arg1 = $argv[1];
}
if (!isset($argv[2])) {
	$arg2 = '';
} else {
	$arg2 = $argv[2];
}

if ($arg2 == 'p'
 || $arg2 == 'c'
 || $arg2 == 'v') {
	$swap = $arg2;
	$arg2 = $arg1;
	$arg1 = $swap;

} elseif ($arg2) {
	displayUsage();
	exit;
}


if ($arg1 == 'p') {
	$level = 1;
	$specific = $arg2;

} elseif ($arg1 == 'c') {
	$level = 2;
	$specific = $arg2;

} elseif ($arg1 == 'v') {
	$level = 3;
	$specific = $arg2;

} else {
	$level = 2;
	$specific = $arg1;
}

define('IGNORE_REVERTS', false);
define('RECOMPRESS_EVERYTHING', $specific);


if ($specific) {
	if ((is_dir($dir = $specific)) && ($scan = scandir($dir))) {
		
		if (substr($dir, -1) != '/') {
			$dir .= '/';
		}
		
		foreach ($scan as $file) {
			if (substr($file, -3) == '.js'
			 && substr($file, -7) != '.min.js'
			 && substr($file, -9) != '.nomin.js'
			 && substr($file, -8) != '.pack.js') {
				$file = substr($file, 0, -3);
				minify($dir, $file, $level);
			
			} else
			if (substr($file, -4) == '.css'
			 && substr($file, -8) != '.min.css') {
				$file = substr($file, 0, -4);
				minify($dir, $file, $level, '.css');
			}
		}
	
	} elseif (is_file($specific)) {
		$dir = dirname($specific) . '/';
		$file = basename($specific);
		if (substr($file, -3) == '.js'
		 && substr($file, -7) != '.min.js'
		 && substr($file, -9) != '.nomin.js'
		 && substr($file, -8) != '.pack.js') {
			$file = substr($file, 0, -3);
			minify($dir, $file, $level);
			
		} else
		if (substr($file, -4) == '.css'
		 && substr($file, -8) != '.min.css') {
			$file = substr($file, 0, -4);
			minify($dir, $file, $level, '.css');
		}
	
	} else {
		displayUsage();
	}
	exit;
}

//Minify JavaScript files in the API directory
if ((is_dir($dir = 'zenario/api/')) && ($scan = scandir($dir))) {
	foreach ($scan as $file) {
		if (substr($file, -3) == '.js'
		 && substr($file, -7) != '.min.js'
		 && substr($file, -9) != '.nomin.js') {
			$file = substr($file, 0, -3);
			minify($dir, $file, $level);
		
		//This code would make JSON copies of all of the schema files
		//} elseif (substr($file, -5) == '.yaml') {
		//	$file = substr($file, 0, -5);
		//	minify($dir, $file, $level, '.yaml');
		}
	}
}

//Minify the js directory
if ((is_dir($dir = 'zenario/js/')) && ($scan = scandir($dir))) {
	foreach ($scan as $file) {
		if (substr($file, -3) == '.js'
		 && substr($file, -7) != '.min.js'
		 && substr($file, -9) != '.nomin.js'
		 && substr($file, -8) != '.pack.js') {
			$file = substr($file, 0, -3);
			minify($dir, $file, $level);
		}
	}
}

//Minify the styles directory
if ((is_dir($dir = 'zenario/styles/')) && ($scan = scandir($dir))) {
	foreach ($scan as $file) {
		if (substr($file, -4) == '.css'
		 && substr($file, -8) != '.min.css') {
			$file = substr($file, 0, -4);
			minify($dir, $file, $level, '.css');
		}
	}
}

//Minify plugin/module js files
foreach ([
	'zenario/modules/',
	//'zenario_custom/modules/'
	'zenario_extra_modules/'
] as $path) {
	if (is_dir($path)) {
		if ($scan = scandir($path)) {
			foreach ($scan as $module) {
				if (substr($module, 0, 1) != '.' && substr($module, 0, 3) != 'my_' && is_dir($dir = $path. $module. '/js/') && ($scan = scandir($dir))) {
					foreach ($scan as $file) {
						if (substr($file, -3) == '.js'
						 && substr($file, -7) != '.min.js'
						 && substr($file, -9) != '.nomin.js'
						 && substr($file, -8) != '.pack.js') {
							$file = substr($file, 0, -3);
							minify($dir, $file, $level);
						}
					}
				}
			}
		}
	}
}

//Minify jquery files
if ((is_dir($dir = 'zenario/libs/manually_maintained/mit/jquery/')) && ($scan = scandir($dir))) {
	foreach ($scan as $file) {
		if (substr($file, -3) == '.js'
		 && substr($file, -7) != '.min.js'
		 && substr($file, -9) != '.nomin.js'
		 && substr($file, -8) != '.pack.js') {
			$file = substr($file, 0, -3);
			minify($dir, $file, $level);
		}
	}
}

//Minify jquery css files
if ((is_dir($dir = 'zenario/libs/manually_maintained/mit/jquery/css/')) && ($scan = scandir($dir))) {
	foreach ($scan as $file) {
		if (substr($file, -4) == '.css'
		 && substr($file, -8) != '.min.css') {
			$file = substr($file, 0, -4);
			minify($dir, $file, $level, '.css');
		}
	}
}

//Minify TinyMCE files
minify(TINYMCE_DIR, 'tinymce', $level, '.js');
minify(TINYMCE_DIR. 'themes/modern/', 'theme', $level, '.js');
if ($scan = scandir(TINYMCE_DIR. 'plugins')) {
	foreach ($scan as $module) {
		if (substr($module, 0, 1) != '.' && is_dir($dir = TINYMCE_DIR. 'plugins/'. $module. '/')) {
			minify($dir, 'plugin', $level, '.js');
		}
	}
}


//Minify some other jQuery extensions
minify('zenario/libs/manually_maintained/bsd/jquery_roundabout/', 'jquery.roundabout', $level, '.js');
minify('zenario/libs/manually_maintained/bsd/jquery_roundabout/', 'jquery.roundabout-shapes', $level, '.js');
minify('zenario/libs/manually_maintained/mit/jquery.fix.clone/', 'jquery.fix.clone', $level, '.js');
minify('zenario/libs/yarn/jquery-lazy/', 'jquery.lazy', $level, '.js');
minify('zenario/libs/yarn/jquery-lazy/', 'jquery.lazy.plugins', $level, '.js');
minify('zenario/libs/yarn/jquery-multiselect/', 'jquery-MultiSelect', $level, '.js');
minify('zenario/libs/yarn/jquery-multiselect/', 'jquery-MultiSelect', $level, '.css');

//Minify colorbox
minify('zenario/libs/manually_maintained/mit/colorbox/', 'jquery.colorbox', $level, '.js');

//Minify Tokenizer
minify('zenario/libs/manually_maintained/bsd/tokenize/', 'jquery.tokenize', $level, '.css');
minify('zenario/libs/manually_maintained/bsd/tokenize/', 'jquery.tokenize', $level, '.js');

//Minify enquire.js
minify('zenario/libs/manually_maintained/mit/enquire/', 'enquire', $level, '.js');

//Minify intro.js
minify('zenario/libs/manually_maintained/mit/intro/', 'introjs', $level, '.css');
minify('zenario/libs/manually_maintained/mit/intro/', 'introjs-rtl', $level, '.css');
minify('zenario/libs/manually_maintained/mit/intro/', 'intro', $level, '.js');

//Minify jPaginator
minify('zenario/libs/manually_maintained/mit/jpaginator/', 'jPaginator', $level, '.js');

//Minify doubleTapToGo
minify('zenario/libs/manually_maintained/mit/doubletap-to-go/', 'jquery.doubletaptogo', $level, '.js');

//Minify Modernizr
minify('zenario/libs/manually_maintained/mit/modernizr/', 'modernizr', $level, '.js');

//Minifythe Responsive Multilevel Menu plugin
minify('zenario/libs/manually_maintained/mit/ResponsiveMultiLevelMenu/js/', 'jquery.dlmenu', $level, '.js');

//Minify slimmenu
minify('zenario/libs/manually_maintained/mit/slimmenu/', 'slimmenu', $level, '.css');
minify('zenario/libs/manually_maintained/mit/slimmenu/', 'jquery.slimmenu', $level, '.js');

//Minify Spectrum
minify('zenario/libs/yarn/spectrum-colorpicker/', 'spectrum', $level, '.css');
minify('zenario/libs/yarn/spectrum-colorpicker/', 'spectrum', $level, '.js');

//Minify the split library for IE 8
minify('zenario/libs/manually_maintained/mit/split/', 'split', $level, '.js');

//Minify Toastr
minify('zenario/libs/yarn/toastr/build/', 'toastr', $level, '.css');
minify('zenario/libs/yarn/toastr/', 'toastr', $level, '.js');

//Minify Underscore
minify('zenario/libs/manually_maintained/mit/underscore/', 'underscore', $level, '.js');

//Minify the libraries in the public domain directory
minify('zenario/libs/manually_maintained/public_domain/json/', 'json2', $level, '.js');
minify('zenario/libs/manually_maintained/public_domain/mousehold/', 'mousehold', $level, '.js');
minify('zenario/libs/manually_maintained/public_domain/tv4/', 'tv4', $level, '.js');

//Minify the JavaScript MD5 library
minify('zenario/libs/manually_maintained/bsd/javascript_md5/', 'md5', $level, '.js');
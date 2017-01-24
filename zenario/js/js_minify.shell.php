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


define('IGNORE_REVERTS', false);
define('RECOMPRESS_EVERYTHING', false);
define('YUI_COMPRESSOR_PATH', 'zenario/libraries/bsd/yuicompressor/yuicompressor-2.4.8.jar');
define('CLOSURE_COMPILER_PATH', 'zenario/libraries/not_to_redistribute/closure-compiler/compiler.jar');


//Don't let this be called from the browser
if (!isset($argv[0])) {
	exit;
}


function displayUsage() {
	echo
"A tool for minifying JavaScript used by Zenario;
this is a wrapper for calling YUI Compressor (http://developer.yahoo.com/yui/compressor/)
or Closure Compiler (https://developers.google.com/closure/compiler/) on all relevant files.

Usage:
	php js_minify
		Minify all of the JavaScript and CSS files that the CMS uses.
	php js_minify filename.js
		Minify a specific JavaScript and CSS files.
	php js_minify directory
		Minify all of the JavaScript and CSS files in a specific directory.
	php js_minify p
		List the files that would be minified, but don't do anything.
	php js_minify v
		Use debug/verbose mode when minifying.

Notes:
  * The Zenario download does not come with a copy of Closure Compiler to save space,
 	but if you download a copy and put it in the right place then this program will use it.
  * If you have svn, this script will only minify files that svn says are new or modified.

";
	exit;
}

//Macros and replacements
function applyCompilationMacros($code) {
	
	//Check if this JavaScript file uses the zenario.lib function.
	//If so, we can use the has() shortcut.
	//If not, we need to write out zenario.has() in full.
	if (false !== strpos($code, 'zenario.lib(')
	 && false !== strpos($code, 'extensionOf, methodsOf, has')) {
		$has = 'has';
	} else {
		$has = 'zenario.has';
	}
	
	//"foreach" is a macro for "for .. in ... hasOwnProperty"
	$patterns = array();
	$replacements = array();
	$patterns[] = '/\bforeach\b\s*\(\s*(.+?)\s*\bas\b\s*(\bvar\b |)\s*(.+?)\s*\=\>\s*(\bvar\b |)\s*(.+?)\s*\)\s*\{/';
	$replacements[] = 'for (\2\3 in \1) { if (!'. $has. '(\1, \3)) continue; \4 \5 = \1[\3];';
	$patterns[] = '/\bforeach\b\s*\(\s*(.+?)\s*\bas\b\s*(\bvar\b |)\s*(.+?)\s*\)\s*\{/';
	$replacements[] = 'for (\2\3 in \1) { if (!'. $has. '(\1, \3)) continue;';
	
	//We don't have node as a dependency so we can't use Babel.
	//So we'll try and make do with a few replacements instead!
	$patterns[] = '/\(([\w\s,]*)\)\s*\=\>\s*\{/';
	$replacements[] = 'function ($1) {';
	$patterns[] = '/(\b\w+\b)\s*\=\>\s*\{/';
	$replacements[] = 'function ($1) {';
	
	return preg_replace($patterns, $replacements, $code);
}


//Change directory to the CMS root directory
$prefix = '';
do {
	if (is_file($prefix. 'zenario/basicheader.inc.php')) {
		if ($prefix) {
			chdir($prefix);
		}
		break;
	} elseif ($i > 5) {
		echo "Could not find the root directory of the CMS\n";
		exit;
	}
	$prefix .= '../';
} while (true);

//Define a constant to mark than any further include files have been legitamately included
define('THIS_FILE_IS_BEING_DIRECTLY_ACCESSED', false);
define('NOT_ACCESSED_DIRECTLY', true);
require 'zenario/admin/db_updates/latest_revision_no.inc.php';

//Use the closure compiler for .js files if it has been installed
//(otherwise we must use YUI Compressor which gives slightly larger filesizes).
define('USE_CLOSURE_COMPILER', file_exists(CLOSURE_COMPILER_PATH));



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


function minify($dir, $file, $level, $ext = '.js') {
	
	$isCSS = $ext == '.css';
	$yamlToJSON = $ext == '.yaml';
	
	if ($yamlToJSON) {
		$srcFile = $dir. $file. $ext;
		$minFile = $dir. $file. '.json';
	} else {
		$srcFile = $dir. $file. $ext;
		$minFile = $dir. $file. '.min'. $ext;
		//$mapFile = $dir. $file. '.min'. '.map';
	}
	
	if (!file_exists($srcFile)) {
		return;
	}
	
	$v = '';
	if ($level > 2) {
		echo ':'. $srcFile. "\n";
		
		if (!$isCSS && USE_CLOSURE_COMPILER) {
			$v = '--warning_level VERBOSE ';
		} else {
			$v = '-v ';
		}
	}
	
	if (!file_exists($dir. $file. '.pack.js')) {
		
		$svnAdd = false;
		$modified = true;
		$needsreverting = false;
		
		if (is_dir('.svn')) {
			$svnAdd = !file_exists($minFile);
			
			$modified = 
				RECOMPRESS_EVERYTHING ||
				exec('svn status '.
							escapeshellarg($srcFile)
					);
			
			if (!$svnAdd && !$modified) {
				$needsreverting = 
					exec('svn status '.
								escapeshellarg($minFile)
						);
			}
		}
		
		if ($modified || ($needsreverting && !IGNORE_REVERTS)) {
			if ($needsreverting && !IGNORE_REVERTS) {
				echo '-reverting '. $minFile. "\n";
			} else {
				echo '-compressing '. $srcFile. "\n";
			}
			
			if ($level > 1) {
				if ($needsreverting && !IGNORE_REVERTS) {
					exec('svn revert '.
								escapeshellarg($minFile)
						);
				} else {
					
					//For our JavaScript files, automatically add
					//foreach-style loops that also automatically add a call
					//to .hasOwnProperty() for safety.
					//Note that JavaScript works slightly differently to php; if you only
					//specifiy one variable then it becomes the key, not the value
					if (!$isCSS
					 && !$yamlToJSON
					 && substr($dir, 0, 18) != 'zenario/libraries/') {
						$tmpFile = tempnam(sys_get_temp_dir(), 'js');
						file_put_contents($tmpFile, applyCompilationMacros(file_get_contents($srcFile)));
						$srcFile = $tmpFile;
					}
					
					
					if ($yamlToJSON) {
						require_once 'zenario/libraries/mit/spyc/Spyc.php';
						$tags = Spyc::YAMLLoad($srcFile);
						file_put_contents($minFile, json_encode($tags));
					
					} elseif (!$isCSS && USE_CLOSURE_COMPILER) {
						exec('java -jar '. escapeshellarg(CLOSURE_COMPILER_PATH). ' '. $v. ' --compilation_level SIMPLE_OPTIMIZATIONS --js_output_file '.
									escapeshellarg($minFile).
							//Code to generate a source-map if needed
								//' --source_map_format=V3 --create_source_map '.
								//	escapeshellarg($mapFile).
								' --js '. 
									escapeshellarg($srcFile)
							);
					} else {
						exec('java -jar '. escapeshellarg(YUI_COMPRESSOR_PATH). ' --type '. ($isCSS? 'css' : 'js'). ' '. $v. '--line-break 150 -o '.
									escapeshellarg($minFile).
								' '. 
									escapeshellarg($srcFile)
							);
					}
				}
			}
			
			if ($svnAdd) {
				echo '-svn adding '. $minFile. "\n";
				
				if ($level > 1) {
					exec('svn add '.
								escapeshellarg($minFile)
						);
				}
			}
		}
	}
}

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
foreach (array(
	'zenario/modules/',
	//'zenario_custom/modules/'
	'zenario_extra_modules/'
) as $path) {
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
if ((is_dir($dir = 'zenario/libraries/mit/jquery/')) && ($scan = scandir($dir))) {
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
if ((is_dir($dir = 'zenario/libraries/mit/jquery/css/')) && ($scan = scandir($dir))) {
	foreach ($scan as $file) {
		if (substr($file, -4) == '.css'
		 && substr($file, -8) != '.min.css') {
			$file = substr($file, 0, -4);
			minify($dir, $file, $level, '.css');
		}
	}
}

//Minify TinyMCE files
minify(TINYMCE_DIR, 'tinymce.jquery', $level, '.js');
minify(TINYMCE_DIR. 'themes/modern/', 'theme', $level, '.js');
if ($scan = scandir(TINYMCE_DIR. 'plugins')) {
	foreach ($scan as $module) {
		if (substr($module, 0, 1) != '.' && is_dir($dir = TINYMCE_DIR. 'plugins/'. $module. '/')) {
			minify($dir, 'plugin', $level, '.js');
		}
	}
}


//Minify colorbox
minify('zenario/libraries/mit/colorbox/', 'jquery.colorbox', $level, '.js');

//Minify jQuery Roundabout
minify('zenario/libraries/bsd/jquery_roundabout/', 'jquery.roundabout', $level, '.js');
minify('zenario/libraries/bsd/jquery_roundabout/', 'jquery.roundabout-shapes', $level, '.js');

//Minify Modernizr
minify('zenario/libraries/bsd/modernizr/', 'modernizr', $level, '.js');

//Minify Tokenizer
minify('zenario/libraries/bsd/tokenize/', 'jquery.tokenize', $level, '.css');
minify('zenario/libraries/bsd/tokenize/', 'jquery.tokenize', $level, '.js');

//Minify enquire.js
minify('zenario/libraries/mit/enquire/', 'enquire', $level, '.js');

//Minify intro.js
minify('zenario/libraries/mit/intro/', 'introjs', $level, '.css');
minify('zenario/libraries/mit/intro/', 'introjs-rtl', $level, '.css');
minify('zenario/libraries/mit/intro/', 'intro', $level, '.js');

//Minify jPaginator
minify('zenario/libraries/mit/jpaginator/', 'jPaginator', $level, '.js');

//Minify Respond
minify('zenario/libraries/mit/respond/', 'respond', $level, '.js');

//Minifythe Responsive Multilevel Menu plugin
minify('zenario/libraries/mit/ResponsiveMultiLevelMenu/js/', 'jquery.dlmenu', $level, '.js');

//Minify slimmenu
minify('zenario/libraries/mit/slimmenu/', 'slimmenu', $level, '.css');
minify('zenario/libraries/mit/slimmenu/', 'jquery.slimmenu', $level, '.js');

//Minify Spectrum
minify('zenario/libraries/mit/spectrum/', 'spectrum', $level, '.css');
minify('zenario/libraries/mit/spectrum/', 'spectrum', $level, '.js');

//Minify the split library for IE 8
minify('zenario/libraries/mit/split/', 'split', $level, '.js');

//Minify Toastr
minify('zenario/libraries/mit/toastr/', 'toastr', $level, '.css');
minify('zenario/libraries/mit/toastr/', 'toastr', $level, '.js');

//Minify Underscore
minify('zenario/libraries/mit/underscore/', 'underscore', $level, '.js');

//Minify the libraries in the public domain directory
minify('zenario/libraries/public_domain/json/', 'json2', $level, '.js');
minify('zenario/libraries/public_domain/mousehold/', 'mousehold', $level, '.js');
minify('zenario/libraries/public_domain/tv4/', 'tv4', $level, '.js');

//Minify the JavaScript MD5 library
minify('zenario/libraries/bsd/javascript_md5/', 'md5', $level, '.js');
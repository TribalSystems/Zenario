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

define('TINYMCE_DIR', 'lgpl/tinymce_4_0_19b');

function displayUsage() {
	echo
"A tool for minifying JavaScript used by Zenario;
this is a wrapper for calling YUI Compressor (http://developer.yahoo.com/yui/compressor/)
and Closure Compiler (https://developers.google.com/closure/compiler/) on all relevant files.

Usage:
	php js_minify p
		Preview the changes that are about to happen.
	php js_minify c
		Create/update minified JavaScript and CSS files.
	php js_minify v
		Create/update minified JavaScript and CSS files, with debug/verbose mode enabled.
";
	exit;
}

//Macros and replacements
class macros {
	public static $patterns = array();
	public static $replacements = array();
}

macros::$patterns[] = '/\bforeach\b\s*\(\s*(.+?)\s*\bas\b\s*(\bvar\b |)\s*(.+?)\s*\=\>\s*(\bvar\b |)\s*(.+?)\s*\)\s*\{/';
macros::$replacements[] = 'for (\2\3 in \1) { if (!zenario.has(\1, \3)) continue; \4 \5 = \1[\3];';
macros::$patterns[] = '/\bforeach\b\s*\(\s*(.+?)\s*\bas\b\s*(\bvar\b |)\s*(.+?)\s*\)\s*\{/';
macros::$replacements[] = 'for (\2\3 in \1) { if (!zenario.has(\1, \3)) continue;';


$prefix = '';
do {
	if (is_file($prefix. 'zenario/cacheheader.inc.php')) {
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



if (!isset($argv[1])) {
	displayUsage();

} elseif ($argv[1] == 'p') {
	$level = 1;

} elseif ($argv[1] == 'c') {
	$level = 2;

} elseif ($argv[1] == 'v') {
	$level = 3;

} else {
	displayUsage();
}



define('USE_CLOSURE_COMPILER', true);
define('IGNORE_REVERTS', false);
define('RECOMPRESS_EVERYTHING', false);



function minify($dir, $file, $level, $ext = '.js', $punyMCE = false) {
	
	$isCSS = $ext == '.css';
	$yamlToJSON = $ext == '.yaml';
	
	if ($yamlToJSON) {
		$srcFile = $dir. $file. $ext;
		$minFile = $dir. $file. '.json';
	} elseif ($punyMCE) {
		$srcFile = $dir. $file. '_src'. $ext;
		$minFile = $dir. $file. $ext;
		//$mapFile = $dir. $file. '.map';
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
		
		if ($modified || $svnAdd || ($needsreverting && !IGNORE_REVERTS)) {
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
						file_put_contents($tmpFile, preg_replace(macros::$patterns, macros::$replacements, file_get_contents($srcFile)));
						$srcFile = $tmpFile;
					}
					
					
					if ($yamlToJSON) {
						require_once 'zenario/libraries/mit/spyc/Spyc.php';
						$tags = Spyc::YAMLLoad($srcFile);
						file_put_contents($minFile, json_encode($tags));
					
					} elseif (!$isCSS && USE_CLOSURE_COMPILER) {
						exec('java -jar zenario/libraries/not_to_redistribute/closure-compiler/compiler.jar '. $v. ' --compilation_level SIMPLE_OPTIMIZATIONS --js_output_file '.
									escapeshellarg($minFile).
							//Code to generate a source-map if needed
								//' --source_map_format=V3 --create_source_map '.
								//	escapeshellarg($mapFile).
								' --js '. 
									escapeshellarg($srcFile)
							);
					} else {
						exec('java -jar zenario/libraries/not_to_redistribute/yuicompressor/yuicompressor-2.4.2.jar '. $v. '--line-break 150 -o '.
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


//Minify JavaScript files in the API directory
if ((is_dir($dir = 'zenario/api/')) && ($scan = scandir($dir))) {
	foreach ($scan as $file) {
		if (substr($file, -3) == '.js' && substr($file, -7) != '.min.js') {
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
	'zenario_extra_modules/',
	'zenario_custom/modules/'
) as $path) {
	if (is_dir($path)) {
		if ($scan = scandir($path)) {
			foreach ($scan as $module) {
				if (substr($module, 0, 1) != '.' && substr($module, 0, 3) != 'my_' && is_dir($dir = $path. $module. '/js/') && ($scan = scandir($dir))) {
					foreach ($scan as $file) {
						if (substr($file, -3) == '.js'
						 && substr($file, -7) != '.min.js'
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
minify('zenario/libraries/'. TINYMCE_DIR. '/', 'tinymce.jquery', $level, '.js');
minify('zenario/libraries/'. TINYMCE_DIR. '/themes/modern/', 'theme', $level, '.js');
if ($scan = scandir('zenario/libraries/'. TINYMCE_DIR. '/plugins')) {
	foreach ($scan as $module) {
		if (substr($module, 0, 1) != '.' && is_dir($dir = 'zenario/libraries/'. TINYMCE_DIR. '/plugins/'. $module. '/')) {
			minify($dir, 'plugin', $level, '.js');
		}
	}
}


//Minify PunyMCE files
minify('zenario/libraries/lgpl/punymce/', 'puny_mce', $level, '.js', true);
if ($scan = scandir('zenario/libraries/lgpl/punymce/plugins')) {
	foreach ($scan as $module) {
		if (substr($module, 0, 1) != '.' && substr($module, -7) != '_src.js') {
			if (is_dir($dir = 'zenario/libraries/lgpl/punymce/plugins/'. $module. '/')) {
				minify($dir, $module, $level, '.js', true);
			} elseif (substr($module, -3) == '.js') {
				minify('zenario/libraries/lgpl/punymce/plugins/', substr($module, 0, -3), $level, '.js', true);
			}
		}
	}
}


//Minify jQuery Roundabout
minify('zenario/libraries/bsd/jquery_roundabout/', 'jquery.roundabout', $level, '.js');
minify('zenario/libraries/bsd/jquery_roundabout/', 'jquery.roundabout-shapes', $level, '.js');

//Minify intro.js
minify('zenario/libraries/mit/intro/', 'introjs', $level, '.css');
minify('zenario/libraries/mit/intro/', 'introjs-rtl', $level, '.css');
minify('zenario/libraries/mit/intro/', 'intro', $level, '.js');

//Minify Respond
minify('zenario/libraries/mit/respond/', 'respond', $level, '.js');

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
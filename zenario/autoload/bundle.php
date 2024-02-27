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


namespace ze;

class bundle {


	//Define a function to include a CSS file
	public static function includeCSSFile($linkV, $path, $file, $pathURL = false) {
		if (!$pathURL) {
			$pathURL = $path;
		}
		
		//Check if there's a stylesheet there
		if (is_file(CMS_ROOT. $path. $file)) {
		
			if ($linkV !== false) {
				echo '
<link rel="stylesheet" type="text/css" href="', htmlspecialchars($pathURL. $file), '?'. $linkV. '"/>';
		
			} else { 
				//Include the contents of the file, being careful to correct for the fact that the relative path for images will be wrong
				if (substr($path, 0, 8) == 'zenario/') {
					echo preg_replace('/url\(([\'\"]?)([^:]+?)\)/i', 'url($1../'. substr($pathURL, 7). '$2)', file_get_contents(CMS_ROOT. $path. $file));
		
				} else {
					echo preg_replace('/url\(([\'\"]?)([^:]+?)\)/i', 'url($1../../'. $pathURL. '$2)', file_get_contents(CMS_ROOT. $path. $file));
				}
		
				echo "\n/**/\n";
			}
		
			return true;
		}
	
		return false;
	}


	//This function can be used to output all of the files in a skin.
	//It can run in one of three different modes:
		//1. Directly outputting the CSS needed, i.e. for a bundle.
		//2. Outputting links to each individual CSS file needed, i.e. for a HTML page.
		//3. Combining and minifying all of the CSS files, i.e. to create a .min.css file.
	public static function includeSkinFiles($skinId, $linkV = false, $overrideCSS = false, $minify = false, $minifyPath = null) {
	
		//Look up the skin from the database
		if (!$skinId
		 || (!$skin = \ze\content::skinDetails($skinId))) {
			return;
		}
		
		//If we're going to be minifying, get a new instance of the minifier
		$minifier = null;
		if ($minify) {
			$minifier = new \MatthiasMullie\Minify\CSS();
		}
		
		
		//Cope with skins that extend other skins by including the extended skins as well in the download
		$skins = [$skin['name']];
	
		$limit = 10;
		do {
			$addedSkin = false;
		
			if (!empty($skin['extension_of_skin'])
			 && ($skin = \ze\content::skinName(false, $skin['extension_of_skin']))
			 && (!in_array($skin['name'], $skins))) {
				array_unshift($skins, $skin['name']);
				$addedSkin = true;
			}
		} while (--$limit && $addedSkin);
		
		
		//Get an array of which modules are currently running
		$runningModules = array_flip(\ze\row::getValues('modules', 'class_name', ['is_pluggable' => 1, 'status' => ['module_running', 'module_is_abstract']]));
	
	
		foreach ($skins as $skinName) {
			$skinPath = \ze\content::skinPath($skinName);
			$skinPathURL = \ze\content::skinURL($skinName);
		
			if (!is_dir(CMS_ROOT. $skinPath)) {
				echo "\n\n". \ze\admin::phrase('This page cannot be displayed, skin not found: '). 'skins/'. $skinName;
				echo "\n\n". \ze\admin::phrase('<br /><br />If you have recently upgraded from Zenario 8, you will need to move the location of the skins folder(s) from underneath zenario_custom/templates/grid_templates/skins/ to underneath zenario_custom/skins/.');
				echo '<br /><br /><a href="organizer.php">Go to Organizer</a>';
				exit;
			}
		
		
			$files = [[], [], [], [], []];
			
			//Add the default styles
			$files[1][] = ['zenario/styles/', 'visitor.min.css', 'zenario/styles/', false];
			$files[4][] = ['zenario/styles/', 'visitor_print.min.css', 'zenario/styles/', false];
		
			if ($skin['import']) {
				foreach (explode("\n", $skin['import']) as $import) {
					if ($import = trim($import)) {
						if (is_file(CMS_ROOT. $import)) {
							$files[1][] = [dirname($import). '/', basename($import), dirname($import). '/', false];
						}
					}
				}
			}
		
			\ze\bundle::includeSkinFilesR($files, $skinPath, $skinPathURL);
		
			foreach ($files as $fi => &$fa) {
				$max = count($fa) - 1;
				for ($i = 0; $i <= $max; ++$i) {
					$fb = &$fa[$i];
				
					$file = $fb[1];
					$isEditableFile = $fb[3];
					
					//Watch out for CSS files that are for plugins.
					//These will be in one of two formats:
						//2.name.css for every plugin of a module
						//2.name_123.css for a specific plugin
					if ($isEditableFile) {
						$nameparts = explode('.', $file);
						if (isset($nameparts[2])
						 && $nameparts[0] == '2') {
							
							//For each plugin-related CSS file, check to see if its module is in the
							//list of running modules.
							//First, check the "2.name.css" format.
							$moduleName = $nameparts[1];
							if (!isset($runningModules[$moduleName])) {
								
								//If that didn't match, maybe the file was using the "2.name_123.css" or
								//"2.name_123_456.css" formats instead?
								//Try chopping some parts off a couple of times.
								$explodedName = explode('_', $moduleName);
								
								array_pop($explodedName);
								if (!isset($runningModules[implode('_', $explodedName)])) {
									
									array_pop($explodedName);
									if (!isset($runningModules[implode('_', $explodedName)])) {
										continue;
									}
								}
							}
						}
					}
				
					//Look for overridden CSS files
					if ($linkV !== false
					 && $overrideCSS !== false
					 && $isEditableFile) {
					
						//Catch the case where an overwritten file already exists in the filesystem.
						//Output the overwritten version of the file, and don't output the file from the filesystem.
						if (isset($overrideCSS[0]) && $overrideCSS[0][0] == $file) {
							\ze\bundle::overwriteCSSFile($overrideCSS[0], $skinPath. 'editable_css/', $skinPathURL. 'editable_css/');
							array_shift($overrideCSS);
							continue;
					
						//Catch the case where the file didn't exist.
						//As soon as we see we've gone past it, output it and keep going.
						} else {
							while (isset($overrideCSS[0]) && $overrideCSS[0][0] < $file) {
								\ze\bundle::overwriteCSSFile($overrideCSS[0], $skinPath. 'editable_css/', $skinPathURL. 'editable_css/');
								array_shift($overrideCSS);
							}
						}
					}
				
					if ($minify) {
			 			$minifier->add(CMS_ROOT. $fb[0]. '/'. $fb[1]);
					} else {
						\ze\bundle::includeCSSFile($linkV, $fb[0], $fb[1], $fb[2]);
					}
				
				
					//Catch the case where there are files that didn't exist,
					//and they're at the end of the list so wouldn't have been caught above
					if ($linkV !== false
					 && $overrideCSS !== false
					 && $isEditableFile
					 && $i == $max) {
					
						//Fiddly bit of logic here:
							//reset.css will be included in the first pass (0)
							//all of the other editable css files will be in the third pass (2)
							//This xor ensures that 
						while (isset($overrideCSS[0]) && ($fi !== 0 xor $overrideCSS[0][0] === '0.reset.css')) {
							\ze\bundle::overwriteCSSFile($overrideCSS[0], $skinPath. 'editable_css/', $skinPathURL. 'editable_css/');
							array_shift($overrideCSS);
						}
					}
				}
			}
		}
		

		if ($minify) {
			return $minifier->minify($minifyPath);
		}
	}

	public static function overwriteCSSFile(&$override, $path, $pathURL) {
		echo
			"\n", '<style type="text/css">',
			"\n",
				preg_replace('/url\(([\'\"]?)([^:]+?)\)/i', 'url($1'. $pathURL. '$2)',
					str_ireplace('</style', '<', $override[1])
				),
			"\n", '</style>';

	}

	//Note there's an order to which the CSS files are included:
		//0 = reset.css
		//1 = non-editable CSS files, included alphabetically by filepath
		//2 = editable CSS files, included alphabetically by filename
		//3 = browser-specific CSS files
		//4 = print-specific CSS files

	public static function includeSkinFilesR(&$files, $skinPath, $skinPathURL, $topLevel = true, $inEditableDir = false, $limit = 10) {
		if (!--$limit) {
			return;
		}
	
		foreach(scandir(CMS_ROOT. $skinPath) as $file) {
		
		
			if ($file[0] != '.') {
				if (is_dir($skinPath. $file)) {
					if ($file != 'adminstyles') {
						\ze\bundle::includeSkinFilesR(
							$files, $skinPath. $file. '/', $skinPathURL. rawurlencode($file). '/',
							false, $topLevel && $file === 'editable_css', $limit);
					}
			
				} elseif (substr($file, -4) == '.css') {
				
					//Allow for files such as 0.reset.css or 7.style_ie.css to use the same logic as files with the regular names
					if ($file[1] === '.'
					 && strlen($file) > 6) {
						$name = substr($file, 2);
					} else {
						$name = $file;
					}
				
					//Check for files for specific uses
					switch ($name) {
						//reset.css should always be first (0)
						case 'reset.css':
							$files[0][] = [$skinPath, $file, $skinPathURL, $inEditableDir];
							break;
						
						//Include print-specific rules last (4)
						case 'print.css':
						case 'stylesheet_print.css':
							$files[4][] = [$skinPath, $file, $skinPathURL, $inEditableDir];
							break;
					
						//Browser-specific stylesheets should be next-to last (3)
						//As of 9.5 they are always included regardless of browser, and we
						//expect you to manually prefix all of the rules in them with a
						//browser-specific prefix (e.g. body.ff, body.webkit, etc...)
						case 'style_chrome.css':
						case 'style_edge.css':
						case 'style_ff.css':
						case 'style_ie.css':
						case 'style_ie6.css':
						case 'style_ie7.css':
						case 'style_ie8.css':
						case 'style_ie9.css':
						case 'style_ie10.css':
						case 'style_ie11.css':
						case 'style_ios.css':
						case 'style_ipad.css':
						case 'style_iphone.css':
						case 'style_opera.css':
						case 'style_safari.css':
						case 'style_webkit.css':
							$files[3][] = [$skinPath, $file, $skinPathURL, $inEditableDir];
							break;
					
						//Non-editable CSS files should be second (1),
						//then editable CSS files should be third (2)
						default:
							$files[$inEditableDir? 2 : 1][] = [$skinPath, $file, $skinPathURL, $inEditableDir];
					}
				}
			}
		}

	}
	
	
	


	public static function incCSS($file) {
		$file = CMS_ROOT. $file;
		if (file_exists($file. '.min.css')) {
			require $file. '.min.css';
		} elseif (file_exists($file. '.css')) {
			require $file. '.css';
		}
	
		echo "\n/**/\n";
	}

	public static function incJS($file, $wrapWrappers = false, $returnString = false) {
		
		$file = CMS_ROOT. $file;
		if ($wrapWrappers && file_exists($file. '.js.php')) {
			
			if ($returnString) {
				ob_start();
			}
			
			chdir(dirname($file));
			require $file. '.js.php';
			chdir(CMS_ROOT);
			
			if ($returnString) {
				return ob_get_clean();
			}
	
		} elseif (file_exists($file. '.pack.js')) {
			$filepath = $file. '.pack.js';
		} elseif (file_exists($file. '.min.js')) {
			$filepath = $file. '.min.js';
		} elseif (file_exists($file. '.js')) {
			$filepath = $file. '.js';
		} else {
			return '';
		}
		
		if ($returnString) {
			return file_get_contents($filepath). "\n";
		} else {
			require $filepath;
			echo "\n";
		}
		
	}
	
	public static function outputMicrotemplates($microtemplateDirs, $targetVar) {
		$output = '';
		foreach ($microtemplateDirs as $mDir) {
			foreach (scandir($dir = CMS_ROOT. $mDir) as $file) {
				if (substr($file, 0, 1) != '.' && substr($file, -5) == '.html' && is_file($dir. $file)) {
					$name = substr($file, 0, -5);
					$output .=
						\ze\cache::esctick($name). '~'.
						\ze\cache::esctick(trim(
							preg_replace('@\s+@', ' ', preg_replace('@%>\s*<%@', '', preg_replace('@<\!--.*?-->@s', '',
								file_get_contents($dir. $file)
						))))). '~';
				}
			}
		}
		return "\nzenario._mkd(". $targetVar. ','. json_encode($output). ');';
	}
	
	
	
	
	
	
	
	
	//
	//	A new experimental way of doing bundles in Zenario 9.6.
	//	Rather than linking to .bundle.js.php files in the zenario/js/ directory, which isn't
	//	very SEO-friendly, we'll try to create static files in the public/ directory whenever needed.
	//
	
	private static function startWritingBundle(&$f, &$exists, &$writable, &$path, $dir, $type, $filename) {
		
		//Look to see if the bundle is already written into the public/ directory
		$path = $type. '/'. $dir. '/'. $filename;
		$fullPath = CMS_ROOT. $path;
		
		//If the bundle already exists, and the code version matches, just use the existing copy without regenerating it.
		if (file_exists($fullPath)) {
			$exists = true;
			$writable = false;
		
		//If the the bundle does not exist, or the code version is out of date, we'll need to generate a new one.
		} elseif ($path = \ze\cache::createDir($dir, $type, false)) {
			\ze\cache::tidyDir($dir, $type);
			
			$path .= $filename;
			$fullpath = CMS_ROOT. $path;
			
			touch($fullpath);
			\ze\cache::chmod($fullpath);
			$f = fopen($fullpath, 'w');
			
			$exists = false;
			$writable = true;
		
		//Catch the case where the disk is not writable!
		} else {
			$exists = false;
			$writable = false;
		}
	}
	private static function writeCodeToBundle(&$f, $fallbackMode, $code) {
		
		//Generate the bundle by adding files to it, one by one.
		if (!$fallbackMode) {
			fwrite($f, $code);
		
		//However if the disk is not writable, we'll need to output the files in a .bundle.js.php file
		//(i.e. the old way of doing things) as a fallback!
		} else {
			echo $code;
		}
	}
	private static function writeFileToBundle(&$f, $fallbackMode, $lib) {
		\ze\bundle::writeCodeToBundle($f, $fallbackMode, file_get_contents(CMS_ROOT. $lib));
	}
	private static function stopWritingBundle(&$f, $fallbackMode) {
		if (!$fallbackMode) {
			fclose($f);
		}
	}
	
	
	//Attempt to create and link to the visitor JS bundle
	public static function visitorJS($fallbackMode, $codeVersion) {
		$f = $writable = $path = $exists = null;
		
		if (!$fallbackMode) {
		
			if (\ze::setting('lib.colorbox')) {
				$codeVersion .= '.cb';
			}
			if (\ze::setting('lib.doubletaptogo')) {
				$codeVersion .= '.dt';
			}
			if (\ze::setting('lib.modernizr')) {
				$codeVersion .= '.m';
			}
			
			$type = 'public/js';
			$dir = 'visitor';
			$filename = $codeVersion. '.min.js';
		
			\ze\bundle::startWritingBundle($f, $exists, $writable, $path, $dir, $type, $filename);
			
			if ($exists) {
				return $path;
			
			//If the disk is not writable, we'll need to link to the old bundle file as a fallback
			} else if (!$writable) {
				return 'zenario/js/visitor.bundle.js.php?v='. $codeVersion;
			}
		}
		
	
		\ze\bundle::writeFileToBundle($f, $fallbackMode, 'zenario/js/base_definitions.min.js');
		
		
		//Include Modernizr site-wide in the bundle if requested.
		//If not requested, it won't be included at all.
		if (\ze::setting('lib.modernizr')) {
			\ze\bundle::writeFileToBundle($f, $fallbackMode, 'zenario/libs/manually_maintained/mit/modernizr/modernizr.min.js');
		}
	
		//Include the underscore utility library (mandatory, as it's used in the core library)
		\ze\bundle::writeFileToBundle($f, $fallbackMode, 'zenario/libs/manually_maintained/mit/underscore/underscore.min.js');
	
		//Include all of the standard JavaScript libraries for the CMS
		\ze\bundle::writeFileToBundle($f, $fallbackMode, 'zenario/js/visitor.min.js');
		\ze\bundle::writeFileToBundle($f, $fallbackMode, 'zenario/reference/plugin_base_class.min.js');
	
		//Include our easing options for jQuery animations
		\ze\bundle::writeFileToBundle($f, $fallbackMode, 'zenario/js/easing.min.js');
	
		//Include Lazy Load library
		\ze\bundle::writeFileToBundle($f, $fallbackMode, 'zenario/libs/yarn/jquery-lazy/jquery.lazy.min.js');
	
		//Add a small checksum library. We have a couple of core functions that need to use checksums,
		//and believe it or not JavaScript doesn't have a checksum-generating function built in!
		\ze\bundle::writeFileToBundle($f, $fallbackMode, 'zenario/libs/yarn/js-crc/src/crc.min.js');
	
		//Include doubletaptogo site-wide in the bundle if requested.
		//If not requested, it will be included separately, and only on pages where a plugin claims to need it.
		if (\ze::setting('lib.doubletaptogo')) {
			\ze\bundle::writeFileToBundle($f, $fallbackMode, 'zenario/libs/yarn/jquery-doubletaptogo/dist/jquery.dcd.doubletaptogo.min.js');
		}
	
		//Include colorbox site-wide in the bundle if requested.
		//If not requested, it will be included separately, and only on pages where a plugin claims to need it.
		if (\ze::setting('lib.colorbox')) {
			\ze\bundle::writeFileToBundle($f, $fallbackMode, 'zenario/libs/manually_maintained/mit/colorbox/jquery.colorbox.min.js');
		}
	
		//Some misc fixes to run when the page has finished loading
		\ze\bundle::writeFileToBundle($f, $fallbackMode, 'zenario/js/visitor.ready.min.js');
		
		\ze\bundle::stopWritingBundle($f, $fallbackMode);
		
		
		if (!$fallbackMode) {
			return $path;
		}
		
	}
	
	
	public static function pluginJS($fallbackMode, $codeVersion, $forAdmin = false) {
		$f = $writable = $path = $exists = null;
		
		if (!$fallbackMode) {
			$type = 'public/js';
			$dir = 'plugin';
			$filename = $codeVersion. '.min.js';
		
			\ze\bundle::startWritingBundle($f, $exists, $writable, $path, $dir, $type, $filename);
			
			if ($exists) {
				return $path;
			
			//If the disk is not writable, we'll need to link to the old bundle file as a fallback
			} else if (!$writable) {
				return 'zenario/js/plugin.bundle.js.php?v='. $codeVersion;
			}
		}
		
		$flagJsAsNotLoaded = $forAdmin;
		
		
		$sql = "
			SELECT id AS module_id, class_name, vlp_class
			FROM ". DB_PREFIX. "modules
			WHERE `status` IN ('module_running', 'module_is_abstract')";
		
		if (!$forAdmin) {
			$sql .= "
			  AND is_pluggable = 1";
		}
		
		$moduleDetails = \ze\sql::fetchAssocs($sql, $indexBy = 'class_name');

		//Add Plugin inheritances as well
		foreach (array_keys($moduleDetails) as $moduleClassName) {
			foreach (\ze\module::inheritances($moduleClassName, 'include_javascript', false) as $inheritanceClassName) {
		
				//Bugfix - if we see one of the modules that we were already going to load is a dependant, move it up in the order.
				if (isset($moduleDetails[$inheritanceClassName])) {
					$mDetails = $moduleDetails[$inheritanceClassName];
					unset($moduleDetails[$inheritanceClassName]);
					$moduleDetails[$inheritanceClassName] = $mDetails;
				}
			}
		}

		//Try to put the modules in dependency order
		$moduleDetails = array_reverse($moduleDetails, true);


		//For FEA plugins, we need to load a list of their paths & which type of FEA logic is used for each path.
		//(This used to be hand-written in each plugin's JS file, but now this list is calculated automatically.)
		$sql = '
			SELECT module_class_name, path, panel_type
			FROM '. DB_PREFIX. 'tuix_file_contents
			WHERE `type` = \'visitor\'
			  AND module_class_name IN ('. \ze\escape::in(array_keys($moduleDetails), 'asciiInSQL'). ')';

		$result = \ze\sql::select($sql);
		while ($fea = \ze\sql::fetchRow($result)) {
			$className = $fea[0];
			$feaPath = $fea[1];
			$feaType = $fea[2];
	
			if (!isset($moduleDetails[$className]['feaPaths'])) {
				$moduleDetails[$className]['feaPaths'] = [];
			}
			$moduleDetails[$className]['feaPaths'][$feaPath] = $feaType;
		}


		//Add JavaScript support elements for each Plugin on the page
		
		$includeMicrotemplates = [];
		if (!empty($moduleDetails)) {
	
			//Create a namespace for each Plugin used on this page
			$code = '(function(c){';
	
			foreach ($moduleDetails as $module) {
				$code .= "\n". 'c('. (int) $module['module_id']. ', '. json_encode($module['class_name']). ', ';
	
				if ($module['class_name'] == $module['vlp_class']) {
					$code .= '1';
				} else {
					$code .= json_encode($module['vlp_class']);
				}
		
				if (isset($module['feaPaths'])) {
					$code .= ', '. json_encode($module['feaPaths']);
				}
		
				if ($flagJsAsNotLoaded) {
					if (!isset($module['feaPaths'])) {
						$code .= ', undefined';
					}
					$code .= ', 1';
				}
		
				$code .= ');';
			}
			$code .= "\n". '})(zenario.enc);';
			
			\ze\bundle::writeCodeToBundle($f, $fallbackMode, $code);
			

			foreach ($moduleDetails as $module) {
				if ($jsDir = \ze::moduleDir($module['class_name'], 'js/')) {
					if ($forAdmin) {
						//Add the any JavaScript needed for the admin front-end and Organizer
						$code = \ze\bundle::incJS($jsDir. 'admin', true, true);
						$code .= \ze\bundle::incJS($jsDir. 'organizer', true, true);
	
					} else {
						//Add the Plugin's library include if it exists
						$code = \ze\bundle::incJS($jsDir. 'plugin', true, true);
				
						if ($jsDir = \ze::moduleDir($module['class_name'], 'microtemplates/', true)) {
							$includeMicrotemplates[] = $jsDir;
						}
					}
					
					//Some debug code what will print the size of each module's include.
					//$code .= "\n/*The plugin JavaScript for ". $module['class_name']. " was ". strlen($code). " in size. */\n";
			
					\ze\bundle::writeCodeToBundle($f, $fallbackMode, $code);
				}
			}
	
			if (!empty($includeMicrotemplates)) {
				$code = \ze\bundle::outputMicrotemplates($includeMicrotemplates, 'zenario.microTemplates');
				\ze\bundle::writeCodeToBundle($f, $fallbackMode, $code);
			}
		}

		if ($forAdmin) {
			//Get a list of Module names for use in the formatting options
			$moduleInfo =
				\ze\sql::fetchAssocs("
					SELECT id, class_name, display_name, status IN ('module_running', 'module_is_abstract') AS running
					FROM ". DB_PREFIX. "modules");
	
			foreach ($moduleInfo as &$info) {
				$info['id'] = (int) $info['id'];
				$info['running'] = (bool) $info['running'];
			}
	
			$code = "\n". 'zenarioA.setModuleInfo('. json_encode($moduleInfo). ');';
			\ze\bundle::writeCodeToBundle($f, $fallbackMode, $code);
		}

		\ze\bundle::stopWritingBundle($f, $fallbackMode);
		
		
		if (!$fallbackMode) {
			return $path;
		}
	}


}

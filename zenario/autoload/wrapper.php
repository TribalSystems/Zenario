<?php
/*
 * Copyright (c) 2018, Tribal Limited
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

class wrapper {


	//Define a function to include a CSS file
	//Formerly "includeCSSFile()"
	public static function includeCSSFile(&$linkV, &$overrideCSS, $path, $file, $pathURL = false, $media = 'screen') {
		if (!$pathURL) {
			$pathURL = $path;
		}
	
		//Check if there's a stylesheet there
		if (is_file(CMS_ROOT. $path. $file)) {
		
			if ($linkV !== false) {
				echo '
<link rel="stylesheet" type="text/css" media="', $media, '" href="', htmlspecialchars($pathURL. $file), '?v='. $linkV. '"/>';
		
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


	//Formerly "includeSkinFiles()"
	public static function includeSkinFiles(&$req, $linkV = false, $overrideCSS = false) {
	
		$media = empty($req['print'])? 'screen' : 'print';
	
		//If a layout has been specified, and it has a grid CSS, output that grid CSS
		if (empty($req['print'])
		 && !empty($req['layoutId'])
		 && ($layout = \ze\content::layoutDetails($req['layoutId']))
		 && ($path = \ze\content::templatePath($layout['family_name']))
		 && (file_exists($file = (CMS_ROOT. \ze\content::templatePath($layout['family_name'], $layout['file_base_name'], true))))) {
			\ze\wrapper::includeCSSFile($linkV, $overrideCSS, $path, basename($file));
		}
	
	
		//Look up the skin from the database
		if (!$skin = \ze\content::skinDetails($req['id'])) {
			return;
		}
	
		$templateFamily = $skin['family_name'];
		$skins = [$skin['name']];
	
		$limit = 10;
		do {
			$addedSkin = false;
		
			if (!empty($skin['extension_of_skin'])
			 && ($skin = \ze\content::skinName($skin['family_name'], $skin['extension_of_skin']))
			 && (!in_array($skin['name'], $skins))) {
				array_unshift($skins, $skin['name']);
				$addedSkin = true;
			}
		} while (--$limit && $addedSkin);
	
	
		foreach ($skins as $skinName) {
			$skinPath = \ze\content::skinPath($templateFamily, $skinName);
			$skinPathURL = \ze\content::skinURL($templateFamily, $skinName);
		
			if (!is_dir(CMS_ROOT. $skinPath)) {
				echo "\n\n". \ze\admin::phrase('This page cannot be displayed, skin not found: '). $templateFamily. '/skins/'. $skinName;
				echo '<br /><br /><a href="zenario/admin/organizer.php">Go to Organizer</a>';
				exit;
			}
		
		
			$files = [[], [], [], []];
			$browsers = explode(' ', \ze\cache::browserBodyClass());
			foreach ($browsers as &$browser) {
				$browser = 'style_'. $browser. '.css';
			}
			$browsers = array_flip($browsers);
			
			//Add the default styles
			$files[1][] = ['zenario/styles/', 'visitor.min.css', 'zenario/styles/', false];
		
			if ($skin['import']) {
				foreach (explode("\n", $skin['import']) as $import) {
					if ($import = trim($import)) {
						if (is_file(CMS_ROOT. $import)) {
							$files[1][] = [dirname($import). '/', basename($import), dirname($import). '/', false];
						}
					}
				}
			}
		
			\ze\wrapper::includeSkinFilesR($req, $browsers, $files, $skinPath, $skinPathURL);
		
			foreach ($files as $fi => &$fa) {
				$max = count($fa) - 1;
				for ($i = 0; $i <= $max; ++$i) {
					$fb = &$fa[$i];
				
					$file = $fb[1];
					$isEditableFile = $fb[3];
				
					//Look for overridden CSS files
					if ($linkV !== false
					 && $overrideCSS !== false
					 && $isEditableFile) {
					
						//Catch the case where an overwritten file already exists in the filesystem.
						//Output the overwritten version of the file, and don't output the file from the filesystem.
						if (isset($overrideCSS[0]) && $overrideCSS[0][0] == $file) {
							\ze\wrapper::overwriteCSSFile($overrideCSS[0], $skinPath. 'editable_css/', $skinPathURL. 'editable_css/', $media);
							array_shift($overrideCSS);
							continue;
					
						//Catch the case where the file didn't exist.
						//As soon as we see we've gone past it, output it and keep going.
						} else {
							while (isset($overrideCSS[0]) && $overrideCSS[0][0] < $file) {
								\ze\wrapper::overwriteCSSFile($overrideCSS[0], $skinPath. 'editable_css/', $skinPathURL. 'editable_css/', $media);
								array_shift($overrideCSS);
							}
						}
					}
				
				
					\ze\wrapper::includeCSSFile($linkV, $overrideCSS, $fb[0], $fb[1], $fb[2], $media);
				
				
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
							\ze\wrapper::overwriteCSSFile($overrideCSS[0], $skinPath. 'editable_css/', $skinPathURL. 'editable_css/', $media);
							array_shift($overrideCSS);
						}
					}
				}
			}
		}
	}

	//Formerly "overwriteCSSFile()"
	public static function overwriteCSSFile(&$override, $path, $pathURL, $media) {
		echo
			"\n", '<style type="text/css" media="', $media, '">',
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

	//Formerly "includeSkinFilesR()"
	public static function includeSkinFilesR(&$req, &$browsers, &$files, $skinPath, $skinPathURL, $topLevel = true, $inEditableDir = false, $limit = 10) {
		if (!--$limit) {
			return;
		}
	
		foreach(scandir(CMS_ROOT. $skinPath) as $file) {
		
		
			if ($file[0] != '.') {
				if (is_dir($skinPath. $file)) {
					\ze\wrapper::includeSkinFilesR(
						$req, $specialFiles, $files, $skinPath. $file. '/', $skinPathURL. rawurlencode($file). '/',
						false, $topLevel && $file === 'editable_css', $limit);
			
				} elseif (substr($file, -4) == '.css') {
				
					//Allow for files such as 0.reset.css or 7.style_ie.css to use the same logic as files with the regular names
					if ($file[1] === '.'
					 && strlen($file) > 6) {
						$name = substr($file, 2);
					} else {
						$name = $file;
					}
				
					//Check for files for specific uses
					if (!empty($req['editor'])) {
						switch ($name) {
							case 'tinymce.css':
								$files[0][] = [$skinPath, $file, $skinPathURL, $inEditableDir];
						}
				
					} elseif (!empty($req['print'])) {
						switch ($name) {
							case 'print.css':
							case 'stylesheet_print.css':
								$files[0][] = [$skinPath, $file, $skinPathURL, $inEditableDir];
						}
				
					} else {
						switch ($name) {
							case 'tinymce.css':
							case 'print.css':
							case 'stylesheet_print.css':
								break;
						
							//reset.css should always be first (0)
							case 'reset.css':
								$files[0][] = [$skinPath, $file, $skinPathURL, $inEditableDir];
								break;
						
							//browser-specific stylesheets are alwats last (3),
							//and should only be included if they match the browser requesting them
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
								if (isset($browsers[$name])) {
									$files[3][] = [$skinPath, $file, $skinPathURL, $inEditableDir];
								}
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

	}


}
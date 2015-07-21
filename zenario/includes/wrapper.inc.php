<?php

/*
 * Copyright (c) 2015, Tribal Limited
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


//Define a function to include a CSS file
if (!function_exists('includeCSSFile')) {
	function includeCSSFile($path, $file, $pathURL = false, $media = 'screen') {
		if (!$pathURL) {
			$pathURL = $path;
		}
		
		//Check if there's a stylesheet there
		if (is_file(CMS_ROOT. $path. $file)) {
			//Include the contents of the file, being careful to correct for the fact that the relative path for images will be wrong
			if (substr($path, 0, 8) == 'zenario/') {
				echo preg_replace('/url\(([\'\"]?)([^:]+?)\)/i', 'url($1../'. substr($pathURL, 7). '$2)', file_get_contents(CMS_ROOT. $path. $file));
			
			} else {
				echo preg_replace('/url\(([\'\"]?)([^:]+?)\)/i', 'url($1../../'. $pathURL. '$2)', file_get_contents(CMS_ROOT. $path. $file));
			}
			
			echo "\n/**/\n";
			
			return true;
		}
		
		return false;
	}
}


function outputRulesForSlotMinHeights() {
	echo '
.container .medium_slot .zenario_slot {
	min-height: 150px;
}

.container .large_slot .zenario_slot {
	min-height: 225px;
}

.container .xlarge_slot .zenario_slot {
	min-height: 300px;
}

.container .xxlarge_slot .zenario_slot {
	min-height: 375px;
}';
}



function includeSkinFiles(&$req) {
	
	//If a layout has been specified, and it has a grid CSS, output that grid CSS
	if (empty($req['print'])
	 && !empty($req['layoutId'])
	 && ($layout = getTemplateDetails($req['layoutId']))
	 && ($path = zenarioTemplatePath($layout['family_name']))
	 && (file_exists($file = (CMS_ROOT. zenarioTemplatePath($layout['family_name'], $layout['file_base_name'], true))))) {
		includeCSSFile($path, basename($file));
	}
	
	
	//Look up the skin from the database
	if (!$skin = getSkinFromId($req['id'])) {
		return;
	}
	
	$templateFamily = $skin['family_name'];
	$skins = array($skin['name']);
	
	$limit = 10;
	do {
		$addedSkin = false;
		
		if (!empty($skin['extension_of_skin'])
		 && ($skin = getSkinFromName($skin['family_name'], $skin['extension_of_skin']))
		 && (!in_array($skin['name'], $skins))) {
			array_unshift($skins, $skin['name']);
			$addedSkin = true;
		}
	} while (--$limit && $addedSkin);
	
	
	foreach ($skins as $skinName) {
		$skinPath = getSkinPath($templateFamily, $skinName);
		$skinPathURL = getSkinPathURL($templateFamily, $skinName);
		
		if (!is_dir(CMS_ROOT. $skinPath)) {
			echo "\n\n". adminPhrase('This page cannot be displayed, skin not found: '). $templateFamily. '/skins/'. $skinName;
			echo '<br /><br /><a href="zenario/admin/organizer.php">Go to Organizer</a>';
			exit;
		}
		
		
		$files = array(array(), array(), array());
		$browsers = explode(' ', browserBodyClass());
		foreach ($browsers as &$browser) {
			$browser = 'style_'. $browser. '.css';
		}
		$browsers = array_flip($browsers);
		
		includeSkinFilesR($req, $browsers, $files, $skinPath, $skinPathURL);
		
		foreach ($files as &$fa) {
			foreach ($fa as &$fb) {
				includeCSSFile($fb[0], $fb[1], $fb[2], empty($req['print'])? 'screen' : 'print');
			}
		}
	}
}

function includeSkinFilesR(&$req, &$browsers, &$files, $skinPath, $skinPathURL, $limit = 10) {
	if (!--$limit) {
		return;
	}
	
	foreach(scandir(CMS_ROOT. $skinPath) as $file) {
		if (substr($file, 0, 1) != '.') {
			if (is_dir($skinPath. $file)) {
				includeSkinFilesR($req, $specialFiles, $files, $skinPath. $file. '/', $skinPathURL. rawurlencode($file). '/', $limit);
			
			} else if (substr($file, -4) == '.css') {
				if (!empty($req['editor'])) {
					switch ($file) {
						case 'tinymce.css':
						case 'fckeditor.css':
							$files[0][] = array($skinPath, $file, $skinPathURL);
					}
				
				} elseif (!empty($req['print'])) {
					switch ($file) {
						case 'stylesheet_print.css':
							$files[0][] = array($skinPath, $file, $skinPathURL);
					}
				
				} else {
					switch ($file) {
						case 'tinymce.css':
						case 'fckeditor.css':
						case 'stylesheet_print.css':
							break;
						
						case 'reset.css':
							$files[0][] = array($skinPath, $file, $skinPathURL);
							break;
						
						case 'style_chrome.css':
						case 'style_ff.css':
						case 'style_ie.css':
						case 'style_ie6.css':
						case 'style_ie7.css':
						case 'style_ie8.css':
						case 'style_ie9.css':
						case 'style_ios.css':
						case 'style_ipad.css':
						case 'style_iphone.css':
						case 'style_opera.css':
						case 'style_safari.css':
						case 'style_webkit.css':
							if (isset($browsers[$file])) {
								$files[2][] = array($skinPath, $file, $skinPathURL);
							}
							break;
						
						default:
							$files[1][] = array($skinPath, $file, $skinPathURL);
					}
				}
			}
		}
	}

}
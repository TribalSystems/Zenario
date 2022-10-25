<?php 
/*
 * Copyright (c) 2022, Tribal Limited
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

class skinAdm {
	
	//Formerly "skinDescriptionFilePath()"
	public static function descriptionFilePath($familyName, $name) {
		foreach ([
			'description.yaml',
			'description.yml',
			'description.xml',
			'skin.xml'
		] as $file) {
			if (file_exists(CMS_ROOT. (
				$path = 'zenario_custom/skins/'. $name. '/'. $file
			))) {
				return $path;
			}
		}
		return false;
	}

	//Load a Skin's description, looking at any Skins it extends if needed
	//Note: works more or less the same was as the \ze\moduleAdm::loadDescription() function
	//Formerly "loadSkinDescription()"
	public static function loadDescription($skin, &$tags) {
	
		$tags = [];
		if (!is_array($skin)) {
			$skin = \ze\content::skinDetails($skin);
		}
	
		if ($skin) {
			$limit = 20;
			$skinsWeHaveRead = [];
			$name = $skin['name'];
	
			while (--$limit
			 && empty($skinsWeHaveRead[$name])
			 && ($skinsWeHaveRead[$name] = true)
			 && ($path = \ze\skinAdm::descriptionFilePath(false, $name))) {
		
				if (!$tagsToParse = \ze\tuix::readFile(CMS_ROOT. $path)) {
					echo \ze\admin::phrase('[[path]] appears to be in the wrong format or invalid.', ['path' => CMS_ROOT. $path]);
					return false;
				} else {
					if (!empty($tagsToParse['extension_of_skin'])) {
						$name = $tagsToParse['extension_of_skin'];
					}
				
					\ze\tuix::parse($tags, $tagsToParse, 'skin_description');
					unset($tagsToParse);
				}
			}
		}
	
		if (!empty($tags['skin_description'])) {
			$tags = $tags['skin_description'];
		
			//Convert the old editor_styles format from 6.1.0 and earlier to the new style_formats format
			if (!empty($tags['editor_styles']) && is_array($tags['editor_styles'])) {
				if (empty($tags['style_formats'])) {
					$tags['style_formats'] = [];
				}
				foreach ($tags['editor_styles'] as $css_class_name => $displayName) {
					$tags['style_formats'][] = [
						'title' => $displayName,
						'selector' => '*',
						'classes' => $css_class_name
					];
				}
			}
			unset($tags['editor_styles']);
		
			//Don't show an empty format list!
			if (empty($tags['style_formats'])) {
				unset($tags['style_formats']);
			}
		
			//Convert the old pickable_css_class_names format to the new format
			if (!empty($tags['pickable_css_class_names']) && is_array($tags['pickable_css_class_names'])) {
				foreach ($tags['pickable_css_class_names'] as $tagName => $details) {
					$tagName = explode(' ', $tagName);
					$tagName = $tagName[0];
					if (\ze::in($tagName, 'content_item', 'layout', 'plugin', 'menu_node')) {
						if (empty($tags['pickable_css_class_names'][$tagName. 's'])) {
							$tags['pickable_css_class_names'][$tagName. 's'] = [];
						}
						$tags['pickable_css_class_names'][$tagName. 's'][] = $details;
						unset($tags['pickable_css_class_names'][$tagName]);
					}
				}
			}
		
			return $tags;
		} else {
			return [];
		}
	}


	//Attempt to load a list of CSS Class Names from the description.yaml in the current Skin to add choices for plugins
	//Formerly "getSkinCSSClassNames()"
	public static function cssClassNames($skin, $type, $moduleClassName = '') {
		$values = [];
	
		$desc = false;
		if (\ze\skinAdm::loadDescription($skin, $desc)) {
		
			if (!empty($desc['pickable_css_class_names'][$type. 's'])
			 && is_array($desc['pickable_css_class_names'][$type. 's'])) {
				foreach ($desc['pickable_css_class_names'][$type. 's'] as $swatch) {
					$module_css_class_name = $swatch['module_css_class_name'] ?? false;
					$css_class_name = $swatch['css_class_name'] ?? false;
					$label = $swatch['label'] ?? false;
				
					if ($type != 'plugin' || $moduleClassName == $module_css_class_name) {
						if ($css_class_name) {
							$values[$css_class_name] = $css_class_name;
							//$values[$css_class_name] =
							//	$css_class_name.
							//	($label? ' ('. (string) $label. ')' : '');
						}
					}
				}
			}
		}
	
		asort($values);
	
		return $values;
	}

	//Formerly "deleteSkinAndClearForeignKeys()"
	public static function delete($skinId) {
		\ze\row::update('layouts', ['skin_id' => 0], ['skin_id' => $skinId]);
		\ze\row::delete('skins', $skinId);
	}

	//Loop through the frameworks/page/tuix cache directories, trying to delete everything there
	public static function clearCacheDir() {
	
		foreach ([
			CMS_ROOT. 'cache/frameworks/',
			CMS_ROOT. 'cache/pages/',
			CMS_ROOT. 'cache/layouts/',
			CMS_ROOT. 'cache/tuix/'
		] as $cacheDir) {
			if (is_dir($cacheDir)) {
				\ze\cache::deleteDir($cacheDir, 1);
			}
		}
	}

	//If the Pro Features module is installed and page/plugin caching is enabled,
	//empty the page cache. (We do this by telling it that the site settings have changed.)
	public static function emptyPageCache() {
		$sql = '';
		$ids = $values = [];
		$table = 'site_settings';
		\ze::$dbL->reviewQueryForChanges($sql, $ids, $values, $table);
	}

	//This function clears as many cached/stored things as possible!
	//Formerly "zenarioClearCache()"
	public static function clearCache() {
	
		//Update the data-revision number in the database to clear anything stored in Organizer's local storage
		\ze\db::updateDataRevisionNumber();
		
		\ze\skinAdm::emptyPageCache();
		\ze\skinAdm::clearCacheDir();
	
		//Check for changes in TUIX, Layout and Skin files
		\ze\miscAdm::checkForChangesInYamlFiles($forceScan = true);
		\ze\skinAdm::checkForChangesInFiles($runInProductionMode = true, $forceScan = true);
	}

	//Include a checksum calculated from the modificaiton dates of any css/js/html files
	//Note that this is only calculated for admins, and cached for visitors
	//Formerly "checkForChangesInCssJsAndHtmlFiles()"
	public static function checkForChangesInFiles($runInProductionMode = false, $forceScan = false) {
		
		//Do not try to do anything if there is no database connection!
		if (!\ze::$dbL) {
			return false;
		}
	
	
		$time = time();
		$changed = false;
		$skinChanges = false;
		$zenario_version = \ze\site::versionNumber();
	
		//Catch the case where someone just updated to a different version of the CMS
		if ($zenario_version != \ze::setting('zenario_version')) {
			//Clear everything that was cached if this has happened
			\ze\site::setSetting('yaml_files_last_changed', '');
			\ze\site::setSetting('yaml_version', '');
			$changed = true;
			$skinChanges = is_dir(CMS_ROOT. 'zenario_custom/skins/');
	
		//Get the date of the last time we ran this check and there was a change.
		} elseif (!($lastChanged = (int) \ze::setting('css_js_html_files_last_changed'))) {
			//If this has never been run before then it must be run now!
			$changed = true;
			$skinChanges = is_dir(CMS_ROOT. 'zenario_custom/skins/');
	
		} elseif ($forceScan) {
			$changed = true;
			$skinChanges = is_dir(CMS_ROOT. 'zenario_custom/skins/');
	
		//Don't run this in production mode unless $forceScan or $runInProductionMode is set
		} elseif (!$runInProductionMode && !\ze\site::inDevMode()) {
			return false;
	
		//Otherwise, check if there have been any files changed since the last modification time
		} else {
		
			//These are the directories that we should look in.
			//N.b. it's important that we check the templates directory first;
			//there's some extra logic if a template file changes so we need to know if
			//a template file change is the change that's triggering this
			$dirs = [];
			foreach ([
				'zenario_custom/skins',
				'zenario/js',
				'zenario/reference',
				'zenario/styles',
				'zenario/modules',
				'zenario_custom/modules',
				'zenario_extra_modules'
			] as $dir) {
				if (is_dir(CMS_ROOT. $dir)) {
					$dirs[] = $dir;
				}
			}
		
			if (empty($dirs)) {
				return false;
			}
		
			//Check to see if there are any .css, .js, .html, .php or .yaml files that have changed on the system
			$useFallback = true;
			
			//Catch the case where a skin's directory has been completely deleted
			//(This wouldn't be picked up by the modification date below.)
			foreach (\ze\row::getAssocs('skins', ['name', 'missing'], []) as $skin) {
				if (((bool) $skin['missing']) != !((bool) is_dir(CMS_ROOT. 'zenario_custom/skins/'. $skin['name']))) {
					$changed = true;
					$skinChanges = true;
					$useFallback = false;
					break;
				}
			}
		
			if ($useFallback && defined('PHP_OS') && \ze\server::execEnabled()) {
				//Make sure we are in the CMS root directory.
				chdir(CMS_ROOT);
			
				try {
					//Look for any .css, .js, .html, .php or .yaml files that have been created or modified since this was last run.
					//(Unfortunately this won't catch the case where the files are deleted.)
					//We'll also look for any changes to *anything* in the zenario_custom/skins directory.
					//(This will catch the case where files are deleted, as the modification times of directories are included.)
					$find =
						' -not -path "*/.*"'.
						' \\( -name "*.css*" -o -name "*.js*" -o -name "*.html" -o -name "*.php" -o -name "*.yaml" \\)'.
						' -print'.
						' | sed 1q';
			
					$locations = '';
					foreach ($dirs as $dir) {
						$locations .= ' '. escapeshellarg($dir);
					}
			
					//If possble, try to use the UNIX shell
					if (PHP_OS == 'Linux') {
						//On Linux it's possible to set a timeout, so do so.
						$output = [];
						$status = false;
						$changed = exec('timeout 10 find -L'. $locations. ' -newermt @'. (int) $lastChanged. $find, $output, $status);
					
						//If the statement times out, then I will assume this was because the file system indexes were out of
						//date and the find statement took a long time.
						//If the indexes were out of date then it probably means that the code has just changed, so we'll handle
						//the time out by assuming that it indicates a change.
						if ($status == 124) {
							$changed = true;
						}
						$useFallback = false;
			
					} elseif (PHP_OS == 'Darwin') {
						$ago = $time - $lastChanged;
						$changed = exec('find -L'. $locations. ' -mtime -'. (int) $ago. 's'. $find);
						$useFallback = false;
					}
				
					$skinChanges = $changed && \ze\ring::chopPrefix('zenario_custom/skins/', $changed);
	
				} catch (\Exception $e) {
					$useFallback = true;
				}
			}
			
			//If we couldn't use the command line, we'll do the same logic using a RecursiveDirectoryIterator
			if ($useFallback) {
				foreach ($dirs as $dir) {
					$RecursiveDirectoryIterator = new \RecursiveDirectoryIterator(CMS_ROOT. $dir);
					$RecursiveIteratorIterator = new \RecursiveIteratorIterator($RecursiveDirectoryIterator);
			
					foreach ($RecursiveIteratorIterator as $file) {
						if ($file->isFile()
						 && $file->getMTime() > $lastChanged) {
							$changed = true;
							$skinChanges = $dir == 'zenario_custom/skins/';
							break 2;
						}
					}
				}
			}
		}
	
	
		if ($changed) {
			if ($skinChanges) {
				//Clear the page cache completely if a Skin or a Template Family has changed
				$sql = '';
				$ids = $values = [];
				\ze::$dbL->reviewQueryForChanges($sql, $ids, $values, $table = 'template_family');
		
		
				//Mark all current Template Families/Template Files/Skins as missing
				\ze\row::update('skins', ['missing' => 1], []);
		
				
				//Check that all of the template-families, files and skins in the filesystem are
				//registered in the database, and add any newly found files/directories.
				if (is_dir($skinsDir = CMS_ROOT. 'zenario_custom/skins/')) {
					foreach (scandir($skinsDir) as $skin) {
						if (substr($skin, 0, 1) != '.' && is_dir($skinsDir. $skin)) {
							$row = ['name' => $skin];
							$exists = \ze\row::exists('skins', $row);
					
							$details = ['missing' => 0];
					
							//Also update the Skin's description
							$desc = false;
							if (\ze\skinAdm::loadDescription($row, $desc)) {
								$details['display_name'] = (($desc['display_name'] ?? false) ?: $row['name']);
								$details['extension_of_skin'] = $desc['extension_of_skin'] ?? false;
								$details['css_class'] = $desc['css_class'] ?? false;
								$details['background_selector'] = (($desc['background_selector'] ?? false) ?: 'body');
								$details['enable_editable_css'] = !\ze\ring::engToBoolean($desc['disable_editable_css'] ?? false);
								$details['import'] = $desc['import'] ?? false;
							
								if (is_array($details['import'])) {
									$details['import'] = implode("\n", $details['import']);
								}
							}
							\ze\row::set('skins', $details, $row);
						}
					}
				}
		
				//Delete anything that is missing *and* not used
				foreach(\ze\row::getValues('skins', 'id', ['missing' => 1]) as $skinId) {
					if (!\ze\layoutAdm::skinInUse($skinId)) {
						\ze\skinAdm::delete($skinId);
					}
				}
				
			}
		
			\ze\site::setSetting('css_js_html_files_last_changed', $time);
			\ze\site::setSetting('css_js_version', base_convert($time, 10, 36));
			\ze\site::setSetting('zenario_version', $zenario_version);
		}
	
		return $changed;
	}
}
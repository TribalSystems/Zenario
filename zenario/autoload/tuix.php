<?php
/*
 * Copyright (c) 2021, Tribal Limited
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

class tuix {



	//Some functions for loading a YAML file
	//Formerly "tuixCacheDir()"
	public static function cacheDir($path) {
	
		$path = str_replace(['/tuix/', '/zenario/'], '/', \ze\ring::chopPrefix(CMS_ROOT, $path, true));
	
		$dir = str_replace('%', ' ', rawurlencode(dirname($path)));
		$file = explode('.', basename($path), 2);
		$file = $file[0];
	
		\ze\cache::cleanDirs();
		
		if ($cd = \ze\cache::createDir($dir, $type = 'tuix', $onlyForCurrentVisitor = true, $ip = false)) {
			return CMS_ROOT. $cd. $file. '.json';
		
		} else {
			return false;
		}
	}


	//Formerly "zenarioReadTUIXFile()"
	public static function readFile($path, $useCache = true, $updateCache = true) {
		$type = explode('.', $path);
		$type = $type[count($type) - 1];
		
		//Check to see if the file is actually there
		if (!file_exists($path)) {
			//T10201: Add a workaround to fix an occasional bug where the tuix_file_contents table is out of date
			//Try to catch the case where the file was deleted in the filesystem but
			//not from the tuix_file_contents table, and we've not noticed this yet
			if (\ze::$dbL) {
			
				//Look for bad rows from the table
				$sql = "
					DELETE FROM ". DB_PREFIX. "tuix_file_contents
					WHERE '". \ze\escape::sql($path). "' LIKE CONCAT('%modules/', module_class_name, '/tuix/', type, '/', filename)";
			
				//If we found any, delete them and flag that the cache table might be out of date
				if ($affectedRows = \ze\sql::update($sql, false, false)) {
					\ze\site::setSetting('yaml_files_last_changed', '');
				
					//Attempt to continue normally
					return [];
				}
			}
		}
		
		//Attempt to use a cached copy of this TUIX file
			//JSON is a lot faster to read than the other formats, so for speed purposes we create cached JSON copies of files
		$filemtime = false;
		$cachePath = false;
		if ($useCache || $updateCache) {
			$cachePath = \ze\tuix::cacheDir($path);
		}
		if ($useCache && $cachePath
		 && ($filemtime = filemtime($path))
		 && (file_exists($cachePath))
		 && (filemtime($cachePath) == $filemtime)
		 && ($tags = json_decode(file_get_contents($cachePath), true))) {
			return $tags;
		}
	
		switch ($type) {
			case 'xml':
				//If this is admin mode, allow an old xml file to be loaded and read as a yaml file
				$tags = [];
				if (function_exists('zenarioReadTUIXFileR')) {
					$xml = simplexml_load_file($path);
					\ze\tuix::readFileR($tags, $xml);
				}
			
				break;
			
			case 'yml':
			case 'yaml':
				$contents = file_get_contents($path);
			
				//If it was missing or unreadable, display an error and then exit.
				if ($contents === false) {
					echo 'Could not read file '. $path;
					exit;
			
				//Check for a byte order mark at the start of the file.
				//Also use PREG's parser to check that the file was UTF8
				} else
				if (pack('CCC', 0xef, 0xbb, 0xbf) === substr($contents, 0, 3)
				 || preg_match('/./u', $contents) === false) {
					echo $path. ' was not saved using UTF-8 encoding. You must change it to UFT-8, and you must not use a Byte Order Mark.';
					exit;
			
				} elseif (\ze\tuix::mixesTabsAndSpaces($contents)) {
					echo 'The YAML file '. $path. ' contains a mixture of tabs and spaces for indentation and cannot be read';
					exit;
				}
			
				try {
					$tags = \Spyc::YAMLLoad($path);
				} catch (\Exception $e) {
					echo 'Could not parse file '. $path, "\n", htmlspecialchars($e->getMessage());
					throw $e;
				}
				unset($contents);
			
				break;
			
			default:
				$tags = [];
		}
	
		if (!is_array($tags) || $tags === NULL) {
			echo 'Error in file '. $path;
			exit;
		}
	
		//Backwards compatability hack so that Modules created before we moved the
		//site settings don't immediately break!
		if (!empty($tags['zenario__administration']['nav']['configure_settings']['panel']['items']['settings']['panel'])
		 && empty($tags['zenario__administration']['panels']['site_settings'])) {
			$tags['zenario__administration']['panels']['site_settings'] =
				$tags['zenario__administration']['nav']['configure_settings']['panel']['items']['settings']['panel'];
			unset($tags['zenario__administration']['nav']['configure_settings']['panel']['items']['settings']['panel']);
		}
	
		//Save this array in the cache as a JSON file, for faster loading next time
		if ($updateCache && $cachePath) {
			@file_put_contents($cachePath, json_encode($tags));
			\ze\cache::chmod($cachePath, 0666);
		
			if ($filemtime) {
				@touch($cachePath, $filemtime);
			}
		}
	
		return $tags;
	}


	public static function mixesTabsAndSpaces($contents) {
		return (preg_match("/[\n\r](\t* +\t|\t+ {4})/", "\n". $contents) !== 0)
			|| (preg_match("/[\n\r](\t+[^\t])/", "\n". $contents) === 1
			 && preg_match("/[\n\r]( +[^ ])/", "\n". $contents) === 1);
	}


	//A recursive function that comes up with a list of all of the Organizer paths that a TUIX file references
	//Formerly "logTUIXFileContentsR()"
	public static function logFileContentsR(&$paths, &$tags, $type, $path = '') {
	
		if (is_array($tags)) {
			if (!empty($tags['panel'])) {
				$recordedPath = $path. '/panel';
			
				if (!empty($tags['panel']['panel_type'])) {
					$paths[$recordedPath] = $tags['panel']['panel_type'];
				} else {
					$paths[$recordedPath] = 'list';
				}
			}
			if (!empty($tags['panels']) && is_array($tags['panels'])) {
				foreach ($tags['panels'] as $panelName => &$panel) {
					$recordedPath = $path. '/panels/'. $panelName;
				
					if (!empty($panel['panel_type'])) {
						$paths[$recordedPath] = $panel['panel_type'];
					} else {
						$paths[$recordedPath] = 'list';
					}
				}
			}
		
			foreach ($tags as $tagName => &$tag) {
				if ($path === '') {
					$thisPath = $tagName;
				} else {
					$thisPath = $path. '/'. $tagName;
				}
				\ze\tuix::logFileContentsR($paths, $tag, $type, $thisPath);
			}
		}
	}






	//Try to check the tuix_file_contents table to see which files we need to include
	//Formerly "modulesAndTUIXFiles()"
	public static function modulesAndTUIXFiles(
		$type, $requestedPath = false, $settingGroup = '',
		$getIndividualFiles = true, $includeBaseFunctionalityWithSettingGroups = true,
		$compatibilityClassNames = false, $runningModulesOnly = true
	) {
		return require \ze::funIncPath(__FILE__, __FUNCTION__);
	}


	//This function scans the Module directory for Modules with certain TUIX files, reads them, and turns them into a php array
		//You should initialise $modules and $tags to empty arrays before calling this function.
	//Formerly "loadTUIX()"
	public static function load(
		&$modules, &$tags, $type, $requestedPath = '', $settingGroup = '', $compatibilityClassNames = false,
		$runningModulesOnly = true, $exitIfError = true
	) {
		
		//Check if we can cache the output of this function, and work out a caching path
		$cachePath = false;
		if ($runningModulesOnly === true
		 && ($yaml_files_last_changed = \ze::setting('yaml_files_last_changed'))
		 && ($cachePath = \ze\cache::createDir($type, 'cache/tuix', $onlyForCurrentVisitor = true, $ip = false))) {
			
			//Use the requested tag path in the caching path
			$cachePath .= str_replace('/', '-', $requestedPath);
			
			//Add the settings group if specified
			if ($settingGroup) {
				$cachePath .= '-'. $settingGroup;
			}
			
			//Add the $compatibilityClassNames if specified...
			if (!empty($compatibilityClassNames)) {
				//...but catch the case where they're the same as the settings group, and don't repeat the same thing twice if so.
				if (count($compatibilityClassNames) == 1 && in_array($settingGroup, $compatibilityClassNames)) {
					$cachePath .= '-';
				} else {
					$cachePath .= '-'. implode('.', $compatibilityClassNames);
				}
			}
			
			//Add the time the yaml files last changed to the cache path.
			//In theory this isn't needed, as the checkForChangesInYamlFiles() function wipes these files,
			//however this is just an extra safe-guard in case it failes for some reason.
			$cachePath = CMS_ROOT. $cachePath. '-'. $yaml_files_last_changed. '.json';
			
			
			//If the cache file already exists, use it and don't bother running the rest of this function
			if (is_file($cachePath)
			 && ($tags = json_decode(file_get_contents($cachePath), true))
			 && (isset($tags['m']) && is_array($tags['m']))
			 && (isset($tags['t']) && is_array($tags['t']))) {
				$modules = $tags['m'];
				$tags = $tags['t'];
				return;
			}
		}
		
		//N.b. if we can't use the caching for the individual object, we can still use caching on a file-by-file basis,
		//as the ze\tuix::readFile() also uses caching
		
		
		
		
		$modules = [];
		$tags = [];
	
		//Ensure that the core plugin is included first, if it is there...
		$modules['zenario_common_features'] = [];
	
		if ($type == 'welcome') {
			foreach (scandir($dir = CMS_ROOT. 'zenario/admin/welcome/') as $file) {
				if (substr($file, 0, 1) != '.') {
					$tagsToParse = \ze\tuix::readFile($dir. $file);
					\ze\tuix::parse($tags, $tagsToParse, 'welcome');
					unset($tagsToParse);
				}
			}
	
		} else {
			//Try to check the tuix_file_contents table to see which files we need to include
			foreach (\ze\tuix::modulesAndTUIXFiles(
				$type, $requestedPath, $settingGroup, true, true, $compatibilityClassNames, $runningModulesOnly
			) as $module) {
				if (empty($modules[$module['class_name']])) {
					\ze\module::setPrefix($module);
				
					$modules[$module['class_name']] = [
						'class_name' => $module['class_name'],
						'depends' => \ze\module::dependencies($module['class_name']),
						'included' => false,
						'files' => []];
				}
				$modules[$module['class_name']]['files'][] = $module['filename'];
			}
		}
	
		//Ensure that the core plugin is included first, if it is there... but remove it again if it wasn't.
		if (empty($modules['zenario_common_features'])) {
			unset($modules['zenario_common_features']);
		}
	
		//Include every Module's TUIX files in dependency order
		$limit = 9999;
		do {
			$progressBeingMade = false;
		
			foreach ($modules as $className => &$module) {
				//No need to include a Module twice
				if ($module['included']) {
					continue;
				}
			
				//Make sure that we include files in dependency order by skipping over Modules whose dependencies
				//are still to be included.
				foreach ($module['depends'] as $depends) {
					if (!empty($modules[$depends['dependency_class_name']])
					 && !$modules[$depends['dependency_class_name']]['included']) {
						continue 2;
					}
				}
			
				//Include any xml files in the directory
				if ($dir = \ze::moduleDir($module['class_name'], 'tuix/'. $type. '/', true)) {
				
					if (!isset($module['files'])) {
						$module['files'] = [];
					
						foreach (scandir($dir) as $file) {
							if (substr($file, 0, 1) != '.') {
								$module['files'] = scandir($dir);
							}
						}
					}
				
					foreach ($module['files'] as $file) {
						$tagsToParse = \ze\tuix::readFile($dir. $file);
						\ze\tuix::parse($tags, $tagsToParse, $type, $className, $settingGroup, $compatibilityClassNames, $requestedPath);
						unset($tagsToParse);
					
						if (!isset($module['paths'])) {
							$module['paths'] = [];
						}
						$module['paths'][$file] = $dir. $file;
					}
				}
			
				$module['included'] = true;
				$progressBeingMade = true;
			}
		//Loop while includes are still being done
		} while ($progressBeingMade && --$limit);
	
		//Readjust the start to get rid of the outer tag
		if (!isset($tags[$type])) {
			if ($exitIfError) {
				echo \ze\admin::phrase('The requested path "[[path]]" was not found in the system. If you have just updated or added files to Zenario, you will need to reload the page and possibly clear Zenario\'s cache.', ['path' => $requestedPath]);
				exit;
			} else {
				$tags = [];
			}
		}
		$tags = $tags[$type];
		
		
		//If we can cache this to avoid doing all of this work next time, do so!
		if ($cachePath) {
			@file_put_contents($cachePath, json_encode(['m' => $modules, 't' => $tags]));
			\ze\cache::chmod($cachePath, 0666);
		}
	}
	
	
	//This function ensures that a list of elements is actually a list,
	//and not someone passing in a string or something by mistake
	public static function ensureArray(&$tags, ...$keys) {
		foreach ($keys as $key) {
			if (isset($tags[$key])
			 && !is_array($tags[$key])) {
				$tags[$key] = [];
			}
		}
	}
	
	
	public static function checkOrganizerPanel(&$tags) {
		
		self::ensureArray($tags,
			'columns',
			'quick_filter_buttons',
			'refiners',
			'items',
			'collection_buttons',
			'item_buttons',
			'inline_buttons',
			'hidden_nav'
		);
	}
	
	public static function checkTUIXForm(&$tags) {
		
		self::ensureArray($tags,
			'key',
			'tabs',
			'lovs'
		);
	}
	
	public static function checkAdminToolbar(&$tags) {
		
		self::ensureArray($tags,
			'toolbars',
			'sections'
		);
	}
	
	
	//Formerly "zenarioReadTUIXFileR()"
	public static function readFileR(&$tags, &$xml) {
		$lastKey = null;
		$children = false;
		foreach ($xml->children() as $child) {
			$children = true;
			$key = preg_replace('/[^\w-]/', '', $child->getName());
		
			//Strip underscores from the begining of tag names
			if (substr($key, 0, 1) == '_') {
				$key = substr($key, 1);
			}
		
			//Hack to try and stop repeated parents with the same name overriding each other
			if (isset($tags[$key]) && $lastKey === $key) {
				$i = 2;
				while (isset($tags[$key. ' ('. $i. ')'])) {
					++$i;
				}
				$key = $key. ' ('. $i. ')';
			
			} else {
				$lastKey = $key;
			}
		
			if (!isset($tags[$key])) {
				$tags[$key] = [];
			}
		
			\ze\tuix::readFileR($tags[$key], $child);
		
		}
	
		if ($children) {
			foreach ($xml->attributes() as $key => $child) {
				$tags[$key] = (string) $child;
			}
		} else {
			$tags = trim((string) $xml);
		}
	
	}


	//This function reads a single xml files, and merges it into the information that we've already read

		//$thisTags = \ze\tuix::readFile($path, $moduleClassName);

	//Formerly "zenarioParseTUIX()"
	public static function parse(&$tags, &$par, $type, $moduleClassName = false, $settingGroup = '', $compatibilityClassNames = [], $requestedPath = false, $tag = '', $path = '', $goodURLs = [], $level = 0, $ord = 1, $parent = false, $parentsParent = false, $parentsParentsParent = false) {
	
		if ($path === '') {
			$tag = $type;
			$path = '';
		}
		$path .= $tag. '/';
	
		$isPanel = $tag == 'panel' || $parent == 'panels';
		$lastWasPanel = $parent == 'panel' || $parentsParent == 'panels';
	
		//Note that I'm stripping the "organizer/" from the start of the URL, and the final "/" from the end
		$url = substr($path, strlen($type. '/'), -1);
	
		//Check to see if we should include this tag and its children
		$goFurther = true;
		$includeThisSubTree = false;
		if ($type == 'organizer') {
		
			//If this tag is a panel, then it's valid to link to this tag
			if ($isPanel) {
				//Record the link to this panel.
				//Note that I'm stripping the "organizer/" from the start of the URL, and the final "/" from the end
				array_unshift($goodURLs, $url);
		
				//If the current tag is a panel, and we have a specific path requested, don't include it if it is not on the requested path
				if ($requestedPath && (strlen($requestedPath) < ($sl = strlen($goodURLs[0])) || substr($requestedPath, 0, $sl) != $goodURLs[0])) {
					$goFurther = false;
				}
			}
		
		
			//Purely client-side panels need to be completely in the map; panels which are loaded via AJAX only need some tags in the map
		
			//If a specific path has been requested, show all of the tags under than path
			if ($requestedPath) {
				$includeThisSubTree = true;
		
			//Always send the top right buttons
			} elseif (substr($path, 0, 28) == 'organizer/top_right_buttons/') {
				$includeThisSubTree = true;
		
			//If getting an overall map, only show certain needed tags, to save space
			} elseif (isset($tags[$tag]) && is_array($tags[$tag])) {
				//If this tag was included from parsing another file, it shouldn't be removed now
				$includeThisSubTree = true;
		
			} elseif (!$isPanel && !$lastWasPanel && ($level < 4 || $parent == 'nav' || $parentsParent == 'nav' || $parentsParentsParent == 'nav')) {
				//The left hand nav always needs to be sent
				$includeThisSubTree = true;
		
			} elseif ($parent == 'refiners') {
				//Always include refiner tags
				$includeThisSubTree = true;
		
			} elseif ($parent == 'columns' && $ord == 1) {
				//The first column always needs to be sent as it is used as a fallback
				$includeThisSubTree = true;
		
			} elseif ($parentsParent == 'quick_filter_buttons') {
				//Always include quick filters
				$includeThisSubTree = true;
		
			} else {
				switch ($tag) {
					case 'back_link':
					case 'link':
					case 'panel':
					case 'panels':
					case 'php':
						$includeThisSubTree = true;
						break;
					case 'always_show':
					case 'show_by_default':
						if ($parentsParent == 'columns') {
							$includeThisSubTree = true;
						}
						break;
					case 'client_side':
					case 'encode_id_column':
						if ($parent == 'db_items') {
							$includeThisSubTree = true;
						}
						break;
					case 'branch':
					case 'path':
					case 'refiner':
						if ($parent == 'link') {
							$includeThisSubTree = true;
						}
						break;
					case 'db_items':
					case 'default_sort_column':
					case 'default_sort_desc':
					case 'default_sort_column':
					case 'item':
					case 'no_return':
					case 'panel_type':
					case 'refiner_required':
					case 'reorder':
					case 'title':
					case '_path_here':
						if ($lastWasPanel) {
							$includeThisSubTree = true;
						}
						break;
					case 'css_class':
						if ($parent == 'item') {
							$includeThisSubTree = true;
						}
						break;
					case 'column':
					case 'lazy_load':
						if ($parent == 'reorder') {
							$includeThisSubTree = true;
						}
						break;
				}
			}
		
	
		} elseif ($type == 'admin_boxes') {
			if ($level == 1 && $url != $requestedPath) {
				$goFurther = false;
		
			//Have an option to bypass the filters below and show everything
			} elseif ($settingGroup === true) {
			
			//Filter by setting group
			} elseif ($requestedPath == 'plugin_settings' || $requestedPath == 'site_settings') {
				//Check attributes for keys and values.
				//For module Settings, use the "module_class_name" attribute to only show the related settings
				//However compatilibity now includes inheriting module Settings, so include module Settings from
				//compatible modules as well
				if ($requestedPath == 'plugin_settings'
				 && !empty($par['module_class_name'])
				 && empty($compatibilityClassNames[(string) $par['module_class_name']])) {
					$goFurther = false;
			
				//For Site Settings, only show settings from the current settings group
				} else
				if ($requestedPath == 'site_settings'
				 && !empty($par['setting_group'])
				 && $par['setting_group'] != $settingGroup) {
					$goFurther = false;
				}
			}
		
			$includeThisSubTree = true;
		
	
		//Visitor TUIX has the option to be customised.
		//(However this is optional; you can also show the base logic without any customisation.)
		} elseif ($type == 'visitor') {
		
			//Not sure if this bit is needed..?
			//if ($level == 1 && $url != $requestedPath) {
			//	$goFurther = false;
			//
			//} else
		
			if ($settingGroup
			 && !empty($par['setting_group'])
			 && $par['setting_group'] != $settingGroup) {
				$goFurther = false;
			}
		
			$includeThisSubTree = true;
		
	
		} elseif ($type == 'module_description') {
			//Only the basic descriptive tags, <dependencies> and <inheritance> are copied using Module inheritance.
			if ($settingGroup == 'inherited') {
				switch ($tag) {
					case 'admin_floating_box_tabs':
					case 'background_tasks':
					case 'content_types':
					case 'jobs':
					case 'pagination_types':
					case 'preview_images':
					case 'signals':
					case 'special_pages':
						if (!$parentsParent) {
							$goFurther = false;
						}
						break;
				}
		
			}
		
			$includeThisSubTree = true;
		} else {
			$includeThisSubTree = true;
		}
	
	
		//In certain places, we need to note down which module owns this element
		$isEmptyArray = false;
		if (!(isset($tags[$tag]) && is_array($tags[$tag]))) {
			$isEmptyArray = true;
		
			//Everything that:
				//Launches an AJAX request
				//May need to be customised using fillOrganizerPanel(), fillAdminBox(), etc...
			//will need the class name written down so we know which module's method to call.
			if ($moduleClassName) {
				$addClass = false;
				if ($type == 'organizer') {
					if ($tag == 'ajax'
					 || $parent == 'db_items'
					 || $parent == 'columns'
					 || $parent == 'collection_buttons'
					 || $parent == 'inline_buttons'
					 || $parent == 'item_buttons'
					 || $parent == 'quick_filter_buttons'
					 || $tag == 'combine_items'
					 || $parent == 'refiners'
					 || $parent == 'panels'
					 || $parent == 'nav'
					 || $tag == 'panel'
					 || $tag == 'pick_items'
					 || $tag == 'reorder'
					 || $tag == 'upload'
					 || $tag === false) {
						$addClass = true;
					}
		
				} elseif ($type == 'admin_boxes' || $type == 'wizards') {
					if ($parentsParent === false
					 || $parent == 'tabs'
					 || $parent == 'fields') {
						$addClass = true;
					}
		
				} elseif ($type == 'admin_toolbar') {
					if ($tag == 'ajax'
					 || $parent == 'buttons'
					 || $tag == 'pick_items'
					 || $parent == 'toolbars') {
						$addClass = true;
					}
		
				} elseif ($type == 'visitor') {
					if ($parentsParent === false
					 || $parent == 'columns'
					 || $parent == 'collection_buttons'
					 || $parent == 'inline_buttons'
					 || $parent == 'tabs'
					 || $parent == 'fields') {
						$addClass = true;
					}
				}
			
				if ($addClass) {
					$tags[$tag] = ['class_name' => $moduleClassName];
				}
			}
		}
	
	
		//Recursively scan each child-tag
		$children = 0;
		if (is_array($par)) {
		
			if ($goFurther && (!isset($tags[$tag]) || !is_array($tags[$tag]))) {
				$tags[$tag] = [];
			}
		
			foreach ($par as $key => &$child) {
				++$children;
				$isEmptyArray = true;
			
				if ($goFurther) {
					
					if (isset($child['only_merge_into_an_existing_object'])
					 && $child['only_merge_into_an_existing_object']
					 && !isset($tags[$tag][$key])) {
						continue;
					}
					
					if (\ze\tuix::parse($tags[$tag], $child, $type, $moduleClassName, $settingGroup, $compatibilityClassNames, $requestedPath, $key, $path, $goodURLs, $level + 1, $children, $tag, $parent, $parentsParent)) {
						$includeThisSubTree = true;
					}
				}
			}
		}
	
	
		if (!$includeThisSubTree) {
			unset($tags[$tag]);
			return false;
	
		} else {
			//If this tag had no children, then note down its value
			if (!is_array($par)) {
			
				//Do not allow empty variables to overwrite arrays if they are not empty
				if (empty($par) && !empty($tags[$tag]) && is_array($tags[$tag]) && !$isEmptyArray) {
					//Do nothing
			
				//Module/Skin description files are read in reverse dependancy order, so don't overwrite existing tags
				} else if (isset($tags[$tag]) && ($type == 'module_description' || $type == 'skin_description')) {
					//Do nothing
				
				} else {
					$tags[$tag] = trim((string) $par);
				}
		
			//If this tag has an Organizer Panel...
			} elseif ($type == 'organizer') {
				if ($isPanel) {
					//..note down the path of the panel...
					$tags[$tag]['_path_here'] = $goodURLs[0];
				
					//...and also the link to the panel above if there is one.
					if (isset($goodURLs[1])
					 && !isset($tags[$tag]['back_link'])
				
						//Note that panels defined against a top level item (which is deprecated) should not count as the natural
						//back-link for a panel defined against a second-level item or in the panels container,
						//as we've removed the ability to have nav-links there
					 //&& !($parentsParentsParent == 'organizer' && ($parent == 'nav' || $parent == 'panels'))
					 ) {
						$tags[$tag]['back_link'] = $goodURLs[1];
					}
				}
			}
		
			return true;
		}
	}


	//Strip out any tags/sections that require a priv that the current admin does not have
	//Also count each tags' children
	//Formerly "zenarioParseTUIX2()"
	public static function parse2(&$tags, &$removedColumns, $type, $requestedPath = '', $mode = false, $path = '', $parentKey = false, $parentParentKey = false, $parentParentParentKey = false) {
	
		//Keep track of the path to this point
		if (!$path) {
			$path = $requestedPath;
		} else {
			$path .= ($path? '/' : ''). $parentKey;
		}
	
	
		//Work out whether we should automatically add an "ord" property to the elements we find. This is needed
		//in some places to keep things in the right order.
		//However we need to be careful not to add "ord" properties to everything, as if they are inserted into
		//a list of objects that would accidentally add a new dummy object into the list!
		$noPrivs = [];
		$orderItems = false;
	
		if ($mode == 'csv' || $mode == 'xml') {
			//Don't order anything
		
		} elseif ($type == 'organizer') {
			$orderItems = (!$requestedPath && $parentKey === false)
						|| $parentKey == 'columns'
						|| $parentKey == 'item_buttons'
						|| $parentKey == 'inline_buttons'
						|| $parentKey == 'collection_buttons'
						|| $parentKey == 'quick_filter_buttons'
						|| $parentKey == 'top_right_buttons'
						|| $parentKey == 'nav';
	
		} elseif ($type == 'admin_boxes' || $type == 'welcome' || $type == 'wizards') {
			$orderItems = $parentKey == 'tabs'
						|| $parentKey == 'fields'
						|| $parentParentKey == 'lovs'
						|| $parentKey == 'values';
	
		} elseif ($type == 'slot_controls') {
			$orderItems = $parentKey == 'info'
						|| $parentKey == 'notes'
						|| $parentKey == 'actions'
						|| $parentKey == 're_move_place'
						|| $parentKey == 'overridden_info'
						|| $parentKey == 'overridden_actions';
	
		} elseif ($type == 'admin_toolbar') {
			$orderItems = ($parentParentKey === false && ($parentKey == 'sections' || $parentKey == 'toolbars'))
						|| ($parentParentParentKey == 'sections' && $parentKey == 'buttons');
		}
	
		if (is_array($tags)) {
			//Strip out any tags/sections that require a priv that the current admin does not have
			foreach ($tags as $key => &$value) {
				if ((string) $key == 'priv') {
					
					//Allow a list of permissions to be checked.
					//The element should be shown if the current admin has rights on one of the checks given.
					if (is_array($value)) {
						$privCheckMet = false;
						
						foreach ($value as $privCheck) {
							if (\ze\priv::check((string) $privCheck)) {
								$privCheckMet = true;
								break;
							}
						}
						
						if (!$privCheckMet) {
							return false;
						}
					} else {
						if (!\ze\priv::check((string) $value)) {
							return false;
						}
					}
			
				} elseif ((string) $key == 'local_admins_only') {
					if (\ze\ring::engToBoolean($value) && ($_SESSION['admin_global_id'] ?? false)) {
						return false;
					}
			
				} elseif ((string) $key == 'superadmins_only') {
					if (\ze\ring::engToBoolean($value) && !($_SESSION['admin_global_id'] ?? false)) {
						return false;
					}
			
				} elseif (!\ze\tuix::parse2($value, $removedColumns, $type, $mode, $requestedPath, $path, (string) $key, $parentKey, $parentParentKey)) {
					$noPrivs[] = $key;
				}
			}
		
			foreach($noPrivs as $key) {
				unset($tags[$key]);
			}
			unset($tags['priv']);
		
			if ($orderItems) {
				\ze\tuix::addOrdinalsToTUIX($tags);
			}
		
			//Don't send any SQL to the client
			if ($type == 'organizer') {
				if ($parentKey === false || $parentKey == 'panel' || $parentParentKey = 'panels') {
					if (!\ze\admin::setting('show_dev_tools')) {
					
						if (isset($tags['db_items']) && is_array($tags['db_items'])) {
							unset(
								$tags['db_items']['table'],
								$tags['db_items']['id_column'],
								$tags['db_items']['group_by'],
								$tags['db_items']['where_statement']
							);
						}
					
						if (isset($tags['columns']) && is_array($tags['columns'])) {
							foreach ($tags['columns'] as &$col) {
								if (is_array($col)) {
									if (!empty($col['db_column'])) {
										$col['db_column'] = true;
									}
									unset(
										$col['search_column'],
										$col['sort_column'],
										$col['sort_column_desc'],
										$col['table_join']
									);
								}
							}
						}
					
						if (isset($tags['refiners']) && is_array($tags['refiners'])) {
							foreach ($tags['refiners'] as &$refiner) {
								if (is_array($refiner)) {
									unset(
										$refiner['sql'],
										$refiner['sql_when_searching'],
										$refiner['table_join'],
										$refiner['table_join_when_searching']
									);
								}
							}
						}
					}
			
				} elseif (($parentParentKey === false || $parentParentKey == 'panel' || $parentParentParentKey == 'panels') && $parentKey == 'columns') {
					//If this is a Organizer request for a specific panel, get a list of columns for that
					//panel that are server side only, so that we can later remove these from the output.
					if ($path == $requestedPath. '/columns') {
						$removedColumns = [];
						foreach ($tags as $key => &$value) {
							if (is_array($value) && \ze\ring::engToBoolean($value['server_side_only'] ?? false)) {
								$removedColumns[] = $key;
							}
						}
					}
				}
			}
		}
	
	
		return true;
	}




	//Formerly "sortTUIX()"
	public static function sort(&$tags) {
		if (is_array($tags)) {
			uasort($tags, 'ze\\tuix::sortCompare');
		}
	}

	//Formerly "sortTUIXCompare()"
	public static function sortCompare($a, $b) {
		$ordA = $ordB = 999999;
		if (isset($a['ord'])) {
			$ordA = $a['ord'];
		}
		if (isset($b['ord'])) {
			$ordB = $b['ord'];
		}
	
		if ($ordA === $ordB) {
			return 0;
		} else if ($ordA < $ordB) {
			return -1;
		} else {
			return 1;
		}
	}


	//Formerly "sortCompareByLabel()"
	public static function sortCompareByLabel($a, $b) {
		if ($a['label'] == $b['label']) {
			return 0;
		}
		return ($a['label'] < $b['label']) ? -1 : 1;
	}

	//Formerly "addOrdinalsToTUIX()"
	public static function addOrdinalsToTUIX(&$tuix) {
	
		$ord = 0;
		$previousGrouping = null;
		$replaces = [];
		if (is_array($tuix)) {
			
			//Look out for the special "copy_of" property
			$unsets = [];
			foreach ($tuix as $key => &$tag) {
				if (isset($tag['copy_of'])) {
					$cey = $tag['copy_of'];
					
					//Check we can find the target
					if ($cey != $key && isset($tuix[$cey])) {
						$new = $tag;
						$tag = $tuix[$cey];
						unset($new['copy_of']);
						self::merge($tag, $new);
					
					//If we can't find the target, remove this element
					} else {
						unset($tag, $tuix[$key]);
					}
				}
			}
			
			//Loop through an array of TUIX elements, checking to see if the "ord" property is there
			foreach ($tuix as $key => &$tag) {
				if (is_array($tag)) {
					if (!isset($tag['ord']) || is_array($tag['ord'])) {
						$tag['ord'] = ++$ord;
				
					//If the ordinal is a string, attempt to parse it
					} elseif (!is_numeric($tag['ord'])) {
						
						//We have some logic where you can enter an ordinal such as "fieldName.001" to place a field
						//immediately after another existing field (which needs to be defined above the field you are
						//trying to add). Watch out for a dev trying to use this logic
						$pos = strrpos($tag['ord'], '.');
						if ($pos) {
							$referencedCodeName = substr($tag['ord'], 0, $pos);
							$offset = substr($tag['ord'], $pos + 1);
							
							//Check if the field they are referencing is defined
							if (isset($tuix[$referencedCodeName]['ord'])) {
								$referencedOrd = $tuix[$referencedCodeName]['ord'];
								
								//Attempt to come up with a numeric ordinal that's after the referenced field's ordinal
								if (false === strpos($referencedOrd, '.')) {
									$tag['ord'] = $referencedOrd. '.'. $offset;
								} else {
									$tag['ord'] = $referencedOrd. $offset;
								}
							}
						}
						
						$bits = explode('.', $tag['ord']);
						$referencedCodeName = array_shift($bits);
					
						//If possible, replace the referenced code name with that element's ordinal
						if ($referencedCodeName && !empty($bits) && isset($tuix[$referencedCodeName]['ord'])) {
							$bits = array_merge(explode('.', $tuix[$referencedCodeName]['ord']), $bits) ;
						
							//Add in the rest of the ordinal, but only add at most one decimal place
							$tag['ord'] = array_shift($bits);
							if (!empty($bits)) {
								$tag['ord'] .= '.'. implode('', $bits);
							}
						}
					}
				}
			
				if (!empty($tag['group_with_previous_field']) && !is_null($previousGrouping)) {
					$tag['grouping'] = $previousGrouping;
				}
			
				if (isset($tag['grouping'])) {
					$previousGrouping = $tag['grouping'];
				} else {
					$previousGrouping = null;
				}
			}
		}
	}
	
	//Merges two TUIX objects together
	public static function merge(&$old, &$new) {
		
		if (is_array($old) && is_array($new)) {
			
			foreach ($new as $key => &$tag) {
				if (isset($old[$key])) {
					self::merge($old[$key], $tag);
				
				} elseif (!is_array($tag) || !isset($tag['only_merge_into_an_existing_object']) ||! $tag['only_merge_into_an_existing_object']) {
					$old[$key] = $tag;
				}
			}
		
		} else {
			$old = $new;
		}
	}



	//Include a Module
	//Formerly "zenarioAJAXIncludeModule()"
	public static function includeModule(&$modules, &$tag, $type, $requestedPath, $settingGroup) {

		if (!empty($modules[$tag['class_name']])) {
			return true;
		} elseif (\ze\module::inc($tag['class_name']) && ($module = \ze\module::activate($tag['class_name']))) {
			$modules[$tag['class_name']] = $module;
			return true;
		} else {
			return false;
		}
	}

	//Formerly "TUIXLooksLikeFAB()"
	public static function looksLikeFAB(&$tags) {
		return !empty($tags['tabs']) && is_array($tags['tabs']);
	}
	
	//Try to work out whether this type of TUIX app uses the sync logic.
	//FEA forms and FEA dashes do, FEA lists don't.
	//If it's not an FEA plugin and/or the fea_type property is not set,
	//then call the looksLikeFAB() function to decide as a fallback.
	public static function usesSync(&$tags) {
		if (isset($tags['fea_type'])) {
			switch ($tags['fea_type']) {
				case 'form':
				case 'dash':
					return true;
				default:
					return false;
			}
		
		} else {
			return \ze\tuix::looksLikeFAB($tags);
		}
	}

	//Formerly "TUIXIsFormField()"
	public static function isFormField(&$field) {
	
		if (!$field || !empty($field['snippet'])) {
			return false;
		}
	
		if (!empty($field['type'])) {
			switch ($field['type']) {
				case 'grouping':
				case 'submit':
				case 'toggle':
				case 'button':
					return false;
			}
		}
	
		return true;
	}

	//Formerly "saveCopyOfTUIXOnServer()"
	public static function saveCopyOnServer(&$tags) {

		//Try to save a copy of the admin box in the cache directory
		if (($adminBoxSyncStoragePath = \ze\tuix::syncStoragePath($tags))
		 && (@file_put_contents($adminBoxSyncStoragePath, \ze\tuix::encode($tags)))) {
			\ze\cache::chmod($adminBoxSyncStoragePath, 0666);
			$tags['_sync']['session'] = false;

		//Fallback code to store in the session
		} else {
			if (empty($_SESSION['admin_box_sync'])) {
				$_SESSION['admin_box_sync'] = [0 => 0]; //I want to start counting from 1 so the key is not empty
			}
	
			if (empty($tags['_sync']['session']) || empty($_SESSION['admin_box_sync'][$tags['_sync']['session']])) {
				$tags['_sync']['session'] = count($_SESSION['admin_box_sync']);
			}
	
			$_SESSION['admin_box_sync'][$tags['_sync']['session']] = \ze\tuix::encode($tags);
			$tags['_sync']['cache_dir'] = false;
		}
	}

	//Formerly "loadCopyOfTUIXFromServer()"
	public static function loadCopyFromServer(&$tags, &$clientTags) {

		//Attempt to pick the right box and load from the Storage
			//(This may be in the cache directory or the session, depending on whether the cache was writable)
		if (($adminBoxSyncStoragePath = \ze\tuix::syncStoragePath($clientTags))
		 && (file_exists($adminBoxSyncStoragePath))
		 && (\ze\tuix::decode($tags, $clientTags, file_get_contents($adminBoxSyncStoragePath)))) {
	
		} else
		if (!empty($clientTags['_sync']['session'])
		 && !empty($_SESSION['admin_box_sync'][$clientTags['_sync']['session']])
		 && (\ze\tuix::decode($tags, $clientTags, $_SESSION['admin_box_sync'][$clientTags['_sync']['session']]))) {
	
		} else {
			if (!empty($clientTags['_sync']['session']) || !\ze::setting('fab_use_cache_dir')) {
				echo \ze\admin::phrase('An error occurred when syncing this form with the server. There is a problem with the server\'s $_SESSION variable.');
		
			} else {
				echo \ze\admin::phrase('An error occurred when syncing this form with the server. A file placed in the cache/ directory could not be found.');
			}
			exit;
		}
	}


	//Formerly "adminBoxSyncStoragePath()"
	public static function syncStoragePath(&$box) {
	
		if (!\ze::setting('fab_use_cache_dir')) {
			return false;
		}
	
		if (empty($box['key'])) {
			$box['key'] = [];
		}
	
		if (empty($box['_sync'])) {
			$box['_sync'] = [];
		}
	
		if (empty($box['_sync']['cache_dir'])
		 || !is_dir(CMS_ROOT. 'cache/fabs/'. preg_replace('/[^\\w-]/', '', $box['_sync']['cache_dir']))) {
			$box['_sync']['cache_dir'] =
				\ze\cache::createRandomDir(
					8, $type = 'cache/fabs/', false, false,
					$prefix = 'ab_'. \ze::hash64(json_encode($box), 8). '_');
		}
	
		if (!empty($box['_sync']['cache_dir'])) {
			$box['_sync']['cache_dir'] = str_replace('cache/fabs/', '', $box['_sync']['cache_dir']);
			$box['_sync']['cache_dir'] = preg_replace('/[^\\w-]/', '', $box['_sync']['cache_dir']);
			touch(CMS_ROOT. 'cache/fabs/'. $box['_sync']['cache_dir']. '/accessed');
			return CMS_ROOT. 'cache/fabs/'. $box['_sync']['cache_dir']. '/ab.json';
	
		} else {
			return false;
		}
	}

	//Encode the contents of the cached FABs before we save the cached copy to the disk
	//Formerly "adminBoxEncodeTUIX()"
	public static function encode(&$tags) {
		
		//Strip out all user-entered values before we save a copy of this admin box, for security reasons
			//N.b. be aware that due to the quirks of PHP, when you create a reference to an array inside
			//an array (as the \ze\tuix::readValues() function does), the array you are targeting itself gets
			//replaced by a reference.
			//Because references are involved, we can't simply create a copy of the array!
		$currentValues = [];
		if (!empty($tags['tabs'])
		 && is_array($tags['tabs'])) {
		
			foreach ($tags['tabs'] as $tabName => &$tab) {
			
				if (!empty($tab['fields'])
				 && is_array($tab['fields'])) {
				
					$currentValues[$tabName] = [];
					foreach ($tab['fields'] as $fieldName => &$field) {
						if (isset($field['current_value'])) {
							$currentValues[$tabName][$fieldName] = $field['current_value'];
							unset($field['current_value']);
						}
					}
				}
			}
		}
	
	
		//If we can, use SSL to encode the file so it's a bit harder for someone browsing the server to read them.
		//Firstly, if there's not already a password, we'll set one up in _sync.password.
		//Then encode the tags (but temporarily remove the password when we do this,
		// so that the encoded message does not contain the password)
		if (function_exists('openssl_encrypt')) {
			if (empty($box['_sync'])) {
				$box['_sync'] = [];
			}
	
			if (empty($tags['_sync']['password'])) {
				$tags['_sync']['password'] = base64_encode(openssl_random_pseudo_bytes(32));
			}
			if (empty($tags['_sync']['iv'])) {
				$tags['_sync']['iv'] = base64_encode(openssl_random_pseudo_bytes(16));
			}
		
			$string = openssl_encrypt(
				json_encode($tags), 'aes128',
				base64_decode($tags['_sync']['password']), 0, base64_decode($tags['_sync']['iv']));
		
		} else {
			$string = json_encode($tags);
		}
	
	
		//Put the values back in
		foreach ($currentValues as $tabName => &$tab) {
			foreach ($tab as $fieldName => &$value) {
				$tags['tabs'][$tabName]['fields'][$fieldName]['current_value'] = $value;
			}
		}
		unset($currentValues);
	
	
		return $string;
	}

	//Reverse the above
	//Formerly "adminBoxDecodeTUIX()"
	public static function decode(&$tags, &$clientTags, $string) {
		if (function_exists('openssl_encrypt') && !empty($clientTags['_sync']['password'])) {
			$iv = '';
			if (!empty($clientTags['_sync']['iv'])) {
				$iv = \ze\tuix::deTilde($clientTags['_sync']['iv']);
			}
			$string = openssl_decrypt($string, 'aes128', base64_decode(\ze\tuix::deTilde($clientTags['_sync']['password'])), 0, base64_decode($iv));
		}
	
		return ($tags = json_decode($string, true)) && (is_array($tags));
	}
	
	public static function fieldIsReadonly($field, $tab = null) {
		return !empty($field['show_as_a_label'])
			|| !empty($field['show_as_a_span'])
			|| (isset($field['read_only']) && \ze\ring::engToBoolean($field['read_only']))
			|| (isset($field['readonly']) && \ze\ring::engToBoolean($field['readonly']))
			|| (isset($field['disabled']) && \ze\ring::engToBoolean($field['disabled']))
			|| (isset($tab) && empty($tab['edit_mode']['on']));
	}

	//Formerly "readAdminBoxValues()"
	public static function readValues(&$box, &$fields, &$values, &$changes, $filling, $resetErrors, $checkLOVs = false, $addOrds = false) {
	
		if (!empty($box['tabs']) && is_array($box['tabs'])) {
		
			if ($addOrds) {
				\ze\tuix::addOrdinalsToTUIX($box['tabs']);
			
				if (!empty($box['lovs']) && is_array($box['lovs'])) {
					foreach ($box['lovs'] as &$lov) {
						\ze\tuix::addOrdinalsToTUIX($lov);
					}
				}
			}
		
			foreach ($box['tabs'] as $tabName => &$tab) {
				if (is_array($tab) && !empty($tab['fields']) && is_array($tab['fields'])) {
				
					if ($addOrds) {
						\ze\tuix::addOrdinalsToTUIX($tab['fields']);
					}
					if ($resetErrors || !isset($tab['errors']) || !is_array($tab['errors'])) {
						$tab['errors'] = [];
					}
				
					$unsets = [];
					foreach ($tab['fields'] as $fieldName => &$field) {
						//Remove anything that's not an array to stop bad code causing bugs
						if (!is_array($field)) {
							$unsets[] = $fieldName;
							continue;
						}
					
						//Only check fields that are actually fields
						$isField = 
							isset($field['upload'])
						 || isset($field['pick_items'])
						 || isset($field['captcha'])
						 || (!empty($field['type']) && $field['type'] != 'submit' && $field['type'] != 'toggle' && $field['type'] != 'button');

					
						if ($addOrds && !empty($field['values']) && is_array($field['values'])) {
							\ze\tuix::addOrdinalsToTUIX($field['values']);
						}
						if ($resetErrors) {
							unset($field['error']);
						}
					
						if ($isField) {
							//Fields in readonly mode should use ['value'] as their value;
							//fields not in readonly mode should use ['current_value'].
							$readOnly = $filling || self::fieldIsReadonly($field, $tab);
						
							$currentValue = $readOnly? 'value' : 'current_value';
						
							if (isset($field['value']) && is_array($field['value'])) {
								unset($field['value']);
							}
							if (isset($field['current_value'])) {
								if (is_array($field['current_value']) || $readOnly) {
									unset($field['current_value']);
							
								} elseif (!$filling && $resetErrors) {
									if (empty($field['dont_trim']) || !\ze\ring::engToBoolean($field['dont_trim'])) {
										$field['current_value'] = trim($field['current_value']);
									}
									if (!empty($field['maxlength']) && (int) $field['maxlength']) {
										$field['current_value'] = mb_substr($field['current_value'], 0, (int) $field['maxlength'], 'UTF-8');
									}
								}
							}
						
							if (!isset($field[$currentValue])) {
								if (!$readOnly && isset($field['value'])) {
									$field['current_value'] = $field['value'];
								} else {
									$field[$currentValue] = '';
								}
						
							//Make sure that checkboxes are either 0 or 1, and catch the case where zeros were
							//being treated as strings (which is bad because '0' == true in JavaScript).
							} elseif (isset($field['type']) && $field['type'] == 'checkbox') {
								$field[$currentValue] = \ze\ring::engToBoolean($field[$currentValue]);
						
							//For upload files, try to look up details on any uploaded files
							//so save the client needing an AJAX request to do this.
							} elseif ($filling && $field[$currentValue] && !empty($field['upload'])) {
								foreach (\ze\ray::explodeAndTrim($field[$currentValue], true) as $fileId) {
								
									if ($file = \ze\file::labelDetails($fileId)) {
										if (empty($field['values'])) {
											$field['values'] = [];
										}
									
										$field['values'][$file['id']] = $file;
									}
								}
						
							//For radiogroups/multiple-checkboxes/select lists, check that the selected value(s) are actually in the LOV!
							} else
							if ($checkLOVs
							 && $field[$currentValue]
							 && isset($field['type'])
							 && !isset($field['load_values_from_organizer_path'])
							 && \ze::in($field['type'], 'radios', 'checkboxes', 'select')) {
							
								//Checkboxes can have multiple values, all of which must be checked.
								if ($field['type'] == 'checkboxes') {
									$checkValues = \ze\ray::explodeAndTrim($field[$currentValue]);
								} else {
									$checkValues = [$field[$currentValue]];
								}
							
								foreach ($checkValues as $checkValue) {
								
									//For each selected value, see if the value is in the list of values
									if (isset($field['values'])) {
										//The list of values can either be an array, or a string which points to an array
										//in the LOVs section.
										if (is_array($field['values'])) {
											if (isset($field['values'][$checkValue])) {
												continue;
											}
									
										} else {
											if (isset($box['lovs'][$field['values']][$checkValue])) {
												continue;
											}
										}
									}
								
									//If an option from the LOV wasn't picked, clear the selected value
									$field[$currentValue] = '';
									break;
								}
							}
						
							//Logic for Multiple-Edit
							//This may be removed soon, but I'm keeping it alive for now as a few things still use this functionality
							if (!isset($field['multiple_edit'])) {
								$changed = false;
						
							} else
							if ($readOnly
							 || (isset($field['multiple_edit']['changed']) && !isset($field['multiple_edit']['_changed']))) {
								$changed = \ze\ring::engToBoolean($field['multiple_edit']['changed'] ?? false);
						
							} else {
								$changed = \ze\ring::engToBoolean($field['multiple_edit']['_changed'] ?? false);
							}
						}
					
						$fields[$tabName. '/'. $fieldName] = &$tab['fields'][$fieldName];
						if ($isField) {
							$values[$tabName. '/'. $fieldName] = &$tab['fields'][$fieldName][$currentValue];
							$changes[$tabName. '/'. $fieldName] = $changed;
						}
					
						if (!isset($fields[$fieldName])) {
							$fields[$fieldName] = &$tab['fields'][$fieldName];
							if ($isField) {
								$values[$fieldName] = &$tab['fields'][$fieldName][$currentValue];
								$changes[$fieldName] = $changed;
							}
						}
					
						if ($isField) {
							//Editor fields will need the \ze\file::addImageDataURIsToDatabase() run on them
							if (isset($field['current_value'])
							 && \ze\ray::value($box, 'tabs', $tabName, 'fields', $fieldName, 'type')  == 'editor'
							 && !empty($box['tabs'][$tabName]['fields'][$fieldName]['insert_image_button'])) {
								//Convert image data urls to files in the database
								\ze\file::addImageDataURIsToDatabase($field['current_value'], \ze\link::absolute());
							}
						}
					}
					if (!empty($unsets)) {
						foreach ($unsets as $unset) {
							unset($tab['fields'][$unset]);
						}
					}
				}
			}
		}
	}

	//Formerly "applyValidationFromTUIXOnTab()"
	public static function applyValidation(&$tab, $saving) {
		
		$uniques = [
			'a' => [],
			'b' => [],
			'c' => []
		];
		
		//Loop through each field, looking for fields with validation set
		if (isset($tab['fields']) && is_array($tab['fields'])) {
			foreach ($tab['fields'] as $fieldName => &$field) {
				
				if (isset($field['captcha'])) {
					
					if (!isset($_SESSION['fea_passed_captchas'])) {
						$_SESSION['fea_passed_captchas'] = [];
						
						if (!empty($field['current_value'])) {
							if (!isset($_SESSION['fea_passed_captchas'][$field['current_value']])) {
								
								//For testing on your local Mac
								//$arrContextOptions=array(
								//	"ssl"=>array(
								//		"verify_peer"=>false,
								//		"verify_peer_name"=>false,
								//	),
								//); 
								
								if (($json = file_get_contents(
										'https://www.google.com/recaptcha/api/siteverify'.
										'?secret='. rawurlencode(\ze::setting('google_recaptcha_secret_key')).
										'&response='. rawurlencode($field['current_value'])
										
										//For testing on your local Mac
										//, false, stream_context_create($arrContextOptions)
									))
								 && ($json = json_decode($json, true))
								 && (!empty($json['success']))
								) {
									$_SESSION['fea_passed_captchas'][$field['current_value']] = true;
								} else {
									$field['current_value'] = '';
								}
							}
						}
					}
				}
				
				
				//Only check fields with then validation property
				if (empty($field['validation'])) {
					continue;
				}
				
				//When not saving, ignore fields with the only_validate_when_saving option set
				if (!$saving && !empty($field['validation']['only_validate_when_saving'])) {
					continue;
				}
				
				$hidden = !empty($field['_was_hidden_before'])
					   || (isset($tab['hidden']) && \ze\ring::engToBoolean($tab['hidden']))
					   || (isset($field['hidden']) && \ze\ring::engToBoolean($field['hidden']));
				
				//Ignore hidden fields if the only_validate_when_not_hidden option set
				if ($hidden && !empty($field['validation']['only_validate_when_not_hidden'])) {
					continue;
				}
			
				$fieldValue = '';
				if (isset($field['current_value'])) {
					$fieldValue = (string) $field['current_value'];
				} elseif (isset($field['value'])) {
					$fieldValue = (string) $field['value'];
				}
				$notSet = !(trim($fieldValue) || $fieldValue === '0');
			
				//Check for required fields
				if (($msg = $field['validation']['required'] ?? false) && $notSet) {
					$field['error'] = $msg;
			
				//Check for fields that are required if not hidden. (Note that it is the user submitted data from the client
				//which determines whether a field was hidden.)
				} elseif (($msg = $field['validation']['required_if_not_hidden'] ?? false) && !$hidden && $notSet) {
					$field['error'] = $msg;
			
				//If a field was not required, do not run any further validation logic on it if it is empty 
				} elseif ($notSet) {
					continue;
			
				} elseif (($msg = $field['validation']['email'] ?? false) && !\ze\ring::validateEmailAddress($fieldValue)) {
					$field['error'] = $msg;
			
				} elseif (($msg = $field['validation']['emails'] ?? false) && !\ze\ring::validateEmailAddress($fieldValue, true)) {
					$field['error'] = $msg;
			
				} elseif (($msg = $field['validation']['no_spaces'] ?? false) && preg_replace('/\S/', '', $fieldValue)) {
					$field['error'] = $msg;
			
				} elseif (($msg = $field['validation']['no_commas'] ?? false) && preg_replace('/[^,]/', '', $fieldValue)) {
					$field['error'] = $msg;
			
				} elseif (($msg = $field['validation']['numeric'] ?? false) && !is_numeric($fieldValue)) {
					$field['error'] = $msg;
			
				} elseif (($msg = $field['validation']['screen_name'] ?? false) && !\ze\ring::validateScreenName($fieldValue)) {
					$field['error'] = $msg;
			
				} elseif (($msg = $field['validation']['no_special_characters'] ?? false) && !\ze\ring::validateScreenName(str_replace(',', '', $fieldValue), true)) {
					$field['error'] = $msg;
			
				} elseif (($msg = $field['validation']['ascii_only'] ?? false) && ($fieldValue !== \ze\escape::ascii($fieldValue))) {
					$field['error'] = $msg;
			
				} elseif (($msg = $field['validation']['mobile_number'] ?? false) && !preg_match('/^\(?\+?([0-9]{1,4})\)?[-\. ]?(\d{3})[-\. ]?([0-9]{7})$/', trim($fieldValue))) {
					$field['error'] = $msg;
			
				} else {
					//Check if something already exists in a table with that name
					if (!empty($field['validation']['non_present']['table'])
					 && !empty($field['validation']['non_present']['column'])
					 && !empty($field['validation']['non_present']['message'])) {
						
						$ids = $field['validation']['non_present']['ids'] ?? [];
						$ids[$field['validation']['non_present']['column']] = $fieldValue;
						
						if (\ze\row::exists(\ze\dbAdm::addConstantsToString($field['validation']['non_present']['table']), $ids)) {
							$field['error'] = $field['validation']['non_present']['message'];
							
							if (is_string($field['error'])) {
								\ze\lang::applyMergeFields($field['error'], $ids);
							}
							continue;
						}
					}
					
					//Check that unique values
					if (($msg = $field['validation']['unique_'. ($u = 'a')] ?? false)
					 || ($msg = $field['validation']['unique_'. ($u = 'b')] ?? false)
					 || ($msg = $field['validation']['unique_'. ($u = 'c')] ?? false)) {
						if (isset($uniques[$u][$fieldValue])) {
							$field['error'] = $msg;
							continue;
						} else {
							$uniques[$u][$fieldValue] = true;
						}
					}
					
					 
					
					
					
					//Check validation rules for file pickers
					$must_be_image = !empty($field['validation']['must_be_image']);
					$must_be_image_or_svg = !empty($field['validation']['must_be_image_or_svg']);
					$must_be_gif_or_png = !empty($field['validation']['must_be_gif_or_png']);
					$must_be_gif_ico_or_png = !empty($field['validation']['must_be_gif_ico_or_png']);
					$must_be_ico = !empty($field['validation']['must_be_ico']);
				
					if ($must_be_image
					 || $must_be_image_or_svg
					 || $must_be_gif_or_png
					 || $must_be_gif_ico_or_png
					 || $must_be_ico) {
					
						//These validation rules should work for multiple file pickers, so we'll need to
						//split by a comma and validate each file separately
						foreach (\ze\ray::explodeAndTrim($fieldValue) as $file) {
						
							//If this file has just been picked, we'll need to check it from the disk
							if ($filepath = \ze\file::getPathOfUploadInCacheDir($file)) {
								$mimeType = \ze\file::mimeType($filepath);
						
							//Otherwise look for it in the files table
							} else {
								$mimeType = \ze\row::get('files', 'mime_type', $file);
							}
						
							$isIcon = \ze::in($mimeType, 'image/vnd.microsoft.icon', 'image/x-icon');
							$isGIFPNG = \ze::in($mimeType, 'image/gif', 'image/png');
						
							//Check all of the possible rules for image validation.
							//Stop checking image validation rules for this field as soon
							//as we find one picked file that doesn't match one rule
							if ($must_be_image && !\ze\file::isImage($mimeType)) {
								$field['error'] = $field['validation']['must_be_image'];
								continue;
						
							} else
							if ($must_be_image_or_svg && !\ze\file::isImageOrSVG($mimeType)) {
								$field['error'] = $field['validation']['must_be_image_or_svg'];
								continue;
						
							} else
							if ($must_be_gif_or_png && !$isGIFPNG) {
								$field['error'] = $field['validation']['must_be_gif_or_png'];
								continue;
						
							} else
							if ($must_be_gif_ico_or_png && !($isGIFPNG || $isIcon)) {
								$field['error'] = $field['validation']['must_be_gif_ico_or_png'];
								continue;
						
							} else
							if ($must_be_ico && !$isIcon) {
								$field['error'] = $field['validation']['must_be_ico'];
								continue;
							}
						}
					}
				}
			}
		}
	}


	public static $yamlFilePath = -1;


	//Formerly "translatePhraseInTUIX()"
	public static function translatePhrase(&$tag, &$overrides, $path, &$moduleClass, &$languageId, &$scan, $i = false, $j = false, $k = false) {
	
		if ($k !== false) {
			$phrase = &$tag[$i][$j][$k];
		} elseif ($j !== false) {
			$phrase = &$tag[$i][$j];
		} elseif ($i !== false) {
			$phrase = &$tag[$i];
		} else {
			$phrase = &$tag;
		}
	
	
		//Don't try and translate numbers, e.g. the hour/minute select list, or true/false values
		//Also don't try and translate empty strings
		if (is_numeric($phrase) || is_bool($phrase) || $phrase === '') {
			return;
	
		//Also don't try to translate any properties that contain microtemplates
		} elseif ($i !== false
		 && !empty($tag['enable_microtemplates_in_properties'])
		 && preg_match('/(\{\{|\{\%|\<\%)/', $phrase)) {
			return;
		}
	
	
		if ($i !== false) {
			$path .= '.'. $i;
		}
		if ($j !== false) {
			$path .= '.'. $j;
		}
		if ($k !== false) {
			$path .= '.'. $k;
		}
	
		if ($scan) {
			$overrides[$path] = $phrase;
		
		} else {
		
			if (isset($overrides[$path])) {
				$phrase = $overrides[$path];
			}
		
			$phrase = \ze\lang::phrase($phrase, false, $moduleClass, $languageId, self::$yamlFilePath);
		}
	}
	
	//Formerly "translatePhrasesInTUIXObject()"
	public static function translatePhrasesInObject(&$t, &$o, &$p, &$c, &$l, &$s, $objectType = false) {
	
		if ($objectType === false) {
		
			if (is_array($t)) {
				foreach ($t as $i => &$thing) {
					if (false !== ($pos = strrpos($i, '.'))) {
						$codeName = substr($i, $pos + 1);
					} else {
						$codeName = $i;
					}
				
					switch ($codeName) {
						case 'title':
						case 'label':
						case 'tooltip':
						case 'disabled_tooltip':
					
						case 'placeholder':
						case 'subtitle':
						case 'missing_items_warning':
						case 'no_items_message':
						case 'item_count_message':
						case 'title_for_existing_records':
						case 'search_bar_placeholder':
							\ze\tuix::translatePhrase($t, $o, $p, $c, $l, $s, $i);
							break;
					}
				}
			
				if (isset($t[$i='error_on_form_message'])) \ze\tuix::translatePhrase($t, $o, $p, $c, $l, $s, $i);
				if (isset($t[$i='confirm_on_close'][$j='title'])) \ze\tuix::translatePhrase($t, $o, $p, $c, $l, $s, $i, $j);
				if (isset($t[$i='confirm_on_close'][$j='message'])) \ze\tuix::translatePhrase($t, $o, $p, $c, $l, $s, $i, $j);
				if (isset($t[$i='confirm_on_close'][$j='button_message'])) \ze\tuix::translatePhrase($t, $o, $p, $c, $l, $s, $i, $j);
				if (isset($t[$i='confirm_on_close'][$j='cancel_button_message'])) \ze\tuix::translatePhrase($t, $o, $p, $c, $l, $s, $i, $j);
			}
	
		} else {
			switch ($objectType) {
				//lovs and phrases don't have standard properties like titles/labels
				case 'lovs':
					if (!empty($t) && is_array($t)) {
						foreach ($t as $k => &$lov) {
							$q = $p. '.'. $k;
							\ze\tuix::translatePhrasesInObject($lov, $o, $q, $c, $l, $s, 'lov');
						}
					}
					break;
				
				//Phrases can be stings, which should just be translated, or strings nested
				//inside an array, which should be looped through and translated.
				case 'phrases':
					if (!empty($t)) {
						if (is_array($t)) {
							foreach ($t as $k => &$phrase) {
								$q = $p. '.'. $k;
								\ze\tuix::translatePhrasesInObject($phrase, $o, $q, $c, $l, $s, $objectType);
							}
						} else {
							\ze\tuix::translatePhrase($t, $o, $p, $c, $l, $s);
						}
					}
					break;
				
				//lov values can be strings, in which case the string should be translated,
				//or be objects, which do have the standard properties translated.
				case 'lov':
				case 'values':
					if (is_string($t)) {
						\ze\tuix::translatePhrase($t, $o, $p, $c, $l, $s);
						break;
					
					} else {
						//Continue to use default logic below
					}
				
				//Assuming a generic object, translate the usual generic properties that the object might have
				default:
					if (isset($t[$i='title'])) \ze\tuix::translatePhrase($t, $o, $p, $c, $l, $s, $i);
					if (isset($t[$i='label'])) \ze\tuix::translatePhrase($t, $o, $p, $c, $l, $s, $i);
					if (isset($t[$i='message'])) \ze\tuix::translatePhrase($t, $o, $p, $c, $l, $s, $i);
					if (isset($t[$i='multiple_select_message'])) \ze\tuix::translatePhrase($t, $o, $p, $c, $l, $s, $i);
					if (isset($t[$i='tooltip'])) \ze\tuix::translatePhrase($t, $o, $p, $c, $l, $s, $i);
					if (isset($t[$i='disabled_tooltip'])) \ze\tuix::translatePhrase($t, $o, $p, $c, $l, $s, $i);
					if (isset($t[$i='placeholder'])) \ze\tuix::translatePhrase($t, $o, $p, $c, $l, $s, $i);
					if (isset($t[$i='no_search_label'])) \ze\tuix::translatePhrase($t, $o, $p, $c, $l, $s, $i);
					
					//Translate some specific properties to specific object types
					switch ($objectType) {
						case 'columns':
							\ze\tuix::translatePhrasesInObjects(['values'], $t, $o, $p, $c, $l, $s);
							
							if (isset($t[$i='sort_asc'])) \ze\tuix::translatePhrase($t, $o, $p, $c, $l, $s, $i);
							if (isset($t[$i='sort_desc'])) \ze\tuix::translatePhrase($t, $o, $p, $c, $l, $s, $i);
							if (isset($t[$i='empty_value'])) \ze\tuix::translatePhrase($t, $o, $p, $c, $l, $s, $i);
							break;
		
						case 'tabs':
							\ze\tuix::translatePhrasesInObjects(['notices', 'fields'], $t, $o, $p, $c, $l, $s);
							
							//Look for anything that looks like custom template fields and translate those too
							foreach ($t as $k => &$fields) {
								if (is_array($fields)
								 && !empty($fields)
								 && $k[0] == 'c'
								 && substr($k, 0, 7) == 'custom_'
								 && substr($k, -7) == '_fields') {
									\ze\tuix::translatePhrasesInObjects([$k], $t, $o, $p, $c, $l, $s, 'fields');
								}
							}
							break;
		
						case 'notices':
							break;
			
						case 'fields':
							\ze\tuix::translatePhrasesInObjects(['values'], $t, $o, $p, $c, $l, $s);
					
							if (isset($t[$i='legend'])) \ze\tuix::translatePhrase($t, $o, $p, $c, $l, $s, $i);
							if (isset($t[$i='side_note'])) \ze\tuix::translatePhrase($t, $o, $p, $c, $l, $s, $i);
							if (isset($t[$i='note_below'])) \ze\tuix::translatePhrase($t, $o, $p, $c, $l, $s, $i);
							if (isset($t[$i='empty_value'])) \ze\tuix::translatePhrase($t, $o, $p, $c, $l, $s, $i);
							if (isset($t[$i='error_on_form_message'])) \ze\tuix::translatePhrase($t, $o, $p, $c, $l, $s, $i);
					
							if (isset($t[$i='validation']) && is_array($t[$i])) {
								foreach ($t[$i] as $j => $object) {
									switch ($j) {
										case 'only_validate_when_saving':
										case 'only_validate_when_not_hidden':
											break;
										case 'non_present':
											if (isset($t[$i][$j][$k='message'])) \ze\tuix::translatePhrase($t, $o, $p, $c, $l, $s, $i, $j, $k);
											break;
										default:
											\ze\tuix::translatePhrase($t, $o, $p, $c, $l, $s, $i, $j);
									}
								}
							}
				
							if (isset($t[$i='snippet'])) {
								if (isset($t[$i][$j='h1'])) \ze\tuix::translatePhrase($t, $o, $p, $c, $l, $s, $i, $j);
								if (isset($t[$i][$j='h2'])) \ze\tuix::translatePhrase($t, $o, $p, $c, $l, $s, $i, $j);
								if (isset($t[$i][$j='h3'])) \ze\tuix::translatePhrase($t, $o, $p, $c, $l, $s, $i, $j);
								if (isset($t[$i][$j='h4'])) \ze\tuix::translatePhrase($t, $o, $p, $c, $l, $s, $i, $j);
								if (isset($t[$i][$j='label'])) \ze\tuix::translatePhrase($t, $o, $p, $c, $l, $s, $i, $j);
								if (isset($t[$i][$j='p'])) \ze\tuix::translatePhrase($t, $o, $p, $c, $l, $s, $i, $j);
								if (isset($t[$i][$j='span'])) \ze\tuix::translatePhrase($t, $o, $p, $c, $l, $s, $i, $j);
							}
				
							if (isset($t[$i='upload'])) {
								if (isset($t[$i][$j='dropbox_phrase'])) \ze\tuix::translatePhrase($t, $o, $p, $c, $l, $s, $i, $j);
								if (isset($t[$i][$j='upload_phrase'])) \ze\tuix::translatePhrase($t, $o, $p, $c, $l, $s, $i, $j);
							}
		
							//Translate button values
							if (isset($t['value']) && isset($t['type']) && ($t['type'] == 'button' || $t['type'] == 'toggle' || $t['type'] == 'submit')) {
			
								//Only translate the values if they look like text
								if ('' !== trim(preg_replace(['/\\{\\{.*?\\}\\}/', '/\\{\\%.*?\\%\\}/', '/\\<\\%.*?\\%\\>/', '/\\W/'], '', $t['value']))) {
									\ze\tuix::translatePhrase($t, $o, $p, $c, $l, $s, 'value');
								}
							}
				
							//N.b. there's no "break" here,
							//we continue on to the next statement as some fields can be buttons too!
		
						case 'collection_buttons':
						case 'item_buttons':
						case 'inline_buttons':
						case 'quick_filter_buttons':
							if (isset($t[$i='confirm'][$j='title'])) \ze\tuix::translatePhrase($t, $o, $p, $c, $l, $s, $i, $j);
							if (isset($t[$i='confirm'][$j='message'])) \ze\tuix::translatePhrase($t, $o, $p, $c, $l, $s, $i, $j);
							if (isset($t[$i='confirm'][$j='button_message'])) \ze\tuix::translatePhrase($t, $o, $p, $c, $l, $s, $i, $j);
							if (isset($t[$i='confirm'][$j='cancel_button_message'])) \ze\tuix::translatePhrase($t, $o, $p, $c, $l, $s, $i, $j);
							if (isset($t[$i='ajax'][$j='confirm'][$k='title'])) \ze\tuix::translatePhrase($t, $o, $p, $c, $l, $s, $i, $j, $k);
							if (isset($t[$i='ajax'][$j='confirm'][$k='message'])) \ze\tuix::translatePhrase($t, $o, $p, $c, $l, $s, $i, $j, $k);
							if (isset($t[$i='ajax'][$j='confirm'][$k='button_message'])) \ze\tuix::translatePhrase($t, $o, $p, $c, $l, $s, $i, $j, $k);
							if (isset($t[$i='ajax'][$j='confirm'][$k='cancel_button_message'])) \ze\tuix::translatePhrase($t, $o, $p, $c, $l, $s, $i, $j, $k);
							break;
					}
			}
		}
	}


	//Formerly "translatePhrasesInTUIXObjects()"
	public static function translatePhrasesInObjects($tagNames, &$tags, &$overrides, $path, $moduleClass, $languageId = false, $scan = false, $overrideObjectType = null) {
	
		if (!is_array($tagNames)) {
			$tagNames = [$tagNames];
		}
	
		foreach ($tagNames as $objectType) {
			if (!empty($tags[$objectType]) && is_array($tags[$objectType])) {
				foreach ($tags[$objectType] as $key => &$object) {
					$p = $path. '.'. $objectType. '.'. $key;
					\ze\tuix::translatePhrasesInObject(
						$object, $overrides, $p, $moduleClass, $languageId, $scan, $overrideObjectType ?? $objectType);
				}
			}
		}
	}

	//Automatically translate any titles/labels in TUIX
	//Formerly "translatePhrasesInTUIX()"
	public static function translatePhrases(&$tags, &$overrides, $path, $moduleClass, $languageId = false, $scan = false) {
		
		$path = 'phrase.'. $path;
		
		\ze\tuix::translatePhrasesInObject(
			$tags, $overrides, $path, $moduleClass, $languageId, $scan);
	
		\ze\tuix::translatePhrasesInObjects(
			['phrases', 'lovs', 'tabs', 'columns', 'collection_buttons', 'item_buttons', 'inline_buttons', 'quick_filter_buttons'],
			$tags, $overrides, $path, $moduleClass, $languageId, $scan);
	}

	//Formerly "lookForPhrasesInTUIX()"
	public static function lookForPhrases($path = '') {
	
		$overrides = [];
		$tags = [];
		$moduleFilesLoaded = [];
		\ze\tuix::load($moduleFilesLoaded, $tags, 'visitor', $path);

		if (!empty($tags[$path])) {
			\ze\tuix::translatePhrases(
				$tags[$path], $overrides, $path, false, false, true);
		}
	
		return $overrides;
	}

	//Formerly "setupOverridesForPhrasesInTUIX()"
	public static function setupOverridesForPhrases(&$box, &$fields, $path = '', $valuesInDB = null) {
	
		$ord = 1000;
		$languageId = $box['key']['languageId'] ?? false;
		$showSecondLanguageColumn = $languageId && $languageId != \ze::$defaultLang && \ze\priv::check('_PRIV_VIEW_LANGUAGE');
		
		if ($showSecondLanguageColumn) {
			$html = '
				<table class="zfab_customise_phrases cols_3"><tr>
					<th>Original Phrase</th>
					<th>Customised Phrase</th>
					<th>' . \ze\lang::name($languageId) . '</th>';
		} else {
			$html = '
				<table class="zfab_customise_phrases cols_2"><tr>
					<th>Original Phrase</th>
					<th>Customised Phrase</th>';
		}
		$html .= '
			</tr>';
		$fields['phrase_table_start'] = [
			'ord' => ++$ord,
			'snippet' => [
				'html' => $html
			]
		];
		
		if (is_null($valuesInDB)) {
			$valuesInDB = [];
			\ze\tuix::loadAllPluginSettings($box, $valuesInDB);
		}
		
		
		foreach (\ze\tuix::lookForPhrases($path) as $ppath => $defaultText) {
		
			$fields[$ppath] = [
				'plugin_setting' => [
					'name' => $ppath,
					'value' => $defaultText,
					'dont_save_default_value' => true,
					'save_empty_value_when_hidden' => false
				],
				'ord' => ++$ord,
				'same_row' => true,
				'pre_field_html' => '
					<tr><td>
						'. htmlspecialchars($defaultText). '
						<br/>
						<span>(<span>'. htmlspecialchars(substr($ppath, 7)). '</span>)</span>
					</td><td>
				',
				'type' => strpos(trim($defaultText), "\n") === false? 'text' : 'textarea',
				'post_field_html' => '
					</td>
				'
			];
		
			if (isset($valuesInDB[$ppath])) {
				$fields[$ppath]['value'] = $valuesInDB[$ppath];
			} else {
				$fields[$ppath]['value'] = $defaultText;
			}
			
			if ($showSecondLanguageColumn) {
				$fields[$ppath . '__' . $languageId] = [
					'value' => \ze\row::get('visitor_phrases', 'local_text', ['code' => $fields[$ppath]['value'], 'language_id' => $languageId, 'module_class_name' => $box['module_class_name']]),
					'ord' => ++$ord,
					'same_row' => true,
					'readonly' => !\ze\priv::check('_PRIV_MANAGE_LANGUAGE_PHRASE'),
					'pre_field_html' => '
						<td>
					',
					'type' => strpos(trim($defaultText), "\n") === false? 'text' : 'textarea',
					'post_field_html' => '
						</td></tr>
					'
				];
			} else {
				$fields[$ppath]['post_field_html'] .= '</tr>';
			}
		}
	
		$fields['phrase_table_end'] = [
			'ord' => ++$ord,
			'same_row' => true,
			'snippet' => [
				'html' => '
					</table>'
			]
		];
	
		if (\ze\row::exists('languages', ['translate_phrases' => 1])) {
			$mrg = [
				'def_lang_name' => htmlspecialchars(\ze\lang::name(\ze::$defaultLang)),
				'phrases_panel' => htmlspecialchars(\ze\link::absolute(). 'zenario/admin/organizer.php#zenario__languages/panels/phrases')
			];
		
			$fields['phrase_table_end']['show_phrase_icon'] = true;
			$fields['phrase_table_end']['snippet']['html'] .= '
				<br/>
				<span>'.
				\ze\admin::phrase('<a href="[[phrases_panel]]" target="_blank">Click here to manage translations in Organizer</a>.', $mrg).
				'</span>';
		}
	}
	
	public static function saveOverridePhrases($box, $values, $path = '') {
		$languageId = $box['key']['languageId'] ?? false;
		$showSecondLanguageColumn = $languageId && $languageId != \ze::$defaultLang && \ze\priv::check('_PRIV_VIEW_LANGUAGE');
		
		if (!$showSecondLanguageColumn || !\ze\priv::check('_PRIV_MANAGE_LANGUAGE_PHRASE')) {
			return false;
		}
		
		$valuesInDB = [];
		\ze\tuix::loadAllPluginSettings($box, $valuesInDB);
		
		foreach (\ze\tuix::lookForPhrases($path) as $ppath => $defaultText) {
			if (isset($valuesInDB[$ppath])) {
				$code = $valuesInDB[$ppath];
			} else {
				$code = $defaultText;
			}
			
			$localText = $values[$ppath . '__' . $languageId];
			
			//Only save the phrase if it is not an empty string. This is to prevent the phrase being deleted accidently.
			if ($code && $localText !== '') {
				\ze\row::set(
					'visitor_phrases', 
					['local_text' => $localText], 
					['code' => $code, 'module_class_name' => $box['module_class_name'], 'language_id' => $languageId]
				);
			}
		}
		return true;
	}


	//Formerly "setupMultipleRowsInTUIX()"
	public static function setupMultipleRows(
		&$box, &$fields, &$values, &$changes, $filling,
		&$templateFields,
		$addRows = 0,
		$minNumRows = 0,
		$tabName = 'details',
		$deleteButtonCodeName = '',
		$idFieldCodeName = '',
		$dupFieldCodeName = '',
		$firstN = 1,
		$setGrouping = false
	) {
	
		$changed = false;
		$removeRows = [];
	
		$tab = &$box['tabs'][$tabName];
	
		$fieldCodeNames = array_keys($templateFields);
		if (empty($fieldCodeNames)) {
			echo 'No template fields found';
			exit;
		}
		
		$firstCodeName = $fieldCodeNames[0];
		if (strpos($firstCodeName, 'znz') === false) {
			echo 'Template fields should have code names of the form "code_name__znz"';
			exit;
		}
		
	
		//Check if ordinals have not been added, and add them automatically if needed
		if (!isset($templateFields[$firstCodeName]['ord'])) {
			\ze\tuix::addOrdinalsToTUIX($templateFields);
		
			foreach ($templateFields as $id => &$field) {
				if (strpos($field['ord'], 'znz') === false) {
					$field['ord'] = 'znz'. str_pad($field['ord'], 3, '0', STR_PAD_LEFT);
				}
			}
			unset($field);
		}
	
		//Work out how many rows are there are currently, including deleted rows
		$dupN = false;
		$numRows = 0;
		$numDeletedRows = 0;
		$n = $firstN;
		while ($rowExists = !empty($tab['fields'][str_replace('znz', $n, $firstCodeName)])) {
			++$numRows;
		
			if ($deleted = $deleteButtonCodeName && !empty($tab['fields'][str_replace('znz', $n, $deleteButtonCodeName)]['pressed'])) {
				++$numDeletedRows;
		
			} elseif (!$addRows && $dupFieldCodeName !== '' && !empty($tab['fields'][str_replace('znz', $n, $dupFieldCodeName)]['pressed'])) {
				$dupN = $n;
				$addRows = 1;
			}
		
			++$n;
		}
	
	
		//Add extra rows if requested
		$numRows += (int) $addRows;
	
		//If a minimum number of rows is set, ensure that the number of (non-deleted)
		//rows is not smaller that the minimum
		if ($minNumRows
		 && $minNumRows > ($numRows - $numDeletedRows)) {
			$numRows = $minNumRows + $numDeletedRows;
		}
		
		
		//Check to see if we need to add or delete any rows
		$n = $firstN;
		while (true) {
		
			$deleted = false;
			if ($rowExists = !empty($tab['fields'][str_replace('znz', $n, $firstCodeName)])) {
			
				//Check if the delete button has been pressed for a row.
				if ($deleted = $deleteButtonCodeName && !empty($tab['fields'][str_replace('znz', $n, $deleteButtonCodeName)]['pressed'])) {
				
					//For things with ids in the database, we'll need to keep the rows in existance so
					//the system can see that they're deleted.
					if ($idFieldCodeName && !empty($tab['fields'][str_replace('znz', $n, $idFieldCodeName)]['value'])) {
						//Hide all of the fields on that row, but keep the actual fields and values
						$removeRows[$n] = false;

		
					} else {
						//Remove a rule, bumping rules below up to its position
						$m = $n + 1;
						while ($rowExists = !empty($tab['fields'][str_replace('znz', $m, $firstCodeName)])) {
							foreach ($fieldCodeNames as $fieldCodeName) {
								$cutName = str_replace('znz', $m, $fieldCodeName);
								$pstName = str_replace('znz', $m - 1, $fieldCodeName);
					
								foreach ([
									'values', 'value', 'current_value', 'pressed',
									'selected_option', '_display_value',
									'hidden', '_was_hidden_before'
								] as $val) {
									if (isset($tab['fields'][$cutName][$val])) {
										$tab['fields'][$pstName][$val] = $tab['fields'][$cutName][$val];
										unset($tab['fields'][$cutName][$val]);
									} else {
										unset($tab['fields'][$pstName][$val]);
									}
								}
							}
						
							++$m;
						}
					
						//Remove the very last row
						$removeRows[$m - 1] = true;
					}
				}
			}
		
			$inRange = $n - $firstN < $numRows;
		
			if ($inRange) {
			
				if ($rowExists) {
					//Row exists and should be there, nothing to do
				} else {
					//Row doesn't exist and should be added.
					//Copy the template fields, replacing "znz" with the row number
					$templateFieldsForThisRow = json_decode(str_replace('znz', $n, json_encode($templateFields)), true);
			
					foreach ($templateFieldsForThisRow as $id => &$field) {
					
						//Allow the caller to pre-populate the values of the fields in the $values array.
						//If they have been put in there they won't be references as usual. So we'll pick them up
						//and put them in the fields array, so that \ze\tuix::readValues() will turn them
						//into references later.
						if (isset($values[$tabName. '/'. $id])) {
							$field['value'] = $values[$tabName. '/'. $id];
						
							if (!$filling) {
								$field['current_value'] = $values[$tabName. '/'. $id];
							}
						}
						//Same for buttons
						if (!empty($fields[$tabName. '/'. $id])) {
							foreach ($fields[$tabName. '/'. $id] as $prop => $val) {
								$field[$prop] = $val;
							}
						}
					
						if ($setGrouping !== false) {
							$field['grouping'] = $setGrouping;
						}
					
						$tab['fields'][$id] = $field;
					
					}
					unset($field);
					$changed = true;
				
					if ($dupN !== false) {
						foreach ($fieldCodeNames as $fieldCodeName) {
							if ($fieldCodeName !== $deleteButtonCodeName
							 && $fieldCodeName !== $idFieldCodeName
							 && $fieldCodeName !== $dupFieldCodeName
							) {
								$copyName = str_replace('znz', $dupN, $fieldCodeName);
								$pstName = str_replace('znz', $n, $fieldCodeName);
							
								$attrs = [
									'value' => 'value',
									'current_value' => 'current_value',
									'pressed' => 'pressed',
									'hidden' => 'hidden',
									'_was_hidden_before' => '_was_hidden_before'
								];
								if (self::fieldIsReadonly($tab['fields'][$copyName])) {
									$attrs['current_value'] = 'value';
								}
							
								foreach ($attrs as $pasteVal => $copyVal) {
									if (isset($tab['fields'][$copyName][$copyVal])) {
										$tab['fields'][$pstName][$pasteVal] = $tab['fields'][$copyName][$copyVal];
									} else {
										unset($tab['fields'][$pstName][$pasteVal]);
									}
								}
							}
						}
					}
				}
			} else {
				if ($rowExists) {
					//Remove any rows that are past the limit of visible rows
					if (!isset($removeRows[$n])) {
						$removeRows[$n] = true;
					}
			
				} else {
					//When there are no more rows, and there are not supposed to be any more rows, stop looping
					break;
				}
			}
		
			++$n;
		}
	
	
		//Either hide or unset() any rows that were flagged to be removed
		foreach ($removeRows as $n => $deleteRow) {

			foreach ($fieldCodeNames as $fieldCodeName) {
				$fieldCodeName = str_replace('znz', $n, $fieldCodeName);
			
				if (isset($tab['fields'][$fieldCodeName])) {
					$tab['fields'][$fieldCodeName]['hidden'] = true;
				
					if ($deleteRow) {
						unset($tab['fields'][$fieldCodeName]);
						$changed = true;
					}
				}
			}
		
			if ($deleteRow) {
				--$numRows;
			}
		}
	
	
		//If we created and/or destroyed fields, we need to update the references
		if ($changed) {
			\ze\tuix::readValues($box, $fields, $values, $changes, $filling, $resetErrors = false);
		}
	
	
		//Do a final loop count of every row
		$activeRows = 0;
		$firstRow = 0;
		$lastRow = 0;
		for ($n = $firstN; $n - $firstN < $numRows; ++$n) {
			if (!$deleted = $deleteButtonCodeName && !empty($tab['fields'][str_replace('znz', $n, $deleteButtonCodeName)]['pressed'])) {
				++$activeRows;
			
				if ($firstRow === 0) {
					$firstRow = $n;
				}
				$lastRow = $n;
			}
		}
	
		return [
			'numRows' => $numRows,
			'activeRows' => $activeRows,
			'firstRow' => $firstRow,
			'lastRow' => $lastRow
		];
	}








	//Formerly "loadAllPluginSettings()"
	public static function loadAllPluginSettings(&$box, &$valuesInDB) {
		$valuesInDB = [];
		if (!empty($box['key']['instanceId'])) {
			$sql = "
				SELECT name, `value`
				FROM ". DB_PREFIX. "plugin_settings
				WHERE instance_id = ". (int) $box['key']['instanceId']. "
				  AND egg_id = ". (int) $box['key']['eggId'];
			$result = \ze\sql::select($sql);

			while($row = \ze\sql::fetchAssoc($result)) {
				$valuesInDB[$row['name']] = $row['value'];
			}
		}
	}


	//Sync updates from the client to the array stored on the server
	//Formerly "syncAdminBoxFromClientToServer()"
	public static function syncFromClientToServer(&$serverTags, &$clientTags, $key1 = false, $key2 = false, $key3 = false, $key4 = false, $key5 = false, $key6 = false) {
		
		if ($key1 === false) {
			if (isset($clientTags['from_client'])) {
				$serverTags['from_client'] = [];
				\ze\tuix::syncAllTagsToServer($serverTags['from_client'], $clientTags['from_client']);
			}
		}
		
		
		$keys = array_merge(\ze\ray::valuesToKeys(array_keys($serverTags)), \ze\ray::valuesToKeys(array_keys($clientTags)));
	
		foreach ($keys as $key0 => $dummy) {
			//Only allow certain tags in certain places to be merged in
			if (
				($key1 === false && \ze::in($key0, 'download', 'path', 'shake', 'tab', 'switchToTab') && ($type = 'value'))
			 || ($key1 === false && \ze::in($key0, '_sync', 'tabs') && ($type = 'array'))
				 || ($key2 === false && $key1 == '_sync' && \ze::in($key0, 'cache_dir', 'password', 'storage') && ($type = 'value'))
				 || ($key2 === false && $key1 == 'tabs' && ($type = 'array'))
					 || ($key3 === false && $key2 == 'tabs' && $key0 == '_was_hidden_before' && ($type = 'value'))
					 || ($key3 === false && $key2 == 'tabs' && \ze::in($key0, 'edit_mode', 'fields') && ($type = 'array'))
						 || ($key4 === false && $key3 == 'tabs' && $key1 == 'edit_mode' && $key0 == 'on' && ($type = 'value'))
						 || ($key4 === false && $key3 == 'tabs' && $key1 == 'fields' && ($type = 'array'))
							 || ($key5 === false && $key4 == 'tabs' && $key2 == 'fields' && \ze::in($key0, '_display_value', '_was_hidden_before', 'current_value', 'pressed') && ($type = 'value'))
							 || ($key5 === false && $key4 == 'tabs' && $key2 == 'fields' && $key0 == 'multiple_edit' && ($type = 'array'))
								 || ($key6 === false && $key5 == 'tabs' && $key3 == 'fields' && $key1 == 'multiple_edit' && $key0 == '_changed' && ($type = 'value'))
			) {
			
				//Update any values from the client on the server's copy
				if ($type == 'value') {
				
					//Security check - don't allow read-only fields to be changed
					if (($key0 === 'current_value' || $key0 === 'pressed') && (self::fieldIsReadonly($serverTags))) {
						continue;
					}
				
					if (!isset($clientTags[$key0])) {
						unset($serverTags[$key0]);
					} else {
						$serverTags[$key0] = \ze\tuix::deTilde($clientTags[$key0]);
					}
			
				//For arrays, check them recursively
				} elseif ($type == 'array') {
					if (isset($serverTags[$key0]) && is_array($serverTags[$key0])
					 && isset($clientTags[$key0]) && is_array($clientTags[$key0])) {
						\ze\tuix::syncFromClientToServer($serverTags[$key0], $clientTags[$key0], $key0, $key1, $key2, $key3, $key4, $key5);
					}
				}
			}
		}
	}

	//This function is similar to the above, except it is specifically coded to handle the "from_client" object,
	//where everything is allowed through
	public static function syncAllTagsToServer(&$serverTags, &$clientTags) {
		
		foreach ($clientTags as $key0 => &$val) {
			if (is_array($clientTags[$key0])) {
				$serverTags[$key0] = [];
				\ze\tuix::syncAllTagsToServer($serverTags[$key0], $clientTags[$key0]);
			} else {
				$serverTags[$key0] = \ze\tuix::deTilde($clientTags[$key0]);
			}
		}
	}
	
	//Remove any tidles that might have been added by the syncAdminBoxFromClientToServerR() function
	public static function deTilde($var) {
		if (is_string($var)
		 && isset($var[0])
		 && $var[0] === '~') {
			return \ze\ring::decodeIdForOrganizer($var);
			//N.b. Cloudflare sometimes blocks the values of strings in JSON objects, e.g. if it sees HTML code in them.
			//We're attempting to work around this by calling encodeItemIdForOrganizer() to mask any HTML,
			//so we need to decode that encoding here.
		}
		return $var;
	}

	//Sync updates from the server to the array stored on the client
	//Formerly "syncAdminBoxFromServerToClient()"
	public static function syncFromServerToClient($serverTags, $clientTags, &$output) {
	
		$keys = \ze\ray::valuesToKeys(array_keys($serverTags));
		foreach ($clientTags as $key0 => &$dummy) {
			$keys[$key0] = true;
		}
	
		foreach ($keys as $key0 => &$dummy) {
			if (!isset($serverTags[$key0])) {
				$output[$key0] = ['[[__unset__]]' => true];
		
			} else
			if (!isset($clientTags[$key0])
			 && isset($serverTags[$key0])) {
				$output[$key0] = $serverTags[$key0];
		
			} else
			if (!is_array($clientTags[$key0])
			 && is_array($serverTags[$key0])) {
				$output[$key0] = $serverTags[$key0];
				$output[$key0]['[[__replace__]]'] = true;
		
			} else
			if (!is_array($serverTags[$key0])) {
				if ($clientTags[$key0] !== $serverTags[$key0]) {
					$output[$key0] = $serverTags[$key0];
				}
			} else {
				$output[$key0] = [];
				\ze\tuix::syncFromServerToClient($serverTags[$key0], $clientTags[$key0], $output[$key0]);
			
				if (empty($output[$key0])) {
					unset($output[$key0]);
				}
			}
		}
	}

	//Bypass the rest of the script in admin_boxes.ajax.php, and go to a new URL
	//(or whatever other flags are set) straight away.
	//Possible flags are:
		//['close_with_message' => $message]
		//['reload_organizer' => true]
		//['open_admin_box' => $path]
		//['go_to_url' => $url]
	//Formerly "closeFABWithFlags()"
	public static function closeWithFlags($flags) {
	
		$tags = [
			'_sync' => [
				'flags' => $flags
			]
		];
		header('Content-Type: text/javascript; charset=UTF-8');
		\ze\ray::jsonDump($tags);
		exit;

	}
	
	public static $feaDebugMode = false;
	public static $feaSelectQuery = false;
	public static $feaSelectCountQuery = false;

	//Formerly "displayDebugMode()"
	public static function displayDebugMode(&$tags, &$modules, &$moduleFilesLoaded, $tagPath, $queryIds = false, $queryFullSelect = false, $querySelectCount = false) {
	
		$modules_loaded = [];
		if (!empty($modules)) {
			$modules_loaded = array_keys($modules);
		}
	
		$tags = [
			'tuix' => $tags,
			'tag_path' => substr($tagPath, 1),
			'modules_loaded' => $modules_loaded,
			'modules_files_loaded' => $moduleFilesLoaded,
			'query_ids' => $queryIds,
			'query_full_select' => $queryFullSelect,
			'query_select_count' => $querySelectCount
		];
	
		header('Content-Type: text/javascript; charset=UTF-8');
		\ze\ray::jsonDump($tags);
		exit;
	}


	//For using encrypted columns in Organizer. Work in progress.
	//Formerly "flagEncryptedColumnsInOrganizer()"
	public static function flagEncryptedColumns(&$panel, $alias, $table) {
	
		$tableName = DB_PREFIX. $table;
		$tableAlias = $alias. '.';
	
		if (!isset(\ze::$dbL->cols[$tableName])) {
			\ze::$dbL->checkTableDef($tableName);
		}
	
		$encryptedColumns = [];
		if (!empty(\ze::$dbL->cols[$tableName])) {
			foreach (\ze::$dbL->cols[$tableName] as $col => $colDef) {
				if ($colDef->encrypted) {
					$encryptedColumns[$col] = $colDef;
				}
			}
		}
	
		if (!empty($encryptedColumns)) {
			foreach ($panel['columns'] as &$column) {
			
				if (isset($column['db_column'])) {
					$colName = trim(\ze\ring::chopPrefix($tableAlias, trim($column['db_column'])), '`');
				
					if (isset($encryptedColumns[$colName])) {
						$colDef = $encryptedColumns[$colName];
				
						$column['db_column'] = $tableAlias. "`%". \ze\escape::sql($colDef->col). "`";
						$column['encrypted'] = [
							'hashed_column' => $tableAlias. "`#". \ze\escape::sql($colDef->col). "`",
							'hashed' => $colDef->hashed
						];
				
						$column['disallow_sorting'] = true;
				
						if (!$colDef->hashed) {
							$column['searchable'] = false;
							//$column['disallow_filtering'] = true;
						}
					}
				}
			}
		}
	}
	
	
	public static function setupPluginFABKey(&$key, &$module, &$instance, &$egg) {
		
		$isInNest = !empty($_GET['refiner__nest']);
		
		//Editing an egg in a nest
		if (($eggId = (int) \ze::get('eggId'))
		 || ($isInNest && ($eggId = (int) \ze::get('id')))) {
			
			if (($egg = \ze\pluginAdm::getNestDetails($eggId))
			 && ($instance = \ze\plugin::details($egg['instance_id']))
			 && ($module = \ze\module::details($egg['module_id']))) {
			} else {
				echo \ze\admin::phrase('This plugin could not be found.');
				exit;
			}
			
			$key['moduleId'] = $egg['module_id'];
			$key['instanceId'] = $egg['instance_id'];
			$key['eggId'] = $eggId;
			$key['slideNum'] = $egg['slide_num'];
			$key['isNest'] = (bool) $instance['is_nest'];
			$key['isSlideshow'] = (bool) $instance['is_slideshow'];
			$key['framework'] = $egg['framework'] ?: $module['default_framework'];
		
		} else {
			//Attempt to get the instance id
			$instanceId = (int) \ze::get('instanceId') ?: ((int) \ze::get('refiner__nest') ?: (int) \ze::get('id'));
	
			//Attempt to get the module id
			$moduleId = (int) \ze::get('moduleId') ?: (int) \ze::get('refiner__plugin');
		
			//Editing an existing plugin
			if ($instanceId) {
				$module = $instance = \ze\plugin::details($instanceId);
				$egg = [];
				
				if (!$instance) {
					echo \ze\admin::phrase('This plugin could not be found.');
					exit;
				}
			
				$key['moduleId'] = $instance['module_id'];
				$key['instanceId'] = $instanceId;
				$key['eggId'] = 0;
				$key['isNest'] = (bool) $instance['is_nest'];
				$key['isSlideshow'] = (bool) $instance['is_slideshow'];
				$key['framework'] = $instance['framework'] ?: $module['default_framework'];
		
			//Creating a new plugin
			} else {
				$module = \ze\module::details($moduleId);
				
				if (!$module) {
					echo \ze\admin::phrase('This module could not be found.');
					exit;
				}
				
				$instance = ['framework' => $module['default_framework'], 'css_class' => ''];
				$egg = [];
			
				$key['moduleId'] = $moduleId;
				$key['instanceId'] = 0;
				$key['eggId'] = 0;
				$key['isSlideshow'] = $module['class_name'] == 'zenario_slideshow';
				$key['isNest'] = $key['isSlideshow'] || $module['class_name'] == 'zenario_plugin_nest';
				$key['framework'] = $module['default_framework'];
			}
		}
		
		if ($module['status'] == 'module_suspended'
		 || $module['status'] == 'module_not_initialized') {
			echo \ze\admin::phrase('This module is not running.');
			exit;
		}
		
		$key['moduleClassName'] = $module['class_name'];
		
		if ($key['instanceId']) {
			$key['mode'] =
				\ze\row::get('plugin_settings', 'value', [
					'name' => 'mode',
					'instance_id' => $key['instanceId'],
					'egg_id' => $key['eggId']
				]);
		}
	}






	public static function visitorTUIX($owningModule, $requestedPath, &$tags, $filling = true, $validating = false, $saving = false, $debugMode = false) {
		
		////Exit if no path is specified
		//if (!$requestedPath) {
		//	echo 'No path specified!';
		//	exit;
		//}
		//
		////Check to see if this path is allowed.
		//} else if (!$owningModule->returnVisitorTUIXEnabled($requestedPath)) {
		//	echo 'You do not have access to this plugin in this mode, or the plugin settings are incomplete.';
		//	exit;
		//}
		
		\ze::$tuixType = $type = 'visitor';
		\ze::$tuixPath = $requestedPath;
		
		$settingGroup = '';
		$tags = [];
		$originalTags = [];
		$moduleFilesLoaded = [];
		\ze\tuix::load($moduleFilesLoaded, $tags, $type, $requestedPath);
		
		if (empty($tags[$requestedPath])) {
			
			$paths = [];
			foreach ($moduleFilesLoaded as $mfl) {
				if (!empty($mfl['paths'])) {
					$paths = array_merge($paths, $mfl['paths']);
				}
			}
			
			echo 'Could not find the tag-path "', $requestedPath, '". The following file(s) were searched: ', implode(', ', $paths);
			exit;
		}
		$tags = $tags[$requestedPath];
		$clientTags = false;
		
		$useSync = \ze\tuix::usesSync($tags);
		
		\ze\tuix::checkTUIXForm($tags);
		
		//Work out which modules interact with this path of FEA.
		//The module that owns the FEA should be in the list, of course
		$modules = [$owningModuleClassName = $owningModule->returnClassName() => $owningModule];
		$extendingModules = [];
		
		//Check to see if any modules are extending the FEA with their own buttons or columns.
		//Add them to the list
		foreach (['collection_buttons', 'item_buttons', 'columns'] as $buttonType) {
			if (!empty($tags[$buttonType]) && is_array($tags[$buttonType])) {
				foreach ($tags[$buttonType] as &$button) {
					if (is_array($button) && !empty($button['class_name']) && $button['class_name'] != $owningModuleClassName) {
						\ze\tuix::includeModule($modules, $button, $type, $requestedPath, $settingGroup);
						$extendingModules[$button['class_name']] = true;
					}
				}
				unset($button);
			}
		}
		
		if ($extendingModules !== []) {
			$owningModule->zAPISetExtendingModules($extendingModules);
		}
		
		//For TUIX forms, check each tab & field as well
		if (\ze\tuix::looksLikeFAB($tags)) {
			foreach ($tags['tabs'] as &$tab) {
				if (!empty($tab['class_name'])) {
					\ze\tuix::includeModule($modules, $tab, $type, $requestedPath, $settingGroup);
				}
		
				if (!empty($tab['fields']) && is_array($tab['fields'])) {
					foreach ($tab['fields'] as &$field) {
						if (!empty($field['class_name'])) {
							\ze\tuix::includeModule($modules, $field, $type, $requestedPath, $settingGroup);
						}
					}
				}
			}
		}
		
		
		
		
		
		
		
		//Debug mode - show the TUIX before it's been modified
		if ($debugMode) {
			$staticTags = $tags;
		
		
			if (!\ze\tuix::looksLikeFAB($tags)) {
				//Logic for initialising an Admin Box
				if (!empty($tags['key']) && is_array($tags['key'])) {
					foreach ($tags['key'] as $key => &$value) {
						if (!empty($_REQUEST[$key])) {
							$value = $_REQUEST[$key];
						}
					}
				}
				\ze\tuix::$feaDebugMode = true;
				foreach ($modules as $className => &$module) {
					$module->fillVisitorTUIX($requestedPath, $tags, $fields, $values);
				}
			}
		
		
			\ze\tuix::displayDebugMode($staticTags, $modules, $moduleFilesLoaded, $tagPath = $requestedPath, false, \ze\tuix::$feaSelectQuery, \ze\tuix::$feaSelectCountQuery);
			exit;
		}
	
		$doSave = false;
		if ($filling) {
			//Logic for initialising an Admin Box
			if (!empty($tags['key']) && is_array($tags['key'])) {
				foreach ($tags['key'] as $key => &$value) {
					if (!empty($_REQUEST[$key])) {
						$value = $_REQUEST[$key];
					}
				}
			}
		
			$fields = [];
			$values = [];
			$changes = [];
			if (\ze\tuix::looksLikeFAB($tags)) {
				\ze\tuix::readValues($tags, $fields, $values, $changes, $filling, $resetErrors = false);
			}
			
			foreach ($modules as $className => &$module) {
				$module->fillVisitorTUIX($requestedPath, $tags, $fields, $values);
			}
	
	
		} else {
			$clientTags = json_decode($_POST['_tuix'], true);
	
			\ze\tuix::loadCopyFromServer($tags, $clientTags);
		
			\ze\tuix::syncFromClientToServer($tags, $clientTags);
		
			if ($useSync) {
				$originalTags = $tags;
			}
		
			if ($validating || $saving) {
				unset($tags['_error_on_tab']);
	
				$fields = [];
				$values = [];
				$changes = [];
				if (\ze\tuix::looksLikeFAB($tags)) {
					\ze\tuix::readValues($tags, $fields, $values, $changes, $filling, $resetErrors = true, $checkLOVs = true);
				
					foreach ($tags['tabs'] as $tabName => &$tab) {
						\ze\tuix::applyValidation($tab, $saving);
					}
				}
				
				foreach ($modules as $className => &$module) {
					$module->validateVisitorTUIX($requestedPath, $tags, $fields, $values, $changes, $saving);
				}
			
			
				if ($saving) {
					//Check if there are any errors. If so, stop the save, and switch to the tab with the errors
					$doSave = true;
					if (\ze\tuix::looksLikeFAB($tags)) {
						foreach ($tags['tabs'] as $tabName => &$tab) {
							if (!empty($tab['errors'])) {
								$doSave = false;
								$tags['tab'] = $tabName;
								break;
							}
					
							if (!empty($tab['fields']) && is_array($tab['fields'])) {
								foreach ($tab['fields'] as &$field) {
									if (!empty($field['error'])) {
										$doSave = false;
										$tags['tab'] = $tabName;
										break 2;
									}
								}
							}
						}
					}
				
					if ($doSave) {
						$fields = [];
						$values = [];
						$changes = [];
						if (\ze\tuix::looksLikeFAB($tags)) {
							\ze\tuix::readValues($tags, $fields, $values, $changes, $filling, $resetErrors = false);
						}
						
						foreach ($modules as $className => &$module) {
							$module->saveVisitorTUIX($requestedPath, $tags, $fields, $values, $changes);
						}
					}
				}
			}
		
		}
	
		if (!$doSave) {
			$fields = [];
			$values = [];
			$changes = [];
			if (\ze\tuix::looksLikeFAB($tags)) {
				\ze\tuix::readValues($tags, $fields, $values, $changes, $filling, $resetErrors = false, $checkLOVs = false, $addOrds = true);
			}
			
			foreach ($modules as $className => &$module) {
				$module->formatVisitorTUIX($requestedPath, $tags, $fields, $values, $changes);
			}
		}
	
		if ($useSync) {
			//Try to save a copy of the admin box in the cache directory
			\ze\tuix::saveCopyOnServer($tags);
		
			if (!empty($originalTags)) {
				$output = [];
				\ze\tuix::syncFromServerToClient($tags, $originalTags, $output);
	
				$tags = $output;
			}
		}
		
		\ze::$tuixPath = '';
	}
	
	
	//JSON encode some TUIX, and also apply some common replacements as a simple way to try and get the size down a bit
	public static function stringify($tags) {
		return str_replace([
			'%',
			
			'":false',
			'":true',
			'"pre_field_html":',
			'"post_field_html":',
			'"redraw_immediately_onchange":',
			'"redraw_onchange":',
			'"hide_if_previous_value_isnt":',
			'"hide_when_children_are_not_visible":',
			'"hide_with_previous_field":',
			'"ajax":',
			'"confirm":',
			'"css_class":',
			'"disabled":',
			'"hidden":',
			'"label":',
			'"last_edited":',
			'"message":',
			'"name":',
			'"onclick":',
			'"ord":',
			'"parent":',
			'"row_class":',
			'"title":',
			'"tooltip":',
			'"visible_if":',
			
			'"enable_microtemplates_in_properties":',
			'"grouping":',
			'"placeholder":',
			'"snippet":',
			'"value":',
			'"empty_value":',
			'"tuix":',
			'"type":',
			'"readonly":',
			
			'~', '"', '+', ':'
		], [
			'%C',
			
			'%0',
			'%1',
			'%2',
			'%3',
			'%4',
			'%5',
			'%7',
			'%8',
			'%9',
			'%a',
			'%f',
			'%c',
			'%d',
			'%h',
			'%l',
			'%e',
			'%m',
			'%n',
			'%k',
			'%o',
			'%p',
			'%r',
			'%i',
			'%t',
			'%v',
			
			'%b',
			'%g',
			'%q',
			'%s',
			'%u',
			'%w',
			'%x',
			'%y',
			'%z',
			
			'%S', '~', '%P', '+'
		], json_encode($tags, JSON_FORCE_OBJECT));
	}
	
	//Reverse of the above, if ever needed
	//public static function parse($json) {
	//	return json_decode(str_replace([
	//		'~', '%S', '+', '%P',
	//		
	//		'%0',
	//		'%1',
	//		'%2',
	//		'%3',
	//		'%4',
	//		'%5',
	//		'%7',
	//		'%8',
	//		'%9',
	//		'%a',
	//		'%f',
	//		'%c',
	//		'%d',
	//		'%h',
	//		'%l',
	//		'%e',
	//		'%m',
	//		'%n',
	//		'%k',
	//		'%o',
	//		'%p',
	//		'%r',
	//		'%i',
	//		'%t',
	//		'%v',
	//		
	//		'%b',
	//		'%g',
	//		'%q',
	//		'%s',
	//		'%u',
	//		'%w',
	//		'%x',
	//		'%y',
	//		'%z',
	//		
	//		'%C'
	//	], [
	//		'"', '~', ':', '+',
	//		
	//		 '":false',
	//		'":true',
	//		'"pre_field_html":',
	//		'"post_field_html":',
	//		'"redraw_immediately_onchange":',
	//		'"redraw_onchange":',
	//		'"hide_if_previous_value_isnt":',
	//		'"hide_when_children_are_not_visible":',
	//		'"hide_with_previous_field":',
	//		'"ajax":',
	//		'"confirm":',
	//		'"css_class":',
	//		'"disabled":',
	//		'"hidden":',
	//		'"label":',
	//		'"last_edited":',
	//		'"message":',
	//		 '"name":',
	//		'"onclick":',
	//		'"ord":',
	//		'"parent":',
	//		'"row_class":',
	//		'"title":',
	//		'"tooltip":',
	//		'"visible_if":',
	//		
	//		'"enable_microtemplates_in_properties":',
	//		'"grouping":',
	//		'"placeholder":',
	//		'"snippet":',
	//		'"value":',
	//		'"empty_value":',
	//		'"tuix":',
	//		'"type":',
	//		'"readonly":',
	//		
	//		'%'
	//	], $json), true);
	//}
}

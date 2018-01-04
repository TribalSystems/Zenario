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







//A recursive function that comes up with a list of all of the Organizer paths that a TUIX file references
function logTUIXFileContentsR(&$paths, &$tags, $type, $path = '') {
	
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
			logTUIXFileContentsR($paths, $tag, $type, $thisPath);
		}
	}
}






//This function scans the Module directory for Modules with certain TUIX files, reads them, and turns them into a php array
	//You should initialise $modules and $tags to empty arrays before calling this function.
function loadTUIX(
	&$modules, &$tags, $type, $requestedPath = '', $settingGroup = '', $compatibilityClassNames = false,
	$runningModulesOnly = true, $exitIfError = true
) {
	$modules = array();
	$tags = array();
	
	//Ensure that the core plugin is included first, if it is there...
	$modules['zenario_common_features'] = array();
	
	if ($type == 'welcome') {
		foreach (scandir($dir = CMS_ROOT. 'zenario/admin/welcome/') as $file) {
			if (substr($file, 0, 1) != '.') {
				$tagsToParse = zenarioReadTUIXFile($dir. $file);
				zenarioParseTUIX($tags, $tagsToParse, 'welcome');
				unset($tagsToParse);
			}
		}
	
	} else {
		//Try to check the tuix_file_contents table to see which files we need to include
		foreach (modulesAndTUIXFiles(
			$type, $requestedPath, $settingGroup, true, true, $compatibilityClassNames, $runningModulesOnly
		) as $module) {
			if (empty($modules[$module['class_name']])) {
				setModulePrefix($module);
				
				$modules[$module['class_name']] = array(
					'class_name' => $module['class_name'],
					'depends' => getModuleDependencies($module['class_name']),
					'included' => false,
					'files' => array());
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
			if ($dir = moduleDir($module['class_name'], 'tuix/'. $type. '/', true)) {
				
				if (!isset($module['files'])) {
					$module['files'] = array();
					
					foreach (scandir($dir) as $file) {
						if (substr($file, 0, 1) != '.') {
							$module['files'] = scandir($dir);
						}
					}
				}
				
				foreach ($module['files'] as $file) {
					$tagsToParse = zenarioReadTUIXFile($dir. $file);
					zenarioParseTUIX($tags, $tagsToParse, $type, $className, $settingGroup, $compatibilityClassNames, $requestedPath);
					unset($tagsToParse);
					
					if (!isset($module['paths'])) {
						$module['paths'] = array();
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
			echo adminPhrase('The requested path "[[path]]" was not found in the system. If you have just updated or added files to the CMS, you will need to reload the page.', array('path' => $requestedPath));
			exit;
		} else {
			$tags = array();
		}
	}
	$tags = $tags[$type];
}


function zenarioReadTUIXFileR(&$tags, &$xml) {
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
			$tags[$key] = array();
		}
		
		zenarioReadTUIXFileR($tags[$key], $child);
		
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

	//$thisTags = zenarioReadTUIXFile($path, $moduleClassName);

function zenarioParseTUIX(&$tags, &$par, $type, $moduleClassName = false, $settingGroup = '', $compatibilityClassNames = array(), $requestedPath = false, $tag = '', $path = '', $goodURLs = array(), $level = 0, $ord = 1, $parent = false, $parentsParent = false, $parentsParentsParent = false) {
	
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
			}
			
			if ($addClass) {
				$tags[$tag] = array('class_name' => $moduleClassName);
			}
		}
	}
	
	
	//Recursively scan each child-tag
	$children = 0;
	if (is_array($par)) {
		
		if ($goFurther && (!isset($tags[$tag]) || !is_array($tags[$tag]))) {
			$tags[$tag] = array();
		}
		
		foreach ($par as $key => &$child) {
			++$children;
			$isEmptyArray = true;
			
			if ($goFurther) {
				if (zenarioParseTUIX($tags[$tag], $child, $type, $moduleClassName, $settingGroup, $compatibilityClassNames, $requestedPath, $key, $path, $goodURLs, $level + 1, $children, $tag, $parent, $parentsParent)) {
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
function zenarioParseTUIX2(&$tags, &$removedColumns, $type, $requestedPath = '', $mode = false, $path = '', $parentKey = false, $parentParentKey = false, $parentParentParentKey = false) {
	
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
	$noPrivs = array();
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
				if (!checkPriv((string) $value)) {
					return false;
				}
			
			} elseif ((string) $key == 'local_admins_only') {
				if (engToBoolean($value) && ($_SESSION['admin_global_id'] ?? false)) {
					return false;
				}
			
			} elseif ((string) $key == 'superadmins_only') {
				if (engToBoolean($value) && !($_SESSION['admin_global_id'] ?? false)) {
					return false;
				}
			
			} elseif (!zenarioParseTUIX2($value, $removedColumns, $type, $mode, $requestedPath, $path, (string) $key, $parentKey, $parentParentKey)) {
				$noPrivs[] = $key;
			}
		}
		
		foreach($noPrivs as $key) {
			unset($tags[$key]);
		}
		unset($tags['priv']);
		
		if ($orderItems) {
			addOrdinalsToTUIX($tags);
		}
		
		//Don't send any SQL to the client
		if ($type == 'organizer') {
			if ($parentKey === false || $parentKey == 'panel' || $parentParentKey = 'panels') {
				if (!adminSetting('show_dev_tools')) {
					
					if (isset($tags['db_items']) && is_array($tags['db_items'])) {
						unset($tags['db_items']['table']);
						unset($tags['db_items']['id_column']);
						unset($tags['db_items']['group_by']);
						unset($tags['db_items']['where_statement']);
					}
					
					if (isset($tags['columns']) && is_array($tags['columns'])) {
						foreach ($tags['columns'] as &$col) {
							if (is_array($col)) {
								if (!empty($col['db_column'])) {
									$col['db_column'] = true;
								}
								unset($col['search_column']);
								unset($col['sort_column']);
								unset($col['sort_column_desc']);
								unset($col['table_join']);
							}
						}
					}
					
					if (isset($tags['refiners']) && is_array($tags['refiners'])) {
						foreach ($tags['refiners'] as &$refiner) {
							if (is_array($refiner)) {
								unset($refiner['sql']);
								unset($refiner['sql_when_searching']);
								unset($refiner['table_join']);
								unset($refiner['table_join_when_searching']);
								unset($refiner['allow_unauthenticated_xml_access']);
							}
						}
					}
				}
			
			} elseif (($parentParentKey === false || $parentParentKey == 'panel' || $parentParentParentKey == 'panels') && $parentKey == 'columns') {
				//If this is a Organizer request for a specific panel, get a list of columns for that
				//panel that are server side only, so that we can later remove these from the output.
				if ($path == $requestedPath. '/columns') {
					$removedColumns = array();
					foreach ($tags as $key => &$value) {
						if (is_array($value) && engToBoolean($value['server_side_only'] ?? false)) {
							$removedColumns[] = $key;
						}
					}
				}
			}
		}
	}
	
	
	return true;
}




function sortTUIX(&$tags) {
	if (is_array($tags)) {
		uasort($tags, 'sortTUIXCompare');
	}
}

function sortTUIXCompare($a, $b) {
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


function sortCompareByLabel($a, $b) {
	if ($a['label'] == $b['label']) {
		return 0;
	}
	return ($a['label'] < $b['label']) ? -1 : 1;
}

function addOrdinalsToTUIX(&$tuix) {
	
	//Loop through an array of TUIX elements, inserting any missing ordinals
	$ord = 0;
	$previousGrouping = null;
	$replaces = array();
	if (is_array($tuix)) {
		foreach ($tuix as $key => &$tag) {
			if (is_array($tag)) {
				if (!isset($tag['ord'])) {
					$tag['ord'] = ++$ord;
				
				//If the ordinal is a string, attempt to parse it
				} elseif (!is_numeric($tag['ord'])) {
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



//Include a Module
function zenarioAJAXIncludeModule(&$modules, &$tag, $type, $requestedPath, $settingGroup) {

	if (!empty($modules[$tag['class_name']])) {
		return true;
	} elseif (inc($tag['class_name']) && ($module = activateModule($tag['class_name']))) {
		$modules[$tag['class_name']] = $module;
		return true;
	} else {
		return false;
	}
}

function TUIXLooksLikeFAB(&$tags) {
	return !empty($tags['tabs']) && is_array($tags['tabs']);
}

function TUIXIsFormField(&$field) {
	
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

function saveCopyOfTUIXOnServer(&$tags) {

	//Try to save a copy of the admin box in the cache directory
	if (($adminBoxSyncStoragePath = adminBoxSyncStoragePath($tags))
	 && (@file_put_contents($adminBoxSyncStoragePath, adminBoxEncodeTUIX($tags)))) {
		@chmod($adminBoxSyncStoragePath, 0666);
		$tags['_sync']['session'] = false;

	//Fallback code to store in the session
	} else {
		if (empty($_SESSION['admin_box_sync'])) {
			$_SESSION['admin_box_sync'] = array(0 => 0); //I want to start counting from 1 so the key is not empty
		}
	
		if (empty($tags['_sync']['session']) || empty($_SESSION['admin_box_sync'][$tags['_sync']['session']])) {
			$tags['_sync']['session'] = count($_SESSION['admin_box_sync']);
		}
	
		$_SESSION['admin_box_sync'][$tags['_sync']['session']] = adminBoxEncodeTUIX($tags);
		$tags['_sync']['cache_dir'] = false;
	}
}

function loadCopyOfTUIXFromServer(&$tags, &$clientTags) {

	//Attempt to pick the right box and load from the Storage
		//(This may be in the cache directory or the session, depending on whether the cache was writable)
	if (($adminBoxSyncStoragePath = adminBoxSyncStoragePath($clientTags))
	 && (file_exists($adminBoxSyncStoragePath))
	 && (adminBoxDecodeTUIX($tags, $clientTags, file_get_contents($adminBoxSyncStoragePath)))) {
	
	} else
	if (!empty($clientTags['_sync']['session'])
	 && !empty($_SESSION['admin_box_sync'][$clientTags['_sync']['session']])
	 && (adminBoxDecodeTUIX($tags, $clientTags, $_SESSION['admin_box_sync'][$clientTags['_sync']['session']]))) {
	
	} else {
		if (!empty($clientTags['_sync']['session']) || !setting('fab_use_cache_dir')) {
			echo adminPhrase('An error occurred when syncing this form with the server. There is a problem with the server\'s $_SESSION variable.');
		
		} else {
			echo adminPhrase('An error occurred when syncing this form with the server. A file placed in the cache/ directory could not be found.');
		}
		exit;
	}
}


function adminBoxSyncStoragePath(&$box) {
	
	if (!setting('fab_use_cache_dir')) {
		return false;
	}
	
	if (empty($box['key'])) {
		$box['key'] = array();
	}
	
	if (empty($box['_sync'])) {
		$box['_sync'] = array();
	}
	
	if (empty($box['_sync']['cache_dir'])
	 || !is_dir(CMS_ROOT. 'cache/fabs/'. preg_replace('/[^\\w-]/', '', $box['_sync']['cache_dir']))) {
		$box['_sync']['cache_dir'] =
			createRandomDir(
				8, $type = 'cache/fabs/', false, false,
				$prefix = 'ab_'. hash64(json_encode($box), 8). '_');
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
function adminBoxEncodeTUIX(&$tags) {
		
	//Strip out all user-entered values before we save a copy of this admin box, for security reasons
		//N.b. be aware that due to the quirks of PHP, when you create a reference to an array inside
		//an array (as the readAdminBoxValues() function does), the array you are targeting itself gets
		//replaced by a reference.
		//Because references are involved, we can't simply create a copy of the array!
	$currentValues = array();
	if (!empty($tags['tabs'])
	 && is_array($tags['tabs'])) {
		
		foreach ($tags['tabs'] as $tabName => &$tab) {
			
			if (!empty($tab['fields'])
			 && is_array($tab['fields'])) {
				
				$currentValues[$tabName] = array();
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
			$box['_sync'] = array();
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
function adminBoxDecodeTUIX(&$tags, &$clientTags, $string) {
	if (function_exists('openssl_encrypt') && !empty($clientTags['_sync']['password'])) {
		$iv = '';
		if (!empty($clientTags['_sync']['iv'])) {
			$iv = $clientTags['_sync']['iv'];
		}
		$string = openssl_decrypt($string, 'aes128', base64_decode($clientTags['_sync']['password']), 0, base64_decode($iv));
	}
	
	return ($tags = json_decode($string, true)) && (is_array($tags));
}

function readAdminBoxValues(&$box, &$fields, &$values, &$changes, $filling, $resetErrors, $checkLOVs = false, $addOrds = false) {
	
	if (!empty($box['tabs']) && is_array($box['tabs'])) {
		
		if ($addOrds) {
			addOrdinalsToTUIX($box['tabs']);
			
			if (!empty($box['lovs']) && is_array($box['lovs'])) {
				foreach ($box['lovs'] as &$lov) {
					addOrdinalsToTUIX($lov);
				}
			}
		}
		
		foreach ($box['tabs'] as $tabName => &$tab) {
			if (is_array($tab) && !empty($tab['fields']) && is_array($tab['fields'])) {
				
				if ($addOrds) {
					addOrdinalsToTUIX($tab['fields']);
				}
				if ($resetErrors || !isset($tab['errors']) || !is_array($tab['errors'])) {
					$tab['errors'] = array();
				}
				
				$unsets = array();
				foreach ($tab['fields'] as $fieldName => &$field) {
					//Remove anything that's not an array to stop bad code causing bugs
					if (!is_array($field)) {
						$unsets[] = $fieldName;
						continue;
					}
					
					//Only check fields that are actually fields
					$isField = 
						!empty($field['upload'])
					 || !empty($field['pick_items'])
					 || (!empty($field['type']) && $field['type'] != 'submit' && $field['type'] != 'toggle' && $field['type'] != 'button');

					
					if ($addOrds && !empty($field['values']) && is_array($field['values'])) {
						addOrdinalsToTUIX($field['values']);
					}
					if ($resetErrors) {
						unset($field['error']);
					}
					
					if ($isField) {
						//Fields in readonly mode should use ['value'] as their value;
						//fields not in readonly mode should use ['current_value'].
						$readOnly =
							$filling
						 || !engToBoolean($tab['edit_mode']['on'] ?? false)
						 || engToBoolean($field['read_only'] ?? false)
						 || engToBoolean($field['readonly'] ?? false)
						 || engToBoolean($field['disabled'] ?? false);
						
						$currentValue = $readOnly? 'value' : 'current_value';
						
						if (isset($field['value']) && is_array($field['value'])) {
							unset($field['value']);
						}
						if (isset($field['current_value'])) {
							if (is_array($field['current_value']) || $readOnly) {
								unset($field['current_value']);
							
							} elseif (!$filling && $resetErrors) {
								if (empty($field['dont_trim']) || !engToBoolean($field['dont_trim'])) {
									$field['current_value'] = trim($field['current_value']);
								}
								if (!empty($field['maxlength']) && (int) $field['maxlength']) {
									$field['current_value'] = mb_substr($field['current_value'], 0, (int) $field['maxlength'], 'UTF-8');
								}
							}
						}
						
						if (!isset($field[$currentValue])) {
							$field[$currentValue] = '';
						
						//Make sure that checkboxes are either 0 or 1, and catch the case where zeros were
						//being treated as strings (which is bad because '0' == true in JavaScript).
						} elseif (isset($field['type']) && $field['type'] == 'checkbox') {
							$field[$currentValue] = engToBoolean($field[$currentValue]);
						
						//For upload files, try to look up details on any uploaded files
						//so save the client needing an AJAX request to do this.
						} elseif ($filling && $field[$currentValue] && !empty($field['upload'])) {
							foreach (explodeAndTrim($field[$currentValue], true) as $fileId) {
								
								if ($file = getRow('files', array('id', 'filename', 'width', 'height', 'checksum', 'usage'), $fileId)) {
									if (empty($field['values'])) {
										$field['values'] = array();
									}
									
									$field['values'][$file['id']] = array(
										'file' => $file,
										'label' => $file['filename']
									);
									
									if ($file['width'] && $file['height']) {
										$field['values'][$file['id']]['label'] .= ' ['. $file['width']. ' × '. $file['height']. 'px]';
									}
								}
							}
						
						//For radiogroups/multiple-checkboxes/select lists, check that the selected value(s) are actually in the LOV!
						} else
						if ($checkLOVs
						 && $field[$currentValue]
						 && isset($field['type'])
						 && !isset($field['load_values_from_organizer_path'])
						 && in($field['type'], 'radios', 'checkboxes', 'select')) {
							
							//Checkboxes can have multiple values, all of which must be checked.
							if ($field['type'] == 'checkboxes') {
								$checkValues = explodeAndTrim($field[$currentValue]);
							} else {
								$checkValues = array($field[$currentValue]);
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
							$changed = engToBoolean($field['multiple_edit']['changed'] ?? false);
						
						} else {
							$changed = engToBoolean($field['multiple_edit']['_changed'] ?? false);
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
						//Editor fields will need the Ze\File::addImageDataURIsToDatabase() run on them
						if (isset($field['current_value'])
						 && arrayKey($box, 'tabs', $tabName, 'fields', $fieldName, 'type')  == 'editor'
						 && !empty($box['tabs'][$tabName]['fields'][$fieldName]['insert_image_button'])) {
							//Convert image data urls to files in the database
							Ze\File::addImageDataURIsToDatabase($field['current_value'], absCMSDirURL());
						}
					}
				}
				if (!empty($unsets)) {
					foreach ($unsets as $unset) {
						unset($tab['fields'][$fieldName]);
					}
				}
			}
		}
	}
}

function applyValidationFromTUIXOnTab(&$tab) {
	//Loop through each field, looking for fields with validation set
	if (isset($tab['fields']) && is_array($tab['fields'])) {
		foreach ($tab['fields'] as $fieldName => &$field) {
			if (empty($field['validation'])) {
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
			} elseif (($msg = $field['validation']['required_if_not_hidden'] ?? false)
				   && !engToBoolean($tab['hidden'] ?? false) && !engToBoolean($field['hidden'] ?? false)
				   //&& !engToBoolean($tab['_was_hidden_before'] ?? false)
				   && !engToBoolean($field['_was_hidden_before'] ?? false)
				   && $notSet
			) {
				$field['error'] = $msg;
			
			//If a field was not required, do not run any further validation logic on it if it is empty 
			} elseif ($notSet) {
				continue;
			
			} elseif (($msg = $field['validation']['email'] ?? false) && !validateEmailAddress($fieldValue)) {
				$field['error'] = $msg;
			
			} elseif (($msg = $field['validation']['emails'] ?? false) && !validateEmailAddress($fieldValue, true)) {
				$field['error'] = $msg;
			
			} elseif (($msg = $field['validation']['no_spaces'] ?? false) && preg_replace('/\S/', '', $fieldValue)) {
				$field['error'] = $msg;
			
			} elseif (($msg = $field['validation']['numeric'] ?? false) && !is_numeric($fieldValue)) {
				$field['error'] = $msg;
			
			} elseif (($msg = $field['validation']['screen_name'] ?? false) && !validateScreenName($fieldValue)) {
				$field['error'] = $msg;
			
			} elseif (($msg = $field['validation']['no_special_characters'] ?? false) && !validateScreenName(str_replace(',', '', $fieldValue), true)) {
				$field['error'] = $msg;
			
			} else {
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
					foreach (explodeAndTrim($fieldValue) as $file) {
						
						//If this file has just been picked, we'll need to check it from the disk
						if ($filepath = Ze\File::getPathOfUploadedInCacheDir($file)) {
							$mimeType = Ze\File::mimeType($filepath);
						
						//Otherwise look for it in the files table
						} else {
							$mimeType = getRow('files', 'mime_type', $file);
						}
						
						$isIcon = in($mimeType, 'image/vnd.microsoft.icon', 'image/x-icon');
						$isGIFPNG = in($mimeType, 'image/gif', 'image/png');
						
						//Check all of the possible rules for image validation.
						//Stop checking image validation rules for this field as soon
						//as we find one picked file that doesn't match one rule
						if ($must_be_image && !Ze\File::isImage($mimeType)) {
							$field['error'] = $field['validation']['must_be_image'];
							break;
						
						} else
						if ($must_be_image_or_svg && !Ze\File::isImageOrSVG($mimeType)) {
							$field['error'] = $field['validation']['must_be_image_or_svg'];
							break;
						
						} else
						if ($must_be_gif_or_png && !$isGIFPNG) {
							$field['error'] = $field['validation']['must_be_gif_or_png'];
							break;
						
						} else
						if ($must_be_gif_ico_or_png && !($isGIFPNG || $isIcon)) {
							$field['error'] = $field['validation']['must_be_gif_ico_or_png'];
							break;
						
						} else
						if ($must_be_ico && !$isIcon) {
							$field['error'] = $field['validation']['must_be_ico'];
							break;
						}
					}
				}
			}
		}
	}
}


class zenario_fea_tuix {
	public static $yamlFilePath = -1;
}


function translatePhraseInTUIX(&$tag, &$overrides, $path, &$moduleClass, &$languageId, &$scan, $i = false, $j = false, $k = false) {
	
	if ($k !== false) {
		$phrase = &$tag[$i][$j][$k];
	} elseif ($j !== false) {
		$phrase = &$tag[$i][$j];
	} elseif ($i !== false) {
		$phrase = &$tag[$i];
	} else {
		$phrase = &$tag;
	}
	
	
	//Don't try and translate numbers, e.g. the hour/minute select list
	if (is_numeric($phrase)) {
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
		
		$phrase = phrase($phrase, false, $moduleClass, $languageId, zenario_fea_tuix::$yamlFilePath);
		//function phrase($code, $replace = array(), $moduleClass = 'lookup', $languageId = false, $backtraceOffset = 1) {
	}
}
	
function translatePhrasesInTUIXObject(&$t, &$o, &$p, &$c, &$l, &$s, $objectType = false) {
	
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
                    case 'no_items_message':
                    case 'item_count_message':
                    case 'title_for_existing_records':
                    case 'search_bar_placeholder':
                        translatePhraseInTUIX($t, $o, $p, $c, $l, $s, $i);
                    	break;
                }
            }
			
			if (isset($t[$i='confirm_on_close'][$j='title'])) translatePhraseInTUIX($t, $o, $p, $c, $l, $s, $i, $j);
			if (isset($t[$i='confirm_on_close'][$j='message'])) translatePhraseInTUIX($t, $o, $p, $c, $l, $s, $i, $j);
			if (isset($t[$i='confirm_on_close'][$j='button_message'])) translatePhraseInTUIX($t, $o, $p, $c, $l, $s, $i, $j);
			if (isset($t[$i='confirm_on_close'][$j='cancel_button_message'])) translatePhraseInTUIX($t, $o, $p, $c, $l, $s, $i, $j);
        }
	
	} else {
        if (isset($t[$i='title'])) translatePhraseInTUIX($t, $o, $p, $c, $l, $s, $i);
        if (isset($t[$i='label'])) translatePhraseInTUIX($t, $o, $p, $c, $l, $s, $i);
        if (isset($t[$i='tooltip'])) translatePhraseInTUIX($t, $o, $p, $c, $l, $s, $i);
        if (isset($t[$i='disabled_tooltip'])) translatePhraseInTUIX($t, $o, $p, $c, $l, $s, $i);
    	if (isset($t[$i='placeholder'])) translatePhraseInTUIX($t, $o, $p, $c, $l, $s, $i);
    	
		switch ($objectType) {
			case 'lovs':
				if (!empty($t) && is_array($t)) {
					foreach ($t as $k => &$lov) {
						$q = $p. '.'. $k;
						translatePhrasesInTUIXObject($lov, $o, $q, $c, $l, $s, 'lov');
					}
				}
				break;
		
			case 'lov':
			case 'values':
				if (is_string($t)) {
					translatePhraseInTUIX($t, $o, $p, $c, $l, $s);
				}
				break;
		
			case 'tabs':
				translatePhrasesInTUIXObjects(array('notices', 'fields', 'custom_template_fields'), $t, $o, $p, $c, $l, $s);
				break;
		
			case 'notices':
				if (isset($t[$i='message'])) translatePhraseInTUIX($t, $o, $p, $c, $l, $s, $i);
				if (isset($t[$i='multiple_select_message'])) translatePhraseInTUIX($t, $o, $p, $c, $l, $s, $i);
				break;
			
			case 'fields':
			case 'custom_template_fields':
				translatePhrasesInTUIXObjects(array('values'), $t, $o, $p, $c, $l, $s);
		
				if (isset($t[$i='legend'])) translatePhraseInTUIX($t, $o, $p, $c, $l, $s, $i);
				if (isset($t[$i='side_note'])) translatePhraseInTUIX($t, $o, $p, $c, $l, $s, $i);
				if (isset($t[$i='note_below'])) translatePhraseInTUIX($t, $o, $p, $c, $l, $s, $i);
				if (isset($t[$i='empty_value'])) translatePhraseInTUIX($t, $o, $p, $c, $l, $s, $i);
				if (isset($t[$i='validation'][$j='required'])) translatePhraseInTUIX($t, $o, $p, $c, $l, $s, $i, $j);
				
				if (isset($t[$i='snippet'])) {
					if (isset($t[$i][$j='h1'])) translatePhraseInTUIX($t, $o, $p, $c, $l, $s, $i, $j);
					if (isset($t[$i][$j='h2'])) translatePhraseInTUIX($t, $o, $p, $c, $l, $s, $i, $j);
					if (isset($t[$i][$j='h3'])) translatePhraseInTUIX($t, $o, $p, $c, $l, $s, $i, $j);
					if (isset($t[$i][$j='h4'])) translatePhraseInTUIX($t, $o, $p, $c, $l, $s, $i, $j);
					if (isset($t[$i][$j='label'])) translatePhraseInTUIX($t, $o, $p, $c, $l, $s, $i, $j);
				}
				
				if (isset($t[$i='upload'])) {
					if (isset($t[$i][$j='dropbox_phrase'])) translatePhraseInTUIX($t, $o, $p, $c, $l, $s, $i, $j);
					if (isset($t[$i][$j='upload_phrase'])) translatePhraseInTUIX($t, $o, $p, $c, $l, $s, $i, $j);
				}
		
				//Translate button values
				if (isset($t['value']) && isset($t['type']) && ($t['type'] == 'button' || $t['type'] == 'toggle' || $t['type'] == 'submit')) {
			
					//Only translate the values if they look like text
					if ('' !== trim(preg_replace(array('/\\{\\{.*?\\}\\}/', '/\\{\\%.*?\\%\\}/', '/\\<\\%.*?\\%\\>/', '/\\W/'), '', $t['value']))) {
						translatePhraseInTUIX($t, $o, $p, $c, $l, $s, 'value');
					}
				}
				
				//N.b. there's no "break" here,
				//we continue on to the next statement as some fields can be buttons too!
		
			case 'collection_buttons':
			case 'item_buttons':
			case 'inline_buttons':
			case 'quick_filter_buttons':
				if (isset($t[$i='confirm'][$j='title'])) translatePhraseInTUIX($t, $o, $p, $c, $l, $s, $i, $j);
				if (isset($t[$i='confirm'][$j='message'])) translatePhraseInTUIX($t, $o, $p, $c, $l, $s, $i, $j);
				if (isset($t[$i='confirm'][$j='button_message'])) translatePhraseInTUIX($t, $o, $p, $c, $l, $s, $i, $j);
				if (isset($t[$i='confirm'][$j='cancel_button_message'])) translatePhraseInTUIX($t, $o, $p, $c, $l, $s, $i, $j);
				if (isset($t[$i='ajax'][$j='confirm'][$k='title'])) translatePhraseInTUIX($t, $o, $p, $c, $l, $s, $i, $j, $k);
				if (isset($t[$i='ajax'][$j='confirm'][$k='message'])) translatePhraseInTUIX($t, $o, $p, $c, $l, $s, $i, $j, $k);
				if (isset($t[$i='ajax'][$j='confirm'][$k='button_message'])) translatePhraseInTUIX($t, $o, $p, $c, $l, $s, $i, $j, $k);
				if (isset($t[$i='ajax'][$j='confirm'][$k='cancel_button_message'])) translatePhraseInTUIX($t, $o, $p, $c, $l, $s, $i, $j, $k);
				break;
		}
	}
}


function translatePhrasesInTUIXObjects($tagNames, &$tags, &$overrides, $path, $moduleClass, $languageId = false, $scan = false) {
	
	if (!is_array($tagNames)) {
		$tagNames = array($tagNames);
	}
	
	foreach ($tagNames as &$tagName) {
		if (!empty($tags[$tagName]) && is_array($tags[$tagName])) {
			foreach ($tags[$tagName] as $key => &$object) {
				$p = $path. '.'. $tagName. '.'. $key;
				translatePhrasesInTUIXObject(
					$object, $overrides, $p, $moduleClass, $languageId, $scan, $tagName);
			}
		}
	}
}

//Automatically translate any titles/labels in TUIX
function translatePhrasesInTUIX(&$tags, &$overrides, $path, $moduleClass, $languageId = false, $scan = false) {
	
	$path = 'phrase.'. $path;
	
	translatePhrasesInTUIXObject(
		$tags, $overrides, $path, $moduleClass, $languageId, $scan);
	
	translatePhrasesInTUIXObjects(
		array('lovs', 'tabs', 'columns', 'collection_buttons', 'item_buttons', 'inline_buttons', 'quick_filter_buttons'),
		$tags, $overrides, $path, $moduleClass, $languageId, $scan);
}

function lookForPhrasesInTUIX($path = '') {
	
	$overrides = array();
	$tags = array();
	$moduleFilesLoaded = array();
	loadTUIX($moduleFilesLoaded, $tags, 'visitor', $path);

	if (!empty($tags[$path])) {
		translatePhrasesInTUIX(
			$tags[$path], $overrides, $path, false, false, true);
	}
	
	return $overrides;
}

function setupOverridesForPhrasesInTUIX(&$box, &$fields, $path = '') {
	
	$ord = 1000;
	
	$fields['phrase_table_start'] = array(
		'ord' => ++$ord,
		'snippet' => array(
			'html' => '
				<table><tr>
					<th>Phrase</th>
					<th>Customise</th>
				</tr>
			'
		)
	);
	
	$valuesInDB = array();
	loadAllPluginSettings($box, $valuesInDB);

	
	foreach (lookForPhrasesInTUIX($path) as $ppath => $defaultText) {
		
		$fields[$ppath] = array(
			'plugin_setting' => array(
				'name' => $ppath,
				'value' => $defaultText,
				'dont_save_default_value' => true
			),
			'ord' => ++$ord,
            'same_row' => true,
            'pre_field_html' => '
				<tr style="margin-top: 5px;"><td style="padding-top: 10px;">
					'. htmlspecialchars($defaultText). '
					<br/>
					<span style="font-size: 8px;">(<span style="font-family: \'Courier New\', Courier, monospace;"
					>'. htmlspecialchars(substr($ppath, 7)). '</span>)</span>
				</td><td style="padding-top: 10px;">
			',
            'type' => strpos(trim($defaultText), "\n") === false? 'text' : 'textarea',
            'style' => 'width: 30em;',
            'post_field_html' => '
                </td></tr>
            '
        );
        
        if (isset($valuesInDB[$ppath])) {
        	$fields[$ppath]['value'] = $valuesInDB[$ppath];
        } else {
        	$fields[$ppath]['value'] = $defaultText;
        }
	}
	
	$fields['phrase_table_end'] = array(
		'ord' => ++$ord,
		'same_row' => true,
		'snippet' => array(
			'html' => '
                </table>'
		)
	);
	
	if (checkRowExists('languages', array('translate_phrases' => 1))) {
		$mrg = array(
			'def_lang_name' => htmlspecialchars(getLanguageName(cms_core::$defaultLang)),
			'phrases_panel' => htmlspecialchars(absCMSDirURL(). 'zenario/admin/organizer.php#zenario__languages/panels/phrases')
		);
		
		$fields['phrase_table_end']['show_phrase_icon'] = true;
		$fields['phrase_table_end']['snippet']['html'] .= '
			<br/>
			<span>'.
			adminPhrase('Enter text in [[def_lang_name]], this site\'s default language. <a href="[[phrases_panel]]" target="_blank">Click here to manage translations in Organizer</a>.', $mrg).
			'</span>';
	}
}


function setupMultipleRowsInTUIX(
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
	$removeRows = array();
	
	$tab = &$box['tabs'][$tabName];
	
	$fieldCodeNames = array_keys($templateFields);
	if (empty($fieldCodeNames)) {
		echo 'No template fields found';
		exit;
	}
	
	//Check if ordinals have not been added, and add them automatically if needed
	if (!isset($templateFields[$fieldCodeNames[0]]['ord'])) {
		addOrdinalsToTUIX($templateFields);
		
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
	while ($rowExists = !empty($tab['fields'][str_replace('znz', $n, $fieldCodeNames[0])])) {
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
		if ($rowExists = !empty($tab['fields'][str_replace('znz', $n, $fieldCodeNames[0])])) {
			
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
					while ($rowExists = !empty($tab['fields'][str_replace('znz', $m, $fieldCodeNames[0])])) {
						foreach ($fieldCodeNames as $fieldCodeName) {
							$cutName = str_replace('znz', $m, $fieldCodeName);
							$pstName = str_replace('znz', $m - 1, $fieldCodeName);
					
							foreach (array('value', 'current_value', 'pressed', 'hidden', '_was_hidden_before') as $val) {
								if (isset($tab['fields'][$cutName][$val])) {
									$tab['fields'][$pstName][$val] = $tab['fields'][$cutName][$val];
									$tab['fields'][$cutName][$val] = '';
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
					//and put them in the fields array, so that readAdminBoxValues() will turn them
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
							if (!empty($tab['fields'][$copyName]['readonly'])) {
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
		readAdminBoxValues($box, $fields, $values, $changes, $filling, $resetErrors = false);
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








function loadAllPluginSettings(&$box, &$valuesInDB) {
	$valuesInDB = array();
	if (!empty($box['key']['instanceId'])) {
		$sql = "
			SELECT name, `value`
			FROM ". DB_NAME_PREFIX. "plugin_settings
			WHERE instance_id = ". (int) $box['key']['instanceId']. "
			  AND egg_id = ". (int) $box['key']['eggId'];
		$result = sqlQuery($sql);

		while($row = sqlFetchAssoc($result)) {
			$valuesInDB[$row['name']] = $row['value'];
		}
	}
}


//Sync updates from the client to the array stored on the server
function syncAdminBoxFromClientToServer(&$serverTags, &$clientTags, $key1 = false, $key2 = false, $key3 = false, $key4 = false, $key5 = false, $key6 = false) {
	
	$keys = array_merge(arrayValuesToKeys(array_keys($serverTags)), arrayValuesToKeys(array_keys($clientTags)));
	
	foreach ($keys as $key0 => $dummy) {
		//Only allow certain tags in certain places to be merged in
		if (
			($key1 === false && in($key0, 'download', 'path', 'shake', 'tab', 'switchToTab') && ($type = 'value'))
		 || ($key1 === false && in($key0, '_sync', 'tabs') && ($type = 'array'))
			 || ($key2 === false && $key1 == '_sync' && in($key0, 'cache_dir', 'password', 'storage') && ($type = 'value'))
			 || ($key2 === false && $key1 == 'tabs' && ($type = 'array'))
				 || ($key3 === false && $key2 == 'tabs' && in($key0, 'edit_mode', 'fields') && ($type = 'array'))
					 || ($key4 === false && $key3 == 'tabs' && $key1 == 'edit_mode' && $key0 == 'on' && ($type = 'value'))
					 || ($key4 === false && $key3 == 'tabs' && $key1 == 'fields' && ($type = 'array'))
						 || ($key5 === false && $key4 == 'tabs' && $key2 == 'fields' && in($key0, '_display_value', '_was_hidden_before', 'current_value', 'pressed') && ($type = 'value'))
						 || ($key5 === false && $key4 == 'tabs' && $key2 == 'fields' && $key0 == 'multiple_edit' && ($type = 'array'))
							 || ($key6 === false && $key5 == 'tabs' && $key3 == 'fields' && $key1 == 'multiple_edit' && $key0 == '_changed' && ($type = 'value'))
		) {
			
			//Update any values from the client on the server's copy
			if ($type == 'value') {
				
				//Security check - don't allow read-only fields to be changed
				if (($key0 === 'current_value' || $key0 === 'pressed')
				 && ((isset($serverTags['disabled']) && engToBoolean($serverTags['disabled']))
				  || (isset($serverTags['readonly']) && engToBoolean($serverTags['readonly']))
				  || (isset($serverTags['read_only']) && engToBoolean($serverTags['read_only']))
				)) {
					continue;
				}
				
				if (!isset($clientTags[$key0])) {
					unset($serverTags[$key0]);
				} else {
					$serverTags[$key0] = $clientTags[$key0];
				}
			
			//For arrays, check them recursively
			} elseif ($type == 'array') {
				if (isset($serverTags[$key0]) && is_array($serverTags[$key0])
				 && isset($clientTags[$key0]) && is_array($clientTags[$key0])) {
					syncAdminBoxFromClientToServer($serverTags[$key0], $clientTags[$key0], $key0, $key1, $key2, $key3, $key4, $key5);
				}
			}
		}
	}
}

//Sync updates from the server to the array stored on the client
function syncAdminBoxFromServerToClient($serverTags, $clientTags, &$output) {
	
	$keys = arrayValuesToKeys(array_keys($serverTags));
	foreach ($clientTags as $key0 => &$dummy) {
		$keys[$key0] = true;
	}
	
	foreach ($keys as $key0 => &$dummy) {
		if (!isset($serverTags[$key0])) {
			$output[$key0] = array('[[__unset__]]' => true);
		
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
			$output[$key0] = array();
			syncAdminBoxFromServerToClient($serverTags[$key0], $clientTags[$key0], $output[$key0]);
			
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
function closeFABWithFlags($flags) {
	
	$tags = [
		'_sync' => [
			'flags' => $flags
		]
	];
	header('Content-Type: text/javascript; charset=UTF-8');
	jsonEncodeForceObject($tags);
	exit;

}

function displayDebugMode(&$tags, &$modules, &$moduleFilesLoaded, $tagPath, $organizerQueryIds = false, $organizerQueryDetails = false) {
	
	$modules_loaded = array();
	if (!empty($modules)) {
		$modules_loaded = array_keys($modules);
	}
	
	$tags = array(
		'tuix' => $tags,
		'tag_path' => substr($tagPath, 1),
		'modules_loaded' => $modules_loaded,
		'modules_files_loaded' => $moduleFilesLoaded,
		'organizer_query_ids' => $organizerQueryIds,
		'organizer_query_details' => $organizerQueryDetails
	);
	
	header('Content-Type: text/javascript; charset=UTF-8');
	jsonEncodeForceObject($tags);
	exit;
}


//For using encrypted columns in Organizer. Work in progress.
function flagEncryptedColumnsInOrganizer(&$panel, $alias, $table) {
	
	$tableName = DB_NAME_PREFIX. $table;
	$tableAlias = $alias. '.';
	
	if (!isset(cms_core::$dbCols[$tableName])) {
		checkTableDefinition($tableName);
	}
	
	$encryptedColumns = array();
	if (!empty(cms_core::$dbCols[$tableName])) {
		foreach (cms_core::$dbCols[$tableName] as $col => $colDef) {
			if ($colDef->encrypted) {
				$encryptedColumns[$col] = $colDef;
			}
		}
	}
	
	if (!empty($encryptedColumns)) {
		foreach ($panel['columns'] as &$column) {
			
			if (isset($column['db_column'])) {
				$colName = trim(chopPrefixOffString($tableAlias, trim($column['db_column'])), '`');
				
				if (isset($encryptedColumns[$colName])) {
					$colDef = $encryptedColumns[$colName];
				
					$column['db_column'] = $tableAlias. "`%". sqlEscape($colDef->col). "`";
					$column['encrypted'] = [
						'hashed_column' => $tableAlias. "`#". sqlEscape($colDef->col). "`",
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

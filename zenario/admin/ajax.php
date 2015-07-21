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

session_start();
require '../visitorheader.inc.php';
require CMS_ROOT. 'zenario/includes/admin.inc.php';
useGZIP();

//Add the admin id and type up as constants
define('ADMIN_ID', (int) session('admin_userid'));

/*
	This file is used to drive the AJAX interface for TUIX.
	It reads all relevant xml files, then merge them together into a PHP array, calls Module code to process
	that array, and then finally sends them via JSON to the client
	
	It's main features are:
		Generating a complete "Map" of everything in Organizer
		"Focusing" on a specific Panel in Organizer, and sending detailed information on that
	It's also used in the new Admin Box system for:
		"Focusing" on a specific Admin Box, and sending detailed information on that
		Validating and Saving Admin Boxes
	And also used to generate the Admin Toolbar, however there is no focusing in this case.
	
	It has a couple of other, rarely used features:
		An XML generation mode for the Languages Panel, to enable a Language Store
		A CSV export system for the users Panel
*/


$mode = false;
$tagPath = '';
$debugMode = (bool) get('_debug');
$storekeeperQueryIds = false;
$storekeeperQueryDetails = false;
$commentMade = false;
$loadDefinition = true;
$settingGroup = '';
$compatibilityClassNames = array();
if (get('_ab')) {
	$type = 'admin_boxes';
} elseif (get('_at')) {
	$type = 'admin_toolbar';
} else {
	$type = 'organizer';
	
	//Work out which mode this should be for Organizer
	if (get('_xml') || get('method_call') == 'showSitemap') {
		define('ORGANIZER_MODE', $mode = 'xml');
	} elseif (get('_select_mode')) {
		define('ORGANIZER_MODE', $mode = 'select');
	} elseif (get('_quick_mode')) {
		define('ORGANIZER_MODE', $mode = 'quick');
	} elseif (get('_get_item_name')) {
		define('ORGANIZER_MODE', $mode = 'get_item_name');
	} elseif (!empty($_REQUEST['_get_item_links'])) {
		define('ORGANIZER_MODE', $mode = 'get_item_links');
	} elseif (get('_get_item_data')) {
		define('ORGANIZER_MODE', $mode = 'get_item_data');
	} elseif (get('_get_ids_for_ajax_request')) {
		define('ORGANIZER_MODE', $mode = 'get_ids_for_ajax_request');
	} else {
		define('ORGANIZER_MODE', $mode = 'full');
	}
}
cms_core::$skType = $type;

//Always require Admin Permissions, except for Organizer which has a feature where feeds from some panels can be made public
if ($type != 'organizer' && $mode != 'xml') {
	if (!checkPriv()) {
		echo '<!--Logged_Out-->', adminPhrase('You have been logged out.');
		exit;
	}
}


define('FOCUSED_LANGUAGE_ID__NO_QUOTES', ifNull(ifNull(request('languageId'), get('refiner__language')), setting('default_language'), 'en'));
define('FOCUSED_LANGUAGE_ID', "'". sqlEscape(FOCUSED_LANGUAGE_ID__NO_QUOTES). "'");




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
	 || !is_dir(CMS_ROOT. 'cache/uploads/'. preg_replace('/[^\\w-]/', '', $box['_sync']['cache_dir']))) {
		$box['_sync']['cache_dir'] =
			createRandomDir(
				8, $type = 'uploads', false, false,
				$prefix = 'ab_'. hash64(json_encode($box), 8). '_');
	}
	
	if (!empty($box['_sync']['cache_dir'])) {
		$box['_sync']['cache_dir'] = str_replace('cache/uploads/', '', $box['_sync']['cache_dir']);
		$box['_sync']['cache_dir'] = preg_replace('/[^\\w-]/', '', $box['_sync']['cache_dir']);
		touch(CMS_ROOT. 'cache/uploads/'. $box['_sync']['cache_dir']. '/accessed');
		return CMS_ROOT. 'cache/uploads/'. $box['_sync']['cache_dir']. '/ab.json';
	
	} else {
		return false;
	}
}

function readAdminBoxValues(&$box, &$fields, &$values, &$changes, $filling, $resetErrors, $preDisplay) {
	
	if (!empty($box['tabs']) && is_array($box['tabs'])) {
		foreach ($box['tabs'] as $tabName => &$tab) {
			if (is_array($tab) && !empty($tab['fields']) && is_array($tab['fields'])) {
				
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
					 || (!empty($field['type']) && $field['type'] != 'submit' && $field['type'] != 'toggle');

					
					if ($resetErrors) {
						unset($field['error']);
					}
					
					if ($isField) {
						//Fields in readonly mode should use ['value'] as their value;
						//fields not in readonly mode should use ['current_value'].
						$readOnly =
							$filling
						 || !engToBooleanArray($tab, 'edit_mode', 'on')
						 || engToBooleanArray($field, 'read_only');
						
						if (isset($field['value']) && is_array($field['value'])) {
							unset($field['value']);
						}
						if ((isset($field['current_value']) && is_array($field['current_value'])) || $readOnly) {
							unset($field['current_value']);
						}
						
						if (!isset($field[$readOnly? 'value' : 'current_value'])) {
							$field[$readOnly? 'value' : 'current_value'] = '';
						}
						
						//Logic for Multiple-Edit
						//This may be removed soon, but I'm keeping it alive for now as a few things still use this functionality
						if (!isset($field['multiple_edit'])) {
							$changed = false;
						
						} else
						if ($readOnly
						 || (isset($field['multiple_edit']['changed']) && !isset($field['multiple_edit']['_changed']))) {
							$changed = engToBooleanArray($field['multiple_edit'], 'changed');
						
						} else {
							$changed = engToBooleanArray($field['multiple_edit'], '_changed');
						}
					}
					
					$fields[$tabName. '/'. $fieldName] = &$tab['fields'][$fieldName];
					if ($isField) {
						$values[$tabName. '/'. $fieldName] = &$tab['fields'][$fieldName][$readOnly? 'value' : 'current_value'];
						$changes[$tabName. '/'. $fieldName] = $changed;
					}
					
					if (!isset($fields[$fieldName])) {
						$fields[$fieldName] = &$tab['fields'][$fieldName];
						if ($isField) {
							$values[$fieldName] = &$tab['fields'][$fieldName][$readOnly? 'value' : 'current_value'];
							$changes[$fieldName] = $changed;
						}
					}
					
					if ($isField) {
						//If this field is for an equivalence, make sure it shows the Content Item in the current language when being displayed
						//And also make sure that it is in the Default Language when it is saved
						if (engToBooleanArray($field, 'pick_items', 'equivalence')) {
							//Try to guess what language this should be in
							if (!$preDisplay) {
								$langIdToUse = setting('default_language');
							} else {
								$langIdToUse = ifNull(arrayKey($box, 'key', 'languageId'), setting('default_language'));
								
								//Attempt to change the opening path to the correct path for the language. But only do this if we recognise the format.
								if (empty($field['pick_items']['path']) || $field['pick_items']['path'] == 'zenario__content/panels/language_equivs') {
									$field['pick_items']['path'] = 'zenario__content/panels/languages/item//'. $langIdToUse. '//collection_buttons/equivs////';
								}
							}
							
							//Attempt to convert the chosen Content Item to the correct language equivalent
							foreach (array('', '_') as $u) {
								if (isset($field[$u. 'value'])) {
									$cID = $field[$u. 'value'];
									$cType = false;
									if (langEquivalentItem($cID, $cType, $langIdToUse)) {
										if ($field[$u. 'value'] != $cType. '_'. $cID) {
											$field[$u. 'value'] = $cType. '_'. $cID;
										}
									}
								}
							}
						
						} elseif (engToBooleanArray($field, 'pick_items', 'by_language')) {
							//Try to guess what language this should be in
							if ($preDisplay) {
								if (!empty($values[$tabName. '/'. $fieldName]) && $langIdToUse = getRow('content', 'language_id', array('tag_id' => $values[$tabName. '/'. $fieldName]))) {
								
								} else {
									$langIdToUse = ifNull(arrayKey($box, 'key', 'languageId'), setting('default_language'));
								}
								
								//Attempt to change the opening path to the correct path for the language. But only do this if we recognise the format.
								if (empty($field['pick_items']['path']) || substr($field['pick_items']['path'], 0, 26) == 'zenario__content/panels/languages') {
									$field['pick_items']['path'] = 'zenario__content/panels/languages/item//'. $langIdToUse. '//';
								}
							}
						
						//Editor fields will need the addImageDataURIsToDatabase() run on them
						} else
						if (isset($field['current_value'])
						 && arrayKey($box, 'tabs', $tabName, 'fields', $fieldName, 'type')  == 'editor'
						 && !empty($box['tabs'][$tabName]['fields'][$fieldName]['insert_image_button'])) {
							//Convert image data urls to files in the database
							addImageDataURIsToDatabase($field['current_value'], absCMSDirURL());
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

//Sync updates from the client to the array stored on the server
function syncAdminBoxFromClientToServer(&$serverTags, &$clientTags, $key1 = false, $key2 = false, $key3 = false, $key4 = false, $key5 = false, $key6 = false) {
	$keys = array();
	if (is_array($serverTags)) {
		foreach (array_keys($serverTags) as $key) {
			$keys[$key] = true;
		}
	}
	if (is_array($clientTags)) {
		foreach (array_keys($clientTags) as $key) {
			$keys[$key] = true;
		}
	}
	
	foreach ($keys as $key0 => $dummy) {
		//Only allow certain tags in certain places to be merged in
		if ((($type = 'array') && $key1 === false && $key0 == '_sync')
		 || (($type = 'value') && $key2 === false && $key1 == '_sync' && $key0 == 'storage')
		 || (($type = 'value') && $key2 === false && $key1 == '_sync' && $key0 == 'cache_dir')
		 || (($type = 'array') && $key1 === false && $key0 == 'key')
		 || (($type = 'value') && $key2 === false && $key1 == 'key')
		 || (($type = 'value') && $key1 === false && $key0 == 'shake')
		 || (($type = 'value') && $key1 === false && $key0 == 'download')
		 || (($type = 'array') && $key1 === false && $key0 == 'tabs')
		 || (($type = 'array') && $key2 === false && $key1 == 'tabs')
		 || (($type = 'array') && $key3 === false && $key2 == 'tabs' && $key0 == 'edit_mode')
		 || (($type = 'value') && $key4 === false && $key3 == 'tabs' && $key1 == 'edit_mode' && $key0 == 'on')
		 || (($type = 'array') && $key3 === false && $key2 == 'tabs' && $key0 == 'fields')
		 || (($type = 'array') && $key4 === false && $key3 == 'tabs' && $key1 == 'fields')
		 || (($type = 'value') && $key5 === false && $key4 == 'tabs' && $key2 == 'fields' && $key0 == 'current_value')
		 || (($type = 'value') && $key5 === false && $key4 == 'tabs' && $key2 == 'fields' && $key0 == '_display_value')
		 || (($type = 'value') && $key5 === false && $key4 == 'tabs' && $key2 == 'fields' && $key0 == '_was_hidden_before')
		 || (($type = 'value') && $key5 === false && $key4 == 'tabs' && $key2 == 'fields' && $key0 == 'pressed')
		 || (($type = 'array') && $key5 === false && $key4 == 'tabs' && $key2 == 'fields' && $key0 == 'multiple_edit')
		 || (($type = 'value') && $key6 === false && $key5 == 'tabs' && $key3 == 'fields' && $key1 == 'multiple_edit' && $key0 == '_changed')) {
			
			//Update any values from the client on the server's copy
			if ($type == 'value') {
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
	$keys = array();
	if (is_array($serverTags)) {
		foreach (array_keys($serverTags) as $key) {
			$keys[$key] = true;
		}
	}
	if (is_array($clientTags)) {
		foreach (array_keys($clientTags) as $key) {
			$keys[$key] = true;
		}
	}
	
	foreach ($keys as $key0 => $dummy) {
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

function displayDebugMode(&$tags, &$modules, &$moduleFilesLoaded, $tagPath, $storekeeperQueryIds = false, $storekeeperQueryDetails = false) {
	
	$modules_loaded = array();
	if (!empty($modules)) {
		$modules_loaded = array_keys($modules);
	}
	
	$tags = array(
		'tuix' => $tags,
		'tag_path' => substr($tagPath, 1),
		'modules_loaded' => $modules_loaded,
		'modules_files_loaded' => $moduleFilesLoaded,
		'organizer_query_ids' => $storekeeperQueryIds,
		'organizer_query_details' => $storekeeperQueryDetails
	);
	
	header('Content-Type: text/javascript; charset=UTF-8');
	jsonEncodeForceObject($tags);
	exit;
}







//If this is an Admin Box, and this isn't the first load, attempt to load the defintion from the Storage
if ($type == 'admin_boxes' && !post('_fill') && !$debugMode) {
	//Load the information that we have from the client
	if (empty($_POST['_box'])) {
		echo adminPhrase('An error occurred when syncing this floating admin box with the server.');
		exit;
	}
	$clientTags = json_decode($_POST['_box'], true);
	
	//Attempt to pick the right box and load from the Storage
		//(This may be in the cache directory or the session, depending on whether the cache was writable)
	if (($adminBoxSyncStoragePath = adminBoxSyncStoragePath($clientTags))
	 && (file_exists($adminBoxSyncStoragePath))
	 && ($tags = json_decode(file_get_contents($adminBoxSyncStoragePath), true))
	 && (is_array($tags))) {
		$loadDefinition = false;
	
	} else
	if (!empty($clientTags['_sync']['session'])
	 && !empty($_SESSION['admin_box_sync'][$clientTags['_sync']['session']])
	 && ($tags = json_decode($_SESSION['admin_box_sync'][$clientTags['_sync']['session']], true))
	 && (is_array($tags))) {
		$loadDefinition = false;
	
	} else {
		if (!empty($clientTags['_sync']['session']) || !setting('fab_use_cache_dir')) {
			echo adminPhrase('An error occurred when syncing this floating admin box with the server. There is a problem with the server\'s $_SESSION variable.');
		
		} else {
			echo adminPhrase('An error occurred when syncing this floating admin box with the server. A file placed in the cache/ directory could not be found.');
		}
		exit;
	}
	
	syncAdminBoxFromClientToServer($tags, $clientTags);
	$originalTags = $tags;
}


//See if there is a requested path.
$requestedPath = false;
if (get('method_call') == 'showSitemap') {
	$requestedPath = 'zenario__content/hidden_nav/sitemap/panel';

} elseif (request('path')) {
	$requestedPath = preg_replace('/[^\w\/]/', '', request('path'));
}
cms_core::$skPath = $requestedPath;

$filters = array();
if ($type == 'organizer' && get('_filters')) {
	$filters = json_decode(get('_filters'), true);
}

//The Plugin Settings Admin Boxes are a special case for looking up XML files.
//They need to include the Settings from the Plugin in question, and any modules it is compatable with
if ($type == 'admin_boxes') {
	if ($requestedPath == 'plugin_settings') {
		if (get('refiner__nest') && get('id')) {
			$nestedItem = getNestDetails(get('id'), get('refiner__nest'));
			$module = getModuleDetails($nestedItem['module_id']);
		
		} elseif (!get('instanceId') && get('refiner__plugin')) {
			$module = getModuleDetails(get('refiner__plugin'));
		
		} elseif (get('moduleId')) {
			$module = getModuleDetails(get('moduleId'));
		
		} else {
			$module = getPluginInstanceDetails(ifNull(get('instanceId'), get('id')));
		}
		
		if ($module) {
			$settingGroup = $module['class_name'];
			
			//Loop through each of the Plugin's Compatibilities
			foreach (getModuleInheritances($module['class_name'], 'inherit_settings') as $className) {
				$compatibilityClassNames[$className] = $className;
			}
		}
	
	} elseif ($requestedPath == 'site_settings') {
		$settingGroup = request('id');
	}
}


if ($loadDefinition) {
	//Scans the Module directory for Modules with the relevant TUIX files, read them, and get a php array
	$moduleFilesLoaded = array();
	$tags = array();
	$originalTags = array();
	loadTUIX($moduleFilesLoaded, $tags, $type, $requestedPath, $settingGroup, $compatibilityClassNames);
	
	
	//If we had a requested path, drill straight down to that level
	if ($requestedPath) {
		
		foreach(explode('/', $requestedPath) as $path) {
			if (isset($tags[$path]) && is_array($tags[$path])) {
				$tags = $tags[$path];
				$tagPath .= '/'. $path;
			
			} else {
				echo adminPhrase('The requested path "[[path]]" was not found in the system. If you have just updated or added files to the CMS, you will need to reload the page.', array('path' => $requestedPath));
				exit;
			}
		}
	
	} else {
		//There's no "map" for admin Admin Boxes; they must have a path
		if ($type == 'admin_boxes') {
			echo adminPhrase('An Admin Box path was needed, but none was given.');
			exit;
		}
	}
}

if ($debugMode) {
	$staticTags = $tags;
}

//Check that an admin is logged in, and kick them if not
	//Note that there's an option to bipass this and allow visitors in!
if (!checkPriv() && !($mode == 'xml' && engToBooleanArray($tags, 'xml', 'allow_unauthenticated_xml_access'))) {
	echo '<!--Logged_Out-->', adminPhrase('You have been logged out.');
	exit;
}

//If this is a request for a specific path, run that Module and let it manage its output in PHP
if ($requestedPath && !empty($tags['class_name'])) {
	
	if (isset($tags['priv']) && !checkPriv($tags['priv'])) {
		if ($type == 'organizer') {
			echo adminPhrase('You do not have permissions to see this Panel.');
		
		} elseif ($type == 'admin_boxes') {
			echo adminPhrase('You do not have permissions to see this Admin Box.');
		}
		exit;
	}
	
	
	if (!zenarioAJAXIncludeModule($modules, $tags, $type, $requestedPath, $settingGroup)) {
		echo adminPhrase('Could not activate the [[class_name]] Module.', array('class_name' => $tags['class_name']));
		exit;
	}
	
	if ($type == 'organizer') {
		
		//Add definitions for any refiners supplied in the request
		$refinersPresent = array('refinerId' => 'REFINER_ID', '_combineItem' => 'COMBINE_ITEM');
		foreach ($_REQUEST as $key => $value) {
			if (substr($key, 0, 9) == 'refiner__') {
				$refinersPresent[$key] = strtoupper($key);
			}
		}
		
		//Add definitions for any refiners defined for this panel
		if (isset($tags['refiners']) && is_array($tags['refiners'])) {
			foreach ($tags['refiners'] as $key => $refiner) {
				$refinersPresent['refiner__'. $key] = strtoupper('refiner__'. $key);
			}
		}
		
		foreach ($refinersPresent as $req => $def) {
			if (isset($_GET[$req])) {
				$refiners = '';
				foreach (explode(',', get($req)) as $i => $refiner) {
					$refiners .= $i? ',' : '';
					$refiner = decodeItemIdForStorekeeper($refiner);
					
					if (is_numeric($refiner)) {
						$refiners .= (int) $refiner;
					} else {
						$refiners .= "'". sqlEscape($refiner). "'";
					}
					
					if (!$i) {
						define($def, $refiners);
					}
				}
			} else {
				define($def, $refiners = 'NULL');
			}
			
			if ($def == 'REFINER_ID' || $def == 'COMBINE_ITEM') {
				define($def. 'S', '('. $refiners. ')');
			} else {
				define($def. '__S', '('. $refiners. ')');
			}
		}
		
		if (request('_combineItem')) {
			define('COMBINE_ITEM__NO_QUOTES', request('_combineItem'));
		}
		
		//Start to populate the Organizer Panel:
		//Firstly, see if other modules have added buttons/columns/refiners.
		//If so, they'll need their own placeholder methods executing as well
		
		//Note down if any buttons or refiners need any code from a different Module
		foreach (array('collection_buttons', 'item_buttons', 'inline_buttons', 'refiners') as $buttonType) {
			if (isset($tags[$buttonType]) && is_array($tags[$buttonType])) {
				foreach ($tags[$buttonType] as &$button) {
					if (is_array($button) && !empty($button['class_name'])) {
						zenarioAJAXIncludeModule($modules, $button, $type, $requestedPath, $settingGroup);
					}
				}
			}
		}
		
		//Have any columns been added that need formatting from their own Module?
		if (!get('_queued')) {
			if (isset($tags['columns']) && is_array($tags['columns'])) {
				foreach ($tags['columns'] as $colName => &$col) {
					if (is_array($col) && !empty($col['class_name'])) {
						zenarioAJAXIncludeModule($modules, $col, $type, $requestedPath, $settingGroup);
					}
				}
			}
		}
		
		//Run the modules' lining method to add/modify/remove columns/refiners/other tags
		foreach ($modules as $className => &$module) {
			
			//Handle the old name if it's not been changed yet
			if (method_exists($module, 'lineStorekeeper')) {
				$module->lineStorekeeper($requestedPath, $tags, request('refinerName'), request('refinerId'), $mode);
			}
			
			$module->preFillOrganizerPanel($requestedPath, $tags, request('refinerName'), request('refinerId'), $mode);
		}
		
		
		//Secondly, run any SQL queries that are needed to populate the items.
		//Refiners and pagination are also applied at this point
		if (empty($tags['items'])
		 && !empty($tags['db_items']['table'])
		 && !empty($tags['db_items']['id_column'])) {
			
			$orderBy = "";
			$whereStatement = "";
			$extraTables = array();
			$sortExtraTables = array();
			
			//Allow a Panel with no columns as a hack to get several complicated refiner chains working
			if (empty($tags['columns'])) {
				$tags['columns'] = array();
			}
			
			$encodeItemIdForStorekeeper = engToBooleanArray($tags, 'db_items', 'encode_id_column');
			
			if (!isset($tags['items']) || !is_array($tags['items'])) {
				$tags['items'] = array();
			}
			
			//Look for any custom fields/tabs
			if ($dataset = getRow('custom_datasets', true, array('extends_organizer_panel' => $requestedPath))) {
				
				//Look up the name of the primary/foreign key column
				checkTableDefinition(DB_NAME_PREFIX. $dataset['table']);		
				if (cms_core::$pkCols[DB_NAME_PREFIX. $dataset['table']]) {
					$datasetIdColumn = "custom.`". sqlEscape(cms_core::$pkCols[DB_NAME_PREFIX. $dataset['table']]). "`";
				} else {
					$datasetIdColumn = "custom.id";
				}
				
				//Add a join to the custom table
					//Note: always do this, even if the admin doesn't have the permissions,
					//just to stop any Modules from erroring
				$join = "
					LEFT JOIN `". sqlEscape(DB_NAME_PREFIX. $dataset['table']). "` AS custom
					ON ". $tags['db_items']['id_column']. " = ". $datasetIdColumn;
				$sortExtraTables[$join] = true;
				
				if (!$dataset['view_priv'] || checkPriv($dataset['view_priv'])) {
					//Add custom fields
					$ord = 1000;
					foreach (getRowsArray(
						'custom_dataset_fields',
						true,
						array('dataset_id' => $dataset['id'], 'show_in_organizer' => 1, 'is_system_field' => 0),
						array('tab_name', 'ord')
					) as $cfield) {
						$cCol = array();
						$cCol['db_column'] = "custom.`". $cfield['db_column']. "`";
						$cCol['searchable'] = $cfield['searchable'];
						$cCol['disallow_sorting'] = !$cfield['sortable'];
						$cCol['show_by_default'] = $cfield['show_by_default'];
						$cCol['always_show'] = $cfield['always_show'];
			
						switch ($cfield['type']) {
							case 'editor':
								//Never show "editor" type fields"
								continue 2;
				
							case 'group':
							case 'checkbox':
								$cCol['format'] = 'yes_or_no';
								break;
				
							case 'date':
								$cCol['format'] = 'date';
								break;
				
							case 'checkboxes':
								//For checkboxes, there could be multiple values, so we'll just load the data as a string
								$cCol['db_column'] = "(
									SELECT GROUP_CONCAT(cdfv.label SEPARATOR ', ')
									FROM ". DB_NAME_PREFIX. "custom_dataset_values_link AS cdvl
									INNER JOIN ". DB_NAME_PREFIX. "custom_dataset_field_values AS cdfv
									   ON cdfv.field_id = ". (int) $cfield['id']. "
									  AND cdfv.id = cdvl.value_id
									WHERE cdvl.linking_id = ". $tags['db_items']['id_column']. "
									)";
								break;
				
							case 'radios':
							case 'select':
								//For radios/select lists, if they are searchable or sortable then
								//we need to load the data as a string
								if ($cfield['searchable'] || $cfield['sortable']) {
									$cCol['db_column'] = "(
										SELECT cdfv.label
										FROM ". DB_NAME_PREFIX. "custom_dataset_field_values AS cdfv
										WHERE cdfv.id = ". $cCol['db_column']. "
										  AND cdfv.field_id = ". (int) $cfield['id']. "
										)";
						
									//If they are also searchable/filterable then we need a select list in this format
									if ($cfield['searchable']) {
										$cCol['format'] = 'enum';
										$cCol['values'] = array();
										foreach (getDatasetFieldLOV($cfield, true) as $displayValue) {
											$cCol['values'][$displayValue] = $displayValue;
										}
									}
									break;
								}
				
							//Otherwise we can just load the real value and format it on the client
							case 'centralised_radios':
							case 'centralised_select':
								$cCol['format'] = 'enum';
								$cCol['values'] = getDatasetFieldLOV($cfield, true);
								break;
						}
			
						$cCol['ord'] = ++$ord;
						$cCol['title'] = $cfield['label'];
						if (substr($cCol['title'], -1) == ':') {
							$cCol['title'] = substr($cCol['title'], 0, -1);
						}
			
						$cFieldName = '__custom_field_'. $cfield['id'];
						$tags['columns'][$cFieldName] = $cCol;
					}
					unset($cfield);
				}
			}
			
			
			
			//Apply a refiners, if this panel has any and one has been selected
			if (get('refinerName') && !empty($tags['refiners'][get('refinerName')])) {
				
				//allow_unauthenticated_xml_access must be repeated on a refiner if they are both used
				if (!checkPriv() && !($mode == 'xml' && engToBooleanArray($tags, 'refiners', get('refinerName'), 'allow_unauthenticated_xml_access'))) {
					echo '<!--Logged_Out-->', adminPhrase('You have been logged out.');
					exit;
				}
				
				if (isset($_GET['_search']) && !empty($tags['refiners'][get('refinerName')]['sql_when_searching'])) {
					$whereStatement .= "
						AND ". $tags['refiners'][get('refinerName')]['sql_when_searching'];
				
				} elseif (!empty($tags['refiners'][get('refinerName')]['sql'])) {
					$whereStatement .= "
						AND ". $tags['refiners'][get('refinerName')]['sql'];
				}
				
				//Add any table-joins for refiners
				if (isset($_GET['_search']) && !empty($tags['refiners'][get('refinerName')]['table_join_when_searching'])) {
					$sortExtraTables[prefixTableJoin($tags['refiners'][get('refinerName')]['table_join_when_searching'])] = true;
				
				} elseif (!empty($tags['refiners'][get('refinerName')]['table_join'])) {
					$sortExtraTables[prefixTableJoin($tags['refiners'][get('refinerName')]['table_join'])] = true;
				}
			
			} elseif (engToBooleanArray($tags, 'refiner_required')) {
				echo 'A refiner was required, but none was set.';
				exit;
			}
			
			
			//Loop through each database-column defined in the XML schema
			$columns = "";
			$sortColumn = false;
			$sortColumnDesc = false;
			
			$i = 0;
			foreach ($tags['columns'] as $colName => &$col) {
				if (is_array($col) && !empty($col['db_column'])) {
					//Add it to the SELECT
					if ($col['db_column'] != 'NULL') {
						$columns .= ",
							". $col['db_column'];
					}
					
					//Add it to the sort if we're sorting by it
					if ($colName == get('_sort_col') && !engToBooleanArray($col, 'disallow_sorting')) {
						if (!empty($col['sort_column'])) {
							$sortColumn = $col['sort_column'];
						} else {
							$sortColumn = $col['db_column'];
						}
						
						if (!empty($col['sort_column_desc'])) {
							$sortColumnDesc = $col['sort_column_desc'];
						} else {
							$sortColumnDesc = $sortColumn. ' DESC';
						}
					}
					
					//If it's from a different table, join to that table
					if (!empty($col['table_join'])) {
						
						//Check whether this column is being filtered, and what
						//the filter format is
						$filterFormat = false;
						if ($isFiltered = !empty($filters[$colName]['value_'])) {
							if (!empty($col['format'])) {
								$filterFormat = $col['format'];
							} elseif (!empty($col['filter_format'])) {
								$filterFormat = $col['filter_format'];
							} 
						}
						
						
						
						if ($colName == get('_sort_col')
						 || (($isFiltered || isset($_GET['_search'])) && engToBooleanArray($col, 'searchable'))
						 || ($isFiltered && $filterFormat && in(
								$filterFormat,
								'enum', 'yes_or_no',
								'language_english_name', 'language_english_name_with_id',
								'language_local_name', 'language_local_name_with_id',
								'date', 'datetime', 'datetime_with_seconds'
						))) {
							$sortExtraTables[prefixTableJoin($col['table_join'])] = true;
						} else {
							$extraTables[prefixTableJoin($col['table_join'])] = true;
						}
					}
				}
			}
			
			
			//Apply a search from the search box
			if (isset($_GET['_search']) && !empty($tags['columns'])) {
				$whereStatement .= "
				  AND (";
				
				$first = true;
				foreach ($tags['columns'] as $colName => &$col) {
					if (!empty($col['db_column']) && engToBooleanArray($col, 'searchable')) {
						//Group functions can't be used in a query
						if (!preg_match('/COUNT\s*\(/i', $col['db_column'])) {
							
							$asciiCharactersOnly = engToBooleanArray($col, 'ascii_only');
							
							if ($first) {
								$first = false;
							} else {
								$whereStatement .= " OR";
							}
							
							$whereStatement .= "
								". ifNull(arrayKey($col, 'search_column'), $col['db_column']). " LIKE '%". likeEscape(get('_search'), true, $asciiCharactersOnly). "%'";
						}
					}
				}
				
				if ($first) {
					$whereStatement .= "TRUE";
				}
				
				$whereStatement .= "
				  )";
			}
			
			
			
			//Apply filters
			foreach ($tags['columns'] as $colName => &$col) {
				if (is_array($col)
				 && !empty($col['db_column'])
				 && !empty($filters[$colName]['value_'])
				 && !engToBooleanArray($col, 'disallow_filtering')) {
					
					$value_ = $filters[$colName]['value_'];
					$columnName = ifNull(arrayKey($col, 'search_column'), $col['db_column']);
					
					$filterFormat = false;
					if (!empty($col['format'])) {
						$filterFormat = $col['format'];
					} elseif (!empty($col['filter_format'])) {
						$filterFormat = $col['filter_format'];
					} 
					$isDateColumn = $filterFormat == 'date' || $filterFormat == 'datetime' || $filterFormat == 'datetime_with_seconds';
					
					switch ($filterFormat) {
						case 'date':
						case 'datetime':
						case 'datetime_with_seconds':
							$dates = explode(',', $value_);
							
							if (!empty($dates[0]) && preg_replace('/\d{4}-\d{2}-\d{2}/', '', $dates[0]) == '') {
								$whereStatement .= "
								  AND ". $columnName.
									  " >= '". sqlEscape($dates[0]). "'";
							}
							
							if (!empty($dates[1]) && preg_replace('/\d{4}-\d{2}-\d{2}/', '', $dates[1]) == '') {
								$whereStatement .= "
								  AND ". $columnName.
									" < DATE_ADD('". sqlEscape($dates[1]). "', INTERVAL 1 DAY)";
							}
							
							break;
						
						//Yes/No type filters on tinyint columns
						case 'yes_or_no':
						
							if (empty($filters[$colName]['not'])) {
								$whereStatement .= "
								  AND ". $columnName. " != 0";
						
							} else {
								$whereStatement .= "
								  AND (". $columnName. " = 0
									OR ". $columnName. " IS NULL)";
							}
							
							break;
							
							
						//enum filters
						case 'enum':
						case 'language_english_name':
						case 'language_english_name_with_id':
						case 'language_local_name':
						case 'language_local_name_with_id':
						
							//A value of "*" should match all values (or all empty values if not is set)
							if ($filters[$colName]['value_'] == '*') {
								if (empty($filters[$colName]['not'])) {
									$whereStatement .= "
									  AND ". $columnName. " != 0
									  AND ". $columnName. " != ''";
						
								} else {
									$whereStatement .= "
									  AND (". $columnName. " = 0
										OR ". $columnName. " = ''
										OR ". $columnName. " IS NULL)";
								}
						
							//Otherwise do a normal match
							} else {
								if (empty($filters[$colName]['not'])) {
									$whereStatement .= "
									  AND ". $columnName. " =  '". sqlEscape($filters[$colName]['value_']). "'";
						
								} else {
									$whereStatement .= "
									  AND (". $columnName. " !=  '". sqlEscape($filters[$colName]['value_']). "'
										OR ". $columnName. " IS NULL)";
								}
							}
							
							break;
							
							
						default:
							//Do a text search for filters on text fields
							if (engToBooleanArray($col, 'searchable')) {
						
								$asciiCharactersOnly = engToBooleanArray($col, 'ascii_only');
						
								if (empty($filters[$colName]['not'])) {
									$whereStatement .= "
										AND ". $columnName. " LIKE '%". likeEscape($filters[$colName]['value_'], true, $asciiCharactersOnly). "%'";
						
								} else {
									$whereStatement .= "
										AND (". $columnName. " IS NULL OR ". $columnName. " NOT LIKE '%". likeEscape($filters[$colName]['value_'], true, $asciiCharactersOnly). "%')";
								}
							}
					}
				}
			}
			
			
			
			
			//Check to see if a hierarchy column is set
			$hierarchyColumn = false;
			if (!empty($tags['hierarchy'])) {
				//It can be set directly in $tags['hierarchy']['db_column'],
				//or looked up by checking for the definition of $tags['hierarchy']['column']
				//in the columns array.
				if (!empty($tags['hierarchy']['db_column'])) {
					$hierarchyColumn = $tags['hierarchy']['db_column'];
				} else {
					if (!empty($tags['hierarchy']['column'])) {
						$hierarchyColumnName = $tags['hierarchy']['column'];
					} else {
						$hierarchyColumnName = 'parent_id';
					}
					
					if (!empty($tags['columns'][$hierarchyColumnName]['db_column'])) {
						$hierarchyColumn = $tags['columns'][$hierarchyColumnName]['db_column'];
					}
				}
			}
			$idColumn = $tags['db_items']['id_column'];
			
			if (!empty($tags['db_items']['group_by'])) {
				$groupBy = $tags['db_items']['group_by'];
			} else {
				$groupBy = $idColumn;
			}
			
			//Load the standard where-statement, if this panel has one
			if (!empty($tags['db_items']['where_statement'])) {
				$whereStatement = $tags['db_items']['where_statement']. $whereStatement;
			} else {
				$whereStatement = "WHERE TRUE". $whereStatement;
			}
			
			//Order by the sort column
			if ($sortColumn) {
				if (get('_sort_desc')) {
					$orderBy = $sortColumnDesc. ", ". $groupBy;
				} else {
					$orderBy = $sortColumn. ", ". $groupBy;
				}
			} else {
				$orderBy = $groupBy;
			}
			
			$in = "";
			$noResults = false;
			
			
			//Handle AJAX buttons that ask for a list of matched ids
			//Note that this code is similar to the logic in zenario/ajax.php that normally handles
			//the handleOrganizerPanelAJAX() and organizerPanelDownload() methods, except this version
			//also runs preFillOrganizerPanel() and includes a list of ids
			if ($mode == 'get_ids_for_ajax_request') {

				//Get a list of all of the matched ids
				$sql = "
					SELECT ". $idColumn. "
					FROM ". $tags['db_items']['table'];
			
				foreach ($sortExtraTables as $join => $dummy) {
					$sql .= "
						". $join;
				}
				
				$sql .= "
					". $whereStatement. "
					GROUP BY ". $groupBy. "
					ORDER BY ". $orderBy;
				
				$storekeeperQueryIds = addConstantsToString($sql);
				$result = sqlSelect($storekeeperQueryIds);
				
				$ids = array();
				while ($row = sqlFetchRow($result)) {
					$ids[] = $row[0];
				}
				
				if (!request('__pluginClassName__')
				 || empty($modules[request('__pluginClassName__')])) {
					echo 'Error, could not find the module for this button on this panel.';
					exit;
				}
				
				if (post('_download')) {
					$modules[request('__pluginClassName__')]->organizerPanelDownload($requestedPath, $ids, request('refinerName'), request('refinerId'));
				} else {
					$newIds = $modules[request('__pluginClassName__')]->handleOrganizerPanelAJAX($requestedPath, $ids, '', request('refinerName'), request('refinerId'));
	
					if ($newIds && !is_array($newIds)) {
						$newIds = explode(',', $newIds);
					}
				
					if ($newIds) {
						if (!is_array(session('sk_new_ids'))) {
							$_SESSION['sk_new_ids'] = array();
						}
						foreach ($newIds as $id) {
							$_SESSION['sk_new_ids'][$id] = true;
						}
					}
				
				}
				
				exit;
			
			
			} elseif ($groupBy === $idColumn && $mode == 'get_item_links') {
				//Only look for a few specific items
					//(Somewhat of a bespoke feature at the moment, it's not in the schema)
				foreach(explode(',', $_REQUEST['_get_item_links']) as $i => $id) {
					$in .= $in? ", " : "IN (";
					$in .= is_numeric($id)? (int) $id : "'". sqlEscape($id). "'";
				}
				
			} elseif ($groupBy === $idColumn && $mode == 'get_item_name') {
				if (isset($_REQUEST['_item'])) {
					foreach(explode(',', $_REQUEST['_item']) as $i => $id) {
						$in .= $in? ", " : "IN (";
						$in .= is_numeric($id)? (int) $id : "'". sqlEscape($id). "'";
					}
				}
			
			} elseif ($hierarchyColumn && (isset($_REQUEST['_openItemsInHierarchy']) || isset($_REQUEST['_openToItemInHierarchy']))) {
				$openItemsInHierarchy = array();
				
				//Display every item in the hierarchy that is open, and their parents.
				if (!empty($_REQUEST['_openItemsInHierarchy'])) {
					foreach (explode(',', $_REQUEST['_openItemsInHierarchy']) as $id) {
						if ($id) {
							$openItemsInHierarchy[$id] = true;
						}
					}
				
				//Alternately, if this is the first load, we might know an id that we wish to display,
				//but be unsure as to what its parents are
				} elseif (!empty($_REQUEST['_openToItemInHierarchy'])) {
					
					$limit = 30;
					$id = $_REQUEST['_openToItemInHierarchy'];
					
					//Look up the selected item's parent, and that's parent, and so on
					//until we reach the top
					do {
						$sql = "
							SELECT ". $hierarchyColumn. "
							FROM ". $tags['db_items']['table']. "
							WHERE ". $idColumn. " = ";
					
						if (is_numeric($id)) {
							$sql .= (int) $id;
						} else {
							$sql .= "'". sqlEscape($id). "'";
						}
					
						if (($result = sqlSelect(addConstantsToString($sql)))
						 && ($row = sqlFetchRow($result))
						 && ($row[0])
						 && (!isset($openItemsInHierarchy[$row[0]]))) {
							$id = $row[0];
							$openItemsInHierarchy[$id] = true;
						} else {
							break;
						}
					} while (--$limit > 0);
				}
				
				//Always fetch the top level items, i.e. things with a 0 or null parent id
				$whereStatement .= "
					AND (". $hierarchyColumn. " IS NULL
				     OR ". $hierarchyColumn. " IN (0";
				
				//Also fetch the items that are parents of any item that is open
				foreach ($openItemsInHierarchy as $id => $dummy) {
					$whereStatement .= ", ". (is_numeric($id)? (int) $id : "'". sqlEscape($id). "'");
				}
				
				$whereStatement .= "))";
				
			
			} else {
				//Get a count of all the rows, and get each id in the correct order
				$sql = "
					SELECT ". $idColumn;
				
				if ($hierarchyColumn) {
					$sql .= ", ". $hierarchyColumn;
				}
				
				$sql .= "
					FROM ". $tags['db_items']['table'];
			
				foreach ($sortExtraTables as $join => $dummy) {
					$sql .= "
						". $join;
				}
				
				$sql .= "
					". $whereStatement. "
					GROUP BY ". $groupBy. "
					ORDER BY ". $orderBy;
				
				$storekeeperQueryIds = addConstantsToString($sql);
				$result = sqlSelect($storekeeperQueryIds);
				
				if (!$debugMode) {
					unset($storekeeperQueryIds);
				}
				
				//For panel requests that are part of a queue, don't attempt to fetch any ids, just get a count.
				if (get('_queued')) {
					$count = sqlNumRows($result);
				
				//Otherwise get the list of ids in the correctly sorted order
				} else {
					$count = 0;
					$tags['__item_sort_order__'] = array();
					
					while ($row = sqlFetchRow($result)) {
						++$count;
						
						if ($encodeItemIdForStorekeeper) {
							$row[0] = encodeItemIdForStorekeeper($row[0]);
						}
						
						$tags['__item_sort_order__'][] = $row[0];
					}
					
					//If "_limit" is in the request, this means that server side sorting/pagination is being used
					if (get('_limit')) {
						//Apply pagination using the limit
						$start = (int) get('_start');
					
						if ($start >= $count) {
							$start = 0;
						}
					
						//If we are using pagination, and have had specific item(s) requested, only show the page that item is on.
						//In the case of a multiple selection, show the earliest page with items on
						if (isset($_GET['_item'])) {
							$pos = false;
							foreach (explode(',', $_GET['_item']) as $item) {
								$itemPos = array_search($item, $tags['__item_sort_order__']);
							
								if ($itemPos !== false && ($pos === false || $itemPos < $pos)) {
									$pos = $itemPos;
								}
							}
						
							//Change the start position appropriately
							if ($pos !== false) {
								$start = $pos - ($pos % (int) get('_limit'));
							}
						}
					
						//Set which page this should be
						$tags['__page__'] = 1 + (int) ($start / (int) get('_limit'));
						$stop = $start + (int) get('_limit');
					
						$startV = $start;
						$stopV = $stop;
						//Don't sent the whole collection of ids if we are using server-side pagination.
						//But add an id either side to help with forward/back buttons in close-up view
						if ($start > 0) {
							--$start;
						}
						if ($stop < $count) {
							++$stop;
						}
					
						$new__item_sort_order__ = array();
						for ($i = $start; $i < $stop; ++$i) {
							if (isset($tags['__item_sort_order__'][$i]) && $tags['__item_sort_order__'][$i] !== null) {
								$new__item_sort_order__[$i] = $tags['__item_sort_order__'][$i];
							} else {
								break;
							}
						
							if ($i >= $startV && $i < $stopV) {
								$in .= $in? ", " : "IN (";
							
								$thisId = $tags['__item_sort_order__'][$i];
								if ($encodeItemIdForStorekeeper) {
									$thisId = decodeItemIdForStorekeeper($thisId);
								}
							
								if (is_numeric($thisId)) {
									$in .= (int) $thisId;
								} else {
									$in .= "'". sqlEscape($thisId). "'";
								}
							}
						}
					
						//We don't need to send the ids of every single item to the client
						unset($tags['__item_sort_order__']);
						$tags['__item_sort_order__'] = $new__item_sort_order__;
						unset($new__item_sort_order__);
					
					//A simplier version of the above that doesn't worry about pagination
					} else {
						foreach ($tags['__item_sort_order__'] as $i => $thisId) {
							
							$in .= $in? ", " : "IN (";
						
							if ($encodeItemIdForStorekeeper) {
								$thisId = decodeItemIdForStorekeeper($thisId);
							}
						
							if (is_numeric($thisId)) {
								$in .= (int) $thisId;
							} else {
								$in .= "'". sqlEscape($thisId). "'";
							}
						}
					}
					
					//Flag if no rows matched the search query
					if (!$in) {
						$noResults = true;
					}
					
					//Remove the $whereStatement as it's not needed as well as an IN() statement
					$whereStatement = "WHERE TRUE";
				}
			}
			
			//When I do the work to add a new type of CSV export, the code to handle it should probably go here!
			
			//if (request('new_csv_mode') {
			//	foreach ($tags['__item_sort_order__'] as $id) {
			//		some_module::some_function($requestedPath, $id);
			//	}
			//} else
			
			if (!get('_queued')) {
				
				//If we've not been using pagination, count the number of items
				if (!$in) {
					$count = 0;
				}
				
				//If we know there will be no results, don't actually bother doing the query
				if (!$noResults) {
					if ($in) {
						$in .= ")";
					
						define('SQL_IN', $in);
					
						$in = "AND ". $idColumn. " ". $in;
				
					} else {
						define('SQL_IN', '');
					}
				
				
					//Get all of the rows on the current page
					$sql = "
						SELECT
							". $idColumn. 
							$columns;
				
					if ($hierarchyColumn) {
						$sql .= ", ". $hierarchyColumn;
					}
				
					$sql .= "
						FROM ". $tags['db_items']['table'];
				
					foreach ($sortExtraTables as $join => $dummy) {
						$sql .= "
							". $join;
					}
				
					foreach ($extraTables as $join => $dummy) {
						if (!isset($sortExtraTables[$join])) {
							$sql .= "
								". $join;
						}
					}
				
					$sql .= "
						". $whereStatement. "
						". $in. "
						GROUP BY ". $groupBy;
				
					//In XML mode, we need to make sure we add the order-by logic in when running the query to get the data
					//In normal mode, we only need to order things when applying pagination logic as there is client-side sorting for the actual data
					if ($mode == 'xml') {
						$sql .= "
						ORDER BY ". $orderBy;
					}
				
					//Loop through the results adding them into the items array (or alternately into the CSV file for CSV exports)
					$storekeeperQueryDetails = addConstantsToString($sql);
				
					if ($debugMode) {
						displayDebugMode($staticTags, $modules, $moduleFilesLoaded, $tagPath, $storekeeperQueryIds, $storekeeperQueryDetails);
						exit;
					}
					$result = sqlSelect($storekeeperQueryDetails);
					unset($storekeeperQueryDetails);
				
					while ($row = sqlFetchRow($result)) {
						if (!$in) {
							++$count;
						}
					
						$id = $row[$i = 0];
					
						if ($encodeItemIdForStorekeeper) {
							$id = encodeItemIdForStorekeeper($id);
						}
					
						$tags['items'][$id] = array();
					
						foreach ($tags['columns'] as $colName => &$col) {
							if (is_array($col) && !empty($col['db_column'])) {
								if ($col['db_column'] != 'NULL') {
									$tags['items'][$id][$colName] = $row[++$i];
								}
							}
						}
						
						//If we're doing a lazy load, we need to look up whether
						//an item has children so we know whether to show a "+" next to it or not
						if ($hierarchyColumn && (isset($_REQUEST['_openItemsInHierarchy']) || isset($_REQUEST['_openToItemInHierarchy']))) {
							if (!isset($tags['__item_parents__'][$row[0]])) {
								$csql = "
									SELECT 1
									FROM ". $tags['db_items']['table']. "
									WHERE ". $hierarchyColumn. " = ". (is_numeric($row[0])? (int) $row[0] : "'". sqlEscape($row[0]). "'"). "
									LIMIT 1";
							
								$cresult = sqlQuery(addConstantsToString($csql));
								$tags['__item_parents__'][$row[0]] = (bool) sqlFetchRow($cresult);
							}
						}
					
						++$i;
						if ($hierarchyColumn && !empty($row[$i])) {
							$tags['__item_parents__'][$row[$i]] = true;
						}
					}
				}
			}
		
		} else {
			if (get('refinerName') && !empty($tags['refiners'][get('refinerName')])) {
				//allow_unauthenticated_xml_access must repeated it on a refiner 
				if (!checkPriv() && !($mode == 'xml' && engToBooleanArray($tags, 'refiners', get('refinerName'), 'allow_unauthenticated_xml_access'))) {
					echo '<!--Logged_Out-->', adminPhrase('You have been logged out.');
					exit;
				}
			}
		}
		
		//Debug mode - show the TUIX before it's been modified
		if ($debugMode) {
			displayDebugMode($staticTags, $modules, $moduleFilesLoaded, $tagPath, $storekeeperQueryIds);
			exit;
		}
		
		//Thirdly, run each modules' fill method to add formating, and some other required attributes
		foreach ($modules as $className => &$module) {
			
			//Handle the old name if it's not been changed yet
			if (method_exists($module, 'fillStorekeeper')) {
				$module->fillStorekeeper($requestedPath, $tags, request('refinerName'), request('refinerId'), $mode);
			}
			
			$module->fillOrganizerPanel($requestedPath, $tags, request('refinerName'), request('refinerId'), $mode);
		}
		
		//Set the current item count
		if ($mode != 'xml') {
			if (isset($count)) {
				$tags['__item_count__'] = $count;
			} elseif (!empty($tags['items'])) {
				$tags['__item_count__'] = count($tags['items']);
			} else {
				$tags['__item_count__'] = 0;
			}
		}
		
	
	} elseif ($type == 'admin_boxes') {
		
		if (!empty($tags['tabs']) && is_array($tags['tabs'])) {
			foreach ($tags['tabs'] as &$tab) {
				if (!empty($tab['class_name'])) {
					zenarioAJAXIncludeModule($modules, $tab, $type, $requestedPath, $settingGroup);
				}
				
				if (!empty($tab['fields']) && is_array($tab['fields'])) {
					foreach ($tab['fields'] as &$field) {
						if (!empty($field['class_name'])) {
							zenarioAJAXIncludeModule($modules, $field, $type, $requestedPath, $settingGroup);
						}
					}
				}
			}
		}
		
		//Remove anything the current admin has no access to
		$removedColumns = false;
		if ($loadDefinition) {
			zenarioParseTUIX2($tags, $removedColumns, $type, $requestedPath, $mode);
		}
		$values = array();
		
		//Debug mode - show the TUIX before it's been modified
		if ($debugMode) {
			displayDebugMode($staticTags, $modules, $moduleFilesLoaded, $tagPath);
			exit;
		
		//Special logic for Validating and Saving
		} elseif (!post('_fill')) {
			$doSave = false;
			$doFormat = true;
			
			if (post('_read_values')) {
				//Given the JSON object for an Admin Box, strip everything out and just return the tabs/values
				$fields = array();
				$values = array();
				$changes = array();
				readAdminBoxValues($tags, $fields, $values, $changes, $filling = false, $resetErrors = false, $preDisplay = false);
				
				//Values need to be in a 2d array format here
				$values2d = array();
				if (!empty($tags['tabs']) && is_array($tags['tabs'])) {
					foreach ($tags['tabs'] as $tabName => &$tab) {
						if (is_array($tab) && !empty($tab['fields']) && is_array($tab['fields'])) {
							$values2d[$tabName] = array();
							foreach ($tab['fields'] as $fieldName => &$field) {
								if (isset($values[$tabName. '/'. $fieldName])) {
									$values2d[$tabName][$fieldName] = $values[$tabName. '/'. $fieldName];
								}
							}
						}
					}
				}
				
				jsonEncodeForceObject($values2d);
				exit;
				
			} else if (post('_validate') || post('_save') || post('_download')) {
				//Take the current state of the box as a JSON object, and validate it
				
				//Create a (read only) shortcut array to the values
				$fields = array();
				$values = array();
				$changes = array();
				readAdminBoxValues($tags, $fields, $values, $changes, $filling = false, $resetErrors = true, $preDisplay = false);
				
				//Apply standard validation formats
				
				//Loop through the tabs that are in edit mode
				if (!empty($tags['tabs']) && is_array($tags['tabs'])) {
					foreach ($tags['tabs'] as $tabName => &$tab) {
						if (engToBooleanArray($tab, 'edit_mode', 'on')) {
							
							//Loop through each field, looking for fields with validation set
							if (isset($tags['tabs'][$tabName]['fields']) && is_array($tags['tabs'][$tabName]['fields'])) {
								foreach ($tags['tabs'][$tabName]['fields'] as $fieldName => &$field) {
									if (empty($field['validation'])) {
										continue;
									}
									
									if (!isset($values[$tabName. '/'. $fieldName])) {
										$fieldValue = '';
										$notSet = true;
									} else {
										$fieldValue = (string) $values[$tabName. '/'. $fieldName];
										$notSet = !trim($fieldValue);
									}
									
									//Check for required fields
									if (($msg = arrayKey($field['validation'], 'required')) && $notSet) {
										$field['error'] = $msg;
									
									//Check for fields that are required if not hidden. (Note that it is the user submitted data from the client
									//which determines whether a field was hidden.)
									} elseif (($msg = arrayKey($field['validation'], 'required_if_not_hidden'))
										   && !engToBooleanArray($tab, 'hidden') && !engToBooleanArray($field, 'hidden')
										   //&& !engToBooleanArray($tab, '_was_hidden_before')
										   && !engToBooleanArray($field, '_was_hidden_before')
										   && $notSet
									) {
										$field['error'] = $msg;
									
									//If a field was not required, do not run any further validation logic on it if it is empty 
									} elseif ($notSet) {
										continue;
									
									} elseif (($msg = arrayKey($field['validation'], 'email')) && !validateEmailAddress($fieldValue)) {
										$field['error'] = $msg;
									
									} elseif (($msg = arrayKey($field['validation'], 'emails')) && !validateEmailAddress($fieldValue, true)) {
										$field['error'] = $msg;
									
									} elseif (($msg = arrayKey($field['validation'], 'no_spaces')) && preg_replace('/\S/', '', $fieldValue)) {
										$field['error'] = $msg;
									
									} elseif (($msg = arrayKey($field['validation'], 'numeric')) && !is_numeric($fieldValue)) {
										$field['error'] = $msg;
									
									} elseif (($msg = arrayKey($field['validation'], 'screen_name')) && !validateScreenName($fieldValue)) {
										$field['error'] = $msg;
									
									} else {
										//Check validation rules for file pickers
										$mbgip = !empty($field['validation']['must_be_gif_ico_or_png']);
										$mbgjp = !empty($field['validation']['must_be_gif_jpg_or_png']);
										$mbgp = !empty($field['validation']['must_be_gif_or_png']);
										$mbi = !empty($field['validation']['must_be_ico']);
										
										if ($mbgip || $mbgjp || $mbgp || $mbi) {
											
											//These validation rules should work for multiple file pickers, so we'll need to
											//split by a comma and validate each file separately
											foreach (explode(',', $fieldValue) as $file) {
												
												//If this file has just been picked, we'll need to check it from the disk
												if ($filepath = getPathOfUploadedFileInCacheDir($file)) {
													$mimeType = documentMimeType($filepath);
												
												//Otherwise look for it in the files table
												} else {
													$mimeType = getRow('files', 'mime_type', $file);
												}
												
												//Check all of the possible rules for image validation.
												//Stop checking image validation rules for this field as soon
												//as we find one picked file that doesn't match one rule
												if ($mbgip
												 && $mimeType != 'image/gif'
												 && $mimeType != 'image/png'
												 && $mimeType != 'image/vnd.microsoft.icon'
												 && $mimeType != 'image/x-icon') {
													$field['error'] = $field['validation']['must_be_gif_ico_or_png'];
													break;
												
												} else
												if ($mbgjp
												 && $mimeType != 'image/gif'
												 && $mimeType != 'image/jpeg'
												 && $mimeType != 'image/png') {
													$field['error'] = $field['validation']['must_be_gif_jpg_or_png'];
													break;
												
												} else
												if ($mbgp
												 && $mimeType != 'image/gif'
												 && $mimeType != 'image/png') {
													$field['error'] = $field['validation']['must_be_gif_or_png'];
													break;
												
												} else
												if ($mbi
												 && $mimeType != 'image/vnd.microsoft.icon'
												 && $mimeType != 'image/x-icon') {
													$field['error'] = $field['validation']['must_be_ico'];
													break;
												}
											}
										}
									}
								}
							}
						}
					}
				}
				
				
				//Apply the modules' specific validation
				foreach ($modules as $className => &$module) {
					$module->validateAdminBox($requestedPath, $settingGroup, $tags, $fields, $values, $changes, (bool) post('_save'));
				}
				
				//If the Admin is trying to save, and the box was valid, fire the save methods
				if (post('_save') || post('_download')) {
					
					//Check if there are any errors
					if (!empty($tags['tabs']) && is_array($tags['tabs'])) {
						$doSave = true;
						foreach ($tags['tabs'] as &$tab) {
							if (!empty($tab['errors']) && is_array($tab['errors'])) {
								foreach ($tab['errors'] as $error) {
									if ($error) {
										$doSave = false;
										break 2;
									}
								}
							}
							
							if (!empty($tab['fields']) && is_array($tab['fields'])) {
								foreach ($tab['fields'] as &$field) {
									if (!empty($field['error'])) {
										$doSave = false;
										break 2;
									}
								}
							}
						}
					}
					
					if ($doSave) {
						if (!post('_download')) {
							echo '<!--Valid-->';
							$commentMade = true;
						}
						
						$download =
							engToBooleanArray($tags, 'download')
								//For backwards compatability with old code
								|| engToBooleanArray($tags, 'confirm', 'download');
						
						//Check if a confirmation is needed
						if (engToBooleanArray($tags, 'confirm', 'show') && !(post('_confirm') || post('_download'))) {
							echo '<!--Confirm-->';
							$commentMade = true;
							
						} else if ($download && !post('_download')) {
							echo '<!--Download-->';
							$commentMade = true;
							$doFormat = post('_save_and_continue');
							
						} else {
							$fields = array();
							$values = array();
							$changes = array();
							readAdminBoxValues($tags, $fields, $values, $changes, $filling = false, $resetErrors = false, $preDisplay = false);
							
							foreach ($modules as $className => &$module) {
								$module->saveAdminBox($requestedPath, $settingGroup, $tags, $fields, $values, $changes);
							}
							
							//If there are custom fields, attempt to save them
							if (!empty($tags['key']['id'])) {
								if ($dataset = getRow('custom_datasets', true, array('extends_admin_box' => $requestedPath))) {
									
									if (!$dataset['edit_priv'] || checkPriv($dataset['edit_priv'])) {
									
										$record = array();
										//Load the created custom fields
										foreach (getRowsArray(
											'custom_dataset_fields',
											true,
											array('dataset_id' => $dataset['id'], 'is_system_field' => 0)
										) as $cfield) {
											$cFieldName = '__custom_field_'. $cfield['id'];
										
											if (!empty($tags['tabs'][$cfield['tab_name']]['edit_mode']['on'])
											 && isset($values[$cfield['tab_name']. '/'. $cFieldName])) {
											
												//Child fields should be blanked if their parents are not checked
												$parents = array();
												getCustomFieldsParents($cfield, $parents);
											
												if (!empty($parents)) {
													$cfield['visible_if'] = '';
													foreach ($parents as $parent) {
														if (empty($values[$parent['tab_name/field_name']])) {
															$values[$cfield['tab_name']. '/'. $cFieldName] = '';
														}
													}
												}
											
												//Checkboxes are stored in the custom_dataset_values_link table as there could be more than one of them
												if ($cfield['type'] == 'checkboxes') {
													updateDatasetCheckboxField($dataset['id'], $cfield['id'], $tags['key']['id'], $values[$cfield['tab_name']. '/'. $cFieldName]);
												
												//Otherwise store the value in an array and at the end of the loop...
												} else {
													$record[$cfield['db_column']] = $values[$cfield['tab_name']. '/'. $cFieldName];
												}
											}
										}
										
										//...update the record.
										if (!empty($record)) {
											setRow($dataset['table'], $record, $tags['key']['id']);
										}
									}
								}
							}
							
							foreach ($modules as $className => &$module) {
								$module->adminBoxSaveCompleted($requestedPath, $settingGroup, $tags, $fields, $values, $changes);
							}
						
							if ($download) {
								//Bugfix for IE 6
								if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 6') !== false) {
									session_cache_limiter(false);
									header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
									header('Pragma: public');
								}
							
								foreach ($modules as $className => &$module) {
									$module->adminBoxDownload($requestedPath, $settingGroup, $tags, $fields, $values, $changes);
								}
								exit;
						
							} else {
								echo '<!--Saved-->';
								$commentMade = true;
								$doFormat = post('_save_and_continue');
							}
						}
					}
				}
			}
			
			//If we're going to wind up displaying the box again, format it again
			if ($doFormat) {
				$fields = array();
				$values = array();
				$changes = array();
				readAdminBoxValues($tags, $fields, $values, $changes, $filling = false, $resetErrors = false, $preDisplay = true);
				
				foreach ($modules as $className => &$module) {
					$module->formatAdminBox($requestedPath, $settingGroup, $tags, $fields, $values, $changes);
				}
			}
			
		} else {
			//Logic for initialising an Admin Box
			if (!empty($tags['key']) && is_array($tags['key'])) {
				foreach ($tags['key'] as $key => &$value) {
					if (get($key)) {
						$value = get($key);
					}
				}
			}
			
			//When opening an Admin Box, accept an array of arrays for initial values for fields
			$valuesWithFieldsMissing = array();
			if (!empty($_POST['_values']) && ($values = json_decode($_POST['_values'], true)) && (is_array($values))) {
				//If it is a valid array, loop through the tabs/fields in the input
				foreach ($values as $tabName => &$tab) {
					if (is_array($tab)) {
						foreach ($tab as $fieldName => &$value) {
							//If this matches with a field in the description, set the value
							if (isset($tags['tabs'][$tabName]['fields'][$fieldName])) {
								$tags['tabs'][$tabName]['fields'][$fieldName]['value'] = $value;
							
							//Otherwise note down that the field was missing, and remember the value for later
							} else {
								if (!isset($valuesWithFieldsMissing[$tabName])) {
									$valuesWithFieldsMissing[$tabName] = array();
								}
								$valuesWithFieldsMissing[$tabName][$fieldName] = $value;
							}
						}
					}
				}
			}
			
			$fields = array();
			$values = array();
			$changes = array();
			readAdminBoxValues($tags, $fields, $values, $changes, $filling = true, $resetErrors = false, $preDisplay = true);
			
			//Run the fill admin box method
			foreach ($modules as $className => &$module) {
				$module->fillAdminBox($requestedPath, $settingGroup, $tags, $fields, $values);
			}
			
			//Look for any custom fields/tabs
			if ($dataset = getRow('custom_datasets', true, array('extends_admin_box' => $requestedPath))) {
				
				//Define the array of tabs if it's not already defined
				if (!isset($tags['tabs'])
				 || !is_array($tags['tabs'])) {
					$tags['tabs'] = array();
				} else {
					//This code used to try and work out which tab came first, and ensure it stayed first
					//even if someone customising the dataset tried to move it. This wasn't working too smoothly
					//so I have disabled it for now and we will review whether it was needed in the first place.
					//$firstTabName = false;
					//foreach ($tags['tabs'] as $tabName => &$tab) {
					//	$firstTabName = $tabName;
					//	break;
					//}
				}
				
				//Look for customised tabs
				foreach(getRowsArray('custom_dataset_tabs', true, array('dataset_id' => $dataset['id'])) as $ctab) {
					
					//Create an entry for that tab if one was not already created
					if (!isset($tags['tabs'][$ctab['name']])
					 || !is_array($tags['tabs'][$ctab['name']])) {
						if (!$dataset['edit_priv'] || checkPriv($dataset['edit_priv'])) {
							$tags['tabs'][$ctab['name']] =
								array('edit_mode' => array('enabled' => true));
						}
					}
					
					//Set properties
					//if ($ctab['ord'] && $ctab['name'] != $firstTabName) {
					if ($ctab['ord']) {
						$tags['tabs'][$ctab['name']]['ord'] = $ctab['ord'];
					}
					if ($ctab['label']) {
						$tags['tabs'][$ctab['name']]['label'] = $ctab['label'];
					}
					
					//If a tab has a parent field set, make it only visible if the parent is visible and checked
					$parents = array();
					getCustomTabsParents($ctab, $parents);
					
					if (!empty($parents)) {
						$tags['tabs'][$ctab['name']]['visible_if'] = '';
						foreach ($parents as $parent) {
							$tags['tabs'][$ctab['name']]['visible_if'] .=
								($tags['tabs'][$ctab['name']]['visible_if']? ' && ' : '').
								"zenarioAB.value('". jsEscape($parent['field_name']). "', '". jsEscape($parent['tab_name']). "') == 1";
							
							//Attempt to set the redraw_onchange property for that field if it is a core field
							//(This will miss custom fields, so we'll need to set them later)
							if (!empty($tags['tabs'][$parent['tab_name']]['fields'][$parent['field_name']])
							 && is_array($tags['tabs'][$parent['tab_name']]['fields'][$parent['field_name']])) {
								$tags['tabs'][$parent['tab_name']]['fields'][$parent['field_name']]['redraw_onchange'] = true;
							}
						}
					}
				}
				unset($ctab);
				
				if (!$dataset['view_priv'] || checkPriv($dataset['view_priv'])) {
					//Attempt to load current values
					$record = false;
					if (!empty($tags['key']['id'])) {
						$record = getRow($dataset['table'], true, $tags['key']['id']);
					}
					
					//Add custom fields
					foreach (getRowsArray(
						'custom_dataset_fields',
						true,
						array('dataset_id' => $dataset['id'], 'is_system_field' => 0)
					) as $cfield) {
						$cFieldName = '__custom_field_'. $cfield['id'];
					
						if (!isset($tags['tabs'][$cfield['tab_name']])
						 || !is_array($tags['tabs'][$cfield['tab_name']])) {
							continue;
						}
						if (!isset($tags['tabs'][$cfield['tab_name']]['fields'])
						 || !is_array($tags['tabs'][$cfield['tab_name']]['fields'])) {
							$tags['tabs'][$cfield['tab_name']]['fields'] = array();
						}
					
						if (in($cfield['type'], 'select', 'centralised_select')) {
							$cfield['empty_value'] = adminPhrase(' -- Select -- ');
						}
					
						if (in($cfield['type'], 'checkboxes', 'radios', 'centralised_radios', 'select', 'centralised_select')) {
							$cfield['values'] = getDatasetFieldLOV($cfield, false);
						}
					
						if ($cfield['width']) {
							$cfield['style'] = 'width: '. $cfield['width']. 'em;';
						}
						if ($cfield['height']) {
							$cfield['rows'] = $cfield['height'];
						}
					
						if ($cfield['validation']
						 && $cfield['validation'] != 'none'
						 && $cfield['validation_message']) {
							$cfield['validation'] = array($cfield['validation'] => $cfield['validation_message']);
						} else {
							$cfield['validation'] = array();
						}
					
						if ($cfield['required']
						 && $cfield['required_message']) {
							$cfield['validation']['required'] = $cfield['required_message'];
						}
					
						//Set the value of the field.
						if (!empty($tags['key']['id'])) {
							if ($cfield['type'] == 'checkboxes' && !empty($cfield['values'])) {
								//Checkbox values are stored in the custom_dataset_values_link table
								$cfield['value'] =
									inEscape(
										getRowsArray(
											'custom_dataset_values_link',
											'value_id',
											array(
												'linking_id' => $tags['key']['id'],
												'value_id' => array_keys($cfield['values']))),
										'numeric');
						
							} elseif ($record && isset($record[$cfield['db_column']])) {
								//Otherwise use the value from the record
								$cfield['value'] = $record[$cfield['db_column']];
							}
						}
					
					
						//Make child fields only visible if their parents are visible and checked
						$parents = array();
						getCustomFieldsParents($cfield, $parents);
					
						if (!empty($parents)) {
							$cfield['visible_if'] = '';
							foreach ($parents as $parent) {
								$cfield['visible_if'] .=
									($cfield['visible_if']? ' && ' : '').
									"zenarioAB.value('". jsEscape($parent['field_name']). "', '". jsEscape($parent['tab_name']). "') == 1";
							
								//Attempt to set the redraw_onchange property for that field if it is on the same tab as this one
								//(This may miss custom fields, so we'll need to set any we've missed below)
								if (!empty($tags['tabs'][$parent['tab_name']]['fields'][$parent['field_name']])
								 && is_array($tags['tabs'][$parent['tab_name']]['fields'][$parent['field_name']])
								 && $parent['tab_name'] == $cfield['tab_name']) {
									$tags['tabs'][$parent['tab_name']]['fields'][$parent['field_name']]['redraw_onchange'] = true;
								}
							}
						}
					
						//If a field has children, be sure to redraw the form on change to display them
						$children = array();
						getCustomFieldsChildren($cfield, $children);
					
						if (!empty($children)) {
							$cfield['redraw_onchange'] = true;
						}
						
						if (!$dataset['edit_priv'] || checkPriv($dataset['edit_priv'])) {
						} else {
							$cfield['read_only'] = true;
						}
						
						$cfield['class'] = 'zab_custom_field zab_custom_field__'. $cfield['type'];
						$cfield['row_class'] = 'zab_custom_field_row zab_custom_field_row__'. $cfield['type'];
						$cfield['label_class'] = 'zab_custom_field_label zab_custom_field_label__'. $cfield['type'];
						
						if ($cfield['type'] == 'group') {
							$cfield['type'] = 'checkbox';
						} else {
							$cfield['type'] = str_replace('centralised_', '', $cfield['type']);
						}
						$tags['tabs'][$cfield['tab_name']]['fields'][$cFieldName] = $cfield;
					}
				}
				
				//Look for customised system fields
				foreach(getRowsArray(
					'custom_dataset_fields',
					true,
					array('dataset_id' => $dataset['id'], 'is_system_field' => 1)
				) as $cfield) {
					if (!isset($tags['tabs'][$cfield['tab_name']]['fields'][$cfield['field_name']])
					 || !is_array($tags['tabs'][$cfield['tab_name']]['fields'][$cfield['field_name']])) {
						continue;
					}
					
					//Set properties
					if ($cfield['ord']) {
						$tags['tabs'][$cfield['tab_name']]['fields'][$cfield['field_name']]['ord'] = $cfield['ord'];
					}
					if ($cfield['label']) {
						$tags['tabs'][$cfield['tab_name']]['fields'][$cfield['field_name']]['label'] = $cfield['label'];
					}
					if ($cfield['note_below']) {
						$tags['tabs'][$cfield['tab_name']]['fields'][$cfield['field_name']]['note_below'] = htmlspecialchars($cfield['note_below']);
					}
				}
				unset($cfield);
			}
			
			//If this Admin Box uses dynamic fields then these won't have been created above
			//But they might be there now, so check any missing fields again.
			foreach ($valuesWithFieldsMissing as $tabName => &$tab) {
				foreach ($tab as $fieldName => &$value) {
					if (isset($tags['tabs'][$tabName]['fields'][$fieldName])) {
						$tags['tabs'][$tabName]['fields'][$fieldName]['value'] = $value;
					}
				}
			}
			unset($valuesWithFieldsMissing);
			
			$fields = array();
			$values = array();
			$changes = array();
			readAdminBoxValues($tags, $fields, $values, $changes, $filling = true, $resetErrors = false, $preDisplay = true);
			
			foreach ($modules as $className => &$module) {
				$module->formatAdminBox($requestedPath, $settingGroup, $tags, $fields, $values, $changes);
			}
		}
		
		
		
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
		
		
		//Try to save a copy of the admin box in the cache directory
		if (($adminBoxSyncStoragePath = adminBoxSyncStoragePath($tags))
		 && (@file_put_contents($adminBoxSyncStoragePath, json_encode($tags)))) {
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
			
			$_SESSION['admin_box_sync'][$tags['_sync']['session']] = json_encode($tags);
			$tags['_sync']['cache_dir'] = false;
		}
		
		
		//Put the values back in
		foreach ($currentValues as $tabName => &$tab) {
			foreach ($tab as $fieldName => &$value) {
				$tags['tabs'][$tabName]['fields'][$fieldName]['current_value'] = $value;
			}
		}
		unset($currentValues);
		
		
		if (!empty($originalTags)) {
			$output = array();
			syncAdminBoxFromServerToClient($tags, $originalTags, $output);
			
			$tags = $output;
		}
	}

} elseif ($type == 'admin_boxes') {
	//Admin Boxes require a specific path
	echo adminPhrase('An Admin Box path was needed, but none was given.');
	exit;

} elseif ($type == 'admin_toolbar') {
	//The Admin Toolbar does not use paths, so no logic for paths
	
	//See if other modules have added toolbars/sections/buttons. If so, they'll need their own placeholder methods executing as well.
	if (isset($tags['toolbars']) && is_array($tags['toolbars'])) {
		foreach ($tags['toolbars'] as &$toolbar) {
			if (!empty($toolbar['class_name'])) {
				zenarioAJAXIncludeModule($modules, $toolbar, $type, $requestedPath, $settingGroup);
			}
		}
	}
	if (isset($tags['sections']) && is_array($tags['sections'])) {
		foreach ($tags['sections'] as &$section) {
			if (!empty($section['class_name'])) {
				zenarioAJAXIncludeModule($modules, $section, $type, $requestedPath, $settingGroup);
			}
			
			if (isset($section['buttons']) && is_array($section['buttons'])) {
				foreach ($section['buttons'] as &$button) {
					if (!empty($button['class_name'])) {
						zenarioAJAXIncludeModule($modules, $button, $type, $requestedPath, $settingGroup);
					}
				}
			}
		}
	}
	
	$removedColumns = false;
	if ($loadDefinition) {
		zenarioParseTUIX2($tags, $removedColumns, $type, '', $mode);
	}
	
	//Debug mode - show the TUIX before it's been modified
	if ($debugMode) {
		displayDebugMode($staticTags, $modules, $moduleFilesLoaded, $tagPath);
		exit;
	}
	
	//Apply the modules' specific logic
	foreach ($modules as $className => &$module) {
		$module->fillAdminToolbar($tags, (int) request('cID'), request('cType'), (int) request('cVersion'));
	}
}

//No other debug modes have currently been implemented
if ($debugMode) {
	displayDebugMode($staticTags, $modules, $moduleFilesLoaded, $tagPath);
	exit;
}


//Tidy away some organizer tags
if ($type == 'organizer') {
	//Remove anything the current admin has no access to, count each tags' children
	$removedColumns = false;
	if ($loadDefinition) {
		zenarioParseTUIX2($tags, $removedColumns, $type, $requestedPath, $mode);
	}
	
	//Don't send any SQL to the client
	if (isset($tags['items']) && is_array($tags['items']) && is_array($removedColumns)) {
		foreach ($tags['items'] as &$item) {
			foreach ($removedColumns as $unset) {
				unset($item[$unset]);
			}
		}
	}
}

//Only get the raw data when getting item data
if ($mode == 'get_item_data') {
	$tags = array(
		'items' => $tags['items'],
		'title' => $tags['title'],
		'__item_count__' => $tags['__item_count__'],
		'__item_sort_order__' => $tags['__item_sort_order__']);
	
//Item links don't need most things in the panel
//I also need to set the path for Menu Nodes
} else if ($mode == 'get_item_links') {
	$tags = array('items' => $tags['items'], 'item' => arrayKey($tags, 'item'));
	foreach ($tags['items'] as $id => &$item) {
		switch ($requestedPath) {
			case 'zenario__menu/panels/menu_nodes':
				$item = array(
					'name' => getMenuPath($id, FOCUSED_LANGUAGE_ID__NO_QUOTES, $separator = ' -> '),
					'css_class' => arrayKey($item, 'css_class'),
					'navigation_path' => arrayKey($item, 'navigation_path'));
				break;
				
			default:
				$item = array(
					'name' => arrayKey($item, 'name'),
					'css_class' => arrayKey($item, 'css_class'),
					'navigation_path' => arrayKey($item, 'navigation_path'));
		}
	}


//When just fetching an item's name, strip away everything that we don't need to calculate the item name
} elseif ($mode == 'get_item_name') {
	//Add information on the item
	$output = array('items' => $tags['items'], 'columns' => array());
	
	$tagString = '[[name]]';
	$usedcolumns = array();
	
	if (!empty($tags['default_sort_column'])) {
		$tagString = '[['. ($output['default_sort_column'] = $tags['default_sort_column']). ']]';
	}
	if (!empty($tags['label_format_for_grid_view'])) {
		$tagString .= $output['label_format_for_grid_view'] = $tags['label_format_for_grid_view'];
	}
	if (!empty($tags['label_format_for_picked_items'])) {
		$tagString .= $output['label_format_for_picked_items'] = $tags['label_format_for_picked_items'];
	}
	
	foreach (preg_split('@\[\[([^\[\]]+)\]\]@s', $tagString, -1,  PREG_SPLIT_DELIM_CAPTURE) as $i => $colName) {
		if ($i % 2) {
			$usedcolumns[$colName] = true;
		}
	}
	
	//Strip out any column that is not used in the label
	foreach ($output['items'] as &$item) {
		if (is_array($item)) {
			foreach ($item as $colName => $column) {
				if (empty($usedcolumns[$colName])) {
					unset($item[$colName]);
				}
			}
			unset($item['cell_css_classes']);
		}
	}
	
	//Add the format information for any column that has format information and is in the label tag
	if ($tagString) {
		if (isset($tags['columns']) && is_array($tags['columns'])) {
			foreach ($tags['columns'] as $colName => &$col) {
				if (!empty($usedcolumns[$colName]) && !empty($col['format'])) {
					$output['columns'][$colName] = array('format' => $col['format']);
					
					if (!empty($col['values'])) {
						$output['columns'][$colName]['values'] = $col['values'];
					}
				}
			}
		}
	}
	
	$tags = $output;
}


//Display the output, either as JSON to send to the Organizer's JavaScript engine, or as a print_r for debuggers
if ($mode == 'xml') {
	
	$xml = new XMLWriter();
	if (!$commentMade) {
		header('Content-Type: text/xml; charset=UTF-8');
	}
	
	$xml->openURI('php://output');
	$xml->startDocument('1.0', 'UTF-8');
	$xml->setIndent(4);
	$xml->startElement(ifNull(arrayKey($tags, 'xml', 'outer_tag'), 'organizer'));
	
	
	if (!empty($tags['xml']['outer_tag_attributes']) && is_array($tags['xml']['outer_tag_attributes'])) {
		foreach ($tags['xml']['outer_tag_attributes'] as $key => $value) {
			$xml->writeAttribute($key, $value);
		}
	}
	
	
	if (engToBooleanArray($tags, 'xml', 'only_show_items_tag')) {
		$tags = $tags['items'];
	} else {
		//Remove a few things we don't need for an XML feed
		unset($tags['_path_here']);
		unset($tags['view_mode']);
		unset($tags['db_items']);
		unset($tags['refiners']);
		unset($tags['default_sort_column']);
		unset($tags['columns']);
		unset($tags['item']);
		unset($tags['collection_buttons']);
		unset($tags['item_buttons']);
		unset($tags['inline_buttons']);
		unset($tags['hidden_nav']);
		unset($tags['link']);
		unset($tags['xml']);
	}
	
	
	function XMLWriterRecurse(&$xml, &$tags) {
		//Remove a few things we don't need for an XML feed
		unset($tags['back_link']);
		unset($tags['panel']);
		unset($tags['__item_count__']);
		unset($tags['ord']);
		
		foreach ($tags as $key => &$child) {
			
			if (is_array($child) && !empty($child['xml_tag_name'])) {
				$key = $child['xml_tag_name'];
				unset($child['xml_tag_name']);
				
			} elseif (is_numeric($key)) {
				$key = '_'. $key;
			}
			
			if (is_array($child)) {
				$xml->startElement($key);
				XMLWriterRecurse($xml, $child);
				$xml->endElement();
			} else {
				$xml->writeElement($key, $child);
			}
		}
	}
	
	XMLWriterRecurse($xml, $tags);
	$xml->endElement();
	$xml->endDocument();
	$xml->flush();
	
} elseif (request('_json')) {
	if (!$commentMade) {
		header('Content-Type: text/javascript; charset=UTF-8');
	}
	
	header('Content-Type: text/javascript; charset=UTF-8');
	jsonEncodeForceObject($tags);

} else {
	echo '<pre>'; print_r($tags); echo '<pre>'; 
}

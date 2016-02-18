<?php

/*
 * Copyright (c) 2016, Tribal Limited
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
								if (!empty($values[$tabName. '/'. $fieldName]) && $langIdToUse = getRow('content_items', 'language_id', array('tag_id' => $values[$tabName. '/'. $fieldName]))) {
								
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

function applyValidationFromTUIXOnTab(&$tab) {
	
	//Check if the tab is in edit mode
	if (engToBooleanArray($tab, 'edit_mode', 'on')) {
		
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
				$notSet = !(trim($fieldValue) || $fieldValue === "0");
				
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
							if ($filepath = getPathOfUploadedFileInCacheDir($file)) {
								$mimeType = documentMimeType($filepath);
							
							//Otherwise look for it in the files table
							} else {
								$mimeType = getRow('files', 'mime_type', $file);
							}
							
							$isIcon = in($mimeType, 'image/vnd.microsoft.icon', 'image/x-icon');
							$isGIFPNG = in($mimeType, 'image/gif', 'image/png');
							
							//Check all of the possible rules for image validation.
							//Stop checking image validation rules for this field as soon
							//as we find one picked file that doesn't match one rule
							if ($must_be_image && !isImage($mimeType)) {
								$field['error'] = $field['validation']['must_be_image'];
								break;
							
							} else
							if ($must_be_image_or_svg && !isImageOrSVG($mimeType)) {
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
}

//Sync updates from the client to the array stored on the server
function syncAdminBoxFromClientToServer(&$serverTags, &$clientTags, $key1 = false, $key2 = false, $key3 = false, $key4 = false, $key5 = false, $key6 = false) {
	
	$keys = array_merge(arrayValuesToKeys(array_keys($serverTags)), arrayValuesToKeys(array_keys($clientTags)));
	
	foreach ($keys as $key0 => $dummy) {
		//Only allow certain tags in certain places to be merged in
		if ((($type = 'array') && $key1 === false && $key0 == '_sync')
		 || (($type = 'value') && $key2 === false && $key1 == '_sync' && $key0 == 'storage')
		 || (($type = 'value') && $key2 === false && $key1 == '_sync' && $key0 == 'cache_dir')
		 || (($type = 'value') && $key2 === false && $key1 == '_sync' && $key0 == 'password')
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
	
	$keys = array_merge(arrayValuesToKeys(array_keys($serverTags)), arrayValuesToKeys(array_keys($clientTags)));
	
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

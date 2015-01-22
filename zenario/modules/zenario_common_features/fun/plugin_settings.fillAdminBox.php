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
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');


if (get('refiner__nest')) {
	$box['key']['instanceId'] = get('refiner__nest');
	$box['key']['nest'] = (int) get('id');
	
	$nestedItem = getNestDetails($box['key']['nest'], $box['key']['instanceId']);
	$box['key']['moduleId'] = $nestedItem['module_id'];

} elseif (!ifNull(get('instanceId'), get('id')) && get('refiner__plugin')) {
	$box['key']['instanceId'] = false;
	$box['key']['nest'] = 0;
	$box['key']['moduleId'] = get('refiner__plugin');

} else {
	$box['key']['instanceId'] = ifNull(get('instanceId'), get('id'));
	$box['key']['nest'] = (int) get('nest');
	$box['key']['moduleId'] = get('moduleId');
}


if ($box['key']['moduleId'] && $box['key']['instanceId']) {
	$module = getModuleDetails($box['key']['moduleId']);
	$instance = getPluginInstanceDetails($box['key']['instanceId']);
} elseif ($box['key']['moduleId']) {
	$module = getModuleDetails($box['key']['moduleId']);
	$instance = array('framework' => $module['default_framework'], 'css_class' => '');
} else {
	$module = $instance = getPluginInstanceDetails($box['key']['instanceId']);
	$box['key']['moduleId'] = $instance['module_id'];
}


$box['key']['isVersionControlled'] = !empty($instance['content_id']);
$box['key']['cID'] = ifNull(arrayKey($instance, 'content_id'), get('cID'), get('parent__cID'));
$box['key']['cType'] = ifNull(arrayKey($instance, 'content_type'), get('cType'), get('parent__cType'));
$box['key']['cVersion'] = ifNull(arrayKey($instance, 'content_version'), get('cVersion'));
$box['key']['slotName'] = ifNull(arrayKey($instance, 'slot_name'), get('slotName'));
$box['key']['languageId'] = ifNull(getContentLang($box['key']['cID'], $box['key']['cType']), setting('default_language'));


if ($box['key']['isVersionControlled']) {
	$box['css_class'] .= ' zenario_wireframe_plugin_settings';
} else {
	$box['css_class'] .= ' zenario_reusable_plugin_settings';
	
	if ($box['key']['nest']) {
		exitIfNotCheckPriv('_PRIV_VIEW_REUSABLE_PLUGIN');
	} elseif ($box['key']['instanceId']) {
		exitIfNotCheckPriv('_PRIV_VIEW_REUSABLE_PLUGIN');
	} else {
		exitIfNotCheckPriv('_PRIV_MANAGE_REUSABLE_PLUGIN');
	}
}

//For Nests and nested Plugins, if this is an existing instance or a Wireframe Plugin on a draft, enable the save and continue button
if ($box['key']['instanceId']
 && (empty($instance['content_id']) || isDraft($instance['content_id'], $instance['content_type'], $instance['content_version']))) {
	
	if ($box['key']['nest']
	 || (($nestablemoduleIds = getNestablemoduleIds()) && (!empty($nestablemoduleIds[$box['key']['moduleId']])))) {
		$box['save_button_message'] = adminPhrase('Save & Close');
		$box['save_and_continue_button_message'] = adminPhrase('Save & Continue');
	}
}


$canEdit = false;
if ($box['key']['isVersionControlled']) {
	if (isDraft($status = getContentStatus($box['key']['cID'], $box['key']['cType'])) || $box['key']['nest']) {
		$canEdit = checkPriv('_PRIV_EDIT_DRAFT', $box['key']['cID'], $box['key']['cType'], $box['key']['cVersion']);
	} else {
		$canEdit =
			$status == 'published'
		 && checkPriv('_PRIV_CREATE_REVISION_DRAFT')
		 && $box['key']['cVersion'] == getLatestVersion($box['key']['cID'], $box['key']['cType']);
	}

} else {
	if ($box['key']['nest']) {
		$canEdit = checkPriv('_PRIV_MANAGE_REUSABLE_PLUGIN');
	} else {
		$canEdit = checkPriv('_PRIV_MANAGE_REUSABLE_PLUGIN');
	}
}


switch ($path) {
	case 'plugin_settings':

		if (empty($instance['instance_name'])) {
			//Load the XML description for this plugin, and get the default instance name
			$desc = false;
			if (loadModuleDescription($module['class_name'], $desc)) {
				$instanceName = $desc['default_instance_name'];
			} else {
				$instanceName = '';
			}
		} else {
			$instanceName = $instance['instance_name'];
		}

		//If this is a new instance, try and ensure that the name we are suggesting is unique
		if (!$box['key']['instanceId']) {
			$sql = "
				SELECT COUNT(*)
				FROM  ". DB_NAME_PREFIX. "plugin_instances
				WHERE name LIKE '". sqlEscape($instanceName). "%'";
			$result = sqlQuery($sql);
			$row = sqlFetchRow($result);
			if ($row[0]) {
				$instanceName .= ' ('. ($row[0] + 1). ')';
			}
		}
		
		$values['first_tab/instance_name'] = $instanceName;

		$valuesInDB = array();
		if ($box['key']['instanceId']) {
			$sql = "
				SELECT name, `value`
				FROM ". DB_NAME_PREFIX. "plugin_settings
				WHERE instance_id = ". (int) $box['key']['instanceId']. "
				  AND nest = ". (int) $box['key']['nest'];
			$result = sqlQuery($sql);
	
			while($row = sqlFetchAssoc($result)) {
				$valuesInDB[$row['name']] = $row['value'];
			}
		}

		foreach ($box['tabs'] as $tabName => &$tab) {
			if (!isInfoTag($tabName)) {
				if (!$canEdit) {
					$tab['edit_mode'] = array('enabled' => false);
				} else {
					if (empty($tab['edit_mode'])) {
						$tab['edit_mode'] = array();
					}
			
					$tab['edit_mode']['enabled'] = true;
					$tab['edit_mode']['always_on'] = true;
					$tab['edit_mode']['enable_revert'] = true;
				}
		
				foreach ($tab['fields'] as $fieldName => &$field) {
					if (!isInfoTag($fieldName)) {
						if (!empty($field['plugin_setting']['name']) && isset($valuesInDB[$field['plugin_setting']['name']])) {
							$field['value'] = $valuesInDB[$field['plugin_setting']['name']];
						}
					}
				}
			}
		}


		//Even if there are no settings for this plugin, then we still want to have
		//one or two fixed fields at the start; e.g. the name of the instance as the very first field
		//Nested modules have a little more information
		if ($box['key']['nest']) {
			$fields['first_tab/instance_name']['hidden'] = true;

		} else {
			if ($box['key']['instanceId']) {
				$fields['first_tab/instance_name']['read_only'] = true;
			}
			if ($box['key']['isVersionControlled']) {
				$fields['first_tab/instance_name']['hidden'] = true;
			}
		}
		
		/*
		//Check to see if there are any non-hidden fields, and any editable fields, on the first tab
		$shownFields = false;
		$editableFields = false;
		foreach ($box['tabs']['first_tab']['fields'] as $tagName => &$field) {
			if (!isInfoTag($tagName)) {
				if (!engToBooleanArray($field, 'hidden')) {
					$shownFields = true;
				}
				if (!engToBooleanArray($field, 'read_only')) {
					$editableFields = true;
				}
			}
		}

		//If not, don't show it
		if (!$shownFields) {
			$box['tabs']['first_tab']['hidden'] = true;

		} elseif (!$editableFields) {
			$box['tabs']['first_tab']['edit_mode']['enabled'] = false;
		}
		*/
		
		break;


	
	
	case 'plugin_css_and_framework':
		
		$instanceName = $instance['instance_name'];
		
		if ($canEdit) {
			$box['tabs']['last_tab']['edit_mode'] = array('enabled' => true);
		}

		//Load the values from the database
		if ($box['key']['nest']) {
			$sql = "
				SELECT name_or_title, framework, css_class
				FROM ". DB_NAME_PREFIX. "nested_plugins
				WHERE id = ". (int) $box['key']['nest'];
	
			$result = sqlQuery($sql);
			$row = sqlFetchAssoc($result);
			$values['last_tab/framework'] = $framework = $row['framework'];
			$values['last_tab/css_class'] = $row['css_class'];

		} else {
			$values['last_tab/framework'] = $framework = $instance['framework'];
			$values['last_tab/css_class'] = $instance['css_class'];
		}


		//Look for frameworks
		$fields['last_tab/framework']['values'] = listModuleFrameworks($module['class_name']);

		if (!empty($fields['last_tab/framework']['values'])) {
			if ($module['default_framework']
			 && isset($fields['last_tab/framework']['values'][$module['default_framework']])) {
				$fields['last_tab/framework']['values'][$module['default_framework']]['label'] .=
					adminPhrase(' (default)');
			}
			if (!isset($fields['last_tab/framework']['values'][$framework])) {
				$fields['last_tab/framework']['values'][$framework] =
					array('ord' => 0, 'label' => adminPhrase('[[framework]] (missing from filesystem)', array('framework' => $framework)));
			}

		} else {
			$fields['last_tab/framework']['hidden'] =
			$fields['last_tab/framework_source']['hidden'] = true;
		}
		
		
		//Attempt to load a list of CSS Class Names from the description file of the current Skin
		//to add choices in for the CSS Class Picker.
		//Do do this we need to get the Skin Id from the Layout Id - and thus need to know the Layout Id.
		//If we know which Content Item we're currently on somehow, we can look this up from the cID/cType/cVersion.
		//Otherwise, try looking at the Plugin placement tables for either a Layout or a Content Item.
		$skin = $skinDescriptionFilePath = false;
		if (($box['key']['cID']
		  && $box['key']['cVersion']
		  && $layoutId = contentItemTemplateId($box['key']['cID'], $box['key']['cType'], $box['key']['cVersion']))
		 || (request('cID')
		  && request('cVersion')
		  && $layoutId = contentItemTemplateId(request('cID'), request('cType'), request('cVersion')))
		 || (request('parent__cID')
		  && request('parent__cVersion')
		  && $layoutId = contentItemTemplateId(request('parent__cID'), request('parent__cType'), request('parent__cVersion')))
		 || ($box['key']['instanceId']
		  && $layoutId = getRow('plugin_layout_link', 'layout_id', array('instance_id' => $box['key']['instanceId'])))
		 || ($box['key']['instanceId']
		  && ($plugin_item_link = getRow('plugin_item_link', array('content_id', 'content_type', 'content_version'), array('instance_id' => $box['key']['instanceId'])))
		  && ($layoutId = contentItemTemplateId($plugin_item_link['content_id'], $plugin_item_link['content_type'], $plugin_item_link['content_version'])))
		) {
			if ($skinId = templateSkinId($layoutId)) {
				if ($skin = getSkinFromId($skinId)) {
					$skinDescriptionFilePath = skinDescriptionFilePath($skin['family_name'], $skin['name'], true);
				}
			}
	
		}
		
		//Load all of the possible values, and add a "default" and a "custom" value
		$fields['last_tab/css_class']['values']['#default#']['label'] = $module['css_class_name']. '__default_style';
		
		if ($skin) {
			$i = 1;
			foreach (getSkinCSSClassNames($skin, 'plugin', $module['css_class_name']) as $cssClass => $label) {
				$fields['last_tab/css_class']['values'][$cssClass] = array('label' => $label, 'ord' => ++$i);
			}
		}
		
		foreach ($fields['last_tab/css_class']['values'] as &$value) {
			$value['label'] = $module['css_class_name']. ' '. $value['label'];
		}
		
		//If no CSS class was entered, pick the "default" value
		if ($values['last_tab/css_class'] == '') {
			$values['last_tab/css_class'] = '#default#';
		
		//If the value is not in the list, pick the "custom" value
		} elseif (empty($fields['last_tab/css_class']['values'][$values['last_tab/css_class']])) {
			$values['last_tab/css_class_custom'] = $values['last_tab/css_class'];
			$values['last_tab/css_class'] = '#custom#';
		}
		
		if ($skinDescriptionFilePath) {
			$fields['last_tab/css_class']['note_below'] =
				adminPhrase('Add CSS classes by picking existing ones from the list or by writing your own in the text box. A designer can add new styles to this list by editing the [[path]] file.', array('path' => $skinDescriptionFilePath));
		} else {
			$fields['last_tab/css_class']['note_below'] =
				adminPhrase('Add CSS classes by picking existing ones from the list or by writing your own in the text box.');
		}
		
		
		break;
}







if ($box['key']['nest'] && $box['key']['isVersionControlled']) {
	$box['title'] = 
		adminPhrase('Editing a plugin of the "[[module]]" module, in the [[nest]]',
			array('module' => htmlspecialchars(getModuleDisplayName($box['key']['moduleId'])),
				  'nest' => htmlspecialchars($instanceName)));

} elseif ($box['key']['nest']) {
	$box['title'] = 
		adminPhrase('Editing a plugin of the "[[module]]" module, in the nest "[[nest]]"',
			array('module' => htmlspecialchars(getModuleDisplayName($box['key']['moduleId'])),
				  'nest' => htmlspecialchars($instanceName)));

} elseif ($box['key']['isVersionControlled']) {
	$box['title'] = 
		adminPhrase('Editing the [[module]]',
			array('module' => htmlspecialchars(getModuleDisplayName($box['key']['moduleId']))));

} elseif ($box['key']['instanceId']) {
	$box['title'] = 
		adminPhrase('Editing a plugin of the module "[[module]]"',
			array('module' => htmlspecialchars(getModuleDisplayName($box['key']['moduleId']))));

} else {
	$box['title'] = 
		adminPhrase('Creating a plugin of the "[[module]]" module',
			array('module' => htmlspecialchars(getModuleDisplayName($box['key']['moduleId']))));
}


return false;

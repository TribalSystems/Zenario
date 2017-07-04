<?php
/*
 * Copyright (c) 2017, Tribal Limited
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

class zenario_user_forms__organizer__user_forms extends module_base_class {
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($refinerName == 'archived') {
			$panel['db_items']['where_statement'] = 'WHERE TRUE';
			$panel['no_items_message'] = adminPhrase('No forms have been archived.');
		}
		if (!inc('zenario_extranet_registration')) {
			$panel['db_items']['where_statement'] .= '
				AND f.type != "registration"';
		}
		if (!inc('zenario_extranet_profile_edit')) {
			$panel['db_items']['where_statement'] .= '
				AND f.type != "profile"';
			$panel['collection_buttons']['create_profile_form']['hidden'] = true;
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($refinerName == 'email_address_setting') {
			unset($panel['collection_buttons']);
			$panel['title'] = adminPhrase('Summary of email addresses used by forms');
			$panel['no_items_message'] = adminPhrase('No forms send emails to a specific address.');
		} else {
			unset($panel['columns']['form_email_addresses']);
		}
		
		//Get plugins using a form
		$moduleIds = zenario_user_forms::getFormModuleIds();
		$formPlugins = array();
		$sql = '
			SELECT id, name, 0 AS egg_id
			FROM '.DB_NAME_PREFIX.'plugin_instances
			WHERE module_id IN ('. inEscape($moduleIds, 'numeric'). ')
			ORDER BY name';
		$result = sqlSelect($sql);
		while ($row = sqlFetchAssoc($result)) {
			$formPlugins[$row['id']] = $row['name'];
		}
		$sql = "
			SELECT pi.id, pi.name, np.id AS egg_id
			FROM ". DB_NAME_PREFIX. "nested_plugins AS np
			INNER JOIN ". DB_NAME_PREFIX. "plugin_instances AS pi
			   ON pi.id = np.instance_id
			WHERE np.module_id IN (". inEscape($moduleIds, 'numeric'). ")
			ORDER BY pi.name";
		$result = sqlSelect($sql);
		while ($row = sqlFetchAssoc($result)) {
			$formPlugins[$row['id']] = $row['name'];
		}
		
		//Get content items with a plugin using a form on
		$formUsage = array();
		$contentItemUsage = array();
		$layoutUsage = array();
		if ($formPlugins) {
			$sql = '
				SELECT pil.content_id, pil.content_type, pil.instance_id
				FROM '.DB_NAME_PREFIX.'plugin_item_link pil
				INNER JOIN '.DB_NAME_PREFIX.'content_items c
					ON (pil.content_id = c.id) AND (pil.content_type = c.type) AND (pil.content_version = c.admin_version)
				WHERE c.status NOT IN (\'trashed\',\'deleted\')
				AND pil.instance_id IN ('. inEscape(array_keys($formPlugins), 'numeric'). ')
				GROUP BY pil.content_id, pil.content_type, pil.instance_id';
			$result = sqlSelect($sql);
			while ($row = sqlFetchAssoc($result)) {
				$tagId = formatTag($row['content_id'], $row['content_type']);
				$contentItemUsage[$row['instance_id']][] = $tagId;
			}
			
			//Get layouts with a plugin using a form on
			$sql = '
				SELECT l.name, pll.instance_id
				FROM '.DB_NAME_PREFIX.'plugin_layout_link pll
				INNER JOIN '.DB_NAME_PREFIX.'layouts l
					ON pll.layout_id = l.layout_id
				WHERE l.status = "active"
				AND pll.instance_id IN (' . inEscape(array_keys($formPlugins), 'numeric') . ')
				GROUP BY l.layout_id, pll.instance_id';
			$result = sqlSelect($sql);
			while ($row = sqlFetchAssoc($result)) {
				$layoutUsage[$row['instance_id']][] = $row['name'];
			}
		}
		
		foreach ($formPlugins as $instanceId => $pluginName) {
			$className = zenario_user_forms::getModuleClassNameByInstanceId($instanceId);
			$moduleName = getModuleDisplayNameByClassName($className);
			
			if ($formId = getRow('plugin_settings', 'value', array('instance_id' => $instanceId, 'name' => 'user_form'))) {
				$details = array('pluginName' => $pluginName, 'moduleName' => $moduleName);
				if (isset($contentItemUsage[$instanceId])) {
					$details['contentItems'] = $contentItemUsage[$instanceId];
				}
				if (isset($layoutUsage[$instanceId])) {
					$details['layouts'] = $layoutUsage[$instanceId];
				}
				$formUsage[$formId][] = $details;
			}
		}
		
		foreach ($panel['items'] as $id => &$item) {
			$pluginUsage = '';
			$contentUsage = '';
			$layoutUsage = '';
			$moduleNames = array();
			if (isset($formUsage[$id]) && !empty($formUsage[$id])) {
				$pluginUsage = '"'.$formUsage[$id][0]['pluginName'].'"';
				if (($count = count($formUsage[$id])) > 1) {
					$plural = (($count - 1) == 1) ? '' : 's';
					$pluginUsage .= ' and '.($count - 1).' other plugin'.$plural;
				}
				$contentCount = 0;
				$layoutCount = 0;
				foreach($formUsage[$id] as $plugin) {
					$moduleNames[$plugin['moduleName']] = $plugin['moduleName'];
					if (isset($plugin['contentItems'])) {
						if (empty($contentUsage)) {
							$contentUsage = '"'.$plugin['contentItems'][0].'"';
						}
						$contentCount += count($plugin['contentItems']);
					}
					if (isset($plugin['layouts'])) {
						if (empty($layoutUsage)) {
							$layoutUsage = '"' . $plugin['layouts'][0] . '"';
						}
						$layoutCount += count($plugin['layouts']);
					}
				}
				
				//Multiple content, no layout
				if ($contentCount > 1 && $layoutCount == 0) {
					$plural = (($contentCount - 1) == 1) ? '' : 's';
					$contentUsage .= ' and '.($contentCount - 1).' other item'.$plural;
				//Multiple content, layout
				} elseif ($contentCount > 1 && $layoutCount > 0) {
					$plural = (($contentCount - 1) == 1) ? '' : 's';
					$plural2 = ($layoutCount == 1) ? '' : 's';
					$contentUsage .= ', '.($contentCount - 1).' other item'.$plural . ' and '.$layoutCount. ' layout'.$plural2;
				//Single content, layout
				} elseif ($contentCount == 1 && $layoutCount > 0) {
					$plural2 = ($layoutCount == 1) ? '' : 's';
					$contentUsage .= ' and '.$layoutCount. ' layout'.$plural2;
				//No content, layout
				} elseif (!$contentCount && $layoutCount) {
					$contentUsage = $layoutUsage;
					if ($layoutCount > 1) {
						$plural2 = (($layoutCount - 1) == 1) ? '' : 's';
						$contentUsage .= ' and '.($layoutCount - 1).' other layout'.$plural2;
					}
				}
			}
			$item['plugin_module_name'] = implode(', ', $moduleNames);
			$item['plugin_usage'] = $pluginUsage;
			$item['plugin_content_items'] = $contentUsage;
			
			if ($item['type'] != 'standard') {
				$item['css_class'] = 'form_type_' . $item['type'];
			}
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		exitIfNotCheckPriv('_PRIV_MANAGE_FORMS');
		if (post('archive_form')) {
			foreach(explode(',', $ids) as $id) {
				updateRow(ZENARIO_USER_FORMS_PREFIX . 'user_forms', array('status' => 'archived'), array('id' => $id));
			}
		} elseif (post('delete_form')) {
			foreach (explode(',', $ids) as $formId) {
				$error = zenario_user_forms::deleteForm($formId);
				if (isError($error)) {
					foreach ($error->errors as $message) {
						echo $message . "\n";
					}
				}
				
			}
		} elseif (post('duplicate_form')) {
			$formProperties = getRow(ZENARIO_USER_FORMS_PREFIX . 'user_forms', true, $ids);
			$formFields = getRowsArray(ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', true, array('user_form_id' => $ids));
			$formNameArray = explode(' ', $formProperties['name']);
			$formVersion = end($formNameArray);
			//Remove version number at end of field
			if (preg_match('/\((\d+)\)/', $formVersion, $matches)) {
				array_pop($formNameArray);
				$formProperties['name'] = implode(' ', $formNameArray);
			}
			for ($i = 2; $i < 1000; $i++) {
				$name = $formProperties['name'].' ('.$i.')';
				if (!checkRowExists(ZENARIO_USER_FORMS_PREFIX . 'user_forms', array('name' => $name))) {
					$formProperties['name'] = $name;
					break;
				}
			}
			
			unset($formProperties['id']);
			$id = insertRow(ZENARIO_USER_FORMS_PREFIX . 'user_forms', $formProperties);
			foreach ($formFields as $formField) {
				$formFieldValues = getRowsArray(ZENARIO_USER_FORMS_PREFIX. 'form_field_values', true, array('form_field_id' => $formField['id']));
				unset($formField['id']);
				$formField['user_form_id'] = $id;
				$fieldId = insertRow(ZENARIO_USER_FORMS_PREFIX . 'user_form_fields', $formField);
				//Duplicate form field values if any
				foreach ($formFieldValues as $field) {
					$field['form_field_id'] = $fieldId;
					unset($field['id']);
					insertRow(ZENARIO_USER_FORMS_PREFIX. 'form_field_values', $field);
				}
			}
		}
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		exitIfNotCheckPriv('_PRIV_MANAGE_FORMS');
		if (post('export_forms')) {
			$formIds = explodeAndTrim($ids);
			$formsJSON = array(
				'major_version' => ZENARIO_MAJOR_VERSION,
				'minor_version' => ZENARIO_MINOR_VERSION,
				'forms' => array()
			);
			foreach ($formIds as $formId) {
				$formJSON = zenario_user_forms::getFormJSON($formId);
				$formsJSON['forms'][] = $formJSON;
			}
			$formsJSON = json_encode($formsJSON);
			
			$filename = tempnam(sys_get_temp_dir(), 'forms_export');
			file_put_contents($filename, $formsJSON);
			//Offer file as download
			header('Content-Type: application/json');
			header('Content-Disposition: attachment; filename="Zenario forms.json"');
			header('Content-Length: ' . filesize($filename));
			readfile($filename);
			//Remove file from temp directory
			@unlink($filename);
			exit;
		}
	}
}
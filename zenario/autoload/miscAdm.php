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

class miscAdm {




	//Formerly "convertMySQLToJqueryDateFormat()"
	public static function convertMySQLToJqueryDateFormat($value) {
		return str_replace(
			['[[_MONTH_SHORT_%m]]', '%Y', '%y', '%c', '%m', '%e', '%d'],
			['M', 'yy', 'y', 'm', 'mm', 'd', 'dd'],
			$value);
	}

	//Format the values for the date-format select lists in the installer and the site-settings
	//Formerly "formatDateFormatSelectList()"
	public static function formatDateFormatSelectList(&$field, $addFormatInBrackets = false, $isJavaScriptFormat = false) {
	
		//	//Check the current date
		//	$dd = date('d');
		//	$mm = date('m');
		//	$yy = date('y');
		//
		//	//Would the current date be a good example date (i.e. the day/month/year must all be different numbers)?
		//	if ($dd == $mm || $dd == $yy || $yy == $mm) {
		//		//If not, use a different sample
		//		$exampleDate = '1999-09-19';
		//	} else {
		//		//If so, use the current date
		//		$exampleDate = date('Y-m-d');
		//	}
	
		$exampleDate = date('Y-m-d');
		$ddmmyyyyDate = '2222-03-04';
	
		//Ensure the chosen value is in the list of values!
		if (!empty($field['value'])
		 && empty($field['values'][$field['value']])) {
			$field['values'][$field['value']] = [];
		}
	
		foreach ($field['values'] as $value => &$details) {
			if ($isJavaScriptFormat) {
				$value = str_replace(
					['yy', 'y', 'm', '%c%c', 'd', '%e%e', 'M'],
					['%Y', '%y', '%c', '%m', '%e', '%d', '%b'],
					$value);
			}
			$value = str_replace(
				['[[_WEEKDAY_%w]]', '[[_MONTH_LONG_%m]]', '[[_MONTH_SHORT_%m]]'],
				['%W', '%M', '%b'],
				$value);
		
			$sql = "SELECT DATE_FORMAT('" . \ze\escape::sql($exampleDate) . "', '" . \ze\escape::sql($value) . "')";
			$result = \ze\sql::select($sql);
			$row = \ze\sql::fetchRow($result);
			$example = $row[0];
		
			if ($addFormatInBrackets) {
				$sql = "SELECT DATE_FORMAT('" . \ze\escape::sql($ddmmyyyyDate) . "', '" . \ze\escape::sql($value) . "')";
				$result = \ze\sql::select($sql);
				$row = \ze\sql::fetchRow($result);
				$ddmmyyy = str_replace(
					['04', '4', '03', '3', '2', 'Monday', 'Mon', 'March', 'Mar'],
					['dd', 'd', 'mm', 'm', 'y', 'Day', 'Day', 'Month', 'Mmm'],
					$row[0]);
		
				$details['label'] = $example. ' ('. $ddmmyyy. ')';
			} else {
				$details['label'] = $example;
			}
		}
	}






	//Generate a hierarchical select list from a table with a parent_id column
	//Formerly "generateHierarchicalSelectList()"
	public static function generateHierarchicalSelectList($table, $labelCol, $parentIdCol = 'parent_id', $ids = [], $orderBy = [], $flat = false, $parentId = 0, $pad = '    ') {
		$output = [];
		$cols = [$labelCol, $parentIdCol];
		\ze\miscAdm::generateHierarchicalListR($output, $table, $cols, $parentIdCol, $ids, $orderBy, $parentId, 0);
	
		$ord = 0;
		foreach ($output as &$row) {
			$row = array(
				'ord' => ++$ord,
				'label' => str_repeat($pad, $row['level']). $row[$labelCol]);
		
			if ($flat) {
				$row = $row['label'];
			}
		}
	
		return $output;
	}

	//Generate a hierarchical list from a table with a parent_id column
	//Formerly "generateHierarchicalList()"
	public static function generateHierarchicalList($table, $cols = [], $parentIdCol = 'parent_id', $ids = [], $orderBy = [], $parentId = 0) {
		$output = [];
	
		if (!is_array($cols)) {
			$cols = [$cols];
		}
		if (!in_array($parentIdCol, $cols)) {
			$cols[] = $parentIdCol;
		}
	
		\ze\miscAdm::generateHierarchicalListR($output, $table, $cols, $parentIdCol, $ids, $orderBy, $parentId, 0);
	
		return $output;
	}

	//Formerly "generateHierarchicalListR()"
	public static function generateHierarchicalListR(&$output, $table, $cols, $parentIdCol, $ids, $orderBy, $parentId, $level) {
	
		$ids[$parentIdCol] = $parentId;
	
		foreach (\ze\row::getArray($table, $cols, $ids, $orderBy) as $id => $row) {
			//This line in theory shouldn't be needed if the data integrety is good,
			//but I have included it to stop infinite loops if it is not!
			if (!isset($output[$id])) {
				$row['level'] = $level;
				$output[$id] = $row;
			
				\ze\miscAdm::generateHierarchicalListR($output, $table, $cols, $parentIdCol, $ids, $orderBy, $id, $level + 1);
			}
		}
	
	}

	//Formerly "generateDocumentFolderSelectList()"
	public static function generateDocumentFolderSelectList($flat = false) {
		return \ze\miscAdm::generateHierarchicalSelectList('documents', 'folder_name', 'folder_id', ['type' => 'folder'], 'ordinal', $flat);
	}



	//Formerly "exportPanelItems()"
	public static function exportPanelItems($headers, $rows, $format = 'csv', $filename = 'export') {
		$filename = str_replace('"', '\'', str_replace('/', ' ', $filename));
		// Export as CSV file
		if ($format == 'csv') {
			// Create temp file to write CSV to
			$filepath = tempnam(sys_get_temp_dir(), 'tmpexportfile');
			$f = fopen($filepath, 'wb');
		
			// Write column headers and data
			fputcsv($f, $headers);
			foreach ($rows as $row) {
				fputcsv($f, $row);
			}
		
			// Output file
			header('Content-Type: text/x-csv');
			header('Content-Disposition: attachment; filename="'. $filename .'.csv"');
			header('Content-Length: '. filesize($filepath));
			readfile($filepath);
		
			// Remove file from temp directory
			@unlink($filepath);
		// Export as excel file
		} elseif ($format == 'excel') {
			require_once CMS_ROOT.'zenario/libs/manually_maintained/lgpl/PHPExcel/Classes/PHPExcel.php';
			// Create excel object
			$objPHPExcel = new \PHPExcel();
			// Write headers and data
			$objPHPExcel->getActiveSheet()->fromArray($headers, NULL, 'A1');
			$objPHPExcel->getActiveSheet()->fromArray($rows, NULL, 'A2');
			// Output file
			header('Content-Type: application/vnd.ms-excel');
			header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
			$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
			$objWriter->save('php://output');
		}
	}

	//Formerly "setupSlideDestinations()"
	public static function setupSlideDestinations(&$box, &$fields, &$values) {
		//Check if this plugin is in a slide in a conductor
		$box['key']['conductorState'] = '';
		if ($box['key']['usesConductor'] && $box['key']['slideNum']) {
	
			//Try and find the current state or states
			$slide = \ze\row::get('nested_plugins', ['states', 'show_back'], [
				'instance_id' => $box['key']['instanceId'],
				'slide_num' => $box['key']['slideNum'],
				'is_slide' => 1
			]);
	
			$currentStates = \ze\ray::explodeAndTrim($slide['states']);
			if (!empty($currentStates[0])) {
				$box['key']['conductorState'] = $currentStates[0];
		
				//Fill the list of slides
				$result = \ze\sql::select("
					SELECT id, slide_num, states, name_or_title
					FROM ". DB_NAME_PREFIX. "nested_plugins
					WHERE instance_id = ". (int) $box['key']['instanceId']. "
					  AND slide_num != ". (int) $box['key']['slideNum']. "
					  AND is_slide = 1");
	
				$ord = 0;
				while ($row = \ze\sql::fetchAssoc($result)) {
		
					$states = \ze\ray::explodeAndTrim($row['states']);
		
					foreach ($states as $state) {
						$row['state'] = $state;
						$box['lovs']['slides_and_states'][$state] = [
							'ord' => ++$ord,
							'label' => \ze\admin::phrase('Slide [[slide_num]][[state]]: [[name_or_title]]', $row)
						];
					}
				}
		
				//Load the existing destination for each path on this slide.
				$sql = "
					SELECT from_state, to_state, equiv_id, content_type, commands
					FROM ". DB_NAME_PREFIX. "nested_paths
					WHERE from_state IN (". \ze\escape::in($currentStates). ")
					  AND instance_id = ". (int) $box['key']['instanceId']. "
					ORDER BY commands, from_state";
		
				$i = 0;
				$lastTo = '';
				$lastCommand = '';
				$result = \ze\sql::select($sql);
				while ($row = \ze\sql::fetchAssoc($result)) {
				
					//Note that there may be more than one, in the case of multiple states. When this happens,
					//we'll show no more than two.
					if ($lastCommand != $row['commands']) {
						$lastCommand = $row['commands'];
						$i = 1;
					} else {
						//If both alternate states link to the same place, don't show it twice
						if ($lastTo == $row['content_type']. $row['equiv_id']. $row['to_state']) {
							continue;
				
						//Show at most two alternate states (as there is a limit of two fields per command)
						} elseif ($i < 2) {
							++$i;
						} else {
							continue;
						}
					}
			
					if (isset($fields['to_state'. $i. '.'. $row['commands']])) {
						$fields['to_state'. $i. '.'. $row['commands']]['hidden'] = false;
					
						if ($row['equiv_id']) {
							$codeName = $row['content_type']. '_'. $row['equiv_id']. '/'. $row['to_state'];
						
							$box['lovs']['slides_and_states'][$codeName] = \ze\content::formatTag($row['equiv_id'], $row['content_type'], -1, false, true);
						
							$values['to_state'. $i. '.'. $row['commands']] = $codeName;
						} else {
							$values['to_state'. $i. '.'. $row['commands']] = $row['to_state'];
						}
					
						$lastTo = $row['content_type']. $row['equiv_id']. $row['to_state'];
					}
				}
		
				//If the back button is enabled in the slide properties, show it as a read-only checkbox
				if (isset($fields['enable.back'])) {
					$values['enable.back'] = $slide['show_back'];
				}
		
				//If the back button has a path but the submit button doesn't have a path,
				//default the path to that of the back button
				if (isset($fields['enable.submit'])
				 && empty($values['to_state1.submit'])
				 && !empty($values['to_state1.back'])) {
					$values['to_state1.submit'] = $values['to_state1.back'];
				}
		
				//Loop through all of the command-fields, looking for any that didn't get values, and mark them with a warning symbol
				foreach ($fields as $key => &$field) {
					if (isset($field['values'])
					 && $field['values'] === 'slides_and_states'
					 && strpos($key, '/') === false //n.b. this line is because fields appear in the $fields shortcut-array twice
					 && empty($values[$key])
					 && empty($field['hidden'])
					 && \ze\ring::chopPrefix('to_state', $key)
					) {
						$field['css_class'] .= ' zfab_warning';
					}
				}
			}
		}
	}

	//Formerly "checkScheduledTaskRunning()"
	public static function checkScheduledTaskRunning($jobName) {
		return \ze\module::inc('zenario_scheduled_task_manager') && zenario_scheduled_task_manager::checkScheduledTaskRunning($jobName, true);
	}






	//Update the values stored for something (e.g. a content item) in a linking table
	//Formerly "updateLinkingTable()"
	public static function updateLinkingTable($table, $key, $idCol, $ids = []) {
	
		if (!is_array($ids)) {
			$ids = \ze\ray::explodeAndTrim($ids);
		}
	
		//Delete anything that wasn't picked from the linking table
		//E.g. \ze\row::delete('group_content_link', array('equiv_id' => 42, 'content_type' => 'test', 'group_id' => array('!' => [1, 2, 3, 4, 5, 6, 7, 8])));
		$key[$idCol] = ['!' => $ids];
		\ze\row::delete($table, $key);
	
		//Make sure each new row exists
		foreach ($ids as $id) {
			$key[$idCol] = $id;
			\ze\row::set($table, [], $key);
		}
	}
	
	
	
	
	
	
	
	

	//Formerly "checkForChangesInYamlFiles()"
	public static function checkForChangesInYamlFiles($forceScan = false) {
	
		//Safety catch - do not try to do anything if there is no database connection!
		if (!\ze::$lastDB) {
			return;
		}
	
		//Make sure we are in the CMS root directory.
		//This should already be done, but I'm being paranoid...
		chdir(CMS_ROOT);
	
		$time = time();
		$zenario_version = \ze\site::versionNumber();
	
		//Catch the case where someone just updated to a different version of the CMS
		if ($zenario_version != \ze::setting('zenario_version')) {
			//Clear everything that was cached if this has happened
			\ze\site::setSetting('css_js_html_files_last_changed', '');
			\ze\site::setSetting('css_js_version', '');
			$changed = true;
	
		//Get the date of the last time we ran this check and there was a change.
		} elseif (!($lastChanged = (int) \ze::setting('yaml_files_last_changed'))) {
			//If this has never been run before then it must be run now!
			$changed = true;
	
		} elseif ($forceScan) {
			$changed = true;
	
		//In production mode, only run this check if it looks like there's
		//been a core software update since the last time we ran
		} elseif (\ze::setting('site_mode') == 'production' && \ze\db::codeLastUpdated(false) < $lastChanged) {
			$changed = false;
	
		//Otherwise, work out the time difference between that time and now
		} else {
			$changed = false;
			foreach (\ze::moduleDirs('tuix/') as $tuixDir) {
			
				$RecursiveDirectoryIterator = new \RecursiveDirectoryIterator(CMS_ROOT. $tuixDir);
				$RecursiveIteratorIterator = new \RecursiveIteratorIterator($RecursiveDirectoryIterator);
			
				foreach ($RecursiveIteratorIterator as $file) {
					if ($file->isFile()
					 && $file->getMTime() > $lastChanged) {
						$changed = true;
						break 2;
					}
				}
			}
			chdir(CMS_ROOT);
		}
	
	
		if ($changed) {
			//Look to see what datasets are on the system, and which datasets extend which FABs
			$datasets = [];
			$datasetFABs = [];
			foreach (\ze\row::getArray('custom_datasets', 'extends_admin_box') as $datasetId => $extends_admin_box) {
				$datasetFABs[$extends_admin_box] = $datasetId;
			}
		
		
			//Scan the TUIX files, and come up with a list of what paths are in what files
			$tuixFiles = [];
			$result = \ze\row::query('tuix_file_contents', true, []);
			while ($tf = \ze\sql::fetchAssoc($result)) {
				$key = $tf['module_class_name']. '/'. $tf['type']. '/'. $tf['filename'];
				$key2 = $tf['path']. '//'. $tf['setting_group'];
		
				if (empty($tuixFiles[$key])) {
					$tuixFiles[$key] = [];
				}
				$tuixFiles[$key][$key2] = $tf;
			}
	
			$contents = [];
			foreach (['admin_boxes', 'admin_toolbar', 'slot_controls', 'organizer', 'visitor', 'wizards'] as $type) {
				foreach (\ze::moduleDirs('tuix/'. $type. '/') as $moduleClassName => $dir) {
			
					foreach (scandir($dir) as $file) {
						if (substr($file, 0, 1) != '.') {
							$key = $moduleClassName. '/'. $type. '/'. $file;
							$filemtime = null;
							$md5_file = null;
							$changes = true;
							$first = true;
					
							//Check the modification time and the checksum of the file. If either are the same as before,
							//there's no need to update this row.
							if (!empty($tuixFiles[$key])) {
								foreach ($tuixFiles[$key] as $key2 => &$tf) {
							
									//Note that this is an array of arrays, but I only need to check the first one
									if ($first) {
										$filemtime = filemtime($dir. $file);
								
										if ($tf['last_modified'] == $filemtime) {
											$changes = false;
								
										} else {
											$md5_file = md5_file($dir. $file);
									
											if ($tf['checksum'] == $md5_file) {
												$changes = false;
											}
										}
									}
							
									//Note that this is an array of arrays, but I only need to check the first one
									if (!$changes) {
										$tf['status'] = 'unchanged';
									}
								}
								unset($tf);
						
								if (!$changes) {
									continue;
								}
							} else {
								$tuixFiles[$key] = [];
							}
					
							//If there have been changes, or if this is the first time we've seen this file,
							//read it, then loop through it looking for all of the TUIX paths it contains
								//Note that as we know there are changes, I'm overriding the normal timestamp logic in \ze\tuix::readFile()
							if (($tags = \ze\tuix::readFile($dir. $file, false))
							 && (!empty($tags))
							 && (is_array($tags))) {
						
								if ($filemtime === null) {
									$filemtime = filemtime($dir. $file);
								}
								if ($md5_file === null) {
									$md5_file = md5_file($dir. $file);
								}
						
								$pathsFound = false;
								if ($type == 'organizer') {
									$paths = [];
									\ze\tuix::logFileContentsR($paths, $tags, $type);
							
									foreach ($paths as $path => $panelType) {
										$pathsFound = true;
										$settingGroup = '';
								
										$key2 = $path. '//'. $settingGroup;
										$tuixFiles[$key][$key2] = array(
											'type' => $type,
											'path' => $path,
											'panel_type' => $panelType,
											'setting_group' => $settingGroup,
											'module_class_name' => $moduleClassName,
											'filename' => $file,
											'last_modified' => $filemtime,
											'checksum' => $md5_file,
											'status' => empty($tuixFiles[$key][$key2])? 'new' : 'updated'
										);
									}
								}
						
								if (!$pathsFound) {
									//For anything else, just read the top-level path
									//Note - also do this for Organizer if no paths were found above,
									//as \ze\tuix::logFileContentsR() will miss files that have navigation definitions but no panel definitions
									foreach ($tags as $path => &$tag) {
								
										$settingGroup = '';
										if ($type == 'admin_boxes') {
											if ($path == 'plugin_settings' && !empty($tag['module_class_name'])) {
												$settingGroup = $tag['module_class_name'];
									
											} elseif ($path == 'site_settings' && !empty($tag['setting_group'])) {
												$settingGroup = $tag['setting_group'];
										
											//Note down if we see any changes in a file for a FAB
											//that is used for a dataset.
											} elseif (!empty($datasetFABs[$path])) {
												$datasets[$datasetFABs[$path]] = $datasetFABs[$path];
											}
								
										} elseif ($type == 'slot_controls') {
											if (!empty($tag['module_class_name'])) {
												$settingGroup = $tag['module_class_name'];
											}
										}
								
										$key2 = $path. '//'. $settingGroup;
										$tuixFiles[$key][$key2] = array(
											'type' => $type,
											'path' => $path,
											'panel_type' => '',
											'setting_group' => $settingGroup,
											'module_class_name' => $moduleClassName,
											'filename' => $file,
											'last_modified' => $filemtime,
											'checksum' => $md5_file,
											'status' => empty($tuixFiles[$key][$key2])? 'new' : 'updated'
										);
									}
								}
							}
							unset($tags);
						}
					}
				}
			}
		
		
		
			//Loop through the array we've generated, and take actions as appropriate
			foreach ($tuixFiles as $key => &$tuixFile) {
				foreach ($tuixFile as $key2 => $tf) {
			
					//Where we could no longer find files, delete them
					if (empty($tf['status'])) {
						$sql = "
							DELETE FROM ". DB_NAME_PREFIX. "tuix_file_contents
							WHERE type = '". \ze\escape::sql($tf['type']). "'
							  AND path = '". \ze\escape::sql($tf['path']). "'
							  AND setting_group = '". \ze\escape::sql($tf['setting_group']). "'
							  AND module_class_name = '". \ze\escape::sql($tf['module_class_name']). "'
							  AND filename = '". \ze\escape::sql($tf['filename']). "'";
						\ze\sql::select($sql);
			
					//Add/update newly added/edited files
					} else if ($tf['status'] != 'unchanged') {
						$sql = "
							INSERT INTO ". DB_NAME_PREFIX. "tuix_file_contents
							SET type = '". \ze\escape::sql($tf['type']). "',
								path = '". \ze\escape::sql($tf['path']). "',
								panel_type = '". \ze\escape::sql($tf['panel_type']). "',
								setting_group = '". \ze\escape::sql($tf['setting_group']). "',
								module_class_name = '". \ze\escape::sql($tf['module_class_name']). "',
								filename = '". \ze\escape::sql($tf['filename']). "',
								last_modified = ". (int) $tf['last_modified']. ",
								checksum = '". \ze\escape::sql($tf['checksum']). "'
							ON DUPLICATE KEY UPDATE
								panel_type = VALUES(panel_type),
								last_modified = VALUES(last_modified),
								checksum = VALUES(checksum)";
						\ze\sql::select($sql);
					}
				}
			}
		
			//Rescan the TUIX files for any datasets that have changed
			foreach ($datasets as $datasetId) {
				\ze\miscAdm::saveSystemFieldsFromTUIX($datasetId);
			}
		
		
			\ze\site::setSetting('yaml_files_last_changed', $time);
			\ze\site::setSetting('yaml_version', base_convert($time, 10, 36));
			\ze\site::setSetting('zenario_version', $zenario_version);
		}
	}
	
	
	
	

	//Formerly "saveSystemFieldsFromTUIX()"
	public static function saveSystemFieldsFromTUIX($datasetId) {
		$dataset = \ze\dataset::details($datasetId);
		//If this extends a system admin box, load the system tabs and fields
		if ($dataset['extends_admin_box']
		 && \ze\row::exists('tuix_file_contents', ['type' => 'admin_boxes', 'path' => $dataset['extends_admin_box']])) {
			$moduleFilesLoaded = [];
			$tags = [];
		
			\ze\tuix::load(
				$moduleFilesLoaded, $tags, $type = 'admin_boxes', $dataset['extends_admin_box'],
				$settingGroup = '', $compatibilityClassNames = false, $runningModulesOnly = false, $exitIfError = true
			);
		
			if (!empty($tags[$dataset['extends_admin_box']]['tabs'])
				 && is_array($tags[$dataset['extends_admin_box']]['tabs'])) {
				$tabCount = 0;
				foreach ($tags[$dataset['extends_admin_box']]['tabs'] as $tabName => $tab) {
					if (is_array($tab) && (!empty($tab['label']) || !empty($tab['dataset_label']))) {
						++$tabCount;
						$tabDetails = \ze\row::get('custom_dataset_tabs', true, ['dataset_id' => $datasetId, 'name' => $tabName]);
						$values = array(
							'is_system_field' => 1,
							'default_label' => \ze::ifNull($tab['dataset_label'] ?? false, ($tab['label'] ?? false), '')
						);
						if (!$tabDetails || !$tabDetails['ord']) {
							$values['ord'] = (float)(($tab['ord'] ?? false) ?: $tabCount);
						}
						\ze\row::set('custom_dataset_tabs', 
							$values,
							[
								'dataset_id' => $datasetId, 
								'name' => $tabName]);
						if (!empty($tab['fields'])
							 && is_array($tab['fields'])) {
							$fieldCount = 0;
							foreach ($tab['fields'] as $fieldName => $field) {
								if (is_array($field)) {
									++$fieldCount;
								
									$fieldDetails = \ze\row::get('custom_dataset_fields', true, ['dataset_id' => $datasetId, 'tab_name' => $tabName, 'is_system_field' => 1, 'field_name' => $fieldName]);
									$values = array(
										'default_label' => \ze::ifNull($field['dataset_label'] ?? false, ($field['label'] ?? false), ''),
										'is_system_field' => 1,
										'allow_admin_to_change_visibility' => !empty($field['allow_admin_to_change_visibility']),
										'allow_admin_to_change_export' => !empty($field['allow_admin_to_change_export'])
									);
									if (!$fieldDetails || !$fieldDetails['ord']) {
										$values['ord'] = (float) (($field['ord'] ?? false) ?: $fieldCount);
									}
									\ze\row::set('custom_dataset_fields',
										$values,
										[
											'dataset_id' => $datasetId, 
											'tab_name' => $tabName, 
											'field_name' => $fieldName]);
								}
							}
						}
					}
				}
			}
		}
	}

}
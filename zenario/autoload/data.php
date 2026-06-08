<?php
/*
 * Copyright (c) 2026, Tribal Limited
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

class data {
	
	//When exporting something that has rows in another table linked by a foreign key,
	//this function adds those rows to the export.
	private static function exportMultipleRows(&$data, $table, $cols, $key, $indexBy = null, $unsetId = null) {
		
		if (isset($indexBy)) {
			$data = \ze\row::getAssocs($table, $cols, $key, $indexBy, $indexBy);
		} else {
			$data = \ze\row::getAssocs($table, $cols, $key);
		}
		
		foreach ($data as $rowId => &$row) {
			\ze\data::processRow($table, $key, $rowId, $row, $unsetId);
		}
		
	}
	
	//Add a row of data to an export.
	//(Likely used at the top level for the thing being exported.)
	private static function exportSingleRow(&$row, $table, $cols, $rowId) {
		
		$row = \ze\row::get($table, $cols, $rowId);
		
		if (!$row) {
			echo \ze\admin::phrase('The requested item to export was not found.');
			exit;
		}
		
		\ze\data::processRow($table, [], $rowId, $row);
		
	}
	
	//Process a row of data and add it to the export.
	private static function processRow($table, $key, $rowId, &$row, $unsetId = null) {
		
		//If this row was looked up by an ID or a foreign key, we don't need to repeat the
		//columns we already know in the exported data.
		foreach ($key as $varName => $val) {
			unset($row[$varName]);
		}
		if (!is_null($unsetId)) {
			unset($row[$unsetId]);
		}
		
		
		//Handle foreign keys to things we're not exporting.
		
		//Handle links to modules. Replace the module's ID with its class name.
		if (isset($row['module_id']) && !empty($row['module_id'])) {
			$moduleId = $row['module_id'];
			
			if ($module = \ze\row::get('modules', ['class_name'], $moduleId)) {
				$row['module_id'] = [
					'§module_class_name' => $module['class_name']
				];
			}
		}
		
		//Handle some of the foreign-key links to other tables from the plugin settings.
		//(Warning: not all have been implemented here.)
		if (isset($row['foreign_key_to'])
		 && !empty($row['foreign_key_to'])
		 && !empty($row['value'])) {
			
			switch ($row['foreign_key_to']) {
				//Any file IDs we find should be replaced by the file's checksum and usage.
				case 'file':
					if ($file = \ze\row::get('files', ['checksum', 'usage'], $row['value'])) {
						
						$row['value'] = [
							'§file_checksum' => $file['checksum'],
							'§file_usage' => $file['usage']
						];
						
						unset(
							$row['foreign_key_to'],
							$row['foreign_key_id'],
							$row['foreign_key_char']
						);
					}
					break;
				
				//Comma-separated lists of files should be replaced with an array of
				//file checksums and usages.
				case 'multiple_files':
					$values = [];
					foreach (\ze\ray::explodeAndTrim($row['value'], true) as $fileId) {
						if ($file = \ze\row::get('files', ['checksum', 'usage'], $fileId)) {
							
							$values[] = [
								'§file_checksum' => $file['checksum'],
								'§file_usage' => $file['usage']
							];
						}
					}
					
					$row['value'] = [
						'§multiple_files' => $values
					];
					
					unset(
						$row['foreign_key_to'],
						$row['foreign_key_id'],
						$row['foreign_key_char']
					);
					break;
			}
		}
		
		
		//For each row, store a list of some related rows from other tables.
		$row['§'] = [];
		
		//By default, we'll ignore/skip related rows from other tables.
		//I've only implemented it in specific hard-coded situations.
		switch ($table) {
			case 'plugin_instances':
				
				//Catch the cases where the plugin_instance_store table was used to store
				//some metadata or config information on a plugin. This should be included.
				//in the export as well.
				$table2 = 'plugin_instance_store';
				$cols2 = true;
				$key2 = [
					'instance_id' => $rowId,
					'is_cache' => 0
				];
				
				$row['§'][$table2] = [];
				\ze\data::exportMultipleRows($row['§'][$table2], $table2, $cols2, $key2);
				
				//When exporting a plugin, its settings should be in the import.
				//Note: For nests, we'll split settings up between settings for the nest and
				//settings for the nested plugins. These here are just the settings for the nest.
				$table2 = 'plugin_settings';
				$cols2 = true;
				$key2 = [
					'instance_id' => $rowId,
					'egg_id' => 0
				];
				$indexBy2 = 'name';
				
				$row['§'][$table2] = [];
				\ze\data::exportMultipleRows($row['§'][$table2], $table2, $cols2, $key2, $indexBy2);
				
				//Store information on the state diagram for conductors
				$table2 = 'nested_paths';
				$cols2 = true;
				$key2 = [
					'instance_id' => $rowId
				];
				
				$row['§'][$table2] = [];
				\ze\data::exportMultipleRows($row['§'][$table2], $table2, $cols2, $key2);
				
				//For nests, record every nested plugin inside it.
				$table2 = 'nested_plugins';
				$cols2 = true;
				$key2 = [
					'instance_id' => $rowId
				];
				$indexBy2 = 'id';
				$unsetId = 'id';
				
				$row['§'][$table2] = [];
				\ze\data::exportMultipleRows($row['§'][$table2], $table2, $cols2, $key2, $indexBy2, $unsetId);
				
				break;
				
				
			case 'nested_plugins':
				
				//The nested plugins table stores both slides and plugins in the nest.
				if ($row['is_slide']) {
					//Not implemented - the group_link table
					#$table2 = 'group_link';
					#$cols2 = ['link_to', 'link_to_id'];
					#$key2 = [
					#	'link_from' => 'slide',
					#	'link_from_id' => $rowId
					#];
					#
					#$row['§'][$table2] = [];
					#\ze\data::exportMultipleRows($row['§'][$table2], $table2, $cols2, $key2);

				} else {
					//For nested plugins, also record their plugin settings.
					//These should be recorded against the nested plugin itself.
					$table2 = 'plugin_settings';
					$cols2 = true;
					$key2 = [
						'instance_id' => $key['instance_id'],
						'egg_id' => $rowId
					];
					$indexBy2 = 'name';
					
					$row['§'][$table2] = [];
					\ze\data::exportMultipleRows($row['§'][$table2], $table2, $cols2, $key2, $indexBy2);
				}
		}
		
		//A few more things that aren't implemented:
		//equiv_id/content_type
		//More options for foreign_key_to/foreign_key_id/foreign_key_char 
		//smart_group_id
		//link_to/link_to_id
		
		
		//Tidy up any empty foreign key references
		foreach ($row['§'] as $table2 => $data2) {
			if (empty($data2)) {
				unset($row['§'][$table2]);
			}
		}
		if (empty($row['§'])) {
			unset($row['§']);
		}
		
	}
	
	
	//Import one row into a table.
	//Can override an existing row you specify, or be added as a brand new row.
	private static function import($table, $row, $existingId = null) {
		
		return \ze\data::processImportedRow($table, $row, [], $existingId);
	}
	
	//Handle the case where we are importing into a table with AUTO_INCREMENT primary keys.
	//Where possible we'll reuse existing IDs.
	private static function overrideRows($table, $rows, $fKey) {
		
		$existingIds = \ze\row::getValues($table, 'id', $fKey, 'id');
		
		foreach ($rows as $row) {
			
			if ($fKey === []) {
				$rowWithKey = $row;
			} else {
				$rowWithKey = array_merge($row, $fKey);
			}
			
			if (!empty($existingIds)) {
				$existingId = array_shift($existingIds);
			} else {
				$existingId = null;
			}
			
			\ze\data::processImportedRow($table, $rowWithKey, $fKey, $existingId);
		}
		
		//Remove any existing IDs we didn't actually use
		if (!empty($existingIds)) {
			\ze\row::delete($table, ['id' => $existingIds]);
		}
	}
	
	//Handle the more simple case where there is no AUTO_INCREMENT and there are no
	//existing rows.
	private static function replaceRows($table, $rows, $fKey) {
		
		//Any rows that do exist should be cleaned up first.
		\ze\row::delete($table, $fKey);
		
		foreach ($rows as $row) {
			
			if ($fKey === []) {
				$rowWithKey = $row;
			} else {
				$rowWithKey = array_merge($row, $fKey);
			}
			
			\ze\data::processImportedRow($table, $rowWithKey, $fKey);
		}
	}
	
	
	//Given a row we've just read from an export file, process it and insert it into the database.
	private static function processImportedRow($table, $row, $fKey, $existingId = null) {
		
		//Separate any related rows out from the data for the current row.
		$fTables = $row['§'] ?? [];
		unset($row['§']);
		
		//Loop through each value, checking to see if we need to do any replacements
		foreach ($row as &$val) {
			if (is_array($val)) {
				
				//Any links to module IDs need to be converted back from class names to IDs.
				if (isset($val['§module_class_name'])) {
					$val = \ze\module::id($val['§module_class_name']);
				}
				
				//Any links to file IDs need to be converted back from checksums & usages to IDs.
				if (isset($val['§file_checksum'])
				 && isset($val['§file_usage'])) {
					$val = \ze\row::get('files', 'id', [
						'checksum' => $val['§file_checksum'],
						'usage' => $val['§file_usage']
					]) ?: 0;
					
					if ($val && $table == 'plugin_settings') {
						$row['foreign_key_to'] = 'file';
						$row['foreign_key_id'] = $val;
						$row['foreign_key_char'] = '';
					}
				}
				if (isset($val['§multiple_files'])
				 && is_array($val['§multiple_files'])) {
					
					$fileIds = [];
					foreach ($val['§multiple_files'] as $fileLink) {
						$fileId =
							\ze\row::get('files', 'id', [
								'checksum' => $fileLink['§file_checksum'] ?? '',
								'usage' => $fileLink['§file_usage'] ?? ''
							]);
						
						if ($fileId) {
							$fileIds[] = $fileId;
						}
					}
					
					$val = implode(',', $fileIds);
					$row['foreign_key_to'] = 'multiple_files';
					$row['foreign_key_id'] = 0;
					$row['foreign_key_char'] = '';
				}
			}
		}
		
		//Either insert a new row into the database or replace an existing row, depending
		//on the options used in the import.
		if (!empty($existingId)) {
			$rowId = \ze\row::set($table, $row, $existingId);
		} else {
			$rowId = \ze\row::insert($table, $row);
		}
		
		//Look through the related data any insert that as well.
		//Warning: I'm not giving the export file free-reign on what tables it can insert into,
		//that would be terrible security. Instead I've hard-coded which table combinations I will
		//read in the switch-statement below.
		if (!empty($fTables)) {
			foreach ($fTables as $table2 => $rows2) {
				switch ($table. '>'. $table2) {
					
					//Catch the cases where the plugin_instance_store table was used to store
					//some metadata or config information on a plugin.
					case 'plugin_instances>plugin_instance_store':
						\ze\data::replaceRows($table2, $rows2, ['instance_id' => $rowId]);
						break;
						
					//Import the settings for a plugin.
					//Note: For nests, this is just settings for the nest itself, not the nested plugins.
					case 'plugin_instances>plugin_settings':
						\ze\data::replaceRows($table2, $rows2, ['instance_id' => $rowId, 'egg_id' => 0]);
						break;
					
					//Read information on the state diagram for conductors.
					case 'plugin_instances>nested_paths':
						\ze\data::replaceRows($table2, $rows2, ['instance_id' => $rowId]);
						break;
					
					//For nests, record every nested plugin inside it.
					case 'plugin_instances>nested_plugins':
						\ze\data::overrideRows($table2, $rows2, ['instance_id' => $rowId]);
						break;
					
					//Read the plugin settings for a nested plugin.
					case 'nested_plugins>plugin_settings':
						\ze\data::replaceRows($table2, $rows2, ['instance_id' => $fKey['instance_id'], 'egg_id' => $rowId]);
						break;
				}
			}
		}
		
		return $rowId;
	}
	
	
	//Given a data object created by the export functions above, covert it to YAML and then gzip it,
	//so we can save it as a file.
	public static function saveToDisc($data, $filepath = null) {
		
		if (is_null($filepath)) {
			$filepath = tempnam(sys_get_temp_dir(), 'exp');
		}
		
		//Spyc throws warnings if you use null values (I think due to a bug in their code) so we need
		//error suppression when calling it using data from the database that might include null columns.
		\ze::ignoreErrors();
		$yaml = \Spyc::YAMLDump($data, $indent = 4, $wordwrap = 100, $no_opening_dashes = true);
		\ze::noteErrors();
		
		$g = gzopen($filepath, 'wb');
		gzwrite($g, $yaml);
		gzclose($g);
		
		return $filepath;
	}
	
	//Reverse of the above; ungzip a file, assume the contents are YAML, and convert to an
	//object in array format.
	public static function loadFromDisc($filepath) {
		
		$yaml = implode('', gzfile($filepath));
		
		$data = \Spyc::YAMLLoadString(trim($yaml));
		
		return $data;
	}
	
	
	//Make an export of a plugin.
	public static function exportPlugin($instanceId, $filepath = null) {
		
		$table = 'plugin_instances';
		$cols = ['name', 'module_id', 'framework', 'css_class', 'is_nest', 'is_slideshow'];
		
		//Include some metadata. (To do: Can we think of any more metadata to add here..?)
		$data = [
			'source_site' => \ze\link::absolute(),
			'plugin_instance' => []
		];
		\ze\data::exportSingleRow($data['plugin_instance'], $table, $cols, $instanceId);
		
		return \ze\data::saveToDisc($data, $filepath);
	}
	//Note: Any custom CSS is not included in this export.
	
	//Read in an import of a plugin.
	public static function importPlugin($filepath, $mustBe, $existingId = null) {
		
		$data = \ze\data::loadFromDisc($filepath);
		
		if (!$data) {
			echo \ze\admin::phrase('This file could not be read.');
			exit;
		}
		
		
		//Do some basic validation on the meta data before we start the import.
		$containedClassName = $data['plugin_instance']['module_id']['§module_class_name'] ?? null;
		
		if (!$containedClassName) {
			echo \ze\admin::phrase('This file does not contain an instance of a plugin.');
			exit;
		}
		
		//Don't allow the admin to import a plugin that couldn't be seen in the current view.
		switch ($mustBe) {
			case 'plugin':
				if (!empty($data['plugin_instance']['is_nest'])) {
					echo \ze\admin::phrase("This file contains a nest, and can't be imported into the plugin library.");
					exit;
				}
				if (!empty($data['plugin_instance']['is_slideshow'])) {
					echo \ze\admin::phrase("This file contains a slideshow, and can't be imported into the plugin library.");
					exit;
				}
				break;
				
				
			case 'nest':
				if (!empty($data['plugin_instance']['is_slideshow'])) {
					echo \ze\admin::phrase("This file contains a slideshow, and can't be imported into the nest library.");
					exit;
				}
				if (empty($data['plugin_instance']['is_nest'])) {
					echo \ze\admin::phrase("This file contains a plugin, and can't be imported into the nest library.");
					exit;
				}
				break;
				
				
			case 'slideshow':
				if (!empty($data['plugin_instance']['is_nest'])) {
					echo \ze\admin::phrase("This file contains a nest, and can't be imported into the slideshow library.");
					exit;
				}
				if (empty($data['plugin_instance']['is_slideshow'])) {
					echo \ze\admin::phrase("This file contains a plugin, and can't be imported into the slideshow library.");
					exit;
				}
				break;
			
			default:
				if ($containedClassName != $mustBe) {
					$mrg = [
						'display_name' => \ze\module::getModuleDisplayNameByClassName($mustBe)
					];
					echo \ze\admin::phrase("This file does not contain a [[display_name]].", $mrg);
					exit;
				}
		}				
		
		
		$table = 'plugin_instances';
		
		if (is_null($existingId)) {
			$name = $data['plugin_instance']['name'] ?? 'Imported';
			
			//Catch the case where someone exports a plugin then immediately imports it
			//back into the same site.
			//(Though I'm not sure why they'd do this, they have the "duplicate" button...)
			//This is just a very quick and dirty bit of code to make sure the name stays unique.
			if (\ze\row::exists($table, ['name' => $name])) {
				$i = 1;
				
				do {
					$newName = $name. '('. ++$i. ')';
				} while ($i <= 999 && \ze\row::exists($table, ['name' => $newName]));
				
				$name = $newName;
			}
			
			$data['plugin_instance']['name'] = $name;
		}
		
		//Do the import.
		$instanceId = \ze\data::import($table, $data['plugin_instance'], $existingId);
		
		if ($instanceId) {
			//The inline_images table will need updating after importing
			\ze\contentAdm::resyncLibraryPluginFiles($instanceId);
			
			//Update the request vars if this was a conductor
			\ze\pluginAdm::setSlideRequestVars($instanceId);
		}
		
		return $instanceId;
	}
}


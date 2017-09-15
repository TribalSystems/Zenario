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


class zenario_common_features__admin_boxes__export_dataset extends module_base_class {
	
	 public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
	 	
	 	//Copy the ids from the $box['key']['id'] variable...
	 	$box['key']['export_ids'] = $box['key']['id'];
	 	//...then clear $box['key']['id'] so that when the FAB is saved it does not select all of the items it exported
	 	$box['key']['id'] = '';
	 	
	 	
		$fields['download/desc']['snippet']['html'] = adminPhrase(
			'<p>Only dataset fields marked to be included in exports will appear in your download. You can mark fields by checking the "Include in export" option in the <a href="zenario/admin/organizer.php?#zenario__administration/panels/custom_datasets/item_buttons/edit_gui//[[dataset]]//" target="zenario_dataset_editor">dataset editor<a>.</p>
			<br>
			<p>If you\'re running any filters you will export the filtered view instead of all records.</p>'
		, array('dataset' => $box['key']['dataset']));
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		$sql = self::getExportableDatasetFieldsSQL($box['key']['dataset']);
		$result = sqlSelect($sql);
		$count = sqlNumRows($result);
		if ($count <= 0) {
			$box['tabs']['download']['errors'][] = adminPhrase('No dataset fields are marked to be included in an export, so your download file will be empty.');
		}
	}
	
	public function adminBoxDownload($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		// Get dataset fields with export property
		$dataset = getDatasetDetails($box['key']['dataset']);
		$sql = self::getExportableDatasetFieldsSQL($dataset['id']);
		$result = sqlSelect($sql);
		$systemFields = array();
		$customFields = array();
		$datasetColumns = array();
		$datasetColumnIdLink = array();
		$datasetFields = array();
		$rowTemplate = array();
		$data = array();
		$ord = 0;
		while ($row = sqlFetchAssoc($result)) {
			// Never export encrypted passwords
			if ($dataset['system_table'] == 'users' && !setting('plaintext_extranet_user_passwords') && $row['db_column'] == 'password') {
				continue;
			}
			
			if ($row['db_column']) {
				if ($row['is_system_field']) {
					$systemFields[] = $row['db_column'];
				} else {
					$customFields[] = $row['db_column'];
				}
				$datasetColumnIdLink[$row['db_column']] = $row['id'];
			}
			
			$datasetColumns[$row['id']] = !empty($row['db_column']) ? $row['db_column'] : $row['field_name'];
			
			$row['ord'] = ++$ord;
			$datasetFields[$row['id']] = $row;
			$rowTemplate[$row['id']] = '';
		}

		if ($dataset['system_table'] == 'users' && inc('zenario_user_activity_bands') && setting('zenario_user_activity_bands__add_activity_band_column')) {
			$datasetColumns[] = 'activity_bands';
		}
		
		//Get location descriptive page (if export is enabled for it)
		$locationContentItemFieldId = false;
		if (inc('zenario_location_manager') && $dataset['system_table'] == ZENARIO_LOCATION_MANAGER_PREFIX . 'locations') {
			$fieldId = getRow('custom_dataset_fields', 'id', array('dataset_id' => $dataset['id'], 'tab_name' => 'content_item', 'field_name' => 'content_item'));
			if (isset($datasetColumns[$fieldId])) {
				$locationContentItemFieldId = $fieldId;
				$systemFields[] = 'equiv_id';
				$systemFields[] = 'content_type';
			}
		}
		
		// Array of tables to get data from
		$recordTables = array(
			array(
				'table' => $dataset['system_table'],
				'fields' => $systemFields
			),
			array(
				'table' => $dataset['table'],
				'fields' => $customFields
			)
		);
		
		// Get data
		foreach ($recordTables as $recordTable) {
			if (!empty($recordTable['table'])) {
				$idColumn = getIdColumnOfTable($recordTable['table']);
				$recordTable['fields'][] = $idColumn;
				
				$sql = '
					SELECT ' . inEscape($recordTable['fields'], 'identifier') . '
					FROM ' . DB_NAME_PREFIX . sqlEscape($recordTable['table']) . '
					WHERE ' . sqlEscape($idColumn) . ' IN (' . inEscape($box['key']['export_ids']) . ')';
				$result = sqlSelect($sql);
				
				while ($row = sqlFetchAssoc($result)) {
					if (!isset($data[$row[$idColumn]])) {
						$data[$row[$idColumn]] = $rowTemplate;
					}
					
					foreach ($row as $col => $value) {
						// Don't export ID column
						if ($col == $idColumn) {
							continue;
						}
						
						// Set value
						$datasetFieldId = $datasetColumnIdLink[$col];
						$data[$row[$idColumn]][$datasetFieldId] = self::formatDatasetFieldValue($value, $datasetFields[$datasetFieldId]);
					}
					
					if ($locationContentItemFieldId && ($recordTable['table'] == $dataset['system_table'])) {
						$data[$row[$idColumn]][$locationContentItemFieldId] = $row['content_type'] . '_' . $row['equiv_id'];
					}
				}
			}
		}
		
		// Sort row values
		foreach ($data as $recordId => $record) {
			uksort($data[$recordId], function($a, $b) use ($datasetFields) {
				return $datasetFields[$a]['ord'] > $datasetFields[$b]['ord'] ? 1 : -1;
			});
			
			if ($dataset['system_table'] == 'users' && inc('zenario_user_activity_bands') && setting('zenario_user_activity_bands__add_activity_band_column')) {
				$data[$recordId][] = zenario_user_activity_bands::getUserActivityBands($recordId);
			}
		}
		

		$downloadFileName = $dataset['label'].' export '.date('Y-m-d');
		if ($values['download/type'] == 'csv') {
			$columnCount = count($datasetColumns);
			
			// Create temp file to write CSV to
			$filename = tempnam(sys_get_temp_dir(), 'tmpsamplefile');
			$f = fopen($filename, 'wb');
			
			// Write column headers then data to CSV
			fputcsv($f, $datasetColumns);
			foreach ($data as $row) {
				fwrite($f, implode(',', $row) . PHP_EOL);
			}
			fclose($f);
			
			// Offer file as download
			header('Content-Type: text/x-csv');
			header('Content-Disposition: attachment; filename="'.$downloadFileName.'.csv"');
			header('Content-Length: '. filesize($filename));
			readfile($filename);
			
			// Remove file from temp directory
			@unlink($filename);
		} else {
			require_once CMS_ROOT.'zenario/libraries/lgpl/PHPExcel/Classes/PHPExcel.php';
			$objPHPExcel = new PHPExcel();
			$objPHPExcel->getActiveSheet()->fromArray($datasetColumns, NULL, 'A1');
			$objPHPExcel->getActiveSheet()->fromArray($data, NULL, 'A2');
			header('Content-Type: application/vnd.ms-excel');
			header('Content-Disposition: attachment; filename="'.$downloadFileName.'.xls"');
			$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
			$objWriter->save('php://output');
		}
	}
	
	private static function formatDatasetFieldValue($value, $datasetField) {
		if ($value === NULL) {
			return '';
			if (($datasetField['type'] == 'checkbox') || ($datasetField['type'] == 'group')) {
				return '0';
			}
		}
		return $value;
	}
	
	private static function getExportableDatasetFieldsSQL($datasetId) {
		$sql = '
			SELECT f.id, f.db_column, f.is_system_field, f.type, f.tab_name, f.field_name
			FROM '.DB_NAME_PREFIX.'custom_dataset_fields f
			INNER JOIN '.DB_NAME_PREFIX.'custom_dataset_tabs t
				ON (f.dataset_id = t.dataset_id) AND (f.tab_name = t.name)
			WHERE f.dataset_id = '.(int)$datasetId. '
			AND f.include_in_export = 1
			AND f.type != "textarea"
			AND f.type != "editor"
			ORDER BY t.ord, f.ord';
		return $sql;
	}
}

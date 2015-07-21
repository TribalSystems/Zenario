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
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');


class zenario_common_features__admin_boxes__export_dataset extends module_base_class {
	
	 public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
	 	// TODO: Add link when GUI form builder is standard
		//$fields['download/desc']['snippet']['html'] = adminPhrase('Only dataset fields marked to be included in exports will appear in your download. To edit dataset fields click <a href="zenario/admin/organizer.php?#zenario__administration/panels/custom_datasets/item//[[dataset]]//">this link<a>.', array('dataset' => $box['key']['dataset']));
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		$sql = '
			SELECT f.id
			FROM '.DB_NAME_PREFIX.'custom_dataset_fields f
			INNER JOIN '.DB_NAME_PREFIX.'custom_dataset_tabs t
				ON (f.dataset_id = t.dataset_id) AND (f.tab_name = t.name)
			WHERE f.dataset_id = '.(int)$box['key']['dataset']. '
			AND f.db_column != ""
			AND f.include_in_export = 1
			ORDER BY t.ord, f.ord';
		$result = sqlSelect($sql);
		$count = sqlNumRows($result);
		if ($count <= 0) {
			$box['tabs']['download']['errors'][] = adminPhrase('No dataset fields are marked to be included in an export, so your download file will be empty.');
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		// Get dataset fields with export property
		$dataset = getDatasetDetails($box['key']['dataset']);
		$sql = '
			SELECT f.id, f.db_column, f.is_system_field, f.type
			FROM '.DB_NAME_PREFIX.'custom_dataset_fields f
			INNER JOIN '.DB_NAME_PREFIX.'custom_dataset_tabs t
				ON (f.dataset_id = t.dataset_id) AND (f.tab_name = t.name)
			WHERE f.dataset_id = '.(int)$dataset['id']. '
			AND f.db_column != ""
			AND f.include_in_export = 1
			ORDER BY t.ord, f.ord';
		$result = sqlSelect($sql);
		$datasetColumns = 
		$systemFields = 
		$customFields = array();
		$ord = 0;
		while ($row = sqlFetchAssoc($result)) {
			if ($row['is_system_field']) {
				$systemFields[] = $row['db_column'];
			} else {
				$customFields[] = 'c.' . $row['db_column'];
			}
			$datasetColumns[$row['id']] = $row['db_column'];
			$row['ord'] = ++$ord;
			$datasetFields[$row['db_column']] = $row;
		}
		
		// Get system fields from dataset
		$systemFields[] = 'id';
		$data = array();
		$result = getRows($dataset['system_table'], $systemFields, array());
		while ($row = sqlFetchAssoc($result)) {
			$indexedRow = array();
			foreach ($row as $col => $value) {
				if ($col == 'id') {
					continue;
				}
				if ($value === NULL) {
					$value = '';
					if (($datasetFields[$col]['type'] == 'checkbox') || ($datasetFields[$col]['type'] == 'group')) {
						$value = '0';
					}
				}
				$indexedRow[$datasetFields[$col]['ord']] = $value;
			}
			$data[$row['id']] = $indexedRow;
		}
		
		// Get custom table primary key
		$sql = '
			SHOW KEYS 
			FROM ' . DB_NAME_PREFIX . $dataset['table'] . '
			WHERE Key_name = "PRIMARY"';
		$result = sqlSelect($sql);
		$keysRow = sqlFetchAssoc($result);
		$primaryKey = $keysRow['Column_name'];
		$customFields[] = 'c.' . $primaryKey;
		
		// Get custom fields from dataset
		$sql = '
			SELECT s.id, ' . sqlEscape(implode(', ', $customFields)) . '
			FROM ' . DB_NAME_PREFIX . $dataset['system_table'] . ' s
			LEFT JOIN ' . DB_NAME_PREFIX . $dataset['table'] . ' c
				ON s.id = c.' . $primaryKey . '
		';
		$result = sqlSelect($sql);
		while ($row = sqlFetchAssoc($result)) {
			$id = $row['id'];
			if (isset($data[$id])) {
				unset($row[$primaryKey], $row['id']);
				foreach ($row as $col => $value) {
					if ($value === NULL) {
						$value = '';
						if (($datasetFields[$col]['type'] == 'checkbox') || ($datasetFields[$col]['type'] == 'group')) {
							$value = '0';
						}
					}
					$ord = $datasetFields[$col]['ord'];
					$data[$id][$ord] = $value;
				}
			}
			ksort($data[$id]);
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
			exit;
		} else {
			require_once CMS_ROOT.'zenario/libraries/lgpl/PHPExcel_1_7_8/Classes/PHPExcel.php';
			$objPHPExcel = new PHPExcel();
			$objPHPExcel->getActiveSheet()->fromArray($datasetColumns, NULL, 'A1');
			$objPHPExcel->getActiveSheet()->fromArray($data, NULL, 'A2');
			header('Content-Type: application/vnd.ms-excel');
			header('Content-Disposition: attachment; filename="'.$downloadFileName.'.xls"');
			$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
			$objWriter->save('php://output');
			exit;
		}
	}
}

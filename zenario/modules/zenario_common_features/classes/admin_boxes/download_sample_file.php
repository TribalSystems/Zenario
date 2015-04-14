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


class zenario_common_features__admin_boxes__download_sample_file extends module_base_class {
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		// Get user dataset columns
		$dataset = getDatasetDetails($box['key']['dataset']);
		$datasetColumns = array();
		$sql = '
			SELECT f.id, f.db_column
			FROM '.DB_NAME_PREFIX.'custom_dataset_fields f
			INNER JOIN '.DB_NAME_PREFIX.'custom_dataset_tabs t
				ON (f.dataset_id = t.dataset_id) AND (f.tab_name = t.name)
			WHERE f.dataset_id = '.(int)$dataset['id']. '
			AND f.db_column != ""
			ORDER BY t.ord, f.ord';
		$result = sqlSelect($sql);
		while ($row = sqlFetchAssoc($result)) {
			$datasetColumns[$row['id']] = $row['db_column'];
		}
		$downloadFileName = $dataset['label'].' sample file';
		if ($values['download/type'] == 'csv') {
			$columnCount = count($datasetColumns);
			// Create temp file to write CSV to
			$filename = tempnam(sys_get_temp_dir(), 'tmpsamplefile');
			$f = fopen($filename, 'wb');
			// Write column headers then blank lines to CSV
			fputcsv($f, $datasetColumns);
			$blankLine = '';
			for ($i = 1; $i < $columnCount; $i++) {
				$blankLine .= ',';
			}
			$blankLine .= "\n";
			$blankLines = '';
			for ($i = 0; $i < 10; $i++) {
				$blankLines .= $blankLine;
			}
			fwrite($f, $blankLines);
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
			header('Content-Type: application/vnd.ms-excel');
			header('Content-Disposition: attachment; filename="'.$downloadFileName.'.xls"');
			$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
			$objWriter->save('php://output');
			exit;
		}
	}
}

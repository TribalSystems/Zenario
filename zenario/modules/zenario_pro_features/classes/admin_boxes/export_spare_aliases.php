<?php
/*
 * Copyright (c) 2023, Tribal Limited
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

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class zenario_pro_features__admin_boxes__export_spare_aliases extends ze\moduleBaseClass {
	
	 public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
	 	//To show which fields included in export
		$fieldsToBeExportedSnippet = '<p>' . ze\admin::phrase('Fields to be exported:') . '</p>' . '<ul>';
		
		$fieldsToBeExportedArray = [
			'alias' => 'Spare alias',
			'content_item' => 'Redirect to content item',
			'ext_url' => 'Redirect to external URL',
			'created_datetime' => 'When created'
		];
		
		foreach ($fieldsToBeExportedArray as $fieldCodeName => $fieldLabel) {
			$fieldsToBeExportedSnippet .= '<li>' . ze\admin::phrase($fieldLabel) . ' (' . $fieldCodeName . ')';
			
			$fieldsToBeExportedSnippet .= '</li>';
		}
		$fieldsToBeExportedSnippet .= '</ul>';		
		
		$fields['download/desc']['snippet']['html'] = $fieldsToBeExportedSnippet;
	}
	
	public function adminBoxDownload($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		$dateTimeFormat = ze::setting('vis_date_format_med');
		
		$headers = [
			'Spare alias',
			'Redirect to content item',
			'Redirect to external URL',
			'When created'
		];
		
		$rows = [];
		$sql = "
			SELECT alias, content_id, content_type, ext_url, created_datetime
			FROM " . DB_PREFIX . "spare_aliases
			ORDER BY alias";
		$result = ze\sql::select($sql);
		while ($row = ze\sql::fetchAssoc($result)) {
			$row['created_datetime'] = ze\date::formatDateTime($row['created_datetime'], $dateTimeFormat);
			
			if ($row['content_id'] && $row['content_type']) {
				$row['content_item'] = ze\content::formatTag($row['content_id'], $row['content_type']);
			} else {
				$row['content_item'] = '';
			}
			
			$rows[] = [
				$row['alias'],
				$row['content_item'],
				$row['ext_url'],
				$row['created_datetime']
			];
		}		

		$downloadFileName = 'Spare aliases export ' . date('Y-m-d');
		
		if ($values['download/type'] == 'csv') {
			// Create temp file to write CSV to
			$filename = tempnam(sys_get_temp_dir(), 'tmpsamplefile');
			$f = fopen($filename, 'wb');
			
			// Write column headers then data to CSV
			fputcsv($f, $headers);
			foreach ($rows as $row) {
				fputcsv($f, $row);
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
			$objPHPSpreadsheet = new Spreadsheet();
			$activeWorksheet = $objPHPSpreadsheet->getActiveSheet();
			$activeWorksheet->fromArray($headers, NULL, 'A1');
			$activeWorksheet->fromArray($rows, NULL, 'A2');
			
			header('Content-Type: application/vnd.ms-excel');
			header('Content-Disposition: attachment; filename="' . $downloadFileName . '.xls"');
			
			$writer = new Xls($objPHPSpreadsheet);
			$writer->save('php://output');
		}
	}
}
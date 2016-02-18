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
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');


class zenario_common_features__admin_boxes__export_vlp extends module_base_class {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		$phrases = array();
		$sql = "
			SELECT COUNT(*)
			FROM (
				SELECT DISTINCT code, module_class_name
				FROM ". DB_NAME_PREFIX. "visitor_phrases
			) AS c";
		$result = sqlQuery($sql);
		list($phrases['total']) = sqlFetchRow($result);
		
		$sql = "
			SELECT COUNT(*)
			FROM (
				SELECT DISTINCT code, module_class_name
				FROM ". DB_NAME_PREFIX. "visitor_phrases
				WHERE language_id = '". sqlEscape($box['key']['id']). "'
			) AS c";
		$result = sqlQuery($sql);
		list($phrases['present']) = sqlFetchRow($result);
		$phrases['missing'] = $phrases['total'] - $phrases['present'];
		$phrases['lang'] = getLanguageName($box['key']['id']);
		$phrases['def_lang'] = getLanguageName(setting('default_language'));
		
		
		$box['tabs']['export']['fields']['desc']['snippet']['html'] =
			adminPhrase('Use this to download a spreadsheet of "[[lang]]" phrases.',$phrases);
		$box['tabs']['export']['fields']['option']['values']['present'] =
			adminPhrase('Only include phrases that are present ([[present]])', $phrases);
		$box['tabs']['export']['fields']['option']['values']['missing'] =
			adminPhrase('Only include phrases that are missing ([[missing]])', $phrases);
		$box['tabs']['export']['fields']['option']['values']['all'] =
			adminPhrase('Include all possible phrases ([[total]])', $phrases);
		
		if ($box['key']['id'] != setting('default_language')) {
			$box['tabs']['export']['fields']['desc']['snippet']['html'] .=
				' '.
				adminPhrase('"[[def_lang]]" will be used as a reference.',$phrases);
		}
		
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
	}


	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		
		if ($values['export/format'] == 'xlsx'
		 && !extension_loaded('zip')) {
			$box['tabs']['export']['errors'][] =
				adminPhrase('Importing or exporting .xlsx files requires the php_zip extension. Please ask your server administrator to enable it.');
		}
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
	}
	
	
	public function adminBoxDownload($path, $settingGroup, &$box, &$fields, &$values, $changes) {
	
		
		exitIfNotCheckPriv('_PRIV_MANAGE_LANGUAGE_PHRASE');
		
		
	
	
		//Functionality for Exporting and Importing Language Packs
		
		//Given a language id, get all of the Language phrases for that language.
		//Calls on the functions defined in the XML section of admin.inc.php
		
		$columnNamesPrinted = false;
		$currentModuleClass = '';
		$currentPluginStatus = 'module_running';
		
		$languageId = $box['key']['id'];
		
		require_once CMS_ROOT. 'zenario/libraries/lgpl/PHPExcel_1_7_8/Classes/PHPExcel.php';
		$objPHPExcel = new PHPExcel();
		$sheet = $objPHPExcel->setActiveSheetIndex(0);
		
		//Add the language id in
		$i = 0;
		//$sheet
		//	->setCellValueByColumnAndRow(0, ++$i, 'Zenario Language PACK WORKSHEET')
		//	->setCellValueByColumnAndRow(2, $i, 'Target Language ID')
		//	->setCellValueByColumnAndRow(0, ++$i, '(Do not edit this column)')
		//	->setCellValueByColumnAndRow(1, $i, 'To create a new Language Pack, change the value of the cell to the right to the ID for the language you are creating ->')
		//	->setCellValueByColumnAndRow(2, $i, $languageId);
		//++$i;
	
		//Look up all of the codes in the database
		//Order such that we have all of the core VLPs first, then VLPs grouped by modules
		$sql = "
			SELECT
				'". sqlEscape($languageId). "' AS `Language ID`,
				codes.module_class_name AS `Module`,
				IF (SUBSTR(codes.code, 1, 1) = '_', codes.code, '') AS `Phrase code`,
				IF (SUBSTR(codes.code, 1, 1) = '_',
					IF (SUBSTR(codes.code, 1, 2) = '__', '', reference.local_text),
					codes.code) AS `Reference text`,
				phrases.local_text AS `". sqlEscape(getLanguageName($languageId, false)). " translation`
			FROM (
				SELECT DISTINCT code, module_class_name
				FROM ". DB_NAME_PREFIX. "visitor_phrases
				WHERE code != '__LANGUAGE_FLAG_FILENAME__'
			) AS codes
			LEFT JOIN ". DB_NAME_PREFIX. "visitor_phrases AS phrases
			   ON phrases.code = codes.code
			  AND phrases.module_class_name = codes.module_class_name
			  AND phrases.language_id = '". sqlEscape($languageId). "'
			LEFT JOIN ". DB_NAME_PREFIX. "visitor_phrases AS reference
			   ON reference.code = codes.code
			  AND reference.module_class_name = codes.module_class_name
			  AND reference.language_id = '". sqlEscape(setting('default_language')). "'
			  AND reference.code != '__LANGUAGE_LOCAL_NAME__'";
		
		if ($values['export/option'] == 'missing') {
			$sql .= "
			WHERE phrases.id IS NULL";
		
		} elseif ($values['export/option'] == 'present') {
			$sql .= "
			WHERE phrases.id IS NOT NULL";
		}
		
		$sql .= "
			ORDER BY
				codes.module_class_name = '' DESC,
				codes.module_class_name = 'zenario_common_features' DESC,
				codes.module_class_name,
				instr(codes.code, '__') DESC,
				codes.code;";
		$result = sqlQuery($sql);
		
		//For each code, write it to the csv file
		while ($row = sqlFetchAssoc($result)) {
			
			//Check if this is the start of a group of VLPs for a Plugin, and checi if this Plugin is running if so
			if ($currentModuleClass != $row['Module']) {
				$currentModuleClass = $row['Module'];
				$currentPluginStatus = getModuleStatusByClassName($row['Module']);
			}
			
			if (!$currentPluginStatus || $currentPluginStatus == 'module_not_initialized') {
				continue;
			}
			
			//Print the columns headers in the first line
			if (!$columnNamesPrinted) {
				++$i;
				$j = -1;
				foreach ($row as $key => &$value) {
					$sheet->setCellValueByColumnAndRow(++$j, $i, $key);
				}
				
				$columnNamesPrinted = true;
			}
			
			if ($row['Phrase code'] == '__LANGUAGE_ENGLISH_NAME__') {
				$row['Reference text'] = adminPhrase('[The name of the language in English, e.g. English, French, German, Spanish...]');
			} elseif ($row['Phrase code'] == '__LANGUAGE_LOCAL_NAME__') {
				$row['Reference text'] = adminPhrase('[The name of the language, e.g. Deutsch, English, Español, Français...]');
			}
			
			//Print each row
			++$i;
			$j = -1;
			foreach ($row as $key => &$value) {
				$sheet->setCellValueByColumnAndRow(++$j, $i, $value);
			}
		}
		
		$extension = $values['export/format'];
		if (!in($extension, 'bom_csv', 'csv')) {
			
			$sheet->getProtection()->setSheet(true); 
			$editableBit = $sheet->getStyle('E2:E'. $i);
			$editableBit->getProtection()->setLocked(PHPExcel_Style_Protection::PROTECTION_UNPROTECTED);
			$editableBit->applyFromArray(array(
				'fill' => array(
					'type' => PHPExcel_Style_Fill::FILL_SOLID,
					'color' => array('rgb' => 'e0ffe0')
			)));
		}
		
		switch ($extension) {
			case 'xls':
				$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
				break;
			
			case 'xlsx':
				$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
				break;
			
			case 'csv':
			case 'bom_csv':
				$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'CSV')
					->setDelimiter(',')
					->setEnclosure('"')
					->setLineEnding("\r\n")
					->setSheetIndex(0);
				
				if ($extension == 'bom_csv') {
					$extension = 'csv';
					$objWriter->setUseBOM(true);
				}
			
				break;
			
			case 'html':
				$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'HTML');
				break;
			
			default:
				exit;
		}
		
		$mimeType = documentMimeType($extension);
		
		header('Content-Type: '. $mimeType. '; charset=UTF-8');
		header('Content-Disposition: attachment;filename="'. $languageId. '.'. $extension. '"');
		$objWriter->save('php://output');
		exit;
	}
}

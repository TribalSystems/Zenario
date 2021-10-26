<?php
/*
 * Copyright (c) 2021, Tribal Limited
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


class zenario_common_features__admin_boxes__export_vlp extends ze\moduleBaseClass {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		$phrases = [];
		$phrases['total'] = ze\sql::fetchValue("
			SELECT COUNT(DISTINCT code, module_class_name)
			FROM ". DB_PREFIX. "visitor_phrases"
		);
		$phrases['present'] = ze\sql::fetchValue("
			SELECT COUNT(DISTINCT code, module_class_name)
			FROM ". DB_PREFIX. "visitor_phrases
			WHERE language_id = '". ze\escape::asciiInSQL($box['key']['id']). "'
			  AND local_text IS NOT NULL
			  AND local_text != ''"
		);
		
		$phrases['missing'] = $phrases['total'] - $phrases['present'];
		$phrases['lang'] = ze\lang::name($box['key']['id']);
		$phrases['def_lang'] = ze\lang::name(ze::$defaultLang);
		
		
		$box['tabs']['export']['fields']['desc']['snippet']['html'] =
			ze\admin::phrase('Use this to download a spreadsheet of "[[lang]]" phrases.',$phrases);
		$box['tabs']['export']['fields']['option']['values']['present'] =
			ze\admin::phrase('Only include phrases that are present ([[present]])', $phrases);
		$box['tabs']['export']['fields']['option']['values']['missing'] =
			ze\admin::phrase('Only include phrases that are missing ([[missing]])', $phrases);
		$box['tabs']['export']['fields']['option']['values']['all'] =
			ze\admin::phrase('Include all possible phrases ([[total]])', $phrases);
		
		if ($box['key']['id'] != ze::$defaultLang) {
			$box['tabs']['export']['fields']['desc']['snippet']['html'] .=
				' '.
				ze\admin::phrase('"[[def_lang]]" will be used as a reference.',$phrases);
		}
		
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
	}


	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		
		if ($values['export/format'] == 'xlsx'
		 && !extension_loaded('zip')) {
			$box['tabs']['export']['errors'][] =
				ze\admin::phrase('Importing or exporting .xlsx files requires the php_zip extension. Please ask your server administrator to enable it.');
		}
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
	}
	
	
	public function adminBoxDownload($path, $settingGroup, &$box, &$fields, &$values, $changes) {
	
		
		ze\priv::exitIfNot('_PRIV_MANAGE_LANGUAGE_PHRASE');
		
		
	
	
		//Functionality for Exporting and Importing Language Packs
		
		//Given a language id, get all of the Language phrases for that language.
		//Calls on the functions defined in the XML section of admin.inc.php
		
		$columnNamesPrinted = false;
		$currentModuleClass = '';
		$currentPluginStatus = 'module_running';
		
		$languageId = $box['key']['id'];
		
		require_once CMS_ROOT. 'zenario/libs/manually_maintained/lgpl/PHPExcel/Classes/PHPExcel.php';
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
				'". ze\escape::sql($languageId). "' AS `Language ID`,
				codes.module_class_name AS `Module`,
				IF (SUBSTR(codes.code, 1, 1) = '_', codes.code, '') AS `Phrase code`,
				IF (SUBSTR(codes.code, 1, 1) = '_',
					IF (SUBSTR(codes.code, 1, 2) = '__', '', reference.local_text),
					codes.code) AS `Reference text`,
				phrases.local_text AS `". ze\escape::sql(ze\lang::name($languageId, false)). " translation`
			FROM (
				SELECT DISTINCT code, module_class_name
				FROM ". DB_PREFIX. "visitor_phrases
				WHERE code != '__LANGUAGE_FLAG_FILENAME__'
			) AS codes
			LEFT JOIN ". DB_PREFIX. "visitor_phrases AS phrases
			   ON phrases.code = codes.code
			  AND phrases.module_class_name = codes.module_class_name
			  AND phrases.language_id = '". ze\escape::asciiInSQL($languageId). "'
			  AND phrases.local_text IS NOT NULL
			  AND phrases.local_text != ''
			LEFT JOIN ". DB_PREFIX. "visitor_phrases AS reference
			   ON reference.code = codes.code
			  AND reference.module_class_name = codes.module_class_name
			  AND reference.language_id = '". ze\escape::asciiInSQL(ze::$defaultLang). "'
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
		$result = ze\sql::select($sql);
		
		//For each code, write it to the csv file
		while ($row = ze\sql::fetchAssoc($result)) {
			
			//Check if this is the start of a group of VLPs for a Plugin, and checi if this Plugin is running if so
			if ($currentModuleClass != $row['Module']) {
				$currentModuleClass = $row['Module'];
				$currentPluginStatus = ze\module::statusByName($row['Module']);
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
				$row['Reference text'] = ze\admin::phrase('[The name of the language in English, e.g. English, French, German, Spanish...]');
			} elseif ($row['Phrase code'] == '__LANGUAGE_LOCAL_NAME__') {
				$row['Reference text'] = ze\admin::phrase('[The name of the language, e.g. Deutsch, English, Español, Français...]');
			}
			
			//Print each row
			++$i;
			$j = -1;
			foreach ($row as $key => &$value) {
				$sheet->setCellValueByColumnAndRow(++$j, $i, $value);
			}
		}
		
		$extension = $values['export/format'];
		if (!ze::in($extension, 'bom_csv', 'csv')) {
			
			$sheet->getProtection()->setSheet(true); 
			$editableBit = $sheet->getStyle('E2:E'. $i);
			$editableBit->getProtection()->setLocked(PHPExcel_Style_Protection::PROTECTION_UNPROTECTED);
			$editableBit->applyFromArray([
				'fill' => [
					'type' => PHPExcel_Style_Fill::FILL_SOLID,
					'color' => ['rgb' => 'e0ffe0']
			]]);
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
		
		$mimeType = ze\file::mimeType($extension);
		
		header('Content-Type: '. $mimeType. '; charset=UTF-8');
		header('Content-Disposition: attachment;filename="'. $languageId. '.'. $extension. '"');
		$objWriter->save('php://output');
		exit;
	}
}

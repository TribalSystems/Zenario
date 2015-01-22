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

switch ($path) {
	case 'zenario_export_vlp':
		
		exitIfNotCheckPriv('_PRIV_MANAGE_LANGUAGE_PHRASE');
		
		
	
	
		//Functionality for Exporting and Importing Language Packs
		
		//Given a language id, get all of the Language phrases for that language.
		//Calls on the functions defined in the XML section of admin.inc.php
		
		$columnNamesPrinted = false;
		$currentModuleClass = '';
		$currentPluginStatus = 'module_running';
		
		$languageId = $box['key']['id'];
		$useReferenceText = $values['export/option'] != 'existing' && $languageId != setting('default_language');
		
		require_once CMS_ROOT. 'zenario/libraries/lgpl/PHPExcel_1_7_8/Classes/PHPExcel.php';
		$objPHPExcel = new PHPExcel();
		
		//Add the language id in
		$i = 0;
		$objPHPExcel->setActiveSheetIndex(0)
			->setCellValueByColumnAndRow(0, ++$i, 'Zenario Language PACK WORKSHEET')
			->setCellValueByColumnAndRow(2, $i, 'Target Language ID')
			->setCellValueByColumnAndRow(0, ++$i, '(Do not edit this column)')
			->setCellValueByColumnAndRow(1, $i, 'To create a new Language Pack, change the value of the cell to the right to the ID for the language you are creating ->')
			->setCellValueByColumnAndRow(2, $i, $languageId);
	
		//Look up all of the codes in the database
		//Order such that we have all of the core VLPs first, then VLPs grouped by modules
		$sql = "
			SELECT
				codes.module_class_name,
				codes.code AS `Phrase Code`,";
		
		if ($useReferenceText) {
			$sql .= "
				reference.local_text AS `Reference Text`,";
		}
		
		$sql .= "
				phrases.local_text AS `Translation`
			FROM (
				SELECT DISTINCT code, module_class_name
				FROM ". DB_NAME_PREFIX. "visitor_phrases
			) AS codes
			LEFT JOIN ". DB_NAME_PREFIX. "visitor_phrases AS phrases
			   ON phrases.code = codes.code
			  AND phrases.module_class_name = codes.module_class_name
			  AND phrases.language_id = '". sqlEscape($languageId). "'";
		
		if ($useReferenceText) {
			$sql .= "
			LEFT JOIN ". DB_NAME_PREFIX. "visitor_phrases AS reference
			   ON reference.code = codes.code
			  AND reference.module_class_name = codes.module_class_name
			  AND reference.language_id = '". sqlEscape(setting('default_language')). "'
			  AND reference.code != '__LANGUAGE_LOCAL_NAME__'";
		}
		
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
			if ($currentModuleClass != $row['module_class_name']) {
				$currentPluginStatus = getModuleStatusByClassName($row['module_class_name']);
			}
			
			if (!$currentPluginStatus || $currentPluginStatus == 'module_not_initialized') {
				$currentModuleClass = $row['module_class_name'];
				continue;
			}
				
			//Check if this is the start of a group of VLPs for a Plugin, and put a header in if so
			if ($currentModuleClass != $row['module_class_name']) {
				$currentModuleClass = $row['module_class_name'];

				++$i;
				$objPHPExcel->setActiveSheetIndex(0)
					->setCellValueByColumnAndRow(0, ++$i, 'Module')
					->setCellValueByColumnAndRow(0, ++$i, $currentModuleClass);
				$columnNamesPrinted = false;
			}
			unset($row['module_class_name']);
			
			//Print the columns names in the first line
			if (!$columnNamesPrinted) {
				$i += 2;
				$j = -1;
				foreach ($row as $key => &$value) {
					$objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(++$j, $i, $key);
				}
				
				$columnNamesPrinted = true;
			}
			
			//Print each row
			++$i;
			$j = -1;
			foreach ($row as $key => &$value) {
				$objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(++$j, $i, $value);
			}
		}
		
		switch ($extension = $values['export/format']) {
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

return false;
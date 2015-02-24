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

//Keep some results on what happens
$numberOf = array('upload_error' => false, 'wrong_language' => false, 'added' => 0, 'updated' => 0, 'protected' => 0);

//Assume that phrases are core, unless we see a Plugin's name
$moduleClass = '';
$columns = false;
$justHadAnEmptyLine = true;
$knownColumnHeader = array('Target Language ID' => 1, 'Phrase Code' => 1, 'Translation' => 1, 'Plugin' => 1, 'Module' => 1);

$mimeType = documentMimeType(str_replace('.php', '', ifNull($realFilename, $file)));

if (in($mimeType, 'text/csv', 'text/comma-separated-values')) {
	$csv = true;
	
	require_once CMS_ROOT. 'zenario/libraries/mit/parsecsv/parsecsv.lib.php';
	
	$pCSV = new parseCSV();
	ini_set('auto_detect_line_endings', true);
	
	$f = fopen($file, "r");

} else {
	$csv = false;

	require_once CMS_ROOT. 'zenario/libraries/lgpl/PHPExcel_1_7_8/Classes/PHPExcel.php';
	
	switch ($mimeType) {
		case 'application/vnd.ms-excel':
			$objReader = PHPExcel_IOFactory::createReader('Excel5');
			break;
		
		case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
			$objReader = PHPExcel_IOFactory::createReader('Excel2007');
			break;
		
		case 'application/vnd.oasis.opendocument.spreadsheet':
			$objReader = PHPExcel_IOFactory::createReader('OOCalc');
			break;
		
		default:
			$numberOf['upload_error'] = true;
			return $numberOf;
	}
	
	$objPHPExcel = $objReader->load($file);
	$sheet = $objPHPExcel->getActiveSheet();
	$maxI = $sheet->getHighestRow();
}

//Loop through each row
for ($i = 1; true; ++$i) {
	
	if ($csv) {
		if (!$row = fgets($f)) {
			break;
		}
		
		$row = $pCSV->parse_string("0,1,2,3,4,5\n". $row. "\n");
		$row = arrayKey($row, 0);
		$rowCount = count($row);
		
		//Ignore empty lines, though remember that we've had them
		if (!$row || $rowCount == 0 || $row[0] === '' || $row[0] === null) {
			$justHadAnEmptyLine = true;
			continue;
		}
	} else {
		if ($i > $maxI) {
			break;
		}
		
		$row = array(
			ifNull($sheet->getCellByColumnAndRow(0, $i)->getCalculatedValue(), '', ''),
			ifNull($sheet->getCellByColumnAndRow(1, $i)->getCalculatedValue(), '', ''),
			ifNull($sheet->getCellByColumnAndRow(2, $i)->getCalculatedValue(), '', ''));
		
		//Ignore empty lines, though remember that we've had them
		if (!$row[0]) {
			$justHadAnEmptyLine = true;
			continue;
		}
		
		foreach ($row as &$cell) {
			if (is_object($cell)) {
				$cell = $cell->getPlainText();
			}
		}
	}
	
	
	//Look out for column headers if we've not had them already...
	//...or if we've just had an empty line and we see what looks like a column header
	if ((
			!$columns || $justHadAnEmptyLine
		) && (
			issetArrayKey($knownColumnHeader, arrayKey($row, 0)) ||
			issetArrayKey($knownColumnHeader, arrayKey($row, 1)) ||
			issetArrayKey($knownColumnHeader, arrayKey($row, 2))
	)	) {
		
		//Little hack so that the import does not fail if someone overtypes the "Translation" column
		if (arrayKey($row, 0) == 'Phrase Code' && arrayKey($row, 1) == 'Reference Text' && arrayKey($row, 2)) {
			$row[2] = 'Translation';
		} elseif (arrayKey($row, 0) == 'Phrase Code' && arrayKey($row, 1)) {
			$row[1] = 'Translation';
		} elseif (arrayKey($row, 0) && arrayKey($row, 1) == 'Phrase Code') {
			$row[0] = 'Translation';
		}
		
		$columns = array_flip($row);
	
	
	//Check if this row has information on a phrase
	} elseif (isset($columns['Phrase Code']) && isset($columns['Translation'])) {
			//Report an error if we've not found the language id yet
			if ($scanning === true || $languageId === false) {
				$numberOf['upload_error'] = true;
				
				if ($csv) {
					fclose($f);
				}
				return $numberOf;
			
			} elseif ($scanning === 'full scan' || $scanning === 'number and file')  {
				++$numberOf['added'];
			
			//Add the phrase (unless it is for a Plugin that we don't have)
			} elseif ($moduleClass !== false) {
				importVisitorPhrase($languageId, $moduleClass, arrayKey($row, $columns['Phrase Code']), arrayKey($row, $columns['Translation']), $adding, $numberOf);
			}
	
	//Handle setup-information (allow this to be on the same line if needed)
	} else {
		//Look out for the language_id in the file before we have the data
		//The language id of this pack should be in the row immediately after
		if (isset($columns['Target Language ID'])) {
			
			//Read and note down the language_id
			$packLanguageId = $row[$columns['Target Language ID']];
					
			//If we were just looking for the language id, return it
			if ($scanning) {
				$languageId = $packLanguageId;
				if ($scanning !== 'full scan' && $scanning !== 'number and file') {
					if ($csv) {
						fclose($f);
					}
					return;
				}
			
			//Are we adding a new VLP? Use the language id from the language pack if so
			} elseif ($adding && !$languageId) {
				$languageId = $packLanguageId;
			
			//Are we updating an existing VLP? Check the language id from the pack matches this language id
			} elseif($packLanguageId != $languageId && !$forceLanguageIdOverride) {
				$numberOf['wrong_language'] = true;
				
				if ($csv) {
					fclose($f);
				}
				return $numberOf;
			}
		}
		
		//Look out for phrases associated with a Plugin
		foreach (array('Module', 'Plugin') as $header) {
			if (isset($columns[$header])) {
				if (!$row[$columns[$header]]) {
					$moduleClass = '';
				
				} elseif ($scanning === 'full scan' || $scanning === 'number and file') {
					$moduleClass = $row[$columns[$header]];
				
				//Check: do we have this Plugin..?
				} elseif (getModuleIdByClassName($row[$columns[$header]])) {
					$moduleClass = $row[$columns[$header]];
				
				} elseif (!$moduleClass = getModuleClassNameByName($row[$columns[$header]])) {
					$moduleClass = false;
				}
			}
		}
	}
}

if ($csv) {
	fclose($f);
}
return $numberOf;
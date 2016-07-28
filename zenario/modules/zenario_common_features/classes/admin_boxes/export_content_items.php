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


class zenario_common_features__admin_boxes__export_content_items extends module_base_class {
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		// Get row headers
		$headers = array(
			'ID/alias',
			'Alias',
			'Language',
			'Title',
			'Description',
			'Keywords',
			'Status',
			'Date/time first created',
			'First created by',
			'Date/time latest version created',
			'Latest version created by',
			'Images and animations',
			'Translations'
		);
		
		// Get Rows
		$sql = '
			SELECT
				c.id,
				c.type,
				c.equiv_id,
				c.tag_id,
				c.alias,
				c.language_id,
				v.title,
				v.description,
				v.keywords,
				c.status,
				c.first_created_datetime,
				v.creating_author_id,
				v.created_datetime,
				v.last_author_id,
				(
					SELECT COUNT(DISTINCT ii.image_id)
					FROM ' . DB_NAME_PREFIX . 'inline_images AS ii
					WHERE ii.foreign_key_to = "content"
					  AND ii.foreign_key_id = v.id
					  AND ii.foreign_key_char = v.type
					  AND ii.foreign_key_version = v.version
				) AS inline_files
				
			FROM ' . DB_NAME_PREFIX . 'content_items c
			INNER JOIN ' . DB_NAME_PREFIX . 'content_item_versions AS v
				ON c.id = v.id
				AND c.type = v.type
				AND c.admin_version = v.version
			WHERE TRUE';
		if (!empty($box['key']['type'])) {
			$sql .= '
				AND c.type = "' . sqlEscape($box['key']['type']) . '"';
		}
		$sql .= '
			ORDER BY c.tag_id';
		
		$result = sqlSelect($sql);
		$statusEnglishNames = array(
			'first_draft' => 'First draft',
			'published_with_draft' => 'Published with draft',
			'hidden_with_draft' => 'Hidden with draft',
			'trashed_with_draft' => 'Trashed with draft',
			'published' => 'Published',
			'hidden' => 'Hidden',
			'trashed' => 'Trashed'
		);
		$admins = array();
		$languages = getLanguages();
		$rows = array();
		
		
		$headers = array(
			'ID',
			'Alias',
			'Language',
			'Title',
			'Description',
			'Keywords',
			'Status',
			'Date/time first created',
			'First created by',
			'Date/time latest version created',
			'Latest version created by',
			'Images and animations',
			'Translations'
		);
		
		while ($row = sqlFetchAssoc($result)) {
			$contentItem = array();
			$contentItem['tag_id'] = $row['tag_id'];
			$contentItem['alias'] = $row['alias'];
			$contentItem['language'] = getLanguageName($row['language_id']);
			$contentItem['title'] = $row['title'];
			$contentItem['description'] = $row['description'];
			$contentItem['keywords'] = $row['keywords'];
			$contentItem['status'] = isset($statusEnglishNames[$row['status']]) ? $statusEnglishNames[$row['status']] : $row['status'];
			$contentItem['first_created_datetime'] = formatDateTimeNicely($row['first_created_datetime'], '_MEDIUM');
			$contentItem['creating_author'] = self::getAdminFullname($row['creating_author_id'], $admins);
			$contentItem['created_datetime'] = formatDateTimeNicely($row['created_datetime'], '_MEDIUM');
			$contentItem['last_author'] = self::getAdminFullname($row['last_author_id'], $admins);
			$contentItem['inline_files'] = (string)$row['inline_files'];
			$contentItem['translations'] = self::getTranslationCount($row['id'], $row['type'], $row['equiv_id'], $row['language_id'], $languages);
			$rows[] = $contentItem;
		}
		// Download file
		$downloadFileName = 'Content items export '.date('Y-m-d');
		if ($values['download/type'] == 'csv') {
			
			// Create temp file to write CSV to
			$filename = tempnam(sys_get_temp_dir(), 'content_items_export_');
			$f = fopen($filename, 'wb');
			
			// Write column headers then data to CSV
			fputcsv($f, $headers);
			foreach ($rows as $row) {
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
			$objPHPExcel->getActiveSheet()->fromArray($headers, NULL, 'A1');
			$objPHPExcel->getActiveSheet()->fromArray($rows, NULL, 'A2');
			header('Content-Type: application/vnd.ms-excel');
			header('Content-Disposition: attachment; filename="'.$downloadFileName.'.xls"');
			$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
			$objWriter->save('php://output');
		}
		exit;
	}
	
	public static function getAdminFullname($adminId, &$admins) {
		$creatingAuthor = '';
		if (isset($admins[$adminId])) {
			$creatingAuthor = $admins[$adminId];
		} else {
			$creatingAuthorDetails = getRow('admins', array('first_name', 'last_name'), $adminId);
			if ($creatingAuthorDetails) {
				$creatingAuthor = $creatingAuthorDetails['first_name'] . ' ' . $creatingAuthorDetails['last_name'];
			}
			$admins[$adminId] = $creatingAuthor;
		}
		return $creatingAuthor;
	}
	
	public static function getTranslationCount($cId, $cType, $equivId, $languageId, $languages) {
		$translations = 1;
		$equivs = equivalences($cId, $cType, true, $equivId);
		if (!empty($equivs)) {
			foreach($languages as $lang) {
				if (!empty($equivs[$lang['id']])) {
					if ($lang['id'] != $languageId) {
						++$translations;
					}
				}
			}
		}
		if ($translations == 1) {
			$translations = 'untranslated';
		} else {
			$translations .= ' / '. count($languages);
		}
		return $translations;
	}
}

<?php
/*
 * Copyright (c) 2024, Tribal Limited
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

class zenario_common_features__admin_boxes__export_content_items extends ze\moduleBaseClass {
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$box['key']['numLanguages'] = ze\lang::count();
		if ($box['key']['type'] || $box['key']['exportDuplicates']) {
			$box['key']['contentTypeDetails'] = ze\contentAdm::cTypeDetails($box['key']['type']);
			$box['key']['contentTypeDetails']['export_filename'] = false;
			
			if (ze::in($box['key']['type'], 'audio', 'document', 'picture', 'video')) {
				$box['key']['contentTypeDetails']['export_filename'] = true;
			}
		} else {
			$box['key']['contentTypeDetails'] = [
				'description_field' => 'mandatory',
				'keywords_field' => 'mandatory',
				'release_date_field' => 'mandatory',
				'writer_field' => 'mandatory',
				'enable_categories' => true,
				'export_filename' => true
			];
		}
		
		$headers = [];
		
		if ($box['key']['exportDuplicates']) {
			$headers[] = 'File ID';
			$headers[] = 'Checksum';
			$headers[] = 'Filename';

			if (ze\module::inc('zenario_ctype_document_extra_data')) {
				$headers[] = 'Reference/case no.';
			}
		}
		
		$headers[] = 'ID';
		$headers[] = 'Alias';
		
		if ($box['key']['numLanguages'] > 1) {
			$headers[] = 'Language';
			$headers[] = 'Translations';
		}
		
		$headers[] = 'Title';
		
		if (ze::in($box['key']['contentTypeDetails']['description_field'], 'optional', 'mandatory')) {
			$headers[] = 'Description';
		}
	
		if (ze::in($box['key']['contentTypeDetails']['keywords_field'], 'optional', 'mandatory')) {
			$headers[] = 'Keywords';
		}
		
		$headers[] = 'Status';
		$headers[] = 'Date/time first created';
		$headers[] = 'First created by';
		$headers[] = 'Date/time latest version created';
		$headers[] = 'Latest version created by';
		
		if ($box['key']['contentTypeDetails']['enable_categories']) {
			$headers[] = 'Categories';
		}
		
		if (ze::in($box['key']['contentTypeDetails']['release_date_field'], 'optional', 'mandatory')) {
			$headers[] = 'Release date';
		}
		
		if (ze::in($box['key']['contentTypeDetails']['writer_field'], 'optional', 'mandatory')) {
			$headers[] = 'Writer';
		}
		
		if ($box['key']['exportDuplicates']) {
			$headers[] = 'Version';
			$headers[] = 'Page word count';
			$headers[] = 'Attachment word count';
		}
		
		if (!$box['key']['exportDuplicates'] && $box['key']['contentTypeDetails']['export_filename']) {
			//In "Export duplicates" version, the filename is already added at the top. No need to add it again.
			$headers[] = 'Filename';
		}
		
		$headers[] = 'Menu path';
		$headers[] = 'Layout';
		
		if (!$box['key']['exportDuplicates']) {
			$headers[] = 'Images and animations';
			
			$exportAccesses = (ze::setting('period_to_delete_the_user_content_access_log'));
			if ($exportAccesses) {
				$headers[] = 'Accesses';
			}
		}
		
		$box['key']['headers'] = $headers;
		
		$datasetFieldNames = '<ul>';
		foreach ($headers as $row) {
			
			if ($row) {
				$datasetFieldNames .= '<li>'.$row.'</li>';
			}
		}
		$datasetFieldNames .= '</ul>';
		
		if ($box['key']['wasFiltered']) {
			$box['title'] = ze\admin::nPhrase('Export filtered list (1 item)', 'Export filtered list ([[count]] items)', count(ze\ray::explodeAndTrim($box['key']['id'])));
		}
		
		$linkHeader = ze\admin::phrase('<p>Fields to be exported:</p>');
		$fields['download/desc']['snippet']['html'] = $linkHeader.'<p>'.$datasetFieldNames. '</p>';
	}
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//Get Rows
		$sql = '
			SELECT';
		
		if ($box['key']['exportDuplicates'] || $box['key']['contentTypeDetails']['export_filename']) {
			$sql .= '
				f.id AS file_id,
				v.filename,';
		}
		
		if ($box['key']['exportDuplicates']) {
			$sql .= '
					f.short_checksum,';
			
			$sql .= '
					IFNULL(cc.text_wordcount, 0) AS text_wordcount,
					IFNULL(cc.extract_wordcount, 0) AS extract_wordcount,';
			
			if (ze\module::inc('zenario_ctype_document_extra_data')) {
				$sql .= ',
					extra.ref_no,';
			}
		}
		
		$sql .= '
				c.id,
				c.type,
				c.equiv_id,
				c.tag_id,
				c.alias,';
		
		if ($box['key']['numLanguages'] > 1) {
			$sql .= '
				c.language_id,';
		}
		
		$sql .= '
				v.title,
				v.version AS version_id,
				c.status,
				c.first_created_datetime,
				v.creating_author_id,
				v.created_datetime,
				v.last_author_id,
				v.last_modified_datetime';
		
		if (ze::in($box['key']['contentTypeDetails']['description_field'], 'optional', 'mandatory')) {
			$sql .= ',
			v.description';
		}
		
		if (ze::in($box['key']['contentTypeDetails']['keywords_field'], 'optional', 'mandatory')) {
			$sql .= ',
				v.keywords';
		}
		
		if (ze::in($box['key']['contentTypeDetails']['release_date_field'], 'optional', 'mandatory')) {
			$sql .= ',
			v.release_date';
		}
		
		if (ze::in($box['key']['contentTypeDetails']['writer_field'], 'optional', 'mandatory')) {
			$sql .= ',
			wp.first_name AS writer_first_name, wp.last_name AS writer_last_name';
		}
		
		if (!$box['key']['exportDuplicates']) {
			$sql .= ',
					(
						SELECT COUNT(DISTINCT ii.image_id)
						FROM ' . DB_PREFIX . 'inline_images AS ii
						WHERE ii.foreign_key_to = "content"
						AND ii.foreign_key_id = v.id
						AND ii.foreign_key_char = v.type
						AND ii.foreign_key_version = v.version
					) AS inline_files';
			
			$exportAccesses = (ze::setting('period_to_delete_the_user_content_access_log'));
			if ($exportAccesses) {
				$sql .= ',
					IF(COUNT(uca.user_id) > 0, COUNT(uca.user_id), 0) AS accesses';
			}
		}
		
		$sql .= '
			FROM ' . DB_PREFIX . 'content_items c
			INNER JOIN ' . DB_PREFIX . 'content_item_versions AS v
				ON c.id = v.id
				AND c.type = v.type
				AND c.admin_version = v.version';
		
		if ($box['key']['exportDuplicates'] || $box['key']['contentTypeDetails']['export_filename']) {
			$sql .= '
			LEFT JOIN ' . DB_PREFIX . 'files AS f
                ON f.id = v.file_id
			LEFT JOIN ' . DB_PREFIX . 'content_cache AS cc
				ON v.id = cc.content_id
				AND v.type = cc.content_type
				AND v.version = cc.content_version';
		}
		
		if ($box['key']['exportDuplicates']) {
			if (ze\module::inc('zenario_ctype_document_extra_data')) {
				$sql .= '
					LEFT JOIN ' . DB_PREFIX . ZENARIO_CTYPE_DOCUMENT_EXTRA_DATA_PREFIX . 'document_extra_data extra
						ON v.id = extra.id
						AND v.type = extra.type
						AND v.version = extra.version';
			}
		}
		
		if (ze::in($box['key']['contentTypeDetails']['writer_field'], 'optional', 'mandatory')) {
			$sql .= '
			LEFT JOIN ' . DB_PREFIX . 'writer_profiles AS wp
				ON v.writer_id = wp.id';
		}
		
		if (!$box['key']['exportDuplicates'] && $exportAccesses) {
			$sql .= '
				LEFT JOIN ' . DB_PREFIX . 'user_content_accesslog AS uca
					ON v.id = uca.content_id
					AND v.type = uca.content_type
					AND v.version = uca.content_version';
		}
		
		$sql .= '
			WHERE TRUE';
			
		if (!empty($box['key']['id'])) {
			$sql .= '
				AND c.tag_id IN (' . ze\escape::in($box['key']['id'], 'asciiInSQL') . ')';
		}
		if (!empty($box['key']['type'])) {
			$sql .= '
				AND c.type = "' . ze\escape::asciiInSQL($box['key']['type']) . '"';
		}
		
		$sql .= '
			GROUP BY c.tag_id';
		
		if ($box['key']['exportDuplicates']) {
			$sql .= '
				ORDER BY f.id, c.type, c.id';
		} else {
			$sql .= '
				ORDER BY c.type, c.id';
		}
		
		$result = ze\sql::select($sql);
		$statusEnglishNames = [
			'first_draft' => 'First draft',
			'published_with_draft' => 'Published with draft',
			'hidden_with_draft' => 'Hidden with draft',
			'trashed_with_draft' => 'Trashed with draft',
			'published' => 'Published',
			'hidden' => 'Hidden',
			'trashed' => 'Trashed'
		];
		$admins = [];
		$languages = ze\lang::getLanguages();
		$rows = [];
		
		while ($row = ze\sql::fetchAssoc($result)) {
			$contentItem = [];
			
			if ($box['key']['exportDuplicates']) {
				$contentItem['file_id'] = $row['file_id'];
				$contentItem['short_checksum'] = $row['short_checksum'];
				$contentItem['filename'] = $row['filename'];
				
				if (ze\module::inc('zenario_ctype_document_extra_data')) {
					$contentItem['ref_no'] = $row['ref_no'];
				}
			}
			
			$contentItem['tag_id'] = $row['tag_id'];
			$contentItem['alias'] = $row['alias'];
			
			if ($box['key']['numLanguages'] > 1) {
				$contentItem['language'] = ze\lang::name($row['language_id']);
				$contentItem['translations'] = self::getTranslationCount($row['id'], $row['type'], $row['equiv_id'], $row['language_id'], $languages);
			}
			
			$contentItem['title'] = $row['title'];
			
			if (ze::in($box['key']['contentTypeDetails']['description_field'], 'optional', 'mandatory')) {
				$contentItem['description'] = $row['description'];
			}
			
			if (ze::in($box['key']['contentTypeDetails']['keywords_field'], 'optional', 'mandatory')) {
				$contentItem['keywords'] = $row['keywords'];
			}
			
			$contentItem['status'] = isset($statusEnglishNames[$row['status']]) ? $statusEnglishNames[$row['status']] : $row['status'];
			$contentItem['first_created_datetime'] = ze\admin::formatDateTime($row['first_created_datetime'], '_MEDIUM');
			$contentItem['creating_author'] = self::getAdminFullname($row['creating_author_id'], $admins);
			
			$contentItem['last_modified_datetime'] = ze\admin::formatDateTime($row['last_modified_datetime'], '_MEDIUM');
			$contentItem['last_author'] = self::getAdminFullname($row['last_author_id'], $admins);
			
			if ($box['key']['contentTypeDetails']['enable_categories']) {
				$contentItemCategories = ze\category::contentItemCategories($row['id'], $row['type']);
				if (!empty($contentItemCategories)) {
					$contentItem['categories'] = [];
					
					foreach ($contentItemCategories as $category) {
						$contentItem['categories'][] = ($category['public_name'] ?: $category['name']);
					}
					
					$contentItem['categories'] = implode(', ', $contentItem['categories']);
				} else {
					$contentItem['categories'] = '';
				}
			}
			
			if (ze::in($box['key']['contentTypeDetails']['release_date_field'], 'optional', 'mandatory')) {
				$contentItem['release_date'] = ze\admin::formatDate($row['release_date'], '_MEDIUM');
			}
			
			if (ze::in($box['key']['contentTypeDetails']['writer_field'], 'optional', 'mandatory')) {
				$contentItem['writer_name'] = trim(implode(" ", [$row['writer_first_name'], $row['writer_last_name']]));
			}
			
			if ($box['key']['exportDuplicates']) {
				$contentItem['version_id'] = $row['version_id'];
				$contentItem['text_wordcount'] = $row['text_wordcount'];
				$contentItem['extract_wordcount'] = $row['extract_wordcount'];
			}
			
			if ($box['key']['exportDuplicates'] || $box['key']['contentTypeDetails']['export_filename']) {
				$contentItem['filename'] = $row['filename'];
			}
			
			$menuArray = ze\menu::getFromContentItem($row['id'], $row['type']);
			if (!empty($menuArray)) {
				$contentItem['menu'] = ze\menuAdm::pathWithSection($menuArray['mID']);
			} else {
				$contentItem['menu'] = ze\lang::phrase('not in menu');
			}
			
			$contentItem['layout'] = ze\layoutAdm::codeName(ze\content::layoutId($row['id'], $row['type'], $row['version_id']));
			
			if (!$box['key']['exportDuplicates']) {
				$contentItem['inline_files'] = (string)$row['inline_files'];
			
				if ($exportAccesses) {
					$contentItem['accesses'] = $row['accesses'];
				}
			}
			
			$rows[] = $contentItem;
						
		}
		
		// Download file
		$downloadFileName = 'Content items export '.date('Y-m-d');
		if ($values['download/type'] == 'csv') {
			
			// Create temp file to write CSV to
			$filename = tempnam(sys_get_temp_dir(), 'content_items_export_');
			$f = fopen($filename, 'wb');
			
			// Write column headers then data to CSV
			fputcsv($f, $box['key']['headers']);
			foreach ($rows as $row) {
				fputcsv($f, $row);
			}
			fclose($f);
			
			// Offer file as download
			header('Content-Type: text/x-csv');
			header('Content-Disposition: attachment; filename="' . $downloadFileName . '.csv"');
			header('Content-Length: '. filesize($filename));
			readfile($filename);
			
			// Remove file from temp directory
			@unlink($filename);
		} else {
			$objPHPSpreadsheet = new Spreadsheet();
			$activeWorksheet = $objPHPSpreadsheet->getActiveSheet();
			$activeWorksheet->fromArray($box['key']['headers'], NULL, 'A1');
			$activeWorksheet->fromArray($rows, NULL, 'A2');
			
			header('Content-Type: application/vnd.ms-excel');
			header('Content-Disposition: attachment; filename="' . $downloadFileName . '.xls"');
			
			$writer = new Xls($objPHPSpreadsheet);
			$writer->save('php://output');
		}
		exit;
	}
	
	public static function getAdminFullname($adminId, &$admins) {
		$creatingAuthor = '';
		if (isset($admins[$adminId])) {
			$creatingAuthor = $admins[$adminId];
		} else {
			$creatingAuthor = ze\admin::formatName($adminId);
			$admins[$adminId] = $creatingAuthor;
		}
		return $creatingAuthor;
	}
	
	public static function getTranslationCount($cId, $cType, $equivId, $languageId, $languages) {
		$translations = 1;
		$equivs = ze\content::equivalences($cId, $cType, true, $equivId);
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
